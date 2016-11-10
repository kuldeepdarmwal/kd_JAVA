<?php $zips_string = implode(', ', $geographics_section_data['zips']); ?>

<!-- geography section -->
	<div id="mpq_geography_info_section" class="mpq_section_card card scrollspy">
		<h4 class="card-title grey-text text-darken-1 bold">Geography
<?php
		 if($is_rfp == true)
		 {
			 $geo_product_text = "";
			 foreach($products as $product)
			 {
				 if($product['is_geo_dependent'] == 1 && !$product['is_political'])
				 {
					 $geo_product_definition = json_decode($product['definition'], true);
					 if($geo_product_text !== "")
					 {
						 $geo_product_text .= ", ";
					 }
					 $geo_product_text .= ($geo_product_definition['first_name'] !== false) ? $geo_product_definition['first_name']." " : "";
					 $geo_product_text .= $geo_product_definition['last_name'];
				 }
			 }
			 echo '<div style="display:inline-block;font-weight:normal;font-size:1rem;margin-left:5%;">' . $geo_product_text . "</div>";
		 }
?>

		</h4>
		<div class="geo_tab">
			<div class="card-content">
				<div style="display:none;" id="geo_error_div">
					<span class="span12 alert alert-error" id="geo_error_text"></span>
				</div>

				<div class="row">
					<div class="col s2">
						<select id="mpq_geo_search_type">
							<option value="custom_region_list">Regions</option>
							<option value="radius_search">Radius Search</option>
							<option value="zip_list">Known Zips</option>
						</select>
					</div>
					<div id="mpq_map_search_box_content" class="col s10">
						<div class="search_box_pane active" id="custom_region_list">
							<form>
								<div class="row">
									<div class="col s9">
										<input style="width:100%" type="hidden" id="custom_regions_multiselect">
									</div>
									<div class="col s3">
										<a id="custom_regions_multiselect_load_button" class="waves-effect waves-light btn">Load Regions</a>
									</div>
								</div>
							</form>
						</div>
						<div class="search_box_pane" id="radius_search">
							<form>
								<div class="row">
									<div class="col s9 grey-text text-darken-1">
										<div style="float:left; width:175px;">
											<input type="hidden" id="region_type" value="ZIP">
											<span>Zips within </span>
											<div id="radius_span">
												<input class="grey-text text-darken-1" type="text" id="radius" value="<?php echo ($geo_radius == '' ? 5 : $geo_radius); ?>" onclick="this.select();" />
												<span> miles of</span>
											</div>
										</div>
										<div style="overflow:hidden">
											<input class="grey-text text-darken-1" type="text" value="<?php echo $geo_center; ?>" id="address" onclick="this.select();" />
										</div>
									</div>
									<div class="col s3">
										<a class="waves-effect waves-light btn" id="searchbut">Search</a>
									</div>
								</div>
							</form>
						</div>
						<div class="search_box_pane" id="zip_list">
							<form>
								<div class="row">
									<div class="col s9 input-field">
										<textarea id="set_zips" class="materialize-textarea grey-text text-darken-1" onclick="this.select();"><?php echo $zips_string; ?></textarea>
										<label id="set_zips_label" style="margin-top: -1.65rem;" for="set_zips">Type zips here (For Example: 94303, 94100, 93456...)</label>
									</div>
									<div class="col s3">
										<a class="waves-effect waves-light btn" id="known_zips_load_button">Load Zips</a>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div id="mpq_geo_map_container">
				<div id="map_loading_image">
					<img src="/images/mpq_v2_loader.gif" />
				</div>
				<div id="add_location_container">
					<a id="geo_add_location_dropdown_button" class="btn waves-effect waves-light grey lighten-2 modal-trigger tooltipped" data-position="bottom" data-tooltip-type="html" data-tooltip="Split your budget<br>by geography" data-target="#bulk_locations_upload_modal" href="#bulk_locations_upload_modal">
						<i class="material-icons">&#xE5D2;</i>
						<span class="number_of_locations_span"></span>
					</a>
					<div id="geo_location_menu_container">
						<div class="geo_location_inner_menu grey lighten-2">
							<div id="search_container_div" class="row">
								<form>
									<div class="input-field">
										<input id="search_location_list" type="text" required>
										<label for="search_location_list">Search</label>
									</div>
								</form>
							</div>
							<a class="btn green tooltipped" data-tooltip="Add single geography" data-position="bottom" id="geo_add_single_location">
								<i class="material-icons">&#xE145;</i>
							</a>
							<a id="geo_add_bulk_locations" class="btn modal-trigger grey tooltipped" data-tooltip="Bulk upload geographies" data-position="bottom" data-target="#bulk_locations_upload_modal" href="#bulk_locations_upload_modal">
								<i class="material-icons">&#xE8B8;</i>
							</a>
						</div>

						<div style="margin:0px -1px;margin-top:-8px;">
							<ul id="geo_location_dropdown" class="collection"></ul>
						</div>
					</div>
				</div>
				<div id="region-links" style="width:100%;height:750px;"></div>
			</div>
		</div>
	</div>

	<div id="bulk_locations_upload_modal" class="modal modal-fixed-footer" style="top: 20%;">
		<div class="modal-content">
			<h4>Bulk Upload Geographies</h4>
			<p>Pro tip: enter your data into a spreadsheet and copy-paste into the text box below.</p>
			<p>Select your bulk upload method:</p>
			<form action="#">
				<span class="tooltipped" data-tooltip="Format: Address;Radius" data-position="top">
					<input name="submission_type" type="radio" id="by_radius" value="radius" checked="checked" />
					<label for="by_radius">By Radius</label>
				</span>
				<span class="tooltipped" data-tooltip="Format: Address;Population" data-position="top">
					<input name="submission_type" type="radio" id="by_population" value="population" />
					<label for="by_population">By Population</label>
				</span>
				<span class="tooltipped" data-tooltip="Format: Address;Radius; OR Address;;Population" data-position="top">
					<input name="submission_type" type="radio" id="by_both" value="both" />
					<label for="by_both">Both</label>
				</span>
				<blockquote id="radius_blockquote">
					Example: Geography Center &#8677; Miles
					<table class="bordered">
						<tbody>
							<tr>
								<td>Golden Gate Bridge</td>
								<td>10</td>
							</tr>
							<tr>
								<td>Statue of Liberty</td>
								<td>13</td>
							</tr>
							<tr>
								<td>1600 Pennsylvania Avenue NW Washington, DC 20500</td>
								<td>9</td>
							</tr>
						</tbody>
					</table>
				</blockquote>
				<blockquote id="population_blockquote" style="display:none;">
					Example: Location Center &#8677; Population
					<table class="bordered">
						<tbody>
							<tr>
								<td>Golden Gate Bridge</td>
								<td>1,000,000</td>
							</tr>
							<tr>
								<td>Statue of Liberty</td>
								<td>15,000,000</td>
							</tr>
							<tr>
								<td>1600 Pennsylvania Avenue NW Washington, DC 20500</td>
								<td>100000</td>
							</tr>
						</tbody>
					</table>
				</blockquote>
				<blockquote id="both_blockquote" style="display:none;">
					Example: Location Center &#8677; Miles &#8677; <span style="font-weight:normal;">OR</span> Location Center &#8677; &#8677; Population
					<table class="bordered">
						<tbody>
							<tr>
								<td>Golden Gate Bridge</td>
								<td></td>
								<td>1,000,000</td>
							</tr>
							<tr>
								<td>Statue of Liberty</td>
								<td>13</td>
								<td></td>
							</tr>
							<tr>
								<td>1600 Pennsylvania Avenue NW Washington, DC 20500</td>
								<td></td>
								<td>100000</td>
							</tr>
						</tbody>
					</table>
					</table>
				</blockquote>
				<div class="input-field">
					<textarea id="bulk_upload_input_field" style="height:125px"></textarea>
					<label for="bulk_upload_input_field">Paste tab-delimited locations here</label>
				</div>
				<span style="font-size:10px;font-weight:light;">(Tabs will be converted into semicolons)</span>
			</form>
			<div class="progress" style="display:none;">
				<div class="indeterminate"></div>
			</div>
		</div>
		<div class="modal-footer">
			<a id="upload_bulk_locations_button" class="waves-effect waves-green btn-flat">Upload</a>
			<a id="close_bulk_upload_modal_button" class="waves-effect waves-red btn-flat">Cancel</a>
		</div>
	</div>