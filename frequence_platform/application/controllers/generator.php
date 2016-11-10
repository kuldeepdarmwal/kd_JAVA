<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Generator extends CI_Controller
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
	
  function index()
  {
    
    if (!$this->tank_auth->is_logged_in()) {			//If not logged in, goto: login page
      $this->session->set_userdata('referer', 'director');
      redirect('login');
    }

    $data['user_id']		= $this->tank_auth->get_user_id();
    $data['username']		= $this->tank_auth->get_username();
    $data['business_name']    = $this->tank_auth->get_biz_name($data['username']);
    $data['role']	        = $this->tank_auth->get_role($data['username']);
    $data['email']		= $this->tank_auth->get_email($data['username']);
    $data['firstname']	= $this->tank_auth->get_firstname($data['username']);
    $data['lastname']		= $this->tank_auth->get_lastname($data['username']);
    $data['bgroup']           = $this->tank_auth->get_bgroup($data['username']);
    $data['isGroupSuper'] 	= $this->tank_auth->get_isGroupSuper($data['username']);
    $data['isGlobalSuper']	= $this->tank_auth->get_isGlobalSuper($data['username']);
			
    if($data['role'] == 'business')
      {
	redirect('report');
      }
    else if($data['role'] == 'sales')
      {
	redirect('dashboard');
      }
    else if($data['role'] == 'creative')
      {
	redirect('dashboard');
      }

				
    $this->load->view('ring_sample/header', $data);
    $this->load->view('ring_sample/generator_navbar', $data);
    $this->load->view('ring_sample/generator_body', $data);
			

    /*
  $username = $this->tank_auth->get_username();
  $role = $this->tank_auth->get_role($username);
  $data['role'] = $role;
  $business_name  = $this->tank_auth->get_biz_name($username);
  if($role == 'admin' or $role == 'creative' or $role == 'ops')
    {
      $this->load->view('vlocal-T1/directit', $data);
    }
  else if($role == 'sales' )
    {
      if($business_name=="Vantage Local Corporate" )
	{
	  redirect('dashboard');
	}
      else
	{
	  redirect('partner_forms');
	}
    }
  else if($role == 'business')
    {
      redirect('report');
    }
  else
    {
      redirect('login');
      }*/
}
  function planner()
  {
        
    if (!$this->tank_auth->is_logged_in()) {			//If not logged in, goto: login page
      $this->session->set_userdata('referer', 'planner');
      redirect('login');
    }

    $data['user_id']		= $this->tank_auth->get_user_id();
    $data['username']		= $this->tank_auth->get_username();
    $data['business_name']    = $this->tank_auth->get_biz_name($data['username']);
    $data['role']	        = $this->tank_auth->get_role($data['username']);
    $data['email']		= $this->tank_auth->get_email($data['username']);
    $data['firstname']	= $this->tank_auth->get_firstname($data['username']);
    $data['lastname']		= $this->tank_auth->get_lastname($data['username']);
    $data['bgroup']           = $this->tank_auth->get_bgroup($data['username']);
    $data['isGroupSuper'] 	= $this->tank_auth->get_isGroupSuper($data['username']);
    $data['isGlobalSuper']	= $this->tank_auth->get_isGlobalSuper($data['username']);
			
    if($data['role'] != 'sales')
      {
	redirect('director');
      }	

				
    $this->load->view('ring_sample/header', $data);
    $this->load->view('ring_sample/generator_navbar', $data);
    $this->load->view('ring_sample/generator_body', $data);
  }
function rollout()										//load github rollout page
{
  if (!$this->tank_auth->is_logged_in()) {
    $this->session->set_userdata('referer', 'director/rollout');
    redirect('login');
  }
  $this->load->view('vlocal-T1/git_roller');
}
/*
  function redirectit($uri)
  {
  $exploded_uri = explode('/', $uri, 2);
  if($exploded_uri[0] == 'info')
  {
  redirect('info.vantagelocal.com/'.$exploded_uri[1]);
  }
  else if($exploded_uri[0] == 'testimonials')
  {
  redirect('info.vantagelocal.com/'.$uri);
  }
  else  if($exploded_uri[0] == 'creative')
  {
  redirect('creative.vantagelocal.com/'.$exploded_uri[1]);
  }
  else{show_404($uri);}
  }*/
	
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
      $password = "v@ntag3l0cal";
      $role = 'admin';
      $isGlobalSuper = 1;
      $bgroup = 'none';
      $isGroupSuper = 1;
      $business_name = 'Vantage Local Corporate';
      $firstname = 'Temporary';
      $lastname = 'Admin';
      $email_activation = FALSE;
    
      $this->tank_auth->create_user($username, $email, $password, $role, $isGlobalSuper, $bgroup, $isGroupSuper, $business_name, $firstname, $lastname, $email_activation);
      echo "Temporary admin user: ".$username." created";
    }
  else
    {
      echo "username required to create first time admin user";
    }

}
/*
  function roll_deactivation_with_business_user_account_and_username($roll='')
  {
  if($roll!='run'||
		
  $this->tank_auth->deactivate($roll)}
  }
*/
}

?>
