<?php

class spectrum_account_model extends CI_Model
{
	private $user_options;

	private $third_party_friendly_name = 'Spectrum';

	public function __construct()
	{
		$this->load->database();
		$this->load->model('tank_auth/users');
		$this->load->library(array(
			'vl_aws_services',
			'cli_data_processor_common'
		));
		$this->load->helper(array(
			'mailgun',
			'spectrum_traffic_system_helper'
		));

		$this->user_options = (object) array(
			'new_access_manager_user_banned' => 0,
			'account_import_account_executive_banned' => 1,
			'account_import_client_or_agency_banned' => 0,

			'placements_viewable' => 1,
			'beta_report_engagements_viewable' => 1,
			'ad_sizes_viewable' => 0,

			'client_agency_screenshots_viewable' => 0,
			'account_executive_screenshots_viewable' => 1
		);
	}
	
	public function set_third_party_friendly_name($friendly_name)
	{
		$this->third_party_friendly_name = $friendly_name;
	}

        public function fetch_advertisers_users_access_manager_view($user_id, $adv_id = null)
	{
		$advertier_id_string = $this->fetch_allowed_advertisers_for_user($user_id);		
		return $this->fetch_all_agency_client_users_for_adv($advertier_id_string, $adv_id);
	}

	public function fetch_advertisers_for_adgroup($user_id, $search_value, $rows_start, $rows_length, $selected_advertiser_id_string, $advertiser_group_id)
	{
		$advertier_id_string=$this->fetch_allowed_advertisers_for_user($user_id);
		return $this->fetch_advertiser_group_select2($advertier_id_string, $search_value, $rows_start, $rows_length, $selected_advertiser_id_string, $advertiser_group_id);
	}

	private function fetch_allowed_advertisers_for_user($user_id)
	{
		$user = $this->users->get_user_by_id($user_id, 1);
		$advertier_id_string="";
		$role=null;
		$is_group_super=0;
		if ($user != null)
		{
			$role=$user->role;
			$is_group_super=$user->isGroupSuper;
		}
		if ($role == 'SALES')
		{
			$advertier_id_string="-1";
			$advertiser_array=$this->users->get_advertisers_by_sales_person_partner_hierarchy($user_id, $is_group_super);
			$advertiser_id_array=array();
			if ($advertiser_array != null)
			{
				foreach ($advertiser_array as $row)
				{
					$advertiser_id_array[]=$row['id'];
				}
				$advertier_id_string=implode(",", $advertiser_id_array);
			}
		}
		else if ($role == 'CLIENT' || $role == 'AGENCY')
		{
			$advertier_id_string="-1";
			$sql = "
				SELECT
					DISTINCT advertiser_id
				FROM
					clients_to_advertisers
				WHERE
					is_power_user = 1 AND
					user_id = ?
			";
			$bindings=array($user_id);
			$query = $this->db->query($sql, $bindings);
			$adv_array = array();
			if($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					 $adv_array[]=$row['advertiser_id'];
				}
				$advertier_id_string = implode(',' , $adv_array);
			}
		}
		return $advertier_id_string;
	}

	public function get_user_access_for_access_manager($user_id)
	{
		$user = $this->users->get_user_by_id($user_id, 1);
		$advertier_id_string="";
		$role=null;
		$is_group_super=0;
		if ($user != null)
		{
			$role=$user->role;
			$is_group_super=$user->isGroupSuper;
		}
		if ($role == 'CLIENT' || $role == 'AGENCY')
		{
			$sql = "
				SELECT
					DISTINCT advertiser_id
				FROM
					clients_to_advertisers
				WHERE
					is_power_user = 1 AND
					user_id = ?
			";
			$bindings=array($user_id);
			$query = $this->db->query($sql, $bindings);
			if($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					 return true;
				}
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	public function get_advertiser_data_datatable()
	{
		$output = array(
			"aaData" => array()
		);
		$this->db->select('id, name');
		$query=$this->db->get('Advertisers');
		$output["aaData"] = $query->result();
		return $output;
	}
        
        public function fetch_all_agency_client_users_for_adv($advertier_id_string, $adv_id = null)
	{		
                if ($advertier_id_string != "" && $adv_id == '')
                {
                        $advertier_id_string = ' WHERE a.id IN ('.$advertier_id_string.')';
                }
                
                // To get the advertiser users to refresh data                
                if ($adv_id != '')
                {
                        $advertier_id_string = ' WHERE a.id IN ('.$adv_id.')';
                }
                
                $sql = "
                    SELECT
                            main.adv_id,
                            main.adv_name AS adv_name,
                            main.ag_name AS ag_name,
                            DATE_FORMAT(main.adv_created_date,'%Y-%m-%d') AS adv_created_date,
                            ptr.partner_name AS partner_name,
                            main.ag_id AS ag_id,
                            main.user_id AS user_id,
                            COALESCE(main.first_name, '') AS first_name,
                            COALESCE(main.last_name, '') AS last_name,
                            main.email AS email,
                            main.role AS role,
                            DATE_FORMAT(main.last_login,'%Y-%m-%d') AS activation_email_date,
                            DATE_FORMAT(main.user_created_date,'%Y-%m-%d') AS user_created_date,
                            DATE_FORMAT(main.registration_date,'%Y-%m-%d') AS registration_date,
                            main.is_power_user,
                            main.resend_link_flag,
                            main.ul_id,
                            main.agency_name,
                            main.traffic_system,
                            COALESCE(u.firstname, '') AS sales_first_name,
                            COALESCE(u.lastname, '') AS sales_last_name

                    FROM
                    (
                            SELECT
                                    a.id AS adv_id,
                                    a.name AS adv_name,
                                    a.sales_person,
                                    a.created_date AS adv_created_date,
                                    g.name AS ag_name,
                                    g.id AS ag_id,
                                    u.id AS user_id,
                                    u.firstname AS first_name,
                                    u.lastname AS last_name,
                                    u.email AS email,
                                    u.role AS role,                                
                                    u.last_login,
                                    u.created AS user_created_date,
                                    u.registration_date,
                                    l.is_power_user,
                                    tsa.ul_id,
                                    tsai.agency_name,
                                    tsai.traffic_system_raw AS traffic_system,
                                    CASE
                                            WHEN new_password_key IS NULL
                                            THEN
                                                    NULL
                                            ELSE '1'
                                            END AS 'resend_link_flag'
                            FROM
                                    Advertisers a
                                    LEFT JOIN
                                            clients_to_advertisers l 
                                            ON a.id = l.advertiser_id
                                    LEFT JOIN
                                            advertiser_groups_to_advertisers ga 
                                            ON ga.advertiser_id = a.id
                                    LEFT JOIN
                                            advertiser_groups g 
                                            ON g.id = ga.advertiser_group_id
                                    LEFT JOIN
                                            users u 
                                            ON (u.id = user_id AND u.role IN ('CLIENT', 'AGENCY'))				
                                    LEFT JOIN
                                            tp_spectrum_accounts tsa
                                            ON tsa.advertiser_id = a.id
                                    LEFT JOIN
                                            tp_spectrum_accounts_import tsai
                                            ON tsai.advertiser_ul_id = tsa.ul_id
                                    $advertier_id_string                                    
                    ) AS main
                            JOIN users u
                                    ON main.sales_person = u.id
                            JOIN wl_partner_details ptr
                                    ON ptr.id = u.partner_id
                    ORDER BY adv_name, first_name, last_name";
		$query = $this->db->query($sql);
		$advertiser_array = null;

		if($query->num_rows() > 0)
		{
			$output_new = array();
			$output_new_export = array();
			$prev_adv_id = "-1";
			$prev_row = null;
			$user_array = array();
			foreach($query->result_array() as $row)
			{
                                $adv_id = $row['adv_id'];
                                if ($prev_adv_id != "-1" && $prev_adv_id != $adv_id)
                                {
                                        $output_new_sub=array();
                                        $output_new_sub['adv_id']=$prev_row['adv_id'];
                                        $output_new_sub['ag_id']=$prev_row['ag_id'];
                                        $output_new_sub['ag_name']=$prev_row['ag_name'];
                                        $output_new_sub['adv_created_date']=$prev_row['adv_created_date'];
                                        $output_new_sub['partner_name']=$prev_row['partner_name'];
                                        $output_new_sub['adv_name']=$prev_row['adv_name'];
                                        $output_new_sub['agency_name']=$prev_row['agency_name'];
                                        $output_new_sub['ul_id']=$prev_row['ul_id'];
                                        $output_new_sub['traffic_system']=$prev_row['traffic_system'];
                                        $output_new_sub['users']=$user_array;
                                        $output_new[]=$output_new_sub;
                                        $user_array=array();
                                }

                                $user_array_sub=array();
                                $user_array_sub['user_id']=$row['user_id'];
                                $user_array_sub['first_name']=$row['first_name'];
                                $user_array_sub['last_name']=$row['last_name'];
                                $user_array_sub['email']=$row['email'];
                                $user_array_sub['role']=$row['role'];
                                $user_array_sub['is_power_user']=$row['is_power_user'];
                                $user_array_sub['resend_link_flag']=$row['resend_link_flag'];
                                $user_array_sub['activation_email_date']=$row['activation_email_date'];
                                $user_array_sub['user_created_date']=$row['user_created_date'];
                                $user_array_sub['registration_date']=$row['registration_date'];
                                $user_array_sub['sales_first_name']=$row['sales_first_name'];
                                $user_array_sub['sales_last_name']=$row['sales_last_name'];

                                $user_array[]=$user_array_sub;
                                $prev_adv_id = $adv_id;
                                $prev_row=$row;
			}

                        $output_new_sub=array();
                        $output_new_sub['adv_id']=$prev_row['adv_id'];
                        $output_new_sub['ag_id']=$prev_row['ag_id'];
                        $output_new_sub['adv_name']=$prev_row['adv_name'];
                        $output_new_sub['adv_created_date']=$prev_row['adv_created_date'];
                        $output_new_sub['ag_name']=$prev_row['ag_name'];
                        $output_new_sub['partner_name']=$prev_row['partner_name'];
                        $output_new_sub['agency_name']=$prev_row['agency_name'];
                        $output_new_sub['ul_id']=$prev_row['ul_id'];
                        $output_new_sub['traffic_system']=$prev_row['traffic_system'];
                        $output_new_sub['users']=$user_array;
                        $output_new[]=$output_new_sub;
                        $output_new_array=array();
                        $output_new_array = $output_new;

                        $output['view_data'] = $output_new_array;
                        
			return $output;                    
		}
        }        

	private function fetch_advertiser_group_select2($advertier_id_string, $search_value, $rows_start, $rows_length, $selected_advertiser_id_string, $advertiser_group_id)
	{
		$output_new_array=array();
		$return_array=array();
		$sql = "";
		if ($advertier_id_string != "")
		{
			$advertier_id_string = ' AND a.id IN ('.$advertier_id_string.')';
		}

		if ($selected_advertiser_id_string != null && $selected_advertiser_id_string != "")
		{
			$selected_advertiser_id_string .= "-1";

			$sql .= "
			SELECT
				aa.id AS id,
				aa.name AS text
			FROM
				Advertisers aa
			WHERE
				aa.id IN ($selected_advertiser_id_string)
				AND
				aa.id NOT IN
				(
					SELECT
						advertiser_id
					FROM
						advertiser_groups_to_advertisers
				)
			";
			$query = $this->db->query($sql);
		}
		else if ($advertiser_group_id != null && $advertiser_group_id != "")
		{
			$sql = "
				SELECT
					a.id AS id,
					a.name AS text
				FROM
					Advertisers a
				WHERE
					a.id IN
					(
						SELECT
							advertiser_id
						FROM
							advertiser_groups_to_advertisers
						WHERE advertiser_group_id = $advertiser_group_id
					)
				ORDER BY
					a.name
				";
				$query = $this->db->query($sql);

				$sql_ag = "
				SELECT
					name
				FROM
					advertiser_groups
				WHERE
					id = ?
				";

				$query_ag = $this->db->query($sql_ag, $advertiser_group_id);

				if($query_ag->num_rows() > 0)
				{
					foreach($query_ag->result_array() as $row)
					{
						$return_array['ag_name']=$row['name'];
					}
				}
		}
		else
		{
			$sql = "
				SELECT
					a.id AS id,
					a.name AS text
				FROM
					Advertisers a
					WHERE a.name like ?
					$advertier_id_string
				AND
					a.id NOT IN
					(
						SELECT
							advertiser_id
						FROM
							advertiser_groups_to_advertisers
					)
				ORDER BY
					a.name
				LIMIT 100";
				$search_value = "%".str_replace("'", "''", $search_value)."%";
				//$bindings = array($search_value, intval($rows_start), intval($rows_length));
				$bindings = array($search_value);
				$query = $this->db->query($sql, $bindings);
		}

		$advertiser_array = null;
		if($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$output_new_sub=array();
				$output_new_sub['id']=$row['id'];
				$output_new_sub['text']=$row['text'];
				$output_new_array[]=$output_new_sub;
			}
		}
		$return_array['result']=$output_new_array;
		return $return_array;
	}

	public function advertiser_group_submit($user_id, $advertiser_group_name, $advertiser_group, $advertiser_group_id)
	{
		$return_array=array();
		//validation section
		$sql = "
				SELECT
					name
				FROM
					advertiser_groups
				WHERE LOWER(name)=LOWER(?)
			";
		$bindings = array($advertiser_group_name);
		if ($advertiser_group_id != null && $advertiser_group_id != "")
		{
			$sql .= " AND id != ? ";
			$bindings[]=$advertiser_group_id;
		}
		$sql .= "
		UNION
			SELECT
				name
			FROM
				Advertisers
			WHERE LOWER(name)=LOWER(?)
		";
		$bindings[]=$advertiser_group_name;

		if ($advertiser_group != null && $advertiser_group != "")
		{
			$sql .= " AND id NOT IN  (-1, $advertiser_group) ";
		}
		$query = $this->db->query($sql, $bindings);

		if($query->num_rows() > 0)
		{
			$return_array['is_success']='false';
			$return_array['is_validation_errors']='true';
			$return_array['is_duplicate_adgroup_name']='true';
			$return_array['error_message']='Duplicate Advertiser Group OR Advertiser name';
			return $return_array;
		}
		//validation end

		if ($advertiser_group_id == null || $advertiser_group_id == "")
		{
			$sql = "
				INSERT INTO advertiser_groups
				(
					name, created_by
				)
				VALUES (?, ?)
			";

			$bindings = array($advertiser_group_name, $user_id);
			$query = $this->db->query($sql, $bindings);

			$sql = "
				SELECT
					id
				FROM
					advertiser_groups
				WHERE name=?
			";
			$bindings = array($advertiser_group_name);
			$query = $this->db->query($sql, $bindings);
			if($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$advertiser_group_id=$row['id'];
				}
			}

		}
		else
		{
			$sql = "
				DELETE
					FROM
				advertiser_groups_to_advertisers
				WHERE
					advertiser_group_id = ?
			";

			$bindings = array($advertiser_group_id);
			$query = $this->db->query($sql, $bindings);

			$sql = "
				UPDATE
					advertiser_groups
				SET
					name = ?
				WHERE
					id = ?
			";

			$bindings = array($advertiser_group_name, $advertiser_group_id);
			$query = $this->db->query($sql, $bindings);
		}

		$advertiser_group_array = explode(",", $advertiser_group);
		foreach ($advertiser_group_array as $row) {
			 $sql = "
				INSERT INTO
				advertiser_groups_to_advertisers
				(
					advertiser_group_id,
					advertiser_id
				)
				VALUES
				(?, ?)
			";

			$bindings = array($advertiser_group_id, $row);
			$query = $this->db->query($sql, $bindings);
		}

		$return_array['is_success']='true';
		return $return_array;
	}

	public function advertiser_group_delete($advertiser_group_id)
	{
		$return_array=array();
		$return_array['is_success']='false';
		$sql = "
				DELETE FROM
					advertiser_groups_to_advertisers
				WHERE
					advertiser_group_id = ?
			";

		$query = $this->db->query($sql, $advertiser_group_id);

		$sql = "
				DELETE FROM
					advertiser_groups
				WHERE
					id = ?
			";

		$query = $this->db->query($sql, $advertiser_group_id);
		if ($this->db->affected_rows() > 0)
		{
			$return_array['is_success']='true';

		}
		return $return_array;
	}

	public function update_last_login_flag_for_user($user_id)
	{
		$return_array=array();
		$return_array['is_success']='false';

		$sql = "
				UPDATE users
				SET
					last_login = CURRENT_TIMESTAMP
				WHERE
					id = ?
			";

		$query = $this->db->query($sql, $user_id);
		if ($this->db->affected_rows() > 0)
		{
			$return_array['is_success']='true';

		}
		return $return_array;
	}

	public function edit_advertiser_user($first_name, $last_name, $email, $is_power_user, $role, $adv_id, $user_id)
	{
		$success_flag = 'No changes';
		$bindings=array($email, $user_id);
		$sql=
		"
		SELECT
			*
		FROM
			users
		WHERE
			LOWER(email) = LOWER(?) AND
			id != ?
			";
		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$success_flag = 'We were not able to edit the user. Email Address already in use. Please try again with another email.';
				return $success_flag;
			}
		}

		$sql = "
			UPDATE users
			SET	
				username = ?,
				firstname = ?,
				lastname = ?,
				email= ?,
				role = ?
			WHERE
				id = ?
		";
		$bindings=array($email, $first_name, $last_name, $email, $role, $user_id);
		$query = $this->db->query($sql, $bindings);

		if ($this->db->affected_rows() > 0) {
			$success_flag = 'User updated';
		}
		$sql = "
		UPDATE clients_to_advertisers
			SET is_power_user = ?
			WHERE
				user_id = ? AND
				advertiser_id = ?
		";
		$bindings=array($is_power_user, $user_id, $adv_id);
		$query = $this->db->query($sql, $bindings);
		if ($this->db->affected_rows() > 0) {
			$success_flag = 'User updated';
		}
		return $success_flag;
	}

	public function remove_advertiser_user($adv_id, $user_id)
	{
		$return_message="";
		$bindings=array($adv_id, $user_id);
		$sql=
		"
		SELECT
			advertiser_id
		FROM
			clients_to_advertisers
		WHERE
			advertiser_id = ? AND
			is_power_user = 1 AND
			user_id != ?
		GROUP BY
			advertiser_id
			";
		$query = $this->db->query($sql, $bindings);
		$delete_allowed_flag=false;
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$delete_allowed_flag=true;
			 }
		}

		if (!$delete_allowed_flag)
		{
			$sql=
			"
			SELECT
				advertiser_id
			FROM
				clients_to_advertisers
			WHERE
				advertiser_id = ? AND
				is_power_user = 0 AND
				user_id = ?
			GROUP BY
				advertiser_id
				";
			$query = $this->db->query($sql, $bindings);
			if($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$delete_allowed_flag=true;
				}
			}
		}

		$bindings=array($user_id, $adv_id);
		if ($delete_allowed_flag)
		{
			$sql = "
			DELETE FROM clients_to_advertisers
			WHERE
				user_id = ? AND
				advertiser_id = ?
			";

			$query = $this->db->query($sql, $bindings);
			if ($this->db->affected_rows() > 0)
			{
				$return_message="User successfully removed from Advertiser";
			}
		}
		else
		{
			$return_message="Cannot remove user. Please add another Admin user before removing this user";
		}
		return $return_message;
	}

	public function remove_advertiser_user_bulk($adv_id, $email)
	{
		$email=implode("','" , explode(',', $email));
		$adv_id_string=$this->make_bindings_sql_from_string($adv_id);
		$adv_array=explode(",",$adv_id);
		$return_message="";

		$sql = "
			SELECT DISTINCT a.id AS id,
				a.name
			FROM
				Advertisers a
			WHERE
				a.id NOT IN
				(
					SELECT
						DISTINCT a.id AS id
					FROM clients_to_advertisers c,
					  	Advertisers a
					WHERE
						advertiser_id IN (".$adv_id_string.") AND
						is_power_user = 1 AND
						c.user_id NOT IN
						(
							SELECT
								id
							FROM
								users uu
							WHERE
								uu.email IN ('$email')
						)
						AND
						c.advertiser_id IN
						(
							SELECT
								a.advertiser_id
							FROM
								users uu,
								clients_to_advertisers a
							WHERE
								uu.email IN ('$email')
								AND uu.id=a.user_id
						)
						AND advertiser_id = a.id
						GROUP BY
							a.id
						HAVING
							COUNT(user_id) > 0
				)
				AND a.id IN
				(
					SELECT
						a.advertiser_id
					FROM
						users uu,
						clients_to_advertisers a
					WHERE
						uu.email IN ('$email')
					AND uu.id=a.user_id
				)
				AND a.id IN
					(".$adv_id_string.")
		";

		$bindings=array_merge($adv_array, $adv_array);
		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			$return_message = '<br><br>Unable to Bulk delete. List of Advertisers where selected user(s) are the only Admin(s) for these Advertisers:<br>';
			foreach ($query->result_array() as $row)
			{
				$return_message .= '<li> '.$row['name'].'</li>';
			}
			return $return_message;
		}

		$sql = "
		DELETE FROM clients_to_advertisers
		WHERE
			user_id IN
			(
				SELECT
					id
				FROM
					users
				WHERE
					email IN ('$email')
			) AND
			advertiser_id IN (".$adv_id_string.")
		";
		$return_message="User(s) removed";
		$query = $this->db->query($sql, $adv_array);
		if ($this->db->affected_rows() > 0)
		{
			$return_message="Selected User(s) removed from Advertisers";
		}
		else
		{
			$return_message="Unable to remove users from Advertisers";
		}
		return $return_message;

	}

	public function user_client_agency_exists_flag($email)
	{
		$sql = "
		SELECT id FROM users
			WHERE
				email = ? AND
				role in ('CLIENT', 'AGENCY')
			LIMIT 1
		";
		$bindings=array($email);
		$query = $this->db->query($sql, $bindings);
		if ($this->db->affected_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				return true;
			}
		}
		return false;
	}

	public function add_advertiser_user($first_name, $last_name, $email, $is_power_user, $role, $adv_id, $user_id)
	{
		$return_flag="";
		$duplicate_user_flag = '0';
		$return_data=array();
		$sql = "
			SELECT
				id,
				firstname,
				lastname
			FROM
				users
			WHERE
				LOWER(email) = LOWER(?) AND
				role in ('CLIENT', 'AGENCY')
			LIMIT 1
		";
		$bindings=array($email);
		$query = $this->db->query($sql, $bindings);
		$user_id = "-1";
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$user_id = $row['id'];
				break;
			 }
		}

		if ($user_id == '-1')
		{

			$sql = "
				SELECT
					id,
					firstname,
					lastname
				FROM
					users
				WHERE
					LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)
				LIMIT 1
			";
			$bindings=array($email, $email);
			$query = $this->db->query($sql, $bindings);
			if($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$return_flag ="Cannot add this user for this Email (".$email."). Please use a different Email or contact Admin";
					$duplicate_user_flag='1';
					break;
				 }
			}
		}
		if ($duplicate_user_flag == '0')
		{
			if ($user_id == "-1")
			{
				$partner_id="3";
				$advertiser_name="Vantage Local";
				$sql = '
					SELECT
						a.partner_id,
						b.name
					FROM
						users a
					JOIN Advertisers b ON a.id = b.sales_person
					JOIN wl_partner_details c ON a.partner_id = c.id
					WHERE b.id = ?';

			  	$query = $this->db->query($sql, $adv_id);
			  	if($query->num_rows() > 0)
			    {
			      $temp = $query->row_array();
			      $partner_id = $temp['partner_id'];
			      $advertiser_name = $temp['name'];
			    }

				$sql = "
					INSERT INTO users (
						firstname,
						lastname,
						email,
						role,
						username,

						banned,
						created,
						business_name,
						partner_id,
						placements_viewable,

						beta_report_engagements_viewable,
						ad_sizes_viewable,
						screenshots_viewable
					)
					VALUES
						(?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?)
				";
				$bindings=array(
					$first_name,
					$last_name,
					$email,
					$role,
					$email,

					$this->user_options->new_access_manager_user_banned,
					// CURRENT_TIMESTAMP
					$advertiser_name,
					$partner_id,
					$this->user_options->placements_viewable,

					$this->user_options->beta_report_engagements_viewable,
					$this->user_options->ad_sizes_viewable,
					$this->user_options->client_agency_screenshots_viewable
				);
				$query = $this->db->query($sql, $bindings);
				$sql = "
					SELECT
						id
					FROM
						users
					WHERE
						LOWER(email) = LOWER(?) AND
						role in ('CLIENT', 'AGENCY')
					LIMIT 1
				";
				$bindings=array($email);
				$query = $this->db->query($sql, $bindings);
				$user_id = "-1";
				if($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$user_id = $row['id'];
						break;
					}
				}
				$return_flag .="Created new user ";
				$return_data['user_id']=$user_id;
			}

			$sql = "
				SELECT
					count(*) AS count
				FROM
					clients_to_advertisers
				WHERE
					advertiser_id = ? AND
					user_id = ?
			";
			$bindings=array($adv_id, $user_id);
			$query = $this->db->query($sql, $bindings);
			$count = 0;
			if($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$count = $row['count'];
				}
			}
			if ($count == '0')
			{
				$sql = "
					INSERT IGNORE INTO clients_to_advertisers
						(advertiser_id, user_id, is_power_user)
					VALUES
						(?, ?, ?)
				";
				$bindings=array($adv_id, $user_id, $is_power_user);
				$query = $this->db->query($sql, $bindings);
				if ($this->db->affected_rows() > 0)
				{
					$return_flag .="Added user to Advertiser(s)";
				}
			}
			else
			{
				$return_flag .=" User already exists for this Advertiser";
			}
		}
		$return_data['return_flag']=$return_flag;
		return $return_data;
	}

	public function fetch_user_info_by_email($user_id, $search_term, $adv_id, $start, $limit)
	{
		$search_term=strtolower($search_term);
		if ($adv_id == '')
		{
			$adv_id=$this->fetch_allowed_advertisers_for_user($user_id);

		}
		$sql="
			SELECT
				email AS id,
				CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''), ' (',email, ')')  AS tag_copy
			FROM
				users
			WHERE
				(
					LOWER(email) LIKE ? OR
					LOWER(firstname) LIKE ? OR
					LOWER(lastname) LIKE ?
				)
				AND role IN ('AGENCY', 'CLIENT') ";
		if ($adv_id != "")
		{
			$sql.="
				AND id IN
				(
					SELECT
						user_id
					FROM
						clients_to_advertisers
					WHERE
						advertiser_id IN (".$this->make_bindings_sql_from_string($adv_id).")
				)
				";
		}
		$sql.="
			ORDER BY EMAIL
			LIMIT ?, ? ";

		$bindings=array($search_term, $search_term, $search_term);
		if ($adv_id != "")
		{

			$adv_array=explode(",",$adv_id);
			$bindings=array_merge($bindings, $adv_array);
		}
		$bindings[]=$start;
		$bindings[]=$limit;
		$query=$this->db->query($sql, $bindings);
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}

	private function make_bindings_sql_from_string($elements)
	{
		$elements=explode(',', $elements);
		return implode(',', array_map(function ($element) { return '?'; }, $elements));
	}

	public function create_new_spectrum_accounts_table($table_name)
	{
		$sql = "
			CREATE TABLE IF NOT EXISTS `$table_name` (
			  `advertiser_ul_id` varchar(63) COLLATE utf8_unicode_ci NULL,
			  `advertiser_ul_id_raw` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `advertiser_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `advertiser_eclipse_id` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `advertiser_eclipse_id_raw` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `newest_order_date` date NOT NULL,
			  `primary_commodity_code` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `primary_commodity` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `secondary_commodity_code` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `secondary_commodity` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `client_email` varchar(63) COLLATE utf8_unicode_ci NULL,
			  `client_first_name` varchar(127) COLLATE utf8_unicode_ci NULL,
			  `client_last_name` varchar(127) COLLATE utf8_unicode_ci NULL,
			  `agency_account_ul_id` varchar(63) COLLATE utf8_unicode_ci NULL,
			  `agency_account_ul_id_raw` varchar(63) COLLATE utf8_unicode_ci NULL,
			  `agency_name` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `agency_email` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `account_executive_name` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `account_executive_ul_id` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `account_executive_ul_id_raw` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `sales_office_name` varchar(255) COLLATE utf8_unicode_ci NULL,
			  `sales_office_ul_id` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `sales_office_ul_id_raw` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `traffic_system` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `traffic_system_raw` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
			  `client_create_date` datetime DEFAULT '1970-01-01 00:00:00',
			  `csv_line_number` int(11) NOT NULL ,
			  UNIQUE KEY `start_primary` (`advertiser_ul_id_raw`,`traffic_system_raw`),
			  UNIQUE KEY `advertiser_ul_id` (`advertiser_ul_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		// TODO: Add primary key after `advertiser_ul_id` is populated correctly?
		//PRIMARY KEY (`advertiser_ul_id`)
		//ALTER TABLE `tp_spectrum_accounts` ADD PRIMARY KEY(`advertiser_ul_id`);

		return $this->db->query($sql);
	}

	public function rename_table($old_name, $new_name)
	{
		$sql = "
			RENAME TABLE $old_name TO $new_name;
		";

		return $this->db->query($sql);
	}

	public function drop_table($table_name)
	{
		$sql = "
			DROP TABLE IF EXISTS $table_name;
		";
		return $this->db->query($sql);
	}

	private function note_other_error(&$notifications, $message)
	{
		$notifications->errors[] = $message;

		echo "\t\t".$message."\n";
	}

	private function note_row_error(&$notifications, $row_index, $row_data, $message_input)
	{
		$message = $message_input." line #$row_data->new_csv_line_number";

		$notifications->errors[] = $message;
		if(!array_key_exists($row_index, $notifications->error_row_data))
		{
			$notifications->error_row_data[$row_index] = $row_data;
			$notifications->error_row_data[$row_index]->errors = array();
		}

		$notifications->error_row_data[$row_index]->errors[] = $message;

		echo "\t\t".$message."\n";
	}

	private function add_account_executive(
		&$relationships,
		&$actions,
		&$stats,
		&$values_sql,
		&$values_bindings,
		$new_row
	)
	{
		$num_added_items = 0;

		if(!array_key_exists($new_row->new_account_executive_ul_id, $relationships->dedupe_new_account_executive_users))
		{
			$relationships->dedupe_new_account_executive_users[$new_row->new_account_executive_ul_id] = true;
			$stats->unknown_account_executives[$new_row->new_account_executive_ul_id] = $new_row->new_account_executive_name;

			$role = 'SALES';

			$this->add_values_and_bindings(
				$values_sql,
				$values_bindings,
				array(
					$new_row->new_account_executive_ul_id,
					$this->user_options->account_import_account_executive_banned,
					$role,
					$this->user_options->placements_viewable,
					$this->user_options->account_executive_screenshots_viewable,
					$this->user_options->beta_report_engagements_viewable,
					$this->user_options->ad_sizes_viewable,
					$new_row->new_match_sales_office_partner_id,
					$new_row->new_account_executive_ul_id,
					$new_row->new_account_executive_ul_id.'@unknown-email'
				)
			);

			$relationships->new_account_executive_ul_ids[] = $new_row->new_account_executive_ul_id;

			$num_added_items = 1;
		}

		return $num_added_items;
	}

	public function process_table_deltas(
		StdClass &$stats,
		StdClass &$notifications,
		$current_accounts_table,
		$new_accounts_table
	)
	{
		$this->print_progress_message("Start - process_table_deltas()");
		$new_accounts_spectrum_table_alias = "new";
		$new_accounts_associated_table_alias_prefix = "new";
		$new_accounts_field_prefix = "new";

		$current_accounts_spectrum_table_alias = "current";
		$current_accounts_associated_table_alias_prefix = "current";
		$current_accounts_field_prefix = "current";

		$spectrum_accounts_fields = array(
			'advertiser_ul_id',
			'advertiser_eclipse_id',
			'advertiser_name',
			'newest_order_date',
			'primary_commodity_code',
			'primary_commodity',
			'secondary_commodity_code',
			'secondary_commodity',
			'client_email',
			'client_first_name',
			'client_last_name',
			'agency_account_ul_id',
			'agency_name',
			'agency_email',
			'account_executive_name',
			'account_executive_ul_id',
			'sales_office_name',
			'sales_office_ul_id',
			'traffic_system',
			'traffic_system_raw',
			'client_create_date',
			'csv_line_number'
		);

		$spectrum_accounts_fields_select_template_sql = '';
		foreach($spectrum_accounts_fields as $field_name)
		{
			$spectrum_accounts_fields_select_template_sql .= "YYY.$field_name AS CCC_$field_name,";
		}

		$newest_order_cutoff_date = '2014-09-01';
		$place_holder_user_ul_id_prefix = '900';

		$related_data_select_template_sql = "
			DDD_ae_users.id AS CCC_match_ae_user_id, /* Does related AE exist 								8.3 	*/
			DDD_ae_users.email AS CCC_match_ae_user_email, /* Does related AE have email 						8.3 	*/
			DDD_ae_users.partner_id AS CCC_match_ae_user_partner_id, /* partner_id for AE 					9.2 	*/
			DDD_ae_users_partner.partner_name AS CCC_match_ae_user_partner_name,
			DDD_client_users.id AS CCC_match_client_user_id, /* Client id for current email association 		1.4.2 	*/
			DDD_client_users.role AS CCC_match_client_user_role, /* Client email current association 		1.4.1 	*/
			DDD_client_users.partner_id AS CCC_match_client_user_partner_id,
			DDD_client_users_partners.partner_name AS CCC_match_client_user_partner_name,
			DDD_agency_email_users.id AS CCC_match_agency_email_user_id, /* Agency user by email 				5.3 	*/
			DDD_agency_email_users.role AS CCC_match_agency_email_user_role, /* Agency user by email 		5.3 	*/
			DDD_agency_email_users.partner_id AS CCC_match_agency_email_user_partner_id,
			DDD_agency_email_users_partners.partner_name AS CCC_match_agency_email_user_partner_name,
			DDD_sales_offices.partner_id AS CCC_match_sales_office_partner_id,	/* Sales Office partner id 	9.2		*/
			DDD_sales_offices_partners.partner_name AS CCC_match_sales_office_partner_name,
			DDD_sales_offices_partners.cname AS CCC_match_sales_office_partner_cname,
			DDD_sales_office_place_holder_user.id AS CCC_match_sales_office_place_holder_account_executive_user_id
		";

		$related_data_from_template_sql = "
			LEFT JOIN users AS DDD_ae_users
				ON (YYY.account_executive_ul_id = DDD_ae_users.spectrum_account_executive_id)
			LEFT JOIN wl_partner_details AS DDD_ae_users_partner
				ON (DDD_ae_users.partner_id = DDD_ae_users_partner.id)
			LEFT JOIN users AS DDD_client_users
				ON (LOWER(YYY.client_email) = LOWER(DDD_client_users.email))
			LEFT JOIN wl_partner_details AS DDD_client_users_partners
				ON (DDD_client_users.partner_id = DDD_client_users_partners.id)
			LEFT JOIN users AS DDD_agency_email_users
				ON (LOWER(YYY.agency_email) = LOWER(DDD_agency_email_users.email))
			LEFT JOIN wl_partner_details AS DDD_agency_email_users_partners
				ON (DDD_agency_email_users.partner_id = DDD_agency_email_users_partners.id)
			LEFT JOIN tp_spectrum_sales_offices AS DDD_sales_offices
				ON (YYY.sales_office_ul_id = DDD_sales_offices.spectrum_id)
			LEFT JOIN wl_partner_details AS DDD_sales_offices_partners
				ON (DDD_sales_offices.partner_id = DDD_sales_offices_partners.id)
			LEFT JOIN users AS DDD_sales_office_place_holder_user
				ON (CONCAT('$place_holder_user_ul_id_prefix', YYY.sales_office_ul_id) = DDD_sales_office_place_holder_user.spectrum_account_executive_id)

		";

		$new_spectrum_accounts_fields_select_sql = str_replace(
			array("YYY", "CCC"),
			array($new_accounts_spectrum_table_alias, $new_accounts_field_prefix),
			$spectrum_accounts_fields_select_template_sql
		);
		$new_related_data_select_sql = str_replace(
			array("CCC", "DDD"),
			array($new_accounts_field_prefix, $new_accounts_associated_table_alias_prefix),
			$related_data_select_template_sql
		);
		$new_related_data_from_sql = str_replace(
			array("YYY", "DDD", "TTT"),
			array($new_accounts_spectrum_table_alias, $new_accounts_associated_table_alias_prefix, $current_accounts_table),
			$related_data_from_template_sql
		);

		$current_spectrum_accounts_fields_select_sql = str_replace(
			array("YYY", "CCC"),
			array($current_accounts_spectrum_table_alias, $current_accounts_field_prefix),
			$spectrum_accounts_fields_select_template_sql
		);
		$current_related_data_select_sql = str_replace(
			array("CCC", "DDD"),
			array($current_accounts_field_prefix, $current_accounts_associated_table_alias_prefix),
			$related_data_select_template_sql
		);
		$current_related_data_from_sql = str_replace(
			array("YYY", "DDD", "TTT"),
			array($current_accounts_spectrum_table_alias, $current_accounts_associated_table_alias_prefix, $current_accounts_table),
			$related_data_from_template_sql
		);

		$get_modified_rows_sql = "
			SELECT				/*	3,4,5,6,7,8,9				*/
				adv_match.advertiser_id AS match_advertiser_id,
				advertiser_client_ids.advertiser_client_id AS client_user_matching_advertiser,
				advertiser_agency_ids.advertiser_agency_id AS agency_user_matching_advertiser,
				$new_spectrum_accounts_fields_select_sql
				$new_related_data_select_sql,
				$current_spectrum_accounts_fields_select_sql
				$current_related_data_select_sql
			FROM
				$new_accounts_table AS $new_accounts_spectrum_table_alias
				JOIN $current_accounts_table AS $current_accounts_spectrum_table_alias
					ON ($new_accounts_spectrum_table_alias.advertiser_ul_id = $current_accounts_spectrum_table_alias.advertiser_ul_id AND
						(
							$current_accounts_spectrum_table_alias.newest_order_date >= '$newest_order_cutoff_date' OR
							$current_accounts_spectrum_table_alias.client_create_date >= '$newest_order_cutoff_date'
						)
					)
				JOIN tp_spectrum_accounts AS adv_match
					ON ($new_accounts_spectrum_table_alias.advertiser_ul_id = adv_match.ul_id)
				LEFT JOIN tp_spectrum_accounts_ignored_items AS ignored_accounts
					ON($new_accounts_spectrum_table_alias.advertiser_ul_id = ignored_accounts.spectrum_account_ul_id)
				LEFT JOIN (
					SELECT
						clients_cta.advertiser_id,
						clients_cta.user_id AS advertiser_client_id
					FROM
						clients_to_advertisers AS clients_cta
						JOIN users
							ON (clients_cta.user_id = users.id AND
								clients_cta.is_power_user = 1 AND
								users.role = 'CLIENT'
							)
						GROUP BY
							advertiser_id
				) AS advertiser_client_ids
					ON (adv_match.advertiser_id = advertiser_client_ids.advertiser_id)
				LEFT JOIN (
					SELECT
						agencies_cta.advertiser_id,
						agencies_cta.user_id AS advertiser_agency_id
					FROM
						clients_to_advertisers AS agencies_cta
						JOIN users
							ON (agencies_cta.user_id = users.id AND
								agencies_cta.is_power_user = 1 AND
								users.role = 'AGENCY'
							)
						GROUP BY
							advertiser_id
				) AS advertiser_agency_ids
					ON (adv_match.advertiser_id = advertiser_agency_ids.advertiser_id)
				$new_related_data_from_sql
				$current_related_data_from_sql
			WHERE
				ignored_accounts.spectrum_account_ul_id IS NULL AND
				(
					$new_accounts_spectrum_table_alias.newest_order_date >= '$newest_order_cutoff_date' OR
					$new_accounts_spectrum_table_alias.client_create_date >= '$newest_order_cutoff_date'
				) AND 		/* 					1.1		*/
				(
					COALESCE($new_accounts_spectrum_table_alias.advertiser_eclipse_id, -1) != COALESCE($current_accounts_spectrum_table_alias.advertiser_eclipse_id, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.advertiser_name, -1) != COALESCE($current_accounts_spectrum_table_alias.advertiser_name, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.primary_commodity_code, -1) != COALESCE($current_accounts_spectrum_table_alias.primary_commodity_code, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.primary_commodity, -1) != COALESCE($current_accounts_spectrum_table_alias.primary_commodity, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.secondary_commodity_code, -1) != COALESCE($current_accounts_spectrum_table_alias.secondary_commodity_code, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.secondary_commodity, -1) != COALESCE($current_accounts_spectrum_table_alias.secondary_commodity, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.client_email, -1) != COALESCE($current_accounts_spectrum_table_alias.client_email, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.agency_account_ul_id, -1) != COALESCE($current_accounts_spectrum_table_alias.agency_account_ul_id, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.agency_email, -1) != COALESCE($current_accounts_spectrum_table_alias.agency_email, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.account_executive_ul_id, -1) != COALESCE($current_accounts_spectrum_table_alias.account_executive_ul_id, -1) OR
					COALESCE($new_accounts_spectrum_table_alias.sales_office_ul_id, -1) != COALESCE($current_accounts_spectrum_table_alias.sales_office_ul_id, -1)
				)
			GROUP BY
				new_advertiser_ul_id
			ORDER BY new_csv_line_number ASC
		";

		$get_new_rows_sql = "
			SELECT
				$new_spectrum_accounts_fields_select_sql
				adv_ul_id.advertiser_id AS new_match_advertiser_ul_id_advertiser_id, /* Existing Advertiser ul id matches new row 		1.2, 1.3	*/
				adv_ul_id.eclipse_id AS new_match_advertiser_ul_id_eclipse_id,
				adv_ul_id.primary_commodity_code AS new_match_advertiser_ul_id_primary_commodity_code,
				adv_ul_id.primary_commodity AS new_match_advertiser_ul_id_primary_commodity,
				adv_ul_id.secondary_commodity_code AS new_match_advertiser_ul_id_secondary_commodity_code,
				adv_ul_id.secondary_commodity AS new_match_advertiser_ul_id_secondary_commodity,
				adv_ul_id_name.Name AS new_match_advertiser_ul_id_advertiser_name, /* Existing Advertiser ul id matches new row 		1.2, 1.3	*/
				$new_related_data_select_sql,
				$current_spectrum_accounts_fields_select_sql
				$current_related_data_select_sql
			FROM
				$new_accounts_table AS $new_accounts_spectrum_table_alias
				LEFT JOIN $current_accounts_table AS $current_accounts_spectrum_table_alias
					ON ($new_accounts_spectrum_table_alias.advertiser_ul_id = $current_accounts_spectrum_table_alias.advertiser_ul_id AND
						(
							$current_accounts_spectrum_table_alias.newest_order_date >= '$newest_order_cutoff_date' OR
							$current_accounts_spectrum_table_alias.client_create_date >= '$newest_order_cutoff_date'
						)
					)
				LEFT JOIN tp_spectrum_accounts_ignored_items AS ignored_accounts
					ON($new_accounts_spectrum_table_alias.advertiser_ul_id = ignored_accounts.spectrum_account_ul_id)
				LEFT JOIN tp_spectrum_accounts AS adv_ul_id
					ON ($new_accounts_spectrum_table_alias.advertiser_ul_id = adv_ul_id.ul_id)	/*								1.2, 1.3	*/
				LEFT JOIN Advertisers AS adv_ul_id_name
					ON (adv_ul_id.advertiser_id = adv_ul_id_name.id)			/*									1.2, 1.3	*/
				$new_related_data_from_sql
				$current_related_data_from_sql
			WHERE
				ignored_accounts.spectrum_account_ul_id IS NULL AND
				(
					$new_accounts_spectrum_table_alias.newest_order_date >= '$newest_order_cutoff_date' OR
					$new_accounts_spectrum_table_alias.client_create_date >= '$newest_order_cutoff_date'
				) AND 		/* 					1.1		*/
				(
					$current_accounts_spectrum_table_alias.advertiser_ul_id IS NULL OR /* new row */
					(
						$current_accounts_spectrum_table_alias.advertiser_ul_id IS NOT NULL AND /* row was present */
						adv_ul_id.ul_id IS NULL AND /* but previously unused row */
						( /* and now modified so try to use it */
							COALESCE($new_accounts_spectrum_table_alias.advertiser_eclipse_id, -1) != COALESCE($current_accounts_spectrum_table_alias.advertiser_eclipse_id, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.advertiser_name, -1) != COALESCE($current_accounts_spectrum_table_alias.advertiser_name, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.primary_commodity_code, -1) != COALESCE($current_accounts_spectrum_table_alias.primary_commodity_code, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.primary_commodity, -1) != COALESCE($current_accounts_spectrum_table_alias.primary_commodity, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.secondary_commodity_code, -1) != COALESCE($current_accounts_spectrum_table_alias.secondary_commodity_code, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.secondary_commodity, -1) != COALESCE($current_accounts_spectrum_table_alias.secondary_commodity, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.client_email, -1) != COALESCE($current_accounts_spectrum_table_alias.client_email, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.agency_account_ul_id, -1) != COALESCE($current_accounts_spectrum_table_alias.agency_account_ul_id, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.agency_email, -1) != COALESCE($current_accounts_spectrum_table_alias.agency_email, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.account_executive_ul_id, -1) != COALESCE($current_accounts_spectrum_table_alias.account_executive_ul_id, -1) OR
							COALESCE($new_accounts_spectrum_table_alias.sales_office_ul_id, -1) != COALESCE($current_accounts_spectrum_table_alias.sales_office_ul_id, -1)
						)
					)
				)
			GROUP BY
				new_advertiser_ul_id
			ORDER BY new_csv_line_number ASC
		";

		$this->print_progress_message("Executing query: get_new_rows_sql");
		$new_rows_result = $this->db->query($get_new_rows_sql);
		$new_rows = $new_rows_result->result();

		//echo "new_rows:\n";
		//print_r($new_rows);
		//echo "\n";

		$this->print_progress_message("Executing query: get_modified_rows_sql");
		$modified_rows_result = $this->db->query($get_modified_rows_sql);
		$modified_rows = $modified_rows_result->result();

		//echo "modified_rows:\n";
		//print_r($modified_rows);
		//echo "\n";

		$create_advertisers_values_sql = '';
		$create_advertisers_values_bindings = array();
		$create_tp_spectrum_accounts_values_sql = ''; //$update_advertisers_spectrum_ids_values_sql = '';
		$create_tp_spectrum_accounts_values_bindings = array();
		$update_advertisers_name_values_sql = '';
		$update_advertisers_name_values_bindings = array();
		$update_advertisers_sales_person_values_sql = '';
		$update_advertisers_sales_person_values_bindings = array();
		$update_tp_spectrum_accounts_values_sql = '';
		$update_tp_spectrum_accounts_values_bindings = array();
		$clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings = array();

		$create_account_executive_values_sql = '';
		$create_account_executive_values_bindings = array();
		$create_client_values_sql = '';
		$create_client_values_bindings = array();
		$create_agency_values_sql = '';
		$create_agency_values_bindings = array();
		$create_client_to_advetiser_relationship_values_sql = '';
		$create_client_to_advetiser_relationship_values_bindings = array();

		$remove_agency_users_from_advertiser_values_bindings = array();

		$actions = new StdClass;
		$actions->update_advertiser_with_new_account_executive_by_advertuser_ul_id = array();
		$actions->update_advertiser_with_new_account_executive_by_advertiser_id = array();
		$actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_id = array();
		$actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_ul_id = array();
		$actions->new_client_to_advertiser_relationship_by_client_user_id = array();
		$actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_id = array();
		$actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_ul_id = array();
		$actions->new_client_to_advertiser_relationship_by_agency_user_id = array();
		$actions->new_spectrum_account_for_advertiser = array();

		$relationships = new StdClass;
		$relationships->new_client_emails = array();
		$relationships->new_agency_emails = array();
		$relationships->new_account_executive_ul_ids = array();
		$relationships->existing_account_executive_user_ids = array();
		$relationships->new_advertiser_ul_ids = array();
		$relationships->map_advertiser_ul_id_to_advertiser_id = array();
		$relationships->map_new_account_executive_ul_id_to_user_id = array();
		$relationships->map_account_executive_to_advertiser_id = array();
		$relationships->dedupe_new_account_executive_users = array();
		$relationships->dedupe_new_users_by_email = array();

		$notifications->errors = array();
		$notifications->info = array();
		$notifications->error_row_data = array();

		$notifications->internal_errors = array();
		$notifications->new_user_email = array();

		$notifications->new_rows = new StdClass;
		$notifications->new_rows->errors = array();
		$notifications->new_rows->info = array();
		$notifications->new_rows->error_row_data = array();

		$notifications->modified_rows = new StdClass;
		$notifications->modified_rows->errors = array();
		$notifications->modified_rows->info = array();
		$notifications->modified_rows->error_row_data = array();

		$notifications->client_users_with_miss_matched_role = array();
		$notifications->client_users_with_miss_matched_partner_id = array();
		$notifications->agency_users_with_miss_matched_role = array();
		$notifications->agency_users_with_miss_matched_partner_id = array();
		$notifications->account_executives_mismatch_sales_offices = array();
		$notifications->unknown_account_executive_rows = array();
		$notifications->unknown_sales_office_rows = array();
		$notifications->unknown_traffic_system_rows = array();

		$stats->account_executives_to_advertisers = array();

		$stats->num_new_rows = count($new_rows);
		$stats->num_new_rows_with_unknown_sales_office = 0;
		$stats->num_rows_with_unknown_traffic_system = 0;
		$stats->unknown_sales_offices = array();
		$stats->unknown_traffic_systems = array();

		$stats->num_unknown_account_executives = 0;
		$stats->num_place_holder_sales_office_account_executives = 0;
		$stats->num_rows_with_unknown_account_executives = 0;
		$stats->num_sales_person_updates_for_advertisers = 0;
		$stats->unknown_account_executives = array();

		$stats->num_new_account_executives_where_sales_office_partner_not_matching_ae_partner = 0;

		$stats->num_new_rows_with_new_client_users = 0;
		$stats->num_new_rows_creating_new_client_users = 0;
		$stats->num_new_rows_with_new_client_user_with_agency_role_already_chosen = 0;
		$stats->num_new_rows_with_new_client_user_referenced_more_than_once = 0;
		$stats->num_new_rows_without_created_client_users = 0;
		$stats->num_new_rows_with_matched_client_users = 0;
		$stats->num_new_rows_with_matched_client_users_but_wrong_role = 0;
		$stats->num_new_rows_with_matched_client_users_but_wrong_partner_id = 0;

		$stats->num_new_rows_with_new_agency_users = 0;
		$stats->num_new_rows_creating_new_agency_users = 0;
		$stats->num_new_rows_with_new_agency_user_referenced_more_than_once = 0;
		$stats->num_new_rows_with_new_agency_user_with_client_role_already_chosen = 0;
		$stats->num_new_rows_without_created_agency_users = 0;
		$stats->num_new_rows_with_matched_agency_users = 0;
		$stats->num_new_rows_with_matched_agency_users_but_wrong_role = 0;
		$stats->num_new_rows_with_matched_agency_users_but_wrong_partner_id = 0;

		$stats->num_created_advertisers = 0;
		$stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id = 0;
		$stats->num_new_advertiser_rows_matching_existing_advertiser_requiring_no_name_change = 0;
		$stats->num_new_advertiser_rows_updating_spectrum_account = 0;
		$stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id = 0;

		$this->print_progress_message("Analyzing new rows");

		foreach($new_rows as $new_row_index => $new_row)
		{
			if($new_row->new_traffic_system == spectrum_traffic_system_ids::unknown)
			{
				$stats->num_rows_with_unknown_traffic_system++;
				$notifications->unknown_traffic_system_rows[] = $new_row;
				$stats->unknown_traffic_systems[$new_row->new_traffic_system_raw] = true;
				$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: Traffic System '{$new_row->new_traffic_system_raw}', is not recognized. (#22087)");

				continue;
			}

			// Handle Sales Office
			$sales_office_partner_id = $new_row->new_match_sales_office_partner_id;
			if(empty($new_row->new_match_sales_office_partner_id))
			{
				$stats->num_new_rows_with_unknown_sales_office++;
				$notifications->unknown_sales_office_rows[] = $new_row;
				$stats->unknown_sales_offices[$new_row->new_sales_office_ul_id] = $new_row->new_sales_office_name;
				$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: Sales Office '{$new_row->new_sales_office_name}', id '{$new_row->new_sales_office_ul_id}' is not recognized. (#25371)");

				continue;
			}

			// Handle Accounte Executive (AE)
			$sales_person_user_id = null;
			if(!empty($new_row->new_match_ae_user_id))
			{
				// If new or pre-existing account executive is not under this sales office, give warning.
				if(!empty($new_row->new_match_sales_office_partner_id) &&
					$new_row->new_match_ae_user_partner_id !== $new_row->new_match_sales_office_partner_id
				)
				{
					$stats->num_new_account_executives_where_sales_office_partner_not_matching_ae_partner++;
					$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: Sales Office '{$new_row->new_sales_office_name}', id '{$new_row->new_sales_office_ul_id}' is under partner '{$new_row->new_match_sales_office_partner_id}' different than corresponding account executive '{$new_row->new_match_ae_user_email}' partner '{$new_row->new_match_ae_user_partner_id}'. (#14911)");
					$notifications->account_executives_mismatch_sales_offices[] = $new_row;

					if(!empty($new_row->new_match_sales_office_place_holder_account_executive_user_id))
					{
						$sales_person_user_id = $new_row->new_match_sales_office_place_holder_account_executive_user_id;
					}
					else
					{
						$place_holder_account_executive_ul_id = $place_holder_user_ul_id_prefix.$new_row->new_sales_office_ul_id;
						$place_holder_account_executive_row = new StdClass;
						$place_holder_account_executive_row->new_account_executive_ul_id = $place_holder_account_executive_ul_id;
						$place_holder_account_executive_row->new_account_executive_name = 'Sales Office - '.$new_row->new_sales_office_name;
						$place_holder_account_executive_row->new_match_sales_office_partner_id = $new_row->new_match_sales_office_partner_id;

						$num_added_account_executives = $this->add_account_executive(
							$relationships,
							$actions,
							$stats,
							$create_account_executive_values_sql,
							$create_account_executive_values_bindings,
							$place_holder_account_executive_row
						);

						$stats->num_place_holder_sales_office_account_executives += $num_added_account_executives;
						$actions->update_advertiser_with_new_account_executive_by_advertuser_ul_id[$new_row->new_advertiser_ul_id] = $place_holder_account_executive_ul_id;
					}
				}
				else
				{
					$sales_person_user_id = $new_row->new_match_ae_user_id;
				}
			}
			else
			{
				$stats->num_rows_with_unknown_account_executives++;
				$notifications->unknown_account_executive_rows[] = $new_row;

				$num_added_account_executives = $this->add_account_executive(
					$relationships,
					$actions,
					$stats,
					$create_account_executive_values_sql,
					$create_account_executive_values_bindings,
					$new_row
				);

				$stats->num_unknown_account_executives += $num_added_account_executives;

				$actions->update_advertiser_with_new_account_executive_by_advertuser_ul_id[$new_row->new_advertiser_ul_id] = $new_row->new_account_executive_ul_id;

				$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Warning: Unknown Account Executive '{$new_row->new_account_executive_name}', id '{$new_row->new_account_executive_ul_id}'. (#19568)");
			}

			// Handle Client
			if(empty($new_row->new_match_client_user_id))
			{
				if(empty($new_row->new_client_email))
				{
					$stats->num_new_rows_without_created_client_users++;
				}
				else
				{
					$stats->num_new_rows_with_new_client_users++;
					$lower_new_client_email = strtolower($new_row->new_client_email);

					if(!array_key_exists($lower_new_client_email, $relationships->dedupe_new_users_by_email))
					{
						$relationships->dedupe_new_users_by_email[$lower_new_client_email] = 'CLIENT';
						$notifications->new_user_email[$lower_new_client_email] = array(
							'first_name' => $new_row->new_client_first_name,
							'last_name' => $new_row->new_client_last_name,
							'email' => $new_row->new_client_email,
							'partner_name' => $new_row->new_match_sales_office_partner_name,
							'partner_cname' => $new_row->new_match_sales_office_partner_cname,
							'advertiser_rows' => array()
						);

						$stats->num_new_rows_creating_new_client_users++;

						$role = 'CLIENT';

						// Create Client User (and eventually send email)
						$this->add_values_and_bindings(
							$create_client_values_sql,
							$create_client_values_bindings,
							array(
								$new_row->new_client_email,
								$this->user_options->account_import_client_or_agency_banned,
								$role,
								$this->user_options->placements_viewable,
								$this->user_options->client_agency_screenshots_viewable,
								$this->user_options->beta_report_engagements_viewable,
								$this->user_options->ad_sizes_viewable,
								$sales_office_partner_id,
								$new_row->new_client_email,
								empty($new_row->new_client_first_name) ? null : $new_row->new_client_first_name,
								empty($new_row->new_client_last_name) ? null : $new_row->new_client_last_name,
								$this->create_user_password_reset_key(),
								date('Y-m-d H:i:s')
							)
						);

						$relationships->new_client_emails[] = $lower_new_client_email;

						$notifications->new_user_email[$lower_new_client_email]['advertiser_rows'][] = $new_row;
						$actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_ul_id[$new_row->new_advertiser_ul_id] = $new_row->new_client_email;
					}
					elseif($relationships->dedupe_new_users_by_email[$lower_new_client_email] !== 'CLIENT')
					{
						$stats->num_new_rows_with_new_client_user_with_agency_role_already_chosen++;

						$notifications->client_users_with_miss_matched_role[] = $new_row;
						$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: Trying to add new Client user, but actual user role is already AGENCY. (#17040)");
					}
					else
					{
						$stats->num_new_rows_with_new_client_user_referenced_more_than_once++;

						$notifications->new_user_email[$lower_new_client_email]['advertiser_rows'][] = $new_row;
						$actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_ul_id[$new_row->new_advertiser_ul_id] = $new_row->new_client_email;
					}
				}
			}
			else
			{
				if($new_row->new_match_client_user_role === 'CLIENT')
				{
					if($new_row->new_match_client_user_partner_id === $new_row->new_match_sales_office_partner_id)
					{
						$stats->num_new_rows_with_matched_client_users++;

						$actions->new_client_to_advertiser_relationship_by_client_user_id[$new_row->new_advertiser_ul_id] = $new_row->new_match_client_user_id;
					}
					else
					{
						$stats->num_new_rows_with_matched_client_users_but_wrong_partner_id++;

						$notifications->client_users_with_miss_matched_partner_id[] = $new_row;
						$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: User with email {$new_row->new_client_email} has partner_id '{$new_row->new_match_client_user_partner_id}' but expected partner_id '{$new_row->new_match_sales_office_partner_id}' from Sales Office (#21122)");
					}
				}
				else
				{
					$stats->num_new_rows_with_matched_client_users_but_wrong_role++;

					$notifications->client_users_with_miss_matched_role[] = $new_row;
					$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: User with email {$new_row->new_client_email} has role '{$new_row->new_match_client_user_role}' but expected 'CLIENT' role. (#19826)");
				}
			}

			// Handle Agency
			if(empty($new_row->new_match_agency_email_user_id))
			{
				if(empty($new_row->new_agency_email))
				{
					$stats->num_new_rows_without_created_agency_users++;
				}
				else
				{
					$stats->num_new_rows_with_new_agency_users++;

					$lower_new_agency_email = strtolower($new_row->new_agency_email);

					if(!array_key_exists($lower_new_agency_email, $relationships->dedupe_new_users_by_email))
					{
						$relationships->dedupe_new_users_by_email[$lower_new_agency_email] = 'AGENCY';
						$notifications->new_user_email[$lower_new_agency_email] = array(
							'first_name' => $new_row->new_agency_name,
							'last_name' => "",
							'email' => $new_row->new_agency_email,
							'partner_name' => $new_row->new_match_sales_office_partner_name,
							'partner_cname' => $new_row->new_match_sales_office_partner_cname,
							'advertiser_rows' => array()
						);

						$stats->num_new_rows_creating_new_agency_users++;

						$role = 'AGENCY';
						$first_name = $new_row->new_agency_name;
						$last_name = null;

						// Create Agency User (and eventually send email)
						$this->add_values_and_bindings(
							$create_agency_values_sql,
							$create_agency_values_bindings,
							array(
								$new_row->new_agency_email,
								$this->user_options->account_import_client_or_agency_banned,
								$role,
								$this->user_options->placements_viewable,
								$this->user_options->client_agency_screenshots_viewable,
								$this->user_options->beta_report_engagements_viewable,
								$this->user_options->ad_sizes_viewable,
								$sales_office_partner_id,
								$new_row->new_agency_email,
								$first_name,
								$last_name,
								$this->create_user_password_reset_key(),
								date('Y-m-d H:i:s')
							)
						);

						$relationships->new_agency_emails[] = $lower_new_agency_email;

						$notifications->new_user_email[$lower_new_agency_email]['advertiser_rows'][] = $new_row;
						$actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_ul_id[$new_row->new_advertiser_ul_id] = $new_row->new_agency_email;
					}
					elseif($relationships->dedupe_new_users_by_email[$lower_new_agency_email] !== 'AGENCY')
					{
						$stats->num_new_rows_with_new_agency_user_with_client_role_already_chosen++;

						$notifications->agency_users_with_miss_matched_role[] = $new_row;
						$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: Trying to add new Agency user, but actual user role is already CLIENT. (#21198)");
					}
					else
					{
						$stats->num_new_rows_with_new_agency_user_referenced_more_than_once++;

						$notifications->new_user_email[$lower_new_agency_email]['advertiser_rows'][] = $new_row;
						$actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_ul_id[$new_row->new_advertiser_ul_id] = $new_row->new_agency_email;
					}
				}
			}
			else
			{
				if($new_row->new_match_agency_email_user_role === 'AGENCY')
				{
					if($new_row->new_match_agency_email_user_partner_id === $new_row->new_match_sales_office_partner_id)
					{
						$stats->num_new_rows_with_matched_agency_users++;

						$actions->new_client_to_advertiser_relationship_by_agency_user_id[$new_row->new_advertiser_ul_id] = $new_row->new_match_agency_email_user_id;
					}
					else
					{
						$stats->num_new_rows_with_matched_agency_users_but_wrong_partner_id++;

						$notifications->agency_users_with_miss_matched_partner_id[] = $new_row;
						$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: User with email {$new_row->new_agency_email} has partner_id '{$new_row->new_match_agency_email_user_partner_id}' but expected partner_id '{$new_row->new_match_sales_office_partner_id}' from Sales Office (#29004)");
					}
				}
				else
				{
					$stats->num_new_rows_with_matched_agency_users_but_wrong_role++;

					$notifications->agency_users_with_miss_matched_role[] = $new_row;
					$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Error: User with email {$new_row->new_agency_email} has role '{$new_row->new_match_agency_email_user_role}' but expected 'AGENCY' role. (#15112)");
				}
			}

			// Handle Advertiser
			if(empty($new_row->new_match_advertiser_ul_id_advertiser_id))
			{
				$stats->num_created_advertisers++;

				$this->add_values_and_bindings(
					$create_advertisers_values_sql,
					$create_advertisers_values_bindings,
					array(
						$new_row->new_advertiser_name,
						$sales_person_user_id
					)
				);

				$actions->new_spectrum_account_for_advertiser[$new_row->new_advertiser_ul_id] = array(
					$new_row->new_advertiser_ul_id,
					$new_row->new_advertiser_eclipse_id,
					$new_row->new_primary_commodity_code,
					$new_row->new_primary_commodity,
					$new_row->new_secondary_commodity_code,
					$new_row->new_secondary_commodity
				);

				$relationships->new_advertiser_ul_ids[] = $new_row->new_advertiser_ul_id;
			}
			elseif(!empty($new_row->new_match_advertiser_ul_id_advertiser_id))
			{
				$relationships->map_advertiser_ul_id_to_advertiser_id[$new_row->new_advertiser_ul_id] = $new_row->new_match_advertiser_ul_id_advertiser_id;

				if($new_row->new_advertiser_name !== $new_row->new_match_advertiser_ul_id_advertiser_name
				)
				{
					$stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id++;

					$this->add_values_and_bindings(
						$update_advertisers_name_values_sql,
						$update_advertisers_name_values_bindings,
						array(
							$new_row->new_match_advertiser_ul_id_advertiser_id,
							$new_row->new_advertiser_name
						)
					);
				}
				else
				{
					$stats->num_new_advertiser_rows_matching_existing_advertiser_requiring_no_name_change++;
				}

				if($new_row->new_advertiser_eclipse_id !== $new_row->new_match_advertiser_ul_id_eclipse_id ||
					$new_row->new_primary_commodity_code !== $new_row->new_match_advertiser_ul_id_primary_commodity_code ||
					$new_row->new_primary_commodity !== $new_row->new_match_advertiser_ul_id_primary_commodity ||
					$new_row->new_secondary_commodity_code !== $new_row->new_match_advertiser_ul_id_secondary_commodity_code ||
					$new_row->new_secondary_commodity !== $new_row->new_match_advertiser_ul_id_secondary_commodity
				)
				{
					$stats->num_new_advertiser_rows_updating_spectrum_account++;

					$this->add_values_and_bindings(
						$update_tp_spectrum_accounts_values_sql,
						$update_tp_spectrum_accounts_values_bindings,
						array(
							$new_row->new_advertiser_ul_id,
							$new_row->new_advertiser_eclipse_id,
							$new_row->new_primary_commodity_code,
							$new_row->new_primary_commodity,
							$new_row->new_secondary_commodity_code,
							$new_row->new_secondary_commodity
						)
					);

					if($new_row->new_advertiser_eclipse_id !== $new_row->new_match_advertiser_ul_id_eclipse_id)
					{
						$stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id++;
						$clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings[] = $new_row->new_advertiser_ul_id;
					}
				}

				// TODO: handle AE to sales office fix / change
			}
			else
			{
				$this->note_row_error($notifications->new_rows, $new_row_index, $new_row, "Unhandled condition when creating Advertiser.");
				throw new Exception("Unhandled condition when creating Advertiser.");
			}
		}

		$stats->num_modified_rows = count($modified_rows);
		$stats->num_modified_advertiser_eclipse_ids = 0;
		$stats->num_modified_spectrum_accounts = 0;
		$stats->num_modified_advertiser_names = 0;
		$stats->num_modified_client_emails = 0;
		$stats->num_modified_agency_account_ul_ids = 0;
		$stats->num_modified_agency_emails = 0;
		$stats->num_modified_account_executive_ul_ids = 0;
		$stats->num_modified_account_executives_where_sales_office_partner_not_matching_ae_partner = 0;
		$stats->num_modified_sales_office_ul_ids = 0;
		$stats->num_modified_sales_office_ul_ids_with_sales_office_partner_not_matching_ae_partner = 0;
		$stats->num_modified_sales_office_ul_ids_with_unknown_sales_offices = 0;

		$stats->num_modified_rows_creating_new_client_user = 0;
		$stats->num_modified_rows_with_new_client_user_referenced_more_than_once = 0;
		$stats->num_modified_rows_with_new_client_user_with_agency_role_already_chosen = 0;

		$stats->num_modified_rows_creating_new_agency_user = 0;
		$stats->num_modified_rows_with_new_agency_user_referenced_more_than_once = 0;
		$stats->num_modified_rows_with_new_agency_user_with_client_role_already_chosen = 0;

		$stats->num_modified_rows_with_matched_agency_users = 0;
		$stats->num_modified_rows_with_matched_client_users_but_wrong_role = 0;
		$stats->num_modified_rows_with_matched_client_users_but_wrong_partner_id = 0;
		$stats->num_modified_rows_with_matched_client_users = 0;
		$stats->num_modified_rows_with_matched_agency_users_but_wrong_partner_id = 0;

		$is_power_user = 1;

		$this->print_progress_message("Analyzing modified rows");

		foreach($modified_rows as $modified_row_index => $modified_row)
		{
			$row_notes = array();

			if($modified_row->new_traffic_system == spectrum_traffic_system_ids::unknown)
			{
				$stats->num_rows_with_unknown_traffic_system++;
				$notifications->unknown_traffic_system_rows[] = $modified_row;
				$stats->unknown_traffic_systems[$modified_row->new_traffic_system_raw] = true;
				$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: Traffic System '{$modified_row->new_traffic_system_raw}', is not recognized. (#22087)");

				continue;
			}


			if($modified_row->new_sales_office_ul_id !== $modified_row->current_sales_office_ul_id)
			{
				$stats->num_modified_sales_office_ul_ids++;

				if(empty($modified_row->new_match_sales_office_partner_id))
				{
					$stats->num_modified_sales_office_ul_ids_with_unknown_sales_offices++;
					$notifications->unknown_sales_office_rows[] = $modified_row;
					$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: Sales Office '{$modified_row->new_sales_office_name}', id '{$modified_row->new_sales_office_ul_id}' doesn't match a partner. (#31593)");
					$stats->unknown_sales_offices[$modified_row->new_sales_office_ul_id] = $modified_row->new_sales_office_name;

					continue;
				}
				elseif(!empty($modified_row->new_match_ae_user_partner_id) &&
					$modified_row->new_match_ae_user_partner_id !== $modified_row->new_match_sales_office_partner_id)
				{
					// New or pre-existing account executive is not under this sales office, give warning.  Handles #ae_sales_office_partner_difference
					$stats->num_modified_sales_office_ul_ids_with_sales_office_partner_not_matching_ae_partner++;
					$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: Sales Office '{$modified_row->new_sales_office_name}', id '{$modified_row->new_sales_office_ul_id}' is under partner '{$modified_row->new_match_sales_office_partner_id}' different than corresponding account executive '{$modified_row->new_match_ae_user_email}' partner '{$modified_row->new_match_ae_user_partner_id}'. (#13939)");
					$notifications->account_executives_mismatch_sales_offices[] = $modified_row;

					if(!empty($modified_row->new_match_sales_office_place_holder_account_executive_user_id))
					{
						$stats->num_sales_person_updates_for_advertisers++;

						$this->add_values_and_bindings(
							$update_advertisers_sales_person_values_sql,
							$update_advertisers_sales_person_values_bindings,
							array(
								$modified_row->match_advertiser_id,
								$modified_row->new_match_sales_office_place_holder_account_executive_user_id
							)
						);
					}
					else
					{
						$place_holder_account_executive_ul_id = $place_holder_user_ul_id_prefix.$modified_row->new_sales_office_ul_id;
						$place_holder_account_executive_row = new StdClass;
						$place_holder_account_executive_row->new_account_executive_ul_id = $place_holder_account_executive_ul_id;
						$place_holder_account_executive_row->new_account_executive_name = 'Sales Office - '.$modified_row->new_sales_office_name;
						$place_holder_account_executive_row->new_match_sales_office_partner_id = $modified_row->new_match_sales_office_partner_id;

						$num_added_account_executives = $this->add_account_executive(
							$relationships,
							$actions,
							$stats,
							$create_account_executive_values_sql,
							$create_account_executive_values_bindings,
							$place_holder_account_executive_row
						);

						$stats->num_place_holder_sales_office_account_executives += $num_added_account_executives;
						$actions->update_advertiser_with_new_account_executive_by_advertiser_id[$modified_row->match_advertiser_id] = $place_holder_account_executive_ul_id;
					}
				}
				else
				{
					// Hooking up Advertiser to new partner happens in AE resolution
				}
			}

			if($modified_row->new_advertiser_name !== $modified_row->current_advertiser_name)
			{
				$stats->num_modified_advertiser_names++;

				$this->add_values_and_bindings(
					$update_advertisers_name_values_sql,
					$update_advertisers_name_values_bindings,
					array(
						$modified_row->match_advertiser_id,
						$modified_row->new_advertiser_name
					)
				);
			}

			if($modified_row->new_advertiser_eclipse_id !== $modified_row->current_advertiser_eclipse_id ||
				$modified_row->new_primary_commodity_code !== $modified_row->current_primary_commodity_code ||
				$modified_row->new_primary_commodity !== $modified_row->current_primary_commodity ||
				$modified_row->new_secondary_commodity_code !== $modified_row->current_secondary_commodity_code ||
				$modified_row->new_secondary_commodity !== $modified_row->current_secondary_commodity
			)
			{
				$stats->num_modified_spectrum_accounts++;

				$this->add_values_and_bindings(
					$update_tp_spectrum_accounts_values_sql,
					$update_tp_spectrum_accounts_values_bindings,
					array(
						$modified_row->new_advertiser_ul_id,
						$modified_row->new_advertiser_eclipse_id,
						$modified_row->new_primary_commodity_code,
						$modified_row->new_primary_commodity,
						$modified_row->new_secondary_commodity_code,
						$modified_row->new_secondary_commodity
					)
				);

				if($modified_row->new_advertiser_eclipse_id !== $modified_row->current_advertiser_eclipse_id)
				{
					$stats->num_modified_advertiser_eclipse_ids++;
					$clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings[] = $modified_row->new_advertiser_ul_id;
				}
			}

			$lower_new_client_email = strtolower($modified_row->new_client_email);
			if($lower_new_client_email !== strtolower($modified_row->current_client_email))
			{
				$stats->num_modified_client_emails++;

				if(!empty($modified_row->new_client_email) && empty($modified_row->client_user_matching_advertiser))
				{
					if(empty($modified_row->new_match_client_user_id))
					{
						if(!array_key_exists($lower_new_client_email, $relationships->dedupe_new_users_by_email))
						{
							$relationships->dedupe_new_users_by_email[$lower_new_client_email] = 'CLIENT';
							$notifications->new_user_email[$lower_new_client_email] = array(
								'first_name' => $modified_row->new_client_first_name,
								'last_name' => $modified_row->new_client_last_name,
								'email' => $modified_row->new_client_email,
								'partner_name' => $modified_row->new_match_sales_office_partner_name,
								'partner_cname' => $modified_row->new_match_sales_office_partner_cname,
								'advertiser_rows' => array()
							);

							$stats->num_modified_rows_creating_new_client_user++;

							$role = 'CLIENT';

							$this->add_values_and_bindings(
								$create_client_values_sql,
								$create_client_values_bindings,
								array(
									$modified_row->new_client_email,
									$this->user_options->account_import_client_or_agency_banned,
									$role,
									$this->user_options->placements_viewable,
									$this->user_options->client_agency_screenshots_viewable,
									$this->user_options->beta_report_engagements_viewable,
									$this->user_options->ad_sizes_viewable,
									$modified_row->new_match_sales_office_partner_id,
									$modified_row->new_client_email,
									empty($modified_row->new_client_first_name) ? null : $modified_row->new_client_first_name,
									empty($modified_row->new_client_last_name) ? null : $modified_row->new_client_last_name,
									$this->create_user_password_reset_key(),
									date('Y-m-d H:i:s')
								)
							);

							$relationships->new_client_emails[] = $lower_new_client_email;

							$notifications->new_user_email[$lower_new_client_email]['advertiser_rows'][] = $modified_row;
							$actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_id[$modified_row->match_advertiser_id] = $modified_row->new_client_email;
						}
						elseif($relationships->dedupe_new_users_by_email[$lower_new_client_email] !== 'CLIENT')
						{
							$stats->num_modified_rows_with_new_client_user_with_agency_role_already_chosen++;

							$notifications->client_users_with_miss_matched_role[] = $modified_row;
							$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: Trying to add new Client user ({$modified_row->new_client_email}), but user previously given role of AGENCY. (#5042)");
						}
						else
						{
							$stats->num_modified_rows_with_new_client_user_referenced_more_than_once++;

							$notifications->new_user_email[$lower_new_client_email]['advertiser_rows'][] = $modified_row;
							$actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_id[$modified_row->match_advertiser_id] = $modified_row->new_client_email;
						}
					}
					else
					{
						if($modified_row->new_match_client_user_role === 'CLIENT')
						{
							if($modified_row->new_match_client_user_partner_id === $modified_row->new_match_sales_office_partner_id)
							{
								$stats->num_modified_rows_with_matched_client_users++;

								$this->add_values_and_bindings(
									$create_client_to_advetiser_relationship_values_sql,
									$create_client_to_advetiser_relationship_values_bindings,
									array(
										$modified_row->new_match_client_user_id,
										$modified_row->match_advertiser_id,
										$is_power_user
									)
								);
							}
							else
							{
								$stats->num_modified_rows_with_matched_client_users_but_wrong_partner_id++;

								$notifications->client_users_with_miss_matched_partner_id[] = $modified_row;
								$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: User with email {$modified_row->new_client_email} has partner_id '{$modified_row->new_match_client_user_partner_id}' but expected partner_id '{$modified_row->new_match_sales_office_partner_id}' from Sales Office (#19412)");
							}
						}
						else
						{
							$stats->num_modified_rows_with_matched_client_users_but_wrong_role++;

							$notifications->client_users_with_miss_matched_role[] = $modified_row;
							$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: User with email {$modified_row->new_client_email} has role '{$modified_row->new_match_client_user_role}' but expected 'CLIENT' role. (#24227)");
						}
					}
				}
			}

			if($modified_row->new_agency_account_ul_id !== $modified_row->current_agency_account_ul_id)
			{
				$stats->num_modified_agency_account_ul_ids++;

				if(!empty($modified_row->current_agency_account_ul_id))
				{
					$remove_agency_users_from_advertiser_values_bindings[] = $modified_row->match_advertiser_id;
				}

				if(!empty($modified_row->new_agency_account_ul_id))
				{
					// Advertiser relationship and user creation is handled in Agency user section below.
				}
			}

			$lower_new_agency_email = strtolower($modified_row->new_agency_email);
			if($lower_new_agency_email !== strtolower($modified_row->current_agency_email) ||
				$modified_row->new_agency_account_ul_id !== $modified_row->current_agency_account_ul_id
			)
			{
				if($lower_new_agency_email !== strtolower($modified_row->current_agency_email))
				{
					$stats->num_modified_agency_emails++;
				}

				$is_new_agency_user_link_to_advertiser_needed = !empty($modified_row->new_agency_email) &&
					(empty($modified_row->agency_user_matching_advertiser) ||
						$modified_row->new_agency_account_ul_id !== $modified_row->current_agency_account_ul_id);

				if($is_new_agency_user_link_to_advertiser_needed)
				{
					if(empty($modified_row->new_match_agency_email_user_id))
					{
						if(!array_key_exists($lower_new_agency_email, $relationships->dedupe_new_users_by_email))
						{
							$relationships->dedupe_new_users_by_email[$lower_new_agency_email] = 'AGENCY';
							$notifications->new_user_email[$lower_new_agency_email] = array(
								'first_name' => $modified_row->new_agency_name,
								'last_name' => "",
								'email' => $modified_row->new_agency_email,
								'partner_name' => $modified_row->new_match_sales_office_partner_name,
								'partner_cname' => $modified_row->new_match_sales_office_partner_cname,
								'advertiser_rows' => array()
							);

							$stats->num_modified_rows_creating_new_agency_user++;

							$role = 'AGENCY';
							$first_name = $modified_row->new_agency_name;
							$last_name = null;

							$this->add_values_and_bindings(
								$create_agency_values_sql,
								$create_agency_values_bindings,
								array(
									$modified_row->new_agency_email,
									$this->user_options->account_import_client_or_agency_banned,
									$role,
									$this->user_options->placements_viewable,
									$this->user_options->client_agency_screenshots_viewable,
									$this->user_options->beta_report_engagements_viewable,
									$this->user_options->ad_sizes_viewable,
									$modified_row->new_match_sales_office_partner_id,
									$modified_row->new_agency_email,
									$first_name,
									$last_name,
									$this->create_user_password_reset_key(),
									date('Y-m-d H:i:s')
								)
							);

							$relationships->new_agency_emails[] = $lower_new_agency_email;

							$notifications->new_user_email[$lower_new_agency_email]['advertiser_rows'][] = $modified_row;
							$actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_id[$modified_row->match_advertiser_id] = $modified_row->new_agency_email;
						}
						elseif($relationships->dedupe_new_users_by_email[$lower_new_agency_email] !== 'AGENCY')
						{
							$stats->num_modified_rows_with_new_agency_user_with_client_role_already_chosen++;

							$notifications->agency_users_with_miss_matched_role[] = $modified_row;
							$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: Trying to add new Agency user ({$modified_row->new_agency_email}), but already given role of CLIENT. (#12758)");
						}
						else
						{
							$stats->num_modified_rows_with_new_agency_user_referenced_more_than_once++;

							$notifications->new_user_email[$lower_new_agency_email]['advertiser_rows'][] = $modified_row;
							$actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_id[$modified_row->match_advertiser_id] = $modified_row->new_agency_email;
						}
					}
					else
					{
						if($modified_row->new_match_agency_email_user_role === 'AGENCY')
						{
							if($modified_row->new_match_agency_email_user_partner_id === $modified_row->new_match_sales_office_partner_id)
							{
								$stats->num_modified_rows_with_matched_agency_users++;

								$this->add_values_and_bindings(
									$create_client_to_advetiser_relationship_values_sql,
									$create_client_to_advetiser_relationship_values_bindings,
									array(
										$modified_row->new_match_agency_email_user_id,
										$modified_row->match_advertiser_id,
										$is_power_user
									)
								);
							}
							else
							{
								$stats->num_modified_rows_with_matched_agency_users_but_wrong_partner_id++;

								$notifications->agency_users_with_miss_matched_partner_id[] = $modified_row;
								$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: User with email {$modified_row->new_agency_email} has partner_id '{$modified_row->new_match_agency_email_user_partner_id}' but expected partner_id '{$modified_row->new_match_sales_office_partner_id}' from Sales Office (#10284)");
							}
						}
						else
						{
							$stats->num_modified_rows_with_matched_client_users_but_wrong_role++;

							$notifications->agency_users_with_miss_matched_role[] = $modified_row;
							$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: User with email {$modified_row->new_agency_email} has role '{$modified_row->new_match_agency_email_user_role}' but expected 'AGENCY' role. (#9849)");
						}
					}
				}
			}

			if($modified_row->new_account_executive_ul_id !== $modified_row->current_account_executive_ul_id)
			{
				$stats->num_modified_account_executive_ul_ids++;

				if(!empty($modified_row->new_match_ae_user_id))
				{
					$sales_person_user_id = null;

					if(!empty($modified_row->new_match_sales_office_partner_id) &&
						$modified_row->new_match_ae_user_partner_id !== $modified_row->new_match_sales_office_partner_id
					)
					{
						if($modified_row->new_sales_office_ul_id === $modified_row->current_sales_office_ul_id)
						{
							$stats->num_modified_account_executives_where_sales_office_partner_not_matching_ae_partner++;
							$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Error: Sales Office '{$modified_row->new_sales_office_name}', id '{$modified_row->new_sales_office_ul_id}' is under partner '{$modified_row->new_match_sales_office_partner_id}' different than corresponding account executive '{$modified_row->new_match_ae_user_email}' partner '{$modified_row->new_match_ae_user_partner_id}'. (#11268)");
							$notifications->account_executives_mismatch_sales_offices[] = $modified_row;

							if(!empty($modified_row->new_match_sales_office_place_holder_account_executive_user_id))
							{
								$sales_person_user_id = $modified_row->new_match_sales_office_place_holder_account_executive_user_id;
							}
							else
							{
								$place_holder_account_executive_ul_id = $place_holder_user_ul_id_prefix.$modified_row->new_sales_office_ul_id;
								$place_holder_account_executive_row = new StdClass;
								$place_holder_account_executive_row->new_account_executive_ul_id = $place_holder_account_executive_ul_id;
								$place_holder_account_executive_row->new_account_executive_name = 'Sales Office - '.$modified_row->new_sales_office_name;
								$place_holder_account_executive_row->new_match_sales_office_partner_id = $modified_row->new_match_sales_office_partner_id;

								$num_added_account_executives = $this->add_account_executive(
									$relationships,
									$actions,
									$stats,
									$create_account_executive_values_sql,
									$create_account_executive_values_bindings,
									$place_holder_account_executive_row
								);

								$stats->num_place_holder_sales_office_account_executives += $num_added_account_executives;
								$actions->update_advertiser_with_new_account_executive_by_advertiser_id[$modified_row->match_advertiser_id] = $place_holder_account_executive_ul_id;
							}
						}
						else
						{
							// Already handled by sales_office change handler #ae_sales_office_partner_difference
						}
					}
					elseif(!empty($modified_row->new_match_sales_office_partner_id) &&
						$modified_row->new_match_ae_user_partner_id === $modified_row->new_match_sales_office_partner_id
					)
					{
						$sales_person_user_id = $modified_row->new_match_ae_user_id;
					}
					else
					{
						throw new Exception("Critical Failure: Unexpected account executive logic. (#24690)");
					}

					if(!empty($sales_person_user_id))
					{
						$stats->num_sales_person_updates_for_advertisers++;

						// Removes previous account executive and links to new account executive.
						$this->add_values_and_bindings(
							$update_advertisers_sales_person_values_sql,
							$update_advertisers_sales_person_values_bindings,
							array(
								$modified_row->match_advertiser_id,
								$sales_person_user_id
							)
						);
					}
				}
				else
				{
					$stats->num_rows_with_unknown_account_executives++;
					$notifications->unknown_account_executive_rows[] = $modified_row;

					$num_added_account_executives = $this->add_account_executive(
						$relationships,
						$actions,
						$stats,
						$create_account_executive_values_sql,
						$create_account_executive_values_bindings,
						$modified_row
					);

					$stats->num_unknown_account_executives += $num_added_account_executives;

					$actions->update_advertiser_with_new_account_executive_by_advertiser_id[$modified_row->match_advertiser_id] = $modified_row->new_account_executive_ul_id;

					$this->note_row_error($notifications->modified_rows, $modified_row_index, $modified_row, "Warning: Unknown Account Executive '{$modified_row->new_account_executive_name}', id '{$modified_row->new_account_executive_ul_id}'. (#31636)");
				}
			}

			// Fields which we don't care about deltas
			// 'sales_office_name'
			// 'client_first_name'
			// 'client_last_name'
			// 'agency_name'
			// 'account_executive_name'
		}

		//echo "Relationships before queries\n";
		//print_r($relationships);

		//echo "Notifications before queries\n";
		//print_r($notifications);

		//echo "\nActions:\n\n";
		//print_r($actions);

		//echo "\nStats:\n\n";
		//print_r($stats);

		//echo "\nstats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id: $stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id\n";
		//echo "stats->num_modified_advertiser_names: $stats->num_modified_advertiser_names;\n";
		//echo "$update_advertisers_name_values_sql\n";
		//print_r($update_advertisers_name_values_bindings);

		//echo "\nstats->num_modified_advertiser_eclipse_ids: $stats->num_modified_advertiser_eclipse_ids\n";
		//echo "stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id: $stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id\n";
		////echo "$clear_tp_spectrum_accounts_spectrum_eclipse_id_values_sql\n";
		//print_r($clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings);

		//echo "\nstats->num_new_advertiser_rows_updating_spectrum_account: $stats->num_new_advertiser_rows_updating_spectrum_account\n";
		//echo "stats->num_modified_spectrum_accounts: $stats->num_modified_spectrum_accounts\n";
		//echo "$update_tp_spectrum_accounts_values_sql\n";
		//print_r($update_tp_spectrum_accounts_values_bindings);

		//echo "\nupdate_advertisers_sales_person_values_sql\n";
		//echo "\n$update_advertisers_sales_person_values_sql\n";
		//print_r($update_advertisers_sales_person_values_bindings);

		$mysql_update_count_multiplier = 2;

		if(!empty($create_advertisers_values_sql))
		{
			$this->print_progress_message("Creating Advertisers");

			$create_advertisers_values_sql = rtrim($create_advertisers_values_sql, ',');
			$create_advertisers_sql = "															/*		1.3.1	*/
				INSERT IGNORE INTO Advertisers
				(Name, sales_person)
				VALUES
				$create_advertisers_values_sql
			";

			if($this->db->query($create_advertisers_sql, $create_advertisers_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				if($num_affected_rows !== $stats->num_created_advertisers)
				{
					$starting_insert_id = $this->db->insert_id();
					$shared_error_message = "Error: $num_affected_rows advertisers actually created, but expected to create {$stats->num_created_advertisers}. (#5635)";
					$this->note_other_error($notifications, $shared_error_message);

					$create_advertisers_values_bindings_string = print_r($create_advertisers_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						create_advertisers_sql: $create_advertisers_sql\n\n
						create_advertisers_values_bindings_string: $create_advertisers_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;

					return false;
				}
				else
				{
					$starting_insert_id = $this->db->insert_id();
					foreach($relationships->new_advertiser_ul_ids as $index => $advertiser_ul_id)
					{
						$relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id] = $starting_insert_id + $index;
					}
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Create advertisers query failed. (#12763)");
				return false;
			}
		}

		if(!empty($update_advertisers_name_values_sql))
		{
			$this->print_progress_message("Updating Advertiser names");
			$update_advertisers_name_values_sql = rtrim($update_advertisers_name_values_sql, ',');
			$update_advertisers_name_sql = "													/*		3.1		*/
				INSERT INTO Advertisers (id, Name)
				VALUES
				$update_advertisers_name_values_sql
				ON DUPLICATE KEY UPDATE Name = VALUES(Name);
			";

			if($this->db->query($update_advertisers_name_sql, $update_advertisers_name_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				$num_expected_affected_rows = $stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id +
					$stats->num_modified_advertiser_names;

				if($num_affected_rows !== $num_expected_affected_rows * $mysql_update_count_multiplier)
				{
					$num_affected_advertisers = $num_affected_rows / $mysql_update_count_multiplier;
					$shared_error_message = "Error: $num_affected_advertisers advertisers rows affected, but expected to affect $num_expected_affected_rows (#17949)";
					$this->note_other_error($notifications, $shared_error_message);
					$update_advertisers_name_values_bindings_string = print_r($update_advertisers_name_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id: $stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id
						stats->num_modified_advertiser_names: $stats->num_modified_advertiser_names\n\n
						update_advertisers_name_sql: $update_advertisers_name_sql\n\n
						update_advertisers_name_values_bindings: $update_advertisers_name_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;

					echo $internal_error_message;
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Update advertisers name query failed. (#23709)");
			}
		}

		foreach($actions->new_spectrum_account_for_advertiser as $advertiser_ul_id => $spectrum_account_data)
		{
			$advertiser_id = $relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id];
			array_unshift($spectrum_account_data, $advertiser_id);

			$this->add_values_and_bindings(
				$create_tp_spectrum_accounts_values_sql,
				$create_tp_spectrum_accounts_values_bindings,
				$spectrum_account_data
			);
		}

		if(!empty($create_tp_spectrum_accounts_values_sql))
		{
			$this->print_progress_message("Creating {$this->third_party_friendly_name} Accounts");

			$create_tp_spectrum_accounts_values_sql = rtrim($create_tp_spectrum_accounts_values_sql, ',');
			$create_tp_spectrum_accounts_sql = "												/*		1.2.1.1		*/
				INSERT IGNORE INTO tp_spectrum_accounts (
					advertiser_id,
					ul_id,
					eclipse_id,
					primary_commodity_code,
					primary_commodity,
					secondary_commodity_code,
					secondary_commodity
				)
				VALUES
				$create_tp_spectrum_accounts_values_sql
			";

			if($this->db->query($create_tp_spectrum_accounts_sql, $create_tp_spectrum_accounts_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				$num_expected_affected_rows = $stats->num_created_advertisers; // + $stats->num_new_advertiser_rows_matching_existing_advertiser_name;
				if($num_affected_rows !== $num_expected_affected_rows)
				{
					$shared_error_message = "Error: $num_affected_rows spectrum account rows affected, but expected to affect $num_expected_affected_rows. (#2276)";
					$this->note_other_error($notifications, $shared_error_message);

					$create_tp_spectrum_accounts_values_bindings_string = print_r($create_tp_spectrum_accounts_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						create_tp_spectrum_accounts_values_sql: $create_tp_spectrum_accounts_values_sql\n\n
						create_tp_spectrum_accounts_values_bindings_string: $create_tp_spectrum_accounts_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Update advertisers spectrum ids query failed. (#24021)");
			}
		}

		if(!empty($create_account_executive_values_sql))
		{
			$this->print_progress_message("Creating Account Executives");

			$create_account_executive_values_sql = rtrim($create_account_executive_values_sql, ',');
			$create_account_executive_users_sql = "
				INSERT IGNORE INTO users (
					username,
					banned,
					role,
					placements_viewable,
					screenshots_viewable,
					beta_report_engagements_viewable,
					ad_sizes_viewable,
					partner_id,
					spectrum_account_executive_id,
					email
				)
				VALUES
				$create_account_executive_values_sql
			";

			if($this->db->query($create_account_executive_users_sql, $create_account_executive_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				$num_expected_affected_rows = $stats->num_unknown_account_executives + $stats->num_place_holder_sales_office_account_executives;
				if($num_affected_rows !== $num_expected_affected_rows)
				{
					$shared_error_message = "Error: $num_affected_rows Account Executive users created, but expected to create $num_expected_affected_rows. (#16154)";
					$this->note_other_error($notifications, $shared_error_message);

					$create_account_executive_values_bindings_string = print_r($create_account_executive_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						create_account_executive_users_sql: $create_account_executive_users_sql\n\n
						create_account_executive_values_bindings_string: $create_account_executive_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;

					return false;
				}
				else
				{
					$starting_insert_id = $this->db->insert_id();
					foreach($relationships->new_account_executive_ul_ids as $index => $account_executive_ul_id)
					{
						$relationships->map_new_account_executive_ul_id_to_user_id[$account_executive_ul_id] = $starting_insert_id + $index;
					}
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Creat account executive users query failed. (#29525)");
				return false;
			}
		}

		if(!empty($actions->update_advertiser_with_new_account_executive_by_advertuser_ul_id))
		{
			foreach($actions->update_advertiser_with_new_account_executive_by_advertuser_ul_id as $advertiser_ul_id => $account_executive_ul_id)
			{
				$stats->num_sales_person_updates_for_advertisers++;

				$this->add_values_and_bindings(
					$update_advertisers_sales_person_values_sql,
					$update_advertisers_sales_person_values_bindings,
					array(
						$relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id],
						$relationships->map_new_account_executive_ul_id_to_user_id[$account_executive_ul_id]
					)
				);
			}
		}

		if(!empty($actions->update_advertiser_with_new_account_executive_by_advertiser_id))
		{
			foreach($actions->update_advertiser_with_new_account_executive_by_advertiser_id as $advertiser_id => $account_executive_ul_id)
			{
				$stats->num_sales_person_updates_for_advertisers++;

				$this->add_values_and_bindings(
					$update_advertisers_sales_person_values_sql,
					$update_advertisers_sales_person_values_bindings,
					array(
						$advertiser_id,
						$relationships->map_new_account_executive_ul_id_to_user_id[$account_executive_ul_id]
					)
				);
			}
		}

		if(!empty($clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings))
		{
			$this->print_progress_message("Clearing {$this->third_party_friendly_name} Accounts Eclipse IDs");

			$clear_tp_spectrum_accounts_spectrum_eclipse_id_values_sql = '('.rtrim(str_repeat('?,', count($clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings)), ',').')';
			$clear_tp_spectrum_accounts_spectrum_eclipse_id_sql = "
				UPDATE tp_spectrum_accounts
				SET eclipse_id = NULL
				WHERE ul_id IN $clear_tp_spectrum_accounts_spectrum_eclipse_id_values_sql
			";

			if($this->db->query($clear_tp_spectrum_accounts_spectrum_eclipse_id_sql, $clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();

				$num_real_expected_affected_rows = $stats->num_modified_advertiser_eclipse_ids +
					$stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id;

				if($num_affected_rows !== $num_real_expected_affected_rows)
				{
					$shared_error_message = "Error: $num_affected_rows {$this->third_party_friendly_name} Accounts had Eclipse ID removed, but expected {$num_real_expected_affected_rows} to be affected. (#21804)";
					$this->note_other_error($notifications, $shared_error_message);

					$clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings_string = print_r($clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						stats->num_modified_advertiser_eclipse_ids: $stats->num_modified_advertiser_eclipse_ids
						stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id: $stats->num_new_advertiser_rows_updating_spectrum_account_eclipse_id\n
						clear_tp_spectrum_accounts_spectrum_eclipse_id_sql: $clear_tp_spectrum_accounts_spectrum_eclipse_id_sql\n\n
						clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings_string: $clear_tp_spectrum_accounts_spectrum_eclipse_id_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Failed to clear Eclipse IDs for update to {$this->third_party_friendly_name} Accounts (#4115)");
			}
		}

		if(!empty($update_tp_spectrum_accounts_values_sql))
		{
			$this->print_progress_message("Updating {$this->third_party_friendly_name} Account Values");

			$update_tp_spectrum_accounts_values_sql = rtrim($update_tp_spectrum_accounts_values_sql, ',');
			$update_tp_spectrum_accounts_sql = "
				INSERT tp_spectrum_accounts (
					ul_id,
					eclipse_id,
					primary_commodity_code,
					primary_commodity,
					secondary_commodity_code,
					secondary_commodity
				)
				VALUES
					$update_tp_spectrum_accounts_values_sql
				ON DUPLICATE KEY UPDATE
					eclipse_id = VALUES(eclipse_id),
					primary_commodity_code = VALUES(primary_commodity_code),
					primary_commodity = VALUES(primary_commodity),
					secondary_commodity_code = VALUES(secondary_commodity_code),
					secondary_commodity = VALUES(secondary_commodity)

			";

			if($this->db->query($update_tp_spectrum_accounts_sql, $update_tp_spectrum_accounts_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();

				$num_real_expected_affected_rows = $stats->num_new_advertiser_rows_updating_spectrum_account +
					$stats->num_modified_spectrum_accounts;
				$num_expected_affected_rows = $num_real_expected_affected_rows * $mysql_update_count_multiplier;

				if($num_affected_rows !== $num_expected_affected_rows)
				{
					$num_real_affected_rows = $num_affected_rows / $mysql_update_count_multiplier;

					$shared_error_message = "Error: $num_real_affected_rows Advertisers updated with unknown Account Executive, but expected {$num_real_expected_affected_rows}. (#1233)";
					$this->note_other_error($notifications, $shared_error_message);

					$update_tp_spectrum_accounts_values_bindings_string = print_r($update_tp_spectrum_accounts_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						stats->num_new_advertiser_rows_updating_spectrum_account: $stats->num_new_advertiser_rows_updating_spectrum_account
						stats->num_modified_spectrum_accounts: $stats->num_modified_spectrum_accounts\n
						update_tp_spectrum_accounts_sql: $update_tp_spectrum_accounts_sql\n\n
						update_tp_spectrum_accounts_values_bindings_string: $update_tp_spectrum_accounts_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Failed to update Advertisers with Account Executives who aren't known (#18658)");
			}
		}

		if(!empty($update_advertisers_sales_person_values_sql))
		{
			$this->print_progress_message("Updating Advertiser sales people");
			$update_advertisers_sales_person_values_sql = rtrim($update_advertisers_sales_person_values_sql, ',');
			$update_advertisers_sales_person_sql = "												/*		8.1, 8.2	*/
				INSERT INTO Advertisers
					(id, sales_person)
				VALUES
					$update_advertisers_sales_person_values_sql
				ON DUPLICATE KEY UPDATE sales_person = VALUES(sales_person);
			";

			if($this->db->query($update_advertisers_sales_person_sql, $update_advertisers_sales_person_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				if($num_affected_rows !== $mysql_update_count_multiplier * $stats->num_sales_person_updates_for_advertisers)
				{
					$num_real_affected_rows = $num_affected_rows / $mysql_update_count_multiplier;

					$shared_error_message = "Error: $num_real_affected_rows Advertisers updated with unknown Account Executive, but expected {$stats->num_sales_person_updates_for_advertisers}. (#31516)";
					$this->note_other_error($notifications, $shared_error_message);

					$update_advertisers_sales_person_values_bindings_string = print_r($update_advertisers_sales_person_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						update_advertisers_sales_person_sql: $update_advertisers_sales_person_sql\n\n
						update_advertisers_sales_person_values_bindings_string: $update_advertisers_sales_person_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Failed to update Advertisers with Account Executives who aren't known (#7984)");
			}
		}

		if(!empty($create_client_values_sql))
		{
			$this->print_progress_message("Creating Client users");

			$create_client_values_sql = rtrim($create_client_values_sql, ',');
			$create_client_users_sql = "
				INSERT IGNORE INTO users (
					username,
					banned,
					role,
					placements_viewable,
					screenshots_viewable,
					beta_report_engagements_viewable,
					ad_sizes_viewable,
					partner_id,
					email,
					firstname,
					lastname,
					new_password_key,
					new_password_requested
				)
				VALUES
				$create_client_values_sql
			";

			if($this->db->query($create_client_users_sql, $create_client_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				$num_new_client_users = $stats->num_new_rows_creating_new_client_users + $stats->num_modified_rows_creating_new_client_user;

				if($num_affected_rows !== $num_new_client_users)
				{
					$shared_error_message = "Error: $num_affected_rows client users created, but expected to create $num_new_client_users. (#5852)";
					$this->note_other_error($notifications, $shared_error_message);

					$create_client_values_bindings_string = print_r($create_client_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						create_client_users_sql: $create_client_users_sql\n\n
						create_client_values_bindings_string: $create_client_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;

					return false;
				}
				else
				{
					$starting_insert_id = $this->db->insert_id();
					foreach($relationships->new_client_emails as $index => $client_email)
					{
						$relationships->map_new_user_email_to_user_id[strtolower($client_email)] = $starting_insert_id + $index;
					}
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Create client users query failed. (#4517)");
				return false;
			}
		}

		if(!empty($create_agency_values_sql))
		{
			$this->print_progress_message("Creating Agency users");
			$create_agency_values_sql = rtrim($create_agency_values_sql, ',');
			$create_agency_users_sql = "
				INSERT IGNORE INTO users (
					username,
					banned,
					role,
					placements_viewable,
					screenshots_viewable,
					beta_report_engagements_viewable,
					ad_sizes_viewable,
					partner_id,
					email,
					firstname,
					lastname,
					new_password_key,
					new_password_requested
				)
				VALUES
				$create_agency_values_sql
			";

			if($this->db->query($create_agency_users_sql, $create_agency_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();
				$num_new_agency_users = $stats->num_new_rows_creating_new_agency_users + $stats->num_modified_rows_creating_new_agency_user;

				if($num_affected_rows !== $num_new_agency_users)
				{
					$shared_error_message = "Error: $num_affected_rows agency users created, but expected to create $num_new_agency_users. (#9874)";
					$this->note_other_error($notifications, $shared_error_message);

					$create_agency_values_bindings_string = print_r($create_agency_values_bindings, true);
					$internal_error_message = "
						$shared_error_message\n
						create_agency_users_sql: $create_agency_users_sql\n\n
						create_agency_values_bindings_string: $create_agency_values_bindings_string\n
					";
					$notifications->internal_errors[] = $internal_error_message;
					echo $internal_error_message;

					return false;
				}
				else
				{
					$starting_insert_id = $this->db->insert_id();
					foreach($relationships->new_agency_emails as $index => $agency_email)
					{
						$relationships->map_new_user_email_to_user_id[strtolower($agency_email)] = $starting_insert_id + $index;
					}
				}
			}
			else
			{
				$this->note_other_error($notifications, "Error: Create agency users query failed. (#19827)");
				return false;
			}
		}

		$is_power_user = 1;
		foreach($actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_ul_id as $advertiser_ul_id => $client_email)
		{
			$client_user_id = $relationships->map_new_user_email_to_user_id[strtolower($client_email)];
			$advertiser_id = $relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id];

			$this->add_values_and_bindings(
				$create_client_to_advetiser_relationship_values_sql,
				$create_client_to_advetiser_relationship_values_bindings,
				array(
					$client_user_id,
					$advertiser_id,
					$is_power_user
				)
			);
		}

		foreach($actions->new_client_to_advertiser_relationship_by_client_user_id as $advertiser_ul_id => $client_user_id)
		{
			$advertiser_id = $relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id];

			$this->add_values_and_bindings(
				$create_client_to_advetiser_relationship_values_sql,
				$create_client_to_advetiser_relationship_values_bindings,
				array(
					$client_user_id,
					$advertiser_id,
					$is_power_user
				)
			);
		}

		foreach($actions->new_client_to_advertiser_relationship_by_client_email_and_advertiser_id as $advertiser_id => $client_email)
		{
			$client_user_id = $relationships->map_new_user_email_to_user_id[strtolower($client_email)];

			$this->add_values_and_bindings(
				$create_client_to_advetiser_relationship_values_sql,
				$create_client_to_advetiser_relationship_values_bindings,
				array(
					$client_user_id,
					$advertiser_id,
					$is_power_user
				)
			);
		}

		foreach($actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_ul_id as $advertiser_ul_id => $agency_email)
		{
			$agency_user_id = $relationships->map_new_user_email_to_user_id[strtolower($agency_email)];
			$advertiser_id = $relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id];

			$this->add_values_and_bindings(
				$create_client_to_advetiser_relationship_values_sql,
				$create_client_to_advetiser_relationship_values_bindings,
				array(
					$agency_user_id,
					$advertiser_id,
					$is_power_user
				)
			);
		}

		foreach($actions->new_client_to_advertiser_relationship_by_agency_user_id as $advertiser_ul_id => $agency_user_id)
		{
			$advertiser_id = $relationships->map_advertiser_ul_id_to_advertiser_id[$advertiser_ul_id];

			$this->add_values_and_bindings(
				$create_client_to_advetiser_relationship_values_sql,
				$create_client_to_advetiser_relationship_values_bindings,
				array(
					$agency_user_id,
					$advertiser_id,
					$is_power_user
				)
			);
		}

		foreach($actions->new_client_to_advertiser_relationship_by_agency_email_and_advertiser_id as $advertiser_id => $agency_email)
		{
			$agency_user_id = $relationships->map_new_user_email_to_user_id[strtolower($agency_email)];

			$this->add_values_and_bindings(
				$create_client_to_advetiser_relationship_values_sql,
				$create_client_to_advetiser_relationship_values_bindings,
				array(
					$agency_user_id,
					$advertiser_id,
					$is_power_user
				)
			);
		}

		if(!empty($remove_agency_users_from_advertiser_values_bindings))
		{
			$this->print_progress_message("Removing old Agency to Advertiser relationships");

			$remove_agency_users_from_advertiser_values_sql = '('.rtrim(str_repeat('?,', count($remove_agency_users_from_advertiser_values_bindings)), ',').')';
			$remove_agency_users_from_advertiser_sql = "
				DELETE FROM
					cta
					USING
					clients_to_advertisers AS cta
					JOIN users
						ON (cta.user_id = users.id)
				WHERE
					users.role = 'AGENCY' AND
					cta.advertiser_id IN $remove_agency_users_from_advertiser_values_sql
			";

			if(!$this->db->query($remove_agency_users_from_advertiser_sql, $remove_agency_users_from_advertiser_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();

				$shared_error_message = "Error: Failed to remove clients to advertisers associations. (#12679)";
				$this->note_other_error($notifications, $shared_error_message);

				$remove_agency_users_from_advertiser_values_bindings_string = print_r($remove_agency_users_from_advertiser_values_bindings, true);
				$internal_error_message = "
					$shared_error_message\n
					remove_agency_users_from_advertiser_sql: $remove_agency_users_from_advertiser_sql\n\n
					remove_agency_users_from_advertiser_values_bindings_string: $remove_agency_users_from_advertiser_values_bindings_string\n
				";
				$notifications->internal_errors[] = $internal_error_message;
				echo $internal_error_message;

				return false;
			}

			$this->print_progress_message("Done removing old Agency to Advertiser relationships");
		}

		if(!empty($create_client_to_advetiser_relationship_values_sql))
		{
			$this->print_progress_message("Creating Client to Advertiser relationships");

			$create_client_to_advetiser_relationship_values_sql = rtrim($create_client_to_advetiser_relationship_values_sql, ',');

			$create_client_to_advetiser_relationship_sql = "
				INSERT IGNORE INTO clients_to_advertisers
				(user_id, advertiser_id, is_power_user)
				VALUES
				$create_client_to_advetiser_relationship_values_sql
			";

			if(!$this->db->query($create_client_to_advetiser_relationship_sql, $create_client_to_advetiser_relationship_values_bindings))
			{
				$num_affected_rows = $this->db->affected_rows();

				$shared_error_message = "Error: Failed to create client/agency to advertiser relationships. (#10229)";
				$this->note_other_error($notifications, $shared_error_message);

				$create_client_to_advetiser_relationship_values_bindings_string = print_r($create_client_to_advetiser_relationship_values_bindings, true);
				$internal_error_message = "
					$shared_error_message\n
					create_client_to_advetiser_relationship_sql: $create_client_to_advetiser_relationship_sql\n\n
					create_client_to_advetiser_relationship_values_bindings_string: $create_client_to_advetiser_relationship_values_bindings_string\n
				";
				$notifications->internal_errors[] = $internal_error_message;
				echo $internal_error_message;
			}
		}

		$this->print_progress_message("Start sending welcome emails");
		foreach($notifications->new_user_email as $lower_email => $email_data)
		{
			$user_id = $relationships->map_new_user_email_to_user_id[$lower_email];

			$advertisers_string = "";
			$num_advertiser_rows = count($email_data['advertiser_rows']);
			$last_row_index = $num_advertiser_rows - 1;

			foreach($email_data['advertiser_rows'] as $index => $row)
			{
				if($index === $last_row_index)
				{
					if($num_advertiser_rows > 2)
					{
						$advertisers_string .= ", and " .$row->new_advertiser_name;
					}
					elseif($num_advertiser_rows == 2)
					{
						$advertisers_string .= " and " .$row->new_advertiser_name;
					}
					else
					{
						$advertisers_string .= $row->new_advertiser_name;
					}
				}
				else
				{
					$advertisers_string .= ", " . $row->new_advertiser_name;
				}
			}

			$advertisers_string = ltrim($advertisers_string, ", ");

			// Uncomment to re-enable sending welcome emails to new Client & Agency users
			//$this->send_email_to_new_user(
			//	$user_id,
			//	$email_data['email'],
			//	$email_data['first_name'],
			//	$email_data['last_name'],
			//	$email_data['partner_cname'],
			//	$advertisers_string,
			//	$email_data['partner_name']
			//);
		}
		$this->print_progress_message("Done sending welcome emails");

		//echo "Relationships after queries:\n";
		//print_r($relationships);

		//echo "\nActions:\n\n";
		//print_r($actions);

		//echo "\nNotifications:\n\n";
		//print_r($notifications);

		echo "\nStats:\n\n";
		print_r($stats);

		$this->print_progress_message("End - process_table_deltas()");

		return true;
	}

	public function create_user_password_reset_key()
	{
		$password_reset_key = substr(md5(microtime()),rand(0,16),15);
		return $password_reset_key;
	}

 	public function send_email_to_new_user(
		$user_id,
		$email,
		$first_name,
		$last_name,
		$cname,
		$advertiser_names_string,
		$partner_name,
		$support_email = null,
		$sales_person_info = null
	)
	{
		$data['firstname'] = $first_name;
		$data['lastname'] = $last_name;
		$data['advertiser_names_string'] = $advertiser_names_string;
		$data['email'] = $email;

		$data['partner_name'] = $partner_name;
		$data['partner_url'] = $cname . '.' . g_second_level_domain;
		$data['user_id']=$user_id;
		$password_reset_key = $this->create_user_password_reset_key();
		$this->users->set_password_key($user_id, $password_reset_key);
		$data['new_pass_key'] = $password_reset_key;
		
		$from_email = "media.digitalsupport@charter.com";
		$reply_to_email = "media.digitalsupport@charter.com";	
		
		if (isset($sales_person_info) && $sales_person_info)
		{
			$from_email = $sales_person_info['firstname'].' '.$sales_person_info['lastname'].' <'.$sales_person_info['email'].'>';
			$reply_to_email = $sales_person_info['email'];
		}
			
		$result = mailgun(
			$from_email,
			$email,
			"Your Multi-Screen Campaign Reporting is Now Available",
			$this->load->view('email/new_spectrum_client_welcome_auto', $data, TRUE),
			"html",
			array(
				'Reply-To' => $reply_to_email
			)
		);
		return $result;
	}


	private $last_micro_time = 0;

	private function print_progress_message($message)
	{
		$micro_time = microtime(true);
		$delta_time = 0;
		if($this->last_micro_time !== 0)
		{
			$delta_time = $micro_time - $this->last_micro_time;
		}

		$time = sprintf("% 10.04F", $delta_time)." (".date("H:i:s")." ".sprintf("% 17.04F", $micro_time).') ';
		echo $time . $message . "\n";

		$this->last_micro_time = $micro_time;
	}

	private function add_values_and_bindings(&$values_sql, &$accumulated_bindings, $new_bindings)
	{
		$values_sql .= '('.rtrim(str_repeat('?,', count($new_bindings)), ',').'),';
		$accumulated_bindings = array_merge($accumulated_bindings, $new_bindings);
	}

	public function insert_new_spectrum_accounts(
		&$stats,
		&$notifications,
		$new_spectrum_accounts_table_name,
		$accounts_csv_input,
		$date_to_process
	)
	{
		$this->print_progress_message("Start loading raw Client Accounts data");
		echo "insert_new_spectrum_accounts()\n";
		$is_success = true;

		/// Old Client CSV column names and order
		// ACCOUNTKEY
		// TrafficID
		// Name
		// Primary Commodity Code
		// Primary Commodity

		// Secondary Commodity Code
		// Secondary Commodity
		// Agency AccountKey
		// Agency Name
		// SalesPersonNAME

		// AEID
		// SalesOfficeNAME
		// SALESOFFICEID
		// Traffic System
		// MAX(O.ORDERENDDATE)

		// Dashboard Client Email
		// Client Users Name First
		// Client Users Name Last
		// Dashboard Agency Email

		/// New Client CSV column names and order
		// CustomerULID
		// CustomerNumber
		// CustomerName
		// PrimCommCode
		// PrimCommName

		// SecCommCode
		// SecCommName
		// AgencyULID
		// AgencyName
		// SalesPersonName

		// SalesPersonCode
		// SalesOfficeName
		// SalesOfficeCode
		// TrafficSystem
		// LastRunDate

		// DashboardClientEmail
		// ClientUsersNameFirst
		// ClientUsersNameLast
		// DashboardAgencyEmail

		// Field order in the database `tp_spectrum_accounts_import` table
		// 'advertiser_ul_id',
		// 'advertiser_name',
		// 'advertiser_eclipse_id',
		// 'newest_order_date',
		// 'primary_commodity_code',

		// 'primary_commodity',
		// 'secondary_commodity_code',
		// 'secondary_commodity',
		// 'client_email',
		// 'client_first_name',

		// 'client_last_name',
		// 'agency_account_ul_id',
		// 'agency_name',
		// 'agency_email',
		// 'account_executive_name',

		// 'account_executive_ul_id',
		// 'sales_office_name',
		// 'sales_office_ul_id',
		// 'traffic_system'

		$table_field_names = array(
			'advertiser_ul_id_raw',
			'advertiser_eclipse_id_raw',
			'advertiser_name',
			'primary_commodity_code',
			'primary_commodity',
			'secondary_commodity_code',
			'secondary_commodity',
			'agency_account_ul_id_raw',
			'agency_name',
			'account_executive_name',
			'account_executive_ul_id_raw',
			'sales_office_name',
			'sales_office_ul_id_raw',
			'traffic_system_raw',
			'newest_order_date',
			'client_email',
			'client_first_name',
			'client_last_name',
			'agency_email'
		);

		$date_time = new DateTime($date_to_process);
		$v2_start_date = new DateTime('2016-03-23');
		$v2_delta = $v2_start_date->diff($date_time);
		$has_v2_data = !$v2_delta->invert;
		if($has_v2_data)
		{
			$table_field_names[] = 'client_create_date';
		}

		$accounts_csv_handle = fopen($accounts_csv_input, "r");
		if($accounts_csv_handle === false)
		{
			throw new Exception("Couldn't get {$this->third_party_friendly_name} Account csv file '$accounts_csv_input' (#987347)");
		}
		else
		{
			echo "Opened file '$accounts_csv_input'\n";
		}

		$csv_field_names = fgetcsv($accounts_csv_handle, 0, '|');
		if($csv_field_names === null || $csv_field_names === false)
		{
			throw new Exception("Couldn't process {$this->third_party_friendly_name} Account csv '$accounts_csv_input' (#238723)");
		}

		$num_fields = count($csv_field_names);

		$table_field_names_string = '`'.implode('`,`', $table_field_names).'`,`csv_line_number`';
		$values_string = '('.rtrim(str_repeat('?,', $num_fields), ',').',?),';

		$insert_main_sql = "
			INSERT IGNORE $new_spectrum_accounts_table_name
			($table_field_names_string)
			VALUES
		";

		$k_max_rows_per_insert = 5000;
		$num_mismatched_rows = 0;
		$total_rows = 1;

		$field_values = true;
		while($field_values && $is_success)
		{
			$bindings = array();
			$num_rows_this_insert = 0;

			while($num_rows_this_insert < $k_max_rows_per_insert &&
				$field_values = fgetcsv($accounts_csv_handle, 0, '|')
			)
			{
				$total_rows++;

				$num_field_values = count($field_values);
				if($num_field_values != $num_fields)
				{
					++$num_mismatched_rows;
					throw new Exception("Row #$total_rows has mismatched columns $num_fields, $num_field_values (#5753971)");
					continue;
				}

				array_walk($field_values, function(&$value, $key) { $value = empty($value) ? null : trim($value); });
				$date_index = 14;
				$field_values[$date_index] = date("Y-m-d", strtotime($field_values[$date_index]));
				$field_values[] = $total_rows;

				$bindings = array_merge($bindings, $field_values);
				$num_rows_this_insert++;
			}

			$insert_values_sql = rtrim(str_repeat($values_string, $num_rows_this_insert), ',');
			if(!empty($insert_values_sql))
			{
				$insert_sql = $insert_main_sql.$insert_values_sql;

				$is_success = $this->db->query($insert_sql, $bindings);
				if(!$is_success)
				{
					$failure_message = "Insert from Account csv failure (#89075472)";
					echo $failure_message."\n";
					throw new Exception($failure_message);
				}
			}
		}

		fclose($accounts_csv_handle);

		$stats->total_rows = $total_rows;

		$traffic_system_case_sql = get_sql_case_mapping_spectrum_traffic_system_name_to_id('traffic_system_raw');

		$encode_traffic_system_sql = "
			UPDATE $new_spectrum_accounts_table_name
			SET traffic_system = $traffic_system_case_sql
		";

		if(!$this->db->query($encode_traffic_system_sql))
		{
			$failure_message = "Failed to encode Traffic System (#32588)";
			echo $failure_message."\n";
			throw new Exception($failure_message);
		}

		$add_traffic_system_to_ul_ids_sql = "
			UPDATE $new_spectrum_accounts_table_name
			SET
				advertiser_ul_id = CONCAT(traffic_system, advertiser_ul_id_raw),
				advertiser_eclipse_id = CONCAT(traffic_system, advertiser_eclipse_id_raw),
				agency_account_ul_id = CONCAT(traffic_system, agency_account_ul_id_raw),
				account_executive_ul_id = CONCAT(traffic_system, account_executive_ul_id_raw),
				sales_office_ul_id = CONCAT(traffic_system, sales_office_ul_id_raw)
		";

		if(!$this->db->query($add_traffic_system_to_ul_ids_sql))
		{
			$failure_message = "Failed to encode IDs with Traffic System (#12710)";
			echo $failure_message."\n";
			throw new Exception($failure_message);
		}

		$this->print_progress_message("Finished loading raw Client Accounts data");

		return $is_success;
	}
}
