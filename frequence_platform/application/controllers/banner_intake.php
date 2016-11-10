<?php

class Banner_intake extends CI_Controller
{
    
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->helper('url');
		$this->load->helper('mailgun');
		$this->load->helper('vl_ajax');
		$this->load->helper('select2_helper');		
		$this->load->model('banner_intake_model');
		$this->load->model('mpq_v2_model');
		$this->load->model('vl_auth_model');
		$this->load->model('cup_model');
		$this->load->library('pagination');
		$this->load->library('vl_aws_services');
	}
	
	public function index($banner_intake_id = null)
	{
		if ($this->vl_platform->has_permission_to_view_page_otherwise_redirect('banner_intake', '/creative_requests'))
		{
			$username = $this->tank_auth->get_username();
			$user_role = strtolower($this->tank_auth->get_role($username));

			//No access for business role.
			if ($user_role === 'business')
			{
				redirect('login');
				return;
			}

			$data['user_role'] = $user_role;

			if ($user_role === 'sales')
			{
				$data['invisible_columns'] = array('id', 'created', 'updated', 'updater', 'latest_version', 'internally_approved_updated_user', 'internally_approved_updated_timestamp', 'email_errors');
			}
			else if ($user_role !== 'admin')
			{			    
			    $data['invisible_columns'] = array('created');
			}

			$active_feature_button_id = 'banner_intake';
			$data['title'] = 'Adset Requests';

			$data['modal_open_id'] = null;
			if($banner_intake_id != null)
			{
				if($this->banner_intake_model->get_adset_request_by_id($banner_intake_id))
				{
					$data['modal_open_id'] = $banner_intake_id;
				}
			}

			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'banner_intake/banner_intake_all_adset_requests_body',
				'banner_intake/banner_intake_all_adset_requests_header',
				false,
				'banner_intake/banner_intake_all_adset_requests_js',
				false
			);
		}
	}

	public function ticket_preview($hashed_banner_intake_id = null)
	{

		if(!$this->tank_auth->is_logged_in())
		{
			$referrer = 'banner_intake/ticket_preview'.(empty($hashed_banner_intake_id) ? '' : '/'.$hashed_banner_intake_id);
			$this->session->set_userdata('referer',$referrer);
			redirect('login');
			return;
		}
		if(!empty($hashed_banner_intake_id))
		{
			$this->index(base64_decode($hashed_banner_intake_id));
		}
		else
		{
			$this->index(null);
		}

	}

	public function new_adset_request()
	{
		if ($this->vl_platform->has_permission_to_view_page_otherwise_redirect('banner_intake', '/creative_requests/new'))
		{
			$data = array();
			$data['source'] = isset($_GET['src']) ? $_GET['src'] : "";

			$data['mpq_id'] = $this->session->userdata('mpq_id');
			$data['product_preload'] = json_encode($this->session->userdata('product'));

			$user = $this->vl_auth_model->get_user_by_id($this->session->userdata('user_id'));

			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$user_role = strtolower($this->tank_auth->get_role($username));

			//No access for business role.
			if ($user_role === 'business')
			{
				redirect('login');
				return;
			}

			$form_data = $this->input->post();

			// Form Validation
			$this->load->helper(array('form'));
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('<span class="label-important">', '</span>');
			$this->form_validation->set_message('url_check', 'Not a valid url');
			$this->form_validation->set_rules('creative_name', 'Creative Name', 'min_length[1]');
			$this->form_validation->set_rules('advertiser_name', 'New Advertiser Name', 'min_length[1]');
			$this->form_validation->set_rules('source_table', 'Source Table', 'min_length[0]');
			
			//Advertiser
			$data['advertiser_id'] = $form_data['advertiser_id'];
			$data['advertiser_name'] = $form_data['advertiser_name'];
			$data['source_table'] = $form_data['source_table'];
			$data['creative_name'] = $form_data['creative_name'];
			
			/*if (isset($form_data['advertiser_id']) && $form_data['advertiser_id'] == 'unverified_advertiser_name')
			{
				$this->form_validation->set_rules('advertiser_name', 'New Advertiser Name', 'min_length[1]');
				$data['advertiser_selected']= "{'id':'unverified_advertiser_name','text':'*New*'}";
			}
			elseif ($form_data['advertiser_id'])
			{
				$adv_name = $this->banner_intake_model->get_advertiser_name_by_id($form_data['advertiser_id']);
				$form_data['advertiser_name'] = $adv_name;
				$data['advertiser_selected']= "{'id':'".$form_data['advertiser_id']."','text':'".$adv_name."'}";
			}*/

			//Custom Banner Design

			$creative_request_owner_name = $this->banner_intake_model->get_user_name_by_id($user->id);
			$data['creative_request_owner_selected']= "{'id':'".$user->id."','text':'".$creative_request_owner_name."'}";
			$product = $form_data['product'];
			$request_type = $form_data['request_type'];
			
			if ($product === 'Display' && isset($request_type) && $request_type === 'Custom Banner Design')
			{
				//Ticket Owner
				$creative_request_owner = $form_data['creative_request_owner_id'];
				if ($creative_request_owner)
				{
					$creative_request_owner_name = $this->banner_intake_model->get_user_name_by_id($creative_request_owner);
					$data['creative_request_owner_selected']= "{'id':'".$creative_request_owner."','text':'".$creative_request_owner_name."'}";
				}
				
				//CC on Ticket
				$cc_on_ticket = $form_data['cc_on_ticket'];
				if ($cc_on_ticket)
				{
					$cc_on_ticket_user_name = $this->banner_intake_model->get_user_name_by_id($cc_on_ticket);
					$data['cc_on_ticket_selected']= "{'id':'".$cc_on_ticket."','text':'".$cc_on_ticket_user_name."'}";
				}

				$this->form_validation->set_rules('advertiser_website', 'Advertiser Website', 'trim|prep_url|callback_url_check');
				$this->form_validation->set_rules('landing_page', 'Landing Page', 'trim|prep_url|callback_url_check');
				$this->form_validation->set_rules('advertiser_email', 'valid_email');
				$this->form_validation->set_rules('scenes[0]', 'First scene', 'min_length[1]');
				$this->form_validation->set_rules('cta', 'Call to Action', 'min_length[1]');
				$data['advertiser_website'] = $form_data['advertiser_website'];
				$data['landing_page'] = trim($form_data['landing_page']);
			
				if ($this->input->post('cta', true) == 'other')
				{
					$this->form_validation->set_rules('cta_other', 'Other CTA', '');
				}
			}
			elseif ($product === 'Display' && isset($request_type) && $request_type === 'Ad Tags')
			{
				$this->form_validation->set_rules('tag_320x50', 'Tag 320x50', '');
				$this->form_validation->set_rules('tag_728x90', 'Tag 728x90', '');
				$this->form_validation->set_rules('tag_160x600', 'Tag 160x600', '');
				$this->form_validation->set_rules('tag_336x280', 'Tag 336x280', '');
				$this->form_validation->set_rules('tag_300x250', 'Tag 300x250', '');
				$this->form_validation->set_rules('tag_custom', 'Tag Custom', '');
			}

			$this->form_validation->set_rules('creative_files', 'Creative Files', '');

			// Accordion fields
			$is_video = $this->input->post('is_video') ? 'required' : '';
			
			if ($this->input->post('is_video'))
			{
				$this->form_validation->set_rules('features_video_youtube_url', 'YouTube URL', 'callback_url_check|prep_url');
			}
			else
			{
				$this->form_validation->set_rules('features_video_youtube_url', 'YouTube URL', '');
			}
			
			$is_map = $this->input->post('is_map') ? 'required' : '';
			$is_social = $this->input->post('is_social') ? 'required' : '';
			$this->form_validation->set_rules('features_video_video_play', 'Video Play Trigger', $is_video);
			$this->form_validation->set_rules('features_video_mobile_clickthrough_to', 'Mobile Clickthrough Target', $is_video);			
			$this->form_validation->set_rules('features_map_locations', 'Map Locations', $is_map);
			$this->form_validation->set_rules('features_social_twitter_text', 'Twitter Text', $is_social);
			$this->form_validation->set_rules('features_social_email_subject', 'Email Subject', $is_social);
			$this->form_validation->set_rules('features_social_email_message', 'Email Message', $is_social);
			$this->form_validation->set_rules('features_social_linkedin_subject', 'LinkedIn Subject', $is_social);
			$this->form_validation->set_rules('features_social_linkedin_message', 'LinkedIn Message', $is_social);
			$this->form_validation->set_rules('other_comments', 'xss_clean');

			$data['user_role'] = $user_role;
			$data['users_via_partner_hierarchy'] = $this->banner_intake_model->get_users_via_partner_heirarchy_for_select2("", 0, 5, $user_id, $user_role);

			if ($this->form_validation->run() != FALSE)
			{
				$data['email'] = $user->email;
				$data['title'] = 'Adset Request';

				//video_url is the radio button and we don't want to capture it's value in DB.
				if (isset($form_data['video_url']))
				{
					unset($form_data['video_url']);
				}
				
				if ($product === 'Preroll')
				{
					unset($form_data['request_type']);
				}

				if(isset($form_data['io_redirect_id']))
				{
					$io_redirect_id = $form_data['io_redirect_id'];
					unset($form_data['io_redirect_id']);
				}
				
				$data['form_data'] = $this->process_form($form_data);

				if ($data['form_data']['is_success'])
				{
					$current_db_time = $this->banner_intake_model->get_db_current_time();
					$data['cur_time'] = $current_db_time ? $current_db_time[0]['time_now'] : new DateTime();
					$data['requested_time'] = $this->time_elapsed_string($data['form_data']['updated'],$data['cur_time'],2);

					//Create new adset and populate the id in adset_request table.
					$adset_group_name = $form_data['creative_name'].'('.date_format(new DateTime(),'m.d.Y.H.i.s').')';

					if (isset($form_data['advertiser_name']) && $form_data['advertiser_name'])
					{
						$adset_group_name = $form_data['advertiser_name'].':'.$adset_group_name;
					}
					else
					{
						$adset_group_name = $data['advertiser_name'].'_'.$adset_group_name;
					}

					//Process variations into handy array
					$variations = null;
					if(isset($form_data['has_variations']) && $form_data['has_variations'] == "on")
					{
						$variations = array();
						foreach($form_data['variation_names'] AS $idx => $variation_name)
						{
							if($variation_name != "" && $form_data['variation_details'][$idx] != "")
							{
								$variations[] = array('name' => $variation_name, 'description' => $form_data['variation_details'][$idx]);
							}
						}						
					}

					$result = $this->insert_new_adset('none',$adset_group_name,$user_id,$product);
					$adset_id = $result['adset']['id'];
					$version_id = $result['version'];

					if ($adset_id && $data['form_data']['insert_id'])
					{
						$successful_update = $this->banner_intake_model->populate_adset_id_in_adset_request($data['form_data']['insert_id'], $adset_id);

						if ($successful_update)
						{
							$data['form_data']['adset_version_url'] = '/creative_uploader/'.$version_id;
						}
					}

					//Check Show for IO box if it's Upload Own
					if($request_type == 'Upload Own' || $request_type == "Ad Tags")
					{
						$this->cup_model->save_show_for_io_value_for_adset_version($version_id, 1);
					}

					$domain = $this->tank_auth->get_domain_without_cname();
					$cname = $this->banner_intake_model->get_banner_intake_owner_cname($data['form_data']['insert_id']);
					if($cname == false)
					{
						$cname = $this->get_banner_intake_requester_cname($data['form_data']['insert_id']);
						if($cname == false)
						{
							$cname = "secure";
						}
					}
					$data['base_url'] = 'http://'.$cname.'.'.$domain.'/';					
					$data['is_review'] = 1;
					if (!empty($io_redirect_id))
					{

						//Link Banner Intake to Insertion Order
						if($request_type == 'Upload Own' || $request_type == "Ad Tags" || $product === 'Preroll')
						{
							$did_link_banner_intake_to_io = $this->banner_intake_model->link_new_banner_intake_to_io_product(
								null,
								$version_id,
								$data['mpq_id'],
								$data['form_data']['product']
							);
						}
						else
						{
							$did_link_banner_intake_to_io = $this->banner_intake_model->link_new_banner_intake_to_io_product(
								$data['form_data']['insert_id'],
								null,
								$data['mpq_id'],
								$data['form_data']['product']
							);
						}
						$this->session->unset_userdata('mpq_id');
						$this->session->unset_userdata('product');

						$io_post = array(
							'mpq_id' => $data['mpq_id'],
							'submission_method' => 'edit',
							'source' => 'io'
						);
						$this->session->set_userdata('io_post_data', $io_post);
						$unique_display_id = $this->mpq_v2_model->get_unique_display_id_by_mpq_id($io_redirect_id);
						if ($unique_display_id)
						{
							redirect('io/'.$unique_display_id);
						} else {
							redirect('insertion_orders');
						}
					} else {
						$data['request_source'] = "new_adset_request";
						$this->vl_platform->show_views(
							$this,
							$data,
							'banner_intake',
							'banner_intake/banner_intake_success_html',
							'banner_intake/banner_intake_review_html_header',
							false,
							'banner_intake/banner_intake_review_js',
							false
						);
						return;
					}
				}
			}
			
			$data['email'] = $user->email;
			$data['user_id'] = $user->id;
			$data['title'] = 'Adset Request';

			// Preload data from insertion order
			$data['io_preload_data'] = json_encode(false);

			if ($data['mpq_id'])
			{
				$data['io_preload_data'] = json_encode($this->mpq_v2_model->get_io_data_for_adset_request($data['mpq_id']));
			}

			// Set to 0 if empty, otherwise count files/scenes posted
			$creative_files = $this->input->post('creative_files', true);
			$data['g_file_counter'] = empty($creative_files) ? 0 : count($creative_files);
			$scenes = $this->input->post('scenes', true);
			$data['g_scene_counter'] = empty($scenes) ? 1 : count($scenes);

			$active_feature_button_id = 'banner_intake';

			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'banner_intake/banner_intake_body',
				'banner_intake/banner_intake_html_header',
				false,
				'banner_intake/banner_intake_js',
				false,
				true,
				true
			);
		}
	}

	public function url_check($str)
	{
		$pattern = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
		if (!preg_match($pattern, $str))
		{
			return FALSE;
		}
		return TRUE;
	}

	public function process_form($form_data)
	{
		$errors = array();
		$form_id = null;

		$insert_result = $this->banner_intake_model->insert_adset_request_form($form_data);
		
		$cc = array();
		$reply_to_email = "";
		$cc_on_ticket = "";
		
		if(!empty($form_data['creative_request_owner_id']))
		{
			$creative_request_owner_email = $this->tank_auth->get_email_by_user_id($form_data['creative_request_owner_id']);
			$reply_to_email = $creative_request_owner_email;					
			$cc[] = $creative_request_owner_email;
		}

		if(!empty($form_data['cc_on_ticket']))
		{					
			$cc_on_ticket = $this->tank_auth->get_email_by_user_id($form_data['cc_on_ticket']);
			$cc[] = $cc_on_ticket;
		}
				
		if($insert_result['is_success'])
		{
			$form_data['is_success'] = true;
			$data['request_source'] = 'process_form';
			$form_data['insert_id'] = $insert_result['result'];
			$form_data['updated'] = $insert_result['updated'];
			
			if (isset($form_data['product']) && $form_data['product'] == 'Display' 
				&& isset($form_data['request_type']) && $form_data['request_type'] == 'Custom Banner Design')
			{
				$current_db_time = $this->banner_intake_model->get_db_current_time();
				$data['cur_time'] = $current_db_time ? $current_db_time[0]['time_now'] : new DateTime();
				$data['requested_time'] = $this->time_elapsed_string($form_data['updated'],$data['cur_time'],2);
				
				$mailgun_extras = array();
				$mailgun_extras['h:reply-to'] = $form_data['requester_email'];
				
				if ($reply_to_email != "")
				{
					$mailgun_extras['h:reply-to'] = $reply_to_email;
					$form_data['ticket_owner'] = $reply_to_email;
				}
				
				if (count($cc) > 0)
				{
					$mailgun_extras['cc'] = implode(', ', array_unique($cc));
				}
				
				if ($cc_on_ticket != "")
				{
					$form_data['cc_on_ticket'] = $cc_on_ticket;
				}
				
				$data['form_data'] = $form_data;
				$domain = $this->tank_auth->get_domain_without_cname();
				$cname = $this->banner_intake_model->get_banner_intake_owner_cname($form_data['insert_id']);
				if($cname == false)
				{
					$cname = $this->get_banner_intake_requester_cname($form_data['insert_id']);
					if($cname == false)
					{
						$cname = "secure";
					}
				}
				$data['base_url'] = 'http://'.$cname.'.'.$domain.'/';	
				$data['is_review'] = 0;
				$email_message_markup = $this->load->view('banner_intake/banner_intake_success_html', $data, true);
				$subject_string = 'Custom Banner Design: '.$form_data['advertiser_name'] . ' [Received]';

				if($this->session->userdata('is_demo_partner') != 1) 
				{
					//send email
					$result = mailgun(
						'no-reply@brandcdn.com',
						'helpdesk@brandcdn.com',
						$subject_string,
						$email_message_markup,
						"html",
						$mailgun_extras
					);
					if ($result !== true)
					{
						$this->banner_intake_model->insert_email_error_text($form_data['insert_id'], $result);
					}
				}
			}
			elseif (isset($form_data['product']) && $form_data['product'] == 'Display' 
				&& isset($form_data['request_type']) && $form_data['request_type'] == 'Upload Own')
			{
				$current_db_time = $this->banner_intake_model->get_db_current_time();
				$data['cur_time'] = $current_db_time ? $current_db_time[0]['time_now'] : new DateTime();
				$data['requested_time'] = $this->time_elapsed_string($form_data['updated'],$data['cur_time'],2);
				$mailgun_extras = array();
				$mailgun_extras['h:reply-to'] = 'no-reply@brandcdn.com';
				
				if ($reply_to_email != "")
				{
					$mailgun_extras['h:reply-to'] = $reply_to_email;
					$form_data['ticket_owner'] = $reply_to_email;
				}
				
				if (count($cc) > 0)
				{
					$mailgun_extras['cc'] = implode(', ', array_unique($cc));
				}
				
				if ($cc_on_ticket != "")
				{
					$form_data['cc_on_ticket'] = $cc_on_ticket;
				}
				
				$data['form_data'] = $form_data;
				$domain = $this->tank_auth->get_domain_without_cname();
				$cname = $this->banner_intake_model->get_banner_intake_owner_cname($form_data['insert_id']);
				if($cname == false)
				{
					$cname = $this->get_banner_intake_requester_cname($form_data['insert_id']);
					if($cname == false)
					{
						$cname = "secure";
					}
				}
				$data['base_url'] = 'http://'.$cname.'.'.$domain.'/';	
				$data['is_review'] = 0;				
				$email_message_markup = $this->load->view('banner_intake/banner_intake_success_html', $data, true);
				$subject_string = 'Uploaded Banner Design: '.$form_data['advertiser_name'] . ' [Received]';				
				
				if($this->session->userdata('is_demo_partner') != 1) 
				{
					//send email
					$result = mailgun(
						'no-reply@brandcdn.com',
						'helpdesk@brandcdn.com',
						$subject_string,
						$email_message_markup,
						"html",
						$mailgun_extras
					);
					if ($result !== true)
					{
						$this->banner_intake_model->insert_email_error_text($form_data['insert_id'], $result);
					}
				}
			}
		}
		else
		{
			$form_data['is_success'] = false;
		}
		return $form_data;
	}

	public function review($page_id = NULL)		
	{		
		if(!$this->tank_auth->is_logged_in())		
		{		
			$this->session->set_userdata('referer',(is_null($form_id) ? 'banner_intake/review/page/'.$page_id : 'banner_intake/review/'.$page_id));		
			redirect('login');		
			return;		
		}		
		
		if ($this->check_if_authorized(array('OPS','ADMIN','CREATIVE')))//no advertisers/sales reps allowed		
		{		
			redirect('creative_requests');
		}		
		else		
		{		
			redirect('director'); 		
		}		
	}

	public function review_single($form_id = null)
	{
		if(!$this->tank_auth->is_logged_in())
		{
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$user_role = strtolower($this->tank_auth->get_role($username));
		$data['user_role'] = $user_role;

		$form_id = $form_id ? $form_id : 1;

		$adset_request = $this->banner_intake_model->get_adset_request_by_id($form_id);

		$current_db_time = $this->banner_intake_model->get_db_current_time();
		$data['cur_time'] = $current_db_time ? $current_db_time[0]['time_now'] : new DateTime();
		$data['title'] = 'Adset Request '. $form_id;

		if($adset_request)
		{
			$data['is_valid_banner_id'] = true;
			$data['form_data'] = $adset_request[0];

			if (!empty($adset_request[0]['creative_files']))
			{
				$data['form_data']['creative_files'] = $this->vl_aws_services->grant_temporary_access(json_decode($adset_request[0]['creative_files'], true), '+10 minutes');
			}
			
			if (!empty($adset_request[0]['features_video_youtube_url']) || !empty($adset_request[0]['features_video_video_play']) || !empty($adset_request[0]['features_video_mobile_clickthrough_to']))
			{
				$data['form_data']['is_video'] = 'on';
			}
			
			if (!empty($adset_request[0]['features_map_locations']))
			{
				$data['form_data']['is_video'] = 'on';
			}
			
			if (!empty($adset_request[0]['features_social_twitter_text']) || !empty($adset_request[0]['features_social_email_subject']) || !empty($adset_request[0]['features_social_email_message'])
				|| !empty($adset_request[0]['features_social_linkedin_subject']) || !empty($adset_request[0]['features_social_linkedin_message']))
			{
				$data['form_data']['is_social'] = 'on';
			}
			
			$data['form_data']['scenes'] = json_decode($adset_request[0]['scenes']);
		}
		else
		{
			$data['is_valid_banner_id'] = false;
		}
		$data['is_review'] = 1;
		$active_feature_button_id = 'banner_intake';
		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'banner_intake/banner_intake_success_html',
			'banner_intake/banner_intake_all_adset_requests_header',
			false,
			'banner_intake/banner_intake_all_adset_requests_js',
			false
		);
	}

	public function creative_files_upload()
	{	
		$allowed_user_types = array('public');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types);

		if ($ajax_verify['is_success'])
		{
			$rand = substr(md5(microtime()),rand(0,26),7); // Create random string to avoid dupes

			$file_object = array(
				'name'	=>	$rand . url_title(strtolower($_FILES['files']['name'][0])),
				'tmp'	=>	$_FILES['files']['tmp_name'][0],
				'size'	=>	$_FILES['files']['size'][0],
				'type'	=>	$_FILES['files']['type'][0]
			);

			try
			{
				$result = $this->vl_aws_services->upload_file($file_object, S3_BANNER_INTAKE_BUCKET);
				$result['is_success'] = true;
				$result['errors'] = [];
			}
			catch (Exception $e)
			{
				$result = array('is_success' => false, 'errors' => array($e->getMessage()));
			}
			
			echo json_encode($result);
		}
	}

	public function creative_files_delete()
	{	
		$allowed_user_types = array('admin', 'ops', 'sales', 'creative', 'business');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types);

		if ($ajax_verify['is_success'])
		{
			$name = $this->input->post('name', true);
			$bucket = $this->input->post('bucket', true);

			if ($name && $bucket)
			{
				$result = $this->vl_aws_services->delete_file($this->input->post('name'), $this->input->post('bucket'));
				echo json_encode($result);
			}
			else
			{
				echo "Wrong post input";
			}
		}
		else {
			echo json_encode('Object deleted, but not from S3.');
		}
	}

	public function check_if_authorized($allowed_roles = array())
	{
		if(!$this->tank_auth->is_logged_in())
		{
			return false;
		}

		$user = $this->users->get_user_by_id($this->tank_auth->get_user_id(), TRUE);
		return in_array($user->role, $allowed_roles);
	}
	
	public function get_advertisers()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$user_role = strtolower($this->tank_auth->get_role($username));
			$user_is_super = $this->tank_auth->get_isGroupSuper($username);
			$calling_method = "get_advertisers_by_user_id_for_select2";
			$params_array = array($user_id, $user_is_super);
			
			if ($user_role == "admin" || $user_role == "ops" || $user_role == "creative" )
			{
				$calling_method = "get_advertisers_for_internal_users_for_select2";
				$params_array = array();
			}
			
			$advertiser_response = select2_helper($this->banner_intake_model, $calling_method, $post_array, $params_array);
			$advertiser_array = array();
			
			if ($post_array['page'] == 1)
			{
				$advertiser_array['result'][] = array(
					'id' => 'unverified_advertiser_name',
					'text' => '*New*'
					);
			}

			if (!empty($advertiser_response['results']) && !$advertiser_response['errors'])
			{
				$advertiser_array['more'] = $advertiser_response['more'];

				for ($i = 0; $i < $advertiser_response['real_count']; $i++)
				{
					$advertiser_array['result'][] = array(
						'id' => $advertiser_response['results'][$i]['id'],
						'text' => $advertiser_response['results'][$i]['Name']
					);
				}
			}
			else
			{
				$advertiser_array['errors'] = $advertiser_response['errors'];
				$advertiser_array['result'] = array();
			}

			echo json_encode($advertiser_array);
		}
		else
		{
			show_404();
		}
	}
	
	public function get_users_via_partner_hierarchy()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			return false;
		}
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_array = $this->input->post();
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$user_role = strtolower($this->tank_auth->get_role($username));			
			$params_array = array($user_id, $user_role);
			
			$users_response = select2_helper($this->banner_intake_model, "get_users_via_partner_heirarchy_for_select2", $post_array, $params_array);
			
			$users_array = array();

			if (!empty($users_response['results']) && !$users_response['errors'])
			{
				if (!empty($post_array['empty_option_required']))
				{
					$users_array['result'][] = array('id' => '0','text' => 'Select One');
				}
				
				$users_array['more'] = $users_response['more'];

				for ($i = 0; $i < $users_response['real_count']; $i++)
				{
					$users_array['result'][] = array(
						'id' => $users_response['results'][$i]['id'],
						'text' => $users_response['results'][$i]['name'],
						'email' => $users_response['results'][$i]['email']
					);
				}
			}
			else
			{
				$users_array['errors'] = $users_response['errors'];
				$users_array['result'] = array();
			}

			echo json_encode($users_array);
		}
		else
		{
			show_404();
		}
	}
	
	private function insert_new_adset($campaign_id, $adset_name, $user_id, $product)
	{
		if ($campaign_id !== false && $adset_name !== false)
		{
			$show_for_io = 0;
			if ($product === 'Preroll')
			{
				$show_for_io = 1;
			}
			
			$response = $this->cup_model->insert_adset($adset_name, ($campaign_id  == "none" OR $campaign_id == "") ? null : $campaign_id, $user_id, $show_for_io);

			if ($response)
			{
				return $response;
			}
		}
		
		return false;
	}
	
	public function ajax_data_for_datatable($timezone_offset=0)
	{
		if (!$this->tank_auth->is_logged_in())
		{
			echo json_encode(array());
			return;
		}
		$username = $this->tank_auth->get_username();
		$user_role = strtolower($this->tank_auth->get_role($username));
		$user_id = $this->tank_auth->get_user_id();
		$user_partner_id = $this->tank_auth->get_partner_id($user_id);
		$user_is_super = $this->tank_auth->get_isGroupSuper($username);
		$descendent_partner_keys = "";
			
		if ($user_role === 'sales' || $user_role === 'business'  || $user_role === 'client' || $user_role === 'agency' )
		{
			$partners_array = $this->tank_auth->get_partner_hierarchy_by_sales_person($user_id,$user_is_super);
			if (isset($partners_array) && count($partners_array) > 0)
			{
				$descendent_partner_array = array();
				
				foreach($partners_array as $partner)
				{
					$descendent_partner_array[] = $partner["id"];
				}				
				if (count($descendent_partner_array) > 0)
				{
					$descendent_partner_keys = implode(",", $descendent_partner_array);
				}
			}
			else
			{
				echo json_encode(array());
				return;
			}			
		}
		$adset_requests_data['data'] = $this->banner_intake_model->get_all_adset_requests($timezone_offset, $user_role, $user_is_super, $user_id, $descendent_partner_keys);
		echo json_encode($adset_requests_data);
	}

	public function ajax_review_single($form_id = null)
	{
		if(!$this->tank_auth->is_logged_in())
		{
			return;
		}
		
		$username = $this->tank_auth->get_username();
		$user_role = strtolower($this->tank_auth->get_role($username));
		$data['user_role'] = $user_role;

		$form_id = $form_id ? $form_id : 1;

		$adset_request = $this->banner_intake_model->get_adset_request_by_id($form_id);
		
		$current_db_time = $this->banner_intake_model->get_db_current_time();
		$data['cur_time'] = $current_db_time ? $current_db_time[0]['time_now'] : new DateTime();
		$data['title'] = 'Adset Request '. $form_id;
		$data['request_source'] = 'ajax_review_single';
		if($user_role == "ops" || $user_role == "creative" || $user_role == "admin")
		{
			$data['version_ids'] = $this->banner_intake_model->get_banner_intake_related_versions($form_id);
		}
		$data['is_review'] = 1;
		if($adset_request)
		{
			$data['is_valid_banner_id'] = true;
			$data['form_data'] = $adset_request[0];
			
			if(!empty($adset_request[0]['creative_request_owner_id']))
			{
				$data['form_data']['ticket_owner'] = $this->tank_auth->get_email_by_user_id($adset_request[0]['creative_request_owner_id']);
			}

			if(!empty($adset_request[0]['cc_on_ticket']) && $adset_request[0]['cc_on_ticket'] != "0")
			{					
				$data['form_data']['cc_on_ticket'] = $this->tank_auth->get_email_by_user_id($adset_request[0]['cc_on_ticket']);
			}

			if (!empty($adset_request[0]['creative_files']))
			{
				$data['form_data']['creative_files'] = $this->vl_aws_services->grant_temporary_access(json_decode($adset_request[0]['creative_files'], true), '+10 minutes');
			}
			
			if (!empty($adset_request[0]['features_video_youtube_url']) || !empty($adset_request[0]['features_video_video_play']) || !empty($adset_request[0]['features_video_mobile_clickthrough_to']))
			{
				$data['form_data']['is_video'] = 'on';
			}
			
			if (!empty($adset_request[0]['features_map_locations']))
			{
				$data['form_data']['is_video'] = 'on';
			}
			
			if (!empty($adset_request[0]['features_social_twitter_text']) || !empty($adset_request[0]['features_social_email_subject']) || !empty($adset_request[0]['features_social_email_message'])
				|| !empty($adset_request[0]['features_social_linkedin_subject']) || !empty($adset_request[0]['features_social_linkedin_message']))
			{
				$data['form_data']['is_social'] = 'on';
			}
			
			$data['form_data']['scenes'] = json_decode($adset_request[0]['scenes']);
			if(!empty($adset_request[0]['variations_input_string']))
			{
				$data['form_data']['has_variations'] = 'on';
				$variations_data = json_decode($adset_request[0]['variations_input_string']);
				$variation_names = array();
				$variation_details = array();
				foreach($variations_data->variations as $variation)
				{
					$variation_names[] = $variation->name;
					$variation_details[] = $variation->description;
				}
				$data['form_data']['variation_spec'] = $variations_data->spec;
				$data['form_data']['variation_names'] = $variation_names;
				$data['form_data']['variation_details'] = $variation_details;
			}
		}
		else
		{
			$data['is_valid_banner_id'] = false;
		}

		$html = $this->load->view('banner_intake/banner_intake_success_html', $data, true);

		echo $html;
			
		
	}
	
	public function time_elapsed_string($datetime,$cur_time, $level = 7)
	{
		//$now = new DateTime;
		$now = new DateTime($cur_time);
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);

		$string = array(
			'y' => 'year',
			'm' => 'month',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ($string as $k => &$v)
		{
			if ($diff->$k)
			{
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			}
			else
			{
				unset($string[$k]);
			}
		}

		$string = array_slice($string, 0, $level);
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}
	
	public function get_creative_request_id_for_adset_version()
	{
		$result = array();
		$result['status'] = 'fail';
		
		if(!$this->tank_auth->is_logged_in())
		{
			$result['err_msg'] = 'Unauthorized user';
			echo json_encode($result);
		}
		
		$adset_version_id = $this->input->post('version_id');
		
		if (!isset($adset_version_id) || $adset_version_id == '')
		{
			$result['err_msg'] = 'Invalid adset version id';
			echo json_encode($result);
		}

		$adset_request_id = $this->banner_intake_model->get_adset_request_id_by_adset_version_id($adset_version_id);
		
		if ($adset_request_id)
		{
			$result['status'] = 'success';
			$result['adset_request_id'] = $adset_request_id;
		}
		else
		{
			$result['err_msg'] = 'Adset request id not found';
		}
		
		echo json_encode($result);
	}

	public function unset_preload_io()
	{
		$this->session->unset_userdata('mpq_id');
		$this->session->unset_userdata('product');
		echo json_encode(array());
	}

}
?>
