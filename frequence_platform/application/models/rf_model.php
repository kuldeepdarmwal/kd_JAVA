<?php

class Rf_model extends CI_Model
{

	public function __construct()
	{
		$this->load->database();
	}

	public function GetMediaComparisonData($mediaType)
	{
		$bindings = array($mediaType);
		$query =
		"	SELECT * 
			FROM `mp_media_compare`
			WHERE `media_type` = ?
		";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function GetMediaCategories()
	{
		$query = "SELECT media_type FROM `mp_media_compare` ORDER BY media_type ASC";
		$response = $this->db->query($query);
		return $response;
	}

	public function GetSettingsData($sessionId)
	{
		$bindings = array($sessionId);
		$query =
		"	SELECT 
				`has_retargeting`, 
				`recommended_impressions` 
			FROM `media_plan_sessions`
			WHERE `session_id` = ?
		";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function SaveSettingsData($sessionId, $hasRetargeting)
	{
		$bindings = array($hasRetargeting, $sessionId);
		$query = "UPDATE `media_plan_sessions` 
			SET `has_retargeting` = ?
			WHERE `session_id` = ?";
		$response = $this->db->query($query, $bindings);
	}

	public function SavePriceNotes($sessionId, $priceNotesString)
	{
		$bindings = array($priceNotesString, $sessionId);
		$query = "UPDATE `media_plan_sessions` 
			SET `price_notes` = ?
			WHERE `session_id` = ?";
		$this->db->query($query, $bindings);
	}

	public function get_demos_from_session($session_id)
	{
		$query =
		"	SELECT 
				`demographic_data` 
			FROM 
				`media_plan_sessions`
			WHERE 
				`session_id` = ?
		";
		$response = $this->db->query($query, $session_id);
		$demos = $response->row(0)->demographic_data;

		if ($demos == null)
		{
			$query2 =
			"	SELECT 
					`default_demo` 
				FROM 
					`place_to_demo`
				WHERE 
					`local_place_type` = 'finance'
			";
			$response2 = $this->db->query($query2);
			$demos = $response2->row(0)->default_demo;
		}
		return $demos;
	}

	public function GetMonthlyPriceEstimate($rf_parameters, $hasRetargeting)
	{
		$rf_inputs = explode("|",urldecode($rf_parameters));
		$nIMPRESSIONS = 0;
		$impressions = $rf_inputs[$nIMPRESSIONS];

		$estimate = "Special";

		$query =
		"	SELECT impressions, display_cpm, retargeting_cpm 
			FROM smb_cpm
			WHERE impressions >= ?
			ORDER BY impressions ASC
		";
		$response = $this->db->query($query, $impressions);
		if($response && $response->num_rows() > 0)
		{
			$cpm = $response->row(0)->display_cpm;
			if($hasRetargeting)
			{
				$cpm += $response->row(0)->retargeting_cpm;
			}
			$estimate = ($impressions / 1000) * $cpm;
		}

		return $estimate;
	}

	public function save_rf_data_to_session_table($rf_data, $session_id)
	{
		$nIMPRESSIONS = 0;
		$nGEO_COV = 1;
		$nGAMMA = 2;
		$nIP_ACC = 3;
		$nDEMO_COV_OVERRIDE = 4;
		$nRETARGETING = 5;

		$rf = explode("|",urldecode($rf_data));
		$darray = array(floatval($rf[$nIMPRESSIONS]),floatval($rf[$nGEO_COV]),floatval($rf[$nGAMMA]),floatval($rf[$nIP_ACC]),floatval($rf[$nDEMO_COV_OVERRIDE]),$session_id);

		$the_query = "UPDATE `media_plan_sessions` SET recommended_impressions = ?, rf_geo_coverage = ?, rf_gamma = ?, rf_ip_accuracy = ?, rf_demo_coverage = ? WHERE session_id = ?";
		$this->db->query($the_query, $darray);
	}

	public function calculate_reach_and_frequency($impressions_array, $geo_population, $target_population, $ip_accuracy, $max_target_reach, $gamma, $retargeting)
	{
		$gamma = floatval($gamma);

		$eta_default = $target_population / $geo_population;
		$eta_adjusted = $gamma < 0 ? $eta_default * ($gamma + 1) : $gamma - $eta_default * ($gamma - 1);
		// Avoid "Divide By Zero" error if gamma is manually set
		$eta_adjusted = $eta_adjusted == 0 ? -1 : $eta_adjusted;

		$geo_coverage_percent = $max_target_reach * $target_population / ($eta_adjusted * $geo_population);
		$geo_coverage = $geo_coverage_percent * $geo_population;

		$target_coverage = $max_target_reach * $target_population;

		$rf_data = array();

		foreach ($impressions_array as $i => $impressions)
		{
			$rf_data[$i]['impressions'] = $impressions;
			$landed_impressions = 0;

			$retargeting_modifier = $retargeting ? $impressions * 0.12 : 0;

			$target_factor = 0;
			if ($geo_coverage > 0)
				$landed_impressions = ($impressions - $retargeting_modifier) * $ip_accuracy * ( $target_coverage / $geo_coverage ) + $retargeting_modifier;
			if ($target_coverage > 0)
				$target_factor = ($target_coverage - 1) / $target_coverage;

			$rf_data[$i]['reach'] = (1 - pow($target_factor, $landed_impressions)) / (1 - $target_factor);
			$rf_data[$i]['reach_percent'] = 0;
			if ($target_population > 0)
			$rf_data[$i]['reach_percent'] = $rf_data[$i]['reach'] / $target_population;

			$rf_data[$i]['frequency'] = $landed_impressions > 0 ? $landed_impressions / $rf_data[$i]['reach'] : 0;

			$rf_data[$i]['gamma'] = $gamma;
		}

		return $rf_data;
	}

	public function get_geo_coverage_percent($zip_codes)
	{
		$geo_coverage = 0;
		if(!empty($zip_codes))
		{
			$zip_sql = "";
			foreach($zip_codes as $zip)
			{
				if($zip_sql !== "")
				{
					$zip_sql .= ", ";
				}
				$zip_sql .= "?";
			}
			$query = "
				SELECT 
					density_by_population_type AS density_type,
					SUM(population_total) AS population_sum
				FROM 
					geo_cumulative_demographics
				WHERE 
					local_id IN(".$zip_sql.") AND
					density_by_population_type IN ('RURAL', 'SUBURBAN', 'URBAN')
				GROUP BY 
					density_by_population_type";
			$response = $this->db->query($query, $zip_codes);
			if($response->num_rows() > 0)
			{
				$density_array = array(
					'total_population' => 0,
					'density_types' => array(
						'URBAN' => array('population_sum' => 0, 'population_percent' => 0, 'weight' => .85), 
						'SUBURBAN' => array('population_sum' => 0, 'population_percent' => 0, 'weight' => .85), 
						'RURAL' => array('population_sum' => 0, 'population_percent' => 0, 'weight' => .78)
						)
					);
				foreach($response->result_array() as $density)
				{
					$density_array['density_types'][$density['density_type']]['population_sum'] = $density['population_sum'];
					$density_array['total_population'] += $density['population_sum'];
				}
				foreach($density_array['density_types'] as &$density_type)
				{
					$density_type['population_percent'] = $density_type['population_sum']/$density_array['total_population'];
					$geo_coverage += $density_type['population_percent'] * $density_type['weight'];
				}
			}
		}
		return $geo_coverage;
	}

	public function get_demo_coverage_percent($demographics, $demographic_shares, $geo_coverage)
	{
		$demo_coverage = 0;
		$gender = array(
			array('name' => 'male', 'selected' => $demographics[0], 'share' => $demographic_shares[0], 'online' => .84),
			array('name' => 'female', 'selected' => $demographics[1], 'share' => $demographic_shares[1], 'online' => .84)
			);

		$age = array(
			array('name' => 'under_18', 'selected' => $demographics[2], 'share' => $demographic_shares[2], 'online' => .8),
			array('name' => '18-24', 'selected' => $demographics[3], 'share' => $demographic_shares[3], 'online' => .97),
			array('name' => '25-35', 'selected' => $demographics[4], 'share' => $demographic_shares[4], 'online' => .95),
			array('name' => '35-44', 'selected' => $demographics[5], 'share' => $demographic_shares[5], 'online' => .93),
			array('name' => '45-54', 'selected' => $demographics[6], 'share' => $demographic_shares[6], 'online' => .87),
			array('name' => '55-64', 'selected' => $demographics[7], 'share' => $demographic_shares[7], 'online' => .81),
			array('name' => '65_up', 'selected' => $demographics[8], 'share' => $demographic_shares[8], 'online' => .568018)
			);

		$income = array(
			array('name' => 'under_50', 'selected' => $demographics[9], 'share' => $demographic_shares[9], 'online' => .75),
			array('name' => '50-100', 'selected' => $demographics[10], 'share' => $demographic_shares[10], 'online' => .85),
			array('name' => '100-150', 'selected' => $demographics[11], 'share' => $demographic_shares[11], 'online' => .94),
			array('name' => '150_up', 'selected' => $demographics[12], 'share' => $demographic_shares[12], 'online' => .9755)
			);

		$college = array(
			array('name' => 'no_college', 'selected' => $demographics[13], 'share' => $demographic_shares[13], 'online' => .8),
			array('name' => 'college', 'selected' => $demographics[14], 'share' => $demographic_shares[14], 'online' => .88),
			array('name' => 'grad_school', 'selected' => $demographics[15], 'share' => $demographic_shares[15], 'online' => .97176)
			);

		$parenting = array(
			array('name' => 'has_kids', 'selected' => $demographics[16], 'share' => $demographic_shares[16], 'online' => .84),
			array('name' => 'no_kids', 'selected' => $demographics[17], 'share' => $demographic_shares[17], 'online' => .84)
			);

		$gender_totals = $this->rf_totals_by_demo_group($gender);
		$age_totals = $this->rf_totals_by_demo_group($age);
		$income_totals = $this->rf_totals_by_demo_group($income);
		$college_totals = $this->rf_totals_by_demo_group($college);
		$parenting_totals = $this->rf_totals_by_demo_group($parenting);

		$pop_total = $gender_totals['percent'] * $age_totals['percent'] * $income_totals['percent'] * $college_totals['percent'] * $parenting_totals['percent'];
		
		$coverage_total = $gender_totals['coverage'] * $age_totals['coverage'] * $income_totals['coverage'] * $college_totals['coverage'] * $parenting_totals['coverage'] * $geo_coverage;		

		$max_coverage = max(array($gender_totals['coverage'], $age_totals['coverage'], $income_totals['coverage'], $college_totals['coverage'], $parenting_totals['coverage'], $geo_coverage));

		$default_coverage = .84;
		$demo_coverage = min(array($coverage_total * pow($default_coverage, -5), $max_coverage));
				
		return $demo_coverage;
	}

	private function rf_totals_by_demo_group($demo_group)
	{
		$return_array = array('coverage' => 0, 'percent' => 0);
		foreach($demo_group as $demo)
		{
			$return_array['percent'] += $demo['share'] * $demo['selected'];
			$return_array['coverage'] += $demo['share'] * $demo['online'] * $demo['selected'];
		}
		$return_array['coverage'] = $return_array['coverage']/$return_array['percent'];
		return $return_array;
	}
}
?>
