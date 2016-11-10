<?php

class Advertisers_main extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('session', 'tank_auth', 'vl_platform'));
		$this->load->model(array('advertisers_main_model', 'campaign_health_model', 'vl_auth_model'));
		$this->load->helper(array('vl_ajax'));
	}

	public function index($preload_user = false)
	{
		if(!$this->vl_platform->has_permission_to_view_page_otherwise_redirect('ad_ops', '/advertisers_main'))
		{
			return;
		}
		$data = array('selected_user' => array( 'id' => 'none', 'text' => 'Select User'));
		if($preload_user !== false)
		{
			if($preload_user == 'all')
			{
				$data['selected_user'] = array('id' => 'all', 'text' => 'All');
			}
			else
			{
				if(ctype_digit($preload_user))
				{
					$response = $this->advertisers_main_model->get_ops_owner_data_by_id($preload_user);
				}
				else
				{
					$response = $this->advertisers_main_model->get_ops_owner_data_by_email($preload_user);
				}
				if(!empty($response))
				{
					$data['selected_user'] = $response;
				}
			}

		}
		$report_date = $this->campaign_health_model->get_last_cached_impression_date();
		if(!empty($report_date))
		{
			$data['title'] = 'Advertisers Main';
			$data['report_date'] = date('Y-m-d', strtotime($report_date['latest_impression_date']));
			$active_feature_button_id = 'ad_ops';

			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'advertisers_main/advertisers_main_html',
				'advertisers_main/advertisers_main_header',
				NULL,
				'advertisers_main/advertisers_main_js',
				NULL
				);
		}
		else
		{
			show_404();
		}
	}
	
	public function ajax_get_advertiser_card_data()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "", 'more' => false, 'data' => array());
		if($verify_ajax_response['is_success'] === true)
		{
			$user_id = $this->input->post('user_id');
			$start_date = $this->input->post('start_date');
			$end_date = $this->input->post('end_date');
			$offset = $this->input->post('offset');
			$limit = $this->input->post('limit');
			if($user_id !== false and $start_date !== false and $end_date !== false and $offset !== false and $limit !== false)
			{
				$start = date('Y-m-d', strtotime($start_date));
				$end = date('Y-m-d', strtotime($end_date));
				$result = $this->advertisers_main_model->get_advertisers_main_data_by_advertiser_owner($user_id, $start, $end, ($offset+0), ($limit+1));
				if($result === false)
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 754830: database error when getting advertiser data";
				}
				else if(!empty($result))
				{
					$advertisers = array();
					foreach($result as $v)
					{
						$v['date'] = date('m/d/Y', strtotime($v['date']));
						if(array_key_exists($v['advertiser_id'], $advertisers))
						{
							if(array_key_exists($v['date'], $advertisers[$v['advertiser_id']]['graph_data']))
							{
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['impressions'] += $v['impressions'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['pr_impressions'] += $v['pr_impressions'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['non_pr_impressions'] += $v['non_pr_impressions'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['pr_clicks'] += $v['pr_clicks'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['non_pr_clicks'] += $v['non_pr_clicks'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['pr_view_throughs'] += $v['pr_view_throughs'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['non_pr_view_throughs'] += $v['non_pr_view_throughs'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['pr_100_percent_completions'] += $v['pre_roll_completions'];
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']]['ad_interactions'] += $v['ad_interactions'];
							}
							else
							{
								$advertisers[$v['advertiser_id']]['graph_data'][$v['date']] = array(
									'date' => $v['date'],
									'impressions' => $v['impressions'],
									'pr_impressions' => $v['pr_impressions'],
									'non_pr_impressions' => $v['non_pr_impressions'],
									'pr_clicks' => $v['pr_clicks'],
									'non_pr_clicks' => $v['non_pr_clicks'],
									'pr_view_throughs' => $v['pr_view_throughs'],
									'non_pr_view_throughs' => $v['non_pr_view_throughs'],
									'pr_100_percent_completions' => $v['pre_roll_completions'],
									'ad_interactions' => $v['ad_interactions']
								);
							}
							$advertisers[$v['advertiser_id']]['average_impressions'] += $v['impressions'];
							$advertisers[$v['advertiser_id']]['average_count']++;

						}
						else
						{
							$advertisers[$v['advertiser_id']] = array(
								'partner_name' => $v['partner_name'],
								'user_name' => $v['username'],
								'advertiser_id' => $v['advertiser_id'],
								'advertiser_name' => $v['advertiser_name'],
								'average_impressions' => $v['impressions'],
								'first_campaign_id' => $v['campaign_id'],
								'num_active_campaigns' => $v['num_active_campaigns'],
								'average_count' => 1,
								'graph_data' => array(
									$v['date'] => array(
										'impressions' => $v['impressions'],
										'pr_impressions' => $v['pr_impressions'],
										'non_pr_impressions' => $v['non_pr_impressions'],
										'pr_clicks' => $v['pr_clicks'],
										'non_pr_clicks' => $v['non_pr_clicks'],
										'pr_view_throughs' => $v['pr_view_throughs'],
										'non_pr_view_throughs' => $v['non_pr_view_throughs'],
										'pr_100_percent_completions' => $v['pre_roll_completions'],
										'ad_interactions' => $v['ad_interactions']
										)
									)
								);
						}
					}
					if(count($advertisers) == $limit +1)
					{
						
						$return_array['more'] = true;
						$removed = array_pop($advertisers);
					}
					foreach($advertisers as $key => $value)
					{
						$value['average_impressions'] = $value['average_impressions']/$value['average_count'];
					}
					
					$return_array['data'] = array_values($advertisers); 
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 754829: incorrect parameters when getting advertiser data";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 754828: user logged out or not permitted";
		}
		echo json_encode($return_array);
	}
	
  public function select2_get_advertiser_owners_with_advertisers()
  {
	  $allowed_roles = array('admin', 'ops');
	  $verify_ajax_response = vl_verify_ajax_call($allowed_roles);
	  $dropdown_list = array('result' => array(), 'more' => false);
	  if($verify_ajax_response['is_success'] === true)
	  {
		  $raw_term = $this->input->post('q');
		  $page_limit = $this->input->post('page_limit');
		  $page_number = $this->input->post('page');
		  if($raw_term && is_numeric($page_limit) && is_numeric($page_number))
		  {
			  if($raw_term != '%')
			  {
				  $search_term = '%'.$raw_term.'%';
			  }
			  else
			  {
				  $search_term = $raw_term;
			  }
			  $mysql_page_number = ($page_number - 1) * $page_limit;
			  $result = $this->advertisers_main_model->get_ops_owner_data_by_search_term($search_term, $mysql_page_number, ($page_limit + 1));
			  if(!empty($result))
			  {
				  if($page_number == 1)
				  {
					  $dropdown_list['result'][] = array("id" => "all", "text" => "All");
				  }
				  
				  if(count($result) == $page_limit + 1)
				  {
					  $real_count = $page_limit;
					  $dropdown_list['more'] = true;
				  }
				  else
				  {
					  $real_count = count($result);
				  }
				  
				  for($i = 0; $i < $real_count; $i++)
				  {
					  $dropdown_list['result'][] = array("id"=>$result[$i]['id'],	"text"=>$result[$i]['text']);
				  }
			  }
			  else if($raw_term == "All" and $page_number == 1)
			  {
				  $dropdown_list['result'][] = array("id" => "all", "text" => "All");
			  }
		  }
	  }
	  echo json_encode($dropdown_list);
  }
}

?>
