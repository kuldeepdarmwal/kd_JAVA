<?php
class screen_shot_approval_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
	}

	public function get_all_advertisers_screen_shots_stats()
	{
		$bindings = array();
		
		$query = '
			SELECT 
			z.id AS id,
			z.business_name AS name,
			SUM(z.num_unset) AS num_unset,
			SUM(z.num_approved) AS num_approved,
			SUM(z.total) AS total
			FROM ((
				SELECT 
					a.id AS id,
					a.Name AS business_name,
					SUM(CASE WHEN ISNULL(ess.is_approved) THEN 1 ELSE 0 END) AS num_unset,
					SUM(CASE WHEN ess.is_approved = 1 THEN 1 ELSE 0 END) AS num_approved,
					COUNT(ess.id) AS total
				FROM Advertisers AS a JOIN
					Campaigns AS c ON (a.id = c.business_id) JOIN
					ad_verify_screen_shots AS ess ON (ess.campaign_id = c.id)
				WHERE 
					1 
				GROUP BY a.id
				)
				UNION ALL
				(
				SELECT 
					a.id AS id,
					a.Name AS business_name,
					0 AS num_unset,
					0 AS num_approved,
					0 AS total
				FROM Advertisers AS a
				)
			) AS z
			GROUP BY z.business_name
			ORDER BY z.business_name
			';

		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_associated_campaigns_screen_shots_stats($advertiser_id, $start_date, $end_date)
	{
		$bindings = array();

		if($advertiser_id == k_all_advertisers_special_id)
		{
			$advertisers_and_campaigns_from_sql = '
				Campaigns AS c
			';
		}
		elseif($advertiser_id == k_no_advertisers_special_id)
		{
			$advertisers_and_campaigns_from_sql = '
				Campaigns AS c
			';
		}
		else
		{
			$advertisers_and_campaigns_from_sql = '
				Advertisers AS a JOIN
				Campaigns AS c ON (a.id = c.business_id)
			';
		}

		$date_range_sql = 'ess.creation_date BETWEEN ? AND ?';
		$bindings[] = $start_date;
		$bindings[] = $end_date;

		if($advertiser_id == k_all_advertisers_special_id)
		{
			$advertisers_where_sql = '
				1
			';
		}
		elseif($advertiser_id == k_no_advertisers_special_id)
		{
			$advertisers_where_sql = '
				0
			';
		}
		else
		{
			$advertisers_where_sql = '
				a.id = ?
			';
			$bindings[] = (int)$advertiser_id;
			$bindings[] = (int)$advertiser_id;
		}

		$query = '
			SELECT 
			z.id AS id,
			z.campaign_name AS name,
			SUM(z.num_unset) AS num_unset,
			SUM(z.num_approved) AS num_approved,
			SUM(z.total) AS total
			FROM ((
				SELECT 
					c.id AS id,
					c.Name AS campaign_name,
					SUM(CASE WHEN ISNULL(ess.is_approved) THEN 1 ELSE 0 END) AS num_unset,
					SUM(CASE WHEN ess.is_approved = 1 THEN 1 ELSE 0 END) AS num_approved,
					COUNT(ess.id) AS total
				FROM 
					'.$advertisers_and_campaigns_from_sql.'
					JOIN
					ad_verify_screen_shots AS ess ON (ess.campaign_id = c.id)
				WHERE 
					'.$date_range_sql.' 
					AND 
					'.$advertisers_where_sql.'
				GROUP BY c.id
				)
				UNION ALL
				(
				SELECT 
					c.id AS id,
					c.Name AS campaign_name,
					0 AS num_unset,
					0 AS num_approved,
					0 AS total
				FROM 
					'.$advertisers_and_campaigns_from_sql.'
				WHERE
					'.$advertisers_where_sql.'
				)
			) AS z
			GROUP BY z.campaign_name
			ORDER BY z.campaign_name
		';

		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function set_screen_shot_status($screen_shot_id, $is_approved)
	{
		$bindings = array();
		$bindings[] = $is_approved;
		$bindings[] = (int)$screen_shot_id;
		$query = 'UPDATE IGNORE
				ad_verify_screen_shots AS ess
			SET ess.is_approved = ?
			WHERE ess.id = ?
			';
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_num_screen_shots(
		$advertiser_id,
		$campaign_id,
		$start_date,
		$end_date,
		$approval_status
	)
	{
		$bindings = array();
		$status_sql = '';
		switch($approval_status)
		{
			case 'unreviewed':
				$status_sql = 'ISNULL(ess.is_approved)';
				break;
			case 'all':
				$status_sql = '1';
				break;
			case 'approved':
				$status_sql = 'ess.is_approved = TRUE';
				break;
			case 'disapproved':
				$status_sql = 'ess.is_approved = FALSE';
				break;
			default:
				die('Unhandled approval_status: '.$approval_status);
		}

		$date_range_sql = '';
		$bindings[] = $start_date;
		$bindings[] = $end_date;
		$date_range_sql = 'AND ess.creation_date BETWEEN ? AND ?';

		$campaign_filter_sql = '';
		$advertiser_filter_sql = '';	

		$query = 'SELECT 
								COUNT(ess.id) AS num_screen_shots
							FROM ad_verify_screen_shots AS ess JOIN 
								Campaigns AS c ON (ess.campaign_id = c.id) JOIN 
								Advertisers AS a ON (c.business_id = a.id)
							WHERE 
								'.$status_sql.' 
								'.$date_range_sql.' 
								'.$advertiser_filter_sql.'
								'.$campaign_filter_sql.'
								';
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_screen_shots_data(
		$advertiser_id,
		$campaign_id,
		$start_date,
		$end_date,
		$approval_status,
		$page_index,
		$num_screen_shots, // 0 means all i.e. no LIMIT
		$primary_sort_column
	)
	{
		
		$advertiser_id = (int)$advertiser_id;
		$campaign_id = (int)$campaign_id;
		$page_index = (int)$page_index;
		$num_screen_shots = (int)$num_screen_shots;

		$page_start = 0;
		if($page_index > 0)
		{
			$page_start = ($page_index - 1) * $num_screen_shots;
		}

		$bindings = array();
		$status_sql = '';
		//echo $approval_status;die();
		switch($approval_status)
		{
			case 'unreviewed':
				$status_sql = 'ISNULL(ess.is_approved)';
				break;
			case 'all':
				$status_sql = '1';
				break;
			case 'approved':
				$status_sql = 'ess.is_approved = TRUE';
				break;
			case 'disapproved':
				$status_sql = 'ess.is_approved = FALSE';
				break;
			default:
				die('Unhandled approval_status: '.$approval_status);
		}

		$date_range_sql = '';
		if($start_date && $end_date)
		{
			$bindings[] = $start_date;
			$bindings[] = $end_date;
			$date_range_sql = 'AND ess.creation_date BETWEEN ? AND ?';
		}
		
		$campaign_filter_sql = '';
		$advertiser_filter_sql = '';
		
		if($advertiser_id)
		{
			$advertiser_filter_sql = 'AND a.id = ?'."\n";
			$bindings[] = (int)$advertiser_id;
		}
		if($campaign_id)
		{
			$campaign_filter_sql = 'AND c.id = ?'."\n";
			$bindings[] = (int)$campaign_id;
		}
		$sort_order_sql = '';
		switch($primary_sort_column)
		{
			case 'business_name':
				$sort_order_sql = 'ORDER BY
								business_name ASC,
								campaign_name ASC,
								base_url ASC,
								creation_date DESC,
								is_approved DESC
								';
				break;
			case 'campaign_name':
				$sort_order_sql = 'ORDER BY
								campaign_name ASC,
								base_url ASC,
								creation_date DESC,
								is_approved DESC
								';
				break;
			case 'date':
				$sort_order_sql = 'ORDER BY
								creation_date DESC,
								business_name ASC,
								campaign_name ASC,
								base_url ASC,
								is_approved DESC
								';
				break;
			case 'status':
				$sort_order_sql = 'ORDER BY
								is_approved DESC,
								business_name ASC,
								campaign_name ASC,
								base_url ASC,
								creation_date DESC
								';
				break;
			case 'url':
				$sort_order_sql = 'ORDER BY
								base_url ASC,
								business_name ASC,
								campaign_name ASC,
								creation_date DESC,
								is_approved DESC 
								';
				break;
			default:
				die('Unhandled sorting method: '.$primary_sort_column);
		}

		$screen_shots_page_sql = '';
		if($num_screen_shots != 0)
		{
			$bindings[] = $page_start;
			$bindings[] = $num_screen_shots;

			$screen_shots_page_sql = 'LIMIT ?, ?';
		}
		elseif($num_screen_shots == 0 && $page_start == 0)
		{
			$screen_shots_page_sql = '';
		}
		elseif($num_screen_shots == 0 && $page_start != 0)
		{
			$bindings[] = $page_start;
			$screen_shots_page_sql = 'LIMIT ?, 999999';
		}
		else
		{
			die('Unhandled logic: page_start('.$page_start.'), num_screen_shots('.$num_screen_shots.')');
		}
		if(!$page_index)
		{
			$screen_shots_page_sql = '';
		}

		$query = 'SELECT 
								ess.id AS id,
								ess.file_name AS file_name,
								ess.creation_date AS creation_date,
								c.Name AS campaign_name,
								a.Name AS business_name,
								ess.base_url AS base_url,
								ess.is_approved AS is_approved
							FROM ad_verify_screen_shots AS ess JOIN 
								Campaigns AS c ON (ess.campaign_id = c.id) JOIN 
								Advertisers AS a ON (c.business_id = a.id)
							WHERE 
								'.$status_sql.' 
								'.$date_range_sql.' 
								'.$advertiser_filter_sql.'
								'.$campaign_filter_sql.'
								'.$sort_order_sql.'
								'.$screen_shots_page_sql.'
								';
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	//This function will fetch all advertiser list.
	public function get_advertisers_total_stat()
	{
		$bindings = array();
		$query = '
			SELECT 
			z.id AS id,
			z.business_name AS name,
			SUM(z.num_unset) AS num_unset,
			SUM(z.num_approved) AS num_approved,
			SUM(z.total) AS total
			FROM ((
				SELECT 
					a.id AS id,
					a.Name AS business_name,
					SUM(CASE WHEN ISNULL(ess.is_approved) THEN 1 ELSE 0 END) AS num_unset,
					SUM(CASE WHEN ess.is_approved = 1 THEN 1 ELSE 0 END) AS num_approved,
					COUNT(ess.id) AS total
				FROM Advertisers AS a JOIN
					Campaigns AS c ON (a.id = c.business_id) JOIN
					ad_verify_screen_shots AS ess ON (ess.campaign_id = c.id)
				WHERE 
					1
				GROUP BY a.id
				)
				UNION ALL
				(
				SELECT 
					a.id AS id,
					a.Name AS business_name,
					0 AS num_unset,
					0 AS num_approved,
					0 AS total
				FROM Advertisers AS a
				)
			) AS z
			GROUP BY z.business_name
			ORDER BY z.business_name
			';

		$response = $this->db->query($query, $bindings);
		$result = "(0, 0, 0)";
		if($response->num_rows() > 0)
		{
			 $advertiser_rows = $response->result_array();
			$unset = 0;
			$approved = 0;
			$total = 0;
			foreach($advertiser_rows as $row)
			{
				 $unset += $row['num_unset'];
				$approved += $row['num_approved'];
				$total += $row['total'];
			}
			$result =  "(".$unset.", ".$approved.", ".$total.")";
		}
		return $result;
	}
	//This function will fetch all campaign list.
	public function get_campaign_total_stat($search_term, $start, $limit, $advertiser_id)
	{
		$bindings = array();
		$bindings[] = $search_term;		
		$bindings[] = $start;
		$bindings[] = $limit;
		if($advertiser_id)
		{
			$advertisers_and_campaigns_from_sql = '
				Advertisers AS a JOIN
				Campaigns AS c ON (a.id = c.business_id)
			';
			$advertisers_where_sql = '
				a.id = '.(int)$advertiser_id.'
			';
		}
		else
		{
			$advertisers_and_campaigns_from_sql = '
				Campaigns AS c
			';
			$advertisers_where_sql = '
				1
			';
		}
		$query = '
			SELECT 
			z.id AS id,
			z.campaign_name AS name,
			SUM(z.num_unset) AS num_unset,
			SUM(z.num_approved) AS num_approved,
			SUM(z.total) AS total
			FROM ((
				SELECT 
					c.id AS id,
					c.Name AS campaign_name,
					SUM(CASE WHEN ISNULL(ess.is_approved) THEN 1 ELSE 0 END) AS num_unset,
					SUM(CASE WHEN ess.is_approved = 1 THEN 1 ELSE 0 END) AS num_approved,
					COUNT(ess.id) AS total
				FROM 
					'.$advertisers_and_campaigns_from_sql.'
					JOIN
					ad_verify_screen_shots AS ess ON (ess.campaign_id = c.id)
				WHERE 
					1 
					AND 
					'.$advertisers_where_sql.'
				GROUP BY c.id
				)
				UNION ALL
				(
				SELECT 
					c.id AS id,
					c.Name AS campaign_name,
					0 AS num_unset,
					0 AS num_approved,
					0 AS total
				FROM 
					'.$advertisers_and_campaigns_from_sql.'
				WHERE
					'.$advertisers_where_sql.'
				)
			) AS z
			WHERE
				z.campaign_name LIKE ?                                
			GROUP BY z.campaign_name
			ORDER BY z.campaign_name                        
			LIMIT ?, ?';

		$response = $this->db->query($query, $bindings);
		$result = "";
		if($response->num_rows() > 0)
		{
			$advertiser_rows = $response->result_array();
			$result = array();
			foreach($advertiser_rows as $row)
			{
				$result[] = array("id" => $row['id'], "text" => $row['name'], "id_list" => " (".$row['num_unset'].",".$row['num_approved'].",".$row['total'].")");
			}
			return $result;
		}
		return $result;
	}
	
	//This function will fetch all campaign list.
	public function get_all_campaign($advertiser_id)
	{
		$bindings = array();
		if($advertiser_id)
		{
			$advertisers_and_campaigns_from_sql = '
				Advertisers AS a JOIN
				Campaigns AS c ON (a.id = c.business_id)
			';
			$advertisers_where_sql = '
				a.id = '.(int)$advertiser_id.'
			';
		}
		else
		{
			$advertisers_and_campaigns_from_sql = '
				Campaigns AS c
			';
			$advertisers_where_sql = '
				1
			';
		}
		$query = '
			SELECT 
			z.id AS id,
			z.campaign_name AS name,
			SUM(z.num_unset) AS num_unset,
			SUM(z.num_approved) AS num_approved,
			SUM(z.total) AS total
			FROM ((
				SELECT 
					c.id AS id,
					c.Name AS campaign_name,
					SUM(CASE WHEN ISNULL(ess.is_approved) THEN 1 ELSE 0 END) AS num_unset,
					SUM(CASE WHEN ess.is_approved = 1 THEN 1 ELSE 0 END) AS num_approved,
					COUNT(ess.id) AS total
				FROM 
					'.$advertisers_and_campaigns_from_sql.'
					JOIN
					ad_verify_screen_shots AS ess ON (ess.campaign_id = c.id)
				WHERE 
					1 
					AND 
					'.$advertisers_where_sql.'
				GROUP BY c.id
				)
				UNION ALL
				(
				SELECT 
					c.id AS id,
					c.Name AS campaign_name,
					0 AS num_unset,
					0 AS num_approved,
					0 AS total
				FROM 
					'.$advertisers_and_campaigns_from_sql.'
				WHERE
					'.$advertisers_where_sql.'
				)
			) AS z
			WHERE
				1
			GROUP BY z.campaign_name
			ORDER BY z.campaign_name
			';
		
		$response = $this->db->query($query, $bindings);
		$result = "(0, 0, 0)";
		if($response->num_rows() > 0)
		{
			$campaign_rows = $response->result_array();
			$unset = 0;
			$approved = 0;
			$total = 0;
			foreach($campaign_rows as $row)
			{
				$unset += $row['num_unset'];
				$approved += $row['num_approved'];
				$total += $row['total'];
			}
			$result =  "(".$unset.", ".$approved.", ".$total.")";
		}

		return $result;
	}
	
	//This function will fetch advertiser.
	public function get_advertisers_details_for_select2($search_term, $start, $limit)
	{
		$bindings = array();
		$bindings[] = $search_term;
		$bindings[] = $start;
		$bindings[] = $limit;
		$query = '
			SELECT 
			z.id AS id,
			z.business_name AS name,
			SUM(z.num_unset) AS num_unset,
			SUM(z.num_approved) AS num_approved,
			SUM(z.total) AS total
			FROM ((
				SELECT 
					a.id AS id,
					a.Name AS business_name,
					SUM(CASE WHEN ISNULL(ess.is_approved) THEN 1 ELSE 0 END) AS num_unset,
					SUM(CASE WHEN ess.is_approved = 1 THEN 1 ELSE 0 END) AS num_approved,
					COUNT(ess.id) AS total
				FROM Advertisers AS a JOIN
					Campaigns AS c ON (a.id = c.business_id) JOIN
					ad_verify_screen_shots AS ess ON (ess.campaign_id = c.id)
				WHERE 
					1 
				GROUP BY a.id
				)
				UNION ALL
				(
				SELECT 
					a.id AS id,
					a.Name AS business_name,
					0 AS num_unset,
					0 AS num_approved,
					0 AS total
				FROM Advertisers AS a
				)
			) AS z
			WHERE
				z.business_name LIKE ?
			GROUP BY z.business_name
			ORDER BY z.business_name
			LIMIT ?, ?';

		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			$advertiser_rows = $response->result_array();
			$result = array();
			foreach($advertiser_rows as $row)
			{
				$result[] = array("id" => $row['id'], "text" => $row['name'], "id_list" => " (".$row['num_unset'].",".$row['num_approved'].",".$row['total'].")");
			}
			return $result;
		}
		return array();
	}
}