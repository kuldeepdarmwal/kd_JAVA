<?php
class Campaign_health_model extends CI_Model {

	private $ad_interaction_hover_threshold = 1;

	public function __construct(){
		$this->load->database();
		$this->load->model('mpq_v2_model');
	}

	public function get_campaign_info($c_id)
	{
		$campaign_details = $this->get_campaign_details($c_id);

		$return_array['c_type'] = "FIXED_TERM";

		$return_array['c_details'] = $campaign_details;
		return $return_array;
	}

	public function get_campaign_details($c_id){
		$q_string = 'SELECT * FROM Campaigns WHERE id = ?';
		$query = $this->db->query($q_string, array($c_id));
		if($query->num_rows() > 0)
		{
			return  $query->result_array();
		}
		return NULL;
	}
	
	public function get_current_time_series_dates($c_id, $report_date)
	{
		$return_array=array();
		$q_string = 
			"
				SELECT
					inner_query.flight_start_date AS start_date,
					inner_query.flight_end_date AS end_date
				FROM     
				(
					SELECT 
						cts.series_date AS flight_start_date,
						DATE_SUB((SELECT inn.series_date FROM campaigns_time_series AS inn WHERE inn.series_date > cts.series_date AND inn.campaigns_id = cts.campaigns_id LIMIT 1),INTERVAL 1 DAY) AS flight_end_date
					FROM
						campaigns_time_series AS cts
					WHERE
						cts.campaigns_id = ?
					AND
						cts.object_type_id = 0
				) AS inner_query
				WHERE
					inner_query.flight_start_date <= ? 
				AND 
					inner_query.flight_end_date >= ?
			";

		$query = $this->db->query($q_string, array($c_id, $report_date, $report_date));

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$return_array['start_date']=$row['start_date'];
				$return_array['end_date']=$row['end_date'];
			}
			return  $return_array;
		}

		return NULL;
	}
	
	public function reset_all_adgroups_cached_city_record_cycle_impression_sum()
	{
		$return_array=array();
		$q_string = 
			"
				UPDATE 
					AdGroups 
				SET 
					cached_city_record_cycle_impression_sum = 0 
				WHERE 
					cached_city_record_cycle_impression_sum > 0
			";

		$query = $this->db->query($q_string);

		if ($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function get_target_impressions_data($report_date)
	{

		$return_array=array();

		//part 1 : get all campaigns
		$campaign_names_array=array();
		$q_string = "
			SELECT
				name,
				id
			FROM
				Campaigns
			WHERE
				ignore_for_healthcheck=0";

		$query = $this->db->query($q_string);
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$campaign_names_array['c-'. $row['id']]=$row['name'];
			}
		}

		//part 2 : get total target impressions for a campaign
		$target_total_impressions_array=array();
		$q_string = "
			SELECT
				cts.campaigns_id,
				SUM(cts.impressions) AS target_total_impressions
			FROM
				campaigns_time_series cts,
				Campaigns c
			WHERE c.id=cts.campaigns_id AND
				c.ignore_for_healthcheck=0
				AND cts.object_type_id=0
			GROUP BY
				cts.campaigns_id";

		$query = $this->db->query($q_string);
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$target_total_impressions_array['c-'. $row['campaigns_id']]=$row['target_total_impressions'];
			}
		}

		//part 3 : get prorated target impressions for a campaign from the beginning of the campaign upto today
		$prorated_target_total_impressions_array=array();
		$q_string = "
				SELECT
					big_query.campaigns_id as campaigns_id,
					SUM(impressions_previous_cycles + current_cycle) prorated_target_total_impressions
				FROM
					(
						SELECT
							a.campaigns_id,
					 		SUM(a.impressions) AS impressions_previous_cycles,
							0 as current_cycle
						FROM
							campaigns_time_series a,
							(
								SELECT
									cts.campaigns_id AS campaigns_id,
									MAX(cts.series_date) AS 'start_date'
								FROM
									campaigns_time_series cts,
									Campaigns c
								WHERE c.id=cts.campaigns_id AND
									c.ignore_for_healthcheck=0 AND
									cts.object_type_id=0 AND
									cts.series_date <=?
									GROUP BY cts.campaigns_id
							)
							AS start_qry
						WHERE
							a.series_date < start_qry.start_date AND
				 			a.campaigns_id=start_qry.campaigns_id
				 			AND a.object_type_id=0
						GROUP BY
		 					a.campaigns_id
					UNION
						SELECT
				  			a.campaigns_id,
							0,
				   			(a.impressions * DATEDIFF(DATE_SUB(STR_TO_DATE(?, '%Y-%m-%d'), INTERVAL 0 DAY), cycle_start_date)) /
				   				DATEDIFF( cycle_end_date, cycle_start_date)
						FROM
							campaigns_time_series a
						JOIN
						(
							SELECT
								cts.campaigns_id AS campaigns_id,
								MAX(cts.series_date) AS 'cycle_start_date'
							FROM
								campaigns_time_series cts,
								Campaigns c
							WHERE
								c.id=cts.campaigns_id AND
								c.ignore_for_healthcheck=0
								AND cts.object_type_id=0 AND
								cts.series_date <=?
							GROUP BY
								cts.campaigns_id
						) AS start_qry ON
					 		a.campaigns_id=start_qry.campaigns_id
					  	JOIN
						(
							SELECT
								cts.campaigns_id,
								MIN(cts.series_date) AS 'cycle_end_date'
							FROM
								campaigns_time_series cts,
								Campaigns c
							WHERE
								c.id=cts.campaigns_id AND
								c.ignore_for_healthcheck=0 AND
								cts.series_date >=?
								AND cts.object_type_id=0
							GROUP BY
								cts.campaigns_id
						) AS end_qry ON
					 		a.campaigns_id=end_qry.campaigns_id
						WHERE
					  		a.series_date = start_qry.cycle_start_date
					  		AND a.object_type_id=0
					 	GROUP BY
					 		a.campaigns_id
				) big_query
				GROUP BY
				 	big_query.campaigns_id
				";

		$query = $this->db->query($q_string, array($report_date, $report_date, $report_date, $report_date));
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$prorated_target_total_impressions_array['c-'. $row['campaigns_id']]=$row['prorated_target_total_impressions'];
			}
		}

		$return_array['campaign_names_array']=$campaign_names_array;
		$return_array['target_total_impressions_array']=$target_total_impressions_array;
		$return_array['prorated_target_total_impressions_array']=$prorated_target_total_impressions_array;
		return $return_array;
	}

	public function get_impressions_total($c_id,$start,$end){
		$bindings = array($c_id, $start, $end);
		$query_string =
				"
					SELECT 
						coalesce(sum(cr.Impressions),0) AS total_impressions
					FROM 
						Campaigns AS c
						LEFT JOIN AdGroups AS ag 
							ON ag.campaign_id = c.id
							AND (ag.subproduct_type_id != '4' OR ag.subproduct_type_id IS NULL)
						LEFT JOIN CityRecords AS cr 
							ON ag.ID = cr.AdGroupID
					WHERE 
						c.id = ? 
					AND 
						cr.Date  BETWEEN ? AND ?
				";
		return $this->db->query($query_string, $bindings)->result_array();
	}
	
	public function populate_cycle_impressions_by_adgroup($c_id,$start,$end)
	{
		$bindings = array($c_id, $start, $end);
		$query_string =
				"
					SELECT
						ag.ID AS adgroup_id,
						coalesce(sum(cr.Impressions),0) AS total_impressions
					FROM 
						Campaigns AS c
						JOIN AdGroups AS ag 
							ON ag.campaign_id = c.id
						JOIN CityRecords AS cr 
							ON ag.ID = cr.AdGroupID							
					WHERE 
						c.id = ?
					AND 
						cr.Date  BETWEEN ? AND ?
					GROUP BY
						ag.ID
				";
		$query = $this->db->query($query_string, $bindings);
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() AS $row)
			{
				$bindings = array($row['total_impressions'], $row['adgroup_id']);
				$update_string =
						"
							UPDATE
								AdGroups As adg
							SET
								cached_city_record_cycle_impression_sum = ?
							WHERE	
								adg.id = ?
						";
				$this->db->query($update_string, $bindings);
			}
			
			return true;
		}
		
		return false;
	}


	public function get_last_impression_date(){
		$query_string = 'SELECT max(Date) as value FROM CityRecords';
		return $this->db->query($query_string)->result_array();
	}

	public function get_all_healthcheck_campaigns()
	{
		$campaigns = array();
		$sql = '
			SELECT
				c.id AS id,
				p.partner_name AS partner,
				a.Name AS advertiser,
				c.Name AS c_name,
				coalesce(max(ag.isRetargeting),0) AS retargeting,
				c.TargetImpressions AS target,
				c.hard_end_date AS end_date
			FROM
				Campaigns AS c
				LEFT JOIN Advertisers AS a
					ON a.id = c.business_id
				LEFT JOIN users AS u
					ON a.`sales_person` = u.`id`
				LEFT JOIN wl_partner_details AS p
					ON u.`partner_id` = p.id
				LEFT JOIN AdGroups AS ag
					ON ag.campaign_id = c.id
			WHERE
				ignore_for_healthcheck = 0
			GROUP BY
				partner,
				advertiser,
				c_name
			ORDER BY
				advertiser,
				c_name';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			$campaigns = $query->result_array();
		}
		return $campaigns;
	}

	public function get_allowed_healthcheck_campaigns($role, $is_group_super, $user_id)
	{
		$campaigns = array();
		$binding_array = array();
		if($role == 'sales')
		{
			$binding_array = array($user_id, $user_id);
			$is_super_condition_sql = "";
			if($is_group_super == 1)
			{
				$is_super_condition_sql = " OR u.partner_id = h.ancestor_id";
			}
			$sql = "
				SELECT DISTINCT
					c.id as id,
					COALESCE(pd.partner_name, pd2.partner_name) as partner,
					a.Name as advertiser,
					c.Name as c_name,
					coalesce(max(ag.isRetargeting),0) as retargeting,
					c.TargetImpressions as target,
					c.hard_end_date as end_date
				FROM
					users u
					LEFT JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON  (po.partner_id = h.ancestor_id".$is_super_condition_sql.")
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2
						ON pd.id = u2.partner_id
					RIGHT JOIN Advertisers a
						ON (u2.id = a.sales_person)
					JOIN Campaigns c
						ON a.id = c.business_id
					LEFT JOIN AdGroups ag
						ON c.id = ag.campaign_id
					JOIN users AS u3
						ON a.sales_person = u3.id
					JOIN wl_partner_details AS pd2
						ON u3.partner_id = pd2.id
				WHERE
					c.ignore_for_healthcheck = 0 AND
					(u.id = ? OR a.sales_person = ?)
					GROUP BY id
					ORDER BY advertiser, c_name";



		}
		else if($role == 'ops' || $role == 'admin')
		{
			$sql = "
					SELECT
						c.id AS id,
						p.partner_name AS partner,
						a.Name AS advertiser,
						c.Name AS c_name,
						coalesce(max(ag.isRetargeting),0) AS retargeting,
						c.TargetImpressions AS target,
						c.hard_end_date AS end_date,
						DATEDIFF(c.hard_end_date, cr.report_date)-1 AS days_left
					FROM
						Campaigns AS c
						LEFT JOIN Advertisers AS a
							ON a.id = c.business_id
						LEFT JOIN users AS u
							ON a.`sales_person` = u.`id`
						LEFT JOIN wl_partner_details AS p
							ON u.`partner_id` = p.id
						LEFT JOIN AdGroups AS ag
							ON ag.campaign_id = c.id,
						(SELECT MAX(Date) AS report_date FROM CityRecords) AS cr
					WHERE
						ignore_for_healthcheck = 0
					GROUP BY
						id
					ORDER BY
						advertiser,
						c_name";
		}
		else
		{
			return $campaigns;
		}
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			$campaigns = $query->result_array();
		}
		return $campaigns;
	}

	public function get_lifetime_dates_impressions($c_id){
		$query_string = "SELECT min(cr.Date) as start_date,max(cr.Date) as end_date,sum(cr.Impressions) as total_impressions, sum(cr.Clicks) as total_clicks
                                FROM Campaigns as c
                                LEFT JOIN AdGroups as ag ON ag.campaign_id = c.id
                                LEFT JOIN CityRecords as cr ON ag.ID = cr.AdGroupID
                                WHERE c.id = ?";
		$query = $this->db->query($query_string, $c_id);
		if($query->num_rows() > 0){
			return $query->result_array();
		}
		return NULL;
	}

	public function get_sites($advertiser, $campaign, $start, $end){
		$query = '        SELECT
                                    b.Base_Site AS site,
                                    SUM(b.Impressions) AS impressions,
                                    SUM(b.Clicks) AS clicks

                                    FROM
                                    AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID)

                                    WHERE
                                    a.BusinessName =\''.$advertiser.'\' AND
                                    b.Date BETWEEN \''.$start.'\' AND \''.$end.'\' AND
                                    a.CampaignName RLIKE \''.$campaign.'\' AND
                                    b.Base_Site != \'All other sites\'

                                    GROUP BY b.Base_Site
                                    ORDER BY SUM(b.Impressions) DESC
                                    LIMIT 10
                                    ';
		$return_object['query'] = $query;
		$return_object['success'] = true;
		$return_object['site_results'] = $this->db->query($query)->result_array();
		return $return_object;
	}



	public function get_graveyard_data(){
		$query = " SELECT
                            p.partner_name as partner,
                            A.Name as advertiser,
                            Campaigns.Name as  campaign_name,
                            Campaigns.id as c_id,
                            max(AdGroups.isRetargeting) as RTG,
                            Campaigns.TargetImpressions as target_monthly_impressions,
                            Campaigns.hard_end_date as hard_end_date,
                            min(CityRecords.Date) as start_date,
                            max(CityRecords.Date) as end_date,
                            timestampdiff(month,min(CityRecords.Date),max(CityRecords.Date)) as months
                    FROM
                            Campaigns as Campaigns
                    JOIN AdGroups as AdGroups ON
                            AdGroups.BusinessName = Campaigns.Business AND
                            AdGroups.CampaignName = Campaigns.Name
                    JOIN CityRecords as CityRecords ON
                            AdGroups.ID = CityRecords.AdGroupID
                    LEFT JOIN Advertisers as A ON A.id = Campaigns.business_id
                    LEFT JOIN users as u ON A.`sales_person` = u.`id`
                    LEFT JOIN `wl_partner_details` as p ON p.`id` = u.`partner_id`
                    WHERE
                            Campaigns.ignore_for_healthcheck = TRUE
                    GROUP BY Campaigns.Business,
                            Campaigns.Name
                            ORDER BY Campaigns.Business,max(CityRecords.Date) DESC";

		return $this->db->query($query);
	}

	function get_graveyard_data_2(){
		$query = "
			SELECT
				c.id as c_id,
				p.partner_name as partner,
				a.Name as advertiser,
				c.Name as campaign_name,
				coalesce(max(ag.isRetargeting),0) as RTG,
				c.TargetImpressions as target_monthly_impressions,
				c.hard_end_date as hard_end_date
			FROM
				Campaigns c
				LEFT JOIN Advertisers a
					ON a.id = c.business_id
				LEFT JOIN users u
					ON a.`sales_person` = u.`id`
				LEFT JOIN wl_partner_details p
					ON u.`partner_id` = p.id
				LEFT JOIN AdGroups ag
					ON ag.campaign_id = c.id
			WHERE
				ignore_for_healthcheck = 1
			GROUP BY
				partner,
				advertiser,
				campaign_name
			ORDER BY
				advertiser,
				campaign_name";
		return $this->db->query($query);
	}



	public function update_campaign_view_status($campaign, $ignore_for_healthcheck)
	{
		$query = "
			UPDATE
				Campaigns
			SET
				ignore_for_healthcheck = ?
			WHERE
				id = ?";
		$response = $this->db->query($query, array($ignore_for_healthcheck, $campaign));
		return $response; //true or false
	}


	public function get_campaign_time_series_id($campaign, $start_date, $end_date)
	{

		$start_date = date("Y-m-d", strtotime($start_date));
		$end_date = date("Y-m-d", strtotime($end_date));

		$query =
			"SELECT U.Date as date,
			 SUM(nr_Imps) as total_impressions,
			 SUM(nr_Clks) as total_clicks,
			 SUM(r_Imps) as rtg_impressions,
			 SUM(r_Clks) as rtg_clicks
			 FROM
			((SELECT b.Date as Date, SUM(b.Impressions) as nr_Imps, SUM(b.Clicks) as nr_Clks, 0 as r_Imps, 0 as r_Clks
							FROM (AdGroups a LEFT JOIN CityRecords b ON (a.ID = b.AdGroupID)) LEFT JOIN Campaigns f ON (a.campaign_id = f.id)
							WHERE
							b.Date BETWEEN '".$start_date."' AND '".$end_date."' AND
							a.campaign_id = '".$campaign."'
							GROUP BY a.isRetargeting, b.Date)
			UNION ALL
			(SELECT d.Date as Date, 0 as nr_Imps, 0 as nr_Clks, SUM(d.Impressions) as r_Imps, SUM(d.Clicks) as r_Clks
							FROM (AdGroups c LEFT JOIN CityRecords d ON (c.ID = d.AdGroupID)) LEFT JOIN Campaigns g ON (c.campaign_id = g.id)
							WHERE
							d.Date BETWEEN '".$start_date."' AND '".$end_date."' AND
							c.campaign_id = '".$campaign."'
							AND c.isRetargeting = 1
							GROUP BY c.isRetargeting, d.Date)
			 ) as U
			GROUP BY Date";
		$return_object['query'] = $query;
		$return_object['success'] = true;
		$return_object['time_series'] = $this->db->query($query)->result_array();
		return $return_object;
	}

	public function get_cities_id($campaign, $start, $end)
	{
		$bindings = array($campaign, $start, $end);
		$sql = "
			SELECT
				b.City AS city,
				b.Region AS region,
				SUM(b.Impressions) AS impressions,
				SUM(b.Clicks) AS clicks
			FROM
				AdGroups AS a
				LEFT JOIN CityRecords AS b
					ON a.ID = b.AdGroupID
			WHERE
				a.campaign_id = ? AND
				b.Date BETWEEN ? AND ? AND
				b.City != 'All other cities'
			GROUP BY
				b.City,
				b.Region
			ORDER BY
				SUM(b.Impressions) DESC
			LIMIT 10";

		$return_object['query'] = $sql;
		$return_object['success'] = true;
		$return_object['city_results'] = $this->db->query($sql, $bindings)->result_array();
		return $return_object;
	}

	public function get_sites_id($campaign, $start, $end)
	{
		$bindings = array($campaign, $start, $end);
		$sql = "
			SELECT
				b.Base_Site AS site,
				SUM(b.Impressions) AS impressions,
				SUM(b.Clicks) AS clicks
			FROM
				AdGroups AS a
				LEFT JOIN SiteRecords AS b
					ON a.ID = b.AdGroupID
			WHERE
				a.campaign_id = ? AND
				b.Date BETWEEN ? AND ? AND
				b.Base_Site != 'All other sites'
			GROUP BY
				b.Base_Site
			ORDER BY
				SUM(b.Impressions) DESC
			LIMIT 10";

		$return_object['query'] = $sql;
		$return_object['success'] = true;
		$return_object['site_results'] = $this->db->query($sql, $bindings)->result_array();
		return $return_object;
	}

	public function get_heaviest_site($c_id, $start_date, $end_date)
	{
		$bindings = array($c_id, $start_date, $end_date);
		$sql = "
			SELECT
				coalesce(sum(b.Impressions),0) AS impressions
			FROM
				AdGroups AS a
				LEFT JOIN SiteRecords AS b
					ON a.ID = b.AdGroupID
			WHERE
				a.campaign_id = ? AND
				b.Date BETWEEN ? AND ? AND
				b.Base_Site != 'All other sites'
			GROUP BY
				b.Base_Site
			ORDER BY
				SUM(b.Impressions) DESC
			LIMIT 1";
		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	public function get_impressions_only($c_id, $start_date, $end_date)
	{
		$bindings = array($c_id, $start_date, $end_date);
		$sql = "
			SELECT
				coalesce(sum(b.Impressions),0) AS impressions
			FROM
				AdGroups AS a
				LEFT JOIN SiteRecords AS b
					ON a.ID = b.AdGroupID
			WHERE
				a.campaign_id = ? AND
				b.Date BETWEEN ? AND ?";
		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	public function get_rtg_impressions_only($c_id, $start_date, $end_date)
	{
		$bindings = array($c_id, $start_date, $end_date);
		$sql = "
			SELECT
				coalesce(sum(b.Impressions),0) AS impressions
			FROM
				AdGroups AS a
				LEFT JOIN SiteRecords AS b
					ON a.ID = b.AdGroupID
			WHERE
				a.campaign_id = ? AND
				b.Date BETWEEN ? AND ?
				AND a.IsRetargeting = 1";
		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	public function get_landing_page($c_id)
	{
	    $query = "SELECT LandingPage FROM Campaigns WHERE id = ?";
	    $result = $this->db->query($query, $c_id);
	    if($result->num_rows() > 0)
	    {
		return $result->result_array();
	    }
	    return FALSE;
	}

	public function get_bulk_download($selected_sales_array, $start, $end, $user_id, $role, $is_super, $selected_partner_id, $campaigns_main_ops_referer_flag)
	{

		$binding_array = array($start, $end, $start, $end, $start, $end);
		$select_sql = "
			SELECT DISTINCT
				ad.external_id AS 'External ID',
				ca.id AS 'cid',
				COALESCE(CONCAT(tsa_query.ul_id, '|', tsa_query.eclipse_id)) AS 'UL/Eclipse IDs',
				COALESCE(CONCAT(us.firstname, ' ', us.lastname), us.email) AS 'Account Executive',
				pd.partner_name AS 'Partner Name',
				ad.name AS 'Advertiser Name',
				ca.name AS 'Campaign Name',
				ca.TargetImpressions * 1000 AS 'Impressions Booked',
				ca.term_type AS 'Term Type',
				SUM(IF(ag.IsRetargeting = 0, cr.impressions, 0)) AS 'Non-RTG Impressions',
				SUM(IF(ag.IsRetargeting = 1, cr.impressions, 0)) AS 'RTG Impressions',
				SUM(cr.impressions) AS 'Total Impressions',
				SUM(IF(ag.IsRetargeting = 0, cr.clicks, 0)) AS 'Non-RTG Clicks',
				SUM(IF(ag.IsRetargeting = 1, cr.clicks, 0)) AS 'RTG Clicks',
				SUM(cr.clicks) AS 'Total Clicks',
				(Sum(cr.clicks) + .00)/SUM(cr.impressions) AS 'CTR',
				ca.start_date AS 'Requested Start Date',
				ca.hard_end_date AS 'Requested End Date',
				COALESCE(e_query.hovers, 0) AS 'Ad Hovers',
				COALESCE(e_query.plays, 0) AS 'Video Plays',
				(.00 + COALESCE(e_query.plays, 0) + COALESCE(e_query.hovers, 0) + Sum(cr.clicks)) AS 'Ad Interactions',
				(.00 + COALESCE(e_query.plays, 0) + COALESCE(e_query.hovers, 0) + Sum(cr.clicks))/SUM(cr.impressions) AS 'Ad Interaction Rate',
				(SUM(cr.post_impression_conversion_1) + SUM(cr.post_impression_conversion_2) + SUM(cr.post_impression_conversion_3) + SUM(cr.post_impression_conversion_4) + SUM(cr.post_impression_conversion_5) + SUM(cr.post_impression_conversion_6)) AS 'View Throughs',
				(SUM(cr.post_impression_conversion_1) + SUM(cr.post_impression_conversion_2) + SUM(cr.post_impression_conversion_3) + SUM(cr.post_impression_conversion_4) + SUM(cr.post_impression_conversion_5) + SUM(cr.post_impression_conversion_6) + .00)/SUM(cr.impressions) AS 'View Through Rate',
				COALESCE(vpr_query.pr_25, 0) AS 'Pre Roll Completion Rate (25%)',
				COALESCE(vpr_query.pr_50, 0) AS 'Pre Roll Completion Rate (50%)',
				COALESCE(vpr_query.pr_75, 0) AS 'Pre Roll Completion Rate (75%)',
				COALESCE(vpr_query.pr_100, 0) AS 'Pre Roll Completion Rate (100%)',
				io_query.order_id AS 'Order ID'
				";
		$external_sql = "
				LEFT JOIN
				(
					SELECT
						tsa.ul_id AS ul_id,
						tsa.eclipse_id AS eclipse_id,
						tsa.advertiser_id AS advertiser_id
					FROM
						tp_spectrum_accounts AS tsa
						LEFT JOIN Advertisers AS adv
							ON tsa.advertiser_id = adv.id
				)AS tsa_query
					ON ad.id = tsa_query.advertiser_id";
		$engagement_sql = "
				LEFT JOIN
				(
				SELECT
					ca.id AS c_id,
					SUM(IF(er.engagement_type = 5 AND er.value >= $this->ad_interaction_hover_threshold, er.total, 0)) AS hovers,
					SUM(IF(er.engagement_type = 1 OR er.engagement_type = 6, er.total, 0)) AS plays
				FROM
					Campaigns AS ca
					JOIN cup_versions AS cv
						ON ca.id = cv.campaign_id
					JOIN cup_creatives AS cc
						ON cv.id = cc.version_id
					LEFT JOIN engagement_records AS er
						ON cc.id = er.creative_id
				WHERE
					er.date BETWEEN ? AND ?
				GROUP BY c_id
				) AS e_query
					ON ca.id = e_query.c_id";

		$video_play_records_sql = "
				LEFT JOIN
				(
				SELECT
					ca.id as campaign_id,
					SUM(vpr.25_percent_count) AS pr_25,
					SUM(vpr.50_percent_count) AS pr_50,
					SUM(vpr.75_percent_count) AS pr_75,
					SUM(vpr.100_percent_count) AS pr_100
				FROM
					Campaigns ca
					JOIN AdGroups AS ag
						ON ca.id = ag.campaign_id
					JOIN report_video_play_records AS vpr
					ON ag.ID = vpr.AdGroupID
					WHERE vpr.date BETWEEN ? AND ?
				GROUP BY ca.id
				) as vpr_query
					ON ca.id = vpr_query.campaign_id";

		$io_sql = "
				LEFT JOIN
				(
					SELECT
						ca.id as campaign_id,
						mss.order_id
					FROM
						Campaigns AS ca
					JOIN
						mpq_sessions_and_submissions AS mss
					ON
						ca.insertion_order_id = mss.id
					WHERE
						mss.order_id IS NOT NULL
				) AS io_query
				ON
					ca.id = io_query.campaign_id
			";

		if($selected_sales_array[0] === 'all')  //admin or ops with "all representatives" selected
		{
			$partner_where_sql = "";
			if($selected_partner_id !== 'all') //specific partner
			{
				$partner_where_sql = "AND pd.id = ?";
				$binding_array[] = $selected_partner_id;
			}
			$all_partner_sql = "
				".$select_sql."
				FROM
					wl_partner_details AS pd
					JOIN users us
						ON pd.id = us.partner_id
					JOIN Advertisers AS ad
						ON us.id = ad.sales_person
					JOIN Campaigns AS ca
						ON ad.id = ca.business_id
					JOIN AdGroups AS ag
						ON ca.id = ag.campaign_id
					JOIN CityRecords AS cr
						ON ag.ID = cr.AdGroupID
					".$external_sql."
					".$engagement_sql."
					".$video_play_records_sql."
					".$io_sql."
				WHERE
					cr.date BETWEEN ? AND ?
					".$partner_where_sql."
				GROUP BY
					ca.id";
		}
		else
		{
			$user_binding_string = "";
			foreach($selected_sales_array as $sales)
			{
				if($user_binding_string !== "")
				{
					$user_binding_string .= ', ';
				}
				$user_binding_string .= '?';
			}
			$all_partner_sql = "
				".$select_sql."
				FROM
					users AS us
					JOIN wl_partner_details pd
						ON us.partner_id = pd.id
					JOIN Advertisers AS ad
						ON us.id = ad.sales_person
					JOIN Campaigns AS ca
						ON ad.id = ca.business_id
					JOIN AdGroups AS ag
						ON ca.id = ag.campaign_id
					JOIN CityRecords AS cr
						ON ag.ID = cr.AdGroupID
					".$external_sql."
					".$engagement_sql."
					".$video_play_records_sql."
					".$io_sql."
				WHERE
					cr.date BETWEEN ? AND ? AND
					us.id IN (".$user_binding_string.")
				GROUP BY
					ca.id";

			$binding_array = array_merge($binding_array, $selected_sales_array);
		}
		$query = $this->db->query($all_partner_sql, $binding_array);
		$timeseries_row = $this->get_time_series_details();
		if($query->num_rows() > 0)
		{
			$bulk_email_array=array();
			$campaigns_for_budget_calculation = array();
			
			foreach ($query->result_array() as $row)
			{
				 	$sub_array=array();
				 	$cid="c-".$row['cid'];
					if($row['UL/Eclipse IDs'] !== '')
					{
						$sub_array['External ID'] = $row['UL/Eclipse IDs'];
					}
					else
					{
						$sub_array['External ID'] = $row['External ID'];
					}
					$sub_array['Order ID'] = $row['Order ID'];
					$sub_array['Account Executive']	=	$row['Account Executive'];
					$sub_array['Partner Name']	=	$row['Partner Name'];
					$sub_array['Advertiser Name']	=	$row['Advertiser Name'];
					$sub_array['Campaign Name']	=	$row['Campaign Name'];
					//	$sub_array['Impressions Booked']	=	$timeseries_row[$cid]['target_total_impressions'];
					if (array_key_exists($cid, $timeseries_row))
					{
						$sub_array['Schedule']	=	$timeseries_row[$cid]['timeseries'];
						$sub_array['Campaign Start Date'] = $timeseries_row[$cid]['start_date'];
						$sub_array['Campaign End Date']	= $timeseries_row[$cid]['end_date'];
					}
					else
					{
						$sub_array['Schedule'] = "";
						$sub_array['Campaign Start Date'] = "";
						$sub_array['Campaign End Date']	= "";
					}
					$sub_array['Non-RTG Impressions']	=	$row['Non-RTG Impressions'];
					$sub_array['RTG Impressions']	=	$row['RTG Impressions'];
					$sub_array['Total Impressions']	=	$row['Total Impressions'];
					$sub_array['Non-RTG Clicks']	=	$row['Non-RTG Clicks'];
					$sub_array['RTG Clicks']	=	$row['RTG Clicks'];
					$sub_array['Total Clicks']	=	$row['Total Clicks'];
					$sub_array['CTR']	=	$row['CTR'];

					$sub_array['Ad Interactions']    =    $row['Ad Hovers'];
					$sub_array['Video Plays']	=	$row['Video Plays'];
					$sub_array['Total Engagements']    =    $row['Ad Interactions'];
					$sub_array['Ad Interaction Rate']	=	$row['Ad Interaction Rate'];
					$sub_array['View Throughs']	=	$row['View Throughs'];
					$sub_array['View Through Rate']	=	$row['View Through Rate'];
					$sub_array['Pre Roll Completion Rate (25%)']	=	$row['Pre Roll Completion Rate (25%)'];
					$sub_array['Pre Roll Completion Rate (50%)']	=	$row['Pre Roll Completion Rate (50%)'];
					$sub_array['Pre Roll Completion Rate (75%)']	=	$row['Pre Roll Completion Rate (75%)'];
					$sub_array['Pre Roll Completion Rate (100%)']	=	$row['Pre Roll Completion Rate (100%)'];
					$sub_array['Report Start Date']	= $start;
					$sub_array['Report End Date'] =	$end;
					
					if (!$campaigns_main_ops_referer_flag)
					{
						$campaigns_for_budget_calculation[] = $row['cid'];
						$sub_array['campaign_id'] = $row['cid'];
					}

				 	$bulk_email_array[]=$sub_array;
			}
			
			//Fetch the budget info for campaigns.
			if (count($campaigns_for_budget_calculation) > 0)
			{
				$campaigns_budget_info = $this->get_budget_info_for_campaigns(implode(",",$campaigns_for_budget_calculation));

				if ($campaigns_budget_info && count($campaigns_budget_info) > 0)
				{
					foreach ($bulk_email_array AS &$bulk_report_row)
					{
						if (isset($campaigns_budget_info['result'][$bulk_report_row["campaign_id"]]))
						{
							$budget_info = $campaigns_budget_info['result'][$bulk_report_row["campaign_id"]];
							$schedule = $campaigns_budget_info['result'][$bulk_report_row["campaign_id"]]['schedule'];
							
							$bulk_report_row['Campaign Schedule'] = $schedule;
							$bulk_report_row['All Time Campaign Budget'] = $budget_info['all_time']['campaign']['budget'];
							$bulk_report_row['All Time Campaign Realized Dollars'] = $budget_info['all_time']['campaign']['realized'];
							$bulk_report_row['All Time Campaign OTI'] = $budget_info['all_time']['campaign']['oti'];
							
							if ($campaigns_budget_info['active_subproducts']['O_O_DISPLAY'])
							{
								$bulk_report_row['All Time Audience Ext Budget'] = $budget_info['all_time']['audience_ext']['budget'];
								$bulk_report_row['All Time Audience Ext Realized Dollars'] = $budget_info['all_time']['audience_ext']['realized'];
								$bulk_report_row['All Time Audience Ext OTI'] = $budget_info['all_time']['audience_ext']['oti'];
								
								$bulk_report_row['All Time O&O Budget'] = $budget_info['all_time']['o_and_o']['budget'];
								$bulk_report_row['All Time O&O Realized Dollars'] = $budget_info['all_time']['o_and_o']['realized'];
								$bulk_report_row['All Time O&O OTI'] = $budget_info['all_time']['o_and_o']['oti'];
							}
							
							$bulk_report_row['This Flight Start Date'] = "";
							if (isset($budget_info['this_flight']['flight_start_date']))
							{
								$bulk_report_row['This Flight Start Date'] = $budget_info['this_flight']['flight_start_date'];
							}
							
							$bulk_report_row['This Flight End Date'] = "";
							if (isset($budget_info['this_flight']['flight_end_date']))
							{
								$bulk_report_row['This Flight End Date'] = $budget_info['this_flight']['flight_end_date'];
							}
							
							$bulk_report_row['This Flight Campaign Budget'] = $budget_info['this_flight']['campaign']['budget'];
							$bulk_report_row['This Flight Campaign Realized Dollars'] = $budget_info['this_flight']['campaign']['realized'];
							$bulk_report_row['This Flight Campaign OTI'] = $budget_info['this_flight']['campaign']['oti'];
							
							if ($campaigns_budget_info['active_subproducts']['O_O_DISPLAY'])
							{
								$bulk_report_row['This Flight Audience Ext Budget'] = $budget_info['this_flight']['audience_ext']['budget'];
								$bulk_report_row['This Flight Audience Ext Realized Dollars'] = $budget_info['this_flight']['audience_ext']['realized'];
								$bulk_report_row['This Flight Audience Ext OTI'] = $budget_info['this_flight']['audience_ext']['oti'];
								
								$bulk_report_row['This Flight O&O Budget'] = $budget_info['this_flight']['o_and_o']['budget'];
								$bulk_report_row['This Flight O&O Realized Dollars'] = $budget_info['this_flight']['o_and_o']['realized'];
								$bulk_report_row['This Flight O&O OTI'] = $budget_info['this_flight']['o_and_o']['oti'];
							}
							
						}
						else
						{
							$bulk_report_row['Campaign Schedule'] = "";
							$bulk_report_row['All Time Campaign Budget'] = "";
							$bulk_report_row['All Time Campaign Realized Dollars'] = "";
							$bulk_report_row['All Time Campaign OTI'] = "";
							
							if ($campaigns_budget_info['active_subproducts']['O_O_DISPLAY'])
							{
								$bulk_report_row['All Time Audience Ext Budget'] = "";
								$bulk_report_row['All Time Audience Ext Realized Dollars'] = "";
								$bulk_report_row['All Time Audience Ext OTI'] = "";
								
								$bulk_report_row['All Time O&O Budget'] = "";
								$bulk_report_row['All Time O&O Realized Dollars'] = "";
								$bulk_report_row['All Time O&O OTI'] = "";
							}
							
							$bulk_report_row['This Flight Start Date'] = "";
							$bulk_report_row['This Flight End Date'] = "";
							$bulk_report_row['This Flight Campaign Budget'] = "";
							$bulk_report_row['This Flight Campaign Realized Dollars'] = "";
							$bulk_report_row['This Flight Campaign OTI'] = "";
							
							if ($campaigns_budget_info['active_subproducts']['O_O_DISPLAY'])
							{
								$bulk_report_row['This Flight Audience Ext Budget'] = "";
								$bulk_report_row['This Flight Audience Ext Realized Dollars'] = "";
								$bulk_report_row['This Flight Audience Ext OTI'] = "";
								
								$bulk_report_row['This Flight O&O Budget'] = "";
								$bulk_report_row['This Flight O&O Realized Dollars'] = "";
								$bulk_report_row['This Flight O&O OTI'] = "";
							}
						}
						
						//Remove the Campaign ID added for budget calculation.
						unset($bulk_report_row["campaign_id"]);
					}
				}
			}			
			return $bulk_email_array;
		}
		return false;
	}

 	private function get_time_series_details()
	{
		ini_set("memory_limit", -1); //increase memory limit
			$timeseries_string=$this->get_timeseries_data_for_hover("excel");

			$sql_start_date="
			SELECT
		    	cts.campaigns_id AS campaigns_id,
		    	DATE_FORMAT(MIN(cts.series_date), '%Y-%m-%d')  start_date,
		    	DATE_FORMAT(DATE_SUB(MAX(cts.series_date), INTERVAL 1 day), '%Y-%m-%d') AS end_date,
		    	SUM(cts.impressions) impressions
		    FROM
		    	campaigns_time_series cts,
		    	Campaigns c
		    WHERE
		    	cts.campaigns_id=c.id
		    	AND cts.object_type_id=0
		    GROUP BY
		    	cts.campaigns_id
			";

		$result_array_new=array();
		$query = $this->db->query($sql_start_date);

		if($query->num_rows() > 0)
		{
			$result_array=array();

			foreach ($query->result_array() as $row)
			{
				 $sub_array=array();
				 $cid="c-".$row['campaigns_id'];
				 $start_date=$row['start_date'];
				 $end_date=$row['end_date'];
				 $impressions=$row['impressions'];
				 $timeseries=$timeseries_string[$cid];

				 $sub_array['start_date']=	$start_date;
				 $sub_array['end_date']=	$end_date;
				 $sub_array['timeseries']=	$timeseries;
				 $sub_array['target_total_impressions']=	$impressions;

				 $result_array_new[$cid]=$sub_array;
			}
		}

		return $result_array_new;

	}

	public function get_sales_person_name($c_id)
	{
		$query = "
			SELECT
				COALESCE(CONCAT(concat(u.firstname, ' '), u.lastname), u.email) as Name
			FROM
				Campaigns c
			JOIN
				Advertisers a ON
					(c.business_id = a.id)
			JOIN
					users u ON (a.sales_person = u.id)
			WHERE
			c.id = ?";
		$result =  $this->db->query($query, $c_id);
		if($result->num_rows() > 0)
		{
		return $result->row()->Name;
		}
		return NULL;
	}

	public function get_brand_cdn_url_prefix($c_id)
	{
	    $query = "SELECT par.cname as Name FROM Campaigns c JOIN Advertisers a ON (c.business_id = a.id) JOIN users u ON (a.sales_person = u.id) JOIN wl_partner_details par ON (u.partner_id = par.id) WHERE c.id = ?";
	    $result =  $this->db->query($query, $c_id);
	    if($result->num_rows() > 0)
	    {
		return $result->row()->Name;
	    }
	    return "N/A";
	}

	public function get_tags_for_advertiser_by_campaign($c_id)
	{
	    $query =
	    " 	SELECT
				tf.name,
				tc.*,
				CONCAT(cmp2.id,') ',adv.Name,' : ',cmp2.Name) as full_campaign
			FROM
				Campaigns AS cmp
				JOIN Advertisers AS adv
					ON (cmp.business_id = adv.id)
				JOIN Campaigns AS cmp2
					ON (adv.id = cmp2.business_id)
				JOIN tag_files_to_campaigns AS ct
					ON (cmp2.id = ct.campaign_id)
				JOIN tag_files AS tf
					ON (ct.tag_file_id = tf.id)
				JOIN tag_codes AS tc
					ON (tf.id = tc.tag_file_id)
			WHERE
				tc.tag_type != 1
				AND tc.isActive = 1
				AND cmp.id = ?
			GROUP BY
				tf.name";
	    $result =  $this->db->query($query, $c_id);
	    if($result->num_rows() > 0)
	    {
		return $result->result_array();
	    }
	    return FALSE;
	}

	public function get_campaign_name($c_id)
	{
	    $query = "SELECT Name FROM Campaigns WHERE id = ?";
	    $result =  $this->db->query($query, $c_id);
	    if($result->num_rows() > 0)
	    {
		return $result->row()->Name;
	    }
	    return FALSE;
	}

	public function get_advertiser_name_for_campaign($c_id)
	{
		$sql =
		"	SELECT
				a.Name as name
			FROM
				Campaigns c
				LEFT JOIN Advertisers a
					ON c.business_id = a.id
			WHERE
			c.id = ?";
		$result =  $this->db->query($sql, $c_id);
		if($result->num_rows() > 0)
		{
			return $result->row()->name;
		}
		return FALSE;
	}
	public function can_user_view_placements($u_id)
	{
	    $query = "SELECT placements_viewable FROM users WHERE id = ? AND placements_viewable = 1";
	    $result = $this->db->query($query, $u_id);
	    if($result->num_rows() > 0)
	    {
		return TRUE;
	    }
	    return FALSE;

	}

	public function get_versions_for_campaign($c_id)
	{
	    $query = "SELECT get_versions.id FROM (SELECT v.id, v.adset_id FROM cup_versions v WHERE v.campaign_id = ?  ORDER BY v.id DESC) get_versions GROUP BY get_versions.adset_id ORDER BY get_versions.id DESC";
	    $result =  $this->db->query($query, $c_id);
	    if($result->num_rows() > 0)
	    {
		return $result->result_array();
	    }
	    return FALSE;
	}
	public function get_all_engagements_for_campaign($c_id)
	{
	   $query = "SELECT COALESCE(SUM( en.total ), 0) as engagements
			FROM engagement_records en
			LEFT JOIN cup_creatives cre ON cre.id = en.creative_id
			LEFT JOIN cup_versions ver ON ver.id = cre.version_id
			WHERE ver.campaign_id = ?
			AND ((en.engagement_type = 5 AND en.value >= $this->ad_interaction_hover_threshold) OR (en.engagement_type = 1 OR en.engagement_type = 6))
		    ";
	   $result = $this->db->query($query, $c_id);
	   $engagements = $result->row()->engagements;
	   return $engagements;

	}

	public function has_screenshots_since_last_reset_date($c_id, $last_reset_date)
	{
	    $insert_array = array("id"=>$c_id, "creation_date"=>$last_reset_date);
	    $query = "SELECT id FROM ad_verify_screen_shots WHERE campaign_id = ? AND creation_date > ? AND is_approved";
	    $result = $this->db->query($query, $insert_array);
	    if($result->num_rows() > 0)
	    {
		return TRUE;
	    }
	    return FALSE;
	}
	public function get_sales_people_by_selected_partner_hierarchy($user_id, $is_super, $role, $partner_id)
	{
		$bindings = array($partner_id);
		if($is_super == 1 OR $role == 'admin' OR $role == 'ops')
		{
			$sql = "SELECT DISTINCT
					us2.id AS id,
					COALESCE(CONCAT(us2.firstname, ' ', us2.lastname), us2.email) AS username,
					pd.partner_name AS partner,
					MIN(ag.earliest_city_record) AS earliest_impression,
					MAX(ag.latest_city_record) AS latest_impression
				FROM
					wl_partner_hierarchy AS ph
					JOIN wl_partner_details AS pd
						ON ph.descendant_id = pd.id
					JOIN users AS us2
						ON pd.id = us2.partner_id
					LEFT JOIN Advertisers ad
						ON us2.id = ad.sales_person
					LEFT JOIN Campaigns ca
						ON ad.id = ca.business_id
					LEFT JOIN AdGroups ag
						ON ca.id = ag.campaign_id
				WHERE
					ph.ancestor_id = ? AND
					us2.role = 'SALES'
				GROUP BY
					us2.id
				ORDER BY
					username";
		}
		else
		{
			$sql = "
				SELECT DISTINCT
					alias.*
				FROM
				(
					SELECT DISTINCT
						us2.id AS id,
						COALESCE(CONCAT(us2.firstname, ' ', us2.lastname), us2.email) as username,
						pd3.partner_name AS partner,
						MIN(ag.earliest_city_record) AS earliest_impression,
						MAX(ag.latest_city_record) AS latest_impression
					FROM
						wl_partner_owners po
						JOIN wl_partner_details as pd1
							ON po.partner_id = pd1.id
						JOIN wl_partner_hierarchy as ph1
							ON pd1.id = ph1.ancestor_id
						JOIN wl_partner_details as pd2
							ON ph1.descendant_id = pd2.id
						JOIN wl_partner_hierarchy as ph2
							ON pd2.id = ph2.ancestor_id
						JOIN wl_partner_details as pd3
							ON ph2.descendant_id = pd3.id
						JOIN users us2
							ON pd3.id = us2.partner_id
						LEFT JOIN Advertisers ad
							ON us2.id = ad.sales_person
						LEFT JOIN Campaigns ca
							ON ad.id = ca.business_id
						LEFT JOIN AdGroups ag
							ON ca.id = ag.campaign_id
					WHERE
						pd2.id = ? AND
						po.user_id = ? AND
						us2.role = 'SALES'
					GROUP BY
						us2.id
					UNION
					SELECT
						us3.id,
						COALESCE(CONCAT(us3.firstname, ' ', us3.lastname), us3.email) as username,
						pd4.partner_name AS partner,
						MIN(ag.earliest_city_record) AS earliest_impression,
						MAX(ag.latest_city_record) AS latest_impression
					FROM
						users us3
						JOIN wl_partner_details pd4
							ON us3.partner_id = pd4.id
						LEFT JOIN Advertisers ad
							ON us3.id = ad.sales_person
						LEFT JOIN Campaigns ca
							ON ad.id = ca.business_id
						LEFT JOIN AdGroups ag
							ON ca.id = ag.campaign_id
					WHERE
						us3.id = ? AND
						us3.partner_id = ?
					GROUP BY
						us3.id
				) alias
				WHERE 1
				ORDER BY
					alias.username
			";
			$bindings[] = $user_id;
			$bindings[] = $user_id;
			$bindings[] = $partner_id;
		}
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}
	public function get_all_sales_people_with_partner()
	{
		$sql = "
			SELECT
				u.id,
				COALESCE(CONCAT(u.firstname, ' ', u.lastname), u.email) as username,
				pd.partner_name as partner,
				MIN(ag.earliest_city_record) AS earliest_impression,
				MAX(ag.latest_city_record) AS latest_impression
			FROM
				users u
				JOIN wl_partner_details pd
					ON u.partner_id = pd.id
				LEFT JOIN Advertisers ad
					ON u.id = ad.sales_person
				LEFT JOIN Campaigns ca
					ON ad.id = ca.business_id
				LEFT JOIN AdGroups ag
					ON ca.id = ag.campaign_id
			WHERE
				u.role = 'SALES'
			GROUP BY
				u.id
			ORDER BY
				u.username ASC";
		$result = $this->db->query($sql);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}

	public function get_timeseries_data_after_bulk($campaign_ids_array)
	{
		$campaign_timeseries_array=$this->get_timeseries_data_for_hover("html");
		$campaign_string = implode("," , $campaign_ids_array);

		$query = "SELECT
					adv.Name AS 'Advertiser Name',
					cpn.Name AS 'Campaign Name',
					cpn.id AS 'CampaignID'
				FROM
					Advertisers AS adv
					JOIN Campaigns AS cpn
						ON adv.id = cpn.business_id
				WHERE
					cpn.id IN (".$campaign_string.")
				ORDER BY
					cpn.id";

		$response = $this->db->query($query);
		$return_final_array=array();
		if($response->num_rows() > 0)
		{
			foreach ($response->result_array() as $row) {
				 $cid=$row['CampaignID'];
				 $timeseries_string=$campaign_timeseries_array['c-'.$cid];

				 $sub_array=array();
				 $sub_array['Advertiser Name']=$row['Advertiser Name'];
				 $sub_array['Campaign Name']=$row['Campaign Name'];
				 $sub_array['CampaignID']=$row['CampaignID'];
				 $sub_array['timeseries_string']=$timeseries_string;
				 $return_final_array[]=$sub_array;
			}

			return $return_final_array;
		}
		return array();
	}

	public function get_timeseries_data_for_hover($mode)
	{
		$sql="
			SELECT
		    	campaigns_id,
		    	DATE_FORMAT(series_date, '%Y-%m-%d') AS timeseries_string,
		    	DATE_FORMAT(DATE_SUB(series_date, INTERVAL 1 day), '%Y-%m-%d') AS previous_day_timeseries_string,
		    	format(cts.impressions,0) impressions
		     FROM
		    	campaigns_time_series cts,
		    	Campaigns c
		    WHERE cts.object_type_id=0 AND
		    	cts.campaigns_id=c.id AND
		    	c.ignore_for_healthcheck=0
		    ORDER by
	    		campaigns_id, series_date
			";


			if ($mode == "excel")
			{
				$sql="
					SELECT
				    	campaigns_id,
				    	DATE_FORMAT(series_date, '%Y-%m-%d') AS timeseries_string,
				    	DATE_FORMAT(DATE_SUB(series_date, INTERVAL 1 day), '%Y-%m-%d') AS previous_day_timeseries_string,
				    	format(cts.impressions,0) impressions
				     FROM
				    	campaigns_time_series cts
				    WHERE
				    	cts.object_type_id=0
				    ORDER by
			    		campaigns_id, series_date
					";
			}

		$result_array=array();
		$result_array_new=array();
		$query = $this->db->query($sql);

		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if (! (array_key_exists('c-'. $row['campaigns_id'], $result_array) == true))
				{
					$result_array['c-'. $row['campaigns_id']]=array();
				}
				$result_array['c-'. $row['campaigns_id']][]= array('campaigns_id'=>$row['campaigns_id'],'start'=>$row['timeseries_string'], 'impressions'=>$row['impressions'], 'end'=>$row['previous_day_timeseries_string']);
			}
			$today = date("Y-m-d");
			foreach ($result_array as $key => $value_array)
			{
				$campaign_string="";
				$total_impr=0;
				 for ($i=0; $i < count($value_array) -1; $i++)
				 {
				 	if ($value_array[$i]['impressions'] != "")
				 	{
				 		if ($mode == "html")
				 		{

				 			if ( strtotime($today) >= strtotime($value_array[$i]['start'])  && strtotime($today) <= strtotime($value_array[$i+1]['end']) )
				 			{
				 				$campaign_string.="<b>".$value_array[$i]['start'] . "<b> to </b>". $value_array[$i+1]['end'] . "&nbsp;&nbsp;&nbsp;&nbsp;". $value_array[$i]['impressions']." impressions</b><br>";
				 			}
				 			else
				 			{
				 				$campaign_string.=$value_array[$i]['start'] . "<b> to </b>". $value_array[$i+1]['end'] . "&nbsp;&nbsp;&nbsp;&nbsp;". $value_array[$i]['impressions']." impressions<br>";
				 			}
				 			$total_impr += intval(str_replace(',','', $value_array[$i]['impressions']));
				 		}
				 		else
				 			$campaign_string.=$value_array[$i]['start'] . " to ". $value_array[$i+1]['end'] . " ". $value_array[$i]['impressions']." impressions | ";
				 	}
				 }
				 if ($mode == "html")
				 {
				 	$result_array_new[$key]="Total impressions: ". number_format($total_impr) . "<br><br>" .$campaign_string;
				 }
				 else
				 {
					$result_array_new[$key]=$campaign_string;
				 }
			}

		}
		return $result_array_new;
	}

	public function get_timeseries_data_for_billing($start_date, $end_date, $mode, $cpm)
	{
		$sql_partner= "
			SELECT
				p.partner_name AS partnername,
				p.id partnerid,
				a.Name AS advertisername,
				a.id adid,
				c.Name AS c_name,
				c.id AS id
			FROM
				Campaigns AS c
				LEFT JOIN Advertisers AS a
					ON a.id = c.business_id
				LEFT JOIN users AS u
					ON a.`sales_person` = u.`id`
				LEFT JOIN wl_partner_details AS p
					ON u.`partner_id` = p.id
				LEFT JOIN AdGroups AS ag
					ON ag.campaign_id = c.id
				JOIN
					campaigns_time_series ccc ON ccc.campaigns_id=c.id AND
					ccc.series_date > '2015-01-01'
					AND ccc.object_type_id=0
				GROUP BY
					p.id,
					a.id,
					c.id";
		$query = $this->db->query($sql_partner);
		$campaign_details_array=array();
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$campaign_details_array['c-'.$row['id']]="<td>".$row['partnername']."</td><td>".$row['partnerid']."</td><td>".
				$row['advertisername']."</td><td>".$row['adid']."</td><td>".$row['c_name']."</td>";
			}
		}

		$sql="
			SELECT
		    	campaigns_id,
		    	DATE_FORMAT(series_date, '%Y-%m-%d') AS timeseries_string,
		    	DATE_FORMAT(DATE_SUB(series_date, INTERVAL 1 day), '%Y-%m-%d') AS previous_day_timeseries_string,
		    	cts.impressions impressions
		     FROM
		    	campaigns_time_series cts,
		    	Campaigns c
		    WHERE
		    	cts.campaigns_id=c.id AND
		    	cts.series_date > '2015-01-01'
		    	AND cts.object_type_id=0
		    ORDER by
	    		campaigns_id, series_date
			";

		$result_array=array();
		$result_array_new=array();
		$query = $this->db->query($sql);

		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if (! (array_key_exists('c-'. $row['campaigns_id'], $result_array) == true))
				{
					$result_array['c-'. $row['campaigns_id']]=array();
				}

				$result_array['c-'. $row['campaigns_id']][]= array('campaigns_id'=>$row['campaigns_id'],'start'=>$row['timeseries_string'], 'impressions'=>$row['impressions'], 'end'=>$row['previous_day_timeseries_string']);
			}
			$today = date("Y-m-d");
			$insert_temp_array=array();
			foreach ($result_array as $key => $value_array)
			{
				 for ($i=0; $i < count($value_array) -1; $i++)
				 {
				 	if ($value_array[$i]['impressions'] != "")
				 	{
		 				$complete_flag="Complete";

		 				if (strtotime($end_date) < strtotime($value_array[$i+1]['end']))
		 					$complete_flag="Incomplete";



		 				if (strtotime($value_array[$i+1]['end']) >= strtotime($start_date) && strtotime($value_array[$i]['start']) < strtotime($end_date) && $value_array[$i]['impressions'] > 0)
		 				{

		 					$actual_end=$value_array[$i+1]['end'];

			 				if (strtotime($end_date) < strtotime($value_array[$i+1]['end']))
			 					$actual_end=$end_date; //ltd end

			 				$main_start_date=$start_date; //mtd start
							if (strtotime($value_array[$i]['start']) > strtotime($start_date) )
							{
								$main_start_date=$value_array[$i]['start'];
							}
		 					$partner_info="<td></td><td></td><td></td><td></td><td></td>";

							$cycle_days=1 + ( (strtotime($value_array[$i+1]['end']) - strtotime($value_array[$i]['start'])  )  / (60*24*60)  ) ;

							$flight_perday_target_impr= $value_array[$i]['impressions'] / $cycle_days;
							if (array_key_exists('c-'.$value_array[$i]['campaigns_id'], $campaign_details_array) == "true")
								$partner_info=$campaign_details_array['c-'.$value_array[$i]['campaigns_id']];

							$main_str=$partner_info;//."<td>".$value_array[$i]['campaigns_id']."</td><td>".$value_array[$i]['start']."</td><td>".$value_array[$i+1]['end']."</td><td>".$value_array[$i]['impressions']."</td><td>".$complete_flag."</td>";
							$main_str = str_replace("'","",$main_str);
							$insert_temp_array[]="('".$value_array[$i]['campaigns_id']."','".$main_str.
							"','".$value_array[$i]['start']."','".$value_array[$i+1]['end']."','".$value_array[$i]['impressions']."','" .$flight_perday_target_impr . "','".$complete_flag."',".
								"'".$value_array[$i]['start']."','".$actual_end."','".$main_start_date."','".$actual_end."')";
		 		 		}
				 	}
				 }
			}

			$delete_str="truncate freq_revenue_calc_temp";
			$this->db->query($delete_str);

			$insert_str="insert into freq_revenue_calc_temp (cid, disp_str, flight_start, flight_end, flight_target_impr, flight_perday_target_impr, flight_status, ltd_start  , ltd_end  , mtd_start  , mtd_end  ) values ";
			$sql_arr = array();
			for ($i=0; $i < count($insert_temp_array); $i++ )
			{
				if ($i > 0 && (( $i%500 == 0) || ($i == count($insert_temp_array) -1 ) ) )
				{
					$sql_str = implode(",", $sql_arr);
					$sql_str = $insert_str. $sql_str;
					$this->db->query($sql_str);
					$sql_arr = array();
				}
				$sql_arr[] = $insert_temp_array[$i];
			}

			if ($mode == 1 || $mode == 2 || $mode == 4)
			{
				$mtd_calc="
					update freq_revenue_calc_temp upd,
						(
						SELECT
	 						a.campaign_id,
	 						f.mtd_start,
	 						sum(r.impressions) impressions
	 						FROM
	 							report_cached_adgroup_date r,
	 							AdGroups a ,
	 							freq_revenue_calc_temp f
	 						WHERE
	 						 	r.adgroup_id = a.id AND
						 		r.date >= f.mtd_start AND
						 		r.date <= f.mtd_end AND
						 		a.campaign_id =f.cid
						 		GROUP BY
						 			a.campaign_id,
						 			mtd_start
						 		)   inn
					 		SET upd.mtd_impr = inn.impressions
					 		WHERE
					 			upd.cid = inn.campaign_id AND
					 		 	upd.mtd_start=inn.mtd_start";
		    	$this->db->query($mtd_calc);

				$ltd_calc="
					update freq_revenue_calc_temp upd,
						(
						SELECT
	 						a.campaign_id,
	 						f.ltd_start,
	 						sum(r.impressions) impressions
	 						FROM
	 							report_cached_adgroup_date r,
	 							AdGroups a ,
	 							freq_revenue_calc_temp f
	 						WHERE
	 						 	r.adgroup_id = a.id AND
						 		r.date >= f.ltd_start AND
						 		r.date <= f.ltd_end AND
						 		a.campaign_id =f.cid
						 		GROUP BY
						 			a.campaign_id,
						 			ltd_start
						 		)   inn
					 		SET upd.ltd_impr = inn.impressions
					 		WHERE
					 			upd.cid = inn.campaign_id AND
					 		 	upd.ltd_start=inn.ltd_start";
				$this->db->query($ltd_calc);

				/////////////////// REPORTING MODULE
				$prev_ltd_query="
					UPDATE
				 		freq_revenue_calc_temp SET
				 		 	prev_ltd_billing = LEAST
		 		 			(
		 		 				ltd_impr - mtd_impr, flight_target_impr
		 		 			)";
				$this->db->query($prev_ltd_query);

				$mtd_query="
					UPDATE freq_revenue_calc_temp SET
					 	mtd_billing =
					CASE
					WHEN flight_status='Complete'
						THEN
							(LEAST(ltd_impr, flight_target_impr) -prev_ltd_billing )
						ELSE
							(LEAST((flight_target_impr - prev_ltd_billing) , mtd_impr ))
					END
					WHERE
						1 = 1;";
				$this->db->query($mtd_query);

				$performance_query="
					UPDATE freq_revenue_calc_temp SET
					 	performance_flag=
							(
							CASE WHEN
									(CASE WHEN
										flight_status='Complete'
									THEN
									 	(ltd_impr- flight_target_impr)
									ELSE '-' end) < 0
									THEN
										'underdelivery'
							ELSE
									(
									CASE WHEN
										flight_status='Complete'
									THEN
										(ltd_impr- flight_target_impr)
									ELSE '-' end
									)
							END
						)";
					$this->db->query($performance_query);

					// add the advertiser id and partner id in the freq_revenue_calc_temp table
					$partner_query="
					UPDATE freq_revenue_calc_temp a,
					(
						SELECT
							p.partner_name AS partnername,
							p.id partnerid,
							a.Name AS advertisername,
							a.id adid,
							c.Name AS c_name,
							c.id AS id
						FROM
							Campaigns AS c
						LEFT JOIN Advertisers AS a
							ON a.id = c.business_id
						LEFT JOIN users AS u
							ON a.`sales_person` = u.`id`
						LEFT JOIN wl_partner_details AS p
							ON u.`partner_id` = p.id
						LEFT JOIN AdGroups AS ag
							ON ag.campaign_id = c.id
						JOIN
							campaigns_time_series ccc ON ccc.campaigns_id=c.id AND
							ccc.series_date > '2015-01-01'
							AND ccc.object_type_id=0
						GROUP BY
							p.id,
							a.id,
							c.id
					)
					ptr_data
					SET
						a.aid = ptr_data.adid,
						a.pid = ptr_data.partnerid,
						a.adv_name=ptr_data.advertisername,
						a.partner_name=ptr_data.partnername,
						a.campaign_name=ptr_data.c_name
					WHERE
						a.cid = ptr_data.id
				";

				$this->db->query($partner_query);

				// add campaign_target_type to the freq_revenue_calc_temp table
				$target_type_query="
					 UPDATE freq_revenue_calc_temp a,
				       (
				       	SELECT c.id,
				               CASE a.target_type
				                 WHEN 'Pre-Roll' THEN 'P'
				                 WHEN 'RTG Pre-Roll' THEN 'P'
				                 ELSE 'D'
				               END AS tgt_type
				        FROM
				           	AdGroups a,
				            Campaigns c
				        WHERE
				        	c.id = a.campaign_id
				        GROUP BY
				         	c.id
				        )
					ptr_data
					SET
					    a.campaign_target_type = ptr_data.tgt_type
					WHERE
					  	a.cid = ptr_data.id
				";
				$this->db->query($target_type_query);

					// now find cpm
					// loop each flight, query each level and see if it has cpm or the ratecard flag is turned on. if no data found move up the level.
					// if cpm found, store, if ratecard flag found, get the ratecard cpm for flight_target_impr.
					// store in cpm or rate card column.
					// add a note column to show where cpm or rate card was found on which level

					$rate_sql="
						SELECT DISTINCT cid,
			                partner_id,
			                partner_name,
			                advertiser_id,
			                campaign_id,
			                cpm_display,
			                cpm_preroll,
			                rate_card_flag ,
			                path_length,
			                campaign_target_type,
			                min_impressions,
			                adv_name,
			                display_name,
			                sales_term,
							billing_address_1,
							billing_address_2,
							billing_city,
							billing_zip,
							billing_state
						FROM (
			                       SELECT fl.cid,
			                              f.partner_id,
			                              p.partner_name ,
			                              f.advertiser_id,
			                              f.campaign_id,
			                              cpm_display,
			                              cpm_preroll,
			                              rate_card_flag ,
			                              '-1' AS path_length,
			                              campaign_target_type,
			                              min_impressions,
			                              f.adv_name,
			                              f.display_name,
			                              sales_term,
											billing_address_1,
											billing_address_2,
											billing_city,
											billing_zip,
											billing_state
			                       FROM
			                       		freq_revenue_billing_cpm_ratecard f,
			                              wl_partner_details p,
			                              (
			                                     SELECT pid,
			                                            aid,
			                                            cid,
			                                            campaign_target_type
			                                     FROM   freq_revenue_calc_temp
			                                     WHERE  mtd_billing > 0 ) AS fl
			                       WHERE  ( (
			                                            partner_id=fl.pid
			                                     AND    advertiser_id=-1
			                                     AND    campaign_id=-1)
			                              OR     (
			                                            partner_id=fl.pid
			                                     AND    advertiser_id=fl.aid
			                                     AND    campaign_id=-1)
			                              OR     (
			                                            partner_id=fl.pid
			                                     AND    advertiser_id=fl.aid
			                                     AND    campaign_id=fl.cid) )
			                       AND    (
			                                     cpm_display >0
			                                            || cpm_preroll > 0
			                                            || rate_card_flag = 1)
			                       AND
			                       		f.partner_id=p.id
			                       UNION
			                       SELECT DISTINCT
			                       		fl.cid,
                                       f.partner_id,
                                       p.partner_name ,
                                       f.advertiser_id,
                                       f.campaign_id,
                                       cpm_display,
                                       cpm_preroll,
                                       rate_card_flag ,
                                       path_length,
                                       campaign_target_type,
			                           min_impressions,
										f.adv_name,
										f.display_name,
										f.sales_term,
										billing_address_1,
										billing_address_2,
										billing_city,
										billing_zip,
										billing_state
			                       FROM
			                       		freq_revenue_billing_cpm_ratecard f,
                                       wl_partner_hierarchy h ,
                                       wl_partner_details p,
                                       (
                                          SELECT pid,
                                                 aid,
                                                 cid,
                                                 campaign_target_type
                                          FROM   freq_revenue_calc_temp
                                          WHERE  mtd_billing > 0
                                        )
                                        AS fl
			                       WHERE
			                       partner_id =ancestor_id
			                       AND             f.partner_id=p.id
			                       AND             descendant_id =fl.pid
			                       AND             path_length > 0
			                       AND             advertiser_id=-1
			                       AND             campaign_id=-1
			                       AND             (
                                       cpm_display > 0
                                       || cpm_preroll > 0
                                       || rate_card_flag = 1)
										) AS main
						ORDER BY
							cid,
						    path_length
					";
					$rate_query = $this->db->query($rate_sql);
					$rate_cpm_array=array();
					if($rate_query->num_rows() > 0)
					{
						foreach ($rate_query->result_array() as $row)
						{
							$key="c-".$row['cid'];
							if (!array_key_exists($key, $rate_cpm_array))
								$rate_cpm_array[$key]=$row;
						}
					}

					$final_data_sql="
					SELECT
						campaign_target_type, disp_str, pid, aid, cid, flight_start, flight_end,
						flight_target_impr, flight_status, ltd_start, ltd_end, ltd_impr, mtd_start,
						mtd_end, mtd_impr , prev_ltd_billing, mtd_billing, performance_flag
					FROM
						freq_revenue_calc_temp
					WHERE mtd_billing > 0 ";
					$query_final_loop = $this->db->query($final_data_sql);

					if($query_final_loop->num_rows() > 0)
					{
						foreach ($query_final_loop->result_array() as $row)
						{
							$campaign_id=$row['cid'];

							if (array_key_exists("c-".$campaign_id, $rate_cpm_array))
							{
								$cpm_master_data_array=$rate_cpm_array["c-".$campaign_id];

								if ($cpm_master_data_array['rate_card_flag'] == 0)
								{
									$sql="
									UPDATE freq_revenue_calc_temp
										SET cpm=?,
										NOTES=?,
										min_impressions=?,
										customer_name=?,
										display_name=?,
										sales_term=?,
										billing_address_1=?,
										billing_address_2=?,
										billing_city=?,
										billing_zip=?,
										billing_state=?
									WHERE CID=?
									";

									$notes=$campaign_id . "-";
									if ($cpm_master_data_array['campaign_target_type'] == 'P')
										$notes.="Preroll campaign without Ratecard. CPM picked from ";
									else
										$notes.="Display campaign without Ratecard. CPM picked from ";
									$cust_name=$cpm_master_data_array['partner_name'];
									if ($cpm_master_data_array['campaign_id'] != "-1")
									{
										$cust_name=$cpm_master_data_array['adv_name'];
										$notes .= " Campaign level ";
									}
									else if ($cpm_master_data_array['advertiser_id'] != "-1")
									{
										$notes .= " Adv level ";
										$cust_name=$cpm_master_data_array['adv_name'];
									}
									else if ($cpm_master_data_array['partner_name'] != "")
										$notes .= " Partner: ". $cpm_master_data_array['partner_name'];

									$bindings=null;
									if ($cpm_master_data_array['campaign_target_type'] == 'P')
										$bindings=array($cpm_master_data_array['cpm_preroll'], $notes, $cpm_master_data_array['min_impressions'],
											$cust_name, $cpm_master_data_array['display_name'], $cpm_master_data_array['sales_term'], $cpm_master_data_array['billing_address_1'],
											$cpm_master_data_array['billing_address_2'], $cpm_master_data_array['billing_city'], $cpm_master_data_array['billing_zip'],
											$cpm_master_data_array['billing_state'], $campaign_id);
									else
										$bindings=array($cpm_master_data_array['cpm_display'], $notes, $cpm_master_data_array['min_impressions'], $cust_name,
											$cpm_master_data_array['display_name'], $cpm_master_data_array['sales_term'], $cpm_master_data_array['billing_address_1'],
											$cpm_master_data_array['billing_address_2'], $cpm_master_data_array['billing_city'], $cpm_master_data_array['billing_zip'],
											$cpm_master_data_array['billing_state'], $campaign_id);

									$query = $this->db->query($sql, $bindings);
								}
								else if ($cpm_master_data_array['rate_card_flag'] == 1)
								{
									$sql="
									UPDATE freq_revenue_calc_temp
										SET cpm=?,
										NOTES=?,
										min_impressions=?,
										customer_name=?,
										display_name=?,
										sales_term=?,
										billing_address_1=?,
										billing_address_2=?,
										billing_city=?,
										billing_zip=?,
										billing_state=?
									WHERE CID=?
									";

									$ratecard_cpm=0;
									$placeholder_retargeting_cpm=0;
									$placeholder_discount=0;
									$summary_string=0;
									$base_dollars_per_term=0;
									$charged_dollars_per_term=0;
									$retargeting_price_per_term=0;

									$this->mpq_v2_model->get_ad_plan_data_from_impressions($ratecard_cpm,  $placeholder_retargeting_cpm,
										$placeholder_discount,$summary_string,$base_dollars_per_term,$charged_dollars_per_term,$retargeting_price_per_term,
										$row['flight_target_impr'],'monthly',1,0,0,0,0);
									$notes=$campaign_id . "-";
									$ratecard_cpm=($ratecard_cpm*0.9*2/3);
									$notes=$campaign_id . "- Ratecard for target impressions " .$row['flight_target_impr']. " is " . $ratecard_cpm  ;
									$bindings=array($ratecard_cpm, $notes, $cpm_master_data_array['min_impressions'], $cpm_master_data_array['partner_name'],
									$cpm_master_data_array['display_name'], $cpm_master_data_array['sales_term'], $cpm_master_data_array['billing_address_1'],
									$cpm_master_data_array['billing_address_2'], $cpm_master_data_array['billing_city'], $cpm_master_data_array['billing_zip'],
									$cpm_master_data_array['billing_state'], $campaign_id);
									$query = $this->db->query($sql, $bindings);
								}
							}
							else
							{
								$sql="
								UPDATE freq_revenue_calc_temp
									SET cpm=?,
									NOTES=?
								WHERE CID=?
								";
								$bindings=array("-9999", "ERROR: PLEASE CHECK CAMPAIGN/PARTNER SETUP!! cid: ". $campaign_id, $campaign_id);
								$query = $this->db->query($sql, $bindings);
							}
						}
					}

					// CPM TO REVENUE
					$update_sql="
						UPDATE freq_revenue_calc_temp SET
						 	final_amount=mtd_billing * cpm / 1000,
						 	due_date = CASE sales_term
						 		WHEN 'Net 60' THEN LAST_DAY(DATE_ADD(now(), INTERVAL 31 DAY))
						 		ELSE LAST_DAY(now())
						 	END,
						 	mtd_billing_final=mtd_billing
						 WHERE
						  	cpm > 0;";
					$this->db->query($update_sql);

					//MINIMUMS FOR CPM
					$update_sql="
						UPDATE
							freq_revenue_calc_temp SET
							 notes=CONCAT('Minimums applied for CPM; Min impressions: ', min_impressions, '; ', notes),
							final_amount=min_impressions * cpm / 1000,
							mtd_billing_final=min_impressions
						WHERE
						  	flight_target_impr/((dateDIFF(flight_end, flight_start)+1)/30) < min_impressions
						  	AND cpm > 0 AND campaign_target_type = 'D' ";

					$this->db->query($update_sql);

					// OVERWRITE MINIMUMS FOR INCOMPLETE FLIGHTS.
					$update_sql="
						UPDATE
							freq_revenue_calc_temp SET
							notes=CONCAT('Removing Minimums; first month for an incomplete flight ', notes),
							final_amount=0,
							mtd_billing_final=0
						WHERE
						 	flight_start > ?
							AND flight_status='Incomplete'
							AND flight_target_impr/((dateDIFF(flight_end, flight_start)+1)/30) < min_impressions
							AND cpm > 0
							AND campaign_target_type = 'D'
							";

					$update_sql1="
						UPDATE
							freq_revenue_calc_temp SET
							notes=CONCAT('Removing Minimums; first month for an incomplete flight ', notes),
							final_amount=0,
							mtd_billing_final=0
						WHERE
						 	flight_start > ?
							AND flight_status='Incomplete'
							AND flight_target_impr/((dateDIFF(flight_end, flight_start)+1)/30) < min_impressions
							AND cpm > 0
							AND campaign_target_type = 'D'
							AND cid NOT IN (
									SELECT cid
									FROM
										(
											SELECT cid
											FROM
												freq_revenue_calc_temp
											GROUP BY
												cid
											HAVING
												COUNT(*) >1
										) AS c
								) ";

					$this->db->query($update_sql, $start_date);

					// cpm end
					$fetch_sql="
					SELECT
						disp_str, cid, flight_start, flight_end, flight_target_impr, flight_status, ltd_start,
						ltd_end, ltd_impr, mtd_start, mtd_end, mtd_impr , prev_ltd_billing, mtd_billing, performance_flag,
						cpm, campaign_target_type, notes, final_amount
					FROM
						freq_revenue_calc_temp";

					if ($mode == "2")
						 $fetch_sql.=" WHERE mtd_billing > 0 ";

					if ($mode == "4")
					{
						$fetch_sql="
							SELECT
								adv_name,
								CASE WHEN notes like '%Removing Minimums%' THEN
									CONCAT(campaign_name, ' (', CAST(FORMAT( ROUND(flight_target_impr/1000), 0) AS CHAR CHARACTER SET utf8), 'k: ' , flight_start , ' to ', flight_end , '): Min to be billed next month')
								ELSE
									CONCAT(campaign_name, ' (', CAST(FORMAT(ROUND(flight_target_impr/1000), 0) AS CHAR CHARACTER SET utf8), 'k: ' , flight_start , ' to ', flight_end , ')')
								END
								AS campaign_name,
								FORMAT(mtd_billing, 0) AS mtd_billing,
								FORMAT(mtd_impr, 0) AS mtd_impr,
								CONCAT(
									adv_name, ', ',
									CASE WHEN notes like '%Removing Minimums%' THEN
										CONCAT(campaign_name, ' (', CAST(ROUND(mtd_billing*.001) AS CHAR CHARACTER SET utf8) , 'k: ' , flight_start , ' to ', flight_end , '): Min to be billed next month')
									ELSE
										CONCAT(campaign_name, ' (', CAST(ROUND(mtd_billing*.001) AS CHAR CHARACTER SET utf8), 'k: ' , flight_start , ' to ', flight_end , ')')
									END	,
									', ', 'Target: ' , CAST(FORMAT(mtd_billing, 0) AS CHAR CHARACTER SET utf8) , ', Delivered: ', CAST(FORMAT(mtd_impr, 0) AS CHAR CHARACTER SET utf8)
								) AS line_desc,
								FORMAT(mtd_billing, 0) AS mtd_billing,
								mtd_billing_final,
								cpm,
								customer_name,
								display_name,
								notes,
								round(mtd_billing_final*0.001 , 9) AS line_qty,
								cpm * mtd_billing_final * 0.001 AS final_amount,
								due_date,
								sales_term,
								billing_address_1,
								billing_address_2,
								billing_city,
								billing_zip,
								billing_state,
								display_name
							FROM
								freq_revenue_calc_temp WHERE mtd_billing > 0
							ORDER BY customer_name, adv_name, campaign_name
						" ;
					}

					$result = $this->db->query($fetch_sql);
					$result_array_new['table_data']=$result->result_array();

				}

				if ($mode == 3)
				{
					$graph_date=$start_date;
					// graph start
					$days_array=array();
					$data_array=array();
					$total_impr=0;
					for ($i=0 ; $i < 1000; $i++)
					{
						if (strtotime($graph_date) > strtotime($end_date))
							break;

						$fetch_sql="
						SELECT
							SUM(f.flight_perday_target_impr) impr
						FROM
							freq_revenue_calc_temp f, Campaigns c
						WHERE
							f.flight_start <= '".$graph_date."' AND
							f.flight_end >= '".$graph_date."' AND
							c.id = f.cid AND
							(c.pause_date > '".$graph_date."' OR
								c.pause_date is null
							) AND
							c.name not like 'zz_%' AND
							c.name not like 'ZZ_%'
						";

						$result_graph = $this->db->query($fetch_sql);

						if($result_graph->num_rows() > 0)
						{
							foreach ($result_graph->result_array() as $row)
							{
								$days_array[]=$graph_date;
								$data_array[]=$row['impr'];
								$total_impr+=$row['impr'];
								break;
							}
						}

						$graph_date = date("Y-m-d", strtotime($graph_date. ' 1 day'));
					}
					$total_revenue = ($total_impr/1000) * $cpm;
					$total_impr=$total_impr/1000000;
					$total_revenue=$total_revenue/1000;
					$title=" Sum of Impressions: " . number_format($total_impr) . " million.        Revenue: \$".number_format($total_revenue) . "k";
					$result_array_new['days_array']=$days_array;
					$result_array_new['data_array']=$data_array;
					$result_array_new['graph_title']=$title;

				}
		}
		return $result_array_new;
	}

	public function fetch_cpm_grid($cpm_mode)
	{

		$sql_partner= "
			INSERT IGNORE INTO freq_revenue_billing_cpm_ratecard(partner_id)
			SELECT
				DISTINCT
				p.id partnerid
			FROM
				wl_partner_details AS p
			";
		$this->db->query($sql_partner);

		$sql_adv= "
			INSERT IGNORE INTO freq_revenue_billing_cpm_ratecard(partner_id, advertiser_id, adv_name)
			SELECT
				DISTINCT
				p.id,
				a.id,
				a.name
			FROM
				Campaigns AS c
				LEFT JOIN Advertisers AS a
					ON a.id = c.business_id
				LEFT JOIN users AS u
					ON a.`sales_person` = u.`id`
				LEFT JOIN wl_partner_details AS p
					ON u.`partner_id` = p.id
				LEFT JOIN AdGroups AS ag
					ON ag.campaign_id = c.id
				JOIN
					campaigns_time_series ccc ON ccc.campaigns_id=c.id AND
					ccc.series_date > '2015-01-01'
					AND ccc.object_type_id=0
				WHERE
					p.id IS NOT NULL AND
					a.id IS NOT NULL AND
					(p.id, a.id) NOT IN
					(
						SELECT
							partner_id, advertiser_id
						FROM
							freq_revenue_billing_cpm_ratecard
					)
				GROUP BY
					p.id,
					a.id
				";
		$this->db->query($sql_adv);

		$sql_campaign= "
			INSERT IGNORE INTO freq_revenue_billing_cpm_ratecard(partner_id, advertiser_id, campaign_id, adv_name)
			SELECT
				DISTINCT
				p.id ,
				a.id,
				c.id,
				a.name
			FROM
				Campaigns AS c
				  JOIN Advertisers AS a
					ON a.id = c.business_id
				  JOIN users AS u
					ON a.`sales_person` = u.`id`
				  JOIN wl_partner_details AS p
					ON u.`partner_id` = p.id
				  JOIN AdGroups AS ag
					ON ag.campaign_id = c.id
				JOIN
					campaigns_time_series ccc ON ccc.campaigns_id=c.id AND
					ccc.series_date > '2015-01-01' AND
					p.id IS NOT NULL AND
					a.id IS NOT NULL AND
					c.id IS NOT NULL AND
					(p.id, a.id, c.id) NOT IN
					(
						SELECT
							partner_id, advertiser_id, campaign_id
						FROM
							freq_revenue_billing_cpm_ratecard
					)
					AND ccc.object_type_id=0
				GROUP BY
					p.id,
					a.id,
					c.id";
		$this->db->query($sql_campaign);


		if ($cpm_mode == "p")
		{
			$sql_campaign= "
			SELECT DISTINCT
				r.id,
				r.partner_id AS p_id,
				p.partner_name AS p_name,
				r.cpm_display,
				r.cpm_preroll,
				r.rate_card_flag,
				r.min_impressions,
				display_name,
				sales_term,
				billing_address_1,
				billing_address_2,
				billing_city,
				billing_zip,
				billing_state
			FROM
				freq_revenue_billing_cpm_ratecard r,
				wl_partner_details p
			WHERE
				r.partner_id=p.id	AND
				r.advertiser_id= '-1' AND
				r.campaign_id = '-1'
			ORDER BY cpm_display DESC, rate_card_flag DESC, r.id
			";

			return $this->db->query($sql_campaign)->result_array();
		}
		else if ($cpm_mode == "a")
		{
			$sql_campaign= "
			SELECT DISTINCT
				r.id,
				p.id p_id,
				p.partner_name AS p_name,
				a.id a_id,
				a.Name AS a_name,
				r.cpm_display,
				r.cpm_preroll,
				r.rate_card_flag,
				r.min_impressions,
				display_name,
				sales_term,
				billing_address_1,
				billing_address_2,
				billing_city,
				billing_zip,
				billing_state
			FROM
				Advertisers AS a,
				wl_partner_details AS p,
				freq_revenue_billing_cpm_ratecard r
			WHERE
				a.id=r.advertiser_id AND
				p.id=r.partner_id AND
				r.campaign_id = '-1'
			ORDER BY
				cpm_display DESC, rate_card_flag DESC
			";
			return $this->db->query($sql_campaign)->result_array();
		}
		else if ($cpm_mode == "c")
		{
			$sql_partner= "
			SELECT DISTINCT
				r.id,
				p.id p_id,
				p.partner_name AS p_name,
				a.id a_id,
				a.Name AS a_name,
				c.id AS c_id,
				c.Name AS c_name,
				r.cpm_display,
				r.cpm_preroll,
				r.rate_card_flag,
				r.min_impressions,
				display_name,
				sales_term,
				billing_address_1,
				billing_address_2,
				billing_city,
				billing_zip,
				billing_state
			FROM
				Campaigns AS c,
				Advertisers AS a,
				wl_partner_details AS p,
				freq_revenue_billing_cpm_ratecard r
			WHERE
				c.id=r.campaign_id AND
				a.id=r.advertiser_id AND
				p.id=r.partner_id
			ORDER BY
				cpm_display DESC,
				rate_card_flag DESC,
				p.partner_name,
				a.name,
				c.name
				";
			return $this->db->query($sql_partner)->result_array();
		}

	}

	public function save_cpm_grid($cpm_data)
	{

		$array_data = explode("##" ,$cpm_data);
		for ($i=0 ; $i < count($array_data)-11 ; $i+=12 ) {
			$binding_array=array($array_data[$i], $array_data[$i+2], $array_data[$i+3], $array_data[$i+4],
				$array_data[$i+5], $array_data[$i+6], $array_data[$i+7], $array_data[$i+8], $array_data[$i+9], $array_data[$i+10], $array_data[$i+11],
				$array_data[$i+1]);
			$sql_partner= "
				UPDATE freq_revenue_billing_cpm_ratecard
				SET
					rate_card_flag=?,
					cpm_display=?,
					cpm_preroll=?,
					min_impressions=?,
					display_name=?,
					sales_term=?,
					billing_address_1=?,
					billing_address_2=?,
					billing_city=?,
					billing_zip=?,
					billing_state=?
				WHERE
					id=?;
				";
				$this->db->query($sql_partner, $binding_array);
		}
	}

	public function get_campaign_end_date()
	{
		$sql="
			SELECT campaigns_id,
		    	DATE_FORMAT(DATE_SUB(MAX(series_date), INTERVAL 1 day), '%Y-%m-%d') AS campaign_end_date
		    FROM
		    	campaigns_time_series cts,
		    	Campaigns c
		    WHERE
		    	cts.campaigns_id=c.id AND
		    	c.ignore_for_healthcheck=0
		    	AND cts.object_type_id=0
			GROUP BY
				cts.campaigns_id";

		$result_array=array();
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$result_array['c-'. $row['campaigns_id']]=$row['campaign_end_date'];
			}
		}
		return $result_array;
	}

	public function get_campaign_notes_data()
	{
		$sql="
			SELECT id,
				notes_text,
				created_date,
				username,
				notes_type,
				is_important_flag
				FROM
				(
					SELECT
						c.id,
						notes.notes_text AS notes_text,
						notes.created_date AS created_date,
						notes.username AS username,
						'C' AS notes_type,
						notes.is_important_flag
					FROM
						notes,
						Campaigns c
					WHERE
						notes.object_id=c.id AND
						notes.object_type_id =1 AND
						c.ignore_for_healthcheck=0
					UNION ALL
					SELECT
						c.id,
						notes.notes_text AS notes_text,
						notes.created_date AS created_date,
						notes.username AS username,
						'A',
						notes.is_important_flag
					FROM
						notes,
						Campaigns c,
						Advertisers a
					WHERE
						notes.object_id=a.id AND
						a.id=c.business_id AND
						notes.object_type_id =2 AND
						c.ignore_for_healthcheck=0
				) AS sum
				ORDER BY
					id,
					notes_type desc,
					sum.created_date DESC";

		$result_array=array();
		$query = $this->db->query($sql);
		$previous_cid="-1";
		$previous_type="-1";
		$table_header="<div><table width='100%' height='350px' style='font-size:10.5px' border=1 cellpadding=0 cellspacing=0><tr><th>Code</th><th>User</th><th>Date</th><th>Time</th><th width='330px'>Flight</th><th>IO</th><th>BI</th><th>RFP</th><th>URL</th><th>Ad ID</th><th>Geo</th><th>Pop</th><th width='270px'>Demo</th><th width='300px'>Context</th><th>Bdgt $</th><th>Bdgt Imp</th><th>Cal</th><th width='330px'>Note</th></tr>";
		$table_header_imp="<div><table width='100%' style='font-size:10.5px' border=1 cellpadding=0 cellspacing=0><tr><th></th><th>User</th><th>Date</th><th>Account</th><th width='200px'>Tracking Tags</th><th width='200px'>Notes</th></tr>";

		$table_footer="</table></div>";
		$cid = "";
		if($query->num_rows() > 0)
		{
			$campaign_string=$table_header;
			$campaign_note_counter=0;
			foreach ($query->result_array() as $row)
			{
				$cid = $row['id'];
				$notes_type=$row['notes_type'];
				if ($previous_cid != "-1" && $previous_cid != $cid) // now is the time to create a string for a campiang and put in the c- array
				{
					$result_array['c-'. $previous_cid]=$campaign_string.$table_footer;
					$campaign_string=$table_header;// reset the campaign string
					$campaign_note_counter=0;
				}
				$notes_text=$row['notes_text'];
				$created_date=$row['created_date'];
				//remove seconds from created date and format date
				$created_date = date("m-d-Y H:i", strtotime($created_date));
				$created_date_arr = explode(" ", $created_date);
				$created_date_val = $created_date_arr[0];
				$created_time = $created_date_arr[1];
				$username=$row['username'];
				//split username to show short name on popup
				$pos = strpos($username, '@');
				    if ($pos === false)
				    {
					$uname_notes =  $username;
				    }
				    else
				    {
					$username = (substr($username, 0, $pos));
					$pos2 = strpos($username, '.');
					if ($pos2 === false)
					{
					    $uname_notes =  $username;
					}
					else
					{
					    $username_dot = explode(".",$username);
					    $uname_notes = substr($username_dot[0],0,1).".".$username_dot[1];
					}
				    }
				$is_important_flag=$row['is_important_flag'];

				$imp_class="";
                if ($is_important_flag == '1')
                {
                   $imp_class="style='background-color:#03FC03'";
                }
                if ($previous_type != "-1" && $previous_type != $notes_type)
                {
		    if($notes_type == 'A')
		    {
			$campaign_string .= "<tr ".$imp_class."><td colspan=19 style='background-color:#000; color:#FFF'><b>Advertiser Note</b></td></tr>";
		    }else
		    {
			$campaign_string .= "<tr ".$imp_class."><td colspan=19 style='background-color:#000;'></td></tr>";
		    }
		}
		$sub_array=explode("^^\n" , $notes_text);
		$code_val = explode("::" , $sub_array[0]);
		$code_value = "";
		if (count($code_val) > 1)
		{
		    $code_value = $code_val[1];
		}
		if($notes_type == 'C')
		{
		    $campaign_string .= "<tr ".$imp_class."><td>". $code_value . "</td><td>". $uname_notes . "</td><td>"  . $created_date_val . "</td><td>"  . $created_time . "</td>";
		}
		else if($notes_type == 'A')
		{
		    $campaign_string .= "<tr ".$imp_class."><td></td><td>". $uname_notes . "</td><td>"  . $created_date_val . "</td><td>"  . $created_time . "</td><td colspan='4'>". $code_value . "</td>";
		}
		$sub_counter=0;
			$code_counter=0;
				foreach ($sub_array as $sub_row)
				{
					if ($code_counter++ == 0) continue;
					if ($notes_type == 'C' && $sub_counter > 13)
					{
						break;
					}
					else if ($notes_type == 'A' && $sub_counter > 1)
					{
						break;
					}

					$sub_internal_array=explode("::" , $sub_row);
					$cell_value="";
					if (count($sub_internal_array) > 1)
					{
							$notes_str=  $sub_internal_array[1];
							$cell_value=$notes_str;
					}
			if ($notes_type=='A')
	                {
	                	$column_style=" colspan=5 ";
	                	if (strlen($cell_value) > 400)
				{
					$cell_value=substr($cell_value, 0, 400)."...";
				}
	                }
	                else if ($notes_type=='C' && ($sub_counter == 1 || $sub_counter == 13))
	                {
	                	$column_style=" style='max-width:450px;word-wrap:break-word;' ";
	                	if (strlen($cell_value) > 400)
				{
				//	$cell_value=substr($cell_value, 0, 400)."...";
				}
	                }
			else if ($notes_type=='C' && ($sub_counter == 6))
	                {
	                	$column_style=" style='max-width:450px;word-wrap:break-word;' ";
	                	if (strlen($cell_value) > 75)
				{
					$cell_value=substr($cell_value, 0, 75)."...";
				}
	                }
	                else
	                {
	                	$column_style=" style='max-width:75px;word-wrap:break-word;' ";
	                }

					$cell_value=str_replace("'", "", $cell_value);

					$campaign_string.="<td $column_style >".$cell_value."</td>";
					$sub_counter++;
				}
				$campaign_string.="</tr>";

				$previous_cid=$cid;
				$previous_type=$notes_type;
				$campaign_note_counter++;
			}
			$campaign_string = preg_replace( '/[^[:print:]]/', '',$campaign_string);//remova all nonprintable chars
			$result_array['c-'. $cid]=$campaign_string.$table_footer;
		}
		return $result_array;
	}

	public function get_action_date_data($cid)
	{
		$sql="
		    SELECT
		    	campaign_id,
		    	MIN(series_date) AS series_date
		    FROM
		    (
				SELECT
			    	campaign_id,
			    	sec.series_date series_date
			    FROM
			    	(
			    		SELECT
			 				main.campaigns_id AS campaign_id,
			 				MAX(series_date) AS cycle_start_date,
			 				min_date.end_date AS end_date
			 			FROM
			 				campaigns_time_series main
			 			LEFT JOIN
		 					(
		 						SELECT
		        					campaigns_id,
		        					MIN(series_date) end_date
		        				FROM
		 							campaigns_time_series
		 						WHERE
		 							series_date >= DATE_SUB(NOW(), INTERVAL 3 day)
		 							AND object_type_id=0
		 						GROUP BY
		 							campaigns_id
		 					) min_date ON
							min_date.campaigns_id = main.campaigns_id
			     		WHERE
		     				series_date <= DATE_SUB(NOW(), INTERVAL 3 day) AND
		     				min_date.end_date IS NOT NULL
		     				AND main.object_type_id=0
		     			GROUP BY
		  					main.campaigns_id
		        	) AS main,
		        	campaigns_time_series first,
		        	campaigns_time_series sec
			    WHERE
			    	main.campaign_id=first.campaigns_id AND
			    	main.campaign_id=sec.campaigns_id AND
			    	main.cycle_start_date = first.series_date AND
			    	sec.series_Date=main.end_date AND
			    	sec.impressions > 0 AND
			    	sec.impressions != first.impressions AND
			    	sec.action_flag IS NULL
			    	AND first.object_type_id=0
			    	AND sec.object_type_id=0
			    UNION
			    SELECT
			    	campaign_id,
			    	sec.series_date series_date
			    FROM
			    	(
			    		SELECT
			 				main.campaigns_id AS campaign_id,
			 				MAX(series_date) AS cycle_start_date,
			 				min_date.end_date AS end_date
			 			FROM
			 				campaigns_time_series main
			 			LEFT JOIN
		 					(
		 						SELECT
		        					campaigns_id,
		        					MIN(series_date) end_date
		        				FROM
		 							campaigns_time_series
		 						WHERE
		 							series_date >= DATE_SUB(NOW(), INTERVAL 2 day)
		 							AND object_type_id=0
		 						GROUP BY
		 							campaigns_id
		 					) min_date ON
							min_date.campaigns_id = main.campaigns_id
			     		WHERE
		     				series_date <= DATE_SUB(NOW(), INTERVAL 2 day) AND
		     				min_date.end_date IS NOT NULL
		     				AND main.object_type_id=0
		     			GROUP BY
		  					main.campaigns_id
		        	) AS main,
		        	campaigns_time_series first,
		        	campaigns_time_series sec
			    WHERE
			    	main.campaign_id=first.campaigns_id AND
			    	main.campaign_id=sec.campaigns_id AND
			    	main.cycle_start_date = first.series_date AND
			    	sec.series_Date=main.end_date AND
			    	sec.impressions > 0 AND
			    	sec.impressions != first.impressions AND
			    	sec.action_flag IS NULL
			    	AND first.object_type_id=0
			    	AND sec.object_type_id=0
			    UNION
				SELECT
			    	campaign_id,
			    	sec.series_date series_date
			    FROM
			    	(
			    		SELECT
			 				main.campaigns_id AS campaign_id,
			 				MAX(series_date) AS cycle_start_date,
			 				min_date.end_date AS end_date
			 			FROM
			 				campaigns_time_series main
			 			LEFT JOIN
		 					(
		 						SELECT
		        					campaigns_id,
		        					MIN(series_date) end_date
		        				FROM
		 							campaigns_time_series
		 						WHERE
		 							series_date >= DATE_SUB(NOW(), INTERVAL 1 day)
		 							AND object_type_id=0
		 						GROUP BY
		 							campaigns_id
		 					) min_date ON
							min_date.campaigns_id = main.campaigns_id
			     		WHERE
		     				series_date <= DATE_SUB(NOW(), INTERVAL 1 day) AND
		     				min_date.end_date IS NOT NULL
		     				AND main.object_type_id=0
		     			GROUP BY
		  					main.campaigns_id
		        	) AS main,
		        	campaigns_time_series first,
		        	campaigns_time_series sec
			    WHERE
			    	main.campaign_id=first.campaigns_id AND
			    	main.campaign_id=sec.campaigns_id AND
			    	main.cycle_start_date = first.series_date AND
			    	sec.series_Date=main.end_date AND
			    	sec.impressions > 0 AND
			    	sec.impressions != first.impressions AND
			    	sec.action_flag IS NULL
			    	AND first.object_type_id=0
			    	AND sec.object_type_id=0
				UNION
				SELECT
			    	campaign_id,
			    	sec.series_date series_date
			    FROM
			    	(
			    		SELECT
			 				main.campaigns_id AS campaign_id,
			 				MAX(series_date) AS cycle_start_date,
			 				min_date.end_date AS end_date
			 			FROM
			 				campaigns_time_series main
			 			LEFT JOIN
		 					(
		 						SELECT
		        					campaigns_id,
		        					MIN(series_date) end_date
		        				FROM
		 							campaigns_time_series
		 						WHERE
		 							series_date >= DATE_SUB(NOW(), INTERVAL 0 day)
		 							AND object_type_id=0
		 						GROUP BY
		 							campaigns_id
		 					) min_date ON
							min_date.campaigns_id = main.campaigns_id
			     		WHERE
		     				series_date <= DATE_SUB(NOW(), INTERVAL 0 day) AND
		     				min_date.end_date IS NOT NULL
		     				AND main.object_type_id=0
		     			GROUP BY
		  					main.campaigns_id
		        	) AS main,
		        	campaigns_time_series first,
		        	campaigns_time_series sec
			    WHERE
			    	main.campaign_id=first.campaigns_id AND
			    	main.campaign_id=sec.campaigns_id AND
			    	main.cycle_start_date = first.series_date AND
			    	sec.series_Date=main.end_date AND
			    	sec.impressions > 0 AND
			    	sec.impressions != first.impressions AND
			    	sec.action_flag IS NULL
			    	AND first.object_type_id=0
			    	AND sec.object_type_id=0
		    ) AS bigr
	    	";

		 if ($cid != null)
		 {
		 	$sql .= " WHERE campaign_id=".$cid;
		 }
		 $sql .= " GROUP BY
	    		 	bigr.campaign_id";

		$result_array=array();
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$result_array['c-'. $row['campaign_id']]=$row['series_date'];
			}
		}
		 if ($cid != null)
		 {
		 	if (array_key_exists('c-'. $cid, $result_array))
		 		return $result_array['c-'. $cid];
		 	else
		 		return null;
		 }
		 else
			return $result_array;
	}

	public function next_flight_data($report_date)
	{
		$sql="
			SELECT
		    	campaigns_id,
		    	DATE_FORMAT(series_date, '%Y-%m-%d') AS timeseries_string,
		    	DATE_FORMAT(DATE_SUB(series_date, INTERVAL 1 day), '%Y-%m-%d') AS previous_day_timeseries_string,
		    	cts.impressions impressions
		     FROM
		    	campaigns_time_series cts,
		    	Campaigns c
		    WHERE
		    	cts.campaigns_id=c.id AND
		    	c.ignore_for_healthcheck=0
		    	AND cts.object_type_id=0
		    ORDER by
	    		campaigns_id, series_date
			";

		$result_array=array();
		$result_array_new=array();
		$query = $this->db->query($sql);

		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if (! (array_key_exists('c-'. $row['campaigns_id'], $result_array) == true))
				{
					$result_array['c-'. $row['campaigns_id']]=array();
				}
				$result_array['c-'. $row['campaigns_id']][]= array('campaigns_id'=>$row['campaigns_id'],'start'=>$row['timeseries_string'], 'impressions'=>$row['impressions'], 'end'=>$row['previous_day_timeseries_string']);
			}

			$today = $report_date;
			foreach ($result_array as $key => $value_array)
			{
				$c_arr=array();
				 for ($i=0; $i < count($value_array) -1; $i++)
				 {
				 	if ($value_array[$i]['impressions'] != "" && strtotime($today) < strtotime($value_array[$i]['start']))
		 			{

						$c_arr['next_flight_days']=1+((strtotime($value_array[$i+1]['end']) - strtotime($value_array[$i]['start']))/(60*60*24));
		 				$c_arr['next_flight_impr']=$value_array[$i]['impressions'];
		 				break;
		 			}
				 }
				 $result_array_new[$key]=$c_arr;
			}

		}
		return $result_array_new;
	}

	public function update_action_date_flag($campaign_id, $series_date)
	{
		$sql="
				UPDATE
					campaigns_time_series
				SET
					action_flag=1
				WHERE
					series_date=?
					AND object_type_id=0
					AND campaigns_id=?";

		$query = $this->db->query($sql, array($series_date, $campaign_id));
		return $query;
	}

	public function check_action_date_flag($campaign_id)
	{
		$sql_action="
				Select
					campaigns_id
				FROM
					campaigns_time_series
				WHERE
					action_flag=1
					AND object_type_id=0
					AND campaigns_id=?
				LIMIT	0,1";

		$query = $this->db->query($sql_action, $campaign_id);
		if($query->num_rows() > 0)
		{
			$return_check['action_check'] = true;
		}
		else
		{
			$return_check['action_check'] = false;
		}
		return $return_check;
	}

	public function check_pending_date_flag($campaign_id, $report_date)
	{
		$sql_action="
				Select
					ttd_daily_modify
				FROM
					Campaigns
				WHERE
					id=?
				LIMIT	0,1";

		$query = $this->db->query($sql_action, $campaign_id);
		$result_pending = $query->result_array();
		$actual_reset_date = date("Y-m-d", strtotime($report_date. ' - 1 day'));

		if($query->num_rows() > 0)
		{			
			$report_date_datetime = datetime::createfromformat('Y-m-d H:i:s', $actual_reset_date." 00:00:00", new DateTimeZone('UTC'));
			$daily_modify_datetime = datetime::createfromformat('Y-m-d H:i:s', $query->row()->ttd_daily_modify, new DateTimeZone('UTC'));

			if($report_date_datetime <= $daily_modify_datetime)
			{
				$return_check['ttd_date_pending_flag'] = true;
			}
			else
			{
				$return_check['ttd_date_pending_flag'] = false;
			}
		}
		else
		{
			$return_check['ttd_date_pending_flag'] = false;
		}		
		return $return_check;
	}

	public function get_campaigns_main_v2_data($role, $is_group_super, $user_id, $report_date, $tag_where_clause, $tag_binding_array, $campaigns_main_ops_referer_flag = false)
	{
		$engagements = array();
		$campaigns = array();
		$binding_array = array();
		$third_party_ids = array();
		$shared_advertiser_id_large = $this->config->item('demo_advertiser_big');
		$shared_advertiser_id_medium = $this->config->item('demo_advertiser_medium');
		$shared_advertiser_id_small = $this->config->item('demo_advertiser_small');
		$disable_campaign_for_non_demo_user_sql = "AND cpn.business_id NOT IN ($shared_advertiser_id_large,$shared_advertiser_id_medium,$shared_advertiser_id_small)";
		$check_for_sales_user = $this->session->userdata('is_demo_partner') ? '' : $disable_campaign_for_non_demo_user_sql;

		$skip_o_o_adgroup_sql = "";
		if ($campaigns_main_ops_referer_flag == true)
		{
			$skip_o_o_adgroup_sql = " AND (adg.subproduct_type_id != '4' OR adg.subproduct_type_id IS NULL) ";
		}
		
		if($role == 'sales')
		{
			$binding_array = array($report_date, $report_date, $report_date, $user_id, $user_id);

			$is_super_condition_sql = "";
			if($is_group_super == 1)
			{
				$binding_array[] = $report_date;
				$binding_array[] = $report_date;
				$binding_array[] = $report_date;
				$binding_array[] = $user_id;
				$binding_array[] = $user_id;
				$is_super_condition_sql = "
					UNION
					SELECT
						cpn.id AS id,
						adg.IsRetargeting as is_retargeting,
						COALESCE(pd.partner_name, pd2.partner_name) as partner,
						adv.Name AS advertiser,
						cpn.Name AS campaign,
						cpn.is_reminder AS is_reminder,
						COALESCE(CONCAT(us3.firstname, ' ', us3.lastname), us3.email) as sales_person,
						cts1.impressions/1000  AS target_impressions,
						cts1.cycle_end_date AS cycle_end_date,
						IFNULL(cts1.cycle_end_date_flag, '') AS cycle_end_date_flag,
						cpn.is_month_end_type AS is_month_end_type,
						cpn.is_monthly_target AS is_monthly_target,
						cpn.term_type AS term_type,
						cpn.LandingPage AS landing_page,
						cpn.ttd_daily_modify AS ttd_daily_modify,
						adg.earliest_city_record AS adg_raw_start_date,
						COALESCE(adg.cached_city_record_impression_sum, 0) AS adg_raw_total_impressions,
						COALESCE(adg.cached_city_record_click_sum, 0) AS adg_raw_total_clicks,
						COALESCE(adg.cached_city_record_last_month_impression_sum, 0) AS adg_raw_last_month_impressions,
						0 AS has_adsets,
						IF(adg.vl_id IS NOT NULL AND adg.source = 'TD', TRUE, FALSE) AS has_adgroups,
						0 AS has_tags,
						DATEDIFF(DATE_SUB(cts1.cycle_end_date, INTERVAL -1 DAY), ?) AS days_left,
						NULL AS pc_adgroup_id,
						cts1.cycle_start_date AS cycle_start_date
					FROM
						users us
						LEFT JOIN wl_partner_owners po
							ON us.id = po.user_id
						JOIN wl_partner_hierarchy ph
							ON  (us.partner_id = ph.ancestor_id)
						JOIN wl_partner_details pd
							ON ph.descendant_id = pd.id
						JOIN users us2
							ON pd.id = us2.partner_id
						RIGHT JOIN Advertisers adv
							ON (us2.id = adv.sales_person)
						JOIN Campaigns cpn
							ON adv.id = cpn.business_id
						JOIN AdGroups adg
							ON cpn.id = adg.campaign_id
						JOIN io_campaign_product_cpm campaign_cpm
							ON cpn.id = campaign_cpm.campaign_id
							AND adg.subproduct_type_id = campaign_cpm.subproduct_type_id
						JOIN users AS us3
							ON adv.sales_person = us3.id
						JOIN wl_partner_details AS pd2
							ON us3.partner_id = pd2.id
						LEFT JOIN (
			       			SELECT
								inner_data.campaign_id 'campaigns_id',
								CASE cts.impressions
									WHEN
										0
									THEN
										'Paused'
									ELSE
										'Active'
									END
									 	cycle_end_date_flag,
								inner_data.end_date cycle_end_date,
								inner_data.cycle_start_date 'cycle_start_date',
								cts.impressions 'impressions'
							FROM
								(
									SELECT
										main.campaigns_id AS campaign_id,
										MAX(series_date) AS cycle_start_date,
										min_date.end_date AS end_date
									FROM
					       				campaigns_time_series main
					       				LEFT JOIN
											(
												SELECT
													campaigns_id,
													DATE_SUB(MIN(series_date), INTERVAL 1 DAY) end_date
												FROM
					       							campaigns_time_series
												WHERE
													series_date >= DATE_SUB(?, INTERVAL -1 DAY)
													AND object_type_id=0
												GROUP BY
													campaigns_id
											)
											min_date ON
												min_date.campaigns_id = main.campaigns_id
									WHERE
										series_date <= ? AND
									 	min_date.end_date IS NOT NULL
									 	AND main.object_type_id=0
									GROUP BY
										main.campaigns_id
									) AS inner_data,
									campaigns_time_series cts
									WHERE
										inner_data.campaign_id =cts.campaigns_id AND
										inner_data.cycle_start_date=cts.series_date
										AND cts.object_type_id=0

			       		)
						cts1 ON
						cpn.id=cts1.campaigns_id
					WHERE
						cpn.ignore_for_healthcheck = 0 AND
						adg.Source != 'TDAV' AND
						(us.id = ? OR adv.sales_person = ?)
						".$check_for_sales_user."
					GROUP BY
						adg.id,
						cpn.id";
			}

			$sql = "
				SELECT
					adg_query.*,
					SUM(adg_raw_total_impressions) AS total_impressions,
					SUM(adg_raw_total_clicks) AS total_clicks,
					SUM(adg_raw_last_month_impressions) AS last_month_impressions,
					MIN(adg_raw_start_date) AS start_date
	 			FROM
				(
					SELECT
						cpn.id AS id,
						adg.IsRetargeting as is_retargeting,
						COALESCE(pd.partner_name, pd2.partner_name) as partner,
						adv.Name AS advertiser,
						cpn.Name AS campaign,
						cpn.is_reminder AS is_reminder,
						COALESCE(CONCAT(us3.firstname, ' ', us3.lastname), us3.email) as sales_person,
						cts1.impressions/1000  AS target_impressions,
						cts1.cycle_end_date AS cycle_end_date,
						IFNULL(cts1.cycle_end_date_flag, '') AS cycle_end_date_flag,
						cpn.is_month_end_type AS is_month_end_type,
						cpn.is_monthly_target AS is_monthly_target,
						cpn.term_type AS term_type,
						cpn.LandingPage AS landing_page,
						cpn.ttd_daily_modify AS ttd_daily_modify,
						adg.earliest_city_record AS adg_raw_start_date,
						COALESCE(adg.cached_city_record_impression_sum, 0) AS adg_raw_total_impressions,
						COALESCE(adg.cached_city_record_click_sum, 0) AS adg_raw_total_clicks,
						COALESCE(adg.cached_city_record_last_month_impression_sum, 0) AS adg_raw_last_month_impressions,
						0 AS has_adsets,
						IF(adg.vl_id IS NOT NULL AND adg.source = 'TD', TRUE, FALSE) AS has_adgroups,
						0 AS has_tags,
						DATEDIFF(DATE_SUB(cts1.cycle_end_date, INTERVAL -1 DAY), ?) AS days_left,
						NULL AS pc_adgroup_id,
						cts1.cycle_start_date AS cycle_start_date
					FROM
						users us
						LEFT JOIN wl_partner_owners po
							ON us.id = po.user_id
						JOIN wl_partner_hierarchy ph
							ON  (po.partner_id = ph.ancestor_id)
						JOIN wl_partner_details pd
							ON ph.descendant_id = pd.id
						JOIN users us2
							ON pd.id = us2.partner_id
						RIGHT JOIN Advertisers adv
							ON (us2.id = adv.sales_person)
						JOIN Campaigns cpn
							ON adv.id = cpn.business_id
						JOIN AdGroups adg
							ON cpn.id = adg.campaign_id
						JOIN io_campaign_product_cpm campaign_cpm
							ON cpn.id = campaign_cpm.campaign_id
							AND adg.subproduct_type_id = campaign_cpm.subproduct_type_id
						JOIN users AS us3
							ON adv.sales_person = us3.id
						JOIN wl_partner_details AS pd2
							ON us3.partner_id = pd2.id
						LEFT JOIN (
			       			SELECT
								inner_data.campaign_id 'campaigns_id',
								CASE cts.impressions
									WHEN
										0
									THEN
										'Paused'
									ELSE
										'Active'
									END
									 	cycle_end_date_flag,
								inner_data.end_date cycle_end_date,
								inner_data.cycle_start_date 'cycle_start_date',
								cts.impressions 'impressions'
							FROM
								(
									SELECT
										main.campaigns_id AS campaign_id,
										MAX(series_date) AS cycle_start_date,
										min_date.end_date AS end_date
									FROM
					       				campaigns_time_series main
					       				LEFT JOIN
											(
												SELECT
													campaigns_id,
													DATE_SUB(MIN(series_date), INTERVAL 1 DAY) end_date
												FROM
					       							campaigns_time_series
												WHERE
													series_date >= DATE_SUB(?, INTERVAL -1 DAY)
													AND object_type_id=0
												GROUP BY
													campaigns_id
											)
											min_date ON
												min_date.campaigns_id = main.campaigns_id
									WHERE
										series_date <= ? AND
									 	min_date.end_date IS NOT NULL
									 	AND main.object_type_id=0
									GROUP BY
										main.campaigns_id
									) AS inner_data,
									campaigns_time_series cts
									WHERE
										inner_data.campaign_id =cts.campaigns_id AND
										inner_data.cycle_start_date=cts.series_date
										AND cts.object_type_id=0

			       		)
						cts1 ON
						cpn.id=cts1.campaigns_id
					WHERE
						cpn.ignore_for_healthcheck = 0 AND
						adg.Source != 'TDAV' AND
						(us.id = ? OR adv.sales_person = ?)
					GROUP BY
						adg.id,
						cpn.id
					".$is_super_condition_sql."
				) as adg_query
				GROUP BY
					is_retargeting,
					id
				ORDER BY
					advertiser,
					campaign";
		}
		else if($role == 'ops' || $role == 'admin')
		{
			$binding_array[] = $report_date;
			$binding_array[] = $report_date;
			$binding_array[] = $report_date;
			if($tag_where_clause !== "")
			{
				$tag_where_clause = " AND ".$tag_where_clause;
				$binding_array = array_merge($binding_array, $tag_binding_array);
			}


			$sql = "
				SELECT
					cpn.id AS id,
					adg.IsRetargeting as is_retargeting,
					pd.partner_name AS partner,
					adv.Name AS advertiser,
					cpn.Name AS campaign,
					cpn.is_reminder AS is_reminder,
					COALESCE(CONCAT(us.firstname, ' ', us.lastname), us.email) as sales_person,
					cpn.TargetImpressions AS target_impressions_OLD,
					cts1.impressions/1000  AS target_impressions,
					cpn.hard_end_date AS end_date_OLD,
					cts1.cycle_end_date AS cycle_end_date,
					IFNULL(cts1.cycle_end_date_flag, '') AS cycle_end_date_flag,
					cpn.is_month_end_type AS is_month_end_type,
					cpn.is_monthly_target AS is_monthly_target,
					cpn.term_type AS term_type,
					cpn.LandingPage AS landing_page,
					cpn.ttd_campaign_id AS ttd_campaign_id,
					cpn.ttd_daily_modify AS ttd_daily_modify,
					COALESCE(cpn.cached_city_record_cycle_impression_sum, 0) AS cycle_impressions,
					MIN(adg.earliest_city_record) AS start_date,
					COALESCE(SUM(adg.cached_city_record_impression_sum), 0) AS total_impressions,
					COALESCE(SUM(adg.cached_city_record_click_sum), 0) AS total_clicks,
					0 AS total_engagements,
					0 AS has_adsets,
					COALESCE(SUM(adg.cached_city_record_yday_impression_sum), 0) AS yday_impressions,
					COALESCE(SUM(adg.cached_city_record_last_month_impression_sum), 0) AS last_month_impressions,
					IF(adg.vl_id IS NOT NULL AND adg.source = 'TD', TRUE, FALSE) AS has_adgroups,
					0 AS has_tags,
					DATEDIFF(DATE_SUB(cts1.cycle_end_date, INTERVAL -1 DAY), ?) AS days_left,
					DATEDIFF(DATE_SUB(cts1.cycle_end_date, INTERVAL -1 DAY), cts1.cycle_start_date) AS cycle_total_days,
					cts1.cycle_start_date AS cycle_start_date,
			        cts1.impressions AS target_impressions_full,
					NULL AS pc_adgroup_id,
					cpn.pause_date
				FROM
					Campaigns AS cpn
					JOIN Advertisers AS adv
						ON adv.id = cpn.business_id
					JOIN users AS us
						ON adv.`sales_person` = us.`id`
					JOIN wl_partner_details AS pd
						ON us.`partner_id` = pd.id
					JOIN AdGroups AS adg
						ON adg.campaign_id = cpn.id
					JOIN io_campaign_product_cpm campaign_cpm
						ON cpn.id = campaign_cpm.campaign_id
						AND adg.subproduct_type_id = campaign_cpm.subproduct_type_id
				 	LEFT JOIN (
			       			SELECT
								inner_data.campaign_id 'campaigns_id',
								CASE cts.impressions
									WHEN
										0
									THEN
										'Paused'
									ELSE
										'Active'
									END
									 	cycle_end_date_flag,
								inner_data.end_date cycle_end_date,
								inner_data.cycle_start_date 'cycle_start_date',								
								cts.impressions 'impressions'
							FROM
								(
									SELECT
										main.campaigns_id AS campaign_id,										
										MAX(series_date) AS cycle_start_date,
										min_date.end_date AS end_date
									FROM
					       				campaigns_time_series main
					       				LEFT JOIN
											(
												SELECT
													campaigns_id,
													DATE_SUB(MIN(series_date), INTERVAL 1 DAY) end_date
												FROM
					       							campaigns_time_series
												WHERE
													series_date >= DATE_SUB(?, INTERVAL -1 DAY)
													AND object_type_id=0
												GROUP BY
													campaigns_id
											)
											min_date ON
												min_date.campaigns_id = main.campaigns_id
									WHERE
										series_date <= ? AND
									 	min_date.end_date IS NOT NULL
									 	AND main.object_type_id=0
									GROUP BY
										main.campaigns_id
									) AS inner_data,
									campaigns_time_series cts
									WHERE
										inner_data.campaign_id =cts.campaigns_id AND
										inner_data.cycle_start_date=cts.series_date
										AND cts.object_type_id=0
			       		)
						cts1 ON
						cpn.id=cts1.campaigns_id
				WHERE
					cpn.ignore_for_healthcheck = 0
					".$disable_campaign_for_non_demo_user_sql.$tag_where_clause.$skip_o_o_adgroup_sql."
				GROUP BY
					adg.IsRetargeting,
					cpn.id
				ORDER BY
					advertiser,
					campaign";

                        // Get Third Party IDs
                        $third_party_ids = $this->get_third_party_ids_for_campaigns_main();

		}
		else
		{
			return $campaigns;
		}
		 
		$engagements = $this->get_engagements_for_campaigns_main();
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			$tags = $this->get_tags_for_campaigns_main();
			$pc_adgroups = $this->get_ttd_pc_adgroups_for_campaigns_main();
			$campaigns = array();
			$raw_campaigns = $query->result_array();

			$this->combine_rtg_and_normal_campaign_rows($raw_campaigns, $campaigns, $role);
		
			//create _assoc arrays which put the id as key
			$engagements_assoc = array();
			$third_party_ids_assoc = array();
			$tags_assoc = array();
			$pc_adgroups_assoc = array();

			foreach($engagements as $engagement)
			{
				$engagements_assoc[$engagement['id']] = $engagement;
			}

			foreach($third_party_ids as $third_party_id)
			{
				$third_party_ids_assoc[$third_party_id['id']] = $third_party_id;
			}

			foreach($tags as $tag)
			{
				$tags_assoc[$tag['id']] = $tag;
			}

			foreach($pc_adgroups as $pc_adgroup)
			{
				$pc_adgroups_assoc[$pc_adgroup['id']] = $pc_adgroup;
			}

			foreach($campaigns as $c2_key => &$campaign)
			{
				if (array_key_exists($campaign['id'], $engagements_assoc))
				{
					$engagement = $engagements_assoc[$campaign['id']];
					if($role == 'admin' or $role == 'ops')
					{
						$campaign['total_engagements'] = $engagement['total_engagements'];
					}
					$campaign['has_adsets'] = $engagement['has_adsets'];
				}
			

				if($role == 'admin' or $role == 'ops')
	            {
	            	if (array_key_exists($campaign['id'], $third_party_ids_assoc))
					{	
						$third_party_id = $third_party_ids_assoc[$campaign['id']];

				        $campaign['f_id'] = $third_party_id['f_id'];
                        $campaign['ttd_adv_id'] = $third_party_id['ttd_adv_id'];
                        $campaign['ul_id'] = $third_party_id['ul_id'];
                        $campaign['eclipse_id'] = $third_party_id['eclipse_id'];
                        $campaign['tmpi_ids'] = $third_party_id['tmpi_ids'];
                     }
                 }
	            
            	if (array_key_exists($campaign['id'], $tags_assoc))
				{	
					$tag = $tags_assoc[$campaign['id']];
					$campaign['has_tags'] = $tag['has_tags'];
					
				}

	           	if (array_key_exists($campaign['id'], $pc_adgroups_assoc))
				{	
					$pc_adgroup = $pc_adgroups_assoc[$campaign['id']];
					if($pc_adgroup['pc_adgroup_count'] == 1)
					{
						$campaign['pc_adgroup_id'] = $pc_adgroup['pc_adgroup_id'];
						$campaign['pc_yday_impressions'] = $pc_adgroup['pc_yday_impressions'];
					}
				}
			}
		}
		return $campaigns;
	}

	public function get_engagements_for_campaigns_main()
	{
		$engagement_sql = "
				SELECT
					cpn.id AS id,
					COALESCE(SUM(ver.cached_engagement_record_sum), 0) AS total_engagements,
					IF(cad.id IS NULL, FALSE, TRUE) AS has_adsets
				FROM
					Campaigns AS cpn
					LEFT JOIN cup_versions AS ver
						ON cpn.id = ver.campaign_id
					LEFT JOIN cup_adsets cad
						ON ver.adset_id = cad.id
				WHERE
					cpn.ignore_for_healthcheck = 0
				GROUP BY
					cpn.id";
		$result = $this->db->query($engagement_sql);
		return $result->result_array();
	}

        public function get_third_party_ids_for_campaigns_main()
        {
            $third_party_ids_sql = "
                                SELECT
                                        cpn.id AS id,
                                        adv.id AS f_id,
                                        adv.ttd_adv_id AS ttd_adv_id,
                                        tsa.ul_id AS ul_id,
                                        tsa.eclipse_id AS eclipse_id,
                                        GROUP_CONCAT(tmpi_join.account_id SEPARATOR ', ') AS tmpi_ids
                                FROM
                                        Campaigns AS cpn
                                        JOIN Advertisers AS adv
                                                        ON adv.id = cpn.business_id
                                        LEFT JOIN tp_spectrum_accounts AS tsa
                                                        ON adv.id = tsa.advertiser_id
                                        LEFT JOIN
                                                (SELECT
                                                        tajtpa.frq_advertiser_id AS frq_advertiser_id,
                                                        tta.account_id AS account_id
                                                FROM
                                                        tp_advertisers_join_third_party_account AS tajtpa
                                                        JOIN tp_tmpi_accounts AS tta
                                                                ON tajtpa.frq_third_party_account_id = tta.id
                                                WHERE
                                                        tajtpa.third_party_source = 1
                                                ) AS tmpi_join
                                                        ON adv.id = tmpi_join.frq_advertiser_id
                                WHERE
                                        cpn.ignore_for_healthcheck = 0
                                GROUP BY
                                        cpn.id";
            $result = $this->db->query($third_party_ids_sql);
            return $result->result_array();
        }

	public function get_tags_for_campaigns_main()
	{
		$tags_sql =
			"
			SELECT
				cpn.id AS id,
				TRUE AS has_tags
			FROM
				Campaigns AS cpn
			JOIN
				tag_files_to_campaigns tftc ON cpn.id = tftc.campaign_id
			JOIN
				tag_codes tc ON tftc.tag_file_id = tc.tag_file_id
			WHERE
				cpn.ignore_for_healthcheck = 0
			AND
				tc.tag_type != 1
			AND
				tc.isActive = 1
			GROUP BY
				cpn.id
			";

		$result = $this->db->query($tags_sql);
		return $result->result_array();
	}

	private function combine_rtg_and_normal_campaign_rows($raw_campaigns, &$campaigns, $role)
	{
		foreach($raw_campaigns as $c_key => $raw_campaign)
		{
			$temp_id = $raw_campaign['id'];
			$campaign = &$campaigns[$temp_id];
			if(empty($campaign))
			{
				$campaign = $raw_campaign;
				$campaign['rtg_last_month_impressions'] = 0;
			}
			else
			{
				if(empty($campaign['start_date']) OR (!empty($raw_campaign['start_date']) and strtotime($campaign['start_date']) > strtotime($raw_campaign['start_date'])))
				{
					$campaign['start_date'] = $raw_campaign['start_date'];
				}

				$campaign['total_impressions'] += $raw_campaign['total_impressions'];
				$campaign['total_clicks'] += $raw_campaign['total_clicks'];
				$campaign['has_adgroups'] |= $raw_campaign['has_adgroups'];
				$campaign['has_tags'] |= $raw_campaign['has_tags'];
				$campaign['is_retargeting'] |= $raw_campaign['is_retargeting'];

				if($role == 'ops' || $role == 'admin')
				{
					$campaign['yday_impressions'] += $raw_campaign['yday_impressions'];
					$campaign['last_month_impressions'] += $raw_campaign['last_month_impressions'];
				}
			}
			if($raw_campaign['is_retargeting'] == 1)
			{
				$campaign['rtg_last_month_impressions'] = $raw_campaign['last_month_impressions'];
			}
		}
	}

	public function get_last_cached_impression_date()
	{
		$query = "
			SELECT
				MAX(ag.latest_city_record) as latest_impression_date
			FROM
				AdGroups AS ag
				JOIN Campaigns AS ca
					ON ag.campaign_id = ca.id
			WHERE
				ca.ignore_for_healthcheck = 0";

		$result = $this->db->query($query);
		if($result->num_rows() > 0)
		{
			return $result->row_array();
		}
		return array();
	}
	public function date_add_months($date_str, $months)
	{
		$date = new DateTime($date_str);
		$start_day = $date->format('j');
		$date->modify("+{$months} month");
		$end_day = $date->format('j');

		if ($start_day != $end_day)
		{
			$date->modify('last day of last month');
		}

		return $date;
	}

	public function date_subtract($date2, $date1)
	{
		$delta_year = date('Y', strtotime($date2)) - date('Y', strtotime($date1));
		$delta_month = date('m', strtotime($date2)) - date('m', strtotime($date1));
		$total_delta_month = 12 * $delta_year + $delta_month;
		$inverted = $total_delta_month < 0 ? -1 : 1;
		$final_delta_year = floor(abs($total_delta_month / 12));
		$final_delta_month = abs($total_delta_month - 12 * $final_delta_year * $inverted);

		return array(
			'invert' => $inverted,
			'y' => $final_delta_year,
			'm' => $final_delta_month);
	}
	public function get_campaign_info_for_cycle_impressions($campaign_id = false)
	{
		$bindings = array();
		if($campaign_id !== false)
		{
			$where_sql = "ca.id = ?";
			$bindings[] = $campaign_id;
		}
		else
		{
			$where_sql = " 1=1 ";
		}

		$query = "
			SELECT
				ca.*,
				SUM(COALESCE(ag.cached_city_record_impression_sum, 0)) AS lifetime_impressions,
				MIN(ag.earliest_city_record) AS start_date
			FROM
				Campaigns AS ca
				JOIN AdGroups ag
					ON ca.id = ag.campaign_id
			WHERE ".$where_sql."
			GROUP BY
				ca.id";

		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}
	public function update_campaign_with_cycle_impressions($campaign_id, $cycle_impressions)
	{
		$query = "
			UPDATE
				Campaigns
			SET
				cached_city_record_cycle_impression_sum = ?
			WHERE
				id = ?";
		$response = $this->db->query($query, array($cycle_impressions, $campaign_id));
		return $response; //true or false
	}
	public function update_adgroups_with_lifetime_impressions_clicks()
	{
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				LEFT JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						SUM(cr.Impressions) AS imp_sum,
						SUM(cr.Clicks) AS clk_sum
					FROM
						CityRecords AS cr
						JOIN AdGroups AS ag2
							ON cr.AdGroupID = ag2.ID
					WHERE 1
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.cached_city_record_impression_sum = COALESCE(sub_ag.imp_sum, 0),
				ag.cached_city_record_click_sum = COALESCE(sub_ag.clk_sum, 0)
			WHERE 1";

		$city_response = $this->db->query($update_cities_sql);

		return $city_response;
	}

	public function update_adsets_with_lifetime_engagements()
	{
		$update_engagements_sql = "
			UPDATE
				cup_versions AS ver
				LEFT JOIN
				(
					SELECT
						cpv.id AS version_id,
						(SUM(IF(er.engagement_type = 5 AND er.value >= $this->ad_interaction_hover_threshold, er.total, 0)) + SUM(IF(er.engagement_type = 1 OR er.engagement_type = 6, er.total, 0))) AS engagements
					FROM
						cup_versions AS cpv
						JOIN cup_creatives AS cpc
							ON cpv.id = cpc.version_id
						JOIN engagement_records AS er
							on cpc.id = er.creative_id
					WHERE
						er.engagement_type IN (1, 5, 6)
					Group BY
						cpv.id
				) AS egr
					ON ver.id = egr.version_id
			SET
				ver.cached_engagement_record_sum = COALESCE(egr.engagements, 0)
			WHERE 1";
		return $this->db->query($update_engagements_sql);
	}

	public function update_adgroups_with_last_month_impressions($start_date, $end_date)
	{
		$bindings = array($start_date, $end_date);
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				LEFT JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						SUM(cr.Impressions) AS imp_sum
					FROM
						CityRecords AS cr
						JOIN AdGroups AS ag2
							ON cr.AdGroupID = ag2.ID
					WHERE
						Date BETWEEN ? AND ?
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.cached_city_record_last_month_impression_sum = COALESCE(sub_ag.imp_sum, 0)
			WHERE 1";

		$city_response = $this->db->query($update_cities_sql, $bindings);

		return $city_response;
	}
	public function update_adgroups_with_yday_impressions($report_date)
	{
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				LEFT JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						SUM(cr.Impressions) AS imp_sum,
						SUM(cr.Clicks) AS clk_sum
					FROM
						CityRecords AS cr
						JOIN AdGroups AS ag2
							ON cr.AdGroupID = ag2.ID
					WHERE cr.Date = ?
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.cached_city_record_yday_impression_sum = COALESCE(sub_ag.imp_sum, 0)
			WHERE 1";
		$city_response = $this->db->query($update_cities_sql, $report_date);
		return $city_response;
	}

	public function update_adgroups_with_earliest_latest_date()
	{
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				JOIN
				(
					SELECT
						AdGroupID,
						min(Date) AS min_date,
 						max(Date) AS max_date
					FROM
						CityRecords
					WHERE 1
					Group BY
						AdGroupID
				) AS cr
					ON ag.ID = cr.AdGroupID
			SET
				ag.earliest_city_record = cr.min_date,
				ag.latest_city_record = cr.max_date
			WHERE 1";

		$update_sites_sql = "
			UPDATE
				AdGroups AS ag
				JOIN
				(
					SELECT
						AdGroupID,
						min(Date) AS min_date,
		 				max(Date) AS max_date
					FROM
						SiteRecords
					WHERE 1
					Group BY
						AdGroupID
				) AS sr
					ON ag.ID = sr.AdGroupID
			SET
				ag.earliest_site_record = sr.min_date,
				ag.latest_site_record = sr.max_date
			WHERE 1";
		$cities_response = $this->db->query($update_cities_sql);
		$sites_response = $this->db->query($update_sites_sql);
		return array('sites_response' => $sites_response, 'cities_response' => $cities_response);
	}
	public function modify_campaign_and_adgroups_with_current_time($c_id)
	{
		$campaign_query = "
			UPDATE
				Campaigns
			SET
				ttd_daily_modify = NOW()
			WHERE
				id = ?";
		$campaign_response = $this->db->query($campaign_query, $c_id);

		$adgroup_query = "
			UPDATE
				AdGroups
			SET
				ttd_daily_modify = NOW()
			WHERE
				campaign_id = ?";
		$adgroup_response = $this->db->query($adgroup_query, $c_id);
		return $campaign_response && $adgroup_response;
	}
	public function get_pc_adgroup_by_campaign_id($c_id)
	{
		$query = "
			SELECT
				*
			FROM
				AdGroups
			WHERE
				target_type = 'PC' AND
				(Source = 'TD' OR Source = 'TDGF') AND
				campaign_id = ?";
		$response = $this->db->query($query, $c_id);
		if($response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}
	public function get_ttd_pc_adgroups_for_campaigns_main()
	{
		$query = "
			SELECT
				ag.ID AS pc_adgroup_id,
				ca.id AS id,
				COUNT(ag.ID) AS pc_adgroup_count,
				COALESCE(SUM(cached_city_record_yday_impression_sum), 0) AS pc_yday_impressions
			FROM
				AdGroups AS ag
				JOIN Campaigns AS ca
					ON ag.campaign_id = ca.id
			WHERE
				ca.ignore_for_healthcheck = 0 AND
				(ag.source = 'TD' OR Source = 'TDGF') AND
				ag.target_type = 'PC'
			GROUP BY
				ca.id";
		$response = $this->db->query($query);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return false;
	}

	public function update_single_campaign_adgroups_with_earliest_latest_date($campaign_id)
	{
		$bindings = array($campaign_id, $campaign_id);
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						min(cr.Date) AS min_date,
		 				max(cr.Date) AS max_date
					FROM
						CityRecords cr
						JOIN AdGroups ag2
							ON cr.AdGroupID = ag2.ID
					WHERE
						ag2.campaign_id = ?
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.earliest_site_record = sub_ag.min_date,
				ag.latest_site_record = sub_ag.max_date
			WHERE
				ag.campaign_id = ?";

		$update_sites_sql = "
			UPDATE
				AdGroups AS ag
				JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						min(sr.Date) AS min_date,
		 				max(sr.Date) AS max_date
					FROM
						SiteRecords sr
						JOIN AdGroups ag2
							ON sr.AdGroupID = ag2.ID
					WHERE
						ag2.campaign_id = ?
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.earliest_site_record = sub_ag.min_date,
				ag.latest_site_record = sub_ag.max_date
			WHERE
				ag.campaign_id = ?";
		$cities_response = $this->db->query($update_cities_sql, $bindings);
		$sites_response = $this->db->query($update_sites_sql, $bindings);

		return $cities_response && $sites_response;
	}

	public function update_single_campaign_adgroups_with_yday_impressions($campaign_id, $report_date)
	{
		$bindings = array($report_date, $campaign_id, $campaign_id);
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				LEFT JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						SUM(cr.Impressions) AS imp_sum,
						SUM(cr.Clicks) AS clk_sum
					FROM
						CityRecords AS cr
						JOIN AdGroups AS ag2
							ON cr.AdGroupID = ag2.ID
					WHERE
						cr.Date = ? AND
						ag2.campaign_id = ?

					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.cached_city_record_yday_impression_sum = COALESCE(sub_ag.imp_sum, 0)
			WHERE
				ag.campaign_id = ?";
		$city_response = $this->db->query($update_cities_sql, $bindings);
		return $city_response;
	}

	public function update_single_campaign_adgroups_with_last_month_impressions($campaign_id, $start_date, $end_date)
	{
		$bindings = array($start_date, $end_date, $campaign_id, $campaign_id);
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				LEFT JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						SUM(cr.Impressions) AS imp_sum
					FROM
						CityRecords AS cr
						JOIN AdGroups AS ag2
							ON cr.AdGroupID = ag2.ID
					WHERE
						Date BETWEEN ? AND ? AND
						ag2.campaign_id = ?
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.cached_city_record_last_month_impression_sum = COALESCE(sub_ag.imp_sum, 0)
			WHERE
				ag.campaign_id = ?";

		$city_response = $this->db->query($update_cities_sql, $bindings);
		return $city_response;
	}

	public function update_single_campaign_adgroups_with_lifetime_impressions_clicks($campaign_id)
	{
		$bindings = array($campaign_id, $campaign_id);
		$update_cities_sql = "
			UPDATE
				AdGroups AS ag
				LEFT JOIN
				(
					SELECT
						ag2.vl_id AS vl_id,
						SUM(cr.Impressions) AS imp_sum,
						SUM(cr.Clicks) AS clk_sum
					FROM
						CityRecords AS cr
						JOIN AdGroups AS ag2
							ON cr.AdGroupID = ag2.ID
					WHERE
						ag2.campaign_id = ?
					Group BY
						ag2.vl_id
				) AS sub_ag
					ON ag.vl_id = sub_ag.vl_id
			SET
				ag.cached_city_record_impression_sum = COALESCE(sub_ag.imp_sum, 0),
				ag.cached_city_record_click_sum = COALESCE(sub_ag.clk_sum, 0)
			WHERE
				ag.campaign_id = ?";

		$city_response = $this->db->query($update_cities_sql, $bindings);
		return $city_response;
	}

	public function update_single_campaign_adsets_with_lifetime_engagements($campaign_id)
	{
		$bindings = array($campaign_id, $campaign_id);
		$update_engagements_sql = "
			UPDATE
				cup_versions AS ver
				LEFT JOIN
				(
					SELECT
						cpv.id AS version_id,
						(SUM(IF(er.engagement_type = 5 AND er.value >= $this->ad_interaction_hover_threshold, er.total, 0)) + SUM(IF(er.engagement_type = 1 OR er.engagement_type = 6, er.total, 0))) AS engagements
					FROM
						cup_versions AS cpv
						JOIN cup_creatives AS cpc
							ON cpv.id = cpc.version_id
						JOIN engagement_records AS er
							on cpc.id = er.creative_id
					WHERE
						er.engagement_type IN (1, 5, 6) AND
						cpv.campaign_id = ?
					Group BY
						cpv.id
				) AS egr
					ON ver.id = egr.version_id
			SET
				ver.cached_engagement_record_sum = COALESCE(egr.engagements, 0)
			WHERE
				ver.campaign_id = ?";
		$engagement_response = $this->db->query($update_engagements_sql, $bindings);
		return $engagement_response;
	}

	public function set_bulk_campaign_email_flag_for_user($user_id, $disable_bulk_email = true)
	{
		if($disable_bulk_email === true)
		{
			$bulk_pending_sql = "NOW()";
		}
		else
		{
			$bulk_pending_sql = "NULL";
		}
		$query = "
			UPDATE
				users
			SET
				bulk_campaign_email_pending = ".$bulk_pending_sql."
			WHERE
				id = ?";
		$response = $this->db->query($query, $user_id);
		return $response;
	}

	public function get_bulk_adgroup_putter_data($selected_campaigns)
	{
		$binding_sql = "";
		foreach($selected_campaigns as $v)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "?";
		}
		$report_date = $this->get_last_impression_date();
		$report_date = $report_date[0]['value'];
		$bindings = array_merge(
			array($report_date),
			$selected_campaigns,
			array($report_date),
			$selected_campaigns,
			array($report_date),
			$selected_campaigns,
			$selected_campaigns,
			$selected_campaigns
		);

		$query = "SELECT
					adv.Name AS 'F Advertiser Name',
					cpn.Name AS 'F Campaign Name',
					cpn.id AS 'F Campaign ID',
					ts.impressions AS 'F Target',
					imprs.impressions AS 'Total Impressions',
					last_cycle.last_end_date AS 'F Total End Date',
					cpn.cached_city_record_cycle_impression_sum AS 'Cycle Impressions',
					ts_end.end_date AS 'F Cyc End Date',
					(ts.impressions - cpn.cached_city_record_cycle_impression_sum)/ (DATEDIFF(ts_end.end_date, ?)) AS 'Cycle Target (Daily)',
					cpn.term_type AS 'F Campaign Type',
					adg.target_type AS 'F Adgroup Type',
					adg.ID AS 'AdGroupId'
				FROM
					Advertisers AS adv
					JOIN Campaigns AS cpn
						ON adv.id = cpn.business_id
					JOIN AdGroups AS adg
						ON cpn.id = adg.campaign_id
					LEFT JOIN
					(SELECT
						cts.campaigns_id AS c_id,
						cts.impressions AS impressions
					FROM
						campaigns_time_series AS cts
						JOIN
						(SELECT
							campaigns_id AS c_id,
							MAX(series_date) AS max_date
						 FROM
						 	campaigns_time_series
						 WHERE
						 	campaigns_id IN (".$binding_sql.")
						 	AND object_type_id = 0
						 	AND series_date <= ?
						GROUP BY
						campaigns_id) AS row_ids
						ON cts.campaigns_id = row_ids.c_id AND cts.series_date = row_ids.max_date
					) AS ts
						ON cpn.id = ts.c_id
					LEFT JOIN
					(SELECT
							 	campaigns_id AS c_id,
							 	DATE_SUB(MIN(series_date), INTERVAL 1 DAY) AS end_date
							 FROM
							 	campaigns_time_series
							 WHERE
							 	campaigns_id IN (".$binding_sql.")
							 	AND series_date > ?
							 	AND object_type_id = 0
							GROUP BY
							campaigns_id
					) AS ts_end
						ON cpn.id = ts_end.c_id
					LEFT JOIN
					(SELECT
						adg.campaign_id AS c_id,
						SUM(rcad.impressions) AS impressions
					FROM
						AdGroups AS adg
						JOIN report_cached_adgroup_date AS rcad
							ON adg.ID = rcad.adgroup_id
					WHERE
						adg.campaign_id IN (".$binding_sql.")
					GROUP BY
						adg.campaign_id
					) AS imprs
						ON cpn.id = imprs.c_id
					LEFT JOIN
					(SELECT
						cts.campaigns_id AS c_id,
						DATE_SUB(MAX(cts.series_date), INTERVAL 1 DAY) AS last_end_date
					FROM
						campaigns_time_series AS cts
					WHERE
						cts.campaigns_id IN (".$binding_sql.")
						AND cts.object_type_id = 0
					GROUP BY
						cts.campaigns_id
					) AS last_cycle
						ON cpn.id = last_cycle.c_id
				WHERE
					cpn.id IN (".$binding_sql.") AND
					adg.Source != 'TDAV'
				GROUP BY
					adg.ID
				ORDER BY
					adv.id";
		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}

	public function get_bulk_timeseries_putter_data($selected_campaigns)
	{
		$binding_sql = "";
		foreach($selected_campaigns as $v)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "?";
		}

		$query = "
			SELECT
				adv.Name AS 'Advertiser Name',
				cpn.Name AS 'Campaign Name',
				cpn.id AS 'CampaignID',
				cts.series_date AS 'Start Date',
				cts.impressions AS 'Impressions'
			FROM
				Advertisers AS adv
				JOIN Campaigns AS cpn
					ON adv.id = cpn.business_id
				JOIN campaigns_time_series AS cts
					ON cpn.id = cts.campaigns_id
			WHERE
				cpn.id IN (".$binding_sql.")
				AND cts.object_type_id=0
			ORDER BY
				cpn.id, cts.series_date";

		$response = $this->db->query($query, $selected_campaigns);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		return array();
	}

	public function bulk_archive_campaigns_by_id($campaign_array, &$return_array)
	{
		$binding_sql = "";
		foreach($campaign_array as $v)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "?";
		}

		$query = "
			UPDATE
				Campaigns
			SET
				ignore_for_healthcheck = 1
			WHERE
				id IN (".$binding_sql.")";
		$response = $this->db->query($query, $campaign_array);
	    if($response === false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 914720: Database error when archiving campaigns";
		}
		else if($this->db->affected_rows() === 0)
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Warning 914880: Unable to archive campaigns";
		}
	}

	public function bulk_migrate_campaigns_to_timeseries($table_mode)
	{
		$query = "
			SELECT
				id,
				start_date,
				IFNULL(hard_end_date, '2016-06-01') hard_end_date,
				term_type,
				targetImpressions*1000  AS targetImpressions
			FROM
				". $table_mode ."
			WHERE
				term_type in ('BROADCAST_MONTHLY','FIXED_TERM','MONTH_END','MONTHLY') AND
				TargetImpressions > 0 AND
				start_date > '1970-01-01'
			";

		$response = $this->db->query($query);
		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}

	}

	public function notes_search_advanced($ad_name, $cpn_name, $cpn_minus, $notes_text, $user_name, $mode)
	{
		if ($mode == '0') // advertiser
		{
			$query = "
				SELECT
					name AS 'advertiser',
					'' AS 'cid',
					'' AS 'cname',
					'' AS 'created_date',
					'' AS 'username',
					'' AS 'notes'
				FROM
					Advertisers
				WHERE
					name LIKE '%". $ad_name. "%'
				LIMIT 100
				";

			$response = $this->db->query($query);
			if($response->num_rows() > 0)
			{
				return $response->result_array();
			}
		}
		else if ($mode == '1') // campaign
		{
			$query = "
				SELECT
					a.name AS 'advertiser',
					c.id AS 'cid',
					c.name AS 'cname',
					'' AS 'created_date',
					'' AS 'username',
					'' AS 'notes'
				FROM
					Advertisers a,
					Campaigns c
				WHERE
					a.id=c.business_id ";

				if ($ad_name != null && strlen($ad_name) > 0)
				{
					$query .= "AND a.name LIKE '%". $ad_name. "%' ";
				}
				if ($cpn_name != null && strlen($cpn_name) > 0)
				{
					$query .= "AND c.name LIKE '%". $cpn_name. "%' ";
				}
				if ($cpn_minus != null && strlen($cpn_minus) > 0)
				{
					$query .= "AND c.name NOT LIKE '%". $cpn_minus. "%' ";
				}

				$query .=  " LIMIT 100 ";

			$response = $this->db->query($query);
			if($response->num_rows() > 0)
			{
				return $response->result_array();
			}
		}
		else if ($mode == '2') // note
		{
			$query = "
			SELECT
	    		SUM.Advertiser 'advertiser',
	    		SUM.object_id 'cid',
	    		SUM.CampaignName 'cname',
				SUM.notes_text 'notes',
				SUM.created_date 'created_date',
				SUM.username 'username'
			FROM
			(
				SELECT
		    		a.name 'Advertiser',
		    		notes.object_id  ,
		    		c.name 'CampaignName',
					notes.notes_text  ,
					notes.created_date  ,
					notes.username
				FROM
					notes,
					Campaigns c,
					Advertisers a
				WHERE
					notes.object_type_id=1 AND
					notes.object_id=c.id AND
					c.business_id=a.id ";

				if ($ad_name != null && strlen($ad_name) > 0)
				{
					$query .= "AND a.name LIKE '%". $ad_name. "%' ";
				}
				if ($cpn_name != null && strlen($cpn_name) > 0)
				{
					$query .= "AND c.name LIKE '%". $cpn_name. "%' ";
				}
				if ($cpn_minus != null && strlen($cpn_minus) > 0)
				{
					$query .= "AND c.name NOT LIKE '%". $cpn_minus. "%' ";
				}
				if ($notes_text != null && strlen($notes_text) > 0)
				{
					$query .= "AND notes.notes_text LIKE '%". $notes_text. "%' ";
				}
				if ($user_name != null && strlen($user_name) > 0)
				{
					$query .= "AND notes.username LIKE '%". $user_name. "%' ";
				}

				$query .= "
				UNION ALL
					SELECT
		    		a.name  ,
		    		'--'  ,
		    		'--'  ,
					notes.notes_text ,
					notes.created_date ,
					notes.username
				FROM
					notes,
					Advertisers a
				WHERE
					notes.object_type_id=2 AND
					notes.object_id=a.id ";

				if ($ad_name != null && strlen($ad_name) > 0)
				{
					$query .= "AND a.name LIKE '%". $ad_name. "%' ";
				}
				if ($notes_text != null && strlen($notes_text) > 0)
				{
					$query .= "AND notes.notes_text LIKE '%". $notes_text. "%' ";
				}
				if ($user_name != null && strlen($user_name) > 0)
				{
					$query .= "AND notes.username LIKE '%". $user_name. "%' ";
				}

				$query .= ") AS SUM
				ORDER BY
					SUM.Advertiser,
					SUM.CampaignName,
					SUM.created_date DESC
				LIMIT 100;
			 ";

			$response = $this->db->query($query);
			if($response->num_rows() > 0)
			{
				return $response->result_array();
			}
		}

	}

	public function get_campaign_io_data()
	{
		$sql=
		"
			SELECT
				c.id AS campaigns_id,
				io.order_id,
				io.unique_display_id
			FROM
				Campaigns AS c
			JOIN
				mpq_sessions_and_submissions AS io
			ON
				c.insertion_order_id = io.id
			WHERE
				io.order_id IS NOT NULL
		";

		$result_array = array();
		$query = $this->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if (! (array_key_exists('c-'. $row['campaigns_id'], $result_array) == true))
				{
					$result_array['c-'. $row['campaigns_id']] = array();
				}
				$result_array['c-'. $row['campaigns_id']] = array('campaigns_id'=>$row['campaigns_id'],'order_id'=>$row['order_id'],'unique_display_id'=>$row['unique_display_id']);
			}
		}

		return $result_array;
	}

	public function update_reminder_flag_status($campaign_id, $reminder_status)
	{
		$binding_array = array($reminder_status, $campaign_id);
		$query = "
				UPDATE
					Campaigns AS cp
				SET
					cp.is_reminder = ?
				WHERE
					cp.id = ?";

		$this->db->query($query, $binding_array);
		if($this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}
	
	public function get_budget_info_for_campaigns($campaign_ids, $report_date = NULL)
	{
		if (!isset($report_date) || empty($report_date))
		{
			$report_date_result = $this->get_last_cached_impression_date();
			$report_date = $report_date_result['latest_impression_date'];
		}
		
		$sql =	"
				SELECT 
					inner_query.*,	
					COALESCE((DATEDIFF(inner_query.flight_end_date, inner_query.flight_start_date) + 1),0) AS num_of_days_in_flight,					
					CASE WHEN (inner_query.flight_start_date <= '$report_date' AND inner_query.flight_end_date >= '$report_date') THEN 1 ELSE 0 END AS current_flight
				FROM
				(
					SELECT 
						cts1.campaigns_id AS campaign_id,
						cts1.series_date AS flight_start_date,						
						COALESCE(ROUND(adg1.subproduct_type_id,2),0) AS subproduct_type,
						DATE_SUB((SELECT inn.series_date FROM campaigns_time_series AS inn WHERE inn.series_date > cts1.series_date AND inn.campaigns_id = cts1.campaigns_id AND object_type_id = 0 LIMIT 1),INTERVAL 1 DAY) AS flight_end_date,
						cts1.budget AS flight_budget,
						COALESCE(ROUND(ifsd.response_o_o_dfp_budget,2),0) AS o_o_budget,
						COALESCE(ifsd.o_o_impressions,0) AS o_o_impressions,
						COALESCE(ROUND(ifsd.geofencing_budget,2),0) AS geofence_budget,
						COALESCE(ifsd.geofencing_impressions,0) AS geofence_impressions,
						COALESCE(ROUND(ifsd.audience_ext_budget,2),0) AS audience_ext_budget,
						COALESCE(ifsd.audience_ext_impressions,0) AS audience_ext_impressions,
						COALESCE(ROUND(sum(icpc.dollar_cpm * adg1.cached_city_record_impression_sum)/1000,2),0) AS campaign_realized_budget,
						COALESCE(sum(adg1.cached_city_record_impression_sum),0) AS campaign_realized_impressions,
						COALESCE(ROUND(sum(icpc.dollar_cpm * adg1.cached_city_record_cycle_impression_sum)/1000,2),0) AS flight_realized_budget,
						COALESCE(sum(adg1.cached_city_record_cycle_impression_sum),0) AS flight_realized_impressions
					FROM
						campaigns_time_series AS cts1
						JOIN io_flight_subproduct_details AS ifsd
							ON cts1.id = ifsd.io_timeseries_id
						JOIN AdGroups AS adg1
							ON cts1.campaigns_id = adg1.campaign_id						 
						JOIN io_campaign_product_cpm AS icpc
							ON adg1.campaign_id = icpc.campaign_id
							AND adg1.subproduct_type_id = icpc.subproduct_type_id 
					WHERE
						cts1.campaigns_id in ($campaign_ids)
					AND
						cts1.series_date < (select max(series_date) from campaigns_time_series where campaigns_id in (cts1.campaigns_id) AND object_type_id = 0 )
					GROUP BY
						campaign_id, 
						flight_start_date,
						subproduct_type			
					ORDER BY
						campaign_id, 
						flight_start_date,
						subproduct_type
				) AS inner_query
				ORDER BY
					campaign_id, 
					flight_start_date,
					subproduct_type			
			";
		
		$query = $this->db->query($sql);
		
		$result = array();
		
		$active_subproducts = array(
			"DISPLAY" => false,
			"PREROLL" => false,
			"GEOFENCING" => false,
			"O_O_DISPLAY" => false
		);
		
		if ($query->num_rows() > 0)
		{
			$previous_campaign_id = 0;
			$processed_date = "";
			
			foreach ($query->result_array() as $row)
			{
				if (empty($processed_date) || ($previous_campaign_id != 0 && $row['campaign_id'] != $previous_campaign_id))
				{
					$processed_date = $row['flight_start_date'];
				}
				
				//OTI Calculation
				if ($previous_campaign_id != 0 && $row['campaign_id'] != $previous_campaign_id)
				{
					$this->calculate_otis_for_campaign($result, $previous_campaign_id, $report_date);
					$this->populate_campaign_schedule_status($result, $previous_campaign_id, $report_date);
				}
				
				if (!empty($row['subproduct_type']))
				{
					if (!isset($result[$row['campaign_id']]))
					{
						$result[$row['campaign_id']] = array();
						$result[$row['campaign_id']]['cycle_days'] = array();
						$result[$row['campaign_id']]['all_time'] = array();
						$result[$row['campaign_id']]['all_time']['campaign'] = array();
						$result[$row['campaign_id']]['all_time']['audience_ext'] = array();
						$result[$row['campaign_id']]['all_time']['o_and_o'] = array();
						
						//All Time - Campaign level budget info
						$result[$row['campaign_id']]['all_time']['campaign']['total_impressions'] = 0;
						$result[$row['campaign_id']]['all_time']['campaign']['budget'] = 0;
						$result[$row['campaign_id']]['all_time']['campaign']['realized'] = 0;
						$result[$row['campaign_id']]['all_time']['campaign']['realized_impressions'] = 0;
						
						//All Time - Audience extension budget info
						$result[$row['campaign_id']]['all_time']['audience_ext']['total_impressions'] = 0;
						$result[$row['campaign_id']]['all_time']['audience_ext']['budget'] = 0;
						$result[$row['campaign_id']]['all_time']['audience_ext']['realized'] = 0;
						$result[$row['campaign_id']]['all_time']['audience_ext']['realized_impressions'] = 0;
						
						//All Time - O&O budget info
						$result[$row['campaign_id']]['all_time']['o_and_o']['total_impressions'] = 0;
						$result[$row['campaign_id']]['all_time']['o_and_o']['budget'] = 0;
						$result[$row['campaign_id']]['all_time']['o_and_o']['realized'] = 0;
						$result[$row['campaign_id']]['all_time']['o_and_o']['realized_impressions'] = 0;
						
						//This Flight - Campaign level budget info
						$result[$row['campaign_id']]['this_flight']['campaign']['total_impressions'] = 0;
						$result[$row['campaign_id']]['this_flight']['campaign']['budget'] = 0;
						$result[$row['campaign_id']]['this_flight']['campaign']['realized'] = 0;
						$result[$row['campaign_id']]['this_flight']['campaign']['realized_impressions'] = 0;
						
						//This Flight - Audience extension budget info
						$result[$row['campaign_id']]['this_flight']['audience_ext']['total_impressions'] = 0;
						$result[$row['campaign_id']]['this_flight']['audience_ext']['budget'] = 0;
						$result[$row['campaign_id']]['this_flight']['audience_ext']['realized'] = 0;
						$result[$row['campaign_id']]['this_flight']['audience_ext']['realized_impressions'] = 0;
						
						//This Flight - O&O budget info
						$result[$row['campaign_id']]['this_flight']['o_and_o']['total_impressions'] = 0;
						$result[$row['campaign_id']]['this_flight']['o_and_o']['budget'] = 0;
						$result[$row['campaign_id']]['this_flight']['o_and_o']['realized'] = 0;
						$result[$row['campaign_id']]['this_flight']['o_and_o']['realized_impressions'] = 0;
						
					}
					
					//Cycle days
					$result[$row['campaign_id']]['cycle_days'][$row['flight_start_date']] = array(
						"num_of_days_in_flight" => $row['num_of_days_in_flight'],
						"flight_end_date" => $row['flight_end_date'],
						"flight_budget" => $row['flight_budget'],
						"is_current_flight" => $row['current_flight']
					);
					
					//This Flight - Campaign level budget info					
					if ($row['current_flight'] == 1)
					{
						$result[$row['campaign_id']]['this_flight']['flight_start_date'] = $row['flight_start_date'];
						$result[$row['campaign_id']]['this_flight']['flight_end_date'] = $row['flight_end_date'];
						$result[$row['campaign_id']]['this_flight']['campaign']['realized'] += $row['flight_realized_budget'];
						$result[$row['campaign_id']]['this_flight']['campaign']['realized_impressions'] += $row['flight_realized_impressions'];
					}
										
					//Sub-product level budget info
					if ($row['subproduct_type'] == 1 || $row['subproduct_type'] == 2)
					{
						if ($row['subproduct_type'] == 1 )
						{
							$active_subproducts['DISPLAY'] = true;
						}
						else
						{
							$active_subproducts['PREROLL'] = true;
						}
						
						$result[$row['campaign_id']]['all_time']['campaign']['budget'] += $row['audience_ext_budget'];
						$result[$row['campaign_id']]['all_time']['campaign']['total_impressions'] += $row['audience_ext_impressions'];
						$result[$row['campaign_id']]['all_time']['audience_ext']['budget'] += $row['audience_ext_budget'];
						$result[$row['campaign_id']]['all_time']['audience_ext']['total_impressions'] += $row['audience_ext_impressions'];
						
						if ($processed_date == $row['flight_start_date'])
						{
							$result[$row['campaign_id']]['all_time']['audience_ext']['realized'] += $row['campaign_realized_budget'];
							$result[$row['campaign_id']]['all_time']['audience_ext']['realized_impressions'] += $row['campaign_realized_impressions'];
							$result[$row['campaign_id']]['all_time']['campaign']['realized'] += $row['campaign_realized_budget'];
							$result[$row['campaign_id']]['all_time']['campaign']['realized_impressions'] += $row['campaign_realized_impressions'];
						}
						
						if ($row['current_flight'] == 1)
						{							
							$result[$row['campaign_id']]['this_flight']['campaign']['budget'] += $row['audience_ext_budget'];
							$result[$row['campaign_id']]['this_flight']['campaign']['total_impressions'] += $row['audience_ext_impressions'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['budget'] += $row['audience_ext_budget'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['total_impressions'] += $row['audience_ext_impressions'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['realized'] = $row['flight_realized_budget'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['realized_impressions'] = $row['flight_realized_impressions'];
						}
					}
					elseif ($row['subproduct_type'] == 3)
					{
						$active_subproducts['GEOFENCING'] = true;
						$result[$row['campaign_id']]['all_time']['campaign']['budget'] += $row['geofence_budget'];
						$result[$row['campaign_id']]['all_time']['campaign']['total_impressions'] += $row['geofence_impressions'];
						$result[$row['campaign_id']]['all_time']['audience_ext']['budget'] += $row['geofence_budget'];
						$result[$row['campaign_id']]['all_time']['audience_ext']['total_impressions'] += $row['geofence_impressions'];
						
						if ($processed_date == $row['flight_start_date'])
						{
							$result[$row['campaign_id']]['all_time']['campaign']['realized'] += $row['campaign_realized_budget'];
							$result[$row['campaign_id']]['all_time']['campaign']['realized_impressions'] += $row['campaign_realized_impressions'];
							$result[$row['campaign_id']]['all_time']['audience_ext']['realized'] += $row['campaign_realized_budget'];
							$result[$row['campaign_id']]['all_time']['audience_ext']['realized_impressions'] += $row['campaign_realized_impressions'];
						}	
						
						if ($row['current_flight'] == 1)
						{
							$result[$row['campaign_id']]['this_flight']['campaign']['budget'] += $row['geofence_budget'];
							$result[$row['campaign_id']]['this_flight']['campaign']['total_impressions'] += $row['geofence_impressions'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['budget'] += $row['geofence_budget'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['total_impressions'] += $row['geofence_impressions'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['realized'] = $row['flight_realized_budget'];
							$result[$row['campaign_id']]['this_flight']['audience_ext']['realized_impressions'] = $row['flight_realized_impressions'];
						}
						
					}elseif ($row['subproduct_type'] == 4)
					{
						$active_subproducts['O_O_DISPLAY'] = true;
						$result[$row['campaign_id']]['all_time']['campaign']['budget'] += $row['o_o_budget'];
						$result[$row['campaign_id']]['all_time']['campaign']['total_impressions'] += $row['o_o_impressions'];
						$result[$row['campaign_id']]['all_time']['o_and_o']['budget'] += $row['o_o_budget'];
						$result[$row['campaign_id']]['all_time']['o_and_o']['total_impressions'] += $row['o_o_impressions'];
						
						if ($processed_date == $row['flight_start_date'])
						{
							$result[$row['campaign_id']]['all_time']['campaign']['realized'] += $row['campaign_realized_budget'];
							$result[$row['campaign_id']]['all_time']['campaign']['realized_impressions'] += $row['campaign_realized_impressions'];
							$result[$row['campaign_id']]['all_time']['o_and_o']['realized'] += $row['campaign_realized_budget'];
							$result[$row['campaign_id']]['all_time']['o_and_o']['realized_impressions'] += $row['campaign_realized_impressions'];
						}
						
						if ($row['current_flight'] == 1)
						{
							$result[$row['campaign_id']]['this_flight']['campaign']['budget'] += $row['o_o_budget'];
							$result[$row['campaign_id']]['this_flight']['campaign']['total_impressions'] += $row['o_o_impressions'];
							$result[$row['campaign_id']]['this_flight']['o_and_o']['budget'] += $row['o_o_budget'];
							$result[$row['campaign_id']]['this_flight']['o_and_o']['total_impressions'] += $row['o_o_impressions'];
							$result[$row['campaign_id']]['this_flight']['o_and_o']['realized'] = $row['flight_realized_budget'];
							$result[$row['campaign_id']]['this_flight']['o_and_o']['realized_impressions'] = $row['flight_realized_impressions'];
						}						
					}
				}
				
				$previous_campaign_id = $row['campaign_id'];
			}
			
			//Processing the last campaign id
			if ($previous_campaign_id > 0)
			{
				$this->calculate_otis_for_campaign($result, $previous_campaign_id, $report_date);
				$this->populate_campaign_schedule_status($result, $previous_campaign_id, $report_date);
			}
		}
		
		return array(
			"result" => $result,
			"active_subproducts" => $active_subproducts
		);
	}
	
	
	private function populate_campaign_schedule_status(&$result, $previous_campaign_id, $report_date)
	{
		$result[$previous_campaign_id]['schedule'] = "";
		
		if (isset($result[$previous_campaign_id]))
		{
			$cycle_days_array = $result[$previous_campaign_id]['cycle_days'];
			$keys = array_keys($cycle_days_array);
			$schedule = "COMPLETED";
			$has_next_active_flight = false;

			foreach (array_keys($keys) AS $k )
			{
				$flight_start_date = $keys[$k];
				$this_flight = $cycle_days_array[$flight_start_date];

				if ($k == 0 && strtotime($flight_start_date) > strtotime($report_date))
				{
					$schedule = "LAUNCHING";
					break;
				}
				else
				{
					if ($this_flight["is_current_flight"])
					{
						if ($this_flight["flight_budget"] > 0)
						{
							$schedule = "LIVE";
							$dStart = DateTime::createFromFormat('Y-m-d', $report_date);
							$dEnd  = DateTime::createFromFormat('Y-m-d', $this_flight["flight_end_date"]);
							$dDiff = $dStart->diff($dEnd);
							if ($dStart < $dEnd && $dDiff->days < 7)
							{
								$schedule = "ENDING";
							}
							elseif($dStart > $dEnd)
							{
								$schedule = "COMPLETED";
							}
							else
							{
								break;
							}
						}
						elseif(isset($keys[$k+1]) && isset($cycle_days_array[$keys[$k+1]]))
						{
							$schedule = "PAUSED";
							$has_next_active_flight = false;
						}
					}
					else
					{
						if ($this_flight["flight_budget"] > 0)
						{
							$has_next_active_flight = true;

							if ($schedule == "ENDING")
							{
								$schedule = "LIVE";
							}
							elseif ($schedule != "PAUSED")
							{
								$report_date_in_millisec = strtotime($report_date);
								$flight_end_date_in_millisec = strtotime($this_flight["flight_end_date"]);
								if ($report_date_in_millisec > $flight_end_date_in_millisec)
								{
									$schedule = "COMPLETED";
								}				
							}
						}			
					}				
				}
			}

			if (!$has_next_active_flight && $schedule == "PAUSED")
			{
				$schedule = "COMPLETED";
			}
			
			$result[$previous_campaign_id]['schedule'] = $schedule;
		}
	}
	
	public function check_campaign_is_geofencing($campaign_id)
	{
			$sql="
			SELECT
				SUM(ifsd.geofencing_impressions) AS geofencing_impressions_total
			 FROM
				campaigns_time_series cts
				JOIN io_flight_subproduct_details AS ifsd
					ON (cts.id = ifsd.io_timeseries_id)
			WHERE cts.campaigns_id IN ($campaign_id)
			";

		$total = 0;
		$query = $this->db->query($sql);
		$result = $query->result_array();
		if($result[0]['geofencing_impressions_total'] > 0 || $result[0]['geofencing_impressions_total'] != null)
		    $total = 1;
		
		return $total;
	}
	
	private function calculate_otis_for_campaign(&$result, $previous_campaign_id, $report_date)
	{
		if (isset($result[$previous_campaign_id]))
		{
			//This Flight - Total number of days campaign will run
			$this_flight_start_date = "";
			if (isset($result[$previous_campaign_id]['this_flight']['flight_start_date']))
			{
				$this_flight_start_date = $result[$previous_campaign_id]['this_flight']['flight_start_date'];
			}
			
			$num_of_run_days_for_campaign_for_this_flight = 0;
			if (!empty($this_flight_start_date))
			{
				$num_of_run_days_for_campaign_for_this_flight = $result[$previous_campaign_id]['cycle_days'][$this_flight_start_date]['num_of_days_in_flight'];
			}
			
			//Number of days campaign running.
			$num_of_days_cmpn_running_for_this_flight = 0;
			
			//All Time - Total number of days campaign will run
			$num_of_run_days_for_campaign = 0;
			$temp_array = $result[$previous_campaign_id]['cycle_days'];
			$campaign_start_date = "";
				
			foreach ($temp_array AS $cycle_date => $flight)
			{
				$num_of_run_days_for_campaign += $flight['num_of_days_in_flight'];
				
				if (empty($campaign_start_date))
				{
					$campaign_start_date = $cycle_date;
				}
				
				if ($this_flight_start_date == $cycle_date)
				{
					$dStart = new DateTime($cycle_date);
					$dEnd  = new DateTime($report_date);
					$dDiff = $dStart->diff($dEnd);
					$num_of_days_cmpn_running_for_this_flight = $dDiff->days + 1;
				}
			}
			
			//If last flight's end date < report date, then total # of days campaign is running should be same as total run days for campaign.						
			if (strtotime($flight['flight_end_date']) < strtotime($report_date))
			{
				$total_num_of_days_cmpn_running = $num_of_run_days_for_campaign;
			}
			else
			{
				$dStart = new DateTime($campaign_start_date);
				$dEnd  = new DateTime($report_date);
				$dDiff = $dStart->diff($dEnd);
				$total_num_of_days_cmpn_running = $dDiff->days + 1;
			}
			 
			//All Time - campaign OTI
			$all_time_campaign_oti = 0;
			if ($result[$previous_campaign_id]['all_time']['campaign']['budget'] > 0)
			{
				//If realized amount > budget, set realized amount to budget
				if($result[$previous_campaign_id]['all_time']['campaign']['realized'] > 
					$result[$previous_campaign_id]['all_time']['campaign']['budget'])
				{
					$result[$previous_campaign_id]['all_time']['campaign']['realized'] = $result[$previous_campaign_id]['all_time']['campaign']['budget'];
				}
				
				$all_time_campaign_oti = round((
								(
									$result[$previous_campaign_id]['all_time']['campaign']['realized']
								) / 
								( 
									(
										($result[$previous_campaign_id]['all_time']['campaign']['budget'])/$num_of_run_days_for_campaign
									) * $total_num_of_days_cmpn_running
								)
							) * 100);
			}
			
			//All Time - audience extension OTI
			$all_time_audience_ext_oti = 0;
			if ($result[$previous_campaign_id]['all_time']['audience_ext']['budget'] > 0)
			{
				//If realized amount > budget, set realized amount to budget
				if($result[$previous_campaign_id]['all_time']['audience_ext']['realized'] > 
					$result[$previous_campaign_id]['all_time']['audience_ext']['budget'])
				{
					$result[$previous_campaign_id]['all_time']['audience_ext']['realized'] = $result[$previous_campaign_id]['all_time']['audience_ext']['budget'];
				}
				
				$all_time_audience_ext_oti =	round((
									(
										$result[$previous_campaign_id]['all_time']['audience_ext']['realized']
									) / 
									( 
										(
											($result[$previous_campaign_id]['all_time']['audience_ext']['budget'])/$num_of_run_days_for_campaign
										) * $total_num_of_days_cmpn_running
									)
								) * 100);
			}
			
			//All Time - o&o OTI
			$all_time_o_and_o_oti = 0;
			if ($result[$previous_campaign_id]['all_time']['o_and_o']['budget'] > 0)
			{
				//If realized amount > budget, set realized amount to budget
				if($result[$previous_campaign_id]['all_time']['o_and_o']['realized'] > 
					$result[$previous_campaign_id]['all_time']['o_and_o']['budget'])
				{
					$result[$previous_campaign_id]['all_time']['o_and_o']['realized']=$result[$previous_campaign_id]['all_time']['o_and_o']['budget'];
				}
				
				$all_time_o_and_o_oti =		round((
									(
										$result[$previous_campaign_id]['all_time']['o_and_o']['realized']
									) / 
									( 
										(
											($result[$previous_campaign_id]['all_time']['o_and_o']['budget'])/$num_of_run_days_for_campaign
										) * $total_num_of_days_cmpn_running
									)
								) * 100);
			}
			
			//This flight - campaign OTI
			$this_flight_campaign_oti = 0;
			if ($result[$previous_campaign_id]['this_flight']['campaign']['budget'] > 0 && $num_of_days_cmpn_running_for_this_flight > 0)
			{
				//If realized amount > budget, set realized amount to budget
				if($result[$previous_campaign_id]['this_flight']['campaign']['realized'] > 
					$result[$previous_campaign_id]['this_flight']['campaign']['budget'])
				{
					$result[$previous_campaign_id]['this_flight']['campaign']['realized']=$result[$previous_campaign_id]['this_flight']['campaign']['budget'];
				}
				
				$this_flight_campaign_oti =	round((
									(
										$result[$previous_campaign_id]['this_flight']['campaign']['realized']
									) / 
									( 
										(
											($result[$previous_campaign_id]['this_flight']['campaign']['budget'])/$num_of_run_days_for_campaign_for_this_flight
										) * $num_of_days_cmpn_running_for_this_flight
									)
								) * 100);
			}
			
			//This flight - campaign OTI
			$this_flight_audience_ext_oti = 0;
			if ($result[$previous_campaign_id]['this_flight']['audience_ext']['budget'] > 0 && $num_of_days_cmpn_running_for_this_flight > 0)
			{
				//If realized amount > budget, set realized amount to budget
				if($result[$previous_campaign_id]['this_flight']['audience_ext']['realized'] > 
					$result[$previous_campaign_id]['this_flight']['audience_ext']['budget'])
				{
					$result[$previous_campaign_id]['this_flight']['audience_ext']['realized']=$result[$previous_campaign_id]['this_flight']['audience_ext']['budget'];
				}
				$this_flight_audience_ext_oti =	round((
									(
										$result[$previous_campaign_id]['this_flight']['audience_ext']['realized']
									) / 
									( 
										(
											($result[$previous_campaign_id]['this_flight']['audience_ext']['budget'])/$num_of_run_days_for_campaign_for_this_flight
										) * $num_of_days_cmpn_running_for_this_flight
									)
								) * 100);
			}
			
			//This flight - campaign OTI
			$this_flight_o_and_o_oti = 0;
			if ($result[$previous_campaign_id]['this_flight']['o_and_o']['budget'] > 0 && $num_of_days_cmpn_running_for_this_flight > 0)
			{
				//This flight - campaign OTI
				if($result[$previous_campaign_id]['this_flight']['o_and_o']['realized'] > 
					$result[$previous_campaign_id]['this_flight']['o_and_o']['budget'])
				{
					$result[$previous_campaign_id]['this_flight']['o_and_o']['realized']=$result[$previous_campaign_id]['this_flight']['o_and_o']['budget'];
				}
				
				
				$this_flight_o_and_o_oti =	round((
									(
										$result[$previous_campaign_id]['this_flight']['o_and_o']['realized']
									) / 
									( 
										(
											($result[$previous_campaign_id]['this_flight']['o_and_o']['budget'])/$num_of_run_days_for_campaign_for_this_flight
										) * $num_of_days_cmpn_running_for_this_flight
									)
								) * 100);
			}
			
			//Save OTI numbers in result
			$result[$previous_campaign_id]['all_time']['campaign']['oti'] = $all_time_campaign_oti;
			$result[$previous_campaign_id]['all_time']['audience_ext']['oti'] = $all_time_audience_ext_oti;
			$result[$previous_campaign_id]['all_time']['o_and_o']['oti'] = $all_time_o_and_o_oti;
			$result[$previous_campaign_id]['this_flight']['campaign']['oti'] = $this_flight_campaign_oti;
			$result[$previous_campaign_id]['this_flight']['audience_ext']['oti'] = $this_flight_audience_ext_oti;
			$result[$previous_campaign_id]['this_flight']['o_and_o']['oti'] = $this_flight_o_and_o_oti;
		}
	}
}