<style>
	.tag_list_data
	{
		width: 250px;
		max-width: 250px;
		word-wrap:break-word;
	}
	.tag_list_data 
	{
	    font-size:13px !important;
	}
        #toast-container
        {
            min-width: 8%;
            right: 13%;
            top: 10%;
            display: block;
            position: fixed;
            z-index: 1001;
        }
	#add_ttd_controls
	{
		padding: 5px;
	}
</style>

<?php
function time_in_millisecond() {
	$microtime = microtime();
	$comps = explode(' ', $microtime);

	// Note: Using a string here to prevent loss of precision
	// in case of "overflow" (PHP converts it to a double)
	return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
}
?>

<script type="text/javascript">
	$(document).ready(function () {
		$('#initial_start_date_div').datetimepicker({pickTime: false, maskInput: true});
		$('#initial_end_date_div').datetimepicker({pickTime: false, maskInput: true});
		$('#pause_date_div').datetimepicker({pickTime: false, maskInput: true});
		$('[data-toggle="tooltip"]').tooltip();
		
	})	
	function check_action_date(me)
	{
		if (document.getElementById("campaign_select") == undefined)
			return;
		 var campaign_id = document.getElementById("campaign_select").value;
		
		 var data_url = "/campaigns_main/get_action_date_for_campaign";
			$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {cid: campaign_id},
			dataType: 'html',
			error: function(){
				document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
				return 'error';
			},
			success: function(msg){ 
				var returned_data = jQuery.parseJSON(msg);
				
				if (returned_data["action_date"] != undefined && returned_data["action_date"] != null && returned_data["action_date"].length > 0 )
				{
					$('#action_date_btn').show();
					$('#action_date_btn span').text(returned_data["action_date"]);
				}
				else {
					$('#action_date_btn').hide();
				}
			}
		});
	}

	function change_action_date_flag(me)
	{
		if (document.getElementById("campaign_select") == undefined)
			return;
		 var campaign_id = document.getElementById("campaign_select").value;
		 var series_date=$('#action_date_btn span').text();
		 if(confirm("Please confirm that this task is completed in Biddr and we can disable the Action Date?")) 
		 {
		 	$.ajax({
				type: "POST",
				url: '/campaigns_main/update_action_date_flag/',
				async: true,
				data: { campaign_id: campaign_id, series_date: series_date },
				dataType: 'json',
				error: function(xhr, textStatus, error)
				{
					show_tags_error('Error 49293577: failed to update');
				},
				success: function(msg)
				{
					$('#action_date_btn').hide();
				}
			});
	  	}
}

</script>

<div id="adgroup_modal" class="modal hide fade big_modal" tabindex="-1" role="dialog" aria-labelledby="adgroup_modalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="adgroup_modal_detail_header">Modal header</h3>
	</div>
	<div class="modal-footer" id="ttd_modal_footer">
		<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
		<button id="modify_button" type="submit" class="btn btn-info "><i class="icon-retweet icon-white"></i> Update</button>
	</div>
	<div id="adgroup_modal_detail_body" class="modal-body" style="max-height:575px;">
		<h4>Campaign Details</h4>
		<span class="label label-success" style="position:relative;top:-10px;" id="c_timestamp_box">ASDASDASD</span>
		<div class="divider"></div>
		<form class="form-horizontal">
		</form>
		<hr/>
		<h4 id='m_header'>Managed Adgroup</h4>
		<span class="label label-success" id="m_timestamp_box">ASDASDASD</span>
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

		<h4 id='r_header'>Retargeting Adgroup</h4>
		<span class="label label-success" id="r_timestamp_box">ASDASDASD</span>
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
		</form>
	</div>
		
	
</div>

<!-- Modal -->
<div id="edit-advertiser-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel"><i class="icon-pencil "></i> Edit Advertiser Name </h3>
	</div>
	<div class="modal-body">
		<h4><small>Change</small> <span id="existing_adv_name_span">EXISTING ADVERTISER NAME</span>  <small>to:</small></h4>
		<input type="text" placeholder="new name goes here" id="advertiser_rename" class="span5" ></input>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		<button class="btn btn-primary" onclick="rename_advertiser()">Save changes</button>
	</div>
</div>	


<!-- Modal -->
<div id="edit-campaign-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel"><i class="icon-pencil "></i> Edit Campaign Name </h3>
	</div>
	<div class="modal-body">
		<h4><small>Change</small> <span id="existing_campaign_name_span">EXISTING CAMPAIGN NAME</span>  <small>to:</small></h4>
		<input type="text" placeholder="new name goes here" id="campaign_rename" class="span5" ></input>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		<button class="btn btn-primary" onclick="rename_campaign()">Save changes</button>
	</div>
</div>	

<div id="trash-campaign-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel"><i class="icon-trash "></i> Trash Campaign </h3>
	</div>
	<div class="modal-body">
		<span id="trash_campaign_span">Are you sure you want to send to the trash advertiser?</span>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">No</button>
		<button class="btn btn-success" data-dismiss="modal" aria-hidden="true" onclick="trash_campaign()">Yes</button>
	</div>
</div>	


<div class="container">
	<h2>Setup <small> Advertiser :: Campaign</small></h2>
	<div id="loader_bar" class="row-fluid" style="height: 15px;"></div>
	<div class="row-fluid">

<!-- ADVERTISER -->
<!-- ADVERTISER -->
<!-- ADVERTISER -->
		<div class = "span6">
			<form class="form">
				<fieldset>
					<legend >Advertiser </legend> 
					<div id="load_advertiser_status" style="height: 25px;"> </div>	
	<!-- ADVERTISER NAME -->
					<label for="advertiser_select">Advertiser Name</label> 
					<div class="row-fluid">
						<input id="advertiser_select" onchange="handle_adv_change(this.value,null);" class="span8">
						<div class= "span4">
							<a class="link vl-clickable-icon" data-toggle="modal" href="#edit-advertiser-modal" style="visibility:hidden" id="edit_advertiser_icon"><i class="icon-pencil icon-large "></i> </a>
							 <i id="adv_refresh_icon" class="icon-refresh icon-spin icon-large" style="visibility: hidden"></i>
						 	<span id="adv_tooltip" class="tool_poppers" style="visibility:hidden;" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="" data-content="Add this advertiser as a new advertiser to bidder">
						 		<button type="button"  id="add_to_td_button" class="btn btn-small btn-info " onclick="add_to_tradedesk();" data-loading-text="Loading..." style="visibility:hidden;padding-left:10px">
						 			<i class="icon-retweet icon-white"></i> 
						 		</button>
						 	</span>
						 </div>
					</div>
					<div class="row-fluid">
						
					</div>

	<!-- NEW ADVERTISER NAME --> 
		   
					<label for="new_advertiser_input"></label>
					<span id="new_advertiser_input"  style="visibility:hidden">
						<input type="text" placeholder="Name new advertiser" id="new_advertiser_name" class="span8" ></input>
					</span>
					
	<!-- SALESPERSON -->
	
					<label for="sales_select">Sales Rep</label>
					<div class="row-fluid">
						<select id="sales_select" data-placeholder="select rep" onchange="update_create_advertiser();" class="span8">
							<option></option>
							<option value="none">Select Sales</option>
							<?php foreach($sales_people as $rep)
							{
								echo '<option value="' .$rep['id']. '">'.$rep['partner']." :: " .$rep['username']. '</option>';
							} ?>
						</select>
					</div>
					<div id="am_ops_owner_div" class="span8">
						<label for="am_ops_owner_select2">Ops Owner</label>
						<div class="row-fluid">
							<input type="hidden" style="width:100%;" id="am_ops_owner_select2">
						</div>
					</div>
				</fieldset>
			</form>
		</div><!-- span6 -->
<!-- CAMPAIGN -->
<!-- CAMPAIGN -->
<!-- CAMPAIGN -->
		<div id="campaign_section" class="span6" style="display:none">
			<form>
				<fieldset>
					<legend >Campaign<span class="help-inline pull-right" style="padding-right:20px">
							<button type="button"  id="campaign_load_button" class="btn btn-mini btn-warning" onclick="update_create_campaign(null);" data-loading-text="Loading..."><i class="icon-thumbs-up icon-white"></i> <span>Update</span></button>

							<div class="btn-group pull-right">
								<a class="btn btn-link dropdown-toggle" data-toggle="dropdown" href="#">
									<i id="refresh_cache_data_cog_icon" class="icon-cog icon-1"></i>
										<span class="caret"></span></a><ul class="dropdown-menu"><li>
										<a class="ai_charts ai_modal" href="#detail_modal" data-toggle="modal" onclick="refresh_cache_campaign_report()">
										<i id="refresh_cache_data_retweet_icon" class="icon-retweet icon-1"></i>&nbsp;Cache refresh</a></li></ul></div>

						</span></legend>
					<div id="load_campaign_status" style="height: 25px;"> </div>	
	<!-- CAMPAIGN NAME -->
					<label for="campaign_select">Campaign Name</label>

					<div class="row-fluid">
						<select id="campaign_select" class="span8" onchange=handle_ui_campaign_change(this.value) >	
						</select>

						<div id="valid_campaign_selected_controls" class= "span3" style="">
								<a class="link vl-clickable-icon" data-toggle="modal" href="#edit-campaign-modal" style="visibility:hidden" id="edit_campaign_icon"><i class="icon-pencil icon-large "></i> </a>
								<a class="link vl-clickable-icon" data-toggle="modal" href="#trash-campaign-modal" style="visibility:hidden;margin-left:10px;" id="trash_campaign_icon"><i class="icon-trash icon-large "></i> </a>								
								 <i id="campaign_refresh_icon" class="icon-refresh icon-spin icon-large" style="visibility: hidden"></i>								  
						</div>
						

					</div>

						<!-- NEW CAMPAIGN NAME -->	
						<span id="new_campaign_input" style="visibility:hidden">
							<input type="text" placeholder="Name new campaign" id="new_campaign_name" class="span8"></input>							
						</span> 
					<div style="display:flex; flex-direction:row;">
						<span class=""><input class="checkbox " name="is_campaign_archived" id="is_campaign_archived" type="checkbox"> <span id="is_archived_copy">Archived</span></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						 Pause:&nbsp; <div  id="pause_date_div"  class="input-append date"><input type="text" data-format="yyyy-MM-dd" class="input-small" id="pause_date" /> <span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar" class="icon-calendar"></i></span> </div> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<span class="" id="action_date_div"><button id="action_date_btn" type="button" onclick="change_action_date_flag(this)" class='btn btn-warning btn-mini'><span></span></button></span>
						<br>
					</div>
					<br>
	<!-- LANDING PAGE -->
					<div id="landing_page_group" class="control-group">
                                                <label for="campaign_landing_page_url">Landing Page URL</label>
                                                <div class="controls">
                                                        <input type="text" placeholder="Name landing page" id="campaign_landing_page_url" class="span12"></input>
                                                </div>
                                        </div>
                                        
						<span id="campaign_tooltip">
							<div id="add_ttd_controls">
								<input type='radio' id="is_display_campaign" value="0" name="video_campaign_type" onchange="handle_adverify_campaign_visibility(this.value);">&nbsp;Display Campaign</input>
								<input type='radio' id="is_pre_roll_campaign" value="1" name="video_campaign_type" onchange="handle_adverify_campaign_visibility(this.value);">&nbsp;Pre-Roll Campaign</input>
								<input type="hidden" id="enabled_campaign_type" value="0" />
							</div>
							<br><br>
							<input id="tag_file_select" onchange="handle_tag_file_change(this.value);"style="margin-left:0px;" class="span10"></input>
							<div id="new_tag_file_input"  style="display:none;top:5px;position:relative;clear:both;">
								<span id="tracking_file_prepend_string" style="color: #ad9e9e;font-size:11px;position:relative;top:-5px;"></span><input type="text" placeholder="Name new tag file" id="new_tag_file_name" class="span5" ></input><span id="tracking_file_append_string" style="color: #ad9e9e;font-size:11px;position: absolute;top: 4px;">.js</span>
								<br/>
								<button type="button"  id="add_new_tag_file_button" onclick="add_new_tag_file_for_advertiser();" class="btn btn-small btn-primary tool_poppers"  style="position:relative;top:-5px;" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Create New Tag File" data-content="Creates a new tag file for the selected advertiser.">
									<i class="icon-tags icon-white"></i> <span>Add Tag File</span>
								</button>
								<span id="new_tracking_tag_file_status" style="position: relative;top: -4px;margin-left: 20px;"></span>
							</div>
							<br><br>
						
							<button type="button" id="add_campaign_to_td_button" class="btn btn-small btn-info add_ttd_controls" onclick="add_campaign_to_tradedesk_click();" data-loading-text="Loading..." style="position:relative;top:-5px;" class="tool_poppers" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Bidder Campaign/Adgroup" data-content="Add this campaign to Bidder, then create the appropriate adgroups depending on which kind of campaign being added.">
								<i class="icon-retweet icon-white"></i> <span>Add to Bidder</span>
							</button>
						</span>

		<!-- Area for Tags -->		
			<div id="displayed_table">
					<table id="campaign_tags_table" class="table table-striped table-hover table-condensed cell-border">
						<thead>
							<tr>
							    <th style="width: 50px;">
									ID
								</th>								
								<th>
									Filename
								</th>								
							</tr>
						</thead>
						<tbody></tbody>
					</table>
					<br/>
				</div>			
						<div id="adgroup_tooltip">
							<span id="video_campaign_type">
							</span>
							<span id="tag_file_selected_error" class="label label-important" style="position: absolute;float: left;margin-top: 2px;margin-left:20px;display:none;">No TTD Tag Assigned</span>
							<span id="tag_warning_popover" class="tool_poppers" style="color:#DBBE48;display:none;" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Tag Warning" data-content="Selected tag file didn't facilitate simple tag creation/assignment. Please go to /tag to ensure everything is set up properly."><i class="icon-warning-sign"></i></span>
							<br><br>
							<button type="button"  id="modify_adgroups_button" class="btn btn-small btn-success tool_poppers" href="#adgroup_modal" data-toggle="modal" onclick="show_adgroup_modal();" style="position:relative;top:-5px;" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="Bidder Campaign/Adgroup" data-content="Open a form that allows you to modify Bidder adgroups for this campaign">
								<i class="icon-retweet icon-white"></i> <span>Edit Bidder Adgroups</span>
							</button>
							<a id="visit_ttd_button" class="btn btn-small btn-primary" style="position:relative;top:-5px;margin-left:5px;" target="_blank"><i class="icon-retweet icon-white"></i> View on bidder</a>
						</div>
						<br/><br/>
						<div id="view_on_bidder" style="display:none;">
							
							
						</div>
						
					 <!-- time series start -->
					<br><br>
					<legend></legend>

				 	<div id="time_series_div_parent" class="span12 well" style='background-color:#C4E4C4;font-size:12px'>
						<span style = "color:red;"><b>Don't edit flights and impressions here</b></span><br/>
				 		<span><b>Campaign Flights - Standard tool:	 </b></span><span id="total_impr_div_header"></span><br>
				 		<div id="tags_alert_div" class="span12"></div> 
				 		<b>&nbsp;&nbsp;&nbsp;&nbsp;Start Date
				 			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				 			End Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				 			Impressions</b><br>

						<div id="time_series_div" class="span12">
						</div> 
						<legend></legend>
						<b>Campaign Flights - Pro tool:	 </b>
						<div id="time_series_tt_div" class="span12">
							<b>&nbsp;&nbsp;Start Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Impressions</b>
							<textarea id="timeseries_pro" rows=10 cols="140" style="font-size: 12px; margin: 0px 0px 10px; width: 318px; height: 203px;background-color : #FBFCCA"></textarea>  
							<button data-toggle="tooltip" title='Tab seperated flights' type="button" class="btn btn-small btn-info" onclick="refresh_time_series_div_from_timeseries_pro();"><i class="icon-forward icon-white"></i> <span>Refresh</span></button>
							 
						</div>
					</div>
 					  <!-- time series end -->
					
 					<!-- site list -->
					<div id="site_list_section">
						<legend>Site List</legend>
						<div id="site_list_edit_container" class="well well-small" style="">
							<span style="margin-bottom: 2px;" class="label label-warning edit_pending" id="sitelist_edit_pending">[!] EDIT PENDING</span>						
							<span style="margin-bottom: 2px;" class="label label-info" id="current_sitelist_name"></span>
							<textarea id="site_list_edit" placeholder="Paste your sitelist here" style="font-size: 12px; margin: 0px 0px 10px; width: 97%; height: 203px;"></textarea>
							<br>
							<input type="text" id="site_list_name"  placeholder="Sitelist name" style="width:75%;"></input>
							<button type="button" onclick="save_site_list();" id="site_list_submit" class="btn btn-small btn-info ttd_only" data-toggle="popover" data-trigger="hover" data-placement="top" title data-original-title="Add Sitelist" data-content="Add this sitelist to the TTD advertiser and link that sitelist to all non-AV TTD adgroups for this campaign. (Sitelists from previous pushes using this utility will be replaced)" style="position:relative;top:-5px;width:110px;"><i class="icon-retweet icon-white"></i> Save Sitelist</button>
						</div>
					</div>

 					<!-- zip list -->
					<div id="zip_list_section">
						<legend>ZIP List</legend>
						<div id="zip_list_edit_container" class="well well-small" style="">
							<span style="margin-bottom: 2px;" class="label label-warning edit_pending" id="ziplist_edit_pending">[!] EDIT PENDING</span>
							<span style="margin-bottom: 2px;" class="label label-info" id="current_ziplist_submission_time"></span>												
							<textarea id="zip_list_edit" placeholder="Put your comma-separated zips here" style="font-size: 12px; margin: 0px 0px 10px; width: 97%; height: 203px;"></textarea>
							<br>
							<button type="button" onclick="save_zip_list();" id="zip_list_submit" class="btn btn-small btn-info ttd_only" data-toggle="popover" data-trigger="hover" data-placement="top" title data-original-title="Add Zips" data-content="Searches up and adds the TTD geosegments for given zips to non-AV TTD adgroups for this campaign. (Overwrites TTD geos currently in use by adgroups)" style="position:relative;top:-5px;width:110px;"><i class="icon-retweet icon-white"></i> Save ZIPs</button>
							<span id="bad_zip_list" style="position:relative;top:-5px;" class="label label-important"></span>
						</div>
					</div>

					<legend>Ad Verify Categories</legend>
					<div id="ad_verify_categories">
						<table>
							<tr>
								<?php
									$count = 0;
									foreach($categories as $category)
									{
										$count = $count+1;
										echo "<td><input class='checkbox' name='category_box' id='cat_".$category['ID']."' type='checkbox' > ".$category['Name']."</td>";
										if ($count == 3)
										{
											$count = 0;
											echo "</tr><tr>";   
										}
									}
								?>
							</tr>
						</table>
					</div>
					<br>
					<button type="button"  id="add_av_campaign_to_td_button" class="btn btn-small btn-info" onclick="add_av_campaign_to_tradedesk();" data-loading-text="Loading..." style="position:relative;top:-4px;visibility:hidden;"><i class="icon-retweet icon-white"></i> <span>Add Ad Verify campaign to Bidder</span></button>
					<span id="av_tooltip" class="tool_poppers" style="visibility:hidden;" data-toggle="popover" data-trigger="hover" data-placement="top" title data-original-title="Bidder Ad_Verify Campaign" data-content="Add an Ad_Verify campaign to Bidder and create/add an adgroup to it."><i class="icon-info-sign "></i></span>
					<input type="hidden" id="check_status_of_av_campaign_to_td_button" value="" />
					<button type="button"  id="add_av_campaign_to_td_button" class="btn btn-small btn-info" onclick="add_av_campaign_to_tradedesk();" data-loading-text="Loading..." style="position:relative;top:-4px;visibility:hidden;"><i class="icon-retweet icon-white"></i> <span>Add Ad Verify campaign to Bidder</span></button>
					<span id="av_tooltip" class="tool_poppers" style="visibility:hidden;" data-toggle="popover" data-trigger="hover" data-placement="top" title data-original-title="Bidder Ad_Verify Campaign" data-content="Add an Ad_Verify campaign to Bidder and create/add an adgroup to it."><i class="icon-info-sign "></i></span>

	<!-- UPDATE BUTTON -->
					
				</fieldset><!-- campaign -->
			</form>
		</div>
<!-- NOTES SECTION STARTS-->
<div id="notes_tt_div" class="span12 well" style="display:none">
	<div class="span9">Notes:<br>
	<textarea  id="new_notes" rows="50" cols="150" style="font-size: 10px; margin: 0px 0px 10px; width: 698px; height: 350px;">Code:: ^^
Flight:: ^^
IO::^^
BI:: ^^
MPQ:: ^^
URL:: ^^
Ad ID:: ^^
Geo:: ^^
Pop:: ^^
Demo:: ^^
Context:: ^^
Budget $:: ^^
Budget IMP:: ^^
Cal:: ^^
Note:: ^^
</textarea> </div>
<div class="span3">
	<span><button type="button" class="btn btn-small btn-info" onclick="add_new_note(1);"><span>Add Note to Campaign</span></button><br><br>
	<!--<button type="button" class="btn btn-small btn-info" onclick="add_new_note(2);"><span>Add Note to Advertiser</span></button><br><br>-->
	<button type="button" class="btn btn-small btn-warning" onclick="reset_notes_textarea();"><span>Reset</span></button></span><br>
	<span id="alert_notes_span" style='background-color:#FAFA7D;font-style:bold'></span>
	<br><br><span style="font-size:10px"><br> * Please type only in between :: and ^^. <br>Dont remove the last blank line. <br>Dont remove any row.<br>
	Existing Notes
	</span>
        <br><br><span><button type="button" class="btn btn-small btn-info" onclick="generate_campaign_notes();"><span>Generate Campaign Notes</span></button></span><br><br>
</div>
	
	 
</div>
<!-- NOTES SECTION ENDS-->
	</div>
</div>

<div id="notes_table_div" ></div>


<script src="/bootstrap/assets/js/bootstrap-transition.js"></script>
<script src="/bootstrap/assets/js/bootstrap-alert.js"></script>
<script src="/bootstrap/assets/js/bootstrap-modal.js"></script>
<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
<script src="/bootstrap/assets/js/bootstrap-scrollspy.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tab.js"></script>
<script src="/bootstrap/assets/js/bootstrap-tooltip.js"></script>
<script src="/bootstrap/assets/js/bootstrap-popover.js"></script>
<script src="/bootstrap/assets/js/bootstrap-button.js"></script>
<script src="/bootstrap/assets/js/bootstrap-collapse.js"></script>
<script src="/bootstrap/assets/js/bootstrap-carousel.js"></script>
<script src="/bootstrap/assets/js/bootstrap-typeahead.js"></script>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>
<script src="/js/campaign_health/notes.js"></script>
<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-datetimepicker.min.css" />
<script src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript">
	g_new_files = {};	
	var sitelist_update_pending = false;
	var ziplist_update_pending = false;

	var is_iframe = <?php echo $is_iframe;?>;

	function handle_adv_change(adv_id, c_id)
	{
		document.getElementById("edit_advertiser_icon").style.visibility = "hidden";
		clear_campaign_inputs();
		show_new_advertiser_input_box(false);
		show_new_campaign_input_box(false);
		set_advertiser_status(null);
		set_campaign_status(null);
		$("#s2id_advertiser_select > .select2-choice").css('height', 'auto');
		if (adv_id == "new"){//if new advertiser was selected
			show_campaign_section("hidden");
			document.getElementById("add_to_td_button").style.visibility = "hidden";
			document.getElementById("adv_tooltip").style.visibility = "hidden";
			show_new_advertiser_input_box(true); 
			clear_campaign_inputs();
			$("#s2id_advertiser_select > .select2-choice").css('height', "");
			document.getElementById("campaign_select").value =  '';
			fetch_notes_for_campaign('new');
		}else if(adv_id == "none"){//if no valid advertiser was selected
			show_campaign_section("hidden");
			document.getElementById("add_to_td_button").style.visibility = "hidden";
			document.getElementById("adv_tooltip").style.visibility = "hidden";
			document.getElementById("sales_select").value="none";
			$('#sales_select').select2({});
			set_campaign_status(null);
			document.getElementById("campaign_select").value =  '';
			clear_campaign_inputs();
			fetch_notes_for_campaign('new');
		}else{//if valid advertiser was selected
			show_new_advertiser_input_box(false);
			toggle_loader_bar(true,0.5,'prefill_campaign_inputs_after_adv_select');
			update_sales_dropdown(adv_id);
			document.getElementById("edit_advertiser_icon").style.visibility = "visible";
			refresh_campaign_dropdown(adv_id,c_id,false); //
			toggle_loader_bar(false,null,'prefill_campaign_inputs_after_adv_select');
			document.getElementById("add_to_td_button").style.visibility = "hidden";
			document.getElementById("adv_tooltip").style.visibility = "hidden";
			set_advertiser_status('success',"Advertiser selected: "+adv_id);
			td_advertiser = check_tradedesk();
			$("#existing_adv_name_span").text($('#advertiser_select').select2('data').text);
			$("#advertiser_rename").val($('#advertiser_select').select2('data').text);
			document.getElementById("campaign_select").value =  '';
			fetch_notes_for_campaign(2);
		}
	}

	function handle_ui_campaign_change(c_id)
	{
	    set_campaign_status(null);
	    handle_campaign_change(c_id,false);
	    make_campaign_tag_table_show(c_id);
	}

	function make_campaign_tag_table_show(campaigns_id)
	{  
	    var advertiser_name = $("#advertiser_select").select2('data').text;	    
	    var adv_name_clean = advertiser_name.replace(/[^\w]/gi, '')
		if(campaigns_id == 'new')
		{		        
			document.getElementById("displayed_table").style.display = 'none';
		} 
		else
		{
		    var data_url = "/campaign_setup/campaign_tag_file/";		
			$.ajax({
				type: "POST",
				url: data_url,
				async: true,
				data: { cam_id: campaigns_id ,adv_name: adv_name_clean},
				dataType: 'html',
				error: function(){ 
					set_formload_status('important', 'error: no tag files');
					document.getElementById("displayed_table").style.display = 'none';
				},
				success: function(msg){  
					if ( !$.fn.DataTable.isDataTable( '#campaign_tags_table' ) )
					{ 						
						$('#campaign_tags_table > tbody').prepend(msg);
						$('#campaign_tags_table').DataTable({'sDom': 't'}).order( [ 0, 'desc' ] ).draw();						
					}
					else
					{ 						
						$('#campaign_tags_table').dataTable().fnDestroy();
						$('#campaign_tags_table > tbody').html("").append(msg);
						$('#campaign_tags_table').DataTable({'sDom': 't'}).order( [ 0, 'desc' ] ).draw();						
					}
				}
			});
		    }
	}

	function set_formload_status(label, copy)
	 {   
		    if(label === null)
		    {
			    document.getElementById("load_campaign_status").innerHTML ='';
		    }else
		    {
			    document.getElementById("load_campaign_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		    } 
	}
	
	function handle_campaign_change(c_id,is_clone_mode)
	{
		clear_campaign_inputs();
		show_campaign_section("hidden");

		show_new_campaign_input_box(false);
		
		if (c_id == "new")
		{
			document.getElementById("campaign_tooltip").style.display = 'none';			
			document.getElementById("adgroup_tooltip").style.display = 'none';
			$(".edit_pending").hide();
			$("#site_list_edit_container").css('background-color', "");
			$("#zip_list_edit_container").css('background-color', "");
			$("#site_list_edit").prop('disabled', true);
			$("#site_list_name").prop('disabled', true);
			$("#zip_list_edit").prop('disabled', true);
			disable_bidder_buttons();
			$("#tag_warning_popover").hide();
			document.getElementById("edit_campaign_icon").style.visibility = "hidden";
			document.getElementById("trash_campaign_icon").style.visibility = "hidden";			
			document.getElementById("is_campaign_archived").checked = false;
			show_new_campaign_input_box(true); 
			blank_category_boxes();
			show_campaign_section("visible");
			populate_default_timeseries_wizard();// reset time series for a new campaign 
			document.getElementById("displayed_table").style.display = 'none';	
			
			fetch_notes_for_campaign('new_cam');
		}
		else
		{
			toggle_loader_bar(true,0.1,'prefill_campaign_inputs_campaign_change');
			prefill_campaign_inputs(c_id, is_clone_mode, function(){document.getElementById("edit_campaign_icon").style.visibility = "visible"; document.getElementById("trash_campaign_icon").style.visibility = "visible";});
			set_campaign_status('success',"Campaign selected: "+c_id,c_id);
			$("#existing_campaign_name_span").text($('#campaign_select').select2('data').text);
			$("#trash_campaign_span").html("Are you sure you want to send <strong>"+$('#campaign_select').select2('data').text+"</strong> to the trash advertiser?");
			$("#campaign_rename").val($('#campaign_select').select2('data').text);
			document.getElementById("displayed_table").style.display = 'block';			
		}
	}

	function set_advertiser_status(label, copy)
	{   
		if(label === null)
		{
			document.getElementById("load_advertiser_status").innerHTML ='';
		}else
		{
			document.getElementById("load_advertiser_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
	}

	function set_campaign_status(label, copy, campaign_id)
	{   
		if(label === null)
		{
			document.getElementById("load_campaign_status").innerHTML ='';
		}else
		{			
			document.getElementById("load_campaign_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';			
			if(typeof campaign_id != 'undefined')
			{
				$("#edit_flights_link").remove();
				$("#campaign_load_button").before('<span id="edit_flights_link" style="font-size:12px;margin-right:10px;"><a href="/campaign_setup/edit_flights/'+campaign_id+'" target="_blank">Edit Flights</a></span>');
			}
		} 
	}

	function show_adgroup_modal()
	{
		var advertiser_name = $("#advertiser_select").select2('data').text;
		var campaign_name = document.getElementById("campaign_select").options[document.getElementById("campaign_select").selectedIndex].text;
		var campaign_id = document.getElementById("campaign_select").value;
		
		document.getElementById('adgroup_modal_detail_header').innerHTML = advertiser_name+' - '+campaign_name+" Adgroups";
		
		document.getElementById('adgroup_modal_detail_body').innerHTML = '<div class="progress progress-striped active"> <div class="bar" style="width: 100%;"></div></div>';
		document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" type="submit" class="btn btn-info disabled"><i class="icon-retweet icon-white"></i> Update</button>';

		var data_url = "/tradedesk/get_adgroup_data/"+campaign_id;
			$.ajax({
			type: "GET",
			url: data_url,
			async: true,
			data: {},
			dataType: 'html',
			error: function(){
				document.getElementById('adgroup_modal_detail_body').innerHTML = temp;
				return 'error';
			},
			success: function(msg){ 
				var returned_data = jQuery.parseJSON(msg)
				if(returned_data.success)
				{	
					adgroups = returned_data.adgroup_ids;
					document.getElementById('adgroup_modal_detail_body').innerHTML = returned_data.form_html;
					document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" onclick="big_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';
					
				}
				else
				{
					$("#adgroup_modal").modal("hide");
					alert(returned_data.err_msg);
				}
			}
		});
	}


	function big_form_update(campaign_id)
	{
		var c_impression_budget = document.getElementById('impression_budget_box').value;
		var c_dollar_budget = document.getElementById('dollar_budget_box').value;
		var c_start_date = document.getElementById('start_date_box').value;
		var c_end_date = document.getElementById('end_date_box').value;
		
		var nan_msg = "The following are invalid, non-number inputs:\n";
		var nan_flag = false;
		
		var zero_msg = "The following must be non-blank, non-zero inputs:\n";
		var zero_flag = false;
		
		if(isNaN(c_impression_budget))
		{
		nan_msg += "Campaign Impressions\n";
		nan_flag = true;
		}
		if(isNaN(c_dollar_budget))
		{
		nan_msg += "Campaign $ Budget\n";
		nan_flag = true;
		}
		
		var adgroup_data = {};
		
		
		
		for(var i = 0; i < adgroups.length; i++)
		{
			adgroup_data[adgroups[i]] = {};
			adgroup_data[adgroups[i]]['is_enabled'] = document.getElementById(adgroups[i]+'_is_enabled_checkbox').checked;
			adgroup_data[adgroups[i]]['impression_budget'] = document.getElementById(adgroups[i]+'_impression_budget_box').value;
			adgroup_data[adgroups[i]]['dollar_budget'] = document.getElementById(adgroups[i]+'_dollar_budget_box').value;
			adgroup_data[adgroups[i]]['daily_impressions'] = document.getElementById(adgroups[i]+'_daily_impression_box').value;
			adgroup_data[adgroups[i]]['daily_dollars'] = document.getElementById(adgroups[i]+'_daily_dollar_box').value;
			
			if(isNaN(adgroup_data[adgroups[i]]['impression_budget']))
			{
			nan_msg += "Adgroup "+adgroups[i]+" Impression Budget\n";
			nan_flag = true;
			}
			if(isNaN(adgroup_data[adgroups[i]]['dollar_budget']))
			{
			nan_msg += "Adgroup "+adgroups[i]+" $ Budget\n";
			nan_flag = true;
			}
			if(isNaN(adgroup_data[adgroups[i]]['daily_impressions']))
			{
			nan_msg += "Adgroup "+adgroups[i]+" Daily Impressions\n";
			nan_flag = true;
			}
			if(isNaN(adgroup_data[adgroups[i]]['daily_dollars']))
			{
			nan_msg += "Adgroup "+adgroups[i]+" Daily $ Budget\n";
			nan_flag = true;
			}
			
			if(is_zero(adgroup_data[adgroups[i]]['impression_budget']))
			{
			zero_msg += "Adgroup "+adgroups[i]+" Impression Budget\n";
			zero_flag = true;
			}
			if(is_zero(adgroup_data[adgroups[i]]['dollar_budget']))
			{
			zero_msg += "Adgroup "+adgroups[i]+" $ Budget\n";
			zero_flag = true;
			}
			if(is_zero(adgroup_data[adgroups[i]]['daily_impressions']))
			{
			zero_msg += "Adgroup "+adgroups[i]+" Daily Impressions\n";
			zero_flag = true;
			}
			if(is_zero(adgroup_data[adgroups[i]]['daily_dollars']))
			{
			zero_msg += "Adgroup "+adgroups[i]+" Daily $ Budget\n";
			zero_flag = true;
			} 
		}

		
		if(nan_flag)
		{
			alert(nan_msg);
			return;
		}
		if(zero_flag)
		{
			alert(zero_msg);
			return; 
		}

	   if(c_impression_budget == "")
	   {
		   document.getElementById('impression_budget_box').value = "0";
	   }
	   if(c_dollar_budget == "")
	   {
		   document.getElementById('dollar_budget_box').value = "0";
	   }
	   
	 
		document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" type="submit" class="btn btn-info disabled">Updating...</button>';

		var data_url = "/tradedesk/update_campaign_from_big_form/";
			$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {c_id: campaign_id, c_imps: c_impression_budget, c_bux: c_dollar_budget, c_start: c_start_date, c_end: c_end_date, adgroup_data: JSON.stringify(adgroup_data)},
			dataType: 'html',
			error: function(){
				
				return 'error';
			},
			success: function(msg){
				var returned_data = jQuery.parseJSON(msg);
				if(returned_data.success)
				{
					document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" onclick="big_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';
					
					document.getElementById('c_timestamp_box').className = "label label-warning";
					document.getElementById('c_timestamp_box').innerHTML = "Changes Pending: Just updated";
					
					for(var i = 0; i < adgroups.length; i++)
					{
						document.getElementById(adgroups[i]+'_timestamp_box').className = "label label-warning";
						document.getElementById(adgroups[i]+'_timestamp_box').innerHTML = "Changes Pending: Just updated";
					}
				}
				else
				{
					alert(returned_data.err_msg);
					document.getElementById('ttd_modal_footer').innerHTML = '<a class="btn" data-dismiss="modal" aria-hidden="true">Close</a><button id="modify_button" onclick="big_form_update('+campaign_id+')" type="submit" class="btn btn-info"><i class="icon-retweet icon-white"></i> Update</button>';

				}
			}
		});
	   
	}

	function check_tradedesk()
	{
		var advertiser_name = $("#advertiser_select").select2('data').text;
		if (advertiser_name == "Select Advertiser")
		{
			return 1;
		}
		var advertiser_id = $("#advertiser_select").select2('data').id;
		var data_url = "/tradedesk/check_advertiser/";
		 $.ajax({
				type: "GET",
				url: data_url,
				async: false,
				data: { adv_name: advertiser_id},
				dataType: 'html',
				error: function(){

					set_advertiser_status('important', "error 684821: Couldn't find user on bidder");
				},
				success: function(msg){
						var returned_data_array = eval('(' +msg+')' );
						if(returned_data_array.success)
						{
				if(returned_data_array.exists == true)
				{
					document.getElementById("add_to_td_button").style.visibility = "hidden";
					document.getElementById("adv_tooltip").style.visibility = "hidden";
					return "YES";
				}
				else
				{
					document.getElementById("add_to_td_button").style.visibility = "visible";
					document.getElementById("adv_tooltip").style.visibility = "visible";
					return "NO";
				}
						}else{
							alert('there was an error: 5er615');
						}
						toggle_loader_bar(false,null,'update_sales_dropdown'); 
			
					}
				});
	}

	function is_zero(input)
	{
		return (input == 0)||(input == "");
	}

	function check_tradedesk_campaign(c_id)
	{
		if(c_id == null || c_id == 'new')
		{
			document.getElementById("campaign_tooltip").style.display = 'none';
			document.getElementById("adgroup_tooltip").style.display = 'none';
			disable_bidder_buttons();
			return;
		}
		if(document.getElementById("add_to_td_button").style.visibility == "hidden")
		{
			var data_url = "/tradedesk/check_campaign/";
			$.ajax({
				type: "GET",
				url: data_url,
				async: false,
				data: { c_id: c_id},
				dataType: 'json',
				error: function(){
					set_campaign_status('important', "error 6821112: Couldn't find campaign "+c_id+" on bidder");
				},
				success: function(returned_data_array){
					if(returned_data_array.success)
					{
						if(returned_data_array.exists != false)
						{
							document.getElementById("campaign_tooltip").style.display = 'none';
							$("#visit_ttd_button").attr("href", "https://desk.thetradedesk.com/campaigns/detail/"+returned_data_array.ttd_cmp_id);
							//Show Adgroup button
							if(returned_data_array.adgroups == true)
							{
								document.getElementById("adgroup_tooltip").style.display = 'inline';
								enable_bidder_buttons();
								$("#video_campaign_type").removeClass();
								var check_video_campaign_type = 0;
								if(returned_data_array.video_campaign_type != false)
								{
									//set bar
									$("#video_campaign_type").addClass("label label-success");
									$("#video_campaign_type").html(returned_data_array.video_campaign_type + " Campaign");
									check_video_campaign_type = (returned_data_array.video_campaign_type == 'Pre-Roll') ? 1 : 0;
									handle_adverify_campaign_visibility(check_video_campaign_type);
								}
								else
								{
									$("#video_campaign_type").addClass("label label-warning");
									$("#video_campaign_type").html("Could not identify campaign type.");							
								}
							}
							
							return "YES";
						}
						else
						{
							document.getElementById("campaign_tooltip").style.display = 'inline';							
							document.getElementById("adgroup_tooltip").style.display = 'none';
							$("#visit_ttd_button").attr("src", "javscript:void(0);");
							disable_bidder_buttons();
							
							return "NO";
						}
					}else{
						alert('there was an error: 5er615');
					}
					toggle_loader_bar(false,null,'update_sales_dropdown'); 
				}
			});
		}
		else
		{
			document.getElementById("campaign_tooltip").style.display = 'none';
			document.getElementById("adgroup_tooltip").style.display = 'none';			
		}
	}


	function check_tradedesk_av_campaign(c_id)
	{
		
		if(c_id == null || c_id == 'new')
		{
			return;
		}
		if(document.getElementById("add_to_td_button").style.visibility == "hidden")
		{
		 var data_url = "/tradedesk/check_av_campaign/";
		 $.ajax({
					type: "GET",
					url: data_url,
					async: false,
					data: { c_id: c_id},
					dataType: 'html',
					error: function(){
						set_advertiser_status('important', "error 684212: Couldn't find campaign on bidder");
					},
					success: function(msg){
						var returned_data_array = eval('(' +msg+')' );
						if(returned_data_array.success)
						{
				if(returned_data_array.exists == true)
				{
					document.getElementById("add_av_campaign_to_td_button").style.visibility = "hidden";
					document.getElementById("check_status_of_av_campaign_to_td_button").value = 1;
					document.getElementById("av_tooltip").style.visibility = "hidden";
					return "YES";
				}
				else
				{
					document.getElementById("add_av_campaign_to_td_button").style.visibility = "visible";
					document.getElementById("check_status_of_av_campaign_to_td_button").value = 0;
					document.getElementById("av_tooltip").style.visibility = "visible";
					return "NO";
				}
						}else{
							alert('there was an error: 5er615');
						}
						toggle_loader_bar(false,null,'update_sales_dropdown'); 
			
					}
				});
		}
		else
		{
		document.getElementById("add_av_campaign_to_td_button").style.visibility = "hidden";
		document.getElementById("av_tooltip").style.visibility = "hidden";
		}
	}


	function add_to_tradedesk()
	{
		
		var advertiser_name = $("#advertiser_select").select2('data').text;
		var adv_id = $('#advertiser_select').select2('data').id;
		var data_url = "/tradedesk/add_advertiser/";
		toggle_loader_bar(true,0.5,'add_to_tradedesk');
		show_campaign_section("hidden");
		 $.ajax({
					type: "POST",
					url: data_url,
					async: true,
					data: { adv_name: advertiser_name, adv_id: adv_id},
					dataType: 'html',
					error: function(){

						set_advertiser_status('important', "error 684821: Couldn't add user to bidder");
					},
					success: function(msg){
						var returned_data_array = eval('(' +msg+')' );
						if(returned_data_array.success)
						{
							document.getElementById("add_to_td_button").style.visibility = "hidden";
							document.getElementById("adv_tooltip").style.visibility = "hidden";
							set_advertiser_status('success', "Advertiser "+adv_id+" added to bidder");
							var sub_id_text = $("#s2id_advertiser_select > .select2-choice > .select2-chosen > div > span").html();
							$("#s2id_advertiser_select > .select2-choice > .select2-chosen > div > span").html(sub_id_text + ' | ttdid: '+returned_data_array.td_id);
							refresh_campaign_dropdown($("#advertiser_select").select2('data'),null,false);
						}
						else
						{
							alert('there was an error: 5er615');
						}
						
					}
				});
	}

	function get_timeseries_data_for_ttd()
	{
		var impressions=0;
		var start_date=(new Date(time_series_data_array[0]['start_date'])).date_timeseries_format_yyyymmdd();
		var end_date=0;
		for (var i=0; i < time_series_data_array.length; i++)
		{
			if (end_date == 0 && time_series_data_array[i]['impressions'] == "0" )
			{
				var end_date_derived = new Date(time_series_data_array[i]['start_date']);
				end_date_derived.setDate(end_date_derived.getDate()-1);
				end_date=end_date_derived.date_timeseries_format_yyyymmdd();
			}
			var temp_impressions=clean_format_impressions(time_series_data_array[i]['impressions']);
			if (time_series_data_array[i]['impressions'] != undefined && parseInt(temp_impressions) > 0)
				impressions+= ((parseInt(temp_impressions))/1000);
		}
		var timeseries_data = new Array();
		timeseries_data['start_date']=start_date;
		timeseries_data['hard_end_date']=end_date;
		timeseries_data['target_budget']=impressions;

		return timeseries_data;
	}
	function add_campaign_to_tradedesk_click()
	{
		var error_message = '';
		var is_pre_roll = $('input[name="video_campaign_type"]:checked').val();
		document.getElementById("add_ttd_controls").style.border = '1px solid #ccc';
		if(is_pre_roll !== "1" && is_pre_roll !== "0")
		{
			error_message = error_message+'<li>Please select a campaign type</li>';
			document.getElementById("add_ttd_controls").style.border = '1px solid #b94a48';
		}
		if(error_message != '')
		{
			Materialize.toast('<ul>'+error_message+'</ul>', 20000, 'toast_top');
			return false;
		}
		update_create_campaign(null, null, true);
	}
	function add_campaign_to_tradedesk()
	{
		$("#tag_file_selected_error").hide();
		var is_pre_roll = $('input[name="video_campaign_type"]:checked').val();
		var advertiser_id = $("#advertiser_select").select2('data').id;
		var campaign_id = document.getElementById("campaign_select").value;
		var campaign_name = document.getElementById("campaign_select").options[document.getElementById("campaign_select").selectedIndex].text;
		var tag_file_id = ($("#tag_file_select").select2('data') != null 
						&& $("#tag_file_select").select2('data').id != 'new_tracking_tag_file'
						&& $("#tag_file_select").select2('data').id != '') ? $("#tag_file_select").select2('data').id : 0;
		var is_new_tag_file = (typeof g_new_files[""+tag_file_id] != 'undefined') ? g_new_files[""+tag_file_id] : 0;
		
		if(tag_file_id == 0)
		{
			var ok_to_proceed = confirm("No Tag File was selected for RTG/CONV. Do you want to create a campaign without assigning a Tag File?");
			if (!ok_to_proceed)
			{
				return;
			}
		}

		var timeseries_data = get_timeseries_data_for_ttd();
		var target_impressions = timeseries_data["target_budget"];//document.getElementById("campaign_target_impressions").value;
		var target_budget = timeseries_data["target_budget"];//target_impressions * 12 * 1.250;
		var hard_end_date = timeseries_data["hard_end_date"];//document.getElementById("campaign_target_end_date").value;
		var start_date = timeseries_data["start_date"];//document.getElementById("campaign_target_start_date").value;
		
		var term_type = 'FIXED_TERM';//(document.getElementById('in_total_radio').checked == true) ? 'FIXED_TERM' : $('input[name="month_reset_choice"]:checked').val();		
		var data_url = "/tradedesk/add_campaign/";
		toggle_loader_bar(true,0.5,'add_to_tradedesk');
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {adv_id: advertiser_id, c_name: campaign_name, c_id: campaign_id, ti_budget: target_impressions, e_date: hard_end_date, term_type: term_type, start_date: start_date, is_pre_roll: is_pre_roll, tag_file_id: tag_file_id, is_new_tag_file: is_new_tag_file},
			dataType: 'json',
			error: function(){
				set_campaign_status('important', "error 684821: Couldn't add campaign to bidder");
				$("#add_campaign_to_td_button").removeClass('disabled');
				$("#add_campaign_to_td_button").prop('disabled', false);				
			},
			success: function(msg){
				var returned_data_array = msg;
				if(returned_data_array.success)
				{
					document.getElementById("campaign_tooltip").style.display = 'none';
					$("#video_campaign_type").removeClass();
					$("#video_campaign_type").addClass("label label-success");
					$("#visit_ttd_button").attr("href", "https://desk.thetradedesk.com/campaigns/detail/"+returned_data_array.id);

					if(is_pre_roll == "1")
					{
						$("#video_campaign_type").html("Pre-Roll Campaign");
					}
					else
					{
						$("#video_campaign_type").html("Display Campaign");	
					}
					document.getElementById("adgroup_tooltip").style.display = 'inline';
					$("#current_sitelist_name").html("");
					$("#current_ziplist_submission_time").html("");
					$("#bad_zip_list").html("");
					update_site_list_name();
					enable_bidder_buttons();

					if(returned_data_array.tag_pushed == true)
					{
						//Just in case
						$("#tag_warning_popover").hide();
					}
					else if(returned_data_array.tag_pushed == -1)
					{
						//Tag push wouldn't fly. Notify.
						$("#tag_warning_popover").show();
						alert("Selected tag file requires changes to facilitate push/assignment. Head to /tag to make changes to the tag file.")
					}
					set_campaign_status('success', 'Added campaign '+campaign_id+" to bidder");
					
					if (tag_file_id == 0)
					{
						$("#tag_file_selected_error").css("display","inline").show();
					}
					
					
					var tradedesk_url = "<?php echo TRADEDESK_WEB_APP_URL; ?>";
					var success_html = "";
					if (typeof msg.ttd_adgroup_ids !== "undefined" && msg.ttd_adgroup_ids.length > 0)
					{
						success_html = success_html + '<span style="color: #b94a48;font-weight: 700;text-decoration: underline;">ACTION REQUIRED:</span>';
						success_html = success_html + "<br/><span style='color:#333333;font-size:12px;'>Assign RTG in Bidder - ";
						for(var i=0;i<msg.ttd_adgroup_ids.length;i++)
						{
							success_html = success_html + "<a target='_blank' href='"+tradedesk_url+"/adgroups/detail/"+msg.ttd_adgroup_ids[i]+"'>"+msg.ttd_adgroup_ids[i]+"</a>, ";
						}
						success_html = success_html.replace(/,\s*$/, "") + "</span>";
					}
					
					if (typeof msg.ttd_campaign_id != 'undefined' && msg.ttd_campaign_id != '')
					{
						success_html = success_html +  "<br/><span style='color: #333;font-size:12px;'>Assign CONV in Bidder - <a target='_blank' href='"+tradedesk_url+"/campaigns/detail/"+msg.ttd_campaign_id+"#settings'>"+msg.ttd_campaign_id+"</a></span><br/>";
					}
					
					if (success_html != "" && (typeof g_new_files[""+tag_file_id] == 'undefined' || g_new_files[""+tag_file_id] == 0) )
					{
						$("#view_on_bidder").html(success_html).show();
					}else{
						$("#view_on_bidder").hide();
					}
					
					if (tag_file_id != 0){
						g_new_files[""+tag_file_id] = 0;
					}
					
					$("#add_campaign_to_td_button").removeClass('disabled');
					$("#add_campaign_to_td_button").prop('disabled', false);					

				}
				else
				{
					set_campaign_status('important', "FAILED: " + returned_data_array.err_msg);
					$("#add_campaign_to_td_button").removeClass('disabled');
					$("#add_campaign_to_td_button").prop('disabled', false);					
				}
				toggle_loader_bar(false,null,'add_to_tradedesk');
				make_campaign_tag_table_show(campaign_id);
			}
		});
	}

	function update_site_list_name()
	{
		$("#site_list_name").val(($("#advertiser_select").select2('data').text + " - " + $("#campaign_select").select2('data').text + " - " + $("#campaign_select").val() + " - " + get_todays_date_string()));
	}

	function add_av_campaign_to_tradedesk()
	{
		var timeseries_data = get_timeseries_data_for_ttd();
		var advertiser_id = $("#advertiser_select").select2('data').id;
		var campaign_id = document.getElementById("campaign_select").value;
		var campaign_name = document.getElementById("campaign_select").options[document.getElementById("campaign_select").selectedIndex].text;
		var start_date = timeseries_data["start_date"];//document.getElementById("campaign_target_start_date").value;

		var data_url = "/tradedesk/add_av_campaign/";
		toggle_loader_bar(true,0.5,'add_to_tradedesk');
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {adv_id: advertiser_id, c_name: campaign_name, c_id: campaign_id, c_start: start_date},
			dataType: 'html',
			error: function(){

				set_campaign_status('important', "error 684821: Couldn't add ad_verify_campaign to bidder");
			},
			success: function(msg){
				var returned_data_array = eval('(' +msg+')' );
				if(returned_data_array.success)
				{
					document.getElementById("add_av_campaign_to_td_button").style.visibility = "hidden";
					document.getElementById("av_tooltip").style.visibility = "hidden";
				}else{
					alert('there was an error: 5er615');
				}
				toggle_loader_bar(false,null,'add_to_tradedesk'); 
			}
		});
	}

	function show_new_advertiser_input_box(is_new)
	{
		if(is_new){
			
			document.getElementById("sales_select").value="none";
			$('#sales_select').select2({
			});
			document.getElementById("new_advertiser_name").value = '';
			document.getElementById("new_advertiser_input").style.visibility = "visible";
			document.getElementById("load_advertiser_status").innerHTML='';
		 }else{
			document.getElementById("new_advertiser_input").style.visibility = "hidden";
			document.getElementById("load_advertiser_status").innerHTML='';
		}
	}

	function show_new_campaign_input_box(is_new)
	{
		if(is_new){
			document.getElementById("new_campaign_name").value = '';
			document.getElementById("new_campaign_input").style.visibility = "visible";
		 }else{
			document.getElementById("new_campaign_input").style.visibility = "hidden";
		}
	}

	function update_sales_dropdown(biz_id)
	{
		var data_url = "/adgroup_setup/get_advertiser_details/";
		
		$.ajax({
			type: "GET",
			url: data_url,
			async: true,
			data: { adv_id: biz_id},
			dataType: 'json',
			error: function(){
				set_advertiser_status('important', "error 613513, couln't find sales rep");
			},
			success: function(msg){ 
				var returned_data_array = msg;
				if(returned_data_array.success)
				{
					document.getElementById("sales_select").value = returned_data_array.data.sales_person;
					$('#sales_select').select2({
					});
				}else{
					alert('there was an error: 5er615');
				}
				toggle_loader_bar(false,null,'update_sales_dropdown'); 
			}
		});
	}

	function toggle_loader_bar(is_on, expected_seconds_to_completion,this_off_secret)
	{
		if(is_on && loader_off_secret === null)
		{

			loader_off_secret = this_off_secret;
			$("#loader_bar").html('<div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div>');
			run(expected_seconds_to_completion);
		}
		else if(this_off_secret == loader_off_secret)
		{

			loader_off_secret = null;
			$("#loader_bar").html('');
			stop_progress_timer();
		}
	}

	function run(expected_seconds_to_completion)
	{   
		
		var time_increment_ms = 10; //mS between ticks
		var target_threshold = 0.9; //at the expected seconds to completion the width should be here
		var time_factor = (target_threshold*target_threshold)/expected_seconds_to_completion;
		var bar_width;

		var ii=1
		function myFunc() {
			timer = setTimeout(myFunc, time_increment_ms);
			ii++;
			bar_width = Math.min(Math.round(100*Math.sqrt(time_factor*time_increment_ms*ii/1000)-0.5),100)+'%';
			document.getElementById('loader_bar').style.width = bar_width;
		}
		timer = setTimeout(myFunc, time_increment_ms);
	}

	function stop_progress_timer() 
	{
		clearInterval(timer);
	}

	function update_create_advertiser()
	{
		set_advertiser_status(null);
		//first check if a sales person has been selected
		if(document.getElementById("sales_select").value!="none")
		{
			//now handle the three cases: 1) no advertiser selected 2) new advertiser 3)existing advertiser
			switch($("#advertiser_select").select2('data').id)
			{
				case "none":
					alert('please select an advertiser');
					document.getElementById("sales_select").value="none";
					$('#sales_select').select2({
					});
					document.getElementById("add_to_td_button").style.visibility = "hidden";
					document.getElementById("adv_tooltip").style.visibility = "hidden";
					break;
				case "new":
					if(document.getElementById("new_advertiser_name").value == "")
					{
						alert('please name new advertiser');
						document.getElementById("sales_select").value = "none";
						$('#sales_select').select2({});
					}else{
						toggle_loader_bar(true,0.5,'prefill_campaign_inputs_on_update_create_adv');
						var adv_name = document.getElementById("new_advertiser_name").value;
						adv_name = adv_name.replace(/\//g, "-"); //replace all slashes with a '-' character
						create_new_advertiser(adv_name, document.getElementById("sales_select").value);
						document.getElementById("add_to_td_button").style.visibility = "visible";
						document.getElementById("adv_tooltip").style.visibility = "visible";
					}
					break;
				default:
					toggle_loader_bar(true,0.5,'update_advertiser');
					update_advertiser(document.getElementById("advertiser_select").value,document.getElementById("sales_select").value);
			}
			document.getElementById("edit_advertiser_icon").style.visibility = "visible";
		}else{
			alert('please pick a sales person');
			show_campaign_section("hidden");
		}
	}


	function rename_campaign()
	{
		set_campaign_status(null);
		if($("#campaign_rename").val() != '')
		{
			clear_campaign_inputs();
			$('#edit-campaign-modal').modal('hide');
			var data_url = "/adgroup_setup/rename_campaign/";
			$.ajax({
				type: "POST",
				url: data_url,
				async: true,
				data: { c_id: $('#campaign_select').select2('data').id, campaign_name: $("#campaign_rename").val()},
				dataType: 'json',
				error: function(ret){
					set_campaign_status('important', "error u57u3, something went wrong renaming campaign: "+ret.responseText);
				},
				success: function(msg){ 
					var returned_data_array = msg;
					if(returned_data_array.is_success)
					{
						refresh_campaign_dropdown($('#advertiser_select').select2('data').id,$('#campaign_select').select2('data').id,false);
						$("#existing_campaign_name_span").text($('#campaign_select').select2('data').text);
						$("#trash_campaign_span").html("Are you sure you want to send <strong>"+$('#campaign_select').select2('data').text+"</strong> to the trash advertiser?");

						$("#campaign_rename").val($('#campaign_select').select2('data').text);
						$("#campaign_refresh_icon").css("visibility", "hidden");
					}else{
						set_campaign_status('important', "error 74563h6, something went wrong renaming campaign");
					}
				}
			});
		}
		else
		{
			alert('Campaign name cannot be blank');
		}
		
	}
	function rename_advertiser()
	{
		set_advertiser_status(null);
		if($("#advertiser_rename").val() != '')
		{
			$("#adv_refresh_icon").css("visibility", "visible");
			$('#edit-advertiser-modal').modal('hide');
			var data_url = "/adgroup_setup/rename_advertiser/";
			var adv_id = $('#advertiser_select').select2('data').id;
			var adv_name = $("#advertiser_rename").val()
			$.ajax({
				type: "POST",
				url: data_url,
				async: true,
				data: { a_id: adv_id, adv_name: adv_name },
				dataType: 'json',
				error: function(ret){
					set_advertiser_status('important', "error 763753, something went wrong renaming advertiser: "+ret.responseText);
				},
				success: function(msg){ 
					var returned_data_array = msg;
					if(returned_data_array.is_success)
					{	
						current_advertiser = $('#advertiser_select').select2('data');
						current_advertiser.name = adv_name;
						refresh_advertiser_dropdown(current_advertiser);
						$("#existing_adv_name_span").text($('#advertiser_select').select2('data').text);
						$("#advertiser_rename").val($('#advertiser_select').select2('data').text);
						$("#adv_refresh_icon").css("visibility", "hidden");
						set_advertiser_status('success', "advertiser: "+adv_id+" renamed");
					}else{
						set_advertiser_status('important', "error 56845, something went wrong renaming advertiser");
					}
				}
			});
		}
		else
		{
			alert('Advertiser name cannot be blank');
		}
	}

	function update_create_campaign(clone_from_id, new_success_callback, coming_from_bidder_button)
	{
		var error_message = '';
		document.getElementById("adgroup_tooltip").style.display = 'none';
		disable_bidder_buttons();
		document.getElementById("edit_campaign_icon").style.visibility = "hidden";
		document.getElementById("trash_campaign_icon").style.visibility = "hidden";
		document.getElementById("add_av_campaign_to_td_button").style.visibility = "hidden";
		if(!coming_from_bidder_button)
		{
			document.getElementById("campaign_tooltip").style.display = 'none';
			$("#add_campaign_to_td_button").removeClass('disabled');
			$("#add_campaign_to_td_button").prop('disabled', false);			
		}
		else
		{
			$("#add_campaign_to_td_button").addClass('disabled');
			$("#add_campaign_to_td_button").prop('disabled', true);
		}
		set_campaign_status(null);
		var campaign_type = document.getElementById("campaign_select").value;
		var campaign = document.getElementById("new_campaign_name").value;
		if (campaign_type == "new" && campaign == "")
		{
			error_message = error_message+'<li>Please enter name of campaign.</li>';
			document.getElementById("campaign_select").value = "new";
			document.getElementById("new_campaign_name").style.border = '1px solid #b94a48';
		}
		else
		{
			document.getElementById("new_campaign_name").style.border = '1px solid #ccc';
		}
		if(document.getElementById("campaign_landing_page_url").value == '')
		{
			document.getElementById("landing_page_group").classList.add("error");
			error_message = error_message+'<li>Please enter landing page URL for this campaign.</li>';                        
		}
		else
		{
			document.getElementById("landing_page_group").classList.remove("error");
		}

		var time_series_string=get_timeseries_string_from_array();
		if (time_series_string == undefined || time_series_string == "" || time_series_string.length < 15 || time_series_string.indexOf("ERROR:- ") != -1)
		{
			error_message = error_message+'<li>Please enter flights information.</li>';
		}
		
		if(error_message == ''){
			var term_type = 'FIXED_TERM';//(document.getElementById('in_total_radio').checked == true) ? 'FIXED_TERM' : $('input[name="month_reset_choice"]:checked').val();
			switch(document.getElementById("campaign_select").value)
			{
				case "new":
					
					if(document.getElementById("new_campaign_name").value != "")
					{
                                                if((clone_from_id != null))
						{
							toggle_loader_bar(true,1.4,'clone_campaign');

						}
						
						//GET ALL CATEGORIES AND PUT INTO ARRAY, PASS IN CREATENEWCMAPGH
						var categories = get_categories();
						var campaign_name = document.getElementById("new_campaign_name").value;
						campaign_name = campaign_name.replace(/\//g, "-"); //replace all slashes with a '-' character
						create_new_campaign(
							$("#advertiser_select").select2('data').id, 
							campaign_name, 
							document.getElementById("campaign_landing_page_url").value, 
							0,
							null,
							null,
							term_type, 
							categories,
							$("#is_campaign_archived").is(":checked") ? 1 : 0,
							null,		
							clone_from_id,
							function(clone_to_id){if (new_success_callback) new_success_callback(clone_to_id);}
						);
						$("#existing_campaign_name_span").text($('#campaign_select').select2('data').text);
						$("#trash_campaign_span").html("Are you sure you want to send <strong>"+$('#campaign_select').select2('data').text+"</strong> to the trash advertiser?");
		   				$("#campaign_rename").val($('#campaign_select').select2('data').text);
					}
					break;
				default:
					toggle_loader_bar(true,1.5,'update_campaign');
					//GET ALL CATEGORIES AND PUT INTO ARRAY
					var categories = get_categories();
					update_campaign(
						document.getElementById("campaign_select").value, 
						document.getElementById("campaign_landing_page_url").value, 
						0,
						null,
						null,
						term_type, 
						categories,
						$("#is_campaign_archived").is(":checked") ? 1 : 0,
						null,
						coming_from_bidder_button
					); 

			}
		}else{
			Materialize.toast('<ul>'+error_message+'</ul>', 20000, 'toast_top');
			return false;
		}
	}

	function create_new_campaign(a_id, c_name, lp, imprs, e_date, s_date, term_type, c_list, is_archived, invoice_budget, clone_from_id, success_callback)
	{	
		var data_url = "/adgroup_setup/create_new_campaign/";
		 
		var time_series_string=get_timeseries_string_from_array();
		if (time_series_string == undefined || time_series_string == "" || time_series_string.length < 15 || time_series_string.indexOf("ERROR:- ")  != -1)
		{
			set_campaign_status('important', "Err : 4578 : Please fix TimeSeries to create a Campaign"); 
			return false;
		}
		
		var pause_date = $("#pause_date").val();
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { a_id: a_id, c_name: c_name,lp: lp,imprs: imprs,e_date: e_date, s_date: s_date, 
				term_type: term_type, cats:c_list, is_archived: is_archived, invoice_budget: invoice_budget,
				 clone_from_id: clone_from_id, time_series_data: time_series_string, pause_date: pause_date},
			dataType: 'json',
			error: function(ret){
				set_campaign_status('important', "error e156ex, something went wrong: "+ret);

			},
			success: function(msg){ 

				var returned_data_array = msg;
				if(returned_data_array.success)
				{
					refresh_campaign_dropdown(a_id,returned_data_array.id,(clone_from_id != null));
					show_new_campaign_input_box(false);
					set_campaign_status('success', "campaign: " + returned_data_array.id + " created");
					if(success_callback) success_callback(returned_data_array.id);
				}
				else
				{
					if(returned_data_array.id==0)
					{
						set_campaign_status('important', "adv: campaign name already in use");
					}
					else
					{
						set_campaign_status('important', "error 34dd651, something went wrong: " + msg.info);
						toggle_loader_bar(false,null,'clone_campaign');
					}
				}
			}
		});
	}

	function update_campaign(c_id, lp, imprs, e_date, s_date, term_type, categories, is_archived, invoice_budget, coming_from_bidder_button){
		var data_url = "/adgroup_setup/update_campaign/";
		var time_series_string=get_timeseries_string_from_array();
		if (time_series_string == undefined || time_series_string == "" || time_series_string.length < 15 || time_series_string.indexOf("ERROR:- ")  != -1)
		{
			set_campaign_status('important', "Err : 5678 : Please fix TimeSeries. Campaign not updated."); 
			return false;
		}

		var pause_date = $("#pause_date").val();
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { c_id: c_id,lp: lp,imprs: imprs, e_date: e_date, s_date: s_date, term_type: term_type, 
				cats: categories, is_archived: is_archived, invoice_budget: invoice_budget, time_series_data: time_series_string, pause_date: pause_date},
			dataType: 'json',
			error: function(ret)
			{
				set_campaign_status('important', "error 8936, something went wrong" + ret.info);
			},
			success: function(msg){ 
				var returned_data_array = msg;
				if(returned_data_array.success)
				{
					update_cycle_impression_cache_for_campaign(c_id);
					set_campaign_status('success', "campaign: " + c_id + " updated");
					refresh_campaign_dropdown($("#advertiser_select").select2('data').id, c_id, false);
					if(coming_from_bidder_button)
					{
						add_campaign_to_tradedesk();
					}
				}
				else
				{
					if(returned_data_array.id == 0)
					{
						set_campaign_status('important', "error 6ax31, something went horribly wrong" + msg);
					}
					else
					{
						set_campaign_status('important', "error ce3ij0c, something went wrong" + msg);
					}
					
				}
				toggle_loader_bar(false,null,'update_campaign'); 
			}
		});
	}

	function create_new_advertiser(adv_name, sales_id)
	{
		var data_url = "/adgroup_setup/create_new_advertiser/";
		$.ajax({
					type: "POST",
					url: data_url,
					async: true,
					data: { adv: adv_name, s_id: sales_id},
					dataType: 'html',
					error: function(ret){
						set_advertiser_status('important', "error 163131, something went wrong"+ret);

					},
					success: function(msg){ 
						var returned_data_array = eval('(' +msg+')' );
						if(returned_data_array.success)
						{
							refresh_advertiser_dropdown(returned_data_array.data);
							document.getElementById("edit_advertiser_icon").style.visibility = "visible";
							$("#existing_adv_name_span").text($('#advertiser_select').select2('data').text);
							$("#advertiser_rename").val($('#advertiser_select').select2('data').text);
							document.getElementById("new_advertiser_input").style.visibility = "hidden";
							set_advertiser_status('success', "advertiser: "+returned_data_array.data.id+" created");
							refresh_campaign_dropdown(returned_data_array.data.id,"new",false)
							make_campaign_tag_table_show("new");
						}else{
							if(returned_data_array.id==0)
							{
								set_advertiser_status('important', "advertiser name already in use");
								document.getElementById("sales_select").value = "none";

							}else{
								set_advertiser_status('important', "error 5e25rcf4, something went wrong"+msg);
							}
							
						}

						toggle_loader_bar(false,null,'create_new_advertiser'); 

					}
				});
	}
	
	function refresh_advertiser_dropdown(advertiser)
	{
		if(advertiser != null)
		{
			$("#advertiser_select").select2('data', {'id': advertiser.id, 'text': advertiser.name, 'id_list': "fid: "+advertiser.id});
			$("#s2id_advertiser_select > .select2-choice").css('height', 'auto');
			update_advertiser_owner_select2();
		}
		toggle_loader_bar(false,null,'refresh_advertiser_dropdown');
	}

	function refresh_campaign_dropdown(adv_id,c_id,is_clone_mode)
	{
		show_campaign_section("hidden");
		var data_url = "/adgroup_setup/get_vl_campaigns_dropdown/";
		$.ajax({
					type: "POST",
					url: data_url,
					async: true,
					data: {adv_id: adv_id},
					dataType: 'html',
					error: function(){
						set_campaign_status('important', "error 56431, error getting campaigns");
					},
					success: function(msg){ 
						document.getElementById("campaign_select").innerHTML=msg;
						if(c_id!=null)
						{
							document.getElementById("campaign_select").value = c_id;

						}	
						$('#campaign_select').select2({});
						handle_campaign_change(document.getElementById("campaign_select").value, is_clone_mode);
						toggle_loader_bar(false,null,'refresh_campaign_dropdown');
						toggle_loader_bar(false,null,'add_to_tradedesk'); 
						toggle_loader_bar(false,null,'prefill_campaign_inputs_on_update_create_adv');
					}
				});
	}

	function update_advertiser(adv_id,sales_id)
	{
		var data_url = "/adgroup_setup/update_advertiser/";
		toggle_loader_bar(false,null,'add_to_tradedesk'); 
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { adv_id: adv_id, s_id: sales_id},
			dataType: 'html',
			error: function(ret){
				set_advertiser_status('important', "error 563, something went wrong"+ret);
			},
			success: function(msg){ 
				var returned_data_array = eval('(' +msg+')' );
				if(returned_data_array.success)
				{
					set_advertiser_status('success', "advertiser: "+adv_id+" updated");
				}
				else
				{
					if(returned_data_array.id==0)
					{
						set_advertiser_status('important', "error prm251, something went horribly wrong"+msg);
					}
					else
					{
						set_advertiser_status('important', "error 5614, something went wrong"+msg);
					}
					
				}
				toggle_loader_bar(false,null,'update_advertiser'); 
			}
		});
	}
	
	function prefill_campaign_inputs(c_id, is_clone_mode, success_callback)
	{
		blank_category_boxes();
		show_campaign_section("hidden");
		clear_campaign_inputs();
		var data_url = "/adgroup_setup/get_campaign_details/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { c_id: c_id},
			dataType: 'json',
			error: function(ret){
				set_campaign_status('important', "error 58413, something went wrong: " + ret.info);
			},
			success: function(msg){ 
				var returned_data_array = msg;
				if(returned_data_array.success)
				{
					//preload fields
					document.getElementById("campaign_landing_page_url").value = returned_data_array.data[0].LandingPage;
					document.getElementById("pause_date").value = returned_data_array.data[0].pause_date;
					document.getElementById("is_campaign_archived").checked = returned_data_array.data[0].ignore_for_healthcheck == 1 ? true : false;
					if (!document.getElementById("is_campaign_archived").checked) 
					{
						$('#is_archived_copy').attr('class', '');
					} 
					else 
					{
						$('#is_archived_copy').attr('class', 'label label-important');
					}
					check_tradedesk_campaign(c_id);
					if(document.getElementById("enabled_campaign_type").value != 1)
					{
						check_tradedesk_av_campaign(c_id);
					}
					update_category_boxes(c_id,is_clone_mode);
					$('input[name="month_reset_choice"][value="' + returned_data_array.data[0].term_type + '"]').prop('checked', true);
					$('input[name="month_reset_choice"]:checked').change();
					populate_time_series_array_for_existing_campaign(returned_data_array['data_timeseries']);
					sitelist_update_pending = false;
					ziplist_update_pending = false;
					$(".edit_pending").hide();
					$("#site_list_edit_container").css('background-color', "");
					$("#zip_list_edit_container").css('background-color', "");
					if(returned_data_array.tag_data)
					{
						//Set data to tag file
						$("#tag_file_select").select2('data', {id: returned_data_array.tag_data.id, text: returned_data_array.tag_data.name});
					}

					make_campaign_tag_table_show(c_id);
					
					if(returned_data_array.campaign_ttd_data.sitelist)
					{
						$("#site_list_edit").val("");
						var sitelist_campaign_name = $("#advertiser_select").select2('data').text + ' - ' + returned_data_array.data[0].Name + " - " + c_id + " - " + get_todays_date_string();
						populate_sitelist_fields(sitelist_campaign_name, returned_data_array.campaign_ttd_data.sitelist.sitelist_data);
					}
					else
					{
						$("#current_sitelist_name").html("");
						$("#site_list_edit").val("");
						$("#site_list_name").val(($("#advertiser_select").select2('data').text + " - " + returned_data_array.data[0].Name + " - " + c_id + " - " + get_todays_date_string()));
					}
					
					if(returned_data_array.campaign_ttd_data.zip_list)
					{
						$("#zip_list_edit").val(returned_data_array.campaign_ttd_data.zip_list.zips);
						if(returned_data_array.campaign_ttd_data.zip_list.bad_zips)
						{
							$("#bad_zip_list").html("Excluded ZIPs: " +returned_data_array.campaign_ttd_data.zip_list.bad_zips);
						}
						else
						{
							$("#bad_zip_list").html("");							
						}
						$("#current_ziplist_submission_time").html("Last Updated: "+returned_data_array.campaign_ttd_data.zip_list.timestamp);
					}
					else
					{
						$("#zip_list_edit").val("");
						$("#current_ziplist_submission_time").html("");
						$("#bad_zip_list").html("");
					}
					check_action_date();
					reset_notes_textarea();
					fetch_notes_for_campaign(1);
					if (success_callback) success_callback();
				}
				else
				{
					if(returned_data_array.id == 0)
					{
						set_campaign_status('important', "error de55de, something went horribly wrong" + msg.info);
					}
					else
					{
						set_campaign_status('important', "error cdw5156, something went wrong" + msg.info);
					}
				}
				
			}
		});
	}

	function blank_category_boxes()
	{
		var categories = document.getElementsByName("category_box");
		for(var i = 0; i < categories.length; i++)
		{
			categories[i].checked = false;
		}
	}

	function get_categories()
	{
		var selected_categories = [];
		var categories = document.getElementsByName("category_box");
		for (var i = 0; i < categories.length; i++)
		{
			if(document.getElementById(categories[i].id).checked)
			{
				selected_categories.push(categories[i].id.slice(4));
			}
		}
		return selected_categories;
	}

	function update_category_boxes(c_id, is_clone_mode)
	{
		
		var data_url = "/adgroup_setup/get_campaign_categories/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { c_id: c_id},
			dataType: 'json',
			error: function(ret){
				set_campaign_status('important', "error 58413, something went wrong" + ret);
			},
			success: function(msg){
		
				var returned_data_array = msg;
				if(returned_data_array.success)
				{
					blank_category_boxes();
					if(returned_data_array.data)
					{
						for(var i = 0; i < returned_data_array.data.length; i++)
						{

							var box = document.getElementById("cat_" + returned_data_array.data[i].tag_id);
							if(box)
							{
								box.checked = true;
							}
						}
					}
				//preload fields
				}
				else
				{
					if(returned_data_array.id == 0)
					{
						set_campaign_status('important', "error de55de, something went horribly wrong" + msg.info);
					}
					else
					{
						set_campaign_status('important', "error cdw5156, something went wrong" + msg.info);
					}
				}
				toggle_loader_bar(false,null,'prefill_campaign_inputs'); 
				toggle_loader_bar(false,null,'prefill_campaign_inputs_as_new'); 
				toggle_loader_bar(false,null,'prefill_campaign_inputs_init'); 
				toggle_loader_bar(false,null,'prefill_campaign_inputs_after_adv_select');
				toggle_loader_bar(false,null,'prefill_campaign_inputs_campaign_change'); 
				toggle_loader_bar(false,null,'prefill_campaign_inputs_on_update_create_adv');
				if(!is_clone_mode)
				{
					show_campaign_section("visible");
				}
				
			}
		});
	}
		
	function validate_url(url)
	{
		var regexp = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\/]))?/
		return regexp.test(url);
	}

	function show_campaign_section(vis)
	{
		if(vis == "hidden")
		{
			$("#campaign_section").css("display","none");
		}
		else
		{
			$("#campaign_section").css("display","block");
		}
	}

	function show_adgroup_section(vis)
	{
		document.getElementById("adgroup_section").style.visibility = vis;
	}

	function clear_campaign_inputs()
	{
		document.getElementById("campaign_landing_page_url").value = '';
		document.getElementById("new_campaign_name").value = '';
		blank_category_boxes();
		document.getElementById("campaign_tooltip").style.display = 'none';
		document.getElementById("edit_campaign_icon").style.visibility = "hidden";
		document.getElementById("trash_campaign_icon").style.visibility = "hidden";
		document.getElementById("adgroup_tooltip").style.display = 'none';
		$("#site_list_edit").prop('disabled', false);
		$("#site_list_edit").val("");
		$("#site_list_name").prop('disabled', false);
		$("#site_list_name").val("");
		$("#current_sitelist_name").html("");
		$("#current_ziplist_submission_time").html("");
		$("#bad_zip_list").html("");
		$("#zip_list_edit").prop('disabled', false);
		$("#zip_list_edit").val("");
		$("#pause_date").val("");
		disable_bidder_buttons();
		document.getElementById("add_av_campaign_to_td_button").style.visibility = "hidden";
		document.getElementById("av_tooltip").style.visibility = "hidden";
		document.getElementById("is_campaign_archived").checked =  false;
		document.getElementById("is_display_campaign").checked = true;
		$('#is_archived_copy').attr('class', '');
		$("#tag_file_select").select2("val", "");
		$("#is_display_campaign").prop('checked', false);
		
	}
	
	function handle_adverify_campaign_visibility(video_campaign_type)
	{
		if(video_campaign_type == 1)
		{
			$("#ad_verify_categories").hide();
			$("#ad_verify_categories").prev().hide();
			$("#add_av_campaign_to_td_button").css("visibility","hidden");
			$("#av_tooltip").css("visibility","hidden");
			
		}
		else
		{
			$("#ad_verify_categories").show();
			$("#ad_verify_categories").prev().show();
			if($("#check_status_of_av_campaign_to_td_button").val() == 0)
			{
				$("#add_av_campaign_to_td_button").css("visibility","visible");
				$("#av_tooltip").css("visibility","visible");
			}
			
		}
		$("#enabled_campaign_type").val(video_campaign_type);
	}
	
	function handle_tag_file_change(tag_file_id)
	{
		if(tag_file_id == "new_tracking_tag_file")
		{
			//show new
			var directory_name = get_advertiser_directory_name();
			
			if (typeof directory_name !== 'undefined' && directory_name !== '')
			{	
				$("#tracking_file_prepend_string").html(directory_name+"/");
				$("#new_tag_file_input").show();
				$("#new_tag_file_name").val("");
			}
		}
		else
		{
			//hide
			$("#new_tag_file_input").hide();
		}
	}

	function get_advertiser_directory_name()
	{
		var campaign_id = $("#campaign_select").val();
		var data_url = "/tag/get_advertiser_directory_name/";
		var directory_name = "";
		$.ajax({
			type: "POST",
			url: data_url,
			async: false,
			data: {
				campaign_id:campaign_id
			},
			dataType: 'json',
			success: function(result){
				if(result.status === "success")
				{
					directory_name = result.directory_name;
				}
			}
		});
		
		return directory_name;
	}


	function init()
	{
		
		if(is_iframe)
		{
			$(".navbar").hide();
			$(".container").css('position', 'relative');
			$(".container").css('top', '-50px');

		}
		
		var advertiser = <?php echo $advertiser_json; ?> ;
		var c_id = <?php echo $c_id; ?> ;

		$('.tool_poppers').popover();

		$('#advertiser_select').select2({
			placeholder: "Select an advertiser",
			minimumInputLength: 0,
			ajax: {
				url: "/adgroup_setup/get_advertisers",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 100,
						page: page
					};
				},
				results: function (data) {
					return {results: data.results, more: data.more};
				}
			},
			allowClear: false,
			formatSelection: format_advertiser_cell,
			formatResult: format_advertiser_cell
		}).on("change", function(e){
			$("#view_on_bidder").hide();
			$("#new_tracking_tag_file_status").hide().html("");
			$("#new_tag_file_input").hide();
			
		});

		$('#tag_file_select').select2({
			placeholder: "Select a tag file",
			minimumInputLength: 0,
			allowClear:true,
			ajax: {
				//url: "/adgroup_setup/get_tag_files_for_campaign",
				url: "/tag/get_select2_tracking_tag_file_names/",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 100,
						page: page,
						campaign_id: $("#campaign_select").val(),
						td_tag_type:0
					};
				},
				results: function (data) {
					return {results: data.results, more: data.more};
				}
			}
		}).on("change",function(){
			$("#new_tracking_tag_file_status").hide().html("");
		});
		
		$('#campaign_select').select2({}).on("change", function(e){
			$("#view_on_bidder").hide();
			$("#new_tracking_tag_file_status").hide().html("");
			$("#new_tag_file_input").hide();
		});
		
		$('#sales_select').select2({}).on("change", function(e){
			$("#view_on_bidder").hide();
			$("#new_tracking_tag_file_status").hide().html("");
			$("#new_tag_file_input").hide();
		});
		
		$('#am_ops_owner_select2').select2({
			multiple: true,
			placeholder: "select user",
			allowClear: true,
			minimumInputLength: 0,
			ajax:{
				type: 'POST',
				url: '/campaign_setup/select2_get_allowed_advertiser_owners/',
				dataType: 'json',
				data: function (term, page){
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return{
						q: term,
						page_limit: 20,
						page: page
					};
				},
				results: function(data){
					return {results: data.result, more: data.more};
				}
			}
		}).on("change", function(e){
			var data_url = "";
			var data_obj = {};
			var advertiser_id = $('#advertiser_select').val();
			
			if(advertiser_id !== 'none' && advertiser_id !== 'new')
			{
				if(typeof e.added !== "undefined")
				{	
					data_url = '/campaign_setup/ajax_add_advertiser_owner';
					data_obj = {user_id: e.added.id, advertiser_id: advertiser_id};
				}
				if(typeof e.removed !== "undefined")
				{
					data_url = '/campaign_setup/ajax_remove_advertiser_owner';
					data_obj = {user_id: e.removed.id, advertiser_id: advertiser_id};
				}
				if(data_url !== "")
				{
					$.ajax({
						type: "POST",
						url: data_url,
						async: true,
						data: data_obj,
						dataType: 'json',
						success: function(data, textStatus, jqXHR){
							if(data.is_success === false)
							{
								set_advertiser_status('important', data.errors);
							}
						},
						error: function(xhr, textStatus, error){
							if(xhr.getAllResponseHeaders())
							{
								set_advertiser_status('important', "Error 164326: server error modifying advertiser owners");
							}
						}
					});
				}
			}
			$("#view_on_bidder").hide();
		});
		
		if(c_id !=0 && advertiser != "none"){
			toggle_loader_bar(true,0.2,'prefill_campaign_inputs_init');
			refresh_advertiser_dropdown(advertiser);
			handle_adv_change(advertiser.id,c_id);
		}else{
			toggle_loader_bar(true,0.2,'refresh_advertiser_dropdown');
			refresh_advertiser_dropdown(null);
		}
		$('#is_campaign_archived').change(function(){
			if (!$(this).is(':checked')) {
				$('#is_archived_copy').attr('class', '');
			} else {
				$('#is_archived_copy').attr('class', 'label label-important');
			}
		});


	}

	function format_advertiser_cell(data)
	{

		var adv_html = '<div style="'+(data.id == "new" ? "" : "line-height:15px;")+'white-space:normal;">' +
							"<strong>"+data.text+"</strong>" +
							(data.id == "new" ? "" : "<br>" + '<span style="font-size:10px;color:#AAAAAA">' +data.id_list+ "</span>") +
						"</div>";
		return adv_html;
	}

	function update_cycle_impression_cache_for_campaign(c_id)
	{
		$.ajax({
			type: "POST",
			url: "/campaigns_main/ajax_cache_single_campaign_cycle_impressions",
			async: true,
			data: {c_id: c_id},
			dataType: 'json',
			error: function(jqXHR, textStatus, errorThrown){
				set_campaign_status('important', "error 58413.1, failed to cache cycle_impressions for selected campaign ("+c_id+")");
			},
			success: function(data, textStatus, jqXHR){
				
				if(data.is_success !== undefined && data.is_success == true)
				{
					//return successfully
				}
				else
				{
					set_campaign_status('warning', "Error caching impressions: " + data.errors);
				}
			}
		});
	}

	function refresh_cache_campaign_report()
	{
		var campaign_id = document.getElementById("campaign_select").value;
		if (campaign_id == '')
			return;

		var data_url = "/adgroup_setup/refresh_cache_campaign_report/";
		set_campaign_status('warning', "Please wait, this operation can take a long time to complete");
		
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {campaign_id: campaign_id},
			dataType: 'json',
			error: function(msg){
				set_campaign_status('important', "error 58813.1, failed to refresh the cache table report_cached_adgroup_date for selected campaign");
			},
			success: function(msg){ 
				set_campaign_status('success', "Cache refresh successful for campaign "+campaign_id);
			}
		});
	}

	function save_site_list()
	{
		
		$("#site_list_submit").addClass('disabled');
		$("#site_list_submit").prop('disabled', true);

		var site_list_name = $("#site_list_name").val();
		if(site_list_name.length < 1)
		{
			button_success($("#site_list_submit"), "No Name!", false, true);
			return;
		}

		var raw_site_list_string = $("#site_list_edit").val();
		if(raw_site_list_string.length < 1)
		{
			button_success($("#site_list_submit"), "No Sitelist!", false, true);
			return;
		}

		var campaign_id = $("#campaign_select").val();

		$.ajax({
			type: "POST",
			url: "/tradedesk/save_site_list",
			async: true,
			data: {site_list_name: site_list_name, raw_site_list: raw_site_list_string, campaign_id: campaign_id},
			dataType: 'json',
			error: function(msg)
			{
				set_campaign_status('important', "error 51731, failed to save new sitelist to campaign " + campaign_id);
				button_success($("#site_list_submit"), "Failed!", false, true);
			},
			success: function(data, textStatus, jqXHR)
			{
				if(data.success)
				{
					set_campaign_status('success', "Saved sitelist for campaign "+campaign_id);
					$("#current_sitelist_name").html("Saved " + site_list_name);
					button_success($("#site_list_submit"), "Sitelist saved", true, true);
					$("#site_list_edit_container").css('background-color', "");
					$("#sitelist_edit_pending").hide();
					sitelist_update_pending = false;
				}
				else
				{
					set_campaign_status('important', "error 51732, failed to save new sitelist to campaign " + campaign_id);
					button_success($("#site_list_submit"), "Failed!", false, true);
				}
			}
		});
	}

	function save_zip_list()
	{
		
		$("#zip_list_submit").addClass('disabled');
		$("#zip_list_submit").prop('disabled', true);

		var raw_zip_list_string = $("#zip_list_edit").val();
		var campaign_id = $("#campaign_select").val();

		$.ajax({
			type: "POST",
			url: "/tradedesk/save_zip_list",
			async: true,
			data: {raw_zip_list: raw_zip_list_string, campaign_id: campaign_id},
			dataType: 'json',
			error: function(msg)
			{
				set_campaign_status('important', "error 51731, failed to save new ZIPs to campaign " + campaign_id);
				button_success($("#zip_list_submit"), "Failed!", false, true);
			},
			success: function(data, textStatus, jqXHR)
			{
				if(data.success)
				{
					set_campaign_status('success', "Saved ZIPs for campaign "+campaign_id);
					button_success($("#zip_list_submit"), "ZIPs saved", true, true);
					$("#zip_list_edit_container").css('background-color', "");
					$("#ziplist_edit_pending").hide();
					ziplist_update_pending = false;
					if(data.bad_zips)
					{
						$("#bad_zip_list").html("Excluded ZIPs: " +data.bad_zips);
					}
					else
					{
						$("#bad_zip_list").html("");
					}
					$("#current_ziplist_submission_time").html("Last Update: Just now");

				}
				else
				{
					set_campaign_status('important', "error 51732, failed to save ZIPs to campaign " + campaign_id);
					button_success($("#zip_list_submit"), "Failed!", false, true);
				}
			}
		});
	}

	function trash_campaign()
	{
		var campaign_id = $("#campaign_select").val();
		$.ajax({
			type: "POST",
			url: '/adgroup_setup/trash_campaign',
			async: false,
			dataType: 'json',		
			data: {
				c_id:campaign_id,
			},
			success: function(data, textStatus, jqXHR){
				if(data.is_success !== true)
				{
					set_campaign_status('important', "error 35712, failed to trash campaign " + campaign_id);
				}
				else
				{
					var advertiser_id = $("#advertiser_select").val();
					handle_adv_change(advertiser_id, null);
				}
			}
		});		
	}

	function button_success(button, success_string, success, disabled)
	{
		var old_button_html = button.html();

		if(success)
		{
			var button_class = "btn-success";
			var button_icon_html = '<i class="icon-thumbs-up icon-white"></i> ';
		}
		else
		{
			var button_class = "btn-danger";
			var button_icon_html = '<i class="icon-remove icon-white"></i> ';
		}

		button.html(button_icon_html+success_string);
		button.removeClass('btn-info');
		button.addClass(button_class);
		setTimeout(function()
		{
			button.html(old_button_html);
			button.addClass('btn-info');
			button.removeClass(button_class);
			if(disabled)
			{				
				button.removeClass('disabled');
				button.prop('disabled', false);
			}
		}, 2000);		
	}

	function disable_bidder_buttons()
	{
		$(".ttd_only").addClass('disabled');
		$(".ttd_only").prop('disabled', true);
	}

	function enable_bidder_buttons()
	{
		$(".ttd_only").removeClass('disabled');
		$(".ttd_only").prop('disabled', false);
	}

	function get_todays_date_string()
	{
		var d = new Date();

		var month = d.getMonth()+1 < 10 ? '0'+(d.getMonth()+1) : d.getMonth()+1 ;
		var day = d.getDate() < 10 ? '0'+d.getDate() : d.getDate();
		var year = d.getFullYear().toString().substr(2,2);
		var hours = d.getHours() < 10 ? '0'+d.getHours() : d.getHours();
		var minutes = d.getMinutes() < 10 ? '0'+d.getMinutes() : d.getMinutes();

		return month + '.' + day + '.' + year + ' - ' + hours + ':' + minutes;
	}

	function populate_sitelist_fields(sitelist_name, sitelist_data)
	{
		$("#site_list_name").val(sitelist_name);
		$("#current_sitelist_name").html(sitelist_name);

		var sitelist_raw_string = "";
		$.each(sitelist_data, function(key, value)
		{
			sitelist_raw_string += value[0] + "\t" + value[1] + "\t" + value[2];
			if(key != sitelist_data.length-1)
			{
				sitelist_raw_string += "\n";
			}
		})

		$("#site_list_edit").val(sitelist_raw_string);		
	}

	function add_new_tag_file_for_advertiser()
	{
		var campaign_id = $("#campaign_select").val();
		var new_tag_file_name = $("#new_tag_file_name").val();
		$("#new_tracking_tag_file_status").hide().html("");
		
		new_tag_file_name = new_tag_file_name.replace(/ /g,"_");
		if (new_tag_file_name == '' || !(/\S/.test(new_tag_file_name)) || new_tag_file_name.match(/^[^a-zA-Z0-9]+$/))
		{
			$("#new_tracking_tag_file_status").html("Invalid file name").css("color","red").show();
			return;
		}
		
		new_tag_file_name = $("#tracking_file_prepend_string").html() + new_tag_file_name;

		$("#add_new_tag_file_button").addClass('disabled');
		$("#add_new_tag_file_button").prop('disabled', true);

		$.ajax({
			type: "POST",
			url: '/tag/create_new_tracking_tag_file',
			async: false,
			dataType: 'json',		
			data: {
				campaign_id:campaign_id,
				tracking_tag_file_name:new_tag_file_name
			},
			success: function(data, textStatus, jqXHR){
				if(data.is_success !== true)
				{
					var error_msg = (typeof data.errors !== 'undefined') ? data.errors : "Error !!!";
					$("#new_tracking_tag_file_status").html(error_msg).css("color","red").show();
				}
				else
				{
					var tracking_tag_file_select2_option = {'id':data.id,'text':new_tag_file_name+".js"};
					$('#tag_file_select').select2('data', tracking_tag_file_select2_option);
					handle_tag_file_change(data.id);
					$("#new_tracking_tag_file_status").hide().html("");
					g_new_files[""+data.id] = 1;
				}
				$("#add_new_tag_file_button").removeClass('disabled');
				$("#add_new_tag_file_button").prop('disabled', false);
			}
		});

	}

$("#site_list_edit").on('input', function(){
	update_site_list_name();
	if(!sitelist_update_pending)
	{
		$("#site_list_edit_container").css('background-color', "#F7D29D");
		$("#sitelist_edit_pending").show();
		sitelist_update_pending = true;
	}
});

$("#zip_list_edit").on('input', function(){
	if(!ziplist_update_pending)
	{
		$("#zip_list_edit_container").css('background-color', "#F7D29D");
		$("#ziplist_edit_pending").show();		
		ziplist_update_pending = true;
	}
});

$(document).on("change", '#advertiser_select', update_advertiser_owner_select2);
	function update_advertiser_owner_select2()
	{
	var advertiser_id = $("#advertiser_select").val();
	$("#am_ops_owner_select2").select2('data', []);
	if(advertiser_id !== "none" && advertiser_id !== "new")
	{
		$.ajax({
			type: "POST",
			url: '/campaign_setup/ajax_get_assigned_advertiser_owners',
			async: true,
			data: {advertiser_id: advertiser_id},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(typeof data.is_success !== "undefined" && typeof data.errors !== "undefined" && typeof data.data !== "undefined")
				{
					if(data.is_success !== false)
					{
						$("#am_ops_owner_select2").select2('data', data.data);
					}
					else
					{
						set_advertiser_status('important', data.errors);
					}
				}
				else
				{
					set_advertiser_status('important', 'Error 469898: invalid server response getting advertiser owners');
				}
			},
			error: function(jqXHR, textStatus, errorThrown){
				set_advertiser_status('important', 'Error 469897: server error getting advertiser owners');
			}
		});
	}
}

</script>

</body>
<script>
	window.onload = init();
</script>
</html>
<script type="text/javascript" src="/js/timeseries/time_series.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/materialize_freq.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-csv/0.71/jquery.csv-0.71.min.js"></script>
<style>
.flexcroll{ 
			height:200px;
			width:'100%';
			overflow:scroll;
		   }
.flexcroll::-webkit-scrollbar {
	width: 9px;
}
.flexcroll::-webkit-scrollbar-thumb {
	-webkit-border-radius: 4px;
	border-radius: 4px;
	background: rgba(#C8CBCC,#C8CBCC,#C8CBCC,0.8); 
	-webkit-box-shadow: inset 0 0 4px rgba(0,0,0,0.5); 
}

.table th, .table td {
	line-height: 10px;
}

.select2-results .select2-result-label
{
	word-wrap: break-word;
}
</style>
