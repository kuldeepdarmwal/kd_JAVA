<?php

class Gallery extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->helper('url');
		$this->load->model('gallery_model');
	}
	

	public function index($limit=300)
	{
		$data['title'] = 'Creative Gallery';
		$active_feature_button_id = 'gallery';
		$partner_details = $this->tank_auth->get_partner_info_by_sub_domain();
		if($partner_details)
		{
			$partner_id = $partner_details['id'];
		}
		else
		{
			show_404();
		}

		$data['gallery_features'] = $this->gallery_model->get_gallery_features($partner_id,$limit);
		$tiles = $this->gallery_model->get_gallery_tiles($partner_id, $limit);
		if($tiles)
		{
			foreach($tiles as $key=>$tile)
			{
				$tiles_array[$key] = $tile;
				$tiles_array[$key]['ref'] = "/crtv/get_gallery_adset/".base64_encode(base64_encode(base64_encode($tile['v_id']))); //this makes it easy to write a chunk of markup at once in the view
			}
			$data['tiles_blob'] = json_encode($tiles_array);//this is for the ajax load
		}
		else
		{
			$data['tiles_blob']=null;
		}
		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'gallery_wow/gallery_body_view',
			'gallery_wow/gallery_header_view',
			false,
			'gallery_wow/gallery_js_view',
			false
		);
	}

	public function broadcaster()
	{	
		//echo "broadcaster";
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','gallery/broadcaster');
			redirect('login');
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' or $role == 'admin' or $role == 'creative')
		{
			$all_partners_array = $this->gallery_model->get_all_partners_and_ids();
			$all_partners_select = array();
			$all_partners_select[] = array(
				'id'=>'0',
				'text'=>'ALL'
			);
			foreach($all_partners_array as $partner)
			{
				$all_partners_select[] = array(
					'id'=> $partner['id'],
					'text'=>$partner['partner_name']
				);
			}
			$data['all_partners_select'] = json_encode($all_partners_select);
			$all_features_array = $this->gallery_model->get_all_gallery_features_and_ids();
			$all_features_select = array();
			foreach($all_features_array as $feature)
			{
				$all_features_select[] = array(
					'id'=> $feature['id'],
					'text'=>$feature['friendly_name']
				);
			}
			$data['all_features_select'] = json_encode($all_features_select);

			$data['title'] = 'Adset Broadcaster';
			$active_feature_button_id = 'gallery';
			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'gallery_wow/broadcaster_body_view',
				'gallery_wow/broadcaster_header_view',
				false,
				'gallery_wow/broadcaster_js_view',
				false
			);
		}
		else
		{ 
			redirect('director'); 
		}
	}

	private function format_adset_name($adset_version)
	{
		$friendly_name = '';
		if($adset_version['advertiser_name'] != NULL)
		{
			$friendly_name .= $adset_version['advertiser_name'].' || ';
		}
		if($adset_version['campaign_name'] != NULL)
		{
			$friendly_name .= $adset_version['campaign_name'].' || ';
		}
		if($adset_version['adset_name'] != NULL)
		{
			$friendly_name .= $adset_version['adset_name'].' || v'.$adset_version['v_num'];
		}

		return $friendly_name ;
		
	}

	private function format_friendly_adset_name($adset_version)
	{
		$friendly_name = '';
		if($adset_version['advertiser_name'] != NULL)
		{
			$friendly_name .= $adset_version['advertiser_name'].' || ';
		}
		if($adset_version['adset_name'] != NULL)
		{
			$friendly_name .= $adset_version['adset_name'];
		}
		return $friendly_name ;
		
	}

	public function adset_feed()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$adsets_array = array('result' => array(), 'more' => false);
			$raw_term = $this->input->post('q');
			$page_limit = $this->input->post('page_limit');
			$page_number = $this->input->post('page');
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
				$adsets_result = $this->gallery_model->get_adsets_by_search_term($search_term, $mysql_page_number, ($page_limit + 1));
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
							'id' => $adsets_result[$i]['adset_v_id'], 
							'text' => $this->format_adset_name($adsets_result[$i]),
							'thumb'=>$adsets_result[$i]['thumb_url'],
							'friendly_name'=>$this->format_friendly_adset_name($adsets_result[$i]),
							'partners'=>$adsets_result[$i]['partners'],
							'features'=>$adsets_result[$i]['features'],
							'saved_adset_name'=>$adsets_result[$i]['saved_adset_name'],
							'preview_link'=>'/crtv/get_adset/'.base64_encode(base64_encode(base64_encode(intval($adsets_result[$i]['adset_v_id'])))));
					}
				}
			}
			echo json_encode($adsets_array);
		}
		else
		{
			show_404();
		}
	}

	public function save_adset_to_gallery()
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$adset_details = array("adset_version_id"=>$this->input->post("as_v_id"),
									"gallery_adset_name"=>$this->input->post("as_name"),
									"partners"=>explode(",",$this->input->post("prtnrs")),
									"features"=>explode(",",$this->input->post("features"))
									);
			//echo json_encode($adset_details);
			$save_adset_to_gallery_result = $this->gallery_model->save_adset_to_gallery($adset_details);
			echo json_encode($save_adset_to_gallery_result);
		}
		else
		{
			show_404();
		}
	}
	

}

?>
