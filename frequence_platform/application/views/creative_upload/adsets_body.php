<?php
function get_campaign_prefix($campaign)
{
	$prefix = '';
	if($campaign["ignore_for_healthcheck"] == 1)
	{
		$prefix = '(ignored) ';
	}
	return $prefix;
}
?>

<style>
.gallery{
	z-index:100000;
	width:100%;
	height:1000px;}
.x_pane_hide{
	display: block;
	position: absolute;
	left:-9999px;
}
#upl_accordion{
min-height:300px;
}


<!--Styling for variables -->
.micro input[type="text"] {
	height: 12px ;
	font-size: 10px ;
	line-height: 12px ;
}

a {text-decoration:none !important;}

.micro{
	margin-bottom: 2px !important;
	line-height: 10px !important;
	font-size: 10px !important;
}

div.micro div {
	font-size: 9px !important;
}

.fp_io_button{
	padding-bottom: 0px;
}
div#myModal > div.modal-body
{
	overflow: hidden;
}

.sideNav
{
	/*padding: 10px 0px 0px 40px;
	position: fixed;*/
/*	left: 0px;
	top: 0px;  same as .content top*/
	/*bottom: 0px;*/
	width: 270px;
	/*background-color: #f1f1f1;*/
	border: 1px solid #bbc0c4; /*z-index: 1020;  10 less than .navbar-fixed to prevent any overlap */
	overflow-x: hidden;
	overflow-y: auto;
	height: 800px;
	float:left;
	padding-left:20px;
	margin-bottom:40px;

}

.content
{
	float: right;
	overflow: auto;

}

.variables_ui_zone
{
	height: 800px;
}

#new_adset_box
{
	margin-top:3px;
	margin-bottom:3px;
}

.show-for-io
{
	float: right;
	width: 117px;
	font-size: 14px;
}
#adset_requests_preview_modal {
	width: 60%;
	left: 20%;
	margin-left: 0px;
	min-width: 400px;
}
#creative_request_link{
	position: relative;
	float: right;
	right: 20px;
	font-size: 12px;
	display:none;
}
#creative_request_link a{
	text-decoration: underline !important;
}
#edit_variation:hover
{
	color:#d4d4d4;
}

</style>

<!--?php $this->load->view("dfa/vl_hd_view_006");?-->
<div class="container">
<form id="fileupload" action="/creative_uploader/multi_upload/" method="POST" enctype="multipart/form-data" class="">
<input type="hidden" name="builder_version" value="">
<h2>Adset Center <small> adsets, creatives, ad tags and all sorts of other stuff you've never heard of</small></h2>
<div class="row">
	<div class = "span12">
				<div class="row">
					<div class="span5">
						<label for="campaign_select">VL Campaign</label>
						<input id="campaign_select" onchange="handle_ui_campaign_change(this.value, null, null);" class="span4">
					</div>
					<div class="span7">
						<label for="adset_select">Adset Name <a href="#myModal" id ="link_adset_button" style="visibility:hidden;position:relative;right:47px;" role="button" class="btn btn-mini btn-info pull-right" data-toggle="modal"><i class="icon-plus icon-white"></i> Link variation to campaign</a></label> 
						<input id="adset_select" onchange="adset_drop_select(this.value);" class="span4">

						<select class="span2 pull-left"  id="adset_version_select" style="margin-left:10px;width:115px;">
							<option value="0">select adset</option>
						</select>
						<select class="span2 pull-left"  id="adset_version_variation_select" style="margin-left:5px;width:120px;">
							<option value="0">select variation</option>
						</select>
						<span style="font-size:20px;display:none;" id="edit_variation" title="Rename this variation" onclick="open_variation_edit_modal();"><i class="icon-pencil"></i></span>
						<!-- NEW ADSET INPUT -->
						<label for="new_adset_input"></label>	
						<!-- <button type="button" style="display:none;" id="new_version_button" class="btn btn-success" onclick="insert_version();"><i class="icon-plus icon-white"></i> <span>New Version</span></button> -->
						<span id="new_adset_input" style="visibility:hidden;" class="form-search">
							<input class="span4" id="new_adset_box" type="text" placeholder="Adset Name"></input>
							<button type="button"  id="adset_load_button" class="btn btn-success " onclick="insert_adset()" ><i class="icon-plus icon-white"></i> <span>Add</span>
							</button>
						</span>						
						<span id="creative_request_link">
							Creative Request: <a href=""></a>
						</span>
					</div>
				</div>
	</div><!-- span6 -->
</div><!--row-->


<div  class="row-fluid" >
		<div id="page_status" style="height:25px"></div> 
</div>


	 



<div><span style="font-size: 24px; line-height: 40px;margin: 10px 0;font-family: inherit;font-weight: bold;color: inherit;text-rendering: optimizelegibility;">Drag & drop new creative assets to stage</span> <a id="uploader_tooltip" href="#"><i class="icon-question-sign"></i></a></div>
						<div class="row-fluid fileupload-buttonbar ">
									<!-- The fileinput-button span is used to style the file input field as button -->
									<span class="btn btn-inverse fileinput-button">
											<i class="icon-plus icon-white"></i>
											<span>Browse</span>
											<input type="file" name="files[]" multiple>
									</span>
									<button type="submit" class="btn btn-primary start">
											<i class="icon-upload icon-white"></i>
											<span>Stage</span>
									</button>
									<button type="button" class="btn btn-danger delete">
											<i class="icon-trash icon-white"></i>
											<span></span>
									</button>
									<input type="checkbox" class="toggle">
									<button type="button" class="btn btn-success pull-right" onclick="perform_action();" style="margin-left:5px"><i class="icon-share-alt icon-white"></i> Load Stage Assets to CDN</span></button>
									
									
									<div class="fileupload-progress fade">
											<!-- The global progress bar -->
											<div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
													<div class="bar" style="width:0%;"></div>
											</div>
											<!-- The extended global progress information -->
											<div class="progress-extended">&nbsp;</div>
									</div>
									<!-- The loading indicator is shown during file processing -->
									<div class="fileupload-loading"></div>
									
						</div><!-- button bar -->
<div class="row-fluid"><!-- data-toggle="collapse" data-parent="#accordion2" href="#collapseOne" -->
	<a class="accordion-toggle pull-right"  onclick="handle_accordion_event();" style="text-decoration:none;">
		<span id="open_new_assets_button" style="visibility:visible"><i class="icon-plus-sign"></i> View assets</span>
		<span id="close_new_assets_button" style="visibility:hidden"><i class="icon-minus-sign"></i> Hide</span>
	</a>
</div>
		<div class="accordion" id="accordion2" >
			<div id="accordion_group2" class="accordion-group ">
				<div class="accordion-heading" ></div>
				<div id="collapseOne" class="accordion-body collapse" >
					<div id="upl_accordion" class="accordion-inner">
			
						

						<div >
									<!-- The table listing the files available for upload/download -->
											<table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
									</div>

					</div><!-- inner -->
				</div><!-- accordion body -->
			</div><!-- accordion group -->
		</div><!-- accordion -->
<div id="show_for_io_div" class="row-fluid">
	<div class="checkbox show-for-io" >
		<label><input type="checkbox" id="show_for_io" name="show_for_io"> show for IO</label>
	</div>
</div>
<div class="row-fluid">
	<div id="output_panel" class="span12" style="height:inherit;"> 
		<h3 id="">
			<small>Adset: </small>
			<span id="output_adset_name"></span>
			<small>
				<span style="color: black;background-color:#CCFFFF;padding-left:20px;padding-right:20px;cursor:text;" id="adset_link"></span>
				<span id="gallery_link"></span>
				<span id="spec_ad_sample_link"></span>
			</small>
		</h3> 
		<ul class="nav nav-tabs">
			<li id="variables_ui_tab" class="">
				<a href="" data-toggle="tab" onclick="load_variables_ui();">Variables UI</a>
			</li>
			<li id="summary_tab" class="active"><a href="" data-toggle="tab" onclick=dropdown_check($('#adset_select').val())> CDN Summary</a>
			</li>
			<li id="preview_tab" class="">
				<a href="" data-toggle="tab" onclick="show_preview_page();">Preview</a>
			</li>
			<li id="publish_tab" class="">
				<a id="publish_tab_anchor" href="" data-toggle="tab" onclick="show_publish_page();">Publisher</a>
			</li>
			<li id="ad_tags_tab" class="">
				<a href="" data-toggle="tab" onclick="show_ad_tags_page();">Get Ad Tags</a>
			</li>
		</ul>
		
		<div id="output_zone"></div>
		<div id="result_zone"></div>
	</div>
</div>
</form>

</div>


<div id="variables_ui_zone">
</div>




<div id="variables_modal" class="modal hide fade"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
</div>


<div id="myModal" class="modal hide fade"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="link_label">Link variation to Campaign</h3>
	</div>
	<div class="modal-body">
		<label for="campaign_link_select">VL Campaign</label>
		<input id="campaign_link_select" class="span5">
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
		<button id="link_adset_to_campaign_button" class="btn btn-primary" data-dismiss="modal" onClick="link_version_to_campaign();">Save changes</button>
	</div>
</div>


<!-- modal popup for version insert -->
		<div id="modal_version_box" class="modal hide fade">
			<div id="version_box_header" class="modal-header">
				<button type="button" onclick="reset_version_index()" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
				<h3>New Adset Version</h3>
			</div>
			<div class="modal-body">
				<span id="modal_version_span"></span>
				<a href="#" id="clone_new_version_btn" class="btn btn-success" data-dismiss="modal" onclick="clone_into_new_version();">Clone Last Version?</a>
				<a href="#" id="new_version_btn" class="btn btn-primary" data-dismiss="modal" onclick="insert_version();">Start from scratch</a>
			</div>
			<div id="confirm_box_footer" class="modal-footer">
				<a href="#" class="btn" onclick="reset_version_index()" data-dismiss="modal">Close</a>
			</div>
		</div>

<!-- modal popup for variations -->
		<div id="modal_variation_box" class="modal hide fade">
			<div id="version_box_header" class="modal-header">
				<button type="button" onclick="reset_variation_index()" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
				<h3>New Variation</h3>
			</div>
			<div class="modal-body">
				<div id="new_variation_name_control" class="control-group">
					<label class="control-label" for="new_variation_box">New Variation Name</label>
					<div class="controls">
						<input style="width:150px;" id="new_variation_box" type="text" placeholder="Variation" length="20"></input>
					</div>
				</div>
				<a href="#" id="clone_new_variation_btn" class="btn btn-success" onclick="clone_into_new_variation();">Clone original variation?</a>
				<a href="#" id="new_variation_btn" class="btn btn-primary" onclick="insert_variation();">Start from scratch</a>
			</div>
			<div id="confirm_box_footer" class="modal-footer">
				<a href="#" class="btn" onclick="reset_variation_index()" data-dismiss="modal">Close</a>
			</div>
		</div>

		<div id="modal_rename_variation" class="modal hide fade">
			<div id="rename_variation_header" class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
				<h3>Rename Variation</h3>
			</div>
			<div class="modal-body">
				<div id="edit_variation_name_control" class="control-group">
					<label class="control-label" for="edit_variation_name">New Variation Name</label>
					<div class="controls">
						<input style="width:200px;" id="edit_variation_name" type="text" placeholder="Variation" length="20"></input>
					</div>
				</div>
			</div>
			<div id="confirm_box_footer" class="modal-footer">
				<a href="#" onclick="rename_variation()" class="btn btn-success" data-dismiss="modal">Rename</a>			
				<a href="#" class="btn" data-dismiss="modal">Close</a>
			</div>
		</div>		

<!-- modal popup for asset overwrite confirmation -->
		<div id="modal_confirm_box" class="modal hide fade">
			<div id="confirm_box_header" class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
				<h3>Some Assets are Already Registered</h3>
			</div>
			<div id="confirm_box_body" class="modal-body">
				<h5 id="confirm_box_body_h">There is nothing here...</h5>
			</div>
			<div id="confirm_box_footer" class="modal-footer">
				<a href="#" id="sub_popup" class="btn btn-primary" data-dismiss="modal" onclick="insert_replace();">Overwrite All</a>
				<a href="#" class="btn" data-dismiss="modal">Let Me Think About That</a>
			</div>
		</div>

		<!-- modal-gallery is the modal dialog used for the image gallery -->
		<div id="modal-gallery" class="modal modal-gallery hide fade" data-filter=":odd">
		<div class="modal-header">
		<a class="close" data-dismiss="modal">&times;</a>
		<h3 class="modal-title"></h3>
		</div>
		<div class="modal-body"><div class="modal-image"></div></div>
		<div class="modal-footer">
		<a class="btn modal-download" target="_blank">
		<i class="icon-download"></i>
		<span>Download</span>
		</a>
		<a class="btn btn-success modal-play modal-slideshow" data-slideshow="5000">
		<i class="icon-play icon-white"></i>
		<span>Slideshow</span>
		</a>
		<a class="btn btn-info modal-prev">
		<i class="icon-arrow-left icon-white"></i>
		<span>Previous</span>
		</a>
		<a class="btn btn-primary modal-next">
		<span>Next</span>
		<i class="icon-arrow-right icon-white"></i>
		</a>
		</div>
		</div>

<div id="adset_requests_preview_modal" class="modal hide fade" tabindex="-1" >
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Adset Request Preview</h3>
	</div>
	<div class="modal-body">
		
	</div>
	<div class="modal-footer">
		<a class="btn" data-dismiss="modal">Close</a>
	</div>
</div>


<!-- The template to display files available for upload -->
		<script id="template-upload" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
		 <tr class="template-upload fade">
		 <td class="preview"><span class="fade"></span></td>
		 <td class="name" style="font-size:10px; word-wrap: break-word;"><span>{%=file.name%}</span></td>
		 <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
		 {% if (file.error) { %}
			<td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
			{% } else if (o.files.valid && !i) { %}
		 
			<td class="start">{% if (!o.options.autoUpload) { %}
			 <button class="btn btn-primary">
			 <i class="icon-upload icon-white"></i>
			 <span>{%=locale.fileupload.start%}</span>
			 </button>
			 {% } %}</td>
			{% } else { %}
			<td colspan="2"></td>
			{% } %}
		 <td class="cancel">{% if (!i) { %}
			 <button class="btn btn-warning">
			 <i class="icon-ban-circle icon-white"></i>
			 <span>{%=locale.fileupload.cancel%}</span>
			 </button>
			 {% } %}</td>
		 </tr>
		 {% } %}
</script>


	<script type="text/javascript" src="//api.filepicker.io/v1/filepicker.js"></script>

		<!-- The template to display files available for download -->
		<script id="template-download" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
		 <tr class="template-download fade" >
		 {% if (file.error) { %}
			<td></td>
			<td class="name"><span>{%=file.name%}</span></td>
			<td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
			<td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
			{% } else { %}
			<td class="preview">{% if (file.thumbnail_url) { %}
				 <a href="{%=file.url%}" title="{%=file.name%}" rel="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
				 {% } %}</td>
			<td class="name">
			<a href="{%=file.url%}" title="{%=file.name%}" rel="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a>
			</td>
			<td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
			<td colspan="2"></td>
			{% } %}
		 <td class="delete">
		 <button class="btn btn-danger delete" onclick="reset_variables_tab(this);" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">
		 <i class="icon-trash icon-white"></i>
		 <span>{%=locale.fileupload.destroy%}</span>
		 </button>
		 
		 <input type="checkbox" name="delete" value="1">
		 </td>
		 </tr>
		 {% } %}
</script>

<script type="text/javascript" src="/libraries/external/form2js/src/form2js.js"></script>
<script type="text/javascript" src="/libraries/external/form2js/example/json2.js"></script>
<script type="text/javascript" src="/libraries/external/form2js//src/js2form.js"></script>
<script src="/libraries/external/select2/select2.js"></script>

<script type="text/javascript"> 

var timer;//this is used for the async loading bar
var previous;
var previous_copy;
var previous_variation;
var global_id_counter = 0;
var disable_clone_btn = false;

$("#adset_version_select").focus(function () {
	// Store the current value on focus and on change
	previous = this.value;
}).change(function() {
	// Do something with the previous value after the change
	version_drop_select(this.value);
	// Make sure the previous value is updated
	display_creative_request_link();
});

$("#adset_version_variation_select").focus(function () {
	// Store the current value on focus and on change
	previous_variation = this.value;
}).change(function() {
	// Do something with the previous value after the change
	variation_drop_select(this.value);
	// Make sure the previous value is updated
	display_creative_request_link();
});

$(document).ready( function () {
	disable_publisher_tab();
	$('#uploader_tooltip').tooltip({
		animation: true,
		html: true,
		placement: "bottom",
		title: "<div style='text-align:left;'><span style='font-weight:bold'>File uploading naming conventions: </span><ul><li>loader_image_widthxheight.jpg</li> <li>backup_widthxheight.jpg</li> <li>variables.xml OR variables.js</li><li>fullscreen.jpg</li> <li>widthxheight.swf</li><li>widthxheight.js</li></ul></div><div style='text-align:left;'><span style='font-weight:bold'>Minimum required files for a creative: </span><ul><li>variables</li><li>backup (only for publishing)</li><li>loader_image OR swf OR size.js</li></ul></div>"
	});
	filepicker.setKey('AaUsXCve3R36W4vz68Nnnz');

	$('#campaign_link_select').select2({
		placeholder: "select a campaign",
		minimumInputLength: 0,
		ajax: {
			url: "/creative_uploader/ajax_get_select2_campaigns",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 100,
					page: page,
					type: "campaign_link_select"
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		formatResult: select2_campaign_result_format,
		formatSelection: select2_campaign_selection_format,
		allowClear: false
	});

	$('#campaign_select').select2({
		placeholder: "select a campaign",
		minimumInputLength: 0,
		ajax: {
			url: "/creative_uploader/ajax_get_select2_campaigns",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 100,
					page: page,
					type: "campaign_select"
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		formatResult: select2_campaign_result_format,
		formatSelection: select2_campaign_selection_format,
		allowClear: false
	});

	$('#adset_select').select2({
		placeholder: "select an adset",
		minimumInputLength: 0,
		ajax: {
			url: "/creative_uploader/get_adsets",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				var t_campaign_id = $("#campaign_select").select2('val');
				return {
					q: term,
					page_limit: 100,
					page: page,
					campaign: t_campaign_id
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		allowClear: false
	});
	
	initialize(); //creative_uploader called with existing version id
	
	$("#show_for_io").change(function(){
		var adset_version_id =$("#adset_version_select").val();
		var is_checked = $(this).is(":checked");
		var show_for_io = (is_checked ? 1 : 0);
		
		if (typeof adset_version_id !== 'undefined' && adset_version_id !== '')
		{
			$.ajax({
				async:false,
				type: "GET",
				url: "/creative_uploader/save_show_for_io/",
				data:{'show_for_io':show_for_io, 'adset_version_id':adset_version_id},
			});
		}
	});
});

function set_show_for_io_value(version_id)
{
	
	if (typeof version_id === 'undefined' || version_id === null || version_id === '')
	{
		return;
	}
	
	$.ajax({
		async:false,
		type: "GET",
		url: "/creative_uploader/get_show_for_io_value/",
		dataType: "html",
		data: {'adset_version_id': version_id},
		success: function(data){
			var ret_obj = $.parseJSON(data);
			if(ret_obj.data == 1)
			{
				$("#show_for_io").prop('checked',true);
			}
			else
			{
				$("#show_for_io").prop('checked',false);
			}
		}
	});
}


window.onbeforeunload = function() {
	while(true)
	{
		jQuery.ajax({
			async:false,
			type: "GET",
			url: "/creative_uploader/unlink_key",
			success: function(data, textStatus, jqXHR) {
			},
			error: function(data, textStatus, jqXHR) {
				alert("Error 8893: Folder unlink failure on window close");
			}
		});
		return;
	}
};

function select2_campaign_result_format(item)
{
	var result = "--";
	if(item.campaign == null || item.advertiserElem == null)
	{
		if(item.text != null)
		{
			result = item.text;
		}
	}
	else
	{
		result = '<small class="muted">'+item.advertiserElem+'</small> <strong>'+item.campaign+'</strong>';
	}
	return result;
}

function select2_campaign_selection_format(item)
{
	var result = "--";
	if(item.campaign == null)
	{
		if(item.text != null)
		{
			result = item.text;
		}
	}
	else
	{
		result = item.campaign;
	}
	return result;
}

function get_and_load_dropdowns(v_id){
	
	if(v_id==0){
		handle_campaign_change("none",null, null);
	}else{
		$.ajax({
				type: "POST",
				url: '/creative_uploader/get_ids_from_adset_version/',
				async: true,
				data: { av_id: v_id },
				dataType: 'html',
				error: function(){
					alert('error');
					return 'error';
				},
				success: function(msg){ 
					var ret_obj = $.parseJSON(msg);
					var cmpn;
					if(ret_obj[0].campaign_id === null){
						cmpn = "none";
					}else{
						cmpn = ret_obj[0].campaign_id;
					}
					handle_campaign_change(cmpn,ret_obj[0].id, v_id);//campaign id, adset_id, adset_version_id
					//alert("about to update link from get_and_load_dropdowns: "+v_id);
					update_adset_link(v_id);
					
				}
			});
	}
}
function update_adset_link(v_id)
{
	if(v_id === undefined || v_id === null)
	{
		$("#adset_link").html("");
	}
	else
	{
		$( "#adset_link" ).html(getBaseURL()+'creative_uploader/'+v_id);
	}
}

function initialize()
{
	var as_id = <?php echo $adset_version_id;?>;
	var existing_adset_object = <?php echo $existing_adset_object ?>;
	if(!$.isEmptyObject(existing_adset_object))
	{
		if(existing_adset_object.adset_id !== undefined && existing_adset_object.version_id !== undefined)
		{
			$("#adset_select").select2('data', {id: existing_adset_object.adset_id, text: existing_adset_object.adset_name});
			adset_drop_select(existing_adset_object.adset_id, (existing_adset_object.variation_parent_id == null ? existing_adset_object.version_id : existing_adset_object.variation_parent_id), existing_adset_object.version_id);
			display_creative_request_link();
		}
	}
	else
	{
		$("#campaign_select").select2('data', {id: "none", text: "All Campaigns", campaign: null, advertiser: null});
	}
}

function build_builder_version_dropdown(builder_version_array){
	$.each(builder_version_array, function(index, item) {
		$( "#builder_version_select" ).append('<option value="'+item.builder_version+'">Builder Version: '+item.builder_version+' '+item.name+'</option>');
	});

	
}


function load_variables_ui(adset_version){
	if(typeof adset_version === 'undefined'){
		adset_version = $("#adset_version_select").val();
	}
	builder_version =	$("#builder_version_select").val();
	//clear out output_zone
	$("#output_zone").html("");
	////place html form
	jQuery.ajax({
		async:false,
		type: "POST",
		url: "/creative_uploader/load_variables_ui",
		data: {builder_version: builder_version, adset_version: adset_version},
		dataType: "html",
		success: function(data){
			document.getElementById("variables_ui_zone").innerHTML = data;
			$("#builder_version_select").change(function() {
				var builder_version = $(this).val();
				var builder_version_recommendation = $('.recommended_builder_version');
				if(builder_version_recommendation.length)
				{
					if(builder_version == builder_version_recommendation.text())
					{
						builder_version_recommendation.closest('.alert').slideUp();
					}
					else
					{
						builder_version_recommendation.closest('.alert').slideDown();
					}
				}
				$('#fileupload [name="builder_version"]').val(builder_version);
				load_ui(builder_version,null);
			});
			$("#adset_save_button").click(function() {
				get_modal('save_adset');
			});

			$("#adset_open_button").click(function() {
				get_modal('open_adset');
			});

			$("#template_save_button").click(function() {
				get_modal('save_template');
			});
			$("#pull_cdn_assets").click(function() {
				//alert( $("input[name='file_assets.all_assets']").val() );
				full_adset_pull($("#adset_version_variation_select").val(),$("#adset_version_select").val());
			});
			$("#template_open_button").click(function() {
				get_modal('open_template');
			});
			$("#refresh_button").click(function() {
				//alert( $("input[name='file_assets.all_assets']").val() );
				populate_new_local_ads();
			});
			$("#test_button").click(function() {
				//alert( $("input[name='file_assets.all_assets']").val() );
				write_to_test();
			});
			////get builder versions
			$.ajax({
				type: "POST",
				url: '/variables/get_builder_versions/',
				async: true,
				data: {  },
				dataType: 'html',
				error: function(){
					alert('error');
					return 'error';
				},
				success: function(msg){ 
					build_builder_version_dropdown($.parseJSON(msg));
					if( adset_version != 0){
						open_adset_variables(adset_version, builder_version); //based on the adset version prefill all the ui fields
					}else{
						if(!builder_version)
						{
							builder_version = $("#builder_version_select").val();
						}
						load_ui(builder_version, null);//if we have no adset, just throw in the default values
					}
					$("#builder_version_select").trigger('change');
					toggle_loader_bar(false);
				}
			});
		},
		error: function(data) {
			alert("Error 5641231: Failed to get variables file");
		}
	});
}

function clone_into_new_version()
{
	if(disable_clone_btn)
	{
		return;
	}
	//first attempt to pull js variables, if they don't exist load the ui with the new adset_v_id
	//alert('previous adset id is: '+previous);
	//alert('about to insert new version');
	var clone_source_version_id = previous;
	var new_v_id = insert_version();
	//alert('prev version is: '+previous+'new version is: '+new_v_id);

	full_adset_pull(clone_source_version_id,new_v_id);

}


function clone_into_new_variation()
{
	//first attempt to pull js variables, if they don't exist load the ui with the new adset_v_id
	//alert('previous adset id is: '+previous);
	//alert('about to insert new version');
	var new_v_id = insert_variation();
	//alert('prev version is: '+previous+'new version is: '+new_v_id);

	full_adset_pull($("#adset_version_select").val(),new_v_id);
}



function full_adset_pull(source_adset_v, ui_adset_v){
	//alert('pulling from: '+source_adset_v+" to "+ui_adset_v);
	//alert("localhost/variables/fetch_variables/"+source_adset_v) ;
	var variables= {};
	jQuery.ajax({
		async: true,
		type: "POST",
		url: "/variables/fetch_variables/"+source_adset_v,
		dataType: "html",
		success: function(data){
			//alert('success fetching js: '+data);
			//alert("this is the data: "+data);
			var adset_data = $.parseJSON(data);
			if(adset_data[0].builder_version === null || adset_data[0].variables_data === null){ //if there's no variables data go with the default for the latest builder version
				alert('for some reason we can\'t find variables');
			}else if(adset_data[0].builder_version == 'js'){///special case reading in old js file
				eval("var flashvars= {}; "+adset_data[0].variables_data);
				variables.flashvars = flashvars;
				save_variables_blob_to_adset(200, ui_adset_v, variables, false,false);
			}else{//
				//alert(adset_data[0].variables_data);
				//eval("var flashvars= {}; "+adset_data[0].variables_data);
				
				variables = $.parseJSON(adset_data[0].variables_data);
				save_variables_blob_to_adset(adset_data[0].builder_version, ui_adset_v, variables, false,false);///old variables_js files correspond to version 200 ///don't refresh local ads yet ads
			}
			pull_cdn_assets_to_local_directory(source_adset_v,ui_adset_v);///this will call new ad populate and refresh ui appropriately
		},
		error: function(data) {
			alert("Error 45654: open variables js file error");
			toggle_loader_bar(false);
		}
	});
}


function load_ui(builder_version, json_variables_data){
	$( "#builder_version_select" ).val(builder_version);
	$.ajax({
		type: "GET",
		url: '/variables/get_config/'+builder_version,
		async: true,
		data: {  },
		dataType: 'html',
		error: function(mesg){
			alert('error getting config for builder version '+builder_version);
			alert(msg);
			return 'error';
		},
		success: function(msg){ 
			$('#ui_control_form').empty();
			var menu_list = $('<dl></dl>');
			if(msg)
			{
				create_sections($.parseJSON(msg), menu_list);
			}
			$('#ui_control_form').append(menu_list);

			



			$("#super_save_button").click(function() {
					super_save();
				});
			$('#super_save_tooltip').tooltip({
				 animation: true,
				 html: true,
				 placement: "bottom" });
			//turn this new input field into 
			$('.fp_io').each(function(i, obj) {
				//console.log(obj);
				filepicker.constructWidget(obj);
			});
			///attach file parameter handling to the field - this fills the hidden supplemental fields
			$(".fp_io").change(function(){
				var the_id = $(this).attr('id');
				console.log(event.fpfiles[0]);
				$('#'+(the_id+'_filename')).val(event.fpfiles[0].filename);
				$('#'+(the_id+'_isWriteable')).val(event.fpfiles[0].isWriteable);
				$('#'+(the_id+'_key')).val(event.fpfiles[0].key);
				$('#'+(the_id+'_mimetype')).val(event.fpfiles[0].mimetype);
				$('#'+(the_id+'_size')).val(event.fpfiles[0].size);
				$('#'+(the_id+'_url')).val(event.fpfiles[0].url);


				var the_id = $(this).attr('id');
				filepicker.stat(event.fpfiles[0].url, {width: true, height: true},
					function(metadata){
					console.log(metadata);
					//console.log(JSON.stringify(metadata));
					$('#'+(the_id+'_width')).val(metadata.width);
					$('#'+(the_id+'_height')).val(metadata.height);
				});

				

				//lert(($(this).attr('id')+'_url'));
				//alert($('#'+($(this).attr('id')+'_url')).val());
				
				var the_vlid = $(this).attr('data-vlid');
				var the_url = event.fpfiles[0].url;
				var the_key = event.fpfiles[0].key;
				var the_well = $(this).attr('id')+"_filename_well";
				var the_file_name = event.fpfiles[0].filename;
				var the_link = '<a href="//s3.amazonaws.com/adnifty0/'+the_key+'" target="_blank">'+the_file_name+'</a>';
				
				//console.log(the_url);
				//console.log("the well",the_well);
				//console.log("the filename",the_file_name);

				$("#"+the_well).html(the_link);
				//$("#"+the_well).addClass("well");

				// var the_well = $(this).attr('id')+"_filename_well";
				// console.log(the_vlid);
				// console.log(the_well);
				// $("#"+the_well).html("");
				// $("#"+the_well).removeClass("well");

			});

			if(json_variables_data !== null){
				js2form(document.getElementById('ui_control_form'), $.parseJSON(json_variables_data));

				//if there are filetype inputs loaded to their repective hidden fields, i want to fill a little div with the file name so the user knows that something is there.
				//console.log($("[id=_filename]"));
				$("[id$=filename]").each(function() {
					var the_vlid = $(this).attr('data-vlid');
					var the_url = $("#fp_io_"+the_vlid+"_url").val();
					var the_key = $("#fp_io_"+the_vlid+"_key").val();
					var the_well = $(this).attr('id')+"_well";
					var the_file_name = $(this).attr('value');
					var the_link = '<a href="//s3.amazonaws.com/adnifty0/'+the_key+'" target="_blank">'+the_file_name+'</a>';
					
					//console.log(the_url);
					//console.log("the well",the_well);
					//console.log("the filename",the_file_name);

					$("#"+the_well).html(the_link);
					//$("#"+the_well).addClass("well");
					//filepicker.constructWidget(obj);
				});

			}

			


			populate_new_local_ads();

			
		}
	});
}


function create_sections(section, menu_list)
{
	$.each(section, function(index, item) {
		//var this_id = Math.floor((Math.random()*10000000000)+1);
		global_id_counter = global_id_counter+1;
		var this_id = 'sec_id_'+global_id_counter;
		
		switch(item.section_type)
		{
			case 'block':
				var title = $('<dt ><a data-toggle="collapse" href="#'+this_id+'" class="accordion-toggle">'+item.section_title+'</a></dt>')
				var block = $('<dd id="'+this_id+'" class="collapse out"></dd>');
				create_sections(item.ui_elements,block);
				menu_list.append([title,block]);
				break;
			case 'field':
				menu_list.append(get_field_html(item));
				break;
		}
	});
}


function get_field_html(item){
	//var this_id = Math.floor((Math.random()*10000000000)+1);
	global_id_counter = global_id_counter+1;
	var this_id = 'field_id_'+global_id_counter;
	
	switch(item.field_type)
		{
			case 'normal_text':
			var html_type = (item.is_hidden == 1)? "hidden" : "text";
			var locked_value = (item.is_hidden == 1)? item.default_value : "";
			var field = $('<div class= "row" id = "'+this_id+'" style="margin-left:0px;padding-bottom:0px;"></div>\
							<form class=" micro">\
								<div class="control-group micro">\
									<label class="control-label" >\
										<small class="muted">'+item.field_label+'</small>\
										<a onclick="tooltip_message(\''+item.tooltip_copy+'\',\''+this_id+'\')"> <i class="icon-info-sign"></i></a>\
										</label>\
									<div class="controls">\
										<input  type="'+html_type+'" name="'+item.field_id+'" placeholder="'+item.field_label+'" value="'+item.default_value+'">\
										'+locked_value+'\
									</div>\
								</div>\
								</form>\
								');
			break;
			case 'text_area':
			var html_type = (item.is_hidden == 1)? "hidden" : "text";
			var locked_value = (item.is_hidden == 1)? item.default_value : "";
			var field = $('<div class= "row" id = "'+this_id+'" style="margin-left:0px;padding-bottom:0px;"></div>\
							<form class=" micro">\
								<div class="control-group micro">\
									<label class="control-label" >\
										<small class="muted">'+item.field_label+'</small>\
										<a onclick="tooltip_message(\''+item.tooltip_copy+'\',\''+this_id+'\')"> <i class="icon-info-sign"></i></a>\
										</label>\
									<div class="controls">\
										<textarea name="'+item.field_id+'" placeholder="'+item.field_label+'">'+item.default_value+'</textarea>\
										'+locked_value+'\
									</div>\
								</div>\
								</form>\
								');
			break;
			case 'boolean':
			var html_type = (item.is_hidden == 1)? "hidden" : "radio";
			var true_checked = '';
			var false_checked = '';
			if(item.default_value == "true" || item.default_value == true ){
				true_checked = "checked";
			}else if(item.default_value == "false" || item.default_value == false ){
				false_checked = "checked";
			}
			
			var field = $('<div class= "row" id = "'+this_id+'" style="margin-left:0px;padding-bottom:0px;"></div>\
							<form class=" micro">\
								<div class="control-group micro">\
									<label class="control-label" >\
										<small class="muted">'+item.field_label+'</small>\
										<a onclick="tooltip_message(\''+item.tooltip_copy+'\',\''+this_id+'\')"> <i class="icon-info-sign"></i></a>\
										</label>\
									<div class="controls">\
										<input  type="'+html_type+'" name="'+item.field_id+'" placeholder="'+item.field_label+'" value="true" '+true_checked+'>\
										true\
										<input  type="'+html_type+'" name="'+item.field_id+'" placeholder="'+item.field_label+'" value="false" '+false_checked+'>\
										false\
									</div>\
								</div>\
								</form>\
								');
			break;
			case 'file_picker':
			var field = $('<div class= "row" id = "'+this_id+'" style="margin-left:0px;padding-bottom:0px;margin-bottom:0px;"></div>\
							<div class="row" style="margin-left:0px;width:260px;height:55px">\
							<form class=" micro">\
								<div class="control-group micro">\
									<label class="control-label" >\
										<small class="muted">'+item.field_label+'</small>\
										<a onclick="tooltip_message(\''+item.tooltip_copy+'\',\''+this_id+'\')"> <i class="icon-info-sign"></i></a>\
										</label>\
									<div class="controls">\
										<div style="font-size:7px !important;">\
											<input class="fp_io " data-vlid="'+this_id+'"  id="fp_io_'+this_id+'"" type="filepicker" data-fp-store-location="S3" data-fp-class="span2 micro registered_files_well" data-fp-button-class="btn btn-primary btn-mini fp_io_button span1 pull-left" data-fp-button-text="Pick file" data-fp-drag-text="or drop here" />\
										</div>\
									</div>\
								</div>\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_filename" name="'+item.field_id+'.filename" value="'+item.default_filename+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_isWriteable" name="'+item.field_id+'.isWriteable" value="'+item.default_is_writable+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_key" name="'+item.field_id+'.key" value="'+item.default_key+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_mimetype" name="'+item.field_id+'.mimetype" value="'+item.default_mimetype+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_size" name="'+item.field_id+'.size" value="'+item.default_size+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_url" name="'+item.field_id+'.url" value="'+item.default_url+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_width" name="'+item.field_id+'.width" value="'+item.default_width+'">\
								<input  type="hidden" data-vlid="'+this_id+'" id="fp_io_'+this_id+'_height" name="'+item.field_id+'.height" value="'+item.default_height+'">\
								</form><br>\
							</div>\
							<div class="row" style="margin-left:0px;width:260px;height:35px"><span id="fp_io_'+this_id+'_filename_well" class="micro registered_files_well help-block"></span></div>\
							');
			break;
		}
		return field;
}






function get_modal(variables_modal_type)
{
	$('#variables_modal').html('<div class="modal-header">\
								<h3 id="myModalLabel">Loading</h3>\
								</div>\
								<div class="modal-body">\
									<div class="progress progress-striped active"><div class="bar" style="width: 40%;"></div></div>\
								</div>');

	$.ajax({
		type: "POST",
		url: '/variables/get_modal/',
		async: true,
		data: { b_vers: $("#builder_version_select").val(), modal_type: variables_modal_type },
		dataType: 'html',
		error: function(){
			alert('error');
			return 'error';
		},
		success: function(msg){ 
			var version = $("#adset_version_select").val();
			$('#variables_modal').html(msg);

			$("#modal_adset_select").select2({
				placeholder: "select an adset",
				minimumInputLength: 0,
				ajax: {
					url: "/variables/get_select2_versions",
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
				allowClear: false
			});
		}
	});

}


function open_adset_variables(adset_id, fallback_builder_version)
{
	//alert('inside open_adset_variables '+adset_id);
	$.ajax({
		type: "POST",
		url: '/variables/fetch_adset',
		async: true,
		data: { a_v_id: adset_id},
		dataType: 'html',
		error: function(){
			alert('error in variables/fetch_adset');
			return 'error';
		},
		success: function(msg){ 
			var adset_data = $.parseJSON(msg);
			if(adset_data[0].builder_version === null){ //if there's no variables data go with the default for the latest builder version
				if(!fallback_builder_version)
				{
					fallback_builder_version = $("#builder_version_select").val();
				}
				//do nothing for now - maybe later prompt for a template to import
				load_ui(fallback_builder_version, null);
			}else{

				load_ui(adset_data[0].builder_version, adset_data[0].variables_data);
				$("#summary_tab").removeClass('active');
				$("#preview_tab").removeClass('active');
				$("#publish_tab").removeClass('active');
				$("#ad_tags_tab").removeClass('active');
				//$("#variables_tab").addClass('active');
				//check_for_variables_file();
				$("#variables_ui_tab").addClass('active');

			}
			
			
			//write_to_test();
			$('#variables_modal').modal('hide');
			// m@@ might want to update some title on the main view to show the name of the 
		}
	});
}

function open_template_variables(template_id)
{
	//alert('inside open_template_variables '+template_id);
	$.ajax({
		type: "POST",
		url: '/variables/fetch_template',
		async: true,
		data: { t_id: template_id },
		dataType: 'html',
		error: function(){
			alert('error in variables/fetch_template');
			return 'error';
		},
		success: function(msg){ 
			var template_data = $.parseJSON(msg);
			load_ui(template_data[0].builder_version, template_data[0].data);

			$('#variables_modal').modal('hide');
			// m@@ might want to update some title on the main view to show the name of the 
		}
	});
}




function write_to_test(){
	var formData = form2js('ui_control_form', '.', true, function(node){});
	document.getElementById('testArea').innerHTML = JSON.stringify(formData, null, '\t');
}

function pull_cdn_assets_to_local_directory(adset_pull_version,ui_version){//pull from adset_pull_version, and load UI_version variables
	if(ui_version===undefined){
		ui_version=adset_pull_version;
	}
	$('body').scrollTop(200);
	alert( "I'm about to pull CDN assets for the adset to the local directory, this might take a minute. The adset will render when I'm done");
	toggle_loader_bar(true, 6)
	jQuery.ajax({
		async: true,
		type: "POST",
		url: "/creative_uploader/save_cdn_assets_to_local_dir/"+adset_pull_version,
		dataType: "html",
		success: function(data){
			//WILL m@ - how do I make the staged files UI update?
			$('#fileupload').each(function () {
				var that = this;
				$.getJSON(this.action, function (result) {
					if (result && result.length) {
						$(that).fileupload('option', 'done')
							.call(that, null, {result: result});
					}
				});
			});
			//alert(data);
			load_variables_ui(ui_version);
			//toggle_loader_bar(false);
		},
		error: function(data) {
			alert("Error 6556: CDN assets not pulled");
			toggle_loader_bar(false);
		}
	});

}

function super_save(){
	$('body').scrollTop(200);
	save_adset_variables($("#adset_version_variation_select").val(),true);
}

function save_adset_variables(v_id, is_super){
	var formData = form2js('ui_control_form', '.', true, function(node){});
	//console.log(formData);
	save_variables_blob_to_adset($("#builder_version_select").val(), v_id, formData, is_super,true);
}

function save_variables_blob_to_adset(builder_version,v_id, form_blob, is_super, refresh_local_ads){
	$.ajax({
		type: "POST",
		url: '/variables/save_adset',
		async: true,
		data: { a_v_id: v_id , variables_data: JSON.stringify(form_blob, null, '\t') , b_vers: builder_version },
		dataType: 'html',
		error: function(){
			alert('error saving adset variables');
		},
		success: function(msg){ 
			//alert(msg);
			$('#variables_modal').modal('hide');
			display_spec_ad_preview_link();
			// m@@ might want to update some title on the main view to show the name of the 
			if(is_super){
				//alert('about to save local assets to CDN');
				perform_action();
			}
			if(refresh_local_ads){
				populate_new_local_ads(builder_version);
			}
			
		}
	});
}

function tooltip_message(message, id){
		$('#'+id).html('<div class="alert alert-info"><a class="close" data-dismiss="alert">×</a><span>'+message+'</span></div>');
}

function adset_drop_select(adset_id,v_id,var_id,campaign_set_override)
{
	if(adset_id != 'none' && adset_id != 'select_new_dropdown_adset_insert' && adset_id != "")
	{
		//update_link_dropdown();//this code goes back to set the campaign
		$('#link_adset_button').css('visibility', 'visible');
	}
	else
	{
		$('#link_adset_button').css('visibility', 'hidden');
		if(adset_id == 'select_new_dropdown_adset_insert')
		{
			dropdown_check(adset_id, null);
			return;
		}
	}

	if(adset_id != 'none' && adset_id != "")
	{
		jQuery.ajax({
			async:false,
			type: "POST",
			url: "/creative_uploader/get_versions",
			dataType: "html",
			data: {adset_id: adset_id, campaign_id: $("#campaign_select").select2('val')},
			success: function(data){
				$('#adset_version_select').html(data);
				if (v_id === undefined || v_id === null)
				{
					v_id = $('#adset_version_select').val();
					dropdown_check(adset_id, v_id);

				}
				if(v_id == 'new' || v_id == '0')
				{
					insert_version();
					dropdown_check(adset_id, v_id);
				}
				else
				{
					$("#adset_version_select").val(v_id);
					version_drop_select(v_id,var_id, campaign_set_override);
					display_creative_request_link();
				}
			},
			error: function(data) {
				alert("Error 1887: Failed to get adset versions");
			}
		});
	}
	else
	{
		dropdown_check(adset_id);
	}
}

function insert_version()
{
	var adset = $("#adset_select").val();
	var new_version_id;
	$.ajax({
		async:false,
		type: "POST",
		url: "/creative_uploader/insert_new_version/",
		data: {adset_id: adset, campaign_id: $("#campaign_select").select2('val')},
		dataType: "html",
		success: function(data) {
			if($.trim(data) != 'false')
			{
				new_version_id = $.trim(data);
				adset_drop_select(adset,new_version_id,new_version_id);
			}
			else
			{
				new_version_id = null;
				alert("Warning 1494: failed to create new version");
			}
		},
		error: function(atda) {
			new_version_id = null;
		}
	});
	return new_version_id;
}

function version_drop_select(value, ver_id, campaign_set_override)
{
	var selected_adset_id = $('#adset_select').val();
	if(value == '0')
	{
		$('#modal_version_box').modal('hide');
		//default value, no load yet
	}
	else if(value == 'new')
	{
		if(selected_adset_id != 'none' && selected_adset_id != '')
		{
			$('#modal_version_box').modal('show');
		}
		else
		{
			//somehow we have new version without an adset
			$('#modal_version_box').modal('hide');
		}
	}
	else
	{
		previous = value;
		$('#modal_version_box').modal('hide');
		$.ajax({
			async:false,
			type: "POST",
			url: "/creative_uploader/get_variations",
			dataType: "html",
			data: {version_id: value, campaign_id: $("#campaign_select").select2('val')},
			success: function(data){
				$('#adset_version_variation_select').html(data);
				if (ver_id === undefined || ver_id === null)
				{
					ver_id = $('#adset_version_variation_select').val();
					dropdown_check(selected_adset_id, value);
					display_creative_request_link();
				}
				if(ver_id == 'new' || ver_id == '0')
				{
					insert_version();
					dropdown_check(selected_adset_id, value);
					display_creative_request_link();					
				}
				else
				{
					$("#adset_version_variation_select").val(ver_id);
					variation_drop_select(ver_id, campaign_set_override);
					
				}
			},
			error: function(data) {
				alert("Error 1887: Failed to get adset versions");
			}
		});
	}
}

function variation_drop_select(value, campaign_set_override)
{
	$("#edit_variation").hide();
	var selected_adset_id = $('#adset_select').val();
	if(value == '0')
	{
		$('#modal_variation_box').modal('hide');
		$("#edit_variation").hide();
		//default value, no load yet
	}
	else if(value == 'new')
	{
		$("#edit_variation").hide();
		if(selected_adset_id != 'none' && selected_adset_id != '')
		{
			$('#new_variation_box').val("");
			$("#new_variation_name_control").removeClass('error');
			$("#modal_variation_box").modal('show');
		}
		else
		{
			//somehow we have new version without an adset
			$('#modal_variation_box').modal('hide');
		}
	}
	else
	{
		previous_variation = value;
		$("#modal_variation_box").modal('hide');
		$("#edit_variation").show();
		update_link_dropdown(campaign_set_override);
		dropdown_check(selected_adset_id, value);
	}
}

function open_variation_edit_modal()
{
	$("#edit_variation_name").val($("#adset_version_variation_select option:selected").text());
	$("#modal_rename_variation").modal('show');
}

function rename_variation()
{
	$("#edit_variation_name").removeClass('error');
	var new_variation_name = $("#edit_variation_name").val();
	if(/^[a-zA-Z0-9-_)( ]*$/.test(new_variation_name) == false)
	{
		$("#page_status").html('<div class="alert alert-important span12">Variation Name Contains Illegal Entry "'+new_variation_name+'".</div>');
		return false;
	}
	if(new_variation_name == "" || new_variation_name == undefined || new_variation_name == null)
	{
		$("#edit_variation_name").addClass('error');
		return;
	}
	else
	{
		$('#edit_variation_name').val("");
		$("#modal_rename_variation").modal('hide');		
		toggle_loader_bar(true,1);
		var variation = $("#adset_version_variation_select").val();
		var parent_version = $("#adset_version_select").val();
		$.ajax({
			type: "POST",
			url: "/creative_uploader/rename_variation/",
			async: true,
			data: {new_variation_name: new_variation_name, variation: variation, parent_version: parent_version},
			dataType: 'json',
			error: function(){
				$("#page_status").html('<div class="alert alert-important span12">Error 0409: Error renaming variation.</div>');
			},
			success: function(msg){
				if(msg.is_success == true)
				{
					$("#adset_version_variation_select option:selected").text(new_variation_name);
				}				
				else
				{
					$("#page_status").html('<div class="alert alert-important span12">'+msg.data+'.</div>');
				}
				toggle_loader_bar(false);
			}
		});
	}
}

function dropdown_check(value, version_id)
{
	toggle_loader_bar(true,2);
	$('#new_version_button').hide();
	$("#output_zone").html("");
	$("#output_adset_name").html("");
	$("#gallery_link").html("");
	$("#page_status").html("");
	$("#variables_ui_zone").html("");
	update_adset_link(null);
	set_show_for_io_value(version_id);
	if(value == "select_new_dropdown_adset_insert")
	{
		$('#adset_version_select').html('<option value="0">select adset</option>');
		$('#adset_version_variation_select').html('<option value="0">no variations</option>');

		$("#new_adset_input").css("visibility", "visible");
		$("#show_for_io_div").hide();
	}
	else if(value == "none" || value == "")
	{
		$('#adset_version_select').html('<option value="0">select adset</option>');
		$('#adset_version_variation_select').html('<option value="0">no variations</option>');
		$("#new_adset_input").css("visibility", "hidden");
		$("#show_for_io_div").hide();
	}
	else
	{
		$("#new_adset_input").css("visibility", "hidden");
		$("#output_zone").html("");
		$("#output_adset_name").html("");
		$("#gallery_link").html("");
		toggle_loader_bar(true,2);
		//output zone with database data
		var adset_data = $("#adset_select").select2('data');
		if(version_id === undefined)
		{
			version_id = $("#adset_version_select").val();
		}
		update_adset_link(version_id);
		jQuery.ajax({
			async:true,
			type: "POST",
			url: "/creative_uploader/populate_from_existing_adset/"+version_id+"",
			dataType: "html",
			success: function(data, textStatus, jqXHR) {
				if($.trim(data) != 'false')
				{
					$("#output_zone").html(data);
					$("#summary_tab").addClass('active');
					$("#preview_tab").removeClass('active');
					$("#publish_tab").removeClass('active');
					$("#ad_tags_tab").removeClass('active');
					//$("#variables_tab").removeClass('active');
					$("#variables_ui_tab").removeClass('active');
					toggle_loader_bar(false);
					$("#output_adset_name").html(adset_data.text);
					display_gallery_link();
				}
				else
				{
					//removed the alert here, since it was causing an issue when page refreshed at wrong time.
				}
				toggle_loader_bar(false);
				verify_version_publish(version_id);
			},
			error: function(data, textStatus, jqXHR) {
				alert("Error 1888: Failed to populate from existing data");
			}
		});
	}
}

function verify_version_publish(version)
{
	$.ajax({
		async:true,
		type: "POST",
		url: "/creative_uploader/can_publish_version",
		dataType: "html",
		data: {version_id: version},
		success: function(data){
			if($.trim(data) == 'true')
			{
				
				enable_publisher_tab();
			}
			else
			{
				disable_publisher_tab();
			}
		},
		error: function(data){
			alert("Error 1788: Failed to retrieve version status");
		}
	});

}

function enable_publisher_tab()
{
	$("#publish_tab").removeClass("disabled"); //remove the boostrap disabled class (makes button grey)
	$("#publish_tab_anchor").attr("href", ""); //sets anchor to not have an href so it wont redirect
	$("#publish_tab_anchor").attr("data-toggle", "tab"); //removes tab toggling
	$("#show_for_io_div").show();
}

function disable_publisher_tab()
{
	$("#publish_tab").addClass("disabled");
	$("#publish_tab_anchor").removeAttr("href");
	$("#publish_tab_anchor").removeAttr("data-toggle", false);
	$("#show_for_io_div").hide();
}

function setSelectedIndex(s, v)
{
	for( var i = 0; i < s.options.length; i++) {
		if( s.options[i].value == v) {
			s.options[i].selected = true;
			return;
		}
	}
}

function insert_adset()
{
	var re =  /[~`\^\|\\\;\<\>\/\n\r]/g;
	var text = $('#new_adset_box').val();
	text = text.replace(/\//g, "-"); //replace all slashes with a '-' character
	if(re.test(text) === true)
	{
		$("#page_status").html('<div class="alert alert-important span12">Adset Name Contains Illegal Entry "'+text+'".</div>');
		return false;
	}
	if(text == "" || text == undefined || text == null)
	{
		//do nothing
	}
	else
	{
		toggle_loader_bar(true,1);
		var temp_campaign = $("#campaign_select").select2('val');
		$.ajax({
			type: "POST",
			url: "/creative_uploader/insert_new_adset/",
			async: true,
			data: { text: text, c_id: temp_campaign},
			dataType: 'json',
			error: function(){
				$("#page_status").html('<div class="alert alert-important span12">Error 0409: Error inserting adset.</div>');
			},
			success: function(msg){
				if(msg.is_success == true)
				{
					handle_campaign_change(temp_campaign, msg.data.adset, msg.data.version);
				}
				else
				{
					$("#page_status").html('<div class="alert alert-important span12">Error 042231: Duplicate Entry: "'+text+'".</div>');
				}
			}
		});
	}
}

function update_link_dropdown(override)
{
	var adset_name = $('#adset_select option:selected').text();
	var version_name = $('#adset_version_select option:selected').text();
	var variation_name = $('#adset_version_variation_select option:selected').text();
	var variation_id = $('#adset_version_variation_select').val();
	$('#link_label').html("Link '"+adset_name+' '+version_name+'-'+variation_name+"' to Campaign");

	if(override === undefined && (variation_id != "" && variation_id != "new"))
	{
		var data_url = "/creative_uploader/get_campaign_for_variation/";
		$('#link_label').html("Link '"+adset_name+' '+version_name+'-'+variation_name+"' to Campaign");
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {variation_id: variation_id},
			dataType: 'json',
			error: function(){
				alert("Error 993175: Server error when updating the link dropdown");
			},
			success: function(msg){
				if(msg.is_success == true)
				{
					$('#campaign_select').select2('data', {id: msg.campaign.id, text: msg.campaign.text, campaign: msg.campaign.campaign, advertiser: msg.campaign.advertiserElem});
					$('#adset_version_variation_select').select2('data', {id: variation_id, text: variation_name});
					if(msg.campaign.id != 'none')
					{
						$('#campaign_link_select').select2('data', {id: msg.campaign.id, text: msg.campaign.text, campaign: msg.campaign.campaign, advertiser: msg.campaign.advertiserElem});
					}
				}
				else
				{
					alert("Error 993173: Incorrect server data sent when updating the link dropdown");
				}
			}
		});
	}
}

function link_version_to_campaign()
{
	var campaign_data = $('#campaign_link_select').select2('data');
	var version_id = $("#adset_version_variation_select").val();
	if(campaign_data != null && version_id != null && campaign_data.id != "none")
	{
		var data_url = "/creative_uploader/link_version_to_campaign/";
		toggle_loader_bar(true,0.5,'link_adset');
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: {c_id: campaign_data.id, v_id: version_id},
			dataType: 'json',
			error: function(){
				alert("Error 733913: Server error when linking adset to campaign");
			},
			success: function(msg){
				if(msg.is_success)
				{
					//hide butan
					toggle_loader_bar(false,null,'link_adset'); 
					//RESET PAGE
					$("#campaign_select").select2('data', campaign_data); 
					$("#campaign_link_select").select2('data', campaign_data);
					adset_drop_select($("#adset_select").val());
					if(document.getElementById('no_campaign_warning') != null)
					{
						$('#no_campaign_warning').css("visibility", "hidden");
					}
					
				}
				toggle_loader_bar(false,null,'link_adset'); 
			}
		});
	}
}

function get_ad_tag_for_creative(creative_id, ad_server)
{
	window.open("/publisher/get_ad_tag_from_creative_id/"+creative_id+"/"+document.getElementById("include_ad_choices_"+ad_server+"_span").checked+"/"+ad_server);
}

function add_creatives_to_tradedesk(button, campaign, version, ad_server)
{
	if($(button).data('pushdisabled') != false)
	{
		return;
	}

	if(document.getElementById("include_ad_choices_"+ad_server+"_span").checked)
	{
		var span = 1;
	}
	else
	{
		var span = 0
	}
	var custom_prefix = document.getElementById("ttd_"+ad_server+"_prefix_textbox").value;

	var element_id_suffix = "";
	var other_ad_server = '';
	if(ad_server == 'dfa')
	{
		other_ad_server = 'fas';
	}
	else if(ad_server == 'fas')
	{
		other_ad_server = 'dfa';
	}
	else
	{
		// unknown ad_server
	}	
	var other_ad_server_add_button = document.getElementById('add_'+other_ad_server+'_tags_to_tradedesk_button');

	var adgroup_set = $("#creative_push_option").val();
	var replace_overwrite = $("#overwrite_creatives").is(':checked') ? 1 : 0;

	var data_url = "/tradedesk/add_creative_set/";
	toggle_loader_bar(true,0.5,'add_to_tradedesk');
	$.ajax({
		type: "POST",
		url: data_url,
		async: true,
		data: {
			c_id: campaign, 
			v_id: version, 
			ad_choices: span,
			ad_server: ad_server,
			prefix: custom_prefix,
			replace: replace_overwrite,
			adgroup_set: adgroup_set
		},
		dataType: 'html',
		error: function(data, textStatus, jqXHR){
			alert("Error 733915: Server error while adding tags to tradedesk");
		},
		success: function(msg){
			var returned_data_array = eval('(' +msg+')' )
			if(returned_data_array.success)
			{
				if(other_ad_server != '')
				{
					$(button).attr("class", "btn btn-inverse disabled");
					$(button).data('pushdisabled', true);

					if(other_ad_server_add_button != null)
					{
						reset_push_button(other_ad_server);
					}
					$("."+ad_server+"_tags_column").addClass("active_tag_set");
					$("."+other_ad_server+"_tags_column").removeClass("active_tag_set");

					$("#"+ad_server+"_linked_to_ttd_notice").html("(linked to TTD)");
					$("#"+other_ad_server+"_linked_to_ttd_notice").html("");
				}
			}
			else
			{
				alert('Error adding '+ad_server+' creatives to TTD: '+returned_data_array.err_msg);
			}
			toggle_loader_bar(false,null,'add_to_tradedesk'); 
		}
	});
}

function perform_action()
{
	var alert_string = '';
	var select_box = $("#adset_select").val();
	if(select_box == "select_new_dropdown_adset_insert" || select_box == "none" || select_box == "")
	{
		var alert_string = 'Warning 1552: No adset selected';
		$('#page_status').css('width', '100%');
		$("#page_status").html('<div class="alert alert-error span12">'+alert_string+'</div>');
	}
	else
	{
		var adset_data = $("#adset_select").select2('data');
		var version_id = $("#adset_version_variation_select").val();
		jQuery.ajax({
			async:true,
			type: "POST",
			url: "/creative_uploader/check_exists/"+version_id+"",
			dataType: "html",
			success: function(data, textStatus, jqXHR) {
				if(data == 0)
				{
					insert_replace();
				}
				else
				{
					$('#modal_confirm_box').modal();
					$("#confirm_box_body_h").html(data);
					$("#output_adset_name").html(adset_data.text);
					display_gallery_link();
					//handle_accordion_event();
				}
			},
			error: function(data, textStatus, jqXHR) {
				alert("Error 0399: Data comparison time out");
			}
		});
	}
}

function insert_replace()
{
	$("#output_zone").html("");
	$("#variables_ui_zone").html("");
	$("#output_adset_name").html("");
	$("#gallery_link").html("");
	toggle_loader_bar(true,15);
	var alert_string = "";
	var adset_data = $('#adset_select').select2('data');
	var version_id = $('#adset_version_variation_select').val();
	jQuery.ajax({
		async:true,
		type: "POST",
		url: "/creative_uploader/find_variables/"+version_id+"",
		dataType: "html",
		success: function(data, textStatus, jqXHR) {
			$("#output_zone").html(data);
			$("#summary_tab").addClass('active');
			$("#preview_tab").removeClass('active');
			$("#publish_tab").removeClass('active');
			$("#ad_tags_tab").removeClass('active');
			//$("#variables_tab").removeClass('active');
			$("#variables_ui_tab").removeClass('active');
			toggle_loader_bar(false);
			//handle_accordion_event();
			$("#output_adset_name").html(adset_data.text);
			display_gallery_link();
			verify_version_publish(version_id);
		},
		error: function(data, textStatus, jqXHR) {
			alert("Critical Error 0474: Data insertion time out");
		}
	});
}

function handle_accordion_event()
{
	if(document.getElementById("open_new_assets_button").style.visibility=="visible")
	{
		document.getElementById("open_new_assets_button").style.visibility="hidden";
		document.getElementById("close_new_assets_button").style.visibility="visible";
	}else{
		document.getElementById("open_new_assets_button").style.visibility="visible";
		document.getElementById("close_new_assets_button").style.visibility="hidden";
	}
	$('#collapseOne').collapse('toggle');

}

function toggle_loader_bar(is_on, expected_seconds_to_completion)
{
	if(is_on)
	{
		$("#page_status").html('<div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div>');
		run(expected_seconds_to_completion);
	}else
	{
		$("#page_status").html('');
		stop_progress_timer();
	}
}


function loader_bar_width(width_target)
{
	$("#page_status").html('<div class="progress progress-success progress-striped active"><div class="bar" style="width: '+width_target+'%;"></div></div>');    
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
		document.getElementById('page_status').style.width = bar_width;
	}
	timer = setTimeout(myFunc, time_increment_ms);
}

function stop_progress_timer() 
{
	clearInterval(timer);
}

function handle_ui_campaign_change(c_id, as_id, v_id)
{
	toggle_loader_bar(true,1);
	handle_campaign_change(c_id, as_id, v_id);
}

function handle_campaign_change(c_id, adset, v_id)
{
	$("#output_zone").html("");
	$("#output_adset_name").html("");
	$("#gallery_link").html("");
	$("#page_status").html("");
	$("#variables_ui_zone").html("");

//Add case where unfiltering happens
	//if c_id is for "all Campaigns"
	//adset_drop_select(adset_id,v_id,var_id) with current adset, version, variation
	if(c_id == "none" && adset == null)
	{
		//get v_id and
		var adset_id = $("#adset_select").val();
		var version_id = $("#adset_version_select").val();
		var variation_id = $("#adset_version_variation_select").val();
		adset_drop_select(adset_id,version_id,variation_id,true);
		return;
	}

	if(adset !== undefined && adset !== null && 
	   v_id !== undefined && v_id !== null &&
	   adset.id !== undefined && adset.name !== undefined)
	{
		$("#adset_select").select2('data', {'id': adset.id, 'text': adset.name});
		adset_drop_select(adset.id, v_id);
	}
	else
	{
		$("#adset_select").select2('data', null);
		$("#adset_version_select").html('<option value="0">select adset</option>');
		$('#adset_version_variation_select').html('<option value="0">no variations</option>');

	}

}

function show_preview_page()
{
	display_gallery_link();
	document.getElementById("output_zone").innerHTML = '<iframe class="gallery" src="'+build_gallery_url(true)+'" style="overflow:visible" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>';
}

function display_spec_ad_preview_link()
{
	var builder_version = $( "#builder_version_select" ).val();
	var spec_ad_sample_url = build_spec_ad_sample_url();
	if(spec_ad_sample_url)
	{
		document.getElementById("spec_ad_sample_link").innerHTML = ' Spec Ad Preview: <a target="_blank" href="'+
			spec_ad_sample_url+'">'+spec_ad_sample_url+'</a>';
	}
	else
	{
		// TODO: handle showing a message if they are missing a valid campaign or whitelabel info.  Or if they haven't saved yet. 
		//document.getElementById("spec_ad_sample_link").innerHTML = ' Spec Ad Preview: need a vlid campaign with whitelabel and saved adset with series 500 builder version';
		document.getElementById("spec_ad_sample_link").innerHTML = '';
	}
}

function build_spec_ad_sample_url()
{
	var version_id = $("#adset_version_variation_select").val();
	var url = "";
	$.ajax({
		type: "POST",
		url: "/creative_uploader/get_sample_ad_display_url",
		async: false,
		data: {
			version_id: version_id 
		},
		dataType: 'json',
		error: function(xhr, textStatus, error) {
			vl_show_jquery_ajax_error(xhr, textStatus, error);
			//alert("Error: Couldn't get spec ad sample url (#4387973)");
		},
		success: function(data, textStatus, xhr) {
			if(vl_is_ajax_call_success(data))
			{
				url = data.url;
			}
			else
			{
				vl_show_ajax_response_data_errors(data, 'failed to get spec ad');
			}
		}
	});

	return url;
}


function display_gallery_link()
{   
	display_spec_ad_preview_link();
	var dat_url = build_gallery_url();
	document.getElementById("gallery_link").innerHTML = '<br/>Gallery Page: <a target="_blank" href="'+dat_url+'">'+dat_url+'</a><br/>';
	
}

function build_gallery_url(is_iframe)
{
	var campaign_id = $("#campaign_select").val();
	var adset_text = $("#s2id_adset_select .select2-chosen").text();
	adset_text = adset_text.replace(/[^a-zA-Z0-9\s-.:_()#]|\s/g, "");
	var adset_version_id = $("#adset_version_variation_select").val();
	var adset_version_text = $("#adset_version_select option:selected").text();	

	var value = "";
	
	$.ajax({
		type: "POST",
		url: "/creative_uploader/get_gallery_url/",
		async: false,
		data: {
			'version_id': adset_version_id, 
			'campaign_id': campaign_id
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				var encoded_adset_version_id = data.encoded_adset_version_id;
				
				if (data.base_url !== '' && typeof is_iframe === 'undefined')
				{
					value = data.base_url+'crtv/get_adset/'+encoded_adset_version_id;
					//update_adset_link(adset_version_id);
				}
				else
				{
					value = getBaseURL()+'crtv/get_adset/'+encoded_adset_version_id;
				}
				
				if(data.vanity_string !== "")
				{
					value += "/" + data.vanity_string;
				}
				else if (typeof adset_text !== 'undefined' && adset_text !== '')
				{
					value += "/" + adset_text + adset_version_text;
				}
			}
			else
			{
				alert('Error 012527: unable to get gallery url');
			}
		},
		error: function(jqXHR, textStatus, error){
			alert('Error 012526: Server error when getting gallery url');
		}
	});
	
	return value;
}

function getBaseURL () 
{
	 return location.protocol + "//" + location.hostname + (location.port && ":" + location.port) + "/";
}

function switch_upload_tabs(ident)
{
	$(".upload_tab_content").hide();
	$("#upload_"+ident).show();
			
	if (ident == "fas")
	{
		prefill_new_campaign_lp($("#adset_version_variation_select").val());
	}
}

///////DFA STUFF
function show_publish_page()
{
	$("#variables_ui_zone").html("");
	if($("#publish_tab").hasClass('disabled'))
	{
		return false;
	}
	else
	{
		var version = $("#adset_version_select").val();  
		if(isNaN(parseFloat(adset)) || !isFinite(adset))
		{
			var adset = "";
		}
		toggle_loader_bar(true,2);
		$.ajax({
			type: "POST",
			url: "/creative_uploader/get_dfa_form/"+version, //m@ handle not logged in!!!
			async: true,
			data: { },
			dataType: 'html',
			error: function(){
				alert('error w6s61, whoa!');
			},
			success: function(msg){ 
				document.getElementById("output_zone").innerHTML = msg;
				build_advertiser_select_dropdown('nothing');
				build_adtech_advertiser_select_dropdown('0');
				//$("#upload_dfa").hide();
				$("#upload_adtech").hide();
				$("#upload_fas").hide();
			}
		});
	}
}

function show_ad_tags_page()
{
	var the_adset = $("#adset_select").val();
	var the_campaign = $("#campaign_select").val();
	var the_version = $("#adset_version_variation_select").val();
	if(the_adset == "select_new_dropdown_adset_insert" || the_adset == "none" || the_adset == "")
	{
		$("#output_zone").html('Please select an adset.');
		$("#output_adset_name").html("");
		$("#gallery_link").html("");
	}
	else
	{
		toggle_loader_bar(true,1);
		$.ajax({
			type: "POST",
			url: "/creative_uploader/get_tags_view/", 
			async: true,
			data: {c_id: the_campaign, version: the_version},
			dataType: 'html',
			error: function(){
				alert('Warning 1652: failed to get ad tags');
			},
			success: function(msg){ 
				$("#output_zone").html(msg);
				$('.ad_tag_poppers').popover();
				toggle_loader_bar(false,null);
			}
		});
	}
}

function build_advertiser_select_dropdown(id)
{
	$.ajax({
		type: "GET",
		url: "/publisher/get_all_advertisers/",
		async: true,
		data: { },
		dataType: 'html',
		error: function(){
			alert('Error 1290: Failed to get advertisers');
		},
		success: function(msg){ 
			document.getElementById("advertiser_dropdown").innerHTML=msg;
			document.getElementById("advertiser_dropdown").value=id;
			$("#advertiser_dropdown").select2({width: '300px'});
			$("#campaign_dropdown").select2({width: '300px'});
			toggle_loader_bar(false,null);
		}
	});
}

function build_adtech_advertiser_select_dropdown(id)
{
    $.ajax({
		type: "POST",
		url: "/adtech/get_advertisers",
		async: true,
		data: {},
		dataType: 'html',
		error: function()
		{
			alert('Error 11942: Failed to get Adtech advertisers');
		},
		success: function(msg)
		{
			document.getElementById("adtech_advertiser_dropdown").innerHTML = msg;
			document.getElementById("adtech_advertiser_dropdown").value = id;
			$("#adtech_advertiser_dropdown").select2({width: '300px'});
			$("#adtech_campaign_dropdown").select2({width: '300px'});
		}
	});
}

function advertiser_select_script()
{
	//first handle if it's a new advertiser
	document.getElementById("campaign_dropdown").innerHTML='';//clears out in case a new advertiser was created after an old one was picked
	document.getElementById("new_campaign_input").innerHTML='';
	document.getElementById("new_advertiser_input").innerHTML='';
	set_advertiser_status(null);
	set_campaign_status(null);
	var a_id = document.getElementById("advertiser_dropdown").value;
	if (a_id == "new"){
		show_new_advertiser_input_box(1); 
	}else if(a_id != "nothing"){
		toggle_loader_bar(true,1);
		build_campaign_select_dropdown(a_id,null);
	}
}

function adtech_advertiser_select_script()
{

    document.getElementById("adtech_campaign_dropdown").innerHTML='';//clears out in case a new advertiser was created after an old one was picked
    document.getElementById("new_adtech_campaign_input").innerHTML='';
    document.getElementById("new_adtech_advertiser_input").innerHTML='';
    set_adtech_advertiser_status(null);
    var a_id = document.getElementById("adtech_advertiser_dropdown").value;
    if (a_id == "new"){
	show_new_adtech_advertiser_input_box(1); 
	} else if(a_id != "0")
    { 
	toggle_loader_bar(true,1);
	build_adtech_campaign_select_dropdown(a_id, "0");
    }
    /*
		//first handle if it's a new advertiser
		document.getElementById("campaign_dropdown").innerHTML='';//clears out in case a new advertiser was created after an old one was picked
		document.getElementById("new_campaign_input").innerHTML='';
		document.getElementById("new_advertiser_input").innerHTML='';
		set_advertiser_status(null);
		set_campaign_status(null);
		var a_id = document.getElementById("advertiser_dropdown").value;
		if (a_id == "new"){
	show_new_advertiser_input_box(1); 
		}else if(a_id != "nothing"){
	toggle_loader_bar(true,1);
	build_campaign_select_dropdown(a_id,"new");
		}
	*/
}


function build_campaign_select_dropdown(advertiser_id, c_id)
{
	$.ajax({
		type: "GET",
		url: "/publisher/get_campaigns_from_advertiser/"+advertiser_id,
		async: false,
		data: { },
		dataType: 'html',
		error: function(){
			alert('error d65e1, whoa!');
		},
		success: function(msg){ 
			document.getElementById("campaign_dropdown").innerHTML=msg;
			if(c_id!=null)
			{
				document.getElementById("campaign_dropdown").value = c_id;
				if(c_id=="new"){
						show_new_campaign_input_box(1);
				}
			}
			$("#campaign_dropdown").select2({width: '300px'});
			toggle_loader_bar(false,null);
		}
	});
}


function build_adtech_campaign_select_dropdown(advertiser_id, c_id)
{
	$.ajax({
		type: "GET",
		url: "/adtech/get_campaigns_for_advertiser/"+advertiser_id,
		async: false,
		data: {},
		dataType: 'html',
		error: function()
		{
			alert('Warning, failed to get adtech campaigns for this advertiser');
		},
		success: function(msg)
		{
			document.getElementById("adtech_campaign_dropdown").innerHTML = msg;
			document.getElementById("adtech_campaign_dropdown").value = c_id;
			if(c_id == "new")
			{
				show_new_adtech_campaign_input_box(1);
			}
			$("#adtech_campaign_dropdown").select2({width: '300px'});
			toggle_loader_bar(false,null);
		}
	});
}

function show_new_advertiser_input_box(is_new)
{
		if(is_new)
		{
				document.getElementById("new_advertiser_input").innerHTML='<input class="span2"  type="text" placeholder="Name new advertiser" id="new_advertiser_name"> <button type="button"  id="advertiser_load_button" class="btn btn-success " onclick="load_new_advertiser_name_script();" data-loading-text="Loading..."><i class="icon-plus icon-white"></i> <span>Add</span></button>';
				document.getElementById("load_advertiser_status").innerHTML='';
				document.getElementById("load_campaign_status").innerHTML='';
		}
		else
		{
				document.getElementById("new_advertiser_input").innerHTML='';
				document.getElementById("load_advertiser_status").innerHTML='';
				document.getElementById("load_campaign_status").innerHTML='';
		}
}

function show_new_adtech_advertiser_input_box(is_new)
{
		if(is_new)
		{
				document.getElementById("new_adtech_advertiser_input").innerHTML='<input class="span2"  type="text" placeholder="Name new advertiser" id="new_adtech_advertiser_name"> <button type="button"  id="advertiser_load_button" class="btn btn-success " onclick="load_new_adtech_advertiser_name_script();" data-loading-text="Loading..."><i class="icon-plus icon-white"></i> <span>Add</span></button>';
				document.getElementById("load_adtech_advertiser_status").innerHTML='';
				document.getElementById("load_adtech_campaign_status").innerHTML='';
		}
		else
		{
				document.getElementById("new_adtech_advertiser_input").innerHTML='';
				document.getElementById("load_adtech_advertiser_status").innerHTML='';
				document.getElementById("load_adtech_campaign_status").innerHTML='';
		}
}

function load_new_advertiser_name_script()
{
		set_advertiser_status(null);
		if (document.getElementById("new_advertiser_name").value != "")
		{ 
				//var load_new_advertiser_url = "/publisher/insert_new_advertiser/"+document.getElementById("new_advertiser_name").value;
				toggle_loader_bar(true,2);
				load_new_advertiser_ajax(document.getElementById("new_advertiser_name").value);
		}else
		{
				alert("please name new advertiser");
		}     
}

function load_new_adtech_advertiser_name_script()
{
		set_adtech_advertiser_status(null);
		if (document.getElementById("new_adtech_advertiser_name").value != "")
		{ 
				//var load_new_advertiser_url = "/publisher/insert_new_advertiser/"+document.getElementById("new_advertiser_name").value;
				toggle_loader_bar(true,2);
				load_new_adtech_advertiser_ajax(document.getElementById("new_adtech_advertiser_name").value);
		}else
		{
				alert("please name new advertiser");
		}     
}

function load_new_advertiser_ajax(advertiser_name)
{   
		document.getElementById("new_campaign_input").innerHTML='';
		document.getElementById("new_advertiser_input").innerHTML='';
		var advertiser_name = (advertiser_name);
		$.ajax({
				type: "POST",
				url: "/publisher/insert_new_advertiser/",
				async: false,
				data: { adv_name: advertiser_name },
				dataType: 'html',
				error: function()
				{
						alert('Error 8412: Failed to load new advertiser.');
				},
				success: function(msg)
				{
				    
						var return_data = eval('(' +msg+')' );
						if(return_data.success )
						{
							build_advertiser_select_dropdown(return_data.advertiser_id);
							build_campaign_select_dropdown(return_data.advertiser_id,"new");
		
							set_advertiser_status('success','new advertiser loaded');
						}else
						{
							set_advertiser_status('important','error');
						}
				}
		});
}

function load_new_adtech_advertiser_ajax(advertiser_name)
{   
	document.getElementById("new_adtech_campaign_input").innerHTML='';
	document.getElementById("new_adtech_advertiser_input").innerHTML='';
	//var advertiser_name = (advertiser_name);
	$.ajax({
		type: "POST",
		url: "/adtech/insert_new_advertiser/",
		async: true,
		data: { adv_name: advertiser_name },
		dataType: 'html',
		error: function()
		{
			alert('Error 8412: Failed to load new advertiser.');
		},
		success: function(msg)
		{
			if(msg != "FAILURE")
			{
				advertiser_id = msg;
				build_adtech_advertiser_select_dropdown(advertiser_id);
				build_adtech_campaign_select_dropdown(advertiser_id,"new");
				//build_adtech_advertiser_select_dropdown();
				set_adtech_advertiser_status('success','new advertiser loaded');
			}else
			{
				set_adtech_advertiser_status('important','error');
			}
		}
	});
}


function load_new_campaign_ajax(campaign_name)
{     
		var a_id = document.getElementById("advertiser_dropdown").value;
		$.ajax({
				type: "POST",
				url: "/publisher/insert_new_campaign/",
				async: false,
				data: { advertiser_id: a_id, campaign_name: campaign_name, landing_page: escape(document.getElementById("new_landing_page").value) },
				dataType: 'html',
				error: function()
				{
			alert('Error 8328: Failed to load new campaign.');
				},
				success: function(msg)
				{
			var new_campaign_load_result = eval('(' +msg+')' );
			if(new_campaign_load_result.success)
			{
		build_campaign_select_dropdown(a_id,new_campaign_load_result.campaign_id);
		//alert(new_campaign_load_result.campaign_id+": "+new_campaign_load_result.campaign_name);
		set_campaign_status('success','new campaign loaded');
		document.getElementById("new_campaign_input").innerHTML='';
			}else
			{
		set_campaign_status('important','error'+msg);
			}
				}
		});
}

function load_new_adtech_campaign_ajax(campaign_name)
{     
	var a_id = document.getElementById("adtech_advertiser_dropdown").value;
	$.ajax({
		type: "POST",
		url: "/adtech/insert_new_campaign/",
		async: false,
		data: { advertiser_id: a_id, campaign_name: campaign_name, landing_page: escape(document.getElementById("new_adtech_landing_page").value) },
		dataType: 'html',
		error: function()
		{
			alert('Error 8328: Failed to load new campaign.');
		},
		success: function(msg)
		{
			if(msg != "FAILURE")
			{
				var campaign_id = msg;
				build_adtech_campaign_select_dropdown(a_id,msg);
				//alert(new_campaign_load_result.campaign_id+": "+new_campaign_load_result.campaign_name);
				set_adtech_campaign_status('success','new campaign loaded');
				document.getElementById("new_adtech_campaign_input").innerHTML='';
			}else
			{
				set_adtech_campaign_status('important','error'+msg);
			}
		}
	});
}


function campaign_select_script()
{
		document.getElementById("new_campaign_input").innerHTML='';
		set_campaign_status(null);
		if (document.getElementById("campaign_dropdown").value == "new"){
				show_new_campaign_input_box(1);
		}else{

		}
}

function adtech_campaign_select_script()
{
    
		document.getElementById("new_adtech_campaign_input").innerHTML='';
		set_adtech_campaign_status(null);
		if (document.getElementById("adtech_campaign_dropdown").value == "new"){
				show_new_adtech_campaign_input_box(1);
		}else{

		}
	
}

function load_new_campaign_name_script()
{
		set_campaign_status(null);
		if (document.getElementById("new_campaign_name").value != "" && document.getElementById("new_landing_page").value != "" && document.getElementById("new_landing_page").value != "http://www.")
		{ 
				toggle_loader_bar(true,2);
				load_new_campaign_ajax(document.getElementById("new_campaign_name").value);
		}else
		{
				alert("please make sure campaign name and landing page are correct");
		}     
}

function load_new_adtech_campaign_name_script()
{
		set_adtech_campaign_status(null);
		if (document.getElementById("new_adtech_campaign_name").value != "" && document.getElementById("new_adtech_landing_page").value != "" && document.getElementById("new_adtech_landing_page").value != "http://www.")
		{ 
				toggle_loader_bar(true,2);
				load_new_adtech_campaign_ajax(document.getElementById("new_adtech_campaign_name").value);
		}else
		{
				alert("please make sure campaign name and landing page are correct");
		}     
}



function prefill_new_campaign_lp(v_id)
{
	//alert("/publisher/get_c_lp/"+as_id);
	$.ajax(
		{
				type: "POST",
				url: "/publisher/get_c_lp/"+v_id,
				async: true,
				data: {  },
				dataType: 'html',
				error: function()
				{
					alert('error - huh?');
				},
				success: function(msg)
				{
					document.getElementById('new_landing_page').value = eval('(' +msg+')' );
					//alert(eval('(' +msg+')' ));
				}
		});
	
}

function prefill_new_campaign_adv_name(v_id)
{
	
	$.ajax(
		{
			type: "POST",
			url: "/publisher/get_c_adv_name/"+v_id,
			async: true,
			data: {  },
			dataType: 'html',
			error: function()
			{
				alert('error - huh?');
			},
			success: function(msg)
			{
				var campaign_name = document.getElementsByClassName('select2-chosen')[0].innerHTML;
				var today = new Date();
				var dd = today.getDate(); 
				var mm = today.getMonth()+1; 
				var yyyy = today.getFullYear().toString().substr(2,2);
				
				var todays_date = mm+'.'+dd+'.'+yyyy;
				var campaign_name_format = eval(msg)+' ('+campaign_name+' - '+todays_date+' - '+$("#adset_version_variation_select").val()+')';
				document.getElementById('new_campaign_name').value = campaign_name_format;
				}
		});
	
}


function prefill_new_adtech_campaign_lp(as_id)
{
	//alert("/publisher/get_c_lp/"+as_id);
	$.ajax(
		{
				type: "POST",
				url: "/publisher/get_c_lp/"+as_id,
				async: true,
				data: {  },
				dataType: 'html',
				error: function()
				{
					alert('error - huh?');
				},
				success: function(msg)
				{
					document.getElementById('new_adtech_landing_page').value = eval('(' +msg+')' );
					//alert(eval('(' +msg+')' ));
				}
		});
	
}

function show_new_campaign_input_box(is_new)
{
	if(is_new)
	{
		document.getElementById("new_campaign_input").innerHTML='<input   type="text" value="campaign name" id="new_campaign_name"> <input  id="new_landing_page" type="url" value="http://www." > <button  type="button" id="campaign_load_button" class="btn btn-success" onclick="load_new_campaign_name_script();"" data-loading-text="Loading...> <i class="icon-plus icon-white"></i> <span>Add</span></button> ';
		prefill_new_campaign_adv_name($("#adset_version_variation_select").val());
		prefill_new_campaign_lp($("#adset_version_variation_select").val());
	}
	else
	{
		document.getElementById("new_campaign_input").innerHTML='';
	}
}

function show_new_adtech_campaign_input_box(is_new)
{
		if(is_new)
		{
	document.getElementById("new_adtech_campaign_input").innerHTML='<input   type="text" placeholder="Name new campaign" id="new_adtech_campaign_name"> <input  id="new_adtech_landing_page" type="url" value="http://www." id="new_landing_page"> <button  type="button" id="adtech_campaign_load_button" class="btn btn-success" onclick="load_new_adtech_campaign_name_script();"" data-loading-text="Loading...> <i class="icon-plus icon-white"></i> <span>Add</span></button> ';
	prefill_new_adtech_campaign_lp($("#adset_select").val());
		}
		else
		{
				document.getElementById("new_adtech_campaign_input").innerHTML='';
		}
}  

//if label is set to null, then it will clear the div
function set_advertiser_status(label, copy)
{   
		if(label === null)
		{
				document.getElementById("load_advertiser_status").innerHTML ='';
		}
		else
		{
				document.getElementById("load_advertiser_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
}

function set_adtech_advertiser_status(label, copy)
{   
		if(label === null)
		{
				document.getElementById("load_adtech_advertiser_status").innerHTML ='';
		}
		else
		{
				document.getElementById("load_adtech_advertiser_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
}

function set_campaign_status(label, copy)
{   
		if(label === null)
		{
				document.getElementById("load_campaign_status").innerHTML ='';
		}
		else
		{
				document.getElementById("load_campaign_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
}

function set_adtech_campaign_status(label, copy)
{   
		if(label === null)
		{
				document.getElementById("load_adtech_campaign_status").innerHTML ='';
		}
		else
		{
				document.getElementById("load_adtech_campaign_status").innerHTML = '<span class="label label-'+label+'">'+copy+'</span>';
		} 
}

function load_adset_to_dfa()
{
		var adset_id = $("#adset_select").val();
		var version_id = $("#adset_version_variation_select").val();
		if(adset_id != "select_new_dropdown_adset_insert" && adset_id != "none" && adset_id != "")//check inputs  m@!!!!
		{
		$.ajax({
				type: "POST",
				url: "/creative_uploader/get_creatives/",
				async: true,
				data: {version: version_id},
				dataType: 'html',
				error: function()
				{
					alert('Warning, failed to get creatives for adset: '+adset_id+' and version: '+version_id+'');
				},
				success: function(msg)
				{
					publish_creative(adset_id, eval('(' +msg+')' ));
				}
		});
		}
		else
		{
	alert('check your inputs');
		}
}

function load_adset_to_adtech()
{
		var adset_id = $("#adset_select").val();
		var version_id = $("#adset_version_variation_select").val();
		if(adset_id != "select_new_dropdown_adset_insert" && adset_id != "none" && adset_id != "")//check inputs  m@!!!!
		{
		$.ajax({
				type: "POST",
				url: "/creative_uploader/get_creatives/",
				async: true,
				data: {version: version_id},
				dataType: 'html',
				error: function()
				{
					alert('Warning, failed to get creatives for adset: '+adset_id+' and version: '+version_id+'');
				},
				success: function(msg)
				{
					publish_creative_to_adtech(adset_id, eval('(' +msg+')' ));
				}
		});
		}
		else
		{
	alert('check your inputs');
		}
    
}

function load_adset_to_fas()
{
	var adset_id = $("#adset_select").val();
	var version_id = $("#adset_version_variation_select").val();
	var landing_page = $("#new_landing_page").val();
	
	if (adset_id != "select_new_dropdown_adset_insert" && adset_id != "none" && adset_id != "")//check inputs  m@!!!!
	{
		$.ajax({
			type: "POST",
			url: "/creative_uploader/get_creatives/",
			async: true,
			data: {version: version_id},
			dataType: 'html',
			error: function()
			{
				alert('Warning, failed to get creatives for adset: '+adset_id+' and version: '+version_id+'');
			},
			success: function(msg)
			{
				publish_creative_to_fas(adset_id, eval('(' +msg+')' ), landing_page);
			}
		});
	}
	else
	{
		alert('check your inputs');
	}
}

var total_creatives_to_load = null;
function publish_creative(adset_id,creative_array)
{
	if(total_creatives_to_load == null)
	{
		document.getElementById("insert_creative_status").innerHTML = '';
		total_creatives_to_load = creative_array.length; //set it the first time through
	}

	if(creative_array.length == 0){
		total_creatives_to_load = null;
		toggle_loader_bar(false,0);
		//all done
	}
	else
	{
		var this_creative = creative_array[0].size;
		loader_bar_width(Math.round(100*(total_creatives_to_load-creative_array.length+1)/total_creatives_to_load));
		document.getElementById("insert_creative_status").innerHTML += '<span><br>loading '+this_creative+': </span>'
		creative_array.splice(0, 1);//(0,1) removes one element starting on the zeroeth element
		$.ajax({
			type: "POST",
			url: "/publisher/publish_creative/",
			async: true,
			data: { cr_size: this_creative, 
				dfa_a_id: document.getElementById("advertiser_dropdown").value,
				dfa_c_id: document.getElementById("campaign_dropdown").value,
				vl_c_id: $("#campaign_select").val(),
				version: $('#adset_version_variation_select').val()},
			dataType: 'html',
			error: function()
			{
				alert('Warning: Error publishing adset: '+adset_id+' creative: '+this_creative);
			},
			success: function(msg)
			{
				document.getElementById("insert_creative_status").innerHTML += msg;
				publish_creative(adset_id,creative_array);
			}
		});
	}
}

function publish_creative_to_fas(adset_id, creative_array, landing_page)
{
	if (total_creatives_to_load == null)
	{
		document.getElementById("insert_creative_status").innerHTML = '';
		total_creatives_to_load = creative_array.length; //set it the first time through
	}

	if (creative_array.length == 0){
		total_creatives_to_load = null;
		toggle_loader_bar(false,0);
		//all done
	}
	else
	{
		var this_creative = creative_array[0].size;
		loader_bar_width(Math.round(100*(total_creatives_to_load-creative_array.length+1)/total_creatives_to_load));
		document.getElementById("insert_fas_creative_status").innerHTML += '<span><br>loading '+this_creative+': </span>'
		creative_array.splice(0, 1);//(0,1) removes one element starting on the zeroeth element
		$.ajax({
			type: "POST",
			url: "/fas/publish_creative/",
			async: true,
			data:
			{ 
				cr_size: this_creative,				
				vl_c_id: $("#campaign_select").val(),
				version: $('#adset_version_variation_select').val(),
				landing_page: landing_page
			},
			dataType: 'html',
			error: function()
			{
				alert('Warning: Error publishing adset: '+adset_id+' creative: '+this_creative);
			},
			success: function(msg)
			{
				document.getElementById("insert_fas_creative_status").innerHTML += msg;
				publish_creative_to_fas(adset_id, creative_array, landing_page);
			}
		});
	}
}

function publish_creative_to_adtech(adset_id,creative_array)
{
	if(total_creatives_to_load == null)
	{
		document.getElementById("insert_adtech_creative_status").innerHTML = '';
		total_creatives_to_load = creative_array.length; //set it the first time through
	}

	if(creative_array.length == 0){
		total_creatives_to_load = null;
		toggle_loader_bar(false,0);
		//all done
	}
	else
	{
		var this_creative = creative_array[0].size;
		loader_bar_width(Math.round(100*(total_creatives_to_load-creative_array.length+1)/total_creatives_to_load));
		document.getElementById("insert_adtech_creative_status").innerHTML += '<span><br>loading '+this_creative+': </span>'
		creative_array.splice(0, 1);//(0,1) removes one element starting on the zeroeth element
		$.ajax({
			type: "POST",
			url: "/adtech/publish_adtech_creative/",
			async: true,
			data: { cr_size: this_creative, 
				adtech_a_id: document.getElementById("adtech_advertiser_dropdown").value,
				adtech_c_id: document.getElementById("adtech_campaign_dropdown").value,
				vl_c_id: $("#campaign_select").val(),
				version: $('#adset_version_variation_select').val()},
			dataType: 'html',
			error: function()
			{
				alert('Warning: Error publishing adset: '+adset_id+' creative: '+this_creative);
			},
			success: function(msg)
			{
				document.getElementById("insert_adtech_creative_status").innerHTML += msg;
				publish_creative_to_adtech(adset_id,creative_array);
			}
		});
	}
}

// TODO: use new parameter for download_spreadsheet()
function download_spreadsheet(adset_id, version_id, ad_server)
{
	window.open("/publisher/get_tags_spreadsheet/"+adset_id+"/"+version_id+"/"+document.getElementById("include_ad_choices_"+ad_server+"_span").checked+"/"+ad_server);
}

// TODO: use new parameter for text_tags()
function text_tags(version_id, ad_server)
{
	window.open("/publisher/get_ad_tags/"+version_id+"/"+document.getElementById("include_ad_choices_"+ad_server+"_span").checked+"/"+ad_server);
}

function render_tags(version_id, ad_server)
{
	window.open("/publisher/render_ad_tags/"+version_id+"/"+ad_server+"/"+document.getElementById("include_ad_choices_"+ad_server+"_span").checked);
}

function oando_text_tags(version_id, ad_server)
{
	window.open("/publisher/get_fas_tags_for_oando/"+version_id+"/"+ad_server);
}


function reset_version_index()
{
		var version = document.getElementById('adset_version_select');
		setSelectedIndex(version, previous);
}

function reset_variation_index()
{
		var version = document.getElementById('adset_version_variation_select');
		setSelectedIndex(version, previous_variation);
}

function populate_local_ads()
{
	$('#variables_ad_all').html('<iframe class="pull-left" src="/creative_uploader/get_local_ad/320x50" style="overflow:visible; padding-bottom: 10px; width: 320px; height: 50px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
	$('#variables_ad_all').append('<div class="pull-left" style="padding-left:50px;padding-top:15px;font-weight:bold;font-size:35px;">Flash</div>');
		$('#variables_ad_all').append('<iframe src="/creative_uploader/get_local_ad/728x90" style="overflow:visible; padding-bottom: 5px; width: 728px; height: 90px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all').append('<iframe class="pull-left" src="/creative_uploader/get_local_ad/160x600" style="overflow:visible;  width: 160px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all').append('<iframe class="" src="/creative_uploader/get_local_ad/336x280" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 336px; height: 280px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all').append('<iframe class="" src="/creative_uploader/get_local_ad/300x250" style="overflow:visible; padding-left: 10px; width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all').append('<iframe class="" src="/creative_uploader/get_local_ad/468x60" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 468px; height: 60px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
	
	$('#variables_ad_all_h5').html('<iframe class="pull-left" src="/creative_uploader/get_local_ad/320x50/1" style="overflow:visible; padding-bottom: 10px; width: 320px; height: 50px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
	$('#variables_ad_all_h5').append('<div class="pull-left" style="padding-left:50px;padding-top:15px; font-weight:bold;font-size:35px;">HTML5</div>');
		$('#variables_ad_all_h5').append('<iframe src="/creative_uploader/get_local_ad/728x90/1" style="overflow:visible; padding-bottom: 5px; width: 728px; height: 90px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all_h5').append('<iframe class="pull-left" src="/creative_uploader/get_local_ad/160x600/1" style="overflow:visible;  width: 160px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all_h5').append('<iframe class="" src="/creative_uploader/get_local_ad/336x280/1" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 336px; height: 280px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all_h5').append('<iframe class="" src="/creative_uploader/get_local_ad/300x250/1" style="overflow:visible; padding-left: 10px; width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
		$('#variables_ad_all_h5').append('<iframe class="" src="/creative_uploader/get_local_ad/468x60/1" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 468px; height: 60px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');

}

//XXX
function populate_new_local_ads(builder_version)
{
	//get variables that are in ui
	if(builder_version===undefined){
		builder_version = $( "#builder_version_select" ).val();
	}
	
	var version_id = $("#adset_version_variation_select").val();
	
	//alert('about to populate new local ads - builder version:  '+builder_version);
	var formData = form2js('ui_control_form', '.', true, function(node){});
	///write variables to local dir
	$.ajax({
		async:true,
		type: "POST",
		data: {"vars": JSON.stringify(formData, null, '\t')},
		url: "/creative_uploader/write_local_variables_json",
		dataType: "html",
		success: function(data) {
			var html_only_version = '<?php echo implode(',', html5_creative_set::get_html5_builder_versions()); ?>'.indexOf(builder_version) > -1;
			var multi_size_version = builder_version > 699 || builder_version < 206 || html_only_version;
			if (multi_size_version) {
				//alert(data);
				$('#variables_ad_all').html('<iframe class="pull-left" src="/creative_uploader/get_new_local_ad/'+builder_version+'/320x50/0/'+version_id+'" style="overflow:visible; padding-bottom: 10px; width: 320px; height: 50px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				$('#variables_ad_all').append('<div class="pull-left" style="padding-left:50px;padding-top:15px;font-weight:bold;font-size:35px;">Default Ad</div>');
				$('#variables_ad_all').append('<iframe src="/creative_uploader/get_new_local_ad/'+builder_version+'/728x90/0/'+version_id+'" style="overflow:visible; padding-bottom: 5px; width: 728px; height: 90px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				$('#variables_ad_all').append('<iframe class="pull-left" src="/creative_uploader/get_new_local_ad/'+builder_version+'/160x600/0/'+version_id+'" style="overflow:visible;  width: 160px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				$('#variables_ad_all').append('<iframe class="" src="/creative_uploader/get_new_local_ad/'+builder_version+'/336x280/0/'+version_id+'" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 336px; height: 280px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				$('#variables_ad_all').append('<iframe class="" src="/creative_uploader/get_new_local_ad/'+builder_version+'/300x250/0/'+version_id+'" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				$('#variables_ad_all').append('<iframe class="" src="/creative_uploader/get_new_local_ad/'+builder_version+'/468x60/0/'+version_id+'" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 468px; height: 60px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				if(!html_only_version)
				{
					$('#variables_ad_all').append('<div id="med-rectangle"></div>');
					$('#variables_ad_all_h5').html('<iframe class="pull-left" src="/creative_uploader/get_new_local_ad/'+builder_version+'/320x50/1/'+version_id+'" style="overflow:visible; padding-bottom: 10px; width: 320px; height: 50px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
					$('#variables_ad_all_h5').append('<div class="pull-left" style="padding-left:50px;padding-top:15px; font-weight:bold;font-size:35px;">Force HTML5</div>');
					$('#variables_ad_all_h5').append('<iframe src="/creative_uploader/get_new_local_ad/'+builder_version+'/728x90/1/'+version_id+'" style="overflow:visible; padding-bottom: 5px; width: 728px; height: 90px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
					$('#variables_ad_all_h5').append('<iframe class="pull-left" src="/creative_uploader/get_new_local_ad/'+builder_version+'/160x600/1/'+version_id+'" style="overflow:visible;  width: 160px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
					$('#variables_ad_all_h5').append('<iframe class="" src="/creative_uploader/get_new_local_ad/'+builder_version+'/336x280/1/'+version_id+'" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 336px; height: 280px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
					$('#variables_ad_all_h5').append('<iframe class="" src="/creative_uploader/get_new_local_ad/'+builder_version+'/300x250/1/'+version_id+'" style="overflow:visible; padding-left: 10px; width: 300px; height: 250px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
					$('#variables_ad_all_h5').append('<iframe class="" src="/creative_uploader/get_new_local_ad/'+builder_version+'/468x60/1/'+version_id+'" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 468px; height: 60px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				}
				else
				{
					$('#variables_ad_all_h5').empty();
				}
			} else {
				//var script = document.createElement( 'script' );
				//script.type = 'text/javascript';
				//script.src = '/creative_uploader/get_new_local_ad/'+builder_version+'/300x250/0';
				//document.body.appendChild( script );
				//document.getElementById( "med-rectangle" ).innerHTML = '<div src="/creative_uploader/get_new_local_ad/'+builder_version+'/300x250/0"></div>';
				//document.getElementById( "med-rectangle" ).innerHTML = '<script language="JavaScript1.1" src="/creative_uploader/get_new_local_ad/'+builder_version+'/300x250/0"><\/script>';
				$('#variables_ad_all').html('<iframe class="" id="iframe1" src="/creative_uploader/get_new_local_ad/'+builder_version+'/728x90/0" style="overflow:visible; padding-left: 10px; padding-bottom: 5px;  width: 728px; height: 600px" scrolling="no" marginwidth="0" marginheight="0" frameBorder="0" allowfullscreen><p>Your browser does not support iframes.</p></iframe>');
				$('#iframe1').load(function() {
					$('#iframe1').contents().find('head').append('<style>.adbox{float:right;}</style>');
					//console.log($('#iframe1').contents().find('html').html());
				});
			}
		},
		error: function(data) {
				console.log("Error: error processing update_variables_file");
		}
	});
	verify_version_publish($( "#adset_version_variation_select" ).val());

}

function update_variables_file()
{
		 var contents = $('#variables_textarea').val();
		$.ajax({
	async:false,
	type: "POST",
	data: {"variables": contents},
	url: "/creative_uploader/update_variables_file",
	dataType: "text",
	success: function(data) {
			if(data == 'Success')
			{
		//update successful
		populate_local_ads();
			}
			else
			{
		alert(data);
			}
	},
	error: function(data) {
			console.log("Error: error processing update_variables_file");
	}
		});
}

function toggle_xhide(size)
{
		//$('.variables_tab').addClass('x_pane_hide');
		//$('#variables_ad_'+size).removeClass('x_pane_hide');
}

function reset_variables_tab(btn)
{
	if(typeof btn !== 'undefined') 
	{
		var data_url = $(btn).attr('data-url');
		var split_arr2 = data_url.split('variables');
		if(split_arr2.length > 1 && split_arr2[1] == '.xml' && $('#variables_ui_tab').hasClass('active'))
		{
			//is xml file present data-url has 'variables' in it and variables tab is active.
			$('#output_zone').html('');
			$("#variables_ui_zone").html('no variables file found on stage...');
		}
	}
	else
	{
		alert("Warning 3215: reset_variables_tab argument 1 undefined");
	}
}

function handle_upload_stop()
{
		$("#summary_tab").removeClass('active');
		$("#preview_tab").removeClass('active');
		$("#publish_tab").removeClass('active');
		$("#ad_tags_tab").removeClass('active');
		//$("#variables_tab").addClass('active');
		//check_for_variables_file();
		$("#variables_ui_tab").addClass('active');
		load_variables_ui();
}

$("#campaign_link_select").change(function() {
	var campaign_id = $(this).val();
	if(campaign_id != "none" && campaign_id != "")
	{
		$("#link_adset_to_campaign_button").prop('disabled', false);
	}
	else
	{
		$("#link_adset_to_campaign_button").prop('disabled', 'disabled');
	}
});

$('#fileupload').bind('fileuploadstop', handle_upload_stop);
$('#fileupload').bind('fileuploaddestroyed', handle_upload_stop);

////DFA STUFF
function modal_open(form_id)
{
        $.ajax({
                url: '/banner_intake/ajax_review_single/'+form_id,
                type: 'POST',
                dataType: 'html',
                success: function(data) {
                        $("#adset_requests_preview_modal .modal-body").html(data);
                        $("#adset_requests_preview_modal").modal("show");
                }
        });
}

function display_creative_request_link(){
        
	var adset_version_id = $("#adset_version_variation_select").val();
	
	if (typeof adset_version_id == 'undefined' || adset_version_id == '0' || adset_version_id == 'new'){
		return;
	}
	
	$.ajax({
                url: '/banner_intake/get_creative_request_id_for_adset_version',
                type: 'POST',
		async: true,
                dataType: 'json',
		data:{
			version_id:adset_version_id
		},
                success: function(result){
			if (typeof result !== 'undefined' && result.status == 'success'){
				$("#creative_request_link a").html(result.adset_request_id);
				$("#creative_request_link a").attr("href","javascript:modal_open("+result.adset_request_id+");");
				$("#creative_request_link").show();
			}else{
				$("#creative_request_link").hide();
			}
                }
        });	
}

function handle_overwrite_change(checked)
{
	if(checked)
	{
		$("#push_description").html("REPLACE");
		$("#push_description").attr('class', 'label label-important');
	}
	else
	{
		$("#push_description").html("APPEND");
		$("#push_description").attr('class', 'label label-warning');		
	}
}

function reset_push_button(ad_server)
{
	if($("#add_"+ad_server+"_tags_to_tradedesk_button").hasClass('disabled'))
	{
		$("#add_"+ad_server+"_tags_to_tradedesk_button").attr('class', 'btn btn btn-info');
		$("#add_"+ad_server+"_tags_to_tradedesk_button").data('pushdisabled', false);
	}
}


function insert_variation()
{
	$("#new_variation_name_control").removeClass('error');
	var new_variation_name = $('#new_variation_box').val();
	if(/^[a-zA-Z0-9-_)( ]*$/.test(new_variation_name) == false)
	{
		$("#page_status").html('<div class="alert alert-important span12">Variation Name Contains Illegal Entry "'+new_variation_name+'".</div>');
		return false;
	}
	if(new_variation_name == "" || new_variation_name == undefined || new_variation_name == null)
	{
		$("#new_variation_name_control").addClass('error');
		return;
	}
	else
	{
		$('#new_variation_box').val("");
		$("#modal_variation_box").modal('hide');		
		toggle_loader_bar(true,1);
		var adset = $("#adset_select").val();
		var parent_version = $("#adset_version_select").val();
		var campaign_id = $("#campaign_select").select2('val');
		$.ajax({
			type: "POST",
			url: "/creative_uploader/insert_new_variation/",
			async: true,
			data: {variation_name: new_variation_name, adset: adset, parent_version: parent_version, campaign_id: campaign_id},
			dataType: 'json',
			error: function(){
				$("#page_status").html('<div class="alert alert-important span12">Error 0409: Error inserting adset.</div>');
			},
			success: function(msg){
				if(msg.is_success == true)
				{
					version_drop_select(parent_version, msg.data);
				}				
				else
				{
					$("#page_status").html('<div class="alert alert-important span12">Error 44211: Duplicate Entry: "'+new_variation_name+'".</div>');
				}
			}
		});
	}
}

</script>

<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="/js/FILE_UPLOADER/uploader-js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="/blueimp/js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="/blueimp/js/load-image.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="/blueimp/js/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS and Bootstrap Image Gallery are not required, but included for the demo -->
<script src="/blueimp/js/bootstrap.min.js"></script>
<script src="/blueimp/js/bootstrap-image-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<!-- <script src="/blueimp/js/uploader-js/jquery.iframe-transport.js"></script> -->
<!-- The basic File Upload plugin -->
<script src="/js/FILE_UPLOADER/uploader-js/jquery.fileupload.js?v=<?php echo CACHE_BUSTER_VERSION ?>"></script>
<!-- The File Upload file processing plugin -->
<script src="/js/FILE_UPLOADER/uploader-js/jquery.fileupload-fp.js"></script>
<!-- The File Upload user interface plugin -->
<script src="/js/FILE_UPLOADER/uploader-js/jquery.fileupload-ui.js"></script>
<!-- The localization script -->
<script src="/js/FILE_UPLOADER/uploader-js/locale.js"></script>
<!-- The main application script -->
<script src="/js/FILE_UPLOADER/uploader-js/main.js"></script>

<?php
write_vl_platform_error_handlers_html_section();
write_vl_platform_error_handlers_js();
?>

</body>
</html>
