<?php

class All_ios_model extends CI_Model
{
	public function __construct()
	{
	    $this->load->database();
		$this->load->model('al_model');
		$this->load->model('mpq_v2_model');
		$this->load->model('siterankpoc_model');
		$this->load->model('tradedesk_model');
		$this->load->model('tag_model');

	}

	public function get_date_by_filter($date)
	{
		$dates = explode(':', $date);
		$not_date_str = $date_str = '';

		foreach ($dates as $value)
		{
			if (substr($value, 0, 3) == 'not')
			{
				$value = str_replace('not', '', $value);
				if ($value == 'today')
				{
					$value_without_not = date("Y-m-d");
				}
				else
				{
					$y = substr($value, 0, 4);
					$m = substr($value, 4, 2);
					$d = substr($value, 6, 2);
					$value_without_not = $y . '-' . $m . '-' . $d;
				}
				$not_date_str = $not_date_str . ", '$value_without_not' ";
			}
			else
			{
				if ($value == 'today')
				{
					$value_without_not = date("Y-m-d");
				}
				else
				{
					$y = substr($value, 0, 4);
					$m = substr($value, 4, 2);
					$d = substr($value, 6, 2);
					$value_without_not = $y . '-' . $m . '-' . $d;
				}
				$date_str = $date_str . ", '$value_without_not' ";
			}
		}
		$not_date_str = ltrim($not_date_str, ',');
		$date_str = ltrim($date_str, ',');

		return array($not_date_str, $date_str);
	}

	public function get_submitted_ios($user_id, $role, $is_super, $io_status = '', $partner_name = '', $start_date = '', $created_date = '')
	{
		$bindings = array();
		$io_query = $partner_query = $createddate_query = '';
		$partner_join = $partner_join_descendent  = $select_sql = '';
		$having = '';
		$status_map = array(
			"submitted" => "io-submitted",
			"saved" => "io-saved",
			"review" => "io-in-review",
			"processed" => "processed"
		);

		//For code of IO status type
		if ($io_status !== '')
		{
			$io_status = explode(":", $io_status);
			$array_temp = $value_after_replace = '';
			$array_status = array();
			$init_array_not_status = array();
			$init_array_status = array();
			foreach ($io_status as $value_io)
			{
				$value_after_replace = str_replace('not', '', $value_io);
				if (array_key_exists($value_after_replace, $status_map))
				{
					if (substr($value_io, 0, 3) == 'not')
					{
						$init_array_not_status[] = $value_after_replace;	
					}
					else
					{
						$init_array_status[] = $value_io;
					}
				}
			}
			if (in_array('processed', $init_array_status) || (!empty($init_array_not_status) && !in_array('processed', $init_array_not_status)))
			{
				$array_status[] = '"io-submitted"';			
				$having = ' HAVING ((associated_campaigns > 0 AND mpq_type = "io-submitted")';
			}

			if (in_array('submitted', $init_array_status) || (!empty($init_array_not_status) && !in_array('submitted', $init_array_not_status)))
			{	
				$array_status[] = '"io-submitted"';
				if (!empty($having))
				{
					$having .= ' OR ';
				}
				else
				{
					$having .= ' HAVING (';
				}
				$having .= '(associated_campaigns <= 0 AND mpq_type = "io-submitted")';
			}

			if (in_array('saved', $init_array_status) || (!empty($init_array_not_status) && !in_array('saved', $init_array_not_status)))
			{		
				$array_status[] = '"io-saved"';
				if (!empty($having))
				{
					$having .= ' OR mpq_type IN ("io-saved")';
				}
			}

			if (in_array('review', $init_array_status) || (!empty($init_array_not_status) && !in_array('review', $init_array_not_status)))
			{
				$array_status[] = '"io-in-review"';
				if (!empty($having))
				{
					$having .= ' OR mpq_type IN ("io-in-review")';
				}
			}
			if (!empty($having))
			{
				$having .= ' ) ';
			}
			
			$array_temp .= implode($array_status, ',');
			if (!empty($array_temp))
			{
				$io_query = " AND mss.mpq_type IN (".$array_temp.")";
			}
		}
		else
		{
			$io_query = $io_query . " AND mss.mpq_type IN ('io-submitted','io-saved','io-in-review')"; //when no status filter, apply previous where condition
		}
		// End code of IO status
		// Start code for partner filter

		if ($partner_name !== '')
		{
			$partner_name = explode(":", $partner_name);

			$not_partner_str = $partner_str = '';

			foreach ($partner_name as $value)
			{
				if (substr($value, 0, 3) == 'not')
				{
					$value_with_out_not = str_replace('not', '', $value);
					$not_partner_str = $not_partner_str . ", '$value_with_out_not' ";
				}
				else
				{
					$partner_str = $partner_str . ", '$value' ";
				}
			}

			$not_partner_str = ltrim($not_partner_str, ',');
			$partner_str = ltrim($partner_str, ',');

			$partner_join =	"
						wl_partner_details
					AS
						partner2
					LEFT JOIN
						wl_partner_hierarchy
					AS
						wph
					ON
						partner2.id = wph.ancestor_id
					JOIN ";

			$partner_join_descendent = ' AND wph.descendant_id = partner.id';

			$partner_query = $partner_query . " AND (";

			if ($not_partner_str != '' && $partner_str != '')
			{
				$partner_query = $partner_query . "
									partner2.partner_name
								NOT IN
									($not_partner_str)
								AND
									partner2.partner_name
								IN
									($partner_str) ";
			}
			elseif ($not_partner_str != '')
			{ // NOT condition
				$partner_query = $partner_query . " partner.partner_name NOT IN ($not_partner_str) ";
				$partner_join_descendent = $partner_join = '';
			}
			elseif ($partner_str != '')
			{ // Without NOT Condition
				$partner_query = $partner_query . " partner2.partner_name IN ($partner_str)";
			}
			$partner_query = $partner_query . " )";
		}
		// End partner filter code.
		//Start code for created date filter
		if ($created_date !== '')
		{

			list($not_date_str, $date_str) = $this->get_date_by_filter($created_date);

			if ($not_date_str != '')
			{
				// NOT condition
				$createddate_query = $createddate_query . "
									AND
										DATE_FORMAT((mss.creation_time), '%Y-%m-%d')
									NOT IN
										($not_date_str) ";
			}
			if ($date_str != '')
			{
				//Without NOT Condition
				$createddate_query = $createddate_query . "
									AND
										DATE_FORMAT((mss.creation_time), '%Y-%m-%d')
									IN
										($date_str)";
			}
		}
		//End code createddate filter
		//Start code for startdate filter
		if ($start_date !== '')
		{
			list($not_date_str, $date_str) = $this->get_date_by_filter($start_date);

			if ($not_date_str != '')
			{
				// NOT condition
				if (!empty($having))
				{
					$having .= " AND (start_date NOT IN ($not_date_str) OR start_date IS NULL)";
				}
				else
				{
					$having = " HAVING start_date NOT IN ($not_date_str) OR start_date IS NULL";
				}
			}
			
			if ($date_str != '')
			{
				// Without NOT Condition
				if (!empty($having))
				{
					$having .= " AND start_date IN ($date_str) ";
				}
				else
				{
					$having = "HAVING start_date IN ($date_str) ";
				}
			}
		}
		//End code startdate filter

		$select_sql = "
			mss.id,
			mss.advertiser_name,
			mss.source_table,
			mss.io_advertiser_id,
			mss.tracking_tag_file_id,
			mss.creation_time,
			mss.last_updated,
			mss.owner_user_id,
			mss.unique_display_id,
			CONCAT(owner.firstname, ' ', owner.lastname) AS owner_name,
			mss.mpq_type,
			mss.is_submitted,
			mss.io_lock_timestamp,
			ios.opportunity_status,
			ios.product_status,
			ios.geo_status,
			ios.audience_status,
			ios.flights_status,
			ios.creative_status,
			ios.notes_status,
			ios.tracking_status,
			CASE WHEN
				COALESCE(o_and_o_forecast.unsuccessful,0) > 0 
			THEN
				0
			ELSE
				1		
			END AS forecast_status,			
			COUNT(cmp.id) AS associated_campaigns,
			partner.partner_name,
			partner.is_demo_partner AS is_demo,
			DATE_FORMAT(MIN(cts.series_date), '%Y-%m-%d') as start_date,
			0 AS timeseries_sum_impressions,
			ROUND(DATEDIFF(MAX(cts.series_date), MIN(cts.series_date))/30) AS timeseries_months,
			mss.order_name,
			mss.order_id,
			mss.mailgun_error_text,
			COALESCE(adv.Name,adv_unv.name) AS io_advertiser_name
			";

		if($role == 'admin' or $role == 'ops')
		{
			$query = "
				SELECT
					".$select_sql."
				FROM 
				" . $partner_join . "
					mpq_sessions_and_submissions AS mss
					JOIN users AS owner
						ON mss.owner_user_id = owner.id
					JOIN wl_partner_details AS partner 
						ON owner.partner_id = partner.id
						" . $partner_join_descendent . "
					JOIN io_status AS ios
						ON mss.id = ios.mpq_id
					LEFT JOIN Campaigns AS cmp
						ON mss.id = cmp.insertion_order_id AND mss.io_advertiser_id = cmp.business_id
					LEFT JOIN cp_submitted_products AS csp
						ON mss.id = csp.mpq_id 
					LEFT JOIN campaigns_time_series AS cts
						ON cts.campaigns_id = csp.id
					LEFT JOIN Advertisers AS adv
						ON adv.id = mss.io_advertiser_id
						AND mss.source_table = 'Advertisers'
					LEFT JOIN advertisers_unverified AS adv_unv
						ON adv_unv.id = mss.io_advertiser_id
						AND mss.source_table = 'advertisers_unverified'
					LEFT JOIN
					(
						SELECT DISTINCT
							cpj.mpq_id,
							COUNT(*) AS unsuccessful 
						FROM
							cp_products_join_io AS cpj
							JOIN cp_products AS cpp
								ON cpj.product_id = cpp.id
								AND cpp.o_o_enabled = 1
							JOIN cp_submitted_products AS csp
								ON cpj.mpq_id = csp.mpq_id
							JOIN campaigns_time_series AS cts
								ON cts.campaigns_id = csp.id
								AND cts.object_type_id = 1
							JOIN io_flight_subproduct_details AS ifsd
								ON cts.id = ifsd.io_timeseries_id 
								AND ifsd.dfp_status != 'COMPLETE'
							GROUP BY
								cpj.mpq_id	
					) AS o_and_o_forecast
						ON o_and_o_forecast.mpq_id = mss.id						
				WHERE
					mss.owner_user_id IS NOT NULL AND
					mss.creator_user_id IS NOT NULL " . $io_query . $partner_query . $createddate_query;
			 $query = $query . " GROUP BY mss.id " .$having;
		}
		else if($role == 'sales')
		{
			if($is_super == 1)
			{
				// sales people with isGroupSuper == 1 need to get mpq_sessions_and_submissions from 2 different sources
				// 1) ios owned by users in the current user's partner hierarchy
				// 2) ios owned by users that are in the hierarchy of a partner owned by the current user

				$query = "
					SELECT DISTINCT
						mss_query.*
					FROM
					(
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_details AS partner 
								ON owner.partner_id = partner.id 
							JOIN wl_partner_hierarchy wph
								ON owner.partner_id = wph.descendant_id
							JOIN users AS us
								ON wph.ancestor_id = us.partner_id
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN io_status AS ios
								ON mss.id = ios.mpq_id
							LEFT JOIN Campaigns AS cmp
								ON mss.id = cmp.insertion_order_id AND mss.io_advertiser_id = cmp.business_id
							LEFT JOIN cp_submitted_products AS csp
								ON mss.id = csp.mpq_id 
							LEFT JOIN campaigns_time_series AS cts
								ON cts.campaigns_id = csp.id
							LEFT JOIN Advertisers AS adv
								ON adv.id = mss.io_advertiser_id
								AND mss.source_table = 'Advertisers'
							LEFT JOIN advertisers_unverified AS adv_unv
								ON adv_unv.id = mss.io_advertiser_id
								AND mss.source_table = 'advertisers_unverified'
							LEFT JOIN
							(
								SELECT DISTINCT
									cpj.mpq_id,
									COUNT(*) AS unsuccessful 
								FROM
									cp_products_join_io AS cpj
									JOIN cp_products AS cpp
										ON cpj.product_id = cpp.id
										AND cpp.o_o_enabled = 1
									JOIN cp_submitted_products AS csp
										ON cpj.mpq_id = csp.mpq_id
									JOIN campaigns_time_series AS cts
										ON cts.campaigns_id = csp.id
										AND cts.object_type_id = 1
									JOIN io_flight_subproduct_details AS ifsd
										ON cts.id = ifsd.io_timeseries_id 
										AND ifsd.dfp_status != 'COMPLETE'
									GROUP BY
										cpj.mpq_id	
							) AS o_and_o_forecast
								ON o_and_o_forecast.mpq_id = mss.id								
						WHERE
							us.id = ? AND
							mss.owner_user_id IS NOT NULL AND
							mss.mpq_type IN ('io-submitted','io-saved','io-in-review') AND
							mss.creator_user_id IS NOT NULL
						GROUP BY
							mss.id
						UNION
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_details AS partner 
								ON owner.partner_id = partner.id 
							JOIN wl_partner_hierarchy AS wph
								ON owner.partner_id = wph.descendant_id
							JOIN wl_partner_owners AS wpo
								ON wpo.partner_id = wph.ancestor_id
							JOIN users AS us
								ON wpo.user_id = us.id
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN io_status AS ios
								ON mss.id = ios.mpq_id
							LEFT JOIN Campaigns AS cmp
								ON mss.id = cmp.insertion_order_id AND mss.io_advertiser_id = cmp.business_id
							LEFT JOIN cp_submitted_products AS csp
								ON mss.id = csp.mpq_id 
							LEFT JOIN campaigns_time_series AS cts
								ON cts.campaigns_id = csp.id
							LEFT JOIN Advertisers AS adv
								ON adv.id = mss.io_advertiser_id
								AND mss.source_table = 'Advertisers'
							LEFT JOIN advertisers_unverified AS adv_unv
								ON adv_unv.id = mss.io_advertiser_id
								AND mss.source_table = 'advertisers_unverified'
							LEFT JOIN
							(
								SELECT DISTINCT
									cpj.mpq_id,
									COUNT(*) AS unsuccessful 
								FROM
									cp_products_join_io AS cpj
									JOIN cp_products AS cpp
										ON cpj.product_id = cpp.id
										AND cpp.o_o_enabled = 1
									JOIN cp_submitted_products AS csp
										ON cpj.mpq_id = csp.mpq_id
									JOIN campaigns_time_series AS cts
										ON cts.campaigns_id = csp.id
										AND cts.object_type_id = 1
									JOIN io_flight_subproduct_details AS ifsd
										ON cts.id = ifsd.io_timeseries_id 
										AND ifsd.dfp_status != 'COMPLETE'
									GROUP BY
										cpj.mpq_id	
							) AS o_and_o_forecast
								ON o_and_o_forecast.mpq_id = mss.id	
						WHERE
							us.id = ? AND
							mss.owner_user_id IS NOT NULL AND
							mss.mpq_type IN ('io-submitted','io-saved','io-in-review') AND
							mss.creator_user_id IS NOT NULL
						GROUP BY
							mss.id
					) AS mss_query 
					JOIN io_status AS ios
						ON mss_query.id = ios.mpq_id
					ORDER BY
						mss_query.creation_time DESC";
				$bindings = array($user_id, $user_id);
			}
			else
			{
				// sales people with isGroupSuper == 0 need to get mpq_sessions_and_submissions in 3 ways
				// 1) ios owned by the current user
				// 2) ios owned by users that are in the hierarchy of a partner owned by the current user
				$query = "
					SELECT DISTINCT
						mss_query.*
					FROM
					(
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN freq_industry_tags AS it
								ON mss.industry_id = it.freq_industry_tags_id
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_details AS partner 
								ON owner.partner_id = partner.id 
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN io_status AS ios
								ON mss.id = ios.mpq_id
							LEFT JOIN Campaigns AS cmp
								ON mss.id = cmp.insertion_order_id AND mss.io_advertiser_id = cmp.business_id
							LEFT JOIN cp_submitted_products AS csp
								ON mss.id = csp.mpq_id 
							LEFT JOIN campaigns_time_series AS cts
								ON cts.campaigns_id = csp.id
							LEFT JOIN Advertisers AS adv
								ON adv.id = mss.io_advertiser_id
								AND mss.source_table = 'Advertisers'
							LEFT JOIN advertisers_unverified AS adv_unv
								ON adv_unv.id = mss.io_advertiser_id
								AND mss.source_table = 'advertisers_unverified'
							LEFT JOIN
							(
								SELECT DISTINCT
									cpj.mpq_id,
									COUNT(*) AS unsuccessful 
								FROM
									cp_products_join_io AS cpj
									JOIN cp_products AS cpp
										ON cpj.product_id = cpp.id
										AND cpp.o_o_enabled = 1
									JOIN cp_submitted_products AS csp
										ON cpj.mpq_id = csp.mpq_id
									JOIN campaigns_time_series AS cts
										ON cts.campaigns_id = csp.id
										AND cts.object_type_id = 1
									JOIN io_flight_subproduct_details AS ifsd
										ON cts.id = ifsd.io_timeseries_id 
										AND ifsd.dfp_status != 'COMPLETE'
									GROUP BY
										cpj.mpq_id	
							) AS o_and_o_forecast
								ON o_and_o_forecast.mpq_id = mss.id	
						WHERE
							mss.owner_user_id = ? AND
							mss.owner_user_id IS NOT NULL AND
							mss.mpq_type IN ('io-submitted','io-saved','io-in-review') AND
							mss.creator_user_id IS NOT NULL
						GROUP BY
							mss.id
						UNION
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN wl_partner_hierarchy AS wph
								ON creator.partner_id = wph.descendant_id
							JOIN wl_partner_owners AS wpo
								ON wpo.partner_id = wph.ancestor_id
							JOIN users AS us
								ON wpo.user_id = us.id
							JOIN freq_industry_tags AS it
								ON mss.industry_id = it.freq_industry_tags_id
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_details AS partner 
								ON owner.partner_id = partner.id 
							JOIN io_status AS ios
								ON mss.id = ios.mpq_id
							LEFT JOIN Campaigns AS cmp
								ON mss.id = cmp.insertion_order_id AND mss.io_advertiser_id = cmp.business_id
							LEFT JOIN cp_submitted_products AS csp
								ON mss.id = csp.mpq_id 
							LEFT JOIN campaigns_time_series AS cts
								ON cts.campaigns_id = csp.id
							LEFT JOIN Advertisers AS adv
								ON adv.id = mss.io_advertiser_id
								AND mss.source_table = 'Advertisers'
							LEFT JOIN advertisers_unverified AS adv_unv
								ON adv_unv.id = mss.io_advertiser_id
								AND mss.source_table = 'advertisers_unverified'
							LEFT JOIN
							(
								SELECT DISTINCT
									cpj.mpq_id,
									COUNT(*) AS unsuccessful 
								FROM
									cp_products_join_io AS cpj
									JOIN cp_products AS cpp
										ON cpj.product_id = cpp.id
										AND cpp.o_o_enabled = 1
									JOIN cp_submitted_products AS csp
										ON cpj.mpq_id = csp.mpq_id
									JOIN campaigns_time_series AS cts
										ON cts.campaigns_id = csp.id
										AND cts.object_type_id = 1
									JOIN io_flight_subproduct_details AS ifsd
										ON cts.id = ifsd.io_timeseries_id 
										AND ifsd.dfp_status != 'COMPLETE'
									GROUP BY
										cpj.mpq_id	
							) AS o_and_o_forecast
								ON o_and_o_forecast.mpq_id = mss.id	
						WHERE
							us.id = ? AND
							mss.owner_user_id IS NOT NULL AND
							mss.mpq_type IN ('io-submitted','io-saved','io-in-review') AND
							mss.creator_user_id IS NOT NULL
						GROUP BY
							mss.id
					) AS mss_query 
					JOIN io_status AS ios
						ON mss_query.id = ios.mpq_id
					ORDER BY
						mss_query.creation_time DESC";
				$bindings = array($user_id, $user_id);
			}
		}
		else
		{
			return false;
		}
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			$timeseries_sums = $this->get_timeseries_sums_for_ios($user_id, $role, $is_super);
			$raw_rfp_data = $response->result_array();
			foreach($raw_rfp_data as &$io_row)
			{
				foreach($timeseries_sums as $timeseries_sum)
				{
				    if($io_row['id'] == $timeseries_sum['id'])
					{
						$io_row['timeseries_sum_impressions'] = $timeseries_sum['timeseries_sum_impressions'];
						break;
					}
				}
			}
			return $raw_rfp_data;
		}
		return false;
	}

	public function get_timeseries_sums_for_ios()
	{
		$query = "
			SELECT
				mss.id,
				SUM(cts.impressions) AS timeseries_sum_impressions
			FROM
				mpq_sessions_and_submissions AS mss
				JOIN cp_submitted_products AS csp
					ON mss.id = csp.mpq_id
				JOIN campaigns_time_series AS cts
					ON csp.id = cts.campaigns_id
			WHERE				
				mss.owner_user_id IS NOT NULL AND
				mss.creator_user_id IS NOT NULL AND
				mss.mpq_type IN ('io-submitted','io-saved','io-in-review')
			GROUP BY 
				mss.id";
		$response = $this->db->query($query);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}

	public function is_insertion_order_launchable($insertion_order_id)
	{
		//Insertion order is launchable if:
			//Statuses for each segment is 1
			//Status is io-submitted
			//io has products selected that are able to become campaigns
			//Advertiser selected isn't trash advertiser?
		$check_io_status_query =
		"SELECT 
			mss.id,
			mss.owner_user_id,
			mss.mpq_type,
			mss.is_submitted,
			ios.opportunity_status,
			ios.product_status,
			ios.geo_status,
			ios.audience_status,
			ios.flights_status,
			ios.creative_status,
			ios.tracking_status
		FROM
			mpq_sessions_and_submissions AS mss
			JOIN users AS owner
				ON mss.owner_user_id = owner.id
			JOIN io_status AS ios
				ON mss.id = ios.mpq_id
			JOIN cp_products_join_io AS cpji
				ON mss.id = cpji.mpq_id
			JOIN cp_products AS cp
				ON cpji.product_id = cp.id
		WHERE
			mss.id = ?
			AND ios.opportunity_status = 1
			AND ios.product_status = 1
			AND ios.geo_status = 1
			AND ios.audience_status = 1
			AND ios.flights_status = 1
			AND ios.creative_status = 1
			AND ios.tracking_status = 1
			AND mss.mpq_type = 'io-submitted'
			AND mss.source_table = 'Advertisers'
			AND cp.can_become_campaign = 1
		GROUP BY
			mss.id
		";
		$check_io_status_result = $this->db->query($check_io_status_query, $insertion_order_id);

		if($check_io_status_result == false || $check_io_status_result->num_rows() != 1)
		{
			return false;
		}
		return true;
	}

	public function build_insertion_order_campaigns($insertion_order_id)
	{
		//For each geo region (region data in mpq_sessions_and_submissions?)
		//Make a campaign for each product

		//Get region data
		$get_io_data_query =
		"SELECT 
			*
		FROM
			mpq_sessions_and_submissions
		WHERE
			id = ?
		";

		$get_io_data_result = $this->db->query($get_io_data_query, $insertion_order_id);
		if($get_io_data_result == false)
		{
			return false;
		}
		$insertion_order = $get_io_data_result->result_array();
		$insertion_order = $insertion_order[0];
		$io_advertiser_id = $insertion_order['io_advertiser_id'];
		$io_tag_file_id = $insertion_order['tracking_tag_file_id'];
		//$io_landing_page = $insertion_order['advertiser_website'];
		$io_landing_page = "";

		$advertiser_data = $this->al_model->get_adv_details($io_advertiser_id);
		if(!$advertiser_data)
		{
			echo "FAILED TO GET INSERTION ORDER ADVERTISER DETAILS";
			return false;
		}

		$regions_data = json_decode($insertion_order['region_data']);
		$demographic_data = explode('_', $insertion_order['demographic_data']);

		//Get products data for insertion order
		$get_io_products_query = 
		"SELECT 
			cp.*
		FROM 
			cp_products_join_io AS cpji
			JOIN cp_products AS cp
				ON cpji.product_id = cp.id
		WHERE
			cpji.mpq_id = ?
			AND cp.can_become_campaign = 1
		";

		$get_io_products_result = $this->db->query($get_io_products_query, $insertion_order_id);

		if($get_io_products_result == false || $get_io_products_result->num_rows() < 1)
		{
			echo "FAILED TO GET PRODUCTS";
			return false;
		}

		$products = $get_io_products_result->result_array();
		
		$submitted_product_ids_array = $this->get_submitted_product_ids($insertion_order_id);
		
		$count = 0;
		foreach($regions_data as $region_data)
		{
			foreach($products as $product)
			{
				//Make campaign
				$campaign_name = $region_data->user_supplied_name.' - '.str_replace("reroll", "re Roll", $product['friendly_name']);
				
				$submitted_product_id = $submitted_product_ids_array[$product["id"]][$count];
				
				//Determine if the name needs a duplicate thing slapped on the end of it
				$campaign_name_okay = false;
				$new_campaign_name = $campaign_name;
				$campaign_duplicate_number = 1;
				while($campaign_name_okay == false)
				{
					$name_matched_campaigns = $this->get_campaign_with_name_and_advertiser($new_campaign_name, $io_advertiser_id);
					if($name_matched_campaigns == -1)
					{
						echo "FAILED TO VERIFY CAMPAIGN NAMES";
						return false;
					}
					if($name_matched_campaigns > 0)
					{
						$new_campaign_name = $campaign_name." (".$campaign_duplicate_number.")";
						$campaign_duplicate_number++;
					}
					else
					{
						$campaign_name_okay = true;
					}
				}

				$campaign_insert_array =  array(
					$new_campaign_name,	//campaign_name
					$io_advertiser_id,	//business_id
					0,					//$target_impressions
					null, 				//hard_end_date,
					"",					//start_date,
					"FIXED_TERM",		//term_type,
					"LandingPage" => $io_landing_page,	//Landing_page
					"categories" => array(),			//categories (Probably okay to leave blank for now)
					0, 					//ignore_for_healthcheck
					"",					//invoice budget
					null,				//pause_date
					null 				//clone_from_id
				);

				//Create campaign entry in campaigns table
				$campaign_id = $this->al_model->create_campaign($campaign_insert_array, true);
				if((!($campaign_id>0)))
				{
					//Failure to create campaign
					echo "FAILURE TO CREATE CAMPAIGN";
					print_r($campaign_insert_array);
					return false;
				}			

				$updated_campaign_io = $this->assign_insertion_order_to_campaign($insertion_order_id, $campaign_id, false, $submitted_product_id);
				if(!$updated_campaign_io)
				{
					//Failure to link
					echo "FAILURE TO LINK CAMPAIGN ({$campaign_id}) TO IO ({$insertion_order_id})";
					return false;

				}

				//Assign tag file to campaign
				if(!empty($io_tag_file_id))
				{
					if(!$this->tag_model->create_tag_files_to_campaigns_entry($io_tag_file_id, $campaign_id))
					{
						//Failure to assign tag file
						echo "FAILURE TO DO ASSIGN CAMPAIGN {$campaigns_id} TO TAG FILE ({$io_tag_file_id})";
						return false;
					}
				}

				//Create timeseries targets
				if(!$this->copy_timeseries_to_new_campaign($insertion_order_id, $campaign_id, $product['id'], $region_data->page))
				{
					//Failure to copy timeseries
					echo "FAILURE TO COPY TIMESERIES FROM INSERTION ORDER ({$insertion_order_id}) FOR NEW CAMPAIGN ({$campaigns_id})";
					return false;
				}
				
				//copy timesieries to new flight subproduct
				if(!$this->copy_io_flights_for_new_campaign($campaign_id, $submitted_product_id))
				{
					//Failure to copy timeseries
					echo "FAILURE TO COPY TIMESERIES FROM CAMPAIGN ID ({$campaign_id}) FROM PRODUCT ID ({$submitted_product_id})";
					return false;
				}
				
				//copy product cpm to campaign cpm
				if(!$this->update_cpm_from_io_to_campaign($campaign_id, $submitted_product_id))
				{
					//Failure to copy timeseries
					echo "FAILURE FOR UPDATING CPM FOR CAMPAIGN ID ({$campaign_id}) FROM PRODUCT ID ({$submitted_product_id})";
					return false;
				}
				
				//Copy Adgroup ids from product to campaign
				if(!$this->update_adgroup_with_new_campaign($campaign_id, $submitted_product_id))
				{
					//Failure to copy timeseries
					echo "FAILURE FOR UPDATING ADGROUP FOR CAMPAIGN ID ({$campaign_id}) FROM PRODUCT ID ({$submitted_product_id})";
					return false;
				}
				
				//Create zip list
				$zips = $region_data->ids->zcta;
				$zips = implode(", ", $zips);
				if(!$this->save_zips_to_campaign($zips, $campaign_id))
				{
					//Failure to save zips
					echo "FAILURE TO SAVE ZIPS FOR CAMPAIGN ({$campaign_id})";
					return false;
				}

				$iab_contextual_categories = $this->get_iab_category_ids($insertion_order_id);
				if($iab_contextual_categories === false)
				{
					//Failure to retrieve iab categories
					echo "FAILURE TO RETRIEVE IAB CATEGORIES FOR INSERTION ORDER ({$insertion_order_id})";
					return false;
				}
				$iab_categories_string = "";
				//$iab_categories = array_column($iab_contextual_categories, "iab_category_id");
				$iab_categories = array();
				foreach($iab_contextual_categories as $category)
				{
					$iab_categories[] = $category['iab_category_id'];
				}
				$iab_categories_string = implode(",", $iab_categories);

				$type_of_sites = "PREMIUM";

				//Create site list
				$sitelist_data = $this->siterankpoc_model->get_sites_for_mpq_ttd(
					50,												//iab_weight
					5, 												//iab_n_weight
					25, 											//reach_weight
					200, 											//stereo_weight
					500, 											//industry_multi
					250, 											//industry_n_multi
					25, 											//iab_multi
					$insertion_order['industry_id'],
					$iab_categories_string, 
					$zips, 
					$demographic_data[0] == 1 ? "true" : "false",	//gender_male_demographic, 
					$demographic_data[1] == 1 ? "true" : "false", 	//$gender_female_demographic, 
					$demographic_data[2] == 1 ? "true" : "false", 	//age_under_18_demographic, 
					$demographic_data[3] == 1 ? "true" : "false", 	//age_18_to_24_demographic, 
					$demographic_data[4] == 1 ? "true" : "false", 	//age_25_to_34_demographic, 
					$demographic_data[5] == 1 ? "true" : "false", 	//age_35_to_44_demographic, 
					$demographic_data[6] == 1 ? "true" : "false", 	//age_45_to_54_demographic, 
					$demographic_data[7] == 1 ? "true" : "false", 	//age_55_to_64_demographic, 
					$demographic_data[8] == 1 ? "true" : "false", 	//age_over_65_demographic, 
					$demographic_data[9] == 1 ? "true" : "false", 	//income_under_50k_demographic, 
					$demographic_data[10] == 1 ? "true" : "false",	//income_50k_to_100k_demographic, 
					$demographic_data[11] == 1 ? "true" : "false",	//income_100k_to_150k_demographic, 
					$demographic_data[12] == 1 ? "true" : "false",	//income_over_150k_demographic, 
					$demographic_data[16] == 1 ? "true" : "false",	//parent_no_kids_demographic,
					$demographic_data[17] == 1 ? "true" : "false",	//parent_has_kids_demographic, 
					$demographic_data[13] == 1 ? "true" : "false",	//education_no_college_demographic, 
					$demographic_data[14] == 1 ? "true" : "false",	//education_college_demographic, 
					$demographic_data[15] == 1 ? "true" : "false",	//education_grad_school_demographic, 
					$type_of_sites
				);
				//Save site list data
				//$sites = array_column($sitelist_data, 'url');
				$sites = array();
				foreach($sitelist_data as $site_entry)
				{
					$sites[] = $site_entry['url'];
				}
				$raw_site_string = implode("\t\t1".PHP_EOL, $sites)."\t\t1";

				$sitelist_name = $advertiser_data['Name']." - ".$campaign_name." - ".$campaign_id." - Launched ".date("n.j.Y");
				$saved_sitelist = $this->tradedesk_model->save_sitelist_to_database_for_campaign(null, $sitelist_name, $raw_site_string, $campaign_id);
				if($saved_sitelist == false)
				{
					echo "FAILURE TO SAVE SITELIST FOR CAMPAIGN ({$campaign_id})";
					return false;
				}
			}
			$count++;
		}
		return true;
	}

	public function get_insertion_order_campaigns($insertion_order_id)
	{
		$get_insertion_order_campaigns_query =
		"SELECT
			adv.Name AS adv_name,
			cmp.*
		FROM
			Campaigns AS cmp
			JOIN Advertisers AS adv
				ON cmp.business_id = adv.id
			JOIN mpq_sessions_and_submissions AS mss
				ON cmp.insertion_order_id = mss.id AND cmp.business_id = mss.io_advertiser_id
		WHERE
			mss.id = ?
		ORDER BY
			cmp.id ASC
		";

		$get_insertion_order_campaigns_result = $this->db->query($get_insertion_order_campaigns_query, $insertion_order_id);
		if($get_insertion_order_campaigns_result == false)
		{
			return -1;
		}
		if($get_insertion_order_campaigns_result->num_rows() < 1)
		{
			return false;
		}

		return $get_insertion_order_campaigns_result->result_array();
	}

	private function copy_timeseries_to_new_campaign($insertion_order_id, $campaign_id, $product_id, $region_index)
	{
		$copy_timeseries_to_new_campaign_query =
		"INSERT INTO 
			campaigns_time_series
			(campaigns_id, series_date, impressions, object_type_id, budget)
			SELECT 
				?,
				ts.series_date,
				ts.impressions,
				?,
				ts.budget
			FROM
				campaigns_time_series AS ts
				JOIN cp_submitted_products AS sp
					ON ts.campaigns_id = sp.id
			WHERE
				sp.mpq_id = ? AND
				ts.object_type_id = 1 AND 
				sp.product_id = ? AND 
				sp.region_data_index = ?
		";

		$bindings = array(
			$campaign_id,
			0,
			$insertion_order_id,
			$product_id,
			$region_index
		);

		$copy_timeseries_to_new_campaign_result = $this->db->query($copy_timeseries_to_new_campaign_query, $bindings);
		if($copy_timeseries_to_new_campaign_result == false)
		{
			return false;
		}
		return $this->db->affected_rows();
	}

	public function assign_insertion_order_to_campaign($insertion_order_id, $campaign_id, $uses_display_id = false, $cp_submitted_products_id = false)
	{
		if($uses_display_id)
		{
			$assign_insertion_order_query = 
			"UPDATE
				Campaigns
			SET
				insertion_order_id = 
				(SELECT 
					id 
				FROM 
					mpq_sessions_and_submissions
				WHERE
					unique_display_id = ?)
			WHERE
				id = ?
			";
			$bindings = array (
				$insertion_order_id,
				$campaign_id
			);			
		}
		else 
		{
			$assign_insertion_order_query = 
			"UPDATE
				Campaigns
			SET
				insertion_order_id = ?,
				cp_submitted_products_id = ?
			WHERE
				id = ?
			";
			$bindings = array (
				$insertion_order_id,
				$cp_submitted_products_id,
				$campaign_id
			);			

		}


		$assign_insertion_order_result = $this->db->query($assign_insertion_order_query, $bindings);
		if($assign_insertion_order_result == false)
		{
			return false;
		}
		return true;
	}

	private function save_zips_to_campaign($zips, $campaign_id)
	{
		$save_zips_query = 
		"INSERT INTO 
			campaigns_zip_data
			(frq_campaign_id, zip_list)
		VALUES
			(?, ?)
		";

		$bindings = array (
			$campaign_id,
			$zips
		);

		$save_zips_result = $this->db->query($save_zips_query, $bindings);
		if($save_zips_result == false)
		{
			return false;
		}
		return true;
	}

	private function get_iab_category_ids($insertion_order_id)
	{
		$get_iab_categories_query = 
		"SELECT
			iab_category_id
		FROM
			mpq_iab_categories_join
		WHERE
			mpq_id = ?
		";

		$get_iab_categories_result = $this->db->query($get_iab_categories_query, $insertion_order_id);

		if($get_iab_categories_result == false)
		{
			return false;
		}
		return $get_iab_categories_result->result_array();
	}

	private function get_campaign_with_name_and_advertiser($search_name, $advertiser_id)
	{
		$get_name_match_query = 
		"SELECT
			id
		FROM
			Campaigns
		WHERE
			name = ?
			AND business_id = ?
		";
		$get_name_match_result = $this->db->query($get_name_match_query, array($search_name, $advertiser_id));

		if($get_name_match_result == false)
		{
			return -1;
		}
		return $get_name_match_result->num_rows();
	}

	public function get_notes_from_insertion_order($insertion_order_id)
	{
		$get_io_data_query =
		"SELECT 
			notes,
			COALESCE(include_retargeting, 0) AS include_retargeting
		FROM
			mpq_sessions_and_submissions
		WHERE
			id = ?
		";

		$get_io_data_result = $this->db->query($get_io_data_query, $insertion_order_id);
		if($get_io_data_result == false || $get_io_data_result->num_rows() < 1)
		{
			return false;
		}
		$row = $get_io_data_result->row_array();
		$notes_string = "";
		if(!empty($row['notes']))
		{
			$notes_string .= $row['notes'];
		}
		return array('rtg'=>$row['include_retargeting'], 'notes'=>$notes_string);

	}
	
	// New Methods
	public function get_submitted_product_ids($insertion_order_id)
	{  
		$get_submitted_product_query = 
			 ' SELECT 
				id,
				product_id,
				region_data_index
			    FROM
				cp_submitted_products
			    WHERE
				mpq_id = ?';
		$get_submitted_product_query_result = $this->db->query($get_submitted_product_query, $insertion_order_id);
		
		if($get_submitted_product_query_result != false)
		{
		    $get_submitted_product_query_result = $get_submitted_product_query_result->result_array();

		    $result_array = array();
		    foreach($get_submitted_product_query_result as $cp_product_id)
		    {
			    $result_array[$cp_product_id['product_id']][$cp_product_id['region_data_index']] = $cp_product_id['id'];
		    }    
		    return $result_array;
		}
		return false;
	}
	
	public function copy_io_flights_for_new_campaign($campaign_id, $product_id)
	{
		$select_old_io_subproduct_id_query = 
			' SELECT 
					cts.id AS id
				FROM	
					campaigns_time_series AS cts				
				WHERE
					cts.campaigns_id = ?
					AND object_type_id = 1';
		$bindings = array($product_id);
		$select_old_io_subproduct_id_query_result = $this->db->query($select_old_io_subproduct_id_query, $bindings);
		$select_old_io_subproduct_id_query_result = $select_old_io_subproduct_id_query_result->result_array();
		
		$select_new_io_subproduct_id_query = 
			' SELECT 
					cts.id AS id
				FROM	
					campaigns_time_series AS cts				
				WHERE
					cts.campaigns_id = ?
					AND object_type_id = 0';
		$bindings = array($campaign_id);
		$select_new_io_subproduct_id_query_result = $this->db->query($select_new_io_subproduct_id_query, $bindings);
		$select_new_io_subproduct_id_query_result = $select_new_io_subproduct_id_query_result->result_array();

		
		$select_old_io_subproduct_id_query_result = array_map('current', $select_old_io_subproduct_id_query_result);
		$select_new_io_subproduct_id_query_result = array_map('current', $select_new_io_subproduct_id_query_result);

		$i = 0;
		foreach($select_old_io_subproduct_id_query_result as $flight_id)
		{
			
			$copy_timeseries_to_new_campaign_query =
			"INSERT INTO 
				io_flight_subproduct_details
				(io_timeseries_id, updated, f_o_o_adgroup_id, o_o_impressions, o_o_impressions_from_dfp, geofencing_impressions, audience_ext_impressions, response_o_o_dfp_budget, geofencing_budget, audience_ext_budget, archived_flag, dfp_object_templates_order_id, dfp_object_templates_lineitem_id, dfp_status, dfp_response)
				SELECT 
					?,
					NOW(),
					NULL,
					ifsd.o_o_impressions,
					ifsd.o_o_impressions_from_dfp, 
					ifsd.geofencing_impressions,
					ifsd.audience_ext_impressions,
					ifsd.response_o_o_dfp_budget,
					ifsd.geofencing_budget,
					ifsd.audience_ext_budget,
					ifsd.archived_flag,
					ifsd.dfp_object_templates_order_id,
					ifsd.dfp_object_templates_lineitem_id,
					ifsd.dfp_status,
					ifsd.dfp_response
				FROM
					io_flight_subproduct_details AS ifsd
				WHERE
					ifsd.io_timeseries_id = ?
			";
			
			$bindings = array(
				$select_new_io_subproduct_id_query_result[$i],
				$select_old_io_subproduct_id_query_result[$i]
			);
			$i++;
			$copy_timeseries_to_new_campaign_result = $this->db->query($copy_timeseries_to_new_campaign_query, $bindings);
			if($copy_timeseries_to_new_campaign_result == false)
			{
				return false;
			}
		}
		return true;
	}
	
	private function update_cpm_from_io_to_campaign($campaign_id, $submitted_product_id)
	{
		$copy_campaign_product_cpm_query = 
		"INSERT INTO
			io_campaign_product_cpm
		(campaign_id, subproduct_type_id, dollar_cpm, max_dollar_pct)
		(SELECT
			?,
			subproduct_type_id,
			dollar_cpm,
			max_dollar_pct
		FROM
			io_campaign_product_cpm
		WHERE
			cp_submitted_products_id = ?
		)
		";
		$bindings = array($campaign_id, $submitted_product_id);
		$copy_campaign_product_cpm_result = $this->db->query($copy_campaign_product_cpm_query, $bindings);

		if($copy_campaign_product_cpm_result == false)
		{
			return false;
		}
		return true;
	}
	
	public function update_adgroup_with_new_campaign($campaign_id, $cp_submitted_product_id)
	{
		$select_adgroups_query =
			'
				UPDATE
					AdGroups
				SET 
					campaign_id = '.$campaign_id.'
				WHERE 
					ID IN (	
						SELECT 
							ifsd.f_o_o_adgroup_id AS adgroup_id
						FROM
							io_flight_subproduct_details as ifsd
							JOIN campaigns_time_series as cts
							    ON (cts.id = ifsd.io_timeseries_id)
						WHERE
							cts.campaigns_id = ?
							AND object_type_id = 1
							AND ifsd.f_o_o_adgroup_id IS NOT NULL
					)
			';
		
		$select_adgroups_query_result = $this->db->query($select_adgroups_query, $cp_submitted_product_id);
		
		if($this->db->affected_rows() >= 0)
		{
		    return true;
		}
		return false;
	}
}
