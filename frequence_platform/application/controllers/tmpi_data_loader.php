<?php

require_once FCPATH . '/vendor/autoload.php';
use OpenCloud\Rackspace;

class Tmpi_data_loader extends CI_Controller
{
	private $k_tmpi_zip_file_prefix = "Charter-";
	private $k_tmpi_zip_file_suffix = ".zip";
	private $k_base_working_directory = "tmpi_working_";
	private $k_cdn_pseudo_path = "tmpi/";

	private $k_last_run_info_file_name = '../log/latest_tmpi_data_load_info.json';

	private $m_working_directory = "";

	private $m_ftp_resource = null;
	private $m_ftp_file_list = null;

	private $m_cdn_container = null;
	private $m_cdn_object_list = null;

	private $info = array();
	private $warnings = array();
	private $errors = array();

	public function __construct()
	{
		parent::__construct();

		$this->load->library(array(
			'session',
			'cli_data_processor_common'
		));
		$this->load->model('tmpi_model');
		$this->load->helper( array(
			'url',
			'mailgun'
		));

		$this->m_working_directory = '/tmp/'.$this->k_base_working_directory.date("Y-m-d_H-i-s");
	}

	private function get_cdn_container()
	{
		if($this->m_cdn_container === null)
		{
			$rackspace_cloud_files_api_credentials = $this->config->item('rackspace_cloud_files_api');
			$rackspace = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
				'username' => $rackspace_cloud_files_api_credentials['rackspace_user'],
				'apiKey' => $rackspace_cloud_files_api_credentials['rackspace_api_key'],
			));

			$region = 'DFW';
			$object_store_service = $rackspace->objectStoreService(null, $region);
			$container_name = 'data_files';
			$cdn_container = $object_store_service->getContainer($container_name);
			if(!empty($cdn_container))
			{
				$this->m_cdn_container = $cdn_container;
			}
			else
			{
				throw new Exception("Failed to get CDN container");
			}
		}

		return $this->m_cdn_container;
	}

	private function get_cdn_object_list(
		$filter = '',
		$start_marker = '',
		$end_marker = '',
		$should_reload = false
	)
	{
		if($this->m_cdn_object_list === null || $should_reload)
		{
			$container = $this->get_cdn_container();
			$object_list_filter = array();

			if(!empty($start_marker))
			{
				$object_list_filter['marker'] = $this->k_cdn_pseudo_path.$this->k_tmpi_zip_file_prefix . $start_marker;
			}

			if(!empty($end_marker))
			{
				$object_list_filter['end_marker'] = $this->k_cdn_pseudo_path.$this->k_tmpi_zip_file_prefix . $end_marker;
			}

			if(!empty($filter))
			{
				$object_list_filter['prefix'] = $this->k_cdn_pseudo_path.$this->k_tmpi_zip_file_prefix . $filter;
			}

			$this->m_cdn_object_list = $container->objectList($object_list_filter);
		}

		return $this->m_cdn_object_list;
	}

	private function download_file_from_cdn($working_directory, $file_name)
	{
		$container = $this->get_cdn_container();
		$this->validate_and_create_working_directory($working_directory);
		try {
			$data_object = $container->getObject($this->k_cdn_pseudo_path.$file_name);
		}
		catch (Exception $exception)
		{
			return false;
		}

		$object_content = $data_object->getContent();
		$object_content->rewind();
		$stream = $object_content->getStream();

		file_put_contents($working_directory."/".$file_name, $stream);

		return true;
	}

	private function upload_file_to_cdn($working_directory, $file_name)
	{
		$container = $this->get_cdn_container();
		$file_resource = fopen($working_directory."/".$file_name, "r");
		$data_object = $container->uploadObject($this->k_cdn_pseudo_path.$file_name, $file_resource);
		if(empty($data_object))
		{
			throw new Exception("Failed to upload '$file_name' to CDN.");
		}
	}

	private function upload_new_files_from_tmpi_ftp_to_cdn()
	{
		$files_in_last_day = array();

		$ftp_file_list = $this->get_ftp_file_list();

		$pdt_time_zone = new DateTimeZone('PDT');
		$now = new DateTime("now", $pdt_time_zone);
		foreach($ftp_file_list as $file_line)
		{
			$match_non_file_name = "[[:blank:]]?".str_repeat("([^[:blank:]]+)[[:blank:]]+", 3);
			$match_file_name = "(.+\.zip)";
			$matches = array();
			if(preg_match("/".$match_non_file_name.$match_file_name."/", $file_line, $matches))
			{
				$date_raw = $matches[1];
				$time = $matches[2];
				$size = $matches[3];
				$file_name = $matches[4];

				$date_pieces = explode('-', $date_raw);
				$month = $date_pieces[0];
				$day = $date_pieces[1];
				$year = $date_pieces[2];

				$file_modification_time = new DateTime("$year-$month-$day $time");
				$delta = $file_modification_time->diff($now);
				if($delta->d < 1)
				{
					if(strpos($file_name, "Charter-") === 0)
					{
						if($this->download_file_from_tmpi_ftp($file_name, $this->m_working_directory))
						{
							$files_in_last_day[] = $file_name;
							$this->upload_file_to_cdn($this->m_working_directory, $file_name);
							$this->info[] = "Transfered '$file_name' from tmpi ftp to cdn";
						}
						else
						{
							$this->errors[] = "Failed to get $file_name from tmpi ftp";
						}
					}
				}
			}
		}

		return $files_in_last_day;
	}

	private function connect_and_login_to_ftp()
	{
		$tmpi_data_ftp_config = $this->config->item('tmpi_report_data_ftp');
		$ftp_resource = ftp_connect($tmpi_data_ftp_config['ftp_host']);
		if($ftp_resource !== false)
		{
			$debug_info[] = "ftp connected\n";

			$is_logged_in = ftp_login(
				$ftp_resource,
				$tmpi_data_ftp_config['ftp_username'],
				$tmpi_data_ftp_config['ftp_password']
			);
			if($is_logged_in !== false)
			{
				$debug_info[] = "ftp logged in\n";

				$is_passive_success = ftp_pasv($ftp_resource, true);
				if($is_passive_success !== false)
				{
					$debug_info[] = "entered passive mode\n";
				}
				else
				{
					throw new Exception("Failed to enter passive mode in ftp.");
				}
			}
			else
			{
				throw new Exception("Failed to login to ftp.");
			}
		}
		else
		{
			throw new Exception("Failed to connect to ftp.");
		}

		return $ftp_resource;
	}

	private function get_ftp_resource()
	{
		if(empty($this->m_ftp_resource))
		{
			try
			{
				$this->m_ftp_resource = $this->connect_and_login_to_ftp();
			}
			catch(Exception $exception)
			{
				$this->m_ftp_resource = false;

				throw $exception;
			}
		}

		return $this->m_ftp_resource;
	}

	private function get_ftp_file_list()
	{
		if($this->m_ftp_file_list === null)
		{
			$ftp_resource = $this->get_ftp_resource();
			$file_list = ftp_nlist($ftp_resource, "-lrt .");

			if($file_list === false)
			{
				throw new Exception("Failed to get list of files from ftp");
			}
			else
			{
				$this->m_ftp_file_list = $file_list;
			}
		}

		return $this->m_ftp_file_list;
	}

	private function make_file_name_from_date($date)
	{
		$date_string = date("Ymd", strtotime($date));
		$file_name = "Charter-$date_string.zip";
		return $file_name;
	}

	private function get_date_from_file_name($file_name)
	{
		$date = "";
		$matches = array();

		$match_result = preg_match("/Charter-([[:alnum:]]+-)?(\d{8})(@(\d{1,2}))?(.{0,3})?.(zip|txt)/", $file_name, $matches);

		// Example file name: Charter-20150307.zip or Charter-20150730@12_v2.zip
		if($match_result)
		{
			$date_string = $matches[2];
			$year = substr($date_string, 0, 4);
			$month = substr($date_string, 4, 2);
			$day = substr($date_string, 6, 2);
			$date = $year."-".$month."-".$day;
		}
		else
		{
			throw new Exception("Unexpected filename format for: '$file_name', expected: Charter-<date>@<hour>_<version>.zip");
		}

		return $date;
	}

	private function get_most_recent_cdn_file_name($date_filter = '', $file_version = '')
	{
		$file_name = false;

		$should_reload_cdn_list = true;
		$old_date_marker = date("Ymd", strtotime("- 10 days"));  // Only 100 items returned per page, look back 50 days
		$tomorrow_date_marker = date("Ymd", strtotime("+ 1 day"));

		if(!empty($date_filter))
		{
			$old_date_marker = null;
			$tomorrow_date_marker = null;
		}

		$cdn_object_list = $this->get_cdn_object_list(
			$date_filter,
			$old_date_marker,
			$tomorrow_date_marker,
			$should_reload_cdn_list
		);

		$num_cdn_objects = $cdn_object_list->count();
		if($num_cdn_objects > 0)
		{
			for($file_index = $num_cdn_objects - 1; $file_index >= 0; $file_index--)
			{
				$cdn_object = $cdn_object_list[$file_index];
				$last_slash_index = strrpos($cdn_object->name, "/");
				$cdn_object_name = substr($cdn_object->name, $last_slash_index + 1);
				if(strpos($cdn_object_name, "_{$file_version}.") !== false)
				{
					$file_name = $cdn_object_name;
					break;
				}
			}
		}

		return $file_name;
	}

	private function validate_and_create_working_directory($working_directory)
	{
		if(!file_exists($working_directory))
		{
			if(!mkdir($working_directory, 0775))
			{
				throw new Exception("Failed to create directory: $working_directory.");
			}
		}
		else
		{
			if(!is_dir($working_directory))
			{
				throw new Exception("Expected $working_directory to be a directory, it is not.");
			}
		}
	}

	private function download_file_from_tmpi_ftp($file_to_get, $working_directory)
	{
		$is_success = false;

		if($file_to_get != null)
		{
			$this->validate_and_create_working_directory($working_directory);
			$new_file_location = $working_directory."/".$file_to_get;
			$ftp_resource = $this->get_ftp_resource();
			$is_success = ftp_get($ftp_resource, $new_file_location, $file_to_get, FTP_BINARY);
		}

		return $is_success;
	}

	//	$directory is where the files are stored
	//	if $date is specified, the file with that date as a substring is retrieved if it exists
	//	if $date is not specified, then the most recent file is retrieved
	//
	//	returns result
	//	if result['is_success'] == false, then result['errors'] has an array of info about what went wrong
	// TODO: move this comment

	private function merge_results(&$destination_results, $source_results)
	{
		$destination_results['is_success'] = $destination_results['is_success'] && $source_results['is_success'];

		$keys_for_arrays_to_merge = array(
			'info',
			'warnings',
			'errors'
		);

		foreach($keys_for_arrays_to_merge as $index => $key)
		{
			$is_destination_array = array_key_exists($key, $destination_results) && is_array($destination_results[$key]);
			$is_source_array = array_key_exists($key, $source_results) && is_array($source_results[$key]);

			if($is_destination_array && $is_source_array)
			{
				$destination_results[$key] = array_merge($destination_results[$key], $source_results[$key]);
			}
			elseif($is_destination_array)
			{
				$destination_results[$key] = $destination_results[$key];
			}
			elseif($is_source_array)
			{
				$destination_results[$key] = $source_results[$key];
			}
			else
			{
				$destination_results[$key] = array();
			}
		}
	}

	public function load_single_file()
	{
		global $argv;

		if($this->input->is_cli_request())
		{
			$results = array(
				'is_success' => true,
				'info' => &$this->info,
				'warnings' => &$this->warngings,
				'errors' => &$this->errors,
				'date_uploaded' => '0000-00-00'
			);

			$this->cli_data_processor_common->mark_script_execution_start_time();

			$input_file = $argv[3];

			if(file_exists($input_file))
			{
				$working_directory = dirname($input_file);
				$file_name = basename($input_file);
				$directory_listing = scandir($working_directory);
				if($directory_listing !== false)
				{
					$matches = array();
					$is_matching = preg_match("/Charter-([[:alnum:]]+)-/", $file_name, $matches);
					if($is_matching)
					{
						$file_segment = $matches[1];

						try {
							$file_date = $this->get_date_from_file_name($file_name);
							$results['date_uploaded'] = $file_date;

							$map_file_segment_to_processor_name = array(
								'CustomerList' => 'upload_customer_list_to_database',
								'DisplayClicks' => 'upload_display_clicks_to_database',
								'DisplayGeoClicks' => 'upload_display_geo_clicks_to_database',
								'EmailLeads' => 'upload_email_leads_to_database',
								'HiLoPerformance' => 'upload_hi_lo_performance_to_database',
								'LocalPerformance' => 'upload_local_performance_to_database',
								'PhoneLeads' => 'upload_phone_leads_to_database',
								'SearchDetailPerformance' => 'upload_search_detail_performance_to_database',
								'VehicleSummary' => 'upload_vehicle_summaries_to_database'
							);

							if(array_key_exists($file_segment, $map_file_segment_to_processor_name))
							{
								$processor_name = $map_file_segment_to_processor_name[$file_segment];
								$upload_result = $this->tmpi_model->$processor_name($working_directory, array($file_name), $file_date);
								$this->merge_results($results, $upload_result);
							}
							else
							{
								$results['is_success'] = false;
								$results['errors'][] = "Failed to match file segment ($file_segment) to processor.";
							}
						}
						catch (Exception $exception)
						{
							$results['is_success'] = false;
							$results['errors'][] = $exception->getMessage();
						}
					}
					else
					{
						$results['is_success'] = false;
						$results['errors'][] = "Failed to find file segment, file_name: $file_name";
					}
				}
				else
				{
					$results['is_success'] = false;
					$results['errors'][] = "Failed listing directory";
				}

				$this->cli_data_processor_common->mark_script_execution_end_time();

				$email_subject = "";
				$email_message = "";
				$is_message_html = false;

				$this->compose_email($email_subject, $email_message, $is_message_html, $results);
				$this->send_email($email_subject, $email_message, $is_message_html);
			}
			else
			{
				echo "Failed to find file: $input_file\n";
			}
		}
		else
		{
			show_404();
		}
	}

	public function load_data($input_date = null, $transfer_files_from_tmpi_ftp_to_cdn = true)
	{
		if($this->input->is_cli_request())
		{
			$results = array(
				'is_success' => true,
				'info' => &$this->info,
				'warnings' => &$this->warngings,
				'errors' => &$this->errors,
				'date_uploaded' => '0000-00-00'
			);

			$this->cli_data_processor_common->mark_script_execution_start_time();

			// exit if already running
			$my_process_id = getmypid();
			$parent_process_id = posix_getppid();

			$active_tmpi_processes = shell_exec('ps aux | grep -v "grep" | grep "php index.php tmpi_data_loader"');
			$process_rows = explode("\n", $active_tmpi_processes);
			foreach($process_rows as $process_row_string)
			{
				if(!empty($process_row_string))
				{
					$process_row = preg_split("/\s+/", $process_row_string);
					$process_id = $process_row[1];
					if($process_id != $my_process_id && $process_id != $parent_process_id)
					{
						echo "Exiting becasue data loader is already running.\n\tMy id: $my_process_id, their id: $process_id, parent_process_id: $parent_process_id\n\n";
						exit(0); // data loader is already running
					}
				}
			}

			$files_in_last_day = array();
			if($transfer_files_from_tmpi_ftp_to_cdn == true && $transfer_files_from_tmpi_ftp_to_cdn !== 'false')
			{
				$files_in_last_day = $this->upload_new_files_from_tmpi_ftp_to_cdn();
			}

			$working_directory = $this->m_working_directory;

			$file_name = null;
			$file_size = 0;

			try {

				$date = $input_date;

				if($input_date === null)
				{
					foreach($files_in_last_day as $check_file_name)
					{
						$regex_pattern = "/{$this->k_tmpi_zip_file_prefix}[[:digit:]]{8}@[[:digit:]]{2}_v4.zip/";
						if(preg_match($regex_pattern, $check_file_name))
						{
							$file_name = $check_file_name;
							break;
						}
					}

					if(empty($file_name))
					{
						throw new Exception("Failed to find new TMPi data file.");
					}

					$date = $this->get_date_from_file_name($file_name);
				}
				else
				{
					$date_filter = date("Ymd", strtotime($date));
					$file_version = '';

					$date_time = new DateTime($date);

					$v2_start_date = new DateTime('2015-08-01');
					$v2_delta = $v2_start_date->diff($date_time);
					$has_v2_data = !$v2_delta->invert;
					if($has_v2_data)
					{
						$date_filter .= '@';
						$file_version = 'v2';
					}
					else
					{
						$date_filter .= '.';
					}

					$v4_start_date = new DateTime('2016-04-13');
					$v4_delta = $v4_start_date->diff($date_time);
					$has_v4_data = !$v4_delta->invert;
					if($has_v4_data)
					{
						$file_version = 'v4';
					}

					$file_name = $this->get_most_recent_cdn_file_name($date_filter, $file_version);

					if(empty($file_name))
					{
						throw new Exception("Failed to find file with date '$date' in name on CDN.");
					}
				}

				$actual_date_used = $date;
				$results['date_uploaded'] = $actual_date_used;

				if($this->download_file_from_cdn($working_directory, $file_name))
				{
					$file_name_and_path = $working_directory."/".$file_name;
					$file_size = filesize($file_name_and_path);

					// load last run info
					if($input_date === null && file_exists($this->k_last_run_info_file_name))
					{
						$last_run_json = file_get_contents($this->k_last_run_info_file_name);

						if(!empty($last_run_json))
						{
							$last_run_info = json_decode($last_run_json);

							if($file_name == $last_run_info->file_name &&
								$file_size == $last_run_info->file_size
							)
							{
								echo "Exiting because same file would be processed, specify date to override\n";
								exit(0); // Nothing has changed since last run
							}
						}
					}

					$unzip_output = shell_exec("unzip $file_name_and_path -d $working_directory");
					$this->info[] = $unzip_output;

					$directory_listing = scandir($working_directory);

					if($directory_listing !== false)
					{
						$upload_results['upload_customer_result'] = $this->tmpi_model->upload_customer_list_to_database($working_directory, $directory_listing, $actual_date_used);
						$upload_results['upload_display_result'] = $this->tmpi_model->upload_display_clicks_to_database($working_directory, $directory_listing, $actual_date_used);
						if(strtotime($actual_date_used) >= strtotime('2015-02-20'))
						{
							$upload_results['upload_display_geo_clicks_result'] = $this->tmpi_model->upload_display_geo_clicks_to_database($working_directory, $directory_listing, $actual_date_used);
						}
						$upload_results['upload_email_result'] = $this->tmpi_model->upload_email_leads_to_database($working_directory, $directory_listing, $actual_date_used);
						$upload_results['upload_hi_lo_performance_result'] = $this->tmpi_model->upload_hi_lo_performance_to_database($working_directory, $directory_listing, $actual_date_used);
						$upload_results['upload_local_result'] = $this->tmpi_model->upload_local_performance_to_database($working_directory, $directory_listing, $actual_date_used);
						$upload_results['upload_phone_result'] = $this->tmpi_model->upload_phone_leads_to_database($working_directory, $directory_listing, $actual_date_used);
						$upload_results['upload_search_result'] = $this->tmpi_model->upload_search_detail_performance_to_database($working_directory, $directory_listing, $actual_date_used);
						$upload_results['upload_vehicle_summaries_result'] = $this->tmpi_model->upload_vehicle_summaries_to_database($working_directory, $directory_listing, $actual_date_used);

						$upload_results['link_tmpi_accounts'] = $this->tmpi_model->link_tmpi_accounts_to_advertisers($actual_date_used);
						foreach($upload_results as $key => $upload_result)
						{
							$this->merge_results($results, $upload_result);
						}
					}
					else
					{
						$results['is_success'] = false;
						$results['errors'][] = "Failed listing directory";
					}
				}
				else
				{
					$results['is_success'] = false;
					$results['errors'][] = "Failed to download file '$file_name' from CDN";
				}
			}
			catch (Exception $exception)
			{
				$results['is_success'] = false;
				$results['errors'][] = $exception->getMessage();
			}

			if($input_date === null && $results['is_success'])
			{
				$current_run_info = array(
					'file_name' => $file_name,
					'file_size' => $file_size,
					'completion_date_time' => date("Y-m-d H:i:s")
				);
				$current_run_json = json_encode($current_run_info);
				file_put_contents($this->k_last_run_info_file_name, $current_run_json);
			}

			$this->cli_data_processor_common->mark_script_execution_end_time();

			$email_subject = "";
			$email_message = "";
			$is_message_html = false;

			$this->compose_email($email_subject, $email_message, $is_message_html, $results);
			$this->send_email($email_subject, $email_message, $is_message_html);
		}
		else
		{
			show_404();
		}
	}

	private function add_newline_if_missing($str)
	{
		$last_character = substr($str, -1);
		if($last_character !== "\n" &&
			$last_character !== "\r"
		)
		{
			return $str.PHP_EOL;
		}
		else
		{
			return $str;
		}

	}

	private function create_message_string_from_messages_array(
		$section_header_message,
		$messages_array
	)
	{
		$accumulated_message = "";

		if(!empty($messages_array))
		{
			$accumulated_message = $this->add_newline_if_missing($section_header_message);
			foreach($messages_array as $index => $message)
			{
				if($index >= 100)
				{
					$accumulated_message .= " - ...\n";
					$accumulated_message .= " - ".$this->add_newline_if_missing(end($messages_array));
					break;
				}
				$accumulated_message .= " - ".$this->add_newline_if_missing($message);
			}

			$accumulated_message .= "\n\n";
		}

		return $accumulated_message;
	}

	private function compose_email(&$subject, &$message, &$is_message_html, $processing_results)
	{
		$subject = "TMPi Data Processor: ";
		$message = "";
		$is_message_html = false;

		$date_time = date("Y-m-d H:i:s T O");

		if($processing_results['is_success'] === true)
		{
			$subject .= "Successfully processed ".$processing_results['date_uploaded']." ($date_time)";
		}
		else
		{
			$subject .= "Failed to process ".$processing_results['date_uploaded']." ($date_time)";
		}

		$num_error_messages = count($processing_results['errors']);
		$num_warning_messages = count($processing_results['warnings']);
		$num_info_messages = count($processing_results['info']);

		$message .= $subject."\n\n";
		$message .= $num_error_messages." error messages\n";
		$message .= $num_warning_messages." warning messages\n";
		$message .= $num_info_messages." info messages\n";
		$message .= "\n";

		$environment_message = $this->cli_data_processor_common->get_environment_message('text');
		$message .= $environment_message;
		$time_message = $this->cli_data_processor_common->get_script_execution_time_message('text');
		$message .= $time_message;
		$message .= "\n";

		$message .= $this->create_message_string_from_messages_array(
			"Error messages (".$num_error_messages."):",
			$processing_results['errors']
		);
		$message .= $this->create_message_string_from_messages_array(
			"Warning messages (".$num_warning_messages."):",
			$processing_results['warnings']
		);
		$message .= $this->create_message_string_from_messages_array(
			"Info messages (".$num_info_messages."):",
			$processing_results['info']
		);
	}

	private function send_email($subject, $message, $is_message_html)
	{
		$from_address = 'TMPi Report Data <tech.logs@frequence.com>';
		$to_address = 'Tech Logs <tech.logs@frequence.com>';

		$message_type = 'text';
		if($is_message_html === true)
		{
			$message_type = 'html';
		}

		$mail_response = mailgun(
			$from_address,
			$to_address,
			$subject,
			$message,
			$message_type
		);

		if($mail_response !== true)
		{
			echo "Failed to send email\n\n";
			echo $subject."\n";
			echo $message."\n";
		}
	}
}

?>
