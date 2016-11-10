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

class report_legacy extends CI_Controller
{
	private $products_and_subproducts_config = null;
	private $subfeature_access_permissions = array();

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array(
			'map_legacy',
			'session',
			'tank_auth',
			'vl_platform'
		));
		$this->load->model(array(
			'report_legacy_model',
			'tmpi_model'
		));
		$this->load->helper(array(
			'url',
			'report_v2_controller_helper',
			'select2_helper'
		));

		// The Frequence Partner id is a special case '1'
		define('k_brandcdn_partner_id', 1);
		define('k_vl_report_num_decimal_places', 2);
	}

	// parallel code exists in javascript
	public function get_starting_tabs_html($organization_products)
	{
		$tabs_html = '';
		foreach($organization_products as $product)
		{
			$image_html = "";
			if(!empty($product['image_path']))
			{
				$image_html = '<img src="/assets/img/'.$product['image_path'].'"/>';
			}

			$brand_html = "";
			if(!empty($product['product_brand']))
			{
				$brand_html = '<span class="product_brand">'.$product['product_brand'].'</span>';
			}

			$product_type_html = "";
			if(!empty($product['product_type']))
			{
				$product_type_html = '<span class="product_type">'.$product['product_type'].'</span>';
			}

			// structure is in javascript too
			$tabs_html .= '
				<a href="#" id="'.$product['html_element_id'].'" class="disabled" onclick="disable_tab_click(event); return false;">
					'.$image_html.'
					'.$brand_html.'
					'.$product_type_html.'
				</a>
			';
		}

		return $tabs_html;
	}

	private function init_access_and_configs($user_id)
	{
		$this->subfeature_access_permissions = $this->report_legacy_model->get_subfeature_access_permissions(
			$user_id
		);

		$beta_flag_html = '<span style="font-size:8.5px; font-family:Arial, Helvetica, sans-serif; position:relative; left:5px; top:-2px;" class="beta badge badge-important">Beta</span>';

		$product_tab_visuals_by_organization = array(
			report_campaign_organization::frequence => array(
				report_product_tab_html_id::overview_product => array(
					'html_element_id' => report_product_tab_html_id::overview_product,
					'image_path' => '',
					'product_brand' => 'Overview',
					'product_type' => ''
				),
				report_product_tab_html_id::display_product => array(
					'html_element_id' => report_product_tab_html_id::display_product,
					'image_path' => '',
					'product_brand' => frequence_product_names::visual_frequence_display,
					'product_type' => ''
				),
				report_product_tab_html_id::pre_roll_product => array(
					'html_element_id' => report_product_tab_html_id::pre_roll_product,
					'image_path' => '',
					'product_brand' => frequence_product_names::pre_roll,
					'product_type' => ''
				),
				report_product_tab_html_id::targeted_clicks_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_clicks_product,
					'image_path' => '',
					'product_brand' => tmpi_product_names::clicks,
					'product_type' => ''
				),
				report_product_tab_html_id::targeted_inventory_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_inventory_product,
					'image_path' => '',
					'product_brand' => tmpi_product_names::inventory,
					'product_type' => ''
				),
				report_product_tab_html_id::targeted_directories_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_directories_product,
					'image_path' => '',
					'product_brand' => tmpi_product_names::directories,
					'product_type' => ''
				),
				report_product_tab_html_id::targeted_content_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_content_product,
					'image_path' => '',
					'product_brand' => carmercial_product_names::content,
					'product_type' => ''
				)
			),
			report_campaign_organization::tmpi => array(
				report_product_tab_html_id::overview_product => array(
					'html_element_id' => report_product_tab_html_id::overview_product,
					'image_path' => 'charter_tmpi/overview.png',
					'product_brand' => 'Campaigns',
					'product_type' => 'Overview'
				),
				report_product_tab_html_id::display_product => array(
					'html_element_id' => report_product_tab_html_id::display_product,
					'image_path' => 'charter_tmpi/devices.png',
					'product_brand' => 'Targeted',
					'product_type' => frequence_product_names::visual_spectrum_display
				),
				report_product_tab_html_id::pre_roll_product => array(
					'html_element_id' => report_product_tab_html_id::pre_roll_product,
					'image_path' => 'charter_tmpi/preroll.png',
					'product_brand' => 'Targeted',
					'product_type' => 'PreRoll'
				),
				report_product_tab_html_id::targeted_clicks_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_clicks_product,
					'image_path' => 'charter_tmpi/visits.png',
					'product_brand' => 'Targeted',
					'product_type' => tmpi_product_names::clicks
				),
				report_product_tab_html_id::targeted_inventory_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_inventory_product,
					'image_path' => 'charter_tmpi/leads.png',
					'product_brand' => 'Targeted',
					'product_type' => tmpi_product_names::inventory
				),
				report_product_tab_html_id::targeted_directories_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_directories_product,
					'image_path' => 'charter_tmpi/directories.png',
					'product_brand' => 'Targeted',
					'product_type' => tmpi_product_names::directories
				),
				report_product_tab_html_id::targeted_content_product => array(
					'html_element_id' => report_product_tab_html_id::targeted_content_product,
					'image_path' => 'charter_tmpi/content.png',
					'product_brand' => 'Targeted',
					'product_type' => carmercial_product_names::content
				)
			)
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
							'subproduct_csv_file_segment' => 'creatives'
						),
						'geography_subproduct' => array(
							'html_element_id' => 'geography_subproduct',
							'title' => 'Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_geography_data_no_pre_roll_yes_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'geography'
						),
						'placements_subproduct' => array(
							'html_element_id' => 'placements_subproduct',
							'title' => 'Placements',
							'is_accessible' => $this->subfeature_access_permissions['are_placements_accessible'],
							'get_data_function_name' => 'get_placements_data_no_pre_roll_yes_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'placements'
						),
						'ad_sizes_subproduct' => array(
							'html_element_id' => 'ad_sizes_subproduct',
							'title' => 'Ad Sizes',
							'is_accessible' => $this->subfeature_access_permissions['are_ad_sizes_accessible'],
							'get_data_function_name' => 'get_ad_sizes_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'ad_sizes'
						),
						'interactions_subproduct' => array(
							'html_element_id' => 'interactions_subproduct',
							'title' => 'Engagements',
							'is_accessible' => $this->subfeature_access_permissions['are_ad_interactions_accessible'],
							'get_data_function_name' => 'get_interactions_data',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'engagements'
						),
						'screenshots_subproduct' => array(
							'html_element_id' => 'screenshots_subproduct',
							'title' => 'Screenshots',
							'is_accessible' => $this->subfeature_access_permissions['are_screenshots_accessible'],
							'get_data_function_name' => 'get_screenshots_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'screenshots'
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
							'subproduct_csv_file_segment' => 'geography'
						),
						'placements_subproduct' => array(
							'html_element_id' => 'placements_subproduct',
							'title' => 'Placements',
							'is_accessible' => $this->subfeature_access_permissions['are_placements_accessible'],
							'get_data_function_name' => 'get_placements_data_yes_pre_roll_no_display',
							'can_download_csv' => true,
							'subproduct_csv_file_segment' => 'placements'
						),
						'pre_roll_subproduct' => array(
							'html_element_id' => 'pre_roll_subproduct',
							'title' => 'Video Stats'.$beta_flag_html,
							'is_accessible' => $this->subfeature_access_permissions['is_pre_roll_accessible'],
							'get_data_function_name' => 'get_video_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'pre_roll'
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
							'subproduct_csv_file_segment' => 'clicks'
						),
						'geo_clicks_subproduct' => array(
							'html_element_id' => 'geo_clicks_subproduct',
							'title' => 'Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_geo_clicks_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'geo_clicks'
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
						'leads_totals_subproduct' => array(
							'html_element_id' => 'leads_totals_subproduct',
							'title' => tmpi_product_names::inventory.'<br>Totals',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_leads_totals_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'leads_totals'
						),
						'leads_details_subproduct' => array(
							'html_element_id' => 'leads_details_subproduct',
							'title' => tmpi_product_names::inventory.'<br>Details',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_leads_details_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'leads_details'
						),
						'search_and_listings_subproduct' => array(
							'html_element_id' => 'search_and_listings_subproduct',
							'title' => 'Search & Listings',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_search_and_listings_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'search_and_listings'
						),
						'top_vehicles_subproduct' => array(
							'html_element_id' => 'top_vehicles_subproduct',
							'title' => 'Vehicles',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_top_vehicles_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'top_vehicles'
						),
						'vehicles_inventory_subproduct' => array(
							'html_element_id' => 'vehicles_inventory_subproduct',
							'title' => 'Vehicle Inventory',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_vehicles_inventory_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'vehicles_inventory'
						),
						'price_breakdown_subproduct' => array(
							'html_element_id' => 'price_breakdown_subproduct',
							'title' => 'Vehicle<br>Price Breakdown',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_inventory_price_breakdown_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'price_breakdown'
						),
						'smart_clicks_subproduct' => array(
							'html_element_id' => 'smart_clicks_subproduct',
							'title' => 'Smart Ads<br>Totals',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_smart_ads_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'smart_ad_clicks'
						),
						'smart_geo_clicks_subproduct' => array(
							'html_element_id' => 'smart_geo_clicks_subproduct',
							'title' => 'Smart Ads<br>Geography',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_smart_ads_geo_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'smart_ad_geo_clicks'
						)
					)
				)
			),
			report_product_tab_html_id::targeted_directories_product => array(
				'tab' => array(), // actual data merged from $product_tab_visuals_by_organization
				'product_csv_file_segment' => 'directories',
				'subproducts_data' => array(
					'data_view_class' => 'targeted_directories_subproduct',
					'subproduct_items' => array(
						'local_search_profile_activity_subproduct' => array(
							'html_element_id' => 'local_search_profile_activity_subproduct',
							'title' => 'Local Search',
							'is_accessible' => true,
							'get_data_function_name' => 'get_tmpi_targeted_directories_local_search_profile_activity_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'local_search_data'
						)
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
							'title' => 'Carmercial Content',
							'is_accessible' => true,
							'get_data_function_name' => 'get_carmercial_targeted_content_data',
							'can_download_csv' => false,
							'subproduct_csv_file_segment' => 'local_search_data'
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

		// Always show Display and Pre-Roll
		$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::display_product]['tab'];
		$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::pre_roll_product]['tab'];

		if($this->subfeature_access_permissions['is_tmpi_accessible'])
		{
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_clicks_product]['tab'];
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_inventory_product]['tab'];
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_directories_product]['tab'];
			$products_to_use[] = $this->products_and_subproducts_config[report_product_tab_html_id::targeted_content_product]['tab'];
		}

		return $products_to_use;
	}

	private function build_subproduct_data(
		$input_active_product, 
		$input_active_subproduct,
		$advertiser_id,
		$campaign_set,
		$start_date,
		$end_date,
		$subfeature_access_permissions,
		$build_data_type
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

				$callback = array($this->report_legacy_model, $table_function);

				$params = array(
					$advertiser_id,
					$campaign_set,
					$start_date,
					$end_date,
					$input_active_product, 
					$input_active_subproduct,
					$subfeature_access_permissions,
					$build_data_type
				);

				$result = call_user_func_array($callback, $params);
			}
		}

		return $result;
	}

	private function build_table_data(
		$input_active_product, 
		$input_active_subproduct,
		$advertiser_id,
		$campaign_set,
		$start_date,
		$end_date,
		$subfeature_access_permissions,
		$build_data_type
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

				$callback = array($this->report_legacy_model, $table_function);

				$params = array(
					$advertiser_id,
					$campaign_set,
					$start_date,
					$end_date,
					$input_active_product, 
					$input_active_subproduct,
					$subfeature_access_permissions,
					$build_data_type
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
				$filename_stub = $product_segment."_".$subproduct_segment;
			}
		}

		return $filename_stub;
	}

	// check if $advertisers is in the set of advertisers viewable by the current user
	private function is_access_allowed($advertiser_id)
	{
		if(!empty($advertiser_id))
		{
			$allowed_advertisers = $this->get_advertisers();
			foreach($allowed_advertisers as $allowed_advertiser)
			{
				if($advertiser_id == $allowed_advertiser['id'])
				{
					return true;
				}
			}
		}

		return false;
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

				if(empty($advertiser_id) || $this->is_access_allowed($advertiser_id))
				{
					$return_data['is_access_allowed'] = true;
				}
				else
				{
					$return_data['errors'][] = 'Access denied to user for chosen advertiser';
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
					$campaigns = $this->report_legacy_model->get_report_campaigns($input->advertiser_id);
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

		$tmpi_presence_response = $this->report_legacy_model->get_tmpi_account_data_presence(
			$inputs->advertiser_id,
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

		$carmercial_presence_response = $this->report_legacy_model->get_carmercial_data_presence(
			$inputs->advertiser_id,
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

		$frequence_presence_response = $this->report_legacy_model->get_frequence_account_data_presence(
			$inputs->advertiser_id,
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

				$summary_response = $this->report_legacy_model->get_summary_data_by_id(
					$inputs->advertiser_id,
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

				$graph_data = $this->get_graph_data_by_id(
					$inputs->advertiser_id,
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

			$source = $this->input->post('source', true) ?: "No source supplied";
			$advertiser_id = $this->input->post('advertiser_id');
			$campaign_string = $this->input->post('campaign_values');
			$raw_start_date = $this->input->post('start_date');
			$raw_end_date = $this->input->post('end_date');
			$action = $this->input->post('action');
			
			$campaign_set=null;
			if ($action == 'change_advertiser')
			{
				 	$campaigns = $this->report_legacy_model->get_report_campaigns($inputs->advertiser_id);
                    $inputs->campaign_set = new report_campaign_set($campaigns);
			}
			else if ($campaign_string)
			{
				$inputs->campaign_set = new report_campaign_set($campaign_string);
			}
			$data = null;
			
			switch($source)
			{
				case 'visits':
					$data = array();
					break;
				case 'creatives':
					$data = $this->report_legacy_model->get_overview_top_creatives($inputs->advertiser_id, $inputs->campaign_set, $inputs->start_date, $inputs->end_date);
					break;
				case 'placements':
					$data_server = $this->report_legacy_model->get_placements_data_no_pre_roll_yes_display(
						$inputs->advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						null,
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
								if ($site == null || $site == "" || $site == 'All other sites')
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

					$data_server = $this->report_legacy_model->get_video_core_data(
						$inputs->advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						null,
						'OVERVIEW'
					);
					$data=array();
					if ($data_server != null)
					{
						foreach($data_server->result_array() as $row)
						{
							$data = array(
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
							
							if (
									(int)$row['video_started'] == 0 && 
									(int)$row['25_percent_viewed'] == 0 && 
									(int)$row['50_percent_viewed'] == 0 &&
									(int)$row['75_percent_viewed'] == 0 && 
									(int)$row['100_percent_viewed'] == 0
								)
								$data=array();
						}
					}
					break;
				case 'clicks':
					$data_server = $this->report_legacy_model->get_targeted_visits_clicks_for_overview_page(
						$inputs->advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date
					);
					$data=array();
					if ($data_server != null)
					{
						foreach($data_server as $row)
						{
							$data[]=array('name' => $row['day'], 'data' => (int)$row['clicks'], 'term' => $row['term']);
						}
					}
					break;
				case 'inventory_price':
					$data_raw = $this->report_legacy_model->get_tmpi_targeted_inventory_price_breakdown_data($inputs->advertiser_id, $inputs->campaign_set, $inputs->start_date, 
						$inputs->end_date, null, null, null, k_build_table_type::csv);
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
					$data_server = $this->report_legacy_model->get_tmpi_targeted_directories_local_search_profile_activity_core_data(
						$inputs->advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						null,
						array(new report_table_starting_column_sort(1, 'desc'))
					);
					$data = array();
					foreach ($data_server->result_array() as $row) 
					{
						$data[]=array('name' => $row['short_name'], 'data' => (int)$row['total']);
					}
					break;
				case 'content':
					$data_response = $this->report_legacy_model->get_carmercial_targeted_content_overview_data(
						$inputs->advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						null,
						null,
						null,
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

				$tabs_data = $this->get_products($inputs);
				if($tabs_data['new_active_product'] != report_product_tab_html_id::overview_product)
				{
					$subproducts_nav_data = $this->get_subproducts_nav_data($tabs_data['new_active_product'], $inputs);

					$subproduct_data = $this->build_subproduct_data(
						$tabs_data['new_active_product'], 
						$subproducts_nav_data['new_active_subproduct'],
						$inputs->advertiser_id,
						$inputs->campaign_set,
						$inputs->start_date,
						$inputs->end_date,
						$this->subfeature_access_permissions,
						k_build_table_type::html
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

	public function get_creative_messages()
	{
		$return_data = array();
		$error_info = array();
		$is_success = true;

		if($this->verify_ajax_access($return_data))
		{
			$advertiser_id = $this->input->post('advertiser_id');
			$creative_ids = $this->input->post('creative_ids');
			$campaign_ids = new report_campaign_set($this->input->post('campaign_ids'));
			$raw_start_date = $this->input->post('start_date');
			$raw_end_date = $this->input->post('end_date');
			//$return_data['creative_ids'] = $creative_ids;
			$creative_data_sets = $this->report_legacy_model->get_creative_messages($advertiser_id, $campaign_ids, $raw_start_date, $raw_end_date, $creative_ids);
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

			if($user->role == 'business')
			{
				$partner_id = $this->tank_auth->get_partner_id_by_advertiser_id($user->advertiser_id);
			}

			$redirect = $this->get_redirect_if_wrong_user_type($user->role, $partner_id);
			if($redirect == '')
			{
				$advertiser_id = $this->input->post('advertiser_id');
				if($this->is_access_allowed($advertiser_id))
				{
					$temp_campaign_values = $this->input->post('campaign_values');
					$campaign_values = explode(",", $temp_campaign_values);
					$campaign_set = new report_campaign_set($campaign_values);
					$start_date = $this->input->post('start_date');
					$end_date = $this->input->post('end_date');
					$input_active_product = $this->input->post('tab'); // TODO: change to product
					$input_active_subproduct = $this->input->post('subproduct_nav_pill');

					$this->init_access_and_configs($user_id);

					$data = array();

					$data['are_view_throughs_accessible'] = $this->subfeature_access_permissions['are_view_throughs_accessible'];

					$csv_data_array = array();

					if($input_active_product == report_product_tab_html_id::overview_product)
					{
						$input_active_product = 'graph';
						$input_active_subproduct = 'graph';
					}

					if(!empty($input_active_product) && !empty($input_active_subproduct))
					{
						$csv_data_array = $this->build_table_data(
							$input_active_product, 
							$input_active_subproduct,
							$advertiser_id,
							$campaign_set,
							$start_date,
							$end_date,
							$this->subfeature_access_permissions,
							k_build_table_type::csv
						);

						$filename_stub = $this->build_csv_filename_stub(
							$input_active_product, 
							$input_active_subproduct
						);

						$data['filename_stub'] = $filename_stub;
					}

					if(!empty($csv_data_array))
					{
						$data['data_response'] = $csv_data_array;
						$this->load->view('report_legacy/download_csv', $data);
						return;
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
		$response = $this->report_legacy_model->get_graph_data_by_id(
			$advertiser_id,
			$campaign_set,
			$begin_date,
			$end_date,
			$subfeature_access_permissions,
			false
		);
		
		$graph_data = array();
		if(!empty($response) && $response->num_rows() > 0)
		{
			$response_data = array();
			foreach($response->result_array() as $row)
			{
				$response_data[$row['date']] = $row;
			}
			
			$zero_data = $this->zero_dates($response_data, $begin_date, $end_date);
			$graph_data = array_values($zero_data);
		}

		return $graph_data;
	}

        
	// gets the set of advertisers this user has access to
	private function get_advertisers()
	{
		$businesses = array();

		$user_id = $this->tank_auth->get_user_id();
		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);

		$advertisers = array();
		if($user->role == 'sales')
		{
			$advertisers = $this->tank_auth->get_advertisers_by_sales_person_partner_hierarchy($user_id, $user->isGroupSuper);
		}
		else if($user->role == 'business')
		{
			$advertiser_name = $this->report_legacy_model->get_advertiser_name($user->advertiser_id);
			$advertisers = array(array('Name' => $advertiser_name, 'id' => $user->advertiser_id));
		}
		else if($user->role == 'admin' || $user->role == 'ops')
		{
			$advertisers = $this->tank_auth->get_businesses();
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

	private function get_first_advertiser_row(array $advertisers)
	{
		$advertiser_id = 0;
		if(count($advertisers) > 0)
		{
			$advertiser = $advertisers[0];
		}

		return $advertiser;
	}		

	// TODO: verfiy pl_permissions is used
	// get where the user should be redirected to if they aren't allowed to access the report
	private function get_redirect_if_wrong_user_type($role, $partner_id)
	{
		if($partner_id != k_brandcdn_partner_id)
		{
			if($role != 'sales' &&
			$role != 'business')
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
		if(!$this->tank_auth->is_logged_in()) 
 		{
			$this->session->set_userdata('referer','reports');
			redirect(site_url("login"));
			return;
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
		$data['advertiser_selected'] = "{}";

		$data['is_whitelabel_business'] = $is_whitelabel_business;
		$partner_info = $this->tank_auth->get_partner_info($partner_id);
		$advertisers = $this->get_advertisers();

		if($data['role'] == "business")
		{
			$selected_advertiser_id = $this->get_first_advertiser_id($advertisers);
			$data['advertiser_selected_id'] = $selected_advertiser_id;
			$data['advertisers'] = $advertisers;
		}
		else
		{
			$data['advertiser_selected'] = json_encode($this->get_first_advertiser_row($advertisers));
		}

		$end_date_time = strtotime('-1 day, -7 hours');
		$end_date = date('m/d/Y', $end_date_time);
		$start_date = date('m/d/Y', strtotime('-1 month', $end_date_time));

		$this->init_access_and_configs($user_id);

		
		$data['end_date'] = $end_date;
		$data['start_date'] = $start_date;

		$data['firstname'] = $user->firstname;
		$data['lastname'] = $user->lastname;

		$data['are_screenshots_accessible'] = $this->subfeature_access_permissions['are_screenshots_accessible'];
		$data['are_ad_interactions_accessible'] = $this->subfeature_access_permissions['are_ad_interactions_accessible'];
		$data['are_engagements_accessible'] = $this->subfeature_access_permissions['are_engagements_accessible'];
		$data['are_view_throughs_accessible'] = $this->subfeature_access_permissions['are_view_throughs_accessible'];
		$data['is_tmpi_accessible'] 		= $this->subfeature_access_permissions['is_tmpi_accessible'];

		$products_tabs = $this->get_accessible_products();
		$data['starting_tabs'] = $this->get_starting_tabs_html($products_tabs);

		$data['css']                = '/css/web_report.css';
		$data['logo_path']          = $partner_info['partner_report_logo_filepath'];
		$data['favicon_path']       = $partner_info['favicon_path'];
		$data['home_url']           = $partner_info['home_url'];
		$data['contact_number']     = $partner_info['contact_number'];
		$data['report_css']         = $partner_info['report_css'];
		$data['partner_unique_id']  = $partner_info['unique_id'];

		$return_data = array();
		$error_info = array();
		$is_success = true;

		$active_feature_button_id = 'report_l';
		$data['title'] = 'Report';

		$this->vl_platform->show_views(
			$this,
			$data,
			$active_feature_button_id,
			'report_legacy/structure_and_data',
			'report_legacy/report_header',
			NULL,
			'report_legacy/report_js.php',
			NULL
		);

	}

	public function ajax_get_advertisers()
	{
		$user_id = $this->tank_auth->get_user_id();
		$user = $this->tank_auth->get_user_by_id($user_id);
		$user->role = strtolower($user->role);

		$advertiser_array = array('results' => array(), 'more' => false);
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->tank_auth->is_logged_in())
		{
			$post_array = $this->input->post();
			if(array_key_exists('q', $post_array) AND array_key_exists('page', $post_array) AND array_key_exists('page_limit', $post_array))
	  		{
	  			$additional_arguments = array();
				if($user->role == 'sales')
				{
					$additional_arguments['user_id'] = $user_id;
					$additional_arguments['is_super'] = $user->isGroupSuper;
					$advertiser_response = select2_helper($this->tank_auth, 'legacy_get_advertisers_by_sales_person_partner_hierarchy', $post_array, $additional_arguments);
					$advertisers = $this->tank_auth->get_advertisers_by_sales_person_partner_hierarchy($user_id, $user->isGroupSuper);
				}
				else if($user->role == 'business')
				{
					$advertiser_name = $this->report_legacy_model->get_advertiser_name($user->advertiser_id);
					$advertiser_array['results'][] = array('id' => $user->advertiser_id, 'text' => $advertiser_name);
					echo json_encode($advertiser_array);
					return;
				}
				else if($user->role == 'admin' || $user->role == 'ops')
				{
					$advertiser_response = select2_helper($this->tank_auth, 'get_businesses', $post_array);
				}
				
				if (!empty($advertiser_response['results']) && !$advertiser_response['errors'])
				{
					$advertiser_array['more'] = $advertiser_response['more'];
					for($i = 0; $i < $advertiser_response['real_count']; $i++)
					{
						$advertiser_array['results'][] = array(
					  		'id' => $advertiser_response['results'][$i]['id'],
					  		'text' => $advertiser_response['results'][$i]['Name']
					  	);
					}
			    }
	  		}
	  	echo json_encode($advertiser_array);
		}
		else
		{
		  show_404();
		}
	}

	private function zero_dates($graph_data, $start_date, $end_date)
	{
		//Generate date ranges
		$dates = array();
		$current = strtotime($start_date);
		$last = strtotime($end_date);
		while($current <= $last)
		{
			$dates[] = date('Y-m-d', $current);
			$current = strtotime('+1 day', $current);
		}
		
		//Return array
		$return_data = array();
		
		foreach($dates as $key)
		{
			if(!array_key_exists($key, $graph_data))
			{
				$return_data[$key] = array(
					"date" => $key,
					"total_impressions" => 0,
					"total_clicks" => 0,
					"rtg_impressions" => 0,
					"rtg_clicks" => 0,
					"engagements" => 0,
					"total_visits" => 0
					);
			}
			else
			{
				$return_data[$key] = $graph_data[$key];
			}
		}
		return $return_data;
	}
}
?>
