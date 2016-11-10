<?php
class user_editor extends CI_Controller
{

	public function __construct()
	{
    	parent::__construct();
    	$this->load->database();
    	$this->load->library(array('tank_auth', 'vl_platform'));
    	$this->load->library('form_validation');
    	$this->load->model(array('vl_auth_model', 'user_editor_model', 'report_v2_model'));
    	$this->load->helper('vl_ajax_helper');
  	}

	public function index()
	{
		if($this->vl_platform->has_permission_to_view_page_otherwise_redirect('user_editor', '/user_editor'))
		{
			$data = array();
			$data['title'] = "User Editor";
			$data['report_permissions'] = $this->report_v2_model->get_subfeature_access_permissions($this->tank_auth->get_user_id());
			$data['can_use_register'] = $this->vl_platform->get_access_permission_redirect('register') == '';
			$data['user_role'] = $this->tank_auth->get_role($this->tank_auth->get_username());
			$active_feature_button_id = 'user_editor';
			//Get current user report permissions details to see what they can/can't edit from the user permissions
			//Get list of users



			//Display list of users, passing in own permissions to determine which columns are visible
			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'user_editor/user_editor_html.php',
				null,
				null,
				'user_editor/user_editor_js.php',
				null
			);
		}
	}

	public function ajax_get_user_editor_users()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' AND $this->tank_auth->is_logged_in())
		{

			$return_array = array();
			$return_array['user_array'] = array();
			$return_array['is_success'] = true;
			$return_array['cur_user_partner_id'] = null;
			$return_array['err_msg'] = "";
			$return_array['can_edit_role'] = true;
			$return_array['timezone_code'] = null;

			$timezone_offset = $this->input->post('timezone_offset');
			if($timezone_offset === false)
			{
				$timezone_offset = 0;
			}

			$username = $this->tank_auth->get_username();
			$user_id = $this->tank_auth->get_user_id();
			$user_report_permissions = $this->report_v2_model->get_subfeature_access_permissions($user_id);
			$user_partner_id = $this->tank_auth->get_partner_id($user_id);
			$user_role = strtolower($this->tank_auth->get_role($username));
			$user_is_super = $this->tank_auth->get_isGroupSuper($username);

			if($user_role != "admin" && $user_role != "ops")
			{
				$return_array['cur_user_partner_id'] = $user_partner_id;
				$return_array['can_edit_role'] = false;
			}
			$get_data_result = $this->user_editor_model->get_users_and_details_for_editor_users_for_id($user_id, $user_role, $user_partner_id, $user_is_super, $timezone_offset);
			if($get_data_result['success'] == false)
			{
				$return_array['is_success'] = true;
				$return_array['err_msg'] = "Error 99852: Failed to retrieve user data";
				echo json_encode($return_array);
				return;
			}

			if($user_role != "admin" && $user_role != "ops")
			{
				$advertisers_result = $this->tank_auth->get_advertisers_by_user_id_and_is_super($user_id, $user_is_super);
			}
			else
			{
				$advertisers_result = $this->tank_auth->get_advertisers_by_user_id_for_admin_and_ops($user_id);
			}
			if($advertisers_result === false)
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "Error 93432: Failed to retrieve user data";
				echo json_encode($return_array);
				return;
			}

			$return_array['available_advertisers'] = array();
			foreach($advertisers_result as $advertiser)
			{
				$return_array['available_advertisers'][$advertiser['id']] = array('id'=>$advertiser['id'], 'name'=>$advertiser['Name'], 'adv_partner'=>$advertiser['partner_id']);
			}


			if(count($get_data_result['user_data']) > 0)
			{
				$return_array['user_array'] = $get_data_result['user_data'];
				$return_array['partner_descendants'] = $get_data_result['partner_descendants'];

			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "Error 70429: Found no users to manage";
				echo json_encode($return_array);
				return;
			}

			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function update_permission_for_user()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);

		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";

		if($verify_ajax_response['is_success'] === true)
		{
			$target_user_id = $this->input->post('user_id');
			$permission_changed = $this->input->post('permission');
			$permission_enabled  = $this->input->post('is_enabled');

			if($target_user_id === false || $permission_changed === false || $permission_enabled === false)
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "Invalid request data.";
				echo json_encode($return_array);
				return;
			}

			if($permission_enabled == "true")
			{
				$permission_enabled = 1;
			}
			else
			{
				$permission_enabled = 0;
			}

			$did_update_user_permission = $this->user_editor_model->set_permission_for_user($target_user_id, $permission_changed, $permission_enabled);
			if($did_update_user_permission['is_success'] == false)
			{
				$return_array['is_success'] = false;
				$return_array['err_msg'] = $did_update_user_permission['err_msg'];
			}

			echo json_encode($return_array);
			return;
		}
	}

	public function get_user_form_details()
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['user_data'] = null;
		$return_array['err_msg'] = "";
		$return_array['bad_fields'] = null;

		$user_id = $this->input->post('user_id');
		if($user_id === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "426911: Failed to retrieve user details";
			echo json_encode($return_array);
			return;
		}
		$user_data = $this->user_editor_model->get_user_info($user_id);
		if($user_data == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "242691: Failed to retrieve user details";
			echo json_encode($return_array);
			return;
		}
		$return_array['user_data'] = $user_data;

		echo json_encode($return_array);
		return;
	}

	public function save_user_details()
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";

		if($this->input->post('user_id') === false || $this->input->post('user_data') === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "571576: Invalid edit request data.";
			echo json_encode($return_array);
			return;
		}

		$user_id = $this->input->post('user_id');
		$user_data_array = $this->input->post('user_data');
		$verify_data_bad_fields = $this->user_editor_model->verify_user_data_array($user_data_array, $user_id);
		if($verify_data_bad_fields === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "131336: Unable to validate user edit data .";
			echo json_encode($return_array);
			return;
		}
		if(!empty($verify_data_bad_fields['fields']))
		{
			//Bad fields
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Please verify you've filled out the following correctly:<br>";
			$return_array['err_msg'] .= implode("<br>", $verify_data_bad_fields['messages']);
			$return_array['bad_fields'] = $verify_data_bad_fields['fields'];
			echo json_encode($return_array);
			return;
		}

		$update_user_result = $this->user_editor_model->update_user($user_data_array, $user_id);
		if($update_user_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "599271: Failed to update user.";
			echo json_encode($return_array);
			return;
		}

		echo json_encode($return_array);
		return;

	}

	public function verify_role_change()
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['user_dependencies'] = "";

		if($this->input->post('user_id') === false || $this->input->post('old_role') === false || $this->input->post('new_role') === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "95216: Invalid edit request data.";
			echo json_encode($return_array);
			return;
		}

		$user_id = $this->input->post('user_id');
		$previous_role = $this->input->post('old_role');
		$dependency_lines = array();
		switch($previous_role)
		{
			case "ADMIN":
				$report_ops_owner = $this->find_and_report_ops_owner($user_id, $dependency_lines);
				if($report_ops_owner === false)
				{
					$return_array['is_success'] = false;
					$return_array['err_msg'] = "48528: Failed to locate ops owner advertisers.";
					echo json_encode($return_array);
					return;
				}
				break;
			case "OPS":
				$report_ops_owner = $this->find_and_report_ops_owner($user_id, $dependency_lines);
				if($report_ops_owner === false)
				{
					$return_array['is_success'] = false;
					$return_array['err_msg'] = "48128: Failed to locate ops owner advertisers.";
					echo json_encode($return_array);
					return;
				}
				break;
			case "CREATIVE":
				$report_ops_owner = $this->find_and_report_ops_owner($user_id, $dependency_lines);
				if($report_ops_owner === false)
				{
					$return_array['is_success'] = false;
					$return_array['err_msg'] = "48828: Failed to locate ops owner advertisers.";
					echo json_encode($return_array);
					return;
				}
				break;
			case "BUSINESS":
				//Business user only cares about assigned advertiser.
				//Shouldn't have to worry
				break;
			case "SALES":
				//Check for owned partners
				//Check for advertisers they might be a salesperson for
				$salesperson = $this->find_and_report_salesperson($user_id, $dependency_lines);
				if($salesperson === false)
				{
					$return_array['is_success'] = false;
					$return_array['err_msg'] = "18328: Failed to locate assigned salesperson advertisers.";
					echo json_encode($return_array);
					return;
				}
				$partner_owner = $this->find_and_report_owned_partners($user_id, $dependency_lines);
				if($partner_owner === false)
				{
					$return_array['is_success'] = false;
					$return_array['err_msg'] = "84228: Failed to locate owned partners.";
					echo json_encode($return_array);
					return;
				}
				break;
			default:
				$return_array['is_success'] = false;
				$return_array['err_msg'] = "191922: Unknown original user role.";
				echo json_encode($return_array);
				return;
		}
		$return_array['user_dependencies'] = implode("<br>", $dependency_lines);
		echo json_encode($return_array);
	}

	private function find_and_report_ops_owner($user_id, &$dependency_lines)
	{
		$ops_owner_advertisers = $this->user_editor_model->check_user_ops_owner($user_id);
		if($ops_owner_advertisers === false)
		{
			return false;
		}
		if(count($ops_owner_advertisers) > 0)
		{
			foreach($ops_owner_advertisers as $advertiser)
			{
				$dependency_lines[] = "- User is an Ops Owner for {$advertiser['advertiser_name']} <a target=\"_blank\" href=\"/campaign_setup/{$advertiser['campaign_id']}\">Resolve this?</a>";
			}
		}
		return true;
	}

	private function find_and_report_salesperson($user_id, &$dependency_lines)
	{
		$salesperson_advertisers = $this->user_editor_model->check_user_sales_person($user_id);
		if($salesperson_advertisers === false)
		{
			return false;
		}
		if(count($salesperson_advertisers) > 0)
		{
			foreach($salesperson_advertisers as $advertiser)
			{
				$dependency_lines[] = "- User is a sales rep for '{$advertiser['advertiser_name']}'. <a target=\"_blank\" href=\"/campaign_setup/{$advertiser['campaign_id']}\">Resolve this?</a>";
			}
		}
		return true;
	}

	private function find_and_report_owned_partners($user_id, &$dependency_lines)
	{
		$owned_partners =$this->user_editor_model->check_user_owns_partners($user_id);
		if($owned_partners === false)
		{
			return false;
		}
		if(count($owned_partners) > 0)
		{
			foreach($owned_partners as $partner)
			{
				$dependency_lines[] = "- User owns partner '{$partner['partner_name']}'. Remove owned partners.";
			}
		}
		return true;
	}

	public function download_users($timezone_offset,$timezone_code)
	{
		if ($this->tank_auth->is_logged_in())
		{
			if ($timezone_offset === false)
			{
				$timezone_offset = 0;
			}

			$username = $this->tank_auth->get_username();
			$user_id = $this->tank_auth->get_user_id();
			$user_partner_id = $this->tank_auth->get_partner_id($user_id);
			$user_role = strtolower($this->tank_auth->get_role($username));
			$user_is_super = $this->tank_auth->get_isGroupSuper($username);

			$get_data_result = $this->user_editor_model->get_users_and_details_for_editor_users_for_id($user_id, $user_role, $user_partner_id, $user_is_super, $timezone_offset, TRUE, '||');

			if ($get_data_result['success'] == false)
			{
				echo "<h2>Error 99852: Failed to retrieve user data</h2>";
				return;
			}

			if (count($get_data_result['user_data']) > 0)
			{
				$data['users'] = $get_data_result['user_data'];
				$data['timezone_code']=$timezone_code;
				usort($data['users'],function ($a,$b){
					$t1 = strtotime($b["last_login"]);
					$t2 = strtotime($a["last_login"]);
					return $t1 - $t2;
				});
				$this->load->view('user_editor/download_users_data', $data);
			}
			else
			{
				echo "<h2>Error 70429: Found no users to manage</h2>";
				return;
			}
		}
		else
		{
			show_404();
		}
	}
}
?>
