<div class="container container-body" style="">
	<img src="<?php echo $logo_path; ?>" id="print_logo"/>
	<div>
		<div class="controller_section">
			<div class="report_v2_advertiser_selection">
				<div class="report_v2_advertiser_title controller_option_title">
					Advertiser
				</div>
				<div id="report_v2_advertiser_dropdown" class="target_dropdown">
						<?php
							if($role == "business")
							{
								echo '<select id="report_v2_advertiser_options" onchange="select_advertiser();" style="width: 280px; ">';
								foreach($advertisers as $advertiser_array)
								{
									$advertiser_name = $advertiser_array['Name'];
									$advertiser_id = $advertiser_array['id'];
									$advertiser_visible = $advertiser_name;
									if($advertiser_id == $advertiser_selected_id)
									{
										echo '<option id="" selected="selected" value="'.$advertiser_id.'" data-html-text="'.$advertiser_id.'">'."\n";
									}
									else
									{
										echo '<option id="" value="'.$advertiser_id.'" data-html-text="'.$advertiser_id.'">'."\n";
									}
									echo $advertiser_visible."\n";
									echo '</option>'."\n";
								}
							}
							else
							{
								echo '<input id="report_v2_advertiser_options" onchange="select_advertiser();" style="width: 280px; ">';
							}
						?>
					</select>
				</div>
			</div>
			<div class="report_v2_campaign_selection">
				<div class="report_v2_campaign_title controller_option_title">
					Campaign
				</div>
				<div id="report_v2_campaign_dropdown" class="target_dropdown">
					<select id="report_v2_campaign_options" multiple="multiple" style="width: 280px;" >
					</select>
				</div>
			</div><div class="report_v2_date_selection">
				<div class="report_v2_date_title controller_option_title">
					Start &amp; End Date
				</div>
				<div class="report_v2_date_dropdown">
					<input type="text" class="report_v2_datepicker_input" id="report_v2_date_range_input" />
				</div>
			</div>
		</div>
		<div class="nav_section_container">

			<div id="products_nav_section" class="products_section">
				<?php echo $starting_tabs; ?>
			</div>
			<div class="report_v2_table_download_csv_button_new">
				<a href="#" id="table_download_link" title="Download .csv file of table data">
					Table CSV <i class="icon icon-download-alt"></i>
				</a>
			</div>
		</div>

		<div class="products_content_section">
		</div>

		<div id="new_tabs_section">
			<div id="new_tabs_controls">
				<div id="new_tabs_radio">
				</div>
			</div>
			<div id="overview_tab_content">
				<div id="overview_tab_inner_content">
					<div class="graphs_section card two">
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
					<div class="card one ajax-load" data-source="creatives" data-chart-type="creatives">
						<h4>Top Creatives</h4>
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
						<div class="chart" id="card2">
							<img class="thumbnail switchable disabled" src="" /><div class="stats">	<div class="impressions report_tooltip" data-content="Ads or listings shown to audiences" data-trigger="hover" data-placement="top" data-delay="160">		<span class="value"></span>		<span>Impressions</span>	</div>	<div class="interaction_rate report_tooltip" data-content="Ad Interactions / Impressions" data-trigger="hover" data-placement="top" data-delay="160">		<span class="value"></span>		<span>Interaction Rate</span>	</div></div>
						</div>
					</div>
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
					<div class="card one ajax-load" data-source="completions" data-chart-type="bar_completions">
						<h4>Completions</h4>
						<h5>
							<?php
							if ($is_tmpi_accessible) {
								echo 'PreRoll';
							}
							else {
								echo 'Pre-Roll';
							}
							?>
						</h5>
						<div class="chart" id="card4"></div>
					</div>
					<?php if ($is_tmpi_accessible) : ?>
					<div class="card one ajax-load" data-source="clicks" data-chart-type="column">
						<h4>Clicks</h4>
						<h5>Visits</h5>
						<div class="chart" id="card5"></div>
					</div>
					<div class="card one ajax-load" data-source="inventory_price" data-chart-type="pie">
						<h4>Price Breakdown</h4>
						<h5>Inventory</h5>
						<div class="chart" id="card6"></div>
					</div>
					<div class="card one ajax-load" data-source="search" data-chart-type="list">
						<h4>Local Search</h4>
						<h5>Directories</h5>
						<div class="chart" id="card7"></div>
					</div>
					<div class="card one ajax-load" data-source="content" data-chart-type="column">
						<h4>Top Videos</h4>
						<h5>Content</h5>
						<div class="chart" id="card8"></div>
					</div>
					<?php endif; ?>
				</div>
				<div id="overview_loading_image">
					Loading <img src="/images/report_v2_loader.gif" />
				</div>
				<div id="no_data_overview_overlay">
					<h3>There is no data available for the dates you've selected. Please select a different set of dates.</h3>
				</div>
			</div>
			<div id="subproduct_loading_enclosure" style="display:none;">
				<div id="tab_left_column" style="float:left;">
					<div id="subproduct_nav_pills_holder">
					</div>
				</div>
				<div id="subproduct_content" style="clear:both;">
				</div>
				<div id="subproduct_loading_image" class="loading_image">
					<img src="/images/report_v2_loader.gif" />
				</div>
				<div id="inventory_leads_modal" class="modal hide fade">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3 id="inventory_leads_modal_header_content"></h3>
					</div>
					<div id="inventory_leads_modal_body_content" class="modal-body">
					</div>
				</div>
			</div>
		</div>

		<div class="footer_section">
			<div class="copyright_position">
			</div>
		</div>
		<div id="error_info">
		</div>

		<form id="download_csv_form" action="/reports/download_csv" method="POST" style="display:none;" target="_blank">
			<input type="hidden" id="download_csv_advertiser" name="advertiser_id" value="" />
			<input type="hidden" id="download_csv_campaign" name="campaign_values" value="" />
			<input type="hidden" id="download_csv_start_date" name="start_date" value="" />
			<input type="hidden" id="download_csv_end_date" name="end_date" value="" />
			<input type="hidden" id="download_csv_tab" name="tab" value="" />
			<input type="hidden" id="download_csv_nav_pill" name="subproduct_nav_pill" value="" />
		</form>
	</div>
</div>
