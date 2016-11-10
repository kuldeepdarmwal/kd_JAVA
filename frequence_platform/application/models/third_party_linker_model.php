<?php
class Third_party_linker_model extends CI_Model {

	public function __construct(){
		$this->load->database();
		$this->load->helper('report_v2_model_helper');
	}

	public function get_allowed_advertisers_and_accounts($user_id, $role, $is_group_super)
	{
		$campaigns = array();
		$binding_array = array();
		$get_accounts_and_advertisers_query = "";
		$group_super_condition_sql = "";

		if($role == "sales")
		{
			$binding_array = array($user_id, $user_id);
			$group_super_condition_sql = "";

			$get_advertiser_rows_query = "
			SELECT DISTINCT
					CONCAT('advertiser_row_',a.id) as DT_RowId,
					NULL as account_only_id,
					a.id as f_advertiser_id,
					COALESCE(pd.partner_name, pd2.partner_name) as partner_name, 
					CONCAT(u3.firstname,' ',u3.lastname) as ae_name,
					a.Name as f_advertiser_name,
					CONCAT('[',
						GROUP_CONCAT(
							CONCAT('{\"id\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.id WHEN 2 THEN cm.frq_id END, '\",
							\"acct_name\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.customer WHEN 2 THEN cm.friendly_dealer_name END, '\",
							\"tp_source\":\"',tp.third_party_source, '\",
							\"acct_id\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.account_id WHEN 2 THEN cm.frq_id END,'\"}')
						),
					']') as tmpi_accounts_object 
				FROM 
					users u
					LEFT JOIN wl_partner_owners AS po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy AS h 
						ON  po.partner_id = h.ancestor_id
					JOIN wl_partner_details AS pd
						ON h.descendant_id = pd.id
					JOIN users u2 
						ON pd.id = u2.partner_id
					RIGHT JOIN Advertisers AS a
						ON (u2.id = a.sales_person)
					JOIN users AS u3
						ON a.sales_person = u3.id
					JOIN wl_partner_details AS pd2
						ON u3.partner_id = pd2.id
					LEFT JOIN tp_advertisers_join_third_party_account AS tp
						ON tp.frq_advertiser_id = a.id
					LEFT JOIN tp_tmpi_accounts AS tmpi
						ON tp.frq_third_party_account_id = tmpi.id AND tp.third_party_source = 1
					LEFT JOIN tp_cm_dealers AS cm
						ON tp.frq_third_party_account_id = cm.frq_id AND tp.third_party_source = 2
				WHERE 
					(u.id = ? OR a.sales_person = ?)
				GROUP BY a.id ";

			if($is_group_super == 1)
			{
				$binding_array[] = $user_id;
				$binding_array[] = $user_id;
				$group_super_condition_sql = "
					UNION
					SELECT DISTINCT
						CONCAT('advertiser_row_',a.id) as DT_RowId,
						NULL as account_only_id,
						a.id as f_advertiser_id,
						COALESCE(pd.partner_name, pd2.partner_name) as partner_name, 
						CONCAT(u3.firstname,' ',u3.lastname) as ae_name,
						a.Name as f_advertiser_name,
					CONCAT('[',
						GROUP_CONCAT(
							CONCAT('{\"id\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.id WHEN 2 THEN cm.frq_id END, '\",
							\"acct_name\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.customer WHEN 2 THEN cm.friendly_dealer_name END, '\",
							\"tp_source\":\"',tp.third_party_source, '\",
							\"acct_id\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.account_id WHEN 2 THEN cm.frq_id END,'\"}')
						),
					']') as tmpi_accounts_object 
					FROM 
						users u
						JOIN wl_partner_hierarchy AS h 
							ON  u.partner_id = h.ancestor_id
						JOIN wl_partner_details AS pd
							ON h.descendant_id = pd.id
						JOIN users u2 
							ON pd.id = u2.partner_id
						RIGHT JOIN Advertisers AS a
							ON (u2.id = a.sales_person)
						JOIN users AS u3
							ON a.sales_person = u3.id
						JOIN wl_partner_details AS pd2
							ON u3.partner_id = pd2.id
						LEFT JOIN tp_advertisers_join_third_party_account AS tp
							ON tp.frq_advertiser_id = a.id
						LEFT JOIN tp_tmpi_accounts AS tmpi
							ON tp.frq_third_party_account_id = tmpi.id AND tp.third_party_source = 1
						LEFT JOIN tp_cm_dealers AS cm
							ON tp.frq_third_party_account_id = cm.frq_id AND tp.third_party_source = 2
					WHERE 
						(u.id = ? OR a.sales_person = ?)
					GROUP BY a.id";
			}
		}
		else if($role == "admin" || $role == "ops")
		{
			$get_advertiser_rows_query = "
				SELECT 
					CONCAT('advertiser_row_',a.id) as DT_RowId,
					NULL as account_only_id,
					a.id as f_advertiser_id,
					p.partner_name as partner_name, 
					CONCAT(u.firstname,' ',u.lastname) as ae_name,
					a.Name as f_advertiser_name,
					CONCAT('[',
						GROUP_CONCAT(
							CONCAT('{\"id\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.id WHEN 2 THEN cm.frq_id END, '\",
							\"acct_name\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.customer WHEN 2 THEN cm.friendly_dealer_name END, '\",
							\"tp_source\":\"',tp.third_party_source, '\",
							\"acct_id\":\"', CASE tp.third_party_source WHEN 1 THEN tmpi.account_id WHEN 2 THEN cm.frq_id END,'\"}')
						),
					']') as tmpi_accounts_object 
				FROM 
					Advertisers AS a  
					 JOIN users AS u  
						ON a.`sales_person` = u.`id` 
					 JOIN wl_partner_details AS p  
						ON u.`partner_id` = p.id 
					LEFT JOIN tp_advertisers_join_third_party_account AS tp
						ON tp.frq_advertiser_id = a.id
					LEFT JOIN tp_tmpi_accounts AS tmpi
						ON tp.frq_third_party_account_id = tmpi.id AND tp.third_party_source = 1
					LEFT JOIN tp_cm_dealers AS cm
						ON tp.frq_third_party_account_id = cm.frq_id AND tp.third_party_source = 2
				GROUP BY a.id";
		}
		else
		{
			//Unexpected user type
			return $campaigns;
		}

		$get_accounts_and_advertisers_query = $get_advertiser_rows_query.$group_super_condition_sql."
					UNION
					SELECT 
						CONCAT('account_row_1_',a.id) as DT_RowId,
						CONCAT('1_',a.id) as account_only_id,
						NULL as f_advertiser_id,
						NULL as partner_name,
						NULL as ae_name,
						NULL as f_advertiser_name,
						CONCAT('[',
							GROUP_CONCAT(
								CONCAT('{\"id\":\"', a.id, '\",
								\"acct_name\":\"',a.customer, '\",
								\"tp_source\":\"',1, '\", 
								\"acct_id\":\"',a.account_id,'\"}')
							),
						']') as tmpi_accounts_object 

					FROM tp_tmpi_accounts AS a
					LEFT JOIN tp_advertisers_join_third_party_account AS linked
					ON a.id = linked.frq_third_party_account_id AND linked.third_party_source = 1
					WHERE linked.frq_advertiser_id IS NULL
					GROUP BY a.id
					UNION
					SELECT 
						CONCAT('account_row_2_',a.frq_id) as DT_RowId,
						CONCAT('2_',a.frq_id) as account_only_id,
						NULL as f_advertiser_id,
						NULL as partner_name,
						NULL as ae_name,
						NULL as f_advertiser_name,
						CONCAT('[',
							GROUP_CONCAT(
								CONCAT('{\"id\":\"', a.frq_id, '\",
								\"acct_name\":\"', a.friendly_dealer_name, '\", 
								\"tp_source\":\"',2, '\", 
								\"acct_id\":\"',a.frq_id,'\"}')
							),
						']') as tmpi_accounts_object 
					FROM tp_cm_dealers AS a
					LEFT JOIN tp_advertisers_join_third_party_account AS linked
					ON a.frq_id = linked.frq_third_party_account_id AND linked.third_party_source = 2
					WHERE linked.frq_advertiser_id IS NULL
					GROUP BY a.frq_id
					ORDER BY f_advertiser_name ASC
					";
		$query = $this->db->query($get_accounts_and_advertisers_query, $binding_array);
		if($query->num_rows() > 0)
		{
			$campaigns = $query->result_array();
		}
		return $campaigns;
	}



	public function get_all_third_party_accounts_for_advertiser($f_advertiser_id)
	{
		$binding_array = array();
		$accounts = array();
		$sql = "SELECT 
					DISTINCT accts.id,
					CONCAT(accts.account_name,
						' [id: ',accts.account_id, ', ',
						CASE accts.source_id WHEN 1 THEN '".tmpi_product_names::clicks."/".tmpi_product_names::inventory."/".tmpi_product_names::directories."' WHEN 2 THEN '".carmercial_product_names::content."' END,
						']') as text 
				FROM 
				(SELECT
					CONCAT('1_',a.id) AS id,
					a.customer AS account_name,
					a.account_id AS account_id,
					1 AS source_id
				FROM
					tp_tmpi_accounts AS a
					LEFT JOIN tp_advertisers_join_third_party_account AS c
						ON a.id = c.frq_third_party_account_id AND c.third_party_source = 1
				WHERE 
					c.frq_third_party_account_id IS NULL OR c.frq_advertiser_id = ?
				UNION ALL
					SELECT
						CONCAT('2_',a.frq_id) AS id,
						a.friendly_dealer_name AS account_name,
						a.frq_id AS account_id,
						2 AS source_id
					FROM
						tp_cm_dealers AS a
						LEFT JOIN tp_advertisers_join_third_party_account AS c
							ON a.frq_id = c.frq_third_party_account_id AND c.third_party_source = 2
					WHERE
						c.frq_third_party_account_id IS NULL OR c.frq_advertiser_id = ?
						) AS accts";
		$binding_array = array($f_advertiser_id, $f_advertiser_id);
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			$accounts = $query->result_array();
		}
		return $accounts;
	}

	public function get_all_linked_third_party_accounts($f_advertiser_id)
	{
		$binding_array = array();
		$accounts = array();
		$sql = "SELECT  
					CONCAT('[',
						GROUP_CONCAT(
							CONCAT('{\"id\":\"', CONCAT(tp.source_id,'_',tp.id), '\",
							 \"acct_name\":\"', tp.account_name, '\",
							 \"acct_id\":\"',tp.account_id,'\", 
							 \"tp_source\":\"',tp.source_id,'\"}')
						),
					']') as tp_accounts_object
				FROM
				(SELECT
					tmpi.id AS id,
					tmpi.customer AS account_name,
					tmpi.account_id AS account_id,
					1 AS source_id
				FROM	
					tp_advertisers_join_third_party_account AS a2a
					JOIN tp_tmpi_accounts tmpi
					ON tmpi.id = a2a.frq_third_party_account_id AND a2a.third_party_source = 1
				WHERE a2a.frq_advertiser_id = ? 
				UNION
					SELECT
						cm.frq_id AS id,
						cm.friendly_dealer_name AS account_name,
						cm.frq_id AS account_id,
						2 AS source_id
					FROM
						tp_advertisers_join_third_party_account AS a2a
						JOIN tp_cm_dealers AS cm
						ON cm.frq_id = a2a.frq_third_party_account_id AND a2a.third_party_source = 2
					WHERE
						a2a.frq_advertiser_id = ? ) AS tp";
		$binding_array = array($f_advertiser_id, $f_advertiser_id);
		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			$accounts = $query->result_array();
		}
		return $accounts;
	}

	public function update_links_to_account($accounts,$f_advertiser_id)
	{
		$is_success = false;
		$new_accounts = null;

		$sql =
		"	DELETE FROM 
				tp_advertisers_join_third_party_account 
			WHERE 
				frq_advertiser_id = ? AND 
				(third_party_source = 1 OR third_party_source = 2)
		";
		if($this->db->query($sql, $f_advertiser_id))
		{
			if($accounts)
			{
				if($this->add_links_to_account($accounts, $f_advertiser_id))
				{
					$new_accounts = $this->get_all_linked_third_party_accounts($f_advertiser_id);
					$is_success = true;
				}
			}
			else
			{
				$is_success = true;
			}
		}
		return array('is_success'=>$is_success,'new_accounts'=>$new_accounts[0]['tp_accounts_object']);
	}

	public function add_links_to_account($accounts,$f_advertiser_id)
	{
		$is_success = false;
		$insert_values_sql = "";
		$bindings_array = array();
		foreach($accounts as $f_tp_account_info)
		{
			$tp_account = explode('_', $f_tp_account_info);
			$tp_source = $tp_account[0];
			$tp_acct_id = $tp_account[1];
			if($insert_values_sql != "")
			{
				$insert_values_sql .= ", ";
			}
			$insert_values_sql .= "(?, ?, ?)";
			$bindings_array[] = $f_advertiser_id;
			$bindings_array[] = $tp_acct_id;
			$bindings_array[] = $tp_source;	
		}

		$sql = 	"INSERT IGNORE INTO 
					tp_advertisers_join_third_party_account 
					(frq_advertiser_id,  frq_third_party_account_id, third_party_source)
				VALUES ".$insert_values_sql;
		if($this->db->query($sql, $bindings_array))
		{
			$is_success = true;
		}
		return $is_success;
	}




	public function get_allowed_advertisers($term, $start, $limit, $user_id, $role, $is_group_super)
	{
		$campaigns = array();
		$binding_array = array();
		if($role == 'sales')
		{
			$binding_array = array($user_id, $user_id);
			$group_super_condition_sql = "";
			if($is_group_super == 1)
			{
				$group_super_condition_sql = " OR u.partner_id = h.ancestor_id";
			}
				$sql = "
				SELECT DISTINCT
					a.id as id,
					a.Name as f_advertiser_name,
					COALESCE(a.Name,'adv name null') as text,
					COALESCE(COALESCE(pd.partner_name, pd2.partner_name),'partner null') as partner_name, 
					COALESCE(CONCAT(u3.firstname,' ',u3.lastname),'ae null') as ae_name
				FROM 
					users u
					LEFT JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h 
						ON  (po.partner_id = h.ancestor_id".$group_super_condition_sql.")
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2 
						ON pd.id = u2.partner_id
					RIGHT JOIN Advertisers a
						ON (u2.id = a.sales_person)
					JOIN users AS u3
						ON a.sales_person = u3.id
					JOIN wl_partner_details AS pd2
						ON u3.partner_id = pd2.id
				WHERE 
					(u.id = ? OR a.sales_person = ?) AND
					(a.Name LIKE ? )
					GROUP BY a.id 
				LIMIT ?, ?
				";
				$binding_array[] = $term;
				$binding_array[] = $start;
				$binding_array[] = $limit;
		}
		else if($role == 'ops' || $role == 'admin')
		{
			$sql = "
					SELECT 
						a.id as id,
						a.Name as f_advertiser_name,
						COALESCE(a.Name,'adv name null') as text,
						COALESCE(p.partner_name,'partner null') as partner_name, 
						COALESCE(CONCAT(u.firstname,' ',u.lastname),'ae null') as ae_name
					FROM 
						Advertisers a 
						 JOIN users u  
							ON a.`sales_person` = u.`id` 
						 JOIN wl_partner_details AS p  
							ON u.`partner_id` = p.id 
					WHERE
						(a.Name LIKE ? )
					GROUP BY a.id
					LIMIT ?, ? 
					";
				$binding_array[] = $term;
				$binding_array[] = $start;
				$binding_array[] = $limit;
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

}
?>
