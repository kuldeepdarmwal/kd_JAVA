<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Third_party_linker extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->helper('form');
    $this->load->helper('url');
    $this->load->library('tank_auth');
    $this->load->library('session');
    $this->load->library('form_validation');
    $this->load->model('al_model');
    $this->load->helper('vl_ajax_helper');
    $this->load->library('vl_platform');
    $this->load->model('third_party_linker_model');
    $this->load->helper('select2_helper');
	$this->load->helper('report_v2_model_helper');
  }
  

  public function index()
  {
    if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('third_party_linker', '/third_party_linker'))
    {
      return;
    }
    $data['title'] = 'Reporting Linker';
    $active_feature_button_id = 'third_party_linker';
    

    $this->vl_platform->show_views(
      $this,
      $data,
      $active_feature_button_id,
      'third_party_linker/subfeature_html_body',
      'third_party_linker/feature_css_header',
      false,
      'third_party_linker/feature_js',
      false
    );
  }

 

  public function get_all_linked_advertisers_and_accounts()
  {
    
    $errors = array();
    $is_success = false;

    $allowed_user_types = array('ops', 'admin','sales');
    $required_post_variables = array();
    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
    if($ajax_verify['is_success'])
    {
      $username = $this->tank_auth->get_username();
      $table_data = $this->third_party_linker_model->get_allowed_advertisers_and_accounts($this->tank_auth->get_user_id(), strtolower($this->tank_auth->get_role($username)), $this->tank_auth->get_isGroupSuper($username));
      $is_success = true;
    }
    else
    {
      foreach($ajax_verify['errors'] as $err)
      {
        $errors[] = $err;
      }
    }

    echo json_encode(array('data'=>$table_data,'errors'=>$errors,'is_success'=>$is_success));
  }




  public function get_third_party_account_details_for_advertiser()
  {
    $errors = array();
    $potential_accounts = null;
    $assigned_accounts = null;
    $is_success = false;
    $allowed_user_types = array('ops', 'admin','sales');
    $required_post_variables = array('f_advertiser_id');
    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
    if($ajax_verify['is_success'])
    {
      $all_potential_accounts = $this->third_party_linker_model->get_all_third_party_accounts_for_advertiser($ajax_verify['post']['f_advertiser_id']);
      $assigned_accounts = $this->third_party_linker_model->get_all_linked_third_party_accounts($ajax_verify['post']['f_advertiser_id']);
      $is_success = true;
    }
    else{
      foreach($ajax_verify['errors'] as $err)
      {
        $errors[] = $err;
      }
    }
    echo json_encode(array('potential_accounts'=>$all_potential_accounts,'assigned_accounts'=>$assigned_accounts[0]['tp_accounts_object'],'errors'=>$errors,'is_success'=>$is_success));   
  }

  

  public function link_accounts_to_advertiser()
  {
	$errors = array();
	$is_success = false;
	$new_linked_accounts_json = null;
	$allowed_user_types = array('ops', 'admin','sales');
	$required_post_variables = array('f_advertiser_id','tp_accounts_string');
	$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
	if($ajax_verify['is_success'])
    {
		if(!empty($ajax_verify['post']['tp_accounts_string']))
		{
			$accounts_array = explode(",",$ajax_verify['post']['tp_accounts_string']);
		}
		else
		{
			$accounts_array = null;
		}
		$update_result = $this->third_party_linker_model->update_links_to_account($accounts_array,$ajax_verify['post']['f_advertiser_id']);
		$is_success = $update_result['is_success'];
		$new_linked_accounts_json = $update_result['new_accounts'];
    }
    else
    {
		foreach($ajax_verify['errors'] as $err)
		{
			$errors[] = $err;
		}
    }
    echo json_encode(array('errors'=>$errors,'is_success'=>$is_success,'new_linked_accounts'=>$new_linked_accounts_json));   
 
  }



  public function get_advertisers() ////DEPRECATE
  {
    $allowed_user_types = array('ops', 'admin','sales');
    $required_post_variables = array('q','page_limit','page');
    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
    if($ajax_verify['is_success'])
    {
      $user_id = $this->tank_auth->get_user_id();
      $username = $this->tank_auth->get_username();
      $role = $this->tank_auth->get_role($username);
      $is_group_super = $this->tank_auth->get_isGroupSuper($username);

      $post_array = $ajax_verify['post'];
      $post_array['q'] = str_replace(" ", "%", $post_array['q']);

      $advertisers_response = select2_helper($this->third_party_linker_model, 'get_allowed_advertisers', $post_array, array($user_id, $role, $is_group_super));
      if (empty($advertisers_response['results']) || $advertisers_response['errors'])
      {
        $advertisers_response['results'] = array();
      }
      echo json_encode($advertisers_response);
    }
  }


 

  public function add_single_account_to_advertiser()
  {
    $errors = array();
    $is_success = false;
    $new_linked_accounts_json = null;
    $allowed_user_types = array('ops', 'admin','sales');
    $required_post_variables = array('f_advertiser_id','account_id');
    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
    if($ajax_verify['is_success'])
    {
      if($this->third_party_linker_model->add_links_to_account(array($ajax_verify['post']['account_id']), $ajax_verify['post']['f_advertiser_id']))
      {
        $is_success = true;
        $new_linked_accounts = $this->third_party_linker_model->get_all_linked_third_party_accounts($ajax_verify['post']['f_advertiser_id']);
        $errors[] = 'something went wrong adding account to advertiser';
      }
    }
    else
    {
      foreach($ajax_verify['errors'] as $err)
      {
        $errors[] = $err;
      }
    }
    echo json_encode(array('errors'=>$errors,'is_success'=>$is_success,'new_linked_accounts'=>$new_linked_accounts[0]['tp_accounts_object']));   
  }
 
}
