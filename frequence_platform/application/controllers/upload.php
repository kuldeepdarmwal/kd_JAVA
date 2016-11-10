<?php

class Upload extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));
	}

	function index()
	{
		$this->load->view('upload_form', array('error' => ' ' ));
	}

	function do_upload()
	{
		$config['upload_path'] = './dfa_creatives/';
		$config['allowed_types'] = 'gif|jpg|png|swf';
		$config['max_size']	= '0';
		$config['max_width']  = '0';
		$config['max_height']  = '0';

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload()){
			$error = array('error' => $this->upload->display_errors());
			echo 'FAIL<br>';
                        print '<pre>'; print_r($error); print '</pre>';
                        //$this->load->view('upload_form', $error);
		}
		else{
			$data = array('upload_data' => $this->upload->data());
                        print '<pre>'; print_r($data); print '</pre>';
			//$this->load->view('upload_success', $data);
		}
	}
}
?>