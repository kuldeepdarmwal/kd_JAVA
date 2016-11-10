<?php
class Campaign_health extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url'));
		$this->load->helper('vl_ajax');
		$this->load->helper('tradedesk');
		$this->load->helper('tag_helper');
		$this->load->library('form_validation');
		$this->load->library('tank_auth');
		$this->load->library('vl_platform');
		$this->load->model('campaign_health_model');
		$this->load->model('tradedesk_model');
	}
  
	private function role_check(){
		if (!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('referer', 'campaign_health');
			redirect('login');
		}
		$vl_username = $this->tank_auth->get_username();
		$vl_role = $this->tank_auth->get_role($vl_username);

            //echo 'role: '.$role.'<br>';
            // if(!($vl_role == 'admin') OR ($vl_role == 'ops') ){
            //     redirect('director');
            // }
    }
  
	public function get_campaign_results($c_id)
	{
		$report_date = urldecode($_GET['r_date']);
		$report_date_object = new DateTime($report_date, new DateTimeZone('UTC'));

		$one_month_ago_date = urldecode($_GET['m_date']);
		$result_array['c_id'] = $c_id;
		$impressions_and_dates = $this->campaign_health_model->get_lifetime_dates_impressions($c_id);
		$campaign_running = ($impressions_and_dates != NULL && $impressions_and_dates[0]['start_date'] != '' && $impressions_and_dates[0]['end_date'] != '');
		$result_array['start_date'] = $campaign_running?  $impressions_and_dates[0]['start_date'] : '-';
		//$result_array['start_date'] = '2012-03-31';//$campaign_running?  $impressions_and_dates[0]['start_date'] : '-';
		$result_array['end_date'] = $campaign_running? $impressions_and_dates[0]['end_date'] : '';
		$campaign_info = $this->campaign_health_model->get_campaign_info($c_id);
		$result_array['campaign_type'] = $campaign_info['c_type'];
		if($campaign_running && $campaign_info['c_type'] != "ERROR")
		{
			$result_array['lifetime_impressions'] = $impressions_and_dates[0]['total_impressions'];
			$result_array['lifetime_clicks'] = $impressions_and_dates[0]['total_clicks'];
			$result_array['lifetime_engagements'] = $this->campaign_health_model->get_all_engagements_for_campaign($c_id);
			$result_array['type'] = $campaign_info['c_type'];
			$result_array['hard_end_date'] = $campaign_info['c_details'][0]['hard_end_date'];
			switch ($campaign_info['c_type']) {
      			
				case 'FIXED_TERM':
					$result_array['first_reset_date'] = $campaign_info['c_details'][0]['hard_end_date'];//1st cycle end date
					$complete_cycles_since_first_reset = 0;
					//$last_reset_date = $result_array['start_date'];
					$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day'));
					$next_reset_date = $campaign_info['c_details'][0]['hard_end_date'];
					$cycle_live_days = date_diff(datetime::createfromformat('Y-m-d',$last_reset_date), datetime::createfromformat('Y-m-d',$report_date))->days;
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$next_reset_date))->days;
					$cycle_life_ratio = $cycle_live_days/($cycle_live_days+$cycle_days_remaining);
					$cycle_impressions = $result_array['lifetime_impressions'];
					$cycles_live = $cycle_life_ratio;
					$cycles_at_next_reset = 1;
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/($cycle_live_days+$cycle_days_remaining);
					break;
      
				case 'MONTH_END_RECURRING':
					$result_array['first_reset_date'] = date("Y-m-t", strtotime($result_array['start_date']));//1st cycle end date
					$next_reset_date = date("Y-m-t", strtotime($report_date. ' + 1 day'));//end of calendar month of report date

					//$time_to_next_reset = date_diff(datetime::createfromformat('Y-m-d',$result_array['first_reset_date']), datetime::createfromformat('Y-m-d',$next_reset_date));
					$time_to_next_reset = $this->date_minus($next_reset_date, $result_array['first_reset_date']);

					$complete_cycles_since_first_reset = ($time_to_next_reset['y']*12+$time_to_next_reset['m'])*($time_to_next_reset['invert']);
					//$complete_cycles_since_first_reset = $complete_cycles_since_first_reset + ((  ($result_array['end_date'] == $result_array['first_reset_date']) )? 1 : 0);
					$complete_cycles_since_first_reset = $complete_cycles_since_first_reset;


					$result_array['time_since_first_reset'] = $time_to_next_reset;


					if($complete_cycles_since_first_reset == 0){ ///we are still in the very first cycle
						$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day')); //previous reset date
					}else{
						$last_reset_date = $this->add_months($next_reset_date,-1)->format('Y-m-t');
					}

					$cycle_live_days = date_diff(datetime::createfromformat('Y-m-d',$last_reset_date), datetime::createfromformat('Y-m-d',$report_date))->days;
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$next_reset_date))->days;
					$cycle_life_ratio = $cycle_live_days / ($cycle_live_days + $cycle_days_remaining);
					$cycle_impressions_result = ($this->campaign_health_model->get_impressions_total($c_id,date('Y-m-d', strtotime($last_reset_date . ' + 1 day')),$next_reset_date));
					$cycle_impressions = $cycle_impressions_result[0]['total_impressions'];
					$cycles_live = $complete_cycles_since_first_reset+$cycle_life_ratio;
					$cycles_at_next_reset = ceil($cycles_live);
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/(28);
					break;

				case 'MONTHLY_WITH_END_DATE':
					$result_array['first_reset_date'] = date('Y-m-d', strtotime($this->add_months($result_array['start_date'],1)->format('Y-m-d') . ' - 1 day'));//1st cycle end date
					$time_since_first_reset = date_diff(datetime::createfromformat('Y-m-d',$result_array['first_reset_date']), datetime::createfromformat('Y-m-d',$report_date));
					$complete_cycles_since_first_reset = ($time_since_first_reset->y*12+$time_since_first_reset->m)*($time_since_first_reset->invert==1? -1 : 1);
					if($time_since_first_reset->invert!=1)
					{
						$last_reset_date = $this->add_months($result_array['first_reset_date'],$complete_cycles_since_first_reset)->format('Y-m-d');
						$next_reset_date = $this->add_months($result_array['first_reset_date'],$complete_cycles_since_first_reset+1)->format('Y-m-d');
						$complete_cycles_since_first_reset++;
					}else{
						$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day'));
						$next_reset_date = $result_array['first_reset_date'];
					}
					if(strtotime($result_array['hard_end_date']) < strtotime($next_reset_date))
					{
						$next_reset_date = date("Y-m-d", strtotime($result_array['hard_end_date'])); //If end date is before next reset date 
					}

					$cycle_live_days = date_diff(datetime::createfromformat('Y-m-d',$last_reset_date), datetime::createfromformat('Y-m-d',$report_date))->days;
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$next_reset_date))->days;
					$cycle_life_ratio = $cycle_live_days/($cycle_live_days+$cycle_days_remaining);
					$cycle_impressions_result = ($this->campaign_health_model->get_impressions_total($c_id,date('Y-m-d', strtotime($last_reset_date . ' + 1 day')),$next_reset_date));
					$cycle_impressions = $cycle_impressions_result[0]['total_impressions'];
					$cycles_live = $complete_cycles_since_first_reset+$cycle_life_ratio;
					$cycles_at_next_reset = ceil($cycles_live);
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/(28);
					break;

				case 'MONTH_END_WITH_END_DATE':         
					$result_array['first_reset_date'] = date("Y-m-t", strtotime($result_array['start_date']));//1st cycle end date
					$next_reset_date = date("Y-m-t", strtotime($report_date. ' + 1 day'));//end of calendar month of report date

					if(strtotime($result_array['hard_end_date']) < strtotime($next_reset_date))
					{
						$next_reset_date = date("Y-m-d", strtotime($result_array['hard_end_date'])); //If end date is before next reset date 
					}

					//$time_to_next_reset = date_diff(datetime::createfromformat('Y-m-d',$result_array['first_reset_date']), datetime::createfromformat('Y-m-d',$next_reset_date));
					$time_to_next_reset = $this->date_minus($next_reset_date,$result_array['first_reset_date']);

					$complete_cycles_since_first_reset = ($time_to_next_reset['y']*12+$time_to_next_reset['m'])*($time_to_next_reset['invert']);

					$result_array['time_since_first_reset'] = $time_to_next_reset;

					if($complete_cycles_since_first_reset == 0){ ///we are still in the very first cycle
						$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day'));
					}else{
						$last_reset_date = $this->add_months($next_reset_date,-1)->format('Y-m-t');
					}

					$cycle_live_days = date_diff(datetime::createfromformat('Y-m-d',$last_reset_date), datetime::createfromformat('Y-m-d',$report_date))->days;
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$next_reset_date))->days;
					$cycle_life_ratio = $cycle_live_days/($cycle_live_days+$cycle_days_remaining);
					$cycle_impressions_result = ($this->campaign_health_model->get_impressions_total($c_id,date('Y-m-d', strtotime($last_reset_date . ' + 1 day')),$next_reset_date));
					$cycle_impressions = $cycle_impressions_result[0]['total_impressions'];
					$cycles_live = $complete_cycles_since_first_reset + $cycle_life_ratio;
					$cycles_at_next_reset = ceil($cycles_live);
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/(28);
					break;

				case 'MONTHLY_RECURRING':
					$result_array['first_reset_date'] = date('Y-m-d', strtotime($this->add_months($result_array['start_date'],1)->format('Y-m-d') . ' - 1 day'));//1st cycle end date
					$time_since_first_reset = date_diff(datetime::createfromformat('Y-m-d',$result_array['first_reset_date']), datetime::createfromformat('Y-m-d',$report_date));
					$complete_cycles_since_first_reset = ($time_since_first_reset->y * 12 + $time_since_first_reset->m) * ($time_since_first_reset->invert==1 ? -1 : 1);
					if($time_since_first_reset->invert!=1){
						$last_reset_date = $this->add_months($result_array['first_reset_date'], $complete_cycles_since_first_reset)->format('Y-m-d');
						$next_reset_date = $this->add_months($result_array['first_reset_date'], $complete_cycles_since_first_reset + 1)->format('Y-m-d');
						$complete_cycles_since_first_reset++;
					}else{
						$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day'));
						$next_reset_date = $result_array['first_reset_date'];
					}

					$cycle_live_days = date_diff(datetime::createfromformat('Y-m-d',$last_reset_date), datetime::createfromformat('Y-m-d',$report_date))->days;
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$next_reset_date))->days;
					$cycle_life_ratio = $cycle_live_days/($cycle_live_days+$cycle_days_remaining);
					$cycle_impressions_result = ($this->campaign_health_model->get_impressions_total($c_id,date('Y-m-d', strtotime($last_reset_date . ' + 1 day')),$next_reset_date));
					$cycle_impressions = $cycle_impressions_result[0]['total_impressions'];
					$cycles_live = $complete_cycles_since_first_reset+$cycle_life_ratio;
					$cycles_at_next_reset = ceil($cycles_live);
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/(28);
					break;

				case 'BROADCAST_MONTHLY_RECURRING':
					$start_date = date("Y-m-d", strtotime($result_array['start_date']));//1st cycle end date
					$start_date = new DateTime($start_date, new DateTimeZone('UTC'));
					
					$temp_first_reset = get_broadcast_month($start_date);
					$temp_first_reset_array = explode('-', $temp_first_reset);
					$result_array['first_reset_date'] = get_broadcast_end_date($temp_first_reset_array[1], $temp_first_reset_array[0]);


					$next_reset_date = date("Y-m-d", strtotime($report_date . ' + 1 day'));//end of broadcast month of report date
					$next_reset_date = new DateTime($next_reset_date, new DateTimeZone('UTC'));
					$temp_broadcast_date = get_broadcast_month($next_reset_date);
					$temp_broadcast_date_array = explode('-', $temp_broadcast_date);
					$next_reset_date = get_broadcast_end_date($temp_broadcast_date_array[1], $temp_broadcast_date_array[0]);
					$time_to_next_reset = $this->date_minus($next_reset_date->format('Y-m-d'), $result_array['first_reset_date']->format('Y-m-d'));
					$complete_cycles_since_first_reset = get_num_broadcast_months($result_array['first_reset_date']->format('Y-m-d'), $next_reset_date->format('Y-m-d')) - 1;
					$result_array['time_since_first_reset'] = $time_to_next_reset;

					if($complete_cycles_since_first_reset == 0) ///we are still in the very first cycle
					{ 
						$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day')); //previous reset date
					}
					else
					{
						$last_reset_date = new DateTime(date('Y-m-d', strtotime($report_date.' + 1 day')), new DateTimeZone('UTC'));
						$temp_last_reset = get_broadcast_month($last_reset_date);
						$temp_last_reset_array = explode('-', $temp_last_reset);
						$last_reset_date = get_broadcast_start_date($temp_last_reset_array[1], $temp_last_reset_array[0]);
						date_add($last_reset_date, date_interval_create_from_date_string('-1 day'));
					}

					$cycle_live_days = date_diff($last_reset_date, $report_date_object)->days;
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d', $report_date), $next_reset_date)->days;
					$cycle_life_ratio = $cycle_live_days / ($cycle_live_days + $cycle_days_remaining);
					$cycle_impressions_result = $this->campaign_health_model->get_impressions_total($c_id, date_add($last_reset_date, date_interval_create_from_date_string('1 day'))->format('Y-m-d'), $next_reset_date->format('Y-m-d'));
					$cycle_impressions = $cycle_impressions_result[0]['total_impressions'];
					$cycles_live = $complete_cycles_since_first_reset + $cycle_life_ratio;
					$cycles_at_next_reset = ceil($cycles_live);
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions'] * 1000) / (28);
					$next_reset_date = $next_reset_date->format('Y-m-d');
					$last_reset_date = $last_reset_date->format('Y-m-d');
					break;

				case 'BROADCAST_MONTHLY_WITH_END_DATE':
					$start_date = date("Y-m-d", strtotime($result_array['start_date']));//1st cycle end date
					$start_date = new DateTime($start_date, new DateTimeZone('UTC'));

					$temp_first_reset = get_broadcast_month($start_date);
					$temp_first_reset_array = explode('-', $temp_first_reset);
					$result_array['first_reset_date'] = get_broadcast_end_date($temp_first_reset_array[1], $temp_first_reset_array[0]);

					$next_reset_date = date("Y-m-d", strtotime($report_date . ' + 1 day'));//end of broadcast month of report date
					$next_reset_date = new DateTime($next_reset_date, new DateTimeZone('UTC'));
					$temp_broadcast_date = get_broadcast_month($next_reset_date);
					$temp_broadcast_date_array = explode('-', $temp_broadcast_date);
					$next_reset_date = get_broadcast_end_date($temp_broadcast_date_array[1], $temp_broadcast_date_array[0]);
					
					if(strtotime($result_array['hard_end_date']) < strtotime($next_reset_date->format('Y-m-d')))
					{
						//If end date is before next reset date
						$next_reset_date = new DateTime($result_array['hard_end_date'], new DateTimeZone('UTC'));
					}

					$time_to_next_reset = $this->date_minus($next_reset_date->format('Y-m-d'), $result_array['first_reset_date']->format('Y-m-d'));
					$complete_cycles_since_first_reset = get_num_broadcast_months($result_array['first_reset_date']->format('Y-m-d'), $next_reset_date->format('Y-m-d')) - 1;
					$result_array['time_since_first_reset'] = $time_to_next_reset;

					if($complete_cycles_since_first_reset == 0) ///we are still in the very first cycle
					{ 
						$last_reset_date = date('Y-m-d', strtotime($result_array['start_date'] . ' - 1 day'));
						$last_reset_date = new DateTime($last_reset_date, new DateTimeZone('UTC'));
					}
					else
					{
						$last_reset_date = new DateTime(date('Y-m-d', strtotime($report_date.' + 1 day')), new DateTimeZone('UTC'));
						$temp_last_reset = get_broadcast_month($last_reset_date);
						$temp_last_reset_array = explode('-', $temp_last_reset);
						$last_reset_date = get_broadcast_start_date($temp_last_reset_array[1], $temp_last_reset_array[0]);
						date_add($last_reset_date, date_interval_create_from_date_string('-1 day'));
					}

					$cycle_live_days = date_diff($last_reset_date, $report_date_object)->days;
					$cycle_days_remaining = date_diff($report_date_object, $next_reset_date)->days;
					$cycle_life_ratio = $cycle_live_days / ($cycle_live_days + $cycle_days_remaining);
					$cycle_impressions_result = ($this->campaign_health_model->get_impressions_total($c_id, date('Y-m-d', strtotime($last_reset_date->format('Y-m-d') . ' + 1 day')), $next_reset_date->format('Y-m-d')));
					$cycle_impressions = $cycle_impressions_result[0]['total_impressions'];
					$cycles_live = $complete_cycles_since_first_reset + $cycle_life_ratio;
					$cycles_at_next_reset = ceil($cycles_live);
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions'] * 1000) / (28);
					$next_reset_date = $next_reset_date->format('Y-m-d');
					$last_reset_date = $last_reset_date->format('Y-m-d');
					break;

				default:
					die('Unknown campaign type: ' . $campaign_info['c_type']);
			}
			
			$result_array['complete_cycles_since_first_reset'] = $complete_cycles_since_first_reset;
			$result_array['last_reset'] = $last_reset_date;
			$result_array['next_reset'] = $next_reset_date;
			$result_array['cycle_live_days'] = $cycle_live_days;
			$result_array['cycle_days_remaining'] = $cycle_days_remaining;
			$result_array['cycle_life_ratio'] = $cycle_life_ratio;
			$result_array['cycle_impressions'] = $cycle_impressions;
			$result_array['cycles_live'] = $cycles_live;

			$result_array['lifetime_remaining_impressions'] = $cycles_at_next_reset*$campaign_info['c_details'][0]['TargetImpressions']*1000 - $result_array['lifetime_impressions'];
			$result_array['lifetime_target_to_next_bill_date'] = $cycle_days_remaining == 0? 0 : $result_array['lifetime_remaining_impressions']/$cycle_days_remaining;
			$result_array['lifetime_OTI'] = $campaign_info['c_details'][0]['TargetImpressions']*$result_array['cycles_live'] != 0? ($result_array['lifetime_impressions']/1000)/($campaign_info['c_details'][0]['TargetImpressions']*$result_array['cycles_live']) : 1;

			$result_array['cycle_remaining_impressions'] = $campaign_info['c_details'][0]['TargetImpressions']*1000 - $result_array['cycle_impressions'];
			$result_array['cycle_target_to_next_bill_date'] = $cycle_days_remaining == 0?  0 : $result_array['cycle_remaining_impressions']/$cycle_days_remaining;
			$result_array['cycle_OTI'] = $campaign_info['c_details'][0]['TargetImpressions']*$result_array['cycle_life_ratio'] != 0? ($result_array['cycle_impressions']/1000)/($campaign_info['c_details'][0]['TargetImpressions']*$result_array['cycle_life_ratio']) : 1;

			$yesterday_impressions_result = ($this->campaign_health_model->get_impressions_total($c_id,$report_date,$report_date));
			$result_array['yesterday_impressions'] = $yesterday_impressions_result[0]['total_impressions'];

			$site = $this->campaign_health_model->get_heaviest_site($c_id,$one_month_ago_date,$report_date);
			$total = $this->campaign_health_model->get_impressions_only($c_id,$one_month_ago_date,$report_date);
			$rtg = $this->campaign_health_model->get_rtg_impressions_only($c_id,$one_month_ago_date,$report_date);
			$result_array['screenshots'] = $this->campaign_health_model->has_screenshots_since_last_reset_date($c_id, $last_reset_date);
    
			if(isset($site[0]['impressions']))
			{
				$result_array['site_weight'] = ($total[0]['impressions']==0)? 0 : count($site[0]['impressions']>0) ? $site[0]['impressions']/$total[0]['impressions'] : 0;
			}
			else
			{
				$result_array['site_weight'] = 0;
			}
    
			$result_array['rtg_weight'] = ($total[0]['impressions']==0)? 0 : count($rtg[0]['impressions']>0) ? $rtg[0]['impressions']/$total[0]['impressions'] : 0;
      
		}
		else ///brand new campaign - should still calc long term target
		{
			switch ($campaign_info['c_type']) {
				case 'FIXED_TERM':
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d',$campaign_info['c_details'][0]['hard_end_date']))->days;
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/$cycle_days_remaining;
					break;
				case 'MONTH_END_RECURRING':
					$next_reset_date = date("Y-m-t", strtotime($report_date. ' + 1 day'));
					$cycle_days_remaining = date_diff(datetime::createfromformat('Y-m-d',$report_date), datetime::createfromformat('Y-m-d', $next_reset_date))->days;
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/$cycle_days_remaining;
					break;
				case 'MONTHLY_RECURRING':
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/(28);
					break;
				case 'BROADCAST_MONTHLY_RECURRING':
					$next_reset_date = new DateTime(date("Y-m-d", strtotime($report_date . ' + 1 day')), new DateTimeZone('UTC'));
					$temp_broadcast_date = get_broadcast_month($next_reset_date);
					$temp_broadcast_date_array = explode('-', $temp_broadcast_date);
					$next_reset_date = get_broadcast_end_date($temp_broadcast_date_array[1], $temp_broadcast_date_array[0]);
					$cycle_days_remaining = date_diff(new DateTime(date("Y-m-d", strtotime($report_date))), $next_reset_date)->days;
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions'] * 1000) / $cycle_days_remaining;
					break;
				case 'ERROR':
					$cycle_days_remaining = 0;
					$result_array['long_term_target'] = 0;
					break;
				default:
					$result_array['long_term_target'] = ($campaign_info['c_details'][0]['TargetImpressions']*1000)/(28);
					break;
			}
		}
  
		$landing_page = $this->campaign_health_model->get_landing_page($c_id);
		$partner_name = $this->campaign_health_model->get_sales_person_name($c_id);
		$result_array['partner_name'] = $partner_name;
		$result_array['landing_page'] = $landing_page[0]['LandingPage'];
		
		$result_array['has_tags'] = (($this->campaign_health_model->get_tags_for_advertiser_by_campaign($c_id) == FALSE) ? FALSE : TRUE);
		$result_array['has_adsets'] = (($this->campaign_health_model->get_versions_for_campaign($c_id) == FALSE) ? FALSE : TRUE);
		$result_array['has_adgroups'] = (($this->tradedesk_model->get_ttd_adgroups_by_campaign($c_id) == FALSE) ? FALSE : TRUE);
		$result_array['ttd_campaign'] = (($this->tradedesk_model->get_ttd_campaign_by_campaign($c_id) == FALSE) ? FALSE : TRUE);
		$result_array['has_m_adgroup'] = (($this->tradedesk_model->get_managed_ttd_adgroups_by_campaign($c_id) == FALSE) ? FALSE : TRUE);
		
		
		if($this->tradedesk_model->get_day_old_managed_ttd_adgroups_by_campaign($c_id) == FALSE && $this->tradedesk_model->is_campaign_day_old($c_id) == FALSE)
		{    
			$result_array['day_old_adgroup'] = FALSE;
		}
		else
		{
			$result_array['day_old_adgroup'] = TRUE;
		}

		$result_array['ttd_date_mismatch'] = $this->tradedesk_model->are_campaign_dates_matching($c_id);

		echo json_encode($result_array);
	}

private function date_minus($date2,$date1){
      $delta_year = date('Y', strtotime($date2)) - date('Y', strtotime($date1));
      //echo 'delta years: '.$delta_year.'<br>';
      $delta_month = date('m', strtotime($date2)) - date('m', strtotime($date1));
      //echo 'delta month: '.$delta_month.'<br>';
      $total_delta_month = 12*$delta_year+$delta_month;
      //echo 'total delta month: '.$total_delta_month.'<br>';
      $inverted =  $total_delta_month<0? -1:1;
      $final_delta_year = floor(abs($total_delta_month/12));
      //echo 'final delta year: '.$final_delta_year.'<br>';
      $final_delta_month = abs($total_delta_month - 12*$final_delta_year*$inverted);
      return array('invert'=>$inverted,'y'=>$final_delta_year,'m'=>$final_delta_month);
  }

public function add_months($date_str,$months){
    $date = new DateTime($date_str);
    $start_day = $date->format('j');

    $date->modify("+{$months} month");
    $end_day = $date->format('j');

    if ($start_day != $end_day)
        $date->modify('last day of last month');

    return $date;
}

 
  
  public function remove_from_healthcheck($blah = "d"){
      $this-> role_check();
      $campaign = urldecode($_GET['campaign']);
      $response = $this->campaign_health_model->update_campaign_view_status($campaign,1);//1 means ignore
      echo json_encode($response);
  }
  
   public function add_to_healthcheck($blah = "d"){
       $this-> role_check();
      $campaign = urldecode($_GET['campaign']);
      $response = $this->campaign_health_model->update_campaign_view_status($campaign,0);//0 means don't ignore
      echo json_encode($response);
  }
  
  public function graveyard(){
      $this-> role_check();
      $data['graveyard_summary'] = $this->campaign_health_model->get_graveyard_data_2();
      $this->load->view('campaign_health/graveyard2', $data);
  }
  
 
  
   public function get_campaign_details_id($blah="")
   {
//      $advertiser_id = urldecode($_GET['advertiser_id']);

      $allowed_user_types = array('sales', 'ops', 'admin');
      $required_post_variables = array('campaign_id', 'start', 'end');
      $ajax_verify = vl_verify_ajax_call($allowed_user_types, $required_post_variables); //post variables saved to ['post']['name']
      if($ajax_verify['is_success'])
      {
        $campaign_id = $ajax_verify['post']['campaign_id'];
        $start = $ajax_verify['post']['start'];
        $end = $ajax_verify['post']['end'];
        
        $campaign_detail_response['time_series'] = $this->campaign_health_model->get_campaign_time_series_id($campaign_id, $start, $end);
        if ($this->vl_platform->get_access_permission_redirect('healthcheck_placements') == '')
        {
          $campaign_detail_response['second_block'] = $this->campaign_health_model->get_sites_id($campaign_id, $start, $end);
          $campaign_detail_response['second_block_title'] = 'Top 10 Sites';
        }
        $campaign_detail_response['city_block'] = $this->campaign_health_model->get_cities_id($campaign_id, $start, $end);
        $campaign_detail_response['is_success'] = TRUE;
        echo json_encode($campaign_detail_response);
      }
      else
      {
        $error_array = array();
        foreach($ajax_verify['errors'] as $err)
        {
          $error_array[] = $err;
        }
        echo json_encode(array('errors' => $error_array, 'is_success' => FALSE));
      }
   }
  
  
  public function index(){
      $this-> role_check();
      $campaigns = $this->campaign_health_model->get_all_healthcheck_campaigns();
      $report_date = $this->campaign_health_model->get_last_impression_date();
      $data = array('campaigns'=>json_encode($campaigns),'report_date'=>$report_date[0]['value']);
      //print '<pre>'; print_r($data); print '</pre>';
      $this->load->view('campaign_health/index_asynch', $data); 
  }
  
  public function lifetime_dates_impressions($c_id){
      $this-> role_check();
      echo json_encode($this->campaign_health_model->get_lifetime_dates_impressions($c_id));
  }
  
  public function impression_total($blah = 'blah'){
      $this-> role_check();
      $campaign_id = urldecode($_GET['c_id']);
      $start = urldecode($_GET['st']);
      $end = urldecode($_GET['end']);
      echo json_encode($this->campaign_health_model->get_impressions_total($campaign_id,$start,$end));
  }
  
  
  public function get_overweight_detail($c= 'bl'){
      $this-> role_check();
      $c_id = urldecode($_GET['c_id']);
      $start_date = urldecode($_GET['st']);
      $end_date = urldecode($_GET['end']);
      $site = $this->campaign_health_model->get_heaviest_site($c_id,$start_date,$end_date);
       
      //print '<pre>'; print_r($site); print '</pre>';
      $total = $this->campaign_health_model->get_impressions_only($c_id,$start_date,$end_date);
      //print '<pre>'; print_r($total); print '</pre>';
      $rtg = $this->campaign_health_model->get_rtg_impressions_only($c_id,$start_date,$end_date);
      //print '<pre>'; print_r($rtg); print '</pre>';
      
      $site_weight = ($total[0]['impressions']==0)? 0 : count($site[0]['impressions']>0) ? $site[0]['impressions']/$total[0]['impressions'] : 0;
      $rtg_weight = ($total[0]['impressions']==0)? 0 : count($rtg[0]['impressions']>0) ? $rtg[0]['impressions']/$total[0]['impressions'] : 0;
      
      
      
      echo json_encode(array('single_site_weight'=>$site_weight,'rtg_weight'=>$rtg_weight));
  }
  

  
	public function download_tags($c_id)
	{
		$tags_result = get_content_for_download_tags($c_id,null);
		
		if (!$tags_result['status'])
		{
			echo $tags_result['err_msg'];
			return;
		}
		
		$this->load->view('campaigns_main/download_campaign_tags', $tags_result['data']);
	}

  private function make_url($c_id)
  {
     $url_prefix = $this->campaign_health_model->get_brand_cdn_url_prefix($c_id);
    
     if($url_prefix == NULL)
     {
	return base_url();
     }
     return "http://".$url_prefix.".brandcdn.com/";
     
  }
  
  public function get_adsets($c_id)
  {
      $versions = $this->campaign_health_model->get_versions_for_campaign($c_id);
      if($versions == FALSE)
      {
	  $return['success'] = FALSE;
	  echo json_encode($return);
	  return;
      }
      $return['output'] = "";
      $url = $this->make_url($c_id);
      foreach($versions as $version)
      {
	  $encoded = base64_encode(base64_encode(base64_encode($version['id'])));
	  $return['output'] .= "<a target='_blank' href='".$url."crtv/get_adset/".$encoded."'><h4>".$url."crtv/get_adset/".$encoded."</h4></a><br>";
      }
      $return['success'] = TRUE;
      echo json_encode($return);
  }
}
?>
