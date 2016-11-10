<?php defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH.'/vendor/autoload.php';

class Test_budget extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('budget_calculation');
	}
	
	public function index()
	{       
		$final_result = '';
		
		// Set Data
		$start_date = '08/31/2016'; //08/01/2016 // m/d/Y
		$end_date = '04/25/2017'; // m/d/Y
		$initial_flight_type = 'MONTH_END'; // FIXED, MONTHLY, BROADCAST_MONTHLY
		$pacing_type = 'MONTHLY'; // DAYS & MONTHLY
		$initial_budget = 100;
		echo $start_date.'<br/>';
		echo $end_date.'<br/>';
		$final_result = get_flight_calculation($start_date, $end_date, $initial_flight_type, $pacing_type, $initial_budget);
		echo json_encode($final_result);
		
		// Method 1
		
		$final_budget_result = '';
		
		// Set Data
		$total_flight_dollars = 1000;
                $audience_ext_cpm = 15;
                $geofencing_cpm = 20;
                $o_o_cpm = 15;
                $o_o_impressions = 1000;
                $max_geofencing_dollar_pct = 15;
                $max_o_o_impression_pct = 25;
                $geofencing_inventory = 8000;
		
		$final_budget_result = get_budget_calculation($total_flight_dollars, $audience_ext_cpm, $geofencing_cpm, $o_o_cpm, $o_o_impressions, $max_geofencing_dollar_pct, $max_o_o_impression_pct, $geofencing_inventory);
		echo '<pre>';
                print_r($final_result);
        }

        

}