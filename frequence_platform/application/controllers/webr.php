<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Webr extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->helper('form');
    $this->load->helper('url');
    $this->load->library('tank_auth');
    $this->load->library('session');
    $this->load->library('form_validation');
    $this->load->model('webr_model');
  }

  /*
    user_id and username are collected from the session data while the rest of the variables are pulled from the database.
    This controller calls the tank auth library functions for each variable independently.
    The functions in tank_auth call the users model which in turn queries the database.
  */
  function index()
  {
    /* ~~UNCOMMENT THIS IF YOU WANT TO TRACK THE NUMBER OF TIMES YOU'VE HIT THE PAGE IN THE SESSION.
       if(!$this->session->userdata('counter1'))
       
       {
       $this->session->set_userdata('counter1', 1);
       }
       else {$this->session->set_userdata('counter1', $this->session->userdata('counter1') + 1);}
       echo " Times Visited = " . $this->session->userdata('counter1') . "<br>";
       ~~ */

    if (!$this->tank_auth->is_logged_in()) {
      $this->session->set_userdata('referer','report');
      redirect(site_url("login"));
    }
    else {

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
      $partner_id               = $this->tank_auth->get_partner_id($data['user_id']);
      if($data['role'] == 'business')
	{
	  $data['display_name'] = $data['business_name'];
	  $partner_id = $this->tank_auth->get_partner_by_business($data['business_name']);
	}
      
      $partner_info             = $this->tank_auth->get_partner_info($partner_id);
      $data['logo_path']        = $partner_info['partner_report_logo_filepath'];
      $data['favicon_path']     = $partner_info['favicon_path'];
      $data['home_url']         = $partner_info['home_url'];
      $data['contact_number']   = $partner_info['contact_number'];
      $data['partner_name']     = $partner_info['partner_name'];
      $data['report_css']       = $partner_info['report_css'];
      if($data['role'] != 'business')
	{
	  $data['display_name'] = $data['partner_name'];
	}
      $data['css']              = '/css/web_report.css';


      $this->session->set_userdata('campaignName', '.*');
      $this->session->set_userdata('startDate', date("Y-m-d", strtotime("-1 month -1days")));
      $this->session->set_userdata('endDate', date("Y-m-d", strtotime("-2 days")));
      
      if($partner_id != 1) //Partner is not Vantage Local
	{
	  $data['css'] = $data['report_css'];//'/css/whitelabel/report_'.$partner_id.'.css';
	  if($data['role'] == 'sales')
	    {
	      if($data['isGroupSuper'] == 1)
		{
		  $data['businesses'] = $this->tank_auth->get_partner_businesses($partner_id);
		}
	      else
		{
		  $data['businesses'] = $this->tank_auth->get_sales_businesses($data['user_id']);
		}
              if(array_key_exists('businesses', $_POST))
                {
                  $data['campaigns'] = $this->tank_auth->get_campaigns($_POST['businesses']);
                } 
              else
                {
	      $data['campaigns'] = $this->tank_auth->get_campaigns($data['businesses'][0]['Name']);
                }
              if($data['campaigns'][0]['Name'] != ""){
              $this->session->set_userdata('campaignName', $data['campaigns'][0]['Name']);
              } else {
                 $this->session->set_userdata('campaignName', '/*'); 
              }
	      $this->session->set_userdata('businessName', $data['businesses'][0]['Name']);
              $this->session->set_userdata('campaign_list', $data['campaigns']);
              $tags = array();
              if(array_key_exists('businesses', $data))
              {
                foreach($data['businesses'] as $business)
                {
                  foreach($this->webr_model->get_tags($business['Name']) as $temp)
                  {
                      array_push($tags, $temp['tag']);
                  }
                }
              }
                $data['tags'] = $tags;
                $this->load->view('vlocal-T1/webreport', $data);
              
	    }
	  else if($data['role'] == 'business')
	    {
	      $data['campaigns'] = $this->tank_auth->get_campaigns($data['business_name']);
               if($data['campaigns'][0]['Name'] != "")
                 {
                    $this->session->set_userdata('campaignName', $data['campaigns'][0]['Name']);
                 } 
	      $this->session->set_userdata('businessName', $data['business_name']);
              $tags = array();
              foreach($this->webr_model->get_tags($this->session->userdata('businessName')) as $temp)
              {
              array_push($tags, $temp['tag']);
              }
              $data['tags'] = $tags;
	      $this->load->view('vlocal-T1/webreport', $data);
	    }
	  else //Somehow we have a user that isn't vantage local and is ops, admin or creative
	    {
	      redirect('auth/logout');
	    }
	}
      else  //Vantage Local
	{
	  $data['logo_path'] = '/images/logo_old.gif';
	  if($data['role'] == 'business')
	    {
	      $data['campaigns'] = $this->tank_auth->get_campaigns($data['business_name']);
              if($data['campaigns'][0]['Name'] != "")
                {
                    $this->session->set_userdata('campaignName', $data['campaigns'][0]['Name']);
                } 
	      $this->session->set_userdata('businessName',$data['business_name']);
              $tags = array();
              foreach($this->webr_model->get_tags($this->session->userdata('businessName')) as $temp)
              {
              array_push($tags, $temp['tag']);
              }
              $data['tags'] = $tags;
	      $this->load->view('vlocal-T1/webreport', $data);
	    }
	  else if($data['role'] == 'admin' or $data['role'] == 'ops')
	    {
	      $data['businesses'] = $this->tank_auth->get_businesses();
	      $data['campaigns'] = $this->tank_auth->get_campaigns($data['businesses'][0]['Name']);
	      $this->session->set_userdata('businessName', $data['businesses'][0]['Name']);
	      $this->load->view('vlocal-T1/webreport', $data);
	    }
	  else if($data['role'] == 'sales')
	    {
	      if($data['isGroupSuper'])
		{$data['businesses'] = $this->tank_auth->get_partner_businesses($partner_info['id']);}	
	      else
		{$data['businesses'] = $this->tank_auth->get_sales_businesses($data['user_id']);}
	       if(array_key_exists('businesses', $_POST))
                {
                  $data['campaigns'] = $this->tank_auth->get_campaigns($_POST['businesses']);
                } 
              else
                {
	      $data['campaigns'] = $this->tank_auth->get_campaigns($data['businesses'][0]['Name']);
              if($data['campaigns'][0]['Name'] != ""){
              $this->session->set_userdata('campaignName', $data['campaigns'][0]['Name']);
              } else {
                 $this->session->set_userdata('campaignName', '/*'); 
              }
                }
	      $this->session->set_userdata('businessName', $data['businesses'][0]['Name']);
              $this->session->set_userdata('campaign_list', $data['campaigns']);
              $tags = array();
              if(array_key_exists('businesses', $data))
              {
                foreach($data['businesses'] as $business)
                {
                  foreach($this->webr_model->get_tags($business['Name']) as $temp)
                  {
                      array_push($tags, $temp['tag']);
                  }
                }
              }
                $data['tags'] = $tags;
	      $this->load->view('vlocal-T1/webreport', $data);
	    }
	  else 
	    {
	      redirect('director');
	    }
	}


      /*
	if($data['role'] == 'business') {$data['campaigns']	= $this->tank_auth->get_campaigns($data['business_name']);}
	else{$data['campaigns'] = $this->tank_auth->get_campaigns('Almaden Valley Athletic Club');}
			
	$data['all_business'] = $this->tank_auth->get_businesses();
			
	if ($data['role'] == 'admin' or $data['role'] == 'ops' or $data['role'] == 'creative') {
	$this->session->set_userdata('businessName', 'Almaden Valley Athletic Club');
	$this->load->view('vlocal-T1/webreport', $data);}
			
	else if ($data['role'] == 'business') {
	$this->session->set_userdata('businessName',$data['business_name']);
	$this->load->view('vlocal-T1/webreport', $data);}
      
	else if ($data['role'] == 'sales') {
	redirect('dashboard');
	}
	else {
	redirect('login');
	}*/
    }  
  }

  function get_campaign($business)
  {
    $stripped = str_replace("%20", " ", $business); //replace url space character with actual space to use in query.
    if (!$this->tank_auth->is_logged_in()) {
      redirect(site_url('login'));}
    $data['campaigns'] = $this->tank_auth->get_campaigns($stripped);
    $this->session->set_userdata('campaign_list', $data['campaigns']);
    $this->load->view('vlocal-T1/campaign_options', $data);

  }
  /*
    inserting internet average data into demographic records that have no data from quantcast
  */
 
  
  
  function fix_demographic_orphans()
  {
    //array of internet average demos
    $internet_average = array(
			      "Domain"		=> "Orphan Placeholder",
			      "Reach" 		=> "0",
			      "Gender_Male" 	=> ".49",
			      "Gender_Female" => ".51",
			      "Age_Under18" 	=> ".18",
			      "Age_18_24" 	=> ".12",
			      "Age_25_34" 	=> ".17",
			      "Age_35_44" 	=> ".2",
			      "Age_45_54" 	=> ".17",
			      "Age_55_64" 	=> ".1",
			      "Age_65" 		=> ".06",
			      "Race_Cauc" 	=> ".77",
			      "Race_Afr_Am" 	=> ".09",
			      "Race_Asian" 	=> ".04",
			      "Race_Hisp" 	=> ".09",
			      "Race_Other" 	=> ".01",
			      "Kids_No" 		=> ".5",
			      "Kids_Yes" 		=> ".5",
			      "Income_0_50" 	=> ".51",
			      "Income_50_100" => ".29",
			      "Income_100_150"=> ".12",
			      "Income_150" 	=> ".08",
			      "College_No" 	=> ".45",
			      "College_Under" => ".41",
			      "College_Grad" 	=> ".14",
			      "AdGroupID"		=> "",
			      );

    $rows_inserted = $this->tank_auth->insert_missing_domains($internet_average);
  }
}
