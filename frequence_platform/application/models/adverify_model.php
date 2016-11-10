<?php

class Adverify_model extends CI_Model 
{
	public function __construct()
	{
		
	}
	
	public function add_campaign_list_to_priority_queue($campaign_list)
	{
		$campaign_list = array_reverse($campaign_list);
		$insert_priority_query = "INSERT INTO ad_verify_priority_queue (campaign_id) VALUES ";
		$insert_array = array();
		$query_values = "";
		
		$first_value_set = false;
		foreach($campaign_list as $campaign_id)
		{
			if($first_value_set)
			{
				$query_values .= ", ";
			}
			$first_value_set = true;
			
			$query_values .= "(?)";
			$insert_array[] = $campaign_id;
		}
		
		$insert_priority_query .= $query_values;
		$did_insert_priority_result = $this->db->query($insert_priority_query, $insert_array);
		if($did_insert_priority_result == false)
		{
			return false;
		}
		return true;
	}
	
	public function validate_campaign_list($campaign_list_array)
	{
		foreach($campaign_list_array as $campaign_id)
		{
			if(!is_numeric($campaign_id))
				return false;
		}
		return true;
	}
	
	public function get_screenshot_health_between_dates_($start_date, $end_date)
	{
		$select_array = array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
		$get_adverify_health_query = "
			SELECT 
				? AS 'Min Screen Shot Date',
				? AS 'Max Date',
				q1.*,
				q4.Num_Tags,
				q2.First_Bot_Date,
				q2.Last_Bot_Date,
				COALESCE(q2.Site_List_Size, 0) AS Site_List_Size,
				COALESCE(q2.Num_Bot_Runs, 0) AS Num_Bot_Runs,
				COALESCE(q2.Bot_Hits, 0) AS Bot_Hits,
				COALESCE(q3.Not_Reviewed, 0) AS Not_Reviewed,
				COALESCE(q3.Rejected, 0) AS Rejected,
				COALESCE(q3.Count_Approved, 0) AS Approved
			FROM (
				SELECT 
					u.username AS 'Partner',
					adv.NAME AS 'Advertiser',
					cmp.NAME AS 'Campaign',
					cmp.id AS 'campaign_id',
					cmp.hard_end_date,
					Min(ag.earliest_city_record) AS 'Start_Date',
					Max(ag.latest_city_record) AS 'Last_Imp_Date'
				FROM 
					Advertisers AS adv
					JOIN Campaigns AS cmp 
						ON (adv.id = cmp.business_id)
					JOIN AdGroups AS ag 
						ON (cmp.id = ag.campaign_id)
					JOIN users AS u 
						ON (adv.sales_person = u.id)
				WHERE 
					cmp.ignore_for_healthcheck = 0
					AND ag.source != 'TDAV'
				GROUP BY 
					cmp.id
				) AS q1
			LEFT JOIN (
				SELECT 
					avr.campaign_id,
					MIN(avr.start_time) AS 'First_Bot_Date',
					MAX(avr.start_time) AS 'Last_Bot_Date',
					MAX(avr.list_size) AS 'Site_List_Size',
					COUNT(avr.campaign_id) AS 'Num_Bot_Runs',
					SUM(avr.hits) AS 'Bot_Hits'
				FROM
					ad_verify_records avr
				WHERE 
					avr.start_time BETWEEN ? AND ?
				GROUP BY 
					avr.campaign_id
				) q2 
					ON q1.campaign_id = q2.campaign_id
			LEFT JOIN (
				SELECT 
					avss.campaign_id,
					SUM(CASE 
							WHEN isnull(avss.is_approved) = 1
								THEN 1
							ELSE 0
							END) AS 'Not_Reviewed',
					SUM(CASE 
							WHEN avss.is_approved = 1
								THEN 1
							ELSE 0
							END) AS 'Count_Approved',
					SUM(CASE 
							WHEN avss.is_approved = 0
								THEN 1
							ELSE 0
							END) AS 'Rejected'
				FROM 
					`ad_verify_screen_shots` AS avss
				WHERE 
					creation_date BETWEEN ? AND ?
				GROUP BY 
					avss.campaign_id
				) q3 
					ON q3.campaign_id = q1.campaign_id
			LEFT JOIN (
				SELECT 
					avcc.campaign_id,
					count(avcc.tag_id) AS 'Num_Tags'
				FROM 
					`ad_verify_campaign_categories` AS avcc
				GROUP BY
					avcc.campaign_id
				) q4 
					ON q4.campaign_id = q1.campaign_id";
		$get_health_result = $this->db->query($get_adverify_health_query,$select_array);
		if($get_health_result == false)
		{
			return false;
		}
		return $get_health_result->result_array();
	}
}

?>