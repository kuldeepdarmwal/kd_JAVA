<?php

class lift_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}
	
	public function process_lift_site_visits_30_data_for_demo($last_day_of_previous_to_previous_month, $demo_campaigns)
	{
		$bindings = array($last_day_of_previous_to_previous_month);
		$query = "
				SELECT 
					group_concat(distinct campaign_id separator ',') AS campaign_ids
				FROM 
					lift_site_visits_30 
				WHERE 
					campaign_id IN (".implode(",",$demo_campaigns).") 
				AND 
					site_visit_date = ?				
                        ";
                $response = $this->db->query($query, $bindings);
		$result = $response->row_array();
		
		if ($result['campaign_ids'] != null)
		{
			$result_campaigns = explode(",", $result['campaign_ids']);
			if (count($demo_campaigns) != count($result_campaigns))
			{
				$this->setup_lift_site_visits_30_data_for_demo(array_diff($demo_campaigns,$result_campaigns), 
					$last_day_of_previous_to_previous_month);
			}
		}
		else
		{
			$this->setup_lift_site_visits_30_data_for_demo($demo_campaigns, $last_day_of_previous_to_previous_month);
		}
	}
	
	private function setup_lift_site_visits_30_data_for_demo($campaign_ids, $last_day_of_previous_to_previous_month)
	{
		$query = "
				SELECT 
					*
				FROM 
					lift_site_visits_30 
				WHERE 
					campaign_id IN (".implode(",",$campaign_ids).") 
				AND 
					site_visit_date = '2016-04-30'
                        ";
                $response = $this->db->query($query);
		
		if ($response->num_rows() > 0)
		{
			foreach ($response->result_array() as $lift_site_visits_30_row)
			{
				$campaign_id = $lift_site_visits_30_row['campaign_id'];
				$site_visit_count_all = $lift_site_visits_30_row['site_visit_count_all'];
				$site_visit_count_all = ($site_visit_count_all == '') ? "NULL" : $site_visit_count_all;
				$site_visit_count_unique = $lift_site_visits_30_row['site_visit_count_unique'];
				$site_visit_count_unique = ($site_visit_count_unique == '') ? "NULL" : $site_visit_count_unique;
				$aggregation_type = $lift_site_visits_30_row['aggregation_type'];
				
				$insert_temp_table_query = "
								INSERT INTO 
									lift_site_visits_30
								VALUES($campaign_id,'$last_day_of_previous_to_previous_month',$site_visit_count_all,$site_visit_count_unique,'$aggregation_type')
							";
				$this->db->query($insert_temp_table_query);
			}
		}		
	}
	
	public function process_lift_site_visits_30_zip_data_for_demo($last_day_of_previous_to_previous_month, $demo_campaigns)
	{
		$bindings = array($last_day_of_previous_to_previous_month);
		$query = "
				SELECT 
					group_concat(distinct campaign_id separator ',') AS campaign_ids
				FROM 
					lift_site_visits_30_zip 
				WHERE 
					campaign_id IN (".implode(",",$demo_campaigns).") 
				AND 
					site_visit_date = ?				
                        ";
                $response = $this->db->query($query, $bindings);
		$result = $response->row_array();
		
		if ($result['campaign_ids'] != null)
		{
			$result_campaigns = explode(",", $result['campaign_ids']);
			if (count($demo_campaigns) != count($result_campaigns))
			{
				$this->setup_lift_site_visits_30_zip_data_for_demo(array_diff($demo_campaigns,$result_campaigns), $last_day_of_previous_to_previous_month);
			}
		}
		else
		{
			$this->setup_lift_site_visits_30_zip_data_for_demo($demo_campaigns, $last_day_of_previous_to_previous_month);
		}
	}
	
	private function setup_lift_site_visits_30_zip_data_for_demo($campaign_ids, $last_day_of_previous_to_previous_month)
	{
		$query = "
				SELECT 
					*
				FROM 
					lift_site_visits_30_zip 
				WHERE 
					campaign_id IN (".implode(",",$campaign_ids).") 
				AND 
					site_visit_date = '2016-04-30'
                        ";
                $response = $this->db->query($query);
		
		if ($response->num_rows() > 0)
		{
			foreach ($response->result_array() as $lift_site_visits_30_zip_row)
			{
				$campaign_id = $lift_site_visits_30_zip_row['campaign_id'];
				$zip = $lift_site_visits_30_zip_row['zip'];
				$site_visit_count_all = $lift_site_visits_30_zip_row['site_visit_count_all'];
				$site_visit_count_all = ($site_visit_count_all == '') ? "NULL" : $site_visit_count_all;
				$site_visit_count_unique = $lift_site_visits_30_zip_row['site_visit_count_unique'];
				$site_visit_count_unique = ($site_visit_count_unique == '') ? "NULL" : $site_visit_count_unique;
				$aggregation_type = $lift_site_visits_30_zip_row['aggregation_type'];
				
				$insert_temp_table_query = "
								INSERT INTO 
									lift_site_visits_30_zip
								VALUES($campaign_id,'$zip','$last_day_of_previous_to_previous_month',$site_visit_count_all,$site_visit_count_unique,'$aggregation_type')
							";
				$this->db->query($insert_temp_table_query);
			}
		}		
	}
	
	public function process_conversion_lift_reach_no_zip_30_data_for_demo($last_day_of_previous_to_previous_month, $demo_ttd_campaigns)
	{
		$bindings = array($last_day_of_previous_to_previous_month);
		$query = "
				SELECT 
					group_concat(distinct ttd_campaign_id separator ',') AS ttd_campaign_ids
				FROM 
					conversion_lift_reach_no_zip_30 
				WHERE 
					ttd_campaign_id IN ('".implode("','",$demo_ttd_campaigns)."') 
				AND 
					impression_date = ?				
                        ";
                $response = $this->db->query($query, $bindings);
		$result = $response->row_array();
		
		if ($result['ttd_campaign_ids'] != null)
		{
			$result_campaigns = explode(",", $result['ttd_campaign_ids']);
			if (count($demo_ttd_campaigns) != count($result_campaigns))
			{
				$this->setup_conversion_lift_reach_no_zip_30_data_for_demo(array_diff($demo_ttd_campaigns,$result_campaigns), $last_day_of_previous_to_previous_month);
			}
		}
		else
		{
			$this->setup_conversion_lift_reach_no_zip_30_data_for_demo($demo_ttd_campaigns, $last_day_of_previous_to_previous_month);
		}
	}
	
	private function setup_conversion_lift_reach_no_zip_30_data_for_demo($ttd_campaigns, $last_day_of_previous_to_previous_month)
	{
		$query = "
				SELECT 
					*
				FROM 
					conversion_lift_reach_no_zip_30 
				WHERE 
					ttd_campaign_id IN ('".implode("','",$ttd_campaigns)."') 
				AND 
					impression_date = '2016-04-30'
                        ";
                $response = $this->db->query($query);
		
		if ($response->num_rows() > 0)
		{
			foreach ($response->result_array() as $conversion_lift_reach_no_zip_30_row)
			{
				$ttd_campaign_id = $conversion_lift_reach_no_zip_30_row['ttd_campaign_id'];
				$conversion_count = $conversion_lift_reach_no_zip_30_row['conversion_count'];
				$conversion_count = ($conversion_count == '') ? "NULL" : $conversion_count;
				$reach_count = $conversion_lift_reach_no_zip_30_row['reach_count'];
				$reach_count = ($reach_count == '') ? "NULL" : $reach_count;
				$conversion_count_unique = $conversion_lift_reach_no_zip_30_row['conversion_count_unique'];
				$conversion_count_unique = ($conversion_count_unique == '') ? "NULL" : $conversion_count_unique;
				$conversion_count_nonunique = $conversion_lift_reach_no_zip_30_row['conversion_count_nonunique'];
				$conversion_count_nonunique = ($conversion_count_nonunique == '') ? "NULL" : $conversion_count_nonunique;
				$impression_count = $conversion_lift_reach_no_zip_30_row['impression_count'];
				$impression_count = ($impression_count == '') ? "NULL" : $impression_count;
				
				$insert_temp_table_query = "
								INSERT INTO 
									conversion_lift_reach_no_zip_30
								VALUES
								(
									'$last_day_of_previous_to_previous_month',$conversion_count,$reach_count,'$ttd_campaign_id',$conversion_count_unique,$conversion_count_nonunique,$impression_count
								)
							";
				$this->db->query($insert_temp_table_query);
			}
		}		
	}
	
	public function process_conversion_lift_reach_zip_30_data_for_demo($last_day_of_previous_to_previous_month, $demo_ttd_campaigns)
	{
		$bindings = array($last_day_of_previous_to_previous_month);
		$query = "
				SELECT 
					group_concat(distinct ttd_campaign_id separator ',') AS ttd_campaign_ids
				FROM 
					conversion_lift_reach_zip_30 
				WHERE 
					ttd_campaign_id IN ('".implode("','",$demo_ttd_campaigns)."')	
				AND 
					impression_date = ?				
                        ";
                $response = $this->db->query($query, $bindings);
		$result = $response->row_array();
		
		if ($result['ttd_campaign_ids'] != null)
		{
			$result_campaigns = explode(",", $result['ttd_campaign_ids']);
			if (count($demo_ttd_campaigns) != count($result_campaigns))
			{
				$this->setup_conversion_lift_reach_zip_30_data_for_demo(array_diff($demo_ttd_campaigns,$result_campaigns), $last_day_of_previous_to_previous_month);
			}
		}
		else
		{
			$this->setup_conversion_lift_reach_zip_30_data_for_demo($demo_ttd_campaigns, $last_day_of_previous_to_previous_month);
		}
	}
	
	private function setup_conversion_lift_reach_zip_30_data_for_demo($ttd_campaigns, $last_day_of_previous_to_previous_month)
	{
		$query = "
				SELECT 
					*
				FROM 
					conversion_lift_reach_zip_30 
				WHERE 
					ttd_campaign_id IN ('".implode("','",$ttd_campaigns)."') 
				AND 
					impression_date = '2016-04-30'
                        ";
                $response = $this->db->query($query);
		
		if ($response->num_rows() > 0)
		{
			foreach ($response->result_array() as $conversion_lift_reach_zip_30_row)
			{
				$ttd_campaign_id = $conversion_lift_reach_zip_30_row['ttd_campaign_id'];
				$conversion_count = $conversion_lift_reach_zip_30_row['conversion_count'];
				$conversion_count = ($conversion_count == '') ? "NULL" : $conversion_count;
				$reach_count = $conversion_lift_reach_zip_30_row['reach_count'];
				$reach_count = ($reach_count == '') ? "NULL" : $reach_count;
				$conversion_count_unique = $conversion_lift_reach_zip_30_row['conversion_count_unique'];
				$conversion_count_unique = ($conversion_count_unique == '') ? "NULL" : $conversion_count_unique;
				$conversion_count_nonunique = $conversion_lift_reach_zip_30_row['conversion_count_nonunique'];
				$conversion_count_nonunique = ($conversion_count_nonunique == '') ? "NULL" : $conversion_count_nonunique;
				$impression_count = $conversion_lift_reach_zip_30_row['impression_count'];
				$impression_count = ($impression_count == '') ? "NULL" : $impression_count;
				$zip = $conversion_lift_reach_zip_30_row['zip'];
				
				$insert_temp_table_query = "
								INSERT INTO 
									conversion_lift_reach_zip_30
								VALUES
								(
									'$last_day_of_previous_to_previous_month',$conversion_count,$reach_count,'$zip','$ttd_campaign_id',$conversion_count_unique,$conversion_count_nonunique,$impression_count
								)
							";
				$this->db->query($insert_temp_table_query);
			}
		}		
	}
	
	public function process_campaign_monthly_lift_approval_for_demo($last_day_of_previous_to_previous_month, $demo_campaigns)
	{
		$bindings = array($last_day_of_previous_to_previous_month);
		$query = "
				SELECT 
					group_concat(distinct campaign_id separator ',') AS campaign_ids
				FROM 
					campaign_monthly_lift_approval 
				WHERE 
					campaign_id IN (".implode(",",$demo_campaigns).")	
				AND 
					lift_date = ?				
                        ";
		
                $response = $this->db->query($query, $bindings);
		$result = $response->row_array();
		
		if ($result['campaign_ids'] != null)
		{
			$result_campaigns = explode(",", $result['campaign_ids']);
			if (count($demo_campaigns) != count($result_campaigns))
			{
				$this->setup_campaign_monthly_lift_approval_for_demo(array_diff($demo_campaigns,$result_campaigns), $last_day_of_previous_to_previous_month);
			}
		}
		else
		{
			$this->setup_campaign_monthly_lift_approval_for_demo($demo_campaigns, $last_day_of_previous_to_previous_month);
		}
	}
	
	private function setup_campaign_monthly_lift_approval_for_demo($campaigns, $last_day_of_previous_to_previous_month)
	{	
		foreach ($campaigns as $campaign_id)
		{
			$insert_temp_table_query = "
							INSERT INTO 
								campaign_monthly_lift_approval
							VALUES
							(
								$campaign_id,'$last_day_of_previous_to_previous_month',1,NOW()
							)
						";
			$this->db->query($insert_temp_table_query);
		}		
	}
	
	public function process_lift_campaign_site_visit_dates_for_demo($demo_advertisers)
	{
		$query = "
				UPDATE 
					lift_campaign_site_visit_dates
				SET 
					site_visit_latest_date = DATE_FORMAT(NOW(),'%Y-%m-%d') 
				WHERE 
					campaign_id IN (".implode(",",$demo_advertisers).") 
			";
		$this->db->query($query);		
	}	
}