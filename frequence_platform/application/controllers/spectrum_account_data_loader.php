<?php

class Spectrum_account_data_loader extends CI_Controller
{
	const third_party_target_spectrum = 'spectrum';
	const third_party_target_bright_house = 'bright_house';
	private $third_party_target = self::third_party_target_spectrum;

	private $third_party_s3_bucket = 'spectrumreach-data';
	private $third_party_s3_path = 'schedule/';

	private $third_party_core_file_name = 'ClientData';
	private $third_party_file_name_version_suffix = 'V2';
	private $third_party_file_name_extension = 'CSV';

	private $third_party_friendly_name = 'Spectrum';

	private $third_party_import_database_table_name = 'tp_spectrum_accounts_import';

	private $additional_external_emails = '';

	public function __construct()
	{
		parent::__construct();

		$this->load->library(array(
			'cli_data_processor_common'
		));
		$this->load->model('spectrum_account_model');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->library('vl_aws_services');
                $this->load->library('csv');
		$this->lang->load('tank_auth');
		$this->load->model('vl_auth_model');
		$this->load->model('advertisers_model');		
		$this->load->helper('vl_ajax_helper');

		$this->load->helper(array(
			'url',
			'mailgun'
		));

		/*
		set_error_handler(
			function ($errno, $errstr, $errfile, $errline ) {
				throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
			}
		);
		*/
	}

	private function resolve_third_party_constants($third_party_target_name)
	{
		switch($third_party_target_name)
		{
			case self::third_party_target_bright_house:
				$this->third_party_target = self::third_party_target_bright_house;

				$this->third_party_s3_bucket = 'spectrumreach-data';
				$this->third_party_s3_path = 'schedule/';
				$this->third_party_core_file_name = 'BrightHouse_ClientData';
				$this->third_party_file_name_extension = 'csv';

				$this->third_party_import_database_table_name = 'tp_spectrum_accounts_import_bright_house';
				$this->third_party_file_name_version_suffix = '';
				$this->third_party_friendly_name = 'Bright House';

				$this->additional_external_emails = defined('BRIGHT_HOUSE_DATA_PROCESSOR_EMAILS') ? ', ' . BRIGHT_HOUSE_DATA_PROCESSOR_EMAILS : '';
				break;
			case self::third_party_target_spectrum:
			default:
				$this->third_party_target = self::third_party_target_spectrum;

				$this->third_party_s3_bucket = 'spectrumreach-data';
				$this->third_party_s3_path = 'schedule/';
				$this->third_party_core_file_name = 'ClientData';
				$this->third_party_file_name_extension = 'CSV';

				$this->third_party_import_database_table_name = 'tp_spectrum_accounts_import';
				$this->third_party_file_name_version_suffix = 'V2';
				$this->third_party_friendly_name = 'Spectrum';

				$this->additional_external_emails = defined('SPECTRUM_DATA_PROCESSOR_EMAILS') ? ', ' . SPECTRUM_DATA_PROCESSOR_EMAILS : '';
				break;
		}

		$this->spectrum_account_model->set_third_party_friendly_name($this->third_party_friendly_name);
	}

	public function load_bright_house_data()
	{
		if($this->input->is_cli_request())
		{
			$this->cli_data_processor_common->mark_script_execution_start_time();

			if($this->upload_new_accounts_data(self::third_party_target_bright_house))
			{
				echo "Successfully processed {$this->third_party_friendly_name} Accounts\n";
			}
			else
			{
				echo "Failed to process {$this->third_party_friendly_name} Accounts\n";
			}
		}
		else
		{
			show_404();
		}
	}

	public function load_spectrum_data()
	{
		if($this->input->is_cli_request())
		{
			$this->cli_data_processor_common->mark_script_execution_start_time();

			if($this->upload_new_accounts_data(self::third_party_target_spectrum))
			{
				echo "Successfully processed {$this->third_party_friendly_name} Accounts\n";
			}
			else
			{
				echo "Failed to process {$this->third_party_friendly_name} Accounts\n";
			}
		}
		else
		{
			show_404();
		}
	}

	public function load_data()
	{
		if($this->input->is_cli_request())
		{
			$this->cli_data_processor_common->mark_script_execution_start_time();

			/*
			Data Cleanup Pre-requisits:
				1) Get a mapping from Spectrum from their client advertiser ACCOUNTKEY and Traffic System to existing Advertiser Names in the Frequence Dashboard
					- We will use this to add the associations before the initial Accounts csv run and then the Accounts csv will rename them as appropriate
				2) Spectrum provides a mapping from partner_id to SALESOFFICEID + traffic_system
					- This is used before the initial Accounts csv run
				3) On initial Accounts csv import, don't send email to Client or Agency users
					- Spectrum will give us a list of emails and account associations later which we will use to update emails
					- At a later point we can send the avalanche of emails to users for those not yet "activated"
						- Use the MAX(O.ORDERENDDATE) to only send to somewhat recenlty active advertisers

			upload new data to new table

			record all actions for report of what transpired

			find deltas between old table and new table
				- new client rows
				- modified rows

				build up list of things to do
				-1 new client advertiser (UL_ID + traffic_system is new) interpreted as new row for client advertiser
					-1 Use the MAX(O.ORDERENDDATE) to only analyze rows that are somewhat recently active
					-2 if advertiser name matches existing advertiser
						-1 use this existing advertiser
							-1 update Advertiser Third Party id with UL_ID + traffic_system and Eclipse_ID + traffic_system
					-3 if advertiser name does NOT match existing advertiser
						-1 create new advertiser
							-1 populate Advertiser Third Party id
							- populate sales person
					-4 if client email
						-1 if client email isn't already associated, create new client user
							-1 and send welcome email for them to configure their user.
						-2 link user to associated advertiser
							-1 if user already existed, send email notifying them of new association
								-1 build up list of advertisers they are added to so we don't send a bunch of emails
				-2 modified advertiser Eclipse ID
					-1 update Advertiser Eclipse ID association so it is used by TMPi and Carmercial data processors.
				-3 modified advertiser name
					-1 update Advertiser name
				-4 removed agency
					-1 unlink all that agencies users from this advertiser (implmented by removing all agency users from that advertiser)
				-5 new or added agency
					-1 agency with empty user email - TODO: ?
						-1 add to "post import todo list"
					-2 if agency without an existing user and find un-associated email
						-1 create new agency user
							-1 send welcome / invite email
					-3 link up primary agency user to advertiser
						-1 if user already existed send email to primary user notifying of new link
							-1 accumulate all new links in one email so they don't get an avalanche
				-6 changed agency email when agency primary user already exists, ignore
				-7 changed client email when client primary user already exists, ignore
				-8 AE association
					-1 remove old AE sales_person if there was one before
					-2 make AE sales_person for the Advertiser
					-3 if AE doesn't have a user, need to create the user record (even though they can't login yet)
						-1 add AE account id using the AE_ID + traffic_system
						-2 mark the user so it shows up in DOT status page to associate an email (empty email and populated Third Party ID is sufficient)
						-3 hook up new AE user to partner based upon Sales Office
						-4 use SalesPersonNAME
				-9 Sales Office association
					-1 id matches partners table spectrum id for SalesOfficeID + traffic_system
					-2 if associated sales person isn't under this sales office, note it in "post import todo list"
					-3 if Sales Office is not associated with a partner- put SalesOfficeNAME & SALESOFFICEID as error in email and don't use the row yet
						-1 need to handle when the Sales Office is added somehow
				-10 Traffic System association
					-1 used for ID uniqueness
			*/

			if($this->upload_new_accounts_data(self::third_party_target_spectrum))
			{
				echo "Successfully processed {$this->third_party_friendly_name} Accounts\n";
			}
			else
			{
				echo "Failed to process {$this->third_party_friendly_name} Accounts\n";
			}
		}
		else
		{
			show_404();
		}
	}

	private function zip_files($zip_archive_name, $file_name_array)
	{
		$zip_exec_output = array();
		$zip_exec_return_var = 0;

		$file_list = implode(' ', $file_name_array);

		$zip_exec_command = "zip -9 $zip_archive_name $file_list";
		exec($zip_exec_command, $zip_exec_output, $zip_exec_return_var);
		if($zip_exec_return_var === 0)
		{
			return $zip_archive_name;
		}

		return false;
	}

	private function upload_new_accounts_data($third_party_to_process)
	{
		$this->resolve_third_party_constants($third_party_to_process);

		$internal_from = $this->third_party_friendly_name.' Accounts Import <tech.logs@frequence.com>';
		$internal_to = 'Tech Team <tech.logs@frequence.com>';

		echo "additional_external_emails: $this->additional_external_emails\n";
		$external_from = $this->third_party_friendly_name.' Accounts Import <tech.help@frequence.com>';
		$external_to = 'Tech Help <tech.help@frequence.com>' . $this->additional_external_emails;

		global $argv;
		global $argc;

		$stats = new StdClass;
		$notifications = new StdClass;
		$info = new StdClass;
		$is_custom_file = false;

		$date_to_process = date("Y-m-d", strtotime('-1 day'));
		if($argc === 4)
		{
			$date_or_file = $argv[3];
			if(false === strpos($date_or_file, "file:"))
			{
				$date_to_process = date("Y-m-d", strtotime($date_or_file));
				$file_time = strtotime($date_to_process);
				if($file_time !== false)
				{
					$info->date_or_file_name = $date_to_process;
					$file_date = date("Ymd", $file_time);
					$input_file = "{$this->third_party_core_file_name}.$file_date";
					echo "date_to_process: $date_to_process\n";
				}
				else
				{
					echo "Error parsing time: '$file_time'\nIf it's a file prepend 'file:' on the name\n";
					exit;
				}
			}
			else
			{
				$input_file = substr($date_or_file, 5);
				$info->date_or_file_name = $input_file;
				echo "input_file: $input_file\n";

				$is_custom_file = true;
			}
		}
		else
		{
			$gmt_time_zone = new DateTimeZone('GMT');
			$date_time = new DateTime("now -1 day", $gmt_time_zone);
			$date_to_process = $date_time->format("Y-m-d");
			$info->date_or_file_name = $date_to_process;
			$file_date = date("Ymd", strtotime($date_to_process));
			$input_file = "{$this->third_party_core_file_name}.$file_date";
		}

		if(!$is_custom_file)
		{
			$date_time = new DateTime($date_to_process);
			$v2_start_date = new DateTime('2016-03-23');
			$v2_delta = $v2_start_date->diff($date_time);
			$has_v2_data = !$v2_delta->invert;
			if($has_v2_data)
			{
				$input_file .= $this->third_party_file_name_version_suffix;
			}

			$input_file .= '.'.$this->third_party_file_name_extension;


			$file_save_path = "/tmp/".rand(1, 10000).'-'.$input_file;
			echo "Downloading file ".$input_file. " to ".$file_save_path."\n";
			$result = $this->vl_aws_services->save_file_to_path($this->third_party_s3_bucket, $this->third_party_s3_path.$input_file, $file_save_path);
		}
		else
		{
			$file_save_path = $info->date_or_file_name;
		}

		$failure_message = "";
		$current_spectrum_accounts_table_name = $this->third_party_import_database_table_name;

		$new_spectrum_accounts_table_name = $current_spectrum_accounts_table_name."_".rand(1000, 9999);
		$old_spectrum_accounts_table_name = $current_spectrum_accounts_table_name."_".date("Y_m_d__H_i_s");
		echo "New table: $new_spectrum_accounts_table_name\n";
		if($this->spectrum_account_model->create_new_spectrum_accounts_table($new_spectrum_accounts_table_name))
		{
			try {

				if($this->spectrum_account_model->insert_new_spectrum_accounts(
					$stats,
					$notifications,
					$new_spectrum_accounts_table_name,
					$file_save_path,
					$date_to_process
				))
				{
					if(!$is_custom_file)
					{
						echo "Deleting temporary {$this->third_party_core_file_name} file at {$file_save_path}...";
						$did_delete = unlink($file_save_path);
						if($did_delete)
						{
							echo "DELETED\n";
						}
						else
						{
							echo "FAILED\n";
						}
					}

					$result = $this->spectrum_account_model->process_table_deltas(
						$stats,
						$notifications,
						$current_spectrum_accounts_table_name,
						$new_spectrum_accounts_table_name
					);

					if($result === true)
					{
						echo "Success processing deltas.\n";
						$is_success = true;
						$is_success = $is_success && $this->spectrum_account_model->rename_table($current_spectrum_accounts_table_name, $old_spectrum_accounts_table_name);
						$is_success = $is_success && $this->spectrum_account_model->rename_table($new_spectrum_accounts_table_name, $current_spectrum_accounts_table_name);
						if($is_success)
						{
							echo "Renamed tables.\n";

							$this->spectrum_account_model->drop_table($old_spectrum_accounts_table_name);

							$subject = "";
							$internal_message = "";
							$external_message = "";
							$attachments = array();
							$info->is_success = $is_success;

							$this->compose_completion_email(
								$subject,
								$internal_message,
								$external_message,
								$attachments,
								$stats,
								$notifications,
								$info
							);

							$this->send_email($internal_from, $internal_to, $subject, $internal_message, $attachments);
							$this->send_email($external_from, $external_to, $subject, $external_message, $attachments);
							return true;
						}
						else
						{
							$failure_message = "Failed to rename {$this->third_party_friendly_name} Client Accounts for {$info->date_or_file_name}";
							echo "$failure_message\n";
						}

					}
					else
					{
						$failure_message = "Failed to Process Imported {$this->third_party_friendly_name} Client Accounts for {$info->date_or_file_name}";
						echo "$failure_message\n";

						$this->spectrum_account_model->drop_table($new_spectrum_accounts_table_name);
					}
				}
				else
				{
					$failure_message = "Failed to Import {$this->third_party_friendly_name} Client Accounts for {$info->date_or_file_name}, Bad File";
					echo "$failure_message\n";
					$this->spectrum_account_model->drop_table($new_spectrum_accounts_table_name);
				}
			}
			catch(Exception $exception)
			{
				$exception_message = $exception->getMessage();
				$failure_message = "Failed to Import {$this->third_party_friendly_name} Client Accounts for {$info->date_or_file_name}, $exception_message";
				echo "$failure_message\n";

				$this->spectrum_account_model->drop_table($new_spectrum_accounts_table_name);
			}
		}
		else
		{
			$failure_message = "Failed to Import {$this->third_party_friendly_name} Client Accounts for {$info->date_or_file_name}, Bad Table";
			echo "$failure_message\n";

			$this->spectrum_account_model->drop_table($new_spectrum_accounts_table_name);
		}

		$subject = '';
		$internal_message = '';
		$external_message = '';
		$this->compose_failure_email(
			$subject,
			$internal_message,
			$external_message,
			$failure_message,
			$stats,
			$notifications,
			$info
		);

		$this->send_email($internal_from, $internal_to, $subject, $internal_message, null);
		$this->send_email($external_from, $external_to, $subject, $external_message, null);
		return false;
	}

	private function compose_failure_email(
		&$email_subject,
		&$email_internal_message,
		&$email_external_message,
		$failure_message,
		$stats,
		$notifications,
		$info
	)
	{
		$email_subject = $failure_message;
		$environment_message = "\n\n";
		$environment_message .= $this->cli_data_processor_common->get_environment_message_with_time();
		$environment_message .= "\n\n";

		$stats_message = "Stats:\n" . print_r($stats, true) . "\n\n";
		$notifications_message = "Notifications:\n" . print_r($notifications, true) . "\n\n";
		$info_message = "Info:\n" . print_r($info, true)."\n\n";

		$email_internal_message = $failure_message . $environment_message . $stats_message . $notifications_message . $info_message;
		$email_external_message = $failure_message;
	}

	private function compose_completion_email(
		&$email_subject,
		&$email_internal_message,
		&$email_external_message,
		&$email_attachments,
		$stats,
		$notifications,
		$info
	)
	{
		$email_subject = "";
		$email_message = "";

		$temp_file_path = "/tmp/spectrum_accounts_".date("Y-m-d_H-i-s_").rand(1000, 9999);
		mkdir($temp_file_path, 0700);
		$account_executive_to_sales_office_mismatch_file_base_name = "partner_mismatch_between_account_executive_and_sales_office_$info->date_or_file_name.csv";
		$account_executive_to_sales_office_mismatch_file_name = "$temp_file_path/$account_executive_to_sales_office_mismatch_file_base_name";
		$unknown_account_executive_file_base_name = "unknown_account_executive_per_advertiser_$info->date_or_file_name.csv";
		$unknown_account_executive_file_name = "$temp_file_path/$unknown_account_executive_file_base_name";
		$unknown_sales_office_file_base_name = "unknown_sales_office_per_advertiser_$info->date_or_file_name.csv";
		$unknown_sales_office_file_name = "$temp_file_path/$unknown_sales_office_file_base_name";
		$unknown_traffic_system_file_base_name = "unknown_traffic_system_per_advertiser_$info->date_or_file_name.csv";
		$unknown_traffic_system_file_name = "$temp_file_path/$unknown_traffic_system_file_base_name";
		$new_user_role_mismatch_file_base_name = "user_role_mismatch_$info->date_or_file_name.csv";
		$new_user_role_mismatch_file_name = "$temp_file_path/$new_user_role_mismatch_file_base_name";
		$new_user_partner_mismatch_file_base_name = "user_to_sales_office_partner_mismatch_$info->date_or_file_name.csv";
		$new_user_partner_mismatch_file_name = "$temp_file_path/$new_user_partner_mismatch_file_base_name";

		$zip_file_base_name = "data_task_csv_files_$info->date_or_file_name.zip";
		$zip_file_name = "$temp_file_path/$zip_file_base_name";

		$csv_files = array();

		if($info->is_success)
		{
			$email_subject .= "Success";
		}
		else
		{
			$email_subject .= "Failure";
		}

		$num_errors = 0;
		$num_warnings = 0;

		$num_action_items = 0;
		$num_action_item_features = 0;
		$actions_summary_message = '';

		$actions_messages = "\n\nAction Items:\n";

		$environment_message = $this->cli_data_processor_common->get_environment_message_with_time();
		$environment_message .= "\n\n";

		$num_unknown_account_executives_created = count($stats->unknown_account_executives);
		if($num_unknown_account_executives_created > 0)
		{
			$num_action_items += $num_unknown_account_executives_created;
			$actions_summary_message .= "- $num_unknown_account_executives_created unrecognized Account Executives need to be addressed.\n";
			$num_action_item_features++;

			$actions_messages .= "$num_unknown_account_executives_created unrecognized Account Executives need to have their emails and user names hooked up in User Editor:\n";
			$actions_messages .= "\t(File attached listing the advertisers associated with these unknown account executives '$unknown_account_executive_file_base_name.zip')\n";
			foreach($stats->unknown_account_executives as $unknown_account_executive_id => $unknown_account_executive_name)
			{
				$actions_messages .= "- Id: \"$unknown_account_executive_id\", Name: \"$unknown_account_executive_name\"\n";
			}

			$unknown_account_executive_file_handle = fopen($unknown_account_executive_file_name, "w");
			$column_headers = array(
				"Source CSV Line Number",
				"Source Row Advertiser",
				"Unknown Account Executive",
				"Account Executive AEID",
				"Sales Office",
				"Sales Office Partner"
			);
			fputcsv($unknown_account_executive_file_handle, $column_headers);

			foreach($notifications->unknown_account_executive_rows as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_advertiser_name,
					$row_data->new_account_executive_name,
					$row_data->new_account_executive_ul_id,
					$row_data->new_sales_office_name,
					$row_data->new_match_sales_office_partner_name,
				);
				fputcsv($unknown_account_executive_file_handle, $row_fields);
			}

			fclose($unknown_account_executive_file_handle);
			$csv_files[] = $unknown_account_executive_file_name;
		}

		$num_unknown_traffic_systems = count($stats->unknown_traffic_systems);
		if($num_unknown_traffic_systems > 0)
		{
			$num_action_items += $num_unknown_traffic_systems;
			$actions_summary_message .= "- $num_unknown_traffic_systems unrecognized Traffic Systems.\n";
			$num_action_item_features++;

			$actions_messages .= "\n$num_unknown_traffic_systems unrecognized Traffic Systems need fixing:\n";
			$actions_messages .= "\t(File attached listing the unknown sales offices '$unknown_traffic_system_file_base_name.zip')\n";
			foreach($stats->unknown_traffic_systems as $unknown_traffic_system_name => $not_used)
			{
				$actions_messages .= "\t- Traffic System Name: \"$unknown_traffic_system_name\"\n";
			}

			$unknown_traffic_system_file_handle = fopen($unknown_traffic_system_file_name, "w");
			$column_headers = array(
				"Source CSV Line Number",
				"Advertiser",
				"Traffic System"
			);
			fputcsv($unknown_traffic_system_file_handle, $column_headers);

			foreach($notifications->unknown_traffic_system_rows as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_advertiser_name,
					$row_data->new_traffic_system_raw
				);
				fputcsv($unknown_traffic_system_file_handle, $row_fields);
			}

			fclose($unknown_traffic_system_file_handle);

			$csv_files[] = $unknown_traffic_system_file_name;
		}

		$num_unknown_sales_offices = count($stats->unknown_sales_offices);
		if($num_unknown_sales_offices > 0)
		{
			$num_action_items += $num_unknown_sales_offices;
			$actions_summary_message .= "- $num_unknown_sales_offices unrecognized Sales Offices.\n";
			$num_action_item_features++;

			$actions_messages .= "\n$num_unknown_sales_offices unrecognized Sales Offices need partners:\n";
			$actions_messages .= "\t(File attached listing the unknown sales offices '$unknown_sales_office_file_base_name.zip')\n";
			foreach($stats->unknown_sales_offices as $unknown_sales_office_id => $unknown_sales_office_name)
			{
				$actions_messages .= "\t- Id: \"$unknown_sales_office_id\", Name: \"$unknown_sales_office_name\"\n";
			}

			$unknown_sales_office_file_handle = fopen($unknown_sales_office_file_name, "w");
			$column_headers = array(
				"Source CSV Line Number",
				"Advertiser",
				"Account Executive",
				"Sales Office"
			);
			fputcsv($unknown_sales_office_file_handle, $column_headers);

			foreach($notifications->unknown_sales_office_rows as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_advertiser_name,
					$row_data->new_account_executive_name,
					$row_data->new_sales_office_name
				);
				fputcsv($unknown_sales_office_file_handle, $row_fields);
			}

			fclose($unknown_sales_office_file_handle);

			$csv_files[] = $unknown_sales_office_file_name;
		}

		$num_account_executives_not_matching_sales_office = $stats->num_modified_sales_office_ul_ids_with_sales_office_partner_not_matching_ae_partner +
			$stats->num_modified_account_executives_where_sales_office_partner_not_matching_ae_partner +
			$stats->num_new_account_executives_where_sales_office_partner_not_matching_ae_partner;

		if($num_account_executives_not_matching_sales_office > 0)
		{
			$num_action_items += $num_account_executives_not_matching_sales_office;
			$actions_summary_message .= "- $num_account_executives_not_matching_sales_office account executive partner not matching sales office\n";
			$num_action_item_features++;

			$actions_messages .= "\n$num_account_executives_not_matching_sales_office resolutions needed between account executive partner and sales office partner:\n";
			$actions_messages .= "\t(Partner for the sales office takes precedence over account executive partner for the Advertiser.  Advertiser is associated with a place holder account executive until resolved.)\n";
			$actions_messages .= "\t(To resolve: either change which Dashboard partner the AE is associated with or change which AE is associated with the advertiser.)\n";
			$actions_messages .= "\t(File attached listing the missmatches '$account_executive_to_sales_office_mismatch_file_base_name.zip')\n";

			$account_executive_to_sales_office_mismatch_file_handle = fopen($account_executive_to_sales_office_mismatch_file_name, "w");
			$column_headers = array(
				"Source CSV Line Number",
				"Account Executive",
				"Account Executive Partner",
				"Sales Office",
				"Sales Office Partner",
				"Source Row Advertiser"
			);
			fputcsv($account_executive_to_sales_office_mismatch_file_handle, $column_headers);

			foreach($notifications->account_executives_mismatch_sales_offices as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_account_executive_name,
					$row_data->new_match_ae_user_partner_name,
					$row_data->new_sales_office_name,
					$row_data->new_match_sales_office_partner_name,
					$row_data->new_advertiser_name
				);
				fputcsv($account_executive_to_sales_office_mismatch_file_handle, $row_fields);
				$actions_messages .= "\t- Account Executive: \"$row_data->new_account_executive_name\" (partner: $row_data->new_match_ae_user_partner_name), Sales Office: \"$row_data->new_sales_office_name\" (partner: $row_data->new_match_sales_office_partner_name).  Advertiser: \"$row_data->new_advertiser_name\" - CSV line #$row_data->new_csv_line_number\n";
			}

			fclose($account_executive_to_sales_office_mismatch_file_handle);

			$csv_files[] = $account_executive_to_sales_office_mismatch_file_name;
		}

		$num_client_users_with_miss_matched_partner_id = count($notifications->client_users_with_miss_matched_partner_id);
		$num_agency_users_with_miss_matched_partner_id = count($notifications->agency_users_with_miss_matched_partner_id);
		$num_users_with_miss_matched_partner_id = $num_client_users_with_miss_matched_partner_id + $num_agency_users_with_miss_matched_partner_id;

		if($num_users_with_miss_matched_partner_id)
		{
			$num_action_items += $num_users_with_miss_matched_partner_id;
			$actions_summary_message .= "- $num_users_with_miss_matched_partner_id client/agency users with partner not matching sales office partner.\n";
			$num_action_item_features++;

			$actions_messages .= "\n$num_users_with_miss_matched_partner_id pre-existing Client/Agency users not linked because their partner doesn't match the Sales Office:\n";
			$actions_messages .= "\t(File attached listing the missmatches '$new_user_partner_mismatch_file_base_name.zip')\n";

			$csv_file_handle = fopen($new_user_partner_mismatch_file_name, "w");
			$column_headers = array(
				"Source CSV Line Number",
				"Advertiser Name",
				"User Email",
				"User Partner",
				"Sales Office",
				"Sales Office Partner"
			);
			fputcsv($csv_file_handle, $column_headers);

			foreach($notifications->client_users_with_miss_matched_partner_id as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_advertiser_name,
					$row_data->new_client_email,
					$row_data->new_match_client_user_partner_name,
					$row_data->new_sales_office_name,
					$row_data->new_match_sales_office_partner_name
				);
				fputcsv($csv_file_handle, $row_fields);

				$actions_messages .= "\t- Email: \"$row_data->new_client_email\", (partner: \"$row_data->new_match_client_user_partner_name\"), Sales Office: \"$row_data->new_sales_office_name\" (partner: \"$row_data->new_match_sales_office_partner_name\").  Advertiser: \"$row_data->new_advertiser_name\" - CSV line #$row_data->new_csv_line_number\n";
			}

			foreach($notifications->agency_users_with_miss_matched_partner_id as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_advertiser_name,
					$row_data->new_agency_email,
					$row_data->new_match_agency_email_user_partner_name,
					$row_data->new_sales_office_name,
					$row_data->new_match_sales_office_partner_name
				);
				fputcsv($csv_file_handle, $row_fields);

				$actions_messages .= "\t- Email: \"$row_data->new_agency_email\", (partner: \"$row_data->new_match_agency_email_user_partner_name\"), Sales Office: \"$row_data->new_sales_office_name\" (partner: \"$row_data->new_match_sales_office_partner_name\").  Advertiser: \"$row_data->new_advertiser_name\" - CSV line #$row_data->new_csv_line_number\n";
			}

			fclose($csv_file_handle);
			unset($csv_file_handle);

			$csv_files[] = $new_user_partner_mismatch_file_name;
		}

		$num_client_users_with_miss_matched_role = count($notifications->client_users_with_miss_matched_role);
		$num_agency_users_with_miss_matched_role = count($notifications->agency_users_with_miss_matched_role);

		$num_users_with_miss_matched_role = $num_client_users_with_miss_matched_role + $num_agency_users_with_miss_matched_role;

		if($num_client_users_with_miss_matched_role ||
			$num_agency_users_with_miss_matched_role
		)
		{
			$num_action_items += $num_users_with_miss_matched_role;
			$actions_summary_message .= "- $num_users_with_miss_matched_role client/agency users not matching expected role.\n";
			$num_action_item_features++;

			$actions_messages .= "\n$num_users_with_miss_matched_role existing Client/Agency users not linked because their role doesn't match the requested relationship:\n";
			$actions_messages .= "\t(File attached listing the missmatches '$new_user_role_mismatch_file_base_name.zip')\n";

			$csv_file_handle = fopen($new_user_role_mismatch_file_name, "w");
			$column_headers = array(
				"Source CSV Line Number",
				"Email",
				"Advertiser Name",
				"Target Role"
			);
			fputcsv($csv_file_handle, $column_headers);

			foreach($notifications->client_users_with_miss_matched_role as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_client_email,
					$row_data->new_advertiser_name,
					'CLIENT'
				);
				fputcsv($csv_file_handle, $row_fields);

				$actions_messages .= "\t- Email: \"$row_data->new_client_email\", Target Role: \"CLIENT\".  Advertiser: \"$row_data->new_advertiser_name\" - CSV line #$row_data->new_csv_line_number\n";
			}

			foreach($notifications->agency_users_with_miss_matched_role as $row_data)
			{
				$row_fields = array(
					$row_data->new_csv_line_number,
					$row_data->new_agency_email,
					$row_data->new_advertiser_name,
					'AGENCY'
				);
				fputcsv($csv_file_handle, $row_fields);

				$actions_messages .= "\t- Email: \"$row_data->new_client_email\", Role: \"AGENCY\".  Advertiser: \"$row_data->new_advertiser_name\" - CSV line #$row_data->new_csv_line_number\n";
			}

			fclose($csv_file_handle);
			unset($csv_file_handle);

			$csv_files[] = $new_user_role_mismatch_file_name;
		}

		$internal_error_messages = '';
		if(property_exists($notifications, 'internal_errors') && count($notifications->internal_errors) > 0)
		{
			$internal_error_messages = "Internal Errors:\n";
			foreach($notifications->internal_errors as $internal_error)
			{
				$internal_error_messages .= '- '.$internal_error."\n";
			}
		}

		if($num_action_items > 0)
		{
			$email_message .= "Action Items: $num_action_items in $num_action_item_features features.\n";
			$email_message .= $actions_summary_message;
		}
		else
		{
			$email_message .= "Action Items: None\n";
		}

		$email_message .= "\n";

		$info_message = "Info Messages:\n";
		$warning_message = "Warning Messages:\n";
		$errors_message = "Error Messages:\n";

		$info_message .= " - $stats->total_rows rows in Client Accounts file.\n";
		$info_message .= " - $stats->num_new_rows new Client rows.\n";
		$info_message .= " - $stats->num_modified_rows modified Client rows.\n";

		$num_new_rows_with_client_users = $stats->num_new_rows - $stats->num_new_rows_without_created_client_users;
		$info_message .= " - $num_new_rows_with_client_users new rows with client users.\n";
		$num_new_rows_with_agency_users = $stats->num_new_rows - $stats->num_new_rows_without_created_agency_users;
		$info_message .= " - $num_new_rows_with_agency_users new rows with agency users.\n";

		$info_message .= " - $stats->num_created_advertisers newly created advertisers.\n";
		$num_new_rows_matching_preexisting_advertiser = $stats->num_new_advertiser_rows_matching_existing_advertiser_spectrum_id +
			$stats->num_new_advertiser_rows_matching_existing_advertiser_requiring_no_name_change;
		$info_message .= " - $num_new_rows_matching_preexisting_advertiser new rows match preexisting advertisers.\n";

		$info_message .= " - $stats->num_sales_person_updates_for_advertisers Advertiser with changed sales person\n";
		$info_message .= " - $stats->num_modified_agency_account_ul_ids rows with agency changes.\n";
		$info_message .= " - $stats->num_modified_account_executive_ul_ids rows with changed account executive.\n";

		$warning_message .= " - $stats->num_rows_with_unknown_account_executives rows with unknown account executives. ($num_unknown_account_executives_created distinct unknown account executives.)\n";
		$num_warnings += $stats->num_rows_with_unknown_account_executives;

		$warning_message .= " - $num_account_executives_not_matching_sales_office rows with account executives who don't match the sales office partner.\n";
		$num_warnings += $num_account_executives_not_matching_sales_office;

		$warning_message .= " - $num_users_with_miss_matched_partner_id rows with client/agency users not linked because of partner mismatch.\n";
		$num_warnings += $num_users_with_miss_matched_partner_id;

		$warning_message .= " - $num_users_with_miss_matched_role rows with client/agency users who have a conflicting role.\n";
		$num_warnings += $num_users_with_miss_matched_role;

		$num_rows_with_unknown_sales_office = $stats->num_new_rows_with_unknown_sales_office +
			$stats->num_modified_sales_office_ul_ids_with_unknown_sales_offices;
		$errors_message .= " - $num_rows_with_unknown_sales_office rows with unknown sales offices. ($num_unknown_sales_offices distinct unknown sales offices.)\n";
		$num_errors += $num_rows_with_unknown_sales_office;

		$errors_message .= " - $stats->num_rows_with_unknown_traffic_system rows with unknown traffic systems. ($num_unknown_traffic_systems distinct unknown traffic systems.)\n";
		$num_errors += $stats->num_rows_with_unknown_traffic_system;

		if(property_exists($notifications, 'errors'))
		{
			$num_errors += count($notifications->errors);
			foreach($notifications->errors as $error)
			{
				$errors_message .= " - $error\n";
			}
		}

		$email_subject .= " Importing {$this->third_party_friendly_name} Client Accounts for {$info->date_or_file_name} ($num_errors errors, $num_warnings warnings)";

		$email_summary_message = $email_subject."\n";
		$email_summary_message .= "Importing {$this->third_party_friendly_name} Client Accounts for $info->date_or_file_name.\n\n";

		$email_internal_message = $email_summary_message . $email_message . $environment_message . $info_message . $warning_message . $errors_message . $internal_error_messages . $actions_messages;
		$email_external_message = $email_summary_message . $email_message . $info_message . $warning_message . $errors_message . $actions_messages; // no environment details

		if(!empty($csv_files))
		{
			if($this->zip_files($zip_file_name, $csv_files))
			{
				$email_attachments[] = $zip_file_name;
			}
		}
	}

	private function send_email($from_address, $to_address, $subject, $message, $attachments)
	{
		$message_type = 'text';

		$mailgun_attachments = array();
		if(!empty($attachments))
		{
			$mailgun_attachments = process_attachments_for_email($attachments);
		}

		$mail_response = mailgun(
			$from_address,
			$to_address,
			$subject,
			$message,
			$message_type,
			$mailgun_attachments
		);

		if($mail_response !== true)
		{
			echo "Failed to send email\n\n";
			echo $subject."\n";
			echo $message."\n";
			print_r($mail_response);
			echo "\n";
		}
	}

	public function access_manager()
	{
		if (!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', 'access_manager');
			redirect('login');
			return;
		}

                ini_set('memory_limit', '-1');
		$username=$this->tank_auth->get_username();
		$user_id=$this->tank_auth->get_user_id();
		$data['user_id'] = $user_id;

		$role=$this->tank_auth->get_role($username);
 		if (($role=='admin' || $role=='ops'|| $role=='sales'|| $role=='client'|| $role=='agency') && ($this->spectrum_account_model->get_user_access_for_access_manager($user_id)))
		{
			$data['title'] = 'Access Manager';
			$data['role'] = $role;
			$active_feature_button_id = 'access_manager';
                        $access_manager = $this->spectrum_account_model->fetch_advertisers_users_access_manager_view($user_id);
                        //$data['access_manager_export_data'] = $access_manager['export_data'];
                        $data['access_manager_view_data'] = $access_manager['view_data'];
			$this->vl_platform->show_views(
				$this,
				$data,
				$active_feature_button_id,
				'access_manager/subfeature_html_body',
				'access_manager/feature_css_header',
				false,
				'access_manager/feature_js',
				false
			);
		}
		else
		{
			redirect('report');
		}
	}
        
        public function download_csv()
	{
                if (!$this->tank_auth->is_logged_in())
		{
                        $this->session->set_userdata('referer', 'access_manager');
			redirect('login');
			return;
                }

                ini_set('memory_limit', '-1');
                $username = $this->tank_auth->get_username();
                $user_id = $this->tank_auth->get_user_id();                
                $role = strtolower($this->tank_auth->get_role($username));                

 		if (($role=='admin' || $role=='ops'|| $role=='sales'|| $role=='client'|| $role=='agency') && ($this->spectrum_account_model->get_user_access_for_access_manager($user_id)))
		{
                        $access_manager = $this->spectrum_account_model->fetch_advertisers_users_access_manager_view($user_id);

			if (count($access_manager['view_data']) > 0)
			{
				$this->load->view('access_manager/download_csv_data', $access_manager);
			}
			else
			{
				echo "<h2>Error 70429: Found no Access Manager data</h2>";
				return;
			}
                }
                else
		{
			redirect('report');
		}
	}

	public function get_advertiser_data_access_manager_grid()
        {
                $error_info = array();
                $is_success = true;
                $return_data = array();
                $allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
                $required_post_variables = array('adv_id');
                $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
                $table_data=null;
                $result=null;
                if($ajax_verify['is_success'])
                {
                        $user_id = $this->tank_auth->get_user_id();
                        $adv_id = $this->input->post('adv_id');
                        
                        // Get updated users data
                        $result = $this->spectrum_account_model->fetch_advertisers_users_access_manager_view($user_id, $adv_id);
                        $return_data['adv_users'] = $result['view_data'][0];
                }
                else
                {
                        $return_data['adv_users'] = array();
                        $is_success = false;
                }
                
                $json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
        }

    //advertisergroup
    public function fetch_advertisers_for_adgroup()
    {
    	$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
	    $ajax_verify = vl_verify_ajax_call($allowed_user_types);
	    $table_data=null;
	    $result=null;
	    if($ajax_verify['is_success'])
	    {
	       	$user_id =$this->tank_auth->get_user_id();
	       	$search_value = $this->input->post('q');
			$rows_start = $this->input->post('page');
			$rows_length = $this->input->post('page_limit');
			$selected_advertiser_id_string = $this->input->post('selected_adv_ids');
			$advertiser_group_id = $this->input->post('advertiser_group_id');
			$result = $this->spectrum_account_model->fetch_advertisers_for_adgroup($user_id, $search_value, $rows_start, $rows_length, $selected_advertiser_id_string, $advertiser_group_id);
	    }
		else
		{
			$is_success = false;
		}
        echo json_encode($result);
    }

    public function advertiser_group_submit()
    {
    	$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
	    $ajax_verify = vl_verify_ajax_call($allowed_user_types);
	    $table_data=null;
	    $result=null;
	    if($ajax_verify['is_success'])
	    {
	       	$user_id =$this->tank_auth->get_user_id();
			$advertiser_group_name = $this->input->post('advertiser_group_name');
			$advertiser_group = $this->input->post('advertiser_group');
			$advertiser_group_id = $this->input->post('advertiser_group_id');
	        $result = $this->spectrum_account_model->advertiser_group_submit($user_id, $advertiser_group_name, $advertiser_group, $advertiser_group_id);
	    }
		else
		{
			$is_success = false;
		}
        echo json_encode($result);
    }

    public function advertiser_group_delete()
    {
    	$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
	    $ajax_verify = vl_verify_ajax_call($allowed_user_types);
	    $table_data=null;
	    $result=null;
	    if($ajax_verify['is_success'])
	    {
	       	$user_id =$this->tank_auth->get_user_id();
			$advertiser_group_id = $this->input->post('advertiser_group_id');
	        $result = $this->spectrum_account_model->advertiser_group_delete($advertiser_group_id);
	    }
		else
		{
			$is_success = false;
		}
        echo json_encode($result);
    }


	public function add_edit_advertiser_user()
	{
		$error_info = array();
		$is_success = true;
		$return_data = array();

		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
		$required_post_variables = array('email', 'adv_id');
		$ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
		$table_data=null;
		if($ajax_verify['is_success'])
		{
			$first_name = $this->input->post('first_name');
			$last_name = $this->input->post('last_name');
			$email = $this->input->post('email');
			$is_power_user = $this->input->post('is_power_user');
			$role = $this->input->post('role');
			$adv_id = $this->input->post('adv_id');
			$adv_name = $this->input->post('adv_name');
			$user_id = $this->input->post('user_id');
			$data = null;
			if ($user_id == null || $user_id == "")
			{
				if (strpos($adv_id, ",") === false)
				{
					$data = $this->spectrum_account_model->add_advertiser_user($first_name, $last_name, $email, $is_power_user, $role, $adv_id, $user_id);
					if (!empty($data['user_id']))
					{
						$password_reset_key = $this->spectrum_account_model->create_user_password_reset_key();
						$this->users->set_password_key($data['user_id'], $password_reset_key);
					}
				}
				else
				{
					$adv_id_array = explode(",", $adv_id);
					foreach ($adv_id_array as $each_adv_id)
					{
						if ($each_adv_id == null || $each_adv_id == "")
						{
							continue;
						}
						$data = $this->spectrum_account_model->add_advertiser_user($first_name, $last_name, $email, $is_power_user, $role, $each_adv_id, $user_id);
						if (!empty($data['user_id']))
						{
							$password_reset_key = $this->spectrum_account_model->create_user_password_reset_key();
							$this->users->set_password_key($data['user_id'], $password_reset_key);
						}
					}
				}


				$return_data['data'] = $data;
			}
			else
			{
				$data = $this->spectrum_account_model->edit_advertiser_user($first_name, $last_name, $email, $is_power_user, $role, $adv_id, $user_id);
				$return_data['data'] = array('return_flag'=>$data);
			}
		}
		else
		{                        
			$is_success = false;
		}
		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function remove_advertiser_user()
	{
		$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
	    $required_post_variables = array('adv_id', 'user_id');
	    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
	    $table_data=null;
	    if($ajax_verify['is_success'])
	    {
			$adv_id = $this->input->post('adv_id');
			$user_id = $this->input->post('user_id');
			$data = $this->spectrum_account_model->remove_advertiser_user($adv_id, $user_id);
			$return_data['data'] = $data;
		}
		else
		{
			$is_success = false;
		}
		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function remove_advertiser_user_bulk()
	{
		$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
	    $required_post_variables = array('adv_id', 'email');
	    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
	    $table_data=null;
	    if($ajax_verify['is_success'])
	    {
			$adv_id = $this->input->post('adv_id');
			$email = $this->input->post('email');
			$data = $this->spectrum_account_model->remove_advertiser_user_bulk($adv_id, $email);
			$return_data['data'] = $data;
		}
		else
		{
			$is_success = false;
		}
		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function resend_link_flag()
	{
		$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
                $required_post_variables = array('user_id', 'adv_name', 'adv_id');
                $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
                $table_data=null;
                if($ajax_verify['is_success'])
                {
			$user_id = $this->input->post('user_id');
			$adv_name = $this->input->post('adv_name');
			$sales_person_info = $this->advertisers_model->get_advertiser_sales_person_info($this->input->post('adv_id'));
			$return_data['data'] = $this->send_reaction_email_to_new_user($user_id, $adv_name, $sales_person_info);
		}
		else
		{
			$is_success = false;
		}
		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

  	private function send_reaction_email_to_new_user($user_id, $adv_name, $sales_person_info)
  	{
  		$user = $this->users->get_user_by_id($user_id, TRUE);
 		$partner = $this->users->get_partner_details_by_id($user->partner_id);
		$data = $this->spectrum_account_model->send_email_to_new_user($user_id, $user->email, $user->firstname, $user->lastname, $partner->cname, $adv_name, $partner->partner_name, $partner->support_email_address, $sales_person_info);
		$this->spectrum_account_model->update_last_login_flag_for_user($user_id);
		return $data;
  	}

	private function get_report_data_json(&$return_data, $error_info, $is_success)
	{
		if(count($error_info) > 0)
		{
			if(array_key_exists('errors', $return_data) && is_array($return_data['errors']))
			{
				$return_data['errors'] = array_merge($return_data['errors'], $error_info);
			}
			else
			{
				$return_data['errors'] = $error_info;
			}
		}
		if(array_key_exists('is_success', $return_data))
		{
			$return_data['is_success'] = $return_data['is_success'] || $is_success;
		}
		else
		{
			$return_data['is_success'] = $is_success;
		}
		return json_encode($return_data);
	}

	public function fetch_user_info_by_email_select2()
	{
		$error_info = array();
		$is_success = true;
		$return_data = array();
		$allowed_user_types = array('ops', 'admin', 'sales', 'client', 'agency');
	    $required_post_variables = array('page_limit', 'page');
	    $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables);
	    $dropdown_list=array
	    (
			'result'=>array(),
			'more'=>false
		);
	    if($ajax_verify['is_success'])
	    {
	    	$user_id=$this->tank_auth->get_user_id();
	    	$raw_term=$this->input->post('q');
			$adv_id=$this->input->post('adv_id');
			$page_limit=$this->input->post('page_limit');
			$page_number=$this->input->post('page');
			if ($raw_term&&is_numeric($page_limit)&&is_numeric($page_number))
			{
				if ($raw_term != '%')
				{
					$search_term='%'.str_replace(" ", "%", $raw_term).'%';
				}
				else
				{
					$search_term=$raw_term;
				}
				$mysql_page_number=($page_number-1)*$page_limit;
				$result=false;
				$result=$this->spectrum_account_model->fetch_user_info_by_email($user_id, $search_term, $adv_id, $mysql_page_number, ($page_limit+1));

				if ($page_number == 1 && $adv_id == '')
				{
					$dropdown_list['result'][]=array( "id"=>'*New*', "text"=>'*New*');
				}
				if ($result)
				{
					if (count($result)==$page_limit+1)
					{
						$real_count=$page_limit;
						$dropdown_list['more']=true;
					}
					else
					{
						$real_count=count($result);
					}
					for ($i=0; $i < $real_count; $i++)
					{
						$dropdown_list['result'][]=array( "id"=>$result[$i]['id'], "text"=>$result[$i]['tag_copy']);
					}
				}
			}
		}
		else
		{
			$is_success = false;
		}
		echo json_encode($dropdown_list);
	}
}
