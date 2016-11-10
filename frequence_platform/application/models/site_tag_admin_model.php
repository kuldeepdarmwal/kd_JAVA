<?php
class site_tag_admin_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}
	public function get_media_targeting_tags_by_term($search_term, $start, $limit)
	{
		$sql="SELECT * FROM
				(
					SELECT
						CONCAT('0>', freq_sites_main_id) AS id,
						CONCAT('SITE: ', url) AS tag_copy,
						0 AS tag_type
					FROM
						freq_sites_main
					WHERE
						CONCAT('SITE: ', url) LIKE '".$search_term."'
					UNION
					SELECT
						CONCAT('1>', id) AS id,
						CONCAT('IAB: ', tag_copy) AS tag_copy,
						1 AS tag_type
					FROM
						iab_categories
					WHERE
						CONCAT('IAB: ', tag_copy) LIKE  '".$search_term."'
					UNION
					SELECT
						CONCAT('2>', id_auto) AS id,
						CONCAT('GEO: ', NAMELSAD10,', ',geo_state_code) AS tag_copy,
						2 AS tag_type
					FROM
						geo_place_map
					WHERE
						CONCAT('GEO: ', NAMELSAD10) LIKE  '".$search_term."'
					UNION
					SELECT
						CONCAT('4>', freq_stereotypes_id) AS id,
						CONCAT('STEREO: ', name) tag_copy,
						4 AS tag_type
					FROM
						freq_stereotypes
					WHERE
						CONCAT('STEREO: ', name) LIKE  '".$search_term."'
				) AS alias
				ORDER BY tag_copy
				LIMIT ".$start.", ".$limit."";
		
		$query=$this->db->query($sql);
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_sites_media_targeting_tags_by_term($search_term, $start, $limit, $bidder_flag=null)
	{
		$bidder_sql=" AND is_bidder_only_flag IS NULL ";
		if ($bidder_flag == 1)
		{
			$bidder_sql = " AND is_bidder_only_flag=1 ";
		}
		$sql="
			SELECT * FROM
				(
					SELECT
						freq_sites_main_id AS id,
						url AS tag_copy,
						0 AS tag_type
					FROM
						freq_sites_main
					WHERE
						url LIKE  '".$search_term."' 
						$bidder_sql
				) AS alias
				ORDER BY tag_copy
				LIMIT ".$start.", ".$limit."";
		
		$query=$this->db->query($sql);
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_context_media_targeting_tags_by_term($search_term, $start, $limit)
	{
		$sql="SELECT * FROM
				(
					SELECT
						id AS id,
						tag_copy AS tag_copy,
						1 AS tag_type
					FROM
						iab_categories
					WHERE
						tag_copy LIKE  '".$search_term."'
				) AS alias
				ORDER BY tag_copy
				LIMIT ".$start.", ".$limit."";
		
		$query=$this->db->query($sql);
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_geo_media_targeting_tags_by_term($search_term, $start, $limit)
	{
		$sql="SELECT * FROM
				(
					SELECT
						id_auto AS id,
						CONCAT(NAMELSAD10,', ',geo_state_code) AS tag_copy,
						2 AS tag_type
					FROM
						geo_place_map
					WHERE
						NAMELSAD10 LIKE  '".$search_term."'
				) AS alias
				ORDER BY tag_copy
				LIMIT ".$start.", ".$limit."";
		
		$query=$this->db->query($sql);
		
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_stereo_media_targeting_tags_by_term($search_term, $start, $limit)
	{
		$sql="SELECT * FROM
				(
					SELECT
						freq_stereotypes_id AS id,
						name AS tag_copy,
						4 AS tag_type
					FROM
						freq_stereotypes
					WHERE
						name LIKE  '".$search_term."'
				) AS alias
				ORDER BY tag_copy
				LIMIT ".$start.", ".$limit."";
		
		$query=$this->db->query($sql);
		
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_site_data_grid($site_id_array)
	{
		$site_id_final=array();
		foreach ($site_id_array as $id)
		{
			$tag_type=substr($id, 0, 1);
			if ($tag_type=="0")
			{
				$site_id_final[]=substr($id, 2);
			}
		}
		$site_ids=implode(",", $site_id_final);
		
		$sql="
		SELECT * FROM (
			SELECT m.freq_sites_main_id 'site_id',
				 m.url 'url',
				 c.tag_copy 'tag',
				 '1' AS 'type',
				 c.id AS tag_id		
			FROM 
				freq_sites_main m
			 	LEFT JOIN freq_site_tagging_link a ON a.tag_type_id = 1 AND a.freq_sites_main_id = m.freq_sites_main_id
			 	LEFT JOIN iab_categories c ON a.tag_id = c.id
			WHERE m.is_rejected_flag IS NULL AND 
				".$this->generate_sql_for_ids($site_id_final, ' m.freq_sites_main_id IN ')."
			UNION
			SELECT m.freq_sites_main_id 'site_id',
				 m.url 'url',
				 CONCAT(c.NAMELSAD10,', ', c.geo_state_code) 'tag',
				 '2' AS 'type',
				 c.id_auto AS tag_id	
			FROM 
				freq_sites_main m
				LEFT JOIN freq_site_tagging_link a ON a.tag_type_id = 2 AND a.freq_sites_main_id = m.freq_sites_main_id
				LEFT JOIN geo_place_map c ON a.tag_id = c.id_auto
			WHERE  m.is_rejected_flag IS NULL AND ".$this->generate_sql_for_ids($site_id_final, ' m.freq_sites_main_id IN ')."
			UNION
			SELECT m.freq_sites_main_id 'site_id',
				 m.url 'url',
				 c.name 'tag',
				 '4' as 'type',
		 		 c.freq_stereotypes_id AS tag_id	
			FROM freq_sites_main m 
				LEFT JOIN freq_site_tagging_link a ON a.tag_type_id = 4 AND a.freq_sites_main_id = m.freq_sites_main_id
				LEFT JOIN freq_stereotypes c ON a.tag_id = c.freq_stereotypes_id
			WHERE  m.is_rejected_flag IS NULL AND ".$this->generate_sql_for_ids($site_id_final, ' m.freq_sites_main_id IN ')."
		) AS full 
				 ORDER BY url, tag";
		
		$bindings=array();
		$bindings=array_merge($bindings, $site_id_final);
		$bindings=array_merge($bindings, $site_id_final);
		$bindings=array_merge($bindings, $site_id_final);
		$query=$this->db->query($sql, $bindings);
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_tags_data_grid($id_array)
	{
		$id_final=array();
		$tag_type="2";
		foreach ($id_array as $id)
		{
			$tag_type=substr($id, 0, 1);
			$id_final[]=substr($id, 2);
		}
		$ids=implode(",", $id_final);
		
		$sql="";
		if ($tag_type=="1") // iab
		{
			$sql="
				SELECT m.freq_sites_main_id AS 'site_id',
					c.id AS tag_id,
					m.url AS 'url',
					c.tag_copy AS 'tag',
					'1' AS 'type',
					m.is_bidder_only_flag AS 'is_bidder_only_flag'
				FROM   
				iab_categories c
					LEFT JOIN freq_site_tagging_link a ON a.tag_type_id = 1 AND a.tag_id = c.id
					LEFT JOIN freq_sites_main m ON a.freq_sites_main_id = m.freq_sites_main_id  AND m.is_rejected_flag IS NULL   
				WHERE ".$this->generate_sql_for_ids($id_final, ' c.id IN ')." ORDER BY tag, url";
		}
		else if ($tag_type=="2") // geo
		{
			$sql="
				SELECT m.freq_sites_main_id AS 'site_id',
					c.id_auto AS tag_id,
					m.url AS 'url',
					CONCAT(c.NAMELSAD10,', ',c.geo_state_code) AS 'tag',
					'2' AS 'type',
					m.is_bidder_only_flag AS 'is_bidder_only_flag'
				FROM   
				geo_place_map c
					LEFT JOIN freq_site_tagging_link a ON a.tag_type_id = 2 AND a.tag_id = c.id_auto
					LEFT JOIN freq_sites_main m ON a.freq_sites_main_id = m.freq_sites_main_id AND m.is_rejected_flag IS NULL  
				WHERE ".$this->generate_sql_for_ids($id_final, ' c.id_auto IN ')." order by tag, url";
		}
		else if ($tag_type=="4") // stereo
		{
			$sql="
				SELECT m.freq_sites_main_id AS 'site_id',
					c.freq_stereotypes_id AS tag_id,
					m.url AS 'url',
					c.name AS 'tag',
					'4' AS 'type',
					m.is_bidder_only_flag AS 'is_bidder_only_flag'
				FROM   
				freq_stereotypes c
					LEFT JOIN freq_site_tagging_link a ON a.tag_type_id =4 AND a.tag_id = c.freq_stereotypes_id
					LEFT JOIN freq_sites_main m ON a.freq_sites_main_id = m.freq_sites_main_id AND m.is_rejected_flag IS NULL  
				WHERE  ".$this->generate_sql_for_ids($id_final, ' c.freq_stereotypes_id IN ')." ORDER BY tag, url";
		}
		$bindings=array();
		$bindings=array_merge($bindings, $id_final);
		
		$query=$this->db->query($sql, $bindings);
		if ($query->num_rows()>0)
		{
			return $query->result_array();
		}
		return false;
	}
	public function get_mixed_tags_data_grid($id_array)
	{
		$context_id_final=array();
		$geo_id_final=array();
		$stereo_id_final=array();
		$context_ids="";
		$geo_ids="";
		$stereo_ids="";
		foreach ($id_array as $id)
		{
			$tag_type=substr($id, 0, 1);
			$tag_id=substr($id, 2);
			if ($tag_type=="1")
			{
				$context_id_final[]=$tag_id;
			}
			else if ($tag_type=="2")
			{
				$geo_id_final[]=$tag_id;
			}
			else if ($tag_type=="4")
			{
				$stereo_id_final[]=$tag_id;
			}
		}
		
		$sql="
		SELECT
			CONCAT('0>', m.freq_sites_main_id) AS site_id
		FROM
			freq_sites_main m
		WHERE 
			 m.is_rejected_flag IS NULL  ";
		if (count($context_id_final)>0)
		{
			$context_ids=implode(",", $context_id_final);
			$sql.="
			AND m.freq_sites_main_id IN 
				(
					SELECT
						a.freq_sites_main_id
					FROM
						freq_site_tagging_link a 
					WHERE
						a.tag_type_id = 1 
					AND 
						a.tag_id IN (".$context_ids.")
				)		
			";
		}
		
		if (count($geo_id_final)>0)
		{
			$geo_ids=implode(",", $geo_id_final);
			$sql.="
			AND m.freq_sites_main_id IN
				(
					SELECT
						a.freq_sites_main_id
					FROM
						freq_site_tagging_link a
					WHERE
						a.tag_type_id = 2
					AND
						a.tag_id IN (".$geo_ids.")
				)
			";
		}
		
		if (count($stereo_id_final)>0)
		{
			$stereo_ids=implode(",", $stereo_id_final);
			$sql.="
			AND m.freq_sites_main_id IN
				(
					SELECT
						a.freq_sites_main_id
					FROM
						freq_site_tagging_link a
					WHERE
						a.tag_type_id = 4
					AND
						a.tag_id IN (".$stereo_ids.")
				)
			";
		}
		
		$query=$this->db->query($sql);
		if ($query->num_rows()>0)
		{
			$site_ids=array();
			$data=$query->result_array();
			foreach ($data as $rows)
			{
				$site_ids[]=$rows['site_id'];
			}
			return $this->get_site_data_grid($site_ids);
		}
	}
	private function generate_sql_for_ids($ids, $where_field_in_sql=null)
	{
		$sql='';
		
		$num_ids=count($ids);
		
		if ($num_ids>0)
		{
			$ids_sql='(?';
			
			for ($ii=1; $ii<$num_ids; $ii++)
			{
				$ids_sql.=',?';
			}
			
			$ids_sql.=')';
			
			$table_where_sql=$where_field_in_sql;
			$sql=$table_where_sql.$ids_sql;
		}
		
		return $sql;
	}
	public function save_site_data_grid($tag_data, $site_id, $tag_type)
	{
		$tag_data=json_decode($tag_data, true);
		$return_string="";
		$sql="
		SELECT
			tag_id AS id
		FROM
			freq_site_tagging_link a
		WHERE
			tag_type_id = ?
		AND
			freq_sites_main_id = ?";
		$query=$this->db->query($sql, array(
				$tag_type,
				$site_id
		));
		$data=$query->result_array();
		$client_tag_ids=array();
		foreach ($tag_data as $row)
		{
			$client_tag_ids[]=$row['id'];
		}
		
		$server_tag_ids=array();
		foreach ($data as $row)
		{
			$server_tag_ids[]=$row['id'];
		}
		
		$result_delete=array_diff($server_tag_ids, $client_tag_ids);
		if (count($result_delete)>0) // delete these records from link table
		{
			foreach ($result_delete as $tag_id)
			{
				$sql="DELETE FROM freq_site_tagging_link WHERE tag_type_id = ? AND freq_sites_main_id = ? AND tag_id=?";
				$query=$this->db->query($sql, array(
						$tag_type,
						$site_id,
						$tag_id
				));
			}
		}
		
		$result_add=array_diff($client_tag_ids, $server_tag_ids);
		
		if (count($result_add)>0) // add these records from link table
		{
			foreach ($result_add as $tag_id)
			{
				$return_string=$this->is_tagging_allowed_flag($tag_type, $site_id, $tag_id);
				if ($tag_type!=1||(strpos($return_string, "#101-")===false)) // this method deletes the records if they are up the tree and returns true. it returns false if they are down the tree
				{
					$sql="INSERT INTO freq_site_tagging_link (tag_type_id, freq_sites_main_id, tag_id, is_proposal_worthy_flag, date_added) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)";
					$query=$this->db->query($sql, array(
							$tag_type,
							$site_id,
							$tag_id
					));
				}
			}
		}
		return $return_string;
	}
	
	public function save_tag_data_grid($tag_data, $tag_id, $tag_type, $bidder_flag=null)
	{
		$bidder_sql=" AND is_bidder_only_flag IS NULL ";
		if ($bidder_flag == 1)
		{
			$bidder_sql = " AND is_bidder_only_flag=1 ";
		}

		$return_string="";
		$tag_data=json_decode($tag_data, true);
		$sql="SELECT
			DISTINCT a.freq_sites_main_id AS id
		FROM
			freq_site_tagging_link a,
			freq_sites_main b
		WHERE
			a.tag_type_id = ? AND 
			a.freq_sites_main_id=b.freq_sites_main_id 
			$bidder_sql
		AND
			a.tag_id = ?";
		$query=$this->db->query($sql, array(
				$tag_type,
				$tag_id
		));
		$data=$query->result_array();
		$server_site_ids=array();
		foreach ($data as $row)
		{
			$server_site_ids[]=$row['id'];
		}
		
		$client_site_ids=array();
		$client_site_urls_new=array();
		foreach ($tag_data as $row)
		{
			if ($row['id']>0)
				$client_site_ids[]=$row['id'];
			else if ($row['id']<0)
				$client_site_urls_new[]=$row['text'];
		}
		
		$found_urls_array=array();
		$not_found_array=array();
		
		if (count($client_site_urls_new)>0)
		{
			$sql="SELECT 
				freq_sites_main_id AS id,
				url
			FROM
				freq_sites_main a
			WHERE
				".$this->generate_sql_for_ids($client_site_urls_new, ' url IN ');
			$query=$this->db->query($sql, $client_site_urls_new);
			$data=$query->result_array();
			foreach ($data as $row)
			{
				$client_site_ids[]=$row['id'];
				$found_urls_array[]=$row['url'];
			}
			$not_found_array=array_diff($client_site_urls_new, $found_urls_array);
		}
		
		$result_delete=array_diff($server_site_ids, $client_site_ids);
		if (count($result_delete)>0) // delete these records from link table
		{
			foreach ($result_delete as $site_id)
			{
				$sql="DELETE FROM freq_site_tagging_link WHERE tag_type_id = ? AND freq_sites_main_id = ? AND tag_id=? ;";
				$query=$this->db->query($sql, array(
					$tag_type,
					$site_id,
					$tag_id
				));
			}
		}
		$result_add=array_diff($client_site_ids, $server_site_ids);
		if (count($result_add)>0) // add these records from link table
		{
			foreach ($result_add as $site_id)
			{
				$return_string=$this->is_tagging_allowed_flag($tag_type, $site_id, $tag_id);
				if ($tag_type!=1||(strpos($return_string, "#101-")===false)) // this method deletes the records if they are up the tree and returns true. it returns false if they are down the tree
				{
					$sql="INSERT IGNORE INTO freq_site_tagging_link (tag_type_id, freq_sites_main_id, tag_id, is_proposal_worthy_flag, date_added) values (?, ?, ?, 1, CURRENT_TIMESTAMP);";
					$query=$this->db->query($sql, array(
							$tag_type,
							$site_id,
							$tag_id
					));
				}
			}
		}
		
		$return_data=array();
		$return_data['message']=$return_string;
		// step 4: fetch all sites for the given tag and return. we need the siteid and the url for the given tagid and tag type
		$query_select="SELECT
			a.freq_sites_main_id AS id,
			a.url AS text
		FROM
			freq_sites_main a,
			freq_site_tagging_link b
		WHERE a.freq_sites_main_id = b.freq_sites_main_id AND
			b.tag_id=? AND
			b.tag_type_id=?
			$bidder_sql
		ORDER BY a.url";
		
		$query_select_result=$this->db->query($query_select, array(
				$tag_id,
				$tag_type
		));
		if (count($query_select_result->result_array())>0) // add these records from link table
		{
			$return_data['options']=$query_select_result->result_array();
		}
		
		$return_data['not_found']=$not_found_array;
		return $return_data;
	}
	
	public function get_todo_items_for_bar()
	{
		$sql="SELECT 
					NAME,
					sql_query_for_completed_count, 
					sql_query_for_remaining_count 
				FROM
				 	freq_site_admin_todo
				WHERE 
					is_completed_flag=0";
		
		$query=$this->db->query($sql);
		
		if ($query->num_rows()>0)
		{
			$return_array=array();
			$ctr=0;
			foreach ($query->result_array() as $row)
			{
				$return_array[$ctr][0]=$row['NAME']; // 0 = name
				$sql_query_for_completed_count=$row['sql_query_for_completed_count'];
				$sql_query_for_remaining_count=$row['sql_query_for_remaining_count'];
				
				$query_completed_count=$this->db->query($sql_query_for_completed_count);
				if ($query_completed_count->num_rows()>0)
				{
					foreach ($query_completed_count->result_array() as $row)
					{
						$return_array[$ctr][1]=$row['count']; // 1 = completed
						break;
					}
				}
				$query_remaining_count=$this->db->query($sql_query_for_remaining_count);
				if ($query_remaining_count->num_rows()>0)
				{
					foreach ($query_remaining_count->result_array() as $row)
					{
						$return_array[$ctr][2]=$row['count']; // 2- remaining
						break;
					}
				}
				$ctr++;
			}
			return $return_array;
		}
		return false;
	}
	public function pull_todo_sitelist($name)
	{
		$sql="SELECT
					sql_query_for_data as query
				FROM
				 	freq_site_admin_todo
				WHERE
					name=?";
		
		$query=$this->db->query($sql, $name);
		
		if ($query->num_rows()>0)
		{
			$return_array=array();
			$ctr=0;
			foreach ($query->result_array() as $row)
			{
				$query_todo=$row['query']; // 0 = name
				$query_todo_data=$this->db->query($query_todo);
				if ($query_todo_data->num_rows()>0)
				{
					foreach ($query_todo_data->result_array() as $row)
					{
						$return_array[$ctr]['id']=$row['type'].">".$row['id'];
						$return_array[$ctr]['text']=$row['text'];
						// $return_array[$ctr]['type']=$row['type'];
						$ctr++;
					}
				}
				break;
			}
			return $return_array;
		}
		return false;
	}
	
	public function pull_site_data($type, $start, $limit, $search_text_original)
	{
		$search_text="";
		if ($search_text_original!=""&&strlen($search_text_original)>0)
			$search_text=" AND URL LIKE '%".$search_text_original."%' ";
		
		if ($type==0) // pending: fetch from freq_pending_sites where is_rejected_flag is null
		{
			$sql="
				SELECT url,
						date_added,
						CASE WHEN source=0 THEN 'SiteRecords' END AS source ,
						CASE WHEN is_retargeting_flag=1 AND is_non_retargeting_flag is null THEN 'Yes' ELSE '' END AS is_retargeting_flag,
						FORMAT(impressions,0) AS f_impressions,
						clicks,
						FORMAT(100*clicks/impressions,2) AS ctr
				FROM
					freq_pending_sites
				WHERE
					is_rejected_flag IS NULL ".$search_text."
				ORDER BY impressions DESC
				LIMIT ".$start.", ".$limit."";
			$query=$this->db->query($sql);
			
			if ($query->num_rows()>0)
			{
				return $query->result_array();
			}
		}
		else if ($type==1) // approved: fetch from freq_sites_main where is_rejected_flag is null
		{
			$sql="
				SELECT url,
						date_added,
						comscore_site_id,
						is_manually_added_flag
				FROM
					freq_sites_main
				WHERE
					is_rejected_flag IS NULL".$search_text."
				ORDER BY URL
				LIMIT ".$start.", ".$limit."";
			$query=$this->db->query($sql);
			
			if ($query->num_rows()>0)
			{
				return $query->result_array();
			}
		}
		else if ($type==2) // rejected: fetch from freq_pending_sites and freq_sites_main where is_rejected_flag is 1
		{
			$sql="SELECT 
					url,
					date_added,
					CASE WHEN is_blacklist =1 THEN 'Black'  ELSE ''  END AS black,
					CASE WHEN bad_site IS NOT NULL THEN 'Grey' ELSE '' END AS grey
				FROM
					freq_pending_sites
					LEFT JOIN td_uploader_blocklist ON bad_site = CONCAT(\"%\",url,\"%\")
				WHERE
					is_rejected_flag=1 ".$search_text."
				LIMIT ".$start.", ".$limit."
				UNION
				SELECT 
					bad_site,
					null,
					CASE WHEN is_blacklist =1 THEN 'Black'  ELSE '' END as black,
					CASE WHEN bad_site IS NOT NULL THEN 'Grey'  ELSE '' END as grey
				FROM
					td_uploader_blocklist WHERE bad_site LIKE '%".$search_text_original."%'
				UNION
				SELECT 
					url,
					date_added,
					CASE WHEN is_blacklist =1 THEN 'Black'  ELSE '' END AS black,
					CASE WHEN bad_site IS NOT NULL THEN 'Grey'  ELSE '' END AS grey
				FROM
					freq_sites_main
					LEFT JOIN td_uploader_blocklist ON bad_site = CONCAT(\"%\",url,\"%\")
				WHERE
					is_rejected_flag=1 ".$search_text."
				ORDER BY URL
				LIMIT ".$start.", ".$limit."";
			$query=$this->db->query($sql);
			if ($query->num_rows()>0)
			{
				return $query->result_array();
			}
		}
		else if ($type==3) // TTD only: fetch from freq_sites_main where is_rejected_flag is null and is_bidder_only_flag is not null
		{
			$sql="
				SELECT url,
						date_added,
						comscore_site_id,
						is_manually_added_flag
				FROM
					freq_sites_main
				WHERE
					is_bidder_only_flag IS NOT NULL AND
					is_rejected_flag IS NULL".$search_text."
				ORDER BY URL
				LIMIT ".$start.", ".$limit."";
			$query=$this->db->query($sql);
			
			if ($query->num_rows()>0)
			{
				return $query->result_array();
			}
		}
	}
	public function site_status_change($url, $status)
	{
		$url_array=explode(",", $url);
		// move the record to approved. Check if the record is in pending table.
		// delete the pending table record and insert a site record if it doesnt already exist
		foreach ($url_array as $url)
		{
			if ($status==1) // TO APPROVED
			{
				$sql="DELETE FROM freq_pending_sites WHERE url=?";
				$this->db->query($sql, $url);
				
				$query_main="
				INSERT INTO freq_sites_main
					(url, date_added, is_manually_added_flag)
				VALUES 
					(?, CURRENT_TIMESTAMP, 1)
				ON DUPLICATE KEY 
					UPDATE is_rejected_flag=NULL";
				$this->db->query($query_main, $url);
			}
			// check if the record is in pending table. if found mark the rejected flag
			// check if record is in sites table. if found mark the rejected flag
			else if ($status==2) // // TO REJECTED
			{
				$query_pending="
				UPDATE freq_pending_sites
					SET is_rejected_flag=1
				WHERE 		
					url=? AND 
					is_rejected_flag IS NULL";
				$this->db->query($query_pending, $url);
				
				$query_insert="
				UPDATE freq_sites_main
					SET is_rejected_flag=1
				WHERE 		
					url=? AND 
					is_rejected_flag IS NULL";
				$this->db->query($query_insert, $url);
			}
			else if ($status==3) // // SET TTD FLAG
			{
				$query_insert="
				UPDATE freq_sites_main
					SET is_bidder_only_flag=1
				WHERE 		
					url=? AND 
					is_bidder_only_flag IS NULL";
				$this->db->query($query_insert, $url);
			}
			else if ($status==4) // // Remove TTD flag
			{
				$query_insert="
				UPDATE freq_sites_main
					SET is_bidder_only_flag = null
				WHERE 		
					url=? AND 
					is_bidder_only_flag = 1";
				$this->db->query($query_insert, $url);
			}
			else if ($status==5) // // Add TTD flag in bulk.
			{
				$client_site_urls_new=explode("\n", $url);
				$query="
				UPDATE freq_sites_main
					SET is_bidder_only_flag = 1
				WHERE 		
					".$this->generate_sql_for_ids($client_site_urls_new, ' url IN ') ;
					 
				$this->db->query($query, $client_site_urls_new);
			}
		}
	}
	public function save_new_sites_bulk($sitenames, $tag_type, $tag_id)
	{
		$sitenames_array=explode("\n", $sitenames);
		$sitenames_array_new=array();
		// step 1: add new sites
		foreach ($sitenames_array as $site)
		{
			if (strlen($site)>3)
			{
				$query_main="
				INSERT INTO freq_sites_main
					(url, date_added, is_manually_added_flag)
				VALUES 
					(?, CURRENT_TIMESTAMP, 1)
				ON DUPLICATE KEY UPDATE 
					is_rejected_flag=NULL";
				$this->db->query($query_main, $site);
				$sitenames_array_new[]=$site;
			}
		}
		
		// step 2: find just added sites by url
		$query_select="
		SELECT 
			freq_sites_main_id 
		FROM
			freq_sites_main
		WHERE 
			".$this->generate_sql_for_ids($sitenames_array_new, ' url IN ');
		
		$query_select_result=$this->db->query($query_select, $sitenames_array_new);
		
		// step 3: tag these sites
		if (count($query_select_result->result_array())>0) // add these records from link table
		{
			foreach ($query_select_result->result_array() as $site_id_row)
			{
				$site_id=$site_id_row['freq_sites_main_id'];
				$sql="INSERT IGNORE INTO freq_site_tagging_link (tag_type_id, freq_sites_main_id, tag_id, is_proposal_worthy_flag, date_added) VALUES (?,?,?,1,CURRENT_TIMESTAMP);";
				$query=$this->db->query($sql, array(
						$tag_type,
						$site_id,
						$tag_id
				));
			}
		}
		
		// step 4: fetch all sites for the given tag and return. we need the siteid and the url for the given tagid and tag type
		$query_select="
		SELECT
			a.freq_sites_main_id AS id, 
			a.url AS text
		FROM
			freq_sites_main a, 
			freq_site_tagging_link b
		WHERE a.freq_sites_main_id = b.freq_sites_main_id AND 
			b.tag_id=? AND 
			b.tag_type_id=?";
		
		$query_select_result=$this->db->query($query_select, array(
			$tag_id,
			$tag_type
		));
		if (count($query_select_result->result_array())>0) // add these records from link table
		{
			return $query_select_result->result_array();
		}
		return true;
	}
	public function site_records_to_pending_table($manual_date)
	{
		$query_sites="SELECT 
				base_site,
				isretargeting, 
				SUM(impressions) AS impr, 
				SUM(clicks) AS clicks 
		FROM 
				SiteRecords a, 
				AdGroups b 
		WHERE a.Date >= '".$manual_date."' AND 
				a.Date <= '".$manual_date."'
				AND a.adgroupid=b.id AND 
					(base_site LIKE '%com' OR 
						base_site LIKE '%net' OR 
						base_site LIKE '%.co.%'
					)  
		GROUP BY 
			base_site, 
			isretargeting 
		HAVING SUM(impressions) > 100";
		
		$sites_array=array();
		$sites_only_array=array();
		$ctr=0;
		$query_sites_result=$this->db->query($query_sites);
		if (count($query_sites_result->result_array())>0) // add these records from link table
		{
			foreach ($query_sites_result->result_array() as $site_id_row)
			{
				
				$site=$site_id_row['base_site'];
				$isretargeting=$site_id_row['isretargeting'];
				$sites_array[$ctr]['site']=$site_id_row['base_site'];
				$sites_array[$ctr]['impr']=$site_id_row['impr'];
				$sites_array[$ctr]['clicks']=$site_id_row['clicks'];
				
				if ($isretargeting==1)
				{
					$sites_array[$ctr]['isretargeting']=1;
					$sites_array[$ctr]['isnoretargeting']=-1;
				}
				else
				{
					$sites_array[$ctr]['isretargeting']=-1;
					$sites_array[$ctr]['isnoretargeting']=1;
				}
				
				$sites_only_array[$ctr]=$site;
				$ctr++;
			}
		}
		$sites_only_str=implode("','", $sites_only_array);
		$query_main="
				SELECT 
					url 
				FROM 
					freq_sites_main 
				WHERE 
					url IN ('".$sites_only_str."')";
		
		$query_main_result=$this->db->query($query_main);
		if (count($query_main_result->result_array())>0) // add these records from link table
		{
			foreach ($query_main_result->result_array() as $site_id_row)
			{
				$site=$site_id_row['url'];
				foreach ($sites_array as &$row)
				{
					if ($row['site']==$site)
					{
						$row['site']="-1";
					}
				}
			}
		}
		
		$query_pending="
				SELECT 	url,
						is_retargeting_flag,
						is_non_retargeting_flag 
				FROM 
						freq_pending_sites 
				WHERE 
						url IN ('".$sites_only_str."')";
		
		$query_pending_result=$this->db->query($query_pending);
		
		if (count($query_pending_result->result_array())>0) // add these records from link table
		{
			foreach ($query_pending_result->result_array() as $site_id_row)
			{
				$site=$site_id_row['url'];
				$is_retargeting_flag=$site_id_row['is_retargeting_flag'];
				$is_non_retargeting_flag=$site_id_row['is_non_retargeting_flag'];
				foreach ($sites_array as &$row)
				{
					$isretargeting=$row['isretargeting'];
					if ($isretargeting==1)
					{
						if ($row['site']==$site&&$is_retargeting_flag==1)
						{
							$row['site']="-1";
						}
					}
					else
					{
						if ($row['site']==$site&&$is_retargeting_flag==0)
						{
							$row['site']="-1";
						}
					}
				}
			}
		}
		
		$number_records=0;
		$query_size=10000;
		
		$query_start="INSERT INTO freq_pending_sites
			(date_added, source, url, impressions, clicks, is_retargeting_flag, is_non_retargeting_flag)
		VALUES ";
		
		$query_end=" ON DUPLICATE KEY UPDATE is_retargeting_flag=VALUES(is_retargeting_flag), is_non_retargeting_flag=VALUES(is_non_retargeting_flag) ;";
		
		$query_middle=array();
		$bindings=array();
		
		foreach ($sites_array as $row)
		{
			$site=$row['site'];
			if ($site=='-1')
				continue;
			
			$istargeting=$row['isretargeting'];
			$isnotargeting=$row['isnoretargeting'];
			
			$bindings[]=$row['site'];
			$bindings[]=$row['impr'];
			$bindings[]=$row['clicks'];
			
			if ($istargeting!="-1")
				$bindings[]=$istargeting;
			else
				$bindings[]=null;
			
			if ($isnotargeting!="-1")
				$bindings[]=$isnotargeting;
			else
				$bindings[]=null;
			
			$query_middle[]='(CURRENT_TIMESTAMP,0, ?,?,?,?,?)';
			
			if (count($query_middle)==$query_size)
			{
				$query=$query_start.implode(',', $query_middle).$query_end;
				if ($this->db->query($query, $bindings))
				{
					$number_records+=$query_size;
				}
				else
				{
					var_dump($this->db);
				}
				$query_middle=array();
				$bindings=array();
			}
		}
		
		if (count($query_middle)>0)
		{
			$query=$query_start.implode(',', $query_middle).$query_end;
			if ($this->db->query($query, $bindings))
			{
				$number_records+=count($query_middle);
			}
			else
			{
				var_dump($this->db);
			}
			$query_middle=array();
			$bindings=array();
		}
	}
	public function get_comscore_site_demo_data($url)
	{
		$sql="SELECT 
			male_all*100/total_audience AS 'male % of Total',
			female_all*100/total_audience AS 'female % of Total',
			`male_2_17`*100/male_all  AS 'Male under 18 %',
			`male_18_24`*100/male_all AS 'Male 18-24 %',
			`male_25_34`*100/male_all AS 'Male 25-34 %',
			`male_35_44`*100/male_all AS 'Male 35-44 %',
			`male_45_54`*100/male_all AS 'Male 45-54 %',
			`male_55_64`*100/male_all AS 'Male 55-64 %',
			`male_over65`*100/male_all AS 'Male 65+ %',
			`female_2_17`*100/female_all  AS 'Female under 18 %',
			`female_18_24`*100/female_all AS 'Female 18-24 %',
			`female_25_34`*100/female_all AS 'Female 25-34 %',
			`female_35_44`*100/female_all AS 'Female 35-44 %',
			`female_45_54`*100/female_all AS 'Female 45-54 %',
			`female_55_64`*100/female_all AS 'Female 55-64 %',
			`female_over65`*100/female_all AS 'Female 65+ %'
		FROM 
			comscore_demo_data a, 
			freq_sites_main b 
		WHERE 
			a.comscore_site_id=b.comscore_site_id AND
			a.comscore_geo_main_id=840 AND
			b.url=?
		";
		$query_select=$this->db->query($sql, $url);
		if (count($query_select->result_array())>0) // add these records from link table
		{
			return $query_select->result_array();
		}
		return true;
	}
	private function is_tagging_allowed_flag($tag_type, $site_id, $tag_id)
	{
		$return_string="";
		if ($tag_type!=1)
			return $return_string;
			// STEP 1: check if the site is tagged to any parent iab tag
		$sql_parent="
		SELECT h.ancestor_id AS ancestor_id, 
				f.freq_sites_main_id AS freq_sites_main_id,
				i.tag_friendly_name AS parent_name
		FROM   
			iab_heirarchy h, 
			freq_site_tagging_link f,
			iab_categories i			
		WHERE
		    h.descendant_id = ? AND 
		    h.path_length > 0 AND
		    h.ancestor_id = f.tag_id AND
		    f.tag_type_id=1 AND 
		    f.freq_sites_main_id=? AND
		    i.id=h.ancestor_id";
		
		$query_parent=$this->db->query($sql_parent, array(
			$tag_id,
			$site_id
		));
		
		// STEP 2: delete all parents tagged to this site
		if (count($query_parent->result_array())>0)
		{
			$return_string="Moving the tagging from ";
			foreach ($query_parent->result_array() as $row)
			{
				$ancestor_id=$row['ancestor_id'];
				$freq_sites_main_id=$row['freq_sites_main_id'];
				$parent_name=$row['parent_name'];
				$sql_delete="
						DELETE FROM 
							freq_site_tagging_link 
						WHERE 
							tag_type_id=1 AND freq_sites_main_id=? AND tag_id=?";
				$this->db->query($sql_delete, array(
						$freq_sites_main_id,
						$ancestor_id
				));
				$return_string.=$parent_name." ";
			}
			return $return_string;
		}
		
		// STEP 3: check if the site is tagged to any of the children of the passed tags
		$sql_child="
		SELECT h.descendant_id AS descendant_id,
				f.freq_sites_main_id AS freq_sites_main_id,
				i.tag_copy AS child_name
		FROM
			iab_heirarchy h, 
			freq_site_tagging_link f,
			iab_categories i				
		WHERE
		    h.ancestor_id = ? AND
		    h.path_length > 0 AND
		    h.descendant_id = f.tag_id AND
		    f.tag_type_id=1 AND
			i.id=h.descendant_id AND
		    f.freq_sites_main_id=?";
		
		$query_child=$this->db->query($sql_child, array(
				$tag_id,
				$site_id
		));
		// if the site is already tagged to one of the children, dont do anything and return false
		if (count($query_child->result_array())>0)
		{
			$return_string="#101-Cannot tag the site to parent. Site already tagged to child IAB tag(s): ";
			foreach ($query_child->result_array() as $row)
			{
				$child_name=$row['child_name'];
				$return_string.=$child_name."; ";
			}
			return $return_string;
		}
		
		// STEP 4: if we have reached here, that means, we have deleted the site tagged to all parent levels, or returned above
		// now is a good time to tag the site to the passed tag and return true
		return "";
	}

	public function pull_industry_data($id=0)
	{
			$sub_sql = "";
			if ($id > 0)
			{
				$sub_sql = "WHERE industry_id=".$id;
			}
			
			$sql="
				SELECT 
					i.freq_industry_tags_id AS industry_id, 
					i.name AS industry_name, 
					i.custom_name_f AS industry_custom_name_f, 
					DATE_FORMAT(i.created_date, '%m-%d-%Y') AS created_date, 
					DATE_FORMAT(i.updated_date, '%m-%d-%Y') AS updated_date, 
					u.username AS updated_username, 
					GROUP_CONCAT(CONCAT(t.tag_copy, ':', sites_tagged1.count_s , '--', t.id )) AS tags, 
					rfp.count_r AS rfp_tag_count,
					sites_tagged.count_s AS sites_count 
				FROM 
					freq_industry_tags i
				LEFT JOIN freq_industry_tags_iab_link l ON i.freq_industry_tags_id=l.freq_industry_tags_id
				LEFT JOIN iab_categories t ON t.id=l.iab_categories_id
				LEFT JOIN users u ON u.id=i.updated_by
				LEFT JOIN  
				(
					SELECT 
						industry_id, 
						count(*) count_r 
					FROM 
						mpq_sessions_and_submissions 
					WHERE 
						LOWER(advertiser_name) NOT LIKE LOWER('zz_test%') AND 
						industry_id IS NOT NULL AND 
						creation_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
					GROUP BY 
						industry_id 
				) AS rfp ON rfp.industry_id=i.freq_industry_tags_id
				LEFT JOIN 
				(
					SELECT   
					  	i.freq_industry_tags_id AS freq_industry_tags_id, 
					  	COUNT(s.freq_sites_main_id) AS count_s  
					FROM
						freq_industry_tags_iab_link i,  
						freq_site_tagging_link s, 
						freq_industry_tags t, 
						iab_categories c
					WHERE 
						s.tag_type_id=1 AND 
						s.tag_id=i.iab_categories_id AND 
						i.freq_industry_tags_id=t.freq_industry_tags_id AND
					 	c.id=i.iab_categories_id
					GROUP BY 
						i.freq_industry_tags_id
				) AS sites_tagged ON 
					sites_tagged.freq_industry_tags_id=i.freq_industry_tags_id
				LEFT JOIN 
				(
					SELECT   
					  	s.tag_id AS tag_id, 
					  	COUNT(s.freq_sites_main_id) AS count_s  
					FROM
						freq_site_tagging_link s, 
						iab_categories c
					WHERE 
						s.tag_type_id=1 AND 
						s.tag_id=c.id
					GROUP BY 
						s.tag_id
				) AS sites_tagged1 ON 
					sites_tagged1.tag_id=t.id	
				$sub_sql	
				GROUP BY i.freq_industry_tags_id
				ORDER BY 
					i.freq_industry_tags_id
				";
			$query=$this->db->query($sql);
			
			$return_data = $query->result_array();

			if ($id <= 0)
			{
				$return_data[]=array(
					'industry_id'=> '-9', 
					'industry_name'=> '',
					'industry_custom_name_f'=> '',
					'created_date'=> '',
					'updated_date'=> '',
					'updated_username'=> '',
					'tags'=> '',
					'rfp_tag_count'=> '',
					'sites_count'=> ''
						);
			}
			return $return_data;
	}

	public function save_industry_data($id, $industry_name, $industry_custom_name_f, $iab_tags, $user_id)
	{
		if ($industry_custom_name_f == "")
		{
			$industry_custom_name_f=null;
		}
		$sub_sql = "";
		$bindings = array($industry_name, $industry_name);

		if ($industry_custom_name_f != null && $industry_custom_name_f != "")
		{
			$sub_sql = "
				 OR 
				name=? OR 
				custom_name_f=?	
			";
			$bindings[] = $industry_custom_name_f;
			$bindings[] = $industry_custom_name_f;
		}

		$sql = "
		SELECT 
			COUNT(*) AS count
		FROM 
			freq_industry_tags 
		WHERE 
			(
				name=? OR 
				custom_name_f=? 
				$sub_sql
			) 
		";

		if ($id != null && $id != "")
		{
			$sql .= "
				AND
				freq_industry_tags_id != ?
			";
			$bindings[] = $id;
		}

		$query=$this->db->query($sql, $bindings);
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row) 
			{
				$count=$row['count'];
			}
			if ($count > 0)
			{
				return "Error#2100: Duplicate Industry Name";
			}
		}

		$new_id="";
		// 1. insert or update industry record
		if ($id < 0)
		{
			$sql = "
			SELECT 
				MAX(freq_industry_tags_id)+1 AS new_id 
			FROM 
				freq_industry_tags 
			";

			$query=$this->db->query($sql);
			$new_id="";
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row) 
				{
					$new_id=$row['new_id'];
				}
			}
			$id=$new_id;
			
			$sql = "
			INSERT INTO freq_industry_tags 
			(
				freq_industry_tags_id,
				name,
				custom_name_f,
				updated_by,
				created_date,
				updated_date
			)
			VALUES
			(
				?,?,?,?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
			)
			";

			$query=$this->db->query($sql, array($new_id, $industry_name, $industry_custom_name_f, $user_id));
		}
		else
		{
			$sql = "
			UPDATE freq_industry_tags 
			SET name=?,
				custom_name_f=?,
				updated_by=?,
				updated_date=CURRENT_TIMESTAMP
			WHERE 
				freq_industry_tags_id=?
			";

			$query=$this->db->query($sql, array($industry_name, $industry_custom_name_f, $user_id, $id));
		}

		//2. delete and insert iab to ind mapping
		$sql = "
			DELETE FROM freq_industry_tags_iab_link 
			WHERE freq_industry_tags_id=?
			";
		$query=$this->db->query($sql, $id);	
		
		$iab_tags=explode(",", $iab_tags);
		foreach ($iab_tags as $row) 
		{
			 $sql = "
			INSERT INTO freq_industry_tags_iab_link 
			(freq_industry_tags_id, iab_categories_id)
			VALUES (?, ?)
			";
			$query=$this->db->query($sql, array($id, $row));	
		}
		return $id;
	}

	public function headers_in_rfps_load($headers_in_rfps_days_back)
	{
		$sql = "
			SELECT 
				site_list
			FROM 
				prop_gen_prop_data a,
				mpq_sessions_and_submissions b
			WHERE 
				a.date_modified >= DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -$headers_in_rfps_days_back DAY) AND
				a.source_mpq=b.id AND
				lower(b.advertiser_name) not like lower('zz_test%') AND
				site_list IS NOT NULL
			";

			$query=$this->db->query($sql);
			$final_array=array();
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row) 
				{
					$site_list=$row['site_list'];
					$site_list_array=explode('header_tag","', $site_list);
					foreach ($site_list_array as $sub_row) 
					{	
						$header = strtok($sub_row, '"');
						if ($header != 'LOCAL MEDIA' && $header != '[[')
						{
							if (!array_key_exists($header, $final_array))
							{
								$final_array[$header]=0;
							}
							$final_array[$header]=$final_array[$header]+1;
						}
					}
				}
			}
			$final_return_array=array();
			$final_return_array['total_count']=$query->num_rows();
			$final_return_array['data']=$final_array;
			return $final_return_array;
	}
}



