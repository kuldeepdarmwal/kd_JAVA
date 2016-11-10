<?php
class Bulk_campaign_upload extends CI_Controller
{
    private $adgroup_types = array("pc", "mobile_320", "mobile_no_320", "tablet", "rtg");
	private $pre_roll_adgroup_types = array("pre_roll", "rtg");
    
    
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url'));
		$this->load->helper('ad_server_type');
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->model('bulk_campaigns_operations_model');
		$this->load->model('al_model');
		$this->load->model('cup_model');
		$this->load->model('tag_model');
		$this->load->model('dfa_model');
		$this->load->model('publisher_model');
		$this->load->model('tradedesk_model');
		$this->load->model('all_ios_model');
		$this->load->helper('tradedesk_helper');
		$this->load->helper('url_helper');
	}

	public $g_csv_array = array();

	public function index()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','bulk_campaign_upload');
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

			$data['active_menu_item'] = "bulk_campaign_upload_menu";//the view will make this menu item active
			$data['title'] = "Bulk Campaign Uploader";

			$this->form_validation->set_rules('userfile', 'File', 'callback_check_file');
			if ($this->form_validation->run() == FALSE)
			{
				$this->load->view('ad_linker/header',$data);
				$this->load->view('ad_linker/bulk_campaign_form_body_view',$data);
			}
			else
			{
				$file_contents = $this->g_csv_array;
				$validation_result_array = array();
				$array_of_new_campaigns = array();
				$inputs_valid = true;
				foreach($file_contents['array_data'] as $ii=>$row)
				{
					$row['cmpn_name'] = str_replace("/","-",$row['cmpn_name']); //remove slashes with '-'
					$is_pre_roll = false;
					///1) check that advertiser id exists in VL and TTD
					////////////////////////////////////////
					if(isset($row['vl_adv_id']) && $this->bulk_campaigns_operations_model->does_advertiser_exist($row['vl_adv_id']))
					{
						$validation_result_array[$ii]['vl_adv_id'] = true; 
					}
					else
					{
						$validation_result_array[$ii]['vl_adv_id'] = false; 
						$inputs_valid = false;
						if(!isset($row['vl_adv_id']))
						{
							$file_contents['array_data'][$ii]['vl_adv_id'] = 'N/A';
						}
						if(!isset($row['cmpn_name']))
						{
							$file_contents['array_data'][$ii]['cmpn_name'] = 'N/A';
						}
						continue;
					}
					///2) check that new campaign name is ok
					////////////////////////////////////////
					if(isset($row['cmpn_name']) && $this->bulk_campaigns_operations_model->is_campaign_name_unique_for_advertiser($row['vl_adv_id'],$row['cmpn_name']))
					{
						//also should check that no two CSV rows have repeated advertiser::campaign
						if(!isset($array_of_new_campaigns[$row['vl_adv_id']]))
						{
							$array_of_new_campaigns[$row['vl_adv_id']] = array();
						}

						if(in_array($row['cmpn_name'],$array_of_new_campaigns[$row['vl_adv_id']]))
						{
							$validation_result_array[$ii]['cmpn_name'] = false; 
							$inputs_valid = false;
							continue;
						}
						else
						{
							$array_of_new_campaigns[$row['vl_adv_id']][] = $row['cmpn_name']; 
							$validation_result_array[$ii]['cmpn_name'] = true; 
						}
						$validation_result_array[$ii]['cmpn_name'] = true; 
					}
					else
					{
						$validation_result_array[$ii]['cmpn_name'] = false; 
						$inputs_valid = false;
						if(!isset($row['cmpn_name']))
						{
							$file_contents['array_data'][$ii]['cmpn_name'] = 'N/A';
						}
						continue;
					}
					///3) check that landing page url is clean
					////////////////////////////////////////
					if(isset($row['is_pre_roll']))
					{
						if($row['is_pre_roll'] == "1" || strtolower($row['is_pre_roll']) == "true")
						{
							$is_pre_roll = true;
						}
					}
					
					if(isset($row['landing_page']))
					{
						$landing_pages = $this->get_landing_pages_from_landing_pages_string($row['landing_page']);
						$num_landing_pages = count($landing_pages);
						$adsets = array();
						$num_adsets = 0;
						if($is_pre_roll)
						{
							$num_adsets = $num_landing_pages;
						}
						else
						{
							if(isset($row['vl_adset_v_id']))
							{
								$adsets = explode(';', $row['vl_adset_v_id']);
								$num_adsets = count($adsets);
							}
						}

						if($num_landing_pages == $num_adsets)
						{
							$are_urls_clean = true;
							foreach($landing_pages as $landing_page)
							{
								if(!$this->is_url_clean($row['landing_page']))
								{
									$are_urls_clean = false;
									break;
								}
							}

							if($are_urls_clean)
							{
								$validation_result_array[$ii]['landing_page'] = true;
							}
							else
							{
								$validation_result_array[$ii]['landing_page'] = false; 
								$inputs_valid = false;
								continue;
							}
						}
						else
						{
							$validation_result_array[$ii]['landing_page'] = false; 
							$inputs_valid = false;
							continue;
						}
					}
					else
					{
						$validation_result_array[$ii]['landing_page'] = false; 
						$inputs_valid = false;
						continue;
					}

					///4) check that target impressions is a number
					////////////////////////////////////////
					if(isset($row['target_k_imprs']) && is_numeric($row['target_k_imprs']))
					{
						$validation_result_array[$ii]['target_k_imprs'] = true; 
					}
					else
					{
						$validation_result_array[$ii]['target_k_imprs'] = false; 
						$inputs_valid = false;
						continue;
					}
					///5) if populated check that end date is in the future (handle GMT)
					////////////////////////////////////////

					if( isset($row['end_date']) && $this->is_valid_date($row['end_date']))
					{
						$current_db_time = $this->bulk_campaigns_operations_model->get_db_current_time();
						$now = new DateTime($current_db_time[0]['time_now']);
						$end = new DateTime($row['end_date']);
						$interval = $now->diff($end);
						//$days = (($interval->invert == 1)? -1 : 1)* $interval->days;
						$in_past = ($interval->invert == 1);
						if($in_past)
						{
							$validation_result_array[$ii]['end_date'] = false ;
							$inputs_valid = false;
							continue;
						}
						else
						{
							$validation_result_array[$ii]['end_date'] = true;
						}
						
					}
					elseif(isset($row['end_date']) && $row['end_date']=='')
					{
						///6) if end date is blank monthly, broadcast monthly, or month-end campaigns are ok
						//set it to be null for the insert
						$file_contents['array_data'][$ii]['end_date'] = NULL;
						////////////////////////////////////////
					}
					else 
					{

						$validation_result_array[$ii]['end_date'] = false;
						$inputs_valid = false;
						continue;
					}


					/////validate start date column
					if( isset($row['start_date']) && $this->is_valid_date($row['start_date']))
					{
						if($row['end_date'] == NULL)//if we have no end date, no need to test for start after end
						{
							$validation_result_array[$ii]['start_date'] = true;
						}
						else
						{
							$end = new DateTime($row['end_date']);
							$start = new DateTime($row['start_date']);
							$interval = $start->diff($end);
							$start_after_end = ($interval->invert == 1);
							if($start_after_end)
							{
								$validation_result_array[$ii]['start_date'] = false ;
								$inputs_valid = false;
								continue;
							}
							else
							{
								$validation_result_array[$ii]['start_date'] = true;
							}
						}
						
					}
					else
					{

						$validation_result_array[$ii]['start_date'] = false;
						$inputs_valid = false;
						continue;
					}

					if(isset($row['invoice_budget']))
					{
						$validation_result_array[$ii]['invoice_budget'] = true;
					}
					else
					{
						$validation_result_array[$ii]['invoice_budget'] = false ;
						$inputs_valid = false;
						continue;
					}


					///7) validate av categories here
					if(!isset($row['ad_verify_categories']))
					{
						$inputs_valid = false;
						$validation_result_array[$ii]['ad_verify_categories'] = false; 
						$file_contents['array_data'][$ii]['ad_verify_categories'] = array();
						continue;
					}
					else
					{
						//try to break up the array and make sure that the categories are numeric
						$category_array = explode(";",$file_contents['array_data'][$ii]['ad_verify_categories']);
						foreach( $category_array as $value ){
							if($value != '' && !is_numeric($value))
							{
								$inputs_valid = false;
								$validation_result_array[$ii]['ad_verify_categories'] = false; 
								continue; //once we find one non-numeric we'll kick out of the foreach
							}
						}
					}

					if(!$inputs_valid)//rather than using "continue 2" above we'll catch the categories error here
					{
						continue;
					}

					//8-ALPHA Check Adgroup weights are there and are not blank
					$adgroup_types = $this->adgroup_types;
					if($is_pre_roll)
					{
						$adgroup_types = $this->pre_roll_adgroup_types;
					}
					
					foreach($adgroup_types as $adgroup_type)
					{
					    if(isset($row[$adgroup_type]) && $row[$adgroup_type] != '')
					    {
							$validation_result_array[$ii][$adgroup_type] = true; 
					    }
					    else
					    {
							$validation_result_array[$ii][$adgroup_type] = false;
							$inputs_valid = false;
							break;
					    }
					    
					}
					if(!$inputs_valid)
					{
					    continue;
					}

					///---VERSION N+
					///8) check that each weight is >= 0
					///9) Check that adgroup weights add to exactly 100%
					//10) check that adset version id exists
					//11) check that geo id is there
					//12) check that sitelist 1 & 2 IDs is in csv
					
					//13) Check if TTD audience ID is in there
					if(isset($row['ttd_audience']) && $row['ttd_audience'] != '')
					{
					    $validation_result_array[$ii]['ttd_audience'] = true; 
					}
					else
					{
					    $validation_result_array[$ii]['ttd_audience'] = false;
					    $inputs_valid = false;
					    continue;
					}
				}
				$data['validation_array'] = $validation_result_array;

				$data['file_data'] = $_FILES;
				$data['data_array'] = $file_contents;

				$data['inputs_valid'] = $inputs_valid;
				if(!$inputs_valid)
				{
					$this->load->library('table');
					$this->table->set_heading('Adv ID','Campaign', 'Error');
					$tmpl = array ('table_open'=> '<table class="table table-hover table-condensed">');
					$this->table->set_template($tmpl);
					//process the validation array to come up with meaningful messages
					foreach($validation_result_array as $row_num=>$row_results)
					{
						$problem_fields[$row_num] = implode('</span><span class="label label-important">',array_keys($row_results,false));
						$this->table->add_row($file_contents['array_data'][$row_num]['vl_adv_id'], $file_contents['array_data'][$row_num]['cmpn_name'], '<span class="label label-important">'.$problem_fields[$row_num].'</span>');
					}
					$data['error_table'] = $this->table->generate();;
					$data['problem_fields'] = $problem_fields;

					$this->load->view('ad_linker/header',$data);
					$this->load->view('ad_linker/bulk_campaign_invalid_inputs_view',$data);
				}
				else
				{
					$this->load->view('ad_linker/header',$data);
					$this->load->view('ad_linker/bulk_campaign_complete_view',$data);
				}

				
			}
		}
		else
		{ 
			redirect('director'); 
		}

	}

	private function get_landing_pages_from_landing_pages_string($landing_pages_string)
	{
		return explode(':?:', $landing_pages_string);
	}

	private function get_base_landing_page_from_landing_pages($landing_pages)
	{
		$landing_page = "";
		if(count($landing_pages) > 1)
		{
			$parsed_url = parse_url($landing_pages[0]);
			if($parsed_url === false)
			{
				$landing_page = false;
			}
			else
			{
				if(array_key_exists('scheme', $parsed_url))
				{
					$landing_page = $parsed_url['scheme'].'://'.$parsed_url['host'];
				}
				else
				{
					$landing_page = '//'.$parsed_url['host'];
				}
			}
		}
		else
		{
			$landing_page = $landing_pages[0];
		}

		return $landing_page;
	}

	public function check_file($file)
	{
		if($_FILES['userfile']['error'] > 0)
		{
			$this->form_validation->set_message('check_file', "No file found ");
			return false;
		}
		elseif(end(explode(".", $_FILES['userfile']['name'])) != 'csv')
		{
			$this->form_validation->set_message('check_file', "CSV file required ");
			return false;
		}
		else
		{
			$this->g_csv_array = $this->csv_to_array();
			$this->form_validation->set_message('check_file', "Unusual CSV format detected ");
			return $this->g_csv_array['is_success'];
		}
		
	}

	private function csv_to_array()
	{
		$array = $fields = array(); 
		$i = 0;
		$handle = @fopen($_FILES['userfile']['tmp_name'], "r");
		if ($handle) {
			while (($row = fgetcsv($handle)) !== false) 
			{
				if (empty($fields)) 
				{
					$fields = $row;
					if(count($fields)<=1)
					{
						return array('is_success'=>false);
					}
					continue;
				}

				foreach ($row as $k=>$value) 
				{
					$array[$i][$fields[$k]] = $value;
				}
				$i++;
			}
			if (!feof($handle)) 
			{
				echo "Error: unexpected fgets() fail\n";
				return array('is_success'=>false);
			}
			fclose($handle);
		}
		return array('is_success'=>true,'array_data'=>$array);
	}

	

	private function verify_ajax_call($allowed_roles,$post_variables = array())
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

	private function is_url_clean($str)
	{
		$pattern = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
		if (!preg_match($pattern, $str))
		{
			return FALSE;
		}
		if(strpos($str, '`'))
		{
		    return FALSE;
		}
		return TRUE;
	}

	private function is_valid_date($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}


	public function load_line_item()
	{
		$allowed_user_types = array('admin','ops');
		$required_post_variables = array('line_item');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types, $required_post_variables);
		if($ajax_verify['is_success'] )
		{
			$operation = "upload";
			$vl_campaign_loaded = false;
			$ttd_campaign_created = false;
			$ttd_adgroups_created = false;
			$vl_ad_groups_loaded = false;
			$ttd_rtg_created = false;
			$vl_rtg_tags_loaded = false;
			$vl_adsets_cloned = false;
			$dfa_adsets_published = 0;
			$ttd_adsets_loaded = 0;
			$ttd_geo_loaded = false;
			$ttd_sitelists_loaded = false;
			
			$force_duplication = false;
			$is_pre_roll = false;
			$no_adverify = false;
		
			$vl_advertiser_id = $ajax_verify['post']['line_item']['vl_adv_id'];
			$landing_pages_string = $ajax_verify['post']['line_item']['landing_page'];
			$landing_pages = $this->get_landing_pages_from_landing_pages_string($landing_pages_string);
			if($landing_pages === false || empty($landing_pages))
			{
				goto load_item_exit;
			}

			$base_landing_page = $this->get_base_landing_page_from_landing_pages($landing_pages);
			if($base_landing_page === false)
			{
				goto load_item_exit;
			}

			$campaign_array = array(
				"Name" => trim($ajax_verify['post']['line_item']['cmpn_name']),
				"business_id" => $vl_advertiser_id,
				"TargetImpressions" => $ajax_verify['post']['line_item']['target_k_imprs'],
				"hard_end_date" => $ajax_verify['post']['line_item']['end_date'] == '' ? NULL : $ajax_verify['post']['line_item']['end_date'],
				"start_date"=>$ajax_verify['post']['line_item']['start_date'],
				"term_type" => null,
				"LandingPage" => $base_landing_page,
				"ignore_for_healthcheck"=>0,
				"invoice_budget"=> (float)$ajax_verify['post']['line_item']['invoice_budget'],
				"categories"=>(empty($ajax_verify['post']['line_item']['ad_verify_categories']))? array() : explode(";",$ajax_verify['post']['line_item']['ad_verify_categories']),
				"pause_date"=>null,
				"cloned_from_campaign_id"=>NULL
			);
			$vl_campaign_id = $this->al_model->create_campaign($campaign_array);
			error_log("campaign id is " . $vl_campaign_id);
			$timeseries_insert_array_new=array();
			$timeseries_insert_array_new[0][0]=date("m/d/Y", strtotime($ajax_verify['post']['line_item']['start_date']));
			$timeseries_insert_array_new[0][1]=$ajax_verify['post']['line_item']['target_1st_imprs']*1000;
			$timeseries_insert_array_new[1][0]=date("m/d/Y", strtotime($ajax_verify['post']['line_item']['end_date']. ' + 1 day'));
			$timeseries_insert_array_new[1][1]="0";
			error_log("after time series..." . $ajax_verify['post']['line_item']['start_date'] . " " . $timeseries_insert_array_new[1][0]);
			$this->al_model->delete_create_time_series_for_campaign($vl_campaign_id, $timeseries_insert_array_new, "EXECUTE");

			if($vl_campaign_id)
			{
				$vl_campaign_loaded = true;
			}
			else
			{
				goto load_item_exit;
			}

			$f_insertion_order_id = null;
			if(!empty($ajax_verify['post']['line_item']['f_insertion_order_id']))
			{
				$f_insertion_order_id = $ajax_verify['post']['line_item']['f_insertion_order_id'];
				
				//Set campaign to be insertion order id
				$updated_campaign_io = $this->all_ios_model->assign_insertion_order_to_campaign($f_insertion_order_id, $vl_campaign_id, true);
				if(!$updated_campaign_io)
				{
					$vl_campaign_loaded = false;
					goto load_item_exit;
				}

			}

			if(!empty($ajax_verify['post']['line_item']['is_pre_roll']))
			{
			    if((strtolower($ajax_verify['post']['line_item']['is_pre_roll']) == "true") || $ajax_verify['post']['line_item']['is_pre_roll'] == "1")
			    {
					$is_pre_roll = true;
			    }
			}
			
			if(!empty($ajax_verify['post']['line_item']['no_adverify']))
			{
			    if((strtolower($ajax_verify['post']['line_item']['no_adverify']) == "true") || $ajax_verify['post']['line_item']['no_adverify'] == "1")
			    {
					$no_adverify = true;
			    }
			}

			$subproduct_data_success = $this->bulk_campaigns_operations_model->create_bulk_subproduct_data_for_campaign($vl_campaign_id, $is_pre_roll);
			if($subproduct_data_success == false)
			{
				$vl_campaign_loaded = false;
				goto load_item_exit;				
			}

			//create TTD campaign and adgroups
			$new_ttd_campaign_result = $this->create_new_ttd_campaign_and_adgroups($ajax_verify['post']['line_item'], $vl_campaign_id, $is_pre_roll, $no_adverify);
			if($new_ttd_campaign_result['is_success'])
			{
				$ttd_campaign_created = true;
				$ttd_adgroups_created = true;
				$vl_ad_groups_loaded = true;
			}
			else
			{
				goto load_item_exit;
			}
			
			$ttd_conversion_tag_id = null;
			if(!empty($ajax_verify['post']['line_item']['ttd_conversion_tag_id']))
			{
				$ttd_conversion_tag_id = $ajax_verify['post']['line_item']['ttd_conversion_tag_id'];
			}
			//Create RTG Tags and save to tags table
			$tags_result = $this->generate_tags_and_save(
				$ajax_verify['post']['line_item']['vl_adv_id'],
				$vl_campaign_id,
				$landing_pages[0],
				$new_ttd_campaign_result['ttd_cmpn_id'],
				$ajax_verify['post']['line_item']['ttd_audience'],
				$ttd_conversion_tag_id,
				$no_adverify
			);
			if($tags_result['is_success'])
			{
			    $ttd_rtg_created = true;
			    $vl_rtg_tags_loaded = true;
			}
			else
			{
				goto load_item_exit;
			}
			
			if(!empty($ajax_verify['post']['line_item']['force_duplication']))
			{
			    if(($ajax_verify['post']['line_item']['force_duplication'] == "true") || $ajax_verify['post']['line_item']['force_duplication'] == "1")
			    {
				$force_duplication = true;
			    }
			}
			
			if(!empty($ajax_verify['post']['line_item']['vl_adset_v_id']) && $is_pre_roll == false)
			{
				//Clone Adsets from list of version ids
				$cloned_adsets_result = $this->bulk_campaigns_operations_model->clone_adset_version_list_for_campaign(
					$ajax_verify['post']['line_item']['vl_adset_v_id'],
					$ajax_verify['post']['line_item']['landing_page'],
					$vl_campaign_id,
					$vl_advertiser_id,
					$operation,
					$force_duplication
				);
				if($cloned_adsets_result['is_success'])
				{
					$vl_adsets_cloned = true;
					$vl_version_ids = $cloned_adsets_result['version_ids'];
				}
				else
				{
					goto load_item_exit;
				}

				$dfa_advertiser_id_to_push = null;
				if(!empty($ajax_verify['post']['line_item']['dfa_advertiser_id']))
				{
					if($ajax_verify['post']['line_item']['dfa_advertiser_id'] != "")
					{
					$dfa_advertiser_id_to_push = $ajax_verify['post']['line_item']['dfa_advertiser_id'];
					}
				}

				//Publish freshly cloned adsets to DFA
				$publish_to_dfa_result = $this->bulk_campaigns_operations_model->publish_multiple_adsets_to_dfa(
					$vl_version_ids, 
					$vl_campaign_id, 
					$ajax_verify['post']['line_item']['vl_adv_id'],
					$landing_pages,
					$dfa_advertiser_id_to_push
				);
				if($publish_to_dfa_result['is_success'])
				{
					$dfa_adsets_published = 1;
				}
				else
				{

					$dfa_adsets_published = -1;
					goto load_item_exit;
				}

				//Link DFA Adsets to appropriate TTD Adgroups
				$link_adsets_to_ttd_result = $this->bulk_campaigns_operations_model->link_versions_array_creatives_to_ttd($vl_version_ids, $vl_campaign_id, "replace");
				if($link_adsets_to_ttd_result['is_success'])
				{
					$ttd_adsets_loaded = 1;
				}
				else
				{
					$ttd_adsets_loaded = -1;
					goto load_item_exit;
				}
			}
			elseif($is_pre_roll == true)
			{
				//Get preroll creative id
				if(!empty($ajax_verify['post']['line_item']['ttd_pr_creative_id']))
				{
					$preroll_ttd_creative_id = $ajax_verify['post']['line_item']['ttd_pr_creative_id'];

					$pushed_creatives = $this->bulk_campaigns_operations_model->push_pre_roll_creative_to_campaign($vl_campaign_id, $preroll_ttd_creative_id);
					if($pushed_creatives)
					{
						$ttd_adsets_loaded = 1;
					}
					else
					{
						$ttd_adsets_loaded = -1;
						goto load_item_exit;
					}
				}
			}
			
			//Link Geo IDs to TTD Adgroups
			$geo_list_success = $this->tradedesk_model->add_geo_id_to_all_adgroups_for_campaign($ajax_verify['post']['line_item']['ttd_geo_id'], $vl_campaign_id);
			if($geo_list_success)
			{
			    $ttd_geo_loaded = true;
			}
			else
			{
			    goto load_item_exit;
			}
			
			
			
			//Link Sitelist IDs to TTD Adgroups
			$sitelist_success = false;
			if(!empty($ajax_verify['post']['line_item']['ttd_sitelist_id_1']))
			{
				$sitelist_success = $this->tradedesk_model->add_bulk_sitelists_to_campaigns_adgroups($ajax_verify['post']['line_item']['ttd_sitelist_id_1'], $vl_campaign_id);
			}

			
			if($sitelist_success)
			{
			    $ttd_sitelists_loaded = true;
			}
			else
			{
			    goto load_item_exit;
			}
			
			
			load_item_exit:

			echo json_encode(array(
				'is_success'=>true,
				'cmpn_name'=>$ajax_verify['post']['line_item']['cmpn_name'],
				'line_item'=>$ajax_verify['post']['line_item'],
				'vl_campaign_loaded'=>$vl_campaign_loaded,
				'ttd_campaign_created'=>$ttd_campaign_created,
				'ttd_campaign_id'=>$new_ttd_campaign_result['ttd_cmpn_id'],
				'ttd_input'=>$new_ttd_campaign_result['ttd_input'],
				'ttd_adgroups_created'=>$ttd_adgroups_created,
				'ttd_rtg_created'=>$ttd_rtg_created,
				'vl_ad_groups_loaded'=>$vl_ad_groups_loaded,
				'vl_rtg_tags_loaded'=>$vl_rtg_tags_loaded,
				'vl_adsets_cloned'=>$vl_adsets_cloned,
				'dfa_adsets_published'=>$dfa_adsets_published,
				'ttd_adsets_loaded'=>$ttd_adsets_loaded,
				'ttd_geo_loaded'=>$ttd_geo_loaded,
				'ttd_sitelists_loaded'=>$ttd_sitelists_loaded));
		}
	}

	private function create_new_ttd_advertiser($advertiser_name, $advertiser_id)
	{
		$is_success = FALSE;
		$access_token = $this->tradedesk_model->get_access_token();
		$td_advertiser_id = $this->tradedesk_model->create_advertiser($access_token, $advertiser_name);
		if($td_advertiser_id == FALSE)
		{
		    goto ttd_adv_exit;
		}
		
		if(!$this->tradedesk_model->add_ttd_id_to_advertiser_with_id($advertiser_id, $td_id))
		{
		    goto ttd_adv_exit;
		}

		$is_success = true;
		
		ttd_adv_exit:
		return array('is_success'=>$is_success); 
		
	}
	private function create_new_ttd_campaign_and_adgroups($line_item, $vl_campaign_id, $is_pre_roll, $no_adverify)
	{
		$is_success = false;
		$ttd_campaign_id = null;

		$tkn = $this->tradedesk_model->get_access_token();
		$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
		$ttd_adv_id = $this->tradedesk_model->get_td_advertiser_id($line_item['vl_adv_id']);
		$start_date = format_start_date($line_item['start_date']);
		$end_date = ($line_item['end_date']=='')? NULL : format_end_date($line_item['end_date']); 
		$ttd_campaign_lifetime_budgets = get_ttd_campain_lifetime_budgets($line_item['target_k_imprs'], $start_date, $end_date);
		$date = new DateTime();//this is to name the campaigns in TTD

		/////MAIN CAMPAIGN
		$ttd_inputs = array('tkn'=> $tkn,
							'access_token_v3' => $access_token_v3,
							'ttd_cmpn_name'=> $line_item['cmpn_name'].' [BU]',
							'ttd_adv_id'=>$ttd_adv_id,
							'ttd_cmpn_dlr_budget'=>$ttd_campaign_lifetime_budgets['ttd_cmpn_dlr_budget'],
							'ttd_imprs'=>$ttd_campaign_lifetime_budgets['ttd_cmpn_impr_budget'],
							'ttd_start'=>$start_date,
							'ttd_end'=> $end_date );
		
		//BUDGET CALCULATIONS
		
		//Weights specified in csv
		if($is_pre_roll)
		{
			$daily_budget_weight_array['pre_roll_weight'] = (float)$line_item['pre_roll'];
			$daily_budget_weight_array['rtg_pre_roll_weight'] = (float)$line_item['rtg'];
		}
		else
		{
			$daily_budget_weight_array['pc_weight'] = (float)$line_item['pc'];
			$daily_budget_weight_array['mobile_320_weight'] = (float)$line_item['mobile_320'];
			$daily_budget_weight_array['mobile_no_320_weight'] = (float)$line_item['mobile_no_320'];
			$daily_budget_weight_array['tablet_weight'] = (float)$line_item['tablet'];
			$daily_budget_weight_array['rtg_weight'] = (float)$line_item['rtg'];
		}
		
		$budget_weight_array = array();
		
		$budget_weight_array['daily_weights'] = $daily_budget_weight_array;
		
		$budget_numbers = $this->tradedesk_model->calculate_budget_numbers($start_date, $ttd_inputs['ttd_end'], $line_item['target_k_imprs']*1000, $budget_weight_array, $is_pre_roll,
			$start_date, $ttd_inputs['ttd_end'], $line_item['target_1st_imprs']);
		if($budget_numbers == FALSE)
		{
		    goto ttd_load_campaigns_exit;
		}
		$ttd_campaign_id = $this->tradedesk_model->create_campaign(
			$ttd_inputs['tkn'],
			$ttd_inputs['ttd_cmpn_name'],
			$ttd_inputs['ttd_adv_id'],
			$budget_numbers['c_total_budget'],
			$budget_numbers['c_total_impressions'],
			$ttd_inputs['ttd_start'],
			$ttd_inputs['ttd_end']
		);

		
		
		//$ttd_campaign_id = $this->tradedesk_model->create_campaign($ttd_inputs['tkn'], $ttd_inputs['ttd_cmpn_name'], $ttd_inputs['ttd_adv_id'],$ttd_inputs['ttd_cmpn_dlr_budget'],$ttd_inputs['ttd_imprs'],$ttd_inputs['ttd_start'], $ttd_inputs['ttd_end']);
		if($ttd_campaign_id == false)
		{
			goto ttd_load_campaigns_exit;
		}
		if($this->tradedesk_model->add_ttd_id_to_campaign($vl_campaign_id, $ttd_campaign_id) == 0)
		{
			goto ttd_load_campaigns_exit;
		}

		
		/////MAIN ADGROUPS
		if($is_pre_roll)
		{
			$ag_success = $this->tradedesk_model->build_pre_roll_adgroup_set($ttd_inputs['access_token_v3'], $ttd_inputs['ttd_cmpn_name'], $ttd_campaign_id, $budget_numbers['adgroup_budgets']);
		}
		else
		{
			$ag_success = $this->tradedesk_model->build_default_adgroup_set($ttd_inputs['access_token_v3'], $ttd_inputs['ttd_cmpn_name'], $ttd_campaign_id, $budget_numbers['adgroup_budgets']);
		}
		if(!$ag_success['success'])
		{
		    goto ttd_load_campaigns_exit;
		}

		if(!$no_adverify)
		{
			///////ADVERIFY CAMPAIGN
			$ttd_av_campaign_id = $this->tradedesk_model->create_av_campaign($ttd_inputs['tkn'], $ttd_inputs['ttd_cmpn_name']." - Ad Verify", $ttd_inputs['ttd_adv_id'], $ttd_inputs['ttd_start'], $ttd_inputs['ttd_end']);
			if($ttd_av_campaign_id==false)
			{
				goto ttd_load_campaigns_exit;
			}
			if($this->tradedesk_model->add_ttd_av_id_to_campaign($vl_campaign_id, $ttd_av_campaign_id) == 0)
			{
				goto ttd_load_campaigns_exit;
			}

			//////ADVERIFY TTD ADGROUP
			$ttd_av_adgroup_id = $this->tradedesk_model->create_av_adgroup($ttd_inputs['tkn'], $ttd_inputs['ttd_cmpn_name']." - Ad_Verify", $ttd_av_campaign_id, NULL, array());
			if($ttd_av_adgroup_id==false)
			{
				goto ttd_load_campaigns_exit;
			}
			if(!$this->tradedesk_model->add_av_adgroup_to_db($ttd_av_adgroup_id, $ttd_av_campaign_id))
			{
			    goto ttd_load_campaigns_exit;
			}
		}
		
		$is_success = true;

		ttd_load_campaigns_exit:
		return array('is_success'=>$is_success,'ttd_cmpn_id'=>$ttd_campaign_id,'ttd_input'=>$ttd_inputs);
	}
	
	public function generate_tags_and_save(
		$vl_adv_id,
		$vl_c_id,
		$landing_page,
		$ttd_campaign_id,
		$csv_audience_id = null,
		$csv_conversion_tag_id = null,
		$no_adverify
	)
	{
		$is_success = false;  
		
		$advertiser_details = $this->al_model->get_adv_details($vl_adv_id);
		if($advertiser_details == null)
		{
			goto tags_exit;
		}
		
		$tag_name = $advertiser_details['Name']." - BULK";
		 
		$advertiser_id = $this->tradedesk_model->get_ttd_advertiser_by_campaign($vl_c_id);
		if($advertiser_id == false)
		{
			goto tags_exit;
		}
		
		$access_token = $this->tradedesk_model->get_access_token();
		$access_token_v3 = $this->tradedesk_model->get_access_token(480, '3');
		$tag_type = 0;
		$audience_id = null;
		$conversion_tag_id = null;
		while($tag_type < 3)
		{
		    $create_new_tag = false;
		    $tag_name_to_create = $tag_name;
		    $is_conversion = false;
		    switch($tag_type)
		    {
			case 0: //RTG Tags
			    if($csv_audience_id == null)
			    {
				$create_new_tag = true;
			    }
			    break;
			case 1: //Adverify Tags
				if($no_adverify)
				{
					$tag_type++;
					continue;
				}
			    $create_new_tag = true;
			    $tag_name_to_create .= " - ADVERIFY";
			    break;
			case 2: //Conversion Tags
			    if($csv_conversion_tag_id == null)
			    {
				$create_new_tag = true;
				$is_conversion = true;
			    }
			    break;
			default: //Something's gone really wrong
			    goto tags_exit;
		    }
		    if($create_new_tag)
		    {
			$tag_id = $this->tradedesk_model->create_tag(
				$access_token_v3,
				($tag_type == 2) ? $tag_name_to_create." - CONV" : $tag_name_to_create, //Make - CONV only in TTD
				$advertiser_id,
				$landing_page,
				$is_conversion
			);
			if($tag_id == FALSE)
			{
				goto tags_exit;
			}
			if($tag_type < 2)
			{
			    $datagroup_id = $this->tradedesk_model->create_data_group($access_token, $tag_name, $advertiser_id, $this->tradedesk_model->get_data_ids_by_tag_name_and_advertiser_id($access_token, $tag_name, $advertiser_id));
			    if($datagroup_id == FALSE)
			    {
				    goto tags_exit;
			    }

			    $audience_id = $this->tradedesk_model->create_audience($access_token, $tag_name, $advertiser_id, array($datagroup_id));
			    if($audience_id == FALSE)
			    {
				    goto tags_exit;
			    }
			}
			else
			{
			    $conversion_tag_id = $tag_id;
			}
		    }
		    else
		    {
			//We won't make a new tag if a tag/audience was provided already for non_adverify.
			switch($tag_type)
			{
			    case 0:
				$audience_id = $csv_audience_id;
				break;
			    case 1:
				goto tags_exit;
				break;
			    case 2:	
				$conversion_tag_id = $csv_conversion_tag_id;
				break;
			    default:
				goto tags_exit;
				break;
			}
		    }
		    if($tag_type < 2)
		    {
			$audience_add = $this->tradedesk_model->add_audience_to_adgroup($audience_id, $vl_c_id, $tag_type);
			if($audience_add == FALSE)
			{
				goto tags_exit;
			}
		    }
		    else
		    {
			$linked_tag_success = $this->tradedesk_model->update_campaign_conversion_reporting_columns($access_token, $ttd_campaign_id, array($conversion_tag_id));
			if($linked_tag_success == false)
			{
			    goto tags_exit;
			}
		    }
		    
		    //get tag HTML
		    if($tag_type == 1 || ($csv_audience_id == NULL && $tag_type == 0) || ($csv_conversion_tag_id == NULL && $tag_type == 2) )
		    {
			$html_tag = $this->tradedesk_model->get_tag_html($advertiser_id, $tag_id, $tag_type);
			$insert_array = array(
				$vl_c_id,
				$tag_name_to_create,
				trim($html_tag),
				trim("BULK_UPLOADER"),
				$tag_type
				);			
			if($this->tag_model->insert_tag($insert_array) < 1)
			{
				goto tags_exit;
			}
		    }		    
		    
		    $tag_type++;
		}
		$is_success = TRUE;
		tags_exit:
		return array('is_success'=>$is_success);
	}
}

