<?php

class Tag extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('tag_model');
		$this->load->model('tradedesk_model');		
		$this->load->model('campaign_health_model');
		$this->load->model('publisher_model');
	 	$this->load->helper('string'); 		
		$this->load->helper('url');
		$this->load->helper('form');
		$this->load->helper('string'); 
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->helper('file');
		$this->load->helper('vl_ajax');
		$this->load->helper('select2_helper');
		$this->load->helper('tag_helper');
	}

	public function active_inactive_tags()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		ini_set('memory_limit', '1024M');
		$data['tags'] = $this->tag_model->get_active_inactive_tags();
		$this->load->view('tag/active_inactive_tags_table_view',$data);
	}

	public function index(){
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','tag');
			redirect('login');
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'ops' or $role == 'admin')
		{
			$data['username']		= $username;
			$data['firstname']	= $this->tank_auth->get_firstname($data['username']);
			$data['lastname']		= $this->tank_auth->get_lastname($data['username']);
			$data['user_id']		= $this->tank_auth->get_user_id();
			//for dropdown
			//$data['advertisers'] = $this->tank_auth->get_businesses();

			$data['vl_campaigns'] = $this->publisher_model->get_all_active_campaigns();

			$data['active_menu_item'] = "tags_menu";//the view will make this menu item active
			$data['title'] = "AL4k [Tags]";
			$this->load->view('ad_linker/header',$data);
			$this->load->view('tag/tag_home_body_view',$data);
		}else
		{	
			redirect('director'); 
		}
	} //index()


	public function getlink(){
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}

		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		$data['user_id'] = $this->tank_auth->get_user_id();

		if ($role == 'ops' or $role == 'admin'){
			$data['tags'] = $this->tag_model->get_tag_by_tag_id($this->uri->segment(3), $this->uri->segment(4));  
			$this->load->view('tag/tag_copy_view', $data);
		}
		else
		{
			echo 'you are not authorized for some reason';
			return;
		}
	} //getlink()	
	
	public function update()
	{
		$post_array = $this->input->post();
		
		$tag_file_id = isset($post_array["tag_file_id"]) ? $post_array["tag_file_id"] : '';
		$adv_id = isset($post_array["adv_id"]) ? $post_array["adv_id"] : '';
		$tag_type = isset($post_array["tag_type"]) ? $post_array["tag_type"] : '';
		$tag_code = isset($post_array["newTag"]) ? trim($post_array["newTag"]) : '';
		$user_name = isset($post_array["username"]) ? trim($post_array["username"]) : '';
		$tag_id = isset($post_array["tagID"]) ? trim($post_array["tagID"]) : '';

		//If file id is not passed, return. 
		if (!is_numeric($tag_file_id))
		{
			return;
		}
		
		$tag_result = $this->tag_model->get_tag_by_tag_id($tag_id, $adv_id);
		$old_tag_file_name = $tag_result[0]['name'];
		
		//Update tag.			
		$update_array = array(
					$tag_code,
					$user_name,
					$tag_file_id,
					$tag_type,
					$tag_id
				);	
		
		$update_result = $this->tag_model->update_tag($update_array);
		
		if ($update_result['status'] === 'success')
		{
			$data['tag_data'] = $this->tag_model->get_tag_by_tag_id($tag_id, $adv_id);
			$tag_file_creation_result = create_tracking_tag_file($data['tag_data'][0]['name'],$data['tag_data'][0]['tag_file_id'],true,$data['tag_data'][0]['tag_type']);
			
			if (!$tag_file_creation_result || $tag_file_creation_result === 'FAILED')
			{
				echo "Error : Not able to create tag file";
				return;
			}
			
			//If file Id changed for tag, rewrite the old file as it should not contain this tag now.
			if ($old_tag_file_name !== $data['tag_data'][0]['name']){
				$tag_file_creation_result = create_tracking_tag_file($old_tag_file_name,$data['tag_data'][0]['tag_file_id'],true,$data['tag_data'][0]['tag_type']);
				if (!$tag_file_creation_result || $tag_file_creation_result === 'FAILED')
				{
					echo "Error : Not able to overwrite tag file";
					return;
				}
			}
			$this->load->view('tag/tags_table_row_view', $data);
		}
		else
		{
			echo "Error : ".$update_result['error_message'];
		}
	}

	public function update_form(){ 
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{
			$data['username'] = $this->tank_auth->get_username();
			$tag_id = $this->uri->segment(3);
			$adv_id = $this->uri->segment(4);
			
			//$data['advertisers'] = $this->tank_auth->get_businesses();
			//$data['vl_campaigns'] = $this->publisher_model->get_all_active_campaigns();                                            
			$data['tags'] = $this->tag_model->get_tag_by_tag_id($tag_id,$adv_id);
			$this->load->view('tag/tag_update_form', $data);
  	   	} else 
  	   	{
			echo 'you are not authorized for some reason';
			return;
		} // role
	} // update

	public function deactivate()
	{
		$this->activate_deactivate_tag("deactivate");
        }

	public function activate()
	{
		$this->activate_deactivate_tag("activate");
	}
        
	public function activate_deactivate_tag($action)
	{
		if (!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}

		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);

		if ($role == 'ops' or $role == 'admin')
		{
			$tag_id = $this->uri->segment(3);
			$adv_id = $this->uri->segment(4);
			$insert_array = array("id" => $tag_id);

			if ($action === 'activate')
			{
				$result = $this->tag_model->activate_tag($insert_array, $adv_id);
				
				if ($result['status'] == 'success')
				{
					$affected_rows = 1;
				}
				else
				{
					$error_msg = 'FAILED'.$result['error_message'];
					echo $error_msg;
					return;
				}
			}
			else
			{
				$affected_rows = $this->tag_model->deactivate_tag($insert_array);
			}

			if ($affected_rows > 0)
			{
				$data['tag_data'] = $this->tag_model->get_tag_by_tag_id($tag_id,$adv_id);
				create_tracking_tag_file($data['tag_data'][0]['name'],$data['tag_data'][0]['tag_file_id'],true,$data['tag_data'][0]['tag_type']);
				$this->load->view('tag/tags_table_row_view', $data);
			}
			else
			{
				echo 'FAILED';
			}
		}
		else
		{
			echo 'you are not authorized for some reason';
		}
	}
        
	public function publish()
	{
		$err_count = 0;
		// Write all the files for ACTIVE
		$active_files = $this->tag_model->get_all_active_tag_files_excluding_ad_verify();
		
		foreach ($active_files as $tag_file)
		{
			$tag_file_creation_result = create_tracking_tag_file($tag_file['name'],$tag_file['id'],true);
			
			if (!isset($tag_file_creation_result) || !$tag_file_creation_result || $tag_file_creation_result === 'FAILED')
			{
				// Error count on writing		
				$err_count += 1;
			}
		}

		echo (($err_count > 0)? 'FAILED' : 'SUCCESS');
	}
	
	public function insert()
	{ 
		if (!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','tag');
			redirect('login');
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);

		if ($role == 'ops' or $role == 'admin')
		{
			$tag_file_id = $this->input->post("tag_file_id");
			$campaign_id = $this->input->post("campaign");
			
			$tag_type = $this->input->post('tag_type');
			$tag_code = trim($this->input->post("newTag"));
			$user_name = trim($this->input->post("username"));
			
			//If file id is not passed, return. 
			if (!isset($tag_file_id) || !is_numeric($tag_file_id))
			{
				return;
			}
			
			//Create tag.			
			$insert_array = array(
						$tag_code,
						$user_name,
						$tag_file_id,
						$tag_type
					);	
			$insert_result = $this->tag_model->insert_tag($insert_array);
			
			if ($insert_result['status'] === 'success')
			{
				//Update Campaigns table with the file id.
				if (isset($campaign_id) && is_numeric($campaign_id))
				{
					$affected_rows = $this->tag_model->create_tag_files_to_campaigns_entry($tag_file_id, $campaign_id);
					
					if ($affected_rows > 0)
					{
						$advertiser = $this->tag_model->get_advertiser_by_campaign_id($campaign_id);
						$data['tag_data'] = $this->tag_model->get_tag_by_tag_id($insert_result['id'],$advertiser['id']);
						create_tracking_tag_file($data['tag_data'][0]['name'],$data['tag_data'][0]['tag_file_id'],true,$data['tag_data'][0]['tag_type']);
						$this->load->view('tag/tags_table_row_view', $data);
					}
				}
			}
			else
			{
				echo "Error : ".$insert_result['error_message'];
			}
		}
		else
		{
			echo 'you are not authorized for some reason';
		} // role
	} // insert

	public function tag_search()
	{
		$allowed_user_types = array('admin','ops');
		$required_post_variables = array('s');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			$search_string = $ajax_verify['post']['s'];
			$data['tags'] = $this->tag_model->get_search_tags($search_string);
			$this->load->view('tag/search_tags_table_view',$data);
		}
		else
		{
			$error_string = '';
			foreach($ajax_verify['errors'] as $err)
			{
				$error_string .= $err.'<br>';
			}
			echo '	<div class="alert alert-error">
						<strong>Warning!</strong>
						'.$error_string.'
					</div>';
		}
	}

	public function download_tags($adv_id, $tag_file_id = -1, $tag_id = -1, $tag_type = -1, $source_table = null)
	{
		$tags_result = get_content_for_download_tags(null,$adv_id,$tag_file_id, $tag_id, $tag_type, $source_table);
		
		if (!$tags_result['status'])
		{
			echo $tags_result['err_msg'];
			return;
		}
		
		$this->load->view('campaigns_main/download_campaign_tags', $tags_result['data']); 
	}	
	
	public function get_select2_tracking_tag_file_names()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$source_table = isset($post_array['source_table']) ? $post_array['source_table'] : '';
			$advertiser_id = isset($post_array['advertiser_id']) ? $post_array['advertiser_id'] : -1;
			$campaign_id = isset($post_array['campaign_id']) ? $post_array['campaign_id'] : -1;
			$td_tag_type = isset($post_array['td_tag_type']) ? $post_array['td_tag_type'] : 0;
			$new_option = isset($post_array['new_option']) ? $post_array['new_option'] : 'yes';
			
			if ($campaign_id !== -1 && $advertiser_id === -1)
			{
				$advertiser = $this->tag_model->get_advertiser_by_campaign_id($campaign_id);
				
				if ($advertiser)
				{
					$advertiser_id = $advertiser["id"];
					$source_table = 'Advertisers';
				}
			}
			
			if ($advertiser_id === -1 || $source_table === '')
			{
				$tracking_tag_files_array['results'] = array();
				echo json_encode($tracking_tag_files_array);
				return;
			}
			
			$params_array = array($advertiser_id, $source_table, $td_tag_type);							
			$tracking_tag_files = select2_helper($this->tag_model, "get_all_tracking_tag_files_for_advertiser_for_search_term", $post_array, $params_array);
			
			if ($post_array['page'] == 1 && $new_option !== 'no')
			{
				$tracking_tag_files_array['results'][] = array(
					'id' => 'new_tracking_tag_file',
					'text' => '*New*'
				);
			}

			if (!empty($tracking_tag_files['results']) && !$tracking_tag_files['errors'])
			{
				$tracking_tag_files_array['more'] = $tracking_tag_files['more'];
				for ($i = 0; $i < $tracking_tag_files['real_count']; $i++)
				{
					$tracking_tag_files_array['results'][] = array(
						'id' => $tracking_tag_files['results'][$i]['id'],
						'text' => $tracking_tag_files['results'][$i]['tag_file_name']
					);
				}
			}			

			echo json_encode($tracking_tag_files_array);
		}
		else
		{
			show_404();
		}
	}
	
	public function download_tag_file($adv_name, $dir_name, $file_name_ext=null)
	{
	    if($file_name_ext == '')
	    {
		$file_name = $dir_name;		
	    }
	    else 
	    {
		$file_name = $dir_name."/".$file_name_ext;		
	    }
	    $extension = ".js";
	    if (strpos($file_name,".js") > 0)
	    {
		$extension = "";
	    }		
	    $download_filename = trim($adv_name."_TAGS.txt");
	    $tags_result['file_name'] = $file_name.$extension;
	    $tags_result['download_name'] = $download_filename;
	    $this->load->view('campaigns_main/download_campaign_tag_file', $tags_result);	
	}
	
	public function create_new_tracking_tag_file()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$tracking = new stdClass();
			$tracking->source_table = isset($post_array['source_table']) ? $post_array['source_table'] : '';
			$tracking->io_advertiser_id = isset($post_array['io_advertiser_id']) ? $post_array['io_advertiser_id'] : -1;
			$tracking->campaign_id = isset($post_array['campaign_id']) ? $post_array['campaign_id'] : -1;
			$tracking->tracking_tag_file = trim($post_array['tracking_tag_file_name']).".js";
			
			if ($tracking->campaign_id !== -1 && $tracking->io_advertiser_id === -1)
			{
				$advertiser = $this->tag_model->get_advertiser_by_campaign_id($tracking->campaign_id);
				
				if (!$advertiser)
				{
					return false;					
				}
				else
				{
					$tracking->io_advertiser_id = $advertiser['id'];
					$tracking->source_table = 'Advertisers';
				}
			}
			
			$result = array();
			$result['is_success'] = true;
			
			//Check if file already created for advertiser.
			$existing_tracking_tag_files = $this->tag_model->get_all_tag_files_for_advertiser($tracking->io_advertiser_id);
			if ($existing_tracking_tag_files)
			{
				$forward_slash_position = strpos($tracking->tracking_tag_file,"/");
				$tracking_tag_file_name = ($forward_slash_position > 0) ? substr($tracking->tracking_tag_file,$forward_slash_position+1) : $tracking->tracking_tag_file;
				$tracking_tag_file_name = str_replace(".js","",strtolower($tracking_tag_file_name));
				for ($i=0;$i<count($existing_tracking_tag_files);$i++)
				{
					$forward_slash_position = strpos($existing_tracking_tag_files[$i]['name'],"/");
					$existing_tracking_tag_file_name = ($forward_slash_position > 0) ? substr($existing_tracking_tag_files[$i]['name'],$forward_slash_position+1) : $existing_tracking_tag_files[$i]['name'];
					$existing_tracking_tag_file_name = str_replace(".js","",strtolower($existing_tracking_tag_file_name));
					if (strtolower($existing_tracking_tag_file_name) === strtolower($tracking_tag_file_name))
					{
						$result['is_success'] = false;
						$result['errors'] = 'File already present';
						break;
					}
				}
			}
			
			if ($result['is_success'])
			{
				$tracking_tag_file_id= $this->tag_model->save_tracking_tag_file($tracking);

				if (isset($tracking_tag_file_id) && $tracking_tag_file_id !== null)
				{				
					$result['id'] = $tracking_tag_file_id;
					$result['name'] = $tracking->tracking_tag_file;
					create_tracking_tag_file($tracking->tracking_tag_file,$tracking_tag_file_id);
				}
				else
				{
					$result['is_success'] = false;
					$result['errors'] = 'Not able to create tracking tag file';
				}
			}
			
			echo json_encode($result);
		}
		else
		{
			show_404();
		}
	}
	
	public function all_tags_for_advertiser_file(){ 
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{
			$tag_file_id = $this->input->post('tag_file_id');
			
			$data['tags'] = $this->tag_model->get_tags_by_file_id($tag_file_id);        
			$this->load->view('tag/tag_file_content', $data);
  	   	} else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
	
	public function update_tag_type_for_tag_id(){ 
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'ops' or $role == 'admin')
		{
			$post_array = $this->input->post();
			
			$adv_id = isset($post_array["adv_id"]) ? $post_array["adv_id"] : '';
			$tag_type_value = isset($post_array["tag_type"]) ? $post_array["tag_type"] : '';
			$tag_code = isset($post_array["tag_code"]) ? trim($post_array["tag_code"]) : '';
			$tag_id = isset($post_array["tag_id"]) ? trim($post_array["tag_id"]) : '';
			
			if($tag_id == '' || $adv_id == '')
			{
				$result['status'] = 'fail';
				echo json_encode($result);
				return;
			}
			
			$old_tag_values = $this->tag_model->get_tag_by_tag_id($tag_id,$adv_id);			
			$result = $this->tag_model->update_tag_type_and_tag_code($tag_type_value,$old_tag_values[0]['tag_type'],$tag_code,$tag_id);
			
			if ($result['status'] !== 'success')
			{
				$result['tag_code'] = $old_tag_values[0]['tag_code'];
				$result['tag_type'] = $old_tag_values[0]['tag_type'];	
			}
			
			echo json_encode($result);
  	   	}
		else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
	
	public function download_tag_file_content(){ 
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin')
		{
			$tag_file_id = $this->uri->segment(3);
			$tag_types = array();
			$tag_types["0"] = "Retargeting";
			$tag_types["1"] = "Ad Verify";
			$tag_types["2"] = "Conversion";
			$tag_types["3"] = "Custom";
			
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename='tag_codes_$tag_file_id.csv'");

			// create a file pointer connected to the output stream
			$output = fopen("php://output", "w");

			// output the column headings
			fputcsv($output, array('Tag ID','Tag Code','Tag Type Code','Tag Type Name','Date Entered'));
			
			$tags = $this->tag_model->get_tags_by_file_id($tag_file_id);
			
			
			foreach($tags as $tag_row)
			{
				if ($tag_row['isActive'] != 1)
				{
					continue;
				}
				
				$tag_info = array();
				$tag_info['tag_id'] = $tag_row['id'];
				$tag_info['tag_code'] = $tag_row['tag_code'];
				$tag_info['tag_type_code'] = $tag_row['tag_type'];
				$tag_info['tag_type_name'] = $tag_types[$tag_row['tag_type']];
				$tag_info['date_entered'] = $tag_row['date_entered'];
				
				fputcsv($output, $tag_info);		   
			}
  	   	} else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
	
	public function get_advertiser_directory_name(){ 
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'ops' or $role == 'admin' or $role == 'sales')
		{
			$post_array = $this->input->post();
			$campaign_id = isset($post_array["campaign_id"]) ? $post_array["campaign_id"] : null;						
			$adv_id = isset($post_array["adv_id"]) ? $post_array["adv_id"] : null;
			$source_table = isset($post_array["source_table"]) ? $post_array["source_table"] : null;
			
			$result = get_tag_file_directory_name($campaign_id,$adv_id,$source_table);
			echo json_encode($result);			
  	   	}
		else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
	
	public function is_tag_file_push_friendly_for_advertiser()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'ops' or $role == 'admin')
		{
			$result['status'] = 'fail';
			$campaign_id = $this->input->post("campaign_id");
			$tag_file_id = $this->input->post("tag_file_id");
			$tag_file_to_campaign = $this->tag_model->get_tag_files_to_campaigns_entry($tag_file_id, $campaign_id);
			
			if (!$tag_file_to_campaign || $tag_file_to_campaign['existing_tags_assigned'] != 1)
			{
				$advertiser = $this->tag_model->get_advertiser_by_campaign_id($campaign_id);
				$td_advertiser_id = $this->tradedesk_model->get_td_advertiser_id($advertiser["id"]);
				$push_friendly = $this->tradedesk_model->is_tag_file_push_friendly_for_advertiser($tag_file_id, $td_advertiser_id);

				if ($push_friendly)
				{
					$result['status'] = 'success';
					$result['rtg_count'] = $push_friendly['rtg'];
					$result['conversion_count'] = $push_friendly['conversion'];
				}
			}
			
			echo json_encode($result);
  	   	}
		else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
	
	public function assign_existing_tags_to_campaign()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'ops' or $role == 'admin')
		{
			$campaign_id = $this->input->post("campaign_id");
			$advertiser = $this->tag_model->get_advertiser_by_campaign_id($campaign_id);
			$td_advertiser_id = $this->tradedesk_model->get_td_advertiser_id($advertiser["id"]);
			$tag_file_id = $this->input->post("tag_file_id");
			$return['status'] = 'fail';
			
			$push_friendly = $this->tradedesk_model->is_tag_file_push_friendly_for_advertiser($tag_file_id, $td_advertiser_id);
			
			if ($push_friendly)
			{
				$ttd_adgroups = $this->tradedesk_model->get_all_ttd_adgroups_by_campaign($campaign_id);
				$ttd_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($campaign_id);
				
				/*$access_token = $this->tradedesk_model->get_access_token();
				$td_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($campaign_id);

				if ($push_friendly['rtg'] == 1)
				{
					//Use existing audience
					$applied_audience = $this->tradedesk_model->apply_audience_from_tag_file_to_campaign($access_token, $tag_file_id, $campaign_id);
					if($applied_audience == false)
					{
						$return['status'] = 'fail';
						$return['err_msg'] = "Failed to apply existing audience id to new adgroup";
						echo json_encode($return);
						return;						
					}
					$saved_rtg_tag = true;
				}				

				if ($push_friendly['conversion'] == 1)
				{
					//Use existing tag
					$applied_conversion_tag = $this->tradedesk_model->apply_conversion_tag_from_tag_file_to_campaign($access_token, $tag_file_id, $td_campaign_id);
					if($applied_conversion_tag == false)
					{
						$return['status'] = 'fail';
						$return['err_msg'] = "Failed to apply existing conversion_tag to new campaign";
						echo json_encode($return);
						return;						
					}
					$saved_conversion_tag = true;						
				}
				

				if(($push_friendly['rtg'] && !$saved_rtg_tag) || ($push_friendly['conversion'] && !$saved_conversion_tag))
				{
					$return['status'] = 'fail';
					$return['err_msg'] = "Error while saving tags";
					echo json_encode($return);
					return;					
				}*/
				
				$saved_tag_file_to_campaign = $this->tag_model->create_tag_files_to_campaigns_entry($tag_file_id, $campaign_id, 1);
				if (!$saved_tag_file_to_campaign)
				{
					$return['status'] = 'fail';
					$return['err_msg'] = "Error while saving tags to campaign";
					echo json_encode($return);
					return;
				}
				
				if (isset($ttd_adgroups) && count($ttd_adgroups) > 0)
				{
					$ttd_adgroup_ids = array();
					for ($i=0;$i < count($ttd_adgroups);$i++)
					{
						if ($ttd_adgroups[$i]['target_type'] == 'RTG' || $ttd_adgroups[$i]['target_type'] == 'RTG Pre-Roll')
						{
							$ttd_adgroup_ids[]=$ttd_adgroups[$i]['ID'];
						}
					}
					$return['ttd_adgroup_ids'] = $ttd_adgroup_ids;					
				}
				
				$return['ttd_campaign_id'] = $ttd_campaign_id;
				$return['status'] = 'success';				
			}
			
			echo json_encode($return);
  	   	}
		else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
	
	public function get_rtg_and_conversion_tag_info_from_ttd()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			echo 'you need to login again';
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		
		if ($role == 'ops' or $role == 'admin')
		{
			$campaign_id = $this->input->post("campaign_id");
			$return['status'] = 'fail';
			
			if ($campaign_id != null && $campaign_id != '')
			{
				$access_token = $this->tradedesk_model->get_access_token();
				$td_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($campaign_id);
				$existing_tags_info = $this->tradedesk_model->get_ttd_tag_info($access_token,$campaign_id,$td_campaign_id);
				
				if ($existing_tags_info)
				{
					$return['existing_tags_info'] = $existing_tags_info;
					$return['status'] = 'success';
				}
			}
			
			echo json_encode($return);
  	   	}
		else 
  	   	{
			echo 'you are not authorized';
			return;
		}
	}
}