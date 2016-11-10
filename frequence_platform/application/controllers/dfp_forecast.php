<?php
class dfp_forecast extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->helper('mailgun');
		$this->load->library('cli_data_processor_common');
		$this->load->library('google_api');
		$this->load->model('mpq_v2_model');
	}

	public function forecast()
	{
		if ($this->input->is_cli_request())
		{
			//check the cron status and if it is COMPLETE or difference between last run and now > 15 minutes then run the cron.			
			$dfp_forecast_cron_status = $this->mpq_v2_model->check_status_for_dfp_forecast_cron();
			$diff_in_minutes = 0;
			
			if($dfp_forecast_cron_status['status'] == 'IN_PROGRESS' && isset($dfp_forecast_cron_status['last_run']))
			{
				$dEnd = new DateTime('NOW');
				$dStart  = new DateTime($dfp_forecast_cron_status['last_run']);
				$dDiff = $dStart->diff($dEnd);
				$diff_in_minutes = $dDiff->i;
			}
			
			if ($dfp_forecast_cron_status['status'] == 'COMPLETE' || $diff_in_minutes > 15)
			{
				if ($diff_in_minutes > 15)
				{
					//Send email to tech team
					$mailgun_extras = array();
					$mailgun_extras['h:reply-to'] = "tech@frequence.com,tech.monitors@frequence.com";

					mailgun(
						'no-reply@brandcdn.com',
						'tech.monitors@frequence.com',
						"DFP Forecast Cron Issue",
						"DFP forecast cron is on IN_PROGRESS status for more than 15 minutes. Please login in server and check if the process is stuck and kill it.",
						"html",
						$mailgun_extras
					);					
				}
				
				//Get all rows from the subproduct table
				$date_time = date("Y-m-d h:i:sa");
				echo "Starting forecast script : ".$date_time."\n\n";
				$forecast_flights = $this->mpq_v2_model->get_o_o_flights_to_forecast();
				echo "Found ".count($forecast_flights['flights_to_process'])." timeseries IDs for processing\n";
				$return_array = $this->mpq_v2_model->forecast_timeseries($forecast_flights['flights_to_process']);

				if (count($return_array['processed_timeseries_ids']) != 0)
				{
					echo "Processed Timeseries IDs : ".implode(",",$return_array['processed_timeseries_ids'])."\n";
					echo "Successful : ".implode(",",$return_array['successful_timeseries_ids'])."\n";
					echo "Failed : ".implode(",",$return_array['failed_timeseries_ids'])."\n";
				}

				$time_beginning  = strtotime($date_time);
				$time_ending = strtotime(date("Y-m-d h:i:sa"));
				$difference_in_seconds = $time_ending - $time_beginning;

				echo "\nEnding forecast script. Time taken : ".$difference_in_seconds." seconds.\n\n\n";
				
				//Update the cron status
				$this->mpq_v2_model->update_status_for_dfp_forecast_cron("COMPLETE");
			}
		}

	}
}
?>