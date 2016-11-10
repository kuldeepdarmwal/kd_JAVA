<?php
class demo_partner_model extends CI_Model
{

	private $new_user_details;
	private $partner_id;
	private $advertisers_id;
	public function __construct()
	{		
		$this->load->model('tank_auth/users');
		$this->load->model('lift_model');
		$this->load->library(array('session', 'tank_auth', 'vl_platform'));		
		$this->load->helper(array('vl_ajax', 'mailgun'));
		// Shared Entities
		$this->partner_id = DEMO_PARTNER_ID;
		$this->advertisers_id = array(DEMO_ADVERTISER_BIG, DEMO_ADVERTISER_MEDIUM, DEMO_ADVERTISER_SMALL, DEMO_ADVERTISER_PREROLL);
	}
	
	public function validate_unique_demo_emails($sales_email, $adv_email)
        {
                $sql_bindings = array();
                $sql_bindings = array(
                        $sales_email,
                        $adv_email
                );               
                $query = "
                    SELECT                 	
                            id
                    FROM
                            users
                    WHERE
                            email IN(? , ? )";
                $result = $this->db->query($query, $sql_bindings);
                return $result->num_rows() > 0;
        }
	
	private function relate_sales_with_partner($sales_user_id, $shared_partner_id)
	{
		$insert_data = array($sales_user_id, $shared_partner_id);
		$sql = "INSERT INTO 
				wl_partner_owners (user_id, partner_id) 
			VALUES(?, ?)";
		$query = $this->db->query($sql, $insert_data);
		return $this->db->affected_rows();
	}
	
	private function relate_client_with_advertisers($client_id, $shared_advertisers_id)
	{  
		foreach($shared_advertisers_id as $advertiser_id)
		{
		    $insert_data = array($client_id, $advertiser_id);
		    $sql = "INSERT INTO 
				    clients_to_advertisers (user_id, advertiser_id) 
			    VALUES(?, ?)";
		    $query = $this->db->query($sql, $insert_data);		    
		}
	}
	
	public function relate_new_user_with_shared_data($data_sales, $data_client)
	{
		// Relate user data with partner 
		$sales_details = $this->relate_sales_with_partner($data_sales['user_id'], $this->partner_id);		
		// Relate bussiness user with advertiser
		$client_details = $this->relate_client_with_advertisers($data_client['user_id'], $this->advertisers_id);	
	}
	
	public function create_demo_user($partner_id, $cname,  $sales_email, $client_email )
	{
	    $user_details['demo_sales_email'] = $sales_email;
	    $user_details['demo_business_email'] = $client_email;
	    $user_details['cname'] = $cname;
	    $user_details['partner_id'] = $partner_id;
	    $user_details['demo_partner_id'] = $partner_id;
	    $user_details['login_email'] =  $this->tank_auth->get_email($this->tank_auth->get_username());
	    $user_details['email_activation'] = $this->config->item('email_activation', 'tank_auth');
	    
	    // Create Sales User
	    $data_sales = $this->save_demo_user( $user_details, 'SALES');	    
	    //Create Client User
	    $data_client = $this->save_demo_user( $user_details, 'CLIENT');
	    if(!empty($data_sales) && !empty($data_client))
	    {
		    $user_details_email = $this->email_demo_details($user_details, $data_sales, $data_client);
		    $this->relate_new_user_with_shared_data($data_sales, $data_client);    
	    }else
	    {
		    $data['partner_form_error_message'] .= '<br /> There was an Error in creating Demo users!';
	    }	    
	}
	
	Public function save_demo_user($user_details , $role)
	{    
		$demo_password = '';
		if($role == "SALES")
		{
			$email = $user_details['demo_sales_email'];
			$is_group_super = 1;
		}else
		{
			$email = $user_details['demo_business_email'];
			$is_group_super = 0;
		}
	    
		$additional_fields = array(
					'address_1' => 'test address',
					'address_2' => 'test address',
					'city' => 'test city',
					'state' => 'test state',
					'zip' => 11234,
					'phone_number' => 1111112222
				);
		$demo_password = $this->create_password();
		$result = $this->tank_auth->create_user(
					    $email,
					    $email,
					    $demo_password,
					    $role,
					    0,
					    'none',
					    $is_group_super,
					    20267,
					    'John',
					    'Doe',
					    $user_details['demo_partner_id'],
					    1,
					    1,
					    1,
					    1,
					    0, //$data['user_permissions']['can_view_ad_sizes'] ? $ad_sizes_viewable : '',
					    $user_details['email_activation'],
					    $additional_fields
				    );
		return $result;
	}
	
	public function email_demo_details($user_details, $data_sales, $data_client)
	{
	    $message = 'New demo users successfully created!';
	    $domain_without_cname = $this->tank_auth->get_domain_without_cname();
	    $partner = $this->tank_auth->get_partner_info($user_details['partner_id']); 
	    $cname_demo = "http://".$user_details['cname'].".".$domain_without_cname;
	    $new_user_details = array();
	   	if(empty($user_details['cname']))
		{
			$cname_demo = "frequence-demo.".$domain_without_cname;
		}
		$new_user_details["password_sales"] = $data_sales["password"];		    
		$new_user_details["password_client"] = $data_client["password"];	
			
		$result = false;
		if($user_details['login_email'])
		{		    
		    $from  = "no-reply@brandcdn.com";
		    $to = $user_details['login_email'];
		    $subject = "New Demo partner created for : {$partner['partner_name']}";

		    $message  = "Below are the credentials for the demo sales and demo Business users created.<br/><br/>";
		    $message .=" URL to access demo environment : ".$cname_demo." <br/><br/>";
		    $message .=" Demo Sales Username : ".$user_details['demo_sales_email']."<br/>Demo Sales Password : ".$new_user_details['password_sales']."<br/><br/><br/>";
		    $message .=" Demo Business Username : ".$user_details['demo_business_email']."<br/>Demo Business Password : ".$new_user_details['password_client']."<br/><br/>";		   
		    $message .=" <b>Please note :</b> User credentials will expire in 15 days from today.<br/><br/>";		    
		    $result = mailgun(
			    $from,
			    $to,
			    $subject,		
			    $message,
			    "html",
			array( 
			    'Reply-To' => "no-reply@brandcdn.com"
			    )
		    );
		}		$message .= ($result) ? '<br>A message containing the new demo user credentials has been sent to the email address provided.' : "<br>Error configuring/sending email";
		unset($password); // Clear password (just for any case)
		return $new_user_details;
	}
	
	public function create_password($length = 8)
	{	    	    
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$password = substr( str_shuffle( $chars ), 0, $length );
		return $password;
	}
	
	/*
	 * Update Date Query
	 */
	public function update_date_for_demo_data()
	{
		$update_status = false;
		$query = '
                        SELECT                 	
                                MAX(DATE)
                        FROM
                                report_cached_adgroup_date
                        ';
                $response = $this->db->query($query);
		$max_overall_date = $response->row_array();
		
		
		$query = " 
                        SELECT                 	
                                MAX(DATE)
                        FROM
                                report_cached_adgroup_date
			WHERE
				AdGroup_ID IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
                        ";
		$response = $this->db->query($query);
		$max_demo_date = $response->row_array();
		$date_interval = date_diff(date_create($max_overall_date['MAX(DATE)']),date_create($max_demo_date['MAX(DATE)']));
		$date_diff = $date_interval->d;
		$binding = array($date_diff,$max_overall_date['MAX(DATE)']);
				
		$result_array = array();
		$update_sql = "
		    UPDATE 
			    SiteRecords
		    SET 
			    Date = DATE_ADD(`Date`, INTERVAL ? DAY)
		    WHERE 
		            Date < ?
		    AND    
			    AdGroupID IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY Date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['SiteRecords'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    CityRecords
		    SET 
			    Date = DATE_ADD(`Date`, INTERVAL ? DAY)
		    WHERE 
		            Date < ?
		    AND    
			    AdGroupID IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY Date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['CityRecords'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    report_cached_adgroup_date
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND    
			    adgroup_id IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY Date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['report_cached_adgroup_date'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    report_ad_size_records
		    SET 
			    Date = DATE_ADD(`Date`, INTERVAL ? DAY)
		    WHERE 
		            Date < ?
		    AND    
			    AdGroupID IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY Date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['report_ad_size_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    report_video_play_records
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND    
			    AdGroupID IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['report_video_play_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    zcta_records
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND    
			    ad_group_id IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['zcta_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    engagement_records
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND    
			    creative_id IN (318955,318956,318957,318958,318959,318960,318961,318962,318963,318964,318965,318966,318967,318968,318969,320017,320018,320019,320020,320021)
		    ORDER BY date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['engagement_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    rfc_campaign_population_by_date
		    SET 
			    run_date = DATE_ADD(`run_date`, INTERVAL ? DAY)
		    WHERE 
		            run_date < ?
		    AND    
			    ttd_campaign_id IN ('ttdcamid1','ttdcamid2','ttdcamid3')
		    ORDER BY run_date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['rfc_campaign_population_by_date'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    rfc_campaign_population_by_zip_and_date
		    SET 
			    run_date = DATE_ADD(`run_date`, INTERVAL ? DAY)
		    WHERE 
		            run_date < ?
		    AND    
			    ttd_campaign_id IN ('ttdcamid1','ttdcamid2','ttdcamid3')
		    ORDER BY run_date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['rfc_campaign_population_by_zip_and_date'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    report_creative_records
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND    
			    adgroup_id IN ('1demoadgrp','2demoadgrp','3demoadgrp','4demoadgrp','5demoadgrp','1demoadgrp2','2demoadgrp2','3demoadgrp2','4demoadgrp2','5demoadgrp2','1demoadgrp3','2demoadgrp3','3demoadgrp3','4demoadgrp3','5demoadgrp3')
		    ORDER BY Date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['report_creative_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    tp_spectrum_tv_previous_airing_data
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND     
		            account_ul_id IN (99967890,99912345)
		    ORDER BY Date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['report_creative_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    tp_spectrum_tv_verified_data
		    SET 
			    verified_date = DATE_ADD(`verified_date`, INTERVAL ? DAY)
		    WHERE 
		            verified_date < ?
		    AND    
			    account_ul_id IN (99967890,99912345)
		    ORDER BY verified_date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['report_creative_records'] = $this->db->affected_rows();
		
		$update_sql = "
		    UPDATE 
			    tp_spectrum_tv_impressions_by_syscode
		    SET 
			    date = DATE_ADD(`date`, INTERVAL ? DAY)
		    WHERE 
		            date < ?
		    AND    
			    account_ul_id IN (99967890,99912345)
		    ORDER BY date DESC";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['tp_spectrum_tv_impressions_by_syscode'] = $this->db->affected_rows();
		
		// Update Geo Fencing Data
		$update_sql = "
		    INSERT IGNORE INTO geofence_saved_points 
			    (geofence_adgroup_centers_id, date_time, location_point, os, was_clicked)
		    SELECT 
			    (CASE 
				WHEN gsp.geofence_adgroup_centers_id = 6 THEN (SELECT id FROM geofence_adgroup_centers WHERE `name` = 'Geofence_location_1')
				WHEN gsp.geofence_adgroup_centers_id = 7 THEN (SELECT id FROM geofence_adgroup_centers WHERE `name` = 'Geofence_location_2')
				WHEN gsp.geofence_adgroup_centers_id = 8 THEN (SELECT id FROM geofence_adgroup_centers WHERE `name` = 'Geofence_location_3')
				WHEN gsp.geofence_adgroup_centers_id = 9 THEN (SELECT id FROM geofence_adgroup_centers WHERE `name` = 'Geofence_location_4')
				WHEN gsp.geofence_adgroup_centers_id = 10 THEN (SELECT id FROM geofence_adgroup_centers WHERE `name` = 'Geofence_location_5')
				WHEN gsp.geofence_adgroup_centers_id = 11 THEN (SELECT id FROM geofence_adgroup_centers WHERE `name` = 'Geofence_location_6')
				ELSE NULL
			    END
			    ) AS geofence_adgroup_id,
			    TIMESTAMPADD(DAY, 1, `date_time`),
			    `location_point`,
			    `os`,
			    `was_clicked`
		    FROM 
			    geofence_saved_points AS gsp 
		    WHERE 
			    geofence_adgroup_centers_id IN (6, 7, 8, 9, 10, 11)";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['geofence_saved_points'] = $this->db->affected_rows();
		
		$update_sql = "
		    INSERT IGNORE INTO geofence_daily_totals 
			    (`date`, geofence_adgroup_centers_id, impressions_total_android, impressions_total_ios, impressions_total_other, clicks_total_android, clicks_total_ios, clicks_total_other)
		    SELECT 
			    DATE_ADD(`date`, INTERVAL 1 DAY),
			    (CASE 
				WHEN gdt.geofence_adgroup_centers_id = 6 THEN (SELECT id FROM geofence_adgroup_centers WHERE `NAME` = 'Geofence_location_1')
				WHEN gdt.geofence_adgroup_centers_id = 7 THEN (SELECT id FROM geofence_adgroup_centers WHERE `NAME` = 'Geofence_location_2')
				WHEN gdt.geofence_adgroup_centers_id = 8 THEN (SELECT id FROM geofence_adgroup_centers WHERE `NAME` = 'Geofence_location_3')
				WHEN gdt.geofence_adgroup_centers_id = 9 THEN (SELECT id FROM geofence_adgroup_centers WHERE `NAME` = 'Geofence_location_4')
				WHEN gdt.geofence_adgroup_centers_id = 10 THEN (SELECT id FROM geofence_adgroup_centers WHERE `NAME` = 'Geofence_location_5')
				WHEN gdt.geofence_adgroup_centers_id = 11 THEN (SELECT id FROM geofence_adgroup_centers WHERE `NAME` = 'Geofence_location_6')
				ELSE NULL
			    END
			    ) AS geofence_adgroup_id,
			    `impressions_total_android`,
			    `impressions_total_ios`,
			    `impressions_total_other`,
			    `clicks_total_android`,
			    `clicks_total_ios`,
			    `clicks_total_other`
		    FROM 
			    geofence_daily_totals AS gdt 
		    WHERE 
			    date < ?
		    AND
			    geofence_adgroup_centers_id IN (6, 7, 8, 9, 10, 11)";
		$update_result = $this->db->query($update_sql,$binding);
		$result_array['geofence_daily_totals'] = $this->db->affected_rows();
		
		//Lift demo data setup.
		$this->setup_lift_data_for_demo();
		
		$update_status = true;
		return $update_status;
	}
	
	public function setup_lift_data_for_demo()
	{
		$demo_campaigns = array(16308,16309,16310);
		$demo_ttd_campaigns = array('ttdcamid1','ttdcamid2','ttdcamid3');
		$demo_advertisers = array(24300,24301,24302);
		
		//Get last day of previous to previous month
		$query = "
				SELECT LAST_DAY(NOW() - INTERVAL 2 MONTH) AS last_day
                        ";
                $response = $this->db->query($query);
		$result_array = $response->row_array();
		$last_day_of_previous_to_previous_month = $result_array['last_day'];
		
		//Check if Lift tables has data for above day, if not add the data.
		$this->lift_model->process_lift_site_visits_30_data_for_demo($last_day_of_previous_to_previous_month, $demo_campaigns);
		$this->lift_model->process_lift_site_visits_30_zip_data_for_demo($last_day_of_previous_to_previous_month, $demo_campaigns);
		$this->lift_model->process_conversion_lift_reach_no_zip_30_data_for_demo($last_day_of_previous_to_previous_month, $demo_ttd_campaigns);
		$this->lift_model->process_conversion_lift_reach_zip_30_data_for_demo($last_day_of_previous_to_previous_month, $demo_ttd_campaigns);
		$this->lift_model->process_campaign_monthly_lift_approval_for_demo($last_day_of_previous_to_previous_month, $demo_campaigns);
		$this->lift_model->process_lift_campaign_site_visit_dates_for_demo($demo_advertisers);
		
		return true;
	}
	

	public function upload_tv_creatives_demo_data()
	{
		$insertion_status = FALSE;
		$update_sql = "
		    INSERT IGNORE INTO 
			    `tp_spectrum_tv_schedule` (`account_ul_id`, `client_traffic_id`, `spot_id`, `bookend_top_id`, `bookend_bottom_id`, `client_name`, `spot_name`, `bookend_top_name`, `bookend_bottom_name`, `30s_sec_spot_name`, `duration`, `zone`, `zone_sys_code`, `network`, `air_time_date`, `program`, `refresh_time_date`, `traffic_system`) 
		    VALUES
			    ('99967890', '50380', 285767831, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES2', 966, 'FSW', '2020-03-06 06:42:30', 'World Poker Tour', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'CMDY', '2020-03-06 04:12:30', 'South Park', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'ESPN', '2020-03-06 04:11:30', '2015 World Series of Poker', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'VH1', '2020-03-06 02:12:30', 'VH1 Plus Music', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'USA', '2020-03-06 01:42:30', 'Law & Order: Special Victims Unit\r\n', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'ESPN', '2020-03-06 01:41:30', 'SportsCenter', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'USA', '2020-03-06 01:12:30', 'Law & Order: Special Victims Unit\r\n', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES1', 8139, 'ESPN', '2020-03-06 01:11:30', 'SportsCenter', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES3', 1685, 'CMDY', '2020-03-06 04:11:30', 'South Park', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES3', 1685, 'VH1', '2020-03-06 02:12:30', 'VH1 Plus Music', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES2', 966, 'H2', '2020-03-06 06:42:30', 'Ancient Aliens', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES2', 966, 'FSW', '2020-03-06 06:42:30', 'World Poker Tour', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES2', 966, 'FSW', '2020-03-06 06:12:30', 'World Poker Tour', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES2', 966, 'H2', '2020-03-06 05:42:30', 'Modern Marvels', '1970-01-01 00:00:00', 'Southeast'),
			    ('99912345', '50380', 285767830, NULL, NULL, 'Toyota of Greer-In House', 'COORS LIGHT 30s DEMO SPOT', 'TOG-11-3-15-B-15', 'TOG-11-3-15-B-15', '10270709 BOOKENDED', 30, 'TES2', 966, 'ESPN', '2020-03-06 04:11:30', '2015 World Series of Poker', '1970-01-01 00:00:00', 'Southeast')";
		$update_result = $this->db->query($update_sql);
		$tp_spectrum_tv_schedule = $this->db->affected_rows();
		
		$update_sql = "
		    INSERT IGNORE INTO 
			    `tp_spectrum_tv_creatives` (`id`, `link_mp4`, `link_webm`, `link_thumb`, `status`, `date_modified`) 
		    VALUES
			    ('285767830', 'https://test-reports-tv-data.s3.amazonaws.com/demo-01/output/videos/285767830_Coors%20Light%20Never%20Stops%20Commercial-o0Rbzke1-Zk.512x288.mp4?x-amz-security-token=FQoDYXdzENv%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaDL%2BSoYrtOUZTdwAC%2ByKsAZohTRkD99R9mbZkXXBMhbBEXLyvg2DcUg5%2FxGS0tHjBz4w%2BXiAg0QCE9c2eTidElGinexhRGS%2FNVjVkGZaaza3tYB81BeHfVpKKPxEuuHG9IbL838WSRgbeD7v%2BjMO%2B7DELhyj9C2y3nRaCk%2B4w2ykeUgdXzDDfkDxBCoIBL1lWPW5Vke4mVpGi%2B7lEH4TAetUU0Rp7siMH2PHQOgOvinZuJe12C11b2Sp1aKcoosKOuQU%3D&AWSAccessKeyId=ASIAJIRJINTSX4ILBI7Q&Expires=1463767202&Signature=app%2B4DB45CF6sKdygQAw5CDEimI%3D', 'https://test-reports-tv-data.s3.amazonaws.com/demo-01/output/videos/285767830_Coors%20Light%20Never%20Stops%20Commercial-o0Rbzke1-Zk.512x288.webm?x-amz-security-token=FQoDYXdzENv%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaDL%2BSoYrtOUZTdwAC%2ByKsAZohTRkD99R9mbZkXXBMhbBEXLyvg2DcUg5%2FxGS0tHjBz4w%2BXiAg0QCE9c2eTidElGinexhRGS%2FNVjVkGZaaza3tYB81BeHfVpKKPxEuuHG9IbL838WSRgbeD7v%2BjMO%2B7DELhyj9C2y3nRaCk%2B4w2ykeUgdXzDDfkDxBCoIBL1lWPW5Vke4mVpGi%2B7lEH4TAetUU0Rp7siMH2PHQOgOvinZuJe12C11b2Sp1aKcoosKOuQU%3D&AWSAccessKeyId=ASIAJIRJINTSX4ILBI7Q&Expires=1463767202&Signature=H3%2FVmnwN7FvdYoL710GPVJ08sO4%3D', 'https://test-reports-tv-data.s3.amazonaws.com/demo-01/output/thumbnails/285767830_Coors%20Light%20Never%20Stops%20Commercial-o0Rbzke1-Zk.512x288.jpg?x-amz-security-token=FQoDYXdzENv%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaDL%2BSoYrtOUZTdwAC%2ByKsAZohTRkD99R9mbZkXXBMhbBEXLyvg2DcUg5%2FxGS0tHjBz4w%2BXiAg0QCE9c2eTidElGinexhRGS%2FNVjVkGZaaza3tYB81BeHfVpKKPxEuuHG9IbL838WSRgbeD7v%2BjMO%2B7DELhyj9C2y3nRaCk%2B4w2ykeUgdXzDDfkDxBCoIBL1lWPW5Vke4mVpGi%2B7lEH4TAetUU0Rp7siMH2PHQOgOvinZuJe12C11b2Sp1aKcoosKOuQU%3D&AWSAccessKeyId=ASIAJIRJINTSX4ILBI7Q&Expires=1463767202&Signature=3D1qj7YGh9D5%2B6hv6ZXw1mjadYM%3D', 'complete', '2016-04-29 18:00:02'),
			    ('285767831', 'https://test-reports-tv-data.s3.amazonaws.com/demo-01/output/videos/285767831_Nike%20USA%20Softball%20Commercial%20-%20Short%20Version-zcz3p1dYbQo.512x288.mp4?x-amz-security-token=FQoDYXdzENv%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaDL%2BSoYrtOUZTdwAC%2ByKsAZohTRkD99R9mbZkXXBMhbBEXLyvg2DcUg5%2FxGS0tHjBz4w%2BXiAg0QCE9c2eTidElGinexhRGS%2FNVjVkGZaaza3tYB81BeHfVpKKPxEuuHG9IbL838WSRgbeD7v%2BjMO%2B7DELhyj9C2y3nRaCk%2B4w2ykeUgdXzDDfkDxBCoIBL1lWPW5Vke4mVpGi%2B7lEH4TAetUU0Rp7siMH2PHQOgOvinZuJe12C11b2Sp1aKcoosKOuQU%3D&AWSAccessKeyId=ASIAJIRJINTSX4ILBI7Q&Expires=1463767202&Signature=MJOQCJelEXrp6YDityQaiRwIVZE%3D', 'https://test-reports-tv-data.s3.amazonaws.com/demo-01/output/videos/285767831_Nike%20USA%20Softball%20Commercial%20-%20Short%20Version-zcz3p1dYbQo.512x288.webm?x-amz-security-token=FQoDYXdzENv%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaDL%2BSoYrtOUZTdwAC%2ByKsAZohTRkD99R9mbZkXXBMhbBEXLyvg2DcUg5%2FxGS0tHjBz4w%2BXiAg0QCE9c2eTidElGinexhRGS%2FNVjVkGZaaza3tYB81BeHfVpKKPxEuuHG9IbL838WSRgbeD7v%2BjMO%2B7DELhyj9C2y3nRaCk%2B4w2ykeUgdXzDDfkDxBCoIBL1lWPW5Vke4mVpGi%2B7lEH4TAetUU0Rp7siMH2PHQOgOvinZuJe12C11b2Sp1aKcoosKOuQU%3D&AWSAccessKeyId=ASIAJIRJINTSX4ILBI7Q&Expires=1463767202&Signature=9dfqh3038BYsa6pgHpm2PquGdms%3D', 'https://test-reports-tv-data.s3.amazonaws.com/demo-01/output/thumbnails/285767831_Nike%20USA%20Softball%20Commercial%20-%20Short%20Version-zcz3p1dYbQo.512x288.jpg?x-amz-security-token=FQoDYXdzENv%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaDL%2BSoYrtOUZTdwAC%2ByKsAZohTRkD99R9mbZkXXBMhbBEXLyvg2DcUg5%2FxGS0tHjBz4w%2BXiAg0QCE9c2eTidElGinexhRGS%2FNVjVkGZaaza3tYB81BeHfVpKKPxEuuHG9IbL838WSRgbeD7v%2BjMO%2B7DELhyj9C2y3nRaCk%2B4w2ykeUgdXzDDfkDxBCoIBL1lWPW5Vke4mVpGi%2B7lEH4TAetUU0Rp7siMH2PHQOgOvinZuJe12C11b2Sp1aKcoosKOuQU%3D&AWSAccessKeyId=ASIAJIRJINTSX4ILBI7Q&Expires=1463767202&Signature=GH5A6cYT5I06frIxQsTeV%2B9sDJw%3D', 'complete', '2016-04-29 18:00:02')";
		$update_result = $this->db->query($update_sql);
		$tp_spectrum_tv_creatives = $this->db->affected_rows();
		
		if($tp_spectrum_tv_schedule > 0 && $tp_spectrum_tv_creatives > 0)
		{
			$insertion_status = TRUE;
		}
		return $insertion_status;
	}
}
?>
