<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class siterank_controller extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper(array(
				'form',
				'url',
				'ttd_excel_upload_helper'
		));
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->library('excel');
		$this->load->library('map');
		$this->load->model('siterankpoc_model');
	}
	public function index($c_id=0)
	{
		if (!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','siterank_controller');
			redirect('login');
			return;
		}
		$username=$this->tank_auth->get_username();
		$role=$this->tank_auth->get_role($username);
		$page_type=$this->input->get('page_type');
		if ($role=='admin'&&$page_type=='ttd_list')
		{
			$data['username']=$username;
			$data['firstname']=$this->tank_auth->get_firstname($data['username']);
			$data['lastname']=$this->tank_auth->get_lastname($data['username']);
			$data['user_id']=$this->tank_auth->get_user_id();
			$data['title']="Site Gen";
			$this->load->view('ad_linker/header', $data);
			$mpq_id=$this->input->get('mpq_id');
			$data['mpq_id']=$mpq_id;
			$this->load->view('site_tag_v2/site_rank_view_v3', $data);
		}
		else if ($role=='admin')
		{
			$data['username']=$username;
			$data['firstname']=$this->tank_auth->get_firstname($data['username']);
			$data['lastname']=$this->tank_auth->get_lastname($data['username']);
			$data['user_id']=$this->tank_auth->get_user_id();
			$data['title']="Site Gen";
			$this->load->view('ad_linker/header', $data);
			$mpq_id=$this->input->get('mpq_id');
			$data['mpq_id']=$mpq_id;
			$this->load->view('site_tag_v2/site_rank_view_v2', $data);
		}
		else
		{
			redirect('director');
		}
	} // index()
	  
	// method 1: // pass mpq id and get back list of sites along with their scores and list of contetuals
	  // group the sites by sorted header
	  // return a json with similar format as site list with site name and 24 columns
	  // add the score to header name itself.
	public function get_site_rankings()
	{
		$mpq_id=$this->input->post('mpq_id');
		$industry_multi=$this->input->post('industry_multi');
		$industry_n_multi=$this->input->post('industry_n_multi');
		$iab_multi=$this->input->post('iab_multi');
		$iab_weight=$this->input->post('iab_weight');
		$iab_n_weight=$this->input->post('iab_n_weight');
		$reach_weight=$this->input->post('reach_weight');
		$stereo_weight=$this->input->post('stereo_weight');
		
		$result['context_array']=$this->siterankpoc_model->get_sites_for_mpq($mpq_id, $iab_weight, $iab_n_weight,
		 $reach_weight, $stereo_weight, $industry_multi, $industry_n_multi, $iab_multi, true, true);
		$header=$this->siterankpoc_model->get_mpq_data_header($mpq_id, true);
		$result['industry_id']=$header['industry_id'];
		$result['context_id']=$header['context_id'];
		$result['demo_id']=$header['demo_id'];
		$result['zip_id']=$header['zip_id'];
		$result['product_type_flag']=$header['product_type_flag'];
		echo json_encode($result);
	}

	public function get_site_rankings_ttd()
	{
		$type_of_sites=$this->input->post('type_of_sites');
		$mpq_id=$this->input->post('mpq_id');
		$industry_multi=$this->input->post('industry_multi');
		$industry_n_multi=$this->input->post('industry_n_multi');
		$iab_multi=$this->input->post('iab_multi');
		$iab_weight=$this->input->post('iab_weight');
		$iab_n_weight=$this->input->post('iab_n_weight');
		$reach_weight=$this->input->post('reach_weight');
		$stereo_weight=$this->input->post('stereo_weight');
		
		$industry_select=$this->input->post('industry_select');
		$iab_contextual_multiselect=$this->input->post('iab_contextual_multiselect');
		$zips=$this->input->post('zips');
		
		$gender_male_demographic=$this->input->post('gender_male_demographic');
		$gender_female_demographic=$this->input->post('gender_female_demographic');
		
		$age_under_18_demographic=$this->input->post('age_under_18_demographic');
		$age_18_to_24_demographic=$this->input->post('age_18_to_24_demographic');
		$age_25_to_34_demographic=$this->input->post('age_25_to_34_demographic');
		$age_35_to_44_demographic=$this->input->post('age_35_to_44_demographic');
		$age_45_to_54_demographic=$this->input->post('age_45_to_54_demographic');
		$age_55_to_64_demographic=$this->input->post('age_55_to_64_demographic');
		$age_over_65_demographic=$this->input->post('age_over_65_demographic');
		
		$income_under_50k_demographic=$this->input->post('income_under_50k_demographic');
		$income_50k_to_100k_demographic=$this->input->post('income_50k_to_100k_demographic');
		$income_100k_to_150k_demographic=$this->input->post('income_100k_to_150k_demographic');
		$income_over_150k_demographic=$this->input->post('income_over_150k_demographic');
		
		$parent_no_kids_demographic=$this->input->post('parent_no_kids_demographic');
		$parent_has_kids_demographic=$this->input->post('parent_has_kids_demographic');
		
		$education_no_college_demographic=$this->input->post('education_no_college_demographic');
		$education_college_demographic=$this->input->post('education_college_demographic');
		$education_grad_school_demographic=$this->input->post('education_grad_school_demographic');
		
		$result['context_array']=$this->siterankpoc_model->get_sites_for_mpq_ttd($iab_weight, 
				$iab_n_weight, $reach_weight, $stereo_weight, $industry_multi, $industry_n_multi, 
				$iab_multi, $industry_select, $iab_contextual_multiselect, $zips, 
				$gender_male_demographic, $gender_female_demographic, $age_under_18_demographic, 
				$age_18_to_24_demographic, $age_25_to_34_demographic, $age_35_to_44_demographic, 
				$age_45_to_54_demographic, $age_55_to_64_demographic, $age_over_65_demographic, 
				$income_under_50k_demographic, $income_50k_to_100k_demographic, 
				$income_100k_to_150k_demographic, $income_over_150k_demographic, $parent_no_kids_demographic,
				$parent_has_kids_demographic, $education_no_college_demographic, $education_college_demographic,
				$education_grad_school_demographic, $type_of_sites);
		
		$header=$this->siterankpoc_model->get_mpq_data_header($mpq_id, true);
		$result['industry_id']=$header['industry_id'];
		$result['context_id']=$header['context_id'];
		$result['demo_id']=$header['demo_id'];
		$result['zip_id']=$header['zip_id'];
		echo json_encode($result);
	}
	public function export_sites()
	{
		$sites_list=$this->input->post('sites_list');
		$site_array=explode(",", $sites_list);
		export_sites($site_array, "", "");
	}
}
