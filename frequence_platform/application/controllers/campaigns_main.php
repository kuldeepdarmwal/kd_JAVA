<?php

class Campaigns_main extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('session', 'tank_auth', 'vl_platform', 'excel', 'user_agent'));
		$this->load->model(array('campaign_health_model', 'tradedesk_model', 'vl_auth_model', 'campaign_tags_model', 'al_model'));
		$this->load->helper(array('download', 'tradedesk', 'vl_ajax', 'mailgun'));
	}
	
	public function chart(){
		$data = array();
		$this->load->view('campaigns/chart_html', $data);
	}

	public function health($offset_days = 0)
	{
		if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('campaigns_main', '/campaigns_main/old'))
		{
			return;
		}
		$data = array();
		$data['bd_partner_options'] = "";
		$data['bd_sales_person_options'] = "";
		$active_feature_button_id = 'campaigns_main';
		$active_subfeature_button_id = 'health_check';

		$data['title'] = 'Campaigns';

		/////SUBFEATURE FUNCTIONS HERE 
		$username = $this->tank_auth->get_username();
		$user_id = $this->tank_auth->get_user_id();
		$data['partner_id'] = $this->tank_auth->get_partner_id($user_id);
		$data['role'] = strtolower($this->tank_auth->get_role($username));

		if($offset_days !=0 && ($data['role']!='ops' && $data['role']!='admin'))
		{
			redirect('campaigns_main');
		}

		$data['is_super'] = $this->tank_auth->get_isGroupSuper($username);
		$campaigns = $this->campaign_health_model->get_allowed_healthcheck_campaigns($data['role'],$data['is_super'],$user_id);
		$report_date = $this->campaign_health_model->get_last_impression_date();
		$report_date[0]['value'] = date('Y-m-d', strtotime($report_date[0]['value'] . ' - '.$offset_days.' day'));
		$data['campaigns'] = json_encode($campaigns);
		$data['report_date'] = $report_date[0]['value'];
		$partners_array = array();
		if($data['role'] == 'admin' OR $data['role'] == 'ops')
		{
			$partners_array = $this->tank_auth->get_partner_array();
		}
		else if($data['role'] == 'sales')
		{
			$partners_array = $this->tank_auth->get_partner_hierarchy_by_sales_person($user_id, $data['is_super']);
		}
		if(count($partners_array) > 1)
		{
			$data['bd_partner_options'] .= '<option value="all">All Partners</option>' . "\n";
		}
		foreach($partners_array as $value)
		{
			$data['bd_partner_options'] .= '<option value="'.$value["id"].'">'.$value["partner_name"].'</option>' . "\n";
		}

		$data['can_view_mpq'] = ($this->vl_platform->get_access_permission_redirect('mpq') == '' ? TRUE : FALSE);
		
		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'campaigns_main/healthcheck_html',
			'campaigns_main/campaigns_main_html_header.php',
			'campaigns_main/healthcheck_html_header.php',
			'campaigns_main/campaigns_main_js.php',
			'campaigns_main/healthcheck_js.php'
		);
	}

	public function get_bulk_download()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'response_code' => 200, 'error_message' => array(), 'is_pending' => false);
		if($verify_ajax_response['is_success'] === true)
		{
			$sales_person_array = array(0); //Set element as 0 by default because this will be used for an "IN()" function in mysql which needs at least one parameter
			$result = false;
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');
			$selected_partner = $this->input->post('selected_partner');
			$selected_sales = $this->input->post('selected_sales_person');
			if(!empty($start_date) and !empty($end_date) and !empty($selected_partner) and !empty($selected_sales))
			{
				$start = date('Y-m-d', strtotime($start_date));
				$end = date('Y-m-d', strtotime($end_date));
				$user_id = $this->tank_auth->get_user_id();
				$user_results = $this->vl_auth_model->get_user_by_id($user_id);

				if($user_results !== null)
				{
					if(!$this->is_user_pending($user_results->bulk_campaign_email_pending))
					{
						$campaigns_main_ops_referer_flag = false;
						if(strpos($this->agent->referrer(), 'campaigns_main') !== false)
						{
							$campaigns_main_ops_referer_flag = true;
						}
						
						$this->campaign_health_model->set_bulk_campaign_email_flag_for_user($user_id, true);
						$username = $user_results->username;
						$role = strtolower($user_results->role);
						$is_super = $user_results->isGroupSuper;
						if($role != 'admin' and $role != 'ops' and $role != 'sales')
						{
							$return_array['is_success'] = false;
							$return_array['response_code'] = 500;
							$return_array['error_message'][] = "Incorrect role: ".$role;
						}

						if($selected_sales === 'all')
						{
							$raw_sales_array = $this->get_sales_people_for_user_by_partner($selected_partner, $role, $user_id, $is_super, $return_array);

							foreach($raw_sales_array as $sales_person)
							{
								$sales_person_array[] = $sales_person['id'];
							}
						}
						else if(is_numeric($selected_sales))
						{
							$sales_person_array[] = $selected_sales;
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['response_code'] = 500;
							$return_array['error_message'][] = "Incorrect account executive value selected";
						}

						$result = $this->campaign_health_model->get_bulk_download($sales_person_array, $start, $end, $user_id, $role, $is_super, $selected_partner, $campaigns_main_ops_referer_flag);

						if($result)
						{
							$objPHPExcel = new PHPExcel();

							$objPHPExcel->getProperties()->setCreator("Campaigns Main")
								->setLastModifiedBy("Campaigns Main")
								->setTitle("Campaign Data")
								->setSubject("Campaign Data")
								->setDescription("");

							$alphas = range('A', 'Z');
							array_push($alphas, "AA","AB");
							$i = 0;
							foreach($result[0] as $k => $v)
							{
								if ($k == 'Campaign Schedule')
								{
									array_push($alphas, "AC","AD","AE","AF","AG","AH","AI","AJ","AK");
								}
								
								if ($k == 'All Time O&O Budget')
								{
									array_push($alphas, "AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW");
								}
																
								$objPHPExcel->setActiveSheetIndex(0)
									->setCellValue($alphas[$i].'1', $k);
								//$objPHPExcel->setActiveSheetIndex(0)
								//	->setCellValue($alphas[$i].$j, $v);
								$i++;
							}

							$start_date_column = 7;
							$end_date_column = 8;

							$objPHPExcel->getActiveSheet()
								->getColumnDimension($alphas[$start_date_column])
								->setAutoSize(true);

							$objPHPExcel->getActiveSheet()
								->getColumnDimension($alphas[$end_date_column])
								->setAutoSize(true);

							$j = 2;
							foreach($result as $v)
							{
								$i = 0;
								foreach( $v as $l => $w)
								{
									if(($i === $start_date_column || $i === $end_date_column) && $w !== '')
									{
										$cell_name = $alphas[$i].$j;
										$objPHPExcel->setActiveSheetIndex(0)
											->setCellValue($cell_name, PHPExcel_Shared_Date::stringToExcel($w));

										$objPHPExcel->setActiveSheetIndex(0)
											->getStyle($cell_name)
											->getNumberFormat()
											->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
									}
									else
									{
										$objPHPExcel->setActiveSheetIndex(0)
											->setCellValue($alphas[$i].$j, $w);
									}
									$i++;
								}
								$j++;
							}
							$objPHPExcel->getActiveSheet()->setTitle("Campaigns Export Data");

							$filename = $user_id."_".date('Ymdhis')."_".$start."_".$end."_bulk.xlsx";
							$writer_filepath = sys_get_temp_dir()."/".$filename; //create temporary file
							$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

							try 
							{
								$objWriter->save($writer_filepath);
							}
							catch(Exception $e)
							{
								$return_array['is_success'] = false;
								$return_array['response_code'] = 500;
								$return_array['error_message'][] = "Unable to create bulk campaign data.";
							}

							$partner_subdomain_info = $this->tank_auth->get_partner_info_by_sub_domain();
							$subdomain_string = "";
							if($partner_subdomain_info !== false)
							{
								$subdomain_string = $partner_subdomain_info['cname'].".";
							}

							$mailgun_extras = array();
							$subject_string = "There was a problem processing your bulk campaign data";

							if($return_array['is_success'] === true)
							{
								$mailgun_extras = process_attachments_for_email(array($writer_filepath));
								$subject_string = "Your bulk campaign data is ready";	
							}
							$from_email = "No Reply <no-reply@".$subdomain_string."brandcdn.com>";
							$to_email = $user_results->email;
							$markup_data = array('firstname' => $user_results->firstname, 'lastname' => $user_results->lastname, 'is_success' => $return_array['is_success']);
							$email_message_markup = $this->load->view('campaigns_main/bulk_campaign_data', $markup_data, true);
							$message_type = "html";

							$mailgun_result = mailgun(
								$from_email,
								$to_email,
								$subject_string,
								$email_message_markup,
								$message_type,
								$mailgun_extras
								);
							$this->campaign_health_model->set_bulk_campaign_email_flag_for_user($user_id, false);
							if($mailgun_result !== true)
							{
								$return_array['is_success'] = false;
								$return_array['response_code'] = 500;
								$return_array['error_message'][] = "There were some issues while sending an email. Please try again.";
							}
							unlink($writer_filepath);
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['response_code'] = 500;
							$return_array['error_message'][] = "No bulk data available for download with specified parameters.";
						}
					}
					else
					{
						$return_array['is_pending'] = true;
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['response_code'] = 500;
					$return_array['error_message'][] = "Invalid user credentials";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['response_code'] = 500;
				$return_array['error_message'][] = "Invalid bulk download parameters";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['response_code'] = 500;
			$return_array['error_message'][] = "User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	public function ajax_get_bulk_download_sales_people()
	{
		$result = array('is_success' => true, 'errors' => "", 'data' => array());
		$all_array_option = array('id' => 'all', 'username' => 'All Account Executives');
		if(!$this->tank_auth->is_logged_in())
		{
			$result['is_success'] = false;
			$result['errors'] = 'Error 494478: Must be logged in to view campaign data';
		}
		else
		{
			$selected_partner = $this->input->post('selected_partner');
			$temp_start_date = $this->input->post('start_date');
			$temp_end_date = $this->input->post('end_date');
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$role = strtolower($this->tank_auth->get_role($username));
			$is_super = $this->tank_auth->get_isGroupSuper($username);
			if($role != 'sales' AND $role != 'admin' AND $role != 'ops')
			{
				$result['is_success'] = false;
				$result['errors'] = 'Error 494499: You do not have permission to view this content';
			}
			else if($selected_partner === false OR $temp_start_date === false OR $temp_end_date === false)
			{
				$result['is_success'] = false;
				$result['errors'] = 'Error 494129: Incorrect or incomplete parameters received';
			}
			else
			{
				$ui_start_date = strtotime($temp_start_date);
				$ui_end_date = strtotime($temp_end_date);
				if($ui_start_date > $ui_end_date) //user has flipped the dates in the ui
				{
					$result['is_success'] = false;
					$result['errors'] = 'Error 494177: Selected start date is later than selected end date';
				}
				else
				{	
					$sales_array = $this->get_sales_people_for_user_by_partner($selected_partner, $role, $user_id, $is_super, $result);
					if(count($sales_array) > 1)
					{
						array_unshift($sales_array, $all_array_option); 
					}
					else if(count($sales_array) === 0)
					{
						$result['is_success'] = false;
						$result['errors'] = 'Warning 494410: No users available with selection';
					}
					$actual_sales_array = array();
					$num_users_with_impressions = 0;
					foreach($sales_array as $key => $sales_user)
					{
						if($sales_user['id'] !== "all")
						{
							$sales_array[$key]['has_impressions'] = false;
							if(!is_null($sales_user['earliest_impression']) OR !is_null($sales_user['latest_impression']))
							{
								$user_start_date = strtotime($sales_user['earliest_impression']);
								$user_end_date = strtotime($sales_user['latest_impression']);

								if($user_start_date <= $ui_end_date AND $user_end_date >= $ui_start_date)
								{
									$actual_sales_array[$key] = $sales_array[$key];
									$sales_array[$key]['has_impressions'] = true;
									$num_users_with_impressions++;
								}
							}
						}
						else
						{
							$actual_sales_array[$key] = $sales_array[$key];
							$sales_array[$key]['has_impressions'] = null;
						}
					}
					if(($num_users_with_impressions == 1 OR count($actual_sales_array) == 1) && $actual_sales_array[0]['id'] == 'all')
					{
						unset($actual_sales_array[0]);
					}

					$result['results'] = $actual_sales_array;
				}
			}
		}
		echo json_encode($result);
	}
	
	public function ajax_get_bulk_download_sales_people_angular()
	{
		$actual_sales_array = array();
		$actual_sales_array['results'] = array();
		if(!$this->tank_auth->is_logged_in())
		{
			$actual_sales_array['response_code'] = 500;
			$actual_sales_array['error_message'] = 'Must be logged in to view campaign data';
		}
		else
		{
			$selected_partner = $this->input->post('selected_partner');
			$temp_start_date = $this->input->post('start_date');
			$temp_end_date = $this->input->post('end_date');
			$term = $this->input->post('q');
			$user_id = $this->tank_auth->get_user_id();
			$username = $this->tank_auth->get_username();
			$role = strtolower($this->tank_auth->get_role($username));
			$is_super = $this->tank_auth->get_isGroupSuper($username);
			if($role != 'sales' AND $role != 'admin' AND $role != 'ops')
			{
				$actual_sales_array['response_code'] = 500;
				$actual_sales_array['error_message'] = 'You do not have permission to view this content';
			}
			else if($selected_partner === false OR $temp_start_date === false OR $temp_end_date === false)
			{
				$actual_sales_array['response_code'] = 500;
				$actual_sales_array['error_message'] = 'Please select start date and end date';
			}
			else
			{
				$ui_start_date = strtotime($temp_start_date);
				$ui_end_date = strtotime($temp_end_date);
				if($ui_start_date > $ui_end_date) //user has flipped the dates in the ui
				{
					$actual_sales_array['response_code'] = 500;
					$actual_sales_array['error_message'] = 'Selected start date is later than selected end date';
				}
				else
				{	
					$sales_array = $this->get_sales_people_for_user_by_partner($selected_partner, $role, $user_id, $is_super, $result);
					if($term == '%')
					{
					    $all_array_option = array('id' => 'all', 'username' => 'All Account Executives','key' => 'all', 'text' => 'All Account Executives','partner' => '',);
					}

					if(count($sales_array) > 1 && $term == '%')
					{
						array_unshift($sales_array, $all_array_option); 
					}
					else if(count($sales_array) === 0)
					{
						$actual_sales_array['response_code'] = 500;
						$actual_sales_array['errors'] = 'No users available with selection';
					}
					
					
					$num_users_with_impressions = 0;

					foreach($sales_array as $key => $sales_user)
					{
						if($sales_user['id'] !== "all")
						{
							$sales_user['has_impressions'] = false;
							if(!is_null($sales_user['earliest_impression']) OR !is_null($sales_user['latest_impression']))
							{
								$user_start_date = strtotime($sales_user['earliest_impression']);
								$user_end_date = strtotime($sales_user['latest_impression']);

								if($user_start_date <= $ui_end_date AND $user_end_date >= $ui_start_date)
								{
								    if(!empty($term) && $term != '%' )
								    {
									if(stripos($sales_user['username'],$term) !== FALSE )
									{
										$sales_user['key'] = $key;
										$sales_user['text'] = $sales_user['username'];
										$actual_sales_array['results'][] = $sales_user;
										$sales_user['has_impressions'] = true;
										$num_users_with_impressions++;
									}
								    }
								    else
								    {
									    $sales_user['key'] = $key;
									    $sales_user['text'] = $sales_user['username'];
									    $actual_sales_array['results'][] = $sales_user;
									    $sales_user['has_impressions'] = true;
									    $num_users_with_impressions++;
								    }
								}
							}
						}
						else
						{
							$actual_sales_array['results'][] = $sales_user;
							$sales_user['has_impressions'] = null;
						}
					}
					if(($num_users_with_impressions == 1 OR count($actual_sales_array['results']) == 1) && $actual_sales_array['results'][0]['id'] == 'all')
					{
						unset($actual_sales_array['results'][0]);
					}
				}
			}
		}
		echo json_encode($actual_sales_array);
	}
	
	private function get_sales_people_for_user_by_partner($selected_partner, $role, $user_id, $is_super, &$result)
	{
		$sales_array = array();
		if($selected_partner === 'all')
		{
			if($role == 'admin' OR $role == 'ops')
			{
				$temp_sales_array = $this->campaign_health_model->get_all_sales_people_with_partner(); //returns array() on error
				if(!empty($temp_sales_array))
				{
					$sales_array = $temp_sales_array;
				}
			}
			else
			{
				$sales_array = $this->tank_auth->get_sales_people_by_partners_allowed_by_user($user_id, $is_super); //returns array() on error
			}
		}
		else if(is_numeric($selected_partner))
		{
			$sales_array = $this->campaign_health_model->get_sales_people_by_selected_partner_hierarchy($user_id, $is_super, $role, $selected_partner);
		}
		else
		{
			$result['is_success'] = false;
			$result['errors'] = 'Error 494510: Invalid partner selected';
		}
		return $sales_array;
	}

	public function ajax_get_campaigns_data()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' AND $this->tank_auth->is_logged_in())
		{
			ini_set('memory_limit', '-1');
			$return_array = array('is_same_sales' => 1, 'is_same_partner' => 1, 'campaign_array' => array());
			$report_date = $this->input->post('report_date');
			if(empty($report_date))
			{
				$report_date_array = $this->campaign_health_model->get_last_cached_impression_date();
				if(count($report_date_array) > 0)
				{
					$report_date = $report_date_array["latest_impression_date"];
				}
			}
			else
			{
				$report_date = date("Y-m-d", strtotime($report_date. ' + 1 day'));
			}
			
			$campaigns_main_ops_referer_flag = false;
			if(isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'campaigns_main') !== false))
			{
			    $campaigns_main_ops_referer_flag = true;
			}	

			
			$return_array['report_date'] = $report_date;
			
			$campaign_tags_string = $this->input->post('campaign_tags');
			if($report_date !== false)
			{
				$username = $this->tank_auth->get_username();
				$user_id = $this->tank_auth->get_user_id();
				$role = strtolower($this->tank_auth->get_role($username));
				$is_super = $this->tank_auth->get_isGroupSuper($username);
				$tag_where_clause = "";
				$tag_binding_array = array();

				if($campaign_tags_string !== "")
				{
					$tag_sql_string = "";
					$tag_parse_response = $this->campaign_tags_model->parse_campaign_tag_string($campaign_tags_string, $tag_sql_string);
					if(!empty($tag_parse_response))
					{
						$tag_where_clause = $tag_sql_string;
						$tag_binding_array = $tag_parse_response;
					}
				}
				$raw_campaign_data = $this->campaign_health_model->get_campaigns_main_v2_data($role, $is_super, $user_id, $report_date, $tag_where_clause, $tag_binding_array, $campaigns_main_ops_referer_flag);
				if(count($raw_campaign_data) > 0)
				{
					reset($raw_campaign_data);
					$first_key = key($raw_campaign_data);
					if($role == 'sales')
					{
						$duplicate_sales = $raw_campaign_data[$first_key]['sales_person'];
						$duplicate_partner = $raw_campaign_data[$first_key]['partner'];
					}
					else
					{
						$return_array['is_same_sales'] = 0;
						$return_array['is_same_partner'] = 0;
					}
				}

				$target_impressions_data=$this->campaign_health_model->get_target_impressions_data($report_date);
				$action_date_data=$this->campaign_health_model->get_action_date_data(null);
				$next_flight_data=$this->campaign_health_model->next_flight_data($report_date);
				$timeseries_string_data=$this->campaign_health_model->get_timeseries_data_for_hover("html");
				$campaign_end_date_data=$this->campaign_health_model->get_campaign_end_date();
				//$campaign_notes_data=$this->campaign_health_model->get_campaign_notes_data();
				$campaign_notes_data = array();
				$campaign_io_data = $this->campaign_health_model->get_campaign_io_data();
					
				$campaigns_for_budget_calculation = array();
				
				foreach($raw_campaign_data as $campaign)
				{
					$temp_campaign_data = $this->get_final_campaign_data($campaign, $report_date, $role, $target_impressions_data, $action_date_data, $timeseries_string_data, $campaign_end_date_data, $campaign_notes_data, $next_flight_data, $campaign_io_data);
					if($temp_campaign_data !== false)
					{
						if($return_array['is_same_sales'] == 1)
						{
							if($campaign['sales_person'] !== $duplicate_sales)
							{
								$return_array['is_same_sales'] = 0;
							}
						}
						if($return_array['is_same_partner'] == 1)
						{
							if($campaign['partner'] !== $duplicate_partner)
							{
								$return_array['is_same_partner'] = 0;
							}
						}						
						$return_array['campaign_array'][] = $temp_campaign_data;
						
						if(!$campaigns_main_ops_referer_flag)
						{
							$campaigns_for_budget_calculation[] = $temp_campaign_data["id"];
						}
					}
				}
				
				//Fetch the budget info for campaigns.
				if (count($campaigns_for_budget_calculation) > 0)
				{
					$campaigns_budget_info = $this->campaign_health_model->get_budget_info_for_campaigns(implode(",",$campaigns_for_budget_calculation), $report_date);
					
					if ($campaigns_budget_info['result'] && count($campaigns_budget_info['result']) > 0)
					{
						foreach ($return_array['campaign_array'] AS &$campaign)
						{
							if (isset($campaigns_budget_info['result'][$campaign["id"]]))
							{
								$campaign['budget_info'] = $campaigns_budget_info['result'][$campaign["id"]];
								$campaign['schedule'] = $campaigns_budget_info['result'][$campaign["id"]]['schedule'];
							}
						}
					}
				}
				
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}
	
	public function test_budgets_for_campaigns_and_report_date($campaign_ids, $report_date)
	{
		if ($this->tank_auth->is_logged_in() && !empty($campaign_ids) && !empty($campaign_ids))
		{
			$username = $this->tank_auth->get_username();
			$role = strtolower($this->tank_auth->get_role($username));
			
			if ($role == 'admin')
			{
				$campaigns_budget_info = $this->campaign_health_model->get_budget_info_for_campaigns($campaign_ids, $report_date);
				echo '<pre>'.json_encode($campaigns_budget_info,JSON_PRETTY_PRINT).'<pre>';
			}
			else
			{
				echo "You don't have permission to perform this operation";
			}
		}		
	}
	
	public function ajax_create_ticket()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST' AND $this->tank_auth->is_logged_in())
		{
			if ($this->vl_platform->get_access_permission_redirect('create_helpdesk_ticket') === '')
			{
				$campaign_id = $this->input->post('campaign_id');
				if ($campaign_id)
				{
					$user_email = $this->tank_auth->get_email($this->tank_auth->get_username());
					$campaign = $this->tank_auth->get_campaign_details_by_id($campaign_id);
					$advertiser = $this->campaign_health_model->get_advertiser_name_for_campaign($campaign_id);
					$subject_line = $advertiser ." - ". $campaign['Name'] ." | ". $this->input->post('subject');
					$body = $this->input->post('body');

					$subject = $subject_line;
					$from = 'No Reply <no-reply@brandcdn.com>';
					$to = 'helpdesk@brandcdn.com';
					$post_overrides = array(
						"h:reply-to" => $user_email, 
						"cc" => $user_email
					);
				    if($this->session->userdata('is_demo_partner') != 1) 
				    {
					$result = mailgun(
						$from,
						$to,
						$subject,
						$body,
						"html",
						$post_overrides
					);
					$this->output->set_output(json_encode($result));
				    }
					
				}
				else
				{
					$this->output->set_status_header(400);
					$this->output->set_output('Campaign ID is required.');
				}
			}
			else
			{
				$this->output->set_status_header(401);
				$this->output->set_output('You are not authorized to create a ticket.');
			}
		}
	}

	private function get_final_campaign_data($campaign, $report_date, $role, $target_impressions_data, $action_date_data, $timeseries_string_data, $campaign_end_date_data, $campaign_notes_data, $next_flight_data, $campaign_io_data)
	{
		$target_total_impressions_array=$target_impressions_data['target_total_impressions_array'];
		$target_total_impressions = 0;
		if (array_key_exists('c-'.$campaign['id'], $target_total_impressions_array))
			$target_total_impressions=$target_total_impressions_array['c-'.$campaign['id']];

		$action_date = null;
		if (array_key_exists('pause_date', $campaign) && $campaign['pause_date'] == "" && array_key_exists('c-'.$campaign['id'], $action_date_data))
			$action_date=$action_date_data['c-'.$campaign['id']];
		else
			$action_date="2050-01-01";

		$timeseries_string = null;
		if (array_key_exists('c-'.$campaign['id'], $timeseries_string_data))
			$timeseries_string=$timeseries_string_data['c-'.$campaign['id']];
		else
			$timeseries_string="";
		
		$campaign_notes_string = null;
		if (array_key_exists('c-'.$campaign['id'], $campaign_notes_data))
			$campaign_notes_string=$campaign_notes_data['c-'.$campaign['id']];
		else
			$campaign_notes_string="";

		$prorated_target_total_impressions_array=$target_impressions_data['prorated_target_total_impressions_array'];

		$campaign_end_date = null;
		if (array_key_exists('c-'.$campaign['id'], $campaign_end_date_data))
			$campaign_end_date=$campaign_end_date_data['c-'.$campaign['id']];
		else
			$campaign_end_date="";
		
		$prorated_target_total_impressions = 1;
		if (array_key_exists('c-'.$campaign['id'], $prorated_target_total_impressions_array))
			$prorated_target_total_impressions=$prorated_target_total_impressions_array['c-'.$campaign['id']];

		$campaign_next_flight_array = null;
		if (array_key_exists('c-'.$campaign['id'], $next_flight_data))
			$campaign_next_flight_array=$next_flight_data['c-'.$campaign['id']];
		else
			$campaign_next_flight_array=array();
		
		$target_impressions_data=null;
		$result_array = $campaign;
		$result_array['friendly_type'] = "**TIME_SERIES**";
		$result_array['ttd_pending_flag'] = false;

		$result_array['action_date']=$action_date;

		$result_array['timeseries_string']=$timeseries_string;
		$result_array['campaign_end_date']=$campaign_end_date;
		$result_array['campaign_notes_string']=$campaign_notes_string;
		// This method will check campaign is geofencing or not.
		$result_array['is_geofencing'] = $this->campaign_health_model->check_campaign_is_geofencing($campaign['id']);;

		$actual_reset_date = date("Y-m-d", strtotime($report_date. ' - 1 day'));

		if(!empty($result_array['ttd_daily_modify']))
		{
			//must default report date to have 00:00:00 hours:minutes:seconds so it does not use current
			
			$report_date_datetime = datetime::createfromformat('Y-m-d H:i:s', $actual_reset_date." 00:00:00", new DateTimeZone('UTC'));
			$daily_modify_datetime = datetime::createfromformat('Y-m-d H:i:s', $result_array['ttd_daily_modify'], new DateTimeZone('UTC'));
			
			if($report_date_datetime <= $daily_modify_datetime)
			{
				$result_array['ttd_pending_flag'] = true;
			}
		}

		//new type
		$result_array['first_reset_date'] = date("Y-m-t", strtotime($campaign['start_date']));
		$next_reset_date = date("Y-m-t", strtotime($report_date));

		if(strtotime($campaign['cycle_end_date']) < strtotime($next_reset_date))
		{
			$next_reset_date = date("Y-m-d", strtotime($campaign['cycle_end_date'])); //If end date is before next reset date 
		}
		
		$time_to_next_reset = $this->campaign_health_model->date_subtract($next_reset_date,$result_array['first_reset_date']);

		$complete_cycles_since_first_reset = ($time_to_next_reset['y']*12+$time_to_next_reset['m'])*($time_to_next_reset['invert']);

		$result_array['time_since_first_reset'] = $time_to_next_reset;
	
		$last_reset_date=$campaign['cycle_start_date'];//NEW 
		$result_array['cycle_start_date'] =$campaign['cycle_start_date'];
		if ($last_reset_date == null)
			$last_reset_date=$report_date;
		$cycle_live_days = date_diff(datetime::createfromformat('Y-m-d',$last_reset_date), datetime::createfromformat('Y-m-d',$report_date))->days;
		$cycle_days_remaining = $campaign['days_left'];//date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$next_reset_date))->days;
		$cycle_divisor = $this->get_cycle_divisor($cycle_live_days, $cycle_days_remaining);
		$result_array['long_term_target'] = ($campaign['target_impressions']*1000)/($cycle_divisor);
		
		if(empty($result_array['start_date']))
		{
			$result_array['start_date'] = "-";
		}
		$result_array['next_reset'] = $next_reset_date;
		 
		$result_array['lifetime_remaining_impressions'] = $target_total_impressions - $result_array['total_impressions'];
		$result_array['lifetime_target_to_next_bill_date'] = $cycle_days_remaining == 0? 0 : $result_array['lifetime_remaining_impressions']/($cycle_days_remaining);
		
		$result_array['lifetime_oti'] = 0;
		if ($prorated_target_total_impressions > 0)
			$result_array['lifetime_oti'] = $campaign['total_impressions'] / ($prorated_target_total_impressions);
		
		if($role == 'admin' or $role == 'ops')
		{
			$result_array['cycle_remaining_impressions'] = $campaign['target_impressions']*1000 - $result_array['cycle_impressions'];
			$result_array['cycle_target_to_next_bill_date'] = 0;
			if ($cycle_days_remaining > 0 && $result_array['cycle_total_days'] > 0)
				$result_array['cycle_target_to_next_bill_date'] = $cycle_days_remaining == 0?  0 : (($result_array['target_impressions']*1000) - $result_array['cycle_impressions'] )/ ($cycle_days_remaining);

			$result_array['cycle_oti'] = 0;
			
			if ($result_array['target_impressions'] == null || $result_array['target_impressions'] == "0")
			{
				$result_array['cycle_oti'] = "1";
			}
			else if ($result_array['cycle_impressions'] > 0 && $result_array['target_impressions'] > 0 && $result_array['cycle_total_days'] > 0 &&$result_array['days_left']/$result_array['cycle_total_days'] > 0
				&& (1000*$result_array['target_impressions']*(($result_array['cycle_total_days']-$result_array['days_left'])/$result_array['cycle_total_days'])))
			{
				$result_array['cycle_oti'] = ($result_array['cycle_impressions'])/(1000*$result_array['target_impressions']*(($result_array['cycle_total_days']-$result_array['days_left'])/$result_array['cycle_total_days']));
			}
			$result_array['ctr'] = ($result_array['total_impressions'] == 0) ? 0 : $result_array['total_clicks']/$result_array['total_impressions'];
			$result_array['engagement_rate'] = ($result_array['total_impressions'] == 0) ? 0 : $result_array['total_engagements']/$result_array['total_impressions'];
			$result_array['ttd_data'] = 0;
			
			$result_array['nf_oti']="0";
			if ($campaign['yday_impressions'] != null && $campaign['yday_impressions'] > 0 && 
				array_key_exists('next_flight_days', $campaign_next_flight_array) && $campaign_next_flight_array['next_flight_days'] != null && $campaign_next_flight_array['next_flight_days'] > 0 &&
				array_key_exists('next_flight_impr', $campaign_next_flight_array) && $campaign_next_flight_array['next_flight_impr'] != null && $campaign_next_flight_array['next_flight_impr'] > 0  
				)
			{
				$nf_ideal_pace=$campaign_next_flight_array['next_flight_impr']/($campaign_next_flight_array['next_flight_days']);
				$result_array['nf_oti']=$campaign['yday_impressions']/($nf_ideal_pace);
				$result_array['nf_oti_hover']="Y'day impressions : "  .  number_format($campaign['yday_impressions']). 
				", NF Ideal Pace : " . number_format($nf_ideal_pace) . " (". number_format($campaign_next_flight_array['next_flight_impr']). "/". 
					number_format($campaign_next_flight_array['next_flight_days']) . ")" ;
			}
				 
			$alert_string="";
			
			if ($campaign['pause_date'] != "" && strtotime($campaign_end_date) <= strtotime($actual_reset_date) && strtotime($campaign['pause_date']) <= strtotime($report_date))
			{
				$alert_string.="PAUS_2: Paused campaign with Campaign End date in past";
			}
			else if ($campaign['pause_date'] != "" && strtotime($campaign['pause_date']) < strtotime($actual_reset_date) && $campaign['yday_impressions'] > 5)
			{
				$alert_string.="PAUS_1: Paused campaign with impressions yesterday";
			}
			else if ($campaign['pause_date'] != "" && strtotime($campaign['pause_date']) <= strtotime($report_date))
			{
				$alert_string.="PAUS_0: Paused campaign";
			}
			else if ($campaign['yday_impressions'] <= 5 && $campaign['cycle_end_date'] != "" && $result_array['target_impressions'] > 0 &&
				strtotime($campaign['cycle_start_date']) < strtotime($report_date) )
			{
				$alert_string.="LIVE_0: Live campaign with 0 impressions yday";
			}
			else if ($campaign['yday_impressions'] > 5 && $campaign['cycle_end_date'] != "" && $result_array['target_impressions'] == 0 &&
				strtotime($campaign['cycle_start_date']) < strtotime($report_date) )
			{
				$alert_string.="OFF_01:  Timeseries target in 0 cycle that has > 0 ( ".$campaign['yday_impressions']." ) Impressions yesterday" ;
			}
			else if ($campaign['yday_impressions'] > 5 && $campaign['cycle_end_date'] == "" && strtotime($campaign_end_date) < strtotime($actual_reset_date) )
			{
				$alert_string.="ARCH_1: Campaign End Date in past with > 0 (".$campaign['yday_impressions'].") Impressions yesterday" ;
			}
			else if($campaign_end_date != "" && ( strtotime($campaign_end_date) < strtotime($report_date) ) && $campaign['yday_impressions'] <= 5 )
			{
				$alert_string.="ARCH_0: Please archive. Campaign End Date in past with 0 Impressions yesterday.";
			}
			else
				$alert_string.="NOTES";
			
			$result_array['alerts_string']=$alert_string;

		}
		if($campaign['last_month_impressions'] > 0 and $campaign['rtg_last_month_impressions'] > 0)
		{
			$result_array['rtg_weight'] = $campaign['rtg_last_month_impressions']/$campaign['last_month_impressions'];
		}
		else
		{
			$result_array['rtg_weight'] = 0;
		}
		
		$result_array['order_id_obj'] = "-";
		$result_array['unique_display_id_obj'] = "";
		if (array_key_exists('c-'.$campaign['id'], $campaign_io_data))
		{
			$campaign_io_fields_array = $campaign_io_data['c-'.$campaign['id']];
			
			if (array_key_exists('order_id', $campaign_io_fields_array))
			{
				$result_array['order_id_obj'] = $campaign_io_fields_array['order_id'];
			}
			if (array_key_exists('unique_display_id', $campaign_io_fields_array))
			{
				$result_array['unique_display_id_obj'] = $campaign_io_fields_array['unique_display_id'];
			}
		}
		
		return $result_array;
		
	}

	public function update_action_date_flag()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");

		if($verify_ajax_response['is_success'] === true)
		{
			$campaign_id=$this->input->post('campaign_id');
			$series_date=$this->input->post('series_date');
			$return_message=$this->campaign_health_model->update_action_date_flag($campaign_id, $series_date);
			$return_array["message"]=$return_message;
		}
		echo json_encode($return_array);
	}

	public function health_v2($raw_campaign_tag_string = "")
	{
		if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('campaigns_main', '/campaigns_main'))
		{
			return;
		}
		$data = array();

		$username = $this->tank_auth->get_username();
		$user_id = $this->tank_auth->get_user_id();
		$data['partner_id'] = $this->tank_auth->get_partner_id($user_id);
		$data['role'] = strtolower($this->tank_auth->get_role($username));
		$data['is_super'] = $this->tank_auth->get_isGroupSuper($username);
	
		$active_feature_button_id = 'campaigns_main';
		$data['title'] = 'Campaigns';
		
		if($data['role'] == 'sales'){
		    redirect("campaigns");
		    return;
		}
		$report_date = $this->campaign_health_model->get_last_cached_impression_date();
		if(!empty($report_date))
		{
			$data['report_date'] = date('Y-m-d', strtotime($report_date['latest_impression_date']));
			$last_month_obj = new DateTime($data['report_date']);
			$last_month_obj->modify('-1 month');
			$data['one_month_ago'] = $last_month_obj->format('Y-m-d');

			$budget_operands = $this->tradedesk_model->get_ttd_budget_numbers();
			
			$data['mini_form_multiplier'] = 1 + $budget_operands['impression_leakage_buffer'];

			$data['bd_partner_options'] = "";
			$data['bd_sales_person_options'] = "";
			$partners_array = array();
			$data['campaign_tags_string'] = "";
			$data['campaign_tags_input'] = "";
			$data['campaign_tags_error'] = "";
			$data['campaign_tag_ids'] = "{}";
			if($data['role'] == 'admin' OR $data['role'] == 'ops')
			{
				if($raw_campaign_tag_string !== "")
				{
					$campaign_tag_string = html_entity_decode($raw_campaign_tag_string);
					$campaign_tag_response = $this->campaign_tags_model->interpret_campaign_tags_string($campaign_tag_string);
					if($campaign_tag_response['is_success'] === true)
					{
						$data['campaign_tags_string'] = $campaign_tag_response['campaign_tags_string'];
						$data['campaign_tags_input'] = $campaign_tag_string;
						$data['campaign_tag_ids'] = $campaign_tag_response['campaign_tag_ids'];
					}
					else
					{
						$data['campaign_tags_error'] = $campaign_tag_response['errors'];
					}
				}
				$partners_array = $this->tank_auth->get_partner_array();
			}
			else if($data['role'] == 'sales')
			{
				if($raw_campaign_tag_string !== "")
				{
					//if sales user and a parameter to campaigns_main is detected show_404
					show_404();
					return;
				}
				$partners_array = $this->tank_auth->get_partner_hierarchy_by_sales_person($user_id, $data['is_super']);
			}

			if(count($partners_array) > 1)
			{
				$data['bd_partner_options'] .= '<option value="all">All Partners</option>' . "\n";
			}

			foreach($partners_array as $value)
			{
				$data['bd_partner_options'] .= '<option value="'.$value["id"].'">'.$value["partner_name"].'</option>' . "\n";
			}
		
			$data['can_view_mpq'] = ($this->vl_platform->get_access_permission_redirect('mpq') == '' ? true : false);
			$data['can_create_ticket'] = $this->vl_platform->get_access_permission_redirect('create_helpdesk_ticket') === '';
			
			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'campaigns_main/healthcheck_html_v2',
				'campaigns_main/campaigns_main_html_header',
				NULL,
				'campaigns_main/healthcheck_js_v2',
				NULL
				);
		}
		else
		{
			show_404();
		}
	}

	public function campaigns_sales_view()
	{
	    if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('campaigns_main', '/campaigns'))
	    {
		    return;
	    }

	    if(! $this->tank_auth->is_logged_in())
	    {
		    $this->session->set_userdata('referer', '/campaigns');
		    redirect("login");
		    return;
	    }
	    $data = array('title' => 'Campaigns');

	    $active_feature_button_id = 'campaigns_main';
	    $this->vl_platform->show_views(
		    $this,
		    $data,
		    $active_feature_button_id,
		    'campaigns/body',
		    'campaigns/header',
		    NULL,
		    'campaigns/footer',
		    NULL,
		    TRUE,
		    TRUE
	    );
	}

	public function health_v2_angular()
	{
		$data = array('response_code' => 200);

		$username = $this->tank_auth->get_username();
		$user_id = $this->tank_auth->get_user_id();
		$data['partner_id'] = $this->tank_auth->get_partner_id($user_id);
		$data['role'] = strtolower($this->tank_auth->get_role($username));
		$data['is_super'] = $this->tank_auth->get_isGroupSuper($username);
		$term = $this->input->post('q');
	
		$report_date = $this->campaign_health_model->get_last_cached_impression_date();
		if(!empty($report_date))
		{
			$partners_array = array();
			if($data['role'] == 'admin' OR $data['role'] == 'ops')
			{
				$partners_array = $this->tank_auth->get_partner_array();
			}
			else if($data['role'] == 'sales')
			{
				$partners_array = $this->tank_auth->get_partner_hierarchy_by_sales_person($user_id, $data['is_super']);
			}

			if($term == '%' )
			{
			    $data['results'] = [array('id' => 'all', 'text' => 'All Partners')];
			}

			foreach($partners_array as $value)
			{
			    if(!empty($term) && $term != '%' )
			    {
				if(stripos($value["partner_name"],$term) !== FALSE )
				{
				    $data['results'][] = array('id' => $value["id"], 'text' => $value["partner_name"]);
				}
			    }
			    else
			    {
				$data['results'][] = array('id' => $value["id"], 'text' => $value["partner_name"]);
			    }
			}
			
		
		}
		else
		{
			$data['response_code'] = 500;
			$data['error_message'] = 'No partners found';
		}
		echo json_encode($data);
	}
	
	private function calculate_cycle_impression_for_campaign($campaign, $report_date)
	{
		$cycle_impressions = 0;
		$cycle_dates_array=$this->campaign_health_model->get_current_time_series_dates($campaign['id'], $report_date);
		if ($cycle_dates_array != null && count($cycle_dates_array) > 0 &&  array_key_exists('start_date', $cycle_dates_array) == 'true')
		{
			//Populate cycle impressions per adgroup in AdGroup table.
			$this->campaign_health_model->populate_cycle_impressions_by_adgroup($campaign['id'], $cycle_dates_array['start_date'], $cycle_dates_array['end_date']);			
			$cycle_impressions_raw = $this->campaign_health_model->get_impressions_total($campaign['id'], $cycle_dates_array['start_date'], $cycle_dates_array['end_date']);			
			$cycle_impressions = $cycle_impressions_raw[0]['total_impressions'];
		}
		
		return $cycle_impressions;
	}

	//must be run from the cli
	public function cache_cycle_impressions()
	{
		if($this->input->is_cli_request())
		{
			$report_date_response = $this->campaign_health_model->get_last_cached_impression_date();
			if(!empty($report_date_response))
			{
				$report_date = $report_date_response['latest_impression_date'];
				
				$all_campaigns_response = $this->campaign_health_model->get_campaign_info_for_cycle_impressions();
				if(!empty($all_campaigns_response))
				{
					//Reset cached_city_record_cycle_impression_sum for all adgroups to 0.
					$this->campaign_health_model->reset_all_adgroups_cached_city_record_cycle_impression_sum();										
					$error_str = "";
					$count = 0;
					$total = count($all_campaigns_response);
					$last_percent = 5;
					foreach($all_campaigns_response as $campaign)
					{
						$cycle_impressions = $this->calculate_cycle_impression_for_campaign($campaign, $report_date);
						if($this->campaign_health_model->update_campaign_with_cycle_impressions($campaign['id'], $cycle_impressions))
						{
							$count++;
						}
						else
						{
							echo "Failed for campaign: ".$campaign['id']."\n";
						}
						
						if((100*$count/$total) > $last_percent)
						{
							echo "Update in progress...".$last_percent."%\n";
							$last_percent += 5;
						}
					}
					echo "Update Complete: " . $count . "/" . $total . " Campaigns updated successfully"."\n";
				}
				else
				{
					echo "Error: no campaigns found"."\n";
				}
			}
			else
			{
				echo "Error: unable to get report date"."\n";
			}
		}
		else
		{
			show_404();
		}
	}
	
	//must be run from the cli
	public function cache_lifetime_impressions()
	{
		if($this->input->is_cli_request())
		{
			$response = $this->campaign_health_model->update_adgroups_with_lifetime_impressions_clicks();
			if($response == true)
			{
				echo "SUCCESS: `cached_city_record_impression_sum` and `cached_city_record_click_sum` updated for AdGroups table"."\n";
			}
			else
			{
				echo "FAILURE: `cached_city_record_impression_sum` and `cached_city_record_click_sum` failed to update for AdGroups table"."\n";
			}
		}
		else
		{
			show_404();
		}
	}
	
	//must be run from the cli
	public function cache_lifetime_engagements()
	{
		if($this->input->is_cli_request())
		{
			if($this->campaign_health_model->update_adsets_with_lifetime_engagements())
			{
				echo "SUCCESS: `cached_engagement_record_sum` updated for cup_versions table"."\n";
			}
			else
			{
				echo "FAILURE: `cached_engagement_record_sum` failed to update for cup_versions table"."\n";
			}
		}
		else
		{
			show_404();
		}
	}

	public function cache_last_month_impressions()
	{
		if($this->input->is_cli_request())
		{
			$report_date_response = $this->campaign_health_model->get_last_cached_impression_date();
			if(!empty($report_date_response))
			{
				$report_date = $report_date_response['latest_impression_date'];
				$last_month_obj = new DateTime($report_date);
				$last_month_obj->modify('-1 month');
				$last_month_date = $last_month_obj->format('Y-m-d');
				$response = $this->campaign_health_model->update_adgroups_with_last_month_impressions($last_month_date, $report_date);
				if($response == true)
				{
					echo "SUCCESS: `cached_city_record_last_month_impression_sum` updated for AdGroups table"."\n";
				}
				else
				{
					echo "FAILURE: `cached_city_record_last_month_impression_sum` failed to update for AdGroups table"."\n";
				}
			}
			else
			{
				echo "Error: unable to get report date"."\n";
			}
		}
		else
		{
			show_404();
		}
	}

	public function cache_yday_impressions()
	{
		if($this->input->is_cli_request())
		{
			$report_date_response = $this->campaign_health_model->get_last_cached_impression_date();
			if(!empty($report_date_response))
			{
				$report_date = $report_date_response['latest_impression_date'];
				$response = $this->campaign_health_model->update_adgroups_with_yday_impressions($report_date);
				if($response == true)
				{
					echo "SUCCESS: `cached_city_record_yday_impression_sum` updated for AdGroups table"."\n";
				}
				else
				{
					echo "FAILURE: `cached_city_record_yday_impression_sum` failed to update for AdGroups table"."\n";
				}
			}
			else
			{
				echo "Error: unable to get report date"."\n";
			}
		}
		else
		{
			show_404();
		}
	}

	public function cache_adgroup_date_range()
	{
		if($this->input->is_cli_request())
		{
			$result = true;
			$response = $this->campaign_health_model->update_adgroups_with_earliest_latest_date();
			
			if($response['cities_response'] == true)
			{
				echo "SUCCESS: `earliest_city_record` and `latest_city_record` updated for AdGroups table"."\n";
			}
			else
			{
				echo "FAILURE: `earliest_city_record` and `latest_city_record` failed to update for AdGroups table"."\n";
				$result = false;
			}

			if($response['sites_response'] == true)
			{
				echo "SUCCESS: `earliest_site_record` and `latest_site_record` updated for AdGroups table"."\n";
			}
			else
			{
				echo "FAILURE: `earliest_site_record` and `latest_site_record` failed to update for AdGroups table"."\n";
				$result = false;
			}
			return $result;
   		}
		else
		{
			show_404();
		}
	}

    private function cache_single_campaign_cycle_impressions($campaign_id, $report_date)
	{
		$response = false;

		$campaign_response = $this->campaign_health_model->get_campaign_info_for_cycle_impressions($campaign_id);
		if(!empty($campaign_response))
		{
			$campaign = $campaign_response[0];
			$cycle_impressions = $this->calculate_cycle_impression_for_campaign($campaign, $report_date);

			$response = $this->campaign_health_model->update_campaign_with_cycle_impressions($campaign_id, $cycle_impressions);
		}
		
		return $response;
	}

	private function cache_single_campaign_impression_amounts($campaign_id, &$return_array)
	{
		if($this->cache_single_campaign_adgroup_date_range($campaign_id))
		{
			$report_date_response = $this->campaign_health_model->get_last_cached_impression_date();
			if(!empty($report_date_response))
			{
				$report_date = $report_date_response['latest_impression_date'];
				$yday_impressions_result = $this->cache_single_campaign_yday_impressions($campaign_id, $report_date);
				$last_month_impressions_result = $this->cache_single_campaign_last_month_impressions($campaign_id, $report_date);
				$lifetime_impressions_result = $this->cache_single_campaign_lifetime_impressions($campaign_id);
				$lifetime_engagements_result = $this->cache_single_campaign_lifetime_engagements($campaign_id);
				if($yday_impressions_result && $last_month_impressions_result && $lifetime_impressions_result)
				{
					$cycle_impressions_result = $this->cache_single_campaign_cycle_impressions($campaign_id, $report_date);
					if(!$cycle_impressions_result)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 91105: unable to cache cycle impressions for campaign.";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 91104: unable to cache impressions for campaign.";
				}
				if(!$lifetime_engagements_result)
				{
					if($return_array['errors'] !== "")
					{
						$return_array['errors'] .= " ";
					}
					$return_array['is_success'] = false;
					$return_array['errors'] .= "Error 91106: unable to cache engagements for campaign.";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 91103: unable to retrieve current report date.";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 91102: unable to cache date range for campaign.";
		}
	}
	
	private function cache_single_campaign_lifetime_engagements($campaign_id)
	{
		return $this->campaign_health_model->update_single_campaign_adsets_with_lifetime_engagements($campaign_id);
	}

	private function cache_single_campaign_adgroup_date_range($campaign_id)
	{
		return $this->campaign_health_model->update_single_campaign_adgroups_with_earliest_latest_date($campaign_id);
	}
	
	private function cache_single_campaign_yday_impressions($campaign_id, $report_date)
	{
		return $this->campaign_health_model->update_single_campaign_adgroups_with_yday_impressions($campaign_id, $report_date);
	}

	private function cache_single_campaign_last_month_impressions($campaign_id, $report_date)
	{
		$last_month_obj = new DateTime($report_date);
		$last_month_obj->modify('-1 month');
		$last_month_date = $last_month_obj->format('Y-m-d');
		return $this->campaign_health_model->update_single_campaign_adgroups_with_last_month_impressions($campaign_id, $last_month_date, $report_date);
	}

	private function cache_single_campaign_lifetime_impressions($campaign_id)
	{
		return $response = $this->campaign_health_model->update_single_campaign_adgroups_with_lifetime_impressions_clicks($campaign_id);
	}

	public function ajax_cache_single_campaign_impression_amounts()
	{
		if($this->input->is_ajax_request() && $_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			if($this->tank_auth->is_logged_in())
			{
				$campaign_id = $this->input->post('c_id');
				if($campaign_id !== false)
				{
					$this->cache_single_campaign_impression_amounts($campaign_id, $return_array);
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 91101: Invalid campaign id";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 91100: User not logged in";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}
	public function ajax_cache_single_campaign_cycle_impressions()
	{
		if($this->input->is_ajax_request() && $_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			if($this->tank_auth->is_logged_in())
			{
				$campaign_id = $this->input->post('c_id');
				if($campaign_id !== false)
				{
					$report_date_response = $this->campaign_health_model->get_last_cached_impression_date();
					if(!empty($report_date_response))
					{
						$report_date = $report_date_response['latest_impression_date'];
						$return_array['is_success'] = $this->cache_single_campaign_cycle_impressions($campaign_id, $report_date);
						if($return_array['is_success'] == false)
						{
							$return_array['errors'] = "Warning 55112: Unable to cache impression data for campaign: ".$campaign_id;
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Warning 55112.1: unable to retrieve current report date"; 
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 55109: Invalid campaign id";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 55100: User not logged in";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}
	public function ajax_remove_from_campaigns_main()
	{
		if( $this->input->is_ajax_request() && $_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "");
			if($this->tank_auth->is_logged_in())
			{
				$campaign_id = $this->input->post('c_id');
				if($campaign_id !== false)
				{
					$result = $this->campaign_health_model->update_campaign_view_status($campaign_id, 1);
					if($result === false)
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 59193: Unable to remove campaign: ".$campaign_id;
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 59109: Invalid campaign id";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 59100: User not logged in";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}
	
	//TODO: remove function from campaign_health controller that does this
	public function ajax_get_adsets_for_campaign()
	{
		if( $this->input->is_ajax_request() || $_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$return_array = array('is_success' => true, 'errors' => "", 'versions' => array());
			if($this->tank_auth->is_logged_in())
			{
				$c_id = $this->input->post('c_id');
				if($c_id !== false)
				{
					$versions = $this->campaign_health_model->get_versions_for_campaign($c_id);
					if($versions !== false)
					{
						$url_prefix = $this->campaign_health_model->get_brand_cdn_url_prefix($c_id);
						if($url_prefix == null)
						{
							$url = base_url();
						}
						else
						{
							$url = "http://".$url_prefix.".brandcdn.com/";
						}
						foreach($versions as $version)
						{
							$encoded = base64_encode(base64_encode(base64_encode($version['id'])));
							$newline_element = "";
							if(!empty($return_array['versions']))
							{
								$newline_element = "<br>";
							}
							$return_array['versions'][] = $newline_element."<a target='_blank' href='".$url."crtv/get_adset/".$encoded."'><h4>".$url."crtv/get_adset/".$encoded."</h4></a>";
						}
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = "Error 52101: No versions available";
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 52109: Invalid campaign id";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 52100: User not logged in";
			}
			echo json_encode($return_array);
		}
		else
		{
			show_404();
		}
	}
	public function ajax_update_ttd_timestamp()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");

		if($verify_ajax_response['is_success'] === true)
		{
			$c_id = $this->input->post('c_id');
			if($c_id !== false)
			{
				$response = $this->campaign_health_model->modify_campaign_and_adgroups_with_current_time($c_id);
				if($response === false)
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 62101: Unable to update campaign with pending changes";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 62109: Invalid campaign id";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Errors 64063: User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	public function get_tradedesk_data_for_campaign()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array(
			'is_success' => true, 
			'errors' => "", 
			'data' => array(
				'pc_daily_impression_budget' => NULL, 
				'pc_daily_impression_realized' => NULL
				)
			);
		if($verify_ajax_response['is_success'] === true)
		{
			$raw_campaign_id = $this->input->post('campaign');
			if($raw_campaign_id !== false)
			{
				$campaign = $this->tank_auth->get_campaign_details_by_id($raw_campaign_id);
				if($campaign !== NULL)
				{
					if(!empty($campaign['ttd_campaign_id']))
					{
						$pc_adgroup_data = $this->campaign_health_model->get_pc_adgroup_by_campaign_id($campaign['id']);
						$access_token = $this->tradedesk_model->get_access_token();
						if($pc_adgroup_data !== false)
						{
							$ttd_response = $this->tradedesk_model->get_adgroup_daily_target_totals($access_token, $campaign['ttd_campaign_id'], $pc_adgroup_data['ID']);
							if(!empty($ttd_response) and $ttd_response['pc_adgroup_info'] !== false)
							{
								if($pc_adgroup_data['cached_city_record_yday_impression_sum'] === null)
								{
									$pc_adgroup_data['cached_city_record_yday_impression_sum'] = 0;
								}
								$return_array['data'] = array(
									'total_target' => $ttd_response['total_target'],
									'non_pc_target_impressions' => $ttd_response['non_pc_target'],
									'pc_daily_impression_realized' => $pc_adgroup_data['cached_city_record_yday_impression_sum'],
									'pc_daily_impression_budget' => $ttd_response['pc_adgroup_info']->RTBAttributes->DailyBudgetInImpressions);
							}
							else
							{
								$return_array['is_success'] = false;
								$return_array['errors'] = "Error 62100: error processing bidder data for campaign";
							}
						}
						else
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 62959: No pc adgroup data found for campaign";
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
				$return_array['errors'] = "Error 62109: Invalid campaign parameters";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 64179: User logged out or not permitted";
		}
		$return_check = $this->campaign_health_model->check_action_date_flag($raw_campaign_id);
		$return_array['data']['check_action_date'] = $return_check['action_check'];

		echo json_encode($return_array);
	}
	public function cache_all_impression_amounts()
	{
		if($this->input->is_cli_request())
		{
			echo "Begin cache adgroup date range."."\n";
			if($this->cache_adgroup_date_range())
			{
				echo "\n"."Begin cache yday impressions."."\n";
				$this->cache_yday_impressions();
				echo "\n"."Begin cache last month impressions."."\n";
				$this->cache_last_month_impressions();
				echo "\n"."Begin cache lifetime impressions."."\n";
				$this->cache_lifetime_impressions();
				echo "\n"."Begin cache cycle impressions."."\n";
				$this->cache_cycle_impressions();
			}
			else
			{
				echo "WARNING: All impressions script stopped because caching adgroup_date_range FAILED.  Try rerunning each caching script individually"."\n";
			}
		}
	}
	
	private function get_cycle_divisor($cycle_live_days, $cycle_days_remaining)
	{
		if($cycle_live_days == 0 and $cycle_days_remaining == 0)
		{
			$cycle_divisor = 1;
		}
		else
		{
			$cycle_divisor = $cycle_live_days + $cycle_days_remaining;
		}
		return $cycle_divisor;
	}
	
	public function ajax_check_user_bulk_pending_flag()
	{
		$allowed_roles = array('admin', 'ops', 'sales');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array(
			'is_success' => true, 
			'response_code' => 200,
			'error_message' => "", 
			'is_pending' => false,
			'user_email' => false
			);
		if($verify_ajax_response['is_success'] === true)
		{
			$user_id = $this->tank_auth->get_user_id();
			$user_results = $this->vl_auth_model->get_user_by_id($user_id);
			
			if($user_results !== null)
			{
				$return_array['user_email'] = $user_results->email;
				$return_array['is_pending'] = $this->is_user_pending($user_results->bulk_campaign_email_pending);
				if($return_array['is_pending'] === false)
				{
					$this->campaign_health_model->set_bulk_campaign_email_flag_for_user($user_id, false);
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['response_code'] = 500;
				$return_array['error_message'] = "Unable to retrieve user data";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['response_code'] = 500;
			$return_array['error_message'] = "User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	private function is_user_pending($user_pending_flag)
	{
		$utc_timezone = new DateTimeZone('UTC');
		$datetime_30_mins_ago = new DateTime("-30 minutes", $utc_timezone);
		if($user_pending_flag === null or datetime::createfromformat('Y-m-d H:i:s', $user_pending_flag) <= $datetime_30_mins_ago)
		{
			return false;
		}
		return true;
	}

	public function get_prepopulated_bulk_adgroup_putter_data()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$user_id = $this->tank_auth->get_user_id();
			$user_record = $this->vl_auth_model->get_user_by_id($user_id);
			if($this->tank_auth->is_logged_in() && $user_record !== null && ($user_record->role === 'ADMIN' || $user_record->role === 'OPS'))
			{
				$campaign_ids = $this->input->post('selected_campaign_ids');
				$report_date = $this->campaign_health_model->get_last_impression_date();
				$report_date = date('Y-m-d', strtotime($report_date[0]['value']));
				$next_flight_data = $this->campaign_health_model->next_flight_data($report_date);
								
				if($campaign_ids !== false and $campaign_ids !== "")
				{
					$formatted_campaigns = json_decode($campaign_ids);
					if(!empty($formatted_campaigns))
					{
						$result = $this->campaign_health_model->get_bulk_adgroup_putter_data($formatted_campaigns, $next_flight_data);
						$campaign_ideal_pace = array();
						foreach($formatted_campaigns as $campaign)
						{
						    $campaign_next_flight_array=$next_flight_data['c-'.$campaign];
						    $nf_ideal_pace = $campaign_next_flight_array['next_flight_impr']/($campaign_next_flight_array['next_flight_days']);
						    $campaign_ideal_pace[$campaign] = number_format($nf_ideal_pace,0,'','');
						}
						
						if(!empty($result))
						{
							foreach($result as $key => $campaign_item)
							{
							    if(array_key_exists($campaign_item['F Campaign ID'],$campaign_ideal_pace))
							    {
								$result[$key]['NF Ideal Pace'] = $campaign_ideal_pace[$campaign_item['F Campaign ID']];
							    }
							}    
							$this->load->view('campaigns_main/bulk_adgroup_putter_display', array('campaigns' => $result));
						}
						else
						{
							echo "Warning 939129: No Adgroups Found for selected campaigns";
						}
					}
					else
					{
						echo "Warning 939118: No campaigns selected";
					}
				}
				else
				{
					echo "Warning 939107: Invalid campaign parameters";
				}
			}
			else
			{
				echo "Warning 939163: User logged out or not permitted";
			}
		}
		else
		{
			show_404();
		}
	}

	public function get_prepopulated_bulk_timeseries_putter_data()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$user_id = $this->tank_auth->get_user_id();
			$user_record = $this->vl_auth_model->get_user_by_id($user_id);
			if($this->tank_auth->is_logged_in() && $user_record !== null && ($user_record->role === 'ADMIN' || $user_record->role === 'OPS'))
			{
				$campaign_ids = $this->input->post('selected_ts_campaign_ids');
				if($campaign_ids !== false and $campaign_ids !== "")
				{
					$formatted_campaigns = json_decode($campaign_ids);
					if(!empty($formatted_campaigns))
					{
						$result = $this->campaign_health_model->get_bulk_timeseries_putter_data($formatted_campaigns);
						if(!empty($result))
						{
							$this->load->view('campaigns_main/bulk_adgroup_putter_display', array('campaigns' => $result));
						}
						else
						{
							echo "Warning 939129: No Timeseries Found for selected campaigns";
						}
					}
					else
					{
						echo "Warning 939118: No campaigns selected";
					}
				}
				else
				{
					echo "Warning 939107: Invalid campaign parameters";
				}
			}
			else
			{
				echo "Warning 939163: User logged out or not permitted";
			}
		}
		else
		{
			show_404();
		}
	}

	public function ajax_archive_selected_campaigns()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");
		if($verify_ajax_response['is_success'] === true)
		{
			$campaign_ids = $this->input->post('campaigns');
			if(!empty($campaign_ids))
			{
				$this->campaign_health_model->bulk_archive_campaigns_by_id($campaign_ids, $return_array);
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Warning 914382: no campaigns found when trying to bulk archive";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Warning 914250: User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	public function insert_new_campaigns()
	{
		if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('campaigns_main', '/campaigns_main'))
		{
			return;
		}
		$table_mode = $this->input->get('table_mode');
		
		if ($table_mode == null || $table_mode == "")
		{
			echo "Please pass a table mode!!";
			return;
		}

		$result_arr=$this->campaign_health_model->bulk_migrate_campaigns_to_timeseries($table_mode);
		$total_ctr=0;
		foreach ($result_arr as $row) 
		{

			$id=$row['id'];
			 
			$start_date=$row['start_date'];
			$hard_end_date=$row['hard_end_date'];
			$term_type=$row['term_type'];
			$targetImpressions=$row['targetImpressions'];
			
		 	$time_series_array_for_campaign=get_timeseries_start_dates($term_type, $start_date, $hard_end_date);
		 	$time_series_array_new=array();
		 	$ctr=0;

		 	foreach ($time_series_array_for_campaign as $value) 
		 	{
		 		$time_series_array_new[$ctr]=array();
		 		$time_series_array_new[$ctr][0]=$value;
		 		if ($ctr < (count($time_series_array_for_campaign)-1))
		 			$time_series_array_new[$ctr][1]=$targetImpressions;
		 		else
		 			$time_series_array_new[$ctr][1]=0;
		 	
		 	$ctr++;
		 	}

		 	$total_ctr++;
		 	echo "-- ".$total_ctr."<br>";
		 	echo $this->al_model->delete_create_time_series_for_campaign($id, $time_series_array_new, "PRINT_QUERIES");
		 	echo ";\n\n<br><br>";

		 } 
		 echo "-- All printed. Finished..";
	}

	

	public function get_impressions_by_range()
	{
		$allowed_roles = array('admin' );
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");
		if($verify_ajax_response['is_success'] === true)
		{
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');
			$start_date_array = $this->campaign_health_model->get_target_impressions_data($start_date);
			$end_date_array = $this->campaign_health_model->get_target_impressions_data($end_date);
			$new_campaigns_array=array();
			foreach ($end_date_array['prorated_target_total_impressions_array'] as $cid => $impressions) 
			{
				if (array_key_exists($cid, $start_date_array['prorated_target_total_impressions_array']))
				{
					$initial_impressions = $start_date_array['prorated_target_total_impressions_array'][$cid];
					$impressions=$impressions-$initial_impressions;
				}
				
				if ($impressions > 0)
				{
					if (array_key_exists($cid, $end_date_array['campaign_names_array']))
					{
						$campaign_name = $end_date_array['campaign_names_array'][$cid];
						if ($cid != "c-0" && !(strpos($campaign_name,'zz_test') !== false) && !(strpos($campaign_name,'ZZ_TEST') !== false)) 
						{
							$c_array=  array();
							$c_array['name']=$campaign_name;
							$c_array['id']=$cid;
							$c_array['prorated_target_impressions']=round($impressions);
							$new_campaigns_array[]=$c_array;
						}
					}
				}
			}
			$return_array['new_campaigns_array']=$new_campaigns_array;
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Warning 914250: User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

  public function get_action_date_for_campaign()
  {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $cid = $this->input->post('cid');
        $action_date=$this->campaign_health_model->get_action_date_data($cid);
        $result['action_date']=$action_date;
      }
      echo json_encode($result);
  }

  public function notes_admin()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','campaigns_main/notes_admin');
			redirect('login');
			return;
		}
		$username=$this->tank_auth->get_username();
		$role=$this->tank_auth->get_role($username);
		if ($role=='admin' || $role=='ops')
		{
			// /setup common header
			$data['username']=$username;
			$data['firstname']=$this->tank_auth->get_firstname($data['username']);
			$data['lastname']=$this->tank_auth->get_lastname($data['username']);
			$data['user_id']=$this->tank_auth->get_user_id();
			$data['title']="Notes Admin";
			$data['notes_data'] = $this->al_model->get_all_notes_for_admin_view();
			$this->load->view('ad_linker/header', $data);
			$this->load->view('campaigns_main/notes_admin_view', $data);
		}
	}

	 public function revenue()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','campaigns_main/revenue');
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
			$data['title']="Revenue";
			$this->load->view('ad_linker/header', $data);
			$this->load->view('campaigns_main/impressions_calculation',$data);
		}
	}

	public function revenue_cpm()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','campaigns_main/revenue_cpm');
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
			$data['title']="Revenue CPM";
			
			$cpm_mode = $this->input->get('cpm_mode');
			if ($cpm_mode == null || $cpm_mode == "")
				$cpm_mode="p";

			$cpm_data = $this->campaign_health_model->fetch_cpm_grid($cpm_mode);
			$data['cpm_data']=$cpm_data;
			$data['cpm_mode']=$cpm_mode;
			$this->load->view('ad_linker/header', $data);
			$this->load->view('campaigns_main/revenue_cpm',$data);
		}
	}

	public function save_cpm_grid()
	{
		$allowed_roles = array('admin' );
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "");
		if($verify_ajax_response['is_success'] === true)
		{
			$cpm_data = $this->input->post('cpm_data');
			$start_date_array = $this->campaign_health_model->save_cpm_grid($cpm_data);
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Warning 914250: User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	 public function notes_search_advanced()
	 {
      $allowed_roles = array('admin', 'ops');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
        $ad_name = $this->input->post('ad_name');
        $cpn_name = $this->input->post('cpn_name');
        $cpn_minus = $this->input->post('cpn_minus');
        $notes_text = $this->input->post('notes_text');
        $user_name = $this->input->post('user_name');
        $mode = $this->input->post('mode');

        $search_result=$this->campaign_health_model->notes_search_advanced($ad_name, $cpn_name, $cpn_minus, $notes_text, $user_name, $mode);
        $result['search_result']=$search_result;
      }
      echo json_encode($result);
  }

  public function get_all_campaign_flights()
	 {
      $allowed_roles = array('admin');
      $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
      $result = array('result' => array());
      if($verify_ajax_response['is_success'] === true)
      {
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');
			$mode = $this->input->post('mode');
			$cpm = $this->input->post('cpm');
			$search_result=$this->campaign_health_model->get_timeseries_data_for_billing($start_date, $end_date, $mode, $cpm);
			$result['search_result']=$search_result;
      }
      echo json_encode($result);
  }
  
  public function update_reminder_flag()
  {
	$allowed_roles = array('admin', 'ops','sales');
	$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
	$result = array('result' => array());
	if($verify_ajax_response['is_success'] === true)
	{
		$campaign_id = $this->input->post('campaign_id');
		$reminder_status = $this->input->post('reminder_status');
		$report_date = $this->input->post('report_date');
		$update_result = $this->campaign_health_model->update_reminder_flag_status($campaign_id, $reminder_status);
		$return_check = $this->campaign_health_model->check_action_date_flag($campaign_id);
		$pending_check = $this->campaign_health_model->check_pending_date_flag($campaign_id, $report_date);

		$result['is_success'] = $update_result;
		$result['check_action_date'] = $return_check['action_check'];
		$result['check_pending'] = $pending_check['ttd_date_pending_flag'];
	}
	echo json_encode($result);
  }
}
?>
