<?php

class vl_platform_v2 extends CI_Controller
{
    
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
    $this->load->library('tank_auth');
		$this->load->helper('url');
	}

	public function test_director_v2()
	{
		// Will probably redirect to a feature depending on permissions and role.
		// Placeholder for now.

		$data = array();
		$data['firstname'] = 'scott_fn';
		$data['lastname'] = 'h_ln';
		$data['user_id'] = '3';
		$data['title'] = 'hello mpq';
		$data['has_partner_dashboar_access'] = true;
		$data['has_mpq_access'] = true;
		$data['has_prop_gen_access'] = true;
		$data['has_ad_ops_access'] = true;
		$data['active_menu_item'] = 'mpq_feature_link';
		$subfeatures_header_item = array();
		$data['subfeatures_header_item'] = $subfeatures_header_item;
		

		$this->load->view('vl_platform_2/ui_core/common_header', $data);
		$this->load->view('vl_platform_2/ui_core/subfeature_header_template', $data);
		//$this->load->view('mpq_v2/feature_specific_html', $data);
		$this->load->view('vl_platform_2/ui_core/common_footer_pre_js', $data);
		//$this->load->view('mpq_v2/feature_specific_js', $data);
		$this->load->view('vl_platform_2/ui_core/common_footer_post_js', $data);
	}
}

?>
