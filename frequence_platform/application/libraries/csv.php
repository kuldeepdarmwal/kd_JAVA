<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Csv
{

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	public function download_posted_inline_csv_data()
	{
		$file_name = $this->ci->input->post('file_name');
		$inline_csv_data = $this->ci->input->post('csv_data');

		if(!empty($inline_csv_data))
		{
			$csv_data = $this->parse_inline_csv($inline_csv_data);
			$this->download_data_as_csv($file_name, $csv_data);
		}
		else
		{
			echo 'no csv data';
		}
	}

	public function download_data_as_csv($file_name, $csv_data)
	{
		$data = array();

			header('Content-Type: text/csv');
			header('CacheControl: no-cache');
			header('Expires: 0');

			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			$should_skip_cache = strpos($user_agent, 'MSIE 7.0') !== false;
			$should_skip_cache = $should_skip_cache || (strpos($user_agent, 'MSIE 8.0') !== false);
			$should_skip_cache = $should_skip_cache || (strpos($user_agent, 'MSIE 9.0') !== false);
			if(!$should_skip_cache)
			{
				header('Pragma: no-cache');
			}
			header('Content-Disposition: attachment; filename='.$file_name.'.csv');

			$csv_formatted_rows = array_map(function($row) {
				return implode(',', $row);
			}, $csv_data);

			echo implode("\r\n", $csv_formatted_rows);

			exit;
	}

	public function parse_inline_csv($inline_data)
	{
		$csv_data = explode(":^:", $inline_data);
		foreach($csv_data as &$row)
		{
			$row = explode(":;:", $row);
		}

		return $csv_data;
	}

	/**
	 * Formats a line (passed as a fields  array) as CSV and returns the CSV as a string.
	 * Adapted from http://us3.php.net/manual/en/function.fputcsv.php#87120
	 */
	public function array_to_csv(array &$fields, $delimiter = ',', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false)
	{
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');

		$output = array();
		foreach($fields as $field)
		{
			if($field === null && $nullToMysqlNull)
			{
				$output[] = 'NULL';
				continue;
			}

			// Enclose fields containing $delimiter, $enclosure or whitespace
			if($encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field))
			{
				$output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
			}
			else
			{
				$output[] = $field;
			}
		}

		return implode($delimiter, $output);
	}

}

/* End of file csv.php */
/* Location: ./application/libraries/csv.php */
