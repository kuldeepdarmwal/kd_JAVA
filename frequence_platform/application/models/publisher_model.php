<?php
class Publisher_model extends CI_Model {

	public function __construct(){
		$this->load->database();  
		$this->load->model('dfa_model');
		$this->load->model('cup_model');
		$this->load->library('google_api');
	}

	public function get_all_campaigns()
	{
		$query_string = "
			SELECT 
				a.Name as advertiser, 
				c.Name as campaign, 
				c.id as c_id, 
				concat(a.Name,' : ',c.Name) as full_campaign, 
				a.id as adv_id,
				c.ignore_for_healthcheck as ignore_for_healthcheck
			FROM 
				Advertisers a
				LEFT JOIN Campaigns c ON a.id = c.business_id
			WHERE 1
			ORDER BY c_id DESC
		";
		return $this->db->query($query_string)->result_array();
	}

	public function get_all_active_campaigns()
	{
		$query_string = "SELECT a.Name as advertiser, c.Name as campaign, c.id as c_id, concat(a.Name,' : ',c.Name) as full_campaign, a.id as adv_id
							FROM Advertisers a
							LEFT JOIN Campaigns c
							ON a.id = c.business_id
							WHERE c.ignore_for_healthcheck = 0
							ORDER BY c_id DESC";
		return $this->db->query($query_string)->result_array();
	}

	public function get_all_active_campaigns_select2($q, $start, $limit)
	{
		$query_string = "SELECT a.Name as advertiser, c.Name as campaign, c.id as id, concat(a.Name,' : ',c.Name) as text, a.id as adv_id
							FROM Advertisers a
							LEFT JOIN Campaigns c
							ON a.id = c.business_id
							WHERE c.ignore_for_healthcheck = 0
							AND a.Name LIKE ? 
							OR c.Name LIKE ?
							ORDER BY id DESC
							LIMIT ?, ?";
		return $this->db->query($query_string, array($q, $q, $start, $limit))->result();
	}
	
	public function get_ad_choices_tag_by_partner_id($partner_id)
	{
		$select_array = array("id"=>$partner_id);
		$query = "SELECT ad_choices_tag FROM wl_partner_details WHERE ad_choices_tag IS NOT NULL AND id = ?";
		$result = $this->db->query($query,$select_array);
		if($result->num_rows() > 0)
		{
		return $result->row()->ad_choices_tag;
		}
		return "";
	}

	public function construct_ad_choices_element(
		$creative_size,
		$ad_choices_tag_source_string
	)
	{
		// TODO: get new spans from Ad Choices for new sizes (336x280) and (320x50)
		// For now, use 1 as the default (from talk with Matt, Scott, and Jason)

		$ad_choices_element = '';

		$span_integer = 1;

		switch($creative_size)
		{
			case '160x600': //('width'=>160,'height'=>600)
				$span_integer = 3;
				break;
			case '728x90': //('width'=>728,'height'=>90)
				$span_integer = 2;
				break;
			case '300x250': //('width'=>300,'height'=>250)
				$span_integer = 1;
				break;
			case '336x280': //('width'=>336,'height'=>280)
				$span_integer = 1;
				break;
			case '320x50': //('width'=>320,'height'=>50)
				// TODO: how do we actually want to handle this? -scott
				$span_integer = 1;
				break;
			default:
				$span_integer = 1;
				// TODO: better error handling -scott
		}

		$dimensions = explode('x',$creative_size);									 
		$width = $dimensions[0];
		$height = $dimensions[1];

		// $ad_choices_tag_source_string looks something like: 
		//		<span id="te-clearads-js-vantagecont||"><script type="text/javascript" src="https://choices.truste.com/ca?pid=tradedesk01&aid=vantagelocal01&cid=0612van&c=vantagecont

		$chunks = explode("||", $ad_choices_tag_source_string);
		if(count($chunks) == 2 && $ad_choices_tag_source_string != "")
		{
			$ad_choices_element = $chunks[0].$span_integer.$chunks[1].$span_integer.'&w='.$width.'&h='.$height.'"></script></span>';
		}

		return $ad_choices_element;
	}
	
	public function push_dfa_creatives($creative, $creative_size, $dfa_advertiser_id, $dfa_campaign_id, $version_id, $is_bulk = FALSE, $landing_page = NULL)
	{
		$this->google_api->dfa_initalize();
		$placement_site_id = $this->config->item('dfa_site');
		$placement_type_id = 3;//3=>agency paid regular https://developers.google.com/doubleclick-advertisers/docs/creative_types
		$price_type_id = 1; //1 is CPM

		$return_array = array("err_msg"=>"", "is_success"=>FALSE);
		$identifier_variable = "engdataids";

		$load_backup_asset_result = $this->google_api->dfa_create_asset($dfa_advertiser_id, $creative['backup_image'], 'IMAGE', false);
		if($load_backup_asset_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $load_backup_asset_result['err_msg'];
			return $return_array;
		}

		$campaign_backup_creative_result = $this->google_api->dfa_upload_image_creative("backup_".$creative_size, $dfa_advertiser_id, $dfa_campaign_id, $creative['backup_image'], $creative_size, $load_backup_asset_result['dfa_result']);
		if($campaign_backup_creative_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $campaign_backup_creative_result['err_msg'];
			return $return_array;
		}
		$load_creative_asset_result = $this->google_api->dfa_create_asset($dfa_advertiser_id, $creative['backup_image'], 'HTML_IMAGE', true);
		if($load_creative_asset_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $load_creative_asset_result['err_msg'];
			return $return_array;
		}


		$vl_creative_record = $this->cup_model->get_creative_details($creative_size, $version_id);
		$creative['vl_creative_id'] = $vl_creative_record[0]['id'];
		$creative['tracking_off'] = false;
		if(isset($creative['variables_js']) and $creative['variables_js'] != '')
		{
			$creative['variables_js'] = json_encode(file_get_contents($creative['variables_js']));
		}
		///get html
		$html = $this->cup_model->get_ad_html( $version_id, $creative_size,true);

		//Replace creative id with placeholder
		if($is_bulk)
		{
			$html = str_replace("VL_".$creative['vl_creative_id'], 'VL_" + "%k'.$identifier_variable.'=!;', $html);
			$html = str_replace("%u", "%kcturl=!`", $html);
		}

		///load dfa in page creative
		$dfa_inpage_creative_result = $this->google_api->dfa_upload_inpage_creative("AL4k_".$creative_size, $dfa_advertiser_id, $dfa_campaign_id, $creative_size, $load_creative_asset_result['dfa_result'], $html);
		if($dfa_inpage_creative_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $dfa_inpage_creative_result['err_msg'];
			return $return_array;
		}

		//create dfa placement			
		$new_placement_result = $this->google_api->dfa_create_placement("Placement ".$creative_size, $dfa_campaign_id, $placement_site_id, $creative_size);
		if($new_placement_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $new_placement_result['err_msg'];
			return $return_array;
		}

		//Enable image creative's ad 
		$enable_image_creative_result = $this->google_api->dfa_enable_ad_for_creative_for_placement($campaign_backup_creative_result['dfa_result'], $new_placement_result['dfa_result']);
		if($enable_image_creative_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $enable_image_creative_result['err_msg'];
			return $return_array;
		}

		//link creative to placement = ad
		$ad_result = $this->google_api->dfa_link_creative_to_placement_and_campaign($dfa_inpage_creative_result['dfa_result'], $new_placement_result['dfa_result'], $dfa_campaign_id);
		if($ad_result['is_success'] == FALSE)
		{
			$return_array['err_msg'] = $ad_result['err_msg'];
			return $return_array;
		}
		
		$dfa_ad_tag = $this->dfa_model->secure_dfa_tag($this->google_api->dfa_get_tags($new_placement_result['dfa_result']->id, $dfa_campaign_id));
		
		//Add keyword to tag url if we're dealing with a bulk uploader/refresher creative
		if($is_bulk)
		{
			$dfa_ad_tag = str_replace("ord=[timestamp]", $identifier_variable."=ttd-%%TTD_CREATIVEID%%-%%TTD_CAMPAIGNID%%;cturl=".$landing_page."`;kw=%%TTD_CREATIVEID%%-%%TTD_CAMPAIGNID%%;ord=[timestamp]", $dfa_ad_tag);
		}
		
		$updated_success = $this->cup_model->update_creative(array(
					'ad_tag'=>$dfa_ad_tag['dfa_result'],
					'dfa_advertiser_id'=>$dfa_advertiser_id,
					'dfa_campaign_id'=>$dfa_campaign_id,
					'dfa_placement_id'=>$new_placement_result['dfa_result']->id,
					'dfa_creative_id'=>$dfa_inpage_creative_result['dfa_result']->id,
					'dfa_ad_id'=>$ad_result['dfa_result']->id,
					'published_ad_server'=>k_ad_server_type::dfa_id,	
					'size'=>$creative_size,
					'adset_id'=>$version_id
					));
		if(!$updated_success)
		{
			$return_array['err_msg'] = "Failed to update VL creative table";
			return $return_array;
		}
		
		$return_array['is_success'] = TRUE;
		return $return_array;
	}
}
