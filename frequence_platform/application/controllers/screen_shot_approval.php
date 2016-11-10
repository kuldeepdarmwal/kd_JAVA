<?php
define('k_vantage_local_partner_id', 1);
define('k_all_advertisers_special_id', 0);
define('k_no_advertisers_special_id', -1);
define('k_all_campaigns_special_id', 0);
define('k_no_campaigns_special_id', -1);

class screen_shot_approval extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->model('screen_shot_approval_model');
		$this->load->helper('url');
		$this->load->helper('select2_helper');
	}

	// send screen shot status change to server
	public function set_screen_shot_status()
	{
		$screen_shot_id = $this->input->post('id');
		$is_approved = $this->input->post('is_approved');
		$input_advertiser_id = $this->input->post('advertiser_id');
		
		if($this->tank_auth->is_logged_in() && 
			$this->get_redirect_if_wrong_user_type() == '' &&
			$input_advertiser_id != k_no_advertisers_special_id &&
			$this->is_access_allowed($input_advertiser_id))
		{
			if($is_approved == '')
			{
				$is_approved = NULL;
			}

			$response = $this->screen_shot_approval_model->set_screen_shot_status($screen_shot_id, $is_approved);
			if(empty($response) || $response->num_rows() == 0)
			{
					die('Failed to set approval for is: '.$screen_shot_id);
			}
		}
	}

	// get where the user should be redirected to if they aren't allowed to access the report
	private function get_redirect_if_wrong_user_type()
	{
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);

		$redirect = '';

		if($role != 'admin' && 
			$role != 'ops' &&
			$role != 'creative')
		{
			$redirect = 'director';
		}

		return $redirect;
	}

	// setup for first show of page
	public function get_screen_shots_structure_and_data()
	{
		if(!$this->tank_auth->is_logged_in()) 
		{
			$this->session->set_userdata('referer','review_screen_shots');
			redirect(site_url("login"));
			return;
		}

		$redirect = $this->get_redirect_if_wrong_user_type();
		if($redirect != '')
		{
			redirect($redirect);
			return;
		}

		$data = array();
		$end_date_time = strtotime('+1 day, -7 hours');
		$end_date = date('Y-m-d H:i:s', $end_date_time);
		$start_date = date('Y-m-d H:i:s', strtotime('-1 month', $end_date_time));
		$data['start_date'] = date("Y m d,H:i:s", strtotime($start_date));
		$data['end_date'] = date("Y m d,H:i:s", strtotime($end_date));

		$this->load->view('screen_shot_approval/ssa_header', $data);
		$this->load->view('screen_shot_approval/ssa_structure', $data);
	}

	// callback that retrieves the data for the various portions of the report which get updated when the call returns.
	public function ajax_get_screen_shots_data()
	{
		$return_data = array();
		$error_info = array();
		$is_success = 'false';
		if($this->tank_auth->is_logged_in() && $this->get_redirect_if_wrong_user_type() == '')
		{
			$input_advertiser_id = $this->input->post('advertiser_id');
			$input_action = $this->input->post('action');

			if($this->is_access_allowed($input_advertiser_id))
			{

				$input_request_id = $this->input->post('request_id');

				$input_start_date = date("Y-m-d H:i:s", strtotime($this->input->post('start_date')));
				$input_end_date = date("Y-m-d H:i:s", strtotime($this->input->post('end_date')));

				$input_status_filter = $this->input->post('status_filter');
				$input_sort_method = $this->input->post('sort_method');
				$input_page_index = $this->input->post('page_index');
				$input_num_items_per_page = $this->input->post('num_items_per_page');
		
				$input_campaign_id = $this->input->post('campaign_id');
				if($input_advertiser_id == k_all_advertisers_special_id)
				{
					$input_campaign_id = k_all_campaigns_special_id;
				}
				elseif($input_action == 'change_advertiser')
				{
					$campaigns_stats_list = $this->get_campaigns_stats_by_id($input_advertiser_id, $input_start_date, $input_end_date);
					$input_campaign_id = $this->get_first_campaign_id($campaigns_stats_list);
				}

				$is_success = $this->gather_specified_screen_shots_data(
					$return_data, 
					$error_info, 
					$input_advertiser_id, 
					$input_action, 
					$input_campaign_id, 
					$input_start_date, 
					$input_end_date, 
					$input_status_filter,
					$input_sort_method, 
					$input_page_index, 
					$input_num_items_per_page, 
					$input_request_id
				);
			}
			else
			{
				$error_info[] = 'Access denied for advertiser';
				$is_success = 'false';
			}
		}
		else
		{
			if($this->tank_auth->is_logged_in())
			{
				$error_info[] = 'Access denied for user';
			}
			else
			{
				$error_info[] = 'Not logged in';
			}

			$is_success = 'false';
		}

		$json_encoded_data = $this->get_screen_shots_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	// get data from server and organize in nested arrays
	private function gather_specified_screen_shots_data(
		Array &$return_data,
		Array &$error_info,
		$input_advertiser_id,
		$input_action, 
		$input_campaign_id,
		$input_start_date, 
		$input_end_date, 
		$input_status_filter,
		$input_sort_method,
		$input_page_index, 
		$input_num_items_per_page,
		$input_request_id
	)
	{
		$real_data = array();
		$real_data['total_num_screen_shots'] = 0;
		$real_data['total_num_screen_shot_pages'] = 1;
		$real_data['screen_shots_data'] = array();
		$real_data['request_id'] = $input_request_id;
		$total_num_screen_shots = 0;
		$num_screen_shots_response = $this->screen_shot_approval_model->get_screen_shots_data(
			$input_advertiser_id,
			$input_campaign_id,
			$input_start_date,
			$input_end_date,
			$input_status_filter,
			0,
			$input_num_items_per_page, 
			$input_sort_method
		);
		if($num_screen_shots_response->num_rows() > 0)
		{
			$total_num_screen_shots = $num_screen_shots_response->num_rows();//$num_screen_shots_response->row()->num_screen_shots;
		}
		$real_data['total_num_screen_shots'] = $total_num_screen_shots;

		$num_pages = 1;
		if($input_num_items_per_page > 0 && $total_num_screen_shots > 0)
		{
			$num_pages = ceil($total_num_screen_shots / $input_num_items_per_page);
		}
		$real_data['total_num_screen_shot_pages'] = $num_pages;

		$screen_shots_data_response = $this->screen_shot_approval_model->get_screen_shots_data(
			$input_advertiser_id,
			$input_campaign_id,
			$input_start_date,
			$input_end_date,
			$input_status_filter,
			$input_page_index,
			$input_num_items_per_page,
			$input_sort_method
		);
		$screen_shots_data = $screen_shots_data_response->result_array();

		if(count($screen_shots_data) > 0)
		{
			foreach($screen_shots_data as &$screen_shot_data)
			{
				$screen_shot_data['creation_date'] = date("m/d/Y H:i:s", strtotime($screen_shot_data['creation_date']));
			}

			$real_data['screen_shots_data'] = $screen_shots_data;
		}
		else
		{
			$real_data['screen_shots_data'] = array();
		}

		$return_data['real_data'] = $real_data;

		$is_success = 'true';
		return $is_success;
	}

	// augment the return data with standard parameters and encode it all in json
	private function get_screen_shots_data_json(&$return_data, $error_info, $is_success)
	{
		if(count($error_info) > 0)
		{
			$return_data['errors'] = $error_info;
		}

		$return_data['is_success'] = $is_success;

		return json_encode($return_data);
	}

	// determine whether the current user has permission to view the advertiser's data
	private function is_access_allowed($advertiser_id)
	{
		$is_allowed = false;
		$allowed_advertisers = $this->get_advertisers_stats();
		foreach($allowed_advertisers as $allowed_advertiser)
		{
			if($advertiser_id == $allowed_advertiser['id'])
			{
				$is_allowed = true;
				break;
			}
		}

		return $is_allowed;
	}

	// retrieves the list of advertiser identifiers this user can access based upon role
	private function get_advertisers_stats()
	{
		$businesses = array();
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if($role == 'admin' or $role == 'ops' or $role == 'creative')
		{
			$response = $this->screen_shot_approval_model->get_all_advertisers_screen_shots_stats();
			if($response->num_rows() > 0)
			{
				$businesses = $response->result_array();
				if($response->num_rows() > 0)
				{
					$totals = array(
						'num_unset' => 0,
						'num_approved' => 0,
						'total' => 0
						);
					foreach($businesses as $stat)
					{
						$totals['num_unset'] += $stat['num_unset'];
						$totals['num_approved'] += $stat['num_approved'];
						$totals['total'] += $stat['total'];
					}
					$all_advertisers_row = array('name' => 'All Advertisers', 'id' => k_all_advertisers_special_id, 'num_unset' => $totals['num_unset'], 'num_approved' => $totals['num_approved'], 'total' => $totals['total'] );
					array_unshift($businesses, $all_advertisers_row);
				}
			}
			else
			{
				$all_advertisers_row = array('name' => 'No Advertisers', 'id' => k_no_advertisers_special_id, 'num_unset' => 0, 'num_approved' => 0, 'total' => 0 );
				array_unshift($businesses, $all_advertisers_row);
			}
		}
		else
		{
			die("get_advertisers_stats() doesn't handle role: ".$role);
		}
		if($businesses == null)
		{
			$businesses = array();
		}
		if(count($businesses) == 0)
		{
			$all_advertisers = array('name' => 'No Advertisers', 'id' => k_no_advertisers_special_id, 'num_unset' => 0, 'num_approved' => 0, 'total' => 0);
			array_unshift($businesses, $all_advertisers);
		}
		return $businesses;
	}

	private function get_multiple_campaigns_stats_by_id(&$campaigns, &$all_campaigns_row, $advertiser_id, $start_date, $end_date)
	{
		$campaigns = array();
		$all_campaigns_row = array();

		$response = $this->screen_shot_approval_model->get_associated_campaigns_screen_shots_stats($advertiser_id, $start_date, $end_date);
		if($response->num_rows() > 0)
		{
			$campaigns = $response->result_array();
			if($response->num_rows() > 1)
			{
				$totals = array(
					'num_unset' => 0,
					'num_approved' => 0,
					'total' => 0
				);
				foreach($campaigns as $stat)
				{
					$totals['num_unset'] += $stat['num_unset'];
					$totals['num_approved'] += $stat['num_approved'];
					$totals['total'] += $stat['total'];
				}
				$all_campaigns_row = array('name' => 'All Campaigns', 'id' => k_all_campaigns_special_id, 'num_unset' => $totals['num_unset'], 'num_approved' => $totals['num_approved'], 'total' => $totals['total'] );
			}
		}
		else
		{
			$all_campaigns_row = array('name' => 'No Campaigns', 'id' => k_no_campaigns_special_id, 'num_unset' => 0, 'num_approved' => 0, 'total' => 0);
		}
	}

	// get the campaigns stats associated with the active advertiser
	private function get_campaigns_stats_by_id($advertiser_id, $start_date, $end_date)
	{
		$campaigns = array();
		if($advertiser_id == k_no_advertisers_special_id)
		{
			$campaigns = array(array('name' => 'No Campaigns', 'id' => k_no_campaigns_special_id, 'num_unset' => 0, 'num_approved' => 0, 'total' => 0));
		}
		elseif($advertiser_id == k_all_advertisers_special_id)
		{
			$all_campaigns_row = array();
			$stats = array();
			$this->get_multiple_campaigns_stats_by_id($stats, $all_campaigns_row, $advertiser_id, $start_date, $end_date);
			if(!empty($all_campaigns_row))
			{
				array_unshift($campaigns, $all_campaigns_row);
			}
		}
		else
		{
			$all_campaigns_row = array();
			$this->get_multiple_campaigns_stats_by_id($campaigns, $all_campaigns_row, $advertiser_id, $start_date, $end_date);
			if(!empty($all_campaigns_row))
			{
				array_unshift($campaigns, $all_campaigns_row);
			}
		}

		if($campaigns == null)
		{
			$campaigns = array();
		}

		if(count($campaigns) == 0)
		{
			$all_campaigns = array('name' => 'No Campaigns', 'id' => k_no_campaigns_special_id, 'num_unset' => 0, 'num_approved' => 0, 'total' => 0);
			array_unshift($campaigns, $all_campaigns);
		}

		return $campaigns;
	}

	// get the first campaign available in the set of campaigns
	private function get_first_campaign_id(array $campaigns)
	{
		$campaign_id = k_no_campaigns_special_id;
		if(count($campaigns) > 0)
		{
			$campaign_id = $campaigns[0]['id'];
		}

		return $campaign_id;
	}
	
	//This function will fetch advertiser list.
	public function get_advertisers_details()
	{
		$advertiser_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array))
			{
				if($post_array['page'] == 1)
				{
					$total_advertiser_result = select2_helper($this->screen_shot_approval_model, 'get_advertisers_total_stat', $post_array);
					$advertiser_array['results'][] = array(
						'id' => k_all_advertisers_special_id,
						'text' => 'All Advertisers ',
						'id_list' => $total_advertiser_result['results']
						);
				}
				$advertiser_response = select2_helper($this->screen_shot_approval_model, 'get_advertisers_details_for_select2', $post_array);
				if(!empty($advertiser_response['results']) && !$advertiser_response['errors'])
				{
					$advertiser_array['more'] = $advertiser_response['more'];
					for($i = 0; $i < $advertiser_response['real_count']; $i++)
					{
						$advertiser_array['results'][] = array(
						'id' => $advertiser_response['results'][$i]['id'],
						'text' => $advertiser_response['results'][$i]['text'],
						'id_list' => $advertiser_response['results'][$i]['id_list']
						);
					}
				}
			}
			echo json_encode($advertiser_array);
		}
		else
		{
			show_404();
		}
	}
	
	//This function will fetch campaign list.
	public function get_campaign_details()
	{
		$advertiser_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array))
			{
				$advertiser_id = isset($post_array['advertiser_id']) ? $post_array['advertiser_id'] : -1;
				$post_array['advertiser_id'] = $advertiser_id;
				if($post_array['page'] == 1)
				{
					$total_campaign_result = $this->screen_shot_approval_model->get_all_campaign($advertiser_id);
					$advertiser_array['results'][] = array(
						'id' => 0,
						'text' => 'All Campaign ',
						'id_list' => $total_campaign_result
						);
				}
				$advertiser_response = select2_helper($this->screen_shot_approval_model, 'get_campaign_total_stat', $post_array, array($post_array['advertiser_id']));

				if(!empty($advertiser_response['results']) && !$advertiser_response['errors'])
				{
					$advertiser_array['more'] = $advertiser_response['more'];
					if($advertiser_response['results'])
					{
						for($i = 0; $i < $advertiser_response['real_count']; $i++)
						{
							$advertiser_array['results'][] = array(
								'id' => $advertiser_response['results'][$i]['id'],
								'text' => $advertiser_response['results'][$i]['text'],
								'id_list' => $advertiser_response['results'][$i]['id_list']
								);
						}
					}
				}
			}
			echo json_encode($advertiser_array);
		}
		else
		{
			show_404();
		}
	}
}

