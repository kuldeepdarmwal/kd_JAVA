<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Partner_login extends CI_Controller
{
  function __construct()
  {
    parent::__construct();

    $this->load->helper(array('form', 'url'));
    $this->load->library('form_validation');
    $this->load->library('tank_auth');
    $this->lang->load('tank_auth');
  }
  function _remap($parameter)
  {
    $this->index($parameter);
  }
  function index($partner_id = 0)
  {
    if($partner_id > 0)
      {
	$data['partner_id'] = $partner_id;
	$partner_info = $this->tank_auth->get_partner_info($data['partner_id']);
	$data['logo_path'] = $partner_info['partner_report_logo_filepath'];
	$data['favicon_path'] = $partner_info['favicon_path'];
	$data['css_path'] = $partner_info['css_filepath'];
	$data['home_url'] = $partner_info['home_url'];
	$data['contact_number'] = $partner_info['contact_number'];
	$data['partner_name'] = $partner_info['partner_name'];
	$this->load->view('auth/partner_login', $data);
      }
    else
      {
	echo "Partner ID required.<br>";
      }
  }
}
