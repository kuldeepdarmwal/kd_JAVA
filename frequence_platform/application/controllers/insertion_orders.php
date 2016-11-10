<?php defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH.'/vendor/autoload.php';

class Insertion_orders extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('all_ios_model');
		$this->load->model('proposals_model');
		$this->load->model('campaign_health_model');
		$this->load->model('mpq_v2_model');
		$this->load->model('proposal_gen_model');
		$this->load->model('strategies_model');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->library('map');
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('mpq_v2_classes');
		
	}

	public function io($unique_display_id = false) 
	{
		$redirect_url = '';
		$redirect_url = $unique_display_id !== false ? '/io/'.$unique_display_id : '/insertion_orders';
		
		if(! $this->tank_auth->is_logged_in()){
		    $this->session->set_userdata('referer', $redirect_url);
		    redirect("login");
		    return;
		}
		$user_id = $this->tank_auth->get_user_id();
		$partner_id = $this->tank_auth->get_partner_id($user_id);
		$session_id = $this->session->userdata('session_id');
		if ($unique_display_id)
		{
			$mpq_id = $this->mpq_v2_model->get_mpq_id_by_unique_display_id($unique_display_id);
			$this->mpq_v2_model->unlock_mpq_session_by_session_id($session_id, $mpq_id);
			$this->mpq_v2_model->update_mpq_with_session_id($mpq_id, $session_id);
		}
		else 
		{
			if($partner_id == false)
			{
				$partner_id = 1; //DEFAULT
			}
			$this->mpq_v2_model->unlock_mpq_session_by_session_id($session_id);
			$mpq_id = $this->mpq_v2_model->initialize_mpq_session_from_scratch_for_io($session_id, $user_id, $partner_id);
			$unique_display_id = $this->mpq_v2_model->get_unique_display_id_by_mpq_id($mpq_id);
			redirect('io/'.$unique_display_id);
		}

		$data['title'] = 'IO';

		$this->vl_platform->show_views(
			$this,
			$data,
			'io',
			'insertion_order/io/body',
			'insertion_order/io/header',
			NULL,
			'insertion_order/io/footer',
			NULL,
			true,
			true
		);
	}

	public function create()
	{
		$session_id = $this->session->userdata('session_id');
		$this->mpq_v2_model->unlock_mpq_session_by_session_id($session_id);

		$mpq_id = $this->input->post('mpq_id');
		$unique_display_id = $this->mpq_v2_model->get_unique_display_id_by_mpq_id($mpq_id);
		$option_id = $this->input->post('option_id');
		$new_mpq_id = $this->mpq_v2_model->initialize_mpq_session_from_existing_for_io($session_id, $mpq_id, $unique_display_id, $option_id);
		$new_unique_display_id = $this->mpq_v2_model->get_unique_display_id_by_mpq_id($new_mpq_id);
		redirect('io/'.$new_unique_display_id);
	}

	public function get_io_data($unique_display_id = false) {
		$user_id = $this->tank_auth->get_user_id();
		$session_id = $this->session->userdata('session_id');

		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$is_super = $this->tank_auth->get_isGroupSuper($username);

		$data['session_id'] = $session_id;

		$data['is_preload'] = false;
		$data['industry_data'] = '{}';
		$data['iab_category_data'] = [];
		$data['flights_data'] = [];
		$data['creatives'] = [];
		$data['is_rfp'] = false;

		$get_initialize_data_flag = true;

		$option_id = false;
		$post_account_executive = false;
		$submission_method = false;
		$io_source = false;

		switch(strtolower($role))
		{
			case 'admin':
			case 'ops':
			case 'creative':
			case 'sales':
				$mpq_user_role = 'media_planner';
				break;
			case 'business':
				$mpq_user_role = 'advertiser';
				break;
			default:
				$mpq_user_role = 'advertiser';
				$is_success = false;
				$errors[] = 'Unknown user role: '.$role.' (#439217)';
		}

		$mpq_id = $this->mpq_v2_model->get_mpq_id_by_unique_display_id($unique_display_id);
		$mpq_session_data = $this->mpq_v2_model->get_mpq_summary_data($mpq_id);
		$products_data = $this->mpq_v2_model->get_product_information_for_io($mpq_id);
		$status_data = $this->mpq_v2_model->get_io_status_by_mpq_id($mpq_id);

		$creatives_data = $this->mpq_v2_model->get_creatives_defined_by_mpq_id($mpq_id);
		if($creatives_data !== false)
		{
			$data['creatives'] = $creatives_data;
		}

		$data['has_geofencing'] = false;
		$data['geofencing_data'] = [];
		$o_and_o_enabled_products = false;
		$data['o_and_o_dfp_enabled_products'] = false;
		
		foreach($products_data as $product)
		{
			if($product['o_o_enabled'] == '1')
			{
				$o_and_o_enabled_products = true;
			}

			if ($product['o_o_dfp'] == '1')
			{
				$data['o_and_o_dfp_enabled_products'] = true;
			}
			
			if(array_key_exists('has_geofencing', $product) && $product['has_geofencing'] && !$data['has_geofencing'])
			{
				$data['has_geofencing'] = true;
				$data['geofencing_data'] = $product['geofencing_data'];
			}
		}
		
		$data['o_and_o_enabled_products'] = $o_and_o_enabled_products;
		$data['mpq_id'] = $mpq_id;
		$data['io_product_data'] = $products_data;
		$data['mpq_type'] = $mpq_session_data['mpq_type'];
		$data['user_id'] = $user_id;
		$data['user_role'] = $role;
		$data['is_super'] = (bool) $is_super;
		$data['advertiser_org_name'] = $mpq_session_data['advertiser_name'];
		$data['source_table'] = $mpq_session_data['source_table'];
		$data['tracking_tag_file_id'] = $mpq_session_data['tracking_tag_file_id'];
		$data['tracking_tag_file_name'] = $this->tag_model->get_tracking_tag_file_name_by_id($data['tracking_tag_file_id']);
		$data['io_advertiser_id'] = $mpq_session_data['io_advertiser_id'];
		$data['include_retargeting'] = $mpq_session_data['include_retargeting'];
		$data['old_source_table'] = $data['source_table'];
		$data['old_tracking_tag_file_id'] = $data['tracking_tag_file_id'];
		$data['old_io_advertiser_id'] = $mpq_session_data['io_advertiser_id'];
		$data['unique_display_id'] = $mpq_session_data['unique_display_id'];
		if (isset($data['io_advertiser_id']) && isset($data['source_table']) && $data['source_table'] === 'Advertisers')
		{
			$data['io_advertiser_name'] = $this->mpq_v2_model->get_verified_advertiser_name_by_id($data['io_advertiser_id']);
		}
		elseif(isset($data['io_advertiser_id']) && isset($data['source_table']))
		{
			$data['io_advertiser_name'] = $this->mpq_v2_model->get_unverified_advertiser_name_by_id($data['io_advertiser_id']);
		}
		
		$data['advertiser_website'] = $mpq_session_data['advertiser_website'];
		$data['order_name'] = $mpq_session_data['order_name'];
		$data['order_id'] = $mpq_session_data['order_id'];
		$data['owner_id'] = $mpq_session_data['creator_user_id'];
		$data['owner_name'] = $mpq_session_data['submitter_name'];
		$data['owner_email'] = $mpq_session_data['submitter_email'];
		$data['allocation_method'] = 'per_pop';
		$data['notes'] = str_replace("<br/>", "\n", $mpq_session_data['notes']);

		if($post_account_executive !== false)
		{
			$account_executive_id = $post_account_executive;
			unset($_POST['new_account_executive']);
		}
		else if($mpq_session_data['owner_user_id'] !== null)
		{
			$account_executive_id = $mpq_session_data['owner_user_id'];
		}
		else
		{
			$account_executive_id = $user_id;
		}

		$original_owner_id = $mpq_session_data['original_owner_id'] ?: $user_id;
		$partner_id = $this->tank_auth->get_partner_id($original_owner_id);
		if($partner_id == false)
		{
			$partner_id = 1; //DEFAULT
		}

		$data['mpq_data'] = $mpq_session_data;
		$data['option'] = [];
		if ($mpq_session_data['parent_mpq_id'])
		{
			$data['option'] = $this->mpq_v2_model->get_mpq_options_by_mpq_id($mpq_id)[0];
		}

		$data['products'] = $this->strategies_model->get_products_by_strategy_id($mpq_session_data['strategy_id']);
		$data['products'] = array_filter($data['products'], function($product){
			return $product['can_become_campaign'];
		});

		foreach($data['products'] as &$product)
		{
			$product['definition'] = json_decode($product['definition'], true);
			$product['selected'] = false;

			if (array_key_exists($product['id'], $data['io_product_data']))
			{
				$product['selected'] = true;
				$product = array_merge($product, $data['io_product_data'][$product['id']]);
			}

			$product['budget_allocation'] = $this->mpq_v2_model->get_allocation_type_for_mpq_and_product($mpq_id, $product['id']) ?: 'per_pop';

			$product['flights'] = [];
			$product['total_flights'] = [];
			$product['cpms'] = [];
			$product_flights = $this->get_flights($mpq_id, $product['id']);
			if ($product_flights['is_success'])
			{
				$product['flights'] = $product_flights['flights'];
				$product['total_flights'] = $product_flights['total_flights'];
				$product['cpms'] = $product_flights['cpms'][0];
			}

			if ($mpq_session_data['parent_mpq_id'])
			{
				$submitted_products = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product['id'], $mpq_id);
				$total = 0;
				if ($submitted_products)
				{
					foreach ($submitted_products as $submitted_product)
					{
						$values = json_decode($submitted_product['submitted_values'], true);
						if (array_key_exists('cpm', $values))
						{
							$cpm = $values['cpm'];
						} else {
							$cpm = intval($product['definition']['options'][0]['cpm']['default']);
						}

						if (array_key_exists('geofence_unit', $values) && array_key_exists('geofence_cpm', $values)){
							$total += floatval($values['geofence_unit']) * $values['geofence_cpm'] / 1000;
							$total += ( floatval($values['unit']) - floatval($values['geofence_unit'])) * $cpm / 1000;
						} else {
							$total += floatval($values['unit']) * $cpm / 1000;
						}
					}
				}

				$total *= $data['option']['duration'];
				$total *= (1 - (intval($data['option']['discount']) / 100));

				$product['submitted_total'] = round($total);
			}

			$product['creatives'] = array_values(array_filter($data['creatives'], function($creative) use ($product) {
				return $creative['product_id'] == $product['id'];
			}));
		}

		$data['products'] = array_values($data['products']);

		$data['geo_radius'] = '';
		$data['geo_center'] = '';

		$industry_data = $this->mpq_v2_model->get_industry_data_by_id($mpq_session_data['industry_id']);
		if($industry_data !== false)
		{
            $data['industry_data'] = array("id" => $industry_data['freq_industry_tags_id'], "text" => $industry_data['name']);
		}

		$iab_category_data = $this->mpq_v2_model->get_iab_contextual_channels_by_mpq_id($mpq_id);
		if ($iab_category_data == false && !empty($mpq_session_data['parent_mpq_id']))
		{
			$iab_category_data = $this->mpq_v2_model->get_iab_contextual_channels_by_mpq_id($mpq_session_data['parent_mpq_id']);
		}

		if ($iab_category_data !== false)
		{
			$data['iab_category_data'] = $iab_category_data;
		}

		$data['custom_regions_data'] = array();

		$custom_regions_response = $this->mpq_v2_model->get_rfp_preload_custom_regions_by_mpq_id($mpq_id);
		if ($custom_regions_response == false && !empty($mpq_session_data['parent_mpq_id']))
		{
			$custom_regions_response = $this->mpq_v2_model->get_rfp_preload_custom_regions_by_mpq_id($mpq_session_data['parent_mpq_id']);
		}

		if($custom_regions_response !== false)
		{
			$data['custom_regions_data'] = $custom_regions_response;
		}
		
		
		// Add Order Ids per region
		$data['custom_regions_data'] = $this->get_order_ids_per_location($data['custom_regions_data'], $mpq_id);
		
		$region_data = json_decode($mpq_session_data['region_data'], true);
		$this->map->convert_old_flexigrid_format($region_data);

		$data['geofence_inventory'] = 0;
		array_walk($region_data, function(&$values){
			$values['geofences'] = [];
		});

		$geofences = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($mpq_id);
		if (!empty($geofences) && $data['geofencing_data'])
		{
			$allowed_keys = ['URBAN', 'SUBURBAN', 'RURAL'];
			$radius_options = [
				'CONQUESTING' => $data['geofencing_data']['radius']['CONQUESTING'],
				'PROXIMITY' => array_intersect_key($data['geofencing_data']['radius'], array_flip($allowed_keys)),
			];
			$data['geofencing_data']['radius'] = $radius_options;

			foreach($geofences as $geofence)
			{
				if (array_key_exists($geofence['location_id'], $region_data))
				{
					$region_data[$geofence['location_id']]['geofences'][] = array(
						'address' => $geofence['search_term'],
						'latlng' => $geofence['latlng'],
						'type' => $geofence['type'],
						'radius' => $geofence['radius'],
						'dropdown_options' => $data['geofencing_data']['dropdown_options'],
						'radius_options' => $radius_options,
						'zcta_type' => $geofence['zcta_type'],
					);
				}
			}
			$data['geofence_inventory'] = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id);

		}
		$data['existing_locations'] = $region_data;
		if (!empty($data['custom_regions_data']) && !empty($data['existing_locations']))
		{
			foreach($data['custom_regions_data'] as $region)
			{
				$data['existing_locations'][$region['location_id']]['custom_regions'][] = $region;
			}
		}

		$data['zips'] = $this->add_geographic_view_variables($data, $mpq_session_data['region_data']);
		$data['demographics'] = $this->add_demographics_view_variables($data, $mpq_session_data['demographic_data']);

		$data['max_locations_for_rfp'] = $this->config->item('max_locations_per_rfp');
		$data['title'] = 'Insertion Order';

		$io_submit_redirect_string = $this->vl_platform->get_access_permission_redirect('io_submit_button');
		$data['io_submit_allowed'] = false;
		if($io_submit_redirect_string == "")
		{
			$data['io_submit_allowed'] = true;
		}

        $this->output->set_output(json_encode($data));
	}

	public function index()
	{
		$io_status = !empty($this->input->get('status')) ? strtolower($this->input->get('status')) : '';
		$start_date = !empty($this->input->get('startdate')) ? strtolower($this->input->get('startdate')) : '';
		$partner_name = !empty($this->input->get('partner')) ? strtolower($this->input->get('partner')) : '';
		$created_date = !empty($this->input->get('createddate')) ? strtolower($this->input->get('createddate')) : '';

                $data = array('post_submission_message' => "");

		$active_feature_button_id = 'io';
		
		$redirect_string = $this->vl_platform->get_access_permission_redirect('io');
		
		if ($redirect_string == 'login')
		{
			$this->session->set_userdata('referer', '/insertion_orders');
			redirect($redirect_string);
		}
		else if($redirect_string !== '')
		{
			redirect($redirect_string);
		}

		$post_source = $this->input->post('source');
		$post_submission_method = $this->input->post('submission_method');
		if($post_source !== false and $post_submission_method !== false)
		{
			if($post_source == "io")
			{
				if($post_submission_method == "save")
				{
					$data['post_submission_message'] = "Insertion order saved successfully.";
				}
				else if($post_submission_method == "submit")
				{
					$data['post_submission_message'] = "Insertion order submitted successfully.";
				}else if($post_submission_method == "submit_for_review")
				{
					$data['post_submission_message'] = "Insertion order submitted for review successfully.";
				}
				else if($post_submission_method == "io_lock")
				{
					$data['post_submission_message'] = "Your insertion order session has expired.";
					
				}
			}
		}

		$user_id = $this->tank_auth->get_user_id();
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$is_super = $this->tank_auth->get_isGroupSuper($username);

		$data['title'] = 'Submitted Insertion Orders';

		$data['all_ios'] = $this->all_ios_model->get_submitted_ios($user_id, $role, $is_super, $io_status, $partner_name, $start_date, $created_date);
		$data['user_role'] = $role;
		$data['show_forecast_column'] = false;

		if($data['all_ios'] !== false)
		{
			$mpq_ids = array();
			
			foreach ($data['all_ios'] as &$insertion_order)
			{
				$mpq_ids[] = $insertion_order['id'];
				$insertion_order['creation_time'] = date('m/d/Y H:i:s', strtotime($insertion_order['creation_time']));
				$insertion_order['last_updated'] = date('m/d/Y H:i:s', strtotime($insertion_order['last_updated']));
				$insertion_order['is_locked'] = false;
				$insertion_order['is_demo_login'] = $this->session->userdata('is_demo_partner');
				
				if ($insertion_order['is_demo'] == 1)
				{
					$insertion_order['demo'] = 'demo';
				}
				else
				{
					$insertion_order['demo'] = 'nodemo';
				} 
				
				if ($insertion_order['io_lock_timestamp'] !== null and (strtotime("now") - strtotime($insertion_order['io_lock_timestamp']))/60 < 30)
				{
					$insertion_order['is_locked'] = true;
				}
				
				if (!isset($insertion_order['io_advertiser_id']) && $insertion_order['opportunity_status'] === '1' && $insertion_order['mpq_type'] !== 'io-submitted' ) 
				{
					$insertion_order['opportunity_status'] = '0';
				}

				if (is_null($insertion_order['io_advertiser_id']))
				{
					$insertion_order['io_advertiser_name'] = "<span class='muted'>[Not Defined]</span>";
				}				
			}
			
			$enabled_flags_ios = $this->mpq_v2_model->get_o_and_o_enabled_ios(implode(",", $mpq_ids));			 
			
 			if (isset($enabled_flags_ios) && count($enabled_flags_ios) > 0)
 			{
 				foreach ($data['all_ios'] as &$insertion_order)
 				{
 					if (isset($enabled_flags_ios['o_o_enable'][$insertion_order['id']]))
 					{
 						$insertion_order['o_o_enabled'] = "1";
 					}
 					else
 					{
 						$insertion_order['o_o_enabled'] = "0";
 					}
					
					if (isset($enabled_flags_ios['dfp_enable'][$insertion_order['id']]))
 					{
 						$insertion_order['dfp_enable'] = "1";
						$data['show_forecast_column'] = true; 
 					}
 					else
 					{
 						$insertion_order['dfp_enable'] = "0";
 					}
 				}
 			}
		}

		// If redirected, show message
		$data['io_locked'] = json_encode($this->session->userdata('io_locked'));
		$this->session->unset_userdata('io_locked');
		
		$data['can_launch'] = ($this->vl_platform->get_access_permission_redirect('launch_io') == '') ? 1 : 0;
		
		$io_submit_redirect_string = $this->vl_platform->get_access_permission_redirect('io_submit_button');
		$data['io_submit_allowed'] = false;
		if($io_submit_redirect_string == "")
		{
			$data['io_submit_allowed'] = true;
		}

		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'insertion_order/all_ios_view_html.php',
			'insertion_order/all_ios_view_header.php',
			NULL,
			'insertion_order/all_ios_view_footer.php',
			NULL
		);	
	}
	
	public function get_insertion_order_summary_html()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'html_data' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);
			if ($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				if ($mpq_id !== false)
				{
					$username = $this->tank_auth->get_username();
					$role = $this->tank_auth->get_role($username);
					$data = $this->proposals_model->get_insertion_order_summary_html_core($mpq_id, $role, 0);
					$io_submit_redirect_string = $this->vl_platform->get_access_permission_redirect('io_submit_button');
					$data['io_submit_allowed'] = false;
					if($io_submit_redirect_string == "")
					{
						$data['io_submit_allowed'] = true;
					}					
					$html_summary = $this->load->view('/generator/io_summary_html.php', $data, true);
					$return_array['html_data'] = $html_summary;
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 562341: unknown mpq id";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 562340: users logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function is_insertion_order_editable()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'is_editable_success' => "success");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if ($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				
				if ($mpq_id !== false)
				{
				    $io_editable_details = $this->mpq_v2_model->get_io_editable_details($mpq_id);
					if($io_editable_details !== false)
					{
						$is_locked = (($io_editable_details['io_lock_timestamp'] !== null) and ((strtotime("now") - strtotime($io_editable_details['io_lock_timestamp']))/60 < 30));
						if(($io_editable_details['is_submitted'] === 1 && $io_editable_details['mpq_type'] == "io-submitted"))
						{
							$return_array['is_editable_success'] = "submitted";
						}
						else if($is_locked)
						{
							$return_array['is_editable_success'] = "locked";
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 704500: server error when getting insertion order details";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 704400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 703403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}
	
	public function download_ad_tags($unique_display_id)
	{
		if (isset($unique_display_id) && $unique_display_id != null && $unique_display_id != "")
		{
			$mpq_id =  $this->mpq_v2_model->get_mpq_id_by_unique_display_id($unique_display_id);
			$data['ad_tags_result'] = $this->mpq_v2_model->get_ad_tags_by_mpq_id($mpq_id);	
			if ($data['ad_tags_result'])
			{
				$data['file_name'] = "ad_tags_".$unique_display_id.".txt";
				$this->load->view('rfp/download_ad_tags', $data);
			} 
		}
	}

	private function add_geographic_view_variables($data, $region_data_json, $location_id = 0)
	{
		$region_data = json_decode($region_data_json, true);
		$this->map->convert_old_flexigrid_format($region_data);
		$zips = array();
		if(!empty($region_data) && isset($region_data[$location_id]['ids']['zcta']))
		{
			$zips = $region_data[$location_id]['ids']['zcta'];
		}
		return array('zips' => $zips);
	}

	//	sets up 'data' variables for demographics section
	//	input: $raw_demographics_string looks like "1_1_1_1_1_1_1_1_1_0_0_0_1_1_1_1_0_1_1_1_1_1_1__All_Force include sites here..."
	//	return: nothing returned
	function add_demographics_view_variables($data, $raw_demographics_string)
	{
		$demo_array = explode("_", $raw_demographics_string);
		$demographics_settings = array(
			'gender_male' => $demo_array[0],
			'gender_female' => $demo_array[1],

			'age_under_18' => $demo_array[2],
			'age_18_to_24' => $demo_array[3],
			'age_25_to_34' => $demo_array[4],
			'age_35_to_44' => $demo_array[5],
			'age_45_to_54' => $demo_array[6],
			'age_55_to_64' => $demo_array[7],
			'age_over_65' => $demo_array[8],

			'income_under_50k' => $demo_array[9],
			'income_50k_to_100k' => $demo_array[10],
			'income_100k_to_150k' => $demo_array[11],
			'income_over_150k' => $demo_array[12],

			'education_no_college' => $demo_array[13],
			'education_college' => $demo_array[14],
			'education_grad_school' => $demo_array[15],

			'parent_no_kids' => $demo_array[16],
			'parent_has_kids' => $demo_array[17]
		);

		$data['demographics_settings'] = $demographics_settings;

		$demographic_elements = array(
			'gender_male' => new mpq_demographic_element_data('Male', 'gender_male', (bool) $demographics_settings['gender_male']),
			'gender_female' => new mpq_demographic_element_data('Female', 'gender_female', (bool) $demographics_settings['gender_female']),

			'age_under_18' => new mpq_demographic_element_data('Under 18', 'age_under_18', (bool) $demographics_settings['age_under_18']),
			'age_18_to_24' => new mpq_demographic_element_data('18 - 24', 'age_18_to_24', (bool) $demographics_settings['age_18_to_24']),
			'age_25_to_34' => new mpq_demographic_element_data('25 - 34', 'age_25_to_34', (bool) $demographics_settings['age_25_to_34']),
			'age_35_to_44' => new mpq_demographic_element_data('35 - 44', 'age_35_to_44', (bool) $demographics_settings['age_35_to_44']),
			'age_45_to_54' => new mpq_demographic_element_data('45 - 54', 'age_45_to_54', (bool) $demographics_settings['age_45_to_54']),
			'age_55_to_64' => new mpq_demographic_element_data('55 - 64', 'age_55_to_64', (bool) $demographics_settings['age_55_to_64']),
			'age_over_65' => new mpq_demographic_element_data('Over 65', 'age_over_65', (bool) $demographics_settings['age_over_65']),

			'income_under_50k' => new mpq_demographic_element_data('Under $50k', 'income_under_50k', (bool) $demographics_settings['income_under_50k']),
			'income_50k_to_100k' => new mpq_demographic_element_data('$50k-100k', 'income_50k_to_100k', (bool) $demographics_settings['income_50k_to_100k']),
			'income_100k_to_150k' => new mpq_demographic_element_data('$100k-150k', 'income_100k_to_150k', (bool) $demographics_settings['income_100k_to_150k']),
			'income_over_150k' => new mpq_demographic_element_data('Over $150k', 'income_over_150k', (bool) $demographics_settings['income_over_150k']),

			'parent_no_kids' => new mpq_demographic_element_data('No Kids', 'parent_no_kids', (bool) $demographics_settings['parent_no_kids']),
			'parent_has_kids' => new mpq_demographic_element_data('Has Kids', 'parent_has_kids', (bool) $demographics_settings['parent_has_kids']),

			'education_no_college' => new mpq_demographic_element_data('No College', 'education_no_college', (bool) $demographics_settings['education_no_college']),
			'education_college' => new mpq_demographic_element_data('College', 'education_college', (bool) $demographics_settings['education_college']),
			'education_grad_school' => new mpq_demographic_element_data('Grad School', 'education_grad_school', (bool) $demographics_settings['education_grad_school'])
		);
		$data['demographic_elements'] = $demographic_elements;

		$demographic_sections = array(
			new mpq_demographic_section(
				'Gender',
				array(
					$demographic_elements['gender_male'],
					$demographic_elements['gender_female']
				)
			),
			new mpq_demographic_section(
				'Age',
				array(
					$demographic_elements['age_under_18'],
					$demographic_elements['age_18_to_24'],
					$demographic_elements['age_25_to_34'],
					$demographic_elements['age_35_to_44'],
					$demographic_elements['age_45_to_54'],
					$demographic_elements['age_55_to_64'],
					$demographic_elements['age_over_65']
				)
			),
			new mpq_demographic_section(
				'Household Annual Income',
				array(
					$demographic_elements['income_under_50k'],
					$demographic_elements['income_50k_to_100k'],
					$demographic_elements['income_100k_to_150k'],
					$demographic_elements['income_over_150k']
				)
			),
			new mpq_demographic_section(
				'Education',
				array(
					$demographic_elements['education_no_college'],
					$demographic_elements['education_college'],
					$demographic_elements['education_grad_school']
				)
			),
			new mpq_demographic_section(
				'Parenting',
				array(
					$demographic_elements['parent_no_kids'],
					$demographic_elements['parent_has_kids']
				)
			)
		);
		return $demographic_sections;
	}

	private function get_flights($mpq_id, $product_id, $region_id = false)
	{
		$return_array = array('is_success' => true, 'errors' => "", 'cpms'=>null, 'flights' => array(), 'total_flights'=>array());

		$budget_allocation = $this->mpq_v2_model->get_allocation_type_for_mpq_and_product($mpq_id, $product_id);
		if($budget_allocation == false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 151554: failed to get budget allocation type";
			return $return_array;	
		}

		$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);

		if($submitted_product_response == false)
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 106442: Failed to retrieve submitted product information";
			return $return_array;				
		}
		$flight_sums = array();
		$product_cpms = array();
		foreach($submitted_product_response AS $submitted_product)
		{
			$submitted_product_id = $submitted_product['id'];
			$cpms[$submitted_product['region_data_index']] = $this->mpq_v2_model->retrieve_product_cpms_for_submitted_product($submitted_product['id']);

			$flights_response = $this->mpq_v2_model->get_flights_for_submitted_product($submitted_product_id);
			if($flights_response == false)
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 114721: Failed to retrieve submitted product flights";
				return $return_array;										
			}
			foreach($flights_response as &$flight_row)
			{
				$flight_row['region_index'] = $submitted_product['region_data_index'];
			}

			
			if($budget_allocation != "custom")
			{
				if(empty($product_cpms))
				{
					$product_cpms = $cpms;
				}
				if(empty($flight_sums))
				{
					$flight_sums = $flights_response;
				}
				else
				{
					foreach($flights_response AS $idx => $flight)
					{
						foreach($flight as $key => $budget_value)
						{	

							switch($key)
							{
								case "id":
									if(!is_array($flight_sums[$idx]["id"]))
									{
										$flight_sums[$idx]["id"] = array($flight_sums[$idx]["id"]);
									}
									$flight_sums[$idx][$key][] = $budget_value;
									break;
								case "start_date":
									break;
								case "end_date":
									break;
								case "dfp_status":
									if($budget_value !== "COMPLETE")
									{
										$flight_sums[$idx][$key] = $budget_value;
									}								
									break;
								default:
									$flight_sums[$idx][$key] += $budget_value;
									break;
							}

						}				
					}
				}
			}		
			else
			{
				$return_array['cpms'] = $cpms;
			}
			$return_array['cpms'] = $cpms;
			$return_array['flights'][] = $flights_response;
		}
		if($budget_allocation != "custom")
		{
			$return_array['total_flights'] = $flight_sums;
		}

		return $return_array;
	}
	
	public function check_o_and_o_forecast_status()
	{
		$result = array(
		    "o_and_o_forecast_status" => false		    
		);
		
		if ($this->tank_auth->is_logged_in())
		{
			$mpq_id = $this->input->post("mpq_id");
			$o_and_o_enabled = $this->input->post("o_and_o_enabled");
			$o_and_o_forecast_status = true;
			
			if ($o_and_o_enabled)
			{
				$unsuccessful_forecast_details = $this->mpq_v2_model->get_unsuccessful_forecast_details($mpq_id);
				$stop_ping = true;
				
				foreach($unsuccessful_forecast_details AS $unsuccessful_forecast_row)
				{
					if($unsuccessful_forecast_row['dfp_status'] != 'FAILED')
					{
						$stop_ping = false;
						break;
					}					
				}

				if (count($unsuccessful_forecast_details) > 0)
				{
					$o_and_o_forecast_status = false;
				}
			}
			
			$result["o_and_o_forecast_status"] = $o_and_o_forecast_status;
			$result["stop_ping"] = $stop_ping;
		}
		
		echo json_encode($result);
	}
	
	public function get_order_ids_per_location($custom_regions, $mpq_id)
	{
		$order_ids = array();
		$test = $this->mpq_v2_model->get_rfp_preload_custom_regions_by_mpq_id($mpq_id);
		//echo '<pre>'; print_r($test);
		$cp_submitted_products = $this->mpq_v2_model->get_submitted_products_by_mpq_id($mpq_id);
		//echo '<pre>'; print_r($cp_submitted_products);
		$i = 0;
		
		foreach($cp_submitted_products as $product)
		{
			$o_o_ids = $this->mpq_v2_model->get_o_o_data_for_mpq_product_region($mpq_id,$product['product_id'],$product['region_data_index']);
			
			if (isset($o_o_ids[0]['o_o_ids']))
			{
				$o_o_id = $o_o_ids[0]['o_o_ids'];			
				$order_ids[$product['region_data_index']]['o_o_ids'] = isset($order_ids[$product['region_data_index']]['o_o_ids']) ? $order_ids[$product['region_data_index']]['o_o_ids'].$o_o_id : $o_o_id;
			}
		}
		
		/*
		 * Order ids are added based on comparing location id with region index
		 */
		foreach($custom_regions as &$custom_region)
		{
			if (isset($order_ids[$custom_region['location_id']]['o_o_ids']))
			{
				$order_id = $order_ids[$custom_region['location_id']]['o_o_ids'];
				$order_id = explode(';',$order_id);
				$order_id = array_map('trim', $order_id);
				$order_id = count($order_id) > 0 ? $order_id : NULL;
				$custom_region['o_o_ids'] = $order_id;
			}
			else
			{
				$custom_region['o_o_ids'] = array();
			}
		}
		
		return $custom_regions;
		
	}
	
	public function save_io_o_o_ids()
	{
		// Save o o ids
		$region_id = $this->input->post('region_id');
		$o_o_ids = $this->input->post('o_o_id');
		$mpq_id = $this->input->post('mpq_id');
		$product_id = $this->input->post('product_id');
		$result = array('success' => false, 'msg' => 'Failed to Update o_o_ids against product');
		$cp_submitted_product_id = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
			
		if(isset($cp_submitted_product_id) && isset($o_o_ids))
		{
		    	$save_o_o_status = $this->mpq_v2_model->save_o_o_ids_for_submitted_product($cp_submitted_product_id[0]['id'], $o_o_ids);
			
			if($save_o_o_status)
			{
				$result['success'] = true;
				$result['msg'] = 'O_O ids are updates successfully';
				return $result;
			}
		}
		echo json_encode($result);
	}
}
