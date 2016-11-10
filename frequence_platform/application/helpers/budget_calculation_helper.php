<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Budget Calculation
function get_timeseries_start_dates_new($type, $start, $end) //returns the number of full broadcast months between two dates
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
				$next_timeseries_date = date("m/d/Y", (get_broadcast_start_date_new($start_date_copy->format('n'),  $start_date_copy->format('Y'))->getTimestamp()));
				if (new DateTime($start) >= new DateTime($next_timeseries_date))
					continue;
			} 
			else if ($type == "MONTH_END")
			{
				$start_copy_time = strtotime(date('Y-m-15', $start_copy_time));
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
				if ($end_date < new DateTime($next_timeseries_date, new DateTimeZone('UTC')))
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

function get_broadcast_month_new($date)
{
	$month_start = get_broadcast_start_date_new($date->format('n'), $date->format('Y'));
	$date_copy = clone $date;
	$num_days_to_remove = $date->format('j');
	
	date_add($date_copy, date_interval_create_from_date_string('-' . ($num_days_to_remove - 1) . ' days'));
	date_add($date_copy, date_interval_create_from_date_string("1 month"));
	
	$next_month = get_broadcast_start_date_new(
		$date_copy->format('n'), 
		$date_copy->format('Y')
	);
	
	if ((date_diff($next_month, $date)->format("%r%a") >= 0) == TRUE)
	{
		return ($date_copy->format('Y') . '-' . $date_copy->format('n'));
	}
	return ($date->format('Y') . '-' . $date->format('n'));
}

function get_broadcast_start_date_new($month, $year)
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

function get_broadcast_end_date_new($month, $year)
{
	$date_string = $year . '-' . $month . '-01';
	$date = new DateTime($date_string, new DateTimeZone('UTC'));
	date_add($date, date_interval_create_from_date_string("1 month"));
	$new_date = get_broadcast_start_date_new($date->format('n'), $date->format('Y'));
	date_add($new_date, date_interval_create_from_date_string('-1 day'));
	return $new_date;
}

function get_num_broadcast_months_new($start, $end) //returns the number of full broadcast months between two dates
{
	$start_date = new DateTime($start);
	$end_date = new DateTime($end);
	$first_month = new DateTime(get_broadcast_month_new($start_date) . '-01');
	$second_month = new DateTime(get_broadcast_month_new($end_date) . '-01');
	date_add($second_month, date_interval_create_from_date_string('1 month'));
	$diff = date_diff($first_month, $second_month);
	return (($diff->y * 12) + $diff->m);
}

function get_days_difference($start, $end)
{
	$days = '';
	$date1 = new DateTime($start);
	$date2 = new DateTime($end);

	$days = $date2->diff($date1)->format("%a");
	return $days + 1;
}

function get_month_difference($start, $end, $is_month_end = false)
{
	$month_diff = '';
	
	$start_date = strtotime($start);
	$end_date = strtotime($end);

	$start_date_year = date('Y', $start_date);
	$end_date_year = date('Y', $end_date);

	$start_date_month = date('m', $start_date);
	$end_date_month = date('m', $end_date);
	$start_date_day = date('d', $start_date);
	$end_date_day = date('d', $end_date);
	if($is_month_end)
	{
		$under_cut_modifier = 1;
	}
	else
	{
		$under_cut_modifier = ($end_date_day <= $start_date_day ? 0 : 1);
	}
	$month_diff = (($end_date_year - $start_date_year) * 12) + ($end_date_month - $start_date_month) + $under_cut_modifier;
	return $month_diff;
}

function get_flight_calculation($start_date, $end_date, $initial_flight_type, $pacing_type, $initial_budget)
{
	$return_calendar_array=get_timeseries_start_dates_new($initial_flight_type, $start_date, $end_date);
	$num_diff = get_difference($initial_flight_type, $pacing_type, $start_date, $end_date);
	$calendar_array_size = count($return_calendar_array);
	$final_array = array();
	$total_diff = $num_diff;
	$total_budget = $initial_budget;
	$monthly_budget = $initial_budget / $num_diff;
	$monthly_budget = round($monthly_budget, 2);
	$per_day_budget = round($monthly_budget, 2);
		
		
	switch($initial_flight_type)
	{
	    case 'FIXED':
		    for ($i=0; $i <= $calendar_array_size-1; $i++)
		    {
			
			$final_array[$i]['start_date'] = $return_calendar_array[$i];
			if($i != ($calendar_array_size-1))
			{
				$final_array[$i]['budget'] = round($initial_budget, 2);
			}
			else
			{
				$final_array[$i]['budget'] = 0;
			}

		    }

		    break;

	    case 'MONTHLY':
		    $budget_value = 0;
		    if ($pacing_type == 'MONTHLY')
		    {
			    for ($i=0; $i <= $calendar_array_size-1; $i++)
			    {
				    $final_array[$i]['start_date'] = $return_calendar_array[$i];
				    if($i == ($calendar_array_size-2))
				    {
					    $final_array[$i]['budget'] = round($initial_budget - $budget_value, 2);
				    }
				    elseif($i == ($calendar_array_size-1))
				    {
					    $final_array[$i]['budget'] = 0;
				    }
				    else
				    {
					    $budget_value =$budget_value + $monthly_budget;
					    $final_array[$i]['budget'] = round($monthly_budget, 2);
				    }
			    }
		    }
		    else	
		    {
			    $ndays = '';
			    $budget_value = 0;

			    for ($i=0; $i <= $calendar_array_size - 1; $i++)
			    {
				    $end_date = '';
				    $final_array[$i]['start_date'] = $return_calendar_array[$i];
				    if (array_key_exists($i+1,$return_calendar_array))
				    {
					    $end_date = date('m/d/Y', strtotime("-1 days", strtotime($return_calendar_array[$i+1])));
					    $ndays = get_days_difference($return_calendar_array[$i], $end_date);
				    }	

				    if($i == ($calendar_array_size-2))
				    {
					    $final_array[$i]['budget'] = round($total_budget - $budget_value, 2);
				    }
				    else
				    {
					    $monthly_budget = $total_budget*($ndays / $num_diff);
					    $final_array[$i]['budget'] = round($monthly_budget, 2);
					    $budget_value = $budget_value+round($monthly_budget, 2);
				    }

				    if($i == ($calendar_array_size-1))
				    {
					    $final_array[$i]['budget'] = 0;
				    }

			    }

		    }
		    break;

		case 'MONTH_END':
		    $budget_value = 0;
		    if ($pacing_type == 'MONTHLY')
		    {
			    for ($i=0; $i <= $calendar_array_size-1; $i++)
			    {
				    $final_array[$i]['start_date'] = $return_calendar_array[$i];
				    if($i == ($calendar_array_size-2))
				    {
					    $final_array[$i]['budget'] = round($initial_budget - $budget_value, 2);
				    }
				    elseif($i == ($calendar_array_size-1))
				    {
					    $final_array[$i]['budget'] = 0;
				    }
				    else
				    {
					    $budget_value = $budget_value + $monthly_budget;
					    $final_array[$i]['budget'] = round($monthly_budget, 2);
				    }
			    }
		    }
		    else	
		    {
			    $ndays = '';
			    $budget_value = 0;

			    for ($i=0; $i <= $calendar_array_size - 1; $i++)
			    {
				    $end_date = '';
				    $final_array[$i]['start_date'] = $return_calendar_array[$i];
				    if (array_key_exists($i+1,$return_calendar_array))
				    {
					    $end_date = date('m/d/Y', strtotime("-1 days", strtotime($return_calendar_array[$i+1])));
					    $ndays = get_days_difference($return_calendar_array[$i], $end_date);
				    }	

				    if($i == ($calendar_array_size-2))
				    {
					    $final_array[$i]['budget'] = round($total_budget - $budget_value, 2);
				    }
				    else
				    {
					    $monthly_budget = $total_budget*($ndays / $num_diff);
					    $final_array[$i]['budget'] = round($monthly_budget, 2);
					    $budget_value = $budget_value+round($monthly_budget, 2);
				    }

				    if($i == ($calendar_array_size-1))
				    {
					    $final_array[$i]['budget'] = 0;
				    }

			    }

		    }
		    break;		
	    case 'BROADCAST_MONTHLY':
		    $budget_value = 0;
		    if ($pacing_type == 'MONTHLY')
		    {
			    for ($i=0; $i <= $calendar_array_size-1; $i++)
			    {
				    $final_array[$i]['start_date'] = $return_calendar_array[$i];
				    if($i == ($calendar_array_size-2))
				    {
					    $final_array[$i]['budget'] = $initial_budget - $budget_value;
				    }
				    elseif($i == ($calendar_array_size-1))
				    {
					    $final_array[$i]['budget'] = 0;
				    }
				    else
				    {
					    $budget_value =$budget_value + $monthly_budget;
					    $final_array[$i]['budget'] = $monthly_budget;
				    }
			    }
		    }	
		    else	
		    {
			    $ndays = '';
			    $budget_value = 0;
			    
			    for ($i=0; $i <= $calendar_array_size - 1; $i++)
			    {
				    $end_date = '';
				    $final_array[$i]['start_date'] = $return_calendar_array[$i];
				    if (array_key_exists($i+1,$return_calendar_array))
				    {
					    $end_date = date('m/d/Y', strtotime("-1 days", strtotime($return_calendar_array[$i+1])));
					    $ndays = get_days_difference($return_calendar_array[$i], $end_date);


				    }	

				    if($i == ($calendar_array_size-2))
				    {
					    $final_array[$i]['budget'] = $total_budget - $budget_value;


				    }
				    else
				    {
					    $monthly_budget = $total_budget*($ndays / $num_diff);
					    $final_array[$i]['budget'] = round($monthly_budget, 2);
					    $budget_value = $budget_value + round($monthly_budget, 2);
				    }

				    if($i == ($calendar_array_size-1))
				    {
					    $final_array[$i]['budget'] = 0;
				    }

			    }
		    }
		    break;

	    default:
		    break;
	}
		
	if($final_array)
	{
	    return $final_array;
	}
	return false;
}

function get_difference($flight_type, $pacing_type, $start_date, $end_date)
{
	$num_diff = '';
	if($pacing_type == 'MONTHLY')
	{
		if($flight_type == 'MONTHLY')
		{
			$num_diff = get_month_difference($start_date, $end_date);
		}
		elseif($flight_type == "MONTH_END")
		{
			$num_diff = get_month_difference($start_date, $end_date, true);
		}
		else
		{
			$num_diff = get_num_broadcast_months_new($start_date, $end_date);
		}
	}
	else
	{
		$num_diff = get_days_difference($start_date, $end_date);
	}
	return $num_diff;
}

function get_timeseries_array($list_of_start_dates, $initial_target_impressions)
{
	$initial_target_impressions = 100;
	$time_series_data_array= array();

	for ($i=0; $i < $list_of_start_dates.sizeof()-1; $i++)
	{
		$time_series_data_array[$i]= array();
		$time_series_data_array[$i]['start_date']=$list_of_start_dates[$i];
		$time_series_data_array[$i]['impressions']=$initial_target_impressions;
	}

	// after loop, hardcode the last date to 0. this is the array that will be persisted to database
	$time_series_data_array[$list_of_start_dates.sizeof()-1]=array();
	$time_series_data_array[$list_of_start_dates.sizeof()-1]['start_date']=$list_of_start_dates[$list_of_start_dates.length-1];	
	$time_series_data_array[$list_of_start_dates.sizeof()-1]['impressions']=0;
	return $time_series_data_array;
}

// Method 1 Calculation
function get_budget_calculation
(
        $total_flight_dollars,
        $audience_ext_cpm,
        $geofencing_cpm = NULL,
        $o_o_cpm = NULL,
        $o_o_impressions = NULL,
        $max_geofencing_dollar_pct = NULL,
        $max_o_o_impression_pct = NULL,
        $geofencing_inventory = NULL,
        $geofencing_impressions = null
)
{
	if($geofencing_inventory && $geofencing_cpm)
	{
        	$data['geofencing_budget'] = calculate_geofencing_budget($geofencing_inventory, $geofencing_cpm, $max_geofencing_dollar_pct, $total_flight_dollars, $geofencing_impressions);
        	$data['geofencing_impressions'] = calculate_geofencing_impressions($data['geofencing_budget'], $geofencing_cpm);
	}
	else
	{
        	$data['geofencing_budget'] = 0;
        	$data['geofencing_impressions'] = 0;		
	}
	
	if($o_o_impressions && $o_o_cpm)
	{
		$data['o_o_budget'] = calculate_o_o_budget($o_o_impressions, $o_o_cpm);
	}
        else
        {
        	$data['o_o_budget'] = 0;
        }
        $data['audience_ext_budget'] = calculate_audience_ext_budget($total_flight_dollars, $data['geofencing_budget'], $data['o_o_budget']);
        $data['audience_ext_impressions'] = calculate_audience_ext_impressions($data['audience_ext_budget'], $audience_ext_cpm);
        
        return $data;
}

function calculate_geofencing_budget($geofencing_inventory, $geofencing_cpm, $max_geofencing_dollar_pct, $total_flight_dollars, $geofencing_impressions)
{       
        if($geofencing_impressions)
        {
            $geofencing_budget = round(($geofencing_impressions * $geofencing_cpm / 1000), 2);
        }
        else
        {
         	$budget[] = $geofencing_inventory * $geofencing_cpm / 1000;
        	if($max_geofencing_dollar_pct !== null)
        	{
        		$budget[] = round(($max_geofencing_dollar_pct * $total_flight_dollars), 2);	
        	}
        	$geofencing_budget = min($budget);
        }
        
        return $geofencing_budget;
}

function calculate_geofencing_impressions($geofencing_budget, $geofencing_cpm)
{
     $geofencing_impressions = round(($geofencing_budget/$geofencing_cpm) * 1000);
     return $geofencing_impressions;
}

function calculate_o_o_budget($o_o_impressions, $o_o_cpm)
{
      $o_o_budget = round(($o_o_impressions * $o_o_cpm / 1000), 2);
      return $o_o_budget;
}

function calculate_audience_ext_budget($total_flight_dollars, $geofencing_budget, $o_o_budget)
{
        $audience_ext_budget = round(($total_flight_dollars - $geofencing_budget - $o_o_budget), 2);
        return $audience_ext_budget;
}

function calculate_audience_ext_impressions($audience_ext_budget, $audience_ext_cpm)
{
        $audience_ext_impressions = round(1000 * $audience_ext_budget / $audience_ext_cpm);
        return $audience_ext_impressions;
}

?>
