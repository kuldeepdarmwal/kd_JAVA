<?php
	class engagement_exception extends Exception
	{
	}

	$g_raw_database;
	$g_main_database;
	$g_processing_stats = array(); // accumulate data for email
	$g_save_file_path = '';
	$g_start_date = date("Y-m-d", strtotime("-2 day"));
	$g_end_date = date("Y-m-d", strtotime("-2 day"));
	if($argc == 2)
	{
		$g_save_file_path = $argv[1];
		if(!is_string($g_save_file_path))
		{
			die('ERROR: Path argument invalid in upload_cdn_data.php: argument #1 ('.$argv[1].')'."\n");
		}
	}
	elseif($argc == 4)
	{
		$g_save_file_path = $argv[1];
		if(!is_string($g_save_file_path))
		{
			die('ERROR: Path argument invalid in upload_cdn_data.php: argument #1 ('.$argv[1].')'."\n");
		}

		$start_time = strtotime($argv[2]);
		$end_time = strtotime($argv[3]);
		if($start_time != false && $end_time != false)
		{
			$g_start_date = date("Y-m-d", $start_time);
			$g_end_date = date("Y-m-d", $end_time);
		}
		else
		{
			die('ERROR: Date arguments invalid in upload_cdn_data.php: argument #2 ('.$argv[2].'), argument #3 ('.$argv[3].')'."\n");
		}
	}
	else
	{
		die('ERROR: Wrong number of arguments to upload_cdn_data.php: ('.$argc.') expected 1 or 3: upload_cdn_data.php <path> [<start_date> <end_date>]'."\n");
	}

	// database config taken from codeigniter database.php 
	$g_database_info_file = $g_save_file_path.'database.php';
	$g_main_database_host_name = 'db.vantagelocaldev.com';
	$g_main_database_user = 'vldevuser';
	$g_main_database_password = 'L0cal1s1n!';
	$g_main_database_name = 'vantagelocal_dev';
	$g_intermediate_database_host_name = 'db.vantagelocaldev.com';
	$g_intermediate_database_user = 'vldevuser';
	$g_intermediate_database_password = 'L0cal1s1n!';
	$g_intermediate_database_name = 'vantagelocal_dev_raw';

	function get_database_config($file_name)
	{
		$is_success = false;

		global $g_main_database_host_name;
		global $g_main_database_user;
		global $g_main_database_password;
		global $g_main_database_name;
		global $g_intermediate_database_host_name;
		global $g_intermediate_database_user;
		global $g_intermediate_database_password;
		global $g_intermediate_database_name;

		// hack so that we can use the codeigniter database.php config file
		if(!defined('BASEPATH'))
		{
			define('BASEPATH', ' ');
		}
		include $file_name;

		if(isset($db))
		{
			$g_main_database_host_name = $db[$active_group]['hostname'];
			$g_main_database_user = $db[$active_group]['username'];
			$g_main_database_password = $db[$active_group]['password'];
			$g_main_database_name = $db[$active_group]['database'];
			$g_intermediate_database_host_name = $db['td_intermediate']['hostname'];
			$g_intermediate_database_user = $db['td_intermediate']['username'];
			$g_intermediate_database_password = $db['td_intermediate']['password'];
			$g_intermediate_database_name = $db['td_intermediate']['database'];

			$is_success = true;
		}
		else
		{
			$is_success = false;
		}

		return $is_success;
	}

	function connect_to_database(&$database_handle, $rdms_name, $user_name, $password, $schema_name)
	{
		$database_handle = new mysqli($rdms_name, $user_name, $password, $schema_name);
		if($database_handle->connect_errno)
		{
			$error_string = "failed to connect to mysql: (".$database_handle->connect_errno.") ".$database_handle->connect_error;
			throw(new engagement_exception($error_string));
		}
	}

	function disconnect_from_database(&$database_handle)
	{
		$database_handle->close();
	}

	function connect_to_raw_database()
	{
		global $g_raw_database;
		global $g_intermediate_database_host_name;
		global $g_intermediate_database_user;
		global $g_intermediate_database_password;
		global $g_intermediate_database_name;
		connect_to_database(
			$g_raw_database, 
			$g_intermediate_database_host_name, 
			$g_intermediate_database_user, 
			$g_intermediate_database_password, 
			$g_intermediate_database_name
			);
	}
	
	function disconnect_from_raw_database()
	{
		global $g_raw_database;
		disconnect_from_database($g_raw_database);
	}

	function put_data_in_raw_database($query_string)
	{
		global $g_raw_database;
		if(!$g_raw_database->query($query_string))
		{
			$error_string = "Failed to upload raw data: (".$g_raw_database->errno.") ".$g_raw_database->error;
			throw(new engagement_exception($error_string));
		}
	}

	function clear_raw_data_for_day($date)
	{
		$query = '
		DELETE FROM rs_cdn_raw_engagements 
		WHERE time BETWEEN \''.$date.' 00:00:00\' AND \''.$date.' 23:59:59\'
		';
		global $g_raw_database;
		if(!$g_raw_database->query($query))
		{
			$error_string = "Failed to truncate raw table: (".$g_raw_database->errno.") ".$g_raw_database->error;
			throw(new engagement_exception($error_string));
		}
	}

	function clear_all_raw_data()
	{
		$query = 'TRUNCATE TABLE rs_cdn_raw_engagements';
		global $g_raw_database;
		if(!$g_raw_database->query($query))
		{
			$error_string = "Failed to truncate raw table: (".$g_raw_database->errno.") ".$g_raw_database->error;
			throw(new engagement_exception($error_string));
		}
	}
	
	// Data for this mapping defined/referenced in google doc: Engagement Tracking Pixel Names
	// 		https://docs.google.com/a/vantagelocal.com/spreadsheet/ccc?key=0AocoBJQFsBDgdHNNR292Njl5S3dEZHNEN0FQRS1uZFE	
	function map_engagement_string_to_id($engagement_string)
	{
		$map_engagement_type_string_to_db_id = array(
			'hvp' => 1,  // hover play video
			'ldv' => 2,  // video load // (depracated)
			'hvc' => 4,  // hover count // (depracated) // also has deprecated 'hvc-2' format where the value is after the '-'
			'hov' => 5,  // hover over // also has 3rd parameter 'cnt', variable value // also has deprecated 'hov-2' format where the value is after the '-'
			'cpv' => 6,  // click play video
			'clk' => 7,  // ad clicked // also has 3rd parameter 'cnt', variable value // added 3rd param: (3/12/2013 - shuber)
			'rph' => 8,  // replay ad hover
			'rpc' => 9,  // replay ad click 
			'cvh' => 10, // close video hover
			'cvc' => 11, // close video click
			'vdc' => 12, // video complete
			'fct' => 13, // fullscreen click tag
			'ful' => 14, // enter fullscreen
			'exf' => 15, // exit fullscreen
			'hvm' => 16, // hover map load
			'clm' => 17, // click map load
			'hve' => 18, // hover email load
			'cle' => 19, // click email load
			'mid' => 20, // video midpoint
			'vpc' => 21, // video play count // also has 3rd parameter 'cnt', variable value
			'hv2' => 22, // hover 2 seconds // also has 3rd parameter 'cnt', variable value
			'pts' => 23, // play time seconds // also has 3rd parameter 'cnt', variable value // was: 's' (3/12/2013 - shuber)
			'she' => 24, // share email
			'shf' => 25, // share facebook
			'shl' => 26, // share linkedin
			'sht' => 27, // share twitter
			'shg' => 28, // share google
			'ct2' => 29, // second clickTag // also has 3rd parameter 'cnt', variable value
			'eml' => 30, // email submitted
			'imp' => 31, // ad impression
			'exp' => 32, // ad expanded // also has 3rd parameter 'cnt', variable value
			'swp' => 33, // swipes // also has 3rd parameter 'cnt', variable value
			'exc' => 34  // close expandable
		);


		$engagemnt_type = 0;
		if(array_key_exists($engagement_string, $map_engagement_type_string_to_db_id))
		{
			$engagement_type = $map_engagement_type_string_to_db_id[$engagement_string];
		}
		else
		{
			$error_string = "ERROR: No engagement_type map for string: ".$engagement_string."\n";
			$engagement_type = $error_string;
		}
				
		return $engagement_type;
	}

	function does_engagment_have_value_in_url_parameter($engagement_string)
	{
		$map_engagement_string_to_value_presence = array(
			'hov' => true,
			'clk' => true,
			'vpc' => true,
			'hv2' => true,
			'pts' => true,
			'ct2' => true,
			'exp' => true,
			'swp' => true
		);

		$has_value = false;
		if(array_key_exists($engagement_string, $map_engagement_string_to_value_presence))
		{
			$has_value = $map_engagement_string_to_value_presence[$engagement_string];
		}

		return $has_value;
	}

	function is_deprecated_non_value_which_now_has_value($engagement_id_string)
	{
		$map_deprecated_cases = array(
			'clk' => true
		);

		$is_deprecated_case = false;
		if(array_key_exists($engagement_id_string, $map_deprecated_cases))
		{
			$is_deprecated_case = $map_deprecated_cases[$engagement_id_string];
		}

		if($is_deprecated_case)
		{
			$g_processing_stats['warnings'][] = 'Warning: engagment_value ('.$engagement_id_string.') detected which used to not have value but now has value, but is still missing value'."\n";
		}
		return $is_deprecated_case;
	}

	// Return: if non-empty string, then errors occured
	// Return: if deprecated_value is non-zero, then found and processed deprecated string
	// Return: fixed_deprecated_engagement_string is populated if deprecated_value is non-zero
	function handle_deprecated_value_format(&$deprecated_value, &$fixed_deprecated_engagement_string, $raw_engagement_string)
	{
		global $g_processing_stats;

		$deprecated_value = 0;
		$fixed_deprecated_engagement_string = '';

		$deprecated_cases = array(
			'hvc-', 
			'hov-'
		);

		$found_old_value_case = false;
		$found_old_value_debug = '';
		foreach($deprecated_cases as $test_string)
		{
			$found_old_value_case = $found_old_value_case || (false !== strpos($raw_engagement_string, $test_string)); // need to test for false because it might be some weird value according to docs
			if($found_old_value_case)
			{
				$found_old_value_debug = $test_string;
				break;
			}
		}

		if($found_old_value_case == true)
		{
			$g_processing_stats['warnings'][] = 'Warning: old engagment_value format processed: '.$raw_engagement_string."\n";

			$deprecated_value = substr($raw_engagement_string, 4);
			if(!is_numeric($deprecated_value))
			{
				$error_string = "ERROR: old (".$found_old_value_debug.") engagement_value is not a number: ".$deprecated_value."\n";
				return $error_string;
			}
			$fixed_deprecated_engagement_string = substr($raw_engagement_string, 0, 3);// Example: get 'hvc' from 'hvc-3'
		}

		return '';
	}

	function parse_raw_data_line(&$creative_id, &$date_value, &$engagement_type, &$unique_display_id, &$engagement_value, $data_line)
	{
		$creative_id = 0;
		$date_value = date("Y-m-d H:i:s");
		$engagement_type = 0;
		$unique_display_id = 0;
		$engagement_value = 0;

		$return_array = array("success"=>"false", "is_bulk"=>0, "err_msg"=>"");

		// Example expected string format: 
		// 97.94.138.123 - - [05/Feb/2013:02:06:53 +0000] "GET /3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif?vlid=VL_3955957_1854626565&e=hvp HTTP/1.1" 200 434 "-" "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17" "-"
		$preg_pattern = '/((".*?")|(\[.*?\])|(\S*))\s*/';
		$data_array = array();
		preg_match_all($preg_pattern, $data_line, $data_array);
		$real_pattern_index = 1;
		$date_string_index = 3;
		$get_string_index = 4;
		$host_string_index = 8;

		// extract the date from section: 
		// [05/Feb/2013:02:06:53 +0000]
		$date_data_string = $data_array[$real_pattern_index][$date_string_index];
		$split_date = explode(' ', $date_data_string);
		if(count($split_date) != 2)
		{
			$return_array['err_msg'] = "ERROR: No data for date"."\n";
			return $return_array;
		}

		$date_time_trimmed = trim($split_date[0], '[');
		$date_time_split = substr_replace($date_time_trimmed, ' ', strpos($date_time_trimmed, ':'), 1);
		$date_fixing = substr_replace($date_time_split, '-', strpos($date_time_split, '/'), 1);
		$date_fixed = substr_replace($date_fixing, ' ', strpos($date_fixing, '/'), 1);
		$converted_time = strtotime($date_fixed);
		if($converted_time == false)
		{
			// new format is: [17/04/2014:17:02:03 +0000] // "day/month/year"
			$date_time_exploded = explode(' ', $date_time_split);
			$broken_date_string = $date_time_exploded[0];

			$broken_date_string_exploded = explode('/', $broken_date_string);
			$fixed_broken_date_string = $broken_date_string_exploded[2] . '-' . $broken_date_string_exploded[1] . '-' . $broken_date_string_exploded[0];  // change to "year-month-day"
			$date_fixed = $fixed_broken_date_string . ' ' . $date_time_exploded[1];  // append the time string back on
			$converted_time = strtotime($date_fixed);
		}

		if($converted_time == false)
		{
			$return_array['err_msg'] = "ERROR: Invalid date format"."\n";
			return $return_array;
		}

		$date_value = date("Y-m-d H:i:s", $converted_time);
		if($date_value == false)
		{
			$return_array['err_msg'] = "ERROR: Failed to format date"."\n";
			return $return_array;
		}

		// extract creative_id, unique_display_id, engagement_type from url section: 
		// "GET /3100e55ffe038d736447-4c272e1e114489a00a7c5f34206ea8d1.r73.cf1.rackcdn.com/px.gif?vlid=VL_3955957_1854626565&e=hvp HTTP/1.1"
		$encoded_data_string = $data_array[$real_pattern_index][$get_string_index];
		$data_string = $encoded_data_string;

		$url_array = explode(' ', $data_string);
		if(count($url_array) != 3)
		{
			$return_array['err_msg'] = "ERROR: No data for url"."\n";
			return $return_array;
		}
		$encoded_url_string = $url_array[1];

		$url_string = rawurldecode($encoded_url_string);
		$url_parameter = explode('?', $url_string);
		if(count($url_parameter) != 2)
		{
			if(count($url_parameter) <= 0 ||
				false === strpos($url_parameter[0], 'crossdomain.xml'))
			{
				$host_string = $data_array[$real_pattern_index][$host_string_index];
				$ignore_erroneous_host_string = '"Test Certificate Info"';

				if($host_string == $ignore_erroneous_host_string)
				{
					$return_array['success'] = 'ignore error';
					return $return_array;
				}
				else
				{
					$return_array['err_msg'] = "ERROR: No data in url"."\n";
					return $return_array;
				}
			}
			else
			{
				$return_array['success'] = 'ignore error';
				return $return_array;
			}
		}

		// extract creative_id, unique_display_id, engagement_type, and possible a variable value from url parameters: 
		// vlid=VL_3955957_1854626565&e=hov&cnt=2
		// vlid=VL_3955957_1854626565&e=hvp
		// Debug stuff: vlid=VL_3955957_1854626565&e=hvc-3

		// split URL variables into array
		$url_variables_string = urldecode($url_parameter[1]);
		$url_variables = explode('&', $url_variables_string);
		$num_url_variables = count($url_variables);
		if($num_url_variables == 0)
		{
			$return_array['err_msg'] = "ERROR: No url variables"."\n";
			return $return_array;
		}
		elseif($num_url_variables != 2 &&
			$num_url_variables != 3
		)
		{
			$return_array['err_msg'] = "ERROR: Invalid # of url variables :".$num_url_variables."\n";
			return $return_array;
		}

		$indetifier_ulr_variable_index = 0;
		$creative_id_and_unique_id_variable_segment = explode('=', $url_variables[$indetifier_ulr_variable_index]);
		if(count($creative_id_and_unique_id_variable_segment) != 2)
		{
			$return_array['err_msg'] = "ERROR: No creative_id and unique_display_id segments"."\n";
			return $return_array;
		}

		// extract creative_id, unique_display_id from 'vlid' segment: 
		// 'vlid=VL_3955957_1854626565'
 		$creative_id_and_unique_id_variable_string = urldecode($creative_id_and_unique_id_variable_segment[1]);
		$creative_id_and_unique_id_variables = explode('_', $creative_id_and_unique_id_variable_string);
		if(count($creative_id_and_unique_id_variables) != 3)
		{
			$return_array['err_msg'] = "ERROR: No creative_id and unique_display_id variables"."\n";
			return $return_array;
		}

		if($creative_id_and_unique_id_variables[0] != 'VL' ||
			!is_numeric($creative_id_and_unique_id_variables[2]))
		{
			$return_array['err_msg'] = "ERROR: creative_id or unique_display_id invalid"."\n";
			return $return_array;
		}
		//if the creative_id looks like a bulk one, retrieve from database
		if(strpos($creative_id_and_unique_id_variables[1], '-'))
		{   		    
		    $return_array['is_bulk'] = 1;
		}
		
		$creative_id = "";
		$unique_display_id = "";
		$creative_id = $creative_id_and_unique_id_variables[1];
		$unique_display_id = $creative_id_and_unique_id_variables[2];
		if(empty($creative_id) || empty($unique_display_id))
		{
		    $return_array['err_msg'] = "ERROR: creative_id or unique_display_id was empty"."\n";
		    return $return_array;  
		}

		// extract engagement type 'e=hvp' or 'e=hvc-7'
		$engagement_type_ulr_variable_index = 1;
		$engagement_id_variable_segment = explode('=', $url_variables[$engagement_type_ulr_variable_index]);
		if(count($engagement_id_variable_segment) != 2)
		{
			$return_array['err_msg'] = "ERROR: No engagement_type segments"."\n";
			return $return_array;
		}

		$engagement_id_string = $engagement_id_variable_segment[1];

		$found_old_value_case = false;
		$old_value = 0;
		$old_engagment_string = '';
		$error_string = handle_deprecated_value_format($old_value, $old_engagement_string, $engagement_id_string);
		if($error_string != '')
		{
			$return_array['err_msg'] = $error_string;
			return $return_array;
		}
		elseif($old_value != 0)
		{
			$found_old_value_case = true;
			$engagement_id_string = $old_engagement_string;
		}
		else
		{
			// continue with standard format
		}

		//$engagement_id_string = $engagement_id_variable_segment[1];
		$engagement_type = map_engagement_string_to_id($engagement_id_string);
		if(gettype($engagement_type) == 'string')
		{
			$return_array['err_msg'] = $engagement_type;
			return $return_array;
		}

		$has_value = does_engagment_have_value_in_url_parameter($engagement_id_string);

		$engagement_value = 0;
		// extract value parameter if it exist 'cnt=7'
		if($has_value == true)
		{
			if($num_url_variables == 3)
			{
				$value_url_variable_index = 2;
				$engagement_value_segment = explode('=', $url_variables[$value_url_variable_index]);
				if(count($engagement_value_segment) != 2)
				{
					$return_array['err_msg'] = "ERROR: No engagement_value for: ".$engagement_id_string."\n";
					return $return_array;
				}

				$engagement_value = $engagement_value_segment[1];
				if(!is_numeric($engagement_value))
				{
					$return_array['err_msg'] = "ERROR: engagement_value is not a number: ".$engagement_value."\n";
					return $return_array;
				}
			}
			elseif(is_deprecated_non_value_which_now_has_value($engagement_id_string))
			{
				$engagement_value = 1;
			}
			elseif($found_old_value_case == true)
			{
				$engagement_value = $old_value;
			}
			else // missing engagment value
			{
				$return_array['err_msg'] = "ERROR: no engagement_value segment for: ".$engagement_id_string."\n";
				return $return_array;
			}
		}
		elseif($num_url_variables == 3)
		{
			// doesn't need engagment value, but provided
			$return_array['err_msg'] = "ERROR: engagement_value segment present but not needed: ".$engagement_id_string."\n";
			return $return_array;
		}
		elseif($found_old_value_case == true)
		{
			$engagement_value = $old_value;
		}
		
		$return_array['success'] = 'success';
		return $return_array;
	}

	function upload_file_to_raw_data($file_name)
	{
		global $g_processing_stats;
		$is_error = false;

		$file_handle = fopen($file_name, 'r');
		if($file_handle)
		{
			$line_number = 0;
			$query_values = '';
			$first_loop = true;
			while($data_line = fgets($file_handle))
			{
				$line_number++;

				$creative_id;
				$date_value;
				$engagement_type;
				$unique_display_id;
				$engagement_value;

				$result = parse_raw_data_line($creative_id, $date_value, $engagement_type, $unique_display_id, $engagement_value, $data_line);

				if($result['success'] == 'success')
				{
					if(!$first_loop)
					{
						$query_values .= ', '."\n";
					}
					else
					{
						$first_loop = false;
					}
					if($result['is_bulk'])
					{
					    $query_values .= '(NULL,\''.$creative_id.'\', \''.$date_value.'\', '.$engagement_type.', '.$unique_display_id.', '.$engagement_value.', '.$result['is_bulk'].')';
					}
					else
					{
					    $query_values .= '('.$creative_id.', NULL, \''.$date_value.'\', '.$engagement_type.', '.$unique_display_id.', '.$engagement_value.', '.$result['is_bulk'].')';
					}
					$g_processing_stats['num_successful_lines'] = $g_processing_stats['num_successful_lines'] + 1;
				}
				elseif($result['success'] == 'ignore error')
				{
					$g_processing_stats['num_ignored_lines'] = $g_processing_stats['num_ignored_lines'] + 1;
					$g_processing_stats['ignored_lines'][] = $data_line;
				}
				else
				{
					$error_string = 'ERROR at line ('.$line_number.') in file ('.$file_name.') with data: '."\n";
					$error_string .= $data_line.$result['err_msg'];
					$g_processing_stats['line_errors'][] = $error_string;
					$is_error = true;
				}
			}

			if($query_values != '')
			{
				$query_string = '
					INSERT IGNORE rs_cdn_raw_engagements 
						(creative_id, raw_source_identifier, time, engagement_type, unique_display_id, value, type_id)
					VALUES
				'.$query_values;

				$g_processing_stats['size_stats']['upload_to_raw_database_table'] += strlen($query_string);

				put_data_in_raw_database($query_string);
			}
			else
			{
				$g_processing_stats['warnings'][] = 'Warning: No aggregated data for file: '.$file_name."\n";
				$g_processing_stats['num_files_without_data'] = $g_processing_stats['num_files_without_data'] + 1;
				$without_data_index = count($g_processing_stats['num_objects_without_data_per_date']) - 1;
				$without_data_count = $g_processing_stats['num_objects_without_data_per_date'][$without_data_index];
				$g_processing_stats['num_objects_without_data_per_date'][$without_data_index] = $without_data_count + 1;
			}

			if($is_error)
			{
				$g_processing_stats['num_files_with_error_line'] = $g_processing_stats['num_files_with_error_line'] + 1;
				$g_processing_stats['file_names_with_error_line'][] = $file_name;
			}
			else
			{
				$g_processing_stats['num_successful_files'] = $g_processing_stats['num_successful_files'] + 1;
			}

			fclose($file_handle);
		}
		else
		{
			$g_processing_stats['num_failed_files'] = $g_processing_stats['num_failed_files'] + 1;
			$error_string = 'Error: Uable to open file ('.$file_name.')';
			$g_processing_stats['failed_file_names'][] = $error_string;
		}
	}

	function aggregate_raw_data_and_upload($start_time, $end_time)
	{
		global $g_raw_database;
		global $g_processing_stats;

		$start_date = date("Y-m-d", $start_time);
		$end_date = date("Y-m-d", $end_time);

		// get aggregated data
		$aggregate_raw_data_query = '
			SELECT creative_id, engagement_type, DATE(time) AS day, COUNT(*) AS total, value, raw_source_identifier FROM
			(
				SELECT creative_id, MAX(time) AS time, engagement_type, MAX(value) AS value, raw_source_identifier
				FROM rs_cdn_raw_engagements                                           
				GROUP BY creative_id, engagement_type, unique_display_id
			) AS a
			WHERE                                                                 
				time BETWEEN \''.$start_date.' 00:00:00\' AND \''.$end_date.' 23:59:59\'
			GROUP BY creative_id, engagement_type, day, value
		';

		$aggregation_result = $g_raw_database->query($aggregate_raw_data_query);
		if(!$aggregation_result)
		{
			$error_string = "Failed to aggregate raw data: (".$g_raw_database->errno.") ".$g_raw_database->error;
			throw(new engagement_exception($error_string));
		}

		// build upload string
		$upload_data = '';
		$is_first_loop = true;
		while($row = $aggregation_result->fetch_assoc())
		{
			if($row['creative_id'] == null)
			{
			    $g_processing_stats['aggregation_errors'][] = $row['raw_source_identifier'];
			    continue;
			}

			if(!$is_first_loop)
			{
				$upload_data .= ', ';
			}
			else
			{
				$is_first_loop = false;
			}
			$upload_data .= '('.$row['creative_id'].', '.$row['engagement_type'].', \''.$row['day'].'\', '.$row['total'].', '.$row['value'].')';
			$g_processing_stats['num_aggregated_rows'] = $g_processing_stats['num_aggregated_rows'] + 1;
		}
		
		if($upload_data != '')
		{
			// upload to final database
			$upload_processed_data_query = '
				REPLACE INTO engagement_records 
				(
					creative_id, 
					engagement_type, 
					date,
					total,
					value
				)
				VALUES
			'.$upload_data;


			$g_processing_stats['size_stats']['upload_to_final_database_table'] += strlen($upload_processed_data_query);

			global $g_main_database;
			connect_to_main_database();
			if($g_main_database->query($upload_processed_data_query))
			{
				$g_processing_stats['is_aggregated_upload_successful'] = true;
			}
			else
			{
				$error_string = "Failed to update raw data with aggregate data: (".$g_main_database->errno.") ".$g_main_database->error;
				throw(new engagement_exception($error_string));
			}
			disconnect_from_database($g_main_database);
		}
		else
		{
			$g_processing_stats['warnings'][] = 'Warning: No aggregated data, nothing put in target database'."\n";
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
			'text' => $message
		);

		$curl_options = array(
			CURLOPT_URL => 'https://api.mailgun.net/v2/mailgun.vantagelocaldev.com/messages',
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

	function send_email($message = '')
	{
		global $g_processing_stats;
		global $g_save_file_path;
		global $g_main_database_host_name;
		global $g_main_database_user;
		global $g_main_database_name;

		$to_email_address = 'Tech Logs <tech.logs@frequence.com>';
		$from_email_address = 'Engagement Uploader <tech.logs@frequence.com>'; 		

		if($message != '')
		{
			$subject_line = 'Upload Engagement Records: ('.date('m/d/Y H:i:s').') special message';
			$is_mail_sent = mailgun(
				$from_email_address,
				$to_email_address,
				$subject_line,
				$message
			);

			if(!$is_mail_sent)
			{
				die('Failed to send mail.'."\n");
			}
		}
		else
		{
			$subject_line = 'Upload Engagement Records: Completed ('.date('m/d/Y H:i:s').') : ';

			if(count($g_processing_stats['errors']) > 0)
			{
				$subject_line .= 'has fundamental system errors';
			}
			elseif($g_processing_stats['num_failed_files'] > 0)
			{
				$subject_line .= 'has file load errors';
			}
			elseif(count($g_processing_stats['line_errors']) > 0)
			{
				$subject_line .= 'has line processing errors';
			}
			elseif(count($g_processing_stats['aggregation_errors']) > 0)
			{
				$subject_line .= 'has aggregation errors';
			}
			elseif(count($g_processing_stats['warnings']) > 0)
			{
				$subject_line .= 'has warnings';
			}
			else
			{
				$subject_line .= 'finished successfully';
			}

			$message = 'Processed dates:';
			$message .= ' '.$g_processing_stats['start_date'];
			$message .= ' - '.$g_processing_stats['end_date'];
			$message .= "\n";
			$message .= 'Processed raw files: '.$g_processing_stats['num_successful_files'].'/'.$g_processing_stats['num_files_processed'].' successfully';
			if($g_processing_stats['num_failed_files'] > 0 || $g_processing_stats['num_files_with_error_line'] > 0)
			{
				$message .= ', failure to open '.$g_processing_stats['num_failed_files'].' files, line errors in '.$g_processing_stats['num_files_with_error_line'].' files';
			}
			$message .= "\n";

			$message .= 'Processed raw lines: '.number_format($g_processing_stats['num_successful_lines']).'/'.number_format($g_processing_stats['num_successful_lines']+$g_processing_stats['num_ignored_lines']+count($g_processing_stats['line_errors'])).' contained data';
			if(count($g_processing_stats['line_errors']) > 0)
			{
				$message .= ', '.number_format($g_processing_stats['num_ignored_lines']).' ignored';
				$message .= ', '.number_format(count($g_processing_stats['line_errors'])).' errors';
			}
			$message .= "\n";

			if($g_processing_stats['is_aggregated_upload_successful'])
			{
				$message .= 'Uploaded '.number_format($g_processing_stats['num_aggregated_rows']).' rows of aggregated data';
			}
			else
			{
				$message .= 'No aggregated data uploaded';
			}
			
			$message .= "\n";
			
			$num_aggregation_errors = count($g_processing_stats['aggregation_errors']);
			if($num_aggregation_errors > 0)
			{
			    $message .= " - Total number of aggregation errors: ".$num_aggregation_errors;
			}
			else
			{
			    $message .= " - No aggregation errors.";
			}
			
			$message .= "\n";

			$message .= 'Processing time: '.number_format($g_processing_stats['time_stats']['total_execution_time']).' seconds';
			$message .= "\n";

			$download_size = number_format($g_processing_stats['size_stats']['total_data_downloaded']).' bytes';
			$processed_file_size = number_format($g_processing_stats['size_stats']['total_data_file_size']).' bytes';
			$upload_raw_database = number_format($g_processing_stats['size_stats']['upload_to_raw_database_table']).' bytes';
			$upload_final_database = number_format($g_processing_stats['size_stats']['upload_to_final_database_table']).' bytes';
			$message .= 'Processing data size: '."\n".
				' - Downloaded('.$download_size.')'."\n".
				' - Parsed('.$processed_file_size.')'."\n".
				' - Upload intermediate database('.$upload_raw_database.')'."\n".
				' - Upload final database('.$upload_final_database.')'."\n"
			;

			$message .= "\n";
			$message .= 'Target database: '.$g_main_database_host_name.'::'.$g_main_database_name."\n";
			$hostname = gethostname();
			if($hostname == false)
			{
			    $hostname = "Unknown";
			}
			$message .= "Hostname: ".$hostname."\n\n";
			/* for debugging
			$igrnored_messages = '';
			$num_ignored_lines = $g_processing_stats['num_ignored_lines'];
			if($num_ignored_lines > 0)
			{
				$igrnored_messages .= 'Ignored Lines: '.$num_ignored_lines."\n";
				foreach($g_processing_stats['ignored_lines'] as $ignored_line)
				{
					$igrnored_messages .= ' - '.$ignored_line;
				}
			}
			$message .= $igrnored_messages;
			*/

			$error_messages = '';
			$num_errors = count($g_processing_stats['errors']);
			if( $num_errors > 0)
			{
				$limit = 100;

				$error_messages .= "\n\n";
				$error_messages .= 'total number of major errors: '.$num_errors."\n";
				$error_messages .= 'showing: '.min($num_errors, $limit+1)."\n";
				$error_messages .= "\n";

				$index = 0;
				foreach($g_processing_stats['errors'] as $error)
				{
					$index++;
					$error_messages .= 'major error #: '.$index."\n";
					$error_messages .= $error."\n";
					if($index >= $limit)
					{
						if($num_errors > $limit)
						{
							$error = $g_processing_stats['errors'][$num_errors - 1];
							$error_messages .= '...skipping errors...'."\n";
							$error_messages .= 'major error #: '.$num_errors."\n";
							$error_messages .= $error."\n";
						}

						break;
					}
				}
			}
			$message .= $error_messages;

			$num_failed_file_names = count($g_processing_stats['failed_file_names']);
			if($num_failed_file_names > 0 )
			{
				$message .= "\n\n";
				$message .= 'Failure while opening '.$num_failed_file_names.' files:'."\n";
				foreach($g_processing_stats['failed_file_names'] as $failed_file_name)
				{
					$message .= ' - '.$failed_file_name."\n";
				}
			}

			$num_file_names_with_error_line = count($g_processing_stats['file_names_with_error_line']);
			if($num_file_names_with_error_line  > 0 )
			{
				$message .= "\n\n";
				$message .= 'Failure in some lines in '.$num_file_names_with_error_line.' files:'."\n";
				foreach($g_processing_stats['file_names_with_error_line'] as $failed_file_name)
				{
					$message .= ' - '.$failed_file_name."\n";
				}
			}
			
			$num_line_errors = count($g_processing_stats['line_errors']);
			if( $num_line_errors > 0)
			{
				$limit = 100;

				$message .= "\n\n";
				$message .= 'total number of line errors: '.$num_line_errors."\n";
				$message .= 'showing: '.min($num_line_errors, $limit+1)."\n";
				$message .= "\n";

				$index = 0;
				foreach($g_processing_stats['line_errors'] as $error)
				{
					$index++;
					$message .= 'line error #: '.$index."\n";
					$message .= $error."\n";
					if($index >= $limit)
					{
						if($num_line_errors > $limit)
						{
							$error = $g_processing_stats['line_errors'][$num_line_errors - 1];
							$message .= '...skipping errors...'."\n";
							$message .= 'line error #: '.$num_line_errors."\n";
							$message .= $error."\n";
						}

						break;
					}
				}
			}
			if($num_aggregation_errors > 0)
			{
			    	$limit = 100;
			    	$message .= "\n\n";
				$message .= 'total number of aggregation errors: '.$num_aggregation_errors."\n";
				$message .= "\n";
				
				foreach($g_processing_stats['aggregation_errors'] as $idx=>$bad_identifier)
				{
					$displayed_index = $idx+1;
					if($displayed_index <= $limit)
					{
						$message .= "Mismatched Identifier #".$displayed_index.": ".$bad_identifier."\n";
					}
					else
					{
					    break;
					}
				}
				$message .= "\n";
			}

			$warning_messages = '';
			$num_warnings = count($g_processing_stats['warnings']);
			if($num_warnings > 0)
			{
				$warning_messages .= 'Warnings: '.$num_warnings."\n";
				foreach($g_processing_stats['warnings'] as $warning)
				{
					$warning_messages .= ' - '.$warning;
				}
			}
			$message .= $warning_messages;

			$message .= "\n";
			$message .= 'path: '.$g_save_file_path."\n";

			$is_mail_sent = mailgun(
				$from_email_address,
				$to_email_address,
				$subject_line,
				$message
			);

			// error_signal matches error_signal_string in handle_cdn_upload_errors.php, if you change this you must update that.
			$error_signal = '0 no error signal';
			$command_line_message = '';
			$command_line_message .= $error_signal."\n";
			if(!$is_mail_sent)
			{
				$command_line_message .= "\n";
				$command_line_message .= 'Failed to send mail.'."\n";
			}

			$command_line_message .= "\n";
			if($error_messages != '')
			{
				$command_line_message .= 'processing done: major errors occured'."\n";
			}
			else
			{
				$command_line_message .= 'processing done: no major errors occured'."\n";
			}
			$command_line_message .= "\n";

			if($warning_messages != '')
			{
				$command_line_message .= $num_warnings.' warnings encountered'."\n";
			}

			if($num_failed_file_names > 0)
			{
				$command_line_message .= $num_failed_file_names.' file access errors'."\n";
			}

			if($num_line_errors > 0)
			{
				$command_line_message .= $num_line_errors.' total line errors'."\n";
			}
			
			if($num_file_names_with_error_line > 0)
			{
				$command_line_message .= $num_file_names_with_error_line.' files with line errors'."\n";
			}
			
			if($num_warnings > 0)
			{
				$command_line_message .= "\n";
				$command_line_message .= 'warning details...'."\n";
				$command_line_message .= $warning_messages;
			}
	
			if($error_messages != '')
			{
				$command_line_message .= "\n";
				$command_line_message .= 'major error message details...'."\n";
				$command_line_message .= $error_messages;
			}
			$command_line_message .= "\n";

			echo $command_line_message;
		}
	}


	function initialize_stats()
	{
		global $g_processing_stats;
		$g_processing_stats = array();
		$g_processing_stats['num_files_processed'] = 0;
		$g_processing_stats['num_objects_per_date'] = array();
		$g_processing_stats['num_successful_lines'] = 0;
		$g_processing_stats['num_ignored_lines'] = 0;
		$g_processing_stats['ignored_lines'] = array();
		$g_processing_stats['errors'] = array();
		$g_processing_stats['warnings'] = array();
		$g_processing_stats['line_errors'] = array();
		$g_processing_stats['num_successful_files'] = 0;
		$g_processing_stats['num_failed_files'] = 0;
		$g_processing_stats['failed_file_names'] = array();
		$g_processing_stats['num_files_with_error_line'] = 0;
		$g_processing_stats['file_names_with_error_line'] = array();
		$g_processing_stats['num_aggregated_rows'] = 0;
		$g_processing_stats['is_aggregated_upload_successful'] = false;
		$g_processing_stats['num_files_without_data'] = 0;
		$g_processing_stats['num_objects_without_data_per_date'] = array();
		$g_processing_stats['aggregation_errors'] = array();

		$g_processing_stats['time_stats'] = array();
		$g_processing_stats['time_stats']['total_execution_time'] = 0;

		$g_processing_stats['size_stats'] = array();
		$g_processing_stats['size_stats']['total_data_downloaded'] = 0;
		$g_processing_stats['size_stats']['total_data_file_size'] = 0;
		$g_processing_stats['size_stats']['upload_to_raw_database_table'] = 0;
		$g_processing_stats['size_stats']['upload_to_final_database_table'] = 0;

	}

	function cdn_operation_with_retry(callable $callable, $parameters = null)
	{
		$max_retries = 4;

		$result = null;

		$function_name = '';
		if(is_string($callable))
		{
			$function_name = $callable;
		}
		elseif(is_string($callable[1]))
		{
			$function_name = $callable[1];
		}

		$is_call_success = false;
		$retries = 0;

		while(!$is_call_success && $retries <= $max_retries)
		{
			try
			{
				$result = call_user_func_array($callable, $parameters);
				$is_call_success = true;
			}
			catch(Exception $exception)
			{
				$message = $exception->getMessage();
				echo("CDN call failed retry #$retries failed for function '$function_name()': $message\n");
				sleep(2);

				$retries++;
			}
		}

		if(!$is_call_success)
		{
			echo("CDN call failed $retries times for function '$function_name()'\n");
			print_r($parameters);

			throw new Exception("Failed to execute CDN function '$function_name()'\n");
		}

		return $result;
	}


	require_once 'cloudfiles.php';

	function upload_cdn_data()
	{
		global $g_processing_stats;
		global $g_save_file_path;
		global $g_start_date;
		global $g_end_date;

		$rackspace_user = "localbranding"; // this is your rackspace user name
		$rackspace_api_key = "fadf29c3cfe25170ffabf4898d9de9e4"; // get it from my account tab
		// Lets connect to Rackspace
		$authentication = new CF_Authentication($rackspace_user, $rackspace_api_key);
		$authentication->authenticate();
		$connection = null;
		try {
			$connection = new CF_Connection($authentication);    
		}
		catch(AuthenticationException $e) {
			$error_string = "Unable to authenticate ".$e->getMessage();
			throw(new engagement_exception($error_string));
		}

		$container = null;  
		// create a new container if you like uncomment the line below 
		// $container = new CF_Container($authentication,$connection,"testcontainer"); 
		// Or use an already exsting container
		// $container = $connection->get_container('testcontainer');
		// or better way to handle this according to me is
		try
		{
				$container = $connection->get_container('.CDN_ACCESS_LOGS');
		}
		catch(NoSuchContainerException $e) {
			$error_string = "Container doesn't exist ".$e->getMessage();
			throw(new engagement_exception($error_string));
		}
		catch(InvalidResponseException $res) {
			// let your users know or try again or just store the file locally and try again later to push it to the Cloud
			$error_string = "Invalid response".$res->getMessage();
			throw(new engagement_exception($error_string));
		}

		$base_start_date = $g_start_date;
		$base_end_date = $g_end_date;

		$start_time = strtotime($base_start_date);
		$end_time = strtotime($base_end_date);
		$target_time = $start_time;
		$previous_date = date("Y/m/d", strtotime("-1 day", $start_time));
		$next_date = date("Y/m/d", strtotime("+1 day", $end_time));

		$g_processing_stats['start_date'] = date("Y-m-d", $start_time);
		$g_processing_stats['end_date'] = date("Y-m-d", $end_time);

		$file_prefix = 'analytics/';
		$objects_list_by_date = array();

		// get the last 3 files from the previous day (it's just a guess that this will cover the overflow)
		$num_to_keep = 3;
		$full_previous_names_list = cdn_operation_with_retry(array($container, 'list_objects'), array(0, NULL, $file_prefix.$previous_date));
		sort($full_previous_names_list);
		$previous_names_list = array_slice($full_previous_names_list, -$num_to_keep);
		if(count($previous_names_list) <= 0)
		{
			$error_string = 'Did not find any files for previous day: '.$previous_date."\n";
			throw(new engagement_exception($error_string));
		}
		$objects_list_by_date[] = $previous_names_list;

		while($target_time <= $end_time)
		{
			$target_date = date("Y/m/d", $target_time);
			$names_list = cdn_operation_with_retry(array($container, 'list_objects'), array(0, NULL, $file_prefix.$target_date));
			if(count($names_list) <= 0)
			{
				$error_string = 'Did not find any files for target day: '.$target_date."\n";
				$g_processing_stats['errors'][] = $error_string;
				//throw(new engagement_exception($error_string));
			}
			$objects_list_by_date[] = $names_list;
			$target_time = strtotime("+1 day", $target_time);
		}	
		
		// get the first 3 files from the next day (it's just a guess that this will cover the overflow)
		$full_next_names_list = cdn_operation_with_retry(array($container, 'list_objects'), array(0, NULL, $file_prefix.$next_date));
		sort($full_next_names_list);
		$next_names_list = array_slice($full_next_names_list, 0, $num_to_keep);
		if(count($next_names_list) <= 0)
		{
			$error_string = 'Did not find any files for following day: '.$next_date."\n";
			throw(new engagement_exception($error_string));
		}
		$objects_list_by_date[] = $next_names_list;

		connect_to_raw_database();
		clear_all_raw_data();

		// upload all valid file data
		foreach($objects_list_by_date as $objects_index=>$objects_by_date)
		{
			$num_objects_for_this_date = count($objects_by_date);
			$g_processing_stats['num_objects_per_date'][] = $num_objects_for_this_date;
			$g_processing_stats['num_objects_without_data_per_date'][] = 0;

			// upload files by date group
			foreach($objects_by_date as $object_name)
			{
				$object = cdn_operation_with_retry(array($container, 'get_object'), array($object_name));
				$g_processing_stats['size_stats']['total_data_downloaded'] += $object->content_length;
				$save_file_name = str_replace('/', '_', $object->name);
				$file_name_and_path = $g_save_file_path.$save_file_name;
				$uncompressed_file_name = str_replace('.gz', '', $file_name_and_path);
				if(
					!file_exists($file_name_and_path) &&
					!file_exists($uncompressed_file_name)
				)
				{
					if(!file_exists($file_name_and_path))
					{
						try
						{
							cdn_operation_with_retry(array($object, 'save_to_filename'), array($file_name_and_path));
						}
						catch(Exception $exception)
						{
							echo("Failed to download file from CDN $file_name_and_path\n");
							throw $exception;
						}
					}
					if(!file_exists($uncompressed_file_name))
					{
						shell_exec('gunzip -f '.$file_name_and_path);
					}
				}

				$g_processing_stats['size_stats']['total_data_downloaded'] += $object->content_length;
				$g_processing_stats['size_stats']['total_data_file_size'] += filesize($uncompressed_file_name);

				upload_file_to_raw_data($uncompressed_file_name);

				$files_to_remove = $g_save_file_path.'analytics_*.log*';
				
				shell_exec('rm -f '.$files_to_remove);

				$g_processing_stats['num_files_processed'] = $g_processing_stats['num_files_processed'] + 1;
			}

			$without_data_index = count($g_processing_stats['num_objects_without_data_per_date']) - 1;
			if($g_processing_stats['num_objects_without_data_per_date'][$without_data_index] ==
				$num_objects_for_this_date)
			{
				$warning_string = 'Warning: No aggregated data for files in date: '.date("m/d/Y", strtotime('+'.$objects_index.' days', $start_time))."\n";
				$g_processing_stats['warnings'][] = $warning_string;
			}
		}
		determine_creative_ids_for_bulk_campaigns();
		
		aggregate_raw_data_and_upload($start_time, $end_time);

		disconnect_from_raw_database();
	}

	function do_upload()
	{
		global $g_save_file_path;
		global $g_processing_stats;
		global $g_database_info_file;
		
		$start_time = microtime(true);

		initialize_stats();
		$is_config_success = get_database_config($g_database_info_file);

		if($is_config_success)
		{
			try
			{
				upload_cdn_data();
				cache_lifetime_engagements();
			}
			catch(engagement_exception $ex)
			{
				$g_processing_stats['errors'][] = $ex->getMessage();
			}
		}
		else
		{
			$g_processing_stats['errors'][] = 'ERROR: Could not get database configuration, file: '.$g_database_info_file;
		}

		$end_time = microtime(true);
		$g_processing_stats['time_stats']['total_execution_time'] = $end_time - $start_time;

		send_email();	
	}
	
	function connect_to_main_database()
	{
	    global $g_main_database;
	    global $g_main_database_host_name;
	    global $g_main_database_user;
	    global $g_main_database_password;
	    global $g_main_database_name;
	    if($g_main_database == NULL)
	    {
		connect_to_database(
			$g_main_database, 
			$g_main_database_host_name,
			$g_main_database_user,
			$g_main_database_password,
			$g_main_database_name
			);
	    }
	}
	
	function determine_creative_ids_for_bulk_campaigns()
	{
	    global $g_raw_database;
	    global $g_main_database;
	    global $g_main_database_name;
	    global $g_processing_stats;
	    if($g_main_database == NULL)
	    {
		connect_to_main_database();
	    }
	    
	    $grab_identifiers_query = "SELECT DISTINCT raw_source_identifier FROM rs_cdn_raw_engagements WHERE type_id = 1";
	    
	    $gather_identifiers_result = $g_raw_database->query($grab_identifiers_query);
	    if($gather_identifiers_result != FALSE)
	    {
		$first_loop = true;
		$query_values = '';
		while($row = $gather_identifiers_result->fetch_assoc())
		{
		    $dsp = "";
		    $ttd_creative_id = "";
		    $ttd_campaign_id = "";
		    
		    $raw_identifiers = explode('-',$row['raw_source_identifier']);
		    
		    if(count($raw_identifiers) != 3)
		    {
			continue;
		    }
		    $dsp = $raw_identifiers[0];
		    $ttd_creative_id = $raw_identifiers[1];
		    $ttd_campaign_id = $raw_identifiers[2];
		    if($ttd_creative_id == "" || $ttd_campaign_id == "")
		    { 
			continue;
		    }
		    if(!$first_loop)
		    {
			$query_values .= ', ';
		    }
		    else
		    {
			$first_loop = false;
		    }
		    $query_values .= "('".$g_raw_database->real_escape_string($row['raw_source_identifier'])."', '".$g_raw_database->real_escape_string($dsp)."', '".$g_raw_database->real_escape_string($ttd_creative_id)."', '".$g_raw_database->real_escape_string($ttd_campaign_id)."')";
		    
		}
		if($query_values == '')
		{
		    return;
		}
		
		//Create temp table
		$create_temp_table_query = "CREATE TEMPORARY TABLE tmp_bulk_mapping (
						    creative_id INT(11) NOT NULL DEFAULT 0,
						    raw_source_identifier VARCHAR(64) DEFAULT NULL COLLATE utf8_unicode_ci,
						    dsp VARCHAR(5) DEFAULT NULL,
						    ttd_creative_id VARCHAR(32) DEFAULT NULL COLLATE utf8_unicode_ci,
						    ttd_campaign_id VARCHAR(32) DEFAULT NULL COLLATE utf8_unicode_ci
					    )";
		$make_temp_table_result = $g_raw_database->query($create_temp_table_query);
		
		$insert_identifier_info = "INSERT INTO tmp_bulk_mapping 
				(raw_source_identifier, dsp, ttd_creative_id, ttd_campaign_id) VALUES ".$query_values;
		    
		$insert_identifier_result = $g_raw_database->query($insert_identifier_info);
		if($insert_identifier_result == FALSE)
		{
			$error_string = "Failed to translate ttd creative data: (".$g_raw_database->errno.") ".$g_raw_database->error;
			throw(new engagement_exception($error_string));
		}		    
		
		$get_creative_id_query = "UPDATE tmp_bulk_mapping bulkmap SET bulkmap.creative_id = (
				SELECT 
				    cre.id
				FROM ".$g_main_database_name.".cup_creatives AS cre
				JOIN ".$g_main_database_name.".cup_versions AS ver
				    ON (cre.version_id = ver.id)
				JOIN ".$g_main_database_name.".Campaigns AS cmp
				    ON (ver.campaign_id = cmp.id)
				WHERE
				    cre.ttd_creative_id = bulkmap.ttd_creative_id
				    AND cmp.ttd_campaign_id = bulkmap.ttd_campaign_id
				    ORDER BY
					cre.id DESC
				    LIMIT 1
				)";		
		
		$get_creative_id_result = $g_raw_database->query($get_creative_id_query);
		if(!$get_creative_id_result)
		{
			$error_string = "Failed to locate ttd creative data: (".$g_raw_database->errno.") ".$g_raw_database->error;
			throw(new engagement_exception($error_string));
		}
		    
		$update_raw_table_creative_ids_query = "
		    UPDATE rs_cdn_raw_engagements AS rawe 
		    SET rawe.creative_id = 
		    (SELECT map.creative_id
		    FROM tmp_bulk_mapping AS map
		    WHERE rawe.raw_source_identifier = map.raw_source_identifier) WHERE rawe.type_id = 1";
		
		$update_raw_table_creatives_result = $g_raw_database->query($update_raw_table_creative_ids_query);
		if($update_raw_table_creatives_result == false)
		{
		    $error_string = "Failed to update raw engagement creative data: (".$g_raw_database->errno.") ".$g_raw_database->error;
		    throw(new engagement_exception($error_string));		    
		}
		
		$drop_temp_table_query = "DROP TABLE tmp_bulk_mapping";
		$drop_temp_table_result = $g_raw_database->query($drop_temp_table_query);
		if($drop_temp_table_result == false)
		{
		    $error_string = "Failed to drop temp bulk mapping table: (".$g_raw_database->errno.") ".$g_raw_database->error;
		    throw(new engagement_exception($error_string));	   
		}
	    }
	}

	function cache_lifetime_engagements()
	{
		$caching_response = shell_exec("php ../../public/index.php campaigns_main cache_lifetime_engagements");

		$to = 'Tech Logs <tech.logs@frequence.com>';
		$from = 'Engagement Uploader <tech.logs@frequence.com>';
		$subject = "Caching Lifetime Engagements: Completed (".date('m/d/Y H:i:s').")";
		$message = $caching_response;

		return mailgun($from, $to, $subject, $message);
	}

	do_upload();
