<?php

class Advertisers_main_model extends CI_Model {

	public function __construct(){
		$this->load->database();                
	}

	public function get_advertisers_main_data_by_advertiser_owner($user_id, $start, $end, $offset, $limit)
	{
		$binding_array = array();
		$advertiser_where_sql = "";
		$advertiser_join_sql = "";
		if($user_id == 'all')
		{
			$binding_array = array($start, $end, $offset, $limit, $start, $end, $start, $end, $start, $end);
			$advertiser_join_sql = "Advertisers ad";
		}
		else
		{
			$binding_array = array($user_id, $start, $end, $offset, $limit, $user_id, $start, $end, $user_id, $start, $end, $user_id, $start, $end);
			$advertiser_where_sql = "us.id = ? AND";
			$advertiser_join_sql = "
				users us
				JOIN users_join_advertisers_for_advertiser_owners ao
					ON us.id = ao.user_id
				JOIN Advertisers ad
					ON ao.advertiser_id = ad.id";
		}
		$query = "
			SELECT
				pd.partner_name AS partner_name,
				CONCAT(us2.firstname, ' ', us2.lastname) AS username,
				ad.id AS advertiser_id,
				ad.Name AS advertiser_name,
				ca.id AS campaign_id,
				ca_query.num_active_campaigns as num_active_campaigns,
				rc.date AS date,
				SUM(rc.impressions) AS impressions,
				SUM(IF(ag.target_type LIKE '%Pre-Roll%', rc.impressions, 0)) AS pr_impressions,
				SUM(IF(ag.target_type NOT LIKE '%Pre-Roll%', rc.impressions, 0)) AS non_pr_impressions,
				SUM(IF(ag.target_type LIKE '%Pre-Roll%', rc.clicks, 0)) AS pr_clicks,
				SUM(IF(ag.target_type NOT LIKE '%Pre-Roll%', rc.clicks, 0)) AS non_pr_clicks,
				order_query.avg_imp AS avg_impressions,
				SUM(IF(ag.target_type LIKE '%Pre-Roll%', rc.post_impression_conversion, 0)) AS pr_view_throughs,
				SUM(IF(ag.target_type NOT LIKE '%Pre-Roll%', rc.post_impression_conversion, 0)) AS non_pr_view_throughs,
				COALESCE(vp_query.100_percent_completions, 0) AS pre_roll_completions,
				COALESCE(er_query.ad_interactions, 0) AS ad_interactions
			FROM
				".$advertiser_join_sql."
				JOIN Campaigns ca
					ON ad.id = ca.business_id
				JOIN AdGroups ag
					ON ca.id = ag.campaign_id
				JOIN report_cached_adgroup_date rc
					ON ag.ID = rc.adgroup_id
				LEFT JOIN users us2
					ON ad.sales_person = us2.id
				LEFT JOIN wl_partner_details pd
					ON us2.partner_id = pd.id
				JOIN (
					SELECT
						ad.id as advertiser_id,
						SUM(IF(ca.ignore_for_healthcheck = 0, 1, 0)) as num_active_campaigns
					FROM
						Advertisers ad
						JOIN Campaigns ca
							ON ad.id = ca.business_id
						GROUP BY advertiser_id
				) AS ca_query
					ON ad.id = ca_query.advertiser_id
				JOIN
				(
					SELECT
						avg_query.id AS id,
						AVG(avg_query.impressions) AS avg_imp
					FROM
					(
						SELECT
							ca.business_id AS id,
							rc.date AS date,
							SUM(rc.impressions) AS impressions
						FROM
							".$advertiser_join_sql."
							JOIN Campaigns AS ca
								ON ad.id = ca.business_id
							JOIN AdGroups AS ag
								ON ca.id = ag.campaign_id
							JOIN report_cached_adgroup_date AS rc
								ON ag.ID = rc.adgroup_id
						WHERE
							".$advertiser_where_sql."
							rc.date BETWEEN ? AND ?
						GROUP BY 
							id,
							date
                    ) avg_query
                    GROUP BY avg_query.id
					ORDER BY avg_imp DESC
					LIMIT ?, ?
                ) AS order_query
                    ON ad.id = order_query.id
				LEFT JOIN
				(
					SELECT
						ad.id AS advertiser_id,
						vp.date AS date,
						SUM(vp.100_percent_count) AS 100_percent_completions
					FROM
						".$advertiser_join_sql."
						JOIN Campaigns ca
							ON ad.id = ca.business_id
						JOIN AdGroups ag
							ON ca.id = ag.campaign_id
						JOIN report_video_play_records vp
							ON ag.ID = vp.AdGroupID
					WHERE 
						".$advertiser_where_sql."
						ag.target_type LIKE '%Pre-Roll%' AND
						vp.date BETWEEN ? AND ?
					GROUP BY
						advertiser_id,
						date
				) AS vp_query
					ON ad.id = vp_query.advertiser_id AND rc.date = vp_query.date
				LEFT JOIN
				(
					SELECT
						ad.id AS advertiser_id,
						er.date AS date,
						SUM(er.total) AS ad_interactions
					FROM
						".$advertiser_join_sql."
						JOIN Campaigns ca 
							ON ad.id = ca.business_id
						JOIN cup_versions cv
							ON ca.id = cv.campaign_id
						JOIN cup_creatives cc
							ON cv.id = cc.version_id
						JOIN engagement_records er
							ON cc.id = er.creative_id
					WHERE
						".$advertiser_where_sql."
						er.date BETWEEN ? AND ? AND
						er.id > 0 AND
						(er.engagement_type IN (1, 6) OR (er.engagement_type = 5 AND er.value >= 1))
					GROUP BY
						advertiser_id,
						date
				) AS er_query
					ON ad.id = er_query.advertiser_id AND rc.date = er_query.date
			WHERE
				".$advertiser_where_sql."
				rc.date BETWEEN ? AND ?
			GROUP BY
				ad.id, 
				rc.date
			ORDER BY
				avg_impressions DESC, 
				ad.id";
		$result = $this->db->query($query, $binding_array);
		if($result !== false)
		{
			return $result->result_array();
		}
		return false;
	}
	
	public function get_ops_owner_data_by_email($preload_user)
	{
		$query = "
			SELECT
				us.id id,
				CONCAT(us.firstname, ' ', us.lastname) as text
			FROM
				users us
				JOIN users_join_advertisers_for_advertiser_owners ao
					ON us.id = ao.user_id
			WHERE
				LOWER(us.email) = LOWER(?)
			GROUP BY user_id";
		$response = $this->db->query($query, $preload_user);
		if($response !== false && $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}
	
	public function get_ops_owner_data_by_id($preload_user)
	{
		$query = "
			SELECT
				us.id id,
				CONCAT(us.firstname, ' ', us.lastname) as text
			FROM
				users us
				JOIN users_join_advertisers_for_advertiser_owners ao
					ON us.id = ao.user_id
			WHERE
				us.id = ?
			GROUP BY user_id";
		$response = $this->db->query($query, $preload_user);
		if($response !== false && $response->num_rows() > 0)
		{
			return $response->row_array();
		}
		return false;
	}

	public function get_ops_owner_data_by_search_term($term, $start, $limit)
	{
		$query = "
			SELECT
				us.id AS id,
				CONCAT(us.firstname, ' ', us.lastname) AS text
			FROM
				users us
				JOIN users_join_advertisers_for_advertiser_owners ao
					ON us.id = ao.user_id
			WHERE
				role IN ('ADMIN', 'OPS', 'CREATIVE') AND
				concat_ws(' ', LOWER(firstname), LOWER(lastname)) LIKE LOWER(?)
			GROUP BY
				us.id
			LIMIT ?, ?";
		$result = $this->db->query($query, array($term, $start, $limit));
		if($result !== false)
		{
			return $result->result_array();
		}
		return array();
	}
}

?>
