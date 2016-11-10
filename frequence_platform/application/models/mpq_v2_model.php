<?php

final class k_is_retargeting
{
	const yes_retargeting = true;
	const no_retargeting = false;

	private function __construct() { } // disable class instantiation
}

class mpq_session_data
{
	public $session_id = '';
	public $region_data = '';
	public $demographic_data = '';
	public $selected_iab_categories = '';

	public function __construct()
	{
	}
}

class Mpq_v2_model extends CI_Model
{
	private $ci;

	public function __construct()
	{
		$this->load->database();

		$this->ci =& get_instance();
		$this->ci->load->library('tank_auth');
		$this->ci->load->library('google_api');
		$this->load->model('users');
		$this->load->model('cup_model');
		$this->load->model('al_model');
		$this->load->helper('url');
		$this->load->helper('budget_calculation_helper');
	}


	public function get_fixed_user_id()
	{
		$user_id = $this->ci->tank_auth->get_user_id();
		$user_id = $this->fix_user_id($user_id);
		return $user_id;
	}

	private function fix_user_id($user_id)
	{
		$fixed_user_id = $user_id;
		if($user_id == 0 || $user_id == '')
		{
			$fixed_user_id = null;
		}

		return $fixed_user_id;
	}

	// split mpq_cpm which is a single value into a retargeting and impression portion
	public function split_mpq_cpm_for_option_engine(&$impression_cpm, &$retargeting_cpm, $mpq_cpm)
	{
		$k_mpq_to_retrageting_cpm_portion = 0.2;

		$impression_cpm = null;
		$retargeting_cpm = null;
		if(!is_null($mpq_cpm))
		{
			$impression_cpm = $mpq_cpm * (1.0 - $k_mpq_to_retrageting_cpm_portion);
			$retargeting_cpm = $mpq_cpm * $k_mpq_to_retrageting_cpm_portion;
		}
	}

	public function get_options_from_mpq($session_id)
	{
		$bindings = array();
		$bindings[] = $session_id;
		$sql = '
			SELECT oap.type, oap.amount, oap.term, oap.duration, oap.cpm, oap.discount, oap.list_order
			FROM mpq_sessions_and_submissions AS mps JOIN
				mpq_options_ad_plans AS oap ON (mps.id = oap.mpq_id)
			WHERE
				mps.session_id = ?
			ORDER BY
				oap.type ASC, oap.list_order ASC
		';
		$response = $this->db->query($sql, $bindings);
		$results = $response->result('mpq_budget_option_get');

		return $results;
	}

	public function get_options_from_mpq_by_id($id)
	{
		$bindings = array();
		$bindings[] = $id;
		$sql = '
			SELECT oap.type, oap.amount, oap.term, oap.duration, oap.cpm, oap.discount, oap.list_order
			FROM mpq_sessions_and_submissions AS mps JOIN
				mpq_options_ad_plans AS oap ON (mps.id = oap.mpq_id)
			WHERE
				mps.id = ?
			ORDER BY
				oap.type ASC, oap.list_order ASC
		';
		$response = $this->db->query($sql, $bindings);
		$results = $response->result('mpq_budget_option_get');

		return $results;
	}

	public function save_insertion_order($input_array)
	{
		$is_success = false;
		$error_message = '';

		$result = array(
			'is_success' => false,
			'errors' => ''
		);

		$sql = 'SELECT id FROM mpq_sessions_and_submissions WHERE session_id = ?';
		$query = $this->db->query($sql, $input_array['session_id']);
		if($query->num_rows() > 0)
		{
			$mpq_id = $query->row()->id;
			$sql = 'INSERT INTO mpq_insertion_orders (mpq_id, impressions, start_date, term_type, term_duration, end_date, landing_page, include_rtg) VALUES(?, ?, ?, ?, ?, ?, ?, ?)';
			$query = $this->db->query($sql, array($mpq_id, $input_array['impressions'], $input_array['start_date'], $input_array['term_type'], $input_array['term_duration'], $input_array['end_date'], $input_array['landing_page'], $input_array['is_retargeting']));
			if($this->db->affected_rows() > 0)
			{
				$result['is_success'] = true;
				$result['id'] = $this->db->insert_id();
			}
			else
			{
				$result['errors'] = 'Error: failed to insert insertion order into database. (#4190)';
			}
		}
		else
		{
			$result['errors'] = 'Error: unable to find associated mpq session. (#4390)';
		}

		return $result;
	}

	public function save_options_to_mpq($session_id, $options)
	{
		$is_success = false;
		$error_message = '';

		$delete_bindings = array();
		$delete_bindings[] = $session_id;
		$delete_sql = '
			DELETE FROM oap
			USING mpq_options_ad_plans AS oap
				JOIN mpq_sessions_and_submissions as mp ON (oap.mpq_id = mp.id)
			WHERE
				mp.session_id = ?
		';
		$delete_response = $this->db->query($delete_sql, $delete_bindings);
		if($delete_response !== false)
		{
			if(count($options) > 0)
			{
				$get_lap_id_bindings = array();
				$get_lap_id_bindings[] = $session_id;
				$get_lap_id_sql = '
					SELECT id
					FROM mpq_sessions_and_submissions
					WHERE session_id = ?
				';
				$get_lap_id_response = $this->db->query($get_lap_id_sql, $get_lap_id_bindings);

				if($get_lap_id_response->num_rows() > 0)
				{
					$row = $get_lap_id_response->row();
					$lap_id = $row->id;

					$insert_bindings = array();
					$insert_sql = '
						INSERT INTO mpq_options_ad_plans (mpq_id, type, amount, term, duration, cpm, discount, list_order)
						VALUES
					';

					$skip_last_option = count($options) - 1;
					foreach($options as $ii=>$option)
					{
						$addition_sql = '(?, ?, ?, ?, ?, ?, ?, ?)';
						if($skip_last_option != $ii)
						{
							$addition_sql .= ',';
						}
						$insert_sql .= $addition_sql;

						$insert_bindings[] = $lap_id;
						$insert_bindings[] = $option->type;
						$insert_bindings[] = str_replace(',', '', $option->amount.'');
						$insert_bindings[] = $option->term;
						$insert_bindings[] = $option->duration;

						$insert_bindings[] = $option->cpm;
						$insert_bindings[] = $option->discount;
						$insert_bindings[] = $option->order;
					}

					$insert_response = $this->db->query($insert_sql, $insert_bindings);
					if($insert_response !== false)
					{
						$is_success = true;
					}
					else
					{
						$is_success = false;
						$error_message = 'Error: failed to insert options into database. (#987534)';
					}
				}
				else
				{
					$is_success = false;
					$error_message = 'Error: no lap associated with session in mpq. (#438923)';
				}
			}
			else
			{
				$is_success = true;
			}
		}
		else
		{
			$is_success = false;
			$error_message = 'Error: failed to delete old options when saving new options in mpq. (#57983)';
		}

		$response = true;
		if($is_success !== true)
		{
			$response = $error_message;
		}

		return $response;
	}

	public function save_submitted_mpq_and_remove_from_session(
		$demographics,
		$channels,
		$adset_request_id,
		$advertiser,
		$submitter,
		$session_id,
		$user_id,
		$industry_id,
		$rooftops_data = null,
		$cc_owner = false,
		$allocation_method = null,
		$existing_mpq_id = false
		)
	{
		$demographics = $this->convert_demo_category_if_all_unset($demographics);
		if($existing_mpq_id)
		{
			$sql = 'SELECT id, region_data FROM mpq_sessions_and_submissions WHERE id = ?';
			$query = $this->db->query($sql, $existing_mpq_id);			
		}
		else
		{
			$sql = 'SELECT id, region_data FROM mpq_sessions_and_submissions WHERE session_id = ?';
			$query = $this->db->query($sql, $session_id);
		}
		if($query->num_rows() > 0)
		{
			$geo_data = $query->row_array();
			if(count($channels) > 0)
			{
				$values_string_sql = "";
				$binding_array = array();
				$channel_mpq_id = $geo_data['id'];
				foreach($channels as $v)
				{
					if($values_string_sql != "")
					{
						$values_string_sql .= ",";
					}
					else
					{
						$values_string_sql .= "VALUES ";
					}
					$values_string_sql .= "(?, ?)";
					$binding_array[] = $channel_mpq_id;
					$binding_array[] = $v;
				}
				$sql = 'INSERT IGNORE INTO mpq_iab_categories_join (mpq_id, iab_category_id) '.$values_string_sql;
				$query = $this->db->query($sql, $binding_array);
			}
		}
		$date_time = date("Y-m-d H:i:s");
		$sql = '
			UPDATE mpq_sessions_and_submissions
			SET
				creation_time = ?,
				is_submitted = 1,
				rooftops_data = ?,
				demographic_data = ?,
				selected_iab_categories = NULL,
				advertiser_name = ?,
				advertiser_website = ?,
				submitter_name = ?,
				submitter_relationship = ?,
				submitter_agency_website = ?,
				submitter_email = ?,
				submitter_phone = ?,
				notes = ?,
				adset_request_id = ?,
				session_id = NULL,
				industry_id = ?,
				budget_allocation_method_type = ?,
				owner_user_id = ?,
				cc_owner = ?
			WHERE id = ?
		';

		$owner_user_id = null;
		if(isset($submitter->id))
		{
			$owner_user_id = $submitter->id;
		}

		$bindings = array(
			$date_time,
			$rooftops_data,
			$demographics,
			$advertiser->business_name,
			$advertiser->website_url,
			$submitter->name,
			$submitter->role,
			$submitter->website,
			$submitter->email_address,
			$submitter->phone_number,
			$submitter->notes,
			$adset_request_id,
			$industry_id,
			$allocation_method,
			$owner_user_id,
			$cc_owner,
			$geo_data['id']
			);


		$response = $this->db->query($sql, $bindings);
		return array('success' => $response, 'geo_data' => $geo_data);
	}

	public function get_all_primary_iab_contextual_channels()
	{
		$sql = "SELECT DISTINCT `primary_topic` FROM iab_contextual_channels ORDER BY `primary_topic`";
		$response = $this->db->query($sql);

		$query_response_array = $response->result_array();
		return $query_response_array;
	}

	public function get_iab_contextual_channels_by_search_term($search_term, $start, $limit)
	{
		$sql = "SELECT * FROM iab_categories WHERE tag_copy LIKE ? ORDER BY tag_copy LIMIT ?, ?";
		$query = $this->db->query($sql, array($search_term, $start, $limit));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_industries_by_search_term($search_term, $start, $limit, $allowed_industries = false)
	{
		$bindings = array($search_term);
		$strategy_sql = "";
		if ($allowed_industries)
		{
			$strategy_sql = "AND freq_industry_tags_id IN (";
			foreach ($allowed_industries as $i => $industry)
			{
				$strategy_sql .= $i === 0 ? "?" : ",?";
				$bindings[] = $industry;
			}
			$strategy_sql .= ") ";
		}
		$bindings[] = $start;
		$bindings[] = $limit;

		$sql = "SELECT * FROM freq_industry_tags WHERE name LIKE ? ".$strategy_sql." ORDER BY name LIMIT ?, ?";
		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_selected_industry($search_term, $start, $limit)
	{
		$sql = "SELECT * FROM freq_industry_tags WHERE name LIKE ? ORDER BY name LIMIT ?, ?";
		$query = $this->db->query($sql, array($search_term, $start, $limit));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_all_iab_contextual_channels()
	{
		$sql = "SELECT * FROM iab_categories WHERE 1 ORDER BY tag_copy";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_all_industries()
	{
		$sql = "SELECT * FROM freq_industry_tags WHERE 1 ORDER BY name";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_iab_contextual_channels_by_mpq_id($mpq_id)
	{
		$sql = '
			SELECT
				iabc.id AS id,
				iabc.tag_copy AS text,
				iabc.tag_copy AS tag_copy
			FROM
				iab_categories iabc
				JOIN mpq_iab_categories_join iabj
					ON iabc.id = iabj.iab_category_id
			WHERE
				iabj.mpq_id = ?
			ORDER BY
				iabc.tag_copy';
		$query = $this->db->query($sql, $mpq_id);
		if($query->num_rows() > 0)
		{
			return $query->result();
		}
		return false;
	}

	public function get_mpq_session_data($session_id)
	{
		$result = false;

		$bindings = array();
		$bindings[] = $session_id;

		$query = "
			SELECT
				id,
				session_id,
				strategy_id,
				region_data,
				demographic_data,
				selected_iab_categories,
				industry_id,
				unique_display_id
			FROM mpq_sessions_and_submissions
			WHERE
				session_id = ?
		";
		$response = $this->db->query($query, $bindings);
		if($response && $response->num_rows() > 0)
		{
			$session_data = $response->row(0, 'mpq_session_data');
			$result = $session_data;
		}

		return $result;
	}

	public function get_io_data_for_adset_request($mpq_id)
	{
		$bindings[] = $mpq_id;

		$query =
		"	SELECT
				mss.advertiser_name,
				mss.advertiser_website,
				mss.io_advertiser_id,
				mss.source_table
			FROM mpq_sessions_and_submissions mss
			WHERE mss.id = ?
		";
		$response = $this->db->query($query, $bindings);

		if ($response && $response->num_rows() > 0)
		{
			$response = $response->row_array();

			if (!empty($response['io_advertiser_id']))
			{
				$bindings = array();
				$bindings[] = $response['io_advertiser_id'];

				if ($response['source_table'] == 'Advertisers')
				{
					$table_in_query = " SELECT adv.Name AS io_advertiser_name FROM Advertisers adv ";
				}
				else
				{
					$table_in_query = " SELECT adv.name AS io_advertiser_name FROM advertisers_unverified adv ";
				}

				$query =
					"	$table_in_query
						WHERE adv.id = ?
					";

				$new_response = $this->db->query($query, $bindings);

				if ($new_response && $new_response->num_rows() > 0)
				{
					$new_response = $new_response->row_array();
					$response['advertiser_name'] = $new_response['io_advertiser_name'];
				}
			}

			unset($response['io_advertiser_name']);
			return $response;
		}

		return false;
	}

	public function get_mpq_submitter_id_by_mpq_id($mpq_id)
	{
		$sql =
		"	SELECT
				creator_user_id
			FROM
				mpq_sessions_and_submissions
			WHERE id = ?
		";
		$result = $this->db->query($sql, $mpq_id);
		if($result->num_rows > 0)
		{
			$user_id = $result->row()->creator_user_id;
			if(is_numeric($user_id))
			{
				return intval($user_id);
			}
		}
		return false;
	}

	public function get_mpq_session_data_by_id($id)
	{
		$result = false;

		$bindings = array();
		$bindings[] = $id;

		$query = "
			SELECT session_id, region_data, demographic_data, selected_iab_categories, is_submitted
			FROM mpq_sessions_and_submissions
			WHERE
				id = ?
		";
		$response = $this->db->query($query, $bindings);
		if($response && $response->num_rows() > 0)
		{
			$session_data = $response->row(0, 'mpq_session_data');
			$result = $session_data;
		}

		return $result;
	}

	// get data for mpq modal box
	public function get_mpq_user_profile($user_id)
	{
		$user_sql = '
			(SELECT
				COALESCE(CONCAT(u.firstname," ",u.lastname), u.email) AS user_full_name,
				u.email AS user_email,
				u.role AS user_role,
					IF(u.role = \'BUSINESS\',
					(SELECT u_sales.partner_id AS partner_id
					FROM users u_inner
					JOIN Advertisers a ON (u_inner.advertiser_id = a.id)
					JOIN users u_sales ON (a.sales_person = u_sales.id)
					WHERE u_inner.id = u.id),
					u.partner_id
					) AS partner_id,
					u.advertiser_id as user_advertiser_id,
					IF(u.role = \'BUSINESS\',
					(SELECT adv.Name as adver_name
					FROM `Advertisers` adv
					WHERE adv.id = u.advertiser_id),
					NULL
					) as advertiser_name,
					u.id AS user_id
				FROM users AS u
				WHERE u.id = ? LIMIT 1) user_profile';

		$user_partner_sql = '(SELECT user_profile.*, p.partner_name as partner_org, p.home_url as partner_home_page, p.contact_number as partner_phone FROM'.
			$user_sql
			.' JOIN wl_partner_details p ON (user_profile.partner_id = p.id)) user_partner_join';

		$sql =   'SELECT user_partner_join.*,  IF(user_partner_join.user_role = \'BUSINESS\', c.LandingPage, NULL) as recent_landing_page FROM'.
			$user_partner_sql
			.' LEFT JOIN Campaigns c ON (user_partner_join.user_advertiser_id = c.business_id)';

		//echo $sql.'<br>';

		$query = $this->db->query($sql, $user_id);
		if($query->num_rows() > 0)
		{
			$return = $query->row_array();
		}
		else
		{
			$return = NULL;
		}
		return $return;
	}

	public function do_write_query_with_create_on_fail($sql, $bindings)
	{
		$response = $this->db->query($sql, $bindings);
		if($response == false || $this->db->affected_rows() <= 0)
		{
			$session_id = $this->session->userdata('session_id');
			$owner_id = $this->get_fixed_user_id();
			$this->initialize_mpq_session($session_id, $owner_id);

			$response = $this->db->query($sql, $bindings);
		}

		return $response;
	}

	public function save_region_table_to_mpq_sessions($session_id, $regions, $location_id, $mpq_id = false)
	{
		if(is_numeric($location_id))
		{
			if($mpq_id !== false)
			{
				$select_sql = "
					SELECT region_data
					FROM mpq_sessions_and_submissions
					WHERE id = ?";
				$result = $this->db->query($select_sql, $mpq_id);
			}
			else
			{
				$select_sql =
					"	SELECT `region_data`
						FROM `mpq_sessions_and_submissions`
						WHERE `session_id` = ?;";
				$result = $this->db->query($select_sql, $session_id);
			}
			if($result->num_rows() > 0)
			{
				$current_regions = json_decode($result->row()->region_data, true);
				$current_regions[$location_id] = $regions;
				ksort($current_regions);
				$json_regions = json_encode(array_values($current_regions));
			}
			else
			{
				$json_regions = json_encode(array($regions));
			}
		}
		else
		{
			$json_regions = json_encode($regions);
		}
		if($mpq_id !== false)
		{
			$sql = "
			    UPDATE `mpq_sessions_and_submissions`
				SET `region_data` = ?, `last_updated` = CURRENT_TIMESTAMP
				WHERE `id` = ?;";
			$bindings = array($json_regions, $mpq_id);
		}
		else
		{
			$sql = "
				UPDATE `mpq_sessions_and_submissions`
				SET `region_data` = ?, `last_updated` = CURRENT_TIMESTAMP
				WHERE `session_id` = ?;";
			$bindings = array($json_regions, $session_id);
		}
		$this->do_write_query_with_create_on_fail($sql, $bindings);
	}

	public function update_industry_for_mpq($industry_id, $mpq_id)
	{
		$sql =
		"	UPDATE `mpq_sessions_and_submissions`
			SET `industry_id` = ?
			WHERE `id` = ?;
		";
		$bindings = array($industry_id, $mpq_id);
		$this->db->query($sql, $bindings);
	}

	public function save_zips($target_zips, $latitude, $longitude, $session_id, $location_name, $location_id = null, $is_custom_regions = false, $mpq_id = false)
	{
		$zip_data = $this->get_zips_data($target_zips, $latitude, $longitude, $session_id, $location_name, $location_id);
		if($is_custom_regions == 'true')
		{
			$zip_data['search_type'] = 'custom_regions';
		}
		else
		{
			$zip_data['search_type'] = 'known_zips';
		}
		$this->save_region_table_to_mpq_sessions($session_id, $zip_data, $location_id, $mpq_id);

		return $zip_data['ids'];
	}

	public function get_zips_data($target_zips, $latitude, $longitude, $session_id, $location_name, $location_id = null)
	{
		$data = array();
		$data['page'] = (is_numeric($location_id)) ? intval($location_id) : 0;
		$data['user_supplied_name'] = $location_name;
		$data['total'] = 0;
		$data['ids'] = ['zcta' => array()];

		$result = $this->map->get_regions_that_exist_in_db_from_region_array($target_zips);
		$data['ids']['zcta'] = $result;
		$data['total'] = count($result);
		return $data;
	}

	public function initialize_mpq_session($session_id, $owner_id, $unique_display_id = false)
	{
		$this->unlock_mpq_session_by_session_id($session_id);

		//if there is no entry for this session id insert
		$date_time_created = date("Y-m-d H:i:s");
		// initial demographics set to all on, with 75 reach frequency ratio, 'All' somthing.
		$initializeDemographics = '0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_75_All_unusedString';

		$bindings = array();
		$bindings[] = $session_id;
		$bindings[] = $initializeDemographics;
		$bindings[] = $owner_id;
		$bindings[] = $date_time_created;
		$bindings[] = json_encode(array());
		$unique_display_id_sql = "";
		$unique_display_id_replace = "";
		if($unique_display_id !== false)
		{
			$bindings[] = $unique_display_id;
			$unique_display_id_sql = ", ?";
			$unique_display_id_replace = ", `unique_display_id`";
		}

		$sql =
		"	REPLACE INTO `mpq_sessions_and_submissions` (
				`session_id`,
				`demographic_data`,
				`creator_user_id`,
				`creation_time`,
				`region_data`".$unique_display_id_replace."
			)
			VALUES (?, ?, ?, ?, ?".$unique_display_id_sql.")
		";

		$result = $this->db->query($sql, $bindings);
		return $this->db->insert_id();
	}

	public function initialize_mpq_session_for_rfp(
		$session_id,
		$user_id,
		$unique_display_id,
		$owner_id,
		$advertiser_name,
		$advertiser_website,
		$industry,
		$strategy_id,
		$proposal_name,
        $presentation_date
	)
	{
		$this->unlock_mpq_session_by_session_id($session_id);

		//if there is no entry for this session id insert
		$date_time_created = date("Y-m-d H:i:s");
		// initial demographics set to all on, with 75 reach frequency ratio, 'All' somthing.
		$initializeDemographics = '0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_75_All_unusedString';

		$bindings = array();
		$bindings[] = $session_id;
		$bindings[] = $initializeDemographics;
		$bindings[] = $user_id;
		$bindings[] = $date_time_created;
		$bindings[] = json_encode(array());
		$bindings[] = $owner_id;
		$bindings[] = $advertiser_name;
		$bindings[] = $advertiser_website;
		$bindings[] = $industry;
		$bindings[] = $strategy_id;
		$unique_display_id_sql = "";
		$unique_display_id_replace = "";
		if($unique_display_id !== false)
		{
			$bindings[] = $unique_display_id;
			$unique_display_id_sql = ", ?";
			$unique_display_id_replace = ", `unique_display_id`";
		}
		$bindings[] = $proposal_name;
		$bindings[] = $presentation_date;

		$sql =
		"	REPLACE INTO `mpq_sessions_and_submissions` (
				`session_id`,
				`demographic_data`,
				`creator_user_id`,
				`creation_time`,
				`region_data`,
				`owner_user_id`,
				`advertiser_name`,
				`advertiser_website`,
				`industry_id`,
				`strategy_id`".$unique_display_id_replace.",
				`proposal_name`,
				`presentation_date`
			)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?".$unique_display_id_sql.")
		";

		$result = $this->db->query($sql, $bindings);
		return $this->db->insert_id();
	}

	// Adds a single zipcode to the region_data field in the // from lap_lite_model.php table
	public function add_zipcode($zipcode, $session_id, $location_id)
	{
		$query =
		"	SELECT `region_data`
			FROM `mpq_sessions_and_submissions`
			WHERE `session_id` = ?;
		";
		$bindings = array($session_id);
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			$original_json_data = $response->row()->region_data;
			$region_data = json_decode($original_json_data, true);
			$this->map->convert_old_flexigrid_format($region_data);

			if(!in_array($zipcode, $region_data[intval($location_id)]['ids']['zcta']))
			{
				$details_response = $this->map->get_demographics_from_region_array(array('zcta' => $zipcode));
				$test_response = array_filter($details_response);

				if(!empty($test_response))
				{
					$region_data[intval($location_id)]['ids']['zcta'][] = $zipcode;
					$region_data[intval($location_id)]['ids']['zcta'] = array_unique($region_data[intval($location_id)]['ids']['zcta']);
					$region_data[intval($location_id)]['total'] = count($region_data[intval($location_id)]['ids']['zcta']);
				}
				$region_data[intval($location_id)]['search_type'] = 'known_zips';
				ksort($region_data);
				$result_json_data = json_encode(array_values($region_data));
				$this->load_session_region_table($session_id, $result_json_data);
			}
		}
	}

	// Removes a single zipcode from the region_data field in the mpq_sessions_and_submissionstable
	public function remove_zipcode($zipcode, $session_id, $location_id)
	{
		$query =
		"	SELECT
				`region_data`
			FROM
				`mpq_sessions_and_submissions`
			WHERE
				`session_id` = ?;
		";
		$bindings = array($session_id);
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			$is_modified = false;
			$original_json_data = $response->row()->region_data;
			$region_data = json_decode($original_json_data, true);
			$this->map->convert_old_flexigrid_format($region_data);

			if(isset($region_data[intval($location_id)]))
			{
				$ids = $region_data[intval($location_id)]['ids']['zcta'];
				$diff = array_values(array_diff($ids, array($zipcode)));
				$is_modified = ($ids !== $diff);
			}

			if($is_modified)
			{
				$region_data[intval($location_id)]['total'] = count($diff);
				$region_data[intval($location_id)]['ids']['zcta'] = $diff;
				$region_data[intval($location_id)]['search_type'] = 'known_zips';
				$result_json_data = json_encode($region_data);
				$this->load_session_region_table($session_id, $result_json_data);
			}
		}
	}

	public function load_session_region_table($session_id, $json_regions)
	{
		// from lap_lite_model.php
		$sql =
		"	UPDATE `mpq_sessions_and_submissions`
			SET `region_data` = ?,
				`last_updated` = CURRENT_TIMESTAMP
			WHERE `session_id` = ?
		";
		$bindings = array($json_regions, $session_id);
		$this->do_write_query_with_create_on_fail($sql, $bindings);
	}

	public function do_geo_search_and_save_result($search_criteria, $session_id, $location_id, $location_name, $address = "")
	{
		$search_criteria_array = explode("_", urldecode($search_criteria));
		$radius_searched = $search_criteria_array[1];
		$zips = $this->map->get_zips_from_radius_and_center(array('latitude' => $search_criteria_array[2], 'longitude' => $search_criteria_array[3]), $radius_searched);
		$zip_array = array_column($zips, 'zips');

		$data = array();

		$data['page'] = (is_numeric($location_id)) ? intval($location_id) : 0;
		$data['user_supplied_name'] = $location_name;
		$data['total'] = 0;
		$data['counter'] = $radius_searched;
		$data['address'] = $address;
		$data['search_type'] = 'radius';
		$data['ids'] = ['zcta' => $zip_array];
		$data['total'] = count($zip_array);

		$this->save_region_table_to_mpq_sessions($session_id, $data, $location_id);

		return $zip_array;
	}

	private $impression_rate_scale = array(
		array(1500000,4.969135802469135,0),
		array(1495000,4.970240307196828,0),
		array(1490000,4.971352224707928,0),
		array(1485000,4.972471629879037,0),
		array(1480000,4.973598598598598,0),
		array(1475000,4.974733207784054,0),
		array(1470000,4.975875535399345,0),
		array(1465000,4.977025660472758,0),
		array(1460000,4.97818366311517,0),
		array(1455000,4.979349624538627,0),
		array(1450000,4.980523627075351,0),
		array(1445000,4.981705754197104,0),
		array(1440000,4.982896090534979,0),
		array(1435000,4.9840947218996,0),
		array(1430000,4.985301735301735,0),
		array(1425000,4.98651721897336,0),
		array(1420000,4.987741262389149,0),
		array(1415000,4.988973956288443,0),
		array(1410000,4.990215392697662,0),
		array(1405000,4.991465664953209,0),
		array(1400000,4.992724867724867,0),
		array(1395000,4.993993097039692,0),
		array(1390000,4.99527045030642,0),
		array(1385000,4.996557026340419,0),
		array(1380000,4.997852925389157,0),
		array(1375000,4.999158249158249,0),
		array(1370000,5.000473100838064,0),
		array(1365000,5.001797585130918,0),
		array(1360000,5.003131808278868,0),
		array(1355000,5.004475878092114,0),
		array(1350000,5.005829903978052,0),
		array(1345000,5.007193996970948,0),
		array(1340000,5.008568269762299,0),
		array(1335000,5.009952836731862,0),
		array(1330000,5.011347813979392,0),
		array(1325000,5.012753319357093,0),
		array(1320000,5.014169472502806,0),
		array(1315000,5.015596394873961,0),
		array(1310000,5.017034209782301,0),
		array(1305000,5.018483042429402,0),
		array(1300000,5.01994301994302,0),
		array(1295000,5.021414271414272,0),
		array(1290000,5.022896927935687,0),
		array(1285000,5.02439112264015,0),
		array(1280000,5.02589699074074,0),
		array(1275000,5.027414669571532,0),
		array(1270000,5.028944298629338,0),
		array(1265000,5.030486019616454,0),
		array(1260000,5.032039976484421,0),
		array(1255000,5.033606315478824,0),
		array(1250000,5.035185185185186,0),
		array(1245000,5.036776736575933,0),
		array(1240000,5.038381123058541,0),
		array(1235000,5.039998500524816,0),
		array(1230000,5.041629027401384,0),
		array(1225000,5.043272864701437,0),
		array(1220000,5.044930176077716,0),
		array(1215000,5.046601127876847,0),
		array(1210000,5.048285889194979,0),
		array(1205000,5.049984631934838,0),
		array(1200000,5.051697530864196,0),
		array(1195000,5.053424763675808,0),
		array(1190000,5.055166511048863,0),
		array(1185000,5.056922956711985,0),
		array(1180000,5.058694287507845,0),
		array(1175000,5.060480693459416,0),
		array(1170000,5.062282367837922,0),
		array(1165000,5.064099507232553,0),
		array(1160000,5.065932311621967,0),
		array(1155000,5.06778098444765,0),
		array(1150000,5.06964573268921,0),
		array(1145000,5.071526766941613,0),
		array(1140000,5.073424301494477,0),
		array(1135000,5.075338554413443,0),
		array(1130000,5.077269747623728,0),
		array(1125000,5.079218106995884,0),
		array(1120000,5.081183862433861,0),
		array(1115000,5.083167247965454,0),
		array(1110000,5.085168501835168,0),
		array(1105000,5.08718786659963,0),
		array(1100000,5.089225589225588,0),
		array(1095000,5.091281921190595,0),
		array(1090000,5.093357118586475,0),
		array(1085000,5.095451442225635,0),
		array(1080000,5.097565157750342,0),
		array(1075000,5.099698535745047,0),
		array(1070000,5.101851851851851,0),
		array(1065000,5.104025386889235,0),
		array(1060000,5.106219426974143,0),
		array(1055000,5.108434263647533,0),
		array(1050000,5.110670194003527,0),
		array(1045000,5.112927520822256,0),
		array(1040000,5.115206552706552,0),
		array(1035000,5.117507604222579,0),
		array(1030000,5.119830996044587,0),
		array(1025000,5.122177055103884,0),
		array(1020000,5.124546114742192,0),
		array(1015000,5.126938514869548,0),
		array(1010000,5.129354602126877,0),
		array(1005000,5.131794730053436,0),
		array(1000000,5.134259259259259,0),
		array(995000,5.136748557602828,0),
		array(990000,5.139263000374111,0),
		array(985000,5.141802970483173,0),
		array(980000,5.144368858654572,0),
		array(975000,5.146961063627729,0),
		array(970000,5.149579992363496,0),
		array(965000,5.152226060257148,0),
		array(960000,5.154899691358023,0),
		array(955000,5.157601318596082,0),
		array(950000,5.160331384015594,0),
		array(945000,5.163090339016264,0),
		array(940000,5.165878644602048,0),
		array(935000,5.168696771637948,0),
		array(930000,5.171545201115093,0),
		array(925000,5.174424424424424,0),
		array(920000,5.17733494363929,0),
		array(915000,5.180277271807325,0),
		array(910000,5.183251933251933,0),
		array(905000,5.186259463883772,0),
		array(900000,5.189300411522632,0),
		array(895000,5.192375336230083,0),
		array(890000,5.195484810653349,0),
		array(885000,5.198629420380832,0),
		array(880000,5.201809764309763,0),
		array(875000,5.205026455026454,0),
		array(870000,5.208280119199658,0),
		array(865000,5.211571397987582,0),
		array(860000,5.214900947459086,0),
		array(855000,5.218269439029672,0),
		array(850000,5.221677559912853,0),
		array(845000,5.225126013587551,0),
		array(840000,5.228615520282186,0),
		array(835000,5.232146817476157,0),
		array(830000,5.235720660419456,0),
		array(825000,5.239337822671156,0),
		array(820000,5.242999096657631,0),
		array(815000,5.246705294251306,0),
		array(810000,5.250457247370827,0),
		array(805000,5.254255808603634,0),
		array(800000,5.25810185185185,0),
		array(795000,5.261996273002561,0),
		array(790000,5.265939990623534,0),
		array(785000,5.269933946685538,0),
		array(780000,5.27397910731244,0),
		array(775000,5.278076463560335,0),
		array(770000,5.282227032227031,0),
		array(765000,5.286431856693294,0),
		array(760000,5.29069200779727,0),
		array(755000,5.295008584743683,0),
		array(750000,5.299382716049382,0),
		array(745000,5.303815560526968,0),
		array(740000,5.308308308308307,0),
		array(735000,5.3128621819098,0),
		array(730000,5.31747843734145,0),
		array(725000,5.322158365261813,0),
		array(720000,5.326903292181068,0),
		array(715000,5.33171458171458,0),
		array(710000,5.33659363588941,0),
		array(705000,5.341541896506434,0),
		array(700000,5.346560846560846,0),
		array(695000,5.351652011723953,0),
		array(690000,5.356816961889424,0),
		array(685000,5.36205731278724,0),
		array(680000,5.367374727668844,0),
		array(675000,5.372770919067214,0),
		array(670000,5.37824765063571,0),
		array(665000,5.383806739069896,0),
		array(660000,5.389450056116722,0),
		array(655000,5.395179530675713,0),
		array(650000,5.400997150997149,0),
		array(645000,5.406904966982485,0),
		array(640000,5.412905092592592,0),
		array(635000,5.418999708369787,0),
		array(630000,5.425191064079952,0),
		array(625000,5.43148148148148,0),
		array(620000,5.437873357228195,0),
		array(615000,5.44436916591388,0),
		array(610000,5.450971463266544,0),
		array(605000,5.457682889501069,0),
		array(600000,5.464506172839505,0),
		array(595000,5.471444133208838,0),
		array(590000,5.478499686126804,0),
		array(585000,5.485675846786957,0),
		array(580000,5.492975734355044,0),
		array(575000,5.500402576489533,0),
		array(570000,5.507959714100064,0),
		array(565000,5.51565060635857,0),
		array(560000,5.523478835978836,0),
		array(555000,5.531448114781448,0),
		array(550000,5.53956228956229,0),
		array(545000,5.547825348284063,0),
		array(540000,5.556241426611796,0),
		array(535000,5.564814814814814,0),
		array(530000,5.573549965059398,0),
		array(525000,5.582451499118166,0),
		array(520000,5.591524216524216,0),
		array(515000,5.600773103200287,0),
		array(510000,5.610203340595496,0),
		array(505000,5.619820315364869,0),
		array(500000,5.62962962962963,0),
		array(495000,5.639637111859334,0),
		array(490000,5.649848828420256,0),
		array(485000,5.660271095838105,0),
		array(480000,5.67091049382716,0),
		array(475000,5.6817738791423,0),
		array(470000,5.692868400315208,0),
		array(465000,5.704201513341298,0),
		array(460000,5.715780998389693,0),
		array(455000,5.727614977614977,0),
		array(450000,5.739711934156379,0),
		array(445000,5.752080732417811,0),
		array(440000,5.764730639730639,0),
		array(435000,5.777671349510429,0),
		array(430000,5.790913006029285,0),
		array(425000,5.80446623093682,0),
		array(420000,5.818342151675485,0),
		array(415000,5.832552431950021,0),
		array(410000,5.847109304426376,0),
		array(405000,5.862025605852766,0),
		array(400000,5.877314814814814,0),
		array(395000,5.892991092358179,0),
		array(390000,5.909069325735992,0),
		array(385000,5.925565175565175,0),
		array(380000,5.942495126705653,0),
		array(375000,5.959876543209877,0),
		array(370000,5.977727727727728,0),
		array(365000,5.996067985794012,0),
		array(360000,6.014917695473249,0),
		array(355000,6.034298382889932,0),
		array(350000,6.054232804232804,0),
		array(345000,6.074745034889962,0),
		array(340000,6.095860566448802,0),
		array(335000,6.117606412382531,0),
		array(330000,6.140011223344556,0),
		array(325000,6.163105413105413,0),
		array(320000,6.186921296296296,0),
		array(315000,6.211493239271016,0),
		array(310000,6.236857825567502,0),
		array(305000,6.263054037644201,0),
		array(300000,6.290123456790123,0),
		array(295000,6.31811048336472,0),
		array(290000,6.347062579821198,0),
		array(285000,6.37703053931124,0),
		array(280000,6.408068783068782,0),
		array(275000,6.440235690235689,0),
		array(270000,6.473593964334704,0),
		array(265000,6.508211041229909,0),
		array(260000,6.544159544159544,0),
		array(255000,6.581517792302105,0),
		array(250000,6.620370370370369,0),
		array(245000,6.660808767951624,0),
		array(240000,6.70293209876543,0),
		array(235000,6.746847911741528,0),
		array(230000,6.792673107890498,0),
		array(225000,6.840534979423866,0),
		array(220000,6.89057239057239,0),
		array(215000,6.94293712316968,0),
		array(210000,6.99779541446208,0),
		array(205000,7.055329719963865,0),
		array(200000,7.115740740740739,0),
		array(195000,7.179249762583095,0),
		array(190000,7.246101364522416,0),
		array(185000,7.316566566566564,0),
		array(180000,7.390946502057611,0),
		array(175000,7.469576719576718,0),
		array(170000,7.552832244008713,0),
		array(165000,7.641133557800223,0),
		array(160000,7.734953703703703,0),
		array(155000,7.834826762246116,0),
		array(150000,7.941358024691358,0),
		array(145000,8.055236270753511,0),
		array(140000,8.177248677248677,0),
		array(135000,8.30829903978052,0),
		array(130000,8.4494301994302,0),
		array(125000,8.601851851851851,0),
		array(120000,8.766975308641975,0),
		array(115000,8.946457326892109,0),
		array(110000,9.142255892255891,0),
		array(105000,9.356701940035272,0),
		array(100000,9.592592592592592,0),
		array(0,14,0),
	);

	private function get_cpm_from_rate_scale(
		&$base_cpm,
		&$retargeting_cpm,
		$amount,
		$amount_type,
		$include_retargeting = k_is_retargeting::yes_retargeting
		)
	{
		$is_success = true;

		$impressions_index = 0;
		$base_cpm_index = 1;
		$retargeting_cpm_index = 2;

		foreach($this->impression_rate_scale as $ii=>$rate_element)
		{
			$rate_base_cpm = $rate_element[$base_cpm_index];
			$rate_retargeting_cpm = $rate_element[$retargeting_cpm_index];
			$rate_impressions = $rate_element[$impressions_index];

			$rate_cpm = $rate_base_cpm;
			if($include_retargeting == k_is_retargeting::yes_retargeting)
			{
				$rate_cpm += $rate_retargeting_cpm;
			}

			switch($amount_type)
			{
			case 'dollar':
				$rate_cost = ($rate_impressions / 1000) * $rate_cpm;
				if($amount >= $rate_cost)
				{
					if($ii == 0)
					{
						$base_cpm = $rate_base_cpm;
						$retargeting_cpm = $rate_retargeting_cpm;
					}
					else
					{
						$previous_index = $ii - 1;
						$previous_rate_element = $this->impression_rate_scale[$previous_index];

						$previous_rate_base_cpm = $previous_rate_element[$base_cpm_index];
						$previous_rate_retargeting_cpm = $previous_rate_element[$retargeting_cpm_index];
						$previous_rate_impressions = $previous_rate_element[$impressions_index];

						$previous_rate_cpm = $previous_rate_base_cpm;
						if($include_retargeting == k_is_retargeting::yes_retargeting)
						{
							$previous_rate_cpm += $previous_rate_retargeting_cpm;
						}

						$next_dollars = $rate_cost;
						$previous_dollars = ($previous_rate_impressions / 1000) * $previous_rate_cpm;
						$scale_delta_dollars = $previous_dollars - $next_dollars;
						$target_delta_dollars = $amount - $next_dollars;
						$scale = $target_delta_dollars / $scale_delta_dollars;

						$base_cpm = $scale * ($previous_rate_base_cpm - $rate_base_cpm) + $rate_base_cpm;
						$retargeting_cpm = $scale * ($previous_rate_retargeting_cpm - $rate_retargeting_cpm) + $rate_retargeting_cpm;
					}

					$is_success = true;
					return $is_success;
				}
				break;
			case 'impression':
				if($amount >= $rate_impressions)
				{
					if($ii == 0)
					{
						$base_cpm = $rate_base_cpm;
						$retargeting_cpm = $rate_retargeting_cpm;
					}
					else
					{
						$previous_index = $ii - 1;
						$previous_rate_element = $this->impression_rate_scale[$previous_index];

						$previous_rate_base_cpm = $previous_rate_element[$base_cpm_index];
						$previous_rate_retargeting_cpm = $previous_rate_element[$retargeting_cpm_index];
						$previous_rate_impressions = $previous_rate_element[$impressions_index];

						$previous_rate_cpm = $previous_rate_base_cpm;
						if($include_retargeting == k_is_retargeting::yes_retargeting)
						{
							$previous_rate_cpm += $previous_rate_retargeting_cpm;
						}

						$next_impressions = $rate_impressions;
						$scale_delta_impressions = $previous_rate_impressions - $next_impressions;
						$target_delta_impressions = $amount - $next_impressions;
						$scale = $target_delta_impressions / $scale_delta_impressions;

						$base_cpm = $scale * ($previous_rate_base_cpm - $rate_base_cpm) + $rate_base_cpm;
						$retargeting_cpm = $scale * ($previous_rate_retargeting_cpm - $rate_retargeting_cpm) + $rate_retargeting_cpm;
					}

					$is_success = true;
					return $is_success;
				}
				break;
			default:
				$base_cpm = $rate_base_cpm;
				$retargeting_cpm = $rate_retargeting_cpm;

				$is_success = false;
				$error_message = 'Unknown amount type: '.$amount_type.' (#0987543)';
				die($error_message);
				return $error_message;
			}
		}
	}

	private $map_term_to_duration = array(
		'monthly' => 'month',
		'weekly' => 'week',
		'daily' => 'day'
		);

	public function get_ad_plan_data_from_impressions(
		&$placeholder_base_cpm,
		&$placeholder_retargeting_cpm,
		&$placeholder_discount,
		&$summary_string,
		&$base_dollars_per_term,
		&$charged_dollars_per_term,
		&$retargeting_price_per_term,

		$impressions,
		$term,
		$duration,
		$base_cpm,
		$retargeting_cpm,
		$discount_param,
		$is_retargeting
		)
	{
		$discount = is_null($discount_param) ? 0 : $discount_param;

		$terms_per_month = 1;
		$impressions_per_month = $impressions;
		switch($term)
		{
		case 'monthly':
			$impressions_per_month = $impressions;
			$terms_per_month = 1;
			break;
		case 'weekly':
			$weeks_per_month = 52/12;
			$capped_weeks = min($duration, $weeks_per_month);
			$terms_per_month = $capped_weeks;
			$impressions_per_month = $impressions * $capped_weeks;
			break;
		case 'daily':
			$days_per_month = 365/12;
			$capped_days = min($duration, $days_per_month);
			$terms_per_month = $capped_days;
			$impressions_per_month = $impressions * $capped_days;
			break;
		default:
			die('Unhandled duration term: '.$term.' (#897394)');
		}

		$rate_scale_base_cpm;
		$rate_scale_retargeting_cpm;
		$this->get_cpm_from_rate_scale($rate_scale_base_cpm, $rate_scale_retargeting_cpm, $impressions_per_month, 'impression', $is_retargeting);

		$rate_scale_total_cpm = 0;
		if($is_retargeting == true)
		{
			$rate_scale_total_cpm = $rate_scale_base_cpm + $rate_scale_retargeting_cpm;
		}
		else
		{
			$rate_scale_total_cpm = $rate_scale_base_cpm;
		}

		$applied_cpm = $rate_scale_total_cpm;
		$applied_retargeting_cpm = $rate_scale_retargeting_cpm;
		if(!is_null($base_cpm) && $base_cpm != 0)
		{
			if($is_retargeting == true)
			{
				$applied_cpm = $base_cpm + $retargeting_cpm;
			}
			else
			{
				$applied_cpm = $base_cpm;
			}

			$applied_retargeting_cpm = $retargeting_cpm;
		}

		$discount_scalar = (integer) $discount >= 100 ? 0 : (1 - $discount / 100);
		$discounted_cpm = $applied_cpm * $discount_scalar;
		$discounted_retargeting_cpm = $applied_retargeting_cpm * $discount_scalar;

		$dollars_per_month = ($impressions_per_month / 1000) * $discounted_cpm;
		$dollars_per_term = $dollars_per_month / $terms_per_month;

		$retargeting_price_per_term = (($impressions_per_month / 1000) * $discounted_retargeting_cpm) / $terms_per_month;

		$base_dollars_per_term = $discount_scalar == 0 ? 0 : $dollars_per_term / $discount_scalar;
		$charged_dollars_per_term = $dollars_per_term;

		$placeholder_base_cpm = $rate_scale_base_cpm;
		$placeholder_retargeting_cpm = $rate_scale_retargeting_cpm;
		$placeholder_discount = 0;

		$singular_term = $this->map_term_to_duration[$term];

		$formatted_cpm = number_format($discounted_cpm, 2, '.', '');
		$formatted_dollars = number_format($dollars_per_term, 0);
		$summary_string = '[$'.$formatted_dollars.'/'.$singular_term.' @ $'.$formatted_cpm.' eCPM]';
	}

	public function get_ad_plan_data_from_dollars(
		&$placeholder_base_cpm,
		&$placeholder_retargeting_cpm,
		&$placeholder_discount,
		&$summary_string,
		&$impressions_per_term,
		&$base_dollars_per_term,
		&$charged_dollars_per_term,
		&$retargeting_price_per_term,

		$dollars,
		$term,
		$duration,
		$base_cpm,
		$retargeting_cpm,
		$discount_param,
		$is_retargeting
		)
	{
		$cost_per_term = $dollars;
		$discount = is_null($discount_param) ? 0 : $discount_param;

		$base_dollars_per_term = intval($discount, 10) >= 100 ? 0 : $cost_per_term / (1 - $discount / 100);
		$charged_dollars_per_term = $cost_per_term;

		$terms_per_month = 1;
		$duration_cost = $cost_per_term;
		switch($term)
		{
		case 'monthly':
			$duration_cost = $cost_per_term;
			$terms_per_month = 1;
			break;
		case 'weekly':
			$weeks_per_month = 52/12;
			$capped_weeks = min($duration, $weeks_per_month);
			$terms_per_month = $capped_weeks;
			$duration_cost = $cost_per_term * $capped_weeks;
			break;
		case 'daily':
			$days_per_month = 365/12;
			$capped_days = min($duration, $days_per_month);
			$terms_per_month = $capped_days;
			$duration_cost = $cost_per_term * $capped_days;
			break;
		default:
			die('Unhandled duration term: '.$term.' (#897393)');
			$duration_cost = $cost_per_term;
			$terms_per_month = 1;
		}

		$discounted_duration_cost = intval($discount, 10) >= 100 ? 0 : $duration_cost / (1 - $discount / 100);

		$discounted_base_cpm_with_retargeting;
		$discounted_retargeting_cpm_with_retargeting;
		$this->get_cpm_from_rate_scale($discounted_base_cpm_with_retargeting, $discounted_retargeting_cpm_with_retargeting, $discounted_duration_cost, 'dollar', k_is_retargeting::yes_retargeting);
		$cpm_with_retargeting = $discounted_base_cpm_with_retargeting + $discounted_retargeting_cpm_with_retargeting;

		$discounted_base_cpm_without_retargeting;
		$discounted_retargeting_cpm_without_retargeting;
		$this->get_cpm_from_rate_scale($discounted_base_cpm_without_retargeting, $discounted_retargeting_cpm_without_retargeting, $discounted_duration_cost, 'dollar', k_is_retargeting::no_retargeting);
		$cpm_without_retargeting = $discounted_base_cpm_without_retargeting;

		$discounted_total_cpm = 0;
		if($is_retargeting == true)
		{
			$discounted_total_cpm = $cpm_with_retargeting;
		}
		else
		{
			$discounted_total_cpm = $cpm_without_retargeting;
		}

		$applied_cpm = $discounted_total_cpm;
		$applied_cpm_with_retargeting = $cpm_with_retargeting;
		$applied_cpm_without_retargeting = $cpm_without_retargeting;
		if(!is_null($base_cpm) && $base_cpm != 0)
		{
			$applied_cpm_with_retargeting = $base_cpm + $retargeting_cpm;
			$applied_cpm_without_retargeting = $base_cpm;
		}

		$impressions_per_month_with_retargeting = $discounted_duration_cost * 1000 / $applied_cpm_with_retargeting;
		$impressions_per_term_with_retargeting = $impressions_per_month_with_retargeting / $terms_per_month;

		$impressions_per_month_without_retargeting = $discounted_duration_cost * 1000 / $applied_cpm_without_retargeting;
		$impressions_per_term_without_retargeting = $impressions_per_month_without_retargeting / $terms_per_month;

		$retargeting_impressions_per_term = $impressions_per_term_without_retargeting - $impressions_per_term_with_retargeting;
		$retargeting_price_per_term = $impressions_per_term_without_retargeting == 0 ? 0 : $charged_dollars_per_term * $retargeting_impressions_per_term / $impressions_per_term_without_retargeting;

		if($is_retargeting == true)
		{
			$applied_cpm = $applied_cpm_with_retargeting;
			$impressions_per_term = $impressions_per_term_with_retargeting;
			$placeholder_base_cpm = $discounted_base_cpm_with_retargeting;
			$placeholder_retargeting_cpm = $discounted_retargeting_cpm_with_retargeting;
		}
		else
		{
			$applied_cpm = $applied_cpm_without_retargeting;
			$impressions_per_term = $impressions_per_term_without_retargeting;
			$placeholder_base_cpm = $discounted_base_cpm_without_retargeting;
			$placeholder_retargeting_cpm = $discounted_retargeting_cpm_without_retargeting;
		}
		$placeholder_discount = 0;

		$effective_cpm = intval($discount, 10) >= 100 ? 0 : $applied_cpm * (1 - $discount / 100);

		$singular_term = $this->map_term_to_duration[$term];

		$formatted_cpm = number_format($effective_cpm, 2, '.', '');
		$formatted_impressions = number_format($impressions_per_term / 1000, 0);
		$summary_string = '['.$formatted_impressions.'k imprs/'.$singular_term.' @ $'.$formatted_cpm.' eCPM]';
	}

    public function get_pricing_by_zones_and_packs_for_tv($zones, $packs){

    	if (empty($packs))
    	{
    		return array();
    	}

        $zones_sub_query = "";
        $packs_sub_query = "";

        foreach($zones as $i => $zone_id)
        {
            $zones_sub_query .= $zone_id;
            if ($i !== count($zones)-1)
            {
                $zones_sub_query .= ', ';
            }
        }

        foreach($packs as $i => $pack_id)
        {
        	if (strlen($packs_sub_query) > 0)
            {
                $packs_sub_query .= ' OR ';
            }
            $packs_sub_query .= "tzp.pack_name like '%". $pack_id ."%'";
        }

        $query =    "SELECT SUM(tzp.monthly_price) AS price,
                        tzp.pack_name AS pack_name
                        FROM tv_zone_packs AS tzp
                        WHERE
                        tzp.regions_collection_id IN (". $zones_sub_query .")
                        AND (". $packs_sub_query .")
                        GROUP BY tzp.pack_name ORDER BY price;";

        $response = $this->db->query($query);
        if($response->num_rows() > 0)
        {
            return $response->result_array();
        }
        return array();

    }

    public function get_zones_for_partner_by_mpq_and_zone_id($q, $start, $limit, $mpq_session_id, $zone_id){

        $bindings = array($mpq_session_id, $zone_id, $q, $start, $limit);

        $query = "
			SELECT
				grc.id as region_id,
				concat(gm.market_name, \" - \", REPLACE(grc.name, 'Zone: ', '')) as region_name
            FROM
				mpq_sessions_and_submissions mss
				JOIN users us
					ON mss.owner_user_id = us.id
				JOIN geo_regions_collections_partner grp
					ON us.partner_id = grp.partner_id
				JOIN geo_regions_collection grc
					ON grp.geo_regions_collection_id = grc.id
				JOIN geo_markets_regions gmr
                    ON grp.geo_regions_collection_id = gmr.geo_regions_collection_partner_id
				JOIN geo_markets gm
					ON gm.id = gmr.geo_markets_id
			WHERE
                mss.id = ? AND
				grp.regions_collection_type = ? AND
				grc.name LIKE ?
			ORDER BY
				region_name ASC
			LIMIT ?, ?";

        $response = $this->db->query($query, $bindings);
        if($response and $response->num_rows() > 0)
        {
            return $response->result_array();
        }
        return false;
    }

	public function get_regions_and_demographics_from_mpq(&$regions, &$demographics, $mpq_id)
	{
		$sql =
		"	SELECT
				`region_data`,
				`demographic_data`
			FROM
				`mpq_sessions_and_submissions`
			WHERE `id` = ?;
		";
		$bindings = array($mpq_id);
		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$row = $response->row();
			if($row->region_data != null)
			{
				$regions = json_decode($row->region_data, true);
				$this->map->convert_old_flexigrid_format($regions);
			}
			else
			{
				$regions = array();
			}

			if($row->demographic_data != null)
			{
				$demographics_string = explode("||", $row->demographic_data);

				$demographics = explode('_', $demographics_string[0]);
			}
			else
			{
				$demographics = array();
			}
		}
		else
		{
			$regions = array();
			$demographics = array();
		}
	}

	private $map_mpq_term_to_ad_plan_period_type = array(
		'monthly' => 'months',
		'weekly' => 'weeks',
		'daily' => 'days'
		);

 	// create records in `prop_gen_*` tables from `mpq_*` tables
	public function create_proposal_from_mpq(&$prop_id, $mpq_id, $user_id)
	{
		$create_proposal_response = array('is_success' => true, 'mpq_type' => '', 'errors' => '');
		$k_rf_geo_coverage = 0.87;
		$k_rf_gamma = 0.30;
		$k_rf_ip_accuracy = 0.99;
		$k_rf_demo_coverage = 0.87;

		$prop_id = 0;
		// create prop_gen_sessions record
		//	create lap_id by creating a record in media_plan_sessions to reserve the lap_id
		//	to create the media_plan_sessions record need a fake session_id

		$regions = null;
		$demographics = null;
		$this->get_regions_and_demographics_from_mpq($regions, $demographics, $mpq_id);
		$region_population = null;
		$regions_string=json_encode($regions);
		if (is_array($regions)  && (substr($regions_string, 0, 1) === "["))
		{
			$region_population = $regions[0];
		 	$regions=json_encode($regions[0]);
		}
		else
		{
			$region_population = $regions;
			$regions=json_encode($regions);
		}

		$population = 0;
		$demo_population = 0;
		$internet_average = 0.5;
		$this->map->calculate_population_based_on_selected_demographics(
			$population,
			$demo_population,
			$internet_average,
			$region_population,
			$demographics
		);

		$rep_id = NULL;
		$mpq_type = "proposal";
		$insertion_or_option_sql =
		"	SELECT
				IF(mio.id IS NULL, 0, 1) as is_insertion_order,
				IF(moap.id IS NULL, 0, 1) as is_proposal,
				mss.creator_user_id AS rep_id,
				(usr.role = 'SALES') AS is_sales
			FROM mpq_sessions_and_submissions mss
				LEFT JOIN mpq_options_ad_plans moap
					ON mss.id = moap.mpq_id
				LEFT JOIN mpq_insertion_orders mio
					ON mss.id = mio.mpq_id
				LEFT JOIN users AS usr
					ON usr.id = mss.creator_user_id
			WHERE mss.id = ?
		";
		$insertion_or_option_query = $this->db->query($insertion_or_option_sql, $mpq_id);
		if($insertion_or_option_query->num_rows() > 0)
		{
			$temp_io_array = $insertion_or_option_query->row_array();
			if($temp_io_array['is_insertion_order'] == 1 AND $temp_io_array['is_proposal'] == 0)
			{
				$mpq_type = "insertion order";
			}
			else if($temp_io_array['is_insertion_order'] == 0 AND $temp_io_array['is_proposal'] == 1)
			{
				$mpq_type = "proposal";
			}
			else
			{
				$mpq_type = "unknown";
			}

			$rep_id = $temp_io_array['is_sales'] ? $temp_io_array['rep_id'] : null;
		}

		$fake_session_id = md5(uniqid(mt_rand().'', true));
		$create_media_plan_session_sql = '
			INSERT INTO media_plan_sessions
			(
				session_id,
				notes,
				region_data,
				demographic_data,
				selected_iab_categories,

				population,
				demo_population,
				internet_average,
				rf_geo_coverage,
				rf_gamma,

				rf_ip_accuracy,
				rf_demo_coverage
			)
			SELECT
				?,
				?,
				?,
				mpq.demographic_data,
				mpq.selected_iab_categories,

				?,
				?,
				?,
				?,
				?,

				?,
				?
			FROM mpq_sessions_and_submissions AS mpq
			WHERE
				mpq.id = ?
		';
		$create_media_plan_session_bindings = array(
			$fake_session_id,
			'place holder session for lap_id creation by mpq #'.$mpq_id,
			$regions,
			// region_data,
			// mpq.demographic_data,
			// mpq.selected_iab_categories,


			$population,
			$demo_population,
			$internet_average,
			$k_rf_geo_coverage,
			$k_rf_gamma,

			$k_rf_ip_accuracy,
			$k_rf_demo_coverage,
			$mpq_id
			);
		$response = $this->db->query($create_media_plan_session_sql, $create_media_plan_session_bindings);
		$lap_id = $this->db->insert_id();

		//	populate data from MPQ and populate default data for remaining fields
		$create_prop_gen_session_sql = '
			INSERT INTO prop_gen_sessions
				(
				region_data,
				demographic_data,
				selected_iab_categories,
				lap_id,
				plan_name,

				advertiser,
				notes,
				recommended_impressions,
				price_notes,
				population,

				demo_population,
				site_array,
				has_retargeting,
				rf_geo_coverage,
				rf_gamma,

				rf_ip_accuracy,
				rf_demo_coverage,
				internet_average,
				owner_id,
				date_created,

				source_mpq
				)
			SELECT
				?,
				mpq.demographic_data,
				mpq.selected_iab_categories,
				?,
				?,

				mpq.advertiser_name,
				?,
				?,
				?,
				?,

				?,
				?,
				?,
				?,
				?,

				?,
				?,
				?,
				?,
				?,

				?
			FROM mpq_sessions_and_submissions AS mpq
			WHERE
				mpq.id = ?
		';

		$create_prop_gen_session_bindings = array(
			$regions,
			// mpq.region_data,
			// mpq.demographic_data,
			// mpq.selected_iab_categories,
			$lap_id,
			"lap from mpq ".$mpq_id,

			// mpq.advertiser_name,
			"notes",
			0,
			"price notes",
			$population,

			$demo_population,
			"
				[{\"Domain\":\"youtube.com\",
				\"Reach\":\"0.49140\",
				\"Targeting_Efficacy\":\"1.00000\",
				\"Demo_Coverage\":\"0.66658\",
				\"score\":\"1.74183\"}]
			",
			1,
			$k_rf_geo_coverage,
			$k_rf_gamma,

			$k_rf_ip_accuracy,
			$k_rf_demo_coverage,
			$internet_average,
			$user_id,
			date("Y-m-d"),

			$mpq_id,
			$mpq_id
			);
		$response = $this->db->query($create_prop_gen_session_sql, $create_prop_gen_session_bindings);

		//Copying media_targeting_tags
		$this->copy_media_targeting_tags_from_mpq_to_proposal($mpq_id, $lap_id);

		// create prop_gen_prop_data record
		$create_prop_gen_prop_data_sql = '
			INSERT INTO prop_gen_prop_data
				(prop_name, source_mpq, rep_id)
			VALUE (?, ?, ?)
		';

		$create_prop_gen_prop_data_bindings = array(
			'create from mpq '.$mpq_id,
			$mpq_id,
			$rep_id
		);

		$response = $this->db->query($create_prop_gen_prop_data_sql, $create_prop_gen_prop_data_bindings);
		$prop_id = $this->db->insert_id();

		if($mpq_type == 'proposal')
		{
			// create prop_gen_option_prop_join, prop_gen_service_option_join, prop_gen_adplan_option_join records
			$get_mpq_options_ad_plans_sql = '
			SELECT *
			FROM mpq_options_ad_plans
			WHERE
				mpq_id = ?
		';
			$get_mpq_options_ad_plans_bindings = array(
				$mpq_id
				);
			$mpq_options_response = $this->db->query($get_mpq_options_ad_plans_sql, $get_mpq_options_ad_plans_bindings );
			if($mpq_options_response !== false && $mpq_options_response->num_rows() > 0)
			{
				$mpq_options = $mpq_options_response->result_array();
				foreach($mpq_options as $ii=>$option)
				{
					$option_id = $ii;

					$dollar_budget = 0;
					$impressions_amount = 0;

					$placeholder_base_cpm = 0;
					$placeholder_retargeting_cpm = 0;
					$placeholder_discount = 0;
					$summary_string = '';
					$base_dollars_per_term = 0;
					$charged_dollars_per_term = 0;

					$retargeting_dollars_per_term = 0;

					$impression_cpm = null;
					$retargeting_cpm = null;
					$this->split_mpq_cpm_for_option_engine(
						$impression_cpm,
						$retargeting_cpm,
						$option['cpm']
						);

					$is_retargeting = true;
					switch($option['type'])
					{
					case 'dollar':
						$dollar_budget = $option['amount'];
						$this->get_ad_plan_data_from_dollars(
							$placeholder_base_cpm,
							$placeholder_retargeting_cpm,
							$placeholder_discount,
							$summary_string,
							$impressions_amount,
							$base_dollars_per_term,
							$charged_dollars_per_term,
							$retargeting_dollars_per_term,
							$option['amount'],
							$option['term'],
							$option['duration'],
							$impression_cpm,
							$retargeting_cpm,
							$option['discount'],
							$is_retargeting
							);

						break;
					case 'impression':
						$impressions_amount = $option['amount'];
						$this->get_ad_plan_data_from_impressions(
							$placeholder_base_cpm,
							$placeholder_retargeting_cpm,
							$placeholder_discount,
							$summary_string,
							$base_dollars_per_term,
							$charged_dollars_per_term,
							$retargeting_dollars_per_term,
							$option['amount'],
							$option['term'],
							$option['duration'],
							$impression_cpm,
							$retargeting_cpm,
							$option['discount'],
							$is_retargeting
							);
						$dollar_budget = $charged_dollars_per_term;
						break;
					default:
						die('unknown option type: '.$option['type'].' (#8947374)');
						$dollar_budget = $option['amount'];
						$this->get_ad_plan_data_from_dollars(
							$placeholder_base_cpm,
							$placeholder_retargeting_cpm,
							$placeholder_discount,
							$summary_string,
							$impressions_amount,
							$base_dollars_per_term,
							$charged_dollars_per_term,
							$retargeting_dollars_per_term,
							$option['amount'],
							$option['term'],
							$option['duration'],
							$impression_cpm,
							$retargeting_cpm,
							$option['discount'],
							$is_retargeting
							);
					}

					$create_ad_plan_sql = '
					INSERT prop_gen_adplan_option_join
					(
						lap_id,
						prop_id,
						option_id,
						budget,
						impressions,

						retargeting,
						retargeting_price,
						term,
						period_type,
						geo_coverage,

						gamma,
						ip_accuracy,
						demo_coverage,
						custom_impression_cpm,
						custom_retargeting_cpm
					)
					VALUE (
						?, ?, ?, ?, ?,
						?, ?, ?, ?, ?,
						?, ?, ?, ?, ?
					)
				';

					$period_type = $this->map_mpq_term_to_ad_plan_period_type[$option['term']];
					$create_ad_plan_bindings = array(
						$lap_id,
						$prop_id,
						$option_id,
						$dollar_budget,
						$impressions_amount,

						1,
						$retargeting_dollars_per_term,
						$option['duration'],
						$period_type,
						0.87,

						0.10,
						0.99,
						0.87,
						$impression_cpm,
						$retargeting_cpm
						);
					$response = $this->db->query($create_ad_plan_sql, $create_ad_plan_bindings);

					$create_option_sql = '
					INSERT prop_gen_option_prop_join
					(
						option_id,
						prop_id,
						option_name,
						one_time_cost_raw,
						one_time_abs_discount,

						one_time_percent_discount,
						one_time_discount_description,
						one_time_cost,
						monthly_cost_raw,
						monthly_abs_discount,

						monthly_percent_discount,
						monthly_discount_description,
						monthly_cost,
						cost_by_campaign
					)
					VALUE (
						?,?,?,?,?,
						?,?,?,?,?,
						?,?,?,?
					)
				';

					$option_number = $ii + 1;
					$create_option_bindings = array(
						$option_id,
						$prop_id,
						'option '.$option_number.' from mpq '.$mpq_id,
						0, // not used as of (2013-07-05)
						0, // not used as of (2013-07-05)

						0, // not used as of (2013-07-05)
						'', // not used as of (2013-07-05)
						0, // not used as of (2013-07-05)
						$base_dollars_per_term,
						0, // not used as of (2013-07-05)

						$option['discount'],
						'',
						$charged_dollars_per_term,
						0 // maybe 1 for insertion order with total term, 0 otherwise.
						);
					$response = $this->db->query($create_option_sql, $create_option_bindings);

					$creative_service_type = 3;
					$create_service_sql = '
					INSERT INTO prop_gen_service_option_join
						(service_id, prop_id, option_id)
					VALUE
					(
						?,?,?
					)';
					$create_service_bindings = array(
						$creative_service_type,
						$prop_id,
						$option_id
						);
					$response = $this->db->query($create_service_sql, $create_service_bindings);
				}
				// TODO: error check $response values
			}
			//snapshot query.  Take snapshot data from the mpq session and submission table.  Snapshot should exist from mpq creation.
			$snapshot_sql =
			"	INSERT INTO prop_gen_snapshot_data
					(lap_id, prop_id, snapshot_num, snapshot_title, snapshot_data)
				SELECT
					?, ?, 1, snapshot_title, snapshot_data
				FROM
					mpq_sessions_and_submissions
				WHERE id = ?;
			";
			$snapshot_bindings = array($lap_id, $prop_id, $mpq_id);
			$snapshot_response = $this->db->query($snapshot_sql, $snapshot_bindings);
			if($this->db->affected_rows() > 0)
			{
				//success
			}
		}
		else if($mpq_type = "insertion order")
		{

		}
		else
		{
			$create_proposal_response['is_success'] = false;
			$create_proposal_response['errors'] = 'Unknown mpq type (#8745445)';
		}
		$create_proposal_response['mpq_type'] = $mpq_type;
		return $create_proposal_response;
	}

	private $map_option_engine_period_type_to_mpq_term = array(
		'months' => 'monthly',
		'weeks' => 'weekly',
		'days' => 'daily',
	);

	// pulls data from the many places pushed to by the option engine and outputs it in a format that can
	// be read by the option engine (JSON) and then used to prepopulate the remaining fields in the option
	// engine format.
	//returns the lap and prop id to be displayed in the option engine upon completion.
	public function get_load_proposal_data($prop_id)
	{
		// taken from proposal_gen_model.php::function loadProposal()

		// we are going to need to undo our nesting in reverse in order to get the proper write-out
		$prop_data = array();
		$sql =
		"	SELECT
				pgpd.prop_name AS prop_name,
				pgpd.show_pricing AS show_pricing,
				pgpd.rep_id AS rep_id,
				usr.firstname AS first_name,
				usr.lastname AS last_name,
				wlpd.partner_name AS partner_name
			FROM
				prop_gen_prop_data AS pgpd
				LEFT JOIN users AS usr
					ON usr.id = pgpd.rep_id
				LEFT JOIN wl_partner_details AS wlpd
					ON usr.partner_id = wlpd.id
			WHERE
				prop_id = ?
		";
		$query = $this->db->query($sql, $prop_id);
		$prop_data = $query->row_array();

		$prop_data['options'] = array();

		$the_query =
		"	SELECT *
			FROM prop_gen_option_prop_join
			WHERE prop_id = ?
			ORDER BY option_id ASC
		";
		$bindings = array($prop_id);
		$response = $this->db->query($the_query, $prop_id);
		$result_array = $response->result_array();

		foreach($result_array as $row)
		{
			// this should return a record of each $row in the result.
			// so our first order of business should be to figure out what row we are on so that we can use that as our index within the option.

			$option_counter = (int)$row['option_id'];

			$curr_option = array();
			$curr_option['option_name'] = $row['option_name'];
			$curr_option['one_time_percent_discount'] = $row['one_time_percent_discount'];
			$curr_option['one_time_abs_discount'] = $row['one_time_abs_discount'];
			$curr_option['monthly_percent_discount'] = $row['monthly_percent_discount'];
			$curr_option['monthly_abs_discount'] = $row['monthly_abs_discount'];
			$curr_option['one_time_discount_description'] = $row['one_time_discount_description'];
			$curr_option['monthly_disocunt_description'] = $row['monthly_discount_description'];
			$curr_option['monthly_raw_cost'] = $row['monthly_cost_raw'];
			$curr_option['monthly_total_cost'] = $row['monthly_cost'];
			$curr_option['cost_by_campaign'] = $row['cost_by_campaign'];

			$inner_query =
			"	SELECT *
				FROM prop_gen_services_data AS pgsd,
				prop_gen_service_option_join AS pgsoj
				WHERE pgsd.service_id = pgsoj.service_id
				AND pgsoj.option_id = ?
				AND pgsoj.prop_id = ?
			";
			$inner_bindings = array($option_counter, $prop_id);
			$inner_response = $this->db->query($inner_query, $inner_bindings);
			$inner_result_array = $inner_response->result_array();

			foreach($inner_result_array as $service)
			{
				$curr_option[$service['service_name']] = "1";
			}

			$curr_option['laps'] = array();
			$inner_query =
			"	SELECT
					pgao.*,
					pgs.plan_name AS plan_name,
					pgs.advertiser AS advertiser,
					pgs.population AS population,
					pgs.demo_population AS demo_population
				FROM prop_gen_adplan_option_join AS pgao
				JOIN prop_gen_sessions AS pgs
				ON pgao.lap_id = pgs.lap_id
				WHERE option_id = ?
				AND prop_id = ?
				ORDER BY option_id, lap_id ASC
			";
			$inner_bindings = array($option_counter, $prop_id);
			$inner_response = $this->db->query($inner_query, $inner_bindings);
			$inner_result_array = $inner_response->result_array();

			$adplan_counter = 0;
			foreach($inner_result_array as $inner_row)
			{
				$ad_plan_data = array();
				$ad_plan_data['lap_id'] = $inner_row['lap_id'];
				$ad_plan_data['plan_name'] = $inner_row['plan_name'];
				$ad_plan_data['advertiser'] = $inner_row['advertiser'];
				$ad_plan_data['impressions'] = $inner_row['impressions'];
				$ad_plan_data['term'] = $inner_row['term'];
				$ad_plan_data['retargeting'] = $inner_row['retargeting'];
				$ad_plan_data['retargeting_price'] = $inner_row['retargeting_price'];
				$ad_plan_data['budget'] = $inner_row['budget'];
				$ad_plan_data['period_type'] = $inner_row['period_type'];
				$ad_plan_data['geo_coverage'] = $inner_row['geo_coverage'];
				$ad_plan_data['gamma'] = $inner_row['gamma'];
				$ad_plan_data['ip_accuracy'] = $inner_row['ip_accuracy'];
				$ad_plan_data['demo_coverage'] = $inner_row['demo_coverage'];
				$ad_plan_data['custom_impression_cpm'] = $inner_row['custom_impression_cpm'];
				$ad_plan_data['custom_retargeting_cpm'] = $inner_row['custom_retargeting_cpm'];
				$ad_plan_data['population'] = $inner_row['population'];
				$ad_plan_data['demo_population'] = $inner_row['demo_population'];

				$default_impression_cpm = 0;
				$default_retargeting_cpm = 0;
				if($inner_row['custom_impression_cpm'] == null ||
				   $inner_row['custom_retargeting_cpm'] == null)
				{

					$temp_placeholder_discount;
					$temp_summary_string;
					$temp_impressions_per_term;
					$temp_base_dollars_per_term;
					$temp_charged_dollars_per_term;
					$temp_retargeting_price_per_term;

					$mpq_term = $this->map_option_engine_period_type_to_mpq_term[$inner_row['period_type']];
					$mpq_duration = $inner_row['term'];
					$this->get_ad_plan_data_from_dollars(
						$default_impression_cpm,
						$default_retargeting_cpm,
						$temp_placeholder_discount,
						$temp_summary_string,
						$temp_impressions_per_term,
						$temp_base_dollars_per_term,
						$temp_charged_dollars_per_term,
						$temp_retargeting_price_per_term,

						$inner_row['budget'],
						$mpq_term,
						$mpq_duration,
						$inner_row['custom_impression_cpm'],
						$inner_row['custom_retargeting_cpm'],
						$row['monthly_percent_discount'],
						$inner_row['retargeting']
					);

				}
				$ad_plan_data['default_impression_cpm'] = $default_impression_cpm;
				$ad_plan_data['default_retargeting_cpm'] = $default_retargeting_cpm;

				$curr_option['laps'][$adplan_counter] = $ad_plan_data;
				$adplan_counter++;
			}

			$prop_data['options'][$option_counter] = $curr_option;
		}

		return $prop_data;
	}

	function get_lap_id_by_source_mpq($mpq_id)
	{
		$sql = 'SELECT lap_id FROM prop_gen_sessions WHERE source_mpq = ? LIMIT 1';
		$query = $this->db->query($sql, $mpq_id);
		if($query->num_rows() > 0)
		{
			$temp = $query->row_array();
			return $temp['lap_id'];
		}
		else
		{
			return false;
		}
	}
	function get_data_for_submittal_notes_view($mpq_id)
	{
		$sql = "SELECT mss.*, IF(mio.id IS NULL, 0, 1) as is_insertion_order, IF(moap.id IS NULL, 0, 1) as is_proposal FROM mpq_sessions_and_submissions mss LEFT JOIN mpq_options_ad_plans moap ON mss.id = moap.mpq_id LEFT JOIN mpq_insertion_orders mio ON mss.id = mio.mpq_id WHERE mss.id = ? LIMIT 1";
		$query = $this->db->query($sql, $mpq_id);
		if($query->num_rows() > 0)
		{
			$mss_data = $query->row_array();
			$rgn_data = $this->get_custom_regions_by_mpq_id($mpq_id);

			if($mss_data['is_insertion_order'] == 1 AND $mss_data['is_proposal'] == 0)
			{
				$sql = "SELECT * FROM mpq_insertion_orders WHERE mpq_id = ? LIMIT 1";
			}
			else if ($mss_data['is_insertion_order'] == 0 AND $mss_data['is_proposal'] == 1)
			{
				$sql = "SELECT * FROM mpq_options_ad_plans WHERE mpq_id = ?";
			}
			else
			{
				return false;
			}
			$query = $this->db->query($sql, $mpq_id);
			if($query->row_array() > 0)
			{
				$mpq_data = $query->result();
				return array('mss_data' => $mss_data, 'mpq_data' => $mpq_data, 'rgn_data' => $rgn_data);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	function write_mpq_image($mpq_id, $img_link, $img_title)
	{
		$sql = "UPDATE mpq_sessions_and_submissions SET snapshot_title = ?, snapshot_data = ? WHERE id = ?";
		$query = $this->db->query($sql, array($img_title, $img_link, $mpq_id));
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function get_creative_requests_by_search_term($term, $start, $limit, $role, $partner_users)
	{
		if ($role == 'admin' || $role == 'ops')
		{
			$sql = "SELECT DISTINCT
					ar.id as id,
					ar.advertiser_name as advertiser_name,
					ar.advertiser_name as text,
					ar.advertiser_email as advertiser_email,
					DATE_FORMAT(ar.updated, '%b. %D') as updated,
					COALESCE(CONCAT(u.firstname, \" \", u.lastname), u.email) as username,
					pd.partner_name as partner_name
					FROM
						wl_partner_details pd
						JOIN users u
							ON pd.id = u.partner_id
						JOIN adset_requests ar
							ON u.id = ar.requester_id
					WHERE
						ar.advertiser_name LIKE ?
						OR ar.advertiser_email LIKE ?
						OR u.firstname LIKE ?
						OR u.lastname LIKE ?
						OR pd.partner_name LIKE ?
					ORDER BY ar.id DESC
					LIMIT ?, ?";
			$binds = array($term, $term, $term, $term, $term, $start, $limit);
		}
		else if (!empty($partner_users))
		{
			$partner_query_string = "";
			$binds = array($term, $term, $term, $term, $term);

			foreach ($partner_users as $user)
			{
				if ($partner_query_string != "")
				{
					$partner_query_string .= ", ";
				}
				$partner_query_string .= "?";
				$binds[] = $user['id'];
			}

			$sql = "SELECT DISTINCT
					ar.id as id,
					ar.advertiser_name as advertiser_name,
					ar.advertiser_name as text,
					ar.advertiser_email as advertiser_email,
					DATE_FORMAT(ar.updated, '%b. %D') as updated,
					COALESCE(CONCAT(u.firstname, \" \", u.lastname), u.email) as username,
					pd.partner_name as partner_name
					FROM
						wl_partner_details pd
						JOIN users u
							ON pd.id = u.partner_id
						JOIN adset_requests ar
							ON u.id = ar.requester_id
					WHERE (
							ar.advertiser_name LIKE ?
							OR ar.advertiser_email LIKE ?
							OR u.firstname LIKE ?
							OR u.lastname LIKE ?
							OR pd.partner_name LIKE ?
						)
					AND CAST(u.id as CHAR) IN (".$partner_query_string.")
					ORDER BY ar.id DESC
					LIMIT ?, ?";

			$binds[] = $start;
			$binds[] = $limit;
		}

		$query = $this->db->query($sql, $binds);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}
	public function get_single_creative_request($id)
	{
		$sql = "SELECT *
				FROM adset_requests
				WHERE CAST(id as CHAR) = ?";
		$query = $this->db->query($sql, array($id));
		if($query->num_rows() > 0)
		{
			$result = $query->row();
			$result->creative_files = json_decode($result->creative_files);
			$result->scenes = json_decode($result->scenes);

			if (!( empty($result->features_video_youtube_url) && empty($result->features_video_video_play) && empty($result->features_video_mobile_clickthrough_to) ))
			{
				$result->is_video = true;
			}

			if (!empty($result->features_map_locations))
			{
				$result->is_map = true;
			}

			if (!( empty($result->features_social_twitter_text) && empty($result->features_social_email_subject) && empty($result->features_social_email_message) && empty($result->features_social_linkedin_subject) && empty($result->features_social_linkedin_message) ))
			{
				$result->is_social = true;
			}

			return $result;
		}
		else
		{
			return false;
		}
	}

	function get_custom_regions_by_partner_and_search_term($term, $start, $limit, $partner_id)
	{
		if($partner_id == '%')
		{
			$partner_like_equal = 'LIKE';
		}
		else if($partner_id == NULL)
		{
			$partner_like_equal = 'LIKE';
		}
		else
		{
			$partner_like_equal = '=';
		}
		/*
		$bindings = array($partner_id, $term, $start, $limit);
		$sql1 =
		"	SELECT
				g.id AS id,
				p.partner_id AS partner_id,
				g.name AS region_name,
				g.json_regions AS regions
			FROM geo_regions_collection AS g
			LEFT OUTER JOIN
				geo_regions_collections_partner AS p
				ON g.id = p.geo_regions_collection_id
			WHERE
				((p.partner_id IS NULL) OR (p.partner_id {$partner_like_equal} ?)) AND
				g.name LIKE ?
			GROUP BY id
			ORDER BY g.name
			LIMIT ?, ?;
		";


		*/

		$bindings = array($partner_id, $term, $term, $start, $limit);

		$sql = "
			SELECT  main.id,
					main.partner_id,
					main.region_name,
					main.regions
			FROM
			(
					SELECT
						g.id AS id,
						p.partner_id AS partner_id,
						g.name AS region_name,
						g.json_regions AS regions
					FROM
						geo_regions_collection AS g
					JOIN
						geo_regions_collections_partner AS p
						ON g.id = p.geo_regions_collection_id
					WHERE
					 	(p.partner_id {$partner_like_equal} ?) AND
						g.name LIKE ?
				UNION
					SELECT
						g.id AS id,
						null AS partner_id,
						g.name AS region_name,
						g.json_regions AS regions
					FROM
						geo_regions_collection AS g
					WHERE
						g.id NOT IN
						(
							SELECT
								geo_regions_collection_id
							FROM
								geo_regions_collections_partner
						)
						AND
							g.name LIKE ?
			) AS main
				GROUP BY main.id
				ORDER BY main.region_name
			LIMIT ?, ?;
			";

		$query = $this->db->query($sql, $bindings);

		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	function get_zips_from_selected_regions_by_id_array($region_array)
	{
		$in_array = array_fill(0, count($region_array), '?');
		$in_string = implode(',', $in_array);

		$sql =
		"	SELECT
				json_regions AS regions
			FROM
				geo_regions_collection
			WHERE
				(id IN ({$in_string}))
		";
		$query = $this->db->query($sql, $region_array);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	function save_regions_to_join_table($region_array, $session_id, $location_id, $mpq_id = false)
	{
		if($mpq_id === false)
		{
			$sql = 'SELECT id FROM mpq_sessions_and_submissions WHERE session_id = ?';
			$query = $this->db->query($sql, $session_id);

			if($query->num_rows() === 0)
			{
				return false;
			}

			$mss_response = $query->row_array();
			$mpq_id = $mss_response['id'];
		}
		$sql = 'DELETE FROM mpq_custom_geos_join WHERE mpq_id = ? AND location_id = ?';
		$query = $this->db->query($sql, array($mpq_id, $location_id));

		foreach($region_array as $v)
		{
			$sql = 'INSERT INTO mpq_custom_geos_join (mpq_id, geo_regions_collection_id, location_id) VALUES(?,?,?)';
			$query = $this->db->query($sql, array($mpq_id, $v, $location_id));
		}

	}

	public function shift_custom_regions_for_rfp_locations($session_id, $location_id_removed)
	{
		$bindings = array($location_id_removed, $session_id);
		$sql =
		"	UPDATE
				mpq_custom_geos_join
			SET
				location_id = location_id - 1
			WHERE
				location_id > ? AND
				mpq_id IN(SELECT id FROM mpq_sessions_and_submissions WHERE session_id = ?)
		";
		$query = $this->db->query($sql, $bindings);
	}

	function remove_selected_custom_regions_from_session($session_id, $location_id)
	{
		$bindings = array($location_id, $session_id);
		$sql =
		"	DELETE FROM
				mpq_custom_geos_join
			WHERE
				location_id = ? AND
				mpq_id IN(SELECT id FROM mpq_sessions_and_submissions WHERE session_id = ?)
		";
		$query = $this->db->query($sql, $bindings);
		return $this->db->affected_rows();
	}

	function remove_selected_custom_regions_with_mpq_id($mpq_id, $location_id)
	{
		$bindings = array($location_id, $mpq_id);
		$query = "
			DELETE FROM
				mpq_custom_geos_join
			WHERE
				location_id = ? AND
				mpq_id = ?";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	function get_custom_regions_by_mpq_id($mpq_id)
	{
		$sql =
		"	SELECT
				COALESCE(b.id, c.id) as id,
				COALESCE(b.name, c.geo_name) as geo_name
			FROM
				mpq_custom_geos_join a
			LEFT OUTER JOIN
				geo_regions_collection b
				ON b.id = a.geo_regions_collection_id
			LEFT OUTER JOIN
				custom_geos c
				ON c.id = a.custom_geos_id
			WHERE a.mpq_id = ?
		";
		$query = $this->db->query($sql, $mpq_id);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	function convert_demo_category_if_all_unset($demographics)
	{
		$demo_array = explode('_', $demographics);

		if($demo_array[0] == '0' AND $demo_array[1] == '0')
		{
			$demo_array[0] = '1'; //male
			$demo_array[1] = '1'; //female
		}
		if($demo_array[2] == '0' AND $demo_array[3] == '0' AND $demo_array[4] == '0' AND $demo_array[5] == '0' AND $demo_array[6] == '0' AND $demo_array[7] == '0' AND $demo_array[8] == '0')
		{
			$demo_array[2] = '1'; //under 18
			$demo_array[3] = '1'; //18-24
			$demo_array[4] = '1'; //25-34
			$demo_array[5] = '1'; //35-44
			$demo_array[6] = '1'; //45-54
			$demo_array[7] = '1'; //55-64
			$demo_array[8] = '1'; //over 65
		}
		if($demo_array[9] == '0' AND $demo_array[10] == '0' AND $demo_array[11] == '0' AND $demo_array[12] == '0')
		{
			$demo_array[9]  = '1'; //under 50k
			$demo_array[10] = '1'; //50k-100k
			$demo_array[11] = '1'; //100k-150k
			$demo_array[12] = '1'; //over 150k
		}
		if($demo_array[13] == '0' AND $demo_array[14] == '0' AND $demo_array[15] == '0')
		{
			$demo_array[13] = '1'; //no college
			$demo_array[14] = '1'; //college
			$demo_array[15] = '1'; //grad school
		}
		if($demo_array[16] == '0' AND $demo_array[17] == '0')
		{
			$demo_array[16] = '1'; //no kids
			$demo_array[17] = '1'; //has kids
		}

		$demographics = implode('_', $demo_array);
		return $demographics;
	}

	function copy_media_targeting_tags_from_mpq_to_proposal($mpq_id, $lap_id)
	{
			$sql = "INSERT INTO media_plan_sessions_iab_categories_join (media_plan_lap_id, iab_category_id) SELECT ?, iab_category_id FROM mpq_iab_categories_join WHERE mpq_id = ?";
			$query = $this->db->query($sql, array($lap_id, $mpq_id));
			$sql = "INSERT INTO prop_gen_sessions_iab_categories_join (prop_gen_lap_id, iab_category_id) SELECT ?, iab_category_id FROM mpq_iab_categories_join WHERE mpq_id = ?";
			$query = $this->db->query($sql, array($lap_id, $mpq_id));
	}

	function is_geo_data_populated_for_session($session_id)
	{
		$sql = 'SELECT region_data FROM mpq_sessions_and_submissions WHERE session_id = ?';
		$query = $this->db->query($sql, $session_id);
		$geo_data = $query->row_array();
		if($geo_data['region_data'] == null)
		{
			return false;
		}
		$region_data = json_decode($geo_data['region_data'], true);
		if(count($region_data[0]['ids']['zcta']) < 1)
		{
			return false;
		}
		return true;
	}

	function update_mpq_session_with_mailgun_errors($mpq_id, $errors)
	{
		$bindings = array(json_encode($errors), $mpq_id);
		$sql = "UPDATE mpq_sessions_and_submissions SET mailgun_error_text = ? WHERE id = ?";
		$response = $this->db->query($sql, $bindings);
		//With write type queries like UPDATE the response is only ever true or false. - Will
		if($response)
		{
			return true;
		}
		return false;
	}

	public function get_products_by_partner($partner_id)
	{
		$query = "
			SELECT
				pr.*
			FROM
				cp_products pr
				JOIN cp_partners_join_products pj
					ON pj.product_id = pr.id
				JOIN (
					SELECT
						ph.ancestor_id AS id,
						MIN(ph.path_length) AS path_length
					FROM
						wl_partner_hierarchy ph
						JOIN cp_partners_join_products pj
							ON ph.ancestor_id = pj.partner_id
					WHERE
						ph.descendant_id = ?
					GROUP BY
						ph.ancestor_id
					ORDER BY
						path_length ASC
					LIMIT 1
				) pq
					ON pj.partner_id = pq.id
			ORDER BY
				pr.display_order ASC";
		$result = $this->db->query($query, $partner_id);
		if($result !== false && $result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}

	public function save_submitted_rfp_products($product_object, $mpq_id, $is_builder = false)
	{
		$raw_region_data = $this->get_region_data_by_mpq_id($mpq_id);
		if($raw_region_data == false)
		{
			return false;
		}

		$bindings = array();
		$sql_value_string = "";
		$parsed_region_data = json_decode($raw_region_data, true);
		$this->map->convert_old_flexigrid_format($parsed_region_data);

		$region_data = array();
		foreach($parsed_region_data as $r_key => $r_val)
		{
			if(array_key_exists('ids', $r_val) && count($r_val['ids']['zcta']) > 0)
			{
				$region_data[] = $r_val;
			}
		}
		$proposal_id = NULL;
		$prop_gen_lap_ids = array();

		if($is_builder !== false)
		{
			$select_query = "SELECT * FROM cp_submitted_products WHERE mpq_id = ? GROUP BY region_data_index ORDER BY region_data_index ASC";
			$select_query = $this->db->query($select_query, $mpq_id);
			if($select_query->num_rows() > 0)
			{
				foreach($select_query->result_array() as $select_row)
				{
					$prop_gen_lap_ids[] = $select_row['frq_prop_gen_lap_id'];
				}
				
				$proposal_id = $select_query->row()->proposal_id;

			}
		}
		$region_count = count($region_data);
		if($region_count > 1)
		{
			$product_ids = array_keys($product_object);
			$geo_dependent_response = $this->get_geo_dependent_and_type_by_product_ids($product_ids);
			if(empty($geo_dependent_response) or count($geo_dependent_response) < count(array_unique($product_ids)))
			{
				return false;
			}

			$total_population = 0;
			foreach($region_data as $key => $region)
			{
				$population_result = $this->map->get_demographics_from_region_array($region['ids']);
				$population = intval($population_result['region_population']);
				if($population !== false)
				{
					$region_data[$key]['region_population'] = $population;
					$total_population += $population;
				}
			}

			foreach($product_object as $product_id => $product)
			{
				if($geo_dependent_response[$product_id]['geo_dependent'] == 0)
				{
					$option_id = 0;
					foreach($product as $fake_option_id => $option)
					{
						if($sql_value_string !== "")
						{
							$sql_value_string .= ", ";
						}
						$sql_value_string .= "(?,?,?,?,?,?,?)";
						$bindings[] = $mpq_id;
						$bindings[] = $proposal_id;
						$bindings[] = $option_id;
						$bindings[] = $product_id;
						if($is_builder !== false && !empty($prop_gen_lap_ids))
						{
							$bindings[] = $prop_gen_lap_ids[0];
						}
						else
						{
							$bindings[] = 0;
						}
						$bindings[] = json_encode($option);
						$bindings[] = 0;
						$option_id++;
					}
				}
				else
				{
					$option_id = 0;
					foreach($product as $fake_option_id => $option)
					{
						$temp_option = $option;
						if(array_key_exists('budget_allocation', $option) && $option['budget_allocation'] == 'per_pop')
						{
							$inventory_by_location = $this->calculate_geofencing_max_inventory($mpq_id, null, true);
							foreach($region_data as $key => $region)
							{
								$temp_option = $option;
								$pop_share_percent = intval($region['region_population']) / $total_population;
								$location_inventory = isset($inventory_by_location[$key]) ? intval($inventory_by_location[$key]) : 0;
								$temp_option['raw_unit'] = intval($option['unit']);

								if(array_key_exists('geofence_unit', $temp_option))
								{
									$temp_option['geofence_unit'] = intval($temp_option['geofence_unit']) * (boolval($location_inventory && $location_inventory > 0) ? 1 : 0);
									$temp_option['unit'] = ($temp_option['raw_unit'] * $pop_share_percent);
								}
								else
								{
									$temp_option['unit'] = $temp_option['raw_unit'] * $pop_share_percent;
								}

								if($sql_value_string !== "")
								{
									$sql_value_string .= ", ";
								}
								$sql_value_string .= "(?,?,?,?,?,?,?)";
								$bindings[] = $mpq_id;
								$bindings[] = $proposal_id;
								$bindings[] = $option_id;
								$bindings[] = $product_id;
								if($is_builder !== false && !empty($prop_gen_lap_ids))
								{
									$bindings[] = $prop_gen_lap_ids[$key];
								}
								else
								{
									$bindings[] = $key;
								}
								$bindings[] = json_encode($temp_option);
								$bindings[] = $key;
							}
						}
						else // fixed
						{
							$temp_total = round($option['unit'] / $region_count, 11); //round to 11 to eliminate repeating number precision since we're inserting to mysql as a string
							$first_unit_total = $option['unit'];
							for($i=1; $i < $region_count; $i++)
							{
								$first_unit_total -= $temp_total; //subtract until you get the remainder so when we add them up again it will equal the sum total
							}
							for($i = 0; $i < $region_count; $i++)
							{
								$submitted_data = $option;
								if($i == 0)
								{
									$submitted_data['unit'] = $first_unit_total;
								}
								else
								{
									$submitted_data['unit'] = $option['unit'] / $region_count;
								}

								$temp_option['raw_unit'] = $temp_option['unit'] / $region_count;
								if(array_key_exists('geofence_unit', $temp_option))
								{
									$location_inventory = isset($inventory_by_location[$key]) ? intval($inventory_by_location[$key]) : 0;
									$temp_option['geofence_unit'] = intval($temp_option['geofence_unit']) * intval(boolval($location_inventory && $location_inventory > 0));
									$temp_option['unit'] = $temp_option['raw_unit'] - $temp_option['geofence_unit'];
								}
								else
								{
									$temp_option['unit'] = $temp_option['raw_unit'] / $region_count;
								}

								if($sql_value_string !== "")
								{
									$sql_value_string .= ", ";
								}
								$sql_value_string .= "(?,?,?,?,?,?,?)";
								$bindings[] = $mpq_id;
								$bindings[] = $proposal_id;
								$bindings[] = $option_id;
								$bindings[] = $product_id;
								if($is_builder !== false && !empty($prop_gen_lap_ids))
								{
									$bindings[] = $prop_gen_lap_ids[$i];
								}
								else
								{
									$bindings[] = $i;
								}
								$bindings[] = json_encode($submitted_data);
								$bindings[] = $i;
							}
						}
						$option_id++;
					}
				}
			}
		}
		else
		{
			foreach($product_object as $product_id => $product)
			{
				$option_id = 0;
				foreach($product as $fake_option_id => $option)
				{
					if($sql_value_string !== "")
					{
						$sql_value_string .= ", ";
					}
					$sql_value_string .= "(?,?,?,?,?,?,?)";
					$bindings[] = $mpq_id;
					$bindings[] = $proposal_id;
					$bindings[] = $option_id;
					$bindings[] = $product_id;
					if($is_builder !== false && !empty($prop_gen_lap_ids))
					{
						$bindings[] = $prop_gen_lap_ids[0];
					}
					else
					{
						$bindings[] = 0;
					}
					$bindings[] = json_encode($option);
					$bindings[] = 0;
					$option_id++;
				}
			}
		}

		if(count($bindings) == 0)
		{
			return false;
		}
		if($is_builder !== false)
		{
			$delete_query = "DELETE FROM cp_submitted_products WHERE mpq_id = ?";
			$delete_response = $this->db->query($delete_query, $mpq_id);
		}

		$query = "
			INSERT INTO
				cp_submitted_products
					(mpq_id, proposal_id, option_id, product_id, frq_prop_gen_lap_id, submitted_values, region_data_index)
				VALUES
					".$sql_value_string;
		$response = $this->db->query($query, $bindings);
		return $response;
	}
	public function get_total_population_by_regions($region_array)
	{
		$bindings = array();
		$sql_string = "";
		foreach($region_array as $region)
		{
			$bindings[] = $region;
			if($sql_string !== "")
			{
				$sql_string .= ", ";
			}
			$sql_string .= "?";
		}
		$query = "
			SELECT
				SUM(HD01_VD01) as total_population
			FROM
				geo_ACS_12_5YR_B01001_zcta
			WHERE
				id2 IN(".$sql_string.")";
		$response = $this->db->query($query, $bindings);
		if($response !== false and $response->num_rows() > 0)
		{
			$row = $response->row_array();
			return $row['total_population'];
		}
		return false;
	}

	public function get_region_data_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				region_data
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response !== false and $response->num_rows() > 0)
		{
			$row = $response->row_array();
			return $row['region_data'];
		}
		return false;
	}

	public function get_geo_dependent_and_type_by_product_ids($product_ids)
	{
		$binding_sql = "";
		$binding_array = array();
		foreach($product_ids as $product_id)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "?";
			$binding_array[] = $product_id;
		}
		$query = "
			SELECT
				*
			FROM
				cp_products
			WHERE
				id IN(".$binding_sql.")";
		$response = $this->db->query($query, $binding_array);
		if($response !== false and $response->num_rows() > 0)
		{
			$result_array = array();
			foreach($response->result_array() as $product)
			{
				$result_array[$product['id']] = array('geo_dependent' => $product['is_geo_dependent'], 'product_type' => $product['product_type']);
			}
			return $result_array;
		}
		return array();
	}

	public function save_submitted_mpq_options($option_data, $mpq_id, $discount_text)
	{
		$binding_sql = "";
		$binding_array = array();
		foreach($option_data as $id => $option)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "(?,?,?,?,?,?,?,?)";
			$binding_array[] = $mpq_id;
			$binding_array[] = $id;
			$binding_array[] = $option['name'];
			$binding_array[] = $option['discount'];
			$binding_array[] = $option['term'];
			$binding_array[] = $option['duration'];
			$binding_array[] = $discount_text;
			$binding_array[] = $option['grand_total'];
		}

		$query = "
			INSERT IGNORE INTO
				mpq_options
					(mpq_id, option_id, option_name, discount, term, duration, discount_name, grand_total_dollars)
				VALUES
					".$binding_sql;
		$response = $this->db->query($query, $binding_array);
		if($response !== false)
		{
			return true;
		}
		return false;
	}

	public function get_submitted_mpq_options($mpq_id)
	{
		$sql = "
			SELECT
				*
			FROM
				iab_categories AS ic
			JOIN
				mpq_iab_categories_join AS micj ON iab_category_id=ic.id
			WHERE
				micj.mpq_id=?
			ORDER BY
				tag_copy ASC";
		$bindings = array($mpq_id);
		$response = $this->db->query($sql, $bindings);

		$query_response_array = $response->result_array();
		return $query_response_array;
	}

	// create proposal from mpq.
	public function create_proposal_from_mpq_v2(&$prop_id, $mpq_id, $user_id)
	{
		$prop_id = intval($prop_id, 10);
		$mpq_id = intval($mpq_id, 10);

		$create_proposal_response = array('is_success' => true, 'mpq_type' => '', 'errors' => '');
		$k_rf_geo_coverage = 0.87;
		$k_rf_gamma = 0.30;
		$k_rf_ip_accuracy = 0.99;
		$k_rf_demo_coverage = 0.87;

		$prop_id = 0;

		//section 1: find rep details
		$rep_id = NULL;
		$mpq_type = "proposal";
		$industry_id = null;

		$insertion_or_option_sql =
		"	SELECT
				0 as is_insertion_order,
				1 as is_proposal,
				mss.owner_user_id AS rep_id,
				(usr.role = 'SALES') AS is_sales,
				industry_id
			FROM mpq_sessions_and_submissions mss
				LEFT JOIN users AS usr
					ON usr.id = mss.owner_user_id
			WHERE mss.id = ?
		";
		$insertion_or_option_query = $this->db->query($insertion_or_option_sql, $mpq_id);

		if($insertion_or_option_query->num_rows() > 0)
		{
			$temp_io_array = $insertion_or_option_query->row_array();
			if($temp_io_array['is_insertion_order'] == 1 AND $temp_io_array['is_proposal'] == 0)
			{
				$mpq_type = "insertion order";
			}
			else if($temp_io_array['is_insertion_order'] == 0 AND $temp_io_array['is_proposal'] == 1)
			{
				$mpq_type = "proposal";
			}
			else
			{
				$mpq_type = "unknown";
			}

			$rep_id = $temp_io_array['is_sales'] ? $temp_io_array['rep_id'] : null;
			$industry_id=$temp_io_array['industry_id'];
		}

		// section 1.5: create_prop_gen_prop_data_sql
		// create prop_gen_prop_data record
		$create_prop_gen_prop_data_sql = '
			INSERT INTO prop_gen_prop_data
				(prop_name, source_mpq, rep_id, process_status, industry_id, auto_proposal_email_notes)
			VALUE (?, ?, ?, ?, ?, ?)
		';

		$create_prop_gen_prop_data_bindings = array(
			'create from mpq '.$mpq_id,
			$mpq_id,
			$rep_id,
			'queued-auto',
			$industry_id,
			date('Y-m-d H:i:s').",queued-auto\n"
		);

		$response = $this->db->query($create_prop_gen_prop_data_sql, $create_prop_gen_prop_data_bindings);
		$prop_id = $this->db->insert_id();
		$create_proposal_response['mpq_id'] = $mpq_id;
		$create_proposal_response['prop_id'] = $prop_id;

		$cp_submitted_products_sql=
			"UPDATE
				cp_submitted_products
			SET
				proposal_id=?
			WHERE
				mpq_id=?";

		$cp_submitted_products_bindings = array($prop_id, $mpq_id);
		$this->db->query($cp_submitted_products_sql, $cp_submitted_products_bindings);

		//section 2: find geo and demo details

		$regions = null;
		$demographics = null;
		$this->get_regions_and_demographics_from_mpq($regions, $demographics, $mpq_id);

		// section 3: for loop for regions
		$geo_counter=0;
		foreach ($regions as $key => $region_array)
		{
			$region=json_encode($region_array);
			$population = 0;
			$demo_population = 0;
			$internet_average = 0.5;

			// multi geo subsection 1: find demo data for region
			$this->map->calculate_population_based_on_selected_demographics(
				$population,
				$demo_population,
				$internet_average,
				$region_array,
				$demographics
			);

			// multi geo subsection 2: populate media plan session

				$fake_session_id = md5(uniqid(mt_rand().'', true));
				$create_media_plan_session_sql = '
				INSERT INTO media_plan_sessions
				(
					session_id,
					notes,
					region_data,
					demographic_data,
					selected_iab_categories,
					population,
					demo_population,
					internet_average,
					rf_geo_coverage,
					rf_gamma,
					rf_ip_accuracy,
					rf_demo_coverage,
					source_mpq
				)
				SELECT
					?,
					?,
					?,
					mpq.demographic_data,
					mpq.selected_iab_categories,
					?,
					?,
					?,
					?,
					?,

					?,
					?,
					?
				FROM mpq_sessions_and_submissions AS mpq
				WHERE
					mpq.id = ?
			';
			$create_media_plan_session_bindings = array(
				$fake_session_id,
				'place holder session for lap_id creation by mpq #'.$mpq_id,
				$region,
				$population,
				$demo_population,
				$internet_average,
				$k_rf_geo_coverage,
				$k_rf_gamma,
				$k_rf_ip_accuracy,
				$k_rf_demo_coverage,
				$mpq_id,
				$mpq_id
				);
			$response = $this->db->query($create_media_plan_session_sql, $create_media_plan_session_bindings);
			$lap_id = $this->db->insert_id();

			// multi geo subsection 3: populate prop_gen_sessions
			//	populate data from MPQ and populate default data for remaining fields
			$create_prop_gen_session_sql = '
				INSERT INTO prop_gen_sessions
					(
					region_data,
					demographic_data,
					selected_iab_categories,
					lap_id,
					plan_name,

					advertiser,
					notes,
					recommended_impressions,
					price_notes,
					population,

					demo_population,
					site_array,
					has_retargeting,
					rf_geo_coverage,
					rf_gamma,

					rf_ip_accuracy,
					rf_demo_coverage,
					internet_average,
					owner_id,
					date_created,

					source_mpq
					)
				SELECT
					?,
					mpq.demographic_data,
					mpq.selected_iab_categories,
					?,
					?,

					mpq.advertiser_name,
					?,
					?,
					?,
					?,

					?,
					?,
					?,
					?,
					?,

					?,
					?,
					?,
					mpq.owner_user_id,
					?,

					?
				FROM mpq_sessions_and_submissions AS mpq
				WHERE
					mpq.id = ?
			';

			$create_prop_gen_session_bindings = array(
				$region,
				$lap_id,
				"Lap " . $key . " from MPQ ".$mpq_id,
				"notes",
				0,
				"price notes",
				$population,
				$demo_population,
				json_encode(array()),
				1,
				$k_rf_geo_coverage,
				$k_rf_gamma,
				$k_rf_ip_accuracy,
				$k_rf_demo_coverage,
				$internet_average,
				date("Y-m-d"),
				$mpq_id,
				$mpq_id
				);
			$response = $this->db->query($create_prop_gen_session_sql, $create_prop_gen_session_bindings);

			// multi geo subsection 4: populate media_targeting_tags
			//Copying media_targeting_tags
			$this->copy_media_targeting_tags_from_mpq_to_proposal($mpq_id, $lap_id);

			// geo subsection 5: TODO. replace this hardcoded 3 loop with number of options in products table
			$cp_submitted_products_sql=
			"UPDATE
				cp_submitted_products
			SET
				frq_prop_gen_lap_id=?
			WHERE
				mpq_id=? AND
				frq_prop_gen_lap_id=?";

			$cp_submitted_products_bindings = array($lap_id, $mpq_id, $geo_counter);

			$this->db->query($cp_submitted_products_sql, $cp_submitted_products_bindings);

			$geo_counter++;
		}
		// geo for loop ends here.


		if($mpq_type == 'proposal')
		{
			//section 0: select mpq_options to be used in for loop later to populate prop_gen_option_prop_join table
			$mpq_options_select_sql =
			'	SELECT
					option_id AS option_id,
					option_name AS option_name,
					discount AS discount,
					term,
					duration,
					discount_name
				FROM
					mpq_options
				WHERE
					mpq_id = ?
			';

			$mpq_options_select_query = $this->db->query($mpq_options_select_sql, $mpq_id);
			if($mpq_options_select_query->num_rows() > 0)
			{
				foreach ($mpq_options_select_query->result_array() as $mpq_options_row)
				{
		 				$create_option_sql = '
								INSERT prop_gen_option_prop_join
								(
									option_id,
									prop_id,
									option_name,
									one_time_cost_raw,
									one_time_abs_discount,

									one_time_percent_discount,
									one_time_discount_description,
									one_time_cost,
									monthly_cost_raw,
									monthly_abs_discount,

									monthly_percent_discount,
									monthly_discount_description,
									monthly_cost,
									cost_by_campaign,
									term,
									duration
								)
								VALUE (
									?,?,?,?,?,
									?,?,?,?,?,
									?,?,?,?,
									?,?
								)
							';

						$create_option_bindings = array(
							$mpq_options_row["option_id"],
							$prop_id,
							$mpq_options_row["option_name"],
							0,
							0,

							0,
							'',
							0,
							0,//$base_dollars_per_term,
							0,

							$mpq_options_row["discount"],
							$mpq_options_row["discount_name"],
							0,//$charged_dollars_per_term,
							0 ,
							$mpq_options_row["term"],
							$mpq_options_row["duration"]
							);
						$response = $this->db->query($create_option_sql, $create_option_bindings);
				}
			}
		}
		else if($mpq_type = "insertion order")
		{

		}
		else
		{
			$create_proposal_response['is_success'] = false;
			$create_proposal_response['errors'] = 'Unknown mpq type (#8745445)';
		}
		$create_proposal_response['mpq_type'] = $mpq_type;
		$this->update_unique_id_for_latest_proposal($mpq_id);
		return $create_proposal_response;
	}

	public function set_proposal_status($proposal_id, $status)
	{
		$proposal_status_sql = "
			UPDATE prop_gen_prop_data
			SET process_status=?,
			auto_proposal_email_notes=CONCAT(auto_proposal_email_notes, ?)
			WHERE prop_id=?";

		$bindings = array($status, date('Y-m-d H:i:s') . ',' . $status . "\n", $proposal_id);

		$update_result = $this->db->query($proposal_status_sql, $bindings);
		$affected_rows = $this->db->affected_rows();

		return ($update_result && $affected_rows);
	}

	public function mark_snapshots_started_time($proposal_id)
	{
		$proposal_sql =
		"	UPDATE prop_gen_prop_data
			SET snapshots_started = NOW()
			WHERE prop_id = ?
		";
		return $this->db->query($proposal_sql, $proposal_id);
	}

	public function check_and_set_proposal_snapshot_status($proposal_id, $source_mpq_id)
	{
		$bindings = array($proposal_id, $source_mpq_id, $proposal_id);
		$sql =
		"	SELECT
				IF(
					count_pgs.count > 0,
					IF(
						count_pgs.count > 1,
						count_pgs.count + 1 = count_pgsd.count,
						count_pgsd.count = 1
					),
					1
				) AS snapshots_good,
				IF(
					rooftops.has_rooftops,
					IF(
						rooftops.rooftops_snapshot IS NOT NULL,
						true,
						false
					),
					true
				) AS rooftops_good
			FROM
				(
					SELECT
						COUNT(*) AS count
					FROM
						prop_gen_snapshot_data
					WHERE
						prop_id = ?
				) AS count_pgsd,
				(
					SELECT
						COUNT(*) AS count
					FROM
						prop_gen_sessions
					WHERE
						source_mpq = ?
				) AS count_pgs,
				(
					SELECT
						mss.rooftops_data AS has_rooftops,
						pgpd.rooftops_snapshot
					FROM
						prop_gen_prop_data AS pgpd
					JOIN
						mpq_sessions_and_submissions AS mss ON mss.id = pgpd.source_mpq
					WHERE
						pgpd.prop_id = ?
				) AS rooftops;
		";
		$result = $this->db->query($sql, $bindings);

		if($result->num_rows > 0)
		{
			if($result->row()->snapshots_good == 1 && $result->row()->rooftops_good)
			{
				$this->set_proposal_status($proposal_id, 'snapshots-complete');
			}
		}
	}

	public function get_proposal_with_mpq($proposal_id)
	{
		$sql = "
			SELECT
				*
			FROM
				prop_gen_prop_data as prop
			JOIN
				mpq_sessions_and_submissions AS mpq ON source_mpq=mpq.id
			WHERE
				prop_id=?";
		$bindings = array($proposal_id);
		$response = $this->db->query($sql, $bindings);

		if($response->num_rows > 0)
		{
			$query_response_array = $response->result_array();
			return $query_response_array[0];
		}
		else
		{
			return false;
		}
	}

	public function get_mpq_summary_data($mpq_id)
	{
		$sql = "
			SELECT
				*
			FROM
				mpq_sessions_and_submissions
			WHERE
				id=?";
		$bindings = array($mpq_id);
		$response = $this->db->query($sql, $bindings);

		if($response->num_rows > 0)
		{
			$query_response_array = $response->result_array();
			return $query_response_array[0];
		}
		else
		{
			return false;
		}
	}

	public function get_rooftops_data($mpq_id)
    {
        $sql = '
            SELECT rooftops_data
            FROM mpq_sessions_and_submissions
            WHERE id = ?
        ';
        $result = $this->db->query($sql, array($mpq_id));
        return $result->row()->rooftops_data;
    }

	public function get_proposals_with_process_status($status)
	{
		$sql = "
			SELECT
				*
			FROM
				prop_gen_prop_data as prop
			JOIN
				mpq_sessions_and_submissions AS mpq ON source_mpq=mpq.id
			WHERE
				process_status=?";
		$bindings = array($status);
		$response = $this->db->query($sql, $bindings);

		$query_response_array = $response->result_array();
		return $query_response_array;
	}

	public function initialize_location_in_mpq_session($session_id, $location_id, $location_name, $mpq_id = false)
	{
		$return_array = array();
		if($mpq_id !== false)
		{
			$select_sql =
			"	SELECT
					region_data
				FROM
					mpq_sessions_and_submissions
				WHERE
					id = ?;
			";
			$result = $this->db->query($select_sql, $mpq_id);
		}
		else
		{
		
			$select_sql =
			"	SELECT
					region_data
				FROM
					mpq_sessions_and_submissions
				WHERE
					session_id = ?;
			";
			$result = $this->db->query($select_sql, $session_id);
		}
		if($result->num_rows() > 0)
		{
			$current_regions = json_decode($result->row()->region_data, true);
			if((count($current_regions) == intval($location_id)) && !(isset($current_regions[intval($location_id)])))
			{
				$location_name = (empty($location_name)) ? '' : $location_name;

				$current_regions[intval($location_id)]['page'] = $location_id;
				$current_regions[intval($location_id)]['user_supplied_name'] = $location_name;
				$current_regions[intval($location_id)]['total'] = 0;
				$current_regions[intval($location_id)]['search_type'] = 'custom_regions';
				$current_regions[intval($location_id)]['ids'] = array('zcta' => array());

				$json_regions = json_encode($current_regions);

				$update_sql =
				"	UPDATE
						mpq_sessions_and_submissions
					SET
						region_data = ?
					WHERE
						session_id = ?;
				";
				$bindings = array($json_regions, $session_id);
				$update_result = $this->db->query($update_sql, $bindings);
				$affected_rows = $this->db->affected_rows();

				$return_array['is_success'] = ($update_result && ($affected_rows > 0));
				$return_array['errors'] = (($this->db->_error_message())) ? array($this->db->_error_message()) : array();
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = ['Location unable to be initialized'];
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = ['No row found in database for this user'];
		}

		return $return_array;
	}

	public function save_location_name($session_id, $location_id, $location_name, $manually_renamed_by_user)
	{
		$select_sql =
		"	SELECT
				region_data
			FROM
				mpq_sessions_and_submissions
			WHERE
				session_id = ?;
		";
		$result = $this->db->query($select_sql, $session_id);

		if($result->num_rows() > 0)
		{
			$current_regions = json_decode($result->row()->region_data, true);
			if(isset($current_regions[intval($location_id)]))
			{
				$current_regions[intval($location_id)]['user_supplied_name'] = $location_name;
				$current_regions[intval($location_id)]['user_renamed'] = $manually_renamed_by_user;
				$json_regions = json_encode($current_regions);

				$update_sql =
				"	UPDATE
						mpq_sessions_and_submissions
					SET
						region_data = ?
					WHERE
						session_id = ?;
				";
				$bindings = array($json_regions, $session_id);
				$update_result = $this->db->query($update_sql, $bindings);
				$affected_rows = $this->db->affected_rows();

				return ($update_result && ($affected_rows > 0));
			}
		}
		return true;
	}

	public function remove_location($session_id, $location_id)
	{
		$select_sql =
		"	SELECT
				region_data
			FROM
				mpq_sessions_and_submissions
			WHERE
				session_id = ?;
		";
		$result = $this->db->query($select_sql, $session_id);

		if($result->num_rows() > 0)
		{
			$current_regions = json_decode($result->row()->region_data, true);
			if(isset($current_regions[intval($location_id)]))
			{
				unset($current_regions[intval($location_id)]);
				$current_regions = array_values($current_regions);

				foreach($current_regions as $index => $region_info)
				{
					$current_regions[$index]['page'] = $index;
				}

				$json_regions = json_encode($current_regions);

				$update_sql =
				"	UPDATE
						mpq_sessions_and_submissions
					SET
						region_data = ?
					WHERE
						session_id = ?;
				";
				$bindings = array($json_regions, $session_id);
				$update_result = $this->db->query($update_sql, $bindings);
				$affected_rows = $this->db->affected_rows();

				return ($update_result && ($affected_rows > 0));
			}
		}
		return true;
	}

	public function validate_multi_geos_location_data_from_mpq_table($session_id)
	{
		$return_array = array('is_success' => true, 'errors' => array());

		$select_sql =
		"	SELECT
				region_data
			FROM
				mpq_sessions_and_submissions
			WHERE
				session_id = ?;
		";
		$result = $this->db->query($select_sql, $session_id);

		if($result->num_rows() > 0)
		{
			$current_regions = json_decode($result->row()->region_data, true);
			$this->map->convert_old_flexigrid_format($current_regions);
			if(!empty($current_regions) && count($current_regions) > 0)
			{
				foreach($current_regions as $location_id => $location_data)
				{
					if(!isset($location_data['ids']['zcta']) || count($location_data['ids']['zcta']) < 1)
					{
						$location_name = (isset($location_data['user_supplied_name'])) ? $location_data['user_supplied_name'] : 'Name not set';
						$return_array['is_success'] = false;
						$return_array['errors'][] = "Error in location id {$location_id} ({$location_name}): Please add regions to this location or remove the location.";
					}
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'][] = 'Location data is empty';
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'][] = 'No row found in database for this user';
		}

		return $return_array;
	}

	public function get_industry_by_id($industry_id)
	{
		$query = "
			SELECT
				*
			FROM
				freq_industry_tags
			WHERE
				freq_industry_tags_id = ?";
		$response = $this->db->query($query, $industry_id);
		if($response and $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function get_advertiser_name_on_mpq($mpq_id)
	{
		$query = "
			SELECT
				advertiser_name
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response !== false and $response->num_rows() > 0)
		{
			$row = $response->row_array();
			return $row['advertiser_name'];
		}
		return false;
	}

	public function get_proposal_id_by_unique_display_id($unique_display_id)
	{
		$sql =
		"	SELECT pgpd.prop_id
			FROM mpq_sessions_and_submissions mss
			JOIN prop_gen_prop_data pgpd
				ON mss.id = pgpd.source_mpq
			WHERE
				mss.unique_display_id = ? AND
				mss.is_submitted = 1
			ORDER BY prop_id DESC
		";
		$result = $this->db->query($sql, $unique_display_id);
		return $result->num_rows() > 0 ? $result->row()->prop_id : false;
	}

	public function get_proposal_id_by_mpq_id($mpq_id)
	{
		$sql =
		"	SELECT prop_id
			FROM prop_gen_prop_data
			WHERE source_mpq = ?
		";
		$result = $this->db->query($sql, $mpq_id);
		return $result->num_rows() > 0 ? $result->row()->prop_id : false;
	}

	public function get_parent_proposal_id_by_unique_display_id($unique_display_id)
	{
		$sql =
		"	SELECT parent_proposal_id
			FROM mpq_sessions_and_submissions
			WHERE
				unique_display_id = ?
		";
		$result = $this->db->query($sql, $unique_display_id);
		return $result->num_rows() > 0 ? $result->row()->parent_proposal_id : false;
	}

	public function get_unique_display_id_by_mpq_id($mpq_id)
	{
		$sql =
		"	SELECT unique_display_id
			FROM mpq_sessions_and_submissions
			WHERE id = ?
		";
		$result = $this->db->query($sql, $mpq_id);
		return $result->num_rows() > 0 ? $result->row()->unique_display_id : false;
	}

	public function is_valid_unique_display_id($unique_display_id)
	{
		$sql =
		"	SELECT unique_display_id
			FROM mpq_sessions_and_submissions
			WHERE unique_display_id = ?
		";
		$result = $this->db->query($sql, $unique_display_id);
		return $result->num_rows() > 0;
	}

	public function update_unique_id_for_latest_proposal($mpq_id)
	{
		$unique_display_id = $this->get_unique_display_id_by_mpq_id($mpq_id);
		$query =
		"	UPDATE mpq_sessions_and_submissions
			SET unique_display_id = CONCAT(unique_display_id, '-', FLOOR(NOW() + 0))
			WHERE
				is_submitted = 1 AND
				id != ? AND
				unique_display_id = ?
		";
		$result = $this->db->query($query, array((int) $mpq_id, $unique_display_id));
	}

	public function initialize_mpq_session_from_existing_proposal($session_id, $owner_id, $proposal_id, $submission_method)
	{
		$this->unlock_mpq_session_by_session_id($session_id);

		$date_time_created = date("Y-m-d H:i:s");

		$bindings = array();
		$bindings[] = $session_id;
		$bindings[] = $date_time_created;
		$bindings[] = $owner_id;
		$parent_mpq_id_sql = "mss.id";
		$unique_id_sql = "unique_display_id,NULL";
		if($submission_method == "copy")
		{
			$parent_mpq_id_sql = "NULL";
			$bindings[] = $this->generate_insertion_order_unique_id();
			$bindings[] = $proposal_id;
			$unique_id_sql = "?,?";
		}

		$bindings[] = $proposal_id;

		$query = "
			REPLACE INTO mpq_sessions_and_submissions (
				session_id,
				creation_time,
				creator_user_id,
				strategy_id,
				region_data,
				demographic_data,
				selected_iab_categories,
				advertiser_name,
				advertiser_website,
				proposal_name,
				submitter_name,
				submitter_relationship,
				submitter_agency_website,
				submitter_email,
				submitter_phone,
				selected_channels,
				notes,
				snapshot_title,
				snapshot_data,
				mailgun_error_text,
				industry_id,
				budget_allocation_method_type,
				original_submission_summary_html,
				parent_mpq_id,
				owner_user_id,
				unique_display_id,
				parent_proposal_id,
				presentation_date
			)
			SELECT
				?,
				?,
				?,
				mss.strategy_id,
				mss.region_data,
				mss.demographic_data,
				mss.selected_iab_categories,
				mss.advertiser_name,
				mss.advertiser_website,
				mss.proposal_name,
				mss.submitter_name,
				mss.submitter_relationship,
				mss.submitter_agency_website,
				mss.submitter_email,
				mss.submitter_phone,
				mss.selected_channels,
				mss.notes,
				mss.snapshot_title,
				mss.snapshot_data,
				mss.mailgun_error_text,
				mss.industry_id,
				mss.budget_allocation_method_type,
				mss.original_submission_summary_html,
				".$parent_mpq_id_sql.",
				mss.owner_user_id,
				".$unique_id_sql.",
				mss.presentation_date
			FROM
				cp_submitted_products as csp
				JOIN mpq_sessions_and_submissions mss
					ON csp.mpq_id = mss.id
			WHERE
				csp.proposal_id = ?
			LIMIT 1";

		$mss_result = $this->db->query($query, $bindings);
		if($mss_result !== false)
		{
			$new_mpq_id = $this->db->insert_id();
			$custom_regions_bindings = [$new_mpq_id, $proposal_id];
			$custom_regions_sql =
			"	REPLACE INTO
					mpq_custom_geos_join (
						mpq_id, geo_regions_collection_id, location_id
					)
				SELECT
					?,
					mcgj.geo_regions_collection_id,
					mcgj.location_id
				FROM
					cp_submitted_products as csp
				INNER JOIN
					mpq_custom_geos_join mcgj
					ON
						csp.mpq_id = mcgj.mpq_id
				WHERE
					csp.proposal_id = ?
			";
			$this->db->query($custom_regions_sql, $custom_regions_bindings);
			return $new_mpq_id;
		}
		return false;
	}

	public function save_rfp_gate_data(
		$id,
		$user_id,
		$owner_id,
		$advertiser_name,
		$advertiser_website,
		$industry_id,
		$strategy_id,
		$proposal_name,
        $presentation_date
	)
	{
		$sql =
		"	UPDATE mpq_sessions_and_submissions
			SET
				creator_user_id = ?,
				owner_user_id = ?,
				advertiser_name = ?,
				advertiser_website = ?,
				industry_id = ?,
				strategy_id = ?,
				proposal_name = ?,
				presentation_date = ?
			WHERE
				id = ?
		";
		$query = $this->db->query($sql, array($user_id, $owner_id, $advertiser_name, $advertiser_website, $industry_id, $strategy_id,$proposal_name, $presentation_date, $id));
	}

	public function get_rfp_preload_mpq_session_data($session_id, $unique_display_id = false, $mpq_id = false, $ignore_session_id = false)
	{
		if($mpq_id !== false)
		{
			$where_sql = "id = ?";
			$bindings = array($mpq_id);
		}
		else if($ignore_session_id === false)
		{
			$where_sql = "session_id = ?";
			$bindings = array($session_id);
		}

		if($unique_display_id !== false)
		{
			if($ignore_session_id !== false)
			{
				$where_sql = "unique_display_id = ?";
				$bindings = array($unique_display_id);
			}
			else
			{
				$where_sql .= " AND unique_display_id = ?";
				$bindings[] = $unique_display_id;
			}
		}

		if($where_sql === "")
		{
			return false;
		}

		$query = "
			SELECT
				id,
				strategy_id,
				is_submitted,
				region_data,
				demographic_data,
				selected_iab_categories,
				advertiser_name,
				advertiser_website,
				proposal_name,
				submitter_name,
				submitter_email,
				industry_id,
				budget_allocation_method_type,
				parent_mpq_id,
				owner_user_id,
				original_owner_id,
				cc_owner,
				notes,
				rooftops_data,
				io_advertiser_id,
				source_table,
				tracking_tag_file_id,
				include_retargeting,
				unique_display_id,
				mpq_type,
				order_name,
				order_id,
				presentation_date
			FROM
				mpq_sessions_and_submissions
			WHERE
				".$where_sql."
		";

		$response = $this->db->query($query, $bindings);
		if($response && $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function get_industry_data_by_id($industry_id)
	{
		$query = "
			SELECT
				*
			FROM
				freq_industry_tags
			WHERE
				freq_industry_tags_id = ?";
		$response = $this->db->query($query, $industry_id);
		if($response and $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function add_submitted_data_to_products($product_data, $proposal_id)
	{
		$query = "
			SELECT
				*
			FROM
				cp_submitted_products
			WHERE
				proposal_id = ?";
		$response = $this->db->query($query, intval($proposal_id));

		$num_laps = $this->get_num_laps_from_products_by_proposal_id($proposal_id);
		if($num_laps === false or $num_laps == 0)
		{
			return false;
		}

		$mpq_id = intval($response->row()->mpq_id);

		$location_populations = [];
		$locations_sql = "
			SELECT
				pgs.population
			FROM
				prop_gen_sessions AS pgs
			WHERE
				source_mpq = ?
		";
		$location_population_response = $this->db->query($locations_sql, $mpq_id);
		$location_populations = array_map('intval', $location_population_response->result_array());

		foreach ($product_data as &$product)
		{
			if (!isset($product['options']))
			{
				$product['options'] = [];
			}

			$product['definition'] = json_decode($product['definition'], true);

			foreach ($response->result_array() as $submitted_product)
			{
				if ($product['id'] == $submitted_product['product_id'])
				{
					$submitted_values = json_decode($submitted_product['submitted_values'], true);
					$product['options'][$submitted_product['option_id']] = array_key_exists($submitted_product['option_id'], $product['options']) ?
						$this->merge_product_values($product['options'][$submitted_product['option_id']], $submitted_values, $product) :
						$submitted_values;
				}
			}

			$product['selected'] = count($product['options']) > 0;

			if (count($product['options']) < 3)
			{
				for ($i = count($product['options']); $i < 3; $i++)
				{
					$product['options'][$i] = array( 'default' => true );
				}
			}

			if (isset($product['definition']['options']))
			{
				foreach ($product['definition']['options'] as $i => $option)
				{
					foreach($option as $key => $value)
					{
						if (!array_key_exists($key, $product['options'][$i]))
						{
							if (gettype($value) === 'array' && isset($value['default']))
							{
								$product['options'][$i][$key] = $value['default'];
							}
							else
							{
								$product['options'][$i][$key] = $value;
							}
						}
					}
				}

				foreach ($product['options'] as $i => &$option_b)
				{
					if (array_key_exists('unit', $option_b) && !array_key_exists('default', $option_b))
					{
						$value = $option_b['unit'];
						if(array_key_exists('type', $option_b))
						{
							if($option_b['type'] == "dollars")
							{
								$budget_multiplier = 1000;
								if(array_key_exists('unit_multiplier', $option_b))
								{
									$budget_multiplier = $option_b['unit_multiplier'];
								}
								if ($product['has_geofencing'] === "1" && array_key_exists('geofence_unit', $option_b))
								{
									$geofence_dollars = $option_b['geofence_unit'] * ($option_b['geofence_cpm'] / $budget_multiplier);
									$vanilla_dollars = ($option_b['unit'] - $option_b['geofence_unit']) * ($option_b['cpm'] / $budget_multiplier);
									$value = round($geofence_dollars + $vanilla_dollars);
								}
								else
								{
									$value = round($value * $option_b['cpm'] / $budget_multiplier);
								}
							}
						}
						$option_b['unit'] = (int) $value;
					}
					if(array_key_exists('custom_name', $option_b) && !empty($option_b['custom_name']))
					{
						$product['definition']['last_name'] = $option_b['custom_name'];
					}
				}
			}
			else
			{
				foreach ($product['options'] as &$option)
				{
					$option = $product['definition'];
				}
			}
		}
		return $product_data;
	}

	public function merge_product_values(&$product_a, $product_b, $product)
	{
		foreach($product_b as $name => $value)
		{
			if($name == "custom_name")
			{
				$product_a['last_name'] = $value;
			}
			else if($name == "cpm")
			{
				$product_a['cpm'] = (float) $value;
			}
			else if($name == "cpc")
			{
				$product_a['cpc'] = (float) $value;
			}
			else if($name == "content")
			{
				$product_a['content'] = (int) $value;
			}
			else if($name == "inventory")
			{
				$product_a['inventory'] = (int) $value;
			}
			else if($name == "raw_inventory")
			{
				$product_a['inventory']['raw_default'] = (int) $value;
			}
			else if($name == "price")
			{
				$product_a['price'] = (int) $value;
			}
			else if($name == "budget_allocation")
			{
				$product_a['budget_allocation'] = $value;
			}
			else if($name == "geofence_cpm")
			{
				$product_a['geofence_cpm'] = $value;
			}
			else if($name == "unit")
			{
				$product_a['unit'] += $value;
				$product_a['unit'] = round($product_a['unit']);
			}
			else if($name == "geofence_unit")
			{
				$product_a['geofence_unit'] += $value;
				$product_a['geofence_unit'] = round($product_a['geofence_unit']);
			}
			else
			{
				$product_a[$name] += $value;
			}
		}
		return $product_a;
	}

	public function populate_products_with_submitted_data($product_data, $proposal_id)
	{
		$query = "
			SELECT
				*
			FROM
				cp_submitted_products
			WHERE
				proposal_id = ?";
		$response = $this->db->query($query, $proposal_id);
		if(!$response or $response->num_rows() < 1)
		{
			return false;
		}

		$num_laps = $this->get_num_laps_from_products_by_proposal_id($proposal_id);
		if($num_laps === false or $num_laps == 0)
		{
			return false;
		}

		$product_result = $response->result_array();

		$temp_product_data = $product_data;
		$has_any_matches = false; // if there are no matches anywhere, we've changed the strategy and all should be selected

		foreach($temp_product_data as &$product)
		{
			$found_match = false;
			$product['definition'] = json_decode($product['definition'], true);
			$temp_unit_array = array(0, 0, 0);
			foreach($product_result as $submitted_product)
			{
				if($product['id'] == $submitted_product['product_id'])
				{
					$found_match = true;
					$has_any_matches = true;
					$submitted_object = json_decode($submitted_product['submitted_values'], true);

					foreach($submitted_object as $name => $value)
					{
						if($name == "custom_name")
						{
							$product['definition']['last_name'] = $value;
						}
						else if($name == "cpm")
						{
							$product['definition']['options'][(int)$submitted_product['option_id']]["cpm"]['default'] = (int) $value;
						}
						else if($name == "unit")
						{
							$temp_unit_array[(int)$submitted_product['option_id']] += (int) $value;
						}
						else if($name == "cpc")
						{
							$product['definition']['options'][(int)$submitted_product['option_id']]['cpc']['custom_default'] = (int) $value;
						}
						else if($name == "content")
						{
							$product['definition']['content']['default'] = (int) $value;
						}
						else if($name == "inventory")
						{
							$product['definition']['inventory']['default'] = (int) $value;
						}
						else if($name == "raw_inventory")
						{
							$product['definition']['inventory']['raw_default'] = (int) $value;
						}
						else if($name == "price")
						{
							$product['definition']['options'][(int)$submitted_product['option_id']]['price']['default'] = (int) $value;
						}
						else if($name == "budget_allocation")
						{
							$product['definition']['allocation_method'] = $value;
						}
						else
						{
							$product['definition']['options'][(int)$submitted_product['option_id']][$name]['default'] = $value;
						}
					}

				}
			}
			$product['has_match'] = $found_match;
			if ($found_match)
			{
				foreach($temp_unit_array as $key => $unit)
				{
					if($unit > 0)
					{
						$value = $unit;
						if(array_key_exists('geo_dependent', $product['definition']) and $product['definition']['geo_dependent'] !== false)
						{
							$value = $unit/$num_laps;
						}
						if(array_key_exists('type', $product['definition']['options'][$key]))
						{
							if($product['definition']['options'][$key]['type']['default'] == "dollars")
							{
								$budget_multiplier = 1000;
								if(array_key_exists('unit_multiplier', $product['definition']['options'][$key]))
								{
									$budget_multiplier = $product['definition']['options'][$key]['unit_multiplier'];
								}
								$value = round($value * $product['definition']['options'][$key]['cpm']['default']/$budget_multiplier);
							}
						}
					}
					else
					{
						$value = 0;
					}

					$product['definition']['options'][$key]["unit"]['default'] = $value;

				}
			}
			$product['definition'] = json_encode($product['definition']);
		}
		return $has_any_matches ? $temp_product_data : $product_data;
	}

	public function get_num_laps_from_products_by_proposal_id($proposal_id)
	{
		$query = "
			SELECT
				COUNT(DISTINCT frq_prop_gen_lap_id) AS num_laps
			FROM
				cp_submitted_products
			WHERE
				proposal_id = ?";
		$response = $this->db->query($query, $proposal_id);
		if($response and $response->num_rows() > 0)
		{
			return $response->row()->num_laps;
		}
		return false;
	}

	public function get_iab_category_data_by_existing_proposal_id($prop_id)
	{
		$query = "
			SELECT
				iab.id,
				tag_copy as text
			FROM
				prop_gen_prop_data AS pg
				JOIN mpq_iab_categories_join AS mpq
					ON pg.source_mpq = mpq.mpq_id
				JOIN iab_categories AS iab
					ON mpq.iab_category_id = iab.id
			WHERE
				pg.prop_id = ?";
		$response = $this->db->query($query, $prop_id);
		if($response and $response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function get_select2_account_executive_data_for_rfp($term, $start, $limit, $user_id, $role, $allowed_roles = false)
	{
		$term = '%'.$term.'%';
		$bindings = array();

		$allowed_roles = $allowed_roles ? implode("','", $allowed_roles) : implode("','", array('SALES', 'ADMIN', 'OPS', 'CREATIVE'));

		if($role == 'admin' or $role == 'ops')
		{
			$query = "
				SELECT
					id,
					firstname,
					lastname,
					email,
					COALESCE(CONCAT(firstname, ' ', lastname), email) as text
				FROM
					users
				WHERE
					role IN ('".$allowed_roles."') AND
					banned = 0 AND
					(
						UPPER(CONCAT_WS(' ', firstname, lastname)) LIKE UPPER(?) OR
						UPPER(email) LIKE UPPER(?)
					)
				ORDER BY text ASC
				LIMIT ?, ?";
		}
		else if($role == 'sales')
		{
			$query = "
				SELECT DISTINCT
					ae_query.*
				FROM
				(
				SELECT
					us.id,
					us.firstname,
					us.lastname,
					us.email,
					COALESCE(CONCAT(us.firstname, ' ', us.lastname), us.email) as text
				FROM
					users ownr
					JOIN wl_partner_hierarchy ph
						ON ownr.partner_id = ph.ancestor_id
					JOIN wl_partner_details pd
						ON ph.descendant_id = pd.id
					JOIN users us
						ON pd.id = us.partner_id
				WHERE
					ownr.id = ? AND
					us.role IN ('".$allowed_roles."') AND
					us.banned = 0 AND
					(
						UPPER(CONCAT_WS(' ', us.firstname, us.lastname)) LIKE UPPER(?) OR
						UPPER(us.email) LIKE UPPER(?)
					)
				UNION
				SELECT
					us.id,
					us.firstname,
					us.lastname,
					us.email,
					COALESCE(CONCAT(us.firstname, ' ', us.lastname), us.email) as text
				FROM
					users ownr
					JOIN wl_partner_owners po
						ON ownr.id = po.user_id
					JOIN wl_partner_hierarchy ph
						ON po.partner_id = ph.ancestor_id
					JOIN wl_partner_details pd
						ON ph.descendant_id = pd.id
					JOIN users us
						ON pd.id = us.partner_id
				WHERE
					ownr.id = ? AND
					us.role IN ('SALES') AND
					us.banned = 0 AND
					(
						UPPER(CONCAT_WS(' ', us.firstname, us.lastname)) LIKE UPPER(?) OR
						UPPER(us.email) LIKE UPPER(?)
					)
				) AS ae_query
				ORDER BY ae_query.text ASC
				LIMIT ?, ?";
			$bindings[] = $user_id;
			$bindings[] = $term;
			$bindings[] = $term;
			$bindings[] = $user_id;
		}
		$bindings[] = $term;
		$bindings[] = $term;
		$bindings[] = $start;
		$bindings[] = $limit;
		$response = $this->db->query($query, $bindings);
		if($response and $response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function get_summary_html_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				original_submission_summary_html
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response && $response->num_rows() > 0)
		{
			return $response->row()->original_submission_summary_html;
		}
		return false;
	}

	public function get_account_executive_data_by_user_id($user_id)
	{
		$query = "
			SELECT
				id,
				COALESCE(CONCAT(firstname, ' ', lastname), email) as text,
				email
			FROM
				users
			WHERE
				id = ?";
		$response = $this->db->query($query, $user_id);
		if($response and $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function compare_partner_products_by_user_ids($new_user, $current_user)
	{
		$return_array = array('is_success' => true, 'is_compare_success' => true);

		$new_user_response = $this->get_product_ids_by_user_id($new_user);
		$current_user_response = $this->get_product_ids_by_user_id($current_user);

		if($new_user_response !== false and $current_user_response !== false)
		{
			if($new_user_response !== $current_user_response)
			{
				$return_array['is_compare_success'] = false;
			}
		}
		else
		{
			$return_array['is_success'] = false;
		}

		return $return_array;

	}

	public function get_product_ids_by_user_id($user_id)
	{
		$query = "
			SELECT
				pr.id
			FROM
				cp_products AS pr
				JOIN cp_partners_join_products AS pj
					ON pj.product_id = pr.id
				JOIN (
					SELECT
						ph.ancestor_id AS id,
						MIN(ph.path_length) AS path_length
					FROM
						users AS usr
						JOIN wl_partner_hierarchy AS ph
							ON usr.partner_id = ph.descendant_id
						JOIN cp_partners_join_products AS pj
							ON ph.ancestor_id = pj.partner_id
					WHERE
						usr.id = ?
					GROUP BY
						ph.ancestor_id
					ORDER BY
						path_length ASC
					LIMIT 1
				) AS pq
					ON pj.partner_id = pq.id
			ORDER BY
			    pr.id";
		$result = $this->db->query($query, $user_id);
		if($result !== false && $result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}

	function get_rfp_preload_custom_regions_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				COALESCE(rc.id, cg.id) AS id,
				COALESCE(rc.name, cg.geo_name) AS geo_name,
				cj.location_id
			FROM
				mpq_custom_geos_join AS cj
				LEFT OUTER JOIN	geo_regions_collection rc
					ON rc.id = cj.geo_regions_collection_id
				LEFT OUTER JOIN custom_geos AS cg
					ON cg.id = cj.custom_geos_id
			WHERE
				cj.mpq_id = ? AND
				cj.location_id IS NOT NULL";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_location_populations_from_db($array_of_region_data_ids)
	{
		$region_ids = [];
		$zcta_bindings = [-1];
		$fsa_bindings = [-1];
		array_walk($array_of_region_data_ids, function($values, $keys) use (&$region_ids){
			$region_ids = array_merge($region_ids, $values['zcta']);
		});
		array_walk($region_ids, function(&$id) use (&$zcta_bindings, &$fsa_bindings){
			if(is_numeric($id))
			{
				$zcta_bindings[] = intval($id);
			}
			else if(is_string($id) && strlen((string)$fsa) == 3)
			{
				$proper_fsa = strtoupper($fsa);
				$fsa_bindings = (int)(ord(substr($proper_fsa, 0, 1)) . substr($proper_fsa, 1, 1) . ord(substr($proper_fsa, 2, 1)));
			}
		});

		$zcta_insert_string = implode(',', array_fill(0, count($zcta_bindings), '?'));
		$fsa_insert_string = implode(',', array_fill(0, count($fsa_bindings), '?'));
		$query = "
			SELECT
				gzm.num_id AS local_id,
				gcd.population_total
			FROM
				geo_cumulative_demographics AS gcd
			LEFT JOIN
				geo_zcta_map AS gzm
				ON gzm.gcd_id = gcd.id
			WHERE
				gzm.num_id IN ({$zcta_insert_string})

			UNION ALL

			SELECT
				gfm.CFSAUID AS local_id,
				gcd.population_total
			FROM
				geo_cumulative_demographics AS gcd
			LEFT JOIN
				geo_fsa_map AS gfm
				ON gfm.gcd_id = gcd.id
			WHERE
				gfm.numeric_fsa IN ({$fsa_insert_string});";
		$response = $this->db->query($query, array_merge($zcta_bindings, $fsa_bindings));
		$populations_by_location = [];
		if($response->num_rows() > 0)
		{
			$result_array = $response->result_array();
			$region_ids_and_populations = array_combine(array_column($result_array, 'local_id'), array_map('intval', array_column($result_array, 'population_total')));
			foreach($array_of_region_data_ids as $location_id => $ids)
			{
				$ids_in_location = array_flip($ids['zcta']);
				$populations_to_sum = array_intersect_ukey($region_ids_and_populations, $ids_in_location, function($region_id_from_db, $region_id_from_region_data){
					return ($region_id_from_db == $region_id_from_region_data);
				});
				$populations_by_location[$location_id] = array_sum($populations_to_sum);
			}
		}
		return $populations_by_location;
	}

	function get_mpq_options_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				*
			FROM
				mpq_options
			WHERE
				mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
			return array();
	}

	function does_rfp_have_no_children($mpq_id)
	{
		$query = "
			SELECT
				id
			FROM
				mpq_sessions_and_submissions
			WHERE
				parent_mpq_id = ? AND
				mpq_type IS NULL AND
				is_submitted = 1";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			return false;
		}
		return true;
	}

	function get_submitted_products_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				*
			FROM
				cp_submitted_products
			WHERE
				mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	function get_display_submitted_product_for_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				csp.*
			FROM
				cp_submitted_products AS csp
				JOIN cp_products AS cp
					ON (csp.product_id = cp.id)
			WHERE
				csp.mpq_id = ?
				AND cp.product_identifier = \"display\"";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	function get_products_by_product_id_array($product_ids)
	{
		if(count($product_ids) > 0)
		{
			$binding_sql = "";
			foreach($product_ids as $product)
			{
				if($binding_sql !== "")
				{
					$binding_sql .= ", ";
				}
				$binding_sql .= "?";
			}
			$query = "
				SELECT DISTINCT
					*
				FROM
					cp_products
				WHERE
					id IN (".$binding_sql.")";
			$response = $this->db->query($query, $product_ids);
			if($response->num_rows() > 0)
			{
				return $response->result_array();
			}
		}
		return false;
	}

	public function get_product_data_for_submitted_product($submitted_product_id)
	{
		$get_product_data_query =
		"SELECT
			cp.*
		FROM
			cp_submitted_products AS csp
			JOIN cp_products AS cp
				ON (csp.product_id = cp.id)
		WHERE
			csp.id = ?";
		$get_product_data_result = $this->db->query($get_product_data_query, $submitted_product_id);
		if($get_product_data_result == false || $get_product_data_result->num_rows() < 1)
		{
			return false;
		}
		return $get_product_data_result->row_array();
	}

	public function get_submitted_product_data_for_id($submitted_product_id)
	{
		$get_submitted_product_details_query = 
		"SELECT
			csp.*
		FROM
			cp_submitted_products AS csp
		WHERE
			csp.id = ?
		";

		$get_submitted_product_details_result = $this->db->query($get_submitted_product_details_query, $submitted_product_id);
		if($get_submitted_product_details_result == false)
		{
			return false;
		}
		return $get_submitted_product_details_result->row_array();
	}

	public function save_submitted_io($raw_demographics, $iab_category_ids, $advertiser, $submitter, $session_id, $user_id, $industry_id, $io_status, $submission_type, $tracking, $status_ok, $mpq_summary)
	{
		$demographics = $this->convert_demo_category_if_all_unset($raw_demographics);

		if (!empty($mpq_summary))
		{
			if ($mpq_summary['mpq_type'] === 'io-submitted' || $mpq_summary['mpq_type'] === 'io-in-review')
			{
				$mpq_type_sql = $mpq_summary['mpq_type'];
			}

			$mpq_id = $mpq_summary['id'];
			if(count($iab_category_ids) > 0)
			{
				$iab_values_sql = "";
				$iab_binding_array = array();
				foreach($iab_category_ids as $iab_category)
				{
					if($iab_values_sql !== "")
					{
						$iab_values_sql .= ",";
					}
					else
					{
						$iab_values_sql .= "VALUES ";
					}
					$iab_values_sql .= "(?, ?)";
					$binding_array[] = $mpq_id;
					$binding_array[] = $iab_category;
				}

				$iab_delete_query = "DELETE FROM mpq_iab_categories_join WHERE mpq_id = ?";
				$iab_delete_response = $this->db->query($iab_delete_query, $mpq_id);

				$iab_query = '
					INSERT INTO
						mpq_iab_categories_join
						(mpq_id, iab_category_id)
						'.$iab_values_sql;
				$iab_result = $this->db->query($iab_query, $binding_array);
			}
		}
		if ($mpq_id)
		{
			$status_response = $this->save_io_status_values($io_status, $mpq_id);
		}

		$date_time = date("Y-m-d H:i:s");
		$owner_user_id = null;
		if(isset($submitter->id))
		{
			$owner_user_id = $submitter->id;
		}

		$bindings = array(
			$demographics,
			$advertiser->source_table,
			$advertiser->advertiser_name,
			$advertiser->advertiser_id,
			$advertiser->website_url,
			$advertiser->order_name,
			$advertiser->order_id,
			$submitter->name,
			$submitter->role,
			$submitter->website,
			$submitter->email_address,
			$submitter->phone_number,
			$submitter->notes,
			$industry_id,
			$owner_user_id,
			$tracking->include_retargeting,
			$tracking->tracking_tag_file_id,
			$mpq_id
			);

		$session_sql = "";
		$io_lock_sql = "";
		if ($submission_type == 'submit')
		{
			$mpq_type_sql = 'io-submitted';
			$session_sql = " session_id = NULL,";
			$io_lock_sql = " io_lock_timestamp = NULL,";
		}
		elseif($submission_type == 'submit_for_review')
		{
			$mpq_type_sql = 'io-in-review';
		}
		elseif(!isset($mpq_type_sql) || !$status_ok)
		{
			$mpq_type_sql = 'io-saved';
		}
		$role = $this->tank_auth->get_role($this->tank_auth->get_username());
		if ($role == 'admin' || $role == 'ops')
		{
			$query= '
				SELECT
					mpq_type
				FROM
					mpq_sessions_and_submissions
				WHERE
					id = ?';
			$mpq_type_check = $this->db->query($query, $mpq_id);
			$mpq_type_check = $mpq_type_check->result_array();
			if($mpq_type_check[0]['mpq_type'] == 'io-submitted')
			{
				$mpq_type_sql = 'io-submitted';
			}

		}

		$session_query = '
			UPDATE
				mpq_sessions_and_submissions
			SET
				'.$session_sql.'
				is_submitted = 1,
				demographic_data = ?,
				selected_iab_categories = NULL,
				source_table = ?,
				advertiser_name = ?,
				io_advertiser_id = ?,
				advertiser_website = ?,
				order_name = ?,
				order_id = ?,
				submitter_name = ?,
				submitter_relationship = ?,
				submitter_agency_website = ?,
				submitter_email = ?,
				submitter_phone = ?,
				notes = ?,
				industry_id = ?,
				owner_user_id = ?,
				mpq_type = "'.$mpq_type_sql.'",
				'.$io_lock_sql.'
				include_retargeting = ?,
				tracking_tag_file_id = ?
			WHERE id = ?';

		$session_response = $this->db->query($session_query, $bindings);
		return $session_response;
	}


	//option_id of false means its preload from io instead of rfp
	public function initialize_mpq_session_from_existing_for_io($session_id, $mpq_id, $unique_display_id, $option_id = false)
	{
		$date_time_created = date("Y-m-d H:i:s");
		$unique_display_id = $this->generate_insertion_order_unique_id();

		$bindings = array();
		$bindings[] = $session_id;
		$bindings[] = $date_time_created;
		$bindings[] = $unique_display_id;
		$bindings[] = ($option_id != false ? $option_id : null);
		$bindings[] = $mpq_id;

		$query = "
			INSERT INTO mpq_sessions_and_submissions (
				session_id,
				creation_time,
				creator_user_id,
				region_data,
				strategy_id,
				demographic_data,
				selected_iab_categories,
				advertiser_name,
				advertiser_website,
				submitter_name,
				submitter_relationship,
				submitter_agency_website,
				submitter_email,
				submitter_phone,
				selected_channels,
				notes,
				snapshot_title,
				snapshot_data,
				mailgun_error_text,
				industry_id,
				budget_allocation_method_type,
				original_submission_summary_html,
				original_owner_id,
				parent_mpq_id,
				mpq_type,
				unique_display_id,
				parent_proposal_option_index

			)
			SELECT
				?,
				?,
				mss.owner_user_id,
				mss.region_data,
				mss.strategy_id,
				mss.demographic_data,
				mss.selected_iab_categories,
				mss.advertiser_name,
				mss.advertiser_website,
				mss.submitter_name,
				mss.submitter_relationship,
				mss.submitter_agency_website,
				mss.submitter_email,
				mss.submitter_phone,
				mss.selected_channels,
				mss.notes,
				mss.snapshot_title,
				mss.snapshot_data,
				mss.mailgun_error_text,
				mss.industry_id,
				mss.budget_allocation_method_type,
				mss.original_submission_summary_html,
				mss.owner_user_id,
				mss.id,
				'io-draft',
				?,
				?
			FROM
				mpq_sessions_and_submissions mss
			WHERE
				mss.id = ?
			LIMIT 1";

		$result = $this->db->query($query, $bindings);
		if($result !== false)
		{
			$new_mpq_id = $this->db->insert_id();
			$this->copy_geofences_from_original_mpq($new_mpq_id, $mpq_id);
			$product_response = $this->initialize_products_for_io($mpq_id, $new_mpq_id, $option_id);
			$option_response = $this->initialize_option_data_for_io($mpq_id, $new_mpq_id, $option_id);
			//option_id can't be false right now.  Untested superflow 3.2 - Will
			if($option_id === false)
			{
				//$flight_creative_response = $this->initialize_flight_and_creative_data_for_io($mpq_id, $new_mpq_id);
				//$status_response = $this->initialize_io_status_for_io($mpq_id, $new_mpq_id);
			}
			else
			{
				$flight_creative_response = true;
				$status_response = true;
			}

			if($product_response && $option_response && $flight_creative_response && $status_response)
			{

				return $new_mpq_id;
			}
		}
		return false;
	}

	public function initialize_mpq_session_from_scratch_for_io($session_id, $owner_id, $partner_id)
	{
		$unique_id = $this->generate_insertion_order_unique_id();
		$date_time_created = date("Y-m-d H:i:s");

		$query = "
			INSERT INTO mpq_sessions_and_submissions (
				session_id,
				strategy_id,
				creation_time,
				creator_user_id,
				region_data,
				demographic_data,
				submitter_name,
				submitter_email,
				submitter_phone,
				original_owner_id,
				mpq_type,
				unique_display_id
			)
			SELECT
				?,
				strategy.id,
				?,
				u.id,
				'[]',
				'0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_0_75_All_unusedString',
				CONCAT(u.firstname, ' ', u.lastname),
				u.email,
				u.phone_number,
				u.id,
				'io-draft',
				?
			FROM
				users u
			JOIN (
				SELECT cpj.cp_strategies_id AS id
				FROM cp_strategies_join_wl_partner_details cpj
				JOIN cp_strategies cps
					ON cps.id = cpj.cp_strategies_id
				WHERE cpj.wl_partner_details_id = ?
				ORDER BY cps.display_order ASC
				LIMIT 1
			) as strategy
			WHERE
				u.id = ?
			LIMIT 1";

		$result = $this->db->query($query, array($session_id, $date_time_created, $unique_id, $partner_id, $owner_id));
		if ($result)
		{
			$mpq_id = $this->db->insert_id();
			$products = $this->initialize_default_products_for_io($mpq_id, $partner_id);
			if ($products)
			{
				return $mpq_id;
			}
		}
		return false;
	}

	//option_id of false means preload from io instead of rfp
	public function initialize_products_for_io($source_id, $new_id, $option_id = false)
	{
		$sp_bindings = array($new_id, $source_id);
		$option_sql = "";
		if($option_id !== false)
		{
			$option_sql = "AND option_id = ?";
			$sp_bindings[] = $option_id;
		}

		$sp_query = "
			INSERT INTO cp_submitted_products (
				mpq_id,
				proposal_id,
				option_id,
				product_id,
				frq_prop_gen_lap_id,
				submitted_values,
				region_data_index
			)
			SELECT
				?,
				NULL,
				0,
				cpsp.product_id,
				NULL,
				cpsp.submitted_values,
	 			cpsp.region_data_index
			FROM
				cp_submitted_products cpsp
				JOIN cp_products cpp
					ON cpsp.product_id = cpp.id
			WHERE
				cpp.can_become_campaign = 1 AND
				mpq_id = ?
				".$option_sql."";

		$sp_response = $this->db->query($sp_query, $sp_bindings);

		$select_query = "
			SELECT DISTINCT
				cpp.id,
				csp.submitted_values
			FROM
				cp_submitted_products csp
				JOIN cp_products cpp
					ON csp.product_id = cpp.id
			WHERE
				csp.mpq_id = ? AND
				cpp.can_become_campaign = 1";
	   $select_response = $this->db->query($select_query, $new_id);
	   if($select_response->num_rows() > 0 && $sp_response)
	   {
		   $product_array = array();
		   foreach($select_response->result_array() as $value)
		   {
			   $values_obj = json_decode($value['submitted_values'], true);
			   $budget_allocation = array_key_exists('budget_allocation', $values_obj) ? $values_obj['budget_allocation'] : 'per_pop';
			   if ($budget_allocation === 'fixed'){
			   	$budget_allocation = 'even';
			   }
			   if(array_key_exists($value['id'], $product_array))
			   {
				   $product_array[$value['id']]['impressions'] += $values_obj['unit'];
				   $product_array[$value['id']]['count']++;
				   $product_array[$value['id']]['budget_allocation'] = $budget_allocation;
			   }
			   else
			   {
				   $product_array[$value['id']] = array('impressions' => $values_obj['unit'], 'count' => 1, 'budget_allocation' => $budget_allocation);
			   }
		   }

		   $product_binding_array = array();
		   $values_sql = "";
		   foreach($product_array as $product_id => $value)
		   {
			   $impressions = $value['impressions']/$value['count'];
			   $product_binding_array[] = $new_id;
			   $product_binding_array[] = $product_id;
			   $product_binding_array[] = round($impressions);
			   $product_binding_array[] = $value['budget_allocation'];
			   if($values_sql !== "")
			   {
				   $values_sql .= ", ";
			   }
			   $values_sql .= "(?, ?, ?, ?)";
		   }
		   $pj_query = "
				INSERT INTO cp_products_join_io (
					mpq_id,
					product_id,
					impressions_per_location,
					io_budget_allocation_type
				)
				VALUES
					".$values_sql;
		   $pj_response = $this->db->query($pj_query, $product_binding_array);
		   if($pj_response)
		   {
			   return true;
		   }
		}
		return false;
	}

	public function initialize_default_products_for_io($mpq_id, $partner_id = null, $product_id = null)
	{
		if ($product_id)
		{
			$products = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
		}
		elseif ($partner_id)
		{
			$products = $this->mpq_v2_model->get_products_by_partner($partner_id);
		}
		else
		{
			return false;
		}

		$sp_bindings = array();
		foreach($products as $product)
		{
			if ($product['can_become_campaign'] && !$product['is_political'])
			{
				$impressions = 0;
				if ($product['type'] = 'cost_per_unit')
				{
					$definition = json_decode($product['definition'], true);
					$option = $definition['options'][1];
					$unit_multiplier = 1000;
					if(array_key_exists('unit_multiplier', $option))
					{
						$unit_multiplier = $option['unit_multiplier'];
					}
					$impressions = $option['unit']['default'] * $unit_multiplier / $option['cpm']['default']; // calculate impressions from CPM dollar value
				}

				$sp_bindings[] = "($mpq_id, NULL, 0, {$product['id']}, NULL, '{\"unit\": $impressions}', 0)";
			}
		}
		$sp_bindings = implode(",", $sp_bindings);

		$sp_query =
		'	INSERT INTO cp_submitted_products (
				mpq_id,
				proposal_id,
				option_id,
				product_id,
				frq_prop_gen_lap_id,
				submitted_values,
				region_data_index
			) VALUES '.$sp_bindings;
		$sp_response = $this->db->query($sp_query);

		$select_query = "
			SELECT DISTINCT
				cpp.id,
				csp.submitted_values
			FROM
				cp_submitted_products csp
				JOIN cp_products cpp
					ON csp.product_id = cpp.id
			WHERE
				csp.mpq_id = ? AND
				cpp.can_become_campaign = 1";

		$select_bindings = array($mpq_id);
		if ($product_id)
		{
			$select_query .= " AND csp.product_id = ?";
			$select_bindings[] = $product_id;
		}

		$select_response = $this->db->query($select_query, $select_bindings);

		if ($select_response && $select_response->num_rows() > 0)
		{
			$product_array = array();
			foreach($select_response->result_array() as $value)
			{
				$values_obj = json_decode($value['submitted_values'], true);
				if(array_key_exists($value['id'], $product_array))
				{
					$product_array[$value['id']]['impressions'] += $values_obj['unit'];
					$product_array[$value['id']]['count']++;
				}
				else
				{
					$product_array[$value['id']] = array('impressions' => $values_obj['unit'], 'count' => 1);
				}
			}

			$product_binding_array = array();
			$values_sql = "";
			foreach($product_array as $product_id => $value)
			{
				$impressions = $value['impressions']/$value['count'];
				$product_binding_array[] = $mpq_id;
				$product_binding_array[] = $product_id;
				$product_binding_array[] = round($impressions);
				if($values_sql !== "")
				{
					$values_sql .= ", ";
				}
				$values_sql .= "(?, ?, ?)";
			}

			$pj_query = "
				INSERT INTO cp_products_join_io (
					mpq_id,
					product_id,
					impressions_per_location
				)
				VALUES
					".$values_sql;
			$pj_response = $this->db->query($pj_query, $product_binding_array);
			if($pj_response)
			{
				return true;
			}
		}
		return false;
	}

	public function link_submitted_products_with_io($mpq_id, $product_id)
	{
		$sql =
		'	INSERT INTO cp_products_join_io
			(mpq_id, product_id, impressions_per_location)
			VALUES (?, ?, 0)';
		$this->db->query($sql, array($mpq_id, $product_id));
	}

	public function remove_product_from_io($mpq_id, $product_id)
	{
		$sql =
		'	DELETE FROM cp_products_join_io
			WHERE mpq_id = ?
			AND product_id = ?
		';
		return $this->db->query($sql, array($mpq_id, $product_id));
	}

	public function initialize_option_data_for_io($source_id, $new_id, $option_id = false)
	{
		$delete_query = "DELETE FROM mpq_options WHERE mpq_id = ?";
		$delete_response = $this->db->query($delete_query, $new_id);

		$bindings = array($new_id, $source_id);
		$option_sql = "";
		if($option_id !== false)
		{
		    $option_sql = "AND option_id = ?";
			$bindings[] = $option_id;
		}

		$query = "
			INSERT INTO mpq_options (
				mpq_id,
				option_id,
				option_name,
				discount,
				term,
				duration,
				discount_name,
				grand_total_dollars
			)
			SELECT
				?,
				option_id,
				option_name,
				discount,
				term,
				duration,
				discount_name,
				grand_total_dollars
			FROM
				mpq_options
			WHERE
				mpq_id = ?
				".$option_sql."";
		$response = $this->db->query($query, $bindings);
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	/*
	 * This function is never called superflow 3.2 - Will
	public function get_product_data_by_mpq_id_for_io($mpq_id)
	{
		$return_array = array();
		$query = "
			SELECT DISTINCT
				cpp.id AS id,
				cpp.product_type AS product_type,
				cpp.definition AS definition,
				cpp.can_become_campaign AS can_become_campaign,
				mpo.term AS term,
				mpo.duration AS duration,
				cps.submitted_values AS submitted_values
			FROM
				cp_submitted_products cps
				JOIN cp_products cpp
					ON cps.product_id = cpp.id
				JOIN mpq_options mpo
					ON cps.mpq_id = mpo.mpq_id
			WHERE
				cps.mpq_id = ?
			ORDER BY
				cpp.display_order ASC";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			foreach($response->result_array() as $result)
			{
				$definition = json_decode($result['definition'], true);
				if($result['product_type'] !== 'discount' and (!array_key_exists('after_discount', $definition) or $definition['after_discount'] == false))
				{
					if(!array_key_exists($result['id'], $return_array))
					{
						$return_array[$result['id']] = array(
							'id' => $result['id'],
							'product_type' => $result['product_type'],
							'img' => $definition['product_enabled_img'],
							'name' => ($definition['first_name'] !== false ? $definition['first_name']." ".$definition['last_name'] : $definition['last_name']),
							'term' => $result['term'],
							'duration' => $result['duration'],
							'impressions' => 0,
							'can_become_campaign' => $result['can_become_campaign']
							);
					}
					$submitted_value = json_decode($result['submitted_values'], true);
					if($result['product_type'] == "cost_per_unit")
					{
						$return_array[$result['id']]['impressions'] += $submitted_value['unit'];
					}
					else
					{
						//placeholder for other products
					}

				}
			}
		}
		return $return_array;
	}
	*/

	public function get_product_information_for_io($mpq_id)
	{
		$return_array = array();
		$query = "
			SELECT DISTINCT
				cpp.id AS id,
				cpp.product_type AS product_type,
				cpp.definition AS definition,
				cpp.banner_intake_id AS banner_intake_id,
				cpp.can_become_campaign AS can_become_campaign,
				cpp.is_political AS is_political,
				mpo.term AS term,
				mpo.duration AS duration,
				cpj.impressions_per_location,
				cpp.o_o_enabled AS o_o_enabled,
				cpp.o_o_min_ratio AS o_o_min_ratio,
				cpp.o_o_max_ratio AS o_o_max_ratio,
				cpp.o_o_default_ratio AS o_o_default_ratio,
				cpp.o_o_dfp AS o_o_dfp
			FROM
				cp_products_join_io as cpj
				JOIN cp_products cpp
					ON cpj.product_id = cpp.id
				LEFT JOIN mpq_options mpo
					ON cpj.mpq_id = mpo.mpq_id
			WHERE
				cpj.mpq_id = ?
			ORDER BY
				cpp.display_order ASC";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			$return_array = $this->format_product_data_for_io($response->result_array());
		}
		return $return_array;
	}

	public function format_product_data_for_io($products)
	{
		$return_array = array();
		foreach($products as $product)
		{
			$definition = json_decode($product['definition'], true);
			if($product['can_become_campaign'] == 1 and $product['product_type'] !== 'discount' and (!array_key_exists('after_discount', $definition) or $definition['after_discount'] == false))
			{
				if(!array_key_exists($product['id'], $return_array))
				{
					$return_array[$product['id']] = array(
						'id' => $product['id'],
						'product_type' => $product['product_type'],
						'img' => $definition['product_enabled_img'],
						'name' => ($definition['first_name'] !== false ? $definition['first_name']." ".$definition['last_name'] : $definition['last_name']),
						'banner_intake_id' => $product['banner_intake_id'],
						'term' => array_key_exists('term', $product) ? $product['term'] : null,
						'duration' => array_key_exists('duration', $product) ? $product['duration'] : null,
						'impressions' => array_key_exists('impressions_per_location', $product) ? $product['impressions_per_location'] : null,
						'can_become_campaign' => $product['can_become_campaign'],
						'is_political' => $product['is_political'],
						'o_o_enabled' => $product['o_o_enabled'],
						'o_o_min_ratio' => $product['o_o_min_ratio'],
						'o_o_max_ratio' => $product['o_o_max_ratio'],
						'o_o_default_ratio' => $product['o_o_default_ratio'],
						'o_o_dfp' => $product['o_o_dfp'],
						'has_geofencing' => array_key_exists('geofencing', $definition),
						'geofencing_data' => (array_key_exists('geofencing', $definition) ? $definition['geofencing'] : []),
					);
				}
			}
		}
		return $return_array;
	}

	public function delete_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id)
	{
		$query = "
			DELETE FROM
				cp_submitted_products
			WHERE
				mpq_id = ? AND
				product_id = ?";
		$response = $this->db->query($query, array($mpq_id, $product_id));
		return $response;
	}

	public function get_select2_adset_versions_for_user_io($term, $page, $page_limit, $user_id, $product_id, $show_all_versions = false)
	{
		// check if this user id is a super user and if the type is sales
		// if yes, fetch all the  users that user has access to and pass the list in the query below

		$product_query = "
			SELECT
				banner_intake_id
			FROM
				cp_products
			WHERE
				id = ?";
		$product_query = $this->db->query($product_query, $product_id);
		if($product_query->num_rows() > 0)
		{
			$banner_intake_product = $product_query->row()->banner_intake_id;
			if($banner_intake_product === null)
			{
				return array();
			}
		}
		else
		{
			return array();
		}

	    $creative_name_sql = "adr.creative_name LIKE ?";
		$adset_name_sql = "cpa.name LIKE ?";
		$version_encoded_sql = "cpv.base_64_encoded_id LIKE ?";
		$vanity_string_sql = "cpv.vanity_string LIKE ?";

		if($term == '%')
		{
			$creative_name_binding = array($term);
			$adset_name_binding = array($term);
			$version_id_binding = array($term);
			$vanity_string_binding = array($term);
		}
		else
		{
			$term = trim($term, '%');
			$creative_name_binding = array();
			$adset_name_binding = array();
			$version_id_binding = array();
			$vanity_string_binding = array();

			$url_explode = explode('/get_adset/', $term);
			if(count($url_explode) > 1) //term is in the form of a version url
			{
				$term = $url_explode[1];
			}
			$term_explode_array = explode('/', $term);

			if(count($term_explode_array) > 1) //there are multiple parts to the url (encoded_id and vanity string)
			{
				$first_flag = true;
				foreach($term_explode_array as $value)
				{
					if($value !== "crtv" and $value !== "get_adset" and $value !== "")
					{
						if($first_flag === false)
						{
							$creative_name_sql .= " OR adr.creative_name LIKE ?";
							$adset_name_sql .= " OR cpa.name LIKE ?";
							$version_encoded_sql .= " OR cpv.base_64_encoded_id LIKE ?";
							$vanity_string_sql .= " OR cpv.vanity_string LIKE ?";
						}
						else
						{
							$first_flag = false;
						}
						$value = '%'.$value.'%';
						$creative_name_binding[] = $value;
						$adset_name_binding[] = $value;
						$version_id_binding[] = $value;
						$vanity_string_binding[] = $value;
					}
				}
			}
			else //a normal string or a partial of the url
			{
				$term = '%'.$term.'%';
				$creative_name_binding = array($term);
				$adset_name_binding = array($term);
				$version_id_binding = array($term);
				$vanity_string_binding = array($term);
			}

		}

		if(count($creative_name_binding) == 0)
		{
			$creative_name_binding = array('%');
			$adset_name_binding = array('%');
			$version_id_binding = array('%');
			$vanity_string_binding = array('%');
		}

		$select2_binding_array = array_merge($creative_name_binding, $adset_name_binding, $version_id_binding, $vanity_string_binding);

		$query = "
			SELECT
				role,
				isGroupSuper
			FROM
				users
			WHERE
				id = ?";
		$response = $this->db->query($query, $user_id);
		$sub_sql = "";
		if($response->num_rows() > 0)
		{
			foreach($response->result_array() as $result)
			{
				$is_super = $result['isGroupSuper'];
				if ($result['role'] == 'SALES' || $result['role'] == 'sales')
				{
					$users_array_returned = $this->tank_auth->get_sales_users_by_partner_hierarchy($user_id, $is_super);
					$sub_sql = "
						AND adr.creative_request_owner_id in
						(";
					foreach($users_array_returned as $row)
					{
						$sub_sql .= $row['id'] . ", ";
					}
					$sub_sql .= "-1)";
				}
			}
		}

		$body_sql = "
			SELECT
				CONCAT('1;', cpv.id) AS id,
				cpv.show_for_io AS show_for_io,
				adr.creative_name AS text,
				adr.advertiser_website AS website,
				adr.updated AS time_created,
				adr.landing_page AS landing_page,
				adr.advertiser_name AS advertiser_name,
				adr.creative_request_owner_id AS request_owner_id,
				caa.open_uri AS normal_thumb,
				caa.ssl_uri AS ssl_thumb,
				cpv.version AS version,
				cpv.base_64_encoded_id AS version_encoded_id,
				cpa.name AS adset_name,
				cpv.updated_timestamp AS updated_timestamp,
				cpv.adset_id AS adset_id,
				cpv.variation_name AS variation_name,
				COALESCE(adr.request_type, '--') AS request_type
			FROM
				adset_requests AS adr
				JOIN cup_adsets AS cpa
					ON adr.adset_id = cpa.id
				JOIN cup_versions AS cpv
					ON cpa.id = cpv.adset_id
				LEFT JOIN cup_creatives cpc
					ON cpv.id = cpc.version_id AND cpc.size = '300x250'
				LEFT JOIN cup_ad_assets AS caa
					ON cpc.id = caa.creative_id AND caa.type = 'backup'
				JOIN (
					SELECT
						MAX(version) AS version,
						adset_id
					FROM
						cup_versions
					GROUP BY
						adset_id
					) AS max_version
					ON (cpv.adset_id = max_version.adset_id AND cpv.version = max_version.version)
			WHERE
			  	cpv.show_for_io = 1 AND
				(
			  	    ".$creative_name_sql." OR
					".$adset_name_sql." OR
				    ".$version_encoded_sql." OR
					".$vanity_string_sql."
				) AND
				adr.product LIKE ? AND
			  	cpv.version IS NOT NULL
			  	$sub_sql
			UNION
			SELECT
				CONCAT('0;', adr.id) AS id,
				cpv.show_for_io AS show_for_io,
				adr.creative_name AS text,
				adr.advertiser_website AS website,
				adr.updated AS time_created,
				adr.landing_page AS landing_page,
				adr.advertiser_name AS advertiser_name,
				adr.creative_request_owner_id AS request_owner_id,
				caa.open_uri AS normal_thumb,
				caa.ssl_uri AS ssl_thumb,
				cpv.version AS version,
				cpv.base_64_encoded_id AS version_encoded_id,
				cpa.name AS adset_name,
				cpv.updated_timestamp AS updated_timestamp,
				cpv.adset_id AS adset_id,
				cpv.variation_name AS variation_name,
				COALESCE(adr.request_type, '--') AS request_type
			FROM
				adset_requests AS adr
				JOIN cup_adsets AS cpa
					ON adr.adset_id = cpa.id
				JOIN cup_versions AS cpv
					ON cpa.id = cpv.adset_id
				LEFT JOIN cup_creatives cpc
					ON cpv.id = cpc.version_id AND cpc.size = '300x250'
				LEFT JOIN cup_ad_assets AS caa
					ON cpc.id = caa.creative_id AND caa.type = 'backup'
				JOIN (
					SELECT
						MAX(version) AS version,
						adset_id
					FROM
						cup_versions
					GROUP BY
						adset_id
					) AS max_version
					ON (cpv.adset_id = max_version.adset_id AND cpv.version = max_version.version)
			WHERE
				(cpa.id IS NULL
				OR cpv.id IS NULL
				OR cpv.show_for_io = 0)
				AND
				(
			  	    ".$creative_name_sql." OR
					".$adset_name_sql." OR
				    ".$version_encoded_sql." OR
					".$vanity_string_sql."
				) AND
				adr.product LIKE ?
			  	$sub_sql";

		if($show_all_versions === false)
		{
			$query = "
				SELECT
					*
				FROM
				(
					".$body_sql."
					ORDER BY
						adset_id,
						version DESC
				) body_query
				ORDER BY
					body_query.show_for_io DESC,
					body_query.updated_timestamp DESC
				LIMIT ?, ?";
		}
		else
		{
			$query = "
			    ".$body_sql."
				ORDER BY
				show_for_io DESC,
				updated_timestamp DESC
				LIMIT ?, ?";
		}


		$select2_binding_array[] = $banner_intake_product;
		$select2_binding_array = array_merge($select2_binding_array, $select2_binding_array);
		$select2_binding_array[] = $page;
		$select2_binding_array[] = $page_limit;
		$response = $this->db->query($query, $select2_binding_array);
		if($response !== false and $response->num_rows() > 0)
		{
		    $result = $response->result_array();
			for($i = 0; $i < count($result); $i++)
			{
				if($result[$i]['version_encoded_id'] !== null)
				{
					$encoded_version_id = $result[$i]['version_encoded_id'];
				}
				else
				{
					$encoded_version_id = base64_encode(base64_encode(base64_encode($result[$i]['id'])));
				}
				$cname_to_use = $this->cup_model->get_version_banner_intake_cname($result[$i]['id']);
				if($cname_to_use == false)
				{
					$cname_to_use = $this->vl_auth_model->get_cname_for_user($this->ci->tank_auth->get_user_id());
				}
				$protocol = ENABLE_HOOKS ? 'https' : 'http';
				$domain = '.'.$this->tank_auth->get_domain_without_cname();
				$result[$i]['gallery_link'] = $protocol.'://'.$cname_to_use.$domain.'/crtv/get_adset/'.$encoded_version_id . '/' . url_title($result[$i]['adset_name']) . 'v' . $result[$i]['version'];
			}
			return $result;
		}
		return array();
	}

	public function io_define_creatives_for_product($product_id, $adset_id, $mpq_id)
	{
		// find cp_submitted_products_id for given mpq and product
		// insert multiple rows for (cp_submitted_products_id * adset_id)
		$cp_submitted_products_id_array=$this->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
		$return_flag=true;
		$region_id_array=array();
		$ctr=0;
		if($cp_submitted_products_id_array != null)
		{
			$delete_query = "
				DELETE FROM
					cp_io_join_cup_versions
				WHERE
					cp_submitted_products_id IN
					(
						SELECT
							id
						FROM
							cp_submitted_products
						WHERE
							mpq_id = ? AND
							product_id = ?
					)";
			$response = $this->db->query($delete_query, array($mpq_id, $product_id));

			foreach($cp_submitted_products_id_array as $result)
			{
				foreach($adset_id as $sub_row)
				{
					$creative_id = explode(';', $sub_row);
					if($creative_id[0] == 0)
					{
						$bindings = array($result['id'], null, $creative_id[1]);
					}
					else
					{
						$bindings = array($result['id'], $creative_id[1], null);
					}
					$sql="
						INSERT INTO cp_io_join_cup_versions
						(cp_submitted_products_id, cup_versions_id, adset_request_id)
						VALUES
						(?, ?, ?)
						";
						$response = $this->db->query($sql, $bindings);
						if($this->db->affected_rows() == 0)
						{
							$return_flag=false;
						}
				}
				$region_id_array[]=$ctr;
				$ctr++;
			}
		}

		if ($return_flag)
		{
			return $region_id_array;
		}
		else
			return false;
	}

	public function io_save_adset_for_product_geo($cp_submitted_product_id, $adset_id)
	{
		$return_flag=true;
		$delete_query = "
			DELETE FROM
				cp_io_join_cup_versions
			WHERE
				cp_submitted_products_id = ?";
		$response = $this->db->query($delete_query, $cp_submitted_product_id);

		foreach($adset_id as $sub_row)
		{
			$creative_id = explode(';', $sub_row);
			if($creative_id[0] == 0)
			{
				$bindings = array($cp_submitted_product_id, null, $creative_id[1]);
			}
			else
			{
				$bindings = array($cp_submitted_product_id, $creative_id[1], null);
			}
			$sql=
			"INSERT INTO
				cp_io_join_cup_versions
				(cp_submitted_products_id, cup_versions_id, adset_request_id)
			VALUES
			(?, ?, ?)
				";
			$response = $this->db->query($sql, $bindings);
			if($this->db->affected_rows() == 0)
			{
				$return_flag=false;
			}
		}

		if ($return_flag)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function io_delete_all_timeseries_and_creatives($mpq_id)
	{
		$delete_query = 
		"DELETE FROM
			cp_io_join_cup_versions
		WHERE
			cp_submitted_products_id IN
			(
				SELECT
					id
				FROM
					cp_submitted_products
				WHERE
					mpq_id = ?
			)";
		$response = $this->db->query($delete_query, $mpq_id);
		
		$delete_query = 
				"
					DELETE FROM
						io_flight_subproduct_details
					WHERE 
						io_timeseries_id IN
						(
							SELECT
								cts.id
							FROM
								campaigns_time_series AS cts
							JOIN cp_submitted_products AS csp
								ON cts.campaigns_id = csp.id
							WHERE
								cts.object_type_id = 1
							AND 
								csp.mpq_id = ?
						)
				";
		$response = $this->db->query($delete_query, $mpq_id);

		$delete_query = "
				DELETE FROM
					campaigns_time_series
				WHERE
					campaigns_id IN
					(
						SELECT
							id
						FROM
							cp_submitted_products
						WHERE
							mpq_id = ?
					)
					AND object_type_id = 1";
		$response = $this->db->query($delete_query, $mpq_id);
		
	}

	public function get_flight_rows($flight_ids)
	{
		$flight_id_where = array();
		foreach($flight_ids as $flight_id)
		{
			$flight_id_where[] = "?";
		}
		$flight_id_where = implode(', ', $flight_id_where);
		$get_flight_rows_query = 
		"SELECT
			* 
		FROM
			campaigns_time_series
		WHERE
			id IN ({$flight_id_where})
		";

		$get_flight_rows_result = $this->db->query($get_flight_rows_query, $flight_ids);
		if($get_flight_rows_result == false || $get_flight_rows_result->num_rows != count($flight_ids))
		{
			return false;
		}
		$mapped_flights = array();
		$results = $get_flight_rows_result->result_array();
		foreach($results as $result_row)
		{
			$mapped_flights[$result_row['campaigns_id']] = $result_row;
		}
		return $mapped_flights;
	}

	public function delete_all_timeseries_data_for_submitted_product($cp_submitted_product_id, $retain_cpms = false)
	{

		$delete_query = 
		"DELETE FROM
			io_flight_subproduct_details
		WHERE
			io_timeseries_id IN 
			(SELECT 
				cts.id
			FROM 
				campaigns_time_series AS cts
			WHERE
				cts.campaigns_id = ?
				AND object_type_id = 1)";
		$response = $this->db->query($delete_query, $cp_submitted_product_id);


		$delete_query = "
				DELETE FROM
					campaigns_time_series
				WHERE
					campaigns_id IN
					(
						SELECT
							id
						FROM
							cp_submitted_products
						WHERE
							id = ?
					)
					AND object_type_id = 1";
		$response = $this->db->query($delete_query, $cp_submitted_product_id);

		if(!$retain_cpms)
		{
			$delete_query = 
			"DELETE FROM 
				io_campaign_product_cpm
			WHERE
				cp_submitted_products_id = ?
			";
			$response = $this->db->query($delete_query, $cp_submitted_product_id);
		}
	}	

	public function delete_all_timeseries_data_for_campaign($campaign_id, $retain_cpms = false)
	{

		$delete_query = 
		"DELETE FROM
			io_flight_subproduct_details
		WHERE
			io_timeseries_id IN 
			(SELECT 
				cts.id
			FROM 
				campaigns_time_series AS cts
			WHERE
				cts.campaigns_id = ?
				AND object_type_id = 0)";
		$response = $this->db->query($delete_query, $campaign_id);

		$delete_query = "
				DELETE FROM
					campaigns_time_series
				WHERE
					campaigns_id IN
					(
						SELECT
							id
						FROM
							cp_submitted_products
						WHERE
							id = ?
					)
					AND object_type_id = 0";
		$response = $this->db->query($delete_query, $campaign_id);

		if(!$retain_cpms)
		{
			$delete_query = 
			"DELETE FROM 
				io_campaign_product_cpm
			WHERE
				campaign_id = ?
			";
			$response = $this->db->query($delete_query, $campaign_id);
		}
	}		

	public function get_flights_data_for_submitted_product($submitted_product_id)
	{
		$get_time_series_query = 
		"SELECT
			cts.id AS id,
			cts.series_date AS start_date,
			cts.budget,
			cts.impressions AS ts_impressions,
			ifsd.o_o_impressions as o_o_impressions
		FROM
			campaigns_time_series AS cts
			LEFT JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		WHERE
			cts.campaigns_id = ?
			AND cts.object_type_id = 1
		ORDER BY 
			cts.series_date ASC
		";

		$get_time_series_result = $this->db->query($get_time_series_query, $submitted_product_id);
		if($get_time_series_result == false || $get_time_series_result->num_rows() < 1)
		{
			return false;
		}
		return $get_time_series_result->result_array();
	}

	public function get_flights_data_for_campaign($campaign_id)
	{
		$get_time_series_query = 
		"SELECT
			cts.series_date AS start_date,
			cts.budget,
			ifsd.o_o_impressions as o_o_impressions
		FROM
			campaigns_time_series AS cts
			JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		WHERE
			cts.campaigns_id = ?
			AND cts.object_type_id = 0
		ORDER BY 
			cts.series_date ASC
		";

		$get_time_series_result = $this->db->query($get_time_series_query, $campaign_id);
		if($get_time_series_result == false || $get_time_series_result->num_rows() < 1)
		{
			return false;
		}
		return $get_time_series_result->result_array();
	}	

	public function io_edit_adset_for_product_geo($product_id, $region_id, $mpq_id)
	{

		$query = "
			SELECT
				CONCAT(c.show_for_io, ';', c.id) AS id,
				CONCAT(a.creative_name, '-', c.version) AS text
			FROM
				cup_versions c,
				adset_requests a
			WHERE
			  	a.adset_id=c.adset_id AND
			  	c.id IN
			  		(
			  			SELECT
			  				DISTINCT v.cup_versions_id AS id
			  			FROM
			  				cp_io_join_cup_versions v,
			  				cp_submitted_products p
			  			WHERE
			  				v.cp_submitted_products_id = p.id AND
			  				p.mpq_id= ? AND
			  				p.product_id= ? AND
			  				p.region_data_index= ?
			  		)
			UNION
			SELECT
				CONCAT('0;', a.id) AS id,
				a.creative_name AS text
			FROM
				adset_requests a
			WHERE
			  	a.id IN
			  		(
			  			SELECT
			  				DISTINCT v.adset_request_id AS id
			  			FROM
			  				cp_io_join_cup_versions v,
			  				cp_submitted_products p
			  			WHERE
			  				v.cp_submitted_products_id = p.id AND
			  				p.mpq_id= ? AND
			  				p.product_id= ? AND
			  				p.region_data_index= ?
			  		)
			ORDER BY
				text ASC
			 ";
		$bindings = array($mpq_id, $product_id, $region_id, $mpq_id, $product_id, $region_id);
		$response = $this->db->query($query, $bindings);
		$return_array=array();
		if($response !== false and $response->num_rows() > 0)
		{
			$return_array['creatives_array']=$response->result_array();
		}
		$query = "
			SELECT
			  	DISTINCT p.id AS cp_submitted_products_id
  			FROM
  				cp_io_join_cup_versions v,
  				cp_submitted_products p
  			WHERE
  				v.cp_submitted_products_id = p.id AND
  				p.mpq_id=? AND
  				p.product_id=? AND
  				p.region_data_index=?
			 ";

		$response = $this->db->query($query, array($mpq_id, $product_id, $region_id));
		if($response !== false and $response->num_rows() > 0)
		{
			foreach ($response->result_array() as $row)
			{
				$return_array['cp_submitted_products_id']=$row['cp_submitted_products_id'];
			}
		}

		return $return_array;
	}

	public function get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id)
	{
		$query = "
			SELECT
				*
			FROM
				cp_submitted_products
			WHERE
				mpq_id = ? AND
				product_id = ?";
		$response = $this->db->query($query, array($mpq_id, $product_id));
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function get_time_series_by_submitted_products($region_id, $product_id, $session_id)
	{
		if (empty($region_id))
		{
			$region_id = '*';
		}
		$query = "
			SELECT
				cts.id,
				cts.campaigns_id,
				DATE_FORMAT(cts.series_date, '%m/%d/%Y') as start_date,
				cts.impressions
			FROM
				mpq_sessions_and_submissions mss
				JOIN cp_submitted_products csp
					ON mss.id = csp.mpq_id
				JOIN campaigns_time_series cts
					ON csp.id = cts.campaigns_id
			WHERE
				csp.product_id = ? AND
				csp.region_data_index = ? AND
				mss.session_id = ? AND
				cts.object_type_id = 1";
		$response = $this->db->query($query, array($product_id, $region_id, $session_id));
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function get_time_series_for_product_or_submitted_product($product_id, $session_id, $region_id = null)
	{
		$group_by = "";	
		$imps_budget = 
		"cts.impressions,
		 cts.budget";

		if ($region_id === null)
		{
			$region_id = '*';
			$group_by = "GROUP BY start_date";
			$imps_budget = 
			"SUM(cts.impressions),
			 SUM(cts.budget)";			
		}
		$query = "
			SELECT
				cts.id,
				cts.campaigns_id,
				DATE_FORMAT(cts.series_date, '%m/%d/%Y') as start_date, ".
				$imps_budget.
			" FROM
				mpq_sessions_and_submissions mss
				JOIN cp_submitted_products csp
					ON mss.id = csp.mpq_id
				JOIN campaigns_time_series cts
					ON csp.id = cts.campaigns_id
			WHERE
				csp.product_id = ? AND
				csp.region_data_index = ? AND
				mss.session_id = ? AND
				cts.object_type_id = 1 ".$group_by;
		$response = $this->db->query($query, array($product_id, $region_id, $session_id));
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}


	public function save_io_status_values($io_status, $mpq_id)
	{
		$query = "
			INSERT INTO
				io_status
				(mpq_id, opportunity_status, product_status, geo_status, audience_status, flights_status, creative_status, notes_status, tracking_status)
				VALUES (?,?,?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE
				opportunity_status = ?,
				product_status = ?,
				geo_status = ?,
				audience_status = ?,
				flights_status = ?,
				creative_status = ?,
				notes_status = ?,
				tracking_status = ?";
		$bindings = array(
			$mpq_id,
			$io_status['opportunity_status'],
			$io_status['product_status'],
			$io_status['geo_status'],
			$io_status['audience_status'],
			$io_status['flights_status'],
			$io_status['creative_status'],
			$io_status['notes_status'],
			$io_status['tracking_status'],
			$io_status['opportunity_status'],
			$io_status['product_status'],
			$io_status['geo_status'],
			$io_status['audience_status'],
			$io_status['flights_status'],
			$io_status['creative_status'],
			$io_status['notes_status'],
			$io_status['tracking_status']
			);
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_mpq_id_and_is_submitted_by_session_id($session_id)
	{
		$query = "
			SELECT
				id,
				is_submitted
			FROM
				mpq_sessions_and_submissions
			WHERE
			session_id = ?";
		$response = $this->db->query($query, $session_id);
		if($response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	/*
	 * Not code reviewed, not used Superflow 3.2 - Will
	public function initialize_flight_and_creative_data_for_io($mpq_id, $new_mpq_id)
	{
		$is_success = true;

		$delete_flights_query = "DELETE FROM campaigns_time_series WHERE campaigns_id = ? AND object_type_id = ?";
		$delete_flights_response = $this->db->query($delete_flights_query, array($new_mpq_id, 1));
		$delete_creative_query = "DELETE FROM cp_io_join_cup_versions WHERE cp_submitted_products_id = ?";
		$delete_creative_response = $this->db->query($delete_creative_query, $new_mpq_id);

		$delete_products_query = "DELETE FROM cp_submitted_products WHERE mpq_id = ?";
		$delete_products_response = $this->db->query($delete_products_query, $new_mpq_id);

		$product_query = "SELECT * FROM cp_submitted_products WHERE mpq_id = ?";
		$product_response = $this->db->query($product_query, $mpq_id);
		if($product_response->num_rows() > 0)
		{
			foreach($product_response->result_array() as $key => $product)
			{
				$insert_query = "
					INSERT INTO
						cp_submitted_products
						(
							mpq_id,
							proposal_id,
							option_id,
							product_id,
							frq_prop_gen_lap_id,
							submitted_values,
							region_data_index
						)
					VALUES (?,?,?,?,?,?,?)";
				$bindings = array(
					$new_mpq_id,
					$product['proposal_id'],
					$product['option_id'],
					$product['product_id'],
					$product['frq_prop_gen_lap_id'],
					$product['submitted_values'],
					$product['region_data_index']
					);
				$insert_response = $this->db->query($insert_query, $bindings);
				$new_product_id = $this->db->insert_id();
				$flight_insert_query = "
					INSERT INTO
						campaigns_time_series
						(
							campaigns_id,
							series_date,
							impressions,
							action_flag,
							object_type_id
						)
					SELECT
						?,
						series_date,
						impressions,
						action_flag,
						object_type_id
					FROM
						campaigns_time_series
					WHERE
						campaigns_id = ? AND
						object_type_id = ?";
				$flight_insert_response = $this->db->query($flight_insert_query, array($new_product_id, $product['id'], 1));
				$creative_insert_query = "
					INSERT INTO
						cp_io_join_cup_versions
						(
							cp_submitted_products_id,
							cup_versions_id,
							created_date
						)
					SELECT
						?,
						cup_versions_id,
						created_date
					FROM
						cp_io_join_cup_versions
					WHERE
						cp_submitted_products_id = ?";
				$creative_insert_response = $this->db->query($creative_insert_query, array($new_product_id, $product['id']));
				if($insert_response == false or $flight_insert_response == false or $creative_insert_response == false)
				{
					$is_success = false;
				}
			}
		}
		return $is_success;
	}
	*/


	/*
	 * Not code reviewed, not used Superflow 3.2 - Will
	public function initialize_io_status_for_io($mpq_id, $new_mpq_id)
	{
		$query = "
			REPLACE INTO
				io_status
				(
					mpq_id,
					opportunity_status,
					product_status,
					geo_status,
					audience_status,
					flights_status,
					creative_status,
					notes_status
				)
			SELECT
				?,
				opportunity_status,
				product_status,
				geo_status,
				audience_status,
				flights_status,
				creative_status,
				notes_status
			FROM
				io_status
			WHERE
				mpq_id = ?";
		$response = $this->db->query($query, array($new_mpq_id, $mpq_id));

		if($response !== false && $this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}
	*/

	public function get_io_status_by_mpq_id($mpq_id)
	{
		$query = "SELECT * FROM io_status WHERE mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response && $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function get_flights_defined_by_mpq_id($mpq_id)
	{
		$query =
		"	SELECT DISTINCT
				sp.id AS submitted_product_id,
				sp.product_id AS product_id,
				sp.region_data_index AS region_id,
				DATE_FORMAT(MIN(ts.series_date), '%m/%d/%Y') AS start_date,
				DATE_FORMAT(MAX(ts.series_date), '%m/%d/%Y')  AS end_date,
				SUM(ts.impressions) AS impressions,
				cspoo.o_o_percent AS o_o_percent,
				GROUP_CONCAT(cspooc.o_o_campaign_id SEPARATOR ' ; ') AS o_o_ids
			FROM
				campaigns_time_series AS ts
				JOIN cp_submitted_products AS sp
					ON ts.campaigns_id = sp.id
				JOIN cp_products_join_io AS cpji
					ON (cpji.mpq_id = sp.mpq_id AND cpji.product_id = sp.product_id)
				LEFT JOIN cp_submitted_product_o_o AS cspoo
					ON sp.id = cspoo.cp_submitted_product_id
				LEFT JOIN cp_submitted_product_o_o_campaigns AS cspooc
					ON sp.id = cspooc.cp_submitted_product_id
			WHERE
				sp.mpq_id = ? AND
				ts.object_type_id = ?
			GROUP BY
				sp.product_id, sp.region_data_index";
		$response = $this->db->query($query, array($mpq_id, 1));
		if($response !== false && $response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function get_time_series_summary_by_mpq_id($mpq_id, $product_id = false)
	{
		$product_sql = '';
		$bindings = array($mpq_id, 1);
		if ($product_id)
		{
			$product_sql = "AND sp.product_id = ?";
			$bindings[] = $product_id;
		}

		$query =
		"	SELECT DISTINCT
				ts.campaigns_id AS submitted_product_id,
				DATE_FORMAT(MIN(ts.series_date), '%m/%d/%Y') AS min_date,
				DATE_FORMAT(MAX(ts.series_date), '%m/%d/%Y')  AS max_date,
				SUM(ts.impressions) AS sum_impressions,
				sp.product_id AS product_id
			FROM
				campaigns_time_series AS ts
				JOIN cp_submitted_products AS sp
					ON ts.campaigns_id = sp.id
			WHERE
				sp.mpq_id = ? AND
				ts.object_type_id = ? $product_sql
			GROUP BY
			    sp.product_id";
		$response = $this->db->query($query, $bindings);
		if($response !== false && $response->num_rows() > 0)
		{
			$results = $response->result_array();
			foreach ($results as &$product)
			{
				$product['max_date'] = date('m/d/Y', strtotime($product['max_date'] . ' -1 day'));
			}
			return $results;
		}
		return false;
	}

	public function get_creatives_defined_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				cv.cup_versions_id AS creative_id,
				sp.id AS submitted_product_id,
				sp.product_id AS product_id,
				sp.region_data_index AS region_id,
				ar.landing_page,
				ar.creative_name,
				1 AS creative_status
			FROM
				cp_io_join_cup_versions AS cv
				JOIN cp_submitted_products AS sp
					ON cv.cp_submitted_products_id = sp.id
				JOIN cup_versions AS cpv
					ON cv.cup_versions_id = cpv.id
				JOIN adset_requests AS ar
					ON cpv.adset_id = ar.adset_id
			WHERE
				cv.adset_request_id IS NULL
				AND sp.mpq_id = ?
			UNION
			SELECT
				cv.cup_versions_id AS creative_id,
				sp.id AS submitted_product_id,
				sp.product_id AS product_id,
				sp.region_data_index AS region_id,
				ar.landing_page,
				ar.creative_name AS creative_name,
				2 AS creative_status
			FROM
				cp_io_join_cup_versions AS cv
				JOIN cp_submitted_products AS sp
					ON cv.cp_submitted_products_id = sp.id
				JOIN adset_requests AS ar
					ON cv.adset_request_id = ar.id
			WHERE
				cv.cup_versions_id IS NULL
				AND sp.mpq_id = ?
			";

		$response = $this->db->query($query, array($mpq_id, $mpq_id));

		if ($response !== false && $response->num_rows() > 0)
		{
			return $response->result_array();
		}

		return false;
	}

	public function get_io_editable_details($mpq_id)
	{
		$query = "
			SELECT
				is_submitted,
				mpq_type,
				io_lock_timestamp
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response && $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function update_mpq_with_session_id($mpq_id, $session_id)
	{
		$query = "UPDATE mpq_sessions_and_submissions SET session_id = ?, io_lock_timestamp = CURRENT_TIMESTAMP WHERE id = ?";
		$response = $this->db->query($query, array($session_id, $mpq_id));
		if($response !== false && $this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function unlock_mpq_session_by_session_id($session_id, $mpq_id = null)
	{
		$query =
		"	UPDATE mpq_sessions_and_submissions
			SET io_lock_timestamp = NULL,
			session_id = NULL
			WHERE session_id = ?";

		if (!empty($mpq_id))
		{
			$query .= "AND NOT(id = $mpq_id)";
		}
		$response = $this->db->query($query, array($session_id));
		if($response !== false)
		{
			return true;
		}
		return false;
	}

	public function unlock_mpq_session_by_mpq_id($mpq_id)
	{
		$query = "
			UPDATE
				mpq_sessions_and_submissions
			SET
				io_lock_timestamp = NULL,
				session_id = NULL
			WHERE
				id = ?";
		$response = $this->db->query($query, $mpq_id);
		return $response;
	}

	public function get_mpq_sessions_session_id_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				session_id
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response !== false && $response->num_rows() > 0)
		{
			return $response->row()->session_id;
		}
		return false;
	}

	public function io_delete_create_new_submitted_products_for_io($mpq_id, $raw_region_data)
	{
		//Delete O&O data first
		$delete_query =
		"DELETE
			cspoo
		FROM
			cp_submitted_product_o_o AS cspoo
			JOIN cp_submitted_products AS csp
				ON (cspoo.cp_submitted_product_id = csp.id)
		WHERE
			csp.mpq_id = ?;
		";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		$delete_query =
		"DELETE
			cspooc
		FROM
			cp_submitted_product_o_o_campaigns AS cspooc
			JOIN cp_submitted_products AS csp
				ON (cspooc.cp_submitted_product_id = csp.id)
		WHERE
			csp.mpq_id = ?;
		";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		$delete_query = 
		"DELETE FROM
			io_flight_subproduct_details
		WHERE 
			io_timeseries_id IN
			(SELECT 
				cts.id
			FROM 
				campaigns_time_series AS cts
				JOIN cp_submitted_products AS csp
					ON(cts.campaigns_id = csp.id AND cts.object_type_id = 1)
			WHERE
				csp.mpq_id = ?)";
		$response = $this->db->query($delete_query, $mpq_id);

		$region_data = json_decode($raw_region_data, true);
		$delete_query = "DELETE FROM cp_submitted_products WHERE mpq_id = ?";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		$select_query = "
			SELECT
				*
			FROM
				cp_products_join_io
			WHERE
				mpq_id = ?";
		$select_response = $this->db->query($select_query, $mpq_id);
		if($select_response->num_rows() > 0)
		{
			$product_join_data = $select_response->result_array();
		}

		$value_sql = "";
		$bindings = array();
		foreach($region_data as $region_index => $region)
		{
			foreach($product_join_data as $product)
			{
				$bindings[] = $mpq_id;
				$bindings[] = null;
				$bindings[] = 0;
				$bindings[] = $product['product_id'];
				$bindings[] = null;
				$bindings[] = '{"unit": '.$product["impressions_per_location"].'}';
				$bindings[] = $region_index;
				if($value_sql !== "")
				{
					$value_sql .= ", ";
				}
				$value_sql .= "(?, ?, ?, ?, ?, ?, ?)";
			}
		}

		$insert_query = "
			INSERT INTO
				cp_submitted_products
				(
					mpq_id,
					proposal_id,
					option_id,
					product_id,
					frq_prop_gen_lap_id,
					submitted_values,
					region_data_index
				)
				VALUES
				".$value_sql;
		$insert_response = $this->db->query($insert_query, $bindings);
		if($insert_response)
		{
			return true;
		}
		return false;

	}

	function get_verified_advertisers_for_internal_users_for_select2($search_term, $start, $limit)
	{
		$binding_array = array();
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $start;
		$binding_array[] = $limit;

		$sql =
			"
			SELECT
				a.id,
				a.Name,
				a.external_id,
				CONCAT(u.firstname, ' ', u.lastname) AS user_name,
				u.email AS email,
				'verified' AS status,
				'Advertisers' AS source_table,
				tsa.ul_id as ul_id,
				tsa.eclipse_id as eclipse_id,
				a.sales_person
			FROM
				wl_partner_details pd
			JOIN
				users AS u
			ON
				pd.id = u.partner_id
			JOIN
				Advertisers AS a
			ON
				u.id = a.sales_person
			LEFT JOIN
				tp_spectrum_accounts tsa
			ON
				a.id = tsa.advertiser_id
			WHERE
				(a.Name LIKE ? OR u.email LIKE ? or a.external_id LIKE ? OR tsa.ul_id LIKE ? OR tsa.eclipse_id LIKE ?)
			ORDER BY
				a.Name ASC
			LIMIT ?, ?";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			$result = array();
			foreach ($query->result_array() as $row)
			{
				$result[$row['Name']] = $row;
			}
			return $result;
		}

		return false;
	}

	public function get_verified_advertisers_by_user_id_for_select2($search_term, $start, $limit, $user_id, $is_super)
	{
		$non_super_sales_where_sql = "";
		$binding_array = array($user_id);

		if ($is_super == 1)
		{
			$wl_partner_hierarchy_join_sql = "(u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)";
		}
		else
		{
			$wl_partner_hierarchy_join_sql = " po.partner_id = h.ancestor_id";
			$non_super_sales_where_sql = "OR a.sales_person = ?";
			$binding_array[] = $user_id;
		}

		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $start;
		$binding_array[] = $limit;


		$sql =
			"
			SELECT DISTINCT
				a.id,
				a.Name,
				a.external_id,
				tsa.ul_id as ul_id,
				tsa.eclipse_id as eclipse_id,
				COALESCE(CONCAT(u2.firstname, ' ', u2.lastname), CONCAT(u3.firstname, ' ', u3.lastname)) AS user_name,
				COALESCE(u2.email, u3.email) AS email,
				'verified' AS status,
				'Advertisers' AS source_table,
				a.sales_person
			FROM
				users AS u ";

		if ($is_super == '1')
		{
			$sql .= "LEFT ";
		}

		$sql .=	"JOIN
				wl_partner_owners po
			ON
				u.id = po.user_id
			JOIN
				wl_partner_hierarchy h
			ON
				".$wl_partner_hierarchy_join_sql."
			JOIN
				wl_partner_details pd
			ON
				h.descendant_id = pd.id
			JOIN
				users u2
			ON
				pd.id = u2.partner_id
			RIGHT JOIN
				Advertisers a
			ON
				u2.id = a.sales_person
			JOIN
				users u3
			ON
				u3.id = a.sales_person
			LEFT JOIN
				tp_spectrum_accounts tsa
			ON
				a.id = tsa.advertiser_id
			WHERE
				(u.id = ? ".$non_super_sales_where_sql.")
			AND
				(a.Name LIKE ? OR u2.email LIKE ? or a.external_id LIKE ? OR tsa.ul_id LIKE ? OR tsa.eclipse_id LIKE ?)
			ORDER BY
				a.Name ASC
			LIMIT ?, ?";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			$result = array();
			foreach ($query->result_array() as $row)
			{
				$result[$row['Name']] = $row;
			}
			return $result;
		}

		return false;
	}

	function get_unverified_advertisers_for_internal_users_for_select2($search_term, $start, $limit)
	{
		$binding_array = array();
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $start;
		$binding_array[] = $limit;

		$sql =
			"
			SELECT
				a.id,
				a.name as Name,
				CONCAT(u.firstname, ' ', u.lastname) AS user_name,
				u.email AS email,
				'unverified' AS status,
				'advertisers_unverified' AS source_table,
				tsa.ul_id as ul_id,
				tsa.eclipse_id as eclipse_id,
				a.sales_person
			FROM
				wl_partner_details pd
			JOIN
				users AS u
			ON
				pd.id = u.partner_id
			JOIN
				advertisers_unverified AS a
			ON
				u.id = a.sales_person
			LEFT JOIN
				tp_spectrum_accounts tsa
			ON
				a.id = tsa.advertiser_id
			WHERE
				(a.name LIKE ? OR u.email LIKE ? OR tsa.ul_id LIKE ? OR tsa.eclipse_id LIKE ?)
			AND
				a.name NOT IN (SELECT Name FROM Advertisers)
			ORDER BY
				a.name ASC
			LIMIT ?, ?";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			$result = array();
			foreach ($query->result_array() as $row)
			{
				$result[$row['Name']] = $row;
			}
			return $result;
		}

		return false;
	}

	public function get_unverified_advertisers_by_user_id_for_select2($search_term, $start, $limit, $user_id, $is_super)
	{
		$non_super_sales_where_sql = "";
		$binding_array = array($user_id);

		if ($is_super == 1)
		{
			$wl_partner_hierarchy_join_sql = "(u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)";
		}
		else
		{
			$wl_partner_hierarchy_join_sql = " po.partner_id = h.ancestor_id";
			$non_super_sales_where_sql = "OR a.sales_person = ?";
			$binding_array[] = $user_id;
		}

		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $search_term;
		$binding_array[] = $start;
		$binding_array[] = $limit;


		$sql =
			"
			SELECT DISTINCT
				a.id,
				a.name AS Name,
				CONCAT(u3.firstname, ' ', u3.lastname) AS user_name,
				u3.email AS email,
				'unverified' AS status,
				'advertisers_unverified' AS source_table,
				tsa.ul_id as ul_id,
				tsa.eclipse_id as eclipse_id,
				a.sales_person
			FROM
				users AS u ";

		if ($is_super == '1')
		{
			$sql .= "LEFT ";
		}

		$sql .=	"JOIN
				wl_partner_owners po
			ON
				u.id = po.user_id
			JOIN
				wl_partner_hierarchy h
			ON
				".$wl_partner_hierarchy_join_sql."
			JOIN
				wl_partner_details pd
			ON
				h.descendant_id = pd.id
			JOIN
				users u2
			ON
				pd.id = u2.partner_id
			RIGHT JOIN
				advertisers_unverified a
			ON
				u2.id = a.sales_person
			JOIN users u3
				ON a.sales_person = u3.id
			LEFT JOIN
				tp_spectrum_accounts tsa
			ON
				a.id = tsa.advertiser_id
			WHERE
				(u.id = ? ".$non_super_sales_where_sql.")
			AND
				(a.name LIKE ? OR u3.email LIKE ? OR tsa.ul_id LIKE ? OR tsa.eclipse_id LIKE ?)
			AND
				a.name NOT IN (SELECT Name FROM Advertisers)
			ORDER BY
				a.name ASC
			LIMIT ?, ?";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			$result = array();
			foreach ($query->result_array() as $row)
			{
				$result[$row['Name']] = $row;
			}
			return $result;
		}

		return false;
	}

	public function check_if_verified_or_unverified_advertiser_exist_by_name($advertiser_name)
	{
		$binding_array = array();
		$binding_array[] = $advertiser_name;
		$binding_array[] = $advertiser_name;

		$sql =
			"
			SELECT
				adv.id
			FROM
				Advertisers adv
			WHERE
				adv.Name = ?
			UNION
			SELECT
				adv_unverified.id
			FROM
				advertisers_unverified adv_unverified
			WHERE
				adv_unverified.name = ?
			";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function create_unverified_advertiser($unverified_advertiser_name, $user_id)
	{
		$binding_array = array();
		$binding_array[] = $unverified_advertiser_name;
		$binding_array[] = $user_id;

		$sql = "INSERT INTO advertisers_unverified (name, sales_person) VALUES(?, ?)";
		$query = $this->db->query($sql,$binding_array);

		if ($this->db->affected_rows() > 0)
		{
			$result['is_success'] = true;
			$result['id'] = $this->db->insert_id();
			$result['source_table'] = 'advertisers_unverified';
			$result['advertiser_name'] = $unverified_advertiser_name;
		}
		else
		{
			$result['is_success'] = false;
		}

		return $result;
	}

	public function get_verified_advertiser_name_by_id($advertiser_id)
	{
		$binding_array = array();
		$binding_array[] = $advertiser_id;

		$sql =
			"
			SELECT
				adv.Name AS adv_name
			FROM
				Advertisers adv
			WHERE
				adv.id = ?
			";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result[0]['adv_name'];
		}

		return false;
	}

	public function get_unverified_advertiser_name_by_id($advertiser_id)
	{
		$binding_array = array();
		$binding_array[] = $advertiser_id;

		$sql =
			"
			SELECT
				adv_unverified.name AS adv_name
			FROM
				advertisers_unverified adv_unverified
			WHERE
				adv_unverified.id = ?
			";

		$query = $this->db->query($sql, $binding_array);

		if ($query->num_rows() > 0)
		{
			$result = $query->result_array();
			return $result[0]['adv_name'];
		}

		return false;
	}

	public function update_all_existing_versions_with_vanity_string()
	{
		$query = "
			SELECT
				cpa.name AS adset_name,
				cpv.id AS version_id,
				cpv.version AS version
			FROM
				cup_adsets AS cpa
				JOIN cup_versions AS cpv
					ON cpa.id = cpv.adset_id
			WHERE
				cpv.vanity_string IS NULL";
		$response = $this->db->query($query);
		if($response->num_rows() > 0)
		{
			$update_query = "
				UPDATE
					cup_versions
				SET
					vanity_string = ?
				WHERE
					id = ?";

			$versions_array = $response->result_array();
			$bindings = array();
			$total = count($versions_array);
			$base_percent = 2;
			if($total < 10000)
			{
				$base_percent = 5;
			}
			else if($total < 1000)
			{
				$base_percent = 10;
			}
			else if ($total < 100)
			{
				$base_percent = 20;
			}
			$count = 0;
			$last_percent = $base_percent;
			echo "Total versions to be updated: ".$total."\n";
			echo "Version update in progress...0%"."\r";
			$failed_string = "";
			foreach($versions_array as $version)
			{
				$response = $this->db->query($update_query, array(url_title($version['adset_name']).'v'.$version['version'], $version['version_id']));
				if($response)
				{
					$count++;
				}
				else
				{
					$failed_string .= "Failed to update version: ".$version['version_id']."\n";
				}

				if((100*$count/$total) > $last_percent)
				{
					echo "Version update in progress...".$last_percent."%\r";
					$last_percent += $base_percent;
				}
			}
			echo "Version update in progress...100%"."\n";
			echo "Version update complete: " . $count . "/" . $total . " Versions updated successfully"."\n";
			echo $failed_string;
		}
		else
		{
			echo "No versions require vanity string update"."\n";
		}

	}

/*  Gets political segment data by optional proposal id
 *  If proposal id is not provided (false by default) it selects the default value
 */
	public function get_political_segment_data($proposal_id = false)
	{
		$binding = array();
		if($proposal_id == false)
		{
			$query = "
				SELECT
					id,
					name,
					question_group,
					default_value AS value
				FROM
					cp_rfp_questions";
		}
		else
		{
			$binding [] = $proposal_id;
			$query = "
				SELECT
					cqu.id,
					cqu.name,
					cqu.question_group,
					answer_value AS value
				FROM
					cp_rfp_questions AS cqu
					JOIN cp_rfp_questions_join_mpq AS cqj
						ON cqu.id = cqj.question_id
					JOIN mpq_sessions_and_submissions AS mss
						ON cqj.mpq_id = mss.id
					JOIN prop_gen_prop_data AS pgd
						ON mss.id = pgd.source_mpq
				WHERE
					pgd.prop_id = ?";
		}
		$response = $this->db->query($query, $binding);
		if($response->num_rows() > 0)
		{
			return array_reduce($response->result_array(), function($carry, $item){
	            if (!array_key_exists($item['question_group'], $carry))
	            {
	            	$carry[$item['question_group']] = array();
	            }
                $carry[$item['question_group']][] = array(
                	'id' => $item['id'],
                	'name' => $item['name'],
                	'value' => $item['value']
                );

	            return $carry;
	        }, []);
		}
		return array();
	}

	public function save_mpq_political_segments($segment_data, $mpq_id)
	{
		$binding_sql = "";
		$binding_array = array();
		foreach($segment_data as $id => $segment)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "(?,?,?)";
			$binding_array[] = $mpq_id;
			$binding_array[] = $segment['name'];
			$binding_array[] = $segment['value'];
		}

		$query =
		"	INSERT INTO cp_rfp_questions_join_mpq
				(mpq_id, question_id, answer_value)
			VALUES ".$binding_sql."
			ON DUPLICATE KEY UPDATE
				answer_value = VALUES(answer_value)";
		$response = $this->db->query($query, $binding_array);
	}

	public function get_io_summary_data_by_session_id($session_id)
	{
		$sql =	"
			SELECT
				*
			FROM
				mpq_sessions_and_submissions
			WHERE
				session_id = ?
			";
		$bindings = array($session_id);
		$response = $this->db->query($sql, $bindings);

		if ($response->num_rows > 0)
		{
			$query_response_array = $response->result_array();
			return $query_response_array[0];
		}
		else
		{
			return false;
		}
	}

	public function get_io_summary_data($mpq_id)
	{
		$sql =	"
			SELECT
				*
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?
			";
		$bindings = array($mpq_id);
		$response = $this->db->query($sql, $bindings);

		if ($response->num_rows > 0)
		{
			$query_response_array = $response->result_array();
			return $query_response_array[0];
		}
		else
		{
			return false;
		}
	}

	public function generate_insertion_order_unique_id()
	{
		$current_time = time();
		$random_number = str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
		return $current_time.$random_number;
	}

	public function get_owner_id_by_mpq_id($mpq_id)
	{
		$sql =
		'	SELECT owner_user_id
			FROM mpq_sessions_and_submissions
			WHERE id = ?
			LIMIT 1
		';
		$query = $this->db->query($sql, array($mpq_id));
		if ($query->num_rows() > 0)
		{
			return $query->row()->owner_user_id;
		}
		return false;
	}

	public function save_submitted_tv_zones($mpq_id, $tv_zones)
	{
		$delete_query = "
			DELETE FROM
				cp_geo_regions_collection_join_mpq
			WHERE
				mpq_id = ?";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		$values_sql = "";
		$bindings = array();
		foreach($tv_zones as $zone)
		{
			if($values_sql !== "")
			{
				$values_sql .= ", ";
			}
			$values_sql .= "(?, ?)";
			$bindings[] = $mpq_id;
			$bindings[] = $zone['id'];
		}

		if($values_sql !== "")
		{
			$query = "
				INSERT INTO
					cp_geo_regions_collection_join_mpq
				(mpq_id, geo_regions_collection_id)
				VALUES ".$values_sql;
			$response = $this->db->query($query, $bindings);
			return $response;
		}
		else
		{
			return false;
		}
	}

	public function get_rfp_tv_zones_by_mpq_id($mpq_id)
	{
		$query = "
			SELECT
				grc.id AS id,
				grc.name AS text
			FROM
				cp_geo_regions_collection_join_mpq AS cpj
				JOIN geo_regions_collection AS grc
					ON cpj.geo_regions_collection_id = grc.id
			WHERE
				cpj.mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}

	public function save_keywords_data($mpq_id, $clicks, $search_terms)
	{
		$bindings = array($mpq_id, $clicks, json_encode($search_terms));
		$query = "
			INSERT INTO
				mpq_search_keywords_data
				(mpq_id, clicks, search_terms)
			VALUES
				(?, ?, ?)
			ON DUPLICATE KEY UPDATE
				clicks = VALUES(clicks),
				search_terms = VALUES(search_terms)";
		$response = $this->db->query($query, $bindings);
		if($response)
		{
			return true;
		}
		return false;
	}

	public function save_advertiser_website($mpq_id, $advertiser_website)
	{
		$sql = 
		'	UPDATE mpq_sessions_and_submissions
			SET advertiser_website = ?
			WHERE id = ?
		';
		$response = $this->db->query($sql, array($advertiser_website, $mpq_id));
		if($response)
		{
			return true;
		}
		return false;
	}

	public function get_rfp_keywords_data($mpq_id)
	{
		$return_array = array();
		$query = "
			SELECT
				*
			FROM
				mpq_search_keywords_data
		   	WHERE
				mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			$return_array['clicks'] = $response->row()->clicks;
			$return_array['search_terms'] = $response->row()->search_terms;
		}
		return $return_array;


	}

	public function get_mpq_id_by_unique_display_id($unique_display_id)
	{
		$sql =
		"	SELECT id
			FROM mpq_sessions_and_submissions
			WHERE unique_display_id = ?
		";
		$result = $this->db->query($sql, $unique_display_id);
		return $result->num_rows() > 0 ? $result->row()->id : false;
	}

	public function add_geofencing_regions_to_db($mpq_id, $location_id, $geofencing_data, $affected_regions)
	{
		$delete_sql = "DELETE FROM proposal_geofencing_points WHERE mpq_id = ? AND location_id = ?";
		$delete_bindings = [
			$mpq_id,
			$location_id
		];
		if($affected_regions)
		{
			$insert_sql = "INSERT IGNORE INTO proposal_geofencing_points (mpq_id, location_id, search_term, center_point, type, radius_in_meters, affected_zctas) VALUES ";
			$insert_bindings = [];
			$insert_string = implode(',', array_fill(0, count($geofencing_data), '(?, ?, ?, POINT(?, ?), ?, ?, ?)'));
			foreach ($geofencing_data as $index => $geofence)
			{
				$insert_bindings[] = $mpq_id;
				$insert_bindings[] = $location_id;
				$insert_bindings[] = $geofence['search'];
				$insert_bindings[] = floatval($geofence['latlng'][1]);
				$insert_bindings[] = floatval($geofence['latlng'][0]);
				$insert_bindings[] = $geofence['type'];
				$insert_bindings[] = intval($geofence['radius']);
				$insert_bindings[] = implode(',', $affected_regions[$index]);
			}

			$this->db->trans_start();
			$this->db->query($delete_sql, $delete_bindings);
			$this->db->query($insert_sql . $insert_string, $insert_bindings);
			$this->db->trans_complete();
		}
		else
		{
			$this->db->query($delete_sql, $delete_bindings);
		}
	}

	public function copy_geofences_from_original_mpq($mpq_id, $original_mpq_id) {
		$sql =
		'	INSERT INTO proposal_geofencing_points
				(
					mpq_id,
					location_id,
					search_term,
					center_point,
					type,
					radius_in_meters,
					affected_zctas
				)
			SELECT
				?,
				location_id,
				search_term,
				center_point,
				type,
				radius_in_meters,
				affected_zctas
			FROM proposal_geofencing_points
			WHERE mpq_id = ?
		';
		$this->db->query($sql, array($mpq_id, $original_mpq_id));
	}

	public function shift_geofence_regions_for_rfp_locations($mpq_id, $location_id)
	{
		$bindings = [$mpq_id, $location_id];
		$delete_sql =
		"	DELETE FROM proposal_geofencing_points
			WHERE
				mpq_id = ? AND
				location_id = ?
		";
		$update_sql =
		"	UPDATE proposal_geofencing_points
			SET location_id = location_id - 1
			WHERE
				mpq_id = ? AND
				location_id > ?
		";
		$result = $this->db->query($delete_sql, $bindings) && $this->db->query($update_sql, $bindings);
		return $result;
	}

	public function insert_missing_geofence_regions_if_needed($mpq_id, $location_id, $affected_regions)
	{
		$region_data = $this->get_region_data_by_mpq_id($mpq_id);
		if($region_data && $region_data = json_decode($region_data, true))
		{
			if(isset($region_data[$location_id]))
			{
				if($region_data[$location_id]['ids'] && $region_data[$location_id]['ids']['zcta'])
				{
					$regions_not_in_db = array_values(array_diff($affected_regions, $region_data[$location_id]['ids']['zcta']));
					$region_data[$location_id]['ids']['zcta'] = array_values(array_unique(array_merge($region_data[$location_id]['ids']['zcta'], $affected_regions)));
				}
				else if($region_data[$location_id]['ids'])
				{
					$regions_not_in_db = array_values($affected_regions);
					$region_data[$location_id]['ids']['zcta'] = $regions_not_in_db;
				}
				$update_sql = "UPDATE mpq_sessions_and_submissions SET region_data = ? WHERE id = ?";
				$bindings = [
					json_encode($region_data),
					$mpq_id
				];
				$this->db->query($update_sql, $bindings);
				return $regions_not_in_db;
			}
		}
		return [];
	}

	public function get_geofencing_points_from_mpq_id_sorted_by_location_id($mpq_id)
	{
		$return_array = [];
		$geofencing_points = $this->get_geofencing_points_from_mpq_id_and_location_id($mpq_id);
		if(count($geofencing_points) > 0)
		{
			$return_array_keys = array_column($geofencing_points, 'location_id');
			$return_array = array_combine($return_array_keys, array_fill(0, count($return_array_keys), []));
			foreach ($geofencing_points as $point_data)
			{
				$return_array[$point_data['location_id']] = $point_data;
			}
		}
		return $return_array;
	}

	public function get_geofencing_points_from_mpq_id_and_location_id($mpq_id, $location_id = null)
	{
		if($mpq_id)
		{
			$select_sql =
			"	SELECT
					pgp.location_id,
					Y(pgp.center_point) AS latitude,
					X(pgp.center_point) AS longitude,
					pgp.type,
					pgp.radius_in_meters AS radius,
					pgp.affected_zctas,
					pgp.search_term,
					gcd.density_by_population_type AS zcta_type
				FROM proposal_geofencing_points AS pgp
				JOIN geo_zcta_map AS gzm
					ON gzm.num_id = SUBSTRING_INDEX(pgp.affected_zctas, ',', 1)
				JOIN geo_cumulative_demographics AS gcd
					ON gcd.id = gzm.gcd_id
				WHERE pgp.mpq_id = ?
			";
			$bindings = [intval($mpq_id)];
			if($location_id !== null)
			{
				$select_sql .= " AND location_id = ?";
				$bindings[] = intval($location_id);
			}
			else
			{
				$select_sql .= "ORDER BY location_id";
			}
			$result = $this->db->query($select_sql, $bindings);
			if($result->num_rows() > 0)
			{
				$results = [];
				foreach($result->result_array() as $row)
				{
					$new_geofence = [];
					$new_geofence['location_id'] = intval($row['location_id']);
					$new_geofence['latlng'] = [floatval($row['latitude']), floatval($row['longitude'])];
					$new_geofence['type'] = $row['type'];
					$new_geofence['radius'] = intval($row['radius']);
					$new_geofence['search_term'] = $row['search_term'];
					$new_geofence['affected_zctas'] = empty($row['affected_zctas']) ? [] : explode(',', $row['affected_zctas']);
					$new_geofence['raw_affected_zctas'] = empty($row['affected_zctas']) ? "" : str_replace(",", ", ", $row['affected_zctas']);
					$new_geofence['zcta_type'] = $row['zcta_type'];
					$results[] = $new_geofence;
				}
				return $results;
			}
		}
		return [];
	}

	public function calculate_geofencing_max_inventory($mpq_id, $location_id = null, $as_array = false)
	{
		$yield = floatval($this->config->item('geofencing_yield'));
		$cap_per_radius_constant = floatval($this->config->item('geofencing_cap_per_radius_constant'));

		$bindings = [$cap_per_radius_constant, $yield, $mpq_id];
		$location_id_sql = '';

		if(is_numeric($location_id))
		{
			$location_id_sql = 'AND location_id = ?';
			$bindings[] = intval($location_id);
		}
		$as_array_sql = '';
		$location_id_select_sql = '';
		if($as_array)
		{
			$location_id_select_sql = 'location_id,';
			$as_array_sql = 'GROUP BY location_id';
		}

		$max_inventory_sql =
		"	SELECT
				{$location_id_select_sql}
				SUM(GREATEST(
					LEAST((t.yield * t.ratio * t.traffic), cap_per_radius),
					150
				)) AS max_inventory
			FROM (
				SELECT
					points.point_number,
					points.local_id AS zcta,
					points.location_id AS location_id,
					(points.radius_in_meters * (?)) AS cap_per_radius,
					? AS yield,
					((PI() * POW((points.radius_in_meters / 1000), 2)) / (gp.land_area_sq_km)) AS ratio,
					IFNULL(SUM(uzmi.imprs), 0) AS traffic
				FROM (
					SELECT
						(@point_count := @point_count + 1) AS point_number,
						pgp.location_id,
						pgp.radius_in_meters,
						SUBSTRING_INDEX(pgp.affected_zctas, ',', 1) AS local_id
					FROM proposal_geofencing_points AS pgp
					JOIN (SELECT @point_count := 0) AS dummy
					WHERE
						pgp.mpq_id = ?
						{$location_id_sql}
				) AS points
				JOIN geo_polygons AS gp
					ON gp.local_id = points.local_id
				LEFT JOIN us_zip_mobile_inventory AS uzmi
					ON uzmi.zip = points.local_id
				GROUP BY points.point_number
			) AS t
			{$as_array_sql}
		";
		$result = $this->db->query($max_inventory_sql, $bindings);
		if($as_array && $result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			$inventories = array_map('floatval', array_column($result_array, 'max_inventory'));
			return array_combine(array_column($result_array, 'location_id'), $inventories);
		}
		else if($result->num_rows() > 0)
		{
			$row = $result->row_array();
			return intval($row['max_inventory']);
		}
		return ($as_array) ? [0] : 0;
	}

	public function get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id)
	{
		$bindings = array($mpq_id, $product_id, $region_id);

		$query = "
			SELECT
				*
			FROM
				cp_submitted_products
			WHERE
				mpq_id = ?
				AND product_id = ?
				AND region_data_index = ?";

		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function save_o_o_percentage_for_submitted_product($submitted_product_id, $o_o_percentage)
	{
		$save_o_o_percentage_query =
		"INSERT INTO
			cp_submitted_product_o_o
				(cp_submitted_product_id, o_o_percent)
			VALUES
			(?, ?)
			ON DUPLICATE KEY UPDATE
				o_o_percent = VALUES(o_o_percent)
		";

		$bindings = array($submitted_product_id, $o_o_percentage);
		$save_o_o_percentage_result = $this->db->query($save_o_o_percentage_query, $bindings);
		if($save_o_o_percentage_result == false)
		{
			return false;
		}
		return true;
	}

	public function save_o_o_ids_for_submitted_product($submitted_product_id, $o_o_ids)
	{
		$delete_o_o_ids =
		"DELETE FROM
			cp_submitted_product_o_o_campaigns
		WHERE
			cp_submitted_product_id = ?
		";

		$delete_o_o_result = $this->db->query($delete_o_o_ids, $submitted_product_id);
		if($delete_o_o_result == false)
		{
			return false;
		}

		if(!empty($o_o_ids))
		{
			$bindings = array();
			$query_values = array();
			foreach($o_o_ids as $o_o_id)
			{
				$bindings[] = $submitted_product_id;
				$bindings[] = is_array($o_o_id) ? $o_o_id['text'] : $o_o_id;
				$query_values[] = "(?,?)";
			}
			$query_values = implode(',', $query_values);
			$save_o_o_ids_query =
			"INSERT IGNORE INTO
				cp_submitted_product_o_o_campaigns
					(cp_submitted_product_id, o_o_campaign_id)
				VALUES".$query_values;

			$save_o_o_ids_result = $this->db->query($save_o_o_ids_query, $bindings);
			if($save_o_o_ids_result == false)
			{
				return false;
			}
		}
		return true;
	}
	public function get_o_o_data_for_mpq_product_region($mpq_id, $product_id, $region_id)
	{
		$get_o_o_data_query =
		"SELECT
			cspoo.o_o_percent AS o_o_percent,
			GROUP_CONCAT(cspooc.o_o_campaign_id SEPARATOR ' ; ') AS o_o_ids
		FROM
			cp_submitted_products AS csp
			LEFT JOIN cp_submitted_product_o_o AS cspoo
				ON (csp.id = cspoo.cp_submitted_product_id)
			LEFT JOIN cp_submitted_product_o_o_campaigns AS cspooc
				ON (csp.id = cspooc.cp_submitted_product_id)
		WHERE
			csp.mpq_id = ?
			AND csp.product_id = ?
			AND csp.region_data_index = ?
		GROUP BY
			csp.id
		";

		$bindings = array($mpq_id, $product_id, $region_id);
		$get_o_o_data_result = $this->db->query($get_o_o_data_query, $bindings);
		if($get_o_o_data_result == false)
		{
			return false;
		}
		return $get_o_o_data_result->result_array();

	}

	public function create_dfp_advertiser($advertiser_name)
	{
		$advertiser = $this->google_api->generate_advertiser_with_name($advertiser_name);
		$create_advertiser_result = $this->google_api->create_dfp_advertiser($advertiser);

		if($create_advertiser_result['success'])
		{
			return $create_advertiser_result['data'][0];
		}
		return false;
	}

	public function link_dfp_advertiser_to_io($mpq_id,$dfp_advertiser_id)
	{
		$link_dfp_adv_io_query = 
		"INSERT INTO
			io_dfp_advertiser_details
				(insertion_order_id, dfp_advertiser_id)
		VALUES
			(?, ?)
		ON DUPLICATE KEY UPDATE
			dfp_advertiser_id = VALUES(dfp_advertiser_id)
		";
		$bindings = array($mpq_id, $dfp_advertiser_id);

		$link_dfp_adv_io_result = $this->db->query($link_dfp_adv_io_query, $bindings);
		if($link_dfp_adv_io_result == false)
		{
			return false;
		}
		return true;
	}

	public function get_o_and_o_enabled_ios($mpq_ids)
	{
		$query =
			"
			SELECT
			DISTINCT sp.mpq_id,     
			  	CASE WHEN (cpp.o_o_enabled = 1) THEN 1 ELSE 0 END AS o_o_enabled,   
			  	CASE WHEN (cpp.o_o_dfp = 1) THEN 1 ELSE 0 END AS dfp_enabled   
			FROM   
			    cp_submitted_products AS sp 
			    JOIN cp_products AS cpp       
			    	ON sp.product_id = cpp.id AND
			        (cpp.o_o_enabled = 1 OR cpp.o_o_dfp  = 1) AND 
                    sp.mpq_id IN  ($mpq_ids)
			";

		$response = $this->db->query($query);

		if ($response->num_rows() > 0)
		{
			$o_and_o_enabled_mpq_ids = $response->result_array();
			$result = array();
			$result["o_o_enable"] = array();
			$result["dfp_enable"] = array();

			foreach ($o_and_o_enabled_mpq_ids as $row)
			{
				if($row['o_o_enabled'] == 1)
				{
					$result["o_o_enable"][$row['mpq_id']] = 1;
				}
				
				if($row['dfp_enabled'] == 1)
				{
					$result["dfp_enable"][$row['mpq_id']] = 1;
				}
			}

			return $result;
		}
		else
		{
			return false;
		}
	}

	public function get_ad_tags_by_mpq_id($mpq_id)
	{
		$query =
			"
				SELECT DISTINCT
					cc.ad_tag,
 					cc.size,
 					cc.version_id,
 					ca.name,
					cc.published_ad_server
				FROM
					cp_io_join_cup_versions AS cv
					JOIN cp_submitted_products AS sp
						ON (cv.cp_submitted_products_id = sp.id)
					JOIN cup_versions AS cpv
						ON (cv.cup_versions_id = cpv.id)
					JOIN cup_creatives AS cc
						ON (cpv.id = cc.version_id)
					JOIN adset_requests AS ar
						ON (cpv.adset_id = ar.adset_id)
					JOIN cup_adsets AS ca
						ON (cpv.adset_id=ca.id)
				WHERE
					cv.adset_request_id IS NULL
					AND cc.ad_tag IS NOT NULL
					AND sp.mpq_id = ?
			";

		$response = $this->db->query($query, array($mpq_id));

		if ($response !== false && $response->num_rows() > 0)
		{
			return $response->result_array();
		}

		return false;
	}

	public function get_dfp_object_template_for_zips($zips, $object_type)
	{
		if($object_type == "LINE_ITEM")
		{
			$object_type = 1;
		}
		else
		{
			$object_type = 0;
		}
		$query_likes = array();
		$bindings = array();
		foreach($zips as $zip)
		{
			$query_likes[] = "allowed_geo_list LIKE ?";
			$bindings[] = "%".$zip."%";
		}
		if(!empty($query_likes))
		{
			$query_likes = implode(" OR ", $query_likes);
			$get_line_item_template_query = 
			"SELECT
				dot.id AS id
			FROM
				dfp_object_templates AS dot
			WHERE
				({$query_likes})
				AND type = ?
			";
			$bindings[] = $object_type;
			$get_line_item_template_result = $this->db->query($get_line_item_template_query, $bindings);
			if($get_line_item_template_result == false)
			{
				return false;
			}
			if($get_line_item_template_result->num_rows() == 0)
			{
				return null;
			}
			$row = $get_line_item_template_result->row_array();
			return $row['id'];
		}
		else
		{
			return false;
		}
	}


	public function save_dfp_object_template_for_submitted_product($object_template_id, $submitted_product_id, $object_type_id)
	{
		if($object_type_id == "ORDER")
		{
			$save_field = "dfp_object_templates_order_id";
		}
		else if ($object_type_id == "LINE_ITEM")
		{
			$save_field = "dfp_object_templates_lineitem_id";			
		}
		else
		{
			return false;
		}

		$save_template_id_query = 
		"UPDATE
			io_flight_subproduct_details
		SET 
			{$save_field} = ?
		WHERE
			io_timeseries_id IN 
			(SELECT 
				id 
			FROM 
				campaigns_time_series
			WHERE 
				campaigns_id = ?
				AND object_type_id = 1)
		";
		$bindings = array($object_template_id, $submitted_product_id);
		$save_template_id_result = $this->db->query($save_template_id_query, $bindings);
		if($save_template_id_result == false)
		{
			return false;
		}
		return true;
	}

	//Method that will forecast for all mpq-related timeseries rows
	public function forecast_dfp_order_rows_for_io($mpq_id)
	{
		$add_order_detail_query = 
		"SELECT
			idod.io_timeseries_id AS io_timeseries_id,
			idod.o_o_impressions AS target_impressions,
			idod.dfp_object_templates_lineitem_id AS order_template_id,
			ts.series_date AS series_date
		FROM
			io_flight_subproduct_details AS idod
			JOIN campaigns_time_series AS ts
				ON idod.io_timeseries_id = ts.id
			JOIN cp_submitted_products AS csp
				ON ts.campaigns_id = csp.id
				AND ts.object_type_id = 1
		WHERE
			csp.mpq_id = ?
		ORDER BY 
			series_date ASC
		";	

		$add_order_detail_result = $this->db->query($add_order_detail_query, $mpq_id);


		if($add_order_detail_result == false)
		{
                    return false;
		}
		$order_rows = $add_order_detail_result->result_array();
		$update_bindings = array();
		foreach($order_rows as $index => $order_row)
		{
			if($index < count($order_rows) && $order_row['target_impressions'] > 0)
			{
				$next_row = $order_rows[$index+1];

				$start_date = $order_row['series_date'];
				$end_date = $next_row['series_date'];
				$target_impressions = $order_row['target_impressions'];
				$timeseries_id = $order_row['io_timeseries_id'];

				$forecast = $this->google_api->get_availability_forecast(
				null,
				$start_date,
				$end_date,
				'CPM',
				 $target_impressions,
				 'IMPRESSIONS'					
				);

				if($forecast['success'] == false)
				{
					return false;
				}

				$possible_units = $forecast['data']->possibleUnits;

				$update_bindings[] = array($possible_units, $timeseries_id);
			}
		}

		if(!empty($update_bindings))
		{
			foreach($update_bindings AS $bindings)
			{
				$update_dfp_details_query =
				"UPDATE 
					io_flight_subproduct_details
				SET
					o_o_impressions_from_dfp = ?
				WHERE
					io_timeseries_id = ?
				";
				$update_dfp_details_result = $this->db->query($update_dfp_details_query, $bindings);
			}
			return true;
		}
		return false;
	}

	//Method for updating the forecast on a single row
	public function forecast_single_product($mpq_id, $cp_submitted_product_id)
	{
		$retrieve_o_o_timeseries_data_query =		
		"SELECT
			idod.io_timeseries_id AS io_timeseries_id,
			idod.o_o_impressions AS target_impressions,
			idod.dfp_object_templates_lineitem_id AS order_template_id,
			ts.series_date AS series_date
		FROM
			io_flight_subproduct_details AS idod
			JOIN campaigns_time_series AS ts
				ON idod.io_timeseries_id = ts.id
			JOIN cp_submitted_products AS csp
				ON ts.campaigns_id = csp.id
				AND ts.object_type_id = 1
		WHERE
			csp.mpq_id = ?
		AND 
			csp.id = ?	
		ORDER BY 
			series_date ASC
		";

		$retrieve_o_o_timeseries_data_result = $this->db->query($retrieve_o_o_timeseries_data_query, array($mpq_id,$cp_submitted_product_id));


		if($retrieve_o_o_timeseries_data_result == false)
		{
                    return false;
		}		
		$order_rows = $retrieve_o_o_timeseries_data_query->result_array();
		$update_bindings = array();
		
		foreach($order_rows as $index => $order_row)
		{
			if($index < count($order_rows) && $order_row['target_impressions'] > 0)
			{
				$next_row = $order_rows[$index+1];

				$start_date = $order_row['series_date'];
				$end_date = $next_row['series_date'];
				$target_impressions = $order_row['target_impressions'];
				$timeseries_id = $order_row['io_timeseries_id'];

				$forecast = $this->google_api->get_availability_forecast(
				null,
				$start_date,
				$end_date,
				'CPM',
				 $target_impressions,
				 'IMPRESSIONS'					
				);

				if($forecast['success'] == false)
				{
					return false;
				}

				$possible_units = $forecast['data']->possibleUnits;

				$update_bindings[] = array($possible_units, $timeseries_id);
			}
		}

		if(!empty($update_bindings))
		{
			foreach($update_bindings AS $bindings)
			{
				$update_dfp_details_query =
				"UPDATE 
					io_flight_subproduct_details
				SET
					o_o_impressions_from_dfp = ?
				WHERE
					io_timeseries_id = ?
				";

				$this->db->query($update_dfp_details_query, $bindings);
			}
			return true;
		}
		return false;
	}

	//Method for updating the forecast on a single row
	public function forecast_individual_timeseries($mpq_id, $timeseries_id)
	{
		$retrieve_o_o_timeseries_data_query =
		"SELECT
			idod.io_timeseries_id AS io_timeseries_id,
			idod.o_o_impressions AS target_impressions,
			idod.dfp_object_templates_lineitem_id AS order_template_id,
			ts.series_date AS series_date
		FROM
			io_flight_subproduct_details AS idod
			JOIN campaigns_time_series AS ts
				ON idod.io_timeseries_id = ts.id
			JOIN cp_submitted_products AS csp
				ON ts.campaigns_id = csp.id
				AND ts.object_type_id = 1
		WHERE
			csp.mpq_id = ?
		ORDER BY 
			series_date ASC
		";
				
		$retrieve_o_o_timeseries_data_result = $this->db->query($retrieve_o_o_timeseries_data_query, $mpq_id);

		if($retrieve_o_o_timeseries_data_result == false)
		{
			return false;
		}

		$io_timeseries = $retrieve_o_o_timeseries_data_result->result_array();

		foreach($io_timeseries AS $index => $timeseries)
		{
			if($timeseries['io_timeseries_id'] == $timeseries_id)
			{
				$next_row = $io_timeseries[$index+1];

				$start_date = $timeseries['series_date'];
				$end_date = $next_row['series_date'];
				$target_impressions = $timeseries['target_impressions'];

				$forecast = $this->google_api->get_availability_forecast(
				null,
				$start_date,
				$end_date,
				'CPM',
				 $target_impressions,
				 'IMPRESSIONS'					
				);

				if($forecast['success'] == false)
				{
					return false;
				}

				$possible_units = $forecast['data']->possibleUnits;	

				$bindings = array($possible_units, $timeseries_id);
				$update_dfp_details_query =
				"UPDATE 
					io_flight_subproduct_details
				SET
					o_o_impressions_from_dfp = ?
				WHERE
					io_timeseries_id = ?
				";

				$update_dfp_details_result = $this->db->query($update_dfp_details_query, $bindings);
				if($update_dfp_details_result == false)
				{
					return false;
				}
				return true;
			}
			return false;
		}
	}

	public function insert_single_flight_for_submitted_product($campaigns_id, $start_date, $end_date, $budget, $o_o_enabled, $is_campaign = false, $needs_end = false)
	{

		if($is_campaign)
		{
			$object_type_id = 0;
		}
		else
		{
			$object_type_id = 1;
		}
		$return_flights = array();
		$insert_single_flight_query = 
		"INSERT INTO
			campaigns_time_series
				(campaigns_id, series_date, impressions, budget, object_type_id)
		VALUES
			(?, ?, ?, ?, ?)
		ON DUPLICATE KEY UPDATE
			budget = VALUES(budget),
			impressions = VALUES(impressions)
		";
		$bindings = array($campaigns_id, $start_date, 0 ,$budget, $object_type_id);
		$insert_single_flight_result = $this->db->query($insert_single_flight_query, $bindings);
		
		if($insert_single_flight_result == false)
		{
			return false;
		}

		//Because we might have just updated, insert_id might not work so we'll just get it
		$get_flight_id_query = 
		"SELECT
			id
		FROM
			campaigns_time_series
		WHERE
			campaigns_id = ?
			AND series_date = ?
		";
		$bindings = array($campaigns_id, $start_date);
		$get_flight_id_result = $this->db->query($get_flight_id_query, $bindings);
		if($get_flight_id_result == false)
		{
			return false;
		}
		$row = $get_flight_id_result->row_array();
		$added_flight_id = $row['id'];
		$return_flights[] = array('id'=>$added_flight_id, 'budget'=>$budget);

		if($needs_end)
		{
			$insert_single_flight_query = 
			"INSERT INTO
				campaigns_time_series
					(campaigns_id, series_date, impressions, budget, object_type_id)
			VALUES
				(?, ?, 0, 0, ?)
			ON DUPLICATE KEY UPDATE
				budget = VALUES(budget),
				impressions = VALUES(impressions)
			";
			$bindings = array($campaigns_id, date('Y-m-d', strtotime($end_date." +1 days")), $object_type_id);
			$insert_single_flight_result = $this->db->query($insert_single_flight_query, $bindings);
			if($insert_single_flight_result == false)
			{
				return false;
			}

			$get_flight_id_query = 
			"SELECT
				id
			FROM
				campaigns_time_series
			WHERE
				campaigns_id = ?
				AND series_date = ?
			";
			$get_flight_id_result = $this->db->query($get_flight_id_query, $bindings);
			if($get_flight_id_result == false)
			{
				return false;
			}
			$row = $get_flight_id_result->row_array();
			$ending_flight_id = $row['id'];
			$return_flights[] = array('id'=>$ending_flight_id, 'budget'=>0);	
		}

		$add_order_detail_rows_query = 
		"INSERT INTO 
			io_flight_subproduct_details
				(io_timeseries_id,
				updated,
				o_o_impressions,
				o_o_impressions_from_dfp,
				geofencing_impressions,
				audience_ext_impressions,
				response_o_o_dfp_budget,
				geofencing_budget,
				audience_ext_budget,
				dfp_object_templates_order_id,
				dfp_object_templates_lineitem_id, 
				dfp_status)
			(SELECT
				ts.id,
				NOW(),
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				NULL,
				NULL, 
				?
			FROM
				campaigns_time_series AS ts
			WHERE
				ts.id = ?)
			ON DUPLICATE KEY UPDATE
				updated = VALUES(updated),
				o_o_impressions = VALUES(o_o_impressions),
				o_o_impressions_from_dfp = VALUES(o_o_impressions_from_dfp),
				geofencing_impressions = VALUES(geofencing_impressions),
				audience_ext_impressions = VALUES(audience_ext_impressions),
				response_o_o_dfp_budget = VALUES(response_o_o_dfp_budget),
				geofencing_budget = VALUES(geofencing_budget),
				audience_ext_budget = VALUES(audience_ext_budget),
				dfp_object_templates_order_id = VALUES(dfp_object_templates_order_id),
				dfp_object_templates_lineitem_id = VALUES(dfp_object_templates_lineitem_id), 				
				dfp_status = VALUES(dfp_status)
		";
		if($o_o_enabled)
		{
			$dfp_status = "PENDING";
		}
		else
		{
			$dfp_status = "COMPLETE";
		}
		$bindings = array($dfp_status, $added_flight_id);
		$add_order_detail_rows_result = $this->db->query($add_order_detail_rows_query, $bindings);
		if($add_order_detail_rows_result == false)
		{
			return false;
		}
		if($needs_end)
		{
			$add_order_detail_rows_query = 
			"INSERT INTO 
				io_flight_subproduct_details
				(io_timeseries_id, updated, dfp_status)
				(SELECT
					ts.id,
					NOW(),
					\"COMPLETE\"
				FROM
					campaigns_time_series AS ts
				WHERE
					ts.id = ?)
				ON DUPLICATE KEY UPDATE
					updated = VALUES(updated),
					o_o_impressions = 0,
					o_o_impressions_from_dfp = 0,
					geofencing_impressions = 0,
					geofencing_budget = 0,
					audience_ext_budget = 0,
					audience_ext_impressions = 0,
					response_o_o_dfp_budget = 0					
			";
			$add_order_detail_rows_result = $this->db->query($add_order_detail_rows_query, $ending_flight_id);
			if($add_order_detail_rows_result == false)
			{
				return false;
			}			
		}
		return $return_flights;
	}

	public function delete_flights_for_io($flight_ids)
	{
		foreach($flight_ids as $flight_id)
		{
			$get_flight_data_query = 
			"SELECT
				 *
			FROM 
				campaigns_time_series
			WHERE 
				campaigns_id = (SELECT campaigns_id FROM campaigns_time_series WHERE id = ?)
			";

			$get_flight_data_result = $this->db->query($get_flight_data_query, $flight_id);
			if($get_flight_data_result == false)
			{
				return false;
			}
			$submitted_product_flights = $get_flight_data_result->result_array();
			if($flight_id == $submitted_product_flights[0]['id'])
			{
				$delete_flight = $this->delete_single_flight($submitted_product_flights[0]['id']);
				unset($submitted_product_flights[0]);
				foreach($submitted_product_flights as $idx => $submitted_product_flight)
				{
					if($submitted_product_flight['budget'] == 0)
					{
						$delete_flight = $this->delete_single_flight($submitted_product_flight['id']);
					}
					else
					{
						break;
					}
				}
			}
			elseif($flight_id == $submitted_product_flights[count($submitted_product_flights)-2]['id'])
			{
				$delete_flight = $this->delete_single_flight($submitted_product_flights[count($submitted_product_flights)-1]['id']);
				$nullified_flight = $this->nullify_flight( $submitted_product_flights[count($submitted_product_flights)-2]['id']);
			}
			else
			{
				foreach($submitted_product_flights as $idx => $submitted_product_flight)
				{
					if($submitted_product_flight['id'] == $flight_id)
					{
						//Set this one to 0, delete the next one if it's 0
						$nullified_flight = $this->nullify_flight($submitted_product_flight['id']);
					}
				}
			}
		}
		return true;
	}

	private function nullify_flight($flight_id)
	{
		$nullify_subproducts_query = 
		"UPDATE
			io_flight_subproduct_details
		SET
			o_o_impressions = 0,
			o_o_impressions_from_dfp = 0,
			geofencing_impressions = 0,
			audience_ext_impressions = 0,
			response_o_o_dfp_budget = 0,
			geofencing_budget = 0,
			audience_ext_budget = 0,
			dfp_status = \"COMPLETE\"
		WHERE
			io_timeseries_id = ?
		";
		$nullify_subproducts_result = $this->db->query($nullify_subproducts_query, $flight_id);
		if($nullify_subproducts_result == false)
		{
			return false;
		}

		$nullify_flight_budget_query = 
		"UPDATE
			campaigns_time_series
		SET
			impressions = 0,
			budget = 0
		WHERE
			id = ?
		";
		$nullify_flight_budget_result = $this->db->query($nullify_flight_budget_query, $flight_id);
		if($nullify_flight_budget_result == false)
		{
			return false;
		}
		return true;
	}

	private function delete_single_flight($flight_id)
	{
		$delete_flight_subproduct_data_query =
		"DELETE FROM 
			io_flight_subproduct_details
		WHERE
			io_timeseries_id = ?
		";

		$delete_flight_subproduct_data_result = $this->db->query($delete_flight_subproduct_data_query, $flight_id);

		if($delete_flight_subproduct_data_result == false)
		{
			return false;
		}

		$delete_flight_query =
		"DELETE FROM
			campaigns_time_series
		WHERE id = ?
		";
		$delete_flight_result = $this->db->query($delete_flight_query, $flight_id);
		if($delete_flight_result == false)
		{
			return false;
		}
		return true;
	}

	public function reforecast_submitted_product($submitted_product_id)
	{
		$reset_forecast_status_query = 
		"UPDATE 
			io_flight_subproduct_details AS ifsd
			JOIN campaigns_time_series AS cts
				ON (ifsd.io_timeseries_id = cts.id)
		SET
			ifsd.dfp_status = \"PENDING\",
			ifsd.dfp_response = NULL,
			ifsd.o_o_impressions_from_dfp = NULL
		WHERE
			cts.campaigns_id = ?
			AND cts.object_type_id = 1
		";

		$reset_forecast_status_result = $this->db->query($reset_forecast_status_query, $submitted_product_id);
		if($reset_forecast_status_result == false)
		{
			return false;
		}
		return true;
	}

	public function translate_subproduct_prefix($subproduct_prefix)
	{
		switch($subproduct_prefix)
		{
			case "ax":
				$subproduct_name = "DISPLAY";
				break;
			case "gf":
				$subproduct_name = "GEOFENCING";
				break;
			case "o_o":
				$subproduct_name = "O_O_DISPLAY";
				break;
			case "preroll":
				$subproduct_name = "PREROLL";
				break;
			default:
				return false;
				break;
		}

		$get_subproduct_type_id_query = 
		"SELECT
			id
		FROM
			subproduct_types
		WHERE 
			name = ?
		";

		$get_subproduct_type_id_result = $this->db->query($get_subproduct_type_id_query, $subproduct_name);
		if($get_subproduct_type_id_result == false || $get_subproduct_type_id_result->num_rows < 1)
		{
			return false;
		}
		$row = $get_subproduct_type_id_result->row_array();
		return $row['id'];

	}

	public function insert_flights_with_budgets_for_submitted_product($cp_submitted_product_id, $flights_array)
	{
		if(empty($flights_array))
		{
			return false; 
		}

		$values = array();
		$bindings = array();
		foreach($flights_array AS $flight)
		{
			$values[] = ("(?, ?, 1, ?)");
			$bindings[] = $cp_submitted_product_id;
			$bindings[] = date("Y-m-d", strtotime($flight['start_date']));
			$bindings[] = $flight['budget'];
		}

		$values = implode(", ", $values);
		$insert_flights_sql = 
		"INSERT INTO
			campaigns_time_series
			(campaigns_id, series_date, object_type_id, budget)
		VALUES ".$values;

		$insert_flights_result = $this->db->query($insert_flights_sql, $bindings);
		if($insert_flights_result == false)
		{
			return false;
		}

		$get_flights_query =
		"SELECT
			*
		FROM 
			campaigns_time_series
		WHERE 
			campaigns_id = ?
			AND object_type_id = 1
		";

		$get_flights_result = $this->db->query($get_flights_query, $cp_submitted_product_id);

		if($get_flights_result == false || $get_flights_result->num_rows() < 1)
		{
			return false;
		}
		return $get_flights_result->result_array();
	}

	public function insert_flights_with_budgets_for_campaign($campaign_id, $flights_array)
	{
		if(empty($flights_array))
		{
			return false; 
		}

		$values = array();
		$bindings = array();
		foreach($flights_array AS $flight)
		{
			$values[] = ("(?, ?, 0, ?)");
			$bindings[] = $campaign_id;
			$bindings[] = date("Y-m-d", strtotime($flight['start_date']));
			$bindings[] = $flight['budget'];
		}

		$values = implode(", ", $values);
		$insert_flights_sql = 
		"INSERT INTO
			campaigns_time_series
			(campaigns_id, series_date, object_type_id, budget)
		VALUES ".$values;

		$insert_flights_result = $this->db->query($insert_flights_sql, $bindings);
		if($insert_flights_result == false)
		{
			return false;
		}

		$get_flights_query =
		"SELECT
			*
		FROM 
			campaigns_time_series
		WHERE 
			campaigns_id = ?
			AND object_type_id = 0
		";

		$get_flights_result = $this->db->query($get_flights_query, $campaign_id);

		if($get_flights_result == false || $get_flights_result->num_rows() < 1)
		{
			return false;
		}
		return $get_flights_result->result_array();
	}

	public function add_dfp_order_rows_for_submitted_product($cp_submitted_product_id, $o_o_enabled)
	{
		$add_order_detail_rows_query = 
		"INSERT INTO 
			io_flight_subproduct_details
			(io_timeseries_id, updated, o_o_impressions, dfp_status)
			(SELECT
				ts.id,
				NOW(),
				(ts.impressions * 1),
				?
			FROM
				campaigns_time_series AS ts
				JOIN cp_submitted_products AS sp
					ON ts.campaigns_id = sp.id
					AND ts.object_type_id = 1

			WHERE
				sp.id = ?)
			ON DUPLICATE KEY UPDATE
				updated = VALUES(updated),
				o_o_impressions = VALUES(o_o_impressions)
		";
		if($o_o_enabled)
		{
			$dfp_status = "PENDING";
		}
		else
		{
			$dfp_status = "COMPLETE";
		}

		$bindings = array($dfp_status, $cp_submitted_product_id);
		$add_order_detail_rows_result = $this->db->query($add_order_detail_rows_query, $bindings);
		if($add_order_detail_rows_result == false)
		{
			return false;
		}
		return true;
	}

	public function add_dfp_order_rows_for_campaign($campaign_id, $o_o_enabled)
	{
		$add_order_detail_rows_query = 
		"INSERT INTO 
			io_flight_subproduct_details
			(io_timeseries_id, updated, o_o_impressions, dfp_status)
			(SELECT
				ts.id,
				NOW(),
				(ts.impressions * 1),
				?
			FROM
				campaigns_time_series AS ts
				JOIN Campaigns AS cmp
					ON ts.campaigns_id = cmp.id
					AND ts.object_type_id = 0

			WHERE
				cmp.id = ?)
			ON DUPLICATE KEY UPDATE
				updated = VALUES(updated),
				o_o_impressions = VALUES(o_o_impressions)
		";
		if($o_o_enabled)
		{
			$dfp_status = "PENDING";
		}
		else
		{
			$dfp_status = "COMPLETE";
		}

		$bindings = array($dfp_status, $campaign_id);
		$add_order_detail_rows_result = $this->db->query($add_order_detail_rows_query, $bindings);
		if($add_order_detail_rows_result == false)
		{
			return false;
		}
		return true;
	}




	public function add_dfp_order_rows_for_flight($flight_id)
	{
		$add_order_detail_row_query = 
		"INSERT INTO 
			io_flight_subproduct_details
			(io_timeseries_id, updated)
			(SELECT 
				ts.id,
				NOW()
			FROM
				campaigns_time_series AS ts
			WHERE
				ts.id = ?)
			ON DUPLICATE KEY UPDATE
				updated = VALUES(updated)
		";
		$add_order_detail_rows_result = $this->db->query($add_order_detail_row_query, $flight_id);
		if($add_order_detail_rows_result == false)
		{
			return false;
		}
		return true;		
	}

	public function insert_product_cpms_for_mpq_submitted_product($mpq_id, $submitted_product_id, $product_identifier, $for_campaign = false)
	{
		$return_cpms = array('ax'=>0, 'o_o'=>0, 'gf'=>0, 'gf_max_dollar_pct'=>null);
		$get_product_information_query = 
		"SELECT
			cp.*
		FROM
			cp_products AS cp
			JOIN cp_submitted_products AS csp
				ON (cp.id = csp.product_id)
		WHERE
			csp.id = ?
		";
		
		$get_product_information_result = $this->db->query($get_product_information_query, $submitted_product_id);

		if($get_product_information_result == false)
		{
			return false;
		}
		$product_info = $get_product_information_result->row_array();
		$product_definition = json_decode($product_info['definition']);

		$get_mpq_option_discount_query = 
		"SELECT 
			discount
		FROM 
			mpq_options
		WHERE
			mpq_id = ?
		";

		$get_mpq_option_discount_result = $this->db->query($get_mpq_option_discount_query, $mpq_id);

		if($get_mpq_option_discount_result == false)
		{
			return false;
		}

		if($get_mpq_option_discount_result->num_rows() < 1)
		{
			$discount_percent = 0;
		}
		else
		{
			$row = $get_mpq_option_discount_result->row_array();
			$discount_percent = $row['discount'];
		}

		$get_submitted_product_definitions_query = 
		"SELECT
			*
		FROM
			cp_submitted_products
		WHERE
			id = ?
		";

		$get_submitted_product_definitions_result = $this->db->query($get_submitted_product_definitions_query, $submitted_product_id);

		if($get_submitted_product_definitions_result == false)
		{
			return false;
		}
		$submitted_product = $get_submitted_product_definitions_result->row_array();

		$ax_bindings = array();
		$o_o_bindings = array();
		$gf_bindings = array();
		$values = array();

		if($product_identifier == "display")
		{
			//Display
			$submitted_values = json_decode($submitted_product['submitted_values']);
			if(property_exists($submitted_values, 'cpm'))
			{
				$base_cpm = $submitted_values->cpm;
			}
			else
			{
				//Replace with the default from the product definiition from the submitted product

				$base_cpm = $product_definition->options[0]->cpm->default;
			}
			$effective_cpm = $base_cpm * (100 - $discount_percent)*0.01;
			$ax_bindings = array($submitted_product_id, 1, $effective_cpm, null);
			$values[] = "(?, ?, ?, ?)";
			$return_cpms['ax'] = $effective_cpm;

			//O&O
			if($product_info['o_o_enabled'] == 1)
			{
				$o_o_bindings = array($submitted_product_id, 4, $effective_cpm, null);
				$values[] = "(?, ?, ?, ?)";
				$return_cpms['o_o'] = $effective_cpm;

			}
			//Geofencing
			$effective_gf_cpm = null;
			if(property_exists($submitted_values, "geofence_cpm"))
			{
				$effective_gf_cpm = $submitted_values->geofence_cpm * (100 - $discount_percent)*0.01;
			}
			else
			{
				if(property_exists($product_definition, "geofencing"))
				{
					$effective_gf_cpm = $product_definition->geofencing->default_cpm * (100 - $discount_percent)*0.01;
				}
			}

			if($effective_gf_cpm != null)
			{
				$max_percentage = $product_definition->geofencing->max_percent;
				$gf_bindings = array($submitted_product_id, 3, $effective_gf_cpm, $max_percentage);
				$values[] = "(?, ?, ?, ?)";
				$return_cpms['gf'] = $effective_gf_cpm;
				$return_cpms['gf_max_dollar_pct'] = $max_percentage;
			}
		}
		elseif ($product_identifier == "preroll")
		{
			//Preroll
			$submitted_values = json_decode($submitted_product['submitted_values']);
			if(property_exists($submitted_values, 'cpm'))
			{
				$base_cpm = $submitted_values->cpm;
			}
			else
			{
				//Replace with the default from the product definiition from the submitted product

				$base_cpm = $product_definition->options[0]->cpm->default;
			}
			$effective_cpm = $base_cpm * (100 - $discount_percent)*0.01;
			$ax_bindings = array($submitted_product_id, 2, $effective_cpm, null);
			$values[] = "(?, ?, ?, ?)";
			$return_cpms['ax'] = $effective_cpm;
		}

		$values = implode(", ", $values);

		if($for_campaign)
		{
			$identifier_field = "campaign_id";
		}
		else
		{
			$identifier_field = "cp_submitted_products_id";
		}

		$bindings = array_merge($ax_bindings, $o_o_bindings, $gf_bindings);
		$insert_submitted_product_cpm_query = 
		"INSERT INTO
			io_campaign_product_cpm
				({$identifier_field}, subproduct_type_id, dollar_cpm, max_dollar_pct)
		VALUES 
			{$values}
		";
		$insert_submitted_product_cpm_result = $this->db->query($insert_submitted_product_cpm_query, $bindings);
		if($insert_submitted_product_cpm_result == false)
		{
			return false;
		}
		return $return_cpms; 
	}

	public function update_product_cpm_for_submitted_product($submitted_product_id, $subproduct_type_id, $cpm)
	{
		$update_cpm_query =
		"INSERT INTO 
			io_campaign_product_cpm
			(cp_submitted_products_id, subproduct_type_id, dollar_cpm)
		VALUES
			(?, ?, ?)
		ON DUPLICATE KEY UPDATE
			dollar_cpm = VALUES(dollar_cpm)
		";
		$bindings = array($submitted_product_id, $subproduct_type_id, $cpm);
		$update_cpm_result = $this->db->query($update_cpm_query, $bindings);
		if($update_cpm_result == false)
		{
			return false;
		}
		return true;
	}

	public function update_product_cpm_for_campaign($campaign_id, $subproduct_type_id, $cpm)
	{
		$update_cpm_query =
		"INSERT INTO 
			io_campaign_product_cpm
			(campaign_id, subproduct_type_id, dollar_cpm)
		VALUES
			(?, ?, ?)
		ON DUPLICATE KEY UPDATE
			dollar_cpm = VALUES(dollar_cpm)
		";
		$bindings = array($campaign_id, $subproduct_type_id, $cpm);
		$update_cpm_result = $this->db->query($update_cpm_query, $bindings);
		if($update_cpm_result == false)
		{
			return false;
		}
		return true;
	}	

	public function retrieve_product_cpms_for_submitted_product($submitted_product_id)
	{
		$return_cpms = array('ax'=>0, 'o_o'=>0, 'gf'=>0, 'gf_max_dollar_pct'=>null);
		$get_cpms_query =
		"SELECT
			icpc.*,
			st.name AS subproduct_type
		FROM 
			io_campaign_product_cpm AS icpc
			JOIN subproduct_types AS st
				ON(icpc.subproduct_type_id = st.id)
		WHERE
			cp_submitted_products_id = ?
		";
		$get_cpms_result = $this->db->query($get_cpms_query, $submitted_product_id);
		if($get_cpms_result == false || $get_cpms_result->num_rows < 1)
		{
			return false;
		}
		$result_array = $get_cpms_result->result_array();
		foreach($result_array as $cpm_details)
		{
			switch($cpm_details['subproduct_type'])
			{
				case "DISPLAY":
					$return_cpms['ax'] = $cpm_details['dollar_cpm'];
					break;
				case "GEOFENCING":
					$return_cpms['gf'] = $cpm_details['dollar_cpm'];
					$return_cpms['gf_max_dollar_pct'] = $cpm_details['max_dollar_pct'];
					break;
				case "O_O_DISPLAY":
					$return_cpms['o_o'] = $cpm_details['dollar_cpm'];
					break;
				case "PREROLL":
					$return_cpms['ax'] = $cpm_details['dollar_cpm'];
					break;
				default:
					break;
			}
		}
		return $return_cpms;
	}

	public function retrieve_product_cpms_for_campaign($campaign_id)
	{
		$return_cpms = array('ax'=>0, 'o_o'=>0, 'gf'=>0, 'gf_max_dollar_pct'=>null);
		$get_cpms_query =
		"SELECT
			icpc.*,
			st.name AS subproduct_type
		FROM 
			io_campaign_product_cpm AS icpc
			JOIN subproduct_types AS st
				ON(icpc.subproduct_type_id = st.id)
		WHERE
			campaign_id = ?
		";
		$get_cpms_result = $this->db->query($get_cpms_query, $campaign_id);
		if($get_cpms_result == false || $get_cpms_result->num_rows < 1)
		{
			return false;
		}
		$result_array = $get_cpms_result->result_array();
		foreach($result_array as $cpm_details)
		{
			switch($cpm_details['subproduct_type'])
			{
				case "DISPLAY":
					$return_cpms['ax'] = $cpm_details['dollar_cpm'];
					break;
				case "GEOFENCING":
					$return_cpms['gf'] = $cpm_details['dollar_cpm'];
					$return_cpms['gf_max_dollar_pct'] = $cpm_details['max_dollar_pct'];
					break;
				case "O_O_DISPLAY":
					$return_cpms['o_o'] = $cpm_details['dollar_cpm'];
					break;
				case "PREROLL":
					$return_cpms['ax'] = $cpm_details['dollar_cpm'];
					break;
				default:
					break;
			}
		}
		return $return_cpms;
	}


	public function retrieve_budget_data_for_flight($flight_ids)
	{
		$ids = array();
		foreach($flight_ids as $flight_id)
		{
			$ids[] = "?";
		}
		$ids = implode(',', $ids);

		$get_flight_budget_data_query =
		"SELECT
			*
		FROM
			io_flight_subproduct_details
		WHERE
			io_timeseries_id IN ({$ids})
		";

		$get_flight_budget_data_result = $this->db->query($get_flight_budget_data_query, $flight_ids);

		if($get_flight_budget_data_result == false)
		{
			return false;
		}

		$budget_data = array(
			"ax_impressions" => 0,
			"ax_budget" => 0,
			"o_o_impressions" => 0,
			"o_o_budget" => 0,
			"o_o_forecast_impressions" => 0,
			"gf_impressions" => 0,
			"gf_budget" => 0,
			"forecast_status" => "COMPLETE");
		$flight_rows = $get_flight_budget_data_result->result_array();
		foreach($flight_rows AS $flight_row)
		{
			$budget_data["ax_impressions"] += $flight_row['audience_ext_impressions'];
			$budget_data["ax_budget"] += $flight_row['audience_ext_budget'] + $flight_row['geofencing_budget'];
			$budget_data["o_o_impressions"] += $flight_row['o_o_impressions'];
			$budget_data["o_o_budget"] += $flight_row['response_o_o_dfp_budget'];
			$budget_data["o_o_forecast_impressions"] += $flight_row['o_o_impressions_from_dfp'];
			$budget_data["gf_impressions"] += $flight_row['geofencing_impressions'];
			$budget_data["gf_budget"] += $flight_row['geofencing_budget'];
			if($flight_row['dfp_status'] != "COMPLETE")
			{
				$budget_data["forecast_status"] = $flight_row['dfp_status'];
			}
		}

		return $budget_data;

	}

	public function does_campaign_have_geofencing($campaign_id)
	{
		$get_geofencing_adgroups_query = 
		"SELECT
			adg.* 
		FROM
			AdGroups AS adg
			JOIN subproduct_types AS st
				ON (adg.subproduct_type_id = st.id)
		WHERE
			campaign_id = ?
			AND st.name LIKE \"GEOFENCING\"
		";

		$get_geofencing_adgroups_result = $this->db->query($get_geofencing_adgroups_query, $campaign_id);
		if($get_geofencing_adgroups_result == false || $get_geofencing_adgroups_result->num_rows() < 1);
		{
			return false;
		}
		return true;
	}

	public function update_flight_budget_for_flight($flight_id, $budget)
	{
		$update_flight_budget_query = 
		"UPDATE 
			campaigns_time_series
		SET 
			budget = ?
		WHERE
			id = ?
		";

		$bindings = array($budget, $flight_id);
		$update_flight_budget_result = $this->db->query($update_flight_budget_query, $bindings);
		if($update_flight_budget_result == false)
		{
			return false;
		}
		return true;
	}

	public function update_flight_budget_for_flight_id($flight_id, $budget)
	{
		$get_flight_query = 
		"SELECT
			*
		FROM 
			campaigns_time_series
		WHERE
			id = ?
		";
		$bindings = array($flight_id);
		$get_flight_result = $this->db->query($get_flight_query, $bindings);
		if($get_flight_result == false)
		{
			return false;
		}
		$flight = $get_flight_result->row_array();
		$flight['budget'] = $budget;

		$update_flight_query = 
		"UPDATE
			campaigns_time_series
		SET 
			budget = ?
		WHERE
			id = ?
		";

		$bindings = array($budget, $flight_id);
		$update_flight_result = $this->db->query($update_flight_query, $bindings);
		if($update_flight_result == false)
		{
			return false;
		}
		return $flight;
	}

	public function save_budget_calcs_for_flight($flight_id, $o_o_impressions, $budget_values, $o_o_dfp)
	{
		$updated_values = array();
		$bindings = array();
		if($budget_values['geofencing_budget'] > 0 && $budget_values['geofencing_impressions'] > 0)
		{
			$updated_values[] = "geofencing_budget = ?";
			$bindings[] = $budget_values['geofencing_budget'];
			$updated_values[] = "geofencing_impressions = ?";
			$bindings[] = $budget_values['geofencing_impressions'];
		}
		else
		{
			$updated_values[] = "geofencing_budget = NULL";
			$updated_values[] = "geofencing_impressions = NULL";
		}

		if($budget_values['o_o_budget'] > 0 && $o_o_impressions > 0)
		{
			$updated_values[] = "o_o_impressions = ?";
			$bindings[] = $o_o_impressions;
			$updated_values[] = "response_o_o_dfp_budget = ?";
			$bindings[] = $budget_values['o_o_budget'];
		}
		else
		{
			$updated_values[] = "response_o_o_dfp_budget = NULL";
		}

		if(!$o_o_dfp)
		{
			$updated_values[] = "dfp_status = ?";
			$bindings[] = "COMPLETE";
		}

		if($budget_values['audience_ext_budget'] > 0 && $budget_values['audience_ext_impressions'] > 0)
		{
			$updated_values[] = "audience_ext_budget = ?";
			$bindings[] = $budget_values['audience_ext_budget'];
			$updated_values[] = "audience_ext_impressions = ?";
			$bindings[] = $budget_values['audience_ext_impressions'];
		}
		else
		{
			$updated_values[] = "audience_ext_budget = NULL";
			$updated_values[] = "audience_ext_impressions = NULL";
		}

		$updated_values = implode(",", $updated_values);
		$bindings[] = $flight_id;
		$save_flight_budget_values_query = 
		"UPDATE 
			io_flight_subproduct_details
		SET
			{$updated_values}
		WHERE
			io_timeseries_id = ?
		";

		$save_flight_budget_values_result = $this->db->query($save_flight_budget_values_query, $bindings);
		if($save_flight_budget_values_result == false)
		{
			return false;
		}

		$sum_budgets_query = 
		"UPDATE
			campaigns_time_series AS cts
			JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		SET
			cts.impressions = COALESCE(ifsd.audience_ext_impressions, 0) + COALESCE(ifsd.geofencing_impressions, 0)
		WHERE
			cts.id = ?
		";
		$sum_budgets_result = $this->db->query($sum_budgets_query, $flight_id);
		if($sum_budgets_result == false)
		{
			return false;
		}
		return true;
	}

	public function does_mpq_region_have_geofencing_points($mpq_id, $region_index)
	{
		$get_geofencing_points_query = 
		"SELECT
			*
		FROM
			proposal_geofencing_points
		WHERE
			mpq_id = ?
			AND location_id = ?
		";
		$bindings = array($mpq_id, $region_index);
		$get_geofencing_points_result = $this->db->query($get_geofencing_points_query, $bindings);
		if($get_geofencing_points_result == false || $get_geofencing_points_result->num_rows() < 1)
		{
			return false;
		}
		return true;
	}

	public function save_allocation_type_for_mpq_and_product($mpq_id, $product_id, $allocation_type)
	{
		$update_allocation_type_query =
		"UPDATE 
			cp_products_join_io
		SET
			io_budget_allocation_type = ?
		WHERE
			mpq_id = ?
			AND product_id = ?
		";
		$bindings = array($allocation_type, $mpq_id, $product_id);
		$update_allocation_type_result = $this->db->query($update_allocation_type_query, $bindings);
		if($update_allocation_type_result == false)
		{
			return false;
		}
		return true;
	}

	public function get_allocation_type_for_mpq_and_product($mpq_id, $product_id)
	{
		$get_allocation_type_query =
		"SELECT
			io_budget_allocation_type
		FROM 
			cp_products_join_io
		WHERE
			mpq_id = ?
			AND product_id = ?
		";
		$bindings = array($mpq_id, $product_id);
		$get_allocation_type_result = $this->db->query($get_allocation_type_query, $bindings);
		if($get_allocation_type_result == false)
		{
			return false;
		}
		if($get_allocation_type_result->num_rows() < 1)
		{
			return "per_pop";
		}
		$row = $get_allocation_type_result->row_array();
		return $row['io_budget_allocation_type'];		
	}

	public function get_select2_dfp_template_groups($search_term, $product_id)
	{
		$get_dfp_template_groups_query = 
		"SELECT
			id,
			name AS text
		FROM
			dfp_template_groups
		WHERE
			name LIKE ?
			AND product_id = ?
		";
		$bindings = array("%".$search_term."%", $product_id);
		$get_dfp_template_groups_result = $this->db->query($get_dfp_template_groups_query, $bindings);

		if($get_dfp_template_groups_result == false)
		{
			return false;
		}
		return $get_dfp_template_groups_result->result_array();
	}

	public function assign_template_group_to_submitted_product($submitted_product_id, $object_group_id)
	{
		$get_template_ids_query = 
		"SELECT
			order_template_id,
			line_item_template_id
		FROM
			dfp_template_groups
		WHERE
			id = ?
		";

		$get_template_ids_result = $this->db->query($get_template_ids_query, $object_group_id);
		if($get_template_ids_result == false || $get_template_ids_result->num_rows() < 1)
		{
			return false;
		}
		$templates = $get_template_ids_result->row_array();

		$update_assigned_templates_query = 
		"UPDATE 
			io_flight_subproduct_details AS ifsd
			JOIN campaigns_time_series AS cts
				ON (ifsd.io_timeseries_id = cts.id)
		SET 
			dfp_object_templates_order_id = ?,
			dfp_object_templates_lineitem_id = ?
		WHERE 
			cts.campaigns_id = ?
			AND cts.object_type_id = 1
		";
		$bindings = array($templates['order_template_id'], $templates['line_item_template_id'], $submitted_product_id);
		$updated_assigned_templates_result = $this->db->query($update_assigned_templates_query, $bindings);
		if($updated_assigned_templates_result == false)
		{
			return false;
		}
		return true;
	}

	public function get_flights_for_submitted_product($submitted_product_id)
	{
		$get_flights_query = 
		"SELECT
			cts.*,
			ifsd.io_timeseries_id AS io_timeseries_id,
			ifsd.o_o_impressions AS o_o_impressions,
			ifsd.o_o_impressions_from_dfp AS o_o_forecast_impressions,
			ifsd.response_o_o_dfp_budget AS o_o_budget,
			ifsd.geofencing_impressions AS gf_impressions,
			ifsd.geofencing_budget AS gf_budget,
			ifsd.audience_ext_impressions AS ax_impressions,
			ifsd.audience_ext_budget AS ax_budget,
			ifsd.dfp_object_templates_order_id AS dfp_object_templates_order_id,
			ifsd.dfp_object_templates_lineitem_id AS dfp_object_templates_lineitem_id,
			ifsd.dfp_status AS dfp_status
		FROM
			campaigns_time_series AS cts
			JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		WHERE
			cts.campaigns_id = ?
			AND cts.object_type_id = 1
		ORDER BY 
			series_date ASC
		";

		$get_flights_result = $this->db->query($get_flights_query, $submitted_product_id);
		if($get_flights_result == false)
		{
			return false;
		}

		$result_array = $get_flights_result->result_array();
		$flights_array = array();
		foreach($result_array as $idx => $flight_row)
		{
			if($idx !== count($result_array)-1)
			{
				if($flight_row['budget'] == 0)
				{
					continue;
				}
				$next_row = $result_array[$idx+1];
				$flight = array();
				$flight['id'] = $flight_row['id'];
				$flight['start_date'] = $flight_row['series_date'];
				$flight['end_date'] = date("Y-m-d", strtotime('-1 day', strtotime($next_row['series_date'])));
				$flight['total_budget'] = $flight_row['budget'];
				$flight['io_timeseries_id'] = $flight_row['io_timeseries_id'];
				$flight['o_o_impressions'] = $flight_row['o_o_impressions'];
				$flight['o_o_budget'] = $flight_row['o_o_budget'];
				$flight['o_o_forecast_impressions'] = $flight_row['o_o_forecast_impressions'];
				$flight['gf_impressions'] = $flight_row['gf_impressions'];
				$flight['gf_budget'] = $flight_row['gf_budget'];
				$flight['ax_impressions'] = $flight_row['ax_impressions'];
				$flight['ax_budget'] = $flight_row['ax_budget'] + $flight_row['gf_budget'];
				$flight['dfp_object_templates_order_id'] = $flight_row['dfp_object_templates_order_id'];
				$flight['dfp_object_templates_lineitem_id'] = $flight_row['dfp_object_templates_lineitem_id'];
				$flight['dfp_status'] = $flight_row['dfp_status'];
				$flights_array[] = $flight;
			}
		}
		return $flights_array;
	}

	public function get_flights_for_campaign($campaign_id)
	{
		$get_flights_query = 
		"SELECT
			cts.*,
			ifsd.io_timeseries_id AS io_timeseries_id,
			ifsd.o_o_impressions AS o_o_impressions,
			ifsd.o_o_impressions_from_dfp AS o_o_forecast_impressions,
			ifsd.response_o_o_dfp_budget AS o_o_budget,
			ifsd.geofencing_impressions AS gf_impressions,
			ifsd.geofencing_budget AS gf_budget,
			ifsd.audience_ext_impressions AS ax_impressions,
			ifsd.audience_ext_budget AS ax_budget,
			ifsd.dfp_object_templates_order_id AS dfp_object_templates_order_id,
			ifsd.dfp_object_templates_lineitem_id AS dfp_object_templates_lineitem_id,
			ifsd.dfp_status AS dfp_status
		FROM
			campaigns_time_series AS cts
			JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		WHERE
			cts.campaigns_id = ?
			AND cts.object_type_id = 0
		ORDER BY 
			series_date ASC
		";

		$get_flights_result = $this->db->query($get_flights_query, $campaign_id);
		if($get_flights_result == false)
		{
			return false;
		}

		$result_array = $get_flights_result->result_array();
		$flights_array = array();
		foreach($result_array as $idx => $flight_row)
		{
			if($idx !== count($result_array)-1)
			{
				if($flight_row['budget'] == 0)
				{
					continue;
				}
				$next_row = $result_array[$idx+1];
				$flight = array();
				$flight['id'] = $flight_row['id'];
				$flight['start_date'] = $flight_row['series_date'];
				$flight['end_date'] = date("Y-m-d", strtotime('-1 day', strtotime($next_row['series_date'])));
				$flight['total_budget'] = $flight_row['budget'];
				$flight['io_timeseries_id'] = $flight_row['io_timeseries_id'];
				$flight['o_o_impressions'] = $flight_row['o_o_impressions'];
				$flight['o_o_budget'] = $flight_row['o_o_budget'];
				$flight['o_o_forecast_impressions'] = $flight_row['o_o_forecast_impressions'];
				$flight['gf_impressions'] = $flight_row['gf_impressions'];
				$flight['gf_budget'] = $flight_row['gf_budget'];
				$flight['ax_impressions'] = $flight_row['ax_impressions'];
				$flight['ax_budget'] = $flight_row['ax_budget'] + $flight_row['gf_budget'];
				$flight['dfp_object_templates_order_id'] = $flight_row['dfp_object_templates_order_id'];
				$flight['dfp_object_templates_lineitem_id'] = $flight_row['dfp_object_templates_lineitem_id'];
				$flight['dfp_status'] = $flight_row['dfp_status'];
				$flights_array[] = $flight;
			}
		}
		return $flights_array;
	}

	public function copy_scx_data($existing_mpq_id, $mpq_id)
	{
		$query =
		'	INSERT IGNORE INTO cp_products_tv_scx_upload
				(mpq_sessions_and_submissions_id, scx_file_blob, selected_networks)
			SELECT
				?,
				scx_file_blob,
				selected_networks
			FROM cp_products_tv_scx_upload
			WHERE mpq_sessions_and_submissions_id = ?
		';

		$query = $this->db->query($query, array($mpq_id, $existing_mpq_id));
	}

	public function save_rfp_scx_upload($file, $mpq_id)
	{
		$blob = gzcompress($file);

		$query =
		'	REPLACE INTO cp_products_tv_scx_upload
				(mpq_sessions_and_submissions_id, scx_file_blob)
			VALUES
				(?, ?)
		';

		$this->db->query($query, array($mpq_id, $blob));
	}

	public function save_selected_networks($networks, $mpq_id)
	{
		$query =
		'	UPDATE cp_products_tv_scx_upload
			SET selected_networks = ?
			WHERE mpq_sessions_and_submissions_id = ?
		';
		$this->db->query($query, array(json_encode($networks), $mpq_id));
	}

	public function get_rfp_scx_upload_data($mpq_id)
	{
		$query =
		'	SELECT
				scx_file_blob as data,
				selected_networks
			FROM cp_products_tv_scx_upload
			WHERE mpq_sessions_and_submissions_id = ?
		';

		$result = $this->db->query($query, array($mpq_id));

		if ($result->num_rows() > 0)
		{
			$data = $result->row_array();
			$data['data'] = gzuncompress($data['data']);
			return $data;
		}
		return false;
	}

    public function get_proposal_data_by_source_mpq($mpq_id)
    {
		$query = "
			SELECT
				*
			FROM
				prop_gen_prop_data
			WHERE
				source_mpq = ?";
		$response = $this->db->query($query, $mpq_id);
		return $response->row_array();
    }

    public function get_o_o_flights_to_forecast()
    {
		$get_flights_query =
		"SELECT
			ifsd.id AS id,
			ifsd.o_o_impressions AS o_o_impressions,
			ifsd.dfp_status AS dfp_status,
			ifsd.dfp_object_templates_lineitem_id AS dfp_object_template_id,
			cts.series_date AS series_date,
			csp.product_id AS product_id,
			csp.region_data_index AS region_data_index,
			csp.mpq_id AS mpq_id,
			cts.campaigns_id AS cp_submitted_product_id
		FROM
			io_flight_subproduct_details AS ifsd
			JOIN campaigns_time_series AS cts
				ON (ifsd.io_timeseries_id = cts.id)
				AND cts.object_type_id = 1
			JOIN cp_submitted_products AS csp
				ON ( cts.campaigns_id = csp.id)
		WHERE
			dfp_status NOT IN ('FAILED', 'COMPLETE', 'IN-PROGRESS')
		ORDER BY
			cts.campaigns_id DESC,
			cts.series_date ASC
		";

		$get_flights_result = $this->db->query($get_flights_query);
		if($get_flights_result == false)
		{
			//Fail
			return;
		}
		
		$old_mpq_id = "";
		
		$return_array = array(
		    'flights_to_process' => array(),
		    'io_flight_subproduct_details_ids' => array(),
		);
		
		foreach ($get_flights_result->result_array() as $row)
		{
			if ($old_mpq_id == "" || $old_mpq_id == $row["mpq_id"])
			{				
				$return_array['flights_to_process'][] = $row;
				$old_mpq_id = $row["mpq_id"];
				$return_array['io_flight_subproduct_details_ids'][] = $row["id"];
			}
			else
			{
				$this->update_dfp_status($return_array['io_flight_subproduct_details_ids'], 'IN-PROGRESS');
				break;
			}
		}

		return $return_array;
	}
	
	public function update_dfp_status($io_flight_subproduct_details_ids, $status)
	{
		$query = 
			"	
				UPDATE 
					io_flight_subproduct_details
				SET 
					dfp_status = '$status'
				WHERE 
					id IN
				(".
					implode(",",$io_flight_subproduct_details_ids)
				.")";
		$this->db->query($query);
	}

	public function forecast_timeseries($timeseries_rows)
	{		
		$product_geos = null;
		
		$return_array = array(
		    'processed_timeseries_ids' => array(),
		    'successful_timeseries_ids' => array(),
		    'failed_timeseries_ids' => array()
		);
		
		$old_cp_submitted_product_id = -1;
		
		foreach($timeseries_rows as $idx => $flight)
		{
			$return_array['processed_timeseries_ids'][] = $flight['id'];
			if($idx != (count($timeseries_rows)-1) && $flight['o_o_impressions'] != 0)
			{
				$next_flight = $timeseries_rows[$idx+1];
				$start_date = $flight['series_date'];
				$end_date = date('Y-m-d', strtotime($next_flight['series_date']." -1 days"));
				
				if ($old_cp_submitted_product_id != $flight['cp_submitted_product_id'])
				{
					$product_geos = null;
				}
				
				//Grab details
				if($product_geos == null)
				{
					$retrieve_product_regions_query = 
					"SELECT 
						region_data 
					FROM
						mpq_sessions_and_submissions
					WHERE
						id = ?
					";
					$retrieve_product_regions_result = $this->db->query($retrieve_product_regions_query, $flight['mpq_id']);
					if($retrieve_product_regions_result == false)
					{
						$no_region_error = "NO REGIONS FOUND FOR MPQ ".$flight['mpq_id'];
						$did_update_status = $this->update_forecast_error_for_subproduct_detail_row_with_error($flight['id'], $flight['dfp_status'], $no_region_error);
						$return_array['failed_timeseries_ids'][] = $flight['id'];
						continue;
					}
					$product_geos = $retrieve_product_regions_result->row_array();
					$product_geos = json_decode($product_geos['region_data']);

				}
				$product_zips = $this->get_zips_for_product_from_region_index($product_geos, $flight['region_data_index']);
				$forecast_targeting = $this->google_api->generate_targeting(
					null,			//$placements_targeted = null,
					null,			//$ad_units_targeted = null,
					$product_zips,	//$geos_included = null,
					null			//$geos_excluded = null
				);
				
				//Forecast
				$forecast_result = $this->google_api->get_availability_forecast(
					/*$advertiser_id,*/
					$start_date,
					$end_date,
					10,
					$flight['o_o_impressions'],
					"IMPRESSIONS",
					$forecast_targeting
				);

				if($forecast_result['success'])
				{
					$save_forecast_value_query = 
					"UPDATE 
						io_flight_subproduct_details
					SET
						o_o_impressions_from_dfp = ?,
						dfp_status = \"COMPLETE\"
					WHERE 
						id = ?
					";
					$bindings = array($forecast_result['data']->possibleUnits, $flight['id']);
					$save_forecast_value_result = $this->db->query($save_forecast_value_query, $bindings);
					$return_array['successful_timeseries_ids'][] = $flight['id'];
				}
				else
				{
					$did_update_status = $this->update_forecast_error_for_subproduct_detail_row_with_error($flight['id'], $flight['dfp_status'], $forecast_result['data']);
					$return_array['failed_timeseries_ids'][] = $flight['id'];
				}
			}
			else
			{
				$save_forecast_value_query = 
				"UPDATE 
					io_flight_subproduct_details
				SET
					o_o_impressions_from_dfp = ?,
					dfp_status = \"COMPLETE\"
				WHERE 
					id = ?
				";
				$bindings = array(0, $flight['id']);
				$save_forecast_value_result = $this->db->query($save_forecast_value_query, $bindings);
				$return_array['successful_timeseries_ids'][] = $flight['id'];
				$product_geos = null;
				continue;
			}
			$old_cp_submitted_product_id = $flight['cp_submitted_product_id'];
		}
		
		return $return_array;
	}

	private function get_zips_for_product_from_region_index($regions, $region_index)
	{
		foreach($regions AS $region)
		{
			if($region->page == $region_index)
			{
				return $region->ids->zcta;
			}
		}
		return false;
	}

	private function update_forecast_error_for_subproduct_detail_row_with_error($io_flight_subproduct_details_id, $io_flight_subproduct_current_status, $error_message)
	{
		$save_forecast_error_query = 
		"UPDATE 
			io_flight_subproduct_details
		SET
			dfp_status = ?,
			dfp_response = ?
		WHERE 
			id = ?
		";
		$bindings = array($this->get_next_error_status($io_flight_subproduct_current_status), $error_message, $io_flight_subproduct_details_id);
		$save_forecast_error_result = $this->db->query($save_forecast_error_query, $bindings);
		if($save_forecast_error_result == false)
		{
			return false;
		}
		return true;
	}

	private function get_next_error_status($error_status)
	{
		switch($error_status)
		{
			case "PENDING":
				return "RETRY1";
				break;
			case "RETRY1":
				return "RETRY2";
				break;
			case "RETRY2":
				return "RETRY3";
				break;
			case "RETRY3":
				return "RETRY4";
				break;
			case "RETRY4":
				return "RETRY5";
				break;
			case "RETRY5":
				return "FAILED";
				break;												
			default:
				return "FAILED";
		}
	}
	
	public function get_unsuccessful_forecast_details($mpq_id)
	{
		$query =
		"	SELECT DISTINCT
				ifsd.* 
			FROM
				cp_products_join_io AS cpj
				JOIN cp_products AS cpp
					ON cpj.product_id = cpp.id					
				JOIN cp_submitted_products AS csp
					ON cpj.mpq_id = csp.mpq_id
				JOIN campaigns_time_series AS cts
					ON cts.campaigns_id = csp.id					
				JOIN io_flight_subproduct_details AS ifsd
					ON cts.id = ifsd.io_timeseries_id 					
				where					
					cpj.mpq_id=? 
				AND 
					ifsd.dfp_status != 'COMPLETE' 
				AND 
					cts.object_type_id = 1 
				AND 
					cpp.o_o_enabled = 1
					
		";
		$result = $this->db->query($query, array($mpq_id));
		if ($result->num_rows() > 0)
		{
			return $result->result_array();			
		}
		else
		{
			return array();
		}		
	}

	public function get_all_budget_details_for_io($mpq_id)
	{
		//Generate required Line Item Data to
		$submitted_product_ids = $this->get_submitted_products_by_mpq_id($mpq_id);
		
		$regions_data = $this->get_region_data_by_mpq_id($mpq_id);
		$regions_data = json_decode($regions_data);
		
		$final_line_item_details = array();
		
		if(isset($submitted_product_ids))
		{
			foreach($submitted_product_ids as $submitted_product_id)
			{
				$check_o_o_status = $this->get_o_o_enabled_status($submitted_product_id['product_id']);
				if(!$check_o_o_status) continue;
				$flights_data = $this->get_flights_for_submitted_product($submitted_product_id['id']);
				
				$geo_zip_list = '';
				if($regions_data)
				{
					foreach($regions_data as $region_data)
					{
						if($region_data->page == $submitted_product_id['region_data_index'])
						{
							$geo_zip_list = $region_data->ids;
							$geo_location = $region_data->user_supplied_name;
						}
					}
				}
				
				
				$creative_sizes = '';
				$i = 0;
				foreach($flights_data as $flight_data)
				{
					$line_item_details['io_timeseries_id'] = $flight_data['io_timeseries_id'];
					$line_item_details['o_o_impressions'] = $flight_data['o_o_impressions']; 
					$line_item_details['start_date'] = $flight_data['start_date']; 
					$line_item_details['end_date'] = $flight_data['end_date']; 
					$line_item_details['dfp_status'] = $flight_data['dfp_status'];
					$line_item_details['o_o_budget'] = $flight_data['o_o_budget'];
					$line_item_details['dfp_object_templates_order_id'] = $flight_data['dfp_object_templates_order_id'];
					$line_item_details['dfp_object_templates_lineitem_id'] = $flight_data['dfp_object_templates_lineitem_id'];
					$line_item_details['geo_data'] = $geo_zip_list;
					$line_item_details['geo_location'] = $geo_location;
					
					$final_line_item_details[$submitted_product_id['id']][$i] = $line_item_details;
					$final_line_item_details[$submitted_product_id['id']][$i]['creative_sizes'] = $this->get_submitted_product_creatives($submitted_product_id['id']);
					$i++;
				}
			}
			
		}
		
		if($final_line_item_details !== false)
		{
			return $final_line_item_details;
		}
		return false;
	}
	
	public function get_submitted_product_creatives($submitted_product_id)
	{
		$creative_sizes_array = '';
		$sql = " 
		    SELECT 
			    cts.id as creative_id,
			    cts.size,
			    cts.ad_tag,
			    cts.published_ad_server,
			    cv.id as version_id,
			    cv.adset_id,
			    ca.name as adset_name
		    FROM
			    cup_creatives as cts
			    JOIN cp_io_join_cup_versions as cjcv
				ON cts.version_id = cjcv.cup_versions_id
			    JOIN cup_versions cv
				ON cts.version_id = cv.id
			    JOIN cup_adsets ca
				ON cv.adset_id = ca.id

		    WHERE
			    cjcv.cp_submitted_products_id = ?
		";
		$result = $this->db->query($sql, $submitted_product_id);
		if ($result !== false)
		{
			return $result->result_array();
		}

		return false;	
	}
	
	public function get_dfp_object_template($dfp_object_templates_id)
  	{
 		$get_dfp_object_template_data_query =
  		"SELECT 
 			dot.id, dot.name, dot.object_blob
  		FROM 
 			dfp_object_templates dot
  		WHERE
 			dot.id = ?
  		";
  
 		$get_dfp_object_template_data_result = $this->db->query($get_dfp_object_template_data_query, $dfp_object_templates_id);
		if ($get_dfp_object_template_data_result !== false && $get_dfp_object_template_data_result->num_rows() > 0)
		{
			return $get_dfp_object_template_data_result->result_array();
		}
 
 		return false;
 	}
	
	public function create_adgroup_per_line_item($line_item_id)
	{
		$create_adgroup_data_query =
		"INSERT INTO
			AdGroups
				(ID, subproduct_type_id)
			VALUES
			(?, ?)
			ON DUPLICATE KEY UPDATE
				ID = VALUES(ID)
		";
		$subproduct_type_id = 4;//O_O_DISPLAY
		$bindings = array($line_item_id, $subproduct_type_id);
		$create_adgroup_result = $this->db->query($create_adgroup_data_query, $bindings);
		if ($this->db->affected_rows() > 0)
		{
			$result['is_success'] = true;
			$result['adgroup_id'] = $line_item_id;
		}
		else
		{
			$result['is_success'] = false;
		}
		return $result;
	}
	
	public function update_io_flight_subproduct_with_adgroup($adgroup_id, $io_timeseries_id)
	{
		$update_io_flight_subproduct_with_adgroup_data_query =
		"UPDATE io_flight_subproduct_details
		SET 
			f_o_o_adgroup_id  = ?
		WHERE 
			io_timeseries_id = ?;
		";
		$bindings = array($adgroup_id, $io_timeseries_id);
		$this->db->query($update_io_flight_subproduct_with_adgroup_data_query, $bindings);
		if ($this->db->affected_rows() > 0)
		{
			return true;
			//$result['adgroup_id'] = $this->db->insert_id();
		}
		else
		{
			return false;
		}
	}
	
	public function insert_new_dfp_order_id_to_freq($mpq_id, $order_id)
	{
		$insert_order_query =
		"INSERT INTO
			io_dfp_line_item
				(insertion_order_id, dfp_order_id)
			VALUES
			(?,?)
		";
		$bindings = array($mpq_id, $order_id);
		$insert_order_query_result = $this->db->query($insert_order_query, $bindings);
		if ($this->db->affected_rows() > 0)
		{
			$result['is_success'] = true;
			$result['last_inserted_id'] = $this->db->insert_id();
		}
		else
		{
			$result['is_success'] = false;
		}
		return $result;
	}
	
	public function link_line_item_id_with_order_id($last_inserted_id, $line_item_id)
	{
		$insert_order_query =
		"UPDATE io_dfp_line_item
		SET 
			dfp_line_item_id  = ?
		WHERE 
			id = ?;
		";
		$bindings = array($line_item_id, $last_inserted_id);
		$insert_order_query_result = $this->db->query($insert_order_query, $bindings);
		if ($this->db->affected_rows() > 0)
		{
			$result['is_success'] = true;
			$result['order_id'] = $this->db->insert_id();
		}
		else
		{
			$result['is_success'] = false;
		}
		return $result;
	}
	
	public function get_dfp_order_id_by_mpq_id($mpq_id)
	{
		$get_dfp_order_query =
		"SELECT 
 			DISTINCT(dfp_order_id)
  		FROM 
 			io_dfp_line_item
  		WHERE
 			insertion_order_id = ?
  		";
		$bindings = array($mpq_id);
		$get_dfp_order_query_result = $this->db->query($get_dfp_order_query, $bindings);
		if ($get_dfp_order_query_result !== false && $get_dfp_order_query_result->num_rows() > 0)
		{
			return $get_dfp_order_query_result->result_array();
		}
 
 		return false;
	}
	
	public function update_freq_table($order_id)
	{
		$result_status = array();
			
		// Delete Adgroups
		$delete_adgroups_sql = '
			DELETE FROM AdGroups
			
			WHERE
				ID IN (SELECT dfp_line_item_id FROM io_dfp_line_item WHERE dfp_order_id = ?)
		';
		$delete_bindings = array($order_id);
		$delete_response = $this->db->query($delete_sql, $delete_bindings);

		print_r($delete_response);
		if($delete_response == false)
		{
			$result_status['is_success'] = false; 
			$result_status['msg'] = 'Adgroups Not Deleted';
			return $result_status;
		}
		
		$delete_sql = '
			DELETE FROM io_dfp_line_item
			
			WHERE
				dfp_order_id = ?
		';
		$delete_bindings = array($order_id);
		$delete_response = $this->db->query($delete_sql, $delete_bindings);
		print_r($delete_response);
		if($delete_response == false)
		{
			$result_status['is_success'] = false; 
			$result_status['msg'] = 'Order Not Deleted';
			return $result_status;
		}

		$result_status['is_success'] = true; 
		$result_status['msg'] = 'All data updates successfully';
		return $result_status;
	}
	
	// Cloning Creatives
	public function clone_submitted_product_adset_creatives($product_details, $product_creative_details)
	{
		//Create new adset in cup_adsets
		$create_new_adset_query = "
			INSERT INTO cup_adsets (
				name
			)
			SELECT
				?
			FROM
				cup_adsets ca
			WHERE
				ca.id = ?
			";

		$binding = array();
		$binding[] = $product_details['adset_name'].'_O&O';
		$binding[] = $product_details['adset_id'];
		$create_new_adset_result = $this->db->query($create_new_adset_query, $binding);
		if ($this->db->affected_rows() > 0)
		{
			$last_inserted_adset_id = $this->db->insert_id();
		}
		else
		{
			return false;
		}
		$clone_version_details = $this->clone_version($last_inserted_adset_id, $product_details['version_id']);
		if($clone_version_details['is_success'])
		{
			$clone_creatives_details = $this->clone_creatives($clone_version_details['new_version_id'], $product_details['version_id']);
			if($clone_creatives_details)
			{
				$clone_ad_assets = $this->clone_ad_assets($clone_version_details['new_version_id'], $product_creative_details);
				if($clone_ad_assets)
				{
					return $update_cp_io_with_O_O_version_id = $this->update_cp_io_with_O_O_version_id($clone_version_details['new_version_id'], $product_details['product_id']);
				}
			}
		}
		return false;
		
	}

	public function update_adgroup_subproduct_types()
	{
				//Update adgroup subproduct_types 
		$update_adgroups = 
		"UPDATE
			AdGroups
		SET
			subproduct_type_id = 2
		WHERE
			target_type LIKE '%Pre-Roll%'
		";
		$updated = $this->db->query($update_adgroups);
		if($updated == false)
		{
			return false;
		}

		$update_adgroups = 
		"UPDATE
			AdGroups
		SET
			subproduct_type_id = 1
		WHERE
			Source != 'TDAV' AND Source != 'DFPBH' AND source != 'TDGF'
			AND target_type IN ('PC', 'Mobile 320', 'Mobile No 320', 'Tablet', 'RTG', 'Custom Averaged', 'Custom Ignored');
		";
		$updated = $this->db->query($update_adgroups);
		if($updated == false)
		{
			return false;
		}

		$update_adgroups = 
		"UPDATE
			AdGroups
		SET
			subproduct_type_id = 4
		WHERE
			source = 'DFPBH'
		";
		$updated = $this->db->query($update_adgroups);
		if($updated == false)
		{
			return false;
		}


		$update_adgroups = 
		"UPDATE
			AdGroups
		SET
			subproduct_type_id = 3
		WHERE
			source = 'TDGF'
		";
		$updated = $this->db->query($update_adgroups);
		if($updated == false)
		{
			return false;
		}
		return true;
	}

	public function migrate_campaigns_for_partner($pivot_date, $before_or_after, $partner_id, $display_cpm, $o_o_cpm, $geofencing_cpm, $geofencing_max_percentage)
	{
		if($before_or_after == "before")
		{
			$limit_migration = "AND adg.latest_site_record < ?";
		}
		elseif($before_or_after == "after")
		{
			$limit_migration = "AND adg.latest_site_record >= ?";
		}
		else
		{
			//????
			return false;
		}

		$get_unlinked_campaigns_query =
		"SELECT
			cmp.id AS campaign_id
		FROM
			Campaigns AS cmp
			LEFT JOIN io_campaign_product_cpm AS icpc
				ON (cmp.id = icpc.campaign_id)
			JOIN AdGroups AS adg
				ON (cmp.id = adg.campaign_id)
			JOIN Advertisers AS adv
				ON (cmp.business_id = adv.id)
			JOIN users AS u
				ON (adv.sales_person = u.id)
		WHERE
			adg.subproduct_type_id IS NOT NULL
			AND cmp.insertion_order_id IS NULL
			AND u.partner_id = ?
			AND icpc.campaign_id IS NULL
			{$limit_migration}
		GROUP BY
			cmp.id
		ORDER BY 
			cmp.id DESC
		";
		$bindings = array($partner_id, $pivot_date);
		$get_unlinked_campaigns_result = $this->db->query($get_unlinked_campaigns_query, $bindings);

		$unlinked_campaigns = $get_unlinked_campaigns_result->result_array();

		$o_o_max_ratio = 0;
		foreach($unlinked_campaigns as $unlinked_campaign)
		{
			$has_preroll = 0;
			$get_preroll_adgroups_query = 
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				subproduct_type_id = 2
				AND campaign_id = ?
			";
			$get_preroll_adgroups_result = $this->db->query($get_preroll_adgroups_query, $unlinked_campaign['campaign_id']);
			if($get_preroll_adgroups_result != false && $get_preroll_adgroups_result->num_rows() > 0)
			{
				$has_preroll = 1;
				//Preroll
				$update_cpm_query =
				"INSERT INTO 
					io_campaign_product_cpm
					(campaign_id, subproduct_type_id, dollar_cpm)
				VALUES
					(?, ?, ?)
				ON DUPLICATE KEY UPDATE
					dollar_cpm = VALUES(dollar_cpm)
				";

				$bindings = array($unlinked_campaign['campaign_id'], 2, 25);
				$update_cpm_result = $this->db->query($update_cpm_query, $bindings);
			}
			else
			{
				$update_values = array();
				$display_cpm_bindings = array();
				//get display adgroups
				$get_display_adgroups_query = 
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					subproduct_type_id = 1
					AND campaign_id = ?
				";
				$get_display_adgroups_result = $this->db->query($get_display_adgroups_query, $unlinked_campaign['campaign_id']);
				if($get_display_adgroups_result && $get_display_adgroups_result->num_rows > 0)
				{
					$update_values[] = "(?, ?, ?, ?)";
					$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
					$display_cpm_bindings[] = 1;
					$display_cpm_bindings[] = $display_cpm;
					$display_cpm_bindings[] = null;
				}

				//get geofencing adgroups
				$get_geofencing_adgroups_query = 
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					subproduct_type_id = 3
					AND campaign_id = ?
				";
				$get_geofencing_adgroups_result = $this->db->query($get_geofencing_adgroups_query, $unlinked_campaign['campaign_id']);
				if($get_geofencing_adgroups_result && $get_geofencing_adgroups_result->num_rows > 0)
				{
					$update_values[] = "(?, ?, ?, ?)";
					$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
					$display_cpm_bindings[] = 3;
					$display_cpm_bindings[] = $geofencing_cpm;
					$display_cpm_bindings[] = $geofencing_max_percentage;
				}

				$get_o_o_adgroups_query = 
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					subproduct_type_id = 4
					AND campaign_id = ?
				";
				$get_o_o_adgroups_result = $this->db->query($get_o_o_adgroups_query, $unlinked_campaign['campaign_id']);
				if($get_o_o_adgroups_result && $get_o_o_adgroups_result->num_rows > 0)
				{
					$update_values[] = "(?, ?, ?, ?)";
					$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
					$display_cpm_bindings[] = 4;
					$display_cpm_bindings[] = $o_o_cpm;
					$display_cpm_bindings[] = null;

					if($get_display_adgroups_result->num_rows < 1)
					{
						$o_o_max_ratio = 100;
						$update_values[] = "(?, ?, ?, ?)";
						$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
						$display_cpm_bindings[] = 1;
						$display_cpm_bindings[] = $display_cpm;
						$display_cpm_bindings[] = null;						
					}
				}
				if(!empty($update_values))
				{
					$update_values = implode(",", $update_values);

					$update_cpm_query =
					"INSERT INTO 
						io_campaign_product_cpm
						(campaign_id, subproduct_type_id, dollar_cpm, max_dollar_pct)
					VALUES
						{$update_values}
					ON DUPLICATE KEY UPDATE
						dollar_cpm = VALUES(dollar_cpm),
						max_dollar_pct = VALUES(max_dollar_pct)
					";
					$update_cpm_result = $this->db->query($update_cpm_query, $display_cpm_bindings);
					if($update_cpm_result == false)
					{
						continue;
					}

				}
			}
			$cpms = $this->retrieve_product_cpms_for_campaign($unlinked_campaign['campaign_id']);

			//Get flights
			$existing_flights = $this->al_model->get_campaign_timeseries_details($unlinked_campaign['campaign_id']);
			foreach($existing_flights as $flight)
			{
				$impressions = $flight['impressions'];

				$budget = ($impressions/1000)*$cpms['ax'];

				$update_flight_budget_value_query =
				"UPDATE
					campaigns_time_series
				SET 
					budget = ?
				WHERE 
					id = ?
				";
				$update_flight_budget_value_result = $this->db->query($update_flight_budget_value_query, array($budget, $flight['id']));
				if($update_flight_budget_value_result == false)
				{
					return false;
				}
				$add_flight_subproduct_row = $this->add_dfp_order_rows_for_flight($flight['id']);
				if($add_flight_subproduct_row == false)
				{
					return false;
				}

				if(!$has_preroll && $get_geofencing_adgroups_result->num_rows() > 0)
				{
					$geofencing_inventory = 99999999999; //I think this uses the max percent if this is super-high
				}
				else
				{
					$geofencing_inventory = 0;
				}
				$o_o_impressions = $cpms['o_o'] ? round((($o_o_max_ratio*0.01)*$budget)/$cpms['o_o'] * 1000) : 0;
				if(!$cpms['ax'])
				{
					echo $unlinked_campaign['campaign_id']." - ";
					print_r($cpms);
					echo "\n";
				}
				$budget_values = get_budget_calculation($budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $o_o_max_ratio, $geofencing_inventory);
				$saved_budget_values = $this->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, false);
				if(!$saved_budget_values)
				{
					return false;
				}
			}
		}		
	}

	public function migrate_campaigns_flights_to_dollars($pivot_date, $before_or_after)
	{

		if($before_or_after == "before")
		{
			$limit_migration = "AND adg.latest_site_record < ?";
		}
		elseif($before_or_after == "after")
		{
			$limit_migration = "AND adg.latest_site_record >= ?";
		}
		else
		{
			//????
			return false;
		}

		//Now we can try and predict what subproduct a set of adgroups is
		$get_unbudgeted_campaigns_query = 
		"SELECT
			cmp.id AS campaign_id,
			mss.id AS mpq_id,
			spt.name,
			spt.id AS subproduct_type_id,
			cpp.definition AS product_definition,
			csp.submitted_values AS submitted_values,
			mo.discount AS discount,
			cspoo.o_o_percent AS o_o_percent,
			cpp.o_o_enabled AS o_o_enabled,
			cpp.o_o_dfp AS o_o_dfp,
			cpp.o_o_max_ratio AS o_o_max_ratio
		FROM
			AdGroups AS adg
			JOIN Campaigns AS cmp
				ON (adg.campaign_id = cmp.id)
			JOIN mpq_sessions_and_submissions AS mss
				ON (cmp.insertion_order_id = mss.id)
			JOIN subproduct_types AS spt
				ON (adg.subproduct_type_id = spt.id)
			JOIN cp_submitted_products AS csp 
				ON (mss.id = csp.mpq_id)
			JOIN cp_products AS cpp
				ON (csp.product_id = cpp.id AND cpp.product_identifier = LOWER(spt.name))
			JOIN campaigns_time_series AS cts
				ON (cmp.id = cts.campaigns_id AND cts.object_type_id = 0)
			LEFT JOIN mpq_options AS mo
				ON (mss.parent_mpq_id = mo.mpq_id)
			LEFT JOIN cp_submitted_product_o_o AS cspoo
				ON (csp.id = cspoo.cp_submitted_product_id)
			LEFT JOIN io_campaign_product_cpm AS icpc
				ON (cmp.id = icpc.campaign_id)
		WHERE 
			adg.subproduct_type_id IS NOT NULL
			AND cmp.insertion_order_id IS NOT NULL
			AND icpc.campaign_id IS NULL
			{$limit_migration}
		GROUP BY 
			cmp.id
		ORDER BY cmp.id DESC
		";

		$get_unbudgeted_campaigns_result = $this->db->query($get_unbudgeted_campaigns_query, $pivot_date);
		if($get_unbudgeted_campaigns_result == false)
		{
			echo "Failed to get list of unbudgeted campaigns";
			return false;
		}

		$unbudgeted_campaigns = $get_unbudgeted_campaigns_result->result_array();

		foreach($unbudgeted_campaigns as $unbudgeted_campaign)
		{
			$cpm = 10;

			$geofencing_cpm = false;
			$preroll_query =
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				campaign_id = ?
				AND subproduct_type_id = 2
			";
			$preroll_result = $this->db->query($preroll_query, $unbudgeted_campaign['campaign_id']);
			if($preroll_result == false)
			{
				return false;
			}
			if($preroll_result->num_rows > 0)
			{
				$cpm = 25;
			}


			//get CPM
			$submitted_values = json_decode($unbudgeted_campaign['submitted_values']);
			$product_definition = json_decode($unbudgeted_campaign['product_definition']);
			if(property_exists($submitted_values, 'cpm'))
			{
				$base_cpm = $submitted_values->cpm;
			}
			else
			{
				$base_cpm = $product_definition->options[0]->cpm->default;
				if(empty($base_cpm))
				{
					$base_cpm = $cpm;
				}
			}
			$cpm = $base_cpm;
			if($unbudgeted_campaign['discount'])
			{
				$cpm = $base_cpm * (100 - $unbudgeted_campaign['discount'])*0.01;
			}


			$update_cpm_bindings = array($unbudgeted_campaign['campaign_id'], $unbudgeted_campaign['subproduct_type_id'], $cpm);
			//determine if there's O&O in the campaign
			$o_o_adgroup_query =
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				campaign_id = ?
				AND subproduct_type_id = 4
			";
			$dfp_values = "";
			$o_o_adgroup_result = $this->db->query($o_o_adgroup_query, $unbudgeted_campaign['campaign_id']);
			if($o_o_adgroup_result == false)
			{
				return false;
			}
			if($o_o_adgroup_result->num_rows() > 0)
			{
				$audience_ext_adgroup_query =
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					campaign_id = ?
					AND subproduct_type_id IN (1, 2)
				";
				$audience_ext_adgroup_result = $this->db->query($audience_ext_adgroup_query, $unbudgeted_campaign['campaign_id']);
				if($audience_ext_adgroup_result == false)
				{
					return false;
				}
				if($audience_ext_adgroup_result->num_rows < 1)
				{
					//O&O only campaign
					$unbudgeted_campaign['o_o_max_ratio'] = 100;
				}
				$dfp_values = ", (?, ?, ?)";
				$update_cpm_bindings = array_merge($update_cpm_bindings, array($unbudgeted_campaign['campaign_id'], 4, $cpm));
			}


			//determine if there's geofencing in the campaign
			$geofencing_adgroup_query =
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				campaign_id = ?
				AND subproduct_type_id = 3
			";
			$dfp_values = "";
			$geofencing_adgroup_result = $this->db->query($geofencing_adgroup_query, $unbudgeted_campaign['campaign_id']);
			if($geofencing_adgroup_result == false)
			{
				return false;
			}
			if($geofencing_adgroup_result->num_rows() > 0)
			{
				$dfp_values = ", (?, ?, ?)";
				if(property_exists($submitted_values, "geofence_cpm"))
				{
					$gf_cpm = $submitted_values->geofence_cpm;
				}
				else
				{
					$gf_cpm = $product_definition->geofencing->default_cpm;
				}

				if($unbudgeted_campaign['discount'])
				{
					$gf_cpm = $gf_cpm * (100 - $unbudgeted_campaign['discount'])*0.01;
				}
                if(empty($gf_cpm))
                {
                	$gf_cpm = $cpm*2;
                }
                if(empty($max_percentage))
                {
                	$max_percentage = 5;
                }				
				$update_cpm_bindings = array_merge($update_cpm_bindings, array($unbudgeted_campaign['campaign_id'], 3, $gf_cpm));
			}

			//Make CPM row
			$update_cpm_query =
			"INSERT INTO 
				io_campaign_product_cpm
				(campaign_id, subproduct_type_id, dollar_cpm)
			VALUES
				(?, ?, ?)".$dfp_values."
			ON DUPLICATE KEY UPDATE
				dollar_cpm = VALUES(dollar_cpm)
			";
			
			$update_cpm_result = $this->db->query($update_cpm_query, $update_cpm_bindings);
			if($update_cpm_result == false)
			{
				return false;
			}

			$cpms = $this->retrieve_product_cpms_for_campaign($unbudgeted_campaign['campaign_id']);

			//Get flights
			$existing_flights = $this->al_model->get_campaign_timeseries_details($unbudgeted_campaign['campaign_id']);
			foreach($existing_flights as $flight)
			{
				$impressions = $flight['impressions'];

				$budget = ($impressions/1000)*$cpm;

				$update_flight_budget_value_query =
				"UPDATE
					campaigns_time_series
				SET 
					budget = ?
				WHERE 
					id = ?
				";
				$update_flight_budget_value_result = $this->db->query($update_flight_budget_value_query, array($budget, $flight['id']));
				if($update_flight_budget_value_result == false)
				{
					return false;
				}
				$add_flight_subproduct_row = $this->add_dfp_order_rows_for_flight($flight['id']);
				if($add_flight_subproduct_row == false)
				{
					return false;
				}
				$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($unbudgeted_campaign['mpq_id']);
				$o_o_impressions = ($unbudgeted_campaign['o_o_enabled'] && $cpms['o_o']) ? round((($unbudgeted_campaign['o_o_max_ratio']*0.01)*$budget)/$cpms['o_o'] * 1000) : 0;
				if(!$cpms['ax'])
				{
					echo $unbudgeted_campaign['campaign_id']." - ";
					print_r($cpms);
					echo "\n";
				}
				$budget_values = get_budget_calculation($budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $unbudgeted_campaign['o_o_max_ratio'], $geofencing_inventory);
				$saved_budget_values = $this->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, false);
				if(!$saved_budget_values)
				{
					return false;
				}
			}
		}

	}

	public function migrate_io_flights_to_dollars($pivot_date, $before_or_after)
	{

		if($before_or_after == "before")
		{
			$limit_migration = "AND mss.last_updated < ?";
		}
		elseif($before_or_after == "after")
		{
			$limit_migration = "AND mss.last_updated >= ?";
		}
		else
		{
			//????
			return false;
		}

		//Do same for insertion orders
		$get_unbudgeted_ios_query = 
		"SELECT
			csp.id AS submitted_product_id,
			cpp.o_o_enabled AS o_o_enabled,
			csp.mpq_id AS mpq_id,
			csp.submitted_values AS submitted_values,
			cpp.product_identifier AS product_identifier,
			cpp.definition AS product_definition,
			mo.discount AS discount,
			cspoo.o_o_percent AS o_o_percent,
			cpp.o_o_dfp AS o_o_dfp,
			cpp.o_o_max_ratio AS o_o_max_ratio,
			pgp.mpq_id AS geofencing_mpq_id	
		FROM
			cp_submitted_products AS csp
			JOIN cp_products AS cpp
				ON (csp.product_id = cpp.id)
			JOIN campaigns_time_series AS cts
				ON (csp.id = cts.campaigns_id AND cts.object_type_id = 1)
			JOIN mpq_sessions_and_submissions AS mss
				ON (csp.mpq_id = mss.id)
			JOIN mpq_options AS mo
				ON (csp.mpq_id = mo.mpq_id)
			LEFT JOIN cp_submitted_product_o_o AS cspoo
				ON (csp.id = cspoo.cp_submitted_product_id)
			LEFT JOIN proposal_geofencing_points AS pgp
				ON (mss.id = pgp.mpq_id AND csp.region_data_index = pgp.location_id)
			LEFT JOIN io_campaign_product_cpm AS icpc
				ON (csp.id = icpc.cp_submitted_products_id)
		WHERE
			icpc.cp_submitted_products_id IS NULL
		{$limit_migration}		
		GROUP BY
			csp.id
		ORDER BY 
			mss.id DESC
		";

		$get_unbudgeted_ios_result = $this->db->query($get_unbudgeted_ios_query, $pivot_date);
		if($get_unbudgeted_ios_result == false)
		{
			return false;
		}

		$unbudgeted_ios = $get_unbudgeted_ios_result->result_array();
		foreach($unbudgeted_ios as $unbudgeted_io)
		{
			$cpm = 10;
			$geofencing_cpm = false;

			//get CPM
			$submitted_values = json_decode($unbudgeted_io['submitted_values']);
			$product_definition = json_decode($unbudgeted_io['product_definition']);
			if(property_exists($submitted_values, 'cpm'))
			{
				$base_cpm = $submitted_values->cpm;
			}
			else
			{
				//Replace with the default from the product definiition from the submitted product
				$base_cpm = $product_definition->options[0]->cpm->default;
				if(empty($base_cpm))
				{
					$base_cpm = 10;
				}				
			}
			$cpm = $base_cpm;
			if($unbudgeted_io['discount'])
			{
				$cpm = $base_cpm * (100 - $unbudgeted_io['discount'])*0.01;
			}

			$normal_subproduct_id = 1;
			if($unbudgeted_io['product_identifier'] == "preroll")
			{
				$normal_subproduct_id = 2;
			}

			$update_cpm_bindings = array($unbudgeted_io['submitted_product_id'], $normal_subproduct_id, $cpm, null);
			//set o&o if needed
			$dfp_values = "";
			if($unbudgeted_io['o_o_enabled'] != false)
			{
				$dfp_values .= ", (?, ?, ?, ?)";
				$update_cpm_bindings = array_merge($update_cpm_bindings, array($unbudgeted_io['submitted_product_id'], 4, $cpm, null));
			}

			if($unbudgeted_io['product_identifier'] == "display" && !empty(['geofencing_mpq_id']))
			{
				$dfp_values .= ", (?, ?, ?, ?)";
				if(property_exists($submitted_values, "geofence_cpm"))
				{
					$gf_cpm = $submitted_values->geofence_cpm;
				}
				else
				{
					$gf_cpm = $product_definition->geofencing->default_cpm;
				}

				if($unbudgeted_io['discount'])
				{
					$gf_cpm = $gf_cpm * (100 - $unbudgeted_io['discount'])*0.01;
				}
                $max_percentage = $product_definition->geofencing->max_percent;

                if(empty($gf_cpm))
                {
                	$gf_cpm = $cpm*2;
                }
                if(empty($max_percentage))
                {
                	$max_percentage = 5;
                }
				$update_cpm_bindings = array_merge($update_cpm_bindings, array($unbudgeted_io['submitted_product_id'], 3, $gf_cpm, $max_percentage));				
			}

			//Make CPM row
			$update_cpm_query =
			"INSERT INTO 
				io_campaign_product_cpm
				(cp_submitted_products_id, subproduct_type_id, dollar_cpm, max_dollar_pct)
			VALUES
				(?, ?, ?, ?)".$dfp_values."
			ON DUPLICATE KEY UPDATE
				dollar_cpm = VALUES(dollar_cpm),
                                max_dollar_pct = VALUES(max_dollar_pct)
			";
			
			$update_cpm_result = $this->db->query($update_cpm_query, $update_cpm_bindings);
			if($update_cpm_result == false)
			{
				return false;
			}
			$cpms = $this->retrieve_product_cpms_for_submitted_product($unbudgeted_io['submitted_product_id']);

			//Get flights
			$existing_flights = $this->get_flights_data_for_submitted_product($unbudgeted_io['submitted_product_id']);
			foreach($existing_flights as $flight)
			{
				$impressions = $flight['ts_impressions'];

				$budget = ($impressions/1000)*$cpm;

				$update_flight_budget_value_query =
				"UPDATE
					campaigns_time_series
				SET 
					budget = ?
				WHERE 
					id = ?
				";
				$update_flight_budget_value_result = $this->db->query($update_flight_budget_value_query, array($budget, $flight['id']));
				if($update_flight_budget_value_result == false)
				{
					return false;
				}
				$add_flight_subproduct_row = $this->add_dfp_order_rows_for_flight($flight['id']);
				if($add_flight_subproduct_row == false)
				{
					return false;
				}				
				$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($unbudgeted_io['mpq_id']);
				$o_o_impressions = ($unbudgeted_io['o_o_enabled'] && $cpms['o_o']) ? round((($unbudgeted_io['o_o_max_ratio']*0.01)*$budget)/$cpms['o_o'] * 1000) : 0;
				$budget_values = get_budget_calculation($budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $unbudgeted_io['o_o_max_ratio'], $geofencing_inventory);
				$saved_budget_values = $this->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $unbudgeted_io['o_o_dfp']);
				if(!$saved_budget_values)
				{
					return false;
				}
			}			
		}		
	}

	public function migrate_unlinked_campaigns_to_dollars($migrate_all = false)
	{

		$limit_migration = "AND icpc.campaign_id IS NULL";
		if($migrate_all)
		{
			$limit_migration = "";
		}

		$get_unlinked_campaigns_query =
		"SELECT
			cmp.id AS campaign_id
		FROM
			Campaigns AS cmp
			LEFT JOIN io_campaign_product_cpm AS icpc
				ON (cmp.id = icpc.campaign_id)
			JOIN AdGroups AS adg
				ON (cmp.id = adg.campaign_id)
		WHERE
			adg.subproduct_type_id IS NOT NULL
			AND cmp.insertion_order_id IS NULL
			{$limit_migration}
		GROUP BY
			cmp.id
		ORDER BY 
			cmp.id DESC
		";

		$get_unlinked_campaigns_result = $this->db->query($get_unlinked_campaigns_query);

		$unlinked_campaigns = $get_unlinked_campaigns_result->result_array();

		$o_o_max_ratio = 0;
		foreach($unlinked_campaigns as $unlinked_campaign)
		{
			$has_preroll = 0;
			$get_preroll_adgroups_query = 
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				subproduct_type_id = 2
				AND campaign_id = ?
			";
			$get_preroll_adgroups_result = $this->db->query($get_preroll_adgroups_query, $unlinked_campaign['campaign_id']);
			if($get_preroll_adgroups_result != false && $get_preroll_adgroups_result->num_rows() > 0)
			{
				$has_preroll = 1;
				//Preroll
				$update_cpm_query =
				"INSERT INTO 
					io_campaign_product_cpm
					(campaign_id, subproduct_type_id, dollar_cpm)
				VALUES
					(?, ?, ?)
				ON DUPLICATE KEY UPDATE
					dollar_cpm = VALUES(dollar_cpm)
				";

				$bindings = array($unlinked_campaign['campaign_id'], 2, 25);
				$update_cpm_result = $this->db->query($update_cpm_query, $bindings);
			}
			else
			{
				$update_values = array();
				$display_cpm_bindings = array();
				//get display adgroups
				$get_display_adgroups_query = 
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					subproduct_type_id = 1
					AND campaign_id = ?
				";
				$get_display_adgroups_result = $this->db->query($get_display_adgroups_query, $unlinked_campaign['campaign_id']);
				if($get_display_adgroups_result && $get_display_adgroups_result->num_rows > 0)
				{
					$update_values[] = "(?, ?, ?, ?)";
					$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
					$display_cpm_bindings[] = 1;
					$display_cpm_bindings[] = 10;
					$display_cpm_bindings[] = null;
				}

				//get geofencing adgroups
				$get_geofencing_adgroups_query = 
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					subproduct_type_id = 3
					AND campaign_id = ?
				";
				$get_geofencing_adgroups_result = $this->db->query($get_geofencing_adgroups_query, $unlinked_campaign['campaign_id']);
				if($get_geofencing_adgroups_result && $get_geofencing_adgroups_result->num_rows > 0)
				{
					$update_values[] = "(?, ?, ?, ?)";
					$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
					$display_cpm_bindings[] = 3;
					$display_cpm_bindings[] = 20;
					$display_cpm_bindings[] = 5;
				}

				$get_o_o_adgroups_query = 
				"SELECT
					*
				FROM
					AdGroups
				WHERE
					subproduct_type_id = 4
					AND campaign_id = ?
				";
				$get_o_o_adgroups_result = $this->db->query($get_o_o_adgroups_query, $unlinked_campaign['campaign_id']);
				if($get_o_o_adgroups_result && $get_o_o_adgroups_result->num_rows > 0)
				{
					$update_values[] = "(?, ?, ?, ?)";
					$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
					$display_cpm_bindings[] = 4;
					$display_cpm_bindings[] = 10;
					$display_cpm_bindings[] = null;

					if($get_display_adgroups_result->num_rows < 1)
					{
						$o_o_max_ratio = 100;
						$update_values[] = "(?, ?, ?, ?)";
						$display_cpm_bindings[] = $unlinked_campaign['campaign_id'];
						$display_cpm_bindings[] = 1;
						$display_cpm_bindings[] = 10;
						$display_cpm_bindings[] = null;						
					}
				}
				if(!empty($update_values))
				{
					$update_values = implode(",", $update_values);

					$update_cpm_query =
					"INSERT INTO 
						io_campaign_product_cpm
						(campaign_id, subproduct_type_id, dollar_cpm, max_dollar_pct)
					VALUES
						{$update_values}
					ON DUPLICATE KEY UPDATE
						dollar_cpm = VALUES(dollar_cpm),
						max_dollar_pct = VALUES(max_dollar_pct)
					";
					$update_cpm_result = $this->db->query($update_cpm_query, $display_cpm_bindings);
					if($update_cpm_result == false)
					{
						continue;
					}

				}
			}
			$cpms = $this->retrieve_product_cpms_for_campaign($unlinked_campaign['campaign_id']);

			//Get flights
			$existing_flights = $this->al_model->get_campaign_timeseries_details($unlinked_campaign['campaign_id']);
			foreach($existing_flights as $flight)
			{
				$impressions = $flight['impressions'];

				$budget = ($impressions/1000)*$cpms['ax'];

				$update_flight_budget_value_query =
				"UPDATE
					campaigns_time_series
				SET 
					budget = ?
				WHERE 
					id = ?
				";
				$update_flight_budget_value_result = $this->db->query($update_flight_budget_value_query, array($budget, $flight['id']));
				if($update_flight_budget_value_result == false)
				{
					return false;
				}
				$add_flight_subproduct_row = $this->add_dfp_order_rows_for_flight($flight['id']);
				if($add_flight_subproduct_row == false)
				{
					return false;
				}

				if(!$has_preroll && $get_geofencing_adgroups_result->num_rows() > 0)
				{
					$geofencing_inventory = 99999999999; //I think this uses the max percent if this is super-high
				}
				else
				{
					$geofencing_inventory = 0;
				}
				$o_o_impressions = $cpms['o_o'] ? round((($o_o_max_ratio*0.01)*$budget)/$cpms['o_o'] * 1000) : 0;
				if(!$cpms['ax'])
				{
					echo $unlinked_campaign['campaign_id']." - ";
					print_r($cpms);
					echo "\n";
				}
				$budget_values = get_budget_calculation($budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $o_o_max_ratio, $geofencing_inventory);
				$saved_budget_values = $this->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, false);
				if(!$saved_budget_values)
				{
					return false;
				}
			}
		}
	}
 
	public function calculate_budgets_for_cpm_defined_campaigns_without_budgets()
	{
		$get_campaigns_with_cpms_but_no_budgets_query =
		"SELECT
			cmp.id AS campaign_id
		FROM
			AdGroups AS adg
			JOIN Campaigns AS cmp
				ON (adg.campaign_id = cmp.id)
			JOIN io_campaign_product_cpm AS icpc
				ON (cmp.id = icpc.campaign_id)
			JOIN campaigns_time_series AS cts
				ON (cmp.id = cts.campaigns_id AND cts.object_type_id = 0)
			LEFT JOIN io_flight_subproduct_details AS ifsd
				ON (cts.id = ifsd.io_timeseries_id)
		WHERE 
			ifsd.id IS NULL
			AND cmp.ignore_for_healthcheck = 0
		GROUP by
			cmp.id
		";

		$get_campaigns_with_cpms_but_no_budgets_result = $this->db->query($get_campaigns_with_cpms_but_no_budgets_query);
		if($get_campaigns_with_cpms_but_no_budgets_result == false || $get_campaigns_with_cpms_but_no_budgets_result->num_rows() < 1)
		{
			return false;
		}

		$campaigns = $get_campaigns_with_cpms_but_no_budgets_result->result_array();
		foreach($campaigns as $campaign)
		{
			$this->calculate_budget_for_cpm_defined_campaign($campaign['campaign_id']);
		}
	}

	public function calculate_budget_for_cpm_defined_campaign($campaign_id)
	{
		$cpms = $this->retrieve_product_cpms_for_campaign($campaign_id);
		$has_preroll = 0;
		$o_o_max_ratio = 0;
		$get_preroll_adgroups_query = 
		"SELECT
			*
		FROM
			AdGroups
		WHERE
			subproduct_type_id = 2
			AND campaign_id = ?
		";
		$get_preroll_adgroups_result = $this->db->query($get_preroll_adgroups_query, $campaign_id);
		if($get_preroll_adgroups_result != false && $get_preroll_adgroups_result->num_rows() > 0)
		{
			$has_preroll = 1;
		}
		else
		{
			$get_display_adgroups_query = 
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				subproduct_type_id = 1
				AND campaign_id = ?
			";
			$get_display_adgroups_result = $this->db->query($get_display_adgroups_query, $campaign_id);

			$get_o_o_adgroups_query = 
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				subproduct_type_id = 4
				AND campaign_id = ?
			";
			$o_o_max_ratio = 0;
			$get_o_o_adgroups_result = $this->db->query($get_o_o_adgroups_query, $campaign_id);
			if($get_o_o_adgroups_result && $get_o_o_adgroups_result->num_rows > 0)
			{
				if($get_display_adgroups_result->num_rows < 1)
				{
					$o_o_max_ratio = 100;				
				}
			}

			$get_geofencing_adgroups_query = 
			"SELECT
				*
			FROM
				AdGroups
			WHERE
				subproduct_type_id = 3
				AND campaign_id = ?
			";
			$get_geofencing_adgroups_result = $this->db->query($get_geofencing_adgroups_query, $campaign_id);


		}
		//Get flights
		$existing_flights = $this->al_model->get_campaign_timeseries_details($campaign_id);
		foreach($existing_flights as $flight)
		{
			$impressions = $flight['impressions'];

			$budget = ($impressions/1000)*$cpms['ax'];

			$update_flight_budget_value_query =
			"UPDATE
				campaigns_time_series
			SET 
				budget = ?
			WHERE 
				id = ?
			";
			$update_flight_budget_value_result = $this->db->query($update_flight_budget_value_query, array($budget, $flight['id']));
			if($update_flight_budget_value_result == false)
			{
				return false;
			}
			$add_flight_subproduct_row = $this->add_dfp_order_rows_for_flight($flight['id']);
			if($add_flight_subproduct_row == false)
			{
				return false;
			}

			if(!$has_preroll && $get_geofencing_adgroups_result->num_rows() > 0)
			{
				$geofencing_inventory = 99999999999; //I think this uses the max percent if this is super-high
			}
			else
			{
				$geofencing_inventory = 0;
			}
			$o_o_impressions = $cpms['o_o'] ? round((($o_o_max_ratio*0.01)*$budget)/$cpms['o_o'] * 1000) : 0;
			if(!$cpms['ax'])
			{
				echo $unlinked_campaign['campaign_id']." - ";
				print_r($cpms);
				echo "\n";
			}
			$budget_values = get_budget_calculation($budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $o_o_max_ratio, $geofencing_inventory);
			$saved_budget_values = $this->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, false);
			if(!$saved_budget_values)
			{
				return false;
			}
		}		
	}

	public function get_campaign_subproducts_using_adgroups($campaign_id)
	{
		$get_subproducts_query = 
		"SELECT
			*
		FROM
			subproduct_types";
		$get_subproducts_result = $this->db->query($get_subproducts_query);
		if($get_subproducts_result == false)
		{
			return false;
		}

		$subproducts = array_fill_keys(array_column($get_subproducts_result->result_array(), "name"), 0);

		$get_campaign_adgroups_query = 
		"SELECT
			adg.subproduct_type_id,
			st.name AS subproduct_name
		FROM
			AdGroups AS adg
			JOIN subproduct_types AS st
				ON (adg.subproduct_type_id = st.id)
		WHERE
			campaign_id = ?
		";
		$get_campaign_adgroups_result = $this->db->query($get_campaign_adgroups_query, $campaign_id);
		if($get_campaign_adgroups_result == false || $get_campaign_adgroups_result->num_rows() < 1)
		{
			return false;
		}

		$adgroups = $get_campaign_adgroups_result->result_array();

		foreach($adgroups as $adgroup)
		{
			$subproducts[$adgroup['subproduct_name']] = 1;
		}
		return $subproducts;
	}

	public function clone_version($adset_id, $version_id)
	{
		$clone_version_query = "
			INSERT INTO cup_versions (
				adset_id,
				variables,
				fullscreen,
				version,
				variables_js,
				variables_xml,
				variables_data,
				builder_version,
				source_id,
				show_for_io,
				created_user,
				created_timestamp,
				updated_user,
				base_64_encoded_id,
				vanity_string,
				parent_variation_id,
				variation_name,
				internally_approved_updated_user,
				campaign_id,
				cached_engagement_record_sum
			)
			SELECT
				?,
				cv.variables,
				cv.fullscreen,
				cv.version,
				cv.variables_js,
				cv.variables_xml,
				cv.variables_data,
				cv.builder_version,
				cv.source_id,
				cv.show_for_io,
				cv.created_user,
				now(),
				cv.updated_user,
				cv.base_64_encoded_id,
				cv.vanity_string,
				cv.parent_variation_id,
				cv.variation_name,
				cv.internally_approved_updated_user,
				cv.campaign_id,
				cv.cached_engagement_record_sum
			FROM
				cup_versions cv
			WHERE
				cv.id = ?
			";
		$binding = array();
		$binding[] = $adset_id;
		$binding[] = $version_id;
		$clone_version_result = $this->db->query($clone_version_query, $binding);

		if ($this->db->affected_rows() > 0)
		{
			$result_status['is_success'] = true; 
			$result_status['new_version_id']  = $this->db->insert_id();
		}
		else
		{
			$result_status['is_success'] = false; 
		}

		return $result_status;

	}

	public function clone_creatives($new_version_id, $version_id)
	{
		$clone_creatives_query = "
			INSERT INTO cup_creatives (
				size,
				version_id,
				ad_tag,
				dfa_advertiser_id,
				dfa_campaign_id,
				dfa_placement_id,
				dfa_creative_id,
				dfa_ad_id,
				ttd_creative_id,
				adtech_ad_tag,
				adtech_flight_id,
				adtech_campaign_id,
				published_ad_server,
				dfa_used_adchoices,
				adtech_used_adchoices,
				ttd_creative_landing_page
			)
			SELECT
				cc.size,
				?,
				cc.ad_tag,
				cc.dfa_advertiser_id,
				cc.dfa_campaign_id,
				cc.dfa_placement_id,
				cc.dfa_creative_id,
				cc.dfa_ad_id,
				cc.ttd_creative_id,
				cc.adtech_ad_tag,
				cc.adtech_flight_id,
				cc.adtech_campaign_id,
				cc.published_ad_server,
				cc.dfa_used_adchoices,
				cc.adtech_used_adchoices,
				cc.ttd_creative_landing_page
			FROM
				cup_creatives cc
			WHERE
				cc.version_id = ?
			";
		$binding = array();
		$binding[] = $new_version_id;
		$binding[] = $version_id;
		$clone_creatives_result = $this->db->query($clone_creatives_query, $binding);

		if ($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	public function clone_ad_assets($new_version_id, $product_creative_details)
	{
		$new_creatives_query = "
			SELECT
				id,
				size
			FROM
				cup_creatives 
			WHERE
				version_id = ?
			";

		$new_creatives_result = $this->db->query($new_creatives_query, $new_version_id);

		if ($new_creatives_result !== false && $new_creatives_result->num_rows() > 0)
		{
			$new_creatives_details = $new_creatives_result->result_array();
		}
		else
		{		    
			return false;
		}

		foreach($new_creatives_details as $creative_details)
		{
			foreach($product_creative_details as $old_creative_details)
			{
				if($creative_details['size'] == $old_creative_details['size'])
				{
		    			$clone_ad_assets_query = "
						INSERT INTO cup_ad_assets (
		    					type,
							open_uri,
							ssl_uri,
							creative_id,
		   					is_archived,
							weight,
							extension
						)
						SELECT
		    					caa.type,
							caa.open_uri,
							caa.ssl_uri,
							?,
							caa.is_archived,
							caa.weight,
							caa.extension
						FROM
							cup_ad_assets caa
						WHERE
					    		caa.creative_id = ?
						";
					$binding = array();
					$binding[] = $creative_details['id'];
					$binding[] = $old_creative_details['creative_id'];
					$clone_creatives_result = $this->db->query($clone_ad_assets_query, $binding);
					if ($this->db->affected_rows() > 0)
					{
						$is_success = true;
				    	}
					else
				    	{
						return false;
					}
				}
			}
		}

		return $is_success;

	}
	
	public function update_cp_io_with_O_O_version_id($version_id, $product_id)
	{
		$update_cp_io_with_O_O_version_id_data_query =
		"UPDATE 
			cp_io_join_cup_versions
		SET 
			o_o_cup_versions_id  = ?
		WHERE 
			cp_submitted_products_id = ?;
		";
		$bindings = array($version_id, $product_id);
		$this->db->query($update_cp_io_with_O_O_version_id_data_query, $bindings);
		if ($this->db->affected_rows() > 0)
		{
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function get_o_o_enabled_status($cp_product_id)
	{
		$creative_sizes_array = '';
		$sql = " 
			SELECT 
				o_o_enabled
			FROM
				cp_products
			WHERE
				id = ?
			";
		$result = $this->db->query($sql, $cp_product_id);
		if ($result !== false)
		{
			$return_val = $result->result_array();
			return $return_val[0]['o_o_enabled'];
		}

		return false; 
	}
	
	public function check_status_for_dfp_forecast_cron()
	{
		$result = array('status'=>'COMPLETE');
		$forecast_cron_query = 
					"	
						SELECT
							status,
							last_run
						FROM
							cron_status
						WHERE
							name = 'dfp_forecast'
					";

		$query_result = $this->db->query($forecast_cron_query);
		
		if ($query_result != false)
		{
			$result = $query_result->row_array();
			$status = $result['status'];
			
			if (strtoupper($status) == 'COMPLETE')
			{
				$this->update_status_for_dfp_forecast_cron('IN_PROGRESS');
			}
		}
		
		return $result;
	}
	
	public function update_status_for_dfp_forecast_cron($status)
	{
		if (!empty($status))
		{
			$query = 
				"	
					UPDATE 
						cron_status
					SET 
						status = '$status'
					WHERE 
						name = 'dfp_forecast'
				";
			$this->db->query($query);			
		}
		
		if ($this->db->affected_rows() > 0)
		{
			
			return true;
		}
		else
		{
			return false;
		}		
	}
}

