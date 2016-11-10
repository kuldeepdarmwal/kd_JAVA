<?php
class geo_in_ads extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library(array('tank_auth','vl_platform'));
		$this->load->model('geo_in_ads_model');
  	}

	public function index()
	{
		$autherized_user = false;
		
		if ($this->tank_auth->is_logged_in())
		{
			$username = $this->tank_auth->get_username();
			$role = $this->tank_auth->get_role($username);

			if ($role == 'ops' || $role == 'creative' || $role == 'admin')
			{
				$this->load->view('geo_in_ads/message_setup');
				$autherized_user = true;
			}
		}
		
		if (!$autherized_user)
		{
			redirect('login');
		}
	}
	
	public function message_csv_upload()
	{
		$file_data = $_FILES['message_file'];
		$response = array();
		$error_messages = array();

		if (isset($file_data))
		{
			$tmp_name = $_FILES['message_file']['tmp_name'];
			$csv_data_array = array_map('str_getcsv', file($tmp_name));

			if (count($csv_data_array) > 1)
			{
				$cnt = 0;
				foreach ($csv_data_array as $data_row)
				{
					if ($cnt == 0 && ($data_row[0] != 'Version ID' || $data_row[1] != 'Zip Code' || $data_row[2] != 'Message') )
					{
						$error_messages[] = "File headers are not matching with sample template";
						break;
					}
					elseif ($cnt == 0)
					{
						$cnt++;
						continue;
					}

					$version_id = $data_row[0];
					$zip_codes = $data_row[1]; 
					$message = $data_row[2];
					$response = $this->set_message_in_db($version_id, $zip_codes, $message);
					
					if (isset($response) && count($response) > 0)
					{
						$error_messages[] = implode(", ",$response);
					}
				}
			}
			else
			{
				$error_messages[] = "No data found in CSV file";
			}
		}
		else
		{
			$error_messages[] = 'Not able to read the uploaded CSV file';
		}
		
		if (count($error_messages) > 0)
		{
			$response = array(
			    'error' => implode(", ",$error_messages)
			);
		}

		$json = json_encode($response);
		header('Access-Control-Allow-Origin: *');

		exit($json);
	}

	public function single_message_upload()
	{
		$response   = "";
		$error_messages = $this->set_message_in_db($_POST['VersionID'],$_POST['ZipCode'],$_POST['Message']);

		if (isset($error_messages) && count($error_messages) > 0)
		{
			$response = array(
			    'error' => implode(", ",$error_messages)
			);
		}

		$json = json_encode($response);
		header('Access-Control-Allow-Origin: *');

		exit($json);
	}
	
	private function set_message_in_db($version_id, $zip_code, $message)
	{
		$version_id = trim($version_id);
		$zip_code = trim($zip_code);
		$message = trim($message);
		$error_messages = array();
		
		if (!is_numeric($version_id))
		{
			$error_messages[] = "Version ID - $version_id should be numeric";
		}

		if (!is_numeric($zip_code))
		{
			$error_messages[] = "Zip code - $zip_code for Version ID - $version_id contains non-numeric value";
		}
		else if(strlen($zip_code) != 5)
		{
			$error_messages[] = "Zip code - $zip_code for Version ID - $version_id is invalid in length";
		}

		if (count($error_messages) == 0)
		{
			$old_msg_id = $this->geo_in_ads_model->get_message_id_by_adset_version_id_and_zip_code($version_id,$zip_code);
			
			if (!($old_msg_id))
			{
				$status = $this->geo_in_ads_model->insert_message($version_id,$zip_code,$message);
			}
			else
			{
				$status = $this->geo_in_ads_model->update_message($version_id,$zip_code,$message,$old_msg_id);
			}
			
			if (!$status)
			{
				$error_messages[] = "Error while saving the message for zip code - ".$zip_code; 
			}
		}
		
		return $error_messages;
	}
	
	public function download_csv_template()
	{
		header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="data_load_template.csv"');
		header('Pragma: no-cache');
		header("Expires: 0");
		header("Content-Transfer-Encoding: UTF-8");

		// create a file pointer connected to the output stream
		$output = fopen('php://output', 'w');

		// output the column headings
		fputcsv($output, array("Version ID","Zip Code","Message"));

		// close file
		fclose($output);
	}
	
	public function delete_messages_for_version()
	{
		$response   = "";
		$adset_version_id = $_POST['VersionID'];
		$zip_codes_for_adset_version_id = $this->geo_in_ads_model->get_zips_by_adset_version_id($adset_version_id);

		if ($zip_codes_for_adset_version_id)
		{
			$is_successful = $this->geo_in_ads_model->delete_messages_for_adset_version_id($adset_version_id);
			if (!$is_successful)
			{
				$response = array(
					'error' => 'Not able to delete the message..please try again'
				);
			}
		}
		else
		{
			$response = array(
				'error' => 'Messages not found for this version id'
			);
		}

		$json = json_encode($response);
		header('Access-Control-Allow-Origin: *');

		exit($json);
	}
}