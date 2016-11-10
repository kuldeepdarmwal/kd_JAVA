<?php
 ?>
<style type="text/css">
	#all_ios_header_section .popover {
		width: 900px;
		padding: 20px;
		z-index: 999999;
		left: 10px !important;
	}
	.io_filter_info
	{
		text-decoration: none !important;
	}
</style>
<div id="all_ios_body_container">
		<div id="all_ios_message_box" class="alert">
			<button type="button" class="close">×</button>
			<span id="all_ios_message_box_content"></span>
		</div>
	<div id="all_ios_header_section">
		<h3 style="display:inline-block;">Insertion Orders</h3>
		<?php if($user_role == 'admin') { ?>
		<a href="#" id="all_ios_header_popover" class="io_filter_info" data-html="true" data-toggle="popover" data-content="<small>URL parameters can be used to filter the Insertion Orders that appear on the page.

		<div><b>Fields that can be filtered via URL parameters:</b></div>

		status<br/>
		partner<br/>
		createdate<br/>
		startdate<br/>
		<p><b>Rules:</b>
		<ul><li>
		Use a colon (:) to join two values for one field<ul>
		<li>Example: <u>http://frequence.brandcdn.com/insertion_orders/insertion_orders</u><b>?startdate=20160612:20160623</b></li>
		<li>Result: Displays all IOs with a Start Date of 2016-06-12 or 2016-06-23</li></ul></li>
		<li>Use a 'NOT' statement to filter out values<ul>
		<li>Example: <u>http://frequence.brandcdn.com/insertion_orders/insertion_orders</u><b>?status=notsubmitted</b></li>
		<li>Result: Displays all IOs except ones with a Status of Submitted</li></ul></li>
		<li>Create an AND statement by filtering two or more fields<ul>
		<li>Example: <u>http://frequence.brandcdn.com/insertion_orders</u><b>?status=submitted&startdate=20160623</b></li>
		<li>Result: Displays all IOs that have a Status of Submitted AND a Start Date of 20160623</li></ul></li>
		<li>Exact match is required for using filters.<ul>
		<li>Searching for all Spectrum Reach IOs requires the Partner is entered as 'Spectrum Reach USA' or 'spectrum reach usa' (search is case agnostic)</li></ul></li>
		</ul></p>

		<b>Useful custom searches:</b>
		<p><u>http://frequence.brandcdn.com/insertion_orders</u><b>?status=submitted</b><br/>
		- Only IOs with a Submitted Status are displayed</p>

		<p><u>http://frequence.brandcdn.com/insertion_orders</u><b>?startdate=today</b><br/>
		- Only IOs with a Start Date of the current date are displayed</p>

		<p><u>http://frequence.brandcdn.com/insertion_orders</u><b>?status=submitted&startdate=today</b><br/>
		- IOs with a Status of Submitted are displayed AND a Start Date of the current date are displayed</p>

		<p><u>http://frequence.brandcdn.com/insertion_orders</u><b>?partner=spectrum reach usa</b><br/>
		- IOs with assigned to Spectrum Reach USA Partner or any of the IOs below in the Partner hierarchy are displayed
		</p>

		<p><b>Email Errors:</b><br/>
		IOs that didn't generate an email can be found by using the custom search field and querying on 'mailgun error'.</p></small>" data-placement="bottom" title="" data-original-title="<b>Advanced Custom Search Filter</b>"><i class="icon-question-sign"></i></a>
		<?php } ?>
		<a href="/io" id="pv_io_link_button" class="btn btn-link btn-large"><i class="icon-paste"></i> <strong>New Insertion Order</strong></a>
	</div>
	<div id="all_ios_body_section">
		<table id="all_ios_table" cellspacing="0" class="order-column hover row-border" style="display:none;"></table>
		<?php if($user_role == 'admin') { ?>
		<label class="checkbox" style="background-color: #FBEFF2; float:left"><input type="checkbox"  name="demo_io_checkbox" id="demo_io_checkbox" value="show"/>Show Demo Data</label>
		<?php } ?>
	</div>
</div>
<form id="all_ios_new_form" action="/io" method="post">
	<input id="all_ios_new_form_source" type="hidden" name="source" value="new" />
</form>
<form id="all_ios_existing_form" action="/io" method="post">
	<input id="all_ios_existing_form_method" type="hidden" name="submission_method" value="" />
	<input id="all_ios_existing_form_mpq_id" type="hidden" name="mpq_id" value="" />
	<input id="all_ios_existing_form_source" type="hidden" name="source" value="io" />
</form>
<div id="all_ios_preview_modal" class="modal hide fade" tabindex="-1">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Insertion Order Preview</h3>
	</div>
	<div class="modal-body">
		
	</div>
	<div class="modal-footer">
		<a class="btn" data-dismiss="modal">Close</a>
	</div>
</div>
