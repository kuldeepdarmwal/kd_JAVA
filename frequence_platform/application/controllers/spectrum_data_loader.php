<?php

/*
 * Copies videos from one S3 bucket, prepare them for an HTML5 player, and copy the resulting files to another S3 bucket.
 */

class Spectrum_data_loader extends CI_Controller
{
	// TODO: change protected to private; this class is not intended to be inherited
	protected $start_time;
	protected $run_time;
	protected $working_dir;
	protected $working_files = [];
	protected $options = [
		'thumbnail_time_offset'             => VIDEO_THUMBNAIL_OFFSET,
		'timeout'                           => VIDEO_CONVERSION_TIMEOUT,
		'default_video_size'                => VIDEO_OUTPUT_SIZE,
		'video_bitrate'                     => VIDEO_OUTPUT_V_BITRATE,
		'audio_bitrate'                     => VIDEO_OUTPUT_A_BITRATE,
		'video_codecs' => [
			'mp4'  => MP4_VIDEO_CODEC,
			'webm' => WEBM_VIDEO_CODEC,
		],
		'audio_codecs' => [
			'mp4'  => MP4_AUDIO_CODEC,
			'webm' => WEBM_AUDIO_CODEC,
		],
		'working_dirname'                   => 'video_processing',
		'tmp_dir'                           => TMPDIR,
		'receiving_bucket'                  => VIDEO_RECEIVING_BUCKET,
		'receiving_path'                    => VIDEO_RECEIVING_PATH,
		'output_bucket'                     => VIDEO_OUTPUT_BUCKET,
		'output_path'                       => VIDEO_OUTPUT_PATH,
		'output_thumbnails_path'            => 'thumbnails',
		'output_videos_path'                => 'videos',
		'url_expiration_period'             => '+21 days',
		'clear_working_files_when_finished' => TRUE,
		'conversion_tool'                   => VIDEO_CONVERSION_TOOL,
		'network_demographics_path'			=> 'networks',
		'network_demographics_dir'			=> "/assets/json/tv/network_json/"
	];
	protected $mime_types = [
		'thumbnail' => 'image/jpeg',
		'mp4'       => 'video/mp4',
		'webm'      => 'video/webm',
	];

	public function __construct()
	{
		parent::__construct();
		$this->load->library('vl_aws_services');
		$this->load->library('cli_data_processor_common');
		$this->load->helper('mailgun');
		$this->load->model(['spectrum_tv_model','demo_partner_model']);
		$this->start_time = microtime(true);
	}

	/*
	 * CLI interface to refresh_signed_urls
	 */
	public function cli_refresh_signed_urls()
	{
		if($this->input->is_cli_request())
		{
			return $this->refresh_signed_urls();
		}
		else
		{
			show_404();
		}
	}

	/*
	 * CLI interface to set vidoes with status "no_source_video" to null, so that the video processor will work on them again.
	 */
	public function cli_enqueue_videos_with_missing_source()
	{
		if($this->input->is_cli_request())
		{
			$statuses_to_reset = [Spectrum_tv_model::$NO_SOURCE_VIDEO, Spectrum_tv_model::$INCOMPLETE];
			$new_status = null;
			$number_of_rows_updated = $this->spectrum_tv_model->bulk_update_creative_status($statuses_to_reset, $new_status, Spectrum_tv_model::$THIRD_PARTY_SOURCE_ID);
			if($number_of_rows_updated !== false)
			{
				echo "{$number_of_rows_updated} videos set to reprocess (i.e. status null).\n";
			}
			else
			{
				echo "Error: unable to update video statuses.\n";
			}
		}
		else
		{
			show_404();
		}
	}

	/*
	 * CLI interface to download tv schedule file and load in db
	 *
	 * @param String $manual_date... optional param to run the import for a specific date manually
	 * @returns Array of processed records count
	 */
	public function cli_process_upload_tv_creatives_for_date($manual_date = null)
	{
		if($this->input->is_cli_request())
		{
			$num_errors = 0;
			$num_warnings = 0;
			$message = array();
			$message['basic_info'] = array();

		if($manual_date == null)
		{
			$manual_date = date("Y-m-d", strtotime('-1 day'));
		}
		$uploader_date =  date("Y-m-d",strtotime($manual_date));
		$this->cli_data_processor_common->mark_script_execution_start_time();

		//Not sure where to have put this but hey there you go
		$this->download_network_data();

		$did_upload_tv_schedule = false;
		$message['schedule_upload'] = array();
		$message['schedule_upload'][] = "<u><strong>TV Schedule Upload</strong></u>";
		$upload_tv_schedule_creatives_result = $this->spectrum_tv_model->upload_tv_creatives_for_date($manual_date);
		$upload_tv_demo_schedule_creatives_result = $this->demo_partner_model->upload_tv_creatives_demo_data();
		
		if(!$upload_tv_schedule_creatives_result['is_success'])
		{
			echo $upload_tv_schedule_creatives_result['err_msg']."\n";
			$message['schedule_upload'][] = "<strong>ERROR: {$upload_tv_schedule_creatives_result['err_msg']}</strong>";
			$num_errors++;
		}
		else
		{
			$did_upload_tv_schedule = true;
			$message['schedule_upload'][] = "Schedule rows inserted: {$upload_tv_schedule_creatives_result['spectrum_tv_schedule_rows_inserted']}";
			$message['schedule_upload'][] = "Creative rows inserted: {$upload_tv_schedule_creatives_result['spectrum_tv_creatives_rows_inserted']}";
			$message['schedule_upload'][] = "Previous airings rows generated: {$upload_tv_schedule_creatives_result['spectrum_historic_zones_inserted']}";
			if(!empty($upload_tv_schedule_creatives_result['bad_syscode_strings']))
			{
				$num_warnings++;
				$message['schedule_upload'][] = "<strong>[WARNING] Unmatched Syscodes Found:</strong>";
				foreach($upload_tv_schedule_creatives_result['bad_syscode_strings'] as $bad_syscode)
				{
					$message['schedule_upload'][] = "--{$bad_syscode}";
				}
			}
		}
		
		if($upload_tv_demo_schedule_creatives_result)
		{
			echo 'Demo Creative data is Added in DB';
		}
		else
		{
			echo 'Error occured while Adding in Demo data to DB';
		}

		$message['verified_upload'] = array();
		$message['verified_upload'][] = "<u><strong>Verified Spot Data Upload</strong></u>";
		$verified_data_result = $this->spectrum_tv_model->upload_v2_verified_data_for_date($manual_date);
		if(!$verified_data_result['is_success'])
		{
			echo $verified_data_result['err_msg']."\n";
			$message['verified_upload'][] = "<strong>ERROR: {$verified_data_result['err_msg']}</strong>";
			$num_errors++;
		}
		else
		{
			$message['verified_upload'][] = "Verified data rows inserted: {$verified_data_result['spectrum_tv_verified_rows_inserted']}";
			$message['verified_upload'][] = "Total verified spots: {$verified_data_result['spectrum_tv_verified_temp_rows_inserted']}";
		}

		$this->refresh_signed_urls();

		$message['misc_post_checks'] = array();
		if($did_upload_tv_schedule)
		{
			$get_mismatched_accounts_result = $this->spectrum_tv_model->get_account_names_with_no_account();
			if($get_mismatched_accounts_result === false)
			{
				$message['misc_post_checks'][] = "<strong>[WARNING] Failed to get account names with TV data and no account</strong>";
				$num_warnings++;
			}
			else
			{
				if(!empty($get_mismatched_accounts_result))
				{
					$num_warnings++;
					$message['misc_post_checks'][] = "<strong>[WARNING] Accounts found that have TV data but no matching Spectrum account:</strong>";
					foreach($get_mismatched_accounts_result as $mismatched_account_row)
					{
						$message['misc_post_checks'][] = "--{$mismatched_account_row['account_name']} ({$mismatched_account_row['ul_id']})";
					}
				}
			}
		}

			$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message_with_time();

			$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
			$message = nl2br(implode("\n\n", $message));

		$from = "TV Schedule Loader <noreply@frequence.com>";
		$to = "Tech Logs <tech.logs@frequence.com>";
		if(!$num_errors)
		{
			$subject = "Successfully loaded TV Schedule Data ({$uploader_date})";
		}
		else
		{
			$subject = "[ERROR] {$num_errors} errors loading TV Schedule Data ({$uploader_date})";
		}

			if($num_warnings)
			{
				$subject .= " with {$num_warnings} warnings";
			}
			$this->send_mailgun_email($from, $to, $subject, $message, 'html');
		}
		else
		{
			show_404();
		}
	}

	/*
	 * @returns associative array of $spot_id => 'working_files' => [], 'output_files' => [], 'errors' => []
	 *
	 *   $working_files: associative array of $description => $file
	 *     $description: 'thumbnail', 'mp4', or 'webm'
	 *     $file: associative array:
	 *       mime_type  => String MIME type (e.g. 'image/jpeg')
	 *       local_path => String path to temporary file
	 *       bucket     => String S3 bucket
	 *       name       => String file key at S3
	 *       signedUrl  => String temprary signed URL
	 *
	 *   $output_files: same as working_files, but only contains files which completed every step
	 *
	 *   $errors: array String messages
	 */
	public function process_videos($disregard_timeout = false)
	{
		if($active_processes = $this->cli_data_processor_common->get_active_processes(__METHOD__))
		{
			$num_processes = count($active_processes['processes']);
			$num_cores = CPU_CORES;
			if($num_processes >= $num_cores)
			{
				exit;
			}
		}
		$results = [];

		if(!$this->prep_working_dir())
		{
			$this->cli_data_processor_common->cron_log("video working directory can't be prepared");
			return $results;
		}

		while(!$this->check_timeout() || $disregard_timeout)
		{
			if(!$video_to_process = $this->spectrum_tv_model->get_next_video_creative_to_be_processed())
			{
				$this->cli_data_processor_common->cron_log("no more spots need processing");
				break;
			}

			if(!$video_key = $this->get_video_key_from_s3($video_to_process['third_party_video_id']))
			{
				$this->spectrum_tv_model->update_creative_status($video_to_process['id'], $video_to_process['third_party_video_id'], $video_to_process['third_party_source_id'], Spectrum_tv_model::$NO_SOURCE_VIDEO);
				continue;
			}

			if(!$downloaded_file = $this->download_video($video_key))
			{
				$this->spectrum_tv_model->update_creative_status($video_to_process['id'], $video_to_process['third_party_video_id'], $video_to_process['third_party_source_id'], Spectrum_tv_model::$NO_SOURCE_VIDEO);
				continue;
			}

			$working_files = [];
			$output_files = [];
			$errors = [];

			// only queue up for processing files for which we don't have links
			if(empty($video_to_process['link_thumb']))
			{
				$working_files['thumbnail'] = [];
			}
			if(empty($video_to_process['link_mp4']))
			{
				$working_files['mp4'] = [];
			}
			if(empty($video_to_process['link_webm']))
			{
				$working_files['webm'] = [];
			}

			foreach($working_files as $description => &$file_info)
			{
				$file_info['mime_type'] = $this->mime_types[$description];

				$prefix = $this->options['output_path'];
				if($description == 'thumbnail')
				{
					$prefix = $this->join_path_parts(array($prefix, $this->options['output_thumbnails_path']));
					$local_path = $this->get_video_thumbnail($downloaded_file);
				}
				else
				{
					$prefix = $this->join_path_parts(array($prefix, $this->options['output_videos_path']));
					$local_path = $this->get_scaled_video($downloaded_file, $description);
				}
				if(!$local_path)
				{
					$errors[] = $this->cli_data_processor_common->cron_log("failed to create $description for spot {$video_to_process['third_party_video_id']}");
				}

				$file_info['bucket'] = $this->options['output_bucket'];
				$file_info['name'] = $this->upload_local_file($local_path, $prefix, $file_info['mime_type']);
				if(!$file_info['name'])
				{
					$errors[] = $this->cli_data_processor_common->cron_log("failed to upload $description for spot {$video_to_process['third_party_video_id']}");
				}
				if(empty($errors))
				{
					$output_files[$description] = $file_info;
				}
			}

			$all_working_files_cleared = false;
			if($this->options['clear_working_files_when_finished'])
			{
				$all_working_files_cleared = $this->clear_all_working_files();
			}

			if(!empty($output_files))
			{
				$output_files = $this->vl_aws_services->grant_temporary_access($output_files, $this->options['url_expiration_period']);

				$status = (count($output_files) === count($working_files) ? Spectrum_tv_model::$COMPLETE : Spectrum_tv_model::$INCOMPLETE);

				$spot_info = [
					'files' => $output_files,
					'status' => $status,
				];

				// add back in files which were already processed
				$spot_info['video_creative_id'] = $video_to_process['id'];
				if(empty($spot_info['files']['mp4']))
				{
					$spot_info['files']['mp4']['signedUrl'] = $video_to_process['link_mp4'];
				}
				if(empty($spot_info['files']['webm']))
				{
					$spot_info['files']['webm']['signedUrl'] = $video_to_process['link_webm'];
				}
				if(empty($spot_info['files']['thumbnail']))
				{
					$spot_info['files']['thumbnail']['signedUrl'] = $video_to_process['link_thumb'];
				}

				$this->save_video_creative_info([$video_to_process['third_party_video_id'] => $spot_info]);
			}

			$results[$video_to_process['id']] = [
				'work' => $working_files,
				'output' => $output_files,
				'errors' => $errors,
				'all_working_files_cleared' => $all_working_files_cleared,
			];
		}

		return $results;
	}

	public function cli_load_tv_impressions($manual_date = null)
	{
		if(!$this->input->is_cli_request())
		{
			show_404();
		}

		$num_errors = 0;
		$num_warnings = 0;
		$message = array();
		$message['basic_info'] = array();

		if($manual_date == null)
		{
			$manual_date = date("Y-m-d", strtotime('-1 day'));
		}
		$uploader_date = date("Y-m-d", strtotime($manual_date));
		$this->cli_data_processor_common->mark_script_execution_start_time();

		$message['verified_upload'] = array();
		$message['verified_upload'][] = "<u><strong>Verified Spot Data Upload</strong></u>";
		$verified_data_result = $this->spectrum_tv_model->upload_v2_verified_data_for_date($manual_date);
		if($verified_data_result['is_success'])
		{
			$message['verified_upload'][] = "Verified data rows inserted in temp table: {$verified_data_result['spectrum_tv_verified_temp_rows_inserted']}";
			$message['verified_upload'][] = "Verified data rows inserted in table: {$verified_data_result['spectrum_tv_verified_rows_inserted']}";
		}
		else
		{
			echo "{$verified_data_result['err_msg']}\n";
			$message['verified_upload'][] = "<strong>ERROR: {$verified_data_result['err_msg']}</strong>";
			$num_errors++;
		}

		$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message_with_time();

		$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
		$message = nl2br(implode("\n\n", $message));

		$from = "Verify V2 Data Loader <noreply@frequence.com>";
		$to = "Tech Logs <tech.logs@frequence.com>";
		if(!$num_errors)
		{
			$subject = "Successfully loaded Verify V2 Data ({$uploader_date})";
		}
		else
		{
			$subject = "[ERROR] {$num_errors} errors loading Verify V2 Data ({$uploader_date})";
		}

		if($num_warnings)
		{
			$subject .= " with {$num_warnings} warnings";
		}
		$this->send_mailgun_email($from, $to, $subject, $message, 'html');
	}

	/*
	 * @returns Array indexed by file description
	 */
	private function refresh_signed_urls()
	{
		echo "Getting video records ... ";
		$this->benchmark->mark('get_videos_in_database_start');
		$video_creative_ids_by_third_party_video_id = $this->spectrum_tv_model->get_video_creative_ids_indexed_by_third_party_video_id(Spectrum_tv_model::$COMPLETE);

		if(empty($video_creative_ids_by_third_party_video_id))
		{
			echo "No video records";
			return;
		}
		$this->benchmark->mark('get_videos_in_database_end');
		echo round(floatval($this->benchmark->elapsed_time('get_videos_in_database_start', 'get_videos_in_database_end')), 1) . " seconds \n";
		 

		$this->benchmark->mark('list_videos_in_s3_start');
		echo "Listing all files in S3 bucket '{$this->options['output_bucket']}' at '/{$this->options['output_path']}' ... ";

		$video_keys = $this->vl_aws_services->get_file_keys(
			$this->options['output_bucket'],
			$this->join_path_parts(array($this->options['output_path'], $this->options['output_videos_path'])) . '/'
		);
		$thumbnail_keys = $this->vl_aws_services->get_file_keys(
			$this->options['output_bucket'],
			$this->join_path_parts(array($this->options['output_path'], $this->options['output_thumbnails_path'])) . '/'
		);

		$file_keys = array_merge($video_keys, $thumbnail_keys);
		$this->benchmark->mark('list_videos_in_s3_end');
		echo round(floatval($this->benchmark->elapsed_time('list_videos_in_s3_start', 'list_videos_in_s3_end')), 1) . " seconds \n";

		$this->benchmark->mark('match_files_to_records_start');
		echo "Matching " . count($file_keys) . " files to " . count($video_creative_ids_by_third_party_video_id) . " video records ... ";

		$files = [];
		foreach($file_keys as &$key)
		{
			$third_party_video_id = $this->get_third_party_video_id_from_video_key($key);
			if(isset($video_creative_ids_by_third_party_video_id[$third_party_video_id]))
			{
				$files[] = [
					'bucket' => $this->options['output_bucket'],
					'video_creative_id' => $video_creative_ids_by_third_party_video_id[$third_party_video_id],
					'third_party_video_id' => $third_party_video_id,
					'name' => $key,
				];
			}
		}

		$matching_file_count = count($files);
		if($matching_file_count == 0)
		{
			echo "No matching files found.";
			return;
		}
		$this->benchmark->mark('match_files_to_records_end');
		echo round(floatval($this->benchmark->elapsed_time('match_files_to_records_start', 'match_files_to_records_end')), 1) . " seconds \n";

		$this->benchmark->mark('request_signed_urls_start');
		echo "Requesting new signed URLs for " . $matching_file_count . " files ... ";

		$files = $this->vl_aws_services->grant_temporary_access($files, $this->options['url_expiration_period']);
		$this->benchmark->mark('request_signed_urls_end');
		echo round(floatval($this->benchmark->elapsed_time('request_signed_urls_start', 'request_signed_urls_end')), 1) . " seconds \n";

		echo "Matching updated file URLs to videos ... ";
		$this->benchmark->mark('place_urls_with_their_records_start');
		$all_spots_info = [];

		foreach($files as &$file_info)
		{
			$third_party_video_id = $file_info['third_party_video_id'];
			$video_creative_id = $file_info['video_creative_id'];
			if(preg_match('/\.jpg$/', $file_info['name']))
			{
				$description = 'thumbnail';
			}
			else if(preg_match('/\.mp4/', $file_info['name']))
			{
				$description = 'mp4';
			}
			else if(preg_match('/\.webm/', $file_info['name']))
			{
				$description = 'webm';
			}
			else
			{
				continue;
			}

			if(!isset($all_spots_info[$third_party_video_id]))
			{
				$all_spots_info[$third_party_video_id] = [
					'video_creative_id' => $video_creative_id,
					'files' => [],
					'status' => Spectrum_tv_model::$COMPLETE
				];
			}
			$file_info['mime_type'] = $this->mime_types[$description];
			$all_spots_info[$third_party_video_id]['files'][$description] = $file_info;
		}
		$this->benchmark->mark('place_urls_with_their_records_end');
		echo round(floatval($this->benchmark->elapsed_time('place_urls_with_their_records_start', 'place_urls_with_their_records_end')), 1) . " seconds \n";

		if(!empty($all_spots_info))
		{
			$this->benchmark->mark('save_new_urls_to_database_start');
			echo "Saving new URLs to " . count($all_spots_info) . " video records ... ";
			$this->save_video_creative_info($all_spots_info);
			$this->benchmark->mark('save_new_urls_to_database_end');
			echo round(floatval($this->benchmark->elapsed_time('save_new_urls_to_database_start', 'save_new_urls_to_database_end')), 1) . " seconds \n";
		}
		else
		{
			echo "No updated info to save.\n";
		}

		echo "Done.\n";
	}

	protected function get_third_party_video_id_from_video_key($video_key)
	{
		return preg_replace('/[_\.].*$/', '', basename($video_key));
	}

	/*
	 * Downloads files from the "receiving_bucket/receiving_path" to "working_dir"
	 * @returns: String downloaded file path, or Boolean FALSE if download or file write failed
	 */
	protected function download_video($video_key)
	{
		$downloaded_path = '';

		$video_basename = basename($video_key);
		$local_path = "{$this->working_dir}/$video_basename";
		$this->register_working_file($local_path);

		// don't download again if the file already exists
		if(!file_exists($local_path))
		{
			$download_response = $this->vl_aws_services->get_file(
				$this->options['receiving_bucket'],
				$video_key
			);

			if(empty($download_response['ContentLength'])) return FALSE;
			if(file_put_contents($local_path, $download_response['Body']) === FALSE) return FALSE;
		}

		return $local_path;
	}

	/*
	 * @param String $local_path
	 * @param String $remote_dir
	 * @param String $mime_type
	 * @returns String file key, or Boolean FALSE if unsuccessful
	 */
	protected function upload_local_file($local_path, $remote_dir, $mime_type)
	{
		$file_basename = basename($local_path);

		$dummy_uploaded_file_info = [
			'name' => "{$remote_dir}/{$file_basename}",
			'tmp' => $local_path,
			'size' => filesize($local_path),
			'type' => $mime_type,
		];

		try
		{
			$result = $this->vl_aws_services->upload_file(
				$dummy_uploaded_file_info,
				$this->options['output_bucket'],
				true // should_overwrite
			);
		}
		catch(Exception $e)
		{
			if($e->getMessage() !== 'File already exists')
			{
				return FALSE;
			}
		}

		return $dummy_uploaded_file_info['name'];
	}

	/*
	 * @param optional Array $spot_ids
	 * @returns Array of file keys from S3
	 */
	protected function get_video_key_from_s3($spot_id)
	{
		$video_keys = $this->vl_aws_services->get_file_keys(
			$this->options['receiving_bucket'],
			$this->join_path_parts(array($this->options['receiving_path'], $spot_id))
		);

		// TODO: if more than one file is matched, pick one by a better criterion than "gimme"
		foreach($video_keys as $received_key)
		{
			return $received_key;
		}
		return FALSE;
	}

	/*
	 * @param String $src_video_path
	 * @param String $format 'mp4' or 'webm'
	 * @param optional String $size as WxH, defaults to default_video_size
	 * @returns String path to output video, or Boolean FALSE if unsuccessful
	 */
	protected function get_scaled_video($src_video_path, $format, $size = NULL)
	{
		if($size === NULL)
		{
			$size = $this->options['default_video_size'];
		}
		$video_basename = basename($src_video_path);
		$output_basename = preg_replace('/\..*$/', ".{$size}.{$format}", $video_basename);
		$output_path = "{$this->working_dir}/{$output_basename}";
		$this->register_working_file($output_path);

		$conversion_options = [
			's' => $size,
			'c:v' => $this->options['video_codecs'][$format],
			'c:a' => $this->options['audio_codecs'][$format],
			'pix_fmt' => 'yuv420p', // force chroma subsampling 4:2:0
			'b:v' => $this->options['video_bitrate'],
			'b:a' => $this->options['audio_bitrate'],
			'f' => $format,
		];

		// VP9 is giving bad results currently. Using default VP8.
		// if($format === 'webm')
		// {
		// 	$conversion_options['c:v'] = 'libvpx-vp9';
		// }

		return $this->convert_video($src_video_path, $output_path, $conversion_options);
	}

	/*
	 * @param String $src_video_path
	 * @returns String path to thumbnail, or Boolean FALSE if unsuccessful
	 */
	private function get_video_thumbnail($src_video_path, $size = NULL)
	{
		if($size === NULL)
		{
			$size = $this->options['default_video_size'];
		}
		$video_basename = basename($src_video_path);
		$thumbnail_basename = preg_replace('/\..*$/', ".{$size}.jpg", $video_basename);
		$thumbnail_path = "{$this->working_dir}/{$thumbnail_basename}";
		$this->register_working_file($thumbnail_path);

		$conversion_options = [
			'ss' => $this->options['thumbnail_time_offset'],
			's' => $size,
			't' => '1',
			'f' => 'image2',
			// TODO: add flag to control JPEG quality
		];

		return $this->convert_video($src_video_path, $thumbnail_path, $conversion_options);
	}

	/*
	 * @param String $input_path
	 * @param String $output_path
	 * @param optional Array $options with keys matching flags compatible with the conversion_tool (ffmpeg/avconv)
	 *   ss => String timecode to seek to
	 *   s => String dimensions like WxH
	 * @returns String $output_path if successful, Boolean FALSE if unsuccessful
	 */
	protected function convert_video($input_path, $output_path, $options = [])
	{
		if(file_exists($output_path)) return $output_path;

		// fit to rectangle defined by "s", instead of stretching
		if(isset($options['s']))
		{
			$dimensions = explode('x', $options['s']);
			if(count($dimensions) !== 2)
			{
				return FALSE;
			}

			$w = $dimensions[0];
			$h = $dimensions[1];

			// avconv complains of a missing ")", but ffmpeg runs it fine
			// $options['vf'] = " scale=\"'if(gt(a,{$w}/{$h}),{$w},-1)':'if(gt(a,{$w}/{$h}),-1,{$h})'\"";

			// fixed height, setting width automatically
			$options['vf'] = " scale=\"-1:{$h}\"";

			unset($options['s']);
		}

		// build command
		$convert_command = "{$this->options['conversion_tool']}";
		$convert_command .= " -y"; // confirm every step, without interaction
		$convert_command .= " -i \"{$input_path}\"";
		$convert_command .= " -strict experimental"; // enables use of AAC audio encoder

		foreach($options as $flag => $value)
		{
			$convert_command .= ' -' . $flag . ' ' . $value;
		}
		$convert_command .= " \"{$output_path}\"";

		// execute command
		$convert_output = [];
		$convert_status = null;
		exec($convert_command, $convert_output, $convert_status);
		if(!file_exists($output_path))
		{
			return FALSE;
		}

		return $output_path;
	}

	/*
	 * Checks that the working directory exists, is writable, and creates it if not.
	 *
	 * @returns Boolean success
	 */
	protected function prep_working_dir()
	{
		if($this->working_dir) return TRUE;

		$working_dir = "{$this->options['tmp_dir']}/{$this->options['working_dirname']}";

		if(is_writable($working_dir) || mkdir($working_dir))
		{
			$this->working_dir = $working_dir;
			return TRUE;
		}

		return FALSE;
	}

	/*
	 * @param String $path - path of the temporary file to register to this session
	 */
	protected function register_working_file($path)
	{
		$this->working_files[] = $path;
	}

	/*
	 * Deletes all files for which :register_working_file() was called
	 *
	 * @returns Boolean success
	 */
	protected function clear_all_working_files()
	{
		$success = true;

		foreach($this->working_files as $index => $path)
		{
			if(file_exists($path))
			{
				if(unlink($path))
				{
					unset($this->working_files[$index]);
				}
				else
				{
					$success = false;
				}
			}
		}

		return $success;
	}

	/*
	 * @param associative array $spot_info indexed by $spot_id:
	 *   'files' => [] (see @return value for process_videos)
	 *   'status' => String or NULL (see static variables on Spectrum_tv_model)
	 * @returns associative array success - $spot_id => Boolean success
	 */
	protected function save_video_creative_info($spot_info)
	{
		$results = [];
		$rows = [];

		foreach($spot_info as $third_party_video_id => $data)
		{
			if(!empty($data['files']))
			{
				$data['link_mp4'] = empty($data['files']['mp4']) ? NULL : $data['files']['mp4']['signedUrl'];
				$data['link_webm'] = empty($data['files']['webm']) ? NULL : $data['files']['webm']['signedUrl'];
				$data['link_thumb'] = empty($data['files']['thumbnail']) ? NULL : $data['files']['thumbnail']['signedUrl'];
			}
			else
			{
				$data['link_mp4'] = NULL;
				$data['link_webm'] = NULL;
				$data['link_thumb'] = NULL;
			}

			if(empty($data['status']))
			{
				$data['status'] = NULL;
			}

			$rows[] = [
				'video_creative_id'    => !empty($data['video_creative_id']) ? $data['video_creative_id'] : NULL,
				'third_party_video_id' => $third_party_video_id,
				'link_mp4'             => $data['link_mp4'],
				'link_webm'            => $data['link_webm'],
				'link_thumb'           => $data['link_thumb'],
				'status'               => $data['status'],
			];
		}

		return $this->spectrum_tv_model->update_video_creatives($rows);
	}

	protected function download_network_data()
	{
		$result = [];
		$demo_file_keys = $this->vl_aws_services->get_file_keys(
			$this->options['receiving_bucket'],
			$this->options['network_demographics_path'] . '/'
		);

		$result = array_flip($demo_file_keys);
		foreach($result as $demo_file_key => &$downloaded_path)
		{
			$downloaded_path = '';

			$demo_file_basename = basename($demo_file_key);
			$tmp_path = getcwd().$this->options['network_demographics_dir']."/".$demo_file_basename;

			// don't download again if the file already exists
			$download_response = $this->vl_aws_services->get_file(
				$this->options['receiving_bucket'],
				$demo_file_key
			);

			if(empty($download_response['ContentLength'])) continue;
			if(file_put_contents($tmp_path, $download_response['Body']) === FALSE) continue;

			$downloaded_path = $tmp_path;
		}
		return $result;
	}

	/*
	 * @returns Boolean if script has run longer than the configured timeout
	 */
	protected function check_timeout()
	{
		$this->run_time = microtime(true) - $this->start_time;
		return ($this->run_time >= $this->options['timeout']);
	}

	private function flatten_message_array_for_email($arr)
	{
		return implode("\n", $arr);
	}

	private function send_mailgun_email($from, $to, $subject, $message, $type, $attachment_path = false)
	{
		if($attachment_path)
		{
			$result = mailgun(
				$from,
				$to,
				$subject,
				$message,
				$type,
				process_attachments_for_email(array($attachment_path))
			);
		}
		else
		{
			$result = mailgun(
				$from,
				$to,
				$subject,
				$message,
				$type
			);
		}
		echo "\n";
		echo ($result) ? 'Message sent successfully' : 'Message failed to send';
		echo "\n\n";
	}

	/*
	 * @param array $path_parts strings to be joined into one path
	 * @returns string
	 */
	private function join_path_parts($path_parts)
	{
		return implode('/', array_filter(array_map(function($path_part) {
			return trim($path_part, '/');
		}, $path_parts)));
	}

}

?>
