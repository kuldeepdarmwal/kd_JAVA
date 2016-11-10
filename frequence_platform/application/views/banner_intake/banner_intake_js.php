<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/vendor/jquery.ui.widget.js"></script>
<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script src="/assets/js/materialize_freq.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload-process.js"></script>

<script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
<script src="/libraries/external/json/json2.js"></script>
<script src="/libraries/external/select2/select2.js"></script>
<!-- WARNING! This is a modified version of the fuelux library, to prevent it from re-loading bootstrap. Please do not replace. -->
<script src="/libraries/external/fuelux/fuelux.js" type="text/javascript"></script>

<script type="text/javascript" charset="UTF-8">
var g_request_src = "<?php echo $source; ?>";
var g_scene_counter = <?php echo $g_scene_counter; ?>;
var g_variation_counter = 2;
var upload_files_text = "Drag and Drop your files anywhere on the page, or click the 'UPLOAD FILES...' button to get started";
var upload_files_for_banner_intake_text = "Upload your logo, image files, and any other brand assets required";
var advertiser_name = "<?php echo $advertiser_name; ?>";
var source_table = "<?php echo $source_table; ?>";
var advertiser_id = "<?php echo $advertiser_id; ?>";

function delete_asset(file_counter)
{
	$('#asset_row_'+file_counter).remove();
}

function add_scene()
{
	g_scene_counter++;
	var scene_string = '<div class="input-field col s12 m4 l4" id="scene_'+g_scene_counter+'_control_group"> \
									<textarea class=\"materialize-textarea\" id="scene_'+g_scene_counter+'_input" placeholder="the story continues..." name="scenes[]"></textarea>  \
									<a id="remove_scene_'+g_scene_counter+'_button" onclick="delete_scene()" class="btn-floating waves-effect waves-light red"><i class="material-icons">delete</i></a> \
									<label for="scene_'+g_scene_counter+'_input" class="active">Scene '+g_scene_counter+'</label> \
								</div>';
							
	$( "#remove_scene_"+(g_scene_counter-1)+"_button" ).remove();
	$( "#scene_1_control_group" ).append( scene_string );
	$('#scene_'+g_scene_counter+'_input').placeholder();
}

function delete_scene()
{
	$('#scene_'+g_scene_counter+'_control_group').hide(200,function(){
		$('#scene_'+g_scene_counter+'_control_group').remove(); 
		g_scene_counter--;
		if(g_scene_counter>1)
		{
			var the_delete_button_string = ' <a id="remove_scene_'+g_scene_counter+'_button" onclick="delete_scene()" class="btn-floating waves-effect waves-light red"><i class="material-icons">delete</i></a>';
			$( '#scene_'+g_scene_counter+'_input' ).after(the_delete_button_string);
		}
	});
}

function is_form_valid(form_data)
{
	var return_ok = true;
	//remove all errors
	$('.control-group').removeClass('error');
	//check advertiser name
	if(form_data.flight_details.advertiser_name == undefined || form_data.flight_details.advertiser_name == '')
	{
		$('#advertiser_name_control_group').addClass('error');
		$('#advertiser_help').html('Advertiser name is a required field');
		return_ok = false;
	}

	return return_ok;
}

$(document).ready( function () {
	$(this).scrollTop(0);
	$('select').material_select();
	$(".error").hide().html("");
	
	/* Preload IO data */
	var preload_data = <?php echo $io_preload_data; ?>;
	var product_preload = <?php echo $product_preload; ?>;

	if (preload_data) {
		if (preload_data.advertiser_name !== null) $('#creative_name').val(preload_data.advertiser_name);
		if (preload_data.advertiser_website !== null) $('#landing_page, #advertiser_website').val(preload_data.advertiser_website);
		if (preload_data.io_advertiser_id !== null) {
			$('#advertiser').val(preload_data.io_advertiser_id);
		} else {
			$('#advertiser').val('unverified_advertiser_name');
		}
	}

	if (product_preload) {
		$('select#product').val(product_preload);
		$('select#product').material_select();
		handleProductSelectChange();
	}

	$('input, textarea').placeholder();
	var g_file_counter = <?php echo $g_file_counter; ?>;
	var files_upload_counter = 0;
	var files_uploaded_counter = 0;

	/* File upload */
	var url = '/banner_intake/creative_files_upload';
	$('#file_upload').fileupload({
        url: url,
        type: 'POST',
		dataType: 'json',
        add: function(e, data) {
		$("#file_upload_error").hide();
		var product = $("#product").val();
		var goUpload = true;
		
		if (product === 'Preroll')
		{
			var uploadFile = data.files[0];
			if (!(/\.(webm|mkv|flv|flv|vob|ogv|ogg|drc|avi|mov|wmv|yuv|rm|rmvb|asf|mp4|m4p|m4v|mpg|mp2|mpeg|mpe|mpv|m2v|svi|3gp|3g2|mxf|roq|nsv)$/i).test(uploadFile.name)) {
				$("#file_upload_error span").html('You must select video files');
				$("#file_upload_error").show();
				goUpload = false;
			}
		}
		
		if (goUpload == true) {
			data.el = $('<div class="file col s12 m3 l3" data-file="'+ data.files[0].name +'"/>');
			$('#files').append(data.el);

			var file_upload_html = '<div class="row uploaded-file">\
						    <div class="col s12"><label class="control-label" style="font-size:0.85em;">'+data.files[0].name.substr(0,20) + '...</label></div>\
						    <div class="col s12"><div class="progress col s12 m12 l12">\
							<div class="indeterminate"></div>\
						    </div></div>\
						</div>\
						';

			$(data.el).html(file_upload_html);
			data.submit();
			$('#main_files_alert')
				.removeClass('alert-success')
				.addClass('alert-info')
				.html('<b>Heads Up!</b> Your files are currently in the process of uploading. Please do not refresh or close your browser window until the uploads are complete.');
			files_upload_counter++;
		}
        },
        fail: function(e, data) {
        	$(data.el).html('<div class="alert alert-danger">'+ data.files[0].name +' failed to upload!</div>');
        },
        done: function(e, data) {
		files_uploaded_counter++;
		var result = data.result;
		var file_size = Math.round(Number(data.files[0].size)/1000);
		g_file_counter++;
		var html = '<div class="row uploaded-file">\
				    <div class="col s12"><label class="control-label" style="font-size:0.85em;">'+result.data.Key.substr(7) + '...</label></div>\
				    <div class="col s6"><label class="control-label" style="font-size:0.85em;">File Size - <b>'+file_size+'KB</b></label></div>\
				    <div class="col s3 offset-s3"><button class="btn-floating waves-effect waves-light red delete_file" data-bucket="'+ result.data.Bucket +'" data-name="'+ result.data.Key +'"><i class="tiny material-icons right">delete</i></button></div>\
				';

		//var html = '<div><b>'+ result.data.Key.substr(7) +'</b> <button class="btn-floating waves-effect waves-light red delete_file" data-bucket="'+ result.data.Bucket +'" data-name="'+ result.data.Key +'"><i class="material-icons">delete</i></button></div>';
		html += '<input type="hidden" name="creative_files['+ g_file_counter +'][url]" value="'+ result.response.ObjectURL +'"/>';
		html += '<input type="hidden" name="creative_files['+ g_file_counter +'][name]" value="'+ result.data.Key +'"/>';
		html += '<input type="hidden" name="creative_files['+ g_file_counter +'][bucket]" value="'+ result.data.Bucket +'"/>';
		html += '<input type="hidden" name="creative_files['+ g_file_counter +'][type]" value="'+ result.data.ContentType +'"/>';
		html += '<input type="hidden" name="creative_files['+ g_file_counter +'][size]" value="'+ file_size +'"/>';
		
		html += '</div>';
		$(data.el).html(html);
		if (files_upload_counter === files_uploaded_counter)
		{
			files_upload_counter = 0;
			files_uploaded_counter = 0;
			
			$('#main_files_alert')
        		.removeClass('alert-info')
        		.addClass('alert-success')
    			.html('<b>Your files have finished uploading!</b> You still need to hit \'Submit Request\' at the bottom of this page to save your files.');
		}
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');

     // Delete buttons for files
     $(document).on('click', 'button.delete_file', function(e){
		e.preventDefault();
		var $el = $(this).closest('div.file');
		$el.animate({opacity: 0.5}, 200);
		$.ajax({
			url: '/banner_intake/creative_files_delete',
			type: 'POST',
			data: {
				'name': $(this).data('name'),
				'bucket': $(this).data('bucket')
			},
			success: function(data, status) {
				$el.fadeOut(200);
				$el.remove();
			},
			error: function(e, data) {
				$el.fadeOut(200);
				$el.remove();
			}
		});
		return false;
	});

	// prevent browser's default action for drag+drop
	$(document).bind('drop dragover', function(e){
		e.preventDefault();
	});

	// If the option changes, show the specified element
	function check_option(check, el)
	{
		if (check)
		{
			$(el).show(200);
		}
		else
		{
			$(el).hide(200);
		}
	}

	$( "#cta_select" ).on('change', function() {
		check_option($(this).val() == 'other', '#other_cta_text');
	});
	check_option($('#cta_select').val() == 'other', '#other_cta_text');

	$( "#is_video" ).on('click', function() {
		check_option($(this).is(':checked'), '#video_details');
	});

	$( "#is_map" ).on('click', function() {
		check_option($(this).is(':checked'), '#map_details');
	});

	$( "#is_social" ).on('click', function() {
		check_option($(this).is(':checked'), '#social_details');
	});

	$( "#has_variations" ).on('click', function() {
		check_option($(this).is(':checked'), '#variations_details');
	});	
	
	$(".preroll_video_url").click(function(){
		$(".video_url_containers").hide();
		$("#"+$(this).attr('id')+"_container").show('slow');
	});
		
	//Request Type select box
	$("#requesttype").change(function(){
		$("#file_upload_error").hide();
		$(".ad_tags_tag").val("").trigger("paste");
		$("*").removeClass("invalid valid invalidselect validselect");
		$("#files").html("");
		$(".ad_tags_tag").trigger('autoresize');
		$("#main_files_alert").html(upload_files_text).removeClass("alert-info alert-success").addClass("alert");
		var old_val = $(this).attr("data-oldval");
		var new_val = $(this).val();
		
		if (old_val !== new_val)
		{
			if (new_val === 'Custom Banner Design')
			{
				$("#upload_your_own_card_title").text('Images and Assets');
				$("#main_files_alert").text(upload_files_for_banner_intake_text);
			}
			else
			{
				$("#upload_your_own_card_title").text('Upload Your Own Files');
				$("#main_files_alert").text(upload_files_text);
			}
			
			if (new_val === 'Ad Tags')
			{
				$("#landing_page_parent").hide();
				$("#newadvertiserparent").removeClass('offset-m2 offset-l2').addClass('offset-m6 offset-l6');
			}
			else
			{
				$("#landing_page_parent").show();
				$("#newadvertiserparent").removeClass('offset-m6 offset-l6').addClass('offset-m2 offset-l2');
			}
			
			toggleCards(new_val);
		}
		    
		$(this).attr("data-oldval",new_val);
	});
	
	//Product select box
	$("#product").change(handleProductSelectChange);

	function handleProductSelectChange(){
		$(".ad_tags_tag").val("").trigger("paste");
		$("#file_upload_error").hide();
		$("#files").html("");
		$("*").removeClass("invalid valid invalidselect validselect");
		$("#main_files_alert").html(upload_files_text).removeClass("alert-info alert-success").addClass("alert");
		var new_val = $('#product').val();
		var old_val = $('#product').attr("data-oldval");
		var requesttype_current_val = $("#requesttype").attr("data-oldval");
		
		if (new_val !== old_val)
		{
			if (new_val === 'Display')
			{
				$("#requesttypeparent").show();
				$("#advertiserparent").toggleClass('offset-l2 offset-m2');
				//$("#newadvertiserparent .input-field").toggleClass('offset-m6 offset-l6');
				$("#video_url").hide();
				$("#video_url #upload_file_container").appendTo("#upload_own_container");
				
				if (requesttype_current_val === 'Custom Banner Design')
				{
					$("#upload_your_own_card_title").text('Images and Assets');
					$("#main_files_alert").text(upload_files_for_banner_intake_text);
				}
				else
				{
					$("#upload_your_own_card_title").text('Upload Your Own Files');
					$("#main_files_alert").text(upload_files_text);
				}
				
				if (requesttype_current_val === 'Ad Tags')
				{
					$("#landing_page_parent").hide();
					$("#newadvertiserparent").removeClass('offset-m2 offset-l2').addClass('offset-m6 offset-l6');
				}
				else
				{
					$("#landing_page_parent").show();
					$("#newadvertiserparent").removeClass('offset-m6 offset-l6').addClass('offset-m2 offset-l2');
				}
				
				toggleCards(requesttype_current_val,new_val);
			}
			else
			{
				$("#requesttypeparent").hide();
				$("#landing_page_parent").show();
				$("#newadvertiserparent").removeClass('offset-m6 offset-l6').addClass('offset-m2 offset-l2');
				$("#advertiserparent").toggleClass('offset-l2 offset-m2');
				//$("#newadvertiserparent .input-field").toggleClass('offset-m6 offset-l6');
				$("#upload_your_own_card_title").text('Video Files');
				$("#upload_file_container").appendTo("#video_url .upload_files_accordion");
				$("#main_files_alert").text(upload_files_text);
				$("#video_url").show();
				toggleCards("Upload Own",new_val);
			}
		}
		
		$('#product').attr("data-oldval",new_val);
	}
	
	$(".ad_tags_tag").on("input paste",function(){
		var id_val = $(this).attr("id");
		var tag_val = $(this).val();
		var twidth = "";
		var theight = "";
		
		switch (id_val)
		{
			case "tag_320x50":
				twidth="320";
				theight="50";
				break;
			case "tag_728x90":
				twidth="728";
				theight="90";
				break;
			case "tag_160x600":
				twidth="160";
				theight="600";
				break;
			case "tag_336x280":
				twidth="336";
				theight="280";
				break;
			case "tag_300x250":
				twidth="300";
				theight="250";
				break;
			case "tag_custom":
				twidth="800";
				theight="600";
				break;
		}
		
		if (typeof twidth === 'undefined' || typeof theight ==='undefined' || twidth === '' || theight === '')
		{
			return false;
		}else if (tag_val === '')
		{
			tag_val = "<img src='/images/banner_intake/"+twidth+"x"+theight+"_placeholder.png'/>";
			$('#'+id_val+'_preview').html(tag_val);
			return;
		}
		$('#'+id_val+'_preview').html("");
		$('<iframe marginwidth="0px;" marginheight="0px;" width="'+twidth+'px;" height="'+theight+'px;" id="'+id_val+'_iframe" frameborder="0"/>').appendTo('#'+id_val+'_preview');
		var myIFrame = $('#'+id_val+'_iframe');
		myIFrame = (myIFrame[0].contentWindow) ? myIFrame[0].contentWindow : (myIFrame[0].contentDocument.document) ? myIFrame[0].contentDocument.document : myIFrame[0].contentDocument;
		myIFrame.document.open();
		myIFrame.document.write(tag_val);
		myIFrame.document.close();
	});
	
	
	$('#creative_request_owner').select2({ 
		width: '100%' ,
		placeholder: "",
		minimumInputLength: 0,
		multiple: false,
		ajax: {
			url: "/banner_intake/get_users_via_partner_hierarchy/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 20,
					page: page
				};
			},
			results: function (data) {
				return {results: data.result, more: data.more};
			}
		},
		formatResult: function(data) {
			return '<small class="grey-text">'+data.email+'</small>'+
				'<br>'+data.text;
		}
	});
	
	$('#cc_on_ticket').select2({ 
		allowClear: true,
		width: '100%' ,
		placeholder: "",
		minimumInputLength: 0,
		multiple: false,
		ajax: {
			url: "/banner_intake/get_users_via_partner_hierarchy/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 20,
					page: page,
					empty_option_required:true
				};
			},
			results: function (data) {
				return {results: data.result, more: data.more};
			}
		},
		formatResult: function(data) {
			if (data.text === 'Select One')
                        {
                                return data.text;
                        }
			else
			{
				return '<small class="grey-text">'+data.email+'</small>'+
					'<br>'+data.text;
			}
		}
	});

	$('#advertiser').select2({ 
		width: '100%' ,
		placeholder: "",
		minimumInputLength: 0,
		multiple: false,
		ajax: {			
			url: "/mpq_v2/get_select2_advertisers/",
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
		formatResult: function(data) {
                        if (data.text === '*New*')
                        {
			    $("#newadvertiserparent").show();
			    $("#newadvertiserparent input").focus();
			    $("#new_advertiser").val("");
			    $("#source_table").val("");
			    return data.text;
                        }
                        else
                        {
                                var verified_html = '';
                                if (data.source_table === 'Advertisers')
                                {
                                        verified_html = '<br/><i class="material-icons io_icon_done" style="color: #009900;position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i><small class="grey-text">verified advertiser</small>';
                                }
                                var external_id_text = data.externalId ? ' <small class="grey-text">EXTID: '+data.externalId+'</small>' : '';
                                
                                return '<small class="grey-text">'+data.user_name+'&nbsp;&nbsp;['+data.email+']'+'</small><br/>'+data.text+verified_html+external_id_text;
                        }			
		},
                formatSelection: function(data) {
                        if (data.source_table === 'Advertisers')
                        {
				var cc_on_ticket_selection = {'id':data.sales_person+"", 'text':data.user_name};
				$('#cc_on_ticket').select2('data', cc_on_ticket_selection);
                        	var external_id_text = data.externalId ? ' <small class="grey-text">EXTID: '+data.externalId+'</small>' : '';
				return data.text+'&nbsp;<i class="material-icons io_icon_done" style="color: #009900;position:relative;top:10px;left:10px;margin-right:10px;">&#xE8E8;</i>'+external_id_text;
                        }else
                        {
				if (typeof data.reset_cc_on_ticket == "undefined" || data.reset_cc_on_ticket)
				{
					$('#cc_on_ticket').select2('data', {'id':'0','text':'Select One'});
				}
                                return data.text;
                        }			
		}
	});
	
	init_advertiser_dropdown(preload_data);
	
	$("#advertiser").change(function(){
		if ($(this).val() === 'unverified_advertiser_name')
		{
			$("#newadvertiserparent").show();
			$("#newadvertiserparent input").focus();
			$("#new_advertiser").val("");
			$("#source_table").val("");
		}
		else
		{
			$("#newadvertiserparent").hide();
			$("#newadvertiserparent input").removeClass("valid").val("");
			$("#source_table").val(get_advertiser_info().source_table);
			$("#new_advertiser").val(get_advertiser_info().advertiser_name);
		}	    
	});
	
	$("#submit_request").click(function(event){
		event.preventDefault();
		var error_messages_array = Banner_Intake.validate_form();
		if (typeof error_messages_array === 'undefined' || error_messages_array.length === 0)
		{
			$("#banner_intake_form").submit();
		}
	});
	
	var creative_request_owner_selected = <?php echo $creative_request_owner_selected; ?>;
	if(!$.isEmptyObject(creative_request_owner_selected))
	{
		$('#creative_request_owner').select2('data', creative_request_owner_selected);
	}
	
	var cc_on_ticket_selected = <?php echo (isset($cc_on_ticket_selected) ? $cc_on_ticket_selected : "{}"); ?>;
	if(!$.isEmptyObject(cc_on_ticket_selected))
	{
		$('#cc_on_ticket').select2('data', cc_on_ticket_selected);
	}
	else
	{
		$('#cc_on_ticket').select2('data', {'id':'0','text':'Select One'});
	}
	
	$('.tooltipped').tooltip();
});

function toggleCards(request_type, product_type)
{
	var banner_intake = $( "#banner_intake");
	var ad_tags = $( "#ad_tags");
	var story_board = $( "#storyboard");
	var features = $( "#features"); 
	var upload_own = $( "#upload_own");
	
	if (request_type === 'Custom Banner Design')
	{
		$(banner_intake).show( "fade", {}, 100,function(){
			$(ad_tags).hide( "fade", {}, 100,function(){
				$(upload_own).show( "fade", {}, 100, function(){
					$(story_board).show( "fade", {}, 100, function(){
						$(features).show( "fade", {}, 100);
					});
				});
			});
		});

	}
	else if (request_type === 'Ad Tags')
	{   
		$(banner_intake).hide( "fade", {}, 100,function(){
			$(ad_tags).show( "fade", {}, 100,function(){
				$(upload_own).hide( "fade", {}, 100, function(){
					$(story_board).hide( "fade", {}, 100, function(){
						$(features).hide( "fade", {}, 100);
					});
				});
			});
		});
	}
	else if (request_type === 'Upload Own')
	{   
		$(banner_intake).hide( "fade", {}, 100,function(){
			$(ad_tags).hide( "fade", {}, 100,function(){
				$(upload_own).show( "fade", {}, 100, function(){
					$(story_board).hide( "fade", {}, 100, function(){
						$(features).hide( "fade", {}, 100);
					});
				});
			});
		});
	}
}

function get_advertiser_info()
{
	var advertiser_info = {};
	advertiser_info.advertiser_id = $('#advertiser').select2('data').id;
	advertiser_info.source_table = $('#advertiser').select2('data').source_table;
	advertiser_info.advertiser_name = $('#advertiser').select2('data').adv_name;
	return advertiser_info;
}

function init_advertiser_dropdown(preload_data)
{
	if (preload_data && preload_data.io_advertiser_id !== null) {
		advertiser_id = preload_data.io_advertiser_id;
		advertiser_name = preload_data.advertiser_name;
		source_table = preload_data.source_table;
	} else if (preload_data && preload_data.advertiser_name !== null) {
		advertiser_id = 'unverified_advertiser_name';
	}
	
	if (typeof advertiser_id != 'undefined' && advertiser_id != null && advertiser_id != '')
	{
		var display_text = advertiser_name;
                
                if (advertiser_id == 'unverified_advertiser_name')
		{
			display_text = '*New*';
			source_table = '';
			$("#newadvertiserparent").show();
		}
                
                var adv_name_select2_option = {'id':advertiser_id,'text':display_text,'adv_name':advertiser_name,'source_table':source_table,'reset_cc_on_ticket':false};
                $('#advertiser').select2('data', adv_name_select2_option);
		$('#source_table').val(source_table);
		$("input[name='advertiser_name']").val(advertiser_name);
	}
}

function validate_and_create_unverified_advertiser(unverified_advertiser)
{
	var unverified_advertiser_create_response = true;
	$.ajax({
		type: "POST",
		url: '/mpq_v2/validate_and_create_unverified_advertiser',
		async: false,
		dataType: 'json',		
		data:{
			unverified_advertiser:unverified_advertiser
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success)
			{
				var unverified_advertiser_info = data.result;
				advertiser_id = data.result.advertiser_id;
				advertiser_name = data.result.advertiser_name;
				source_table = data.result.source_table;
				unverified_advertiser_info.io_advertiser_id = data.result.advertiser_id;
				init_advertiser_dropdown(unverified_advertiser_info);
			}else if(typeof(data.errors) !== 'undefined' && data.errors !== '')
			{
				unverified_advertiser_create_response = data.errors;
			}
		}
	});
	return unverified_advertiser_create_response;
}

function remove_variation(clicked_button)
{
	//TODO: Add another row to variation list, and move the button down to the newly created row
	g_variation_counter--;
	if(g_variation_counter == 2)
	{
		$(clicked_button).parent().parent().remove();
		$(".tooltipped").tooltip();		
	}	
	else
	{
		$(clicked_button).parent().parent().remove();
		var remove_button_html = '<button class="btn-floating waves-effect waves-light red remove_variation_btn tooltipped" data-position="right" data-delay="50" data-tooltip="Remove this variation" onclick="remove_variation(this)"><i class="icon-minus icon-white"></i></button>';
		$("#variations_details div.row:nth-last-child(2) .remove_variation_div").html(remove_button_html);
		$(".tooltipped").tooltip();
	}					
	$("#num_variations").val(g_variation_counter);

}

function add_variation(clicked_button)
{
	//TODO: Add another row to variation list, and move the button down to the newly created row
	var add_button_row_html = '<div class="row" id="add_variation_row"><div class="input-field col s12 m4 l4 offset-m1 offset-l1"><a class="waves-effect waves-light btn" onclick="add_variation(this)"><i class="material-icons left">add</i>Add Text Variation</a></div></div>';
	$(".remove_variation_btn").parent().html("&nbsp");
	$(clicked_button).parent().parent().remove();
	g_variation_counter++;
	var new_variation_html = '<div class="row"><div class="input-field col s10 m4 l2 offset-m1 offset-l1">  \
									<input type="text" id="variation_'+g_variation_counter+'_name" name="variation_names[]" placeholder="Another city" value="" length="20">  \
								</div>';
	new_variation_html += '<div class="input-field col s10 m4 l4 offset-m1 offset-l1">  \
								<input type="text" id="variation_'+g_variation_counter+'_details" name="variation_details[]" placeholder="Another text variation" value="" length="100">  \
							</div><div class="col s1 remove_variation_div"><button class="btn-floating waves-effect waves-light red remove_variation_btn tooltipped" data-position="right" data-delay="50" data-tooltip="Remove this variation" onclick="remove_variation(this)"><i class="icon-minus icon-white"></i></button></div>';							
	$("#variations_details").append(new_variation_html);
	$("#variations_details").append(add_button_row_html);
	$(".tooltipped").tooltip();	
	$("#variation_"+g_variation_counter+"_name").characterCounter();
	$("#variation_"+g_variation_counter+"_details").characterCounter();
	$("#num_variations").val(g_variation_counter);

}
	
var Banner_Intake = (function($){
	var validate_form = function(){
		var invalid_elements = [];
		
		//Creative Name
		var creative_name = $("#banner_intake_form").find("#creative_name");
		if ($(creative_name).val() === '')
		{
			$(creative_name).removeClass('valid').addClass('invalid');
			invalid_elements.push(creative_name);
		}
		else
		{
			$(creative_name).removeClass('invalid').addClass('valid');
		}
		
		//Advertiser
		var advertiser = $("#banner_intake_form").find("#advertiser");
		if ($(advertiser).val() === '')
		{
			$("#s2id_advertiser a").removeClass('validselect').addClass('invalidselect');
			invalid_elements.push(advertiser);
		}
		else
		{
			$("#s2id_advertiser a").removeClass('invalidselect').addClass('validselect');
		}
		
		//New Advertiser
		var new_advertiser = $("#banner_intake_form").find("#new_advertiser");
		if($(advertiser).val() === 'unverified_advertiser_name' && $(new_advertiser).val() === '')
		{
			$(new_advertiser).removeClass('valid').addClass('invalid');
			invalid_elements.push(new_advertiser);
		}
		else if($(advertiser).val() === 'unverified_advertiser_name')
		{
			var new_advertiser_creation_response = validate_and_create_unverified_advertiser($(new_advertiser).val());
			if (new_advertiser_creation_response === true)
			{				
				$(new_advertiser).removeClass('invalid').addClass('valid');
				$("#newadvertiserparent").hide();
				$("#newadvertiser_error").hide().html("");
			}else {
				$(new_advertiser).removeClass('valid').addClass('invalid');
				invalid_elements.push(new_advertiser);
				$("#newadvertiser_error").html(new_advertiser_creation_response).show();
			}
		}
		
		//Features - Video
		var is_video = $("#banner_intake_form").find("#is_video");
		if($(is_video).is(":checked"))
		{
			//video youtube url
			var features_video_youtube_url = $("#banner_intake_form").find("#features_video_youtube_url");
			if ($(features_video_youtube_url).val() === '')
			{
				$(features_video_youtube_url).removeClass('valid').addClass('invalid');
				invalid_elements.push(features_video_youtube_url);
			}
			else
			{
				$(features_video_youtube_url).removeClass('invalid').addClass('valid');
			}
			    
			//video plays when
			var features_video_youtube_url = $("#banner_intake_form").find("input[name='features_video_video_play']");
			var is_checked = false;
			$(features_video_youtube_url).each(function(){
				if($(this).is(":checked"))
				{
					is_checked = true;
					return;
				}
			});
			if (!is_checked)
			{
				$("#features_video_video_play_span").removeClass('validselect').addClass('invalidselect');
				invalid_elements.push($("#features_video_video_play_span"));
			}
			else
			{
				$("#features_video_video_play_span").removeClass('invalidselect').addClass('validselect');
			}
			
			//mobile clickthrough to
			var features_video_mobile_clickthrough_to = $("#banner_intake_form").find("input[name='features_video_mobile_clickthrough_to']");
			is_checked = false;
			$(features_video_mobile_clickthrough_to).each(function(){
				if($(this).is(":checked"))
				{
					is_checked = true;
					return;
				}
			});
			if (!is_checked)
			{
				$("#features_video_mobile_clickthrough_to_span").removeClass('validselect').addClass('invalidselect');
				invalid_elements.push($("#features_video_mobile_clickthrough_to_span"));
			}
			else
			{
				$("#features_video_mobile_clickthrough_to_span").removeClass('invalidselect').addClass('validselect');
			}
		}
		
		//Features - Map
		var is_map = $("#banner_intake_form").find("#is_map");
		if($(is_map).is(":checked"))
		{
			//Map locations
			var map_locations = $("#banner_intake_form").find("#features_map_locations");
			if($(map_locations).val() === '')
			{
				$(map_locations).removeClass('valid').addClass('invalid');
				invalid_elements.push(map_locations);
			}
			else
			{
				$(map_locations).removeClass('invalid').addClass('valid');
			}
		}
		
		//Features - Social
		var is_social = $("#banner_intake_form").find("#is_social");
		if($(is_social).is(":checked"))
		{
			//Linkedin Subjet
			var li_subject = $("#banner_intake_form").find("#li_subject");
			if($(li_subject).val() === '')
			{
				$(li_subject).removeClass('valid').addClass('invalid');
				invalid_elements.push(li_subject);
			}
			else
			{
				$(li_subject).removeClass('invalid').addClass('valid');
			}
			
			//Linkedin Message
			var li_message = $("#banner_intake_form").find("#li_message");
			if($(li_message).val() === '')
			{
				$(li_message).removeClass('valid').addClass('invalid');
				invalid_elements.push(li_message);
			}
			else
			{
				$(li_message).removeClass('invalid').addClass('valid');
			}
			
			//Email Subject
			var email_subject = $("#banner_intake_form").find("#email_subject");
			if($(email_subject).val() === '')
			{
				$(email_subject).removeClass('valid').addClass('invalid');
				invalid_elements.push(email_subject);
			}
			else
			{
				$(email_subject).removeClass('invalid').addClass('valid');
			}
			
			//Email Message
			var email_message = $("#banner_intake_form").find("#email_message");
			if($(email_message).val() === '')
			{
				$(email_message).removeClass('valid').addClass('invalid');
				invalid_elements.push(email_message);
			}
			else
			{
				$(email_message).removeClass('invalid').addClass('valid');
			}
			
			//Email Message
			var twitter_text = $("#banner_intake_form").find("#twitter_text");
			if($(twitter_text).val() === '')
			{
				$(twitter_text).removeClass('valid').addClass('invalid');
				invalid_elements.push(twitter_text);
			}
			else
			{
				$(twitter_text).removeClass('invalid').addClass('valid');
			}
		}
		

		//Features - Variation
		var has_variations = $("#banner_intake_form").find("#has_variations");
		if($(has_variations).is(":checked"))
		{
			var variation_spec = $("#variation_spec");
			if($(variation_spec).val() == '')
			{
				$("#variation_spec").addClass('invalid');
				invalid_elements.push(variation_spec);
			}
			else
			{
				$("#variation_spec").removeClass('invalid');
				$("#variation_spec").addClass('valid');

			}
			for(v_num = 1; v_num <= g_variation_counter; v_num++)
			{
				var variation_name = $("#variation_"+v_num+"_name");
				var variation_details = $("#variation_"+v_num+"_details");
				if($(variation_name).val() !== '')
				{
					if($(variation_details).val() === '')
					{
						$(variation_details).removeClass('valid').addClass('invalid');
						invalid_elements.push(variation_details);
					}
					else
					{
						$(variation_name).removeClass('invalid').addClass('valid');
						$(variation_details).removeClass('invalid').addClass('valid');
					}
				}
				else
				{
					if($(variation_details).val() !== '')
					{
						$(variation_name).removeClass('valid').addClass('invalid');
						invalid_elements.push(variation_name);
					}
					else
					{
						$(variation_name).removeClass('invalid').addClass('valid');
						$(variation_details).removeClass('invalid').addClass('valid');
					}
				}
			}
		}
	

		var product = $("#banner_intake_form").find("#product");
		var request_type = $("#banner_intake_form").find("#requesttype");
		
		if ($(product).val() === 'Display' && $(request_type).val() === 'Custom Banner Design')
		{
			//Advertiser Website
			var advertiser_website = $("#banner_intake_form").find("#advertiser_website");
			if($(advertiser_website).val() === '')
			{
				$(advertiser_website).removeClass('valid').addClass('invalid');
				invalid_elements.push(advertiser_website);
			}
			else
			{
				$(advertiser_website).removeClass('invalid').addClass('valid');
			}

			//Landing Page
			var landing_page = $("#banner_intake_form").find("#landing_page");
			if($(landing_page).val() === '')
			{
				$(landing_page).removeClass('valid').addClass('invalid');
				invalid_elements.push(landing_page);
			}
			else
			{
				$(landing_page).removeClass('invalid').addClass('valid');
			}
			
			//Ticket Owner
			var creative_request_owner = $("#banner_intake_form").find("#creative_request_owner");
			if ($(creative_request_owner).val() === '')
			{
				$("#s2id_creative_request_owner a").removeClass('validselect').addClass('invalidselect');
				invalid_elements.push(creative_request_owner);
			}
			else
			{
				$("#s2id_creative_request_owner a").removeClass('invalidselect').addClass('validselect');
			}

			//Storyboarding
			var first_story = $("#banner_intake_form").find("#scene_1_input");
			if ($(request_type).val() === 'Custom Banner Design' &&  $(first_story).val() === '')
			{
				$(first_story).removeClass('valid').addClass('invalid');
				invalid_elements.push(first_story);
			}
			else
			{
				$(first_story).removeClass('invalid').addClass('valid');
			}

			//CTA Select
			var cta_select = $("#banner_intake_form").find("#cta_select");
			if($(cta_select).val() === '')
			{
				$(cta_select).prev("input.select-dropdown").removeClass('validselect').addClass('invalidselect');
				invalid_elements.push(cta_select);
			}
			else
			{
				$(cta_select).prev("input.select-dropdown").removeClass('invalidselect').addClass('validselect');
			}
						
			//CTA Other
			var cta_other = $("#banner_intake_form").find("#cta_other");
			if($(cta_select).val() === 'other' && $(cta_other).val() === '')
			{
				$(cta_other).removeClass('valid').addClass('invalid');
				invalid_elements.push(cta_other);
			}
			else
			{
				$(cta_other).removeClass('invalid').addClass('valid');
			}
		}
		else if ($(product).val() === 'Display' && $(request_type).val() === 'Ad Tags')
		{
			var is_tag_set = false;
			$('.ad_tags_tag').each(function(){
				if ($(this).val() !== '')
				{
					$('#ad_tag_header .label-important').remove();
					is_tag_set = true;
					return;
				}
			});
			
			if (!is_tag_set)
			{
				$('#ad_tag_header .label-important').remove();
				$('#ad_tag_header').append('<span class="label-important" style="margin-left:10px;">Please enter ad tag(s)</span>');
				invalid_elements.push($('#ad_tag_header'));
			}
			
		}
		
		if($(product).val() === 'Preroll')
		{
			//Preroll Video URL
			var video_url = $("#banner_intake_form").find("#preroll_video_url");
			var files_uploaded = $("input[name^='creative_files']");
			
			if ((typeof files_uploaded === 'undefined' || files_uploaded.length === 0))
			{
				if (($(product).val() === 'Preroll' && $(video_url).val() === '') || ($(product).val() !== 'Preroll' && g_request_src !== 'testing'))
				{
					if ($(product).val() === 'Preroll')
					{
						$("#file_upload_error span").html("Please complete video files section");
					}
					else
					{
						$("#file_upload_error span").html("Please upload file(s)");
					}
					$("#file_upload_error").show();
					invalid_elements.push($("#file_upload_error"));
				}
				else
				{
					$("#file_upload_error span").html("");
					$("#file_upload_error").hide();
				}
			}
			else
			{
				$("#file_upload_error span").html("");
				$("#file_upload_error").hide();
			}
			
			//Landing Page
			var landing_page = $("#banner_intake_form").find("#landing_page");
			if($(landing_page).val() === '')
			{
				$(landing_page).removeClass('valid').addClass('invalid');
				invalid_elements.push(landing_page);
			}
			else
			{
				$(landing_page).removeClass('invalid').addClass('valid');
			}
		}

		    
		if (invalid_elements.length > 0)
		{
			scrollTo($(invalid_elements[0]).closest('.card'));
		}
		
		return invalid_elements;
	};
	
	//Scrolling to the top of element
	var scrollTo = function(element) {
		$('html, body').animate({
		    scrollTop: $(element).offset().top + 'px'
		}, 'fast');
	};
	
	return {
		validate_form : function(){
			return validate_form();
		}
	}
})(jQuery);


</script>
