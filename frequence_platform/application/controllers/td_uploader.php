<?php

class Td_uploader extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('mailgun');
		$this->load->library('cli_data_processor_common');
		$this->load->model('td_uploader_model');
	}

	private $missing_imps;
	private $missing_clks;

	private $num_clicks = 0;
	private $num_impressions = 0;

	private $num_siterecords = 0;
	private $num_cityrecords = 0;
	private $num_adsizerecords = 0;
	private $num_creativerecords = 0;
	private $num_zctarecords = 0;

	private $num_errors = 0;
	private $num_warnings = 0;

	private $new_imp_table;
	private $new_clk_table;
	private $new_blacklist_imp_table;
	private $new_blacklist_clk_table;

	private $impression_bucket_path;
	private $click_bucket_path;

	private $process_date;

	public function td_upload($manual_date = null)
	{
		if($this->input->is_cli_request())
		{
			require_once('misc_external/dataload/td_daily/aws-sdk-1.5.6.2/sdk.class.php');
			require_once('misc_external/uploader_constants.php');

			$start_timestamp = new DateTime("now");

			$this->num_impressions = 0;
			$this->num_clicks = 0;
			$this->num_siterecords = 0;
			$this->num_cityrecords = 0;
			$this->num_adsizerecords = 0;
			$this->num_creativerecords = 0;

			$this->missing_imps = array();
			$this->missing_clks = array();

			$this->num_errors = 0;
			$this->num_warnings = 0;

			$this->new_imp_table = "td_raw_impressions_" . date("Y_m_d",strtotime('-1 day'));
			$this->new_clk_table = "td_raw_clicks_" . date("Y_m_d",strtotime('-1 day'));
			$this->new_blacklist_imp_table = "td_raw_impressions_" . date("Y_m_d",strtotime('-1 day'))."_BLACKLIST";
			$this->new_blacklist_clk_table = "td_raw_clicks_" . date("Y_m_d",strtotime('-1 day'))."_BLACKLIST";

			switch($this->config->item('ttd_feed_ver'))
			{
				case "2":
					$this->impression_bucket_path = "/impressions_{$this->config->item('ttd_partner_id')}_v2/";
					$this->click_bucket_path = "/clicks_{$this->config->item('ttd_partner_id')}_v2/";
					break;
				case "3":
					$this->impression_bucket_path = "/impressions_{$this->config->item('ttd_partner_id')}_v3/";
					$this->click_bucket_path = "/clicks_{$this->config->item('ttd_partner_id')}_v3/";
					echo "Invalid input in config for version: 3";
					return;
					break;
				case "4":
					$this->impression_bucket_path = "/impressions_{$this->config->item('ttd_partner_id')}_v4/";
					$this->click_bucket_path = "/clicks_{$this->config->item('ttd_partner_id')}_v4/";
					break;
				default:
					echo "Invalid input in config 'ttd_feed_ver'";
					return;
					break;
			}

			$is_successful = true;

			if(!empty($manual_date))
			{
				if(func_num_args() > 1)
				{
					echo "USAGE: php index.php td_uploader td_upload YYYY-MM-DD\n";
					return;
				}
				if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $manual_date))
				{
					echo "USAGE: php index.php td_uploader td_upload YYYY-MM-DD <-- Yo, format your date correctly, please.\n";
					return;
				}
				if(!strtotime($manual_date))
				{
					echo "USAGE: php index.php td_uploader td_upload YYYY-MM-DD <-- Yeah. Really funny. Use a valid date.\n";
					return;
				}
				$start_time = date("m/d/Y 00:00:00", strtotime($manual_date));
				$early_time = date('m/d/Y 23:00:00', strtotime('-1 day', strtotime($start_time)));
				$end_time = date('m/d/Y 00:00:00', strtotime('+1 day', strtotime($start_time)));
				$this->process_date = date("Y-m-d", strtotime($start_time));
				echo "Ah, so we're going to run {$start_time}\n";
			}
			else
			{
				echo "Deleting old tables...\n\n";
				$old_date_to_delete = date("Y_m_d", strtotime('-2 weeks -1 day'));
				$deleted_old_tables = $this->td_uploader_model->delete_tables_for_date($old_date_to_delete);
				if($deleted_old_tables === false)
				{
					echo "Failed to delete past run tables. ({$old_date_to_delete})";
				}
				$early_time = date("m/d/Y 23:00:00", strtotime('-2 day'));
				$start_time = date("m/d/Y 00:00:00", strtotime('-1 day'));
				$end_time   = date("m/d/Y 00:00:00", strtotime('-0 day'));
				$this->process_date = date("Y-m-d", strtotime($start_time));

				//Verify process date isn't already in there
				if($this->td_uploader_model->do_td_impressions_exist_for_date($this->process_date))
				{
					echo "TD Impressions already detected. Cancelling run.\n";
					return;
				}

				$full_day_data_exists = $this->verify_date($start_time, $end_time);
				if($this->verify_date($start_time, $end_time) !== true)
				{
					//Oldest non-loaded day is not in there yet
					echo "NOPE!\n";

					//Determine # of hours since end of that day GMT
					$start_datetime = new DateTime($start_time);
					$hours_difference = $start_datetime->diff($start_timestamp)->h;
					$hours_to_warn = $this->config->item('td_uploader_warning_threshold');
					echo "It's been $hours_difference hours since the end of the day ({$hours_to_warn} to notify ops, ".($hours_to_warn+5)." to be super-late)\n";
					$message['basic_info'] = array();
					$message['late_data'] = array();


					if($hours_difference == $hours_to_warn + 5)
					{
						//Email tech, saying to contact TTD
						$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message();
						$message['basic_info'][] = 'Tradedesk Raw Event Data Feed Version: '.$this->config->item("ttd_feed_ver");
						$message['late_data'][] = "<strong>TTD Data for {$this->process_date} is {$hours_difference} hours late. Someone might want to get a hold of TTD</strong>";
						$message['late_data'][] = "Earliest missing bucket: {$full_day_data_exists}";
						$subject = '[ATTN] - Nightly TD Upload (' . date('Y-m-d',  strtotime($start_time)) . ') - Super Late';
						$from = "TTD Daily <noreply@frequence.com>";
						$to = "Tech Logs <tech.logs@frequence.com>";

						$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
						$message = nl2br(implode("\n\n", $message));
						$this->send_mailgun_email($from, $to, $subject, $message, 'html');
					}
					else if($hours_difference == $hours_to_warn)
					{
						//Email Ops/Tech, saying stuff is late
						$message['late_data'][] = "TTD Data for {$this->process_date} is currently {$hours_difference} hours late. Will update you when the data becomes available.";
						$subject = 'TTD Impr/Click Data for ' . date('Y-m-d',  strtotime($start_time)) . ' is late';
						$from = "Charles Semple <charles.semple@frequence.com>";
						$to = "Tech Logs <tech.logs@frequence.com>, Ops Team <ops@frequence.com>";
						//$to = "Tech Logs <tech.logs@frequence.com>";
						$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
						$message = nl2br(implode("\n\n", $message));
						$this->send_mailgun_email($from, $to, $subject, $message, 'html');
					}
					return;
				}
				echo "Cool. Finished checking for buckets.\n";

				//Clear out old table"
			}

			$deleted_run_day_tables = $this->td_uploader_model->delete_tables_for_date(date("Y_m_d", strtotime($this->process_date)));
			if($deleted_run_day_tables === false)
			{
				echo "Failed to delete tables for process date.";
				return;
			}
			$this->new_imp_table = "td_raw_impressions_" . date("Y_m_d", strtotime($start_time));
			$this->new_clk_table = "td_raw_clicks_" . date("Y_m_d", strtotime($start_time));
			$this->new_blacklist_imp_table = "td_raw_impressions_" . date("Y_m_d", strtotime($start_time))."_BLACKLIST";
			$this->new_blacklist_clk_table = "td_raw_clicks_" . date("Y_m_d", strtotime($start_time))."_BLACKLIST";

			echo "Start Time: {$start_time}\nEnd Time: {$end_time}\nProcess Date: {$this->process_date}";

			$message = array();

			$message['basic_info'] = array();
			$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message();
			$message['tradedesk_data_upload'] = array();
			$message['tradedesk_data_upload'][] = '<u>TRADEDESK DATA UPLOAD</u>';
			$message['tradedesk_data_upload'][] = 'DATE PROCESSED: ' . date("Y-m-d", strtotime($start_time));
			$message['tradedesk_data_upload'][] = 'PROCESS STARTED: ' . date("Y-m-d H:i:s T");
			if (!$this->td_uploader_model->create_impressions_and_clicks_tables($this->new_imp_table, $this->new_clk_table, $this->new_blacklist_imp_table, $this->new_blacklist_clk_table))
			{
				die("Raw impressions/clicks tables already exist for this date\n");
			}

			$this->load_data($early_time, $start_time);
			$this->load_data($start_time, $end_time);
			echo "\n\nData Loaded. Trimming excess data.\n";
			$this->td_uploader_model->remove_records_with_incorrect_date($this->new_imp_table, raw_db_groupname, $this->process_date, 'LogEntryTime');
			$this->td_uploader_model->remove_records_with_incorrect_date($this->new_clk_table, raw_db_groupname, $this->process_date, 'LogEntryTime');

			// Need to run clean up script which handles geofencing
			echo "\nRunning geofencing query\n";
			$geofencing_script_start = microtime(true);
			$geofencing_output = $this->td_uploader_model->geofencing_assign_geos_to_points_if_they_exist($this->new_imp_table);
			$geofencing_script_end = microtime(true);
			$geofencing_time = round($geofencing_script_end - $geofencing_script_start, 1);
			echo "Geofencing query finished ({$geofencing_time}s): {$geofencing_output['affected_rows']} rows geofenced\n";

			echo "Aggregating geofencing data into tables\n";
			$this->benchmark->mark('geofencing_aggregation_start');
			$geofencing_aggregation_output = $this->td_uploader_model->geofencing_aggregation_for_date($this->process_date, $this->new_imp_table, $this->new_clk_table, $geofencing_output['tdgf_adgroups']);
			$this->benchmark->mark('geofencing_aggregation_end');
			$geofencing_aggregation_time = round(floatval($this->benchmark->elapsed_time('geofencing_aggregation_start', 'geofencing_aggregation_end')), 1);
			echo "Geofencing aggregation finished ({$geofencing_aggregation_time}s): {$geofencing_aggregation_output}\n";

			$this->num_impressions = $this->td_uploader_model->get_num_records($this->new_imp_table, raw_db_groupname);
			$this->num_clicks = $this->td_uploader_model->get_num_records($this->new_clk_table, raw_db_groupname);

			echo "\nLoaded {$this->num_impressions} impressions\nLoaded {$this->num_clicks} clicks.\n\n";

			$message['s3_buckets'] = array();
			$message['s3_buckets'][] = '<u>S3 BUCKETS</u>';
			$message['s3_buckets'][] = 'Impressions: ' . (25 - count($this->missing_imps)) . '/25 found';

			$uh_oh = false;
			if(count($this->missing_imps) > 0)
			{
				$uh_oh = true;
				$message['s3_buckets'][] = '<strong>(MISSING: ' . implode(', ', $this->missing_imps) . ')</strong>';
				$this->num_errors++;
			}
			$message['s3_buckets'][] = 'Clicks: ' . (25 - count($this->missing_clks)) . '/25 found';
			if(count($this->missing_clks) > 0)
			{
				$uh_oh = true;
				$message['s3_buckets'][] = '<strong>(MISSING: ' . implode(', ', $this->missing_clks) . ')</strong>';
				$this->num_warnings++;
			}

			$raw_timestamp = new DateTime("now");
			$message['raw_data_loaded'] = array();
			$message['raw_data_loaded'][] = '<u>RAW DATA LOADED</u> (' . date_diff($start_timestamp, $raw_timestamp)->format("%imin, %ss") . ')';
			$message['raw_data_loaded'][] = 'Raw Impressions: ' . number_format($this->num_impressions);
			$message['raw_data_loaded'][] = 'Raw Clicks: ' . number_format($this->num_clicks);
			$did_filter_raw_sites = $this->td_uploader_model->post_process_raw_sites($this->new_imp_table);
			if(!$did_filter_raw_sites)
			{
				$this['raw_data_loaded'][] = '<strong>[ERROR] Raw Sites Filtering Failed</strong>';
				$this->num_errors++;
			}

			$did_blacklist_removal = $this->td_uploader_model->remove_raw_blacklist_sites($this->new_imp_table, $this->new_clk_table, $this->new_blacklist_imp_table, $this->new_blacklist_clk_table);
			if($did_blacklist_removal['is_success'] === false)
			{
				$message['raw_data_loaded'][] = "<strong>Blacklist Removal: FAILED ({$did_blacklist_removal['err_msg']})</strong>";
				$this->num_errors++;
			}
			else
			{
				$message['raw_data_loaded'][] = "Blacklist Removal:";
				$message['raw_data_loaded'][] = " - ".$did_blacklist_removal['removed_imps']." impressions removed";
				$message['raw_data_loaded'][] = " - ".$did_blacklist_removal['removed_clks']." clicks removed";
				$this->num_impressions -= $did_blacklist_removal['removed_imps'];
				$this->num_clicks -= $did_blacklist_removal['removed_clks'];
			}
			$message['raw_data_loaded'][] = "Geofencing Result({$geofencing_time}s): {$geofencing_output['affected_rows']}";
			$message['raw_data_loaded'][] = "Geofencing Aggregation({$geofencing_aggregation_time}s): {$geofencing_aggregation_output}";
			$this->process_and_transfer_data($this->process_date);

			if($geofencing_output['affected_rows'] > 0 && $geofencing_output['tdgf_adgroups'])
			{
				echo "\nRunning geofencing distribution\n";
				$this->benchmark->mark('geofencing_distribution_start');
				$fixed_geofences_status = $this->td_uploader_model->fix_geofenced_impressions_with_no_locations($this->process_date, $geofencing_output['tdgf_adgroups']);
				$this->benchmark->mark('geofencing_distribution_end');
				$geofencing_distribution_time = round(floatval($this->benchmark->elapsed_time('geofencing_distribution_start', 'geofencing_distribution_end')), 1);
				echo "Geofencing distribution finished ({$geofencing_distribution_time}s):\n";
				array_walk($fixed_geofences_status, function($value, $key){
					$key = ucfirst($key);
					echo "{$key} updated: {$value['rows_updated']}\n{$key} deleted: {$value['rows_deleted']}\n";
				});
				echo "\n";
			}

			$agg_timestamp = new DateTime("now");
			$message['raw_binned_data_loaded'] = array();
			$message['raw_binned_data_loaded'][] = '<u>RAW BINNED DATA LOADED</u> (' . date_diff($raw_timestamp, $agg_timestamp)->format("%imin, %ss") . ')';

			$date_data = $this->td_uploader_model->get_num_clicks_and_impressions_by_date($this->process_date);

			$message['raw_binned_data_loaded'][] = 'New Site Records: ' . number_format($this->num_siterecords);
			$message['raw_binned_data_loaded'][] = 'New City Records: ' . number_format($this->num_cityrecords);
			$message['raw_binned_data_loaded'][] = 'New Zcta Records: ' . number_format($this->num_zctarecords);

			$this->check_and_report_accuracy_of_data(
				$message['raw_binned_data_loaded'],
				'impressions',
				$date_data['sites']['num_imps'],
				$date_data['cities']['num_imps'],
				$date_data['sizes']['num_imps'],
				$date_data['creatives']['num_imps'],
				$date_data['zctas']['num_imps'],
				$this->num_impressions
			);
			$this->check_and_report_accuracy_of_data(
				$message['raw_binned_data_loaded'],
				'clicks',
				$date_data['sites']['num_clicks'],
				$date_data['cities']['num_clicks'],
				$date_data['sizes']['num_clicks'],
				$date_data['creatives']['num_clicks'],
				$date_data['zctas']['num_clicks'],
				$this->num_clicks
			);

			echo "Running SiteRecords collation\n";
			$this->benchmark->mark('siterecords_collation_start');
			$collation_result = $this->td_uploader_model->collate_loose_impressions($this->process_date, 'sites');
			$this->benchmark->mark('siterecords_collation_end');
			$siterecords_collation_time = round(floatval($this->benchmark->elapsed_time('siterecords_collation_start', 'siterecords_collation_end')), 1);
			$collation_result_string = ($collation_result) ? 'successfully' : 'unsuccessfully';
			echo "SiteRecords collation finished {$collation_result_string} ({$siterecords_collation_time}s)\n\n";
			//TODO: finish cityrecords aggregation

			if($collation_result === false)
			{
				$message['tail_aggregation'][] = "<strong>ERROR: Failed to successfully aggregate tail SiteRecords</strong>";
				$this->num_errors++;
			}

			$tail_timestamp = new DateTime("now");
			$message['tail_aggregation'] = array();
			$message['tail_aggregation'][] = '<u>TAIL SITE AGGREGATION</u> (' . date_diff($agg_timestamp, $tail_timestamp)->format("%imin, %ss") . ')';
			$message['tail_aggregation'][] =
				'Aggregate Tail Site Records: ' .
				number_format($date_data['loose_sites']['num_rows']) . ' (' .
				number_format(intval(100 * ($date_data['loose_sites']['num_rows'] / $this->num_siterecords))) . '%)';
			$message['tail_aggregation'][] =
				'Aggregate Tail Site Impressions: ' .
				number_format($date_data['loose_sites']['num_imps']) . ' (' .
				number_format(intval(100 * ($date_data['loose_sites']['num_imps'] / $date_data['sites']['num_imps']))) . '%)';

			$post_aggregate_row_check = $this->td_uploader_model->get_site_rows_post_aggregation_for_date($this->process_date);
			if($post_aggregate_row_check === false)
			{
				$message['tail_aggregation'][] = "<strong>ERROR: Failed to get post-aggregate site row count</strong>";
				$this->num_errors++;
			}
			else
			{
				$date_data['sites']['num_rows'] = $post_aggregate_row_check;
			}
			//TODO: finish city aggregation

			$small_impressions_match_aggregate_rows_result = $this->td_uploader_model->check_that_impressions_match_between_sites_tables($this->process_date);
			$small_site_check = 'Small Impressions Table Check: ';
			if(!$small_impressions_match_aggregate_rows_result['is_success'])
			{
				$small_site_check .= "<strong>[ERROR] Impression/AdGroup mismatch following aggregation\n{$small_impressions_match_aggregate_rows_result['error']}</strong>";
				$this->num_errors++;
			}
			else
			{
				$small_site_check .= 'OK';
			}
			$message['tail_aggregation'][] = $small_site_check;

			$site_check = 'SiteRecords Check: ';
			if (!$this->num_impressions == $date_data['sites']['num_imps'])
			{
				$site_check .= '<strong>[ERROR] Site impression mismatch following aggregation</strong>';
				$this->num_errors++;
			}
			else
			{
				$site_check .= 'OK';
			}
			$message['tail_aggregation'][] = $site_check;

			$unknown_zips = [];
			$unknown_zips_elapsed_time = 0;
			if($this->config->item('ttd_feed_ver') == "4")
			{
				$this->benchmark->mark('unknown_zips_query_start');
				$unknown_zips = $this->td_uploader_model->get_unknown_zips_from_raw_table($this->new_imp_table);
				$this->benchmark->mark('unknown_zips_query_end');
				$unknown_zips_elapsed_time = round($this->benchmark->elapsed_time('unknown_zips_query_start', 'unknown_zips_query_end'), 1);
				echo "Got Unknown Zips from DB ({$unknown_zips_elapsed_time}s): " . number_format(count($unknown_zips)) . "\n";

				$unknown_zips_file = $this->create_file_from_array_of_zipcodes($unknown_zips);
				$percent_zipcode_conversion = $this->td_uploader_model->get_percentage_of_zipcode_conversions($this->new_imp_table);

				if($geofencing_output['affected_rows'] > 0 && $geofencing_output['tdgf_adgroups'])
				{
					$geofence_timestamp = new DateTime("now");
					$this->benchmark->mark('bad_geofencing_query_start');
					$adgroup_data = $this->td_uploader_model->get_percentage_of_incorrect_geofenced_regions($this->new_imp_table, $geofencing_output['tdgf_adgroups']);
					$this->benchmark->mark('bad_geofencing_query_end');
					$bad_geofenced_regions_elapsed_time = round($this->benchmark->elapsed_time('bad_geofencing_query_start', 'bad_geofencing_query_end'), 1);
					echo "Got geofencing stats ({$bad_geofenced_regions_elapsed_time}s): " . count($adgroup_data) . " affected\n";
					$geofence_tail_timestamp = new DateTime("now");

					$message['geofencing_accuracy'] = [];
					$message['geofencing_accuracy'][] = '<u>GEOFENCING ACCURACY RESULTS</u> (' . date_diff($geofence_timestamp, $geofence_tail_timestamp)->format("%imin, %ss") . ')';
					$warning_cutoff = 25;
					if(empty($adgroup_data))
					{
						$message['geofencing_accuracy'][] = 'No AdGroups were Geofenced';
					}
					else
					{
						$message['geofencing_accuracy'][] = 'The following adgroups were geofenced: ';
						$geofencing_message = '';
						foreach($adgroup_data as $adgroup_id => $percentages)
						{
							$total_points = $percentages['total_points'];
							unset($percentages['total_points']);

							$geofencing_adgroup_message = "{$adgroup_id}: {$total_points} -> ";
							foreach($percentages as $type => $percentage)
							{
								if($percentage >= $warning_cutoff)
								{
									$this->num_warnings++;
									$geofencing_adgroup_message .= "<strong>([Warning] {$percentage}% {$type})</strong> ";
								}
								else
								{
									$geofencing_adgroup_message .= "({$percentage}% {$type}) ";
								}
							}
							$message['geofencing_accuracy'][] = $geofencing_adgroup_message;
						}
						if($fixed_geofences_status)
						{
							$message['geofencing_accuracy'][] = "Impression/Click Distribution for unknown points ({$geofencing_distribution_time}s):";
							$message['geofencing_accuracy'][] = "Impressions/Clicks adjusted for CityRecords: {$fixed_geofences_status['cities']['rows_updated']}";
							$message['geofencing_accuracy'][] = "Geofence Rows deleted from CityRecords: {$fixed_geofences_status['cities']['rows_deleted']}";
							$message['geofencing_accuracy'][] = "Impressions/Clicks adjusted for zcta_records: {$fixed_geofences_status['zctas']['rows_updated']}";
							$message['geofencing_accuracy'][] = "Geofence Rows deleted from zcta_records: {$fixed_geofences_status['zctas']['rows_deleted']}";
						}
					}
				}
			}

			$this->benchmark->mark('delete_adverify_records_start');
			$this->td_uploader_model->delete_adverify_records($this->process_date);
			$this->benchmark->mark('delete_adverify_records_end');
			echo "\nAdverify records deleted (" . round(floatval($this->benchmark->elapsed_time('delete_adverify_records_start', 'delete_adverify_records_end')), 1) . "s)\n";

			$count_cache = $this->td_uploader_model->load_report_cached_campaign('DATE_MODE', $this->process_date, '');
			$final_timestamp = new DateTime("now");

			$subject = ($uh_oh) ? '[WARNING!] ' : '';
			$subject = 'Nightly TD Upload (' . date('Y-m-d', strtotime($start_time)) . ') - Completed ';

			if ($this->num_errors > 0)
			{
				$subject .= ($this->num_errors == 1) ? 'with 1 error' : "with {$this->num_errors} errors";
			}
			else
			{
				$subject .= 'successfully';
			}

			$from = "TTD Daily <noreply@frequence.com>";
			$to = "Tech Logs <tech.logs@frequence.com>";

			$message['summary'] = array();
			$message['summary'][] = '<u>SUMMARY</u>';
			$message['summary'][] = 'Tradedesk Raw Event Data Feed Version: '.$this->config->item("ttd_feed_ver");
			$message['summary'][] = 'Total Site Records: ' . number_format($date_data['sites']['num_rows']);
			$message['summary'][] = 'Total City Records: ' . number_format($date_data['cities']['num_rows']);
			$message['summary'][] = 'Total Ad Size Records: ' . number_format($date_data['sizes']['num_rows']);
			$message['summary'][] = 'Total Creative Records: ' . number_format($date_data['creatives']['num_rows'])." (".$date_data['creatives']['unknown_creatives']." TTD creatives not associated with Frequence adgroup/creative)";
			$message['summary'][] = 'Total Zcta Records: ' . number_format($date_data['zctas']['num_rows']);
			if($this->config->item('ttd_feed_ver') == "4")
			{
				$message['summary'][] = 'Percent of Zip Codes Converted to Zctas: ' . floatval($percent_zipcode_conversion) . '%';
				$message['summary'][] = 'Number of Zip Codes not in DB (' . round(floatval($unknown_zips_elapsed_time), 1) . 's): ' . number_format(count($unknown_zips));
			}
			$message['summary'][] = 'Total New Impressions: ' . number_format($date_data['sites']['num_imps']);
			$message['summary'][] = 'Total New Clicks: ' . number_format($date_data['sites']['num_clicks']);
			$message['summary'][] = 'Total Records loaded in report_cached_adgroup_date: ' . $count_cache;
			$message['summary'][] = 'Total Process Time: ' . date_diff($start_timestamp, $final_timestamp)->format("%Hh %imin, %ss");

			$num_errors_string = $this->num_errors == 1 ? 'Error' : 'Errors';
			$num_warnings_string = $this->num_warnings == 1 ? 'Warning': 'Warnings';

			$message['basic_info'][] = $this->num_errors ? "<strong>{$this->num_errors} {$num_errors_string}</strong>" : "{$this->num_errors} {$num_errors_string}";
			$message['basic_info'][] = $this->num_warnings ? "<strong>{$this->num_warnings} {$num_warnings_string}</strong>" : "{$this->num_warnings} {$num_warnings_string}";

			$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
			$message = nl2br(implode("\n\n", $message));
			if($this->config->item('ttd_feed_ver') == "4")
			{
				$this->send_mailgun_email($from, $to, $subject, $message, 'html', $unknown_zips_file);
				$this->remove_unknown_zip_file($unknown_zips_file);
			}
			else
			{
				$this->send_mailgun_email($from, $to, $subject, $message, 'html');

			}

			$deleted_run_day_tables = $this->td_uploader_model->delete_tables_for_date(date("Y_m_d", strtotime($this->process_date)));
			if($deleted_run_day_tables === false)
			{
				echo "Failed to delete tables for process date.";
				return;
			}

			$this->execute_all_impression_caching();
		}
		else
		{
			show_404();
		}
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

	private function create_file_from_array_of_zipcodes($zip_array)
	{
		if(count($zip_array) > 0)
		{
			$file_path = "/tmp/list_of_unknown_zip_codes_{$this->process_date}.txt";
			$success = file_put_contents($file_path, implode("\n", $zip_array));
			if($success === false)
			{
				throw(new Exception("Failed to create unknown zip codes file\n"));
			}
			return $file_path;
		}
		return false;
	}

	private function remove_unknown_zip_file($unknown_zips_file)
	{
		if($unknown_zips_file)
		{
			if(!unlink($unknown_zips_file))
			{
				throw(new Exception("Failed to delete unknown zip codes file\n"));
			}
		}
	}

	//Function takes in an s3 object full of trade desk data
	//And which table we're going into and dumps the raw data into that table
	private function load_raw_data_feed($s3, $destination_table_type, $source_files_list, $destination_table)
	{
		foreach($source_files_list as $hour_bucket)
		{
			$temp_file_compressed = bucket_file_path;
			$temp_file_decompressed = "/tmp/decompressed_{$this->process_date}.log";

			if($this->config->item('ttd_feed_ver') == "2")
			{
				$object_response = $s3->get_object(
					'thetradedesk-uswest-partners-vantagelocal',
					$hour_bucket,
					array('fileDownload' => $temp_file_compressed)
				);
				shell_exec("gunzip -c {$temp_file_compressed} > {$temp_file_decompressed}");
			}
			else
			{
				$object_response = $s3->get_object(
					'thetradedesk-uswest-partners-vantagelocal',
					$hour_bucket,
					array('fileDownload' => $temp_file_decompressed)
				);
			}
			if($object_response->isOK())
			{
				//shell_exec("gunzip -c {$temp_file_compressed} > {$temp_file_decompressed}");

				if ($this->td_uploader_model->load_data_infile($temp_file_decompressed, $destination_table, $destination_table_type, $this->process_date))
				{
					echo "{$destination_table_type}: OKAY!\t";
					shell_exec("rm -f {$temp_file_decompressed}");
					return true;
				}
				else
				{
					die("Failed to open or gunzip for bucket: {$hour_bucket}\n");
				}
			}
			else
			{
				die("Failed to get response from Amazon s3 object.\n");
			}
		}
		return false;
	}

	private function load_data($start_time, $end_time)
	{
		echo "\n\nLoading data: {$start_time} - {$end_time}";

		if($start_time && $end_time)
		{
			$start_datetime = new DateTime($start_time);
			$start_date_hour = new DateTime($start_datetime->format("Y-m-d H:00:00"));
			$current_date_hour = clone $start_date_hour;
			$time_interval = new DateInterval('PT1H0M0S');
			$end_datetime = new DateTime($end_time);

			$s3 = new AmazonS3();

			while($current_date_hour->getTimestamp() < $end_datetime->getTimestamp())
			{
				$bucket_by_date_and_hour = $current_date_hour->format('Y/m/d/H/');
				echo "\n\tLoop: {$current_date_hour->format('Y-m-d H:i:s')} {$bucket_by_date_and_hour}\t";

				$impression_buckets_by_hour = $s3->get_object_list(
					"thetradedesk-uswest-partners-vantagelocal",
					array(
						"prefix" => $bucket_by_date_and_hour,
						"pcre" => $this->impression_bucket_path
					)
				);

				$click_buckets_by_hour = $s3->get_object_list(
					"thetradedesk-uswest-partners-vantagelocal",
					array(
						"prefix" => $bucket_by_date_and_hour,
						"pcre" => $this->click_bucket_path
					)
				);

				if(!($this->load_raw_data_feed($s3, "impressions", $impression_buckets_by_hour, $this->new_imp_table)))
				{
					array_push($this->missing_imps, $current_date_hour->format("d/m-H"));
				}

				if(!($this->load_raw_data_feed($s3, "clicks", $click_buckets_by_hour, $this->new_clk_table)))
				{
					array_push($this->missing_clks, $current_date_hour->format("d/m-H"));
				}
				$current_date_hour->add($time_interval);
			}
		}
	}

	//Grabs and aggregates all clicks and impressions
	//for a given date, and aggregates and passes rows
	//generated to be put into CityRecords/SiteRecords/zcta_records
	private function process_and_transfer_data($date_to_process)
	{
		if($date_to_process)
		{
			$this->td_uploader_model->same_day_clean($date_to_process, $this->new_imp_table);

			$this->benchmark->mark('site_aggregate_query_start');
			$sites_aggregate_response = $this->td_uploader_model->aggregate_impression_and_click_data($this->new_imp_table, $this->new_clk_table, 'sites');
			$this->benchmark->mark('site_aggregate_query_end');
			echo 'Sites to be loaded (' . round(floatval($this->benchmark->elapsed_time('site_aggregate_query_start', 'site_aggregate_query_end')), 1) . 's): ' . count($sites_aggregate_response) . "\n";
			$this->num_siterecords += $this->td_uploader_model->upload_impression_and_click_data('sites', $sites_aggregate_response, $date_to_process);

			$this->benchmark->mark('city_aggregate_query_start');
			$cities_aggregate_response = $this->td_uploader_model->aggregate_impression_and_click_data($this->new_imp_table, $this->new_clk_table, 'cities');
			$this->benchmark->mark('city_aggregate_query_end');
			echo 'Cities to be loaded (' . round(floatval($this->benchmark->elapsed_time('city_aggregate_query_start', 'city_aggregate_query_end')), 1) . 's): ' . count($cities_aggregate_response) . "\n";
			$this->num_cityrecords += $this->td_uploader_model->upload_impression_and_click_data('cities', $cities_aggregate_response, $date_to_process);

			$this->benchmark->mark('size_aggregate_query_start');
			$ad_sizes_aggregate_response = $this->td_uploader_model->aggregate_impression_and_click_data($this->new_imp_table, $this->new_clk_table, 'sizes');
			$this->benchmark->mark('size_aggregate_query_end');
			echo 'Sizes to be loaded (' . round(floatval($this->benchmark->elapsed_time('size_aggregate_query_start', 'size_aggregate_query_end')), 1) . 's): ' . count($ad_sizes_aggregate_response) . "\n";
			$this->num_adsizerecords += $this->td_uploader_model->upload_impression_and_click_data('sizes', $ad_sizes_aggregate_response, $date_to_process);

			$this->benchmark->mark('creative_aggregate_query_start');
			$creative_aggregate_response = $this->td_uploader_model->aggregate_impression_and_click_data($this->new_imp_table, $this->new_clk_table, 'creatives');
			$this->benchmark->mark('creative_aggregate_query_end');
			echo 'Creatives to be loaded (' . round(floatval($this->benchmark->elapsed_time('creative_aggregate_query_start', 'creative_aggregate_query_end')), 1) . 's): ' . count($creative_aggregate_response) . "\n";
			$this->num_creativerecords += $this->td_uploader_model->upload_impression_and_click_data('creatives', $creative_aggregate_response, $date_to_process);

			if($this->config->item('ttd_feed_ver') == "4")
			{
				$this->benchmark->mark('zcta_aggregate_query_start');
				$zctas_aggregate_response = $this->td_uploader_model->aggregate_impression_and_click_data($this->new_imp_table, $this->new_clk_table, 'zctas');
				$this->benchmark->mark('zcta_aggregate_query_end');
				echo 'Zctas to be loaded (' . round(floatval($this->benchmark->elapsed_time('zcta_aggregate_query_start', 'zcta_aggregate_query_end')), 1) . 's): ' . count($zctas_aggregate_response) . "\n";
				$this->num_zctarecords += $this->td_uploader_model->upload_impression_and_click_data('zctas', $zctas_aggregate_response, $date_to_process);
			}
		}
	}

	private function verify_raw_data($destination_table_type, $source_files_list)
	{
		if(count($source_files_list) > 0)
		{
			echo "{$destination_table_type}: OKAY!\t";
			return true;
		}
		else
		{
			echo "{$destination_table_type}: FAIL!\t";
			return false;
		}
	}

	private function verify_date($start_date, $end_date)
	{
		$start_datetime = new DateTime($start_date);
		$start_date_hour = new DateTime($start_datetime->format("Y-m-d H:00:00"));
		$current_date_hour = clone $start_date_hour;
		$time_interval = new DateInterval('PT1H0M0S');
		$end_datetime = new DateTime($end_date);
		$s3 = new AmazonS3();

		while($current_date_hour->getTimestamp() < $end_datetime->getTimestamp())
		{
			$bucket_by_date_and_hour = $current_date_hour->format('Y/m/d/H/');
			echo "\tCheck-Loop: {$current_date_hour->format('Y-m-d H:i:s')} {$bucket_by_date_and_hour}\t";

			$impression_buckets_by_hour = $s3->get_object_list(
				"thetradedesk-uswest-partners-vantagelocal",
				array(
					"prefix" => $bucket_by_date_and_hour,
					"pcre" => $this->impression_bucket_path
				)
			);

			$need_to_save_line = false;
			if(!$this->verify_raw_data("impressions", $impression_buckets_by_hour))
			{
				return $current_date_hour->format('Y-m-d H:i:s');
			}

			$click_buckets_by_hour = $s3->get_object_list(
				"thetradedesk-uswest-partners-vantagelocal",
				array(
					"prefix" => $bucket_by_date_and_hour,
					"pcre" => $this->click_bucket_path
				)
			);

			if(!$this->verify_raw_data("clicks", $click_buckets_by_hour))
			{
				// Missing clicks in the rare case that there are no clicks for a given hour
				// Add to warnings list and iterate warnings
				$this->num_warnings++;
				$need_to_save_line = true;
			}
			$current_date_hour->add($time_interval);

			echo (!$need_to_save_line) ? "\r\033[K" : "\n";
		}
		return true;
	}

	private function execute_all_impression_caching()
	{
		$caching_response = shell_exec("php index.php campaigns_main cache_all_impression_amounts");

		$cache_from = "Frequence Daily Caching <noreply@frequence.com>";
		$cache_to = "Tech Logs <tech.logs@frequence.com>";
		$cache_subject = "Nightly Impression Caching (" . date("m/d", strtotime('-1 day')) . ")";
		$cache_message = $caching_response;

		$this->send_email($cache_from, $cache_to, $cache_subject, $cache_message, 'text');
	}

	private function flatten_message_array_for_email($arr)
	{
		return implode("\n", $arr);
	}

	private function send_email($from, $to, $subject, $message, $body_type = 'html')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/mg.brandcdn.com/messages');
		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			array('from' => $from,
				'to' => $to,
				'subject' => $subject,
				$body_type => $message
			)
		);
		$result = curl_exec($ch);
		curl_close($ch);
		echo $result . "\n\n";
	}

	private function check_and_report_accuracy_of_data(&$message_sub_array, $data_type, $site_value, $city_value, $size_value, $creative_value, $zcta_value, $total_value)
	{
		$error = abs($total_value - (($site_value + $city_value) / 2)) / $total_value;
		$site_value_string = number_format($site_value);
		$city_value_string = number_format($city_value);
		$size_value_string = number_format($size_value);
		$creative_value_string = number_format($creative_value);
		$zcta_value_string = number_format($zcta_value);
		$total_value_string = number_format($total_value);
		$imps_string = 'City/Site/Sizes/Creatives/Zctas ' . ucfirst($data_type) . ': ';
		$mismatch_exists = !(($city_value == $site_value) && ($site_value == $size_value) && ($size_value == $creative_value) && ($creative_value == $zcta_value));

		if($mismatch_exists)
		{
			$imps_string .= "<strong>NOT OK</strong> ({$city_value_string} / {$site_value_string} / {$size_value_string} / {$creative_value_string} / {$zcta_value_string})";
			$this->num_errors++;
		}
		else
		{
			$imps_string .= "OK ({$city_value_string} / {$site_value_string} / {$size_value_string} / {$creative_value_string} / {$zcta_value_string})";
		}

		$message_sub_array[] = $imps_string;
		if ($error > 0 && $error <= 0.01)
		{
			$message_sub_array[] = "<strong>[WARNING] Small Raw-to-Aggregate Sites/Cities {$data_type} mismatch (".round($error*100, 2)."%)</strong>";
			$this->num_warnings++;
		}
		else if ($error > 0.01)
		{
			$message_sub_array[] = "<strong>[ERROR] Raw-to-Aggregate Sites/Cities {$data_type} mismatch (".round($error*100, 2)."%)</strong>";
			$this->num_errors++;
		}
	}

	// this method will be invoked weekly during off-peak hours. it will first delete all rows in the report header caching table and insert new rows
	// from the cityrecords table. After deleting and inserts, it will send out an email to tech group with count of rows inserted
	// Please take care to not run this method frequently as it processes large volume of data.
	public function load_complete_report_cached_data()
	{
		if($this->input->is_cli_request())
		{
			require_once('misc_external/dataload/td_daily/aws-sdk-1.5.6.2/sdk.class.php');
			require_once('misc_external/uploader_constants.php');

			$start_timestamp = new DateTime("now");
			$count_cache = $this->td_uploader_model->load_report_cached_campaign('REFRESH_ALL', $this->process_date, '');
			$subject = "Report Cache: Successfully completed weekly refresh";
			$from = "TTD Daily <noreply@frequence.com>";
			$to = "Tech Logs <tech.logs@frequence.com>";
			$message = array();

			$message['basic_info'] = array();
			$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message();

			$message['summary'] = array();
			$message['summary'][] = '<u>SUMMARY</u>';
			$message['summary'][] = 'Total Records loaded in report_cached_adgroup_date: ' . $count_cache;
			$final_timestamp = new DateTime("now");
			$message['summary'][] = 'Total Process Time: ' . date_diff($start_timestamp, $final_timestamp)->format("%imin, %ss");
			$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
			$message = nl2br(implode("\n\n", $message));
			$this->send_email($from, $to, $subject, $message, 'html');
		}
	}

}

?>
