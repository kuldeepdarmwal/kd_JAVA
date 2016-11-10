<?php
class Campaign_tags_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();                
	}

	public function get_tag_data_for_campaigns($campaign_ids)
	{
		$binding_sql = "";
		foreach($campaign_ids as $id)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "?";
		}
		
		$query = "
			SELECT
				ct.id AS tag_id,
				ct.name AS tag_name,
				ca.id AS campaign_id,
				ca.Name AS campaign_name,
				ad.Name AS advertiser_name,
				CONCAT(us.firstname, ' ', us.lastname) AS sales_person,
				pd.partner_name AS partner_name
				
			FROM
				ct_campaign_tags AS ct
				RIGHT JOIN ct_campaign_tags_join_campaigns AS cj
					ON ct.id = cj.ct_campaign_tags_id
				RIGHT JOIN Campaigns AS ca
					ON cj.campaign_id = ca.id
				JOIN Advertisers AS ad
					ON ca.business_id = ad.id
				JOIN users AS us
					ON ad.sales_person = us.id
				JOIN wl_partner_details AS pd
					ON us.partner_id = pd.id
				
			WHERE
				ca.id IN (".$binding_sql.")";
		$result = $this->db->query($query, $campaign_ids);
		if($result !== false and $result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}
	
	public function get_campaign_tags_by_search_term($search_term, $start, $limit)
	{
		$query = "
			SELECT 
				* 
			FROM 
				ct_campaign_tags 
			WHERE 
				name LIKE ? 
			LIMIT ?, ?";
		$result = $this->db->query($query, array($search_term, $start, $limit));
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}

	public function does_campaign_tag_with_text_exist($search_term)
	{
		$query = "
			SELECT
				*
			FROM
				ct_campaign_tags
			WHERE
				name = ?";
		$result = $this->db->query($query, array($search_term));
		if($result->num_rows() >0)
		{
			return true;
		}
		return false;
		
			
	}
	
	public function insert_new_campaign_tag($tag_name, $user_id)
	{
		$query = "INSERT INTO ct_campaign_tags (name, last_updated, created_by) VALUES(?, NOW(), ?)";
		$result = $this->db->query($query, array($tag_name, $user_id));
		if($result !== false && $this->db->affected_rows() > 0)
		{
			return $this->db->insert_id();
		}
		return false;
	}
	
	public function insert_new_campaign_tag_join_campaign($tag_id, $campaign_array)
	{
		if(count($campaign_array) < 1)
		{
			return true;
		}
		$value_sql = "";
		$binding_array = array();
		foreach($campaign_array as $campaign_id)
		{
			if($value_sql !== "")
			{
				$value_sql .= ", ";
			}
			$value_sql .= "(?, ?)";
			$binding_array[] = $tag_id;
			$binding_array[] = $campaign_id;
		}
		$query = "INSERT IGNORE INTO ct_campaign_tags_join_campaigns (ct_campaign_tags_id, campaign_id) VALUES".$value_sql."";
		$result = $this->db->query($query, $binding_array);
		if($result !== false)
		{
			return true;
		}
		return false;
	}

	public function delete_from_campaign_tag_join_campaign($tag_id, $campaign_array)
	{
		if(count($campaign_array) < 1)
		{
			return true;
		}
		$campaign_id_sql = "";
		$binding_array = array($tag_id);
		foreach($campaign_array as $campaign_id)
		{
			if($campaign_id_sql !== "")
			{
				$campaign_id_sql .= ", ";
			}
			$campaign_id_sql .= "?";
			$binding_array[] = $campaign_id;
		}
		$query = "DELETE FROM ct_campaign_tags_join_campaigns WHERE ct_campaign_tags_id = ? AND campaign_id IN(".$campaign_id_sql.")";
		$result = $this->db->query($query, $binding_array);
		if($result !== false && $this->db->affected_rows() > 0)
		{
			return true;
		}
		return false;
	}

	public function get_common_tags_by_campaign_ids($campaign_array)
	{
		$binding_sql = "0";
		foreach($campaign_array as $campaign_id)
		{
			$binding_sql .= ", ?";
		}
		$binding_array = $campaign_array;
		$binding_array[] = count($campaign_array);
		$query = "
			SELECT
				ct.id AS id,
				ct.name AS text
			FROM
				ct_campaign_tags ct
				JOIN ct_campaign_tags_join_campaigns ctj
					ON ct.id = ctj.ct_campaign_tags_id
			WHERE
				ctj.campaign_id IN (".$binding_sql.")
			GROUP BY
				ct.id
			HAVING
				count(ct.id) = ?";
		$result = $this->db->query($query, $binding_array);
		if($result === false)
		{
			return false;
		}
		else if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}
	
	public function get_tag_utility_view_data()
	{
		$query = "
			SELECT
				ct.id AS tag_id,
				ct.name AS tag_name,
				SUM(IF(ca.ignore_for_healthcheck = 1, 1, 0)) ignored_campaigns,
				SUM(IF(ca.ignore_for_healthcheck = 0, 1, 0)) live_campaigns,
				COALESCE(COUNT(ctj.campaign_id), 0) AS num_campaigns,
				COALESCE(ct.last_updated, '0000-00-00 00:00:00') AS last_updated,
				us.username AS username
			FROM
				ct_campaign_tags ct
				LEFT JOIN ct_campaign_tags_join_campaigns ctj
					ON ct.id = ctj.ct_campaign_tags_id
				JOIN users us
					ON ct.created_by = us.id
				LEFT JOIN Campaigns ca
					ON ctj.campaign_id = ca.id
			WHERE 1
			GROUP BY
				ct.id";

		$result = $this->db->query($query);
		if($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		return array();
	}
	
	public function interpret_campaign_tags_string($tag_string)
	{
		$return_array = array('is_success' => true, 'errors' => "", 'campaign_tags_string' => "", 'campaign_tag_ids' => "");
		$tag_ids = array();
		$regex_success = preg_match_all('/\d+/', $tag_string, $matches);
			
		if($regex_success !== false and $regex_success > 0)
		{
			$tag_ids = $matches[0];
			$campaign_tag_ids = array();
			$binding_sql = "";
			foreach($tag_ids as $v)
			{
				$campaign_tag_ids[$v] = array('id' => $v, 'is_modified' => false);
				if($binding_sql !== "")
				{
					$binding_sql .= ", ";
				}
				$binding_sql .= "?";
			}
			$return_array['campaign_tag_ids'] = json_encode($campaign_tag_ids);
			$query = "
				SELECT
					id,
					name
				FROM
					ct_campaign_tags
				WHERE
					id IN(".$binding_sql.")";
			$response = $this->db->query($query, $tag_ids);
			if($response->num_rows() > 0 and $response->num_rows() === count(array_unique($tag_ids)))
			{
				if(count($tag_ids) <= 100)
				{
					$temp_result = $response->result_array();
					foreach($temp_result as $v)
					{
						$temp_string = $tag_string;
						$tag_string = preg_replace('/^('.$v["id"].')(?=[AO])|(?<=[\(DRT])('.$v["id"].')(?=[\)AO])|^'.$v["id"].'$|(?<=[DRT])('.$v["id"].')$/', " \"".$v['name']."\" ", $tag_string);
						if(is_null($tag_string) or $temp_string === $tag_string)
						{
							$return_array['is_success'] = false;
							$return_array['errors'] = "Error 457372: tag input malformed near tag id \'".$v['id']."\'.";
							break;
						}
					}
					$tag_string = preg_replace('/((?<=AND|OR|NOT)(?=[\(])|(?<=AND|OR)(?=NOT)|(?<=[\)])(?=AND|OR|NOT))/', ' ', $tag_string);
					$return_array['campaign_tags_string'] = $tag_string;
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = "Error 457362: campaign string too long to calculate";
				}
			}
			else
			{
				$return_array['is_success'] = false;
				$return_array['errors'] = "Error 457370: tag input contains tags that could not be found";
			}
		}
		else
		{
			$return_array['is_success'] = false;
			$return_array['errors'] = "Error 457371: No campaign ids found in tag argument";
		}
		return $return_array;
	}

	public function parse_campaign_tag_string($raw_string, &$sql_string)
	{
		$string = html_entity_decode($raw_string);
		$string_array = str_split($string);
		$i = 0;
		$binding_array = array();
		if($this->process_tag_string_to_sql($string_array, $sql_string, $binding_array, $i))
		{
			return $binding_array;
		}
		else
		{
			return array();
		}
	}

	//allowed operators: 'AND', 'OR', 'NOT', '(', ')'
	public function process_tag_string_to_sql($array, &$sql_string, &$binding_array, &$i, $inverse = false)
	{
		$is_success = true;
		$is_return = false;
		$inverse_operator = false;
		$sql_string .= "(";
		while($i < count($array) and $is_return === false and $is_success === true)
		{
			if(is_numeric($array[$i]))
			{
				$partial_id = $array[$i++];
				while($i < count($array) and is_numeric($array[$i]))
				{
					$partial_id .= $array[$i++];
				}
				$sql_string .= "
				cpn.id ";
				if($inverse or $inverse_operator)
				{
					$sql_string .= "NOT ";
					$inverse_operator = false;
				}
				$sql_string .= "IN (
					SELECT
						ctj.campaign_id
					FROM
						ct_campaign_tags_join_campaigns ctj
						JOIN ct_campaign_tags ct
							ON ct.id = ctj.ct_campaign_tags_id
					WHERE
						ct.id = ?) ";
				$binding_array[] = $partial_id;
			}
			else if($array[$i] === "(")
			{
				$i++;
				$is_success = $this->process_tag_string_to_sql($array, $sql_string, $binding_array, $i, ($inverse_operator or $inverse));
				$inverse_operator = false;
			}
			else if($array[$i] === ")")
			{
				$is_return = true;
				$i++;
			}
			else if($array[$i] === "N")
			{
				if($array[$i+1] === "O" and $array[$i+2] === "T" and ($array[$i+3] === "(" or is_numeric($array[$i+3])))
				{
					//NOT OPERATOR
					$inverse_operator = true; //apply negation to next operation
					$i += 3;
				}
				else
				{
					$is_success = false;
				}
			}
			else if($array[$i] === "O")
			{
				if($array[$i+1] === "R")
				{
					//OR OPERATOR
					$sql_string .= "OR ";
					$i += 2;
				}
				else
				{
					$is_success = false;
				}
			}
			else if($array[$i] === "A")
			{
				if($array[$i+1] === "N" and $array[$i+2] === "D")
				{
					//AND OPERATOR
					$sql_string .= "AND ";
					$i += 3;
				}
				else
				{
					$is_success = false;
				}
			}
			else
			{
				$is_success = false;
			}
		}
		$sql_string .= ")";
		return $is_success;
	}
	

}
?>