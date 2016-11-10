<?php

class Media_targeting_tags extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('media_targeting_tags_model');
		$this->load->library('table');
		$this->load->library('tank_auth');
		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');	
	}

	private function verify_ajax_call()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$response = array('is_success' => true, 'errors' => array());
			if($this->tank_auth->is_logged_in())
			{
				$username = $this->tank_auth->get_username();
				$role = $this->tank_auth->get_role($username);
				if(!($role == 'ops' OR $role == 'admin'))
				{
					$response['is_success'] = false;
					$response['errors'][] = 'Error 1104394: Incorrect role "'.$role.'" for media_targeting_tags';
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = "Error 1104421: User not logged in";
			}
			return $response;
		}
		else
		{
			show_404();
		}
	}

	public function edit_tags()
	{
		$data['result'] = array();
		$data['option_string'] = "";
		if (!$this->tank_auth->is_logged_in()) 
		{
			$this->session->set_userdata('referer', 'media_targeting_tags/edit_tags');
			redirect('login');
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);

		if(!($role == 'ops' or $role == 'admin'))
		{
			redirect('director');
		}
		$result = $this->media_targeting_tags_model->get_media_targeting_sites_by_term("%", 0, PHP_INT_MAX); //some large number for the limit (all rows)
		if($result)
		{
			$data['result'] = $result;
			foreach($result as $site)
			{
				$data['option_string'] .= '<option value="'.$site['id'].'">'.$site['url'].'</option>';
			}
		}
		$this->load->view('media_targeting_tags/edit_media_targeting_tags_view',$data);
	}

	public function get_media_targeting_tags_for_site()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$id = $this->input->post('id');
			if($id)
			{
				$site_result = $this->media_targeting_tags_model->get_site_url($id);
				$media_targeting_tags_result = $this->media_targeting_tags_model->get_media_targeting_tags($id);
				if($site_result)
				{
					$response['data'] = array('url' => $site_result[0]['url'], 'media_targeting_tags' => $media_targeting_tags_result);
				}
				else
				{
					$response['is_success'] = false;
					$response['errors'][] = 'Error 2294771: could not retrieve site data for site id: '.$id;
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = 'Error 2294029: no site id provided for server request';
			}
		}
		echo json_encode($response);
	}

	public function ajax_sites() //select2
	{
		$response = $this->verify_ajax_call();
		$dropdown_list = array('result' => array(), 'more' => false);
		if($response['is_success'])
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
				$result = $this->media_targeting_tags_model->get_media_targeting_sites_by_term($search_term, $mysql_page_number, ($page_limit +1));
				if($result)
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
						$dropdown_list['result'][] = array("id" => $result[$i]['id'], "text" => $result[$i]['url']);
					}
				}
			}
		}
		echo json_encode($dropdown_list);
	}

	public function ajax_media_targeting_tags()
	{
		$response = $this->verify_ajax_call();
		$dropdown_list = array('result' => array(), 'more' => false);
		if($response['is_success'])
		{
			$raw_term = $this->input->post('q');
			$page_limit = $this->input->post('page_limit');
			$page_number = $this->input->post('page');
			if($raw_term && is_numeric($page_limit) && is_numeric($page_number))
			{
				if($raw_term != '%')
				{
					$search_term = '%'.str_replace(" ", "%", $raw_term).'%';
				}
				else
				{
					$search_term = $raw_term;
				}
				$mysql_page_number = ($page_number - 1) * $page_limit;
				$result = $this->media_targeting_tags_model->get_media_targeting_tags_by_term($search_term, $mysql_page_number, ($page_limit + 1));
				if($result)
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
						$dropdown_list['result'][] = array("id"=>$result[$i]['id'],	"text"=>$result[$i]['tag_copy'], "tag_type" => $result[$i]['tag_type']);
					}
				}
			}
		}
		echo json_encode($dropdown_list);
	}

	public function save_tags_to_site()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$site = $this->input->post('site');
			$tag_data = $this->input->post('tag_data');
			if(!empty($site))
			{
				if(!empty($tag_data))
				{
					$tags_array = json_decode($tag_data);
				}
				else
				{
					$tags_array = false;
				}
				$this->media_targeting_tags_model->save_tags_to_site($tags_array, $site, $response);
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = 'Error 2023439: No tags or site found, unable to save tags to site.';
			}
		}
		echo json_encode($response);
	}

	public function get_site_packs_select()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$sitepacks = $this->media_targeting_tags_model->get_all_sitepacks();
			if($sitepacks)
			{
				$response['data'] = $sitepacks;
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = 'Error 5911044: no sitepacks found';
			}
		}
		echo json_encode($response);
	}

	public function get_sites_from_sitepack()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$response['data'] = array();
			$response['data']['is_warning'] = false;
			$sitepack_id = $this->input->post('pack_id');
			if($sitepack_id)
			{
				$row_array = $this->media_targeting_tags_model->get_json_sites_from_sitepack($sitepack_id);
				if($row_array)
				{
					$raw_sites = json_decode($row_array['site_array']); //this is the list as saved
					$sites_to_push  = array();
					$sites_failed = array();
					foreach($raw_sites as $row)
					{
						if($row[0] != "header_tag")
						{
							//now do a lookup for the site id in the media planning sites
							$this_site_id = $this->media_targeting_tags_model->get_site_id_from_url($row[0]);
							if($this_site_id)
							{
								$sites_to_push[] = $this_site_id[0]['id']."||".$row[0];
							}
							else
							{
								$is_sitepack_site = true;
								$inserted_response = $this->media_targeting_tags_model->save_site($row, $is_sitepack_site);
								if($inserted_response)
								{
									$sites_to_push[] = $inserted_response['site_id']."||".$inserted_response['site_url'];
								}
								else
								{
									$sites_failed[] = $row[0];
								}
							}
						}
					}
					$response['data']['sites_failed'] = $sites_failed;
					$response['data']['sites_to_push'] = $sites_to_push;
				}
				else
				{
					$response['data']['is_warning'] = true;
					$response['data']['warning'] = "No sites found for sitepack: ".$sitepack_id;
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'] .= "Error 0682294: no sitepack id found"."\n";
			}
		}
		echo json_encode($response);
	}

	public function add_tags_to_site()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$tags = $this->input->post('tags');
			$site = $this->input->post('site');
			if(!empty($tags) AND !empty($site))
			{
				$media_targeting_tags = json_decode($tags);
				$this->media_targeting_tags_model->add_tags_to_site($media_targeting_tags, $site, $response);
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = 'Error 8309661: no values received for tags and site';
			}
		}
		echo json_encode($response);
	}

	public function save_site()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$site_row = $this->input->post('demo_array');
			if($site_row)
			{
				$save_result = $this->media_targeting_tags_model->save_site($site_row);
				if($save_result)
				{
					$response['data'] = $save_result;
				}
				else
				{
				    $response['is_warning'] = true;
					$response['warnings'] = 'Warning 0049793: no update required for site: '.$site_row[0];
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = "Error 0830293: no site found";
			}
		}
		echo json_encode($response);
	}

	public function get_sites_by_tags()
	{
		$response = $this->verify_ajax_call();
		if($response['is_success'])
		{
			$response['is_warning'] = false;
			$response['warning'] = "";
			$tags = $this->input->post('tags');
			if(!empty($tags))
			{
				$media_targeting_tags = json_decode($tags);
				$result = $this->media_targeting_tags_model->get_sites_by_media_targeting_tags($media_targeting_tags);
				if($result)
				{
					$response['data'] = $result;
				}
				else
				{
					$response['is_warning'] = true;
					$response['warning'] = "no sites found for tag selection";
				}
			}
			else
			{
				$response['is_warning'] = true;
				$response['warning'] = "no tags selected";
			}
		}
		echo json_encode($response);
	}
}
?>