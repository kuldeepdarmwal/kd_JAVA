
<?php

require FCPATH.'/vendor/autoload.php';

class Mpq_v2 extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('mpq_v2_classes');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->library('email');
		$this->load->library('ftp');
		$this->load->library('map');
		$this->load->library('google_api');
		$this->load->model('mpq_v2_model');
		$this->load->helper('url');
		$this->load->model('vl_auth_model');
		$this->load->model('lap_lite_model');
		$this->load->model('proposal_gen_model');
		$this->load->model('proposals_model');
		$this->load->model('banner_intake_model');
		$this->load->model('strategies_model');
		$this->load->model('vl_platform_model');
		$this->load->model('tag_model');
		$this->load->model('al_model');
		$this->load->model('fas_model');
		$this->load->helper('select2_helper');
		$this->load->helper('mailgun');
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('multi_lap_helper');
		$this->load->helper('tradedesk_helper');
		$this->load->helper('budget_calculation_helper');
	}

	private function add_geographic_view_variables(&$data, $region_data_json, $location_id = 0)
	{
		$region_data = json_decode($region_data_json, true);
		$this->map->convert_old_flexigrid_format($region_data);
		$zips = array();
		if(!empty($region_data) && isset($region_data[$location_id]['ids']['zcta']))
		{
			$zips = $region_data[$location_id]['ids']['zcta'];
		}
		$data['geographics_section_data'] = array('zips' => $zips);
	}

	//	sets up 'data' variables for demographics section
	//	input: $raw_demographics_string looks like "1_1_1_1_1_1_1_1_1_0_0_0_1_1_1_1_0_1_1_1_1_1_1__All_Force include sites here..."
	//	return: nothing returned
	private function add_demographics_view_variables(&$data, $raw_demographics_string)
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
			'gender_male' => new mpq_demographic_element_data('Male', 'gender_male', $demographics_settings['gender_male']),
			'gender_female' => new mpq_demographic_element_data('Female', 'gender_female', $demographics_settings['gender_female']),

			'age_under_18' => new mpq_demographic_element_data('Under 18', 'age_under_18', $demographics_settings['age_under_18']),
			'age_18_to_24' => new mpq_demographic_element_data('18 - 24', 'age_18_to_24', $demographics_settings['age_18_to_24']),
			'age_25_to_34' => new mpq_demographic_element_data('25 - 34', 'age_25_to_34', $demographics_settings['age_25_to_34']),
			'age_35_to_44' => new mpq_demographic_element_data('35 - 44', 'age_35_to_44', $demographics_settings['age_35_to_44']),
			'age_45_to_54' => new mpq_demographic_element_data('45 - 54', 'age_45_to_54', $demographics_settings['age_45_to_54']),
			'age_55_to_64' => new mpq_demographic_element_data('55 - 64', 'age_55_to_64', $demographics_settings['age_55_to_64']),
			'age_over_65' => new mpq_demographic_element_data('Over 65', 'age_over_65', $demographics_settings['age_over_65']),

			'income_under_50k' => new mpq_demographic_element_data('Under $50k', 'income_under_50k', $demographics_settings['income_under_50k']),
			'income_50k_to_100k' => new mpq_demographic_element_data('$50k-100k', 'income_50k_to_100k', $demographics_settings['income_50k_to_100k']),
			'income_100k_to_150k' => new mpq_demographic_element_data('$100k-150k', 'income_100k_to_150k', $demographics_settings['income_100k_to_150k']),
			'income_over_150k' => new mpq_demographic_element_data('Over $150k', 'income_over_150k', $demographics_settings['income_over_150k']),

			'parent_no_kids' => new mpq_demographic_element_data('No Kids', 'parent_no_kids', $demographics_settings['parent_no_kids']),
			'parent_has_kids' => new mpq_demographic_element_data('Has Kids', 'parent_has_kids', $demographics_settings['parent_has_kids']),

			'education_no_college' => new mpq_demographic_element_data('No College', 'education_no_college', $demographics_settings['education_no_college']),
			'education_college' => new mpq_demographic_element_data('College', 'education_college', $demographics_settings['education_college']),
			'education_grad_school' => new mpq_demographic_element_data('Grad School', 'education_grad_school', $demographics_settings['education_grad_school'])
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
				'Parenting',
				array(
					$demographic_elements['parent_no_kids'],
					$demographic_elements['parent_has_kids']
				)
			),
			new mpq_demographic_section(
				'Education',
				array(
					$demographic_elements['education_no_college'],
					$demographic_elements['education_college'],
					$demographic_elements['education_grad_school']
				)
			)
		);
		$data['demographic_sections'] = $demographic_sections;
	}
	//	sets up 'data' variables for channels section
	//	input: $raw_channels_string looks like "Games|Health|Hobbies & Leisure"
	//	return: nothing returned
  private function add_channels_view_variables(&$data, $raw_channels_string)
	{
		$channel_options = array();
		$all_channels = $this->mpq_v2_model->get_all_iab_contextual_channels();
		if($all_channels)
		{
			$first_element = true;
			//$selected_channels = explode('|', $raw_channels_string); //FOR FUTURE: code for handling already selected channels from the session here.
			foreach($all_channels as $v)
			{
				$channel_name = $v['tag_copy'];
				$visible_string = $channel_name;

				$channel_data = new mpq_channel_data(
					$visible_string,
					$channel_name,
					false
					);
				$channel_options[] = $channel_data;
			}
			$channel_sections = array();
			$channel_sections[] = new mpq_channel_section($channel_options);
			$data['channel_sections'] = $channel_sections;
		}
		else
		{
			$data['channel_sections'] = false;
		}
	}

	private function add_industries_view_variables(&$data, $raw_industry_string)
	{
		$industry_options = array();
		$all_industries = $this->mpq_v2_model->get_all_industries();
		if($all_industries)
		{
			$first_element = true;
			//$selected_channels = explode('|', $raw_channels_string); //FOR FUTURE: code for handling already selected channels from the session here.
			foreach($all_industries as $v)
			{
				$industry_name = $v['freq_industry_tags_id'];
				$visible_string = $v['name'];

				$industry_data = new mpq_industry_data(
					$visible_string,
					$industry_name,
					false
					);
				$industry_options[] = $industry_data;
			}
			$industry_sections = array();
			$industry_sections[] = new mpq_industry_section($industry_options);
			$data['industry_sections'] = $industry_sections;
		}
		else
		{
			$data['industry_sections'] = false;
		}
	}

	//	calculates option row data
	//	return: a mpq_budget_option_row object with completed calculations
	private function create_option_row_data(
		$amount,
		$term,
		$duration,
		$type
	)
	{
		$option = new mpq_budget_option_get();
		$option->amount = $amount;
		$option->term = $term;
		$option->duration = $duration;
		$option->type = $type;

		$option_row = $this->get_option_row_data_from_raw_data($option);
		return $option_row;
	}

	//	calculates option row data from mpq_budget_option_get object
	//	return: a mpq_budget_option_row object with completed calculations
	private function get_option_row_data_from_raw_data(mpq_budget_option_get $raw_data)
	{
		$placeholder_cpm = 0;
		$placeholder_discount = 0;
		$summary_string = 0;
		if($raw_data->amount == '')
		{
			//no value for option
		}
		else
		{
			$this->calculate_option_data_for_mpq(
				$placeholder_cpm,
				$placeholder_discount,
				$summary_string,

				$raw_data->type,
				$raw_data->amount,
				$raw_data->term,
				$raw_data->duration,
				$raw_data->cpm,
				$raw_data->discount
				);
		}
		$row_data = new mpq_budget_option_row(
			$raw_data->amount,
			$raw_data->term,
			$raw_data->duration,
			$raw_data->type,
			$raw_data->cpm,
			$raw_data->discount,
			$placeholder_cpm,
			$placeholder_discount,
			$summary_string
		);

		return $row_data;
	}

	// setup 'data' for options view
	// return: nothing
  private function add_options_view_variables(&$data, $options_data)
	{
		$dollar_budget_ranges = array();
		$dollar_budget_ranges[] = new mpq_options_budget_range(
			'Select Budget Range', array());
		$dollar_budget_ranges[] = new mpq_options_budget_range(
			'$1,000 - $3,000 per month',
			array(
				$this->create_option_row_data(1200, 'monthly', 6, 'dollar'),
				$this->create_option_row_data(2400, 'monthly', 6, 'dollar'),
				$this->create_option_row_data(3500, 'monthly', 6, 'dollar')
			)
		);
		$dollar_budget_ranges[] = new mpq_options_budget_range(
			'$3,000 - $10,000 per month',
			array(
				$this->create_option_row_data(3500, 'monthly', 6, 'dollar'),
				$this->create_option_row_data(6500, 'monthly', 6, 'dollar'),
				$this->create_option_row_data(10500, 'monthly', 6, 'dollar')
			)
		);
		$dollar_budget_ranges[] = new mpq_options_budget_range(
			'Over $10,000 per month',
			array(
				$this->create_option_row_data(15000, 'monthly', 6, 'dollar'),
				$this->create_option_row_data(25000, 'monthly', 6, 'dollar'),
				$this->create_option_row_data(60000, 'monthly', 6, 'dollar')
			)
		);
		$dollar_budget_ranges[] = new mpq_options_budget_range(
			'Other',
			array(
				$this->create_option_row_data('', 'monthly', 6, 'dollar')
				)
			);

		$data['options_dollar_budget_defaults'] = $dollar_budget_ranges;

		$impression_budget_set = new mpq_options_budget_range(
			'',
			array(
				$this->create_option_row_data(100000, 'monthly', 6, 'impression'),
				$this->create_option_row_data(200000, 'monthly', 4, 'impression'),
				$this->create_option_row_data(350000, 'monthly', 6, 'impression')
			)
		);
		$data['options_impression_budget_defaults'] = $impression_budget_set;


		$budget_options = array();
		foreach($options_data as $option)
		{
			$option_row = $this->get_option_row_data_from_raw_data($option);
			$budget_options[] = $option_row;
			//$budget_options_sets[$option->type][] = $option_row;
		}

		$data['options_existing_data'] = $budget_options;
	}

	public function ajax_check_mpq_session()
	{
		if (count($_POST) > 0 && isset($_POST['referrer_email']))
		{
			$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
			$post_variables = array('referrer_email');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if (!$response['is_success'])
			{
				echo json_encode($response);
				return;
			}
			else
			{
				$redirect_string = $this->vl_platform->get_access_permission_redirect('mpq');
				if($redirect_string == 'login')
				{
					if(is_null($response['post']['referrer_email']))
					{
						echo json_encode(array('is_success' => false));
						exit(0);
					}
				}

				$session_id = $this->session->userdata('session_id');
				$index_in_session_array = 0;
				$region_data = $this->mpq_v2_model->get_mpq_session_data($session_id);
				// fix for empty region data
				$decoded_region_data = json_decode($region_data->region_data, true);
				$has_session_data = ($decoded_region_data !== null && isset($decoded_region_data[$index_in_session_array]) && count($decoded_region_data[$index_in_session_array]['ids']['zcta']) > 0);
				echo json_encode(array('is_success' => true, 'has_session_data' => $has_session_data));
			}
		}
		else
		{
			echo json_encode(array('is_success' => false, 'errors' => array('Invalid page visit')));
		}

	}

	// show the single page version of the mpq
	public function single_page($referrer_email = null)
	{
		$errors = array();
		$is_success = true;

		$mpq_user_role = 'advertiser';
		$is_logged_in = false;

		$redirect_string = $this->vl_platform->get_access_permission_redirect('mpq');
		if($redirect_string == 'login')
		{
			if(is_null($referrer_email))
			{
				$this->session->set_userdata('referer', 'mpq');
				redirect($redirect_string);
			}
			else
			{
				$is_logged_in = false;
				$mpq_user_role = 'media_planner';
				$creative_requester = $this->vl_auth_model->get_user_by_email($referrer_email);
			}

			// TODO: use cookie to get/set default -scott (2013_07_18)
		}
		elseif($redirect_string == '')
		{
			$is_logged_in = true;
			$user_name = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($user_name);
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

			//$mpq_user_role = 'public';
		}
		else
		{
			redirect($redirect_string);
			$is_logged_in = false;
			$is_success = false;
			$errors[] = 'Unexpected permission rejection with mpq: '.$redirect_string.' (#08743)';
		}

		preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
		if(count($matches) > 1)
		{
			$version = $matches[1];
			if($version <= 8)
			{
				$this->load->view('mpq_v2/old_browser_redirect');
				return;
			}
		}

		$active_feature_button_id = 'mpq';
		$data = array();

		$session_id = $this->session->userdata('session_id');
		$data['session_id'] = $session_id;

		$options_data = array();

		$has_session_data = false;

		$owner_id = $this->tank_auth->get_user_id();
		if($owner_id == 0 || $owner_id == '')
		{
			$owner_id = null;
		}

		$this->mpq_v2_model->initialize_mpq_session($session_id, $owner_id);
		$this->mpq_v2_model->initialize_location_in_mpq_session($session_id, 0, 'mpq session');

		$mpq_session_data = $this->mpq_v2_model->get_mpq_session_data($session_id);

		$data['geo_radius'] = '';
		$data['geo_center'] = '';

		//let's set up the geo view if this user can use custom geos
		if(!isset($role))
		{
			$role = 'public';
		}

		$data['custom_geos_enabled'] = true;
		$this->add_geographic_view_variables($data, $mpq_session_data->region_data);
		$this->add_demographics_view_variables($data, $mpq_session_data->demographic_data);
		$this->add_channels_view_variables($data, $mpq_session_data->selected_iab_categories);
		$this->add_industries_view_variables($data, $mpq_session_data->industry_id);
		$this->add_options_view_variables($data, $options_data);

		$data['user_role'] = $mpq_user_role;
		$data['is_logged_in'] = $is_logged_in;

		$data['creative_requester'] = isset($creative_requester) ? array('id'=>$creative_requester->id, 'email'=>$creative_requester->email) : FALSE;

		$session_id = $this->session->userdata('session_id');
		$data['session_id'] = $session_id;
		$data['mpq_id'] = $mpq_session_data->id;

		$data['has_session_data'] = $has_session_data;

		$data['title'] = 'MPQ';


		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'mpq_v2/single_page_mpq_html',
			'mpq_v2/single_page_mpq_html_header.php',
			NULL,
			'mpq_v2/single_page_mpq_js.php',
			NULL
			// , false
		);
	}

	//	shows the geo map
	//	supposed to be used in iframe
	public function get_geo_map_and_stats($location_id = 0, $unique_display_id = false)
	{
		ini_set('memory_limit', '1024M');
		$data = array();
		$session_id = $this->session->userdata('session_id');
		$data['session_id'] = $session_id;


		$region_type = 'zcta';
		$region_details = array();
		$mpq_id = false;
		if($unique_display_id !== false)
		{
			$mpq_id = $this->mpq_v2_model->get_mpq_id_by_unique_display_id($unique_display_id);
		}

		if($region_type == 'zcta')
		{
			if($mpq_id !== false)
			{
				$region_details = $this->map->get_zips_from_mpq_id_and_feature_table($mpq_id, 'mpq_sessions_and_submissions', false, $location_id);
			}
			else
			{
				$region_details = $this->map->get_zips_from_session_id_and_feature_table($session_id, 'mpq_sessions_and_submissions', false, $location_id);
			}
			$data['search_type'] = 'zcta';
		}
		if($mpq_id === false)
		{
			$mpq_id_string = $this->mpq_v2_model->get_mpq_session_data($session_id)->id;
			$mpq_id = intval($mpq_id_string);
		}


		$stats_data = $this->get_geo_stats($location_id);
		$processed_stats = $stats_data;
		$this->add_geo_stats_data($processed_stats, $stats_data);
		$data['stats_data'] = $processed_stats;
		$data['stats_data']['demographics']['median_age'] = number_format($processed_stats['demographics']['median_age'], 1);
		$data['stats_data']['demographics']['persons_household'] = number_format($processed_stats['demographics']['persons_household'], 1);
		$data['stats_data']['demographics']['average_home_value'] = number_format($processed_stats['demographics']['average_home_value'], 0);
		$data['stats_data']['demographics']['num_establishments'] = number_format($processed_stats['demographics']['num_establishments']);

		$data['national_averages_array'] = $this->map->get_national_averages_for_demos();
		$geofencing_points = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($mpq_id, $location_id);
		$data['geofencing_points'] = ($geofencing_points) ? json_encode($geofencing_points) : null;

		// Check if there are more than 1K zip codes returned
		$data['big_map'] = count($region_details['zcta']) > 1000;
		$map_blobs = $data['big_map'] ?
			$this->map->get_geojson_points_from_region_array($region_details) : // OR
			$this->map->get_geojson_blobs_from_region_array($region_details);

		$map_geojson = $this->map->get_geojson_for_mpq($map_blobs);

		$data['map_objects'] = (empty($map_blobs)) ? 'false' : $map_geojson;
		$data['shared_js'] = $this->load->view('maps/map_shared_functions_js', null, true);
		$data['location_id'] = (is_numeric($location_id)) ? $location_id : '-1';
		$data['google_access_token_rfp_io'] = $this->config->item('google_access_token_rfp_io');

		$view_result = $this->load->view('mpq_v2/map_view', $data, true);
		echo $view_result;
	}

	//	add the geo stats to 'data' for the view
	private function add_geo_stats_data(&$data, $geo_stats)
	{
		if(empty($geo_stats['errors']))
		{
			$data['target_region'] = $geo_stats['targeted_region_summary'];
			$data['population'] = number_format($geo_stats['target_population']);
			$data['income'] = '$'.number_format($geo_stats['demographics']['household_income']);
		}
		else
		{
			$data['target_region'] = '';
			$data['population'] = '0';
			$data['income'] = '$0';

			$error_string = 'ERROR: failed to get geo stats. (#549874)';
			if(array_key_exists('errors', $data))
			{
				if(is_array($data['errors']))
				{
					$data['errors'][] = $error_string;
					$data['errors'] = array_merge($data['errors'], $geo_stats['errors']);
				}
				else
				{
					$previous_errors = $data['errors'];
					$data['errors'] = array();
					$data['errors'][] = $previous_errors;
					$data['errors'][] = $error_string;
				}
			}
			else
			{
				$data['errors'] = array();
				$data['errors'][] = $error_string;
			}
		}
	}

	//	add or remove a single zip code to/from the database
	//
	//	output: echos response status in json
	//	return: nothing
	public function ajax_modify_zipcodes()
	{
		$response = array();
		$response['is_success'] = true;
		$response['errors'] = array();

		$session_id = $this->session->userdata('session_id');

		$action = $this->input->post('action');
		$zip = $this->input->post('zip');
		$location_id = empty($this->input->post('location_id')) ? 0 : $this->input->post('location_id');
		if($action && $zip)
		{
			if($action == 'add_zipcode')
			{
				$this->mpq_v2_model->add_zipcode($zip, $session_id, $location_id);
			}
			elseif($action == 'remove_zipcode')
			{
				$this->mpq_v2_model->remove_zipcode($zip, $session_id, $location_id);
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = "unknown action ({$action}) in modify_zipcodes() (#9854987)";
			}

			$geo_stats = $this->get_geo_stats($location_id);

			$response['demographics'] = $geo_stats['demographics'];
			$response['target_population'] = $geo_stats['target_population'];
			$response['demographics']['median_age'] = number_format($geo_stats['demographics']['median_age'], 1);
			$response['demographics']['persons_household'] = number_format($geo_stats['demographics']['persons_household'], 1);
			$response['demographics']['average_home_value'] = number_format($geo_stats['demographics']['average_home_value'], 0);
			$response['demographics']['num_establishments'] = number_format($geo_stats['demographics']['num_establishments']);

			$this->add_geo_stats_data($response, $geo_stats);
		}

		$json_response = json_encode($response);
		echo $json_response;
	}

	//	get the 'data' for the geo stats
	private function get_geo_stats($location_id = null)
	{
		$data = array();
		$data['errors'] = array();

		$session_id = $this->session->userdata('session_id');
		$zips = $this->map->get_zips_from_session_id_and_feature_table($session_id, 'mpq_sessions_and_submissions', false, $location_id);

		$data['demographics'] = $this->map->get_demographics_from_region_array($zips);
		$data['target_population'] = $data['demographics']['region_population'];
		$data['targeted_region_summary'] = $this->map->get_targeting_regions_string($zips['zcta']);

		return $data;
	}

	public function ajax_check_rfp_session()
	{
		$allowed_roles = array('admin', 'ops', 'sales');

		$response = vl_verify_ajax_call($allowed_roles);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			return;
		}
		else
		{
			$session_id = $this->session->userdata('session_id');
			$check_session_data_result = $this->mpq_v2_model->validate_multi_geos_location_data_from_mpq_table($session_id);
			echo json_encode($check_session_data_result);
		}
	}

	private function build_options_from_data(&$is_success, array &$errors, array &$options, $options_data)
	{
		foreach($options_data as $ii=>$option_data)
		{
			$new_option = mpq_budget_option_save::make_new_with_std_class($option_data);
			if(is_object($new_option) && get_class($new_option) == 'mpq_budget_option_save')
			{
				$options[] = $new_option;
			}
			else
			{
				$is_success = false;
				$errors[] = 'Failed to build option #'.$ii.' of '.count($options_data).' (#095743)';
				$errors[] = $new_option;
				break;
			}
		}

		return $is_success;
	}

	private function calculate_option_data_for_mpq(
		&$placeholder_cpm,
		&$placeholder_discount,
		&$summary_string,
		$type,
		$amount,
		$term,
		$duration,
		$cpm,
		$discount
	)
	{
		$placeholder_base_cpm = 0;
		$placeholder_retargeting_cpm = 0;
		$impressions_per_term = 0;
		$base_dollars_per_term = 0;
		$charged_dollars_per_term = 0;
		$retargeting_price_per_term = 0;

		$impression_cpm = null;
		$retargeting_cpm = null;
		$this->mpq_v2_model->split_mpq_cpm_for_option_engine(
			$impression_cpm,
			$retargeting_cpm,
			$cpm
		);

		$is_retargeting = true;
		switch($type)
		{
			case 'dollar':
				$this->mpq_v2_model->get_ad_plan_data_from_dollars(
					$placeholder_base_cpm,
					$placeholder_retargeting_cpm,
					$placeholder_discount,
					$summary_string,
					$impressions_per_term,
					$base_dollars_per_term,
					$charged_dollars_per_term,
					$retargeting_price_per_term,

					$amount,
					$term,
					$duration,
					$impression_cpm,
					$retargeting_cpm,
					$discount,
					$is_retargeting
				);
				break;
			case 'impression':
				$this->mpq_v2_model->get_ad_plan_data_from_impressions(
					$placeholder_base_cpm,
					$placeholder_retargeting_cpm,
					$placeholder_discount,
					$summary_string,
					$base_dollars_per_term,
					$charged_dollars_per_term,
					$retargeting_price_per_term,

					$amount,
					$term,
					$duration,
					$impression_cpm,
					$retargeting_cpm,
					$discount,
					$is_retargeting
				);
				break;
			default:
				die('Unknown option type: '.$type);
		}

		$placeholder_cpm = $placeholder_base_cpm + $placeholder_retargeting_cpm;
	}

	//	recalculate option from POST data
	//
	//	output: json string conforming to vl_platform calling convention
	//	return: nothing
	public function ajax_get_option_refresh_for_mpq()
	{
		$is_success = true;
		$errors = array();
		$data = array();

		$type = $this->input->post('type');
		$amount = $this->input->post('amount');
		$term = $this->input->post('term');
		$duration = $this->input->post('duration');

		$cpm = $this->input->post('cpm');
		$discount = $this->input->post('discount');

		$placeholder_cpm = 0;
		$placeholder_discount = 0;
		$summary_string = '';
		$this->calculate_option_data_for_mpq(
			$placeholder_cpm,
			$placeholder_discount,
			$summary_string,

			$type,
			$amount,
			$term,
			$duration,
			$cpm,
			$discount
		);

		$data['computed_discount_placeholder'] = $placeholder_discount;
		$data['computed_cpm_placeholder'] = $placeholder_cpm;
		$data['summary'] = $summary_string;

		$data['is_success'] = $is_success;
		$data['errors'] = $errors;
		$json_data = json_encode($data);
		echo $json_data;
	}
	private function save_insertion_order_data(&$is_success, &$errors, $session_id, $ajax_data)
	{
		if($is_success === true)
		{
			$inputs['session_id'] = $session_id;
			if($ajax_data->is_retargeting === true)
			{
				$inputs['is_retargeting'] = 1;
			}
			else if ($ajax_data->is_retargeting === false)
			{
				$inputs['is_retargeting'] = 0;
			}
			else
			{
				$is_success = false;
				$errors[] = 'insertion order retargeting value error: '.$ajax_data->is_retargeting;
			}
			if(isset($ajax_data->landing_page))
			{
				if($ajax_data->landing_page !== "" AND $ajax_data->landing_page !== false)
				{
					$inputs['landing_page'] = $ajax_data->landing_page;
				}
				else
				{
					$is_success = false;
					$errors[] = 'insertion order landing page value error';
				}
			}
			if(isset($ajax_data->num_impressions))
			{
				$ajax_data->num_impressions = str_replace(',', '', $ajax_data->num_impressions);
				if(ctype_digit($ajax_data->num_impressions))
				{
					if((int)$ajax_data->num_impressions > 1000)
					{
						$inputs['impressions'] = $ajax_data->num_impressions;
					}
					else
					{
						$is_success = false;
						$errors[] = 'insertion order impressions must be greater than 1000';
					}
				}
				else
				{
					$is_success = false;
					$errors[] = 'insertion order impressions must be a number';
				}
			}
			else
			{
				$is_success = false;
				$errors[] = 'insertion order impressions value error: ';
			}
			if(isset($ajax_data->start_date))
			{
				$t_start_date = date_parse($ajax_data->start_date);
				if($t_start_date !== false AND $t_start_date['error_count'] == 0)
				{
					if(checkdate($t_start_date['month'], $t_start_date['day'], $t_start_date['year']))
					{
						$inputs['start_date'] = date("Y-m-d", strtotime($ajax_data->start_date));
					}
					else
					{
						$is_success = false;
						$errors[] = 'insertion order invalid start date: '.$ajax_data->start_date;
					}
				}
				else
				{
					$is_success = false;
					$errors[] = 'insertion order invalid start date: '.$ajax_data->start_date;
				}
			}
			else
			{
				$is_success = false;
				$errors[] = 'insertion order start date value error';
			}
			if(isset($ajax_data->end_date))
			{
				if($ajax_data->end_date === "false")
				{
					$inputs['end_date'] = NULL;
				}
				else
				{
					$t_end_date = date_parse($ajax_data->end_date);
					if($t_start_date !== false AND $t_start_date['error_count'] == 0)
					{
						if(checkdate($t_end_date['month'], $t_end_date['day'], $t_end_date['year']))
						{
							if(strtotime($ajax_data->start_date) < strtotime($ajax_data->end_date))
							{
								$inputs['end_date'] = date("Y-m-d", strtotime($ajax_data->end_date));
							}
							else
							{
								$is_success = false;
								$errors[] = 'insertion order end date must be later than start date';
							}
						}
						else
						{
							$is_success = false;
							$errors[] = 'insertion order invalid end date: '.$ajax_data->end_date;
						}
					}
					else
					{
						$is_success = false;
						$errors[] = 'insertion order invalid end date: '.$ajax_data->end_date;
					}
				}
			}
			else
			{
				$is_success = false;
				$errors[] = 'insertion order end date value error: '.$ajax_data->end_date;
			}

			if (in_array($ajax_data->term_type, array('MONTH_END', 'BROADCAST_MONTHLY', 'FIXED_TERM')))
			{
				$inputs['term_type'] = $ajax_data->term_type;
			}
			else
			{
				$is_success = false;
				$errors[] = 'insertion order invalid term type: '.$ajax_data->term_type;
				break;
			}

			if($ajax_data->term_duration == '-1')
			{
				$inputs['term_duration'] = "on going";
			}
			else if($ajax_data->term_duration == '0')
			{
				$inputs['term_duration'] = 'specified';
			}
			else if(1 <= (int)$ajax_data->term_duration AND (int)$ajax_data->term_duration <= 12)
			{
				$inputs['term_duration'] = $ajax_data->term_duration;
			}
			else
			{
				$is_success = false;
				$errors[] = 'insertion order invalid term duration: '.$ajax_data->term_duration;
			}
			if($is_success === true)
			{
				return $this->mpq_v2_model->save_insertion_order($inputs);
			}
		}
		return array('is_success' => $is_success);
	}

	// save mpq options to database
	private function save_options_data(&$is_success, &$errors, $options_data)
	{
		$options = array();
		$this->build_options_from_data($is_success, $errors, $options, $options_data);
		if($is_success === true)
		{
			$session_id = $this->session->userdata('session_id');
			$is_success = $this->mpq_v2_model->save_options_to_mpq($session_id, $options);

			if($is_success !== true)
			{
				$errors[] = 'failed to save options data to database';
				$model_error_message = $is_success;
				$errors[] = $model_error_message;
				$is_success = false;
			}
		}

		return $is_success;
	}

	// save zip codes to database
	public function ajax_save_zip_codes()
	{
		$is_success = true;
		$errors = array();
		$return_data = array();

		$input_zips = $this->input->post('zips_json');
		$input_lat_center = $this->input->post('map_center_latitude') ?: 0;
		$input_long_center = $this->input->post('map_center_longitude') ?: 0;
		$is_custom_regions = $this->input->post('is_custom_regions') == 'true';
		$location_id = empty($this->input->post('location_id')) ? 0 : $this->input->post('location_id');
		$location_name = empty($this->input->post('location_name')) ? '' : $this->input->post('location_name');
		$is_builder = $this->input->post('is_builder');
		$mpq_id = $this->input->post('mpq_id');
		$session_id = $this->session->userdata('session_id');
		if($mpq_id !== false && $is_builder === false)
		{
			$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
			if($session_id !== $session_response)
			{
				$return_data['is_success'] = true;
				$return_data['errors'] = [];
				$return_data['session_expired'] = true;
				echo json_encode($return_data);
				return;
			}
		}
		if(!is_numeric($input_lat_center))
		{
			$is_success = false;
			$errors[] = "Invalid latitude: {$input_lat_center}";
		}

		if(!is_numeric($input_long_center))
		{
			$is_success = false;
			$errors[] = "Invalid longitude: {$input_long_center}";
		}

		if(!is_numeric($location_id))
		{
			$is_success = false;
			$errors[] = "Invalid location: {$location_id}";
		}

		$zips = json_decode($input_zips);
		$dedupe_zips = array_filter(array_unique($zips));
		$return_data['custom_location_name'] = '';
		if($is_success)
		{
			$custom_location_name = (empty($location_name)) ?
				$this->map->get_custom_name_for_location($session_id, $location_id, $is_custom_regions, $dedupe_zips, $mpq_id) :
				$location_name;
			$return_data['custom_location_name'] = $custom_location_name;

			$zips_saved_successfully = $this->mpq_v2_model->save_zips(
				$dedupe_zips,
				$input_lat_center,
				$input_long_center,
				$session_id,
				$custom_location_name,
				$location_id,
				$is_custom_regions,
				$mpq_id
			);
		}
		$return_data['is_success'] = $is_success;
		$return_data['errors'] = $errors;
		$return_data['successful_zips'] = $zips_saved_successfully;

		$demographics = $this->map->get_demographics_from_region_array($zips_saved_successfully);
		$return_data['location_population'] = intval($demographics['region_population']);
		echo json_encode($return_data);
	}

	// do radius search and save zip codes to database
	public function ajax_save_geo_radius_search($search_criteria)
	{
		$return_array = array('is_success' => true, 'errors' => array(), 'session_expired' => false);

		$location_id = empty($this->input->post('location_id')) ? 0 : $this->input->post('location_id');
		$location_name = empty($this->input->post('location_name')) ? 'mpq session' : $this->input->post('location_name');
		$address = empty($this->input->post('address')) ? "" : $this->input->post('address');
		$mpq_id = $this->input->post('mpq_id');
		$session_id = $this->session->userdata('session_id');
		if($mpq_id !== false)
		{
			$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
			if($session_id !== $session_response)
			{
				$return_array['session_expired'] = true;
				echo json_encode($return_array);
				return;
			}
		}

		$result_regions = $this->mpq_v2_model->do_geo_search_and_save_result($search_criteria, $session_id, $location_id, $location_name, $address);
		$return_array['result_regions'] = $result_regions;

		$demographics = $this->map->get_demographics_from_region_array(['zcta' => $result_regions]);
		$return_array['location_population'] = intval($demographics['region_population']);
		echo json_encode($return_array);
	}

	public function ajax_initialize_new_location()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
		$post_variables = array('location_id', 'location_name');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			return;
		}
		else
		{
			$mpq_id = $this->input->post('mpq_id');
			$session_id = $this->session->userdata('session_id');
			if($mpq_id !== false)
			{
				$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
				if($session_id !== $session_response)
				{
					$result = array('is_success' => true, 'errors' => array(), 'session_expired' => true);
					echo json_encode($result);
					return;
				}
			}

			$result = $this->mpq_v2_model->initialize_location_in_mpq_session($session_id, $response['post']['location_id'], $response['post']['location_name'], $mpq_id);

			echo json_encode($result);
		}
	}

	public function ajax_save_location_name()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
		$post_variables = array('location_id', 'location_name', 'manually_renamed');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			return;
		}
		else
		{
			$session_id = $this->session->userdata('session_id');
			$errors = array();
			$success = $this->mpq_v2_model->save_location_name($session_id, $response['post']['location_id'], $response['post']['location_name'], $response['post']['manually_renamed'] == 'true');
			if($success != true)
			{
				$errors = array('Failed to update row in db');
			}
			echo json_encode(array('is_success' => $success, 'errors' => $errors));
		}
	}

	public function ajax_remove_location()
	{
		$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
		$post_variables = array('location_id');

		$response = vl_verify_ajax_call($allowed_roles, $post_variables);
		if (!$response['is_success'])
		{
			echo json_encode($response);
			return;
		}
		else
		{
			$mpq_id = intval($this->input->post('mpq_id'));
			$session_id = $this->session->userdata('session_id');
			if($mpq_id !== false)
			{
				$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
				if($session_id !== $session_response)
				{
					$result = array('is_success' => true, 'errors' => array(), 'session_expired' => true);
					echo json_encode($result);
					return;
				}
			}
			$errors = array();
			$success = $this->mpq_v2_model->remove_location($session_id, $response['post']['location_id']);
			if($success)
			{
				$this->mpq_v2_model->remove_selected_custom_regions_from_session($session_id, $response['post']['location_id']);
				$this->mpq_v2_model->shift_custom_regions_for_rfp_locations($session_id, $response['post']['location_id']);
				$this->mpq_v2_model->shift_geofence_regions_for_rfp_locations($mpq_id, $response['post']['location_id']);
			}
			else
			{
				$errors = array('Failed to remove location from db');
			}
			echo json_encode(array('is_success' => $success, 'errors' => $errors));
		}
	}

	//	save the mpq to database and remove association between data and active session
	//
	//	output: json string conforming to vl_platform calling convention
	//	return: nothing
	public function ajax_submit_mpq()
	{
		$is_success = true;
		$response_data = array();
		$errors = array();
		$submit_data = array();

		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$demographics = $this->input->post('demographics');
			$options_json = $this->input->post('options_json');
			$insertion_order_json = $this->input->post('insertion_order_json');
			$channels = $this->input->post('channels');
			$advertiser_json = $this->input->post('advertiser_json');
			$requester_json = $this->input->post('requester_json');
			$referrer_email = $this->input->post('referrer_json');
			$industry_id = $this->input->post('industry_id');
			$adset_request = false;

			if($demographics !== false &&
				$channels !== false &&
				$advertiser_json !== false &&
				$requester_json !== false
			)
			{
				$session_id = $this->session->userdata('session_id');

				$advertiser = json_decode($advertiser_json);
				$requester = json_decode($requester_json);

				$owner_id = $this->tank_auth->get_user_id();
				if($owner_id == 0 || $owner_id == '')
				{
					$owner_id = null;
				}
				if($options_json != "false" AND $insertion_order_json == "false")
				{
					$insertion_order_data = false;
					$options_data = json_decode($options_json);
					$this->save_options_data($is_success, $errors, $options_data);
				}
				else if ($insertion_order_json != "false" AND $options_json == "false")
				{
					if ($industry_id == "")
					{
						$industry_id = null;
					}
					$options_data = false;
					$insertion_order_data = json_decode($insertion_order_json);
					$insertion_order = $this->save_insertion_order_data($is_success, $errors, $session_id, $insertion_order_data);
					if ($insertion_order['is_success'])
					{
						$response_data['insertion_order_id'] = $insertion_order['id'];
					}
					$adset_request = $this->input->post('banner_intake_fields');
				}
				else
				{
					//fatal error
					$is_success = false;
					$errors[] = $options_json. " " .$insertion_order_json;
				}

				$geo_data = $this->mpq_v2_model->is_geo_data_populated_for_session($session_id);

				if($geo_data == false)
				{
					$is_success = false;
					$response_data['was_geo_empty'] = true;
				}

				$adset_request_id = NULL;

				if($adset_request)
				{
					if (isset($adset_request['id']))
					{
						$adset_request_id = $adset_request['id'];
					}
					else
					{
						// Unset is_form_complete, otherwise the model
						// will try to find a column with that name in the database
						if (isset($adset_request['is_form_complete']))
						{
							unset($adset_request['is_form_complete']);
						}

						$adset_request['advertiser_name'] = $advertiser->business_name;
						$adset_request['advertiser_email'] = $requester->email_address;
						$adset_request['advertiser_website'] = $advertiser->website_url;
						$adset_request['landing_page'] = $insertion_order_data->landing_page;

						$adset_request['product'] = 'Display';
						$adset_request['request_type'] = 'Custom Banner Design';
						$adset_request['creative_name'] = $advertiser->business_name.'_'.rand(5000,9999999);

						$adset_request_id = $this->process_banner_intake($adset_request);
						if (!$adset_request_id)
						{
							$errors[] = 'failed to create a creative request (#190838)';
						}
					}
				}

				$channel_ids = json_decode($channels);
				if($is_success === true)
				{
					$response_array = $this->mpq_v2_model->save_submitted_mpq_and_remove_from_session(
						$demographics,
						$channel_ids,
						$adset_request_id,
						$advertiser,
						$requester,
						$session_id,
						$owner_id,
						$industry_id
					);
					$is_success = $response_array['success'];
					$geo_mpq_data = $response_array['geo_data'];
					$mpq_id = $geo_mpq_data['id'];
					if($is_success !== true)
					{
						$errors[] = 'failed to save to mpq_sessions_and_submissions table (#190837)';
					}
					else
					{
						//url takes lap_id = false, prop_id = false and sets the optional mpq_id as the third parameter to lap_image_gen
						$url = base_url("proposal_builder/lap_image_gen/false/false/{$mpq_id}");
						$location = FCPATH.'/assets/js/phantom/snapshot.js';
						$response = exec(PHANTOMJS_BIN_LOCATION . " {$location} {$url}");
						if($this->session->userdata('is_demo_partner') != 1)
						{
							$submit_data = $this->send_mpq_request_email($is_success, $errors, $advertiser, $requester, $demographics, $geo_mpq_data, $options_data, $insertion_order_data, $referrer_email, $mpq_id, $adset_request_id);
							if(is_array($submit_data['mailgun_result'])) //mailgun_result is either an array of errors or bool true
							{
								if($this->mpq_v2_model->update_mpq_session_with_mailgun_errors($geo_mpq_data['id'], $submit_data['mailgun_result']))
								{
									//updated with mailgun error successfully
								}
							}

						}else{
							$mail_error = 'Do not have permission to submit mpq!';
							$mail_errors[] = $mail_error;
							$submit_data = $mail_errors;
						}

					}
				}
			}
			else
			{
				$is_success = false;
				$error = 'bad post data for submitted mpq (#5408754)';
				$errors[] = $error;
			}
		}
		else
		{
			echo show_404();
			return;
		}

		$response_data['errors'] = $errors;
		$response_data['is_success'] = $is_success;
		$response_data['submit_data'] = $submit_data;

		$json_response = json_encode($response_data);
		echo $json_response;
	}

	private function process_banner_intake($adset_request)
	{
		$adset_request_id = NULL;

		if (isset($adset_request['creative_files']))
		{
			foreach ($adset_request['creative_files'] as $i => &$file)
			{
				$file['url'] = $file[0];
				$file['name'] = $file[1];
				$file['bucket'] = $file[2];
				$file['type'] = $file[3];

				unset($file[0]);
				unset($file[1]);
				unset($file[2]);
				unset($file[3]);
			}
		}

		$insert_result = $this->banner_intake_model->insert_adset_request_form($adset_request);
		if($insert_result['is_success'])
		{
			$adset_request_id = $insert_result['result'];

			$data['form_data'] = $adset_request;
			$data['requested_time'] = 'just now';

			$email_message_markup = $this->load->view('banner_intake/banner_intake_confirmation_email_html', $data, true);
			$subject_string = 'Banner Intake: '.$adset_request['advertiser_name'] . ' [Received]';

			$mailgun_extras = array();
			if (!isset($adset_request['advertiser_email']) || $adset_request['requester_email'] == $adset_request['advertiser_email'])
			{
				$adset_request['advertiser_email'] = $adset_request['requester_email'];
			}
			else
			{
				$mailgun_extras['cc'] = $adset_request['requester_email'];
			}
			$mailgun_extras['h:reply-to'] = $adset_request['advertiser_email'];
			if($this->session->userdata('is_demo_partner') != 1)
			{
				//send email
				$result = mailgun(
					'no-reply@brandcdn.com',
					'helpdesk@brandcdn.com',
					$subject_string,
					$email_message_markup,
					"html",
					$mailgun_extras
				);
			}
		}

		return $adset_request_id;
	}

	private function send_mpq_request_email(&$is_success, &$errors, $advertiser, $requester, $demographics, $geo_mpq_data, $options_data, $insertion_order_data, $referrer_email, $mpq_id, $adset_request_id)
	{
		$geo_object = json_decode($geo_mpq_data['region_data']);
		$geo_object = (isset($geo_object->page)) ? $geo_object : $geo_object[0];
		$this->map->convert_old_flexigrid_format_object($geo_object);
		$geo_object->regions = $geo_object->ids->zcta;
		unset($geo_object->ids);

		if($insertion_order_data != false)
		{
			$mpq_type = 'insertion order';
		}
		else
		{
			$mpq_type = 'proposal';
		}
		$channel_array = $this->mpq_v2_model->get_iab_contextual_channels_by_mpq_id($mpq_id);
		$demo_array = explode("_", $demographics);
		$demographics_settings = array(
			'Male' => $demo_array[0],
			'Female' => $demo_array[1],
			'Age under 18' => $demo_array[2],
			'Age 18 to 24' => $demo_array[3],
			'Age 25 to 34' => $demo_array[4],
			'Age 35 to 44' => $demo_array[5],
			'Age 45 to 54' => $demo_array[6],
			'Age 55 to 64' => $demo_array[7],
			'Age 65 and older' => $demo_array[8],
			'Income under 50k' => $demo_array[9],
			'Income 50k to 100k' => $demo_array[10],
			'Income 100k to 150k' => $demo_array[11],
			'Income over 150k' => $demo_array[12],
			'No college education' => $demo_array[13],
			'College education' => $demo_array[14],
			'Grad school education' => $demo_array[15],
			'People without children' => $demo_array[16],
			'Parents' => $demo_array[17]);

		$term_translate = array('monthly' => 'month', 'daily' => 'day', 'weekly' => 'week');
		$data_array = array(
			'mpq_type' => $mpq_type,
			'is_success' => $is_success,
			'errors' => $errors,
			'advertiser' => $advertiser,
			'requester' => $requester,
			'channels' => $channel_array,
			'demographics' => $demographics_settings,
			'geo_mpq_data' => $geo_object,
			'option_data' => $options_data,
			'insertion_order_data' => $insertion_order_data,
			'term_translate' => $term_translate,
			'submit_view' => false,
			'adset_request_id' => $adset_request_id
		);
		$partner_details = false;
		$user_email = "";
		$user_id = $this->tank_auth->get_user_id();
		if($user_id)
		{
			$username = $this->tank_auth->get_username();
			$user_email = $this->tank_auth->get_email($username);
			$partner_id = $this->tank_auth->get_partner_id($user_id);
			if(!is_null($partner_id))
			{
				$partner_details = $this->tank_auth->get_partner_info($partner_id);
			}
		}

		if($partner_details === false OR $partner_details === NULL) //not logged in or failed to get partner data
		{
			$partner_details = $this->tank_auth->if_partner_return_details();
		}

		if($partner_details === false OR $partner_details === NULL) //if not on a cnamed url and not logged in we force anyone to be Brand CDN
		{
			$partner_name = 'Brand CDN';
			$partner_id = 1;
		}
		else
		{
			$partner_name = $partner_details['partner_name'];
			$partner_id = $partner_details['id'];
		}
		$cc_emails = array();
		$bcc_emails = array('webintake@vantagelocal.com');
		if($mpq_type == 'insertion order')
		{
			$sales_result = $this->tank_auth->get_parent_and_sales_owner_emails_by_partner($partner_id);
			foreach($sales_result as $sales_user)
			{
				$bcc_emails[] = $sales_user['email'];
			}
		}

		if(!empty($referrer_email))
		{
			$cc_emails[] = $referrer_email;
		}

		if(!empty($user_email) AND $user_email !== $requester->email_address)
		{
			$cc_emails[] = $user_email;
		}

		$cc_emails = array_unique($cc_emails);
		$bcc_emails = array_unique($bcc_emails);
		$data_array['cc_email'] = $referrer_email;
		$data_array['mpq_id'] = $mpq_id;
		$data_array['custom_regions'] = $this->mpq_v2_model->get_custom_regions_by_mpq_id($mpq_id);
		$message = $this->load->view('mpq_v2/mpq_email_view', $data_array, true);
		$subject = ($advertiser->business_name == '') ? '' : $advertiser->business_name." - ";
		$subject .= $mpq_type .' - '.$partner_name;
		$from = 'No Reply <no-reply@brandcdn.com>';
		$to = 'helpdesk@brandcdn.com';
		$cc = implode(", ", $cc_emails);
		$bcc = implode(", ", $bcc_emails);
		$post_overrides = array('bcc' => $bcc, "h:reply-to" => $requester->email_address);
		if($cc !== "")
		{
			$post_overrides['cc'] = $cc;
		}
		$result = mailgun(
			$from,
			$to,
			$subject,
			$message,
			"html",
			$post_overrides
		);
		$data_array['mailgun_result'] = $result;
		return $data_array;
	}

	public function mpq_submitted()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$data['cc_email'] = $this->input->post('cc_email');
			$submitted_data = $this->input->post('submitted_data');
			if(!empty($json = json_decode($submitted_data)))
			{
				$submitted_data = $json;
				$data = array();
				foreach($submitted_data as $key=>$value)
				{
					if(is_string($key))
					{
						$data[$key] = $value;
					}
					else
					{
						die("expected 'key' to only be of type string");
					}
				}

				$data['custom_regions'] = $this->mpq_v2_model->get_custom_regions_by_mpq_id($submitted_data->mpq_id);
				$data['submit_view'] = true;
				$data['term_translate'] = array('monthly' => 'month', 'daily' => 'day', 'weekly' => 'week');

				$active_feature_button_id = 'mpq';
				$data['title'] = 'Review MPQ Submission';

				$this->vl_platform->show_views(
					$this,
					$data,
					$active_feature_button_id,
					'mpq_v2/submission_summary_html',
					'mpq_v2/submission_summary_html_header.php',
					NULL,
					NULL, // 'mpq_v2/submission_summary_js.php',
					NULL
					// , false
				);
			}
			else
			{
				redirect("/mpq"); //submit data is incorrect
			}
		}
		else
		{
			redirect("/mpq");
		}
	}

	private $map_option_engine_ad_plan_type_to_mpq_ad_plan_type = array(
		'budget' => 'dollar',
		'impressions' => 'impression'
	);

	private $map_option_engine_period_type_to_mpq_term = array(
		'months' => 'monthly',
		'weeks' => 'weekly',
		'days' => 'daily',
	);

	//	output: json string conforming to vl_platform calling convention
	//	return: nothing
	public function ajax_recalculate_option_for_option_engine()
	{
		$is_success = true;
		$response_data = array();
		$errors = array();

		$option_json = $this->input->post('option_json');
		$ad_plan_index = $this->input->post('ad_plan_index');
		$change_type = $this->input->post('change_type');
		$scope = $this->input->post('scope');

		if($option_json !== false &&
			$ad_plan_index !== false &&
			$change_type !== false &&
			$scope !== false
		)
		{
			$option = json_decode($option_json);
			if($option != null)
			{
				switch($scope)
				{
					case 'ad_plan':
					case 'option':
						switch($change_type)
						{
							// option change_types
							case 'discount':
							case 'ad_plan_removed':

							// ad_plan change_types
							case 'budget':
							case 'cpm':
							case 'custom_cpm':
							case 'impressions':
							case 'period_type':
							case 'retargeting':
							case 'select_lap':
							case 'term_duration':
								$discount = $option->discount_monthly_percent;
								$total_base_dollars_per_term = 0;
								$total_charged_dollars_per_term = 0;
								$total_impressions = 0;

								foreach($option->laps as $ii=>&$ad_plan)
								{
									$placeholder_base_cpm = '';
									$placeholder_retargeting_cpm = '';
									$placeholder_discount = '';
									$summary_string = '';
									$base_dollars_per_term = 0;
									$charged_dollars_per_term = 0;
									$retargeting_dollars_per_term = 0;

									$raw_type = $ad_plan->option_type;
									$raw_type = explode('_', $raw_type);
									$type = $this->map_option_engine_ad_plan_type_to_mpq_ad_plan_type[$raw_type[0]];

									// Note: term in the option_engine has a different meaning than the mpq
									$term = $this->map_option_engine_period_type_to_mpq_term[$ad_plan->period_type];
									$duration = $ad_plan->term;

									$base_cpm = $ad_plan->custom_impression_cpm == 'null' ? 0 : $ad_plan->custom_impression_cpm;
									$retargeting_cpm = $ad_plan->custom_retargeting_cpm == 'null' ? 0 : $ad_plan->custom_retargeting_cpm;
									$is_retargeting = $ad_plan->retargeting;
									switch($type)
									{
										case 'dollar':
											$derived_impressions = 0;

											$amount = $ad_plan->budget;
											$this->mpq_v2_model->get_ad_plan_data_from_dollars(
												$placeholder_base_cpm,
												$placeholder_retargeting_cpm,
												$placeholder_discount,
												$summary_string,
												$derived_impressions,
												$base_dollars_per_term,
												$charged_dollars_per_term,
												$retargeting_dollars_per_term,

												$amount,
												$term,
												$duration,
												$base_cpm,
												$retargeting_cpm,
												$discount,
												$is_retargeting
											);

											$ad_plan->impressions = $derived_impressions;
											break;
										case 'impression':
											$amount = $ad_plan->impressions;
											$this->mpq_v2_model->get_ad_plan_data_from_impressions(
												$placeholder_base_cpm,
												$placeholder_retargeting_cpm,
												$placeholder_discount,
												$summary_string,
												$base_dollars_per_term,
												$charged_dollars_per_term,
												$retargeting_dollars_per_term,

												$amount,
												$term,
												$duration,
												$base_cpm,
												$retargeting_cpm,
												$discount,
												$is_retargeting
											);

											$ad_plan->budget = $charged_dollars_per_term;
											break;
										default:
											$error = 'Unknown budget option type: '.$type;
											die($error);
											$is_success = false;
											$errors[] = $error;
									}

									if($ad_plan->custom_impression_cpm == 'null')
									{
										$ad_plan->custom_impression_cpm = $placeholder_base_cpm;
										$ad_plan->custom_retargeting_cpm = $placeholder_retargeting_cpm;
									}
									$ad_plan->retargeting_price = $retargeting_dollars_per_term;

									$reach_frequency_data = $this->proposal_gen_model->get_reach_frequency_data(
										$ad_plan->lap_id,
										$ad_plan->term,
										$ad_plan->period_type,
										$ad_plan->impressions,
										$ad_plan->gamma,
										$ad_plan->ip_accuracy,
										$ad_plan->demo_coverage,
										$ad_plan->retargeting
									);

									$ad_plan->reach_frequency_table = $reach_frequency_data;

									$total_base_dollars_per_term += $base_dollars_per_term ;
									$total_charged_dollars_per_term += $charged_dollars_per_term ;

									$total_impressions += $ad_plan->impressions;
								}
								break;
							default:
								$error = 'unhandled change type: '.$change_type.' (#84379487)';
								die($error);
								$is_success = false;
								$errors[] = $error;
						}

						$option->monthly_raw_cost = round($total_base_dollars_per_term);
						$option->monthly_total_cost = round($total_charged_dollars_per_term);
						$option->total_impressions = number_format($total_impressions, 0);

						$option->total_cpm = $total_impressions > 0 ? number_format(($total_base_dollars_per_term / $total_impressions) * 1000, 4) : 0;

						if($is_success)
						{
							$response_data['option'] = $option;
						}

						break;
					default:
						$error = 'unhandled scope: '.$scope.' (#54098745)';
						die($error);
						$is_success = false;
						$errors[] = $error;
				}
			}
			else
			{
				$is_success = false;
				$error = "Failed to decode option_json";
				$errors[] = $error;
			}
		}
		else
		{
			$is_success = false;
			$error = 'bad post data for recalculating option (#9874398)';
			$errors[] = $error;
		}

		$response_data['errors'] = $errors;
		$response_data['is_success'] = $is_success;

		$json_response = json_encode($response_data);
		echo $json_response;
	}

	//	output: json string conforming to vl_platform calling convention
	//	return: nothing
	public function ajax_recalculate_reach_frequency_for_option_engine()
	{
		$is_success = true;
		$response_data = array();
		$errors = array();

		$ad_plan_json = $this->input->post('ad_plan_json');

		if($ad_plan_json !== false)
		{
			$ad_plan = json_decode($ad_plan_json);
			if($ad_plan != null)
			{
				$response_data['reach_frequency'] = $this->proposal_gen_model->get_reach_frequency_data(
					$ad_plan->lap_id,
					$ad_plan->term,
					$ad_plan->period_type,
					$ad_plan->impressions,
					$ad_plan->gamma,
					$ad_plan->ip_accuracy,
					$ad_plan->demo_coverage,
					$ad_plan->retargeting
				);
			}
			else
			{
				$is_success = false;
				$error = "Failed to decode ad plan json (#0543727)";
				$errors[] = $error;
			}
		}
		else
		{
			$is_success = false;
			$error = 'bad post data for recalculating reach frequency (#4398743)';
			$errors[] = $error;
		}

		$response_data['errors'] = $errors;
		$response_data['is_success'] = $is_success;

		$json_response = json_encode($response_data);
		echo $json_response;
	}

	function are_custom_geos_enabled_for_this_user($role)
	{
		$partner_id = $this->get_partner_id_for_regions_query($role);
		$term = '%';
		$start = 0;
		$limit = 1;
		$can_see_regions = $this->mpq_v2_model->get_custom_regions_by_partner_and_search_term($term, $start, $limit, $partner_id);
		if($can_see_regions)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function get_partner_id_for_regions_query($role)
	{
		if($role == 'admin' || $role == 'ops' )
		{
			$partner_id = "%";
		}
		else
		{
			if($role == 'sales')
			{
				$partner_id = $this->tank_auth->get_partner_id($this->tank_auth->get_user_id());
			}
			else //business or public
			{
				//$business_id = $this->tank_auth->get_business_id($user_name);
				//$partner_id = $this->tank_auth->get_partner_id_by_advertiser_id($business_id);
				$partner_id = 'NULL';
			}
		}
		return $partner_id;
	}
	function get_notes_view_for_get_all_mpqs()
	{
		$is_success = true;
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$is_success = true;
			$data = "";
			$mpq_id = $this->input->post('mpq_id');
			if($mpq_id)
			{
				$mpq_query_response = $this->mpq_v2_model->get_data_for_submittal_notes_view($mpq_id);
				if($mpq_query_response)
				{
					$mss_data = $mpq_query_response['mss_data'];
					$mpq_data = $mpq_query_response['mpq_data'];
					$custom_regions = $mpq_query_response['rgn_data'];
					$geo_object = json_decode($mss_data['region_data']);
					$geo_object = (isset($geo_object->page)) ? $geo_object : $geo_object[0];
					$this->map->convert_old_flexigrid_format_object($geo_object);
					$geo_object->regions = $geo_object->ids->zcta;
					unset($geo_object->ids);

					$requester = new stdClass();
					$advertiser = new stdClass();
					$notes_snapshot = new stdClass();
					$requester->name = $mss_data['submitter_name'];
					$requester->website = $mss_data['submitter_agency_website'];
					$requester->email_address = $mss_data['submitter_email'];
					$requester->phone_number = $mss_data['submitter_phone'];
					$requester->notes = $mss_data['notes'];
					$advertiser->business_name = $mss_data['advertiser_name'];
					$advertiser->website_url = $mss_data['advertiser_website'];
					$notes_snapshot->snapshot_data = $mss_data['snapshot_data'];
					$notes_snapshot->snapshot_title = $mss_data['snapshot_title'];

					if($mss_data['is_proposal'] == 1 AND $mss_data['is_insertion_order'] == 0)
					{
						$mpq_type = 'proposal';
						$insertion_order_data = false;
						$options_data = $mpq_data;
					}
					else if($mss_data['is_proposal'] == 0 AND $mss_data['is_insertion_order'] == 1)
					{
						$mpq_type = 'insertion order';
						$options_data = false;
						$insertion_order_data = $mpq_data[0];
						$insertion_order_data->is_retargeting = $insertion_order_data->include_rtg;
						$insertion_order_data->num_impressions = $insertion_order_data->impressions;
						if($insertion_order_data->term_duration == 'specified')
						{
							$insertion_order_data->term_duration = 0;
						}
						else if($insertion_order_data->term_duration == 'on going')
						{
							$insertion_order_data->term_duration = -1;
						}
					}
					else
					{
						$data = "Unknown mpq type";
						$is_success = false;
					}
					$channel_array = $this->mpq_v2_model->get_iab_contextual_channels_by_mpq_id($mpq_id);
					$demo_array = explode ("_", $mss_data['demographic_data']);
					$demographics_settings = array(
						'Male' => $demo_array[0],
						'Female' => $demo_array[1],
						'Age under 18' => $demo_array[2],
						'Age 18 to 24' => $demo_array[3],
						'Age 25 to 34' => $demo_array[4],
						'Age 35 to 44' => $demo_array[5],
						'Age 45 to 54' => $demo_array[6],
						'Age 55 to 64' => $demo_array[7],
						'Age 65 and older' => $demo_array[8],
						'Income under 50k' => $demo_array[9],
						'Income 50k to 100k' => $demo_array[10],
						'Income 100k to 150k' => $demo_array[11],
						'Income over 150k' => $demo_array[12],
						'No college education' => $demo_array[13],
						'College education' => $demo_array[14],
						'Grad school education' => $demo_array[15],
						'People without children' => $demo_array[16],
						'Parents' => $demo_array[17]);
					$term_translate = array('monthly' => 'month', 'daily' => 'day', 'weekly' => 'week');
					$info_array = array(
						'notes_snapshot' => $notes_snapshot,
						'mpq_type' => $mpq_type,
						'advertiser' => $advertiser,
						'requester' => $requester,
						'channels' => $channel_array,
						'demographics' => $demographics_settings,
						'geo_mpq_data' => $geo_object,
						'option_data' => $options_data,
						'insertion_order_data' => $insertion_order_data,
						'term_translate' => $term_translate,
						'submit_view' => true,
						'notes_view' => true,
						'custom_regions' => $custom_regions,
						'adset_request_id' => $mss_data['adset_request_id'] ?: null
					);
					$data = $this->load->view('/mpq_v2/submission_summary_html', $info_array, true);
				}
				else
				{
					$data = "Failed to retrieve mpq data";
					$is_success = false;
				}
			}
			else
			{
				$data = "mpq not found";
				$is_success = false;
			}

			echo json_encode(array('view_data' => $data, 'success' => $is_success));
			return;
		}
		else
		{
			echo show_404();
		}
	}

	public function save_mpq_image($mpq_id)
	{
		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$ftp_config = $this->config->item('proposal_snapshots_ftp_config');
			$proposals_domain = 'proposals-ftp.brandcdn.com';

			$img_file = imagecreatefromstring(base64_decode(substr($this->input->post('img'), 22)));  //funky stuff here,
			$img_path = "{$mpq_id}_" . date("Y-m-d:H-i-s") . '.png';
			$local_path = "{$_SERVER['DOCUMENT_ROOT']}/assets/proposal_pdf/{$img_path}";
			$vldir_path = $this->get_mpq_vldir_path();
			$remote_path = "/{$vldir_path}/mpqimg/{$img_path}";
			$remote_link = "http://{$proposals_domain}/{$vldir_path}/mpqimg/{$img_path}";
			imagepng($img_file, $local_path, 9);
			$this->ftp->connect($ftp_config);
			if($this->ftp->upload($local_path, $remote_path, '', '775'))
			{
				$img_save_response = $this->mpq_v2_model->write_mpq_image($mpq_id, $remote_link, "Geographic Summary");
				if($img_save_response)
				{
					echo json_encode(array('is_success' => true, 'message' => 'Image saved successfully'));
				}
				else
				{
					echo json_encode(array('is_success' => false, 'message' => 'Image uploaded but not saved'));
				}
			}
			else
			{
				echo json_encode(array('is_success' => false, 'message' => 'Image not uploaded'));
			}
			$this->ftp->close();
			unlink($local_path);
		}
		else
		{
			echo show_404();
			return;
		}
	}

	private function get_mpq_vldir_path()
	{
		$vldir_path = 'proposal';
		switch(ENVIRONMENT)
		{
		case 'production':
			break;
		case 'staging':
			$vldir_path .= '/stage';
			break;
		default:
			$vldir_path .= '/dev';
			break;
		}
		return $vldir_path;
	}

	public function get_contextual_iab_categories()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$contextual_response = select2_helper($this->mpq_v2_model, 'get_iab_contextual_channels_by_search_term', $this->input->post());
			$contextual_array = array();

			if (!empty($contextual_response['results']) && !$contextual_response['errors'])
			{
				$contextual_array['more'] = $contextual_response['more'];

				for($i = 0; $i < $contextual_response['real_count']; $i++)
				{
					$contextual_array['result'][] = array(
						'id' => $contextual_response['results'][$i]['id'],
						'text' => $contextual_response['results'][$i]['tag_copy']
					);
				}
			}
			else
			{
				$contextual_array['errors'] = $contextual_response['errors'];
				$contextual_array['result'] = array();
			}

			echo json_encode($contextual_array);
		}
		else
		{
			show_404();
		}
	}

	public function get_industries()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			// filter by the cp_strategies_join_freq_industry_tags table
			$strategy_id = $this->input->post('strategy_id');
			$added_args = array();
			if ($strategy_id)
			{
				$allowed_industries = $this->strategies_model->get_industry_info_by_strategy($strategy_id);
				if (!empty($allowed_industries))
				{
					$added_args[] = $allowed_industries;
				}
			}
			$industry_response = select2_helper($this->mpq_v2_model, 'get_industries_by_search_term', $this->input->post(), $added_args);
			$industry_array = array();

			if (!empty($industry_response['results']) && !$industry_response['errors'])
			{
				$industry_array['more'] = $industry_response['more'];

				for($i = 0; $i < $industry_response['real_count']; $i++)
				{
					$industry_array['results'][] = array(
						'id' => $industry_response['results'][$i]['freq_industry_tags_id'],
						'text' => $industry_response['results'][$i]['name']
					);
				}
			}
			else
			{
				$industry_array['errors'] = $industry_response['errors'];
				$industry_array['results'] = array();
			}

			echo json_encode($industry_array);
		}
		else
		{
			show_404();
		}
	}

	public function get_selected_industry()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$industry_response = select2_helper($this->mpq_v2_model, 'get_industries_by_search_term', $this->input->post());
			$industry_array = array();

			if (!empty($industry_response['results']) && !$industry_response['errors'])
			{
				$industry_array['more'] = $industry_response['more'];

				for($i = 0; $i < $industry_response['real_count']; $i++)
				{
					$industry_array['result'][] = array(
						'id' => $industry_response['results'][$i]['freq_industry_tags_id'],
						'text' => $industry_response['results'][$i]['name']
					);
				}
			}
			else
			{
				$industry_array['errors'] = $industry_response['errors'];
				$industry_array['result'] = array();
			}
			echo json_encode($industry_array);
		}
		else
		{
			show_404();
		}
	}

	public function get_custom_regions()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('admin', 'ops', 'sales', 'business', 'creative', 'public');
			$post_variables = array('q', 'page_limit', 'page');

			$response = vl_verify_ajax_call($allowed_roles, $post_variables);
			if(!$response['is_success'])
			{
				echo json_encode($response);
				return;
			}
			else
			{
				$username = $this->tank_auth->get_username();
				if($username)
				{
					$role = $this->tank_auth->get_role($username);
					$partner_id = $this->get_partner_id_for_regions_query($role);
				}
				else
				{
					$role = 'public';
					$partner_id = 'NULL';
				}

				$args = array($partner_id);
				$regions_response = select2_helper($this->mpq_v2_model, 'get_custom_regions_by_partner_and_search_term', $response['post'], $args);
				$regions_array = array();

				if(!empty($regions_response['results']) && !$regions_response['errors'])
				{
					$regions_array['more'] = $regions_response['more'];

					for($i = 0; $i < $regions_response['real_count']; $i++)
					{
						$regions_array['result'][] = array(
							'id' => $regions_response['results'][$i]['id'],
							'text' => $regions_response['results'][$i]['region_name']
						);
					}
				}
				else
				{
					$regions_array['errors'] = $regions_response['errors'];
					$regions_array['result'] = array();
				}

				echo json_encode($regions_array);
			}
		}
		else
		{
			show_404();
		}
	}

    public function get_tv_zones()
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $allowed_roles = array('admin', 'ops', 'sales');
            $zone_id = 1;

            $post_variables = array('q', 'page_limit', 'page');

            $response = vl_verify_ajax_call($allowed_roles, $post_variables);
            $mpq_id = $this->input->post('mpq_id');

            if(!$response['is_success'])
            {
                echo json_encode($response);
                return;
            }
            else
            {
                $args = array("mpq_id" => $mpq_id, 'zone_id' => $zone_id);
                $zones_response = select2_helper($this->mpq_v2_model, 'get_zones_for_partner_by_mpq_and_zone_id', $response['post'], $args);
                $zones_array = array();

                if(!empty($zones_response['results']) && !$zones_response['errors'])
                {
                    $zones_array['more'] = $zones_response['more'];

                    for($i = 0; $i < $zones_response['real_count']; $i++)
                    {
                        $zones_array['results'][] = array(
						'id' => $zones_response['results'][$i]['region_id'],
						'text' => $zones_response['results'][$i]['region_name']
					);
                    }
                }
                else
                {
                    $zones_array['errors'] = $zones_response['errors'];
                    $zones_array['results'] = array();
                }

                echo json_encode($zones_array);
            }
        }
        else
        {
            show_404();
        }
    }

	public function get_creative_requests()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);
			$isGroupSuper = $this->tank_auth->get_isGroupSuper($username);

			if (in_array(strtolower($role), array('admin', 'ops', 'sales')))
			{
				$partner_users = array();

				if($role == 'sales')
				{
					$partner_users = $this->tank_auth->get_sales_users_by_partner_hierarchy($user_id, $isGroupSuper);
					$partner_users[] = array('id'=>$user_id);
				}

				$post_array = $this->input->post();
				$post_array['q'] = str_replace(" ", "%", $post_array['q']);

				$creative_response = select2_helper($this->mpq_v2_model, 'get_creative_requests_by_search_term', $post_array, array($role, $partner_users));

				if (empty($creative_response['results']) || $creative_response['errors'])
				{
					$creative_response['results'] = array();
				}

				echo json_encode($creative_response);
			}
			else
			{
				echo json_encode(array('errors' => "Not authorized - #900020"));
			}
		}
		else
		{
			show_404();
		}
	}

    public function get_prices_by_zones_and_packs_for_tv_request(){
        if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in()){
            $return_array = array('is_success' => true, 'errors' => "", 'data' => array());

            $zones = $this->input->post('zones');
            $packs = $this->input->post('packs');
            if($zones !== false && $packs !== false && is_array ($zones) && is_array($packs)){
                $result = $this->mpq_v2_model->get_pricing_by_zones_and_packs_for_tv($zones, $packs);
                $return_array['data'] = $result;
            }else{
                $return_array['is_success'] = false;
                $return_array['errors'] = "Error 182500: unable to get pricing information";
            }
            echo json_encode($return_array);
        }
        else
        {
            show_404();
        }
    }

	public function get_single_creative_request()
	{
		if ($this->tank_auth->is_logged_in())
		{
			$adset_id = $this->input->post('id', true);
			$result = $this->mpq_v2_model->get_single_creative_request($adset_id);
			$response = array();

			$response['is_success'] = true;
			$response['errors'] = array();

			if ($result)
			{

				// Fix for old banner intakes entered as objects
				if (gettype($result->creative_files) == "object")
				{
					$arr = array();
					foreach ($result->creative_files as $file)
					{
						$arr[] = $file;
					}
					$result->creative_files = $arr;
				}

				$response['adset'] = $result;
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] =  'Cannot find selected adset - #900021';
			}

			echo json_encode($response);
		}
		else
		{
			show_404();
		}
	}

	public function get_zips_from_selected_regions_and_save()
	{
		$json_response = array("error" => false, "error_text" => "", "response" => "", "session_expired" => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$mpq_id = $this->input->post('mpq_id');
			$session_id = $this->session->userdata('session_id');
			$unique_display_id = $this->input->post('unique_display_id');
			if($unique_display_id === false && $mpq_id !== false && $session_id !== false)
			{
				$session_result = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
				if($session_result !== $session_id)
				{
					$json_response['session_expired'] = true;
					echo json_encode($json_response);
					return;
				}
			}

			$region_ids = $this->input->post('custom_region_ids');
			$location_id = empty($this->input->post('location_id')) ? 0 : $this->input->post('location_id');
			if($region_ids)
			{
				$region_array = explode(',', $region_ids);
				$regions_response = $this->mpq_v2_model->get_zips_from_selected_regions_by_id_array($region_array);
				if($regions_response)
				{
					$this->mpq_v2_model->save_regions_to_join_table($region_array, $session_id, $location_id, $mpq_id);
					$json_response['response'] = $regions_response;
				}
				else
				{
					$json_response['error'] = true;
					$json_response['error_text'] = "No records found for selected regions";
				}
			}
			else
			{
				$json_response['error'] = true;
				$json_response['error_text'] = "No regions found";
			}
			echo json_encode($json_response);
		}
		else
		{
			show_404();
		}
	}

	public function remove_selected_custom_regions()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array("is_success" => true, "errors" => "", "response" => "", "session_expired" => false);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				$location_id = $this->input->post('location_id');
				$session_id = $this->session->userdata('session_id');
				if($mpq_id !== false && $location_id !== false && $session_id !== false)
				{
					$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
					if($session_response === $session_id)
					{
						$query_response = $this->mpq_v2_model->remove_selected_custom_regions_with_mpq_id($mpq_id, $location_id);
						if(!$query_response)
						{
							$response['is_success'] = false;
							$response['errors'] = "Error 501500: unable to remove regions";
						}
					}
					else
					{
						$response['session_expired'] = true;
					}
				}
				else
				{
					$response['is_success'] = false;
					$response['errors'] = "Error 501400: Invalid request parameters";
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'] = "Error 501403: user logged out or not permitted";
			}
			echo json_encode($response);
		}
		else
		{
			show_404();
		}
	}

	// show the single page version of the mpq
	public function embed_single_page($mpq_type = null, $token = null)
	{
		if (empty($mpq_type) || empty($token))
		{
			show_404();
		}

		$errors = array();
		$is_success = true;

		try {
			$decoded = JWT::decode($token, null);
		}
		catch (Exception $e)
		{
			show_404();
		}

		$user = (array) $this->tank_auth->get_user_by_id($decoded->aud);

		if ($user)
		{
			$user['partner'] = $this->tank_auth->get_partner_info($user['partner_id']);

			$session_id = $this->session->userdata('session_id');
			$mpq_session_data = $this->tank_auth->get_session_by_mpq_session_id($decoded->mpq_session_id, $session_id);

			$data = array('user' => $user, 'mpq_session_data' => $mpq_session_data, 'session_id' => $session_id);
			$data['role'] = strtolower($user['role']) == "business" ? "advertiser" : "agency";

			$options_data = array();

			$has_session_data = false;
			if($mpq_session_data !== false && $mpq_session_data->is_submitted != 1)
			{
				$has_session_data = true;

				$data['geo_radius'] = '';
				$data['geo_center'] = '';

				$options_data = $this->mpq_v2_model->get_options_from_mpq_by_id($decoded->mpq_session_id);
			}
			else
			{
				show_404();
			}

			//let's set up the geo view if this user can use custom geos
			$role = 'public';
			$data['custom_geos_enabled'] = $this->are_custom_geos_enabled_for_this_user($role);
			$this->add_geographic_view_variables($data, $mpq_session_data->region_data);
			$this->add_demographics_view_variables($data, $mpq_session_data->demographic_data);
			$this->add_channels_view_variables($data, $mpq_session_data->selected_iab_categories);
			//$this->add_industries_view_variables($data, $mpq_session_data->selected_industries);
			$this->add_options_view_variables($data, $options_data);

			$data['partner_mpq_can_see_rate_card'] = ($user['partner_id'] == 3 OR $user['partner_id'] == 11 OR $user['partner_id'] == 13 OR $user['partner_id'] == 14) ? false : true;
			$data['partner_mpq_can_submit_proposal'] = true;

			$data['user_role'] = $role;
			$data['is_logged_in'] = false;

			$data['creative_requester'] = isset($creative_requester) ? array('id'=>$creative_requester->id, 'email'=>$creative_requester->email) : FALSE;

			$session_id = $this->session->userdata('session_id');
			$data['session_id'] = $session_id;

			$data['has_session_data'] = $has_session_data;

			$data['title'] = 'MPQ';

			if ($mpq_type == "insertion_order")
			{
				$this->load->view('insertion_order/header', $data);
				$this->load->view('insertion_order/body', $data);
				$this->load->view('insertion_order/footer', $data);
			}
			else if ($mpq_type == "proposal")
			{
				$this->load->view('proposal/header', $data);
				$this->load->view('proposal/body', $data);
				$this->load->view('proposal/footer', $data);
			}
			else
			{
				show_404();
			}

		}
		else
		{
			show_404();
		}
	}

	public function embed_submitted($mpq_type = null)
	{
		if (empty($mpq_type))
		{
			show_404();
		}

		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$data['cc_email'] = $this->input->post('cc_email');
			$submitted_data = $this->input->post('submitted_data');
			if($submitted_data !== false)
			{
				$data = array();
				$submitted_data = json_decode($submitted_data);
				foreach($submitted_data as $key=>$value)
				{
					if(is_string($key))
					{
						$data[$key] = $value;
					}
					else
					{
						die("expected 'key' to only be of type string");
					}
				}

				$data['custom_regions'] = $this->mpq_v2_model->get_custom_regions_by_mpq_id($submitted_data->mpq_id);
				$data['submit_view'] = true;
				$data['term_translate'] = array('monthly' => 'month', 'daily' => 'day', 'weekly' => 'week');

				$active_feature_button_id = 'mpq';
				$data['title'] = 'Review MPQ Submission';
				$data['submitted_data'] = $submitted_data;
				$data['insertion_order_id'] = $this->input->post('insertion_order_id');

				if ($mpq_type == "insertion_order")
				{
					$this->load->view('insertion_order/submission_summary', $data);
				}
				else if ($mpq_type == "proposal")
				{
					$this->load->view('proposal/submission_summary', $data);
				}
			}
			else
			{
				redirect("/embed/insertion_order"); //submit data is incorrect
			}
		}
		else
		{
			redirect("/embed/insertion_order");
		}
	}

	public function rfp($unique_display_id = false)
	{
		// Login stuff
		$this->vl_platform->has_permission_to_view_page_otherwise_redirect('proposals', 'rfp');

		if (!$unique_display_id || !$this->mpq_v2_model->is_valid_unique_display_id($unique_display_id))
		{
			redirect('/rfp/create');
		}

		$data = array();
		$data['is_rfp'] = true;
		$data['title'] = 'RFP';

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

		$mpq_session = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id, $unique_display_id);
		$parent_proposal_id = $this->mpq_v2_model->get_parent_proposal_id_by_unique_display_id($unique_display_id);
		$existing_proposal_id = $this->mpq_v2_model->get_proposal_id_by_unique_display_id($unique_display_id) ?: $parent_proposal_id;

		if (empty($mpq_session)) // The MPQ session doesn't exist because we've come straight from /proposals
		{
			// this also unlocks the MPQ session from user's session_id
			$this->mpq_v2_model->initialize_mpq_session_from_existing_proposal($session_id, $user_id, $existing_proposal_id, 'edit');
			$mpq_session = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
		}

		if (!empty($existing_proposal_id) && $role === 'sales')
		{
			// determine whether user has access to edit the RFP
			if (!$this->proposals_model->sales_user_has_access_to_rfp($user_id, $is_super, $existing_proposal_id))
			{
				redirect('proposals');
			}
		}

		// If it's an old MPQ session with no strategy selected, we'll force the user back to the gate
		if ($mpq_session['strategy_id'] === null)
		{
			redirect('rfp/create/'.$unique_display_id);
		}


		// Set up default data
		$data['industry_data'] = '{}';
		$data['iab_category_data'] = array();
		$data['rooftops_data'] = '[]';
		$data['rfp_tv_zones_data'] = '[]';
		$data['rfp_keywords_data'] = '[]';
		$data['rfp_keywords_clicks'] = "";
		$data['is_rfp'] = true;
		$data['is_preload'] = true;
		$data['current_user_id'] = $user_id;
		$data['unique_display_id'] = $unique_display_id;

		// Geo Data
		$data['is_existing_rfp'] = false;
		$data['existing_locations'] = array();
		$data['custom_regions_data'] = array();
		$data['rfp_options_data'] = array();
		$data['max_locations_for_rfp'] = $this->config->item('max_locations_per_rfp');
		$data['geo_radius'] = '';
		$data['geo_center'] = '';

		$custom_regions_response = $this->mpq_v2_model->get_rfp_preload_custom_regions_by_mpq_id($mpq_session['id']);
		if($custom_regions_response !== false)
		{
			$data['custom_regions_data'] = $custom_regions_response;
		}

		$region_data = json_decode($mpq_session['region_data'], true);
		$this->map->convert_old_flexigrid_format($region_data);
		$data['existing_locations'] = json_encode($region_data);

		if ($existing_proposal_id)
		{
			$preload_data_response = $this->proposal_gen_model->get_preload_rfp_data_by_proposal_id($existing_proposal_id);
			if(!empty($preload_data_response))
			{
				$rfp_zones_data = $this->mpq_v2_model->get_rfp_tv_zones_by_mpq_id($preload_data_response['id']);
				if(count($rfp_zones_data) > 0)
				{
					$data['rfp_tv_zones_data'] = json_encode($rfp_zones_data);
				}
				$data['rfp_options_data'] = $this->mpq_v2_model->get_mpq_options_by_mpq_id($preload_data_response['id']);

				$data['is_existing_rfp'] = true;

				if($preload_data_response['rooftops_data'] == null)
				{
					$data['rooftops_data'] = "[]";
				}
				else
				{
					$data['rooftops_data'] = $preload_data_response['rooftops_data'];
				}

				$rfp_keywords_data = $this->mpq_v2_model->get_rfp_keywords_data($preload_data_response['id']);
				if(!empty($rfp_keywords_data))
				{
					$data['rfp_keywords_data'] = $rfp_keywords_data['search_terms'];
					$data['rfp_keywords_clicks'] = $rfp_keywords_data['clicks'];
				}

			}

			$data['is_preload'] = true;
			$data['iab_category_data'] = $this->mpq_v2_model->get_iab_category_data_by_existing_proposal_id($existing_proposal_id);
		}

		$data['advertiser_org_name'] = $mpq_session['advertiser_name'];
		$data['advertiser_website'] = $mpq_session['advertiser_website'];
		$data['proposal_name'] = $mpq_session['proposal_name'];
		$data['submitter_name'] = $mpq_session['submitter_name'];
		$data['submitter_email'] = $mpq_session['submitter_email'];
		$account_executive_id = $mpq_session['owner_user_id'] ?: $user_id;

		$partner_id = $this->tank_auth->get_partner_id($account_executive_id) ?: 1;

		// Get product data
		$data['products'] = $this->strategies_model->get_products_by_strategy_id($mpq_session['strategy_id']);
		if($existing_proposal_id)
		{
			$data['products'] = $this->mpq_v2_model->populate_products_with_submitted_data($data['products'], $existing_proposal_id);
		}

		$data['raw_discount'] = 10;
		$data['raw_discount_name'] = "Discount";
		$data['term_duration'] = 6;
		$data['has_political'] = false;

		foreach($data['products'] as $key => $value)
		{
			if ($value['is_political'])
			{
				$data['has_political'] = true;
			}
			if($value['product_type'] == 'discount')
			{
				$discount_obj = json_decode($value['definition'], true);
				$data['raw_discount'] = $discount_obj['discount_percent'];
				if(!empty($discount_obj['discount_name']))
				{
					$data['raw_discount_name'] = $discount_obj['discount_name'];
				}
			}
		}

		$data['political_segment_data'] = $this->mpq_v2_model->get_political_segment_data($existing_proposal_id) ?: $this->mpq_v2_model->get_political_segment_data();

		$industry_data = $this->mpq_v2_model->get_industry_data_by_id($mpq_session['industry_id']);
		if($industry_data !== false)
		{
			$data['industry_data'] = "{'id':".$industry_data['freq_industry_tags_id'].",'text':'".$industry_data['name']."'}";
			$data['industry_name'] = $industry_data['name'];
		}

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

		$this->add_geographic_view_variables($data, $mpq_session['region_data']);
		$this->add_demographics_view_variables($data, $mpq_session['demographic_data']);

		$data['user_role'] = $mpq_user_role;
		$data['is_logged_in'] = true;

		$data['creative_requester'] = isset($creative_requester) ? array('id'=>$creative_requester->id, 'email'=>$creative_requester->email) : FALSE;

		$this->vl_platform->show_views(
			$this,
			$data,
			'proposals',
			'rfp/rfp_body',
			'rfp/rfp_header',
			NULL,
			'rfp/rfp_footer',
			NULL
		);
	}

	public function rfp_clone($unique_display_id = false)
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

	public function rfp_gate($unique_display_id = false)
	{
		// Login stuff
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

		if($proposal_name == "")$proposal_name = null;


		// TODO: actual validation
		if ($owner_id && $advertiser_name !== false && $advertiser_website !== false && $industry_id && $strategy_id)
		{
			if ($unique_display_id !== false)
			{
				// Load the proposal being either edited or cloned
				$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
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
					$proposal_name
				);
			}
			else
			{
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
					$proposal_name
				);
			}
			redirect('rfp/'.$unique_display_id);
		}
		else // load the gate
		{

			$rfp_gate_preload_data = array(
				'owner_user_id' => false,
				'owner_is_submitter' => true,
				'advertiser_name' => false,
				'advertiser_website' => false,
				'proposal_name' => false,
				'strategy_id' => false,
				'industry_id' => false,
				'industry_data' => '{}'
			);

			if ($unique_display_id)
			{
				$rfp_gate_preload_response = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);

				if (!$rfp_gate_preload_response)
				{
					redirect('rfp/create');
				}

				$rfp_gate_preload_data = $rfp_gate_preload_response;
				$industry_data = $this->mpq_v2_model->get_industry_data_by_id($rfp_gate_preload_data['industry_id']);
				if($industry_data !== false)
				{
					$rfp_gate_preload_data['industry_data'] = "{'id':".$industry_data['freq_industry_tags_id'].",'text':'".$industry_data['name']."'}";
				}
			}

			$data = array_merge($data, $rfp_gate_preload_data);

			$partner_id = $this->tank_auth->get_partner_id($data['owner_user_id'] ?: $user_id) ?: 1;
			$data['strategies'] = $this->strategies_model->get_strategy_info($partner_id, $data['industry_id']);
			$data['user_id'] = $user_id;
			$data['unique_display_id'] = $unique_display_id;
			$data['account_executive_data'] = $this->mpq_v2_model->get_account_executive_data_by_user_id($data['owner_user_id'] ?: $user_id);

			$this->vl_platform->show_views(
				$this,
				$data,
				'proposals',
				'rfp/rfp_gate_html',
				'rfp/rfp_gate_header',
				NULL,
				'rfp/rfp_gate_footer',
				NULL
			);
		}
	}

	public function io($unique_display_id = false)
	{
		$errors = array();
		$is_success = true;

		$is_logged_in = false;

		$redirect_string = $this->vl_platform->get_access_permission_redirect('io');
		if($redirect_string == 'login')
		{
			$this->session->set_userdata('referer', 'io');
			redirect($redirect_string);
		}
		else if($redirect_string == '')
		{
			$is_logged_in = true;
		}
		else
		{
			redirect($redirect_string);
		}

		$user_name = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($user_name);

		preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
		if (count($matches)>1)
		{
			$version = $matches[1];
			if($version <=8)
			{
				$this->load->view('mpq_v2/old_browser_redirect');
				return;
			}
		}

		$active_feature_button_id = 'io';
		$data = array();

		$owner_id = $this->tank_auth->get_user_id();
		if($owner_id == 0 || $owner_id == '')
		{
			$owner_id = null;
		}

		$session_id = $this->session->userdata('session_id');
		$data['session_id'] = $session_id;

		$data['is_preload'] = false;
		$data['industry_data'] = '{}';
		$data['iab_category_data'] = [];
		$data['flights_data'] = [];
		$data['creatives_data'] = [];
		$data['is_rfp'] = false;

		$get_initialize_data_flag = true;

		$mpq_id = false;
		$option_id = false;
		$post_account_executive = false;
		$submission_method = false;
		$io_source = false;

		// Clicked Create IO button on /proposals page
		if ($this->input->post('mpq_id'))
		{
			$mpq_id = $this->input->post('mpq_id');
			$option_id = $this->input->post('option_id');
			$post_account_executive = $this->input->post('new_account_executive');
			$submission_method = $this->input->post('submission_method');
			$io_source = $this->input->post('source');

			if($io_source == "io" or $io_source == "rfp")
			{
				$io_post = array(
					'mpq_id' => $mpq_id,
					'option_id' => $option_id,
					'account_executive' => $post_account_executive,
					'submission_method' => $submission_method,
					'source' => $io_source
					);
				$this->session->set_userdata('io_post_data', $io_post);
				redirect('io');
			}
		}
		elseif ($unique_display_id)
		{
			$mpq_id = $this->mpq_v2_model->get_mpq_id_by_unique_display_id($unique_display_id);

			if ($mpq_id)
			{
				$io_post = array(
					'mpq_id' => $mpq_id,
					'option_id' => null,
					'account_executive' => null,
					'submission_method' => 'edit',
					'source' => 'io'
					);
				$this->session->set_userdata('io_post_data', $io_post);
				redirect('io/old');
			}
		}
		elseif ($this->input->post('source') === 'new')
		{
			$partner_id = $this->tank_auth->get_partner_id($owner_id);
			if($partner_id == false)
			{
				$partner_id = 1; //DEFAULT
			}
			$this->mpq_v2_model->unlock_mpq_session_by_session_id($session_id);
			$mpq_id = $this->mpq_v2_model->initialize_mpq_session_from_scratch_for_io($session_id, $owner_id, $partner_id);
			$option_id = false;
			$post_account_executive = $owner_id;
			$submission_method = 'edit';
			$io_source = 'new';
			$io_post = array(
				'mpq_id' => $mpq_id,
				'option_id' => $option_id,
				'account_executive' => $post_account_executive,
				'submission_method' => $submission_method,
				'source' => 'io'
			);
			$this->session->set_userdata('io_post_data', $io_post);
			redirect('io');
		}
		elseif ($this->session->userdata('io_post_data'))
		{
			$existing_io_post_data = $this->session->userdata('io_post_data');

			if ($existing_io_post_data)
			{
				$mpq_id = isset($existing_io_post_data['mpq_id']) ? $existing_io_post_data['mpq_id']: false;
				$option_id = isset($existing_io_post_data['option_id']) ? $existing_io_post_data['option_id']: false;
				$post_account_executive = isset($exiting_io_post_data['account_executive']) ? $exiting_io_post_data['account_executive'] : false;
				$submission_method = isset($existing_io_post_data['submission_method']) ? $existing_io_post_data['submission_method'] : false;
				$io_source = isset($existing_io_post_data['source']) ? $existing_io_post_data['source'] : false;
			}
		}

		if($mpq_id === false)
		{
			$is_submitted_result = $this->mpq_v2_model->get_mpq_id_and_is_submitted_by_session_id($session_id);
			if($is_submitted_result !== false && $is_submitted_result['is_submitted'] == 1)
			{
				$get_initialize_data_flag = false;
				$mpq_id = $is_submitted_result['id'];
				$new_mpq_id = $is_submitted_result['id'];
			}
		}

		if(($io_source == "io") && $mpq_id !== false && $submission_method !== false)
		{
			$get_initialize_data_flag = false;
			if($submission_method == 'edit')
			{
				$new_mpq_id = $mpq_id;
			}
		}

		if($mpq_id !== false)
		{
			$this->mpq_v2_model->unlock_mpq_session_by_session_id($session_id, $mpq_id);
			$io_editable_details = $this->mpq_v2_model->get_io_editable_details($mpq_id);

			if($io_editable_details == false or ($io_editable_details['io_lock_timestamp'] !== null and (strtotime("now") - strtotime($io_editable_details['io_lock_timestamp']))/60 < 30))
			{
				$this->session->set_userdata('io_locked', true);
				redirect('insertion_orders');
			}
			$data['is_preload'] = true;
			$session_response = false;
			if($get_initialize_data_flag == true)
			{
				$unique_display_id = $this->mpq_v2_model->generate_insertion_order_unique_id();
				$session_response = $this->mpq_v2_model->initialize_mpq_session_from_existing_for_io($session_id, $mpq_id, $unique_display_id, $option_id);
				$new_mpq_id = $session_response;

				$io_post = array(
					'mpq_id' => $new_mpq_id,
					'option_id' => false,
					'account_executive' => false,
					'submission_method' => 'edit',
					'source' => 'io'
					);
				$this->session->set_userdata('io_post_data', $io_post);
				redirect('io');
			}
			else
			{
				$update_response = $this->mpq_v2_model->update_mpq_with_session_id($new_mpq_id, $session_id);
			}
			if($session_response !== false or $get_initialize_data_flag == false)
			{
				$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
				$products_data = $this->mpq_v2_model->get_product_information_for_io($new_mpq_id);
				$status_data = $this->mpq_v2_model->get_io_status_by_mpq_id($mpq_id);

				$flights_data = $this->mpq_v2_model->get_flights_defined_by_mpq_id($mpq_id);

				$data['time_series_data'] = array();
				if($flights_data !== false)
				{
					foreach($flights_data as &$flight)
					{
						$flight['end_date'] = date('m/d/Y', strtotime($flight['end_date'] . ' -1 day'));
					}

					$time_series_data = $this->mpq_v2_model->get_time_series_summary_by_mpq_id($mpq_id);
					if($time_series_data !== false)
					{
						$data['time_series_data'] = $time_series_data;
					}
					$data['flights_data'] = $flights_data;
				}
				$creatives_data = $this->mpq_v2_model->get_creatives_defined_by_mpq_id($mpq_id);
				if($creatives_data !== false)
				{
					$data['creatives_data'] = $creatives_data;
				}

				$data['has_geofencing'] = false;
				$data['geofencing_data'] = [];
				foreach($products_data as $product)
				{
					if(array_key_exists('has_geofencing', $product) && $product['has_geofencing'])
					{
						$data['has_geofencing'] = true;
						$data['geofencing_data'] = $product['geofencing_data'];
						break;
					}
				}

				$data['mpq_id'] = $new_mpq_id;
				$data['io_product_data'] = $products_data;
				$data['mpq_type'] = $mpq_session_data['mpq_type'];
				$data['user_role'] = $role;
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
				$data['submitter_name'] = $mpq_session_data['submitter_name'];
				$data['submitter_email'] = $mpq_session_data['submitter_email'];
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
					$account_executive_id = $owner_id;
				}

				$original_owner_id = $mpq_session_data['original_owner_id'] ?: $owner_id;
				$partner_id = $this->tank_auth->get_partner_id($original_owner_id);
				if($partner_id == false)
				{
					$partner_id = 1; //DEFAULT
				}

				$data['default_products'] = $this->strategies_model->get_products_by_strategy_id($mpq_session_data['strategy_id']);
				$data['default_products'] = array_filter($data['default_products'], function($product){
					return $product['can_become_campaign'];
				});

				$data['geo_radius'] = '';
				$data['geo_center'] = '';

				$industry_data = $this->mpq_v2_model->get_industry_data_by_id($mpq_session_data['industry_id']);
				if($industry_data !== false)
				{
					$data['industry_data'] = "{'id':".$industry_data['freq_industry_tags_id'].",'text':'".$industry_data['name']."'}";
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

				$region_data = json_decode($mpq_session_data['region_data'], true);
				$this->map->convert_old_flexigrid_format($region_data);

				$data['geofence_inventory'] = 0;
				array_walk($region_data, function(&$values){
					$values['geofences'] = [];
				});

				$geofences = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($new_mpq_id);
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
					$data['geofence_inventory'] = $this->mpq_v2_model->calculate_geofencing_max_inventory($new_mpq_id);
				}
				$data['existing_locations'] = json_encode($region_data);
			}
			else
			{
				show_error('There was a problem creating your insertion order', 500);
			}
		}
		else
		{
			redirect('insertion_orders');
		}

		$account_executive_data = $this->mpq_v2_model->get_account_executive_data_by_user_id($account_executive_id);
		if($account_executive_data !== false)
		{
			$data['account_executive_data'] = $account_executive_data;
		}
		else
		{
			//error
		}

		$mpq_session_data = $this->mpq_v2_model->get_mpq_session_data($session_id);

		$this->add_geographic_view_variables($data, $mpq_session_data->region_data);
		$this->add_demographics_view_variables($data, $mpq_session_data->demographic_data);

		$data['max_locations_for_rfp'] = $this->config->item('max_locations_per_rfp');
		$data['google_access_token_rfp_io'] = $this->config->item('google_access_token_rfp_io');
		$data['title'] = 'Insertion Order';

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
			'rfp/io_body',
			'rfp/io_header',
			NULL,
			'rfp/io_footer',
			NULL
			// , false
		);
	}

	public function rfp_success()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$proposal_id = $this->input->post('id');
			if(!empty($proposal_id))
			{
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
		}
		redirect('/rfp');
		return;
	}

	public function get_account_executives_for_rfp()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array();
			$allowed_roles = array('sales', 'admin', 'ops');
			$post_variables = array('q', 'page_limit', 'page');
			$response = vl_verify_ajax_call($allowed_roles, $post_variables);

			if($response['is_success'])
			{
				$parameters = array();
				$add_args = array();
				$add_args['user_id'] = $this->tank_auth->get_user_id();
				$username = $this->tank_auth->get_username();
				$add_args['role'] = $this->tank_auth->get_role($username);
				$parameters['q'] = $this->input->post('q');
				$parameters['page_limit'] = $this->input->post('page_limit');
				$parameters['page'] = $this->input->post('page');
				$account_executive_response = select2_helper($this->mpq_v2_model, 'get_select2_account_executive_data_for_rfp', $parameters, $add_args);

				if($this->session->userdata('is_demo_partner'))
				{
					$u_id = $add_args['user_id'];
					$key = array_search($u_id, array_column($account_executive_response['results'], 'id'));
					$logged_user = $account_executive_response['results'][$key];
					$account_executive_response['results'] = '';
					$account_executive_response['results'][$key] = $logged_user;
				}
				echo json_encode($account_executive_response);
			}
			else
			{
				echo json_encode(array('errors' => "Not authorized - #903813"));
			}
		}
		else
		{
			show_404();
		}
	}

	public function get_rfp_summary_html()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'html_data' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('rfp_id');
				if($mpq_id !== false)
				{
					$summary_html_result = $this->mpq_v2_model->get_summary_html_by_mpq_id($mpq_id);
					if(!empty($summary_html_result))
					{
						$return_array['html_data'] = $summary_html_result;
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 562342: unable to find summary data";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 562341: unknown rfp id";
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

	function is_rfp_product_set_different()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'is_compare_success' => true);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$selected_user = $this->input->post('selected_user');
				$existing_user = $this->input->post('existing_user');
				if($selected_user !== false && $existing_user !== false)
				{
					$response = $this->mpq_v2_model->compare_partner_products_by_user_ids($selected_user, $existing_user);
					if($response['is_success'] == true)
					{
						$return_array['is_compare_success'] = $response['is_compare_success'];

					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 694500: Unable to retrieve product data for selected user";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 694400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 693403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	function is_rfp_editable()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'is_editable_success' => true);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				if($mpq_id !== false)
				{
					$return_array['is_editable_success'] = $this->mpq_v2_model->does_rfp_have_no_children($mpq_id);

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

	public function change_io_product_set()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$product_id = $this->input->post('product_id');
			$product_status = $this->input->post('product_status');
			$mpq_id = $this->input->post('mpq_id');

			if ($product_id && $product_status && $mpq_id)
			{
				$product_status = $product_status === "true";

				if ($product_status)
				{
					$this->add_product_to_io($mpq_id, $product_id);
				}
				else
				{
					$this->mpq_v2_model->remove_product_from_io($mpq_id, $product_id);
				}

				redirect('io');
			}
			else
			{
				show_404();
			}
		}
		else
		{
			show_404();
		}
	}

	public function ajax_change_io_product_set()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$product_id = $this->input->post('product_id');
			$product_status = $this->input->post('product_status');
			$mpq_id = $this->input->post('mpq_id');

			if ($product_id && $product_status && $mpq_id)
			{
				$product_status = $product_status === "true";

				if ($product_status)
				{
					$this->add_product_to_io($mpq_id, $product_id);
				}
				else
				{
					$this->mpq_v2_model->remove_product_from_io($mpq_id, $product_id);
				}
			}
		}
		else
		{
			show_404();
		}
	}

	private function add_product_to_io($mpq_id, $product_id)
	{
		$submitted_products = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
		if ($submitted_products)
		{
			$this->mpq_v2_model->link_submitted_products_with_io($mpq_id, $product_id);
		}
		else
		{
			$this->mpq_v2_model->initialize_default_products_for_io($mpq_id, null, $product_id);
		}
	}

	public function get_select2_adset_versions_for_user_io()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array();
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$user_id = $this->input->post('user_id');
				$term = $this->input->post('q');
				$page_limit = $this->input->post('page_limit');
				$page = $this->input->post('page');
				$product_id = $this->input->post('product_id');
				$raw_show_all_versions = $this->input->post('show_all_versions');

				if($user_id !== false && $term !== false && $page_limit !== false && $page !== false && $product_id !== false)
				{
					$show_all_versions = false;
					if($raw_show_all_versions === 'true')
					{
						$show_all_versions = true;
					}

					$cup_versions_data = select2_helper($this->mpq_v2_model, 'get_select2_adset_versions_for_user_io', array('q' => $term, 'page' => $page, 'page_limit' => $page_limit), array('user_id' => $user_id, 'product_id' => $product_id, 'show_all_versions' => $show_all_versions));
					$return_array = $cup_versions_data;
				}
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function io_define_creatives_for_product()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'cup_versions' => array(), 'session_expired' => false);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$adset_id = $this->input->post('adset_id');
				$mpq_id = $this->input->post('mpq_id');
				$session_id = $this->session->userdata('session_id');
				if($product_id !== false && $adset_id !== false && $mpq_id !== false && $session_id !== false)
				{
					$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
					if($session_response === $session_id)
					{
						$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
						$result_data = $this->mpq_v2_model->io_define_creatives_for_product($product_id, $adset_id, $mpq_session_data['id']);
						if($result_data !== false)
						{
							$return_array['region_ids'] = $result_data;
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 293500: Not able to insert the creatives. Please try again";
						}
					}
					else
					{
						$return_array['session_expired'] = true;
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 293400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 293403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function io_save_adset_for_product_geo()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'cup_versions' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$cp_submitted_product_id = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
				$cp_submitted_product_id = $cp_submitted_product_id[0]['id'];
				$adset_id = $this->input->post('adset_id');
				$result_data = $this->mpq_v2_model->io_save_adset_for_product_geo($cp_submitted_product_id, $adset_id);
				if($result_data !== false)
				{
					$return_array['is_success'] = true;
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Not able to insert the creatives. Please try again";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 182403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function io_edit_adset_for_product_geo()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'data' => array(), 'session_expired' => false);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$session_id = $this->session->userdata('session_id');
				$mpq_id = $this->input->post('mpq_id');
				if($product_id !== false && $region_id !== false && $session_id !== false && $mpq_id !== false)
				{
					$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
					if($session_response === $session_id)
					{
						$result_data = $this->mpq_v2_model->io_edit_adset_for_product_geo($product_id, $region_id, $mpq_id);
						if($result_data !== false)
						{
							$return_array['data'] = $result_data;
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 304500: Not able to edit the creatives. Please try again";
						}
					}
					else
					{
						$return_array['session_expired'] = true;
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 304400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 304403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function io_delete_all_timeseries_and_creatives()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'session_expired' => false);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				$session_id = $this->session->userdata('session_id');
				if($mpq_id !== false && $session_id !== false)
				{
					$session_response = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
					if($session_response === $session_id)
					{
						$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
						$product_response = $this->mpq_v2_model->io_delete_create_new_submitted_products_for_io($mpq_session_data['id'], $mpq_session_data['region_data']);
						$result_data = $this->mpq_v2_model->io_delete_all_timeseries_and_creatives($mpq_session_data['id']);
						if($result_data !== false && $product_response !== false)
						{
							$return_array['is_success'] = true;
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 669500: server error when trying to delete flights and creatives";
						}
					}
					else
					{
						$return_array['session_expired'] = true;
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 669400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 669403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	function get_time_series_by_region_and_product()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'time_series_data' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$region_id = $this->input->post('region_id');
				$product_id = $this->input->post('product_id');
				$session_id = $this->session->userdata('session_id');
				if($region_id !== false && $product_id !== false)
				{

					$time_series_response = $this->mpq_v2_model->get_time_series_by_submitted_products($region_id, $product_id, $session_id);
					if($time_series_response !== false)
					{
						$return_array['time_series_data'] = $time_series_response;
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 005500: server error when getting time series data";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 005400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 005403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function generate_flights_for_product()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'impressions' => 0);
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$raw_start_date = $this->input->post('start_date');
				$raw_end_date = $this->input->post('end_date');
				$impressions = $this->input->post('impressions');
				$term = $this->input->post('term');
				$existing_time_series = $this->input->post('time_series');

				if($raw_start_date !== false && $raw_end_date !== false && $impressions !== false && $term !== false)
				{
					$dates_array = get_timeseries_start_dates($term, $raw_start_date, $raw_end_date);
					$return_array['time_series'] = array_map(function($date) use ($impressions){
						return array(
							'start_date' => $date,
							'impressions' => $impressions,
							'new' => true
						);
					}, $dates_array);
					$return_array['time_series'][count($return_array['time_series']) - 1]['impressions'] = 0; // set end date

					if ($existing_time_series)
					{
						$new_date_ranges = $this->create_date_range_array_from_time_series($return_array['time_series']);
						$existing_date_ranges = $this->create_date_range_array_from_time_series($existing_time_series);

						$return_array['date_ranges'][] = $existing_date_ranges;
						$return_array['date_ranges'][] = $new_date_ranges;

						$time_series_conflicts = $this->validate_consecutive_time_series($existing_date_ranges, $new_date_ranges);
						if (!empty($time_series_conflicts))
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Some dates are overlapping! Please select a new date range.";
							$return_array['conflicts'] = array_map(function($conflict){
								return array(
									'start_date' => date('m/d/Y', $conflict['start_date']),
									'end_date' => date('m/d/Y', $conflict['end_date'])
								);
							}, $time_series_conflicts);
						}
						else
						{

							$return_array['time_series'] = $this->merge_time_series($existing_time_series, $return_array['time_series']);
						}
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 182400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 182403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function create_date_range_array_from_time_series($time_series)
	{
		$date_ranges[] = array('start_date' => strtotime($time_series[0]['start_date']));
		$date_range_index = 0;
		foreach($time_series as $i => $flight)
		{
			if ($flight['impressions'] == 0)
			{
				$date_ranges[$date_range_index]['end_date'] = strtotime($flight['start_date'] . ' -1 day');
				if ($i+1 < count($time_series))
				{
					$date_ranges[] = array('start_date' => strtotime($time_series[$i+1]['start_date']));
					$date_range_index++;
				}
			}
		}
		return $date_ranges;
	}

	public function validate_consecutive_time_series($existing_date_ranges, $new_date_ranges)
	{
		$validated = [];
		foreach($existing_date_ranges as $existing_date_range)
		{
			foreach($new_date_ranges as $new_date_range)
			{
				if (
					(
						$new_date_range['start_date'] >= $existing_date_range['start_date'] &&
						$new_date_range['start_date'] <= $existing_date_range['end_date']
					) ||
					(
						$new_date_range['end_date'] >= $existing_date_range['start_date'] &&
						$new_date_range['end_date'] <= $existing_date_range['end_date']
					) ||
					(
						$existing_date_range['start_date'] > $new_date_range['start_date'] &&
						$existing_date_range['end_date'] < $new_date_range['end_date']
					)
				)
				{
					$validated[] = $new_date_range;
				}
			}
		}

		return $validated;
	}

	public function merge_time_series($existing_time_series, $new_time_series)
	{
		// Merge and then sort the new time series by date
		$merged_time_series = array_merge($existing_time_series, $new_time_series);
		usort($merged_time_series,
			function($a, $b){
				$a_date = strtotime($a['start_date']);
				$b_date = strtotime($b['start_date']);

				if ($a_date === $b_date)
				{
					// We can't have to entries with the same date and same number of impresssions
					// because of the previous validation.
					return $a['impressions'] < $b['impressions'] ? -1 : 1;
				}

				return $a_date < $b_date ? -1 : 1;
			}
		);

		// If two flights have the same start date,
		// remove the one with 0 impressions.
		foreach($merged_time_series as $i => &$flight)
		{
			if ($i+1 < count($merged_time_series))
			{
				if ((int) $flight['impressions'] === 0 && strtotime($flight['start_date']) === strtotime($merged_time_series[$i+1]['start_date']))
				{
					$flight = false;
				}
			}
		}
		return array_values(array_filter($merged_time_series));
	}

	public function save_time_series_for_region_and_product()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$time_series = $this->input->post('time_series');
				$allocation_method = $this->input->post('allocation_method');
				$session_id = $this->session->userdata('session_id');
				$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);
				if($product_id !== false && $time_series !== false)
				{

					$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
					if($product_data != false)
					{	
						$product_data = $product_data[0];
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_session_data['id']);

						if ($region_id !== "" && $region_id !== false)
						{
							$return_array['region_ids'] = array((int) $region_id);
							foreach($submitted_product_response as $submitted_product)
							{
								if($submitted_product['region_data_index'] == $region_id)
								{
									$submitted_product_id = $submitted_product['id'];
								}
							}
							if($submitted_product_id !== false)
							{
								$return_array['impressions'] = array_reduce($time_series, function($impressions, $flight){
									$impressions += $flight['impressions'];
									return $impressions;
								}, 0);
								$this->al_model->delete_create_time_series_for_io($submitted_product_id, $time_series);
								$return_array['start_date'] = $time_series[0]['start_date'];
								$return_array['end_date'] = date('m/d/Y', strtotime($time_series[count($time_series)-1]['start_date'] . ' -1 day'));
							}
						}
						else
						{
							$raw_region_data = json_decode($this->mpq_v2_model->get_region_data_by_mpq_id($mpq_session_data['id']), true);
							$this->map->convert_old_flexigrid_format($raw_region_data);
							$region_data = array();
							foreach($raw_region_data as $r_key => $r_val)
							{
								if(array_key_exists('ids', $r_val) && count($r_val['ids']['zcta']) > 0)
								{
									$region_data[$r_key] = $r_val;
								}
							}

							$return_array['region_ids'] = array_keys($region_data);

							$total_population = 0;
							$region_count = count($region_data);
							foreach($region_data as $region_index => $region)
							{
								$population = $this->map->get_demographics_from_region_array($region['ids']);
								if($population !== false)
								{
									$region_data[$region_index]['region_population'] = $population['region_population'];
									$total_population += $population['region_population'];
								}
								else
								{
									$region_data[$region_index]['region_population'] = 0;
								}
							}
							if($total_population > 0)
							{
								$return_array['impressions'] = 0;
								foreach($region_data as $region_index => $region)
								{
									$pop_share_percent = $region['region_population'] / $total_population;
									$regional_time_series = [];
									$impressions = 0;
									foreach($time_series as $i => $flight)
									{
										if ($allocation_method == 'per_pop')
										{
											$flight['impressions'] = round(($flight['impressions'] * count($region_data)) * $pop_share_percent);
										}
										$regional_time_series[] = $flight;
										$return_array['impressions'] += $flight['impressions'];
										$impressions += $flight['impressions'];
									}
									foreach($submitted_product_response as $submitted_product)
									{
										if($submitted_product['region_data_index'] == $region_index)
										{
											$submitted_product_id = $submitted_product['id'];
										}
									}
									if($submitted_product_id !== false)
									{
										$this->al_model->delete_create_time_series_for_io($submitted_product_id, $regional_time_series);

											//convert submitted product's timeseries to dfp timeseries
										$this->mpq_v2_model->add_dfp_order_rows_for_submitted_product($submitted_product_id, $product_data['o_o_dfp']);

										$return_array['start_date'] = $regional_time_series[0]['start_date'];
										$return_array['end_date'] = date('m/d/Y', strtotime($regional_time_series[count($regional_time_series)-1]['start_date'] . ' -1 day'));
										$return_array['region_data'][$region_index] = $impressions;
									}
								}
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 182500: unable to get geo population data";
							}
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 006419: failed to retrieve product data";						
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 006400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 006403: user logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}

	}

	public function get_time_series_summary_for_io()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$mpq_id = $this->input->post('mpq_id');
			$product_id = $this->input->post('product_id');
			if ($mpq_id)
			{
				$summary_data = $this->mpq_v2_model->get_time_series_summary_by_mpq_id($mpq_id, $product_id);
				if ($summary_data)
				{
					$this->output->set_output(json_encode($summary_data[0]));
					$this->output->set_status_header(200);
				}
				else $this->output->set_status_header(400);
			}
			else
			{
				$this->output->set_status_header(400, 'mpq_id is required');
			}
		}
		else
		{
			show_404();
		}
	}

	public function unlock_mpq_session()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				if($mpq_id !== false)
				{
					$response = $this->mpq_v2_model->unlock_mpq_session_by_mpq_id($mpq_id);
				}
				else
				{
					$session_id = $this->session->userdata('session_id');
					$response = $this->mpq_v2_model->unlock_mpq_session_by_session_id($session_id);
				}
			}
		}
		else
		{
			show_404();
		}
	}

	public function preload_io()
	{
		$this->session->set_userdata('mpq_id', $this->input->post('mpq_id'));
		$this->session->set_userdata('product', $this->input->post('product'));
		$this->output->set_status_header('200')->set_output(json_encode(array()));
	}

	public function is_io_in_use()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$mpq_id = $this->input->post('mpq_id');
				$session_id = $this->session->userdata('session_id');
				if($mpq_id !== false and $session_id !== false)
				{
					$result = $this->mpq_v2_model->get_mpq_sessions_session_id_by_mpq_id($mpq_id);
					if($result !== false)
					{
						if($result !== $session_id)
						{
							$this->output->set_status_header(409, 'IO is in use.');
						}
						else
						{
							$this->output->set_status_header(200);
						}
					}
					else
					{
						$this->output->set_status_header(400);
					}
				}
				else
				{
					$this->output->set_status_header(400);
				}
			}
			else
			{
				$this->output->set_status_header(401);
			}
		}
		else
		{
			show_404();
		}
	}

	public function get_select2_advertisers()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$user_role = strtolower($this->tank_auth->get_role($username));
			$user_is_super = $this->tank_auth->get_isGroupSuper($username);

			$advertiser_response = array();

			//Pull unverified advertisers.
			$unverified_advertisers_calling_method = "get_unverified_advertisers_by_user_id_for_select2";
			$params_array = array($user_id, $user_is_super);
			if ($user_role == "admin" || $user_role == "ops" || $user_role == "creative" )
			{
				$unverified_advertisers_calling_method = "get_unverified_advertisers_for_internal_users_for_select2";
				$params_array = array();
			}
			$unverified_advertiser_response = select2_helper($this->mpq_v2_model, $unverified_advertisers_calling_method, $post_array, $params_array);


			//Pull verified advertisers.
			$verified_advertisers_calling_method = "get_verified_advertisers_by_user_id_for_select2";
			$params_array = array($user_id, $user_is_super);
			if ($user_role == "admin" || $user_role == "ops" || $user_role == "creative" )
			{
				$verified_advertisers_calling_method = "get_verified_advertisers_for_internal_users_for_select2";
				$params_array = array();
			}
			$verified_advertisers_response = select2_helper($this->mpq_v2_model, $verified_advertisers_calling_method, $post_array, $params_array);


			//Remove duplicate entries from unverified advertisers array and merge the verified/unverified advertisers array.
			if (!empty($unverified_advertiser_response['results']) && !$unverified_advertiser_response['errors']
				&& !empty($verified_advertisers_response['results']) && !$verified_advertisers_response['errors'])
			{
				$advertiser_response['results'] = array_merge($verified_advertisers_response['results'],$unverified_advertiser_response['results']);
				$advertiser_response['more'] = $verified_advertisers_response['more'];
			}elseif (!empty($unverified_advertiser_response['results']) && !$unverified_advertiser_response['errors'])
			{
				$advertiser_response['results'] = $unverified_advertiser_response['results'];
				$advertiser_response['more'] = $unverified_advertiser_response['more'];
			}elseif (!empty($verified_advertisers_response['results']) && !$verified_advertisers_response['errors'])
			{
				$advertiser_response['results'] = $verified_advertisers_response['results'];
				$advertiser_response['more'] = $verified_advertisers_response['more'];
			}


			//Prepare final response to send to front end.
			$feature_id = 'create_unverified_advertiser';
			$is_accessible = $this->vl_platform_model->is_feature_accessible($user_id, $feature_id);
			$advertiser_array = array();

			if ($post_array['page'] == 1 && $is_accessible)
			{
				$advertiser_array['results'][] = array(
					'id' => 'unverified_advertiser_name',
					'text' => '*New*'
				);
			}

			if (!empty($advertiser_response['results']))
			{
				$advertiser_array['more'] = $advertiser_response['more'];
				foreach ($advertiser_response['results'] as $org_name=>$result_row)
				{
					$user_name = isset($result_row['user_name']) ? $result_row['user_name'] : '';
					$user_email = isset($result_row['email']) ? $result_row['email'] : '';
					$ul_id = isset($result_row['ul_id']) ? $result_row['ul_id'] : '';
					$eclipse_id = isset($result_row['eclipse_id']) ? $result_row['eclipse_id'] : '';
					$id_value = $result_row['id'];
					$advertiser_array['results'][] = array(
						'id' => $id_value,
						'text' => $result_row['Name'],
						'external_id' => isset($result_row['external_id']) ? $result_row['external_id'] : null,
						'adv_name' => $result_row['Name'],
						'status' => $result_row['status'],
						'source_table' => $result_row['source_table'],
						'user_name' => $user_name,
						'email' => $user_email,
						'ul_id' => $ul_id,
						'eclipse_id' => $eclipse_id,
						'sales_person' => $result_row['sales_person']
					);

				}
			}
			elseif(empty($advertiser_array['results']))
			{
				$advertiser_array['errors'] = isset($advertiser_response['errors']) ? $advertiser_response['errors'] : "Error while fetching advertisers";
				$advertiser_array['results'] = array();
			}

			echo json_encode($advertiser_array);
		}
		else
		{
			show_404();
		}
	}

	public function validate_and_create_unverified_advertiser()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$user_id = $this->tank_auth->get_user_id();
			$unverified_advertiser = $post_array['unverified_advertiser'];

			if (!$this->mpq_v2_model->check_if_verified_or_unverified_advertiser_exist_by_name($unverified_advertiser))
			{
				$create_result = $this->mpq_v2_model->create_unverified_advertiser($unverified_advertiser, $user_id);
				if ($create_result['is_success'])
				{
					$result['is_success'] = true;
					$result['result'] = array(
						'advertiser_id' => $create_result['id'],
						'advertiser_name' => $create_result['advertiser_name'],
						'source_table' => $create_result['source_table']
					);
				}
			}
			else
			{
				$result = array();
				$result['is_success'] = false;
				$result['errors'] = 'Advertiser name already exist';
			}

			echo json_encode($result);
		}
		else
		{
			show_404();
		}
	}

	public function update_versions_with_vanity_string()
	{
		if($this->input->is_cli_request())
		{
		    $this->mpq_v2_model->update_all_existing_versions_with_vanity_string();
		}
		else
		{
			show_404();
		}
	}

	public function get_creatives_info()
	{
		$result = array();
		$result['is_success'] = false;

		if (!$this->tank_auth->is_logged_in())
		{
			$result['errors'] = 'Not logged in';
		}
		elseif ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$mpq_id = $post_array['mpq_id'];

			if (empty($mpq_id))
			{
				$result['errors'] = 'Invalid mpq id';
			}
			else
			{
				$creatives_data = $this->mpq_v2_model->get_creatives_defined_by_mpq_id($mpq_id);

				if (!empty($creatives_data))
				{
					$result['is_success'] = true;
					$result['creatives_data'] = $creatives_data;
				}
				else
				{
					$result['errors'] = 'Not able to fetch the creatives data by mpq id';
				}
			}
		}

		echo json_encode($result);
	}

	public function get_filtered_strategy_info()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$owner_id = $this->input->post('owner_id');
				$industry_id = $this->input->post('industry_id');
				$strategy_id = $this->input->post('strategy_id');

				if (!$owner_id)
				{
					$this->output->set_status_header(400, 'You must pass at least an owner id.');
				}

				$rfp_info = array(
					'strategies' => false,
					'industries' => true
				);

				$partner_id = $this->tank_auth->get_partner_id($owner_id) ?: 1;

				if ($strategy_id) // we picked a strategy and want to filter the list of industries
				{
					$rfp_info['industries'] = $this->strategies_model->get_industry_info_by_strategy($strategy_id);
				}

				$rfp_info['strategies'] = $this->strategies_model->get_strategy_info($partner_id, $industry_id);

				if ($rfp_info)
				{
					$this->output->set_output(json_encode($rfp_info));
					$this->output->set_status_header(200);
				}
				else // the combo of owner, industry, and strategy don't match
				{
					$this->output->set_status_header(401);
				}
			}
		}
	}

	public function save_o_o_percentage()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$return_array = array('is_success' => true, 'err' => "");
				$mpq_id = $this->input->post('mpq_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$o_o_percentage = $this->input->post('o_o_percentage');

				if($mpq_id === false || $product_id === false || $region_id === false || $o_o_percentage === false)
				{
					$return_array['is_success'] = false;
					$return_array['err'] = "Failed to save O&O Percentage: #22194 Request failed validation.";
				}
				else
				{
					$submitted_product = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					if($submitted_product == false)
					{
						$return_array['is_success'] = false;
						$return_array['err'] = "Failed to save O&O Percentage: #22195 Couldn't retreve product info.";
					}
					else
					{
						$saved_percentage = $this->mpq_v2_model->save_o_o_percentage_for_submitted_product($submitted_product[0]['id'], $o_o_percentage);
						if($saved_percentage == false)
						{
							$return_array['is_success'] = false;
							$return_array['err'] = "Failed to save O&O Percentage: #22195 Couldn't save new percentage.";
						}
					}
				}
				echo json_encode($return_array);
			}
		}
	}

	public function save_o_o_ids()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$return_array = array('is_success' => true, 'err' => "");
				$mpq_id = $this->input->post('mpq_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$o_o_ids = $this->input->post('o_o_ids');

				if($mpq_id === false || $product_id === false || $region_id === false || $o_o_ids === false)
				{
					$return_array['is_success'] = false;
					$return_array['err'] = "Failed to save O&O Percentage: #22196 Request failed validation.";
				}
				else
				{
					if(!is_array($o_o_ids))
					{
						$o_o_ids = array();
					}

					$submitted_product = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					if($submitted_product == false)
					{
						$return_array['is_success'] = false;
						$return_array['err'] = "Failed to save O&O Campaign IDs: #22197 Couldn't retreve product info.";
					}
					else
					{
						$saved_percentage = $this->mpq_v2_model->save_o_o_ids_for_submitted_product($submitted_product[0]['id'], $o_o_ids);
						if($saved_percentage == false)
						{
							$return_array['is_success'] = false;
							$return_array['err'] = "Failed to save O&O Campaign IDs: #22198 Couldn't save new ids.";
						}
					}
				}
				echo json_encode($return_array);
			}
		}
	}

	public function retrieve_o_o_data()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$return_array = array('is_success' => true, 'err' => "", "o_o_percent" => null, "o_o_ids" => null);
				$mpq_id = $this->input->post('mpq_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				if($mpq_id === false || $product_id === false || $region_id === false)
				{
					$return_array['is_success'] = false;
					$return_array['err'] = "Failed to retrieve O&O Data: #22199 Request failed validation.";
				}
				else
				{
					$o_o_data = $this->mpq_v2_model->get_o_o_data_for_mpq_product_region($mpq_id, $product_id, $region_id);
					if($o_o_data === false)
					{
						$return_array['is_success'] = false;
						$return_array['err'] = "Failed to retrieve O&O Data: #22200 Database Error";
					}
					else
					{
						if(!empty($o_o_data))
						{
							$return_array['o_o_percent'] = $o_o_data[0]['o_o_percent'];
							$return_array['o_o_ids'] = $o_o_data[0]['o_o_ids'];
						}
					}
				}
				echo json_encode($return_array);
			}
		}
	}

	public function get_dfp_advertisers()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$search_string = $this->input->post('q');
			$advertiser_array = array();
			$dfp_result = $this->google_api->get_dfp_advertisers(true, $search_string);
			if($dfp_result['success'])
			{
				$advertiser_array['results'][] = $dfp_result['data'];
				foreach ($dfp_result['data'] as $dfp_advertiser)
				{					
					$advertiser_array['results'][] = array(
						'id' => $dfp_advertiser["id"],
						'text' => $dfp_advertiser["text"]
					);

				}
			}
			echo json_encode($advertiser_array);
		}
		else
		{
			show_404();
		}
	}

	public function create_dfp_advertiser()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{		
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);
			if($response['is_success'])
			{
				$return_array = array('is_success' => true, 'err' => "", "dfp_advertisers" => null, "new_advertiser_id" => null);
				$new_advertiser_name = $this->input->post('new_dfp_advertiser');
				if($new_advertiser_name === false)
				{
					$return_array['is_success'] = false;
					$return_array['err'] = "Failed to create new DFP advertiser: #45718 Request failed validation.";
				} 
				else
				{
					$add_advertiser = $this->mpq_v2_model->create_dfp_advertiser($new_advertiser_name);
					if($add_advertiser != false)
					{
						$return_array['new_advertiser_id'] = $add_advertiser->id;
						$return_array['new_advertiser_name'] = $add_advertiser->name;
						$return_array['dfp_advertisers'] = json_encode(array());
						$dfp_result = $this->google_api->get_dfp_advertisers(true);
						if($dfp_result['success'])
						{
							$return_array['dfp_advertisers'] = $dfp_result['data'];
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['err'] = "Failed to create new DFP advertiser: #45482 DFP Request failed.";						
					}
				}
			}
			echo json_encode($return_array);			
		}
	}
	public function save_dfp_advertiser_to_io()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{		
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);
			if($response['is_success'])
			{
				$return_array = array('is_success' => true, 'err' => "");
				$dfp_advertiser_id = $this->input->post('dfp_advertiser_id');
				$mpq_id = $this->input->post('mpq_id');
				if($dfp_advertiser_id === false || $mpq_id === false)
				{
					$return_array['is_success'] = false;
					$return_array['err'] = "Failed to assign DFP advertiser: #42118 Request failed validation.";
				} 
				else
				{
					$assign_dfp_advertiser = $this->mpq_v2_model->link_dfp_advertiser_to_io($mpq_id,$dfp_advertiser_id);
					$dfp_order_details = array();
					$dfp_order_details['dfp_advertiser_id'] = $dfp_advertiser_id;
					$get_io_flight_subproducts = $this->mpq_v2_model->get_io_flight_subproducts($mpq_id);
					$series_date = array();
					foreach($get_io_flight_subproducts as $get_io_flight_subproduct)
					{
					    if(!in_array($get_io_flight_subproduct['series_date'],$series_date))
					    {
						    $series_date[] = $get_io_flight_subproduct['series_date'];
					    }
					}
					
					$dfp_order_details['start_date'] = min($series_date);
					$max_date = max($series_date);
					$dfp_order_details['end_date'] = date( 'Y-m-d', strtotime( $max_date . ' -1 day' ) );
					$dfp_object_templates_id = 1;
					
					$dfp_json_object = $this->mpq_v2_model->get_dfp_object_template($dfp_object_templates_id);
					$dfp_object_template = json_decode($dfp_json_object[0]['object_blob']);
					$create_order = $this->google_api->create_dfp_order_lineitems($dfp_order_details, $dfp_object_template);


					$get_creative_adtag = $this->mpq_v2_model->get_ad_tags_by_mpq_id($mpq_id);
					if($assign_dfp_advertiser == false)
					{
						$return_array['is_success'] = false;
						$return_array['err'] = "Failed to create new DFP advertiser: #49172 Failed to assign DFP advertiser.";
					}
					$create_order = $this->google_api->create_dfp_order_lineitems($dfp_order_details);
				}
			}
			echo json_encode($return_array);			
		}		
	}

	function get_time_series_for_product()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'time_series_data' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$region_id = $this->input->post('region_id');
				$product_id = $this->input->post('product_id');
				$session_id = $this->session->userdata('session_id');
				if($product_id !== false)
				{
					if($region_id == "")
					{
						$time_series_response = $this->mpq_v2_model->get_time_series_for_product_or_submitted_product($product_id, $session_id);
					}
					else
					{
						$time_series_response = $this->mpq_v2_model->get_time_series_for_product_or_submitted_product($product_id, $session_id, $region_id);
					}
					if($time_series_response !== false)
					{
						$return_array['time_series_data'] = $time_series_response;
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 005500: server error when getting time series data";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 005400: invalid request parameters";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 005403: User logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function delete_all_time_series_for_campaign()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);
			$response = array('is_success' => true);

			if(true || $response['is_success'])
			{
				$campaign_id = $this->input->post('campaign_id');

/*				$product_id = 4;
				$region_id = 0;
				$region_id = false;
				$mpq_id = 79724;
*/
				if($campaign_id !== false)
				{						
					$this->mpq_v2_model->delete_all_timeseries_data_for_campaign($campaign_id);
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 98643: Invalid parameters";
					echo json_encode($return_array);
					return;						
				}
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}		
	}


	public function delete_all_time_series_for_product_region()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);
			$response = array('is_success' => true);

			if(true || $response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$mpq_id = $this->input->post('mpq_id');

/*				$product_id = 4;
				$region_id = 0;
				$region_id = false;
				$mpq_id = 79724;
*/
				if($product_id !== false && $mpq_id !== false)
				{						
					if($region_id !== "" && $region_id !== false)
					{
						//Region id is specified, only use one submitted product
						//Get submitted product
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					}
					else
					{
						//Region id is not specified, do this for the entire product.
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
					}
					if($submitted_product_response != false)
					{
						foreach($submitted_product_response as $submitted_product)
						{
							$submitted_product_id = $submitted_product['id'];
							$this->mpq_v2_model->delete_all_timeseries_data_for_submitted_product($submitted_product_id);
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 98746: Could not get product geo information for deletion.";
						echo json_encode($return_array);
						return;								
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 41976: Could not save flights";
					echo json_encode($return_array);
					return;						
				}
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}


	public function save_time_series_for_product_region()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'cpms' => null, 'flights' => null, 'total_flights' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);
			$response = array('is_success' => true);

			if(true || $response['is_success'])
			{
				$campaign_id = $this->input->post('campaign_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$start_date = $this->input->post('start_date');
				$end_date = $this->input->post('end_date');
				$pacing_type = $this->input->post('pacing_type');
				$flight_type = $this->input->post('flight_type');
				$total_budget = $this->input->post('budget');
				$budget_allocation = $this->input->post('budget_allocation');
				$session_id = $this->session->userdata('session_id');
				$mpq_id = $this->input->post('mpq_id');
				$mpq_session_data = $this->mpq_v2_model->get_rfp_preload_mpq_session_data($session_id);

/*				$campaign_id = 18855;
//				$product_id = 34;
//				$region_id = 0;
//				$region_id = false;
				$start_date = "2016-09-01";
				$end_date = "2017-02-28";
				$pacing_type = "MONTHLY";
				$flight_type = "MONTHLY";
//				$budget_allocation = "per_pop";
				$total_budget = 10400;
//				$mpq_id = 77467;
				$mpq_session_data = array('id' => 71071);
*/

				if($campaign_id)
				{
					$campaign_details = $this->al_model->get_campaign_details($campaign_id);

					$product_data = $this->mpq_v2_model->get_product_data_for_submitted_product($campaign_details[0]['cp_submitted_products_id']);
					$submitted_product_data = $this->mpq_v2_model->get_submitted_product_data_for_id($campaign_details[0]['cp_submitted_products_id']);
					if($product_data == false || $submitted_product_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 789456: Failed to retrieve product information.";
						echo json_encode($return_array);
						return;							
					}

					$this->mpq_v2_model->delete_all_timeseries_data_for_campaign($campaign_id);

					$cpms = $this->mpq_v2_model->insert_product_cpms_for_mpq_submitted_product($submitted_product_data['mpq_id'], $campaign_id, $product_data['product_identifier'], true);
					if($cpms == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 21564: Failed to save cpms";
						echo json_encode($return_array);
						return;
					}

					$flights = get_flight_calculation($start_date, $end_date, $flight_type, $pacing_type, $total_budget);
					if($flights == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 145261: Failed to generate flights";
						echo json_encode($return_array);
						return;										
					}

					$insert_flights = $this->mpq_v2_model->insert_flights_with_budgets_for_campaign($campaign_id, $flights);
					if($insert_flights == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 145712: Could not save flights";
						echo json_encode($return_array);
						return;											
					}

					$added_subproduct_rows = $this->mpq_v2_model->add_dfp_order_rows_for_campaign($campaign_id, $product_data['o_o_dfp']);

					foreach($insert_flights as $idx => $flight)
					{
						if($idx != count($insert_flights) && $flight['budget'] != 0)
						{
							//calculate budget
							if($this->mpq_v2_model->does_campaign_have_geofencing($campaign_id))
							{
								$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($campaign_details[0]['insertion_order_id']);
							}
							else
							{
								$geofencing_inventory = 0;
							}
							$o_o_impressions = $product_data['o_o_enabled'] ? round((($product_data['o_o_max_ratio']*0.01)*$flight['budget'])/$cpms['o_o'] * 1000) : 0;
							$budget_values = get_budget_calculation($flight['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
							$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
							if($saved_budget_values == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 123784: Could not save flight budgets";
								echo json_encode($return_array);
								return;
							}
							//save
							$flight_budgets = array();
							$next_flight = $insert_flights[$idx+1];
							$flight_budgets['id'] = $flight['id'];
							$flight_budgets['start_date'] = $flight['series_date'];
							$flight_budgets['end_date'] = date('Y-m-d', strtotime('-1 day', strtotime($next_flight['series_date'])));
							$flight_budgets['total_budget'] = $flight['budget'];
							$flight_budgets['o_o_impressions'] = $o_o_impressions;
							$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
							$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
							$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
							$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
							$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
							$flight_budgets['region_index'] = 0;
							$submitted_product_flights[] = $flight_budgets;
						}
					}
					$return_array['flights'] = $submitted_product_flights;
					$return_array['cpms'] = $cpms;
				}
				else
				{
					if($product_id !== false && $mpq_id !== false)
					{
						if(strtotime($start_date) > strtotime($end_date))
						{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 888889: Start Date is after End Date. Please adjust your dates.";
								echo json_encode($return_array);
								return;								
						}
						$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
						$region_data = null;
						if($product_data != false)
						{	
							$product_data = $product_data[0];
							$submitted_products[] = array();
							//Determine if we're referring to a single submitted_product or not
							$region_data = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id);
							if($region_data == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 378272: Failed to retrieve region data";
								echo json_encode($return_array);
								return;									
							}
							$region_data = json_decode($region_data);
							$region_data = (array) $region_data;
							$saved_allocation_type = $this->mpq_v2_model->save_allocation_type_for_mpq_and_product($mpq_id, $product_id, $budget_allocation);						
							if($region_id !== "" && $region_id !== false)
							{
								//Region id is specified, only use one submitted product
								//Get submitted product
								$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
							}
							else
							{
								//Region id is not specified, do this for the entire product.
								$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
								if($budget_allocation == "per_pop")
								{
									$total_population = 0;

									foreach($submitted_product_response as $idx => $submitted_product)
									{
										$region_index = $submitted_product['region_data_index'];
										$region_page = (array)$region_data[$region_index];
										$population = $this->map->get_demographics_from_region_array((array)$region_page['ids']);
										if($population !== false)
										{
											$submitted_product_response[$idx]['region_population'] = $population['region_population'];
											$total_population += $population['region_population'];
										}
										else
										{
											$submitted_product_response[$idx]['region_population'] = 0;
										}									
									}
								}
							}
							if($submitted_product_response != false)
							{
								$return_flights = array();
								foreach($submitted_product_response as $submitted_product)
								{
									$submitted_product_id = $submitted_product['id'];
									if($region_id === "" || $region_id === false)
									{
										if($budget_allocation == "even")
										{
											$region_budget = $total_budget/count($submitted_product_response);
										}
										elseif($budget_allocation == "per_pop")
										{
											$region_budget = $total_budget*($submitted_product['region_population']/$total_population);
										}
										else
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 21564: Invalid Region/Budget parameters";
											echo json_encode($return_array);
											return;										
										}
									}
									else //region is specified, and for builder this should mean custom
									{
										$region_budget = $total_budget;
									}

									$this->mpq_v2_model->delete_all_timeseries_data_for_submitted_product($submitted_product_id);

									$cpms = $this->mpq_v2_model->insert_product_cpms_for_mpq_submitted_product($mpq_id, $submitted_product_id, $product_data['product_identifier']);
									if($cpms == false)
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Error 21564: Failed to save cpms";
										echo json_encode($return_array);
										return;
									}
									//create flights
									$flights = get_flight_calculation($start_date, $end_date, $flight_type, $pacing_type, $region_budget);
									if($flights == false)
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Error 145261: Failed to generate flights";
										echo json_encode($return_array);
										return;										
									}
									$insert_flights = $this->mpq_v2_model->insert_flights_with_budgets_for_submitted_product($submitted_product_id, $flights);
									if($insert_flights == false)
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Error 145712: Could not save flights";
										echo json_encode($return_array);
										return;											
									}
									//Assuming an array of flights to get inserted into campaigns_time_series gets returned. Insert that straight up

									//create dfp rows based off flights
									$added_subproduct_rows = $this->mpq_v2_model->add_dfp_order_rows_for_submitted_product($submitted_product_id, $product_data['o_o_dfp']);

									if($product_data['o_o_dfp'])
									{
										$zips = $region_data[$submitted_product['region_data_index']]->ids->zcta;
										//Get line item template id for zips

										$order_template = $this->mpq_v2_model->get_dfp_object_template_for_zips($zips, "ORDER");
										if($order_template === false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 145715: Failed to retireve dfp template";
											echo json_encode($return_array);
											return;											
										}

										$line_item_template = $this->mpq_v2_model->get_dfp_object_template_for_zips($zips, "LINE_ITEM");
										if($line_item_template === false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 145714: Failed to retireve dfp template";
											echo json_encode($return_array);
											return;											
										}
										//Save template 
										$saved_order_template = $this->mpq_v2_model->save_dfp_object_template_for_submitted_product($order_template, $submitted_product_id, "ORDER");
										$saved_line_item_template = $this->mpq_v2_model->save_dfp_object_template_for_submitted_product($line_item_template, $submitted_product_id, "LINE_ITEM");
										if($saved_order_template == false || $saved_line_item_template == false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 174562: Failed to save initial dfp templates";
											echo json_encode($return_array);
											return;											
										}
									}

									//PLACEHOLDER:
									$submitted_product_flights = array();
									foreach($insert_flights as $idx => $flight)
									{
										if($idx != count($insert_flights) && $flight['budget'] != 0)
										{
											//calculate budget
											if($this->mpq_v2_model->does_mpq_region_have_geofencing_points($mpq_id, $submitted_product['region_data_index']))
											{
												$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id);
											}
											else
											{
												$geofencing_inventory = 0;
											}
											$o_o_impressions = $product_data['o_o_enabled'] ? round((($product_data['o_o_max_ratio']*0.01)*$flight['budget'])/$cpms['o_o'] * 1000) : 0;
											$budget_values = get_budget_calculation($flight['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
											$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
											if($saved_budget_values == false)
											{
												$return_array['is_success'] = false;
												$return_array['errors'] = "Error 123784: Could not save flight budgets";
												echo json_encode($return_array);
												return;
											}
											//save
											$flight_budgets = array();
											$next_flight = $insert_flights[$idx+1];
											$flight_budgets['id'] = $flight['id'];
											$flight_budgets['start_date'] = $flight['series_date'];
											$flight_budgets['end_date'] = date('Y-m-d', strtotime('-1 day', strtotime($next_flight['series_date'])));
											$flight_budgets['total_budget'] = $flight['budget'];
											$flight_budgets['o_o_impressions'] = $o_o_impressions;
											$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
											$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
											$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
											$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
											$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
											$flight_budgets['region_index'] = $submitted_product['region_data_index'];
											$submitted_product_flights[] = $flight_budgets;
										}
									}
									$return_flights[] = $submitted_product_flights;
									//Something($submitted_product_id);??????
								}

								if($region_id === "" || $region_id === false)
								{
									//generate summation array
									$flight_sums = array();
									foreach($return_flights as $flights)
									{
										if(empty($flight_sums))
										{
											$flight_sums = $flights;
										}
										else
										{
											foreach($flights as $idx => $flight)
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
														default:
															$flight_sums[$idx][$key] += $budget_value;
															break;

													}
												}
											}
										}
									}
									$return_array['total_flights'] = $flight_sums;
								}
								$return_array['cpms'] = $cpms;
								$return_array['flights'] = $return_flights;
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 047217: failed to retrieve submitted product data";							
							}
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 006419: failed to retrieve product data";						
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 006400: invalid request parameters";
					}
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 006403: user logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}

	}

	public function retrieve_flights($mpq_id, $product_id, $region_id = false)
	{
		$return_array = array('is_success' => true, 'errors' => "", 'cpms'=>null, 'flights' => array(), 'total_flights'=>array());
/*		$product_id = 35;
		$region_id = 0;
		$region_id = false;
		$mpq_id = 82491;
		$mpq_session_data = array('id' => 71071);
*/
		$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
		$product_data = $product_data[0];

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
		echo json_encode($return_array);
		return $return_array;
	}


	public function retrieve_flights_for_campaign($campaign_id)
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'cpms'=> array(), 'flights' => array(), 'total_flights' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'] && isset($campaign_id) && !empty($campaign_id))
			{		
				//$campaign_id = $this->input->post('campaign_id');
				$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_campaign($campaign_id);
				$flights_response = $this->mpq_v2_model->get_flights_for_campaign($campaign_id);
				if($flights_response == false)
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 741564: failed to retrieve flights";
					echo json_encode($return_array);
					return;
				}

				$campaign_adv_details = $this->al_model->get_campaign_advertiser_details($campaign_id);


				if(empty($campaign_adv_details[0]['cp_submitted_products_id']))
				{
					$subproduct_data = $this->mpq_v2_model->get_campaign_subproducts_using_adgroups($campaign_id);
					if($subproduct_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 789456: failed to retrieve subproduct data";
						echo json_encode($return_array);
						return;						
					}
					if($subproduct_data['DISPLAY'] == 1)
					{
						$last_name = "Display";
					}
					else
					{
						$last_name = "Pre-Roll";
					}
					$product_data = array(
						"has_geofencing" => $subproduct_data['GEOFENCING'],
						"o_o_enabled" => $subproduct_data['O_O_DISPLAY'],
						"o_o_dfp" => 0,
						"definition" => '{"last_name":"'.$last_name.'"}'
					);
				}
				else
				{
					$product_data = $this->mpq_v2_model->get_product_data_for_submitted_product($campaign_adv_details[0]['cp_submitted_products_id']);
					if($product_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 789456: Failed to retrieve product information.";
						echo json_encode($return_array);
						return;
					}
				}

				$return_array['cpms'] = $cpms;
				$return_array['advertiser_name'] = $campaign_adv_details[0]['advertiser_name'];
				$return_array['campaign_name'] = $campaign_adv_details[0]['Name'];
				$return_array['flights'][] = $flights_response;
				$return_array['has_geofencing'] = $product_data['has_geofencing'];
				$return_array['o_o_enabled'] = $product_data['o_o_enabled'];
				$return_array['o_o_dfp'] = $product_data['o_o_dfp'];
				$return_array['definition'] = json_decode($product_data['definition'], true);
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 789756: invalid campaign_id";
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

	public function update_existing_flight_budget()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'flights' => array(), 'total_flights' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);

			if(true || $response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$flight_ids = $this->input->post('flight_id');
				$campaign_id = $this->input->post('campaign_id');
				$mpq_id = $this->input->post('mpq_id');
				$budget_allocation = $this->input->post('budget_allocation');
				$flight_budget = $this->input->post('budget');
				$session_id = $this->session->userdata('session_id');
				
				if($campaign_id != false)
				{
						$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_campaign($campaign_id);
						if($cpms == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 132232: Failed to retrieve cpms";
							echo json_encode($return_array);
							return;
						}

						$campaign_details = $this->al_model->get_campaign_details($campaign_id);

						$product_data = $this->mpq_v2_model->get_product_data_for_submitted_product($campaign_details[0]['cp_submitted_products_id']);
						if($product_data == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 789456: Failed to retrieve product information.";
							echo json_encode($return_array);
							return;							
						}

						$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($campaign_details[0]['insertion_order_id']);

						//update and get flight for this product
						$updated_budgets = $this->mpq_v2_model->update_flight_budget_for_flight_id($flight_ids[0], $flight_budget);
						$o_o_impressions = $product_data['o_o_enabled'] ? round((($product_data['o_o_max_ratio']*0.01)*$flight_budget)/$cpms['o_o'] * 1000) : 0;
						$budget_values = get_budget_calculation($flight_budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
						$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight_ids[0], $o_o_impressions, $budget_values, false);
						if($saved_budget_values == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 123784: Could not save flight budgets";
							echo json_encode($return_array);
							return;
						}	
						$flight_budgets = array();
						$flight_budgets['id'] = $flight_ids[0];
						$flight_budgets['total_budget'] = $flight_budget;
						$flight_budgets['o_o_impressions'] = $o_o_impressions;
						$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
						$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
						$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
						$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
						$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];					
						$flight_budgets['region_index'] = 0;
						$modified_flights[] = $flight_budgets;
						$return_array['flights'] = $modified_flights;	

				}
				else
				{
					if($product_id == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 106400: invalid request parameters";
						echo json_encode($return_array);
						return;				
					}
					$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
					$product_data = $product_data[0];
					$submitted_products[] = array();

					//Determine if we're referring to a single submitted_product or not
					$edit_flights_data = $this->mpq_v2_model->get_flight_rows($flight_ids);
					if($edit_flights_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 688954: Failed to find flights data";
						echo json_encode($return_array);
						return;						
					}
					if($region_id !== "" && $region_id !== false)
					{

						//Update the one flight with the budgets and recalculate
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					}
					else
					{
						//Region id is not specified, do this for the entire product.
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
						if($budget_allocation == "per_pop")
						{
							$total_population = 0;
							$region_data = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id);
							if($region_data == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 378272: Failed to retrieve region data";
								echo json_encode($return_array);
								return;									
							}
							$region_data = json_decode($region_data);
							$region_data = (array) $region_data;
							foreach($submitted_product_response as $idx => $submitted_product)
							{
								$region_index = $submitted_product['region_data_index'];
								$region_page = (array)$region_data[$region_index];
								$population = $this->map->get_demographics_from_region_array((array)$region_page['ids']);
								if($population !== false)
								{
									$submitted_product_response[$idx]['region_population'] = $population['region_population'];
									$total_population += $population['region_population'];
								}
								else
								{
									$submitted_product_response[$idx]['region_population'] = 0;
								}									
							}
						}
					}
					if($submitted_product_response == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 106442: Failed to retrieve submitted product information";
						echo json_encode($return_array);
						return;						
					}

					$modified_flights = array();
					foreach($submitted_product_response AS $submitted_product)
					{
						$submitted_product_id = $submitted_product['id'];
						if($region_id === "" || $region_id === false)
						{
							if($budget_allocation == "even")
							{
								$region_budget = $flight_budget/count($submitted_product_response);
							}
							elseif($budget_allocation == "per_pop")
							{
								$region_budget = $flight_budget*($submitted_product['region_population']/$total_population);
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 21564: Invalid Region/Budget parameters";
								echo json_encode($return_array);
								return;										
							}
						}
						else //region is specified, and for builder this should mean custom
						{
							$region_budget = $flight_budget;
						}


						$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_submitted_product($submitted_product_id);
						if($cpms == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 132232: Failed to retrieve cpms";
							echo json_encode($return_array);
							return;
						}

						//Get budget values to use in update
						$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id);

						//update and get flight for this product
						$updated_budgets = $this->mpq_v2_model->update_flight_budget_for_flight_id($edit_flights_data[$submitted_product_id]['id'], $region_budget);
						$o_o_impressions = $product_data['o_o_enabled'] ? round((($product_data['o_o_max_ratio']*0.01)*$region_budget)/$cpms['o_o'] * 1000) : 0;
						$budget_values = get_budget_calculation($region_budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
						$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($edit_flights_data[$submitted_product_id]['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
						if($saved_budget_values == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 123784: Could not save flight budgets";
							echo json_encode($return_array);
							return;
						}	
						$flight_budgets = array();
						$flight_budgets['id'] = $edit_flights_data[$submitted_product_id]['id'];
						$flight_budgets['total_budget'] = $region_budget;
						$flight_budgets['o_o_impressions'] = $o_o_impressions;
						$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
						$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
						$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
						$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
						$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];					
						$flight_budgets['region_index'] = $submitted_product['region_data_index'];
						$modified_flights[] = $flight_budgets;
						$return_array['flights'] = $modified_flights;									
					}
					if(count($return_array['flights']))
					{
						$total_flights = null;
						foreach($return_array['flights'] as $flight)
						{
							if($total_flights == null)
							{
								$total_flights = $flight;
							}
							else
							{

								foreach($flight as $key => $budget_value)
								{	

									switch($key)
									{
										case "id":
											if(!is_array($total_flights["id"]))
											{
												$total_flights["id"] = array($total_flights["id"]);
											}
											$total_flights[$key][] = $budget_value;
											break;
										default:
											$total_flights[$key] += $budget_value;
											break;
									}
								}				
							}
						}
						$return_array['total_flights'] = $total_flights;
					}
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 106400: invalid request parameters";
			}			
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function update_o_o_impressions_for_flight()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'flights' => array(), 'total_flights' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);

			if(true || $response['is_success'])
			{
				$campaign_id = $this->input->post('campaign_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$flight_ids = $this->input->post('flight_id');
				$mpq_id = $this->input->post('mpq_id');
				$budget_allocation = $this->input->post('budget_allocation');
				$o_o_impressions = $this->input->post('o_o_impressions');
				$session_id = $this->session->userdata('session_id');
				
/*				$flight_ids = array("286514");
				$campaign_id = 18854;
//				$product_id = 34;
//				$region_id = 0;
//				$region_id = false;
//				$mpq_id = 77908;
//				$budget_allocation = "per_pop";
				$o_o_impressions = 555;
*/				
				if($product_id == false && $campaign_id == false)
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 106400: invalid request parameters";
					echo json_encode($return_array);
					return;				
				}

				$edit_flights_data = $this->mpq_v2_model->get_flight_rows($flight_ids);
				if($edit_flights_data == false)
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 688954: Failed to find flights data";
					echo json_encode($return_array);
					return;						
				}				
				if($campaign_id)
				{
					$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_campaign($campaign_id);
					if($cpms == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 785652: Failed to retrieve cpms.";
						echo json_encode($return_array);
						return;								
					}

					$campaign_details = $this->al_model->get_campaign_details($campaign_id);

					$product_data = $this->mpq_v2_model->get_product_data_for_submitted_product($campaign_details[0]['cp_submitted_products_id']);
					if($product_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 789456: Failed to retrieve product information.";
						echo json_encode($return_array);
						return;							
					}

					$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($campaign_details[0]['insertion_order_id']);

					//update and get flight for this product
					$budget_values = get_budget_calculation($edit_flights_data[$campaign_id]['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
					if($budget_values['audience_ext_impressions'] < 0)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "O&O Impressions would exceed budget. Please try a different value";
						echo json_encode($return_array);
						return;						
					}
					$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($edit_flights_data[$campaign_id]['id'], $o_o_impressions, $budget_values, false);
					if($saved_budget_values == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 123784: Could not save flight budgets";
						echo json_encode($return_array);
						return;
					}
					$flight_budgets = array();
					$flight_budgets['id'] = $edit_flights_data[$campaign_id]['id'];
					$flight_budgets['o_o_impressions'] = $o_o_impressions;
					$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
					$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
					$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
					$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
					$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
					$flight_budgets['region_index'] = 0;
					$modified_flights[] = $flight_budgets;
					$return_array['flights'] = $modified_flights;						
				}
				else
				{
					$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
					$product_data = $product_data[0];
					$submitted_products[] = array();

					//Determine if we're referring to a single submitted_product or not

					if($region_id !== "" && $region_id !== false)
					{

						//Update the one flight with the budgets and recalculate
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					}
					else
					{
						//Region id is not specified, do this for the entire product.
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
						if($budget_allocation == "per_pop")
						{
							$total_population = 0;
							$region_data = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id);
							if($region_data == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 378272: Failed to retrieve region data";
								echo json_encode($return_array);
								return;									
							}
							$region_data = json_decode($region_data);
							$region_data = (array) $region_data;
							foreach($submitted_product_response as $idx => $submitted_product)
							{
								$region_index = $submitted_product['region_data_index'];
								$region_page = (array)$region_data[$region_index];
								$population = $this->map->get_demographics_from_region_array((array)$region_page['ids']);
								if($population !== false)
								{
									$submitted_product_response[$idx]['region_population'] = $population['region_population'];
									$total_population += $population['region_population'];
								}
								else
								{
									$submitted_product_response[$idx]['region_population'] = 0;
								}									
							}
						}
					}
				
					if($submitted_product_response == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 106442: Failed to retrieve submitted product information";
						echo json_encode($return_array);
						return;						
					}

					$modified_flights = array();
					foreach($submitted_product_response AS $submitted_product)
					{
						$submitted_product_id = $submitted_product['id'];
						if($region_id === "" || $region_id === false)
						{
							if($budget_allocation == "even")
							{
								$region_o_o_impressions = $o_o_impressions/count($submitted_product_response);
							}
							elseif($budget_allocation == "per_pop")
							{
								$region_o_o_impressions = $o_o_impressions*($submitted_product['region_population']/$total_population);
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 21564: Invalid Region/Budget parameters";
								echo json_encode($return_array);
								return;										
							}
						}
						else //region is specified, and for builder this should mean custom
						{
							$region_o_o_impressions = $o_o_impressions;
						}
						$region_o_o_impressions = round($region_o_o_impressions);

						$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_submitted_product($submitted_product_id);
						if($cpms == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 132232: Failed to retrieve cpms";
							echo json_encode($return_array);
							return;
						}

						//Get budget values to use in update
						$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id);

						//update and get flight for this product
						$budget_values = get_budget_calculation($edit_flights_data[$submitted_product_id]['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $region_o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
						if($budget_values['audience_ext_impressions'] < 0)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "O&O Impressions would exceed budget. Please try a different value";
							echo json_encode($return_array);
							return;						
						}
						$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($edit_flights_data[$submitted_product_id]['id'], $region_o_o_impressions, $budget_values, $product_data['o_o_dfp']);
						if($saved_budget_values == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 123784: Could not save flight budgets";
							echo json_encode($return_array);
							return;
						}	
						$flight_budgets = array();
						$flight_budgets['id'] = $edit_flights_data[$submitted_product_id]['id'];
						$flight_budgets['o_o_impressions'] = $region_o_o_impressions;
						$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
						$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
						$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
						$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
						$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
						$flight_budgets['region_index'] = $submitted_product['region_data_index'];
						$modified_flights[] = $flight_budgets;
						$return_array['flights'] = $modified_flights;									
					}
					if(count($return_array['flights']))
					{
						$total_flights = null;
						foreach($return_array['flights'] as $flight)
						{
							if($total_flights == null)
							{
								$total_flights = $flight;
							}
							else
							{

								foreach($flight as $key => $budget_value)
								{	

									switch($key)
									{
										case "id":
											if(!is_array($total_flights["id"]))
											{
												$total_flights["id"] = array($total_flights["id"]);
											}
											$total_flights[$key][] = $budget_value;
											break;
										default:
											$total_flights[$key] += $budget_value;
											break;
									}
								}				
							}
						}
						$return_array['total_flights'] = $total_flights;
					}
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 106400: invalid request parameters";
			}			
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function poll_for_budget_values()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'data'=> null);
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);

			if(true || $response['is_success'])
			{
				$flight_ids = $this->input->post('flight_id');
				
				if($flight_ids != "" && $flight_ids != false)
				{
					$flight_data = $this->mpq_v2_model->retrieve_budget_data_for_flight($flight_ids);
					if($flight_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 462121: Failed to retrieve flight_data";
						echo json_encode($return_array);
						return;							
					}
					$return_array['data'] = $flight_data;
				}	
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 464891: Invalid parameters";
					echo json_encode($return_array);
					return;					
				}						
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}			
	}

	public function remove_flights()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$campaign_id = $this->input->post('campaign_id');
				$flight_ids = $this->input->post('flight_id');
				$budget_allocation = $this->input->post('budget_allocation');
				$product_id = $this->input->post('product_id');
				$mpq_id = $this->input->post('mpq_id');

/*				$flight_ids = array();
				$budget_allocation = "per_pop";
				$product_id = 34;
				$mpq_id = 77351;
*/
				if($campaign_id)
				{
					$deleted_flights = $this->mpq_v2_model->delete_flights_for_io($flight_ids);
					if($deleted_flights == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 454841: Failed to remove flights";
						echo json_encode($return_array);
						return;							
					}
				}
				elseif($mpq_id !== false && $product_id !== false && $budget_allocation !== false)
				{
					$saved_allocation_type = $this->mpq_v2_model->save_allocation_type_for_mpq_and_product($mpq_id, $product_id, $budget_allocation);
					if($saved_allocation_type == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 46684: Failed to update budget allocation";
						echo json_encode($return_array);
						return;	
					}
					if($flight_ids != "" && $flight_ids != false)
					{
						$deleted_flights = $this->mpq_v2_model->delete_flights_for_io($flight_ids);
						if($deleted_flights == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 454842: Failed to remove flights";
							echo json_encode($return_array);
							return;							
						}
					}	
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 464891: Invalid parameters";
						echo json_encode($return_array);
						return;					
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 1314125: Invalid parameters";
					echo json_encode($return_array);
					return;	
				}				
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}			
	}

	public function reforecast_flights()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$mpq_id = $this->input->post('mpq_id');

				if($product_id !== false && $mpq_id !== false)
				{
					$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
					if($product_data[0]['o_o_dfp'])
					{
						if($region_id !== "" && $region_id !== false)
						{
							//Region id is specified, only use one submitted product
							//Get submitted product
							$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
						}
						else
						{
							//Region id is not specified, do this for the entire product.
							$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
						}
						if($submitted_product_response == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 875425: failed to retrieve submitted product data";
							echo json_encode($return_array);
							return;									
						}
						foreach($submitted_product_response as $submitted_product)
						{
							$submitted_product_id = $submitted_product['id'];
							$nullify_forecast_result = $this->mpq_v2_model->reforecast_submitted_product($submitted_product_id);
							if($nullify_forecast_result == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 996541: failed to reset flights for forecast";
								echo json_encode($return_array);
								return;									
							}
						}
					}

				}				
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 1347626: user logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}		
	}

	public function retrieve_dfp_template_groups()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$search_string = $this->input->post('q');
			$product_id  = $this->input->post('product_id');
			$template_groups_array = array();
			$template_groups = $this->mpq_v2_model->get_select2_dfp_template_groups($search_string, $product_id);
			if($template_groups !== false)
			{
				$template_groups_array =  $template_groups;
			}
			echo json_encode($template_groups_array);
		}
		else
		{
			show_404();
		}		

	}

	public function assign_template_to_flights()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			$allowed_roles = array('sales', 'admin', 'ops');
			$response = vl_verify_ajax_call($allowed_roles);

			if($response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$mpq_id = $this->input->post('mpq_id');
				$template_group_id = $this->input->post('template_group_id');

				if($product_id !== false && $mpq_id !== false)
				{
					if($region_id !== "" && $region_id !== false)
					{
						//Region id is specified, only use one submitted product
						//Get submitted product
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					}
					else
					{
						//Region id is not specified, do this for the entire product.
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
					}
					if($submitted_product_response == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 875425: failed to retrieve submitted product data";
						echo json_encode($return_array);
						return;									
					}
					foreach($submitted_product_response as $submitted_product)
					{
						$submitted_product_id = $submitted_product['id'];
						$set_templates_result = $this->mpq_v2_model->assign_template_group_to_submitted_product($submitted_product_id, $template_group_id);
						if($set_templates_result == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 165454: failed to assign templates";
							echo json_encode($return_array);
							return;										
						}
					}

				}				
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 1347626: user logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}			
	}

	public function add_flight()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'cpms' => null, 'flights' => null, 'total_flights' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);
			$response = array('is_success' => true);

			if(true || $response['is_success'])
			{
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$campaign_id = $this->input->post('campaign_id');
				$start_date = $this->input->post('start_date');
				$end_date = $this->input->post('end_date');
				$flight_budget = $this->input->post('budget');
				$budget_allocation = $this->input->post('budget_allocation');
				$mpq_id = $this->input->post('mpq_id');

//				$product_id = 34;
//				$region_id = 0;
//				$region_id = false;
//				$mpq_id = 77908;
//				$budget_allocation = "per_pop";
//				$campaign_id = 18849;
//				$flight_budget = 3600;
//				$start_date = "2017-06-01";
//				$end_date = "2017-06-30";


				if($campaign_id !== false)
				{
					$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_campaign($campaign_id);
					if($cpms == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 2159: Failed to retrieve cpms";
						echo json_encode($return_array);
						return;
					}

					$campaign_details = $this->al_model->get_campaign_details($campaign_id);

					$product_data = $this->mpq_v2_model->get_product_data_for_submitted_product($campaign_details[0]['cp_submitted_products_id']);
					if($product_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 789456: Failed to retrieve product information.";
						echo json_encode($return_array);
						return;							
					}

					$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($campaign_details[0]['insertion_order_id']);


					//create flights
					$flights = get_flight_calculation($start_date, $end_date, "FIXED", null, $flight_budget);
					if($flights == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 157845: Failed to generate flight";
						echo json_encode($return_array);
						return;										
					}					

					$flights_response = $this->mpq_v2_model->get_flights_for_campaign($campaign_id);
					if($flights_response == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 741564: failed to retrieve flights";
						echo json_encode($return_array);
						return;		
					}

					if(strtotime($start_date) <= strtotime($flights_response[0]['start_date']))
					{
						$needs_end = true;
						if(strtotime($end_date." +1 days") == strtotime($flights_response[0]['start_date']))
						{
							$needs_end = false;
						}

						$flight_insert = $this->mpq_v2_model->insert_single_flight_for_submitted_product($campaign_id, $start_date, $end_date, $flight_budget, false, true, $needs_end);
					}
					elseif(strtotime($start_date) >= strtotime($flights_response[count($flights_response)-1]['start_date']))
					{
						$needs_end = true;
						$flight_insert = $this->mpq_v2_model->insert_single_flight_for_submitted_product($campaign_id, $start_date, $end_date, $flight_budget, false, true, $needs_end);
					}
					else
					{
						//New flight goes in middle
						foreach($flights_response as $flight_idx => $existing_flight)
						{
							if(strtotime($start_date) <= strtotime($existing_flight['start_date']))
							{
								$needs_end = true;
								if(strtotime($end_date." +1 days") == strtotime($flights_response[$flight_idx]['start_date']))
								{
									$needs_end = false; 										
								}
								$flight_insert = $this->mpq_v2_model->insert_single_flight_for_submitted_product($campaign_id, $start_date, $end_date, $flight_budget, false, true, $needs_end);
								break;	
							}
						}
					}
					$submitted_product_flights = array();
					foreach($flight_insert as $idx => $flight)
					{
						if($idx != count($flight_insert) && $flight['budget'] != 0)
						{
							//calculate budget
							$o_o_impressions = $product_data['o_o_enabled'] ? (($product_data['o_o_max_ratio']*0.01)*$flight['budget'])/$cpms['o_o'] * 1000 : 0;
							$budget_values = get_budget_calculation($flight_budget, $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
							$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
							if($saved_budget_values == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 123784: Could not save flight budgets";
								echo json_encode($return_array);
								return;
							}
							//save
							$flight_budgets = array();
							$flight_budgets['id'] = $flight['id'];
							$flight_budgets['start_date'] = $start_date;
							$flight_budgets['end_date'] = $end_date;
							$flight_budgets['total_budget'] = $flight['budget'];
							$flight_budgets['o_o_impressions'] = $o_o_impressions;
							$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
							$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
							$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
							$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
							$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
							$flight_budgets['region_index'] = 0;
							$submitted_product_flights[] = $flight_budgets;
						}
					}
					$return_flights[] = $submitted_product_flights;
					$return_array['flights'] = $return_flights;
				}
				else
				{
					if($product_id !== false && $mpq_id !== false)
					{
						$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
						$region_data = null;
						if($product_data != false)
						{	
							$product_data = $product_data[0];
							$submitted_products[] = array();
							//Determine if we're referring to a single submitted_product or not
							$saved_allocation_type = $this->mpq_v2_model->save_allocation_type_for_mpq_and_product($mpq_id, $product_id, $budget_allocation);
							if($saved_allocation_type == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 46684: Failed to update budget allocation";
								echo json_encode($return_array);
								return;	
							}						
							if($region_id !== "" && $region_id !== false)
							{
								//Region id is specified, only use one submitted product
								//Get submitted product
								$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
							}
							else
							{
								$region_data = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id);
								if($region_data == false)
								{
									$return_array['is_success'] = false;
									$return_array['errors'] = "Error 378272: Failed to retrieve region data";
									echo json_encode($return_array);
									return;									
								}
								//Region id is not specified, do this for the entire product.
								$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
								if($budget_allocation == "per_pop")
								{
									$total_population = 0;

									$region_data = json_decode($region_data);
									$region_data = (array) $region_data;
									foreach($submitted_product_response as $idx => $submitted_product)
									{
										$region_index = $submitted_product['region_data_index'];
										$region_page = (array)$region_data[$region_index];
										$population = $this->map->get_demographics_from_region_array((array)$region_page['ids']);
										if($population !== false)
										{
											$submitted_product_response[$idx]['region_population'] = $population['region_population'];
											$total_population += $population['region_population'];
										}
										else
										{
											$submitted_product_response[$idx]['region_population'] = 0;
										}									
									}
								}
							}
							if($submitted_product_response != false)
							{
								$return_flights = array();
								foreach($submitted_product_response as $submitted_product)
								{
									$submitted_product_id = $submitted_product['id'];
									if($region_id === "" || $region_id === false)
									{
										if($budget_allocation == "even")
										{
											$region_budget = $flight_budget/count($submitted_product_response);
										}
										else 
										{
											$region_budget = $flight_budget*($submitted_product['region_population']/$total_population);
										}
									}
									else
									{
										$region_budget = $flight_budget;
									}

									//$this->mpq_v2_model->delete_all_timeseries_data_for_submitted_product($submitted_product_id);

									$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_submitted_product($submitted_product_id);
									if($cpms == false)
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Error 2155: Failed to retrieve cpms";
										echo json_encode($return_array);
										return;
									}
									//create flights
									$flights = get_flight_calculation($start_date, $end_date, "FIXED", null, $region_budget);
									if($flights == false)
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Error 157845: Failed to generate flight";
										echo json_encode($return_array);
										return;										
									}

									$existing_flights = $this->mpq_v2_model->get_flights_data_for_submitted_product($submitted_product_id);
									//try to determine which position the new flight is gonna be in
									//Lazy checks for before and after all flights:
									if(strtotime($start_date) <= strtotime($existing_flights[0]['start_date']))
									{
										$needs_end = true;
										if(strtotime($end_date." +1 days") == strtotime($existing_flights[0]['start_date']))
										{
											$needs_end = false;
										}

										$flight_insert = $this->mpq_v2_model->insert_single_flight_for_submitted_product($submitted_product_id, $start_date, $end_date, $region_budget, $product_data['o_o_dfp'], false, $needs_end);
									}
									elseif(strtotime($start_date) >= strtotime($existing_flights[count($existing_flights)-1]['start_date']))
									{
										$needs_end = true;
										$flight_insert = $this->mpq_v2_model->insert_single_flight_for_submitted_product($submitted_product_id, $start_date, $end_date, $region_budget, $product_data['o_o_dfp'], false, $needs_end);
									}
									else
									{
										//New flight goes in middle
										foreach($existing_flights as $flight_idx => $existing_flight)
										{
											if(strtotime($start_date) < strtotime($existing_flight['start_date']))
											{
												$needs_end = true;
												if(strtotime($end_date." +1 days") == strtotime($existing_flights[$flight_idx]['start_date']))
												{
													$needs_end = false; 										
												}
												$flight_insert = $this->mpq_v2_model->insert_single_flight_for_submitted_product($submitted_product_id, $start_date, $end_date, $region_budget, $product_data['o_o_dfp'], false, $needs_end);
												break;	
											}
										}
									};

									if($product_data['o_o_dfp'])
									{
										$zips = $region_data[$submitted_product['region_data_index']]->ids->zcta;
										//Get line item template id for zips

										$order_template = $this->mpq_v2_model->get_dfp_object_template_for_zips($zips, "ORDER");
										if($order_template === false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 145715: Failed to retireve dfp template";
											echo json_encode($return_array);
											return;											
										}

										$line_item_template = $this->mpq_v2_model->get_dfp_object_template_for_zips($zips, "LINE_ITEM");
										if($line_item_template === false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 145714: Failed to retireve dfp template";
											echo json_encode($return_array);
											return;											
										}
										//Save template 
										$saved_order_template = $this->mpq_v2_model->save_dfp_object_template_for_submitted_product($order_template, $submitted_product_id, "ORDER");
										$saved_line_item_template = $this->mpq_v2_model->save_dfp_object_template_for_submitted_product($line_item_template, $submitted_product_id, "LINE_ITEM");
										if($saved_order_template == false || $saved_line_item_template == false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 174562: Failed to save initial dfp templates";
											echo json_encode($return_array);
											return;											
										}
									}

								//PLACEHOLDER:
								$submitted_product_flights = array();
								foreach($flight_insert as $idx => $flight)
								{
									if($idx != count($flight_insert) && $flight['budget'] != 0)
									{
										//calculate budget
										$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id);
										$o_o_impressions = $product_data['o_o_enabled'] ? round((($product_data['o_o_max_ratio']*0.01)*$flight['budget'])/$cpms['o_o'] * 1000) : 0;
										$budget_values = get_budget_calculation($flight['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
										$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
										if($saved_budget_values == false)
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 123784: Could not save flight budgets";
											echo json_encode($return_array);
											return;
										}
										//save
										$flight_budgets = array();
										$flight_budgets['id'] = $flight['id'];
										$flight_budgets['start_date'] = $start_date;
										$flight_budgets['end_date'] = $end_date;
										$flight_budgets['total_budget'] = $flight['budget'];
										$flight_budgets['o_o_impressions'] = $o_o_impressions;
										$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
										$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
										$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
										$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
										$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
										$flight_budgets['region_index'] = $submitted_product['region_data_index'];
										$submitted_product_flights[] = $flight_budgets;
									}
								}
								$return_flights[] = $submitted_product_flights;
								//Something($submitted_product_id);??????
							}

								if($region_id === "" || $region_id === false)
								{
									//generate summation array
									$flight_sums = array();
									foreach($return_flights as $flights)
									{
										if(empty($flight_sums))
										{
											$flight_sums = $flights;
										}
										else
										{
											foreach($flights as $idx => $flight)
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
														default:
															$flight_sums[$idx][$key] += round($budget_value, 2);
															break;
													}
												}
											}
										}
									}
									$return_array['total_flights'] = $flight_sums;
								}
								$return_array['cpms'] = $cpms;
								$return_array['flights'] = $return_flights;
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 047217: failed to retrieve submitted product data";							
							}
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 006419: failed to retrieve product data";						
						}
					}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 006400: invalid request parameters";
				}
                            }
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 006403: user logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}

	}		

	public function update_cpm()
	{
		if(true || $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'flights' => null, 'total_flights' => array());
			$allowed_roles = array('sales', 'admin', 'ops');
			//$response = vl_verify_ajax_call($allowed_roles);

			if(true || $response['is_success'])
			{
				$campaign_id = $this->input->post('campaign_id');
				$mpq_id = $this->input->post('mpq_id');
				$product_id = $this->input->post('product_id');
				$region_id = $this->input->post('region_id');
				$budget_allocation = $this->input->post('budget_allocation');
				$cpm_value = $this->input->post('cpm_value');

				if($campaign_id)
				{
					$flights_response = $this->mpq_v2_model->get_flights_for_campaign($campaign_id);
					if($flights_response == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 741564: failed to retrieve flights";
						echo json_encode($return_array);
						return;		
					}

					$campaign_details = $this->al_model->get_campaign_details($campaign_id);

					$product_data = $this->mpq_v2_model->get_product_data_for_submitted_product($campaign_details[0]['cp_submitted_products_id']);
					if($product_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 789456: Failed to retrieve product information.";
						echo json_encode($return_array);
						return;							
					}

					foreach($cpm_value as $type => $value)
					{
						if($product_data['product_identifier'] != "preroll")
						{
							$subproduct_type_id = $this->mpq_v2_model->translate_subproduct_prefix($type);
						}
						else
						{
							$subproduct_type_id = $this->mpq_v2_model->translate_subproduct_prefix("preroll");
						}
						$updated_cpm = $this->mpq_v2_model->update_product_cpm_for_campaign($campaign_id, $subproduct_type_id, $value);						
					}

					$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_campaign($campaign_id);	
					if($cpms == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 21564: Failed to save cpms";
						echo json_encode($return_array);
						return;
					}

					$flights = $this->mpq_v2_model->get_flights_data_for_campaign($campaign_id);
					if($flights == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 145261: Failed to generate flights";
						echo json_encode($return_array);
						return;										
					}

					$this->mpq_v2_model->delete_all_timeseries_data_for_campaign($campaign_id, true);

					$insert_flights = $this->mpq_v2_model->insert_flights_with_budgets_for_campaign($campaign_id, $flights);
					if($insert_flights == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 145712: Could not save flights";
						echo json_encode($return_array);
						return;											
					}

					$this->mpq_v2_model->add_dfp_order_rows_for_campaign($campaign_id, $product_data['o_o_dfp']);

					$campaign_flights = array();
					foreach($insert_flights as $idx => $flight)
					{
						if($idx != count($insert_flights) && $flight['budget'] != 0)
						{
							if($this->mpq_v2_model->does_campaign_have_geofencing($campaign_id))
							{
								$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($campaign_details[0]['insertion_order_id']);
							}
							else
							{
								$geofencing_inventory = 0;
							}

							$o_o_impressions = $product_data['o_o_enabled'] ? (($product_data['o_o_max_ratio']*0.01)*$flight['budget'])/$cpms['o_o'] * 1000 : 0;

							$budget_values = get_budget_calculation($flight['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
							$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
							if($saved_budget_values == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 123784: Could not save flight budgets";
								echo json_encode($return_array);
								return;
							}
							//save
							$flight_budgets = array();
							$next_flight = $insert_flights[$idx+1];
							$flight_budgets['id'] = $flight['id'];
							$flight_budgets['start_date'] = $flight['series_date'];
							$flight_budgets['end_date'] = date('Y-m-d', strtotime('-1 day', strtotime($next_flight['series_date'])));
							$flight_budgets['total_budget'] = $flight['budget'];
							$flight_budgets['o_o_impressions'] = $o_o_impressions;
							$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
							$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
							$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
							$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
							$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
							$flight_budgets['region_index'] = 0;
							$campaign_flights[] = $flight_budgets;
						}
					}
					$return_array['flights'] = $campaign_flights;
				}
				else
				{
					if($product_id == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 106400: invalid request parameters";
						echo json_encode($return_array);
						return;				
					}
					$product_data = $this->mpq_v2_model->get_products_by_product_id_array(array($product_id));
					$product_data = $product_data[0];
					$region_data = $this->mpq_v2_model->get_region_data_by_mpq_id($mpq_id);
					if($region_data == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 378272: Failed to retrieve region data";
						echo json_encode($return_array);
						return;									
					}
					$region_data = json_decode($region_data);
					$region_data = (array) $region_data;

					$saved_allocation_type = $this->mpq_v2_model->save_allocation_type_for_mpq_and_product($mpq_id, $product_id, $budget_allocation);
					$submitted_products[] = array();
					//Determine if we're referring to a single submitted_product or not
					if($region_id !== "" && $region_id !== false)
					{
						//Region id is specified, only use one submitted product
						//Get submitted product
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_mpq_id_product_and_region_index($mpq_id, $product_id, $region_id);
					}
					else
					{
						//Region id is not specified, do this for the entire product.
						$submitted_product_response = $this->mpq_v2_model->get_submitted_products_by_product_id_and_mpq_id($product_id, $mpq_id);
					}

					if($submitted_product_response == false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 106442: Failed to retrieve submitted product information";
						echo json_encode($return_array);
						return;						
					}
					foreach($submitted_product_response as $submitted_product)
					{
						$submitted_product_id = $submitted_product['id'];

						foreach($cpm_value as $type => $value)
						{
							if($product_data['product_identifier'] != "preroll")
							{
								$subproduct_type_id = $this->mpq_v2_model->translate_subproduct_prefix($type);
							}
							else
							{
								$subproduct_type_id = $this->mpq_v2_model->translate_subproduct_prefix("preroll");
							}
							$updated_cpm = $this->mpq_v2_model->update_product_cpm_for_submitted_product($submitted_product_id, $subproduct_type_id, $value);						
						}


						$cpms = $this->mpq_v2_model->retrieve_product_cpms_for_submitted_product($submitted_product_id);	
						if($cpms == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 21564: Failed to save cpms";
							echo json_encode($return_array);
							return;
						}

						$flights = $this->mpq_v2_model->get_flights_data_for_submitted_product($submitted_product_id);
						if($flights == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 145261: Failed to generate flights";
							echo json_encode($return_array);
							return;										
						}

						$this->mpq_v2_model->delete_all_timeseries_data_for_submitted_product($submitted_product_id, true);

						$insert_flights = $this->mpq_v2_model->insert_flights_with_budgets_for_submitted_product($submitted_product_id, $flights);
						if($insert_flights == false)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 145712: Could not save flights";
							echo json_encode($return_array);
							return;											
						}
						//Assuming an array of flights to get inserted into campaigns_time_series gets returned. Insert that straight up

						//create dfp rows based off flights
						$added_subproduct_rows = $this->mpq_v2_model->add_dfp_order_rows_for_submitted_product($submitted_product_id, $product_data['o_o_dfp']);

						if($product_data['o_o_dfp'])
						{
							$zips = $region_data[$submitted_product['region_data_index']]->ids->zcta;
							//Get line item template id for zips

							$order_template = $this->mpq_v2_model->get_dfp_object_template_for_zips($zips, "ORDER");
							if($order_template === false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 145715: Failed to retireve dfp template";
								echo json_encode($return_array);
								return;											
							}

							$line_item_template = $this->mpq_v2_model->get_dfp_object_template_for_zips($zips, "LINE_ITEM");
							if($line_item_template === false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 145714: Failed to retireve dfp template";
								echo json_encode($return_array);
								return;											
							}
							//Save template 
							$saved_order_template = $this->mpq_v2_model->save_dfp_object_template_for_submitted_product($order_template, $submitted_product_id, "ORDER");
							$saved_line_item_template = $this->mpq_v2_model->save_dfp_object_template_for_submitted_product($line_item_template, $submitted_product_id, "LINE_ITEM");
							if($saved_order_template == false || $saved_line_item_template == false)
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 174562: Failed to save initial dfp templates";
								echo json_encode($return_array);
								return;											
							}
						}

						//PLACEHOLDER:
						$submitted_product_flights = array();
						foreach($insert_flights as $idx => $flight)
						{
							if($idx != count($insert_flights) && $flight['budget'] != 0)
							{
								//calculate budget
								if($this->mpq_v2_model->does_mpq_region_have_geofencing_points($mpq_id, $submitted_product['region_data_index']))
								{
									$geofencing_inventory = $this->mpq_v2_model->calculate_geofencing_max_inventory($mpq_id);
								}
								else
								{
									$geofencing_inventory = 0;
								}
								$o_o_impressions = $product_data['o_o_enabled'] ? (($product_data['o_o_max_ratio']*0.01)*$flight['budget'])/$cpms['o_o'] * 1000 : 0;

								$budget_values = get_budget_calculation($flight['budget'], $cpms['ax'], $cpms['gf'], $cpms['o_o'], $o_o_impressions, $cpms['gf_max_dollar_pct']*0.01, $product_data['o_o_max_ratio'], $geofencing_inventory);
								$saved_budget_values = $this->mpq_v2_model->save_budget_calcs_for_flight($flight['id'], $o_o_impressions, $budget_values, $product_data['o_o_dfp']);
								if($saved_budget_values == false)
								{
									$return_array['is_success'] = false;
									$return_array['errors'] = "Error 123784: Could not save flight budgets";
									echo json_encode($return_array);
									return;
								}
								//save
								$flight_budgets = array();
								$next_flight = $insert_flights[$idx+1];
								$flight_budgets['id'] = $flight['id'];
								$flight_budgets['start_date'] = $flight['series_date'];
								$flight_budgets['end_date'] = date('Y-m-d', strtotime('-1 day', strtotime($next_flight['series_date'])));
								$flight_budgets['total_budget'] = $flight['budget'];
								$flight_budgets['o_o_impressions'] = $o_o_impressions;
								$flight_budgets['o_o_budget'] = $budget_values['o_o_budget'];
								$flight_budgets['gf_budget'] = $budget_values['geofencing_budget'];
								$flight_budgets['gf_impressions'] = $budget_values['geofencing_impressions'];
								$flight_budgets['ax_impressions'] = $budget_values['audience_ext_impressions'];
								$flight_budgets['ax_budget'] = $budget_values['audience_ext_budget'] + $budget_values['geofencing_budget'];
								$flight_budgets['region_index'] = $submitted_product['region_data_index'];
								$submitted_product_flights[] = $flight_budgets;
							}
						}
						$return_flights[] = $submitted_product_flights;
					}

					if($region_id === "" || $region_id === false)
					{
						//generate summation array
						$flight_sums = array();
						foreach($return_flights as $flights)
						{
							if(empty($flight_sums))
							{
								$flight_sums = $flights;
							}
							else
							{
								foreach($flights as $idx => $flight)
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
											default:
												$flight_sums[$idx][$key] += $budget_value;
												break;

										}
									}
								}
							}
						}
						$return_array['total_flights'] = $flight_sums;
					}
					$return_array['flights'] = $return_flights;
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 006403: user logged out or not permitted";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}

	public function migrate_campaigns_flights_to_dollars($pivot_date, $before_or_after, $partner_id = null)
	{
		if($this->input->is_cli_request())
		{	
			if(empty($pivot_date) || empty($before_or_after))
			{
				echo "USAGE: php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] (OPTIONAL PARTNER ID)"; 
			}

			if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $pivot_date))
			{
				echo "USAGE: php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] (OPTIONAL PARTNER ID)";
			}

			if($before_or_after != "before" && $before_or_after != "after")
			{
				echo "USAGE: php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] (OPTIONAL PARTNER ID)"; 
			}

			$adgroups = $this->mpq_v2_model->update_adgroup_subproduct_types();
			if($adgroups)
			{
				$migrated = $this->mpq_v2_model->migrate_campaigns_flights_to_dollars($pivot_date, $before_or_after, $partner_id);
			}
		}
		else
		{
			show_404();
		}
	}

	public function update_adgroup_subproduct_types()
	{
		if($this->input->is_cli_request())
		{		
			$this->mpq_v2_model->update_adgroup_subproduct_types();
		}
		else
		{
			show_404();
		}
	}

	public function migrate_io_flights_to_dollars($pivot_date, $before_or_after, $partner_id = null)
	{
		if($this->input->is_cli_request())
		{
			if(empty($pivot_date) || empty($before_or_after))
			{
				echo "USAGE: php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] (OPTIONAL PARTNER ID)"; 
			}

			if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $pivot_date))
			{
				echo "USAGE: php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] (OPTIONAL PARTNER ID)";
			}

			if($before_or_after != "before" && $before_or_after != "after")
			{
				echo "USAGE: php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] (OPTIONAL PARTNER ID)"; 
			}
			$migrated = $this->mpq_v2_model->migrate_io_flights_to_dollars($pivot_date, $before_or_after);
		}
		else
		{
			show_404();
		}
	}

	public function migrate_unlinked_campaigns_to_dollars($migrate_all = false)
	{
		if($this->input->is_cli_request())
		{
			$migrated = $this->mpq_v2_model->migrate_unlinked_campaigns_to_dollars($migrate_all);
		}
		else
		{
			show_404();
		}
	}

	public function calculate_budgets_for_cpm_defined_campaigns_without_budgets()
	{
		if($this->input->is_cli_request())
		{
			$migrated = $this->mpq_v2_model->calculate_budgets_for_cpm_defined_campaigns_without_budgets();
		}
		else
		{
			show_404();
		}		
	}

	public function calculate_budget_for_cpm_defined_campaign($campaign_id)
	{
		if($this->input->is_cli_request())
		{
			$this->mpq_v2_model->calculate_budget_for_cpm_defined_campaign($campaign_id);
		}
		else
		{
			show_404();
		}
	}

	public function migrate_campaigns_for_partner($pivot_date, $before_or_after, $partner_id, $display_cpm, $o_o_cpm, $geofencing_cpm, $geofencing_max_percentage)
	{
		if($this->input->is_cli_request())
		{
			if(empty($pivot_date) || empty($before_or_after))
			{
				echo "USAGE (1): php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] [partner_id] [display cpm] [o&o cpm] [geofencing cpm] [geofencing max percentage]"; 
				return;
			}

			if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $pivot_date))
			{
				echo "USAGE (2): php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] [partner_id] [display cpm] [o&o cpm] [geofencing cpm] [geofencing max percentage]"; 
			}

			if($before_or_after != "before" && $before_or_after != "after")
			{
				echo "USAGE (3): php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] [partner_id] [display cpm] [o&o cpm] [geofencing cpm] [geofencing max percentage]";  
			}

			if(!$display_cpm || !$o_o_cpm || !$geofencing_cpm || !$geofencing_max_percentage)
			{
				echo "USAGE (4): php index.php mpq_v2 migrate_campaigns_flights_to_dollars [YYYY-MM-DD] [before|after] [partner_id] [display cpm] [o&o cpm] [geofencing cpm] [geofencing max percentage]"; 
			}
			$migrated = $this->mpq_v2_model->migrate_campaigns_for_partner($pivot_date, $before_or_after, $partner_id, $display_cpm, $o_o_cpm, $geofencing_cpm, $geofencing_max_percentage);
		}
		else
		{
			show_404();
		}
	}


	//DFP Stuff
	public function create_order_line_item_in_dfp($mpq_id, $dfp_advertiser_id)
	{
		$final_array_details = $this->mpq_v2_model->get_all_budget_details_for_io($mpq_id);

		$assign_dfp_advertiser = $this->mpq_v2_model->link_dfp_advertiser_to_io($mpq_id,$dfp_advertiser_id);
		$return_array = array("success" => true, "data" => null);

		foreach($final_array_details as $key => $order_details)
		{
			/*$submitted_product_details = array();
			$submitted_product_details['product_id'] = $key;
			$submitted_product_details['adset_id'] = $order_details[0]['creative_sizes'][0]['adset_id'];
			$submitted_product_details['adset_name'] = $order_details[0]['creative_sizes'][0]['adset_name'];
			$submitted_product_details['version_id'] = $order_details[0]['creative_sizes'][0]['version_id'];
			$submitted_product_creative_details = $order_details[0]['creative_sizes'];
			*/
			//Clone adset,version,creatives, ad assets
			//$clone_submitted_product_adset = $this->mpq_v2_model->clone_submitted_product_adset_creatives($submitted_product_details, $submitted_product_creative_details);

			//Order Create
			$dfp_object_template_order_id = $order_details[0]['dfp_object_templates_order_id'];
			$new_order_details['dfp_advertiser_id'] = $dfp_advertiser_id;

			$dfp_object_template_order_id = 2; 

			$dfp_json_object = $this->mpq_v2_model->get_dfp_object_template($dfp_object_template_order_id);
			$dfp_object_template = json_decode($dfp_json_object[0]['object_blob']);

			$new_order_details['order_name'] = $dfp_json_object[0]['name'].' [ '.$order_details[0]['start_date'].' ] '.time();

			$order_object = $this->google_api->generate_order($new_order_details, $dfp_object_template);

			if($order_object)
			{
				$dfp_order_detail = $this->google_api->create_order($order_object);

				if($dfp_order_detail['success'] != '')
				{
					// Creative Ad tags
					foreach($order_details[0]['creative_sizes'] as $creative_size)
					{
						$creative_sizes[] = $creative_size['size'];
						// Detect Publisher type and push respective ad tags
						if($creative_size['published_ad_server'] == 3 && $creative_size['ad_tag'] != NULL)
						{
							$creative_size['ad_tag'] = $this->add_macros_in_tags($creative_size['ad_tag']);
						}
						elseif($creative_size['ad_tag'] == NULL)
						{
							// Create Ad tags Functionality

							$submitted_product = $this->mpq_v2_model->get_display_submitted_product_for_mpq_id($mpq_id);
							$cp_submitted_product_id = $submitted_product['id'];
							$creative_size['ad_tag'] = $this->create_ad_tags($cp_submitted_product_id, $creative_size['size'], $creative_size['version_id']);
							if(!$creative_size['ad_tag'])
							{
								continue; // If Ad Tag is not created we have not pushed to DFP
							}
							$creative_size['ad_tag'] = $this->add_macros_in_tags($creative_size['ad_tag']);
						}
						
						$creatives_array[] = $this->google_api->generate_third_party_creative('Test Creative', $dfp_advertiser_id, $creative_size['size'], $creative_size['ad_tag']);
					}
					
					// Create New Creatives in DFP and Assign those Ids in freq creative tab
					if(count($creatives_array))
					{
						$dfp_creative_array = $this->google_api->create_creatives($creatives_array);
					}
					else
					{
						$return_array['success'] = false;
						$return_array['msg'] = 'Unable to create Creatives in DFP';
						//echo json_encode($return_array);
						return $return_array;
					}
						
					// Create Line Item
					foreach($order_details as $line_item_detail)
					{
						//Create New row for Order Id in io_dfp_line_item_table

						$create_order_status = $this->mpq_v2_model->insert_new_dfp_order_id_to_freq($mpq_id, $dfp_order_detail['data'][0]->id);
						$dfp_object_template_line_item_id = $line_item_detail['dfp_object_templates_lineitem_id'];

						$dfp_object_template_line_item_id = 9; //$line_item_detail['dfp_object_templates_lineitem_id'];
						$dfp_json_object = $this->mpq_v2_model->get_dfp_object_template($dfp_object_template_line_item_id);
						$dfp_line_item_object_template = json_decode($dfp_json_object[0]['object_blob']);

						$new_line_item_details['line_item_name'] = $dfp_json_object[0]['name'].' '.$line_item_detail['start_date'].time();
						$new_line_item_details['start_date'] = $line_item_detail['start_date'];
						$new_line_item_details['end_date'] = $line_item_detail['end_date'];
						$new_line_item_details['o_o_impressions'] = $line_item_detail['o_o_impressions'];
						$new_line_item_details['order_id'] = $dfp_order_detail['data'][0]->id; //620264670,623975070;
						$new_line_item_details['cost_per_unit'] = ($line_item_detail['o_o_budget']/$line_item_detail['o_o_impressions']) * 1000;

						$new_line_item_details['creative_sizes'] = $creative_sizes;
						
						$new_line_item_details['google_api_location_ids'] = $this->google_api->convert_zips_to_google_api_location_ids($line_item_detail['geo_data']->zcta);
						
						$line_item_object = $this->google_api->generate_line_item($new_line_item_details, $dfp_line_item_object_template);
						if($line_item_object)
						{
							$dfp_line_item = $this->google_api->create_line_item($line_item_object);

							if($dfp_line_item['success'] != '')
							{
								$dfp_order_details[$dfp_order_detail['data'][0]->id][] = $dfp_line_item['data'][0]->id;
								// Link Creatives with the Line Item
								$linking_result = $this->google_api->link_creatives_to_line_item($dfp_creative_array['data'], $dfp_line_item['data'][0]->id);
								if($linking_result['success'] != NULL )
								{
									// Add 
								}
								else
								{
									$return_array['success'] = false;
									$return_array['msg'] = 'Not Sccessfully Linked : '.$linking_result['success'];
									return $return_array;
								}
								
								// Make entry in Frequence Database For Only Line ItemIO DFP
								$new_line_item_id = $dfp_line_item['data'][0]->id;
								if($create_order_status['is_success'])
								{
									$mapping_status = $this->mpq_v2_model->link_line_item_id_with_order_id($create_order_status['last_inserted_id'], $new_line_item_id);
								}
								$adgroups = $this->mpq_v2_model->create_adgroup_per_line_item($new_line_item_id);
								
								if($adgroups['is_success'])
								{
									$io_dfp_adgroup = $this->mpq_v2_model->update_io_flight_subproduct_with_adgroup($adgroups['adgroup_id'], $line_item_detail['io_timeseries_id']);
								}

								$approve_order = $this->google_api->approve_order_with_id($dfp_order_detail['data'][0]->id);
								if($approve_order != true)
								{
									//failed
								}

							}
							else
							{
								$return_array['success'] = false;
								$return_array['msg'] = 'DFP Line Item Create API Fails : '.$dfp_line_item['data'];
								return $return_array;
							}
						}
						else
						{
							$return_array['success'] = false;
							$return_array['msg'] = "DFP Line object was not created Properly";
							return $return_array;
						}
					}
				}
				else
				{
					$return_array['success'] = false;
					$return_array['msg'] = 'DFP Create Order API Fails : '.$dfp_order_detail['data'];
					return $return_array;
				}
			}
			else
			{
				$return_array['success'] = false;
				$return_array['msg'] = 'Errors : Order object was not created properly';
			}
			
		}

		return $return_array;

	}
	

	
	public function delete_existing_order()
	{
		$mpq_id = 77442;
		$update_status = array();
		$remove_dfp_order_ids = $this->mpq_v2_model->get_dfp_order_id_by_mpq_id($mpq_id);
		
		if($remove_dfp_order_ids)
		{
			foreach($remove_dfp_order_ids as $remove_dfp_order_id)
			{
				//$remove_dfp_order_id['dfp_order_id'] = 627995550;
				echo '<br/>Id Deleted : '.$remove_dfp_order_id['dfp_order_id'];
				$result_status = $this->google_api->delete_order_from_dfp($remove_dfp_order_id['dfp_order_id']);
				
				if($result_status)
				{
					$update_status = $this->mpq_v2_model->update_freq_table($remove_dfp_order_id['dfp_order_id']);
					echo '<br/>'.$update_status['msg'];
				}
				
				
			}
		}
	}
	
	public function add_macros_in_tags($ad_tags)
	{
		$return_ad_tag = '';
		$return_ad_tag = str_replace('fas_candu=', 'fas_candu=%%CLICK_URL_ESC%%', $ad_tags);
		$return_ad_tag = str_replace('fas_c=', 'fas_c=%%CLICK_URL_ESC%%', $return_ad_tag);
		$return_ad_tag = str_replace('fas_c_for_js=""', 'fas_c_for_js="%%CLICK_URL_ESC%%"', $return_ad_tag);
		$return_ad_tag = str_replace('fas_candu_for_js="', 'fas_candu_for_js="%%CLICK_URL_ESC%%', $return_ad_tag);
		$return_ad_tag = str_replace('A HREF="', 'A HREF="%%CLICK_URL_ESC%%', $return_ad_tag);
		return $return_ad_tag;
	}
	
	public function create_ad_tags($vl_campaign_id, $creative_size, $version_id)
	{
		$landing_page = 'http://test-rohan.com';		
		$assets = $this->cup_model->get_assets_by_adset($version_id, $creative_size);
		$builder_version = $this->cup_model->get_builder_version_by_version_id($version_id);
		if ($this->cup_model->files_ok($assets, $creative_size, $builder_version, FALSE, FALSE, TRUE))
		{
			$creative = $this->cup_model->prep_file_links($assets,$creative_size, $vl_campaign_id);
			$fas_push_success = $this->fas_model->push_fas_creatives($creative, $creative_size, $version_id, FALSE, $landing_page);
			
			if ($fas_push_success['is_success'])
			{
				$this->cup_model->mark_version_published_time($version_id);
				return $fas_push_success['ad_tag'];
				
			}
			else
			{
				//echo '<span class="label-warning" title="'.htmlentities($fas_push_success['err_msg']).'"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
				return false;
			}
		}
	}
	
	public function create_new_dfp_order()
	{
		$status = array();
		$sent_mail_status = '';
		$mpq_id = $this->input->post("mpq_id");//$mpq_id_response['id'];
		$dfp_advertiser_id = $this->input->post("dfp_advertiser_id");
				
		$status = $this->create_order_line_item_in_dfp($mpq_id, $dfp_advertiser_id);
		
		if($status['success'] == false || $status['success'] == '')
		{
		    $status['sent_mail_status'] = $this->send_failed_dfp_order_email($status, $mpq_id, $dfp_advertiser_id);
		    $status['success'] = false;
		}
		
		echo json_encode($status);
		return $status;
	}
		
	
	private function send_failed_dfp_order_email($result_status, $mpq_id, $dfp_advertiser_id)
	{
		$to = 'Tech Logs <tech.logs@frequence.com>';
		$from = 'no-reply@frequence.com';
		$mailgun_extras['h:reply-to'] = 'tech@frequence.com';
		$subject = 'DFP Failed Order '.Date('Y-m-d H:m:s');
		$mpq_summary_data = $this->mpq_v2_model->get_mpq_summary_data($mpq_id);
		$message = '';
				
		$message .= 'Advertiser Name: '.$mpq_summary_data['advertiser_name'];
		$message .= '<br/>Unique Display Id : '.$mpq_summary_data['unique_display_id'];
		$message .= '<br/>Order Id : '.$mpq_summary_data['order_id'];
		$message .= '<br/>Order Name : '.$mpq_summary_data['order_name'];
		$message .= '<br/>Advertiser Website : '.$mpq_summary_data['advertiser_website'];
		$message .= '<br/>DFP Advertiser Id : '.$dfp_advertiser_id;
		$message .= '<br/>Error Message : '.$result_status['msg'];
		
		$result = mailgun(
			$from,
			$to,
			$subject,
			$message,
			"html",
			$mailgun_extras
		);
		return $result;
		
	}

	public function get_order_summary()
	{
		$session_id = $this->session->userdata('session_id');
		$mpq_id_response = $this->mpq_v2_model->get_mpq_id_and_is_submitted_by_session_id($session_id);
		$mpq_id = $mpq_id_response['id'];
		//$mpq_id =  78228;
		$final_array_details = $this->mpq_v2_model->get_all_budget_details_for_io($mpq_id);
		$order_details = array();
		$i = 0;
		foreach($final_array_details as $key => $order)
		{
		    $dfp_object_template_order_id = $order[0]['dfp_object_templates_order_id'];
		    $dfp_object_template_order_id = 2; 

		    $dfp_json_object = $this->mpq_v2_model->get_dfp_object_template($dfp_object_template_order_id);
		    $order_details[$i]['order_name'] = $dfp_json_object[0]['name'].' - '.$order[0]['start_date'];
		    $order_details[$i]['geo_location'] = $order[0]['geo_location'];
		    $get_order_details = $this->get_new_order_details($order);
		    $order_details[$i]['impressions'] = $get_order_details['o_o_impressions'];
		    $order_details[$i]['budget'] = $get_order_details['o_o_budget'];
		    $order_details[$i]['start_date'] = $get_order_details['start_date'];
		    $order_details[$i]['end_date'] = $get_order_details['end_date'];
		    $i++;
		}
		echo json_encode($order_details);
	}

	public function get_new_order_details($order_detail)
	{
		$order_detail_array['o_o_budget'] = '';
		$order_detail_array['o_o_impressions'] = '';
		$array_count = count($order_detail);
 
		$i = 0;
		foreach($order_detail as $line_item)
		{
			if($i == 0)
			{
				$order_detail_array['start_date'] = $line_item['start_date'];
			}
			$order_detail_array['o_o_budget'] = $order_detail_array['o_o_budget'] + $line_item['o_o_budget'];
			$order_detail_array['o_o_impressions'] = $order_detail_array['o_o_impressions'] + $line_item['o_o_impressions'];
			if($i == count($order_detail)-1)
			{
				$order_detail_array['end_date'] = $line_item['end_date'];
			}
			$i++;
		}
		return $order_detail_array;
	}

}

