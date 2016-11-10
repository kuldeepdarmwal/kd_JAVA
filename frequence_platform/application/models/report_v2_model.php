<?php

class report_v2_model extends CI_Model
{
	private $format_whole_number_callable;
	private $format_percent_callable;
	private $format_string_callable;
	private $format_image_callable;
	private static $map_product_html_id_to_report_campaign_type;

	public function __construct()
	{
		$this->load->database();

		$this->load->helper(array('report_v2_model_helper', 'url'));

		$this->load->model('tmpi_model');

		report_campaign::init_maps();

		$this->format_whole_number_callable = array($this, 'format_whole_number');
		$this->format_percent_callable = array($this, 'format_percent');
		$this->format_string_callable = array($this, 'format_string');
		$this->format_image_callable = array($this, 'format_image');

		$map_product_html_id_to_report_campaign_type = array(
			report_product_tab_html_id::display_product => report_campaign_type::frequence_display,
			report_product_tab_html_id::pre_roll_product => report_campaign_type::frequence_pre_roll,
			report_product_tab_html_id::targeted_clicks_product => report_campaign_type::tmpi_clicks,
			report_product_tab_html_id::targeted_inventory_product => report_campaign_type::tmpi_inventory,
			report_product_tab_html_id::targeted_directories_product => report_campaign_type::tmpi_directories,
			report_product_tab_html_id::targeted_content_product => report_campaign_type::carmercial_content
		);
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

			$permissions_for_user = $this->vl_platform->get_list_of_user_permission_html_ids_for_user($user_id);

			$result['are_view_throughs_accessible'] = isset($permissions_for_user['reports_view_throughs']) ? 1 : 0;
			$result['is_pre_roll_accessible'] = isset($permissions_for_user['reports_pre_roll']) ? 1 : 0;
			$result['is_tmpi_accessible'] = isset($permissions_for_user['reports_tmpi']) ? 1 : 0;
			$result['are_display_creatives_accessible'] = isset($permissions_for_user['reports_creatives']) ? 1 : 0;
			$result['are_tv_schedules_accessible'] = 1; // Preserved from previous comment: isset($permissions_for_user['reports_tv']) ? 1 : 0;
			$result['are_tv_impressions_accessible'] = isset($permissions_for_user['reports_tv_impressions']) ? 1 : 0;
			$result['is_expanded_tv_reporting_available'] = isset($permissions_for_user['reports_expanded_tv']) ? 1 : 0;
			$result['is_lift_report_accessible'] = isset($permissions_for_user['lift_report']) ? 1 : 0;

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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';

		$bindings_array = array_merge($bindings_array, $advertiser_id, $campaign_ids);

		$ad_interactions_from_sql = "
			Campaigns AS cmp
			";
		$ad_interactions_where_sql = "
			cmp.business_id IN $advertisers_sql AND
			cv.campaign_id IN $campaign_id_string AND
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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
				100 * (SUM(nr_clicks) + SUM(r_clicks)) / (SUM(nr_impressions) + SUM(r_impressions)) AS click_rate,
				should_show_in_table_ui
				'.$view_throughs_outer_select_sql.'
			FROM
			(
				(
					SELECT
						b.Base_Site AS place,
						SUM(b.Impressions) AS nr_impressions,
						SUM(b.Clicks) AS nr_clicks,
						0 AS r_impressions,
						0 AS r_clicks,
						TRUE AS should_show_in_table_ui
						'.$view_throughs_inner_non_retargeting_select_sql.'
					FROM (AdGroups a JOIN SiteRecords b ON (a.ID = b.AdGroupID))
						JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id IN '.$advertisers_sql .' AND
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
						SUM(b.Clicks) AS r_clicks,
						TRUE AS should_show_in_table_ui
						'.$view_throughs_inner_retargeting_select_sql.'
					FROM (AdGroups a JOIN SiteRecords b ON (a.ID = b.AdGroupID))
						JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id IN '.$advertisers_sql .' AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN '.$campaigns_sql.' AND
						a.IsRetargeting = 1
						'.$data_filter.'
					GROUP BY place
				)
				UNION ALL
				(
					SELECT
						b.Base_Site AS place,
						SUM(b.Impressions) AS r_impressions,
						SUM(b.Clicks) AS r_clicks,
						0 AS nr_impressions,
						0 AS nr_clicks,
						FALSE AS should_show_in_table_ui
					FROM (AdGroups a JOIN report_all_other_sites_small_impressions b ON (a.ID = b.AdGroupID))
							JOIN Campaigns c ON (a.campaign_id = c.id)
					WHERE
						c.business_id IN '.$advertisers_sql .' AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN '.$campaigns_sql.'
						'.$data_filter.'
					GROUP BY place
				)
			) AS u
			GROUP BY place
			'.$sort_sql.'
		';

		$advertiser_bindings = array_map('intval', $advertiser_id);

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
		$build_data_type,
		$unused_timezone_offset
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
			),
			array(
				'',
				new report_table_column_format(
					$column_class_prefix.'_should_show_row',
					'desc',
					$this->format_whole_number_callable,
					false,
					false,
					false,
					false
				),
				'determines if row is shown in ui'
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
				$table_data->caption = "Domain Performance";
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

	private function format_image($src)
	{
		return "<img src=\"$src\" >";
	}

	private function get_geography_sorting()
	{
		return array(new report_table_starting_column_sort(3, 'desc'));
	}

	private function get_networks_sorting()
	{
		return array(new report_table_starting_column_sort(3, 'desc'));
	}

	private function get_networks_programs_sorting()
	{
		return array(new report_table_starting_column_sort(4, 'desc'));
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
		$build_data_type,
		$unused_timezone_offset
	)
	{
		$response = $this->get_display_creatives_versions($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date, "REPORTS");

		$display_creatives_list = $response->result_array();

		$display_creatives_data_set = array(
			'display_function' => 'build_display_creatives_table',
			'display_selector' => '#display_creatives_table_wrapper',
			'display_data'	 => [
				'creative_ids' => $display_creatives_list,
				'creative_type' => 'display'
			]
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

		$advertiser_id_bindings = array_map('intval', $advertiser_id);
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);


		if ($mode == 'OVERVIEW' && empty($campaign_ids))
		{
			return null;
		}
		$campaign_id_bindings = array_map('intval', $campaign_ids);
		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_id_bindings).')';
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
			$campaign_id_bindings
		);



		$bindings = array_merge(
			$advertiser_id_bindings,
			$campaign_id_bindings,
			$campaign_id_bindings,
			$date_bindings,

			$date_bindings,
			$advertiser_id_bindings,
			$campaign_id_bindings,
			$ad_interactions_bindings,
			$date_bindings,

			$advertiser_id_bindings,
			$campaign_id_bindings,
			$campaign_id_bindings,
			$date_bindings,

			$date_bindings,
			$advertiser_id_bindings,
			$campaign_id_bindings,
			$ad_interactions_bindings,
			$date_bindings
		);

		$query = "
			SELECT
				versions.version_id AS cup_version_id,
				CAST((SUM(versions.engagements)) / SUM(versions.impressions) AS DECIMAL(10,8)) AS interaction_rate";

		if ($mode == "OVERVIEW")
		{
			$query .= " , SUM(versions.impressions) AS impressions,
						SUM(versions.engagements) AS engagements
			";
		}

		$query .= "
			FROM
				(SELECT
					cv.id AS version_id,
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
					JOIN cup_versions AS cv
						ON (cre.version_id = cv.id)
				WHERE
					cmp.business_id IN $advertisers_sql
					AND adg.campaign_id IN ".$campaigns_sql."
					AND cv.campaign_id IN ".$campaigns_sql."
					AND rcr.date BETWEEN ? AND ?
					AND cv.source_id IS NULL
				GROUP BY
					version_id
			UNION ALL
				SELECT
					cv.id AS version_id,
					0 AS impressions,
					0 AS clicks,
					".$ad_interactions_hovers_total_sql." + ".$ad_interactions_video_plays_total_sql." AS engagements
				FROM
					".$ad_interactions_from_sql."
					JOIN cup_versions AS cv
						ON (cmp.id = cv.campaign_id)
					JOIN cup_creatives AS cre
						ON (cv.id = cre.version_id)
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
								JOIN cup_versions AS cv
									ON (cre.version_id = cv.id)
								JOIN Campaigns AS cmp
									ON (cv.campaign_id = cmp.id)
							WHERE
								rcr.date BETWEEN ? AND ?
								AND cmp.business_id IN $advertisers_sql
								AND cv.campaign_id IN ".$campaigns_sql."
							GROUP BY
								rcr.creative_id,
								rcr.date
						) AS rcr_s
						ON (cre.id = rcr_s.creative_id AND er.date = rcr_s.date)
				WHERE
					".$ad_interactions_where_sql."
					er.date BETWEEN ? AND ?
					AND (cv.source_id IS NULL)
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
					JOIN cup_versions AS cv
						ON (cre.version_id = cv.id)
					JOIN cup_versions AS ver2
						ON (cv.source_id = ver2.id)

				WHERE
					cmp.business_id IN $advertisers_sql
					AND adg.campaign_id IN ".$campaigns_sql."
					AND cv.campaign_id IN ".$campaigns_sql."
					AND rcr.date BETWEEN ? AND ?
					AND cv.source_id IS NOT NULL
				GROUP BY
					version_id
			UNION ALL
				SELECT
					cv.source_id AS version_id,
					0 AS impressions,
					0 AS clicks,
					".$ad_interactions_hovers_total_sql." + ".$ad_interactions_video_plays_total_sql." AS engagements
				FROM
					".$ad_interactions_from_sql."
					JOIN cup_versions AS cv
						ON (cmp.id = cv.campaign_id)
					JOIN cup_creatives AS cre
						ON (cv.id = cre.version_id)
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
								JOIN cup_versions AS cv
									ON (cre.version_id = cv.id)
								JOIN Campaigns AS cmp
									ON (cv.campaign_id = cmp.id)
							WHERE
								rcr.date BETWEEN ? AND ?
								AND cmp.business_id IN $advertisers_sql
								AND cv.campaign_id IN ".$campaigns_sql."
							GROUP BY
								rcr.creative_id,
								rcr.date
						) AS rcr_s
						ON (cre.id = rcr_s.creative_id AND er.date = rcr_s.date)

				WHERE
					".$ad_interactions_where_sql."
					er.date BETWEEN ? AND ?
					AND (cv.source_id IS NOT NULL)
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


	public function get_tv_creative_messages(
		$advertiser_id,
		$campaigns,
		$start_date,
		$end_date,
		$tv_creative_ids
	)
	{

		$user_id = $this->tank_auth->get_user_id();
		$subfeature_access_permissions = $this->get_subfeature_access_permissions($user_id);

		$report_data = $this->get_tv_creative_message_report_data(
			$advertiser_id,
			$tv_creative_ids,
			$start_date,
			$end_date,
			$campaigns,
			$subfeature_access_permissions
		);
		$tv_creative_data_array = $report_data->result_array();

		$result = array();
		foreach($tv_creative_data_array as $tv_creative)
		{
			$creative_data = json_decode($tv_creative['creative_json'], true);
			$creative_timeseries_data = $this->zero_dates($creative_data, $start_date, $end_date);

			$row_data = array(
				'creative_data' => array(
					'tv_creative_spot_id' => $tv_creative['spot_id'],
					'tv_creative_bookend_top_id' => $tv_creative['bookend_top_id'],
					'tv_creative_bookend_bottom_id' => $tv_creative['bookend_bottom_id'],
					'tv_creative_spot_name' => $tv_creative['spot_name'],
					'tv_creative_bookend_top_name' => $tv_creative['bookend_top_name'],
					'tv_creative_bookend_bottom_name' => $tv_creative['bookend_bottom_name'],
					'tv_creative_spot_video_url' => $tv_creative['spot_link_mp4'],
					'tv_creative_spot_video_url_webm' => $tv_creative['spot_link_webm'],
					'tv_creative_bookend_top_video_url' => $tv_creative['bookend_top_link_mp4'],
					'tv_creative_bookend_top_video_webm' => $tv_creative['bookend_top_link_webm'],
					'tv_creative_bookend_bottom_video_url' => $tv_creative['bookend_bottom_link_mp4'],
					'tv_creative_bookend_bottom_video_url_webm' => $tv_creative['bookend_bottom_link_webm'],
					'tv_creative_spot_thumbnail' => $tv_creative['spot_link_thumb'],
					'tv_creative_bookend_top_thumbnail' => $tv_creative['bookend_top_link_thumb'],
					'tv_creative_bookend_bottom_thumbnail' => $tv_creative['bookend_bottom_link_thumb']
				)
			);

			$row_data['creative_data']['chart'] = ['dates' => array_column($creative_timeseries_data, 'date')];
			if($subfeature_access_permissions['are_tv_impressions_accessible'])
			{
				$row_data['creative_data']['chart']['impressions'] = array_column($creative_timeseries_data, 'total_impressions');
			}
			$row_data['creative_data']['chart']['airings'] = array_column($creative_timeseries_data, 'airings');

			$result[] = $row_data;
		}
		return $result;
	}

	private function get_creative_message_report_data($advertiser_id, $version_ids, $start_date, $end_date, $campaigns)
	{

		$advertiser_id_bindings = $advertiser_id;

		$campaign_ids = $campaigns->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);

		$campaigns_sql = '(-1,'.$this->make_bindings_sql_from_array($campaign_ids).')';
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
					COALESCE(cv.source_id, cv.id) AS version_id,
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
					JOIN cup_versions AS cv
						ON (cre.version_id = cv.id)
				WHERE
					cmp.business_id IN $advertisers_sql
					AND adg.campaign_id IN ".$campaigns_sql."
					AND rcr.date BETWEEN ? AND ?
					AND (cv.id IN ".$version_sql." OR cv.source_id IN ".$version_sql.")
				GROUP BY
					version_id,
					rcr.date
				UNION ALL
				SELECT
					COALESCE(cv.source_id, cv.id) AS version_id,
					er.date AS date,
					0 AS impressions,
					0 AS clicks,
					".$ad_interactions_hovers_total_sql." + ".$ad_interactions_video_plays_total_sql." AS engagements
				FROM
					".$ad_interactions_from_sql."
					JOIN cup_versions AS cv
						ON (cmp.id = cv.campaign_id)
					JOIN cup_creatives AS cre
						ON (cv.id = cre.version_id)
					JOIN engagement_records AS er
						ON (cre.id = er.creative_id)
				WHERE
					".$ad_interactions_where_sql."
					er.date BETWEEN ? AND ?
					AND (cv.id IN ".$version_sql." OR cv.source_id IN ".$version_sql.")
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

	private function get_tv_creative_message_report_data($advertiser_id, $tv_creative_ids, $start_date, $end_date, $campaigns, $subfeature_access_permissions)
	{

		$account_ids = $campaigns->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		$account_ul_ids = array_unique(array_column($tv_creative_ids, 'account_ul_id'));

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
		$account_ul_ids_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ul_ids).')';
		$start_date_search = date("Y-m-d", strtotime($start_date));
		$end_date_search = date("Y-m-d", strtotime($end_date));

		$advertiser_id_bindings = array_map('intval', $advertiser_id);
		$account_ids_bindings = array_map('intval', $account_ids);
		$account_ul_ids_bindings = array_map('intval', $account_ul_ids);
		$date_bindings = array(
			$start_date_search,
			$end_date_search
		);

		$bindings = array_merge(
			$date_bindings,
			$account_ul_ids_bindings,
			$account_ids_bindings,
			$advertiser_id_bindings,
			$date_bindings,
			$account_ul_ids_bindings
		);

		$tv_impressions_select = $subfeature_access_permissions['are_tv_impressions_accessible'] ? "'\"total_impressions\":', tstibc.estimated_impressions, ','," : '';
		$tv_impressions_subselect = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'SUM(tstibc.estimated_impressions) AS estimated_impressions,' : '';

		$query =
		"	SELECT
				tstibc.spot_id,
				tstibc.bookend_top_id,
				tstibc.bookend_bottom_id,
				tstibc.spot_name,
				tstibc.bookend_top_name,
				tstibc.bookend_bottom_name,
				IF(tstibc.spot_name != '' AND tvc_spot.link_thumb IS NULL, '/images/reports/no_video_available.png', tvc_spot.link_thumb) AS spot_link_thumb,
				IF(tstibc.bookend_top_name != '' AND tvc_b1.link_thumb IS NULL, '/images/reports/no_video_available.png', tvc_b1.link_thumb) AS bookend_top_link_thumb,
				IF(tstibc.bookend_bottom_name != '' AND tvc_b2.link_thumb IS NULL, '/images/reports/no_video_available.png', tvc_b2.link_thumb) AS bookend_bottom_link_thumb,
				tvc_spot.link_mp4 AS spot_link_mp4,
				tvc_spot.link_webm AS spot_link_webm,
				tvc_b1.link_mp4 AS bookend_top_link_mp4,
				tvc_b1.link_webm AS bookend_top_link_webm,
				tvc_b2.link_mp4 AS bookend_bottom_link_mp4,
				tvc_b2.link_webm AS bookend_bottom_link_webm,
				CONCAT('{', GROUP_CONCAT(CONCAT('\"', tstibc.date, '\": {\"date\":\"', tstibc.date, '\",', {$tv_impressions_select}'\"airings\":', tstibc.spot_count, '}') ORDER BY tstibc.date ASC SEPARATOR ','), '}') AS creative_json
			FROM
			(	SELECT
					tstibc.account_ul_id,
					tstibc.date,
					tstibc.spot_id,
					tstibc.bookend_top_id,
					tstibc.bookend_bottom_id,
					tstibc.spot_name,
					tstibc.bookend_top_name,
					tstibc.bookend_bottom_name,
					{$tv_impressions_subselect}
					SUM(tstibc.spot_count) AS spot_count
				FROM
					tp_spectrum_tv_impressions_by_creative AS tstibc
				WHERE
					(tstibc.date BETWEEN ? AND ?) AND
					tstibc.account_ul_id IN {$account_ul_ids_sql}
				GROUP BY
					tstibc.spot_id,
					tstibc.bookend_top_id,
					tstibc.bookend_bottom_id,
					tstibc.date
			) AS tstibc
			LEFT JOIN
				tp_video_creatives AS tvc_spot
				ON
					(tvc_spot.third_party_video_id = tstibc.spot_id AND tvc_spot.third_party_source_id = 3)
			LEFT JOIN
				tp_video_creatives AS tvc_b1
				ON
					(tvc_b1.third_party_video_id = tstibc.bookend_top_id AND tvc_b1.third_party_source_id = 3)
			LEFT JOIN
				tp_video_creatives AS tvc_b2
				ON
					(tvc_b2.third_party_video_id = tstibc.bookend_bottom_id AND tvc_b2.third_party_source_id = 3)
			INNER JOIN tp_spectrum_tv_accounts AS tpsta
				ON (tstibc.account_ul_id = tpsta.account_id)
			INNER JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql} AND
				tpajtp.frq_advertiser_id IN $advertisers_sql AND
				(tstibc.date BETWEEN ? AND ?) AND
				tstibc.account_ul_id IN {$account_ul_ids_sql}
			GROUP BY
				tstibc.spot_id,
				tstibc.bookend_top_id,
				tstibc.bookend_bottom_id
		";
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

	public function get_spectrum_targeted_tv_network_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		// TODO:TV implement to get data when the tab (or specific nav-pill) is chosen
		// See "get_carmercial_targeted_content_data()" for systems that can conform
		// See "get_display_creatives_data()" for systems that need to get more data after initial setup
		$tv_creatives_list = $this->get_targeted_tv_network_zone_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			$timezone_offset
		);

		$tv_creatives_data_set = array(
			'display_function' => 'build_tv_network_table', // // TODO:TV - see assets/js/report_v2.js -> build_tv_schedule_thing() for more custom javascript handling
			'display_selector' => '#tv_network_table_wrapper',
			'display_data'	 => $tv_creatives_list
		);

		$data_sets = array(
			$tv_creatives_data_set
		);

		$html_scaffolding = '
			<div id="tv_network_table_wrapper" class="tv_schedule" style="">
			</div>
		';

		$result = array(
			'subproduct_html_scaffolding' => $html_scaffolding,
			'subproduct_data_sets' => $data_sets
		);
		return $result;
	}

	private function get_targeted_tv_network_zone_data(
		$advertiser_ids,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$date_for_timezone =  new DateTime(null, new DateTimeZone('America/New_York'));
		$dst_flag = $date_for_timezone->format('I');

		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);
		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';

		$dtz = new DateTimeZone('GMT');
		$eastern_time = new DateTime('now', $dtz);
		$eastern_time_string = $eastern_time->format("Y-m-d H:i:s");

		$get_network_data_bindings = array();

		// Convert the advertiser ids to integer
		$advertiser_ids = array_map('intval', $advertiser_ids);

		$get_network_data_bindings = array_merge(
			$account_ids,
			$advertiser_ids,
			array
			(
				$eastern_time_string
			)
		);
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_ids).')';

		$get_network_data_query =
		"	SELECT
				stvs.account_ul_id,
				stvs.client_traffic_id,
				stvs.client_name,
				stvs.zone,
				stzd.sysname,
				stvs.network,
				stnd.network_name,
				COUNT(*) AS airings_count
			FROM
				tp_spectrum_tv_schedule AS stvs
			LEFT JOIN tp_spectrum_tv_zone_data AS stzd
				ON (stvs.zone_sys_code = stzd.id)
			LEFT JOIN tp_spectrum_tv_network_data AS stnd
				ON (stvs.network = stnd.abbreviation)
			JOIN tp_spectrum_tv_accounts AS tpsta
				ON (stvs.account_ul_id = tpsta.account_id)
			JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql}
				AND tpajtp.frq_advertiser_id IN $advertisers_sql
				AND stvs.air_time_date >= ?
			GROUP BY
				network, zone
			ORDER BY
				zone, network
		";

		$network_data_result = $this->db->query($get_network_data_query, $get_network_data_bindings);
		$network_data_array = $network_data_result->result_array();
		$network_demographics = array();
		$network_icons = array();
		$network_data = array();

		foreach($network_data_array as $network_data_row)
		{
			$current_network = $network_data_row['network'];
			$current_zone = $network_data_row['zone'];

			if(!array_key_exists($current_network, $network_demographics))
			{
				$network_icon_url = "https://s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/".$current_network.".png";
				$file_headers = @get_headers($network_icon_url);
				if(strpos($file_headers[0], '200 OK'))
				{
					$network_icons[$current_network] = $network_icon_url;
				}
				else
				{
					$network_icons[$current_network] = "/assets/img/network_logo_goes_here.png";
				}

				$demographic_data_url = getcwd()."/assets/json/tv/network_json/".$current_network.".json";
				if(file_exists($demographic_data_url))
				{
					$demographic_file_result = file_get_contents($demographic_data_url);
				}
				else
				{
					$demographic_file_result = false;
				}

				if($demographic_file_result != false)
				{
					$demographic_file_result = json_decode($demographic_file_result);
					if ($demographic_file_result != null)
					{
						$demographic_data = $demographic_file_result->demographic_data;
						if ($demographic_data != null)
						{
							$network_demographics[$current_network] = array();
							$network_demographics[$current_network]['avg_household_income'] = $demographic_data->{"Avg Household Income"};
							$network_demographics[$current_network]['avg_household_income']->Value = "$".number_format($network_demographics[$current_network]['avg_household_income']->Value, 0);

							$network_demographics[$current_network]['median_age'] = $demographic_data->{"Median Age"};
							$network_demographics[$current_network]['median_age']->Value = number_format($network_demographics[$current_network]['median_age']->Value, 1);

							$network_demographics[$current_network]['avg_household_size'] = $demographic_data->{"Avg Household Size"};

							$network_demographics[$current_network]['avg_hosehold_value'] = $demographic_data->{"Avg Home Value"};
							$network_demographics[$current_network]['avg_hosehold_value']->Value = "$".number_format($network_demographics[$current_network]['avg_hosehold_value']->Value, 0);

							$network_demographics[$current_network]['male_population'] = $demographic_data->{"Men"};
							$network_demographics[$current_network]['female_population'] = $demographic_data->{"Women"};
							$network_demographics[$current_network]['age_18_24'] = $demographic_data->{"18 - 24"};
							$network_demographics[$current_network]['age_25_34'] = $demographic_data->{"25 - 34"};
							$network_demographics[$current_network]['age_35_44'] = $demographic_data->{"35 - 44"};
							$network_demographics[$current_network]['age_45_54'] = $demographic_data->{"45 - 54"};
							$network_demographics[$current_network]['age_55_64'] = $demographic_data->{"55 - 64"};
							$network_demographics[$current_network]['age_65'] = $demographic_data->{"65 or older"};

							$network_demographics[$current_network]['income_0_50'] = $demographic_data->{"Less than $49,999"};
							$network_demographics[$current_network]['income_50_100'] = $demographic_data->{"$50,000 - $99,999"};
							$network_demographics[$current_network]['income_100_150'] = $demographic_data->{"$100,000 - $149,999"};
							$network_demographics[$current_network]['income_150'] = $demographic_data->{"$150,000 or more"};

							$network_demographics[$current_network]['white_population'] = $demographic_data->{"White"};
							$network_demographics[$current_network]['black_population'] = $demographic_data->{"Black/African American"};
							$network_demographics[$current_network]['asian_population'] = $demographic_data->{"Asian"};
							$network_demographics[$current_network]['hispanic_population'] = $demographic_data->{"Hispanic"};
							$network_demographics[$current_network]['race_other'] = $demographic_data->{"Other"};

							$network_demographics[$current_network]['kids_no'] = $demographic_data->{"No Kids"};
							$network_demographics[$current_network]['kids_yes'] = $demographic_data->{"Has Kids"};

							$network_demographics[$current_network]['college_no'] = $demographic_data->{"No Kids"};
							$network_demographics[$current_network]['college_under'] = $demographic_data->{"Has Kids"};
							$network_demographics[$current_network]['college_grad'] = $demographic_data->{"Has Kids"};
						}
					}
				}
				else
				{
					$network_demographics[$current_network] = array();
				}
			}

			if(!array_key_exists($current_network."-".$current_zone, $network_data))
			{
				if (array_key_exists($network_data_row['network'], $network_demographics))
				{
					$network_data[$current_network."-".$current_zone] = array(
						"network" => $network_data_row['network'],
						"network_icon" => $network_icons[$network_data_row['network']],
						"network_friendly_name" => $network_data_row['network_name'],
						"demographic_data" => $network_demographics[$network_data_row['network']],
						"zone" => $network_data_row['zone'],
						"zone_friendly_name"=> $network_data_row['sysname'] != null ? $network_data_row['sysname'] : $network_data_row['zone'],
						"airings" => array(),
						"airings_count" => $network_data_row['airings_count'],
						"advertiser_ids" => $advertiser_ids,
						"account_ids" => $account_ids
					);
				}
			}

		}
		$network_data = array_values($network_data);
		return $network_data;
	}

	public function get_airings_data_for_zone_network(
		$advertiser_ids,
		$account_ids,
		$zone,
		$network
	)
	{
		$date_for_timezone =  new DateTime(null, new DateTimeZone('America/New_York'));
		$dst_flag = $date_for_timezone->format('I');

		// Convert the account and advertiser ids to integer
		$account_ids = array_map('intval', $account_ids);
		$advertiser_ids = array_map('intval', $advertiser_ids);

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';

		$dtz = new DateTimeZone('GMT');
		$eastern_time = new DateTime('now', $dtz);
		$eastern_time_string = $eastern_time->format("Y-m-d H:i:s");

		$get_network_data_bindings = array();

		$get_network_data_bindings = array_merge(
			$account_ids,
			$advertiser_ids,
			array($eastern_time_string),
			array($zone),
			array($network),
			$account_ids,
			$advertiser_ids,
			array($eastern_time_string),
			array($zone),
			array($network)
		);

		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_ids).')';

		$get_network_data_query =
		"	SELECT
				stvs.spot_id AS creative_id,
				NULL AS creative_id_bookend_bottom,
				stvs.spot_name,
				NULL AS bookend_top_name,
				NULL AS bookend_bottom_name,
				(0+stzd.utc_offset) AS 'utc_offset',
				(0+stzd.utc_dst_offset) AS 'utc_dst_offset',
				stvs.air_time_date,
				stvs.zone,
				stvs.network,
				stvs.program,
				tvc.link_mp4,
				tvc.link_webm,
				IF(stvs.spot_name != '' AND tvc.link_thumb IS NULL, '/images/reports/no_video_available.png', tvc.link_thumb) AS link_thumb,
				NULL AS link_mp4_bookend_bottom,
				NULL AS link_webm_bookend_bottom,
				NULL AS link_thumb_bookend_bottom
			FROM
				tp_spectrum_tv_schedule AS stvs
				JOIN tp_video_creatives AS tvc
					ON (stvs.spot_id = tvc.third_party_video_id AND tvc.third_party_source_id = 3)
				LEFT JOIN tp_spectrum_tv_zone_data AS stzd
					ON (stvs.zone_sys_code = stzd.id)
				JOIN tp_spectrum_tv_accounts AS tpsta
					ON (stvs.account_ul_id = tpsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tpajtp
					ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql} AND
				tpajtp.frq_advertiser_id in $advertisers_sql AND
				stvs.air_time_date >= ? AND
				stvs.zone = ? AND
				stvs.network = ?
			GROUP BY
				network, air_time_date, zone
			UNION ALL
			SELECT
				stvs.bookend_top_id AS creative_id,
				stvs.bookend_bottom_id AS creative_id_bookend_bottom,
				stvs.spot_name,
				stvs.bookend_top_name,
				stvs.bookend_bottom_name,
				(0+stzd.utc_offset) AS 'utc_offset',
				(0+stzd.utc_dst_offset) AS 'utc_dst_offset',
				stvs.air_time_date,
				stvs.zone,
				stvs.network,
				stvs.program,
				tvc.link_mp4,
				tvc.link_webm,
				IF(stvs.bookend_top_name != '' AND tvc.link_thumb IS NULL, '/images/reports/no_video_available.png', tvc.link_thumb) AS link_thumb,
				tvc2.link_mp4 AS link_mp4_bookend_bottom,
				tvc2.link_webm AS link_webm_bookend_bottom,
				IF(stvs.bookend_bottom_name != '' AND tvc2.link_thumb IS NULL, '/images/reports/no_video_available.png', tvc2.link_thumb) AS link_thumb_bookend_bottom
			FROM
				tp_spectrum_tv_schedule AS stvs
			JOIN tp_video_creatives AS tvc
				ON (stvs.bookend_top_id = tvc.third_party_video_id AND tvc.third_party_source_id = 3)
			JOIN tp_video_creatives AS tvc2
				ON (stvs.bookend_bottom_id = tvc2.third_party_video_id AND tvc2.third_party_source_id = 3)
			LEFT JOIN tp_spectrum_tv_zone_data AS stzd
				ON (stvs.zone_sys_code = stzd.id)
			JOIN tp_spectrum_tv_accounts AS tpsta
				ON (stvs.account_ul_id = tpsta.account_id)
			JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql} AND
				tpajtp.frq_advertiser_id IN $advertisers_sql AND
				stvs.air_time_date >= ? AND
				stvs.zone = ? AND
				stvs.network = ?
			GROUP BY
				network, air_time_date, zone
			ORDER BY
				air_time_date
		";

		$network_data_result = $this->db->query($get_network_data_query, $get_network_data_bindings);
		$network_data_array = $network_data_result->result_array();

		$network_data = array();
		foreach($network_data_array as $network_data_row)
		{
			$current_network = $network_data_row['network'];
			$current_zone = $network_data_row['zone'];

			$airing_array = array(
				"airing_time" => $this->get_zone_airing_half_hour($network_data_row['air_time_date'], $network_data_row['utc_offset'], $network_data_row['utc_dst_offset'], $dst_flag),
				"show"=>$network_data_row['program'],
				"creative" => array(
					"creative_id" => $network_data_row['creative_id'],
					"creative_name" => $network_data_row['creative_id_bookend_bottom'] == null ? $network_data_row['spot_name'] : $network_data_row['bookend_top_name'].' & '.$network_data_row['bookend_bottom_name'],
					"creative_video" => $network_data_row['link_mp4'],
					"creative_video_webm" => $network_data_row['link_webm'],
					"creative_video_bookend_2" => $network_data_row['link_mp4_bookend_bottom'],
					"creative_video_bookend_2_webm" => $network_data_row['link_webm_bookend_bottom'],
					"creative_thumbnail" => $network_data_row['link_thumb'],
					"creative_thumbnail_2" => $network_data_row['link_thumb_bookend_bottom']
				)
			);

			$network_data[$current_network.'-'.$current_zone]['airings'][] = $airing_array;
		}

		$network_data = array_values($network_data);
		return $network_data;
	}

	private function get_targeted_tv_network_airing_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$date_for_timezone =  new DateTime(null, new DateTimeZone('America/New_York'));
		$dst_flag = $date_for_timezone->format('I');

		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';


		$dtz = new DateTimeZone('GMT');
		$eastern_time = new DateTime('now', $dtz);
		$eastern_time_string = $eastern_time->format("Y-m-d H:i:s");

		$get_network_data_bindings = array();

		$get_network_data_bindings = array_merge(
			$account_ids,
			$advertiser_id,
			array
			(
				$eastern_time_string
			),
			$account_ids,
			$advertiser_id,
			array
			(
				$eastern_time_string
			)
		);
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';

				$get_network_data_query =
				"SELECT *
				FROM (
					SELECT
					@row_number := IF(@previous_network = network AND @previous_zone = zone, @row_number + 1, 1) AS row_number,
					@previous_network := network,
			@previous_zone := zone,
					tv_remove_duplicates.*
					FROM (
						SELECT
										stvs.account_ul_id,
										stvs.client_traffic_id,
										stvs.spot_id AS creative_id,
										NULL AS creative_id_bookend_bottom,
										stvs.client_name,
										stvs.spot_name,
										NULL AS bookend_top_name,
										NULL AS bookend_bottom_name,
										stvs.zone,
										stzd.sysname,
										(0+stzd.utc_offset) AS 'utc_offset',
										(0+stzd.utc_dst_offset) AS 'utc_dst_offset',
										stvs.network,
										stnd.network_name,
										stvs.air_time_date,
										stvs.program,
										stvc.link_mp4,
										stvc.link_webm,
										stvc.link_thumb,
										NULL AS link_mp4_bookend_bottom,
										NULL AS link_webm_bookend_bottom,
										NULL AS link_thumb_bookend_bottom
								FROM
										tp_spectrum_tv_schedule AS stvs
										JOIN tp_spectrum_tv_creatives AS stvc
												ON (stvs.spot_id = stvc.id)
										LEFT JOIN tp_spectrum_tv_zone_data AS stzd
												ON (stvs.zone_sys_code = stzd.id)
										LEFT JOIN tp_spectrum_tv_network_data AS stnd
												ON (stvs.network = stnd.abbreviation)
										JOIN tp_spectrum_tv_accounts AS tpsta
										ON (stvs.account_ul_id = tpsta.account_id)
										JOIN tp_advertisers_join_third_party_account AS tpajtp
										ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
								WHERE
										stvs.spot_id IS NOT NULL
										AND tpsta.frq_id IN {$accounts_sql}
										AND tpajtp.frq_advertiser_id in $advertisers_sql
										AND stvs.air_time_date >= ?
								GROUP BY
										network, air_time_date, zone
								UNION ALL
								SELECT
										stvs.account_ul_id,
										stvs.client_traffic_id,
										stvs.bookend_top_id AS creative_id,
										stvs.bookend_bottom_id AS creative_id_bookend_bottom,
										stvs.client_name,
										stvs.spot_name,
										stvs.bookend_top_name,
										stvs.bookend_bottom_name,
										stvs.zone,
										stzd.sysname,
										(0+stzd.utc_offset) AS 'utc_offset',
										(0+stzd.utc_dst_offset) AS 'utc_dst_offset',
										stvs.network,
										stnd.network_name,
										stvs.air_time_date,
										stvs.program,
										stvc.link_mp4,
										stvc.link_webm,
										stvc.link_thumb,
										stvc2.link_mp4 AS link_mp4_bookend_bottom,
										stvc2.link_webm AS link_webm_bookend_bottom,
										stvc2.link_thumb AS link_thumb_bookend_bottom
								FROM
										tp_spectrum_tv_schedule AS stvs
										JOIN tp_spectrum_tv_creatives AS stvc
										ON (stvs.bookend_top_id = stvc.id)
										JOIN tp_spectrum_tv_creatives AS stvc2
										ON (stvs.bookend_bottom_id = stvc2.id)
										LEFT JOIN tp_spectrum_tv_zone_data AS stzd
												ON (stvs.zone_sys_code = stzd.id)
										LEFT JOIN tp_spectrum_tv_network_data AS stnd
												ON (stvs.network = stnd.abbreviation)
										JOIN tp_spectrum_tv_accounts AS tpsta
										ON (stvs.account_ul_id = tpsta.account_id)
										JOIN tp_advertisers_join_third_party_account AS tpajtp
										ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
								WHERE
										stvs.spot_id IS NULL
										AND tpsta.frq_id IN {$accounts_sql}
										AND tpajtp.frq_advertiser_id IN $advertisers_sql
										AND stvs.air_time_date >= ?
								GROUP BY
										network, air_time_date, zone
								ORDER BY
										zone, network, air_time_date
								) AS tv_remove_duplicates
								JOIN (SELECT @previous_network := NULL, @row_number := 0) AS vars
						GROUP BY
								network, air_time_date, zone
						ORDER BY
								zone ASC, network ASC, air_time_date ASC
						) AS ordered_airings
						WHERE
								row_number <= 10";

		$network_data_result = $this->db->query($get_network_data_query, $get_network_data_bindings);
		$network_data_array = $network_data_result->result_array();

		$network_demographics = array();
		$network_icons = array();
		$network_data = array();

		foreach($network_data_array as $network_data_row)
		{
			$current_network = $network_data_row['network'];
			$current_zone = $network_data_row['zone'];

			if(!array_key_exists($current_network, $network_demographics))
			{
				$network_icon_url = "https://s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/".$current_network.".png";
				$file_headers = @get_headers($network_icon_url);
				if(strpos($file_headers[0], '200 OK'))
				{
					$network_icons[$current_network] = $network_icon_url;
				}
				else
				{
					$network_icons[$current_network] = "/assets/img/network_logo_goes_here.png";
				}

				$demographic_data_url = getcwd()."/assets/json/tv/network_json/".$current_network.".json";
				if(file_exists($demographic_data_url))
				{
					$demographic_file_result = file_get_contents($demographic_data_url);
				}
				else
				{
					$demographic_file_result = false;
				}

				if($demographic_file_result != false)
				{
					$demographic_file_result = json_decode($demographic_file_result);
					if ($demographic_file_result != null)
					{
						$demographic_data = $demographic_file_result->demographic_data;
						if ($demographic_data != null)
						{
							$network_demographics[$current_network] = array();
							$network_demographics[$current_network]['avg_household_income'] = $demographic_data->{"Avg Household Income"};
							$network_demographics[$current_network]['avg_household_income']->Value = "$".number_format($network_demographics[$current_network]['avg_household_income']->Value, 0);

							$network_demographics[$current_network]['median_age'] = $demographic_data->{"Median Age"};
							$network_demographics[$current_network]['median_age']->Value = number_format($network_demographics[$current_network]['median_age']->Value, 1);

							$network_demographics[$current_network]['avg_household_size'] = $demographic_data->{"Avg Household Size"};

							$network_demographics[$current_network]['avg_hosehold_value'] = $demographic_data->{"Avg Home Value"};
							$network_demographics[$current_network]['avg_hosehold_value']->Value = "$".number_format($network_demographics[$current_network]['avg_hosehold_value']->Value, 0);

							$network_demographics[$current_network]['male_population'] = $demographic_data->{"Men"};
							$network_demographics[$current_network]['female_population'] = $demographic_data->{"Women"};
							$network_demographics[$current_network]['age_18_24'] = $demographic_data->{"18 - 24"};
							$network_demographics[$current_network]['age_25_34'] = $demographic_data->{"25 - 34"};
							$network_demographics[$current_network]['age_35_44'] = $demographic_data->{"35 - 44"};
							$network_demographics[$current_network]['age_45_54'] = $demographic_data->{"45 - 54"};
							$network_demographics[$current_network]['age_55_64'] = $demographic_data->{"55 - 64"};
							$network_demographics[$current_network]['age_65'] = $demographic_data->{"65 or older"};

							$network_demographics[$current_network]['income_0_50'] = $demographic_data->{"Less than $49,999"};
							$network_demographics[$current_network]['income_50_100'] = $demographic_data->{"$50,000 - $99,999"};
							$network_demographics[$current_network]['income_100_150'] = $demographic_data->{"$100,000 - $149,999"};
							$network_demographics[$current_network]['income_150'] = $demographic_data->{"$150,000 or more"};

							$network_demographics[$current_network]['white_population'] = $demographic_data->{"White"};
							$network_demographics[$current_network]['black_population'] = $demographic_data->{"Black/African American"};
							$network_demographics[$current_network]['asian_population'] = $demographic_data->{"Asian"};
							$network_demographics[$current_network]['hispanic_population'] = $demographic_data->{"Hispanic"};
							$network_demographics[$current_network]['race_other'] = $demographic_data->{"Other"};

							$network_demographics[$current_network]['kids_no'] = $demographic_data->{"No Kids"};
							$network_demographics[$current_network]['kids_yes'] = $demographic_data->{"Has Kids"};

							$network_demographics[$current_network]['college_no'] = $demographic_data->{"No Kids"};
							$network_demographics[$current_network]['college_under'] = $demographic_data->{"Has Kids"};
							$network_demographics[$current_network]['college_grad'] = $demographic_data->{"Has Kids"};
						}
					}
				}
				else
				{
					$network_demographics[$current_network] = array();
				}
			}

			if(!array_key_exists($current_network."-".$current_zone, $network_data))
			{
				if (array_key_exists($network_data_row['network'], $network_demographics))
				{
					$network_data[$current_network."-".$current_zone] = array(
						"network"=> $network_data_row['network'],
						"network_icon" => $network_icons[$network_data_row['network']],
						"network_friendly_name" =>$network_data_row['network_name'],
						"demographic_data"=>$network_demographics[$network_data_row['network']],
						"zone"=>$network_data_row['zone'],
						"zone_friendly_name"=>$network_data_row['sysname'] != null ? $network_data_row['sysname'] : $network_data_row['zone'],
						"airings" => array()
					);
				}
			}

			$airing_array = array(
				"airing_time" => $this->get_zone_airing_half_hour($network_data_row['air_time_date'], $network_data_row['utc_offset'], $network_data_row['utc_dst_offset'], $dst_flag),
				"show"=>$network_data_row['program'],
				"creative" => array(
					"creative_id" => $network_data_row['creative_id'],
					"creative_name" => $network_data_row['creative_id_bookend_bottom'] == null ? $network_data_row['spot_name'] : $network_data_row['bookend_top_name'].' & '.$network_data_row['bookend_bottom_name'],
					"creative_video" => $network_data_row['link_mp4'],
					"creative_video_webm" => $network_data_row['link_webm'],
					"creative_video_bookend_2" => $network_data_row['link_mp4_bookend_bottom'],
					"creative_video_bookend_2_webm" => $network_data_row['link_webm_bookend_bottom'],
					"creative_thumbnail" => $network_data_row['link_thumb'],
					"creative_thumbnail_2" => $network_data_row['link_thumb_bookend_bottom']
				)
			);
			$network_data[$current_network.'-'.$current_zone]['airings'][] = $airing_array;
		}
		$network_data = array_values($network_data);
		return $network_data;
	}

	public function get_spectrum_targeted_tv_creative_impressions_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$tv_creatives_list = $this->get_targeted_tv_creative_impressions_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$tv_delivered_creatives_list = array(
			'display_function' => 'build_tv_creatives_table',
			'display_selector' => '#tv_creatives_table_wrapper',
			'display_data'	 => [
				'creative_ids' => $tv_creatives_list,
				'creative_type' => 'tv'
			]
		);

		$data_sets = array(
			$tv_delivered_creatives_list
		);

		$html_scaffolding = '
			<div id="tv_creatives_table_wrapper" class="tv_creatives" style="">
			</div>
		';

		$result = array(
			'subproduct_html_scaffolding' => $html_scaffolding,
			'subproduct_data_sets' => $data_sets
		);

		return $result;
	}

	public function get_spectrum_targeted_tv_network_impressions_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$tv_delivered_networks_list = $this->get_targeted_tv_network_impressions_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$table_starting_sorts = $this->get_networks_sorting();

		$column_class_prefix_networks = 'table_cell_delivered_networks';
		$table_headers = array(
			array(
				'Network',
				new report_table_column_format(
					"{$column_class_prefix_networks}_network_csv",
					'asc',
					$this->format_string_callable,
					false, // orderable
					false, // searchable
					false, // visible
					true // exportable
				),
				''
			),
			array(
				'Network',
				new report_table_column_format(
					"{$column_class_prefix_networks}_network",
					'asc',
					$this->format_image_callable,
					false, // orderable
					false, // searchable
					true, // visible
					false // exportable
				),
				'Network'
			),
			array(
				'', // Network name column
				new report_table_column_format(
					"{$column_class_prefix_networks}_network_name",
					'asc',
					$this->format_string_callable,
					true, // orderable
					true, // searchable
					true, // visible
					false // exportable
				),
				''
			),
		);

		if($subfeature_access_permissions['are_tv_impressions_accessible'])
		{
			$table_headers[] = array(
				'Impressions',
				new report_table_column_format(
					"{$column_class_prefix_networks}_impressions",
					'asc',
					$this->format_whole_number_callable
				),
				'Impressions'
			);
		}

		$table_headers[] = array(
			'Airings',
			new report_table_column_format(
				"{$column_class_prefix_networks}_airings",
				'asc',
				$this->format_whole_number_callable
			),
			'Airings'
		);

		$network_table_data = $this->build_simple_table_data(
			$table_headers,
			$tv_delivered_networks_list,
			$table_starting_sorts
		);
		$network_table_data->caption = ($subfeature_access_permissions['are_tv_impressions_accessible'] ? 'Impressions' : 'Airings') . ' by Network';
		$network_result = $this->get_table_subproduct_result($network_table_data);

		return $network_result;
	}

	public function get_spectrum_targeted_tv_network_impressions_data_by_program(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$tv_delivered_programs_list = $this->get_targeted_tv_network_impressions_data_by_program(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$table_starting_sorts = $this->get_networks_programs_sorting();

		$column_class_prefix_program = 'table_cell_delivered_programs';
		$table_headers = array(
			array(
				'Network',
				new report_table_column_format(
					"{$column_class_prefix_program}_network_csv",
					'asc',
					$this->format_string_callable,
					false, // orderable
					false, // searchable
					false, // visible
					true // exportable
				),
				''
			),
			array(
				'Network',
				new report_table_column_format(
					"{$column_class_prefix_program}_network",
					'asc',
					$this->format_image_callable,
					false, // orderable
					false, // searchable
					true, // visible
					false // exportable
				),
				'Network'
			),
			array(
				'', // Network name column
				new report_table_column_format(
					"{$column_class_prefix_program}_network_name",
					'asc',
					$this->format_string_callable,
					true, // orderable
					true, // searchable
					true, // visible
					false // exportable
				),
				''
			),
			array(
				'Program',
				new report_table_column_format(
					"{$column_class_prefix_program}_program",
					'asc',
					$this->format_string_callable,
					true, // orderable
					true // searchable
				),
				'Program'
			)
		);

		if($subfeature_access_permissions['are_tv_impressions_accessible'])
		{
			$table_headers[] = array(
				'Impressions',
				new report_table_column_format(
					"{$column_class_prefix_program}_impressions",
					'asc',
					$this->format_whole_number_callable
				),
				'Impressions'
			);
		}

		$table_headers[] = array(
			'Airings',
			new report_table_column_format(
				"{$column_class_prefix_program}_airings",
				'asc',
				$this->format_whole_number_callable
			),
			'Airings'
		);

		$program_table_data = $this->build_simple_table_data(
			$table_headers,
			$tv_delivered_programs_list,
			$table_starting_sorts
		);
		$program_table_data->caption = ($subfeature_access_permissions['are_tv_impressions_accessible'] ? 'Impressions' : 'Airings') . ' by Program';
		$program_table_data->table_options = [
			'searching' => true,
			'info' => true,
			'dom' => 'lfrtip'
		];
		$program_result = $this->get_table_subproduct_result($program_table_data);

		return $program_result;
	}

	public function get_spectrum_targeted_tv_zones_impressions_data(
		$advertiser_id,
		$campaign_set,
		$raw_start_date,
		$raw_end_date,
		$product,
		$subproduct,
		$subfeature_access_permissions,
		$build_data_type,
		$timezone_offset
	)
	{
		$tv_delivered_zones = $this->get_targeted_tv_zones_impressions_data(
			$advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type
		);

		$report_zones_geojson = $this->map->get_report_zones_geojson_and_data($tv_delivered_zones);

		$syscode_string_array = array_map('strval', array_column($tv_delivered_zones, 'syscode'));
		if($subfeature_access_permissions['are_tv_impressions_accessible'])
		{
			$number_formatted_zone_data = array_map(function($zone_data){
				$zone_data['total_impressions'] = number_format($zone_data['total_impressions']);
				$zone_data['airings'] = number_format($zone_data['airings']);
				return $zone_data;
			}, $tv_delivered_zones);
		}
		else
		{
			$number_formatted_zone_data = array_map(function($zone_data){
				$zone_data['airings'] = number_format($zone_data['airings']);
				return $zone_data;
			}, $tv_delivered_zones);
		}


		$zone_data = array_combine($syscode_string_array, $number_formatted_zone_data);

		$tv_delivered_zones_list = array(
			'display_function' => 'build_tv_zones_map',
			'display_selector' => '#tv_zones_map_wrapper',
			'display_data'	 => [
				'map_id' => 'zones_map',
				'initial_geojson' => $report_zones_geojson,
				'zone_data' => $zone_data
			]
		);

		$data_sets = array(
			$tv_delivered_zones_list
		);

		$html_scaffolding = '
			<div id="tv_zones_map_wrapper" class="tv_zones" style="">
				<div id="zones_map"></div>
			</div>
		';

		$result = array(
			'subproduct_html_scaffolding' => $html_scaffolding,
			'subproduct_data_sets' => $data_sets
		);

		return $result;
	}

	private function get_targeted_tv_network_impressions_data(
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
		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		$accounts_sql = '(-1, ' . $this->make_bindings_sql_from_array($account_ids) . ')';
		$advertisers_sql = '(-1, ' . $this->make_bindings_sql_from_array($advertiser_id) . ')';

		$advertiser_ids = array_map('intval', $advertiser_id);

		$get_network_data_bindings = array_merge(
			$account_ids,
			$advertiser_ids,
			[$raw_start_date, $raw_end_date]
		);

		$tv_impressions_select = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'SUM(tptinp.estimated_impressions) AS total_impressions,' : '';
		$tv_impressions_group_by = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'total_impressions DESC,' : '';

		$get_network_data_query =
		"	SELECT
				tptinp.account_ul_id,
				tptinp.network,
				stnd.network_name,
				{$tv_impressions_select}
				SUM(tptinp.spot_count) AS airings
			FROM
				tp_spectrum_tv_impressions_by_network_program AS tptinp
			LEFT JOIN tp_spectrum_tv_network_data AS stnd
				ON (tptinp.network = stnd.abbreviation)
			JOIN tp_spectrum_tv_accounts AS tpsta
				ON (tptinp.account_ul_id = tpsta.account_id)
			JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql} AND
				tpajtp.frq_advertiser_id in $advertisers_sql AND
				(tptinp.date BETWEEN ? AND ?)
			GROUP BY
				network
			ORDER BY
				{$tv_impressions_group_by}
				airings DESC
		";

		$network_data_result = $this->db->query($get_network_data_query, $get_network_data_bindings);
		$network_data_result_array = $network_data_result->result_array();

		$network_icons = array();
		$unique_networks = array_unique(array_column($network_data_result_array, 'network'));
		foreach($unique_networks as $network)
		{
			$network_icon_url = "https://s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/{$network}.png";
			$file_headers = @get_headers($network_icon_url);
			$network_icons[$network] = (strpos($file_headers[0], '200 OK')) ? $network_icon_url : '/assets/img/network_logo_goes_here.png';
		}

		$network_data_array = array();
		foreach($network_data_result_array as $network_data_row)
		{
			$current_network = $network_data_row['network'];
			$network_data = array(
				'network_csv' => $network_data_row['network_name'],
				'network_icon' => $network_icons[$current_network],
				'network_friendly_name' => $network_data_row['network_name']
			);
			if($subfeature_access_permissions['are_tv_impressions_accessible'])
			{
				$network_data['total_impressions'] = $network_data_row['total_impressions'];
			}
			$network_data['airings'] = $network_data_row['airings'];
			$network_data_array[] = $network_data;
		}

		return $network_data_array;
	}

	private function get_targeted_tv_network_impressions_data_by_program(
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
		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		$accounts_sql = '(-1, ' . $this->make_bindings_sql_from_array($account_ids) . ')';
		$advertisers_sql = '(-1, ' . $this->make_bindings_sql_from_array($advertiser_id) . ')';

		$advertiser_ids = array_map('intval', $advertiser_id);

		$get_network_data_bindings = array_merge(
			$account_ids,
			$advertiser_ids,
			[$raw_start_date, $raw_end_date]
		);

		$tv_impressions_select = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'SUM(tptinp.estimated_impressions) AS total_impressions,' : '';
		$tv_impressions_group_by = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'total_impressions DESC,' : '';

		$get_program_data_query =
		"	SELECT
				tptinp.account_ul_id,
				tptinp.network,
				stnd.network_name,
				tptinp.program,
				{$tv_impressions_select}
				SUM(tptinp.spot_count) AS airings
			FROM
				tp_spectrum_tv_impressions_by_network_program AS tptinp
			LEFT JOIN tp_spectrum_tv_network_data AS stnd
				ON (tptinp.network = stnd.abbreviation)
			JOIN tp_spectrum_tv_accounts AS tpsta
				ON (tptinp.account_ul_id = tpsta.account_id)
			JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql} AND
				tpajtp.frq_advertiser_id in $advertisers_sql AND
				(tptinp.date BETWEEN ? AND ?)
			GROUP BY
				network,
				program
			ORDER BY
				{$tv_impressions_group_by}
				airings DESC
		";

		$program_data_result = $this->db->query($get_program_data_query, $get_network_data_bindings);
		$program_data_result_array = $program_data_result->result_array();

		$network_icons = array();
		$unique_networks = array_unique(array_column($program_data_result_array, 'network'));
		foreach($unique_networks as $network)
		{
			$network_icon_url = "https://s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/{$network}.png";
			$file_headers = @get_headers($network_icon_url);
			$network_icons[$network] = (strpos($file_headers[0], '200 OK')) ? $network_icon_url : '/assets/img/network_logo_goes_here.png';
		}

		$program_data_array = array();
		foreach($program_data_result_array as $program_data_row)
		{
			$current_network = $program_data_row['network'];
			$program_data = array(
				'network_csv' => $program_data_row['network_name'],
				'network_icon' => $network_icons[$current_network],
				'network_friendly_name' => $program_data_row['network_name'],
				'program' => $program_data_row['program']
			);
			if($subfeature_access_permissions['are_tv_impressions_accessible'])
			{
				$program_data['total_impressions'] = $program_data_row['total_impressions'];
			}
			$program_data['airings'] = $program_data_row['airings'];
			$program_data_array[] = $program_data;
		}
		return $program_data_array;
	}

	private function get_targeted_tv_zones_impressions_data(
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
		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		$accounts_sql = '(-1, ' . $this->make_bindings_sql_from_array($account_ids) . ')';
		$advertisers_sql = '(-1, ' . $this->make_bindings_sql_from_array($advertiser_id) . ')';

		$account_ids = array_map('intval', $account_ids);
		$advertiser_ids = array_map('intval', $advertiser_id);

		$get_zone_data_bindings = array_merge(
			$account_ids,
			$advertiser_ids,
			[$raw_start_date, $raw_end_date]
		);

		$tv_impressions_select = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'SUM(tstibs.estimated_impressions) AS total_impressions,' : '';

		$get_zone_data_query =
		"	SELECT
				*
			FROM (
				SELECT
					tstibs.account_ul_id,
					tstibs.syscode,
					tstzd.sysname,
					{$tv_impressions_select}
					SUM(tstibs.spot_count) AS airings
				FROM
					tp_spectrum_tv_impressions_by_syscode AS tstibs
				LEFT JOIN tp_spectrum_tv_zone_data AS tstzd
					ON (tstibs.syscode = tstzd.id)
				JOIN tp_spectrum_tv_accounts AS tpsta
					ON (tstibs.account_ul_id = tpsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tpajtp
					ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
				WHERE
					tpsta.frq_id IN {$accounts_sql} AND
					tpajtp.frq_advertiser_id in $advertisers_sql AND
					(tstibs.date BETWEEN ? AND ?)
				GROUP BY
					tstibs.syscode,
					tstzd.sysname
			) AS a
			ORDER BY
				a.sysname ASC
		";
		$zone_data_result = $this->db->query($get_zone_data_query, $get_zone_data_bindings);
		if($zone_data_result->num_rows() > 0)
		{
			$zones = $zone_data_result->result_array();
			return array_combine(array_column($zones, 'syscode'), $zones);
		}
		return array();
	}

	private function get_targeted_tv_creative_impressions_data(
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
		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		$accounts_sql = '(-1, ' . $this->make_bindings_sql_from_array($account_ids) . ')';
		$advertisers_sql = '(-1, ' . $this->make_bindings_sql_from_array($advertiser_id) . ')';

		$advertiser_ids = array_map('intval', $advertiser_id);

		$get_creative_data_bindings = array_merge(
			$account_ids,
			$advertiser_ids,
			[$raw_start_date, $raw_end_date]
		);

		$tv_impressions_select = $subfeature_access_permissions['are_tv_impressions_accessible'] ? 'SUM(tptic.estimated_impressions) AS total_impressions,' : '';

		$get_creative_data_sql =
		"	SELECT
				tptic.account_ul_id,
				tptic.spot_id,
				tptic.bookend_top_id,
				tptic.bookend_bottom_id,
				tptic.spot_name,
				tptic.bookend_top_name,
				tptic.bookend_bottom_name,
				{$tv_impressions_select}
				SUM(tptic.spot_count) AS airings
			FROM
				tp_spectrum_tv_impressions_by_creative AS tptic
			JOIN tp_spectrum_tv_accounts AS tpsta
				ON (tptic.account_ul_id = tpsta.account_id)
			JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				tpsta.frq_id IN {$accounts_sql} AND
				tpajtp.frq_advertiser_id in $advertisers_sql AND
				(tptic.date BETWEEN ? AND ?)
			GROUP BY
				spot_id,
				bookend_top_id,
				bookend_bottom_id
		";

		$creative_data_result = $this->db->query($get_creative_data_sql, $get_creative_data_bindings);
		$creative_data_array = $creative_data_result->result_array();

		return $creative_data_array;
	}

	private function get_zone_airing_half_hour($time, $utc_offset, $utc_dst_offset, $dst_flag)
	{
		if(empty($utc_offset)) //Normalize to Eastern Time if not available
		{
			$utc_offset = "-5";
			$utc_dst_offset = "-4";
		}

		$offset = $utc_offset;
		if ($dst_flag == 1)
		{
			$offset = $utc_dst_offset;
		}
		$timezone_abbreviation = $this->get_timezone_abbreviation($offset, $dst_flag);
		$time = date("Y-m-d h:i A", strtotime($offset.' hours', strtotime($time)));
		$date = date("Y-m-d", strtotime($time));
		if($date == date('Y-m-d', strtotime($offset.' hours', strtotime('now'))))
		{
			$date_time_string = "Today, ";
		}
		else if($date == date('Y-m-d', strtotime("+1 day ".$offset. ' hours', strtotime('now'))))
		{
			$date_time_string = "Tomorrow, ";
		}
		else
		{
			$date_time_string = date('l', strtotime($time)).", ";
		}
		$minute = date("i", strtotime($time));
		$is_first_half_hour = $minute < 30;
		if($is_first_half_hour)
		{
			return $date_time_string.date("g:00", strtotime($time))." to ".date("g:30 A", strtotime($time))." ".$timezone_abbreviation;
		}
		else
		{
			return $date_time_string.date("g:30", strtotime($time))." to ".date("g:00 A", strtotime("$time + 1 hour"))." ".$timezone_abbreviation;
		}
	}

	private function get_timezone_abbreviation($timezone_offset, $dst_flag)
	{
		if($dst_flag)
		{
			switch($timezone_offset)
			{
				case "-9":
					return "HADT";
					break;
				case "-8":
					return "AKDT";
					break;
				case "-7":
					return "PDT";
					break;
				case "-6":
					return "MDT";
					break;
				case "-5":
					return "CDT";
					break;
				case "-4":
					return "EDT";
					break;
			}
		}
		else
		{
			switch($timezone_offset)
			{
				case "-10":
					return "HAST";
					break;
				case "-9":
					return "AKST";
					break;
				case "-8":
					return "PST";
					break;
				case "-7":
					return "MST";
					break;
				case "-6":
					return "CST";
					break;
				case "-5":
					return "EST";
					break;
			}
		}
		return "GMT".str_replace("0", "", substr($timezone_offset, 0, strpos($timezone_offset, ":")));
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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
					c.business_id IN '.$advertisers_sql .' AND
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
					c.business_id IN '.$advertisers_sql .' AND
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

		$advertiser_bindings = $advertiser_id;

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
		$build_data_type,
		$unused_timezone_offset
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
		$build_data_type,
		$unused_timezone_offset
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
		$build_data_type,
		$unused_timezone_offset
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
		$build_data_type,
		$unused_timezone_offset
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
		$has_heatmap_data = false;
		$has_pl_heatmap_access = $this->vl_platform_model->is_feature_accessible($this->tank_auth->get_user_id(), 'report_heatmap');

		if($has_pl_heatmap_access)
		{
			$campaign_ids = $this->get_frequence_campaign_ids_with_pre_roll_filter($data_set, $campaign_set);
			$heatmap_geojson_with_data['heatmap'] = $this->map->get_report_heatmap_geojson_and_data($advertiser_id, $campaign_ids, $raw_start_date, $raw_end_date);
			$heatmap_geojson_with_data['geofence_multipoints'] = $this->map->get_report_heatmap_geofencing_data($advertiser_id, $campaign_ids, $raw_start_date, $raw_end_date);

			if($heatmap_geojson_with_data['heatmap'])
			{
				$has_heatmap_data = true;
				$heatmap_data_set = array(
					'display_function' => 'build_heatmap_from_data',
					'display_selector' => '#heatmap_container',
					'display_data' => $heatmap_geojson_with_data
				);
			}

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
				$table_data->caption = "Geographic Performance";
				$result = $this->get_table_subproduct_result($table_data, $has_heatmap_data);

				if($has_heatmap_data)
				{
					$result['subproduct_data_sets'][] = $heatmap_data_set;
				}

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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
		$view_throughs_engagement_total_sql = "";
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
						c.business_id IN '.$advertisers_sql .' AND
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
							JOIN cup_versions AS cv ON (cv.campaign_id = cmp.id)
							JOIN cup_creatives AS cr ON (cr.version_id = cv.id)
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

		$advertiser_bindings = $advertiser_id;

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
		$build_data_type,
		$unused_timezone_offset
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
				$table_data->caption="Daily Interactions";
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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
				cmp.business_id IN $advertisers_sql AND
				ss.is_approved = 1 AND
				ss.campaign_id IN $campaigns_sql
			$sort_sql
		";

		$advertiser_bindings = $advertiser_id;

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
		$build_data_type,
		$unused_timezone_offset
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
				$table_data->caption = "Recent Screenshots";
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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
						c.business_id IN $advertisers_sql AND
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
						c.business_id IN $advertisers_sql AND
						b.Date BETWEEN ? AND ? AND
						a.campaign_id IN $campaigns_sql AND
						a.IsRetargeting = 1
					GROUP BY size
				)
			) AS u
			GROUP BY size
			$sort_sql
		";

		$advertiser_bindings = $advertiser_id;

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
		$build_data_type,
		$unused_timezone_offset
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
				$table_data->caption = "Ad Size Performance";
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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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

		$type_sql = report_digital_overview_type::targeted_display." AS type,";
		if($pre_roll_value)
		{
			$type_sql = report_digital_overview_type::targeted_pre_roll." AS type,";
		}

		$summary_frequence_sql = "
			(
				SELECT
					$type_sql
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
					cmp.business_id IN $advertisers_sql
					AND rcad.date BETWEEN ? AND ?
					$frequence_campaigns_sql
				$date_group_by_sql
			)
		";

		$summary_frequence_bindings = array_merge(
				$advertiser_id,
				array(
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
			if((!empty($ids) || $force_include_sql_for_empty_sets) &&
				!empty($data_by_type[$type]['sql'])
			)
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
		$tmpi_clicks_campaign_type,
		$product
	)
	{
		$is_clicks_product_for_clicks = (int)($product === report_product_tab_html_id::targeted_clicks_product || $product === null);
		$is_inventory_product_for_clicks = (int)($product === report_product_tab_html_id::targeted_inventory_product || $product === null);

		$has_dates = !empty($date_bindings);
		$sql = "
				SELECT
					$select_sql
				FROM
					$vl_advertiser_to_tmpi_account_from_sql
					JOIN tp_tmpi_accounts AS ta
						ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
					JOIN tp_tmpi_account_products AS tap
						ON (ta.account_id = tap.account_id)
					JOIN tp_tmpi_display_clicks AS tdc
						ON (
							tap.account_id = tdc.account_id AND
							tap.date = tdc.captured AND
							(
								($is_clicks_product_for_clicks AND tap.visits = 1 AND tdc.campaign_type = 0) OR
								($is_inventory_product_for_clicks AND tap.leads = 1 AND tdc.campaign_type = 1)
							)
						)
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
		$subfeature_access_permissions,
		$product = null,
		$is_digital_overview = false,
		$is_csv_download = false
	)
	{
		$is_inventory_product_for_local_performance = 0;
		$is_directories_product_for_local_performance = 0;
		$is_inventory_product_for_leads = 0;
		$is_directories_product_for_leads = 0;

		$digital_overview_exclude = (int)(!$is_digital_overview);

		if($product === report_product_tab_html_id::targeted_directories_product || $product === null)
		{
			$is_directories_product_for_local_performance = 1;
			$is_directories_product_for_leads = 1;
		}

		if($product === report_product_tab_html_id::targeted_inventory_product || $product === null)
		{
			$is_inventory_product_for_local_performance = 1;
			$is_inventory_product_for_leads = 1;
		}

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

			$group_by_sql = "";
			$order_by_sql = "";
			if($is_digital_overview)
			{
				$group_by_sql = "
					GROUP BY
						type";

				$order_by_sql = "
					ORDER BY
						type";
			}
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
							'.report_digital_overview_type::targeted_display.' AS type,
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
							JOIN cup_versions AS cv ON (cv.campaign_id = cmp.id)
							JOIN cup_creatives AS cr ON (cr.version_id = cv.id)
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
				$is_csv_download
			);

			$inner_display_sql =
			"	SELECT
					type AS type,
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
					{$summary_display_sql}
					{$ad_interactions_sql}
				) as accumulate
				GROUP BY date
				ORDER BY date
			";
			$display_sql =
			"	SELECT
					type AS type,
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
					{$inner_display_sql}
				) AS mask
			";
			$display_bindings = array_merge(
				$summary_display_bindings,
				$ad_interactions_bindings
			);
			if($is_csv_download && $product === report_product_tab_html_id::display_product)
			{
				$response = $this->db->query($inner_display_sql, $display_bindings);
				return $response->result_array();
			}
			$frequence_summary_data_by_type = array(
				report_campaign_type::frequence_display => array(
					'sql' => $display_sql,
					'bindings' => $display_bindings
				),
				report_campaign_type::frequence_pre_roll => array(
					'sql' => $summary_pre_roll_sql,
					'bindings' => array_merge(
						$summary_pre_roll_bindings
					)
				)
			);

			if($is_csv_download && $product === report_product_tab_html_id::pre_roll_product)
			{
				$response = $this->db->query(
					$frequence_summary_data_by_type[report_campaign_type::frequence_pre_roll]['sql'],
					$frequence_summary_data_by_type[report_campaign_type::frequence_pre_roll]['bindings']
				);
				return $response->result_array();
			}

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
				'.report_digital_overview_type::targeted_clicks.' AS type,
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

			$tmpi_common_inventory_select_sql = '
				'.report_digital_overview_type::targeted_inventory.' AS type,
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
				0 AS has_targeted_clicks,
				1 AS has_targeted_inventory,
				0 AS has_targeted_directories
				, SUM(tdc.impressions) AS null_detector
			';
			$tmpi_common_clicks_group_by_sql = '';

			$tmpi_summary_data_sql_by_type = array(
				report_campaign_type::tmpi_clicks => array(
					'sql' => '('.
						$this->get_targeted_common_clicks_sql(
							$tmpi_common_clicks_select_sql,
							$vl_advertiser_to_tmpi_account_from_sql,
							$vl_advertiser_to_tmpi_account_where_sql,
							$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_clicks],
							$tmpi_common_clicks_group_by_sql,
							$tmpi_dates,
							tmpi_clicks_campaign_type::clicks,
							$product
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
					'sql' => "",
					'bindings' => array()
				),
				report_campaign_type::tmpi_inventory => array(
					'sql' => '('.
						$this->get_targeted_common_clicks_sql(
							$tmpi_common_inventory_select_sql,
							$vl_advertiser_to_tmpi_account_from_sql,
							$vl_advertiser_to_tmpi_account_where_sql,
							$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory],
							$tmpi_common_clicks_group_by_sql,
							$tmpi_dates,
							tmpi_clicks_campaign_type::smart_ads,
							$product
						)
					.')' ,
					'bindings' => array_merge(
						$this->get_targeted_common_clicks_bindings(
							$vl_advertiser_to_tmpi_account_bindings,
							$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
							$tmpi_dates,
							tmpi_clicks_campaign_type::smart_ads
						)
					)
				)
			);

			if($product === null)
			{
				$tmpi_campaign_ids_in_leads_bindings = array_merge(array_unique($tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory], SORT_NUMERIC), array());
				$tmpi_campaign_ids_in_leads_values_sql = $this->generate_sql_for_ids($tmpi_campaign_ids_in_leads_bindings, null);

				$tmpi_campaign_ids_in_directories_bindings = array_merge(array_unique($tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories], SORT_NUMERIC), array());
				$tmpi_campaign_ids_in_directories_values_sql = $this->generate_sql_for_ids($tmpi_campaign_ids_in_directories_bindings, null);
			}
			else
			{
				$tmpi_campaign_ids_in_leads_bindings = array_merge(array_unique(array_merge($tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory], $tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories]), SORT_NUMERIC), array());
				$tmpi_campaign_ids_in_leads_values_sql = $this->generate_sql_for_ids($tmpi_campaign_ids_in_leads_bindings, null);

				$tmpi_campaign_ids_in_directories_bindings = $tmpi_campaign_ids_in_leads_bindings;
				$tmpi_campaign_ids_in_directories_values_sql = $this->generate_sql_for_ids($tmpi_campaign_ids_in_directories_bindings, null);
			}

			$tmpi_summary_data_sql_by_type[] = array(
				'sql' => '
					(
						SELECT
							'.report_digital_overview_type::targeted_directories.' AS type,
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
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_local_performance AS tlp
								ON (
									tap.account_id = tlp.account_id AND
									tap.date = tlp.captured AND
									(
										('.$is_directories_product_for_local_performance.' AND tap.directories = 1) OR
										('.$digital_overview_exclude.' AND '.$is_inventory_product_for_local_performance.' AND tap.leads = 1)
									)
								)
						WHERE
							'.$vl_advertiser_to_tmpi_account_where_sql.'
							'.$tmpi_campaign_ids_in_directories_values_sql.'
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
							'.report_digital_overview_type::targeted_directories.' AS type,
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
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_local_performance AS tlp
								ON (
									tap.account_id = tlp.account_id AND
									tap.date = tlp.captured AND
									(
										('.$is_directories_product_for_local_performance.' AND tap.directories = 1) OR
										('.$digital_overview_exclude.' AND '.$is_inventory_product_for_local_performance.' AND tap.leads = 1)
									)
								)
						WHERE
							'.$vl_advertiser_to_tmpi_account_where_sql.'
							'.$tmpi_campaign_ids_in_directories_values_sql.'
							AND tlp.captured BETWEEN ? AND ?
							AND (
								tlp.short_name = "Map Views/Directions" OR
								tlp.short_name = "Printed Info"
							)
					)
					UNION ALL
					(
						SELECT
							'.report_digital_overview_type::targeted_directories.' AS type,
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
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_local_performance AS tlp
								ON (
									tap.account_id = tlp.account_id AND
									tap.date = tlp.captured AND
									(
										('.$is_directories_product_for_local_performance.' AND tap.directories = 1) OR
										('.$digital_overview_exclude.' AND '.$is_inventory_product_for_local_performance.' AND tap.leads = 1)
									)
								)
						WHERE
							'.$vl_advertiser_to_tmpi_account_where_sql.'
							'.$tmpi_campaign_ids_in_directories_values_sql.'
							AND tlp.captured BETWEEN ? AND ?
							AND (
								tlp.short_name = "Clicks to Websites" OR
								tlp.short_name = "Inventory Clicks" OR
								tlp.short_name = "Appointment Clicks"
							)
					)
					UNION ALL
					(
						SELECT
							'.report_digital_overview_type::targeted_inventory.' AS type,
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
									JOIN tp_tmpi_account_products AS tap
										ON (ta.account_id = tap.account_id)
									JOIN tp_tmpi_email_leads AS tel
										ON (
											tap.account_id = tel.account_id AND
											tap.date = tel.captured AND
											(
												('.$is_inventory_product_for_leads.' AND tap.leads = 1) OR
												('.$digital_overview_exclude.' AND '.$is_directories_product_for_leads.' AND tap.directories = 1)
											)
										)
								WHERE
									'.$vl_advertiser_to_tmpi_account_where_sql.'
									'.$tmpi_campaign_ids_in_leads_values_sql.'
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
									JOIN tp_tmpi_account_products AS tap
										ON (ta.account_id = tap.account_id)
									JOIN tp_tmpi_phone_leads AS tpl
										ON (
											tap.account_id = tpl.account_id AND
											tap.date = tpl.captured AND
											(
												('.$is_inventory_product_for_leads.' AND tap.leads = 1) OR
												('.$digital_overview_exclude.' AND '.$is_directories_product_for_leads.' AND tap.directories = 1)
											)
										)
								WHERE
									'.$vl_advertiser_to_tmpi_account_where_sql.'
									'.$tmpi_campaign_ids_in_leads_values_sql.'
									AND tpl.captured BETWEEN ? AND ?
							)
						) AS leads_union
						WHERE 1
					)
				',
				'bindings' => array_merge(
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_directories_bindings,
					$tmpi_dates,
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_directories_bindings,
					$tmpi_dates,
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_directories_bindings,
					$tmpi_dates,

					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_bindings,
					$tmpi_dates,
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_bindings,
					$tmpi_dates
				)
			);

			$tmpi_accounts_bindings_by_type[] = array_merge($tmpi_campaign_ids_in_directories_bindings, $tmpi_campaign_ids_in_leads_bindings);

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
					type AS type,
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
				$group_by_sql
				$order_by_sql
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
		$is_getting_csv_data,
		$product = null
	)
	{
		$is_inventory_product_for_local_performance = 0;
		$is_directories_product_for_local_performance = 0;
		$is_inventory_product_for_leads = 0;
		$is_directories_product_for_leads = 0;

		if($product === report_product_tab_html_id::targeted_directories_product || $product === null)
		{
			$is_directories_product_for_local_performance = 1;
			$is_directories_product_for_leads = 1;
		}

		if($product === report_product_tab_html_id::targeted_inventory_product || $product === null)
		{
			$is_inventory_product_for_local_performance = 1;
			$is_inventory_product_for_leads = 1;
		}

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
				$frequence_bindings = array_merge($frequence_bindings, $advertiser_id);

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
							JOIN cup_versions AS cv ON (cv.campaign_id = cmp.id)
							JOIN cup_creatives AS cr ON (cr.version_id = cv.id)
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
				$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
							AND cmp.business_id IN $advertisers_sql
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
							tmpi_clicks_campaign_type::clicks,
							$product
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
					'sql' => '',
					'bindings' => array()
				),
				report_campaign_type::tmpi_inventory => array(
					'sql' => '
						('.
						 $this->get_targeted_common_clicks_sql(
							$tmpi_common_clicks_select_sql,
							$vl_advertiser_to_tmpi_account_from_sql,
							$vl_advertiser_to_tmpi_account_where_sql,
							$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory],
							$tmpi_common_clicks_group_by_sql,
							$tmpi_dates,
							tmpi_clicks_campaign_type::smart_ads,
							$product
						)
						.')',
					'bindings' => $this->get_targeted_common_clicks_bindings(
						$vl_advertiser_to_tmpi_account_bindings,
						$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory],
						$tmpi_dates,
						tmpi_clicks_campaign_type::smart_ads
					)
				)
			);

			$tmpi_campaign_ids_in_leads_and_directories_bindings = array_merge(array_unique(array_merge($tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory], $tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_directories]), SORT_NUMERIC), array());
			$tmpi_campaign_ids_in_leads_and_directories_values_sql = $this->generate_sql_for_ids($tmpi_campaign_ids_in_leads_and_directories_bindings, null);

			$tmpi_graph_data_by_type[] = array(
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
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_local_performance AS tlp
								ON (
									tap.account_id = tlp.account_id AND
									tap.date = tlp.captured AND
									(
										('.$is_directories_product_for_local_performance.' AND tap.directories = 1) OR
										('.$is_inventory_product_for_local_performance.' AND tap.leads = 1)
									)
								)
						WHERE
							'.$vl_advertiser_to_tmpi_account_where_sql.'
							'.$tmpi_campaign_ids_in_leads_and_directories_values_sql.'
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
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_local_performance AS tlp
								ON (
									tap.account_id = tlp.account_id AND
									tap.date = tlp.captured AND
									(
										('.$is_directories_product_for_local_performance.' AND tap.directories = 1) OR
										('.$is_inventory_product_for_local_performance.' AND tap.leads = 1)
									)
								)
						WHERE
							'.$vl_advertiser_to_tmpi_account_where_sql.'
							'.$tmpi_campaign_ids_in_leads_and_directories_values_sql.'
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
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_local_performance AS tlp
								ON (
									tap.account_id = tlp.account_id AND
									tap.date = tlp.captured AND
									(
										('.$is_directories_product_for_local_performance.' AND tap.directories = 1) OR
										('.$is_inventory_product_for_local_performance.' AND tap.leads = 1)
									)
								)
						WHERE
							'.$vl_advertiser_to_tmpi_account_where_sql.'
							'.$tmpi_campaign_ids_in_leads_and_directories_values_sql.'
							AND tlp.captured BETWEEN ? AND ?
							AND (
								tlp.short_name = "Clicks to Websites" OR
								tlp.short_name = "Inventory Clicks" OR
								tlp.short_name = "Appointment Clicks"
							)
						GROUP BY Date
					)
					UNION ALL
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
									JOIN tp_tmpi_account_products AS tap
										ON (ta.account_id = tap.account_id)
									JOIN tp_tmpi_email_leads AS tel
										ON (
											tap.account_id = tel.account_id AND
											tap.date = tel.captured AND
											(
												('.$is_inventory_product_for_leads.' AND tap.leads = 1) OR
												('.$is_directories_product_for_leads.' AND tap.directories = 1)
											)
										)
								WHERE
									'.$vl_advertiser_to_tmpi_account_where_sql.'
									'.$tmpi_campaign_ids_in_leads_and_directories_values_sql.'
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
									JOIN tp_tmpi_account_products AS tap
										ON (ta.account_id = tap.account_id)
									JOIN tp_tmpi_phone_leads AS tpl
										ON (
											tap.account_id = tpl.account_id AND
											tap.date = tpl.captured AND
											(
												('.$is_inventory_product_for_leads.' AND tap.leads = 1) OR
												('.$is_directories_product_for_leads.' AND tap.directories = 1)
											)
										)
								WHERE
									'.$vl_advertiser_to_tmpi_account_where_sql.'
									'.$tmpi_campaign_ids_in_leads_and_directories_values_sql.'
									AND tpl.captured BETWEEN ? AND ?
								GROUP BY tpl.captured
							)
						) AS leads_union
						GROUP BY date
					)
				',
				'bindings' => array_merge(
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_and_directories_bindings,
					$tmpi_dates,

					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_and_directories_bindings,
					$tmpi_dates,

					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_and_directories_bindings,
					$tmpi_dates,

					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_and_directories_bindings,
					$tmpi_dates,

					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_campaign_ids_in_leads_and_directories_bindings,
					$tmpi_dates
				)
			);

			$tmpi_accounts_bindings_by_type[] = $tmpi_campaign_ids_in_leads_and_directories_bindings;

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
		$build_data_type,
		$unused_timezone_offset
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
			$header_cell->tooltip = $header_data[2];

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
			$header_cell->orderable = $column_format->orderable;
			$header_cell->searchable = $column_format->searchable;
			$header_cell->visible = $column_format->visible;

			if($column_format->csv_exportable !== null)
			{
				$header_cell->csv_exportable = $column_format->csv_exportable;
			}

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
		$build_data_type,
		$unused_timezone_offset	)
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
				$table_data->caption="Completion Rates";
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
		$has_heatmap_geojson = false
	)
	{
		$html_scaffolding = $has_heatmap_geojson ? '<div id="heatmap_container"></div>' : '';
		$html_scaffolding .= '
			<div id="subproduct_table" class="geography">
			</div>
		';

		$table_data_set = array(
			'display_function' => 'build_subproduct_table',
			'display_selector' => '#subproduct_table',
			'display_data'	 => $table_data
		);

		$data_sets = array(
			$table_data_set
		);

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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));

		$query = "
			SELECT
				SUM(impressions) AS impressions,
				SUM(starts) AS video_started,
				SUM(25_percents) AS 25_percent_viewed,
				SUM(50_percents) AS 50_percent_viewed,
				SUM(75_percents) AS 75_percent_viewed,
				SUM(100_percents) AS 100_percent_viewed,
				MAX(newest_date) AS newest_date
			FROM
			(
				(
					SELECT
						0 AS impressions,
						SUM(vid_d.start_count) AS starts,
						SUM(vid_d.25_percent_count) AS 25_percents,
						SUM(vid_d.50_percent_count) AS 50_percents,
						SUM(vid_d.75_percent_count) AS 75_percents,
						SUM(vid_d.100_percent_count) AS 100_percents,
						NULL as newest_date

					FROM
						AdGroups AS adg
						JOIN report_video_play_records AS vid_d
							ON (adg.ID = vid_d.AdGroupID)
						JOIN Campaigns AS cmp
							ON (adg.campaign_id = cmp.id)
					WHERE
						cmp.business_id IN $advertisers_sql AND
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
						0 AS 100_percents,
						NULL as newest_date
					FROM
						AdGroups AS adg
						JOIN report_cached_adgroup_date AS crd
							ON (adg.ID = crd.adgroup_id)
						JOIN Campaigns AS cmp
							ON (adg.campaign_id = cmp.id)
						JOIN report_video_play_records AS vid
							ON (crd.date = vid.date AND crd.adgroup_id = vid.AdGroupID)
					WHERE
						cmp.business_id IN $advertisers_sql AND
						adg.campaign_id IN $campaigns_sql AND
						adg.target_type LIKE \"%Pre-Roll%\" AND
						crd.date BETWEEN ? AND ?
				)
				UNION ALL
				(
					SELECT
						0 AS impressions,
						0 AS starts,
						0 AS 25_percents,
						0 AS 50_percents,
						0 AS 75_percents,
						0 AS 100_percents,
						MAX(date) AS newest_date
					FROM
						report_video_play_records AS rvpr
				)
			) AS u
		";

		$advertiser_bindings = $advertiser_id;

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
		if(!is_array($vl_advertiser_id))
		{
			$vl_advertiser_id=array($vl_advertiser_id);
		}
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($vl_advertiser_id).')';

		$from_sql .= "
				tp_advertisers_join_third_party_account AS ajtpa
		";
		$where_sql .= "
					ajtpa.frq_advertiser_id IN $advertisers_sql
					AND ajtpa.third_party_source = ?
		";

		// Just in case advertiser ids aren't passed as ints
		$vl_advertiser_id = array_map('intval', $vl_advertiser_id);
		$bindings = array_merge($bindings, $vl_advertiser_id, array($third_party_source));
	}

	public function get_report_campaigns($advertiser_id, $subfeature_access_permissions)
	{
		$campaigns = array();

		// TODO: filter by is pre-roll accessible?
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
		$frequence_campaigns_sql = '
			SELECT
				CASE LOWER(cmp.name)  WHEN LOWER(a.name) THEN cmp.name ELSE CONCAT(cmp.name, " (", a.name , ")")  END AS campaign_name,
				IF(GROUP_CONCAT(ag.target_type) LIKE "%Pre-Roll%", "'.frequence_product_names::pre_roll.'", "'.frequence_product_names::display.'") AS type,
				cmp.id AS campaign_id
			FROM
				Campaigns AS cmp
				JOIN AdGroups AS ag
					ON (cmp.id = ag.campaign_id)
				JOIN Advertisers a
					ON cmp.business_id = a.id
			WHERE
				cmp.business_id IN '.$advertisers_sql.'
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

		if($subfeature_access_permissions['is_tmpi_accessible'])
		{
			$spectrum_account_info_sql = ' stv.name AS name,';
			$spectrum_account_info_sql_inner = 'CASE LOWER(stv.name)  WHEN LOWER(a.name) THEN stv.name ELSE CONCAT(stv.name, " (", a.name , ")")  END AS name,';
			$spectrum_tv_campaigns_response = $this->get_spectrum_tv_data_presence(
				$advertiser_id,
				$spectrum_account_info_sql,
				null,
				null,
				null,
				false,
				$spectrum_account_info_sql_inner
			);

			if(!empty($spectrum_tv_campaigns_response))
			{
				$spectrum_tv_account_rows = $spectrum_tv_campaigns_response->result();

				foreach($spectrum_tv_account_rows as $index => $row)
				{
					$account_types = array();

					if($row->has_targeted_tv == 1)
					{
						$account_types[] = spectrum_product_names::tv;
					}

					$spectrum_group = report_organization_names::spectrum;

					foreach($account_types as $account_type)
					{
						$campaigns[] = new report_campaign(
							$spectrum_group,
							$account_type,
							$row->tv_account_id,
							$row->name
						);
					}
				}
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

			$carmercial_account_info_sql_inner = 'CASE LOWER(cd.friendly_dealer_name)  WHEN LOWER(a.name) THEN cd.friendly_dealer_name ELSE CONCAT(cd.friendly_dealer_name, " (", a.name , ")")  END AS friendly_dealer_name,';
			$carmercial_campaigns_response = $this->get_carmercial_data_presence(
				$advertiser_id,
				$carmercial_account_info_sql,
				null,
				null,
				null,
				false,
				$carmercial_account_info_sql_inner
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
		$is_overview_calculation = $product === null;
		$is_inventory_product_for_leads = (int)($product === report_product_tab_html_id::targeted_inventory_product || $is_overview_calculation);
		$is_directories_product_for_leads = (int)($product === report_product_tab_html_id::targeted_directories_product);
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

		$tmpi_campaign_type = self::$map_product_html_id_to_report_campaign_type[$product];
		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			$tmpi_campaign_type
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_email_leads AS tel
							ON (
								tap.account_id = tel.account_id AND
								tap.date = tel.captured AND
								(
									($is_inventory_product_for_leads AND tap.leads = 1) OR
									($is_directories_product_for_leads AND tap.directories = 1)
								)
							)
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_email_leads AS tel
							ON (
								tap.account_id = tel.account_id AND
								tap.date = tel.captured AND
								(
									($is_inventory_product_for_leads AND tap.leads = 1) OR
									($is_directories_product_for_leads AND tap.directories = 1)
								)
							)
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_phone_leads AS tpl
							ON (
								tap.account_id = tpl.account_id AND
								tap.date = tpl.captured AND
								(
									($is_inventory_product_for_leads AND tap.leads = 1) OR
									($is_directories_product_for_leads AND tap.directories = 1)
								)
							)
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_phone_leads AS tpl
							ON (
								tap.account_id = tpl.account_id AND
								tap.date = tpl.captured AND
								(
									($is_inventory_product_for_leads AND tap.leads = 1) OR
									($is_directories_product_for_leads AND tap.directories = 1)
								)
							)
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
		$build_data_type,
		$unused_timezone_offset
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
				"Lead Type",
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
				$table_data->caption="Performance Summary";
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
		$is_overview_calculation = $product === null;
		$is_inventory_product_for_leads = (int)($product === report_product_tab_html_id::targeted_inventory_product || $is_overview_calculation);
		$is_directories_product_for_leads = (int)($product === report_product_tab_html_id::targeted_directories_product);

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

		$tmpi_campaign_type = self::$map_product_html_id_to_report_campaign_type[$product];
		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			$tmpi_campaign_type
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_email_leads AS tel
							ON (
								tap.account_id = tel.account_id AND
								tap.date = tel.captured AND
								(
									($is_inventory_product_for_leads AND tap.leads = 1) OR
									($is_directories_product_for_leads AND tap.directories = 1)
								)
							)
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_phone_leads AS tpl
							ON (
								tap.account_id = tpl.account_id AND
								tap.date = tpl.captured AND
								(
									($is_inventory_product_for_leads AND tap.leads = 1) OR
									($is_directories_product_for_leads AND tap.directories = 1)
								)
							)
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
		$build_data_type,
		$unused_timezone_offset
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
				$table_data->caption="Contact Details";
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

		$is_inventory_product_for_search = (int)($product === report_product_tab_html_id::targeted_inventory_product || $product === null);

		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

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
				SUM(tsdp_month.months_total) AS $k_monthly_views_key,
				SUM(tsdp_year.years_total) AS $k_ytd_views_key
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_search_detail_performance AS tsdp
							ON (
								tap.account_id = tsdp.account_id AND
								tap.date = tsdp.report_month AND
								($is_inventory_product_for_search AND tap.leads = 1)
							)
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
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_search_detail_performance AS tsdp
							ON (
								tap.account_id = tsdp.account_id AND
								tap.date = tsdp.report_month AND
								($is_inventory_product_for_search AND tap.leads = 1)
							)
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
				JOIN
				(
					SELECT
						ta.account_id AS account_id,
						MAX(tap.leads) AS has_leads
					FROM
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_search_detail_performance AS tsdp
							ON (
								tap.account_id = tsdp.account_id AND
								tap.date = tsdp.report_month AND
								($is_inventory_product_for_search AND tap.leads = 1)
							)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql AND
						tsdp.report_month BETWEEN ? AND ?
					GROUP BY tsdp.account_id
				) AS tsdp_mask
				ON (tsdp_year.account_id = tsdp_mask.account_id AND tsdp_mask.has_leads = 1)
			GROUP BY
				tsdp_month.short_name
		";

		$mask_range_bindings = array($start_date, $end_date);
		$month_range_bindings = array($start_month_first_day, $end_month_last_day);
		$year_range_bindings = array($start_year_first_day, $end_year_last_day);

		$bindings = array_merge(
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$month_range_bindings,
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$year_range_bindings,
			$vl_advertiser_to_tmpi_account_bindings,
			$tmpi_account_ids,
			$mask_range_bindings
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

			assert(count($totals_row) == count($table_content[0]), 'Mismatched content, update $total_rows');

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

		$result->caption = "Inventory Totals";

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

		$is_clicks_product_for_clicks = (int)($product === report_product_tab_html_id::targeted_clicks_product || $product === null);
		$is_inventory_product_for_clicks = (int)($product === report_product_tab_html_id::targeted_inventory_product || $product === null);

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
				$caption = "Campaign Totals";
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
				$select_data_set_specific_sql = "tdc.captured AS 'date',";
				$from_data_set_specific_sql = "tp_tmpi_display_clicks ";
				$group_by_data_set_specific_sql = ", tdc.captured ";
				$table_starting_sorts = $this->get_tmpi_targeted_clicks_sorting();
				$impressions_percent_of_total_from_sql = "";
				$impressions_percent_of_total_subquery_sql = "";
				$impressions_percent_of_total_subquery_bindings = array();

				break;
			case tmpi_clicks_data_set_type::geo:
				$caption="Geographic Performance";
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

				$impressions_percent_of_total_from_sql = "100 * SUM(tdc.Impressions) / tdc_totals.total_impressions AS percent_of_total,";
				$impressions_percent_of_total_subquery_sql = "
					JOIN (
						SELECT
							tdc.record_master_id AS record_master_id,
							SUM(tdc.impressions) AS total_impressions
						FROM
							$vl_advertiser_to_tmpi_account_from_sql
							JOIN tp_tmpi_accounts AS ta
								ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
							JOIN tp_tmpi_account_products AS tap
								ON (ta.account_id = tap.account_id)
							JOIN tp_tmpi_display_clicks AS tdc
								ON (
									tap.account_id = tdc.account_id AND
									tap.date = tdc.captured AND
									(
										($is_clicks_product_for_clicks AND tap.visits = 1 AND tdc.campaign_type = 0) OR
										($is_inventory_product_for_clicks AND tap.leads = 1 AND tdc.campaign_type = 1)
									)
								)
						WHERE
							$vl_advertiser_to_tmpi_account_where_sql
							$tmpi_account_ids_sql
							AND tdc.campaign_type = $tmpi_clicks_campaign_type
							AND tdc.captured BETWEEN ? AND ?
						GROUP BY
							tdc.account_id,
							tdc.record_master_id
					) AS tdc_totals ON (tdc.record_master_id = tdc_totals.record_master_id)
				";
				$impressions_percent_of_total_subquery_bindings = array_merge(
					$vl_advertiser_to_tmpi_account_bindings,
					$tmpi_account_ids,
					$date_bindings
				);

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
				JOIN tp_tmpi_account_products AS tap
					ON (ta.account_id = tap.account_id)
				JOIN $from_data_set_specific_sql AS tdc
					ON (
						tap.account_id = tdc.account_id AND
						tap.date = tdc.captured AND
						(
							($is_clicks_product_for_clicks AND tap.visits = 1 AND tdc.campaign_type = 0) OR
							($is_inventory_product_for_clicks AND tap.leads = 1 AND tdc.campaign_type = 1)
						)
					)
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

			if(isset($caption))
			{
				$table_data->caption = $caption;
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
		$is_overview_calculation = $product === null;
		$is_inventory_product_for_local_performance = (int)($product === report_product_tab_html_id::targeted_inventory_product);
		$is_directories_product_for_local_performance = (int)(($product === report_product_tab_html_id::targeted_directories_product) || $is_overview_calculation);

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

		$tmpi_campaign_type = self::$map_product_html_id_to_report_campaign_type[$product];
		$tmpi_account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::tmpi,
			$tmpi_campaign_type
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
				JOIN tp_tmpi_account_products AS tap
					ON (ta.account_id = tap.account_id)
				JOIN tp_tmpi_local_performance AS tlp
					ON (
						tap.account_id = tlp.account_id AND
						tap.date = tlp.captured AND
						(
							($is_directories_product_for_local_performance AND tap.directories = 1) OR
							($is_inventory_product_for_local_performance AND tap.leads = 1)
						)
					)
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
		$build_data_type,
		$unused_timezone_offset
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
				"Activity Types",
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
				$table_data->caption="Profile Activity";
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
		$sorting = null,
		$result_extra_columns = null
	)
	{
		$is_inventory_product_for_performance = (int)($product === report_product_tab_html_id::targeted_inventory_product || $product === null);

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

		$thlp_extra_columns = "";
		$thlp_list_extra_columns = "";

		if(isset($result_extra_columns) && $result_extra_columns !== null)
		{
			foreach ($result_extra_columns as $extra_column)
			{
				$thlp_extra_columns = $thlp_extra_columns.",thlp.".$extra_column." AS ".$extra_column;
				$thlp_list_extra_columns = $thlp_list_extra_columns.",thlp_list.".$extra_column." AS ".$extra_column;
			}
		}

		$sql = "
			SELECT
					thlp_list.searched_item AS searched_item,
					thlp_list.num_searches AS num_searches,
					thlp_list.num_ad_details AS num_ad_details,
					thlp_list.item_price AS item_price".$thlp_list_extra_columns."
			FROM
			(
				SELECT
					thlp.listing_id AS listing_id,
					thlp.listing_text AS searched_item,
					thlp.listing_search AS num_searches,
					thlp.listing_details AS num_ad_details,
					thlp.listing_price AS item_price".$thlp_extra_columns."
				FROM
					$vl_advertiser_to_tmpi_account_from_sql
					JOIN tp_tmpi_accounts AS ta
						ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
					JOIN tp_tmpi_account_products AS tap
						ON (ta.account_id = tap.account_id)
					JOIN tp_tmpi_hi_lo_performance AS thlp
						ON (
							tap.account_id = thlp.account_id AND
							tap.date = thlp.date AND
							($is_inventory_product_for_performance AND tap.leads = 1)
						)
				WHERE
					$vl_advertiser_to_tmpi_account_where_sql
					$tmpi_account_ids_sql
					AND thlp.date BETWEEN ? AND ?
				ORDER BY
					thlp.date DESC
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
		$build_data_type,
		$unused_timezone_offset
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
				"Vehicle Details",
				new report_table_column_format(
					$column_class_prefix.'_top_searched_items',
					'asc',
					$this->format_string_callable
				),
				"Vehicle details"
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
			$core_sorting,
			array('listing_price_range')
		);

		$table_content = $response->result_array();
		$format_callable = array($this, 'format_vehicles_table_row');
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
				$table_data->caption="Top Searched Vehicles";
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
		$is_inventory_product_for_vehicles_inventory = (int)($product === report_product_tab_html_id::targeted_inventory_product || $product === null);

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
				JOIN tp_tmpi_account_products AS tap
					ON (ta.account_id = tap.account_id)
				JOIN tp_tmpi_vehicle_summaries AS tvs
					ON (
						tap.account_id = tvs.account_id AND
						tap.date = tvs.date AND
						($is_inventory_product_for_vehicles_inventory AND tap.leads = 1)
					)
				JOIN
				(
					SELECT
						ta.account_id AS account_id,
						MAX(tvs.date) AS date
					FROM
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta
							ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						JOIN tp_tmpi_account_products AS tap
							ON (ta.account_id = tap.account_id)
						JOIN tp_tmpi_vehicle_summaries AS tvs
							ON (
								tap.account_id = tvs.account_id AND
								tap.date = tvs.date AND
								($is_inventory_product_for_vehicles_inventory AND tap.leads = 1)
							)
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
		$build_data_type,
		$unused_timezone_offset
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
				"Inventory category",
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
				$table_data->caption = "Inventory Listing Summary";
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
		$is_inventory_product_for_performance = (int)($product === report_product_tab_html_id::targeted_inventory_product || $product === null);

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
					JOIN tp_tmpi_account_products AS tap
						ON (ta.account_id = tap.account_id)
					JOIN tp_tmpi_hi_lo_performance AS thlp
						ON (
							tap.account_id = thlp.account_id AND
							tap.date = thlp.date AND
							($is_inventory_product_for_performance AND tap.leads = 1)
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						$tmpi_account_ids_sql
						AND thlp.date BETWEEN ? AND ?
					ORDER BY
						thlp.date DESC
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
		$build_data_type,
		$unused_timezone_offset
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
			'A'	=> 'Average',
			'L'	=> 'Low',
			'H'	=> 'High',
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
		$build_data_type,
		$unused_timezone_offset
	)
	{
		$html_scaffolding = '
			<div id="subproduct_table" class="content_main" style="clear:both;">
			</div>
		';

		$table_data = $this->get_carmercial_targeted_content_table_data(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$product,
			$subproduct,
			$subfeature_access_permissions,
			$build_data_type,
			$unused_timezone_offset
		);

		$table_data_set = array(
			'display_function' => 'build_subproduct_table',
			'display_selector' => '#subproduct_table',
			'display_data'	 => $table_data
		);

		$data_sets = array(
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
			$core_sorting,
			4
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
		$sorting = null,
		$number_of_rows = null
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

		$sort_sql = "
			ORDER BY
				total DESC
		";

		$limit_sql = "";
		if($number_of_rows != null)
		{
			$limit_sql = "
				LIMIT {$number_of_rows}
			";
		}

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

	public function get_carmercial_targeted_content_overview_total(
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
				SUM(crd.count) AS total
		";

		return $this->get_carmercial_targeted_content_generic(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$select_sql
		);
	}

	public function get_carmercial_targeted_content_timeseries_data(
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
				crd.date,
				SUM(crd.count) AS views
		";

		$group_by_sql = "
			GROUP BY
				crd.date
		";

		$sort_sql = "
			ORDER BY
				crd.date ASC
		";

		return $this->get_carmercial_targeted_content_generic(
			$vl_advertiser_id,
			$campaign_set,
			$raw_start_date,
			$raw_end_date,
			$select_sql,
			$group_by_sql,
			$sort_sql
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
		$build_data_type,
		$unused_timezone_offset
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

				$result->caption = "Recent Video Views";

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

		$common_bindings = array_merge($date_bindings, $advertiser_id);

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
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
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
						adv.id IN $advertisers_sql
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
						adv.id IN $advertisers_sql
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
				'sql' => "
					SELECT
						ta.account_id AS account_id,
						$account_data_sql
						IF(MAX(ap.visits) IS NULL, 0, MAX(ap.visits)) AS targeted_clicks,
						0 AS targeted_inventory,
						0 AS targeted_directories
					FROM
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_account_products AS ap ON (
							ta.account_id = ap.account_id
							".($has_dates ? "AND ap.date BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_clicks]."
					GROUP BY
						ta.account_id
				",
				'bindings' => array_merge(
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_clicks]
				)
			),
			report_campaign_type::tmpi_inventory => array(
				'sql' => "
					SELECT
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						IF(MAX(ap.leads) IS NULL, 0, MAX(ap.leads)) AS targeted_inventory,
						0 AS targeted_directories
					FROM
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_account_products AS ap ON (
							ta.account_id = ap.account_id
							".($has_dates ? "AND ap.date BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_tmpi_account_where_sql
						".$tmpi_accounts_sql_by_type[report_campaign_type::tmpi_inventory]."
					GROUP BY
						ta.account_id
				",
				'bindings' => array_merge(
					$common_bindings,
					$tmpi_accounts_bindings_by_type[report_campaign_type::tmpi_inventory]
				)
			),
			report_campaign_type::tmpi_directories => array(
				'sql' => "
					SELECT
						ta.account_id AS account_id,
						$account_data_sql
						0 AS targeted_clicks,
						0 AS targeted_inventory,
						IF(MAX(ap.directories) IS NULL, 0, MAX(ap.directories)) AS targeted_directories
					FROM
						$vl_advertiser_to_tmpi_account_from_sql
						JOIN tp_tmpi_accounts AS ta ON (ajtpa.frq_third_party_account_id = ta.id && ajtpa.third_party_source = ".report_campaign_organization::tmpi.")
						LEFT JOIN tp_tmpi_account_products AS ap ON (
							ta.account_id = ap.account_id
							".($has_dates ? "AND ap.date BETWEEN ? AND ?" : "")."
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
		$condense_to_one_row = false,
		$dealer_data_sql_inner = ""
	)
	{
		if ($dealer_data_sql_inner =="" && $dealer_data_sql != "")
		{
			$dealer_data_sql_inner=$dealer_data_sql;
		}
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
						$dealer_data_sql_inner
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
					JOIN Advertisers a on a.id = ajtpa.frq_advertiser_id
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

	public function get_spectrum_tv_data_presence(
		$advertiser_id,
		$spectrum_data_sql = "",
		$start_date = null,
		$end_date = null,
		report_campaign_set $campaign_set_parameter = null,
		$condense_to_one_row = false,
		$spectrum_data_sql_inner = ""
	)
	{
		if ($spectrum_data_sql_inner == "" && $spectrum_data_sql != "")
		{
			$spectrum_data_sql_inner = $spectrum_data_sql;
		}
		$has_dates = false;
		$date_bindings = array();
		if($start_date !== null && $end_date !== null)
		{
			$has_dates = true;
			//$date_bindings = array($start_date, $end_date);
		}

		$campaign_set = $campaign_set_parameter;
		if($campaign_set_parameter === null)
		{
			$campaign_set = new report_campaign_set(array());
		}

		$vl_advertiser_to_spectrum_account_from_sql = "";
		$vl_advertiser_to_spectrum_account_where_sql = "";
		$vl_advertiser_to_spectrum_account_bindings = array();

		$this->add_vl_advertiser_to_third_party_account_sql_and_bindings(
			$vl_advertiser_to_spectrum_account_from_sql,
			$vl_advertiser_to_spectrum_account_where_sql,
			$vl_advertiser_to_spectrum_account_bindings,
			$advertiser_id,
			report_campaign_organization::spectrum
		);

		$common_bindings = array_merge($date_bindings, $vl_advertiser_to_spectrum_account_bindings);

		$spectrum_accounts_sql_by_type = array();
		$spectrum_accounts_bindings_by_type = array();

		$this->get_campaigns_sql_and_bindings_by_type(
			$spectrum_accounts_sql_by_type,
			$spectrum_accounts_bindings_by_type,
			$campaign_set,
			report_campaign_organization::spectrum,
			'AND stv.frq_id IN ',
			true
		);

		$spectrum_accounts_data_sql_by_type = array(
			report_campaign_type::spectrum_tv => array(
				'sql' => "
					SELECT
						stv.frq_id AS spectrum_id,
						$spectrum_data_sql_inner
						IF(stv.account_id IS NULL, 0, 1) AS targeted_tv
					FROM
						$vl_advertiser_to_spectrum_account_from_sql
						JOIN tp_spectrum_tv_accounts AS stv
							ON (ajtpa.frq_third_party_account_id = stv.frq_id
								AND ajtpa.third_party_source = ".report_campaign_organization::spectrum."
							)
						JOIN Advertisers a on a.id=ajtpa.frq_advertiser_id
						LEFT JOIN tp_spectrum_tv_schedule AS tpsts ON (
							stv.account_id = tpsts.account_ul_id
							".(/*$has_dates - TODO: FIGURE OUT DATE TIME STUFF*/ false ? "AND tpsts.air_date_time BETWEEN ? AND ?" : "")."
						)
					WHERE
						$vl_advertiser_to_spectrum_account_where_sql
						".$spectrum_accounts_sql_by_type[report_campaign_type::spectrum_tv]."
					GROUP BY
						stv.frq_id
				",
				'bindings' => array_merge(
					$common_bindings,
					$spectrum_accounts_bindings_by_type[report_campaign_type::spectrum_tv]
					)
				)
			);

		$ignore_empty_id_sets_to_get_campaigns_for_advertiser = $campaign_set_parameter == null;

		$spectrum_accounts_sql = "";
		$spectrum_accounts_bindings = array();
		$this->resolve_campaign_type_data_to_sql_and_bindings(
			$spectrum_accounts_sql,
			$spectrum_accounts_bindings,
			$spectrum_accounts_bindings_by_type,
			$spectrum_accounts_data_sql_by_type,
			$ignore_empty_id_sets_to_get_campaigns_for_advertiser
		);

		$response = null;

		$outer_group_by = "GROUP BY tv_account_id";
		if($condense_to_one_row === true)
		{
			$outer_group_by = "";
		}

		if(!empty($spectrum_accounts_sql))
		{
			$sql = "
				SELECT
					stv.spectrum_id AS tv_account_id,
					$spectrum_data_sql
					MAX(targeted_tv) AS has_targeted_tv
				FROM
				(
					$spectrum_accounts_sql
				) AS stv
				$outer_group_by
			";
			$response = $this->db->query($sql, $spectrum_accounts_bindings);
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

	public function get_campaigns_for_applicable_products_on_overview_map($campaign_set)
	{
		$products_and_ids = [];
		$products_and_ids['preroll'] = $this->get_frequence_campaign_ids_with_pre_roll_filter(k_impressions_clicks_data_set::pre_roll_only, $campaign_set);
		$products_and_ids['display'] = $this->get_frequence_campaign_ids_with_pre_roll_filter(k_impressions_clicks_data_set::non_pre_roll, $campaign_set);
		$products_and_ids['tv'] = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		return $products_and_ids;
	}

	public function get_tv_regions_from_advertiser_id_and_campaign_ids($advertiser_id, $account_ids, $start_date, $end_date, $timezone_offset)
	{
		if(!$advertiser_id || !$account_ids)
		{
			return array();
		}

		$timezone_string = " +{$timezone_offset} hour";
		$timezone_string .= (abs(intval($timezone_offset)) == 1) ? '' : 's';

		$start_time = date("Y-m-d H:i:s", strtotime($start_date . $timezone_string));
		$end_time = date("Y-m-d H:i:s", strtotime($end_date . $timezone_string));

		$advertisers_sql = '(-1,' . $this->make_bindings_sql_from_array($advertiser_id) . ')';
		$accounts_sql = '(-1,' . $this->make_bindings_sql_from_array($account_ids) . ')';

		$bindings = array_merge(array($start_time, $end_time), $account_ids, $advertiser_id);

		$sql =
		"	SELECT
				grc.json_regions AS zctas,
				sys.account_id AS relavant_campaigns
			FROM `geo_regions_collection` AS grc
			JOIN tp_spectrum_syscode_region_join AS tssrj
				ON tssrj.geo_regions_collection_id = grc.`id`
			JOIN
			(	SELECT DISTINCT
					tstpas.zone_sys_code AS syscode,
					tpsta.frq_id AS account_id
				FROM
					tp_spectrum_tv_previous_airing_data AS tstpas
					JOIN tp_spectrum_tv_accounts AS tpsta
						ON (tstpas.account_ul_id = tpsta.account_id)
					JOIN tp_advertisers_join_third_party_account AS tpajtp
						ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
					WHERE
						(tstpas.date BETWEEN ? AND ?) AND
						tpsta.frq_id IN {$accounts_sql} AND
						tpajtp.frq_advertiser_id IN {$advertisers_sql}
			) AS sys
				ON sys.syscode = tssrj.`syscode_int`;
		";
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() > 0)
		{
			$combined_regions = array();
			foreach ($result->result_array() as $region_data)
			{
				$campaign_int = intval($region_data['relavant_campaigns']);
				$zcta_ints = array_map('intval', json_decode($region_data['zctas'], true));

				if((isset($combined_regions[$campaign_int])))
				{
					$combined_regions[$campaign_int] = array_merge($combined_regions[$campaign_int], $zcta_ints);
					$combined_regions[$campaign_int] = array_unique($combined_regions[$campaign_int]);
				}
				else
				{
					$combined_regions[$campaign_int] = $zcta_ints;
				}
			}
			return $combined_regions;
		}
		return array();
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
			report_product_tab_html_id::targeted_clicks_product,
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
			for ($ctr = 0 ; $ctr <= $days_diff; $ctr += $term_increment)
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
	public function get_overview_first_airings($advertiser_id, $campaign_set, $timezone_offset)
	{
		$return_data = array();
		if ($campaign_set == "")
			return $return_data;

		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		if(empty($account_ids))
			return $return_data;

		$date_for_timezone =  new DateTime(null, new DateTimeZone('America/New_York'));
		$dst_flag = $date_for_timezone->format('I');

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';

		$dtz = new DateTimeZone('GMT');
		$eastern_time = new DateTime('now', $dtz);
		$eastern_time_string = $eastern_time->format("Y-m-d H:i:s");

		$get_airings_data_bindings = array();

		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';

		$get_airings_data_bindings = array_merge(
			$account_ids,
			$advertiser_id,
			array
			(
				$eastern_time_string
			),

			$account_ids,
			$advertiser_id,
			array
			(
				$eastern_time_string
			)
		);

		$sql =
		"
			SELECT
				tv_airings.*
			FROM
			(SELECT
				stvs.account_ul_id,
				stvs.client_traffic_id,
				stvs.spot_id AS creative_id,
				NULL AS creative_id_bookend_bottom,
				stvs.client_name,
				stvs.spot_name,
				NULL AS bookend_top_name,
				NULL AS bookend_bottom_name,
				stvs.zone,
				stzd.sysname,
				(0+stzd.utc_offset) AS 'utc_offset',
				(0+stzd.utc_dst_offset) AS 'utc_dst_offset',
				stvs.network,
				stvs.air_time_date,
				stvs.program,
				stvc.link_mp4,
				stvc.link_webm,
				stvc.link_thumb,
				NULL AS link_mp4_bookend_bottom,
				NULL AS link_webm_bookend_bottom,
				NULL AS link_thumb_bookend_bottom
			FROM
				tp_spectrum_tv_schedule AS stvs
				JOIN tp_spectrum_tv_creatives AS stvc
					ON (stvs.spot_id = stvc.id)
				LEFT JOIN tp_spectrum_tv_zone_data AS stzd
					ON (stvs.zone_sys_code = stzd.id)
				JOIN tp_spectrum_tv_accounts AS tpsta
				ON (stvs.account_ul_id = tpsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				stvs.spot_id IS NOT NULL
				AND tpsta.frq_id IN {$accounts_sql}
				AND tpajtp.frq_advertiser_id IN $advertisers_sql
				AND stvs.air_time_date >= ?
			GROUP BY
				network, air_time_date, zone
			UNION ALL
			SELECT
				stvs.account_ul_id,
				stvs.client_traffic_id,
				stvs.bookend_top_id AS creative_id,
				stvs.bookend_bottom_id AS creative_id_bookend_bottom,
				stvs.client_name,
				stvs.spot_name,
				stvs.bookend_top_name,
				stvs.bookend_bottom_name,
				stvs.zone,
				stzd.sysname,
				(0+stzd.utc_offset) AS 'utc_offset',
				(0+stzd.utc_dst_offset) AS 'utc_dst_offset',
				stvs.network,
				stvs.air_time_date,
				stvs.program,
				stvc.link_mp4,
				stvc.link_webm,
				stvc.link_thumb,
				stvc2.link_mp4 AS link_mp4_bookend_bottom,
				stvc2.link_webm AS link_webm_bookend_bottom,
				stvc2.link_thumb AS link_thumb_bookend_bottom
			FROM
				tp_spectrum_tv_schedule AS stvs
				LEFT JOIN tp_spectrum_tv_zone_data AS stzd
					ON (stvs.zone_sys_code = stzd.id)
				JOIN tp_spectrum_tv_creatives AS stvc
				ON (stvs.bookend_top_id = stvc.id)
				JOIN tp_spectrum_tv_creatives AS stvc2
				ON (stvs.bookend_bottom_id = stvc2.id)
				JOIN tp_spectrum_tv_accounts AS tpsta
				ON (stvs.account_ul_id = tpsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tpajtp
				ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
			WHERE
				stvs.spot_id IS NULL
				AND tpsta.frq_id IN {$accounts_sql}
				AND tpajtp.frq_advertiser_id IN $advertisers_sql
				AND stvs.air_time_date >= ?
			GROUP BY
				network, air_time_date, zone
			ORDER BY
				network, air_time_date, zone
			) AS tv_airings
		GROUP BY
			network, air_time_date, zone
		ORDER BY
			air_time_date ASC
		LIMIT 4";

		$result = $this->db->query($sql, $get_airings_data_bindings);

		if($result == null || $result->num_rows() < 1)
		{
			return $return_data;
		}
		$airing_rows = $result->result_array();



		foreach($airing_rows as $airing)
		{

			$current_network = $airing['network'];
			$network_icon_url = "https://s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/".$current_network.".png";
			$file_headers = @get_headers($network_icon_url);
			if(strpos($file_headers[0], '200 OK'))
			{
				$network_icon = $network_icon_url;
			}
			else
			{
				$network_icon = "/assets/img/network_logo_goes_here.png";
			}


			$airing_array = array(
				"zone"=>$airing['zone'],
				"zone_friendly_name"=>$airing['sysname'] != null ? $airing['sysname'] : $airing['zone'],
				"airing_time" => $this->get_zone_airing_half_hour($airing['air_time_date'], $airing['utc_offset'], $airing['utc_dst_offset'], $dst_flag),
				"network_icon" => $network_icon,
				"show"=>$airing['program'],
				"creative" => array(
					"creative_id" => $airing['creative_id'],
					"creative_name" => $airing['creative_id_bookend_bottom'] == null ? $airing['spot_name'] : $airing['bookend_top_name'].' & '.$airing['bookend_bottom_name'],
					"creative_video" => $airing['link_mp4'],
					"creative_video_webm" => $airing['link_webm'],
					"creative_video_bookend_2" => $airing['link_mp4_bookend_bottom'],
					"creative_video_bookend_2_webm" => $airing['link_webm_bookend_bottom'],
					"creative_thumbnail" => ($airing['link_thumb'] ? $airing['link_thumb'] : '/images/reports/no_video_available.png')
				)
			);
			$return_data[] = $airing_array;
		}
		return $return_data;
	}

	public function get_advertisers_search($search_term=null,$mysql_page_number=0, $page_limit=0, $user, $user_id, $role)
	{
		$businesses = array();
		$user->role = strtolower($user->role);
		if ($search_term == null)
		{
			$search_term="";
		}
		$search_term="%".$search_term."%";
		$advertisers = array();
		$binding_array=array();

		if ($role == 'sales' && $user->isGroupSuper == 1)
		{
			$binding_array = array($user_id, $search_term, $user_id, $search_term);
			$sql="
			SELECT
				tot.name,
				tot.id
			FROM
			(
				SELECT
					ag.Name,
					CONCAT('ag-', GROUP_CONCAT(a.id SEPARATOR '-')) AS id
				FROM
					users u
					LEFT JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON (u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2
						ON pd.id = u2.partner_id
					JOIN Advertisers a
						ON u2.id = a.sales_person
					JOIN advertiser_groups_to_advertisers aga
						ON a.id = aga.advertiser_id
					JOIN advertiser_groups ag
						ON ag.id = aga.advertiser_group_id
				WHERE
					u.id = ? AND
					ag.name LIKE ?
				GROUP BY ag.id
				UNION
				SELECT DISTINCT
					a.Name, a.id
				FROM
					users u
					LEFT JOIN wl_partner_owners po
						ON u.id = po.user_id
					JOIN wl_partner_hierarchy h
						ON (u.partner_id = h.ancestor_id OR po.partner_id = h.ancestor_id)
					JOIN wl_partner_details pd
						ON h.descendant_id = pd.id
					JOIN users u2
						ON pd.id = u2.partner_id
					JOIN Advertisers a
						ON u2.id = a.sales_person AND
						a.id NOT IN
						(
							SELECT advertiser_id FROM advertiser_groups_to_advertisers aga
						)
				WHERE
					u.id = ? AND
					a.name LIKE ?
			) AS tot
			ORDER BY tot.Name ASC";
		}
		else if ($role == 'sales' && $user->isGroupSuper == 0)
		{
			$binding_array = array($user_id, $user_id, $search_term, $user_id, $user_id, $search_term);
			$sql="
				SELECT
					tot.name,
					tot.id
				FROM
				(
					SELECT
						ag.Name,
						CONCAT('ag-', GROUP_CONCAT(a.id SEPARATOR '-')) AS id
					FROM
						users u
						JOIN wl_partner_owners po
							ON u.id = po.user_id
						JOIN wl_partner_hierarchy h
							ON po.partner_id = h.ancestor_id
						JOIN wl_partner_details pd
							ON h.descendant_id = pd.id
						JOIN users u2
							ON pd.id = u2.partner_id
						RIGHT JOIN Advertisers a
							ON u2.id = a.sales_person
						JOIN advertiser_groups_to_advertisers aga
							ON a.id = aga.advertiser_id
						JOIN advertiser_groups ag
							ON ag.id = aga.advertiser_group_id
					WHERE
						(u.id = ? OR a.sales_person = ?) AND
						ag.name LIKE ?
					GROUP BY ag.id
					UNION
					SELECT DISTINCT
						a.Name, a.id
					FROM
						users u
						JOIN wl_partner_owners po
							ON u.id = po.user_id
						JOIN wl_partner_hierarchy h
							ON po.partner_id = h.ancestor_id
						JOIN wl_partner_details pd
							ON h.descendant_id = pd.id
						JOIN users u2
							ON pd.id = u2.partner_id
						RIGHT JOIN Advertisers a
							ON u2.id = a.sales_person AND
							a.id NOT IN
							(
								SELECT advertiser_id FROM advertiser_groups_to_advertisers aga
							)
					WHERE
						(u.id = ? OR a.sales_person = ?) AND
						a.name LIKE ?
				) AS tot
				ORDER BY tot.Name ASC";
		}
		else if ($role == 'business')
		{
			$binding_array=array($user->advertiser_id, $user->advertiser_id);
			$sql="
				SELECT tot.name, tot.id FROM
					(
					SELECT
						ag.Name,
						CONCAT('ag-', GROUP_CONCAT(a.id SEPARATOR '-')) AS id
					FROM
						Advertisers a
						JOIN advertiser_groups_to_advertisers aga
							ON a.id = aga.advertiser_id
						JOIN advertiser_groups ag
							ON ag.id = aga.advertiser_group_id
					WHERE
						a.id = ?
					GROUP BY ag.id
					UNION
					SELECT DISTINCT
						a.Name, a.id
					FROM
						Advertisers a
					WHERE
							a.id NOT IN
							(
								SELECT advertiser_id FROM advertiser_groups_to_advertisers aga
							)
					AND
						a.id = ?
					) AS tot
					ORDER BY tot.Name ASC
			";
		}
		else if ($role == 'admin' || $role == 'creative' || $role == 'ops')
		{
			$binding_array=array($search_term, $search_term);
			$sql = "
				SELECT
					tot.name,
					tot.id
				FROM
				(
					SELECT
						ag.Name,
						CONCAT('ag-', GROUP_CONCAT(a.id SEPARATOR '-')) AS id
					FROM
						Advertisers a
					JOIN advertiser_groups_to_advertisers aga
						ON a.id = aga.advertiser_id
					JOIN advertiser_groups ag
						ON ag.id = aga.advertiser_group_id
					WHERE
						ag.name LIKE ?
					GROUP BY ag.id
					UNION
					SELECT DISTINCT
						a.Name, a.id
					FROM
						Advertisers a
					WHERE
						a.id NOT IN
						(
							SELECT
								advertiser_id
							FROM
								advertiser_groups_to_advertisers aga
						)
					AND
						a.name LIKE ?
				) AS tot
				ORDER BY
					tot.Name ASC
			";
		}
		else if ($role == 'agency' || $role == 'client')
		{
			$binding_array = array($user_id, $search_term, $user_id, $search_term);
			$sql = "
				SELECT
					tot.name,
					tot.id
				FROM
					(
					SELECT
						ag.Name,
						CONCAT('ag-', GROUP_CONCAT(a.id SEPARATOR '-')) AS id
					FROM
						Advertisers a
						JOIN advertiser_groups_to_advertisers aga
							ON a.id = aga.advertiser_id
						JOIN advertiser_groups ag
							ON ag.id = aga.advertiser_group_id
						JOIN clients_to_advertisers AS cta
							ON cta.advertiser_id = a.id
					WHERE
						cta.user_id = ?	AND
						ag.name LIKE ?
					GROUP BY ag.id
					UNION
					SELECT DISTINCT
						a.Name,
						a.id
					FROM
						Advertisers a
						JOIN clients_to_advertisers AS cta
							ON cta.advertiser_id = a.id
						WHERE
						cta.user_id = ? AND
						a.id NOT IN
						(
							SELECT
								advertiser_id
							FROM
								advertiser_groups_to_advertisers aga
						)
					AND
						a.name LIKE ?
				) AS tot
				ORDER BY
					tot.Name ASC
			";
		}

		if ($mysql_page_number > 0 || $page_limit > 0)
		{
			$binding_array[]=$mysql_page_number;
			$binding_array[]=$page_limit;
			$sql .= " LIMIT ?,? ";
		}

		$query = $this->db->query($sql, $binding_array);
		if($query->num_rows() > 0)
		{
			$advertisers = $query->result_array();
		}

		if(is_null($advertisers))
		{
			$advertisers = array();
		}
		return $advertisers;
	}

	public function get_verified_spots_by_date($advertiser_id, $account_set, $raw_start_date, $raw_end_date)
	{
		$get_verified_spots_bindings = array();
		$return_data = array();

		$advertiser_id = array_map('intval', $advertiser_id);
		$account_ids = $account_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);

		if(empty($account_ids))
		{
			return $return_data;
		}

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';

		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';

		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$get_verified_spots_bindings = array_merge(
			$account_ids,
			$advertiser_id,
			[$start_date, $end_date],
			$account_ids,
			$advertiser_id,
			[$start_date, $end_date],
			$account_ids,
			$advertiser_id,
			[$start_date, $end_date],
			$account_ids,
			$advertiser_id,
			[$start_date, $end_date]
		);

		$get_verified_spots_by_date_sql =
		"	SELECT
				tv_imps.date AS date,
				COALESCE(tv_imps.spots, tv_verif_old.spots) AS spots,
				COALESCE(tv_imps.total_impressions, 0) AS total_impressions
			FROM (
				SELECT
					tstibc.date AS date,
					SUM(tstibc.spot_count) AS spots,
					SUM(tstibc.estimated_impressions) AS total_impressions
				FROM
					tp_spectrum_tv_impressions_by_creative AS tstibc
				JOIN tp_spectrum_tv_accounts AS tsta
					ON (tstibc.account_ul_id = tsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tajtpa
					ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
				WHERE
					tsta.frq_id IN {$accounts_sql} AND
					tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
					tstibc.date BETWEEN ? AND ?
				GROUP BY
					tstibc.date
			) AS tv_imps
			LEFT JOIN (
				SELECT
					tstvd.verified_date AS date,
					SUM(tstvd.spot_count) AS spots
				FROM
					tp_spectrum_tv_verified_data AS tstvd
				JOIN tp_spectrum_tv_accounts AS tsta
					ON (tstvd.account_ul_id = tsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tajtpa
					ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
				WHERE
					tsta.frq_id IN {$accounts_sql} AND
					tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
					tstvd.verified_date BETWEEN ? AND ?
				GROUP BY
					tstvd.verified_date
			) AS tv_verif_old
			ON tv_imps.date = tv_verif_old.date
			UNION
			SELECT
				tv_verif_old.date AS date,
				COALESCE(tv_imps.spots, tv_verif_old.spots) AS spots,
				COALESCE(tv_imps.total_impressions, 0) AS total_impressions
			FROM (
				SELECT
					tstvd.verified_date AS date,
					SUM(tstvd.spot_count) AS spots
				FROM
					tp_spectrum_tv_verified_data AS tstvd
				JOIN tp_spectrum_tv_accounts AS tsta
					ON (tstvd.account_ul_id = tsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tajtpa
					ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
				WHERE
					tsta.frq_id IN {$accounts_sql} AND
					tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
					tstvd.verified_date BETWEEN ? AND ?
				GROUP BY
					tstvd.verified_date
			) AS tv_verif_old
			LEFT JOIN (
				 SELECT
					tstibc.date AS date,
					SUM(tstibc.spot_count) AS spots,
					SUM(tstibc.estimated_impressions) AS total_impressions
				FROM
					tp_spectrum_tv_impressions_by_creative AS tstibc
				JOIN tp_spectrum_tv_accounts AS tsta
					ON (tstibc.account_ul_id = tsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tajtpa
					ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
				WHERE
					tsta.frq_id IN {$accounts_sql} AND
					tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
					tstibc.date BETWEEN ? AND ?
				GROUP BY
					tstibc.date
			) AS tv_imps
			ON tv_imps.date = tv_verif_old.date
			ORDER BY
				date ASC
		";
		$result = $this->db->query($get_verified_spots_by_date_sql, $get_verified_spots_bindings);
		if($result == null || $result->num_rows() < 1)
		{
			return $return_data;
		}
		return $result->result_array();
	}

	public function get_overview_top_networks($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date, $subfeature_access_permissions, $limit = 7)
	{
		$return_data = array();
		if ($campaign_set == "")
			return $return_data;
		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);
		if(empty($account_ids))
		{
			return $return_data;
		}

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$get_networks_data_bindings = array();

		$get_networks_data_bindings = array_merge(
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			[$limit]
		);

		$units = 'Airings';
		$tv_impressions_order_by = 'total_airings';
		if($subfeature_access_permissions['are_tv_impressions_accessible'])
		{
			$units = 'Impressions';
			$tv_impressions_order_by = 'total_impressions';
		}

		$get_top_networks_sql =
		"	SELECT
				a.network,
				a.network_name,
				SUM(a.impressions) AS total_impressions,
				SUM(a.airings) AS total_airings
			FROM (
				SELECT
					tptinp.network AS network,
					tstnd.network_name AS network_name,
					SUM(tptinp.estimated_impressions) AS impressions,
					SUM(tptinp.spot_count) AS airings
				FROM
					tp_spectrum_tv_impressions_by_network_program AS tptinp
				JOIN tp_spectrum_tv_network_data AS tstnd
					ON (tptinp.network = tstnd.abbreviation)
				JOIN tp_spectrum_tv_accounts AS tsta
					ON (tptinp.account_ul_id = tsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tajtpa
					ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
				WHERE
					tsta.frq_id IN {$accounts_sql} AND
					tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
					(tptinp.date BETWEEN ? AND ?)
				GROUP BY
					network

				UNION ALL

				SELECT
					tstvd.network AS network,
					tstnd.network_name AS network_name,
					SUM(0) AS impressions,
					SUM(tstvd.spot_count) AS airings
				FROM
					tp_spectrum_tv_verified_data AS tstvd
				JOIN tp_spectrum_tv_network_data AS tstnd
					ON (tstvd.network = tstnd.abbreviation)
				JOIN tp_spectrum_tv_accounts AS tsta
					ON (tstvd.account_ul_id = tsta.account_id)
				JOIN tp_advertisers_join_third_party_account AS tajtpa
					ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
				WHERE
					tsta.frq_id IN {$accounts_sql} AND
					tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
					(tstvd.verified_date BETWEEN ? AND ?) AND
					tstvd.verified_date NOT IN (
						SELECT DISTINCT
							tptinp.date
						FROM
							tp_spectrum_tv_impressions_by_network_program AS tptinp
						JOIN tp_spectrum_tv_network_data AS tstnd
							ON (tptinp.network = tstnd.abbreviation)
						JOIN tp_spectrum_tv_accounts AS tsta
							ON (tptinp.account_ul_id = tsta.account_id)
						JOIN tp_advertisers_join_third_party_account AS tajtpa
							ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
						WHERE
							tsta.frq_id IN {$accounts_sql} AND
							tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
							(tptinp.date BETWEEN ? AND ?)
					)
				GROUP BY
					network
			) AS a
			GROUP BY
				network
			ORDER BY
				{$tv_impressions_order_by} DESC
			LIMIT ?
		";

		$result = $this->db->query($get_top_networks_sql, $get_networks_data_bindings);
		if($result == null || $result->num_rows() < 1)
		{
			return $return_data;
		}
		$network_rows = $result->result_array();
		$return_data['network_icons'] = array();
		$return_data['graph_data'] = array();
		foreach($network_rows as $network_row)
		{
			$network_icon_url = "https://s3-us-west-1.amazonaws.com/reports-tv-data/network_icons/{$network_row['network']}.png";
			$file_headers = @get_headers($network_icon_url);
			if(!strpos($file_headers[0], '200 OK'))
			{
				$network_icon_url = "/assets/img/network_logo_goes_here.png";
			}
			$return_data['network_icons'][$network_row['network']] = $network_icon_url;
			$return_data['graph_data'][] = array(
				"name" => $network_row['network'],
				"friendly_name" => $network_row['network_name'],
				"data" => intval($subfeature_access_permissions['are_tv_impressions_accessible'] ? $network_row['total_impressions'] : $network_row['total_airings'])
			);
			$return_data['units'] = $units;
		}
		return $return_data;
	}

	public function get_user_support_email_by_user_id($user_id)
	{
		if(!$user_id)
		{
			return null;
		}
		$sql =
		"	SELECT
				support_email_address
			FROM
				wl_partner_details
			WHERE
				id = (
					SELECT
						COALESCE(s.partner_id, a.partner_id) AS id
					FROM (
						SELECT
							partner_id
						FROM
							users
						WHERE id = ?
					) AS s,
					(
						SELECT
							u2.partner_id
						FROM
							users AS u1
						LEFT JOIN
							Advertisers AS a
							ON
								a.id = u1.advertiser_id
						LEFT JOIN
							users AS u2
							ON
								u2.id = a.sales_person
						WHERE
							u1.id = ?
					) AS a
				)
		";
		$bindings = array($user_id, $user_id);
		$result = $this->db->query($sql, $bindings);
		if($result->num_rows() == 1)
		{
			return $result->row()->support_email_address;
		}
		return null;
	}

	public function get_overview_tv_totals($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date)
	{
		$return_data = array();
		if ($campaign_set == "")
			return $return_data;
		$account_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::spectrum,
			report_campaign_type::spectrum_tv
		);
		if(empty($account_ids))
		{
			return $return_data;
		}

		$accounts_sql = '(-1,'.$this->make_bindings_sql_from_array($account_ids).')';
		$advertisers_sql = '(-1,'.$this->make_bindings_sql_from_array($advertiser_id).')';
		$start_date = date("Y-m-d", strtotime($raw_start_date));
		$end_date = date("Y-m-d", strtotime($raw_end_date));

		$get_verified_totals_bindings = array();

		$get_verified_totals_bindings = array_merge(
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date],
			array_map('intval', $account_ids),
			array_map('intval', $advertiser_id),
			[$start_date, $end_date]
		);
		$get_verified_totals_sql =
		"	SELECT
				SUM(a.impressions) AS impressions,
				SUM(a.networks) AS networks,
				SUM(a.airings) AS airings,
				(	SELECT
						COUNT(DISTINCT tstibs.syscode)
					FROM tp_spectrum_tv_impressions_by_syscode AS tstibs
					JOIN tp_spectrum_tv_accounts AS tpsta
						ON (tstibs.account_ul_id = tpsta.account_id)
					JOIN tp_advertisers_join_third_party_account AS tpajtp
						ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
					WHERE
						tpsta.frq_id IN {$accounts_sql} AND
						tpajtp.frq_advertiser_id IN {$advertisers_sql} AND
						tstibs.date BETWEEN ? AND ?
				) AS count_zones,
				(
					SELECT
						GROUP_CONCAT(grc.json_regions SEPARATOR '==') AS zctas
					FROM
						geo_regions_collection AS grc
					JOIN
						tp_spectrum_syscode_region_join AS tssrj
						ON
							tssrj.geo_regions_collection_id = grc.id
					JOIN (
						SELECT DISTINCT
							tstibs.syscode AS syscode
						FROM
							tp_spectrum_tv_impressions_by_syscode AS tstibs
							JOIN tp_spectrum_tv_accounts AS tpsta
								ON (tstibs.account_ul_id = tpsta.account_id)
							JOIN tp_advertisers_join_third_party_account AS tpajtp
								ON (tpsta.frq_id = tpajtp.frq_third_party_account_id AND tpajtp.third_party_source = 3)
						WHERE
							tpsta.frq_id IN {$accounts_sql} AND
							tpajtp.frq_advertiser_id IN {$advertisers_sql} AND
							tstibs.date BETWEEN ? AND ?
					) AS sys
					ON
						sys.syscode = tssrj.syscode_int

				) AS regions_string
			FROM (
				SELECT
					COUNT(DISTINCT net.network) AS networks,
					SUM(net.impressions) AS impressions,
					SUM(net.airings) AS airings
				FROM (
					SELECT
						tptinp.network AS network,
						SUM(tptinp.estimated_impressions) AS impressions,
						SUM(tptinp.spot_count) AS airings
					FROM
						tp_spectrum_tv_impressions_by_network_program AS tptinp
					JOIN tp_spectrum_tv_network_data AS tstnd
						ON (tptinp.network = tstnd.abbreviation)
					JOIN tp_spectrum_tv_accounts AS tsta
						ON (tptinp.account_ul_id = tsta.account_id)
					JOIN tp_advertisers_join_third_party_account AS tajtpa
						ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
					WHERE
						tsta.frq_id IN {$accounts_sql} AND
						tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
						(tptinp.date BETWEEN ? AND ?)
					GROUP BY
						tptinp.network

					UNION ALL

					SELECT
						DISTINCT tstvd.network AS networks,
						0 AS impressions,
						SUM(tstvd.spot_count) AS airings
					FROM
						tp_spectrum_tv_verified_data AS tstvd
					JOIN tp_spectrum_tv_network_data AS tstnd
						ON (tstvd.network = tstnd.abbreviation)
					JOIN tp_spectrum_tv_accounts AS tsta
						ON (tstvd.account_ul_id = tsta.account_id)
					JOIN tp_advertisers_join_third_party_account AS tajtpa
						ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
					WHERE
						tsta.frq_id IN {$accounts_sql} AND
						tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
						(tstvd.verified_date BETWEEN ? AND ?) AND
						tstvd.verified_date NOT IN (
							SELECT DISTINCT
								tptinp.date
							FROM
								tp_spectrum_tv_impressions_by_network_program AS tptinp
							JOIN tp_spectrum_tv_network_data AS tstnd
								ON (tptinp.network = tstnd.abbreviation)
							JOIN tp_spectrum_tv_accounts AS tsta
								ON (tptinp.account_ul_id = tsta.account_id)
							JOIN tp_advertisers_join_third_party_account AS tajtpa
								ON (tsta.frq_id = tajtpa.frq_third_party_account_id AND tajtpa.third_party_source = 3)
							WHERE
								tsta.frq_id IN {$accounts_sql} AND
								tajtpa.frq_advertiser_id IN {$advertisers_sql} AND
								(tptinp.date BETWEEN ? AND ?)
						)
					GROUP BY
						tstvd.network
				) AS net
			) AS a;
		";

		$this->db->query("SET group_concat_max_len = 2097152;");
		$result = $this->db->query($get_verified_totals_sql, $get_verified_totals_bindings);
		if($result == null || $result->num_rows() < 1)
		{
			return $return_data;
		}
		return $result->row_array();
	}

	private function format_vehicles_table_row($headers, $result_row)
	{
		$table_row = new report_table_row();

		assert(count($result_row) === 5);

		$searched_item_column_format = $headers[0][1];
		$searched_item_cell = new report_table_cell();
		$searched_item_cell->html_content = $result_row['searched_item'];
		$searched_item_cell->css_classes = 'table_body_cell '.$searched_item_column_format->css_class;
		$table_row->cells[] = $searched_item_cell;

		$num_searches_column_format = $headers[1][1];
		$num_searches_cell = new report_table_cell();
		$num_searches_cell->html_content = $result_row['num_searches'];
		$num_searches_cell->css_classes = 'table_body_cell '.$num_searches_column_format->css_class;
		$table_row->cells[] = $num_searches_cell;

		$num_ad_details_column_format = $headers[2][1];
		$num_ad_details_cell = new report_table_cell();
		$num_ad_details_cell->html_content = $result_row['num_ad_details'];
		$num_ad_details_cell->css_classes = 'table_body_cell '.$num_ad_details_column_format->css_class;
		$table_row->cells[] = $num_ad_details_cell;

		$item_price = number_format($result_row['item_price']);
		$price_html = '<i class="icon-ok"></i>';
		$tooltip_copy = "Your price is among the highest within the local market and this may impact the level of response you experience. Depending on mileage, condition and trim, you may want to consider lowering the price, enhancing the vehicle's description which allows a potential buyer to understand the VALUE of this unique vehicle, or leave the price out of the description (where allowed by law).";

		if ($result_row['listing_price_range'] === 'L')
		{
			$price_html = '<i class="icon-exclamation"></i>';
			$tooltip_copy = "Your price is among the lowest within the local market. Depending on mileage, condition and trim, you may be able to increase the price.";
		}
		elseif ($result_row['listing_price_range'] === 'A')
		{
			$price_html = '<i class="icon-ok for-avg-price"></i>';
			$tooltip_copy = "Your price looks inline with the local market. Depending on mileage and condition, you may be able to increase the price.";
		}

		$item_price_row = '$'.$item_price.'&nbsp;&nbsp;<span class="report_tooltip" data-content="'.$tooltip_copy.'" data-trigger="hover" data-placement="top" data-delay="200">'.$price_html.'</span>';

		$item_price_column_format = $headers[3][1];
		$item_price_cell = new report_table_cell();
		$item_price_cell->html_content = $item_price_row;
		$item_price_cell->css_classes = 'table_body_cell '.$item_price_column_format->css_class;
		$table_row->cells[] = $item_price_cell;

		return $table_row;
	}

	public function zero_dates($graph_data, $start_date, $end_date)
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
					"total_visits" => 0,
					"leads" => 0,
					"spots" => 0,
					"views" => 0,
					"airings" => 0
				);
			}
			else
			{
				$return_data[$key] = $graph_data[$key];
			}
		}
		return $return_data;
	}

	public function get_lift_data_per_zip_for_campaigns($campaign_ids, $date_report_start, $date_report_end)
	{
		ini_set('memory_limit', '2048M');

		//Get all TTD campaign ids for the passed campaign ids which will be used in further query.
		$sql = "SELECT
				ttd_campaign_id
			FROM
				Campaigns
			WHERE
				id IN ($campaign_ids)";

		$query = $this->db->query($sql);
		$ttd_campaign_ids = "";
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$ttd_campaign_ids = $ttd_campaign_ids."'".$row['ttd_campaign_id']."',";
			}
			$ttd_campaign_ids = rtrim($ttd_campaign_ids,",");
		}

		if ($ttd_campaign_ids != "")
		{
			$query =
				"
				SELECT
					abc.zip,
					abc.average_conversion_rate,
					abc.average_baseline,
					abc.average_lift,
					coalesce(gpm.NAMELSAD10,avg2.zip) AS city,
					avg2.population_total,
					avg2.avg_home_value,
					avg2.percentage_avg_home_value,
					avg2.avg_median_age,
					avg2.percentage_avg_median_age,
					avg2.avg_income,
					avg2.percentage_avg_income,
					avg2.avg_occupancy,
					avg2.percentage_avg_occupancy
				FROM

					(
						SELECT
						zip,
						ROUND(
							SUM(COALESCE(lift_table.conversion_rate, 0) * reach_count)  / SUM(reach_count)
						,6) AS average_conversion_rate,
						ROUND(
							(SUM(COALESCE(lift_table.conversion_rate, 0) * reach_count)  / SUM(reach_count)) /
							(SUM(COALESCE(lift_table.lift,0) * reach_count) / SUM(reach_count))
						,6) AS average_baseline,
						ROUND(
							SUM(COALESCE(lift_table.lift, 0) * reach_count)  / SUM(reach_count)
						,6) AS average_lift
						FROM
						(
						SELECT DISTINCT
							c.zip,
							a.conversion_count_unique,
							a.reach_count,
							b.site_visit_count_unique,
							c.population,
							((a.conversion_count_unique/ a.reach_count) * 100) AS conversion_rate,
							CASE WHEN
								((((b.site_visit_count_unique-a.conversion_count_unique)/a.conversion_count_unique) * 100) < 20)
									|| c.population<=a.reach_count || a.conversion_count_unique is null
							THEN
								(((b.site_visit_count_unique) / (c.population)) * 100)
							ELSE
								(((b.site_visit_count_unique-a.conversion_count_unique) / (c.population-a.reach_count)) * 100)
							END AS baseline,
							(
								(((a.conversion_count_unique/ a.reach_count) * 100)) /
								(
									CASE WHEN
										((((b.site_visit_count_unique-a.conversion_count_unique)/a.conversion_count_unique) * 100) < 20)
											|| c.population<=a.reach_count || a.conversion_count_unique is null
									THEN
										(((b.site_visit_count_unique) / (c.population)) * 100)
									ELSE
										(((b.site_visit_count_unique-a.conversion_count_unique) / (c.population-a.reach_count)) * 100)
									END
								)
							) AS lift
						FROM
							rfc_campaign_population_by_zip_and_date c
						LEFT JOIN
							conversion_lift_reach_zip_30 a
						ON
							c.ttd_campaign_id  = a.ttd_campaign_id
						AND
							c.run_date = a.impression_date
						AND
							a.zip = c.zip
						JOIN
							Campaigns d
						ON
							c.ttd_campaign_id = d.ttd_campaign_id
						AND
							d.id  IN ($campaign_ids)
						AND
							c.ttd_campaign_id IN ($ttd_campaign_ids)
						LEFT JOIN
							lift_site_visits_30_zip b
						ON
							b.site_visit_date  = c.run_date
						AND
							b.zip = c.zip
						AND
							b.aggregation_type = 'ZIP'
						AND
							d.id = b.campaign_id
						JOIN
							campaign_monthly_lift_approval ap
						ON
							ap.lift_date = c.run_date
						AND
							d.id = ap.campaign_id
						AND
							ap.approval_flag = '1'
						WHERE
							c.run_date >= ?
						AND
							c.run_date <= ?
						AND
							c.population > 0
						AND
							c.time_frame = '30'
						AND
							c.aggregation_type = 'ZIP'
						) AS lift_table
					GROUP BY
						zip
					) AS abc
					JOIN
					(
						 SELECT DISTINCT
							b.local_id AS zip,
							b.population_total,
							b.households_median_value AS avg_home_value,
							round((b.households_median_value - avg1.households_median_value)*100/avg1.households_median_value)  AS percentage_avg_home_value,
							b.population_median_age AS avg_median_age,
							round((b.population_median_age - avg1.population_median_age)*100/avg1.population_median_age)  AS percentage_avg_median_age,
							b.households_average_income AS avg_income,
							round((b.households_average_income - avg1.households_average_income)*100/avg1.households_average_income) AS percentage_avg_income,
							b.households_average_occupancy AS avg_occupancy,
							round((b.households_average_occupancy - avg1.households_average_occupancy)*100/avg1.households_average_occupancy) AS percentage_avg_occupancy
						FROM
							geo_cumulative_demographics AS b,
							rfc_campaign_population_by_zip_and_date c,
						(
							SELECT
								avg(households_median_value)  AS households_median_value,
								avg(population_median_age) AS population_median_age,
								avg(households_average_income) AS households_average_income,
								avg(households_average_occupancy) AS households_average_occupancy
							FROM
								geo_cumulative_demographics,
								rfc_campaign_population_by_zip_and_date c
							WHERE
								local_id = c.zip
							AND
								c.run_date >= ?
							AND
								c.run_date <= ?
							AND
								c.ttd_campaign_id  IN ($ttd_campaign_ids)
						) AS avg1
						WHERE
							local_id = c.zip
						AND
							c.run_date >= ?
						AND
							c.run_date <= ?
						AND
							c.ttd_campaign_id  IN ($ttd_campaign_ids)
					) AS avg2
				ON
						abc.zip = avg2.zip
					LEFT JOIN
						geo_zcta_to_place AS gzp
					ON
						avg2.zip = gzp.ZCTA5
					LEFT JOIN
						geo_place_map AS gpm
					ON
						gzp.geoid_int = gpm.num_id
					GROUP BY
						abc.zip
					ORDER BY
						abc.average_lift

				";

			$result = $this->db->query($query, array($date_report_start, $date_report_end, $date_report_start, $date_report_end, $date_report_start, $date_report_end));

			if ($result->num_rows() > 0)
			{
				return $result->result_array();
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	public function get_lift_data_for_report_overview($campaign_ids, $date_report_start, $date_report_end, $per_day = false)
	{
		ini_set('memory_limit', '1024M');
		$per_day_selector = "";
		$per_day_outer_selector = "";
		$per_day_where_clause = "";

		if ($per_day)
		{
			$per_day_outer_selector = "impression_date,";
			$per_day_selector = "MONTHNAME(impression_date) AS impression_date,";
			$per_day_where_clause = "
						WHERE
							impression_date IS NOT NULL
						GROUP BY
							impression_date
						ORDER BY
							impression_date";
		}

		$sub_query =	"
				SELECT DISTINCT
					$per_day_selector
					a.conversion_count_unique,
					a.reach_count,
					b.site_visit_count_unique,
					c.population,
					((a.conversion_count_unique/ a.reach_count) * 100) AS conversion_rate,
					CASE WHEN
						((((b.site_visit_count_unique-a.conversion_count_unique)/a.conversion_count_unique) * 100) < 20) || c.population<=a.reach_count || a.conversion_count_unique is null
					THEN
						(((b.site_visit_count_unique) / (c.population)) * 100)
					ELSE
						(((b.site_visit_count_unique-a.conversion_count_unique) / (c.population-a.reach_count)) * 100)
					END AS baseline,
					(
						(((a.conversion_count_unique/ a.reach_count) * 100)) /
						(
							CASE WHEN
								((((b.site_visit_count_unique-a.conversion_count_unique)/a.conversion_count_unique) * 100) < 20)
									|| c.population<=a.reach_count || a.conversion_count_unique is null
							THEN
								(((b.site_visit_count_unique) / (c.population)) * 100)
							ELSE
								(((b.site_visit_count_unique-a.conversion_count_unique) / (c.population-a.reach_count)) * 100)
							END
						)
					) AS lift
				FROM
					rfc_campaign_population_by_date c
				LEFT JOIN
					conversion_lift_reach_no_zip_30 a
				ON
					c.ttd_campaign_id  = a.ttd_campaign_id
				AND
					c.run_date = a.impression_date
				JOIN
					Campaigns d
				ON
					c.ttd_campaign_id = d.ttd_campaign_id
				AND
					d.id  IN ($campaign_ids)
				LEFT JOIN
					lift_site_visits_30 b
				ON
					b.site_visit_date  = c.run_date
				AND
					b.aggregation_type = 'ZIP'
				AND
					d.id = b.campaign_id
				JOIN
					campaign_monthly_lift_approval ap
				ON
					ap.lift_date = c.run_date
				AND
					d.id = ap.campaign_id
				AND
					ap.approval_flag = '1'
				WHERE
					c.run_date >= ?
				AND
					c.run_date <= ?
				AND
					c.population > 0
				AND
					c.time_frame = '30'
				AND
					c.aggregation_type = 'ZIP'
			";


		$query = "
			SELECT
				$per_day_outer_selector
				ROUND(SUM(COALESCE(lift_table.conversion_rate, 0) * reach_count)  / SUM(reach_count),2) AS average_conversion_rate,
				ROUND(
					(SUM(COALESCE(lift_table.conversion_rate, 0) * reach_count)  / SUM(reach_count)) /
					(SUM(COALESCE(lift_table.lift,0) * reach_count) / SUM(reach_count))
				,2) AS average_baseline,
				ROUND(
					SUM(COALESCE(lift_table.lift, 0) * reach_count)  / SUM(reach_count)
				,2) AS average_lift
			FROM
				(
					$sub_query
				) AS lift_table
			$per_day_where_clause
			";


		$result = $this->db->query($query, array($date_report_start, $date_report_end));

		if ($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_lift_data_for_campaign_overview($campaign_ids, $date_report_start, $date_report_end, $per_day = false)
	{
		ini_set('memory_limit', '1024M');
		$per_day_selector = "";
		$per_day_outer_selector = "";
		$per_day_group_by_order_by = "";
		$per_day_where_clause = "";

		if ($per_day)
		{
			$per_day_group_by_order_by = ",impression_date";
			$per_day_selector = "MONTHNAME(impression_date) AS impression_date,";
			$per_day_outer_selector = "impression_date,";
			$per_day_where_clause = " AND impression_date IS NOT NULL";
		}

		$sub_query =	"
				SELECT DISTINCT
					d.id AS campaign_id,
					$per_day_selector
					a.conversion_count_unique,
					a.reach_count,
					b.site_visit_count_unique,
					c.population,
					((a.conversion_count_unique/ a.reach_count) * 100) AS conversion_rate,
					CASE WHEN
						((((b.site_visit_count_unique-a.conversion_count_unique)/a.conversion_count_unique) * 100) < 20) || c.population<=a.reach_count || a.conversion_count_unique is null
					THEN
						(((b.site_visit_count_unique) / (c.population)) * 100)
					ELSE
						(((b.site_visit_count_unique-a.conversion_count_unique) / (c.population-a.reach_count)) * 100)
					END AS baseline,
					(
						(((a.conversion_count_unique/ a.reach_count) * 100)) /
						(
							CASE WHEN
								((((b.site_visit_count_unique-a.conversion_count_unique)/a.conversion_count_unique) * 100) < 20)
									|| c.population<=a.reach_count || a.conversion_count_unique is null
							THEN
								(((b.site_visit_count_unique) / (c.population)) * 100)
							ELSE
								(((b.site_visit_count_unique-a.conversion_count_unique) / (c.population-a.reach_count)) * 100)
							END
						)
					) AS lift
				FROM
					rfc_campaign_population_by_date c
				LEFT JOIN
					conversion_lift_reach_no_zip_30 a
				ON
					c.ttd_campaign_id  = a.ttd_campaign_id
				AND
					c.run_date = a.impression_date
				JOIN
					Campaigns d
				ON
					c.ttd_campaign_id = d.ttd_campaign_id
				AND
					d.id  IN ($campaign_ids)
				LEFT JOIN
					lift_site_visits_30 b
				ON
					b.site_visit_date  = c.run_date
				AND
					b.aggregation_type = 'ZIP'
				AND
					d.id = b.campaign_id
				JOIN
					campaign_monthly_lift_approval ap
				ON
					ap.lift_date = c.run_date
				AND
					d.id = ap.campaign_id
				AND
					ap.approval_flag = '1'
				WHERE
					c.run_date >= ?
				AND
					c.run_date <= ?
				AND
					c.population > 0
				AND
					c.time_frame = '30'
				AND
					c.aggregation_type = 'ZIP'
			";


		$query = "
			SELECT
				campaign_id,
				$per_day_outer_selector
				ROUND(SUM(COALESCE(lift_table.conversion_rate, 0) * reach_count)  / SUM(reach_count),2) AS average_conversion_rate,
				ROUND(
					(SUM(COALESCE(lift_table.conversion_rate, 0) * reach_count)  / SUM(reach_count)) /
					(SUM(COALESCE(lift_table.lift,0) * reach_count) / SUM(reach_count))
				,2) AS average_baseline,
				ROUND(
					SUM(COALESCE(lift_table.lift, 0) * reach_count)  / SUM(reach_count)
				,2) AS average_lift
			FROM
				(
					$sub_query
				) AS lift_table
			WHERE
				campaign_id IS NOT NULL
				$per_day_where_clause
			GROUP BY
				campaign_id
				$per_day_group_by_order_by
			ORDER BY
				campaign_id
				$per_day_group_by_order_by
			";


		$result = $this->db->query($query, array($date_report_start, $date_report_end));

		if ($result->num_rows() > 0)
		{
			return $result->result_array();
		}
		else
		{
			return false;
		}
	}

	public function get_campaigns_from_version_ids($version_ids)
	{
		$sql =
			"
				SELECT DISTINCT
					COALESCE(source_id, id) AS version_id,
					campaign_id
				FROM
					cup_versions
				WHERE
					(
							id IN ($version_ids)
						OR
							source_id IN ($version_ids)
					)
				AND
					campaign_id IS NOT NULL
			";
		$result = $this->db->query($sql);

		if ($result->num_rows() > 0)
		{
			$campaigns_from_version_ids_result = $result->result_array();
			$final_result = array();

			foreach ($campaigns_from_version_ids_result as $result_entry)
			{
				$final_result[$result_entry['version_id']][] = $result_entry['campaign_id'];
			}

			return $final_result;
		}

		return null;
	}

	public function get_campaign_names_from_ids($campain_ids)
	{
		$sql =
			"SELECT
				id AS campaign_id,
				Name
			FROM
				Campaigns
			WHERE
				id IN ($campain_ids)";

		$result = $this->db->query($sql);

		if ($result->num_rows() > 0)
		{
			$campaign_names_result = $result->result_array();
			$final_result = array();

			foreach ($campaign_names_result as $result_entry)
			{
				$final_result[$result_entry['campaign_id']] = $result_entry['Name'];
			}

			return $final_result;
		}

		return null;
	}

	public function get_lift_creative_messages($version_ids)
	{
		$backup_images_array = $this->get_creative_backup_images_for_versions($version_ids);
		$backup_images = array();

		foreach ($backup_images_array as $backup_image)
		{
			$backup_images[$backup_image['version_id']]['src'] = $backup_image['backup_image'];
			$size = explode('x', $backup_image['creative_size']);
			$backup_images[$backup_image['version_id']]['width'] = $size[0];
			$backup_images[$backup_image['version_id']]['height'] = $size[1];
		}

		$result = array();

		foreach ($version_ids as $version_id)
		{
			$version_creative_data_set = array(
				'creative_data' => array(
					'cup_version_id' => $version_id,
					'ad_set_preview_url' => site_url().'crtv/get_gallery_adset/'.base64_encode(base64_encode(base64_encode($version_id))),
					'creative_thumbnail' => $backup_images[$version_id]['src'],
					'creative_width' => (int)$backup_images[$version_id]['width'],
					'creative_height' => (int)$backup_images[$version_id]['height']
				)
			);

			$result[$version_id] = $version_creative_data_set;
		}

		return $result;
	}

	public function get_lift_creative_ids($advertiser_id, $campaign_set, $raw_start_date, $raw_end_date)
	{
		$campaign_ids = $campaign_set->get_campaign_ids(
			report_campaign_organization::frequence,
			report_campaign_type::frequence_display
		);

		$campaigns_sql = '(-1,'.implode(",",$campaign_ids).')';
		$advertisers_sql = '(-1,'.implode(",",$advertiser_id).')';
		$start_date_search = date("Y-m-d", strtotime($raw_start_date));
		$end_date_search = date("Y-m-d", strtotime($raw_end_date));
		$date_bindings = array($start_date_search, $end_date_search);

		$query =
			"
				SELECT
					COALESCE(cv.source_id,cv.id) AS version_id
				FROM
					cup_versions AS cv,
					(
						SELECT
							cv.id AS version_id
						FROM
							report_creative_records AS rcr
							JOIN AdGroups AS adg
								ON (rcr.adgroup_id = adg.ID)
							JOIN Campaigns AS cmp
								ON (adg.campaign_id = cmp.id)
							JOIN cup_creatives AS cre
								ON (rcr.creative_id = cre.id)
							JOIN cup_versions AS cv
								ON (cre.version_id = cv.id)
						WHERE
							cmp.business_id IN $advertisers_sql
							AND adg.campaign_id IN $campaigns_sql
							AND cv.campaign_id IN $campaigns_sql
							AND rcr.date BETWEEN ? AND ?
						GROUP BY
							cv.id
					) AS inner_query
				WHERE
					cv.id = inner_query.version_id
			";

		$result = $this->db->query($query, $date_bindings);

		if ($result->num_rows() > 0)
		{
			$version_ids = array();

			foreach ($result->result_array() as $version_id_entry)
			{
				$version_ids[] = $version_id_entry['version_id'];
			}

			return $version_ids;
		}
		else
		{
			return false;
		}
	}
}
?>
