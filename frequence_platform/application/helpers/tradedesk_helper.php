<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');



define('BUFFER',1.2);
define('RTG_CPM',2.9);
define('RTG_CPM_MAX',5);
define('MC_CPM',1);
define('MC_CPM_MAX',2);
define('MC_WEIGHT',1);
define('RTG_WEIGHT',0.12);
	

function get_todays_date()
{
	$t = time();
	$x = $t+date("Z", $t);
	return strftime("%Y-%m-%dT00:00:00.0000+00:00", $x);
}

function format_end_date($end_date)
{
	return strftime("%Y-%m-%dT23:59:00.0000+00:00", strtotime($end_date));
}

function format_start_date($start_date)
{
	return strftime("%Y-%m-%dT00:00:00.0000+00:00", strtotime($start_date));
}

function get_ttd_campain_lifetime_budgets($target_k_impressions, $start_date, $end_date)
{
	//TTD CAMPAIGN BUDGET = implied_blended_impression_weight * implied_blended_cpm_max * target_k_impressions * (1+buffer) * lifetime_cycles
	$implied_blended_impression_weight = MC_WEIGHT + RTG_WEIGHT;
	$implied_blended_cpm_max = (MC_CPM_MAX*MC_WEIGHT + RTG_CPM_MAX*RTG_WEIGHT)/$implied_blended_impression_weight;
	$ttd_campaign_budget = $implied_blended_impression_weight * $implied_blended_cpm_max * $target_k_impressions * BUFFER;
	return array('ttd_cmpn_dlr_budget'=>$ttd_campaign_budget, 
				'ttd_cmpn_impr_budget'=>$target_k_impressions*1000*BUFFER*(MC_WEIGHT+RTG_WEIGHT));
}

function get_num_broadcast_months($start, $end) //returns the number of full broadcast months between two dates
{
	//I added a timezone parameter here because it was defaulting to the wrong timezone which was messing up calcs in the get_broadcast_month function
	$timezone = new DateTimeZone('UTC');
	$start_date = new DateTime($start,$timezone);
	$end_date = new DateTime($end,$timezone);
	$first_month = new DateTime(get_broadcast_month($start_date) . '-01');
	$second_month = new DateTime(get_broadcast_month($end_date) . '-01');
	date_add($second_month, date_interval_create_from_date_string('1 month'));
	$diff = date_diff($first_month, $second_month);
	return (($diff->y * 12) + $diff->m);
}

function get_timeseries_start_dates($type, $start, $end) //returns the number of full broadcast months between two dates
{
	$start_date = new DateTime($start, new DateTimeZone('UTC'));
	$start_date_copy = clone $start_date;
	$start_copy_time = strtotime($start);
	$end_date = new DateTime($end, new DateTimeZone('UTC')); 
	
	$return_calendar_array=array();
	$return_calendar_array[]=date("m/d/Y", $start_copy_time);
	
	if ($type == "BROADCAST_MONTHLY" || $type == "MONTH_END"  || $type == "MONTHLY" )
	{
		for($ctr=0; $ctr < 1000; $ctr++)
		{
			$next_timeseries_date=null;
			if ($type == "BROADCAST_MONTHLY")
			{
				$start_date_copy = new DateTime($start_date_copy->format('Y-m-15'), new DateTimeZone('UTC'));
				date_add($start_date_copy, date_interval_create_from_date_string('1 month'));
				$next_timeseries_date = date("m/d/Y", (get_broadcast_start_date($start_date_copy->format('n'),  $start_date_copy->format('Y'))->getTimestamp()));
				if (new DateTime($start) >= new DateTime($next_timeseries_date))
					continue;
			} 
			else if ($type == "MONTH_END")
			{
				$start_copy_time = strtotime("+1 month",$start_copy_time);
				$next_timeseries_date = date("m/01/Y", $start_copy_time);
			}
			else if ($type == "MONTHLY")
			{
				$start_copy_time = strtotime("+1 month",$start_copy_time);
				$next_timeseries_date = date("m/d/Y", $start_copy_time);
			}

			if ($next_timeseries_date != null)
			{
				if ($end_date <= new DateTime($next_timeseries_date))
				{
					break;
				}
				$return_calendar_array[]=$next_timeseries_date;
			}
		}
	} 
	
	$return_calendar_array[]=date("m/d/Y", strtotime("+1 day",strtotime($end)));
		return $return_calendar_array;
}

function get_broadcast_month($date)
{
	$month_start = get_broadcast_start_date($date->format('n'), $date->format('Y'));
	$date_copy = clone $date;
	$num_days_to_remove = $date->format('j');
	
	date_add($date_copy, date_interval_create_from_date_string('-' . ($num_days_to_remove - 1) . ' days'));
	date_add($date_copy, date_interval_create_from_date_string("1 month"));
	
	$next_month = get_broadcast_start_date(
		$date_copy->format('n'), 
		$date_copy->format('Y')
	);
	
	if ((date_diff($next_month, $date)->format("%r%a") >= 0) == TRUE)
	{
		return ($date_copy->format('Y') . '-' . $date_copy->format('n'));
	}
	return ($date->format('Y') . '-' . $date->format('n'));
}

function get_broadcast_start_date($month, $year)
{
	$date_string = $year . '-' . $month . '-01';
	$date = new DateTime($date_string, new DateTimeZone('UTC'));
	if ($date->format('w') != 1)
	{
		$new_date = strtotime('last monday', $date->getTimestamp());
		$date->setTimestamp($new_date);
	}
	return $date;
}

function get_broadcast_end_date($month, $year)
{
	$date_string = $year . '-' . $month . '-01';
	$date = new DateTime($date_string, new DateTimeZone('UTC'));
	date_add($date, date_interval_create_from_date_string("1 month"));
	$new_date = get_broadcast_start_date($date->format('n'), $date->format('Y'));
	date_add($new_date, date_interval_create_from_date_string('-1 day'));
	return $new_date;
}

function parse_response_error_email($response, $url, $params)
{
	$CI =& get_instance();
	$CI->load->library('tank_auth');
	$CI->load->library('cli_data_processor_common');
	$from = 'tech@frequence.com';
	$to = 'tech.monitors@frequence.com';		
	if(!empty($response->ErrorDetails))
	{
	    $error_message = $response->Message;
	    $error_message .= " : ".$response->ErrorDetails[0]->Reasons[0];	    
	}
	else 
	{
	    $error_message = $response->Message;
	    if($response->Message == 'An internal service error has occured')
	    {
		$error_message = 'Request failed validation';
	    }
	}
	if($CI->input->is_cli_request())
	{
		$username = "COMMAND LINE";
		$role = "COMMAND LINE";
		$page = "COMMAND LINE";
	}
	else
	{
		$username = $CI->tank_auth->get_username();
		$role = strtolower($CI->tank_auth->get_role($username));
		$CI->load->helper('url');
		$page = current_url();

	}

	$subject = "Error: TTD issue in {$url}";
	$basic_info = $CI->cli_data_processor_common->get_environment_message();
	$message =  $basic_info . "<br/><br/> User: " . $username . ". <br/> Role: ". $role . "<br/> Page: ".$page." <br/><br/>=========================<br/> ";	
	$message .= "<strong>TTD Error details for failed campaign setup </strong><br/><br/><br/> API URL : ".$url."<br/><br/> ";
	$message .="Error Message : ".$error_message."<br/><br/> Input Parameters : <pre> ". $params."</pre>" ;
	$result = mailgun(
		$from,
		$to,
		$subject,		
		$message,
		"html"
	);	
}
