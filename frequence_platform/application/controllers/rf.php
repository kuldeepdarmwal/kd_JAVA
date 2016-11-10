<?php

class RfSettingsData {
	public $hasRetargeting = 0;

	public function __construct($hasRetargeting)
	{
		$this->hasRetargeting = $hasRetargeting;
	}
}

class Rf  extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('rf_model');
		$this->load->model('lap_lite_model');
		$this->load->model('siterank_model');
		$this->load->library('session');
		$this->load->library('map');
	}

	function SavePrice()
	{
		$priceNotes = $this->input->post('priceNotes');
		$sessionId = $this->session->userdata('session_id');
		$this->rf_model->SavePriceNotes($sessionId, $priceNotes);
	}

	function getPopulation()
	{
		$sessionId = $this->session->userdata('session_id');
		$geoSums = $this->lap_lite_model->get_geo_sums($sessionId);

		$population = round($geoSums['population'],0);
		echo $population;
	}

	function updateImpressions($impression)
	{
		$sessionId = $this->session->userdata('session_id');
		$this->rf_model->update_impressions($sessionId, $impression);
	}

	function getDemo()
	{
		$sessionId = $this->session->userdata('session_id');
		$demos = $this->rf_model->get_demos_from_session($sessionId);
		echo trim($demos);
	}

	function GetRfPerformanceData(&$data, $rfParameters, $numIterations, $session_id, $hasRetargeting)
	{
		$isSuccess = false;
		$geoSums = $this->lap_lite_model->get_geo_sums($session_id);
		if ($geoSums['success'] != false)
		{
			$zips = $this->map->get_zips_from_session_id_and_feature_table($session_id, 'media_plan_sessions');
			$data['targeted_region_summary'] = $this->map->get_targeting_regions_string($zips['zcta']);

			$geo_parameters['geo_pop'] = round($geoSums['population'], 0);
			$geo_parameters['demo_pop'] = round($geoSums['demo_pop'], 0);
			$geo_parameters['internet_average'] = $geoSums['internet_average'];

			$data['rf_parameters'] = urldecode($rfParameters);
			$data['geo_pop'] = $geo_parameters['geo_pop'];
			$data['demo_pop'] = $geo_parameters['demo_pop'];
			$data['internet_average'] = $geoSums['internet_average'];
			$data['RON_demo_coverage'] = $this->lap_lite_model->get_RON_demo_coverage($rfParameters, $geo_parameters['internet_average'], $geo_parameters['demo_pop']);

			$rf_parameters = explode('|', $data['rf_parameters']);
			$impressions_array = array();
			$i = 1;
			while ($i <= 6)
			{
				$impressions_array[] = $rf_parameters[0] * $i;
				$i++;
			}

			$data['reach_frequency_result'] = $this->rf_model->calculate_reach_and_frequency(
				$impressions_array,
				$data['geo_pop'],
				$data['demo_pop'],
				$rf_parameters[3],
				$rf_parameters[4],
				$rf_parameters[2],
				$hasRetargeting
			);

			$data['monthlyPriceEstimate'] = $this->rf_model->GetMonthlyPriceEstimate(urldecode($rfParameters), $hasRetargeting);

			$isSuccess = true;
		}

		return $isSuccess;
	}

	public function GetMediaComparisonData(&$data, $mediaType)
	{
		$mediaCategories = $this->rf_model->GetMediaCategories();
		$data['mediaComparisonCategories'] = $mediaCategories;

		if($mediaType == "Default")
		{
			$defaultMediaType = $mediaCategories->row(0)->media_type;
			$data['mediaType'] = $defaultMediaType;
			$data['mediaComparisonData'] = $this->rf_model->GetMediaComparisonData($defaultMediaType);
		}
		else
		{
			$data['mediaType'] = $mediaType;
			$data['mediaComparisonData'] = $this->rf_model->GetMediaComparisonData($mediaType);
		}

		$data['mediaComparisonReach'] = 'Test Reach 2';
		$data['mediaComparisonFrequency'] = 'Test Frequency 2';
		$data['mediaComparisonOverall'] = 'Test Overall 2';
	}

	public function GetSettingsData()
	{
		$sessionId = $this->session->userdata('session_id');
		$response = $this->rf_model->GetSettingsData($sessionId);
		$row = $response->row();
		$returnData = array(
			'hasRetargeting' => $row->has_retargeting,
			'impressions' => $row->recommended_impressions
		);
		$jsonData = json_encode($returnData);

		echo $jsonData;
	}

	public function SaveSettingsData($sessionId, $hasRetargeting)
	{
		$this->rf_model->SaveSettingsData($sessionId, $hasRetargeting);
	}

	public function GetPageData()
	{
		$data = array();

		$encodedData = $this->input->post('encodedData');
		$data['encodedData'] = $encodedData;

		$mediaType = $this->input->post('mediaType');
		$hasRetargeting = $this->input->post('hasRetargeting');
		$discountPercent = $this->input->post('discountPercent');

		$splitData = explode("||",urldecode($encodedData));
		$demoParametersIndex = 0;
		$demoParameters = $splitData[$demoParametersIndex];
		$rfParametersIndex = 2;
		$rfParameters = $splitData[$rfParametersIndex];

		$sessionId = $this->session->userdata('session_id');
		$this->SaveSettingsData($sessionId, $hasRetargeting);
		$this->rf_model->save_rf_data_to_session_table($rfParameters, $sessionId);
		if(!$this->siterank_model->save_demographics_and_sites_to_session_table(urldecode($demoParameters), $sessionId))
		{
			echo 'Failed to save demographics and sites to session table.';
			return;
		}

		$numElementsInChart = 6; // WARNING: corresponds to var of same name in rf_header.php & rf_json_data.php
		if(!$this->GetRfPerformanceData($data, $rfParameters, $numElementsInChart, $sessionId, $hasRetargeting))
		{
			echo 'Failed to calculate reach & frequency';
			return;
		}

		$this->GetMediaComparisonData($data, $mediaType);

		$data['discountPercent'] = $discountPercent;
		$this->load->view('rf/rf_json_data', $data);
	}

	// returns javascript for eval()
	public function ReachFrequencyMainBody($encodedData)
	{
		$data = array();
		$encodedParameters = urldecode($encodedData);
		$data['encodedData'] = $encodedData;

		$mediaType = $this->input->post('mediaType');
		$hasRetargeting = $this->input->post('hasRetargeting');

		$splitData = explode("||",urldecode($encodedData));
		$demoParametersIndex = 0;
		$demoParameters = $splitData[$demoParametersIndex];
		$rfParametersIndex = 2;
		$rfParameters = $splitData[$rfParametersIndex];

		$sessionId = $this->session->userdata('session_id');
		$this->SaveSettingsData($sessionId, $hasRetargeting);
		$this->siterank_model->save_impressions_to_session_table($rfParameters, $sessionId);
		if(!$this->siterank_model->save_demographics_and_sites_to_session_table(urldecode($demoParameters), $sessionId))
		{
			echo 'Failed to save demographics and sites to session table.';
			return;
		}

		$numElementsInChart = 6; // WARNING: corresponds to var of same name in rf_header.php & rf_json_data.php
		if(!$this->GetRfPerformanceData($data, $rfParameters, $numElementsInChart, $sessionId, $hasRetargeting))
		{
			echo 'Failed to calculate reach & frequency';
			return;
		}

		$this->GetMediaComparisonData($data, $mediaType);

		$this->load->view('rf/rf_main_body', $data);
	}

	public function ReachFrequencySliderBody()
	{
		$data = array();
		$this->load->view('rf/rf_slider_body', $data);
	}

	public function get_rf_performance($rf_parameters)
	{
		$data = array();

		$sessionId = $this->session->userdata('session_id');
		$response = $this->rf_model->GetSettingsData($sessionId);
		$row = $response->row();
		$hasRetargeting = $row->has_retargeting;

		if($this->GetRfPerformanceData($data, $rf_parameters, 6, $sessionId, $hasRetargeting))
		{
			$this->load->view('rf/rf_display', $data);
		}
		else
		{
			echo "regions not initialized";
		}
	}
}
?>
