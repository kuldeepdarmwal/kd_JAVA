<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Director extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('url');
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->library('session');
	}

	function index($force_session = "")
	{
		if (!$this->tank_auth->is_logged_in())
		{
			//If not logged in, goto: login page
			$this->session->set_userdata('referer', 'director');
			redirect('login');
		}

		$data['user_id'] = $this->tank_auth->get_user_id();
		$data['username'] = $this->tank_auth->get_username();
		$data['business_name'] = $this->tank_auth->get_biz_name($data['username']);
		$data['role'] = $this->tank_auth->get_role($data['username']);
		$data['email'] = $this->tank_auth->get_email($data['username']);
		$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
		$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
		$data['bgroup'] = $this->tank_auth->get_bgroup($data['username']);
		$data['isGroupSuper'] = $this->tank_auth->get_isGroupSuper($data['username']);
		$data['isGlobalSuper'] = $this->tank_auth->get_isGlobalSuper($data['username']);
		$data['partner_id'] = $this->tank_auth->get_partner_id($data['user_id']);
		$data['planner_viewable'] = $this->tank_auth->get_planner_viewable($data['user_id']);
		$partner_info = $this->tank_auth->get_partner_info($data['partner_id']);
		$data['logo_path'] = $partner_info['partner_report_logo_filepath'];
		$data['favicon_path'] = $partner_info['favicon_path'];
		$data['partner_name'] = $partner_info['partner_name'];

		switch($data['role'])
		{
			case 'admin':
				redirect('campaign_setup');
				break;
			case 'agency':
				redirect('report');
				break;
			case 'business':
				redirect('report');
				break;
			case 'client':
				redirect('report');
				break;
			case 'creative':
				redirect('creative_uploader');
				break;
			case 'ops':
				redirect('campaign_setup');
				break;
			case 'sales':
				redirect('campaigns_main');
				break;
			default:
				redirect('report');
				break;
		}
	}

	function planner()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			//If not logged in, goto: login page
			$this->session->set_userdata('referer', 'planner');
			redirect('login');
		}

		$data['user_id'] = $this->tank_auth->get_user_id();
		$data['username'] = $this->tank_auth->get_username();
		$data['business_name'] = $this->tank_auth->get_biz_name($data['username']);
		$data['role'] = $this->tank_auth->get_role($data['username']);
		$data['email'] = $this->tank_auth->get_email($data['username']);
		$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
		$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
		$data['bgroup'] = $this->tank_auth->get_bgroup($data['username']);
		$data['isGroupSuper'] = $this->tank_auth->get_isGroupSuper($data['username']);
		$data['isGlobalSuper'] = $this->tank_auth->get_isGlobalSuper($data['username']);
		$data['planner_viewable'] = $this->tank_auth->get_planner_viewable($data['user_id']);

		if($data['role'] == 'business')
		{
			redirect('report');
			return;
		}
		else if($data['planner_viewable'] == 0)
		{
			redirect('director');
			return;
		}

		$this->load->view('ring_sample/header', $data);
		$this->load->view('ring_sample/planner', $data);
		$this->load->view('ring_sample/body', $data);
	}

	function rollout()										//load github rollout page
	{
		if (!$this->tank_auth->is_logged_in()) {
			$this->session->set_userdata('referer', 'rollout');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if($role != 'admin')
		{
			redirect('director');
		}
		$this->load->view('vlocal-T1/git_roller');
	}

function creative($uri)
{
  redirect('http://creative.vantagelocal.com/'.$uri);
}
function info($uri)
{
  redirect('http://info.vantagelocal.com/'.$uri);
}
function testimonials($uri)
{
  redirect('http://info.vantagelocal.com/testimonials/'.$uri);
}
function ad_demo($uri='')
{
  redirect('http://creative.vantagelocal.com/ad_demo/'.$uri);
}
function demo_files($uri)
{
  redirect('http://creative.vantagelocal.com/demo_files/'.$uri);
}
function environment_setup($username='')
{
  if(strlen($username) > 0)
    {
      $email = $username."@".time().".com";
      $password = "l0c@lbrand";
      $role = 'admin';
      $isGlobalSuper = 1;
      $bgroup = 'none';
      $isGroupSuper = 1;
      $partner = 1;
      $business_name = 'Vantage Local Corporate';
      $firstname = 'Temporary';
      $lastname = 'Admin';
      $email_activation = FALSE;
    
      $this->tank_auth->create_user($username, $email, $password, $role, $isGlobalSuper, $bgroup, $isGroupSuper, $business_name, $firstname, $lastname, $partner, $email_activation);
      echo "Temporary admin user: ".$username." created";
    }
  else
    {
      echo "username required to create first time admin user";
    }

}

}

?>
