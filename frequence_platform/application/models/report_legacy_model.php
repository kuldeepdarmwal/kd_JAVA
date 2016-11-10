<?php

class report_legacy_model extends CI_Model 
{
	private $format_whole_number_callable;
	private $format_percent_callable;
	private $format_string_callable;

	public function __construct()
	{
		$this->load->database();

		$this->load->helper(array('report_v2_model_helper', 'url'));

		$this->load->model('tmpi_model');

		report_campaign::init_maps();

		$this->format_whole_number_callable = array($this, 'format_whole_number');
		$this->format_percent_callable = array($this, 'format_percent');
		$this->format_string_callable = array($this, 'format_string');
	}

	public function get_subfeature_access_permissions(
		$user_id
	)
	{
		$result = false;

		$sql = "
			SELECT
				ad_sizes_viewable,
				screenshots_viewable,
				beta_report_engagements_viewable,
				placements_viewable
			FROM 
				users 
			WHERE id = ? 
			LIMIT 1
		";

		$response = $this->db->query($sql, $user_id);
		if($response->num_rows() == 1)
		{
			$row = $response->row_array();

			$result = array(
				'are_ad_sizes_accessible' => $row['ad_sizes_viewable'],
				'are_ad_interactions_accessible' => $row['beta_report_engagements_viewable'],
				'are_placements_accessible' => $row['placements_viewable'],
				'are_screenshots_accessible' => $row['screenshots_viewable']
			);
		
			// TODO: Get these in 1 query instead of 3
			$result['are_view_throughs_accessible'] = $this->vl_platform->get_access_permission_redirect('reports_view_throughs') == '' ? 1 : 0;
			$result['is_pre_roll_accessible'] = $this->vl_platform->get_access_permission_redirect('reports_pre_roll') == '' ? 1 : 0;
			$result['is_tmpi_accessible'] = $this->vl_platform->get_access_permission_redirect('reports_tmpi') == '';
			$result['are_display_creatives_accessible'] = $this->vl_platform->get_access_permission_redirect('reports_creatives') == '';

			// When only view throughs are accessible, but not ad interactions or tmpi, the engagments mirror Visits. 
			// But we're keeping it accessible becasue Dave and Tom want to always have this to refer to.
			$result['are_engagements_accessible'] = $result['are_ad_interactions_accessible'] ||
				$result['is_tmpi_accessible'] ||
				$result['are_view_throughs_accessible']
			;
		}
		else
		{
			// TMPi-TODO: Something other than exception?
			throw new Exception("Trying to get report access permissions for non-existent user. (#1741875)");
		}

		return $result;  
	}

	private function get_ad_interactions_query_elements(
		&$ad_interactions_from_sql,
		&$ad_interactions_where_sql,
		&$ad_interactions_hovers_total_sql,
		&$ad_interactions_video_plays_total_sql,
		&$bindings_array,
		$advertiser_id,
		$campaign_ids
	)
	{
		$hover_ad_interactions_count_id = 5;
		$video_click_play_ad_interactions_id = 6;
		$video_hover_play_ad_interactions_id = 1;
		
		$hover_count_minimum = 1;

		$ad_interactions_id_set = '('.
			$hover_ad_interactions_count_id.','.
			$video_click_play_ad_interactions_id.','.
			$video_hover_play_ad_interactions_id.')';

		$ad_interactions_hovers_total_sql = '
			SUM(IF(er.engagement_type = '.
				$hover_ad_interactions_count_id.
				' AND er.value >= '.$hover_count_minimum.', er.total, 0))
		'; // AS hovers
		$ad_interactions_video_plays_total_sql = '
			SUM(IF(er.engagement_type = '.
				$video_click_play_ad_interactions_id.
				' OR er.engagement_type = '.
				$video_hover_play_ad_interactions_id.
				', er.total, 0))
		'; // AS video_plays


		$campaign_id_string = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$bindings_array[] = $advertiser_id;
		$bindings_array = array_merge($bindings_array, $campaign_ids);

		$ad_interactions_from_sql = "
			Campaigns AS cmp
			";
		$ad_interactions_where_sql = "
			cmp.business_id = ? AND
			ver.campaign_id IN $campaign_id_string AND
			er.engagement_type IN $ad_interactions_id_set AND
		";
	}

	public function get_advertiser_name($advertiser_id)
	{
		$sql = "
			SELECT
				Name
			FROM
				Advertisers
			WHERE
				id = ?
		";

		$response = $this->db->query($sql, $advertiser_id);

		$name = "";

		if($response->num_rows() > 0)
		{
			$name = $response->row()->Name;
		}

		return $name;
	}

	private function get_view_through_sql_pieces(
		&$view_throughs_outer_select_sql,
		&$view_throughs_inner_non_retargeting_select_sql,
		&$view_throughs_inner_retargeting_select_sql,
		$are_view_throughs_accessible,
		$build_data_type
	)
	{
		$view_throughs_outer_select_sql = "";
		$view_throughs_inner_non_retargeting_select_sql = "";
		$view_throughs_inner_retargeting_select_sql = "";

		if($are_view_throughs_accessible)
		{
			$view_throughs_outer_select_sql = "
				,
				SUM(r_view_throughs) + SUM(nr_view_throughs) AS view_throughs
			";

			if($build_data_type !== k_build_table_type::csv)
			{
				$view_throughs_outer_select_sql .= "
					,
					100 * (SUM(r_view_throughs) + SUM(nr_view_throughs)) / (SUM(r_impressions) + SUM(nr_impressions)) AS view_through_rate
				";
			}

			$view_throughs_inner_non_retargeting_select_sql = "
				,
				SUM(b.post_impression_conversion_1) + SUM(b.post_impression_conversion_2) + SUM(b.post_impression_conversion_3) +
					SUM(b.post_impression_conversion_4) + SUM(b.post_impression_conversion_5) + SUM(b.post_impression_conversion_6) AS nr_view_throughs,
				0 AS r_view_throughs
			";
			$view_throughs_inner_retargeting_select_sql = "
				,
				0 AS nr_view_throughs,
				SUM(b.post_impression_conversion_1) + SUM(b.post_impression_conversion_2) + SUM(b.post_impression_conversion_3) +
					SUM(b.post_impression_conversion_4) + SUM(b.post_impression_conversion_5) + SUM(b.post_impression_conversion_6) AS r_view_throughs
			";
		}
	}

	private function get_placements_sorting()
	{
		return array(new report_table_starting_column_sort(2, 'desc'));
	}

	private function get_pre_roll_filter_sql($data_set)
	{
		$data_filter = "";
		switch($data_set)
		{
			case k_impressions_clicks_data_set::pre_roll_only:
				$data_filter = 'AND (a.target_type = "Pre-Roll" OR a.target_type = "RTG Pre-Roll")';
				break;
			case k_impressions_clicks_data_set::non_pre_roll:
				$data_filter = 'AND ((a.target_type != "Pre-Roll" AND a.target_type != "RTG Pre-Roll") OR a.target_type IS NULL)';
				break;
			case k_impressions_clicks_data_set::all:
				$data_filter = '';
				break;
			default:
				throw new Exception("Unknown impression and clicks data set: ".$data_set);
				break;
		}

		return $data_filter;
	}

	private function get_placements_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$data_set,
		$sorting = null,
		$mode = null
	)
	{
		$campaign_ids = $this->get_frequence_campaign_ids_with_pre_roll_filter($data_set, $campaign_set);
		if ($mode == 'OVERVIEW' && $this->make_bindings_sql_from_array($campaign_ids) == "")
			return null;
		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));		

		$sort_sql = $this->get_sorting_sql($sorting);

		$data_filter = $this->get_pre_roll_filter_sql($data_set);
		
		// View Through data not used for placements because data from The Trade Desk (TTD) HD Report gereralizes to "All other sites" too heavily.
		// Not enough good data left over.
		$view_throughs_outer_select_sql = "";
		$view_throughs_inner_non_retargeting_select_sql = "";
		$view_throughs_inner_retargeting_select_sql = "";
		
		$query = '
			SELECT
				place AS place,
				SUM(nr_impressions) + SUM(r_impressions) AS impressions,
				SUM(nr_clicks) + SUM(r_clicks) AS clicks,
				100 * (SUM(nr_clicks) + SUM(r_clicks)) / (SUM(nr_impressions) + SUM(r_impressions)) AS click_rate
				'.$view_throughs_outer_select_sql.'
			FROM
			(
				(
					SELECT
						b.Base_Site AS place,
						SUM(b.Impressions) AS nr_impressions,
						SUM(b.Clicks) AS nr_clicks,
						0 AS r_impressions,
						0 AS r_clicks
						'.$view_throughs_inner_non_retargeting_select_sql.'
					FROM (AdGroups a JOIN SiteRecords b ON (a.ID = b.AdGroupID)) 
						JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id = ? AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN '.$campaigns_sql.' AND
						a.IsRetargeting = 0
						'.$data_filter.'
					GROUP BY place
				)
				UNION ALL
				(
					SELECT
						b.Base_Site AS place,
						0 AS nr_impressions,
						0 AS nr_clicks,
						SUM(b.Impressions) AS r_impressions,
						SUM(b.Clicks) AS r_clicks
						'.$view_throughs_inner_retargeting_select_sql.'
					FROM (AdGroups a JOIN SiteRecords b ON (a.ID = b.AdGroupID)) 
						JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id = ? AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN '.$campaigns_sql.' AND
						a.IsRetargeting = 1
						'.$data_filter.'
					GROUP BY place
				)
			) AS u
			GROUP BY place
			'.$sort_sql.'
		';

		$advertiser_bindings = array(
			$advertiser_id
		);

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$bindings = array_merge(
			$advertiser_bindings,
			$date_bindings,
			$campaign_ids,

			$advertiser_bindings,
			$date_bindings,
			$campaign_ids
		);
		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_placements_data_no_pre_roll_yes_display(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$mode=null
	)
	{
		return $this->get_placements_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			k_impressions_clicks_data_set::non_pre_roll,
			$mode
		);
	}

	public function get_placements_data_yes_pre_roll_no_display(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		return $this->get_placements_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			k_impressions_clicks_data_set::pre_roll_only
		);
	}

	public function get_placements_data_yes_pre_roll_yes_display(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$mode=null
	)
	{
		return $this->get_placements_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			k_impressions_clicks_data_set::all,
			$mode
		);
	}

	private function get_placements_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$data_set,
		$mode=null
	)
	{	

		$table_starting_sorts = $this->get_placements_sorting();
		if ($mode =='OVERVIEW')
			$table_starting_sorts = array(new report_table_starting_column_sort(1, 'desc'));

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$response = $this->get_placements_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$data_set,
			$core_sorting,
			$mode
		);

		$column_class_prefix = 'table_cell_tareted_display_placements';

		$table_headers = array(
			array(
				"Domain", 
				new report_table_column_format(
					$column_class_prefix.'_place', 
					'asc', 
					$this->format_string_callable
				),
				"Core domain where ad was placed"
			),
			array(
				"Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Ads shown to users"
			),
			array(
				"Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Clicks on ads leading to a visit to conversion pages"
			),
			array(
				"Click Rate", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of impressions which lead to a click (clicks / impressions)"
			)
		);

		// Placements don't show View Throughs because data is not good enough
		/* 
		if($subfeature_access_permissions['are_view_throughs_accessible'])
		{
			$view_through_table_headers = array(
				array(
					"View Throughs", 
					new report_table_column_format(
						$column_class_prefix.'_view_throughs', 
						'desc', 
						$this->format_whole_number_callable
					),
					"Visits to conversion page by impressioned users (who did not click)"
				),
				array(
					"View Through Rate", 
					new report_table_column_format(
						$column_class_prefix.'_view_throughs_rate', 
						'desc', 
						$this->format_percent_callable
					),
					"Percent of impressions which lead to a view through (view throughs / impressions)"
				)
			);

			$table_headers = array_merge($table_headers, $view_through_table_headers);
		}
		*/

		$result = false;
		if ($mode == 'OVERVIEW' && $response == null)
			return $result;

		$table_content = $response->result_array();

		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function format_whole_number($number)
	{
		return number_format($number);
	}

	private function format_percent($number)
	{
		return number_format($number, 2).'%';
	}

	private function format_string($string)
	{
		return $string;
	}

	private function get_geography_sorting()
	{
		return array(new report_table_starting_column_sort(3, 'desc'));
	}

	private function get_frequence_campaign_ids_with_pre_roll_filter($data_set, $campaign_set)
	{
		$campaign_ids = array();

		switch($data_set)
		{
			case k_impressions_clicks_data_set::pre_roll_only:
				$campaign_ids = $campaign_set->get_campaign_ids(
					report_campaign_organization::frequence,
					report_campaign_type::frequence_pre_roll
				);
				break;
			case k_impressions_clicks_data_set::non_pre_roll:
				$campaign_ids = $campaign_set->get_campaign_ids(
					report_campaign_organization::frequence,
					report_campaign_type::frequence_display
				);
				break;
			case k_impressions_clicks_data_set::all:
				$campaign_ids = $campaign_set->get_campaign_ids(
					report_campaign_organization::frequence
				);
				break;
			default:
				throw new Exception("Unknown data set: ".$data_set);
				break;
		}

		return $campaign_ids;
	}

	public function get_display_creatives_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$response = $this->get_display_creatives_versions($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date, "REPORTS");

		$display_creatives_list = $response->result_array();

		$display_creatives_data_set = array(
			'display_function' => 'build_display_creatives_table',
			'display_selector' => '#display_creatives_table_wrapper',
			'display_data'     => $display_creatives_list
		);

		$data_sets = array(
			$display_creatives_data_set
		);

		$html_scaffolding = '
			<div id="display_creatives_table_wrapper" class="display_creatives" style="">
			</div>
		';

		$result = array(
			'subproduct_html_scaffolding' => $html_scaffolding,
			'subproduct_data_sets' => $data_sets
		);

		return $result;
	}

	private function get_display_creatives_versions(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$mode
	)
	{

		$advertiser_id_bindings = array($advertiser_id);
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);
		
		if ($mode == 'OVERVIEW' && empty($campaign_ids))
		{
			return null;
		}
		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));		

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);		

		$ad_interactions_from_sql = '';
		$ad_interactions_where_sql = '';
		$ad_interactions_hovers_total_sql = '';
		$ad_interactions_video_plays_total_sql = '';

		$ad_interactions_bindings = array();
		$this->get_ad_interactions_query_elements(
			$ad_interactions_from_sql,
			$ad_interactions_where_sql,
			$ad_interactions_hovers_total_sql,
			$ad_interactions_video_plays_total_sql,
			$ad_interactions_bindings,
			$advertiser_id,
			$campaign_ids
		);



		$bindings = array_merge(
			$advertiser_id_bindings,
			$campaign_ids,
			$campaign_ids,
			$date_bindings,

			$date_bindings,
			$advertiser_id_bindings,
			$campaign_ids,					
			$ad_interactions_bindings,
			$date_bindings,

			$advertiser_id_bindings,
			$campaign_ids,
			$campaign_ids,
			$date_bindings,
			$advertiser_id_bindings,
			$campaign_ids,					

			$date_bindings,
			$ad_interactions_bindings,
			$date_bindings
		);

		$query = "
			SELECT
				versions.version_id AS cup_version_id,
				CAST((SUM(versions.engagements) + SUM(versions.clicks)) / SUM(versions.impressions) AS DECIMAL(10,8)) AS interaction_rate";

		if ($mode == "OVERVIEW")
		{
			$query .= " , SUM(versions.impressions) AS impressions,
						SUM(versions.engagements) AS engagements
			";
		}		
		
		$query .= "
			FROM
				(SELECT
					ver.id AS version_id,
					SUM(rcr.impressions) AS impressions,
					SUM(rcr.clicks) AS clicks,
					0 AS engagements
				FROM
					report_creative_records AS rcr
					JOIN AdGroups AS adg
						ON (rcr.adgroup_id = adg.ID)
					JOIN Campaigns AS cmp
						ON (adg.campaign_id = cmp.id)
					JOIN cup_creatives AS cre
						ON (rcr.creative_id = cre.id)
					JOIN cup_versions AS ver
						ON (cre.version_id = ver.id)
				WHERE
					cmp.business_id = ?
					AND adg.campaign_id IN ".$campaigns_sql."
					AND ver.campaign_id IN ".$campaigns_sql."
					AND rcr.date BETWEEN ? AND ?
					AND ver.source_id IS NULL
				GROUP BY
					version_id
			UNION ALL
				SELECT
					ver.id AS version_id,
					0 AS impressions, 
					0 AS clicks,
					".$ad_interactions_hovers_total_sql." + ".$ad_interactions_video_plays_total_sql." AS engagements
				FROM
					".$ad_interactions_from_sql."
					JOIN cup_versions AS ver 
						ON (cmp.id = ver.campaign_id)
					JOIN cup_creatives AS cre 
						ON (ver.id = cre.version_id)
					JOIN engagement_records AS er
						ON (cre.id = er.creative_id)
					JOIN (
							SELECT 
								rcr.creative_id,
								rcr.date
							FROM
								report_creative_records AS rcr
								JOIN cup_creatives AS cre
									ON (rcr.creative_id = cre.id)
								JOIN cup_versions AS ver
									ON (cre.version_id = ver.id)
								JOIN Campaigns AS cmp
									ON (ver.campaign_id = cmp.id)
							WHERE 
								rcr.date BETWEEN ? AND ?
								AND cmp.business_id = ?
								AND ver.campaign_id IN ".$campaigns_sql."						
							GROUP BY
								rcr.creative_id,
								rcr.date
						) AS rcr_s
						ON (cre.id = rcr_s.creative_id AND er.date = rcr_s.date)	
				WHERE
					".$ad_interactions_where_sql."
					er.date BETWEEN ? AND ?
					AND (ver.source_id IS NULL)
				GROUP BY
				version_id
			UNION ALL
				SELECT
					ver2.id AS version_id,
					SUM(rcr.impressions) AS impressions,
					SUM(rcr.clicks) AS clicks,
					0 AS engagements
				FROM
					report_creative_records AS rcr
					JOIN AdGroups AS adg
						ON (rcr.adgroup_id = adg.ID)
					JOIN Campaigns AS cmp
						ON (adg.campaign_id = cmp.id)	
					JOIN cup_creatives AS cre
						ON (rcr.creative_id = cre.id)
					JOIN cup_versions AS ver
						ON (cre.version_id = ver.id)
					JOIN cup_versions AS ver2
						ON (ver.source_id = ver2.id)
				WHERE
					cmp.business_id = ?
					AND adg.campaign_id IN ".$campaigns_sql."
					AND ver.campaign_id IN ".$campaigns_sql."
					AND rcr.date BETWEEN ? AND ?
					AND ver.source_id IS NOT NULL
				GROUP BY 
					version_id
			UNION ALL
				SELECT
					ver.source_id AS version_id,
					0 AS impressions, 
					0 AS clicks,
					".$ad_interactions_hovers_total_sql." + ".$ad_interactions_video_plays_total_sql." AS engagements
				FROM
					".$ad_interactions_from_sql."
					JOIN cup_versions AS ver 
						ON (cmp.id = ver.campaign_id)						
					JOIN cup_creatives AS cre 
						ON (ver.id = cre.version_id)
					JOIN engagement_records AS er
						ON (cre.id = er.creative_id)
					JOIN (
							SELECT 
								rcr.creative_id,
								rcr.date
							FROM
								report_creative_records AS rcr
								JOIN cup_creatives AS cre
									ON (rcr.creative_id = cre.id)
								JOIN cup_versions AS ver
									ON (cre.version_id = ver.id)
								JOIN Campaigns AS cmp
									ON (ver.campaign_id = cmp.id)
							WHERE 
								rcr.date BETWEEN ? AND ?
								AND cmp.business_id = ?
								AND ver.campaign_id IN ".$campaigns_sql."						
							GROUP BY
								rcr.creative_id,
								rcr.date
						) AS rcr_s
						ON (cre.id = rcr_s.creative_id AND er.date = rcr_s.date)							

				WHERE
					".$ad_interactions_where_sql."
					er.date BETWEEN ? AND ?
					AND (ver.source_id IS NOT NULL)
				GROUP BY
				version_id) AS versions
			GROUP BY
				versions.version_id HAVING SUM(versions.impressions) > 1000
			ORDER BY 
				interaction_rate DESC";

			if ($mode == "OVERVIEW")
			{
				$query .= " LIMIT 5";
			}			
		$response = $this->db->query($query, $bindings);

		return $response;
	}


	public function get_creative_messages(
		$advertiser_id,
		$campaigns,
		$start_date,
		$end_date,
		$cup_version_ids
	)
	{
		$version_ids = array();
		foreach($cup_version_ids as $version_id)
		{
			$version_ids[] = $version_id['cup_version_id'];
		}

		$report_data = $this->get_creative_message_report_data(
			$advertiser_id,
			$version_ids,
			$start_date,
			$end_date,
			$campaigns
		);

		$backup_images_array = $this->get_creative_backup_images_for_versions($version_ids);
		$backup_images = array();
		foreach($backup_images_array as $backup_image)
		{
			$backup_images[$backup_image['version_id']]['src'] = $backup_image['backup_image'];
			$size = explode('x', $backup_image['creative_size']);
			$backup_images[$backup_image['version_id']]['width'] = $size[0];
			$backup_images[$backup_image['version_id']]['height'] = $size[1]; 
		}

		$version_data_array = $report_data->result_array();
		$result = array();
		foreach($version_ids as $version_id)
		{
			$version_creative_data_set = array(
				'creative_data' => array(
					'cup_version_id' => $version_id,
					'ad_set_preview_url' => site_url().'crtv/get_gallery_adset/'.base64_encode(base64_encode(base64_encode($version_id))),
					'creative_thumbnail' => $backup_images[$version_id]['src'],
					'creative_width' => (int)$backup_images[$version_id]['width'],
					'creative_height' => (int)$backup_images[$version_id]['height'],
					'summary_values' => array(
						'impressions'=> 0,
						'engagements'=> 0,
						'engagement_rate'=> 0,
						'clicks' => 0,
						'click_rate' => 0
						),	
					'chart' => array()
				)
			);	
			$current = strtotime($start_date);
			$last = strtotime($end_date);
			while($current <= $last)
			{	
				$formatted_current_date = date('Y-m-d', $current);
				$version_creative_data_set['creative_data']['chart']['dates'][] = $formatted_current_date;
				$version_creative_data_set['creative_data']['chart']['impressions'][$formatted_current_date] =  0;
				$version_creative_data_set['creative_data']['chart']['clicks'][$formatted_current_date] =  0;
				$version_creative_data_set['creative_data']['chart']['engagements'][$formatted_current_date] =  0;
				$current = strtotime('+1 day', $current);
			}
			$result[$version_id] = $version_creative_data_set;							
		}

		$version_data_array[] = -1;
		$previous_version_id = null;
		foreach($version_data_array as $version_date_data)
		{

			if($previous_version_id != $version_date_data['version_id'] && $previous_version_id != null)
			{
				$previous_creative_data = $result[$previous_version_id]['creative_data'];
				$previous_creative_data['summary_values']['engagement_rate'] = number_format(
					100*$previous_creative_data['summary_values']['engagements']/$previous_creative_data['summary_values']['impressions'], 2)."%";
				$previous_creative_data['summary_values']['click_rate'] = number_format(
					100*$previous_creative_data['summary_values']['clicks']/$previous_creative_data['summary_values']['impressions'], 2)."%";

				$previous_creative_data['summary_values']['impressions'] = number_format($previous_creative_data['summary_values']['impressions']);
				$previous_creative_data['summary_values']['clicks'] = number_format($previous_creative_data['summary_values']['clicks']);
				$previous_creative_data['summary_values']['engagements'] = number_format($previous_creative_data['summary_values']['engagements']);
				$previous_creative_data['summary_values'] = array_values($previous_creative_data['summary_values']);
				
				$previous_creative_data['chart']['impressions'] = array_values($previous_creative_data['chart']['impressions']);
				$previous_creative_data['chart']['clicks'] = array_values($previous_creative_data['chart']['clicks']);
				$previous_creative_data['chart']['engagements'] = array_values($previous_creative_data['chart']['engagements']);
				$result[$previous_version_id]['creative_data'] = $previous_creative_data;
			}
			if($version_date_data != -1)
			{
				$creative_data = $result[$version_date_data['version_id']]['creative_data'];

				$creative_data['summary_values']['impressions'] += $version_date_data['impressions'];
				$creative_data['chart']['impressions'][$version_date_data['date']] = (int)$version_date_data['impressions'];

				$creative_data['summary_values']['engagements'] += $version_date_data['engagements'];
				$creative_data['chart']['engagements'][$version_date_data['date']] = (int)$version_date_data['engagements'];

				$creative_data['summary_values']['clicks'] += $version_date_data['clicks'];
				$creative_data['chart']['clicks'][$version_date_data['date']] = (int)$version_date_data['clicks'];

				$result[$version_date_data['version_id']]['creative_data'] = $creative_data;
				$previous_version_id = $version_date_data['version_id'];
			}
		}
		$result = array_values($result);
		return $result;
	}

	private function get_creative_message_report_data($advertiser_id, $version_ids, $start_date, $end_date, $campaigns)
	{

		$advertiser_id_bindings = array($advertiser_id);

		$campaign_ids = $campaigns->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);
		
		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($start_date));
		$end_date_search = date("Y-m-d", strtotime($end_date));	

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$version_sql = "(".$this->make_bindings_sql_from_array($version_ids).")";




		$ad_interactions_from_sql = '';
		$ad_interactions_where_sql = '';
		$ad_interactions_hovers_total_sql = '';
		$ad_interactions_video_plays_total_sql = '';

		$ad_interactions_bindings = array();
		$this->get_ad_interactions_query_elements(
			$ad_interactions_from_sql,
			$ad_interactions_where_sql,
			$ad_interactions_hovers_total_sql,
			$ad_interactions_video_plays_total_sql,
			$ad_interactions_bindings,
			$advertiser_id,
			$campaign_ids
		);
		$bindings = array_merge(
			$advertiser_id_bindings,
			$campaign_ids,
			$date_bindings,
			$version_ids,
			$version_ids,

			$ad_interactions_bindings,
			$date_bindings,
			$version_ids,
			$version_ids
		);

		$query = "
			SELECT 
				message_data.version_id,
				message_data.date,
				SUM(message_data.impressions) AS impressions,
				SUM(message_data.clicks) AS clicks,
				IF(SUM(message_data.impressions) > 0, SUM(message_data.engagements), 0) AS engagements
			FROM
				(SELECT
					COALESCE(ver.source_id, ver.id) AS version_id,
					rcr.date AS date,
					COALESCE(SUM(rcr.impressions), 0) AS impressions,
					COALESCE(SUM(rcr.clicks), 0) AS clicks,
					0 AS engagements
				FROM
					report_creative_records AS rcr
					JOIN AdGroups AS adg
						ON (rcr.adgroup_id = adg.ID)
					JOIN Campaigns AS cmp
						ON (adg.campaign_id = cmp.id)
					JOIN cup_creatives AS cre
						ON (rcr.creative_id = cre.id)
					JOIN cup_versions AS ver
						ON (cre.version_id = ver.id)
				WHERE
					cmp.business_id = ?
					AND adg.campaign_id IN ".$campaigns_sql."
					AND rcr.date BETWEEN ? AND ?
					AND (ver.id IN ".$version_sql." OR ver.source_id IN ".$version_sql.")
				GROUP BY
					version_id,
					rcr.date
				UNION ALL
				SELECT
					COALESCE(ver.source_id, ver.id) AS version_id,
					er.date AS date, 
					0 AS impressions, 
					0 AS clicks,
					".$ad_interactions_hovers_total_sql." + ".$ad_interactions_video_plays_total_sql." AS engagements
				FROM
					".$ad_interactions_from_sql."
					JOIN cup_versions AS ver 
						ON (cmp.id = ver.campaign_id)
					JOIN cup_creatives AS cre 
						ON (ver.id = cre.version_id)
					JOIN engagement_records AS er
						ON (cre.id = er.creative_id)
				WHERE
					".$ad_interactions_where_sql."
					er.date BETWEEN ? AND ?
					AND (ver.id IN ".$version_sql." OR ver.source_id IN ".$version_sql.")
				GROUP BY
				version_id, er.date) AS message_data
			GROUP BY
				message_data.version_id,
				message_data.date
			ORDER BY
				message_data.version_id,
				message_data.date";

		$response = $this->db->query($query, $bindings);

		return $response;

	}
	private function get_creative_backup_images_for_versions($version_ids)
	{
		$version_sql = "(".$this->make_bindings_sql_from_array($version_ids).")";

		$query = "
			SELECT
			creatives.*
			FROM
				(SELECT
					ver.id AS version_id,
					caa.ssl_uri AS backup_image,
					cre.size AS creative_size
				FROM
					cup_versions AS ver
					JOIN cup_creatives AS cre
						ON (ver.id = cre.version_id)
					JOIN cup_ad_assets AS caa
						ON (cre.id = caa.creative_id)
				WHERE
					caa.type = \"backup\"
					AND ver.id IN ".$version_sql."
					AND cre.size = \"300x250\"
				UNION
				SELECT
					ver.id AS version_id,
					caa.ssl_uri AS backup_image,
					cre.size AS creative_size
				FROM
					cup_versions AS ver
					JOIN cup_creatives AS cre
						ON (ver.id = cre.version_id)
					JOIN cup_ad_assets AS caa
						ON (cre.id = caa.creative_id)
				WHERE
					caa.type = \"backup\"
					AND ver.id IN ".$version_sql."
					AND cre.size != \"300x250\"
				) AS creatives
			GROUP BY
				creatives.version_id
			";
		$query_result = $this->db->query($query, array_merge($version_ids, $version_ids));
		return $query_result->result_array();
	}
	
	private function get_geography_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$data_set,
		$sorting,
		$build_data_type
	)
	{
		$campaign_ids = $this->get_frequence_campaign_ids_with_pre_roll_filter($data_set, $campaign_set);

		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));

		$sort_sql = $this->get_sorting_sql($sorting);

		$data_filter = $this->get_pre_roll_filter_sql($data_set);

		$view_throughs_outer_select_sql = "";
		$view_throughs_inner_non_retargeting_select_sql = "";
		$view_throughs_inner_retargeting_select_sql = "";
		$this->get_view_through_sql_pieces(
			$view_throughs_outer_select_sql,
			$view_throughs_inner_non_retargeting_select_sql,
			$view_throughs_inner_retargeting_select_sql,
			$subfeature_access_permissions['are_view_throughs_accessible'],
			$build_data_type
		);

		// TODO: No longer differentiate between retargeting and non, so just merge queries.  No union.

		$query = '
		SELECT
			place AS place,
			region AS region,
			SUM(nr_impressions) + SUM(r_impressions) AS impressions,
			SUM(nr_clicks) + SUM(r_clicks) AS clicks,
			100 * (SUM(nr_clicks) + SUM(r_clicks)) / (SUM(nr_impressions) + SUM(r_impressions)) AS click_rate
			'.$view_throughs_outer_select_sql.'
		FROM
		(
			(
				SELECT
					b.City AS place,
					b.Region AS region,
					SUM(b.Impressions) AS nr_impressions,
					SUM(b.Clicks) AS nr_clicks,
					0 AS r_impressions,
					0 AS r_clicks
					'.$view_throughs_inner_non_retargeting_select_sql.'
				FROM (AdGroups a JOIN CityRecords b ON (a.ID = b.AdGroupID)) 
					JOIN Campaigns c ON (a.campaign_id = c.id)
				WHERE
					c.business_id = ? AND
					b.Date BETWEEN ? AND ? AND
					a.campaign_id IN '.$campaigns_sql.' AND
					a.IsRetargeting = 0
					'.$data_filter.'
				GROUP BY place, region
			)
			UNION ALL
			(
				SELECT
					b.City AS place,
					b.Region AS region,
					0 AS nr_impressions,
					0 AS nr_clicks,
					SUM(b.Impressions) AS r_impressions,
					SUM(b.Clicks) AS r_clicks
					'.$view_throughs_inner_retargeting_select_sql.'
				FROM (AdGroups a JOIN CityRecords b ON (a.ID = b.AdGroupID)) 
					JOIN Campaigns c ON (a.campaign_id = c.id)
				WHERE
					c.business_id = ? AND
					b.Date BETWEEN ? AND ? AND
					a.campaign_id IN '.$campaigns_sql.' AND
					a.IsRetargeting = 1
					'.$data_filter.'
				GROUP BY place, region
			)
		) AS u
		GROUP BY place, region
		'.$sort_sql.'
		';

		$advertiser_bindings = array(
			$advertiser_id
		);

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$bindings = array_merge(
			$advertiser_bindings,
			$date_bindings,
			$campaign_ids,

			$advertiser_bindings,
			$date_bindings,
			$campaign_ids
		);

		$response = $this->db->query($query, $bindings);

		return $response;
	}

	public function get_overview_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		return null;
	}

	public function get_geography_data_no_pre_roll_yes_display(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		return $this->get_geography_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			k_impressions_clicks_data_set::non_pre_roll
		);
	}

	public function get_geography_data_yes_pre_roll_no_display(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		return $this->get_geography_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			k_impressions_clicks_data_set::pre_roll_only
		);
	}

	public function get_geography_data_yes_pre_roll_yes_display(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		return $this->get_geography_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			k_impressions_clicks_data_set::all
		);
	}

	private function get_geography_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$data_set
	)
	{
		$table_starting_sorts = $this->get_geography_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$response = $this->get_geography_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$data_set,
			$core_sorting,
			$build_data_type
		);

		$heatmap_geojson_with_data = null;
		$has_pl_heatmap_access = $this->vl_platform_model->is_feature_accessible($this->tank_auth->get_user_id(), 'report_heatmap');
		$is_advertiser_heatmaps_enabled = $this->does_advertiser_have_report_heatmaps_enabled($advertiser_id);

		if($has_pl_heatmap_access && $is_advertiser_heatmaps_enabled)
		{
			$campaign_ids = $this->get_frequence_campaign_ids_with_pre_roll_filter($data_set, $campaign_set);
			$heatmap_geojson_with_data = $this->map_legacy->get_report_heatmap_geojson_and_data($campaign_ids, $raw_start_date, $raw_end_date);
		}

		$column_class_prefix = 'table_cell_tareted_display_geography';

		$table_headers = array(
			array(
				"Place", 
				new report_table_column_format(
					$column_class_prefix.'_place', 
					'asc', 
					$this->format_string_callable
				),
				"City or Town"
			),
			array(
				"Region",
				new report_table_column_format(
					$column_class_prefix.'_region',
					'asc',
					$this->format_string_callable
				),
				"State"
			),
			array(
				"Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Ads shown to users"
			),
			array(
				"Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Clicks on ads leading to a visit to conversion pages"
			),
			array(
				"Click Rate", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of impressions which lead to a click (clicks / impressions)"
			),
		);

		if($subfeature_access_permissions['are_view_throughs_accessible'])
		{
			$view_through_table_headers = array(
				array(
					"View Throughs", 
					new report_table_column_format(
						$column_class_prefix.'_view_throughs', 
						'desc', 
						$this->format_whole_number_callable
					),
					"Visits to conversion page by impressioned users (who did not click)"
				)
			);

			if($build_data_type !== k_build_table_type::csv)
			{
				$view_through_table_headers[] = array(
					"View Through Rate", 
					new report_table_column_format(
						$column_class_prefix.'_view_through_rate', 
						'desc', 
						$this->format_percent_callable
					),
					"Percent of impressions which lead to a view through (view throughs / impressions)"
				);
			}

			$table_headers = array_merge($table_headers, $view_through_table_headers);
		}

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data, $heatmap_geojson_with_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}
		return $result;
	}

	private function get_interactions_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'desc'));
	}

	private function make_bindings_sql_from_array(array $elements)
	{
		return implode(',', array_map(function ($element) { return '?'; }, $elements));
	}

	private function get_interactions_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting
	)
	{
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence, 
			report_campaign_type::frequence_display
		);

		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));		

		$sort_sql = $this->get_sorting_sql($sorting);

		$random_string = time()."_".rand(10000, 99999);
		$temp_dates_table_name = "temp_dates_$random_string";

		$dates_set = '';
		$current = strtotime($start_date_search);
		$last = strtotime($end_date_search);
		$is_first_loop = true;
		while($current <= $last)
		{
			if($is_first_loop)
			{
				$is_first_loop = false;
			}
			else
			{
				$dates_set .= ', '."\n";
			}
			$dates_set .= '(\''.date('Y-m-d', $current).'\')';
			$current = strtotime('+1 day', $current);
		}
		$dates_set .= '';

		$delete_temp_table_query = "
			DROP TEMPORARY TABLE IF EXISTS $temp_dates_table_name;
		";
		$delete_temp_table_response = $this->db->query($delete_temp_table_query);

		$create_temp_table_query = "
			CREATE TEMPORARY TABLE $temp_dates_table_name (
				day DATE NOT NULL PRIMARY KEY
			);
		";
		$create_temp_table_response = $this->db->query($create_temp_table_query);

		$insert_temp_table_query = "
			INSERT INTO $temp_dates_table_name
			VALUES
				$dates_set
				;
		";
		$insert_temp_table_response = $this->db->query($insert_temp_table_query);

		$ad_interactions_from_sql = '';
		$ad_interactions_where_sql = '';
		$ad_interactions_hovers_total_sql = '';
		$ad_interactions_video_plays_total_sql = '';

		$ad_interactions_bindings = array();
		$this->get_ad_interactions_query_elements(
			$ad_interactions_from_sql,
			$ad_interactions_where_sql,
			$ad_interactions_hovers_total_sql,
			$ad_interactions_video_plays_total_sql,
			$ad_interactions_bindings,
			$advertiser_id,
			$campaign_ids
		);

		$view_throughs_outer_select_sql = "";
		if($subfeature_access_permissions['are_view_throughs_accessible'])
		{
			$view_throughs_outer_select_sql = "
				SUM(view_throughs) AS view_throughs,
			";
			$view_throughs_engagement_total_sql = "
				+ SUM(view_throughs)
			";			
		}

		$query = '
			SELECT 
				place AS place,
				SUM(impressions) AS impressions,
				IF(SUM(impressions) > 0, 
					SUM(hovers) + SUM(video_plays) + SUM(clicks) '.$view_throughs_engagement_total_sql.',
					0
				) AS engagements,
				IF(SUM(impressions) > 0, 
					100 * (SUM(hovers) + SUM(video_plays) + SUM(clicks)  '.$view_throughs_engagement_total_sql.') / SUM(impressions),
					0
				) AS engagement_rate,
				IF(SUM(impressions) > 0, 
					SUM(clicks),
					0
				) AS clicks,
				'.$view_throughs_outer_select_sql.'
				IF(SUM(impressions) > 0, 
					SUM(hovers),
					0
				) AS hovers,
				IF(SUM(impressions) > 0, 
					SUM(video_plays),
					0
				) AS video_plays
			FROM
			(
				(
					SELECT
						rcad.date AS place,
						SUM(rcad.impressions) AS impressions,
						SUM(rcad.clicks) AS clicks,
						0 AS hovers,
						0 AS video_plays,
						SUM(rcad.post_impression_conversion) AS view_throughs
					FROM 
						(AdGroups a 
						JOIN report_cached_adgroup_date AS rcad 
							ON (a.ID = rcad.adgroup_id))
						JOIN Campaigns c 
							ON (a.campaign_id = c.id)
					WHERE
						c.business_id = ? AND
						rcad.date BETWEEN ? AND ? AND
						a.campaign_id IN '.$campaigns_sql.'
					GROUP BY place
				)
				UNION ALL
				(
					SELECT 
						er.date AS place, 
						0 AS impressions, 
						0 AS clicks, 
						'.$ad_interactions_hovers_total_sql.'
							AS hovers,
						'.$ad_interactions_video_plays_total_sql.'
							AS video_plays,
						0 AS view_throughs
						FROM 
							'.$ad_interactions_from_sql.'
							JOIN cup_versions AS ver ON (ver.campaign_id = cmp.id)
							JOIN cup_creatives AS cr ON (cr.version_id = ver.id)
							JOIN engagement_records AS er ON (er.creative_id = cr.id)
						WHERE
							'.$ad_interactions_where_sql.'
							er.date BETWEEN ? AND ?
						GROUP BY
							place
				)
				UNION ALL
				(
					SELECT 
						day AS place, 
						0 AS impressions, 
						0 AS clicks, 
						0 AS hovers,
						0 AS video_plays,
						0 AS view_throughs
						FROM '.$temp_dates_table_name.'
				)
			) AS mask
			GROUP BY place
			'.$sort_sql.'
		';

		$advertiser_bindings = array(
			$advertiser_id
		);

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$bindings = array_merge(
			$advertiser_bindings,
			$date_bindings,
			$campaign_ids,

			$ad_interactions_bindings,
			$date_bindings
		);

		$response = $this->db->query($query, $bindings);

		$delete_temp_table_response = $this->db->query($delete_temp_table_query);

		return $response;
	}

	public function get_interactions_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$table_starting_sorts = $this->get_interactions_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$response = $this->get_interactions_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$column_class_prefix = 'table_cell_tareted_display_interactions';

		$table_headers = array(
			array(
				"Date", 
				new report_table_column_format(
					$column_class_prefix.'_date', 
					'desc', 
					$this->format_string_callable
				),
				""
			),
			array(
				"Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Ads shown to users"
			),
			array(
				"Engagements", 
				new report_table_column_format(
					$column_class_prefix.'_engagements', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Ad interactions (video plays + video clicks + hovers) + visits"
			),
			array(
				"Engagement Rate", 
				new report_table_column_format(
					$column_class_prefix.'_engagement_rate', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of impressions resulting in an engagement (engagements / impressions)"
			),
			$table_headers[] = array(
				"Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Clicks on ads leading to a visit to conversion pages"
			)
		);

		if($subfeature_access_permissions['are_view_throughs_accessible'])
		{
			$table_headers[] = array(
				"View Throughs", 
				new report_table_column_format(
					$column_class_prefix.'_view_throughs', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Visits to conversion page by impressioned users (who did not click)"
			);
		}

		$table_headers = array_merge($table_headers, array(			
				array(
					"Hovers", 
					new report_table_column_format(
						$column_class_prefix.'_hovers', 
						'desc', 
						$this->format_whole_number_callable
					),
					"User interactions with an ad by hovering cursor over display ad or map instance"
				),
				array(
					"Video Plays", 
					new report_table_column_format(
						$column_class_prefix.'_video_plays', 
						'desc', 
						$this->format_whole_number_callable
					),
					"User action causing video play"
				)
			)
		);

		$table_content = $response->result_array();

		$result = false;

		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function format_screenshots_row($headers, $result_row)
	{
		$table_row = new report_table_row();

		assert(count($result_row) === 4);

		$date_column_format = $headers[0][1];
		$date_cell = new report_table_cell();
		$date_cell->html_content = '<a href="http:'.$result_row["path"].'" target="_blank">'.$result_row['date'].'</a>';
		$date_cell->css_classes = 'table_body_cell '.$date_column_format->css_class;
		$table_row->cells[] = $date_cell;

		$domain_column_format = $headers[1][1];
		$domain_cell = new report_table_cell();
		$domain_cell->html_content = $result_row['URL'];
		$domain_cell->css_classes = 'table_body_cell '.$domain_column_format->css_class;
		$table_row->cells[] = $domain_cell;

		$full_url_column_format = $headers[2][1];
		$full_url_cell = new report_table_cell();
		$full_url_cell->html_content = $result_row['full'];
		$full_url_cell->css_classes = 'table_body_cell '.$full_url_column_format->css_class;
		$table_row->cells[] = $full_url_cell;

		return $table_row;
	}

	private function get_screenshots_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'desc'));
	}

	public function get_screenshots_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting
	)
	{
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);

		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));		

		$sort_sql = $this->get_sorting_sql($sorting);

		$query = "
			SELECT 
				ss.file_name AS path,
				ss.creation_date AS date,
				ss.base_url as URL,
				ss.full_url as full
			FROM 
				Campaigns AS cmp 
				JOIN ad_verify_screen_shots AS ss ON (cmp.id = ss.campaign_id)
			WHERE 
				cmp.business_id = ? AND
				ss.is_approved = 1 AND 
				ss.campaign_id IN $campaigns_sql
			$sort_sql
		";

		$advertiser_bindings = array(
			$advertiser_id
		);

		$bindings = array_merge($advertiser_bindings, $campaign_ids);

		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_screenshots_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$table_starting_sorts = $this->get_screenshots_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$response = $this->get_screenshots_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$column_class_prefix = 'table_cell_tareted_display_screenshots';

		$table_headers = array(
			array(
				"Time Captured", 
				new report_table_column_format(
					$column_class_prefix.'_time_captured', 
					'desc', 
					$this->format_string_callable
				),
				"When the screenshot was taken"
			),
			array(
				"Domain", 
				new report_table_column_format(
					$column_class_prefix.'_domain', 
					'asc', 
					$this->format_whole_number_callable
				),
				"Core domain where the screenshot was taken"
			),
			array(
				"Full Page URL", 
				new report_table_column_format(
					$column_class_prefix.'_full_url', 
					'asc', 
					$this->format_whole_number_callable
				),
				"Page url where the screenshot was taken"
			)
		);

		$format_callable = array($this, 'format_screenshots_row');

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_special_format_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts,
					$format_callable
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				throw new Exception("Downloading of screenshot csv not implemented. (#6701024)");
				$result = false;
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function get_ad_sizes_sorting()
	{
		return array(new report_table_starting_column_sort(2, 'desc'));
	}

	private function get_ad_sizes_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting
	)
	{
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);

		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));		

		$sort_sql = $this->get_sorting_sql($sorting);

		// TODO: Remove union and subquery, don't distinguish between retargeting and not.

		$query = "
			SELECT
				size AS size,
				SUM(nr_impressions) + SUM(r_impressions) AS impressions,
				SUM(nr_clicks) + SUM(r_clicks) AS clicks,
				100 * (SUM(nr_clicks) + SUM(r_clicks)) / (SUM(nr_impressions) + SUM(r_impressions)) AS click_rate
			FROM
			(
				(
					SELECT
						b.Size AS size,
						SUM(b.Impressions) AS nr_impressions,
						SUM(b.Clicks) AS nr_clicks,
						0 AS r_impressions,
						0 AS r_clicks
					FROM (AdGroups a JOIN report_ad_size_records b ON (a.ID = b.AdGroupID)) 
						JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id = ? AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN $campaigns_sql AND
						a.IsRetargeting = 0
					GROUP BY size
				)
				UNION ALL
				(
					SELECT
						b.Size AS size,
						0 AS nr_impressions,
						0 AS nr_clicks,
						SUM(b.Impressions) AS r_impressions,
						SUM(b.Clicks) AS r_clicks
					FROM (AdGroups a JOIN report_ad_size_records b ON (a.ID = b.AdGroupID)) 
						JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id = ? AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN $campaigns_sql AND
						a.IsRetargeting = 1
					GROUP BY size
				)
			) AS u
			GROUP BY size
			$sort_sql
		";

		$advertiser_bindings = array(
			$advertiser_id
		);

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$bindings = array_merge(
			$advertiser_bindings,
			$date_bindings,
			$campaign_ids,

			$advertiser_bindings,
			$date_bindings,
			$campaign_ids
		);

		$response = $this->db->query($query, $bindings);
		return $response;
	}

	public function get_ad_sizes_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$table_starting_sorts = $this->get_ad_sizes_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$response = $this->get_ad_sizes_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$column_class_prefix = 'table_cell_tareted_display_ad_sizes';

		$table_headers = array(
			array(
				"Ad Size", 
				new report_table_column_format(
					$column_class_prefix.'_ad_size', 
					'asc', 
					$this->format_string_callable
				),
				"The dimensions of the ad"
			),
			array(
				"Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Ads shown to users"
			),
			array(
				"Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Clicks on ads leading to a visit to conversion pages"
			),
			array(
				"Click Rate", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of impressions which lead to a click (clicks / impressions)"
			)
		);

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function generate_sql_for_ids($ids, $where_field_in_sql = null, $use_empty_string_on_empty_set = false)
	{
		$sql = '';

		$num_ids = count($ids);
		if ($use_empty_string_on_empty_set && $num_ids == 0)
		{
			return $sql;
		}
		else
		{
			if($num_ids > 0)
			{
				$ids_sql = '(-1,'.$this->make_bindings_sql_from_array($ids).')';
			}
			else
	        {
	            $ids_sql = '(-1)';
	        }
			$table_where_sql = $where_field_in_sql;
			if($where_field_in_sql === null)
			{
				$table_where_sql = 'AND ta.account_id IN ';
			}

			$sql = $table_where_sql . $ids_sql;

			return $sql;
		}
	}

	private function get_campaigns_sql_and_bindings_by_type(
		array &$sql_by_type,
		array &$bindings_by_type,
		report_campaign_set $campaign_set,
		$organization, // Example: report_campaign_organization::tmpi
		$table_where_in_sql = null, // Example: ' WHERE ta.account_id IN ',
		$use_empty_string_on_empty_set = false
	)
	{
		$ids_by_type = $campaign_set->get_organziation_campaign_id_sets($organization);

		foreach($ids_by_type as $type => $ids)
		{
			$sql_by_type[$type] = $this->generate_sql_for_ids($ids, $table_where_in_sql, $use_empty_string_on_empty_set);
			$bindings_by_type[$type] = $ids;
		}
	}

	private function get_summary_frequence_sql_and_bindings(
		&$summary_frequence_sql,
		array &$summary_frequence_bindings,
		$advertiser_id,
		$start_date_search,
		$end_date_search,
		$frequence_campaigns_sql,
		$frequence_campaigns_ids,
		$frequence_campaigns_type,
		$subfeature_access_permissions,
		$is_group_by_date
	)
	{
		$display_value = $frequence_campaigns_type == report_campaign_type::frequence_display ? 1 : 0;
		$pre_roll_value = $frequence_campaigns_type == report_campaign_type::frequence_pre_roll ? 1 : 0;

		$view_throughs_inner_select_sql = "0 AS view_throughs,";
		if($subfeature_access_permissions['are_view_throughs_accessible'])
		{
			$view_throughs_inner_select_sql = "SUM(rcad.post_impression_conversion) AS view_throughs,";
		}

		$date_select_sql = "";
		$date_group_by_sql = "";
		if($is_group_by_date)
		{
			$date_select_sql = ", rcad.date AS date";
			$date_group_by_sql = "GROUP BY date";
		}

		$summary_frequence_sql = "
			(
				SELECT
					SUM(rcad.impressions) AS impressions,
					SUM(rcad.clicks) AS clicks,
					SUM(rcad.retargeting_impressions) AS retargeting_impressions,
					SUM(rcad.retargeting_clicks) AS retargeting_clicks,
					0 AS ad_interactions,
					$view_throughs_inner_select_sql
					0 AS outer_leads_value,
					0 AS engagements_extras,

					$display_value AS has_targeted_display,
					$pre_roll_value AS has_targeted_pre_roll,
					0 AS has_targeted_clicks,
					0 AS has_targeted_inventory,
					0 AS has_targeted_directories,

					SUM(rcad.Impressions) AS null_detector
					$date_select_sql
				FROM
					report_cached_adgroup_date AS rcad
					JOIN AdGroups AS ag 
						ON rcad.adgroup_id = ag.id
					JOIN Campaigns AS cmp 
						ON (ag.campaign_id = cmp.id)
				WHERE
					cmp.business_id = ?
					AND rcad.date BETWEEN ? AND ?
					$frequence_campaigns_sql
				$date_group_by_sql
			)
		";

		$summary_frequence_bindings = array_merge(
			array(
				$advertiser_id,
				$start_date_search,
				$end_date_search
			),
			$frequence_campaigns_ids
		);
	}

	private function resolve_campaign_type_data_to_sql_and_bindings(
		&$data_sql,
		&$data_bindings,
		$ids_by_type,
		$data_by_type,
		$force_include_sql_for_empty_sets = false
	)
	{
		foreach($ids_by_type as $type => $ids)
		{
			if(!empty($ids) || $force_include_sql_for_empty_sets)
			{
				if(!empty($data_sql))
				{
					$data_sql .= "\nUNION ALL\n";
				}

				$data_sql .= $data_by_type[$type]['sql'];
				$data_bindings = array_merge(
					$data_bindings,
					$data_by_type[$type]['bindings']
				);
			}
		}
	}

	private function get_targeted_common_clicks_sql(
		$select_sql,
		$vl_advertiser_to_tmpi_account_from_sql,
		$vl_advertiser_to_tmpi_account_where_sql,
		$tmpi_accounts_sql,
		$group_by_sql,
		$date_bindings,
		$tmpi_clicks_campaign_type
	)
	{
		$has_dates = !empty($date_bindings);
		$sql = "
				SELECT 
					$select_sql
				FROM 
					$vl_advertiser_to_tmpi_account_from_sql
					JOIN tp_tmpi_accounts AS ta
						ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
					JOIN tp_tmpi_display_clicks AS tdc
						ON (ta.account_id = tdc.account_id)
				WHERE
					$vl_advertiser_to_tmpi_account_where_sql
					$tmpi_accounts_sql
					AND tdc.campaign_type = $tmpi_clicks_campaign_type
					".($has_dates ? "AND tdc.captured BETWEEN ? AND ?" : "")."
				$group_by_sql
		";

		return $sql;
	}
	
	private function get_targeted_common_clicks_bindings(
		$vl_advertiser_to_tmpi_account_bindings,
		$tmpi_accounts_bindings,
		$tmpi_dates_bindings,
		$tmpi_clicks_campaign_type
	)
	{
		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_accounts_bindings,
			$tmpi_dates_bindings
		);

		return $bindings;
	}

	public function get_summary_data_by_id(
		$advertiser_id, 
		$campaign_set, 
		$start_date, 
		$end_date,
		$subfeature_access_permissions
	)
	{
		$start_date_search = date("Y-m-d", strtotime($start_date));
		$end_date_search = date("Y-m-d", strtotime($end_date));

		$frequence_data_sql = "";
		$frequence_data_bindings = array();

		$has_frequence_data = true; // TODO: really populate this
		if($has_frequence_data)
		{
			$frequence_campaigns_sql_by_type = array();
			$frequence_campaigns_bindings_by_type = array(); 
		
			$this->get_campaigns_sql_and_bindings_by_type(
				$frequence_campaigns_sql_by_type,
				$frequence_campaigns_bindings_by_type,
				$campaign_set,
				report_campaign_organization::frequence,
				'AND ag.campaign_id IN '
			);

			$ad_interactions_sql = '';
			$ad_interactions_bindings = array();

			$ad_interactions_campaign_ids = $campaign_set->get_campaign_ids(
				report_campaign_organization::frequence,
				report_campaign_type::frequence_display
			);

			if($subfeature_access_permissions['are_ad_interactions_accessible'] && 
				!empty($ad_interactions_campaign_ids)
			)
			{
				$ad_interactions_from_sql = '';
				$ad_interactions_where_sql = '';
				$ad_interactions_hovers_total_sql = '';
				$ad_interactions_video_plays_total_sql = '';
				$ad_interactions_bindings = array();

				$this->get_ad_interactions_query_elements(
					$ad_interactions_from_sql,
					$ad_interactions_where_sql,
					$ad_interactions_hovers_total_sql,
					$ad_interactions_video_plays_total_sql,
					$ad_interactions_bindings,
					$advertiser_id,
					$ad_interactions_campaign_ids
				);

				// Removed ad_interactions from the null detector because it's masked by impressions.

				$ad_interactions_sql = '
					UNION ALL 
					(
						SELECT 
							0 AS impressions,
							0 AS clicks,
							0 AS retargeting_impressions,
							0 AS retargeting_clicks,
							'.$ad_interactions_hovers_total_sql.' + '.
								$ad_interactions_video_plays_total_sql.' 
								AS ad_interactions,
							0 AS view_throughs,
							0 AS outer_leads_value
							,
							0 AS engagements_extras
							,
							1 AS has_targeted_display,
							0 AS has_targeted_pre_roll,
							0 AS has_targeted_clicks,
							0 AS has_targeted_inventory,
							0 AS has_targeted_directories
							, 
							0 AS null_detector,
							er.date AS date
						FROM 
							'.$ad_interactions_from_sql.'
							JOIN cup_versions AS ver ON (ver.campaign_id = cmp.id)
							JOIN cup_creatives AS cr ON (cr.version_id = ver.id)
							JOIN engagement_records AS er ON (er.creative_id = cr.id)
						WHERE
							'.$ad_interactions_where_sql.'
							er.date BETWEEN ? AND ?
						GROUP BY date
					)
				';

				$ad_interactions_bindings[] = $start_date_search;
				$ad_interactions_bindings[] = $end_date_search;
			}
		
			$summary_display_sql = "";
			$summary_display_bindings = array();
			$this->get_summary_frequence_sql_and_bindings(
				$summary_display_sql,
				$summary_display_bindings,
				$advertiser_id,
				$start_date_search,
				$end_date_search,
				$frequence_campaigns_sql_by_type[report_campaign_type::frequence_display],
				$frequence_campaigns_bindings_by_type[report_campaign_type::frequence_display],
				report_campaign_type::frequence_display,
				$subfeature_access_permissions,
				true
			);

			$summary_pre_roll_sql = "";
			$summary_pre_roll_bindings = array();
			$this->get_summary_frequence_sql_and_bindings(
				$summary_pre_roll_sql,
				$summary_pre_roll_bindings,
				$advertiser_id,
				$start_date_search,
				$end_date_search,
				$frequence_campaigns_sql_by_type[report_campaign_type::frequence_pre_roll],
				$frequence_campaigns_bindings_by_type[report_campaign_type::frequence_pre_roll],
				report_campaign_type::frequence_pre_roll,
				$subfeature_access_permissions,
				false
			);

			$frequence_summary_data_by_type = array(
				report_campaign_type::frequence_display => array(
					'sql' => '
						SELECT
							SUM(impressions) AS impressions,
							SUM(clicks) AS clicks,
							SUM(retargeting_impressions) AS retargeting_impressions,
							SUM(retargeting_clicks) AS retargeting_clicks,
							SUM(IF(impressions > 0, 
								ad_interactions,
								0
							)) AS ad_interactions,
							SUM(view_throughs) AS view_throughs,
							SUM(outer_leads_value) AS outer_leads_value,
							SUM(engagements_extras) AS engagements_extras,
							1 AS has_targeted_display,
							0 AS has_targeted_pre_roll,
							0 AS has_targeted_clicks,
							0 AS has_targeted_inventory,
							0 AS has_targeted_directories, 
							SUM(null_detector) AS null_detector
						FROM
						(	
							SELECT
								SUM(impressions) AS impressions,
								SUM(clicks) AS clicks,
								SUM(retargeting_impressions) AS retargeting_impressions,
								SUM(retargeting_clicks) AS retargeting_clicks,
								SUM(ad_interactions) AS ad_interactions,
								SUM(view_throughs) AS view_throughs,
								SUM(outer_leads_value) AS outer_leads_value,
								SUM(engagements_extras) AS engagements_extras,
								SUM(null_detector) AS null_detector,
								date AS date
							FROM
							(
							'. $summary_display_sql
							.  $ad_interactions_sql
							.') as accumulate
							GROUP BY date
						) AS mask
						',
					'bindings' => array_merge(
						$summary_display_bindings,
						$ad_interactions_bindings
					)
				),
				report_campaign_type::frequence_pre_roll => array(
					'sql' => $summary_pre_roll_sql,
					'bindings' => array_merge(
						$summary_pre_roll_bindings
					)
				)
			);

			$this->resolve_campaign_type_data_to_sql_and_bindings(
				$frequence_data_sql,
				$frequence_data_bindings,
				$frequence_campaigns_bindings_by_type,
				$frequence_summary_data_by_type
			);
		}

		$tmpi_summary_data_sql = "";
		$tmpi_summary_data_bindings = array();
		if($subfeature_access_permissions['is_tmpi_accessible'])
		{
			$vl_advertiser_to_tmpi_account_from_sql = "";
			$vl_advertiser_to_tmpi_account_where_sql = "";
			$vl_advertiser_to_tmpi_account_bindings = array();

			$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
				$vl_advertiser_to_tmpi_account_from_sql,
				$vl_advertiser_to_tmpi_account_where_sql,
				$vl_advertiser_to_tmpi_account_bindings,
				$advertiser_id,
			report_campaign_organization::tmpi
			);

			$tmpi_accounts_sql_by_type = array();
			$tmpi_accounts_bindings_by_type = array();
			$this->get_campaigns_sql_and_bindings_by_type(
				$tmpi_accounts_sql_by_type,
				$tmpi_accounts_bindings_by_type,
				$campaign_set,
				report_campaign_organization::tmpi
			);

			$tmpi_dates = array($start_date_search, $end_date_search);

			$tmpi_common_clicks_select_sql = '
				SUM(tdc.impressions) AS impressions,
				SUM(tdc.clicks) AS clicks,
				0 AS retargeting_impressions,
				0 AS retargeting_clicks,
				0 AS ad_interactions,
				0 AS view_throughs,
				0 AS outer_leads_value
				,
				0 AS engagements_extras
				,
				0 AS has_targeted_display,
				0 AS has_targeted_pre_roll,
				1 AS has_targeted_clicks,
				0 AS has_targeted_inventory,
				0 AS has_targeted_directories
				, SUM(tdc.impressions) AS null_detector
			';
			$tmpi_common_clicks_group_by_sql = '';

			$tmpi_summary_data_sql_by_type = array(
				report_campaign_type::tmpi_clicks => array(
					'sql' => 
						'('.
						$this->get_targeted_common_clicks_sql(
							$tmpi_common_clicks_select_sql,
							$vl_advertiser_to_tmpi_account_from_sql,
							$vl_advertiser_to_tmpi_account_where_sql,
							$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_clicks],
							$tmpi_common_clicks_group_by_sql,
							$tmpi_dates,
							tmpi_clicks_campaign_type::clicks
						)
						.')' ,
					'bindings' => $this->get_targeted_common_clicks_bindings(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_clicks],
						$tmpi_dates,
						tmpi_clicks_campaign_type::clicks
					)
				),
				report_campaign_type::tmpi_directories => array(
					'sql' => '
						(
							SELECT 
								SUM(tlp.total) AS impressions,
								0 AS clicks,
								0 AS retargeting_impressions,
								0 AS retargeting_clicks,
								0 AS ad_interactions,
								0 AS view_throughs,
								0 AS outer_leads_value
								,
								0 AS engagements_extras
								,
								0 AS has_targeted_display,
								0 AS has_targeted_pre_roll,
								0 AS has_targeted_clicks,
								0 AS has_targeted_inventory,
								1 AS has_targeted_directories
								, SUM(tlp.total) AS null_detector
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_local_performance AS tlp
									ON (ta.account_id = tlp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories].'
								AND tlp.captured BETWEEN ? AND ?
								AND (
									tlp.short_name = "Offer Views" OR
									tlp.short_name = "Profile Views" OR
									tlp.short_name = "Review Views"
								)
						)
						UNION ALL
						(
							SELECT 
								0 AS impressions,
								0 AS clicks,
								0 AS retargeting_impressions,
								0 AS retargeting_clicks,
								0 AS ad_interactions,
								0 AS view_throughs,
								0 AS outer_leads_value
								,
								SUM(tlp.total) AS engagements_extras
								,
								0 AS has_targeted_display,
								0 AS has_targeted_pre_roll,
								0 AS has_targeted_clicks,
								0 AS has_targeted_inventory,
								1 AS has_targeted_directories
								, SUM(tlp.total) AS null_detector
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_local_performance AS tlp
									ON (ta.account_id = tlp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories].'
								AND tlp.captured BETWEEN ? AND ?
								AND (
									tlp.short_name = "Map Views/Directions" OR
									tlp.short_name = "Printed Info"
								)
						)
						UNION ALL
						(
							SELECT 
								0 AS impressions,
								SUM(tlp.total) AS clicks,
								0 AS retargeting_impressions,
								0 AS retargeting_clicks,
								0 AS ad_interactions,
								0 AS view_throughs,
								0 AS outer_leads_value
								,
								0 AS engagements_extras
								,
								0 AS has_targeted_display,
								0 AS has_targeted_pre_roll,
								0 AS has_targeted_clicks,
								0 AS has_targeted_inventory,
								1 AS has_targeted_directories
								, SUM(tlp.total) AS null_detector
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_local_performance AS tlp
									ON (ta.account_id = tlp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories].'
								AND tlp.captured BETWEEN ? AND ?
								AND (
									tlp.short_name = "Clicks to Websites" OR
									tlp.short_name = "Inventory Clicks" OR
									tlp.short_name = "Appointment Clicks"
								)
						)
					',
					'bindings' => array_merge(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories],
						$tmpi_dates,
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories],
						$tmpi_dates,
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories],
						$tmpi_dates
					)
				),
				report_campaign_type::tmpi_inventory => array(
					/*
						(
							SELECT 
								SUM(tsdp.month_total) AS impressions,
								0 AS clicks,
								0 AS retargeting_impressions,
								0 AS retargeting_clicks,
								0 AS ad_interactions,
								0 AS view_throughs,
								0 AS outer_leads_value
								,
								0 AS engagements_extras
								,
								0 AS has_targeted_display,
								0 AS has_targeted_pre_roll,
								0 AS has_targeted_clicks,
								1 AS has_targeted_inventory,
								0 AS has_targeted_directories
								, SUM(tsdp.month_total) AS null_detector
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_search_detail_performance AS tsdp
									ON (ta.account_id = tsdp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory].'
								AND tsdp.report_month BETWEEN ? AND ?
								AND tsdp.short_name = "Inventory Searches"
						)
						UNION ALL
						(
							SELECT 
								0 AS impressions,
								0 AS clicks,
								0 AS retargeting_impressions,
								0 AS retargeting_clicks,
								0 AS ad_interactions,
								0 AS view_throughs,
								0 AS outer_leads_value
								,
								SUM(tsdp.month_total) AS engagements_extras
								,
								0 AS has_targeted_display,
								0 AS has_targeted_pre_roll,
								0 AS has_targeted_clicks,
								1 AS has_targeted_inventory,
								0 AS has_targeted_directories
								, SUM(tsdp.month_total) AS null_detector
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_search_detail_performance AS tsdp
									ON (ta.account_id = tsdp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory].'
								AND tsdp.report_month BETWEEN ? AND ?
								AND tsdp.short_name = "Inventory Detail Views"
						)
						UNION ALL
						*/
					'sql' => '
						(
							SELECT 
								0 AS impressions,
								0 AS clicks,
								0 AS retargeting_impressions,
								0 AS retargeting_clicks,
								0 AS ad_interactions,
								0 AS view_throughs,
								SUM(inner_leads_value) AS outer_leads_value
								,
								0 AS engagements_extras
								,
								0 AS has_targeted_display,
								0 AS has_targeted_pre_roll,
								0 AS has_targeted_clicks,
								1 AS has_targeted_inventory,
								0 AS has_targeted_directories
								, SUM(inner_leads_value) AS null_detector
							FROM
							(
								(
									SELECT 
										COUNT(*) AS inner_leads_value
									FROM 
										'.$vl_advertiser_to_tmpi_account_from_sql.'
										JOIN tp_tmpi_accounts AS ta
											ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
										JOIN tp_tmpi_email_leads AS tel
											ON (ta.account_id = tel.account_id)
									WHERE
										'.$vl_advertiser_to_tmpi_account_where_sql.'
										'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory].'
										AND tel.captured BETWEEN ? AND ?
								)
								UNION ALL
								(
									SELECT 
										COUNT(*) AS inner_leads_value
									FROM 
										'.$vl_advertiser_to_tmpi_account_from_sql.'
										JOIN tp_tmpi_accounts AS ta
											ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
										JOIN tp_tmpi_phone_leads AS tpl
											ON (ta.account_id = tpl.account_id)
									WHERE
										'.$vl_advertiser_to_tmpi_account_where_sql.'
										'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory].'
										AND tpl.captured BETWEEN ? AND ?
								)
							) AS leads_union
							WHERE 1
						)
						UNION ALL
					'.
					'('.
					$this->get_targeted_common_clicks_sql(
						$tmpi_common_clicks_select_sql,
						$vl_advertiser_to_tmpi_account_from_sql,
						$vl_advertiser_to_tmpi_account_where_sql,
						$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_common_clicks_group_by_sql,
						$tmpi_dates,
						tmpi_clicks_campaign_type::smart_ads
					)
					.')' ,
					'bindings' => array_merge(
						/*
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,
						*/
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,
						$this->get_targeted_common_clicks_bindings(
							$vl_advertiser_to_tmpi_account_bindings,
							$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
							$tmpi_dates,
							tmpi_clicks_campaign_type::smart_ads
						)
					)
				)
			);

			$this->resolve_campaign_type_data_to_sql_and_bindings(
				$tmpi_summary_data_sql,
				$tmpi_summary_data_bindings,
				$tmpi_accounts_bindings_by_type,
				$tmpi_summary_data_sql_by_type
			);
		}

		if(!empty($frequence_data_sql) || !empty($tmpi_summary_data_sql))
		{
			if(!empty($frequence_data_sql) && !empty($tmpi_summary_data_sql))
			{
				$frequence_data_sql .= "\nUNION ALL\n";
			}

			$view_throughs_outer_select_sql = "0 AS view_throughs,";
			if($subfeature_access_permissions['are_view_throughs_accessible'])
			{
				$view_throughs_outer_select_sql = "SUM(u.view_throughs) AS view_throughs,";
			}

			$ad_interactions_outer_select_sql = "0 AS ad_interactions,";
			if($subfeature_access_permissions['are_ad_interactions_accessible'])
			{
				$ad_interactions_outer_select_sql = "SUM(u.ad_interactions) AS ad_interactions,";
			}

			$leads_outer_select_sql = "0 AS leads,";
			$engagements_extras_outer_select_sql = "0 AS engagements_extras,";
			if($subfeature_access_permissions['is_tmpi_accessible'])
			{
				$leads_outer_select_sql = "SUM(outer_leads_value) AS leads,";
				$engagements_extras_outer_select_sql = "SUM(u.engagements_extras) AS engagements_extras,";
			}

			$sql = "
				SELECT
					SUM(u.impressions) AS impressions,
					SUM(u.clicks) AS clicks,
					SUM(u.retargeting_impressions) AS retargeting_impressions,
					SUM(u.retargeting_clicks) AS retargeting_clicks,
					$ad_interactions_outer_select_sql
					$view_throughs_outer_select_sql
					$leads_outer_select_sql
					$engagements_extras_outer_select_sql
					SUM(has_targeted_display) AS has_targeted_display,
					SUM(has_targeted_pre_roll) AS has_targeted_pre_roll,
					SUM(has_targeted_clicks) AS has_targeted_clicks,
					SUM(has_targeted_inventory) AS has_targeted_inventory,
					SUM(has_targeted_directories) AS has_targeted_directories
				FROM
				(
					$frequence_data_sql
					$tmpi_summary_data_sql
				) AS u
				WHERE
					null_detector IS NOT NULL
			";

			$bindings = array_merge(
				$frequence_data_bindings,
				$tmpi_summary_data_bindings
			);

			$response = $this->db->query($sql, $bindings);
		}
		else
		{
			$response = false;
		}

		return $response;
	}

	public function get_graph_data_by_id(
		$advertiser_id,
		$campaign_set,
		$start_date,
		$end_date,
		$subfeature_access_permissions,
		$is_getting_csv_data
	)
	{
		$bindings = array();

		$start_date = date("Y-m-d", strtotime($start_date));
		$end_date = date("Y-m-d", strtotime($end_date));		

		$frequence_sql = "";
		$frequence_bindings = array();

		$engagements_outer_from_sql = "";
		if($subfeature_access_permissions['are_engagements_accessible'] || !$is_getting_csv_data)
		{
			$engagements_outer_from_sql = '
				, SUM(eng) + SUM(tot_Clks) + SUM(view_throughs) + SUM(outer_leads_value) AS engagements
			';
		}

		$has_frequence_data = true;
		if($has_frequence_data)
		{
			$campaign_ids = $campaign_set->get_campaign_ids(report_campaign_organization::frequence);

			if(!empty($campaign_ids))
			{
				$frequence_campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

				if(!empty($frequence_campaigns_sql))
				{
					$frequence_campaigns_sql = 'ag.campaign_id IN '.$frequence_campaigns_sql;
				}

				$frequence_bindings = array_merge($bindings, $campaign_ids);
				$frequence_bindings[] = $start_date;
				$frequence_bindings[] = $end_date;
				$frequence_bindings[] = $advertiser_id;
				
				$ad_interactions_union_sql = "";
				$ad_interactions_bindings = array();

				$ad_interactions_campaign_ids = $campaign_set->get_campaign_ids(
					report_campaign_organization::frequence, 
					report_campaign_type::frequence_display
				);

				if($subfeature_access_permissions['are_ad_interactions_accessible'] &&
					!empty($ad_interactions_campaign_ids)
				)
				{
					$ad_interactions_from_sql = '';
					$ad_interactions_where_sql = '';
					$ad_interactions_hovers_total_sql = '';
					$ad_interactions_video_plays_total_sql = '';

					$this->get_ad_interactions_query_elements(
						$ad_interactions_from_sql,
						$ad_interactions_where_sql,
						$ad_interactions_hovers_total_sql,
						$ad_interactions_video_plays_total_sql,
						$ad_interactions_bindings,
						$advertiser_id,
						$ad_interactions_campaign_ids
					);

					$ad_interactions_union_sql = '
					UNION ALL 
					(
						SELECT 
							er.date as Date, 
							0 as tot_Imps, 
							0 as tot_Clks, 
							0 as r_Imps, 
							0 as r_Clks,'. 
							$ad_interactions_hovers_total_sql.' + '.
								$ad_interactions_video_plays_total_sql.' 
								as eng,
							0 AS view_throughs,
							0 AS outer_leads_value
						FROM 
							'.$ad_interactions_from_sql.'
							JOIN cup_versions AS ver ON (ver.campaign_id = cmp.id)
							JOIN cup_creatives AS cr ON (cr.version_id = ver.id)
							JOIN engagement_records AS er ON (er.creative_id = cr.id)
						WHERE
							'.$ad_interactions_where_sql.'
							er.date BETWEEN ? AND ?	
						GROUP BY
							er.date
					)
					';

					$ad_interactions_bindings[] = $start_date;
					$ad_interactions_bindings[] = $end_date;
				}

				$view_throughs_inner_select_sql = "0 AS view_throughs,";
				if($subfeature_access_permissions['are_view_throughs_accessible'])
				{
					$view_throughs_inner_select_sql = "SUM(rcad.post_impression_conversion) AS view_throughs,";
				}

				$frequence_sql = "
					SELECT
						Date AS Date, 
						SUM(tot_Imps) AS tot_Imps, 
						SUM(tot_Clks) AS tot_Clks,
						SUM(r_Imps) AS r_Imps, 
						SUM(r_Clks) AS r_Clks, 
						IF(SUM(tot_Imps) > 0, 
							SUM(eng),
							0
						) AS eng,
						SUM(view_throughs) AS view_throughs,
						SUM(outer_leads_value) AS outer_leads_value
					FROM 
					(
						SELECT 
							rcad.date AS Date, 
							SUM(rcad.impressions) AS tot_Imps, 
							SUM(rcad.clicks) AS tot_Clks,
							SUM(rcad.retargeting_impressions) AS r_Imps, 
							SUM(rcad.retargeting_clicks) AS r_Clks, 
							0 AS eng,
							$view_throughs_inner_select_sql
							0 AS outer_leads_value
						FROM 
							report_cached_adgroup_date AS rcad
							JOIN AdGroups AS ag 
								ON (rcad.adgroup_id = ag.id)
							JOIN Campaigns AS cmp
								ON (ag.campaign_id = cmp.id)
						WHERE 
							".$frequence_campaigns_sql." 
							AND rcad.date BETWEEN ? AND ? 
							AND cmp.business_id = ?
						GROUP BY rcad.date
						".$ad_interactions_union_sql."
					) AS mask
					GROUP BY Date
				";

				$frequence_bindings = array_merge($frequence_bindings, $ad_interactions_bindings);
			}
		}

		$is_tmpi_accessible = $subfeature_access_permissions['is_tmpi_accessible'];

		$tmpi_graph_sql = '';
		$tmpi_graph_bindings = array();
		$leads_outer_from_sql = '';
		if($is_tmpi_accessible)
		{
			$tmpi_accounts_sql_by_type = array();
			$tmpi_accounts_bindings_by_type = array();
			$this->get_campaigns_sql_and_bindings_by_type(
				$tmpi_accounts_sql_by_type,
				$tmpi_accounts_bindings_by_type,
				$campaign_set,
				report_campaign_organization::tmpi
			);

			$vl_advertiser_to_tmpi_account_from_sql = "";
			$vl_advertiser_to_tmpi_account_where_sql = "";
			$vl_advertiser_to_tmpi_account_bindings = array();

			$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
				$vl_advertiser_to_tmpi_account_from_sql,
				$vl_advertiser_to_tmpi_account_where_sql,
				$vl_advertiser_to_tmpi_account_bindings,
				$advertiser_id,
			report_campaign_organization::tmpi
			);

			$leads_outer_from_sql = '
				, SUM(outer_leads_value) AS leads
			';

			$tmpi_dates = array($start_date, $end_date);

			$tmpi_common_clicks_select_sql = '
				tdc.captured AS Date,
				SUM(tdc.impressions) AS tot_Imps,
				SUM(tdc.clicks) AS tot_Clks,
				0 AS r_Imps,
				0 AS r_Clks,
				0 AS eng,
				0 AS view_throughs,
				0 AS outer_leads_value
			';
			$tmpi_common_clicks_group_by_sql = '
				GROUP BY Date
			';

			$tmpi_graph_data_by_type = array(
				report_campaign_type::tmpi_clicks => array(
					'sql' => '('.  
						$this->get_targeted_common_clicks_sql(
							$tmpi_common_clicks_select_sql,
							$vl_advertiser_to_tmpi_account_from_sql,
							$vl_advertiser_to_tmpi_account_where_sql,
							$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_clicks],
							$tmpi_common_clicks_group_by_sql,
							$tmpi_dates,
							tmpi_clicks_campaign_type::clicks
						)
						.')',
					'bindings' => $this->get_targeted_common_clicks_bindings(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_clicks],
						$tmpi_dates,
						tmpi_clicks_campaign_type::clicks
					)
				),
				report_campaign_type::tmpi_directories => array(
					'sql' => '
						(
							SELECT 
								tlp.captured as Date,
								SUM(tlp.total) AS tot_Imps,
								0 AS tot_Clks,
								0 AS r_Imps,
								0 AS r_Clks,
								0 AS eng,
								0 AS view_throughs,
								0 AS outer_leads_value
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_local_performance AS tlp
									ON (ta.account_id = tlp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories].'
								AND tlp.captured BETWEEN ? AND ?
								AND (
									tlp.short_name = "Offer Views" OR
									tlp.short_name = "Profile Views" OR
									tlp.short_name = "Review Views"
								)
							GROUP BY Date
						)
						UNION ALL
						(
							SELECT 
								tlp.captured as Date,
								0 AS tot_Imps,
								0 AS tot_Clks,
								0 AS r_Imps,
								0 AS r_Clks,
								SUM(tlp.total) AS eng,
								0 AS view_throughs,
								0 AS outer_leads_value
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_local_performance AS tlp
									ON (ta.account_id = tlp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories].'
								AND tlp.captured BETWEEN ? AND ?
								AND (
									tlp.short_name = "Map Views/Directions" OR
									tlp.short_name = "Printed Info"
								)
							GROUP BY Date
						)
						UNION ALL
						(
							SELECT 
								tlp.captured as Date,
								0 AS tot_Imps,
								SUM(tlp.total) AS tot_Clks,
								0 AS r_Imps,
								0 AS r_Clks,
								0 AS eng,
								0 AS view_throughs,
								0 AS outer_leads_value
							FROM 
								'.$vl_advertiser_to_tmpi_account_from_sql.'
								JOIN tp_tmpi_accounts AS ta
									ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
								JOIN tp_tmpi_local_performance AS tlp
									ON (ta.account_id = tlp.account_id)
							WHERE
								'.$vl_advertiser_to_tmpi_account_where_sql.'
								'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories].'
								AND tlp.captured BETWEEN ? AND ?
								AND (
									tlp.short_name = "Clicks to Websites" OR
									tlp.short_name = "Inventory Clicks" OR
									tlp.short_name = "Appointment Clicks"
								)
							GROUP BY Date
						)
					',
					'bindings' => array_merge(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories],
						$tmpi_dates,

						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories],
						$tmpi_dates,

						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories],
						$tmpi_dates
					),
				),
				report_campaign_type::tmpi_inventory => array(
					'sql' => '
						(
							SELECT 
								leads_union.date as Date,
								0 AS tot_Imps,
								0 AS tot_Clks,
								0 AS r_Imps,
								0 AS r_Clks,
								0 AS eng,
								0 AS view_throughs,
								SUM(leads_union.inner_leads_value) AS outer_leads_value
							FROM
							(
								(
									SELECT 
										tel.captured AS date,
										COUNT(*) AS inner_leads_value
									FROM 
										'.$vl_advertiser_to_tmpi_account_from_sql.'
										JOIN tp_tmpi_accounts AS ta
											ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
										JOIN tp_tmpi_email_leads AS tel
											ON (ta.account_id = tel.account_id)
									WHERE
										'.$vl_advertiser_to_tmpi_account_where_sql.'
										'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory].'
										AND tel.captured BETWEEN ? AND ?
									GROUP BY tel.captured
								)
								UNION ALL
								(
									SELECT 
										tpl.captured AS date,
										COUNT(*) AS inner_leads_value
									FROM 
										'.$vl_advertiser_to_tmpi_account_from_sql.'
										JOIN tp_tmpi_accounts AS ta
											ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = '.report_campaign_organization::tmpi.')
										JOIN tp_tmpi_phone_leads AS tpl
											ON (ta.account_id = tpl.account_id)
									WHERE
										'.$vl_advertiser_to_tmpi_account_where_sql.'
										'.$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory].'
										AND tpl.captured BETWEEN ? AND ?
									GROUP BY tpl.captured
								)
							) AS leads_union
							GROUP BY date
						)
						UNION ALL
						('.
						 $this->get_targeted_common_clicks_sql(
							$tmpi_common_clicks_select_sql,
							$vl_advertiser_to_tmpi_account_from_sql,
							$vl_advertiser_to_tmpi_account_where_sql,
							$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory],
							$tmpi_common_clicks_group_by_sql,
							$tmpi_dates,
							tmpi_clicks_campaign_type::smart_ads
						)
						.')',
					'bindings' => array_merge(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,

						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,

						$this->get_targeted_common_clicks_bindings(
							$vl_advertiser_to_tmpi_account_bindings,
							$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
							$tmpi_dates,
							tmpi_clicks_campaign_type::smart_ads
						)
					)
				)
			);

			$this->resolve_campaign_type_data_to_sql_and_bindings(
				$tmpi_graph_sql,
				$tmpi_graph_bindings,
				$tmpi_accounts_bindings_by_type,
				$tmpi_graph_data_by_type
			);
		}

		$are_view_throughs_accessible = $subfeature_access_permissions['are_view_throughs_accessible'];

		$visits_outer_from_sql = '';
		if($are_view_throughs_accessible || !$is_getting_csv_data)
		{
			$visits_outer_from_sql = '
				, SUM(tot_Clks) + SUM(view_throughs) AS total_visits
			';
		}

		$order_sql = '
			ORDER BY Date ASC
		';
		if($is_getting_csv_data)
		{
			$order_sql = '
				ORDER BY Date DESC
			';
		}

		if(!empty($frequence_sql) && !empty($tmpi_graph_sql))
		{
			$frequence_sql .= "\nUNION ALL\n";
		}

		$response = null;

		if(!empty($frequence_sql) || !empty($tmpi_graph_sql))
		{
			$sql = "
				SELECT U.Date AS date,
				SUM(tot_Imps) AS total_impressions,
				SUM(tot_Clks) AS total_clicks,
				SUM(r_Imps) AS rtg_impressions,
				SUM(r_Clks) AS rtg_clicks
				".$engagements_outer_from_sql."
				".$visits_outer_from_sql."
				".$leads_outer_from_sql."
				FROM 
				(
					".$frequence_sql."
					".$tmpi_graph_sql."
				)
				AS U
				GROUP BY Date
				".$order_sql."
			"; 

			$bindings = array_merge($frequence_bindings, $tmpi_graph_bindings);

			$response = $this->db->query($sql, $bindings);
		}

		return $response;
	}

	private function get_graph_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'desc'));
	}

	private function get_graph_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$core_sorting
	)
	{
		$response = $this->get_graph_data_by_id(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$subfeature_access_permissions,
			true
		);

		return $response;
	}

	public function get_graph_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$table_starting_sorts = $this->get_graph_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$response = $this->get_graph_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$column_class_prefix = 'table_cell_graph_summary';

		$table_headers = array(
			array(
				"Date", 
				new report_table_column_format(
					$column_class_prefix.'_date', 
					'desc', 
					$this->format_string_callable
				),
				""
			),
			array(
				"Total Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_total_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			),
			array(
				"Total Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_total_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			),
			array(
				"Retargeting Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_retargeting_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			),
			array(
				"Retargeting Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_retargeting_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			)
		);

		if($subfeature_access_permissions['are_engagements_accessible'])
		{
			$table_headers[] = array(
				"Engagements", 
				new report_table_column_format(
					$column_class_prefix.'_engagements', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			);
		}

		if($subfeature_access_permissions['are_view_throughs_accessible'])
		{
			$table_headers[] = array(
				"Visits", 
				new report_table_column_format(
					$column_class_prefix.'_visits', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			);
		}

		if($subfeature_access_permissions['is_tmpi_accessible'])
		{
			$table_headers[] = array(
				"Leads", 
				new report_table_column_format(
					$column_class_prefix.'_leads', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			);
		}

		$table_content = null;
		if(!empty($response))
		{
			$table_content = $response->result_array();
		}

		switch($build_data_type)
		{
			case k_build_table_type::html:
				$result = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function build_csv_data(
		$headers,
		$sql_results
	)
	{
		$csv_rows = array();

		$header_row = array();
		foreach($headers as $index => $header_data)
		{
			$header_row[] = $header_data[0];
		}
		$csv_rows[] = $header_row;

		if(!empty($sql_results) && count($sql_results[0]) === count($headers))
		{
			foreach($sql_results as $result_index => $result_row)
			{
				$csv_rows[] = $result_row;
			}
		}

		return $csv_rows;
	}

	private function build_special_format_table_data(
		$headers,
		$input_rows,
		$table_starting_sorts,
		$format_callable
	)
	{
		$table_data = new report_table_data();

		$header = new report_table_header();

		foreach($headers as $index => $header_data)
		{
			$header_cell = new report_table_header_cell();
			$header_cell->html_content = $header_data[0];

			$column_format = $header_data[1];
			$header_cell->initial_sort_order = $column_format->initial_sort_order;
			$header_cell->css_classes = 'table_header_cell '.$column_format->css_class;
			$header->cells[] = $header_cell;
		}
		$table_data->header = $header;

		$output_rows = array();		
		
		if(!empty($input_rows)) // && count($input_rows[0]) === count($headers))
		{
			foreach($input_rows as $result_index => $result_row)
			{
				$table_row = call_user_func_array($format_callable, array($headers, $result_row));
				$output_rows[] = $table_row;
			}
		}

		$table_data->rows = $output_rows;
		$table_data->starting_sorts = $table_starting_sorts;

		return $table_data;
	}

	private function build_simple_table_data(
		$headers,
		$sql_results,
		$table_starting_sorts
	)
	{
		$table_data = new report_table_data();

		$header = new report_table_header();

		foreach($headers as $index => $header_data)
		{
			$header_cell = new report_table_header_cell();
			$header_cell->html_content = '<div class="report_tooltip" data-content="'.$header_data[2].'" data-trigger="hover" data-placement="top" data-delay="200">'.$header_data[0].'</div>';
			$header_cell->tooltip = $header_data[2];

			$column_format = $header_data[1];
			$header_cell->initial_sort_order = $column_format->initial_sort_order;
			$header_cell->css_classes = 'table_header_cell '.$column_format->css_class;
			$header->cells[] = $header_cell;
		}
		$table_data->header = $header;

		$rows = array();		
		
		if(!empty($sql_results) && count($sql_results[0]) === count($headers))
		{
			foreach($sql_results as $result_index => $result_row)
			{
				$table_row = new report_table_row();
				$cell_index = 0;
				foreach($result_row as $row_key => $result_cell)
				{
					$column_format = $headers[$cell_index][1];

					$table_cell = new report_table_cell();
					$formatted_content = call_user_func_array(
						$column_format->format_function,
						array($result_cell)
					);

					$table_cell->html_content = $formatted_content;

					$table_cell->css_classes = 'table_body_cell '.$column_format->css_class;
					$table_cell->link = null;
					$table_row->cells[] = $table_cell;
					$cell_index++;
				}

				$rows[] = $table_row;
			}
		}

		$table_data->rows = $rows;
		$table_data->starting_sorts = $table_starting_sorts;

		return $table_data;
	}

	private function get_sorting_sql($sorting_set)
	{
		$sort_sql = "";

		if($sorting_set !== null)
		{
			$sort_sql = "ORDER BY ";
			foreach($sorting_set as $index => $sorting_item)
			{
				if($index !== 0)
				{
					$sort_sql .= ", ";
				}

				$one_based_column_index = 1 + $sorting_item->column_index;
				$sort_sql .= $one_based_column_index." ".strtoupper($sorting_item->column_order);
			}
		}
		
		return $sort_sql;
	}

	public function get_video_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$response = $this->get_video_core_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions
		);

		$column_class_prefix = 'table_cell_pre_roll';

		$table_headers = array(
			array(
				"", 
				new report_table_column_format(
					$column_class_prefix.'_ad_size', 
					'desc', 
					$this->format_string_callable
				),
				""
			),
			array(
				"Start", 
				new report_table_column_format(
					$column_class_prefix.'_impressions', 
					'desc', 
					$this->format_string_callable
				),
				"Pre-roll videos that started playing"
			),
			array(
				"25% Completed", 
				new report_table_column_format(
					$column_class_prefix.'_clicks', 
					'desc', 
					$this->format_string_callable
				),
				"Pre-roll videos that reached 25% complete"
			),
			array(
				"50% Completed", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_string_callable
				),
				"Pre-roll videos that reached 50% complete"
			),
			array(
				"75% Completed", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_string_callable
				),
				"Pre-roll videos that reached 75% complete"
			),
			array(
				"100% Completed", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_string_callable
				),
				"Pre-roll videos that completed"
			)
		);

		$query_results = $response->result_array();
		$impressions = $query_results[0]['impressions'];
		$table_content = array();
		if($impressions > 0)
		{
			$video_data_counts = array(
									"Impressions",
									$this->format_whole_number($impressions),
									$this->format_whole_number($query_results[0]['25_percent_viewed']),
									$this->format_whole_number($query_results[0]['50_percent_viewed']),
									$this->format_whole_number($query_results[0]['75_percent_viewed']),
									$this->format_whole_number($query_results[0]['100_percent_viewed'])
								);
			$video_data_percentages = array(
									"Percentages",
									$this->format_percent(100.0),
									$impressions > 0 ? $this->format_percent($query_results[0]['25_percent_viewed']/$impressions*100) : "N/A",
									$impressions > 0 ? $this->format_percent($query_results[0]['50_percent_viewed']/$impressions*100) : "N/A",
									$impressions > 0 ? $this->format_percent($query_results[0]['75_percent_viewed']/$impressions*100) : "N/A",
									$impressions > 0 ? $this->format_percent($query_results[0]['100_percent_viewed']/$impressions*100) : "N/A"
								);
			$table_content = array($video_data_percentages, $video_data_counts);
		}
		$table_starting_sorts = $this->get_video_sorting();
		
		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				throw new Exception("CSVs for Pre-Roll data not permitted.");
				$result = false;
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function get_table_subproduct_result(
		$table_data,
		$heatmap_geojson = null
	)
	{
		$html_scaffolding = $heatmap_geojson ? '<div id="heatmap_container"></div>' : '';
		$html_scaffolding .= '
			<div id="subproduct_table" class="geography">
			</div>
		';

		$table_data_set = array(
			'display_function' => 'build_subproduct_table',
			'display_selector' => '#subproduct_table',
			'display_data'     => $table_data
		);

		$data_sets = array(
			$table_data_set
		);

		if($heatmap_geojson)
		{
			$heatmap_data_set = array(
				'display_function' => 'build_heatmap_from_data',
				'display_selector' => '#heatmap_container',
				'display_data' => $heatmap_geojson
			);
			$data_sets[] = $heatmap_data_set;
		}

		$result = array(
			'subproduct_html_scaffolding' => $html_scaffolding,
			'subproduct_data_sets' => $data_sets
		);

		return $result;
	}
	
	public function get_video_core_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$mode=null
	)
	{
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_pre_roll
		);
		if ($mode == 'OVERVIEW' && $this->make_bindings_sql_from_array($campaign_ids) == "")
			return null;
		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';

		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));		

		$query = "		
			SELECT 
				SUM(impressions) AS impressions,
				SUM(starts) AS video_started,
				SUM(25_percents) AS 25_percent_viewed,
				SUM(50_percents) AS 50_percent_viewed,
				SUM(75_percents) AS 75_percent_viewed,
				SUM(100_percents) AS 100_percent_viewed
			FROM
			(
				(
					SELECT 
						0 AS impressions,
						SUM(vid_d.start_count) AS starts,
						SUM(vid_d.25_percent_count) AS 25_percents,
						SUM(vid_d.50_percent_count) AS 50_percents,
						SUM(vid_d.75_percent_count) AS 75_percents,
						SUM(vid_d.100_percent_count) AS 100_percents

					FROM 
						AdGroups AS adg
						JOIN report_video_play_records AS vid_d
							ON (adg.ID = vid_d.AdGroupID)
						JOIN Campaigns AS cmp
							ON (adg.campaign_id = cmp.id)
					WHERE
						cmp.business_id = ? AND
						adg.campaign_id IN $campaigns_sql AND
						adg.target_type LIKE \"%Pre-Roll%\" AND
						vid_d.date BETWEEN ? AND ?
				)
				UNION ALL
				(
					SELECT
						SUM(crd.impressions),
						0 AS starts,
						0 AS 25_percents,
						0 AS 50_percents,
						0 AS 75_percents,
						0 AS 100_percents
					FROM
						AdGroups AS adg
						JOIN report_cached_adgroup_date AS crd
							ON (adg.ID = crd.adgroup_id)
						JOIN Campaigns AS cmp
							ON (adg.campaign_id = cmp.id)
						JOIN report_video_play_records AS vid
							ON (crd.date = vid.date AND crd.adgroup_id = vid.AdGroupID)
					WHERE
						cmp.business_id = ? AND
						adg.campaign_id IN $campaigns_sql AND
						adg.target_type LIKE \"%Pre-Roll%\" AND
						crd.date BETWEEN ? AND ?
				)
			) AS u
		";

		$advertiser_bindings = array(
			$advertiser_id
		);

		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$bindings = array_merge(
			$advertiser_bindings,
			$campaign_ids,
			$date_bindings,

			$advertiser_bindings,
			$campaign_ids,
			$date_bindings
		);
		$response = $this->db->query($query, $bindings);
		return $response;
	}
	
	private function get_video_sorting()
	{
		return null;
	}

	private function add_vl_advertiser_to_third_party_account_sql_and_bindings(
		&$from_sql,
		&$where_sql,
		&$bindings,
		$vl_advertiser_id,
		$third_party_source
	)
	{
		$from_sql .= "
				tp_advertisers_join_third_party_account AS ajtpa
		";
		$where_sql .= "
					ajtpa.frq_advertiser_id = ?
					AND ajtpa.third_party_source = ?
		";

		$bindings = array_merge($bindings, array($vl_advertiser_id, $third_party_source));
	}
	
	public function get_report_campaigns($advertiser_id)
	{
		$campaigns = array();
				
		$frequence_campaigns_sql = '
			SELECT
				cmp.Name AS campaign_name,
				IF(GROUP_CONCAT(ag.target_type) LIKE "%Pre-Roll%", "'.frequence_product_names::pre_roll.'", "'.frequence_product_names::display.'") AS type,
				cmp.id AS campaign_id
			FROM
				Campaigns AS cmp
				JOIN AdGroups AS ag
					ON (cmp.id = ag.campaign_id)
			WHERE
				cmp.business_id = ?
			GROUP BY
				cmp.id
		';
		$frequence_campaigns_reponse = $this->db->query($frequence_campaigns_sql, $advertiser_id);
		$frequence_campaigns_rows = $frequence_campaigns_reponse->result();

		$frequence_group = report_organization_names::frequence;
		foreach($frequence_campaigns_rows as $index => $row)
		{
			$campaigns[] = new report_campaign(
				$frequence_group,
				$row->type,
				$row->campaign_id,
				$row->campaign_name
			);
		}

		$tmpi_account_info_sql = 'ta.customer AS customer,';
		$tmpi_campaigns_response = $this->get_tmpi_account_data_presence(
			$advertiser_id,
			$tmpi_account_info_sql
		);

		if(!empty($tmpi_campaigns_response))
		{
			$tmpi_campaigns_rows = $tmpi_campaigns_response->result();

			foreach($tmpi_campaigns_rows as $index => $row)
			{
				$account_types = array();

				if($row->has_targeted_clicks == 1)
				{
					$account_types[] = tmpi_product_names::clicks;
				}

				if($row->has_targeted_inventory == 1)
				{
					$account_types[] = tmpi_product_names::inventory;
				}

				if($row->has_targeted_directories == 1)
				{
					$account_types[] = tmpi_product_names::directories;
				}

				$tmpi_group = report_organization_names::tmpi;
				
				foreach($account_types as $account_type)
				{
					$campaigns[] = new report_campaign(
						$tmpi_group,
						$account_type,
						$row->account_id,
						$row->customer
					);
				}
			}
		}

		$carmercial_account_info_sql = 'cd.friendly_dealer_name AS friendly_dealer_name,';
		$carmercial_campaigns_response = $this->get_carmercial_data_presence(
			$advertiser_id,
			$carmercial_account_info_sql
		);

		if(!empty($carmercial_campaigns_response))
		{
			$carmercial_campaigns_rows = $carmercial_campaigns_response->result();

			foreach($carmercial_campaigns_rows as $index => $row)
			{
				$account_types = array();

				if($row->has_targeted_content == 1)
				{
					$account_types[] = carmercial_product_names::content;
				}

				$carmercial_group = report_organization_names::carmercial;
				
				foreach($account_types as $account_type)
				{
					$campaigns[] = new report_campaign(
						$carmercial_group,
						$account_type,
						$row->dealer_id,
						$row->friendly_dealer_name
					);
				}
			}
		}

		return $campaigns;
	}

	public function get_tmpi_targeted_inventory_leads_totals_core_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$ytd_year = date("Y", strtotime($raw_end_date));
		$ytd_start_date = $ytd_year."-01-01";
		$ytd_end_date = $ytd_year."-12-31";

		$sort_sql = $this->get_sorting_sql($sorting);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_inventory
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$sql = "
			SELECT 
				lead_type,
				SUM(actions) AS actions,
				SUM(ytd) AS ytd
			FROM
			(
				(
					SELECT 
						'Email contacts' AS lead_type,
						COUNT(*) AS actions,
						0 AS ytd
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_email_leads AS tel
							ON (ta.account_id = tel.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tel.captured BETWEEN ? AND ?
				)
				UNION ALL
				(
					SELECT 
						'Email contacts' AS lead_type,
						0 AS actions,
						COUNT(*) AS ytd
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_email_leads AS tel
							ON (ta.account_id = tel.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tel.captured BETWEEN ? AND ?
				)
				UNION ALL
				(
					SELECT 
						'Phone calls' AS lead_type,
						COUNT(*) AS actions,
						0 AS ytd
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_phone_leads AS tpl
							ON (ta.account_id = tpl.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tpl.captured BETWEEN ? AND ?
				)
				UNION ALL
				(
					SELECT 
						'Phone calls' AS lead_type,
						0 AS actions,
						COUNT(*) AS ytd
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_phone_leads AS tpl
							ON (ta.account_id = tpl.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tpl.captured BETWEEN ? AND ?
				)
			) AS leads_rows
			GROUP BY lead_type
			".$sort_sql."
		";

		$date_range_bindings = array($start_date, $end_date);
		$ytd_bindings = array($ytd_start_date, $ytd_end_date);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_range_bindings,

			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$ytd_bindings,

			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_range_bindings,

			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$ytd_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_tmpi_targeted_inventory_leads_totals_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'asc'));
	}

	public function get_tmpi_targeted_inventory_leads_totals_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$column_class_prefix = 'table_cell_targeted_inventory_leads_totals';

		$table_starting_sorts = $this->get_tmpi_targeted_inventory_leads_totals_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$table_headers = array(
			array(
				"Performance Summary", 
				new report_table_column_format(
					$column_class_prefix.'_performance_summary', 
					'asc', 
					$this->format_string_callable
				),
				"Lead type"
			),
			array(
				"Actions", 
				new report_table_column_format(
					$column_class_prefix.'_actions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Total actions in selected date range"
			),
			array(
				"YTD", 
				new report_table_column_format(
					$column_class_prefix.'_ytd', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Total actions year-to-date"
			)
		);

		$response = $this->get_tmpi_targeted_inventory_leads_totals_core_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function format_leads_details_row($headers, $result_row)
	{
		$table_row = new report_table_row();

		assert(count($result_row) === 8);

		$date_column_format = $headers[0][1];
		$date_cell = new report_table_cell();
		$date_cell->html_content = $result_row['date'];
		$date_cell->css_classes = 'table_body_cell '.$date_column_format->css_class;
		$table_row->cells[] = $date_cell;

		$lead_type_column_format = $headers[1][1];
		$lead_type_cell = new report_table_cell();
		$lead_type_cell->html_content = $result_row['lead_type'];
		$lead_type_cell->css_classes = 'table_body_cell '.$lead_type_column_format->css_class;
		$table_row->cells[] = $lead_type_cell;

		$contact_column_format = $headers[2][1];
		$contact_cell = new report_table_cell();
		$contact_cell->html_content = $result_row['contact'];
		$contact_cell->css_classes = 'table_body_cell '.$contact_column_format->css_class;
		$table_row->cells[] = $contact_cell;

		$name_column_format = $headers[3][1];
		$name_cell = new report_table_cell();
		$name_cell->html_content = $result_row['name'];
		$name_cell->css_classes = 'table_body_cell '.$name_column_format->css_class;
		$table_row->cells[] = $name_cell;

		$table_row->hidden_data = array(
			'date' => $result_row['date'],
			'name' => $result_row['name'],
			'email' => $result_row['email'],
			'phone' => $result_row['phone'],
			'zip_code' => $result_row['zip_code'],
			'comment' => $result_row['comment']
		);

		return $table_row;
	}

	public function get_tmpi_targeted_inventory_leads_details_core_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$sort_sql = $this->get_sorting_sql($sorting);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_inventory
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$sql = "
			SELECT 
				*
			FROM
			(
				(
					SELECT 
						tel.captured AS date,
						'Email' AS lead_type,
						tel.from_mail AS contact,
						CONCAT(tel.first_name, ' ', tel.last_name) AS name,
						tel.from_mail AS email,
						tel.phone AS phone,
						tel.zip_code AS zip_code,
						tel.comment AS comment
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_email_leads AS tel
							ON (ta.account_id = tel.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tel.captured BETWEEN ? AND ?
				)
				UNION ALL
				(
					SELECT 
						tpl.captured AS date,
						'Phone' AS lead_type,
						tpl.phone AS contact,
						CONCAT(tpl.first_name, ' ', tpl.last_name) AS name,
						NULL AS email,
						tpl.phone AS phone,
						tpl.zip_code AS zip_code,
						NULL AS comment
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_phone_leads AS tpl
							ON (ta.account_id = tpl.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tpl.captured BETWEEN ? AND ?
				)
			) AS relevant_leads
			ORDER BY date DESC, name ASC, lead_type ASC
		";

		$date_bindings = array($start_date, $end_date);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_bindings,

			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_tmpi_targeted_inventory_leads_details_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'desc'));
	}

	public function get_tmpi_targeted_inventory_leads_details_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$table_starting_sorts = $this->get_tmpi_targeted_inventory_leads_details_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$column_class_prefix = 'table_cell_targeted_inventory_leads_details';

		$table_headers = array(
			array(
				"Date", 
				new report_table_column_format(
					$column_class_prefix.'_date', 
					'desc', 
					$this->format_string_callable
				),
				""
			),
			array(
				"Lead Type", 
				new report_table_column_format(
					$column_class_prefix.'_lead_type', 
					'asc', 
					$this->format_string_callable
				),
				"Type of Lead"
			),
			array(
				"Contact", 
				new report_table_column_format(
					$column_class_prefix.'_contact', 
					'asc', 
					$this->format_string_callable
				),
				"Contact Information"
			),
			array(
					"Name", 
				new report_table_column_format(
					$column_class_prefix.'_name', 
					'asc', 
					$this->format_string_callable
				),
				"Name of Lead"
			)
		);

		$response = $this->get_tmpi_targeted_inventory_leads_details_core_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$format_callable = array($this, 'format_leads_details_row');
				$table_data = $this->build_special_format_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts,
					$format_callable
				);
				$table_data->row_javascript_click_function_name = "inventory_lead_details_click_function";
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	private function get_tmpi_targeted_inventory_search_and_listings_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'asc'));
	}

	public function get_tmpi_targeted_inventory_search_and_listings_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions
	)
	{
		$result = array();

 		// data in database is by month so use day 01 to mark the month
		$start_month_first_day = date("Y-m-01", strtotime($raw_start_date));
		$end_month_last_day = date("Y-m-t", strtotime($raw_end_date));
		$start_year_first_day = date("Y-01-01", strtotime($raw_start_date));
		$end_year_last_day = date("Y-12-31", strtotime($raw_end_date));

		$column_class_prefix = 'table_cell_targeted_inventory_search_and_listings';

		$table_starting_sorts = $this->get_tmpi_targeted_inventory_search_and_listings_sorting();

		$table_headers = array(
			array(
				"Exposure Type", 
				new report_table_column_format(
					$column_class_prefix.'_exposure', 
					'desc', 
					$this->format_string_callable
				),
				"Exposure type"
			),
			array(
				"MTD", 
				new report_table_column_format(
					$column_class_prefix.'_views', 
					'asc', 
					$this->format_whole_number_callable
				),
				"Month-to-date Views"
			),
			array(
				"YTD", 
				new report_table_column_format(
					$column_class_prefix.'_ytd', 
					'asc', 
					$this->format_whole_number_callable
				),
				"Year-to-date views"
			)
		);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_inventory
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$k_name_key = 'name';
		$k_monthly_views_key = 'month_total';
		$k_ytd_views_key = 'year_total';

		$sql = "
			SELECT 
				tsdp_month.short_name AS $k_name_key,
				tsdp_month.months_total AS $k_monthly_views_key,
				tsdp_year.years_total AS $k_ytd_views_key
			FROM 
				(
					SELECT 
						ta.account_id AS account_id,
						tsdp.short_name AS short_name,
						SUM(tsdp.month_total) AS months_total
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_search_detail_performance AS tsdp
							ON (ta.account_id = tsdp.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql AND
						tsdp.report_month BETWEEN ? AND ?
					GROUP BY
						tsdp.account_id,
						tsdp.short_name
				) AS tsdp_month
				JOIN
				(
					SELECT 
						ta.account_id AS account_id,
						tsdp.short_name AS short_name,
						SUM(tsdp.month_total) AS years_total
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_search_detail_performance AS tsdp
							ON (ta.account_id = tsdp.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql AND
						tsdp.report_month BETWEEN ? AND ?
					GROUP BY
						tsdp.account_id,
						tsdp.short_name
				) AS tsdp_year
				ON (tsdp_month.account_id = tsdp_year.account_id && 
					tsdp_month.short_name = tsdp_year.short_name
				)
			GROUP BY 
				tsdp_month.account_id,
				tsdp_month.short_name
		";

		$month_range_bindings = array($start_month_first_day, $end_month_last_day);
		$year_range_bindings = array($start_year_first_day, $end_year_last_day);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$month_range_bindings,
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$year_range_bindings
		);

		$response = $this->db->query($sql, $bindings);
		if($response->num_rows() > 0)
		{
			$table_content = $response->result_array();
			
			$totals_row = array(
				$k_name_key		  => 'Total Views',
				$k_monthly_views_key => 0,
				$k_ytd_views_key	 => 0
			);

			assert(count($totals_row) == count($table_content[0]), 'Miss matched content, update $total_rows');

			foreach($table_content as $row_index => $row)
			{
				$totals_row[$k_monthly_views_key] += $row[$k_monthly_views_key];
				$totals_row[$k_ytd_views_key] += $row[$k_ytd_views_key];
			}

			$table_content[] = $totals_row;

			$result = $this->build_simple_table_data(
				$table_headers,
				$table_content,
				$table_starting_sorts
			);
		}
		else
		{
			$result = $this->build_simple_table_data(
				$table_headers, 
				array(),
				$table_starting_sorts
			);
		}

		return $this->get_table_subproduct_result($result);
	}

	private function get_tmpi_targeted_clicks_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'asc'));
	}

	private function get_tmpi_targeted_geo_clicks_sorting()
	{
		return array(
			new report_table_starting_column_sort(5, 'desc'),
		);
	}

	public function get_tmpi_targeted_clicks_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions
	)
	{
		return $this->get_tmpi_targeted_common_clicks_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			tmpi_clicks_campaign_type::clicks,
			tmpi_clicks_data_set_type::summary
		);
	}

	public function get_tmpi_targeted_smart_ads_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions
	)
	{
		return $this->get_tmpi_targeted_common_clicks_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			tmpi_clicks_campaign_type::smart_ads,
			tmpi_clicks_data_set_type::summary
		);
	}

	public function get_tmpi_targeted_geo_clicks_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions
	)
	{
		return $this->get_tmpi_targeted_common_clicks_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			tmpi_clicks_campaign_type::clicks,
			tmpi_clicks_data_set_type::geo
		);
	}

	public function get_tmpi_targeted_smart_ads_geo_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions
	)
	{
		return $this->get_tmpi_targeted_common_clicks_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			tmpi_clicks_campaign_type::smart_ads,
			tmpi_clicks_data_set_type::geo
		);
	}

	private function get_tmpi_targeted_common_clicks_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$tmpi_clicks_campaign_type,
		$tmpi_clicks_data_set_type
	)
	{
		$table_data = array();

		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$date_bindings = array($start_date, $end_date);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = array();
		switch($tmpi_clicks_campaign_type)
		{
			case tmpi_clicks_campaign_type::clicks:
				$tmpi_account_ids = $campaign_set->get_campaign_ids(
					report_campaign_organization::tmpi,
					report_campaign_type::tmpi_clicks
				);
				break;
			case tmpi_clicks_campaign_type::smart_ads:
				$tmpi_account_ids = $campaign_set->get_campaign_ids(
					report_campaign_organization::tmpi,
					report_campaign_type::tmpi_inventory
				);
				break;
			default:
				throw new Exception("Unhandled tmpi click type: ".$tmpi_clicks_campaign_type);
		}

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);
		
		$column_class_prefix = 'table_cell_targeted_clicks_clicks';

		$table_starting_sorts = array();

		$table_headers = array();

		$table_headers_first_set = array(
			array(
				"Campaign", 
				new report_table_column_format(
					$column_class_prefix.'_tmpi_campaign', 
					'asc', 
					$this->format_string_callable
				),
				"Campaign name"
			)
		);

		$table_headers_middle_set = array(
			array(
				"Impressions", 
				new report_table_column_format(
					$column_class_prefix.'_impressions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Ads shown to users"
			)
		);

		$table_headers_last_set = array(
			array(
				"Clicks", 
				new report_table_column_format(
					$column_class_prefix.'_clicks', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Clicks on ads leading to a visit to conversion pages"
			),
			array(
				"Click Rate", 
				new report_table_column_format(
					$column_class_prefix.'_click_rate', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of impressions which lead to a click (clicks / impressions)"
			)
		);

		$table_headers_region_data_set_specific = array();
		$table_headers_impression_percent_data_set_specific = array();
		$select_data_set_specific_sql = "";
		$from_data_set_specific_sql = "";
		$group_by_data_set_specific_sql = "";
		$impressions_percent_of_total_from_sql = "";
		$impressions_percent_of_total_subquery_sql = "";
		$impressions_percent_of_total_subquery_bindings = array();

		switch($tmpi_clicks_data_set_type)
		{
			case tmpi_clicks_data_set_type::summary:
				$table_headers_region_data_set_specific = array();
				$table_headers_impression_percent_data_set_specific = array();
				$select_data_set_specific_sql = "";
				$from_data_set_specific_sql = "tp_tmpi_display_clicks ";
				$group_by_data_set_specific_sql = "";
				$table_starting_sorts = $this->get_tmpi_targeted_clicks_sorting();
				$impressions_percent_of_total_from_sql = "";
				$impressions_percent_of_total_subquery_sql = "";
				$impressions_percent_of_total_subquery_bindings = array();
				
				break;
			case tmpi_clicks_data_set_type::date_raw_data:
				$table_headers_region_data_set_specific = array();
				$table_headers_impression_percent_data_set_specific = array();
				$select_data_set_specific_sql = "tdc.captured 'date',";
				$from_data_set_specific_sql = "tp_tmpi_display_clicks ";
				$group_by_data_set_specific_sql = ", tdc.captured ";
				$table_starting_sorts = $this->get_tmpi_targeted_clicks_sorting();
				$impressions_percent_of_total_from_sql = "";
				$impressions_percent_of_total_subquery_sql = "";
				$impressions_percent_of_total_subquery_bindings = array();
				
				break;	
			case tmpi_clicks_data_set_type::geo:
				$table_headers_region_data_set_specific = array(
					array(
						"City",
						new report_table_column_format(
							$column_class_prefix.'_city',
							'asc',
							$this->format_string_callable
						),
						"City"
					),
					array(
						"Region",
						new report_table_column_format(
							$column_class_prefix.'_region',
							'asc',
							$this->format_string_callable
						),
						"Region"
					)
				);

				$table_headers_impression_percent_data_set_specific = array(
					array(
						"% of Campaign Impressions", 
						new report_table_column_format(
							$column_class_prefix.'_impressions_percent', 
							'desc', 
							$this->format_percent_callable
						),
						"Percent of Campaign Impressions for City/Region"
					)
				);

				$select_data_set_specific_sql = "
					tdc.city,
					tdc.region,
				";
				$from_data_set_specific_sql = "tp_tmpi_display_geo_clicks ";
				$group_by_data_set_specific_sql = "
					,
					tdc.region,
					tdc.city
				";

				$display_clicks_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids, "tdc_total.account_id IN ");

				$impressions_percent_of_total_from_sql = "100 * SUM(tdc.Impressions) / tdc_totals.total_impressions AS percent_of_total,";
				$impressions_percent_of_total_subquery_sql = "
					JOIN (
						SELECT 
							tdc_total.record_master_id AS record_master_id,
							SUM(tdc_total.impressions) AS total_impressions
						FROM
							tp_tmpi_display_clicks AS tdc_total
						WHERE
							$display_clicks_account_ids_sql
							AND tdc_total.campaign_type = $tmpi_clicks_campaign_type
							AND tdc_total.captured BETWEEN ? AND ?
						GROUP BY
							tdc_total.account_id,
							tdc_total.record_master_id
					) AS tdc_totals ON (tdc.record_master_id = tdc_totals.record_master_id)
				";
				$impressions_percent_of_total_subquery_bindings = array_merge($tmpi_account_ids, $date_bindings);

				$table_starting_sorts = $this->get_tmpi_targeted_geo_clicks_sorting();

				break;
			default:
				throw new Exception("Unhandled tmpi_clicks_data_set_type: ".$tmpi_clicks_data_set_type);

				break;
		}

		$table_headers = array_merge(
			$table_headers_first_set,
			$table_headers_region_data_set_specific,
			$table_headers_middle_set,
			$table_headers_impression_percent_data_set_specific,
			$table_headers_last_set
		);

		$sql = "
			SELECT 
				tdc.record_campaign_name AS tmpi_campaign_name,
				$select_data_set_specific_sql
				SUM(tdc.impressions) AS impressions,
				$impressions_percent_of_total_from_sql
				SUM(tdc.clicks) AS clicks
			FROM 
				$vl_advertiser_to_tmpi_account_from_sql
				JOIN tp_tmpi_accounts AS ta
					ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
				JOIN $from_data_set_specific_sql AS tdc 
					ON (ta.account_id = tdc.account_id)
				$impressions_percent_of_total_subquery_sql
			WHERE
				$vl_advertiser_to_tmpi_account_where_sql
				$tmpi_account_ids_sql
				AND tdc.campaign_type = $tmpi_clicks_campaign_type
				AND tdc.captured BETWEEN ? AND ?
			GROUP BY 
				tdc.account_id,
				tdc.record_master_id
				$group_by_data_set_specific_sql
		";

		$bindings = array_merge(
			$impressions_percent_of_total_subquery_bindings,
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_bindings
		);
		$response = $this->db->query($sql, $bindings);
		if ($tmpi_clicks_data_set_type == tmpi_clicks_data_set_type::date_raw_data)
		{
			return $response;
		}
		else
		{
			if($response->num_rows() > 0)
			{
				$table_content = $response->result_array();
				foreach($table_content as $index => &$row)
				{
					if($row['impressions'] != 0)
					{
						$row['click_rate'] = 100 * $row['clicks'] / $row['impressions'];
					}
					else
					{
						$row['click_rate'] = 0;
					}
				}

				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
			}
			else
			{
				$table_data = $this->build_simple_table_data(
					$table_headers, 
					array(),
					$table_starting_sorts
				);
			}

			return $this->get_table_subproduct_result($table_data);
		}
	}

	public function get_tmpi_targeted_directories_local_search_profile_activity_core_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$sort_sql = $this->get_sorting_sql($sorting);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_directories
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$sql = "
			SELECT 
				tlp.short_name AS short_name,
				SUM(tlp.total) AS total
			FROM 
				$vl_advertiser_to_tmpi_account_from_sql
				JOIN tp_tmpi_accounts AS ta
					ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
				JOIN tp_tmpi_local_performance AS tlp
					ON (ta.account_id = tlp.account_id)
			WHERE
				$vl_advertiser_to_tmpi_account_where_sql
				$tmpi_account_ids_sql
				AND tlp.captured BETWEEN ? AND ?
			GROUP BY short_name
			".$sort_sql."
		";

		$date_range_bindings = array($start_date, $end_date);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_range_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_tmpi_targeted_directories_local_search_profile_activity_sorting()
	{
		return array(new report_table_starting_column_sort(1, 'desc'));
	}

	public function get_tmpi_targeted_directories_local_search_profile_activity_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$column_class_prefix = 'table_cell_targeted_directories_local_search_profile_activity';

		$table_starting_sorts = $this->get_tmpi_targeted_directories_local_search_profile_activity_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$table_headers = array(
			array(
				"Local Search - Profile Activity", 
				new report_table_column_format(
					$column_class_prefix.'_local_search_profile_activity', 
					'asc', 
					$this->format_string_callable
				),
				"Activity types"
			),
			array(
				"Actions", 
				new report_table_column_format(
					$column_class_prefix.'_actions', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Total actions by activity type"
			)
		);

		$response = $this->get_tmpi_targeted_directories_local_search_profile_activity_core_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	public function get_tmpi_targeted_inventory_top_vehicles_core_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$sort_sql = $this->get_sorting_sql($sorting);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_inventory
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$sql = "
			SELECT 
					thlp_list.searched_item AS searched_item,
					thlp_list.num_searches AS num_searches,
					thlp_list.num_ad_details AS num_ad_details,
					thlp_list.item_price AS item_price
			FROM
			(
				SELECT 
					thlp.listing_id AS listing_id,
					thlp.listing_text AS searched_item,
					thlp.listing_search AS num_searches,
					thlp.listing_details AS num_ad_details,
					thlp.listing_price AS item_price
				FROM 
					$vl_advertiser_to_tmpi_account_from_sql
					JOIN tp_tmpi_accounts AS ta
						ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
					JOIN tp_tmpi_hi_lo_performance AS thlp
						ON (ta.account_id = thlp.account_id)
				WHERE
					$vl_advertiser_to_tmpi_account_where_sql
					$tmpi_account_ids_sql
					AND thlp.date BETWEEN ? AND ?
				ORDER BY
					date DESC
			) AS thlp_list
			GROUP BY
				thlp_list.listing_id
			".$sort_sql."
		";

		$date_range_bindings = array($start_date, $end_date);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_range_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_tmpi_targeted_inventory_top_vehicles_sorting()
	{
		return array(new report_table_starting_column_sort(1, 'desc'));
	}

	public function get_tmpi_targeted_inventory_top_vehicles_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$column_class_prefix = 'table_cell_targeted_inventory_top_vehicles';

		$table_starting_sorts = $this->get_tmpi_targeted_inventory_top_vehicles_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$table_headers = array(
			array(
				"Top Searched", 
				new report_table_column_format(
					$column_class_prefix.'_top_searched_items', 
					'asc', 
					$this->format_string_callable
				),
				"Vehcile details"
			),
			array(
				"Search Results", 
				new report_table_column_format(
					$column_class_prefix.'_search_results', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Total number of searches by vehicle listing"
			),
			array(
				"Ad Details", 
				new report_table_column_format(
					$column_class_prefix.'_ad_details', 
					'desc', 
					$this->format_whole_number_callable
				),
				""
			),
			array(
				"Price", 
				new report_table_column_format(
					$column_class_prefix.'_price', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Vehicle listing price"
			)
		);

		$response = $this->get_tmpi_targeted_inventory_top_vehicles_core_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	public function get_tmpi_targeted_inventory_vehicles_inventory_core_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$sort_sql = $this->get_sorting_sql($sorting);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_inventory
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$sql = "
			SELECT 
				tvs.performance_report_name AS item_name,
				SUM(tvs.total) AS item_total,
				100 * (SUM(tvs.total) / SUM(tvs.count)) AS item_percent
			FROM 
				$vl_advertiser_to_tmpi_account_from_sql
				JOIN tp_tmpi_accounts AS ta
					ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
				JOIN tp_tmpi_vehicle_summaries AS tvs
					ON (ta.account_id = tvs.account_id)
				JOIN 
				(
					SELECT
						ta.account_id AS account_id,
						MAX(tvs.date) AS date
					FROM
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_vehicle_summaries AS tvs
							ON (ta.account_id = tvs.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND tvs.date BETWEEN ? AND ?
					GROUP BY
						account_id
				) AS most_recent
					ON (ta.account_id = most_recent.account_id AND
						tvs.date = most_recent.date)
			WHERE
				$vl_advertiser_to_tmpi_account_where_sql
			GROUP BY 
				item_name
			".$sort_sql."
		";

		$date_range_bindings = array($start_date, $end_date);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_range_bindings,
			$vl_advertiser_to_tmpi_account_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_tmpi_targeted_inventory_vehicles_inventory_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'asc'));
	}

	public function get_tmpi_targeted_inventory_vehicles_inventory_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$column_class_prefix = 'table_cell_targeted_inventory_vehicles_inventory';

		$table_starting_sorts = $this->get_tmpi_targeted_inventory_vehicles_inventory_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$table_headers = array(
			array(
				"Vehicle Inventory Summary", 
				new report_table_column_format(
					$column_class_prefix.'_name', 
					'asc', 
					$this->format_string_callable
				),
				"Inventory category"
			),
			array(
				"Count", 
				new report_table_column_format(
					$column_class_prefix.'_count', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Number of vehicles in the category"
			),
			array(
				"%", 
				new report_table_column_format(
					$column_class_prefix.'_percent', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of vehicles in the category"
			)
		);

		$response = $this->get_tmpi_targeted_inventory_vehicles_inventory_core_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	public function get_tmpi_targeted_inventory_price_breakdown_core_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$sort_sql = $this->get_sorting_sql($sorting);

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::tmpi
		);

		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			report_campaign_type::tmpi_inventory
		);

		$tmpi_account_ids_sql = $this->generate_sql_for_ids($tmpi_account_ids);

		$sql = "
			SELECT
				thlp_price_range.listing_price_range AS price_range,
				COUNT(thlp_price_range.listing_price_range) AS price_count
			FROM
			(
				SELECT 
					thlp_list.listing_price_range AS listing_price_range
				FROM
				(
					SELECT 
						thlp.listing_id AS listing_id,
						thlp.listing_price_range AS listing_price_range
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_hi_lo_performance AS thlp
							ON (ta.account_id = thlp.account_id)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND thlp.date BETWEEN ? AND ?
					ORDER BY
						date DESC
				) AS thlp_list
				GROUP BY
					thlp_list.listing_id
			) AS thlp_price_range
			GROUP BY
				thlp_price_range.listing_price_range
			".$sort_sql."
		";

		$date_range_bindings = array($start_date, $end_date);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$date_range_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_tmpi_targeted_inventory_price_breakdown_sorting()
	{
		return array(new report_table_starting_column_sort(0, 'asc'));
	}

	public function get_tmpi_targeted_inventory_price_breakdown_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$column_class_prefix = 'table_cell_targeted_inventory_price_breakdown';

		$table_starting_sorts = $this->get_tmpi_targeted_inventory_price_breakdown_sorting();

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$table_headers = array(
			array(
				"Price Range", 
				new report_table_column_format(
					$column_class_prefix.'_price_range', 
					'asc', 
					$this->format_string_callable
				),
				"Breakdown of price ranges"
			),
			array(
				"Count", 
				new report_table_column_format(
					$column_class_prefix.'_count', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Total vehicles in price range"
			),
			array(
				"%", 
				new report_table_column_format(
					$column_class_prefix.'_percent', 
					'desc', 
					$this->format_percent_callable
				),
				"Percent of vehicles in price range"
			)
		);

		$response = $this->get_tmpi_targeted_inventory_price_breakdown_core_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		$map_database_range_to_title = array(
			'A'    => 'Average',
			'L'    => 'Low',
			'H'    => 'High',
			chr(0) => 'No Price' // NUL ASCII character
		);

		$total = 0;
		foreach($table_content as $row)
		{
			$total += $row['price_count'];
		}

		foreach($table_content as &$row)
		{
			$row['percent'] = 100.0 * $row['price_count'] / $total;

			if(array_key_exists($row['price_range'], $map_database_range_to_title))
			{
				$row['price_range'] = $map_database_range_to_title[$row['price_range']];
			}
			else
			{
				$row['price_range'] = "";
			}
		}

		$result = false;
		switch($build_data_type)
		{
			case k_build_table_type::html:
				$table_data = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);
				$result = $this->get_table_subproduct_result($table_data);
				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	public function get_carmercial_targeted_content_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$html_scaffolding = '
			<div id="subproduct_summary" class="content_main" style="float:left;width:20%;">
			</div>
			<div id="subproduct_chart" class="content_main" style="float:left;width:80%;">
			</div>
			<div id="subproduct_table" class="content_main" style="clear:both;">
			</div>
		';

		$summary_data = $this->get_carmercial_targeted_content_summary_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$chart_data = $this->get_carmercial_targeted_content_chart_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$table_data = $this->get_carmercial_targeted_content_table_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$summary_data_set = array(
			'display_function' => 'build_subproduct_summary',
			'display_selector' => '#subproduct_summary',
			'display_data'     => $summary_data
		);

		$chart_data_set = array(
			'display_function' => 'build_subproduct_chart',
			'display_selector' => '#subproduct_chart',
			'display_data'     => $chart_data
		);

		$table_data_set = array(
			'display_function' => 'build_subproduct_table',
			'display_selector' => '#subproduct_table',
			'display_data'     => $table_data
		);

		$data_sets = array(
			$summary_data_set,
			$chart_data_set,
			$table_data_set
		);

		$result = array(
			'subproduct_html_scaffolding' => $html_scaffolding,
			'subproduct_data_sets' => $data_sets
		);

		return $result;
	}

	public function get_carmercial_targeted_content_overview_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$core_sorting
	)
	{
		return $this->get_carmercial_targeted_content_top_clips(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);
	}

	private function get_carmercial_targeted_content_summary_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$summary_return_data = array();

		$select_sql = "
			SELECT 
				SUM(crd.count) AS total
		";
	
		$summary_data_response = $this->get_carmercial_targeted_content_generic(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$select_sql
		);

		if($summary_data_response->num_rows() > 0)
		{
			$summary_data_row = $summary_data_response->row();
			$summary_return_data[] = array(
				'css_selector' => '#content_summary_num_views_value',
				'value' => number_format($summary_data_row->total)
			);
		}

		$data_set = array(
			'html_scaffolding' => '
				<div id="content_summary_num_views_value"></div>
				<div class="content_summary_num_views_description">Number of times your videos were watched during the reporting period</div>
			',
			'values' => $summary_return_data
		);

		return $data_set;
	}

	private function get_carmercial_targeted_content_chart_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$select_sql = "
			SELECT 
				crd.date AS date,
				SUM(crd.count) AS watches
		";

		$group_by_sql = "
			GROUP BY
				crd.date
		";

		$sort_sql = "
			ORDER BY 
				date ASC
		";
	
		$chart_data_response = $this->get_carmercial_targeted_content_generic(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$select_sql,
			$group_by_sql,
			$sort_sql
		);

		if($chart_data_response->num_rows() > 0)
		{
			$rows = $chart_data_response->result();

			$views_series_data = array();
			$x_axis_data = array();
			foreach($rows as $row)
			{
				$views_series_data[] = (int)$row->watches;
				$x_axis_data[] = $row->date;
			}

			$chart_data = array(
				'container_css_selector' => '#subproduct_chart',
				'javascript_builder_function' => 'build_content_chart',
				'x_axis_data' => $x_axis_data,
				'series' => array(
					$views_series_data
				)
			);
		}

		return $chart_data;
	}

	private function get_carmercial_targeted_content_top_clips(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$sorting = null
	)
	{
		$select_sql = "
			SELECT 
				crd.video_name_string AS video_name_string,
				SUM(crd.count) AS total
		";

		$group_by_sql = "
			GROUP BY
				crd.video_id
		";

		$sort_sql = $this->get_sorting_sql($sorting);

		$limit_sql = "
			LIMIT 10
		";
	
		return $this->get_carmercial_targeted_content_generic(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$select_sql,
			$group_by_sql,
			$sort_sql,
			$limit_sql
		);
	}


	private function get_carmercial_targeted_content_generic(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$select_sql,
		$group_by_sql = "",
		$sort_sql = "",
		$limit_sql = ""
	)
	{
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$vl_advertiser_to_third_party_account_from_sql = "";
		$vl_advertiser_to_third_party_account_where_sql = "";
		$vl_advertiser_to_third_party_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_third_party_account_from_sql,
			$vl_advertiser_to_third_party_account_where_sql,
			$vl_advertiser_to_third_party_account_bindings,
			$vl_advertiser_id,
			report_campaign_organization::carmercial
		);

		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::carmercial,
			report_campaign_type::carmercial_content
		);

		$account_ids_sql = $this->generate_sql_for_ids($account_ids, ' AND cd.frq_id IN ');

		$sql = "
			$select_sql
			FROM 
				$vl_advertiser_to_third_party_account_from_sql
				JOIN tp_cm_dealers AS cd
					ON (ajtpa.frq_third_party_account_id = cd.frq_id
						AND ajtpa.third_party_source = ".report_campaign_organization::carmercial."
					)
				JOIN tp_cm_report_data AS crd 
					ON (cd.frq_id = crd.frq_dealer_id)
			WHERE
				$vl_advertiser_to_third_party_account_where_sql
				$account_ids_sql
				AND crd.date BETWEEN ? AND ?
			$group_by_sql
			$sort_sql
			$limit_sql
		";

		$date_range_bindings = array($start_date, $end_date);

		$bindings = array_merge(
			$vl_advertiser_to_third_party_account_bindings,
			$account_ids,
			$date_range_bindings
		);

		$response = $this->db->query($sql, $bindings);

		return $response;
	}

	private function get_carmercial_targeted_content_table_data(
		$vl_advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type
	)
	{
		$result = false;

		$column_class_prefix = 'table_cell_targeted_content_top_clips';

		$table_starting_sorts = array(new report_table_starting_column_sort(1, 'desc'));

		$core_sorting = null;
		if($build_data_type === k_build_table_type::csv)
		{
			$core_sorting = $table_starting_sorts;
		}

		$table_headers = array(
			array(
				"Video Name", 
				new report_table_column_format(
					$column_class_prefix.'_clip_name', 
					'asc', 
					$this->format_string_callable
				),
				"Name of Video Clip"
			),
			array(
				"Views", 
				new report_table_column_format(
					$column_class_prefix.'_num_watches', 
					'desc', 
					$this->format_whole_number_callable
				),
				"Number of times watched during reporting period"
			)
		);

		$response = $this->get_carmercial_targeted_content_top_clips(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$core_sorting
		);

		$table_content = $response->result_array();

		switch($build_data_type)
		{
			case k_build_table_type::html:
				$result = $this->build_simple_table_data(
					$table_headers,
					$table_content,
					$table_starting_sorts
				);

				$result->table_options = array(
					'paging' => false,
					'info' => false
				);

				$result->caption = "Top 10 Videos";

				break;
			case k_build_table_type::csv:
				$result = $this->build_csv_data(
					$table_headers,
					$table_content
				);
				break;
			default:
				throw new Exception("Unknown build type: ".$build_data_type);
				$result = false;
				break;
		}

		return $result;
	}

	public function get_frequence_account_data_presence(
		$advertiser_id,
		$subfeature_access_permissions,
		$select_data_sql = "",
		$start_date = null,
		$end_date = null,
		report_campaign_set $campaign_set_parameter = null,
		$condense_to_one_row = false
	)
	{
		$has_dates = false;
		$date_bindings = array();
		if($start_date !== null && $end_date !== null)
		{
			$has_dates = true;
			$date_bindings = array($start_date, $end_date);
		}

		$common_bindings = array_merge($date_bindings, array($advertiser_id));

		$campaign_set = $campaign_set_parameter;
		$include_all_campaigns = false;
		if($campaign_set_parameter === null)
		{
			$campaign_set = new report_campaign_set(array());
			$include_all_campaigns = true;
		}

		$frequence_campaigns_sql_by_type = array();
		$frequence_campaigns_bindings_by_type = array();

		$this->get_campaigns_sql_and_bindings_by_type(
			$frequence_campaigns_sql_by_type,
			$frequence_campaigns_bindings_by_type,
			$campaign_set,
			report_campaign_organization::frequence,
			'AND cmp.id IN ',
			$include_all_campaigns
		);
				
		$ad_interactions_union_sql = "";
		$ad_interactions_bindings = array();
		// Ad Interactions are masked by impressions, so only need to test for impressions
		//if($subfeature_access_permissions['are_ad_interactions_accessible'] == true)
		//{
		//	$ad_interactions_from_sql = '';
		//	$ad_interactions_where_sql = '';
		//	$ad_interactions_hovers_total_sql = '';
		//	$ad_interactions_video_plays_total_sql = '';

		//	$ad_interactions_bindings = array();

		//	$ad_interactions_campaign_ids = $campaign_set->get_campaign_ids(
		//		report_campaign_organization::frequence,
		//		report_campaign_type::frequence_display
		//	);

		//	if(!empty($ad_interactions_campaign_ids))
		//	{
		//		$this->get_ad_interactions_query_elements(
		//			$ad_interactions_from_sql,
		//			$ad_interactions_where_sql,
		//			$ad_interactions_hovers_total_sql,
		//			$ad_interactions_video_plays_total_sql,
		//			$ad_interactions_bindings,
		//			$advertiser_id,
		//			$ad_interactions_campaign_ids
		//		);

		//		if($has_dates)
		//		{
		//			$ad_interactions_bindings[] = $start_date;
		//			$ad_interactions_bindings[] = $end_date;
		//		}

		//		$ad_interactions_union_sql = '
		//			UNION ALL 
		//			(
		//				SELECT 
		//					ads.campaign_id AS campaign_id, 
		//					IF(er.creative_id IS NULL, 0, 1) AS targeted_display,
		//					0 AS targeted_preroll
		//				FROM 
		//					'.$ad_interactions_from_sql.'
		//					JOIN cup_versions AS cv ON (cv.adset_id = ads.id)
		//					JOIN cup_creatives AS cr ON (cr.version_id = cv.id)
		//					LEFT JOIN engagement_records AS er ON (er.creative_id = cr.id)
		//				WHERE
		//					'.$ad_interactions_where_sql.'
		//					'.($has_dates ? ' er.date BETWEEN ? AND ?' : ' 1').'
		//				GROUP BY
		//					campaign_id
		//			)
		//		';
		//	}
		//}

		$frequence_campaigns_data_sql_by_type = array(
			report_campaign_type::frequence_display => array(
				'sql' => "
					SELECT 
						cmp.id AS campaign_id,
						$select_data_sql
						IF(rcad.adgroup_id IS NULL, 0, 1) AS targeted_display,
						0 AS targeted_preroll
					FROM
						Advertisers AS adv
						JOIN Campaigns AS cmp 
							ON (adv.id = cmp.business_id)
						JOIN AdGroups AS ag 
							ON (cmp.id = ag.campaign_id)
						LEFT JOIN report_cached_adgroup_date AS rcad
							ON (ag.id = rcad.adgroup_id
								".($has_dates ? "AND rcad.date BETWEEN ? AND ?" : "")."
							)
					WHERE
						rcad.adgroup_id IS NOT NULL AND
						adv.id = ?
						".$frequence_campaigns_sql_by_type[report_campaign_type::frequence_display]."
					GROUP BY
						cmp.id
					$ad_interactions_union_sql
				",
				'bindings' => array_merge(
					$common_bindings,
					$frequence_campaigns_bindings_by_type[report_campaign_type::frequence_display],
					$ad_interactions_bindings
				)
			),
			report_campaign_type::frequence_pre_roll=> array(
				'sql' => "
					SELECT 
						cmp.id AS campaign_id,
						$select_data_sql
						0 AS targeted_display,
						IF(rcad.adgroup_id IS NULL, 0, 1) AS targeted_preroll
					FROM
						Advertisers AS adv
						JOIN Campaigns AS cmp 
							ON (adv.id = cmp.business_id)
						JOIN AdGroups AS ag 
							ON (cmp.id = ag.campaign_id)
						LEFT JOIN report_cached_adgroup_date AS rcad
							ON (ag.id = rcad.adgroup_id
								".($has_dates ? "AND rcad.date BETWEEN ? AND ?" : "")."
							)
					WHERE
						rcad.adgroup_id IS NOT NULL AND
						adv.id = ?
						".$frequence_campaigns_sql_by_type[report_campaign_type::frequence_pre_roll]."
					GROUP BY
						cmp.id
				",
				'bindings' => array_merge(
					$common_bindings,
					$frequence_campaigns_bindings_by_type[report_campaign_type::frequence_pre_roll]
				)
			),
		);

		$ignore_empty_id_sets_to_get_campaigns_for_advertiser = $campaign_set_parameter == null;

		$frequence_campaigns_sql = "";
		$frequence_campaigns_bindings = array();
		$this->resolve_campaign_type_data_to_sql_and_bindings(
			$frequence_campaigns_sql,
			$frequence_campaigns_bindings,
			$frequence_campaigns_bindings_by_type,
			$frequence_campaigns_data_sql_by_type,
			$ignore_empty_id_sets_to_get_campaigns_for_advertiser
		);

		$response = null;

		$outer_group_by = "GROUP BY campaign_id";
		if($condense_to_one_row === true)
		{
			$outer_group_by = "";
		}

		if(!empty($frequence_campaigns_sql))
		{
			$sql = "
				SELECT 
					cmp.campaign_id AS campaign_id,
					$select_data_sql
					MAX(targeted_display) AS has_targeted_display,
					MAX(targeted_preroll) AS has_targeted_preroll
				FROM
				(
					$frequence_campaigns_sql
				) AS cmp
				$outer_group_by
			";

			$response = $this->db->query($sql, $frequence_campaigns_bindings);
		}

		return $response;
	}

	public function get_tmpi_account_data_presence(
		$advertiser_id,
		$account_data_sql = "",
		$start_date = null,
		$end_date = null,
		report_campaign_set $campaign_set_parameter = null,
		$condense_to_one_row = false
	)
	{
		$has_dates = false;
		$date_bindings = array();
		if($start_date !== null && $end_date !== null)
		{
			$has_dates = true;
			$date_bindings = array($start_date, $end_date);
		}

		$campaign_set = $campaign_set_parameter;
		$include_all_campaigns = false;
		if($campaign_set_parameter === null)
		{
			$campaign_set = new report_campaign_set(array());
			$include_all_campaigns = true;
		}

		$vl_advertiser_to_tmpi_account_from_sql = "";
		$vl_advertiser_to_tmpi_account_where_sql = "";
		$vl_advertiser_to_tmpi_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_tmpi_account_from_sql,
			$vl_advertiser_to_tmpi_account_where_sql,
			$vl_advertiser_to_tmpi_account_bindings,
			$advertiser_id,
			report_campaign_organization::tmpi
		);
		
		$common_bindings = array_merge($date_bindings, $vl_advertiser_to_tmpi_account_bindings);

		$tmpi_accounts_sql_by_type = array();
		$tmpi_accounts_bindings_by_type = array();

		$this->get_campaigns_sql_and_bindings_by_type(
			$tmpi_accounts_sql_by_type,
			$tmpi_accounts_bindings_by_type,
			$campaign_set,
			report_campaign_organization::tmpi,
			'AND ta.account_id IN ',
			$include_all_campaigns
		);

		$tmpi_common_clicks_select_sql = "
		";
		$tmpi_common_clicks_group_by_sql = "
			GROUP BY account_id
		";

		$tmpi_accounts_data_sql_by_type = array(
			report_campaign_type::tmpi_clicks => array(
				// Assumes summary clicks and geo clicks are same data, grouped in different ways.
				'sql' => $this->get_targeted_common_clicks_sql(
					"
						ta.account_id AS account_id,
						$account_data_sql
						IF(tdc.account_id IS NULL, 0, 1) AS targeted_clicks,
						0 AS targeted_inventory,
						0 AS targeted_directories
					",
					$vl_advertiser_to_tmpi_account_from_sql,
					$vl_advertiser_to_tmpi_account_where_sql,
					$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_clicks],
					$tmpi_common_clicks_group_by_sql,
					$date_bindings,
					tmpi_clicks_campaign_type::clicks
				),
				'bindings' => $this->get_targeted_common_clicks_bindings(
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_clicks],
					$date_bindings,
					tmpi_clicks_campaign_type::clicks
				)
			),
			report_campaign_type::tmpi_inventory => array(
				'sql' => "
					SELECT 
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(hlp.account_id IS NULL, 0, 1) AS targeted_inventory,
						0 AS targeted_directories
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_hi_lo_performance AS hlp ON (
							ta.account_id = hlp.account_id
							".($has_dates ? "AND hlp.date BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory]."
					GROUP BY
						ta.account_id

					UNION ALL

					SELECT 
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(sdp.account_id IS NULL, 0, 1) AS targeted_inventory,
						0 AS targeted_directories
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_search_detail_performance AS sdp ON (
							ta.account_id = sdp.account_id
							".($has_dates ? "AND sdp.report_month BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory]."
					GROUP BY
						ta.account_id

					UNION ALL

					SELECT 
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(vs.account_id IS NULL, 0, 1) AS targeted_inventory,
						0 AS targeted_directories
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_vehicle_summaries AS vs ON (
							ta.account_id = vs.account_id
							".($has_dates ? "AND vs.date BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory]."
					GROUP BY
						ta.account_id

					UNION ALL

					SELECT 
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(pl.account_id IS NULL, 0, 1) AS targeted_inventory,
						0 AS targeted_directories
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_phone_leads AS pl ON (
							ta.account_id = pl.account_id
							".($has_dates ? "AND pl.captured BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory]."
					GROUP BY
						ta.account_id

					UNION ALL

					SELECT 
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(el.account_id IS NULL, 0, 1) AS targeted_inventory,
						0 AS targeted_directories
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_email_leads AS el ON (
							ta.account_id = el.account_id
							".($has_dates ? "AND el.captured BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory]."
					GROUP BY
						ta.account_id

					UNION ALL

				" . 
				// Assumes summary clicks and geo clicks are same data, grouped in different ways.
				$this->get_targeted_common_clicks_sql(
					"
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(tdc.account_id IS NULL, 0, 1) AS targeted_inventory,
						0 AS targeted_directories
					",
					$vl_advertiser_to_tmpi_account_from_sql,
					$vl_advertiser_to_tmpi_account_where_sql,
					$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory],
					$tmpi_common_clicks_group_by_sql,
					$date_bindings,
					tmpi_clicks_campaign_type::smart_ads
				),
				'bindings' => array_merge(
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
					$this->get_targeted_common_clicks_bindings(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$date_bindings,
						tmpi_clicks_campaign_type::smart_ads
					)
				)
			),
			report_campaign_type::tmpi_directories => array(
				'sql' => "
					SELECT 
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						0 AS targeted_inventory,
						IF(lp.account_id IS NULL, 0, 1) AS targeted_directories
					FROM 
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_local_performance AS lp ON (
							ta.account_id = lp.account_id
							".($has_dates ? "AND lp.captured BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_directories]."
					GROUP BY
						ta.account_id
				",
				'bindings' => array_merge(
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories]
				)
			)
		);

		$ignore_empty_id_sets_to_get_campaigns_for_advertiser = $campaign_set_parameter == null;

		$tmpi_accounts_sql = "";
		$tmpi_accounts_bindings = array();
		$this->resolve_campaign_type_data_to_sql_and_bindings(
			$tmpi_accounts_sql,
			$tmpi_accounts_bindings,
			$tmpi_accounts_bindings_by_type,
			$tmpi_accounts_data_sql_by_type,
			$ignore_empty_id_sets_to_get_campaigns_for_advertiser
		);

		$response = null;

		$outer_group_by = "GROUP BY account_id";
		if($condense_to_one_row === true)
		{
			$outer_group_by = "";
		}

		if(!empty($tmpi_accounts_sql))
		{
			$sql = "
				SELECT 
					ta.account_id AS account_id,
					$account_data_sql
					MAX(targeted_clicks) AS has_targeted_clicks,
					MAX(targeted_inventory) AS has_targeted_inventory,
					MAX(targeted_directories) AS has_targeted_directories
				FROM
				(
					$tmpi_accounts_sql
				) AS ta
				$outer_group_by
			";

			$response = $this->db->query($sql, $tmpi_accounts_bindings);
		}

		return $response;
	}

	public function get_carmercial_data_presence(
		$advertiser_id,
		$dealer_data_sql = "",
		$start_date = null,
		$end_date = null,
		report_campaign_set $campaign_set_parameter = null,
		$condense_to_one_row = false
	)
	{
		$has_dates = false;
		$date_bindings = array();
		if($start_date !== null && $end_date !== null)
		{
			$has_dates = true;
			$date_bindings = array($start_date, $end_date);
		}

		$campaign_set = $campaign_set_parameter;
		$include_all_campaigns = false;
		if($campaign_set_parameter === null)
		{
			$campaign_set = new report_campaign_set(array());
			$include_all_campaigns = true;
		}

		$vl_advertiser_to_carmercial_account_from_sql = "";
		$vl_advertiser_to_carmercial_account_where_sql = "";
		$vl_advertiser_to_carmercial_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_carmercial_account_from_sql,
			$vl_advertiser_to_carmercial_account_where_sql,
			$vl_advertiser_to_carmercial_account_bindings,
			$advertiser_id,
			report_campaign_organization::carmercial
		);
		
		$common_bindings = array_merge($date_bindings, $vl_advertiser_to_carmercial_account_bindings);

		$carmercial_accounts_sql_by_type = array();
		$carmercial_accounts_bindings_by_type = array();

		$this->get_campaigns_sql_and_bindings_by_type(
			$carmercial_accounts_sql_by_type,
			$carmercial_accounts_bindings_by_type,
			$campaign_set,
			report_campaign_organization::carmercial,
			'AND cd.frq_id IN ',
			$include_all_campaigns
		);

		$carmercial_accounts_data_sql_by_type = array(
			report_campaign_type::carmercial_content => array(
				'sql' => "
					SELECT 
						cd.frq_id AS dealer_id,
						$dealer_data_sql
						IF(crd.frq_dealer_id IS NULL, 0, 1) AS targeted_content
					FROM 
						$vl_advertiser_to_carmercial_account_from_sql
						JOIN tp_cm_dealers AS cd
							ON (ajtpa.frq_third_party_account_id = cd.frq_id
								AND ajtpa.third_party_source = ".report_campaign_organization::carmercial."
							)
						LEFT JOIN tp_cm_report_data AS crd ON (
							cd.frq_id = crd.frq_dealer_id
							".($has_dates ? "AND crd.date BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_carmercial_account_where_sql
						".$carmercial_accounts_sql_by_type[report_campaign_type::carmercial_content]."
					GROUP BY
						cd.frq_id
				",
				'bindings' => array_merge(
					$common_bindings,
					$carmercial_accounts_bindings_by_type[report_campaign_type::carmercial_content]
				)
			)
		);

		$ignore_empty_id_sets_to_get_campaigns_for_advertiser = $campaign_set_parameter == null;

		$carmercial_accounts_sql = "";
		$carmercial_accounts_bindings = array();
		$this->resolve_campaign_type_data_to_sql_and_bindings(
			$carmercial_accounts_sql,
			$carmercial_accounts_bindings,
			$carmercial_accounts_bindings_by_type,
			$carmercial_accounts_data_sql_by_type,
			$ignore_empty_id_sets_to_get_campaigns_for_advertiser
		);

		$response = null;

		$outer_group_by = "GROUP BY dealer_id";
		if($condense_to_one_row === true)
		{
			$outer_group_by = "";
		}

		if(!empty($carmercial_accounts_sql))
		{
			$sql = "
				SELECT 
					cd.dealer_id AS dealer_id,
					$dealer_data_sql
					MAX(targeted_content) AS has_targeted_content
				FROM
				(
					$carmercial_accounts_sql
				) AS cd 
				$outer_group_by
			";

			$response = $this->db->query($sql, $carmercial_accounts_bindings);
		}

		return $response;
	}

	public function get_overview_top_creatives($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date)
	{
		if ($campaign_set == "")
			return array();
		$response = $this->get_display_creatives_versions($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date, "OVERVIEW");
		if ($response == null)
			return array();
		$cup_version_ids = $response->result_array();
		 
		$version_ids = array();
		foreach($cup_version_ids as $version_id)
		{
			$version_ids[] = $version_id['cup_version_id'];
		}
		
		if (count($version_ids) == 0)
			return array();

		$backup_images_array = $this->get_creative_backup_images_for_versions($version_ids);
		$cup_version_ids_new=array();
		foreach($cup_version_ids as $version_id)
		{
			$sub_array=array();
			$cup_version_id = $version_id['cup_version_id'];
			foreach($backup_images_array as $backup_image)
			{
				if ($backup_image['version_id'] == $cup_version_id)
				{
					$sub_array= array('impressions' => (int)$version_id['impressions'], 'interaction_rate' => $version_id['interaction_rate'], 'thumbnail' => $backup_image['backup_image']);
					break;
				}
			}
			$cup_version_ids_new[]=	array('data' => $sub_array);
		}

		return $cup_version_ids_new;

	}

	public function does_advertiser_have_report_heatmaps_enabled($advertiser_id)
	{
		if(!is_numeric($advertiser_id) || ((int)$advertiser_id != $advertiser_id))
		{
			throw new Exception('Advertiser id must be an int', 1);
		}

		$sql =
		"	SELECT 
				are_heatmaps_enabled 
			FROM 
				Advertisers 
			WHERE 
				id = ?
		";
		$result = $this->db->query($sql, $advertiser_id);

		if($result->num_rows() == 1)
		{
			return ((int)$result->row()->are_heatmaps_enabled) > 0;
		}
		return false;
	}

	// this method called by overview page to show clicks from targeted visits. It does custom grouping to fetch data to render custom graphs which changes by number of days searched
	public function get_targeted_visits_clicks_for_overview_page($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date)
	{
		$subfeature_access_permissions=array();
		$subfeature_access_permissions['are_view_throughs_accessible']=false;
		$subfeature_access_permissions['are_ad_interactions_accessible']=false;
		$subfeature_access_permissions['are_engagements_accessible']=false;
		$subfeature_access_permissions['is_tmpi_accessible']=true;

		$response = $this->get_tmpi_targeted_common_clicks_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			null,
			null,
			null,
			tmpi_clicks_campaign_type::clicks,
			tmpi_clicks_data_set_type::date_raw_data
		);

		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$start_date_1 = new DateTime($raw_start_date);
	   	$end_date_1  = new DateTime($raw_end_date);
	   	$difference = $start_date_1->diff($end_date_1);
	   	$days_diff = $difference->days;
	  	
	  	$sql="";
	  	$return_array=array();
	  	if ($response != null)
	  	{
	  		$term_type="";
	  		$term_increment = "";
			if ($days_diff < 14)
			{	
				$term_type="Daily";
	  			$term_increment = "1";
			}
			else if ($days_diff < 71)
			{
				$term_type="Weekly";
	  			$term_increment = "7";
			}
			else
			{	
				$term_type="Monthly";
	  			$term_increment = "30";
			}

			$total_weekly_clicks=0;
			$ctr=0;
			$last_date=null;
			$non_zero_impressions_flag=false;
			for ($ctr = 0 ; $ctr < $days_diff; $ctr += $term_increment)
			{
				$limit_start = date("Y-m-d", strtotime($raw_start_date. ' '. $ctr.'  day'));
				$limit_end = date("Y-m-d", strtotime($raw_start_date. ' '. ($ctr+$term_increment).'  day'));
				$total_weekly_clicks=0;
				foreach ($response->result_array() as $row) 
				{
					$row_date=$row['date'];
					$row_date=date($row_date);
					if ($limit_start <= $row_date && $row_date < $limit_end)
					{
						$total_weekly_clicks += $row['clicks'];
					}
				}
				if ($total_weekly_clicks > 0)
				{
					$non_zero_impressions_flag=true;
				}
				$return_array[]=array('day' => $limit_start, 'clicks' => $total_weekly_clicks, 'term' => $term_type);
			}
		}
		if ($non_zero_impressions_flag)
		{
			return $return_array;
		}
		else
		{
			return array();
		}
	}
}
?>
