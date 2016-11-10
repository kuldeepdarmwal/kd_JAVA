<?php

class Proposals_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();

		$CI =& get_instance();
		$CI->load->model('rf_model', 'rf', true);
		$CI->load->model('mpq_v2_model', 'mpq', true);
		$CI->load->helper('strata_helper', 'strata', true);
		$CI->load->helper('tradedesk_helper', 'tradedesk', true);
		$CI->load->helper('demographics_helper', 'demographics', true);
		$CI->load->library('map', true);
	}

	public function get_proposal_by_id($proposal_id, $is_audience = true, $is_geo = true)
	{
		$this->load->library('map');

		$proposal = $this->get_proposal_data($proposal_id);
		$options = $this->get_option_data($proposal_id);
		$proposal['options'] = array();

		$palette = $this->get_proposal_palette_by_partner_id($proposal['partner_id']);
		$proposal = array_merge($proposal, $palette);

		$proposal['products'] = $this->get_product_data($proposal_id);
		if (empty($proposal['products']))
		{
			throw new Exception('Proposal '.$proposal_id.' is not an RFP proposal', 400);
		}

		$proposal['demo_string'] = $proposal['demographic_data'];
		$proposal['demographic_data'] = $this->parse_demo_string($proposal['demographic_data']);
		$proposal['selected_demos'] = $this->parse_demo_string_for_widgets($proposal['demo_string']);

		$is_geo_dependent = false;
		$is_audience_dependent = false;
		$is_rooftops_dependent = false;
		$is_tv_zones_dependent = false;
		$is_keywords_dependent = false;

		$is_political = false;
		$proposal['tv_zone_packages'] = array();
		$proposal['sem_keywords'] = array();
		$proposal['total_sem_clicks'] = 0;
		$proposal['tv_custom_packs'] = array();
		$custom_pack_type = "16 Network";
		$proposal['has_scx_data'] = false;
		$proposal['tv_scx_data_encoded'] = false;

		foreach($proposal['products'] as $product)
		{
			if($product['is_geo_dependent'] == 1)
			{
				$is_geo_dependent = true;
			}
			if($product['is_audience_dependent'] == 1)
			{
				$is_audience_dependent = true;
			}
			if($product['is_rooftops_dependent'] == 1)
			{
				$is_rooftops_dependent = true;
			}
			if($product['is_political'] == 1)
			{
				$is_political = true;
			}
			if($product['is_keywords_dependent'] == 1)
			{
				$is_keywords_dependent = true;
				$keywords_data = $this->get_keywords_data($proposal['source_mpq']);
				$proposal['total_sem_clicks'] = $keywords_data['clicks'];
				$proposal['sem_keywords'] = $keywords_data['search_terms'];
				array_splice($proposal['sem_keywords'], 36);
			}
			if($product['is_zones_dependent'] == 1)
			{
				$is_tv_zones_dependent = true;
				$temp_tv_zone_package = json_decode($product['submitted_values'], true);
				$proposal['tv_zone_packages'][$product['option_id']] = $temp_tv_zone_package;
				$definition = json_decode($product['definition'], true);
				$custom_pack_type = $definition['custom_pack_mapping'];
			}
			if ($product['product_type'] == 'tv_scx_upload')
			{
				$proposal['has_scx_data'] = true;
			}
		}

		$proposal['tv_zone_packs'] = $this->get_tv_zone_pack_data($proposal['source_mpq'], $proposal['tv_zone_packages'], $options, $custom_pack_type);
		$proposal['tv_zone_packs_json'] = json_encode($proposal['tv_zone_packs']);
		$proposal['tv_zones_json'] = json_encode(array());

		if($is_tv_zones_dependent)
		{
			$proposal['tv_width_class'] = "tv-three-package";
			$proposal['tv_budget_spots'] = $this->get_tv_spots($proposal['source_mpq'], $proposal['tv_zone_packages'], $options);
			$proposal['spots_total'] = 0;
			foreach($proposal['tv_budget_spots'] as $value)
			{
				$proposal['spots_total'] += $value;
			}

			$proposal['tv_zones_data'] = $this->mpq->get_rfp_tv_zones_by_mpq_id($proposal['source_mpq']);
			$proposal['tv_zones_json'] = json_encode($proposal['tv_zones_data']);

			$zone_ids = array();
			foreach($proposal['tv_zones_data'] as &$tv_zone){
				$zone_ids[] = $tv_zone['id'];
				$tv_zone['text'] = str_replace('Zone: ', '', $tv_zone['text']);
				$tv_zone['text'] = preg_replace("/\[[^)]+\]/","", $tv_zone['text']);
			}
			$proposal['tv_zone_pricing'] = $this->mpq->get_pricing_by_zones_and_packs_for_tv($zone_ids, array_filter(array_column($proposal['tv_zone_packages'], 'unit'), function($pack) {
				return $pack !== "custom";
			}));
			$proposal['tv_footer_class'] = "tv-16-pack";
			if(count($proposal['tv_zone_packs']) === 2)
			{
				$proposal['tv_width_class'] = "tv-two-package";
			}
			else if(count($proposal['tv_zone_packs']) === 1)
			{
				$proposal['tv_width_class'] = "tv-one-package";
			}
		}

		if ($proposal['has_scx_data'])
		{
			$tv_scx_data = $this->mpq->get_rfp_scx_upload_data($proposal['source_mpq']);
			if ($tv_scx_data)
			{
				$tv_scx_data['data'] = format_tv_schedule($tv_scx_data['data']);
				$proposal['has_scx_upload'] = true;
			}
			if ($tv_scx_data['selected_networks'])
			{
				$selected_networks = json_decode($tv_scx_data['selected_networks'], true);
				$networks_string = "'". implode($selected_networks, "','") ."'";
				$network_demographics = $this->get_tv_networks_demographics($networks_string, $proposal['selected_demos'], $proposal['partner_id']);
				if ($network_demographics)
				{
					$tv_scx_data['data']['networks'] = array_map(function($network) use ($selected_networks, $network_demographics) {
						$network['selected'] = array_search($network['name'], $selected_networks) !== false ? true : false;

						$demo_index = array_search($network['name'], array_column($network_demographics, 'name'));
						if ($demo_index !== false)
						{
							$network['friendly_name'] = $network_demographics[$demo_index]['friendly_name'];
							$network['logo'] = 'https://s3.amazonaws.com/brandcdn-assets/images/tv_network_logos/'.$network_demographics[$demo_index]['logo'];
							$network['demo_index'] = $network_demographics[$demo_index]['demo_index'];
						}
						return $network;
					}, $tv_scx_data['data']['networks']);
				}
			}
            $tv_scx_data['data']['networks_count'] = count($tv_scx_data['data']['networks']);
			$tv_scx_data['data']['num_zones'] = count($tv_scx_data['data']['geos']);
			$proposal['tv_scx_data'] = $tv_scx_data['data'];
		}

		$proposal['is_geo_dependent'] = $is_geo_dependent;
		$proposal['is_audience_dependent'] = $is_audience_dependent;
		$proposal['is_rooftops_dependent'] = $is_rooftops_dependent;
		$proposal['is_zones_dependent'] = $is_tv_zones_dependent;
		$proposal['is_political'] = $is_political;
		$proposal['is_keywords_dependent'] = $is_keywords_dependent;

		if($is_keywords_dependent)
		{
			$proposal['keywords_array'] = array();
		}

		// geofencing
		$proposal['geofences'] = array(
			'num_locations' => 0,
			'locations' => array(),
			'types' => array()
		);
		$geofences = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($proposal['source_mpq']);
		if (count($geofences) > 0)
		{
			foreach($geofences as $geofence)
			{
				if (!array_key_exists($geofence['location_id'], $proposal['geofences']['locations']))
				{
					$proposal['geofences']['locations'][$geofence['location_id']] = array();
				}

				if (!array_key_exists($geofence['type'], $proposal['geofences']['types']))
				{
					$proposal['geofences']['types'][$geofence['type']] = array(
						'name' => $geofence['type'],
						'num_locations' => 0,
						'max_radius' => 0
					);
				}

				$proposal['geofences']['num_locations']++;
				$proposal['geofences']['types'][$geofence['type']]['num_locations']++;

				if ($geofence['radius'] > $proposal['geofences']['types'][$geofence['type']]['max_radius'])
				{
					$proposal['geofences']['types'][$geofence['type']]['max_radius'] = $geofence['radius'];
				}
				if(!array_key_exists($geofence['type'], $proposal['geofences']['locations'][$geofence['location_id']]))
				{
					$proposal['geofences']['locations'][$geofence['location_id']][$geofence['type']] = array('geofence_type' => $geofence['type'], 'rows' => array());
				}
				$proposal['geofences']['locations'][$geofence['location_id']][$geofence['type']]['rows'][] = $geofence;
			}
			$proposal['has_geofences'] = true;
		}

		// Ensure that the options are indexed correctly
		foreach($options as &$option)
		{
			$proposal['options'][$option['id']] = $option;
			$this->get_term_language($proposal['options'][$option['id']]);
			$proposal['options'][$option['id']]['per_location_pre_discount_cost'] = 0;
			$proposal['options'][$option['id']]['per_location_post_discount_cost'] = 0;
			$proposal['options'][$option['id']]['per_location_average_post_discount_cost'] = 0;
			if($is_keywords_dependent)
			{
				$proposal['options'][$option['id']]['sem_clicks'] = 0;
			}
		}

		$proposal['discount_name'] = $proposal['options'][0]['monthly_discount_description'] ?: 'Discount';

		$proposal['no_discount'] = true;

		$proposal['political'] = $this->format_political_data($proposal['source_mpq']);

		$extra_demos = array_map(function($item){
			if ($item !== 'All')
			{
				return strtolower($item);
			}
		}, $proposal['political']['parties']);
		$proposal['unique_laps'] = array_unique(
			array_map(function($curr){
				return $curr['lap_id'];
			}, $proposal['products'])
		);
        $proposal['unique_laps_count'] = count($proposal['unique_laps']);
		$proposal['geos'] = $this->format_geo_data(
			$proposal['unique_laps'],
			$proposal_id,
			$extra_demos
		);

		foreach($proposal['geos'] as $key => &$geo)
		{
			if(array_key_exists($key, $proposal['geofences']['locations']))
			{
				$geo['geofences'] = $proposal['geofences']['locations'][$key];
			}
		}
		if($is_geo_dependent)
		{
			$proposal['region_overview'] = $this->create_region_overview($proposal['geos']);
		}
        $proposal['site_list_exists']  = false;
		if($is_geo_dependent && $is_audience_dependent)
		{
			$proposal['site_list'] = $this->format_site_list(json_decode($proposal['site_list'], true));
		    $proposal['site_list_exists'] = true;
		}

		$proposal['advertiser'] = $proposal['advertiser_name'];

		$proposal['rooftops_data'] = $this->mpq->get_rooftops_data($proposal['source_mpq']);
		$proposal['pricing'] = array();
		if($is_tv_zones_dependent)
		{
			$this->get_pricing_data($proposal, $proposal['tv_budget_spots']);
		}
		else
		{
			$this->get_pricing_data($proposal);
		}
		if ($proposal['has_scx_data']) //put this after becuase months get added in the `get_pricing_data` function  for tv_scx_data object
		{
			$proposal['tv_scx_data_encoded'] = json_encode($proposal['tv_scx_data']);
		}
		//this is for proposals that have the reach/frequency bar graph
		//they get the regular reach/frequency graph if there is only one option
		$proposal['is_single_rf_graph'] = false;
		if(count($proposal['options']) == 1)
		{
			$proposal['is_single_rf_graph'] = true;
		}
		else
		{
			$proposal['is_single_rf_graph'] = true;
			$first_option_term = count($proposal['options'][0]['display_packages']);
			$first_option_last_display_package = array_pop(array_slice($proposal['options'][0]['display_packages'], -1));

			foreach($proposal['options'] as $rf_option)
			{
				if($first_option_term !== count($rf_option['display_packages']))
				{
					$proposal['is_single_rf_graph'] = false;
				}
				else
				{
					$last_display_package = array_pop(array_slice($rf_option['display_packages'], -1));
					if($first_option_last_display_package['impressions'] !== $last_display_package['impressions'] or $first_option_last_display_package['reach'] !== $last_display_package['reach'] or $first_option_last_display_package['frequency'] !== $last_display_package['frequency'])
					{
						$proposal['is_single_rf_graph'] = false;
					}
				}
			}
		}

		if($is_keywords_dependent && count($proposal['keywords_array']) == 0)
		{
			$proposal['is_keywords_dependent'] = false;
		}

		$proposal['num_options_class'] = "table-three-options ";

		if(count($proposal['options']) == 2)
		{
			$proposal['num_options_class'] = "table-two-options ";
		}
		else if(count($proposal['options']) == 1)
		{
			$proposal['num_options_class'] = "table-one-option ";
		}

		$proposal['iab_categories'] = $this->get_iab_categories_array($proposal['unique_laps']);

		$proposal['pricing_options'] = array();

		foreach($proposal['pricing'] as $product_id => $t_pricing_product)
		{
			foreach($t_pricing_product['options'] as $option_id => $t_pricing_option)
			{
				if(array_key_exists('has_geofence', $t_pricing_option))
				{
					$proposal['options'][$option_id]['products'][$product_id]['has_geofence'] = $t_pricing_option['has_geofence'];
				}
				if(array_key_exists('total_geofencing_budget', $t_pricing_option))
				{
					$proposal['options'][$option_id]['products'][$product_id]['total_geofencing_budget'] = $t_pricing_option['total_geofencing_budget'];
				}
				if(array_key_exists('total_ip_budget', $t_pricing_option))
				{
					$proposal['options'][$option_id]['products'][$product_id]['total_ip_budget'] = $t_pricing_option['total_ip_budget'];
				}
			}
		}
		return $proposal;
	}

	public function get_proposal_data_for_template($proposal_id, $template_id = null)
	{
		$proposal = $this->get_proposal_by_id($proposal_id);

		$template = $this->get_template_by_template_id($template_id);

		$proposal['uses_partner_palette'] = $template['uses_partner_palette'];
		$proposal['single_location'] = true;// setting it here for proposals with no geo

		if ($proposal['is_geo_dependent'])
		{
			foreach($proposal['geos'] as &$geo)
			{
				$geo['zip_list'] = $this->format_zips_for_template($geo['zips'], $geo['name'], $template_id);
				if(!array_key_exists('county_list', $geo))
				{
					$geo['county_list'] = "";
					foreach($geo['counties'] as $county)
					{
						if($geo['county_list'] !== "")
						{
							$geo['county_list'] .= ", ";
						}
						$geo['county_list'] .= $county;
					}


					if(count($geo['counties'] > 1))
					{
						$geo['county_list'] .= " Counties";
					}
					else
					{
						$geo['county_list'] .= " County";
					}
				}
				$geo['province_list'] = "";
				if(array_key_exists('provinces', $geo))
				{
					foreach($geo['provinces'] as $province)
					{
						if($geo['province_list'] !== "")
						{
							$geo['province_list'] .= ", ";
						}
						$geo['province_list'] .= $county;
					}
				}

			}

			$proposal['single_location'] = false;

			if (count($proposal['geos']) == 1)
			{
				$proposal['geo_overview_title'] = $proposal['geos'][0]['geo_snapshot_title'];
				$proposal['geo_overview_link'] = $proposal['geos'][0]['geo_snapshot_link'];
				$proposal['zip_list'] = $this->format_zips_for_template($proposal['geos'][0]['zips'], $proposal['geos'][0]['name'], $template_id);

				$proposal['single_location'] = true;
			}

			$proposal['paged_geos'] = $this->format_locations_for_template($proposal['geos'], $template_id);
			$proposal['num_zips'] = count($proposal['region_overview']['zips']);
			$proposal['formatted_num_zips'] = number_format($proposal['num_zips']);

			/*
			 * Proposal Widget Stubs
			 */
			$proposal['widgets'] = array();
			$region_and_state_demographics = $this->get_demographics_data($proposal['region_overview']['zips']);

			// Ethnic Data bar chart
			$proposal['widgets']['ethnic_data'] = json_encode(array(
				array(
					"label" => "Caucasian",
					"value" => $region_and_state_demographics['demos']['Caucasian'][0],
					"average" => $region_and_state_demographics['demos']['Caucasian'][1]
				),
				array(
					"label" => "Hispanic",
					"value" => $region_and_state_demographics['demos']['Hispanic'][0],
					"average" => $region_and_state_demographics['demos']['Hispanic'][1]
				),
				array(
					"label" => "Afr. American",
					"value" => $region_and_state_demographics['demos']['Afr. American'][0],
					"average" => $region_and_state_demographics['demos']['Afr. American'][1]
				),
				array(
					"label" => "Asian",
					"value" => $region_and_state_demographics['demos']['Asian'][0],
					"average" => $region_and_state_demographics['demos']['Asian'][1]
				),
				array(
					"label" => "Other",
					"value" => $region_and_state_demographics['demos']['Other'][0],
					"average" => $region_and_state_demographics['demos']['Other'][1]
				)
			));

			$proposal['widgets']['states'] = json_encode($region_and_state_demographics['state_list']);

			// Demo Targets column chart

			$proposal['widgets']['selected_demos'] = $this->parse_demo_string_for_widgets($proposal['demo_string']);
			$proposal['widgets']['selected_demos_list'] = '';
			foreach($proposal['demographic_data'] as $title => $value){
				if ($value !== 'All')
				{
					if ($proposal['widgets']['selected_demos_list'] !== '')
					{
						$proposal['widgets']['selected_demos_list'] .= ', ';
					}
					$proposal['widgets']['selected_demos_list'] .= $title == 'Income' ? 'Income '. $value : $value;
				}
			};
			if (empty($proposal['widgets']['selected_demos_list']))
			{
				$proposal['widgets']['selected_demos_list'] = false;
			}

			$proposal['widgets']['demo_data'] = array(
				array(
					"label" => 'Education',
					"data" => array(
						array(
							"label" => 'No College',
							"value" => $region_and_state_demographics['demos']['No College'][0],
							"average" => $region_and_state_demographics['demos']['No College'][1],
							"selected" => false
						),
						array(
							"label" => 'Undergrad',
							"value" => $region_and_state_demographics['demos']['Undergrad'][0],
							"average" => $region_and_state_demographics['demos']['Undergrad'][1],
							"selected" => false
						),
						array(
							"label" => 'Grad School',
							"value" => $region_and_state_demographics['demos']['Grad School'][0],
							"average" => $region_and_state_demographics['demos']['Grad School'][1],
							"selected" => false
						)
					)
				),
				array(
					"label" => 'Parenting',
					"data" => array(
						array(
							"label" => 'Has Kids',
							"value" => $region_and_state_demographics['demos']['Has Kids'][0],
							"average" => $region_and_state_demographics['demos']['Has Kids'][1],
							"selected" => false
						),
						array(
							"label" => 'No Kids',
							"value" => $region_and_state_demographics['demos']['No Kids'][0],
							"average" => $region_and_state_demographics['demos']['No Kids'][1],
							"selected" => false
						)
					)
				),
				array(
					"label" => 'Income',
					"data" => array(
						array(
							"label" => '0-50k',
							"value" => $region_and_state_demographics['demos']['0-50k'][0],
							"average" => $region_and_state_demographics['demos']['0-50k'][1],
							"selected" => false
						),
						array(
							"label" => '50-100k',
							"value" => $region_and_state_demographics['demos']['50-100k'][0],
							"average" => $region_and_state_demographics['demos']['50-100k'][1],
							"selected" => false
						),
						array(
							"label" => '100-150k',
							"value" => $region_and_state_demographics['demos']['100-150k'][0],
							"average" => $region_and_state_demographics['demos']['100-150k'][1],
							"selected" => false
						),
						array(
							"label" => '150k+',
							"value" => $region_and_state_demographics['demos']['150k+'][0],
							"average" => $region_and_state_demographics['demos']['150k+'][1],
							"selected" => false
						)

					)
				),
				array(
					"label" => 'Gender',
					"data" => array(
						array(
							"label" => 'Male',
							"value" => $region_and_state_demographics['demos']['Male'][0],
							"average" => $region_and_state_demographics['demos']['Male'][1],
							"selected" => false
						),
						array(
							"label" => 'Female',
							"value" => $region_and_state_demographics['demos']['Female'][0],
							"average" => $region_and_state_demographics['demos']['Female'][1],
							"selected" => false
						)
					)
				),
				array(
					"label" => 'Age',
					"data" => array(
						array(
							"label" => 'Under 18',
							"value" => $region_and_state_demographics['demos']['Under 18'][0],
							"average" => $region_and_state_demographics['demos']['Under 18'][1],
							"selected" => false
						),
						array(
							"label" => '18-24',
							"value" => $region_and_state_demographics['demos']['18-24'][0],
							"average" => $region_and_state_demographics['demos']['18-24'][1],
							"selected" => false
						),
						array(
							"label" => '25-34',
							"value" => $region_and_state_demographics['demos']['25-34'][0],
							"average" => $region_and_state_demographics['demos']['25-34'][1],
							"selected" => false
						),
						array(
							"label" => '35-44',
							"value" => $region_and_state_demographics['demos']['35-44'][0],
							"average" => $region_and_state_demographics['demos']['35-44'][1],
							"selected" => false
						),
						array(
							"label" => '45-54',
							"value" => $region_and_state_demographics['demos']['45-54'][0],
							"average" => $region_and_state_demographics['demos']['45-54'][1],
							"selected" => false
						),
						array(
							"label" => '55-64',
							"value" => $region_and_state_demographics['demos']['55-64'][0],
							"average" => $region_and_state_demographics['demos']['55-64'][1],
							"selected" => false
						),
						array(
							"label" => '65+',
							"value" => $region_and_state_demographics['demos']['65+'][0],
							"average" => $region_and_state_demographics['demos']['65+'][1],
							"selected" => false
						)
					)
				)
			);

			foreach($proposal['widgets']['demo_data'] as &$demo_group)
			{
				foreach($demo_group['data'] as &$demo)
				{
					$demo['selected'] = $proposal['widgets']['selected_demos'][$demo_group['label']][$demo['label']];
				}
			}

			$proposal['widgets']['selected_demos'] = json_encode($proposal['widgets']['selected_demos']);
			$proposal['widgets']['demo_data'] = json_encode($proposal['widgets']['demo_data']);

			$proposal['widgets']['generations'] = json_encode($this->get_geo_generational_data($proposal['region_overview']['zips']), true);
			$proposal['widgets']['housing'] = json_encode($this->get_housing_tenure_data($proposal['region_overview']['zips']), true);
		}

		if (count($proposal['submitted_products']) == 1)
		{
			$proposal['single_product'] = true;
		}

		if ($proposal['is_audience_dependent'])
		{
			$proposal['iab_categories'] = $this->format_iab_categories_for_template($proposal['iab_categories'], $template_id);
		}

		if ($proposal['is_audience_dependent'] && $proposal['is_geo_dependent'])
		{
			$proposal['site_list'] = $this->format_site_list_for_template($proposal['site_list'], $template_id);
			// Audience Interest
			$headers = json_decode($proposal['site_list_widget'], true)['site_results'];
			if ($headers)
			{
				foreach($headers as $header => &$header_data)
				{

					$header_data['header_score'] =  (int) $header_data['header_score'];

					$header_data['industry_score'] = 0;
					$header_data['stereotypes_score'] = 0;
					$header_data['iab_score'] = 0;
					$header_data['reach_score'] = 0;
					$header_data['INDUSTRY_PARENT_N_SCORE'] = 0;
					$header_data['INDUSTRY_TOPLEVEL_N_SCORE'] = 0;
					$header_data['INDUSTRY_CHILD_N_SCORE'] = 0;

					$header_data['label'] = $header;
					$sites = array();

					foreach($header_data['sites'] as $sitename=>$site_data)
					{
						foreach($site_data as $raw_score_category => $category_data)
						{
							if($raw_score_category == "industries" || $raw_score_category == "iab_n_categories" || $raw_score_category == "iab_categories" || $raw_score_category == "stereotypes"){
								foreach($category_data as $tag)
								{
									switch ($raw_score_category) {
										case "industries":
											$header_data['industry_score'] += $tag['score'];
										break;
										case "iab_n_categories":
											$header_data['iab_score'] += $tag['score'];
										break;
										case "iab_categories":
											$header_data['iab_score'] += $tag['score'];
										break;
										case "stereotypes":
											$header_data['stereotypes_score'] += $tag['score'];
									}
								}
							}
							elseif ($raw_score_category == "INDUSTRY_PARENT_NEIGH" || $raw_score_category == "INDUSTRY_TOPLEVEL_NEIGH" || $raw_score_category == "INDUSTRY_CHILD_NEIGH" || $raw_score_category == "reach-score")
							{
									switch ($raw_score_category) {
										case "INDUSTRY_PARENT_NEIGH":
											$header_data['INDUSTRY_PARENT_N_SCORE'] += 2000;
										break;
										case "INDUSTRY_TOPLEVEL_NEIGH":
											$header_data['INDUSTRY_TOPLEVEL_N_SCORE'] += 1000;
										break;
										case "INDUSTRY_CHILD_NEIGH":
											$header_data['INDUSTRY_CHILD_N_SCORE'] += 5000;
										case "reach-score":
											$header_data['reach_score'] += $category_data;
										break;
									}
							}
						}

						$site_data['url'] = $sitename;
						$sites[] = $site_data;
					}

					$header_data['reach_score']++;

					$header_data['sites'] = $sites;

					$header_data['x_score'] = (100 * $header_data['iab_score']) + ($header_data['industry_score'] / 5) + $header_data['stereotypes_score'];
					$header_data['y_score'] = $header_data['header_score'] + $header_data['industry_score'] + $header_data['INDUSTRY_TOPLEVEL_N_SCORE'] + $header_data['INDUSTRY_CHILD_N_SCORE'] + $header_data['INDUSTRY_PARENT_N_SCORE'];

					$audience_data[] = $header_data;
				}

				// Calculate sigma
				$audience_data = array_map(function($header) use (&$audience_data) {

					$count = count($audience_data);

					$y_rank = ( ( $count + 1 ) - $this->rank_average($header, $audience_data, 'y_score') ) / ( $count + 1 );
					$y = $this->NormSInv($y_rank);

					$x_rank = ( ( $count + 1 ) - $this->rank_average($header, $audience_data, 'x_score') ) / ( $count + 1 );
					$x = $this->NormSInv($x_rank);

					// Calculate radius
					$reach_rank = ( ( $count + 1 ) - $this->rank_average($header, $audience_data, 'reach_score') ) / ( $count + 1 );
					$stereotypes_rank = ( ( $count + 1 ) - $this->rank_average($header, $audience_data, 'stereotypes_score') ) / ( $count + 1 );

					$r_percent = $reach_rank * $stereotypes_rank;

					return array(
						'label' => $header['label'],
						'x' => $x,
						'y' => $y,
						'r_percent' => $r_percent,
						'rank_value' => $x + $y + $r_percent,
						'sites' => $header['sites']
					);

				}, $audience_data);

				$audience_data = array_map(function($header) use (&$audience_data) {

					$count = count($audience_data);

					$r_rank = ( ( $count + 1 ) - $this->rank_average($header, $audience_data, 'r_percent') ) / ( $count + 1 );
					$header['r'] = $this->NormSInv($r_rank);

					unset($header['r_percent']);
					return $header;

				}, $audience_data);

				// Get a list of high-performing site URLs
				$header_count = count($audience_data);
				$num_top_websites = $template['num_top_websites'] ?: 21;
				$culled_site_count = floor($template['num_top_websites'] / $header_count);
				$culled_site_remainder = $template['num_top_websites'] % $header_count;
				$culled_sites = array();
				$sitelist = array();

				if($template['uses_partner_palette'])
				{

				}

				foreach ($audience_data as $i => &$header) {

					foreach ($header['sites'] as $site){
						$temp_site_array = array(
							'url' => $site['url'],
							'categories' => $this->get_site_categories($site)
						);
						if($template['uses_partner_palette'])
						{
							$temp_site_array['category_svgs'] = $this->get_site_category_svgs($temp_site_array['categories']);
						}
						$sitelist[] = $temp_site_array;
					}

					$sites = array_splice($header['sites'], 0, $culled_site_count);
					if (!empty($header['sites']) && $culled_site_remainder > 0) {
						array_push($sites, $header['sites'][0]);
						$culled_site_remainder--;
					}

					foreach ($sites as $site)
					{
						$temp_culled_site_array = array(
							'url' => $site['url'],
							'categories' => $this->get_site_categories($site)
						);
						if($template['uses_partner_palette'])
						{
							$temp_culled_site_array['category_svgs'] = $this->get_site_category_svgs($temp_culled_site_array['categories']);
						}
						$culled_sites[] = $temp_culled_site_array;
					}

					unset($header['sites']);
				}
			}
			else
			{
				$audience_data = null;
				$culled_sites = null;
				$sitelist = null;
			}
			$proposal['widgets']['audience_interest'] = json_encode(array_values($audience_data));
			$proposal['widgets']['culled_sites'] = $culled_sites;

			usort($sitelist, function($a,$b)
			{
				if (count($a['categories']) == count($b['categories']))
				{
					return 0;
				}

				return count($a['categories']) < count($b['categories']) ? 1 : -1;
			});
			$proposal['categorized_sitelist'] = $this->format_categorized_sitelist_for_template($sitelist, $template_id);
		}

		$proposal['proposal_date'] = date("F Y", strtotime($proposal['presentation_date']));
		$proposal['valid_date'] = date("F t, Y", strtotime("+ 1 month"));
		switch (count($proposal['options'])) {
			case 1:
				$proposal['one_option'] = true;
				break;
			case 2:
				$proposal['two_option'] = true;
				break;
			case 3:
				$proposal['three_option'] = true;
				break;
		}

		if($proposal['unique_display_id'] === null)
		{
			$proposal['unique_display_id'] = "None Available";
		}

		return $proposal;
	}

	private function get_site_categories(array $site)
	{
		$categories = array();

		if (
			isset($site['industries']) ||
			isset($site['INDUSTRY_CHILD_NEIGH']) ||
			isset($site['INDUSTRY_PARENT_NEIGH']) ||
			isset($site['INDUSTRY_TOPLEVEL_NEIGH'])
		) {
			$categories[] = 'industry';
		}
		if (
			isset($site['iab_categories']) ||
			isset($site['iab_n_categories'])
		) {
			$categories[] = 'contextual';
		}
		if (isset($site['stereotypes'])) {
			$categories[] = 'demographic';
		}

		return $categories;
	}

	private function rank_average($value, array $array, $index) {
		usort($array, function($a, $b) use ($index) {
			if ($a[$index] == $b[$index])
			{
				return 0;
			}

			return ($a[$index] > $b[$index]) ? -1 : 1;
		});

		return array_search($value, $array) + 1;
	}

	private function NormSInv($p){
	   $a1 = -39.6968302866538;
	   $a2 = 220.946098424521;
	   $a3 = -275.928510446969;
	   $a4 = 138.357751867269;
	   $a5 = -30.6647980661472;
	   $a6 = 2.50662827745924;
	   $b1 = -54.4760987982241;
	   $b2 = 161.585836858041;
	   $b3 = -155.698979859887;
	   $b4 = 66.8013118877197;
	   $b5 = -13.2806815528857;
	   $c1 = -7.78489400243029E-03;
	   $c2 = -0.322396458041136;
	   $c3 = -2.40075827716184;
	   $c4 = -2.54973253934373;
	   $c5 = 4.37466414146497;
	   $c6 = 2.93816398269878;
	   $d1 = 7.78469570904146E-03;
	   $d2 = 0.32246712907004;
	   $d3 = 2.445134137143;
	   $d4 = 3.75440866190742;
	   $p_low = 0.02425;
	   $p_high = 1 - $p_low;
	   $q = 0.0;
	   $r = 0.0;
	   if($p < 0 || $p > 1){
		  throw new Exception("NormSInv: Argument out of range.");
	   } else if($p < $p_low){
		  $q = pow(-2 * log($p), 2);
		  $NormSInv = ((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) /
			 (((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);
		} else if($p <= $p_high){
		  $q = $p - 0.5; $r = $q * $q;
		  $NormSInv = ((((($a1 * $r + $a2) * $r + $a3) * $r + $a4) * $r + $a5) * $r + $a6) * $q /
			 ((((($b1 * $r + $b2) * $r + $b3) * $r + $b4) * $r + $b5) * $r + 1);
		} else {
		  $q = pow(-2 * log(1 - $p), 2);
		  $NormSInv = -((((($c1 * $q + $c2) * $q + $c3) * $q + $c4) * $q + $c5) * $q + $c6) /
			 (((($d1 * $q + $d2) * $q + $d3) * $q + $d4) * $q + 1);
		}
		return $NormSInv;
	}

	private function get_demographics_data($zcta_array)
	{
		$result = $this->map->get_averages_of_regions_and_containing_states(array('zcta' => $zcta_array));
		if($result)
		{
			$return_array = array();
			$return_array['state_list'] = $result['states'];

			$demographics_key = array(
				'Caucasian' => array( 'key' => 'white_population', 'set' => 'normalized_race_population',),
				'Hispanic' => array( 'key' => 'hispanic_population', 'set' => 'normalized_race_population'),
				'Afr. American' => array( 'key' => 'black_population', 'set' => 'normalized_race_population'),
				'Asian' => array( 'key' => 'asian_population', 'set' => 'normalized_race_population'),
				'Other' => array( 'key' => 'other_race_population', 'set' => 'normalized_race_population'),
				'Male' => array( 'key' => 'male_population', 'set' => 'region_population'),
				'Female' => array( 'key' => 'female_population', 'set' => 'region_population'),
				'No College' => array( 'key' => 'college_no', 'set' => 'region_population'),
				'Undergrad' => array( 'key' => 'college_under', 'set' => 'region_population'),
				'Grad School' => array( 'key' => 'college_grad', 'set' => 'region_population'),
				'Has Kids' => array( 'key' => 'kids_yes', 'set' => 'total_households'),
				'No Kids' => array( 'key' => 'kids_no', 'set' => 'total_households'),
				'Under 18' => array( 'key' => 'age_under_18', 'set' => 'region_population'),
				'18-24' => array( 'key' => 'age_18_24', 'set' => 'region_population'),
				'25-34' => array( 'key' => 'age_25_34', 'set' => 'region_population'),
				'35-44' => array( 'key' => 'age_35_44', 'set' => 'region_population'),
				'45-54' => array( 'key' => 'age_45_54', 'set' => 'region_population'),
				'55-64' => array( 'key' => 'age_55_64', 'set' => 'region_population'),
				'65+' => array( 'key' => 'age_65_and_over', 'set' => 'region_population'),
				'0-50k' => array( 'key' => 'income_0_50', 'set' => 'total_households'),
				'50-100k' => array( 'key' => 'income_50_100', 'set' => 'total_households'),
				'100-150k' => array( 'key' => 'income_100_150', 'set' => 'total_households'),
				'150k+' => array( 'key' => 'income_150', 'set' => 'total_households'),
			);

			$final_set = array();

			foreach($demographics_key as $label => $parameters)
			{
				$final_set[$label] = array(
					100 * $result['region_demos'][$parameters['key']] / $result['region_demos'][$parameters['set']],
					100 * $result['state_demos'][$parameters['key']] / $result['state_demos'][$parameters['set']],
				);
			}
			$return_array['demos'] = $final_set;
			return $return_array;
		}
		return false;
	}

	private function format_geo_data($unique_laps, $proposal_id, $extra_demos = NULL)
	{

		$geos = $this->get_geo_data($unique_laps, $proposal_id);

		$geos = array_reduce($geos, function($prev, $geo) use ($extra_demos) {
			$geo['region_data'] = json_decode($geo['region_data'], true);
			$this->map->convert_old_flexigrid_format($geo['region_data']);
			$geo['name'] = $geo['region_data']['user_supplied_name'];
			$geo['zips'] = $geo['region_data']['ids']['zcta'];

			$geo['demographics'] = $this->get_region_demographics($geo['zips']);

			$geo['site_list'] = json_decode($geo['site_list']);
			$geo['site_list'] = array_reduce($geo['site_list'], function($prev, $site){
				$demos = array_splice($site, 2, 9);
				$demos = array_merge($demos, array_splice($site, 7, 6));
				$formatted_demos = array();
				foreach($demos as $i => $demo)
				{
					$formatted_demos[$i] = "".($demo*100)."%";
				}
				$prev[] = array(
					'url' => $site[0],
					'demos' => $demos,
					'formatted_demos' => $formatted_demos
				);
				return $prev;
			});
			$geo['site_list_string'] = "";
			foreach($geo['site_list'] as $site)
			{
				if($geo['site_list_string'] !== "")
				{
					$geo['site_list_string'] .= ", ";
				}
				$geo['site_list_string'] .= $site['url'];
			}
			// Want number of american zip codes
			$geo['num_zips'] = count(array_filter($geo['zips'], function($value){
				return is_numeric($value);
			}));
			$geo['num_fsas'] = count(array_filter($geo['zips'], function($value){
				return !is_numeric($value);
			}));

			if($geo['num_zips'])
			{
				$geo['counties'] = $this->map->get_list_of_distinct_counties_from_array_of_zips($geo['zips'], true);
				if (count($geo['counties']) > 50)
				{
					$state_and_county_names = $this->map->get_county_and_state_list_for_regions('zcta', $geo['zips']);
					$geo['county_list'] = count($geo['counties']) . ' Counties';
					if($state_and_county_names)
					{
						$geo['county_list'] .= " in {$state_and_county_names['state_list']}";
					}
				}
			}

			if($geo['num_fsas'])
			{
				$geo['provinces'] = array_values(array_unique($this->map->get_canadian_provinces_by_fsa($geo['zips'])));
			}

			$geo['population'] = 0;
			$geo['demo_population'] = 0;

			$this->map->calculate_population_based_on_selected_demographics(
				$geo['population'],
				$geo['demo_population'],
				$geo['internet_average'],
				$geo['region_data'],
				explode('_', $geo['demographic_data']),
				$extra_demos
			);

			$demo_percent = ($geo['demo_population'] / $geo['population']) * 100;
			$geo['demo_population_percent'] = $demo_percent == 100 ? $demo_percent : number_format($demo_percent, 1);
			$geo['formatted_population'] = number_format($geo['population']);
			$geo['formatted_demo_population'] = number_format($geo['demo_population']);

			$geo['has_retargeting'] = $geo['has_retargeting'] == '1' ? true : false;

			$geo['options'] = array();

			unset($geo['region_data'], $geo['plan_name']);

			$prev[] = $geo;

			return $prev;
		}, array());

		return $geos;
	}

	private function create_region_overview($geos)
	{
		if(count($geos) === 0)
		{
		}
		$region_overview = array_reduce($geos, function($prev, $geo){
			$prev['population'] += $geo['population'];
			$prev['demo_population'] += $geo['demo_population'];
			$prev['zips'] = array_merge($prev['zips'], $geo['zips']);
			return $prev;
		}, array('population' => 0, 'demo_population' => 0, 'zips' => array()));
		$demo_percent = ($region_overview['demo_population'] / $region_overview['population']) * 100;
		$region_overview['demo_population_percent'] = $demo_percent == 100 ? $demo_percent : number_format($demo_percent, 1);
		$region_overview['zips'] = array_unique($region_overview['zips']);

		// Again, only want to count US zips in this count
		$region_overview['num_zips'] = count(array_filter($region_overview['zips'], function($value){
			return is_numeric($value);
		}));
		$region_overview['num_fsas'] = count(array_filter($region_overview['zips'], function($value){
			return !is_numeric($value);
		}));

		$region_overview['demographics'] = $this->get_region_demographics($region_overview['zips']);

		if($region_overview['num_zips'])
		{
			$region_overview['counties'] = $this->map->get_list_of_distinct_counties_from_array_of_zips($region_overview['zips'], true);
			$region_overview['num_counties'] = count($region_overview['counties']);
			if (count($region_overview['counties']) > 50)
			{
				$state_and_county_names = $this->map->get_county_and_state_list_for_regions('zcta', $region_overview['zips']);
				$region_overview['county_list'] = count($region_overview['counties']) . ' Counties';
				if($state_and_county_names)
				{
					$region_overview['county_list'] .= " in {$state_and_county_names['state_list']}";
				}
			}
			else
			{
				$region_overview['county_list'] = "";
				$region_overview['county_list'] = "";
				foreach($region_overview['counties'] as $county)
				{
					if($region_overview['county_list'] !== "")
					{
						$region_overview['county_list'] .= ", ";
					}
					$region_overview['county_list'] .= $county;
				}


				if(count($region_overview['counties'] > 1))
				{
					$region_overview['county_list'] .= " Counties";
				}
				else
				{
					$geo['county_list'] .= " County";
				}
			}
		}

		if($region_overview['num_fsas'])
		{
			$region_overview['provinces'] = array_values(array_unique($this->map->get_canadian_provinces_by_fsa($region_overview['zips'])));
			$region_overview['num_provinces'] = count($region_overview['provinces']);
			$region_overview['province_list'] = "";
			if(array_key_exists('provinces', $region_overview))
			{
				foreach($region_overview['provinces'] as $province)
				{
					if($region_overview['province_list'] !== "")
					{
						$region_overview['province_list'] .= ", ";
					}
					$region_overview['province_list'] .= $county;
				}
			}

		}

		$region_overview['total_regions'] = $region_overview['num_zips'] + $region_overview['num_fsas'];
		if($region_overview['num_zips'] && $region_overview['num_fsas'])
		{
			$region_type_string = 'ZIPs/FSAs';
		}
		else
		{
			$region_type_string = ($region_overview['num_zips']) ? 'ZIP Code' : 'FSA';
			$region_type_string .= ($region_overview['num_zips'] > 1 || $region_overview['num_fsas'] > 1) ? 's' : '';
		}
		$region_overview['region_types'] = $region_type_string;
		$region_overview['region_types_uppercase'] = strtoupper($region_type_string);

		$region_overview['formatted_total_regions'] = number_format($region_overview['total_regions']);
		$region_overview['formatted_demo_population'] = number_format($region_overview['demo_population']);
		$region_overview['formatted_population'] = number_format($region_overview['population']);
		$region_overview['formatted_num_zips'] = number_format($region_overview['num_zips']);
		$region_overview['formatted_num_fsas'] = number_format($region_overview['num_fsas']);

		return $region_overview;
	}

	private function get_term_language(&$option)
	{
		$term_type_array = array(
			'monthly' => array(
				'singular' => 'Month',
				'plural' => 'Months',
				'adverb' => 'Monthly',
				'abbrev' => 'Mo'
			),
			'weekly' => array(
				'singular' => 'Week',
				'plural' => 'Weeks',
				'adverb' => 'Weekly',
				'abbrev' => 'Wk'
			),
			'daily' => array(
				'singular' => 'Day',
				'plural' => 'Days',
				'adverb' => 'Daily',
				'abbrev' => 'Day'
			),
		);

		$option['term_singular'] = $term_type_array[$option['term']]['singular'];
		$option['term_plural'] = $term_type_array[$option['term']]['plural'];
		$option['term_adverb'] = $term_type_array[$option['term']]['adverb'];
		$option['term_abbrev'] = $term_type_array[$option['term']]['abbrev'];
	}

	// TODO: break into separate functions
	private function get_pricing_data(&$proposal, $budget_spots = false)
	{
	    $has_only_visits = true;
		$has_visits = false;
		$keywords_index_counter = 0;
		foreach($proposal['products'] as $product)
		{
			$option = &$proposal['options'][$product['option_id']];
			$geo = &$proposal['geos'][array_search($product['lap_id'], array_column($proposal['geos'], 'lap_id'))];

			if (!isset($option['products']))
			{
				$option['products'] = array();
			}

			if (!isset($geo['products']))
			{
				$geo['products'] = array();
			}

			$product_data = json_decode($product['definition'], true);
			$product['type'] = $product['product_type'];
			$product['after_discount'] = $product_data['after_discount'];
			$product['selectable'] = $product['selectable'] === "1";
			$product = array_merge($product, json_decode($product['submitted_values'], true));
			$product['name'] = isset($product['custom_name']) ? $product['custom_name'] : trim($product_data['first_name']." ".$product_data['last_name']);
			$product['has_geofencing'] = false;

			$proposal['submitted_products'][$product['name']] = true;

			if ($product['type'] == 'cost_per_unit')
			{
				$has_only_visits = false;
				$budget_multiplier = 1000;
				if(array_key_exists('unit_multiplier', $product_data['options'][$product['option_id']]))
				{
					$budget_multiplier = $product_data['options'][$product['option_id']]['unit_multiplier'];
				}
				$product['impressions'] = $product['unit'];
				$product['budget'] = $product['impressions'] * $product['cpm'];
				$product['budget'] /= $budget_multiplier;

				if (array_key_exists('geofence_unit', $product))
				{
					$display_budget = ($product['impressions'] - $product['geofence_unit']) * $product['cpm'] / $budget_multiplier;
					$product['geofencing_budget'] = $product['geofence_unit'] * $product['geofence_cpm'] / 1000;
					$product['budget'] = $display_budget + $product['geofencing_budget'];
					$product['has_geofencing'] = true;
				}
				$product['budget'] = round($product['budget']);
			}
			else if ($product['type'] == 'cost_per_inventory_unit')
			{
				$has_only_visits = false;
				$product['impressions'] = $product['unit'];
				$product['budget'] = $product['impressions'] * $product['cpm'];
				$product['budget'] /= 1000;
				$product['budget'] += $product['inventory'];
				$product['budget'] = round($product['budget']);
			}
			else if ($product['type'] == 'cost_per_discrete_unit')
			{
				$has_visits = true;
				$product['visits'] = $product['unit'];
				$product['budget'] = $product['visits'] * $product['cpc'];
				$product['budget'] = round($product['budget']);
			}
			// TODO: this is not being passed correctly
			else if ($product['type'] == 'input_box')
			{
				$product['impressions'] = 0;
				$product['budget'] = $product['unit'];
				$product['budget'] = round($product['budget']);
			}
			else if ($product['type'] == 'cost_per_static_unit')
			{
				$product['impressions'] = 0;
				$product['budget'] = $product['price'] * $product['content'];
				$product['budget'] = round($product['budget']);
			}
			else if ($product['type'] == 'cost_per_tv_package')
			{
                $product['impressions'] = 0;
                if ($product['unit'] === "custom")
            	{
            		$product['budget'] = $product['price'];
            	}
                else
                {
	                foreach($proposal['tv_zone_pricing'] as $price){
	                	if($product['unit'] == $price['pack_name'])
	                    {
	                    	$product['budget'] = $price['price'];
	                    }
	                }
	            }
			}
			else if($product['type'] == 'cost_per_sem_unit')
			{
				$product['impressions'] = 0;
				$product['budget'] = $product['unit'];
				$product['budget'] = round($product['budget']);
			}
			else
			{
				$product['impressions'] = 0;
				$product['budget'] = isset($product['price']) ? $product['price'] : 0;
				$product['budget'] = round($product['budget']);
			}

			$product_content_description = "";

			// Multiply budget by number of rooftops
			if ($product['is_rooftops_dependent'])
			{
				$num_rooftops = count(json_decode($proposal['rooftops_data'], true));
				$proposal['num_rooftops'] = $num_rooftops;
				$product['budget'] *= $num_rooftops;
				$product_content_description = "".$num_rooftops." rooftop";
				if($num_rooftops > 1)
				{
					$product_content_description .= "s";
				}
			}
			else if($product['product_type'] == 'cost_per_static_unit')
			{
			    $product_content_description = "".$product['content']." brand";
				if($product['content'] > 1)
				{
					$product_content_description .= "s";
				}
			}
			else if($product['is_geo_dependent'])
			{
				$num_geos = count($proposal['geos']);

				if($num_geos > 1)
				{
					$product_content_description = "".$num_geos." locations";
				}
				else
				{
					$product_content_description = "".$num_geos." location";
				}
			}
            else if($product['is_zones_dependent'])
            {
                $num_zones = count($proposal['tv_zones_data']);
                if($num_zones > 1)
                {
                    $product_content_description = "".$num_zones." zones";
                }
                else
                {
                    $product_content_description = "".$num_zones." zone";
                }

            }
            else if ($product['product_type'] == 'tv_scx_upload')
            {
            	$num_zones = count($proposal['tv_scx_data']['geos']);
            	$product_content_description = $num_zones." zone";
            	$product_content_description .= $num_zones > 1 ? "s" : "";
            }

			$pricing_key = array_search($product['name'], array_column($proposal['pricing'], 'name'));
			if ($pricing_key === false)
			{
				$icon = isset($product_data['proposal_icon']) ? $product_data['proposal_icon'] : '';
				$custom_product_name = false;
				if(array_key_exists('custom_name', $product))
				{
					$custom_product_name = $product['custom_name'];
				}

				$proposal['pricing'][] = array(
					'name' => $product['name'],
					'custom_name' => $custom_product_name,
					'description' => $product_data['friendly_description'],
					'icon' => $icon,
					'type' => $product['type'],
					'options' => array(),
					'after_discount' => $product['after_discount'],
					'selectable' => $product['selectable'],
					'geo_dependent' => $product['is_geo_dependent'],
					'product_content_description' => $product_content_description,
					'zones_dependent' => $product['is_zones_dependent']
				);
				$pricing_key = array_search($product['name'], array_column($proposal['pricing'], 'name'));

			}

			$option_key = array_search($product['name'], array_column($option['products'], 'name'));
			if ($option_key === false)
			{
				$icon = isset($product_data['proposal_icon']) ? $product_data['proposal_icon'] : '';
				$option['products'][] = array(
					'name' => $product['name'],
					'description' => $product_data['friendly_description'],
					'icon' => $icon,
					'type' => $product['type'],
					'impressions' => 0,
					'budget' => 0,
					'after_discount' => $product['after_discount'],
					'selectable' => $product['selectable'],
					'geo_dependent' => $product['is_geo_dependent']
				);
				$option_key = array_search($product['name'], array_column($option['products'], 'name'));
			}

			$geo_key = array_search($product['name'], array_column($geo['products'], 'name'));
			if ($geo_key === false)
			{
				$icon = isset($product_data['proposal_icon']) ? $product_data['proposal_icon'] : '';
				$geo['products'][] = array(
					'name' => $product['name'],
					'custom_name' => $custom_product_name,
					'description' => $product_data['friendly_description'],
					'icon' => $icon,
					'type' => $product['type'],
					'options' => array(),
					'after_discount' => $product['after_discount'],
					'selectable' => $product['selectable'],
					'geo_dependent' => $product['is_geo_dependent']
				);
				$geo_key = array_search($product['name'], array_column($geo['products'], 'name'));
			}

			if (!isset($proposal['pricing'][$pricing_key]['options'][$product['option_id']]))
			{
				$proposal['pricing'][$pricing_key]['options'][$product['option_id']] = array(
					'budget' => 0,
					'geofencing_budget' => 0,
					'has_geofence' => false,
					'impressions' => 0,
					'visits' => 0,
					'description' => '',
					'term_singular' => $option['term_singular'],
					'term_plural' => $option['term_plural'],
					'term_adverb' => $option['term_adverb']
				);
			}

			if (!isset($geo['products'][$geo_key]['options'][$product['option_id']]))
			{
				$geo['products'][$geo_key]['options'][$product['option_id']] = array(
					'budget' => 0,
					'impressions' => 0,
					'visits' => 0,
					'description' => '',
					'term_singular' => $option['term_singular'],
					'term_plural' => $option['term_plural'],
					'term_adverb' => $option['term_adverb']
				);
			}

			if ($product['type'] == 'cost_per_unit' || $product['type'] == 'cost_per_inventory_unit')
			{
				$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['impressions'] += $product['impressions'];
				$option['products'][$option_key]['impressions'] += $product['impressions'];
				$geo['products'][$geo_key]['options'][$product['option_id']]['impressions'] += $product['impressions'];
			}

			if ($product['type'] == 'cost_per_discrete_unit')
			{
				$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['visits'] += $product['visits'];
				$geo['products'][$geo_key]['options'][$product['option_id']]['visits'] += $product['visits'];
			}
			if($product['type'] == 'cost_per_sem_unit')
			{
				if($product['cpm'] != "0" && $product['cpm'] !== "")
				{
					$existing_id = "";
					foreach($proposal['keywords_array'] as $keywords_id => $keywords)
					{
						if($keywords['sem_clicks'] == $product['cpm'])
						{
							$existing_id = $keywords['id'];
						}
					}
					if($existing_id !== "")
					{
						$proposal['keywords_array'][$existing_id]['names'][] = $proposal['options'][$product['option_id']]['name'];
					}
					else
					{
						$proposal['keywords_array'][$keywords_index_counter] = array(
							'id' => $keywords_index_counter,
							'sem_clicks' => (int)$product['cpm'],
							'names' => array($proposal['options'][$product['option_id']]['name'])
						);
						$keywords_index_counter++;
					}
				}

				$proposal['options'][$product['option_id']]['sem_clicks'] = $product['cpm'];

				$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['clicks'] = $product['cpm'];
			}

			if ($product['is_geo_dependent'])
			{
				$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['budget'] += $product['budget'];
				if ($product['has_geofencing'])
				{
					$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['geofencing_budget'] += $product['geofencing_budget'];
				}
				$option['products'][$option_key]['budget'] += $product['budget'];
				$geo['products'][$geo_key]['options'][$product['option_id']]['budget'] += $product['budget'];

				// If it is geo dependent, we want to add to the total budget for each geo
				if (!$product['after_discount'])
				{
					$option['pre_discount_cost'] += $product['budget'];
				}
				else
				{
					$option['post_discount_cost'] += $product['budget'];
				}
			}
			else
			{
				if ($proposal['pricing'][$pricing_key]['options'][$product['option_id']]['budget'] === 0)
				{
					$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['budget'] = $product['budget'];
					if ($product['has_geofencing'])
					{
						$proposal['pricing'][$pricing_key]['options'][$product['option_id']]['geofencing_budget'] = $product['geofencing_budget'];
					}
					$option['products'][$option_key]['budget'] = $product['budget'];

					// If it's not geo dependent, we only want to add to the option budget once
					if (!$product['after_discount'])
					{
						$option['pre_discount_cost'] += $product['budget'];
						$option['per_location_pre_discount_cost'] += $product['budget'];
					}
					else
					{
						$option['post_discount_cost'] += $product['budget'];
					}
				}
			}

			if (isset($product['lap_id']))
			{
				if (!isset($option['geos']))
				{
					$option['geos'] = array();
				}
				if (!isset($option['geos'][$product['lap_id']]))
				{
					$option['geos'][$product['lap_id']] = array('impressions' => 0);
				}

				if (isset($product['impressions']))
				{
					$option['geos'][$product['lap_id']]['impressions'] += $product['impressions'];
					$option['impressions'] += $product['impressions'];
				}
			}
		}

		// Calculate total budget
		foreach ($proposal['pricing'] as &$product)
		{
			foreach ($product['options'] as $i => &$option)
			{
				$num_geos = count($proposal['options'][$i]['geos']);

				$option['total_budget'] = $option['budget'];
				$option['formatted_total_budget'] = number_format($option['total_budget']);
				$option['formatted_budget'] = number_format($option['budget']);
				$option['total_duration_budget'] = $proposal['options'][$i]['duration'] * $option['budget'];
				$option['formatted_total_duration_budget'] = number_format($option['total_duration_budget']);
				$option['formatted_budget'] = number_format($option['budget']);
				$option['formatted_impressions'] = number_format($option['impressions']);
				if ($product['geo_dependent'])
				{
					if (array_key_exists('geofencing_budget', $option) && $option['geofencing_budget'] > 0)
					{
						$option['has_geofence'] = true;
						$option['total_geofencing_budget'] = $option['geofencing_budget'];
						$option['formatted_total_geofencing_budget'] = number_format(round($option['total_geofencing_budget']));
						$option['total_duration_geofencing_budget'] = $proposal['options'][$i]['duration'] * $option['geofencing_budget'];
						$option['formatted_total_duration_geofencing_budget'] = $option['total_duration_geofencing_budget'];
					}

					if ($product['after_discount'])
					{
						$proposal['options'][$i]['per_location_post_discount_cost'] += $option['budget'];
					}
					else
					{
						$proposal['options'][$i]['per_location_pre_discount_cost'] += $option['budget'];
					}
				    $proposal['options'][$i]['formatted_per_location_pre_discount_cost'] = number_format($proposal['options'][$i]['per_location_pre_discount_cost']);
					$proposal['options'][$i]['formatted_per_location_post_discount_cost'] = number_format($proposal['options'][$i]['per_location_post_discount_cost']);
				}

				if (array_key_exists('geofencing_budget', $option) && $option['geofencing_budget'] > 0)
				{
					$option['total_ip_budget'] = $option['total_budget'] - $option['total_geofencing_budget'];
					$option['formatted_total_ip_budget'] = number_format(round($option['total_ip_budget']));
					$option['total_duration_ip_budget'] = $option['total_duration_budget'] - $option['total_duration_geofencing_budget'];
					$option['formatted_total_duration_ip_budget'] = number_format($option['total_duration_ip_budget']);
					$option['ip_budget'] = $option['budget'] - $option['geofencing_budget'];
				}

				if ($product['type'] == 'cost_per_unit' || $product['type'] == 'cost_per_inventory_unit')
				{
					$option['description'] = number_format(round($option['impressions'])) . ' imprs/' . $proposal['options'][$i]['term_singular'];
					$option['duration_description'] = number_format(round($option['impressions'] * $proposal['options'][$i]['duration'])) . ' total imprs';
					$option['description_amount'] = number_format(round($option['impressions']));
					$option['description_suffix'] = ' imprs/' . $proposal['options'][$i]['term_singular'];
					$option['duration_description_amount'] = number_format(round($option['impressions'] * $proposal['options'][$i]['duration']));
					$option['duration_description_suffix'] = ' total imprs';
				}

				if ($product['type'] == 'cost_per_discrete_unit')
				{
					$option['description'] = number_format(round($option['visits'])) . ' visits/' . $proposal['options'][$i]['term_singular'];
					$option['duration_description'] = number_format(round($option['visits'] * $proposal['options'][$i]['duration'])) . ' total visits';
					$option['description_amount'] = number_format(round($option['visits']));
					$option['description_suffix'] = ' visits/' . $proposal['options'][$i]['term_singular'];
					$option['duration_description_amount'] = number_format(round($option['visits'] * $proposal['options'][$i]['duration']));
					$option['duration_description_suffix'] = ' total visits';
				}

				if($product['type'] == 'cost_per_tv_package')
				{
					foreach($budget_spots as $option_id => $spots)
					{
						if($i == $option_id)
						{
							$option['description_amount'] = number_format($spots);
							$option['description_suffix'] = ' spots/' . $proposal['options'][$i]['term_singular'];
						}
					}
				}
				if($product['type'] == 'tv_scx_upload')
				{
					$scx_timezone = new DateTimeZone('UTC');
					$scx_start = $proposal['tv_scx_data']['start_date'] . " 00:00:00";
					$scx_end = $proposal['tv_scx_data']['end_date'] . " 00:00:00";
					$scx_months = get_num_broadcast_months($scx_start, $scx_end);
					$proposal['tv_scx_data']['budget']['months'] = $scx_months;
					$option['average_budget'] = round($option['budget'] / $scx_months);
					$option['description'] = 'average per '.$proposal['options'][$i]['term_singular'];

					$proposal['options'][$i]['per_location_average_post_discount_cost'] += $option['average_budget'];

					$option['description_amount'] = number_format($proposal['tv_scx_data']['budget']['spots']);
					$option['description_suffix'] = ' spots';
				}
				if($product['type'] == 'cost_per_sem_unit')
				{
					$option['description_amount'] = number_format($option['clicks']);
					$option['description_suffix'] = ' clicks/' . $proposal['options'][$i]['term_singular'];
				}
			}
		}

		foreach ($proposal['options'] as &$option)
		{
			$option['campaign_options_impression_description'] = ($has_only_visits && $has_visits) ? ' visits/' : ' imprs/';

			foreach($option['products'] as &$product)
			{
				$product['total_budget'] = $product['budget'];
			}
		}

		if($proposal['is_geo_dependent'] == 1)
		{
			foreach ($proposal['geos'] as &$geo)
			{
				foreach ($geo['products'] as &$product)
				{
					foreach($product['options'] as $i => &$option)
					{
						if ($product['type'] == 'cost_per_unit' || $product['type'] == 'cost_per_inventory_unit')
						{
							$impressions = $option['impressions'];
							$option['description'] = number_format(round($impressions)) . ' imprs/' . $proposal['options'][$i]['term_singular'];
						}

						if ($product['type'] == 'cost_per_discrete_unit')
						{
							$visits = $option['visits'];
							$option['description'] = number_format(round($visits)) . ' visits/' . $proposal['options'][$i]['term_singular'];
						}
					}
				}
			}
		}

		// Calculate reach frequency and after-discount budget per option
		foreach($proposal['options'] as $option_id => &$option)
		{
			$option['name'] = $option['name'] ?: 'Option ' . ($option_id + 1);

			$option['total_cost'] = 0;
			$option['total_after_discount_cost'] = 0;

			$option['has_retargeting'] = false;
			$option['retargeting_price'] = 0;

			$option['display_packages'] = array();
			if($proposal['is_geo_dependent'] == 1)
			{
				foreach($option['geos'] as $lap_id => &$geo)
				{
					$region_key = array_search($lap_id, array_column($proposal['geos'], 'lap_id'));
					$region = &$proposal['geos'][$region_key];

					$demographic_shares = array(
						$region['demographics']['male_population']/$region['demographics']['region_population'],
						$region['demographics']['female_population']/$region['demographics']['region_population'],
						$region['demographics']['age_under_18']/$region['demographics']['region_population'],
						$region['demographics']['age_18_24']/$region['demographics']['region_population'],
						$region['demographics']['age_25_34']/$region['demographics']['region_population'],
						$region['demographics']['age_35_44']/$region['demographics']['region_population'],
						$region['demographics']['age_45_54']/$region['demographics']['region_population'],
						$region['demographics']['age_55_64']/$region['demographics']['region_population'],
						$region['demographics']['age_65_and_over']/$region['demographics']['region_population'],
						$region['demographics']['income_0_50']/$region['demographics']['total_households'],
						$region['demographics']['income_50_100']/$region['demographics']['total_households'],
						$region['demographics']['income_100_150']/$region['demographics']['total_households'],
						$region['demographics']['income_150']/$region['demographics']['total_households'],
						$region['demographics']['college_no']/$region['demographics']['region_population'],
						$region['demographics']['college_under']/$region['demographics']['region_population'],
						$region['demographics']['college_grad']/$region['demographics']['region_population'],
						$region['demographics']['kids_yes']/$region['demographics']['total_households'],
						$region['demographics']['kids_no']/$region['demographics']['total_households']
						);
					$demographic_array = explode('_', $region['demographic_data']);
					$geo_coverage = $this->rf->get_geo_coverage_percent($region['zips']);
					$demo_coverage = $this->rf->get_demo_coverage_percent($demographic_array, $demographic_shares, $geo_coverage);

					$rf_data = array();
					$term_array = array();
					for ($i = 1; $i <= $option['duration'];$i++)
					{
						$term_array[] = $geo['impressions'] * $i;
					}
					$region['term_array'] = $term_array;

					// Limits demo population to 100% of population. Necessary because of a rounding bug
					$region['demo_population'] = $region['demo_population'] > $region['population'] ? $region['population'] : $region['demo_population'];

					// TODO: replace with actual retargeting once it's populated.
					$rf_data = $this->rf->calculate_reach_and_frequency(
						$term_array,
						$region['population'],
						$region['demo_population'],
						$region['ip_accuracy'],
						$demo_coverage,
						$region['gamma'],
						$region['has_retargeting']
						);

					foreach($term_array as $month => $impressions)
					{
						if (!isset($option['display_packages'][$month]))
						{
							$option['display_packages'][$month] = array(
								'option_name' => $option['name'],
								'implied_audience_size' => 0,
								'reach_raw'			 => 0,
								'landed_impressions'	=> 0,
								'impressions'		   => 0
								);
						}

						$implied_audience_size = $rf_data[$month]['reach_percent'] > 0 ? $rf_data[$month]['reach'] / $rf_data[$month]['reach_percent'] : 0;
						$option['display_packages'][$month]['implied_audience_size'] += $implied_audience_size;
						$option['display_packages'][$month]['reach_raw'] += $rf_data[$month]["reach"];
						$option['display_packages'][$month]['landed_impressions'] += ($rf_data[$month]['reach'] * $rf_data[$month]['frequency']);
						$option['display_packages'][$month]['impressions'] += $impressions;
						$option['display_packages'][$month]['term_number'] = $month + 1;
					}
					foreach($rf_data as $i => &$term)
					{
						$term['term_number'] = $i + 1;
						$term['reach_percent'] = number_format($term['reach_percent'] * 100, 1);
						$term['frequency'] = number_format($term['frequency'], 1);
					}

					$region['options'][$option_id]['rf_data'] = $rf_data;
					$region['options'][$option_id]['name'] = $option['name'];
					$region['options'][$option_id]['duration'] = $option['duration'];
					$region['options'][$option_id]['term_plural'] = $option['term_plural'];
				}
			}

			foreach($option['products'] as &$product)
			{
				$product['formatted_budget'] = number_format($product['budget']);
				$product['formatted_impressions'] = number_format($product['impressions']);
			}

			foreach($option['display_packages'] as $i => &$rf_data)
			{
				$rf_data['reach_percent'] = $rf_data['implied_audience_size'] > 0 ? number_format($rf_data['reach_raw'] / $rf_data['implied_audience_size'] * 100, 1) : 0;
				$rf_data['reach'] = round($rf_data['reach_raw'], 0);
				$rf_data['frequency_raw'] = $rf_data['reach_raw'] > 0 ? $rf_data['landed_impressions'] / $rf_data['reach_raw'] : 0;
				$rf_data['frequency'] = $rf_data['reach_raw'] > 0 ? number_format($rf_data['landed_impressions'] / $rf_data['reach_raw'], 1) : 0;
			}
			$option['rf_last_term'] = end($option['display_packages']);
			unset($rf_data);

			if ($option['monthly_percent_discount'] > 0)
			{
				$proposal['no_discount'] = false;
			}
			$option['formatted_impressions'] = number_format($option['impressions']);
			$option['per_location_monthly_absolute_discount'] = round($option['per_location_pre_discount_cost'] * ($option['monthly_percent_discount'] / 100));
			$option['formatted_per_location_monthly_absolute_discount'] = number_format($option['per_location_monthly_absolute_discount']);
			$option['per_location_total_cost'] = ($option['per_location_pre_discount_cost'] - $option['per_location_monthly_absolute_discount']) + $option['per_location_post_discount_cost'];
			$option['formatted_per_location_total_cost'] = number_format($option['per_location_total_cost']);
			$option['monthly_absolute_discount'] = round($option['pre_discount_cost'] * ($option['monthly_percent_discount'] / 100));
			$option['formatted_monthly_absolute_discount'] = number_format($option['monthly_absolute_discount']);
			$option['total_absolute_discount'] = $option['monthly_absolute_discount'] * $proposal['options'][$option_id]['duration'];
			$option['formatted_total_absolute_discount'] = number_format($option['total_absolute_discount']);
			$option['total_monthly_absolute_discount'] = $option['monthly_absolute_discount'];
			$option['total_cost'] = $option['pre_discount_cost'] - $option['total_monthly_absolute_discount'];
			$option['formatted_total_cost'] = number_format($option['total_cost']);
			$option['total_average_cost'] = $option['total_cost'] + $option['per_location_average_post_discount_cost'];
			$option['formatted_total_average_cost'] = number_format($option['total_average_cost']);
			$option['grand_total'] = ($option['total_cost'] * $option['duration']) + $option['post_discount_cost'];
			$option['formatted_grand_total'] = number_format($option['grand_total']);
		}
	}

	private function format_site_list($site_list_data)
	{
		// Format the sitelist for template display.
		// We put all sitelist columns into a single page object for the time being.
		// At this point, there is no template associated with the proposal, so we have no way
		// of knowing how many columns per page.
		$site_list = array(
			'pages' => array(
				array(
					'columns' => array()
				)
			)
		);
		$current_column = 0;
		$current_header = -1;
		// Remove first site if it's a break tag, ya dingus.
		if ($site_list_data[0][0] == 'break_tag')
		{
			unset($site_list_data[0][0]);
		}
		foreach ($site_list_data as $site)
		{
			if ($site[0] == 'break_tag')
			{
				$current_column++;
				$current_header = -1;
				$site_list['pages'][0]['columns'][$current_column] = array(
					'site_groups' => array()
				);
			}
			else if ($site[0] == 'header_tag')
			{
				$current_header++;
				$site_list['pages'][0]['columns'][$current_column]['site_groups'][$current_header] = array(
					'header' => $site[1],
					'sites' => array()
				);
			}
			else
			{
				if ($current_header === -1)
				{
					$current_header++;
				}
				$demos = array_splice($site, 2, 9);
				$demos = array_merge($demos, array_splice($site, 7, 6));
				$formatted_demos = array();
				foreach($demos as $i => $demo)
				{
					$formatted_demos[$i] = "".($demo*100)."%";
				}
				$site_list['pages'][0]['columns'][$current_column]['site_groups'][$current_header]['sites'][] = array(
					'url' => $site[0],
					'demos' => $demos,
					'formatted_demos' => $formatted_demos
				);
			}
		}

		return $site_list;
	}

	private function format_categorized_sitelist_for_template($site_list, $template_id)
	{
		$template = $this->get_template_by_template_id($template_id);

		$site_list_new = array(
			'pages' => array(
				array(
					'columns' => array()
				)
			)
		);
		$current_page = 0;
		$current_column = 0;
		$site_count = 0;

		foreach($site_list as $site)
		{
			if ($site_count >= $template['sitelist_sites_per_column'])
			{
				$current_column++;
				$site_count = 0;
			}

			if ($current_column >= $template['sitelist_columns_per_page'])
			{
				$current_page++;
				$current_column = 0;
			}

			$site_list_new['pages'][$current_page]['columns'][$current_column]['sites'][] = $site;
			$site_count++;
		}

		return $site_list_new;
	}

	private function format_site_list_for_template($site_list, $template_id)
	{
		$template = $this->get_template_by_template_id($template_id);

		// Break sitelist into pages based on proposal_template.sitelist_columns_per_page
		$sitelist_columns_per_page = $template['sitelist_columns_per_page'] ?: count($site_list['pages'][0]['columns']);
		$site_list_new = array('pages' => array());
		while (count($site_list['pages'][0]['columns']) > 0)
		{
			$site_list_new['pages'][] = array(
				'columns' => array_splice($site_list['pages'][0]['columns'], 0, $sitelist_columns_per_page)
			);
		}
		return $site_list_new;
	}

	private function format_locations_for_template($locations, $template_id)
	{
		$template = $this->get_template_by_template_id($template_id);

		if (!empty($template['appendix_locations_per_page']))
		{
			$locations_new = array('pages' => array());
			$page_index = 0;
			$i = 0;

			if ($template['offset_locations_by_one'] == true || count($locations) === 1)
			{
				$locations_new['pages'][$page_index]['geos'][] = array_shift($locations);
				$locations_new['pages'][$page_index]['first_page'] = true;
				$page_index++;
			}

			foreach($locations as $location)
			{
				if ($i == $template['appendix_locations_per_page'])
				{
					$page_index++;
					$i = 0;
				}
				$temp_location = $location;
				$temp_location['formatted_num_zips'] = number_format($location['num_zips']);
				$temp_location['formatted_num_fsas'] = number_format($location['num_fsas']);
				$locations_new['pages'][$page_index]['geos'][] = $temp_location;
				$i++;
			}
			return $locations_new;
		}
		else
		{
			foreach($locations as &$paged_geo)
			{
				$paged_geo['formatted_num_zips'] = number_format($paged_geo['num_zips']);
				$paged_geo['formatted_num_fsas'] = number_format($paged_geo['num_fsas']);
			}
		}
		return $locations;
	}

	private function get_iab_categories_array($iab_categories = array())
	{
		// Get IAB categories
		$iab_query_string = "";
		foreach($iab_categories as $iab_lap)
		{
			if ($iab_query_string == "")
			{
				$iab_query_string = "?";
			}
			else
			{
				$iab_query_string .= ",?";
			}
		}
		$iab_sql =
		"   SELECT DISTINCT
				iab.id,
				iab.tag_copy
			FROM iab_categories AS iab
			JOIN prop_gen_sessions_iab_categories_join AS pgiab
				ON iab.id = pgiab.iab_category_id
			WHERE
				pgiab.prop_gen_lap_id IN (". $iab_query_string .")
		";
		$iab_query = $this->db->query($iab_sql, $iab_categories);

		// Convert categories to array,
		// merge duplicate entries
		if ($iab_query->num_rows() > 0)
		{
			$iab = $iab_query->result_array();
			$organized_iab_categories = array();

			$iab_categories = array_reduce($iab, function($prev, $cat){
				$split = explode(">", $cat['tag_copy']);
				$header = trim($split[0]);
				unset($split[0]);
				$key = empty($prev) ? false : array_search($header, array_column($prev, 'name'));
				if ($key === false)
				{
					$prev[] = array('name' => $header, 'subheaders' => array());
					$key = array_search($header, array_column($prev, 'name'));
				}
				foreach($split as $i => $part)
				{
					if (array_search(trim($part), array_column($prev[$key]['subheaders'], 'name')) === false)
					{
						$prev[$key]['subheaders'][] = array('name' => trim($part));
					}
				}
				return $prev;
			}, array());
			return $iab_categories;
		}
		else
		{
			return array();
		}

	}

	public function get_iab_categories_array_for_mpq_summary($mpq_id)
	{
		$iab_categories=array();
		$iab_sql =
		"
			SELECT DISTINCT
				iab.id,
				iab.tag_copy
			FROM iab_categories AS iab
			JOIN mpq_iab_categories_join AS pgiab
				ON iab.id = pgiab.iab_category_id
			WHERE
				pgiab.mpq_id = ?
		";
		$iab_query = $this->db->query($iab_sql, $mpq_id);

		// Convert categories to array,
		// merge duplicate entries
		if ($iab_query->num_rows() > 0)
		{
			$iab = $iab_query->result_array();
			$organized_iab_categories = array();

			$iab_categories = array_reduce($iab, function($prev, $cat){
				$split = explode(">", $cat['tag_copy']);
				$header = trim($split[0]);
				unset($split[0]);
				$key = empty($prev) ? false : array_search($header, array_column($prev, 'name'));
				if ($key === false)
				{
					$prev[] = array('name' => $header, 'subheaders' => array());
					$key = array_search($header, array_column($prev, 'name'));
				}
				foreach($split as $i => $part)
				{
					if (array_search(trim($part), array_column($prev[$key]['subheaders'], 'name')) === false)
					{
						$prev[$key]['subheaders'][] = array('name' => trim($part));
					}
				}
				return $prev;
			}, array());
			return $iab_categories;
		}
		else
		{
			return array();
		}

	}

	private function format_iab_categories_for_template($iab_categories, $template_id)
	{
		$template = $this->get_template_by_template_id($template_id);

		$contextual = array('columns' => array(array('sites' => array())));
		if ($template['contextual_tags_per_column'] && $template['contextual_columns_per_page'])
		{
			$tags = 0;
			$columns = 0;

			foreach($iab_categories as $i => $cat)
			{
				$tags += 2;
				if ($tags >= $template['contextual_tags_per_column'])
				{
					$tags = 2;
					$columns++;
					$contextual['columns'][$columns] = array('sites' => array());
				}
				if ($columns >= $template['contextual_columns_per_page'])
				{
					break;
				}
				$contextual['columns'][$columns]['sites'][] = array(
					'name' => $cat['name'],
					'header' => true
				);

				foreach ($cat['subheaders'] as $subheader)
				{
					$tags++;
					if ($tags >= $template['contextual_tags_per_column'])
					{
						$tags = 1;
						$columns++;
						$contextual['columns'][$columns] = array();
					}
					if ($columns >= $template['contextual_columns_per_page'])
					{
						break;
					}
					$contextual['columns'][$columns]['sites'][] = array(
						'name' => $subheader['name'],
						'header' => false
					);
				}
			}
			$iab_categories = $contextual;
		}

		return $iab_categories;
	}

	private function format_zips_for_template($zips, $region_name, $template_id)
	{
		$template = $this->get_template_by_template_id($template_id);
		$zips_per_page = $template['appendix_zips_per_page'] ?: 1000;

		$zips_array = array(
			'pages' => array(
			)
		);
		$zip_count = 0;
		$page_count = 0;

	   $zips_array['pages'][$page_count] = array(
			'header' => $region_name,
			'zips' => array()
		);

		foreach ($zips as $zip)
		{
			if ($zip_count > $zips_per_page)
			{
				$page_count++;
				$zip_count = 0;
				$zips_array['pages'][$page_count] = array(
					'header' => $region_name . ' Cont.',
					'zips' => array()
				);
			}

			$zips_array['pages'][$page_count]['zips'][] = $zip;
			$zip_count++;
		}

		return $zips_array;
	}

	private function get_region_demographics($region_array)
	{
		$region_data = $this->map->get_demographics_from_region_array(array('zcta' => $region_array));

		$region_data['region_population_formatted'] = number_format($region_data['region_population']);
		$region_data['income'] = number_format($region_data['household_income']);
		$region_data['MedianAge'] = number_format($region_data['median_age']);
		$region_data['HouseholdPersons'] = number_format($region_data['persons_household'], 1);
		$region_data['HouseValue'] = number_format($region_data['average_home_value'], 0);
		$region_data['BusinessCount'] = number_format($region_data['num_establishments']);

		return $region_data;
	}

	protected $demos = array(
			'Gender' => array(
				'Male' => array(),
				'Female' => array()
			),
			'Age' => array(
				'Under 18' => array(
					'lower' => 'Under 18'
				),
				'18-24' => array(
					'lower' => '18',
					'upper' => '24'
				),
				'25-34' => array(
					'lower' => '25',
					'upper' => '34'
				),
				'35-44' => array(
					'lower' => '35',
					'upper' => '44'
				),
				'45-54' => array(
					'lower' => '45',
					'upper' => '54'
				),
				'55-64' => array(
					'lower' => '55',
					'upper' => '64'
				),
				'65+' => array(
					'upper' => '65+'
				)
			),
			'Income' => array(
				'0-50k' => array(
					'lower' => '0',
				),
				'50-100k' => array(
					'lower' => '50',
					'upper' => '100k',
					'appender' => 'k'
				),
				'100-150k' => array(
					'lower' => '100',
					'upper' => '150k',
					'appender' => 'k'
				),
				'150k+' => array(
					'upper' => '150k+'
				)
			),
			'Education' => array(
				'No College' => array(),
				'Undergrad' => array(),
				'Grad School' => array()
			),
			'Parenting' => array(
				'No Kids' => array(),
				'Has Kids' => array()
			)
		);

	public function parse_demo_string($demo_string)
	{
		$demo_bools = explode("_", $demo_string);
		$demos = $this->demos;

		$i = 0;

		foreach($demos as $group_key => &$demo_group)
		{
			$demo_all_switch = true;
			$activated_demos = array();
			$demo_value_range = array();

			foreach($demo_group as $demo_key => &$demo_value_group)
			{
				$demo = (bool) $demo_bools[$i];
				$demo_prev = isset($demo_prev) ? $demo_prev : $demo;
				$demo_key_for_range = (isset($demo_value_group['lower']) || isset($demo_value_group['upper'])) ? true : false;

				if ($demo != $demo_prev)
				{
					$demo_all_switch = false;
				}

				if ($demo_key_for_range)
				{
					if ($demo)
					{
						$demo_value_range[] = $demo_key;
					}
					else
					{
						$this->process_demo_range($activated_demos,$demo_value_range,$demo_group);
					}
				}
				elseif ($demo)
				{
					$activated_demos[] = $demo_key;
				}

				unset($demo_key_for_range);
				$demo_prev = $demo;
				$i++;
			}

			unset($demo_prev);

			if ($demo_all_switch)
			{
				$demo_group = 'All';
			}
			else
			{
				if (count($demo_value_range) > 0)
				{
					$this->process_demo_range($activated_demos,$demo_value_range,$demo_group);
				}

				$demo_group = implode(", ", $activated_demos);
			}
		}

		return $demos;
	}

	private function process_demo_range(&$activated_demos,&$demo_value_range,$demo_group)
	{
		if (count($demo_value_range) == 1)
		{
			$activated_demos[] = $demo_value_range[0];
		}
		else if (count($demo_value_range) > 1)
		{
			$first_elem_in_range = $demo_value_range[0];
			$first_elem_lower_val = $demo_group[$first_elem_in_range]['lower'];
			$first_elem_appender = isset($demo_group[$first_elem_in_range]['appender']) ? $demo_group[$first_elem_in_range]['appender'] : '';
			$last_elem_in_range = $demo_value_range[count($demo_value_range)-1];
			$last_elem_upper_val = $demo_group[$last_elem_in_range]['upper'];

			if (strpos($last_elem_upper_val,'+') !== false)
			{
				$activated_demos[] = $first_elem_lower_val.$first_elem_appender.'+';
			}
			else
			{
				$activated_demos[] = $first_elem_lower_val.'-'.$last_elem_upper_val;
			}
		}

		$demo_value_range = array();
	}

	private function parse_demo_string_for_widgets($demo_string)
	{
		$demo_bools = explode("_", $demo_string);
		$demos = $this->demos;
		$i = 0;

		foreach($demos as $group_key => &$demo_group)
		{
			$activated_demos = array();
			$demo_value_range = array();

            foreach($demo_group as $demo_key => &$demo_value_group)
            {
                $demo_value_group = (bool) $demo_bools[$i];
                $i++;
            }
        }
        return $demos;
    }

    public function format_political_data($mpq_id)
    {
        $rfp_questions = $this->get_rfp_question_data($mpq_id);
        $political = array( 'parties' => array(), 'segments' => array() );
        if (array_key_exists('Voter Registration', $rfp_questions))
        {
            $all_parties = true;
            foreach($rfp_questions['Voter Registration'] as $segment => $value)
            {
                if ($value)
                {
                    $political['parties'][] = $segment;
                }
                else
                {
                    $all_parties = false;
                }
            }

            if ($all_parties === true || empty($political['parties'])) $political['parties'] = array('All');

            $political['party_switch'] = (
                count($political['parties']) == 1 &&
                $political['parties'][0] !== 'Unregistered' &&
                $political['parties'][0] !== 'All'
            ) ? $political['parties'][0] : false;
        }

        if (array_key_exists('Voting Likelihood', $rfp_questions))
        {
            foreach($rfp_questions['Voting Likelihood'] as $segment => $value)
            {
                if ($value)
                {
                    $political['segments'][] = $segment . ' Propensity Voters';
                }
            }
        }
		$political['parties_exists'] = false;
		if(count($political['parties']) > 0)
		{
			$political['parties_exists'] = true;
		}
		$political['parties_list'] = implode(", ", $political['parties']);

        return $political;
    }

    private function get_proposal_data($proposal_id)
    {
        $prop_sql =
        "   SELECT
                pgpd.show_pricing,
                pgpd.site_list,
                pgpd.source_mpq,
                pgpd.rep_id,
                pgpd.site_list_widget,
                pgpd.rooftops_snapshot,
                usr.firstname,
                usr.lastname,
                usr.address_1,
                usr.address_2,
                usr.city,
                usr.state,
                usr.zip,
                mss.proposal_name AS name,
                mss.submitter_name,
                mss.submitter_email,
				mss.industry_id,
				mss.advertiser_name,
				mss.demographic_data,
				mss.unique_display_id,
				mss.advertiser_website,
				mss.presentation_date,
				usr.phone_number,
				usr.fax_number,
				wlpd.partner_name,
				wlpd.home_url,
				wlpd.pretty_partner_name,
				wlpd.proposal_logo_filepath,
				wlpd.header_img,
				wlpd.partner_report_logo_filepath,
				pgsd.snapshot_title AS geo_overview_title,
				pgsd.snapshot_data AS geo_overview_link,
				wlpd.id AS partner_id
			FROM
				prop_gen_prop_data AS pgpd
				LEFT JOIN users AS usr
					ON usr.id = pgpd.rep_id
				LEFT JOIN wl_partner_details AS wlpd
					ON usr.partner_id = wlpd.id
				LEFT JOIN prop_gen_snapshot_data AS pgsd
					ON pgsd.prop_id = pgpd.prop_id
					AND pgsd.lap_id IS NULL
				LEFT JOIN mpq_sessions_and_submissions AS mss
					ON mss.id = pgpd.source_mpq
			WHERE pgpd.prop_id = ?
		";
		$prop_query = $this->db->query($prop_sql, array($proposal_id));

		if ($prop_query->num_rows() < 1)
		{
			throw new Exception('No proposal found with that ID', 404);
		}

		return $prop_query->row_array();
	}

	private function get_option_data($proposal_id)
	{
		$option_sql =
		"   SELECT
			option_id as id,
			option_name as name,
			monthly_percent_discount,
			monthly_discount_description,
			cost_by_campaign,
			term,
			duration,
			monthly_discount_description AS discount_name,
			0 AS impressions,
			0 AS pre_discount_cost,
			0 AS post_discount_cost,
			0 AS total_cost
			FROM
				prop_gen_option_prop_join
			WHERE
				prop_id = ?
			ORDER BY
				option_id ASC
		";
		$option_query = $this->db->query($option_sql, array($proposal_id));

		if ($option_query->num_rows() < 1)
		{
			throw new Exception('No options found for proposal '. $proposal_id .'', 500);
		}

		return $option_query->result_array();
	}

	private function get_product_data($proposal_id)
	{
		$product_sql =
		"   SELECT
				cpsp.frq_prop_gen_lap_id AS lap_id,
				cpsp.*,
				cpp.*
			FROM cp_submitted_products AS cpsp
				LEFT JOIN cp_products AS cpp
					ON cpp.id = cpsp.product_id
			WHERE
				cpsp.proposal_id = ?
			ORDER BY
				cpp.display_order ASC
		";
		$product_query = $this->db->query($product_sql, array($proposal_id, $proposal_id));
		return $product_query->result_array();
	}

	private function get_geo_data(array $lap_ids, $proposal_id)
	{
		$query_string = "";
		foreach($lap_ids as $id)
		{
			if ($query_string !== "")
			{
				$query_string .= ",";
			}
			$query_string .= $id;
		}
		$geo_sql =
		"   SELECT
				pgs.lap_id,
				pgs.plan_name AS plan_name,
				pgs.advertiser AS advertiser,
				pgs.region_data AS region_data,
				pgs.population AS population,
				pgs.demo_population AS demo_population,
				pgs.demographic_data AS demographic_data,
				pgs.site_array AS site_list,
				pgs.selected_iab_categories AS selected_iab_categories,
				pgs.has_retargeting,
				pgs.rf_gamma AS gamma,
				pgs.rf_ip_accuracy AS ip_accuracy,
				pgs.rf_demo_coverage AS demo_coverage,
				pgs.rf_geo_coverage AS geo_coverage,
				pgs.internet_average,
				pgsd.snapshot_title AS geo_snapshot_title,
				pgsd.snapshot_data AS geo_snapshot_link
			FROM prop_gen_sessions AS pgs
				LEFT JOIN prop_gen_snapshot_data as pgsd
					ON pgs.lap_id = pgsd.lap_id
					AND pgsd.prop_id = ?
			WHERE
				pgs.lap_id IN (".$query_string.")
			ORDER BY
				pgs.lap_id ASC
		";
		$lap_ids[0] = $proposal_id;
		$geo_query = $this->db->query($geo_sql, $lap_ids);
		return $geo_query->result_array();
	}

	private function get_geo_generational_data(array $zips)
	{
		$insert_string = implode(',', array_fill(0, count($zips), '?'));
		$sql =
		"	SELECT
				SUM(population_age_0_4) AS `0-4`,
				SUM(population_age_5_9) AS `5-9`,
				SUM(population_age_10_14) AS `10-14`,
				SUM(population_age_15_17) AS `15-17`,
				SUM(population_age_18_19) AS `18-19`,
				SUM(ROUND(population_age_20_24 / 5)) AS `20`,
				SUM(ROUND(population_age_20_24 / 5)) AS `21`,
				SUM(ROUND((population_age_20_24 / 5) * 3)) AS `22-24`,
				SUM(population_age_25_29) AS `25-29`,
				SUM(population_age_30_34) AS `30-34`,
				SUM(population_age_35_39) AS `35-39`,
				SUM(population_age_40_44) AS `40-44`,
				SUM(population_age_45_49) AS `45-49`,
				SUM(population_age_50_54) AS `50-54`,
				SUM(population_age_55_59) AS `55-59`,
				SUM(ROUND((population_age_60_64 / 5) * 2)) AS `60-61`,
				SUM(ROUND((population_age_60_64 / 5) * 3)) AS `62-64`,
				SUM(ROUND((population_age_65_69 / 5) * 2)) AS `65-66`,
				SUM(ROUND((population_age_65_69 / 5) * 3)) AS `67-69`,
				SUM(population_age_70_74) AS `70-74`,
				SUM(population_age_75_79) AS `75-79`,
				SUM(population_age_80_84) AS `80-84`,
				SUM(population_age_85_and_over) AS `85-95`
			FROM
				geo_cumulative_demographics
			WHERE
				local_id IN ({$insert_string})
		";

		$query = $this->db->query($sql, $zips);
		$age_data = $query->result_array();

		$raw_ages = array();
		$data_year = 2012;

		foreach($age_data[0] as $range => $value)
		{
			$range = explode('-',$range);
			if (count($range) > 1)
			{
				$range[0] = intval($range[0]);
				$range[1] = intval($range[1]);
				$range_size = ($range[1] + 1) - $range[0];

				for($i = $range[0]; $i <= $range[1]; $i++)
				{
					$year = $data_year - $i;
					$raw_ages[] = array(
						'year' => $year,
						'value' => sqrt($value / $range_size)
					);
				}
			}
			else
			{
				$range[0] = intval($range[0]);
				$year = $data_year - $range[0];
				$raw_ages[] = array(
						'year' => $year,
						'value' => sqrt($value)
					);
			}
		}

		$filter_coeffs = array(0.5,1.5,3,1.5,0.5);
		$filter_coeffs_sum = array_reduce($filter_coeffs, function($prev,$curr){
			return $prev + $curr;
		}, 0);

		$return_array = array();

		foreach($raw_ages as $i => $year)
		{
			if ($i < 2 || $i >= count($raw_ages) - 2)
			{
				$return_array[] = $year;
			}
			else
			{
				$smoothing_value = 0;
				$curr_array = array_slice($raw_ages, $i - 2, 5);
				for ($x = 0; $x < 5; $x++)
				{
					$smoothing_value += $curr_array[$x]['value'] * $filter_coeffs[$x];
				}
				$return_array[] = array(
					'year' => $year['year'],
					'value' => $smoothing_value / $filter_coeffs_sum
				);
			}
		}

		return $return_array;
	}

	public function get_housing_tenure_data($zips)
	{
		$insert_string = implode(',', array_fill(0, count($zips), '?'));
		$sql =
		"	SELECT
				households_total AS total,
				households_occupied_by_renter AS rent,
				households_occupied_by_owner AS own
			FROM
				geo_cumulative_demographics
			WHERE
				local_id IN ({$insert_string})
		";
		$query = $this->db->query($sql, $zips);
		$total = array_reduce(
			$query->result_array(),
			function($prev, $curr)
			{
				$prev['rent'] += $curr['rent'];
				$prev['own'] += $curr['own'];
				$prev['total'] += $curr['total'];
				return $prev;
			},
			array( 'rent' => 0, 'own' => 0, 'total' => 0 )
		);

		return array(
			'rent' => $total['rent'] / $total['total'],
			'own' => $total['own'] / $total['total']
		);
	}

    public function get_rfp_question_data($mpq_id)
    {
        $sql =
        '   SELECT
                questions.name,
                questions.question_group,
                answers.answer_value
            FROM cp_rfp_questions_join_mpq AS answers
            JOIN cp_rfp_questions AS questions
                ON questions.id = answers.question_id
            WHERE answers.mpq_id = ?
        ';

        $query = $this->db->query($sql, array($mpq_id));
        return array_reduce($query->result_array(), function($carry, $item){
            if (array_key_exists($item['question_group'], $carry))
            {
                $carry[$item['question_group']][$item['name']] = (bool) $item['answer_value'];
            }
            else
            {
                $carry[$item['question_group']] = array( $item['name'] => (bool) $item['answer_value'] );
            }

            return $carry;
        }, []);
    }

    public function get_rfp_components($strategy_id)
    {
    	$sql =
    	'	SELECT
    			components.*,
    			pjc.cp_products_id as product_id
    		FROM cp_strategies_join_cp_products AS sjp
    		JOIN cp_products_join_cp_components AS pjc
    			ON pjc.cp_products_id = sjp.cp_products_id
    		JOIN cp_components AS components
    			ON components.id = pjc.cp_components_id
    		WHERE sjp.cp_strategies_id = ?
    	';

    	$query = $this->db->query($sql, $strategy_id);
    	$results = $query->result_array();
    	$components = [];
    	foreach($results as $component)
    	{
    		$index = array_search($component['component_name'], array_column($components, 'component_name'));
    		if ($index === false)
    		{
    			$components[] = array(
    				'component_name' => $component['component_name'],
    				'products' => array($component['product_id'])
    			);
    		}
    		else {
    			$components[$index]['products'][] = $component['product_id'];
    		}
    	}
    	return $components;
    }

    public function get_templates()
    {
        $sql = 'SELECT * FROM proposal_templates';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function get_template_by_proposal_id($proposal_id)
    {
        $sql =
        '   SELECT
                prh.html_blob AS html,
                pt.is_landscape
            FROM proposal_rendered_html AS prh
            JOIN proposal_templates AS pt
                ON pt.id = prh.base_template_id
            WHERE prh.proposal_id = ?
        ';
        $result = $this->db->query($sql, array($proposal_id));
        if ($result->num_rows() > 0)
        {
            $template = $result->row_array();
            $template['html'] = gzuncompress($template['html']);
            return $template;
        }
        return false;
    }

	public function get_template_by_template_id($template_id)
	{
		$sql = '
			SELECT *
			FROM proposal_templates
			WHERE id = ?
		';
		$result = $this->db->query($sql, array($template_id));
		return $result->row_array();
	}

    public function get_default_template($partner_id, $proposal_id)
    {
        $template_field = $this->is_political_proposal($proposal_id) ? 'political_proposal_template' : 'default_proposal_template' ;
        $sql = '
            SELECT pt.*
            FROM proposal_templates as pt
            JOIN wl_partner_details as wpd
                ON wpd.'.$template_field.' = pt.id
            WHERE wpd.id = ?
        ';
        $result = $this->db->query($sql, array($partner_id));
        return $result->row_array();
    }

    public function is_political_proposal($proposal_id)
    {
        $sql =
        '   SELECT SUM(cpp.is_political) > 0 AS sum
            FROM cp_products cpp
            JOIN cp_submitted_products cpsp
                ON cpsp.product_id = cpp.id
            WHERE cpsp.proposal_id = ?;
        ';
        $query = $this->db->query($sql, array($proposal_id));
        return (bool) $query->row('sum');
    }

    public function save_proposal_html($prop_id, $html, $template_id)
    {
        $html = gzcompress($html);

		$sql = '
			INSERT INTO proposal_rendered_html
			(proposal_id, html_blob, base_template_id)
			VALUES (?,?,?)
			ON DUPLICATE KEY UPDATE
				html_blob = ?';

		if ($template_id !== 'null')
		{
			$sql .= ',
				base_template_id = ?';
		}
		$this->db->query($sql, array($prop_id, $html, $template_id, $html, $template_id));

		return $this->db->insert_id();
	}

	public function save_proposal_pdf_location($proposal_id, $location)
	{
		$sql = '
			UPDATE prop_gen_prop_data
			SET pdf_location = ?
			WHERE prop_id = ?
		';
		$this->db->query($sql, array($location, $proposal_id));
	}

	public function save_template($template_data)
	{
		$sql = '
			INSERT INTO proposal_templates
			(filename, s3_bucket, is_landscape)
			VALUES(?,?,?)
			ON DUPLICATE KEY UPDATE is_landscape = ?
		';
		$this->db->query($sql, array($template_data['filename'], $template_data['s3_bucket'], $template_data['is_landscape'],  $template_data['is_landscape']));

		return $this->db->insert_id();
	}

	public function get_proposal_name_details($proposal_id)
	{
		$sql = '
			SELECT
				mss.advertiser_name AS advertiser,
				pgpd.date_modified AS date
			FROM prop_gen_prop_data AS pgpd
				JOIN mpq_sessions_and_submissions AS mss
					ON pgpd.source_mpq = mss.id
			WHERE pgpd.prop_id = ?
		';
		$result = $this->db->query($sql, array($proposal_id));
		return $result->row_array();
	}

	public function sales_user_has_access_to_rfp($user_id, $is_super, $rfp_id)
	{
		if ($is_super)
		{
			$is_super_sql =
			"	SELECT
					mss.unique_display_id
				FROM
					mpq_sessions_and_submissions AS mss
				JOIN users AS creator
					ON mss.creator_user_id = creator.id
				JOIN wl_partner_hierarchy AS wph
					ON creator.partner_id = wph.descendant_id
				JOIN users AS us
					ON wph.ancestor_id = us.partner_id
				JOIN prop_gen_prop_data AS pg
					ON mss.id = pg.source_mpq
				WHERE
					us.id = ? AND
					pg.prop_id = ?
				UNION
				SELECT
					mss.unique_display_id
				FROM
					mpq_sessions_and_submissions AS mss
				JOIN users AS owner
					ON mss.owner_user_id = owner.id
				JOIN wl_partner_hierarchy wph
					ON owner.partner_id = wph.descendant_id
				JOIN users AS us
					ON wph.ancestor_id = us.partner_id
				JOIN prop_gen_prop_data AS pg
					ON mss.id = pg.source_mpq
				WHERE
					us.id = ? AND
					pg.prop_id = ?";
			$bindings = array($user_id, $rfp_id, $user_id, $rfp_id);
		}
		else
		{
			$is_super_sql =
			"	SELECT
					mss.unique_display_id
				FROM
					mpq_sessions_and_submissions AS mss
				JOIN prop_gen_prop_data AS pg
					ON mss.id = pg.source_mpq
				WHERE
					(mss.owner_user_id = ? OR mss.creator_user_id = ?) AND
					pg.prop_id = ?";
				$bindings = array($user_id, $user_id, $rfp_id);
		}

		$sql =
		"	SELECT DISTINCT mss_query.unique_display_id
			FROM
			(
				$is_super_sql
				UNION
				SELECT
					mss.unique_display_id
				FROM
					mpq_sessions_and_submissions AS mss
				JOIN users AS creator
					ON mss.creator_user_id = creator.id
				JOIN wl_partner_hierarchy AS wph
					ON creator.partner_id = wph.descendant_id
				JOIN wl_partner_owners AS wpo
					ON wpo.partner_id = wph.ancestor_id
				JOIN users AS us
					ON wpo.user_id = us.id
				JOIN prop_gen_prop_data AS pg
					ON mss.id = pg.source_mpq
				WHERE
					us.id = ? AND
					pg.prop_id = ?
				UNION
				SELECT
					mss.unique_display_id
				FROM
					mpq_sessions_and_submissions AS mss
				JOIN users AS owner
					ON mss.owner_user_id = owner.id
				JOIN wl_partner_hierarchy AS wph
					ON owner.partner_id = wph.descendant_id
				JOIN wl_partner_owners AS wpo
					ON wpo.partner_id = wph.ancestor_id
				JOIN users AS us
					ON wpo.user_id = us.id
				JOIN prop_gen_prop_data AS pg
					ON mss.id = pg.source_mpq
				WHERE
					us.id = ? AND
					pg.prop_id = ?
			) AS mss_query
		";

		array_push($bindings, $user_id, $rfp_id, $user_id, $rfp_id);

		$response = $this->db->query($sql, $bindings);
		if ($response)
		{
			return $response->num_rows() > 0;
		}
		return false;
	}

	public function get_template_by_proposal_strategy($proposal_id)
	{
		$sql =
		"	SELECT pt.*
			FROM proposal_templates pt
			JOIN cp_strategies_join_proposal_templates ptj
				ON ptj.proposal_templates_id = pt.id
			JOIN mpq_sessions_and_submissions mss
				ON mss.strategy_id = ptj.cp_strategies_id
			JOIN prop_gen_prop_data pgpd
				ON pgpd.source_mpq = mss.id
			WHERE
				pgpd.prop_id = ?
		";
		$result = $this->db->query($sql, $proposal_id);
		return $result->num_rows() > 0 ? $result->row_array() : false;
	}

	public function get_submitted_rfps($user_id, $role, $is_super)
	{
    ini_set('memory_limit', '2048M');

    $creator_sql = "CONCAT(creator.firstname, ' ', creator.lastname)";
		if($role == 'sales')
		{
			$creator_sql = "IF(creator.role NOT LIKE 'SALES', ' ', CONCAT(creator.firstname, ' ', creator.lastname))";
		}
		$bindings = array();
		$select_sql = "
			mss.id,
			mss.creation_time,
			mss.creator_user_id,
			mss.advertiser_name,
			mss.advertiser_website,
			mss.proposal_name,
			mss.submitter_name,
			mss.submitter_email,
			mss.industry_id,
			mss.parent_mpq_id,
			mss.owner_user_id,
			mss.unique_display_id,
			it.name AS industry_name,
			CONCAT(owner.firstname, ' ', owner.lastname) AS owner_name,
			".$creator_sql." AS creator_name,
			pg.prop_id AS proposal_id,
			pg.pdf_location AS pdf_location,
			pg.process_status AS process_status,
			wl.partner_name AS partner_name,
			wl.is_demo_partner AS is_demo,
			cpp.can_become_campaign,
			options_query.grand_total_dollars,
			options_query.term,
			options_query.duration,
			pcs.is_gate_complete,
			pcs.is_targeting_complete,
			pcs.is_budget_complete,
			pcs.is_builder_complete";
		if($role == 'admin' or $role == 'ops')
		{
			$query = "
				SELECT
					".$select_sql.",
					MAX(can_become_campaign) as can_submit_io
				FROM
					mpq_sessions_and_submissions AS mss
					JOIN freq_industry_tags AS it
						ON mss.industry_id = it.freq_industry_tags_id
					JOIN users AS owner
						ON mss.owner_user_id = owner.id
					JOIN users AS creator
						ON mss.creator_user_id = creator.id
					JOIN prop_gen_prop_data AS pg
						ON mss.id = pg.source_mpq
					LEFT JOIN wl_partner_details wl
						ON owner.partner_id = wl.id
					JOIN cp_submitted_products csp
						ON mss.id = csp.mpq_id
					JOIN cp_products cpp
						ON csp.product_id = cpp.id
					LEFT JOIN proposal_completion_status pcs
						ON mss.id = pcs.mpq_id
					JOIN (
						SELECT
							moa.mpq_id,
							moa.term,
							moa.duration,
							moa.grand_total_dollars
						FROM
							mpq_options moa
							LEFT JOIN mpq_options mob
								ON moa.mpq_id = mob.mpq_id AND
								(
									moa.grand_total_dollars < mob.grand_total_dollars OR
									(
										moa.grand_total_dollars = mob.grand_total_dollars AND
										moa.option_id < mob.option_id
									)
								)
						WHERE
							mob.grand_total_dollars IS NULL
					) options_query
						ON mss.id = options_query.mpq_id
				WHERE
					mss.is_submitted = 1 AND
					mss.owner_user_id IS NOT NULL AND
					mss.creator_user_id IS NOT NULL AND
					mss.id NOT IN
					(
						SELECT DISTINCT
							parent_mpq_id
						FROM
								mpq_sessions_and_submissions
						WHERE
							parent_mpq_id IS NOT NULL AND
							mpq_type IS NULL AND
							is_submitted = 1
					)
				GROUP BY
					mss.id";
		}
		else if($role == 'sales')
		{
			if($is_super == 1)
			{
				// sales people with isGroupSuper == 1 need to get mpq_sessions_and_submissions from 2 different sources
				// 1) rfps owned by users in the current user's partner hierarchy
				// 2) rfps owned by users that are in the hierarchy of a partner owned by the current user

				$query = "
					SELECT DISTINCT
						*,
						MAX(can_become_campaign) as can_submit_io
					FROM
					(
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_hierarchy wph
								ON owner.partner_id = wph.descendant_id
							JOIN users AS us
								ON wph.ancestor_id = us.partner_id
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN freq_industry_tags AS it
								ON mss.industry_id = it.freq_industry_tags_id
							JOIN prop_gen_prop_data AS pg
								ON mss.id = pg.source_mpq
							LEFT JOIN wl_partner_details wl
								ON owner.partner_id = wl.id
							JOIN cp_submitted_products csp
								ON mss.id = csp.mpq_id
							JOIN cp_products cpp
								ON csp.product_id = cpp.id
							LEFT JOIN proposal_completion_status pcs
								ON mss.id = pcs.mpq_id
							JOIN (
								SELECT
									moa.mpq_id,
									moa.term,
									moa.duration,
									moa.grand_total_dollars
								FROM
									mpq_options moa
									LEFT JOIN mpq_options mob
										ON moa.mpq_id = mob.mpq_id AND
										(
											moa.grand_total_dollars < mob.grand_total_dollars OR
											(
												moa.grand_total_dollars = mob.grand_total_dollars AND
												moa.option_id < mob.option_id
											)
										)
								WHERE
									mob.grand_total_dollars IS NULL
							) options_query
								ON mss.id = options_query.mpq_id
						WHERE
							us.id = ? AND
							mss.is_submitted = 1 AND
							mss.owner_user_id IS NOT NULL AND
							mss.creator_user_id IS NOT NULL
						UNION
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_hierarchy AS wph
								ON owner.partner_id = wph.descendant_id
							JOIN wl_partner_owners AS wpo
								ON wpo.partner_id = wph.ancestor_id
							JOIN users AS us
								ON wpo.user_id = us.id
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN freq_industry_tags AS it
								ON mss.industry_id = it.freq_industry_tags_id
							JOIN prop_gen_prop_data AS pg
								ON mss.id = pg.source_mpq
							LEFT JOIN wl_partner_details wl
								ON owner.partner_id = wl.id
							JOIN cp_submitted_products csp
								ON mss.id = csp.mpq_id
							JOIN cp_products cpp
								ON csp.product_id = cpp.id
							LEFT JOIN proposal_completion_status pcs
								ON mss.id = pcs.mpq_id
							JOIN (
								SELECT
									moa.mpq_id,
									moa.term,
									moa.duration,
									moa.grand_total_dollars
								FROM
									mpq_options moa
									LEFT JOIN mpq_options mob
										ON moa.mpq_id = mob.mpq_id AND
										(
											moa.grand_total_dollars < mob.grand_total_dollars OR
											(
												moa.grand_total_dollars = mob.grand_total_dollars AND
												moa.option_id < mob.option_id
											)
										)
								WHERE
									mob.grand_total_dollars IS NULL
							) options_query
								ON mss.id = options_query.mpq_id
						WHERE
							us.id = ? AND
							mss.is_submitted = 1 AND
							mss.owner_user_id IS NOT NULL AND
							mss.creator_user_id IS NOT NULL
					) AS mss_query
					WHERE
						mss_query.id NOT IN
						(
							SELECT DISTINCT
								parent_mpq_id
							FROM
									mpq_sessions_and_submissions
							WHERE
								parent_mpq_id IS NOT NULL AND
								mpq_type IS NULL AND
								is_submitted = 1
						)
					GROUP BY
						mss_query.id
					ORDER BY
						mss_query.creation_time DESC";
				$bindings = array($user_id, $user_id);
			}
			else
			{
				// sales people with isGroupSuper == 0 need to get mpq_sessions_and_submissions in 2 ways
				// 1) rfps owned by the current user
				// 2) rfps owned by users that are in the hierarchy of a partner owned by the current user
				$query = "
					SELECT DISTINCT
						*,
						MAX(can_become_campaign) as can_submit_io
					FROM
					(
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN freq_industry_tags AS it
								ON mss.industry_id = it.freq_industry_tags_id
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN prop_gen_prop_data AS pg
								ON mss.id = pg.source_mpq
							LEFT JOIN wl_partner_details wl
								ON owner.partner_id = wl.id
							JOIN cp_submitted_products csp
								ON mss.id = csp.mpq_id
							JOIN cp_products cpp
								ON csp.product_id = cpp.id
							LEFT JOIN proposal_completion_status pcs
								ON mss.id = pcs.mpq_id
							JOIN (
								SELECT
									moa.mpq_id,
									moa.term,
									moa.duration,
									moa.grand_total_dollars
								FROM
									mpq_options moa
									LEFT JOIN mpq_options mob
										ON moa.mpq_id = mob.mpq_id AND
										(
											moa.grand_total_dollars < mob.grand_total_dollars OR
											(
												moa.grand_total_dollars = mob.grand_total_dollars AND
												moa.option_id < mob.option_id
											)
										)
								WHERE
									mob.grand_total_dollars IS NULL
							) options_query
								ON mss.id = options_query.mpq_id
						WHERE
							mss.owner_user_id = ? AND
							mss.is_submitted = 1 AND
							mss.owner_user_id IS NOT NULL AND
							mss.creator_user_id IS NOT NULL
						UNION
						SELECT
							".$select_sql."
						FROM
							mpq_sessions_and_submissions AS mss
							JOIN users AS owner
								ON mss.owner_user_id = owner.id
							JOIN wl_partner_hierarchy AS wph
								ON owner.partner_id = wph.descendant_id
							JOIN wl_partner_owners AS wpo
								ON wpo.partner_id = wph.ancestor_id
							JOIN users AS us
								ON wpo.user_id = us.id
							JOIN freq_industry_tags AS it
								ON mss.industry_id = it.freq_industry_tags_id
							JOIN users AS creator
								ON mss.creator_user_id = creator.id
							JOIN prop_gen_prop_data AS pg
								ON mss.id = pg.source_mpq
							LEFT JOIN wl_partner_details wl
								ON owner.partner_id = wl.id
							JOIN cp_submitted_products csp
								ON mss.id = csp.mpq_id
							JOIN cp_products cpp
								ON csp.product_id = cpp.id
							LEFT JOIN proposal_completion_status pcs
								ON mss.id = pcs.mpq_id
							JOIN (
								SELECT
									moa.mpq_id,
									moa.term,
									moa.duration,
									moa.grand_total_dollars
								FROM
									mpq_options moa
									LEFT JOIN mpq_options mob
										ON moa.mpq_id = mob.mpq_id AND
										(
											moa.grand_total_dollars < mob.grand_total_dollars OR
											(
												moa.grand_total_dollars = mob.grand_total_dollars AND
												moa.option_id < mob.option_id
											)
										)
								WHERE
									mob.grand_total_dollars IS NULL
							) options_query
								ON mss.id = options_query.mpq_id
						WHERE
							us.id = ? AND
							mss.is_submitted = 1 AND
							mss.owner_user_id IS NOT NULL AND
							mss.creator_user_id IS NOT NULL
					) AS mss_query
					WHERE
						mss_query.id NOT IN
						(
							SELECT DISTINCT
								parent_mpq_id
							FROM
									mpq_sessions_and_submissions
							WHERE
								parent_mpq_id IS NOT NULL AND
								mpq_type IS NULL AND
								is_submitted = 1
						)
					GROUP BY
						mss_query.id
					ORDER BY
						mss_query.creation_time DESC";
				$bindings = array($user_id, $user_id);
			}
		}
		else
		{
			return false;
		}
		$response = $this->db->query($query, $bindings);
		if($response)
		{
			$raw_rfp_data = $response->result_array();
			return $raw_rfp_data;
		}
		return false;
	}

	public function get_mpq_by_id($mpq_id)
	{
		$result_array=array();
		$sql = "
				SELECT
					mss.id,
					mss.advertiser_name,
					mss.advertiser_website,
					mss.creation_time,
					mss.last_updated,
					mss.owner_user_id,
					CONCAT(owner.firstname, ' ', owner.lastname) AS owner_name,
					CONCat(creator.firstname, ' ', creator.lastname) AS creator_name,
					mss.mpq_type,
					mss.is_submitted,
					ios.opportunity_status,
					ios.product_status,
					ios.geo_status,
					ios.audience_status,
					ios.flights_status,
					ios.creative_status,
					ind.name AS industry_name,
					mss.region_data,
					mss.submitter_email,
					owner.email AS owner_email,
					creator.email AS creator_email,
					mss.demographic_data,
					mss.notes AS notes,
					mss.unique_display_id,
					mss.include_retargeting,
					mss.order_id,
					mss.order_name
				FROM
					mpq_sessions_and_submissions AS mss
					LEFT JOIN users AS owner
						ON mss.owner_user_id = owner.id
					LEFT JOIN users AS creator
						ON mss.creator_user_id = creator.id
					JOIN io_status AS ios
						ON mss.id = ios.mpq_id
					LEFT JOIN freq_industry_tags AS ind
						ON ind.freq_industry_tags_id = mss.industry_id
				WHERE
					mss.creator_user_id IS NOT NULL AND
					mss.mpq_type IN ('io-submitted','io-saved','io-in-review')
					and mss.id=?

		";
		$result = $this->db->query($sql, $mpq_id);
		$result_array['mpq_core']=$result->row_array();

		$sql = "
				SELECT
					a.region_data_index,
					a.id,
					c.friendly_name,
					c.o_o_enabled,
					c.o_o_dfp
				FROM
					cp_submitted_products a,
					mpq_sessions_and_submissions b,
					cp_products c,
					cp_products_join_io cpj
				WHERE
					a.mpq_id=b.id AND
					mpq_type IS NOT NULL AND
					a.mpq_id=? AND
					a.product_id=c.id AND
					cpj.product_id = a.product_id AND
					cpj.mpq_id = a.mpq_id AND
					c.can_become_campaign = 1
				ORDER BY a.region_data_index,
				c.friendly_name
		";
		$result = $this->db->query($sql, $mpq_id);
		$result_array['mpq_products']=$result->result_array();
		$submitted_product_ids_array=array();
		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$submitted_product_ids_array[]=$row['id'];
			}
		}
		$result_array['submitted_product_ids_array']=$submitted_product_ids_array;

		 $sql = "
			SELECT
				p.id AS id,
				CONCAT(a.creative_name, ' || v', c.version) AS text,
				c.id AS cup_id,
				c.base_64_encoded_id AS base_64_id,
				c.vanity_string AS vanity_string
			FROM
				cup_versions c,
				adset_requests a,
				cp_io_join_cup_versions v,
				cp_submitted_products p
			WHERE
				a.adset_id=c.adset_id AND
				c.id = v.cup_versions_id AND
				v.cp_submitted_products_id = p.id AND
				p.mpq_id = ?
			UNION
			SELECT
				p.id AS id,
				CONCAT('PENDING: ',a.creative_name, ' || v', c.version) AS text,
				c.id AS cup_id,
				c.base_64_encoded_id AS base_64_id,
				c.vanity_string AS vanity_string
			FROM
				cup_versions c,
				adset_requests a,
				cp_io_join_cup_versions v,
				cp_submitted_products p
			WHERE
				a.adset_id=c.adset_id AND
				a.id = v.adset_request_id AND
				v.cp_submitted_products_id = p.id AND
				p.mpq_id = ?
			ORDER BY
				id, text ASC
			 ";
		$result = $this->db->query($sql, array($mpq_id, $mpq_id));
		$result_array['mpq_creatives']=$result->result_array();

		$geofences = $this->mpq_v2_model->get_geofencing_points_from_mpq_id_and_location_id($mpq_id);
		$result_array['geofence_data'] = $geofences;

		return $result_array;
	}

	public function get_timeseries_data_for_flight_table($campaign_ids)
	{
			$sql="
			SELECT
				campaigns_id,
				DATE_FORMAT(series_date, '%Y-%m-%d') AS timeseries_string,
				DATE_FORMAT(DATE_SUB(series_date, INTERVAL 1 day), '%Y-%m-%d') AS previous_day_timeseries_string,
				cts.impressions impressions,
				cts.budget AS budget,
				ifsd.audience_ext_impressions AS audience_ext_impressions,
				ifsd.audience_ext_budget AS audience_ext_budget,
				ifsd.geofencing_budget AS geofencing_budget,
				ifsd.geofencing_impressions AS geofencing_impressions,
				ifsd.o_o_impressions AS o_o_impressions,
				ifsd.response_o_o_dfp_budget AS o_o_budget,
				CASE WHEN (SELECT 'preroll' FROM io_campaign_product_cpm AS cpm WHERE cpm.subproduct_type_id = 2 AND cpm.campaign_id = cts.campaigns_id) = 'preroll' THEN 'preroll' ELSE 'display' END AS product_name
			 FROM
				campaigns_time_series cts
				JOIN io_flight_subproduct_details AS ifsd
					ON (cts.id = ifsd.io_timeseries_id)
			WHERE
				cts.object_type_id = 0
			AND
				cts.campaigns_id IN ($campaign_ids)
			ORDER by
				campaigns_id, series_date
			";

		$result_array=array();
		$new_result_array['results'] = array();
		$query = $this->db->query($sql);
		$is_preroll_campaign = false;
		
		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$result_array[]= array(
					'campaigns_id'=>$row['campaigns_id'],
					'start'=>$row['timeseries_string'],
					'impressions'=>$row['impressions'],
					'end'=>$row['previous_day_timeseries_string'],
					'budget'=>$row['budget'],
					'audience_ext_impressions' => $row['audience_ext_impressions'],
					'audience_ext_budget' => $row['audience_ext_budget'],
					'geofencing_budget' => $row['geofencing_budget'],
					'geofencing_impressions' => $row['geofencing_impressions'],
					'o_o_impressions' => $row['o_o_impressions'],
					'o_o_budget' => $row['o_o_budget']
				);
				
				if ($row['product_name'] == 'preroll')
				{
					$is_preroll_campaign = true;
				}
				
			}
		}

		for ($i=0; $i < count($result_array) -1; $i++)
		{
		    if(!empty($result_array[$i]['budget']))
		    {
			    $new_result_array['results'][]= array(
					'campaigns_id'=>$result_array[$i]['campaigns_id'],
					'start'=>$result_array[$i]['start'],
					'impressions'=>number_format($result_array[$i]['impressions']),
					'end'=>$result_array[$i+1]['end'],
					'budget'=>round($result_array[$i]['budget'], 2),
					'audience_ext_impressions' => number_format($result_array[$i]['audience_ext_impressions']),
					'audience_ext_budget' => round($result_array[$i]['audience_ext_budget'], 2),
					'geofencing_budget' => round($result_array[$i]['geofencing_budget'], 2),
					'geofencing_impressions' => number_format($result_array[$i]['geofencing_impressions']),
					'o_o_impressions' => number_format($result_array[$i]['o_o_impressions']),
					'o_o_budget' => round($result_array[$i]['o_o_budget'], 2)
				);
		     }
		 }

		 $enable_impressions_col = true;
		 $enable_budget_col = true;
		 $enable_audience_ext_impressions_col = true;
		 $enable_audience_ext_budget_col = true;
		 $enable_geofencing_budget_col = true;
		 $enable_geofencing_impressions_col = true;
		 $enable_o_o_impressions_col = true;
		 $enable_o_o_budget_col = true;

		 $total_impressions = 0;
		 $total_budget = 0;
		 $total_audience_ext_impressions = 0;
		 $total_audience_ext_budget = 0;
		 $total_geofencing_budget = 0;
		 $total_geofencing_impressions = 0;
		 $total_o_o_impressions = 0;
		 $total_o_o_budget = 0;

		 //Check sum of every budget,impressions
		 foreach($new_result_array['results'] as $flights)
		 {
		     $total_impressions += $flights['impressions'];
		     $total_budget += $flights['budget'];
		     $total_audience_ext_impressions += $flights['audience_ext_impressions'];
		     $total_audience_ext_budget += $flights['audience_ext_budget'];
		     $total_geofencing_budget += $flights['geofencing_budget'];
		     $total_geofencing_impressions += $flights['geofencing_impressions'];
		     $total_o_o_impressions += $flights['o_o_impressions'];
		     $total_o_o_budget += $flights['o_o_budget'];
		 }

		 if($total_impressions == 0)
		 {
		     $enable_impressions_col = false;
		 }

		 if($total_budget == 0)
		 {
		     $enable_budget_col = false;
		 }

		 if($total_audience_ext_impressions == 0)
		 {
		     $enable_audience_ext_impressions_col = false;
		 }
		 if($total_audience_ext_budget == 0)
		 {
		 	$enable_audience_ext_budget_col = false;
		 }

		 if($total_geofencing_budget == 0)
		 {
		     $enable_geofencing_budget_col = false;
		 }

		 if($total_geofencing_impressions == 0)
		 {
		     $enable_geofencing_impressions_col = false;
		 }

		 if($total_o_o_impressions == 0)
		 {
		     $enable_o_o_impressions_col = false;
		 }

		 if($total_o_o_budget == 0)
		 {
		     $enable_o_o_budget_col = false;
		 }

		 $new_result_array['col']['enable_impressions_col'] = $enable_impressions_col;
		 $new_result_array['col']['enable_budget_col'] = $enable_budget_col;
		 $new_result_array['col']['enable_audience_ext_impressions_col'] = $enable_audience_ext_impressions_col;
		 $new_result_array['col']['enable_audience_ext_budget_col'] = $enable_audience_ext_budget_col;
		 $new_result_array['col']['enable_geofencing_budget_col'] = $enable_geofencing_budget_col;
		 $new_result_array['col']['enable_geofencing_impressions_col'] = $enable_geofencing_impressions_col;
		 $new_result_array['col']['enable_o_o_impressions_col'] = $enable_o_o_impressions_col;
		 $new_result_array['col']['enable_o_o_budget_col'] = $enable_o_o_budget_col;
		 $new_result_array['col']['is_preroll_campaign'] = $is_preroll_campaign;

		return $new_result_array;
	}

	public function get_timeseries_data_for_hover($campaign_ids)
	{
			$sql="
			SELECT
				campaigns_id,
				DATE_FORMAT(series_date, '%Y-%m-%d') AS timeseries_string,
				DATE_FORMAT(DATE_SUB(series_date, INTERVAL 1 day), '%Y-%m-%d') AS previous_day_timeseries_string,
				format(cts.impressions,0) impressions,
				cts.budget AS budget,
				ifsd.audience_ext_impressions AS audience_ext_impressions,
				ifsd.audience_ext_budget AS audience_ext_budget,
				ifsd.geofencing_budget AS geofencing_budget,
				ifsd.geofencing_impressions AS geofencing_impressions,
				ifsd.o_o_impressions AS o_o_impressions,
				ifsd.response_o_o_dfp_budget AS o_o_budget,
				CONCAT(UCASE(MID(cpp.product_identifier,1,1)),MID(cpp.product_identifier,2)) AS product_name
			FROM
				campaigns_time_series cts
				JOIN io_flight_subproduct_details AS ifsd
					ON (cts.id = ifsd.io_timeseries_id)
				JOIN cp_submitted_products AS csp
					ON (csp.id = cts.campaigns_id)
				JOIN cp_products AS cpp
					ON (csp.product_id = cpp.id)	
			WHERE cts.object_type_id=1
				AND cts.campaigns_id IN ($campaign_ids)
			ORDER by
				campaigns_id, series_date
			";

		$result_array=array();
		$result_array_new=array();
		$query = $this->db->query($sql);

		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if (! (array_key_exists('c-'. $row['campaigns_id'], $result_array) == true))
				{
					$result_array['c-'. $row['campaigns_id']]=array();
				}
				$result_array['c-'. $row['campaigns_id']][]= array(
					'campaigns_id'=>$row['campaigns_id'],
					'start'=>$row['timeseries_string'],
					'impressions'=>$row['impressions'],
					'end'=>$row['previous_day_timeseries_string'],
					'budget'=>$row['budget'],
					'audience_ext_impressions' => $row['audience_ext_impressions'],
					'audience_ext_budget' => $row['audience_ext_budget'],
					'geofencing_budget' => $row['geofencing_budget'],
					'geofencing_impressions' => $row['geofencing_impressions'],
					'o_o_impressions' => $row['o_o_impressions'],
					'o_o_budget' => $row['o_o_budget'],
					'product_name' => $row['product_name']
				);
			}
			$today = date("Y-m-d");
			foreach ($result_array as $key => $value_array)
			{

				$campaign_string = "<table style='border-color:#cacaca; line-height:20px' border='1' cellpadding='7' cellspacing='0' height='100%' width='100%' id='flights_body_table'>";
				$table_header = "";
				$total_impr=0;
				$totals = null;
				 for ($i=0; $i < count($value_array) -1; $i++)
				 {
					if ($value_array[$i]['impressions'] != "")
					{
								if($table_header == "")
								{
									$table_header = "<thead><tr style='font-weight:bold;background-color:#696666; color:#fff'><td>Dates</td><td>Total<br>Budget</td>";
									$totals['budget'] = 0;

									if($value_array[$i]['o_o_impressions'] !== null && $value_array[$i]['o_o_budget'] !== null)
									{
										$table_header .= "<td>O&O<br>Impressions</td><td>O&O<br>Budget</td>";
										$totals['o_o_impressions'] = 0;
										$totals['o_o_budget'] = 0;
									}

									if($value_array[$i]['geofencing_budget'] !== null && $value_array[$i]['geofencing_impressions'] !== null)
									{
										$table_header .= "<td>".$value_array[$i]['product_name']."<br>Impressions (Est)</td>";
										$totals['audience_ext_impressions'] = 0;
									}

									if($value_array[$i]['geofencing_budget'] === null && $value_array[$i]['geofencing_impressions'] === null)
									{
										$table_header .= "<td>".$value_array[$i]['product_name']."<br>Impressions</td>";
										$totals['audience_ext_impressions'] = 0;
									}

									if($value_array[$i]['geofencing_budget'] !== null && $value_array[$i]['geofencing_impressions'] !== null)
									{
										$table_header .= "<td>Geofencing<br>Impressions (Est)</td>";
										$totals['geofencing_impressions'] = 0;
									}

									if($value_array[$i]['o_o_impressions'] !== null && $value_array[$i]['o_o_budget'] !== null)
									{
										$table_header .= "<td>Audience Ext.<br>Budget</td>";
										$totals['audience_ext_budget'] = 0;
									}

									$table_header .= "<tr>";
									$campaign_string .= $table_header;
								}

								if($value_array[$i]['budget'] == 0) continue;
								$table_row = "<tr><td>".$value_array[$i]['start'] . " - ". $value_array[$i+1]['end']."</td><td>$".number_format($value_array[$i]['budget'], 2)."</td>";
								$totals['budget'] += $value_array[$i]['budget'];

								if($value_array[$i]['o_o_impressions'] !== null && $value_array[$i]['o_o_budget'] !== null)
								{
									$table_row .= "<td>".number_format($value_array[$i]['o_o_impressions'])."</td><td>$".number_format($value_array[$i]['o_o_budget'], 2)."</td>";
									$totals['o_o_impressions'] += $value_array[$i]['o_o_impressions'];
									$totals['o_o_budget'] += $value_array[$i]['o_o_budget'];
								}

								if($value_array[$i]['geofencing_budget'] !== null && $value_array[$i]['geofencing_impressions'] !== null)
								{
									$table_row .= "<td>".number_format($value_array[$i]['audience_ext_impressions'])."</td>";
									$totals['audience_ext_impressions'] += $value_array[$i]['audience_ext_impressions'];
								}

								if($value_array[$i]['geofencing_budget'] === null && $value_array[$i]['geofencing_impressions'] === null)
								{
									$table_row .= "<td>".number_format($value_array[$i]['audience_ext_impressions'])."</td>";
									$totals['audience_ext_impressions'] += $value_array[$i]['audience_ext_impressions'];
								}

								if($value_array[$i]['geofencing_budget'] !== null && $value_array[$i]['geofencing_impressions'] !== null)
								{
									$table_row .= "<td>".number_format($value_array[$i]['geofencing_impressions'])."</td>";
									$totals['geofencing_impressions'] += $value_array[$i]['geofencing_impressions'];
								}

								if($value_array[$i]['o_o_impressions'] !== null && $value_array[$i]['o_o_budget'] !== null)
								{
									$table_row .= "<td>$".number_format($value_array[$i]['audience_ext_budget'], 2)."</td>";
									$totals['audience_ext_budget'] += $value_array[$i]['audience_ext_budget'];
								}

								$table_row .="</tr>";
								$campaign_string .= $table_row;
					}
				 }
				 if(!empty($totals))
				 {
				 	$total_row = "<tr><td><strong>Totals:</strong></td>";
				 	foreach($totals as $total_key => $total)
				 	{
				 		//echo $total_key;
				 		if(strpos($total_key, 'budget') !== false)
				 		{
				 			$total_row .= "<td>$".number_format($total,2)."</td>";
				 		}
				 		else
				 		{
				 			$total_row .= "<td>".number_format($total)."</td>";
				 		}
				 	}
				 	$total_row .= "</tr>";
				 	$campaign_string .= $total_row;
				 }

				 $campaign_string .= "</table>";
					$result_array_new[$key]=$campaign_string;
			}
		}
		return $result_array_new;
	}

	public function get_insertion_order_summary_html_core($mpq_id, $role, $mode)
	{
		$data = array();
		$data['role']=$role;
		$data['mpq'] = $this->get_mpq_by_id($mpq_id);
		$data['mpq']['geos'] = json_decode($data['mpq']['mpq_core']['region_data'], true);
		$data['mpq']['dfp_advertiser_id'] = null;
		$data['mpq']['dfp_orders'] = array();
		$submitted_product_ids_string=implode(",", $data['mpq']['submitted_product_ids_array']);

		$domain = $this->tank_auth->get_domain_without_cname();
		$cname = $this->get_insertion_order_advertiser_partner_cname($mpq_id);
		if($cname == false)
		{
			$cname = $this->get_insertion_order_owner_cname($mpq_id);
			if($cname == false)
			{
				$cname = "secure";
			}
		}
		$data['base_url'] = 'http://'.$cname.'.'.$domain.'/';

		$time_series_array_by_products_id = $this->get_timeseries_data_for_hover($submitted_product_ids_string);

		$geofences = $data['mpq']['geofence_data'];
		foreach ($data['mpq']['geos'] as &$row)
		{
			$row['name'] = $row['user_supplied_name'];
			$page = $row['page'];
			$row['products']=array();

			foreach($data['mpq']['mpq_products'] as $product)
			{
				if(array_key_exists('friendly_name', $product))
				{
					if($product['region_data_index'] == $page)
					{
						$region_product_sub_array=array();
						$region_product_sub_array['friendly_name']=$product['friendly_name'];

						if (array_key_exists('c-'.$product['id'], $time_series_array_by_products_id))
						{
							$region_product_sub_array['time_series']=$time_series_array_by_products_id['c-'.$product['id']];
						}
						else
						{
							$region_product_sub_array['time_series']= "No flights found";
						}
						$region_product_sub_array['mpq_creatives']=array();
						foreach ($data['mpq']['mpq_creatives'] as $creative_row)
						{
							if ($creative_row['id'] == $product['id'])
							{
								$region_product_sub_array['mpq_creatives'][]=$creative_row;
							}
						}
						$region_product_sub_array['o_o_data'] = null;
						$region_product_sub_array['o_o_enabled'] = $product['o_o_enabled'];
						if($product['o_o_enabled'])
						{
							$o_o_summary_data = $this->get_o_o_data_for_submitted_product($mpq_id, $product['id'], $product['o_o_dfp']);
							$region_product_sub_array['o_o_data'] = $o_o_summary_data;
						}
						$row['products'][]= $region_product_sub_array;
					}
				}
			}
			$row['zips'] = $row['ids']['zcta'];
			$row['geofence_points'] = [];
			if(!empty($geofences) && array_search(intval($page), array_column($geofences, 'location_id')) !== false)
			{
				foreach($geofences as $geofence_point)
				{
					if($geofence_point['location_id'] == intval($page))
					{
						$row['geofence_points'][] = $geofence_point;
					}
				}
			}
		}

		$data['mpq']['demographic_data'] = $this->parse_demo_string($data['mpq']['mpq_core']['demographic_data']);
		$data['mpq']['iab_data'] = $this->get_iab_categories_array_for_mpq_summary($mpq_id);
		$data['mode'] = $mode;
		return $data;
	}

	private function get_insertion_order_advertiser_partner_cname($io_id)
	{
		$get_io_adv_partner_query =
		"SELECT
			wlp.cname AS cname
		FROM
			mpq_sessions_and_submissions AS mss
			JOIN Advertisers AS adv
				ON mss.io_advertiser_id = adv.id AND mss.source_table = 'Advertisers'
			JOIN users AS u
				ON adv.sales_person = u.id
			JOIN wl_partner_details AS wlp
				ON u.partner_id = wlp.id
		WHERE
			mss.id = ?
		";

        $get_io_adv_partner_result = $this->db->query($get_io_adv_partner_query, $io_id);

        if($get_io_adv_partner_result == false || $get_io_adv_partner_result->num_rows() < 1)
        {
            return false;
        }
        $row = $get_io_adv_partner_result->row_array();
        return $row['cname'];
	}

    private function get_insertion_order_owner_cname($io_id)
    {
        $get_io_owner_partner_query =
        "SELECT
            wlp.cname AS cname
        FROM
            mpq_sessions_and_submissions AS mss
            JOIN users AS u
                ON mss.owner_user_id = u.id
            JOIN wl_partner_details AS wlp
                ON u.partner_id = wlp.id
        WHERE
            mss.id = ?
        ";

        $get_io_owner_partner_result = $this->db->query($get_io_owner_partner_query, $io_id);
        if($get_io_owner_partner_result == false || $get_io_owner_partner_result->num_rows() < 1)
        {
            return false;
        }
        $row = $get_io_owner_partner_result->row_array();
        return $row['cname'];
    }

	private function get_tv_zone_pack_data($mpq_id, $packages, $options, $custom_pack_type)
	{
		if(count($packages) == 0)
		{
			return array();
		}

		$bindings = array($mpq_id);
		$package_sql = "";
		foreach($packages as $package)
		{
			$bindings[] = $package['unit'] === "custom" ? $custom_pack_type : $package['unit'];
			if($package_sql !== "")
			{
				$package_sql .= ", ";
			}
			$package_sql .= "?";
		}

		$query = "
			SELECT
				tvz.*
			FROM
				cp_geo_regions_collection_join_mpq AS cpj
				JOIN geo_regions_collection AS grc
					ON cpj.geo_regions_collection_id = grc.id
				JOIN tv_zone_packs AS tvz
					ON grc.id = tvz.regions_collection_id
			WHERE
				cpj.mpq_id = ? AND
				 tvz.pack_name IN (".$package_sql.")";

		$response = $this->db->query($query, $bindings);
		if($response->num_rows() > 0)
		{
			$tv_zone_packs = $response->result_array();
			$tv_zone_pack_ids = array();
			foreach($tv_zone_packs as $zone_pack)
			{
				$tv_zone_pack_ids[] = $zone_pack['id'];
			}
			$zone_pack_networks = $this->get_tv_zone_pack_networks($tv_zone_pack_ids);

			if(count($zone_pack_networks) > 0)
			{
				$real_package_array = array();
				foreach($packages as $option_id => $package)
				{
					$is_custom = $package['unit'] === "custom";
					$pack_name = $is_custom ? $custom_pack_type : $package['unit'];

					if ($is_custom)
					{
						$package_index = false;
						if (count($real_package_array) > 0)
						{
							foreach($real_package_array as $i => $real_package)
							{
								if ($real_package['is_custom'] && $real_package['total_spots'] == $package['spots'])
								{
									$package_index = $i;
								}
							}
						}
					} else {
						$package_index = array_search($package['unit'], array_column($real_package_array, 'package_type'));
						if ($package_index !== false && $real_package_array[$package_index]['is_custom'])
						{
							$package_index = false;
						}
					}
					if($package_index === false)
					{
						$real_package_array[] = array(
							'name' => "",
							'package_type' => $is_custom ? $custom_pack_type : $package['unit'],
							'is_custom' => $is_custom,
							'networks' => array(),
							'total_spots' => $is_custom ? $package['spots'] : 0,
							'reach' => 0,
							'frequency' => 0,
							'max_grp' => 0
						);
						$package_index = count($real_package_array) - 1;
					}

					foreach($options as $option)
					{
						if($option['id'] == $option_id)
						{
							if($real_package_array[$package_index]['name'] !== "")
							{
								$real_package_array[$package_index]['name'] .= ", ";
							}
							$real_package_array[$package_index]['name'] .= $option['name'];
						}
					}

					if($real_package_array[$package_index]['total_spots'] === 0 || $is_custom)
					{
						foreach($tv_zone_packs as $zone_pack)
						{
							if($pack_name == $zone_pack['pack_name'] || ($is_custom && count($real_package_array[$package_index]['networks']) === 0))
							{
								if (!$is_custom)
								{
									$grp = $zone_pack['monthly_reach'] * $zone_pack['monthly_frequency'] * 100;
									if ($grp > $real_package_array[$package_index]['max_grp']) {
										$real_package_array[$package_index]['max_grp'] = $grp;
										$real_package_array[$package_index]['reach'] = round($zone_pack['monthly_reach'], 2) * 100;
										$real_package_array[$package_index]['frequency'] = round($zone_pack['monthly_frequency'], 1);
									}
								}

								foreach($zone_pack_networks as $network)
								{
									if($zone_pack['id'] == $network['tv_zone_pack_id'])
									{
										$network_index = array_search($network['network_abbreviation'], array_column($real_package_array[$package_index]['networks'], 'name'));
										if($network_index === false)
										{
											$real_package_array[$package_index]['networks'][] = array(
												'name' => $network['network_abbreviation'],
												'spots' => (int) $network['monthly_spot_count'],
												'image' => 'https://s3.amazonaws.com/brandcdn-assets/images/tv_network_logos/'.$network['network_abbreviation'].'.png'
											);
										}
										else
										{
											$real_package_array[$package_index]['networks'][$network_index]['spots'] += $network['monthly_spot_count'];
										}
										if (!$is_custom)
										{
											$real_package_array[$package_index]['total_spots'] += $network['monthly_spot_count'];
										}
									}
								}
							}
						}
					}
				}
				$indexed_package_array = array();
				foreach($real_package_array as &$real_package)
				{
					usort($real_package['networks'], function($a, $b) {
							return $b['spots'] - $a['spots'];
						});
					if(count($real_package_array) == 1)
					{
						$real_package['name'] = 'Network Summary';
					}
					$real_package['package_type'] = strtolower(str_replace(' ', '_', $real_package['package_type']));
					$indexed_package_array[] = $real_package;
				}
				return $indexed_package_array;

			}
		}
		return array();
	}

	private function get_tv_zone_pack_networks($tv_zone_pack_ids)
	{
		if(count($tv_zone_pack_ids) > 0)
		{
			$id_sql = "";
			foreach($tv_zone_pack_ids as $id)
			{
				if($id_sql !== "")
				{
					$id_sql .= ", ";
				}
				$id_sql .= "?";
			}
			$query = "
				SELECT
					*
				FROM
					tv_zone_pack_networks
				WHERE
					tv_zone_pack_id IN (".$id_sql.")
				ORDER BY
					monthly_spot_count DESC";
			$response = $this->db->query($query, $tv_zone_pack_ids);
			if($response->num_rows() > 0)
			{
				return $response->result_array();
			}
		}
		return array();
	}

	private function get_tv_spots($mpq_id, $packages, $options)
	{
		$return_array = array();
		foreach($options as $option)
		{
			$return_array[$option['id']] = 0;
		}
		$bindings = array($mpq_id);
		$package_sql = "";
		foreach($packages as $package)
		{
			if ($package['unit'] !== "custom")
			{
				$bindings[] = $package['unit'];
				if($package_sql !== "")
				{
					$package_sql .= ", ";
				}
				$package_sql .= "?";
			}
		}
		if (count($bindings) > 1)
		{
			$query = "
				SELECT
					tvz.pack_name,
					SUM(tpn.monthly_spot_count) AS spots
				FROM
					cp_geo_regions_collection_join_mpq AS cpj
					JOIN geo_regions_collection AS grc
						ON cpj.geo_regions_collection_id = grc.id
					JOIN tv_zone_packs AS tvz
						ON grc.id = tvz.regions_collection_id
					JOIN tv_zone_pack_networks AS tpn
						ON tvz.id = tpn.tv_zone_pack_id
				WHERE
					cpj.mpq_id = ? AND
					tvz.pack_name IN (".$package_sql.")
					GROUP BY tvz.pack_name";
			$response = $this->db->query($query, $bindings);
			if($response->num_rows() > 0)
			{
				foreach($response->result_array() as $result)
				{
					foreach($packages as $option_id => $package)
					{
						if ($package['unit'] == "custom")
						{
							$return_array[$option_id] = $package['spots'];
						} else {
							if($package['unit'] == $result['pack_name'])
							{
								$return_array[$option_id] = $result['spots'];
							}
						}
					}
				}
			}
		} else {
			$return_array = array_map(function($package) {
				return $package['spots'];
			}, $packages);
		}
		return $return_array;
	}

	public function get_tv_networks_demographics($selected_networks, $selected_demos, $partner_id)
	{
		$selected_demos = implode(",", array_reduce($selected_demos, function($carry, $group) {
			$all_selected = array_reduce($group, function($selected, $demo) {
				return $demo === true;
			}, true);

			if ($all_selected)
			{
				return $carry;
			}

			foreach($group as $demo => $value)
			{
				if ($value) {
					$carry[] = "'".$demo."'";
				}
			}
			return $carry;
		}, []));

		if ($selected_demos === '')
		{
			$selected_demos = "LIKE '%'";
		} else {
			$selected_demos = "IN ($selected_demos)";
		}

		$sql =
		'	SELECT
				name,
				friendly_name,
				logo,
				(EXP(SUM(LOG(demo_index))) / EXP(SUM(LOG(viewer_avg)))) as demo_index
			FROM (
				SELECT
					network.name,
					network.friendly_name,
					network.logo,
					category.name as demo_category,
					SUM(data.value / (data.demo_index / 100)) as viewer_avg,
					SUM(data.value) as demo_index
				FROM tv_networks network
				JOIN tv_network_demographics data
					ON network.id = data.tv_networks_id
				JOIN demographic_values demos
					ON demos.id = data.demographic_values_id
				JOIN demographic_categories category
					ON category.id = demos.demographic_categories_id
				WHERE
					data.partner_id = 3 AND
					demos.friendly_name '.$selected_demos.' AND
					network.name IN ('.$selected_networks.')
				GROUP BY category.name, network.name
			) result
			GROUP BY name
		';
		$query = $this->db->query($sql, array($partner_id));
		if ($query->num_rows() > 0)
		{
			return $query->result_array();
		}
	}

	public function get_list_of_proposal_ids_from_visible_rfps($update_existing = false)
	{
		$grand_total_sql = "";
		if($update_existing === false)
		{
			$grand_total_sql = "mpo.grand_total_dollars = 0 AND";
		}
		$query = "
			SELECT
				prop.prop_id
			FROM
				mpq_sessions_and_submissions AS mss
				JOIN prop_gen_prop_data AS prop
					ON mss.id = prop.source_mpq
				JOIN mpq_options AS mpo
					ON mss.id = mpo.mpq_id
			WHERE
				".$grand_total_sql."
				mss.is_submitted = 1 AND
				mss.owner_user_id IS NOT NULL AND
				mss.creator_user_id IS NOT NULL AND
				mss.id NOT IN
				(
					SELECT DISTINCT
						parent_mpq_id
					FROM
							mpq_sessions_and_submissions
					WHERE
						parent_mpq_id IS NOT NULL AND
						mpq_type IS NULL AND
						is_submitted = 1
				)
			GROUP BY prop.prop_id";
		$response = $this->db->query($query);
		if($response->num_rows() > 0)
		{
			$proposal_ids = array();
			foreach($response->result_array() as $row)
			{
				$proposal_ids[] = $row['prop_id'];
			}
			return $proposal_ids;
		}
		return array();
	}

	public function update_mpq_options_with_grand_total($mpq_id, $option_id, $grand_total)
	{
		$bindings = array($grand_total, $mpq_id, $option_id);
		$query = "
			UPDATE mpq_options SET grand_total_dollars = ? WHERE mpq_id = ? AND option_id = ?";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_keywords_data($mpq_id)
	{
		$return_array = array('clicks' => 0, 'search_terms' => array());
		$query = "
			SELECT
				*
			FROM
				mpq_search_keywords_data
			WHERE
				mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() > 0)
		{
			$return_array['clicks'] = $response->row()->clicks;
			$return_array['search_terms'] = json_decode($response->row()->search_terms);
		}
		return $return_array;
	}

	public function get_proposal_palette_by_partner_id($partner_id)
	{
		$query = "
			SELECT
				prp.primary_color,
				prp.secondary_color,
				prp.tertiary_color,
				prp.quaternary_color,
				prp.quinary_color
			FROM
				wl_partner_details wpd
				JOIN proposal_palettes prp
					ON wpd.proposal_palettes_id = prp.id
			WHERE
				wpd.id = ?";
		$response = $this->db->query($query, $partner_id);
		if($response->num_rows() > 0)
		{
			return $response->row_array();
		}
		else
		{
			$default_query = "
				SELECT
					primary_color,
					secondary_color,
					tertiary_color,
					quaternary_color,
					quinary_color
		 		FROM
					proposal_palettes
				WHERE
					is_default_palette = 1
				LIMIT 1";
			$default_response = $this->db->query($default_query);
			if($default_response->num_rows() > 0)
			{
				return $default_response->row_array();
			}

			//use the default f2 colors if everything is broken
			return array(
				'primary_color' => '#ea6144',
				'secondary_color' => '#3498db',
				'tertiary_color' => '#24a2a6',
				'quaternary_color' => '#f9a83b',
				'quinary_color' => '#465872'
				);
		}

	}
	public function get_site_category_svgs($categories)
	{
		$svg_html = "";
		foreach($categories as $category)
		{
		    if($category == 'industry')
			{
				$svg_html .= '<svg class="websites-category-svg" viewBox="0 0 49.28 49.28"><path class="websites-category-industry-component" d="M418.7,608.27l-4.88-.81a18.39,18.39,0,0,0-1.55-4.13l3.12-3.87a2.15,2.15,0,0,0-.07-2.79l-1.94-2.19a2.16,2.16,0,0,0-2.76-.39l-4.17,2.62a18.37,18.37,0,0,0-6.16-2.78L399.48,589a2.15,2.15,0,0,0-2.13-1.8h-2.93a2.15,2.15,0,0,0-2.12,1.8l-0.82,4.91a18.35,18.35,0,0,0-5.18,2.15l-4-2.83a2.16,2.16,0,0,0-2.78.23l-2.07,2.07a2.16,2.16,0,0,0-.23,2.78l2.84,4a18.4,18.4,0,0,0-2.14,5.14l-4.94.82a2.15,2.15,0,0,0-1.8,2.13v2.92a2.15,2.15,0,0,0,1.8,2.13l4.94,0.82a18.36,18.36,0,0,0,1.63,4.3l-3.11,3.85a2.16,2.16,0,0,0,.07,2.79l1.94,2.19a2.16,2.16,0,0,0,2.76.39l4.23-2.66a18.34,18.34,0,0,0,6,2.66l0.82,4.91a2.15,2.15,0,0,0,2.12,1.8h2.93a2.15,2.15,0,0,0,2.13-1.8l0.82-4.91a18.33,18.33,0,0,0,5.12-2.12l4.14,3a2.15,2.15,0,0,0,2.78-.23l2.07-2.07a2.15,2.15,0,0,0,.23-2.78l-2.94-4.13a18.31,18.31,0,0,0,2.14-5.16l4.88-.81a2.15,2.15,0,0,0,1.8-2.13V610.4a2.15,2.15,0,0,0-1.8-2.13h0ZM395.94,621.1a9.24,9.24,0,1,1,9.24-9.24,9.24,9.24,0,0,1-9.24,9.24h0Zm0,0" transform="translate(-371.22 -587.22)"/></svg>';
			}
			else if($category == 'contextual')
			{
				$svg_html .= '<svg class="websites-category-svg" viewBox="0 0 50 50"><rect class="websites-category-audience-component" x="37.5" width="12.5" height="50"/><rect class="websites-category-audience-component" x="18.75" y="12.49" width="12.49" height="37.51"/><rect class="websites-category-audience-component" y="25" width="12.49" height="25"/></svg>';
			}
			else if($category == 'demographic')
			{
				$svg_html .= '<svg class="websites-category-svg" viewBox="0 0 47.62 40.08"><path class="websites-category-demographic-component" d="M400.93,598.84a8.66,8.66,0,0,1,4,6.43,7,7,0,1,0-4-6.43h0Zm-4.58,14.3a7,7,0,1,0-7-7,7,7,0,0,0,7,7h0Zm3,0.48h-5.93a9,9,0,0,0-8.94,8.94v7.25l0,0.11,0.5,0.16a40.74,40.74,0,0,0,12.16,2c6.57,0,10.38-1.87,10.62-2l0.46-.23h0v-7.25a9,9,0,0,0-8.94-8.94h0Zm11.55-7.21H405a8.61,8.61,0,0,1-2.66,6,10.62,10.62,0,0,1,7.59,10.17v2.23a24.07,24.07,0,0,0,9.37-2l0.46-.24h0.05v-7.25a9,9,0,0,0-8.94-8.94h0Zm-26.77-.48a6.94,6.94,0,0,0,3.71-1.08,8.66,8.66,0,0,1,3.25-5.51c0-.13,0-0.26,0-0.39a7,7,0,1,0-7,7h0Zm6.27,6.47a8.61,8.61,0,0,1-2.66-6c-0.22,0-.44,0-0.66,0h-5.93a9,9,0,0,0-8.94,8.94v7.25l0,0.11,0.5,0.16a41.92,41.92,0,0,0,10.07,1.89v-2.19a10.62,10.62,0,0,1,7.59-10.17h0Zm0,0" transform="translate(-372.19 -591.96)"/></svg>';
			}
		}
		if($svg_html == "")
		{
			return false;
		}
		return $svg_html;
	}

	private function get_o_o_data_for_submitted_product($mpq_id, $submitted_product_id, $is_o_o_dfp)
	{
		if($is_o_o_dfp)
		{
			$o_o_data = array('dfp_advertiser_id' => false, 'dfp_orders' => false);

			$o_o_data['dfp_advertiser_id'] = $this->get_o_o_dfp_advertiser_id($mpq_id);
			$o_o_data['dfp_orders'] = $this->get_o_o_dfp_order_details($mpq_id, $submitted_product_id);
		}
		else
		{
			$o_o_data['dfp_orders'] = $this->get_cp_o_o_dfp_order_details($submitted_product_id);
		}

		return $o_o_data;
	}

	private function get_o_o_dfp_advertiser_id($mpq_id)
	{
		$get_o_o_dfp_advertiser_query =
		"SELECT
			dfp_advertiser_id
		FROM
			io_dfp_advertiser_details
		WHERE
			insertion_order_id = ?
		";

		$get_o_o_dfp_advertiser_result = $this->db->query($get_o_o_dfp_advertiser_query, $mpq_id);

		if($get_o_o_dfp_advertiser_result != false && $get_o_o_dfp_advertiser_result->num_rows() > 0)
		{
			$row = $get_o_o_dfp_advertiser_result->row_array();
			return $row['dfp_advertiser_id'];
		}
		return false;
	}

	private function get_o_o_dfp_order_details($mpq_id, $submitted_product_id)
	{
		$get_o_o_dfp_orders_query =
		"SELECT
			idli.*
		FROM
			io_dfp_line_item AS idli
			JOIN io_flight_subproduct_details AS ifsd
				ON (idli.dfp_line_item_id = ifsd.f_o_o_adgroup_id)
			JOIN campaigns_time_series AS cts
				ON (ifsd.io_timeseries_id = cts.id)
		WHERE
			idli.insertion_order_id = ?
			AND cts.campaigns_id = ?
			AND cts.object_type_id = 1
		";
		$get_o_o_dfp_orders_result = $this->db->query($get_o_o_dfp_orders_query, array($mpq_id, $submitted_product_id));
		if($get_o_o_dfp_orders_result != false && $get_o_o_dfp_orders_result->num_rows() > 0)
		{
			$results = $get_o_o_dfp_orders_result->result_array();
			$check_id = '';
			$orders = array();
			foreach($results as $order_details)
			{
				if($check_id == $order_details['dfp_order_id']) continue;
				$orders[] = array('id' => $order_details['dfp_order_id'], 'order_url' => "https://www.google.com/dfp/#delivery/OrderDetail/orderId=".$order_details['dfp_order_id']);
				$check_id = $order_details['dfp_order_id'];
			}
			return $orders;
		}
		return false;
	}

    /*
     * New Builder functions
     */

    public function get_proposal_id_by_unique_display_id($unique_display_id)
    {
        $sql =
            '	SELECT pgpd.prop_id AS id
    		FROM prop_gen_prop_data AS pgpd
    		JOIN mpq_sessions_and_submissions AS mss
    			ON mss.id = pgpd.source_mpq
    		WHERE
    			mss.unique_display_id = ? AND
    			mss.is_submitted = 1 AND
    			mss.id NOT IN
				(
					SELECT DISTINCT
						parent_mpq_id
					FROM
							mpq_sessions_and_submissions
					WHERE
						parent_mpq_id IS NOT NULL AND
						mpq_type IS NULL AND
						is_submitted = 1
				)
			GROUP BY mss.id
			ORDER BY
				mss.creation_time DESC
			LIMIT 1
    	';

        $query = $this->db->query($sql, $unique_display_id);
        return $query->num_rows() > 0 ? $query->row()->id : false;
    }

    public function get_proposal_templates_by_strategy($proposal_id)
    {
        $sql =
            '	SELECT pt.*
    		FROM proposal_templates AS pt
    		JOIN cp_strategies_join_proposal_templates AS cps_pt
    			ON cps_pt.proposal_templates_id = pt.id
    		JOIN mpq_sessions_and_submissions AS mss
    			ON cps_pt.cp_strategies_id = mss.strategy_id
    		JOIN prop_gen_prop_data pgpd
    			ON pgpd.source_mpq = mss.id
    		WHERE
    			pgpd.prop_id = ?
    	';

        $query = $this->db->query($sql, $proposal_id);
        return $query ? $query->result() : false;
    }

    public function get_proposal_pages($proposal_id)
    {
        $sql = "
			SELECT 
				ptp.*,
    			pps.weight,
    			ptj.is_default_page,
                ptj.is_generic,
                ptj.is_not_deletable
			FROM 
				proposal_pages pps
				JOIN proposal_templates_join_proposal_templates_pages AS ptj
					ON ptj.proposal_templates_pages_id = pps.proposal_templates_pages_id
    			JOIN proposal_templates_pages AS ptp
    				ON pps.proposal_templates_pages_id = ptp.id
    		WHERE 
				pps.prop_gen_prop_data_id = ?
    		ORDER BY pps.weight
    	";

        $query = $this->db->query($sql, array($proposal_id));
        return $query->result_array();
    }

    public function get_proposal_page($page_id)
    {
        $page = $this->get_proposal_page_template($page_id);
        $page['includes'] = $this->get_proposal_template_includes($page['proposal_template_id']);
        return $page;
    }

    public function get_proposal_page_template($page_id)
    {
        $sql = "
			SELECT 
    			pps.id,
    			ptp.raw_html,
    		    ptj.proposal_templates_id AS proposal_template_id
    		FROM
				proposal_templates_pages AS ptp
    			JOIN proposal_pages AS pps
    				ON pps.proposal_templates_pages_id = ptp.id
    			JOIN proposal_templates_join_proposal_templates_pages AS ptj
    				ON ptj.proposal_templates_pages_id = ptp.id
    		WHERE pps.id = ?
    	";
        $query = $this->db->query($sql, $page_id);
        if ($query->num_rows() === 0)
        {
            return false;
        }

        return $query->row_array();
    }

    public function add_proposal_page($proposal_id, $weight, $template_page_id)
    {
        $sql =
            '	INSERT INTO proposal_pages
    			(prop_gen_prop_data_id, weight, proposal_templates_pages_id)
    			VALUES (?,?,?)
    	';

        $query = $this->db->query($sql, array($proposal_id, $weight, $template_page_id));
        $new_id = $this->db->insert_id();
        if ($new_id)
        {
            $reorder_sql =
                '	UPDATE proposal_pages
	    		SET weight = weight + 1
	    		WHERE 
	    			prop_gen_prop_data_id = ? AND
	    			id != ? AND
	    			weight >= ?
	    	';
            $query = $this->db->query($reorder_sql, array($proposal_id, (int) $new_id, (int) $weight));
        }
        return $this->get_proposal_page_template($new_id);
    }

    public function save_proposal_pages($proposal_id, $weight, $template_page_id)
    {
        $sql =
            '	INSERT INTO proposal_pages
    			(prop_gen_prop_data_id, weight, proposal_templates_pages_id)
    			VALUES (?,?,?)
    	';

        $query = $this->db->query($sql, array($proposal_id, $weight, $template_page_id));
        return $this->db->insert_id();
    }

    public function remove_proposal_page($page_id)
    {
        $proposal_id = $this->get_proposal_id_by_page_id($page_id);

        $sql =
            '	DELETE FROM proposal_pages
    		WHERE id = ?
    	';

        $query = $this->db->query($sql, array($page_id));
        if ($query)
        {
            $var_sql = 'SET @i = -1';
            $reorder_sql =
                '	UPDATE proposal_pages
	    		SET weight = @i:=@i+1
	    		WHERE 
	    			prop_gen_prop_data_id = ?
	    		ORDER BY weight ASC
	    	';
            $this->db->trans_start();
            $this->db->query($var_sql);
            $query = $this->db->query($reorder_sql, $proposal_id);
            $this->db->trans_complete();
        }
        return $query;
    }

    public function get_proposal_id_by_page_id($page_id)
    {
        $sql =
            '	SELECT prop_gen_prop_data_id AS id
    		FROM proposal_pages
    		WHERE id = ?
    		LIMIT 1
    	';
        $query = $this->db->query($sql, $page_id);
        if ($query->num_rows() > 0)
        {
            return $query->row()->id;
        }
    }

    public function get_template_id_by_proposal_id($proposal_id)
    {
        $sql =
            '   SELECT join_templates.proposal_templates_id AS id
            FROM cp_strategies_join_proposal_templates AS join_templates
            JOIN mpq_sessions_and_submissions AS mss 
                ON mss.strategy_id = join_templates.cp_strategies_id 
            JOIN prop_gen_prop_data AS proposal 
                ON proposal.source_mpq = mss.id
            WHERE 
                proposal.prop_id = ?
        ';
        $query = $this->db->query($sql, array($proposal_id));
        if ($query->num_rows() > 0)
        {
            return $query->row()->id;
        }
    }


    public function get_proposal_template_includes($template_id)
    {
        $sql =
            '	SELECT *
    		FROM proposal_templates_includes
    		WHERE proposal_templates_id = ?
    		ORDER BY weight
    	';
        $query = $this->db->query($sql, $template_id);
        return $query->result_array();
    }

	public function get_proposal_template_includes_from_template_list($template_ids)
	{
		$templates_sql = "-1";
		foreach($template_ids as $template_id)
		{
			$templates_sql .= ",?";
		}
		$query = "
			SELECT
				*
			FROM
				proposal_templates_includes
			WHERE
				proposal_templates_id IN (".$templates_sql.")
			ORDER BY weight";
		$response = $this->db->query($query, $template_ids);
		return $response->result_array();
	}

    public function populate_proposal_pages($proposal_id)
    {
        $sql = "
			INSERT INTO proposal_pages (prop_gen_prop_data_id, proposal_templates_pages_id, weight)
    		SELECT 
 				pgp.prop_id,
				ptp.id,
				ptj.weight
    		FROM 
				prop_gen_prop_data AS pgp
                JOIN mpq_sessions_and_submissions AS mss
					ON pgp.source_mpq = mss.id
				JOIN cp_strategies_join_proposal_templates AS csj
                    ON csj.cp_strategies_id = mss.strategy_id	
				JOIN proposal_templates AS pts
                    ON pts.id = csj.proposal_templates_id
				JOIN proposal_templates_join_proposal_templates_pages ptj
					ON pts.id = ptj.proposal_templates_id
				JOIN proposal_templates_pages AS ptp
					ON ptj.proposal_templates_pages_id = ptp.id
    		WHERE 
    			pgp.prop_id = ? AND
    			ptj.is_default_page = 1";
        $query = $this->db->query($sql, $proposal_id);
    }

    public function get_proposal_page_templates($proposal_id, $canvas_ids = array())
    {
        $canvas_sql = "";
        $bindings = array($proposal_id);
        if(count($canvas_ids > 0))
        {
            foreach($canvas_ids as $id)
            {
                if($canvas_sql !== "")
                {
                    $canvas_sql .= ", ";
                }
                $canvas_sql .= "?";
                $bindings[] = $id;
            }
            $canvas_sql = "AND ptp.id NOT IN (".$canvas_sql.")";
        }
        $sql = "
                SELECT 
                    ptp.*,
                    ptj.weight,
                    ptj.is_default_page,
                    ptj.is_generic,
                    ptj.is_not_deletable,
					IF(ptj.is_default_page, '-2', IF(ptc.id IS NULL, '-1', ptc.id)) AS category_id,
					IF(ptj.is_default_page, 'Previously Deleted Slides', IF(ptc.name IS NULL, 'Uncategorized', ptc.name)) AS category_name,
					IF(ptj.is_default_page, 999999999, IF(ptc.weight IS NULL, -1, ptc.weight)) AS category_weight
                FROM
					prop_gen_prop_data AS pgp
                    JOIN mpq_sessions_and_submissions AS mss
						ON pgp.source_mpq = mss.id
					JOIN cp_strategies_join_proposal_templates AS csj
                        ON csj.cp_strategies_id = mss.strategy_id	
					JOIN proposal_templates AS pts
                        ON pts.id = csj.proposal_templates_id
					JOIN proposal_templates_join_proposal_templates_pages ptj
						ON pts.id = ptj.proposal_templates_id
					JOIN proposal_templates_pages AS ptp
						ON ptj.proposal_templates_pages_id = ptp.id
					LEFT JOIN proposal_templates_pages_categories ptc
						ON ptp.proposal_templates_pages_categories_id = ptc.id
                WHERE
                    pgp.prop_id = ?
					".$canvas_sql."
                ORDER BY ptc.weight ASC, ptj.weight ASC";
        $query = $this->db->query($sql, $bindings);
        return $query->result_array();
    }

    public function save_all_proposal_pages($proposal_id, $template_pages)
    {
        if(count($template_pages) > 0)
        {
            $page_sql = "";
            $bindings = "";
            $weight = 5;
            foreach($template_pages as $template_page)
            {
                if($page_sql !== "")
                {
                    $page_sql .= ", ";
                }
                $page_sql.= "(?, ?, ?)";
                $bindings[] = $proposal_id;
                $bindings[] = $template_page;
                $bindings[] = $weight;
                $weight += 5;
            }
            if($page_sql !== "")
            {
                $response = $this->delete_proposal_pages($proposal_id);
                if($response)
                {
                    $query = "
						INSERT INTO proposal_pages 
						(prop_gen_prop_data_id, proposal_templates_pages_id, weight)
						VALUES ".$page_sql;
                    $response = $this->db->query($query, $bindings);
                    if($response)
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function delete_proposal_pages($prop_id)
    {
        $query = "DELETE FROM proposal_pages WHERE prop_gen_prop_data_id = ?";
        $response = $this->db->query($query, $prop_id);
        return $response;
    }

    public function get_geo_snapshots_data($prop_id)
	{
        $sql = "SELECT * FROM prop_gen_snapshot_data where prop_id = ?";
        $query = $this->db->query($sql, $prop_id);
        return $query->result_array();
    }

    public function get_rooftop_snapshots_data($prop_id){
        $sql = "SELECT prop_id, rooftops_snapshot FROM prop_gen_prop_data where prop_id = ?";
        $query = $this->db->query($sql, $prop_id);
        return $query->result_array();
    }

	public function get_categories_by_template_page_ids($ids)
	{
		if(count($ids) > 0)
		{
			$page_sql = "";
			foreach($ids as $id)
			{
				if($page_sql !== "")
				{
					$page_sql .= ",";
				}
				$page_sql .= $id;
			}
			$query = "
				SELECT DISTINCT
					ptc.*
				FROM
					proposal_templates_pages_categories ptc
					JOIN proposal_templates_pages ptp
						ON ptc.id = ptp.proposal_templates_pages_categories_id
				WHERE
					ptp.id IN (" . $page_sql . ")";
            $response = $this->db->query($query);
            return $response->result_array();
        }
    }

	private function get_cp_o_o_dfp_order_details($submitted_product_id)
	{
		$get_o_o_dfp_orders_query =
		"SELECT
			o_o_campaign_id
		FROM
			cp_submitted_product_o_o_campaigns
		WHERE
			cp_submitted_product_id = ?
		";
		$get_o_o_dfp_orders_result = $this->db->query($get_o_o_dfp_orders_query, array($submitted_product_id));
		if($get_o_o_dfp_orders_result != false && $get_o_o_dfp_orders_result->num_rows() > 0)
		{
			$results = $get_o_o_dfp_orders_result->result_array();
			$check_id = '';
			$orders = array();
			foreach($results as $order_details)
			{
				if($check_id == $order_details['o_o_campaign_id']) continue;
				$orders[] = array('id' => $order_details['o_o_campaign_id'], 'order_url' => "https://www.google.com/dfp/#delivery/OrderDetail/orderId=".$order_details['o_o_campaign_id']);
				$check_id = $order_details['o_o_campaign_id'];
			}
			return $orders;
		}
		return false;
	}

	public function builder_save_targeting_data($mpq_id, $demographics, $iab_categories, $rooftops = null, $tv_zones, $tv_selected_networks, $keywords_data, $advertiser_website, $political_segments)
	{
		$save_iab_categories_response = $this->save_iab_categories($mpq_id, $iab_categories);

		$save_tv_zones_response = true;
		if($tv_zones)
		{
			$save_tv_zones_response = $this->mpq->save_submitted_tv_zones($mpq_id, $tv_zones);
		}

		$save_tv_networks_response = true;
		if($tv_selected_networks)
		{
			$save_tv_networks_response = $this->mpq->save_selected_networks($tv_selected_networks, $mpq_id);
		}

		$save_political_segments_response = true;
		if($political_segments)
		{
			$save_political_segments_response = $this->mpq->save_mpq_political_segments($political_segments, $mpq_id);
		}

		$save_keywords_data_response = true;
		if($keywords_data)
		{
			$save_keywords_data_response = $this->mpq->save_keywords_data($mpq_id, $keywords_data['clicks'], $keywords_data['search_terms']);
		}
		if ($advertiser_website)
		{
			$this->mpq->save_advertiser_website($mpq_id, $advertiser_website);
		}

		//save mpq
	    $demographics = convert_demo_category_if_all_unset($demographics);
		$date_time = date("Y-m-d H:i:s");

		$query = "
			UPDATE mpq_sessions_and_submissions
			SET
				creation_time = ?,
				rooftops_data = ?,
				demographic_data = ?
			WHERE id = ?";
		$response = $this->db->query($query, array($date_time, $rooftops, $demographics, $mpq_id));

		return $response && $save_iab_categories_response && $save_tv_zones_response && $save_tv_networks_response && $save_political_segments_response && $save_keywords_data_response;
	}

	public function save_iab_categories($mpq_id, $iab_categories)
	{
		$delete_query = "DELETE FROM mpq_iab_categories_join WHERE mpq_id = ?";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		if(count($iab_categories) > 0)
		{
			$values_sql = "";
			$binding_array = array();
			foreach($iab_categories as $category)
			{
				if($values_sql !== "")
				{
					$values_sql .= ",";
				}
				else
				{
					$values_sql .= "VALUES ";
				}
				$values_sql .= "(?, ?)";
				$binding_array[] = $mpq_id;
				$binding_array[] = $category;
			}
			$query = 'INSERT IGNORE INTO mpq_iab_categories_join (mpq_id, iab_category_id) '.$values_sql;
			$response = $this->db->query($query, $binding_array);
			return $response;
		}
		return true;
	}

	public function builder_save_proposal_data($mpq_id)
	{
		$select_query = "
			SELECT
				id,
				owner_user_id,
				industry_id
			FROM
				mpq_sessions_and_submissions
			WHERE
				id = ?";
		$select_response = $this->db->query($select_query, $mpq_id);
		if($select_response->num_rows() > 0)
		{
			$mpq_data = $select_response->row_array();
		}
		else
		{
			return false;
		}

		$prop_gen_prop_data_response = $this->save_prop_gen_prop_data($mpq_id, $mpq_data['owner_user_id'], 'queued-auto', $mpq_data['industry_id']);

		return $prop_gen_prop_data_response;

	}

	public function save_prop_gen_prop_data($mpq_id, $rep_id, $status = "queued-auto", $industry_id)
	{
		$prop_id = false;
		$select_query = "SELECT prop_id FROM prop_gen_prop_data WHERE source_mpq = ? ORDER BY date_modified DESC LIMIT 1";
		$select_response = $this->db->query($select_query, $mpq_id);
		if($select_response->num_rows() > 0)
		{
			$prop_id = $select_response->row()->prop_id;
			$query = "
				UPDATE 
					prop_gen_prop_data
				SET
					process_status = ?,
					rep_id = ?,
					industry_id = ?,
					auto_proposal_email_notes = ?
				WHERE
					prop_id = ?";
			$bindings = array('queued-auto', $rep_id, $industry_id, date('Y-m-d H:i:s').",queued-auto\n", $prop_id);
			$response = $this->db->query($query, $bindings);

		}
		else
		{
			$query = "
				INSERT INTO prop_gen_prop_data
					(prop_name, source_mpq, rep_id, process_status, industry_id, auto_proposal_email_notes)
				VALUES
					(?, ?, ?, ?, ?, ?)";
			$bindings = array(
				'create from mpq '.$mpq_id,
				$mpq_id,
			    $rep_id,
				'queued-auto',
			    $industry_id,
				date('Y-m-d H:i:s').",queued-auto\n");
			$response = $this->db->query($query, $bindings);
			$prop_id = $this->db->insert_id();

		}

		if($response)
		{
			return $prop_id;
		}
		return $response;
	}

	public function builder_save_location_and_product_data($mpq_id, $product_object, $prop_id)
	{
		$regions = array();
		$demographics = array();

		$this->mpq->get_regions_and_demographics_from_mpq($regions, $demographics, $mpq_id);
		$lap_ids_array = array();

		$delete_query = "DELETE FROM prop_gen_sessions WHERE source_mpq = ?";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		$prop_gen_success = true;
		foreach($regions as $region_index => $region_array)
		{
			$region = json_encode($region_array);
			$population = 0;
			$demo_population = 0;
			$internet_average = 0.5;

			$this->map->calculate_population_based_on_selected_demographics($population, $demo_population, $internet_average, $region_array, $demographics);

			$mps_query = "INSERT INTO media_plan_sessions
				(
					session_id,
					notes,
					region_data,
					demographic_data,
					selected_iab_categories,
					population,
					demo_population,
					internet_average,
					rf_geo_coverage,
					rf_gamma,
					rf_ip_accuracy,
					rf_demo_coverage,
					source_mpq
				)
				SELECT
					?,
					?,
					?,
					mpq.demographic_data,
					mpq.selected_iab_categories,
					?,
					?,
					?,
					?,
					?,

					?,
					?,
					?
				FROM mpq_sessions_and_submissions AS mpq
				WHERE
					mpq.id = ?";
			$k_rf_geo_coverage = 0.87;
			$k_rf_gamma = 0.30;
			$k_rf_ip_accuracy = 0.99;
			$k_rf_demo_coverage = 0.87;
			$mps_bindings = array(
				md5(uniqid(mt_rand().'', true)),
				'place holder session for lap_id creation by mpq #'.$mpq_id,
				$region,
				$population,
				$demo_population,
				$internet_average,
				$k_rf_geo_coverage,
				$k_rf_gamma,
				$k_rf_ip_accuracy,
				$k_rf_demo_coverage,
				$mpq_id,
				$mpq_id);

			$mps_response = $this->db->query($mps_query, $mps_bindings);
			$lap_id = $this->db->insert_id();
			$lap_ids_array[$region_index] = $lap_id;
			$pgs_query = "INSERT INTO prop_gen_sessions
					(
					region_data,
					demographic_data,
					selected_iab_categories,
					lap_id,
					plan_name,

					advertiser,
					notes,
					recommended_impressions,
					price_notes,
					population,

					demo_population,
					site_array,
					has_retargeting,
					rf_geo_coverage,
					rf_gamma,

					rf_ip_accuracy,
					rf_demo_coverage,
					internet_average,
					owner_id,
					date_created,

					source_mpq
					)
				SELECT
					?,
					mpq.demographic_data,
					mpq.selected_iab_categories,
					?,
					?,

					mpq.advertiser_name,
					?,
					?,
					?,
					?,

					?,
					?,
					?,
					?,
					?,

					?,
					?,
					?,
					mpq.owner_user_id,
					?,

					?
				FROM mpq_sessions_and_submissions AS mpq
				WHERE
					mpq.id = ?";
			$pgs_bindings = array(
				$region,
				$lap_id,
				"Lap " . $region_index . " from MPQ (builder) ".$mpq_id,
				"notes",
				0,
				"price notes",
				$population,
				$demo_population,
				json_encode(array()),
				1,
				$k_rf_geo_coverage,
				$k_rf_gamma,
				$k_rf_ip_accuracy,
				$k_rf_demo_coverage,
				$internet_average,
				date("Y-m-d"),
				$mpq_id,
				$mpq_id
				);
			$pgs_response = $this->db->query($pgs_query, $pgs_bindings);
			$prop_gen_success = $prop_gen_success && $pgs_response;
		}

		$products_response = $this->save_products($mpq_id, $product_object, $regions, $lap_ids_array, $prop_id);
		return $products_response && $prop_gen_success;
	}

	public function save_products($mpq_id, $product_object, $region_data, $lap_ids_array, $prop_id)
	{
		$delete_query = "DELETE FROM cp_submitted_products WHERE mpq_id = ?";
		$delete_response = $this->db->query($delete_query, $mpq_id);
		if(!$delete_response)
		{
			return false;
		}
		$sql_value_string = "";
		$bindings = array();

		$region_count = count($region_data);
		if($region_count > 1)
		{
			$product_ids = array_keys($product_object);
			$geo_dependent_response = $this->mpq->get_geo_dependent_and_type_by_product_ids($product_ids);
			if(empty($geo_dependent_response) or count($geo_dependent_response) < count(array_unique($product_ids)))
			{
				return false;
			}

			$total_population = 0;
 			foreach($region_data as $key => $region)
			{
				$population_result = $this->map->get_demographics_from_region_array($region['ids']);
				$population = intval($population_result['region_population']);
				if($population !== false)
				{
					$region_data[$key]['region_population'] = $population;
					$total_population += $population;
				}
			}

			foreach($product_object as $product_id => $product)
			{
				if($geo_dependent_response[$product_id]['geo_dependent'] == 0)
				{
					$option_id = 0;
					foreach($product as $fake_option_id => $option)
					{
						if($sql_value_string !== "")
						{
							$sql_value_string .= ", ";
						}
						$sql_value_string .= "(?,?,?,?,?,?,?)";
						$bindings[] = $mpq_id;
						$bindings[] = $prop_id;
						$bindings[] = $option_id;
						$bindings[] = $product_id;
						$bindings[] = $lap_ids_array[0];
						$bindings[] = json_encode($option);
						$bindings[] = 0;
						$option_id++;
					}
				}
				else
				{
					$option_id = 0;
					foreach($product as $fake_option_id => $option)
					{
						$temp_option = $option;
						if(array_key_exists('budget_allocation', $option) && $option['budget_allocation'] == 'per_pop')
						{
							$inventory_by_location = $this->mpq->calculate_geofencing_max_inventory($mpq_id, null, true);
							foreach($region_data as $key => $region)
							{
								$temp_option = $option;
								$pop_share_percent = intval($region['region_population']) / $total_population;
								$location_inventory = isset($inventory_by_location[$key]) ? intval($inventory_by_location[$key]) : 0;
								$temp_option['raw_unit'] = intval($option['unit']);

								if(array_key_exists('geofence_unit', $temp_option))
								{
									$temp_option['geofence_unit'] = intval($temp_option['geofence_unit']) * (boolval($location_inventory && $location_inventory > 0) ? 1 : 0);
									$temp_option['unit'] = ($temp_option['raw_unit'] * $pop_share_percent);
								}
								else
								{
									$temp_option['unit'] = $temp_option['raw_unit'] * $pop_share_percent;
								}

								if($sql_value_string !== "")
								{
									$sql_value_string .= ", ";
								}
								$sql_value_string .= "(?,?,?,?,?,?,?)";
								$bindings[] = $mpq_id;
								$bindings[] = $prop_id;
								$bindings[] = $option_id;
								$bindings[] = $product_id;
								$bindings[] = $lap_ids_array[$key];
								$bindings[] = json_encode($temp_option);
								$bindings[] = $key;
							}
						}
						else // fixed
						{
							$temp_total = round($option['unit'] / $region_count, 11); //round to 11 to eliminate repeating number precision since we're inserting to mysql as a string
							$first_unit_total = $option['unit'];
							for($i=1; $i < $region_count; $i++)
							{
								$first_unit_total -= $temp_total; //subtract until you get the remainder so when we add them up again it will equal the sum total
							}
							for($i = 0; $i < $region_count; $i++)
							{
								$submitted_data = $option;
								if($i == 0)
								{
									$submitted_data['unit'] = $first_unit_total;
								}
								else
								{
									$submitted_data['unit'] = $option['unit'] / $region_count;
								}

								$temp_option['raw_unit'] = $temp_option['unit'] / $region_count;
								if(array_key_exists('geofence_unit', $temp_option))
								{
									$location_inventory = isset($inventory_by_location[$key]) ? intval($inventory_by_location[$key]) : 0;
									$temp_option['geofence_unit'] = intval($temp_option['geofence_unit']) * intval(boolval($location_inventory && $location_inventory > 0));
									$temp_option['unit'] = $temp_option['raw_unit'] - $temp_option['geofence_unit'];
								}
								else
								{
									$temp_option['unit'] = $temp_option['raw_unit'] / $region_count;
								}

								if($sql_value_string !== "")
								{
									$sql_value_string .= ", ";
								}
								$sql_value_string .= "(?,?,?,?,?,?,?)";
								$bindings[] = $mpq_id;
								$bindings[] = $prop_id;
								$bindings[] = $option_id;
								$bindings[] = $product_id;
								$bindings[] = $lap_ids_array[$i];
								$bindings[] = json_encode($submitted_data);
								$bindings[] = $i;
							}
						}
						$option_id++;
					}
				}
			}
		}
		else
		{
			foreach($product_object as $product_id => $product)
			{
				$option_id = 0;
				foreach($product as $fake_option_id => $option)
				{
					if($sql_value_string !== "")
					{
						$sql_value_string .= ", ";
					}
					$sql_value_string .= "(?,?,?,?,?,?,?)";
					$bindings[] = $mpq_id;
					$bindings[] = $prop_id;
					$bindings[] = $option_id;
					$bindings[] = $product_id;
					$bindings[] = $lap_ids_array[0];
					$bindings[] = json_encode($option);
					$bindings[] = 0;
					$option_id++;
				}
			}
		}

		if(count($bindings) == 0)
		{
			return false;
		}

		$query = "
			INSERT INTO
				cp_submitted_products
					(mpq_id, proposal_id, option_id, product_id, frq_prop_gen_lap_id, submitted_values, region_data_index)
				VALUES
					".$sql_value_string;
		$response = $this->db->query($query, $bindings);
		return $response;

	}

	public function get_proposal_id_by_mpq_id($mpq_id)
	{
		$query = "SELECT prop_id FROM prop_gen_prop_data WHERE source_mpq = ? ORDER BY date_modified DESC LIMIT 1";
		$response = $this->db->query($query, $mpq_id);

		if($response->num_rows() > 0)
		{
			return $response->row()->prop_id;
		}
		return false;
	}

	public function builder_save_mpq_options($mpq_id, $option_data, $discount_text)
	{
		$delete_query = "
			DELETE FROM
				mpq_options
			WHERE
				mpq_id = ?";
		$delete_response = $this->db->query($delete_query, $mpq_id);

		$binding_sql = "";
		$binding_array = array();
		foreach($option_data as $id => $option)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "(?,?,?,?,?,?,?,?)";
			$binding_array[] = $mpq_id;
			$binding_array[] = $id;
			$binding_array[] = $option['name'];
			$binding_array[] = $option['discount'];
			$binding_array[] = $option['term'];
			$binding_array[] = $option['duration'];
			$binding_array[] = $discount_text;
			$binding_array[] = $option['grand_total'];
		}

		$query = "
			INSERT INTO
				mpq_options
					(mpq_id, option_id, option_name, discount, term, duration, discount_name, grand_total_dollars)
				VALUES
					".$binding_sql."
			ON DUPLICATE KEY UPDATE
				option_name = VALUES(option_name),
				discount = VALUES(discount),
				term = VALUES(term),
				duration = VALUES(duration),
				discount_name = VALUES(discount_name),
				grand_total_dollars = VALUES(grand_total_dollars)
		";
		$response = $this->db->query($query, $binding_array);
		if($response !== false)
		{
			return true;
		}
		return false;
	}

	public function builder_save_prop_gen_options($prop_id, $option_data, $discount_text)
	{
		$delete_query = "DELETE FROM prop_gen_option_prop_join WHERE prop_id = ?";
		$delete_result = $this->db->query($delete_query, $prop_id);

		$bindings = array();
		$binding_sql = "";
		foreach($option_data as $id => $option)
		{
			if($binding_sql !== "")
			{
				$binding_sql .= ", ";
			}
			$binding_sql .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
			$bindings[] = $id;
			$bindings[] = $prop_id;
			$bindings[] = $option['name'];
			$bindings[] = 0;
			$bindings[] = 0;
			$bindings[] = 0;
			$bindings[] = '';
			$bindings[] = 0;
			$bindings[] = 0;
			$bindings[] = 0;
			$bindings[] = $option['discount'];
			$bindings[] = $discount_text;
			$bindings[] = 0;
			$bindings[] = 0;
			$bindings[] = $option['term'];
			$bindings[] = $option['duration'];
		}
		$query = "
			INSERT INTO prop_gen_option_prop_join
				(
					option_id,
					prop_id,
					option_name,
					one_time_cost_raw,
					one_time_abs_discount,
					one_time_percent_discount,
					one_time_discount_description,
					one_time_cost,
					monthly_cost_raw,
					monthly_abs_discount,
					monthly_percent_discount,
					monthly_discount_description,
					monthly_cost,
					cost_by_campaign,
					term,
					duration
				)
			VALUES ".$binding_sql."
			ON DUPLICATE KEY UPDATE
				option_name = VALUES(option_name),
				one_time_cost_raw = VALUES(one_time_cost_raw),
				one_time_abs_discount = VALUES(one_time_abs_discount),
				one_time_percent_discount = VALUES(one_time_percent_discount),
				one_time_discount_description = VALUES(one_time_discount_description),
				one_time_cost = VALUES(one_time_cost),
				monthly_cost_raw = VALUES(monthly_cost_raw),
				monthly_abs_discount = VALUES(monthly_abs_discount),
				monthly_percent_discount = VALUES(monthly_percent_discount),
				monthly_discount_description = VALUES(monthly_discount_description),
				monthly_cost = VALUES(monthly_cost),
				cost_by_campaign = VALUES(cost_by_campaign),
				term = VALUES(term),
				duration = VALUES(duration)";

		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function builder_set_mpq_submitted($mpq_id)
	{
		$query = "UPDATE mpq_sessions_and_submissions SET is_submitted = 1 WHERE id = ?";
		$response = $this->db->query($query, $mpq_id);
		return $response;
	}

	public function set_proposal_completion_status($mpq_id, $status)
	{
		$bindings = array(
			$mpq_id,
			($status['is_gate_cleared'] == "true"),
			($status['is_targets_cleared'] == "true"),
			($status['is_budget_cleared'] == "true"),
			($status['is_builder_cleared'] == "true")
			);
		$query = "
			INSERT INTO proposal_completion_status
				(mpq_id, is_gate_complete, is_targeting_complete, is_budget_complete, is_builder_complete)
			VALUES
				(?,?,?,?,?)
			ON DUPLICATE KEY UPDATE
			is_gate_complete = VALUES(is_gate_complete),
			is_targeting_complete = VALUES(is_targeting_complete),
			is_budget_complete = VALUES(is_budget_complete),
			is_builder_complete = VALUES(is_builder_complete)";
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_proposal_completion_status($mpq_id)
	{
		$query = "
			SELECT
				is_gate_complete AS is_gate_cleared,
				is_targeting_complete AS is_targets_cleared,
				is_budget_complete AS is_budget_cleared,
				is_builder_complete AS is_builder_cleared
			FROM
				proposal_completion_status
			WHERE
				mpq_id = ?";
		$response = $this->db->query($query, $mpq_id);
		if($response->num_rows() === 0)
		{
			$return_array = array(
				'is_gate_cleared' => false,
				'is_targets_cleared' => false,
				'is_budget_cleared' => false,
				'is_builder_cleared' => false
				);
			$this->set_proposal_completion_status($mpq_id, $return_array);
		}
		else
		{
			$return_array = $response->row_array();
		}
		return $return_array;
	}

	public function delete_non_overview_snapshots($proposal_id, $also_delete_overview = false)
	{
		$overview_sql = "AND lap_id IS NOT NULL";
		if($also_delete_overview === true)
		{
			$overview_sql = "";
		}

		$query = "DELETE FROM prop_gen_snapshot_data WHERE prop_id = ? ".$overview_sql;
		$response = $this->db->query($query, $proposal_id);
		return $response;
	}

	public function reset_queued_status($prop_id)
	{
		$query = "UPDATE prop_gen_prop_data SET process_status = 'queued-auto' WHERE prop_id = ?";
		$response = $this->db->query($query, $prop_id);
		return $response;
	}
}
