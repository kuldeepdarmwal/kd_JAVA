<!--CAMPAIGN HEALTHCHECK VIEW -->

<div id="c_message_box" class="alert alert-error">
	<button type="button" class="close">&times;</button>
	<div id="c_message_box_content"></div>
</div>

<div id="campaigns_main_page_container">
	<div class="row-fluid">
		<div id="report_date_header" class="span8">
			<h3 style="width:auto; float:left; margin-right:10px;"><span>Report Date: 	<?php echo $report_date; ?></span></h3>
			<h3 style="width:auto;"><?php echo $campaign_tags_string; ?></h3>
		</div>
		<?php if($role == 'admin' || $role == 'ops') { ?>
			<input type='text' id='custom_search' style="font-size:11px; width: 670px;" title='Cycle End can also be searched by ce:<date in yyyy-mm-dd format>' 
			value=''>
			<button type="button" class="btn btn-success" onclick='custom_table_search(true)'><i class="icon-search"></i></button>
			<button type="button" class="btn btn-info" onclick='document.getElementById("custom_search").value="^PAUS_2, ^PAUS_1, ^PAUS_0, ^LIVE_0, ^OFF_01, ^ARCH_1, ^ARCH_0, YES_TGT_IM"'>
				<i class="icon-refresh"></i>
			</button>
			<button type="button" class="btn btn-info" onclick='document.getElementById("custom_search").value+=", TTD_NO_PEND"'>
				NP
			</button>
		<?php } ?>
		<div id="campaign_html_header" class="span4">
			<button data-toggle="modal" data-target="#bulk_download_modal" class="btn btn-inverse" type="button" id="bulk_download_button" >
				<i class="icon-envelope-alt icon-white"></i> Bulk Campaign Data
			</button>
			<?php if($role == 'admin' || $role == 'ops') { ?>
			<a class="btn btn-danger" href="/campaign_health/graveyard" target="_blank"><i class="icon-trash icon-white"></i> Archived</a>
			<?php } ?>
	 		
        </div>
	</div>
	<div id="campaign_body_section">

<?php if($role == 'admin' || $role == 'ops'){ ?>
		<button id="ct_tag_utility_button_1" data-toggle="modal" data-target="#tag_utility_modal" type="button" class="btn btn-link ct_tag_utility_button">
			<i class="icon-cog"></i> Tag Utility
		</button>

		
<?php } ?>

		<table id="campaign_table" cellspacing="0" class="order-column row-border" style="display:none;"></table>
	</div>
</div>

<?php if($role == 'admin' || $role == 'ops') { ?>
<div id="campaign_tags_page_container">

	<div id="ct_header_content">
		<div id="ct_header_left">
			<a id="ct_campaigns_main_button" href="#" class="btn btn-success ct_header_button">
				<span class="ct_bulk_tags_button_icons">
					<i class="icon-arrow-left"></i>
				</span>
				Back to Campaigns Main
			</a>

			<a id="ct_tag_utility_button_2" href="#tag_utility_modal" data-toggle="modal" class="btn btn-info ct_tag_utility_button ct_header_button">
				<span class="ct_bulk_tags_button_icons">
					<i class="icon-cog"></i>
				</span>
				Tag Utility
			</a>
		</div>
		<div id="ct_header_right">


			<a id="ct_bulk_edit_tags_button" href="#bulk_campaign_tags_modal" data-toggle="modal" class="btn btn-primary ct_header_button">
				<span class="ct_bulk_tags_button_icons">
					<i class="icon-pencil"></i>
				</span> 
				Bulk Edit Tags
			</a>
		</div>
	</div>

	<div id="ct_body_content">
		<table class="table" id="ct_campaign_tags_table">
			
		</table>
	</div>
</div>

<?php } ?>

<!-- Modal -->

<!-- bulk download modal -->	
	<div id="bulk_download_modal" class="modal hide fade" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3 id="bulk_download_modal_header">Bulk Campaign Data</h3>
		</div>
		<div id="bulk_download_modal_body" class="modal-body"> 
			<div id="bulk_download_loader_content" class="bulk_download_content_div">
				<i class="icon-spinner icon-spin icon-4x"></i>
			</div>
			<div id="bulk_download_body_content" class="bulk_download_content_div">
				<div class="bd_inline_block">
					<div class="bd_form_element">
						<div><h4>Start Date</h4></div> 
						<input type="text" name="start_date" id="bulk_download_start_date" class="bulk_download_datepicker" data-date-format="mm/dd/yyyy">
					</div>
					<div class="bd_form_element" >
						<div><h4>End Date</h4></div> 
						<input type="text" name="end_date" id="bulk_download_end_date" class="bulk_download_datepicker" data-date-format="mm/dd/yyyy">
						
					</div>
				</div>
				
				<div>
					<hr style="margin:0px 0px 0px 0px;" />
					<div class="bd_inline_block">
						<div class="bd_form_element">
							<div>
								<h4>Partner</h4>
							</div>
							<select name="bd_partner_select"  id="bd_partner_select">
								<?php echo $bd_partner_options; ?>
							</select>
						</div>
						<div class="bd_form_element">
							<div>
								<h4>Account Executive</h4> 
							</div>
							<select name="bd_sales_person_select" id="bd_sales_person_select">
							</select>
						</div>
					</div>
					<div id="bd_ae_tooltip_div">
						<a href="#" id="bd_ae_tooltip" onclick="event.preventDefault();"> Why can&apos;t I find an AE? <i class="icon-question-sign"></i></a>
					</div>
				</div>
			</div>
			<div id="bulk_download_warning_content" class="bulk_download_content_div">
				It looks like you already have a bulk data request in progress. 
				<br>
				Give us a few minutes to collect your data before requesting a new one.
				
			</div>
		</div>
		<div id="bulk_download_modal_footer" class="modal-footer">
			<button type="button" data-dismiss="modal" class="btn">Close</button>
			<button disabled="disabled" id="bulk_download_init_button" type="button" class="btn btn-inverse"><i class="icon-envelope-alt icon-white"></i> Email</button>
		</div>
	</div>
	
<!-- empty modal -->
	<div id="detail_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="detail_modalLabel" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="modal_detail_header">&nbsp;</h3>
		</div>
		<div id="modal_detail_body" class="modal-body">
			<p>&nbsp;</p>
		</div>
	</div>

<!-- adset modal -->	
	<div id="adset_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="adset_modalLabel" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="adset_modal_detail_header">Modal header</h3>
		</div>
		<div id="adset_modal_detail_body" class="modal-body">
			<p></p>
		</div>
	    <div class="modal-footer">
			<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
	    </div>
	</div> 

	<div id="create_ticket_modal" class="modal hide fade big_modal" tabindex="-1">
	  <div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="myModalLabel"><i class="icon-ticket"></i> Create New Ticket</h3>
	  </div>
	  <div class="modal-body" style="overflow:hidden;">
	  	<div class="alert alert-error fade in">All fields are required.</div>
	    <form id="create_ticket_form" class="container">
	    	<div class="control-group">
		    	<input type="text" name="subject" id="ticket_subject" class="span6" required placeholder="Subject Line"/>
		    </div>
		    <div class="control-group">
		    	<textarea name="body" id="ticket_body" class="span8" rows="8" required placeholder="Type your message here..."></textarea>
		    </div>
	    	<input type="hidden" name="campaign_id" id="ticket_campaign_id" />
	    </form>
	  </div>
	  <div class="modal-footer">
	    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
	    <?php if($this->session->userdata('is_demo_partner') != 1){ ?>
	    <button class="btn btn-primary" id="create_ticket_button">Create Ticket</button>
	    <?php }else{ ?>
	    <button class="btn" style="cursor:default;outline:none; color: #A4A4A4">Create Ticket</button>
	    <?php } ?>
	  </div>
	</div>

<?php if($role == 'admin' || $role == 'ops') { ?>
<!-- adgroup modal -->	
    <div id="adgroup_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="adgroup_modalLabel" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="adgroup_modal_detail_header">Modal header</h3>
		</div>
		<div id="adgroup_modal_detail_body" class="modal-body" style="max-height:450px;">
			<h4>Campaign Details</h4> <span class="label label-success" style="position:relative;top:-10px;" id="c_timestamp_box">ASDASDASD</span>
			<div class="divider"></div>
			<form class="form-horizontal">
				<div class="control-group">
					<label class="control-label" for="impression_budget_box">Impression Budget</label>
					<div class="controls">
						<input type="text" id="impression_budget_box">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="dollar_budget_box">$ Budget</label>
					<div class="controls">
						<input type="text" id="dollar_budget_box">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="start_date_box">Start Date</label>
					<div class="controls">
						<input type="date" id="start_date_box">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="end_date_box">End Date <span class="tool_poppers"  data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="End Date Adjustment" data-content="This is going to adjust both the TTD and the VL End Date."> <i class="icon-info-sign "></i></span></label>
					<div class="controls">
						<input type="date" id="end_date_box">
					</div>
				</div>
			</form>
			<hr/>
			<div id="adgroups_options">
				<h4 id='m_header'>Managed Adgroup</h4> <span class="label label-success" id="m_timestamp_box">ASDASDASD</span>
				<form class="form-horizontal">
					<div class="control-group">
						<label class="control-label" for="m_is_enabled_checkbox">Enabled?</label>
						<div class="controls">
							<input type="checkbox" id="m_is_enabled_checkbox"/>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="m_impression_budget_box">Impression Budget</label>
						<div class="controls">
							<input type="text" id="m_impression_budget_box">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="m_dollar_budget_box">$ Budget</label>
						<div class="controls">
							<input type="text" id="m_dollar_budget_box">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="m_daily_impression_box">Daily Impression Budget</label>
						<div class="controls">
							<input type="text" id="m_daily_impression_box">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="m_daily_dollar_box">Daily $ Budget</label>
						<div class="controls">
							<input type="text" id="m_daily_dollar_box">
						</div>
					</div>
				</form>
				<h4 id="r_header">Retargeting Adgroup</h4> <span class="label label-success" id="r_timestamp_box">&nbsp;</span>
				<div class="divider"></div>
				<form class="form-horizontal">
					<div class="control-group">
						<label class="control-label" for="r_is_enabled_checkbox">Enabled?</label>
						<div class="controls">
							<input type="checkbox" id="r_is_enabled_checkbox"/>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="r_impression_budget_box">Impression Budget</label>
						<div class="controls">
							<input type="text" id="r_impression_budget_box">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="r_dollar_budget_box">$ Budget</label>
						<div class="controls">
							<input type="text" id="r_dollar_budget_box">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="r_daily_impression_box">Daily Impression Budget</label>
						<div class="controls">
							<input type="text" id="r_daily_impression_box">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="r_daily_dollar_box">Daily $ Budget</label>
						<div class="controls">
							<input type="text" id="r_daily_dollar_box">
						</div>
					</div>
			</div>
			</form>
		</div>
	    <div class="modal-footer" id="ttd_modal_footer">
			<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
			<button id="ttd_modify_button" disabled="disabled" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>
	    </div>
	</div>
    
<!-- confirm modal -->
    <div id="confirm_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="confirm_modalLabel" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="confirm_modal_detail_header">Confirm Action</h3>
		</div>
		<div id="confirm_modal_detail_body" class="modal-body">
			
		</div>
	    <div class="modal-footer">
			<a class="btn btn-danger" data-dismiss="modal" aria-hidden="true">No</a>
			<a class="btn btn-success" id="confirm_delete_button" onclick="" data-dismiss="modal" aria-hidden="true">Yes</a>
	    </div>
	</div> 

<!-- bulk archive modal -->
	<div id="bulk_archive_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="bulk_archive_modalLabel" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3>Archive Campaigns</h3>
		</div>
		<div class="modal-body">
			<b>
				Are you sure you want to archive the following Campaigns (yesterday impressions)?
			</b>
			<div id="bulk_archive_modal_campaigns">
			</div>
		</div>
		<div class="modal-footer">
			<a id="bulk_archive_cancel_button" class="btn btn-danger" data-dismiss="modal" aria-hidden="true">Don't Archive</a>
			<a class="btn btn-success" id="confirm_bulk_archive_button" data-dismiss="modal" aria-hidden="true">Archive these Campaigns</a>
		</div>
	</div>
	
	<form id="bap_submit_form" action="/campaigns_main/get_prepopulated_bulk_adgroup_putter_data" method="post" target="_blank">
		<input id="bap_campaign_ids_input" type="hidden" name="selected_campaign_ids" value="">
	</form>

	<form id="bap_ts_submit_form" action="/campaigns_main/get_prepopulated_bulk_timeseries_putter_data" method="post" target="_blank">
		<input id="bap_ts_campaign_ids_input" type="hidden" name="selected_ts_campaign_ids" value="">
	</form>

<!-- bulk campaign tags modal -->
	<div id="bulk_campaign_tags_modal" class="modal hide fade" tabindex="-1">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3>Bulk Edit Campaign Tags</h3>
		</div>
		<div class="modal-body">
			<input type="hidden" style="width:100%;height:90px;" id="ct_bulk_edit_campaign_tags_select2">
		</div>
		<div class="modal-footer">
			<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
			<!--<a class="btn btn-success" id="confirm_bulk_campaign_tag_button" data-dismiss="modal" aria-hidden="true">Save</a>-->
		</div>
	</div>

<!-- tag utility modal -->
	<div id="tag_utility_modal" class="modal hide fade" tabindex="-1">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal">×</button>
			<h3>Tag Utility</h3>
		</div>
		<div class="modal-body">
			<div id="tag_utility_loader_content">
				<i class="icon-spinner icon-spin icon-4x"></i>
			</div>
			<div id="tag_utility_body_content">
				<div id="ct_campaign_rows">
					<table id="campaign_tags_table" class="dataTable" cellspacing="0" role="grid">
						<thead>
							<tr>
								<th>ID</th>
								<th>Tag Name</th>
								<th>Unarchived Campaigns</th>
								<th>Archived Campaigns</th>
								<th>Created</th>
								<th>Created By</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<a class="btn" data-dismiss="modal">Close</a>
		</div>
	</div>

<?php } ?>



<img id="c_main_loading_img" src="/images/loadingImage.gif" alt="">

