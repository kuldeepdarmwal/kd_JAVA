<?php
class Html2canvas_Proxy extends CI_Controller
{
    function __construct()
    {
		parent::__construct();

		$this->load->helper('url');
    }

	public function proxy()
	{
		$url = $this->input->get('url');
		$callback = $this->input->get('callback');
		if(!empty($url) AND !empty($callback))
		{
			header('Access-Control-Max-Age:' . 5 * 60 * 1000);
			header("Access-Control-Allow-Origin: *");
			header('Access-Control-Request-Method: *');
			header('Access-Control-Allow-Methods: OPTIONS, GET');
			header('Access-Control-Allow-Headers *');
			header("Content-Type: application/javascript");

			// Retrieve file details
			$file_details = $this->get_url_details($url, 1, $callback);

			if (!in_array($file_details["mime_type"], array("image/jpg", "image/png")))
			{
				print "Error: Incorrect Mime Type";
			} 
			else
			{
				$re_encoded_image = sprintf(
					'data:%s;base64,%s', $file_details["mime_type"], base64_encode($file_details["data"])
					);

				print "{$callback}(" . json_encode($re_encoded_image) . ")";
			}
		}
		else
		{
			print "Error: Input Error";
		}
	}
	private function get_url_details($url, $attempt = 1, $callback = "")
	{
		$pathinfo = pathinfo($url);

		$max_attempts = 10;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		//curl_setopt($ch, CURLOPT_PROXY, 'username:password@host:port');
		$data = curl_exec($ch);
		$error = curl_error($ch);

		$mime_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		if(($mime_type != "image/png" AND $mime_type != "image/jpg") AND $max_attempts > $attempt)
		{
			$attempt += 1;
			return $this->get_url_details($url, $attempt, $callback);
		}
		return array(
			"pathinfo" => $pathinfo,
			"error" => $error,
			"data" => $data,
			"mime_type" => $mime_type
			);
	}
}
?>