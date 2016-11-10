<?php

global $g_mpq_user_role;
global $g_mpq_user_is_logged_in;
global $g_mpq_has_cpm_and_discount;
global $g_mpq_user_can_see_io;


$g_mpq_user_role = $user_role;
$g_mpq_user_is_logged_in = $is_logged_in;
$g_mpq_has_cpm_and_discount = $g_mpq_user_is_logged_in == true && $g_mpq_user_role == 'media_planner';
$g_mpq_user_can_see_io = $g_mpq_user_is_logged_in == true && $g_mpq_user_role == 'media_planner';


function write_common_mpq_css()
{
	?>
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-datetimepicker.min.css" />
	<link href="/libraries/external/fuelux/fuelux-responsive.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="/libraries/external/js/jquery-file-upload-9.5.7/css/jquery.fileupload.css"/>
	<link rel="stylesheet" href="/libraries/external/js/jquery-file-upload-9.5.7/css/jquery.fileupload-ui.css"/>
	<style type="text/css"> <?php // for jquery.placeholder.js ?>
	input, textarea { color: #000; }
	.placeholder,
	textarea.placeholder,
	input[type="text"].placeholder 
	{ color: #aaa; }
	.wizard{position:relative;overflow:hidden;background-color:#f9f9f9;border:1px solid #d4d4d4;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;*zoom:1;-webkit-box-shadow:0 1px 4px rgba(0,0,0,0.065);-moz-box-shadow:0 1px 4px rgba(0,0,0,0.065);box-shadow:0 1px 4px rgba(0,0,0,0.065)}
	.wizard:before,
	.wizard:after{display:table;line-height:0;content:""}
	.wizard:after{clear:both}
	.wizard ul{width:4000px;padding:0;margin:0;list-style:none outside none}
	.wizard ul.previous-disabled li.complete{cursor:default}
	.wizard ul.previous-disabled li.complete:hover{color:#468847;cursor:default;background:#f3f4f5}
	.wizard ul.previous-disabled li.complete:hover .chevron:before{border-left-color:#f3f4f5}
	.wizard ul li{position:relative;float:left;height:46px;padding:0 20px 0 30px;margin:0;font-size:16px;line-height:46px;color:#999;cursor:default;background:#ededed}
	.wizard ul li .chevron{position:absolute;top:0;right:-14px;z-index:1;display:block;border:24px solid transparent;border-right:0;border-left:14px solid #d4d4d4}
	.wizard ul li .chevron:before{position:absolute;top:-24px;right:1px;display:block;border:24px solid transparent;border-right:0;border-left:14px solid #ededed;content:""}
	.wizard ul li.complete{color:#468847;background:#f3f4f5}
	.wizard ul li.complete:hover{cursor:pointer;background:#e7eff8}
	.wizard ul li.complete:hover .chevron:before{border-left:14px solid #e7eff8}
	.wizard ul li.complete .chevron:before{border-left:14px solid #f3f4f5}
	.wizard ul li.active{color:#3a87ad;background:#f1f6fc}
	.wizard ul li.active .chevron:before{border-left:14px solid #f1f6fc}
	.wizard ul li .badge{margin-right:8px}
	.wizard ul li:first-child{padding-left:20px;border-radius:4px 0 0 4px}
	.wizard .actions{position:absolute;right:0;z-index:1000;float:right;padding-right:15px;padding-left:15px;line-height:46px;vertical-align:middle;background-color:#e5e5e5;border-left:1px solid #d4d4d4}
	.wizard .actions a{margin-right:8px;font-size:12px;line-height:45px}
	.wizard .actions .btn-prev i{margin-right:5px}
	.wizard .actions .btn-next i{margin-left:5px}
	.step-content{padding:10px;margin-bottom:10px;border:1px solid #d4d4d4;border-top:0;border-radius:0 0 4px 4px}
	.step-content .step-pane{display:none}
	.step-content .active{display:block}
	.step-content .active .btn-group .active{display:inline-block}
	.step-content input.error, .step-content textarea.error{background-color: #f2dede;border-color: #eed3d7;}
	.step-content div.control-group.alert-error{color: #333!important;}


#add_scene_button {
	color: #666;
	text-decoration: none;
	cursor: pointer;
	-webkit-transition: all 0.1s ease-in-out;
	position: relative;
}
.remove_scene_button {
	position: absolute;
	top:0px;
	left:-27px;
}
#add_scene_button:hover {
	color: #777;
	-webkit-transition: all 0.1s ease-in-out;
}
span.center {
	display: block;
	margin: 5px 0 0 0;
	text-align: center;
}

div.file.row {
	margin-left: 0;
}

.fileinput-button {
	margin-bottom: 15px;
}
.bar {
	line-height: 30px;
}
button.delete_file {
	margin-bottom: 15px;
	vertical-align: 0%;
}
div.step-pane {
	padding: 0px 20px 20px 20px;
}
.step-pane input[class*="span"],
.step-pane textarea[class*="span"]{
	float:none!important;
}

	</style>
	<?php
}

function write_commmon_mpq_javascript()
{
	?>
	<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/vendor/jquery.ui.widget.js"></script>
	<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
	<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.iframe-transport.js"></script>
	<!-- The basic File Upload plugin -->
	<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload.js"></script>
	<!-- The File Upload processing plugin -->
	<script src="/libraries/external/js/jquery-file-upload-9.5.7/js/jquery.fileupload-process.js"></script>

	<script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
	<script src="/libraries/external/json/json2.js"></script>
	<!-- WARNING! This is a modified version of the fuelux library, to prevent it from re-loading bootstrap. Please do not replace. -->
	<script src="/libraries/external/fuelux/fuelux.js" type="text/javascript"></script>

	<script type="text/javascript">
	$(document).ready(function() {
		$('input, textarea').placeholder();
		$('#set_zips').removeClass("placeholder");

		var $wizard = $('#io_wizard').wizard();

		$wizard.on('change', function(e, data){
			if (!validate_wizard($(this).data('wizard').currentStep) && data.direction == "next")
			{
				e.preventDefault();
			}
		});

		$wizard.on('stepclick', function(){
			$('#io_wizard .actions button.btn-next').prop('disabled', false);
		});

		$wizard.on('changed', function(e){
			if ($(this).data('wizard').currentStep == 4)
			{
				$wizard.trigger('finished');
			}
		});

		$wizard.on('finished', function(e){
			// Add request summary
			var fields = process_banner_intake();
			$('#creative_request_summary').html(summarize_creative_request(fields));

			$('#io_wizard .actions button.btn-next').prop('disabled', 'disabled');

			// Flag that the form has been completed (all steps have been viewed)
			$('input[name="is_form_complete"]').val('true');
		});

		$('select[name="handle_creative_request"]').on('change', function(e){
			e.preventDefault();

			var selection = $(this).val();

			if ($("#"+selection).hasClass('active'))
			{
				return false;
			}
			else
			{
				$('.handle_creative.active')
					.removeClass('active')
					.fadeOut(100, function(){
						$('#'+selection)
							.fadeIn(200)
							.addClass('active');
					});
			};

			$('#advertiser_business_name_input, #advertiser_website_url_input').val('');
		});

	    function summarize_creative_request(fields){
	    	var html = "";

	    	// Landing Page
	    	if (fields.landing_page)
	    	{
		    	html += "<tr><td>Landing Page:</td>\n<td>" + fields.landing_page + "</td></tr>";
		    }

	    	// Creative Assets
	    	html += "<tr><td>Creative Assets:</td>\n<td>";

	    	if (fields.creative_files && fields.creative_files.length > 0)
	    	{
	    		for (key in fields.creative_files)
	    		{
	    			var field = fields.creative_files[key];
	    			var name = field.name || field[1];
	    			html += name.substring(7) +"<br/>";
	    		}
	    	}
	    	else
	    	{
	    		html += '<span class="label label"> None specified</span>';
	    	}
	    	html += "</td>\n</tr>";


	    	// Scenes
	    	html += "<tr><td>Storyboard:</td>\n<td>";

	    	if (fields.scenes.length > 0)
	    	{
	    		if (typeof fields.scenes == 'object')
	    		{
		    		for (key in fields.scenes)
		    		{
		    			var scene = fields.scenes[key];
		    			var num = parseInt(key, 10) + 1;
		    			html += '<span class="label" style="font-size:x-small"><small >'+ num +'</small></span> '+ scene +'<br>';
		    		}
		    	}
		    	else if (typeof fields.scenes == 'string')
		    	{
		    		html += '<span class="label" style="font-size:x-small"><small >1</small></span> '+ fields.scenes +'<br>';
		    	}
	    	}
	    	else
	    	{
	    		html += '<span class="label label"> None specified</span>';
	    	}
	    	html += "</td>\n</tr>";

	    	// Video
	    	html += "<tr><td>Video:</td>\n<td>";

	    	if (fields.is_video)
	    	{
	    		html += fields.features_video_youtube_url !== '' ? '<span class="muted" ><i class="icon-youtube-play"></i>  </span><a href="'+ fields.features_video_youtube_url +'" target="_blank">'+ fields.features_video_youtube_url +'</a><br>' : '';
				html += fields.features_video_video_play ? '<span class="muted" style="color:#999999">video play: </span>'+ fields.features_video_video_play.replace('_',' ') +'<br>' : '';
				html += fields.features_video_mobile_clickthrough_to ? '<span class="muted" style="color:#999999">mobile click-through: </span>'+ fields.features_video_mobile_clickthrough_to.replace('_',' ') +'<br>' : '';
	    	}
	    	else
	    	{
	    		html += '<span class="label label"> None specified</span>';
	    	}
	    	
	    	html += "</td>\n</tr>";

	    	// Map
	    	html += "<tr><td>Map:</td>\n<td>";

	    	if (fields.is_map)
	    	{
	    		html += fields.features_map_locations !== '' ? '<span class="muted" style="color:#999999">Map Locations: </span>'+ fields.features_map_locations +'<br>' : '';
	    	}
	    	else
	    	{
	    		html += '<span class="label label"> None specified</span>';
	    	}
	    	
	    	html += "</td>\n</tr>";

	    	// Social
	    	html += "<tr><td>Social:</td>\n<td>";

	    	if (fields.is_social)
	    	{
	    		html += fields.features_social_twitter_text !== '' ? '<span class="muted" style="color:#999999">Twitter Text: </span>'+ fields.features_social_twitter_text +'<br>' : '';
	    		html += fields.features_social_email_subject !== '' ? '<span class="muted" style="color:#999999">Email Subject: </span>'+ fields.features_social_email_subject +'<br>' : '';
	    		html += fields.features_social_email_message !== '' ? '<span class="muted" style="color:#999999">Email Message: </span>'+ fields.features_social_email_message +'<br>' : '';
	    		html += fields.features_social_linkedin_subject !== '' ? '<span class="muted" style="color:#999999">LinkedIn Subject: </span>'+ fields.features_social_linkedin_subject +'<br>' : '';
	    		html += fields.features_social_linkedin_message !== '' ? '<span class="muted" style="color:#999999">LinkedIn Message: </span>'+ fields.features_social_linkedin_message +'<br>' : '';
	    	}
	    	else
	    	{
	    		html += '<span class="label label"> None specified</span>';
	    	}
	    	
	    	html += "</td>\n</tr>";

	    	return html;
	    }

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

		function format_s2(obj){
			var markup = '<small style="font-size:8pt;">' + obj.partner_name + ': ' + obj.username + '</small> &nbsp;<b>' + obj.advertiser_name + ': ' + obj.advertiser_email + '</b> &nbsp;<span class="badge">' + obj.updated + '</span>';
			return markup;
		};

	    $('#creative_request_select').select2({
	    	minimumInputLength: 0,
	    	placeholder: "Start typing to find existing creative requests...",
	    	ajax: {
				url: "/mpq_v2/get_creative_requests/",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 10,
						page: page
					};
				},
				results: function (data) {
					return {results: data.results, more: data.more};
				}
			},
			formatResult: format_s2,
			formatSelection: format_s2,
			allowClear: true
	    });

	    $('#creative_request_select').on('change', function(){
	    	var adset_request_id = $('#creative_request_select').val();

	    	if (adset_request_id == "")
	    	{
	    		$('#existing_adset_review').html('');
	    	}
	    	else
	    	{
	    		$.ajax({
	    			url: '/mpq_v2/get_single_creative_request',
	    			type: 'POST',
	    			dataType: 'json',
	    			data: {'id': adset_request_id},
	    			error: function(jqxhr, responseText, error){
	    				vl_show_jquery_ajax_error(jqxhr, responseText, error);
	    			},
	    			success: function(data, responseText, jqxhr){
	    				if (vl_is_ajax_call_success(data))
	    				{
		    				$('#existing_adset_review').html(summarize_creative_request(data.adset));

		    				if (data.adset.advertiser_name)
		    				{
		    					$('#advertiser_business_name_input').val(data.adset.advertiser_name);
		    				}

		    				if (data.adset.advertiser_website)
		    				{
		    					$('#advertiser_website_url_input').val(data.adset.advertiser_website);
		    				}
		    			}
		    			else
		    			{
		    				vl_show_ajax_response_data_errors(data);
		    			}
	    			}
	    		});
	    	}
	    });
	});
	</script>
	<?php
}

function write_demographic_component_javascript($demographic_elements)
{
	?>
	<script>
	function handle_demographics_change()
	{
	}

	var encoded_demographics_string_separator = "_";

	function encode_demographics_in_string()
	{
		var demographic_ids = new Array(
		<?php
			$demographic_elements['gender_male']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['gender_female']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_under_18']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_18_to_24']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_25_to_34']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_35_to_44']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_45_to_54']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_55_to_64']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['age_over_65']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['income_under_50k']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['income_50k_to_100k']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['income_100k_to_150k']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['income_over_150k']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['education_no_college']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['education_college']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['education_grad_school']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['parent_no_kids']->write_element_id_name();
			echo ','."\n";
			$demographic_elements['parent_has_kids']->write_element_id_name();
		?>
		);

		var encoded_string = "";
		var length = demographic_ids.length;
		var skip_separator_for_last_index = length - 1;
		for(var ii = 0; ii < length; ii++)
		{
			var id = demographic_ids[ii];
			var is_checked = $("#"+id).is(':checked');
			encoded_string += is_checked ? "1" : "0";
			encoded_string += ii == skip_separator_for_last_index ? "" : encoded_demographics_string_separator;
		}

		return encoded_string;
	}

	function encode_legacy_unused_items_for_demographics()
	{
		var encoded_string = "";
		encoded_string += "1_1_1_1_1" + encoded_demographics_string_separator +
			"" + encoded_demographics_string_separator + // campaign focus value
			"All" +	encoded_demographics_string_separator + // category
		 "Force include sites here..."; // extra sites
		
		return encoded_string;
	}

	function encode_backwards_compatible_demographics_string()
	{
		var encoded_string = "";
		encoded_string += encode_demographics_in_string();
		encoded_string += encoded_demographics_string_separator + encode_legacy_unused_items_for_demographics();
		
		return encoded_string;
	}

	</script>
	<?php
}

function write_demographic_component_html($demographic_sections)
{
	?>
	<div class="row-fluid">
		<div class="span12">
			<div>
				<h3>
					Demographic Targeting <small>Please use this section to specify the demographics of the audience you would like to target <a href="#demo" onclick="chardin_demo_targeting();"> <i class="icon-question-sign icon-large"></i></a></small>
					<hr>
				</h3>
			</div>
		<?php
			foreach($demographic_sections as $demographic_section)
			{
				$demographic_section->write_demographic_section();
			}
		?>
		</div>
	</div>
	<?php
}

function write_media_component_javascript()
{
	?>
	<script type="text/javascript">

	var encoded_channel_string_separator = "|";

	function encode_selected_contextual_channels()
	{
		var raw_channel_data = $('#iab_contextual_multiselect').select2('val');
		return JSON.stringify(raw_channel_data);
		/*
		var checked_channels = $("input.channel_checkbox:checked");
		var last_checked_channel_index = checked_channels.length - 1;

		var encoded_channels = "";
		checked_channels.each(function(index) {
			var channel_data_temp = $(this).parent().html();
			var channel_data = $.trim(channel_data_temp);
			if(index == last_checked_channel_index)
			{
				encoded_channels += channel_data;
			}
			else
			{
				encoded_channels += channel_data + "|";
			}
		});

		return encoded_channels;
		*/
	}

	function encode_selected_industries()
	{
		return $('#industry_select').select2('val');
	}
	
	function init_iab_category_data()
	{
		//$('#iab_contextual_multiselect').show();
		$('#iab_contextual_multiselect').select2({
			placeholder: "Select custom contextual channels",
			minimumInputLength: 0,
			multiple: true,
			ajax: {
				url: "/mpq_v2/get_contextual_iab_categories/",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 10,
						page: page
					};
				},
				results: function (data) {
					return {results: data.result, more: data.more};
				}
			},
			allowClear: true
		});
	}

	function init_industry_data()
	{
		$.fn.modal.Constructor.prototype.enforceFocus = function () {};
		$('#industry_select').select2({ width: '100%' ,
			placeholder: "Advertiser Product",
			minimumInputLength: 0,
			multiple: false,
			ajax: {
				url: "/mpq_v2/get_industries/",
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
					return {results: data.results, more: data.more};
				}
			}
		});
	}

	function init_custom_regions_data()
	{
		$('#custom_regions_multiselect').select2({
			placeholder: "Start typing to find regions...",
			minimumInputLength: 2,
			multiple: true,
			ajax: {
				url: "/mpq_v2/get_custom_regions/",
				type: 'POST',
				dataType: 'json',
				data: function (term, page) {
					term = (typeof term === "undefined" || term == "") ? "%" : term;
					return {
						q: term,
						page_limit: 10,
						page: page
					};
				},
				results: function (data) {
					return {results: data.result, more: data.more};
				}
			},
			allowClear: true
		});
	}

	$('#custom_regions_multiselect_load_button').click(function() {
		var record_ids = $('#custom_regions_multiselect').val();
		$.ajax({
			async:true,
			type: "POST",
			url: "/mpq_v2/get_zips_from_selected_regions_and_save",
			data:{custom_region_ids: record_ids},
			dataType: "json",
			success: function(data){
				var list_of_zipcodes = "";
				if(data.error == false)
				{
					$.each(data.response, function(index, value) {
						if(list_of_zipcodes != "")
						{
							list_of_zipcodes += ',';
						}
						try
						{
							list_of_zipcodes += jQuery.parseJSON(value.regions);
						}
						catch(e)
						{
							list_of_zipcodes += value.regions
						}
					});
					$('#set_zips').val(list_of_zipcodes);
					handle_set_zips('custom_regions');
				}
				else
				{
					alert("Server Error: "+data.error_text+" (#0831998)");
				}
			},
			error: function(data) {
				alert("Error 113466: Failed to retrieve desired custom regions");
			}
		});
	});
	
	function toggle_select_all_contextual(element)
	{
		var channel_checkboxes = $('.channel_checkbox');
		channel_checkboxes.prop("checked", element.prop("checked"));
	}
	$('.channel_checkbox').change(function(){
		if(!$(this).is(":checked"))
		{
			$('#select_all_contextual_checkbox').prop("checked", false);
		}
		else
		{
			var checked = true;
			$('.channel_checkbox').each(function(){
				if(!$(this).is(":checked"))
				{
					checked = false;
					return;
				}
			});
			if(checked == true)
			{
				$('#select_all_contextual_checkbox').prop("checked", true);
			}
		}
	});
	function handle_mpq_channel_change()
	{
	}
	</script>
	<?php
}

function write_media_component_css()
{
}

function write_media_component_html($channel_sections)
{
	?>
	<div class="row-fluid">
		<div class="span12">
			<div id="media_targeting_section" class="intro-chardin">
				<div class="row-fluid">
					<h3>
						Media Targeting <small>Please use this section to describe your offering and the interests of your audience <a href="#media" onclick="chardin_media_targeting();"><i class="icon-question-sign icon-large"></i></a></small>
						<hr>
					</h3>
				</div>
				<?php
					write_media_component_select_channels_html($channel_sections);
				?>
			</div>
		</div>
	</div>
	<?php
}

function write_industry($industry_sections)
{
	echo '<div class="row-fluid">'."\n";
	foreach($industry_sections as $industry_section)
	{
		$industry_section->write_select_industry_section();
	}
	echo '</div>'."\n";
}

function write_channels($channel_sections)
{
	 
	echo '<div class="row-fluid">'."\n";
	foreach($channel_sections as $channel_section)
	{
		$channel_section->write_select_channel_section();
	}
	echo '</div>'."\n";
}

function write_media_component_select_industry_html($industry_sections)
{
	write_industry($industry_sections);
}

function write_media_component_select_channels_html($channel_sections)
{
	?>
	<div >
		<h4 class="muted">
			Contextual Channels<!--&nbsp;&nbsp;&nbsp;<input id="select_all_contextual_checkbox" onclick="toggle_select_all_contextual($(this));" style="margin-bottom:4px;" type="checkbox"/> <span style="font-size:14px;"> Select All</span>-->
		</h4>
		
	</div>

	<?php
	write_channels($channel_sections);
}

function write_budget_options_component_javascript(
	$dollar_budget_ranges, 
	$impression_budget_set,
	$has_session_data,
	$budget_options,
	$partner_mpq_can_submit_proposal,
	$partner_mpq_can_see_rate_card
)
{
	global $g_mpq_user_is_logged_in;
	global $g_mpq_has_cpm_and_discount;
	?>
	<script type="text/javascript">

	function load_proposal_budget_options(options, active_set)
	{
		$(".mpq_options_tab").empty();
		
		var num_options = options.length;
		for(var ii = 0; ii < num_options; ii++)
		{
			var option = options[ii];
			append_budget_option(option);
		}

		$(".mpq_options_tab.active").removeClass("active");
		switch(active_set)
		{
			case "impressions":
				$("#impressions_tab").addClass("active");
				break;
			case "dollar":
				$("#dollar_tab").addClass("active");
				break;
			default:
				$("#dollar_tab").addClass("active");
				vl_show_error_html("Unknown active options set: "+active_set);
		}
	}

	function get_proposal_budget_options_data(type)
	{
		var options = new Array();
		$(".mpq_option", $("#"+type+"_budget_options_list")).each(function(index) {
			var amount_value = $(".mpq_option_amount", this).val();
			var term_value = $(".mpq_option_term", this).val();
			var duration_value = $(".mpq_option_duration", this).val();

			<?php if($g_mpq_has_cpm_and_discount): ?>
			var cpm_value = $(".mpq_option_cpm", this).val();
			var discount_value = $(".mpq_option_discount", this).val();
			<?php endif; ?>

			var option = {
				type : type,
				amount : amount_value,
				term : term_value,
				duration : duration_value,
				<?php if($g_mpq_has_cpm_and_discount): ?>
				cpm : cpm_value,
				discount : discount_value,
				<?php endif; ?>
				order : index
			};

			options.push(option);
		});
		
		return options;
	}

	function validate_options_input_fields(options)
	{
		// TODO: implement this - scott (2013-05-29)
		/*
		var are_valid = false;
		var num_options = options.length;
		for(var ii = 0; ii < num_options; ii++)
		{
			var option = options[ii];
		}
		*/
		return true;
	}

	function is_active_option_set_valid()
	{
		var are_options_valid = false;
		var active_set_id = $(".mpq_options_tab.active").attr("id");
		//var active_set = "";
		switch(active_set_id)
		{
			case "dollar_tab":
				are_options_valid = validate_options_input_fields(dollar_options);
				//active_set = "dollars";
				break;
			case "impressions_tab":
				are_options_valid = validate_options_input_fields(impression_options);
				//active_set = "impressions";
				break;
			default:
				vl_show_error_html("Unknown active options budget tab: "+active_set_id);
				are_options_valid = false;
				//acitve_set = "unknown";
		}

		return are_options_valid;
	}

	function get_active_option_set()
	{
		var active_options = false;

		var active_set_id = $(".mpq_options_tab.active").attr("id");
		switch(active_set_id)
		{
			case "dollar_tab":
				active_options = get_proposal_budget_options_data("dollar");
				break;
			case "impressions_tab":
				active_options = get_proposal_budget_options_data("impression");
				break;
			default:
				vl_show_error_html("Unknown active options budget tab: "+active_set_id);
		}

		return active_options;
	}

	/* not used anymore
	function get_all_options()
	{
		var dollar_options = get_proposal_budget_options_data("dollar");
		var impression_options = get_proposal_budget_options_data("impression");
		var all_options = $.merge($.merge([], dollar_options), impression_options);
	}
	*/

	function append_dollar_budget_option(data)
	{
		append_budget_option(data, 'dollar');
	}

	function append_impression_budget_option(data)
	{
		append_budget_option(data, 'impression');
	}

	var mpq_map_term_to_duration = {
		'monthly' : 'months',
		'weekly' : 'weeks',
		'daily' : 'days'
	}

	function handle_option_source_value_change(event)
	{
		var delegate = $(event.delegateTarget);
		var option = delegate.closest("form");
		update_option_refresh(option);
	}

	function update_option_refresh(option_form)
	{
		<?php if($g_mpq_has_cpm_and_discount): ?>
		var option = $(option_form);
		var term = $(".mpq_option_term", option).val();
		var amount = $(".mpq_option_amount", option).val();
		var duration = $(".mpq_option_duration", option).val();
		<?php if($g_mpq_has_cpm_and_discount): ?>
		var cpm = $(".mpq_option_cpm", option).val();
		var discount = $(".mpq_option_discount", option).val();
		<?php endif; ?>
		//var result = $(".mpq_option_result", option).val();
		var type = $(".mpq_option_type", option).val();

		if($.isNumeric(amount))
		{
			$.ajax({
				url: '/mpq/get_option_refresh',
				dataType: 'json',
				success: function(data, textStatus, xhr) 
				{
					if(vl_is_ajax_call_success(data))
					{
						<?php if($partner_mpq_can_see_rate_card){ ?>
							$(".mpq_option_cpm", option).attr("placeholder", data.computed_cpm_placeholder);
						<?php } ?>
						$(".mpq_option_discount", option).attr("placeholder", data.computed_discount_placeholder);
						$(".mpq_option_result", option).html(data.summary);
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'get option refresh data failed');
					}
				},
				error: function(xhr, textStatus, error) 
				{
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				async: true,
				type: "POST", 
				data: {
					type : type,
					amount : amount,
					term : term,
					duration : duration
					<?php if($g_mpq_has_cpm_and_discount): ?>
					,
					cpm : cpm,
					discount : discount
					<?php endif; ?>
				}
			});
		}
		else
		{
			$(".mpq_option_cpm", option).attr("placeholder", '');
			$(".mpq_option_discount", option).attr("placeholder", '');
			$(".mpq_option_result", option).html('');
		}

		<?php endif; ?>
	}

	// term type, example: "monthly"
	function handle_frequency_term_change(event)
	{
		var delegate = event.delegateTarget;
		var term = $(delegate).val();
		var duration = mpq_map_term_to_duration[term];

		if(typeof duration === "undefined")
		{
			vl_show_error_html("Unhandled option term: "+term+" (#5487454)");
		}

		$(".mpq_option_duration_string", $(delegate).parent()).html(" "+duration+" ");
	}

	function handle_enter_key(event)
	{
		var enter_return_key = 13;
		if(event.which == enter_return_key)
		{
			var $delegate = $(event.delegateTarget);

			if (!$delegate.is("textarea") && !$delegate.is(":button,:submit")) 
			{
				var focusNext = false;
				var $option_form = $delegate.closest("form");
				$option_form.find(":input:visible:not([disabled],[readonly]), a").each(function()
				{
					if(this === event.delegateTarget)
					{
						focusNext = true;
					}
					else if(focusNext)
					{
						$(this).focus();
						return false;
					}
				});

				return false;
			}
		}
	}

	function append_budget_option(data, type)
	{
		<?php
		$frequency_values = array(
			'monthly',
			'weekly',
			'daily'
		);

		$frequency_term = '\'<select id="option_frequency_term" class="input-small mpq_option_term intro-chardin">';
		foreach($frequency_values as $value)
		{
			$frequency_term .= '<option value="'.$value.'">'.$value.'</option>';
		}
		$frequency_term .= '</select>\'';

		$dollar_option_amount_per_term = '
			\'<div id="dollar_option_amount_per_term" class="input-prepend intro-chardin">\'+
				\'<span class="add-on">$</span>\'+
				\'<input type="text" class="input-mini mpq_option_amount" placeholder="">\'+
			\'</div>\'
		';

		$impression_option_amount_per_term = '
			\'<div class="input-append">\'+
				\'<input type="text" class="input-small mpq_option_amount" placeholder="">\'+
				\'<span class="add-on">impressions</span>\'+
			\'</div>\'
		';

		$duration_values = array(
			1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12
		);

		$term_duration = '\'<select id="option_term_duration" class="input-mini mpq_option_duration intro-chardin">';
		foreach($duration_values as $value)
		{
			$term_duration .= '<option value="'.$value.'">'.$value.'</option>';
		}
		$term_duration .= '</select>\'';

		$term_cpm = '
			\'<div id="option_gross_cpm" class="input-prepend input-append intro-chardin">\'+
				\'<span class="add-on">$</span>\'+
				\'<input type="text" class="input-mini mpq_option_cpm" placeholder="" value="">\'+
				\'<span class="add-on">CPM</span>\'+
			\'</div>\'
		';

		$option_discount = '
			\'<div id="option_discount_percentage" class="input-append intro-chardin">\'+
				\'<input type="text" class="input-mini mpq_option_discount" placeholder="" value="">\'+
				\'<span class="add-on">%</span>\'+
			\'</div>\'
		';

		$option_result = '\'<span id="option_summary_span" class="muted mpq_option_result intro-chardin"></span>\''; //[100k imprs/month @ $8.00 eCPM]

				//\'<button class="btn  btn-link" type="button" onclick="handle_delete_dollar_budget_option(event); return false;">\'+
		$remove_option_button = '
			\'<div class="pull-right">\'+
				\'<button id="option_remove_button" class="btn  btn-link intro-chardin" type="button">\'+
					\'<span class="label label-important tool_poppers" data-toggle="popover"\'+
					\'data-trigger="hover" data-placement="left" data-content="remove this option"><i class="icon-remove icon-white"></i></span>\'+
				\'</button>\'+
			\'</div>\'
		';

		$option_type = '
			\'<input type="hidden" class="mpq_option_type" />\'
		';
		?>

		var option_amount_html_string = "";
		if(type == 'dollar')
		{
			option_amount_html_string = <?php echo $dollar_option_amount_per_term; ?>;
		}
		else if(type == 'impression')
		{
			option_amount_html_string = <?php echo $impression_option_amount_per_term; ?>;
		}
		else
		{
			alert("unhandled option type: "+type);
			vl_show_error_html("Unhandled option type: "+type);
		}

		var option_amount = $(option_amount_html_string);

		//$("input", option_amount).attr("placeholder", data.option_amount+"");
		$("input", option_amount).
			val(data.option_amount+"").
			change(handle_option_source_value_change).
			keypress(handle_enter_key).
			placeholder();

		var frequency_term = $(<?php echo $frequency_term; ?>); // term type
		frequency_term.val(data.frequency_term+"");
		frequency_term.
			change(handle_frequency_term_change).
			change(handle_option_source_value_change);

		var term_duration = $(<?php echo $term_duration; ?>);
		term_duration.
			val(data.term_duration+"").
			change(handle_option_source_value_change);

		var duration_string = mpq_map_term_to_duration[data.frequency_term];
		var duration_span = $("<span class=\"mpq_option_duration_string\" style=\"display:inline-block;min-width:4em;text-align:center;\"> "+duration_string+" </span>");
		
		<?php if($g_mpq_has_cpm_and_discount): ?>
		var term_cpm = $(<?php echo $term_cpm; ?>);
		<?php if($partner_mpq_can_see_rate_card){ ?>
		$("input", term_cpm).
			val(data.term_cpm+"").
			attr("placeholder", data.placeholder_cpm+"").
			change(handle_option_source_value_change).
			keypress(handle_enter_key).
			placeholder();
		<?php }else{ ?>
		$("input", term_cpm).
			val(data.term_cpm+"").
			change(handle_option_source_value_change).
			keypress(handle_enter_key).
			placeholder();
		<?php } ?>
		var option_discount = $(<?php echo $option_discount; ?>);
		$("input", option_discount).
			val(data.option_discount+"").
			attr("placeholder", data.placeholder_discount+"").
			change(handle_option_source_value_change).
			keypress(handle_enter_key).
			placeholder();

		var option_result = $(<?php echo $option_result; ?>);
		$(option_result).html(data.summary_string);

		<?php endif; ?>


		var option_type = $(<?php echo $option_type; ?>);
		$(option_type).val(data.option_type);

		var remove_option_button = $(<?php echo $remove_option_button; ?>);

		var form = $('<form class="form-inline form-hover mpq_option"></form>');
		form.append(option_amount);
		form.append(" ");
		form.append(frequency_term);
		form.append(" for ");
		form.append(term_duration);
		form.append(" ");
		form.append(duration_span);
		form.append(" ");
		<?php if($g_mpq_has_cpm_and_discount): ?>
		form.append(" @ ");
		form.append(term_cpm);
		form.append(" discounted by ");
		form.append(option_discount);
		form.append(" ");
		form.append(option_result);
		form.append(" ");
		<?php endif; ?>
		form.append(option_type);
		form.append(remove_option_button);

		if(type == 'dollar')
		{
			$("#dollar_budget_options_list").append(form);
			$("button", remove_option_button).click(handle_delete_dollar_budget_option);
		}
		else if(type == 'impression')
		{
			var impression_options = $("#impression_budget_options_list");
			if(impression_options.length > 0)
			{
				impression_options.append(form);
				$("button", remove_option_button).click(handle_delete_impression_budget_option);
			}
			else
			{
				vl_show_error_html("Trying to show unsupported option for user type");
			}
		}
		else
		{
			vl_show_error_html("Trying to show unsupported option type: "+type);
		}
		$("input[type=text]").click( function(){this.select();});
	}

	function handle_dollar_budget_range_selection(event)
	{
		var dollar_budget_ranges = new Array(
		<?php
			$last_budget_range_index = count($dollar_budget_ranges) - 1;
			foreach($dollar_budget_ranges AS $ii=>$budget_range)
			{
				if(!$partner_mpq_can_see_rate_card)
				{
					foreach($budget_range->default_options as $v)
					{
						$v->default_cpm = 10;
						$v->placeholder_cpm = "";
					}
				}
				$budget_range->write_option_rows_javascript_data();
				if($ii != $last_budget_range_index)
				{
					echo ',';
				}
			}
		?>
		);

		var delegate = event.delegateTarget;
		var selection = $(delegate).val();
		var budget_rows_data = dollar_budget_ranges[selection];
		var num_budget_rows_data = budget_rows_data.length;
		$(delegate).val('0');
		$("#dollar_budget_options_list").html("");
		for(var ii=0; ii < num_budget_rows_data; ii++)
		{
			var row_data = budget_rows_data[ii];
			append_dollar_budget_option(row_data);
		}
		
		$("#dollar_budget_range_div").hide();
		$("#dollar_budget_options_div").show();
			$('.label-important.tool_poppers').each(function() {
			$(this).popover();
		});
	}
	
	function handle_budget_range_link_switch(event)
	{
		event.preventDefault();
		$("#dollar_budget_options_div").hide();
		$("#dollar_budget_range_div").show();
	}
	function handle_delete_dollar_budget_option(event)
	{
		var delegate = event.delegateTarget;
		$(delegate).parent().parent().remove();
	}

	function handle_delete_impression_budget_option(event)
	{
		var delegate = event.delegateTarget;
		$(delegate).parent().parent().remove();
	}

	function handle_add_dollar_budget_option(event)
	{
		append_dollar_budget_option({
			option_amount: "", 
			frequency_term: "monthly",
			term_duration: "6",
			term_cpm: "<?php echo ($partner_mpq_can_see_rate_card) ? '': '10'; ?>",
			option_discount: "",
			option_result: "",
			option_type: 'dollar',
			placeholder_cpm: "",
			placeholder_discount: ""
		});
	}

	function handle_add_impression_budget_option(event)
	{
		append_impression_budget_option({
			option_amount: "", 
			frequency_term: "monthly",
			term_duration: "6",
			term_cpm: "<?php echo ($partner_mpq_can_see_rate_card) ? '': '10'; ?>",
			option_discount: "",
			option_result: "",
			option_type: 'impression',
			placeholder_cpm: "",
			placeholder_discount: ""
		});
	}
	
	$(document).ready(function()
	{
		<?php
		if($partner_mpq_can_submit_proposal)
		{ 
			?>
			$("select", $("#dollar_budget_range_div")).change(handle_dollar_budget_range_selection); 
			<?php
			if($g_mpq_user_is_logged_in == true)
			{
				?>
				// initialize impression budget set
				var impression_budget_set = 
				<?php
					if(!$partner_mpq_can_see_rate_card)
					{
						foreach($impression_budget_set->default_options as $v)
						{
							$v->default_cpm = 10;
							$v->placeholder_cpm = "";
						}
					}
					$impression_budget_set->write_option_rows_javascript_data();
					echo ';';
				?>
				var budget_rows_data = impression_budget_set;
				var num_budget_rows_data = budget_rows_data.length;
				for(var ii=0; ii < num_budget_rows_data; ii++)
				{
					var row_data = budget_rows_data[ii];
					append_impression_budget_option(row_data);
				}
				<?php
			}
		}
		?>

	});

	</script>
	<?php
}

/* // TODO: use this when we load mpq data
function future_handle_loaded_options()
{
	//?->
	<script>
	$(document).ready(function()
	{
  	$("select", $("#dollar_budget_range_div")).change(handle_dollar_budget_range_selection); 

		//<-?php
			if($has_session_data)
			{
				// This code is for when loading options from the database (future) -scott (2013_08_06)
				$last_option_index = count($budget_options) - 1;
				echo 'var budget_options = new Array(';
				foreach($budget_options as $index=>$option)
				{
					$option->write_option_row_javascript_data();
					if($index != $last_option_index)
					{
						echo ',';
					}
				}
				echo ');';
				echo "\n";
				//?->
				var does_dollar_option_exist = false;
				var num_budget_options = budget_options.length;
				for(var ii = 0; ii < num_budget_options; ii++)
				{
					var option_data = budget_options[ii];
					if(option_data.option_type == "dollar")
					{
						does_dollar_option_exist = true;
					}
					append_budget_option(option_data, option_data.option_type);
				}

				if(does_dollar_option_exist)
				{
					// hide dollar range default when loading dollar items
					$("#dollar_budget_range_div").hide();
				}
				else if(num_budget_options > 0) // TODO: this should be num_impression_options
				{
					// show impressions tab if no dollars loaded
					//$(".mpq_options_tab.active").removeClass("active");
					//$("#impressions_tab").addClass("active");
					$('#option_tabs a[href="#impressions_tab"]').tab('show');
				}

				//<-?php
			}
			else
			{
				if($g_mpq_user_is_logged_in == true)
				{
			//?->
				// initialize impression budget set
				var impression_budget_set = 
				//<-?php
					$impression_budget_set->write_option_rows_javascript_data();
					echo ';';
				//?->
				var budget_rows_data = impression_budget_set;
				var num_budget_rows_data = budget_rows_data.length;
				for(var ii=0; ii < num_budget_rows_data; ii++)
				{
					var row_data = budget_rows_data[ii];
					append_impression_budget_option(row_data);
				}
			//<-?php
				}
			}
		//?->

	});

	</script>
	//<-?php
}
*/

function write_budget_options_component_css()
{
	   ?>
	<style type="text/css">
	#dollar_budget_options_div{
		display:none;
	}
	#dollar_budget_options_range_link{
		font-size:14px;
		margin-left:10px;
	}
	.budget_refresh_icon{
		margin-top:1px !important;
	}
	form.mpq_option:hover{
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		border-radius: 5px;
		background-color:#eaeaea;
	}
	form.mpq_option, div.form-hover{
		margin-bottom:0px;
		padding-top:10px;
		padding-bottom:10px;
		padding-left:10px;
	}
	.popover-title {display:none;}

	a small {cursor:pointer; !important}
	</style>
	<?php
}

function write_io_form($g_mpq_user_can_see_io){
	?>
			<div id="insertion_order_form" style="display:none;">
				<div class="alert alert-block">
					<h4>Notice</h4>
					This is an insertion order - which is <b>an instruction to buy</b>. If you would rather request a proposal <a  class="link" onclick="toggle_forms();"> click here</a>
				</div>
				<div style="display:none;" class="alert alert-error mpq_error_box" id="insertion_order_form_error">
					<span id="insertion_order_form_error_text">&nbsp;</span>
				</div> 
				<div style="display:none;" class="alert alert-success" id="insertion_order_form_success">
					<button type="button" class="close" data-dismiss="alert">x</button>
					Insertion Order Submitted
				</div>
				<?php if($g_mpq_user_can_see_io){
					?>
					<form class="form-horizontal">
					<div class="control-group">
						<div class="input-append">
							<input class="span2" id="impressions_box" style="width:100px;" type="text">
							<span class="add-on">impressions</span>
						</div> &nbsp;
						<select class="input-small" style="width:200px;" id="period_dropdown" onchange="period_change(this.value)" >
							<option value="MONTH_END">Monthly (Month End)</option>
							<option value="BROADCAST_MONTHLY">Monthly (Broadcast)</option>
							<option value="FIXED_TERM">In Total</option>
						</select>
					</div>
					<div class="control-group">
						<label class="control-label" for="start_date_input">Preferred Start Date</label>
						<div class="controls">
							<div id="datetimepicker3" class="input-append date">
								<input id="start_date_input" data-format="MM/dd/yyyy" data-type="text" type="text" value="<?php echo date('m/d/Y', mktime(0, 0, 0, date('m'), date('d')+3, date('Y'))); ?>"></input>
								<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
							</div>
						</div>
					</div>
					<div id="flight_term_picker" class="control-group">
						<label class="control-label" for="flight_term">Flight Term</label>
						<div class="controls">
							<select id="flight_term" onchange="flight_term_change(this.value)">
								<option value="-1">Ongoing</option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
								<option value="0">Specify end date</option>
							</select>
							<span id="end_date_field"> Preferred End Date 
								<div id="datetimepicker2" class="input-append date">
									<input id="specify_end_date_box" data-format="MM/dd/yyyy" type="text" value="<?php echo date('m/d/Y', mktime(0, 0, 0, date('m')+1, date('d')+3, date('Y'))); ?>"></input>
									<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
								</div>
							</span>
							<span id="literally_just_the_word_months"> months</span>
						</div>
					</div>
					<div id="end_date_picker" class="control-group" style="visibility:visible;">
						<label class="control-label" for="datetimepicker1">Preferred End Date</label>
						<div class="controls">
							<div id="datetimepicker1" class="input-append date">
								<input id="in_total_end_date_box" data-format="MM/dd/yyyy" type="text" value="<?php echo date('m/d/Y', mktime(0, 0, 0, date('m')+1, date('d')+3, date('Y'))); ?>"></input>
								<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
							</div>
						</div>
					</div>
					<div style="display:none;" class="alert alert-error mpq_error_box" id="landing_page_form_error">
						<span id="landing_page_form_error_text">&nbsp;</span>
					</div> 
					<div class="control-group">
						<label class="control-label" for="landing_page_input">Campaign Landing Page</label>
						<div class="controls">
							<input type="text" id="landing_page_input">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="retargeting_input">Includes Retargeting?</label>
						<div class="controls">
							<input type="checkbox" id="retargeting_input" checked="checked">
						</div>
					</div>
				</form>
					<?php
				}
				?>

				<legend class="muted">Creative</legend>

				<div class="control-group">
					<select class="span10" name="handle_creative_request">
						<option value="skip">Skip this</option>
						<option value="attach_existing_request">Select one of your existing creative requests</option>
						<option value="creative_new_request">Create a new adset request</option>
					</select>
			  	</div>

			  	<div id="skip" class="handle_creative active">
			  		<p>You have chosen not to make a creative request with your order.</p>
			  	</div>

			  	<div class="control-group handle_creative" id="attach_existing_request" style="display:none;">
			  		<div style="padding-right:4px;float:none;" class="span10"><input class="span12" type="hidden" id="creative_request_select"/></div>
		  			<table id="existing_adset_review" class="table table-hover" style="margin-top: 20px;">
					</table>
			  	</div>

				<div id="creative_new_request" class="control-group handle_creative" style="display:none">
					<div id="io_wizard" class="wizard">
						<ul class="steps">
							<li data-target="#step1" class="active"><span class="badge badge-info">1</span>Assets<span class="chevron"></span></li>
							<li data-target="#step2"><span class="badge badge-info">2</span>Storyboard<span class="chevron"></span></li>
							<li data-target="#step3"><span class="badge badge-info">3</span>Features<span class="chevron"></span></li>
							<li data-target="#step4" style="display:none;"><span class="badge badge-info">3</span>Finished<span class="chevron"></span></li>
						</ul>
						<div class="actions">
							<button type="button" class="btn btn-mini btn-prev"> <i class="icon-arrow-left"></i>Prev</button>
							<button type="button" class="btn btn-mini btn-next" data-last="Finish">Next<i class="icon-arrow-right"></i></button>
						</div>
					</div>
					<div class="step-content">
						<div class="step-pane active" id="step1">
							<div class="control-group">
								<span class="btn btn-primary fileinput-button">
							        <i class="icon-file icon-white"></i>
							        <span>Add files...</span>
							        <!-- The file input field used as target for the file upload widget -->
							        <input id="file_upload" type="file" name="files[]" multiple />
							    </span>
							    <span style="position:relative;top:-8px;left:10px;">or drag-and-drop files here</span>
							    <br />
							    <!-- The container for the uploaded files -->
							    <div id="files" class="files">
							    </div>
							</div>
						</div>
						<div class="step-pane" id="step2">
							<div class="error alert alert-error" style="display:none;"></div>
							<div class="control-group" id="scene_1_control_group">
								<label class="control-label" for="scene_1_input">Scene 1</label>
								<div class="controls" style="position:relative;">
									<textarea class="input span4" type="text" id="scene_1_input" placeholder="an image of a burger and the words 'america's #1 diner'" required name="scenes[]"></textarea>
								</div>
							</div>
							<div class="control-group" id="add_scene_cg">
								<label class="control-label"></label>
								<div class="controls">
									<a class="btn btn-mini " onclick="add_scene()"><i class="icon-plus" ></i> add scene</a>
								</div>
							</div>
						</div>
						<div class="step-pane" id="step3">
							<div class="error alert alert-error" style="display:none;"></div>
							<div class="controls">
								<label class="checkbox">
									<input type="checkbox" id="is_video" name="is_video"> <b>Video</b>
								</label>
								<div id="video_details" style="display:none">
									<div class="control-group">
										<label class="control-label" for="youtube_url">Youtube URL</label>

										<div class="controls">
											<input class="input-xlarge" type="text" name="features_video_youtube_url" placeholder="http://youtu.be/dQw4w9WgXcQ">
										</div>
										
									</div>
									<div class="control-group" id="features_video_video_play">
										<span class="indent-box">Video plays when: </span>
										<label class="radio indent-box">
											<input type="radio" name="features_video_video_play" id="optionsRadios1" value="hover-to-play_anytime">
											Hover-to-play anytime
										</label>
										<label class="radio indent-box">
											<input type="radio" name="features_video_video_play" id="optionsRadios2" value="hover-to-play_after_animation">
											Hover-to-play after animation
										</label>
										<label class="radio indent-box">
											<input type="radio" name="features_video_video_play" id="optionsRadios3" value="click_after_animation">
											Click to play after animation completes
										</label>
										<label class="radio indent-box">
											<input type="radio" name="features_video_video_play" id="optionsRadios4" value="auto_after_animation">
											Autoplay after animation completes
										</label>
										<label class="radio indent-box">
											<input type="radio" name="features_video_video_play" id="optionsRadios5" value="auto_with_animation">
											Video and animation play simultaneously
										</label>
									</div>
									<div class="control-group" id="features_video_mobile_clickthrough_to">
										<span class="indent-box">Mobile ad unit (320x50) clicks through to: </span>
										<label class="radio indent-box">
											<input type="radio" name="features_video_mobile_clickthrough_to" id="mobile_clickthrough_radio_lp" value="landing_page">
											Landing page
										</label>
										<label class="radio indent-box">
											<input type="radio" name="features_video_mobile_clickthrough_to" id="mobile_clickthrough_radio_vid" value="video">
											Video
										</label>
									</div>
								</div>
							</div>

							<div class="controls">
								<label class="checkbox">
									<input type="checkbox" id="is_map" name="is_map"> <b>Map</b>
								</label>
								<div id="map_details" style="display:none">
								<div class="control-group">
										<label class="control-label" for="map_locations">Map locations (one per line)</label>
										<div class="controls">
											<textarea class="span6" id="map_locations" name="features_map_locations" placeholder="1600 Pennsylvania Ave NW, Washington, DC 20500"></textarea>
										</div>
									</div>
								</div>
							</div>

							<div class="controls">
								<label class="checkbox">
									<input type="checkbox" id="is_social" name="is_social"> <b>Social</b>
								</label>
								<div id="social_details" style="display:none">
									<div class="control-group">
										<label class="control-label" for="twitter_text">Twitter text</label>
										<div class="controls">
											<input class="span6" type="text" id="twitter_text" name="features_social_twitter_text" placeholder="Check it out: Johnny's Diner - 2 for 1 Thursday!">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="email_subject">Email subject title</label>
										<div class="controls">
											<input class="span6" type="text" id="email_subject" name="features_social_email_subject" placeholder="Johnny's Diner - 2 for 1 Thursday! johnnys.com/burger">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="email_message">Email message</label>
										<div class="controls">
											<textarea class="span6" id="email_message" name="features_social_email_message" placeholder="Hi there, Meet you at Johnny's on Thursday? Burgers are delicious and it's 2 for 1!"></textarea>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="li_subject">Linkedin subject title</label>
										<div class="controls">
											<input class="span6" type="text" id="li_subject" name="features_social_linkedin_subject" placeholder="Chek it out: Johnny's Diner - 2 for 1 Thursday!">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="li_message">Linkedin message</label>
										<div class="controls">
											<textarea class="span6" id="li_message" name="features_social_linkedin_message" placeholder="I'll be at Johnny's on Thursday. Burgers are delicious and it's 2 for 1!"></textarea>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="step-pane" id="step4">
							<div class="alert alert-success">Thanks! Your creative request is as follows. Please complete the rest of the form to submit your request.</div>
							<table id="creative_request_summary" class="table table-hover">
								<tr>
									<td>Creative Assets:</td>
									<td><span class="label label-warning">none requested</span></td>
								</tr>
								<tr>
									<td>Storyboard:</td>
									<td><span class="label label-warning">none requested</span></td>
								</tr>
								<tr>
									<td>Video:</td>
									<td><span class="label label-warning">none requested</span></td>
								</tr>
								<tr>
									<td>Map:</td>
									<td><span class="label label-warning">none requested</span></td>
								</tr>
								<tr>
									<td>Social:</td>
									<td><span class="label label-warning">none requested</span></td>
								</tr>
							</table>
							<input type="hidden" name="is_form_complete" value="false"/>
						</div>
					</div>
				</div>	
				
			</div>
	<?php
}

function write_budget_options_dollar_budget_tab(
	$dollar_budget_ranges, 
	$has_session_data
)
{
	// Dollar Budget Range
	// Dollar Budget Options List
	// Dollar Add Buddget Option Button

	?>
	<div class="mpq_options_tab tab-pane active" id="dollar_tab">
		<div id="dollar_budget_range_div">
			<h4 class="muted">
				$ Budget Range <small>pick a budget range and we<?php echo "'";?>ll prefill your options with dollar amounts within the range</small>
			</h4>
			<div style="display:none;" class="alert alert-error mpq_error_box" id="budget_range_selection_error_box">
				<span id="budget_range_selection_error_text">&nbsp;</span>
			</div>
			<select >
			<?php
				foreach($dollar_budget_ranges as $ii=>$budget_range)
				{
					$budget_range->write_range_option_html($ii);
				}
			?>
			</select>
		</div>
		<div id="dollar_budget_options_div">
			<h4 class="muted">
				$ Budget Options <small> Feel free to edit the options below <a href="#dollar_options" onclick="chardin_dollar_options();"><i class="icon-question-sign icon-large"></i></a> or go back<a onclick="handle_budget_range_link_switch(event);" id="dollar_budget_options_range_link" href="">to budget range</a></small>
			</h4>
			<div style="display:none;" class="alert alert-error mpq_error_box" id="dollar_budget_number_error_box">
				<span id="dollar_budget_number_error_text">&nbsp;</span>
			</div>
			<div id="dollar_budget_options_list">
			</div>

			<div class="row form-hover">
				<div class="pull-right">
					<button id="option_add_button" class="btn  btn-link intro-chardin" type="button" onclick="handle_add_dollar_budget_option(event);">
						<span class="label label-success  tool_poppers" data-toggle="popover"
						data-trigger="hover" data-placement="left" data-content="add an option"><i class="icon-plus icon-white"></i></span>
					</button>
				</div>
			</div>
		</div>
		<div style="clear:both;">
		&nbsp;
		</div>
	</div>
	<?php
}

function write_budget_options_impression_budget_tab()
{
	// Impression Budget Options List
	// Impression Add Buddget Option Button

	?>
	<div class="mpq_options_tab tab-pane" id="impressions_tab">
		<div id="impression_budget_options_div">
			<h4 class="muted">
				Impression Options
			</h4>
			<div style="display:none;" class="alert alert-error mpq_error_box" id="impressions_budget_number_error_box">
				<span id="impressions_budget_number_error_text">&nbsp;</span>
			</div>
			<div id="impression_budget_options_list">
			</div>

			<div class="row form-hover">
				<div class="pull-right">
					<button class="btn  btn-link" type="button" onclick="handle_add_impression_budget_option(event);">
						<span class="label label-success  tool_poppers" data-toggle="popover"
						data-trigger="hover" data-placement="left" data-content="add an option"><i class="icon-plus icon-white"></i></span>
					</button>
				</div>
			</div>
		</div>
		<div style="clear:both;">
		&nbsp;
		</div>
	</div>
	<?php
}


function write_budget_options_component_html(
	$dollar_budget_defaults,
	$has_session_data,
	$partner_mpq_can_submit_proposal
)
{
	global $g_mpq_user_is_logged_in;
	global $g_mpq_has_cpm_and_discount;
	global $g_mpq_user_can_see_io;





	?>
	<div class="row-fluid" name="options">
		<div class="span12">
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<div>
					<h3 id="options_header">
						<?php
							if($g_mpq_user_can_see_io){
								echo '<span id="header_title">Insertion Order <small>Please use this section to specify the final campaign parameters</small></span> ';
								if($partner_mpq_can_submit_proposal){
									echo '<div id="insertion_order_button" class="intro-chardin" style="float:right;"><a id="form_toggle_button" class="link" onclick="toggle_forms();" > <small><i class="icon-edit"></i> Toggle to  proposal</a></small> <small><a href="#toggle_request_type" onclick="chardin_insertion_order_button();"><i class="icon-question-sign icon-large"></i></a></small>
								</div>';
								}
								
							}else if($partner_mpq_can_submit_proposal){
								echo '<span id="header_title">Proposal Options <small>Please use this section to specify the different options that will be included in the proposal</small></span> ';
							}
							
						?>
					<hr>
				</h3>
			</div>


			<?php

			write_io_form($g_mpq_user_can_see_io);

			?>
			
			<div>
				<div style="display:none;" class="alert alert-error mpq_error_box" id="options_error_box">
					<span id="options_error_text">&nbsp;</span>
				</div>
				<div class="tabbable" id="options_form" style="display:none;">
					<ul id="option_tabs" class="nav nav-pills">
					<?php
					if($partner_mpq_can_submit_proposal) 
					{
						if($g_mpq_user_is_logged_in){
							echo '
							<li class="active intro-chardin" id ="dollar_budget_pill">
								<a href="#dollar_tab" data-toggle="tab">Dollar Budget</a>
							</li>
							<li id ="impression_budget_pill" class="intro-chardin">
								<a href="#impressions_tab" data-toggle="tab">Impression Budget</a>
							</li>
						';
						}
						
					}
					?>
					 <!-- <a href="#budget" onclick="chardin_budget_tabs();"><i class="icon-question-sign icon-large"></i></a> -->
				</ul>
				<div class="tab-content">
				<?php
					  if($partner_mpq_can_submit_proposal)
					  {
						  write_budget_options_dollar_budget_tab($dollar_budget_defaults, $has_session_data);
						  if($g_mpq_user_is_logged_in == true)
						  {
							  write_budget_options_impression_budget_tab();
						  }
					  }
				?>
				</div>
			</div>
		</div>
	</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
		</div>
	</div>

	<?php
}

function write_insertion_order_component_javascript()
{
	global $g_mpq_user_can_see_io;

	?>
	<script>

	var order_form = <?php echo ($g_mpq_user_can_see_io ? 'false' : 'true'); //spoof to the opposite of what we want to toggle into what we want ?>;
	$( document ).ready(function() {
		toggle_forms();
	});
	
	
	function period_change(dropdown_value)
	{
		if(dropdown_value == "FIXED_TERM")
		{
			$('#flight_term_picker').hide();
			$('#end_date_picker').show();
		}
		else
		{
			$('#flight_term_picker').show();
			$('#end_date_picker').hide();
		}
	}
	
	function flight_term_change(dropdown_value)
	{
		if(dropdown_value == 0)
		{
			$('#end_date_field').show();
			$('#literally_just_the_word_months').hide();
		}
		else
		{
			$('#end_date_field').hide(); 
			if(dropdown_value != -1)
			{
				$('#literally_just_the_word_months').show();
			}
			else
			{
				$('#literally_just_the_word_months').hide();
			}
		}
	}

	</script>
	<?php
}

function write_notes_component_html()
{
	?>
	<div style="padding-bottom:50px;" class="row-fluid">
	  <div class="span12">
		<h3>Notes <small>Use this section to add any custom instructions for your request</small></h3><hr>
		<div style="padding: 0 48px 0 24px;box-sizing:border-box;-moz-box-sizing:border-box;">
			<textarea id="submission_notes_textarea" style="height:60px;width:100%;border-box;-moz-box-sizing:border-box;" placeholder="type anything here..." ></textarea>
	  	</div>
	  </div>
	</div>
	<?php
}

function write_submission_component_html()
{
	?>
	<div class="row-fluid">
		<div class="span12 well well-large">
			<a style="display:none;" id="proposal_submit_button" role="button" class="btn btn-large btn-block btn-primary" data-toggle="modal">
			  <i class="icon-thumbs-up icon-white"></i> Build me a proposal!</a>
			<a id="insertion_order_submit_button" role="button" class="btn btn-large btn-block btn-primary" data-toggle="modal">
			  <i class="icon-thumbs-up icon-white"></i> Kick off campaign!</a>
		</div>
	</div>
	<?php
}

function write_misc_extras_css()
{
	?>
	
	<style type="text/css">
		#loading_image {
			position:absolute;
			top:50%;
			left:50%;
			margin-top:-60px;
			margin-left:-60px;
			display:none;
		}
		a [class^="icon-"]{
			text-decoration:none;
		}
		.mpq_error_box{
			margin-top:5px;
			margin-bottom:10px;
			
		}
		.requester_error_box{
			max-width:447px;
		}
	</style>
	<?php
}

function write_misc_extras_javascript()
{
	?>
	<link href="/libraries/external/chardin/chardinjs.css" rel="stylesheet">
	<script src="/libraries/external/chardin/chardinjs.min.js"></script>
	<script>
		

	function chardin_geo_tabs(){
		$('#custom_regions_pill').attr('data-intro', "If you know the name of your regions (city, county or metro area) use this feature");
		$('#custom_regions_pill').attr('data-position', "left");
		$('#custom_regions_pill').css('background-color', 'white');
		$('#radius_search_pill').attr('data-intro', "Use the \"Radius Search\" feature to target all the zip codes within a certain distance from your location...");
		$('#radius_search_pill').attr('data-position', "bottom");
		$('#radius_search_pill').css('background-color', 'white');
		$('#known_zips_pill').attr('data-intro', "...or, if you know your target zip codes you can specify them after clicking the \"Known Zips\" tab");
		$('#known_zips_pill').attr('data-position', "right");
		$('#known_zips_pill').css('background-color', 'white');
		$('body').chardinJs('start');
		make_overlay_ok();
	}

	function chardin_geo_search(){
		$('#radius_span').attr('data-intro', "Specify the number of miles around your location that you\'d like to target here");
		$('#radius_span').attr('data-position', "bottom");
		$('#address').attr('data-intro', "Specify your location here. You can use an address, zip code, city or even a landmark.");
		$('#address').attr('data-position', "right");
		$('body').chardinJs('start');
		make_overlay_ok();
	}

	function chardin_demo_targeting(){
		$('#hhi_checkboxes').attr('data-intro', "The Household Income demographic refers to the total annual income per household");
		$('#hhi_checkboxes').attr('data-position', "left");
		$('#hhi_checkboxes').css('background-color', 'white');
		$('#ed_checkboxes').attr('data-intro', "The Education demographic refers to the highest level of education acheived by the target audience. For example, \"College\" would include audiences with at least a college degree.");
		$('#ed_checkboxes').attr('data-position', "right");
		$('#ed_checkboxes').css('background-color', 'white');
		$('body').chardinJs('start');
		make_overlay_ok();
	}

	function chardin_media_targeting(){

		$('#media_targeting_section').attr('data-intro', "<div style=\"width:500px\">Most sites on the internet have content that can be categorized according to these channels. You should select contextual channels that: <br>1) Match the advertiser's offering and <br>2) Match the advertiser's audience's interests. <br>For example, a car dealership who tends to sell their cars to sports enthusiasts should target both the \"Automotive\" and \"Sports\" contextual channels.</div>");

		$('#media_targeting_section').attr('data-position', "bottom");
		$('#media_targeting_section').css('background-color', 'white');
		$('body').chardinJs('start');
		make_overlay_ok();
	}

	
	function chardin_budget_tabs(){
		$('#dollar_budget_pill').attr('data-intro', "Use the \"Dollar Budget\" feature to pick a dollar range that you'd like to see in the proposal...");
		$('#dollar_budget_pill').attr('data-position', "top");
		$('#dollar_budget_pill').css('background-color', 'white');
		$('#impression_budget_pill').attr('data-intro', "...or, if you know the impression levels for your proposal options click on \"Impression Budget\".");
		$('#impression_budget_pill').attr('data-position', "right");
		$('#impression_budget_pill').css('background-color', 'white');
		$('body').chardinJs('start');
		make_overlay_ok();
	}

	function chardin_dollar_options(){
		$('#dollar_option_amount_per_term').attr('data-intro', "This is the gross dollar budget per period that will be included for this particular option");
		$('#dollar_option_amount_per_term').attr('data-position', "top");
		$('#option_frequency_term').attr('data-intro', "Set the period here");
		$('#option_frequency_term').attr('data-position', "bottom");
		$('#option_term_duration').attr('data-intro', "Set how many periods into the future you would like the proposal to estimate performance and cost");
		$('#option_term_duration').attr('data-position', "top");
		$('#option_gross_cpm').attr('data-intro', "This is the gross CPM* before discount that will be on the proposal for this option. <br><br>*CPM is cost per 1,000 impressions.");
		$('#option_gross_cpm').attr('data-position', "bottom");
		$('#option_discount_percentage').attr('data-intro', "If you would like the proposal to show a discounted rate, you can set that here. <br><br> Be careful to not set too high a discount.");
		$('#option_discount_percentage').attr('data-position', "top");
		$('#option_summary_span').attr('data-intro', "Here is the summary of the option after discount");
		$('#option_summary_span').attr('data-position', "bottom");
		$('#option_summary_span').css('background-color', 'white');
		$('#option_remove_button').attr('data-intro', "Click here to remove this option");
		$('#option_remove_button').attr('data-position', "top");
		$('#option_add_button').attr('data-intro', "Click here to add an option");
		$('#option_add_button').attr('data-position', "left");
		$('body').chardinJs('start');
		make_overlay_ok();
	}

	function chardin_insertion_order_button(){
		
		$('#insertion_order_button').attr('data-intro', "You can switch between making this form a request for a \"Proposal\" or an \"Insertion Order\". <br><br>A proposal is an an advertiser-facing document that describes the planned campaign while an Insertion Order is an instruction to launch a campaign.");
		$('#insertion_order_button').attr('data-position', "bottom");
		$('body').chardinJs('start');
		make_overlay_ok();
	}


	function make_overlay_ok(){
		$(".chardinjs-overlay").css("filter","alpha(opacity=80)");///this is for IE8
		$(".chardinjs-overlay").on("click",  function(event){
			clear_all_chardin();
		});
	}

	function clear_all_chardin(){
		$('.intro-chardin').each(function(i, obj) {
			$(this).removeAttr('data-intro');
			$(this).removeAttr('data-position');
			$(this).css('background-color', '');
		});
	}

	$("#custom_regions_load_button").on("click",  function(event){
		var the_friendly_subregions = '';
		$('#custom_regions > option:selected').each(function() {
			the_friendly_subregions += $(this).data('polygons')+",";
		});
		$("#set_zips").val(the_friendly_subregions);
		$("#set_zips").html(the_friendly_subregions);
		handle_set_zips(true);
		$('#known_zips_tab_anchor').tab('show');
	});

	$("#custom_regions_abandon_changes").on("click",  function(event){
		$('#known_zips_tab_anchor').tab('show');
	});

	var g_scene_counter = 1;

	function add_scene()
	{
		g_scene_counter++;
		var scene_string = '<div class="control-group" id="scene_'+g_scene_counter+'_control_group"> \
									<label class="control-label" for="scene_'+g_scene_counter+'_input">Scene '+g_scene_counter+'</label> \
									<div class="controls" style="position:relative;"> \
										<textarea class=\"span4\" id="scene_'+g_scene_counter+'_input" placeholder="the story continues..." name="scenes[]"></textarea>  \
										<a id="remove_scene_'+g_scene_counter+'_button" onclick="delete_scene()" class="btn btn-danger btn-mini remove_scene_button"><span class="icon icon-remove"></span></a> \
									</div> \
								</div>';
								
		$( "#remove_scene_"+(g_scene_counter-1)+"_button" ).remove();
		$( "#add_scene_cg" ).before( scene_string );
		$('#scene_'+g_scene_counter+'_input').placeholder();
	}

	function delete_scene()
	{
		$('#scene_'+g_scene_counter+'_control_group').hide(200,function(){
				$('#scene_'+g_scene_counter+'_control_group').remove(); 
				g_scene_counter--;
				if(g_scene_counter>1)
				{
					var the_delete_button_string = ' <a id="remove_scene_'+g_scene_counter+'_button" onclick="delete_scene()" class="btn btn-danger btn-mini remove_scene_button"><span class="icon icon-remove"></span></a>';
					$( '#scene_'+g_scene_counter+'_input' ).after(the_delete_button_string);
				}
				
			});
	}

	(function($){

		var g_file_counter = 0;

		/* File upload */
		 var url = '/banner_intake/creative_files_upload';
	    $('#file_upload').fileupload({
	        url: url,
	        type: 'POST',
			dataType: 'json',
	        add: function(e, data) {
	        	data.el = $('<div class="file row" data-file="'+ data.files[0].name +'"/>');
	        	$('#files').append(data.el);
	        	$(data.el).html('<label class="control-label" style="font-size:0.85em;">' + data.files[0].name.substr(0,20) + '...</label><div class="progress progress-success progress-striped active span6"><div class="bar"></div></div>');
	        	data.submit();

	        	$('#main_files_alert')
	        		.removeClass('alert-success')
	        		.addClass('alert-info')
	        		.html('<b>Heads Up!</b> Your files are currently in the process of uploading. Please do not refresh or close your browser window until the uploads are complete.');
	        },
	        progress: function (e, data) {
	            var progress = parseInt(data.loaded / data.total * 100, 10);
	            $(data.el).find('.progress .bar').css(
	                'width',
	                progress + '%'
	            )
	            .text('Uploading ' + progress + '% of ' + Math.floor(data.total / 1024) + 'kb...');
	            if (progress == 100) {
	            	$(data.el).find('.progress .bar').text('Processing ' + data.files[0].name + '...');
	            }
	        },
	        fail: function(e, data) {
	        	$(data.el).html('<div class="alert alert-danger">'+ data.files[0].name +' failed to upload!</div>');
	        },
	        done: function(e, data) {
				var result = data.result;
				g_file_counter++;
				var html = '<div style="margin-left: 20px;" class="uploaded_file"><b>'+ result.data.Key.substr(7) +'</b> <button class="btn btn-danger btn-mini delete_file" data-bucket="'+ result.data.Bucket +'" data-name="'+ result.data.Key +'"><span class="icon icon-remove"></span></button></div>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +']" value="'+ result.response.ObjectURL +'"/>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +']" value="'+ result.data.Key +'"/>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +']" value="'+ result.data.Bucket +'"/>';
				html += '<input type="hidden" name="creative_files['+ g_file_counter +']" value="'+ result.data.ContentType +'"/>';
				$(data.el).html(html);

	        	$('#main_files_alert')
	        		.removeClass('alert-info')
	        		.addClass('alert-success')
	    			.html('<b>Your files have finished uploading!</b> You still need to hit \'Submit\' at the bottom of this page to save your files.');
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

	})(jQuery);

	</script>
	<?php
}

function write_misc_extras_html()
{
}

function write_submit_modal_box_html($user_profile_data, $requester, $industry_sections)
{

	$industry_sections=$industry_sections;
	///do some logic here to determin the actual strings to pre-fill into the form
	//m@!! look out for public users perhaps
	$user_full_name = (isset($user_profile_data['user_full_name']))? ($user_profile_data['user_full_name']) : "";
	$user_email = isset($user_profile_data['user_email'])? $user_profile_data['user_email'] : '';
	$partner_homepage = isset($user_profile_data['partner_home_page'])? $user_profile_data['partner_home_page'] : '';
	$advertiser_name = isset($user_profile_data['advertiser_name'])? $user_profile_data['advertiser_name'] : '';
	$recent_campaign_landing_page = isset($user_profile_data['recent_landing_page'])? $user_profile_data['recent_landing_page'] : '';
	$partner_phone_number = isset($user_profile_data['partner_phone'])? $user_profile_data['partner_phone'] : '';
	$requester_email = isset($user_profile_data['user_email'])? $user_profile_data['user_email'] : $requester['email'];
	$requester_id = isset($user_profile_data['user_id'])? $user_profile_data['user_id'] : $requester['id'];

	////handle hardcoding user type 
	//default is dropdown with media planner selected
	

    if(isset($user_profile_data['user_role'])&&($user_profile_data['user_role'] == 'BUSINESS')){
    	$usertype_options = '<option value="advertiser">
								I\'m the advertiser
							</option>';
		$contact_number = '';
    }elseif(isset($user_profile_data['user_role'])&&($user_profile_data['user_role'] == 'SALES')){
    	$usertype_options = '<option value="agency">
								I\'m an agency media planner
							</option>';
		$contact_number = $partner_phone_number;
    }else{//if public or ops or admin or creative
    	$usertype_options = '<option value="agency">
								I\'m an agency media planner
							</option>
							<option value="advertiser">
								I\'m the advertiser
							</option>';
		$contact_number = '';
    }



	?>
<?php
global $g_mpq_user_is_logged_in;
	if(!$g_mpq_user_is_logged_in){
		echo '<div id="welcome_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
    		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    			×
    		</button>
    		<h3 id="myModalLabel">
    			RFP
    			<small>
    				How it works
    			</small>
    		</h3>
    	</div>
    	<div class="modal-body">
    		<div class="row-fluid">
    			<div class="span4">
    				<p class="text-center">1. <i class="icon-bullseye icon-4x"></i></p>
    				<h4>Select your targets</h4>
    			</div>
    			<div class="span4">
    				2. <i class="icon-envelope icon-4x"></i>
    				<h4>Submit the form</h4>
    			</div>
    			<div class="span4">
    				3. <i class="icon-file-text-alt icon-4x"></i>
    				<h4>Wow!</h4>
    			</div>
    		</div>
    		<div class="row-fluid">
    			<div class="span12">
    			</div>
    		</div>
    		<div class="row-fluid">
    			<div class="span12">
    				The result will be a polished client-facing proposal that includes:
    				<ul class="icons-ul">
    					<li><i class="icon-li icon-ok"></i> Geographic Targeting Summary</li>
    					<li><i class="icon-li icon-ok"></i> Demographic Targeting Summary</li>
    					<li><i class="icon-li icon-ok"></i> Media Targeting Summary</li>
    					<li><i class="icon-li icon-ok"></i> Performance Estimates</li>
    				</ul>
    			</div>
    		</div>
    	</div>
    	<div class="modal-footer">
    		<button  data-dismiss="modal" class="btn btn-primary">
    			Let\'s get started!
    		</button>
    	</div>
    </div>';
	}
	?>


	<div id="submit_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    	<div class="modal-header">
    		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    			×
    		</button>
    		<h3 id="myModalLabel">
    			Request
    			<small>
    				Almost done!
    			</small>
    		</h3>
    	</div>
    	<div class="modal-body">
    		<div class="row-fluid">
    			<div class="span12">
    				<legend>
    					Advertiser Info
    				</legend>
    				<div style="height:30px;" class="controls">
    				  <div class="input-prepend span11">
    					<span class="add-on"><i class="icon-home"></i></span>
    					<input id="advertiser_business_name_input" class="input-block-level" type="text" placeholder="Advertiser Business Name" value="<?php echo $advertiser_name;?>">
    				  </div>
    				</div>
					<div style="display:none;" class="alert alert-error mpq_error_box requester_error_box" id="advertiser_business_name_error">
					  
					  <span id="advertiser_business_name_error_text">&nbsp;</span>
					</div> 
					
    				<div style="height:30px;" class="controls">
    				  <div class="input-prepend span11">
    					<span class="add-on"><i class="icon-globe"></i></span>
    					<input id="advertiser_website_url_input" class="input-block-level" type="text" placeholder="www.advertiser.website.com" value="<?php echo $recent_campaign_landing_page;?>">
    				  </div>
    				</div>
					<div style="display:none;" class="alert alert-error mpq_error_box requester_error_box" id="advertiser_website_url_error">
					  
					  <span id="advertiser_website_url_error_text">&nbsp;</span>
					</div> 
					
					<!-- Industry for MPQ Modal -->
					
					<div id="industry_id_div">
					<?php
					if($industry_sections != null)
					{
						write_media_component_select_industry_html($industry_sections);
					}
					?>
					</div>
					<div style="display:none;" class="alert alert-error mpq_error_box" id="media_targeting_form_error">
						<span id="media_targeting_form_error_text">&nbsp;</span>
					</div>
    			</div>
    		</div>
    		<div class="row-fluid">
    			<div class="span12">
    				<legend>
    					Your Info
    				</legend>
    				<div style="height:30px;" class="controls">
    					<div class="input-prepend span11">
    						<span class="add-on"><i class="icon-user"></i></span>
    						<input id="requester_name_input" class="input-block-level" type="text" placeholder="Firstname Lastname" value="<?php echo $user_full_name;?>"/>
    					</div>
    				</div>
					<div style="display:none;" class="alert alert-error mpq_error_box requester_error_box" id="requester_name_error">
					  
					  <span id="requester_name_error_text">&nbsp;</span>
					</div>
					
    				<select class="span12" id="role_dropdown">
    					<?php echo $usertype_options;?>
    				</select>
    				<div style="height:30px;" class="controls" >
    					<div id="agency_website_control" class="input-prepend span11">
    						<span class="add-on"><i class="icon-globe"></i></span>
    						<input id="agency_website_input" class="input-block-level" type="text" placeholder="www.agency.website.com" value="<?php echo $partner_homepage;?>">
    					</div>
    				</div>
					<div style="display:none;" class="alert alert-error mpq_error_box requester_error_box" id="agency_website_error">
					  
					  <span id="agency_website_error_text">&nbsp;</span>
					</div> 
					
    				<div style="height:30px;" class="controls">
    					<div class="input-prepend span11">
    						<span class="add-on"><i class="icon-envelope"></i></span>
    						<input id="requester_email_address_input" class="input-block-level" type="text" placeholder="your@email.here" value="<?php echo $user_email;?>">
    					</div>
					</div>
					<div style="display:none;" class="alert alert-error mpq_error_box requester_error_box" id="requester_email_address_error">
					  
					  <span id="requester_email_address_error_text">&nbsp;</span>
					</div>
					<div>
    				<div style="height:30px;" class="controls">
    					<div class="input-prepend span11">
    						<span class="add-on"><i class="icon-phone"></i></span>
    						<input id="requester_phone_number_input" class="input-block-level" type="text" placeholder="(YOUR)-PHONE-NUMBER" value="<?php echo $contact_number;?>">
    					</div>
    				</div>
					<div style="display:none;" class="alert alert-error mpq_error_box requester_error_box" id="requester_phone_number_error">
					  
					  <span id="requester_phone_number_error_text">&nbsp;</span>
					</div></div> 

					<input type="hidden" name="requester_email" value="<?php echo $requester_email; ?>"/>
					<input type="hidden" name="requester_id" value="<?php echo $requester_id; ?>"/>
    			</div>
    		</div>
    	</div>
    	<div class="modal-footer">
    		<button id="submit_proposal_request_button" class="btn btn-primary">Submit</button>
    	</div>
    </div>
	<?php
}

function write_submit_modal_box_javascript()
{
	?>
	<script>
						
		
	function handle_role_dropdown_value(){
		if($("#role_dropdown").val()==="agency"){
			$("#agency_website_control").parent().show();
		}else{
			$("#agency_website_control").parent().hide();
		}
	}

	function get_advertiser_info()
	{
		var advertiser_info = new Object();
		advertiser_info.business_name = $("#advertiser_business_name_input").val();
		advertiser_info.website_url = $("#advertiser_website_url_input").val();
		return advertiser_info;
	}

	function get_requester_info()
	{
		var requester_info = new Object();
		requester_info.name = $("#requester_name_input").val();
		requester_info.role = $("#role_dropdown").val();
		requester_info.website = $("#agency_website_input").val();
		requester_info.email_address = $("#requester_email_address_input").val();
		requester_info.phone_number = $("#requester_phone_number_input").val();
		requester_info.notes = $("#submission_notes_textarea").val();
		return requester_info;
	}
	
	function get_active_insertion_order_set()
	{
		var io_array = new Object();
		io_array.num_impressions = $('#impressions_box').val();
		io_array.start_date = $('#start_date_input').val();
		io_array.term_duration = $('#flight_term').val();
		io_array.landing_page = $('#landing_page_input').val();
		io_array.is_retargeting = $('#retargeting_input').prop('checked');
		io_array.term_type = $('#period_dropdown').val();
		if(io_array.term_type == 'FIXED_TERM')
		{
			io_array.end_date = $('#in_total_end_date_box').val();
			io_array.term_duration = "0";
		}
		else if(io_array.term_duration == '0')
		{
			io_array.end_date = $('#specify_end_date_box').val();
		}
		else
		{
			io_array.end_date = "false";
		}
	    return io_array;
	}
	process_banner_intake = function()
	{
	   var temp_object = {};
	   var temp_array = $('#creative_new_request').find('input, textarea').serializeArray();
	   $.each(temp_array, function() {
			if (temp_object[this.name]) {
			   if (!temp_object[this.name].push) {
			       temp_object[this.name] = [temp_object[this.name]];
			   }
			   temp_object[this.name].push(this.value || '');
			} else {
			   temp_object[this.name] = this.value || '';
			}
	   });
	   // make property names safe for codeigniter
	   	var re = new RegExp(/\[.+\]/);
	   	var re2 = new RegExp(/\[\]/);
		for (var key in temp_object)
		{
			if (key.match(re))
			{
				var safe_key = key.replace(re, '');
				if (typeof temp_object[safe_key] == 'object') // if the safe property already exists
				{
					temp_object[safe_key].push(temp_object[key]);
					delete temp_object[key];
				}
				else
				{
					temp_object[safe_key] = [temp_object[key]];
					delete temp_object[key];
				}
			}
			else if (key.match(re2))
			{
				var safe_key = key.replace(re2, '');
				temp_object[safe_key] = typeof temp_object[key] == 'string' ? [temp_object[key]] : temp_object[key];
				delete temp_object[key];
			}
		}

	   return temp_object;
	};

	function validate_wizard(currentStep)
	{
		$('#creative_new_request div.error').hide(0);
		$('#creative_new_request input, #creative_new_request textarea, #creative_new_request div.control-group').removeClass('error alert alert-error');

		$('#io_wizard .actions button.btn-next').prop('disabled', false);

		if (currentStep == 2)
		{
			if ($('#scene_1_input').val() == '')
			{
				$('#step2 div.error')
					.text('First scene is required')
					.show(0);
				$('#scene_1_input').addClass('error');
				return false;
			}
		}
		else if (currentStep == 3)
		{
			var fields = {
				"is_video": [
					"features_video_youtube_url",
					"features_video_video_play",
					"features_video_mobile_clickthrough_to"
				],
				"is_map": [
					"features_map_locations"
				],
				"is_social": [
					"features_social_twitter_text",
					"features_social_email_subject",
					"features_social_email_message",
					"features_social_linkedin_subject",
					"features_social_linkedin_message"
				]
			}

			var error = false;
			for (var k in fields)
			{
				if ($('input[name="'+k+'"]').prop('checked'))
				{
					fields[k].forEach(function(field){
						var $field = $('[name="'+field+'"]');
						if ($field.val() == '' || $field.val() == undefined || ( $field.is(':radio') && !$('[name="'+field+'"]:radio:checked').val() ))
						{
							$field.addClass('error');
							if ($field.is(":radio"))
							{
								$('#'+field+'').addClass('alert alert-error');
							}
							error = true;
						}
					});
				}
			}

			if (error === true)
			{
				$('#step3 div.error.alert')
					.text("Highlighted fields are required.")
					.show(0);
				return false;
			}
		}
		else if (currentStep == 4)
		{
			$('#io_wizard .actions button.btn-next').prop('disabled', false);
		}

		return true;
	}

	function validate_page_form(is_valid, validation_messages)
	{
		validate(is_valid, validation_messages);
		return is_valid.is_valid;
	}

	function validate_modal_form(is_valid, validation_messages)
	{
		requester_advertiser_data = {};
		get_requester_and_advertiser_data_and_validate(is_valid, validation_messages, requester_advertiser_data);
		if($(insertion_order_form).is(':hidden') && !$(options_form).is(':hidden'))
		{//options is visible
			validate_industry(is_valid, validation_messages);
		}
		return is_valid.is_valid;
	}

	function handle_submit_mpq(is_valid, validation_messages)
	{
		var active_options_json = false;
		var active_insertion_order_json = false;

		if(is_valid.is_valid === true)
		{
			if($(insertion_order_form).is(':hidden') && !$(options_form).is(':hidden'))
			{
				var active_options = get_active_option_set();
				active_options_json = JSON.stringify(active_options);
			}
			else if(!$(insertion_order_form).is(':hidden') && $(options_form).is(':hidden'))
			{
				var active_insertion_order = get_active_insertion_order_set();
				active_insertion_order_json = JSON.stringify(active_insertion_order);
			}
			else
			{//either or neither is visible, error

			}

			var demographics_data =  encode_backwards_compatible_demographics_string();
			var channels_data = encode_selected_contextual_channels();
			var industry_id = encode_selected_industries();
			var advertiser_info = get_advertiser_info();
			var advertiser_json = JSON.stringify(advertiser_info);
			var requester_info = get_requester_info();
			var requester_json = JSON.stringify(requester_info);
			var banner_intake_fields = null;

			if ($('#attach_existing_request').hasClass('active') && $('#creative_request_select').val() != "")
			{
				banner_intake_fields = {'id': $('#creative_request_select').val()};
			}
			else if ($('#creative_new_request').hasClass('active'))
			{
				banner_intake_fields = process_banner_intake();
				banner_intake_fields.requester_email = $('input[name="requester_email"]').val();
				banner_intake_fields.requester_id = $('input[name="requester_id"]').val();
			}

			var cc_email = '';
			var the_array = window.location.pathname.split('/');
			if(the_array.length == 4){
				cc_email = the_array[3];
			}

			var data = {
				demographics: demographics_data,
				options_json: active_options_json,
				insertion_order_json: active_insertion_order_json,
				channels: channels_data,
				advertiser_json: advertiser_json,
				requester_json: requester_json,
				referrer_json: cc_email,
				banner_intake_fields: banner_intake_fields,
				industry_id: industry_id
			};

			$.ajax({
				url: '/mpq/submit_mpq',
				dataType: 'json',
				success: function(data, textStatus, xhr) 
				{
					if(data['was_geo_empty'])
					{
						show_geo_error("Please select at least one region.");
					}
					else
					{
						if(vl_is_ajax_call_success(data))
						{
							$('#submit_modal').hide();
							$("body").append('<form action="/mpq/mpq_submitted" name="mpq_is_submitted" method="post" style="display:none;"><input id="mpq_is_submitted_json_input" type="text" name="submitted_data" value="" /><input id="cc_email" type="text" name="cc_email" value="" /></form>');
							$("#mpq_is_submitted_json_input").val(JSON.stringify(data["submit_data"]));
							$("#cc_email").val(cc_email);
							document.forms['mpq_is_submitted'].submit();
							// the successfull submission removes the link between the session and the page data
						}
						else
						{
							vl_show_ajax_response_data_errors(data, 'submitting mpq data failed');
						}
					}
				},
				error: function(xhr, textStatus, error) 
				{
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				async: true,
				type: "POST", 
				data: data
			});

		}
		else
		{
			show_errors(validation_messages);
		}
	}
	
	function validate(is_valid, validation_messages)
	{
		check_if_mpq_session_populated(is_valid, validation_messages);
		//TODO: validate geo and demographic data - WILL 7/29/2013

		if($(insertion_order_form).is(':hidden') && !$(options_form).is(':hidden'))
		{//options is visible
			option_data = {};
			get_option_data_and_validate(is_valid, validation_messages, option_data);
		}
		else if(!$(insertion_order_form).is(':hidden') && $(options_form).is(':hidden'))
		{//insertion order is visible
			insertion_order_data = {};
			get_insertion_order_data_and_validate(is_valid, validation_messages, insertion_order_data);
		}
		else
		{//either or neither is visible, error
		}

	}

	function validate_industry(is_valid, validation_messages)
	{
		if (encode_selected_industries() == "")
		{
			is_valid.is_valid = false;
			validation_messages.media_targeting = {};
			validation_messages.media_targeting.error_visibility_id = "media_targeting_form_error";
            validation_messages.media_targeting.error_content_id = "media_targeting_form_error_text";
            validation_messages.media_targeting.error_text = "Please Select Advertiser Product";
 		}
	}
	
	function check_if_mpq_session_populated(is_valid, validation_messages)
	{
		var cc_email = '';
		var the_array = window.location.pathname.split('/');
		if(the_array.length == 4){
			cc_email = the_array[3];
		}

		$.ajax({
			url: '/mpq/check_mpq_session',
			dataType: 'json',
			async: false,
			type: "POST", 
			data: {
				referrer_email: cc_email
			},
			success: function(data, textStatus, xhr) 
			{
				if(vl_is_ajax_call_success(data))
				{
					if (!data.has_session_data)
					{
						is_valid.is_valid = false;
						validation_messages.selected_regions = {};
						validation_messages.selected_regions.error_text = 'Please select at least one region.';
						validation_messages.selected_regions.error_visibility_id = 'geo_error_div';
						validation_messages.selected_regions.error_content_id = 'geo_error_text';
					}
				}
				else
				{
					vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
				}
			},
			error: function(xhr, textStatus, error) 
			{
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			}
		});
	}
	
	function get_option_data_and_validate(is_valid, validation_messages, option_data)
	{
		validation_messages.options_form = {};
		validation_messages.options_form.error_visibility_id = "options_error_box";
		validation_messages.options_form.error_content_id = "options_error_text";
		validation_messages.options_form.error_text = "";
		if($('#dollar_tab').is(":visible"))
		{
			if($('#dollar_budget_range_div').is(":visible"))
			{
				is_valid.is_valid = false;
				validation_messages.dollar_budget_numbers = {};
				validation_messages.dollar_budget_numbers.error_visibility_id = "budget_range_selection_error_box";
				validation_messages.dollar_budget_numbers.error_content_id = "budget_range_selection_error_text";
				validation_messages.dollar_budget_numbers.error_text = "Please select a budget range<br>";
			}
			else if($('#dollar_budget_options_div').is(":visible"))
			{
				validation_messages.dollar_budget_numbers = {};
				validation_messages.dollar_budget_numbers.error_visibility_id = "dollar_budget_number_error_box";
				validation_messages.dollar_budget_numbers.error_content_id = "dollar_budget_number_error_text";
				validation_messages.dollar_budget_numbers.error_text = "";
				var inti = 1;
				$('#dollar_budget_options_list').children('form.mpq_option').each(function(){

					if(!validate_option_budget(is_valid, validation_messages.options_form, $(this).find('.mpq_option_amount').val()))
					{
						is_valid.is_valid = false;
						validation_messages.dollar_budget_numbers.error_text += "Please enter a valid budget for option " + inti.toString() + "<br>";
					}
					inti++;
				});
				if(inti == 1)
				{
					is_valid.is_valid = false;
					validation_messages.option_selection = {};
					validation_messages.option_selection.error_visibility_id = "dollar_budget_number_error_box";
					validation_messages.option_selection.error_content_id = "dollar_budget_number_error_text";
					validation_messages.option_selection.error_text = "Please select at least one option.";
				}
			}
			else
			{
				is_valid.is_valid = false;
				validation_messages.options_form.error_text += "Options form critical error: budget form display error.<br>";
			}

		}
		else if($('#impressions_tab').is(":visible"))
		{
			validation_messages.impression_budget_numbers = {};
			validation_messages.impression_budget_numbers.error_visibility_id = "impressions_budget_number_error_box";
			validation_messages.impression_budget_numbers.error_content_id = "impressions_budget_number_error_text";
			validation_messages.impression_budget_numbers.error_text = "";

			var inti = 1;
			$('#impression_budget_options_list').children('form.mpq_option').each(function(){
				if(!validate_option_impression_amount(is_valid, validation_messages.options_form, $(this).find('.mpq_option_amount').val()))
				{
					is_valid.is_valid = false;
					validation_messages.impression_budget_numbers.error_text += "Please enter a valid impression amount for option " + inti.toString() + "<br>";
				}
				inti++;
			});
			if(inti == 1)
			{
				is_valid.is_valid = false;
				validation_messages.option_selection = {};
				validation_messages.option_selection.error_visibility_id = "dollar_budget_number_error_box";
				validation_messages.option_selection.error_content_id = "dollar_budget_number_error_text";
				validation_messages.option_selection.error_text = "Please select at least one option.";
			}
		}
		else
		{
			is_valid.is_valid = false;
			validation_messages.options_form.error_text += "Options form critical error: unable to detect option form.<br>";
		}

	}
	
	function validate_option_budget(valid, error, value)
	{
		var number_regex = new RegExp(/^[1-9]([0-9]+)$/);
		if(!number_regex.test(value))
		{
			return false;
		}
		return true;
	}
	
	function validate_option_impression_amount(valid, error, value)
	{
		var impression_regex = new RegExp(/^[1-9][0-9][0-9]([0-9]+)$/);
		if(!impression_regex.test(value))
		{
			return false;
		}
		return true;
	}
	
	function validate_option_cpm(valid, error, value)
	{
		var cpm_regex = new RegExp(/^(\d+\.?\d*|\.\d+)$/);
		if(!cpm_regex.test(value))
		{
			error = "Please enter a valid cpm";
			valid.is_valid = false;
		}
	}
	
	function validate_option_discount(valid, error, value)
	{
		var discount_regex = new RegExp(/^[0-9][0-9]?$/);
		if(!discount_regex.test(value))
		{
			error = "Please enter a valid discount value";
			valid.is_valid = false;
		}
	}
	
	function get_requester_and_advertiser_data_and_validate(is_valid, validation_messages, requester_advertiser_data)
	{
		validation_messages.requester_phone_number = {};
		validation_messages.requester_phone_number.error_visibility_id = "requester_phone_number_error";
		validation_messages.requester_phone_number.error_content_id = "requester_phone_number_error_text";
		validation_messages.requester_phone_number.error_text = "";
		
		validation_messages.requester_email_address = {};
		validation_messages.requester_email_address.error_visibility_id = "requester_email_address_error";
		validation_messages.requester_email_address.error_content_id = "requester_email_address_error_text";
		validation_messages.requester_email_address.error_text = "";
		
		validation_messages.agency_website = {};
		validation_messages.agency_website.error_visibility_id = "agency_website_error";
		validation_messages.agency_website.error_content_id = "agency_website_error_text";
		validation_messages.agency_website.error_text = "";
		
		validation_messages.requester_name = {};
		validation_messages.requester_name.error_visibility_id = "requester_name_error";
		validation_messages.requester_name.error_content_id = "requester_name_error_text";
		validation_messages.requester_name.error_text = "";
		
		validation_messages.advertiser_website = {};
		validation_messages.advertiser_website.error_visibility_id = "advertiser_website_url_error";
		validation_messages.advertiser_website.error_content_id = "advertiser_website_url_error_text";
		validation_messages.advertiser_website.error_text = "";

		validation_messages.advertiser_business_name = {};
		validation_messages.advertiser_business_name.error_visibility_id = "advertiser_business_name_error";
		validation_messages.advertiser_business_name.error_content_id = "advertiser_business_name_error_text";
		validation_messages.advertiser_business_name.error_text = "";
		
		requester_advertiser_data.requester_phone_number = $("#requester_phone_number_input").val();
		requester_advertiser_data.requester_email_address = $("#requester_email_address_input").val();
		requester_advertiser_data.agency_website = $("#agency_website_input").val();
		requester_advertiser_data.requester_name = $("#requester_name_input").val();
		requester_advertiser_data.advertiser_website = $("#advertiser_website_url_input").val();
		requester_advertiser_data.advertiser_business_name = $("#advertiser_business_name_input").val();

		validate_requester_phone_number(is_valid, validation_messages.requester_phone_number, requester_advertiser_data.requester_phone_number);
		validate_requester_email_address(is_valid, validation_messages.requester_email_address, requester_advertiser_data.requester_email_address);
		validate_agency_website(is_valid, validation_messages.agency_website, requester_advertiser_data.agency_website);
		validate_requester_name(is_valid, validation_messages.requester_name, requester_advertiser_data.requester_name);
		validate_advertiser_website(is_valid, validation_messages.advertiser_website, requester_advertiser_data.advertiser_website);
		validate_advertiser_business_name(is_valid, validation_messages.advertiser_business_name, requester_advertiser_data.advertiser_business_name);
	}
	
	function validate_requester_phone_number(valid, error, value)
	{
		var phone_regex = new RegExp(/[(]?\d{3}[)]?[-\s\.]?\d{3}[-\s\.]?\d{4}/);
		if(!phone_regex.test(value))
		{
			valid.is_valid = false;
			error.error_text = 'Please enter a valid phone number';
		}
	}
	
	function validate_requester_email_address(valid, error, value)
	{
		var email_regex = new RegExp(/^.+@.+[\.].+$/);
		if(!email_regex.test(value))
		{
			valid.is_valid = false;
			error.error_text = 'Please enter a valid email address';
		}
	}
	
	function validate_agency_website(valid, error, value)
	{
		if($('#role_dropdown').val() == 'agency')
		{
			var website_regex = new RegExp(/^.+[\.].+$/);
			if(!website_regex.test(value))
			{
				valid.is_valid = false;
				error.error_text = 'Please enter a valid agency website';
			}
		}
	}
	
	function validate_requester_name(valid, error, value)
	{
		if(value == "")
		{
			valid.is_valid = false;
			error.error_text = 'Please enter a name';
		}
	}
	
	function validate_advertiser_website(valid, error, value)
	{
		var website_regex = new RegExp(/^.+[\.].+$/);
		if(!website_regex.test(value))
		{
			valid.is_valid = false;
			error.error_text = 'Please enter a valid advertiser website';
		}
	}
	
	function validate_advertiser_business_name(valid, error, value)
	{
		if(value == "")
		{
			valid.is_valid = false;
			error.error_text = 'Please enter a business name';
		}
	}
	
	function get_insertion_order_data_and_validate(is_valid, validation_messages, insertion_order_data)
	{
		validation_messages.insertion_order = {};
		validation_messages.insertion_order.error_visibility_id = "insertion_order_form_error";
		validation_messages.insertion_order.error_content_id = "insertion_order_form_error_text";
		validation_messages.insertion_order.error_text = "";
		
		validation_messages.landing_page = {};
		validation_messages.landing_page.error_visibility_id = "landing_page_form_error";
		validation_messages.landing_page.error_content_id = "landing_page_form_error_text";
		validation_messages.landing_page.error_text = "";
		
		insertion_order_data.num_impressions = $('#impressions_box').val();
		insertion_order_data.start_date = $('#start_date_input').val();
		insertion_order_data.term_duration = $('#flight_term').val()
		insertion_order_data.landing_page = $('#landing_page_input').val();
		insertion_order_data.is_retargeting = $('#retargeting_input').prop('checked');
		insertion_order_data.term_type = $('#period_dropdown').val();
		if(insertion_order_data.term_type == 'FIXED_TERM')
		{
			insertion_order_data.end_date = $('#in_total_end_date_box').val();
		}
		else if(insertion_order_data.term_duration == '0')
		{
			insertion_order_data.end_date = $('#specify_end_date_box').val();
		}
		else
		{
			insertion_order_data.end_date = false;
		}
		validate_insertion_order_impressions(is_valid, validation_messages.insertion_order, insertion_order_data.num_impressions);
		validate_insertion_order_start_date(is_valid, validation_messages.insertion_order, insertion_order_data.start_date);
		validate_insertion_order_term_duration(is_valid, validation_messages.insertion_order, insertion_order_data.term_duration);
		validate_insertion_order_landing_page(is_valid, validation_messages.landing_page, insertion_order_data.landing_page);
		validate_insertion_order_retargeting(is_valid, validation_messages.insertion_order, insertion_order_data.is_retargeting);
		validate_insertion_order_end_date(is_valid, validation_messages.insertion_order, insertion_order_data.end_date, insertion_order_data.start_date);
		validate_insertion_order_creative(is_valid, validation_messages.insertion_order);
		validation_messages.insertion_order.error_text = validation_messages.insertion_order.error_text.substring(0, validation_messages.insertion_order.error_text.length -4);

	}
	
	function validate_insertion_order_impressions(valid, error, value)
	{
		var impressions = value.replace(/,/g, '');
		if(!$.isNumeric(impressions))
		{
			valid.is_valid = false;
			error.error_text += "Impressions value must be a number<br>";
		}
		else if(parseInt(impressions) <= 1000)
		{
			valid.is_valid = false;
			error.error_text += "Impressions value must be larger than 1000<br>";
		}
	}

	function validate_insertion_order_start_date(valid, error, value)
	{
		if(value != '')
		{
			var date_value = new Date(value);
			var today = new Date();
			today.setHours(0,0,0,0);
			if(date_value < today)
			{
				valid.is_valid = false;
				error.error_text += "Start date cannot be earlier than today<br>";
			}
		}
		else
		{
			valid.is_valid = false;
			error.error_text += "Start date cannot be blank<br>";
		}
	}

	function validate_insertion_order_term_duration(valid, error, value)
	{
		if(value != '-1' && value != '0' && (1 > parseInt(value) || parseInt(value) > 12))
		{
			valid.is_valid = false;
			error.error_text += "Invalid Flight term value<br>";
		}
	}

	function validate_insertion_order_landing_page(valid, error, value)
	{
		var landing_regex = new RegExp(/^.+[\.].+$/);
		if(!landing_regex.test(value))
		{
			valid.is_valid = false;
			error.error_text += "Invalid landing page value<br>";
		}
	}

	function validate_insertion_order_retargeting(valid, error, value)
	{
		if(value != true && value != false)
		{
			valid.is_valid = false;
			error.error_text += "Error reading retargeting value<br>";
		}
	}

	function validate_insertion_order_end_date(valid, error, value, start_date)
	{
		if(value !== false)
		{
			if(value != '')
			{
				var end_value = new Date(value);
				var start_value = new Date(start_date);
				if(end_value <= start_value)
				{
					valid.is_valid = false;
					error.error_text += "End date must be later than start date<br>";
				}
			}
			else
			{
				valid.is_valid = false;
				error.error_text += "End date cannot be blank<br>";
			}
		}
	}	

	function validate_insertion_order_creative(valid, error)
	{
		if ($('#creative_new_request').hasClass('active'))
		{
			var creative_valid = validate_wizard(2) && validate_wizard(3) && $('input[name="is_form_complete"]').val() == "true";

			if (!creative_valid)
			{
				valid.is_valid = false;
				error.error_text += "Please complete creative request form<br />";
			}
		}
		else if ($('#attach_existing_request').hasClass('active'))
		{
			if ($('#creative_request_select').val() == '')
			{
				valid.is_valid = false;
				error.error_text += "Please select an existing creative request<br />";
			}
		}
	}


    
    function period_change(dropdown_value)
    {
		if(dropdown_value == "FIXED_TERM")
		{
			$('#flight_term_picker').hide();
			$('#end_date_picker').show();
		}
		else
		{
			$('#flight_term_picker').show();
			$('#end_date_picker').hide();
		}
    }
    
    function flight_term_change(dropdown_value)
    {
		if(dropdown_value == 0)
		{
			$('#end_date_field').show();
			$('#literally_just_the_word_months').hide();
		}
		else
		{
			$('#end_date_field').hide(); 
			if(dropdown_value != -1)
			{
				$('#literally_just_the_word_months').show();
			}
			else
			{
				$('#literally_just_the_word_months').hide();
			}
		}
    }
    
    function toggle_forms()
    {
		if(order_form)
		{
			order_form = false;
			$('#header_title').html("Proposal Options <small>Please use this section to specify the different options that will be included in the proposal</small>");
			$('#insertion_order_form_success').hide();
			$('#insertion_order_form_error').hide();
			$('#landing_page_form_error').hide();
			$('#insertion_order_form').hide();
			$('#options_form').show();
			$('#form_toggle_button').attr('class', 'link');
			$('#form_toggle_button').html('<small><i class="icon-share-alt"></i> Toggle to insertion order</small>');
			$('#insertion_order_submit_button').hide();
			$('#proposal_submit_button').show();

		}
		else
		{
			order_form = true;
			$('#header_title').html("Insertion Order <small>Please use this section to specify the final campaign parameters</small>");
			$('#insertion_order_form').show();
			$('#insertion_order_form_success').hide();
			$('#insertion_order_form_error').hide();
			$('#insertion_landing_page_error').hide();
			$('#options_form').hide();
			$('#form_toggle_button').attr('class', 'link');
			$('#form_toggle_button').html('<small><i class="icon-edit"></i> Toggle to proposal</small>')
			$('#insertion_order_submit_button').show();
			$('#proposal_submit_button').hide();
			
		}
    }

	function show_geo_error(error)
	{
	    $('#geo_error_text').html(error);
	    $('#geo_error_div').show();
	    $(window).scrollTop($('#geo_error_div').offset());
	}

	$(document).ready(function () {
            $('.tool_poppers').popover();
            init_iab_category_data();
            init_industry_data();
            init_custom_regions_data();
            $('#datetimepicker1').datetimepicker({pickTime: false, maskInput: true});
	    $('#datetimepicker2').datetimepicker({pickTime: false, maskInput: true});
	    $('#datetimepicker3').datetimepicker({pickTime: false, maskInput: true});
	    //$('#insertion_order_form').hide();
	    //$('#insertion_order_submit_button').hide();
		$('#end_date_picker').hide();
		$('#end_date_field').hide();
		$('#literally_just_the_word_months').hide()
		$('#role_dropdown').change(function() { //when the self claimed drop down is selected, toggle visibility of the agency website
			handle_role_dropdown_value();
		});

		$("#submit_proposal_request_button").click(function() {
			$(".mpq_error_box").hide();
			$('#geo_error_div').hide();
			var is_valid = {};
			is_valid.is_valid = true;
			var validation_messages = {};

			var page_still_valid = validate_page_form(is_valid, validation_messages);

			if (validate_modal_form(is_valid, validation_messages) && page_still_valid)
			{
				handle_submit_mpq(is_valid, validation_messages);
			}
			else
			{
				if (!page_still_valid) $('submit_modal').modal('hide');
				show_errors(validation_messages);
			}
		});
		
		$("#proposal_submit_button, #insertion_order_submit_button").click(function() {
			$(".mpq_error_box").hide();
			$('#geo_error_div').hide();
			var is_valid = {};
			is_valid.is_valid = true;
			var validation_messages = {};

			if (validate_page_form(is_valid, validation_messages))
			{
				if($(insertion_order_form).is(':hidden') && !$(options_form).is(':hidden'))
				{
					$('#industry_id_div').show();// show for mpq
				} 
				else
				{
					$('#industry_id_div').hide();// hide for io
				}
				$('#submit_modal').modal('show');
			}
			else
			{
				show_errors(validation_messages);
			}
		});

		function show_errors(validation_messages)
		{
			var top_error = {};
			top_error.offset_top = Number.MAX_VALUE;
			var top_padding = 20;

			$.each(validation_messages, function(){
				if(this.error_text != "")
				{
					var error_id = this.error_visibility_id;
					var error_element = this.error_content_id;
					var error_text = this.error_text;
					$('#' + error_id).show();
					$('#' + error_element).html(error_text);

					var offset = $('#' + error_id).offset();
					if (offset.top < top_error.offset_top)
					{
						top_error.offset_top = offset.top;
						top_error.error_box = $('#' + error_id);
					}
				}
			});
			$(window).scrollTop(top_error.error_box.offset().top - $('.navbar').height() - top_padding); //Goes to whichever error is higher on the page
		}
		
		handle_role_dropdown_value();
		
		$("input[type=text]").click( function(){this.select();});
		
		$('#welcome_modal').modal('show');
                
                //Put commas in number.
                $.fn.digits = function() {
                    return this.each(function() {
                        var value = $(this).val().replace(/[^0-9\.]/gi,'');
                        $(this).val(value.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,"));
                    });
                }
                //Put commas in impressions.
                $("#impressions_box").focusout(function() {
                    if($(this).val().length) {
                        $(this).digits();
                    }
                });
                
	});
	</script>
	<?php
}

?>
