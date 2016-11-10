<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// Common functions used by our dataprocessor
// 
// Main functions to use:
// - cli_data_processor_common->get_environment_message();
// - cli_data_processor_common->get_script_execution_time_message();
// - cli_data_processor_common->mark_script_execution_start_time();
// - cli_data_processor_common->mark_script_execution_end_time();
//
// Example usage in application/libraries/cli_data_processor_common.php (on branch cli_data_processor_common_scott if it's not on develop yet)
class cli_data_processor_common
{
	private $script_start_time = null;
	private $script_end_time = null;

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->database();
	}

	private function get_end_of_line($message_type)
	{
		$end_of_line = "\n";

		switch($message_type)
		{
			case "html":
				$end_of_line = "<br>";
				break;
			case "text":
			default:
				$end_of_line = "\n";
				break;
		}

		return $end_of_line;
	}

	// get the raw environment info data
	public function get_environment_info()
	{
		$data = array(
			'physical_server_hostname' => gethostname(),
			'database_server_hostname' => $this->ci->db->hostname,
			'database_name' => $this->ci->db->database,
			'current_working_directory' => getcwd()
		);

		return (object)$data;
	}

	// $message_type determines if it's formatted for a 'text' email or 'html' email
	// $end_of_line is an alternative string appended to end of line
	//
	// Output: Message in the form: "
	// 	Physical server: scott-ubuntu-vm
	// 	Directory: /var/websites/application/local_dev/public
	// 	Database: db.vantagelocaldev.com::vantagelocal_dev	
	// "
	public function get_environment_message($message_type = 'text', $end_of_line = null)
	{
		if(is_null($end_of_line))
		{
			$end_of_line = $this->get_end_of_line($message_type);
		}

		$environment_info = $this->get_environment_info();

		$message = "Database: ".$environment_info->database_server_hostname."::".$environment_info->database_name.$end_of_line;
		$message .= "Physical server: ".$environment_info->physical_server_hostname.$end_of_line;
		$message .= "Directory: ".$environment_info->current_working_directory.$end_of_line;
		
		return $message;
	}
	
	// $message_type determines if it's formatted for a 'text' email or 'html' email
	// $end_of_line is an alternative string appended to end of line
	//
	// Output: Message in the form: "
	// 	Physical server: scott-ubuntu-vm
	// 	Directory: /var/websites/application/local_dev/public
	// 	Database: db.vantagelocaldev.com::vantagelocal_dev
	// "
	public function get_environment_message_with_time($message_type = 'text', $end_of_line = null)
	{
		if(is_null($end_of_line))
		{
			$end_of_line = $this->get_end_of_line($message_type);
		}
		if(is_null($this->script_start_time))
		{
			return "Error formatting time. Please call mark_script_execution_start_time() at the beginning of execution.";
		}
		
		$this->mark_script_execution_end_time();
		$message =  "===========================================".$end_of_line;
		
		$message .= "Start Time: ".$this->get_start_time_formatted_string().$end_of_line;
		$message .=  $this->get_script_execution_time_message($message_type, $end_of_line);
		$message .= $end_of_line. $this->get_environment_message($message_type, $end_of_line);
		$message .= "===========================================".$end_of_line;
		return $message;
	}

	// call before starting doing the data processing.  Marks the beginning of execution.
	public function mark_script_execution_start_time()
	{
		$this->script_start_time = microtime(true);
	}

	//Returns start time in date format. Call after marking start time
	public function get_start_time_formatted_string()
	{
	    if($this->script_start_time != null)
	    {
		return date("Y-m-d H:i:s T O", $this->script_start_time);
	    }
	    return "Error formatting time. Time values not populated correctly in code.";
	}
	
	// call when the data processing is complete but before calling get_script_execution_time_message() the email. Marks the end of execution.
	public function mark_script_execution_end_time()
	{
		$this->script_end_time = microtime(true);
	}

	// get the raw script execution time data
	public function get_script_execution_time()
	{
		if(is_null($this->script_end_time) || is_null($this->script_start_time))
		{
			return false;
		}
		else
		{
			return $this->script_end_time - $this->script_start_time;
		}
	}

	// $message_type determines if it's formatted for a 'text' email or 'html' email
	// $end_of_line is an alternative string appended to end of line
	//
	// Output: Message in the form of "Processing time: 4 seconds"
	public function get_script_execution_time_message($message_type = 'text', $end_of_line = null)
	{
		if(is_null($end_of_line))
		{
			$end_of_line = $this->get_end_of_line($message_type);
		}

		$execution_time = $this->get_script_execution_time();
		if($execution_time === false)
		{
			$message = "Error generating processing time.  Time values not populated correctly in code.".$end_of_line;
		}
		else
		{
			$execution_time_string = "";
			$hours = (int)($execution_time/60/60);
			$minutes = (int)($execution_time/60)-$hours*60;
			$seconds =(int)$execution_time-$hours*60*60-$minutes*60;
			if($hours > 0)
			{
			    $execution_time_string .= $hours."h, ";
			}
			if($minutes > 0)
			{
			    $execution_time_string .= $minutes."m, ";
			}
			if($seconds > 0)
			{
			    $execution_time_string .= $seconds."s";
			}
			$message = "Processing time: ".$execution_time_string.$end_of_line;
		}

		return $message;
	}

	/**
	 * @param Array $method_name - the value of __METHOD__ as called from within the method to check
	 * @returns Array - information about the method and processes, or FALSE if no processes are found
	 */
	public function get_active_processes($method_name)
	{
		$method_parts = explode('::', $method_name);
		$cli_reference = strtolower($method_parts[0]) . ' ' . $method_parts[1];
		$folder = str_replace("/application/controllers", "", __DIR__);
        $process_search_script = 'ps auxwww | grep "php.*index\.php ' . $cli_reference . '" | grep "'.$folder.'" | grep -v "grep\| ' . posix_getpid() . ' \|$SHELL"';
		$cron_process_output = array();
		exec($process_search_script, $cron_process_output);

		if(!empty($cron_process_output))
		{
			return array(
				'method'        => $method_name,
				'cli_reference' => $cli_reference,
				'processes'     => $cron_process_output
			);
		}

		return false;
	}

	/**
	 * @param String $message
	 * @returns String $message, unchanged
	 */
	public function cron_log($message)
	{
		echo time() . ": $message\n";
		return $message;
	}

}
