<?php
	ini_set('memory_limit', '2048M');
?>
<?php
class Comscore_integration extends CI_Controller
{
	//CONSTANTS
	private $COMSCORE_US_GEO_ID=840;
	private $COMSCORE_KEYMEASURES_WSDL_URL="https://api.comscore.com/KeyMeasures.asmx?WSDL";
	private $COMSCORE_DMA_WSDL_URL="https://api.comscore.com/DemographicProfile.asmx?WSDL";
	private $COMSCORE_LOCAL_DMA_WSDL_URL="https://api.comscore.com/DMA/DemographicProfileDMA.asmx?WSDL";
	
	private $COMSCORE_FAILED="Failed";
	private $COMSCORE_PROCESSING="Processing";
	private $COMSCORE_QUEUED="Queued";
	private $COMSCORE_COMPLETED="Completed";
	
	//SESSION VARIABLES
	private $comscore_job_id_to_process;
	private $comscore_time_period_id;// this is the id for the latest month that comscore publishes data. This is loaded only once in the constructor
	private $comscore_local_dma_time_period_id;// this is the id for the latest month that comscore publishes data. This is loaded only once in the constructor
	
	private $count_comscore_calls_demo=0; // total unique calls to be made . a
	private $count_comscore_calls_demo_success=0; // data processed and persisted .b
	private $count_comscore_calls_demo_fail=0; // fail during first time report pull for a given job id
	private $count_comscore_calls_demo_fail_perm=0; // fail permament .c
	private $count_comscore_calls_demo_no_job_id=0; // fail as no job id passed back.d (a=b + d + c)
	private $time_period_name_comscore=""; // time period for which this job is running
	private $count_timeout_exceptions=0; // time period for which this job is running
	
	public function __construct()
	{
		parent::__construct();
		
		$this->load->helper('mailgun');
		$this->load->library('cli_data_processor_common');
		$this->load->library('comscore_php_soap_common');
		$this->load->model('comscore_integration_model');
		
	}
	
	// this method will be invoked weekly during off-peak hours. it will first delete all rows in the report header caching table and insert new rows
	// from the cityrecords table. After deleting and inserts, it will send out an email to tech group with count of rows inserted
	// Please take care to not run this method frequently as it processes large volume of data.
	public function load_websites_from_comscore_to_db()
	{
		echo '--> Comscore_integration in ';
		$start_timestamp=new DateTime("now");
		$subject_date=$start_timestamp->format('Y-m-d');
		$this->cli_data_processor_common->mark_script_execution_start_time();
		$message='<br><br><table border="1">';
		
		try
		{
				$this->load_comscore_time_period_id();
				//@returns: sites_from_comscore_count, total_sites_in_sites_table_before_count, total_sites_in_sites_table_after_count
				$message .='<tr><td><a href="http://frequence.com" target="_blank"><img src="https://s3.amazonaws.com/brandcdn-assets/partners/frequence/login.png"/></a></td><td></td></tr>';
				$message .='<tr><td style="font-weight: bold;font-weight: 900;text-align: center;">COMSCORE INTEGRATION REPORT</td><td></td></tr>';
				$message .='<tr><td>Time Period: 3 months average data for '.$this->time_period_name_comscore.'</td><td></td></tr>';
				
				echo '0. load_geoids_for_urls_comscore_to_db in ';
				$sites_info=$this->get_all_ids_for_urls_from_comscore();
				$final_timestamp0=new DateTime("now");
				$message .='<tr><td style="font-weight: bold;background-color:yellow">PART A: FETCHMEDIA API TO GET IDS FOR NEW URLS</td><td></td></tr>';
				$message .='<tr><td>Processing time</td><td>' . date_diff($start_timestamp, $final_timestamp0)->format("%Hh %imin, %ss") . '</td></tr>';
				$message .='<tr><td>Count of Sites with blank Comscore ID before FetchMedia call</td><td>' . $sites_info['total_sites_in_sites_table_before_count'] . '</td></tr>';
				
				echo '1. load_websites_from_comscore_to_db in ';
				$sites_info=$this->get_all_sites_from_comscore();
				$final_timestamp1=new DateTime("now");
				$message .='<tr><td style="font-weight: bold;background-color:yellow">PART B: KEYMETRIX API TO GET LIST OF SITES</td><td></td></tr>';
				$message .='<tr><td>Total Adsupported sites in Comscore Keymetrix API</td><td>' . $sites_info['sites_from_comscore_count'] . '</td></tr>';
				$message .='<tr><td>Processing time</td><td>' . date_diff($final_timestamp0, $final_timestamp1)->format("%Hh %imin, %ss") . '</td></tr>';
				$message .='<tr><td>Count of Sites in Sites table before the Keymetrix API call</td><td>' . $sites_info['total_sites_in_sites_table_before_count'] . '</td></tr>';
				$message .='<tr><td>Count of Sites in Sites table after the Keymetrix API call</td><td>' . $sites_info['total_sites_in_sites_table_after_count'] . '</td></tr>';
				$message .='<tr><td>Count of Sites with blank Comscore ID after Keymetrix API call</td><td>' . $sites_info['total_sites_in_sites_table_after_count_no_id'] . '</td></tr>';
					
				echo '2. load_websites_from_comscore_to_db : get_all_sites_from_comscore complete ';
				//@returns: total_api_calls_made_count, total_sites_demo_table_before_count, total_sites_demo_table_after_count
				$demo_info=$this->get_demo_data_from_comscore(); 
				$final_timestamp2=new DateTime("now");
				$message .='<tr><td style="font-weight: bold;background-color:yellow">PART C: DEMOGRAPHICS API TO GET NATIONAL DEMO DATA</td><td></td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;A. API calls made to Comscore (A=B + C + D). 5 sites per call</td><td>' . $demo_info['count_comscore_calls_demo'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;B. Count of permanent Failures for calls with JOB ID</td><td>' . $demo_info['count_comscore_calls_demo_fail_perm'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;C. Count of successful API calls</td><td>' . $demo_info['count_comscore_calls_demo_success'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;D. Count of API calls failed due to no Comscore data (no JOB ID) </td><td>' . $demo_info['count_comscore_calls_demo_no_job_id'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;E. Count of one time failed API calls</td><td>' . $demo_info['count_comscore_calls_demo_fail'] . '</td></tr>';
				$message .='<tr><td>Total Processing time</td><td>' . date_diff($final_timestamp1, $final_timestamp2)->format("%Hh %imin, %ss") . '</td></tr>';
				$message .='<tr><td>Count of National Demo rows in comscore_demo_data before the Demo calls</td><td>' . $demo_info['total_sites_demo_table_before_count'] . '</td></tr>';
				$message .='<tr><td>Count of National Demo rows in comscore_demo_data after the Demo calls</td><td>' . $demo_info['total_sites_demo_table_after_count'] . '</td></tr>';
				
				echo '3. load_websites_from_comscore_to_db : get_demo_data_from_comscore complete ';
					
				//@returns: total_local_api_calls_made_count, total_sites_localdemo_table_before_count, total_sites_localdemo_table_after_count, total_valid_sites_pulled_count
				$local_demo_info=$this->get_local_demo_data_from_comscore();
				$final_timestamp3=new DateTime("now");
				$message .='<tr><td style="font-weight: bold;background-color:yellow">PART D: LOCAL DEMOGRAPHICS API TP GET DMA DEMO DATA';
				if ($this->comscore_local_dma_time_period_id !=$this->comscore_time_period_id)
					$message .=' (Using previous Comscore TimePeriod)';
				$message .='</td><td></td></tr>';
				$message .='<tr><td>Count of Local Sites in our system tagged to cities used to make Local Demo calls</td><td>' . $local_demo_info['total_local_tagged_sites'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;A. API calls made to Comscore (A=B + C + D). One Site-DMA combination per call</td><td>' . $local_demo_info['count_comscore_calls_demo'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;B. Count of permanent Failures for calls with JOB ID</td><td>' . $local_demo_info['count_comscore_calls_demo_fail_perm'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;C. Count of successful API calls</td><td>' . $local_demo_info['count_comscore_calls_demo_success'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;D. Count of API calls failed due to no data (no JOB ID) </td><td>' . $local_demo_info['count_comscore_calls_demo_no_job_id'] . '</td></tr>';
				$message .='<tr><td>&nbsp;&nbsp;E. Count of one time failed API calls</td><td>' . $local_demo_info['count_comscore_calls_demo_fail'] . '</td></tr>';
				
				$message .='<tr><td>Total Processing time</td><td>' . date_diff($final_timestamp2, $final_timestamp3)->format("%Hh %imin, %ss") . '</td></tr>';
				$message .='<tr><td>Count of Local Demo rows in comscore_demo_data before Demo calls</td><td>' . $local_demo_info['total_sites_demo_table_before_count'] . '</td></tr>';
				$message .='<tr><td>Count of Local Demo rows in comscore_demo_data after Demo calls</td><td>' . $local_demo_info['total_sites_demo_table_after_count'] . '</td></tr>';
				$message .='<tr><td style="font-weight: bold">Total Processing time</td><td>' . date_diff($start_timestamp, $final_timestamp3)->format("%Hh %imin, %ss") . '</td></tr>';
				$message .='<tr><td>Total Timeout exceptions</td><td>' . $this->count_timeout_exceptions . '</td></tr>';
				
				echo '4. load_websites_from_comscore_to_db : get_local_demo_data_from_comscore complete ';
				$subject="Comscore Integration: Successfully completed - week : ". $subject_date;
			} 
			catch (Exception $exception) 
			{
				$subject="Comscore Integration: Failed - week : ". $subject_date;
				echo $exception->getMessage();
				echo "This is final exception message. Now sending email and out";
				$message.="<br><br>Exception: ". $exception->getMessage();
			}
			echo 'load_websites_from_comscore_to_db out ----- END ';
			$this->send_email($subject, $message, 'html');
	}
	
	///////// AREA A: FETCH MEDIA API TO PULL THE COMSORE SITE IDS FOR URLS ADDED NEW
	private function get_all_ids_for_urls_from_comscore()
	{
		$sites_info=array();
		$soap_client=$this->comscore_php_soap_common->get_cs_soap_client($this->COMSCORE_KEYMEASURES_WSDL_URL);
		$sites_info['total_sites_in_sites_table_before_count']=$this->comscore_integration_model->count_sites_table_blank_comscore_id();
		$url_array=$this->comscore_integration_model->read_sites_table_for_blank_comscore_ids();
		
		$inner_counter=0;
		$suburl_array=array();
		
		foreach ($url_array as $row_data) 
		{
			$suburl_array[$inner_counter]=$row_data;
			
			if ($inner_counter==5) 
			{
				$this->call_comscore_persist_data($soap_client, $suburl_array);
				$inner_counter=0;
				$suburl_array=array();
			} 
			else
			{
				$inner_counter++;
			}
		}
		
		if (count($suburl_array) > 0)
		{
			$this->call_comscore_persist_data($soap_client, $suburl_array);
		}
		//return the array with counts to be sent in summary email
		return $sites_info;
	}
	
	private function call_comscore_persist_data($soap_client, $url_array) 
	{
		$param_data1=new Parameter();
		$param_data1->KeyId="geo";
		$param_data1->Value=$this->COMSCORE_US_GEO_ID; //US
		
		$param_data2=new Parameter();
		$param_data2->KeyId="timeType";
		$param_data2->Value="5";  // 3 months avg
		
		$param_data3=new Parameter();
		$param_data3->KeyId="timePeriod";
		$param_data3->Value=$this->comscore_time_period_id;
		
		$param_data4=new Parameter();
		$param_data4->KeyId="mediaSetType";
		$param_data4->Value="1"; // Total Audience
		
		$fetch_media_query=new fetchMediaQuery();
		
		foreach ($url_array as $url_counter => $row_data)
		{
			$search_criteria=new SearchCritera();
			$search_criteria->Critera=$row_data;
			$fetch_media_query->SearchCritera[$url_counter]=$search_criteria;
		}
		
		$report_query=new reportQuery();
		$report_query->Parameter[0]=$param_data1;
		$report_query->Parameter[1]=$param_data2;
		$report_query->Parameter[2]=$param_data3;
		$report_query->Parameter[3]=$param_data4;
		
		$fetch_media=new FetchMedia();
		$fetch_media->parameterId="media"; // string
		$fetch_media->fetchMediaQuery=$fetch_media_query; // fetchMediaQuery
		$fetch_media->reportQuery=$report_query;
		
		$result=$soap_client->FetchMedia($fetch_media);
		$sites_array=array();
		$counter=0;
		
		if (property_exists($result, 'FetchMediaResult') && property_exists($result->FetchMediaResult, 'MediaItem'))
		{
			if (is_array($result->FetchMediaResult->MediaItem))
			{
				foreach ($result->FetchMediaResult->MediaItem as $row_data)
				{
					$site_url=$this->cleanse_url($row_data->Name);
					if ($this->is_valid_url($site_url)) 
					{
						$sites_array[$counter][0]=$site_url;
						$sites_array[$counter][1]=$row_data->Id;
						$counter++;
					}
				}
			}
			else
			{
				$site_url=$this->cleanse_url($result->FetchMediaResult->MediaItem->Name);
				
				if ($this->is_valid_url($site_url))
				{
					$sites_array[0][0]=$site_url;
					$sites_array[0][1]=$result->FetchMediaResult->MediaItem->Id;
				}
			}
		}
		$this->comscore_integration_model->persist_comscore_site_data($sites_array);
	}
	
	///////// AREA B: GET KEYMETRIX ALL SITES LIST AND PERSIST
	// this method does following steps:
	// 1. connect to comscore : keymetrix api and get a list of all the webdomains
	// 2. insert/update the list of sites in database in freq_sites_main table
	// 3. this function returns 1. total_sites_count 2. new_sites_count 3. comscore_api_timestamp 4. processing_timestamp
	// 4. sites_from_comscore_count, total_sites_in_sites_table_before_count, total_sites_in_sites_table_after_count
	private function get_all_sites_from_comscore() 
	{
		$soap_client=$this->comscore_php_soap_common->get_cs_soap_client($this->COMSCORE_KEYMEASURES_WSDL_URL);
		$sites_info=array();
		
		// step 1: submit request to comscore
		$comscore_job_id=$this->comscore_submit_keymetrix_request_get_jobid($soap_client);
	
		//step 2: wait till the job completes on comscore
		$this->wait_till_job_completes_pingreportstatus($comscore_job_id, $soap_client);
		
		//step 3: for the job id, get the list of all the sites from comscore
		$list_of_sites=$this->fetch_report_for_job_id($comscore_job_id, $soap_client);
		$sites_info['sites_from_comscore_count']=count($list_of_sites);
		
		//step 4: ct before the update
		$sites_info['total_sites_in_sites_table_before_count']=$this->comscore_integration_model->count_sites_table();		
		
		//step 5: pass the list of sites to model to persist it in db
		$sites_info['new_sites_count']=$this->comscore_integration_model->persist_comscore_site_data($list_of_sites);
	
		//step 6: ct after the update
		$sites_info['total_sites_in_sites_table_after_count']=$this->comscore_integration_model->count_sites_table();
		
		// step 7: get count of sites with no comscore id
		$sites_info['total_sites_in_sites_table_after_count_no_id']=$this->comscore_integration_model->count_sites_table_blank_comscore_id();
		 
		//return the array with counts and timestamps to be sent in summary email
		return $sites_info;
	}
	
	// this method calls the comscore keymetrix api and get back a job id. 
	private function comscore_submit_keymetrix_request_get_jobid($client) 
	{
		$param_data=new Parameter();
		$param_data->KeyId="geo";
		$param_data->Value=$this->COMSCORE_US_GEO_ID; //US
	
		$param_data1=new Parameter();
		$param_data1->KeyId="loc";
		$param_data1->Value="0"; // home and office
	
		$param_data2=new Parameter();
		$param_data2->KeyId="timeType";
		$param_data2->Value="5";  // 3 months avg
	
		$param_data3=new Parameter();
		$param_data3->KeyId="timePeriod";
		$param_data3->Value=$this->comscore_time_period_id;
		
		$param_data4=new Parameter();
		$param_data4->KeyId="mediaSetType";
		$param_data4->Value="1"; //ranked categories
	
		$param_data5=new Parameter();
		$param_data5->KeyId="targetGroup";
		$param_data5->Value="15"; // Total Audience
	
		$param_data6=new Parameter();
		$param_data6->KeyId="mediaSet";
		$param_data6->Value="28"; //all ad supported properties
	
		$param_data7=new Parameter();
		$param_data7->KeyId="measure";
		$param_data7->Value="1";
	
		$param_data8=new Parameter();
		$param_data8->KeyId="targetType";
		$param_data8->Value="0";
	
		$option_expand=new Option();
		$option_expand->Id="expandAllMedia";
	
		$query_data=new Query();
		$query_data->Parameter=array();
		$query_data->Parameter[0]=$param_data;
		$query_data->Parameter[1]=$param_data1;
		$query_data->Parameter[2]=$param_data2;
		$query_data->Parameter[3]=$param_data3;
		$query_data->Parameter[4]=$param_data4;
		$query_data->Parameter[5]=$param_data5;
		$query_data->Parameter[6]=$param_data6;
		$query_data->Parameter[7]=$param_data7;
		$query_data->Parameter[8]=$param_data8;
		$query_data->Option=$option_expand;
		
		$query_report=new QueryReport();
		$query_report->query=$query_data;
		
		$result=$client->QueryReport($query_report);
		return $result->QueryReportResult->JobId;
	}
	
	// this method pings a job id once it is completed. 
	// in return it gets back a list of all the sites. 
	// it fetches the site name and comscore site id and returns back in an array
	private function fetch_report_for_job_id($comscore_job_id, $client) 
	{
		sleep(60);
		$sites_array=array();
		$ping_report_status=new FetchReport();
		$ping_report_status->jobId=$comscore_job_id;
		$result=$client->FetchReport($ping_report_status);
		$counter=0;
		
		foreach ($result->FetchReportResult->REPORT->TABLE->TBODY->TR as $value) 
		{
			$site_url=$this->cleanse_url($value->TH->_);
			
			if ($this->is_valid_url($site_url) && property_exists($value->TH, 'web_id'))
			{
				$sites_array[$counter][0]=$site_url;
				$sites_array[$counter][1]=$value->TH->web_id;
				$counter++;
			}
		}
		
		return $sites_array;
	}
	
	///////// AREA C: GET NATIONAL DEMO DATA
	// this is the main entry point for fetching demo data from comscore and persisting it in database
	// @returns: total_api_calls_made_count, total_sites_demo_table_before_count, total_sites_demo_table_after_count
	private function get_demo_data_from_comscore()
	{ 
		$this->count_comscore_calls_demo=0;
		$this->count_comscore_calls_demo_success=0;
		$this->count_comscore_calls_demo_fail=0;
		$this->count_comscore_calls_demo_fail_perm=0;
		$this->count_comscore_calls_demo_no_job_id=0;
		
		$soap_client=$this->comscore_php_soap_common->get_cs_soap_client($this->COMSCORE_DMA_WSDL_URL);
		$sites_list=$this->comscore_integration_model->read_sites_table();  
		
		return $this->process_sites_for_demo_data($sites_list, $soap_client, 5);		
	}
	
	// this method expects all the sites from our sites main table as input
	// it loops thru all the sites and fetches the comscore ids. it loops thru 5 sites in each loop
	// next it creates a request for comscore demo api that accepts five sites at a time. 
	// this api call returns a job id
	// we ping this job id and checks until the job processing is complete by checking with the pingstatus comscore api
	// once the job processing is complete on comscore end, we ping fetch report to get the data
	// this api returns all the demo data for these 5 sites
	// next this data is persisted in our database site demo table
	// we request for 5 reports in one go (25 sites data) and process one request in one go. 
	// we do this to do proper throttling so that all our requests are queued and give time to comscore to process our data
	// the job id for each request is saved at the end of the $comscore_job_id_to_process array. we use this array as a stack
	// we will put the new job ids at the end and process the job ids from position 0. we will use array_values to reset the array everytime we remove some thing from array
	
	private function process_sites_for_demo_data($sites_list, $soap_client, $counter_jump) 
	{
		$results_array=array();
		
		$local_flag = false;
		if ($counter_jump == 1)
			$local_flag = true;
		
		$results_array['total_sites_demo_table_before_count']=$this->comscore_integration_model->count_demo_table($local_flag);
		
		$sites_list_length=count($sites_list);
		for ($counter=0; $counter < $sites_list_length; $counter+=$counter_jump) 
		{
			$this->count_comscore_calls_demo++; // total calls
			$sub_sites_array=array(); // this array is a 2d array. each row has 2 columns, cs site id and url
			$geo_id=$this->COMSCORE_US_GEO_ID;
			
			if (array_key_exists('dma_id', $sites_list[$counter])) 
			{
				$geo_id=$sites_list[$counter]['dma_id'];
			}
			
			for ($s_ctr=0; ($counter + $s_ctr) < $sites_list_length && $s_ctr < $counter_jump; $s_ctr+=1) 
			{
				$sub_sites_array[$s_ctr][0]=$sites_list[$counter + $s_ctr]['comscore_site_id'];
				$sub_sites_array[$s_ctr][1]=$sites_list[$counter + $s_ctr]['url'];
			}
			
			$is_sites_processed_flag=false;
			$try_again_counter=0;
			
			while (!$is_sites_processed_flag) 
			{
				$job_id_array=$this->request_comscore_dma_demo_report($sub_sites_array, $soap_client, $geo_id);
				
				if ($job_id_array==1) 
				{
					continue;
				}
				
				if (count($job_id_array)==0) 
				{
					$this->count_comscore_calls_demo_no_job_id +=1;
					break; // if the job is not returned from comscore. dont process and break from this while loop.
				}
				
				$this->comscore_job_id_to_process=$job_id_array;
				
				// this method will return false when the job call to comscore fails.
				$is_sites_processed_flag=$this->process_job_id_and_persist_data($soap_client, $geo_id, $counter_jump);
				
				if ($try_again_counter > 0) 
				{
					//we will try each set of 5 sites only 2 times and assume that comscore has the site data corrupted and move on.. 
					$this->count_comscore_calls_demo_fail_perm++;
					break;
				}
				$try_again_counter++;
			}
			
			if ($counter%100==0)
			{
				echo $counter_jump. '-'.$counter . '/'.$sites_list_length .'+'. $geo_id.' ';
			}
			
			//if ($counter > 100) break;
		}
		
		$results_array['total_sites_demo_table_after_count']=$this->comscore_integration_model->count_demo_table($local_flag);
		$results_array['count_comscore_calls_demo']=$this->count_comscore_calls_demo;
		$results_array['count_comscore_calls_demo_success']=$this->count_comscore_calls_demo_success;
		$results_array['count_comscore_calls_demo_fail']=$this->count_comscore_calls_demo_fail;
		$results_array['count_comscore_calls_demo_fail_perm']=$this->count_comscore_calls_demo_fail_perm;
		$results_array['count_comscore_calls_demo_no_job_id']=$this->count_comscore_calls_demo_no_job_id;
		
		return $results_array;
	}
	
	// accepts the entire $comscore_job_id_to_process and pick the jobid from the 0th element
	// checks if the jobid is complete
	// once job id is complete, fetch the report from comscore
	// persist data 
	// remove the 0th element and re-index the array
	private function process_job_id_and_persist_data($soap_client, $geo_id, $counter_jump) 
	{
		// take the 0th element from the array. we use array as a queue here
		$comscore_job_sub_array=$this->comscore_job_id_to_process; // take the 0th element always for processing
		$comscore_job_id=$comscore_job_sub_array[0];
		$comscore_job_status=$this->wait_till_job_completes_pingreportstatus($comscore_job_id, $soap_client);
		$comscore_demo_data_length=0;
		
		if ($comscore_job_status==$this->COMSCORE_COMPLETED) 
		{
			$comscore_demo_data=$this->fetch_dma_report_for_job_id($comscore_job_sub_array, $soap_client, $geo_id, $counter_jump);
			$comscore_demo_data_length=count($comscore_demo_data);

			if ($comscore_demo_data_length > 0) {
				$this->count_comscore_calls_demo_success+=1;
			}
		}		
		
		if ($comscore_job_status==$this->COMSCORE_FAILED || $comscore_demo_data_length > 0) 
		{

			if ($comscore_job_status==$this->COMSCORE_FAILED) 
			{
				$this->count_comscore_calls_demo_fail++;
				return false;
			} 
		}  
		
		$this->comscore_integration_model->persist_demo_data_for_sites($comscore_demo_data);  
		
		return true;
	}
	
	// once the job is completed, fetch entire report and return as it is. we are not fetching the data here itself and instead passing the entire 
	// response structure as it is to the model layer. this is not the best way to do it but we dont want to hardcode all the column names in two places.
	// our column names in the database and the attributes that comscore returns are different and there are over 100 attributes to map to
	private function fetch_dma_report_for_job_id($comscore_job_sub_array, $client, $geo_id, $counter_jump) 
	{
		$comscore_job_id=$comscore_job_sub_array[0]; // 1 job id
		$comscore_sites_list=$comscore_job_sub_array[1]; // array of sites for this job, 0 element=id, 1st element=site url 
		$comscore_sites_data_array=array();
		$sites_array=array();
		$ping_report_status=new FetchReport();
		$ping_report_status->jobId=$comscore_job_id;
		$try_more_flag=true;
		$try_again_counter=0;
		$result=null;
		
		while ($try_more_flag)
		{
			$result=null;

			try 
			{
				$result=$client->FetchReport($ping_report_status);
			} 
			catch (Exception $exception) 
			{
				echo " Exception: fetch_dma_report_for_job_id: comscore_job_id ". $comscore_job_id . " count_timeout_exceptions ".
				$this->count_timeout_exceptions . " geo_id ". $geo_id ;
				$this->count_timeout_exceptions++;
			}
			
			if (property_exists($result, 'FetchReportResult') && property_exists($result->FetchReportResult, 'REPORT')
					&& property_exists($result->FetchReportResult->REPORT, 'TITLE')) 
			{
				break;// if we can access the report go ahead
			}
			
			$try_again_counter++;
			
			if ($try_again_counter > 4) 
			{
				break;// try 4 times and quit
			}
		}
		
		if (!property_exists($result, 'FetchReportResult') || !property_exists($result->FetchReportResult, 'REPORT')) 
 	    {
			return $comscore_sites_data_array;
		}
	    
		$site_counter=0;
		
		// this loop initializes the blank 2d array $comscore_sites_data_array for each row of a site
		// this is the main array. each row contains at 0th element the comscore site id and for 1st element the complete data for that site
		// here we only initialize blank. the loop below takes the data from comscore and converts rows to columns
		$report_data=$result->FetchReportResult;
		
		foreach ($report_data->REPORT->TABLE->THEAD->TR as &$value) 
		{
			if ($counter_jump > 1) 
			{
				for ($init_counter=0; $init_counter < $counter_jump; $init_counter++) 
				{
					if (array_key_exists($init_counter+2, $value->TD)) 
					{
						$comscore_sites_data_array[$init_counter][0]=$value->TD[$init_counter+2]->_; // comscore site name that came back from comscore just now
						$site_row_data=array();
						$site_row_data['comscore_geo_main_id']=$geo_id;
						$site_name_td=$this->cleanse_url($value->TD[$init_counter+2]->_);						

						foreach ($comscore_sites_list as $site_data_arr)  // compare 5 names from original list to each returned name to pick one
						{
							if ($site_name_td==$site_data_arr[1]) 
							{
								$site_row_data['comscore_site_id']=$site_data_arr[0];
								break;
							}
						}
						$comscore_sites_data_array[$init_counter][1]=$site_row_data;// each row has comscore_geo_main_id element=geo id and comscore_site_id=site id
					}
				}	
			} 
			else 
			{
				for ($init_counter=0; $init_counter < $counter_jump; $init_counter++) 
				{
					$site_row_data=array();
					$site_row_data['comscore_geo_main_id']=$geo_id;
					$site_row_data['comscore_site_id']=$comscore_sites_list[0][0];
					$comscore_sites_data_array[$init_counter][1]=$site_row_data;
				}
			}
			break;
		}
		
		foreach ($result->FetchReportResult->REPORT->TABLE->TBODY->TR as &$value) 
		{
			$comscore_attribute_name=$value->TH->_;
			$database_atribute_name='';
			
			foreach ($this->comscore_integration_model->db_to_comscore_array as $db_column_name=>$cs_column_name) 
			{
				if ($cs_column_name==$comscore_attribute_name) 
				{
					$database_atribute_name=$db_column_name;// get each column
					break;
				}
			}
				
			if ($database_atribute_name=='')
				continue;
			
			if ($counter_jump > 1) 
			{
				$site_data_1="";
				
				if (array_key_exists(2, $value->TD) && $value->TD[2]->_ !='-Infinity') 
				{
					$site_data_1=round($value->TD[2]->_ * 1000);
				}
				
				$site_data_2="";
				
				if (array_key_exists(3, $value->TD) && $value->TD[3]->_ !='-Infinity') 
				{
					$site_data_2=round($value->TD[3]->_ * 1000);
				}
				
				$site_data_3="";
				
				if (array_key_exists(4, $value->TD) && $value->TD[4]->_ !='-Infinity') 
				{
					$site_data_3=round($value->TD[4]->_ * 1000);
				}
				
				$site_data_4="";
				
				if (array_key_exists(5, $value->TD) && $value->TD[5]->_ !='-Infinity') 
				{
					$site_data_4=round($value->TD[5]->_ * 1000);
				}
				
				$site_data_5="";
				
				if (array_key_exists(6, $value->TD) && $value->TD[6]->_ !='-Infinity') 
				{
					$site_data_5=round($value->TD[6]->_ * 1000);
				}
				
				if (array_key_exists(0, $comscore_sites_data_array)) 
				{
					$site_row_data=$comscore_sites_data_array[0][1];
					$site_row_data[$database_atribute_name]=$site_data_1;
					$comscore_sites_data_array[0][1]=$site_row_data;
				}
				
				if (array_key_exists(1, $comscore_sites_data_array)) 
				{
					$site_row_data=$comscore_sites_data_array[1][1];
					$site_row_data[$database_atribute_name]=$site_data_2;
					$comscore_sites_data_array[1][1]=$site_row_data;
				}
				
				if (array_key_exists(2, $comscore_sites_data_array)) 
				{
					$site_row_data=$comscore_sites_data_array[2][1];
					$site_row_data[$database_atribute_name]=$site_data_3;
					$comscore_sites_data_array[2][1]=$site_row_data;
				}
				
				if (array_key_exists(3, $comscore_sites_data_array)) 
				{
					$site_row_data=$comscore_sites_data_array[3][1];
					$site_row_data[$database_atribute_name]=$site_data_4;
					$comscore_sites_data_array[3][1]=$site_row_data;
				}
				
				if (array_key_exists(4, $comscore_sites_data_array)) 
				{
					$site_row_data=$comscore_sites_data_array[4][1];
					$site_row_data[$database_atribute_name]=$site_data_5;
					$comscore_sites_data_array[4][1]=$site_row_data;
				}
			} 
			else 
			{
				$site_data_1="";
				
				if ($value->TD->_ !='-Infinity') 
				{
					$site_data_1=round($value->TD->_ * 1000);
					
					if (array_key_exists(0, $comscore_sites_data_array)) 
					{
						$site_row_data=$comscore_sites_data_array[0][1];
						$site_row_data[$database_atribute_name]=$site_data_1;
						$comscore_sites_data_array[0][1]=$site_row_data;
					}
				}
			}
		}
		return $comscore_sites_data_array;
	}
	
	// this is the method that calls the demo api for comscore. it accepts an array with upto comscore site ids for 5 sites.
	private function request_comscore_dma_demo_report($sub_sites_array, $soap_client, $geo_id) 
	{
		$param_data=new Parameter();
		$param_data->KeyId="geo";
		$param_data->Value=$geo_id;  
			
		$param_data1=new Parameter();
		$param_data1->KeyId="loc";
		$param_data1->Value="0"; // home and office
			
		$param_data2=new Parameter();
		$param_data2->KeyId="timeType";
		$param_data2->Value="5";  // 3 months avg
			
		$param_data3=new Parameter();
		$param_data3->KeyId="timePeriod";
		
		if ($geo_id==$this->COMSCORE_US_GEO_ID) 
		{
			$param_data3->Value=$this->comscore_time_period_id;
		} 
		else 
		{
			$param_data3->Value=$this->comscore_local_dma_time_period_id;
		}
			
		$param_data5=new Parameter();
		$param_data5->KeyId="targetGroup";
		$param_data5->Value="15"; // Total Audience
			
		$param_data6=new Parameter();
		$param_data6->KeyId="measure";
		
		if ($geo_id==$this->COMSCORE_US_GEO_ID) 
		{
			$param_data6->Value="73";
		}
		else 
		{ 
			$param_data6->Value="1";
		}
		
		$query_data=new Query();
		$query_data->Parameter=array();
		$query_data->Parameter[0]=$param_data;
		$query_data->Parameter[1]=$param_data1;
		$query_data->Parameter[2]=$param_data2;
		$query_data->Parameter[3]=$param_data3;
		$query_data->Parameter[4]=$param_data5;
		$query_data->Parameter[5]=$param_data6;
		$site_names="";	
		
		if (array_key_exists(0,$sub_sites_array)) 
		{
			$param_data_site_0=new Parameter();
			$param_data_site_0->KeyId="media";
			$param_data_site_0->Value=$sub_sites_array[0][0];
			$query_data->Parameter[6]=$param_data_site_0;
			$site_names .=" ". $sub_sites_array[0][0];
		}
		
		if (array_key_exists(1,$sub_sites_array)) 
		{
			$param_data_site_1=new Parameter();
			$param_data_site_1->KeyId="media";
			$param_data_site_1->Value=$sub_sites_array[1][0];
			$query_data->Parameter[7]=$param_data_site_1;
			$site_names .=" ". $sub_sites_array[1][0];
		}
		
		if (array_key_exists(2,$sub_sites_array)) 
		{
			$param_data_site_2=new Parameter();
			$param_data_site_2->KeyId="media";
			$param_data_site_2->Value=$sub_sites_array[2][0];
			$query_data->Parameter[8]=$param_data_site_2;
			$site_names .=" ". $sub_sites_array[2][0];
		}
		
		if (array_key_exists(3,$sub_sites_array)) 
		{
			$param_data_site_3=new Parameter();
			$param_data_site_3->KeyId="media";
			$param_data_site_3->Value=$sub_sites_array[3][0];
			$query_data->Parameter[9]=$param_data_site_3;
			$site_names .=" ". $sub_sites_array[3][0];
		}
		
		if (array_key_exists(4,$sub_sites_array)) 
		{
			$param_data_site_4=new Parameter();
			$param_data_site_4->KeyId="media";
			$param_data_site_4->Value=$sub_sites_array[4][0];
			$query_data->Parameter[10]=$param_data_site_4;
			$site_names .=" ". $sub_sites_array[4][0];
		}
	
		$query_report=new QueryReport();
		$query_report->query=$query_data;
		$result=null;
		
		try 
		{
			$result=$soap_client->QueryReport($query_report);
		} 
		catch (Exception $exception) // this is to continue after a timeout from comscore end
		{
			echo " Exception: request_comscore_dma_demo_report: site_names ". $site_names . " count_timeout_exceptions ".
					$this->count_timeout_exceptions . " geo_id ". $geo_id ;
			$this->count_timeout_exceptions++;
			$return_array=array();
			return $return_array;
		}
				
		if (property_exists($result->QueryReportResult, "Errors") && 
			property_exists($result->QueryReportResult->Errors, "Message") &&
			$result->QueryReportResult->Errors->Message=="Passed Parameter(KeyId='timePeriod') value is not valid.")
		{
			$this->comscore_local_dma_time_period_id=intval($this->comscore_time_period_id) - 1;
			return 1; // one is a special case handled to reduce the timeperiod by one
		}
		
		$return_array=array();
		
		if (property_exists($result->QueryReportResult, 'JobId')) 
		{	
			$return_array[0]=$result->QueryReportResult->JobId;
			$return_array[1]=$sub_sites_array;
		}
		
		return $return_array;// 0th element in this array is job id, 1st element is an array whch has 0 element=id and 1st element=url of sites for this job
	}
	
	///////// AREA D: GET LOCAL DEMO DATA
	// STEPS:
	// 1. Fetch all the sites that are tagged to a city from freq_sites_tagging_link table. **todo** get more sites populated in this table with ops manually
	// 2. For each site, fetch the latitude and longitude of the tagged city from REGION_DETAILS table
	// 3. For each latitude and longitude, use distance query to find out the comscore dmas (from comscore 100 dmas) closest to the city by sorting on distance. 
	// 4. **todo** ask ops to populate the correct latitude, longitude and population for the 100 dmas provided by comscore and update in comscore_geo_main table
	// 4. limit to closest 10 dmas within 500 miles radius.
	// 5. for the given site, pick the comscore_site_id from point 2. above and pick the comscore_dma_id from point 3. above and call the local demo api of comscore
	// 6. for returned data, insert/update in the comscore_demo_data table for the given comscore_site_id and comscore_demo_id
	// 7. repeat the calls for all the site and dma combination
	private function get_local_demo_data_from_comscore()
	{
		$this->count_comscore_calls_demo=0;
		$this->count_comscore_calls_demo_success=0;
		$this->count_comscore_calls_demo_fail=0;
		$this->count_comscore_calls_demo_fail_perm=0;
		$this->count_comscore_calls_demo_no_job_id=0;
		
		$soap_client=$this->comscore_php_soap_common->get_cs_soap_client($this->COMSCORE_LOCAL_DMA_WSDL_URL);
		
		$sites_city_list=$this->comscore_integration_model->fetch_sites_city_data_for_comscore();
		$results_array=$this->process_sites_for_demo_data($sites_city_list, $soap_client, 1);
		$results_array['total_local_tagged_sites']=$this->comscore_integration_model->count_local_tagged_sites();
		
		// this method returns back an array with each row containing comscore_site_id, url and dma_id
		return $results_array;
	}
	
	///////// COMMON AREA D: METHODS USED ACROSS AREAS
	
	// this method pings comscore pingreport status api for a given job id and keeps on pinging in a loop until it is completed
	private function wait_till_job_completes_pingreportstatus($comscore_job_id, $client) 
	{
		$result_status=$this->COMSCORE_QUEUED;
		$wait_counter=0;
		
		while ($result_status==$this->COMSCORE_QUEUED || $result_status==$this->COMSCORE_PROCESSING)
		{	
			$ping_report_status=new PingReportStatus();
			$ping_report_status->jobId=$comscore_job_id;
			 
			try
			{
				$result=$client->PingReportStatus($ping_report_status);
				$result_status=$result->PingReportStatusResult->Status;
				if ($result_status==$this->COMSCORE_COMPLETED)
					break;
			} 
			catch (Exception $exception) // this is to continue this loop after ignoring a timeout exception
			{
				echo " Exception: wait_till_job_completes_pingreportstatus: comscore_job_id ". $comscore_job_id . " count_timeout_exceptions ".
						$this->count_timeout_exceptions ;
				$this->count_timeout_exceptions++;
			}
			sleep(1);
			
			if ($wait_counter > 60) // we will try for one minute and will return failure
			{
				$result_status=$this->COMSCORE_FAILED;
				break;
			}
			$wait_counter++;
		}
		return $result_status;
	}
	
	// this is the method that calls the demo api for comscore. it accepts an array with upto comscore site ids for 5 sites.
	private function load_comscore_time_period_id() 
	{
		$soap_client=$this->comscore_php_soap_common->get_cs_soap_client($this->COMSCORE_KEYMEASURES_WSDL_URL);
		
		$param_data=new Parameter();
		$param_data->KeyId="geo";
		$param_data->Value="840";
		
		$param_data1=new Parameter();
		$param_data1->KeyId="loc";
		$param_data1->Value="0";
		
		$param_data2=new Parameter();
		$param_data2->KeyId="timeType";
		$param_data2->Value="1";
	
		$query_data=new Query();
		$query_data->Parameter=array();
		$query_data->Parameter[0]=$param_data;
		$query_data->Parameter[1]=$param_data1;
		$query_data->Parameter[2]=$param_data2;
		
		$discover_param=new DiscoverParameterValues();
		$discover_param->parameterId="timePeriod";
		$discover_param->query=$query_data;
		
		$result=$soap_client->DiscoverParameterValues($discover_param);
		
		$this->comscore_time_period_id=$result->DiscoverParameterValuesResult->EnumValue[0]->Id;
		$this->time_period_name_comscore=$result->DiscoverParameterValuesResult->EnumValue[0]->Value;
		$this->comscore_local_dma_time_period_id=$this->comscore_time_period_id;
	}
		
	///////// COMMON AREA E: METHODS USED ACROSS AREAS. SMALL UTIL FUNCTIONS 
	// this is used to help send email
	private function flatten_message_array_for_email($array_var)
	{
		return implode("\n", $array_var);
	}
	
	// input is url, output is removed special characters like *
	private function cleanse_url($url)
	{
		return preg_replace('/[\*]+/', '', (strtolower($url)));
	}
	
	// input is url, output returns boolean if the url is having a period as 4th last or 3rd last character
	private function is_valid_url($url)
	{
		return (strpos($url,'.') !==false && (substr($url, -3,1)=='.' || substr($url, -4,1)=='.'));
	}
	
	// common method to send email
	private function send_email($subject, $message, $body_type='html')
	{
		$message_header=$subject." <br>Errors: " . $this->count_timeout_exceptions."<br><br>";
		$message_header.=$this->cli_data_processor_common->get_environment_message_with_time('html');
		
		$from="TTD Daily <noreply@frequence.com>";
		$to="Tech Logs <tech@frequence.com>";
		//$to="Amit<amit.dar@frequence.com>";
		$message=$message_header . $message;
		$curl_variable=curl_init();
		curl_setopt($curl_variable, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl_variable, CURLOPT_USERPWD, 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4');
		curl_setopt($curl_variable, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_variable, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl_variable, CURLOPT_URL, 'https://api.mailgun.net/v2/mg.brandcdn.com/messages');
		curl_setopt(
			$curl_variable,
			CURLOPT_POSTFIELDS,
			array(	'from'=> $from,
					'to'=> $to,
					'subject'=> $subject,
					$body_type=> $message
			)
		);
		$result=curl_exec($curl_variable);
		curl_close($curl_variable);
	}
}

?>
