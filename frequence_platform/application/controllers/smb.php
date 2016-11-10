<?php

class Smb extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('smb_model');
		$this->load->model('category_model');
		$this->load->model('lap_lite_model');
		$this->load->model('rf_model');
		$this->load->model('siterank_model');
		$this->load->helper('url');
		$this->load->helper('form');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('map');
		
	}

	public function GetMediaPlanChannelSites($categories_string)
	{
		die("GetMediaPlanChannelSites() is expected not to be used.");
		$the_category_database = "smb_demographic_records_mp";
		$categories = explode('|',urldecode($categories_string));
		$sitelist_sql_result = $this->category_model->get_sites_query($categories,$the_category_database);
		
		$this->load->library('table');
		$this->table->set_heading('Domain', 'Reach','Channel');
		
		$data['sites'] = $this->table->generate($sitelist_sql_result);
		$this->load->view('categories/sites_view',$data);
	}

	public function get_media_targeting_body()
	{
		$sitePackCategories = $this->smb_model->getMediaTargetingSitePackCategories();
		$data['sitePackCategories'] = $sitePackCategories;
		
		$realSitePackName = $sitePackCategories->row(0)->sitePackName;
		$demoResponse = $this->smb_model->getDemoSitePack($realSitePackName);
		$data['demoResponse'] = $demoResponse;
		$channelResponse = $this->smb_model->getChannelSitePack($realSitePackName);
		$data['channelResponse'] = $channelResponse;
		$sessionId = $this->session->userdata('session_id');
		$population = $this->smb_model->get_population($sessionId);
		$data['population'] = $population;
		$channels = $this->smb_model->get_channels_from_session_table($sessionId);
		$data['channels'] = $channels;
		$data['selected_iab_categories'] = $this->smb_model->get_iab_categories_from_session_table($sessionId);
		$realizedValueResponse = $this->smb_model->getRealizedValue($realSitePackName);
		$data['realizedValueResponse'] = $realizedValueResponse;
		$internetStandardDeviation = $this->smb_model->getInternetStandardDeviation();
		$data['internetStandardDeviation'] = $internetStandardDeviation;
		$getInternetMean = $this->smb_model->getInternetMean();
		$data['getInternetMean'] = $getInternetMean;
		
		$data['sitePackName'] = $this->session->userdata('sitePackName');

		$data['unique_site_categories'] = $this->category_model->get_all_categories("smb_all_sites");
		$data['unique_iab_categories'] = $this->category_model->get_all_iab_channels();

		$username = $this->tank_auth->get_username();
		$data['role'] = $this->tank_auth->get_role($username);

		$this->load->view('smb/media_targeting_body', $data);
	}

	public function get_media_targeting_demographics()
	{
		$data = array();
		$sitePackName = $this->uri->segment(3);
		$realSitePackName = "";
		$sitePackCategories = $this->smb_model->getMediaTargetingSitePackCategories();

		if($sitePackName!==false)
		{
			$realSitePackName = urldecode($sitePackName); 
		}
		else
		{
			$realSitePackName = $sitePackCategories->row(0)->sitePackName;
		}
		
		$data['realSitePackName'] = $realSitePackName;

		$demoResponse = $this->smb_model->getDemoSitePack($realSitePackName);
		$data['demoResponse'] = $demoResponse;
		$channelResponse = $this->smb_model->getChannelSitePack($realSitePackName);
		$data['channelResponse'] = $channelResponse;
		$sessionId = $this->session->userdata('session_id');
		$population = $this->smb_model->get_population($sessionId);
		$data['population'] = $population;

		$realizedValueResponse = $this->smb_model->getRealizedValue($realSitePackName);
		$data['realizedValueResponse'] = $realizedValueResponse;
		$internetStandardDeviation = $this->smb_model->getInternetStandardDeviation();
		$data['internetStandardDeviation'] = $internetStandardDeviation;
		$getInternetMean = $this->smb_model->getInternetMean();
		$data['getInternetMean'] = $getInternetMean;

		$sitePackCoverageResponse = $this->smb_model->getSitePackCoverage($realSitePackName);
		$data['sitePackCoverageResponse'] = $sitePackCoverageResponse;
		/*
		$demoResponse = $this->smb_model->getDemoSitePack($realSitePackName);
		$data['demoResponse'] = $demoResponse;
		$channelResponse = $this->smb_model->getChannelSitePack($realSitePackName);
		$data['channelResponse'] = $channelResponse;
		$sessionId = $this->session->userdata('session_id');
		$population = $this->smb_model->getPopulation($sessionId);
		$data['population'] = $population;
		*/

		//$this->load->view('smb/javascript_test', $data);
		$this->load->view('smb/media_targeting_demographics', $data);
	}
	
	public function get_media_targeting_demographics_by_site($site)
	{
		$data = array();
		$sitePackName = $this->uri->segment(3);
	
		$realizedValueResponse = $this->smb_model->getRealizedValue($sitePackName);
		$data['realizedValueResponse'] = $realizedValueResponse;
		$internetStandardDeviation = $this->smb_model->getInternetStandardDeviation();
		$data['internetStandardDeviation'] = $internetStandardDeviation;
		$getInternetMean = $this->smb_model->getInternetMean();
		$data['getInternetMean'] = $getInternetMean;
	
		//$sitePackCoverageResponse = $this->smb_model->getSitePackCoverage($sitePackName);
		//$data['sitePackCoverageResponse'] = $sitePackCoverageResponse;
		
		$this->load->view('smb/media_targeting_demographics', $data);
	}

	public function save_iab_categories()
	{
		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$sessionId = $this->session->userdata('session_id');
			$iab_categories = $this->input->post('iab_categories');
			if($sessionId)
			{
				if(empty($iab_categories))
				{
					$iab_categories = array();
				}
				$this->siterank_model->save_iab_categories_to_session($sessionId, $iab_categories);
			}
		}
		else
		{
			show_404();
		}
	}

	public function get_media_targeting_site_pack()
	{
		$data = array();

		$encodedData = $this->input->post('encodedData');
		$channels = $this->input->post('channels');
		//$encodedParameters = urldecode($encodedData);
		$data['encodedData'] = $encodedData;

		$split_data = explode("||", urldecode($encodedData));            
		$demo_parameters_index = 0;
		$demo_parameters = $split_data[$demo_parameters_index];
		$rf_parameters_index = 2;
		$rf_parameters = $split_data[$rf_parameters_index];

		$session_id = $this->session->userdata('session_id');
		$this->siterank_model->save_channels_to_session_table($session_id, $channels);
		$this->siterank_model->save_impressions_to_session_table($rf_parameters, $session_id);
		if(!$this->siterank_model->save_demographics_and_sites_to_session_table(urldecode($demo_parameters), $session_id))
		{
			echo 'Failed to save demographics and sites to session table.';
			return;
		}

		$geo_sums = $this->lap_lite_model->get_geo_sums($session_id);
		$demos_selected = $this->siterank_model->get_demographic_settings_from_session($session_id);
		$query_result = $this->smb_model->get_sites($demos_selected, $geo_sums);
		$data['demoResponse'] = $query_result;

		$the_category_database = "smb_all_sites";
		$categories = explode('|', urldecode($channels));
		$sitelist_sql_result = $this->category_model->get_sites_query($categories, $the_category_database);
		$data['channelResponse'] = $sitelist_sql_result;
		$data['population'] = $geo_sums['population'];

		$this->load->view('smb/site_pack_table', $data);
	}
}
?>
