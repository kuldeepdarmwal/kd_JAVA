<?php

class Insertion_order_model extends CI_Model 
{

	public function __construct()
	{
		$this->load->database();
		$this->load->helper('url');
	}

	public function get_submitted_insertion_orders(
		$user_id, 
		$after_id = null, 
		$since_id = null, 
		$count = null, 
		$start_date = null, 
		$end_date = null, 
		$insertion_order_id = null)
	{
		if (empty($user_id))
		{
			throw new Exception("Authorization is required", 401);
		}

		$sql_bindings = array($user_id);

		$where_string = "";
		$count_string = "";
		if (!empty($insertion_order_id))
		{
			$where_string .= "AND mio.id = ?";
			$sql_bindings[] = $insertion_order_id;
		}
		else // we don't want them to be able to filter if they request a specific IO
		{
			if(!empty($after_id))
			{
				$where_string .= " AND mio.id < ?";
				$sql_bindings[] = $after_id;
			}
			if(!empty($since_id))
			{
				$where_string .= " AND mio.id > ?";
				$sql_bindings[] = $since_id;
			}
			if(!empty($start_date))
			{
				$where_string .= " AND DATE(mss.creation_time) >= DATE(?)";
				$sql_bindings[] = $start_date;
			}
			if(!empty($end_date))
			{
				$where_string .= " AND DATE(mss.creation_time) <= DATE(?)";
				$sql_bindings[] = $end_date;
			}
			if (!empty($count))
			{
				$count = (int)$count;
				$count_string = " LIMIT 0, ?";
				$sql_bindings[] = $count;
			}
		}

		$sql = 
		"	SELECT 
				mio.id,
				mss.id AS mpq_id, 
				mss.creation_time AS created_at,
				mio.landing_page AS landing_page,
				mio.term_type,
				mio.term_duration AS budget_periods,
				mio.start_date,
				mio.end_date,
				mss.region_data,
				mss.notes AS target_geo_notes,
				mss.demographic_data AS target_demographics,
				mio.impressions AS impression_budget, 
				ar.creative_files,
				GROUP_CONCAT(
					iab.tag_friendly_name 
					ORDER BY iab.tag_friendly_name 
					SEPARATOR ','
				) AS target_contextuals
			FROM
				mpq_sessions_and_submissions AS mss 
				JOIN mpq_insertion_orders AS mio ON mio.mpq_id = mss.id
				LEFT JOIN adset_requests AS ar on mss.adset_request_id = ar.id
				LEFT JOIN mpq_iab_categories_join AS mpqiab 
					ON mpqiab.mpq_id = mio.mpq_id
				LEFT JOIN iab_categories AS iab 
					ON iab.id = mpqiab.iab_category_id 
			WHERE
				mss.is_submitted = 1 
				AND 
				mio.id IS NOT NULL
				AND 
				mss.creator_user_id = ? 
			".$where_string."
			GROUP BY mio.id
			ORDER BY 
				mio.id DESC 
			".$count_string
		;

		$response = $this->db->query($sql, $sql_bindings);
		if ($response->num_rows() == 0 && !empty($insertion_order_id))
		{
			throw new Exception("No results found", 404);
		}
		return $response->result();
	}

	public function get_campaign_results($io_id, $start_date, $end_date)
	{
		// Returns only preroll results. Should be modified later to return entire
		// results object.
		$sql = 
		'	SELECT 
				COALESCE(SUM(rvpr.start_count), 0) as preroll_impressions,
				COALESCE(SUM(rcad.clicks), 0) as preroll_clicks, 
				COALESCE(SUM(rvpr.25_percent_count), 0) as preroll_25_percent_count, 
				COALESCE(SUM(rvpr.50_percent_count), 0) as preroll_50_percent_count, 
				COALESCE(SUM(rvpr.75_percent_count), 0) as preroll_75_percent_count, 
				COALESCE(SUM(rvpr.100_percent_count), 0) as preroll_100_percent_count
			FROM Campaigns as c 
			JOIN AdGroups as ag 
				ON c.id = ag.campaign_id 
			JOIN report_video_play_records as rvpr 
				ON rvpr.AdGroupID = ag.ID 
			JOIN report_cached_adgroup_date as rcad 
				ON rcad.adgroup_id = rvpr.AdGroupID AND rcad.date = rvpr.date
			WHERE c.insertion_order_id = ?
		';

		$sql_bindings = array($io_id);

		if (!empty($start_date))
		{
			$sql .= ' AND rcad.date >= ?';
			$sql_bindings[] = $start_date;
		}

		if (!empty($end_date))
		{
			$sql .= ' AND rcad.date <= ?';
			$sql_bindings[] = $end_date;
		}

		/*
		 * This returns 0 for all fields if the campaign exists, but 
		 * doesn't have impressions.
		 * Returns 404 if the campaign doesn't exist.
		 */
		$query = $this->db->query($sql, $sql_bindings);
		if ($query->row()->preroll_impressions == 0)
		{
			$exists_sql = 
			'	SELECT id
				FROM Campaigns 
				WHERE insertion_order_id = ?
			';
			$exists_query = $this->db->query($exists_sql, $sql_bindings);
			if ($exists_query->num_rows() == 0)
			{
				throw new Exception('No results found!', 404);
			}
		}

		return $query->result();
	}

}

