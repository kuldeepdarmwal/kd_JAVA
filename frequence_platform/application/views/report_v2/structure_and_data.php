<style>
<?php if(!$are_tv_impressions_accessible) { ?>
	.impression_total_value {
		display: none;
	}
<?php } ?>
#report_v2_advertiser_dropdown{
	font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif!important;
}
body
{
	background-color:#f6f6f6;
}
</style>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

<div id="overview_loading_image">
	<img src="/images/report_v2_loader.gif" />
</div>
<div class="navigation_container container-body">
	<ul class="sidebar-nav">
		<!--li>
			<div class="report_v2_table_download_csv_button_new">
				<a href="#" id="table_download_link" title="Download .csv file of table data">
					Table CSV <i class="icon icon-download-alt"></i>
				</a>
			</div>
		</li-->
		<li>
			<div class="report_v2_advertiser_selection">
				<div class="report_v2_advertiser_title controller_option_title">
					Advertiser
				</div>
				<div id="report_v2_advertiser_dropdown" class="target_dropdown">
					<select id="report_v2_advertiser_options" data-max="5" multiple="multiple">
						<?php

							foreach($advertisers as $advertiser_array)
							{

								$advertiser_name = $advertiser_array['name'];
								$advertiser_id = $advertiser_array['id'];
								$advertiser_visible = $advertiser_name;
								/*if($advertiser_id == $advertiser_selected_id)
								{
									echo '<option id="" selected="selected" value="'.$advertiser_id.'" data-html-text="'.$advertiser_id.'">'."\n";
								}
								else
								{*/
									echo '<option id="" value="'.$advertiser_id.'" data-html-text="'.$advertiser_id.'">'."\n";
								//}
								echo $advertiser_visible."\n";
								echo '</option>'."\n";
							}
						?>
					</select>
				</div>
			</div>
		</li>
		<li>
			<div class="report_v2_campaign_selection">
				<div class="report_v2_campaign_title controller_option_title">
					Campaign
				</div>
				<div id="report_v2_campaign_dropdown" class="target_dropdown">
					<select id="report_v2_campaign_options" multiple="multiple">
					</select>
				</div>
			</div>
		</li>
		<li>
			<div class="report_v2_date_selection">
				<div class="report_v2_date_title controller_option_title">
					Start &amp; End Date
				</div>
				<div class="report_v2_date_dropdown">
					<input type="text" class="report_v2_datepicker_input" id="report_v2_date_range_input" />
				</div>
			</div>
		</li>
		<?php echo $starting_tabs; ?>
	</ul>
	<?php if($has_support_email) : ?>
		<div class="support_backdrop"></div>
		<div class="support_container">
			<div class="support_tab_container">
				<div class="support_tab">
					Support
				</div>
			</div>
			<div class="support_form_container hidden">
				<form>
					<div class="row-fluid">
						<div class="span9">
							<label for="support_message">How can we help you?</label>
							<textarea id="support_message" rows="5"></textarea>
						</div>
						<div class="span3 button_container">
							<button class="btn btn-primary" type="button">Submit</button>
						</div>
					</div>
					<div class="row-fluid">
						<div class="span6">
							<div class="message_container"></div>
						</div>
						<div class="span6"></div>
					</div>
				</form>
			</div>
		</div>
	<?php endif; ?>
</div>
<div class="content_container container-body">
	<img src="<?php echo $logo_path; ?>" id="print_logo"/>
	<div class="content_holder">
		<div id="tags_alert_div"></div>
		<div class="products_content_section">
		</div>
		<div id="new_tabs_section">
			<div id="overview_tab_content">
				<div id="overview_tab_inner_content">
					<div class="card_rows">
						<div id="campaigns_overview_title" class="card_row campaigns_overview_title">
							<h3>Campaigns Overview<span>Campaigns Live:&nbsp;&nbsp;<span id="campaigns_live_date"></span></span></h3>
						</div>
						<div class="card_row overview_map">
							<div class="card full ajax-load" data-source="map" data-chart-type="map" style="height:452px;padding:0;">
								<div id="overview_map" style="position:relative;height:500px;"></div>
							</div>
						</div>
						<div id="overview_lift_container" class="card_row lift_container">
							<div id="overview_lift_row">
								<div class="overview_lift_left_card" >
									<div class="overview_lift_left_card_inner">
										<div>
											<span class="overview_lift_digital_lift_text">DIGITAL LIFT</span>
										</div>
										<div class="average_lift_container overview_lift_left_card_inner_avg_lift_text" style="margin-top:25px;">
											<i class="fa fa-arrow-up"></i>
											<span class="avg_lift"></span>
											<span class="avg_lift_appender">x</span>
										</div>
									</div>
								</div>
								<div class="overview_lift_right_card">
									<div class="overview_lift_right_card_inner">
  										<div>
											<span class="overview_lift_site_visit_rate_text">SITE VISIT RATE</span>
										</div>
										<div class="exposed_rate_container overview_lift_right_card_inner_one">
											<i class="fa fa-arrow-up"></i>
											<span class="avg_conversion_rate"></span>%&nbsp;<span class="exposed_and_baseline_text" >EXPOSED</span>
										</div>
										<div class="overview_lift_right_card_inner_three">
											<span class="avg_baseline"></span>%&nbsp;<span class="exposed_and_baseline_text">BASELINE</span>
										</div>
									</div>
								</div>
								<div class="overview_lift_middle_card">
									<div class="overview_lift_middle_card_inner_one" >
										<span class="positive_lift">People who saw your digital ad were <b><span class="avg_lift_text"></span> times more likely</b> to visit your website than the unexposed audience.</span>
										<span class="negative_lift">People who saw your digital ad were <b><span class="avg_lift_text"></span> times as likely</b> to visit your website than the unexposed audience.</span>
									</div>
									<div class="overview_lift_middle_card_inner_two" ><a id="overview_lift_container_view_more" href="#">VIEW MORE <i class="icon-angle-right"></i></a></div>
								</div>
							</div>
						</div>
						<div class="card_row <?php echo report_product_tab_html_id::targeted_tv_product; ?>">
							<h3><?php echo trim($products_brand_text['targeted_tv_product']);?> <span class="overview_page_view_more_link"><a href="#targeted_tv_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span></h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="totals_tv_verified" data-chart-type="list2">

									<div class="chart" id="card1_1"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="networks" data-chart-type="bar_networks">
									<h4>Top 5 Networks</h4>
									<div class="chart" id="card6"></div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_tv_verified" data-chart-type="timeseries-shared-chart" >
								<?php if($are_tv_impressions_accessible) { ?>
									<h4>Verified Television Impressions and Airings</h4>
								<?php } else { ?>
									<h4>Verified Television Airings</h4>
								<?php } ?>
									<div class="chart" id="card1_2"></div>
								</div>
								<!--
								<div class="card full ajax-load" data-source="tv" data-chart-type="tv">
									<h4>Upcoming Airings</h4>
									<h5>Targeted TV</h5>
									<div id="card0" class="chart"></div>
									<div id="summary_loading_image" class="loading_image">
										<img src="/images/report_v2_loader.gif" />
									</div>
								</div>
								-->
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
						<div class="card_row <?php echo report_product_tab_html_id::display_product; ?>">
							<h3>
								<?php echo trim($products_brand_text['display_product']);?>
								<span class="overview_page_view_more_link"><a href="#display_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span>
								<a href="#" id="digital_overview_download_button" data-product="<?php echo report_product_tab_html_id::display_product; ?>" class="overview_product_table_download_link" title="Download .csv file of Chart data"><i class="icon icon-download"></i></a>
							</h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="display_totals" data-chart-type="list2">
									<div class="chart" id="card2_0"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="creatives" data-chart-type="creatives">
									<h4>Top Creatives</h4>
									<div class="chart" id="card2">
									</div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_display" data-chart-type="timeseries-shared-chart">
									<h4>Digital Impressions and Engagements</h4>
									<div class="chart" id="card2_2"></div>
								</div>
								<!--
								<div class="graphs_section card full">
									<h4>Digital Impressions and Engagements</h4>
									<h5>All Products</h5>
									<div id="no_graph_data_overlay">
										No Data
									</div>
									<div class="graph_key">
									</div>
									<div class="graph_visuals">
										<div id="graph_container">
										</div>
									</div>
								</div>
								-->
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
						<div class="card_row <?php echo report_product_tab_html_id::pre_roll_product; ?>">
							<h3>
								<?php echo trim($products_brand_text['pre_roll_product']);?>
								<span class="overview_page_view_more_link"><a href="#pre_roll_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span>
								<a href="#" id="preroll_overview_download_button" data-product="<?php echo report_product_tab_html_id::pre_roll_product; ?>" class="overview_product_table_download_link" title="Download .csv file of Chart data"><i class="icon icon-download"></i></a>
							</h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="pre_roll_totals" data-chart-type="list2">

									<div class="chart" id="card1_1"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="completions" data-chart-type="bar_completions" style="z-index:1;">
									<h4>Completions</h4>
									<div class="completions_date_warning"></div>
									<div class="chart" id="card4"></div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_pre_roll" data-chart-type="timeseries-shared-chart">
									<h4>Pre-Roll Impressions and Engagements</h4>
									<div class="chart" id="card2_3"></div>
								</div>
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
					<?php if ($is_tmpi_accessible) : ?>
						<div class="card_row <?php echo report_product_tab_html_id::targeted_clicks_product; ?>">
							<h3><?php echo trim($products_brand_text['targeted_clicks_product']);?> <span class="overview_page_view_more_link"><a href="#targeted_clicks_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span></h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="visits_totals" data-chart-type="list2">

									<div class="chart" id="card1_1"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="clicks" data-chart-type="column">
									<h4>Clicks</h4>
									<div class="chart" id="card5"></div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_visits_product" data-chart-type="timeseries-shared-chart">
									<h4>Visits Impressions and Engagements</h4>
									<div class="chart" id="card2_4"></div>
								</div>
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
						<div class="card_row <?php echo report_product_tab_html_id::targeted_inventory_product; ?>">
							<h3><?php echo trim($products_brand_text['targeted_inventory_product']);?> <span class="overview_page_view_more_link"><a href="#targeted_inventory_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span></h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="leads_totals" data-chart-type="list2">

									<div class="chart" id="card1_1"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="inventory_price" data-chart-type="pie">
									<h4>Price Breakdown</h4>
									<div class="chart" id="card6"></div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_leads_product" data-chart-type="timeseries-shared-chart">
									<h4>Leads Impressions and Engagements</h4>
									<div class="chart" id="card2_5"></div>
								</div>
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
						<div class="card_row <?php echo report_product_tab_html_id::targeted_directories_product; ?>">
							<h3><?php echo trim($products_brand_text['targeted_directories_product']);?> <span class="overview_page_view_more_link"><a href="#targeted_directories_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span></h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="directories_totals" data-chart-type="list2">
									<div class="chart" id="card1_1"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="directories_actions" data-chart-type="bar">
									<h4>Top Actions</h4>
									<div class="chart" id="card5"></div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_directories_product" data-chart-type="timeseries-shared-chart">
									<h4>Directories Impressions and Engagements</h4>
									<div class="chart" id="card2_6"></div>
								</div>
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
						<div class="card_row <?php echo report_product_tab_html_id::targeted_content_product; ?>">
							<h3><?php echo trim($products_brand_text['targeted_content_product']);?> <span class="overview_page_view_more_link"><a href="#targeted_content_product">VIEW MORE&nbsp;&nbsp;<i class="icon-angle-right"></i></a></span></h3>
							<div class="left_card">
								<div class="card full ajax-load" data-source="content_totals" data-chart-type="list2">
									<div class="chart" id="card8_1"></div>
								</div>
							</div>
							<div class="right_card">
								<div class="card full ajax-load" data-source="content" data-chart-type="bar">
									<h4>Top Videos</h4>
									<div class="chart" id="card8"></div>
								</div>
							</div>
							<div class="middle_card">
								<div class="card full ajax-load" data-source="timeseries_content_product" data-chart-type="timeseries-1-column">
									<h4>Content Video Views</h4>
									<div class="chart" id="card8"></div>
								</div>
							</div>
							<div class="after_divider">&nbsp;</div>
						</div>
					<?php endif; ?>
					</div>

					<!--
					<div id="summary_section_enclosure" class="card one">
						<h4>Digital Performance Overview</h4>
						<h5>All Products</h5>
						<div class="summary_section">
							<table>
								<thead>
									<tr>
										<th></th>
										<th>Count</th>
										<th>Rate</th>
									</tr>
								</thead>
								<tbody>
									<tr class="impressions">
										<td class="row-label">Impressions</td>
										<td><div class="impressions-count report_tooltip" data-content="Ads or listings shown to audiences" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td class="impressions-rate"></td>
									</tr>
									<tr class="engagements">
										<td class="row-label">Total Engagements</td>
										<td><div class="engagements-count report_tooltip" data-content="Visits + Ad Hovers + Video Plays + Other Audience Initiated Interactions With Campaigns" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td><div class="engagements-rate report_tooltip" data-content="Engagements / Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
									</tr>
									<tr class="ad-interactions">
										<td class="row-label">Ad Interactions</td>
										<td><div class="interactions-count report_tooltip" data-content="Single Hovers + Full Screen Video Clicks + Video Plays" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td><div class="interactions-rate report_tooltip" data-content="Ad Interactions / Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
									</tr>
									<tr class="visits">
										<td class="row-label">Visits</td>
										<td><div class="visits-count report_tooltip" data-content="Click-Throughs + View-Throughs" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td><div class="visits-rate report_tooltip" data-content="Visits / Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
									</tr>
									<tr class="view-throughs">
										<td class="row-label">View-Throughs</td>
										<td><div class="view-throughs-count report_tooltip" data-content="Visits to conversion page by impressioned users (who did not click)" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td><div class="view-throughs-rate report_tooltip" data-content="View Throughs / Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
									</tr>
									<tr class="clicks">
										<td class="row-label">Clicks</td>
										<td><div class="clicks-count report_tooltip" data-content="Clicks on Ads or Listings, (aka Click-Throughs)" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td><div class="clicks-rate report_tooltip" data-content="Clicks / Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
									</tr>
									<tr class="retargeting">
										<td class="row-label">Retargeting</td>
										<td><div class="retargeting-count report_tooltip" data-content="Impressions from retargeting program where site visitors are shown follow-on ads / listings" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td><div class="retargeting-rate report_tooltip" data-content="Retargeting Clicks / Retargeting Impressions" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
									</tr>
									<tr class="leads">
										<td class="row-label">Leads</td>
										<td><div class="leads-count report_tooltip" data-content="Phone or email requests" data-trigger="hover" data-placement="top" data-delay="160"><span class="f_totals_blank_cell">--</span></div></td>
										<td class="leads-rate"></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					-->
					<!--
					<div class="card one ajax-load" data-source="placements" data-chart-type="bar">
						<h4>Top Placements</h4>
						<h5>
							<?php
							if ($is_tmpi_accessible) {
								echo 'Display';
							}
							else {
								echo 'Display';
							}
							?>
						</h5>
						<div class="chart" id="card3"></div>
					</div>
					-->
				</div>
				<div id="no_data_overview_overlay">
					<h3>There is no data available for the dates you've selected. Please select a different set of dates.</h3>
				</div>
				<div id="lift_no_data_overview_overlay">
					<h3></h3>
				</div>
			</div>
			<div id="lift_analysis_overview" class="lift_container">
				<div id="lift_analysis_card_row" class="subproduct_loading_enclosure">
					<div class="card_row lift_analysis">
						<h3>
							<span style="margin-left:35px;">DIGITAL LIFT ANALYSIS - </span>
							<span class="date_in_header">Report includes 12 months of data through <span class="date_end"></span></span>
						</h3>
						<div class="lift_card full ajax-load">
							<div class="lift_analysis_overview_top_card_left no_data">
								<div class="first_column">
									<div class="digital_lift_info_container">
										<div>
											<span class="overview_lift_digital_lift_text">DIGITAL LIFT</span>
										</div>
										<div class="average_lift_container overview_lift_left_card_inner_avg_lift_text"  style="margin-top:25px;">
											<i class="fa fa-arrow-up"></i>
											<span class="avg_lift"></span>
											<span class="avg_lift_appender">x</span>
										</div>										
									</div>
									<div class="exposed_rate_info_container">
										<div>
											<span class="overview_lift_exponsed_baseline_text">EXPOSED RATE</span>
										</div>
										<div class="overview_lift_left_card_inner_avg_lift_text" style="margin-top:10px;">
											<i class="fa fa-arrow-up" style="font-size: 25px;"></i>
											<span class="avg_conversion_rate"></span>
											<span class="avg_conversion_rate_appender">%</span>
										</div>
									</div>
									<div class="vs_test">VS</div>
									<div class="baseline_rate_info_container">
										<div>
											<span class="overview_lift_exponsed_baseline_text">BASELINE RATE</span>
										</div>
										<div class="overview_lift_left_card_inner_avg_lift_text" style="margin-top:10px;">
											<span class="avg_baseline_rate"></span>
											<span class="avg_baseline_rate_appender">%</span>
										</div>
									</div>
								</div>
								<div class="second_column">
									<div class="overview_lift_middle_card_inner_one">
										<span class="positive_lift">People who saw your digital ad were <b><span class="avg_lift"></span> times more likely</b> to visit your website than the unexposed audience.</span>
										<span class="negative_lift">People who saw your digital ad were <b><span class="avg_lift"></span> times as likely</b> to visit your website than the unexposed audience.</span>
									</div>
								</div>
							</div>
							<div class="lift_analysis_overview_top_card_right">
								<div id="lift_analysis_conversion_baseline_container"></div>
							</div>
						</div>
					</div>
				</div>
				<div id="lift_analysis_map_container" class="lift_analysis_card_container">
					<div class="lift_analysis_middle_full">
						<div class="lift_analysis_card" style="padding:0px;">
							<div class="lift_map_loading_image"><img src="/images/report_v2_loader.gif"></div>
							<div id="lift_map"></div>
						</div>
					</div>
				</div>
				<div id="lift_analysis_daily_lift_container" class="lift_analysis_card_container">
					<div class="lift_analysis_middle_full">
						<div class="lift_analysis_card lift_analysis_daily_lift_card">
							<h3>LIFT TREND</h3>
							<div class="lift_analysis_card_inner">
								<div class="lift_analysis_card_inner_left">
									<div class="lift_analysis_average_row">
										<span class="lift_analysis_average_row_number"><span class="avg_lift"></span></span>
										<span class="lift_analysis_average_row_appender" style="margin-left:-3px;">X</span>
										<div class="lift_analysis_average_row_text">LIFT</div>
									</div>
									<div class="lift_analysis_average_row">
										<span class="lift_analysis_average_row_number"><span class="avg_conversion_rate"></span></span>
										<span class="lift_analysis_average_row_appender">%</span>
										<div class="lift_analysis_average_row_text">EXPOSED RATE</div>
									</div>
									<div class="lift_analysis_average_row">
										<span class="lift_analysis_average_row_number"><span class="avg_baseline_rate"></span></span>
										<span class="lift_analysis_average_row_appender">%</span>
										<div class="lift_analysis_average_row_text">BASELINE RATE</div>
									</div>
								</div>
								<div class="lift_analysis_card_inner_right">
									<div id="lift_analysis_daily_lift_container_graph" class="lift_analysis_daily_lift_container"></div>
								</div>
							</div>
						</div>
					</div>
					<div class="campaigns_lift_loading_image" ><img src="/images/report_v2_loader.gif"></div>
					<div id="campaigns_lift_table"></div>
				</div>
				<div class="lift_analysis_row_spacer"></div>
			</div>
			<div id="product_details_card_row" class="subproduct_loading_enclosure" ></div>
			<div id="subproduct_loading_enclosure" class="subproduct_loading_enclosure" >
				<div id="leftsection"></div>
				<div id="middlesection">
					<div id="tab_left_column" style="float:left;">
						<div id="subproduct_nav_pills_holder">
						</div>
					</div>
					<div id="subproduct_content" style="clear:both;">
					</div>
					<!--div id="subproduct_loading_image" class="loading_image">
						<img src="/images/report_v2_loader.gif" />
					</div-->
					<div id="inventory_leads_modal" class="modal hide fade">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h3 id="inventory_leads_modal_header_content"></h3>
						</div>
						<div id="inventory_leads_modal_body_content" class="modal-body">
						</div>
					</div>
					<div id="tv_airing_schedule_modal" class="modal hide fade" style="width:1010px;margin-left:-505px;">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h3 id="tv_airing_schedule_modal_header_content"></h3>
						</div>
						<div id="tv_airing_schedule_modal_body_content_no_data" class="modal-body" style="display:none;"></div>
						<div id="tv_airing_schedule_modal_body_content" class="modal-body">
							<table>
								<tr>
									<td>
										<div class="demos_left" style="width:150px;">
											<div class="demo">
												<label>Average Income</label>
												<span class="income"></span>
											</div>
											<br>
											<div class="demo">
												<label>Median Age</label>
												<span class="median_age"></span>
											</div>
											<br>
											<div class="demo">
												<label>People / Household</label>
												<span class="persons_household"></span>
											</div>
											<br>
											<div class="demo">
												<label>Average Home Value</label>
												<span class="home_value"></span>
											</div>
										</div>
									</td>
									<td>
										<div class="demo_column">
											<div class="demographic_group">
												<div class="demographic_group_title" style="">Gender</div>
												<div class="internet_average_center_line"></div>
												<figure>
													<div id="sparkline_male_population" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Male:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_female_population" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Female:</figcaption>
												</figure>
											</div>

											<div class="demographic_group">
												<div class="demographic_group_title" style="">Age</div>
												<div class="internet_average_center_line"></div>
												<figure>
													<div id="sparkline_age_under_18" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">&lt; 18:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_age_18_24" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">18 - 24:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_age_25_34" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">25 - 34:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_age_35_44" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">35 - 44:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_age_45_54" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">45 - 54:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_age_55_64" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">55 - 64:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_age_65" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">65 +:</figcaption>
												</figure>
											</div>

											<div class="internet_average_subtext">US Average</div>
										</div>
									</td>

									<td>
										<div class="demo_column">
											<div class="demographic_group">
												<div class="demographic_group_title" style="">Household Income</div>
												<div class="internet_average_center_line"></div>
												<figure>
													<div id="sparkline_income_0_50" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">&lt; $50k:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_income_50_100" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">$50k-100k:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_income_100_150" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">$100k-150k:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_income_150" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">$150k +:</figcaption>
												</figure>
											</div>

											<div class="demographic_group" style="left:0px; top:0px;">
												<div class="demographic_group_title" style="">Education Level</div>
												<div class="internet_average_center_line"></div>
												<figure>
													<div id="sparkline_college_no" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">No College:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_college_under" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">College:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_college_grad" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Grad School:</figcaption>
												</figure>
											</div>

											<div class="internet_average_subtext">US Average</div>
										</div>
									</td>

									<td>
										<div class="demo_column">
											<div class="demographic_group">
												<div class="demographic_group_title" style="">Children In Household</div>
												<div class="internet_average_center_line"></div>
												<figure>
													<div id="sparkline_kids_no" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">No Kids:</figcaption>
												</figure>
												<figure>
													<div id="sparkline_kids_yes" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Has Kids:</figcaption>
												</figure>
											</div>

											<div class="demographic_group">
												<div class="demographic_group_title" style="">Ethnicity</div>
												<div class="internet_average_center_line"></div>
												<figure>
													<div id="sparkline_white_population" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Cauc:  </figcaption>
												</figure>
												<figure>
													<div id="sparkline_black_population" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Afr Amer: </figcaption>
												</figure>
												<figure>
													<div id="sparkline_asian_population" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Asian: </figcaption>
												</figure>
												<figure>
													<div id="sparkline_hispanic_population" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Hisp: </figcaption>
												</figure>
												<figure>
													<div id="sparkline_race_other" class="demographic_sparkline"></div>
													<figcaption class="demographic_row_name">Other: </figcaption>
												</figure>
											</div>

											<div class="internet_average_subtext">US Average</div>
										</div>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<div id="rightsection"></div>
			</div>

			<div class="footer_section">
				<div class="copyright_position">
				</div>
			</div>
			<div id="error_info">
			</div>

			<form id="download_csv_form" action="/report_v2/download_csv" method="POST" style="display:none;" target="_blank">
				<div id="download_csv_advertiser"></div>
				<div id="download_csv_campaign"></div>
				<input type="hidden" id="download_csv_start_date" name="start_date" value="" />
				<input type="hidden" id="download_csv_end_date" name="end_date" value="" />
				<input type="hidden" id="download_csv_tab" name="tab" value="" />
				<input type="hidden" id="download_csv_nav_pill" name="subproduct_nav_pill" value="" />
			</form>
			<form id="download_inline_csv_form" action="/report_v2/download_inline_csv" method="POST" style="display:none;" target="_blank">
				<input type="hidden" id="file_name" name="file_name" value="" />
				<input type="hidden" id="csv_data" name="csv_data" value="" />
			</form>
		</div>
	</div>
	<div id="tv_creative_video_modal" class="modal hide fade">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3 id="tv_creative_video_modal_header_content"></h3>
		</div>
		<div id="tv_creative_video_modal_body_content" class="modal-body tv_creative_video_player_modal">
		</div>
	</div>
</div>
	<div id="digital_overview_modal" class="modal hide fade" style="width:1000px;margin-left:-500px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<div id="digital_overview_info">
				<span id="digital_overview_date"></span>
			</div>
			<h3 id="digital_overview_modal_header_content">Product Summary</h3>
		</div>
		<div id="digital_overview_modal_body_content" class="modal-body" style="overflow:visible;">
			<div class="card full ajax-load" data-source="digital_overview" data-chart-type="digital_overview" style="height:auto;">
				<div id="overview_digital_overview" style="position:relative;height:auto;"></div>
			</div>
		</div>
	</div>
<script type="text/javascript">
<?php
echo "var adv_results=new Array();";
foreach($advertisers as $advertiser_array)
{
	$advertiser_name = $advertiser_array['name'];
	$advertiser_id = $advertiser_array['id'];
	$advertiser_name=str_replace("'","\'",$advertiser_name);
	echo "adv_results['a-$advertiser_id']='$advertiser_name';";

}
?>
</script>
