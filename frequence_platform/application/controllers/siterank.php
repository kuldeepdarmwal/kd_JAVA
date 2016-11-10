<?php
class Siterank extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('siterank_model');
		$this->load->library('session');
		$this->load->model('lap_lite_model');
		$this->load->model('rf_model');
	}
  
  	public function select_your_audience()
	{
			$this->load->view('siterank/demopicker', "");
	}

	public function save_demographics_and_sites_to_session_table($demos_selected)
	{
		$sessionId = $this->session->userdata('session_id');
		return $this->siterank_model->save_demographics_and_sites_to_session_table($demos_selected, $sessionId);
	}

	public function get_siterank_table_from_session_table()
	{
		$query_result = false;
		$session_id = $this->session->userdata('session_id');
		$geo_sums = $this->lap_lite_model->get_geo_sums($session_id);
		if($geo_sums['success'] == true)
		{
			$demos_selected = $this->siterank_model->get_demographic_settings_from_session($session_id);
			$query_result = $this->siterank_model->get_siterank_results($demos_selected, $geo_sums);
		}
		return $query_result;
	}
	
	public function site_table($demos_selected)
	{
		if($this->save_demographics_and_sites_to_session_table(urldecode($demos_selected)))
		{
			$query_result = $this->get_siterank_table_from_session_table();
			if($query_result != false)
			{
				$this->load->library('table');
				$this->table->set_heading('', 'GC','TE', 'DC','Score');
				$data['siterank_results'] = $this->table->generate($query_result);
				$this->load->view('siterank/sitetable', $data);
			}
			else
			{
				echo 'Need to load geo before showing this table';
			}
		}
		else
		{
			echo 'Unable to save demographics and sites to session table.';
		}
	}
}
