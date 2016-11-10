<?php

class Banner_intake_model extends CI_Model 
{

	public function __construct()
	{
		$this->load->database();
		$this->load->helper('url');	}

	public function insert_adset_request_form($form_data)
	{
		//Clear feature fields if hidden
		if (!isset($form_data['is_video']))
		{
			unset($form_data['features_video_youtube_url']);
			unset($form_data['features_video_video_play']);
			unset($form_data['features_video_mobile_clickthrough_to']);
		}

		if (!isset($form_data['is_map']))
		{
			unset($form_data['features_map_locations']);
		}

		if (!isset($form_data['is_social']))
		{
			unset($form_data['features_social_twitter_text']);
			unset($form_data['features_social_email_subject']);
			unset($form_data['features_social_email_message']);
			unset($form_data['features_social_linkedin_subject']);
			unset($form_data['features_social_linkedin_message']);
		}
		
		//Variations saving
		if(isset($form_data['has_variations']) && $form_data['has_variations'] == "on")
		{
			
			$variations_data = array();
			foreach($form_data['variation_names'] AS $idx => $variation_name)
			{
				if($variation_name != "" && $form_data['variation_details'][$idx] != "")
				{
					$variations_data[] = array('name' => $variation_name, 'description' => $form_data['variation_details'][$idx]);
				}
			}
			unset($form_data['num_variations']);
			unset($form_data['has_variations']);
			unset($form_data['variation_names']);
			unset($form_data['variation_details']);
			if(!empty($variations_data))
			{
				$form_data['variations_input_string'] = json_encode(array('spec'=>$form_data['variation_spec'], 'variations'=>$variations_data));
			}
			unset($form_data['variation_spec']);
		}
		else
		{
			unset($form_data['variation_spec']);
			unset($form_data['num_variations']);
			unset($form_data['has_variations']);
			unset($form_data['variation_names']);
			unset($form_data['variation_details']);				

		}
		
		if(!isset($form_data['creative_request_owner_id']))
		{
			$form_data['creative_request_owner_id'] = $form_data['requester_id'];
		}

		unset($form_data['is_video']);
		unset($form_data['is_map']);
		unset($form_data['is_social']);

		if (isset($form_data['creative_files']))
		{
			$indexed_array = array();
			foreach ($form_data['creative_files'] as $key => $value) {
				$indexed_array[] = $value;
			}
			$form_data['creative_files'] = json_encode($indexed_array);
		}
		
		$form_data['scenes'] = json_encode($form_data['scenes']);
		$cur_time = $this->get_db_current_time();
		$form_data['updated'] = $cur_time ? $cur_time[0]['time_now'] : new DateTime();
		$form_data['landing_page'] = trim($form_data['landing_page']);

		$this->db->insert('adset_requests', $form_data);
		if ($this->db->insert_id())
		{
			return array('is_success'=>true, 'result'=>$this->db->insert_id(), 'updated'=>$form_data['updated']);
		}
		else
		{
			return array('is_success'=>false);
		}
	}

	public function insert_email_error_text($creative_id, $errors)
	{
		$bindings = array(json_encode($errors), $creative_id);
		$sql = "UPDATE 
				adset_requests 
			SET 
				email_errors = ? 
			WHERE 
				id = ?
			";

		$response = $this->db->query($sql, $bindings);
		if ($response)
		{
			return true;
		}
		return false;
	}

	public function count_all_adset_requests()
	{
		return $this->db->count_all('adset_requests');
	}

	public function get_all_adset_requests($timezone_offset = 0, $user_role, $user_is_super, $user_id, $descendent_partner_keys = "")
	{
		$timezone_offset = -1*$timezone_offset;
		$bindings = [];
		
		if ($timezone_offset > -1)
		{
			$timezone_offset = "+".$timezone_offset;
		}
		$select_sql = "ar.id,
				ar.creative_name,
				ar.updated AS requested,
				ar.requester_id,
				ar.adset_id,
				'N/A' AS partner,
				ar.creative_request_owner_id,
				ar.advertiser_name,
				ar.product,
				CASE WHEN
					ar.product = 'Preroll'
				THEN
					''
				ELSE	
					ar.request_type
				END AS request_type,	
				ar.landing_page,
				'N/A' AS created,
				'N/A' AS updated, 
				'N/A' AS updater,
				'N/A' AS latest_version,
				'N/A' AS internally_approved_updated_user,
				'N/A' AS internally_approved_updated_timestamp,
				IF(email_errors IS NULL,1,0) as email_errors";
		
		
		$group_super_sql = "";
		if ($user_is_super && $descendent_partner_keys != "")
		{
			$group_super_sql = "	
					UNION
						SELECT
							$select_sql
						FROM
							adset_requests AS ar
						JOIN
							users AS us
						ON
							ar.requester_id = us.id
						WHERE
							us.partner_id IN ($descendent_partner_keys)
					";
		}
		
		$main_sql = "";
		if ($user_role == 'admin' || $user_role == 'ops' || $user_role == 'creative')
		{
			$main_sql = "
					SELECT
						$select_sql
					FROM 
						adset_requests AS ar
					WHERE
						ar.product IS NOT NULL
				";
		}
		else
		{
			$main_sql = "
					SELECT 
						$select_sql
					FROM
						adset_requests AS ar
					WHERE
						ar.product is not null
					AND
						ar.creative_request_owner_id=? OR ar.requester_id=?
					$group_super_sql	
				";
			$bindings = array($user_id, $user_id);
		}
		
		$sql = "
			SELECT
				ar.id,
				ar.creative_name,
				ar.requested AS requested,
				ar.requester_id,
				ar.adset_id,
				ar.partner,
				ar.advertiser_name,
				ar.product,
				ar.request_type,	
				ar.landing_page,
				ar.created,
				ar.updated, 
				ar.updater,
				ar.latest_version,
				ar.internally_approved_updated_user,
				ar.internally_approved_updated_timestamp,
				ar.email_errors,
				pd.is_demo_partner AS is_demo_login,
				pd.partner_name AS partner,
				CONCAT_WS(' ',usr.firstname,usr.lastname) AS creative_request_owner_id,
				CASE WHEN
					pd.is_demo_partner = 1
				THEN
					'demo'
				ELSE	
					'nodemo'
				END AS demo,
				cpv.id AS latest_version,
				cpv.version AS version,
				cpv.base_64_encoded_id AS base_64_encoded_id,
				cpa.name AS adset_name,
				cpv.variation_name AS variation_name,
				cpv.created_timestamp,
				cpv.updated_timestamp,
				CONCAT_WS(' ', usr2.firstname,usr2.lastname) AS updater,
				CONCAT('V',cpv.version) AS version_name,
				cpv.internally_approved_updated_user,
				cpv.internally_approved_updated_timestamp,
				cpv.show_for_io
			FROM
			(	
				$main_sql
			) AS ar
			JOIN users AS usr
				ON usr.id = COALESCE(ar.creative_request_owner_id,ar.requester_id)
			JOIN wl_partner_details pd 
				ON pd.id = usr.partner_id
			LEFT JOIN cup_adsets cpa
				ON ar.adset_id = cpa.id
			LEFT JOIN cup_versions cpv
				ON cpa.id = cpv.adset_id
			LEFT JOIN users AS usr2
				ON cpv.updated_user = usr2.id						
			ORDER BY 
				ar.id DESC, cpv.id DESC";
		
		$query = $this->db->query($sql, $bindings);
		$result_array = $query->result_array();
		
		$final_results = array();
		
		if ($query->num_rows() > 0)
		{
			$old_adset_request = null;
			$v_result = array();
			$done_processing = false;
			
			foreach ($result_array as $result)
			{
				if ($old_adset_request == null || $old_adset_request['id'] == $result['id'])
				{
					$this->populate_v_result($result, $v_result);
					if ($old_adset_request == null)
					{
						$old_adset_request = $result;
					}
				}
				elseif ($old_adset_request['id'] != $result['id'])
				{
					$this->populate_version_info_for_ad_set($old_adset_request, $v_result, $timezone_offset);					
					$final_results[] = $old_adset_request;
					$v_result = array();
					$this->populate_v_result($result, $v_result);
					$old_adset_request = $result;
				}								
			}
			
			if ($old_adset_request != null)
			{
				$this->populate_version_info_for_ad_set($old_adset_request, $v_result, $timezone_offset);					
				$final_results[] = $old_adset_request;
			}
			
			return $final_results;
		}
		else
		{
			return false;
		}
	}
	
	private function populate_v_result($result, &$v_result)
	{
		if (!empty($result['version']) && !empty($result['adset_name']) && !empty($result['show_for_io']) && $result['show_for_io'] == 1)
		{
			$encoded_id = $result['base_64_encoded_id'];
			if($encoded_id == null)
			{
				$encoded_id = base64_encode(base64_encode(base64_encode($result['id'])));
			}
			$version_array = array(
				'version_name' => 'V' . $result['version'] . "-" . $result['variation_name'],
				'version_identifier' => $encoded_id . '/' . url_title($result['adset_name']) . 'v' . $result['version'],
				'version_url' => base_url('crtv/get_adset/' . $encoded_id . '/' . url_title($result['adset_name']) . 'v' . $result['version']));
			$v_result[] = $version_array;
		}
	}
	
	private function populate_version_info_for_ad_set(&$old_adset_request, $v_result, $timezone_offset)
	{
		$old_adset_request['versions'] = $v_result;
		$old_adset_request['requested'] = date("Y-m-d h:i A", strtotime($timezone_offset . ' hours', strtotime($old_adset_request['requested'])));

		if (isset($old_adset_request['adset_id']) && $old_adset_request['adset_id'])
		{
			$old_adset_request['created'] = date("Y-m-d h:i A", strtotime($timezone_offset.' hours', strtotime($old_adset_request['created_timestamp'])));
			$old_adset_request['updated'] = date("Y-m-d h:i A", strtotime($timezone_offset.' hours', strtotime($old_adset_request['updated_timestamp'])));			
		}
	}

	public function get_adset_request_by_id($form_id)
	{
		$sql = "SELECT 
				* 
			FROM 
				adset_requests 
			WHERE 
				id = ?";
		$query = $this->db->query($sql, array($form_id));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_db_current_time()
	{
		$sql='SELECT NOW() as time_now';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function check_if_real_authorized_user($email)
	{
		$sql = "SELECT 
				users.email,
				users.role
			FROM 
				users
			WHERE 
				LOWER(role) IN ('admin', 'ops', 'creative', 'sales', 'business') 
			AND 
				LOWER(email)=?
			LIMIT 1";
		$query = $this->db->query($sql, array(strtolower($email)));
		return $query->num_rows() > 0;
	}
	
	function get_advertisers_for_internal_users_for_select2($search_term, $start, $limit)
	{
		$binding_array = array();
		$binding_array[] = $search_term;
		$binding_array[] = $start;
		$binding_array[] = $limit;

		$sql = 
			'
			SELECT 
				a.id, 
				a.Name
			FROM 
				wl_partner_details pd 
			JOIN 
				users AS u 
			ON 
				pd.id = u.partner_id 
			JOIN 
				Advertisers AS a
			ON 
				u.id = a.sales_person
			WHERE
				a.Name LIKE ?
			ORDER BY 
				a.Name ASC
			LIMIT ?, ?';
		
		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		
		return false;
	}
	
	public function get_advertisers_by_user_id_for_select2($search_term, $start, $limit, $user_id, $is_super)
	{
		$non_super_sales_where_sql = "";
		$binding_array = array($user_id);
		
		if ($is_super == 1)
		{
			$wl_partner_hierarchy_join_sql = "(u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)";
		}
		else
		{
			$wl_partner_hierarchy_join_sql = " po.partner_id = h.ancestor_id";
			$non_super_sales_where_sql = "OR a.sales_person = ?";
			$binding_array[] = $user_id;
		}
		
		$binding_array[] = $search_term;
		$binding_array[] = $start;
		$binding_array[] = $limit;

		
		$sql = 
			'
			SELECT DISTINCT
				a.id,
				a.Name
			FROM
				users AS u ';
		
		if ($is_super == '1')
		{
			$sql .= 'LEFT ';
		}
		
		$sql .=	'JOIN 
				wl_partner_owners po
			ON 
				u.id = po.user_id
			JOIN 
				wl_partner_hierarchy h
			ON 
				'.$wl_partner_hierarchy_join_sql.'
			JOIN 
				wl_partner_details pd
			ON 
				h.descendant_id = pd.id
			JOIN 
				users u2 
			ON 
				pd.id = u2.partner_id
			RIGHT JOIN 
				Advertisers a
			ON 
				u2.id = a.sales_person
			WHERE 
				u.id = ? '.$non_super_sales_where_sql.'
			AND	
				a.Name LIKE ?				    
			ORDER BY 
				a.Name ASC
			LIMIT ?, ?';
		
		$query = $this->db->query($sql, $binding_array);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		
		return false;
	}
	
	public function get_users_via_partner_heirarchy_for_select2($term, $start, $limit, $user_id, $role)
	{
		$term = '%'.$term.'%';
		$bindings = array();
		
		if ($role == 'admin' or $role == 'ops' || $role == 'creative')
		{
			$query = "
				SELECT
					us.id,
					CONCAT_WS(' ', us.firstname, us.lastname) AS name,
					us.email
				FROM
					users AS us
				WHERE
					role IN ('SALES', 'ADMIN', 'OPS', 'CREATIVE') 
				AND
					banned = 0 
				AND
				(
					UPPER(CONCAT_WS(' ', us.firstname, us.lastname)) LIKE UPPER(?) 
				OR
					UPPER(us.email) LIKE UPPER(?)
				)
				ORDER BY 
					us.firstname ASC
				LIMIT ?, ?";
		}
		else if($role == 'sales')
		{
			$query = "
				SELECT DISTINCT
					ae_query.*
				FROM
				(
					SELECT
						us.id,
						CONCAT_WS(' ', us.firstname, us.lastname) AS name,
						us.email
					FROM
						users ownr
					JOIN 
						wl_partner_hierarchy AS ph
					ON 
						ownr.partner_id = ph.ancestor_id
					JOIN 
						wl_partner_details AS pd
					ON 
						ph.descendant_id = pd.id
					JOIN 
						users AS us
					ON 
						pd.id = us.partner_id
					WHERE
						ownr.id = ? 
					AND
						us.role IN ('SALES', 'ADMIN', 'OPS', 'CREATIVE') 
					AND
						us.banned = 0 
					AND
					(
						UPPER(CONCAT_WS(' ', us.firstname, us.lastname)) LIKE UPPER(?) 
					OR
						UPPER(us.email) LIKE UPPER(?)
					)
					UNION
					SELECT
						us.id,
						CONCAT_WS(' ', us.firstname, us.lastname) AS name,
						us.email
					FROM
						users AS ownr
					JOIN 
						wl_partner_owners po
					ON 
						ownr.id = po.user_id
					JOIN 
						wl_partner_hierarchy AS ph
					ON 
						po.partner_id = ph.ancestor_id
					JOIN 
						wl_partner_details pd
					ON 
						ph.descendant_id = pd.id
					JOIN 
						users AS us
					ON 
						pd.id = us.partner_id
					WHERE
						ownr.id = ? 
					AND
						us.role IN ('SALES') 
					AND
						us.banned = 0 
					AND
					(
						UPPER(CONCAT_WS(' ', us.firstname, us.lastname)) LIKE UPPER(?) 
					OR
						UPPER(us.email) LIKE UPPER(?)
					)
				) AS ae_query
				ORDER BY 
					ae_query.name ASC
				LIMIT ?, ?";
			
			$bindings[] = $user_id;
			$bindings[] = $user_id;
			$bindings[] = $term;
			$bindings[] = $term;
		}
		$bindings[] = $term;
		$bindings[] = $term;
		$bindings[] = $start;
		$bindings[] = $limit;
		$response = $this->db->query($query, $bindings);
		
		if ($response and $response->num_rows() > 0)
		{
			return $response->result_array();
		}
		
		return false;
	}
	
	public function get_advertiser_name_by_id($advertiser_id)
	{
		$sql = 
			'
			SELECT 
				a.Name AS name
			FROM	
				Advertisers AS a
			WHERE
				a.id = ?';
		
		$query = $this->db->query($sql, $advertiser_id);
		
		if ($query->num_rows() > 0)
		{
			$result = $query->row_array();
			return $result['name'];
		}
		
		return false;
	}
	
	public function get_user_name_by_id($user_id)
	{
		$sql = 
			"
			SELECT 
				CONCAT_WS(' ', usr.firstname, usr.lastname) AS name
			FROM	
				users AS usr
			WHERE
				usr.id = ?";
		
		$query = $this->db->query($sql, $user_id);
		
		if ($query->num_rows() > 0)
		{
			$result = $query->row_array();
			return  $result['name'];
		}
		
		return false;
	}
	
	public function populate_adset_id_in_adset_request($adset_request_id, $adset_id)
	{
		$sql =  "UPDATE 
				adset_requests
			SET 
				adset_id = ? 
			WHERE 
				id = ? ";
		$binding_array = array($adset_id, $adset_request_id);
		$this->db->query($sql, $binding_array);
		
		if ($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function get_adset_request_id_by_adset_version_id($adset_version_id)
	{
		$sql = 
			"SELECT 
				ar.id AS adset_request_id 
			FROM 
				adset_requests AS ar
			JOIN
				cup_versions AS cv
			ON
				cv.adset_id = ar.adset_id		 
			WHERE 
				cv.id = ?
			";
		$query = $this->db->query($sql,array($adset_version_id));
		
		if ($query->num_rows() > 0)
		{
			return $query->row_array()['adset_request_id'];
		}
		else
		{
			return false;
		}
	}

	public function get_banner_intake_owner_cname($banner_intake_id)
	{
		$get_owner_cname_query = 
		"SELECT
			wlp.cname AS cname
		FROM 
			adset_requests AS ar
			JOIN users AS u
				ON (ar.creative_request_owner_id = u.id)
			JOIN wl_partner_details AS wlp
				ON (u.partner_id = wlp.id)
		WHERE
			ar.id = ?
		";

		$get_owner_cname_result = $this->db->query($get_owner_cname_query, $banner_intake_id);
		if($get_owner_cname_result == false || $get_owner_cname_result->num_rows() < 1)
		{
			return false;
		}
		$row = $get_owner_cname_result->row_array();
		return $row['cname'];
	}

	public function get_banner_intake_requester_cname($banner_intake_id)
	{
		$get_requester_cname_query = 
		"SELECT
			wlp.cname AS cname
		FROM 
			adset_requests AS ar
			JOIN users AS u
				ON (ar.requester_id = u.id)
			JOIN wl_partner_details AS wlp
				ON (u.partner_id = wlp.id)
		WHERE
			ar.id = ?
		";

		$get_requester_cname_result = $this->db->query($get_requester_cname_query, $banner_intake_id);
		if($get_requester_cname_result == false || $get_requester_cname_result->num_rows() < 1)
		{
			return false;
		}
		$row = $get_requester_cname_result->row_array();
		return $row['cname'];
	}

	public function get_banner_intake_related_versions($banner_intake_id)
	{
		$get_request_versions_query = 
		"SELECT
			ver.version AS version_number,
			ver.id AS version_id
		FROM
			adset_requests AS ar
			JOIN cup_adsets AS ads
				ON (ar.adset_id = ads.id)
			JOIN cup_versions AS ver
				ON (ads.id = ver.adset_id)
		WHERE
			ar.id = ?
			AND ver.parent_variation_id IS NULL
		";

		$get_request_versions_result = $this->db->query($get_request_versions_query, $banner_intake_id);
		if($get_request_versions_result == false || $get_request_versions_result->num_rows() < 1)
		{
			return false;
		}

		return $get_request_versions_result->result_array();
	}

	public function link_new_banner_intake_to_io_product($banner_intake_id, $version_id, $mpq_id, $product)
	{
		//Get product id
		$get_product_join_io_query = 
		"SELECT
			csp.id
		FROM
			cp_submitted_products AS csp
			JOIN cp_products AS cp 
				ON (csp.product_id = cp.id)
		WHERE
			csp.mpq_id = ?
			AND cp.banner_intake_id LIKE ?
		";

		$get_product_join_io_result = $this->db->query($get_product_join_io_query, array($mpq_id, $product));
		if($get_product_join_io_result == false || $get_product_join_io_result->num_rows() < 1)
		{
			return false;
		}
		$product_rows = $get_product_join_io_result->result_array();

		$values_sql = array();
		$bindings = array();
		foreach($product_rows as $product_row)
		{
			$values_sql[] = "(?, ?, ?)";
			$bindings[] = $product_row['id'];
			$bindings[] = $version_id;
			$bindings[] = $banner_intake_id;
		}

		$values_sql = implode(",", $values_sql);
		$insert_cp_creative_join_query = 
		"INSERT INTO
			cp_io_join_cup_versions
			(cp_submitted_products_id, cup_versions_id, adset_request_id)
		VALUES
			$values_sql
		";

		$insert_cp_creative_join_result = $this->db->query($insert_cp_creative_join_query, $bindings);
		if($insert_cp_creative_join_result == false)
		{
			return false;
		}
		return true;
	}
}

