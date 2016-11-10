<?php

class Lap_lite extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('lap_lite_model');
		$this->load->helper('url');
		$this->load->library('tank_auth');
		$this->load->library('session');
		$this->load->library('map');
		$this->load->model('proposal_gen_model');
		$this->load->model('mpq_v2_model');
	}

	public function switch_media_plan()
	{
		$success = $this->lap_lite_model->old_data_new_lap($this->session->userdata('session_id'));
		if($success)
		{
			echo "win";
		}
		else
		{
			echo "lose";
		}
	}

	public function load_advertiser_data()
	{
		$advertiser = $this->input->post('advertiser');
		$plan_name = $this->input->post('planName');
		$notes = $this->input->post('notes');

		$this->proposal_gen_model->loadAdvertiserData($this->session->userdata('session_id'), $advertiser, $plan_name, $notes);
	}

	public function getInit($blank)
	{
		$type = $this->session->userdata('type');
		$radius = $this->session->userdata('radius');
		$center = $this->session->userdata('center');
		if ($type !== false)
		{
			$data['geo_radius'] = str_replace('%20', ' ', $radius);
			$data['geo_center'] = str_replace('%20', ' ', $center);
		}
		else
		{
			// *** corresponds to assignment in lap_lite_model.php, create_new_lap() ***
			$data['geo_radius'] = '5';
			$data['geo_center'] = '320 Mountain View Avenue, Mountain View, California';
		}
		$owner_id = $this->tank_auth->get_user_id();
		$this->lap_lite_model->initialize_session_table($this->session->userdata('session_id'), $owner_id);	
		$this->load->view('lap/lap_main_body', $data);
	}

	// add or remove a single zip code
	public function modify_zipcodes()
	{
		$session_id = $this->session->userdata('session_id');

		$action = $this->input->post('action');
		$zip = $this->input->post('zip');
		if($action && $zip)
		{
			if($action == 'add_zipcode')
			{
				$this->lap_lite_model->add_zipcode($zip, $session_id);
			}
			elseif($action == 'remove_zipcode')
			{
				$this->lap_lite_model->remove_zipcode($zip, $session_id);
			}
			echo json_encode(array('is_success' => true));
		}
	}

	public function get_initial_data()
	{
		$session_id = $this->session->userdata('session_id');
		$data = array();
		$data['zips_string'] = $this->map->get_zips_from_session_id_and_feature_table($session_id, 'media_plan_sessions', true);
		$data['notes_response'] = $this->lap_lite_model->get_notes_data($session_id);
		$this->load->view('lap/initial_flexigrid_data', $data);
	}

	public function saveParameters($parameters)
	{
		$results = explode("_", $parameters);
		$parameterArray = array(
			'type' => $results[0],
			'radius' => $results[1],
			'center' => $results[2]
		);
		$this->session->set_userdata($parameterArray);
	}

	public function flexigrid($search_criteria)
	{
		$regions = $this->lap_lite_model->flexigrid($search_criteria, $this->session->userdata('session_id'));//CHANGE THIS NAME
		echo json_encode($regions);
	}

	public function flexigrid_manual()
	{
		$zips = trim(urldecode($this->input->post('zips', true)));
		$zips = trim($zips, '|');
		$zips_array = explode('|', $zips);

		$region_result = $this->lap_lite_model->flexigrid_manual($zips_array, $this->session->userdata('session_id'));
		echo json_encode($region_result);
	}

	public function get_demographics($unused_parameters = false)
	{
		$session_id = $this->session->userdata('session_id');

		$zips = $this->map->get_zips_from_session_id_and_feature_table($session_id, 'media_plan_sessions');
		if (empty($zips['zcta']))
		{
			die('Cannot find specified region(s)');
		}
		
		$data['demographics'] = $this->map->get_demographics_from_region_array($zips);
		$data['national_averages_array'] = $this->map->get_national_averages_for_demos();

		$data['targeted_region_summary'] = $this->map->get_targeting_regions_string($zips['zcta']);
		$this->load->view('lap/demo_view', $data);
	}

	public function get_lap_lite_map()
	{
		ini_set('memory_limit', '2048M');
		$session_id = $this->session->userdata('session_id');
		$region_details = $this->map->get_zips_from_session_id_and_feature_table($session_id, 'media_plan_sessions', false);
		$data['search_type'] = 'zcta';

		// Check if there are more than 1K zip codes returned
		$data['big_map'] = count($region_details['zcta']) > 1000;

		$map_blobs = $data['big_map'] ?
			$this->map->get_geojson_points_from_region_array($region_details) : // OR
			$this->map->get_geojson_blobs_from_region_array($region_details);

		$map_geojson = $this->map->get_geojson_for_mpq($map_blobs);

		$data['map_objects'] = (empty($map_blobs)) ? 'false' : $map_geojson;
		$data['shared_js'] = $this->load->view('maps/map_shared_functions_js', null, true);

		if ($data['map_objects'] == 'false')
		{
			die('<br><br>Cannot find specified region(s)');
		}
		$this->load->view('lap/map_view', $data);
	}

}

?>
