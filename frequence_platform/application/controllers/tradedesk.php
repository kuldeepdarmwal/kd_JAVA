<?php
class tradedesk extends CI_Controller
{
	private $hdr_log_file;
	
    public function __construct()
    {
	    parent::__construct();
	    $this->load->helper('form');
	    $this->load->helper('url_helper');
	    $this->load->helper('ad_server_type');
	    $this->load->helper('mailgun');
	    $this->load->library('session');
	    $this->load->library('tank_auth');
	    $this->load->library('cli_data_processor_common');
	    $this->load->model('cup_model');
	    $this->load->model('al_model');
	    $this->load->model('tradedesk_model');
	    $this->load->model('publisher_model');
	    $this->load->model('al_model');
	    $this->load->model('campaign_health_model');
	    $this->load->model('tag_model');
	    $this->load->model('td_uploader_model');
		$this->load->helper('vl_ajax_helper');
		$this->load->helper('tradedesk_helper');
    }

    public function check_campaign_advertiser()
    {
		$exists = $this->tradedesk_model->does_campaign_have_ttd_advertiser($_GET['c_id']);
		$return['success'] = TRUE;
		if ($exists)
		{
			$return['exists'] = TRUE;
		}
		else
		{
			$return['exists'] = FALSE;
		}
		echo json_encode($return);
    }
    
    public function check_campaign_adgroup()
    {
		$exists = $this->tradedesk_model->does_campaign_have_adgroup($_GET['c_id']);
		$return['success'] = TRUE;
		if ($exists > 0)
		{
			$return['exists'] = TRUE;
		}
		else
		{
			$return['exists'] = FALSE;
		}
		echo json_encode($return);
    }

    public function post_tag_and_return_tag()
    {
    	
		
		if($this->input->post('c_id') === false || $this->input->post('tag_type') === false || $this->input->post('tag_file_id') === false)
		{
		    echo json_encode($return);
		    return;
		}
		
		//If passed tag type is RTG/Conversion and if tag file already contains one of those tags, don't proceed.
		if($this->tag_model->check_if_tag_file_contains_rtg_or_conversion_tag(null,$this->input->post('tag_type'),$this->input->post('tag_file_id')))
		{
			$return['err_msg'] = "Tag file already contains ".($this->input->post('tag_type') == '0' ? "RTG" : "conversion")." tag";
			$return['success'] = false;
			echo json_encode($return);
			return;
		}
		
		//Find tag file name for tag file id. If name contains directory name, remove the directory name.
		$tag_file_name = $this->get_tag_file_name($this->input->post('tag_file_id'));
				
		$ttd_advertiser_id = $this->tradedesk_model->get_ttd_advertiser_by_campaign($this->input->post('c_id'));
		$ttd_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($this->input->post('c_id'));
		$landing_page = $this->tradedesk_model->get_campaign_landing_page($this->input->post('c_id'));
		$is_adverify = false;
		$is_conversion = false;
		
		switch($this->input->post('tag_type'))
		{
			case "0":
				$tag_name = $tag_file_name." - RTG";
				break;
			case "1":
				$is_adverify = true;
				$landing_page ="http://adverify.vantagelocalstage.com/cookie_monster.php?id=".$this->input->post('c_id');
				$tag_name = $tag_file_name;
				break;
			case "2":
				$is_conversion = true;
				$tag_name = $tag_file_name." - CONV";
				break;
			default:
				echo json_encode($return);
				return;
		}

		echo json_encode($this->add_tag_and_return_tag($ttd_advertiser_id,$ttd_campaign_id, $tag_name, $landing_page, $is_conversion, $this->input->post('c_id'), $this->input->post('tag_type')));
    }
    
	public function add_tag_and_return_tag($ttd_advertiser_id, $ttd_campaign_id, $tag_name,  $landing_page, $is_conversion, $f_campaign_id, $tag_type) 
    {
		
		$return['err_msg'] = "";
		$return['success'] = false;

		$access_token = $this->tradedesk_model->get_access_token();
		$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
		if($ttd_advertiser_id != FALSE)
		{
		    $tag_id = $this->tradedesk_model->create_tag($access_token_v3, $tag_name, $ttd_advertiser_id, $landing_page, $is_conversion);
		    if($tag_id != FALSE)
		    {
				if($is_conversion)
				{
				    $this->tradedesk_model->update_campaign_conversion_reporting_columns($access_token, $ttd_campaign_id, array($tag_id));
				    $return['success'] = true;
				}
				else
				{
				    $datagroup_id = $this->tradedesk_model->create_data_group($access_token, $tag_name, $ttd_advertiser_id, $this->tradedesk_model->get_data_ids_by_tag_name_and_advertiser_id($access_token, $tag_name, $ttd_advertiser_id));
				    if($datagroup_id != FALSE)
				    {
						$audience_id = $this->tradedesk_model->create_audience($access_token, $tag_name, $ttd_advertiser_id, array($datagroup_id));

						if($audience_id != FALSE)
						{

						    //TODO RYAN: If something? Set tag with tag id to campaign 
						    $audience_result = $this->tradedesk_model->add_audience_to_adgroup($audience_id, $f_campaign_id, $tag_type);
						    if($audience_result != false)
						    {
								$return['success'] = true;
						    }
						    else
						    {
								$return['err_msg'] = "Failed to add TTD audience to adgroup.";
						    }
						}
						else
						{
						    $return['err_msg'] = "Failed to create TTD audience.";
						}
				    }
				    else
				    {
						$return['err_msg'] = "Failed to create TTD datagroup.";
				    }
				}
				if($return['success'])
				{
				    $return['tags'] = $this->tradedesk_model->get_tag_html($ttd_advertiser_id, $tag_id, $tag_type);
				}
		    }
		    else
		    {
				$return['success'] = FALSE;
				$return['err_msg'] = "Failed to create TTD Tracking tag.";
		    }
		}
		else
		{
		    $return['success'] = FALSE;
		    $return['err_msg'] = "Failed to retrieve TTD Advertiser ID from database.";
		}
		return $return;
    }
    

    
    public function check_advertiser()
    {
		$exists = $this->tradedesk_model->is_advertiser_in_trade_desk($_GET['adv_name']);
		$return['success'] = TRUE;
		$return['exists'] = $exists;
		echo json_encode($return);
    }
    
    public function check_campaign()
    {
		$return['success'] = false;
		$return['exists'] = false;
		$return['adgroups'] = false;
		$return['video_campaign_type'] = "";
		if($this->input->get('c_id') === false)
		{
			echo json_encode($return);
			return;
		}
		$campaign_id = $this->input->get('c_id');
		$return['exists'] = $this->tradedesk_model->is_campaign_in_trade_desk($campaign_id);
		$return['ttd_cmp_id'] = $return['exists']['ttd_campaign_id'];
		$adgroups = $this->tradedesk_model->get_ttd_adgroups_by_campaign($campaign_id);
		if($adgroups != false)
		{
		    $return['adgroups'] = true;
			$return['video_campaign_type'] = $this->tradedesk_model->determine_video_campaign_type_for_campaign($campaign_id);
			if($return['video_campaign_type'] == false)
			{
				echo json_encode($return);
				return;	
			}
		}
		$return['are_dates_matching'] = $this->tradedesk_model->are_campaign_dates_matching($campaign_id);
		$return['success'] = true;
		echo json_encode($return);
    }
    
    public function check_av_campaign()
    {
		$exists = $this->tradedesk_model->is_av_campaign_in_trade_desk($_GET['c_id']);
		$return['success'] = TRUE;
		$return['exists'] = $exists;
		echo json_encode($return);
    }
    
 

    public function get_num_monthly_cycles($hard_end_date)
    {
		$time_diff = date_diff(datetime::createfromformat('Y-m-d',date("Y-m-d", strtotime("now"))), datetime::createfromformat('Y-m-d',$hard_end_date));
		$num_cycles = ($time_diff->y*12+$time_diff->m)*($time_diff->invert==1? -1 : 1);
		return $num_cycles+1;
    }
    

    
    public function add_advertiser()
    {
	
		if($this->input->post('adv_name') === FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Missing request parameters";
			echo json_encode($return);
			return;
		}  
	
		$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
		$td_id = $this->tradedesk_model->create_advertiser($access_token_v3, $this->input->post('adv_name'), $this->input->post('adv_id'));
		if ($td_id != false)
		{
			$rows_affected = $this->tradedesk_model->add_ttd_id_to_advertiser($this->input->post('adv_name'), $td_id);
			$return['rows'] = $rows_affected;
			if($rows_affected > 0)
			{
				$return['success'] = TRUE;
				$return['td_id'] = $td_id;
			}
			else
			{
				$return['success'] = TRUE;
			}
		}
		else
		{
			$return['success'] = TRUE;
		}
		echo json_encode($return);

    }
    
    
	public function add_campaign()
	{
	
		$return = array('success' => true, 'err_msg' => '', 'tag_pushed' => false);
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('adv_id','c_name', 'c_id', 'e_date', 'start_date', 'is_pre_roll', 'ti_budget', 'tag_file_id');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if($ajax_verify['is_success'])
		{		
			$return['err_msg'] = "";
			$access_token = $this->tradedesk_model->get_access_token();
			$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
			$td_advertiser_id = $this->tradedesk_model->get_td_advertiser_id($ajax_verify['post']['adv_id']);
			$campaign_id = $ajax_verify['post']['c_id'];
			$start_date = $ajax_verify['post']['start_date'];
			$is_pre_roll = $ajax_verify['post']['is_pre_roll'];
			$e_date = $ajax_verify['post']['e_date'] == "" ? null : $ajax_verify['post']['e_date'];
			 
			$first_timeseries_details=$this->al_model->get_first_flight_campaign_timeseries_details($campaign_id);
			$total_audience_extension_impressions = $this->al_model->get_managed_impression_totals_for_campaign($campaign_id);			
			
			$budget_numbers = $this->tradedesk_model->calculate_budget_numbers($start_date, $e_date, $total_audience_extension_impressions, null, $is_pre_roll, 
			$first_timeseries_details['first_flight_start_date'], $first_timeseries_details['first_flight_end_date'], $first_timeseries_details['first_flight_impressions']);
			
			if($budget_numbers == false)
			{
				$return['err_msg'] .= "Failed to generate TTD Budget calculations.";
				$return['success'] = false;
				echo json_encode($return);
				return;   
			}
			
			$new_budget_numbers = $this->al_model->generate_budget_and_impressions($campaign_id);
			
			$td_campaign_id = $this->tradedesk_model->create_campaign($access_token, $ajax_verify['post']['c_name'], $td_advertiser_id,$budget_numbers['c_total_budget'],$budget_numbers['c_total_impressions'],$start_date, $budget_numbers['c_end_date']);
			$affected_rows = $this->tradedesk_model->add_ttd_id_to_campaign($campaign_id, $td_campaign_id);
			if($affected_rows > 0)
			{
				$return['success'] = true;
				$return['id'] = $td_campaign_id;
			}
			else
			{
				$return['err_msg'] .= "Failed to link TTD Campaign to VL Campaign. ";
				$return['success'] = false;
				echo json_encode($return);
				return;
			}
			
			if($is_pre_roll)
			{
				$ag_success = $this->tradedesk_model->build_pre_roll_adgroup_set($access_token_v3, $ajax_verify['post']['c_name'], $td_campaign_id, $budget_numbers['adgroup_budgets']);
			}
			else
			{
				$ag_success = $this->tradedesk_model->build_default_adgroup_set($access_token_v3, $ajax_verify['post']['c_name'], $td_campaign_id, $budget_numbers['adgroup_budgets']);
			}
			
			if(!$ag_success['success'])
			{
				$return['success'] = false;
				$return['err_msg'] = $ag_success['err_msg'];
				echo json_encode($return);
				return;
			}

			//Add tag and save
			if($ajax_verify['post']['tag_file_id'])
			{
				$tag_file_id = $ajax_verify['post']['tag_file_id'];				
				$tag_empty = $this->tradedesk_model->is_tag_file_empty_or_nopixels($tag_file_id, $td_advertiser_id);				
				if($tag_empty == 0)
				{
					$is_new_tag_file = true;
				}
				else
				{
					$is_new_tag_file = isset($_POST['is_new_tag_file']) ? $_POST['is_new_tag_file'] : 0;
				}
				//Find tag file name for tag file id. If name contains directory name, remove the directory name.
				$tag_file_name = $this->get_tag_file_name($tag_file_id);
				
				$push_friendly = $this->tradedesk_model->is_tag_file_push_friendly_for_advertiser($tag_file_id, $td_advertiser_id);				
				if($push_friendly)
				{
					$ttd_adgroups = $this->tradedesk_model->get_all_ttd_adgroups_by_campaign($campaign_id);
					$ttd_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($campaign_id);
					
					$landing_page = $this->tradedesk_model->get_campaign_landing_page($campaign_id);
					$names = $this->tradedesk_model->get_campaign_and_advertiser_name_by_campaign_id($campaign_id);

					$rtg_tag = false;
					$saved_rtg_tag = false;
					$conversion_tag = false;
					$saved_conversion_tag = false;

					if($push_friendly['rtg'] <= 1 || $push_friendly['conversion'] <= 1)
					{
						//Use existing audience
						/*$applied_audience = $this->tradedesk_model->apply_audience_from_tag_file_to_campaign($access_token, $tag_file_id, $campaign_id);
						if($applied_audience == false)
						{
							$return['success'] = false;
							$return['err_msg'] = "Failed to apply existing audience id to new adgroup";
							echo json_encode($return);
							return;						
						}*/
						$rtg_tag = true;
						$saved_rtg_tag = true;
						$conversion_tag = true;
						$saved_conversion_tag = true;						
					}
					
					if($is_new_tag_file)
					{
						$rtg_tag_result = $this->add_tag_and_return_tag($td_advertiser_id, $td_campaign_id, $tag_file_name." - RTG", $landing_page, false, $campaign_id, 0);
						if($rtg_tag_result['success'] == false)
						{
							$return['success'] = false;
							$return['err_msg'] = "RTG Tag: ".$rtg_tag_result['err_msg'];
							echo json_encode($return);
							return;
						}
						$rtg_tag = $rtg_tag_result['tags'];
						$saved_rtg_tag = $this->tag_model->save_tag_code_to_file($rtg_tag, $tag_file_id, 0);						
					
						$conversion_tag_result = $this->add_tag_and_return_tag($td_advertiser_id, $td_campaign_id, $tag_file_name." - CONV", $landing_page, true, $campaign_id, 2);
						if($conversion_tag_result['success'] == false)
						{
							$return['success'] = false;
							$return['err_msg'] = "CONV Tag: ".$conversion_tag_result['err_msg'];
							echo json_encode($return);
							return;
						}
						$conversion_tag = $conversion_tag_result['tags'];
						$saved_conversion_tag = $this->tag_model->save_tag_code_to_file($conversion_tag, $tag_file_id, 2);							
					}

					if(!$saved_rtg_tag || !$saved_conversion_tag)
					{
						$return['success'] = false;
						$return['err_msg'] = "Error while saving tags";
						echo json_encode($return);
						return;					
					}
					
					$existing_tags_assigned = null;
					if($is_new_tag_file)
					{
						$existing_tags_assigned = 1;
					}
					
					$saved_tag_file_to_campaign = $this->tag_model->create_tag_files_to_campaigns_entry($tag_file_id, $campaign_id, $existing_tags_assigned);
					if (!$saved_tag_file_to_campaign)
					{
						$return['success'] = false;
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
					
					$return['tag_pushed'] = true;					
				}
				else
				{
					$return['tag_pushed'] = -1;
					$return['success'] = true;
					$return['err_msg'] = "Error while saving tags";
				}
			}
		}
		else
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Missing request parameters";
			echo json_encode($return);
			return;
		}  			
		echo json_encode($return);
	
    }

	//Add an adverify campaign to TTD, add that campaign's id to the database
	public function add_av_campaign()
	{
		$return = array('success' => true, 'err_msg' => '');
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('adv_id','c_name', 'c_id', 'c_start');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if($ajax_verify['is_success'])
		{		
			
			$access_token = $this->tradedesk_model->get_access_token();
			$advertiser_id = $ajax_verify['post']['adv_id'];
			$campaign_name = $ajax_verify['post']['c_name'];
			$campaign_id = $ajax_verify['post']['c_id'];
			$td_advertiser_id = $this->tradedesk_model->get_td_advertiser_id($advertiser_id);
			$start_date = format_start_date($ajax_verify['post']['c_start']);
			$td_campaign_id = $this->tradedesk_model->create_av_campaign($access_token, $campaign_name." - Ad Verify", $td_advertiser_id, $start_date);
			$affected_rows = $this->tradedesk_model->add_ttd_av_id_to_campaign($campaign_id, $td_campaign_id);
			$adgroupid = $this->tradedesk_model->create_av_adgroup($access_token, $campaign_name." - Ad_Verify", $td_campaign_id, NULL, array());
			if($affected_rows > 0)
			{
				$return['id'] = $td_campaign_id;
			}
			else
			{
				$return['err_msg'] = "No campaign ID added to DB";
				$return['success'] = false;
				echo json_encode($return);	
				return;
			}
			if($adgroupid == false)
			{
				$return['err_msg'] = "Failed to create adverify adgroup";
				$return['success'] = false;
				echo json_encode($return);	
				return;			
			}
			
			$add_adgroup_to_db = $this->tradedesk_model->add_av_adgroup_to_db($adgroupid, $td_campaign_id);
			
			if($add_adgroup_to_db == false)
			{
				$return['success'] = false;
				$return['err_msg'] = "Failed to add adgroup to DB";
				echo json_encode($return);	
				return;				
			}

			$names = $this->tradedesk_model->get_campaign_and_advertiser_name_by_campaign_id($campaign_id);
			$file_name = $names->c_name."_AV";
			$file_name = str_replace(" ", "-", $file_name);
			$file_name = str_replace("/", "-", $file_name);
			$file_name = str_replace("*", "-", $file_name);
			//Create tag file
			$tracking = new stdClass();
			$tracking->io_advertiser_id = $advertiser_id;
			$tracking->source_table = "Advertisers";
			$tracking->tracking_tag_file = $file_name.".js";

			$tag_file_id= $this->tag_model->save_tracking_tag_file($tracking);

			if(!$tag_file_id)	
			{
				$return['success'] = false;
				$return['err_msg'] = "Failed to create tag file";
				echo json_encode($return);	
				return;
			}
			//Create adverify tag
			$landing_page ="http://adverify.vantagelocalstage.com/cookie_monster.php?id=".$campaign_id;
			$adverify_tag_result = $this->add_tag_and_return_tag($td_advertiser_id, $td_campaign_id, $file_name, $landing_page, false, $campaign_id, 1);
			if($adverify_tag_result['success'] == false)
				{
					$return['success'] = false;
					$return['err_msg'] = "RTG Tag: ".$adverify_tag_result['err_msg'];
					echo json_encode($return);
					return;
				}			
			//Save adverify tag to tag file
			$adverify_tag = $adverify_tag_result['tags'];
			$saved_adverify_tag = $this->tag_model->save_tag_code_to_file($adverify_tag, $tag_file_id, 1);	
			if(!$saved_adverify_tag)
			{
				$return['success'] = false;
				$return['err_msg'] = "Error while saving adverify tag";
				echo json_encode($return);
				return;					
			}			
			//Save tag file to campaign
			$saved_tag_file_to_campaign = $this->tag_model->create_tag_files_to_campaigns_entry($tag_file_id, $campaign_id);
			if(!$saved_tag_file_to_campaign)
			{
				$return['success'] = false;
				$return['err_msg'] = "Error while saving adverify tag";
				echo json_encode($return);
				return;						
			}			
		}
		echo json_encode($return);
	}
	
	//Add a creative version to TTD, slap them in an array.
	//And then link the array to applicable adgroups.
	public function add_creative_set()
	{
		$return['success'] = TRUE;
		$return['err_msg'] = "";
		
		$version_id = $this->input->post('v_id');
		$custom_prefix = $this->input->post('prefix');
		$replace = $this->input->post('replace');
		$adgroup_set = $this->input->post('adgroup_set');
		if(empty($version_id) || $custom_prefix === FALSE)
		{
		    $return['success'] = false;
		    $return['err_msg'] = "Missing version or prefix information";
		    echo json_encode($return);
		    return;
		}

		if($custom_prefix != "")
		{
		    $custom_prefix = $custom_prefix.'_';
		}
		$ad_server_post = $this->input->post('ad_server');

		$ad_server_type_id = k_ad_server_type::unknown;
		if($ad_server_post)
		{
			$ad_server_type_id = k_ad_server_type::resolve_string_to_id($ad_server_post);
			if($ad_server_type_id == k_ad_server_type::unknown)
			{
				die("unknown ad_server: ".$ad_server_post." (Error: #197402)"); // TODO: better error handling -scott
			}
		}
		else
		{
			die("missing ad_server from post (Error: #897742)"); // TODO: better error handling -scott
		}

		$campaign_id = $this->tradedesk_model->get_campaign_for_version($version_id);
		$landing_page = $this->tradedesk_model->get_campaign_landing_page($campaign_id);
		
		$ad_choices_span = $this->tradedesk_model->get_ad_choice_tag_by_version($version_id);

		$ttd_advertiser_id = $this->tradedesk_model->get_ttd_advertiser_by_campaign($campaign_id);
		if($ttd_advertiser_id == FALSE)
		{
			$return['success'] = FALSE;
			echo json_encode($return);
			return;
		}
		$names = $this->tradedesk_model->get_campaign_and_advertiser_name_by_campaign_id($campaign_id);
		$adset_version_details = $this->tradedesk_model->get_version_adset_name_and_version_number($version_id);
		if($adset_version_details == false || $names == false)
		{
		    $return['success'] = false;
		    $return['err_msg'] = "Failed to get adset data";
		    echo json_encode($return);
		    return;
		}
		$username = $this->session->userdata('username');
		//Get all tags and names required.
		$access_token = $this->tradedesk_model->get_access_token();
		$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
		$creative_data = $this->cup_model->get_creatives_for_version($version_id, $ad_server_type_id);
		$pc_creative_set = array(); //All sizes
		$mobile_320_creative_set = array(); //Only 320x50
		$mobile_no_320_creative_set = array(); //All but 320x50	
		$ttd_supported_ad_sizes = [
			'160x600' => true,
			'300x250' => true,
			'320x50' => true,
			'336x280' => true,
			'728x90' => true
		];
		//Throw in each creative and add it to the array
		foreach($creative_data as $creative)
		{
			if(!array_key_exists($creative['size'], $ttd_supported_ad_sizes))
			{
				continue;
			}
			$ad_tag = '';
			switch($ad_server_type_id)
			{
			case k_ad_server_type::dfa_id:
				$ad_tag = $creative['ad_tag'];
				break;
			case k_ad_server_type::fas_id:
				$ad_tag = $creative['ad_tag'];
				$ad_tag = str_replace("fas_candu=", "fas_candu=%%TTD_CLK_ESC%%", $ad_tag);
				$ad_tag = str_replace("fas_c=", "fas_c=%%TTD_CLK_ESC%%", $ad_tag);
				$ad_tag = str_replace("fas_candu_for_js=\"", "fas_candu_for_js=\"%%TTD_CLK_ESC%%", $ad_tag);
				$ad_tag = str_replace("fas_c_for_js=\"", "fas_c_for_js=\"%%TTD_CLK_ESC%%", $ad_tag);
				$ad_tag = str_replace("<NOSCRIPT><A HREF=\"", "<NOSCRIPT><A HREF=\"%%TTD_CLK_ESC%%", $ad_tag);
				break;
			case k_ad_server_type::adtech_id:
				$ad_tag = $creative['adtech_ad_tag'];
				$ad_tag = str_replace("rdclick=", "rdclick=%%TTD_CLK%%", $ad_tag);
				break;
			default:
				// TODO: do something other than die? -scott
				die('unknown ad server type: '.$ad_server.', expected "dfa" or "adtech". (Error #298732)');
			}

			$size = explode('x', $creative['size']);
			$width = $size[0];
			$height = $size[1];

			if($this->input->post('ad_choices') && $ad_choices_span != FALSE)
			{
				$trust_tag = $this->publisher_model->construct_ad_choices_element(
					$creative['size'],
					$ad_choices_span
					);
				$ad_tag .= $trust_tag;
				
				$this->cup_model->set_adchoices_status_for_version($version_id, 1, $ad_server_post);
			}
			else
			{
			    $this->cup_model->set_adchoices_status_for_version($version_id, 0, $ad_server_post);
			}
				
			$creative_name = $custom_prefix.$names->a_name.'_'.$names->c_name.'_'.$adset_version_details->adset_name.'_v'.$adset_version_details->v_number.'-'.$adset_version_details->variation_name.'_'.$username.'_'.$ad_server_post.'_'.$creative['size'];
			$creative_name = str_replace(' ', '_', $creative_name);
			//add to tradedesk

			//MOAT Tag
			$moat_tag = $this->cup_model->generate_moat_tag_for_campaign_with_ad_size($campaign_id, $creative['size']);
			if($moat_tag !== false)
			{
				$ad_tag .= $moat_tag;
			}

			$ttd_creative_id = $this->tradedesk_model->create_creative($access_token_v3, $creative_name, $ttd_advertiser_id, $ad_tag, $height, $width, $landing_page);

			if($ttd_creative_id != FALSE)
			{
				array_push($pc_creative_set, $ttd_creative_id);
				if($creative['size'] == "320x50")
				{
				    array_push($mobile_320_creative_set, $ttd_creative_id);
				}
				else
				{
				    array_push($mobile_no_320_creative_set, $ttd_creative_id);
				}
				$rows = $this->tradedesk_model->add_ttd_id_to_creative($creative['id'], $ttd_creative_id, $ad_server_type_id);
			}
			else
			{
				$return['success'] = false;
				$return['err_msg'] = "Failed to create creative in TTD";
			}
		}

		$did_clear_unused = $this->tradedesk_model->clear_ttd_id_from_unused_creatives($version_id, $ad_server_type_id);
		if(!$did_clear_unused)
		{
			die("failed to remove unused creatives - (Error #93271)"); // TODO: better error handling -scott
		}
		
		//get adgroups in VL database for that campaign
		$adgroups = $this->tradedesk_model->get_all_ttd_adgroups_by_campaign($campaign_id);
		$updated = false;
		//Update adgroups in TTD with the creative array.
		//If there are no adgroups found, well, whatever, it won't do anything, then.
		if($adgroups != FALSE)
		{
			foreach($adgroups as $adgroup)
			{
				$ag_id = $adgroup['ID'];
				if($adgroup['target_type'] == NULL && $adgroup['Source'] != "TDAV" )
				{
					$adgroup['target_type'] = $this->tradedesk_model->determine_target_type_for_adgroup_id($ag_id);
				}
				
				if($adgroup_set == "rtg")
				{
					if($adgroup['target_type'] == "RTG")
					{
						$updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $pc_creative_set, $replace);
						if($updated == false)
						{
							$return['success'] = false;
							$return['err_msg'] = "Failed to update adgroup's TTD creative ids";
						}
					}				
				}
				else
				{
					if($adgroup['target_type'] == "Mobile 320")
					{
					    $updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $mobile_320_creative_set, $replace);
					}
					else if($adgroup['target_type'] == "Mobile No 320")
					{
					    $updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $mobile_no_320_creative_set, $replace);
					}
					else if($adgroup['target_type'] == "RTG")
					{
						if($adgroup_set == "all")
						{
							$updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $pc_creative_set, $replace);
						}
					}
					else if($adgroup['target_type'] != "Pre-Roll" && $adgroup['target_type'] != "Custom Averaged" && $adgroup['target_type'] != "Custom Ignored" || $adgroup['target_type'] != "RTG Pre-Roll")
					{
					    $updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $pc_creative_set, $replace);
					}
					if($updated == false)
					{
						$return['success'] = false;
						$return['err_msg'] = "Failed to update adgroup's TTD creative ids";
					}					
				}

			}
		}
		echo json_encode($return);
	}
	
	
	public function update_impression_target()
	{
		if($this->input->post('new_target') === FALSE ||
	      $this->input->post('c_id') === FALSE
		)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Missing request parameters";
			echo json_encode($return);
			return;
		}
	    
	    $return['success'] = true;
	    $return['err_msg'] = "";
	    $campaign_id = $this->input->post('c_id');

	    $new_target_number = $this->input->post('new_target');
	    $access_token = $this->tradedesk_model->get_access_token();

	    //get adgroups in VL database for that campaign
	    $adgroups = $this->tradedesk_model->get_non_av_ttd_adgroups_by_campaign($campaign_id);

	    //Update adgroups in TTD with the creative array.
	    //If there are no adgroups found, well, whatever, it won't do anything, then.
	    if($adgroups != FALSE)
	    {
			$ratios = $this->tradedesk_model->determine_weights_for_adgroups($adgroups);
			if($ratios == FALSE)
			{
				$return['success'] = false;
				$return['err_msg'] = "Failed to retrieve TTD Adgroup budget data";
				echo json_encode($return);
				return;
			}
			foreach($adgroups as $adgroup)
			{
				if(array_key_exists($adgroup['ID'], $ratios)) //ratios only has the adgroup ids for the adgroups we want to update
				{
					$ag_id = $adgroup['ID'];
					$daily_impression_target = $new_target_number*$ratios[$ag_id];
					$daily_budget_target = $daily_impression_target*0.0012;
					$updated = $this->tradedesk_model->update_adgroup_daily_impression_target($access_token, $ag_id, $daily_impression_target, $daily_budget_target);
					if($updated == FALSE)
					{
						$return['success'] = false;
						$return['err_msg'] = "Failed to update adgroup ".$adgroup['ID'].".";
						echo json_encode($return);
						return;
					}
					else
					{
						if(!$this->tradedesk_model->update_timestamp_for_adgroup($adgroup['ID']))
						{
							$return['success'] = false;
							$return['err_msg'] = "Failed to update update timestamp for adgroup ".$adgroup['ID'].".";
							echo json_encode($return);
							return;  
						}
					}
				}
			}
	    }
	    else
	    {
			$return['success'] = false;
			$return['err_msg'] = "No TTD adgroups found to update.";
	    }
		echo json_encode($return);
    }
    
 
    public function get_adgroup_data($c_id)
    {
	
		$return['success'] = TRUE;
		$ttd_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($c_id);
		if($ttd_campaign_id == FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "TTD Campaign ID for this campaign not found.";
			echo json_encode($return);
			return;
		}
	    
		$adgroups = $this->tradedesk_model->get_non_av_ttd_adgroups_by_campaign($c_id);
		if($adgroups == FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Adgroups not found.";
			echo json_encode($return);
	    return;
	}
	
	
		$access_token = $this->tradedesk_model->get_access_token();
		$campaign_data = $this->tradedesk_model->get_info($access_token, "campaign", $ttd_campaign_id);
		$decoded = json_decode($campaign_data);
		if(property_exists($decoded, 'Message'))
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Failed to retrieve campaign data from TTD.";
			echo json_encode($return);
			return;
		}
		$adgroup_display_data = array();
	
	
		$adgroup_display_data['c_impressions'] = $decoded->BudgetInImpressions;
		$adgroup_display_data['c_start_date'] = substr($decoded->StartDate, 0, strpos($decoded->StartDate, "T"));
		$adgroup_display_data['c_end_date'] = substr($decoded->EndDate, 0, strpos($decoded->EndDate, "T"));
		$adgroup_display_data['c_dollars'] = $decoded->BudgetInUSDollars;
		$adgroup_display_data['c_modify_timestamp'] = $this->tradedesk_model->get_ttd_modify_timestamp_by_campaign($c_id);
		$adgroup_display_data['c_day_old_modify'] = $this->tradedesk_model->is_campaign_day_old($c_id);
		if($adgroup_display_data['c_modify_timestamp'] == FALSE)
		{
			$adgroup_display_data['c_modify_timestamp'] = "NEVER";
		}
	
		if($adgroup_display_data['c_impressions'] == NULL)
		{
			$adgroup_display_data['c_impressions'] = "0";
		}
		$adgroup_display_data['adgroups_to_display'] = array();
		$adgroup_ids = array();
		foreach($adgroups as $adgroup)
		{
			$adgroup_data = $this->tradedesk_model->get_info($access_token, "adgroup", $adgroup['ID']);
			$decoded = json_decode($adgroup_data);

			$which_adgroup = $adgroup['ID'];
	    
			$data['ttd_id'] = $adgroup['ID'];
			$data['name'] = $decoded->AdGroupName;
			$data['target_type'] = $adgroup['target_type'];
			$data['is_enabled'] = $decoded->IsEnabled;
			$data['impression_budget'] = $decoded->RTBAttributes->BudgetInImpressions;
			$data['dollar_budget'] = $decoded->RTBAttributes->BudgetInUSDollars;
			$data['daily_impression_budget'] = $decoded->RTBAttributes->DailyBudgetInImpressions;
			$data['daily_dollar_budget'] = $decoded->RTBAttributes->DailyBudgetInUSDollars;
			$data['modify_timestamp'] = $adgroup['modify_timestamp'];
			if($data['modify_timestamp'] == null)
			{
				$data['modify_timestamp'] = "NEVER";
			}
			$data['day_old_modify'] = $this->tradedesk_model->is_adgroup_day_old($adgroup['ID']);
	    
			$adgroup_display_data['adgroups_to_display'][$which_adgroup] = $data;
	    
			array_push($adgroup_ids, $adgroup['ID']);
		}
	
		$html = $this->load->view('ad_linker/ttd_adgroup_edit_form',$adgroup_display_data, true);
		$return['form_html'] = $html;
		$return['adgroup_ids'] = $adgroup_ids;
		echo json_encode($return);
    }
    
	public function get_ttd_campaign_data($c_id)
    {
	
		$return['success'] = TRUE;
		$ttd_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($c_id);
		if($ttd_campaign_id == FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "TTD Campaign ID for this campaign not found.";
			echo json_encode($return);
			return;
		}
	
		$access_token = $this->tradedesk_model->get_access_token();
		$campaign_data = $this->tradedesk_model->get_info($access_token, "campaign", $ttd_campaign_id);
		$decoded = json_decode($campaign_data);
		if(property_exists($decoded, 'Message'))
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Failed to retrieve campaign data from TTD.";
			echo json_encode($return);
			return;
		}
	
	
		$return['c_impressions'] = $decoded->BudgetInImpressions;
		$return['c_start_date'] = substr($decoded->StartDate, 0, strpos($decoded->StartDate, "T"));
		$return['c_end_date'] = substr($decoded->EndDate, 0, strpos($decoded->EndDate, "T"));
		$return['c_dollars'] = $decoded->BudgetInUSDollars;
		$return['modify_timestamp'] = $this->tradedesk_model->get_ttd_modify_timestamp_by_campaign($c_id);
		$return['day_old_modify'] = $this->tradedesk_model->is_campaign_day_old($c_id);
	
		if($return['c_impressions'] == NULL)
		{
			$return['c_impressions'] = "0";
		}
		if($return['c_dollars'] == NULL)
		{
			$return['c_dollars'] = "0";
		}
		if($return['modify_timestamp'] == FALSE)
		{
			$return['modify_timestamp'] = "NEVER";
		}
		echo json_encode($return);
    }
    
    
    public function update_campaign_from_big_form()    
    {
	if($this->input->post('c_imps') === FALSE ||
	   $this->input->post('c_bux') === FALSE ||
	   $this->input->post('c_start') === FALSE ||
	   $this->input->post('c_end') === FALSE ||
	   $this->input->post('adgroup_data') === FALSE ||
	   $this->input->post('c_id') === FALSE
	  )
	{
	    $return['success'] = FALSE;
	    $return['err_msg'] = "Missing request parameters";
	    echo json_encode($return);
	    return;
	} 
	
	$return['success'] = TRUE;
	$return['err_msg'] = "";
	
	$campaign_id = $this->input->post('c_id');
	$ttd_campaign = $this->tradedesk_model->get_ttd_campaign_by_campaign($campaign_id);
	if($ttd_campaign == FALSE)
	{
	    $return['success'] = FALSE;
	    $return['err_msg'] = "Failed to retrieve TTD campaign.";
	    echo json_encode($return);
	    return;
	}
	
	if($this->input->post('c_imps') == "")
	{
	    $c_impressions = 0;
	}
	else
	{
	  $c_impressions = $this->input->post('c_imps');  
	}
	
	if($this->input->post('c_bux') == "")
	{
	    $c_budget = 0;
	}
	else
	{
	  $c_budget = $this->input->post('c_bux'); 
	}
	
	if($this->input->post('c_start') == "")
	{
	    $c_start_date = NULL;
	    $vl_start_date = $c_start_date;
	}
	else
	{
	    $c_start_date = format_start_date($this->input->post('c_start'));
	    $vl_start_date = date("Y-m-d", strtotime($this->input->post('c_start')));
	}
	if($this->input->post('c_end') == "")
	{
	    $c_end_date = NULL;
	    $vl_end_date = $c_end_date;
	}
	else
	{
	    $c_end_date = format_end_date($this->input->post('c_end'));
	    $vl_end_date = date("Y-m-d", strtotime($this->input->post('c_end')));
	}
	

	$report_date = $this->input->post('report_date');
	
	if($report_date == FALSE)
	{
	    $report_date_result = $this->campaign_health_model->get_last_impression_date();
	    $report_date = date('Y-m-d', strtotime($report_date_result[0]['value'] . ' + 1 day'));
	}	
	
	if(!$this->tradedesk_model->set_vl_campaign_dates($campaign_id, $vl_start_date, $vl_end_date))	
	{
	    $return['success'] = FALSE;
	    $return['err_msg'] = "Failed to update dates for Frequence campaign.";
	    echo json_encode($return);
	    return; 
	}
	else
	{
	    $campaign_info = $this->campaign_health_model->get_campaign_info($campaign_id);
	    $return['campaign_type'] = $campaign_info['c_type'];
	    $return['end_date'] = $campaign_info['c_details'][0]['hard_end_date'];
	    $return['start_date'] = $campaign_info['c_details'][0]['start_date'];
	   if($return['end_date'] != NULL)
	   {
	    $return['date_check'] = floor((strtotime($return['end_date']) - strtotime($report_date))/(60*60*24))-1;
	   }
	   else
	   {
	       $return['date_check'] = "-";
	   }
	}
	
	$access_token = $this->tradedesk_model->get_access_token();
	$campaign_modify = $this->tradedesk_model->update_campaign_details($access_token, $ttd_campaign, $c_impressions, $c_budget, '', $c_start_date, $c_end_date);
	
	if($campaign_modify == FALSE)
	{
	    $return['success'] = FALSE;
	    $return['err_msg'] = "Tradedesk failed to update the campaign. Verify the campaign was set up properly.";
	    echo json_encode($return);
	    return; 
	}
	if($this->tradedesk_model->update_timestamp_for_campaign($campaign_id) == 0)
	{
	    $return['success'] = FALSE;
	    $return['err_msg'] = "Failed to update the update timestamp for this campaign.";
	    echo json_encode($return);
	    return; 
	}
	
	
	$adgroup_inputs = json_decode($this->input->post('adgroup_data'), true);
	foreach($adgroup_inputs as $ag_id => $adgroup_to_update)
	{
	   $is_enabled = $adgroup_to_update['is_enabled'];   
	   $impressions = $adgroup_to_update['impression_budget'];
	   $budget = $adgroup_to_update['dollar_budget'];	   
	   $daily_impressions = $adgroup_to_update['daily_impressions'];
	   $daily_dollars = $adgroup_to_update['daily_dollars'];
	   
	   if($impressions == 0 || $budget == 0 || $daily_dollars == 0 || $daily_impressions == 0)
	   {
	       	$return['success'] = FALSE;
		$return['err_msg'] = "Invalid inputs for adgroup ".$ag_id;
		echo json_encode($return);
		return;  
	   } 
	   
	   //Update that boyeeeeeee
	    $adgroup_modify = $this->tradedesk_model->update_adgroup_details($access_token, $ag_id, $is_enabled, $impressions, $budget, $daily_impressions, $daily_dollars);
	    if($adgroup_modify == FALSE)
	    {
		$return['success'] = FALSE;
		$return['err_msg'] = "Tradedesk failed to update the adgroup ".$ag_id.". Verify the adgroup was set up properly.";
		echo json_encode($return);
		return; 
	    }
	   
	   
	    //set update timestamp to now to verify that the row is there.
	    
	   if(!$this->tradedesk_model->update_timestamp_for_adgroup($ag_id))
	   {
	       	$return['success'] = FALSE;
		$return['err_msg'] = "Failed to set update time for adgroup ".$ag_id;
		echo json_encode($return);
		return;  
	   }
	   
	}
	
	echo json_encode($return);
	return;
	
    }
	
    public function update_campaign_from_campaign_form()    
    {
		if($this->input->post('c_imps') === FALSE ||
		   $this->input->post('c_bux') === FALSE ||
		   $this->input->post('c_start') === FALSE ||
		   $this->input->post('c_end') === FALSE ||
		   $this->input->post('c_id') === FALSE
		)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Missing request parameters";
			echo json_encode($return);
			return;
		}  
		
		
		$campaign_id = $this->input->post('c_id');
		$ttd_campaign = $this->tradedesk_model->get_ttd_campaign_by_campaign($campaign_id);
		if($ttd_campaign == FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Failed to retrieve TTD campaign.";
			echo json_encode($return);
			return;
		}
		$c_impressions = $this->input->post('c_imps');
		$c_budget = $this->input->post('c_bux');
		$c_start_date = $this->format_start_date($this->input->post('c_start'));
		$c_end_date = $this->format_end_date($this->input->post('c_end'));
		
		
		if(!$this->tradedesk_model->set_vl_campaign_end_date($campaign_id, date("Y-m-d", strtotime($this->input->post('c_end')))))
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Failed to update end date for VL campaign.";
			echo json_encode($return);
			return; 
		} 
		$access_token = $this->tradedesk_model->get_access_token();
		
		if($this->input->post('c_imps') == "0")
		{
			$c_impressions = NULL;
		}
		if($this->input->post('c_bux') == "0")
		{
			$c_budget = NULL;
		}
		if($this->input->post('c_start') == "")
		{
			$c_impressions = NULL;
		}
		if($this->input->post('c_end') == "")
		{
			$c_end_date = NULL;
		}
		
		$campaign_modify = $this->tradedesk_model->update_campaign_details($access_token, $ttd_campaign, $c_impressions, $c_budget, $c_start_date, $c_end_date);
		if($campaign_modify == FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Tradedesk failed to update the campaign. Verify the campaign was set up properly.";
			echo json_encode($return);
			return; 
		}
		
		$this->tradedesk_model->update_timestamp_for_campaign($campaign_id);
		$return['success'] = TRUE;
		echo json_encode($return);
		return;
		
    }
    
    
    public function is_ttd_campaign_end_date_wrong($c_id, $vl_end_date)
    {
		
		$return['success'] = TRUE;
		$ttd_campaign_id = $this->tradedesk_model->get_ttd_campaign_by_campaign($c_id);
		if($ttd_campaign_id == FALSE)
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "TTD Campaign ID for this campaign not found.";
			echo json_encode($return);
			return;
		}
		
		$access_token = $this->tradedesk_model->get_access_token();
		$campaign_data = $this->tradedesk_model->get_info($access_token, "campaign", $ttd_campaign_id);
		$decoded = json_decode($campaign_data);
		if(property_exists($decoded, 'Message'))
		{
			$return['success'] = FALSE;
			$return['err_msg'] = "Failed to retrieve campaign data from TTD.";
			echo json_encode($return);
			return;
		}
		
		$ttd_date = substr($decoded->EndDate, 0, strpos($decoded->EndDate, "T"));
	
	
		$return['success'] = TRUE;
		echo json_encode($return);
    }
    
    private function upload_facebook_data($access_token, $report_date)
    {
		$facebook_advertisers = $this->tradedesk_model->get_list_of_facebook_advertisers_and_adgroups();
		$affected_adgroups = array();
		$return_array = array(
			'is_success' => true,
			'db_stats' => null,
			'num_city_rows' => 0,
			'num_site_rows' => 0,
			'num_size_rows' => 0,
			'affected_adgroups' => null,
			'errors' => array()
		);

		if($facebook_advertisers)
		{
			foreach ($facebook_advertisers as $ttd_adv_id => $adgroup_ids)
			{
				$affected_adgroups = array_merge($affected_adgroups, $adgroup_ids);
				$hd_report_list = $this->tradedesk_model->get_hd_reports_of_advertiser_for_day($access_token, $ttd_adv_id, $report_date, false);
				if($hd_report_list['success'])
				{
					foreach($hd_report_list['result'] as $report)
					{
						if($report->Type == 'GeoReport')
						{
							$this->tradedesk_model->clear_previous_generic_viewthrough_data($adgroup_ids, $report_date);

							$report_tsv = file_get_contents($report->DownloadUrl);
							$report_arr = explode("\n", $report_tsv);
							$headers = array_shift($report_arr);
							$header_keys = explode("\t", $headers);

							$affected_rows_for_query_data = array();
							foreach($report_arr as $report_line)
							{
								$report_line_arr = explode("\t", $report_line);
								if(!empty(array_filter($report_line_arr)))
								{
									$line = array_combine($header_keys, $report_line_arr);
									if(in_array($line['Ad Group Id'], $adgroup_ids))
									{
										$affected_rows_for_query_data[] = $line;
									}
								}
 							}
							$fb_data = $this->tradedesk_model->insert_facebook_data_in_db($affected_rows_for_query_data, $report_date);
							if(!$fb_data['is_success'])
							{
								$return_array['is_success'] = false;
								$return_array['errors'][] = "Failed to upload Facebook data for advertiser {$ttd_adv_id}";
							}
							else
							{
								$return_array['num_city_rows'] += $fb_data['city_rows'];
								$return_array['num_site_rows'] += $fb_data['site_rows'];
								$return_array['num_size_rows'] += $fb_data['size_rows'];
							}
							continue 2; // Goes to end of next outer loop (advertisers)
						}
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'][] = "HD report failed to download for advertiser {$ttd_adv_id}";
				}
			}
		}

		$return_array['affected_adgroups'] = (!empty($affected_adgroups)) ? $affected_adgroups : null;
		$return_array['db_stats'] = $this->tradedesk_model->get_db_stats_for_facebook_data($report_date, $affected_adgroups);
		return $return_array;
	}

	public function upload_ttd_hdreport_data($report_date = null)
	{
		if($this->input->is_cli_request())
		{
			$this->hdr_log_file = fopen('../log/hdreport_log.log', 'a');
			$stats['info']['start_time'] = "";
			$stats['info']['end_time'] = "";
			$stats['info']['report_date'] = "";
			$stats['info']['elapsed_time'] = "";
			$stats['info']['fatal_error'] = false;
			$stats['metrics']['facebook_data'] = null;
			$stats['metrics']['files_downloaded'] = 0;
			$stats['metrics']['size_downloaded'] = 0;
			$stats['metrics']['raw_geo_rows_uploaded'] = 0;
			$stats['metrics']['raw_site_rows_uploaded'] = 0;
			$stats['metrics']['raw_video_rows_uploaded'] = 0;
			$stats['metrics']['raw_geo_conversion_count'] = 0;
			$stats['metrics']['raw_site_conversion_count'] = 0;
			$stats['metrics']['raw_video_start_count'] = 0;
			$stats['metrics']['aggregate_geo_conversion_count'] = 0;
			$stats['metrics']['aggregate_site_conversion_count'] = 0;
			$stats['metrics']['aggregate_video_row_count'] = 0;
			$stats['metrics']['aggregate_video_start_count'] = 0;
			$stats['metrics']['sites_fixed'] = 0;
			$stats['metrics']['geos_fixed'] = 0;
			$stats['metrics']['fix_list_zip_path'] = null;
			$stats['errors'] = array();
			$stats['warnings'] = array();

			$rand_temp_folder = null;
			$report_cached_adgroup_ct=0;

			$this->cli_data_processor_common->mark_script_execution_start_time();
			$stats['info']['start_time'] = $this->cli_data_processor_common->get_start_time_formatted_string();
			try
			{
				$access_token = $this->tradedesk_model->get_access_token();
				if($access_token == false)
				{
					throw(new Exception("Failed to get Tradedesk Access Token\n"));
				}
				if($report_date == null)
				{
					$report_date_result = $this->campaign_health_model->get_last_impression_date();
					$report_date = date('Y-m-d', strtotime($report_date_result[0]['value']));
				}
				else
				{
					if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $report_date))
					{
						echo "USAGE: php index.php tradedesk upload_ttd_hdreport_data YYYY-MM-DD.\n";
						return;
					}
					$data_exists = $this->tradedesk_model->does_data_exist_for_day($report_date);
					if(!$data_exists)
					{
						throw(new Exception("No existing record data found for date {$report_date}\n"));
					}
				}

				$stats['info']['report_date'] = $report_date;

				$advertisers_list = $this->tradedesk_model->get_all_advertisers($access_token);

				$rand_temp_folder = 'hdrtsv_' . rand(1, 10000000);

				fwrite($this->hdr_log_file, "Date to process: {$report_date}\n");
				fwrite($this->hdr_log_file, "Uploaded File Directory: {$rand_temp_folder}\n");

				$folder_made = mkdir("/tmp/{$rand_temp_folder}");
				if(!$folder_made)
				{
					throw(new Exception("Failed to create temp directory for report files\n"));
				}

				$adgroup_folder = "/tmp/{$rand_temp_folder}/adgroupreports";
				$conversions_folder = "/tmp/{$rand_temp_folder}/conversionreports";

				$adgroup_folder_made = mkdir($adgroup_folder);
				$conversions_folder_made = mkdir($conversions_folder);
				if(!$adgroup_folder_made || !$conversions_folder_made)
				{
					throw(new Exception("Failed to create adgroup and/or conversion subfolders\n"));
				}

				foreach($advertisers_list as $advertiser_entry)
				{
					$advertiser_id = $advertiser_entry->AdvertiserId;
					fwrite($this->hdr_log_file, "{$advertiser_id}\n");
					$access_token = $this->tradedesk_model->get_access_token();
					if($access_token == false)
					{
						throw(new Exception("Failed to get Tradedesk Access Token during advertiser {$advertiser_id}\n"));
					}
					$hd_report_list = $this->tradedesk_model->get_hd_reports_of_advertiser_for_day($access_token, $advertiser_id, $report_date, false);
					if($hd_report_list['success'] == false)
					{
						if(strpos($hd_report_list['err_msg'], 'are not authorized to access Advertiser'))
						{
							fwrite($this->hdr_log_file, "Not Authorized to get HDReport list for advertiser {$advertiser_id} ({$hd_report_list['err_msg']})\n");
							$stats['errors'][] = "Not Authorized to get HDReport list for advertiser {$advertiser_id} ({$hd_report_list['err_msg']})";
							continue;
						}
						else
						{
							$stats['errors'][] = "Failed to get Tradedesk HDReport list for advertiser {$advertiser_id} ({$hd_report_list['err_msg']})";
							continue;
						}
					}

					if(count($hd_report_list['result']) == 0)
					{
					//No Reports found for day for advertiser
						continue;
					}

					$has_conversions = $hd_report_list['conversions'];

					//Grab specific reports, download them 
					foreach($hd_report_list['result'] as $hd_report)
					{
						if($hd_report->Type == 'AdGroupPerformanceReport' || $hd_report->Type == 'SiteListReport' || $hd_report->Type == 'GeoReport')
						{
							$download_url = $hd_report->DownloadUrl;
							$file_name = urldecode(basename($download_url));
							if(strpos($file_name, '?'))
							{
								$file_name = substr($file_name,0,strpos($file_name, '?'));
							}
							//get file name
							$full_file_path = '';
							if($hd_report->Type == 'SiteListReport' || $hd_report->Type == 'GeoReport')
							{
								// if this advertiser has facebook campaigns, call separate facebook function here
								if($has_conversions)
								{
									$full_file_path = "{$conversions_folder}/{$file_name}";
								}
								else
								{
									continue;
								}
							}
							else if ($hd_report->Type == 'AdGroupPerformanceReport')
							{
								$full_file_path = "{$adgroup_folder}/{$file_name}";
							}
							else
							{
								throw(new Exception("Invalid report type attempted to get processed: {$file_name}\n"));
							}
							$attempts = 0;
							$file_downloaded = false;
							while($attempts < 5 && !$file_downloaded)
							{
								if($attempts > 2)
								{
									fwrite($this->hdr_log_file, "Delaying...\n");
									$did_sleep = sleep(3);
									if($did_sleep === 0)
									{
										fwrite($this->hdr_log_file, "Okay.\n");
									}
									else
									{
										fwrite($this->hdr_log_file, "Delay failed?\n");
									}
								}
								$file_downloaded = file_put_contents($full_file_path, fopen($download_url, 'r'));
								$attempts++;
							}
							if(!$file_downloaded)
							{
								throw(new Exception("Failed to download file {$file_name}\n"));
							}
							$stats['metrics']['files_downloaded'] += 1;
							$stats['metrics']['size_downloaded'] += filesize($full_file_path);
						}
					}
				}

				if($stats['metrics']['files_downloaded'] < 1)
				{
					throw(new Exception("No conversion data found\n"));
				}

				//Clear raw tables
				fwrite($this->hdr_log_file,"Clearing raw tables...\n");
				$clear_raw_tables_result = $this->tradedesk_model->clear_raw_hdreport_tables();
				if($clear_raw_tables_result == false)
				{
					throw(new Exception("Failed to delete raw HD report data\n"));
				}
				fwrite($this->hdr_log_file,"Raw tables cleared.\n");

				fwrite($this->hdr_log_file,"Reading report files...\n");
				//Get all files in directory and process them
				$video_data = $this->tradedesk_model->translate_hd_report_from_directory_to_data_arrays($adgroup_folder, "video");
				$conversion_data = $this->tradedesk_model->translate_hd_report_from_directory_to_data_arrays($conversions_folder, "conversions");
				if($conversion_data == false || $video_data == false)
				{
					throw(new Exception("Failed to compile HD report data for raw data insertion\n"));
				}
				$stats['metrics']['raw_geo_rows_uploaded'] = count($conversion_data['geo_data']);
				$stats['metrics']['raw_site_rows_uploaded'] = count($conversion_data['sites_data']);
				$stats['metrics']['raw_video_rows_uploaded'] = count($video_data['video_data']);

				fwrite($this->hdr_log_file,"Uploading report data to raw tables...\n");
				//Process each data array into raw table 
				$raw_upload_result = $this->tradedesk_model->transfer_tsv_rows_into_raw_for_date(array_merge($video_data, $conversion_data), $report_date);
				if($raw_upload_result == false)
				{
					throw(new Exception("Failed to upload HD report data to raw database tables\n"));
				}

				fwrite($this->hdr_log_file, "Correcting identifiers for raw data...\n");
				//Data fixing for city/site values
				$resolve_mismatches_result = $this->tradedesk_model->resolve_raw_data_mismatches();
				if($resolve_mismatches_result == false)
				{
					throw(new Exception("Failed to resolve raw data identifier mismatches"));
				}
				$stats['metrics']['sites_fixed'] = $resolve_mismatches_result['site_fixes'];
				$stats['metrics']['geos_fixed'] = $resolve_mismatches_result['geo_fixes'];
				$site_fix_list = $resolve_mismatches_result['sites_fixed_list'];
				$geo_fix_list = $resolve_mismatches_result['geos_fixed_list'];
				$fix_list_paths = array();
				if($stats['metrics']['sites_fixed'] > 0 )
				{
					$site_file_path = "/tmp/{$rand_temp_folder}/site_fix_list.txt";
					$did_write_site_file = $this->tradedesk_model->print_conversion_list_to_file($site_fix_list, $site_file_path);
					if(!$did_write_site_file)
					{
						$stats['warnings'][] = 'Failed to write site fix list file.';
					}
					else
					{
						array_push($fix_list_paths, 'site_fix_list.txt');
					}
				}
				if($stats['metrics']['geos_fixed'] > 0)
				{
					$geo_file_path = "/tmp/{$rand_temp_folder}/geo_fix_list.txt";
					$did_write_geo_file =$this->tradedesk_model->print_conversion_list_to_file($geo_fix_list, $geo_file_path);
					if(!$did_write_geo_file)
					{
						$stats['warnings'][] = 'Failed to write geo fix list file.';
					}
					else
					{
						array_push($fix_list_paths, 'geo_fix_list.txt');
					}
				}

				//Zip files
				$zip_file = new ZipArchive();
				$zip_filename = "/tmp/{$rand_temp_folder}/conversion_fixes.zip";
				$made_zip = $zip_file->open($zip_filename, ZipArchive::CREATE);
				if($made_zip !== true)
				{
					$stats['warnings'][] = 'Failed to create zip for data fix lists.';
				}
				else
				{
					foreach($fix_list_paths as $fix_file_path)
					{
						if($zip_file->addFile("/tmp/{$rand_temp_folder}/{$fix_file_path}", $fix_file_path) == false)
						{
							$stats['warnings'][] = "Failed to add file to conversion fixes zip: {$fix_file_path}";
						}
					}
					$stats['metrics']['fix_list_zip_path'] = $zip_filename;
					$zip_okay = $zip_file->close();
					if($zip_okay !== true)
					{
						$stats['warnings'][] = 'Failed to create zip for data fix lists.';
					}
				}

				fwrite($this->hdr_log_file, "Aggregating conversions data...\n");
				//Aggregation
				$aggregation_result = $this->tradedesk_model->aggregate_viewthroughs_to_records();
				if($aggregation_result == false)
				{
					throw(new Exception("Failed to aggregate conversion data.\n"));
				}

				//Get conversion counts
				$raw_geo_conversion_count = $this->tradedesk_model->get_conversion_count('geos', true);
				$raw_site_conversion_count = $this->tradedesk_model->get_conversion_count('sites', true);
				$raw_video_play_count = $this->tradedesk_model->get_video_start_play_counts(true);
				if($raw_geo_conversion_count === false || $raw_site_conversion_count === false)
				{
					throw(new Exception("Failed to retrieve raw engagement counts.\n"));
				}

				if($raw_geo_conversion_count == 0 || $raw_site_conversion_count == 0)
				{
					throw(new Exception("Files downloaded, but no conversions found.\n"));
				}
				$stats['metrics']['raw_geo_conversion_count'] = $raw_geo_conversion_count;
				$stats['metrics']['raw_site_conversion_count'] = $raw_site_conversion_count;
				$stats['metrics']['raw_video_start_count'] = $raw_video_play_count;

				if($raw_geo_conversion_count == 0 || $raw_site_conversion_count == 0)
				{
					throw(new Exception("Files downloaded, but no conversions found.\n"));
				}
				$stats['metrics']['raw_geo_conversion_count'] = $raw_geo_conversion_count;
				$stats['metrics']['raw_site_conversion_count'] = $raw_site_conversion_count;
				$stats['metrics']['raw_video_start_count'] = $raw_video_play_count;

				$aggregate_geo_conversion_count = $this->tradedesk_model->get_conversion_count('geos', false, $report_date);
				$aggregate_site_conversion_count = $this->tradedesk_model->get_conversion_count('sites', false, $report_date);
				$aggregate_video_row_count = $this->tradedesk_model->get_aggregate_video_row_count_for_date($report_date);
				$aggregate_video_play_count = $this->tradedesk_model->get_video_start_play_counts(false, $report_date);
				if($aggregate_geo_conversion_count === false || $aggregate_site_conversion_count === false)
				{
					throw(new Exception("Failed to retrieve aggregate engagement counts.\n"));
				}
				if($aggregate_geo_conversion_count == 0 || $aggregate_site_conversion_count == 0)
				{
					throw(new Exception("Raw conversions found, but no aggregate conversions found.\n"));
				}
				$stats['metrics']['aggregate_geo_conversion_count'] = $aggregate_geo_conversion_count;
				$stats['metrics']['aggregate_site_conversion_count'] = $aggregate_site_conversion_count;
				$stats['metrics']['aggregate_video_row_count'] = $aggregate_video_row_count;
				$stats['metrics']['aggregate_video_start_count'] = $aggregate_video_play_count;

				$facebook_data = $this->upload_facebook_data($access_token, $report_date);
				$stats['errors'] = array_merge($stats['errors'], $facebook_data['errors']);
				$stats['metrics']['facebook_data'] = $facebook_data;

				$report_cached_adgroup_ct = $this->td_uploader_model->load_report_cached_campaign('DATE_MODE', $report_date, '');
				if (!isset($report_cached_adgroup_ct))
					$report_cached_adgroup_ct = "0";

				fwrite($this->hdr_log_file,"Data upload complete.\n");
			}
			catch(Exception $e)
			{
				$stats['errors'][] = $e->getMessage();
				$stats['info']['fatal_error'] = true;
			}
			$this->cli_data_processor_common->mark_script_execution_end_time();
			//Build and send email
			$email_sent = $this->send_hdreport_uploader_email($stats, 'tech.logs@frequence.com', $report_cached_adgroup_ct);
			if($email_sent == false)
			{
				echo "Email failed to send!!!!!\n";
			}

			if($rand_temp_folder != null)
			{
				if($folder_made)
				{
					$delete_reports = null;
					system("rm -rf /tmp/{$rand_temp_folder}", $delete_reports);
					if(!($delete_reports === 0))
					{
						echo "Failed to delete temp directory /tmp/{$rand_temp_folder}\n";
					}
				}
			}
		}
		else
		{
			show_404();
		}
	}

	private function send_hdreport_uploader_email($stats, $to, $report_cached_adgroup_ct)
	{
		$subject = "HDReport Uploader: Processed {$stats['info']['report_date']} ";
		$num_errors = count($stats['errors']);
		$num_warnings = count($stats['warnings']);
		$error_string = ($num_errors == 1) ? '1 error' : "{$num_errors} errors";
		$warning_string = ($num_warnings == 1) ? '1 warning' : "{$num_warnings} warnings";
		$failed = $stats['info']['fatal_error'];
		$fixes_noted = false;

		if($failed)
		{
			$subject .= ' - Failed';
		}
		else
		{
			$subject .= ' - Completed';
		}
		if($num_errors > 0 || $num_warnings > 0)
		{
			if($num_errors > 0 && $num_warnings > 0)
			{
				$subject .= " with {$error_string} and {$warning_string}";
			}
			else
			{
				if($num_errors > 0)
				{
					$subject .= " with {$error_string}";
				}
				if($num_warnings > 0)
				{
					$subject .= " with {$warning_string}";
				}
			}
		}
		else if(!$failed)
		{
			$subject .= ' successfully';
		}

		$message = '';
		$message .= "----HDReport Uploader---- \n";
		$message .= "Date Processed: {$stats['info']['report_date']}\n";
		if($failed)
		{
			$message .= "Failed \n\n";
		}
		else
		{
			$message .= "Succeeded \n\n";
		}
		$message .= ucwords($error_string) . "\n";
		$message .= ucwords($warning_string) . "\n\n";

		$message .= "Start Time: {$stats['info']['start_time']}\n";
		$message .= $this->cli_data_processor_common->get_script_execution_time_message()."\n\n";

		$message .= $this->cli_data_processor_common->get_environment_message();
		if(!$stats['info']['fatal_error'])
		{
			$message .= "\n---------------------------------------------\n";
			$message .= "HDReport Files Downloaded: {$stats['metrics']['files_downloaded']} (" . number_format($stats['metrics']['size_downloaded']) . " bytes)\n";
			$message .= "Raw Geo Data: {$stats['metrics']['raw_geo_rows_uploaded']} rows ({$stats['metrics']['raw_geo_conversion_count']})\n";
			$message .= "Raw Site Data: {$stats['metrics']['raw_site_rows_uploaded']} rows ({$stats['metrics']['raw_site_conversion_count']})\n";
			$message .= "Raw Video Data: {$stats['metrics']['raw_video_rows_uploaded']} rows ({$stats['metrics']['raw_video_start_count']} plays)\n";
			$message .= "Geo Data Corrections: {$stats['metrics']['geos_fixed']}\n";


			if($stats['metrics']['geos_fixed'] > 0)
			{
				$message .= " - Attached file has specifics\n";
				$fixes_noted = true;
			}

			$message .= "Site Data Corrections: {$stats['metrics']['sites_fixed']}\n";
			
			if($stats['metrics']['sites_fixed'] > 0)
			{
				$message .= " - Attached file has specifics\n";
				$fixes_noted = true;
			}

			$message .= "Aggregate Viewthroughs: {$stats['metrics']['aggregate_geo_conversion_count']} Geo - {$stats['metrics']['aggregate_site_conversion_count']} Site ";

			if($stats['metrics']['aggregate_geo_conversion_count'] == $stats['metrics']['aggregate_site_conversion_count'])
			{
				$message .= '(OK!)';
			}
			else
			{
				$message .= '(NOT OK!)';
			}

			$message .= "\n";
			$message .= "Aggregate Video Rows: {$stats['metrics']['aggregate_video_row_count']} rows ({$stats['metrics']['aggregate_video_start_count']} plays)\n";

			$message .= "\n";

			if(!empty($stats['metrics']['facebook_data']) && $stats['metrics']['facebook_data']['num_city_rows'] > 0)
			{
				$message .= "Facebook Data Results:\n";
				$message .= "\tRows Added: City - {$stats['metrics']['facebook_data']['num_city_rows']}, ";
				$message .= "Site - {$stats['metrics']['facebook_data']['num_site_rows']}, ";
				$message .= "Adsize - {$stats['metrics']['facebook_data']['num_size_rows']}\n";

				$fb_city_imps = $stats['metrics']['facebook_data']['db_stats']['num_city_impressions'];
				$fb_site_imps = $stats['metrics']['facebook_data']['db_stats']['num_site_impressions'];
				$fb_size_imps = $stats['metrics']['facebook_data']['db_stats']['num_size_impressions'];
				$fb_city_clks = $stats['metrics']['facebook_data']['db_stats']['num_city_clicks'];
				$fb_site_clks = $stats['metrics']['facebook_data']['db_stats']['num_site_clicks'];
				$fb_size_clks = $stats['metrics']['facebook_data']['db_stats']['num_size_clicks'];
				$fb_city_vts = $stats['metrics']['facebook_data']['db_stats']['num_city_viewthroughs'];
				$fb_site_vts = $stats['metrics']['facebook_data']['db_stats']['num_site_viewthroughs'];

				$impressions_okay_str = ((int)$fb_city_imps == (int)$fb_site_imps && (int)$fb_site_imps == (int)$fb_size_imps) ? ' - (OK!)' : ' - (NOT OK!)';
				$clicks_okay_str = ((int)$fb_city_clks == (int)$fb_site_clks && (int)$fb_site_clks == (int)$fb_size_clks) ? ' - (OK!)' : ' - (NOT OK!)';
				$viewthroughs_okay_str = ((int)$fb_city_vts == (int)$fb_site_vts) ? ' - (OK!)' : ' - (NOT OK!)';

				$message .= "\tImpressions Added: City {$fb_city_imps}, Site {$fb_site_imps}, Adsize {$fb_size_imps}{$impressions_okay_str}\n";
				$message .= "\tClicks Added: City {$fb_city_clks}, Site {$fb_site_clks}, Adsize {$fb_size_clks}{$clicks_okay_str}\n";
				$message .= "\tViewthroughs Added: City {$fb_city_vts}, Site {$fb_site_vts}{$viewthroughs_okay_str}\n";
			}

		}
		$message .= "\n";
		if($num_errors > 0)
		{
			$message .= "Uploader encountered the following errors:\n";
			foreach($stats['errors'] as $error)
			{
				$message .= "{$error}\n";
			}
			$message .= "\n";
		}
		if($num_warnings > 0)
		{
			$message .= "Uploader encountered the following warnings:\n";
			foreach($stats['errors'] as $error)
			{
				$message .= "{$error}\n";
			}
			$message .= "\n";
		}

		$message .= "Total Records loaded in report_cached_adgroup_date with HD: {$report_cached_adgroup_ct}.\n";

		if($stats['metrics']['fix_list_zip_path'] != null && $fixes_noted)
		{
			$result = mailgun(
				'HDReport Uploader <tech.logs@frequence.com>',
				$to,
				$subject,
				$message,
				'text',
				process_attachments_for_email(array($stats['metrics']['fix_list_zip_path']))
			);
		}
		else
		{
			$result = mailgun(
				'HDReport Uploader <tech.logs@frequence.com>',
				$to,
				$subject,
				$message,
				'text'
			);
		}
		if($result === true)
		{
			fwrite($this->hdr_log_file, $message);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function update_pc_adgroup_impression_target()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");
		
		if($verify_ajax_response['is_success'] === true)
		{
			$pc_adgroup_id = $this->input->post('adgroup_id');
			$campaign_id = $this->input->post('campaign_id');
			$impression_target = $this->input->post('new_target');
			$non_pc_realized = $this->input->post('non_pc_realized');
			$access_token = $this->tradedesk_model->get_access_token();
				
			if($pc_adgroup_id !== false && ctype_digit($impression_target) && ctype_digit($campaign_id) && ctype_digit($non_pc_realized))
			{
				$campaign = $this->tank_auth->get_campaign_details_by_id($campaign_id);
					
				if($campaign !== NULL)
				{
					if(!empty($campaign['ttd_campaign_id']) && $campaign['cached_city_record_cycle_impression_sum'] !== false)
					{
						$access_token = $this->tradedesk_model->get_access_token();
						$adgroup_impression_info = $this->tradedesk_model->get_adgroup_daily_target_totals($access_token, $campaign['ttd_campaign_id'], $pc_adgroup_id);
						
						$ttd_budget_numbers = $this->tradedesk_model->get_ttd_budget_numbers();
						if($ttd_budget_numbers !== false)
						{
							$pc_impression_dollar_ratio = $ttd_budget_numbers['tolerable_cpm_max_pc']/1000;
						
							if(!empty($adgroup_impression_info) && $adgroup_impression_info['pc_adgroup_info'] !== false)
							{
								$pc_adgroup_info = $adgroup_impression_info['pc_adgroup_info'];

								$ttd_pc_daily_impression_budget = $pc_adgroup_info->RTBAttributes->DailyBudgetInImpressions;
								$new_ttd_pc = $impression_target - $non_pc_realized;
							
								if($new_ttd_pc > 0) //modification will reduce/increase but not 0 or below
								{
								
									$daily_dollar_budget = $new_ttd_pc * $pc_impression_dollar_ratio;
									$ttd_update_response = $this->tradedesk_model->update_adgroup_enabled_flag_and_daily_impression_target($access_token, $pc_adgroup_id, true, $new_ttd_pc, $daily_dollar_budget);
									if(!($this->tradedesk_model->update_timestamp_for_adgroup($pc_adgroup_id) and $this->tradedesk_model->update_timestamp_for_campaign($campaign_id)))
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Error 99407: Bidder impression target update successful but failed to update adgroup timestamp";
									}
								}
								else
								{
									if($new_ttd_pc == $ttd_pc_daily_impression_budget or ($pc_adgroup_info->IsEnabled === false and (int)$impression_target === 0))
									{
										//either there was no change in the impression budget or the adgroup is already disabled
									}
									else
									{
										$disabled_daily_impressions = 1;
										$disabled_daily_dollars = $disabled_daily_impressions * $pc_impression_dollar_ratio;
										$ttd_update_response = $this->tradedesk_model->update_adgroup_enabled_flag_and_daily_impression_target($access_token, $pc_adgroup_id, false, $disabled_daily_impressions, $disabled_daily_dollars);
										if(!($this->tradedesk_model->update_timestamp_for_adgroup($pc_adgroup_id) and $this->tradedesk_model->update_timestamp_for_campaign($campaign_id)))
										{
											$return_array['is_success'] = false;
											$return_array['errors'] = "Error 99405: Bidder impression target update successful but failed to update adgroup/campaign timestamp";
										}
									}
								}

								//get info for updating miniform warnings
								
								$return_array['pc_warning_data'] = array();
								
								$pc_adgroup_data = $this->campaign_health_model->get_pc_adgroup_by_campaign_id($campaign_id);
								if($pc_adgroup_data !== false)
								{
									$pc_ttd_response = $this->tradedesk_model->get_adgroup_daily_target_totals($access_token, $campaign['ttd_campaign_id'], $pc_adgroup_data['ID']);
									if(!empty($pc_ttd_response))
									{
										if($pc_adgroup_data['cached_city_record_yday_impression_sum'] === null)
										{
											$pc_adgroup_data['cached_city_record_yday_impression_sum'] = 0;
										}
								
										$return_array['pc_warning_data'] = array(
											'total_target' => $pc_ttd_response['total_target'],
											'non_pc_target_impressions' => $pc_ttd_response['non_pc_target'],
											'pc_daily_impression_realized' => $pc_adgroup_data['cached_city_record_yday_impression_sum'],
											'pc_daily_impression_budget' => $pc_ttd_response['pc_adgroup_info']->RTBAttributes->DailyBudgetInImpressions);
									}
									else
									{
										$return_array['is_success'] = false;
										$return_array['errors'] = "Warning 64175: Update complete but unable to retrieve updated PC Adgroup data";
									}
								}
								else
								{
									$return_array['is_success'] = false;
									$return_array['errors'] = "Warning 64133: Update complete but unable to display PC Adgroup warnings";
								}
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 61909: Failed to retrieve PC Adgroup data from bidder";
							}
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 62257: Failed to retrieve dollar/impression ratio";
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 62102: No bidder data found for campaign"; 
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 62105: Could not retrieve campaign data";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 99202: Invalid parameters sent to update pc adgroup function";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 99131: User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	public function save_site_list()
	{
		$return_array = array();
		$return_array['success'] = true;
		$return_array['err_msg'] = "";

		//Validate inputs from ajax call
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('site_list_name','raw_site_list', 'campaign_id');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if(!$ajax_verify['is_success'])
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "invalid request";
			echo json_encode($return_array);
			return;
		}

		$site_list_name = $ajax_verify['post']['site_list_name'];
		$raw_site_list_string = $ajax_verify['post']['raw_site_list'];
		$campaign_id = $ajax_verify['post']['campaign_id'];

		//Get TTD Advertiser
		$access_token = $this->tradedesk_model->get_access_token();
		$ttd_advertiser_id = $this->tradedesk_model->get_ttd_advertiser_by_campaign($campaign_id);
		if($ttd_advertiser_id === false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Unable to get campaign's ttd advertiser id";
			echo json_encode($return_array);
			return;
		}

		//Create sitelist on TTD from the stuff passed in ajax request
		$saved_sitelist_result = $this->tradedesk_model->create_sitelist_for_advertiser($access_token, $raw_site_list_string, $ttd_advertiser_id, $site_list_name);
		if(!$saved_sitelist_result['success'])
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Failed to create sitelist in tradedesk";
			echo json_encode($return_array);
			return;
		}

		//Grab the old sitelist out of the database in case we need to remove an existing sitelist
		$old_sitelist_id = null;
		$campaign_ttd_linkage_details = $this->tradedesk_model->grab_ttd_details_for_campaign($campaign_id);
		if($campaign_ttd_linkage_details === false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Failed to grab ttd campaign linkage data";
			echo json_encode($return_array);
			return;
		}
		if($campaign_ttd_linkage_details && $campaign_ttd_linkage_details['ttd_sitelist_id'] != null)
		{
			$old_sitelist_id = $campaign_ttd_linkage_details['ttd_sitelist_id'];
		}

		//Grab adgroups and update them all
		$adgroups = $this->tradedesk_model->get_ttd_adgroups_by_campaign($campaign_id);
		if($adgroups != false)
		{
			$error_adgroups = false;
			$error_message_adgroups = "Failed to updates for adgroups ";
			foreach($adgroups as $adgroup)
			{
				$did_update_adgroup = $this->tradedesk_model->add_sitelist_to_adgroup($access_token, $saved_sitelist_result['sitelist_id'], $adgroup['ID'], $old_sitelist_id, $adgroup['target_type']);
				if($did_update_adgroup == false)
				{
					$error_adgroups = true;
					$error_message_adgroups .= $adgroup['ID']." ";
				}
			}
			if($error_adgroups)
			{
				$return_array['success'] = false;
				$return_array['err_msg'] = $error_message_adgroups;
				echo json_encode($return_array);
				return;				
			}
		}
		else 
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Adgroups to update not found";
			echo json_encode($return_array);
			return;			
		}

		//Update the data regarding the sitelist in the database
		$update_ttd_campaign_data = $this->tradedesk_model->save_sitelist_to_database_for_campaign($saved_sitelist_result['sitelist_id'], $site_list_name, $raw_site_list_string, $campaign_id);
		if($update_ttd_campaign_data == false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "failed to update ttd campaign data";
			echo json_encode($return_array);
			return;					
		}
		
		echo json_encode($return_array);
	}



	public function save_zip_list()
	{
		$return_array = array();
		$return_array['success'] = true;
		$return_array['err_msg'] = "";

		//Validate inputs from ajax call
		$allowed_user_types = array('ops', 'admin');
		$required_post_variables = array('raw_zip_list', 'campaign_id');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
		if(!$ajax_verify['is_success'])
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "invalid request";
			echo json_encode($return_array);
			return;
		}

		$raw_zip_list_string = $ajax_verify['post']['raw_zip_list'];
		$campaign_id = $ajax_verify['post']['campaign_id'];

		//Get TTD Advertiser
		$access_token = $this->tradedesk_model->get_access_token();
		$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
		$ttd_advertiser_id = $this->tradedesk_model->get_ttd_advertiser_by_campaign($campaign_id);
		if($ttd_advertiser_id === false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Unable to get campaign's ttd advertiser id";
			echo json_encode($return_array);
			return;
		}

		$geosegment_list_result = $this->tradedesk_model->get_geosegment_list_from_zip_list($access_token, $raw_zip_list_string);
		if($geosegment_list_result === false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Failed to query ttd geos";
			echo json_encode($return_array);
			return;			
		}
		
		$geosegment_id_list = $geosegment_list_result['geo_segment_ids'];
		$bad_zips = $geosegment_list_result['bad_zips'];

		if(!empty($bad_zips))
		{
			$raw_zip_list_string = str_replace($bad_zips, "", $raw_zip_list_string);
			$raw_zip_list_string = str_replace(array("  ", ",,", "\n\n"), "", $raw_zip_list_string);
		}

		//Grab adgroups and update them all
		$adgroups = $this->tradedesk_model->get_ttd_adgroups_by_campaign($campaign_id);
		if($adgroups != false)
		{
			$error_adgroups = false;
			$error_message_adgroups = "Failed to update for adgroups ";
			foreach($adgroups as $adgroup)
			{
				$did_update_adgroup = $this->tradedesk_model->add_geosegment_list_to_adgroup($access_token, $adgroup['ID'], $geosegment_id_list);
				if($did_update_adgroup == false)
				{
					$error_adgroups = true;
					$error_message_adgroups .= $adgroup['ID']." ";
				}
			}
			if($error_adgroups)
			{
				$return_array['success'] = false;
				$return_array['err_msg'] = $error_message_adgroups;
				echo json_encode($return_array);
				return;				
			}
		}
		else 
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "Adgroups to update not found";
			echo json_encode($return_array);
			return;			
		}

		//Update the data regarding the sitelist in the database
		$update_ttd_campaign_data = $this->tradedesk_model->save_zip_lists_to_database_for_campaign($raw_zip_list_string, $bad_zips, $campaign_id);
		if($update_ttd_campaign_data == false)
		{
			$return_array['success'] = false;
			$return_array['err_msg'] = "failed to update ttd campaign data";
			echo json_encode($return_array);
			return;					
		}

		$return_array['bad_zips'] = $bad_zips;

		echo json_encode($return_array);
	}

	public function get_tag_file_name($tag_file_id)
	{
		//Find tag file name for tag file id. If name contains directory name, remove the directory name.
		$tag_file_name = $this->tag_model->get_tracking_tag_file_name_by_id($tag_file_id);
		if (!isset($tag_file_name) || $tag_file_name == null)
		{
			//If not able to find tag file name for some reason, use tag file id as it's name.
			$tag_file_name = $tag_file_id;
		}
		elseif(strpos($tag_file_name,"/") > 0)
		{
			$tag_file_name = substr($tag_file_name,strpos($tag_file_name,"/")+1);
			
			if (strpos($tag_file_name,'.js') > 0)
			{
				$tag_file_name = str_replace(".js","",$tag_file_name);
			}
		}
		return $tag_file_name;
	}

	public function retrieve_geosegments_for_zips()
	{
		if($this->input->is_cli_request())
		{
			$did_save_geosegments = $this->tradedesk_model->grab_and_save_zips_for_geosegments();
			if($did_save_geosegments === false)
			{
				echo "Failed to save anything\n";
			}
			else
			{
				echo "SUCCESS: Saved/Updated {$did_save_geosegments} geosegments\n";
			}
		}
	}


}




?>
