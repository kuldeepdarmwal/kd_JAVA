<?php

class Custom_gallery_model extends CI_Model 
{

	public function __construct()
	{
		$this->load->database();
		$this->load->helper('url');
	}


	public function get_broadcasted_adsets_by_search_term($partner_id, $search_term, $start, $limit)
	{
		$sql = "
			SELECT 
				base.adset_version_id, 
				base.friendly_adset_name,
				base.open_uri, 
				concat('',group_concat(base.friendly_name separator ' , ')) as features
			FROM 
			(
				SELECT DISTINCT 
					gap.adset_version_id, 
					gav.friendly_adset_name, 
					caa.open_uri, 
					gf.friendly_name
				FROM 
					wl_partner_hierarchy wph
					RIGHT JOIN gallery_adsets_to_partner gap
						ON wph.descendant_id = gap.partner_id
					LEFT JOIN gallery_adset_versions gav
						ON gap.adset_version_id = gav.adset_version_id
					LEFT JOIN cup_creatives cc
						ON gap.adset_version_id = cc.version_id
					LEFT JOIN cup_ad_assets caa
						ON caa.creative_id = cc.id
					LEFT JOIN gallery_adset_version_to_features avf
						ON gap.adset_version_id = avf.adset_version_id
					LEFT JOIN gallery_features as gf
						ON avf.gallery_feature_id = gf.id
				WHERE 
					(wph.ancestor_id = ? OR gap.partner_id = '0') AND 
					cc.size = '300x250' AND 
					caa.type = 'backup' AND 
					(gav.friendly_adset_name LIKE ? OR gf.friendly_name LIKE ?) 
			) as base
			GROUP BY base.adset_version_id
			ORDER BY base.adset_version_id DESC
			LIMIT ?, ?";
		$query = $this->db->query($sql, array($partner_id, $search_term, $search_term, $start, $limit));
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	public function insert_new_gallery($name,$adsets,$user_id, $is_tracked)
	{ 
		
		$is_success = false;
		$is_alert = false;
		$message = null;
		$gallery = array();
		//first add an entry into the custom_galleries table
		$sql = "INSERT INTO custom_galleries (user_id, friendly_name, slug, is_tracked) 
				VALUES (?, ?, ?,  ?)";
		$new_gallery_insert_result = $this->insert_with_error_code_enabled($sql, array($user_id, $name,url_title($name),$is_tracked));
		if($new_gallery_insert_result['is_success'])//if the insert doesn't work, returns false
		{
			//loop through and insert each one into the custom_gallery_adset_versions table
			if(count($adsets)>0)
			{
				$order = 0;
				$gallery_id = $this->db->insert_id();//this is the gallery id result from the insert into custom_galleries
				$insert_string = "INSERT INTO custom_gallery_adset_versions (custom_gallery_id, adset_version_id, tile_order) 
									VALUES ";
				$bindings = array();
				foreach($adsets as $adset_version)
				{
					$insert_string .= "(?,?,?) ";
					array_push($bindings, $gallery_id, $adset_version['id'], $order);
					$order++;
					if($order<count($adsets))
					{
						$insert_string .= ", ";
					}
				}
				$this_insert_result = $this->db->query($insert_string, $bindings);
				if(!$this_insert_result)
				{
					$message = 'adset insertion problem';
				}
			}
			else{///adsets array is empty!!
				$message = 'no adsets to save';//system - ui error - should have been handled before getting here
			}
			///retreive saved details for this one
			$inserted_gallery_details = $this->get_live_gallery_details_by_id($gallery_id);
			if($inserted_gallery_details)
			{
				$is_success = true;
				$gallery = $inserted_gallery_details[0];//success!
			}
			else
			{
				$message = 'couldn\t retreive successful gallery insertion result';
			}
		}
		else//the gallery insert into custom_gallery_table didn't work (most likely duplicate name)
		{
			$sql_error_detail = $this->get_friendly_gallery_insert_error($new_gallery_insert_result['result']);
			$is_success = $sql_error_detail['user_fixable']? true : false;//if the user can fix it we'll say that the query was a success
			$is_alert = $sql_error_detail['user_fixable'];//although the insert didn't work we need to allow the ui to tell the user how to fix it
			$message = $sql_error_detail['message'];
		}
		return array('is_success'=>$is_success,
				'is_alert'=>$is_alert,
				'message'=>$message,
				'gallery'=>$gallery );
	}

	private function insert_with_error_code_enabled($sql, $bindings)
	{
		$orig_db_debug = $this->db->db_debug;
		$this->db->db_debug = FALSE;//setting this to false will allow the db to return an error code that can be handles by us
		$query_result = $this->db->query($sql, $bindings);
		$this->db->db_debug = $orig_db_debug;
		if($query_result)
		{
			return array('is_success'=>true,'result'=>$query_result);
		}
		else
		{
			return array('is_success'=>false,'result'=>$this->db->_error_number());
		}
	}

	private function get_friendly_gallery_insert_error($sql_error_number)
	{
		switch ($sql_error_number) 
		{
			case 1062:
				return array('user_fixable'=>true,'message'=>"DUPLICATE");//user fixable error
				break;
			default:
				return array('user_fixable'=>false,'message'=>'id- '.$sql_error_number);//system
				break;
		}
	}

	

	public function get_galleries_by_search_term($search_term, $start, $limit, $user)
	{
		$bindings = array();
		$sql = "
				SELECT DISTINCT
					cg.id, 
					cg.friendly_name, 
					cg.last_saved, 
					cg.slug, 
					cg.is_tracked, 
					cg.user_id AS u, 
					CONCAT(us.firstname,' ',us.lastname) AS u_name";

		if($user->role == 'ADMIN' OR $user->role == 'OPS')
		{
			$sql .= "
					FROM
						custom_galleries AS cg
						JOIN users AS us
							ON cg.user_id = us.id
					WHERE
						cg.friendly_name LIKE ? AND
						cg.is_archived = 0";
		}
		else
		{
			$is_super_string = "";
			if($user->isGroupSuper == 1)
			{
				$is_super_string = " OR us_src.partner_id = wph.ancestor_id";
			}
			$sql .= "
					FROM
						users AS us_src
						LEFT JOIN wl_partner_owners AS wpo
							ON us_src.id = wpo.user_id
						JOIN wl_partner_hierarchy AS wph
							ON (wpo.partner_id = wph.ancestor_id".$is_super_string.")
						JOIN wl_partner_details AS wpd
							ON wph.descendant_id = wpd.id
						RIGHT JOIN users AS us
							ON wpd.id = us.partner_id
						JOIN custom_galleries AS cg
							ON us.id = cg.user_id
					WHERE 
						(us_src.id = ? OR us.id = ?) AND
						cg.friendly_name LIKE ? AND
						cg.is_archived = 0";
			
			$bindings[] = $user->id;
			$bindings[] = $user->id;
		}
		
		$sql .= "
				ORDER BY
					cg.last_saved DESC
				LIMIT ?, ?";
	
		$bindings[] = $search_term;
		$bindings[] = $start;
		$bindings[] = $limit;

		$query = $this->db->query($sql, $bindings);
		if($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		return array();
	}

	public function update_gallery($g_id,$is_tracked, $u_id)
	{
		$sql = "UPDATE custom_galleries
				SET  is_tracked = ?, user_id = ?
				WHERE id=?";
				
		if($query = $this->db->query($sql, array($is_tracked,$u_id,$g_id)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function update_custom_gallery($g_id,$adsets,$is_tracked, $u_id)
	{
		///first update the custom_gallery table
		if($this->update_gallery($g_id,$is_tracked, $u_id))
		{
			///then delete all the custom_gallery_adset_versions rows
			$delete_sql = "DELETE FROM custom_gallery_adset_versions WHERE custom_gallery_id = ?";
			$delete_bindings = array('id'=>$g_id);
			$query = $this->db->query($delete_sql, $delete_bindings);

			////then insert all the custom_gallery_adset_versions rows
			if(count($adsets)>0)
			{
				$order = 0;
				$gallery_id = $g_id;//this is the gallery id result from the insert into custom_galleries
				$insert_string = "INSERT INTO custom_gallery_adset_versions (custom_gallery_id, adset_version_id, tile_order) 
									VALUES ";
				$bindings = array();
				foreach($adsets as $adset_version)
				{
					$insert_string .= "(?,?,?) ";
					array_push($bindings, $gallery_id, $adset_version['id'], $order);
					$order++;
					if($order<count($adsets))
					{
						$insert_string .= ", ";
					}
				}
				$this_insert_result = $this->db->query($insert_string, $bindings);
				if(!$this_insert_result)
				{
					return array('is_success'=>false,'message'=>'tile insertion problem');//system error
				}
			}
			else{///adsets array is empty!!
				return array('is_success'=>false,'message'=>'no adsets to save');//system - user error
			}
			return array('is_success'=>true,'message'=>'existing gallery updated');
		}
		else
		{
			return array('is_success'=>false,'message'=>'couldn\'t update the gallery');
		}
	}

	public function get_gallery_by_id($id, $cname_derived_partner_id)
	{
		$sql = "
				SELECT
					gcav.adset_version_id as id, 
					gav.friendly_adset_name as saved_adset_name, 
					concat('',group_concat(gf.friendly_name separator ' , ')) as features, 
					caa.open_uri as thumb
				FROM 
					wl_partner_hierarchy wph
					RIGHT JOIN gallery_adsets_to_partner gap
						ON wph.descendant_id = gap.partner_id
					JOIN custom_gallery_adset_versions gcav
						ON gcav.adset_version_id = gap.adset_version_id
					LEFT JOIN cup_versions cv
						ON gcav.adset_version_id = cv.id
					LEFT JOIN cup_adsets ca
						ON cv.adset_id = ca.id
					LEFT JOIN cup_creatives cc
						ON gcav.adset_version_id = cc.version_id
					LEFT JOIN cup_ad_assets caa
						ON caa.creative_id = cc.id
					LEFT JOIN gallery_adset_version_to_features avf
						ON gcav.adset_version_id = avf.adset_version_id
					LEFT JOIN gallery_features as gf
						ON avf.gallery_feature_id = gf.id
					LEFT JOIN gallery_adset_versions gav
						ON gav.adset_version_id = gcav.adset_version_id 
				WHERE gcav.custom_gallery_id = ? AND
					cc.size = '300x250' AND 
					caa.type = 'backup' AND 
					(wph.ancestor_id = ? OR gap.partner_id = 0)
				GROUP BY gcav.adset_version_id
				ORDER BY gcav.tile_order";
		$result = $this->db->query($sql, array('id'=>$id,'pid'=>$cname_derived_partner_id));
		
		if($result->num_rows() > 0)
		{
			$result_array = $result->result_array();
			for($i = 0; $i < $result->num_rows(); $i++){
				$adsets_array[$i] = array(
					'id' => $result_array[$i]['id'], 
					'thumb'=> $result_array[$i]['thumb'],
					'features'=> $result_array[$i]['features'],
					'saved_adset_name'=> $result_array[$i]['saved_adset_name'],
					'preview_link'=> '/crtv/get_gallery_adset/'.base64_encode(base64_encode(base64_encode(intval($result_array[$i]['id']))))
				);
			}
			return json_encode($adsets_array);
		}
		else
		{
			return false;
		}
	}
	public function get_custom_gallery_data($user_id, $slug)
	{
		$sql = "
				SELECT 
					cg.id,
					cg.friendly_name, 
					cg.is_tracked, 
					u.email, 
					p.cname
				FROM 
					custom_galleries cg 
					JOIN users u 
						ON u.id = cg.user_id 
					JOIN wl_partner_details p
						ON u.partner_id = p.id
				WHERE 
					cg.user_id = ? AND 
					cg.slug = ? AND 
					cg.is_archived = 0 ";

		$result = $this->db->query($sql, array($user_id, $slug));

		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		else
		{
			return false;
		}
	}

	function get_live_gallery_details_by_id($id)
	{
		$sql = "
				SELECT 
					cg.id as id,
					cg.friendly_name as text, 
					cg.slug as slug, 
					cg.user_id as u,  
					cg.is_tracked as is_tracked, 
					concat(u.firstname,' ',u.lastname) as u_name
				FROM 
					custom_galleries cg 
					LEFT JOIN users u 
						ON u.id = cg.user_id 
				WHERE 
					cg.id = ? AND 
					cg.is_archived = 0";
		$result = $this->db->query($sql, array('id'=>$id));

		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		else
		{
			return false;
		}
	}


	function mailgun($from, $to, $subject, $message)
	{
		$is_mail_sent = false;

		$email_curl = curl_init();

		$post_data = array(
			'from' => $from,
			'to' => $to,
			'subject' => $subject,
			'html' => $message
		);

		$curl_options = array(
			CURLOPT_URL => 'https://api.mailgun.net/v2/mg.brandcdn.com/messages',
			CURLOPT_USERPWD => 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_RETURNTRANSFER => true
		);

		$is_ok = curl_setopt_array($email_curl, $curl_options);
		if($is_ok)
		{
			$result = curl_exec($email_curl);
			if($result != false)
			{
				$is_mail_sent = true;
			}
		}

		curl_close($email_curl);
		$email_curl = null;

		return $is_mail_sent;
	}

	function archive_gallery_by_id($id)
	{
		$sql = "UPDATE custom_galleries
				SET is_archived = 1, deleted_time = CURRENT_TIMESTAMP
				WHERE id=?";
				
		if($query = $this->db->query($sql, $id))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function  update_gallery_tracking($g_id,$is_tracked)
	{
		$sql = "UPDATE custom_galleries
				SET is_tracked = ?
				WHERE id=?";
				
		if($query = $this->db->query($sql, array($is_tracked,$g_id)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function get_list_of_editors($user)
	{
		if($user->role != 'BUSINESS' AND $user->role != 'CREATIVE')//if ADMIN, OPS, SALES
		{
			$bindings = array();
			$sql = "
					SELECT DISTINCT
						us.id AS id,
						CONCAT(COALESCE(us.firstname, ''), ' ', COALESCE(us.lastname, '')) AS text";
			if($user->role == 'SALES')
			{
				$is_super_string = "";
				if($user->isGroupSuper == 1)
				{
					$is_super_string = " OR us_src.partner_id = wph.ancestor_id";
				}
				$sql .= "
							FROM
								users AS us_src
								LEFT JOIN wl_partner_owners AS wpo
									ON us_src.id = wpo.user_id
								JOIN wl_partner_hierarchy AS wph 
									ON (wpo.partner_id = wph.ancestor_id".$is_super_string.")
								JOIN wl_partner_details AS wpd
									ON wph.descendant_id = wpd.id
								RIGHT JOIN users AS us
									ON wpd.id = us.partner_id
							WHERE
								us.activated = 1 AND
								us.banned = 0 AND
								us.role = 'SALES' AND
								(us_src.id = ? OR us.id = ?)";

				$bindings[] = $user->id;
				$bindings[] = $user->id;
			}
			else
			{
				$sql .= "
						FROM
							wl_partner_hierarchy AS wph
							JOIN wl_partner_details AS wpd
								ON wph.descendant_id = wpd.id
							JOIN users AS us
								ON wpd.id = us.partner_id
						WHERE
							us.role IN ('ADMIN', 'OPS', 'SALES') AND 
							us.activated = 1 AND 
							us.banned = 0
						ORDER BY text";
			}

			$result = $this->db->query($sql, $bindings);

			if($result->num_rows() > 0)
			{
				return $result->result_array();
			}
		}
		return false;		
	}

	private function get_user_filtered_query_string($user_list)
	{
		$bindings = array();
		$user_sql = "0";
		foreach($user_list as $this_user)
		{
			$user_sql .= ", ?";
			$bindings[] = $this_user['id'];
		}
		$sql = "
				SELECT
					*
				FROM
					custom_galleries
				WHERE
					user_id IN (".$user_sql.") AND
					is_archived = 0";
		
		return array('q_string' => $sql, 'bindings' => $bindings);
	}

	public function get_galleries_from_user_list($user_list)
	{
		if(is_array($user_list))
		{
			$sql = $this->get_user_filtered_query_string($user_list);
			$query = $this->db->query($sql['q_string'], $sql['bindings']);
			if($query->num_rows() > 0)
			{
				return $query->result_array();
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}


}
?>