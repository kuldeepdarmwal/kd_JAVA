<?php

class carmercial_model extends CI_Model 
{
	public function __construct()
	{
		$this->load->database();
		$this->load->helper(array(
			'spectrum_traffic_system_helper'
		));
	}

	//Function used to make CURL requests to the charterauto API
	//returns decoded data on success or false on a failure
	/*Possible request urls
		CURLOPT_URL => "http://charterauto.tv/API/listDealers",
		CURLOPT_URL => "http://charterauto.tv/API/redirects/dealer/breakawayhonda/start/2015-05-12/end/2015-05-12",
		CURLOPT_URL => "http://charterauto.tv/library/view/_family",
		CURLOPT_URL => "http://charterauto.tv/API/redirects/dealer",
	*/
	private function make_charterauto_request($url_query)
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['data'] = null;
		
		$curl_resource = curl_init();
		if($curl_resource === false)
		{
			return false;
		}
		$curl_options = array(
			CURLOPT_URL => "http://charterauto.tv/API/".$url_query,
			CURLOPT_USERPWD => $this->config->item('carmercial_username').":".$this->config->item('carmercial_pass'),
			CURLOPT_RETURNTRANSFER => 1
		);
		if(!curl_setopt_array($curl_resource, $curl_options))
		{
			return false;
		}
		
		$response = curl_exec($curl_resource);
		if(empty($response) || $response == "\n")
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Empty response during query ".$url_query;
			return $return_array;
		}
		curl_close($curl_resource);
		$curl_resource = null;

		$decoded_response = json_decode($response);
		if($decoded_response->code != 200)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Charterauto.tv request  failed with code ".$decoded_response->code."(".$decoded_response->message.") - Querying: ".$url_query;
			return $return_array;
		}
		$return_array['data'] = $decoded_response->data;
		
		return $return_array;
	}	

	public function get_charterauto_dealer_list()
	{
		return $this->make_charterauto_request("listDealers");
	}

	public function get_charterauto_report_data_for_dealer_for_dates($dealer_name, $start_date, $end_date)
	{
		$query_path = "redirects/dealer/".$dealer_name."/start/".$start_date."/end/".$end_date;
		return $this->make_charterauto_request($query_path);
	}

	public function update_dealer_list($dealer_list)
	{
		//Add dealers that aren't already in there.
		$dealer_values = array();
		$dealer_bindings = array();
		foreach($dealer_list as $dealer_name)
		{
			$dealer_values[] = "(?, ?, ?, ?, ?)";
			$dealer_bindings[] = $dealer_name->real;
			$dealer_bindings[] = $dealer_name->friendly;
			$dealer_bindings[] = $dealer_name->customerId;
			$dealer_bindings[] = $this->append_traffic_system_to_customer_id($dealer_name->region, $dealer_name->customerId);
			$dealer_bindings[] = $dealer_name->region;
		}
		$dealer_values = implode(", ", $dealer_values);

		$insert_dealers_query = "
			INSERT INTO 
				tp_cm_dealers
				(dealer_name, friendly_dealer_name, charterauto_customer_id, eclipse_id, traffic_system)
			VALUES ".$dealer_values."
			ON DUPLICATE KEY UPDATE
				friendly_dealer_name = VALUES(friendly_dealer_name),
				charterauto_customer_id = VALUES(charterauto_customer_id),
				eclipse_id = VALUES(eclipse_id),
				traffic_system = VALUES(traffic_system)";

		$insert_dealers_result = $this->db->query($insert_dealers_query, $dealer_bindings);
		if($insert_dealers_result == false)
		{
			return false;
		}
		return $this->db->affected_rows(); //Return # of new dealers found
	}

	public function upload_third_party_dealer_data_to_database_for_date($report_date)
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['rows_inserted'] = 0;
		$return_array['video_count_found'] = 0;
		$return_array['video_count_inserted'] = 0;
		$return_array['video_count_ignored'] = 0;

		$dealer_array = $this->get_dealer_ids_names();
		if($dealer_array === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to gather dealer data from database";
			return $return_array;
		}

		$upload_bindings = array();
		$insert_values = array();
		foreach($dealer_array as $dealer)
		{
			if($dealer['charterauto_customer_id'] == NULL)
			{
				continue;
			}
			$dealer_api_data_result = $this->get_charterauto_report_data_for_dealer_for_dates($dealer['charterauto_customer_id'], $report_date, $report_date);
			if($dealer_api_data_result['is_success'] == false)
			{
				$return_array['err_msg'] .= "[ERROR] Failure to retrieve data for dealer ".$dealer['dealer_name']." (".$dealer_api_data_result['err_msg'].")";
				continue;
			}
			$dealer_api_data = $dealer_api_data_result['data'];
			if(empty($dealer_api_data))
			{
				continue;
			}
			foreach($dealer_api_data as $key => $video_data)
			{
				if($video_data->type != "Dashboard")
				{
					$upload_bindings[] = $dealer['frq_id'];
					$upload_bindings[] = $video_data->_id;
					$upload_bindings[] = $report_date;
					$upload_bindings[] = $video_data->title;
					$upload_bindings[] = $video_data->count;
					$insert_values[] = "(?, ?, ?, ?, ?)";
				}
				else
				{
					$return_array['video_count_ignored'] += $video_data->count;
				}
				$return_array['video_count_found'] += $video_data->count;
			}
		}
		if(empty($upload_bindings))
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "No dealer data captured for date ".$report_date;
			return $return_array;			
		}
		$return_array['rows_inserted'] = count($insert_values);
		$insert_values = implode(', ', $insert_values);

		$insert_tp_carmercial_data = "
			INSERT INTO
				tp_cm_report_data
				(frq_dealer_id, video_id, date, video_name_string, count)
			VALUES ".$insert_values."
			ON DUPLICATE KEY UPDATE
				video_name_string = VALUES(video_name_string),
				count = VALUES(count)";

		$insert_tp_carmercial_result = $this->db->query($insert_tp_carmercial_data, $upload_bindings);
		if($insert_tp_carmercial_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to insert third-party data into database";
			return $return_array;			
		}

		$gather_final_count_query = "
			SELECT 
				SUM(count) AS total
			FROM 
			 	tp_cm_report_data
			WHERE
				date = ?";
		$get_final_count_result = $this->db->query($gather_final_count_query, $report_date);
		if($get_final_count_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to gather final totals after data insertion";
			return $return_array;					
		}
		$total_row = $get_final_count_result->row_array();
		$return_array['video_count_inserted'] = $total_row['total'];

		return $return_array;
	}

	private function get_dealer_ids_names()
	{
		$get_dealer_info_query = "SELECT
										*
									FROM
										tp_cm_dealers";
		$get_dealer_result = $this->db->query($get_dealer_info_query);
		if($get_dealer_result == false)
		{
			return false;
		}
		return $get_dealer_result->result_array();
	}

	public function refresh_carmerical_account_links()
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		$return_array['account_links_created'] = "";
		$return_array['unmatched_accounts'] = array();

		$delete_old_links_query = 
		"DELETE FROM
			tp_advertisers_join_third_party_account
		WHERE
			third_party_source = 2
		";
		$delete_old_links_result = $this->db->query($delete_old_links_query);
		if($delete_old_links_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to delete old Carmercial account links";
			return $return_array;
		}

		$create_new_links_query = 
		"INSERT INTO  
			tp_advertisers_join_third_party_account
			(frq_advertiser_id, frq_third_party_account_id, third_party_source)
		(SELECT
			tsa.advertiser_id AS advertiser_id,
			tcd.frq_id AS tp_account_id,
			2
		FROM 
			tp_cm_dealers AS tcd
			JOIN tp_spectrum_accounts AS tsa
			ON (tcd.eclipse_id = tsa.eclipse_id))
		";
		$create_new_links_result = $this->db->query($create_new_links_query);
		if($create_new_links_result == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to create new Carmecial account links";
			return $return_array;			
		}
		$return_array['account_links_created'] = $this->db->affected_rows();

		$get_unmatched_accounts_query = 
		"SELECT 
			tcd.*
		FROM 
			tp_cm_dealers AS tcd
			LEFT JOIN tp_advertisers_join_third_party_account AS tajtpa
				ON (tcd.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 2)
		WHERE
			tajtpa.frq_third_party_account_id IS NULL
		";

		$get_unmatched_accounts_result = $this->db->query($get_unmatched_accounts_query);
		if($get_unmatched_accounts_result == false)
		{
			$return_array['unmatched_accounts'] = -1;
		}
		else
		{
			$unmatched_accounts = $get_unmatched_accounts_result->result_array();
			foreach ($unmatched_accounts as $account)
			{
				$return_array['unmatched_accounts'][] = $account;
			}
		}
		return $return_array;
	}

	private function append_traffic_system_to_customer_id($traffic_system_string, $customer_id)
	{
		switch($traffic_system_string)
		{
			case spectrum_traffic_system_names_from_carmercial::central_pacific:
				$traffic_system_id = spectrum_traffic_system_ids::central_pacific;
				break;
			case spectrum_traffic_system_names_from_carmercial::mid_north:
				$traffic_system_id = spectrum_traffic_system_ids::mid_north;
				break;			
			case spectrum_traffic_system_names_from_carmercial::southeast:
				$traffic_system_id = spectrum_traffic_system_ids::southeast;
				break;
			default:
				$traffic_system_id = spectrum_traffic_system_ids::unknown;
				break;
		}
		return $traffic_system_id.$customer_id;
	}	
}
?>
