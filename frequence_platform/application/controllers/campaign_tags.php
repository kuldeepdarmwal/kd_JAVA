<?php

class Campaign_tags extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('session', 'tank_auth', 'vl_platform'));
		$this->load->model(array('vl_auth_model', 'campaign_tags_model'));
		$this->load->helper(array('vl_ajax'));
	}
	
	public function ajax_get_tag_utility_data()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "", 'tag_data' => array());
		if($verify_ajax_response['is_success'] === true)
		{
			$response = $this->campaign_tags_model->get_tag_utility_view_data();
			if(!empty($response))
			{
				$return_array['tag_data'] = $response;
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 462202: No tag data found for tag utility view";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 462201: User logged out or not permitted";
		}
		echo json_encode($return_array);
	}

	public function ajax_get_campaign_tag_data_for_selected_campaigns()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$return_array = array('is_success' => true, 'errors' => "", 'campaigns' => array());
		if($verify_ajax_response['is_success'] === true)
		{
			$campaign_ids = $this->input->post('selected_campaigns');
			if(!empty($campaign_ids))
			{
				$campaign_tag_data = $this->campaign_tags_model->get_tag_data_for_campaigns($campaign_ids);
				if(!empty($campaign_tag_data))
				{
					foreach($campaign_tag_data as $v)
					{
						if(!isset($return_array['campaigns'][$v['campaign_id']]))
						{
							$return_array['campaigns'][$v['campaign_id']] = array(
								'campaign_id' => $v['campaign_id'],
								'campaign_name' => $v['campaign_name'],
								'advertiser_name' => $v['advertiser_name'],
								'sales_person' => $v['sales_person'],
								'partner_name' => $v['partner_name'],
								'campaign_tags' => array()
								);
						}
						if($v['tag_id'] !== null and $v['tag_name'] !== null)
						{
							$return_array['campaigns'][$v['campaign_id']]['campaign_tags'][] = array('id' => $v['tag_id'], 'text' => $v['tag_name']); 
						}
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 791204: unable to get tag data for selected campaigns";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 791102: no campaigns found for tag data function";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 791100: user logged out or not permitted";
		}
		echo json_encode($return_array);
	}
	public function ajax_get_campaign_tags()
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
				$result = $this->campaign_tags_model->get_campaign_tags_by_search_term($search_term, $mysql_page_number, ($page_limit + 1));
				if(!empty($result))
				{
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
						$dropdown_list['result'][] = array("id"=>$result[$i]['id'],	"text"=>$result[$i]['name']);
					}
				}
				if($raw_term != '%' && !$this->campaign_tags_model->does_campaign_tag_with_text_exist($raw_term))
				{
					array_unshift($dropdown_list['result'], array("id"=> "ct_placeholder_id", "text"=> $raw_term." (new tag)", "ct_original_text"=> $raw_term));
				}
			}
		}
		echo json_encode($dropdown_list);
	}
	
	public function ajax_create_new_campaign_tag()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$result = array('is_success' => true, 'errors' => "", 'tag_id' => false);
		if($verify_ajax_response['is_success'] === true)
		{
			$tag_name = $this->input->post('tag_name');
			$selected_campaigns = $this->input->post('selected_campaigns');
			if($selected_campaigns === "")
			{
				$selected_campaigns = array();
			}
			if($tag_name !== false and is_array($selected_campaigns))
			{
				$user_id = $this->tank_auth->get_user_id();
				$new_tag_id = $this->campaign_tags_model->insert_new_campaign_tag($tag_name, $user_id);
				if($new_tag_id === false)
				{
					$result['is_success'] = false;
					$result['errors'] = "Error 701391: failed to insert new campaign tag";
				}
				else
				{
					$result['tag_id'] = $new_tag_id;
					if(count($selected_campaigns) > 0)
					{
						$join_insert_success = $this->campaign_tags_model->insert_new_campaign_tag_join_campaign($new_tag_id, $selected_campaigns);
						if($join_insert_success === false)
						{
							$result['is_success'] = false;
							$result['errors'] = "Error 701389: tag created but failed to create tag to campaign relationship";
						}
					}
				}
			}
			else
			{
				$result['is_success'] = false;
				$result['errors'] = "Error 791393: invalid parameters to create new tag function";
			}
		}
		else
		{
			$result['is_success'] = false;
			$result['errors'] = "Error 791395: user logged out or not permitted";
		}
		echo json_encode($result);
	}
	
	public function ajax_add_campaign_tag_to_campaign()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$result = array('is_success' => true, 'errors' => "");
		if($verify_ajax_response['is_success'] === true)
		{
			$tag_id = $this->input->post('tag_id');
			$selected_campaigns = $this->input->post('selected_campaigns');
			if($tag_id !== false and is_array($selected_campaigns))
			{
				$join_insert_success = $this->campaign_tags_model->insert_new_campaign_tag_join_campaign($tag_id, $selected_campaigns);
				if($join_insert_success === false)
				{
					$result['is_success'] = false;
					$result['errors'] = "Error 701589: failed to create tag to campaign relationship";
				}
			}
			else
			{
				$result['is_success'] = false;
				$result['errors'] = "Error 791593: invalid campaign tag parameters";
			}
		}
		else
		{
			$result['is_success'] = false;
			$result['errors'] = "Error 791595: user logged out or not permitted";
		}
		echo json_encode($result);
	}
	
	public function ajax_remove_campaign_tag_from_campaign()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$result = array('is_success' => true, 'errors' => "");
		if($verify_ajax_response['is_success'] === true)
		{
			$tag_id = $this->input->post('tag_id');
			$selected_campaigns = $this->input->post('selected_campaigns');
			if($tag_id !== false and is_array($selected_campaigns))
			{
				$join_delete_success = $this->campaign_tags_model->delete_from_campaign_tag_join_campaign($tag_id, $selected_campaigns);
				if($join_delete_success === false)
				{
					$result['is_success'] = false;
					$result['errors'] = "Error 701689: failed to delete tag to campaign relationship";
				}
			}
			else
			{
				$result['is_success'] = false;
				$result['errors'] = "Error 791693: invalid campaign tag parameters";
			}
		}
		else
		{
			$result['is_success'] = false;
			$result['errors'] = "Error 791695: user logged out or not permitted";
		}
		echo json_encode($result);
	}

	public function ajax_get_common_tags_by_campaign_ids()
	{
		$allowed_roles = array('admin', 'ops');
		$verify_ajax_response = vl_verify_ajax_call($allowed_roles);
		$result = array('is_success' => true, 'errors' => "", 'tags' => array());
		if($verify_ajax_response['is_success'] === true)
		{
			$selected_campaigns = $this->input->post('campaign_ids');
			if(is_array($selected_campaigns))
			{
				$response = $this->campaign_tags_model->get_common_tags_by_campaign_ids($selected_campaigns);
				if($response !== false)
				{
					$result['tags'] = $response;
				}
				else
				{
					$result['is_success'] = false;
					$result['errors'] = "Error 771089: failed to get tags for bulk campaign edit";
				}
			}
			else
			{
				$result['is_success'] = false;
				$result['errors'] = "Error 771093: invalid campaign tag parameters";
			}
		}
		else
		{
			$result['is_success'] = false;
			$result['errors'] = "Error 771095: user logged out or not permitted";
		}
		echo json_encode($result);
	}


	
}

?>