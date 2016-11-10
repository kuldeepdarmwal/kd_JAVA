<?php
//session_start();
class Publisher  extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url','multi_upload'));
		$this->load->helper('ad_server_type');
		$this->load->helper('url');
		$this->load->library('tank_auth');
		$this->load->library('session');
		$this->load->model('dfa_model');
		$this->load->model('cdn_model');
		$this->load->model('cup_model');
		$this->load->model('al_model');
		$this->load->model('publisher_model');
		$this->load->library('ftp');
		$this->load->library('google_api');
	}
	
	public function index()
	{
		$this->role_check();
		$username = $this->tank_auth->get_username();
		$data['user_id']		= $this->tank_auth->get_user_id();
		$data['firstname']		  = $this->tank_auth->get_firstname($username);
		$data['lastname']		= $this->tank_auth->get_lastname($username);
		
		$data['active_menu_item'] = "publish_menu";//the view will make this menu item active
		$data['title'] = "AL4k [Publisher]";
		$this->load->view('ad_linker/header',$data);
		$this->load->view('ad_linker/body',$data);

		//$this->load->view('publisher/index',$data);
	}

	public function get_vl_campaign_dropdown()
	{	
		$data['vl_campaigns'] = $this->publisher_model->get_all_active_campaigns();
		$this->load->view('publisher/get_all_vl_campaigns_dropdown_view',$data);
	}
			
	private function role_check()
	{
			if (!$this->tank_auth->is_logged_in()) 
			{
				redirect('login');
			}
			$vl_username = $this->tank_auth->get_username();
			$vl_role = $this->tank_auth->get_role($vl_username);
			if(!($vl_role == 'admin' OR $vl_role == 'ops' OR $vl_role == 'creative'))
			{
					redirect('director');
			}
	}
		
		/*
	public function spit_out_tag($input_string)
	{
		$this->role_check();
		$this->dfa_model->load_dfa_token();
		$input_array = explode('|',urldecode($input_string));
		echo '<xmp>'.$this->get_tags($input_array[0],$input_array[1]).'</xmp>';
		echo '<br>'.$this->get_tags($input_array[0],$input_array[1]).'<br>';
	}	 
		*/
	
	//javaScriptTag => JavaScript/Standard Tag
	//iframeJavaScriptTag => iFrame/JavaScript Tag
	private function get_tags($placement_id, $campaign_id)
	{
		$authToken = $this->session->userdata('dfa_token');
		$applicationName = $this->session->userdata('dfa_app_name');
		$namespace = $this->session->userdata('dfa_namespace');
		$username = $this->session->userdata('dfa_username');
		$options = $this->session->userdata('dfa_options');
		$networkId = $this->session->userdata('dfa_network_id');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		$tags = $this->dfa_model->get_dfa_tags($this->dfa_model->get_placement_wsdl(),$options,$headers,$campaign_id,$placement_id);
				/* // TODO: can we delete this code, it appears to have no effect -scott
		$placement_details = $this->dfa_model->get_placement_details($this->get_placement_wsdl(),$options,$headers,$placement_id);
		$dimensions = $this->dfa_model->get_dimensions_array_from_size_id($this->get_size_wsdl(), $options,$headers,$placement_details->sizeId);
		$dimensions['size_id'] = $placement_details->sizeId;
		$ad_choices_tag = $this->load->view('dfa/ad_choices_tag',$dimensions,TRUE);
				*/
				return $tags->placementTagInfos[0]->iframeJavaScriptTag;
	}
	
		/*
	private function deprecated_file_check($blah = 'blah')
	{
				die('Error in: '.__FILE__.'::'.__FUNCTION__.'() line: '.__LINE__.' assumed function was dead');
		$this-> role_check();
		$authToken = $this->session->userdata('dfa_token');
		$applicationName = $this->session->userdata('dfa_app_name');
		$namespace = $this->session->userdata('dfa_namespace');
		$username = $this->session->userdata('dfa_username');
		$options = $this->session->userdata('dfa_options');
		$networkId = $this->session->userdata('dfa_network_id');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		$placement_site_id = $this->session->userdata('dfa_site_id');

		$full_filepath_directory = urldecode($_GET['asset_filepath']);
		$path_parts = pathinfo($full_filepath_directory);
		$vl_creative_type = urldecode($_GET['vl_creative_type']);
		$advertiser_id = $_GET['advertiser_id'];
		$campaign_id = $_GET['campaign_id'];
		$message = '';
		$vl_campaign_id = $_GET['vl_c_id'];

		

		//first: make sure this creative type is supported
		if ($success = $this->dfa_model->is_vl_creative_type_supported($vl_creative_type))
		{
			//$message = $vl_creative_type.' is supported';
			$file_name_check_result = $this->dfa_model->inspect_file_names($vl_creative_type, $full_filepath_directory);
			if($success = $file_name_check_result['success'])
			{
				$dfa_creative_object = $file_name_check_result['file_details'];
				foreach($dfa_creative_object as $dfa_size_id=>$value)
				{
					$creative_width = $dfa_creative_object[$dfa_size_id]['dimensions']['width'];
					$creative_height = $dfa_creative_object[$dfa_size_id]['dimensions']['height'];
					$size_string = $creative_width.'x'.$creative_height;
					$placement_type_id = 3;//3=>agency paid regular https://developers.google.com/doubleclick-advertisers/docs/creative_types
					$price_type_id = 1; //1 is CPM
					if($dfa_creative_object[$dfa_size_id]['is_there'])
					{
						foreach($value['dfa_backup_asset'] as $backup_filename=>$dfa_backup_asset_id)
						{
							$load_backup_asset_result = $this->dfa_model->create_dfa_asset($this->get_creative_wsdl(),$options,$headers,$advertiser_id,$this->dfa_model->get_first_file_match($path_parts['dirname'].'/*'.$backup_filename),FALSE);
							$dfa_creative_object[$dfa_size_id]['dfa_backup_asset'][$backup_filename]= $load_backup_asset_result->savedFilename;
							$create_dfa_creative_result = $this->dfa_model->upload_image_creative_to_dfa($this->get_creative_wsdl(), $options,$headers,$namespace,'backup_'.$size_string,$advertiser_id,$campaign_id,$dfa_creative_object[$dfa_size_id]['dfa_backup_asset'][$backup_filename],$dfa_size_id);
						}
						//load dfa assets
						if(!empty($value['dfa_assets']))
						{
							foreach($value['dfa_assets'] as $filename=>$dfa_asset_id)
							{
								$load_asset_result = $this->dfa_model->create_dfa_asset($this->get_creative_wsdl(),$options,$headers,$advertiser_id,$this->dfa_model->get_first_file_match($path_parts['dirname'].'/*'.$filename),$file_name_check_result['is_html_creative']);
								$dfa_creative_object[$dfa_size_id]['dfa_assets'][$filename]= $load_asset_result->savedFilename;  
							}
						}
						if(!empty($value['cdn_assets']))
						{
							foreach($value['cdn_assets'] as $filename=>$cdn_asset_link)
							{
								$long_filename = $this->dfa_model->get_first_file_match($path_parts['dirname'].'/*'.$filename);
								$actual_path_parts = pathinfo($long_filename);
								$friendly_name = $actual_path_parts['basename'];
								// cup_model->load_assets has changed. if this is ever uncommented, check this -CL
								$dfa_creative_object[$dfa_size_id]['cdn_assets'][$filename]= $this->cdn_model->load_asset($long_filename,$advertiser_id,$campaign_id,$friendly_name);
							}
						}

						$dfa_creative_object[$dfa_size_id]['dfa_creative_html'] = $this->dfa_model->get_dfa_html($vl_creative_type,$dfa_creative_object,$advertiser_id,$dfa_size_id,$creative_width,$creative_height,$size_string,$vl_campaign_id);

						$create_dfa_creative_result = $this->dfa_model->create_dfa_creative($this->get_creative_wsdl(), $options,$headers,$namespace,$vl_creative_type.'_'.$size_string,$advertiser_id,$campaign_id,$dfa_creative_object[$dfa_size_id]['dfa_assets'],$dfa_size_id,3,$dfa_creative_object[$dfa_size_id]['dfa_creative_html'],$creativeID = 0);
						$dfa_creative_object[$dfa_size_id]['creative_id'] = $create_dfa_creative_result->id;
						$message = $message.'CREATIVE: '.$create_dfa_creative_result->id.' was created.<br>';
						
						$new_placement_result = $this->dfa_model->insert_placement($this->get_placement_wsdl(), $options,$headers,$size_string,$campaign_id,$placement_type_id,$placement_site_id,$dfa_size_id,$price_type_id);
						$dfa_creative_object[$dfa_size_id]['placement_id'] = $new_placement_result->id;
						$message = $message.'PLACEMENT: '.$new_placement_result->id.' was created.<br>';
						$associate_result = $this->dfa_model->associate_creatives_to_placements($this->get_creative_wsdl(), $options,$headers,$create_dfa_creative_result->id,$new_placement_result->id);
						$message = $message.'AD: '.$associate_result[0]->adId.' was created. ';
						$message = $message.'<a href="/publisher/spit_out_tag/'.$new_placement_result->id.'|'.$campaign_id.'" target="_blank">'.$size_string.' tag -> </a><br><br>';
					}
				}
				$tag_options = $this->dfa_model->get_tag_options($this->get_placement_wsdl(), $options,$headers);
				
				$message = '<h1>Success!</h1>'.$message;
			}else
			{
				$message = 'missing some files<br>'.$message;
			}
		}else
		{
			$message = $vl_creative_type.' is not supported yet';
		}

		echo "{";
		echo				"success: '".$success."',\n";
		echo				"file_check_message: '" . $message . "'\n";
		echo "}";
		}	 
		*/
		
	public function multi_upload($blah = 'blaf')
	{  
		$this-> role_check();
		$data['file_name'] = isset($_GET['file_stub']) ? basename(stripslashes($_GET['file_stub'])) : null;
		$file_name = $data['file_name'];

		


		error_reporting(E_ALL | E_STRICT);
		$upload_handler = new UploadHandler();

		header('Pragma: no-cache');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Content-Disposition: inline; filename="files.json"');
		header('X-Content-Type-Options: nosniff');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
		header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

		switch ($_SERVER['REQUEST_METHOD']) 
		{
			case 'OPTIONS':
				break;
			case 'HEAD':
			case 'GET':
				$upload_handler->get();
				break;
			case 'POST': 
				if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') 
				{
					$upload_handler->delete($file_name);
				} else 
				{
					$upload_handler->post();
				}
				break;
			case 'DELETE':
				$upload_handler->delete($file_name);
				break;
			default:
				header('HTTP/1.1 405 Method Not Allowed');
		}
	}	 
  
	private function prettyPrint($array,$comment = "smile")
	{
		echo $comment.'<br>';
		print '<pre>'; print_r($array); print '</pre>';
	}
	
	

	public function get_campaigns_from_advertiser($advertiser_id)
	{
		$this->role_check();
		$this->google_api->dfa_initalize();
		$campaigns_result = $this->google_api->dfa_get_campaign_list_for_advertiser($advertiser_id);
		$data['result'] = $campaigns_result['dfa_result'];
	$this->load->view('dfa/get_campaigns_dropdown',$data);
	}
	
	

	public function get_all_advertisers()
	{
		$this->role_check();
		$this->google_api->dfa_initalize();
		////////GET ALL ADVERTSERS
		$get_advertisers_result = $this->google_api->dfa_get_advertiser_list();
		$data['dfa_advertiser_records'] = $get_advertisers_result['dfa_advertiser_records'];
		$this->load->view('dfa/get_advertisers_dropdown',$data);
	}
	
	public function placements_from_campaign($campaign_id)
	{
		$this->role_check();
		$authToken = $this->session->userdata('dfa_token');
		$applicationName = $this->session->userdata('dfa_app_name');
		$namespace = $this->session->userdata('dfa_namespace');
		$username = $this->session->userdata('dfa_username');
		$options = $this->session->userdata('dfa_options');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		$placement_result = $this->dfa_model->fetch_placement_ids_from_campaign_id($this->dfa_model->get_placement_wsdl(),$options,$headers,$campaign_id);
		$data['result'] = $placement_result['dfa_result'];
	$this->load->view('dfa/get_placements_dropdown',$data);
	}
	
	public function delete_creative($id)
	{
		$this-> role_check();
		$username = $this->session->userdata('dfa_username');
		$namespace = $this->session->userdata('dfa_namespace');
		$authToken = $this->session->userdata('dfa_token');
		$options = $this->session->userdata('dfa_options');
		$applicationName = $this->session->userdata('dfa_app_name');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		$result = $this->dfa_model->delete_dfa_creative($this->dfa_model->get_creative_wsdl(),$options,$headers,$id);
	}
	  
	public function delete_placement($id)
	{
		$this-> role_check();
		$username = $this->session->userdata('dfa_username');
		$namespace = $this->session->userdata('dfa_namespace');
		$authToken = $this->session->userdata('dfa_token');
		$options = $this->session->userdata('dfa_options');
		$applicationName = $this->session->userdata('dfa_app_name');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		$result = $this->dfa_model->delete_dfa_placement($this->dfa_model->get_placement_wsdl(),$options,$headers,$id);
	}
	  
	public function delete_campaign($id)
	{
		$this-> role_check();
		$username = $this->session->userdata('dfa_username');
		$namespace = $this->session->userdata('dfa_namespace');
		$authToken = $this->session->userdata('dfa_token');
		$options = $this->session->userdata('dfa_options');
		$applicationName = $this->session->userdata('dfa_app_name');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		$result = $this->dfa_model->delete_dfa_campaign($this->dfa_model->get_campaign_wsdl(),$options,$headers,$id);
	}

	public function upload_creative($blah = '')
	{
		$this-> role_check();
		$advertiser_id = $_GET['advertiser_id'];
		$asset_filename = urldecode($_GET['asset_filename']);
		$size_id = $_GET['size_id'];
		$campaign_id = $_GET['campaign_id'];

		$authToken = $this->session->userdata('dfa_token');
		$applicationName = $this->session->userdata('dfa_app_name');
		$namespace = $this->session->userdata('dfa_namespace');
		$username = $this->session->userdata('dfa_username');
		$options = $this->session->userdata('dfa_options');
		$headers = $this->dfa_model->build_security_header($username,$authToken,$namespace, $applicationName);
		
		$dimensions_result = $this->dfa_model->get_dimensions_from_size_id($this->dfa_model->get_size_wsdl(), $options,$headers,$size_id);
		$creative_name = $dimensions_result['dfa_result']->records[0]->width.'x'.$result->records[0]->height;
		$data['result'] = $this->dfa_model->upload_image_creative_to_dfa($this->dfa_model->get_creative_wsdl(), $options,$headers,$namespace,$creative_name,$advertiser_id,$campaign_id,$asset_filename,$size_id);
	}
	

	public function insert_new_campaign($blah = '')
	{
		$this-> role_check();
		$advertiser_id = $this->input->post('advertiser_id');
		$campaign_name = $this->input->post('campaign_name');
		$landing_page = trim($this->input->post('landing_page'));
		if($advertiser_id === false || $campaign_name === false || $landing_page === false)
		{
			$data['success'] = false;
			echo json_encode($data);
			return;
		}

		$landing_page = urldecode($landing_page);
		$this->google_api->dfa_initalize();

		$campaign_result = $this->google_api->dfa_insert_campaign($campaign_name, $landing_page, $advertiser_id);
		if($campaign_result['is_success'] == false)
		{
			$data['success'] = false;
			echo json_encode($data);
			return;
		}
		$data['campaign_name'] = $campaign_name;
		$data['campaign_id'] = $campaign_result['dfa_result']->id;
		$data['success'] = true;
		echo json_encode($data);
	}
	
	public function insert_new_advertiser($blah = '')
	{
		$this->role_check();
		$advertiser_name = $this->input->post('adv_name');
		if($advertiser_name == false)
		{
			$data['success'] = false;
			echo json_encode($data);
			return;
		}
		$this->google_api->dfa_initalize();
		$advertiser_result = $this->google_api->dfa_insert_advertiser($advertiser_name);
		if($advertiser_result['is_success'] == false)
		{
			$data['success'] = false;
			echo json_encode($data);
			return;			
		}
		$data['advertiser_name'] = $advertiser_result['dfa_result']->name;
		$data['advertiser_id'] = $advertiser_result['dfa_result']->id;
		$data['success'] = true;
		echo json_encode($data);
		//$this->load->view('dfa/insert_new_advertiser_result_view',$data);

	}

	public function get_c_lp($version_id)
	{
		$campaign = $this->al_model->get_campaign_details_by_version_id($version_id);
		echo json_encode($this->al_model->format_url($campaign['LandingPage']));
	}

	public function get_c_adv_name($version_id)
	{
		$campaign_advertiser_name = $this->al_model->get_campaign_adv_name_by_version_id($version_id);
		echo json_encode($campaign_advertiser_name['Name']);
	}

	public function publish_creative()
	{

		$creative_size = $_POST["cr_size"];
		$dfa_supported_ad_sizes = [
			'160x600' => true,
			'300x250' => true,
			'320x50' => true,
			'336x280' => true,
			'728x90' => true
		];
		if(!array_key_exists($creative_size, $dfa_supported_ad_sizes))
		{
			$ignore_size_message = "The {$creative_size} ad size is currently ignored when publishing ot DFA.";
			echo '<span class="label-warning" title="'.htmlentities($ignore_size_message).'"><i class="icon-eye-close icon-white"></i>SKIPPED</span>';
			return;
		}
		$version_id = $_POST['version'];
		$adset_id = $this->cup_model->get_adset_by_version_id($version_id);
		$dfa_advertiser_id = $_POST["dfa_a_id"];
		$dfa_campaign_id = $_POST["dfa_c_id"];
		$vl_campaign_id = $_POST["vl_c_id"];
		$assets = $this->cup_model->get_assets_by_adset($version_id, $creative_size);
		$builder_version = $this->cup_model->get_builder_version_by_version_id($version_id);
		//array_push($assets, array('type'=>'variables_data'));
		if($this->cup_model->files_ok($assets, $creative_size, $builder_version, FALSE, FALSE, TRUE))
		{
			$creative = $this->cup_model->prep_file_links($assets,$creative_size, $vl_campaign_id);

			$dfa_push_success = $this->publisher_model->push_dfa_creatives($creative, $creative_size, $dfa_advertiser_id, $dfa_campaign_id, $version_id);

			if($dfa_push_success['is_success'] == TRUE)
			{
				$this->cup_model->mark_version_published_time($version_id);
				echo '<span class="label label-success"><i class="icon-thumbs-up icon-white"></i>SUCCESS</span>';
			}
			else
			{
				echo '<span class="label-warning" title="'.htmlentities($dfa_push_success['err_msg']).'"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
				return;
			}
		}
		else
		{
			echo '<span class="label-warning" title="File check failed for size ' . $creative_size . '"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
		}
	}
	

	private function save_creative_details($dfa_adv_id, $dfa_camp_id, $dfa_placement_id, $dfa_creative_id, $dfa_ad_id,$creative_size, $version_id)
	{
	
		return $this->cup_model->update_creative(array(
												'ad_tag'=>$this->dfa_model->secure_dfa_tag($this->dfa_model->get_tags($dfa_placement_id,$dfa_camp_id)),
												'dfa_advertiser_id'=>$dfa_adv_id,
												'dfa_campaign_id'=>$dfa_camp_id,
												'dfa_placement_id'=>$dfa_placement_id,
												'dfa_creative_id'=>$dfa_creative_id,
												'dfa_ad_id'=>$dfa_ad_id,
												'published_ad_server'=>k_ad_server_type::dfa_id,
												'size'=>$creative_size,
												'adset_id'=>$version_id
												));

	}

	public function get_ad_tag_from_creative_id(
			$creative_id,
			$include_ad_choices_element,			
			$ad_server
		)
	{
			$ad_data = $this->cup_model->get_data_for_ad_tag($creative_id, $ad_server);
			
			if($ad_data !== false)
			{
				$ad_tag = $ad_data['ad_tag'];
				
				$trust_tag = '';
				if($include_ad_choices_element == 'true')
				{
					$trust_tag = $this->publisher_model->construct_ad_choices_element(
						$ad_data['ad_size'],
						$ad_data['ad_choices_tag']
					);
				}

				echo '<xmp>'.$ad_tag.$trust_tag.'</xmp>';
			}
	}
	
	public function get_ad_tags($version_id, $include_ad_choices_element, $ad_server_string)
	{
			$ad_server_type_id = k_ad_server_type::resolve_string_to_id($ad_server_string);
			if($ad_server_type_id == k_ad_server_type::unknown)
			{
				die("unknown ad_server: ".$ad_server_post." (Error: #843783)"); // TODO: better error handling -scott
			}
			$creatives = $this->cup_model->get_creatives_for_version($version_id, $ad_server_type_id);
			$partner_array = $this->cup_model->get_partner_from_version($version_id);
			$partner_id = ($include_ad_choices_element == 'true')? $partner_array[0]['partner_id'] : null;
			$tag_string = $this->publisher_model->get_ad_choices_tag_by_partner_id($partner_id);

			foreach($creatives as $creative)
			{
				$ad_tag = $creative['ad_tag'];
				
				$trust_tag = '';
				if($include_ad_choices_element == 'true')
				{
					$trust_tag = $this->publisher_model->construct_ad_choices_element(
						$creative['size'],
						$tag_string
					);
				}
				
				echo '<br><xmp><!--'.$creative['size'].'--></xmp>';
				if($ad_server_type_id == k_ad_server_type::dfa_id)
				{
					echo '<xmp>'.$ad_tag.$trust_tag.'</xmp><br>';
				}
				elseif($ad_server_type_id == k_ad_server_type::fas_id)
				{
					echo '<xmp>'.$ad_tag.$trust_tag.'</xmp><br>';
				}
				elseif($ad_server_type_id == k_ad_server_type::adtech_id)
				{
					echo '<xmp>'.$creative['adtech_ad_tag'].$trust_tag.'</xmp><br>';
				}
				else
				{
					// TODO: better error handling -scott
				}
			}
	}

	public function render_ad_tags($version_id, $ad_server_string, $include_ad_choices_element)
	{
			$this->role_check();

			$ad_server_type_id = k_ad_server_type::resolve_string_to_id($ad_server_string);
			if($ad_server_type_id == k_ad_server_type::unknown)
			{
				die("unknown ad_server: ".$ad_server_post." (Error: #843783)"); // TODO: better error handling -scott
			}

			$creatives = $this->cup_model->get_creatives_for_version($version_id, $ad_server_type_id);
			$partner_array = $this->cup_model->get_partner_from_version($version_id);
			$partner_id = ($include_ad_choices_element == 'true')? $partner_array[0]['partner_id'] : null;
			$tag_string = $this->publisher_model->get_ad_choices_tag_by_partner_id($partner_id);

			foreach($creatives as $creative)
			{
				$ad_tag = $creative['ad_tag'];
				
				echo '<br><xmp><!--'.$creative['size'].'--></xmp>';

				$trust_tag = '';
				if($include_ad_choices_element == 'true')
				{
					$trust_tag = $this->publisher_model->construct_ad_choices_element(
						$creative['size'],
						$tag_string
					);
				}

				switch($ad_server_type_id)
				{
					case k_ad_server_type::dfa_id:
						echo $ad_tag.$trust_tag.'<br>';
						break;
					case k_ad_server_type::fas_id:
						echo $ad_tag.$trust_tag.'<br>';
						break;
					case k_ad_server_type::adtech_id:
						echo $creative['adtech_ad_tag'].$trust_tag.'<br>';
						break;
					default:
						// TODO: better error handling -scott
				}
			}
	}

	private function secure_dfa_tag($tag)
	{
		return str_replace("http://", "https://", $tag);
	}

	public function get_tags_spreadsheet(
		$adset_id,
		$version_id,
		$include_ad_choices_element,
		$ad_server_string
	)
	{
		$ad_server_type_id = k_ad_server_type::resolve_string_to_id($ad_server_string);
		if($ad_server_type_id == k_ad_server_type::unknown)
		{
			die("unknown ad_server: ".$ad_server_post." (Error: #843783)"); // TODO: better error handling -scott
		}

		$data['include_ad_choices'] = $include_ad_choices_element;
		$partner_array = $this->cup_model->get_partner_from_version($version_id);
		$partner_id = ($include_ad_choices_element == 'true')? $partner_array[0]['partner_id'] : null;
		$data['partner'] = $partner_id;
		$tag_string = $this->publisher_model->get_ad_choices_tag_by_partner_id($partner_id);
		$data['adset'] = $this->cup_model->get_adset_details($adset_id);
		$creatives = $this->cup_model->get_creatives_for_version($version_id, $ad_server_type_id);
		$data['creatives'] = $creatives;
		$data['ad_server_type_id'] = $ad_server_type_id;

		$creatives_data = array();
		foreach($creatives as $creative)
		{
			$dimensions = explode('x',$creative['size']);									 
			$width = $dimensions[0];
			$height = $dimensions[1];

			$trust_tag = '';
			if($include_ad_choices_element) 
			{
				$trust_tag = $this->publisher_model->construct_ad_choices_element(
					$creative['size'],
					$tag_string
				);
			}

			$creatives_data[] = array(
				'width' => $width,
				'height' => $height,
				'trust_tag' => $trust_tag
			);
		}

		$data['creatives_data'] = $creatives_data;

		$this->load->view('ad_linker/tags_xls',$data);
	}
	
	public function get_fas_tags_for_oando($version_id, $ad_server_string)
	{
		$ad_server_type_id = k_ad_server_type::resolve_string_to_id($ad_server_string);
		
		if ($ad_server_type_id == k_ad_server_type::unknown)
		{
			die("unknown ad_server:  (Error: #843783)");
		}
		
		$creatives = $this->cup_model->get_creatives_for_version($version_id, $ad_server_type_id);
		
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="ad_tags.txt"');

		if (!defined('PHP_EOL'))
		{
			switch (strtoupper(substr(PHP_OS, 0, 3)))
			{
				// Windows
				case 'WIN':
					define('PHP_EOL', "\r\n");
					break;
				// Mac
				case 'DAR':
					define('PHP_EOL', "\r");
					break;
				// Unix
				default:
					define('PHP_EOL', "\n");
			}
		}
		
		foreach ($creatives as $creative)
		{
			$ad_tag = $creative['ad_tag'];
			$ad_tag = str_replace("fas_candu=", "fas_candu=%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("fas_c=", "fas_c=%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("fas_candu_for_js=\"", "fas_candu_for_js=\"%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("fas_c_for_js=\"", "fas_c_for_js=\"%%CLICK_URL_ESC%%", $ad_tag);
			$ad_tag = str_replace("<NOSCRIPT><A HREF=\"", "<NOSCRIPT><A HREF=\"%%CLICK_URL_ESC%%", $ad_tag);
			echo $ad_tag."\r\n\n";
		}		
		exit();
	}
}
?>
