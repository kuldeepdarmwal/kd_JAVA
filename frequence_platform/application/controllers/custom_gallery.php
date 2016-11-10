<?php

class Custom_gallery extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->helper('url');
		$this->load->model('custom_gallery_model');
		$this->load->model('tank_auth/users');
	}

	public function build($preset_id = null)
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer',(is_null($preset_id) ? 'custom_gallery/build' : 'custom_gallery/build/'.$preset_id));
			redirect('login');
			return;
		}

		$redirect_string = $this->vl_platform->get_access_permission_redirect('custom_gallery');
		if($redirect_string == 'login')
		{
			$this->session->set_userdata('referer', 'custom_gallery');
			redirect($redirect_string);
		}
		else if($redirect_string == '')
		{
			$is_logged_in = true;
			$user_name = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($user_name);
		}
		else
		{
			redirect($redirect_string);
		}

		$user = $this->users->get_user_by_id($this->tank_auth->get_user_id(), TRUE);//brings back every column in user table in an object
		$data['def_editor'] = json_encode(array('id'=>$user->id,'text'=>$user->firstname." ".$user->lastname));

		if ($user->role == 'OPS' or $user->role == 'ADMIN' or $user->role == 'SALES')//no advertisers allowed
		{
			//before we get available gallery owners, we'll need to know the cname we're on
			//because the way the adset picker works on the gallery builder page is based on cname
			//we wouldn't want a vl ops person to build a partner a gallery of adsets that the partner doesn't have access to
			$cname_partner_details = $this->tank_auth->get_partner_info_by_sub_domain();//funtion returns false if not on a cname
			$cname_derived_partner_id = ($cname_partner_details ? $cname_partner_details['id'] : 1); 

			$user_list  = $this->custom_gallery_model->get_list_of_editors($user);//returns a list of id's and text for select2 dropdown
			$data['user_list'] = json_encode($user_list);//this is for the save as dropdown

			//based on the user list let's do a quick check to see if these users have any galleries
			//if there are no galleries, the open exisitng button will be disabled out on the builder page
			$data['do_galleries_exist'] = json_encode($this->custom_gallery_model->get_galleries_from_user_list($user_list) ? true : false);//json encode so js can use it

			$data['can_user_see_editor_dropdown'] = ($user->role == 'OPS' || $user->role == 'ADMIN' || ($user->role == 'SALES'));

			$data['title'] = 'Gallery Builder';
			$active_feature_button_id = 'custom_gallery';

			$data['preset'] = $preset_id;
			//get data for given preset
			if(!is_null($preset_id))
			{
				//determine if this galleries owner is an allowed editor for this user
				$the_active_gallery = $this->custom_gallery_model->get_live_gallery_details_by_id($preset_id);
				$data['the_active_gallery'] = json_encode(null);
				$data['live_gallery_entry_exists'] = json_encode(false);
				if($the_active_gallery)
				{
					//check to see if the gallery owner is an allowed editor for this uder
					if(in_array(array('id'=>$the_active_gallery[0]['u'],'text'=>$the_active_gallery[0]['u_name']),$user_list))
					{
						$data['live_gallery_entry_exists'] = json_encode( true );
						$data['the_active_gallery'] = json_encode($the_active_gallery[0]);
					}
				}
				
			}
			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'custom_gallery/build_body_view',
				'custom_gallery/build_header_view',
				false,
				'custom_gallery/build_js_view',
				false
			);
		}
		else
		{
			redirect('director'); 
		}
		
		
		
	}

	public function broadcasted_adset_feed(){

		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('q','page_limit','page');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']

		$adsets_array = array('result' => array(), 'more' => false);


		if($ajax_verify['is_success'] )
		{
			$raw_term = $ajax_verify['post']['q'];
			$page_limit = $ajax_verify['post']['page_limit'];
			$page_number = $ajax_verify['post']['page'];
			
			///we'll use the cname to determine which adsets we can choose from to build the gallery this is for a vl ops/admin user who wants to build a gallery for a partner
			$partner_details = $this->tank_auth->get_partner_info_by_sub_domain();//funtion returns false if not on a cname
			$user_id = $this->tank_auth->get_user_id();
			$partner_id = ($partner_details ? $partner_details['id'] : 1); 

			if($raw_term && is_numeric($page_limit) && is_numeric($page_number))
			{
				if($raw_term != "%")
				{
					$search_term = '%'.$raw_term.'%';
				}
				else
				{
					$search_term = $raw_term;
				}
				$mysql_page_number = ($page_number - 1) * $page_limit;
				$adsets_result = $this->custom_gallery_model->get_broadcasted_adsets_by_search_term($partner_id, $search_term, $mysql_page_number, ($page_limit + 1));
				if($adsets_result)
				{
					if(count($adsets_result) == $page_limit + 1)
					{
						$real_count = $page_limit;
						$adsets_array['more'] = true;
					}
					else
					{
						$real_count = count($adsets_result);
					}
					
					for($i = 0; $i < $real_count; $i++)
					{
						$adsets_array['result'][] = array(
							'id' => $adsets_result[$i]['adset_version_id'], 
							'thumb'=>$adsets_result[$i]['open_uri'],
							'features'=>$adsets_result[$i]['features'],
							'saved_adset_name'=>$adsets_result[$i]['friendly_adset_name'],
							'preview_link'=>'/crtv/get_gallery_adset/'.base64_encode(base64_encode(base64_encode(intval($adsets_result[$i]['adset_version_id'])))));
					}
				}
			}
		}
		echo json_encode($adsets_array);
	}

	public function save_new_gallery()
	{
		$is_success = false;
		$alert_type = null;
		$errors = array();
		$message = null;
		$saved_g_id = null;
		$saved_g_name = null;
		$u = null;
		$u_name = null;
		$slug = null;
		$is_tracked = null;

		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('g_name','adsets','is_tracked','u_id');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			
			$gallery_name = $ajax_verify['post']['g_name'];
			$adsets = $ajax_verify['post']['adsets'];
			$is_tracked = $ajax_verify['post']['is_tracked'];
			$owner_id = $ajax_verify['post']['u_id'];

			$save_new_gallery_result = $this->custom_gallery_model->insert_new_gallery($gallery_name,$adsets,$owner_id,$is_tracked);

			if($save_new_gallery_result['is_success'] == true)
			{
				$is_success = true;
				if($save_new_gallery_result['is_alert'])
				{
					$alert_type = $save_new_gallery_result['message'];
				}
				else
				{
					$message = 'new gallery inserted';
					$saved_g_id = $save_new_gallery_result['gallery']['id'];
					$saved_g_name = $save_new_gallery_result['gallery']['text'];
					$u = $save_new_gallery_result['gallery']['u'];
					$u_name = $save_new_gallery_result['gallery']['u_name'];
					$slug = $save_new_gallery_result['gallery']['slug'];
					$is_tracked = $save_new_gallery_result['gallery']['is_tracked'];
				}
			}
			else
			{
				$message = $save_new_gallery_result['message'];
			}
		}
		else
		{
			$errors = array_merge($errors,$ajax_verify['errors']);
		}
		echo json_encode(
			array('is_success'=>$is_success,
				'alert_type'=>$alert_type,
				'errors'=>$errors,
				'message' => $message,
				'saved_g_id' => $saved_g_id,
				'saved_g_name' => $saved_g_name,
				'u' => $u,
				'u_name'=>$u_name,
				'slug' => $slug,
				'is_tracked' => $is_tracked
				)
			);



	}

	public function existing_galleries_feed()
	{
		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('q','page_limit','page');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']

		$galleries_array = array('result' => array(), 'more' => false);
		if($ajax_verify['is_success'])
		{
			$raw_term = $ajax_verify['post']['q'];
			$page_limit = $ajax_verify['post']['page_limit'];
			$page_number = $ajax_verify['post']['page'];
			if($raw_term && is_numeric($page_limit) && is_numeric($page_number))
			{
				if($raw_term != "%")
				{
					$search_term = '%'.$raw_term.'%';
				}
				else
				{
					$search_term = $raw_term;
				}
				$mysql_page_number = ($page_number - 1) * $page_limit;
				

				$user = $this->users->get_user_by_id($this->tank_auth->get_user_id(), TRUE);//TRUE means looking for activated user id
				$cname_partner_details = $this->tank_auth->get_partner_info_by_sub_domain();//funtion returns false if not on a cname
				$cname_derived_partner_id = ($cname_partner_details ? $cname_partner_details['id'] : 1); 

				$galleries_result = $this->custom_gallery_model->get_galleries_by_search_term($search_term, $mysql_page_number, ($page_limit + 1), $user);
				
				if(count($galleries_result) == $page_limit +1)
				{
					$real_count = $page_limit;
					$galleries_array['more'] = true;
				}
				else
				{
					$real_count = count($galleries_result);
				}
				
				for($i = 0; $i < $real_count; $i++)
				{
					$galleries_array['result'][] = array(
						'id' => $galleries_result[$i]['id'], 
						'text' => $galleries_result[$i]['friendly_name'],
						'slug'=>$galleries_result[$i]['slug'],
						'is_tracked'=>$galleries_result[$i]['is_tracked'],
						'u'=>$galleries_result[$i]['u'],
						'u_name'=>$galleries_result[$i]['u_name']
						);
				}
			}
		}
		echo json_encode($galleries_array);
	}

	public function save_existing_gallery()
	{
		$is_success = false;
		$errors = array();

		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('g_id','adsets','is_tracked','u_id');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']


		
		if($ajax_verify['is_success'])
		{
			$gallery_id = $ajax_verify['post']['g_id'];
			$adsets = $ajax_verify['post']['adsets'];
			$is_tracked = $ajax_verify['post']['is_tracked'];
			$u_id = $ajax_verify['post']['u_id'];
			$save_existing_result = $this->custom_gallery_model->update_custom_gallery($gallery_id,$adsets,$is_tracked, $u_id);
			if($save_existing_result['is_success'])
			{
				$is_success=true;
			}
			else
			{
				$errors[] = 'couldn\'t update existing gallery';
			}
		}
		else
		{
			$errors = array_merge($errors,$ajax_verify['errors']);
		}
		echo json_encode(array('is_success'=>$is_success,'errors'=>$errors));


	}

	public function get_gallery()
	{
		
		$is_success = false;
		$errors = array();
		$alert_type = '';
		$json_adset_string = false;

		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('id');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']

		$gallery_id = $ajax_verify['post']['id'];

		if($ajax_verify['is_success'])
		{
			//first determine if the gallery has been archived or even exists
			$gallery_details = $this->custom_gallery_model->get_live_gallery_details_by_id($gallery_id);
			if($gallery_details)//if we found an unarchived gallery entry - try to get the adsets
			{
				$cname_partner_details = $this->tank_auth->get_partner_info_by_sub_domain();//function returns false if not on a cname
				$cname_derived_partner_id = ($cname_partner_details ? $cname_partner_details['id'] : 1); 
				$json_adset_string = $this->custom_gallery_model->get_gallery_by_id($gallery_id,$cname_derived_partner_id);

				if($json_adset_string)//if we got a json adset string back - success
				{
					$is_success = true;
				}
				else //if the json adset string is false - we couldn't find any allowed adsets
				{
					$alert_type = "NO_ADSETS";
					$is_success = true;
				}
			}
			else//gallery details returns false if we couldn't find an unarchived row
			{
				$is_success = true;
				$alert_type = "NO_GALLERY";
				$errors[] = "it looks like the selected gallery has been archived or is expired";
			}
		}
		else
		{
			$errors = array_merge($errors,$ajax_verify['errors']);
		}
		echo json_encode(array('is_success'=>$is_success,'json_adset_string'=>$json_adset_string,'errors'=>$errors,'alert_type'=>$alert_type));
	}

	public function view_custom_gallery($user_id, $slug)
	{
		
		$data['title'] = 'Creative Samples';
		$active_feature_button_id = 'custom_gallery';
		
		$gallery_data = $this->custom_gallery_model->get_custom_gallery_data($user_id, $slug);

		if($gallery_data)//a live gallery exists if we can find the row
		{
			//now let's get the individual tiles that are allowed for this partner
			$cname_partner_details = $this->tank_auth->get_partner_info_by_sub_domain();//funtion returns false if not on a cname
			$cname_derived_partner_id = ($cname_partner_details ? $cname_partner_details['id'] : 1); 
			$tile_result = $this->custom_gallery_model->get_gallery_by_id($gallery_data[0]['id'],$cname_derived_partner_id);

			if($tile_result)
			{
				$data['tiles_blob'] = json_encode($tile_result);//this is for the ajax load
				$this->vl_platform->show_views(
					$this,
					$data,
					$active_feature_button_id,
					'gallery_wow/custom_gallery_body_view',
					'gallery_wow/custom_gallery_header_view',
					false,
					'gallery_wow/custom_gallery_js_view',
					false
				);
				if($gallery_data[0]['is_tracked']){
					$message = '<b>Congrats!</b><br><br> Your custom gallery: <b>'.$gallery_data[0]['friendly_name'].'</b> was viewed';
					$message .= "<br>";
					$message .= "<br>";
					$message .= "<br>";
					$message .= 'Happy Selling!<br>';
					$message .= 'Your friends at '.$gallery_data[0]['cname'].'.brandcdn.com';
					$message .= "<br><br>";
					$message .= '<small>PS: to disable this gallery and/or edit notifications please navigate to <a href="//'.$gallery_data[0]['cname'].'.brandcdn.com/custom_gallery/build/'.$gallery_data[0]['id'].'">the custom gallery builder</a></small>';
					$this->custom_gallery_model->mailgun('Gallery Notice <noreply@'.$gallery_data[0]['cname'].'.brandcdn.com>', 
															$gallery_data[0]['email'], 
															$gallery_data[0]['friendly_name'].' was viewed', 
															$message);
				}
			}
			else
			{
				$this->vl_platform->show_views(
					$this,
					$data,
					$active_feature_button_id,
					'gallery_wow/custom_gallery_empty_body_view',
					false,
					false,
					false,
					false
				);
			}

		}
		else
		{
			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'gallery_wow/custom_gallery_empty_body_view',
				false,
				false,
				false,
				false
			);
		}

	}


	public function delete_gallery()
	{
		$is_success = false;
		$errors = array();
		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('id');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			$gallery_id = $ajax_verify['post']['id'];
			$result = $this->custom_gallery_model->archive_gallery_by_id($gallery_id);
			if($result)
			{
				$is_success = true;
			}
			else
			{
				$errors[] = "the delete result came back false";
			}
		}
		else
		{
			$errors = array_merge($errors,$ajax_verify['errors']);
		}
		echo json_encode(array('is_success'=>$is_success,'errors'=>$errors));
	}

	public function update_tracking()
	{
		$is_success = false;
		$errors = array();
		$allowed_user_types = array('admin','ops','sales');
		$required_post_variables = array('id','is_tracked');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		if($ajax_verify['is_success'])
		{
			$gallery_id = $ajax_verify['post']['id'];
			$is_tracked = $ajax_verify['post']['is_tracked'];
			$result = $this->custom_gallery_model->update_gallery_tracking($gallery_id,$is_tracked);
			if($result)
			{
				$is_success = true;
			}
			else
			{
				$errors[] = "the update gallery tracking result came back false";
			}
		}
		else
		{
			$errors = array_merge($errors,$ajax_verify['errors']);
		}
		echo json_encode(array('is_success'=>$is_success,'errors'=>$errors));
	}

	private function verify_ajax_call($allowed_roles,$post_variables = array())
	//private function verify_ajax_call($allowed_roles)
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$response = array('is_success' => true, 'errors' => array());
			if($this->tank_auth->is_logged_in())
			{
				$username = $this->tank_auth->get_username();
				$role = $this->tank_auth->get_role($username);
				if(!in_array($role,$allowed_roles))
				{
					$response['is_success'] = false;
					$response['errors'][] = $role.' not authorized';
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = "public not authorized";
			}

			//test each required post variable - if doesn't exist - ajax fail
			foreach($post_variables as $post_variable)
			{
				$response['post'][$post_variable] = $this->input->post($post_variable);
				if($response['post'][$post_variable] === false)
				{
					$response['errors'][] = 'post variable: `'.$post_variable.'` not found';
					$response['is_success'] = false;
				}
			}
			return $response;
		}
		else
		{
			show_404();
		}
	}





}

?>