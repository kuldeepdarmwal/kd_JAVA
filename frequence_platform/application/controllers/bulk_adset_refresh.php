<?php
class Bulk_adset_refresh extends CI_Controller
{
    	private $adgroup_type_array = array("pc", "mobile_320", "mobile_no_320", "tablet", "rtg", "pre_roll", "pre_roll_rtg");
	private $budget_csv_fields = array("budget_dollars", "budget_impressions", "daily_target_dollars", "daily_target_impressions");
	private $f_campaign_budget_fields = array("cmp_target_k_imprs", "cmp_f_end_date");
	private $ttd_campaign_budget_fields = array("cmp_ttd_end_date", "cmp_target_dollars", "cmp_target_impressions", "cmp_ttd_daily_imp_cap");
	
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url'));
		$this->load->helper('ad_server_type');
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->model('bulk_campaigns_operations_model');
		$this->load->model('al_model');
		$this->load->model('campaign_health_model');		
		$this->load->model('cup_model');
		$this->load->model('tag_model');
		$this->load->model('dfa_model');
		$this->load->model('publisher_model');
		$this->load->model('tradedesk_model');
		$this->load->helper('tradedesk');
	}

	public $g_csv_array = array();

	public function index()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','bulk_adset_refresh');
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

			$data['active_menu_item'] = "bulk_adset_refresh_menu";//the view will make this menu item active
			$data['title'] = "Bulk Adset Refresher";

			$this->form_validation->set_rules('userfile', 'File', 'callback_check_file');
			if ($this->form_validation->run() == FALSE)
			{
				$this->load->view('ad_linker/header',$data);
				$this->load->view('bulk_adset_refresh/bulk_adset_form_body_view',$data);
			}
			else
			{
				$file_contents = $this->g_csv_array;
				$validation_result_array = array();
				$array_of_new_campaigns = array();
				$inputs_valid = true;
				foreach($file_contents['array_data'] as $ii=>$row)
				{
					///1) check that campaign id exists in VL and TTD
					////////////////////////////////////////
					if(isset($row['vl_cmpn_id']) && $this->bulk_campaigns_operations_model->does_campaign_and_campaign_ttd_exist($row['vl_cmpn_id']))
					{
						$validation_result_array[$ii]['vl_cmpn_id'] = true; 
					}
					else
					{
						$validation_result_array[$ii]['vl_cmpn_id'] = false; 
						$inputs_valid = false;
						if(!isset($row['vl_cmpn_id']))
						{
							$file_contents['array_data'][$ii]['vl_cmpn_id'] = 'N/A';
						}
						else
						{
						    $file_contents['array_data'][$ii]['vl_cmpn_id'] = $row['vl_cmpn_id'];
						}

						continue;
					}
					
					if((isset($row['vl_adset_v_id']) && $row['vl_adset_v_id'] != '') ||
					   (isset($row['landing_page']) && $row['landing_page'] != '') ||
					   (isset($row['update_method']) && $row['update_method'] != ''))
					{
					    ///2 (formerly 4)) Check for Update Method
					    if(isset($row['update_method']) && $row['update_method'] != '')
					    {
						    if($row['update_method'] != "replace" && $row['update_method'] != "append" && $row['update_method'] != "remove")
						    {
							$validation_result_array[$ii]['update_method'] = false; 
							$inputs_valid = false;
							continue;
						    }
						    $validation_result_array[$ii]['update_method'] = true; 
					    }
					    else
					    {
						$validation_result_array[$ii]['update_method'] = false; 
						$inputs_valid = false;
						continue;
					    }					    
					    ///3 (Formerly 2)) Check that VL version IDs exist
					    $num_versions;
					    if(isset($row['vl_adset_v_id']) && $row['vl_adset_v_id'] != '')
					    {
						$versions = explode(';', $row['vl_adset_v_id']);
						$num_versions = count($versions);

						$are_versions_okay = true;
						foreach($versions as $version_id)
						{
						    if(!is_numeric($version_id))
						    {
							$are_versions_okay = false;
							break;
						    }
						    if($row['update_method'] == "remove")
						    {
							if($this->tradedesk_model->does_version_exist_with_ttd_creatives_for_campaign($version_id, $row['vl_cmpn_id']) == FALSE)
							{
							    $are_versions_okay = false;
							    break;   
							}
						    }
						    else
						    {
							if($this->cup_model->does_version_exist($version_id) == FALSE)
							{
							    $are_versions_okay = false;
							    break; 
							}
						    }
						}
						if($are_versions_okay)
						{
						    $validation_result_array[$ii]['vl_adset_v_id'] = true; 
						}
						else
						{
						    $validation_result_array[$ii]['vl_adset_v_id'] = false; 
						    $inputs_valid = false;
						    continue;
						}
					    }
					    else
					    {
						$validation_result_array[$ii]['vl_adset_v_id'] = false; 
						$inputs_valid = false;
						continue;
					    }

					    
					    ///4 (formerly 3)) check that landing page url list is okay and matches the length of the adset list if needed
					    ////////////////////////////////////////
					    if($row['update_method'] != "remove")
					    {
						if(isset($row['landing_page']) && $row['landing_page'] != '')
						{
							$landing_pages = $this->get_landing_pages_from_landing_pages_string($row['landing_page']);
							$num_landing_pages = count($landing_pages);

							if($num_landing_pages == $num_versions)
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
					    }
					else
					{
						$validation_result_array[$ii]['landing_page'] = true; 
					}
					}					
				}
				$data['validation_array'] = $validation_result_array;

				$data['file_data'] = $_FILES;
				$data['data_array'] = $file_contents;

				$data['inputs_valid'] = $inputs_valid;
				if(!$inputs_valid)
				{
					$this->load->library('table');
					$this->table->set_heading('Cmpn ID', 'Error');
					$tmpl = array ('table_open'=> '<table class="table table-hover table-condensed">');
					$this->table->set_template($tmpl);
					//process the validation array to come up with meaningful messages
					foreach($validation_result_array as $row_num=>$row_results)
					{
						$problem_fields[$row_num] = implode('</span><span class="label label-important">',array_keys($row_results,false));
						$this->table->add_row($file_contents['array_data'][$row_num]['vl_cmpn_id'], '<span class="label label-important">'.$problem_fields[$row_num].'</span>');
					}
					$data['error_table'] = $this->table->generate();;
					$data['problem_fields'] = $problem_fields;

					$this->load->view('ad_linker/header',$data);
					$this->load->view('bulk_adset_refresh/bulk_adset_invalid_inputs_view',$data);
				}
				else
				{
					$this->load->view('ad_linker/header',$data);
					$this->load->view('bulk_adset_refresh/bulk_adset_complete_view',$data);
				}

				
			}
		}
		else
		{ 
			redirect('director'); 
		}

	}

	public function bulk_load_timeseries()
	{
		if(!$this->tank_auth->is_logged_in())
		{
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

			$data['active_menu_item'] = "bulk_adset_refresh_menu";//the view will make this menu item active
			$data['title'] = "Bulk Adset Refresher";
			$data['timeseries_error_string']="";
			$data['timeseries_success_array']="";
			$this->form_validation->set_rules('userfile', 'File', 'callback_check_file');
			if ($this->form_validation->run() == FALSE)
			{
				 
				$this->load->view('ad_linker/header',$data);
				$this->load->view('bulk_adset_refresh/bulk_timeseries_output_view',$data);
			}
			else
			{
				$file_contents = $this->g_csv_array;
				$error_string="";
				$bulk_queries_timeseries="";
				$delete_campaign_timeseries=array();
				$previous_row_campaign_id="-1";
				$counter=0;
				$delete_query_array=array();
				$insert_query_array=array(); 
				$campaigns_inserted=array();
				foreach($file_contents['array_data'] as $ii=>$row)
				{
					if ($counter == count($file_contents['array_data']) -1 && $row['Impressions'] > 0)
					{
					 	$error_string.="Time series for Campaign ID " . $row['CampaignID'] . " should end with 0 impressions <br>";
					}
					if ($previous_row_campaign_id != "-1" && $row['CampaignID'] != $previous_row_campaign_id && $previous_row_impressions != 0)
					{
						$error_string.="Time series for Campaign ID " . $previous_row_campaign_id . " should end with 0 impressions <br>";
					}
					if ($row['CampaignID'] == "" || $row['Start Date'] == "" || $row['Impressions'] == "")
					{
						$error_string.="Blank cells in row " . ($ii+2)."<br>";
					}
					if ($this->validate_date($row['Start Date']) == false)
					{
						$error_string.="Start Date invalid in row " . ($ii+2)." It should be in (yyyy-mm-dd) format <br>";
					}
					if ($previous_row_campaign_id == "-1" || $row['CampaignID'] != $previous_row_campaign_id)
					{
						$delete_query_array[]=$row['CampaignID'];
					}
					if ($counter >= 0)
					{
						$insert_query_array[]="(".$row['CampaignID'].",'".$row['Start Date']."',".$row['Impressions'].", 0)";
					}
					$campaigns_inserted[$row['CampaignID']] = $row['CampaignID'];
					$previous_row_campaign_id=$row['CampaignID'];	
					$previous_row_impressions=$row['Impressions'];	
					$counter++;
				}
				$data['file_data'] = $_FILES;
				$data['data_array'] = $file_contents;
				

				if($error_string != "")
				{
					$data['timeseries_error_string'] = $error_string;			
					$this->load->view('ad_linker/header',$data);
					 
					$this->load->view('bulk_adset_refresh/bulk_timeseries_output_view',$data);
				}
				else
				{
					$count=$this->al_model->delete_create_time_series_for_campaign_bulk($delete_query_array, $insert_query_array);
					if ($count == 0)
					{
						$data['timeseries_error_string'] = 'Could not insert Timeseries';			
					}
					foreach($campaigns_inserted as $campaign_inserted)
					{
						$this->bulk_campaigns_operations_model->create_bulk_subproduct_data_for_campaign($campaign_inserted, -1);
					}
					$return_arr=$this->campaign_health_model->get_timeseries_data_after_bulk($delete_query_array);
					$data['timeseries_success_array']=$return_arr;
					$this->load->view('ad_linker/header',$data);
					$this->load->view('bulk_adset_refresh/bulk_timeseries_output_view',$data);
				}
		
			}
		}
		else
		{ 
			redirect('director'); 
		}

	}

	function validate_date($date)
	{	
		if (!(substr($date, 4, 1) === "-"))
		{
			return false;
		}
		return (bool)strtotime($date);
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
			
			$operation = "refresh";
			$vl_landing_page_updated = false;
			$vl_adsets_cloned = false;
			$dfa_adsets_published = 0;
			$ttd_adsets_loaded = 0;
			$f_campaign_budgets_updated = 0;
			$ttd_campaign_budgets_updated = 0;
			$ttd_adgroup_budgets_updated = false;
			$is_remove = false;
			
			$force_duplication = false;
			
			$vl_campaign_id = $ajax_verify['post']['line_item']['vl_cmpn_id'];
			if(isset($ajax_verify['post']['line_item']['dfa_campaign_name']))
			{
			    $dfa_campaign_name = $ajax_verify['post']['line_item']['dfa_campaign_name'];
			}
			else
			{
			    $dfa_campaign_name = '';	    
			}
			if(isset($ajax_verify['post']['line_item']['bidder']))
			{
			    $bidder = $ajax_verify['post']['line_item']['bidder'];
			}
			else
			{
			    $bidder = '';	    
			}			
			
			$campaign_details = $this->al_model->get_campaign_details($vl_campaign_id);
			if($campaign_details === NULL)
			{
			    goto load_item_exit;
			}
			$vl_campaign_name = $campaign_details[0]['Name'];
			$vl_advertiser_id = $campaign_details[0]['business_id'];
			$ttd_campaign_id = $campaign_details[0]['ttd_campaign_id'];

			if(!empty($ajax_verify['post']['line_item']['force_duplication']))
			{
			    if(($ajax_verify['post']['line_item']['force_duplication'] == "true") || $ajax_verify['post']['line_item']['force_duplication'] == "1")
			    {
				$force_duplication = true;
			    }
			}
			
			if(
			    (isset($ajax_verify['post']['line_item']['vl_adset_v_id']) && $ajax_verify['post']['line_item']['vl_adset_v_id'] != '' ) &&
			    (isset($ajax_verify['post']['line_item']['update_method']) && $ajax_verify['post']['line_item']['update_method'] != '')
			){
			    
			    if($ajax_verify['post']['line_item']['update_method'] != "remove")
			    {
				if((isset($ajax_verify['post']['line_item']['landing_page']) && $ajax_verify['post']['line_item']['landing_page'] != ''))
				{
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

				    $did_landing_page_get_updated = $this->al_model->update_campaign_landing_page($vl_campaign_id ,$base_landing_page);
				    if($did_landing_page_get_updated)
				    {
					$vl_landing_page_updated = true;
				    }
				    else
				    {
					goto load_item_exit;
				    }

			    //Clone Adsets from list of version ids
			    $cloned_adsets_result = $this->bulk_campaigns_operations_model->clone_adset_version_list_for_campaign(
				    $ajax_verify['post']['line_item']['vl_adset_v_id'],
				    $ajax_verify['post']['line_item']['landing_page'],
				    $vl_campaign_id,
				    $vl_advertiser_id,
				    $operation,
				    $force_duplication,
				    $dfa_campaign_name
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
					    $vl_advertiser_id,
					    $landing_pages,
					    $dfa_advertiser_id_to_push,					   
					    $bidder
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
				    if($bidder !== 'dfa')
				    {
					$link_adsets_to_ttd_result = $this->bulk_campaigns_operations_model->link_versions_array_creatives_to_ttd($vl_version_ids, $vl_campaign_id, $ajax_verify['post']['line_item']['update_method'] );
				    }
				    else
				    {
					$link_adsets_to_ttd_result = false;
					$ttd_adsets_loaded = 0;
					goto load_item_exit;
				    }
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
			    }
			    else
			    {
				//Remove relevant adsets
				$is_remove = TRUE;
				$remove_versions_from_ttd_result = $this->remove_version_list_from_campaign($ajax_verify['post']['line_item']['vl_adset_v_id'], $vl_campaign_id);
				if($remove_versions_from_ttd_result['is_success'])
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
			
			if($this->do_campaign_budget_update_values_exist_for_platform($ajax_verify['post']['line_item'], "f"))
			{
			    //Update campaign budget values
			    $update_f_campaign_budgets_result = $this->update_f_campaign_data($ajax_verify['post']['line_item']);
			    if($update_f_campaign_budgets_result['is_success'])
			    {
			    	$f_campaign_budgets_updated = 1;
			    }
			    else
			    {
				$f_campaign_budgets_updated = -1;
			    	goto load_item_exit;
			    }
			}

			if($this->do_campaign_budget_update_values_exist_for_platform($ajax_verify['post']['line_item'], "ttd"))
			{
			    //Update campaign budget values
			    $update_ttd_campaign_budgets_result = $this->update_ttd_campaign_data($ajax_verify['post']['line_item'], $ttd_campaign_id);
			    if($update_ttd_campaign_budgets_result['is_success'])
			    {
			    	$ttd_campaign_budgets_updated = 1;
			    }
			    else
			    {
				$ttd_campaign_budgets_updated = -1;
			    	goto load_item_exit;
			    }
			}			
			
			//Update TTD adgroup budgets only if we see any budget update values for this row
			if($this->do_adgroup_budget_update_values_exist($ajax_verify['post']['line_item']))
			{
			    $update_budgets_in_ttd_result = $this->update_ttd_budgets($vl_campaign_id, $ajax_verify['post']['line_item']);		
			    if($update_budgets_in_ttd_result['is_success'])
			    {
				$ttd_adgroup_budgets_updated = true;
			    }
			    else
			    {
				goto load_item_exit;
			    }
			}
			
			
			load_item_exit:

			echo json_encode(array(
				'is_success'=>true,
				'cmpn_name'=>$vl_campaign_id.' - '.$vl_campaign_name,
				'line_item'=>$ajax_verify['post']['line_item'],
				'vl_landing_page_updated'=>$vl_landing_page_updated,
				'vl_adsets_cloned'=>$vl_adsets_cloned,
				'dfa_adsets_published'=>$dfa_adsets_published,
				'ttd_adsets_loaded'=>$ttd_adsets_loaded,
				'f_campaign_budgets_updated'=>$f_campaign_budgets_updated,
				'ttd_campaign_budgets_updated'=>$ttd_campaign_budgets_updated,
				'ttd_adgroup_budgets_updated'=>$ttd_adgroup_budgets_updated,
				'is_removal'=>$is_remove
			    ));
		}
	}

	private function update_ttd_budgets($vl_campaign_id, $line_item)
	{
	    
	    $is_success = false;
	    
	    $adgroups = $this->tradedesk_model->get_non_av_ttd_adgroups_by_campaign($vl_campaign_id);
	    if($adgroups == false)
	    {
		goto ttd_budget_update_exit;
	    }
	    
	    foreach($adgroups as $adgroup)
	    {
		$ag_id = $adgroup['ID'];

		if($adgroup['target_type'] == NULL)
		{
		    $adgroup['target_type'] = $this->tradedesk_model->determine_target_type_for_adgroup_id($ag_id);
		}
		switch($adgroup['target_type'])
		{
		    case "Mobile 320":
			$budget_prefix = "mobile_320";
			break;
		    case "Mobile No 320":
			$budget_prefix = "mobile_no_320";
			break;
		    case "PC":
			$budget_prefix = "pc";
			break;
		    case "Tablet":
			$budget_prefix = "tablet";
			break;
		    case "RTG":
			$budget_prefix = "rtg";
			break;
		    case "Pre-Roll":
			$budget_prefix = "pre_roll";
			break;
		    case "RTG Pre-Roll":
			$budget_prefix = "pre_roll_rtg";
			break;
		    default:
			continue;
			break;
		}
		
		$access_token = $this->tradedesk_model->get_access_token();
		$adgroup_data = $this->tradedesk_model->get_info($access_token, "adgroup", $ag_id);
		$decoded = json_decode($adgroup_data);
		if(property_exists($decoded, 'Message'))
		{
		    goto ttd_budget_update_exit;
		}
		$is_enabled = $decoded->IsEnabled;
		$total_budget = 0;
		$total_impressions = 0;
		$daily_budget = 0;
		$daily_impressions = 0;
		if(isset($line_item[$budget_prefix."_budget_dollars"]) && is_numeric($line_item[$budget_prefix."_budget_dollars"]))
		{
		    $total_budget = $line_item[$budget_prefix."_budget_dollars"];
		}
		else
		{
		    $total_budget = $decoded->RTBAttributes->BudgetInUSDollars;
		}
		
		if(isset($line_item[$budget_prefix."_budget_impressions"]) && is_numeric($line_item[$budget_prefix."_budget_impressions"]))
		{
		    $total_impressions = $line_item[$budget_prefix."_budget_impressions"];
		}
		else
		{
		    $total_impressions = $decoded->RTBAttributes->BudgetInImpressions;
		}

		if(isset($line_item[$budget_prefix."_daily_target_dollars"]) && is_numeric($line_item[$budget_prefix."_daily_target_dollars"]))
		{
		    $daily_budget = $line_item[$budget_prefix."_daily_target_dollars"];
		}
		else
		{
		    $daily_budget = $decoded->RTBAttributes->DailyBudgetInUSDollars;
		}		

		if(isset($line_item[$budget_prefix."_daily_target_impressions"]) && is_numeric($line_item[$budget_prefix."_daily_target_impressions"]))
		{
		    $daily_impressions = $line_item[$budget_prefix."_daily_target_impressions"];
		}
		else
		{
		    $daily_impressions = $decoded->RTBAttributes->DailyBudgetInImpressions;
		}
		
		if($total_budget == 0)
		{
		    $total_budget = 0.01;
		    $is_enabled = FALSE;
		}
		if($daily_budget == 0)
		{
		    $daily_budget = 0.01;
		    $is_enabled = FALSE;
		}
		if($total_impressions == 0)
		{
		    $total_impressions = 1;
		    $is_enabled = FALSE;
		}		
		if($daily_impressions == 0)
		{
		    $daily_impressions = 1;
		    $is_enabled = FALSE;
		}
		
		$adgroup_modify = $this->tradedesk_model->update_adgroup_details($access_token, $ag_id, $is_enabled, $total_impressions, $total_budget, $daily_impressions, $daily_budget);
		if($adgroup_modify == FALSE)
		{
		    goto ttd_budget_update_exit;
		}

	    }
	    $is_success = true;
	    
	    
	    ttd_budget_update_exit:
	    return array('is_success'=>$is_success);
	    
	}
	
	private function do_adgroup_budget_update_values_exist($line_item)
	{
	    foreach($this->adgroup_type_array as $adgroup_type)
	    {
		foreach($this->budget_csv_fields as $budget_field)
		{
		    $field = $adgroup_type.'_'.$budget_field;
		    if(isset($line_item[$field]) && $line_item[$field] != '' )
		    {
			return true;
		    }
		}
	    }
	    return false;
	}
	
	private function remove_version_list_from_campaign($version_id_list, $vl_campaign_id)
	{
	    $is_success = false;
	    $new_versions = array();
	    
	    $versions_array = explode(";", $version_id_list);
	    $ids_to_delete = array();
	    foreach($versions_array as $version)
	    {
		$ttd_creative_ids = $this->tradedesk_model->get_ttd_ids_for_creatives_with_version_id_and_campaign($version, $vl_campaign_id);
		if($ttd_creative_ids == FALSE)
		{
		    goto ttd_adset_remove_exit;
		}
		$ids_to_delete = array_merge($ids_to_delete, $ttd_creative_ids);
		
	    }
	    $removal_result = $this->tradedesk_model->remove_creatives_from_campaign_with_ids($vl_campaign_id, $ids_to_delete);
	    if($removal_result == FALSE)
	    {
		goto ttd_adset_remove_exit;
	    }
	    $is_success = TRUE;
	    
	    ttd_adset_remove_exit:
	    return array('is_success'=>$is_success);
	}

	private function do_campaign_budget_update_values_exist_for_platform($line_item, $which_platform)
	{
	    $campaign_budget_fields_to_check = null;
	    switch($which_platform)
	    {
		case "f":
		    $campaign_budget_fields_to_check = $this->f_campaign_budget_fields;
		    break;
		case "ttd":
		    $campaign_budget_fields_to_check = $this->ttd_campaign_budget_fields;
		    break;
		default:
		    return false;
	    }
	    
	    foreach($campaign_budget_fields_to_check as $campaign_budget_field)
	    {
		if(isset($line_item[$campaign_budget_field]) && $line_item[$campaign_budget_field] != '')
		{
		    return true;
		}
	    }
	    return false;
	}	
	
	private function update_f_campaign_data($line_item)
	{
	    $is_success = false;
	    //get campaign data from f database
	    $campaign_details_result = $this->al_model->get_campaign_details($line_item['vl_cmpn_id']);
	    if($campaign_details_result != null || empty($campaign_details_result))
	    {
		$imprs_same = true;
		if(!empty($line_item['cmp_target_k_imprs']))
		{
		    if($line_item['cmp_target_k_imprs'] != $campaign_details_result[0]['TargetImpressions'])
		    {
			$imprs_same = false;
		    }
		}
		$end_date_same = true;
		if(!empty($line_item['cmp_f_end_date']))
		{
		    if($line_item['cmp_f_end_date'] != $campaign_details_result[0]['hard_end_date'])
		    {
			$end_date_same = false; 
		    }
		}
		$all_are_same = $imprs_same && $end_date_same;
		if(!$all_are_same)
		{
		    $update_target_and_end_date_result = $this->bulk_campaigns_operations_model->update_f_campaign_cycle_target_and_end_date
												(
												    $line_item['vl_cmpn_id'], 
												    empty($line_item['cmp_target_k_imprs']) == false ? $line_item['cmp_target_k_imprs'] : $campaign_details_result[0]['TargetImpressions'],
												    empty($line_item['cmp_f_end_date']) == false ? $line_item['cmp_f_end_date'] : $campaign_details_result[0]['hard_end_date']
												);
		    if($update_target_and_end_date_result == true)
		    {
		       $is_success = true;
		    }
		}
		else
		{
		    //duplicates. Peace out.
		    $is_success = true;
		}
	    }
	    return array('is_success'=>$is_success);
	}
	
	private function update_ttd_campaign_data($line_item, $ttd_campaign_id)
	{
	    $is_success = false;
	    $access_token = $this->tradedesk_model->get_access_token();
	    //Pull down campaign data
	    $get_ttd_campaign_result = $this->tradedesk_model->get_campaign($ttd_campaign_id);
	    if($get_ttd_campaign_result['is_success'] == true)
	    {
		$ttd_campaign_data = $get_ttd_campaign_result['ttd_campaign_object'];
		
		$line_item['cmp_ttd_daily_imp_cap'] = array_key_exists('cmp_ttd_daily_imp_cap', $line_item)== true ? $line_item['cmp_ttd_daily_imp_cap'] : intval($ttd_campaign_data->DailyBudgetInImpressions);
		$line_item['cmp_ttd_daily_imp_cap'] = ($line_item['cmp_ttd_daily_imp_cap'] != 0) ? $line_item['cmp_ttd_daily_imp_cap'] : '';
		
		$update_campaign_result = $this->tradedesk_model->update_campaign_details(
								    $access_token,
								    $ttd_campaign_data->CampaignId, 
								    (!empty($line_item['cmp_target_impressions']) && is_numeric($line_item['cmp_target_impressions'])) == true ? $line_item['cmp_target_impressions'] : $ttd_campaign_data->BudgetInImpressions,
								    (!empty($line_item['cmp_target_dollars']) && is_numeric($line_item['cmp_target_dollars'])) == true ? $line_item['cmp_target_dollars'] : $ttd_campaign_data->BudgetInUSDollars,
								    is_numeric($line_item['cmp_ttd_daily_imp_cap'])== true ? $line_item['cmp_ttd_daily_imp_cap'] : '',
								    $ttd_campaign_data->StartDate,
								    empty($line_item['cmp_ttd_end_date']) == false ? format_end_date($line_item['cmp_ttd_end_date']) : $ttd_campaign_data->EndDate
								    );
		
		if($update_campaign_result != false)
		{
		    $is_success = true;
		}
	    }
	    return array('is_success'=>$is_success);
	}
}

