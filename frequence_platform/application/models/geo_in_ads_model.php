<?php

class geo_in_ads_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}
	
	public function get_messages_data_for_adset_version($adset_version_id)
	{
		if (!isset($adset_version_id))
		{
			return false;
		}
		
		$result = array();
		$geo_in_ads_zips_messages_join_query = "SELECT CONCAT(',',GROUP_CONCAT(zip_code),',') AS zips,message_id FROM geo_in_ads_zips_messages_join WHERE adset_version_id = ? GROUP BY message_id";
		$query = $this->db->query($geo_in_ads_zips_messages_join_query,$adset_version_id);
		
		if ($query->num_rows() > 0)
		{
			$result['zips_to_message_id_array'] = $query->result_array();
			$messages = $this->get_all_messages_for_adset_version_id($adset_version_id);
			
			if ($messages)
			{
				$result['message_id_to_message_array'] = $messages;
				return $result;
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
		
	
	public function get_zips_by_adset_version_id($adset_version_id)
	{
		if (!isset($adset_version_id))
		{
			return false;
		}
		
		$sql_query = "SELECT zip_code FROM geo_in_ads_zips_messages_join WHERE adset_version_id = ?";
		$query = $this->db->query($sql_query,$adset_version_id);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}
	
	public function check_if_records_exist_for_adset_version_id_message_id($adset_version_id, $message_id)
	{
		if (!isset($adset_version_id) || !isset($message_id))
		{
			return false;
		}
		
		$sql_query = "SELECT zip_code FROM geo_in_ads_zips_messages_join WHERE adset_version_id = ? AND message_id = ?";
		$binding_array = array($adset_version_id,$message_id);
		$query = $this->db->query($sql_query,$binding_array);
		
		if ($query->num_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	public function get_message_id_by_adset_version_id_and_zip_code($adset_version_id, $zip_code)
	{
		if (!isset($adset_version_id) || !isset($zip_code))
		{
			return false;
		}
		
		$binding_array = array($adset_version_id,$zip_code);
		
		$sql_query = "SELECT msgjoin.message_id 
				FROM geo_in_ads_zips_messages_join msgjoin 
				WHERE msgjoin.adset_version_id = ? AND msgjoin.zip_code = ?";
		
		$query = $this->db->query($sql_query,$binding_array);
		
		if ($query->num_rows() > 0)
		{
			$message_result = $query->row_array();
			return $message_result['message_id'];
		}
		else
		{
			return false;
		}
	}
		
	public function insert_message($adset_version_id, $zip_code, $message)
	{
		if (!isset($adset_version_id) || !isset($zip_code) || !isset($message))
		{
			return false;
		}
		
		$message_id = $this->get_geo_in_ads_message_id($adset_version_id, $message);
		
		if (!$message_id)
		{
			$message_id = $this->insert_geo_in_ads_message($message);
		}
		
		if ($message_id)
		{
			$insert_binding_array = array($adset_version_id,$zip_code,$message_id);
			$geo_in_ads_zips_messages_join_insert_query ="INSERT IGNORE INTO geo_in_ads_zips_messages_join (adset_version_id, zip_code, message_id) VALUES (?, ?, ?)";
			$this->db->query($geo_in_ads_zips_messages_join_insert_query,$insert_binding_array);

			if ($this->db->affected_rows() > 0)
			{
				return true;
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
	
	public function update_message($adset_version_id, $zip_code, $message, $old_msg_id)
	{
		if (!isset($adset_version_id) || !isset($zip_code) || !isset($message) || !isset($old_msg_id))
		{
			return false;
		}
		
		$new_message_id = $this->get_geo_in_ads_message_id($adset_version_id, $message);
		
		if (!$new_message_id)
		{
			$new_message_id = $this->insert_geo_in_ads_message($message);
		}
		
		if ($new_message_id)
		{
			$update_binding_array = array($new_message_id,$adset_version_id,$zip_code);
			$geo_in_ads_zips_messages_join_update_query ="UPDATE geo_in_ads_zips_messages_join SET message_id=? WHERE adset_version_id = ? AND zip_code = ?";
			$this->db->query($geo_in_ads_zips_messages_join_update_query, $this->db->escape($update_binding_array));

			if ($this->db->affected_rows() != -1)
			{
				//Delete the old message on adset_version_id and zip_code combination if that message is not in use by any other zip code for that adset version id.
				if (!$this->check_if_records_exist_for_adset_version_id_message_id($adset_version_id, $old_msg_id))
				{
					$this->delete_geo_from_ads_message($old_msg_id);
				}
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	public function delete_messages_for_adset_version_id($adset_version_id)
	{
		if (!isset($adset_version_id))
		{
			return false;
		}
		
		$geo_in_ads_messages_delete_query = "DELETE FROM geo_in_ads_messages WHERE message_id in (SELECT message_id FROM geo_in_ads_zips_messages_join WHERE adset_version_id= ?)";
		$this->db->query($geo_in_ads_messages_delete_query,$adset_version_id);
		
		if ($this->db->affected_rows() > 0)
		{
			$geo_in_ads_zips_messages_join_delete_query = "DELETE FROM geo_in_ads_zips_messages_join WHERE adset_version_id= ?";
			$this->db->query($geo_in_ads_zips_messages_join_delete_query,$adset_version_id);
			
			if ($this->db->affected_rows() > 0)
			{
				return true;
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
	
	private function get_all_messages_for_adset_version_id($adset_version_id)
	{
		if (!isset($adset_version_id))
		{
			return false;
		}
		
		$select_sql = "SELECT DISTINCT msg.message_id,msg.message
				FROM geo_in_ads_messages msg 
				INNER JOIN geo_in_ads_zips_messages_join msgjoin 
				ON msg.message_id = msgjoin.message_id 
				WHERE msgjoin.adset_version_id = ? ";
		
		$query = $this->db->query($select_sql,$adset_version_id);
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return false;
		}
	}

	private function get_geo_in_ads_message_id($adset_version_id, $message)
	{
		if (!isset($adset_version_id) || !isset($message))
		{
			return false;
		}
		
		$select_query = "SELECT msg.message_id AS message_id
				FROM geo_in_ads_messages msg 
				INNER JOIN geo_in_ads_zips_messages_join msgjoin 
				ON msg.message_id = msgjoin.message_id 
				WHERE msgjoin.adset_version_id = ? and msg.message= ?";
		
		$select_binding_array = array($adset_version_id,$message);
		$query = $this->db->query($select_query,$select_binding_array);
		
		if ($query->num_rows() > 0)
		{
			$message_result = $query->row_array();
			return $message_result['message_id'];
		}
		else
		{
			return false;
		}
	}

	private function insert_geo_in_ads_message($message)
	{
		if (!isset($message))
		{
			return false;
		}
		
		$msg_insert_query ="INSERT IGNORE INTO geo_in_ads_messages (message_id, message) VALUES (0,?)";
		$this->db->query($msg_insert_query,$message);
		$message_id = mysql_insert_id();
		
		if (isset($message_id))
		{
			return $message_id;
		}
		else
		{
			return false;
		}
	}
	
	private function delete_geo_from_ads_message($message_id)
	{
		if (!isset($message_id))
		{
			return false;
		}
		
		$msg_insert_query ="DELETE FROM geo_in_ads_messages WHERE message_id = ?";
		$this->db->query($msg_insert_query,$message_id);

		if ($this->db->affected_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}