<?php

class RFP extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('mpq_v2_classes');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->library('map');
		$this->load->model('mpq_v2_model');
		$this->load->helper('url');
		$this->load->model('vl_auth_model');
		$this->load->model('proposal_gen_model');
		$this->load->model('proposals_model');
		$this->load->model('strategies_model');
		$this->load->model('vl_platform_model');
		$this->load->helper('select2_helper');
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('multi_lap_helper');
		$this->load->helper('strata_helper');
	}

	public function index()
	{
		// Login stuff
		$this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposals', 'rfp');

		preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
		if(count($matches) > 1)
		{
			$version = $matches[1];
			if($version <= 9)
			{
				$this->load->view('mpq_v2/old_browser_redirect');
				return;
			}
		}

		$data = array(
			'title' => 'RFP',
			'google_access_token_rfp_io' => $this->config->item('google_access_token_rfp_io'),
			'allowed_monthly_impressions_to_population_ratio' => $this->config->item('allowed_monthly_impressions_to_population_ratio'),
		);
		$this->vl_platform->show_views(
			$this,
			$data,
			'proposals',
			'builder/body',
			'builder/header',
			NULL,
			'builder/footer',
			NULL,
			TRUE,
			TRUE
		);
	}

	public function redirect($unique_display_id = false) {
		if ($unique_display_id) {
			redirect("/rfp/form/$unique_display_id");
		}
		else
		{
			redirect("/rfp/gate");
		}
	}

	public function get_proposal_data($unique_display_id)
	{
		$user_id = $this->tank_auth->get_user_id();
		$session_id = $this->session->userdata('session_id');

		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$is_super = $this->tank_auth->get_isGroupSuper($username);

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

		$mpq_session = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id, $unique_display_id, false, true);
		$parent_proposal_id = $this->mpq_v2_model->get_parent_proposal_id_by_unique_display_id($unique_display_id);
		$existing_proposal_id = $this->mpq_v2_model->get_proposal_id_by_unique_display_id($unique_display_id) ?: $parent_proposal_id;

		if (empty($mpq_session)) // The MPQ session doesn't exist because we've come straight from /proposals
		{
			// this also unlocks the MPQ session from user's session_id
			$this->mpq_v2_model->initialize_mpq_session_from_existing_proposal($session_id, $user_id, $existing_proposal_id, 'edit');
			$mpq_session = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
		}

		// Set up default data
		$data['industry_data'] = '{}';
		$data['iab_category_data'] = array();
		$data['rfp_keywords_data'] = array();
		$data['rooftops_data'] = array();
		$data['rfp_tv_zones_data'] = array();
		$data['is_rfp'] = true;
		$data['is_preload'] = true;
		$data['current_user_id'] = $user_id;
		$data['unique_display_id'] = $unique_display_id;
		$data['cc_owner'] = $mpq_session['cc_owner'];
		// Geo Data
		$data['is_existing_rfp'] = false;
		$data['existing_locations'] = array();
		$data['custom_regions_data'] = array();
		$data['options'] = array();
		$data['max_locations_for_rfp'] = $this->config->item('max_locations_per_rfp');
		$data['geo_radius'] = '';
		$data['geo_center'] = '';

		$custom_regions_response = $this->mpq_v2_model->get_rfp_preload_custom_regions_by_mpq_id($mpq_session['id']);
		$data['proposal_status'] = $this->proposals_model->get_proposal_completion_status($mpq_session['id']);

		if($custom_regions_response !== false)
		{
			$data['custom_regions_data'] = $custom_regions_response;
		}

		$region_data = json_decode($mpq_session['region_data'], true);
		$this->map->convert_old_flexigrid_format($region_data);
		$data['existing_locations'] = $region_data;
		$location_population_response = $this->mpq_v2_model->get_location_populations_from_db(array_column($region_data, 'ids'));

		if (!empty($data['custom_regions_data']) && !empty($data['existing_locations']))
		{
			foreach($data['custom_regions_data'] as $region)
			{
				$data['existing_locations'][$region['location_id']]['custom_regions'][] = $region;
			}
		}

		//geofencing data
		$data['geofence_inventory'] = [0];
		foreach($data['existing_locations'] as $i => &$location)
		{
			$location['geofences'] = array();
			$location['location_population'] = intval($location_population_response[$i]);
		}
		$geofences = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($mpq_session['id']);
		if (!empty($geofences))
		{
			foreach($geofences as $geofence)
			{
				if (array_key_exists($geofence['location_id'], $data['existing_locations']))
				{
					$data['existing_locations'][$geofence['location_id']]['geofences'][] = array(
						'address' => $geofence['search_term'],
						'latlng' => $geofence['latlng'],
						'type' => $geofence['type'],
						'proximity_radius' => $geofence['radius']
					);
				}
			}
			$data['geofence_inventory'] = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_session['id'], null, true);
		}

		if ($existing_proposal_id)
		{
			$preload_data_response = $this->proposal_gen_model->get_preload_rfp_data_by_proposal_id($existing_proposal_id);
			if(!empty($preload_data_response))
			{
				$rfp_zones_data = $this->mpq_v2_model->get_rfp_tv_zones_by_mpq_id($preload_data_response['id']);

				$this->mpq_v2_model->copy_scx_data($preload_data_response['id'], $mpq_session['id']);

				if (empty($geofences))
				{
					$this->mpq_v2_model->copy_geofences_from_original_mpq($mpq_session['id'], $preload_data_response['id']);
					$geofences = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($mpq_session['id']);
					foreach($geofences as $geofence)
					{
						if (array_key_exists($geofence['location_id'], $data['existing_locations']))
						{
							$data['existing_locations'][$geofence['location_id']]['geofences'][] = array(
								'address' => $geofence['search_term'],
								'latlng' => $geofence['latlng'],
								'type' => $geofence['type'],
								'proximity_radius' => $geofence['radius']
							);
						}
					}
					$data['geofence_inventory'] = $this->mpq_v2_model->calculate_geofencing_max_inventory($preload_data_response['id'], null, true);
				}

				if(count($rfp_zones_data) > 0)
				{
					$data['rfp_tv_zones_data'] = $rfp_zones_data;
				}


				$data['options'] = $this->mpq_v2_model->get_mpq_options_by_mpq_id($preload_data_response['id']);
				foreach ($data['options'] as &$option)
				{
					$option['selected'] = true;
				}

				$data['is_existing_rfp'] = true;

				if($preload_data_response['rooftops_data'] == null)
				{
					$data['rooftops_data'] = array();
				}
				else
				{
					$data['rooftops_data'] = json_decode($preload_data_response['rooftops_data'], true);
				}
				$rfp_keywords_data = $this->mpq_v2_model->get_rfp_keywords_data($preload_data_response['id']);
				if(!empty($rfp_keywords_data))
				{
					$data['rfp_keywords_data'] = json_decode($rfp_keywords_data['search_terms'], true);
					$data['rfp_keywords_clicks'] = $rfp_keywords_data['clicks'];
				}
			}

			$data['is_preload'] = true;
			$data['iab_category_data'] = $this->mpq_v2_model->get_iab_category_data_by_existing_proposal_id($existing_proposal_id);
		}

		$data['advertiser_name'] = $mpq_session['advertiser_name'];
		$data['advertiser_website'] = $mpq_session['advertiser_website'];
		$data['name'] = $mpq_session['proposal_name'];
		$data['submitter_name'] = $mpq_session['submitter_name'];
		$data['submitter_email'] = $mpq_session['submitter_email'];
		$data['strategy_id'] = $mpq_session['strategy_id'];
        $data['presentation_date'] = $mpq_session['presentation_date'];
		$account_executive_id = $mpq_session['owner_user_id'] ?: $user_id;

		$partner_id = $this->tank_auth->get_partner_id($account_executive_id) ?: 1;


		// Get product data
		$data['products'] = $this->strategies_model->get_products_by_strategy_id($mpq_session['strategy_id']);
		if($existing_proposal_id)
		{
			$data['products'] = $this->mpq_v2_model->add_submitted_data_to_products($data['products'], $existing_proposal_id);
		}

		$data['raw_discount'] = 10;
		$data['raw_discount_name'] = "Discount";
		$data['term_duration'] = 6;
		$data['has_political'] = false;

		// Get SCX Upload data
		$tv_scx_data = $this->mpq_v2_model->get_rfp_scx_upload_data($mpq_session['id']);
		if ($tv_scx_data)
		{
			$data['tv_scx_data'] = format_tv_schedule($tv_scx_data['data']);
			if ($tv_scx_data['selected_networks'] !== null)
			{
				$selected_networks = json_decode($tv_scx_data['selected_networks'], true);

				foreach($data['tv_scx_data']['networks'] as &$network)
				{
					$network['selected'] = array_search($network['name'], $selected_networks) !== false;
				}
			}
		} else {
			$data['tv_scx_data'] = false;
		}

		foreach($data['products'] as $i => &$product)
		{
			if ($product['is_political'])
			{
				$data['has_political'] = true;
			}
			if($product['product_type'] == 'discount')
			{
				if(!is_array($product["definition"]))
					$product["definition"] = json_decode($product["definition"], true);

				$data['raw_discount'] = $product['definition']['discount_percent'];
				if(!empty($product['definition']['discount_name']))
				{
					$data['raw_discount_name'] = $product['definition']['discount_name'];
				}
			}
		}

		$data['political_segment_data'] = $this->mpq_v2_model->get_political_segment_data($existing_proposal_id) ?: $this->mpq_v2_model->get_political_segment_data();

		$industry_data = $this->mpq_v2_model->get_industry_data_by_id($mpq_session['industry_id']);
		if($industry_data !== false)
		{
			$industry_data_obj = array("id" => $industry_data['freq_industry_tags_id'], "text" => $industry_data['name']);
			$data['industry_data'] = $industry_data_obj;
			$data['industry_name'] = $industry_data['name'];
			$data['industry_id'] = $industry_data['freq_industry_tags_id'];
		}

		//Get Strategies
		$data['strategies'] = $this->strategies_model->get_strategy_info($partner_id, $mpq_session['industry_id']);

		$account_executive_data = $this->mpq_v2_model->get_account_executive_data_by_user_id($account_executive_id);
		if($account_executive_data !== false)
		{
			$data['account_executive_data'] = $account_executive_data;
		}
		$data['owner_is_submitter'] = isset($data['account_executive_data']) && $data['account_executive_data']['id'] === $data['current_user_id'];

		$data['mpq_id'] = $mpq_session['id'];
		$data['unique_display_id'] = $mpq_session['unique_display_id'];

		//custom geos (regions) are always enabled now for all users, no need to call a function to check for it.
		$data['custom_geos_enabled'] = true;

		$data['zips'] = $this->add_geographic_view_variables($data, $mpq_session['region_data']);
		$data['demographics'] = $this->add_demographics_view_variables($data, $mpq_session['demographic_data']);

		$data['user_data'] = array('user_id' => $user_id, 'role' => $role, 'is_super' => $is_super);

		$data['user_role'] = $mpq_user_role;
		$data['is_logged_in'] = true;

		$data['creative_requester'] = isset($creative_requester) ? array('id'=>$creative_requester->id, 'email'=>$creative_requester->email) : FALSE;

		$this->output->set_output(json_encode($data));
	}

	public function copy($unique_display_id = false)
	{
		$this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposals', 'rfp');

		if ($unique_display_id === false)
		{
			redirect('proposals');
		}

		$user_id = $this->tank_auth->get_user_id();
		$session_id = $this->session->userdata('session_id');

		$existing_proposal_id = $this->mpq_v2_model->get_proposal_id_by_unique_display_id($unique_display_id);
		$mpq_id = $this->mpq_v2_model->initialize_mpq_session_from_existing_proposal($session_id, $user_id, $existing_proposal_id, 'copy');
		$unique_display_id = $this->mpq_v2_model->get_unique_display_id_by_mpq_id($mpq_id);

		redirect('rfp/gate/'.$unique_display_id);
	}

	public function success($unique_display_id = false)
	{
		if($unique_display_id)
		{
			$proposal_id = $this->mpq_v2_model->get_proposal_id_by_unique_display_id($unique_display_id);
			$data = array();
			$data['title'] = 'RFP';
			$data['proposal'] = $this->mpq_v2_model->get_proposal_with_mpq($proposal_id);
			$data['creator_user'] = $this->users->get_user_by_id($data['proposal']['creator_user_id'], true);

			$this->vl_platform->show_views(
				$this,
				$data,
				'proposals',
				'rfp/submission_summary',
				'rfp/rfp_header',
				NULL,
				NULL,
				NULL
				// , false
			);
			return;
		}
		else
		{
			redirect('proposals');
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
	private function add_demographics_view_variables($data, $raw_demographics_string)
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

	public function createUpdateRFP($unique_display_id = false){

		// Login stuff - should change it a bit to send back the response to front end
		$this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposals', 'rfp');

		$data = array();
		$data['is_rfp'] = true;
		$data['title'] = 'RFP';

		$user_id = $this->tank_auth->get_user_id();
		$session_id = $this->session->userdata('session_id');

		$owner_id = $this->input->post('owner_id');
		$advertiser_name = $this->input->post('advertiser_name');
		$advertiser_website = $this->input->post('advertiser_website');
		$industry_id = $this->input->post('industry_id');
		$strategy_id = $this->input->post('strategy_id');
		$proposal_name = $this->input->post('proposal_name');
        $presentation_date = $this->input->post('presentation_date');
		$proposal_status = $this->input->post('status');

		if($unique_display_id)
		{
			// Load the proposal being either edited or cloned
			$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id, $unique_display_id, false, true);
			$parent_proposal_id = $this->mpq_v2_model->get_parent_proposal_id_by_unique_display_id($unique_display_id);
			$existing_proposal_id = $this->mpq_v2_model->get_proposal_id_by_unique_display_id($unique_display_id) ?: $parent_proposal_id;
			if (!$mpq_session_data)
			{
				$mpq_id = $this->mpq_v2_model->initialize_mpq_session_from_existing_proposal($session_id, $user_id, $existing_proposal_id, 'edit');
			}
			else
			{
				$mpq_id = $mpq_session_data['id'];
			}
			$this->mpq_v2_model->save_rfp_gate_data(
				$mpq_id,
				$user_id,
				$owner_id,
				$advertiser_name,
				$advertiser_website,
				$industry_id,
				$strategy_id,
				$proposal_name,
                $presentation_date
			);
			if($strategy_id !== $mpq_session_data['strategy_id'])
			{
				$proposal_id = $this->proposals_model->get_proposal_id_by_mpq_id($mpq_id);
				$this->proposals_model->delete_proposal_pages($proposal_id);
			}
		}
		else
		{	//New RFP
			$unique_display_id = $this->mpq_v2_model->generate_insertion_order_unique_id();
			$mpq_id = $this->mpq_v2_model->initialize_mpq_session_for_rfp(
				$session_id,
				$user_id,
				$unique_display_id,
				$owner_id,
				$advertiser_name,
				$advertiser_website,
				$industry_id,
				$strategy_id,
				$proposal_name,
                $presentation_date
			);

		}
		if($proposal_status === false)
		{
			$proposal_status = array(
				'is_gate_cleared' => true,
				'is_targets_cleared' => false,
				'is_budget_cleared' => false,
				'is_builder_cleared' => false
				);
		}
		else
		{
			$proposal_status['is_gate_cleared'] = true;
		}
		$set_completion_status_response = $this->proposals_model->set_proposal_completion_status($mpq_id, $proposal_status);

			$this -> get_proposal_data($unique_display_id);
	}

	public function get_center_geofence_type()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
		$post_variables = array('point_center');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			return;
		}
		else
		{
			$return_array = ['is_success' => true];
			$point_center = $response['post']['point_center'];
			if($point_center)
			{
				$latlng = [
					'latitude' => floatval($point_center[0]),
					'longitude' => floatval($point_center[1])
				];
				$geofencing_regions_data_result = $this->map->zcta_and_type_from_center($latlng);
				$return_array['point_info'] = $geofencing_regions_data_result;
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = ['Data improperly formatted'];
			}
			echo json_encode($return_array);
		}
	}

	public function handle_geofencing_ajax()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
		$post_variables = array('mpq_id', 'location_id', 'geofences');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			return;
		}
		else
		{
			$return_array = ['is_success' => true];
			$geofencing_data = $response['post']['geofences'];
			if ($geofencing_data == 'false') $geofencing_data = [];
			$mpq_id = intval($response['post']['mpq_id']);
			$location_id = intval($response['post']['location_id']);
			$affected_regions = $this->map->get_zips_affected_by_geofencing($geofencing_data);
			$this->mpq_v2_model->add_geofencing_regions_to_db($mpq_id, $location_id, $geofencing_data, $affected_regions);
			if (count($affected_regions) > 0)
			{
				$affected_regions = array_values(array_unique(array_filter(call_user_func_array('array_merge', $affected_regions))));
				$missing_geofence_regions = $this->mpq_v2_model->insert_missing_geofence_regions_if_needed($mpq_id, $location_id, $affected_regions);
			}
			else
			{
				$missing_geofence_regions = [];
			}
			$geofence_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id, $location_id);

			$return_array['geofence_inventory'] = $geofence_inventory;
			$return_array['affected_regions'] = $affected_regions;
			$return_array['missing_geofence_regions'] = $missing_geofence_regions;
			$return_array['location_id'] = $location_id;
			$return_array['location_population'] = 0;

			$region_data = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id);
			if($region_data && $region_data = json_decode($region_data, true))
			{
				if($region_data[$location_id]['ids'] && $region_data[$location_id]['ids']['zcta'])
				{
					$total_regions_for_location = $region_data[$location_id]['ids']['zcta'];
					$demographics = $this->map->get_demographics_from_region_array(['zcta' => $total_regions_for_location]);
					$return_array['location_population'] = intval($demographics['region_population']);
				}
			}
			echo json_encode($return_array);
		}
	}

	public function get_current_user_data()
	{
		$user_id = $this->tank_auth->get_user_id();
		$session_id = $this->session->userdata('session_id');

		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$is_super = $this->tank_auth->get_isGroupSuper($username);
		$industry_id = false;

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

		$current_user_data = $this -> mpq_v2_model -> get_mpq_user_profile($user_id);
		$strategies = $this->strategies_model->get_strategy_info($current_user_data["partner_id"], $industry_id);

		if($current_user_data)
			$data['current_user'] = $current_user_data;

		$data['strategies'] = array();
		if($strategies)
			$data['strategies'] = $strategies;

		$data['user_role'] = $mpq_user_role;
		$data['is_logged_in'] = true;

		$this->output->set_output(json_encode($data));
	}

	public function process_scx_upload() {
		if (isset($_FILES['file']))
		{
			if ($_FILES['file']['type'] !== 'application/octet-stream' || substr($_FILES['file']['name'], -4) !== '.scx')
			{
				$this->output->set_status_header(415);
				$this->output->set_output(json_encode(array('error' => 'Only .SCX files are allowed.')));
				return;
			}
			$mpq_id = $this->input->post('mpq_id');
			$unique_display_id = $this->input->post('unique_display_id');
			$data = format_tv_schedule($_FILES['file']['tmp_name']);

			$session_id = $this->session->userdata('session_id');
			$ignore_session_id = false;
			if($mpq_id !== false && $unique_display_id !== false)
			{
				$ignore_session_id = true;
			}
			$mpq_session = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id, $unique_display_id, $mpq_id, $ignore_session_id);

			$this->mpq_v2_model->save_rfp_scx_upload(file_get_contents($_FILES['file']['tmp_name']), $mpq_session['id']);

			$this->output->set_status_header(200);
			$this->output->set_output(json_encode($data));
		}
	}

	public function get_user_permissions(){
        $data = array();
        $data['featurePermissions'] = $this->vl_platform -> get_list_of_user_permission_html_ids_for_user();
        $this->output->set_output(json_encode($data));
        $this->output->set_status_header(200);
    }
}
