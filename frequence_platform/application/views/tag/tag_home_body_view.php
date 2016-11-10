<style>
	.tag_list_data
	{
		width: 250px;
		max-width: 250px;
		word-wrap:break-word;
	}
	#active_inactive_tab
	{
		cursor: pointer;
	}
	#displayed_table, .ttd_tag_controls
	{
		display:none;
	}
	#new_tracking_tag_file_container
	{
		display:none;
	}
	#new_tracking_tag_file_status
	{
		display: none;
		font-weight: bold;
		position: absolute;
		float: left;
		margin-left: 130px;
	}
	#tag_file_content_display
	{
		display:none;
		margin-bottom:0px;
	}
	#tag_file_content_display .hide_content
	{
		display:none;
		cursor: pointer;
	}
	#tag_file_content_display .hide_content:hover
	{
		text-decoration: underline;
	}
	#tag_file_content_display .show_content
	{
		cursor: pointer;
	}
	#tag_file_content_display .show_content:hover
	{
		text-decoration: underline;
	}
	#tag_file_content_display #download_tag_file_content
	{
		position: relative;
		float: right;
		margin-right: 10px;
		margin-top: 17px;
	}
	#tag_file_content_display #assign_existing_tags
	{
		display:none;
		position: absolute;
		float: right;
		top: -240px;
		right: 10px;
	}
	#tag_file_content_display #assign_existing_tags_status
	{
		display:none;
		color: #BD362F;
		font-size: 11px;
		position: absolute;
		float: right;
		right:10px;
		top: -209px;
		width: 187px;
	}
	#tag_file_content_display .controls
	{
		position:relative;
		margin-bottom:20px;
	}
	#tag_file_content_display_container
	{
		display:none;
	}
	#tag_file_content_display_container .tag_file_content
	{
		background: #ccc;
		margin-bottom: 0px !important;
		padding-bottom: 10px;
	}
	#tag_file_content_display_container .first_tag
	{
		padding-top:20px !important;
		border-radius: 3px 3px 0px 0px;
	}
	#tag_file_content_display_container .last_tag
	{
		margin-bottom:20px !important;
		padding-bottom:20px !important;
		border-radius: 0px 0px 3px 3px;
	}
	#tag_file_content_display_container .update_status
	{
		position:relative;
		top:8px;
		margin-left: 10px;
	}
	#tag_file_content_display_container .tag_id_display
	{
		position:relative;
		float:right;
		top:-19px;
		color:#BD362F;
		font-size:11px;
		right:-13px;
	}
	#tag_file_content_display_container .activate_deactivate
	{		
		position: relative;
		float: right;
		right: -13px;
		top: 33px;
	}
	#tag_file_content_display_container .hide_class
	{		
		display:none;
	}
	#tag_file_content_display_container .tag_file_content .controls
	{
		background: #cccccc;
		margin-left: 55px;
	}
	#tag_file_content_display_container .tag_file_content .tag_file_content_controls
	{
		float: right;
		margin-right: 55px;
		padding: 20px;
		background-color: #f5f5f5;
		margin-top: 15px;
		border-radius: 5px;
		width:325px;
		height:60px;
	}
	#tag_file_content_display_container .tag_file_content .tag_file_content_controls input
	{
		position: relative;
		top: -4px;
		margin-left: 10px;
	}
	#tag_file_content_display_container .tag_file_content .tag_file_content_controls .rtg_radio
	{
		position: relative;
		top: -4px;
		margin-left: 0px;
	}
	#tag_file_content_display_container .tag_file_content .tag_file_content_controls .tag_file_content_tag_button_save_btn
	{
		margin-top: 15px;
	}
	#download_tag_btn
	{
		width: 97%;
		position: absolute;
		float: right;
		z-index: 1;
	}
	#download_tag_btn a
	{
		float: right;
		margin-top: 8px;
	}
</style>
<div class="container">
	<div id="debug_spot"></div>
	<div class="row-fluid">
		<div class="span12" >
			<h2>Stage New Tag<small> tag: snippet of html code</small></h2>
		</div>
	</div>
	<div class="row-fluid">
		<form class="form-horizontal" action="/tag/insert" method="post" accept-charset="utf-8" name="new_tag_form">
			<div class="span12 well">
				<input type="hidden" name="username" value="<?php echo $username;?>" />
				<div class="control-group">
					<label class="control-label" for="new_vl_campaign">VL Campaign</label>
					<div class="controls">
						<select id="new_vl_campaign" name="new_vl_campaign" onChange="campaign_selection_handling();" class="span7">
							<option></option>
							<option value="0">No Campaign Selected</option>
<?php
							foreach($vl_campaigns as $campaign)
							{
								echo '<option value="'.trim($campaign["c_id"]).'">'.trim($campaign["full_campaign"]).'</option>';
							}							
?>						</select>
					</div>
				</div>
				<div class="control-group ttd_tag_controls">
					<div class="controls">
						<span>
							<input type="radio" name="td_tag_type" checked="true" value ="0" onclick="handle_tag_type_radio_change(this.value);" style="position:relative;top:-4px;"> RTG Tag  &nbsp;
							<input type="radio" name="td_tag_type" value ="2" onclick="handle_tag_type_radio_change(this.value);" style="position:relative;top:-4px;"> Conversion Tag &nbsp;
							<input type="radio" name="td_tag_type" value ="3" onclick="handle_tag_type_radio_change(this.value);" style="position:relative;top:-4px;"> Custom Tag &nbsp;&nbsp; &nbsp; || &nbsp; &nbsp; 
							<input type="radio" name="td_tag_type" value ="1" onclick="handle_tag_type_radio_change(this.value);" style="position:relative;top:-4px;"> AdVerify Tag  
						</span>
						<br>
						<div><br></div>
					</div>
				</div>
				<div class="control-group ttd_tag_controls">
					<label class="control-label" for="file_name">Tracking Tag Filename</label>
					<div class="controls">
						<input type="hidden" name="filename" id="file_name" class="span7"/>
					</div>
					&nbsp;<span id="download_tag" class="hide_content"><a href="#" ><i class="icon-download-alt"></i>&nbsp;Download Tag</a></span>
				</div>
				<div id="new_tracking_tag_file_container" class="control-group">
					<label class="control-label" for="file_name">New Filename</label>
					<div class="controls">
						<span id="tracking_file_prepend_string" style="color: #ad9e9e;"></span>&nbsp;<input type="text" name="new_tracking_tag_file" id="new_tracking_tag_file" class="span7"/><span style="color: #ad9e9e;"><span id="adverify_tag_file_appender"></span>.js</span>
						<button id="new_tracking_tag_file_save_btn" type="button" class="btn btn-primary" style="margin-left: 25px;"><i class="icon-thumbs-up icon-white"></i> Save</button>
						<br/>
						<span id="new_tracking_tag_file_status"></span>
					</div>
				</div>
				<div class="control-group ttd_tag_controls" style="margin-bottom:10px;">
					<label class="control-label" for="add_to_td_button"></label>
					<div class="controls">
						<span id="ttd_tag_controls" style="visibility:hidden;">
							<button type="button"  id="add_to_td_button" class="btn btn-small btn-info" onclick="add_to_tradedesk();" data-loading-text="Loading..."><i class="icon-retweet icon-white"></i>
								<span>TD Tag</span>
							</button>
							<span id="td_tooltip" class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="TD Tag" data-content="Add a tracking tag to tradedesk. Add that tag to a datagroup, that datagroup to an audience, and then link that audience to the Managed/RTG or Prop(Adverify) Adgroups for that campaign. Then display that tag below."><i class="icon-info-sign "></i></span>
							<span id="insert_tag_status" style="margin-left:25px;"></span>
							<br>
						</span>
						<span id="no_adgroup_warning" style="visibility:hidden;margin-top: 20px;" class="label label-important">Adgroup does not exist for this campaign!</span>
						<br>						
					</div>
				</div>
				<div id="tag_file_content_display" class="control-group">
					<div class="controls">
						<span class="show_content"><i class="icon-plus-sign"></i>&nbsp;Show tracking tag file content</span>
						<span class="hide_content"><i class="icon-minus-sign"></i>&nbsp;Hide tracking tag file content</span>
						<span id="download_tag_file_content" class="hide_content"><a href="#"><i class="icon-download-alt"></i>&nbsp;Download Tag File Content</a></span>
						<span id="assign_existing_tags" style="display: inline;">
							<button type="button" id="reassign_audience" class="btn btn-small btn-warning" onclick="assign_existing_tags_to_campaign();" data-loading-text="Loading..."><i class="icon-retweet icon-white"></i><span> Assign Existing Tags To Campaign</span></button>
							<span id="assign_existing_tags_tooltip" class="tool_poppers" href="#" data-toggle="popover" data-trigger="hover" data-placement="right" title data-original-title="TD Assign Tags" data-content="Assign existing RTG and conversion tag from selected file to the selected campaign internal (not in TTD)"><i class="icon-info-sign "></i></span>
						</span>
						<span id="assign_existing_tags_status"></span>
					</div>
				</div>
				<div id="tag_file_content_display_container"></div>
				<div class="control-group ttd_tag_controls">
					<label class="control-label" for="new_tag">Tag</label>
					<div class="controls">
						<textarea name="newTag" id="new_tag"  rows="6" class="span7"></textarea>
					</div>
				</div>
				<div class="control-group ttd_tag_controls">
					<div class="controls">
						<div class="form-inline">
							<button type="button" class="btn btn-primary" onclick="insert_new_tag();"><i class="icon-folder-open icon-white"></i> Stage New Tag</button>&nbsp;<span id="tag_load_status" class="label label-important"></span>
						</div>
					</div>
				</div>
			</div>
		</form>   
	</div>
	<div class="row-fluid">
		<div id="loader_bar" class="span12" style="height: 25px;"></div>
	</div>
	<div class="row-fluid">
		<div class="span12" >
			<div>
				<h2>Staged Tags<small> manage staged tags below before writing js files on server </small></h2>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div>
			<div class=" well" style="word-wrap:break-word;">
				<div class="form-inline pull-right">
					<span id="reload_status"></span>
					<span class="btn btn-danger" onclick="update_server_files();" title="Assemble all the staged tags from VL database into js files on the server"><i class="icon-file icon-white"></i> Write all active js files</span>
				</div>
				<ul class="nav nav-pills">
					<li id="active_inactive_tab" class=""><a onclick="make_active_inactive_tag_table_show(true);">Show All Active/Inactive Tags</a></li>
				</ul>
				<div id="displayed_table">
					<table id="active_inactive_tags_table" class="table table-striped table-hover table-condensed cell-border">
						<thead>
							<tr>
								<th>
									ID
								</th>
								<th>
									Tag Type
								</th>
								<th>
									Advertiser : Campaigns
								</th>
								<th>
									Filename
								</th>
								<th>
									Is_Active
								</th>
								<th>
									Actions
								</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
					<br/>
				</div>
			</div>
		</div>
	</div>
</div> <!-- div container -->
<!-- Modal -->
<div id="get_tag_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true" >
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="get_tag_header">Copy / Paste <small>landing page code</small></h3>
	</div>
	<div id="download_tag_btn">
		<a id="download_this_tag" class="btn btn-mini btn-inverse" type="button" href="#" target="_blank" alt="Download tag script" title="Download tag script">
			<i class="icon-download-alt icon-white"></i>
		</a>
	</div>
	<div id="get_tag_body" class="modal-body">
		<p>Tag here</p>
	</div>
	<div id="get_tag_footer" class="modal-footer">
		<button class="btn btn-primary" data-dismiss="modal" aria-hidden="true"><i class="icon-thumbs-up icon-white"></i> OK</button>
	</div>
</div>
<!-- Modal -->
<div id="update_tag_modal" class="modal hide fade" tabindex="-1" role="dialog"  aria-hidden="true" >
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="update_tag_header">Edit Tag</h3>
	</div>
	<div id="update_tag_body" class="modal-body">
		<p>Form Here</p>
	</div>
	<div id="update_tag_footer" class="modal-footer">
		<button class="btn btn-primary" data-dismiss="modal" aria-hidden="true" onclick="load_tag_update();"><i class="icon-thumbs-up icon-white" ></i> Save</button>
		<button class="btn btn-danger" data-dismiss="modal" aria-hidden="true"><i class="icon-ban-circle icon-white"></i> Close</button>
	</div>
</div>
<script>
	var timer;//this is used for the async loading bar

	function make_active_inactive_tag_table_show()
	{
		$('#displayed_table').hide();
		toggle_loader_bar(true,1);
		var data_url = "/tag/active_inactive_tags/";
		$("#active_inactive_tab").attr("class","active");
		var return_data;

		$.ajax({
			type: "GET",
			url: data_url,
			async: true,
			data: {  },
			dataType: 'html',
			error: function(){
				set_formload_status('important', 'error 564515');
			},
			success: function(msg){
				if ( !$.fn.DataTable.isDataTable( '#active_inactive_tags_table' ) )
				{
					$('#active_inactive_tags_table > tbody').prepend(msg);
					$('#active_inactive_tags_table').DataTable({}).order( [ 0, 'desc' ] ).draw();
				}
				else
				{
					$('#active_inactive_tags_table').dataTable().fnDestroy();
					$('#active_inactive_tags_table > tbody').html("").prepend(msg);
					$('#active_inactive_tags_table').DataTable({}).order( [ 0, 'desc' ] ).draw();
				}

				$('#displayed_table').show();
				$('html, body').animate({
					scrollTop: $('#new_tag').offset().top
				},1000);
				toggle_loader_bar(false); 
			}
		});
	}

	function check_tradedesk()
	{
		var campaign_id = document.getElementById("new_vl_campaign").value;	
		var data_url = "/tradedesk/check_campaign_advertiser/";

		$.ajax({
			type: "GET",
			url: data_url,
			async: false,
			data: { c_id: campaign_id},
			dataType: 'html',
			error: function(){

				set_formload_status('important', "error 4278271: Couldn't find selected campaign on tradedesk");
			},
			success: function(msg){
				var returned_data_array = eval('('+msg+')' )
				if(returned_data_array.success)
				{
					if(returned_data_array.exists == true)
					{
						$(".ttd_tag_controls").show();
						document.getElementById("ttd_tag_controls").style.visibility = "visible";
					}
					else
					{						
						document.getElementById("ttd_tag_controls").style.visibility = "hidden";
						document.getElementById("no_adgroup_warning").style.visibility = "hidden";
						$(".ttd_tag_controls").hide();
						return 0;
					}
				}
				else
				{
					set_formload_status('important', 'there was an error: 5er615');
				}
				toggle_loader_bar(false,null,'update_sales_dropdown'); 
			}
		});

		var data_url = "/tradedesk/check_campaign_adgroup/";
		 $.ajax({
			type: "GET",
			url: data_url,
			async: false,
			data: { c_id: campaign_id},
			dataType: 'html',
			error: function(){
				set_formload_status('important', "error 428821: Couldn't find campaign adgroups");
			},
			success: function(msg){
				var returned_data_array = eval('('+msg+')' )
				if(returned_data_array.success)
				{
					if(returned_data_array.exists == true)
					{
						document.getElementById("no_adgroup_warning").style.visibility = "hidden";
					}
					else
					{
						document.getElementById("no_adgroup_warning").style.visibility = "visible";

					}
				}
				else
				{
					set_formload_status('important', 'there was an error: 5er615');
				}
				toggle_loader_bar(false,null,'update_sales_dropdown'); 
			}
		});
	}

	function add_to_tradedesk()
	{
		set_formload_status(null);
		var ttd_tag_type = $('input[name=td_tag_type]:checked').val();
		var existing_tags_info = get_existing_tags_info_for_campaign_from_ttd();
		if (typeof existing_tags_info !== 'undefined')
		{
			var warning_message = "";
			
			if (ttd_tag_type == 2)
			{
				if (typeof existing_tags_info.conversion_tags_count !== 'undefined' && existing_tags_info.conversion_tags_count >= 2)
				{
					alert("You can't append more than 2 conversion tags");
					return;
				}
				if (typeof existing_tags_info.conversion_tags_count !== 'undefined' && existing_tags_info.conversion_tags_count == 1)
				{
					warning_message = "You are about to append one more conversion tag";
				}
			}else if(ttd_tag_type == 0)
			{
				if (typeof existing_tags_info.rtg_audience_id !== 'undefined' && existing_tags_info.rtg_audience_id != '' && existing_tags_info.rtg_audience_id != null)
				{
					warning_message = "You are about to overwrite an existing RTG tag\n";
				}
			}
			
			if (warning_message !== '')
			{
				warning_message = warning_message + "\n" + "Are you sure you want to continue?";
				var resp = confirm(warning_message);
				if (resp !== true)
				{
					return;
				}
			}			
		}
		var tag_file_id = document.getElementById('file_name').value;		
		if (tag_file_id == '' || tag_file_id == 'new_tracking_tag_file')
		{
			set_formload_status('important',"Invalid file name");
			toggle_loader_bar(false,null,'add_to_tradedesk');
			return;
		}
		
		
		var campaign_id = document.getElementById("new_vl_campaign").value;
		toggle_loader_bar(true,0.5,'add_to_tradedesk');
		var data_url = "/tradedesk/post_tag_and_return_tag/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {
				c_id: campaign_id, 
				tag_file_id: tag_file_id, 
				tag_type: ttd_tag_type
			},
			dataType: 'json',
			error: function(jqXHR, textStatus, error){
				set_formload_status('important', "error 9411852: Couldn't add tag to Tradedesk.");
			},
			success: function(data, textStatus, jqXHR){
				if(data.success)
				{
					$("#new_tag").val(data.tags);
				}
				else
				{
					set_formload_status('important', data.err_msg);
				}
				toggle_loader_bar(false,null,'add_to_tradedesk'); 
			}
		});

	}
	function insert_new_tag()
	{
		toggle_loader_bar(true,1);
		var tag_file_id = document.new_tag_form.filename.value;
		var tag_code = document.new_tag_form.newTag.value;
		
		if (tag_file_id == "" || tag_file_id == 'new_tracking_tag_file')
		{
			$("#tag_load_status").html('File not selected');
			toggle_loader_bar(false);
			return;
		}else if (tag_code == "")
		{
			$("#tag_load_status").html('Tag is empty');
			toggle_loader_bar(false);
			return;
		}else{
			$("#tag_load_status").html('');
		}
		
		var data_url = "/tag/insert";
		var ttd_tag_type = $('input[name=td_tag_type]:checked').val();
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { 
				campaign: document.new_tag_form.new_vl_campaign.value, 
				tag_file_id: tag_file_id, 
				newTag: tag_code, 
				username: document.new_tag_form.username.value, 
				tag_type: ttd_tag_type
			},
			dataType: 'html',
			error: function(){
				set_reload_status('important', 'error 94121');
			},
			success: function(msg){ 
				if (msg.indexOf("Error : ") !== -1)
				{
					set_reload_status('important', msg);
				}
				else
				{
					set_reload_status('success', 'tags is loaded: SUCCESS');
					$("#new_tag").val("");
					if ($('#active_inactive_tags_table').length)
					{
						$('#active_inactive_tags_table > tbody').prepend(msg);

						if ( !$.fn.DataTable.isDataTable( '#active_inactive_tags_table' ) ) {
							$('#active_inactive_tags_table').DataTable({}).order( [ 0, 'desc' ] ).draw();
						}

						$('#displayed_table').show();
						$('html, body').animate({
							scrollTop: $('#new_tag').offset().top
						},1000);
					}
					pull_all_tags_for_file();
				}
				toggle_loader_bar(false);
			}
		});
	}

	function set_formload_status(label, copy)
	{   
		if(label === null)
		{
			document.getElementById("insert_tag_status").innerHTML ='';
		}else
		{
			document.getElementById("insert_tag_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
	}

	function update_server_files()
	{
		ajax_update_server();
	}

	function set_reload_status(label, copy)
	{   
		if(label === null)
		{
			document.getElementById("reload_status").innerHTML ='';
		}else
		{
			document.getElementById("reload_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
	}

	function ajax_update_server()
	{
		var data_url = "/tag/publish";
		toggle_loader_bar(true,10);
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { },
			dataType: 'html',
			error: function(msg){
				set_reload_status('important', 'error 5612105');
				toggle_loader_bar(false); 
			},
			success: function(msg){ 
				if(msg === 'SUCCESS')
				{
					set_reload_status('success', 'files updated: '+msg);
				}
				else
				{
					set_reload_status('important', 'there was a problem: '+msg);
				}
				make_active_inactive_tag_table_show();
				toggle_loader_bar(false);			   
			}
		});
	}

	function activate_tag(tag_id,advertiser_id,source)
	{
		var data_url = "/tag/activate/"+tag_id+"/"+advertiser_id;
		
		if (typeof source === 'undefined' || source == '' || source == null)
		{
			toggle_loader_bar(true,1);
		}else{
			$("#tag_file_content_display_container .update_status").html("").hide();			
		}

		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { },
			dataType: 'html',
			error: function(){
				set_reload_status('important', 'error 4591');
				toggle_loader_bar(false); 
			},
			success: function(msg){ 
				if (typeof source !== 'undefined' && source === 'tag_file_content')
				{
					if (msg.indexOf('FAILED') !== -1)
					{
						msg = msg.substring(msg.indexOf("FAILED")+6);
						$("#"+tag_id+"_status").html(msg);
						$("#"+tag_id+"_status").removeClass("label-success").addClass("label-important").show();
					}else{
						$("#"+tag_id+"_activate_button").hide();
						$("#"+tag_id+"_deactivate_button").show();
						$("#"+tag_id+"_status").html("Tag activated successfully");
						$("#"+tag_id+"_status").removeClass("label-important").addClass("label-success").show();
						display_assign_existing_tags_option();
					}
				}else{
					if (msg === 'FAILED')
					{
						set_reload_status('important', 'there was a problem: FAILED');
					}
					else
					{
						set_reload_status('success', 'tag activated: SUCCESS');
						$('#active_inactive_tags_table > tbody').children('#row_id_'+tag_id).replaceWith(msg);
					}

					toggle_loader_bar(false);
				}
			}
		});
	}

	function deactivate_tag(tag_id,advertiser_id,source)
	{
		var data_url = "/tag/deactivate/"+tag_id+"/"+advertiser_id;
		
		if (typeof source === 'undefined' || source == '' || source == null)
		{
			toggle_loader_bar(true,1);
		}else{
			$("#tag_file_content_display_container .update_status").html("").hide();			
		}

		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { },
			dataType: 'html',
			error: function(){
				set_reload_status('important', 'error 892531');
				toggle_loader_bar(false);
			},
			success: function(msg){
				if (typeof source !== 'undefined' && source === 'tag_file_content')
				{
					if (msg === 'FAILED')
					{
						$("#"+tag_id+"_status").html(msg);
						$("#"+tag_id+"_status").removeClass("label-success").addClass("label-important").show();
					}else{
						$("#"+tag_id+"_deactivate_button").hide();
						$("#"+tag_id+"_activate_button").show();
						$("#"+tag_id+"_status").html("Tag deactivated successfully");
						$("#"+tag_id+"_status").removeClass("label-important").addClass("label-success").show();
						display_assign_existing_tags_option();
					}
				}else{
					if (msg === 'FAILED')
					{
						set_reload_status('important', 'there was a problem: FAILED');
					}
					else
					{
						set_reload_status('success', 'tag deactivated: SUCCESS');
						$('#active_inactive_tags_table > tbody').children('#row_id_'+tag_id).replaceWith(msg);
					}
					toggle_loader_bar(false);
				}
			}
		});
	}

	function pop_up_tag(tag_id,adv_id,tag_type){
		ajax_get_client_js(tag_id,adv_id,tag_type);
	}

	function ajax_get_client_js(tag_id,adv_id,tag_type)
	{
		var data_url = "/tag/getlink/"+tag_id+"/"+adv_id;
		toggle_loader_bar(true,0.5);

		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { },
			dataType: 'html',
			error: function(){
				set_reload_status('important', 'error 1562154');
				toggle_loader_bar(false); 
			},
			success: function(msg){ 
				$("#download_tag_btn a").attr('href','/tag/download_tags/'+adv_id+'/-1/'+tag_id+'/'+tag_type);
				$("#get_tag_body").html('Send the code below to landing page\'s webmaster<pre>'+msg+'</pre>');
				$('#get_tag_modal').modal();
				toggle_loader_bar(false);
			}
		});
	}


	function pop_up_tag_update(tag_id,adv_id)
	{
		toggle_loader_bar(true,1);
		var data_url = "/tag/update_form/"+tag_id+"/"+adv_id;
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { },
			dataType: 'html',
			error: function(){
				set_reload_status('important', 'error 56156');
				toggle_loader_bar(false);
			},
			success: function(msg){ 
				$('#update_tag_modal').modal();
				$("#update_tag_body").html(msg);
				toggle_loader_bar(false);
			}
		});
	}

	function load_tag_update()
	{
		var data_url = "/tag/update/";
		var ttd_tag_type = $('input[name=edit_td_tag_type]:checked').val();		
		toggle_loader_bar(true,0.1);
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: { 
				newTag: document.update_form.newTag.value,
				tag_file_id: document.update_form.update_form_tracking_tag_file.value,
				adv_id: document.update_form.update_form_adv_id.value,
				username: document.update_form.username.value,
				tagID: document.update_form.tagID.value,
				tag_type: ttd_tag_type
			},
			dataType: 'html',
			error: function(){
				set_reload_status('important', 'error 96314');
				toggle_loader_bar(false); 
			},
			success: function(msg){ 
				if(msg.indexOf("Error : ") !== -1)
				{
					set_reload_status('important', msg);
				}else
				{
					set_reload_status('success', 'tag updated: SUCCESS');
					$('#active_inactive_tags_table > tbody').children('#row_id_'+document.update_form.tagID.value).replaceWith(msg);
					pull_all_tags_for_file();
				}
				toggle_loader_bar(false);
			}
		});
	}

	function campaign_selection_handling()
	{
		var campaign_dropdown_object = document.getElementById('new_vl_campaign');
		var segment = campaign_dropdown_object.options[campaign_dropdown_object.selectedIndex].text.split(":",1);
		segment = encodeURIComponent(jQuery.trim(segment).replace(/ /g,"_").replace(")","").replace("'",""));
		check_tradedesk();
		if(document.getElementById("ttd_tag_controls").style.visibility != "visible")
		{
			document.getElementById("no_adgroup_warning").style.visibility = "hidden";
			$('input[name=td_tag_type][value="0"]').prop('checked', true);
			handle_tag_type_radio_change(0);
		}
		else
		{
			handle_tag_type_radio_change($('input[name=td_tag_type]:checked').val());
		}
		clear_all_status();
		document.getElementById("new_tag").innerHTML = "";
	}

	function toggle_loader_bar(is_on, expected_seconds_to_completion)
	{
		if(is_on)
		{
			clear_all_status();
			$("#loader_bar").html('<div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div>');
			run(expected_seconds_to_completion);
		}else
		{
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

	function clear_all_status(){
		set_formload_status(null);
		set_reload_status(null);
	}

	function stop_progress_timer() 
	{
		clearInterval(timer);
	}

	function init_page()
	{
		$('#td_tooltip').popover();
	}

	function handle_tag_type_radio_change(tag_type_val)
	{				
		set_formload_status(null);
		if(tag_type_val == 1 || tag_type_val == 3)
		{
			$('#file_name').select2("val", "");
			$("#new_tracking_tag_file_container").hide();
			$("#new_tracking_tag_file_container input").val("");
			$("#new_tag").val("");
			$(".show_content").hide();
			$("#tag_file_content_display").hide();
			$("#tag_file_content_display_container").hide();
		}
		
		if(tag_type_val == 3)
		{
			$("#add_to_td_button").prop("disabled",true);
		}else{
			$("#add_to_td_button").prop("disabled",false);
		}
		
		if(tag_type_val == 1)
		{
			$("#adverify_tag_file_appender").html("_adverify");
		}else{
			$("#adverify_tag_file_appender").html("");
		}
			
	}
	
	function pull_all_tags_for_file()
	{
		var tag_file_id = $("#file_name").val();
		var data_url = "/tag/all_tags_for_advertiser_file";
		$.ajax({
			type: "POST",
			url: data_url,
			async: false,
			data: {
				tag_file_id:tag_file_id
			},
			dataType: 'html',
			success: function(msg){
				if(msg !== "")
				{
					$("#tag_file_content_display_container").html(msg);
					$("#tag_file_content_display").show();
				}else{
					$("#tag_file_content_display").hide();
					$("#tag_file_content_display_container").html("");
				}
			}
		});
	}

	function update_tag_type(tag_id,adv_id)
	{
		$("#tag_file_content_display_container .update_status").html("").hide();
		$("#tag_file_content_display_container .update_status_tooltip").hide();
		$("#"+tag_id+"_save_btn").prop("disabled",true);
		var temp = setInterval(function(){
			clearInterval(temp);
			var tag_type_value = $("input[name='"+tag_id+"_tag_type']:checked").val();
			var tag_code = $("#"+tag_id+"_content_file_tag").val();
			
			$.ajax({
				type: "POST",
				url: "/tag/update_tag_type_for_tag_id/",
				async: false,
				data: {
					adv_id:adv_id,
					tag_type:tag_type_value,
					tag_code:tag_code,
					tag_id:tag_id
				},
				dataType: 'json',
				success: function(result){ 
					if (result.status === 'success')
					{
						$("#"+tag_id+"_status").html("Tag updated successfully");
						$("#"+tag_id+"_status").removeClass("label-important").addClass("label-success").show();
						display_assign_existing_tags_option();
					}
					else
					{
						$("#"+tag_id+"_content_file_tag").val(result.tag_code);
						 $("#"+tag_id+"_"+result.tag_type+"_tag_type").prop("checked", true);

						$("#"+tag_id+"_status").html(result.error_message);
						$("#"+tag_id+"_status").removeClass("label-success").addClass("label-important").show();
					}
					$("#"+tag_id+"_save_btn").prop("disabled",false);
				}
			});
		},100);
	}
	
	function get_advertiser_directory_name()
	{
		var campaign_id = $("#new_vl_campaign").val();
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
	
	function display_assign_existing_tags_option()
	{
		var result = is_tag_file_push_friendly_for_advertiser();
		if (result != null && result.status === "success"){
			$("#assign_existing_tags").show();
		}else{
			$("#assign_existing_tags").hide();
		}
	}
	
	function is_tag_file_push_friendly_for_advertiser()
	{
		var resp = null;
		var campaign_id = $("#new_vl_campaign").val();
		var tag_file_id = $("#file_name").val();
		var data_url = "/tag/is_tag_file_push_friendly_for_advertiser/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: false,
			data: {
				campaign_id:campaign_id,
				tag_file_id:tag_file_id
			},
			dataType: 'json',
			success: function(result){
				resp = result;
			}
		});
		return resp;
	}
	
	function assign_existing_tags_to_campaign()
	{
		/*var result = is_tag_file_push_friendly_for_advertiser();
		
		if (result != null && result.rtg_count >= 1 || result.conversion_count >= 1)
		{
			var existing_tags_info = get_existing_tags_info_for_campaign_from_ttd();
			if (typeof existing_tags_info !== 'undefined')
			{
				var warning_message = "";

				if (result.conversion_count >= 1 && typeof existing_tags_info.conversion_tags_count !== 'undefined' && existing_tags_info.conversion_tags_count >= 2)
				{
					alert("You can't append more than 2 conversion tags");
					return;
				}

				if (result.rtg_count >= 1 && typeof existing_tags_info.rtg_audience_id !== 'undefined' && existing_tags_info.rtg_audience_id != null && existing_tags_info.rtg_audience_id != '')
				{
					warning_message = "You are about to overwrite an existing RTG tag\n";
				}

				if (result.conversion_count >= 1 &&  typeof existing_tags_info.conversion_tags_count !== 'undefined' && existing_tags_info.conversion_tags_count == 1)
				{
					warning_message = warning_message + "You are about to append one more conversion tag";
				}

				if (warning_message !== '')
				{
					warning_message = warning_message + "\n" + "Are you sure you want to continue?";
					if (!confirm(warning_message)){
						return;
					}
				}			
			}
		}*/
		
		$("#assign_existing_tags_status").hide().html("");
		var campaign_id = $("#new_vl_campaign").val();
		var tag_file_id = $("#file_name").val();
		var data_url = "/tag/assign_existing_tags_to_campaign/";
		$.ajax({
			type: "POST",
			url: data_url,
			async: false,
			data: {
				campaign_id:campaign_id,
				tag_file_id:tag_file_id
			},
			dataType: 'json',
			success: function(result){
				if (result.status === "fail"){
					$("#assign_existing_tags_status").html("<span class='label label-important'>"+result.err_msg+"</span>").show();
				}else{
					$("#assign_existing_tags").hide();
					
					var success_html = '<span style="color: #b94a48;font-weight: 700;text-decoration: underline;">ACTION REQUIRED:</span>';
					var tradedesk_url = "<?php echo TRADEDESK_WEB_APP_URL; ?>";
					if (typeof result.ttd_adgroup_ids !== "undefined" && result.ttd_adgroup_ids.length > 0)
					{
						success_html = success_html + "<br/><span style='color:#333333;font-size:12px;'>Assign RTG in Bidder - ";						
						for(var i=0;i<result.ttd_adgroup_ids.length;i++)
						{
							success_html = success_html + "<a target='_blank' href='"+tradedesk_url+"/adgroups/detail/"+result.ttd_adgroup_ids[i]+"'>"+result.ttd_adgroup_ids[i]+"</a>, ";
						}
						success_html = success_html.replace(/,\s*$/, "") + "</span>";
					}
					if (typeof result.ttd_campaign_id != 'undefined' && result.ttd_campaign_id != '')
					{
						success_html = success_html +  "<br/><span style='color: #333;font-size:12px;'>Assign CONV in Bidder - <a target='_blank' href='"+tradedesk_url+"/campaigns/detail/"+result.ttd_campaign_id+"#settings'>"+result.ttd_campaign_id+"</a></span><br/>";
					}
					$("#assign_existing_tags_status").html(success_html).show();
				}
				
			}
		});
	}
	
	function get_existing_tags_info_for_campaign_from_ttd()
	{		
		var campaign_id = $("#new_vl_campaign").val();
		var data_url = "/tag/get_rtg_and_conversion_tag_info_from_ttd/";
		var existing_tag_info = {};
		$.ajax({
			type: "POST",
			url: data_url,
			async: false,
			data: {
				campaign_id:campaign_id
			},
			dataType: 'json',
			success: function(result){
				if (result.status === "success"){
					existing_tag_info = result.existing_tags_info;
				}
			}
		});
		return existing_tag_info;
	}
	
	
</script>
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
<script src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script>
	window.onload = init_page();
	$(document).ready( function () {
		$('#new_vl_campaign').select2({
			placeholder: "Select a campaign"
		});
		$('#new_vl_campaign').change(function(){
			$('#file_name').select2("val", "");
			$("#new_tag").val("");
			$("#tag_file_content_display").hide();
			$("#tag_file_content_display_container").hide();
			$("#assign_existing_tags_status").hide();
			$("#assign_existing_tags").hide();
			$("#new_tracking_tag_file_container").hide();
			set_formload_status(null);
		});
		$('#file_name').select2({
			placeholder: "Select Tracking Tag File",
			minimumInputLength: 0,
			multiple: false,
			ajax: {
				url: "/tag/get_select2_tracking_tag_file_names/",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					var td_tag_type = $('input[name=td_tag_type]:checked').val();
					var campaign_id = $("#new_vl_campaign").val();
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 100,
						page: page,
						campaign_id:campaign_id,
						td_tag_type:td_tag_type
					};
				},results: function (data) {
					return {results: data.results, more: data.more};
				},error: function(jqXHR, textStatus, error){
					console.log(error);
				}                        
			},
			formatSelection: function(data) {
				var download_tag_link = $("#download_tag_file_content a");
				if ($(download_tag_link).length)
				{
					$("#download_tag_file_content a").attr("href","/tag/download_tag_file_content/"+data.id);
				}
				var download_tag = $("#download_tag a");
				var adv_name_str = $("#new_vl_campaign").select2('data').text; 
				var adv_name_split = adv_name_str.split(":");
				var adv_name = adv_name_split[0].replace(" ","");
				var adv_name_clean = adv_name.replace(/[^\w]/gi, '');
				if ($(download_tag).length)
				{				    
					$("#download_tag a").attr("href","/tag/download_tag_file/"+adv_name_clean+"/"+data.text);	
				}
				return data.text;
			}
		});
		$("#file_name").change(function(){
			$("#tag_file_content_display .hide_content").trigger('click');
			$("#tag_file_content_display_container").html("");
			$("#tag_file_content_display_container").hide();
			$("#new_tracking_tag_file_status").hide().html("");
			$("#assign_existing_tags_status").hide();
			set_formload_status(null);
			
			if ($(this).val() === 'new_tracking_tag_file')
			{
				var directory_name = get_advertiser_directory_name();
				
				if (typeof directory_name !== 'undefined' && directory_name !== '')
				{
					$("#new_tracking_tag_file_container #tracking_file_prepend_string").html(directory_name+"/");
					$("#new_tracking_tag_file_container").show();
					$("#download_tag").hide();
					$("#new_tracking_tag_file_container input").focus();
					$("#tag_file_content_display").hide();
					$("#assign_existing_tags").hide();					
				}
			}
			else
			{
				$("#new_tracking_tag_file_container").hide();
				$("#download_tag").show();
				$("#new_tracking_tag_file_container input").val("");				
				$("#new_tracking_tag_file_container #tracking_file_prepend_string").html("");
				display_assign_existing_tags_option();
				pull_all_tags_for_file();
			}
		});
		$("#new_tracking_tag_file_save_btn").click(function(){
			var campaign_id = $("#new_vl_campaign").val();
			var new_tracking_tag_file_name = $("#new_tracking_tag_file").val();
			new_tracking_tag_file_name = new_tracking_tag_file_name.replace(/ /g,"_");
			
			$("#new_tracking_tag_file_status").hide().html("");
			if (new_tracking_tag_file_name == '' || !(/\S/.test(new_tracking_tag_file_name)) || new_tracking_tag_file_name.match(/^[^a-zA-Z0-9]+$/))
			{
				$("#new_tracking_tag_file_status").html("Invalid file name").css("color","red").show();
				return;
			}
			
			new_tracking_tag_file_name = $("#tracking_file_prepend_string").html() + new_tracking_tag_file_name + $("#adverify_tag_file_appender").html();
			
			$.ajax({
				type: "POST",
				url: '/tag/create_new_tracking_tag_file',
				async: false,
				dataType: 'json',		
				data: {
					campaign_id:campaign_id,
					tracking_tag_file_name:new_tracking_tag_file_name
				},
				success: function(data, textStatus, jqXHR){
					if(data.is_success !== true)
					{
						var error_msg = (typeof data.errors !== 'undefined') ? data.errors : "Error !!!";
						$("#new_tracking_tag_file_status").html(error_msg).css("color","red").show();
					}
					else
					{
						$("#new_tracking_tag_file_status").hide().html("");
						var tracking_tag_file_select2_option = {'id':data.id,'text':new_tracking_tag_file_name+".js"};
						$('#file_name').select2('data', tracking_tag_file_select2_option);
						$("#new_tracking_tag_file_container").hide();
						$("#new_tracking_tag_file_container input").val("");
					}
				}
			});
		});
		
		$("#tag_file_content_display .show_content").click(function(){
			$("#tag_file_content_display .show_content").hide();
			$("#tag_file_content_display .hide_content").show();
			$("#tag_file_content_display_container").show();
		});
		
		$("#tag_file_content_display .hide_content").click(function(){
			$("#tag_file_content_display .hide_content").hide();
			$("#tag_file_content_display_container").hide();
			$("#tag_file_content_display .show_content").show();			
		});
		$('#assign_existing_tags_tooltip').popover();
	});
</script>
</body>
</html>