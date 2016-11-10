<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require FCPATH . '/vendor/autoload.php';
require FCPATH . '/libraries/external/php/dfa-v1.1.6/google/apiclient/autoload.php';


require_once 'vendor/googleads/googleads-php-lib/src/Google/Api/Ads/Dfp/Util/v201605/StatementBuilder.php';
require_once "vendor/googleads/googleads-php-lib/src/Google/Api/Ads/Dfp/v201605/OrderService.php";
require_once "vendor/googleads/googleads-php-lib/src/Google/Api/Ads/Dfp/v201605/LineItemService.php";
require_once "vendor/googleads/googleads-php-lib/src/Google/Api/Ads/Dfp/v201605/CreativeService.php";
require_once "vendor/googleads/googleads-php-lib/src/Google/Api/Ads/Dfp/v201605/CompanyService.php";
require_once "vendor/googleads/googleads-php-lib/src/Google/Api/Ads/Dfp/v201605/LineItemCreativeAssociationService.php";

class google_api
{
	private $client;
	private $service;
	private $auth_token;
	private $google_user_email;
	private $user_profile_id;
	private $client_email;
	private $private_key;
	
	private $dfp_client_id;
	private $dfp_client_secret;
	private $dfp_refresh_token;
	private $dfp_network_code;
	private $dfp_application_name;

	private $dfp_user;
	private $dfp_api_version = "v201605";
	private $report_service = '';

	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('dfp_model');

		$this->client = new Google_Client();
		$this->google_user_email = $this->ci->config->item('dfa_client_user_email');
		$this->user_profile_id = $this->ci->config->item('dfa_profile_id');;
		$this->client_email = $this->ci->config->item('dfa_client_email');
		$this->private_key = $this->ci->config->item('dfa_api_key');

		$this->dfp_client_id = $this->ci->config->item('dfp_client_id');
		$this->dfp_client_secret = $this->ci->config->item('dfp_client_secret');
		$this->dfp_refresh_token = $this->ci->config->item('dfp_refresh_token');
		$this->dfp_network_code = $this->ci->config->item('dfp_network_code');
		$this->dfp_application_name = $this->ci->config->item('dfp_application_name');
		
		$this->dfp_initialize(); //User Initialized
		
	}

	public function dfp_initialize()
	{
		$oauth2Info = '';
	    	$oauth2Info = array(
			'client_id' => $this->dfp_client_id,
			'client_secret' => $this->dfp_client_secret,
			'refresh_token' => $this->dfp_refresh_token
			);
		$this->dfp_user = new DfpUser(null, $this->dfp_application_name, $this->dfp_network_code, null, $oauth2Info);
	}


	public function dfa_initalize()
	{
		$scopes = array(Google_Service_Dfareporting::DFAREPORTING, Google_Service_Dfareporting::DFATRAFFICKING);
		$this->google_api_initialize($scopes);
	}

	private function google_api_initialize($scopes)
	{
		$credentials = new Google_Auth_AssertionCredentials(
			$this->client_email,
			$scopes,
			$this->private_key,
			'notasecret',
			'http://oauth.net/grant_type/jwt/1.0/bearer',
			$this->google_user_email
			);

		$this->client->setAssertionCredentials($credentials);

		if($this->client->getAuth()->isAccessTokenExpired())
		{
			$this->client->getAuth()->refreshTokenWithAssertion();
		}
	}

	public function dfa_get_advertiser_with_name($advertiser_name)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$return_advertisers = array();
			$this->service = new Google_Service_Dfareporting($this->client);
			$get_advertisers_result = $this->service->advertisers->listAdvertisers($this->user_profile_id, array('searchString'  => $advertiser_name, 'pageToken' => null));
			$result['dfa_result'] = $get_advertisers_result->advertisers;
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;		
	}

	public function dfa_get_advertiser_list()
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_advertiser_records'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$return_advertisers = array();
			$this->service = new Google_Service_Dfareporting($this->client);
			$get_advertisers_result = $this->service->advertisers->listAdvertisers($this->user_profile_id, array('sortField' => "NAME", 'sortOrder' => "ASCENDING", 'pageToken' => null));
			$page_advertisers = $get_advertisers_result->advertisers;
			$result['dfa_advertiser_records'] = $page_advertisers;
			while(count($page_advertisers) == 1000)
			{
				$get_advertisers_result = $this->service->advertisers->listAdvertisers($this->user_profile_id, array('sortField' => "NAME", 'sortOrder' => "ASCENDING", 'pageToken' => $get_advertisers_result->nextPageToken));
				$page_advertisers = $get_advertisers_result->advertisers;
				$result['dfa_advertiser_records'] = array_merge($result['dfa_advertiser_records'], $get_advertisers_result->advertisers);
			}
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_insert_advertiser($advertiser_name)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$new_advertiser = new Google_Service_Dfareporting_Advertiser();
			$new_advertiser->setName($advertiser_name);
			$new_advertiser->setStatus("APPROVED");

			$result['dfa_result'] = $this->service->advertisers->insert($this->user_profile_id, $new_advertiser);
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;

	}

	public function dfa_get_campaign_list_for_advertiser($advertiser_id)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$return_campaigns = array();
			$this->service = new Google_Service_Dfareporting($this->client);
			$get_campaigns_result = $this->service->campaigns->listCampaigns($this->user_profile_id, array('sortField' => "NAME", 'sortOrder' => "ASCENDING", 'advertiserIds' => $advertiser_id, 'pageToken' => null));
			$page_campaigns = $get_campaigns_result->campaigns;
			$result['dfa_result'] = $page_campaigns;
			while(count($page_campaigns) == 1000)
			{
				$get_campaigns_result = $this->service->campaigns->listCampaigns($this->user_profile_id, array('sortField' => "NAME", 'sortOrder' => "ASCENDING", 'advertiserIds' => $advertiser_id, 'pageToken' => $get_campaigns_result->nextPageToken));
				$page_campaigns = $get_campaigns_result->campaigns;
				$result['dfa_result'] = array_merge($result['dfa_result'], $get_campaigns_result->campaigns);
			}
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_insert_campaign($campaign_name, $landing_page, $advertiser_id)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$new_campaign = new Google_Service_Dfareporting_Campaign();
			$new_campaign->setName($campaign_name);
			$new_campaign->setAdvertiserId($advertiser_id);
			$new_campaign->setStartDate(date("Y-m-d", strtotime('today')));
			$new_campaign->setEndDate(date("Y-m-d", strtotime('+4 years')));

			$result['dfa_result'] = $this->service->campaigns->insert($this->user_profile_id, $campaign_name, $landing_page, $new_campaign);
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_create_asset($advertiser_id, $filename, $asset_type, $for_html_creatives)
	{	
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$path_parts = pathinfo($filename);
			
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$file_mime_type = $finfo->buffer(file_get_contents($filename));

			$this->service = new Google_Service_Dfareporting($this->client);
			$new_asset_id = new Google_Service_Dfareporting_CreativeAssetId();
			$new_asset_id->setName(uniqid().'.'.$path_parts['extension']);
			$new_asset_id->setType($asset_type);
			//$new_asset_id->setActive(true);

			$metadata = new Google_Service_Dfareporting_CreativeAssetMetadata();
			$metadata->setAssetIdentifier($new_asset_id);

			$asset_insert = $this->service->creativeAssets->insert($this->user_profile_id, $advertiser_id, $metadata,
				array(
					"data" => file_get_contents($filename),
					"mimeType" => $file_mime_type,
					"uploadType" => 'multipart'
				));
			$result['dfa_result'] = $asset_insert->getAssetIdentifier();

		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_upload_inpage_creative($creative_name, $advertiser_id, $campaign_id, $size, $asset_id, $ad_html)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$creative = new Google_Service_Dfareporting_Creative();
			$creative->setAdvertiserId($advertiser_id);
			$creative->setName($creative_name);
			$creative->setType('CUSTOM_DISPLAY');
			$creative->setActive(true);
			$creative->setHtmlCode($ad_html);
			$creative->setSslCompliant(true);

			$creative_size = $this->get_creative_size_id($size);
			if($creative_size === false)
			{
				throw("Failed to retrieve creative size data from DFA");
			}
			$creative->setSize($creative_size);

			$asset = new Google_Service_Dfareporting_CreativeAsset();
			$asset->setAssetIdentifier($asset_id);
			$asset->setRole("BACKUP_IMAGE");
			$asset->setActive(true);

			$creative->setCreativeAssets(array($asset));

			$result['dfa_result'] = $this->service->creatives->insert($this->user_profile_id, $creative);
			$campaign_association = new Google_Service_Dfareporting_CampaignCreativeAssociation();
			$campaign_association->setCreativeId($result['dfa_result']->id);

			$associate_result = $this->service->campaignCreativeAssociations->insert($this->user_profile_id, $campaign_id, $campaign_association);
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_upload_image_creative($creative_name, $advertiser_id, $campaign_id, $file_name, $size, $asset_id)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$creative = new Google_Service_Dfareporting_Creative();
			$creative->setAdvertiserId($advertiser_id);
			$creative->setName($creative_name);
			$creative->setType('IMAGE');
			$creative->setActive(true);

			$creative_size = $this->get_creative_size_id($size);
			if($creative_size === false)
			{
				throw("Failed to retrieve creative size data from DFA");
			}
			$creative->setSize($creative_size);

			$asset = new Google_Service_Dfareporting_CreativeAsset();
			$asset->setAssetIdentifier($asset_id);
			$asset->setRole('PRIMARY');
			$asset->setActive(true);

			$creative->setCreativeAssets(array($asset));

			$result['dfa_result'] = $this->service->creatives->insert($this->user_profile_id, $creative);
			$campaign_association = new Google_Service_Dfareporting_CampaignCreativeAssociation();
			$campaign_association->setCreativeId($result['dfa_result']->id);

			$associate_result = $this->service->campaignCreativeAssociations->insert($this->user_profile_id, $campaign_id, $campaign_association);
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;		
	}

	public function dfa_create_placement($placement_name, $campaign_id, $placement_site_id, $size)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$placement = new Google_Service_Dfareporting_Placement();
			$placement->setCampaignId($campaign_id);
			$placement->setCompatibility('DISPLAY');
			$placement->setName($placement_name);
			$placement->setPaymentSource('PLACEMENT_AGENCY_PAID');
			$placement->setSiteId($placement_site_id);
			$placement->setTagFormats(array(
										'PLACEMENT_TAG_STANDARD',
										'PLACEMENT_TAG_IFRAME_JAVASCRIPT',
										'PLACEMENT_TAG_IFRAME_JAVASCRIPT_LEGACY',
										'PLACEMENT_TAG_INTERNAL_REDIRECT',
										'PLACEMENT_TAG_TRACKING',
										'PLACEMENT_TAG_TRACKING_IFRAME',
										'PLACEMENT_TAG_TRACKING_JAVASCRIPT',
										'PLACEMENT_TAG_JAVASCRIPT',
										'PLACEMENT_TAG_CLICK_COMMANDS'));

			$placement_size = $this->get_creative_size_id($size);
			if($placement_size === false)
			{
				throw("Failed to retrieve placement creative size data from DFA");
			}
			$placement->setSize($placement_size);
			
			$pricing_schedule = new Google_Service_Dfareporting_PricingSchedule();
			$pricing_schedule->setStartDate(date("Y-m-d", strtotime('today')));
			$pricing_schedule->setEndDate(date("Y-m-d", strtotime('+1 year')));
			$pricing_schedule->setPricingType('PRICING_TYPE_CPM');
			$placement->setPricingSchedule($pricing_schedule);
			 
			$result['dfa_result'] = $this->service->placements->insert($this->user_profile_id, $placement);
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_enable_ad_for_creative_for_placement($creative, $placement)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;

		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$creative_ad_list = $this->service->ads->listAds($this->user_profile_id, array('creativeIds' => $creative->id));
			if(count($creative_ad_list->ads) > 0)
			{
				$creative_ad = $creative_ad_list->ads[0];
				$creative_ad->setActive(true);
				$creative_ad->setStartTime(date("Y-m-d\TH:i:sP", strtotime('+1 minute -3 hours')));
				$creative_ad->setEndTime(date("Y-m-d\T03:59:59\Z", strtotime('+4 years -1 day')));
				$result['dfa_result'] = $this->service->ads->update($this->user_profile_id, $creative_ad);
			}		
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;
	}

	public function dfa_get_tags($placement_id, $campaign_id)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;
		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);
			$tags_result = $this->service->placements->generatetags($this->user_profile_id, array(
				"campaignId" => $campaign_id,
				"placementIds" => array($placement_id),
				"tagFormats"=> array('PLACEMENT_TAG_IFRAME_JAVASCRIPT_LEGACY')
				));
			$tags = $tags_result->getPlacementTags();
			
			$result['dfa_result'] = $tags[0]['tagDatas'][0]->getImpressionTag();
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;		
	}

	public function dfa_link_creative_to_placement_and_campaign($creative, $placement, $campaign_id)
	{
		$result['is_success'] = true;
		$result['err_msg'] = "";
		$result['dfa_result'] = NULL;
		$result['error_counter'] = 0;
		try
		{
			$this->service = new Google_Service_Dfareporting($this->client);

			$url = new Google_Service_Dfareporting_ClickThroughUrl();
			$url->setDefaultLandingPage(true);

			$creative_assignment = new Google_Service_Dfareporting_CreativeAssignment();
			$creative_assignment->setActive(true);
			$creative_assignment->setCreativeId($creative->getId());
			$creative_assignment->setClickThroughUrl($url);

			$placement_assignment = new Google_Service_Dfareporting_PlacementAssignment();
			$placement_assignment->setActive(true);
			$placement_assignment->setPlacementId($placement->getId());

			$creative_rotation = new Google_Service_Dfareporting_CreativeRotation();
			$creative_rotation->setCreativeAssignments(array($creative_assignment));

			$delivery_schedule = new Google_Service_Dfareporting_DeliverySchedule();
			$delivery_schedule->setImpressionRatio(1);
			$delivery_schedule->setPriority('AD_PRIORITY_15');

			$new_ad = new Google_Service_Dfareporting_Ad();
			$new_ad->setCampaignId($campaign_id);
			$new_ad->setCreativeRotation($creative_rotation);
			$new_ad->setDeliverySchedule($delivery_schedule);
			$new_ad->setStartTime(date("Y-m-d\TH:i:sP", strtotime('+1 minute -3 hours')));
			$new_ad->setEndTime(date("Y-m-d\T03:59:59\Z", strtotime('+4 years -1 day')));
			$new_ad->setName($creative->getName()."-".$placement->getName());
			$new_ad->setPlacementAssignments(array($placement_assignment));
			$new_ad->setType('AD_SERVING_STANDARD_AD');
			$new_ad->setActive(true);

			$result['dfa_result'] = $this->service->ads->insert($this->user_profile_id,$new_ad);
		}
		catch(Exception $e)
		{
			$result['is_success'] = false;
			$result['err_msg'] = $e->getMessage();
		}
		return $result;			
	}

	private function get_creative_size_id($size_string)
	{
		$dimensions = explode('x', $size_string);
		$width = $dimensions[0];
		$height = $dimensions[1];

		$size_response =$this->service->sizes->listSizes($this->user_profile_id, array(
				'height' => $height,
				'width' => $width
			)
		);
		$size_results = $size_response->getSizes();
		if(!empty($size_results))
		{	
			return $size_results[0];
		}
		return false;
	}

	public function create_dfp_order_lineitems($dfp_order_details, $dfp_object_order_template, $dfp_object_lineitem_template)
	{

		$order = $this->generate_order($dfp_order_details, $dfp_object_order_template);
		$order_details = $this->create_order($order);

		$targeting = $this->generate_targeting(
			null,
			array(327449430)
		);

		$line_item = $this->generate_line_item($dfp_order_details, $dfp_object_lineitem_template);
		$line_item = $this->create_line_item($line_item);
		print_r($line_item);

//		print_r($this->link_creatives_to_line_item($new_creative['data'], $line_item['data'][0]->id));


		print_r($this->get_line_item("2493763470"));
	}

	private function start_service($service_name)
	{
		return $this->dfp_user->GetService($service_name, $this->dfp_api_version);

	}

	public function get_order($order_id)
	{
		$order_service = $this->start_service('OrderService');

		$statement_builder = new StatementBuilder();
		$statement_builder->Where('id = '.$order_id);
		$retrieved_order = $order_service->getOrdersByStatement($statement_builder->ToStatement());
		if(isset($retrieved_order->results))
		{
			//get order
			return $retrieved_order->results[0];
		}
		return false;
	}

	public function get_line_item($line_item_id)
	{
		$line_item_service = $this->start_service('LineItemService');

		$statement_builder = new StatementBuilder();
		$statement_builder->Where('id = '.$line_item_id);
		$retrieved_line_item = $line_item_service->getLineItemsByStatement($statement_builder->ToStatement());
		if(isset($retrieved_line_item->results))
		{
			return $retrieved_line_item->results[0];
		}
		return false;
	}

	public function get_line_items_for_order($order_id)
	{
		$line_item_service = $this->start_service('LineItemService');

		$statement_builder = new StatementBuilder();
		$statement_builder->Where('orderId = '.$order_id);
		$retrieved_line_items = $line_item_service->getLineItemsByStatement($statement_builder->ToStatement());
		if(isset($retrieved_line_items->results))
		{
			return $retrieved_line_items->results;
		}
		return false;		
	}

	public function generate_order($dfp_order_details,$dfp_object_order_template)
	{
		$new_order = new Order();

		$new_order->name = $dfp_order_details['order_name'];
		$new_order->unlimitedEndDateTime = $dfp_object_order_template->unlimitedEndDateTime;
		$new_order->status = $dfp_object_order_template->status;
		$new_order->notes = $dfp_object_order_template->notes;
		$new_order->externalOrderId = $dfp_object_order_template->externalOrderId;
		$new_order->poNumber = $dfp_object_order_template->poNumber;
		$new_order->currencyCode = $dfp_object_order_template->currencyCode;
		$new_order->advertiserId = $dfp_order_details['dfp_advertiser_id'];
		$new_order->advertiserContactIds = $dfp_object_order_template->advertiserContactIds;
		$new_order->agencyId = $dfp_object_order_template->agencyId;
		$new_order->agencyContactIds = $dfp_object_order_template->agencyContactIds;
		$new_order->creatorId = $dfp_object_order_template->creatorId;
		$new_order->traffickerId = 166650990;
		$new_order->secondaryTraffickerIds = $dfp_object_order_template->secondaryTraffickerIds;
		$new_order->salespersonId = $dfp_object_order_template->salespersonId;
		$new_order->secondarySalespersonIds = $dfp_object_order_template->secondarySalespersonIds;
		$new_order->totalImpressionsDelivered = $dfp_object_order_template->totalImpressionsDelivered;
		$new_order->totalClicksDelivered = $dfp_object_order_template->totalClicksDelivered;
		$new_order->appliedLabels = $dfp_object_order_template->appliedLabels;
		$new_order->effectiveAppliedLabels = $dfp_object_order_template->effectiveAppliedLabels;
		$new_order->lastModifiedByApp = $dfp_object_order_template->lastModifiedByApp;
		$new_order->isProgrammatic = $dfp_object_order_template->isProgrammatic;
		$new_order->programmaticSettings = $dfp_object_order_template->programmaticSettings;
		$new_order->appliedTeamIds = $dfp_object_order_template->appliedTeamIds;
		$new_order->lastModifiedDateTime = $dfp_object_order_template->lastModifiedDateTime;
		$new_order->customFieldValues = $dfp_object_order_template->customFieldValues;

		return $new_order;
	}

	public function create_order($order)
	{
		return $this->create_orders(array($order));
	}

	public function create_orders($orders)
	{
		$return_array = array("success" => true, "data" => null);
		try
		{
			$order_service = $this->start_service('OrderService');
			$return_array['data'] = $order_service->createOrders($orders);
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}


	public function generate_line_item($dfp_lineitem_details, $dfp_object_lineitem_template)
	{
		$line_item = new LineItem();

		//Pass targeted ad units and placements
		$targeted_ad_units = $dfp_object_lineitem_template->targeting->inventoryTargeting->targetedAdUnits;
		$targeted_placement_ids = $dfp_object_lineitem_template->targeting->inventoryTargeting->targetedPlacementIds;
		$targeting = $this->generate_targeting(
  			$targeted_placement_ids,
			$targeted_ad_units,
			$dfp_lineitem_details['google_api_location_ids']
  		);
		
		$line_item->targeting = $targeting;

		$line_item->creativeTargetings = $dfp_object_lineitem_template->creativeTargetings;

		$line_item->orderId = $dfp_lineitem_details['order_id'];

		$line_item->name = $dfp_lineitem_details['line_item_name'];

		$line_item->externalId = $dfp_object_lineitem_template->externalId;

		$line_item->startDateTime = DateTimeUtils::ToDfpDateTime(new DateTime($dfp_lineitem_details['start_date'], new DateTimeZone('America/New_York')));

		$line_item->startDateTimeType = $dfp_object_lineitem_template->startDateTimeType;

		$line_item->endDateTime = DateTimeUtils::ToDfpDateTime(new DateTime($dfp_lineitem_details['end_date'], new DateTimeZone('America/New_York')));

		$line_item->autoExtensionDays = $dfp_object_lineitem_template->autoExtensionDays;
		$line_item->unlimitedEndDateTime = $dfp_object_lineitem_template->unlimitedEndDateTime;
		$line_item->creativeRotationType = $dfp_object_lineitem_template->creativeRotationType;
		$line_item->deliveryRateType = $dfp_object_lineitem_template->deliveryRateType;
		$line_item->roadblockingType = $dfp_object_lineitem_template->roadblockingType;
		$line_item->frequencyCaps = $dfp_object_lineitem_template->frequencyCaps;
		$line_item->lineItemType = $dfp_object_lineitem_template->lineItemType;
		$line_item->priority = $dfp_object_lineitem_template->priority;

		$budget = new Money();
		$budget->currencyCode = 'USD';
		$budget->microAmount = $dfp_lineitem_details['cost_per_unit']*1000000;

		$line_item->costPerUnit = $budget;

		$line_item->costType = $dfp_object_lineitem_template->costType;
		$line_item->discountType = $dfp_object_lineitem_template->discountType;
		$line_item->discount = $dfp_object_lineitem_template->discount;
		$line_item->contractedUnitsBought = $dfp_object_lineitem_template->contractedUnitsBought;
		
		$creative_sizes = $dfp_lineitem_details['creative_sizes']; //array("160x600");
		$line_item->creativePlaceholders = array();
		foreach($creative_sizes as $creative_size)
		{
		    	$creative_placeholder = new creativePlaceholder();
			$size = explode('x', $creative_size);
			$creative_placeholder->size = new Size($size[0], $size[1], false);
			$line_item->creativePlaceholders[] = $creative_placeholder;
		}
		
		$line_item->activityAssociations = $dfp_object_lineitem_template->creativeTargetings;
		$line_item->environmentType = $dfp_object_lineitem_template->environmentType;
		$line_item->companionDeliveryOption = $dfp_object_lineitem_template->companionDeliveryOption;
		$line_item->creativePersistenceType = $dfp_object_lineitem_template->creativePersistenceType;
		$line_item->allowOverbook = $dfp_object_lineitem_template->allowOverbook;
		$line_item->skipInventoryCheck = $dfp_object_lineitem_template->skipInventoryCheck;
		$line_item->skipCrossSellingRuleWarningChecks = $dfp_object_lineitem_template->skipCrossSellingRuleWarningChecks;
		$line_item->reserveAtCreation = $dfp_object_lineitem_template->reserveAtCreation;
		
		$line_item->stats = $dfp_object_lineitem_template->creativeTargetings;
		
		$line_item->deliveryIndicator = $dfp_object_lineitem_template->creativeTargetings;
		
		$line_item->deliveryData = $dfp_object_lineitem_template->deliveryData;
		
		$line_item->status = $dfp_object_lineitem_template->status;
		$line_item->reservationStatus = $dfp_object_lineitem_template->reservationStatus;
		
		$line_item->webPropertyCode = $dfp_object_lineitem_template->webPropertyCode;
		$line_item->appliedLabels = $dfp_object_lineitem_template->appliedLabels;
		$line_item->effectiveAppliedLabels = $dfp_object_lineitem_template->effectiveAppliedLabels;
		$line_item->disableSameAdvertiserCompetitiveExclusion = $dfp_object_lineitem_template->disableSameAdvertiserCompetitiveExclusion;
		$line_item->notes = $dfp_object_lineitem_template->notes;
		$line_item->isPrioritizedPreferredDealsEnabled = $dfp_object_lineitem_template->isPrioritizedPreferredDealsEnabled;
		$line_item->adExchangeAuctionOpeningPriority = $dfp_object_lineitem_template->adExchangeAuctionOpeningPriority;
		$line_item->customFieldValues = $dfp_object_lineitem_template->customFieldValues;
		$line_item->isSetTopBoxEnabled = $dfp_object_lineitem_template->isSetTopBoxEnabled;
		$line_item->isMissingCreatives = $dfp_object_lineitem_template->isMissingCreatives;
		$line_item->setTopBoxDisplayInfo = $dfp_object_lineitem_template->setTopBoxDisplayInfo;
		$line_item->videoMaxDuration = $dfp_object_lineitem_template->videoMaxDuration;

		
		$goal = new Goal();
		$goal->units = $dfp_lineitem_details['o_o_impressions'];//o_o_impressions
		$goal->unitType = 'IMPRESSIONS';

		$line_item->primaryGoal = $goal;

		
		$line_item->secondaryGoals = $dfp_object_lineitem_template->secondaryGoals;
		$line_item->grpSettings = $dfp_object_lineitem_template->grpSettings;

	

		/*if($start_date == 'IMMEDIATELY')
		{
			$line_item->startDateTimeType = $start_date;
		}
		else
		{
			$line_item->startDateTime = DateTimeUtils::ToDfpDateTime(new DateTime($start_date, new DateTimeZone('America/New_York')));
		}

		$line_item->endDateTime = DateTimeUtils::ToDfpDateTime(new DateTime($end_date, new DateTimeZone('America/New_York')));

		if($creative_sizes != null)
		{
			$line_item->creativePlaceholders = array();
			foreach($creative_sizes as $creative_size)
			{
				$creative_placeholder = new creativePlaceholder();
				$size = explode('x', $creative_size);
				$creative_placeholder->size = new Size($size[0], $size[1], false);
				$line_item->creativePlaceholders[] = $creative_placeholder;
			}
		}*/
		//$line_item->creativeRotationType = $creative_rotation_type;

/*		$inventory_targeting = new InventoryTargeting();
//		$inventory_targeting->targetedPlacementIds = array(1452512);
		$inventory_targeting->targetedAdUnits = array(new AdUnitTargeting("327449430", true));

		$targeting = new Targeting();
		$targeting->inventoryTargeting = $inventory_targeting;
*/
		//$line_item->targeting = $targeting;

		return $line_item;
	}
	public function create_line_item($line_item)
	{
		return $this->create_line_items(array($line_item));
	}

	public function create_line_items($line_items)
	{
		$return_array = array("success" => true, "data" => null);
		try
		{
			$line_item_service = $this->start_service('LineItemService');
			$return_array['data'] = $line_item_service->createLineItems($line_items);
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}

	public function approve_order_with_id($order_id)
	{
		$order_service = $this->start_service('OrderService');

		$statement_builder = new StatementBuilder();
		$statement_builder->Where('id = '.$order_id);
		$action = new ApproveOrders();
		// Perform action.
		$result = $order_service->performOrderAction($action,$statement_builder->ToStatement());
		// Display results.
		if (isset($result) && $result->numChanges > 0) 
		{
			return true;
			//printf("Number of orders deleted: %d\n", $result->numChanges);
		}
		
		return false;
		//printf("No orders were deleted.\n");

	}

	public function generate_third_party_creative(
		$name,
		$advertiser_id,
		$ad_size,
		$ad_tag
	)
	{


		$new_creative = new ThirdPartyCreative();
		$new_creative->name = $name;
		$new_creative->advertiserId = $advertiser_id;
		$new_creative->snippet = $ad_tag;

		$ad_size = explode("x", $ad_size);

		$new_creative->size = new Size($ad_size[0], $ad_size[1]);

		return $new_creative;
	}

	public function create_creative($creative)
	{
		return $this->create_creatives(array($creative, $creative));
	}

	public function create_creatives($creatives)
	{
		$return_array = array("success" => true, "data" => null);
		try
		{
			$creative_service = $this->start_service('CreativeService');
			$return_array['data'] = $creative_service->createCreatives($creatives);
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}

	public function link_creatives_to_line_item($creatives, $line_item_id)
	{
		$return_array = array("success" => true, "data" => null);
		$creative_associations = array();
		foreach($creatives as $creative)
		{
			$line_item_creative_association = new LineItemCreativeAssociation();
			$line_item_creative_association->lineItemId = $line_item_id;
			$line_item_creative_association->creativeId = $creative->id;
			$creative_associations[] = $line_item_creative_association;
		}
		try
		{
			$creative_association_service = $this->start_service('LineItemCreativeAssociationService');
			$return_array['data'] = $creative_association_service->createLineItemCreativeAssociations($creative_associations);
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}

	//Placements or adunits is required
	public function generate_targeting(
		$targeted_placements = null,
		$targeted_ad_units = null,
		$geos_included = null,
		$geos_excluded = null
	)
	{
		$targeting = new Targeting();

		if(!empty($targeted_ad_units) || !empty($targeted_placements))
		{
			$inventory_targeting = new InventoryTargeting();

			if(!empty($targeted_placements))
			{
				$inventory_targeting->targetedPlacementIds = $targeted_placements;
			}
			if(!empty($targeted_ad_units))
			{
				$inventory_targeting->targetedAdUnits = array();
				foreach($targeted_ad_units as $ad_unit)
				{
					$ad_unit_targeting = new AdUnitTargeting();
					$ad_unit_targeting->adUnitId = $ad_unit->adUnitId;
					$ad_unit_targeting->includeDescendants = $ad_unit->includeDescendants;
					$inventory_targeting->targetedAdUnits[] = $ad_unit_targeting;
				}
			}
			$targeting->inventoryTargeting = $inventory_targeting;
		}
		else
		{
			return false;
		}

		if(!empty($geos_included) || !empty($geos_excluded))
		{
			$geo_targeting = new GeoTargeting();
			if(!empty($geos_included))
			{
				$geo_targeting->targetedLocations = array();
				foreach($geos_included as $geo)
				{
					$postal_code_location = new DfpLocation();
					$postal_code_location->id = $geo;
					$geo_targeting->targetedLocations[] = $postal_code_location;
				}
			}

			if(!empty($geos_excluded))
			{
				$geo_targeting->excludedLocations = array();
				foreach($geos_excluded as $geo_no)
				{
					$geo_targeting->excludedLocations[] = $geo_no;
				}
			}
			$targeting->geoTargeting = $geo_targeting;
		}
		return $targeting;
	}

	public function get_dfp_advertisers($for_select2 = false, $search_param = null)
	{
		$company_service = $this->start_service('CompanyService');
		$return_array = array("success" => true, "data" => null);

		try
		{
			$statement = new StatementBuilder();
			if(!empty($search_param))
			{
				$statement->Where('type = :type AND (id = :id OR name LIKE :name)')
				->OrderBy('id ASC')
				->WithBindVariableValue('type', 'ADVERTISER')
				->WithBindVariableValue('id', $search_param)
				->WithBindVariableValue('name', '%'.$search_param.'%');
			}
			else
			{
				$statement->Where('type = :type')
				->OrderBy('id ASC')
				->WithBindVariableValue('type', 'ADVERTISER');
			}

			$advertisers = $company_service->getCompaniesByStatement(
			$statement->ToStatement());	

			$advertiser_list = $advertisers->results;
			if($for_select2)
			{
				$return_list = [array('id' => "new_dfp_advertiser", 'text' => "*New*")];
				foreach($advertiser_list as $advertiser)
				{
					$return_list[] = array('id' => $advertiser->id, 'text' =>$advertiser->name." (".$advertiser->id.")");
				}
				$return_array['data'] = $return_list;
			}
			else
			{
				$return_array['data'] = $advertisers->results;
			}
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}

	public function generate_advertiser_with_name($name)
	{
		$advertiser = new Company();
		$advertiser->name = $name;
		$advertiser->type = "ADVERTISER";
		return $advertiser;
	}

	public function create_dfp_advertiser($advertiser)
	{
		return $this->create_dfp_advertisers(array($advertiser));
	}

	public function create_dfp_advertisers($advertisers)
	{
		$company_service = $this->start_service('CompanyService');
		$return_array = array("success" => true, "data" => null);
		try
		{
			$advertisers = $company_service->createCompanies($advertisers);
			$return_array['data'] = $advertisers;
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;		
	}

	public function save_dfp_order_as_template($order, $name)
	{
		return $this->ci->dfp_model->save_dfp_order_as_template($order, $name);
	}
	public function save_dfp_line_item_as_template($line_item, $name)
	{
		return $this->ci->dfp_model->save_dfp_line_item_as_template($line_item, $name);
	} 

	public function get_delivery_forecast_for_line_item($line_item)
	{
		return get_delivery_forecast_for_line_items(array($line_item));
	}

	public function get_delivery_forecast_for_line_items($line_items)
	{
		$forecast_service = $this->start_service('ForecastService');
		$return_array = array("success" => true, "data" => null);

		try
		{
			$options = new DeliveryForecastOptions();

			$forecast = $forecast_service->getDeliveryForecastByIds($line_items, $options);
			$return_array['data'] = $forecast->lineItemDeliveryForecasts;
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}

	public function get_availability_forecast(
		/*$advertiser_id,*/
		$start_date,
		$end_date,

		$cost_per_unit,

		$goal_num_units,
		$goal_unit,
		$targeting
	)
	{
		$forecast_service = $this->start_service('ForecastService');
		$network_service = $this->start_service('NetworkService');
		$return_array = array("success" => true, "data" => null);
		try
		{
			$root_ad_unit_id = $network_service->getCurrentNetwork()->effectiveRootAdUnitId;
			$inventory_targeting = new InventoryTargeting();
			$ad_unit_targeting = new AdUnitTargeting();
			$ad_unit_targeting->adUnitId = $root_ad_unit_id;
			$ad_unit_targeting->includeDescendants = true;

			$inventory_targeting->targetedAdUnits = array($ad_unit_targeting); 

			$targeting->inventoryTargeting = $inventory_targeting;

			$line_item = new LineItem();
			$line_item->targeting = $targeting;
			$line_item->lineItemType = "STANDARD";
			$line_item->costType = "CPM";
			$line_item->costPerUnit = new Money ('USD', $cost_per_unit*1000000);

			if($start_date == 'IMMEDIATELY')
			{
				$line_item->startDateTimeType = $start_date;
			}
			else
			{
				$line_item->startDateTime = DateTimeUtils::ToDfpDateTime(new DateTime($start_date, new DateTimeZone('America/New_York')));
			}
			$line_item->endDateTime = DateTimeUtils::ToDfpDateTime(new DateTime($end_date, new DateTimeZone('America/New_York')));

			$goal = new Goal();
			$goal->units = $goal_num_units;
			$goal->unitType = $goal_unit;
			$goal->goalType = "LIFETIME";
			$line_item->primaryGoal = $goal;


			$prospective_line_item = new ProspectiveLineItem();
			$prospective_line_item->lineItem = $line_item;
			//$prospective_line_item->advertiserId = $advertiser_id;

			$options = new AvailabilityForecastOptions();
			$options->includeContendingLineItems = true;
			$options->includeTargetingCriteriaBreakdowwn = true;

			$forecast = $forecast_service->getAvailabilityForecast($prospective_line_item, $options);
			$return_array['data'] = $forecast;
		}
		catch (Exception $e)
		{
			$return_array['success'] = false;
			$return_array['data'] = $e->getMessage();
		}
		return $return_array;
	}

	
	//convert_zips_to_google_api_location_ids
	//Expected Input:
	//		- $zips: an array of ZIP codes or Canadian FSAs
	//Expected Output:
	//		- An array of Google Geographic Location IDs to use in Geo Targeting
	//		- On failure, will return FALSE
	public function convert_zips_to_google_api_location_ids($zips)
	{

		$pql_service = $this->start_service('PublisherQueryLanguageService');
		if(!empty($zips))
		{

			$statement_builder = new StatementBuilder();

			$zip_wheres = array();
			foreach($zips as $index => $zip)
			{
				$zip_wheres[] = ":zip".$index;
				$statement_builder->WithBindVariableValue('zip'.$index, $zip);
			}
			$zip_where = "AND name IN (".implode(', ', $zip_wheres).")";

			
			$statement_builder->Select('Id, name')
			->From('Geo_Target')
			->Where('Type = :Type AND Targetable = true AND (CountryCode = :UnitedStates OR CountryCode = :Canada) '.$zip_where)
			->OrderBy('CountryCode ASC, Name ASC')
			->Limit(StatementBuilder::SUGGESTED_PAGE_LIMIT)
			->WithBindVariableValue('Type', 'POSTAL_CODE')
			->WithBindVariableValue('UnitedStates', "US")
			->WithBindVariableValue('Canada', "CA");

			$result = $pql_service->select($statement_builder->ToStatement());
			$result_rows = $result->rows;
			if(!empty($result_rows))
			{
				$zip_ids = array();
				foreach($result_rows as $row)
				{
					$zip_ids[] = $row->values[0]->value;
				}
				return $zip_ids;
			}
			else
			{
				return false;
			}
		}
		return false;
	}

	public function generate_report($report_date, $destination_folders_array, $frequence_brighthouse_adgroup_ids)
	{
		$this->report_service = $this->start_service('ReportService');
		
		// Set Report Download Format
		$report_download_options = new ReportDownloadOptions();
		$report_download_options->exportFormat = 'CSV_DUMP';
		$report_download_options->useGzipCompression = FALSE;
		
		// For Cities
		$city_csv_status = $this->get_dfp_report_cities($report_date, $report_download_options, $destination_folders_array['city_folder'], $frequence_brighthouse_adgroup_ids);

		// For Sites 
		$site_csv_status = $this->get_dfp_report_sites($report_date, $report_download_options, $destination_folders_array['site_folder'], $frequence_brighthouse_adgroup_ids);
		
		//For Size
		$size_csv_status = $this->get_dfp_report_size($report_date, $report_download_options, $destination_folders_array['size_folder'], $frequence_brighthouse_adgroup_ids);
		
		//For Creatives
		$creative_csv_status = $this->get_dfp_report_creatives($report_date, $report_download_options, $destination_folders_array['creative_folder'], $frequence_brighthouse_adgroup_ids);
		
		if($city_csv_status == false || $site_csv_status == false || $size_csv_status == false || $creative_csv_status == false)
		{
			return false;
		}
		return true;
		
	}
	
	public function get_dfp_report_cities($report_date, $report_download_option, $folders_name, $frequence_brighthouse_adgroup_ids)
	{
		$cities_download_url = '';
		
		$reportQuery = new ReportQuery();
		
		$reportQuery->dimensions = array('LINE_ITEM_ID','LINE_ITEM_NAME','ORDER_ID', 'ORDER_NAME', 'CITY_NAME', 'CITY_ID', 'REGION_NAME', 'REGION_ID');
		$reportQuery->columns = array('AD_SERVER_IMPRESSIONS', 'AD_SERVER_CLICKS');
		
		$statementBuilder = new StatementBuilder();
		$statementBuilder->Where("line_item_id IN (".$frequence_brighthouse_adgroup_ids.")");

		$reportQuery->statement = $statementBuilder->ToStatement();
		
		$report_date = strtotime($report_date);
		$reportQuery->dateRangeType = 'CUSTOM_DATE';
		$reportQuery->startDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
			    $reportQuery->endDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
		
		$cities_download_url = $this->generate_download_url($reportQuery,$report_download_option);
		if($cities_download_url['is_success'])
		{
			$csv_status = $this->save_to_csv($cities_download_url['url'],$folders_name,'/report_creatives');
			return $csv_status;
		}	
		else
		{
			throw(new Exception($cities_download_url['message']));
		}
	}
	
	public function get_dfp_report_sites($report_date, $report_download_option, $folders_name, $frequence_brighthouse_adgroup_ids)
	{
		$sites_download_url = '';
		
		$reportQuery = new ReportQuery();
		
		$reportQuery->dimensions = array('LINE_ITEM_ID','LINE_ITEM_NAME','ORDER_ID', 'ORDER_NAME','AD_UNIT_ID','AD_UNIT_NAME');
		$reportQuery->columns = array('AD_SERVER_IMPRESSIONS', 'AD_SERVER_CLICKS','VIEW_THROUGH_CONVERSIONS','CLICK_THROUGH_CONVERSIONS');
		
		$statementBuilder = new StatementBuilder();
		$statementBuilder->Where("line_item_id IN (".$frequence_brighthouse_adgroup_ids.")");

		$reportQuery->statement = $statementBuilder->ToStatement();
		$report_date = strtotime($report_date);
		$reportQuery->dateRangeType = 'CUSTOM_DATE';
		$reportQuery->startDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
			    $reportQuery->endDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
			    
		$sites_download_url = $this->generate_download_url($reportQuery,$report_download_option);
		if($sites_download_url['is_success'])
		{
			$csv_status = $this->save_to_csv($sites_download_url['url'],$folders_name,'/report_creatives');
			return $csv_status;
		}	
		else
		{
			throw(new Exception($sites_download_url['message']));
		}
		
	}
	
	public function get_dfp_report_size($report_date, $report_download_option, $folders_name, $frequence_brighthouse_adgroup_ids)
	{
		$size_download_url = '';
		
		$reportQuery = new ReportQuery();
		
		$reportQuery->dimensions = array('LINE_ITEM_ID','LINE_ITEM_NAME','ORDER_ID', 'ORDER_NAME','CREATIVE_SIZE');
		$reportQuery->columns = array('AD_SERVER_IMPRESSIONS', 'AD_SERVER_CLICKS','VIEW_THROUGH_CONVERSIONS','CLICK_THROUGH_CONVERSIONS');
		
		$statementBuilder = new StatementBuilder();
		$statementBuilder->Where("line_item_id IN (".$frequence_brighthouse_adgroup_ids.")");

		$reportQuery->statement = $statementBuilder->ToStatement();
		$report_date = strtotime($report_date);
		$reportQuery->dateRangeType = 'CUSTOM_DATE';
		$reportQuery->startDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
			    $reportQuery->endDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
		
		$size_download_url = $this->generate_download_url($reportQuery,$report_download_option);
		if($size_download_url['is_success'])
		{
			$csv_status = $this->save_to_csv($size_download_url['url'],$folders_name,'/report_creatives');
			return $csv_status;
		}	
		else
		{
			throw(new Exception($size_download_url['message']));
		}
		
	}
	
	public function get_dfp_report_creatives($report_date, $report_download_option, $folders_name, $frequence_brighthouse_adgroup_ids)
	{
		$creative_download_url = '';
		
		$reportQuery = new ReportQuery();
		
		$reportQuery->dimensions = array('LINE_ITEM_ID','LINE_ITEM_NAME','ORDER_ID', 'ORDER_NAME','CREATIVE_ID','CREATIVE_NAME');
		$reportQuery->columns = array('AD_SERVER_IMPRESSIONS', 'AD_SERVER_CLICKS','VIEW_THROUGH_CONVERSIONS','CLICK_THROUGH_CONVERSIONS');
		
		$statementBuilder = new StatementBuilder();
		$statementBuilder->Where("line_item_id IN (".$frequence_brighthouse_adgroup_ids.")");

		$reportQuery->statement = $statementBuilder->ToStatement();
		$report_date = strtotime($report_date);
		$reportQuery->dateRangeType = 'CUSTOM_DATE';
		$reportQuery->startDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
		$reportQuery->endDate = DateTimeUtils::ToDfpDateTime(
				new DateTime(date("Y-m-d h:i:sa", $report_date)))->date;
		
		$creative_download_url = $this->generate_download_url($reportQuery,$report_download_option);
		if($creative_download_url['is_success'])
		{
			$csv_status = $this->save_to_csv($creative_download_url['url'],$folders_name,'/report_creatives');
			return $csv_status;
		}	
		else
		{
			throw(new Exception($creative_download_url['message']));
		}
	}
	
	public function generate_download_url($reportQuery, $report_download_option)
	{
		$result = array();
		$reportJob = new ReportJob();
		$reportJob->reportQuery = $reportQuery;
		try
		{
			$reportJob = $this->report_service->runReportJob($reportJob);

			$reportDownloader = new ReportDownloader($this->report_service, $reportJob->id);

			$reportDownloader->waitForReportReady();

		        $status = $this->report_service->getReportJobStatus($reportJob->id);
			if($status == 'COMPLETED')
			{
				$result['is_success'] = true;
				$result['url'] = $this->report_service->getReportDownloadUrlWithOptions($reportJob->id,$report_download_option);
			}
			else
			{
				$result['is_success'] = false;
				$result['message'] = 'Failed to generate Download Url';
			}
		}
		catch (Exception $e)
		{
			$result['is_success'] = false;
			$error_message = json_decode($e->getMessage());
			$result['message'] = 'Unable to execute job : '.$error_message->error_description;
		}
		return $result;
	}
	
	public function save_to_csv($url,$folder_name,$file_name)
	{	
		$data = file_get_contents($url);
		
		$first_column_length = strpos($data, PHP_EOL);
		$column_text = substr($data, 0, $first_column_length);
		$column_text = str_replace('Dimension.','',$column_text);
		$column_text = str_replace('Column.','',$column_text);
		$column_text = str_replace('LINE_ITEM_NAME','Line Item',$column_text);
		$column_text = str_replace('LINE_ITEM_ID','Line Item ID',$column_text);
		$data = substr_replace($data, $column_text, 0, $first_column_length);
		
		$file = fopen($folder_name.$file_name.".csv","w");
		
		fwrite($file,$data);
		
		fclose($file);
		return true;
	}
	
	public function delete_order_from_dfp($order_id)
	{
		$order_service = $this->start_service('OrderService');

		$statement_builder = new StatementBuilder();
		$statement_builder->Where('id = '.$order_id);
		$action = new DeleteOrders();
		// Perform action.
		$result = $order_service->performOrderAction($action,$statement_builder->ToStatement());
		// Display results.
		if (isset($result) && $result->numChanges > 0) 
		{
			return true;
			//printf("Number of orders deleted: %d\n", $result->numChanges);
		}
		
		return false;
		//printf("No orders were deleted.\n");
		
	}
}
?>
