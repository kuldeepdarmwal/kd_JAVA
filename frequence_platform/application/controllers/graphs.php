<?php
//Calls the graph functions to be displayed within an iframe in the web report view.  Each function requires session data to generate data.
//In each function is come commented code that is hardcoded data for testing each graph individually.  
//You will need to overwrite the businessName, campaignName, startDate, and endDate elements of the data array to get a working graph.
class Graphs extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->model('graphs_model');
		$this->load->helper('url');
	}

	public function graph_101()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_101');
		//$data['chartHeight'] = 	$this->session->userdata('height_101');

		// Test code
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
		*/
		
		//loading data for graph 101

		$data['impressionsAndClicksColumnsResponse'] = 	$this->graphs_model->get_impressions_clicks_column_101($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['impressionsAndClicksTotalsResponse'] = 	$this->graphs_model->get_impressions_clicks_total_101($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		$this->load->view('graphs/101', $data);
		
	}
	public function graph_102()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_102');
		//$data['chartHeight'] = 	$this->session->userdata('height_102');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
		*/
		
	 	$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = $actualDateRange * 2;
		
		//$data['chartWidth'] = 	$this->session->userdata('width_102');
		//data['chartHeight'] = 	$this->session->userdata('height_102');

		//loading data for graph 102
		$data['graphRowsResponse'] = $this->graphs_model->graph_rows_query_102($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		$this->load->view('graphs/102', $data);
		
	}
	public function graph_103()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_103');
		//$data['chartHeight'] = 	$this->session->userdata('height_103');
		
		/*
		$data['businessName'] = 'Half Moon Bay'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
		*/
		
	 	$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = $actualDateRange * 2;
		
		//loading data for graph 103
		
		$data['graphRowsResponse'] = $this->graphs_model->graph_rows_query_103($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/103', $data);
	}
	public function graph_104()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_104');
		//$data['chartHeight'] = 	$this->session->userdata('height_104');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = $actualDateRange * 2;
		*/
		
	 	$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = 20;
		//loading data for graph 104
		
		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_104_105($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_graph_query_104_105($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_graph_query_104_105($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/104', $data);
		
	}
	public function graph_105()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_105');
		//$data['chartHeight'] = 	$this->session->userdata('height_105');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = 20;
		
		//loading data for graph 105
		
		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_104_105($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_graph_query_104_105($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_graph_query_104_105($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/105', $data);
	}
	public function graph_106()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_106');
		//$data['chartHeight'] = 	$this->session->userdata('height_106');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = 12;
		
		//loading data for graph 106
		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_106($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_graph_query_106($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_graph_query_106($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/106', $data);
	}
	public function graph_107()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_107');
		//$data['chartHeight'] = 	$this->session->userdata('height_107');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = 15;
		//loading data for graph 107
		
		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_graph_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_graph_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/107', $data);
	}
        
        
        public function city_data(){
            $this->load->library('table');
            $this->load->dbutil();
            $data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_107');
		//$data['chartHeight'] = 	$this->session->userdata('height_107');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = 1000000;
		//loading data for graph 107
		
                
                $data['region_data']= $this->graphs_model->all_region_data($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
                
                
//		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
//		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_graph_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
//		$data['totalGraphResponse'] = 			$this->graphs_model->total_graph_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
//		
//		if ($data['campaignName'] == '.*')
//		{
//			$data['campaignName'] = 'All Campaigns';
//		}
		
                $delimiter = ",";
                $newline = "<br>";
                $data['all_data_table'] = $this->table->generate($data['region_data']);
                $data['all_data_csv'] = $this->dbutil->csv_from_result($data['region_data'], $delimiter, $newline);
		$this->load->view('graphs/107_csv', $data);
            
        }
        
	public function graph_108()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_108');
		//$data['chartHeight'] = 	$this->session->userdata('height_108');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = 15;
		
		//loading data for graph 108
		
		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_graph_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName'], $data['rankLimit']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_graph_query_107_108($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/108', $data);
	}
	public function graph_109()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_109');
		//$data['chartHeight'] = 	$this->session->userdata('height_109');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = $actualDateRange * 2;
		
		//loading data for graph 109
		
		/*
		$data['total_impressions'] = 0;
		$response_101 = $this->graphs_model->get_impressions_clicks_column_101($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		foreach($response_101->result() as $row)
		{
			$data['total_impressions'] += $row->TotalImpressions;
		}*/
		
		$data['graphResponse'] = 				$this->graphs_model->graph_rows_query_109($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/109', $data);
	}
	public function graph_110()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_110');
		//$data['chartHeight'] = 	$this->session->userdata('height_110');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
	 	*/
		
		$diff_secs = abs(strtotime($data['endDate']) - strtotime($data['startDate']));
		$days = floor($diff_secs / (3600 * 24));
		$actualDateRange = $days + 1;
		$data['rankLimit'] = $actualDateRange * 2;
		
		//loading data for graph 110
		
		/*
		$data['total_impressions'] = 0;
		$response_101 = $this->graphs_model->get_impressions_clicks_column_101($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		foreach($response_101->result() as $row)
		{
			$data['total_impressions'] += $row->TotalImpressions;
		}*/
		
		$data['graphResponse'] = $this->graphs_model->graph_rows_query_110($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/110', $data);
	}
	public function graph_111()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		//$data['chartWidth'] = 	$this->session->userdata('width_111');
		//$data['chartHeight'] = 	$this->session->userdata('height_111');
		
		/*
		$data['businessName'] = 'Western Athletic Clubs'; // Passed as a parameter to this view.
		$data['campaignName'] = '.*'; // Passed as a parameter to this view.
		$data['startDate'] = date("Y-m-d", strtotime("-1 month")); // Passed as a parameter to this view.
		$data['endDate'] = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
		*/
		
		//loading data for graph 111
		
		/*
		$data['total_impressions'] = 0;
		$response_101 = $this->graphs_model->get_impressions_clicks_column_101($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		foreach($response_101->result() as $row)
		{
			$data['total_impressions'] += $row->TotalImpressions;
		}
		*/
		
		$data['graphResponse'] = $this->graphs_model->graph_rows_query_111($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/111', $data);
	}
	public function graph_112()
	{
		$data['businessName'] = $this->session->userdata('businessName');
		$data['campaignName'] = $this->session->userdata('campaignName');
		$data['startDate'] = 	$this->session->userdata('startDate');
		$data['endDate'] = 		$this->session->userdata('endDate');
		
		/*
		$data['total_impressions'] = 0;
		$response_101 = $this->graphs_model->get_impressions_clicks_column_101($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		foreach($response_101->result() as $row)
		{
			$data['total_impressions'] += $row->TotalImpressions;
		}*/
		
		
		$data['graphResponse'] = $this->graphs_model->graph_rows_query_112($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['partialTotalGraphResponse'] = 	$this->graphs_model->partial_total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		$data['totalGraphResponse'] = 			$this->graphs_model->total_demos_query($data['businessName'], $data['startDate'], $data['endDate'], $data['campaignName']);
		
		if ($data['campaignName'] == '.*')
		{
			$data['campaignName'] = 'All Campaigns';
		}
		
		$this->load->view('graphs/112', $data);
	}
	public function error()
	{
		
	}
}
?>
