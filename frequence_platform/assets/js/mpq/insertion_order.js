;(function($){

	/* geo_component_functions.php */
	google.load('visualization', '1', {packages: ['corechart']});
	google.load('visualization', '1', {packages: ['table']});
	google.load("visualization", "1", {packages: ["map"]});

	var geocoder = new google.maps.Geocoder();

	function get_selected_geo_regions()
	{
		var record_ids = $('#custom_regions_multiselect').val();
		return record_ids;
	}

	function remove_existing_custom_regions()
	{
		$('#custom_regions_multiselect').select2('data', null);
		$.ajax({
			url: '/mpq_v2/remove_selected_custom_regions',
			dataType: 'json',
			async: true,
			type: "POST", 
			success: function(data) 
			{
				if(data.is_success == false)
				{
					vl_show_ajax_response_data_errors(data, 'failed to remove selected custom regions');
				}
			},
			error: function(xhr, textStatus, error) 
			{
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			}
		});
	}

	function get_zips_array()
	{
		var raw_zips_string = $("#set_zips").val();
		var zips_string_2 = raw_zips_string.replace(/,/g, ' '); // make commas into spaces
		var zips_string_3 = $.trim(zips_string_2); // remove beginning and ending white space
		var zips_array = zips_string_3.split(/\s+/); // break into array on whitespace characters
		
		return zips_array;
	}

	function check_zips(zips_array)
	{
		var num_zips = zips_array.length;

		var are_zips_valid = true;
		for(var ii=0; ii < num_zips; ii++)
		{
			var test_zip = zips_array[ii];
			if(-1 != test_zip.search(/\D/) || // check non digit
				test_zip.length != 5)						// check 5 digits
			{
				are_zips_valid = false;
				break;
			}
		}

		return are_zips_valid;
	}

	window.update_zip_set = function(action, zip)
	{
		var zips = get_zips_array();

		if(action == "remove_zipcode")
		{
			for(var ii=0; ii < zips.length; ii++)
			{
				if(zip == zips[ii])
				{
					zips.splice(ii, 1);
					ii--; // look at the new element at this index
				}
			}
		}
		else if(action == "add_zipcode")
		{
			var is_in_array = false;
			var num_zips = zips.length;
			for(var ii=0; ii < num_zips; ii++)
			{
				if(zip == zips[ii])
				{
					is_in_array = true;
					break;
				}
			}

			if(!is_in_array)
			{
				zips.push(zip);
			}
		}
		else
		{
		}
		remove_existing_custom_regions();
		change_zip_textarea(zips);
	}

	function change_zip_textarea(zips)
	{
		var zips_string = '';
		var num_zips = zips.length;
		var last_index = num_zips - 1;
		for(var ii=0; ii < num_zips; ii++)
		{
			var zip = zips[ii];
			if(ii != last_index)
			{
				zips_string += zip + ', ';
			}
			else
			{
				zips_string += zip;
			}
		}
		document.getElementById('set_zips').value = zips_string;
	}


	function handle_set_zips(event)
	{
		var is_custom_regions = false;
		if(event == 'custom_regions')
		{
			is_custom_regions = true;
		}
		else
		{
			remove_existing_custom_regions();
		}
		var zips = get_zips_array();
		var are_zips_valid = check_zips(zips);

		if(!are_zips_valid || zips.length <= 0)
		{
			return;
		}

		var zips_json = JSON.stringify(zips);

		var map_frame = document.getElementById('iframe-map');
		var map_window = map_frame.contentWindow ? map_frame.contentWindow : map_frame.contentDocument; // handle IE windows

		var latitude = 0;
		var longitude = 0;
		if(typeof map_window !== "undefined" &&
			typeof map_window.get_map_center_latitude !== "undefined"
		)
		{
			latitude = map_window.get_map_center_latitude();
			longitude = map_window.get_map_center_longitude();
		}

		start_map_loading_gif();

		var confirm = true;
		if (zips.length >= 1000 && zips.length <= 4000)
		{
			confirm = window.confirm('Warning: you have selected '+ zips.length +' zip codes. This may take a long time or even freeze your browser. Are you sure you want to continue?');
		}
		else if (zips.length > 4000)
		{
			window.alert("Warning: You've entered too many zip codes. Please comment in the 'Notes' section below that you have more zip codes and a representative will contact you to add them to your request.");
			confirm = false;
		}

		if (confirm)
		{

			$.ajax({
				url: '/mpq/save_zips',
				dataType: 'json',
				success: function(data, textStatus, xhr) 
				{
					if(vl_is_ajax_call_success(data))
					{
						var mapLinks ="<iframe id='iframe-map' src='/mpq/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
						document.getElementById('region-links').innerHTML = '';
						document.getElementById('region-links').innerHTML = mapLinks;

						// loading gif is stopped when iframe map loads. 
						// stop_map_loading_gif(); is called from within the iframe
					}
					else
					{
						stop_map_loading_gif();
						vl_show_ajax_response_data_errors(data, 'saving map zips data failed');
					}
				},
				error: function(xhr, textStatus, error) 
				{
					stop_map_loading_gif();
					vl_show_jquery_ajax_error(xhr, textStatus, error);
				},
				async: true,
				type: "POST", 
				data: {
					zips_json : zips_json,
					map_center_latitude : latitude,
					map_center_longitude : longitude,
					is_custom_regions : is_custom_regions
				}
			});
		}
		else
		{
			stop_map_loading_gif();
		}
	}

	$('input#radius, input#address').keypress(function(e)
	{
		var key = e.keyCode || e.which;
		var enterKeyCode = 13;
		if(key==enterKeyCode)
		{
			flexigrid();
		}
	});

	$('#searchbut').on('click', function(){
		flexigrid();
	});

	function checkDataTypes(desiredDataType,theValue,theID){
		if(desiredDataType == "numeric"){
			if(isNaN(theValue)==true){
				alert(theValue+" for "+theID+" is not "+desiredDataType);
				return false;
			}
			else{
				return true;
			}
		}
	}

	function flexigrid(first)
	{
		remove_existing_custom_regions();
		
		if(checkDataTypes("numeric",document.getElementById("radius").value,"Radius Input")==true)
		{
			var address = document.getElementById("address").value;
			if(address == '')
			{
				document.getElementById("address").value = address;
			}
			else
			{
			var passed;

			geocoder.geocode( { 'address': address}, function(results, status) 
			{
				if (status == google.maps.GeocoderStatus.OK) 
				{

					passed = document.getElementById("region_type").value+
							"_"+document.getElementById("radius").value+
							"_"+results[0].geometry.location.lat()+
							"_"+results[0].geometry.location.lng()+
							"_";

					if (first !== undefined && savedZips !== "") {

						var mapLinks ="<iframe id='iframe-map' src='/mpq/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
						document.getElementById('region-links').innerHTML = '';

						document.getElementById('region-links').innerHTML = mapLinks;
						document.getElementById("sliderBodyContent_geo").innerHTML='';

						document.getElementById("radius").value = '';
						document.getElementById("address").value = '';
					} 
					else 
					{
						start_map_loading_gif();

						var confirm = true;
						if ($('#radius').val() >= 100 && $('#radius').val() < 300)
						{
							confirm = window.confirm('Warning: you have selected a large radius. This may take a long time or even freeze your browser. Are you sure you want to continue?');
						}
						else if ($('#radius').val() >= 300)
						{
							window.alert('Warning: your radius is too large. Please enter a smaller radius value or leave a comment in the notes section below with the area you would like to target.');
							confirm = false;
						}

						if (confirm)
						{
							var jsonData = $.ajax({
								url: "/mpq/save_geo_search/"+passed,
								dataType:"json",
								async: true,
								success: function(data)
								{
									if(vl_is_ajax_call_success(data))
									{
										change_zip_textarea(data.result_regions);
									}
									else
									{
										vl_show_ajax_response_data_errors(data, 'do geo search failed');
									}
									var mapLinks ="<iframe id='iframe-map' src='/mpq/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
									document.getElementById('region-links').innerHTML = '';
									document.getElementById('region-links').innerHTML = mapLinks;
								},
								error: function(xhr, textStatus, error) 
								{
									vl_show_jquery_ajax_error(xhr, textStatus, error);
								}
							}).responseText;
						}
						else
						{
							stop_map_loading_gif();
						}
					}
				} 
				else 
				{
					alert("Geocode was not successful for the following reason: " + status);
				}
			});
			}
		}
	}

	function show_map()
	{
			document.getElementById('region-links').innerHTML = '';
			var mapLinks ="<iframe id='iframe-map' src='/mpq/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
			document.getElementById('region-links').innerHTML = mapLinks;
	}

	function setup_initial_css()
	{
		$(".map_overlay_box").css({'background-color':'rgb(255,0,0)'}); // 'rgba(255,0,0,0.5)'});
	}

	function update_geo_stats(population, income, region_details)
	{
		//$("#geo_stats_population_value").text(population);
		//$("#geo_stats_income_value").text(income);
		//$("#geo_region_summary").text(region_details);
	}

	function start_map_loading_gif()
	{
		$("#map_loading_image").show("fast");
		$("#region-links").fadeTo(200, 0);
	}

	function stop_map_loading_gif()
	{
		$("#map_loading_image").hide("fast");
		$("#region-links").fadeTo(200, 1);
	}

	show_map();

	/* mpq_component_functions.php */
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

	var encoded_demographics_string_separator = "_";

	function encode_demographics_in_string()
	{
		var demographic_ids = new Array(
			'gender_male_demographic',
			'gender_female_demographic',
			'age_under_18_demographic',
			'age_18_to_24_demographic',
			'age_25_to_34_demographic',
			'age_35_to_44_demographic',
			'age_45_to_54_demographic',
			'age_55_to_64_demographic',
			'age_over_65_demographic',
			'income_under_50k_demographic',
			'income_50k_to_100k_demographic',
			'income_100k_to_150k_demographic',
			'income_over_150k_demographic',
			'education_no_college_demographic',
			'education_college_demographic',
			'education_grad_school_demographic',
			'parent_no_kids_demographic',
			'parent_has_kids_demographic'
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

	// removed some code which automatically set are_options_valid to true.
	function is_active_option_set_valid()
	{
		var are_options_valid = true;
		var active_set_id = $(".mpq_options_tab.active").attr("id");
		//var active_set = "";
		switch(active_set_id)
		{
			case "dollar_tab":
				break;
			case "impressions_tab":
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

	var order_form = true; //spoof to the opposite of what we want to toggle into what we want ?>;∂
	
	
	window.period_change = function(dropdown_value)
	{
		if(dropdown_value == -1)
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
	
	window.flight_term_change = function(dropdown_value)
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

	$('#geo_tab_help_button').on('click', function(){
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
	});

	$('#geo_search_help_button').on('click', function(){
		$('#radius_span').attr('data-intro', "Specify the number of miles around your location that you\'d like to target here");
		$('#radius_span').attr('data-position', "bottom");
		$('#address').attr('data-intro', "Specify your location here. You can use an address, zip code, city or even a landmark.");
		$('#address').attr('data-position', "right");
		$('body').chardinJs('start');
		make_overlay_ok();
	});

	$('#demo_targeting_help_button').on('click', function(){
		$('#hhi_checkboxes').attr('data-intro', "The Household Income demographic refers to the total annual income per household");
		$('#hhi_checkboxes').attr('data-position', "left");
		$('#hhi_checkboxes').css('background-color', 'white');
		$('#ed_checkboxes').attr('data-intro', "The Education demographic refers to the highest level of education acheived by the target audience. For example, \"College\" would include audiences with at least a college degree.");
		$('#ed_checkboxes').attr('data-position', "right");
		$('#ed_checkboxes').css('background-color', 'white');
		$('body').chardinJs('start');
		make_overlay_ok();
	});

	$('#media_targeting_help_button').on('click', function(){

		$('#media_targeting_section').attr('data-intro', "<div style=\"width:500px\">Most sites on the internet have content that can be categorized according to these channels. You should select contextual channels that: <br>1) Match the advertiser's offering and <br>2) Match the advertiser's audience's interests. <br>For example, a car dealership who tends to sell their cars to sports enthusiasts should target both the \"Automotive\" and \"Sports\" contextual channels.</div>");

		$('#media_targeting_section').attr('data-position', "bottom");
		$('#media_targeting_section').css('background-color', 'white');
		$('body').chardinJs('start');
		make_overlay_ok();
	});

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

	$("#known_zips_load_button").on("click", function(e){
		handle_set_zips(e);
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
			var html = '<div style="margin-left: 20px;"><b>'+ result.data.Key.substr(7) +'</b> <button class="btn btn-danger btn-mini delete_file" data-bucket="'+ result.data.Bucket +'" data-name="'+ result.data.Key +'"><span class="icon icon-remove"></span></button></div>';
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
		if(io_array.term_type == '-1')
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

	function validate_page_form(is_valid, validation_messages)
	{
		validate(is_valid, validation_messages);
		return is_valid.is_valid;
	}

	function validate_modal_form(is_valid, validation_messages)
	{
		requester_advertiser_data = {};
		get_requester_and_advertiser_data_and_validate(is_valid, validation_messages, requester_advertiser_data);
		return is_valid.is_valid;
	}

	function handle_submit_mpq(is_valid, validation_messages)
	{
		var active_options_json = false;
		var active_insertion_order_json = false;

		if(is_valid.is_valid === true)
		{
			var active_insertion_order = get_active_insertion_order_set();
			active_insertion_order_json = JSON.stringify(active_insertion_order);

			var demographics_data =  encode_backwards_compatible_demographics_string();
			var channels_data = encode_selected_contextual_channels();
			var advertiser_info = get_advertiser_info();
			var advertiser_json = JSON.stringify(advertiser_info);
			var requester_info = get_requester_info();
			var requester_json = JSON.stringify(requester_info);
			var banner_intake_fields = null;

			banner_intake_fields = process_banner_intake();
			if (typeof banner_intake_fields.creative_files == 'object')
			{
				banner_intake_fields.scenes = [''];
				banner_intake_fields.requester_email = $('input[name="requester_email"]').val();
				banner_intake_fields.requester_id = $('input[name="requester_id"]').val();
			}
			else
			{
				banner_intake_fields = null;
			}

			var cc_email = requester_info.email_address;

			var data = {
				demographics: demographics_data,
				options_json: active_options_json,
				insertion_order_json: active_insertion_order_json,
				channels: channels_data,
				advertiser_json: advertiser_json,
				requester_json: requester_json,
				referrer_json: cc_email,
				banner_intake_fields: banner_intake_fields
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
							$("body").append('<form action="/embed/insertion_order/summary" name="mpq_is_submitted" method="post" style="display:none;"><input id="mpq_is_submitted_json_input" type="text" name="submitted_data" value="" /><input id="cc_email" type="text" name="cc_email" value="" /><input id="insertion_order_id" name="insertion_order_id"/></form>');
							$("#mpq_is_submitted_json_input").val(JSON.stringify(data["submit_data"]));
							$('input[name="insertion_order_id"').val(data['insertion_order_id']);
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
		
		insertion_order_data = {};
		get_insertion_order_data_and_validate(is_valid, validation_messages, insertion_order_data);
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
		if(insertion_order_data.term_type == '-1')
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
		validate_insertion_order_term_type(is_valid, validation_messages.insertion_order, insertion_order_data.term_type);
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

	function validate_insertion_order_term_type(valid, error, value)
	{
		if(value != "BROADCAST_MONTHLY" && value != "MONTH_END" && value != "FIXED_TERM")
		{
			valid.is_valid = false;
			error.error_text += "Invalid term type value<br>";
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
		if(dropdown_value == -1)
		{
			$('#flight_term_picker').hide();
			$('#end_date_picker').show();
		}
		else
		{
			$('#flight_term_picker').show();
			$('#end_date_picker').hide();
			switch(dropdown_value)
			{
			case "1":
				$('#literally_just_the_word_months').html('days');
				break;
			case "7":
				$('#literally_just_the_word_months').html('weeks');
				break;
			case "30":
				$('#literally_just_the_word_months').html('months');
				break;
			default:
				$('#literally_just_the_word_months').html('months');
				$("#insertion_order_form_error_text").html('Warning 6014: Unknown period type');
				$("#insertion_order_form_error").show();
				break;
			}

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

	function show_geo_error(error)
	{
	    $('#geo_error_text').html(error);
	    $('#geo_error_div').show();
	    $(window).scrollTop($('#geo_error_div').offset());
	}

	$('.tool_poppers').popover();
	init_iab_category_data();
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
	
	$("#proposal_submit_button, #insertion_order_submit_button").click(function() {
		$(".mpq_error_box").hide();
		$('#geo_error_div').hide();
		var is_valid = {};
		is_valid.is_valid = true;
		var validation_messages = {};

		if (validate_page_form(is_valid, validation_messages))
		{
			handle_submit_mpq(is_valid, validation_messages);
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
								   
})(jQuery);
