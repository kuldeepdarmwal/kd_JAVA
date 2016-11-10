<?php
class Syscode_upload extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url'));
		$this->load->library('tank_auth');
		$this->load->model('syscode_upload_model');
	}

	private $errors = [];
	private $file_fields = array('Provider', 'Owner', 'DMA', 'Syscode', 'Sysname', 'Region', 'Zip Code', 'UTC offset', 'UTC DST offset');

	public function index()
	{
		if(!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer','syscode_upload');
			redirect('login');
			return;
		}
		$username = $this->tank_auth->get_username();
		$role = $this->tank_auth->get_role($username);
		if ($role == 'ops' || $role == 'admin')
		{
			///setup common header
			$data['username'] = $username;
			$data['firstname'] = $this->tank_auth->get_firstname($data['username']);
			$data['lastname'] = $this->tank_auth->get_lastname($data['username']);
			$data['user_id'] = $this->tank_auth->get_user_id();

			$data['active_menu_item'] = 'syscode_uploader'; // the view will make this menu item active
			$data['title'] = 'Syscode Uploader';

			$data['file_fields'] = $this->file_fields;

			$this->load->view('ad_linker/header', $data);
			$this->load->view('syscode_upload_form_body_view', $data);
		}
		else
		{
			redirect('director');
		}
	}

	public function ajax_submit_file()
	{
		$allowed_user_types = array('admin','ops');
		$ajax_verify = $this->verify_ajax_call($allowed_user_types);
		$return_array = array('is_success' => $ajax_verify['is_success']);

		if($ajax_verify['is_success'])
		{
			$delimiter = $this->get_delimiter($_FILES['userfile']['name']);
			if($delimiter)
			{
				$file_path = $_FILES['userfile']['tmp_name'];
				$file_contents = $this->csv_to_array($delimiter);
				if($file_contents['is_success'])
				{
					$update_syscodes = $this->syscode_upload_model->update_syscodes($file_contents['array_data']);
					if($update_syscodes['is_success'])
					{
						$return_array['messages'] = array_map(function($a){ return nl2br($a); }, $update_syscodes['messages']);
					}
					else
					{
						$return_array['is_success'] = false;
						$return_array['errors'] = array_merge($this->errors, $update_syscodes['errors']);
					}
				}
				else
				{
					$return_array['is_success'] = false;
					$return_array['errors'] = $this->errors;
				}
			}
			else
			{
				$this->errors[] = 'File type not supported for this upload';
				$return_array['is_success'] = false;
				$return_array['errors'] = $this->errors;
			}
		}

		echo json_encode($return_array);
	}

	private function get_delimiter($file_path)
	{
		$file_info = pathinfo($file_path);
		switch (strtolower($file_info['extension']))
		{
			case 'tsv':
				return "\t";
			case 'csv':
				return ',';
			default:
				return false;
		}
	}

	private function csv_to_array($delimiter = ',')
	{
		$array = $fields = array();
		$i = 0;
		$handle = @fopen($_FILES['userfile']['tmp_name'], "r");
		if ($handle) {
			while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
			{
				if (empty($fields))
				{
					if($this->is_first_row_equal_to_fields($row))
					{
						$fields = $row;
					}
					else if(count($row) == count($this->file_fields))
					{
						$fields = $this->file_fields;
					}
					else
					{
						$this->errors[] = 'Headers/columns of file do not match needed file fields';
						return array('is_success' => false);
					}

					if(count($fields) <= 1)
					{
						$this->errors[] = 'No header row in file';
						return array('is_success' => false);
					}
					continue;
				}

				foreach ($row as $k => $value)
				{
					$array[$i][$fields[$k]] = $value;
				}
				$i++;
			}
			if (!feof($handle))
			{
				$this->errors[] = 'Error: unexpected fgets() fail';
				return array('is_success' => false);
			}
			fclose($handle);
		}
		return array('is_success'=> true, 'array_data' => $array);
	}

	private function is_first_row_equal_to_fields($row)
	{
		$row = array_values(array_filter($row));
		$temp_fields = $this->file_fields;
		sort($temp_fields);
		sort($row);

		$needed_variables = array('Region', 'Sysname', 'Syscode', 'Zip Code'); // These are the variables that are required later down the line

		$are_arrays_equal = ($row == $temp_fields);
		$array_diff = array_diff($temp_fields, $row);

		// Return true if arrays are equal OR if the required variables aren't in the array_diff from the row and the file fields arrays
		return $are_arrays_equal || count(array_diff($needed_variables, $array_diff)) == count($needed_variables);
	}

	private function verify_ajax_call($allowed_roles, $post_variables = array())
	{
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$response = array('is_success' => true, 'errors' => array());
			if($this->tank_auth->is_logged_in())
			{
				$username = $this->tank_auth->get_username();
				$role = $this->tank_auth->get_role($username);
				if(!in_array($role,$allowed_roles))
				{
					$response['is_success'] = false;
					$response['errors'][] = $role.' not authorized';
				}
			}
			else
			{
				$response['is_success'] = false;
				$response['errors'][] = "public not authorized";
			}

			//test each required post variable - if doesn't exist - ajax fail
			foreach($post_variables as $post_variable)
			{
				$response['post'][$post_variable] = $this->input->post($post_variable);
				if($response['post'][$post_variable] === false)
				{
					$response['errors'][] = 'post variable: `'.$post_variable.'` not found';
					$response['is_success'] = false;
				}
			}
			return $response;
		}
		else
		{
			show_404();
		}
	}

}

