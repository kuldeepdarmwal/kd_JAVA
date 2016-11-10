<?php

class Carmercial extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('mailgun');
		$this->load->library('cli_data_processor_common');
		$this->load->model('carmercial_model');
	}

	public function carmercial_upload($manual_date = null)
	{
		if($this->input->is_cli_request())
		{
			$message = array();
			$errors = array();
			$fatal_error = false;
			$num_errors = 0;
			$num_warnings = 0;
			$this->cli_data_processor_common->mark_script_execution_start_time();

			try
			{
				if(!empty($manual_date))
				{

					if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $manual_date))
					{
						echo "USAGE: php index.php carmercial carmercial_upload YYYY-MM-DD.\n";
						return;
					}

					$report_date = $manual_date;
				}
				else
				{
					$report_date = date("Y-m-d", strtotime('-1 day'));
				}

				$message['basic_info'] = array();
				$message['dealer_info'] = array();

				$dealers = $this->carmercial_model->get_charterauto_dealer_list();
				if($dealers['is_success'] === false)
				{
					throw(new Exception("Failed to retrieve dealers from third-party data"));
				}
				$dealer_list = $dealers['data'];
				$dealers_inserted = $this->carmercial_model->update_dealer_list($dealer_list);
				if($dealers_inserted === false)
				{
					throw(new Exception("Failed to update dealer listing"));
				}				
				$message['dealer_info'][] = "<u>Dealers</u>";
				$message['dealer_info'][] = "Dealers found: ".count($dealer_list);
				$message['dealer_info'][] = "New dealers added: ".$dealers_inserted;

				$message['tp_data_info'] = array();
				$upload_result = $this->carmercial_model->upload_third_party_dealer_data_to_database_for_date($report_date);
				if($upload_result['is_success'] == false)
				{
					throw(new Exception($upload_result['err_msg']));
				}
				if(!empty($upload_result['err_msg']))
				{
					$errors[] = "<b>".$upload_result['err_msg']."<b>";
					$num_errors += count($errors);
				}

				$message['tp_data_info'][] = "<u>Car-mercial Data</u>";
				$message['tp_data_info'][] = "Rows Inserted: ".$upload_result['rows_inserted'];
				$found_insert_match = $upload_result['video_count_found'] - $upload_result['video_count_ignored'] == $upload_result['video_count_inserted'];
				$message['tp_data_info'][] = "Video Counts: ".$upload_result['video_count_found']." found (".$upload_result['video_count_ignored']." dashboard views ignored) - ".$upload_result['video_count_inserted']." in records (".($found_insert_match ? "OK!" : "NOT OK!").")";
				if(!$found_insert_match)
				{
					$num_warnings++;
					$message['tp_data_info'][] = "<b>[WARNING] Mismatch in video plays found and video plays inserted into database</b>";
				}

				//Link accounts 
				$message['account_linkage'] = array();
				$message['account_linkage'][] = "<u>Spectrum Account Linkage</u>";
				$refreshed_carmercial_account_link_result = $this->carmercial_model->refresh_carmerical_account_links();
				if($refreshed_carmercial_account_link_result['is_success'] == false)
				{
					throw(new Exception($refreshed_carmercial_account_link_result['err_msg']));
				}
				else
				{
					if($refreshed_carmercial_account_link_result['unmatched_accounts'] === -1)
					{
						$num_warnings++;
						$message['account_linkage'][] = "<strong>[WARNING] Failed to gather list of mismatched accounts</strong>";
					}
					else
					{
						$message['account_linkage'][] = "Accounts linked: {$refreshed_carmercial_account_link_result['account_links_created']}";
						if(!empty($refreshed_carmercial_account_link_result['unmatched_accounts']))
						{
							$num_warnings++;
							$message['account_linkage'][] = "<strong>[WARNING] Failed to link accounts:</strong>";
							foreach($refreshed_carmercial_account_link_result['unmatched_accounts'] as $unmatched_account)
							{
								$message['account_linkage'][] = "--{$unmatched_account['friendly_dealer_name']} ({$unmatched_account['dealer_name']} - {$unmatched_account['frq_id']})";
							}
						}
					}
				}
			}
			catch(Exception $e)
			{
				echo $e->getMessage()."\n";
				$errors[] = "<b>Error: ".$e->getMessage()."<b>";
				$num_errors++;
				$fatal_error = true;
			}
			
			$message['basic_info'][] = $this->cli_data_processor_common->get_environment_message_with_time();
			
			$subject = "Car-mercial Data Uploader (".$report_date.") - ";
			$summary = array();
			if($num_errors)
			{
				if($fatal_error)
				{
					$subject .= "Failed with ".$num_errors." errors";
				}
				else
				{
					$subject .= "Completed with ".$num_errors." errors";
				}
			}
			else if($num_warnings)
			{
				$subject .= "Completed with ".$num_warnings." warnings";
			}
			else
			{
				$subject .= "Completed successfuly";
			}
			$summary[] = $subject;
			$summary[] = "Errors: ".$num_errors;
			$summary[] = "Warnings: ".$num_warnings;
			$message['errors'] = $errors;
			$message = array_merge(array($summary), $message);

			$message = array_map(array($this, 'flatten_message_array_for_email'), $message);
			$message = nl2br(implode("\n\n", $message));
			
			$this->send_email("Car-Mercial Data Uploader <tech.logs@frequence.com>",
							  "Tech Logs <tech.logs@frequence.com>",
							   $subject,
							   $message);
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

}

?>