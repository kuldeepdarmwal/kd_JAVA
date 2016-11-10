<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Campaign_setup extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->helper('form');
    $this->load->helper('url');
    $this->load->helper('tradedesk');
	$this->load->helper('vl_ajax');
    $this->load->library('tank_auth');
    $this->load->library('session');
    $this->load->library('form_validation');
	  $this->load->library('vl_platform');

    $this->load->model('al_model');
    $this->load->model('campaign_health_model');
    $this->load->model('campaign_model');
    $this->load->model('proposals_model');    
  }
  
   public function campaign_tag_file()
    {          
	$campaign_id = $_POST['cam_id'];
	$advertiser_name = $_POST['adv_name'];
	$data['tags'] = $this->campaign_model->get_tags_campaign($campaign_id);
	$data['adv_name'] = $advertiser_name; 
	$this->load->view('tag/campaign_tags_table_view',$data);
   }
   
  public function index($c_id = 0, $iframe_request = 0)
  {
    if(!$this->tank_auth->is_logged_in())
    {
      $referrer = 'campaign_setup'.($c_id == 0 ? '' : '/'.$c_id);
      $this->session->set_userdata('referer',$referrer);
      redirect('login');
      return;
    }
    $username = $this->tank_auth->get_username();
    $role = $this->tank_auth->get_role($username);
    if ($role == 'ops' or $role == 'admin')
    {
      
      ///setup common header
      $data['username']   = $username;
      $data['firstname']  = $this->tank_auth->get_firstname($data['username']);
      $data['lastname']   = $this->tank_auth->get_lastname($data['username']);
      $data['user_id']    = $this->tank_auth->get_user_id();


     
      ///this is for the main subfunction specific body view
      $campaign_details = $this->al_model->get_campaign_details($c_id);
      $all_categories = $this->al_model->get_all_categories();
      $campaign_categories = $this->al_model->get_campaign_categories($c_id);
      $advertiser_data = $this->al_model->get_adv_details($campaign_details[0]['business_id']);
      $data['advertiser_json'] = ($campaign_details == null) ?  "\"none\"" : json_encode(array("id" => $advertiser_data['id'], "name" => $advertiser_data['Name']));
      //$data['a_id'] = ($campaign_details == null) ? "\"none\"" : $campaign_details[0]['business_id'];
      //$data['a_id'] = 181;
      $data['c_id'] = $c_id;

      $data['sales_people'] = $this->campaign_health_model->get_all_sales_people_with_partner();

      $data['active_menu_item'] = "campaigns_menu";//the view will make this menu item active
      $data['title'] = "AL4k [Campaigns]";
      $data['categories'] = $all_categories;
      $data['is_iframe'] = $iframe_request;
      //$data['selected_categories'] = $all_categories;
      $this->load->view('ad_linker/header',$data);
      
      $this->load->view('ad_linker/campaigns_form',$data);
    }else
    { 
      redirect('director'); 
    }
  } //index()


  public function get_advertiser_details()
  {
    $adv_id = $_GET["adv_id"];
    if(!$this->tank_auth->is_logged_in())
    {
      $return['success'] = FALSE;
      $return['info'] = 'you need to login again';
    }else{
      $username = $this->tank_auth->get_username();
      $role = $this->tank_auth->get_role($username);
      if ($role == 'ops' or $role == 'admin'){
        $return['data'] = $this->al_model->get_adv_details($adv_id); 
        $return['success'] = TRUE; 
      }else{
        $return['success'] = FALSE;
        $return['info'] = 'you are not authorized for some reason';
      }

    }
  echo json_encode($return);
  }

  public function get_campaign_details()
  {
    $c_id = $_POST["c_id"];
    if(!$this->tank_auth->is_logged_in())
    {
      $return['success'] = FALSE;
      $return['info'] = 'you need to login again';
    }else{
      $username = $this->tank_auth->get_username();
      $role = $this->tank_auth->get_role($username);
      if ($role == 'ops' or $role == 'admin'){
        $return['data'] = $this->al_model->get_campaign_details($c_id); 
        $return['success'] = TRUE; 
      }else{
        $return['success'] = FALSE;
        $return['info'] = "how'd you do that?";
      }

    }
  echo json_encode($return);
  }

  public function create_new_advertiser()
  {
    if(!$this->tank_auth->is_logged_in())
    {
      $return['success'] = FALSE;
      $return['info'] = 'you need to login again';
    }else{
      $username = $this->tank_auth->get_username();
      $role = $this->tank_auth->get_role($username);
      if ($role == 'ops' or $role == 'admin'){
        $insert_array = array(
              "Name" => trim($_POST["adv"]),
              "sales_person" =>  $_POST["s_id"]);  
        //$return['effected_rows'] = $this->al_model->create_advertiser($insert_array); 
        $return['data'] = $this->al_model->create_advertiser($insert_array);
        $return['success'] = ($return['data'] != 0)? TRUE : FALSE; 
      }else{
        $return['success'] = FALSE;
        $return['info'] = "not authorized: how'd you get here?";
      }
    }
    echo json_encode($return);
  }


public function create_new_campaign()
  {
    echo $_POST["e_date"];
    // if(!$this->tank_auth->is_logged_in())
    // {
    //   $return['success'] = FALSE;
    //   $return['info'] = 'you need to login again';
    // }else{
    //   $username = $this->tank_auth->get_username();
    //   $role = $this->tank_auth->get_role($username);
    //   if ($role == 'ops' or $role == 'admin'){
    //     //a_id, c_name,lp,imprs,e_date
    //     $insert_array = array(
    //           "Name" => trim($_POST["c_name"]),
    //           "business_id" => $_POST["a_id"],
    //           "TargetImpressions" => $_POST["imprs"],
    //           "hard_end_date" => $_POST["e_date"],
    //           "LandingPage" =>  $_POST["lp"]); 
              
    //     //$return['effected_rows'] = $this->al_model->create_advertiser($insert_array); 
    //     $return['id'] = $this->al_model->create_campaign($insert_array);
    //     $return['success'] = ($return['id']>0)? TRUE : FALSE; 
    //   }else{
    //     $return['success'] = FALSE;
    //     $return['info'] = "not authorized: how'd you get here?";
    //   }
    // }
    // echo json_encode($return);
  }



  public function  get_vl_advertisers_dropdown()
  {
    
    $advertisers = $this->tank_auth->get_businesses();
    echo '<option value="none">Select Advertiser</option><option value="new">*New*</option>';
    foreach($advertisers as $advertiser)
    {
    echo '<option value="' .$advertiser['id']. '">' .$advertiser['Name']. '</option>';
    }
  }


public function  get_vl_campaigns_dropdown()
  { 
    $advertiser_id = $_POST["adv_id"];
    $campaigns = $this->tank_auth->get_campaigns_by_id($advertiser_id);
    foreach($campaigns as $campaign)
    {
    echo '<option value="' .$campaign['id']. '">' .$campaign['Name']. '</option>';
      
    }
    echo '<option value="new">*New*</option>';
  }

public function update_advertiser()
  {
    if(!$this->tank_auth->is_logged_in())
    {
      $return['success'] = FALSE;
      $return['info'] = 'you need to login again';
    }else{
      $username = $this->tank_auth->get_username();
      $role = $this->tank_auth->get_role($username);
      if ($role == 'ops' or $role == 'admin'){
        $insert_array = array(
            "sales_person" =>  $_POST["s_id"],
            "id" => trim($_POST["adv_id"]));  
        //$return['effected_rows'] = $this->al_model->create_advertiser($insert_array); 
        $return['success'] = ($this->al_model->update_advertiser($insert_array)>0)? TRUE : FALSE; 
      }else{
        $return['success'] = FALSE;
        $return['info'] = "not authorized: how'd you get here?";
      }
    }
    echo json_encode($return);
  }


  public function update_campaign()
  {
    if(!$this->tank_auth->is_logged_in())
    {
      $return['success'] = FALSE;
      $return['info'] = 'you need to login again';
    }else{
      $username = $this->tank_auth->get_username();
      $role = $this->tank_auth->get_role($username);
      if ($role == 'ops' or $role == 'admin'){
        //c_id: c_id,lp: lp,imprs: imprs, e_date: e_date
        $insert_array = array(
            "LandingPage" =>  $_POST["lp"],
            "TargetImpressions" =>  $_POST["imprs"],
            "hard_end_date" =>  $_POST["e_date"],
            "id" => $_POST["c_id"]);  
        //$return['effected_rows'] = $this->al_model->create_advertiser($insert_array); 
        $return['success'] = ($this->al_model->update_campaign($insert_array)>0)? TRUE : FALSE; 
      }else{
        $return['success'] = FALSE;
        $return['info'] = "not authorized: how'd you get here?";
      }
    }
    echo json_encode($return);
  }

  public function create_new_campaign_from_insertion_order()
  {
    if ($this->input->post("insertion_order_id", true))
    {

      $passed_params = $this->input->post(null, true);

      if (empty($passed_params['campaign_name']) || empty($passed_params['business_id']))
      {
        $error = 'Campaign Name, Advertiser are required fields';
        $this->output->set_status_header(400, 'Bad Request');
        $this->output->set_output(
          $error
        );
        return;
      }

      try
      {
        $campaign = $this->al_model->create_campaign_from_insertion_order($passed_params['campaign_name'], $passed_params['business_id'], $passed_params['insertion_order_id']);
      }
      catch (Exception $e)
      {
        $this->output->set_status_header($e->getCode(), 'Conflict');
        $this->output->set_output($e->getMessage());
        return;
      }

      $this->output->set_status_header(200, 'OK');
      $this->output->set_output(json_encode($campaign));
    }
    else
    {
      $this->output->set_status_header(400, 'Bad Request');
      $this->output->set_output('Must include an insertion order ID with your request.');
    }
    
  }
  public function ajax_get_assigned_advertiser_owners()
  {
	  $allowed_roles = array('admin', 'ops');
	  $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
	  $return_array = array('is_success' => true, 'errors' => "", 'data' => array());
	  if($verify_ajax_response['is_success'] === true)
	  {
		  $advertiser_id = $this->input->post('advertiser_id');
		  if($advertiser_id !== false && is_numeric($advertiser_id))
		  {
			  $result = $this->al_model->get_advertiser_owners_by_advertiser_id($advertiser_id);
			  if($result !== false)
			  {
				  $return_array['data'] = $result;
			  }
			  else
			  {
				  $result_array['is_success'] = false;
				  $result_array['errors'] = "Error 469896: database error when retrieving advertiser owner data";
			  }
		  }
		  else
		  {
			  $return_array['is_success'] = false;
			  $return_array['errors'] = "Error 469895: incorrect advertiser_id when trying to get advertiser owners";
		  }
	  }
	  else
	  {
		  $return_array['is_success'] = false;
		  $return_array['errors'] = "Error 469894: user logged out or not permitted";
	  }
	  echo json_encode($return_array);
  }
  
  public function select2_get_allowed_advertiser_owners()
  {
	  $allowed_roles = array('admin', 'ops');
	  $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
	  $dropdown_list = array('result' => array(), 'more' => false);
	  if($verify_ajax_response['is_success'] === true)
	  {
		  $raw_term = $this->input->post('q');
		  $page_limit = $this->input->post('page_limit');
		  $page_number = $this->input->post('page');
		  if($raw_term && is_numeric($page_limit) && is_numeric($page_number))
		  {
			  if($raw_term != '%')
			  {
				  $search_term = '%'.$raw_term.'%';
			  }
			  else
			  {
				  $search_term = $raw_term;
			  }
			  $mysql_page_number = ($page_number - 1) * $page_limit;
			  $result = $this->al_model->get_advertiser_owners_by_search_term($search_term, $mysql_page_number, ($page_limit + 1));
			  if(!empty($result))
			  {
				  if(count($result) == $page_limit + 1)
				  {
					  $real_count = $page_limit;
					  $dropdown_list['more'] = true;
				  }
				  else
				  {
					  $real_count = count($result);
				  }
				  for($i = 0; $i < $real_count; $i++)
				  {
					  $dropdown_list['result'][] = array("id" => $result[$i]['id'],	"text" => $result[$i]['text']);
				  }
			  }
		  }
	  }
	  echo json_encode($dropdown_list);
  }
  
  // this method calls get_timeseries_start_dates and returns array of start dates
  public function get_initial_timeseries_start_date_array()
  {
    	$allowed_roles = array('admin', 'ops');
    	$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
    	$dropdown_list = array('result' => array());
    	if($verify_ajax_response['is_success'] === true)
    	{
    		$start_date = $this->input->post('start_date');
    		$end_date = $this->input->post('end_date');
    		$initial_series_type = $this->input->post('initial_series_type');
    		$return_calendar_array=get_timeseries_start_dates($initial_series_type, $start_date, $end_date);
    		$dropdown_list['result']=$return_calendar_array;
    	}
      echo json_encode($dropdown_list);
  }

  public function ajax_add_advertiser_owner()
  {
	  $allowed_roles = array('admin', 'ops');
	  $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
	  $result_array = array('is_success' => true, 'errors' => "");
	  if($verify_ajax_response['is_success'] === true)
	  {
		  $advertiser_id = $this->input->post('advertiser_id');
		  $user_id = $this->input->post('user_id');
		  if($advertiser_id !== false && $user_id !== false)
		  {
			  $result = $this->al_model->add_advertiser_owner_relationship($user_id, $advertiser_id);
			  if($result === false)
			  {
				  $result_array['is_success'] = false;
				  $result_array['errors'] = "Error 164320: unable to save advertiser owner relationship";
			  }
		  }
		  else
		  {
			  $result_array['is_success'] = false;
			  $result_array['errors'] = "Error 164321: incorrect parameters when saving advertiser owner";
		  }
	  }
	  else
	  {
		  $result_array['is_success'] = false;
		  $result_array['errors'] = "Error: 164322: user logged out or not permitted";
	  }
	  echo json_encode($result_array);
  }
  
  public function ajax_remove_advertiser_owner()
  {
	  $allowed_roles = array('admin', 'ops');
	  $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
	  $result_array = array('is_success' => true, 'errors' => "");
	  if($verify_ajax_response['is_success'] === true)
	  {
		  $advertiser_id = $this->input->post('advertiser_id');
		  $user_id = $this->input->post('user_id');
		  if($advertiser_id !== false && $user_id !== false)
		  {
			  $result = $this->al_model->remove_advertiser_owner_relationship($user_id, $advertiser_id);
			  if($result === false)
			  {
				  $result_array['is_success'] = false;
				  $result_array['errors'] = "Error 164323: unable to delete advertiser owner relationship";
			  }
		  }
		  else
		  {
			  $result_array['is_success'] = false;
			  $result_array['errors'] = "Error 164324: incorrect parameters when deleting advertiser owner";
		  }
	  }
	  else
	  {
		  $result_array['is_success'] = false;
		  $result_array['errors'] = "Error: 164325: user logged out or not permitted";
	  }
	  echo json_encode($result_array);
  }

  public function add_new_note()
  {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $new_note_text = $this->input->post('new_note_text');
        $object_id = $this->input->post('object_id');
        $object_type_id = $this->input->post('object_type_id');
        $legacy_date = $this->input->post('legacy_date');
        
        $username = $this->tank_auth->get_username();
        $this->al_model->add_new_note($object_id, $new_note_text, $username, $object_type_id, $legacy_date);
        $result['is_success'] = true;
      }
      echo json_encode($result);
  }
  
  public function generate_campaign_notes()
  {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $campaign_id = $this->input->post('campaign_id');
        $geo_data = $this->input->post('geo_data');
        $campaign_region = $this->input->post('campaign_region');
        $campaign_product = $this->input->post('campaign_product');
        $result = $this->al_model->generate_campaign_notes($campaign_id, $campaign_region, $campaign_product, $geo_data);

        // Get demographic data
        $result['demo'] = '';
        if (!empty($result['demographic_data']))
        {
            $demo_data = $this->proposals_model->parse_demo_string($result['demographic_data']);            
            foreach ($demo_data as $key => $val)
            {
                $result['demo'] .= $key.' - '.$val.'<br />';
            }
            $result['demo'] = rtrim($result['demo'], '<br />');
        }

        $result['is_success'] = true;
      }
      echo json_encode($result);
  }

  public function update_note_bad_flag()
  {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $note_id = $this->input->post('notes_id');
        $result['is_success'] = $this->al_model->update_note_bad_flag($note_id);;
      }
      echo json_encode($result);
  }

  public function update_imp_flag()
  {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $note_id = $this->input->post('notes_id');
        $result['is_success'] = $this->al_model->update_imp_flag($note_id);;
      }
      echo json_encode($result);
  }

  public function get_notes_for_campaign()
  {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $cid = $this->input->post('cid');
        $advertiser_id = $this->input->post('advertiser_id');
        
        $notes_array=$this->al_model->get_notes_for_campaign($advertiser_id, $cid);
        $result['is_success'] = true;
        $result['notes_data'] = $notes_array;
      }
      echo json_encode($result);
  }


	public function edit_flights()
	{
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'admin')
		{
			$data['title'] = "Edit Campaign Flights";
			$this->vl_platform->show_views(
				$this,
				$data,
				'campaign_setup',
				"campaign_setup/body",
				'campaign_setup/header',
				null,
				'campaign_setup/footer',
				null,
				true,
				true
			);
		}
	}
   
 
}//class