<ul id="io_right_nav" class="side-nav fixed table-of-contents" style="">
	<li id="io_nav_opportunity">
		<a href="#mpq_advertiser_info_section">
			<span class="io_nav_text">Opportunity</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
	<li id="io_nav_geo">
		<a href="#mpq_geography_info_section">
			<span class="io_nav_text">Geo</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
	<li id="io_nav_audience">
		<a href="#mpq_audience_info_section">
			<span class="io_nav_text">Audience</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
	<li id="io_nav_tracking">
		<a href="#mpq_tracking_info_section">
			<span class="io_nav_text">Tracking</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
	<li id="io_nav_flights">
		<a href="#mpq_flight_info_section">
			<span class="io_nav_text">Flights</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
	<li id="io_nav_creative">
		<a href="#mpq_creative_info_section">
			<span class="io_nav_text">Creative</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
	<li id="io_nav_notes">
		<a href="#mpq_notes_info_section">
			<span class="io_nav_text">Notes</span>
			<i class="material-icons io_icon_not_started">&#xE002;</i>
		</a>
	</li>
<?php
	if ($user_role != 'sales' || ($user_role == 'sales' && $mpq_type != 'io-submitted'))
	{
?>		<li class="io_save_button_container">
			<a id="io_save_button" href="#!" class="waves-effect waves-light btn">Save</a>
		</li>
<?php	}
?>
<?php
	if ($io_submit_allowed)
	{
?>		<li class="io_submit_button_container">
			<a id="io_submit_button" href="#!" class="waves-effect waves-light btn">Submit</a>
		</li>
<?php	}
	else
	{
?>		<li class="io_submit_for_review_button_container">
			<a id="io_submit_for_review_button" href="#!" class="waves-effect waves-light btn">Submit For Review</a>
		</li>
<?php	}
?>

</ul>

<div id="mpq_body_container" class="container" style="">
<?php

	$this->load->view('rfp/advertiser_info_section_for_io_html');
	$this->load->view('rfp/product_info_section_for_io_html');
	$this->load->view('rfp/geography_info_section_html');
	$this->load->view('rfp/audience_info_section_html');
	$this->load->view('rfp/tracking_info_section_html');
	$this->load->view('rfp/flight_info_section_html');
	$this->load->view('rfp/creative_info_section_html');
	$this->load->view('rfp/notes_info_section_html');
?>
</div>

<div id="io_flights_modal" class="modal modal-fixed-footer">
	<div class="modal-content">
		<form class="col s12">
			<div id="io_define_flights_budget_allocation" class="row">
				<h4 class="col s5">Flights <small class="location_name"></small></h4>
				<div class="input-group col s4">
					<input class="with-gap" name="budget_allocation" type="radio" id="io_allocation_per_pop" <?php echo ($allocation_method == "per_pop"? 'checked="checked"':'');?>>
					<label for="io_allocation_per_pop">allocate by population</label>
				</div>
				<div class="input-group col s3">
					<input class="with-gap" name="budget_allocation" type="radio" id="io_allocation_fixed" <?php echo ($allocation_method == "fixed"? 'checked="checked"':'');?>>
					<label for="io_allocation_fixed">fixed per location</label>
				</div>
			</div>
			<div class="row">
				<div class="input-field col s2">
					<input id="io_add_flight_start" type="text" class="datepicker" value="">
					<label for="io_add_flight_start">Start Date</label>
				</div>
				<div class="input-field col s2">
					<input id="io_add_flight_end" type="text" class="datepicker" value="">
					<label for="io_add_flight_end">End Date</label>
				</div>
				<div class="input-field col s3">
					<input id="io_add_flight_impression" type="text" value=" ">
					<label for="io_add_flight_impression">Impressions <span class="per_geo">Per Location</span></label>
				</div>
				<div class="input-field col s4">
					<select id="io_add_flight_term" class="io_period_dropdown">
						<option value="MONTH_END">Calendar Month</option>
						<option value="FIXED">Fixed Term</option>
						<option value="BROADCAST_MONTHLY">Broadcast Calendar</option>
					</select>
					<label for="io_add_flight_term">Type</label>
				</div>
				<div class="input-field col s1">
					<button class="btn btn-floating" id="io_add_flights_button"><i class="icon-plus icon-white"></i></button>
				</div>
			</div>
		</form>
		<ul class="collection with-header" id="flights-collection">
			<li class="collection-header row">
				<span class="col s4">Date Range</span>
				<span class="col s2 offset-s1">Impressions</span>
				<span class="col s2 offset-s2">Remove?</span>
			</li>
		</ul>
	</div>
	<div class="modal-footer">
		<input type="hidden" id="io_flights_region_id" />
		<input type="hidden" id="io_flights_product_id" />
		<span class="total-impressions"></span>
		<a id="io_flights_cancel" href="#" class="modal-action modal-close waves-effect waves-red btn-flat">Cancel</a>
		<a id="io_flights_ok" href="#" class="waves-effect waves-green btn-flat">Save Flights</a>
	</div>
</div>

<div id="io_confirm_flights_modal" class="modal">
	<div class="modal-content">
		<h4>Warning!</h4>
		<p>Doing this will overwrite any flights you've previously created for this product. Are you sure you want to define a new set of flights?</p>
	</div>
	<div class="modal-footer">
		<a id="io_confirm_flights_cancel" href="#" class="modal-action waves-effect waves-green btn-flat">Cancel</a>
		<a id="io_confirm_flights_ok" href="#" class="waves-effect waves-green btn-flat">OK</a>
	</div>
</div>

<div id="io_confirm_creatives_modal" class="modal">
	<div class="modal-content">
		<h4>Warning!</h4>
		<p>Doing this will overwrite any creative assignments you've previously made for this product. Are you sure you want to assign a new set of creatives for this product?</p>
	</div>
	<div class="modal-footer">
		<a id="io_confirm_creatives_cancel" href="#" class="modal-action waves-effect waves-green btn-flat">Cancel</a>
		<a id="io_confirm_creatives_ok" href="#" class="waves-effect waves-green btn-flat">OK</a>
	</div>
</div>

<div id="io_define_creatives_modal" class="modal">
	<div class="modal-content">
		<div class="row">
			<h4 class="col s7 grey-text text-darken-1">Creative</h4>
			<div class="col s5" style="margin:1rem 0rem 1rem 0rem; padding-top:7px;">
				<div style="float:right; display:inline-block;">
					<input class="filled-in" type="checkbox" id="define_creatives_modal_all_versions">
					<label for="define_creatives_modal_all_versions">Show all versions?</label>
				</div>
			</div>
		</div>

		<div id="io_define_creatives_modal_body_content">
		</div>
	</div>
	<div class="modal-footer">
		<a id="io_define_creatives_cancel" href="#" class="modal-action modal-close waves-effect waves-red btn-flat">Cancel</a>
		<a id="io_define_creatives_ok" href="#" class="waves-effect waves-green btn-flat">OK</a>
	</div>
</div>

<div id="io_single_creative_modal" class="modal">
	<div class="modal-content">
		<div class="row">
			<h4 class="col s7 grey-text text-darken-1">Creative</h4>
			<div class="col s5" style="margin:1rem 0rem 1rem 0rem; padding-top:7px;">
				<div style="float:right; display:inline-block;">
					<input class="filled-in" type="checkbox" id="single_creative_modal_all_versions">
					<label for="single_creative_modal_all_versions">Show all versions?</label>
				</div>
			</div>
		</div>
		<div id="io_single_creative_modal_body_content">
		</div>
	</div>
	<div class="modal-footer">
		<a id="io_single_creative_cancel" href="#" class="modal-action modal-close waves-effect waves-red btn-flat">Cancel</a>
		<a id="io_single_creative_save" href="#" class="waves-effect waves-green btn-flat">OK</a>
	</div>
</div>

<div id="io_redirect_to_all_ios_modal" class="modal">
	<div class="modal-content">
		<h4 class="grey-text text-darken-1">The form has expired.</h4>
		You will be redirected to your insertion orders dashboard.
	</div>
	<div class="modal-footer">
		<a id="io_redirect_to_all_ios_ok" href="#" class="waves-effect waves-green btn-flat">OK</a>
	</div>
</div>

<form id="io_product_change" method="POST" action="/mpq_v2/change_io_product_set" class="hidden">
	<input type="hidden" name="product_id" value="">
	<input type="hidden" name="product_status" value="">
	<input type="hidden" name="mpq_id" value="<?php echo $mpq_id; ?>"/>
</form>

<div id="io_product_change_confirm" class="modal">
	<div class="modal-content">
		<h4>Your products have changed.</h4>
		<p>
			You have changed your selected products.  The page needs to refresh to load the new data.  If you have removed a product, any changes to that product's flights or creatives will be lost.
		</p>
	</div>
	<div class="modal-footer">
		<a id="io_product_change_cancel" href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cancel</a>
		<a id="io_product_change_reload" href="#!" class="modal-action waves-effect waves-green btn-flat">Reload Page</a>
	</div>
</div>

<form id="io_redirect_to_all_ios" method="POST" action="/insertion_orders">
	<input id="io_redirect_to_all_ios_source" type="hidden" name="source" value="io">
	<input id="io_redirect_to_all_ios_method" type="hidden" name="submission_method" value="">
</form>

<div id="io_loading_mask"></div>

<div id="io_dfp_advertiser_modal" class="modal">
	<div class="modal-content">
		<h4>Select a DFP Advertiser</h4>
		<p>You're currently using a product that has impressions set to go to DFP. Please select an advertiser.</p>
		<div class="row">
			<div class="input-field col s6">
				<input type="text" id="mpq_dfp_advertiser" class="grey-text text-darken-1" length="255" value="<?php echo empty($order_name) ? "" : $order_name; ?>">
				<label for="s2id_mpq_dfp_advertiser" class="mpq_org_name_label">DFP Advertiser</label>
			</div>
		</div>
		<div id="dfp_advertiser_container" class="row">
			<div class="input-field col s6">
				<input placeholder="" id="dfp_advertiser_name" name="dfp_advertiser_name" type="text" length="255"/>
				<label for="dfp_advertiser_name" >DFP Advertiser Name</label> 
			</div>
			<div id="#new_dfp_advertiser_loading_image" style="position:relative;top:20px;height:25px;display:none;"> 
					<img src="/images/mpq_v2_loader.gif">
			</div>
		</div>					
	</div>
	<div class="modal-footer">
		<a id="io_dfp_advertiser_modal_cancel" href="#" class="modal-action waves-effect waves-green btn-flat">Cancel</a>
		<a id="io_dfp_advertiser_modal_ok" href="#" class="waves-effect waves-green btn-flat">Submit</a>
	</div>
</div>

<div class="modal geofencing_modal" id="add_geofencing_modal">
	<div class="modal-content" style="position: static;">
		<h4>Geofences (<span class="geofence_modal_name"></span>)</h4>
		<form action="#" class="col s12">
			<!--template bindings={}-->
		</form>
	</div>
	<div class="modal-footer" style="position: static;">
		<a class="save_geofence_points waves-effect waves-green btn-flat">Save</a>
		<a class="waves-effect modal-close waves-red btn-flat">Cancel</a>
	</div>
</div>
