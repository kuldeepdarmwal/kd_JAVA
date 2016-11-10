<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Demos extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('url');
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->library('session');
		$this->load->model('users');
	}
	
	function index()
	{
	if(array_key_exists("demo_submit", $_POST))
	{
		$this->
	}
		$this->load->view('vlocal-T1/demo_insert');
	}
	
	
}
?>