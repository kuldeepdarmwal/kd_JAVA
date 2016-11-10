<!-- ADVERTISERS MAIN HTML -->

<div id="am_message_box" class="alert alert-error">
	<button type="button" data-dismiss="alert" class="close">&times;</button>
	<div id="am_message_box_content"></div>
</div>

<div id="am_page_container">
	<div id="am_header_content" class="row-fluid">
		<div id="am_date_header">
			<h3><span>Client Success <?php echo $report_date; ?></span></h3>
		</div>
		

		<div id="am_header_controls">
			<div class="am_control_item">
				
				<div id="am_end_date_control">
					<div class="am_control_label">End: </div>
					<input type="text" name="end_date" id="am_end_date" class="am_datepicker" data-date-format="mm/dd/yyyy">
				</div>
				<div id="am_start_date_control">
					<div class="am_control_label">Start: </div>
					<input type="text" name="start_date" id="am_start_date" class="am_datepicker" data-date-format="mm/dd/yyyy">
				</div>
				<br>
				<button id="am_date_control_button" type="button" class="btn btn-success" style="display:block; float:right;">Apply Date Range</button>
				
			</div>

			<div id="am_assigned_control" class="am_control_item">
				<div class="am_control_label">Assigned: </div>
				<input type="hidden" id="am_advertiser_owner_select">
			</div>
		</div>

	</div>

	<div id="am_body_content">
	</div>

</div>

<img id="am_loading_img" src="/images/loading.gif" alt="">
