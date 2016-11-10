<?php

class Gallery_model extends CI_Model 
{

	public function __construct()
	{
		$this->load->database();
	}

	public function get_gallery_tiles($partner_id, $limit){// OR cc.size='728x90' OR cc.size='160x600'
		$bindings = array();
		$bindings[] = $partner_id;
		$bindings[] = intval($limit);
		$sql = "
			SELECT DISTINCT
				caa.open_uri, 
				cv.id as v_id, 
				gav.friendly_adset_name as name, 
				group_concat(concat('feature_',gf.id) separator ' ') as feature_classes
			FROM 
				wl_partner_details wpd
				JOIN wl_partner_hierarchy wph
					ON wpd.id = wph.ancestor_id
				RIGHT JOIN gallery_adsets_to_partner ap
					ON wph.descendant_id = ap.partner_id
				JOIN gallery_adset_versions gav
					ON ap.adset_version_id = gav.adset_version_id
				JOIN cup_versions cv
					ON gav.adset_version_id = cv.id
				JOIN cup_creatives cc
					ON cc.version_id = gav.adset_version_id
				JOIN cup_ad_assets caa
					ON caa.creative_id = cc.id
				LEFT JOIN gallery_adset_version_to_features avf
					ON avf.adset_version_id = ap.adset_version_id
				LEFT JOIN gallery_features gf
					ON gf.id = avf.gallery_feature_id
			WHERE 
				cc.size='300x250' AND 
				caa.type = 'backup' AND 
				(ap.partner_id = 0 OR wpd.id = ?)
			GROUP BY v_id
			ORDER BY v_id DESC
			LIMIT ?";
		$response = $this->db->query($sql, $bindings);
		// return $response->result_array();

		if($response->num_rows() > 0)
		{
			return $response->result_array();
		}
		else
		{
			return false;
		}
	
	}

	public function get_all_adset_versions(){
		$bindings = array();
		$sql = "
			SELECT 
				base.*,
				concat('[',group_concat(concat('{id:',avf.gallery_feature_id,',text:''',gf.friendly_name,'''}') separator','),']') AS features
			FROM 
				(
				SELECT 
					cv.id AS adset_v_id, 
					a.Name AS advertiser_name, 
					c.Name AS campaign_name, 
					ca.name AS adset_name, 
					cv.version AS v_num, 
					caa.open_uri AS thumb_url, 
					gav.friendly_adset_name AS saved_adset_name,
					concat('[',group_concat(concat('{id:',avp.partner_id,',text:''',COALESCE(p.partner_name,'ALL'),'''}' )separator','),']') AS partners
				FROM 
					cup_ad_assets AS caa
					JOIN cup_creatives AS cc
						ON caa.creative_id = cc.id
					JOIN cup_versions AS cv
						ON cc.version_id = cv.id
					JOIN cup_adsets AS ca 
						ON cv.adset_id = ca.id
					LEFT JOIN Campaigns AS c
						ON cv.campaign_id = c.id
					LEFT JOIN Advertisers AS a
						ON c.business_id = a.id
					LEFT JOIN gallery_adsets_to_partner AS avp
						ON cv.id = avp.adset_version_id
					LEFT JOIN gallery_adset_versions AS gav
						ON cv.id = gav.adset_version_id
					LEFT JOIN wl_partner_details AS p
						ON p.id = avp.partner_id
				WHERE 
					caa.type = 'backup' AND 
					cc.size='300x250'
				GROUP BY 
					adset_v_id
				ORDER BY 
					adset_v_id DESC
				) AS base
				LEFT JOIN gallery_adset_version_to_features avf
					ON base.adset_v_id = avf.adset_version_id
				LEFT JOIN gallery_features AS gf
					ON avf.gallery_feature_id = gf.id
			GROUP BY 
				adset_v_id
			ORDER BY 
				adset_v_id DESC";
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_all_partners_and_ids(){
		$bindings = array();
		$sql = "SELECT id, partner_name FROM wl_partner_details";
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_all_gallery_features_and_ids(){
		$bindings = array();
		$sql = "SELECT id, friendly_name FROM gallery_features";
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function save_adset_to_gallery($adset_details)
	{
		//insert adset version to gallery_adset_version table
		$result['success'] = FALSE;
		$version_gallery_table_update_array = array($adset_details['adset_version_id'],$adset_details['gallery_adset_name']);
		//return $version_gallery_table_update_array;
		$adset_gallery_table_update_result = $this->save_adset_to_gallery_table($version_gallery_table_update_array);
		if($adset_gallery_table_update_result['success'])
		{
			//delete all adset to partner relationships for this adset version
			$this->delete_adset_partner_rels($adset_details['adset_version_id']);
			//insert all new adset to partner relationships
			$add_partners_result = $this->add_partners_to_adset_version($adset_details['adset_version_id'],$adset_details['partners']);
			if($add_partners_result['success'])
			{
				//delete all adset to filter relationships
				$this->delete_adset_feature_rels($adset_details['adset_version_id']);
				//insert all new adset to feature relationships
				$result = $this->add_features_to_adset_version($adset_details['adset_version_id'],$adset_details['features']);
			}
			else{
				$result['message'] = $add_partners_result['message'];
			}
		}
		else
		{//adset gallery update result failed
			$result['message'] = "failed inserting into the adset gallery table";
		}
		return $result;
	}


	private function save_adset_to_gallery_table($adset_gallery_array){
		$query_data = array_merge($adset_gallery_array,$adset_gallery_array);
		$sql = "INSERT INTO gallery_adset_versions 
				(adset_version_id,friendly_adset_name) 
				VALUES (?,?) 
				ON DUPLICATE KEY 
				UPDATE 
				adset_version_id = ?,
				friendly_adset_name = ?,
				last_updated = CURRENT_TIMESTAMP()";
		$query = $this->db->query($sql, $this->db->escape($query_data));
		if($this->db->affected_rows() == 0){
			return array("success"=>FALSE,"message"=>"an adset insert/update didn't work");
		}else{
			return array("success"=>TRUE,"message"=>"adset updated/inserted","adset_version_id"=>mysql_insert_id(), "adset_name"=>$adset_gallery_array[1]);
		}
	}

	private function delete_adset_partner_rels($adset_version_id){
		$sql = "DELETE FROM gallery_adsets_to_partner 
				WHERE adset_version_id  = ".$adset_version_id;
		$query = $this->db->query($sql);
	}

	private function add_partners_to_adset_version($adset_version_id,$partners){
		$partners_inserted = 0;
		foreach($partners as $p_id){
			if($p_id != ''){
				$data = array('adset_version_id' => $adset_version_id, 'partner_id' => $p_id);
				$sql =  "INSERT IGNORE INTO gallery_adsets_to_partner (adset_version_id,  partner_id) VALUES ( ?, ?)";
				$query = $this->db->query($sql, $this->db->escape($data));
				if($this->db->affected_rows() == 0){
					return array("success"=>FALSE,"message"=>"a partner insert didn't work");
				}
				$partners_inserted++;
			}
		}
		return array("success"=>TRUE,"message"=>$partners_inserted." partners inserted");
	}

	private function delete_adset_feature_rels($adset_version_id){
		$sql = "DELETE FROM gallery_adset_version_to_features 
				WHERE adset_version_id  = ".$adset_version_id;
		$query = $this->db->query($sql);
	}

	private function add_features_to_adset_version($adset_version_id,$features){
		$features_inserted = 0;
		foreach($features as $f_id){
			if($f_id != ''){
				$data = array('adset_version_id' => $adset_version_id, 'feature_id' => $f_id);
				$sql =  "INSERT IGNORE INTO gallery_adset_version_to_features (adset_version_id,  gallery_feature_id) VALUES ( ?, ?)";
				$query = $this->db->query($sql, $this->db->escape($data));
				if($this->db->affected_rows() == 0){
					return array("success"=>FALSE,"message"=>"a feature insert didn't work");
				}
				$features_inserted++;
			}
		}
		return array("success"=>TRUE,"message"=>$features_inserted." features inserted");
	}

	public function get_gallery_features($partner_id,$limit){
		$bindings = array();
		$bindings[] = intval($partner_id);
		$bindings[] = intval($limit);
		$sql = "
			SELECT 
				DISTINCT gf.id as feature_id,
				gf.friendly_name as feature_name
			FROM
			(
				SELECT 
					caa.open_uri, 
					cv.id as v_id, 
					gav.friendly_adset_name as name	
				FROM 
					gallery_adsets_to_partner ap
					LEFT JOIN gallery_adset_versions gav 
						ON ap.adset_version_id = gav.adset_version_id 
					LEFT JOIN cup_versions cv 
						ON gav.adset_version_id = cv.id 
					LEFT JOIN cup_creatives cc 
						ON cc.version_id = gav.adset_version_id
					LEFT JOIN cup_ad_assets caa 
						ON caa.creative_id = cc.id
				WHERE
					cc.size='300x250' AND	
					caa.type = 'backup' AND	
					(
						ap.partner_id = 0 OR 
						ap.partner_id = ?
					)
				GROUP BY v_id
				ORDER BY v_id DESC
				LIMIT ?	
			) limited_adsets
				LEFT JOIN gallery_adset_version_to_features avf
					ON avf.adset_version_id = limited_adsets.v_id
				LEFT JOIN gallery_features gf
					ON gf.id = avf.gallery_feature_id
			ORDER BY gf.id";
		$response = $this->db->query($sql, $bindings);
		return $response->result_array();
	}

	public function get_adsets_by_search_term($search_term, $start, $limit)
	{
		$sql = "
			SELECT 
				base.*,
				concat('[',group_concat(concat('{id:',avf.gallery_feature_id,',text:''',gf.friendly_name,'''}') separator','),']') as features
			FROM
		 	(
				SELECT 
					cv.id as adset_v_id, 
					a.Name as advertiser_name, 
					c.Name as campaign_name, 
					ca.name as adset_name, 
					cv.version as v_num, 
					caa.open_uri as thumb_url, 
					gav.friendly_adset_name as saved_adset_name,
					concat('[',group_concat(concat('{id:',avp.partner_id,',text:''',COALESCE(p.partner_name,'ALL'),'''}' )separator','),']') as partners
				FROM 
					cup_ad_assets caa
					JOIN cup_creatives cc
						ON caa.creative_id = cc.id
					JOIN cup_versions cv
						ON cc.version_id = cv.id
					JOIN cup_adsets ca 
						ON cv.adset_id = ca.id
					LEFT JOIN Campaigns c
						ON ca.campaign_id = c.id
					LEFT JOIN Advertisers a
						ON c.business_id = a.id
					LEFT JOIN gallery_adsets_to_partner avp
						ON cv.id = avp.adset_version_id
					LEFT JOIN gallery_adset_versions gav
						ON cv.id = gav.adset_version_id
					LEFT JOIN wl_partner_details p
						ON p.id = avp.partner_id
				WHERE 
					caa.type = 'backup' AND 
					cc.size = '300x250' AND 
					(a.name LIKE ? OR c.name LIKE ? OR ca.name LIKE ?)
				GROUP BY 
					adset_v_id
				ORDER BY 
					adset_v_id DESC
			) base
				LEFT JOIN gallery_adset_version_to_features avf
					ON base.adset_v_id = avf.adset_version_id
				LEFT JOIN gallery_features as gf
					ON avf.gallery_feature_id = gf.id
			GROUP BY 
				adset_v_id
			ORDER BY 
				adset_v_id DESC
			LIMIT ?, ?";
		$query = $this->db->query($sql, array($search_term, $search_term, $search_term, $start, $limit));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}
}

