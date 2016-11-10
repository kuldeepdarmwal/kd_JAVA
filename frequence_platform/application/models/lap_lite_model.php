<?php

class Lap_lite_model extends CI_Model
{

	public function __construct()
	{
		$this->load->database();
	}

	public function get_RON_demo_coverage($rf_parameters,$internet_average, $demo_pop)
	{
		$rf_inputs = explode("|",urldecode($rf_parameters));
		$nIMPRESSIONS = 0;
		$nGEO_COV = 1;
		$nGAMMA = 2;
		$nIP_ACC = 3;
		$nDEMO_COV_OVERRIDE = 4;

		if ($rf_inputs[$nDEMO_COV_OVERRIDE]=="")
		{
			$implied_RON_demo_coverage = min(1,$rf_inputs[$nGEO_COV]*$internet_average/$demo_pop);///caged at 100% potential for geo data to not line up with internet average
		}
		else
		{
			$implied_RON_demo_coverage = $rf_inputs[$nDEMO_COV_OVERRIDE];
		}
		return $implied_RON_demo_coverage;
	}

	public function get_geo_sums($session_id)
	{
		$success = true;
		$regions = $this->get_regions_from_session($session_id);
		$demos = $this->get_demos_from_session($session_id);

		if($regions['success'] == 1)
		{
			$pop = 0;
			$demo_pop = 0;
			$internet_average = 0;
			$this->map->convert_old_flexigrid_format($regions['region_result']);
			$this->map->calculate_population_based_on_selected_demographics($pop, $demo_pop, $internet_average, $regions['region_result'], $demos);
			
			//set population in media_plan_sessions_based on region details retrieved above.
			$update_population = "UPDATE media_plan_sessions SET population = ?, demo_population = ?, internet_average = ? WHERE session_id = ?";
			$this->db->query($update_population, array($pop, $demo_pop, $internet_average, $session_id));

			$return_array = array('success' => $regions['success'], 'population' => $pop, 'demo_pop' => $demo_pop, 'internet_average' => $internet_average);
			return $return_array;
		}
		else
		{
			$return_array = array('success' => $regions['success']);
			return $return_array;  
		}
	}

	public function get_regions_from_session($session_id)
	{
		$the_query = 
		"	SELECT `region_data` 
			FROM `media_plan_sessions`
			WHERE `session_id` = ?
		";
		$query = $this->db->query($the_query, $session_id);
		$query_response = $query->result_array();

		if (!isset($query_response[0]['region_data']))
		{
			return array('success' => false);
		}
		
		$return_array = array('success' => true, 'region_result' => json_decode($query_response[0]['region_data'], true));
		return $return_array;
	}

	public function get_demos_from_session($session_id)
	{
		$the_query = 
		"	SELECT 
				`demographic_data` 
			FROM `media_plan_sessions`
			WHERE `session_id` = ?
		";
		$query_result = $this->db->query($the_query, $session_id);
		$query_response = $query_result->row()->demographic_data;

		$remove_unused = explode("||", $query_response);
		return explode("_", $remove_unused[0]);
	}

	public function create_new_lap($session_id, $owner_id)
	{
		$bindings = array($session_id);
		$query = "DELETE FROM `media_plan_sessions` WHERE session_id = ?";
		$this->db->query($query, $bindings);

		$this->initialize_session_table($session_id, $owner_id);

		// *** corresponds to assignment in lap_lite.php, getInit() ***
		$geo_session_data = array(
			'type' => 'ZIP',
			'radius' => '5',
			'center' => '320 Mountain View Avenue, California'
			);
		$this->session->set_userdata($geo_session_data);
	}

	public function old_data_new_lap($session_id)
	{
		$sql = 'SELECT * FROM media_plan_sessions WHERE session_id = ?';
		$query = $this->db->query($sql, $session_id);
		if($query->num_rows() == 1)
		{
			$h = $query->row_array();
			$bindings = array(
				$h['session_id'], 
				$h['region_data'], 
				$h['population'], 
				$h['demo_population'], 
				$h['demographic_data'], 
				$h['site_array'], 
				$h['recommended_impressions'], 
				$h['price_notes'], 
				$h['selected_channels'], 
				$h['has_retargeting'], 
				$h['rf_geo_coverage'], 
				$h['rf_gamma'], 
				$h['rf_ip_accuracy'], 
				$h['rf_demo_coverage'], 
				$h['internet_average'], 
				$h['owner_id'], 
				$h['date_created'],
				$h['selected_iab_categories']
			);

			$sql = 'DELETE FROM media_plan_sessions WHERE session_id = ?';
			$query = $this->db->query($sql, $session_id);

			$sql = '
				INSERT INTO media_plan_sessions (
				session_id, 
				region_data, 
				population, 
				demo_population, 
				demographic_data, 
				site_array, 
				recommended_impressions, 
				price_notes, 
				selected_channels, 
				has_retargeting, 
				rf_geo_coverage, 
				rf_gamma, 
				rf_ip_accuracy, 
				rf_demo_coverage, 
				internet_average, 
				owner_id, 
				date_created,
				selected_iab_categories
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
			$query = $this->db->query($sql, $bindings);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function initialize_session_table($session_id, $owner_id)
	{
		//if there is no entry for this session id insert
		$date_created = date("Y-m-d");
		// initial demographics set to all on, with 75 reach frequency ratio, 'All' somthing.
		$initialize_demographics = '1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_1_75_All_unusedString';
		$bindings = array($session_id, $initialize_demographics, $owner_id, $date_created);
		$query = 
		"	INSERT IGNORE INTO `media_plan_sessions`(
				`session_id`, 
				`demographic_data`,
				`owner_id`,
				`date_created`
			) 
			VALUES (?, ?, ?, ?)
		";
		$this->db->query($query, $bindings);
	}

	public function load_session_region_table($session_id, $json_regions)
	{
		$bindings = array($json_regions, $session_id);
		$the_query = 
		"	UPDATE `media_plan_sessions` 
			SET `region_data` = ? 
			WHERE `session_id` = ?
		";
		return $this->db->query($the_query, $bindings);
	}

	public function get_notes_data($session_id)
	{
		$bindings = array($session_id);
		$query = 
		"	SELECT plan_name, advertiser, notes, is_saved_lap
			FROM media_plan_sessions
			WHERE session_id = ?
		";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	// gets data based upon $search_criteria
	// also sets `region_data` with said data
	function flexigrid($search_criteria, $session_id)
	{
		$page = null; // This variable is used for location id in MPQ/RFP, null for planner-made laps

		$search_criteria_array = explode("_", urldecode($search_criteria));

		$zips = $this->map->get_zips_from_radius_and_center(array('latitude' => $search_criteria_array[2], 'longitude' => $search_criteria_array[3]), $search_criteria_array[1]);
		$zips = call_user_func_array('array_merge_recursive', $zips);
		$data = array();

		// $data is returned to the controller
		$data['page'] = $page;
		$data['total'] = count($zips['zips']);
		$data['ids'] = ['zcta' => $zips['zips']];

		$is_success = $this->load_session_region_table($session_id, json_encode($data));
		return array('is_success' => $is_success, 'zips' => $zips['zips']);
	}

	function flexigrid_manual($zips, $session_id)
	{
		$page = null; // This variable is used for location id in MPQ/RFP, null for planner-made laps

		$data = array();
		$zips = array_unique(array_filter($zips));
		$total = 0;

		// $data is returned to the controller
		$data['page'] = $page;
		$data['total'] = 0;
		$data['ids'] = ['zcta' => array()];

		$result = $this->map->get_demographics_array_from_region_array($zips);
		// $data is returned to the controller
		foreach ($result as $row)
		{
			$test_response = array_filter($row); 
			if (!empty($test_response))
			{
				$data['ids']['zcta'][] = $row['region_name'];
			}
		}

		$data['total'] = count($data['ids']['zcta']);
		$is_success = $this->load_session_region_table($session_id, json_encode($data));
		return array('is_success' => $is_success, 'zips' => $data['ids']['zcta']);
	}

	// Adds a single zipcode to the region_data field in the media_plan_sessions table
	function add_zipcode($zipcode, $session_id)
	{
		$query = 
		"	SELECT `region_data` 
			FROM `media_plan_sessions`
			WHERE `session_id` = ?;
		";
		$response = $this->db->query($query, $session_id);
		if($response->num_rows() > 0)
		{
			$original_json_data = $response->row()->region_data;
			$region_data = json_decode($original_json_data, 1);
			$this->map->convert_old_flexigrid_format($region_data);

			$rows = $this->map->get_demographics_array_from_region_array(array('zcta' => $zipcode));
			$test_response = array_filter($rows[0]);
			if (!empty($test_response))
			{
				$region_data['ids']['zcta'][] = $rows[0]['region_name'];
				$region_data['ids']['zcta'] = array_unique($region_data['ids']['zcta']);
			}

			$region_data['total'] = count($region_data['ids']['zcta']);
			$result_json_data = json_encode($region_data);
			$this->load_session_region_table($session_id, $result_json_data);
		}
	}

	// Removes a single zipcode from the region_data field in the media_plan_sessions table
	function remove_zipcode($zipcode, $session_id)
	{
		$query = 
		"	SELECT `region_data` 
			FROM `media_plan_sessions`
			WHERE `session_id` = ?;
		";
		$response = $this->db->query($query, $session_id);
		if($response->num_rows() > 0)
		{
			$original_json_data = $response->row()->region_data;
			$region_data = json_decode($original_json_data, 1);
			$this->map->convert_old_flexigrid_format($region_data);

			$ids = $region_data['ids']['zcta'];
			$diff = array_values(array_diff($ids, array($zipcode)));
			$is_modified = ($ids !== $diff);

			if($is_modified)
			{
				$region_data['ids']['zcta'] = $diff;
				$region_data['total'] = count($diff);
				$result_json_data = json_encode($region_data);
				$this->load_session_region_table($session_id, $result_json_data);
			}
		}
	}
}

?>
