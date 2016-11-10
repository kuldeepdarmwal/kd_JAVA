<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class site_tag_admin_controller extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array(
				'form',
				'url'
		));
		$this->load->library('form_validation');
		$this->load->library('table');
		$this->load->library('tank_auth');
		$this->load->model('site_tag_admin_model');
	}
	public function save_site_data_grid()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$tag_data=$this->input->post('tag_data');
			$site_id=$this->input->post('site_id');
			$site_id=substr($site_id, 2);
			$tag_type=$this->input->post('tag_type');
			$return_message=$this->site_tag_admin_model->save_site_data_grid($tag_data, $site_id, $tag_type);
			$response["message"]=$return_message;
		}
		echo json_encode($response);
	}
	public function save_tag_data_grid()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$tag_data=$this->input->post('tag_data');
			$tag_id=$this->input->post('tag_id');
			$bidder_flag=$this->input->post('bidder_flag');
			$tag_type=substr($tag_id, 0, 1);
			$tag_id1=substr($tag_id, 2);
			$response['data']=$this->site_tag_admin_model->save_tag_data_grid($tag_data, $tag_id1, $tag_type, $bidder_flag);
			$response['tag_id']=$tag_id;
		}
		echo json_encode($response);
	}
	public function index($c_id=0)
	{
		if (!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', 'site_tag_admin_controller');
			redirect('login');
			return;
		}
		$username=$this->tank_auth->get_username();
		$role=$this->tank_auth->get_role($username);
		if ($role=='admin')
		{
			// /setup common header
			$data['username']=$username;
			$data['firstname']=$this->tank_auth->get_firstname($data['username']);
			$data['lastname']=$this->tank_auth->get_lastname($data['username']);
			$data['user_id']=$this->tank_auth->get_user_id();
			$data['title']="Site Tag Admin";
			$todo_array=$this->site_tag_admin_model->get_todo_items_for_bar();
			$data['todo_array']=$todo_array;
			$this->load->view('ad_linker/header', $data);
			$this->load->view('site_tag_v2/site_tag_admin_view', $data);
		}
		else
		{
			redirect('director');
		}
	} // index()
	  
	// this is called to display data in the select2 when user types something. this can return data in multiple modes
	public function ajax_media_targeting_tags()
	{
		$response=$this->verify_ajax_call();
		$dropdown_list=array(
				'result'=>array(),
				'more'=>false
		);
		if ($response['is_success'])
		{
			$raw_term=$this->input->post('q');
			$page_limit=$this->input->post('page_limit');
			$page_number=$this->input->post('page');
			$source=$this->input->post('source');
			$bidder_flag=$this->input->post('bidder_flag');
			if ($raw_term&&is_numeric($page_limit)&&is_numeric($page_number))
			{
				if ($raw_term!='%')
				{
					$search_term='%'.str_replace(" ", "%", $raw_term).'%';
				}
				else
				{
					$search_term=$raw_term;
				}
				$mysql_page_number=($page_number-1)*$page_limit;
				$result=false;
				if ($source=="all")
					$result=$this->site_tag_admin_model->get_media_targeting_tags_by_term($search_term, $mysql_page_number, ($page_limit+1));
				else if ($source=="0")
					$result=$this->site_tag_admin_model->get_sites_media_targeting_tags_by_term($search_term, $mysql_page_number, ($page_limit+1), $bidder_flag);
				else if ($source=="1")
					$result=$this->site_tag_admin_model->get_context_media_targeting_tags_by_term($search_term, $mysql_page_number, ($page_limit+1));
				else if ($source=="2")
					$result=$this->site_tag_admin_model->get_geo_media_targeting_tags_by_term($search_term, $mysql_page_number, ($page_limit+1));
				else if ($source=="4")
					$result=$this->site_tag_admin_model->get_stereo_media_targeting_tags_by_term($search_term, $mysql_page_number, ($page_limit+1));
				
				if ($result)
				{
					if (count($result)==$page_limit+1)
					{
						$real_count=$page_limit;
						$dropdown_list['more']=true;
					}
					else
					{
						$real_count=count($result);
					}
					for ($i=0; $i<$real_count; $i++)
					{
						
						$dropdown_list['result'][]=array(
								"id"=>$result[$i]['id'],
								"text"=>$result[$i]['tag_copy'],
								"tag_type"=>$result[$i]['tag_type']
						);
					}
				}
			}
		}
		echo json_encode($dropdown_list);
	}
	
	// generic util method
	private function verify_ajax_call()
	{
		if ($_SERVER['REQUEST_METHOD']==='POST')
		{
			$response=array(
					'is_success'=>true,
					'errors'=>array()
			);
			if ($this->tank_auth->is_logged_in())
			{
				$username=$this->tank_auth->get_username();
				$role=$this->tank_auth->get_role($username);
				if (!($role=='ops' or $role=='admin'))
				{
					$response['is_success']=false;
					$response['errors'][]='Error 1104394: Incorrect role "'.$role.'" for media_targeting_tags';
				}
			}
			else
			{
				$response['is_success']=false;
				$response['errors'][]="Error 1104421: User not logged in";
			}
			return $response;
		}
		else
		{
			show_404();
		}
	}
	
	// see what user has searched in the text area on top to decide what mode to display the data in
	private function get_mode_from_data($id_all)
	{
		$mode="sites_only";
		
		$site_only_flag=true;
		$first_tag_type=-1;
		$diff_stereo_flag=false;
		$site_available_flag=false;
		$stereo_available_flag=false;
		
		$counter=0;
		foreach ($id_all as $id)
		{
			$tag_type=substr($id, 0, 1);
			
			if ($counter==0)
			{
				$first_tag_type=$tag_type;
			}
			if ($tag_type=="0")
			{
				$site_available_flag=true;
			}
			if ($tag_type=="1"||$tag_type=="2"||$tag_type=="4")
			{
				$site_only_flag=false;
				$stereo_available_flag=true;
				if ($counter>0&&$tag_type!=$first_tag_type)
				{
					$diff_stereo_flag=true;
				}
			}
			$counter++;
		}
		
		if ($site_only_flag)
			return "sites_only";
		else if ($site_available_flag&&$stereo_available_flag)
			return "sites_and_stereos";
		else if (!$diff_stereo_flag)
			return "same_stereos";
		else if ($diff_stereo_flag)
			return "diff_stereos";
	}
	
	// get data to show after search is done to display results on the todos page. this is a grid which is shown on screen in 2 modes
	public function get_data_grid()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$id_all=$this->input->post('id_all');
			
			if ($id_all)
			{
				$mode=$this->get_mode_from_data($id_all);
				
				$response["mode"]=$mode;
				$site_result_data;
				if ($mode=="sites_only"||$mode=="sites_and_stereos")
				{
					$site_result_data=$this->site_tag_admin_model->get_site_data_grid($id_all);
				}
				else if ($mode=="same_stereos")
				{
					$site_result_data=$this->site_tag_admin_model->get_tags_data_grid($id_all);
				}
				else if ($mode=="diff_stereos")
				{
					$site_result_data=$this->site_tag_admin_model->get_mixed_tags_data_grid($id_all);
				}
				
				if ($site_result_data)
				{
					$response['data']=$site_result_data;
				}
				else
				{
					$response['is_success']=false;
					$response['errors'][]='Mode C: Could not get any site that is tagged to this combination of tags';
				}
			}
			else
			{
				$response['is_success']=false;
				$response['errors'][]='Error 2294029: no site id provided for server request';
			}
		}
		
		echo json_encode($response);
	}
	
	// get list of todo names and the counts to display in top header
	public function pull_todo_sitelist()
	{
		$response=$this->verify_ajax_call();
		
		if ($response['is_success'])
		{
			$name=$this->input->post('name');
			$data=$this->site_tag_admin_model->pull_todo_sitelist($name);
			
			$response['data']=$data;
		}
		echo json_encode($response);
	}
	
	// type: type, limit: limit, offset : offset
	// get the sites for a given type to display in the table
	public function pull_site_data()
	{
		$response=$this->verify_ajax_call();
		
		if ($response['is_success'])
		{
			$type=$this->input->post('type');
			$start=$this->input->post('start');
			$limit=$this->input->post('limit');
			$search_text=$this->input->post('search_text');
			
			$data=$this->site_tag_admin_model->pull_site_data($type, $start, $limit, $search_text);
			
			$response['data']=$data;
		}
		echo json_encode($response);
	}
	
	// this function is called when site status is changed. this can go to pending, approved or rejected by calling a site url
	public function site_status_change()
	{
		$response=$this->verify_ajax_call();
		
		if ($response['is_success'])
		{
			$url=$this->input->post('url');
			$status=$this->input->post('status');
			$data=$this->site_tag_admin_model->site_status_change($url, $status);
			$response['data']=$data;
		}
		echo json_encode($response);
	}
	public function save_new_sites_bulk()
	{
		$response=$this->verify_ajax_call();
		
		if ($response['is_success'])
		{
			$sitenames=$this->input->post('sitenames');
			$tag_id=$this->input->post('tag_id');
			$tag_type=substr($tag_id, 0, 1);
			$tag_id1=substr($tag_id, 2);
			$data=$this->site_tag_admin_model->save_new_sites_bulk($sitenames, $tag_type, $tag_id1);
			$response['data']=$data;
			$response['tag_id']=$tag_id;
		}
		echo json_encode($response);
	}
	public function get_comscore_site_demo_data()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$url=$this->input->post('url');
			$data=$this->site_tag_admin_model->get_comscore_site_demo_data($url);
			$response['data']=$data;
		}
		echo json_encode($response);
	}
	public function site_records_to_pending_table($manual_date = null)
	{
		$this->site_tag_admin_model->site_records_to_pending_table($manual_date);
	}

	public function pull_industry_data()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$industry_id=$this->input->post('industry_id');
			$data=$this->site_tag_admin_model->pull_industry_data($industry_id);
			$response['data']=$data;
		}
		echo json_encode($response);
	}

	public function save_industry_data()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$id=$this->input->post('id');
			$industry_name=$this->input->post('industry_name');
			$industry_custom_name_f=$this->input->post('industry_custom_name_f');
			$iab_tags=$this->input->post('iab_tags');
			$user_id=$this->tank_auth->get_user_id();
			$data=$this->site_tag_admin_model->save_industry_data($id, $industry_name, $industry_custom_name_f, $iab_tags, $user_id);
			$response['data']=$data;
		}
		echo json_encode($response);
	}

	public function headers_in_rfps_load()
	{
		$response=$this->verify_ajax_call();
		if ($response['is_success'])
		{
			$id=$this->input->post('id');
			$headers_in_rfps_days_back=$this->input->post('headers_in_rfps_days_back');
			$data=$this->site_tag_admin_model->headers_in_rfps_load($headers_in_rfps_days_back);
			$response['data']=$data;
		}
		echo json_encode($response);
	}

	

}