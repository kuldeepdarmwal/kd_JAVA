<?php

class Advertisers_model extends CI_Model {

	public function __construct(){
		$this->load->database();                
	}

	public function save_advertiser($advertiser_id, $advertiser_name, $external_id, $sales_person)
	{
		if (strlen($external_id) === 0)
		{
			$external_id = null;
		}
		
		$sql = 
		"	UPDATE Advertisers
			SET 
				Name = ?,
				external_id = ?,
				sales_person = ?
			WHERE id = ?
		";
		$query = $this->db->query($sql, array($advertiser_name, $external_id, $sales_person, $advertiser_id));
		return $this->db->insert_id();
	}

	public function get_select2_sales_users_for_edit_advertiser($term, $start, $limit, $user_id)
	{
		//get role and is super by user id
		$role_query = "SELECT LOWER(role) AS role, isGroupSuper FROM users WHERE id = ?";
		$role_response = $this->db->query($role_query, $user_id);
		if($role_response->num_rows() > 0)
		{
			$role = $role_response->row()->role;
			$is_super = $role_response->row()->isGroupSuper;
		}
		else
		{
			return array();
		}

		$binding = array();
		if($role == 'admin')
		{
			$binding[] = $term;
			$binding[] = $term;
			$binding[] = $start;
			$binding[] = $limit;
			$query = "
				SELECT
					id,
					CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, '')) AS text,
					email
				FROM
					users
				WHERE
					LOWER(role) = 'sales'AND
					(
						CONCAT_WS(' ', LOWER(firstname), LOWER(lastname)) LIKE LOWER(?) OR
						email LIKE ?
					)
				LIMIT ?, ?";
		}
		else if($role == 'sales')
		{
			$binding[] = $term;
			$binding[] = $term;
			$binding[] = $user_id;
			$binding[] = $user_id;
			$is_super_condition_sql = "";
			if($is_super == 1)
			{
				$binding[] = $term;
				$binding[] = $term;
				$binding[] = $user_id;
				$is_super_condition_sql = "
					UNION
					SELECT
						us2.id,
						CONCAT(COALESCE(us2.firstname, ''), ' ', COALESCE(us2.lastname, '')) AS text,
						us2.email
					FROM
						users AS usr
						JOIN wl_partner_hierarchy AS wph
							ON usr.partner_id = wph.ancestor_id
						JOIN wl_partner_details AS wpd
							ON wph.descendant_id = wpd.id
						JOIN users AS us2
							ON wpd.id = us2.partner_id
					WHERE
						LOWER(us2.role) = 'sales' AND
						(
							CONCAT_WS(' ', LOWER(us2.firstname), LOWER(us2.lastname)) LIKE LOWER(?) OR
							us2.email LIKE ?
						) AND
						usr.id = ?";
			}
			$binding[] = $start;
			$binding[] = $limit;
			$query = "
				SELECT
					*
				FROM
					(
					SELECT
						usr.id,
						CONCAT(COALESCE(usr.firstname, ''), ' ', COALESCE(usr.lastname, '')) AS text,
						usr.email
					FROM
						wl_partner_owners AS wpo
						JOIN wl_partner_hierarchy AS wph
							ON wpo.partner_id = wph.ancestor_id
						JOIN wl_partner_details AS wpd
							ON wph.descendant_id = wpd.id
						RIGHT JOIN users AS usr
							ON wpd.id = usr.partner_id
					WHERE
						LOWER(usr.role) = 'sales' AND
						(
							CONCAT_WS(' ', LOWER(usr.firstname), LOWER(usr.lastname)) LIKE LOWER(?) OR
							usr.email LIKE ?
						) AND
						(
							wpo.user_id = ? OR 
							usr.id = ?
						)
						".$is_super_condition_sql."
					) AS usr_query
				GROUP BY usr_query.id
				LIMIT ?, ?";
		}
		else
		{
			return array();
		}
		$result = $this->db->query($query, $binding);
		return $result->result_array();
	}

	//user id required, option advertiser id for checking permission for a specific advertiser/user combination
	//returns array of advertisers or array() when no records found
	public function get_allowed_advertisers_by_user_id($user_id, $advertiser_id = false)
	{

		//get role and is super by user id
		$role_query = "SELECT LOWER(role) AS role, isGroupSuper FROM users WHERE id = ?";
		$role_response = $this->db->query($role_query, $user_id);
		if($role_response->num_rows() > 0)
		{
			$role = $role_response->row()->role;
			$is_super = $role_response->row()->isGroupSuper;
		}
		else
		{
			return array();
		}
		$bindings = array();
		$advertiser_sql = "";

		if($role == "admin" or $role == "ops")
		{
			if($advertiser_id !== false)
			{
				$bindings = array($advertiser_id);
				$advertiser_sql = " AND adv.id = ?";
			}
			//select all advertisers if admin or ops
			$query = '
				SELECT
					adv.id AS advertiser_id,
					adv.Name AS advertiser_name,
					adv.sales_person,
					CONCAT(usr.firstname, " ", usr.lastname) AS user_name,
					wpd.partner_name AS partner_name,
				    adv.external_id AS external_id,
					TO_BASE64(adv.id) AS encoded_advertiser_id
				FROM
					Advertisers AS adv
					JOIN users AS usr
						ON adv.sales_person = usr.id
					JOIN wl_partner_details AS wpd
						ON usr.partner_id = wpd.id
				WHERE 1'.$advertiser_sql.'';
		}
		else if($role == "sales")
		{
			//get advertisers in three ways:
			//1. advertisers that belong to partner hierarchy owned by user
			//2. if is super all advertisers in users partner hierarchy
			//3. advertisers who have sales person as selected user

			$bindings = array($user_id, $user_id);
			if($advertiser_id !== false)
			{
				$bindings[] = $advertiser_id;
				$advertiser_sql = " AND adv.id = ?";
			}
			$is_super_condition_sql = "";
			if($is_super == 1)
			{
				$bindings[] = $user_id;
				if($advertiser_id !== false)
				{
					$bindings[] = $advertiser_id;
				}
				$is_super_condition_sql = '
					UNION
					SELECT
						adv.id AS advertiser_id,
						adv.Name AS advertiser_name,
						adv.sales_person,
						CONCAT(us2.firstname, " ", us2.lastname) AS user_name,
						wpd.partner_name AS partner_name,
					    adv.external_id AS external_id,
						TO_BASE64(adv.id) AS encoded_advertiser_id
					FROM
						users usr
						JOIN wl_partner_hierarchy wph
							ON usr.partner_id = wph.ancestor_id
						JOIN wl_partner_details wpd
							ON wph.descendant_id = wpd.id
						JOIN users us2
							ON wpd.id = us2.partner_id
						JOIN Advertisers adv
							ON us2.id = adv.sales_person
					WHERE usr.id = ?
					'.$advertiser_sql.'
					GROUP BY adv.id';
			}
			$query = '
					SELECT
					  	adv.id AS advertiser_id,
						adv.Name AS advertiser_name,
						adv.sales_person,
						COALESCE(CONCAT(us2.firstname, " ", us2.lastname), CONCAT(us3.firstname, " ", us3.lastname)) AS user_name,
						COALESCE(wpd.partner_name, pd2.partner_name) AS partner_name,
					    adv.external_id AS external_id,
						TO_BASE64(adv.id) AS encoded_advertiser_id
					FROM
						users usr
						LEFT JOIN wl_partner_owners wpo
							ON usr.id = wpo.user_id
						JOIN wl_partner_hierarchy wph 
							ON  wpo.partner_id = wph.ancestor_id
						JOIN wl_partner_details wpd
							ON wph.descendant_id = wpd.id
						JOIN users us2 
							ON wpd.id = us2.partner_id
					    RIGHT JOIN Advertisers adv
							ON us2.id = adv.sales_person
						JOIN users AS us3
							ON adv.sales_person = us3.id
						JOIN wl_partner_details AS pd2
							ON us3.partner_id = pd2.id
					WHERE
						(usr.id = ? OR adv.sales_person = ?)
						'.$advertiser_sql.'
					GROUP BY adv.id
					'.$is_super_condition_sql.'';
		}
		else
		{
			return array();
		}

		$response = $this->db->query($query, $bindings);
		$result = $response->result_array();
		return $result;
	}
	
	public function get_advertiser_sales_person_info($advertiser_id)
	{
		$query = "
				SELECT
					usr.*
				FROM
					Advertisers AS ad
				JOIN
					users AS usr
				ON
					ad.sales_person = usr.id
				WHERE
					ad.sales_person IS NOT NULL
				AND
					usr.email NOT LIKE '%unknown-email%'
				AND
					ad.id = ?
			";
		
		$result = $this->db->query($query, $advertiser_id);
		
		if ($result->num_rows() > 0)
		{
			return $result->row_array();
		}

		return false;
	}
}

?>