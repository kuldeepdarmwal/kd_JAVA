<?php

class Ring extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->helper('form');
		$this->load->library('session');
		$this->load->library('tank_auth');
		$this->load->model('ring_model'); 
	}

	public function hchk(){
		$this->load->library('table');
		$data['encoded_data'] = $this->ring_model->hchk();
		$this->load->view('ring_sample/hchk', $data);
	}

	public function highchart($str)
	{
		$data['encoded_data'] = $this->ring_model->highchart(urldecode($str));
		$this->load->view('ring_sample/highchart',$data);
	}

	public function top5($str)
	{
		$data['encoded_data'] = $this->ring_model->top5(urldecode($str));
		$this->load->view('ring_sample/top5',$data);
	}

	public function top5cities($str)
	{
		$data['encoded_data'] = $this->ring_model->top5cities(urldecode($str));
		$this->load->view('ring_sample/top5cities',$data);
	}

	public function details($str)
	{
		$this->load->library('table');
		$query_result=$data['encoded_data'] = $this->ring_model->details(urldecode($str));
		$this->table->set_heading('Ad Group', 'kImpressions','Clicks', 'CTR[%]','Cost','CPM','CTC');
		$tmpl = array(
			'table_open' => '<table border="1" cellpadding="4" cellspacing="0" style="width:98%; margin-left: 10px;">',
			'heading_row_start' => '<tr>',
			'heading_row_end' => '</tr>',
			'heading_cell_start' => '<th bgcolor="#666" style="color:#fff;border:1px solid #00f;">',
			'heading_cell_end' => '</th>',

			'row_start' => '<tr>',
			'row_end' => '</tr>',
			'cell_start' => '<td style="border:1px solid #00f;">',
			'cell_end' => '</td>',

			'row_alt_start' => '<tr>',
			'row_alt_end' => '</tr>',
			'cell_alt_start' => '<td style="border:1px solid #00f;">',
			'cell_alt_end' => '</td>',

			'table_close' => '</table>'
		);
		$this->table->set_template($tmpl);
		$data['siterank_results'] = $this->table->generate($query_result);
		$data['get_num_rows'] = $data['encoded_data']->num_rows;
		$this->load->view('ring_sample/details', $data);
	}

}
?>
