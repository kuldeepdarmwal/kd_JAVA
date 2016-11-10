<?php
class dbm_uploader extends CI_Controller
{
	private $raw_dbm_site_table = "dbm_raw_sites";
	private $raw_dbm_city_table = "dbm_raw_cities";
	private $raw_dbm_size_table = "dbm_raw_sizes";
	private $raw_dbm_creative_table = "dbm_raw_creatives";
	private $raw_dbm_video_table = "dbm_raw_videos";
	private $raw_dbm_trueview_table = "dbm_raw_trueview";

	
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('mailgun');
		$this->load->library('cli_data_processor_common');
		$this->load->model('dbm_uploader_model');
		$this->load->model('td_uploader_model');
		$this->load->model('google_cloud_model');
	}
	

	
	
	public function index()
	{
		$this->google_cloud_model->setup_client();
	}
	
	public function dbm_upload($manual_date = null, $save_me = null)
	{
		if($this->input->is_cli_request())
		{
			$did_error = false;
			try
			{
				$this->cli_data_processor_common->mark_script_execution_start_time();

				$message = array();
				$num_warnings = 0;
				
				if(!empty($manual_date))
				{
					$report_date = $manual_date;
				}
				else
				{
					$report_date = date("Y-m-d", strtotime('-1 day'));
				}
				$dbm_gather_report_date = date("Y-m-d", strtotime('+1 day', strtotime($report_date)));

				$rand_temp_folder = "dbmcsv_".rand(1, 10000000);
				
				$message['basic_info'] = array();
				$message['basic_info'][] = "----DBM Scheduled Report Uploader----";
				$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message();
				$message['basic_info'][] = "Date to process: ".$report_date;
				$message['basic_info'][] = "Uploaded File Directory: ".$rand_temp_folder;
				$message['basic_info'][] = "-----------------------------------------------";
				
				$folder_made = mkdir("/tmp/".$rand_temp_folder);
				if(!$folder_made)
				{
					throw(new Exception("Failed to create temp directory for report files"));
				}
				
				
				$siterecords_folder = "/tmp/".$rand_temp_folder."/sitereports";
				$cityrecords_folder = "/tmp/".$rand_temp_folder."/cityreports";
				$sizerecords_folder = "/tmp/".$rand_temp_folder."/sizereports";
				$creativerecords_folder = "/tmp/".$rand_temp_folder."/creativereports";
				$videorecords_folder = "/tmp/".$rand_temp_folder."/videoreports";
				$trueview_folder = "/tmp/".$rand_temp_folder."/trueviewreports";
				
				$siterecords_folder_made = mkdir($siterecords_folder);
				$cityrecords_folder_made = mkdir($cityrecords_folder);
				$sizerecords_folder_made = mkdir($sizerecords_folder);
				$creativerecords_folder_made = mkdir($creativerecords_folder);
				$videorecords_folder_made = mkdir($videorecords_folder);
				$trueview_folder_made = mkdir($trueview_folder);					
				if(!$siterecords_folder_made || !$cityrecords_folder_made || !$sizerecords_folder_made || !$creativerecords_folder_made || !$videorecords_folder_made || !$trueview_folder_made)
				{
					throw(new Exception("Failed to create a report subfolder"));
				}
				
				$bucket_name_array = $this->google_cloud_model->get_bucket_names();
				if($bucket_name_array == false)
				{
					throw(new Exception ("Failed to retrieve bucket names from database"));
				}
				
				$destination_folders_array = array(
					"site_folder"=>$siterecords_folder,
					"city_folder"=>$cityrecords_folder,
					"size_folder"=>$sizerecords_folder,
					"creative_folder"=>$creativerecords_folder,
					"video_folder"=>$videorecords_folder,
					"trueview_folder" => $trueview_folder
				);
				
				$get_gcloud_files = $this->google_cloud_model->retrieve_reports_for_buckets_for_date($bucket_name_array,$destination_folders_array, $dbm_gather_report_date);
				if($get_gcloud_files['is_success'] == false)
				{
					throw(new Exception($get_gcloud_files['err_msg']));
				}

				if($save_me == "save")
				{
					$this->raw_dbm_site_table .= "_".str_replace('-', '_', $report_date);
					$this->raw_dbm_city_table .= "_".str_replace('-', '_', $report_date);
					$this->raw_dbm_size_table .= "_".str_replace('-', '_', $report_date);
					$this->raw_dbm_creative_table .= "_".str_replace('-', '_', $report_date);
					$this->raw_dbm_video_table .= "_".str_replace('-', '_', $report_date);
					$this->raw_dbm_trueview_table .= "_".str_replace('-', '_', $report_date);
					if($this->dbm_uploader_model->create_raw_dbm_tables($this->raw_dbm_site_table, $this->raw_dbm_city_table, $this->raw_dbm_size_table, $this->raw_dbm_creative_table, $this->raw_dbm_video_table, $this->raw_dbm_trueview_table) == false)
					{
						throw(new Exception("Failed to create new raw dbm tables for saved operation"));
					}
				}
				else
				{
					$raw_tables = array(
						$this->raw_dbm_site_table,
						$this->raw_dbm_city_table,
						$this->raw_dbm_size_table,
						$this->raw_dbm_creative_table,
						$this->raw_dbm_video_table,
						$this->raw_dbm_trueview_table
					);
					if($this->dbm_uploader_model->clear_raw_dbm_tables($raw_tables) == false)
					{
						throw(new Exception("Failed to clear raw dbm data tables"));
					}
				}
				
				//Upload files to raw data table
				$site_data = $this->dbm_uploader_model->translate_report_data_directory_to_data_arrays($siterecords_folder);
				$city_data = $this->dbm_uploader_model->translate_report_data_directory_to_data_arrays($cityrecords_folder);
				$size_data = $this->dbm_uploader_model->translate_report_data_directory_to_data_arrays($sizerecords_folder);
				$creative_data = $this->dbm_uploader_model->translate_report_data_directory_to_data_arrays($creativerecords_folder);
				$video_data = $this->dbm_uploader_model->translate_report_data_directory_to_data_arrays($videorecords_folder);
				$trueview_data = $this->dbm_uploader_model->translate_report_data_directory_to_data_arrays($trueview_folder);

				if($city_data == false || $site_data == false || $size_data == false || $creative_data == false || $trueview_data == false)
				{
					throw(new Exception("Failed to compile DCM reporting data for raw data upload"));
				}				
				
				$message['raw_data'] = array();
				$raw_site_rows = $this->dbm_uploader_model->add_csv_rows_for_report_type_to_raw_for_date($site_data, "site", $report_date, $this->raw_dbm_site_table);
				$raw_city_rows = $this->dbm_uploader_model->add_csv_rows_for_report_type_to_raw_for_date($city_data, "geo", $report_date, $this->raw_dbm_city_table);
				$raw_size_rows = $this->dbm_uploader_model->add_csv_rows_for_report_type_to_raw_for_date($size_data, "size", $report_date, $this->raw_dbm_size_table);
				$raw_creative_rows = $this->dbm_uploader_model->add_csv_rows_for_report_type_to_raw_for_date($creative_data, "creative", $report_date, $this->raw_dbm_creative_table);
				$raw_video_rows = $this->dbm_uploader_model->add_csv_rows_for_report_type_to_raw_for_date($video_data, "video", $report_date, $this->raw_dbm_video_table);
				$raw_trueview_rows = $this->dbm_uploader_model->add_csv_rows_for_report_type_to_raw_for_date($trueview_data, "trueview", $report_date, $this->raw_dbm_trueview_table);				
				$raw_data_counts = $this->dbm_uploader_model->gather_impression_and_click_counts_for_tables($this->raw_dbm_site_table, $this->raw_dbm_city_table, $this->raw_dbm_size_table, $this->raw_dbm_creative_table, $this->raw_dbm_video_table);
				$message['raw_data'][] = "<u>RAW DATA</u>";
				$message['raw_data'][] = "Number of raw site rows: ".number_format($raw_site_rows);
				$message['raw_data'][] = "Number of raw city rows: ".number_format($raw_city_rows);
				$message['raw_data'][] = "Number of raw size rows: ".number_format($raw_size_rows);
				$message['raw_data'][] = "Number of raw creative rows: ".number_format($raw_creative_rows);
				$message['raw_data'][] = "Number of raw video rows: ".number_format($raw_video_rows)." ({$raw_data_counts['video_data']['starts']}) starts";
				$message['raw_data'][] = "Trueview Data Rows: ".number_format($raw_trueview_rows);				
				$raw_impression_line = "Raw Site/City/Size/Creative Impressions: ";
				$raw_impression_counts = "(".number_format($raw_data_counts['site_data']['imps'])."/".number_format($raw_data_counts['city_data']['imps'])."/".number_format($raw_data_counts['size_data']['imps'])."/".number_format($raw_data_counts['creative_data']['imps']).")";
				$okay_msg = "";
				if(!($raw_data_counts['site_data']['imps'] == $raw_data_counts['city_data']['imps'] && $raw_data_counts['city_data']['imps'] == $raw_data_counts['size_data']['imps'] && $raw_data_counts['size_data']['imps'] == $raw_data_counts['creative_data']['imps']))
				{
					$okay_msg = "<strong>NOT OK!</strong> ";
					$num_warnings++;
				}
				else
				{
					$okay_msg = "OK! ";
				}
				$message['raw_data'][] = $raw_impression_line.$okay_msg.$raw_impression_counts;
				
				$raw_click_line = "Raw Site/City/Size/Creative Clicks: ";
				$raw_click_counts = "(".number_format($raw_data_counts['site_data']['clks'])."/".number_format($raw_data_counts['city_data']['clks'])."/".number_format($raw_data_counts['size_data']['clks'])."/".number_format($raw_data_counts['creative_data']['clks']).")";
				if(!($raw_data_counts['site_data']['clks'] == $raw_data_counts['city_data']['clks'] && $raw_data_counts['city_data']['clks'] == $raw_data_counts['size_data']['clks'] && $raw_data_counts['size_data']['clks'] == $raw_data_counts['creative_data']['clks']))
				{
					$okay_msg = "<strong>NOT OK!</strong> ";
					$num_warnings++;
				}
				else
				{
					$okay_msg = "OK! ";
				}
				$message['raw_data'][] = $raw_click_line.$okay_msg.$raw_click_counts;


				$raw_viewthrough_line = "Raw Site/City Viewthroughs: ";
				$raw_viewthrough_counts = "(".number_format($raw_data_counts['site_data']['viewthroughs'])."/".number_format($raw_data_counts['city_data']['viewthroughs']).")";
				if(!($raw_data_counts['site_data']['viewthroughs'] == $raw_data_counts['city_data']['viewthroughs']))
				{
					$okay_msg = "<strong>NOT OK!</strong> ";
					$num_warnings++;
				}
				else
				{
					$okay_msg = "OK! ";
				}
				$message['raw_data'][] = $raw_viewthrough_line.$okay_msg.$raw_viewthrough_counts;

				//Blacklist
				$did_blacklist_cleanup = $this->dbm_uploader_model->scoop_blacklist_sites($this->raw_dbm_site_table);
				if($did_blacklist_cleanup == false)
				{
					throw(new Exception("Failed to scoop blocklist sites into All other sites"));
				}
				//Modify/cleanup
				
				$did_cleanup_site_urls = $this->dbm_uploader_model->post_process_raw_sites($this->raw_dbm_site_table);
				if($did_cleanup_site_urls == false)
				{
					throw(new Exception("Failed to rectify site-data URL issues"));
				}
				
				$did_cleanup_city_data = $this->dbm_uploader_model->post_process_raw_cities($this->raw_dbm_city_table);
				if($did_cleanup_city_data == false)
				{
					throw(new Exception("Failed to rectify city/region name issues"));
				}			
				

				$did_process_creative_data = $this->dbm_uploader_model->match_raw_creatives($this->raw_dbm_creative_table);
				if($did_process_creative_data == false)
				{
					throw(new Exception("Failed to match creatives to Frequence creative ids"));
				}
				
				//Trueview
				$fixed_trueview_sites = $this->dbm_uploader_model->convert_trueview_sites($this->raw_dbm_site_table, $this->raw_dbm_trueview_table);
				if($fixed_trueview_sites == false)
				{
					throw(new Exception("Failed to convert Trueview siterecords"));
				}

				$fixed_trueview_cities = $this->dbm_uploader_model->convert_trueview_cities($this->raw_dbm_city_table, $this->raw_dbm_trueview_table);
				if($fixed_trueview_cities == false)
				{
					throw(new Exception("Failed to convert Trueview cityrecords"));
				}				

				//Transfer to aggregate table
				$aggregate_to_reporting = $this->dbm_uploader_model->transfer_tables_to_aggregate_table($this->raw_dbm_site_table, $this->raw_dbm_city_table, $this->raw_dbm_size_table, $this->raw_dbm_creative_table, $this->raw_dbm_video_table);
				if($aggregate_to_reporting == false)
				{
					throw(new Exception("Failed to aggregate and transfer data to reporting tables"));
				}
				
				$aggregate_data_counts = $this->dbm_uploader_model->get_aggregate_counts_for_adgroups_for_date($report_date, dbm_uploader_model::trafficing_system_dbm);
				if($aggregate_data_counts == false)
				{
					throw(new Exception("Failed to gather aggregate data counts"));
				}
				
				$did_agg_mismatch = false;

				$message['agg_data'] = array();
				$message['agg_errs'] = array();
				$message['agg_data'][] = "<u>PROCESSED DATA</u>";
				$message['agg_data'][] = "Aggregate site rows inserted: ".number_format($aggregate_to_reporting['site_row_count']);
				$message['agg_data'][] = "Aggregate city rows inserted: ".number_format($aggregate_to_reporting['city_row_count']);
				$message['agg_data'][] = "Aggregate size rows inserted: ".number_format($aggregate_to_reporting['size_row_count']);
				$message['agg_data'][] = "Aggregate creative rows inserted: ".number_format($aggregate_to_reporting['creative_row_count']);


				if($aggregate_data_counts['video_data']['starts'] != $raw_data_counts['video_data']['starts'])
				{
					$num_warnings++;
					$did_agg_mismatch = true;
				}
				$message['agg_data'][] = "Aggregate video rows inserted: ".number_format($aggregate_to_reporting['video_row_count'])." ({$aggregate_data_counts['video_data']['starts']}) starts";

				$agg_impression_line = "Aggregate Site/City/Size/Creative Impressions: ";
				$agg_impression_counts = "(".number_format($aggregate_data_counts['site_data']['imps'])."/".number_format($aggregate_data_counts['city_data']['imps'])."/".number_format($aggregate_data_counts['size_data']['imps'])."/".number_format($aggregate_data_counts['creative_data']['imps']).")";
				if(!($aggregate_data_counts['site_data']['imps'] == $aggregate_data_counts['city_data']['imps'] && $aggregate_data_counts['city_data']['imps'] == $aggregate_data_counts['size_data']['imps'] && $aggregate_data_counts['size_data']['imps'] == $aggregate_data_counts['creative_data']['imps']))
				{
					$okay_msg = "<strong>NOT OK!</strong> ";
					$num_warnings++;
				}
				else
				{
					$okay_msg = "OK! ";
				}
				$message['agg_data'][] = $agg_impression_line.$okay_msg.$agg_impression_counts;
				
				$agg_click_line = "Aggregate Site/City/Size/Creative Clicks: ";
				$agg_click_counts = "(".number_format($aggregate_data_counts['site_data']['clks'])."/".number_format($aggregate_data_counts['city_data']['clks'])."/".number_format($aggregate_data_counts['size_data']['clks'])."/".number_format($aggregate_data_counts['creative_data']['clks']).")";
				if(!($aggregate_data_counts['site_data']['clks'] == $aggregate_data_counts['city_data']['clks'] && $aggregate_data_counts['city_data']['clks'] == $aggregate_data_counts['size_data']['clks'] && $aggregate_data_counts['size_data']['clks'] == $aggregate_data_counts['creative_data']['clks']))
				{
					$okay_msg = "<strong>NOT OK!</strong> ";
					$num_warnings++;
				}
				else
				{
					$okay_msg = "OK! ";
				}
				$message['agg_data'][] = $agg_click_line.$okay_msg.$agg_click_counts;
				
				$agg_viewthrough_line = "Aggregate Site/City Viewthroughs: ";
				$agg_viewthrough_counts = "(".number_format($aggregate_data_counts['site_data']['viewthroughs'])."/".number_format($aggregate_data_counts['city_data']['viewthroughs']).")";
				if(!($aggregate_data_counts['site_data']['viewthroughs'] == $aggregate_data_counts['city_data']['viewthroughs']))
				{
					$okay_msg = "<strong>NOT OK!</strong> ";
					$num_warnings++;
				}
				else
				{
					$okay_msg = "OK! ";
				}
				$message['agg_data'][] = $agg_viewthrough_line.$okay_msg.$agg_viewthrough_counts;

				
				if($aggregate_data_counts['site_data']['imps'] != $raw_data_counts['site_data']['imps'] 
					|| $aggregate_data_counts['city_data']['imps'] != $raw_data_counts['city_data']['imps']
					|| $aggregate_data_counts['size_data']['imps'] != $raw_data_counts['size_data']['imps'])
				{
					$message['agg_data'][] = "<strong>[WARNING] Raw-to-Aggregate Impression Mismatch</strong>";
					$num_warnings++;
					$did_agg_mismatch = true;
				}				

				if($aggregate_data_counts['site_data']['clks'] != $raw_data_counts['site_data']['clks'] 
					|| $aggregate_data_counts['city_data']['clks'] != $raw_data_counts['city_data']['clks']
					|| $aggregate_data_counts['size_data']['clks'] != $raw_data_counts['size_data']['clks'])
				{
					$message['agg_data'][] = "<strong>[WARNING] Raw-to-Aggregate Clicks Mismatch</strong>";
					$num_warnings++;
					$did_agg_mismatch = true;
				}					
				if($did_agg_mismatch)
				{
					$message['agg_data'][] = "<strong>[NOTICE] Raw-to-Aggregate mismatches might be due to DBM adgroup not added to a campaign.</strong>";
				}
				$report_cached_adgroup_ct = $this->td_uploader_model->load_report_cached_campaign('DATE_MODE', $report_date, '');
				if (!isset($report_cached_adgroup_ct))
				{
					$report_cached_adgroup_ct = "0";
				}
				
				$message['agg_data'][] = "Total Records loaded in report_cached_adgroup_date: " .$report_cached_adgroup_ct;
			}
			catch(Exception $e)
			{
				$msg_info = $message['basic_info'];
				$message = array();
				$message['basic_info'] = $msg_info;
				$message['errors'] = array();
				$message['errors'][] = "Encountered a fatal error: ".$e->getMessage();
				$did_error = true;
			}
			
			if($rand_temp_folder != null)
			{
				if($folder_made)
				{
				  $delete_reports = null;
				  system("rm -rf /tmp/".$rand_temp_folder, $delete_reports);
				  if(!($delete_reports === 0))
				  {
					  echo "Failed to delete temp directory /tmp/".$rand_temp_folder;
				  }

				}
			}
			
			$from = "DBM Data Uploader <noreply@frequence.com>";
			$to = "Tech Logs <tech.logs@frequence.com>";
			
			$subject = "DBM Uploader (".$report_date.") ";
			if($did_error)
			{
				$subject .= "Failed with a fatal error";
			}
			else
			{
				$subject .= "Completed successfully";
			}
			if($num_warnings > 0)
			{
				$subject .= " with ".$num_warnings." warnings";
			}
			
			$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
			$message = nl2br(implode("\n\n", $message));
			
			$this->send_email($from, $to, $subject, $message, 'html');
			
			$this->execute_impression_caching();
		}
		
	}

	private function flatten_message_array_for_email($arr)
	{
		return implode("\n", $arr);
	}
	
	private function send_email($from, $to, $subject, $message, $body_type = 'html')
	{
		$mail_result = mailgun(
					$from,
					$to,
					$subject,
					$message,
					$body_type
					);
		if($mail_result !== true)
		{
			echo "\nFailed to send mail!\n";
		}
	}
		
	private function execute_impression_caching()
	{
		$caching_response = shell_exec("php index.php campaigns_main cache_all_impression_amounts");
		
		$cache_from = "Frequence Daily Caching <noreply@frequence.com>";
		$cache_to = "Tech Logs <tech.logs@frequence.com>";
		$cache_subject = "Nightly Impression Caching (" . date("m/d", strtotime('-1 day')) . ")";
		$cache_message = $caching_response;
		
		$this->send_email($cache_from, $cache_to, $cache_subject, $cache_message, 'text');
	}	
}
?>
