<?php

class tradedesk_model extends CI_Model
{
	private $max_tries = 3;

	private $base_cpm = 1.0;
	private $max_cpm = 3.0;
	private	$base_rtg_cpm = 3.0;
	private $max_rtg_cpm = 7.0;

	private $worst_case_mc_cpm = 2.00;
	private $target_mc_cpm = 1.00;
	private $worst_case_rtg_cpm = 5.00;
	private $target_rtg_cpm = 2.90;

	private $ttd_base_url;
	private $ttd_partner_id;
	private $ttd_industry_category_id;
	private $ttd_create_logo_url;

	private $prod_sitelist_ids= array("oi3nejm", "k54d186");
	private $prod_pre_roll_sitelist_ids = array("o3me2y7i","1xxrh516","j7e9iwhg","fhvyjrj1","nvfuzeoi");
	private $sandbox_sitelist_ids = array();
	private $sandbox_pre_roll_sitelist_ids = array();

	private $prod_av_geo_list = array(array("Id"=>"bylkuhptwe", "Adjustment"=>1.0));
	private $sandbox_av_geo_list = array();

	private $active_sitelist = array();
	private $pre_roll_sitelists = array();
	private $av_geo_list = array();

	private $display_adgroup_types = array("pc", "mobile_320", "mobile_no_320", "tablet", "rtg");
	private $pre_roll_adgroup_types = array("pre_roll", "rtg_pre_roll");

	public function __construct()
	{
		$this->load->database();
		$this->load->helper(array('tradedesk_helper', 'mailgun'));
		$this->load->library('tank_auth');
		$this->ttd_base_url = $this->config->item('ttd_base_url');
		$this->ttd_v3_base_url = $this->config->item('ttd_v3_base_url');
		$this->ttd_partner_id = $this->config->item('ttd_partner_id');
		$this->ttd_industry_category_id = $this->config->item('ttd_industry_category_id');
		$this->ttd_create_logo_url = $this->config->item('ttd_create_logo_url');

		if(strpos($this->ttd_base_url, 'apisb'))
		{
			$this->active_sitelist = $this->sandbox_sitelist_ids;
			$this->pre_roll_sitelists = $this->sandbox_pre_roll_sitelist_ids;
			$this->av_geo_list = $this->sandbox_av_geo_list;
		}
		else
		{
			$this->active_sitelist = $this->prod_sitelist_ids;
			$this->pre_roll_sitelists = $this->prod_pre_roll_sitelist_ids;
			$this->av_geo_list = $this->prod_av_geo_list;
		}
	}

	//Method used to send requests to the td server
	//make_request($url to send request to, post data, access token from td used to verify if the request is allowed)
	//returns json string response
	private function make_request($url, $post_data, $access_token)
	{
	 $request = curl_init();
	 curl_setopt($request, CURLOPT_URL, $url);
	 curl_setopt($request, CURLOPT_POST, 1);
	 curl_setopt($request, CURLOPT_VERBOSE, 0);
	 curl_setopt($request, CURLOPT_HEADER, 0);
	 curl_setopt($request, CURLOPT_HTTPHEADER,  array('TTD-Auth: '.$access_token ,'Content-Type: application/json'));
	 curl_setopt($request, CURLOPT_POSTFIELDS, $post_data);
	 curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
	 $result = curl_exec($request);
	 return $result;
	}

	//Method used to send update requests to the td server via PUT.
	//returns json string response
	private function make_update_request($url, $post_data, $access_token)
	{
	 $request = curl_init();
	 curl_setopt($request, CURLOPT_URL, $url);
	 curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'PUT');
	 curl_setopt($request, CURLOPT_VERBOSE, 0);
	 curl_setopt($request, CURLOPT_HEADER, 0);
	 curl_setopt($request, CURLOPT_HTTPHEADER,  array('TTD-Auth: '.$access_token ,'Content-Type: application/json'));
	 curl_setopt($request, CURLOPT_POSTFIELDS, $post_data);
	 curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
	 $result = curl_exec($request);
	 return $result;
	}

	//Method that makes a request to grab data
	//retrieve_data(url to send requst to, access token)
	//returns json string response
	private function retrieve_data($url, $access_token)
	{
	 $request = curl_init();
	 curl_setopt($request, CURLOPT_URL, $url);
	 curl_setopt($request, CURLOPT_VERBOSE, 0);
	 curl_setopt($request, CURLOPT_HEADER, 0);
	 curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
	 curl_setopt($request, CURLOPT_HTTPHEADER,  array('TTD-Auth: '.$access_token ,'Content-Type: application/json'));
	 $result = curl_exec($request);
	 return $result;
	}

	//Method used to retrieve the tradedesk access token
	//(login name, login's password, amount of time token will remain valid in minutes)
	//returns token string
	public function get_access_token($token_timeout = 480, $api_version = '2')
	{
		$ttd_data_prefix = ($api_version === '2' ? 'ttd' : "ttd_v{$api_version}");
		$access_token = $this->session->userdata("{$ttd_data_prefix}_token");
		$token_generation_time = $this->session->userdata("{$ttd_data_prefix}_timestamp");
		if($access_token === false || $token_generation_time === false || (strtotime('now') - $token_generation_time > ($token_timeout-3)*60))
		{
			$connect_url = $this->ttd_base_url.'authentication';
			$post_data = json_encode(array(
						"Login"=>$this->config->item('ttd_login'),
						"Password"=>$this->config->item('ttd_password'),
						"TokenExpirationInMinutes"=>$token_timeout
						));
			$get_token = curl_init();
			curl_setopt($get_token, CURLOPT_URL, $connect_url);
			curl_setopt($get_token, CURLOPT_POST, 1);
			curl_setopt($get_token, CURLOPT_VERBOSE, 0);
			curl_setopt($get_token, CURLOPT_HEADER, 0);
			curl_setopt($get_token, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($get_token, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($get_token, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($get_token);
			curl_close($get_token);
			$decoded = json_decode($result);
			$access_token = $decoded->Token;
			$this->session->set_userdata("{$ttd_data_prefix}_token", $access_token);
			$this->session->set_userdata("{$ttd_data_prefix}_timestamp", strtotime("now"));
		}
		return $access_token;
	}

	//Method used to make a GET request to get information regarding something from TTD
	public function get_info($access_token, $item, $item_id)
	{
	    $connect_url = $this->ttd_base_url.$item."/".$item_id;
	    $result = $this->retrieve_data($connect_url, $access_token);
	    return $result;
	}

	public function get_info_v3($access_token,$item,$item_id)
	{
		$connect_url = $this->ttd_v3_base_url.$item."/".$item_id;
		$result = $this->retrieve_data($connect_url, $access_token);

		return $result;
	}
	//Get all advertisers for the current partner ID (Which is pretty much always ours)
	public function get_all_advertisers($access_token)
	{
	    $error_counter = 0;
	    $return_value = false;
	    while($error_counter < $this->max_tries && $return_value === false)
	    {
		$adgroup_data = $this->get_info($access_token, "advertisers", $this->ttd_partner_id);
		$decoded = json_decode($adgroup_data);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->Result;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		$connect_url = $this->ttd_base_url. "advertisers/".$this->ttd_partner_id;
		parse_response_error_email($decoded, $connect_url, $access_token);
	    }
	    return $return_value;
	}

	public function get_all_campaigns_by_advertiser($access_token, $advertiser_id)
	{
	    return json_decode($this->get_info($access_token, "campaigns", $advertiser_id));
	}

	public function get_all_tags_by_advertiser($access_token, $advertiser_id)
	{
	    return json_decode($this->get_info($access_token, "trackingtags", $advertiser_id));
	}

	public function get_all_data_groups_by_advertiser($access_token, $advertiser_id)
	{
	    return json_decode($this->get_info($access_token, "datagroups", $advertiser_id));
	}

	public function get_all_creatives_by_advertiser($access_token, $advertiser_id)
	{
	    return json_decode($this->get_info($access_token, "creatives", $advertiser_id));
	}

	public function get_all_adgroups_by_campaign($access_token, $campaign_id)
	{
	    return json_decode($this->get_info($access_token, "adgroups", $campaign_id));
	}

	public function get_all_adgroups_by_vl_campaign($access_token, $campaign_id)
	{
	    return json_decode($this->get_info($access_token, "adgroups", $this->get_ttd_campaign_by_campaign($campaign_id)));
	}

	//Method used to determine if an advertiser's name already exists in TD
	public function is_advertiser_in_trade_desk($advertiser_id)
	{
		$sql = "SELECT ttd_adv_id FROM Advertisers WHERE id = ".$advertiser_id." AND ttd_adv_id IS NOT NULL";
		$query = $this->db->query($sql);
		if ($query->num_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function get_td_advertiser_id($advertiser_id)
	{
		$sql = "SELECT ttd_adv_id FROM Advertisers WHERE id = ".$advertiser_id." AND ttd_adv_id IS NOT NULL";
		$query = $this->db->query($sql);
		if ($query->num_rows() > 0)
		{
			return $query->row()->ttd_adv_id;
		}
		else
		{
			return false;
		}
	}

	public function is_campaign_in_trade_desk($campaign_id)
	{
		$sql = "SELECT ttd_campaign_id FROM Campaigns WHERE id = ".$campaign_id." AND ttd_campaign_id IS NOT NULL";
		$query = $this->db->query($sql);
		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		else
		{
			return false;
		}
	}

	public function is_av_campaign_in_trade_desk($campaign_id)
	{
		$sql = "SELECT ttd_av_id FROM Campaigns WHERE id = ".$campaign_id." AND ttd_av_id IS NOT NULL";
		$query = $this->db->query($sql);
		if ($query->num_rows() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	//Method to make an advertiser, only needs token and name.
	public function create_advertiser($access_token, $advertiser_name, $advertiser_id)
	{
		//sanitize the advertiser name before posting to ttd
		$advertiser_name = $this->sanitize_string_for_ttd($advertiser_name);
		$logo_url = $this->ttd_create_logo_url.$advertiser_name;
		$advertiser_website = $this->get_advertiser_website($advertiser_id);
		$connect_url = $this->ttd_v3_base_url.'advertiser';
		$request_array = array(
			"PartnerId"=>$this->ttd_partner_id,
			"AdvertiserId"=>$advertiser_id,
			"AdvertiserName"=>$advertiser_name,
			"CurrencyCodeId"=>"USD",
			"AttributionClickLookbackWindowInSeconds"=>2592000,
			"AttributionImpressionLookbackWindowInSeconds"=>2592000,
			"ClickDedupWindowInSeconds"=>7,
			"ConversionDedupWindowInSeconds"=>600,
 			"DefaultRightMediaOfferTypeId"=>1,
 			"IndustryCategoryId"=>$this->ttd_industry_category_id,
 			"LogoURL"=>$logo_url
		);
		if (!filter_var($advertiser_website, FILTER_VALIDATE_URL) === false) {
			$request_array["DomainAddress"] = $advertiser_website;
		}
		$ad_post = json_encode($request_array);
		$error_counter = 0;
		$return_value = false;
		while($error_counter < $this->max_tries && $return_value == false)
		{
			$result = $this->make_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_value = $decoded->AdvertiserId;
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
			parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	/*DEPRECIATED Method to create an adgroup in tradedesk (Access token, adgroup's name, campaign's TD ID, Audience's TD ID, an array of creatives)
	public function create_adgroup_v2($access_token, $adgroup_name, $campaign_id, $audience_id, $creatives_array, $total_budget, $daily_budget, $total_impressions, $daily_impressions, $base_cpm, $max_cpm, $pacing, $site_list_fallthrough, $tech_array, $is_pre_roll = false)
	{
	    $connect_url = $this->ttd_base_url.'adgroup';
	    if($is_pre_roll)
	    {
			$sitelists = array_merge($this->active_sitelist, $this->pre_roll_sitelists);
	    }
	    else
	    {
			$sitelists = $this->active_sitelist;
	    }
	    $ad_post = json_encode(array(
					"AdGroupName"=>$adgroup_name,
					"AdGroupId"=>"",
					"CampaignId"=>$campaign_id,
					"IsEnabled"=>false,
					"RTBAttributes"=>array(
							    "AudienceId"=>$audience_id,
							    "CreativeIds"=>$creatives_array,
							    "SiteListIds"=>$sitelists,
							    "SiteListFallThroughAdjustment"=>$site_list_fallthrough,
							    "BelowFoldAdjustment"=>1.0,
							    "DeviceTypeAdjustments"=>$tech_array,
							    "AdFormatAdjustments"=>array(
											array("Id"=>"1", "Adjustment"=>1.0),
											array("Id"=>"15", "Adjustment"=>1.0),
											array("Id"=>"16", "Adjustment"=>1.0)
											),
							    "BudgetInUSDollars"=>$total_budget,
							    "BudgetInImpressions"=>$total_impressions,
							    "DailyBudgetInUSDollars"=>$daily_budget,
							    "DailyBudgetInImpressions"=>$daily_impressions,
							    "PacingEnabled"=>$pacing,
							    "BaseBidCPMInUSDollars"=>$base_cpm,
							    "MaxBidCPMInUSDollars"=>$max_cpm
							    )));
	    $error_counter = 0;
	    $return_value = false;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->AdGroupId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }

	    return $return_value;
	}
	*/

	public function create_adgroup($access_token, $adgroup_name, $campaign_id, $audience_id, $creatives_array, $total_budget, $daily_budget, $total_impressions, $daily_impressions, $base_cpm, $max_cpm, $pacing, $site_list_fallthrough, $tech_array, $is_pre_roll = false, $extra_sitelists = null)
	{
		$connect_url = $this->ttd_v3_base_url.'adgroup';
		if($is_pre_roll)
		{
			$sitelists = array_merge($this->active_sitelist, $this->pre_roll_sitelists);
		}
		else
		{
			$sitelists = $this->active_sitelist;
		}
		if($extra_sitelists)
		{
			$sitelists = array_merge($sitelists, $extra_sitelists);
		}
		$ad_post = json_encode(array(
			"AdGroupName" => $adgroup_name,
			"AdGroupId" => "",
			"CampaignId" => $campaign_id,
			"IsEnabled" => false,
			"IndustryCategoryId" => $this->ttd_industry_category_id,
			"RTBAttributes" => array(
				"BudgetSettings" => array(
					"Budget" => array(
						"Amount" => $total_budget,
						"CurrencyCode" => "USD"
					),
					"BudgetInImpressions" => $total_impressions,
					"DailyBudget" => array(
						"Amount" => $daily_budget,
						"CurrencyCode" => "USD"
					),
					"DailyBudgetInImpressions" => $daily_impressions,
					"PacingEnabled" => $pacing
				),
				"BaseBidCPM" => array(
					"Amount" => $base_cpm,
					"CurrencyCode" => "USD"
				),
				"MaxBidCPM" => array(
					"Amount" => $max_cpm,
					"CurrencyCode" => "USD"
				),
				"AudienceTargeting" => array(
					"AudienceId" => $audience_id
				),
				"CreativeIds" => $creatives_array,
				"AdFormatAdjustments"=>array(
					array("Id"=>"1", "Adjustment"=>1.0),
					array("Id"=>"15", "Adjustment"=>1.0),
					array("Id"=>"16", "Adjustment"=>1.0)
				),
				"SiteTargeting" => array(
					"SiteListIds"=>$sitelists,
					"SiteListFallThroughAdjustment"=>$site_list_fallthrough
				),
				"FoldTargeting" => array(
					"AboveFoldAdjustment" => 1.0,
					"BelowFoldAdjustment" => 1.0,
					"UnknownFoldAdjustment" => 1.0
				),
				"RenderingContextAdjustments" => array(
					"DefaultAdjustment" => 1.0,
					"Adjustments" => array(
						array("Id" => "InApp", "Adjustment" => 1.0),
						array("Id" => "MobileOptimizedWeb", "Adjustment" => 1.0),
						array("Id" => "Other", "Adjustment" => 1.0)
					)
				),
				"DeviceTypeAdjustments" => $tech_array
			)
		));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->AdGroupId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
		parse_response_error_email($decoded, $connect_url, $ad_post);
		}

		return $return_value;
	}

	public function create_av_adgroup($access_token, $adgroup_name, $campaign_id, $audience_id, $creatives_array)
	{
		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
			"AdGroupName"=>$adgroup_name,
			"AdGroupId"=>"",
			"CampaignId"=>$campaign_id,
			"IsEnabled"=>false,
			"RTBAttributes"=>array(
			"AboveFoldAdjustment"=>1.0,
			"BelowFoldAdjustment"=>0,
			"UnknownFoldAdjustment"=>0,
			"AdFormatAdjustments"=>array(
				array("Id"=>"1", "Adjustment"=>1.0),
				array("Id"=>"15", "Adjustment"=>1.0),
				array("Id"=>"16", "Adjustment"=>1.0)
			),
			"GeoSegmentAdjustments"=>$this->av_geo_list,
			"FrequencyCap"=>1.0,
			"FrequencyPeriodInMinutes"=>1.0,
			"BudgetInUSDollars"=>100,
			"BudgetInImpressions"=>20000,
			"DailyBudgetInUSDollars"=>1,
			"DailyBudgetInImpressions"=>5000,
			"PacingEnabled"=>false,
			"BaseBidCPMInUSDollars"=>5,
			"MaxBidCPMInUSDollars"=>7
		)));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->AdGroupId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
		parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function add_adgroup_to_db($adgroup_id, $campaign_id, $is_retargeting, $type)
	{
		$subproduct_types_id = 0; // Default Value
		$select_array = array("ttd_campaign_id"=>$campaign_id);
		$get_business_sql = "SELECT id FROM Campaigns WHERE ttd_campaign_id = ?";
		$query = $this->db->query($get_business_sql, $select_array);
		if ($query->num_rows() > 0)
		{
			$campaign_id = $query->row()->id;
		}
		else
		{
			echo "WELP";
			return false;
		}

		if($type == 'PC' || $type == 'Mobile 320' || $type == 'Mobile No 320' || $type == 'Tablet' || $type == 'RTG')
		{
			$subproduct_types_id = 1;
		}
		if($type == 'Pre-Roll' || $type == 'RTG Pre-Roll')
		{
			$subproduct_types_id = 2;
		}
		$insert_array = array($adgroup_id, $campaign_id, $subproduct_types_id, $is_retargeting, $type);
		$insert_adgroup_sql = "INSERT INTO AdGroups (ID, campaign_id, subproduct_type_id, Source, IsRetargeting, target_type) VALUES (?, ?, ?, 'TD', ?, ?)";
		$query = $this->db->query($insert_adgroup_sql, $insert_array);
		return true;
	}

	public function add_ttd_adgroup_to_freq_db($adgroup_id, $freq_campaign_id, $is_retargeting, $source, $type)
	{
		$insert_array = array($adgroup_id, $freq_campaign_id, $source, $is_retargeting, $type);
		$insert_adgroup_sql = "INSERT INTO AdGroups (ID,  campaign_id, Source, IsRetargeting, target_type) VALUES (?, ?, ?, ?, ?)";
		return $this->db->query($insert_adgroup_sql, $insert_array);
	}


	public function add_av_adgroup_to_db($adgroup_id, $campaign_id)
	{
		$select_array = array("ttd_campaign_id"=>$campaign_id);
		//echo $campaign_id;
		$get_business_sql = "SELECT id FROM Campaigns WHERE ttd_av_id = ?";
		$query = $this->db->query($get_business_sql, $select_array);
		//echo $query->num_rows();
		if ($query->num_rows() > 0)
		{
		//echo $campaign_id;
		$campaign_id = $query->row()->id;

		}
		else
		{
		return FALSE;
		}

		$insert_array = array("ID"=>$adgroup_id, "campaign_id"=>$campaign_id);
		$insert_adgroup_sql = "INSERT INTO AdGroups (ID,  campaign_id, Source, IsRetargeting) VALUES (?, ?, 'TDAV', 1)";
		$query = $this->db->query($insert_adgroup_sql, $insert_array);
		return true;
	}

	//Method used to create a campaign in tradedesk (Access token, name, TTD advertiser ID, the budget in USD, start date)
	public function create_campaign($access_token, $campaign_name, $advertiser_id, $budget, $impressions, $start_date, $hard_end_date)
	{
		//sanitize the campaign name before posting to ttd
		$campaign_name = $this->sanitize_string_for_ttd($campaign_name);
		$connect_url = $this->ttd_base_url.'campaign';
		$ad_post = json_encode(array(
			"CampaignName"=>$campaign_name,
			"AdvertiserId"=>$advertiser_id,
			"CampaignConversionReportingColumns"=>NULL,
			"BudgetInUSDollars"=>$budget,
			"BudgetInImpressions"=>$impressions,
			"StartDate"=>$start_date,
			"EndDate"=>$hard_end_date
		));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->CampaignId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
			parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function post_campaign($ad_post)
	{
		$access_token = $this->get_access_token();
		$connect_url = $this->ttd_base_url.'campaign';
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->CampaignId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
		parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function post_adgroup($ad_post)
	{
		$access_token = $this->get_access_token();
		$connect_url = $this->ttd_base_url.'adgroup';
		$error_counter = 0;
		$return_value = false;
		while($error_counter < $this->max_tries && $return_value == false)
		{
			$result = $this->make_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_value = $decoded->AdGroupId;
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
			parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}


	public function create_ip_targeting_list($access_token, $advertiser_id, $list_name, $ranges)
	{
		$connect_url = $this->ttd_base_url.'iptargetinglist';
		$ad_post = json_encode(array(
					"AdvertiserId"=>$advertiser_id,
					"IPTargetingDataName"=>$list_name,
					"IPTargetingRanges"=>$ranges
					));
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		echo $result;
		$decoded = json_decode($result);
		if((property_exists($decoded, 'Message')) && (strrpos($result, "error")))
		{
			parse_response_error_email($decoded,$connect_url,$ad_post);
			return false;
		}
		return $decoded->IPTargetingListId;
	}

	public function add_ip_targeting_list_to_adgroup($access_token, $ip_list_id, $adgroup_id)
	{

	}

	public function create_av_campaign($access_token, $campaign_name, $advertiser_id, $start_date, $end_date = null)
	{
	    //sanitize the campaign name before posting to ttd
	    $campaign_name = $this->sanitize_string_for_ttd($campaign_name);
	    $connect_url = $this->ttd_base_url.'campaign';
	    $ad_post = json_encode(array(
					"CampaignName"=>$campaign_name,
					"AdvertiserId"=>$advertiser_id,
					"CampaignConversionReportingColumns"=>NULL,
					"BudgetInUSDollars"=>200,
					"BudgetInImpressions"=>200000,
					"StartDate"=>$start_date,
					"EndDate"=>$end_date
					));
	    $error_counter = 0;
	    $return_value = FALSE;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->CampaignId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	//Method used to create a creative asset in tradedesk (access token, name of creative, TTD Advertiser id, tag for the ad, ad's height, ad's width)
	public function create_creative($access_token, $creative_name, $advertiser_id, $ad_tag, $ad_height, $ad_width, $landing_page)
	{
	    //sanitize the creative name before posting to ttd
	    $creative_name = $this->sanitize_string_for_ttd($creative_name);
	    $connect_url = $this->ttd_v3_base_url.'creative';
	    // $connect_url = $this->ttd_base_url.'creative';
	    $ad_post = json_encode(array(
					"CreativeName"=>$creative_name,
					"AdvertiserId"=>$advertiser_id,
					"ThirdPartyTagAttributes"=>array(
									"AdTag"=>$ad_tag,
									"Width"=>$ad_width,
									"Height"=>$ad_height,
									"RightMediaOfferTypeId"=>4,
									"LandingPageUrls"=>array($landing_page),
									"IsSecurable"=>true
								)
					));
	    $error_counter = 0;
	    $return_value = FALSE;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->CreativeId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	public function get_ad_choice_tag_by_version($v_id)
	{
		$sql = "
			SELECT
				w.ad_choices_tag as tag
			FROM
				cup_versions ver
				LEFT JOIN Campaigns c
					ON ver.campaign_id = c.id
				LEFT JOIN `Advertisers` a
					ON a.id = c.business_id
				LEFT JOIN users u
					ON a.sales_person = u.id
				LEFT JOIN wl_partner_details w
					ON u.partner_id = w.id
			WHERE
				w.ad_choices_tag IS NOT NULL AND
				ver.id = ?";
		$query = $this->db->query($sql, $v_id);
		if($query->num_rows() > 0)
		{
				return $query->row()->tag;
		}
		return FALSE;
	}

	//Method used to create a tag in traddesk. Defaults to a retargeting tag.
	public function create_tag($access_token, $tag_name, $advertiser_id, $landing_page, $is_conversion)
	{
		$ttd_tag_type_id = "Retargeting";
		if($is_conversion)
		{
		    $ttd_tag_type_id = "Conversion";
		}

		$connect_url = $this->ttd_v3_base_url.'trackingtag';
		$ad_post = json_encode(array(
					    "TrackingTagName"=>$tag_name,
					    "AdvertiserId"=>$advertiser_id,
					    "TrackingTagType"=>$ttd_tag_type_id,
					    "TrackingTagLocation"=>$landing_page
					    ));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		    $result = $this->make_request($connect_url, $ad_post, $access_token);
		    $decoded = json_decode($result);
		    if(!property_exists($decoded, 'Message'))
		    {
			$return_value = $decoded->TrackingTagId;
		    }
		    else
		    {
			$error_counter += 1;
		    }
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);

		}
		return $return_value;
	}

	public function get_tag_types($access_token)
	{
	    $connect_url = $this->ttd_base_url.'trackingtag';
	    $ad_post = json_encode(array(
					"TrackingTagName"=>$tag_name,
					"AdvertiserId"=>$advertiser_id,
					"TrackingTagTypeId"=>"2",
					"TrackingTagLocation"=>"http://google.com"
					));
	    $result = $this->make_request($connect_url, $ad_post, $access_token);
	    ////echo $result;
	    return $result;

	}

	//Method used to make a data group in tradedesk (Access token, data group's name, TTD advertiser ID, array of tags)
	public function create_data_group($access_token, $data_group_name, $advertiser_id, $tag_array)
	{
		if(strlen($data_group_name) > 64)
		{
			$data_group_name = substr($data_group_name, 0, 64);
		}

	    $connect_url = $this->ttd_base_url.'datagroup';
	    $ad_post = json_encode(array(
					"PartnerId"=>$this->ttd_partner_id,
					"DataGroupName"=>$data_group_name,
					"AdvertiserId"=>$advertiser_id,
					"FirstPartyDataIds"=>$tag_array
					));
	    $error_counter = 0;
	    $return_value = FALSE;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->DataGroupId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	//Method used to make an audience in tradedesk. (Access token, Audience Name, TTD advertiser ID, Array of datagroups)
	public function create_audience($access_token, $audience_name, $advertiser_id, $datagroup_array)
	{

		if(strlen($audience_name) > 64)
		{
			$audience_name = substr($audience_name, 0, 64);
		}

	    $connect_url = $this->ttd_base_url.'audience';
	    $ad_post = json_encode(array(
					"PartnerId"=>$this->ttd_partner_id,
					"AudienceName"=>$audience_name,
					"AudienceId"=>"",
					"AdvertiserId"=>$advertiser_id,
					"IncludedDataGroupIds"=>$datagroup_array
					));
	    $error_counter = 0;
	    $return_value = FALSE;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->AudienceId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	//Return DataIds for a given tag name and advertiser ID
	public function get_data_ids_by_tag_name_and_advertiser_id($access_token, $tag_name, $advertiser_id)
	{
	   $all_tags = $this->get_info($access_token, "data/firstparty", $advertiser_id);
	   //print $all_tags.'\n\n\n\n\n\n';
	   $results = json_decode($all_tags)->Result;
	   //$results = $decoded_tags["Result"];
	   $return_value = array();
	   foreach($results as $tag)
	   {
	      //echo "NAME:".$tag->FirstPartyDataName."<br>";
	      if ($tag->FirstPartyDataName == $tag_name)
	      {
		  //echo $tag->FirstPartyDataId;
		  array_push($return_value, $tag->FirstPartyDataId);
	      }
	   }
	   return $return_value;
	}

	//Method generates HTML tags, modeled after the ones already found in the tags table.
	public function get_tag_html($advertiser_id, $tag_id, $tag_type)
	{
	    $tag = FALSE;

	    if($tag_type == "2")
	    {
			$tag = "<iframe width=\"0\" height=\"0\" name=\"\" frameborder=\"0\" style=\"display:none;\" scrolling=\"no\" src=\"//insight.adsrvr.org/tags/".$advertiser_id."/".$tag_id."/iframe\" ></iframe>";
		}
	    else
	    {
			$tag = "<iframe width=\"0\" height=\"0\" name=\"\" frameborder=\"0\" style=\"display:none;\" scrolling=\"no\" src=\"//insight.adsrvr.org/tags/".$advertiser_id."/".$tag_id."/iframe\" ></iframe><img height=\"1\" width=\"1\" style=\"display:none; border-style:none;\" alt=\"\" src=\"//insight.adsrvr.org/track/evnt/?adv=".$advertiser_id."&ct=0:".$tag_id."&fmt=3\"/>";
	    }
	    return $tag;
	}

	//Method to make a tag name at random. Depreciated.
	public function make_tag_name($is_adverify)
	{
	    $name = "ADLOADER_";
	    if($is_adverify)
	    {
		$name .= "ADVERIFY_";
	    }

	    $library = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	    $size = strlen( $library );
	    for( $i = 0; $i < 6; $i++ ) {
		$name .= $library[ rand( 0, $size - 1 ) ];
	    }
	    return $name;
	}
	//Method to add tradedesk id to an advertiser.
	public function add_ttd_id_to_advertiser($advertiser_name, $ttd_id)
	{
	    $insert_array = array("ttd_adv_id"=>$ttd_id, "Name"=>$advertiser_name);
	    $sql = "UPDATE Advertisers SET ttd_adv_id = ? WHERE Name = ?";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();

	}

	public function add_ttd_id_to_advertiser_with_id($advertiser_id, $ttd_id)
	{
	    $insert_array = array($ttd_id, $advertiser_id);
	    $sql = "UPDATE Advertisers SET ttd_adv_id = ? WHERE id = ?";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();

	}

	public function add_ttd_id_to_campaign($campaign_id, $ttd_id)
	{
	    $insert_array = array("ttd_campaign_id"=>$ttd_id, "id"=>$campaign_id);
	    $sql = "UPDATE Campaigns SET ttd_campaign_id = ? WHERE id = ?";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();
	}

	//Add a TTD id to a creative's entry in VL database. Overwrites older TTD ids.
	public function add_ttd_id_to_creative($creative_id, $ttd_creative_id, $ad_server_type_id)
	{
		if(k_ad_server_type::is_valid_for_database($ad_server_type_id))
		{
		    $insert_array = array(
			    $ttd_creative_id,
			    $ad_server_type_id,
			    $creative_id
		    );


		    $sql = "
			    UPDATE
				    cup_creatives
			    SET
				    ttd_creative_id = ?,
				    published_ad_server = ?
			    WHERE
				    id = ?
		    ";
		    $query = $this->db->query($sql, $insert_array);
		    return $this->db->affected_rows();
		}
		else
		{
			// TODO: better error handling -scott
			die("invalid ad server type id: ".$ad_server_type_id." (Error #290873)");
			return 0;
		}
	}

	public function clear_ttd_id_from_unused_creatives($ad_version_id, $active_ad_server_type)
	{
		$ad_server_sql = '';
		switch($active_ad_server_type)
		{
			case k_ad_server_type::dfa_id:
				$ad_server_sql = '
					(ad_tag IS NULL)
				';
				break;
			case k_ad_server_type::fas_id:
				$ad_server_sql = '
					(ad_tag IS NULL)
				';
				break;
			case k_ad_server_type::adtech_id:
				$ad_server_sql = '
					(adtech_ad_tag IS NULL)
				';
				break;
			default:
				die("unhandled ad_server_type_id: ".$active_ad_server_type);
				$ad_server_sql = '
					0
				';
		}

		$sql = '
			UPDATE
				cup_creatives
			SET
				ttd_creative_id = ?,
				published_ad_server = ?
			WHERE
				version_id = ? AND
				'.$ad_server_sql.'
		';

		$bindings = array(
			NULL,
			NULL,
			$ad_version_id
		);

		$response = $this->db->query($sql, $bindings);
		return $response;
	}

	//Add an adverify campaign's id to a campaign's entry in the VL database
	public function add_ttd_av_id_to_campaign($campaign_id, $ttd_id)
	{
	    $insert_array = array("ttd_av_id"=>$ttd_id, "id"=>$campaign_id);
	    $sql = "UPDATE Campaigns SET ttd_av_id = ? WHERE id = ?";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();

	}

	//Returns true if the campaign has any adgroups in it.
	public function does_campaign_have_adgroup($campaign_id)
	{
	    $insert_array = array("c.id"=>$campaign_id);
	    $sql = "SELECT vl_id FROM AdGroups WHERE campaign_id = ? AND IsRetargeting = 1";
	    $query = $this->db->query($sql, $insert_array);
	    return $query->num_rows();
	}

	//Method used to determine if a campaign's advertiser is found in tradedesk.
	public function does_campaign_have_ttd_advertiser($campaign_id)
	{
	    return $this->get_ttd_advertiser_by_campaign($campaign_id);
	}


	//Get campaign id for a version.
	public function get_campaign_for_version($version_id)
	{
	    $sql =
	    "SELECT
	     	v.campaign_id as cid
	     FROM
	     	cup_versions v
	     WHERE v.id = ?";
	    $query = $this->db->query($sql, $version_id);
	    if($query->num_rows() > 0)
	    {
		return $query->row()->cid;
	    }
	    return false;
	}

	//Method that returns the ttd id of a campaign's advertiser
	public function get_ttd_advertiser_by_campaign($campaign_id)
	{
		$ttd_campaign_id = $this->get_ttd_campaign_by_campaign($campaign_id);
		if(!$ttd_campaign_id)
		{
			return false;
		}
		$access_token = $this->get_access_token();
		$error_counter = 0;
		$did_succeed = FALSE;
		while($error_counter < $this->max_tries && $did_succeed == FALSE)
		{
			$campaign_data = $this->get_info($access_token, "campaign", $ttd_campaign_id);
			$decoded = json_decode($campaign_data);
			if(!property_exists($decoded, 'Message'))
			{
				$did_succeed = true;
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "campaign/".$ttd_campaign_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}
		return $decoded->AdvertiserId;
	}

	public function get_ttd_campaign_by_campaign($campaign_id)
	{
	    $insert_array = array("c.id"=>$campaign_id);
	    $sql = "SELECT ttd_campaign_id FROM Campaigns WHERE id = ?";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		$td_id = $query->row()->ttd_campaign_id;
		return $td_id;
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function get_ttd_modify_timestamp_by_campaign($campaign_id)
	{
	    $insert_array = array("c.id"=>$campaign_id);
	    $sql = "SELECT ttd_daily_modify FROM Campaigns WHERE id = ? AND ttd_daily_modify > DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		$td_timestamp = $query->row()->ttd_daily_modify;
		return $td_timestamp;
	    }
	    else
	    {
		return FALSE;
	    }
	}


	//Get TTD adgroups for a campaign from the adgroups table.
	public function get_ttd_adgroups_by_campaign($campaign_id)
	{
		$insert_array = array("campaign_id"=>$campaign_id);
		$sql = "SELECT ID, target_type FROM AdGroups WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF')";
		$query = $this->db->query($sql, $insert_array);
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
		else
		{
			return FALSE;
		}
	}

		//Get TTD adgroups for a campaign from the adgroups table.
	public function get_all_ttd_adgroups_by_campaign($campaign_id)
	{
	    $insert_array = array("campaign_id"=>$campaign_id);
	    $sql = "SELECT ID, target_type, Source FROM AdGroups WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDAV' OR Source = 'TDGF')";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		return $query->result_array();
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function get_managed_ttd_adgroups_by_campaign($campaign_id)
	{
	    $sql = "SELECT ID, target_type FROM AdGroups WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF') AND IsRetargeting = 0";
	    $query = $this->db->query($sql, $campaign_id);
	    if ($query->num_rows() > 0)
	    {
		return $query->result_array();
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function get_retargeting_ttd_adgroups_by_campaign($campaign_id)
	{
	    $insert_array = array("campaign_id"=>$campaign_id);
	    $sql = "SELECT ID FROM AdGroups WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF') AND IsRetargeting = 1";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		return $query->result_array();
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function get_non_av_ttd_adgroups_by_campaign($campaign_id)
	{
	    $sql = "SELECT ID, target_type, ttd_daily_modify as modify_timestamp, IsRetargeting FROM AdGroups WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF')";
	    $query = $this->db->query($sql, $campaign_id);
	    if ($query->num_rows() > 0)
	    {
		return $query->result_array();
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function get_day_old_managed_ttd_adgroups_by_campaign($campaign_id)
	{
	    $insert_array = array("campaign_id"=>$campaign_id);
	    $sql = "SELECT ID FROM AdGroups WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF') AND IsRetargeting = 0 AND ttd_daily_modify > DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		return $query->result_array();
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function is_adgroup_day_old($adgroup_id)
	{
	    $insert_array = array("campaign_id"=>$adgroup_id);
	    $sql = "SELECT ID FROM AdGroups WHERE ID = ? AND (Source = 'TD' OR Source = 'TDGF') AND ttd_daily_modify > DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		return true;
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function is_campaign_day_old($campaign_id)
	{
	    $insert_array = array("campaign_id"=>$campaign_id);
	    $sql = "SELECT id FROM Campaigns WHERE id = ? AND ttd_daily_modify > DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		return true;
	    }
	    else
	    {
		return FALSE;
	    }
	}

	public function update_timestamp_for_campaign($campaign_id)
	{
	    $insert_array = array("id"=>$campaign_id);
	    $sql = "UPDATE Campaigns SET ttd_daily_modify = NOW() WHERE id = ?";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();
	}

	public function set_day_old_managed_ttd_adgroup_for_campaign($campaign_id)
	{
	    $insert_array = array("campaign_id"=>$campaign_id);
	    $sql = "UPDATE AdGroups SET ttd_daily_modify = NOW() WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF') AND IsRetargeting = 0";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();
	}
	public function set_day_old_retargeting_ttd_adgroup_for_campaign($campaign_id)
	{
	    $insert_array = array("campaign_id"=>$campaign_id);
	    $sql = "UPDATE AdGroups SET ttd_daily_modify = NOW() WHERE campaign_id = ? AND (Source = 'TD' OR Source = 'TDGF') AND IsRetargeting = 1";
	    $query = $this->db->query($sql, $insert_array);
	    return $this->db->affected_rows();
	}


	//Probably exists somewhere else. Method used to get the landing page from a campaign by ID.
	public function get_campaign_landing_page($campaign_id)
	{
	    $insert_array = array("c.id"=>$campaign_id);
	    $sql = "SELECT LandingPage FROM Campaigns WHERE id = ?";
	    $query = $this->db->query($sql, $insert_array);
	    if ($query->num_rows() > 0)
	    {
		$landing_page = $query->row()->LandingPage;
		return trim($landing_page);
	    }
	    else
	    {
		return FALSE;
	    }
	}

	//Method used to ad an audience's id to an adgroup.
	public function update_adgroup_audience($access_token, $adgroup_id, $audience_id)
	{
	     $connect_url = $this->ttd_base_url.'adgroup';
	    $ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"RTBAttributes"=>array("AudienceId"=>$audience_id)
					));
	    $error_counter = 0;
	    $return_value = FALSE;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_update_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->AdGroupId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	//Method adding an array of creatives to the given adgroup.
	public function update_adgroup_creatives($access_token, $adgroup_id, $creative_array, $overwrite = TRUE)
	{
	    $return_value = FALSE;

	    if(!$overwrite)
	    {
		$old_creative_array = FALSE;
		$error_counter = 0;

		while($error_counter < $this->max_tries)
		{
		    $adgroup_data = $this->get_info($access_token, "adgroup", $adgroup_id);
		    $decoded = json_decode($adgroup_data);
		    if(property_exists($decoded, 'Message'))
		    {
			$error_counter += 1;
		    }
		    else
		    {
			$old_creative_array = $decoded->RTBAttributes->CreativeIds;
			break;
		    }
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "adgroup/".$adgroup_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}
		$creative_array = array_merge($old_creative_array, $creative_array);
	    }

	    $return_value = $this->push_creative_id_array_to_adgroup($access_token, $adgroup_id, $creative_array);

	    return $return_value;
	}

	public function update_adgroup_daily_impression_target($access_token, $adgroup_id, $daily_imp_target, $daily_budget)
	{
	    $connect_url = $this->ttd_base_url.'adgroup';
	    $ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"RTBAttributes"=>array(
							    "DailyBudgetInImpressions"=>$daily_imp_target,
							    "DailyBudgetInUSDollars"=>$daily_budget
		    					    )
					));
	    $error_counter = 0;
	    $return_value = FALSE;
	    while($error_counter < $this->max_tries && $return_value == FALSE)
	    {
		$result = $this->make_update_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->AdGroupId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    if($error_counter == $this->max_tries)
	    {
		    parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	public function update_adgroup_enabled_flag_and_daily_impression_target($access_token, $adgroup_id, $is_enabled, $daily_imp_target, $daily_budget)
	{
		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"IsEnabled"=>$is_enabled,
					"RTBAttributes"=>array(
							    "DailyBudgetInImpressions"=>$daily_imp_target,
							    "DailyBudgetInUSDollars"=>$daily_budget)));
		$error_counter = 0;
	    $return_value = false;
	    while($error_counter < $this->max_tries && $return_value === false)
	    {
			$result = $this->make_update_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_value = $decoded->AdGroupId;
			}
			else
			{
				$error_counter += 1;
			}
	    }
	    if($error_counter == $this->max_tries)
	    {
		    parse_response_error_email($decoded, $connect_url, $ad_post);
	    }
	    return $return_value;
	}

	public function update_adgroup_details($access_token, $adgroup_id, $is_enabled, $impression_budget, $dollar_budget, $daily_impression_target, $daily_dollar_budget)
	{
		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"IsEnabled"=>$is_enabled,
					"RTBAttributes"=>array(
								"BudgetInUSDollars"=>$dollar_budget,
								"BudgetInImpressions"=>$impression_budget,
								"DailyBudgetInImpressions"=>$daily_impression_target,
								"DailyBudgetInUSDollars"=>$daily_dollar_budget
									)
					));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_update_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->AdGroupId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
			parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}
	public function update_adgroup_budgets($access_token, $adgroup_id, $impression_budget, $dollar_budget, $daily_impression_target, $daily_dollar_budget)
	{
		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"RTBAttributes"=>array(
								"BudgetInUSDollars"=>$dollar_budget,
								"BudgetInImpressions"=>$impression_budget,
								"DailyBudgetInImpressions"=>$daily_impression_target,
								"DailyBudgetInUSDollars"=>$daily_dollar_budget
								)
					));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_update_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->AdGroupId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
			parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function update_campaign_details($access_token, $campaign_id, $impression_budget, $dollar_budget, $cmp_ttd_daily_imp_cap, $start_date, $end_date)
	{
		$connect_url = $this->ttd_base_url.'campaign';
		$ad_post = json_encode(array(
					"CampaignId"=>$campaign_id,
					"BudgetInImpressions"=>$impression_budget,
					"BudgetInUSDollars"=>$dollar_budget,
					"DailyBudgetInImpressions"=>$cmp_ttd_daily_imp_cap,
					"StartDate"=>$start_date,
					"EndDate"=>$end_date
					));
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$result = $this->make_update_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $decoded->CampaignId;
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
		   parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function get_rtg_adgroup_by_campaign_id($campaign_id)
	{
		$insert_array = array("c.id"=>$campaign_id);
		$sql = "SELECT ID FROM AdGroups WHERE campaign_id = ? AND IsRetargeting = 1 AND (Source = 'TD' OR Source = 'TDGF')";
		$query = $this->db->query($sql, $insert_array);
		if ($query->num_rows() > 0)
		{
		return $query->row()->ID;
		}
		else
		{
		return FALSE;
		}
	}


	public function get_av_adgroup_by_campaign_id($campaign_id)
	{
		$insert_array = array("c.id"=>$campaign_id);
		$sql = "SELECT ID FROM AdGroups WHERE campaign_id = ? AND IsRetargeting = 1 AND Source = 'TDAV'";
		$query = $this->db->query($sql, $insert_array);
		if ($query->num_rows() > 0)
		{
		return $query->row()->ID;
		}
		else
		{
		return FALSE;
		}
	}

	public function get_campaign_and_advertiser_name_by_campaign_id($campaign_id)
	{
		$insert_array = array("c.id"=>$campaign_id);
		$sql = "SELECT c.Name as c_name, a.Name as a_name FROM Campaigns c JOIN Advertisers a on c.business_id = a.id WHERE c.id = ?";
		$query = $this->db->query($sql, $insert_array);
		if($query->num_rows() > 0)
		{
		return $query->row();
		}
		else
		{
		return FALSE;

		}
	}

	public function get_landing_page_by_campaign($campaign_id)
	{
		$insert_array = array("c.id"=>$campaign_id);
		$sql = "SELECT LandingPage FROM Campaigns WHERE id = ?";
		$query = $this->db->query($sql, $insert_array);
		if($query->num_rows() > 0)
		{
		return $query->row()->LandingPage;
		}
		else
		{
		return FALSE;

		}
	}
	public function are_campaign_dates_matching($campaign_id)
	{
		$result = array('start_match'=>false,'end_match'=>false,'error'=>true);

		$insert_array = array("c.id"=>$campaign_id);
		$sql = "SELECT hard_end_date, start_date, ttd_campaign_id FROM Campaigns WHERE id = ?";
		$query = $this->db->query($sql, $insert_array);
		if($query->num_rows() > 0)
		{
			$hard_end_date = $query->row()->hard_end_date;
			$start_date = $query->row()->start_date;
			$ttd_campaign_id = $query->row()->ttd_campaign_id;
			if($ttd_campaign_id == NULL)
			{
				$result['error'] = false;
				$result['start_match'] = true;
				$result['end_match'] = true;
			}
			else
			{
				$access_token = $this->get_access_token();
				$error_counter = 0;
				$did_succeed = FALSE;
				while($error_counter < $this->max_tries && $did_succeed == FALSE)
				{
					$campaign_data = $this->get_info($access_token, "campaign", $ttd_campaign_id);
					$decoded = json_decode($campaign_data);
					if(!property_exists($decoded, 'Message'))
					{
						$did_succeed = TRUE;
					}
					else
					{
						$error_counter += 1;
					}
				}
				if($error_counter == $this->max_tries)
				{
					return $result;

				}
				$result['error'] = false;
				if($decoded->EndDate == NULL)
				{
					$ttd_date = NULL;
				}
				else
				{
					$ttd_date = substr($decoded->EndDate, 0, strpos($decoded->EndDate, "T"));
				}

				if($decoded->StartDate == NULL)
				{
					$ttd_start_date = NULL;
				}
				else
				{
					$ttd_start_date = substr($decoded->StartDate, 0, strpos($decoded->StartDate, "T"));
				}

				if(strtotime($hard_end_date) == strtotime($ttd_date))
				{
					$result['end_match'] = true;

				}


				if(strtotime($start_date) == strtotime($ttd_start_date))
				{
					$result['start_match'] = true;

				}
			}
		}
		return $result;
	}

	public function set_vl_campaign_end_date($c_id, $end_date)
	{
		if($end_date == NULL)
		{
			$insert_array = array("campaign_id"=>$c_id);
			$sql = "SELECT * FROM Campaigns WHERE hard_end_date IS NULL AND id = ?";
		}
		else
		{
			$insert_array = array("hard_end_date"=>$end_date, "campaign_id"=>$c_id);
			$sql = "SELECT * FROM Campaigns WHERE hard_end_date = ? AND id = ?";
		}


		$query = $this->db->query($sql, $insert_array);
		if($query->num_rows() > 0)
		{
			return 1; //It's that date already
		}
		if($end_date == NULL)
		{
			$insert_array = array("hard_end_date"=>$end_date, "campaign_id"=>$c_id);
			$sql = "UPDATE Campaigns SET hard_end_date = ? WHERE id = ?";
		}
		else
		{
			$sql = "UPDATE Campaigns SET hard_end_date = ? WHERE id = ?";
		}
		$query = $this->db->query($sql, $insert_array);
		$rows = $this->db->affected_rows();
		return $rows;
	}

	public function set_vl_campaign_dates( $c_id, $start, $end)
	{
		$insert_array = array($end, $start, $c_id);
		$sql = "UPDATE Campaigns SET hard_end_date = ?, start_date = ? WHERE id = ?";
		return $this->db->query($sql, $insert_array);
	}

	public function get_version_adset_name_and_version_number($version_id)
	{
		$sql = "SELECT
			v.version AS v_number,
			v.variation_name AS variation_name,
			ads.name AS adset_name
			FROM
			cup_versions AS v
			LEFT JOIN
			cup_adsets ads
			ON (v.adset_id = ads.id)
			WHERE
			v.id = ?";

		$query = $this->db->query($sql, $version_id);
		if($query->num_rows() > 0)
		{
		return $query->row();
		}
		else
		{
		return FALSE;
		}
	}
	public function determine_ttd_tech_parameters($type)
	{
		$tech_array = false;
		switch($type)
		{
		case "mobile":
			$tech_array = array("DefaultAdjustment"=>0.0, "Adjustments"=>array(array("Id"=>"Mobile", "Adjustment"=>1.0)));
			break;
		case "tablet":
			$tech_array = array("DefaultAdjustment"=>0.0, "Adjustments"=>array(array("Id"=>"Tablet", "Adjustment"=>1.0)));
			break;
		case "pc":
			$tech_array = array("DefaultAdjustment"=>0.0, "Adjustments"=>array(array("Id"=>"PC", "Adjustment"=>1.0)));
			break;
		case "none":
			$tech_array = array("DefaultAdjustment"=>1.0, "Adjustments"=>array());
			break;
		default:
			$tech_array = false;
			break;
		}
		return $tech_array;

	}

	public function determine_ttd_extra_sitelists($type)
	{
		if(strpos($this->ttd_base_url, 'apisb'))
		{
			return null; //No sitelists in sandbox API - Prevents the data backups from causing problems on sandbox-using environments
		}

		$get_extra_sitelists_query = 
		"SELECT
			ttd_sitelist_id
		FROM
			ttd_extra_adgroup_sitelists
		WHERE
			adgroup_target_type = ?
		";

		$get_extra_sitelists_result = $this->db->query($get_extra_sitelists_query, $type);
		if($get_extra_sitelists_result == false || $get_extra_sitelists_result->num_rows() < 1)
		{
			return null;
		}

		$sitelist_result = $get_extra_sitelists_result->result_array();
		$sitelists = array();
		foreach($sitelist_result AS $sitelist)
		{
			$sitelists[] = $sitelist['ttd_sitelist_id'];
		}
		return $sitelists;
	}

	public function update_timestamp_for_adgroup($adgroup_id)
	{
		$sql = "UPDATE AdGroups SET ttd_daily_modify = NOW() WHERE ID = ?";
		$query = $this->db->query($sql, $adgroup_id);
		return $this->db->affected_rows();
	}

	public function determine_weights_for_adgroups($adgroups)
	{
		$ratios = array();
		$budget_numbers = array();
		$sum = 0;
		$access_token = $this->get_access_token();

		foreach($adgroups as $adgroup)
		{
		$error_counter = 0;
		$did_succeed = FALSE;
		while($error_counter < $this->max_tries && $did_succeed == FALSE)
		{
			$adgroup_data = $this->get_info($access_token, "adgroup", $adgroup['ID']);
			$decoded = json_decode($adgroup_data);
			if(!property_exists($decoded, 'Message'))
			{
				$did_succeed = TRUE;
			}
			else
			{
			$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
			$connect_url = $this->ttd_base_url. "adgroup/".$adgroup['ID'];
			parse_response_error_email($decoded, $connect_url, $access_token);
			return false;
		}
		if($adgroup['target_type'] == NULL)
		{
			//determine what the target_type will be
			$adgroup['target_type'] = $this->determine_target_type($decoded->AdGroupName);
		}
		if($adgroup['target_type'] != 'RTG' && $adgroup['target_type'] != 'Pre-roll' && $adgroup['target_type'] != "Custom Ignored")
		{

			$budget_numbers[$adgroup['ID']] = $decoded->RTBAttributes->DailyBudgetInImpressions;
			$sum += $budget_numbers[$adgroup['ID']];
		}
		else if($adgroup['target_type'] == 'RTG')
		{
			$ratios[$adgroup['ID']] = 0.12;
		}

		}
		foreach($budget_numbers as $adgroup_id => $num_impressions)
		{
			$ratios[$adgroup_id] = $num_impressions/$sum;
		}
		return $ratios;

	}

	private function determine_target_type($adgroup_ttd_name)
	{
		if(strpos($adgroup_ttd_name, " - Managed") || strpos($adgroup_ttd_name, " - PC")  )
		{
		return "PC";
		}
		if(strpos($adgroup_ttd_name, " - RTG"))
		{
		return "RTG";
		}
		if($adgroup_ttd_name == "Mobile" || $adgroup_ttd_name == "Mobile No 320" || strpos($adgroup_ttd_name, " - Mobile no 320") )
		{
		return "Mobile No 320";
		}
		if($adgroup_ttd_name == "Mobile 320" || strpos($adgroup_ttd_name, " - Mobile 320") )
		{
		return "Mobile 320";
		}
		if($adgroup_ttd_name == "Tablet" || $adgroup_ttd_name == "Tablet Only" || strpos($adgroup_ttd_name, " - Tablet") )
		{
		return "Tablet";
		}
		return "Custom Ignored";
	}

	public function determine_target_type_for_adgroup_id($adgroup_id)
	{
		$access_token = $this->get_access_token();
		$error_counter = 0;
		$return_value = FALSE;
		while($error_counter < $this->max_tries && $return_value == FALSE)
		{
		$adgroup_data = $this->get_info($access_token, "adgroup", $adgroup_id);
		$decoded = json_decode($adgroup_data);
		if(!property_exists($decoded, 'Message'))
		{
			$return_value = $this->determine_target_type($decoded->AdGroupName);
		}
		else
		{
			$error_counter += 1;
		}
		}
		if($error_counter == $this->max_tries)
		{
			$connect_url = $this->ttd_base_url. "adgroup/".$adgroup_id;
			parse_response_error_email($decoded, $connect_url, $access_token);
		}
		return $return_value;
	}

	public function calculate_budget_numbers($start_date, $end_date, $target_kimpressions, $budget_weight_array, $is_pre_roll = false,
		$first_flight_start_date, $first_flight_end_date, $first_flight_impressions) //Used to take $adgroups as parameter
	{

		$budget_numbers = $this->get_ttd_budget_numbers();
		if($budget_numbers == FALSE)
		{
			return FALSE;
		}
		if($budget_weight_array != NULL)
		{
			if(isset($budget_weight_array['daily_weights']))
			{
				if($is_pre_roll)
				{
					$budget_numbers['ttd_daily_target_weight_pre_roll'] = $budget_weight_array['daily_weights']['pre_roll_weight'];
					$budget_numbers['ttd_daily_target_weight_rtg_pre_roll'] = $budget_weight_array['daily_weights']['rtg_pre_roll_weight'];
				}
				else
				{
					$budget_numbers['ttd_daily_target_weight_pc'] = $budget_weight_array['daily_weights']['pc_weight'];
					$budget_numbers['ttd_daily_target_weight_mobile_320'] = $budget_weight_array['daily_weights']['mobile_320_weight'];
					$budget_numbers['ttd_daily_target_weight_mobile_no_320'] = $budget_weight_array['daily_weights']['mobile_no_320_weight'];
					$budget_numbers['ttd_daily_target_weight_tablet'] = $budget_weight_array['daily_weights']['tablet_weight'];
					$budget_numbers['ttd_daily_target_weight_rtg'] = $budget_weight_array['daily_weights']['rtg_weight'];
				}
			}
		}


		$days_to_end_date = 28;
		$buffer = $budget_numbers['budget_buffer'];
		$impression_leakage_buffer = $budget_numbers['impression_leakage_buffer'];
		$mystery_y = 12;

		$days_to_end_date = $this->get_distance_to_hard_end_date($start_date,$end_date);
		$days_to_end_date_first_flight = $this->get_distance_to_hard_end_date($start_date,$first_flight_end_date);
		$end_date = format_end_date($end_date);
		$mystery_y = 1;

		$start_date = format_start_date($start_date);

		//MATH
		$adgroup_budgets = array();
		$adgroup_types = array();
		if($is_pre_roll)
		{
			$adgroup_types = $this->pre_roll_adgroup_types;
		}
		else
		{
			$adgroup_types = $this->display_adgroup_types;
		}

		$blended_impression_ratio = 0;
		$blended_worst_case_cpm = 0;
		foreach($adgroup_types as $adgroup_type)
		{
			$blended_impression_ratio += $budget_numbers['ttd_daily_target_weight_'.$adgroup_type];
			$blended_worst_case_cpm += ($budget_numbers['tolerable_cpm_max_'.$adgroup_type] * $budget_numbers['ttd_daily_target_weight_'.$adgroup_type]);
		}

		$blended_worst_case_cpm = $blended_worst_case_cpm/$blended_impression_ratio;
		$total_impressions = (1+$buffer)*$target_kimpressions*(1+$impression_leakage_buffer)*$blended_impression_ratio * $mystery_y;
		$total_dollars = $total_impressions * $blended_worst_case_cpm / 1000;

		foreach($adgroup_types as $adgroup_type)
		{
			$adgroup_budgets[$adgroup_type.'_adgroup']['total_impressions'] = $total_impressions*$budget_numbers['ttd_budget_weight_'.$adgroup_type];
			$adgroup_budgets[$adgroup_type.'_adgroup']['total_budget'] = $adgroup_budgets[$adgroup_type.'_adgroup']['total_impressions'] * $budget_numbers['tolerable_cpm_max_'.$adgroup_type] / 1000;

			$adgroup_budgets[$adgroup_type.'_adgroup']['daily_impressions'] = ($first_flight_impressions*(1+$impression_leakage_buffer)/$days_to_end_date_first_flight)*$budget_numbers['ttd_daily_target_weight_'.$adgroup_type];
			$adgroup_budgets[$adgroup_type.'_adgroup']['daily_budget'] = $adgroup_budgets[$adgroup_type.'_adgroup']['daily_impressions']*$budget_numbers['tolerable_cpm_max_'.$adgroup_type]/1000;

			if($budget_numbers['ttd_daily_target_weight_'.$adgroup_type] == 0)
			{
				$adgroup_budgets[$adgroup_type.'_adgroup'] = $this->get_zeroed_adgroup($adgroup_budgets[$adgroup_type.'_adgroup']);
			}
			$adgroup_budgets[$adgroup_type.'_adgroup']['base_bid'] = (float)$budget_numbers['ttd_ad_group_base_bid_'.$adgroup_type];
			$adgroup_budgets[$adgroup_type.'_adgroup']['max_bid'] = (float)$budget_numbers['ttd_ad_group_max_bid_'.$adgroup_type];
		}

		$budgets['adgroup_budgets'] = $adgroup_budgets;
		$budgets['c_total_budget'] = $total_dollars;
		$budgets['c_total_impressions'] = $total_impressions;
		$budgets['c_end_date'] = $end_date;
		$budgets['c_start_date'] = $start_date;
	 	return $budgets;

	}


	// public function get_todays_date()
	// {
	//	$t = time();
	//	$x = $t+date("Z", $t);
	//	return strftime("%Y-%m-%dT00:00:00.0000+01:00", $x);
	// }



	public function get_distance_to_last_date_of_broadcast_month($start_date)
	{
		$now = new DateTime($start_date, new DateTimeZone('UTC'));
		$current_broadcast_month = new DateTime(get_broadcast_month($now) . '-01', new DateTimeZone('UTC'));
		$next_broadcast_month = date_add($current_broadcast_month, date_interval_create_from_date_string("1 month"));
		$last_date = date_add(get_broadcast_start_date($next_broadcast_month->format('n'), $next_broadcast_month->format('Y')), date_interval_create_from_date_string('-1 day'));
		$num_days = date_diff($now->setTime(0,0,0), $last_date)->days;

		if ($num_days == 0)
		{
			date_add($next_broadcast_month, date_interval_create_from_date_string('1 month'));
			$next_month = get_broadcast_start_date($next_broadcast_month->format('n'), $next_broadcast_month->format('Y'));
			date_add($next_month, date_interval_create_from_date_string('-1 day'));
			$num_days = date_diff($now->setTime(0,0,0), $last_date)->days;
		}

		return $num_days;
	}

	public function get_distance_to_last_date_of_month($start_date)
	{
		$start_time = strtotime($start_date);
		$your_date = strtotime(date("Y-m-t", strtotime($start_date)));
		$datediff = $start_time - $your_date;
		$date_distance = abs(floor($datediff/(60*60*24)));

		if($start_date == date("Y-m-t", strtotime($start_date)))
		{
			$your_date = strtotime(date("Y-m-t", strtotime("+1day",$start_time)));
			$datediff = $start_time - $your_date;
			$date_distance = abs(floor($datediff/(60*60*24)));
		}

		return $date_distance;
	}

	public function get_distance_to_hard_end_date($start_date,$hard_end_date)
	{
		$start_time = strtotime($start_date);
		$your_date = strtotime($hard_end_date);
		$datediff = $start_time - $your_date;
		$datediff_gen=abs(floor($datediff/(60*60*24)));
		if ($datediff_gen == 0)
 			$datediff_gen=1;
		return $datediff_gen;
	}

	public function get_num_monthly_cycles($hard_end_date)
	{
		$time_diff = date_diff(datetime::createfromformat('Y-m-d',date("Y-m-d", strtotime("now"))), datetime::createfromformat('Y-m-d',$hard_end_date));
		$num_cycles = ($time_diff->y*12+$time_diff->m)*($time_diff->invert==1? -1 : 1);
		return $num_cycles+1;
	}

	public function build_default_adgroup_set($access_token, $campaign_name, $ttd_campaign_id, $budget_numbers)
	{
		$return_value['err_msg'] = "";
		$return_value['success'] = TRUE;

		$pc_tech = $this->determine_ttd_tech_parameters("pc");
		$pc_extra_sitelists = $this->determine_ttd_extra_sitelists("PC");
		$pc_adgroupid = $this->create_adgroup($access_token, $campaign_name." - PC", $ttd_campaign_id, NULL, array(), $budget_numbers['pc_adgroup']['total_budget'], $budget_numbers['pc_adgroup']['daily_budget'], $budget_numbers['pc_adgroup']['total_impressions'], $budget_numbers['pc_adgroup']['daily_impressions'], $budget_numbers['pc_adgroup']['base_bid'], $budget_numbers['pc_adgroup']['max_bid'], true, 0.0, $pc_tech, false, $pc_extra_sitelists);

		$mobile_tech = $this->determine_ttd_tech_parameters("mobile");
		$mobile_320_extra_sitelists = $this->determine_ttd_extra_sitelists("Mobile 320");		
		$mobile_adgroupid = $this->create_adgroup($access_token, $campaign_name." - Mobile 320", $ttd_campaign_id, NULL, array(), $budget_numbers['mobile_320_adgroup']['total_budget'], $budget_numbers['mobile_320_adgroup']['daily_budget'], $budget_numbers['mobile_320_adgroup']['total_impressions'], $budget_numbers['mobile_320_adgroup']['daily_impressions'], $budget_numbers['mobile_320_adgroup']['base_bid'], $budget_numbers['mobile_320_adgroup']['max_bid'], true, 0.0, $mobile_tech, false, $mobile_320_extra_sitelists);
		
		$mobile_no_320_extra_sitelists = $this->determine_ttd_extra_sitelists("Mobile No 320");				
		$mobile_no_adgroupid = $this->create_adgroup($access_token, $campaign_name." - Mobile no 320", $ttd_campaign_id, NULL, array(), $budget_numbers['mobile_no_320_adgroup']['total_budget'], $budget_numbers['mobile_no_320_adgroup']['daily_budget'], $budget_numbers['mobile_no_320_adgroup']['total_impressions'], $budget_numbers['mobile_no_320_adgroup']['daily_impressions'], $budget_numbers['mobile_no_320_adgroup']['base_bid'], $budget_numbers['mobile_no_320_adgroup']['max_bid'], true, 0.0, $mobile_tech, false, $mobile_no_320_extra_sitelists);

		$tablet_tech = $this->determine_ttd_tech_parameters("tablet");
		$tablet_extra_sitelists = $this->determine_ttd_extra_sitelists("Tablet");				
		$tablet_adgroupid = $this->create_adgroup($access_token, $campaign_name." - Tablet", $ttd_campaign_id, NULL, array(), $budget_numbers['tablet_adgroup']['total_budget'], $budget_numbers['tablet_adgroup']['daily_budget'], $budget_numbers['tablet_adgroup']['total_impressions'], $budget_numbers['tablet_adgroup']['daily_impressions'], $budget_numbers['tablet_adgroup']['base_bid'], $budget_numbers['tablet_adgroup']['max_bid'], true, 0.0, $tablet_tech, false, $tablet_extra_sitelists);


		$no_tech = $this->determine_ttd_tech_parameters("none");
		$rtg_extra_sitelists = $this->determine_ttd_extra_sitelists("RTG");				
		$rtg_adgroupid = $this->create_adgroup($access_token, $campaign_name." - RTG", $ttd_campaign_id, NULL, array(), $budget_numbers['rtg_adgroup']['total_budget'], $budget_numbers['rtg_adgroup']['daily_budget'], $budget_numbers['rtg_adgroup']['total_impressions'], $budget_numbers['rtg_adgroup']['daily_impressions'], $budget_numbers['rtg_adgroup']['base_bid'], $budget_numbers['rtg_adgroup']['max_bid'], false, 0.0, $no_tech, false, $rtg_extra_sitelists);
		if($pc_adgroupid == FALSE || $mobile_adgroupid == FALSE || $mobile_no_adgroupid == FALSE || $tablet_adgroupid == FALSE || $rtg_adgroupid == FALSE)
		{
		$return_value['err_msg'] .= "Failed to create adgroup(s): ";
		$return_value['success'] = FALSE;
		if($pc_adgroupid == FALSE)
		{
			$return_value['err_msg'] .= "PC ";
		}
		if($mobile_adgroupid == FALSE)
		{
			$return_value['err_msg'] .= "Mobile-320 ";
		}
		if($mobile_no_adgroupid == FALSE)
		{
			$return_value['err_msg'] .= "Mobile-No-320 ";
		}
		if($tablet_adgroupid == FALSE)
		{
			$return_value['err_msg'] .= "Tablet ";
		}
		if($rtg_adgroupid == FALSE)
		{
			$return_value['err_msg'] .= "Retargeting ";
		}
		$return_value['err_msg'] .= ".";
		echo json_encode($return_value);
		return;
		}
		$pc_db_success = $this->add_adgroup_to_db($pc_adgroupid, $ttd_campaign_id, 0, 'PC');
		$mobile_db_success = $this->add_adgroup_to_db($mobile_adgroupid, $ttd_campaign_id, 0, 'Mobile 320');
		$mobile_no_db_success = $this->add_adgroup_to_db($mobile_no_adgroupid, $ttd_campaign_id, 0, 'Mobile No 320');
		$tablet_db_success = $this->add_adgroup_to_db($tablet_adgroupid, $ttd_campaign_id, 0, 'Tablet');
		$rtg_db_success = $this->add_adgroup_to_db($rtg_adgroupid, $ttd_campaign_id, 1, 'RTG');

		if(!$pc_db_success || !$mobile_db_success || !$mobile_no_db_success || !$tablet_db_success || !$rtg_db_success)
		{
			$return_value['err_msg'] .= "Failed to link TTD adgroup(s) to VL adgroup(s): ";
			$return_value['success'] = FALSE;
			if($pc_db_success == FALSE)
			{
				$return_value['err_msg'] .= "PC ";
			}
			if($mobile_db_success == FALSE)
			{
				$return_value['err_msg'] .= "Mobile-320 ";
			}
			if($mobile_no_db_success == FALSE)
			{
				$return_value['err_msg'] .= "Mobile-No-320 ";
			}
			if($tablet_db_success == FALSE)
			{
				$return_value['err_msg'] .= "Tablet ";
			}
			if($rtg_db_success == FALSE)
			{
				$return_value['err_msg'] .= "Retargeting ";
			}
			$return_value['err_msg'] .= ".";
		}
		return $return_value;
	}

	public function build_pre_roll_adgroup_set($access_token, $campaign_name, $ttd_campaign_id, $budget_numbers)
	{
		$return_value['err_msg'] = "";
		$return_value['success'] = TRUE;

		$no_tech = $this->determine_ttd_tech_parameters("none");
		$preroll_extra_sitelists = $this->determine_ttd_extra_sitelists("Pre-Roll");						
		$pre_roll_adgroupid = $this->create_adgroup($access_token, $campaign_name." - Pre-Roll", $ttd_campaign_id, NULL, array(), $budget_numbers['pre_roll_adgroup']['total_budget'], $budget_numbers['pre_roll_adgroup']['daily_budget'], $budget_numbers['pre_roll_adgroup']['total_impressions'], $budget_numbers['pre_roll_adgroup']['daily_impressions'], $budget_numbers['pre_roll_adgroup']['base_bid'], $budget_numbers['pre_roll_adgroup']['max_bid'], true, 0.0, $no_tech, true, $preroll_extra_sitelists);

		$preroll_rtg_extra_sitelists = $this->determine_ttd_extra_sitelists("RTG Pre-Roll");								
		$rtg_adgroupid = $this->create_adgroup($access_token, $campaign_name." - RTG Pre-Roll", $ttd_campaign_id, NULL, array(), $budget_numbers['rtg_pre_roll_adgroup']['total_budget'], $budget_numbers['rtg_pre_roll_adgroup']['daily_budget'], $budget_numbers['rtg_pre_roll_adgroup']['total_impressions'], $budget_numbers['rtg_pre_roll_adgroup']['daily_impressions'], $budget_numbers['rtg_pre_roll_adgroup']['base_bid'], $budget_numbers['rtg_pre_roll_adgroup']['max_bid'], false, 0.0, $no_tech, true, $preroll_rtg_extra_sitelists);

		if($pre_roll_adgroupid == FALSE || $rtg_adgroupid == FALSE)
		{
			$return_value['err_msg'] .= "Failed to create adgroup(s): ";
			$return_value['success'] = FALSE;
			if($pre_roll_adgroupid == FALSE)
			{
				$return_value['err_msg'] .= "Pre-Roll ";
			}
			if($rtg_adgroupid == FALSE)
			{
				$return_value['err_msg'] .= "Retargeting ";
			}
			$return_value['err_msg'] .= ".";
			echo json_encode($return_value);
			return;
		}
		$pre_roll_db_success = $this->add_adgroup_to_db($pre_roll_adgroupid, $ttd_campaign_id, 0, 'Pre-Roll');
		$rtg_db_success = $this->add_adgroup_to_db($rtg_adgroupid, $ttd_campaign_id, 1, 'RTG Pre-Roll');
		if(!$pre_roll_db_success || !$rtg_db_success)
		{
			$return_value['err_msg'] .= "Failed to link TTD adgroup(s) to VL adgroup(s): ";
			$return_value['success'] = FALSE;
			if($pre_roll_db_success == FALSE)
			{
				$return_value['err_msg'] .= "Pre-Roll ";
			}
			if($rtg_db_success == FALSE)
			{
				$return_value['err_msg'] .= "Retargeting ";
			}
		}
		return $return_value;
	}

	public function add_sitelist_id_to_all_adgroups_for_campaign($sitelist, $campaign_id)
	{
		$adgroups = $this->get_non_av_ttd_adgroups_by_campaign($campaign_id);
		if($adgroups == FALSE)
		{
			return FALSE;
		}

		$access_token = $this->get_access_token();
		foreach($adgroups as $adgroup)
		{
			$error_counter = 0;
			$site_list_array = FALSE;
			while($error_counter < $this->max_tries && $site_list_array == false)
			{
				$adgroup_data = $this->get_info($access_token, "adgroup", $adgroup['ID']);
				$decoded = json_decode($adgroup_data);
				if(!property_exists($decoded, 'Message'))
				{
					$site_list_array = $decoded->RTBAttributes->SiteListIds;
					break;
				}
				else
				{
					$error_counter += 1;
				}
			}
			if($error_counter == $this->max_tries)
			{
				$connect_url = $this->ttd_base_url. "adgroup/".$adgroup['ID'];
				parse_response_error_email($decoded, $connect_url, $access_token);
				return false;
			}
			array_push($site_list_array, $sitelist);
			$adgroup_id = $this->update_adgroup_sitelists($access_token, $adgroup['ID'], $site_list_array);
			if(!$adgroup_id)
			{
				return false;
			}
		}
		return TRUE;
	}

	public function update_adgroup_sitelists($access_token, $adgroup_id, $sitelists)
	{
		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"RTBAttributes"=>array("SiteListIds"=>$sitelists)
					));
		$error_counter = 0;
		$return_value = false;
		while($error_counter < $this->max_tries && $return_value == false)
		{
			$result = $this->make_update_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_value = $decoded->AdGroupId;
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function add_geo_id_to_all_adgroups_for_campaign($geo_id, $campaign_id)
	{
		$adgroups = $this->get_non_av_ttd_adgroups_by_campaign($campaign_id);
		if($adgroups == false)
		{
			return false;
		}

		$access_token = $this->get_access_token();
		foreach($adgroups as $adgroup)
		{
			$error_counter = 0;
			$geo_array = false;
			while($error_counter < $this->max_tries && $geo_array == false)
			{
			    $adgroup_data = $this->get_info($access_token, "adgroup", $adgroup['ID']);
			    $decoded = json_decode($adgroup_data);
			    if(!property_exists($decoded, 'Message'))
			    {
					$geo_array = $decoded->RTBAttributes->GeoSegmentAdjustments;
					break;
			    }
			    else
			    {
					$error_counter += 1;
			    }
			}
			if($error_counter == $this->max_tries)
			{
			    $connect_url = $this->ttd_base_url. "adgroup/".$adgroup['ID'];
			    parse_response_error_email($decoded, $connect_url, $access_token);
			    return false;
			}
			array_push($geo_array, array("Id"=>$geo_id, "Adjustment"=>1.0));
			$adgroup_id = $this->update_adgroup_geos($access_token, $adgroup['ID'], $geo_array);
			if(!$adgroup_id)
			{
					return false;
			}
		}
		return TRUE;
	}

	public function update_adgroup_geos($access_token, $adgroup_id, $sitelists)
	{
		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
					    "AdGroupId"=>$adgroup_id,
					    "RTBAttributes"=>array("GeoSegmentAdjustments"=>$sitelists)
					    ));
		$error_counter = 0;
		$return_value = false;
		while($error_counter < $this->max_tries && $return_value == false)
		{
		    $result = $this->make_update_request($connect_url, $ad_post, $access_token);
		    $decoded = json_decode($result);
		    if(!property_exists($decoded, 'Message'))
		    {
			$return_value = $decoded->AdGroupId;
		    }
		    else
		    {
			$error_counter += 1;
		    }
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function add_audience_to_adgroup($audience_id, $campaign_id, $tag_type)
	{
		//Get adgroup id
		if($tag_type == 0)
		{
			$adgroup_id = $this->get_rtg_adgroup_by_campaign_id($campaign_id);
		}
		else if ($tag_type == 1)
		{
			$adgroup_id = $this->get_av_adgroup_by_campaign_id($campaign_id);
		}
		else
		{
		    return false;
		}

		if($adgroup_id != false)
		{
			//update that boyeeee
			$access_token = $this->get_access_token();
			return $this->update_adgroup_audience($access_token, $adgroup_id, $audience_id);
		}
		return false;
	}

	public function get_ttd_budget_numbers()
	{
	    $sql = "SELECT * FROM ttd_budget_operands ORDER BY id DESC LIMIT 1";
	    $query = $this->db->query($sql);
	    if($query->num_rows() != 1)
	    {
		return false;
	    }
	    return $query->row_array();
	}
	private function get_zeroed_adgroup($adgroup)
	{
	    $adgroup['daily_budget'] = 0.01;
	    $adgroup['daily_impressions'] = 1;
	    return $adgroup;
	}

	public function does_version_exist_with_ttd_creatives_for_campaign($version_id, $campaign_id)
	{
	    $sql = "SELECT
			ver.id
		    FROM
		    cup_versions ver
		    JOIN cup_creatives cr
			ON (cr.version_id = ver.id)
		    JOIN cup_adsets ads
			on (ver.adset_id = ads.id)
		    WHERE (ver.id = ? OR ver.source_id = ?) AND ver.campaign_id = ? AND cr.ttd_creative_id IS NOT NULL
		    LIMIT 1";
	    $query = $this->db->query($sql,array($version_id, $version_id, $campaign_id));
	    if($query->num_rows() < 1)
	    {
		return false;
	    }
	    return TRUE;
	}

	public function get_ttd_ids_for_creatives_with_version_id_and_campaign($version_id, $campaign_id)
	{
	    $sql = "SELECT cr.ttd_creative_id
		    FROM cup_creatives cr
		    JOIN cup_versions v
			ON (v.id = cr.version_id)
		    JOIN cup_adsets ads
			ON (v.adset_id = ads.id)
		    WHERE (cr.version_id = ? OR v.source_id = ?)
		    AND ver.campaign_id = ?";
	    $query = $this->db->query($sql, array($version_id, $version_id, $campaign_id));
	    if($query->num_rows < 1)
	    {
		return false;
	    }

	    $creative_id_array = array();

	    $result_array = $query->result_array();
	    foreach($result_array as $row)
	    {
		array_push($creative_id_array, $row['ttd_creative_id']);
	    }
	    return $creative_id_array;
	}

	public function remove_creatives_from_campaign_with_ids($campaign_id, $delete_list)
	{
	    $return_value = false;
	    $old_creative_array = array();
	    $adgroups = $this->get_all_ttd_adgroups_by_campaign($campaign_id);
	    if($adgroups == false)
	    {
		    return false;
	    }

	    $access_token = $this->get_access_token();
	    foreach($adgroups as $adgroup)
	    {
		$error_counter = 0;
		while($error_counter < $this->max_tries)
		{
		    $adgroup_data = $this->get_info($access_token, "adgroup", $adgroup['ID']);
		    $decoded = json_decode($adgroup_data);
		    if(property_exists($decoded, 'Message'))
		    {
			$error_counter += 1;
		    }
		    else
		    {
			$old_creative_array = $decoded->RTBAttributes->CreativeIds;
			break;
		    }
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "adgroup/".$adgroup['ID'];
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}
		$new_creative_array = array_values(array_diff($old_creative_array, array_intersect($old_creative_array, $delete_list)));

		$update_result = $this->push_creative_id_array_to_adgroup($access_token, $adgroup['ID'], $new_creative_array);
		if($update_result == false)
		{
		    return false;
		}
	    }
	    return TRUE;
	}

	public function push_creative_id_array_to_adgroup($access_token, $adgroup_id, $creative_array)
	{
		$error_counter = 0;
		$return_value = false;

		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
					"AdGroupId"=>$adgroup_id,
					"RTBAttributes"=>array("CreativeIds"=>$creative_array)
					));
		while($error_counter < $this->max_tries && $return_value == false)
		{
			$result = $this->make_update_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_value = $decoded->AdGroupId;
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}

	public function clear_previous_generic_viewthrough_data($adgroup_ids, $report_date)
	{
		$adgroup_id_in_string = implode(',', array_fill(0, count($adgroup_ids), '?'));

		$bindings = array_merge(array($report_date), $adgroup_ids);
		$delete_cityrecords_sql =
		"	DELETE FROM
				CityRecords
			WHERE
				Date = ? AND
				AdGroupID IN ({$adgroup_id_in_string});
		";

		$delete_siterecords_sql =
		"	DELETE FROM
				SiteRecords
			WHERE
				Date = ? AND
				AdGroupID IN ({$adgroup_id_in_string});
		";

		$delete_report_cache_sql =
		"	DELETE FROM
				report_cached_adgroup_date
			WHERE
				date = ? AND
				adgroup_id IN ({$adgroup_id_in_string});
		";

		$city_result = $this->db->query($delete_cityrecords_sql, $bindings);
		$site_result = $this->db->query($delete_siterecords_sql, $bindings);
		$cached_result = $this->db->query($delete_report_cache_sql, $bindings);

		return $city_result && $site_result && $cached_result;
	}

	private function get_advertiser_id_from_adgroup_id($adgroup_id)
	{
		$adgroup_data = $this->get_adgroup($adgroup_id);
		if($adgroup_data['is_success'])
		{
			$campaign_id = $adgroup_data['ttd_adgroup_object']->CampaignId;
			$campaign_data = $this->get_campaign($campaign_id);
			if($campaign_data['is_success'])
			{
				return $campaign_data['ttd_campaign_object']->AdvertiserId;
			}
		}
		return false;
	}

	public function get_list_of_facebook_advertisers_and_adgroups()
	{
		$sql =
		"	SELECT
				ag.id AS adgroup_ids
			FROM
				AdGroups AS ag
			WHERE
				ag.target_type = 'TTD_FB';
		";
		$response = $this->db->query($sql);
		if($response->num_rows() > 0)
		{
			$adgroup_ids = array_column($response->result_array(), 'adgroup_ids');
			$advertiser_ids = array();
			foreach($adgroup_ids as $adgroup_id)
			{
				$adv_id = $this->get_advertiser_id_from_adgroup_id($adgroup_id);
				if($adv_id)
				{
					if(!array_key_exists($adv_id, $advertiser_ids))
					{
						$advertiser_ids[$adv_id] = array($adgroup_id);
					}
					else
					{
						$advertiser_ids[$adv_id][] = $adgroup_id;
					}
				}
			}
			return $advertiser_ids;
		}
		return false;
	}

	private function generate_facebook_cities_sql($lines_to_use, $report_date)
	{
		$cities_affected_fields = array(
			'AdGroupId',
			'City',
			'Region',
			'Date',
			'Impressions',
			'Clicks',
			'Cost',
			'post_click_conversion_1',
			'post_click_conversion_2',
			'post_click_conversion_3',
			'post_click_conversion_4',
			'post_click_conversion_5',
			'post_click_conversion_6',
			'post_impression_conversion_1',
			'post_impression_conversion_2',
			'post_impression_conversion_3',
			'post_impression_conversion_4',
			'post_impression_conversion_5',
			'post_impression_conversion_6'
		);

		$cities_affected_fields_string = implode(',', $cities_affected_fields);
		$insert_block_piece = '(' . implode(',', array_fill(0, count($cities_affected_fields), '?')) . ')';
		$insert_block = implode(',', array_fill(0, count($lines_to_use), $insert_block_piece));

		$cities_sql =
		"	INSERT INTO CityRecords
				({$cities_affected_fields_string})
			VALUES
				{$insert_block}
			ON DUPLICATE KEY UPDATE
				Impressions = Impressions + VALUES(Impressions),
				Clicks = Clicks + VALUES(Clicks),
				Cost = Cost + VALUES(Cost),
				post_click_conversion_1 = post_click_conversion_1 + VALUES(post_click_conversion_1),
				post_click_conversion_2 = post_click_conversion_2 + VALUES(post_click_conversion_2),
				post_click_conversion_3 = post_click_conversion_3 + VALUES(post_click_conversion_3),
				post_click_conversion_4 = post_click_conversion_4 + VALUES(post_click_conversion_4),
				post_click_conversion_5 = post_click_conversion_5 + VALUES(post_click_conversion_5),
				post_click_conversion_6 = post_click_conversion_6 + VALUES(post_click_conversion_6),
				post_impression_conversion_1 = post_impression_conversion_1 + VALUES(post_impression_conversion_1),
				post_impression_conversion_2 = post_impression_conversion_2 + VALUES(post_impression_conversion_2),
				post_impression_conversion_3 = post_impression_conversion_3 + VALUES(post_impression_conversion_3),
				post_impression_conversion_4 = post_impression_conversion_4 + VALUES(post_impression_conversion_4),
				post_impression_conversion_5 = post_impression_conversion_5 + VALUES(post_impression_conversion_5),
				post_impression_conversion_6 = post_impression_conversion_6 + VALUES(post_impression_conversion_6);
		";

		$bindings = array();
		foreach($lines_to_use as $line)
		{
			$bindings[] = $line['Ad Group Id'];
			$bindings[] = empty($line['City']) ? 'All other cities' : $line['City'];
			$bindings[] = empty($line['Region']) ? 'All other regions' : $line['Region'];
			$bindings[] = $report_date;
			$bindings[] = $line['Imps'];
			$bindings[] = $line['Clicks'];
			$bindings[] = $line['Advertiser Total Cost'];
			$bindings[] = $line['PC 1'];
			$bindings[] = $line['PC 2'];
			$bindings[] = $line['PC 3'];
			$bindings[] = $line['PC 4'];
			$bindings[] = $line['PC 5'];
			$bindings[] = $line['PC 6'];
			$bindings[] = $line['PI 1'];
			$bindings[] = $line['PI 2'];
			$bindings[] = $line['PI 3'];
			$bindings[] = $line['PI 4'];
			$bindings[] = $line['PI 5'];
			$bindings[] = $line['PI 6'];
		}

		return ['sql' => $cities_sql, 'bindings' => $bindings];
	}

	private function generate_facebook_sites_sql($lines_to_use, $report_date)
	{
		$adgroups = array_flip(array_unique(array_column($lines_to_use, 'Ad Group Id')));
		array_walk($adgroups, function(&$a, $key) use ($report_date){
			$a = array(
				'AdGroupId' => $key,
				'Site' => 'facebook.com',
				'Date' => $report_date,
				'Impressions' => 0,
				'Clicks' => 0,
				'Base_Site' => 'facebook.com',
				'Cost' => 0.0,
				'post_click_conversion_1' => 0,
				'post_click_conversion_2' => 0,
				'post_click_conversion_3' => 0,
				'post_click_conversion_4' => 0,
				'post_click_conversion_5' => 0,
				'post_click_conversion_6' => 0,
				'post_impression_conversion_1' => 0,
				'post_impression_conversion_2' => 0,
				'post_impression_conversion_3' => 0,
				'post_impression_conversion_4' => 0,
				'post_impression_conversion_5' => 0,
				'post_impression_conversion_6' => 0
			);
		});
		$sites_affected_fields = array(
			'AdGroupId',
			'Site',
			'Date',
			'Impressions',
			'Clicks',
			'Base_Site',
			'Cost',
			'post_click_conversion_1',
			'post_click_conversion_2',
			'post_click_conversion_3',
			'post_click_conversion_4',
			'post_click_conversion_5',
			'post_click_conversion_6',
			'post_impression_conversion_1',
			'post_impression_conversion_2',
			'post_impression_conversion_3',
			'post_impression_conversion_4',
			'post_impression_conversion_5',
			'post_impression_conversion_6'
		);
		$sites_affected_fields_string = implode(',', $sites_affected_fields);
		$insert_block_piece = '(' . implode(',', array_fill(0, count($sites_affected_fields), '?')) . ')';
		$insert_block = implode(',', array_fill(0, count($adgroups), $insert_block_piece));

		$sites_sql =
		"	INSERT INTO SiteRecords
				({$sites_affected_fields_string})
			VALUES
				{$insert_block}
			ON DUPLICATE KEY UPDATE
				Impressions = Impressions + VALUES(Impressions),
				Clicks = Clicks + VALUES(Clicks),
				Base_Site = VALUES(Base_Site),
				Cost = Cost + VALUES(Cost),
				post_click_conversion_1 = post_click_conversion_1 + VALUES(post_click_conversion_1),
				post_click_conversion_2 = post_click_conversion_2 + VALUES(post_click_conversion_2),
				post_click_conversion_3 = post_click_conversion_3 + VALUES(post_click_conversion_3),
				post_click_conversion_4 = post_click_conversion_4 + VALUES(post_click_conversion_4),
				post_click_conversion_5 = post_click_conversion_5 + VALUES(post_click_conversion_5),
				post_click_conversion_6 = post_click_conversion_6 + VALUES(post_click_conversion_6),
				post_impression_conversion_1 = post_impression_conversion_1 + VALUES(post_impression_conversion_1),
				post_impression_conversion_2 = post_impression_conversion_2 + VALUES(post_impression_conversion_2),
				post_impression_conversion_3 = post_impression_conversion_3 + VALUES(post_impression_conversion_3),
				post_impression_conversion_4 = post_impression_conversion_4 + VALUES(post_impression_conversion_4),
				post_impression_conversion_5 = post_impression_conversion_5 + VALUES(post_impression_conversion_5),
				post_impression_conversion_6 = post_impression_conversion_6 + VALUES(post_impression_conversion_6);
		";

		foreach($lines_to_use as $line)
		{
			$line_adgroup_id = $line['Ad Group Id'];

			$adgroups[$line_adgroup_id]['Impressions'] += intval($line['Imps']);
			$adgroups[$line_adgroup_id]['Clicks'] += intval($line['Clicks']);
			$adgroups[$line_adgroup_id]['Cost'] += floatval($line['Advertiser Total Cost']);
			$adgroups[$line_adgroup_id]['post_click_conversion_1'] += intval($line['PC 1']);
			$adgroups[$line_adgroup_id]['post_click_conversion_2'] += intval($line['PC 2']);
			$adgroups[$line_adgroup_id]['post_click_conversion_3'] += intval($line['PC 3']);
			$adgroups[$line_adgroup_id]['post_click_conversion_4'] += intval($line['PC 4']);
			$adgroups[$line_adgroup_id]['post_click_conversion_5'] += intval($line['PC 5']);
			$adgroups[$line_adgroup_id]['post_click_conversion_6'] += intval($line['PC 5']);
			$adgroups[$line_adgroup_id]['post_impression_conversion_1'] += intval($line['PI 1']);
			$adgroups[$line_adgroup_id]['post_impression_conversion_2'] += intval($line['PI 2']);
			$adgroups[$line_adgroup_id]['post_impression_conversion_3'] += intval($line['PI 3']);
			$adgroups[$line_adgroup_id]['post_impression_conversion_4'] += intval($line['PI 4']);
			$adgroups[$line_adgroup_id]['post_impression_conversion_5'] += intval($line['PI 5']);
			$adgroups[$line_adgroup_id]['post_impression_conversion_6'] += intval($line['PI 5']);
		}

		$bindings = array();
		foreach($adgroups as $adgroup)
		{
			$bindings = array_merge($bindings, array_values($adgroup));
		}

		return ['sql' => $sites_sql, 'bindings' => $bindings];
	}

	private function generate_facebook_adsizes_sql($lines_to_use, $report_date)
	{
		$adgroups = array_flip(array_unique(array_column($lines_to_use, 'Ad Group Id')));
		array_walk($adgroups, function(&$a, $key) use ($report_date){
			$a = array(
				'AdGroupId' => $key,
				'Size' => '600x315',
				'Date' => $report_date,
				'Impressions' => 0,
				'Clicks' => 0,
				'Cost' => 0.0
			);
		});
		$adsizes_affected_fields = array(
			'AdGroupId',
			'Size',
			'Date',
			'Impressions',
			'Clicks',
			'Cost'
		);
		$adsizes_affected_fields_string = implode(',', $adsizes_affected_fields);
		$insert_block_piece = '(' . implode(',', array_fill(0, count($adsizes_affected_fields), '?')) . ')';
		$insert_block = implode(',', array_fill(0, count($adgroups), $insert_block_piece));

		$adsizes_sql =
		"	INSERT INTO report_ad_size_records
				({$adsizes_affected_fields_string})
			VALUES
				{$insert_block}
			ON DUPLICATE KEY UPDATE
				Impressions = VALUES(Impressions),
				Clicks = VALUES(Clicks),
				Cost = VALUES(Cost);
		";

		foreach($lines_to_use as $line)
		{
			$line_adgroup_id = $line['Ad Group Id'];

			$adgroups[$line_adgroup_id]['Impressions'] += intval($line['Imps']);
			$adgroups[$line_adgroup_id]['Clicks'] += intval($line['Clicks']);
			$adgroups[$line_adgroup_id]['Cost'] += floatval($line['Advertiser Total Cost']);
		}

		$bindings = array();
		foreach($adgroups as $adgroup)
		{
			$bindings = array_merge($bindings, array_values($adgroup));
		}
		return ['sql' => $adsizes_sql, 'bindings' => $bindings];
	}

	public function insert_facebook_data_in_db($lines_from_files_to_use, $report_date)
	{
		$cities_query_info = $this->generate_facebook_cities_sql($lines_from_files_to_use, $report_date);
		$sites_query_info = $this->generate_facebook_sites_sql($lines_from_files_to_use, $report_date);
		$adsizes_query_info = $this->generate_facebook_adsizes_sql($lines_from_files_to_use, $report_date);

		$city_result = $this->db->query($cities_query_info['sql'], $cities_query_info['bindings']);
		$num_city_rows = $this->db->affected_rows();
		$site_result = $this->db->query($sites_query_info['sql'], $sites_query_info['bindings']);
		$num_site_rows = $this->db->affected_rows();
		$size_result = $this->db->query($adsizes_query_info['sql'], $adsizes_query_info['bindings']);
		$num_size_rows = $this->db->affected_rows();

		return array(
			'is_success' => $city_result && $site_result && $size_result,
			'city_rows' => $num_city_rows,
			'site_rows' => $num_site_rows,
			'size_rows' => $num_size_rows
		);
	}

	public function get_db_stats_for_facebook_data($report_date, $affected_adgroups)
	{
		$return_array = array(
			'num_city_impressions' => 0,
			'num_site_impressions' => 0,
			'num_size_impressions' => 0,
			'num_city_clicks' => 0,
			'num_site_clicks' => 0,
			'num_size_clicks' => 0,
			'num_city_viewthroughs' => 0,
			'num_site_viewthroughs' => 0
		);
		$adgroup_id_in_string = implode(',', array_fill(0, count($affected_adgroups), '?'));
		$bindings = array_merge(array($report_date, $report_date, $report_date), $affected_adgroups, $affected_adgroups, $affected_adgroups);
		$city_sql =
		"	SELECT
				SUM(cr.Impressions) AS city_impressions,
				SUM(cr.Clicks) AS city_clicks,
				SUM(cr.post_click_conversion_1) + SUM(cr.post_click_conversion_2) + SUM(cr.post_click_conversion_3) +
				SUM(cr.post_click_conversion_4) + SUM(cr.post_click_conversion_5) + SUM(cr.post_click_conversion_6) +
				SUM(cr.post_impression_conversion_1) + SUM(cr.post_impression_conversion_2) + SUM(cr.post_impression_conversion_3) +
				SUM(cr.post_impression_conversion_4) + SUM(cr.post_impression_conversion_5) + SUM(cr.post_impression_conversion_6) AS city_viewthroughs
			FROM
				CityRecords AS cr
			WHERE
				cr.Date = ? AND
				cr.AdGroupID IN ({$adgroup_id_in_string});
		";
		$city_result = $this->db->query($city_sql, $bindings);
		$return_array['num_city_impressions'] = $city_result->row()->city_impressions;
		$return_array['num_city_clicks'] = $city_result->row()->city_clicks;
		$return_array['num_city_viewthroughs'] = $city_result->row()->city_viewthroughs;

		$site_sql =
		"	SELECT
				SUM(sr.Impressions) AS site_impressions,
				SUM(sr.Clicks) AS site_clicks,
				SUM(sr.post_click_conversion_1) + SUM(sr.post_click_conversion_2) + SUM(sr.post_click_conversion_3) +
				SUM(sr.post_click_conversion_4) + SUM(sr.post_click_conversion_5) + SUM(sr.post_click_conversion_6) +
				SUM(sr.post_impression_conversion_1) + SUM(sr.post_impression_conversion_2) + SUM(sr.post_impression_conversion_3) +
				SUM(sr.post_impression_conversion_4) + SUM(sr.post_impression_conversion_5) + SUM(sr.post_impression_conversion_6) AS site_viewthroughs
			FROM
				SiteRecords AS sr
			WHERE
				sr.Date = ? AND
				sr.AdGroupID IN ({$adgroup_id_in_string});
		";
		$site_result = $this->db->query($site_sql, $bindings);
		$return_array['num_site_impressions'] = $site_result->row()->site_impressions;
		$return_array['num_site_clicks'] = $site_result->row()->site_clicks;
		$return_array['num_site_viewthroughs'] = $site_result->row()->site_viewthroughs;

		$size_sql =
		"	SELECT
				SUM(si.Impressions) AS size_impressions,
				SUM(si.Clicks) AS size_clicks
			FROM
				report_ad_size_records AS si
			WHERE
				si.Date = ? AND
				si.AdGroupID IN ({$adgroup_id_in_string});
		";
		$size_result = $this->db->query($size_sql, $bindings);
		$return_array['num_size_impressions'] = $size_result->row()->size_impressions;
		$return_array['num_size_clicks'] = $size_result->row()->size_clicks;

		return $return_array;
	}

	public function get_hd_reports_of_advertiser_for_day($access_token, $advertiser_id, $report_day, $require_conversions = false)
	{
		$report_request_date = "{$report_day}T01:00:00+01:00";
		$error_counter = 0;
		$return_value['err_msg'] = '';
		$return_value['success'] = false;
		$return_value['result'] = array();
		$connect_url = $this->ttd_base_url . 'hdreports';

		$hd_report_post = json_encode(
			array(
				"AdvertiserId" => $advertiser_id,
				"ReportDateUTC" => $report_request_date
			)
		);

		while($error_counter < $this->max_tries)
		{
			$hd_report_data = $this->make_request($connect_url, $hd_report_post, $access_token);
			$decoded = json_decode($hd_report_data);
			if(property_exists($decoded, 'Message'))
			{
				$error_counter += 1;
			}
			else
			{
				$hd_report_list = $decoded->Result;
				break;
			}
		}
		if($error_counter == $this->max_tries)
		{
			$return_value['err_msg'] = $decoded->Message;
			//parse_response_error_email($decoded, $connect_url, $hd_report_post);	 Removed to prevent HDReport Uploader spitting out a bunch of emails 03/29/16 -Ryan
			return $return_value;
		}
		$has_conversions = false;
		foreach($hd_report_list as $idx => $report)
		{
			if($report->Duration != "OneDay" || $report->Scope == "Partner")
			{
				unset($hd_report_list[$idx]);
			}
			if($report->Type == "ConversionReport")
			{
				$has_conversions = true;
			}
		}

		if($require_conversions && !$has_conversions)
		{
			$return_value['err_msg'] = "No conversion files found for this advertiser. This isn't a fatal error.";
			return $return_value;
		}
		$return_value['result'] = $hd_report_list;
		$return_value['success'] = true;
		$return_value['conversions'] = $has_conversions;
		return $return_value;
	}

	public function update_campaign_conversion_reporting_columns($access_token, $ttd_campaign_id, $tag_id_list, $operation = "append")
	{
	    $error_counter = 0;
	    $return_value = false;

	    $col_count = 1;
	    $conversion_reporting_array = array();

	    if ($operation == "append")
	    {
		$error_counter = 0;
		$did_succeed = false;
		while($error_counter < $this->max_tries && $did_succeed == false)
		{
		    $campaign_data = $this->get_info($access_token, "campaign", $ttd_campaign_id);
		    $decoded = json_decode($campaign_data);
		    if(!property_exists($decoded, 'Message'))
		    {
			$did_succeed = true;
		    }
		    else
		    {
			$error_counter += 1;
		    }
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "campaign/".$ttd_campaign_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return $return_value;
		}
		$conversion_reporting_array = $decoded->CampaignConversionReportingColumns;
		$col_count = count($conversion_reporting_array)+1;

	    }

	    foreach($tag_id_list as $tag_id)
	    {
		array_push($conversion_reporting_array, array("TrackingTagId"=>$tag_id, "ReportingColumnId"=>$col_count));
		$col_count++;
	    }

	    if(count($conversion_reporting_array) > 5)
	    {
		return $return_value;
	    }

	    $connect_url = $this->ttd_base_url.'campaign';
	    $ad_post = json_encode(array(
					"CampaignId"=>$ttd_campaign_id,
					"CampaignConversionReportingColumns"=>$conversion_reporting_array
					));
	    while($error_counter < $this->max_tries && $return_value == false)
	    {
		$result = $this->make_update_request($connect_url, $ad_post, $access_token);
		$decoded = json_decode($result);
		if(!property_exists($decoded, 'Message'))
		{
		    $return_value = $decoded->CampaignId;
		}
		else
		{
		    $error_counter += 1;
		}
	    }
	    return $return_value;
	}


	public function get_campaign($ttd_campaign_id)
	{
		$return['is_success'] = false;
		$return['ttd_campaign_object'] = null;

		$access_token = $this->get_access_token();
		$error_counter = 0;

		while($error_counter < $this->max_tries && $return['is_success']== false)
		{
		    $campaign_data = $this->get_info($access_token, "campaign", $ttd_campaign_id);
		    $return['ttd_campaign_object'] = json_decode($campaign_data);
		    if(!property_exists($return['ttd_campaign_object'], 'Message'))
		    {
				$return['is_success'] = true;
		    }
		    else
		    {
				$error_counter += 1;
		    }
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "campaign/".$ttd_campaign_id;
		    parse_response_error_email($return['ttd_campaign_object'], $connect_url, $access_token);
		}
		return $return;
	}

	public function get_adgroup($ttd_adgroup_id)
	{
		$return['is_success'] = false;
		$return['ttd_adgroup_object'] = null;

		$access_token = $this->get_access_token();
		$error_counter = 0;

		while($error_counter < $this->max_tries && $return['is_success']== false)
		{
		    $adgroup_data = $this->get_info_v3($access_token, "adgroup", $ttd_adgroup_id);
		    $return['ttd_adgroup_object'] = json_decode($adgroup_data);
		    if(!property_exists($return['ttd_adgroup_object'], 'Message'))
		    {
				$return['is_success'] = true;
		    }
		    else
		    {
				$error_counter += 1;
		    }
		}
		return $return;
	}

	public function get_audience($audience_id)
	{
		$access_token = $this->get_access_token();
		$error_counter = 0;
		$return['is_success'] = false;
		$return['ttd_audience_data'] = NULL;

		while($error_counter < $this->max_tries && $return['is_success']== false)
		{
			$return['ttd_audience_data'] = json_decode($this->get_info_v3($access_token, "audience", $audience_id));
			if(property_exists($return['ttd_audience_data'], 'AudienceId'))
			{
				$return['is_success'] = true;
			}
			else
			{
				$error_counter += 1;
			}
		}
		return $return;
	}

	public function clear_raw_hdreport_tables()
	{
		$this->raw_db = $this->load->database('td_intermediate', true);
		$delete_geos_query = "TRUNCATE TABLE ttd_raw_viewthrough_cities";
		$delete_sites_query = "TRUNCATE TABLE ttd_raw_viewthrough_sites";
		$delete_video_query = "TRUNCATE TABLE ttd_raw_video";

		$clear_geos = $this->raw_db->query($delete_geos_query);
		$clear_sites = $this->raw_db->query($delete_sites_query);
		$clear_video = $this->raw_db->query($delete_video_query);
		if($clear_sites == false || $clear_geos == false || $clear_video == false)
		{
			return false;
		}
		$this->raw_db->close();
		return true;
	}

	public function translate_hd_report_from_directory_to_data_arrays($file_path, $type)
	{
		$downloaded_files = array_diff(scandir($file_path), array('.','..'));
		$tsv_data = array();

		if($type == "conversions")
		{
			$tsv_data['geo_data'] = array();
			$tsv_data['sites_data'] = array();
		}
		else if($type == "video")
		{
			$tsv_data['video_data'] = array();
		}
		else
		{
			return false;
		}

		foreach($downloaded_files as $file_name)
		{
			$tsv_type = "";
			$full_file_path = $file_path."/".$file_name;
			$file = fopen($full_file_path, 'r');
			if($file == false)
			{
				return false;
			}
			$header = null;
		while(($row = fgetcsv($file, 0, "\t")) != false)
		{
			if($header == null)
			{
				$header = $row;
				if($type == "conversions")
				{
					if(in_array("Metropolitan Area", $header))
					{
						$tsv_type = "geo";
					}
					else
					{
						$tsv_type = "sites";
					}
				}
				else if($type == "video")
				{
					$tsv_type = "video";
				}
				else
				{
					return false;
				}
			}
			else
			{
				$tsv_data_row = array_combine($header, $row);
				$has_data = false;

				if($type == "conversions")
				{
					$has_data = $this->tsv_row_has_conversions($tsv_data_row);
				}
				else if($type == "video")
				{
					$has_data = $this->tsv_row_has_video_data($tsv_data_row);
				}
				else
				{
					return false;
				}

				if($has_data)
				{
					$tsv_data[$tsv_type."_data"][] = $tsv_data_row;
				}
			}
		}
		fclose($file);
	    }
	    return $tsv_data;
	}

	private function tsv_row_has_conversions($tsv_row)
	{
	    $fields_to_check = array("PC", "PI");
	    $num_fields_per = 6;
	    $field_idx = 1;
	    while($field_idx <= $num_fields_per)
	    {
		foreach($fields_to_check as $field)
		{
		    if($tsv_row[$field." ".$field_idx] != "0")
		    {
			return true;
		    }
		}
		$field_idx++;
	    }
	    return false;
	}

	private function tsv_row_has_video_data($tsv_row)
	{
		$fields_to_check = array("VideoEventStart");
		foreach($fields_to_check as $field)
		{
			if($tsv_row[$field] != 0)
			{
				return true;
			}
		}
		return false;
	}

	public function transfer_tsv_rows_into_raw_for_date($tsv_data_rows, $report_date)
	{
		//Process geos
		$geos_result = $this->add_tsv_rows_for_report_type_to_raw_for_date($tsv_data_rows['geo_data'], "geo", $report_date);
		if($geos_result == false)
		{
			return false;
		}

		//Process sites
		$sites_result = $this->add_tsv_rows_for_report_type_to_raw_for_date($tsv_data_rows['sites_data'], "sites", $report_date);
		if($sites_result == false)
		{
			return false;
		}

		$video_result = $this->add_tsv_rows_for_report_type_to_raw_for_date($tsv_data_rows['video_data'], "video", $report_date);
		if($video_result == false)
		{
			return false;
		}
		return true;
	}

	private function add_tsv_rows_for_report_type_to_raw_for_date($tsv_data_rows, $tsv_type, $report_date)
	{
		$conversion_fields = "post_click_conversion_1, post_click_conversion_2, post_click_conversion_3,
							post_click_conversion_4, post_click_conversion_5, post_click_conversion_6,
							post_impression_conversion_1, post_impression_conversion_2, post_impression_conversion_3,
							post_impression_conversion_4, post_impression_conversion_5, post_impression_conversion_6";
		if($tsv_type == "geo")
		{
			$type_specific_fields = "city, region, ".$conversion_fields;
			$raw_table = "ttd_raw_viewthrough_cities";
		}
		else if ($tsv_type == "sites")
		{
			$type_specific_fields = "site, ".$conversion_fields;
			$raw_table = "ttd_raw_viewthrough_sites";
		}
		else if($tsv_type == "video")
		{
			$type_specific_fields = "start_count, 25_percent_count, 50_percent_count, 75_percent_count, 100_percent_count";
			$raw_table = "ttd_raw_video";
		}
		else
		{
			return false;
		}

		$first_value_set = false;
		$query_values = "";
		$insert_array = array();
		foreach($tsv_data_rows as $data_row)
		{
			if($first_value_set)
			{
				$query_values .= " ,";
			}
			$first_value_set = true;

			$insert_array[] = $data_row["Ad Group Id"];
			$insert_array[] = $report_date;
			if($tsv_type == "geo")
			{
				$query_values .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				$insert_array[] = $data_row["City"];
				$insert_array[] = $data_row["Region"];
			}
			else if ($tsv_type == "sites")
			{
				$query_values .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				$insert_array[] = $data_row["Site"];
			}
			else if($tsv_type == "video")
			{
				$query_values .= "(?,?,?,?,?,?,?)";
			}
			else
			{
				return false;
			}
			if($tsv_type == "sites" || $tsv_type == "geo")
			{
				$insert_array = array_merge($insert_array,
											array($data_row["PC 1"],
												$data_row["PC 2"],
												$data_row["PC 3"],
												$data_row["PC 4"],
												$data_row["PC 5"],
												$data_row["PC 6"],
												$data_row["PI 1"],
												$data_row["PI 2"],
												$data_row["PI 3"],
												$data_row["PI 4"],
												$data_row["PI 5"],
												$data_row["PI 6"])
												);
			}
			else if($tsv_type == "video")
			{
				$insert_array = array_merge($insert_array,
											array($data_row["VideoEventStart"],
												$data_row["VideoEventFirstQuarter"],
												$data_row["VideoEventMidpoint"],
												$data_row["VideoEventThirdQuarter"],
												$data_row["VideoEventComplete"])
											);
			}
			else
			{
				return false;
			}
	    }
	    if($query_values != "")
	    {
			$insert_raw_data_query = "INSERT INTO ".$raw_table."
					(adgroup_id,
					date,
					".$type_specific_fields.")
				VALUES
					".$query_values;

			$this->raw_db = $this->load->database('td_intermediate', true);
			$raw_insert_result = $this->raw_db->query($insert_raw_data_query, $insert_array);

			if($raw_insert_result == false)
			{
				return false;
			}
				$this->raw_db->close();
		}
		return true;
	}

	public function resolve_raw_data_mismatches()
	{
	    $return_value = array();
	    $return_value['geo_fixes'] = 0;
	    $return_value['site_fixes'] = 0;
	    $return_value['geos_fixed_list'] = array();
	    $return_value['sites_fixed_list'] = array();
	    $this->raw_db = $this->load->database('td_intermediate', TRUE);
	    //Find sites that aren't in siterecords
	    $find_mismatched_sites_query = "SELECT
						rt.*
					    FROM
						".$this->db->database.".SiteRecords AS sr
						RIGHT JOIN ttd_raw_viewthrough_sites AS rt
							ON sr.AdGroupID = rt.adgroup_id AND
							sr.base_site = rt.site
					    WHERE
					    sr.AdGroupID IS NULL";
	   $mismatched_sites_result = $this->raw_db->query($find_mismatched_sites_query);
	   if($mismatched_sites_result == false)
	   {
	       return false;
	   }
	   if($mismatched_sites_result->num_rows() > 0)
	   {
	       $site_fixes = array(); //array of adjusted rows, with all fields included to just update all of them
	       $mismatched_sites = $mismatched_sites_result->result_array();
	       foreach($mismatched_sites as $bad_site)
	       {
		  $orig_site_string = "(".$bad_site['adgroup_id'].") ".$bad_site['site'];
		  $got_fixed = false;
		  $bad_site['site'] = substr($bad_site['site'], strpos($bad_site['site'], '.')+1);
		  while(substr_count($bad_site['site'], ".") > 0)
		  {
		      $find_matching_new_site_query = "SELECT * FROM SiteRecords WHERE AdGroupID = ? AND Site = ?";
		      $search_array = array($bad_site['adgroup_id'], $bad_site['site']);
		      $matching_site_result = $this->db->query($find_matching_new_site_query, $search_array);
		      if($matching_site_result == false)
		      {
			  return false;
		      }
		      if($matching_site_result->num_rows() > 0)
		      {
			  $got_fixed = true;
			  array_push($site_fixes, $bad_site);
			  break;
		      }
		      $bad_site['site'] = substr($bad_site['site'], strpos($bad_site['site'], '.')+1);
		  }
		  if($got_fixed)
		  {
		      continue;
		  }
		  $bad_site['site'] = "All other sites";
		  array_push($site_fixes, $bad_site);
		  array_push($return_value['sites_fixed_list'], $orig_site_string." -> ".$bad_site['site']);
		  $return_value['site_fixes'] += 1;
	       }
	       //Push site_fixes to raw table
	       $fix_insert_array = array();
	       $update_raw_sites_query = "UPDATE ttd_raw_viewthrough_sites SET site = CASE ";
	       foreach($site_fixes as $site_fix)
	       {
		   $update_raw_sites_query .= " WHEN id = ? THEN ?";
		   array_push($fix_insert_array, $site_fix['id']);
		   array_push($fix_insert_array, $site_fix['site']);
	       }

	       $update_raw_sites_query .= "ELSE site END";
	       $update_raw_sites_result = $this->raw_db->query($update_raw_sites_query, $fix_insert_array);
	       if($update_raw_sites_result == false)
	       {
		   return false;
	       }
	   }

	   $find_mismatched_cities_query = "SELECT
						rt.*
					    FROM
						".$this->db->database.".CityRecords AS cr
						RIGHT JOIN ttd_raw_viewthrough_cities AS rt
						    ON cr.AdGroupID = rt.adgroup_id AND
						    cr.Date = rt.date AND
						    cr.Region = rt.region AND
						    cr.City = rt.city
					    WHERE
					    cr.AdGroupID IS NULL";
	    $mismatched_cities_result = $this->raw_db->query($find_mismatched_cities_query);
	    if($mismatched_cities_result == false)
	    {
		return false;
	    }
	    if($mismatched_cities_result->num_rows() > 0)
	    {
			$city_fixes = array();
			$mismatched_cities = $mismatched_cities_result->result_array();
			//Iterate through each result
			foreach($mismatched_cities as $bad_geo)
			{
			    $orig_geo_string = "(".$bad_geo['adgroup_id'].") ".$bad_geo['city'].", ".$bad_geo['region'];
			    //Look through mismatch list
			    $mismatch_geo_query = "SELECT new_string FROM report_viewthrough_identifier_fixes WHERE vt_type = 0 AND field_num = ? AND old_string = ?";
			    $mismatch_region_result = $this->db->query($mismatch_geo_query, array(0, $bad_geo['region']));
			    if($mismatch_region_result == false)
			    {
					return false;
			    }
			    if($mismatch_region_result->num_rows() > 0)
			    {
					$row = $mismatch_region_result->row_array();
					$bad_geo['region'] = $row['new_string'];
			    }
			    else
			    {
			    	//No need to change
					continue;
			    }

			    $mismatch_city_result = $this->db->query($mismatch_geo_query, array(1, $bad_geo['city']));
			    if($mismatch_city_result == false)
			    {
					return false;
			    }
			    if($mismatch_city_result->num_rows() > 0)
				{
					$row = $mismatch_region_result->row_array();
					$bad_geo['city'] = $row['new_string'];
				}
			    else
			    {
			    	//No need to change
					continue;
			    }
			    array_push($city_fixes, $bad_geo);
			    array_push($return_value['geos_fixed_list'], $orig_geo_string." -> ".$bad_geo['city'].", ".$bad_geo['region']);
			    $return_value['geo_fixes'] += 1;
			}
			$fix_insert_array = array();
			$update_raw_cities_query = "UPDATE ttd_raw_viewthrough_cities SET region = CASE ";
			foreach($city_fixes as $city_fix)
			{
			   $update_raw_cities_query .= " WHEN id = ? THEN ?";
			   array_push($fix_insert_array, $city_fix['id']);
			   array_push($fix_insert_array, $city_fix['region']);
			}

			$update_raw_cities_query .= "ELSE region END, city = CASE ";
			foreach($city_fixes as $city_fix)
			{
			   $update_raw_cities_query .= " WHEN id = ? THEN ?";
			   array_push($fix_insert_array, $city_fix['id']);
			   array_push($fix_insert_array, $city_fix['city']);
			}
			$update_raw_cities_query .= "ELSE city END";
			if(!empty($fix_insert_array))
			{
				$update_raw_cities_result = $this->raw_db->query($update_raw_cities_query, $fix_insert_array);
				if($update_raw_cities_result == false)
				{
			   	return false;
				}
			}
	    }
	    $this->raw_db->close();
	    return $return_value;
	}

	public function aggregate_viewthroughs_to_records()
	{
		$this->raw_db = $this->load->database('td_intermediate', true);

		$insert_sites_query =
		"	INSERT INTO
				SiteRecords (AdGroupID, Site, Date, Impressions, Clicks, Cost, Base_site,
						post_click_conversion_1,  post_click_conversion_2,  post_click_conversion_3,
						post_click_conversion_4,  post_click_conversion_5,  post_click_conversion_6,
						post_impression_conversion_1, post_impression_conversion_2, post_impression_conversion_3,
						post_impression_conversion_4, post_impression_conversion_5, post_impression_conversion_6)
				(SELECT
					adgroup_id,
					site,
					date,
					0,
					0,
					0.0,
					site,
					SUM(post_click_conversion_1),
					SUM(post_click_conversion_2),
					SUM(post_click_conversion_3),
					SUM(post_click_conversion_4),
					SUM(post_click_conversion_5),
					SUM(post_click_conversion_6),
					SUM(post_impression_conversion_1),
					SUM(post_impression_conversion_2),
					SUM(post_impression_conversion_3),
					SUM(post_impression_conversion_4),
					SUM(post_impression_conversion_5),
					SUM(post_impression_conversion_6)
					FROM {$this->raw_db->database}.ttd_raw_viewthrough_sites
					GROUP BY site, date, adgroup_id)
			ON DUPLICATE KEY UPDATE
				post_click_conversion_1 = VALUES(post_click_conversion_1),
				post_click_conversion_2 = VALUES(post_click_conversion_2),
				post_click_conversion_3 = VALUES(post_click_conversion_3),
				post_click_conversion_4 = VALUES(post_click_conversion_4),
				post_click_conversion_5 = VALUES(post_click_conversion_5),
				post_click_conversion_6 = VALUES(post_click_conversion_6),
				post_impression_conversion_1 = VALUES(post_impression_conversion_1),
				post_impression_conversion_2 = VALUES(post_impression_conversion_2),
				post_impression_conversion_3 = VALUES(post_impression_conversion_3),
				post_impression_conversion_4 = VALUES(post_impression_conversion_4),
				post_impression_conversion_5 = VALUES(post_impression_conversion_5),
				post_impression_conversion_6 = VALUES(post_impression_conversion_6)
				";
		$aggregate_sites_result = $this->db->query($insert_sites_query);
		if($aggregate_sites_result == false)
		{
			return false;
		}

		//Now do the same for cities
		$insert_cities_query =
		"	INSERT INTO
				CityRecords (AdGroupID, City, Region, Date, Impressions, Clicks, Cost,
						post_click_conversion_1,  post_click_conversion_2,  post_click_conversion_3,
						post_click_conversion_4,  post_click_conversion_5,  post_click_conversion_6,
						post_impression_conversion_1, post_impression_conversion_2, post_impression_conversion_3,
						post_impression_conversion_4, post_impression_conversion_5, post_impression_conversion_6)
				(SELECT
					adgroup_id,
					city,
					region,
					date,
					0,
					0,
					0.0,
					SUM(post_click_conversion_1),
					SUM(post_click_conversion_2),
					SUM(post_click_conversion_3),
					SUM(post_click_conversion_4),
					SUM(post_click_conversion_5),
					SUM(post_click_conversion_6),
					SUM(post_impression_conversion_1),
					SUM(post_impression_conversion_2),
					SUM(post_impression_conversion_3),
					SUM(post_impression_conversion_4),
					SUM(post_impression_conversion_5),
					SUM(post_impression_conversion_6)
					FROM {$this->raw_db->database}.ttd_raw_viewthrough_cities
					GROUP BY city, region, date, adgroup_id)
			ON DUPLICATE KEY UPDATE
				post_click_conversion_1 = VALUES(post_click_conversion_1),
				post_click_conversion_2 = VALUES(post_click_conversion_2),
				post_click_conversion_3 = VALUES(post_click_conversion_3),
				post_click_conversion_4 = VALUES(post_click_conversion_4),
				post_click_conversion_5 = VALUES(post_click_conversion_5),
				post_click_conversion_6 = VALUES(post_click_conversion_6),
				post_impression_conversion_1 = VALUES(post_impression_conversion_1),
				post_impression_conversion_2 = VALUES(post_impression_conversion_2),
				post_impression_conversion_3 = VALUES(post_impression_conversion_3),
				post_impression_conversion_4 = VALUES(post_impression_conversion_4),
				post_impression_conversion_5 = VALUES(post_impression_conversion_5),
				post_impression_conversion_6 = VALUES(post_impression_conversion_6)
				";
		$aggregate_cities_result = $this->db->query($insert_cities_query);
		if($aggregate_cities_result == false)
		{
			return false;
		}


		$insert_video_records_query = 'INSERT INTO
										report_video_play_records (AdGroupID, date, start_count, 25_percent_count, 50_percent_count, 75_percent_count, 100_percent_count)
										(SELECT
											adgroup_id,
											date,
											SUM(start_count),
											SUM(25_percent_count),
											SUM(50_percent_count),
											SUM(75_percent_count),
											SUM(100_percent_count)
										FROM '.$this->raw_db->database.'.ttd_raw_video
										GROUP BY
											adgroup_id, date)
										ON DUPLICATE KEY UPDATE
											start_count = VALUES(start_count),
											25_percent_count = VALUES(25_percent_count),
											50_percent_count = VALUES(50_percent_count),
											75_percent_count = VALUES(75_percent_count),
											100_percent_count = VALUES(100_percent_count)';
		$insert_video_records_result = $this->db->query($insert_video_records_query);
		if($insert_video_records_result == false)
		{
			return false;
		}

	    $this->raw_db->close();
	    return true;
	}

	public function does_data_exist_for_day($report_date)
	{
		$find_data_query = "SELECT * FROM CityRecords WHERE Date = ? LIMIT 1";
		$find_data_result = $this->db->query($find_data_query, $report_date);
		if($find_data_result == false || $find_data_result->num_rows() < 1)
		{
			return false;
		}
		return true;
	}

	public function get_conversion_count($type, $from_raw, $report_date = null)
	{
		$get_conversion_counts_query = "SELECT
						SUM(post_click_conversion_1)+SUM(post_click_conversion_2)+SUM(post_click_conversion_3)+
						SUM(post_click_conversion_4)+SUM(post_click_conversion_5)+SUM(post_click_conversion_6)+
						SUM(post_impression_conversion_1)+SUM(post_impression_conversion_2)+SUM(post_impression_conversion_3)+
						SUM(post_impression_conversion_4)+SUM(post_impression_conversion_5)+SUM(post_impression_conversion_6)
						AS total_conversions FROM ";
		switch($type)
		{
			case "geos":
				if($from_raw)
				{
					$get_conversion_counts_query .="ttd_raw_viewthrough_cities";
				}
				else
				{
					$get_conversion_counts_query .= "CityRecords WHERE Date = ?";
				}
				break;
			case "sites":
				if($from_raw)
				{
					$get_conversion_counts_query .= "ttd_raw_viewthrough_sites";
				}
				else
				{
					$get_conversion_counts_query .= "SiteRecords WHERE Date = ?";
				}
				break;
			default:
				return false;
				break;
		}
		if($from_raw)
		{
			$this->raw_db = $this->load->database('td_intermediate', true);
			$conversion_count_result = $this->raw_db->query($get_conversion_counts_query);
		}
		else
		{
			if($report_date == null)
			{
				return false;
			}
			$conversion_count_result = $this->db->query($get_conversion_counts_query, $report_date);
		}
		if($conversion_count_result == false)
		{
			return false;
		}
		$return_value = false;
		if($conversion_count_result->num_rows() > 0)
		{
			$row = $conversion_count_result->row_array();
			$return_value = $row['total_conversions'];
		}
		if($from_raw)
		{
			$this->raw_db->close();
		}
		return $return_value;
	}

	public function get_video_start_play_counts($from_raw, $report_date = null)
	{
		$get_video_start_count_query = "SELECT
											SUM(start_count) as video_starts
										FROM ";
		if($from_raw)
		{
			$get_video_start_count_query .= "ttd_raw_video";
			$this->raw_db = $this->load->database('td_intermediate', true);
			$get_video_start_count_result = $this->raw_db->query($get_video_start_count_query);
		}
		else
		{
			if($report_date == null)
			{
				return false;
			}
			$get_video_start_count_query .= "report_video_play_records WHERE date = ?";
			$get_video_start_count_result = $this->db->query($get_video_start_count_query, $report_date);
		}

		if($get_video_start_count_result == false)
		{
			return false;
		}

		if($get_video_start_count_result->num_rows() > 0)
		{
			$row = $get_video_start_count_result->row_array();
			$video_starts = $row['video_starts'];
			if($from_raw)
			{
				$this->raw_db->close();
			}
			return $video_starts;
		}
		return false;
	}

	public function get_aggregate_video_row_count_for_date($date)
	{
		$video_row_query = "SELECT
								COUNT(AdGroupID) AS video_rows
							FROM
								report_video_play_records
							WHERE
								date = ?";
		$video_count_result = $this->db->query($video_row_query, $date);
		if($video_count_result == false)
		{
			return false;
		}
		if($video_count_result->num_rows() > 0)
		{
			$row = $video_count_result->row_array();
			return $row['video_rows'];
		}
		return false;
	}


	public function print_conversion_list_to_file($conversion_list, $filepath_to_write_to)
	{
	    $conversion_file = fopen($filepath_to_write_to, 'x');
	    if($conversion_file == false)
	    {
		return false;
	    }
	    foreach($conversion_list as $entry)
	    {
		$write_result = fwrite($conversion_file, $entry."\n");
		if($write_result === false)
		{
		    return false;
		}
	    }
	    return true;
	}
	public function determine_video_campaign_type_for_campaign($campaign_id)
	{
		$get_adgroup_types_sql = "
								SELECT
									target_type
								FROM
									AdGroups
								WHERE
									campaign_id = ?
									AND Source != \"TDAV\"";
		$get_types_result = $this->db->query($get_adgroup_types_sql, $campaign_id);
		if($get_types_result == false)
		{
			return false;
		}
		if($get_types_result->num_rows() < 1)
		{
			return false;
		}

		$adgroup_types = $get_types_result->result_array();

		$found_pc = 0;
		$found_mobile_320 = 0;
		$found_mobile_no_320 = 0;
		$found_tablet = 0;
		$found_pre_roll = 0;
		$found_rtg = 0;
		$found_other = 0;

		foreach($adgroup_types as $adgroup_type)
		{
			switch($adgroup_type['target_type'])
			{
				case 'PC':
					$found_pc++;
					break;
				case 'RTG':
					$found_rtg++;
					break;
				case 'Mobile 320':
					$found_mobile_320++;
					break;
				case 'Mobile No 320':
					$found_mobile_no_320++;
					break;
				case 'Tablet':
					$found_tablet++;
					break;
				case 'Pre-Roll':
					$found_pre_roll++;
					break;
				case 'RTG Pre-Roll':
					$found_rtg++;
					break;
				default:
					$found_other++;
					break;
			}
		}
		if($found_pre_roll > 0)
		{
			$non_pre_roll = $found_pc + $found_mobile_320 + $found_mobile_no_320 + $found_tablet + $found_other;
			if($non_pre_roll)
			{
				return "Pre-Roll Custom";
			}
			return "Pre-Roll";
		}
		if($found_pc + $found_mobile_320 + $found_mobile_no_320 + $found_tablet + $found_rtg + $found_other == 5)
		{
			return "Display";
		}
		return "Custom";

	}

	public function get_adgroup_daily_target_totals($access_token, $campaign_id, $pc_adgroup_id)
	{
		$data = array();
		$adgroups = $this->get_all_adgroups_by_campaign($access_token, $campaign_id);

		if(property_exists($adgroups, 'Result'))
		{
			$data['non_pc_target'] = 0;
			$data['pc_adgroup_info'] = false;
			$data['total_target'] = 0;
			foreach($adgroups->Result as $adgroup)
			{
				$data['total_target'] += $adgroup->RTBAttributes->DailyBudgetInImpressions;
				if($pc_adgroup_id != $adgroup->AdGroupId)
				{
					$data['non_pc_target'] += $adgroup->RTBAttributes->DailyBudgetInImpressions;
				}
				else
				{
					$data['pc_adgroup_info'] = $adgroup;
				}
			}
		}
		return $data;
	}

	//allows user to update a ttd adgroup
	public function put_adgroup($ttd_json, $version)
	{
		$access_token = $this->get_access_token();
		if($version == 'v3')
		{
			$connect_url = "{$this->ttd_v3_base_url}adgroup";
		}
		else
		{
			$connect_url = "{$this->ttd_base_url}adgroup";
		}
		$error_counter = 0;
		$results['is_success'] = false;

		while(($error_counter < $this->max_tries) && ($results['is_success'] == false))
		{
			$result = $this->make_update_request($connect_url, $ttd_json, $access_token);
			$decoded = json_decode($result, true);
			if(!array_key_exists('Message', $decoded))
			{
				$results['errors'] = '';
				$results['ttd_response'] = $decoded;
				$results['is_success'] = true;
				$results['url'] = $connect_url;
			}
			else
			{
				$results['errors'] = $decoded['Message'];
				$results['ttd_response'] = $decoded;
				$results['url'] = $connect_url;
				$error_counter += 1;
			}
		}
		return $results;
	}

	private function process_sitelist_string($raw_site_string)
	{
		$sites_for_sitelist = array();
		$site_lines = explode(PHP_EOL, $raw_site_string);
		if(empty($site_lines))
		{
			return false;
		}

		$sites_for_sitelist = array();
		foreach($site_lines as $site_line)
		{
			$line_array = explode("\t", $site_line);
			if(count($line_array) != 3)
			{
				//Handle illegal line element count, I guess ignore it for now.
				continue;
			}
			$sites_for_sitelist[] = array(
				"Domain" => $line_array[0],
				"UniversalCategoryTaxonomyId" => empty($line_array[1]) ? null : $line_array[1],
				"Adjustment" => (float)$line_array[2]
			);

		}
		return $sites_for_sitelist;
	}

	public function create_sitelist_for_advertiser($access_token, $raw_site_string, $ttd_advertiser_id, $sitelist_name)
	{
		$return_data = array();
		$return_data['success'] = true;
		$return_data['err_msg'] = '';
		$return_data['sitelist_id'] = '';

		$sites_for_sitelist = $this->process_sitelist_string($raw_site_string);

		if(empty($sites_for_sitelist))
		{
			//Handle empty input list;
			return false;
		}

		$connect_url = $this->ttd_base_url.'sitelist';
		$ad_post = json_encode(array(
			"SiteListName" => $sitelist_name,
			"AdvertiserId" => $ttd_advertiser_id,
			"SiteListLines" => $sites_for_sitelist,
			"Permissions" => "Private"
		));
		$error_counter = 0;
		$did_succeed = false;
		while($error_counter < $this->max_tries && $did_succeed == false)
		{
			$result = $this->make_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_data['sitelist_id'] = $decoded->SiteListId;
				$did_succeed = true;
			}
			else
			{
				$return_data['err_msg'] = $decoded->Message;
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_data;
	}


	public function grab_ttd_details_for_campaign($campaign_id)
	{
		$grab_ttd_details_query = "
			SELECT
				ctd.*
			FROM
				campaigns_ttd_data AS ctd
			WHERE
				ctd.frq_campaign_id = ?
		";

		$grab_ttd_details_result = $this->db->query($grab_ttd_details_query, $campaign_id);
		if($grab_ttd_details_result == false)
		{
			return false;
		}
		if($grab_ttd_details_result->num_rows() == 0)
		{
			return 0;
		}
		else
		{
			return $grab_ttd_details_result->row_array();
		}

	}

	public function add_bulk_sitelists_to_campaigns_adgroups($sitelist_id, $campaign_id)
	{
		$access_token = $this->get_access_token();
		$adgroups = $this->get_ttd_adgroups_by_campaign($campaign_id);

		if($adgroups != false)
		{
			$error_adgroups = false;
			$error_message_adgroups = "Failed to updates for adgroups ";
			foreach($adgroups as $adgroup)
			{
				$did_update_adgroup = $this->tradedesk_model->add_sitelist_to_adgroup($access_token, $sitelist_id, $adgroup['ID'], null, $adgroup['target_type']);
				if($did_update_adgroup == false)
				{
					$error_adgroups = true;
					$error_message_adgroups .= $adgroup['ID']." ";
				}
			}
			if($error_adgroups)
			{
				return false;
			}
			if(!$this->save_sitelist_to_database_for_campaign($sitelist_id, "BULK UPLOADED - ".$sitelist_id. " - ".$campaign_id." - ".date("m.d.y"), "-BULK UPLOADED-\t\t0", $campaign_id))
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		return true;
	}

	public function add_sitelist_to_adgroup($access_token, $sitelist_id, $adgroup_id, $old_sitelist_id, $target_type)
	{
		$sitelist_array = array();
		$error_counter = 0;
		$sitelist_array = false;
		while($error_counter < $this->max_tries && $sitelist_array == false)
		{
			$adgroup_data = $this->get_info($access_token, "adgroup", $adgroup_id);
			$decoded = json_decode($adgroup_data);
			if(property_exists($decoded, 'Message'))
			{
				$error_counter += 1;
			}
			else
			{
				$sitelist_array = $decoded->RTBAttributes->SiteListIds;
				break;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "adgroup/".$adgroup_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}
		//Modify old sitelist list to replace existing sitelist with new one
		if(!empty($sitelist_array) && $old_sitelist_id)
		{
			$key = array_search($old_sitelist_id, $sitelist_array);
			if($key !== false && $old_sitelist_id[$key])
			{
				$sitelist_array[$key] = $sitelist_id;
			}
			else
			{
				array_push($sitelist_array, $sitelist_id);
			}
		}
		else
		{
			if(strpos($target_type, "Pre-Roll") === false)
			{
				array_push($sitelist_array, $sitelist_id);
			}
			else
			{
				$index = array_search($this->prod_pre_roll_sitelist_ids[0], $sitelist_array);
				if($index !== false)
				{
					array_splice($sitelist_array, $index, 1, array($sitelist_array[$index], $sitelist_id));
				}
				else
				{
					//Some weird preroll with its sitelists messed up
					array_push($sitelist_array, $sitelist_id);
				}
			}
		}

		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
			"AdGroupId"=>$adgroup_id,
			"RTBAttributes"=>array("SiteListIds"=>array_values($sitelist_array))
		));
		$error_counter = 0;
		$update_success = false;
		while($error_counter < $this->max_tries && $update_success == false)
		{
			$result = $this->make_update_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$update_success = $decoded->AdGroupId;
			}
			else
			{
				$error_counter += 1;
			}
		}
		return $update_success;
	}

	public function save_sitelist_to_database_for_campaign($ttd_sitelist_id, $sitelist_name, $raw_site_string, $campaign_id)
	{
		//encode the sitelist data for saving
		$sitelist_save_blob = $this->convert_sitelist_data_to_saving_blob($sitelist_name, $raw_site_string);

		$save_sitelist_query =
		"INSERT INTO
			campaigns_ttd_data
			(frq_campaign_id, ttd_sitelist_id, site_list_contents)
		VALUES
			(?, ?, ?)
		ON DUPLICATE KEY UPDATE
			ttd_sitelist_id = VALUES(ttd_sitelist_id),
			site_list_contents = VALUES(site_list_contents)
		";

		$save_sitelist_result = $this->db->query($save_sitelist_query, array($campaign_id, $ttd_sitelist_id, $sitelist_save_blob));
		if($save_sitelist_result == false)
		{
			return false;
		}

		return true;
	}

	private function convert_sitelist_data_to_saving_blob($sitelist_name, $raw_site_string)
	{
		$sitelist_data = array();
		$sitelist_data['name'] = $sitelist_name;
		$sites_for_sitelist = array();
		$site_lines = explode(PHP_EOL, $raw_site_string);
		if(empty($site_lines))
		{
			return false;
		}

		$sites_for_sitelist = array();
		foreach($site_lines as $site_line)
		{
			$line_array = explode("\t", $site_line);
			$sites_for_sitelist[] = $line_array;
		}
		$sitelist_data['data'] = $sites_for_sitelist;

		return json_encode($sitelist_data);
	}

	public function get_geosegment_list_from_zip_list($access_token, $zip_list)
	{
		$geo_segment_ids = array();
		$bad_zips = array();

		if(empty($zip_list))
		{
			return $geo_segment_ids;
		}

		$zips = preg_split('/[\ \n\,]+/', $zip_list);
		$zips = array_unique($zips);

		foreach($zips as $zip)
		{
			if(empty($zip))
			{
				continue;
			}
			//Get geosegments out of the database
			$get_zip_geosegment_query =
			"SELECT
				geosegment_id
			FROM
				ttd_geosegments_zips
			WHERE
				search_term LIKE ?
			";

			$get_zip_geosegment_result = $this->db->query($get_zip_geosegment_query, $zip);
			if($get_zip_geosegment_result == false)
			{
				return false;
			}

			if($get_zip_geosegment_result->num_rows() != 1)
			{
				$geosegment_for_stray_zip = $this->get_geosegment_for_missing_zip($access_token, $zip);
				if($geosegment_for_stray_zip == false)
				{
					$bad_zips[] = $zip;
				}
				else
				{
					$geo_segment_ids[] = $geosegment_for_stray_zip;
					$saved_zip = $this->save_geosegment_id($geosegment_for_stray_zip, $zip);
				}
				continue;
			}
			$row = $get_zip_geosegment_result->row_array();
			$geo_segment_ids[] = $row['geosegment_id'];
			$bad_zips = array_unique($bad_zips);
		}
		return array('geo_segment_ids' => $geo_segment_ids, 'bad_zips' => implode(",", $bad_zips));
	}

	private function get_geosegment_for_missing_zip($access_token, $zip)
	{
		$connect_url = $this->ttd_v3_base_url.'geosegment/query/advertiser';
		$ad_post = json_encode(array(
					"AdvertiserId" => "lzf2jmi",
					"SearchTerms" => array("United States", $zip),
					"PageStartIndex" => 0,
					"GeoSegmentFilter" => "StandardOnly",
					"PageSize" => null
					));
		$error_counter = 0;
		$did_succeed = false;
		$geo_segment = false;
		while($error_counter < $this->max_tries && $did_succeed == false)
		{
			$result = $this->make_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$did_succeed = true;
				if($decoded->ResultCount > 0)
				{
					$geo_segment = $decoded->Result;
					$geo_segment = $geo_segment[0]->GeoSegmentId;
				}
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $geo_segment;
	}

	private function get_us_geosegments_id_for_advertiser($access_token, $advertiser_id)
	{
		$connect_url = $this->ttd_v3_base_url.'geosegment/query/advertiser';
		$ad_post = json_encode(array(
					"AdvertiserId" => $advertiser_id,
					"SearchTerms" => array("United States - "),
					"PageStartIndex" => 0,
					"GeoSegmentFilter" => "StandardOnly",
					"PageSize" => null
					));
		$error_counter = 0;
		$did_succeed = false;
		while($error_counter < $this->max_tries && $did_succeed == false)
		{
			$result = $this->make_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$did_succeed = true;
				if($decoded->ResultCount > 0)
				{
					$geo_segments = $decoded->Result;
				}
			}
			else
			{
				$error_counter += 1;
			}
		}
		if(!$did_succeed)
		{
			return false;
		}
		return $geo_segments;
	}

	public function add_geosegment_list_to_adgroup($access_token, $adgroup_id, $geosegment_list)
	{
		$return_value = false;
		$geosegment_adjustments = array();
		foreach($geosegment_list as $geosegment)
		{
			$geosegment_adjustments[] = array(
				"Id" => $geosegment,
				"Adjustment" => 1.0
			);
		}

		$connect_url = $this->ttd_base_url.'adgroup';
		$ad_post = json_encode(array(
				"AdGroupId"=>$adgroup_id,
				"RTBAttributes"=>array("GeoSegmentAdjustments"=>$geosegment_adjustments)
			)
		);
		$error_counter = 0;
		while($error_counter < $this->max_tries && $return_value == false)
		{
			$result = $this->make_update_request($connect_url, $ad_post, $access_token);
			$decoded = json_decode($result);
			if(!property_exists($decoded, 'Message'))
			{
				$return_value = $decoded->AdGroupId;
			}
			else
			{
				$error_counter += 1;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    parse_response_error_email($decoded, $connect_url, $ad_post);
		}
		return $return_value;
	}


	public function save_zip_lists_to_database_for_campaign($raw_zip_list, $bad_zip_list, $campaign_id)
	{

		$save_zips_query =
		"INSERT INTO
			campaigns_zip_data
			(frq_campaign_id, zip_list, bad_zips)
		VALUES
			(?, ?, ?)
		";

		$save_zips_result = $this->db->query($save_zips_query, array($campaign_id, $raw_zip_list, $bad_zip_list));
		if($save_zips_result == false)
		{
			return false;
		}

		return true;
	}

	public function grab_newest_zips_for_campaign($campaign_id)
	{
		$grab_ttd_details_query = "
			SELECT
				czd.*
			FROM
				campaigns_zip_data AS czd
			WHERE
				czd.frq_campaign_id = ?
			ORDER BY time_created DESC
			LIMIT 1
		";

		$grab_ttd_details_result = $this->db->query($grab_ttd_details_query, $campaign_id);
		if($grab_ttd_details_result == false)
		{
			return false;
		}
		if($grab_ttd_details_result->num_rows() == 0)
		{
			return 0;
		}
		else
		{
			return $grab_ttd_details_result->row_array();
		}
	}

	public function grab_and_save_zips_for_geosegments()
	{
		$advertiser_id = "lnmnpqe";
		$batch_size = 0;
		$count = 0;
		$inserted = 0;
		$batch_max_size = 100;
		$bindings = array();
		$query_values = array();

		$access_token_v3 = $this->get_access_token(480, '3');
		$geosegments = $this->get_us_geosegments_id_for_advertiser($access_token_v3, $advertiser_id);
		echo "Retrieved Geosegments from Tradedesk.\n";
		foreach($geosegments as $geosegment)
		{
			if($geosegment->GeoLocationType != "Zip")
			{
				continue;
			}

			$zip_code = $geosegment->GeoSegmentName;
			$zip_code = str_replace("United States - ", "", $zip_code);
			echo "{$count} ({$geosegment->GeoSegmentName}) - ZIP: {$zip_code}  - GEOSEGMENT: {$geosegment->GeoSegmentId} ";

			if(!is_numeric($zip_code) || strlen($zip_code) != 5)
			{
				echo "=SKIPPED=\n";
				continue;
			}
			echo "\n";
			$count++;
			$batch_size++;
			$query_values[] = "(?, ?)";
			$bindings[] = $zip_code;
			$bindings[] = $geosegment->GeoSegmentId;

			if($batch_size == $batch_max_size)
			{
				$query_string = implode(',', $query_values);
				$saved_geosegments = $this->save_geosegment_batch($bindings, $query_string);
				if($saved_geosegments === false)
				{
					return false;
				}
				$inserted += $saved_geosegments;
				$bindings = array();
				$query_values = array();
				$batch_size = 0;
			}
		}
		if(!empty($query_values))
		{
			$query_string = implode(',', $query_values);
			$saved_geosegments = $this->save_geosegment_batch($bindings, $query_string);
			if($saved_geosegments === false)
			{
				return false;
			}
			$inserted += $saved_geosegments;
		}
		return $inserted;
	}

	private function save_geosegment_batch($bindings, $values_string)
	{
		$insert_geosegments_query =
		"INSERT INTO
			ttd_geosegments_zips
			(search_term, geosegment_id)
			VALUES {$values_string}
			ON DUPLICATE KEY UPDATE
				geosegment_id = VALUES(geosegment_id)
		";

		$insert_geosegments_result = $this->db->query($insert_geosegments_query, $bindings);
		if($insert_geosegments_result == false)
		{
			return false;
		}
		return $this->db->affected_rows();
	}

	public function is_tag_file_push_friendly_for_advertiser($tag_file_id, $td_advertiser_id)
	{
		//Does the tag file have just 1 rtg and 1 conversion tag?
		$bindings = array($tag_file_id, "%".$td_advertiser_id."%");
		$get_tags_for_file_query =
		"SELECT
			tag_code,
			tag_type
		FROM
			tag_codes
		WHERE
			tag_file_id = ?
		AND
			tag_type IN (0,2)
		AND
			isActive = 1
		AND
			tag_code LIKE ?
		";
		$get_tags_for_file_result = $this->db->query($get_tags_for_file_query, $bindings);
		if($get_tags_for_file_result === false || $get_tags_for_file_result->num_rows > 2)
		{
			return false;
		}
		$tag_codes = $get_tags_for_file_result->result_array();
		$rtg_tags = 0;
		$conversion_tags = 0;
		foreach($tag_codes as $tag_code)
		{
			//Let's just check again to be sure...
			if(strpos($tag_code['tag_code'], $td_advertiser_id) === false)
			{
				return false;
			}
			if($tag_code['tag_type'] == 0)
			{
				if (substr_count($tag_code['tag_code'],"<iframe") > 1)
				{
					$rtg_tags = $rtg_tags + substr_count($tag_code['tag_code'],"<iframe");
				}
				else
				{
					$rtg_tags++;
				}
			}
			if($tag_code['tag_type'] == 2)
			{
				if (substr_count($tag_code['tag_code'],"<iframe") > 1)
				{
					$conversion_tags = $conversion_tags + substr_count($tag_code['tag_code'],"<iframe");
				}
				else
				{
					$conversion_tags++;
				}
			}
		}
		if($rtg_tags <= 1 && $conversion_tags <= 1)
		{
			return array('rtg' => $rtg_tags, 'conversion' => $conversion_tags);
		}
		return false;
	}
	
	public function is_tag_file_empty_or_nopixels($tag_file_id, $td_advertiser_id)
	{
		//Does the tag file have 1 rtg or 1 conversion tag? check if file empty or not?
		$bindings = array($tag_file_id, "%".$td_advertiser_id."%");
		$get_tags_for_file_query =
			"SELECT
				tag_code,
				tag_type
			FROM
				tag_codes
			WHERE
				tag_file_id = ?
			AND
				tag_type IN (0,2)
			AND
				isActive = 1
			AND
				tag_code LIKE ?
			";
		$get_tags_for_file_result = $this->db->query($get_tags_for_file_query, $bindings);
		if($get_tags_for_file_result === false || $get_tags_for_file_result->num_rows > 2)
		{
			return false;
		}
		$tag_codes = $get_tags_for_file_result->result_array();		
		return $get_tags_for_file_result->num_rows();
	}

	public function apply_audience_from_tag_file_to_campaign($access_token, $tag_file_id, $campaign_id)
	{
		//Get TTD RTG ID of campaign using the tag file
		$get_rtg_adgroup_query =
		"SELECT
			adg.ID AS adgroup_id
		FROM
			tag_files_to_campaigns AS tftc
		JOIN
			AdGroups AS adg
		ON
			(adg.campaign_id = tftc.campaign_id)
		WHERE
			(adg.target_type = 'RTG' OR adg.target_type = 'RTG Pre-Roll')
		AND
			tftc.tag_file_id = ?
		";

		$get_rtg_adgroup_result = $this->db->query($get_rtg_adgroup_query, $tag_file_id);
		if($get_rtg_adgroup_result == false || $get_rtg_adgroup_result->num_rows < 1)
		{
			return false;
		}
		$row = $get_rtg_adgroup_result->row_array();
		$adgroup_id = $row['adgroup_id'];

		//Go to that one and get its audience
		$error_counter = 0;
		$audience_id = null;
		while($error_counter < $this->max_tries && $audience_id == null)
		{
			$adgroup_data = $this->get_info($access_token, "adgroup", $adgroup_id);
			$decoded = json_decode($adgroup_data);
			if(property_exists($decoded, 'Message'))
			{
				$error_counter += 1;
			}
			else
			{
				$audience_id = $decoded->RTBAttributes->AudienceId;
				break;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "adgroup/".$adgroup_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}

		//Apply that audience to the RTG adgroup of $campaign_id
		$get_rtg_adgroup_query =
		"SELECT
			adg.ID AS adgroup_id
		FROM
			AdGroups AS adg
		WHERE
			(adg.target_type = 'RTG' OR adg.target_type = 'RTG Pre-Roll')
		AND
			adg.campaign_id = ?
		";
		$get_rtg_adgroup_result = $this->db->query($get_rtg_adgroup_query, $campaign_id);
		if($get_rtg_adgroup_result == false || $get_rtg_adgroup_result->num_rows < 1)
		{
			return false;
		}
		$row = $get_rtg_adgroup_result->row_array();
		$new_adgroup_id = $row['adgroup_id'];
		return $this->update_adgroup_audience($access_token, $new_adgroup_id, $audience_id);
	}

	public function apply_conversion_tag_from_tag_file_to_campaign($access_token, $tag_file_id, $td_campaign_id)
	{
		$get_conversion_tag_query =
		"SELECT
			tc.tag_code AS tag_code
		FROM
			tag_files AS tf
			JOIN tag_codes AS tc
				ON (tf.id = tc.tag_file_id)
		WHERE
			tc.tag_type = 2
			AND tf.id = ?
		";
		$get_conversion_tag_result = $this->db->query($get_conversion_tag_query, $tag_file_id);
		if($get_conversion_tag_result == false || $get_conversion_tag_result->num_rows() != 1)
		{
			return false;
		}
		$row = $get_conversion_tag_result->row_array();
		$conversion_tag = $row['tag_code'];

		//Extract tag id
		$tag_url_fragment = substr($conversion_tag, strpos($conversion_tag, 'tags'));
		$tag_fragments = explode('/', $tag_url_fragment);
		$conversion_tag_id = $tag_fragments[2];

		return $this->update_campaign_conversion_reporting_columns($access_token, $td_campaign_id, array($conversion_tag_id));
	}

	public function get_ttd_tag_info($access_token, $campaign_id, $ttd_campaign_id)
	{
		$result = array();

		$get_rtg_adgroup_query =
		"SELECT
			adg.ID AS adgroup_id
		FROM
			AdGroups AS adg
		WHERE
			(adg.target_type = 'RTG' OR adg.target_type = 'RTG Pre-Roll')
		AND
			adg.campaign_id = ?
		";

		$get_rtg_adgroup_result = $this->db->query($get_rtg_adgroup_query, $campaign_id);

		if ($get_rtg_adgroup_result == false || $get_rtg_adgroup_result->num_rows < 1)
		{
			return false;
		}

		$row = $get_rtg_adgroup_result->row_array();
		$rtg_adgroup_id = $row['adgroup_id'];

		//Get the RTG audience id
		$error_counter = 0;
		while ($error_counter < $this->max_tries && !isset($result['rtg_audience_id']) )
		{
			$adgroup_data = $this->get_info($access_token, "adgroup", $rtg_adgroup_id);
			$decoded = json_decode($adgroup_data);
			if(property_exists($decoded, 'Message'))
			{
				$error_counter += 1;
			}
			else
			{
				$result['rtg_audience_id'] = $decoded->RTBAttributes->AudienceId;
				break;
			}
		}
		if($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "adgroup/".$rtg_adgroup_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}

		//Get the conversion tags count
		$error_counter = 0;
		while ($error_counter < $this->max_tries && !isset($result['conversion_tags_count']))
		{
		    $campaign_data = $this->get_info($access_token, "campaign", $ttd_campaign_id);
		    $decoded = json_decode($campaign_data);
		    if(!property_exists($decoded, 'Message'))
		    {
			$result['conversion_tags_count'] = count($decoded->CampaignConversionReportingColumns);
			break;
		    }
		    else
		    {
			$error_counter += 1;
		    }
		}
		if ($error_counter == $this->max_tries)
		{
		    $connect_url = $this->ttd_base_url. "campaign/".$ttd_campaign_id;
		    parse_response_error_email($decoded, $connect_url, $access_token);
		    return false;
		}

		return $result;
	}

	private function save_geosegment_id($geosegment_id, $search_term)
	{
		$insert_geosegments_query =
		"INSERT INTO
			ttd_geosegments_zips
			(search_term, geosegment_id)
			VALUES
				(?, ?)
			ON DUPLICATE KEY UPDATE
				geosegment_id = VALUES(geosegment_id)
		";

		$insert_geosegments_result = $this->db->query($insert_geosegments_query, array($search_term, $geosegment_id));
		if($insert_geosegments_result == false)
		{
			return false;
		}
		return true;
	}

	//sanitise names to disallow special characters
	private function sanitize_string_for_ttd($name)
 	{
 	    $text = preg_replace('/[;~\/<>|^\r\n]|[;]$/s', '_', $name);
 	    return $text;
 	}
	
	//This method will get advertiser website by using advertiser_id
	private function get_advertiser_website($advertiser_id)
	{
		$sql_query =
			"SELECT
				advertiser_website
			FROM
				mpq_sessions_and_submissions
			WHERE
				io_advertiser_id = ?
			";
		$result = $this->db->query($sql_query, $advertiser_id);
		if ($result->num_rows())
		{
			return $result->row()->advertiser_website;
		}
		return false;
	}

}
?>
