<?php

class Bulk_campaigns_operations_model extends CI_Model 
{

	public function __construct()
	{
		$this->load->database();
		$this->load->helper('url');
		$this->load->model('al_model');
		$this->load->model('cup_model');
		$this->load->model('tag_model');
		$this->load->model('dfa_model');
		$this->load->model('publisher_model');
		$this->load->model('tradedesk_model');
		$this->load->library('google_api');
	}


	public function does_advertiser_exist($id)
	{
		$sql = 'SELECT * FROM Advertisers WHERE id = ?';
		$bindings = array($id);
		
		$query = $this->db->query($sql, $bindings);
		$result = $query->result_array();
		if($query->num_rows() > 0 && $result[0]['ttd_adv_id'] != NULL)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function is_campaign_name_unique_for_advertiser($adv_id,$campaign_name)
	{
		$sql = 'SELECT COUNT(Name) as count FROM Campaigns WHERE business_id = ? and Name = ?';
		$bindings = array($adv_id,$campaign_name);

		$query = $this->db->query($sql, $bindings);
		$result = $query->result_array();
		if($result[0]['count'] > 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function get_db_current_time()
	{
		$sql='SELECT NOW() as time_now';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}
	
	public function clone_adset_for_campaign($adset_id, $campaign_id)
	{
	    $sql = "INSERT INTO cup_adsets (name, adgroup_id, campaign_id)
		    SELECT concat(name, ' - BULK', ?),
			   adgroup_id,
			   ?
		    FROM cup_adsets
		    WHERE id = ?";
	    $query = $this->db->query($sql, array($campaign_id, $campaign_id,$adset_id));
	    if($this->db->affected_rows() > 0)
	    {
		$insert_id = $this->db->insert_id();
		return $insert_id;
	    }
	    else
	    {
		return false;
	    }
	}
	
	public function clone_version_to_adset($version_id, $adset_id, $source_id, $campaign_id)
	{
		$adset_sql = "
			SELECT
				id,
				name
			FROM
				cup_adsets
			WHERE
				id = ?";
		$adset_response = $this->db->query($adset_sql, $adset_id);
		if($adset_response->num_rows() > 0)
		{
			$adset_data = $adset_response->row_array();
			$vanity_string = url_title($adset_data['name']).'v1';
		}
		else
		{
			return false;
		}
		
	    $sql = "
			INSERT INTO 
				cup_versions 
				(
					adset_id, 
					variables, 
					fullscreen, 
					version, 
					variables_js, 
					variables_xml, 
					variables_data, 
					builder_version, 
					source_id, 
					base_64_encoded_id,
					vanity_string,
					campaign_id
				)
			SELECT ?, 
				variables,
				fullscreen,
				1 AS version,
				variables_js,
				variables_xml,
				variables_data,
				builder_version,
				?,
				base_64_encoded_id,
				?,
				?
		    FROM cup_versions
		    WHERE id = ?";
	    $query = $this->db->query($sql, array($adset_id, $source_id, $vanity_string, $campaign_id, $version_id));
	    if($this->db->affected_rows() > 0)
	    {
	    	$insert_id = $this->db->insert_id();
		    return $insert_id;
	    }
	    else
	    {
		    return false;
	    }
	}
	
	public function clone_creatives_and_assets_from_version_into_version($from_v_id, $to_v_id, $landing_page, $initial_clone = false)
	{
	    if(!$initial_clone)
	    {
		$sql = "SELECT 
			    id,
			    size,
			    ad_tag,
			    dfa_advertiser_id,
			    dfa_campaign_id,
			    dfa_placement_id,
			    dfa_creative_id,
			    dfa_ad_id,
			    ttd_creative_id,
			    adtech_ad_tag,
			    adtech_flight_id,
			    adtech_campaign_id,
			    published_ad_server,
			    dfa_used_adchoices,
			    adtech_used_adchoices,
			    ttd_creative_landing_page
			FROM
			    cup_creatives
			WHERE
			    version_id = ?
			";
	    }
	    else
	    {
		$sql = "SELECT 
			    cre.id AS id,
			    cre.size AS size,
			    cre.version_id AS version_id,
			    COUNT(aas.id) AS adset_count
			FROM
			    cup_creatives AS cre
			    LEFT JOIN cup_ad_assets AS aas
				ON (cre.id = aas.creative_id)
			WHERE
			    cre.version_id = ?
			GROUP BY cre.id";
	    }
	    $query = $this->db->query($sql, $from_v_id);
	    if($query->num_rows() < 1)
	    {
		return false;
	    }
	    $creatives = $query->result_array();
	    foreach($creatives as $creative)
	    {
		//Insert new creative row
		
		if(!$initial_clone)
		{
		    $insert_array = array($creative['size'],
					  $creative['dfa_advertiser_id'],
					  $creative['dfa_campaign_id'],
					  $creative['dfa_placement_id'],
					  $creative['dfa_creative_id'],
					  $creative['dfa_ad_id'],
					  $creative['ttd_creative_id'],
					  $creative['adtech_ad_tag'],
					  $creative['adtech_flight_id'],
					  $creative['adtech_campaign_id'],
					  $creative['published_ad_server'],
					  $creative['dfa_used_adchoices'],
					  $creative['adtech_used_adchoices'],
					  $to_v_id,
					  $creative['ttd_creative_landing_page']);
		    
		    $sql = "INSERT INTO cup_creatives (
			    size,
			    dfa_advertiser_id,
			    dfa_campaign_id,
			    dfa_placement_id,
			    dfa_creative_id,
			    dfa_ad_id,
			    ttd_creative_id,
			    adtech_ad_tag,
			    adtech_flight_id,
			    adtech_campaign_id,
			    published_ad_server,
			    dfa_used_adchoices,
			    adtech_used_adchoices,
			    version_id,
			    ttd_creative_landing_page)
			    
			    VALUES
			    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		}
		else
		{
		    if($creative['adset_count'] > 0)
		    {
			$insert_array = array($creative['size'], $to_v_id, $landing_page);
			$sql = "INSERT INTO cup_creatives (size, version_id, ttd_creative_landing_page) VALUES (?, ?, ?)";
		    }
		    else
		    {
			$insert_array = array($creative['size'], $to_v_id);
			$sql = "INSERT INTO cup_creatives (size, version_id) VALUES (?, ?)";
		    }
		}
		$query = $this->db->query($sql, $insert_array);
		
		$new_creative_id = $this->db->insert_id();
		
		if(!$initial_clone)
		{
		    if($creative['ad_tag'] != NULL)
		    {
			$creative['ad_tag'] = preg_replace('/cturl=(.*(?=`))/','cturl='.$landing_page, $creative['ad_tag']);
			$sql = "UPDATE cup_creatives SET ad_tag = ? WHERE id = ?";
			$query = $this->db->query($sql, array($creative['ad_tag'], $new_creative_id));
			if($this->db->affected_rows() < 1)
			{
			    return FALSE;
			}
		    }
		}
		

		
		//Clone ad assets and reassign them to the new creative
		$sql = "INSERT INTO cup_ad_assets (type, open_uri, ssl_uri, creative_id, is_archived, weight, extension)
			SELECT type, open_uri, ssl_uri, ?, is_archived, weight, extension
			FROM cup_ad_assets
			WHERE creative_id = ?";
		$query = $this->db->query($sql, array($new_creative_id, $creative['id']));
		
		/* TODO: Scott (Error handling for no ad assets for a creative that's being cloned)
		if($this->db->affected_rows() < 1)
		{
			return FALSE;
		}
		 * 
		 */
	    }
	    return TRUE;
	}
	public function get_version_with_source_version_id_and_advertiser_id_and_landing_page($source_version_id, $advertiser_id, $landing_page)
	{
	    $sql = "SELECT ver.id AS id,
			   ver.source_id AS source_id
		    FROM 
			cup_creatives AS cre
			JOIN cup_versions AS ver 
			    ON (cre.version_id = ver.id)
			JOIN cup_versions_join_campaigns AS cvjc 
			    ON (ver.id = cvjc.version_id) 
			JOIN Campaigns AS c 
			    ON (cvjc.campaign_id = c.id)
			JOIN Advertisers AS adv 
			    ON (c.business_id = adv.id)
		    WHERE
			ver.source_id = ? AND adv.id = ? AND cre.ttd_creative_landing_page = ? ORDER BY ver.id ASC";
	    $query = $this->db->query($sql, array($source_version_id, $advertiser_id, $landing_page));
	    if($query->num_rows > 0)
	    {
		return $query->row_array();
	    }
	    return FALSE;    
	}
	
	public function set_source_id_for_version_with_version($new_version_id, $version_id)
	{
	    $sql = "UPDATE cup_versions SET source_id = ? WHERE id = ?";
	    $query = $this->db->query($sql, array($version_id, $new_version_id));
	    if($this->db->affected_rows() > 0)
	    {
		return TRUE;
	    }
	    return FALSE;
	}
	public function clone_adset_version_list_for_campaign($version_id_list, $landing_page_list, $vl_campaign_id, $vl_advertiser_id, $operation_type, $force_duplication = false, $dfa_campaign_names = null)
	{
		$is_success = false;
		$new_versions = array();
		
		$versions_array = explode(";", $version_id_list);
		$landing_page_array = explode(":?:", $landing_page_list);
		$dfa_campaign_names_array = explode(";", $dfa_campaign_names);
		
		foreach($versions_array as $index => $version)
		{
			$clone_adset_result = $this->clone_adset_with_version_id_to_campaign($version, $landing_page_array[$index], $vl_campaign_id, $vl_advertiser_id, $operation_type, $force_duplication);
			if(!$clone_adset_result['is_success'])
			{
					goto vl_adsets_exit;
			}
			if($dfa_campaign_names === NULL || $dfa_campaign_names === '' )
			{
				array_push($new_versions, array("v_id"=>$clone_adset_result['version_id'],"needs_dfa_push"=>$clone_adset_result['needs_push']));
			}
			else
			{
				array_push($new_versions, array("v_id"=>$clone_adset_result['version_id'],"needs_dfa_push"=>$clone_adset_result['needs_push'],"dfa_campaign_name"=>$dfa_campaign_names_array[$index]));
			}
		}
		$is_success = true;
		vl_adsets_exit:
		return array('is_success'=>$is_success,'version_ids'=>$new_versions);
	}

	private function clone_adset_with_version_id_to_campaign($version_id, $landing_page, $campaign_id, $advertiser_id, $operation_type, $force_duplication)
	{
		$is_success = false;
		$new_version_id = "";
		//Get old adset id to clone
		$old_adset_id = $this->cup_model->get_adset_by_version_id($version_id);
		if($old_adset_id == false)
		{
			goto vl_adset_clone_exit;
		}
		
		//clone adset
		switch($operation_type)
		{
		    case "upload":
			$new_adset_id = $this->clone_adset_for_campaign($old_adset_id, $campaign_id);
			break;
		    case "refresh":
			$new_adset_id = $this->clone_adset_for_refreshing_campaign($old_adset_id, $campaign_id);
			break;
		    default:
			goto vl_adset_clone_exit;
			break;
		}

		if($new_adset_id == false)
		{
			goto vl_adset_clone_exit;
		}
		
		//Try to find previous cloned version id.
		if($force_duplication)
		{
		    $cloned_version = false;
		}
		else
		{
		    $cloned_version = $this->get_version_with_source_version_id_and_advertiser_id_and_landing_page($version_id, $advertiser_id, $landing_page);    
		}
		$needs_push = false;
		if($cloned_version != false)
		{
		    //copy versions from old adset over to new adset
		    $new_version_id = $this->clone_version_to_adset($version_id, $new_adset_id, $version_id, $campaign_id);
		    if($new_version_id == false)
		    {
			goto vl_adset_clone_exit;
		    }
		    
		    //If this adset has been cloned before, then copy the clone. It won't need to be pushed to dfa
		    $creative_clone_success = $this->clone_creatives_and_assets_from_version_into_version($cloned_version['id'], $new_version_id, $landing_page);
		}
		else
		{
		    //copy versions from old adset over to new adset
		    $new_version_id = $this->clone_version_to_adset($version_id, $new_adset_id, $version_id, $campaign_id);
		    if($new_version_id == false)
		    {
			goto vl_adset_clone_exit;
		    }
		    
		    //If the adset hasn't been cloned yet, then copy it, and prepare to push it to dfa
		    $needs_push = true;
		    $creative_clone_success = $this->clone_creatives_and_assets_from_version_into_version($version_id, $new_version_id, $landing_page, true);
		}
		if(!$creative_clone_success)
		{
		    goto vl_adset_clone_exit;
		}
		
		$is_success = true;
		vl_adset_clone_exit:
		return array('is_success'=>$is_success,'version_id'=>$new_version_id,'needs_push'=>$needs_push);
	}

	public function publish_multiple_adsets_to_dfa(
		$version_id_array, 
		$campaign_id, 
		$advertiser_id,
		$landing_pages,
		$dfa_advertiser_id = null,
		$bidder = null
	)
	{
		$is_success = false;
		$err_msg = "";

		if(count($landing_pages) == count($version_id_array))
		{
			foreach($version_id_array as $index => $version_id)
			{
				if($version_id['needs_dfa_push'])
				{
				    if(isset($version_id['dfa_campaign_name']))
				    {
					    $dfa_adv_name = $version_id['dfa_campaign_name'];
				    }
				    else
				    {
					    $dfa_adv_name = '';
				    }
				    $publish_to_dfa_result = $this->publish_adset_to_dfa(
					    $version_id['v_id'], 
					    $campaign_id, 
					    $advertiser_id,
					    $landing_pages[$index],
					    $dfa_advertiser_id,
					    $dfa_adv_name,
					    $bidder
				    );
				}
				else
				{
				    $publish_to_dfa_result['is_success'] = TRUE;
				}
				if(!$publish_to_dfa_result['is_success'])
				{
					$publish_to_dfa_result['is_success'] = FALSE;
					$err_msg = $publish_to_dfa_result['err_msg'];
					goto dfa_adsets_exit;
				}
			}
			$is_success = true;
		}
		
		dfa_adsets_exit:
		return array('is_success'=>$is_success, 'err_msg'=>$err_msg);
	}
	
	private function publish_adset_to_dfa(
		$version_id, 
		$campaign_id, 
		$advertiser_id,
		$landing_page,
		$forced_dfa_advertiser_id = null,
		$dfa_campaign_name = null,
		$bidder = null		
	)
	{
		$is_success = 0;
		$dfa_advertiser_id = NULL;
		$dfa_campaign_id = NULL;
		$is_bulk_flag = TRUE;
		
		$err_msg = "";
		
		$this->google_api->dfa_initalize();
		$placement_site_id = $this->session->userdata('dfa_site_id');
		$placement_type_id = 3;//3=>agency paid regular https://developers.google.com/doubleclick-advertisers/docs/creative_types
		$price_type_id = 1; //1 is CPM
	    
		//Get advertiser name and id
		$advertiser_details = $this->al_model->get_adv_details($advertiser_id);
		if($advertiser_details == NULL)
		{
			goto dfa_push_exit;
		}
		
		if($forced_dfa_advertiser_id == null)
		{
		    $advertiser_name = $advertiser_details[0]['Name'];

		    //DETERMINE IF WE WANT TO MAKE ADVERTISER
		    $advertiser_result = $this->google_api->dfa_get_advertiser_with_name($advertiser_name);
		    if($advertiser_result['is_success'] == FALSE)
		    {
			    goto dfa_push_exit;
		    }
		    $advertiser = $advertiser_result['dfa_result'];

		    $need_new_campaign = false;

		    //If no advertisers with that name
		    if(count($advertiser) < 1)
		    {
				//MAKE ADVERTISER
				$insert_result = $this->google_api->dfa_insert_advertiser($advertiser_name);
				if($insert_result['is_success'] == FALSE)
				{
				    goto dfa_push_exit;
				}
				$dfa_advertiser_id = $insert_result['dfa_result']->id;
				//$need_new_campaign = true;		
		    }
		    else
		    {
				$dfa_advertiser_id = $advertiser[0]->id;
		    }
		}
		else
		{
		    $dfa_advertiser_id = $forced_dfa_advertiser_id;
		}
	    /*	
		if(!$need_new_campaign)
		{		
		    $campaign_result = $this->dfa_model->fetch_campaign_from_advertiser_with_name($this->dfa_model->get_campaign_wsdl(),$options,$headers, $dfa_advertiser_id, $campaign_name);
		    if($campaign_result['is_success'] == FALSE)
		    {
			    $err_msg = $campaign_result['err_msg'];
			    goto dfa_push_exit;
		    }
		    if(count($campaign_result['dfa_result']->records) < 1)
		    {
			    $need_new_campaign = true;
		    }
		    else
		    {
			    $dfa_campaign_id = $campaign_result['dfa_result']->records[0]->id;
		    }
		}
	     * 
	     */
	    
	    //CAMPAIGN
	    //Get landing page
	    
		//if($need_new_campaign)
		//{
	    $campaign_details = $this->al_model->get_campaign_details($campaign_id);
	    if($campaign_details == NULL)
	    {
		    goto dfa_push_exit;
	    }
	    if(($dfa_campaign_name !== '' && $dfa_campaign_name !== NULL) && $bidder === 'ttd')
	    {
		    $campaign_name = $dfa_campaign_name.' - '.$version_id.' [BU]';
	    }
	    else if(($dfa_campaign_name !== '' && $dfa_campaign_name !== NULL) && $bidder === 'dfa')
	    {
		    $campaign_name = $dfa_campaign_name.' - '.$version_id.' [BU DCM]';
		    $is_bulk_flag = FALSE;
	    }
	    else if(($dfa_campaign_name === '' || $dfa_campaign_name === NULL) && $bidder === 'ttd')
	    {
		    $campaign_name = $campaign_details[0]['Name'].' - '.$version_id.' [BU]';
	    }
	    else if(($dfa_campaign_name === '' || $dfa_campaign_name === NULL) && $bidder === 'dfa')
	    {
		    $campaign_name = $campaign_details[0]['Name'].' - '.$version_id.' [BU DCM]';
		    $is_bulk_flag = FALSE;
	    }
	    else if(($dfa_campaign_name !== '' && $dfa_campaign_name !== NULL) && $bidder === '')
	    {
		    $campaign_name = $dfa_campaign_name.' - '.$version_id.' [BU DCM]';
	    }
	    else
	    {
		    $campaign_name = $campaign_details[0]['Name'].' - '.$version_id.' [BU]';
	    }

	    $campaign_insert_result = $this->google_api->dfa_insert_campaign($campaign_name, $landing_page, $dfa_advertiser_id);
	    if($campaign_insert_result['is_success'] == FALSE)
	    {
		goto dfa_push_exit;
	    }
	    $dfa_campaign_id = $campaign_insert_result['dfa_result']->id;
		//}
	    
	    //GET ALL CREATIVES
	    $all_creatives = $this->cup_model->get_creatives_by_adset($version_id);
	    foreach($all_creatives as $creative_row)
	    {
		$creative_size = $creative_row['size'];
		$assets = $this->cup_model->get_assets_by_adset($version_id, $creative_size);
		$builder_version = $this->cup_model->get_builder_version_by_version_id($version_id);
		if($this->cup_model->files_ok($assets, $creative_size, $builder_version, FALSE, FALSE, TRUE))
		{
		    //get files in order and named up correctly for the html function later
		    $creative = $this->cup_model->prep_file_links($assets,$creative_size, $campaign_id);

		    $dfa_push_success = $this->publisher_model->push_dfa_creatives($creative, $creative_size, $dfa_advertiser_id, $dfa_campaign_id, $version_id, $is_bulk_flag, $landing_page);

		    if($dfa_push_success['is_success'] == FALSE)
		    {
				goto dfa_push_exit;
		    }
		}
	    }
	    
	    $is_success = true;
	    
	    dfa_push_exit:
	    return array('is_success'=>$is_success,'dfa_advertiser_id'=>$dfa_advertiser_id, 'dfa_campaign_id'=>$dfa_campaign_id, 'err_msg'=>$err_msg);
	}

	public function clone_adset_for_refreshing_campaign($adset_id, $campaign_id)
	{
	    $sql = "SELECT MAX(id)+1 AS new_id FROM cup_adsets";
	    $query = $this->db->query($sql);
	    $new_id = $query->row()->new_id;
	    
	    $sql = "INSERT INTO cup_adsets (name, adgroup_id, campaign_id)
		    SELECT concat(name, ?),
			   adgroup_id,
			   ?
		    FROM cup_adsets
		    WHERE id = ?";
	    $query = $this->db->query($sql, array(' - AS_'.$new_id.' - CMP_'.$campaign_id, $campaign_id,$adset_id));
	    if($this->db->affected_rows() > 0)
	    {
		$insert_id = $this->db->insert_id();
		return $insert_id;
	    }
	    else
	    {
		return false;
	    }
	}
	
	public function link_versions_array_creatives_to_ttd($version_ids, $campaign_id, $update_method)
	{
	    $is_success = false;
	     
	    if($update_method == "replace")
	    {
		$overwrite_ttd_creatives = true;
	    }
	    else if($update_method == "append")
	    {
		$overwrite_ttd_creatives = false;
	    }
	    else
	    {
		goto dfa_to_ttd_exit;
	    }
	    
	    foreach($version_ids as $version_id)
	    {
		$link_adsets_to_ttd_result = $this->link_versions_creatives_to_ttd($version_id['v_id'], $campaign_id, $overwrite_ttd_creatives);
		if(!$link_adsets_to_ttd_result['is_success'])
		{
		    goto dfa_to_ttd_exit;
		}
		$overwrite_ttd_creatives = false; //Overwrite only during first iteration of loop to not overwrite after each version
	    }
	    $is_success = true;
	    
	    dfa_to_ttd_exit:
	    return array('is_success'=>$is_success);
	}
	
	private function link_versions_creatives_to_ttd($version_id, $campaign_id, $overwrite_creatives)
	{
		$is_success = false;
		$access_token = $this->tradedesk_model->get_access_token();
		$ad_server_type_id = k_ad_server_type::resolve_string_to_id('dfa');
		$names = $this->tradedesk_model->get_campaign_and_advertiser_name_by_campaign_id($campaign_id);
		$ttd_advertiser_id = $this->tradedesk_model->get_ttd_advertiser_by_campaign($campaign_id);
		$adset_version_details = $this->tradedesk_model->get_version_adset_name_and_version_number($version_id);
		$creative_data = $this->cup_model->get_creatives_for_version($version_id,$ad_server_type_id);
		if($creative_data == FALSE)
		{
		    goto ttd_link_exit;
		}
		$landing_page = $this->tradedesk_model->get_campaign_landing_page($campaign_id);
		
		if($landing_page == FALSE || $adset_version_details == FALSE || $ttd_advertiser_id == FALSE || $names == FALSE)
		{
			goto ttd_link_exit;
		}
		
		$pc_creative_set = array();
		$mobile_320_creative_set = array();
		$mobile_no_320_creative_set = array(); 
		
		foreach($creative_data as $creative)
		{
			$new_ttd_creative = false;
			if($creative['ttd_creative_id'] == NULL)
			{
			    $new_ttd_creative = true;
			    $ad_choices_tag_string = $this->tradedesk_model->get_ad_choice_tag_by_version($version_id);
			    $ad_choices_trust_tag = $this->publisher_model->construct_ad_choices_element(
				    $creative['size'],
				    $ad_choices_tag_string
			    );
			    $configured_ad_tag = $creative['ad_tag'].$ad_choices_trust_tag;

				//MOAT Tag
				$moat_tag = $this->cup_model->generate_moat_tag_for_campaign_with_ad_size($campaign_id, $creative['size']);
				if($moat_tag !== false)
				{
					$configured_ad_tag .= $moat_tag;
				}

			    $size = explode('x', $creative['size']);
			    $width = $size[0];
			    $height = $size[1];
			    $ttd_creative_landing_page = trim($creative['ttd_creative_landing_page']);
			    $creative_name = $names->a_name.'_'.$names->c_name.'_'.$adset_version_details->adset_name.'_v'.$adset_version_details->v_number.'_BULK_dfa_'.$creative['size'];
			    $creative_name = str_replace(' ', '_', $creative_name);
			    $ttd_creative_id = $this->tradedesk_model->create_creative(
				    $access_token,
				    $creative_name,
				    $ttd_advertiser_id,
				    $configured_ad_tag,
				    $height,
				    $width,
				    $ttd_creative_landing_page
			    );
			}
			else
			{
			    $ttd_creative_id = $creative['ttd_creative_id'];
			}
			if($ttd_creative_id == FALSE)
			{
					goto ttd_link_exit;
			}
			array_push($pc_creative_set, $ttd_creative_id);
			if($creative['size'] == "320x50")
			{
					array_push($mobile_320_creative_set, $ttd_creative_id);
			}
			else
			{
					array_push($mobile_no_320_creative_set, $ttd_creative_id);
			}
			if($new_ttd_creative)
			{
			    if(!$this->tradedesk_model->add_ttd_id_to_creative($creative['id'], $ttd_creative_id, $ad_server_type_id))
			    {
					    goto ttd_link_exit;
			    }
			}
		}
	    
	    $adgroups = $this->tradedesk_model->get_all_ttd_adgroups_by_campaign($campaign_id);
	    if($adgroups == FALSE)
	    {
			goto ttd_link_exit;
	    }
	    foreach($adgroups as $adgroup)
	    {
		    $ag_id = $adgroup['ID'];

		    if($adgroup['target_type'] == NULL && $adgroup['Source'] != "TDAV" )
		    {
			$adgroup['target_type'] = $this->tradedesk_model->determine_target_type_for_adgroup_id($ag_id);
		    }

		    if($adgroup['target_type'] == "Mobile 320")
		    {
			$updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $mobile_320_creative_set, $overwrite_creatives);
		    }
		    else if($adgroup['target_type'] == "Mobile No 320")
		    {
			$updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $mobile_no_320_creative_set, $overwrite_creatives);
		    }
		    else if($adgroup['target_type'] != "Pre-Roll" && $adgroup['target_type'] != "Custom Averaged" && $adgroup['target_type'] != "Custom Ignored" || $adgroup['target_type'] != "RTG Pre-Roll")
		    {
			$updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, $pc_creative_set, $overwrite_creatives);
		    }
		    if($updated == FALSE)
		    {
			goto ttd_link_exit;
		    }
	    }

	    $is_success = true;
	    
	    ttd_link_exit:
	    return array('is_success'=>$is_success);
	}
	
	public function does_campaign_and_campaign_ttd_exist($id)
	{
		$sql = 'SELECT * FROM Campaigns WHERE id = ?';
		$bindings = array($id);
		
		$query = $this->db->query($sql, $bindings);
		$result = $query->result_array();
		if($query->num_rows() > 0 && $result[0]['ttd_campaign_id'] != NULL)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function update_f_campaign_cycle_target_and_end_date($campaign_id, $cycle_target, $end_date)
	{
	    $sql = "UPDATE Campaigns SET TargetImpressions = ?, hard_end_date = ? WHERE id = ?";
	    $update_array = array($cycle_target, $end_date, $campaign_id);
	    $query = $this->db->query($sql, $update_array);
	    if($this->db->affected_rows() < 1)
	    {
		return false;
	    }
	    return true;
	}

	public function create_bulk_subproduct_data_for_campaign($campaign_id, $is_pre_roll)
	{
		if($is_pre_roll === -1)
		{
			$get_subproduct_type_query = 
			"SELECT
				MIN(subproduct_type_id) AS subproduct_type_id
			FROM
				AdGroups
			WHERE
				campaign_id = ?
				AND subproduct_type_id IS NOT NULL
			";
			$get_subproduct_type_result = $this->db->query($get_subproduct_type_query, $campaign_id);
			if($get_subproduct_type_result == false || $get_subproduct_type_result->num_rows() < 1)
			{
				return false;
			}
			$row = $get_subproduct_type_result->row_array();
			$subproduct_type_id = $row['subproduct_type_id'];
		}
		else
		{
			$subproduct_type_id = ($is_pre_roll ? 2 : 1);
		}
		
		$get_partner_cpm_for_campaign_query =
		"SELECT
			wpbc.dollar_cpm AS dollar_cpm
		FROM
			Campaigns AS cmp
			JOIN Advertisers AS adv
				ON (cmp.business_id = adv.id)
			JOIN users AS u
				ON (adv.sales_person = u.id)
			JOIN wl_partner_bulk_cpm AS wpbc
				ON (u.partner_id = wpbc.wl_partner_id)
		WHERE
			cmp.id = ?
			AND subproduct_type_id = ?
		";
		$bindings = array($campaign_id, $subproduct_type_id);
		$get_partner_cpm_for_campaign_result = $this->db->query($get_partner_cpm_for_campaign_query, $bindings);

		if($get_partner_cpm_for_campaign_result == false)
		{
			return false;
		}

		if($get_partner_cpm_for_campaign_result->num_rows() < 1)
		{
			$get_partner_cpm_for_campaign_query =
			"SELECT
				wpbc.dollar_cpm AS dollar_cpm
			FROM
				 wl_partner_bulk_cpm AS wpbc
			WHERE
				wl_partner_id = ?
				AND subproduct_type_id = ?
			";

			$bindings = array(-1, $subproduct_type_id);
			$get_partner_cpm_for_campaign_result = $this->db->query($get_partner_cpm_for_campaign_query, $bindings);
			if($get_partner_cpm_for_campaign_result == false || $get_partner_cpm_for_campaign_result->num_rows() < 1)
			{
				return false;
			}
		}
		$row = $get_partner_cpm_for_campaign_result->row_array();
		$cpm = $row['dollar_cpm'];

		$insert_cpms_query = 
		"INSERT INTO 
			io_campaign_product_cpm
			(campaign_id, subproduct_type_id, dollar_cpm)
		VALUES
			(?, ?, ?)
		ON DUPLICATE KEY UPDATE
			dollar_cpm = VALUES(dollar_cpm)
		";
		$bindings = array($campaign_id, $subproduct_type_id, $cpm);
		$insert_cpms_result = $this->db->query($insert_cpms_query, $bindings);
		if($insert_cpms_result == false)
		{
			return false;
		}

		$update_flight_budgets_query = 
		"UPDATE
			campaigns_time_series
		SET
			budget = (impressions/1000)*?
		WHERE
			campaigns_id = ?
			AND object_type_id = 0
		";
		$bindings = array($cpm, $campaign_id);
		$update_flight_budgets_result = $this->db->query($update_flight_budgets_query, $bindings);
		if($update_flight_budgets_result == false)
		{
			return false;
		}

		$insert_subproduct_data_query = 
		"INSERT INTO io_flight_subproduct_details
			(io_timeseries_id, updated, audience_ext_budget, audience_ext_impressions, dfp_status)
		(SELECT
			id,
			NOW(),
			budget,
			impressions,
			\"COMPLETE\"
		FROM
			campaigns_time_series
		WHERE
			campaigns_id = ?
			AND object_type_id = 0)
		ON DUPLICATE KEY UPDATE
			updated = VALUES(updated),
			audience_ext_budget = VALUES(audience_ext_budget),
			audience_ext_impressions = VALUES(audience_ext_impressions)
		"; 
		$insert_subproduct_data_result = $this->db->query($insert_subproduct_data_query, $campaign_id);
		if($insert_subproduct_data_result == false)
		{
			return false;
		}
		return true;
	}

	public function push_pre_roll_creative_to_campaign($campaign_id, $creative_id)
	{
		$access_token = $this->tradedesk_model->get_access_token();
		$adgroups = $this->tradedesk_model->get_all_ttd_adgroups_by_campaign($campaign_id);
		if($adgroups == FALSE)
		{
			return false;
		}
	    foreach($adgroups as $adgroup)
	    {
		    $ag_id = $adgroup['ID'];
		    $updated = $this->tradedesk_model->update_adgroup_creatives($access_token, $ag_id, array($creative_id), true);
		    if(!$updated)
		    {
		    	return false;
		    }
		}
		return true;
	}
}
?>
