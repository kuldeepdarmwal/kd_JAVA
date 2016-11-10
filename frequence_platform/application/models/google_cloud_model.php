<?php

require FCPATH . '/vendor/autoload.php';
require FCPATH . '/libraries/external/php/dfa-v1.1.6/google/apiclient/autoload.php';

class google_cloud_model extends CI_Model 
{
	private $client;
	private $service;
	
	public function __construct()
	{
		$this->load->database();
	}
	public function get_google_api_creds()	
	{
		$get_api_creds_query = " SELECT
										client_id,
										client_secret,
										redirect_uri,
										auth_token_json
									FROM
										report_dbm_uploader_data
									ORDER BY 
										id DESC 
									LIMIT 1";
		$get_api_creds_result = $this->db->query($get_api_creds_query);
		if($get_api_creds_result == false)
		{
			return false;
		}
		return $get_api_creds_result->row_array();
	}
	
	public function get_bucket_names()
	{
		$get_bucket_names_query = " SELECT
										siterecord_bucket_name AS site_bucket,
										cityrecord_bucket_name AS city_bucket,
										sizerecord_bucket_name AS size_bucket,
										creativerecord_bucket_name AS creative_bucket,
										videorecord_bucket_name AS video_bucket,
										trueview_bucket_name AS trueview_bucket
									FROM
										report_dbm_uploader_data
									ORDER BY 
										id DESC 
									LIMIT 1";
		$get_bucket_names_result = $this->db->query($get_bucket_names_query);
		if($get_bucket_names_result == false)
		{
			return false;
		}
		return $get_bucket_names_result->row_array();
	}
	
	//Set headers and gather refreshed access token using an older access token to access Google API scopes
	private function set_google_client_headers_with_scopes($scopes = array())
	{
		$this->client = new Google_Client();
		$this->client->setAccessType('offline');		
		$api_creds = $this->get_google_api_creds();
		if($api_creds == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to retrieve API data from the database";
			return $return_array;
		}
		$this->client->setClientId($api_creds['client_id']);
		$this->client->setClientSecret($api_creds['client_secret']);
		$this->client->setRedirectUri($api_creds['redirect_uri']);
		$this->client->setScopes($scopes);
		
		$decoded_token = json_decode($api_creds['auth_token_json']);
		$this->client->refreshToken($decoded_token->refresh_token);
		$refresher_token = $this->client->getAccessToken();
		$this->client->setAccessToken($refresher_token);
		
	}
	
	public function retrieve_reports_for_buckets_for_date($bucket_array,$destination_folders, $report_date)
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";

		$this->set_google_client_headers_with_scopes(array("https://www.googleapis.com/auth/devstorage.read_only",
															"https://www.googleapis.com/auth/cloud-platform",
															"https://www.googleapis.com/auth/devstorage.full_control"));	

		$this->service = new Google_Service_Storage($this->client);
		
		$did_get_site_file = $this->download_report_for_date_into_folder($report_date, $bucket_array['site_bucket'], $destination_folders['site_folder']);
		if($did_get_site_file['is_success'] == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $did_get_site_file['err_msg'];
			return $return_array;		
		}

		$did_get_city_file = $this->download_report_for_date_into_folder($report_date, $bucket_array['city_bucket'], $destination_folders['city_folder']);
		if($did_get_city_file['is_success'] == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $did_get_city_file['err_msg'];
			return $return_array;		
		}

		$did_get_size_file = $this->download_report_for_date_into_folder($report_date, $bucket_array['size_bucket'], $destination_folders['size_folder']);
		if($did_get_size_file['is_success'] == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $did_get_size_file['err_msg'];
			return $return_array;		
		}

		$did_get_creative_file = $this->download_report_for_date_into_folder($report_date, $bucket_array['creative_bucket'], $destination_folders['creative_folder']);
		if($did_get_creative_file['is_success'] == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $did_get_creative_file['err_msg'];
			return $return_array;		
		}		

		$did_get_video_file = $this->download_report_for_date_into_folder($report_date, $bucket_array['video_bucket'], $destination_folders['video_folder']);
		if($did_get_video_file['is_success'] == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $did_get_video_file['err_msg'];
			return $return_array;		
		}
		
		$did_get_trueview_file = $this->download_report_for_date_into_folder($report_date, $bucket_array['trueview_bucket'], $destination_folders['trueview_folder']);
		if($did_get_trueview_file['is_success'] == false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = $did_get_trueview_file['err_msg'];
			return $return_array;		
		}

		return $return_array;
	}
	
	private function download_report_for_date_into_folder($report_date, $bucket_name, $destination_path)
	{
		$return_array = array();
		$return_array['is_success'] = true;
		$return_array['err_msg'] = "";
		
		$bucket_details = $this->service->objects->listObjects($bucket_name);
		if($bucket_details == false || $bucket_details == null)
		{
			$return_array['is_success'] = false;
			$return_arary['err_msg'] = "Failed to retrieve data for bucket ".$bucket_name;
			return $return_array;
		}
		$items = $bucket_details->getItems();
		$item_to_download = null;
		foreach($items as $item)
		{
			if(strpos($item['name'], $report_date))
			{
				if($item_to_download == null)
				{
					$item_to_download = $item;
				}
				else
				{
					if($item_to_download['generation'] < $item['generation'])
					{
						$item_to_download = $item;
					}
				}
			}
		}
		
		if($item_to_download == null)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to locate a report in ".$bucket_name." created on ".$report_date;
			return $return_array;
		}
		
		$file_data = $this->service->objects->get($bucket_name, $item_to_download['name']);
		$get_file_request = new Google_Http_Request($file_data->mediaLink);
		$response = $this->client->getAuth()->authenticatedRequest($get_file_request);
		$item_file_contents = $response->getResponseBody();
		$did_write_file = file_put_contents($destination_path."/".$item_to_download['name'], $item_file_contents);
		if($did_write_file === false)
		{
			$return_array['is_success'] = false;
			$return_array['err_msg'] = "Failed to write report file in ".$destination_path;
			return $return_array;
		}
		return $return_array;
	}


}
?>
