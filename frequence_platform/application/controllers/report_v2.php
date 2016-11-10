<?php

/*
--- Glossary ---
product: The values populating the tabs.  Examples: "Targeted Display", "Pre-Roll", "Targeted Inventory"
subproduct: The values populating the nav pills.  Examples: "Geography" and "Screenshots" in "Targeted Display"

States:
 - is accessible:  If true, the value will contribute to what the users sees.  If false the user wouldn't know it exists.
 - is visible:     If true, the UI element is present/visible but not necessarily interactable
 - is active:      If true, the thing can be engaged with
*/

class report_v2 extends CI_Controller
{
	private $products_and_subproducts_config = null;
	private $subfeature_access_permissions = false;

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array(
			'map',
			'session',
			'tank_auth',
			'vl_platform',
			'csv'
		));
		$this->load->model(array(
			'report_v2_model',
			'tmpi_model',
			'vl_platform_model',
			'td_uploader_model'
		));
		$this->load->helper(array(
			'url',
			'report_v2_controller_helper',
			'mailgun',
			'mixpanel',
			'vl_ajax_helper'
		));

		// The Frequence Partner id is a special case '1'
		define('k_brandcdn_partner_id', 1);
		define('k_vl_report_num_decimal_places', 2);
	}

	// parallel code exists in javascript
	public function get_starting_tabs_html($organization_products)
	{
		$tabs_html = '';
		$products_brand_text = array();
		$products_html = '<div id="products_nav_section" class="products_nav_section_on">';
		$lift_link_html = '';
		if ($this->subfeature_access_permissions['is_lift_report_accessible'])
		{
			$lift_link_html = '<li id="overview_lift"><a href="#overview_lift" class="topparentnav lift disabled">Lift</a></li>';
		}
		foreach($organization_products as $product)
		{
			if (strtolower($product['product_type']) === 'overview' || strtolower($product['product_brand']) === 'overview')
			{
				$tabs_html = '
					<li style="margin-top:25px;margin-bottom:15px;"><div class="ruler"></div></li>
					<li id="overview_product">
						<a href="#overview_product" data-event-name="' . $product['event_friendly_name'] . '"  class="topparentnav topparentnavclicked" style="overflow:visible;position:relative;">
							Overview
							<div id="digital_overview_button"  style="top: 10%;right: 5px;position: absolute;">
								<i class="digital_overview_button_no_hover icon-gear" data-content="Product Summary" data-trigger="hover" data-placement="right"></i>
							</div>
						</a>
					</li>'.
					$lift_link_html
					.'<li>
						<a href="#" class="productnav topparentnav">Products</a>
					</li>
				';
			}
			else
			{
				$image_html = "";
				if(!empty($product['image_path']))
				{
					$image_html = '<img src="/assets/img/'.$product['image_path'].'" width="10px;" height="10px;" />';
				}

				$brand_html = "";
				if(!empty($product['product_brand']))
				{
					$brand_html = '<span class="product_brand">'.$product['product_brand'].'</span>';
				}
				else
				{
					$product['product_brand'] = "";
				}

				$products_html .= '
					<li>
					<a href="#" id="'.$product['html_element_id'].'" class="disabled product_child" onclick="product_child_clicked(this,event);disable_tab_click(event); return false;">
						'.$brand_html.'&nbsp;&nbsp;'.$product['product_type'].'
					</a>
					</li>
				';
				$products_brand_text[$product['html_element_id']] = $product['product_brand']." ".$product['product_type'];
			}
		}
		$products_html.="</div>";
		$tabs_html.=$products_html;

		$response['tabs_html'] =  $tabs_html;
		$response['products_brand_text'] =  $products_brand_text;

		return $response;
	}

	private function init_access_and_configs($user_id)
	{
		$this->subfeature_access_permissions = $this->subfeature_access_permissions ?: $this->report_v2_model->get_subfeature_access_permissions(
			$user_id
		);

		$product_tab_visuals_by_organization = array(
			report_campaign_organization::frequence => array(
				report_product_tab_html_id::overview_product => array(
					'html_element_id' => report_product_tab_html_id::overview_product,
					'image_path' => '',
					'product_brand' => 'Overview',
					'product_type' => '',
					'event_friendly_name' => 'Overview'
				),
				report_product_tab_html_id::targeted_tv_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_tv_product,
					'image_path' => '',
					'product_brand' => spectrum_product_names::tv,
					'product_type' => '',
					'overview_map_id' => 'tv',
					'event_friendly_name' => 'TV'
				),
				report_product_tab_html_id::display_product => array(
					'html_element_id' => report_product_tab_html_id::display_product,
					'image_path' => '',
					'product_brand' => frequence_product_names::visual_frequence_display,
					'product_type' => '',
					'overview_map_id' => 'display',
					'event_friendly_name' => 'Display'
				),
				report_product_tab_html_id::pre_roll_product => array(
					'html_element_id' => report_product_tab_html_id::pre_roll_product,
					'image_path' => '',
					'product_brand' => frequence_product_names::pre_roll,
					'product_type' => '',
					'overview_map_id' => 'preroll',
					'event_friendly_name' => 'PreRoll'
				),
				report_product_tab_html_id::targeted_clicks_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_clicks_product,
					'image_path' => '',
					'product_brand' => tmpi_product_names::clicks,
					'product_type' => '',
					'event_friendly_name' => 'Clicks'
				),
				report_product_tab_html_id::targeted_inventory_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_inventory_product,
					'image_path' => '',
					'product_brand' => tmpi_product_names::inventory,
					'product_type' => '',
					'event_friendly_name' => 'Inventory'
				),
				report_product_tab_html_id::targeted_directories_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_directories_product,
					'image_path' => '',
					'product_brand' => tmpi_product_names::directories,
					'product_type' => '',
					'event_friendly_name' => 'Directories'
				),
				report_product_tab_html_id::targeted_content_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_content_product,
					'image_path' => '',
					'product_brand' => carmercial_product_names::content,
					'product_type' => '',
					'event_friendly_name' => 'Content'
				)
			),
			report_campaign_organization::tmpi => array(
				report_product_tab_html_id::overview_product => array(
					'html_element_id' => report_product_tab_html_id::overview_product,
					'image_path' => 'charter_tmpi/overview.png',
					'product_brand' => 'Campaigns',
					'product_type' => 'Overview',
					'event_friendly_name' => 'Overview'
				),
				report_product_tab_html_id::targeted_tv_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_tv_product,
					'image_path' => 'charter_tmpi/television.png',
					'product_brand' => '',
					'product_type' => spectrum_product_names::tv,
					'overview_map_id' => 'tv',
					'event_friendly_name' => 'TV'
				),
				report_product_tab_html_id::display_product => array(
					'html_element_id' => report_product_tab_html_id::display_product,
					'image_path' => 'charter_tmpi/devices.png',
					'product_brand' => '',
					'product_type' => frequence_product_names::visual_spectrum_display,
					'overview_map_id' => 'display',
					'event_friendly_name' => 'Display'
				),
				report_product_tab_html_id::pre_roll_product => array(
					'html_element_id' => report_product_tab_html_id::pre_roll_product,
					'image_path' => 'charter_tmpi/preroll.png',
					'product_brand' => '',
					'product_type' => 'PreRoll',
					'overview_map_id' => 'preroll',
					'event_friendly_name' => 'PreRoll'
				),
				report_product_tab_html_id::targeted_clicks_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_clicks_product,
					'image_path' => 'charter_tmpi/visits.png',
					'product_brand' => '',
					'product_type' => tmpi_product_names::clicks,
					'event_friendly_name' => 'Clicks'
				),
				report_product_tab_html_id::targeted_inventory_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_inventory_product,
					'image_path' => 'charter_tmpi/leads.png',
					'product_brand' => '',
					'product_type' => tmpi_product_names::inventory,
					'event_friendly_name' => 'Inventory'
				),
				report_product_tab_html_id::targeted_directories_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_directories_product,
					'image_path' => 'charter_tmpi/directories.png',
					'product_brand' => '',
					'product_type' => tmpi_product_names::directories,
					'event_friendly_name' => 'Directories'
				),
				report_product_tab_html_id::targeted_content_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_content_product,
					'image_path' => 'charter_tmpi/content.png',
					'product_brand' => '',
					'product_type' => carmercial_product_names::content,
					'event_friendly_name' => 'Content'
				)
			)
		);

		$shared_subproduct_tmpi_leads_totals = array(
			'html_element_id' => 'leads_totals_subproduct',
			'title' => 'Leads<br>Totals',
			'is_accessible' => true,
			'get_data_function_name' => 'get_tmpi_targeted_inventory_leads_totals_data',
			'can_download_csv' => false,
			'subproduct_csv_file_segment' => 'leads_totals'
		);
		$shared_subproduct_tmpi_leads_details = array(
			'html_element_id' => 'leads_details_subproduct',
			'title' => 'Leads',
			'is_accessible' => true,
			'get_data_function_name' => 'get_tmpi_targeted_inventory_leads_details_data',
			'can_download_csv' => false,
			'subproduct_csv_file_segment' => 'leads_details'
		);
		$shared_subproduct_tmpi_search_profile_activity = array(
			'html_element_id' => 'local_search_profile_activity_subproduct',
			'title' => 'Local Search',
			'is_accessible' => true,
			'get_data_function_name' => 'get_tmpi_targeted_directories_local_search_profile_activity_data',
			'can_download_csv' => false,
			'subproduct_csv_file_segment' => 'local_search_data'
		);

		$this->products_and_subproducts_config = array(
			report_product_tab_html_id::overview_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'overview',
				'subproducts_data' => array(
					'data_view_class' => 'overview_subproduct',
					'subproduct_items' => array(
						'overview_subproduct' => array(
							'html_element_id' => 'overview_subproduct',
							'title' => 'Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_overview_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'overview'
						),
					),
				),
			),
			report_product_tab_html_id::targeted_tv_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'tv',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_tv_subproduct',
					'subproduct_items' => array(
						'tv_airings_subproduct' => array(
							'html_element_id' => 'tv_airings_subproduct',
							'title' => 'UPCOMING<br>AIRINGS',
							'is_accessible' => $this->subfeature_access_permissions['are_tv_schedules_accessible'],
							'get_data_function_name' => 'get_spectrum_targeted_tv_network_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'tv_airings',
							'event_friendly_name' => 'TV Upcoming Airings subproduct'
						),
						'tv_creative_subproduct' => array(
							'html_element_id' => 'tv_creative_subproduct',
							'title' => 'CREATIVE<br>DETAILS',
							'is_accessible' => $this->subfeature_access_permissions['is_expanded_tv_reporting_available'],
							'get_data_function_name' => 'get_spectrum_targeted_tv_creative_impressions_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'tv_creatives',
							'event_friendly_name' => 'TV Delivered Creatives subproduct'
						),
						'tv_zones_subproduct' => array(
							'html_element_id' => 'tv_zones_subproduct',
							'title' => 'DELIVERED<br>ZONES',
							'is_accessible' => $this->subfeature_access_permissions['is_expanded_tv_reporting_available'],
							'get_data_function_name' => 'get_spectrum_targeted_tv_zones_impressions_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'tv_zones',
							'event_friendly_name' => 'TV Delivered Zones subproduct'
						),
						'tv_networks_subproduct' => array(
							'html_element_id' => 'tv_networks_subproduct',
							'title' => 'DELIVERED<br>NETWORKS',
							'is_accessible' => $this->subfeature_access_permissions['is_expanded_tv_reporting_available'],
							'get_data_function_name' => 'get_spectrum_targeted_tv_network_impressions_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'tv_networks',
							'event_friendly_name' => 'TV Delivered Networks subproduct'
						),
						'tv_programs_subproduct' => array(
							'html_element_id' => 'tv_programs_subproduct',
							'title' => 'DELIVERED<br>PROGRAMS',
							'is_accessible' => $this->subfeature_access_permissions['is_expanded_tv_reporting_available'],
							'get_data_function_name' => 'get_spectrum_targeted_tv_network_impressions_data_by_program',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'tv_programs',
							'event_friendly_name' => 'TV Delivered Programs subproduct'
						)
					)
				)
			),
			report_product_tab_html_id::display_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'display',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_display_subproduct',
					'subproduct_items' => array(
						'creatives_subproduct' => array(
							'html_element_id' => 'creatives_subproduct',
							'title' => 'Creatives',
							'is_accessible' => $this->subfeature_access_permissions['are_display_creatives_accessible'],
							'get_data_function_name' => 'get_display_creatives_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'creatives',
							'event_friendly_name' => 'Display subproduct'
						),
						'geography_subproduct' => array(
							'html_element_id' => 'geography_subproduct',
							'title' => 'Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_geography_data_no_pre_roll_yes_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'geography',
							'event_friendly_name' => 'Display subproduct'
						),
						'placements_subproduct' => array(
							'html_element_id' => 'placements_subproduct',
							'title' => 'Placements',
							'is_accessible' => $this->subfeature_access_permissions['are_placements_accessible'],
							'get_data_function_name' => 'get_placements_data_no_pre_roll_yes_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'placements',
							'event_friendly_name' => 'Display subproduct'
						),
						'ad_sizes_subproduct' => array(
							'html_element_id' => 'ad_sizes_subproduct',
							'title' => 'Ad Sizes',
							'is_accessible' => $this->subfeature_access_permissions['are_ad_sizes_accessible'],
							'get_data_function_name' => 'get_ad_sizes_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'ad_sizes',
							'event_friendly_name' => 'Display subproduct'
						),
						'interactions_subproduct' => array(
							'html_element_id' => 'interactions_subproduct',
							'title' => 'Engagements',
							'is_accessible' => $this->subfeature_access_permissions['are_ad_interactions_accessible'],
							'get_data_function_name' => 'get_interactions_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'engagements',
							'event_friendly_name' => 'Display subproduct'
						),
						'screenshots_subproduct' => array(
							'html_element_id' => 'screenshots_subproduct',
							'title' => 'Screenshots',
							'is_accessible' => $this->subfeature_access_permissions['are_screenshots_accessible'],
							'get_data_function_name' => 'get_screenshots_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'screenshots',
							'event_friendly_name' => 'Display subproduct'
						)
					)
				)
			),
			'graph' => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'graph',
				'subproducts_data' => array(
					'data_view_class' => 'graph_subproduct',
					'subproduct_items' => array(
						'graph' => array(
							'html_element_id' => 'graph',
							'title' => 'Graph',
							'is_accessible' => false,
							'get_data_function_name' => 'get_graph_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'summary'
						)
					)
				)
			),
			report_product_tab_html_id::pre_roll_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'preroll',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_preroll_subproduct',
					'subproduct_items' => array(
						'geography_subproduct' => array(
							'html_element_id' => 'geography_subproduct',
							'title' => 'Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_geography_data_yes_pre_roll_no_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'geography',
							'event_friendly_name' => 'PreRoll subproduct'
						),
						'placements_subproduct' => array(
							'html_element_id' => 'placements_subproduct',
							'title' => 'Placements',
							'is_accessible' => $this->subfeature_access_permissions['are_placements_accessible'],
							'get_data_function_name' => 'get_placements_data_yes_pre_roll_no_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'placements',
							'event_friendly_name' => 'PreRoll subproduct'
						),
						'pre_roll_subproduct' => array(
							'html_element_id' => 'pre_roll_subproduct',
							'title' => 'Video Stats',
							'is_accessible' => $this->subfeature_access_permissions['is_pre_roll_accessible'],
							'get_data_function_name' => 'get_video_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'pre_roll',
							'event_friendly_name' => 'PreRoll subproduct'
						),
					)
				)
			),
			report_product_tab_html_id::targeted_clicks_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'clicks',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_clicks_subproduct',
					'subproduct_items' => array(
						'clicks_subproduct' => array(
							'html_element_id' => 'clicks_subproduct',
							'title' => tmpi_product_names::clicks.' Totals',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_clicks_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'clicks',
							'event_friendly_name' => 'Clicks subproduct'
						),
						'geo_clicks_subproduct' => array(
							'html_element_id' => 'geo_clicks_subproduct',
							'title' => 'Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_geo_clicks_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'geo_clicks',
							'event_friendly_name' => 'Clicks subproduct'
						)
					)
				)
			),
			report_product_tab_html_id::targeted_inventory_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'inventory',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_inventory_subproduct',
					'subproduct_items' => array(
						'search_and_listings_subproduct' => array(
							'html_element_id' => 'search_and_listings_subproduct',
							'title' => 'Vehicles',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_search_and_listings_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'search_and_listings',
							'combined_subproducts' => array(
								'top_vehicles_subproduct' => array(
									'html_element_id' => 'top_vehicles_subproduct',
									'title' => 'Vehicles',
									'is_accessible' => false,
									'get_data_function_name' => 'get_tmpi_targeted_inventory_top_vehicles_data',
									'can_download_csv' => false,
									'subproduct_csv_file_segment' => 'top_vehicles'
								)
							),
							'event_friendly_name' => 'Inventory subproduct'
						),
						'leads_details_subproduct' => $shared_subproduct_tmpi_leads_details,
						'vehicles_inventory_subproduct' => array(
							'html_element_id' => 'vehicles_inventory_subproduct',
							'title' => 'Inventory',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_vehicles_inventory_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'vehicles_inventory',
							'event_friendly_name' => 'Inventory subproduct'
						),
						'price_breakdown_subproduct' => array(
							'html_element_id' => 'price_breakdown_subproduct',
							'title' => 'Vehicle<br>Price Breakdown',
							'is_accessible' => false,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_price_breakdown_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'price_breakdown',
							'event_friendly_name' => 'Price breakdown subproduct'
						),
						'smart_clicks_subproduct' => array(
							'html_element_id' => 'smart_clicks_subproduct',
							'title' => 'Smart Ads',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_smart_ads_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'smart_ad_clicks',
							'combined_subproducts' => array(
								'smart_geo_clicks_subproduct' => array(
									'html_element_id' => 'smart_geo_clicks_subproduct',
									'title' => 'Smart Ads<br>Geography',
									'is_accessible' => true,
									'get_data_function_name' => 'get_tmpi_targeted_smart_ads_geo_data',
									'can_download_csv' => false,
									'subproduct_csv_file_segment' => 'smart_ad_geo_clicks'
								)
							),
							'event_friendly_name' => 'Smart clicks subproduct'
						),
						'local_search_profile_activity_subproduct' => $shared_subproduct_tmpi_search_profile_activity
					)
				)
			),
			report_product_tab_html_id::targeted_directories_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'directories',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_directories_subproduct',
					'subproduct_items' => array(
						'leads_totals_subproduct' => array(
							'html_element_id' => 'leads_totals_subproduct',
							'title' => 'Leads',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_leads_totals_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'leads_totals',
							'combined_subproducts' => array(
								'leads_details_subproduct' => $shared_subproduct_tmpi_leads_details
							),
							'event_friendly_name' => 'Directories subproduct'
						),
						'local_search_profile_activity_subproduct' => $shared_subproduct_tmpi_search_profile_activity
					)
				)
			),
			report_product_tab_html_id::targeted_content_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'content',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_content_subproduct',
					'subproduct_items' => array(
						'carmercial_subproduct' => array(
							'html_element_id' => 'carmercial_subproduct',
							'title' => 'Content',
							'is_accessible' => true,
							'get_data_function_name' => 'get_carmercial_targeted_content_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'content'
						)
					)
				)
			)
		);

		$tab_data_visuals = $product_tab_visuals_by_organization[report_campaign_organization::frequence];
		if($this->subfeature_access_permissions['is_tmpi_accessible'])
		{
			$tab_data_visuals = $product_tab_visuals_by_organization[report_campaign_organization::tmpi];
		}

		foreach($tab_data_visuals as $tab_data)
		{
			$this->products_and_subproducts_config[$tab_data['html_element_id']]['tab'] = $tab_data;
		}

	}

	private function get_accessible_products()
	{
		$products_to_use = array();

		// Always show Overview
		$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::overview_product]['tab'];

		//Arriving out of order, here.
		if($this->subfeature_access_permissions['is_tmpi_accessible'])
		{
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_tv_product]['tab'];
		}

		// Always show Display and Pre-Roll
		$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::display_product]['tab'];
		$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::pre_roll_product]['tab'];

		if($this->subfeature_access_permissions['is_tmpi_accessible'])
		{
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_clicks_product]['tab'];
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_directories_product]['tab'];
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_inventory_product]['tab'];
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_content_product]['tab'];
		}

		return $products_to_use;
	}

	private function build_active_subproduct_data(
		$input_active_product,
		$input_active_subproduct,
		$advertiser_id,
		$campaign_set,
		$start_date,
		$end_date,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$result = null;

		if(array_key_exists($input_active_product, $this->products_and_subproducts_config))
		{
			$product_config = $this->products_and_subproducts_config[$input_active_product]['subproducts_data'];
			$subproduct_items = $product_config['subproduct_items'];

			if(empty($input_active_subproduct))
			{
				reset($subproduct_items);
				$input_active_subproduct = key($subproduct_items);
			}

			if(array_key_exists($input_active_subproduct, $subproduct_items))
			{
				$result = array();
				$subproduct_item = $subproduct_items[$input_active_subproduct];
				$result[] = $this->build_subproduct_data($subproduct_item,
							$input_active_subproduct,
							$advertiser_id,
							$campaign_set,
							$start_date,
							$end_date,
							$input_active_product,
							$subfeature_access_permissions,
							$build_data_type,
							$timezone_offset);

				if (isset($subproduct_items[$input_active_subproduct]['combined_subproducts']))
				{
					$combined_sub_products = $subproduct_items[$input_active_subproduct]['combined_subproducts'];

					foreach ($combined_sub_products as $combined_sub_product_item)
					{
						$combined_sub_product_item_result = $this->build_subproduct_data($combined_sub_product_item,
									$input_active_subproduct,
									$advertiser_id,
									$campaign_set,
									$start_date,
									$end_date,
									$input_active_product,
									$subfeature_access_permissions,
									$build_data_type,
									$timezone_offset);

						if (isset($combined_sub_product_item_result))
						{
							$subproduct_data_set = &$combined_sub_product_item_result['subproduct_data_sets'][0];
							$subproduct_data_set['display_selector'] = $subproduct_data_set['display_selector']."_combined_sub_product";
							$combined_sub_product_item_result['subproduct_html_scaffolding'] = '
													<div id="'.substr($subproduct_data_set['display_selector'],1).'" class="geography">
													</div>
												';
						}

						$result[] = $combined_sub_product_item_result;
					}
				}
			}
		}

		return $result;
	}

	private function build_subproduct_data(
		$subproduct_item,
		$input_active_subproduct,
		$advertiser_id,
		$campaign_set,
		$start_date,
		$end_date,
		$input_active_product,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset)
	{
		$table_function = $subproduct_item['get_data_function_name'];
		$callback = array($this->report_v2_model, $table_function);
		$params = array(
			$advertiser_id,
			$campaign_set,
			$start_date,
			$end_date,
			$input_active_product,
			$input_active_subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			$timezone_offset
		);

		return call_user_func_array($callback, $params);
	}

	private function build_table_data(
		$input_active_product,
		$input_active_subproduct,
		$advertiser_id,
		$campaign_set,
		$start_date,
		$end_date,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$result = null;

		if(array_key_exists($input_active_product, $this->products_and_subproducts_config))
		{
			$product_config = $this->products_and_subproducts_config[$input_active_product]['subproducts_data'];
			$subproduct_items = $product_config['subproduct_items'];

			if(empty($input_active_subproduct))
			{
				reset($subproduct_items);
				$input_active_subproduct = key($subproduct_items);
			}

			if(array_key_exists($input_active_subproduct, $subproduct_items))
			{
				$table_function = $subproduct_items[$input_active_subproduct]['get_data_function_name'];

				$callback = array($this->report_v2_model, $table_function);

				$params = array(
					$advertiser_id,
					$campaign_set,
					$start_date,
					$end_date,
					$input_active_product,
					$input_active_subproduct,
					$subfeature_access_permissions,
					$build_data_type,
					$timezone_offset
				);

				$result = call_user_func_array($callback, $params);
			}
		}

		return $result;
	}

	private function get_accessible_subproducts($product_html_element_id, $subfeature_access_permissions)
	{
		$subproducts_data = null;

		if(array_key_exists($product_html_element_id, $this->products_and_subproducts_config))
		{
			$subproducts_data = $this->products_and_subproducts_config[$product_html_element_id]['subproducts_data'];

			$php_subproducts = $subproducts_data['subproduct_items'];
			$client_subproduct_items = array();

			foreach($php_subproducts as $subproduct)
			{
				if($subproduct['is_accessible'])
				{
					unset($subproduct['is_accessible']);
					unset($subproduct['get_data_function_name']);

					$client_subproduct_items[] = $subproduct;
				}
			}

			$subproducts_data['client_subproduct_items'] = $client_subproduct_items;
			unset($subproducts_data['subproduct_items']);
		}
		else
		{
			$subproducts_data = null;
		}

		return $subproducts_data;
	}

	private function build_csv_filename_stub(
		$input_active_product,
		$input_active_subproduct
	)
	{
		$filename_stub = "";
		if(array_key_exists($input_active_product, $this->products_and_subproducts_config))
		{
			$product_config = $this->products_and_subproducts_config[$input_active_product];
			$product_segment = $product_config['product_csv_file_segment'];

			$subproduct_items = $product_config['subproducts_data']['subproduct_items'];

			if(empty($input_active_subproduct))
			{
				throw new Exception("Selected report subproduct is empty. (#875397)");

				reset($subproduct_items);
				$input_active_subproduct = key($subproduct_items);
			}

			if(array_key_exists($input_active_subproduct, $subproduct_items))
			{
				$subproduct_segment = $subproduct_items[$input_active_subproduct]['subproduct_csv_file_segment'];
				$filename_stub = "{$product_segment}_{$subproduct_segment}";
			}
			else if($input_active_subproduct === report_product_tab_html_id::overview_product)
			{
				$filename_stub = "{$product_segment}_overview";
			}
		}

		return $filename_stub;
	}

	// check if $advertisers is in the set of advertisers viewable by the current user
	private function is_access_allowed($advertiser_id)
	{
		if(!empty($advertiser_id))
		{
			if(!is_array($advertiser_id))
			{
				$advertiser_id=array($advertiser_id);
			}
			$allowed_advertisers = $this->get_advertisers();
			foreach($advertiser_id as $advertiser_id_cell)
			{
				$adv_exists_flag=false;
				foreach($allowed_advertisers as $allowed_advertiser)
				{
					if($advertiser_id_cell == $allowed_advertiser['id'])
					{
						$adv_exists_flag=true;
					}
				}
				if(!$adv_exists_flag)
				{
					return false;
				}
			}
		}

		return true;
	}

	// augment the return data with standard parameters and encode it all in json
	private function get_report_data_json(&$return_data, $error_info, $is_success)
	{
		if(count($error_info) > 0)
		{
			if(array_key_exists('errors', $return_data) && is_array($return_data['errors']))
			{
				$return_data['errors'] = array_merge($return_data['errors'], $error_info);
			}
			else
			{
				$return_data['errors'] = $error_info;
			}
		}

		if(array_key_exists('is_success', $return_data))
		{
			$return_data['is_success'] = $return_data['is_success'] || $is_success;
		}
		else
		{
			$return_data['is_success'] = $is_success;
		}

		return json_encode($return_data);
	}

	private function verify_ajax_access(array &$return_data)
	{
		$return_data['is_access_allowed'] = false;
		$return_data['is_logged_in'] = false;

		if($this->input->server('REQUEST_METHOD') == 'POST')
		{
			if($this->tank_auth->is_logged_in())
			{
				$return_data['is_logged_in'] = true;

				$advertiser_id = $this->input->post('advertiser_id');

				if(!is_array($advertiser_id))
				{
					$advertiser_id=array($advertiser_id);
				}
				$is_access_allowed=true;
				foreach ($advertiser_id as $advertiser_id_string)
				{
					if(!(empty($advertiser_id_string) || $this->is_access_allowed($advertiser_id_string)))
					{
						$is_access_allowed=false;
						break;
					}
				}
				if($is_access_allowed)
				{
					$return_data['is_access_allowed'] = true;
				}
				else
				{
					$return_data['errors'][] = 'Access denied to user for chosen advertiser(s)';
				}

			}
			else
			{
				$return_data['errors'][] = 'User not logged in';
			}
		}
		else
		{
			show_404();
		}

		return $return_data['is_access_allowed'];
	}

	private function get_processed_inputs()
	{
		$input = new report_ajax_input();

		$input->error_info = array();
		$input->is_success = true;

		if($this->tank_auth->is_logged_in())
		{
			$input->request_id = $this->input->post('request_id');

			$input->action = $this->input->post('action');

			$formatted_start_date = date("Y-m-d", strtotime($this->input->post('start_date')));
			$formatted_end_date = date("Y-m-d", strtotime($this->input->post('end_date')));

			$input->start_date = $formatted_start_date;
			$input->end_date = $formatted_end_date;
			$input->active_product = $this->input->post('active_product');
			$input->active_subproduct = $this->input->post('active_subproduct');
			$input->timezone_offset = $this->input->post('timezone_offset');
			$input->advertiser_id = $this->input->post('advertiser_id');
			$input->campaign_set = new report_campaign_set(array());

			if(empty($input->advertiser_id))
			{
				// User has no advertiser so return empty data. // Don't show an error
			}
			elseif($this->is_access_allowed($input->advertiser_id))
			{
				if($input->action == 'change_advertiser')
				{
					$advertiser_id=$input->advertiser_id;
					if(!is_array($advertiser_id))
					{
						$advertiser_id=array($advertiser_id);
					}

					$user_id = $this->tank_auth->get_user_id();
					$this->subfeature_access_permissions = $this->subfeature_access_permissions ?: $this->report_v2_model->get_subfeature_access_permissions(
						$user_id
					);
					$campaigns = $this->report_v2_model->get_report_campaigns($advertiser_id, $this->subfeature_access_permissions);
					$input->campaign_set = new report_campaign_set($campaigns);
				}
				else
				{
					$campaigns_values = $this->input->post('campaign_values');
					if(is_array($campaigns_values))
					{
						$input->campaign_set = new report_campaign_set($campaigns_values);
					}
				}
			}
			else
			{
				$input->error_info[] = 'Access denied for advertiser';
				$input->is_success = false;
			}
		}
		else
		{
			$input->error_info[] = 'Not logged in';
			$input->is_success = false;
		}

		return $input;
	}

	public function get_campaigns()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$inputs = $this->get_processed_inputs();
			if($inputs->is_success === true)
			{
				$user_id = $this->tank_auth->get_user_id();
				$this->init_access_and_configs($user_id);

				$report_data = array();
				$report_data['request_id'] = $inputs->request_id;
				$report_data['campaigns'] = $inputs->campaign_set->get_campaigns_for_html($this->subfeature_access_permissions);
				$return_data['real_data'] = $report_data; // TODO: rename "real_data" to something
			}
			else
			{
				$is_success = $inputs->is_success;
				$error_info = array_merge($inputs->error_info, $error_info);
			}
		}
		else
		{
			$is_success = false;
		}

 		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function get_products_tabs_and_views_nav_pills()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$inputs = $this->get_processed_inputs();
			if($inputs->is_success === true)
			{
				$user_id = $this->tank_auth->get_user_id();
				$this->init_access_and_configs($user_id);

				$report_data = array();

				$report_data['request_id'] = $inputs->request_id;
				$tabs_data = $this->get_products($inputs);
				$report_data['tabs_data'] = $tabs_data; // TODO: rename tabs_data
				$report_data['subproducts_data'] = $this->get_subproducts_nav_data($tabs_data['new_active_product'], $inputs);

				$return_data['real_data'] = $report_data;
			}
			else
			{
				$is_success = $inputs->is_success;
				$error_info = array_merge($inputs->error_info, $error_info);
			}
		}
		else
		{
			$is_success = false;
		}

 		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	private function get_products($inputs)
	{
		$tabs_data = array();

		$active_product = $inputs->active_product;

		if($inputs->action == 'initialization' ||
			$inputs->action == 'change_advertiser' ||
			$inputs->action == 'change_campaign' ||
			$inputs->action == 'change_date'
		)
		{
			$products_tabs = $this->get_accessible_products();
			$tabs_data['new_tabs'] = $products_tabs;

			$tabs_visibility = $this->get_tabs_visibility($inputs);
			$tabs_data['tabs_visibility'] = $tabs_visibility; // TODO: rename tabs_visibility

			if(!empty($products_tabs))
			{
				$active_product = $this->get_new_active_product(
					$inputs->active_product,
					$inputs->action,
					$products_tabs,
					$tabs_visibility
				);
			}
		}

		$tabs_data['new_active_product'] = $active_product;
		return $tabs_data;
	}

	private function get_tabs_visibility($inputs)
	{
		$tabs_visibility_data = array();
		$tabs_visibility_data[report_product_tab_html_id::overview_product] = true;

		$condense_to_one_row = true;

		$advertiser_id=$inputs->advertiser_id;
		if(!is_array($advertiser_id))
		{
			$advertiser_id=array($advertiser_id);
		}
		$spectrum_tv_presence_response = $this->report_v2_model->get_spectrum_tv_data_presence(
			$advertiser_id,
			"",
			$inputs->start_date,
			$inputs->end_date,
			$inputs->campaign_set,
			$condense_to_one_row
		);
		if(!empty($spectrum_tv_presence_response) && $spectrum_tv_presence_response->num_rows() > 0)
		{
			$row = $spectrum_tv_presence_response->row();
			$tabs_visibility_data[report_product_tab_html_id::targeted_tv_product] = $row->has_targeted_tv == 1;

		}
		else
		{
			$tabs_visibility_data[report_product_tab_html_id::targeted_tv_product] = false;
		}

		$tmpi_presence_response = $this->report_v2_model->get_tmpi_account_data_presence(
			$advertiser_id,
			"",
			$inputs->start_date,
			$inputs->end_date,
			$inputs->campaign_set,
			$condense_to_one_row
		);

		if(!empty($tmpi_presence_response) && $tmpi_presence_response->num_rows() > 0)
		{
			$row = $tmpi_presence_response->row();

			$tabs_visibility_data[report_product_tab_html_id::targeted_clicks_product] = $row->has_targeted_clicks == 1;
			$tabs_visibility_data[report_product_tab_html_id::targeted_inventory_product] = $row->has_targeted_inventory == 1;
			$tabs_visibility_data[report_product_tab_html_id::targeted_directories_product] = $row->has_targeted_directories == 1;
		}
		else
		{
			$tabs_visibility_data[report_product_tab_html_id::targeted_clicks_product] = false;
			$tabs_visibility_data[report_product_tab_html_id::targeted_inventory_product] = false;
			$tabs_visibility_data[report_product_tab_html_id::targeted_directories_product] = false;
		}

		$carmercial_presence_response = $this->report_v2_model->get_carmercial_data_presence(
			$advertiser_id,
			"",
			$inputs->start_date,
			$inputs->end_date,
			$inputs->campaign_set,
			$condense_to_one_row
		);

		if(!empty($carmercial_presence_response) && $carmercial_presence_response->num_rows() > 0)
		{
			$row = $carmercial_presence_response->row();

			$tabs_visibility_data[report_product_tab_html_id::targeted_content_product] = $row->has_targeted_content == 1;
		}
		else
		{
			$tabs_visibility_data[report_product_tab_html_id::targeted_content_product] = false;
		}

		$frequence_presence_response = $this->report_v2_model->get_frequence_account_data_presence(
			$advertiser_id,
			$this->subfeature_access_permissions,
			"",
			$inputs->start_date,
			$inputs->end_date,
			$inputs->campaign_set,
			$condense_to_one_row
		);

		if(!empty($frequence_presence_response) && $frequence_presence_response->num_rows() > 0)
		{
			$row = $frequence_presence_response->row();

			$tabs_visibility_data[report_product_tab_html_id::display_product] = $row->has_targeted_display == 1;
			$tabs_visibility_data[report_product_tab_html_id::pre_roll_product] = $row->has_targeted_preroll == 1;
		}
		else
		{
			$tabs_visibility_data[report_product_tab_html_id::display_product] = false;
			$tabs_visibility_data[report_product_tab_html_id::pre_roll_product] = false;
		}

		return $tabs_visibility_data;
	}

	public function get_headline_data()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$inputs = $this->get_processed_inputs();
			if($inputs->is_success === true)
			{
				$user_id = $this->tank_auth->get_user_id();
				$this->init_access_and_configs($user_id);

				$real_data = array();
				$real_data['request_id'] = $inputs->request_id;

				$blank_out_string = '<span class="f_totals_blank_cell">--</span>';

				$is_tmpi_accessible = $this->subfeature_access_permissions['is_tmpi_accessible'];

				$advertiser_id=$inputs->advertiser_id;
				if(!is_array($advertiser_id))
				{
					$advertiser_id=array($advertiser_id);
				}

				$summary_response = $this->report_v2_model->get_summary_data_by_id(
					$advertiser_id,
					$inputs->campaign_set,
					$inputs->start_date,
					$inputs->end_date,
					$this->subfeature_access_permissions
				);

				if(!empty($summary_response) && $summary_response->num_rows() > 0)
				{
					$summary_response_data = $summary_response->row();

					$impressions = $summary_response_data->impressions;
					$leads = $summary_response_data->leads;
					$ad_interactions = $summary_response_data->ad_interactions;
					$view_throughs = $summary_response_data->view_throughs; // is zero if $this->subfeature_access_permissions['are_view_throughs_accessible'] is false
					$clicks = $summary_response_data->clicks;
					$retargeting_impressions = $summary_response_data->retargeting_impressions;
					$retargeting_clicks = $summary_response_data->retargeting_clicks;
					$engagements_extras = $summary_response_data->engagements_extras;

					$visits = $view_throughs + $clicks;

					$total_engagements = $visits + $ad_interactions + $leads + $engagements_extras;

					$ad_interaction_rate = 0;
					$visit_rate = 0;
					$view_through_rate = 0;
					$click_rate = 0;
					$retargeting_click_rate = 0;
					$total_engagement_rate = 0;

					if(!empty($impressions))
					{
						$ad_interaction_rate = $ad_interactions > 0 ? 100 * $ad_interactions / $impressions: 0;
						$visit_rate = $visits > 0 ? 100 * $visits / $impressions : 0;
						$view_through_rate = $view_throughs > 0 ? 100 * $view_throughs / $impressions : 0;
						$click_rate = $clicks > 0 ? 100 * $clicks / $impressions : 0;
						$total_engagement_rate = $total_engagements > 0 ? 100 * $total_engagements / $impressions : 0;
					}

					if(!empty($retargeting_impressions))
					{
						$retargeting_click_rate = $retargeting_clicks > 0 ? 100 * $retargeting_clicks / $retargeting_impressions : 0;
					}

					$summary_data = array(
						'impressions' => ($impressions == 0) ?                         $blank_out_string :  number_format($impressions),
						'leads' => ($leads == 0) ?                                     $blank_out_string :  number_format($leads),
						'total_engagements' => ($total_engagements == 0) ?             $blank_out_string :  number_format($total_engagements),
						'total_engagement_rate' => ($total_engagement_rate == 0) ?     $blank_out_string : (number_format($total_engagement_rate, k_vl_report_num_decimal_places).'%'),
						'ad_interactions' => ($ad_interactions == 0) ?                 $blank_out_string :  number_format($ad_interactions),
						'ad_interaction_rate' => ($ad_interaction_rate == 0) ?         $blank_out_string : (number_format($ad_interaction_rate, k_vl_report_num_decimal_places).'%'),
						'visits' => ($visits == 0) ?                                   $blank_out_string :  number_format($visits),
						'visit_rate' => ($visit_rate == 0) ?                           $blank_out_string : (number_format($visit_rate, k_vl_report_num_decimal_places).'%'),
						'view_throughs' => ($view_throughs == 0) ?                     $blank_out_string :  number_format($view_throughs),
						'view_through_rate' => ($view_through_rate == 0) ?             $blank_out_string : (number_format($view_through_rate, k_vl_report_num_decimal_places).'%'),
						'clicks' => ($clicks == 0) ?                                   $blank_out_string :  number_format($clicks),
						'click_rate' => ($click_rate == 0) ?                           $blank_out_string : (number_format($click_rate, k_vl_report_num_decimal_places).'%'),
						'retargeting_impressions' => ($retargeting_impressions == 0) ? $blank_out_string :  number_format($retargeting_impressions),
						'retargeting_click_rate' => ($retargeting_click_rate == 0) ?   $blank_out_string : (number_format($retargeting_click_rate, k_vl_report_num_decimal_places).'%'),

						'are_leads_visible' => $this->subfeature_access_permissions['is_tmpi_accessible'],
						'are_visits_visible' => $this->subfeature_access_permissions['are_view_throughs_accessible']
					);
					$real_data['summary_data'] = $summary_data;

					$tabs_visibility_data = array(
						report_product_tab_html_id::overview_product => true,
						report_product_tab_html_id::display_product => $summary_response_data->has_targeted_display > 0,
						report_product_tab_html_id::pre_roll_product => $summary_response_data->has_targeted_pre_roll > 0,
						report_product_tab_html_id::targeted_clicks_product => $summary_response_data->has_targeted_clicks > 0,
						report_product_tab_html_id::targeted_inventory_product => $summary_response_data->has_targeted_inventory > 0,
						report_product_tab_html_id::targeted_directories_product => $summary_response_data->has_targeted_directories > 0
					);
					$real_data['tabs_visibility_data'] = $tabs_visibility_data; // TODO: should be active rather than visibility?
				}
				else
				{
					$summary_data = array(
						'impressions' => $blank_out_string,
						'leads' => $blank_out_string,
						'total_engagements' => $blank_out_string,
						'total_engagement_rate' => $blank_out_string,
						'ad_interactions' => $blank_out_string,
						'ad_interaction_rate' => $blank_out_string,
						'visits' => $blank_out_string,
						'visit_rate' => $blank_out_string,
						'view_throughs' => $blank_out_string,
						'view_through_rate' => $blank_out_string,
						'clicks' => $blank_out_string,
						'click_rate' => $blank_out_string,
						'retargeting_impressions' => $blank_out_string,
						'retargeting_click_rate' => $blank_out_string,

						'are_leads_visible' => $this->subfeature_access_permissions['is_tmpi_accessible'],
						'are_visits_visible' => $this->subfeature_access_permissions['are_view_throughs_accessible']
					);
					$real_data['summary_data'] = $summary_data;

					$tabs_visibility_data = array(
						report_product_tab_html_id::overview_product => true,
						report_product_tab_html_id::display_product => false,
						report_product_tab_html_id::pre_roll_product => false,
						report_product_tab_html_id::targeted_clicks_product => false,
						report_product_tab_html_id::targeted_inventory_product => false,
						report_product_tab_html_id::targeted_directories_product => false
					);
					$real_data['tabs_visibility_data'] = $tabs_visibility_data;
				}

				$return_data['real_data'] = $real_data;
			}
			else
			{
				$is_success = $inputs->is_success;
				$error_info = $inputs->error_info + $error_info;
			}
		}
		else
		{
			$is_success = false;
		}

 		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function get_graph_data()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$inputs = $this->get_processed_inputs();
			if($inputs->is_success === true)
			{
				$user_id = $this->tank_auth->get_user_id();
				$this->init_access_and_configs($user_id);

				$real_data = array();
				$real_data['request_id'] = $inputs->request_id;
				$real_data['are_leads_visible'] = $this->subfeature_access_permissions['is_tmpi_accessible'];
				$advertiser_id=$inputs->advertiser_id;
				if(!is_array($advertiser_id))
				{
					$advertiser_id=array($advertiser_id);
				}
				$graph_data = $this->get_graph_data_by_id(
					$advertiser_id,
					$inputs->campaign_set,
					$inputs->start_date,
					$inputs->end_date,
					$this->subfeature_access_permissions
				);

				if(!empty($graph_data))
				{
					foreach($graph_data as &$date_data)
					{
						$date_data['date'] = date("M j, Y", strtotime($date_data['date']));
					}
					$real_data['graph_data'] = $graph_data;
				}
				else
				{
					$real_data['graph_data'] = array();
				}

				$return_data['real_data'] = $real_data;
			}
			else
			{
				$is_success = $inputs->is_success;
				$error_info = $inputs->error_info + $error_info;
			}
		}
		else
		{
			$is_success = false;
		}

 		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function get_overview_card_data()
	{
		if($this->tank_auth->is_logged_in())
		{
			$inputs = $this->get_processed_inputs();

			$user_id = $this->tank_auth->get_user_id();
			$this->subfeature_access_permissions = $this->report_v2_model->get_subfeature_access_permissions($user_id);

			$source = $this->input->post('source', true) ?: "No source supplied";
			$advertiser_id = $this->input->post('advertiser_id');
			$campaign_string = $this->input->post('campaign_values');
			$raw_start_date = $this->input->post('start_date');
			$raw_end_date = $this->input->post('end_date');
			$action = $this->input->post('action');
			$advertiser_id=$inputs->advertiser_id;
			if(!is_array($advertiser_id))
			{
				$advertiser_id=array($advertiser_id);
			}
			$campaign_set=null;

			if ($action == 'change_advertiser')
			{
					$campaigns = $this->report_v2_model->get_report_campaigns($advertiser_id, $this->subfeature_access_permissions);
					$inputs->campaign_set = new report_campaign_set($campaigns);
			}
			else if ($campaign_string)
			{
				$inputs->campaign_set = new report_campaign_set($campaign_string);
			}
			$data = null;

			switch($source)
			{
				case 'tv':
					$data = array();
					if($this->subfeature_access_permissions['is_tmpi_accessible'])
					{
						$data = $this->report_v2_model->get_overview_first_airings(
							$advertiser_id,
							$inputs->campaign_set,
							$inputs->timezone_offset
						);
					}
					break;
				case 'visits':
					$data = array();
					break;
				case 'creatives':
					$data = $this->report_v2_model->get_overview_top_creatives(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date
					);
					break;
				case 'placements':
					$data_server = $this->report_v2_model->get_placements_data_no_pre_roll_yes_display(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						$this->subfeature_access_permissions,
						k_build_table_type::csv,
						'OVERVIEW'
					);
					$data = array();
					$ctr=0;
					if ($data_server != null)
					{
						foreach ($data_server as $row)
						{
							if (array_key_exists('place', $row))
							{
								$site = $row['place'];
								$impressions =  $row['impressions'];
								if ($site == null || $site == "" || $site == Td_uploader_model::all_other_sites_placeholder || $site == Td_uploader_model::all_other_sites_small_impressions_placeholder)
									continue;
								$sub_array=array('name' => $site , 'data' => (int)$impressions);
								$data[]=$sub_array;

								if ($ctr > 8)
									break;
								$ctr++;
							}
						}
					}
					break;
				case 'completions':
					$data_server = $this->report_v2_model->get_video_core_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						$this->subfeature_access_permissions,
						'OVERVIEW'
					);
					$data = array();

					if ($data_server != null)
					{
						foreach($data_server->result_array() as $row)
						{

							if (
									(int)$row['video_started'] == 0 &&
									(int)$row['25_percent_viewed'] == 0 &&
									(int)$row['50_percent_viewed'] == 0 &&
									(int)$row['75_percent_viewed'] == 0 &&
									(int)$row['100_percent_viewed'] == 0
								)
							{
								$data = array();
							}
							else
							{
								$pre_roll_recent_date = date('m-d-Y', strtotime($row['newest_date']));
								if($pre_roll_recent_date != date('m-d-Y', strtotime("today")))
								{
									$max_completion_date = $pre_roll_recent_date;
								}
								else
								{
									$max_completion_date = null;
								}
								$data['max_completion_date'] = $max_completion_date;

								$data['completion_data'] = array(
									array(
										'name' => 'Started',
										'data' => (int)$row['impressions']
									),
									array(
										'name' => '25% Viewed',
										'data' => (int)$row['25_percent_viewed']
									),
									array(
										'name' => '50% Viewed',
										'data' => (int)$row['50_percent_viewed']
									),
									array(
										'name' => '75% Viewed',
										'data' => (int)$row['75_percent_viewed']
									),
									array(
										'name' => 'Completed',
										'data' => (int)$row['100_percent_viewed']
									)
								);
							}
						}
					}
					break;
				case 'networks':
					$data_server = $this->report_v2_model->get_overview_top_networks(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						5
					);
					$data=array();
					if ($data_server != null)
					{
						$data = $data_server;
					}
					break;
				case 'clicks':
					$data = array();

					if($this->subfeature_access_permissions['is_tmpi_accessible'])
					{
						$data_server = $this->report_v2_model->get_targeted_visits_clicks_for_overview_page(
							$advertiser_id,
							$inputs->campaign_set,
							$inputs->start_date,
							$inputs->end_date
						);
						if ($data_server != null)
						{
							foreach($data_server as $row)
							{
								$data[]=array('name' => $row['day'], 'data' => (int)$row['clicks'], 'term' => $row['term']);
							}
						}
					}
					break;
				case 'inventory_price':
					$data_raw = $this->report_v2_model->get_tmpi_targeted_inventory_price_breakdown_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						report_product_tab_html_id::targeted_inventory_product,
						null,
						$this->subfeature_access_permissions,
						k_build_table_type::csv,
						null
					);
					$data=array();
					foreach ($data_raw as $row)
					{
						if (array_key_exists('price_range', $row))
						{
							$data[] = array('name' => $row['price_range'], 'data' => round($row['percent'], 2));
						}
					}
					// Sort array according to $order
					$order = array('High', 'Average', 'Low', 'No Price');
					usort($data, function($a, $b) use ($order){
						$pos_a = array_search($a['name'], $order);
						$pos_b = array_search($b['name'], $order);
						return $pos_a - $pos_b;
					});
					break;
				case 'search':
					$data_server = $this->report_v2_model->get_tmpi_targeted_directories_local_search_profile_activity_core_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						report_product_tab_html_id::targeted_directories_product,
						null,
						$this->subfeature_access_permissions,
						array(new report_table_starting_column_sort(1, 'desc'))
					);
					$data = array();
					foreach ($data_server->result_array() as $row)
					{
						$data[]=array('name' => $row['short_name'], 'data' => (int)$row['total']);
					}
					break;
				case 'content':
					$data_response = $this->report_v2_model->get_carmercial_targeted_content_overview_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						report_product_tab_html_id::targeted_content_product,
						null,
						$this->subfeature_access_permissions,
						null
					);
					$data = $data_response->result_array();
					usort($data, function($a, $b){
						if ($a['total'] == $b['total'])
						{
							return 0;
						}

						return ($a['total'] > $b['total']) ? -1 : 1;
					});
					$data = array_slice($data, 0, 5);
					$data = array_map(function($row){
						return array(
							'name' => $row['video_name_string'],
							'data' => (int)$row['total']
						);
					}, $data);
					break;
				case 'map':
					$data = array();
					$campaign_products_and_ids = $this->report_v2_model->get_campaigns_for_applicable_products_on_overview_map($inputs->campaign_set);
					$tv_regions = $this->report_v2_model->get_tv_regions_from_advertiser_id_and_campaign_ids($advertiser_id, $campaign_products_and_ids['tv'], $inputs->start_date, $inputs->end_date, $inputs->timezone_offset);
					$map_geojson_with_data = $this->map->get_report_overview_geojson_and_data($advertiser_id, $campaign_products_and_ids, $inputs->start_date, $inputs->end_date, $tv_regions);

					if($map_geojson_with_data)
					{
						$data['overview_geojson'] = $map_geojson_with_data;
					}
					break;
				case 'display_totals':
					$data = array();

					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::frequence,
						report_campaign_type::frequence_display
					);

					$response = $this->report_v2_model->get_summary_data_by_id(
						$advertiser_id,
						$filtered_campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						report_product_tab_html_id::display_product
					);

					if(!empty($response) && $response->num_rows() > 0)
					{
						$row = $response->row();

						$view_throughs = $row->view_throughs; // is zero if $this->subfeature_access_permissions['are_view_throughs_accessible'] is false
						$clicks = $row->clicks;
						$visits = $view_throughs + $clicks;
						$ad_interactions = ($row->ad_interactions ? $row->ad_interactions : 0) + $visits;

						if($row->impressions || $ad_interactions || $visits)
						{
							$data = array(
								array(
									'name' => 'Impressions',
									'data' => $row->impressions ? $row->impressions : 0
								),
								array(
									'name' => 'Engagements',
									'data' => $ad_interactions
								),
								array(
									'name' => 'Site Visits',
									'data' => $visits ? $visits : 0
								)
							);
						}
					}
					break;
				case 'pre_roll_totals':
					$data = array();

					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::frequence,
						report_campaign_type::frequence_pre_roll
					);

					$impressions_response = $this->report_v2_model->get_summary_data_by_id(
						$advertiser_id,
						$filtered_campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						report_product_tab_html_id::pre_roll_product
					);

					$completions_response = $this->report_v2_model->get_video_core_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						$this->subfeature_access_permissions,
						'OVERVIEW'
					);

					if((!empty($impressions_response) && $impressions_response->num_rows() > 0) && (!empty($completions_response) && $completions_response->num_rows() > 0))
					{
						$impression_row = $impressions_response->row();
						$completions_row = $completions_response->row_array();

						$view_throughs = $impression_row->view_throughs; // is zero if $this->subfeature_access_permissions['are_view_throughs_accessible'] is false
						$clicks = $impression_row->clicks;
						$visits = $view_throughs + $clicks;
						$completion_rate = $impression_row->impressions > 0 && $completions_row['impressions'] > 0 ? (int)$completions_row['100_percent_viewed']/(int)$completions_row['impressions']*100 : 0;

						if(($impression_row->impressions || $visits))
						{
							$data = array(
								array(
									'name' => 'Impressions',
									'data' => $impression_row->impressions
								),
								array(
									'name' => 'Completion Rate',
									'number_type' => $completion_rate > 0 ? 'percent' : 'non-number',
									'data' => $completion_rate > 0 ? $completion_rate : '--'
								),
								array(
									'name' => 'Site Visits',
									'data' => $visits
								)
							);
						}
					}

					break;
				case 'directories_totals':
					$data = array();

					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_directories
					);

					$directories_response = $this->report_v2_model->get_summary_data_by_id(
						$advertiser_id,
						$filtered_campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						report_product_tab_html_id::targeted_directories_product
					);


					if((!empty($directories_response) && $directories_response->num_rows() > 0))
					{
						$directories_row = $directories_response->row();

						$clicks = $directories_row->clicks;
						$leads = $directories_row->leads;
						$engagements = $directories_row->engagements_extras;
						$total_actions = $clicks + $leads + $engagements;

						if($total_actions)
						{
							$data = array(
								array(
									'name' => 'Total Actions',
									'data' => $total_actions
								),
								array(
									'name' => 'Directories',
									'number_type' => 'non-number',
									'data' => "300+"
								),
							);
						}
					}

					break;
				case 'visits_totals':
					$data = array();

					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_clicks
					);

					$visits_response = $this->report_v2_model->get_summary_data_by_id(
						$advertiser_id,
						$filtered_campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						report_product_tab_html_id::targeted_clicks_product
					);


					if((!empty($visits_response) && $visits_response->num_rows() > 0))
					{
						$visits_row = $visits_response->row();

						$impressions = $visits_row->impressions;
						$clicks = $visits_row->clicks;
						$click_through_rate = $impressions > 0 ? ($clicks/$impressions)*100 : 0;

						if($clicks)
						{
							$data = array(
								array(
									'name' => 'Impressions',
									'data' => $impressions
								),
								array(
									'name' => 'Visits',
									'data' => $clicks
								),
								array(
									'name' => 'Click Through Rate',
									'number_type' => $click_through_rate > 0 ? 'percent' : 'non-number',
									'data' => $click_through_rate > 0 ? number_format($click_through_rate, 2) : '--'
								)
							);
						}
					}

					break;
				case 'leads_totals':
					$data = array();

					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_inventory
					);

					$leads_response = $this->report_v2_model->get_tmpi_targeted_inventory_leads_totals_core_data(
						$advertiser_id,
						$filtered_campaign_set,
						$raw_start_date,
						$raw_end_date,
						report_product_tab_html_id::targeted_inventory_product,
						null,
						$this->subfeature_access_permissions,
						null
					);
					$search_response = $this->report_v2_model->get_tmpi_targeted_inventory_search_and_listings_data(
						$advertiser_id,
						$filtered_campaign_set,
						$raw_start_date,
						$raw_end_date,
						report_product_tab_html_id::targeted_inventory_product,
						null,
						$this->subfeature_access_permissions,
						null
					);

					$combined_array = array();

					if(!empty($search_response) && !empty($search_response['subproduct_data_sets'][0]['display_data']->rows))
					{
						$search_rows = $search_response['subproduct_data_sets'][0]['display_data']->rows;
						if(count($search_rows) == 3)
						{
							$totals_row = $search_rows[2];
							$search_total_mtd = $totals_row->cells[1]->html_content;
							$search_total_mtd = filter_var($search_total_mtd, FILTER_SANITIZE_NUMBER_INT);
							$search_total_mtd = $search_total_mtd/1000;

							$combined_array['search_total'] = $search_total_mtd;
						}
					}
					$smart_ads_data = $this->report_v2_model->get_tmpi_targeted_smart_ads_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						report_product_tab_html_id::targeted_inventory_product,
						null,
						$this->subfeature_access_permissions
					);

					$smart_ads_totals = 0;
					if(!empty($smart_ads_data) && isset($smart_ads_data['subproduct_data_sets'][0]['display_data']->rows))
					{
						$rows = $smart_ads_data['subproduct_data_sets'][0]['display_data']->rows;
						foreach($rows as $data)
						{
							if($data->cells)
							{
								$smart_ads_totals += filter_var($data->cells[1]->html_content, FILTER_SANITIZE_NUMBER_INT);
							}
						}
						$smart_ads_totals /= 1000;
					}

					if((!empty($leads_response) && $leads_response->num_rows() > 0))
					{
						$leads_array = $leads_response->result_array();
						foreach($leads_array as $leads_entry)
						{
							if($leads_entry['actions'] > 0)
							{
								$combined_array[$leads_entry['lead_type']] = (int)$leads_entry['actions'];
							}
						}
					}

					if(!empty($combined_array))
					{
						$searches_and_views = isset($combined_array['search_total']) ? $combined_array['search_total'] : 0;
						$searches_and_views += $smart_ads_totals;
						$phone_calls = isset($combined_array['Phone calls']) ? $combined_array['Phone calls'] : 0;
						$emails = isset($combined_array['Email contacts']) ? $combined_array['Email contacts'] : 0;
						$data = array(
							array(
								'name' => 'Searches & Views',
								'number_type' => 'non-number',
								'data' => $searches_and_views < 1 ? number_format($searches_and_views, 1)."k" : number_format($searches_and_views, 0)."k"
							),
							array(
								'name' => 'Emails',
								'data' => $emails
							),
							array(
								'name' => 'Phone Calls',
								'data' => $phone_calls
							)
						);
					}
					break;
				case 'content_totals':
					$data = array();

					$totals_response = $this->report_v2_model->get_carmercial_targeted_content_overview_total(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						report_product_tab_html_id::targeted_content_product,
						null,
						$this->subfeature_access_permissions,
						null
					);

					if($totals_response != false)
					{
						$totals_data = $totals_response->row_array();
						if($totals_data['total'] > 0)
						{
							$data = array(
								array(
									'name' => 'Video Views',
									'data' => $totals_data['total']
								)
							);
						}
					}
					break;
				default:
					$data = array();
					break;
				case 'timeseries_display':
					$data_keys = array(
						'total_impressions',
						'retargeting_impressions',
						'visits',
						'clicks',
						'engagements'
					);
					$data = $this->get_timeseries_data(
						report_campaign_organization::frequence,
						report_campaign_type::frequence_display,
						$inputs,
						$advertiser_id,
						$this->subfeature_access_permissions,
						$data_keys,
						report_product_tab_html_id::display_product
					);
					break;
				case 'timeseries_pre_roll':
					$data_keys = array(
						'total_impressions',
						'retargeting_impressions',
						'visits',
						'clicks'
					);
					$data = $this->get_timeseries_data(
						report_campaign_organization::frequence,
						report_campaign_type::frequence_pre_roll,
						$inputs,
						$advertiser_id,
						$this->subfeature_access_permissions,
						$data_keys,
						report_product_tab_html_id::pre_roll_product
					);
					break;
				case 'timeseries_visits_product':
					$data_keys = array(
						'total_impressions',
						'clicks'
					);
					$data = $this->get_timeseries_data(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_clicks,
						$inputs,
						$advertiser_id,
						$this->subfeature_access_permissions,
						$data_keys,
						report_product_tab_html_id::targeted_clicks_product
					);
					break;
				case 'timeseries_leads_product':
					$data_keys = array(
						'total_impressions',
						'clicks',
						'engagements',
						'leads'
					);
					$data = $this->get_timeseries_data(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_inventory,
						$inputs,
						$advertiser_id,
						$this->subfeature_access_permissions,
						$data_keys,
						report_product_tab_html_id::targeted_inventory_product
					);
					break;
				case 'timeseries_directories_product':
					$data_keys = array(
						'total_impressions',
						'clicks',
						'engagements',
						'leads'
					);
					$data = $this->get_timeseries_data(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_directories,
						$inputs,
						$advertiser_id,
						$this->subfeature_access_permissions,
						$data_keys,
						report_product_tab_html_id::targeted_directories_product
					);
					break;
				case 'timeseries_content_product':
					$data_keys = array(
						'views'
					);
					$content_timeseries_response = $this->report_v2_model->get_carmercial_targeted_content_timeseries_data(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						$this->subfeature_access_permissions,
						null
					);

					$graph_data = array();
					$data_result = $content_timeseries_response->result_array();
					if(!empty($data_result))
					{
						$response_data = array();
						foreach($data_result as $row)
						{
							$response_data[$row['date']] = $row;
						}


						$zero_data = $this->report_v2_model->zero_dates($response_data, $inputs->start_date, $inputs->end_date);
						$graph_data = array_values($zero_data);
					}
					else
					{
						$data = array();
						break;
					}

					$date_series = array();
					$views_series = array();

					foreach($graph_data as $date => $row)
					{
						$date_to_format = new DateTime($row['date']);
						$date_series[] = $date_to_format->format('m-d-Y');
						$views_series[] = (int)$row['views'];
					}
					$data = array(
						'series_data' => array(
							$views_series
						),
						'x_axis_data' => $date_series
					);
					break;
				case 'timeseries_tv_verified':
					$filtered_accounts_set = clone $inputs->campaign_set;
					$filtered_accounts_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::spectrum,
						report_campaign_type::spectrum_tv
					);
					$data_response = $this->report_v2_model->get_verified_spots_by_date(
						$advertiser_id,
						$filtered_accounts_set,
						$inputs->start_date,
						$inputs->end_date
					);

					$graph_data = array();
					if(!empty($data_response))
					{
						$response_data = array();
						foreach($data_response as $row)
						{
							$response_data[$row['date']] = $row;
						}


						$zero_data = $this->report_v2_model->zero_dates($response_data, $inputs->start_date, $inputs->end_date);
						$graph_data = array_values($zero_data);
					}
					else
					{
						$data = array();
						break;
					}

					$date_series = array();
					$spots_series = array();
					$impressions_series = array();
					foreach($graph_data as $date => $row)
					{
						$date_to_format = new DateTime($row['date']);
						$date_series[] = $date_to_format->format('m-d-Y');
						$spots_series[] = (int)$row['spots'];
						$impressions_series[] = (int)$row['total_impressions'];
					}
					$data = array(
						'series_data' => array(
							['data_purpose' => 'airings', 'data' => $spots_series]
						),
						'x_axis_data' => $date_series
					);
					if($this->subfeature_access_permissions['are_tv_impressions_accessible'])
					{
						$data['series_data'][] = ['data_purpose' => 'total_impressions', 'data' => $impressions_series];
					}
					break;
				case 'totals_tv_verified':
					$filtered_accounts_set = clone $inputs->campaign_set;
					$filtered_accounts_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::spectrum,
						report_campaign_type::spectrum_tv
					);
					$data_response = $this->report_v2_model->get_overview_tv_totals(
						$advertiser_id,
						$filtered_accounts_set,
						$inputs->start_date,
						$inputs->end_date
					);

					$data = array();
					if(!empty($data_response))
					{
						$impressions = (int)$data_response['impressions'];
						$airings = (int)$data_response['airings'];
						$networks = (int)$data_response['networks'];
						$zones = (int)$data_response['count_zones'];

						$zip_list_account_array = explode("==", $data_response['regions_string']);
						$all_zips = array();
						foreach($zip_list_account_array as $zip_list_for_account)
						{
							$zip_lists = json_decode($zip_list_for_account, true);
							if(!empty($zip_lists))
							{
								$all_zips = array_merge($all_zips, $zip_lists);
							}
						}
						$all_zips = array_unique($all_zips);
						$zips = count($all_zips);

						if($airings || $networks || $impressions || $zips)
						{
							$data = array(
								array(
									'name' => 'Airings',
									'data' => $airings
								),
								array(
									'name' => 'Networks',
									'data' => $networks
								)
							);
							if($this->subfeature_access_permissions['are_tv_impressions_accessible'])
							{
								array_unshift($data, ['name' => 'Impressions', 'data' => $impressions]);
							}
							else
							{
								// $data[] = ['name' => 'ZIPs', 'data' => $zips];
								$data[] = ['name' => 'Zones', 'data' => $zones];
							}
						}
					}
					break;
				case 'directories_actions':
					$data = array();

					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::tmpi,
						report_campaign_type::tmpi_directories
					);

					$leads_response = $this->report_v2_model->get_tmpi_targeted_inventory_leads_totals_core_data(
						$advertiser_id,
						$filtered_campaign_set,
						$raw_start_date,
						$raw_end_date,
						report_product_tab_html_id::targeted_directories_product,
						null,
						$this->subfeature_access_permissions,
						null
					);
					$search_response = $this->report_v2_model->get_tmpi_targeted_directories_local_search_profile_activity_core_data(
						$advertiser_id,
						$filtered_campaign_set,
						$raw_start_date,
						$raw_end_date,
						report_product_tab_html_id::targeted_directories_product,
						null,
						$this->subfeature_access_permissions,
						null
					);

					$combined_array = array();

					if((!empty($leads_response) && $leads_response->num_rows() > 0))
					{
						$leads_array = $leads_response->result_array();
						foreach($leads_array as $leads_entry)
						{
							if($leads_entry['actions'] > 0)
							{
								$combined_array[$leads_entry['lead_type']] = (int)$leads_entry['actions'];
							}
						}

					}

					if((!empty($search_response) && $search_response->num_rows() > 0))
					{
						$search_array = $search_response->result_array();
						foreach($search_array as $search_entry)
						{
							if($search_entry['total'] > 0)
							{
								$combined_array[$search_entry['short_name']] = (int)$search_entry['total'];
							}
						}
					}

					if(!empty($combined_array))
					{
						arsort($combined_array, SORT_NUMERIC);
						$combined_array = array_slice($combined_array, 0, 3, true);
						foreach($combined_array as $action => $value)
						{
							$data[] = array('name' => $action, 'data' => $value);
						}
					}
					break;
				case 'digital_overview':
					$data = array();

					$summary_response = $this->report_v2_model->get_summary_data_by_id(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						null,
						true
					);

					$content_totals_response = $this->report_v2_model->get_carmercial_targeted_content_overview_total(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						$this->subfeature_access_permissions,
						null
					);

					$tv_totals_response = $this->report_v2_model->get_overview_tv_totals(
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date
					);

					$overview_data = array();

					if($this->subfeature_access_permissions['are_tv_impressions_accessible'] && $tv_totals_response)
					{
						if($tv_totals_response['impressions']) //If there's non-zero values in the row
						{
							$overview_data[] = array(
								'type' => 'Targeted TV',
								'friendly_name' => 'tv',
								'impressions' => (int)$tv_totals_response['impressions'],
								'total_engagements' => 0,
								'ad_interactions' => 0,
								'visits' => 0,
								'view_throughs' => 0,
								'clicks' => 0,
								'retargeting_impressions' => 0,
								'retargeting_clicks' => 0,
								'leads' => 0,
								'profile_activity' => 0,
								'content_views' => 0
							);
						}
					}

					if($summary_response != false && $summary_response->num_rows() > 0)
					{
						$summary_response_rows = $summary_response->result_array();
						foreach($summary_response_rows AS $overview_row)
						{
							if($overview_row['impressions'] +
								$overview_row['ad_interactions'] +
								$overview_row['view_throughs'] +
								$overview_row['clicks'] +
								$overview_row['view_throughs'] +
								$overview_row['retargeting_impressions'] +
								$overview_row['leads']  > 0) //If there's non-zero values in the row
							{
								$overview_data[] = array(
									'type' => report_digital_overview_type::get_account_type_friendly_name($overview_row['type'], $this->subfeature_access_permissions['is_tmpi_accessible']),
									'friendly_name' => strtolower(report_digital_overview_type::get_account_type_friendly_name($overview_row['type'], 0)),
									'impressions' => ($overview_row['type'] == report_digital_overview_type::targeted_directories ? 0 : (int)$overview_row['impressions']),
									'total_engagements' => (int)$overview_row['ad_interactions'] + (int)$overview_row['view_throughs'] + (int)$overview_row['clicks'],
									'ad_interactions' => (int)$overview_row['ad_interactions'],
									'visits' => (int)$overview_row['view_throughs'] + $overview_row['clicks'],
									'view_throughs' => (int)$overview_row['view_throughs'],
									'clicks' => (int)$overview_row['clicks'],
									'retargeting_impressions' => (int)$overview_row['retargeting_impressions'],
									'retargeting_clicks' => (int)$overview_row['retargeting_clicks'],
									'leads' => (int)$overview_row['leads'],
									'profile_activity' => ($overview_row['type'] == report_digital_overview_type::targeted_directories ? (int)$overview_row['impressions'] : 0),
									'content_views' => 0
								);
							}
						}
					}

					if(count($overview_data) < 1)
					{
						break;
					}
					if($content_totals_response != false && $content_totals_response->num_rows() > 0)
					{
						$content_totals_rows = $content_totals_response->result_array();
						foreach($content_totals_rows AS $content_row)
						{
							if($content_row['total'] > 0)
							{
								$overview_data[] = array(
									'type' => report_digital_overview_type::get_account_type_friendly_name(report_digital_overview_type::targeted_content, $this->subfeature_access_permissions['is_tmpi_accessible']),
									'impressions' => 0,
									'total_engagements' => 0,
									'ad_interactions' => 0,
									'visits' => 0,
									'view_throughs' => 0,
									'clicks' => 0,
									'retargeting_impressions' => 0,
									'leads' => 0,
									'profile_activity' => 0,
									'content_views' => (int)$content_row['total']
								);
							}
						}
					}
					if(count($overview_data) > 0)
					{
						$data['overview_data'] = $overview_data;
						$data['is_tmpi_accessible'] = $this->subfeature_access_permissions['is_tmpi_accessible'];
					}
					break;
				default:
					$data = array();
					break;
			}

			$this->output->set_status_header('200');
			$this->output->set_output(json_encode($data));
		}
		else
		{
			$this->output->set_status_header('401');
			$this->output->set_output('Must be logged in.');
		}
	}

	private function get_timeseries_data(
		$organization,
		$product,
		$inputs,
		$advertiser_id,
		$subfeature_access_permissions,
		$data_set,
		$product_id
	)
	{
		$data = array();

		$filtered_campaign_set = clone $inputs->campaign_set;
		$filtered_campaign_set->clear_ids_not_in_organization_and_product(
			$organization,
			$product
		);

		$response = $this->report_v2_model->get_graph_data_by_id(
			$advertiser_id,
			$filtered_campaign_set,
			$inputs->start_date,
			$inputs->end_date,
			$subfeature_access_permissions,
			false,
			$product_id
		);

		if(!empty($response) && $response->num_rows() > 0)
		{
			$has_data_for_display = false;
			$data_response = $this->populate_empty_graph_dates(
				$response,
				$inputs->start_date,
				$inputs->end_date
			);

			$series_set_map = [
				'total_impressions' => array(
					'data_purpose' => 'total_impressions',
					'data_key' => 'total_impressions'
				),
				'retargeting_impressions' => array(
					'data_purpose' => 'retargeting_impressions',
					'data_key' => 'rtg_impressions'
				),
				'visits' => array(
					'data_purpose' => 'visits',
					'data_key' => 'total_visits'
				),
				'clicks' => array(
					'data_purpose' => 'clicks',
					'data_key' => 'total_clicks'
				),
				'engagements' => array(
					'data_purpose' => 'engagements',
					'data_key' => 'engagements'
				),
				'leads' => array(
					'data_purpose' => 'leads',
					'data_key' => 'leads'
				),
			];

			$date_series = array();
			$accumulate_series_data = array();

			$series_set = array();
			foreach($data_set as $data_id)
			{
				$series_set[] = $series_set_map[$data_id];
				$accumulate_series_data[] = array();
			}

			foreach($data_response as $date => $row)
			{
				$date_to_format = new DateTime($row['date']);
				$date_series[] = $date_to_format->format('m-d-Y');

				foreach($series_set as $index => $series_source)
				{
					$accumulate_series_data[$index][] = (int)$row[$series_source['data_key']];
					if(!$has_data_for_display && (int)$row[$series_source['data_key']] > 0)
					{
						$has_data_for_display = true;
					}
				}
			}

			if(!$has_data_for_display)
			{
				return $data;
			}

			$series_data = array();

			foreach($series_set as $index => $series_source)
			{
				$series_data[$index] = array(
					'data_purpose' => $series_source['data_purpose'],
					'data' => $accumulate_series_data[$index]
				);
			}

			$data = array(
				'series_data' => $series_data,
				'x_axis_data' => $date_series
			);
		}

		return $data;
	}


	private function get_new_active_product(
		$previous_active_product,
		$action,
		$products_tabs,
		$tabs_visibility
	)
	{
		$active_product = $previous_active_product;

		if(!empty($products_tabs))
		{
			$should_search_for_active_product = true;

			if(!empty($previous_active_product) &&
				($action == 'change_advertiser' ||
					$action == 'change_campaign' ||
					$action == 'change_date'
				)
			)
			{
				foreach($products_tabs as $tab_data)
				{
					$html_element_id = $tab_data['html_element_id'];
					if($html_element_id === $active_product)
					{
						if($tabs_visibility[$html_element_id] === true)
						{
							$should_search_for_active_product = false;
						}
						break;
					}
				}
			}

			if($should_search_for_active_product)
			{
				$first_active_product = null;
				$first_non_overview_active_product = null;
				$num_non_overview_active_items = 0;
				foreach($products_tabs as $tab_data)
				{
					$html_element_id = $tab_data['html_element_id'];
					if($tabs_visibility[$html_element_id] === true)
					{
						if($first_active_product === null)
						{
							$first_active_product = $html_element_id;
						}

						if($html_element_id !== report_product_tab_html_id::overview_product)
						{
							if($first_non_overview_active_product === null)
							{
								$first_non_overview_active_product = $html_element_id;
							}

							$num_non_overview_active_items++;
						}
					}
				}

				if($num_non_overview_active_items == 1)
				{
					// Only switch to specific product type, when last selected product has no data, when only campaigns of that type are selected.
					$active_product = $first_non_overview_active_product;
				}
				else
				{
					// If there are more than one active product types and last selected product has no data, switch to overview.
					$active_product = $first_active_product;
				}
			}
		}

		return $active_product;
	}

	private function get_subproducts_nav_data(
		$active_product_id,
		$inputs
	)
	{
		$return_data = array();

		$active_subproduct = $inputs->active_subproduct;

		if($inputs->action == 'initialization' ||
			$inputs->action == 'change_advertiser' ||
			$inputs->action == 'change_campaign' ||
			$inputs->action == 'change_date'
		)
		{
			if(!empty($active_product_id))
			{
				$subproducts_data = $this->get_accessible_subproducts($active_product_id, $this->subfeature_access_permissions);

				if(empty($inputs->active_subproduct))
				{
					$active_subproduct = $subproducts_data['client_subproduct_items'][0]['html_element_id'];
				}
				else
				{
					$is_active_subproduct_present = false;
					foreach($subproducts_data['client_subproduct_items'] as $subproduct_data)
					{
						if($subproduct_data['html_element_id'] === $active_subproduct)
						{
							$is_active_subproduct_present = true;
							break;
						}
					}

					if(!$is_active_subproduct_present)
					{
						$active_subproduct = $subproducts_data['client_subproduct_items'][0]['html_element_id'];
					}
				}

				$return_data['new_subproduct'] = $subproducts_data;
			}
		}
		else
		{
			if($inputs->action == "change_product")
			{
				$subproducts_data = $this->get_accessible_subproducts($active_product_id, $this->subfeature_access_permissions);
				$active_subproduct = $subproducts_data['client_subproduct_items'][0]['html_element_id'];
				$return_data['new_subproduct'] = $subproducts_data;
			}
			else if($inputs->action == "change_subproduct")
			{
				// no change to product nore subproduct
			}
		}

		$return_data['new_active_subproduct'] = $active_subproduct;
		return $return_data;
	}

	public function get_subproduct_content_data()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$inputs = $this->get_processed_inputs();
			if($inputs->is_success === true)
			{
				$user_id = $this->tank_auth->get_user_id();
				$this->init_access_and_configs($user_id);

				$real_data = array();
				//$real_data['request_id'] = $inputs->request_id;
				$advertiser_id=$inputs->advertiser_id;
				if(!is_array($advertiser_id))
				{
					$advertiser_id=array($advertiser_id);
				}
				$tabs_data = $this->get_products($inputs);
				if($tabs_data['new_active_product'] != report_product_tab_html_id::overview_product)
				{
					$subproducts_nav_data = $this->get_subproducts_nav_data($tabs_data['new_active_product'], $inputs);

					$subproduct_data = $this->build_active_subproduct_data(
						$tabs_data['new_active_product'],
						$subproducts_nav_data['new_active_subproduct'],
						$advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						k_build_table_type::html,
						$inputs->timezone_offset
					);

					$real_data['subproduct_data'] = $subproduct_data;
					$real_data['is_subproduct_data_response'] = true;

					$return_data['real_data'] = $real_data;
				}
				else
				{
					$real_data['subproduct_data'] = null;
					$real_data['is_subproduct_data_response'] = false;

					$return_data['real_data'] = $real_data;
				}
			}
			else
			{
				$is_success = $inputs->is_success;
				$error_info = $inputs->error_info + $error_info;
			}
		}
		else
		{
			$is_success = false;
		}

		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function get_charter_targeted_tv_schedule_ajax_data()
	{
		// TODO:TV implement to get ajax data outside the normal tab/nav-pill selection
	}

	public function get_creative_messages()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$advertiser_id = $this->input->post('advertiser_id');
			if(!is_array($advertiser_id))
			{
				$advertiser_id = array($advertiser_id);
			}
			$creative_type = $this->input->post('creative_type');
			$creative_ids = $this->input->post('creative_ids');
			$campaign_ids = new report_campaign_set($this->input->post('campaign_ids'));
			$raw_start_date = $this->input->post('start_date');
			$raw_end_date = $this->input->post('end_date');
			if($creative_type == 'tv')
			{
				$creative_data_sets = $this->report_v2_model->get_tv_creative_messages($advertiser_id, $campaign_ids, $raw_start_date, $raw_end_date, $creative_ids);
			}
			else
			{
				$creative_data_sets = $this->report_v2_model->get_creative_messages($advertiser_id, $campaign_ids, $raw_start_date, $raw_end_date, $creative_ids);
			}
			$return_data['creative_data_sets'] = $creative_data_sets;
		}
		else
		{
			$is_success = false;
		}

 		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}


	// callback that returns the csv data with a special header that tells the client to download the file instead of displaying it
	public function ajax_download_csv()
	{
		if($this->tank_auth->is_logged_in())
		{
			$user_id = $this->tank_auth->get_user_id();
			$user = $this->tank_auth->get_user_by_id($user_id);
			$user->role = strtolower($user->role);
			$partner_id = $user->partner_id;

			$inputs = $this->get_processed_inputs();

			if($user->role == 'business')
			{
				$partner_id = $this->tank_auth->get_partner_id_by_advertiser_id($user->advertiser_id);
			}

			$redirect = $this->get_redirect_if_wrong_user_type($user->role, $partner_id);
			if($redirect == '')
			{
				if($this->is_access_allowed($inputs->advertiser_id))
				{
					$campaign_filter = null;
					if($this->input->post('subproduct_nav_pill') === report_product_tab_html_id::pre_roll_product)
					{
						$campaign_filter = report_campaign_type::frequence_pre_roll;
					}
					else if($this->input->post('subproduct_nav_pill') === report_product_tab_html_id::display_product)
					{
						$campaign_filter = report_campaign_type::frequence_display;
					}

					$campaign_string = $this->input->post('campaign_values');
					$inputs->campaign_set = new report_campaign_set($campaign_string);
					$filtered_campaign_set = clone $inputs->campaign_set;
					$filtered_campaign_set->clear_ids_not_in_organization_and_product(
						report_campaign_organization::frequence,
						$campaign_filter
					);

					$this->init_access_and_configs($user_id);

					$is_csv_download = true;
					$response = $this->report_v2_model->get_summary_data_by_id(
						$inputs->advertiser_id,
						$filtered_campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						$this->input->post('subproduct_nav_pill'),
						false,
						$is_csv_download
					);

					if(!empty($response))
					{
						$csv_response = $this->format_db_response($response, $this->input->post('subproduct_nav_pill'));
						if($csv_response)
						{
							$filename_stub = $this->build_csv_filename_stub(
								$this->input->post('subproduct_nav_pill'),
								$this->input->post('tab')
							);

							$data['data_response'] = $csv_response;
							$data['filename_stub'] = $filename_stub;
							$this->load->view('report_v2/download_csv', $data);
							return;
						}
						else
						{
							echo 'invalid product type';
						}
					}
					else
					{
						echo 'no csv data';
					}
				}
				else
				{
					echo 'access not allowed';
				}
			}
			else
			{
				echo 'wrong user type';
			}
		}
		else
		{
			echo 'not logged in ';
		}
	}

	private function format_db_response(array $response, $csv_data_type)
	{
		if($csv_data_type === report_product_tab_html_id::display_product)
		{
			$csv_headers = [
				'Date' => 'date',
				'Total Impressions' => 'impressions',
				'Total Clicks' => 'clicks',
				'Retargeting Impressions' => 'retargeting_impressions',
				'Retargeting Clicks' => 'retargeting_clicks',
				'Engagements' => ['view_throughs', 'clicks', 'ad_interactions'],
				'Visits' => ['view_throughs', 'clicks']
			];
		}
		else if($csv_data_type === report_product_tab_html_id::pre_roll_product)
		{
			$csv_headers = [
				'Date' => 'date',
				'Total Impressions' => 'impressions',
				'Total Clicks' => 'clicks',
				'Retargeting Impressions' => 'retargeting_impressions',
				'Retargeting Clicks' => 'retargeting_clicks',
				'Visits' => ['view_throughs', 'clicks']
			];
		}
		else
		{
			return false;
		}

		$csv_response = [array_keys($csv_headers)];
		foreach($response as $line)
		{
			$csv_line = [];
			foreach ($csv_headers as $csv_header => $sql_column_name)
			{
				if(is_array($sql_column_name))
				{
					$filtered_array = array_intersect_key($line, array_flip($sql_column_name));
					$csv_line[$csv_header] = array_sum($filtered_array);
				}
				else
				{
					$csv_line[$csv_header] = $line[$sql_column_name];
				}
			}
			$csv_response[] = $csv_line;
		}
		return $csv_response;
	}

	public function download_inline_csv()
	{
		if($this->tank_auth->is_logged_in())
		{
			$this->csv->download_posted_inline_csv_data();
		}
		else
		{
			echo 'not logged in ';
		}
	}

	private function get_email_if_user_has_email_support()
	{
		$user_id = $this->tank_auth->get_user_id();
		$email = $this->report_v2_model->get_user_support_email_by_user_id($user_id);
		if($email)
		{
			return $email;
		}
		return false;
	}

	public function ajax_send_support_email()
	{
		if($this->tank_auth->is_logged_in())
		{
			$return_array = array('is_success' => false);

			$user_id = $this->tank_auth->get_user_id();
			$user = $this->tank_auth->get_user_by_id($user_id);
			$user->role = strtolower($user->role);
			$partner_id = $user->partner_id;

			$redirect = $this->get_redirect_if_wrong_user_type($user->role, $partner_id);
			if($redirect == '')
			{
				$support_email = $this->get_email_if_user_has_email_support();
				if($support_email)
				{
					$message = trim($this->input->post('message'));
					if($message)
					{
						$username = $this->tank_auth->get_username();
						$user_email = $this->tank_auth->get_email($username);
						$users_name = $this->tank_auth->get_firstname($username) . ' ' . $this->tank_auth->get_lastname($username);

						$mail_send = $this->send_support_email($message, $support_email, $user_email, $users_name);
						if($mail_send === true)
						{
							$return_array['is_success'] = true;
						}
						else
						{
							$return_array['errors'] = $mail_send;
						}
					}
					else
					{
						$result_array['errors'][] = 'message cannot be blank';
					}
				}
				else
				{
					$return_array['errors'][] = 'email support not available';
				}
			}
			else
			{
				$return_array['errors'][] = 'wrong user type';
			}
		}
		else
		{
			$return_array['errors'][] = 'not logged in ';
		}
		echo json_encode($return_array);
	}

	private function send_support_email($message, $support_email, $user_email, $user_name)
	{
		$user_email_string = "{$user_name} <{$user_email}>";
		$mailgun_extras = array('cc' => $user_email_string, 'bcc' => 'tech.help@frequence.com');

		$from = $user_email_string;
		$to = $support_email;
		$subject = 'Email Support';

		$result = mailgun(
			$from,
			$to,
			$subject,
			$message,
			'text',
			$mailgun_extras
		);
		return $result;
	}

	private function populate_empty_graph_dates(
		$response,
		$begin_date,
		$end_date
	)
	{
		$graph_data = array();
		if(!empty($response) && $response->num_rows() > 0)
		{
			$response_data = array();
			foreach($response->result_array() as $row)
			{
				$response_data[$row['date']] = $row;
			}

			$zero_data = $this->report_v2_model->zero_dates($response_data, $begin_date, $end_date);
			$graph_data = array_values($zero_data);
		}

		return $graph_data;
	}

	//Returns graph query results from database regarding impressions based on IDs.
	//Parameters:
	// $advertiser - advertiser ID we're going to look up campaigns for (int)
	// $campaign   - campaign ID we're going to look up data for (int)
	// $begin_date - Beginning of date range to look up impressions/clicks of (string: YYYY-MM-DD)
	// $end_date   - End of date range to look up impressions/clicks of (string: YYYY-MM-DD)
	//
	// Output: array containing impressions/clicks data for days in range.
	//         --Returns empty if no data in that range found.
	private function get_graph_data_by_id(
		$advertiser_id,
		$campaign_set,
		$begin_date,
		$end_date,
		$subfeature_access_permissions
	)
	{
		$response = $this->report_v2_model->get_graph_data_by_id(
			$advertiser_id,
			$campaign_set,
			$begin_date,
			$end_date,
			$subfeature_access_permissions,
			false
		);

		return $this->populate_empty_graph_dates($response, $begine_date, $end_date);
	}


	public function get_advertisers_ajax()
	{
		$raw_term=$this->input->post('q');
		$page_limit=$this->input->post('page_limit');
		$page_number=$this->input->post('page');
		$advertisers = null;
		$user_id = $this->tank_auth->get_user_id();
		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);
		$role=$user->role;
		if (is_numeric($page_limit)&&is_numeric($page_number))
		{
			$search_term='%'.str_replace(" ", "%", $raw_term).'%';
			$mysql_page_number=($page_number-1)*$page_limit;
			$advertisers=$this->report_v2_model->get_advertisers_search($search_term, $mysql_page_number, $page_limit+1, $user, $user_id, $role);
		}
		$dropdown_list=array(
				'result'=>array(),
				'more'=>false
		);
		if (count($advertisers)==$page_limit+1)
		{
			$real_count=$page_limit;
			$dropdown_list['more']=true;
		}
		else
		{
			$real_count=count($advertisers);
		}
		for ($i=0; $i<$real_count; $i++)
		{
			$dropdown_list['result'][]=array(
					"id"=>$advertisers[$i]['id'],
					"text"=>$advertisers[$i]['name']
			);
		}
		echo json_encode($dropdown_list);
	}

	public function get_advertisers($search_term=null,$mysql_page_number=0, $page_limit=0)
	{
		$businesses = array();
		$user_id = $this->tank_auth->get_user_id();
		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);
		$advertisers = array();
		switch($user->role)
		{
			case 'sales':
				$advertisers = $this->tank_auth->get_advertisers_by_sales_person_partner_hierarchy($user_id, $user->isGroupSuper, $search_term, $mysql_page_number, $page_limit);
				break;
			case 'business':
				$advertiser_name = $this->report_v2_model->get_advertiser_name($user->advertiser_id);
				$advertisers = array(array('Name' => $advertiser_name, 'id' => $user->advertiser_id));
				break;
			case 'admin':
			case 'creative':
			case 'ops':
				$advertisers = $this->tank_auth->get_businesses($search_term, $mysql_page_number, $page_limit);
				break;
			case 'agency':
			case 'client':
				$advertisers = $this->tank_auth->get_advertisers_for_client_or_agency($user_id, $search_term, $mysql_page_number, $page_limit);
				break;
			default:
				throw new Exception("Unknown user role: ".$user->role." (#7863801)");
				break;
		}
		if(is_null($advertisers))
		{
			$advertisers = array();
		}
		return $advertisers;
	}

	// get the first advertiser id in the list of available advertisers
	private function get_first_advertiser_id(array $advertisers)
	{
		$advertiser_id = 0;
		if(count($advertisers) > 0)
		{
			$advertiser_id = $advertisers[0]['id'];
		}

		return $advertiser_id;
	}

	// TODO: verfiy pl_permissions is used
	// get where the user should be redirected to if they aren't allowed to access the report
	private function get_redirect_if_wrong_user_type($role, $partner_id)
	{
		if($partner_id != k_brandcdn_partner_id)
		{
			if($role != 'agency' &&
				$role != 'business' &&
				$role != 'client' &&
				$role != 'sales'
			)
			{
				// Somehow we have a user that isn't in brandcdn partner while being admin, ops, or creative.
				//
				// If this is encountered it means something is messed up with
				// our database and so we block this erroneous user from getting detailed info on all
				// of the business we are selling our service to, and the businesses they
				// sell our services to.
				return 'auth/logout';
			}
		}
		else
		{
			if($role != 'business' &&
				$role != 'admin' &&
				$role != 'ops' &&
				$role != 'sales')
			{
				// Creative or Unknown user type
				return 'director';
			}
		}
		return '';
	}

	// Called when the report page is first shown.  Contains the structure of the page, and data to fill in this structure.
	public function get_structure_and_data()
	{
		// $this->vl_platform->has_permission_to_view_page_otherwise_redirect('reports', 'report'); // TODO: Re-enable once Charter is fully transitioned to Spectrum Reach
		$new_report_feature_id = 'reports';
		$new_report_post_login_redirect_url = 'report';
		$redirect = $this->vl_platform->get_access_permission_redirect($new_report_feature_id);
		if($redirect != '')
		{
			if($redirect == 'login')
			{
				$this->session->set_userdata('referer', 'report');
				$this->session->set_userdata($new_report_feature_id, $new_report_post_login_redirect_url);
				redirect(site_url($redirect));
			}
			else
			{
				$charter_legacy_report_feature_id = 'report_l'; // Correspond to values in database pl_features table
				$charter_legacy_report_redirect_url = 'reports'; // Correspond to values in database pl_features table

				$charter_legacy_redirect = $this->vl_platform->get_access_permission_redirect($charter_legacy_report_feature_id);
				if($charter_legacy_redirect == '')
				{
					redirect(site_url($charter_legacy_report_redirect_url));
				}
				else
				{
					redirect(site_url($redirect));
				}
			}
		}

		$user_id = $this->tank_auth->get_user_id();
		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);

		$role = $user->role;

		$partner_id = $user->partner_id;
		$is_whitelabel_business = false;
		if($role == 'business')
		{
			$partner_id = $this->tank_auth->get_partner_id_by_advertiser_id($user->advertiser_id);

			if($partner_id != 1)
			{
				$is_whitelabel_business = true;
			}
		}

		$redirect = $this->get_redirect_if_wrong_user_type($role, $partner_id);
		if($redirect != '')
		{
			redirect($redirect);
			return;
		}

		$data = array();

		$data['user_id'] = $user_id;
		$data['username'] = $this->tank_auth->get_username();
		$data['role'] = $role;

		$data['is_whitelabel_business'] = $is_whitelabel_business;
		$partner_info = $this->tank_auth->get_partner_info($partner_id);

		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);
		$role=$user->role;

		$advertisers=$this->report_v2_model->get_advertisers_search("%%", 0, 100, $user, $user_id, $role);
		$selected_advertiser_id = $this->get_first_advertiser_id($advertisers);
		$end_date_time = strtotime('-1 day, -7 hours');
		$end_date = date('m/d/Y', $end_date_time);
		$start_date = date('m/d/Y', strtotime('-1 month', $end_date_time));

		$this->init_access_and_configs($user_id);

		$data['advertisers'] = $advertisers;
		$data['advertiser_selected_id'] = $selected_advertiser_id;
		$data['end_date'] = $end_date;
		$data['start_date'] = $start_date;

		$data['firstname'] = $user->firstname;
		$data['lastname'] = $user->lastname;

		$data['are_screenshots_accessible'] = $this->subfeature_access_permissions['are_screenshots_accessible'];
		$data['are_ad_interactions_accessible'] = $this->subfeature_access_permissions['are_ad_interactions_accessible'];
		$data['are_engagements_accessible'] = $this->subfeature_access_permissions['are_engagements_accessible'];
		$data['are_view_throughs_accessible'] = $this->subfeature_access_permissions['are_view_throughs_accessible'];
		$data['is_tmpi_accessible'] = $this->subfeature_access_permissions['is_tmpi_accessible'];
		$data['are_tv_impressions_accessible'] = $this->subfeature_access_permissions['are_tv_impressions_accessible'];

		$products_tabs = $this->get_accessible_products();

		$starting_tabs_response = $this->get_starting_tabs_html($products_tabs);

		$data['starting_tabs'] = $starting_tabs_response['tabs_html'];
		$data['has_support_email'] = $this->get_email_if_user_has_email_support() ? true : false;

		$data['products_brand_text'] = $starting_tabs_response['products_brand_text'];
		$data['css']                = '/css/web_report.css';
		$data['logo_path']          = $partner_info['partner_report_logo_filepath'];
		$data['favicon_path']       = $partner_info['favicon_path'];
		$data['home_url']           = $partner_info['home_url'];
		$data['contact_number']     = $partner_info['contact_number'];
		$data['report_css']         = $partner_info['report_css'];
		$data['partner_unique_id']  = $partner_info['unique_id'];

		$data['mixpanel_info'] = get_mixpanel_data_array($this, $user);

		$return_data = array();
		$error_info = array();
		$is_success = true;

		$active_feature_button_id = 'reports';
		$data['title'] = 'Report';

		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'report_v2/structure_and_data',
			'report_v2/report_header',
			NULL,
			'report_v2/report_js.php',
			NULL
		);

	}

	public function get_lift_data_for_report_overview()
	{
		$result = array();
		$result['is_success'] = true;

		$required_post_variables = array('campaign_values','start_date', 'end_date');
		$ajax_verify = vl_verify_ajax_post_variables($required_post_variables);

		if ($ajax_verify['is_success'])
		{
			$campaign_ids = $ajax_verify['post']['campaign_values'];
			$date_report_start = $ajax_verify['post']['start_date'];
			$date_report_end = $ajax_verify['post']['end_date'];
			$user_id = $this->tank_auth->get_user_id();
			$user = $this->tank_auth->get_user_by_id($user_id);
			$user->role = strtolower($user->role);

			$accessible_subfeatures = $this->report_v2_model->get_subfeature_access_permissions($user_id);
			if ($accessible_subfeatures['is_lift_report_accessible'])
			{
				$lift_data_for_report_overview = $this->report_v2_model->get_lift_data_for_report_overview($campaign_ids, $date_report_start, $date_report_end);

				if (!$lift_data_for_report_overview)
				{
					$result['is_success'] = false;
				}
				else
				{
					$avg_lift = $lift_data_for_report_overview[0]['average_lift'];
					if (isset($avg_lift) && $avg_lift != null)
					{
						if ($avg_lift >= 0.04 || $user->role == 'admin')
						{
							if ($avg_lift > 9.0)
							{
								$avg_lift = intval($avg_lift);
							}
							else
							{
								$avg_lift = number_format(floatval($avg_lift),1);
							}
							$lift_data_for_report_overview[0]['average_lift'] = $avg_lift;
							$result['lift_data_for_report_overview'] = $lift_data_for_report_overview[0];
						}
						else
						{
							$result['is_success'] = false;

						}
					}
				}
			}
			else
			{
				$result['is_success'] = false;
			}
		}
		else
		{
			$result['is_success'] = false;
		}

		echo json_encode($result);
	}

	public function get_lift_data_for_report_detail()
	{
		$result = array();
		$result['is_success'] = true;

		$required_post_variables = array('advertiser_id','campaign_values','campaign_values_set','start_date', 'end_date');
		$ajax_verify = vl_verify_ajax_post_variables($required_post_variables);

		if ($ajax_verify['is_success'])
		{
			$advertiser_id = $ajax_verify['post']['advertiser_id'];
			if(!is_array($advertiser_id))
			{
				$advertiser_id = array($advertiser_id);
			}

			$campaign_ids = $ajax_verify['post']['campaign_values'];
			$campaign_values = $ajax_verify['post']['campaign_values_set'];
			$date_report_start = $ajax_verify['post']['start_date'];
			$date_report_end = $ajax_verify['post']['end_date'];
			$user_id = $this->tank_auth->get_user_id();
			$accessible_subfeatures = $this->report_v2_model->get_subfeature_access_permissions($user_id);

			if ($accessible_subfeatures['is_lift_report_accessible'])
			{
				//Fetch all campaigns average lift data.
				$lift_data_for_report_overview = $this->report_v2_model->get_lift_data_for_report_overview($campaign_ids, $date_report_start, $date_report_end);
				if ($lift_data_for_report_overview && count($lift_data_for_report_overview) > 0)
				{
					$avg_lift = $lift_data_for_report_overview[0]['average_lift'];
					if (isset($avg_lift) && $avg_lift != null)
					{
						if ($avg_lift > 9.0)
						{
							$avg_lift = intval($avg_lift);
						}
						else
						{
							$avg_lift = number_format(floatval($avg_lift),1);
						}
						$lift_data_for_report_overview[0]['average_lift'] = $avg_lift;
					}

					$result['lift_data_for_report_overview'] = $lift_data_for_report_overview[0];

					//Fetch average lift data for all campaigns by date.
					$lift_data_for_report_overview_per_day = $this->report_v2_model->get_lift_data_for_report_overview($campaign_ids, $date_report_start, $date_report_end, true);
					if ($lift_data_for_report_overview_per_day && count($lift_data_for_report_overview_per_day) > 0)
					{
						$result['lift_data_for_report_overview_per_day'] = $lift_data_for_report_overview_per_day;
					}
					else
					{
						$result['is_success'] = false;
					}
				}
				else
				{
					$result['is_success'] = false;
				}
			}
			else
			{
				$result['is_success'] = false;
			}
		}
		else
		{
			$result['is_success'] = false;
		}

		echo json_encode($result);
	}


	public function get_lift_data_per_zip_for_campaigns()
	{
		$geojson_for_map = ['is_success' => false, 'geojson_blob' => null, 'geojson_blob_v2' => null];
		$lift_data_for_zip_codes = array();
		$required_post_variables = array('campaign_values','start_date', 'end_date');
		$ajax_verify = vl_verify_ajax_post_variables($required_post_variables);

		if ($ajax_verify['is_success'])
		{
			$campaign_ids = $ajax_verify['post']['campaign_values'];
			$date_report_start = $ajax_verify['post']['start_date'];
			$date_report_end = $ajax_verify['post']['end_date'];
			$rank_max = 0;
			$user_id = $this->tank_auth->get_user_id();
			$accessible_subfeatures = $this->report_v2_model->get_subfeature_access_permissions($user_id);

			if ($accessible_subfeatures['is_lift_report_accessible'])
			{
				$result = $this->report_v2_model->get_lift_data_per_zip_for_campaigns($campaign_ids, $date_report_start, $date_report_end);

				if ($result && count($result) > 0)
				{
					for ($i=0; $i<count($result); $i++)
					{
						$zip_code = $result[$i]['zip'];
						$city = $result[$i]['city'];
						$population_total = intval($result[$i]['population_total']);
						$avg_conversion_rate = floatval($result[$i]['average_conversion_rate']);
						$avg_lift = floatval($result[$i]['average_lift']);
						$avg_baseline = $result[$i]['average_baseline'];
						$avg_home_value = intval($result[$i]['avg_home_value']);
						$percentage_avg_home_value = floatval($result[$i]['percentage_avg_home_value']);
						$median_age = floatval($result[$i]['avg_median_age']);
						$percentage_median_age = floatval($result[$i]['percentage_avg_median_age']);
						$avg_income = intval($result[$i]['avg_income']);
						$percentage_avg_income = floatval($result[$i]['percentage_avg_income']);
						$avg_occupancy = floatval($result[$i]['avg_occupancy']);
						$percentage_avg_occupancy = floatval($result[$i]['percentage_avg_occupancy']);
						$percentage_opacity = null;
						if (!isset($avg_lift) || $avg_lift == null)
						{
							$percentage_opacity = 0.50 * 0.8;
						}else
						{
							$rank_max++;
							if ($avg_lift > 9.0)
							{
								$avg_lift = intval($avg_lift);
							}
							else
							{
								$avg_lift = number_format($avg_lift,1);
							}
						}

						$lift_data_for_zip_codes[$zip_code] = array(
							'city' => $city,
							'population_total' => number_format($population_total),
							'avg_conversion_rate' => number_format($avg_conversion_rate,1),
							'avg_lift' => $avg_lift,
							'percentage_opacity' =>  $percentage_opacity,
							'avg_baseline' => number_format($avg_baseline,1),
							'avg_home_value' => number_format($avg_home_value),
							'percentage_avg_home_value' => number_format($percentage_avg_home_value),
							'median_age' => number_format($median_age),
							'percentage_median_age' => number_format($percentage_median_age),
							'avg_income' => number_format($avg_income),
							'percentage_avg_income' => number_format($percentage_avg_income),
							'avg_per_household' => number_format($avg_occupancy, 1),
							'percentage_avg_per_household' => number_format($percentage_avg_occupancy),
						);
					}
				}

				if (count($lift_data_for_zip_codes) > 0)
				{
					$norms_inv = array();
					$rank = 0;
					foreach ($lift_data_for_zip_codes as $key=>$value)
					{
						if ($value['percentage_opacity'] == null)
						{
							$rank++;
							$unique_percentile = ($rank/($rank_max+1));
							$norms_inv[] = $this->norms_inv($unique_percentile);
						}
					}

					if (count($norms_inv) > 0)
					{
						$norms_inv_min = min($norms_inv);
						$norms_inv_max = max($norms_inv);

						$rank = 0;
						foreach ($lift_data_for_zip_codes as $key=>$value)
						{
							if ($value['percentage_opacity'] == null)
							{
								$norms_inv_elem = $norms_inv[$rank++];
								$opacity = number_format((($norms_inv_elem-$norms_inv_min)/($norms_inv_max - $norms_inv_min)),2);
								$opacity = $opacity * 0.8;
								$lift_data_for_zip_codes[$key]['percentage_opacity'] = $opacity;
							}
						}
					}
				}

				$geojson_for_lift = $this->map->get_report_lift_geojson_with_data($lift_data_for_zip_codes);
				$geojson_for_map = ['is_success' => true, 'geojson_blob' => $geojson_for_lift];
			}
			else
			{
				$geojson_for_map = ['is_success' => false, 'geojson_blob' => null];
			}
		}

		echo json_encode($geojson_for_map);
	}

	private function norms_inv($p)
	{
		$a1 = -39.6968302866538; $a2 = 220.946098424521; $a3 = -275.928510446969;
		$a4 = 138.357751867269; $a5 = -30.6647980661472; $a6 = 2.50662827745924;
		$b1 = -54.4760987982241; $b2 = 161.585836858041; $b3 = -155.698979859887;
		$b4 = 66.8013118877197; $b5 = -13.2806815528857; $c1 = -7.78489400243029E-03;
		$c2 = -0.322396458041136; $c3 = -2.40075827716184; $c4 = -2.54973253934373;
		$c5 = 4.37466414146497; $c6 = 2.93816398269878; $d1 = 7.78469570904146E-03;
		$d2 = 0.32246712907004; $d3 = 2.445134137143; $d4 = 3.75440866190742;
		$p_low = 0.01; $p_high = 1 - $p_low;
		$q = 0.0; $r = 0.0;
		if($p < 0 || $p > 1)
		{
		   throw new Exception("NormSInv: Argument out of range.");
		}
		else if($p < $p_low)
		{
		   $q = pow(-2 * log($p), 2);
		   $norms_inv = ((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) /
		      (((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);
		}
		else if($p <= $p_high)
		{
		   $q = $p - 0.5; $r = $q * $q;
		   $norms_inv = ((((($a1 * $r + $a2) * $r + $a3) * $r + $a4) * $r + $a5) * $r + $a6) * $q /
		      ((((($b1 * $r + $b2) * $r + $b3) * $r + $b4) * $r + $b5) * $r + 1);
		}
		else
		{
		   $q = pow(-2 * log(1 - $p), 2);
		   $norms_inv = -((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) /
		      (((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);
		}
		return $norms_inv;
	}

	public function get_airings_data_for_zone_network()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$advertiser_ids = $this->input->post('advertiser_ids');
			if(!is_array($advertiser_ids))
			{
				$advertiser_ids = array($advertiser_ids);
			}

			$account_ids = $this->input->post('account_ids');
			if(!is_array($account_ids))
			{
				$account_ids = array($account_ids);
			}

			$zone = $this->input->post('zone');
			$network = $this->input->post('network');
		}
		else
		{
			$is_success = false;
		}

		$return_data = $this->report_v2_model->get_airings_data_for_zone_network($advertiser_ids, $account_ids, $zone, $network);
		$json_encoded_data = $this->get_report_data_json($return_data, $error_info, $is_success);
		echo $json_encoded_data;
	}

	public function get_campaigns_lift_data_for_report_detail()
	{
		$result = array();
		$result['is_success'] = true;

		$required_post_variables = array('advertiser_id','campaign_values','campaign_values_set','start_date', 'end_date');
		$ajax_verify = vl_verify_ajax_post_variables($required_post_variables);

		if ($ajax_verify['is_success'])
		{
			$advertiser_id = $ajax_verify['post']['advertiser_id'];
			if(!is_array($advertiser_id))
			{
				$advertiser_id = array($advertiser_id);
			}

			$campaign_ids = $ajax_verify['post']['campaign_values'];
			$campaign_values = $ajax_verify['post']['campaign_values_set'];
			$date_report_start = $ajax_verify['post']['start_date'];
			$date_report_end = $ajax_verify['post']['end_date'];
			$user_id = $this->tank_auth->get_user_id();
			$accessible_subfeatures = $this->report_v2_model->get_subfeature_access_permissions($user_id);

			if ($accessible_subfeatures['is_lift_report_accessible'])
			{
				$campaign_names = $this->report_v2_model->get_campaign_names_from_ids($campaign_ids);
				$lift_data_for_campaign_overview = $this->report_v2_model->get_lift_data_for_campaign_overview($campaign_ids, $date_report_start, $date_report_end);
				$lift_data_for_campaign_overview_per_month = $this->report_v2_model->get_lift_data_for_campaign_overview($campaign_ids, $date_report_start, $date_report_end, true);
				$campaign_creatives = $this->get_campaign_creatives($advertiser_id, $campaign_values, $date_report_start, $date_report_end);

				$campaign_entries = array();

				if ($lift_data_for_campaign_overview)
				{
					foreach ($lift_data_for_campaign_overview AS $campaign_overview)
					{
						$campaign_id = $campaign_overview['campaign_id'];

						if (!isset($campaign_entries[$campaign_id]) && !isset($campaign_entries[$campaign_id]['overview']))
						{
							$avg_lift_for_campaign = floatval($campaign_overview['average_lift']);

							if (isset($avg_lift_for_campaign) && $avg_lift_for_campaign != null)
							{
								$campaign_entries[$campaign_id]['overview'] = $campaign_overview;
								$campaign_entries[$campaign_id]['campaign_id'] = $campaign_id;
								$campaign_entries[$campaign_id]['campaign_name'] = $campaign_names[$campaign_id];

								if ($avg_lift_for_campaign > 9.0)
								{
									$avg_lift_for_campaign = intval($avg_lift_for_campaign);
								}
								else
								{
									$avg_lift_for_campaign = number_format(floatval($avg_lift_for_campaign),1);
								}

								$campaign_entries[$campaign_id]['overview']['average_lift'] = $avg_lift_for_campaign;
							}
						}
					}
				}

				if ($lift_data_for_campaign_overview_per_month)
				{
					foreach ($lift_data_for_campaign_overview_per_month AS $campaign_overview_per_moth_entry)
					{
						$campaign_id = $campaign_overview_per_moth_entry['campaign_id'];
						if (isset($campaign_entries[$campaign_id]))
						{
							if (!isset($campaign_entries[$campaign_id]['overview_per_month']))
							{
								$campaign_entries[$campaign_id]['overview_per_month'] = array();
							}

							$campaign_entries[$campaign_id]['overview_per_month'][] = $campaign_overview_per_moth_entry;
						}
					}
				}

				if ($campaign_creatives)
				{
					foreach ($campaign_creatives AS $campaign_id => $campaign_creatives_entry)
					{
						if (isset($campaign_entries[$campaign_id]))
						{
							if (!isset($campaign_entries[$campaign_id]['creatives']))
							{
								$campaign_entries[$campaign_id]['creatives'] = array();
							}

							$campaign_entries[$campaign_id]['creatives'] = $campaign_creatives_entry;
						}
					}
				}

				$campaign_lift_entry = array();

				foreach (array_values($campaign_entries) AS $lift_entry)
				{
					$campaign_lift_entry[] = array("campaigns_lift" => $lift_entry);
				}

				$result['campaigns_lift_data_set'] = $campaign_lift_entry;
			}
			else
			{
				$result['is_success'] = false;
			}
		}
		else
		{
			$result['is_success'] = false;
		}

		echo json_encode($result);
	}


	private function get_campaign_creatives($advertiser_id, $campaign_values, $date_report_start, $date_report_end)
	{
		//Fetch creatives for all the passed campaigns.
		$campaign_creatives = array();
		$campaign_set = new report_campaign_set($campaign_values);
		$creative_ids = $this->report_v2_model->get_lift_creative_ids($advertiser_id,$campaign_set,$date_report_start,$date_report_end);

		if (isset($creative_ids) && count($creative_ids) > 0)
		{
			$creative_data_sets = $this->report_v2_model->get_lift_creative_messages($creative_ids);

			if (isset($creative_data_sets) &&  count($creative_data_sets) > 0)
			{
				$cup_version_ids = array();

				foreach ($creative_data_sets AS $creative_data_set_entry)
				{
					$cup_version_ids[] = $creative_data_set_entry['creative_data']['cup_version_id'];
				}

				if (count($cup_version_ids) > 0)
				{
					$campaign_version_mapping = $this->report_v2_model->get_campaigns_from_version_ids(implode(",",$cup_version_ids));

					if ($campaign_version_mapping)
					{
						foreach ($creative_data_sets AS $creative_data_set_entry)
						{
							$version_id = $creative_data_set_entry['creative_data']['cup_version_id'];

							if (isset($campaign_version_mapping[$version_id]))
							{
								$campaign_ids_for_version = $campaign_version_mapping[$version_id];

								for ($cnt=0;$cnt<count($campaign_ids_for_version);$cnt++)
								{
									$campaign_creatives[$campaign_ids_for_version[$cnt]][] = $creative_data_set_entry;
								}
							}
						}
					}
				}
			}
		}

		return $campaign_creatives;
	}
}
