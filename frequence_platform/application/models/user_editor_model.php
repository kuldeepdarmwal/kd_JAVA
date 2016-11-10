<?php

class user_editor_model extends CI_Model
{

	public function __construct()
	{
		$this->load->model('vl_auth_model');
	}

	public function get_users_and_details_for_editor_users_for_id($user_id, $user_role, $user_partner_id, $is_group_super, $timezone_offset, $return_partner_name_descendants = FALSE, $partners_delimiter = ',')
	{
		$return_data = array();
		$return_data['success'] = true;
		$return_data['err_msg'] = "";
		$return_data['user_data'] = array();
		$return_data['partner_descendants'] = array();
		$allowed_user_roles = array('admin','creative','business','sales','ops','client','agency');

		$owned_partners_query = "
		    SELECT
			    user_id,
			    GROUP_CONCAT(CAST(partner_id AS CHAR) SEPARATOR '".$partners_delimiter."') AS owned_partners
		    FROM
			    wl_partner_owners AS wlpo
		    GROUP BY
			    user_id
		    ";

		if ($return_partner_name_descendants)
		{
			$owned_partners_query = "
			SELECT
				wlpo.user_id,
				GROUP_CONCAT(CAST(wlpd.partner_name AS CHAR) SEPARATOR '".$partners_delimiter."') AS owned_partners
			FROM
				wl_partner_owners AS wlpo
			LEFT JOIN
				wl_partner_details AS wlpd
			ON
				wlpd.id=wlpo.partner_id
			GROUP BY
				wlpo.user_id
			";
		}


		$user_retrieve_where = "";
		$super_union_queries = "";
		$bindings = array();
		$append_bindings = array();
		switch($user_role)
		{
			case "admin":
				//Show everyone
				$user_retrieve_where = "WHERE u.id != ?";
				$append_bindings[] = $user_id;
				break;
			case "ops":
				//Show everyone who isn't an admin or ops, this should naturally exclude yourself
				$user_retrieve_where = " WHERE (u.role != \"ADMIN\" OR u.role != \"OPS\")";
				break;
			case "sales":
				//If super
				if($is_group_super)
				{
					//Show users who belong to this org
					//Grab users who belong to the org first, and then
					//union with users who have an assigned advertiser whose salesperson
					//belongs to this org
					$super_union_queries = "
					UNION ALL
						SELECT
							u2.id,
							u2.username,
							u2.email,
							u2.activated,
							u2.banned,
							u2.role,
							u2.last_login AS last_login,
							u2.business_name,
							u2.partner_id AS org_id,
							u2.firstname,
							u2.lastname,
							u2.isGroupSuper,
							u2.placements_viewable,
							u2.planner_viewable,
							u2.screenshots_viewable,
							u2.beta_report_engagements_viewable,
							u2.advertiser_id,
							u2.ad_sizes_viewable,
							wlp2.partner_name AS org_name,
							CONCAT(wlp2.cname,'.brandcdn.com') AS p_login,
							p_owners.owned_partners AS owned_partners
						FROM
							users AS u
							JOIN wl_partner_hierarchy AS wlph
							ON (u.partner_id = wlph.ancestor_id)
							JOIN users AS u2
							ON (wlph.descendant_id = u2.partner_id AND u.id != u2.id)
							JOIN wl_partner_details AS wlp2
							ON (u2.partner_id = wlp2.id)
							LEFT JOIN
							(".$owned_partners_query.") AS p_owners
							ON(u2.id = p_owners.user_id)
							WHERE
								u.id = ?
								AND (u2.role != 'ADMIN' AND u2.role != 'OPS')
								AND u2.id != ?
					UNION ALL
						SELECT
							ua.id,
							ua.username,
							ua.email,
							ua.activated,
							ua.banned,
							ua.role,
							ua.last_login,
							ua.business_name,
							adv.id AS org_id,
							ua.firstname,
							ua.lastname,
							ua.isGroupSuper,
							ua.placements_viewable,
							ua.planner_viewable,
							ua.screenshots_viewable,
							ua.beta_report_engagements_viewable,
							ua.advertiser_id,
							ua.ad_sizes_viewable,
							adv.Name AS org_name,
							CONCAT(wlp.cname,'.brandcdn.com') AS p_login,
							NULL AS owned_partners
						FROM
							users AS u
							JOIN wl_partner_hierarchy AS wlph
							ON (u.partner_id = wlph.ancestor_id)
							JOIN users AS u2
							ON (wlph.descendant_id = u2.partner_id AND u.id != u2.id)
							JOIN Advertisers AS adv
							ON (u2.id = adv.sales_person)
							JOIN users AS ua
							ON(adv.id = ua.advertiser_id)
							LEFT JOIN wl_partner_details AS wlp
							ON (u2.partner_id = wlp.id)
							WHERE u.id = ?
							AND (ua.role != 'ADMIN' AND ua.role != 'OPS')
					";
					$append_bindings[] = $user_id;
					$append_bindings[] = $user_id;
					$append_bindings[] = $user_id;
				}
				break;
			default:
				$return_data['err_msg'] = "Unrecognized/Unallowed user type";
				$return_data['success'] = false;
				return $return_data;
				break;
		}
		if($user_role == "admin" || $user_role == "ops")
		{
			$retrieve_user_details_query = "
			SELECT
				u.id,
				u.username,
				u.email,
				u.activated,
				u.banned,
				u.role,
				u.last_login,
				u.business_name,
				IF(u.role = \"BUSINESS\",adv.id, u.partner_id) AS org_id,
				u.firstname,
				u.lastname,
				u.isGroupSuper,
				u.placements_viewable,
				u.planner_viewable,
				u.screenshots_viewable,
				u.beta_report_engagements_viewable,
				u.advertiser_id,
				u.ad_sizes_viewable,
				IF(u.role = \"BUSINESS\", adv.Name ,wlp.partner_name) AS org_name,
				CONCAT(wlp.cname,'.brandcdn.com') AS p_login,
				p_owners.owned_partners AS owned_partners
			FROM
				users AS u
				LEFT JOIN wl_partner_details AS wlp
					ON (u.partner_id = wlp.id)
				LEFT JOIN Advertisers AS adv
					ON (u.advertiser_id = adv.id)
				LEFT JOIN users AS u_adv_sales
					ON (adv.sales_person = u_adv_sales.id)
				LEFT JOIN
				(".$owned_partners_query.") AS p_owners
					ON(u.id = p_owners.user_id)".$user_retrieve_where;
		}
		if($user_role == "sales")
		{
			//Gets owned partner users
			$retrieve_user_details_query = "
			SELECT
				u2.id,
				u2.username,
				u2.email,
				u2.activated,
				u2.banned,
				u2.role,
				u2.last_login,
				u2.business_name,
				u2.partner_id AS org_id,
				u2.firstname,
				u2.lastname,
				u2.isGroupSuper,
				u2.placements_viewable,
				u2.planner_viewable,
				u2.screenshots_viewable,
				u2.beta_report_engagements_viewable,
				u2.advertiser_id,
				u2.ad_sizes_viewable,
				wlp2.partner_name AS org_name,
				CONCAT(wlp2.cname,'.brandcdn.com') AS p_login,
				p_owners.owned_partners AS owned_partners
			FROM
				users AS u
				JOIN wl_partner_owners AS wlpo
				ON (u.id = wlpo.user_id)
				JOIN wl_partner_hierarchy AS wlph
				ON (wlpo.partner_id = wlph.ancestor_id)
				JOIN users AS u2
				ON (wlph.descendant_id = u2.partner_id)
				JOIN wl_partner_details AS wlp2
				ON (u2.partner_id = wlp2.id)
				LEFT JOIN
				(".$owned_partners_query.") AS p_owners
				ON(u2.id = p_owners.user_id)
			WHERE
				u.id = ?
				AND (u2.role != 'ADMIN' AND u2.role != 'OPS')
			UNION ALL
				SELECT
							ua.id,
							ua.username,
							ua.email,
							ua.activated,
							ua.banned,
							ua.role,
							ua.last_login,
							ua.business_name,
							adv.id AS org_id,
							ua.firstname,
							ua.lastname,
							ua.isGroupSuper,
							ua.placements_viewable,
							ua.planner_viewable,
							ua.screenshots_viewable,
							ua.beta_report_engagements_viewable,
							ua.advertiser_id,
							ua.ad_sizes_viewable,
							adv.Name AS org_name,
					CONCAT(wlp.cname,'.brandcdn.com') AS p_login,
					NULL AS owned_partners
				FROM
					users AS u
					JOIN wl_partner_owners AS wlpo
					ON(u.id = wlpo.user_id)
					JOIN wl_partner_hierarchy AS wlph
					ON(wlpo.partner_id = wlph.ancestor_id)
					JOIN users AS u2
					ON (wlph.descendant_id = u2.partner_id)
					JOIN Advertisers AS adv
					ON (u2.id = adv.sales_person)
					JOIN users AS ua
					ON(adv.id = ua.advertiser_id)
					LEFT JOIN wl_partner_details AS wlp
					ON (u2.partner_id = wlp.id)
				WHERE u.id = ?
				AND (ua.role != 'ADMIN' AND ua.role != 'OPS')
				".$super_union_queries;
			$bindings[] = $user_id;
			$bindings[] = $user_id;

		}
		$bindings = array_merge($bindings, $append_bindings);

		//Run query
		$get_users_result = $this->db->query($retrieve_user_details_query, $bindings);
		if($get_users_result == false)
		{
			$return_data['success'] = false;
			$return_data['err_msg'] = "Error #64290: Failed to retrieve user data.";
			return $return_data;
		}

		$return_data['user_data'] = $get_users_result->result_array();

		$partners_to_get_descendants = array($user_partner_id);

		$timezone_offset = -1*$timezone_offset;
		if($timezone_offset > -1)
		{
			$timezone_offset = "+".$timezone_offset;
		}

		$user_data_final = array();

		foreach($return_data['user_data'] as &$row)
		{
			if (!in_array(strtolower($row['role']),$allowed_user_roles))
			{
				continue;
			}

			if($row['last_login'] == "0000-00-00 00:00:00")
			{
				$row['last_login'] = "N/A";
				$row['time_sort'] = 0;
			}
			else
			{
				$row['last_login'] = date("Y-m-d h:i A", strtotime($timezone_offset.' hours', strtotime($row['last_login'])));
				$row['time_sort'] = strtotime($row['last_login']);
			}


			if($row['role'] != "BUSINESS" && !$return_partner_name_descendants)
			{
				if(isset($row['owned_partners']) && $row['owned_partners'] != null)
				{
					$row['owned_partners'] = explode(',', $row['owned_partners']);
				}
			}
			$user_data_final[] = $row;
		}

		$return_data['user_data'] = $user_data_final;

		if (!$return_partner_name_descendants)
		{
			$partner_id_descendants = $this->get_descendant_names_for_partner_array($user_partner_id, $user_role);
			$return_data['partner_descendants'] = $partner_id_descendants;
		}

		$returned_row['creative_viewable'] = $this->vl_platform_model->is_feature_accessible($user_id,'banner_intake') ? '1' : '0';
		$returned_row['proposals_viewable'] = $this->vl_platform_model->is_feature_accessible($user_id,'proposals') ? '1' : '0';
		$returned_row['sample_ad_builder'] = $this->vl_platform_model->is_feature_accessible($user_id,'ad_machina') ? '1' : '0';
		$returned_row['sample_ad_manager'] = $this->vl_platform_model->is_feature_accessible($user_id,'ad_machina_manager') ? '1' : '0';
		return $return_data;
	}

	public function set_permission_for_user($user_id, $permission, $allowed)
	{
		$return_data = array();
		$return_data['is_success'] = true;
		$return_data['err_msg'] = "";

		$update_bindings = array();
		$user_permission_query = "UPDATE users SET ";

		switch($permission)
		{
			case "placement":
				$user_permission_query .= "placements_viewable = ?";
				break;
			case "screenshot":
				$user_permission_query .= "screenshots_viewable = ?";
				break;
			case "engagement":
				$user_permission_query .= "beta_report_engagements_viewable = ?";
				break;
			case "ban":
				$user_permission_query .= "banned = ?";
				break;
			default:
				$return_data['is_success'] = false;
				$return_data['err_msg'] = "Unknown permission type";
				return $return_data;
		}
		$update_bindings[] = $allowed;

		$user_permission_query .= " WHERE id = ?";
		$update_bindings[] = $user_id;

		$update_permission_result = $this->db->query($user_permission_query, $update_bindings);
		if($update_permission_result == false)
		{
			$return_data['is_success'] = false;
			$return_data['err_msg'] = "Failed to update user permissions in database";
			return $return_data;
		}

		return $return_data;
	}

	private function get_descendant_names_for_partner_array($user_partner_id, $user_role)
	{
		$partner_descendant_where = "";
		$partner_id_binding = null;
		if($user_role != "admin")
		{
			$partner_id_binding = $user_partner_id;
			$partner_descendant_where = "WHERE
				wlpd.id IN (SELECT descendant_id FROM wl_partner_hierarchy WHERE ancestor_id = ?)";
		}
		$get_descendant_names_query = "
			SELECT
				wlpd.id AS ancestor_id,
				wlpd.partner_name AS partner_name,
				IF(wlpd.cname IS NOT NULL, CONCAT(wlpd.cname,'.brandcdn.com'), '--') AS p_login,
				wlph.descendant_id,
				GROUP_CONCAT(CONCAT(\"<li>\",CAST(wlpd_d.partner_name AS CHAR),\"</li>\") SEPARATOR \"\") AS descendant_partners
			FROM
				wl_partner_details AS wlpd
				LEFT JOIN wl_partner_hierarchy AS wlph
					ON (wlph.ancestor_id = wlpd.id)
				LEFT JOIN wl_partner_details AS wlpd_d
					ON (wlph.descendant_id = wlpd_d.id AND wlph.ancestor_id != wlpd_d.id)
				".$partner_descendant_where."
			GROUP BY
				wlpd.id";

		$get_descendant_names_result = $this->db->query($get_descendant_names_query, $partner_id_binding);

		if($get_descendant_names_result == false)
		{
			return false;
		}
		$descendant_array = $get_descendant_names_result->result_array();
		$return_array = array();
		foreach($descendant_array as $descendant_set)
		{
			$return_array[$descendant_set['ancestor_id']] = array();
			$return_array[$descendant_set['ancestor_id']]['partner_name'] = $descendant_set['partner_name'];
			$return_array[$descendant_set['ancestor_id']]['login_url'] = $descendant_set['p_login'];
			if($descendant_set['descendant_partners'] != null)
			{
				$descendant_set['descendant_partners'] = preg_replace('/(.*),/', '$1, and', $descendant_set['descendant_partners']);

			}
			$return_array[$descendant_set['ancestor_id']]['descendants'] = $descendant_set['descendant_partners'];
		}
		return $return_array;
	}

	public function get_user_info($user_id)
	{
		$get_user_info_sql = "
			SELECT
				u.firstname AS first_name,
				u.lastname AS last_name,
				u.email AS email,
				u.username AS username,
				u.role AS role,
				u.isGroupSuper AS is_group_super,
				IF(u.role = \"SALES\", u.partner_id, null) AS partner_id,
				u.placements_viewable AS placements_viewable,
				u.beta_report_engagements_viewable AS engagements_viewable,
				u.screenshots_viewable AS screenshots_viewable,
				u.address_1 AS address_1,
				u.address_2 AS address_2,
				u.city AS city,
				u.state AS state,
				u.zip AS zip,
				u.advertiser_id AS advertiser_id,
				adv.name AS advertiser_name,
				u.phone_number AS phone_number,
				p_owners.owned_partners AS owned_partners
			FROM
				users AS u
				LEFT JOIN (
					SELECT
						user_id,
						GROUP_CONCAT(CAST(partner_id AS CHAR) ) AS owned_partners
					FROM
						wl_partner_owners AS wlpo
					GROUP BY
						user_id) AS p_owners
				ON (u.id = p_owners.user_id)
				LEFT JOIN Advertisers adv
					ON adv.id = u.advertiser_id
			WHERE
				u.id = ?
			";
		$get_user_info_result = $this->db->query($get_user_info_sql, $user_id);
		if($get_user_info_result == false)
		{
			return false;
		}
		$returned_row = $get_user_info_result->row_array();

		if($returned_row['owned_partners'] != null)
		{
			$returned_row['owned_partners'] = explode(',', $returned_row['owned_partners']);
		}

		$returned_row['creative_viewable'] = $this->vl_platform_model->is_feature_accessible($user_id,'banner_intake') ? '1' : '0';
		$returned_row['proposals_viewable'] = $this->vl_platform_model->is_feature_accessible($user_id,'proposals') ? '1' : '0';
		$returned_row['sample_ad_builder'] = $this->vl_platform_model->is_feature_accessible($user_id,'ad_machina') ? '1' : '0'; // TODO: rename `ad_machina` to `sample_ads` in `pl_feature`?
		$returned_row['sample_ad_manager'] = $this->vl_platform_model->is_feature_accessible($user_id,'ad_machina_manager') ? '1' : '0'; // TODO: rename `ad_machina` to `sample_ads` in `pl_feature`?

		return $returned_row;
	}

	public function verify_user_data_array($user_data, $user_id)
	{
		$bad_entries_array = array();
		$bad_entries_description = array();
		if(!array_key_exists('first_name', $user_data) || empty($user_data['first_name']))
		{
			$bad_entries_array[] = "first_name";
			$bad_entries_description[] = "- Please enter a first name";
		}

		if(!array_key_exists('last_name', $user_data) || empty($user_data['last_name']))
		{
			$bad_entries_array[] = "last_name";
			$bad_entries_description[] = "- Please enter a last name";
		}

		if(!array_key_exists('email', $user_data) || empty($user_data['email']))
		{
			$bad_entries_array[] = "email";
			$bad_entries_description[] = "- Please enter a valid e-mail address";
		}
		else
		{
			if(!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL))
			{
				$bad_entries_array[] = "email";
				$bad_entries_description[] = "- Please enter a valid e-mail address";
			}
			else
			{
				//Check if it's duplicate entry
				$check_duplicate_email = $this->does_user_exist_with_email_except($user_data['email'], $user_id);
				if($check_duplicate_email === -1)
				{
					return false;
				}
				if($check_duplicate_email == true)
				{
					$bad_entries_array[] = "email";
					$bad_entries_description[] = "- Email already belongs to an existing user";
				}
			}
		}

		if(!array_key_exists('role', $user_data) || empty($user_data['role']))
		{
			if( $user_data['role'] != "ADMIN" &&
				$user_data['role'] != "SALES" &&
				$user_data['role'] != "OPS" &&
				$user_data['role'] != "BUSINESS" &&
				$user_data['role'] != "CREATIVE")
			{
				return false;
			}
		}
		if($user_data['role'] == "SALES")
		{
			if(!array_key_exists('partner', $user_data) || empty($user_data['partner']) || $user_data['partner'] == "-1")
			{
				$bad_entries_array[] = "partner";
				$bad_entries_description[] = "- Please select a partner";
			}
		}
		if($user_data['role'] == "BUSINESS")
		{
			if(!array_key_exists('advertiser', $user_data) || empty($user_data['advertiser']) || $user_data['advertiser'] == "-1")
			{
				$bad_entries_array[] = "advertiser";
				$bad_entries_description[] = "- Please select an advertiser";
			}
		}
		if(!array_key_exists('is_super', $user_data) || (!$user_data['is_super'] === 0 || !$user_data['is_super'] === 1))
		{
			return false;
		}

		if(array_key_exists('placements', $user_data))
		{
			if(!$user_data['placements'] === 0 && !$user_data['placements'] === 1)
			{
				return false;
			}
		}

		if(array_key_exists('screenshots', $user_data))
		{
			if (!$user_data['screenshots'] === 0 && !$user_data['screenshots'] === 1)
			{
				return false;
			}
		}

		if(array_key_exists('engagements', $user_data))
		{
			if(!$user_data['engagements'] === 0 && !$user_data['engagements'] === 1)
			{
				return false;
			}
		}

		if(array_key_exists('banner_intake', $user_data))
		{
			if(!$user_data['banner_intake'] === 0 && !$user_data['banner_intake'] === 1)
			{
				return false;
			}
		}

		if(array_key_exists('proposals', $user_data))
		{
			if(!$user_data['proposals'] === 0 && !$user_data['proposals'] === 1)
			{
				return false;
			}
		}

		if(array_key_exists('sample_ad_manager', $user_data))
		{
			if(!$user_data['sample_ad_manager'] === 0 && !$user_data['sample_ad_manager'] === 1)
			{
				return false;
			}
		}

		if(array_key_exists('sample_ad_builder', $user_data))
		{
			if(!$user_data['sample_ad_builder'] === 0 && !$user_data['sample_ad_builder'] === 1)
			{
				return false;
			}
		}

		return array("fields"=>$bad_entries_array, "messages"=>$bad_entries_description);
	}

	public function update_user($user_data, $user_id)
	{
	//Check partner id in relation to role and set accordingly
		$user_old_data = $this->get_user_info($user_id);
		$user_data['username'] = ($user_old_data['email'] != $user_data['email']) ? $user_data['email'] : $user_old_data['username'];
		$bindings = array(
							$user_data['first_name'],
							$user_data['last_name'],
							$user_data['username'],
							$user_data['email'],
							$user_data['role'],
							$user_data['advertiser'] == "" ? null : $user_data['advertiser'],
							$user_data['is_super']
						);
		$reports_permissions = "";
		if(array_key_exists('placements', $user_data))
		{
			$bindings[] = $user_data['placements'];
			$reports_permissions .= ", placements_viewable = ?";
		}
		if(array_key_exists('screenshots', $user_data))
		{
			$bindings[] = $user_data['screenshots'];
			$reports_permissions .= ", screenshots_viewable = ?";
		}
		if(array_key_exists('engagements', $user_data))
		{
			$bindings[] = $user_data['engagements'];
			$reports_permissions .= ", beta_report_engagements_viewable = ?";
		}
		if(array_key_exists('banner_intake', $user_data))
		{
			$this->process_feature_permission_request('banner_intake',$user_data['banner_intake'],$user_id);
		}

		if(array_key_exists('proposals', $user_data))
		{
			$this->process_feature_permission_request('proposals',$user_data['proposals'],$user_id);
		}

		if(array_key_exists('sample_ad_manager', $user_data))
		{
			$this->process_feature_permission_request('ad_machina_manager',$user_data['sample_ad_manager'],$user_id); // TODO: rename `ad_machina` to `sample_ads` in `pl_feature`?
		}

		if(array_key_exists('sample_ad_builder', $user_data))
		{
			$this->process_feature_permission_request('ad_machina',$user_data['sample_ad_builder'],$user_id); // TODO: rename `ad_machina` to `sample_ads` in `pl_feature`?
		}

		$role_specific_fields = "";
		if($user_data['role'] == "BUSINESS")
		{
			$role_specific_fields = ", partner_id = NULL";
		}
		if($user_data['role'] == "SALES")
		{
			$bindings = array_merge($bindings,array(
													$user_data['partner'],
													$user_data['address_1'] == "" ? null : $user_data['address_1'],
													$user_data['address_2'] == "" ? null : $user_data['address_2'],
													$user_data['city'] == "" ? null : $user_data['city'],
													$user_data['state'] == "" ? null : $user_data['state'],
													$user_data['zip'] == "" ? null : $user_data['zip'],
													$user_data['phone'] == "" ? null : $user_data['phone']));
			$role_specific_fields = ",
								partner_id = ?,
								address_1 = ?,
								address_2 = ?,
								city = ?,
								state = ?,
								zip = ?,
								phone_number = ?";

			//Remove/Add partners


			if(array_key_exists('owned_partners_to_add', $user_data) && !empty($user_data['owned_partners_to_add']))
			{
				$partners_to_add = $user_data['owned_partners_to_add'];
				$add_statements = array();
				$add_bindings = array();
				$insert_partners_query = "INSERT INTO wl_partner_owners (user_id, partner_id) VALUES ";
				foreach($partners_to_add as $partner_owner_to_add)
				{
					$add_statements[] = "(?, ?)";
					$add_bindings[] = $user_id;
					$add_bindings[] = $partner_owner_to_add;
				}
				$insert_partners_query .= implode(",", $add_statements);
				$insert_partners_query .= "ON DUPLICATE KEY UPDATE user_id = user_id";
				$insert_partner_owners_result = $this->db->query($insert_partners_query, $add_bindings);
				if($insert_partner_owners_result == false)
				{
					return false;
				}
			}

			if(array_key_exists('owned_partners_to_remove', $user_data) && !empty($user_data['owned_partners_to_remove']))
			{
				$partners_to_remove = $user_data['owned_partners_to_remove'];
				$remove_conditions = array();
				$remove_bindings = array();
				$remove_partners_query = "DELETE FROM wl_partner_owners WHERE ";
				foreach($partners_to_remove as $partner_owner_to_remove)
				{
					$remove_conditions[] = "(user_id = ? AND partner_id = ?)";
					$remove_bindings[] = $user_id;
					$remove_bindings[] = $partner_owner_to_remove;
				}
				$remove_partners_query .= implode(" OR ", $remove_conditions);

				$remove_partner_owners_result = $this->db->query($remove_partners_query, $remove_bindings);
				if($remove_partner_owners_result == false)
				{
					return false;
				}
			}

		}

		$bindings[] = $user_id;

		$update_user_query = "
			UPDATE
				users
			SET
				firstname = ?,
				lastname = ?,
				username = ?,
				email = ?,
				role = ?,
				advertiser_id = ?,
				isGroupSuper = ?".$reports_permissions.$role_specific_fields."
			WHERE
				id = ?";

		$update_user_result = $this->db->query($update_user_query, $bindings);
		if($update_user_result == false)
		{
			return false;
		}
		return true;

	}

	private function does_user_exist_with_email_except($email, $user_id)
	{
		$sql = "
			SELECT
				 1
			FROM users
			WHERE
			LOWER(email) = ?
			AND id != ?
		";
		$bindings = array($email, $user_id);
		$query = $this->db->query($sql, $bindings);
		if($query == false)
		{
			return -1;
		}
		if($query->num_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function check_user_ops_owner($user_id)
	{
		$find_owned_advertisers = "
			SELECT
				adv.Name AS advertiser_name,
				cmp.id AS campaign_id
			FROM
				users_join_advertisers_for_advertiser_owners AS uja
				JOIN Advertisers AS adv
					ON (uja.advertiser_id = adv.ID)
				JOIN Campaigns cmp
					ON (adv.ID = cmp.business_id)
			WHERE
				user_id = ?
			GROUP BY
				adv.id";
		$find_owned_advertisers_result = $this->db->query($find_owned_advertisers, $user_id);
		if($find_owned_advertisers == false)
		{
			return false;
		}
		return $find_owned_advertisers_result->result_array();
	}

	public function check_user_sales_person($user_id)
	{
		$find_sales_advertisers = "
			SELECT
				adv.Name AS advertiser_name,
				cmp.id AS campaign_id
			FROM
				Advertisers AS adv
				JOIN Campaigns cmp
					ON (adv.ID = cmp.business_id)
			WHERE
				sales_person = ?
			LIMIT 1";
		$find_sales_advertisers_result = $this->db->query($find_sales_advertisers, $user_id);
		if($find_sales_advertisers == false)
		{
			return false;
		}
		return $find_sales_advertisers_result->result_array();
	}

	public function check_user_owns_partners($user_id)
	{
		$find_owned_partners = "
			SELECT
				wpd.partner_name AS partner_name
			FROM
				wl_partner_details AS wpd
				JOIN wl_partner_owners AS wpo
					ON (wpd.id = wpo.partner_id)
			WHERE
				wpo.user_id = ?";
		$find_owned_partners_result = $this->db->query($find_owned_partners, $user_id);
		if($find_owned_partners_result == false)
		{
			return false;
		}
		return $find_owned_partners_result->result_array();
	}

	private function process_feature_permission_request($feature_html_id, $new_permission_value, $user_id)
	{
		if (!isset($feature_html_id) || !isset($new_permission_value))
		{
			return;
		}

		$current_permission_value = $this->vl_platform_model->is_feature_accessible($user_id,$feature_html_id) ? '1' : '0';

		//If no change in permission, return.
		if ($current_permission_value == $new_permission_value)
		{
			return;
		}

		$feature_id = $this->vl_platform_model->get_feature_id_by_html_id($feature_html_id);
		$permission_group_id = $this->search_or_create_user_level_permission_group($user_id);

		if (isset($permission_group_id))
		{
			$this->create_or_update_feature_permission($permission_group_id,$feature_id,$new_permission_value);
		}
	}

	private function search_or_create_user_level_permission_group($user_id)
	{
		$permission_group_id = $this->vl_platform_model->get_user_level_permission_group_id($user_id);

		if (!isset($permission_group_id))
		{
			$permission_group_id = $this->vl_platform_model->create_user_level_permission_group_id($user_id);
		}

		return $permission_group_id;
	}

	private function create_or_update_feature_permission($permission_group_id,$feature_id,$has_access)
	{
		$feature_permission = $this->vl_platform_model->get_feature_permission($permission_group_id,$feature_id);

		if (!isset($feature_permission))
		{
			return $this->vl_platform_model->create_feature_permission($permission_group_id,$feature_id,$has_access);
		}
		else
		{
			return $this->vl_platform_model->update_feature_permission($permission_group_id,$feature_id,$has_access);
		}
	}
}
?>
