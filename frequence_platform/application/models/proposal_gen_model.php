<?php

class Proposal_gen_model extends CI_Model
{
    public function __construct()
    {
		$this->load->database();
    }
    /**
     * loads each option from the media_plan_sessions table and loads it into a JSON for display to the
     * sales user constructing an automated proposal.  There needs to be a column that has the information
     * about the option number index
     *
     * @param $sessionId is the session ID that was linked to the demotool instance for a given client.
     * should be tagged to the sales user, and kept held in the database.
     *
     * @return a JSON formatted object with all information necessary for processing the required options.
     */
    public function loadOptionsData($sessionId)
    {
		$optionsData = "";
		$bindings = array($sessionId);
		$query = "SELECT `region_data` FROM `media_plan_sessions` WHERE `session_id` = ?";
		//we willl pull all information here from all for the

		$queryArray = $this->db->query($the_query, $bindings);
		$queryResponse = $queryArray->result_array();

		return $optionsData;
	}

	public function get_lap_ids_from_mpq_id($mpq_id)
	{
		$lap_ids = array();
		$sql =
		"	SELECT
				lap_id
			FROM
				prop_gen_sessions
			WHERE
				source_mpq = ?
			ORDER BY lap_id ASC;
		";
		$result = $this->db->query($sql, $mpq_id);
		if($result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			$lap_ids = array_column($result_array, 'lap_id');
		}
		return $lap_ids;
	}

	public function get_lap_ids_from_mpq_id_for_snapshots($mpq_id)
	{
		$lap_ids = [];
		$sql =
		"	SELECT
				IF(pgsd.lap_id IS NULL, pgs.lap_id, NULL) AS lap_id
			FROM
				prop_gen_sessions AS pgs
			LEFT JOIN
				prop_gen_snapshot_data AS pgsd
				ON pgsd.lap_id = pgs.lap_id
			WHERE
				pgs.source_mpq = ?
			ORDER BY lap_id ASC;
		";
		$result = $this->db->query($sql, $mpq_id);
		if($result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			$lap_ids = array_column($result_array, 'lap_id');
		}
		return $lap_ids;
	}

	/**
	 * #savesnapshot #snapshot
	 * actually writes the snapshot data to the prop_gen_snapshot_data table.
	 *
	 */
	public function write_lap_image($lap_id, $prop_id, $img_data, $img_title)
	{
		$query_bindings = array($lap_id, $prop_id);
		$query =
		"	SELECT
				1
			FROM
				prop_gen_snapshot_data
			WHERE
				lap_id = ? AND
				prop_id = ? AND
				snapshot_num = 1;
		";
		$select_result = $this->db->query($query, $query_bindings);
		$record_exists = $select_result->num_rows() > 0;

		if($record_exists)
		{
			$delete_query =
			"	DELETE FROM
					prop_gen_snapshot_data
				WHERE
					lap_id = ? AND
					prop_id = ?;
			";
			$this->db->query($delete_query, $query_bindings);
		}

		$insert_query =
		"	INSERT INTO prop_gen_snapshot_data
				(lap_id, prop_id, snapshot_num, snapshot_title, snapshot_data)
			VALUES
				(?, ?, 1, ?, ?)
			ON DUPLICATE KEY UPDATE
				snapshot_title = VALUES(snapshot_title),
				snapshot_data = VALUES(snapshot_data);
		";

		$query_bindings[] = $img_title;
		$query_bindings[] = $img_data;

		$this->db->query($insert_query, $query_bindings);
	}

	// Overwrites the overview image for a prop if one exists,
	// otherwise inserts new
	public function write_prop_overview_image($prop_id, $img_data, $img_title)
	{
		$query =
		"	SELECT
				1
			FROM
				prop_gen_snapshot_data
			WHERE
				lap_id IS NULL AND
				prop_id = ? AND
				snapshot_num = 1;
		";
		$select_result = $this->db->query($query, $prop_id);
		$record_exists = $select_result->num_rows() > 0;

		if($record_exists)
		{
			$delete_query =
			"	DELETE FROM
					prop_gen_snapshot_data
				WHERE
					lap_id IS NULL AND
					prop_id = ?;
			";
			$this->db->query($delete_query, $prop_id);
		}

		$insert_query =
		"	INSERT INTO prop_gen_snapshot_data
				(lap_id, prop_id, snapshot_num, snapshot_title, snapshot_data)
			VALUES
				(NULL, ?, 1, ?, ?);
		";
		$this->db->query($insert_query, array($prop_id, $img_title, $img_data));
	}

	public function write_rooftops_image($prop_id, $img_data)
	{
		$sql =
		"	UPDATE prop_gen_prop_data
				SET rooftops_snapshot = ?
			WHERE prop_id = ?
		";
		$this->db->query($sql, array($img_data, $prop_id));
	}

    /**EDIT IMAGES FOR A SPECIFIC LAP**/
    /*
      Takes lap_id and a bunch of inputs of the snapshot data rows and updates them
    */
    public function editLapImages($lap_id, $prop_id, $input)
    {
		$counter = 0;
		$fake_num = 9000;
		$error_string = "";
		if (is_null($lap_id))
		{
			foreach($input as $v)
			{
				$sql = "
					UPDATE prop_gen_snapshot_data
					SET
						snapshot_title = ?
					WHERE
						lap_id IS NULL
						AND prop_id = ?
				";
				$query = $this->db->query($sql, array($v['title'], $prop_id));
				if ($this->db->affected_rows() == 0)
				{
					$error_string .= "<br>Final Error updating snapshot: No snapshot found with Proposal ID #" . $prop_id;
				}
			}
		}
		else
		{
			foreach($input as $v)
			{
				$sql = "
					UPDATE prop_gen_snapshot_data
					SET
						snapshot_title = ?
					WHERE
						lap_id = ?
						AND prop_id = ?
				";
				$query = $this->db->query($sql, array($v['title'], $lap_id, $prop_id));
				if ($this->db->affected_rows() == 0)
				{
					$error_string .= "<br>Final Error updating snapshot: No snapshot found with Proposal ID #" . $prop_id;
				}
			}
		}
		if($error_string == "")
		{
			$error_string = "Edit Successful";
		}
		return $error_string;
    }
    public function deleteLapImage($lap_id, $prop_id, $snapshot)
    {
    	if (is_null($lap_id))
    	{
			$sql = "DELETE FROM prop_gen_snapshot_data WHERE lap_id IS NULL AND prop_id = ? AND snapshot_num = ?";
			$bind = array($prop_id, $snapshot);
		}
		else
		{
			$sql = "DELETE FROM prop_gen_snapshot_data WHERE lap_id = ? AND prop_id = ? AND snapshot_num = ?";
			$bind = array($lap_id, $prop_id, $snapshot);
		}
		$query = $this->db->query($sql, $bind);
		if($this->db->affected_rows() == 0)
		{
			return "Deletion Failed";
		}
		return "Deletion Successful";
	}

	public function getLapImages($lap_id, $prop_id)
	{
		if (is_null($lap_id))
		{
			$sql = "SELECT * FROM prop_gen_snapshot_data WHERE lap_id IS NULL AND prop_id = ? AND snapshot_num = 1;";
			$bind = array($prop_id);
		}
		else
		{
			$sql = "SELECT * FROM prop_gen_snapshot_data WHERE lap_id = ? AND prop_id = ? AND snapshot_num = 1";
			$bind = array($lap_id, $prop_id);
		}
		$query = $this->db->query($sql, $bind);
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return NULL;
	}

    /**
     * loads the data for all laps saved in the prop_gen_sessions table and loads the relvant preview information.
     *
     */
    public function loadLaps() {
		// this will eventually need to be limited, as you make more and more calls to the database you are going to either want to include a
		// control to delete entries or to increase the scope of how carefully you are screening them in the database here before shooting your
		// query back to the front end to be chomped and otherwise analyzed.

		$sql = "SELECT lap_id, plan_name, advertiser, recommended_impressions, date_created, lap_save_time FROM `prop_gen_sessions` ORDER BY lap_id DESC";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();

		}
		return array();
    }

	public function load_laps_select2($term, $start, $limit)
   	{
		$sql = "
			SELECT
				lap_id AS id,
				plan_name,
				advertiser,
				population,
				demo_population
			FROM
				`prop_gen_sessions`
			WHERE
				plan_name LIKE ?
				OR advertiser LIKE ?
				OR lap_id LIKE ?
			ORDER BY lap_id DESC
			LIMIT ?,?
		";
		$bind = array($term, $term, $term, $start, $limit);

		$query = $this->db->query($sql, $bind);
		if($query->num_rows() > 0)
		{
			$result = $query->result_array();
			foreach($result as &$lap)
			{
				$lap['population'] = number_format($lap['population']);
				$lap['demo_population'] = number_format($lap['demo_population']);
			}
			return $result;

		}
		return array();
    }

    public function load_reps_select2($term, $start, $limit)
   	{
		$sql =
		"	SELECT
				usr.id AS id,
				usr.firstname AS first_name,
				usr.lastname AS last_name,
				wlpd.partner_name AS partner_name
			FROM
				users AS usr
				LEFT JOIN wl_partner_details AS wlpd
				ON usr.partner_id = wlpd.id
			WHERE (
				LOWER(usr.firstname) LIKE LOWER(?)
				OR LOWER(usr.lastname) LIKE LOWER(?)
				OR LOWER(wlpd.partner_name) LIKE LOWER(?)
			)
			AND usr.role = 'SALES'
			AND usr.banned != '1'
			ORDER BY id ASC
			LIMIT ?,?
		";
		$bind = array($term, $term, $term, $start, $limit);

		$query = $this->db->query($sql, $bind);
		if($query->num_rows() > 0)
		{
			return $query->result_array();

		}
		return array();
    }

	public function get_submitted_mpqs_insertion_orders($start = false, $limit = false)
	{
		$sql_bindings = array();
		$limit_string = "";
		if($start !== false AND $limit !== false)
		{
			$start = (int)$start;
			$limit = (int)$limit;
			$limit_string = "LIMIT ?, ?";
			$sql_bindings[] = $start;
			$sql_bindings[] = $limit;
		}

		$sql = "SELECT
				mss.id,
				mss.creation_time,
				mss.advertiser_name,
				mss.advertiser_website,
				mss.submitter_relationship,
				mss.submitter_name,
				mss.submitter_agency_website,
				mss.adset_request_id,
				mss.region_data,
				mss.submitter_agency_website,
				NOT ISNULL(mss.mailgun_error_text) as has_mailgun_error,
				COUNT(DISTINCT(moap.id)) AS num_options,
				IF(u.id IS NOT NULL,
					CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')),
					'public user'
				) AS user_name,
				pgpd.prop_id,
				pgpd.prop_name,
				pgs.lap_id as mpq_lap_id,
				site_list IS NOT NULL as has_sitelist,
				COUNT(DISTINCT(pgpd.prop_id)) AS num_derived_proposals,
				COUNT(DISTINCT(pgs.lap_id)) AS num_derived_lap_geos,
				mio.id as mio_id,
				mio.landing_page as mio_landing_page,
				mio.impressions as mio_impressions,
				mio.start_date as mio_start_date,
				mio.term_type as mio_term_type,
				mio.term_duration as mio_term_duration,
				mio.end_date as mio_end_date,
				wpd.partner_name as partner_name,
				GROUP_CONCAT(
					c.id
					ORDER BY c.id DESC
					SEPARATOR ','
				) as campaign_ids,
				GROUP_CONCAT(
					c.name
					ORDER BY c.id DESC
					SEPARATOR ','
				) as campaign_names
			FROM
				mpq_sessions_and_submissions AS mss
				LEFT JOIN users AS u ON u.id = mss.creator_user_id
				LEFT JOIN prop_gen_prop_data pgpd ON pgpd.source_mpq = mss.id
				LEFT JOIN prop_gen_sessions AS pgs ON pgs.source_mpq = mss.id
				LEFT JOIN mpq_insertion_orders AS mio ON mio.mpq_id = mss.id
				LEFT JOIN mpq_options_ad_plans AS moap ON moap.mpq_id = mss.id
				LEFT JOIN wl_partner_details AS wpd ON wpd.id = u.partner_id
				LEFT JOIN Campaigns AS c ON c.insertion_order_id = mio.id
			WHERE
				mss.is_submitted = 1
				AND
				mio.id IS NOT NULL
			GROUP BY
				mss.id,
				mio_id
			ORDER BY
				mss.creation_time DESC ".$limit_string;

		$response = $this->db->query($sql, $sql_bindings);
		return $response->result_array();
	}

	public function get_submitted_mpqs_proposals($start = false, $limit = false)
	{
		$sql_bindings = array();
		$limit_string = "";
		if($start !== false AND $limit !== false)
		{
			$start = (int)$start;
			$limit = (int)$limit;
			$limit_string = "LIMIT ?, ?";
			$sql_bindings[] = $start;
			$sql_bindings[] = $limit;
		}

		$sql = "SELECT
				mss.id,
				mss.creation_time,
				mss.advertiser_name,
				mss.advertiser_website,
				mss.submitter_relationship,
				mss.submitter_name,
				mss.submitter_agency_website,
				COUNT(DISTINCT(moap.id)) AS num_options,
				IF(u.id IS NOT NULL,
					CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')),
					'public user'
				) AS user_name,
				pgpd.prop_id,
				pgpd.prop_name,
				pgs.lap_id as mpq_lap_id,
				site_list IS NOT NULL as has_sitelist,
				COUNT(DISTINCT(pgpd.prop_id)) AS num_derived_proposals,
				COUNT(DISTINCT(pgs.lap_id)) AS num_derived_lap_geos,
				wpd.partner_name as partner_name
			FROM
				mpq_sessions_and_submissions AS mss
				LEFT JOIN users AS u ON u.id = mss.creator_user_id
				LEFT JOIN prop_gen_prop_data pgpd ON pgpd.source_mpq = mss.id
				LEFT JOIN prop_gen_sessions AS pgs ON pgs.source_mpq = mss.id
				LEFT JOIN mpq_options_ad_plans AS moap ON moap.mpq_id = mss.id
				LEFT JOIN wl_partner_details AS wpd ON wpd.id = u.partner_id
			WHERE
				mss.is_submitted = 1
				AND
				moap.id IS NOT NULL
			GROUP BY
				mss.id
			ORDER BY
				mss.creation_time DESC ".$limit_string;

		$response = $this->db->query($sql, $sql_bindings);

		$prop_list = $response->result_array();
		$prop_id_list = array();
		$prop_id_string = '';

		foreach ($prop_list as &$prop)
		{
			if (isset($prop['prop_id']))
			{
				if ($prop_id_string != "")
				{
					$prop_id_string .= ",";
				}
				$prop_id_string .= '?';

				$prop_id_list[] = $prop['prop_id'];
			}
		}

		if (strlen($prop_id_string) > 0)
		{

			$sql = "
				SELECT
					option_id,
					option_name,
					prop_id
				FROM
					prop_gen_option_prop_join
				WHERE
					prop_id IN (".$prop_id_string.")
				ORDER BY
					prop_id DESC,
					option_id ASC
				";
			$option_response =  $this->db->query($sql, $prop_id_list);

			$option_list = $option_response->result_array();

			$option_list_keyed = array();

			foreach ($option_list as $opt)
			{
				$option_list_keyed[strval($opt['prop_id'])][] = array(
					'option_id' => $opt['option_id'],
					'option_name' => $opt['option_name']
				);
			}

			foreach ($prop_list as &$prop)
			{
				if (array_key_exists($prop['prop_id'], $option_list_keyed))
				{
					$prop['options_array'] = $option_list_keyed[strval($prop['prop_id'])];
				}
			}
		}

		return $prop_list;
	}

	public function get_submitted_mpqs_rfps($start = false, $limit = false)
	{
		$sql_bindings = array();
		$limit_string = "";
		if($start !== false AND $limit !== false)
		{
			$start = (int)$start;
			$limit = (int)$limit;
			$limit_string = "LIMIT ?, ?";
			$sql_bindings[] = $start;
			$sql_bindings[] = $limit;
		}

		$sql = "SELECT
				mss.id,
				mss.creation_time,
				REPLACE(mss.advertiser_name,\"'\",\"\") AS advertiser_name,
				mss.advertiser_website,
				mss.submitter_relationship,
				mss.submitter_name,
				mss.submitter_agency_website,
				mss.region_data,
				IF(u.id IS NOT NULL,
					CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')),
					'public user'
				) AS user_name,
				pgpd.prop_id,
				pgpd.prop_name,
				pgs.lap_id as mpq_lap_id,
				site_list IS NOT NULL as has_sitelist,
				COUNT(DISTINCT(pgpd.prop_id)) AS num_derived_proposals,
				COUNT(DISTINCT(pgs.lap_id)) AS num_derived_lap_geos,
				COUNT(DISTINCT(mij.iab_category_id)) AS contextual_count,
				GROUP_CONCAT(DISTINCT iab.tag_friendly_name SEPARATOR ', ') AS contextual_string,
				wpd.partner_name as partner_name,
				pgpd.pdf_location,
				pgpd.process_status,
				pgpd.date_modified,
				ind.name as industry_name,
				REPLACE(mss.original_submission_summary_html,\"'\",\"\") AS original_submission_summary_html
			FROM
				mpq_sessions_and_submissions AS mss
				LEFT JOIN users AS u ON u.id = mss.creator_user_id
				LEFT JOIN prop_gen_prop_data pgpd ON pgpd.source_mpq = mss.id
				LEFT JOIN prop_gen_sessions AS pgs ON pgs.source_mpq = mss.id
				LEFT JOIN wl_partner_details AS wpd ON wpd.id = u.partner_id
				LEFT JOIN freq_industry_tags AS ind ON mss.industry_id = ind.freq_industry_tags_id
				LEFT JOIN mpq_iab_categories_join AS mij ON mss.id = mij.mpq_id
				LEFT JOIN iab_categories AS iab ON iab.id = mij.iab_category_id
			WHERE
				mss.is_submitted = 1
				AND
				pgpd.process_status IS NOT NULL
			GROUP BY
				mss.id
			ORDER BY
				mss.creation_time DESC ".$limit_string;

		$response = $this->db->query($sql, $sql_bindings);

		$prop_list = $response->result_array();
		$prop_id_list = array();
		$prop_id_string = '';

		foreach ($prop_list as &$prop)
		{
			if (isset($prop['prop_id']))
			{
				if ($prop_id_string != "")
				{
					$prop_id_string .= ",";
				}
				$prop_id_string .= '?';

				$prop_id_list[] = $prop['prop_id'];
			}

			if(!empty($prop['region_data']))
			{
				$raw_region_data = json_decode($prop['region_data'], true);
				$prop['regions_list'] = array();
				if(is_array($raw_region_data))
				{
					foreach($raw_region_data as $single_region_data)
					{
						if(is_array($single_region_data) && array_key_exists('page', $single_region_data))
						{
							$name = $single_region_data['user_supplied_name'];

							$prop['regions_list'][] = array(
								'id' => $single_region_data['page'],
								'name' => $name,
							);
						}
						else
						{
							// TODO: some elements in this array are just `int 1` or numerically indexed array. What are these?

							// var_dump($single_region_data); // XXX
						}
					}
				}
				unset($prop['region_data']);
			}
		}
		/*
		if (strlen($prop_id_string) > 0)
		{

			$sql = "
				SELECT
					option_id,
					option_name,
					prop_id
				FROM
					prop_gen_option_prop_join
				WHERE
					prop_id IN (".$prop_id_string.")
				ORDER BY
					prop_id DESC,
					option_id ASC
				";
			$option_response =  $this->db->query($sql, $prop_id_list);

			$option_list = $option_response->result_array();

			$option_list_keyed = array();

			foreach ($option_list as $opt)
			{
				$option_list_keyed[strval($opt['prop_id'])][] = array(
					'option_id' => $opt['option_id'],
					'option_name' => $opt['option_name']
				);
			}

			foreach ($prop_list as &$prop)
			{
				if (array_key_exists($prop['prop_id'], $option_list_keyed))
				{
					$prop['options_array'] = $option_list_keyed[strval($prop['prop_id'])];
				}
			}
		}*/

		return $prop_list;
	}

    public function get_all_proposals_and_options($start = false, $limit = false)
    {
		$return_array = array('prop_list' => array(), 'option_list' => array());
		$sql_bindings = array();
		$limit_string = "";
		if($start !== false AND $limit !== false)
		{
			$start = (int)$start;
			$limit = (int)$limit;
			$limit_string = "LIMIT ?, ?";
			$sql_bindings[] = $start;
			$sql_bindings[] = $limit;
		}
		$sql = 'SELECT pgpd.*, pgs.advertiser
				FROM prop_gen_prop_data pgpd
				LEFT JOIN prop_gen_sessions pgs
				ON pgpd.source_mpq = pgs.source_mpq
				WHERE 1 ORDER BY pgpd.date_modified DESC '.$limit_string;
		$query = $this->db->query($sql, $sql_bindings);
		if($query->num_rows() > 0)
		{
			$prop_list = $query->result_array();
			$sql_bindings = array();
			$prop_string = "";
			foreach($prop_list as $prop)
			{
				if($prop_string != "")
				{
					$prop_string .= ', ';
				}
				$prop_string .= '?';
				$sql_bindings[] = $prop['prop_id'];
			}
			$sql = 'SELECT option_id, option_name, prop_id FROM prop_gen_option_prop_join where prop_id IN ('.$prop_string.') ORDER BY FIELD(prop_id,'.$prop_string.'), option_id ASC';
			$query = $this->db->query($sql, array_merge($sql_bindings, $sql_bindings));
			$option_list = $query->result_array();
			$return_array['prop_list'] = $prop_list;
			$return_array['option_list'] = $option_list;
		}
		return $return_array;
	}

	/**
	 * #hardcoded order-dependant.
	 * Loads the requested session and lapID back from the prop_gen_sessions table to the media_plan_sessions table
	 * and replaces any previous instance within the scratch table.
	 *
	 */
	public function load_to_media_sessions($session_id, $force_lap_id)
	{
		$bindings = array($session_id, $force_lap_id);
		$sql =
		"	REPLACE INTO media_plan_sessions(
				lap_id,
				is_saved_lap,
				plan_name,
				advertiser,
				notes,
				recommended_impressions,
				price_notes,
				region_data,
				population,
				demo_population,
				demographic_data,
				site_array,
				selected_channels,
				has_retargeting,
				rf_geo_coverage,
				rf_gamma,
				rf_ip_accuracy,
				rf_demo_coverage,
				internet_average,
				session_id,
				selected_iab_categories,
				source_mpq
			)
			SELECT
				lap_id,
				1,
				plan_name,
				advertiser,
				notes,
				recommended_impressions,
				price_notes,
				region_data,
				population,
				demo_population,
				demographic_data,
				site_array,
				selected_channels,
				has_retargeting,
				rf_geo_coverage,
				rf_gamma,
				rf_ip_accuracy,
				rf_demo_coverage,
				internet_average,
				?,
				selected_iab_categories,
				source_mpq
			FROM `prop_gen_sessions`
			WHERE `lap_id` = ?
		";
		$result = $this->db->query($sql, $bindings);
		if($this->db->affected_rows() > 0)
		{
			$lap_id = $force_lap_id;
			$sql = "DELETE FROM media_plan_sessions_iab_categories_join WHERE media_plan_lap_id = ?";
			$query = $this->db->query($sql, $lap_id);

			$sql = "INSERT INTO media_plan_sessions_iab_categories_join (media_plan_lap_id, iab_category_id) SELECT prop_gen_lap_id, iab_category_id FROM prop_gen_sessions_iab_categories_join WHERE prop_gen_lap_id = ?";
			$query = $this->db->query($sql, $lap_id);
		}
	}

    public function get_owner_name($owner_id)
    {
		$bindings = array($owner_id);
		$query = "SELECT firstname, lastname FROM users WHERE id = ?";
		$result = $this->db->query($query, $bindings);
		if($result->num_rows() > 0)
		{
			$result_row = $result->row();
			return $result_row->firstname.' '.$result_row->lastname;
		}
		else
		{
			return 'Unknown user id: '.$owner_id;
		}
    }

	/**
	 * loads all relevant information from the save sidetab into the media plan session table before shooting the finished product
	 * over to another database table prop_gen_sessions. #wufoo
	 *
	 * @param $session_id exists for legacy support.
	 */
	public function loadAdvertiserData($session_id, $advertiser, $plan_name, $notes)
	{
		$bindings = array($advertiser, $plan_name, $notes, $session_id);
		$sql =
		"	UPDATE
				`media_plan_sessions`
			SET
				`advertiser`= ?,
				`plan_name`= ?,
				`notes`= ?
			WHERE
				`session_id` = ?
		";
		$this->db->query($sql, $bindings);

		//save the lap by creating a copy of the current scratch row with the saved flag put up.
		$bindings = array($session_id);
		$the_query ="REPLACE INTO `prop_gen_sessions` ( lap_id, plan_name, advertiser, lap_save_time, notes, recommended_impressions, price_notes, region_data, population, demo_population, demographic_data, site_array, selected_channels, has_retargeting, rf_geo_coverage, rf_gamma, rf_ip_accuracy, rf_demo_coverage, internet_average, owner_id, date_created, selected_iab_categories, source_mpq) SELECT lap_id, plan_name, advertiser, lap_save_time, notes, recommended_impressions, price_notes, region_data, population, demo_population,  demographic_data, site_array, selected_channels, has_retargeting, rf_geo_coverage, rf_gamma, rf_ip_accuracy, rf_demo_coverage, internet_average, owner_id, date_created, selected_iab_categories, source_mpq FROM `media_plan_sessions` WHERE `session_id`= ?";
		$this->db->query($the_query, $bindings);

		$sql = "SELECT lap_id FROM media_plan_sessions WHERE session_id = ?";
		$query = $this->db->query($sql, $session_id);
		if($query->num_rows() > 0)
		{
			$lap_id = $query->row()->lap_id;
			$sql = "DELETE FROM prop_gen_sessions_iab_categories_join WHERE prop_gen_lap_id = ?";
			$query = $this->db->query($sql, $lap_id);

			$sql = "INSERT INTO prop_gen_sessions_iab_categories_join (prop_gen_lap_id, iab_category_id) SELECT media_plan_lap_id, iab_category_id FROM media_plan_sessions_iab_categories_join WHERE media_plan_lap_id = ?";
			$query = $this->db->query($sql, $lap_id);
		}
		//***FLAG*** restart the scratch index that we should be pointing to with each edit by making it the most recent instance of that particular lap_id
	}

	/**
	 * #prop_full_data #database_write
	 * takes in data scraped by the option engine, including groupings of ad plans, options, discounts,
	 * lap information, term information, essentially, this should be everything that the proposal generator
	 * needs to build a complete automated proposal for our client.
	 *
	 * @return nothing, simply writes out to the prop_full_data table in the database
	 */
	public function saveProposal($data, $prop_id = "")
	{
		$raw_options = $data['prop_data'];
		if($prop_id == "")
		{
			$sql = "INSERT INTO prop_gen_prop_data (prop_name, show_pricing, rep_id) VALUES (?, ?, ?)";
			$query = $this->db->query($sql, array($data['prop_name'], $data['show_pricing'], $data['rep_id']));
			$prop_id = $this->db->insert_id();
		}
		else
		{
			$the_query = "UPDATE prop_gen_prop_data SET prop_name = ?, show_pricing = ?, rep_id = ? WHERE prop_id = ?";
			$the_query_result = $this->db->query($the_query, array($data['prop_name'], $data['show_pricing'], $data['rep_id'], $prop_id));
			$delete_query = "DELETE FROM prop_gen_option_prop_join WHERE prop_id = ?";  //Clear database of prop data if the id already exists since we're about to insert new data
			$this->db->query($delete_query, array($prop_id, $prop_id));
			$delete_query = "DELETE FROM prop_gen_service_option_join WHERE prop_id = ?";
			$this->db->query($delete_query, $prop_id);
			$delete_query = "DELETE FROM prop_gen_adplan_option_join WHERE prop_id = ?";
			$this->db->query($delete_query, $prop_id);
		}

		for($i = 0; $i < count($raw_options); $i++)
		{
			$creative_design = $raw_options[$i]['creative_design'];
			$option_name = $raw_options[$i]['option_name'];

			$monthly_cost_raw = $raw_options[$i]['monthly_raw_cost'];
			$monthly_cost = $raw_options[$i]['monthly_total_cost'];
			$monthly_percent_discount = $raw_options[$i]['discount_monthly_percent'];
			$cost_by_campaign = $raw_options[$i]['cost_by_campaign'];

			/////////////LVL1 SQL QUERIES////////////////////////////////////////////////////////

			$select_query = "SELECT session_id from media_plan_sessions where lap_id = ?";
			$this->db->query($select_query, $raw_options[$i]['laps'][0]['lap_id']);  //NOT SURE WHATS HAPPENING HERE, SET LAP INDEX TO 0 FOR QUERY - Will
			//I figured out whats happening here, though we're only allowing a single lap across all the adplans right now.  This needs to be able to get multiple lap ids.

			$bindings = array($i, $prop_id, $option_name, $monthly_cost_raw, $monthly_percent_discount, $monthly_cost, $cost_by_campaign);
			$sql =
			"	INSERT INTO
					prop_gen_option_prop_join (option_id, prop_id, option_name, monthly_cost_raw, monthly_percent_discount, monthly_cost, cost_by_campaign)
				VALUES
					(?, ?, ?, ?, ?, ?, ?)
			";
			$this->db->query($sql, $bindings);

			if ($creative_design == 1)
			{
				$bindings = array($prop_id, $i);
				$sql =
				"	INSERT INTO
						prop_gen_service_option_join (service_id, prop_id, option_id)
					VALUES
						('3', ?, ?)
				";
				$this->db->query($sql, $bindings);
			}

			////////////////////////////////////////END LVL1 SQL/////////////////////////////////
			for($j = 0; $j < count($raw_options[$i]['laps']); $j++)
			{
				$impressions = $raw_options[$i]['laps'][$j]['impressions'];
				$term = $raw_options[$i]['laps'][$j]['term'];
				$retargeting = $raw_options[$i]['laps'][$j]['retargeting'];
				$lap_id = $raw_options[$i]['laps'][$j]['lap_id'];
				$retargeting_price = $raw_options[$i]['laps'][$j]['retargeting_price'];
				$period_type = $raw_options[$i]['laps'][$j]['period_type'];
				$budget = $raw_options[$i]['laps'][$j]['budget'];
				$gamma = $raw_options[$i]['laps'][$j]['gamma'];
				$ip_accuracy = $raw_options[$i]['laps'][$j]['ip_accuracy'];
				$demo_coverage = $raw_options[$i]['laps'][$j]['demo_coverage'];
				$custom_impression_cpm = ($raw_options[$i]['laps'][$j]['custom_impression_cpm'] == 'null' ? null : $raw_options[$i]['laps'][$j]['custom_impression_cpm']);
				$custom_retargeting_cpm = ($raw_options[$i]['laps'][$j]['custom_retargeting_cpm'] == 'null' ? null : $raw_options[$i]['laps'][$j]['custom_retargeting_cpm']);

				/////////////LVL2 SQL QUERIES////////////////////////////////////////////////////////
				$bindings = array(
					$lap_id,
					$prop_id,
					$i,
					$budget,
					$impressions,
					$retargeting,
					$retargeting_price,
					$term,
					$period_type,
					$gamma,
					$ip_accuracy,
					$demo_coverage,
					$custom_impression_cpm,
					$custom_retargeting_cpm
				);
				$sql =
				"	INSERT INTO
						prop_gen_adplan_option_join (lap_id, prop_id, option_id, budget, impressions, retargeting, retargeting_price, term, period_type, gamma, ip_accuracy, demo_coverage, custom_impression_cpm, custom_retargeting_cpm)
					VALUES
						(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				";
				$this->db->query($sql, $bindings);

				////////////////////////////////////////END LVL2 SQL/////////////////////////////////
			}
		}
		//returns the lap and prop id to be displayed in the option engine upon completion.
		//$string_cat = "&nbsp;Lap ID = ".$lap_id." | Prop ID = ".$prop_id."<br><a target='_blank' href='/proposal_builder/lap_image_gen/".$lap_id."/".$prop_id."'>Generate images for this lap</a>&nbsp;<a target='_blank' href='/proposal_builder/create_pdf/".$prop_id."'>Download Proposal PDF</a>&nbsp;<a target='_blank' href='/proposal_builder/control_panel/".$prop_id."'>Display Proposal</a>";
		return $prop_id;
	}

	public function &add_channel_key_and_get_array($channel_key, &$channel_set)
	{
		if(!array_key_exists($channel_key, $channel_set))
		{
			$channel_set[$channel_key] = array();
		}

		return $channel_set[$channel_key];
	}

    /**
     *
     *
     * function to return an array giving the reach and frequency statistics when given a line in the ad plan.  Includes impressions retargeting into
     * reach frequency calc.  Currently a placeholder function for the data rendering step.
     */
    public function getReachFrequency($population, $demo_population, $month, $impressions, $rf_geo_coverage, $rf_gamma, $rf_ip_accuracy, $rf_demo_coverage, $internet_average, $lap_id, $retargeting = 1){
		$demo_population = intval($demo_population);
		$population = intval($population);
		$impressions = intval($impressions)*$month;
		$internet_average = floatval($internet_average);

		$geo = array('geo_pop' => $population, 'demo_pop' => ($demo_population/$population), 'internet_average' => $internet_average);
		$rf = implode("|", array($impressions, $rf_geo_coverage, $rf_gamma, $rf_ip_accuracy, $rf_demo_coverage));
		$RON = $this->get_RON_demo_coverage($rf, $internet_average, $demo_population);


		$CI =& get_instance();
		$CI->load->model('rf_model', 'rf', true);

		$reach_frequency = $this->rf->calculate_reach_and_frequency($rf, $geo, $RON, 1, NULL, $retargeting, $lap_id, $retargeting);
		$new_demo = intval($demo_population);
		$rf_test = $reach_frequency['time_series']['demo_reach'];
		$reach_percent = $reach_frequency['time_series']['demo_reach'][0];
		$reach = $reach_frequency['time_series']['demo_reach'][0];
		$frequency = $reach_frequency['time_series']['demo_frequency'][0];

		return array("month_num" => $month, "impressions" => $impressions, "reach_percent"=> $reach_percent*100, "reach"=> $reach*$demo_population, "frequency"=> $frequency);
	}

	public function get_RON_demo_coverage($rf_parameters,$internet_average, $demo_pop)
	{
		$rf_inputs = explode("|",urldecode($rf_parameters));
		$nIMPRESSIONS = 0;
		$nGEO_COV = 1;
		$nGAMMA = 2;
		$nIP_ACC = 3;
		$nDEMO_COV_OVERRIDE = 4;

		if ($rf_inputs[$nDEMO_COV_OVERRIDE]==""){
			$implied_RON_demo_coverage = min(1,$rf_inputs[$nGEO_COV]*$internet_average/$demo_pop);///caged at 100% potential for geo data to not line up with internet average
		} else{
			$implied_RON_demo_coverage = $rf_inputs[$nDEMO_COV_OVERRIDE];
		}
		return $implied_RON_demo_coverage;
	}

	/**
	 * gets the zips from the prop_gen_sessions table and creates a "$region_details" string input
	 * that goes into the proposal generator map builder tool.
	 *
	 */
	public function get_zips_from_prop_gen_session($lap_id)
	{
		$zips = array();
		$query =
		"	SELECT
				`region_data`
			FROM
				`prop_gen_sessions`
			WHERE
				`lap_id` = ?;
		";
		$result = $this->db->query($query, $lap_id);
		if($result->num_rows() > 0)
		{
			$region_data_array = json_decode($result->row()->region_data, true);
			$this->map->convert_old_flexigrid_format($region_data_array);

			$zips = isset($region_data_array['ids']['zcta']) ? $region_data_array['ids']['zcta'] : array();
		}
		return array('zcta' => $zips);
	}

	/**
	 * gets the zips from the prop_gen_sessions table for all LAPs associated with adplans owned
	 * by the proposal and creates a "$region_details" string input that goes into the proposal generator
	 * map builder tool.
	 *
	 */
	public function get_zips_from_all_associated_prop_gen_sessions($prop_id)
	{
		$zips = array();
		$sql =
		"	SELECT
				pgs.region_data AS region_data
			FROM
				prop_gen_adplan_option_join AS pgao
			INNER JOIN
				prop_gen_sessions AS pgs
				ON pgs.lap_id = pgao.lap_id
			WHERE
				pgao.prop_id = ?;
		";
		$bind = array($prop_id);
		$result = $this->db->query($sql, $bind);
		if($result->num_rows() > 0)
		{
			$result_array = $result->result();
			foreach ($result_array as $row)
			{
				$region_data_array = json_decode($row->region_data, true);
				$this->map->convert_old_flexigrid_format($region_data_array);

				$zips = isset($region_data_array['ids']['zcta']) ? array_merge($zips, $region_data_array['ids']['zcta']) : $zips;
			}
		}
		return array('zcta' => $zips);
	}

	public function save_site_pack($name, $site_json, $id="")
	{
		if($id == "")
		{
			$sql = "Insert INTO prop_gen_site_packs (Name, site_array) VALUES(?, ?)";
			$query = $this->db->query($sql, array($name, $site_json));
			return $this->db->insert_id();
		}
		else
		{
			$sql = "Update prop_gen_site_packs SET site_array = ? WHERE ID = ?";
			$query = $this->db->query($sql, array($site_json, $id));
			if($this->db->affected_rows() > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
    }
    public function get_pack_names()
    {
		$sql = "SELECT ID, Name FROM prop_gen_site_packs WHERE 1 ORDER BY Name ASC";
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return false;
    }
    public function get_sites_by_pack($id)
    {
		$sql = "SELECT site_array from prop_gen_site_packs WHERE ID = ?";
		$query = $this->db->query($sql, $id);
		if($query->num_rows() == 1)
		{
			$temp = $query->row_array();
			return $temp['site_array'];
		}
		return false;
    }
    public function get_proposal_channel_sites($prop_id)
    {
		$pgpd_sql = "SELECT * FROM prop_gen_prop_data WHERE prop_id = ?";
		$pgpd_query = $this->db->query($pgpd_sql, $prop_id);
		if($pgpd_query->num_rows() > 0)
		{
			$pgpd_row = $pgpd_query->row_array();
			if($pgpd_row['source_mpq'] == NULL)
			{
				$sql = "SELECT * FROM prop_gen_sessions WHERE lap_id LIKE (SELECT lap_id FROM prop_gen_adplan_option_join WHERE prop_id = ? LIMIT 1)";
				$query = $this->db->query($sql, array($prop_id));
				$row = $query->row_array();
				if(strlen($row['selected_channels']) > 1)
				{
					$channel_array = explode('|', $row['selected_channels']);
					$array_count = count($channel_array);
					$binding_placeholder = "?";
					if($array_count > 1)
					{
						for($i = 1; $i < $array_count; $i++)
						{
							$binding_placeholder .= ", ?";

						}
					}
					$sql = "SELECT * FROM smb_all_sites WHERE Category IN (".$binding_placeholder.")";
					$query = $this->db->query($sql, $channel_array);
					return $query->result_array();
				}
			}
			else
			{
				return array();
			}
		}
		else
		{
			return array();
		}
	}
    public function get_proposal_targeted_sites($prop_id)
    {
		$pgpd_sql = "SELECT * FROM prop_gen_prop_data WHERE prop_id = ?";
		$pgpd_query = $this->db->query($pgpd_sql, $prop_id);
		if($pgpd_query->num_rows() > 0)
		{
			$pgpd_row = $pgpd_query->row_array();
			if($pgpd_row['source_mpq'] != NULL)
			{
				$sql = "SELECT * FROM prop_gen_sessions WHERE source_mpq = ? LIMIT 1";
				$query = $this->db->query($sql, $pgpd_row['source_mpq']);
			}
			else
			{
				$sql = "SELECT * FROM prop_gen_sessions WHERE lap_id LIKE (SELECT lap_id FROM prop_gen_adplan_option_join WHERE prop_id = ? LIMIT 1)";
				$query = $this->db->query($sql, array($prop_id));
			}
			if($query->num_rows() > 0)
			{
				$row = $query->row_array();
				$site_array = json_decode($row['site_array'], true);
				$site_holder = array();
				$array_count = count($site_array);
				$binding_placeholder = "?";
				$site_holder[0] = $site_array[0]['Domain'];
				for($i = 1; $i < $array_count; $i++)
				{
					$site_holder[$i] = $site_array[$i]['Domain'];
					$binding_placeholder .= ", ?";
				}
				$sql = "SELECT `Site`, `Reach`, `Gender_Male`, `Gender_Female`, `Age_Under18`, `Age_18_24`, `Age_25_34`, `Age_35_44`, `Age_45_54`, `Age_55_64`, `Age_65`, `Race_Cauc`, `Race_Afr_Am`, `Race_Asian`, `Race_Hisp`, `Race_Other`, `Kids_No`, `Kids_Yes`, `Income_0_50`, `Income_50_100`, `Income_100_150`, `Income_150`, `College_No`, `College_Under`, `College_Grad` FROM smb_all_sites WHERE Site IN(".$binding_placeholder.")";
				$query = $this->db->query($sql, $site_holder);
				return $query->result_array();
			}
			else
			{
				return array();
			}
		}
		else //no prop_gen data
		{
			return array();
		}
    }
    public function save_proposal_sites($json, $prop_id)
    {
		$sql = "UPDATE prop_gen_prop_data SET site_list = ? WHERE prop_id = ?";
		$query = $this->db->query($sql, array($json, $prop_id));
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
    }
    public function get_existing_proposal_sites($prop_id)
    {
		$sql = "SELECT site_list FROM prop_gen_prop_data WHERE prop_id =?";
		$query = $this->db->query($sql, array($prop_id));
		if($query->num_rows() > 0)
		{
			$temp = $query->row_array();
			return $temp['site_list'];
		}
		else
		{
			return false;
		}
    }
    public function get_mpq_id_for_proposal($prop_id)
    {
    	$sql = "SELECT source_mpq FROM prop_gen_prop_data WHERE prop_id =?";
    	$query = $this->db->query($sql, array($prop_id));
    	if($query->num_rows() > 0)
    	{
    		$temp = $query->row_array();
    		return $temp['source_mpq'];
    	}
    	else
    	{
    		return false;
    	}
    }
    public function get_prop_lap_data($lap_id)
    {
		$sql = "SELECT * FROM prop_gen_sessions WHERE lap_id = ?";
		$query = $this->db->query($sql, $lap_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		else
		{
			return false;
		}
    }

    /* BEGIN new proposal methods */

    public function load_proposal_render_data($prop_id)
    {
    	$this->load->library('map');
    	$response = array(
    		'is_success' => true,
    		'results'	 => false,
    		'errors'	 => array()
		);

    	/*
    	 * Get Proposal
    	 */
    	$prop_sql =
		"	SELECT
				pgpd.show_pricing,
				pgpd.site_list,
				pgpd.source_mpq,
				pgpd.rep_id,
				usr.firstname,
				usr.lastname,
				usr.address_1,
				usr.address_2,
				usr.city,
				usr.state,
				usr.zip,
				usr.phone_number,
				usr.fax_number,
				wlpd.partner_name,
				wlpd.home_url,
				wlpd.pretty_partner_name,
				wlpd.proposal_logo_filepath,
				pgsd.snapshot_title AS geo_overview_title,
				pgsd.snapshot_data AS geo_overview_link
			FROM
				prop_gen_prop_data AS pgpd
				LEFT JOIN users AS usr
					ON usr.id = pgpd.rep_id
				LEFT JOIN wl_partner_details AS wlpd
					ON usr.partner_id = wlpd.id
				LEFT JOIN prop_gen_snapshot_data AS pgsd
					ON pgsd.prop_id = pgpd.prop_id
					AND pgsd.lap_id IS NULL
			WHERE pgpd.prop_id = ?
		";
		$prop_query = $this->db->query($prop_sql, array($prop_id));

		if ($prop_query->num_rows() < 1)
		{
			$response['is_success'] = false;
			$response['errors'][] = 'No proposal found with that ID [Err# 81001]';
			return $response;
		}

		$proposal = $prop_query->row_array();
		$prop_query->free_result();

		$proposal['site_list'] = json_decode($proposal['site_list']);
		$proposal['valid_date'] = strtotime(date("Y-m-d", strtotime(date('Y-m-d'))) . "+ 2 week");
		$proposal['has_retargeting'] = false;
		$proposal['unique_laps'] = array();
		$proposal['demographic_data'] = array();
		$proposal['zips'] = array();
		$proposal['single_adplan'] = false;

		$CI =& get_instance();
		$CI->load->model('rf_model', 'rf', true);
		$CI->load->model('mpq_v2_model', 'mpq', true);


		/*
		 * Get Options
		 */
		$option_sql =
		"	SELECT *
			FROM
				prop_gen_option_prop_join
			WHERE
				prop_id = ?
			ORDER BY
				option_id ASC
		";
		$option_query = $this->db->query($option_sql, array($prop_id));

		if ($option_query->num_rows() < 1)
		{
			$response['is_success'] = false;
			$response['errors'][] = 'No options found for proposal '. $prop_id .' [Err# 81002]';
			return $response;
		}

		$proposal['options'] = $option_query->result_array();
		$option_query->free_result();


		/*
		 * Get Adplans + LAPs
		 * NOTE: Still need to limit screenshots to 1 per proposal/LAP combo
		 */
		$adplan_sql =
		"	SELECT
				pgao.*,
				pgs.plan_name AS plan_name,
				pgs.advertiser AS advertiser,
				pgs.region_data AS region_data,
				pgs.population AS population,
				pgs.demo_population AS demo_population,
				pgs.internet_average AS internet_average,
				pgs.demographic_data AS demographic_data,
				pgs.selected_iab_categories AS selected_iab_categories,
				pgsd.snapshot_title AS geo_snapshot_title,
				pgsd.snapshot_data AS geo_snapshot_link
			FROM prop_gen_sessions AS pgs
				RIGHT JOIN prop_gen_adplan_option_join AS pgao
					ON pgao.lap_id = pgs.lap_id
				LEFT JOIN prop_gen_snapshot_data as pgsd
					ON pgao.lap_id = pgsd.lap_id
					AND pgsd.prop_id = ?
			WHERE
				pgao.prop_id = ?
			GROUP BY pgao.lap_id, pgao.prop_id, pgao.option_id
			ORDER BY
				pgao.lap_id ASC
		";
		$adplan_query = $this->db->query($adplan_sql, array($prop_id, $prop_id));
		$adplans = $adplan_query->result_array();
		$adplan_query->free_result();

		// Assign each adplan to the correct option
		foreach($adplans as $adplan)
		{
			$option = &$proposal['options'][$adplan['option_id']];

			if (!isset($option['adplans']))
			{
				$option['adplans'] = array();
			}

			// Set the option-level period type
			if (!isset($option['period_type']))
			{
				$option['period_type'] = $adplan['period_type'];
			}
			elseif ($adplan['period_type'] != $option['period_type'])
			{
				if ($option['period_type'] == 'days')
				{
					$option['period_type'] = $adplan['period_type'];
				}
				else if ($option['period_type'] == 'weeks' && $adplan['period_type'] == 'months')
				{
					$option['period_type'] = 'months';
				}
			}

			$adplan['region_data'] = json_decode($adplan['region_data']);
			$this->map->convert_old_flexigrid_format_object($adplan['region_data']);

			if (!in_array($adplan['lap_id'], $proposal['unique_laps']))
			{
				$proposal['unique_laps'][] = $adplan['lap_id'];
				$proposal['demographic_data'][$adplan['lap_id']] = $adplan['region_data'];

				$proposal['zips'] = $adplan['region_data']->ids->zcta;

			}

			$adplan['region_data'] = json_encode($adplan['region_data']);
			$option['adplans'][] = $adplan;
		}

		if (count($proposal['unique_laps']) == 1)
		{
			$proposal['single_adplan'] = true;

			$adplan = $proposal['options'][0]['adplans'][0];
			$proposal['geo_overview_title'] = $adplan['geo_snapshot_title'];
			$proposal['geo_overview_link'] = $adplan['geo_snapshot_link'];

		}

		foreach($proposal['options'] as $i => &$option)
		{
			$option['impressions'] = 0;
			$option['total_cost'] = 0;

			// Add to population totals
			$option['total_population'] = 0;
			$option['demo_population'] = 0;

			$option['has_retargeting'] = false;
			$option['retargeting_price'] = 0;

			$option['display_packages'] = array();

			foreach($option['adplans'] as $i => &$adplan)
			{
				if ($adplan['retargeting'])
				{
					$option['has_retargeting'] = true;
				}
				$option['retargeting_price'] += intval($adplan['retargeting_price']);

				$rf_data = array();
				$term_array = $this->calculate_period_budget($adplan['term'], $adplan['impressions'], $adplan['budget'], $option['period_type'], $adplan['period_type']);
				$adplan['term_array'] = $term_array;

				$option['impressions'] += $term_array['impressions'][0];
				$option['total_cost'] += $term_array['budget'];

				// Add to population totals
				$option['total_population'] += intval($adplan['population'], 10);
				$option['demo_population'] += intval($adplan['demo_population'], 10);

				// Limits demo population to 100% of population. Necessary because of a rounding bug
				$adplan['demo_population'] = $adplan['demo_population'] > $adplan['population'] ? $adplan['population'] : $adplan['demo_population'];

				$rf_data = $this->rf->calculate_reach_and_frequency(
					$term_array['impressions'],
					$adplan['population'],
					$adplan['demo_population'],
					$adplan['ip_accuracy'],
					$adplan['demo_coverage'],
					$adplan['gamma'],
					$adplan['retargeting']
				);

				foreach($term_array['impressions'] as $month => $impressions)
				{
					if (!isset($option['display_packages'][$month]))
					{
						$option['display_packages'][$month] = array(
							'implied_audience_size'	=> 0,
							'reach_raw'				=> 0,
							'landed_impressions'	=> 0,
							'impressions'			=> 0
						);
					}

					$implied_audience_size = $rf_data[$month]['reach_percent'] > 0 ? $rf_data[$month]['reach'] / $rf_data[$month]['reach_percent'] : 0;
					$option['display_packages'][$month]['implied_audience_size'] += $implied_audience_size;
					$option['display_packages'][$month]['reach_raw'] += $rf_data[$month]["reach"];
					$option['display_packages'][$month]['landed_impressions'] += ($rf_data[$month]['reach'] * $rf_data[$month]['frequency']);
					$option['display_packages'][$month]['impressions'] += $impressions;
				}

				$adplan['rf_data'] = $rf_data;
			}

			foreach($option['display_packages'] as $i => &$rf_data)
			{
				$rf_data['reach_percent'] = $rf_data['implied_audience_size'] > 0 ? number_format($rf_data['reach_raw'] / $rf_data['implied_audience_size'] * 100, 1) : 0;
				$rf_data['reach'] = round($rf_data['reach_raw'], 0);
				$rf_data['frequency'] = $rf_data['reach_raw'] > 0 ? number_format($rf_data['landed_impressions'] / $rf_data['reach_raw'], 1) : 0;
			}
			unset($rf_data);

			$option['impressions_cost'] = $option['total_cost'];
			$option['final_cost'] = $option['total_cost'] - $option['total_cost'] * ($option['monthly_percent_discount'] / 100);

			if ($option['has_retargeting'] == true)
			{
				$proposal['has_retargeting'] = true;
			}
		}

		// Get list of counties
		$proposal['zips'] = array_unique($proposal['zips']);
		$proposal['counties'] = $this->map->get_list_of_distinct_counties_from_array_of_zips($proposal['zips'], true);
		$proposal['num_zips'] = count($proposal['zips']);
		// Remove zips array from memory
		unset($proposal['zips']);

		// Get demographic data. We need to format the regions in a way that can
		// be handled by mpqv2_model->get_demographics
		foreach ( $proposal['demographic_data'] as $lap_id => &$region )
		{
			$this->map->convert_old_flexigrid_format_object($region);
			$regions_array = $region->ids->zcta;
			$region_data = $this->map->get_demographics_from_region_array(array('zcta' => $regions_array));

			$region_data['lap_id'] = $lap_id;
			$region_data['region_population_formatted'] = number_format($region_data['region_population']);
			$region_data['income'] = number_format($region_data['household_income']);
			$region_data['MedianAge'] = number_format($region_data['median_age']);
			$region_data['HouseholdPersons'] = number_format($region_data['persons_household'], 1);
			$region_data['HouseValue'] = number_format($region_data['average_home_value'], 0);
			$region_data['BusinessCount'] = number_format($region_data['num_establishments']);

			$region = $region_data;
		}

		// Get IAB categories
		$proposal['iab_categories'] = $this->get_iab_categories_array($proposal['unique_laps']);

		// Set advertiser name to that of first adplan
		$proposal['advertiser_name'] = $proposal['options'][0]['adplans'][0]['advertiser'];

		// Set up the array with selected demographics from the first adplan
		$proposal['demographics'] = $this->parse_demo_string($proposal['options'][0]['adplans'][0]['demographic_data']);

		$response['results'] = $proposal;
		return $response;
    }

    public function get_iab_categories_array($iab_categories = array())
    {
    	// Get IAB categories
		$iab_query_string = "";
		foreach($iab_categories as $iab_lap)
		{
			if ($iab_query_string == "")
			{
				$iab_query_string = "?";
			}
			else
			{
				$iab_query_string .= ",?";
			}
		}
		$iab_sql =
		"	SELECT DISTINCT
				iab.id,
				iab.tag_copy
			FROM iab_categories AS iab
			JOIN prop_gen_sessions_iab_categories_join AS pgiab
				ON iab.id = pgiab.iab_category_id
			WHERE
				pgiab.prop_gen_lap_id IN (". $iab_query_string .")
		";
		$iab_query = $this->db->query($iab_sql, $iab_categories);

		// Convert categories to array,
		// merge duplicate entries
		if ($iab_query->num_rows() > 0)
		{
			$iab = $iab_query->result_array();
			$organized_iab_categories = array();

			foreach($iab as $iab_cat)
			{
				$split_iab_categories = array_reverse(explode(">", $iab_cat['tag_copy']));

				$split_iab_categories = array_reduce($split_iab_categories, function($carry, $item)
				{
					return array(trim($item) => $carry);
				}, array());

				$organized_iab_categories = array_merge_recursive($organized_iab_categories, $split_iab_categories);
			}

			$this->ksortTree($organized_iab_categories);
			return $organized_iab_categories;
		}
		else
		{
			return array();
		}

    }

    // Recursively sorts by keys
    public function ksortTree( &$array )
	{
		if (!is_array($array)) {
			return false;
		}

    	ksort($array);
    	foreach ($array as $k=>$v) {
    	$this->ksortTree($array[$k]);
    	}
    	return true;
	}

	// Calculates budgets between different period types
	public function calculate_period_budget($term, $impressions_budget, $dollar_budget, $option_period_type, $adplan_period_type)
	{
		$average_weights = array(
			'months' => array(
				'months' 	=> 1,
				'weeks'		=> 4 + 1 / 3,
				'days'		=> 30.4375
			),

			'weeks' => array(
				'weeks'		=> 1,
				'days'		=> 7
			),

			'days' => array(
				'days'		=> 1
			)
		);

		$period_average = $average_weights[$option_period_type][$adplan_period_type];


		// Separate the number of periods in the term
		// from the remainder in the last period
		$term_period = $term / $period_average;
		$term_remainder = $term_period >= 1 ? $term_period - floor($term_period) : $term_period;
		$term_period = floor($term_period);

		if ($term_period > 0)
		{
			$budget = $dollar_budget * $period_average;
		}
		else
		{
			$budget = $dollar_budget * $period_average * $term_remainder;
		}

		$term_array = array(
			'budget' => $budget,
			'impressions' => array()
		);
		$i = $term_period;

		// For each period, the budget will be the
		// budget per selected period type times
		// the period average
		$total_impressions = 0;
		while($i > 0)
		{
			$total_impressions += $impressions_budget * $period_average;
			$term_array['impressions'][] = $total_impressions;
			$i--;
		}

		// If there is a remainder for the last period,
		// it will be added as a partial period calculated
		// by multiplying by the remainder
		if ($term_remainder > 0)
		{
			$term_array['impressions'][] = $impressions_budget * ($period_average * $term_remainder);
		}

		return $term_array;
	}


	public function parse_demo_string($demo_string)
	{
		$demo_bools = explode("_", $demo_string);

		$demos = array(
			'Gender' => array(
				'Male' => false,
				'Female' => false
			),
			'Age' => array(
				'Under 18' => false,
				'18-24' => false,
				'25-34' => false,
				'35-44' => false,
				'45-54' => false,
				'55-64' => false,
				'65+' => false
			),
			'Income' => array(
				'0-50k' => false,
				'50-100k' => false,
				'100-150k' => false,
				'150k +' => false
			),
			'Education' => array(
				'No College' => false,
				'Undergrad' => false,
				'Grad School' => false
			),
			'Parenting' => array(
				'No Kids' => false,
				'Has Kids' => false
			)
		);

		$i = 0;

		foreach($demos as $group_key => &$demo_group)
		{
			$demo_all_switch = true;
			$activated_demos = array();

			foreach($demo_group as $demo_key => &$demo)
			{
				$demo = (bool) $demo_bools[$i];
				$demo_prev = isset($demo_prev) ? $demo_prev : $demo;

				if ($demo != $demo_prev)
				{
					$demo_all_switch = false;
				}

				if ($demo)
				{
					$activated_demos[] = $demo_key;
				}

				$demo_prev = $demo;
				$i++;
			}

			unset($demo_prev);

			if ($demo_all_switch)
			{
				$demo_group = 'All';
			}
			else
			{
				$demo_group = $activated_demos;
			}
		}

		return $demos;
	}

	/* END new proposal methods */

    public function get_sitelist($prop_id)
    {
		$sql = 'SELECT site_list, prop_name FROM prop_gen_prop_data WHERE prop_id = ?';
		$query = $this->db->query($sql, $prop_id);
		return $query->row_array();
    }
	public function get_regions_by_insertion_order($mpq_id)
	{
		$sql = 'SELECT region_data, advertiser FROM prop_gen_sessions WHERE source_mpq = ?';
		$query = $this->db->query($sql, $mpq_id);
		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		else
		{
			return false;
		}
	}
    public function get_regions_by_prop_option($prop_id, $option_id)
    {
		$sql = 'SELECT a.region_data, a.advertiser FROM prop_gen_sessions a JOIN prop_gen_adplan_option_join b ON a.lap_id = b.lap_id WHERE b.prop_id = ? AND b.option_id = ?';
		$query = $this->db->query($sql, array($prop_id, $option_id));
		return $query->row_array();
    }

	//	calculate reach/frequency data from parameters
	//
	//	return: array of reach/frequency data
	public function get_reach_frequency_data(
		$lap_id,
		$term_duration,
		$term_type,
		$impressions,
		$gamma,
		$ip_accuracy,
		$demo_coverage,
		$retargeting
		)
	{
		$ret = $this->proposal_gen_model->get_prop_lap_data($lap_id);

		if(
			$ret['population'] > 0 &&
			$ret['demo_population'] > 0 &&
			$impressions > 0 &&
			$ip_accuracy > 0 &&
			$demo_coverage > 0
		)
		{
			$impressions_array = array();
			$i = 1;
			while($i <= $term_duration)
			{
				$impressions_array[] = $impressions * $i;
				$i++;
			}

			$CI =& get_instance();
			$CI->load->model('rf_model', 'rf', true);

			$rf_array = $CI->rf->calculate_reach_and_frequency(
				$impressions_array,
				$ret['population'],
				$ret['demo_population'],
				$ip_accuracy,
				$demo_coverage,
				$gamma,
				$retargeting
			);

			foreach($rf_array as &$term)
			{
				$term['impressions'] = number_format($term['impressions']);
				$term['reach'] = number_format($term['reach'], 0);
				$term['reach_percent'] = number_format($term['reach_percent'] * 100, 1);
				$term['frequency'] = number_format($term['frequency'], 1);
			}
		}
		else
		{
			$rf_array = array(
				'impressions' => $impressions,
				'reach' => 0,
				'reach_percent' => 0,
				'frequency' => 0
			);
		}

		return $rf_array;
	}

	public function get_proposal_owner_id_from_proposal_id($prop_id)
	{
		$sql =
		"	SELECT DISTINCT
				pgs.owner_id
			FROM
				prop_gen_sessions AS pgs
			INNER JOIN
				prop_gen_prop_data AS pgpd
				ON pgpd.source_mpq = pgs.source_mpq
			WHERE pgpd.prop_id = ?
		";
		$result = $this->db->query($sql, $prop_id);
		if($result->num_rows > 0)
		{
			$owner_id = $result->row()->owner_id;
			if(is_numeric($owner_id))
			{
				return intval($owner_id);
			}
		}
		return false;
	}

	public function get_name_and_regions_from_mpq_session($mpq_id, $selected_location_id = null)
	{
		$region_details = array();
		$sql =
		"	SELECT
				region_data
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?;
		";
		$query = $this->db->query($sql, $mpq_id);
		if($query->num_rows > 0)
		{
			$regions_array = json_decode($query->row()->region_data, true);
			$this->map->convert_old_flexigrid_format($regions_array);

			if(isset($regions_array['page']))
			{
				// old data structure
				$region_details[0]['zcta'] = isset($regions_array['ids']['zcta']) ? $regions_array['ids']['zcta'] : array();
			}
			else
			{
				// new data structure
				if($selected_location_id !== null)
				{
					if(array_key_exists((int)$selected_location_id, $regions_array))
					{
						// Return only zips of the given location
						$region_details[$selected_location_id]['user_supplied_name'] = $regions_array[$selected_location_id]['user_supplied_name'];
						$region_details[$selected_location_id]['zcta'] = isset($regions_array[$selected_location_id]['ids']['zcta']) ?
							$regions_array[$selected_location_id]['ids']['zcta'] :
							array();
					}
					else
					{
						return false;
					}
				}
				else
				{
					// Return all zips from all locations
					foreach($regions_array as $location_id => $region_array)
					{
						$region_details[$location_id]['user_supplied_name'] = $regions_array[$location_id]['user_supplied_name'];
						$region_details[$location_id]['zcta'] = isset($region_array['ids']['zcta']) ?
							$region_array['ids']['zcta'] :
							array();
					}
				}
			}
			return $region_details;
		}
		else
		{
			return false;
		}
	}

	public function get_sitelist_by_media_targeting_tag_ids($tag_data)
	{
		if(count($tag_data) > 0)
		{
			$result_array = array();
			$tag_binding_sql = "";
			$iab_binding_sql = "?";
			$geo_binding_sql = "?";
			$demo_binding_sql = "?";
			$tag_ids = array();
			$tag_concat_array = array();
			$iab_ids = array(0); //initialize value arrays with 0 so the 'IN' part of query doesnt fail on no ids
			$geo_ids = array(0);
			$demo_ids = array(0);
			foreach($tag_data as $v)
			{
				$temp_id = $v->id;
				$temp_type = $v->tag_type;
				$tag_concat_array[] = $temp_id."_".$temp_type;
				$tag_ids[] = $temp_id;
				if($temp_type == 1) //iab
				{
					$iab_ids[] = $temp_id;
					$iab_binding_sql .= ", ?";
				}
				else if($temp_type == 2) //geo
				{
					$geo_ids[] = $temp_id;
					$geo_binding_sql .= ", ?";
				}
				else if($temp_type == 3) //demo
				{
					$demo_ids[] = $temp_id;
					$demo_binding_sql .= ", ?";
				}
				else
				{
					//FUTURE TAG TYPES GO HERE
				}

				if($tag_binding_sql == "")
				{
					$tag_binding_sql .= "?";
				}
				else
				{
					$tag_binding_sql .= ", ?";
				}
			}
			$sql_tag_array = array_merge($iab_ids, $geo_ids, $demo_ids, $tag_concat_array, $tag_concat_array);
			$sql =
			"	SELECT * FROM
				(
					SELECT
						iabc.id,
						iabc.tag_friendly_name AS tag_friendly_name,
						CONCAT(iabc.id, '_', 1) AS group_on,
						CONCAT(iabh.descendant_id, '_', 1) AS tag_concat

					FROM
						iab_categories iabc
						JOIN
						iab_heirarchy iabh
							ON iabc.id = iabh.ancestor_id
					WHERE
						iabh.descendant_id IN({$iab_binding_sql})

					UNION

					SELECT
						CONVERT(GEOID10, SIGNED) AS id,
						CONCAT('Local Media / ', REPLACE(REPLACE(NAMELSAD10, ' Metro Area', ''), ' Micro Area', '')) AS tag_friendly_name,
						CONCAT(GEOID10, '_', 2) AS group_on,
						CONCAT(GEOID10, '_', 2) AS tag_concat
					FROM
						geo_cbsa_map
					WHERE
						num_id IN({$geo_binding_sql})

					UNION

					SELECT
						id,
						CONCAT('Audience / ', tag_friendly_name) AS tag_friendly_name,
						CONCAT(id, '_', 3) AS group_on,
						CONCAT(id, '_', 3) AS tag_concat

					FROM
						demographic_categories_legacy
					WHERE
						id IN({$demo_binding_sql})
					ORDER BY FIELD (tag_concat, {$tag_binding_sql}), id ASC
				) AS alias
				GROUP BY group_on
				ORDER BY FIELD(tag_concat, {$tag_binding_sql}), id ASC
			";

			$query = $this->db->query($sql, $sql_tag_array);
			if($query->num_rows() > 0)
			{
				$unique_tag_array = array('0_0');
				$unique_tag_sql = '?';
				$tags_result = $query->result_array();
				foreach($tags_result as $tag)
				{
					$unique_tag_array[] = $tag['group_on'];
					$unique_tag_sql .= ', ?';
				}
				$sql = "SELECT
						mts.*,
						s2t.tag_id AS tag_id,
						CONCAT(s2t.tag_id, '_', s2t.table_source) AS tag_concat
					FROM
						media_targeting_sites mts
						JOIN
						media_targeting_sites_to_tags s2t
							ON mts.id = s2t.site_id
					WHERE
						s2t.prop_worthy = 1
						AND CONCAT(s2t.tag_id, '_', s2t.table_source) IN(".$unique_tag_sql.")
					";
				$query = $this->db->query($sql, $unique_tag_array);
				$result_array = array();
				foreach($tags_result as $tag)
				{
					$temp_tag_result = array();
					$temp_tag_result['tag_friendly_name'] = $tag['tag_friendly_name'];
					$temp_tag_result['sites'] = array();
					if($query->num_rows() > 0)
					{
						$sites_result = $query->result_array();
						$temp_sites_array = array();
						foreach($sites_result as $site)
						{
							if($tag['group_on'] == $site['tag_concat'])
							{
								$temp_sites_array[] = $site;
							}
						}
						$temp_tag_result['sites'] = $temp_sites_array;
					}
					$result_array[] = $temp_tag_result;
				}
				return $result_array;
			}
		}
		return false;
	}

	public function get_existing_media_targeting_tags_by_prop_id($prop_id)
	{
		$sql =
		"	SELECT DISTINCT(lap_id)
			FROM prop_gen_adplan_option_join
			WHERE prop_id = ?
			ORDER BY lap_id
		";
		$query = $this->db->query($sql, $prop_id);
		if($query->num_rows() > 0)
		{
			$adplan_results = $query->result_array();
			$media_targeting_tags = array();

			foreach($adplan_results as $adplan)
			{
				$lap_id = $adplan['lap_id'];
				$sql = "SELECT region_data, demographic_data FROM prop_gen_sessions WHERE lap_id = ?";
				$query = $this->db->query($sql, $lap_id);
				if($query->num_rows() > 0)
				{
					$demographic_raw = $query->row()->demographic_data;
					$demographic_array = explode("_", $demographic_raw);
					$selected_demographics = $this->get_selected_demographics_for_tags_query($demographic_array); //gets array of demographic ids we care about
					$demo_value_string = "";
					foreach($selected_demographics as $v)
					{
						if($demo_value_string != "")
						{
							$demo_value_string .= ", ";
						}
						$demo_value_string .= "?";
					}

					$region_data = json_decode($query->row()->region_data);
					$this->map->convert_old_flexigrid_format_object($region_data);
					$zips_binding_array = $region_data->ids->zcta;
					$zips_value_string = implode(',', array_fill(0, count($zips_binding_array), '?'));

					$sql_binding_array = array_merge($zips_binding_array, $selected_demographics);
					array_unshift($sql_binding_array, $lap_id); //prepend lap_id

					$sql =
					"	SELECT
							iabc.id AS id,
							CONCAT('IAB: ', iabc.tag_copy) AS tag_copy,
							iabc.tag_friendly_name AS tag_friendly_name,
							1 AS tag_type
						FROM
							iab_categories iabc
							INNER JOIN
							prop_gen_sessions_iab_categories_join pgsj
								ON iabc.id = pgsj.iab_category_id
						WHERE
								pgsj.prop_gen_lap_id = ?

						UNION

						SELECT
							gcm.GEOID10 AS id,
							CONCAT('GEO: ', gcm.NAMELSAD10) AS tag_copy,
							REPLACE(REPLACE(gcm.NAMELSAD10, ' Metro Area', ''), ' Micro Area', '') AS tag_friendly_name,
							2 AS tag_type
						FROM
							geo_cbsa_map gcm
							INNER JOIN
							geo_zcta_to_cbsa AS ztc
								ON gcm.num_id = ztc.cbsa_int
						WHERE
							ztc.zcta_int IN({$zips_value_string})

						UNION

						SELECT
							id,
							CONCAT('DEMO: ', tag_copy) AS tag_copy,
							tag_friendly_name,
							3 AS tag_type
						FROM
							demographic_categories_legacy
						WHERE
							id IN({$demo_value_string})
					";

					$query = $this->db->query($sql, $sql_binding_array);
					if($query->num_rows() > 0)
					{
						$new_tags = $query->result_array();
						$media_targeting_tags = array_merge($media_targeting_tags, $new_tags);
					}
				}
			}
			return $media_targeting_tags;
		}
		return false;
	}
	private function get_selected_demographics_for_tags_query($demos)
	{
		$demo_id_array = array(0); //initialize value array with 0 so the 'IN' part of query doesnt fail on no ids

		if($demos[0] == 1 and $demos[1] == 0) //MALE
		{
			$demo_id_array[] = 1;
		}
		else if($demos[0] == 0 and $demos[1] == 1) //FEMALE
		{
			$demo_id_array[] = 2;
		}
		if(!($demos[2] == 1 and $demos[3] == 1 and $demos[4] == 1 and $demos[5] == 1 and $demos[6] == 1 and $demos[7] == 1 and $demos[8] == 1))
		{
			if($demos[2] == 1) //under 18
			{
				$demo_id_array[] = 3;
			}
			if($demos[3] == 1) //18 - 24
			{
				$demo_id_array[] = 4;
			}
			if($demos[4] == 1) //25 - 34
			{
				$demo_id_array[] = 5;
			}
			if($demos[5] == 1) //35 - 44
			{
				$demo_id_array[] = 6;
			}
			if($demos[6] == 1) //45 - 54
			{
				$demo_id_array[] = 7;
			}
			if($demos[7] == 1) //55 - 64
			{
				$demo_id_array[] = 8;
			}
			if($demos[8] == 1) //65+
			{
				$demo_id_array[] = 9;
			}
		}
		if(!($demos[9] == 1 and $demos[10] == 1 and $demos[11] == 1 and $demos[12] == 1))
		{
			if($demos[9] == 1) //0 - 50k
			{
				$demo_id_array[] = 10;
			}
			if($demos[10] == 1) //50 - 100k
			{
				$demo_id_array[] = 11;
			}
			if($demos[11] == 1) //100 - 150k
			{
				$demo_id_array[] = 12;
			}
			if($demos[12] == 1) //150k+
			{
				$demo_id_array[] = 13;
			}
		}
		if(!($demos[13] == 1 and $demos[14] == 1 and $demos[15] == 1))
		{
			if($demos[13] == 1) //No College
			{
				$demo_id_array[] = 14;
			}
			if($demos[14] == 1) //College
			{
				$demo_id_array[] = 15;
			}
			if($demos[15] == 1) //Grad School
			{
				$demo_id_array[] = 16;
			}
		}
		if($demos[16] == 1 and $demos[17] == 0) //No Kids
		{
			$demo_id_array[] = 17;
		}
		else if($demos[16] == 0 and $demos[17] == 1) //Kids
		{
			$demo_id_array[] = 18;
		}
		if(!($demos[18] == 1 and $demos[19] == 1 and $demos[20] == 1 and $demos[21] == 1 and $demos[22] == 1))
		{
			if($demos[18] == 1) //Caucasian
			{
				$demo_id_array[] = 19;
			}
			if($demos[19] == 1) //Afr American
			{
				$demo_id_array[] = 20;
			}
			if($demos[20] == 1) //Asian
			{
				$demo_id_array[] = 21;
			}
			if($demos[21] == 1) //Hispanic
			{
				$demo_id_array[] = 22;
			}
			if($demos[22] == 1) //Other Race
			{
				$demo_id_array[] = 23;
			}
		}
		return $demo_id_array;
	}

	public function get_industry_from_proposal($prop_id)
	{
		$industry_array = array();

		$sql = "SELECT
					freq_industry_tags_id AS id,
					name AS text
				FROM
					prop_gen_prop_data a,
					mpq_sessions_and_submissions b,
					freq_industry_tags c
				WHERE
					a.prop_id=? AND
					a.source_mpq=b.id AND
					b.industry_id=c.freq_industry_tags_id";

		$query = $this->db->query($sql, $prop_id);
		if($query->num_rows > 0)
		{
			foreach($query->result_array() as $row)
			{
				$industry_array["id"] = $row["id"];
				$industry_array["text"] = $row["text"];
			}
			return $industry_array;
		}
		else
		{
			return false;
		}
	}

	public function save_original_summary_html($mpq_id, $html_summary)
	{
		$update_sql =
		"	UPDATE
				mpq_sessions_and_submissions
			SET
				original_submission_summary_html = ?
			WHERE
				id = ?;
		";
		$bindings = array($html_summary, $mpq_id);
		$update_result = $this->db->query($update_sql, $bindings);
		$affected_rows = $this->db->affected_rows();

		return ($update_result && ($affected_rows > 0));
	}

	public function get_preload_rfp_data_by_proposal_id($proposal_id)
	{
		$query = "
			SELECT
				mss.id,
				mss.region_data,
				mss.rooftops_data
			FROM
				cp_submitted_products as csp
				JOIN mpq_sessions_and_submissions mss
					ON csp.mpq_id = mss.id
		  	WHERE
			   	csp.proposal_id = ?";
		$result = $this->db->query($query, $proposal_id);
		if($result->num_rows() > 0)
		{
			return $result->row_array();
		}
		return array();
	}
}
?>
