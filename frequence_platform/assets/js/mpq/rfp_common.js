google.load('visualization', '1', {packages: ['corechart']});
google.load('visualization', '1', {packages: ['table']});
google.load("visualization", "1", {packages: ["map"]});

var geocoder = new google.maps.Geocoder();

var last_account_executive = rfp_account_executive_data;
var smart_option_naming = true;

function get_selected_geo_regions()
{
	var record_ids = $('#custom_regions_multiselect').val();
	return record_ids;
}

function remove_existing_custom_regions(location_id)
{
	location_id = (location_id == undefined) ? location_collection.current_location_tab : location_id;

	$('#custom_regions_multiselect').select2('data', null);
	$.ajax({
		url: '/mpq_v2/remove_selected_custom_regions',
		dataType: 'json',
		async: true,
		type: "POST",
		data: {
			location_id: location_id,
			mpq_id: mpq_id
		},
		success: function(data)
		{
			if(data.session_expired === true)
			{
				if(!is_rfp)
				{
					$('#io_redirect_to_all_ios_modal').openModal();
					io_redirect_timeout_id = window.setTimeout(function(){
						redirect_to_all_ios('io_lock');
					}, 8000);
				}
				else
				{
					Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
				}
			}
			else if(data.is_success == false)
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
	$('#set_zips_label').addClass('active');
}


function handle_set_zips(event, optional_location_id)
{
	if(location_collection.locations.length == 0)
	{
		location_collection.create_new_location(false, false);
		var location_id = 0;
	}
	else
	{
		var location_id = (optional_location_id !== undefined) ? optional_location_id : location_collection.get_current_location_id();
	}

	var is_custom_regions = false;
	if(event == 'custom_regions')
	{
		is_custom_regions = true;
	}
	else
	{
		remove_existing_custom_regions(location_id);
	}

	var zips = get_zips_array();
	if(zips.length <= 0 || zips == [""])
	{
		return;
	}

	var zips_json = JSON.stringify(zips);

	var map_frame = document.getElementById('iframe-map');
	var map_window = map_frame.contentWindow ? map_frame.contentWindow : map_frame.contentDocument; // handle IE windows

	var latitude = 0;
	var longitude = 0;
	var location_name = (location_collection.locations[location_id].has_been_renamed) ? location_collection.get_location_name(location_id) : "";

	if(typeof map_window !== "undefined" && typeof map_window.get_map_center_latitude !== "undefined")
	{
		latitude = map_window.get_map_center_latitude();
		longitude = map_window.get_map_center_longitude();
	}

	if(zips.length < 1000)
	{
		start_map_loading_gif();
		search_zips_function(event, zips_json, latitude, longitude, is_custom_regions, location_id, location_name);
	}
	else if (zips.length >= 1000 && zips.length <= 7000)
	{
		window.confirm_search_zips_json = zips_json;
		var escaped_name = location_name.replace(/'/g, "\\'");
		var search_zips_function_string = "search_zips_function(event, window.confirm_search_zips_json, '" + latitude + "', '" + longitude + "', " + is_custom_regions + ", " + location_id + ", '" + escaped_name + "')";
		var toast_content =
			'You have selected ' + zips.length + ' zip codes. ' +
			'This may take a long time or even freeze your browser.<br>' +
			'Are you sure you want to continue?<br>' +
			'<button style="margin:0px 20px;" class="btn-floating waves-effect waves-light green darken-3" type="button" onclick="' + search_zips_function_string + '"><i class="material-icons">&#xE876;</i></button>' +
			'<button class="btn-floating waves-effect waves-light red darken-4" type="button" onclick="document.getElementById(\'toast-container\').parentElement.removeChild(document.getElementById(\'toast-container\'));"><i class="material-icons">&#xE033;</i></button>';
		Materialize.toast(toast_content, 1500000, 'toast_top', '', 'Please confirm:');
	}
	else if (zips.length > 7000)
	{
		Materialize.toast("You've entered too many zip codes. Please reduce the number or break down your zips into multiple locations.", 20000, '');
	}
}
window.handle_set_zips = handle_set_zips;

function search_zips_function(event, zips_json, latitude, longitude, is_custom_regions, location_id, location_name)
{
	start_map_loading_gif();
	$.ajax({
		url: '/mpq/save_zips',
		dataType: 'json',
		success: function(data, textStatus, xhr)
		{
			if(typeof data.session_expired !== "undefined" && data.session_expired === true)
			{
				if(!is_rfp)
				{
					$('#io_redirect_to_all_ios_modal').openModal();
					io_redirect_timeout_id = window.setTimeout(function(){
						redirect_to_all_ios('io_lock');
					}, 8000);
				}
				else
				{
					Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
				}
			}
			else if(vl_is_ajax_call_success(data))
			{
				var mapLinks ="<iframe id='iframe-map' src='/mpq/map/" + location_id + "#snug' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
				document.getElementById('region-links').innerHTML = '';
				document.getElementById('region-links').innerHTML = mapLinks;
				location_collection.rename_location_in_ui(location_id, data.custom_location_name);
				if(!location_collection.locations[location_id].has_been_populated)
				{
					location_collection.locations[location_id].has_been_populated = true;
					location_collection.handle_populated_location_counter_and_span("add location");
				}
				if(is_rfp == false)
				{
					io_update_side_nav();
				}
				var zips_string = data.successful_zips.zcta.join(', ');
				$('textarea#set_zips').val(zips_string);
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
			is_custom_regions : is_custom_regions,
			location_id : location_id,
			location_name : location_name,
			mpq_id: mpq_id
		}
	});
	$('#toast-container').remove();
}
window.search_zips_function = search_zips_function;

$('input#radius, input#address').keypress(function(e) {
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

function checkDataTypes(desiredDataType,theValue,theID)
{
	if(desiredDataType == "numeric")
	{
		if(isNaN(theValue)==true)
		{
			alert(theValue+" for "+theID+" is not "+desiredDataType);
			return false;
		}
		else
		{
			return true;
		}
	}
}

function flexigrid(first)
{
	var radius = document.getElementById("radius").value;
	if(checkDataTypes("numeric", radius, "Radius Input") == true)
	{
		var address = document.getElementById("address").value;
		if(address == '')
		{
			document.getElementById("address").value = address;
		}
		else
		{
			var passed;

			geocoder.geocode({'address': address}, function(results, status) {
				var location_id;
				if (status == google.maps.GeocoderStatus.OK)
				{
					if(location_collection.locations.length === 0)
					{
						location_collection.create_new_location();
						location_id = 0;
					}
					else
					{
						location_id = location_collection.get_current_location_id();
					}

					location_collection.set_is_radius_search(true, location_id, radius, address);

					passed = document.getElementById("region_type").value +
						"_" + radius +
						"_" + results[0].geometry.location.lat() +
						"_" + results[0].geometry.location.lng();

					if (first !== undefined && savedZips !== "") {

						var mapLinks ="<iframe id='iframe-map' src='/mpq/map/" + location_id + "#snug' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
						document.getElementById('region-links').innerHTML = '';

						document.getElementById('region-links').innerHTML = mapLinks;
						document.getElementById("sliderBodyContent_geo").innerHTML='';

						document.getElementById("radius").value = '';
						document.getElementById("address").value = '';
					}
					else
					{
						if(radius < 100)
						{
							radius_search_function(null, location_id, radius, address, passed);
						}
						else if(radius >= 100 && radius <= 300)
						{
							var radius_search_function_string = "radius_search_function(event, " + location_id + ", " + radius + ", '" + address + "', '" + passed + "')";
							var toast_content =
								'You have selected a large radius. ' +
								'This may take a long time or even freeze your browser.<br>' +
								'Are you sure you want to continue?<br>' +
								'<button style="margin:0px 20px;" class="btn-floating waves-effect waves-light green darken-3" type="button" onclick="' + radius_search_function_string + '"><i class="material-icons">&#xE876;</i></button>' +
								'<button class="btn-floating waves-effect waves-light red darken-4" type="button" onclick="document.getElementById(\'toast-container\').parentElement.removeChild(document.getElementById(\'toast-container\'));"><i class="material-icons">&#xE033;</i></button>';
							Materialize.toast(toast_content, 1500000, 'toast_top', '', 'Please confirm:');
						}
						else if(radius > 300)
						{
							Materialize.toast("Ooops! Your radius is too large. Please enter a smaller radius value.", 20000, '');
						}
					}
				}
				else
				{
					Materialize.toast("Oooops! There was a problem searching for your location.", 20000, 'toast_top');
				}
			});
		}
	}
}

function radius_search_function(event, location_id, radius, address, passed)
{
	start_map_loading_gif();
	remove_existing_custom_regions(location_id);

	var location_name = (location_collection.locations[location_id].has_been_renamed) ?
		location_collection.get_location_name(location_id) :
		address + " - " + radius + " mile radius";

	var jsonData = $.ajax({
		url: "/mpq/save_geo_search/" + passed,
		dataType:"json",
		data: {
			location_id: location_id,
			location_name: location_name,
			address: address,
			mpq_id: mpq_id
		},
		type: 'post',
		async: true,
		success: function(data)
		{
			if(typeof data.session_expired !== "undefined" && data.session_expired === true)
			{
				if(!is_rfp)
				{
					$('#io_redirect_to_all_ios_modal').openModal();
					io_redirect_timeout_id = window.setTimeout(function(){
						redirect_to_all_ios('io_lock');
					}, 8000);
					stop_map_loading_gif()
				}
				else
				{
					Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
				}
			}
			else
			{
				if(vl_is_ajax_call_success(data))
				{
					location_collection.rename_location_in_ui(location_id, location_name);
					change_zip_textarea(data.result_regions);
					if(!location_collection.locations[location_id].has_been_populated)
					{
						location_collection.locations[location_id].has_been_populated = true;
						location_collection.handle_populated_location_counter_and_span("add location");
					}
					if(is_rfp == false)
					{
						io_update_side_nav();
					}
				}
				else
				{
					stop_map_loading_gif();
					vl_show_ajax_response_data_errors(data, 'do geo search failed');
				}
				var mapLinks ="<iframe id='iframe-map' src='/mpq/map/" + location_id + "#snug' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
				document.getElementById('region-links').innerHTML = '';
				document.getElementById('region-links').innerHTML = mapLinks;
			}
		},
		error: function(xhr, textStatus, error)
		{
			stop_map_loading_gif();
			vl_show_jquery_ajax_error(xhr, textStatus, error);
		}
	}).responseText;
	$('#toast-container').remove();
}
window.radius_search_function = radius_search_function;

function show_map(location_id)
{
	var loading_gif_visible = $("#map_loading_image").is(":visible");
	if(!loading_gif_visible) start_map_loading_gif();

	var region_links = document.getElementById('region-links');
	if (region_links) {
		region_links.innerHTML = '';
		var mapLinks ="<iframe id='iframe-map' src='/mpq/map/" + location_id + "#snug' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
		region_links.innerHTML = mapLinks;
	}
}
window.show_map = show_map;

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

show_map(0);

$('input, textarea').placeholder();
$('#set_zips').removeClass("placeholder");


function init_iab_category_data()
{
	$('#iab_contextual_multiselect').select2({
		placeholder: "Select at least 3 audience interest channels",
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
					page_limit: 50,
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
					page_limit: 50,
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
	var location_id;

	if(location_collection.locations.length === 0)
	{
		location_collection.create_new_location();
		location_id = 0;
	}
	else
	{
		location_id = location_collection.get_current_location_id();
	}

	modify_region_ajax_queue.push({
		async: true,
		type: "POST",
		url: "/mpq_v2/get_zips_from_selected_regions_and_save",
		data:{
			custom_region_ids: record_ids,
			location_id: location_id,
			mpq_id: mpq_id
		},
		dataType: "json",
		success: function(data){
			var list_of_zipcodes = "";
			if(typeof data.session_expired !== "undefined" && data.session_expired === true)
			{
				if(!is_rfp)
				{
					$('#io_redirect_to_all_ios_modal').openModal();
					io_redirect_timeout_id = window.setTimeout(function(){
						redirect_to_all_ios('io_lock');
					}, 8000);
					stop_map_loading_gif();
				}
				else
				{
					Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
				}
			}
			else if(data.error == false)
			{
				$.each(data.response, function(index, value) {
					if(list_of_zipcodes != "")
					{
						list_of_zipcodes += ', ';
					}
					try
					{
						list_of_zipcodes += jQuery.parseJSON(value.regions);
					}
					catch(e)
					{
						list_of_zipcodes += value.regions;
					}
				});
				$('#set_zips').val(list_of_zipcodes);
				$('#set_zips_label').addClass('active');
				handle_set_zips('custom_regions', location_id);
				location_collection.set_has_custom_regions(true, location_id);
			}
			else
			{
				Materialize.toast("Server Error: " + data.error_text + " (#0831998)", 40000, 'toast_top');
			}
		},
		error: function(data) {
			Materialize.toast("Error 113466: Failed to retrieve desired custom regions", 40000, 'toast_top');
		}
	});
});


$("#known_zips_load_button").on("click", function(e){
	handle_set_zips(e);
});

$("#custom_regions_abandon_changes").on("click",  function(event){
	$('#known_zips_tab_anchor').tab('show');
});

init_iab_category_data();
init_custom_regions_data();

var vl_error_background_color_index = 0;
var vl_error_background_colors = new Array(
	"#ccf",
	"#fcc",
	"#cfc"
);

function vl_color_error_background(error)
{
	error.css('background-color', vl_error_background_colors[vl_error_background_color_index]);
	vl_error_background_color_index++;
	vl_error_background_color_index %= vl_error_background_colors.length;
}

function vl_get_error_header(header_extras)
{
	var d = new Date();
	var date_time = d.toLocaleString("en-US");

	var header_addition = $("<span />");
	if(typeof header_extras === 'string')
	{
		header_addition.
			text(header_extras).
			css({
				paddingLeft: "32px"
			});
	}

	var error_header = $("<div />").
		html("Error logged at: ("+date_time+")").
		append(header_addition).
		css({
			backgroundColor: "#000",
			color: "#fff"
		});
	return error_header;
}

function vl_hide_errors()
{
	$("#vl_errors_section").hide();
}

function vl_show_errors()
{
	$("#vl_errors_section").show();
	window.location.hash = "#";
	window.location.hash = "#vl_errors_section_bottom";
}

function vl_append_error(error, header_extras)
{
	vl_color_error_background(error);
	error.prepend(vl_get_error_header(header_extras));
	$("#vl_display_errors_div").append(error);
	vl_show_errors();
}

function vl_prepend_error(error, header_extras)
{
	vl_color_error_background(error);
	error.prepend(vl_get_error_header(header_extras));
	$("#vl_display_errors_div").prepend(error);
	vl_show_errors();
}

function vl_clear_errors()
{
	$("#vl_display_errors_div").empty();
	vl_hide_errors();
}

// evaluates whether the ajax json call succeded
function vl_is_ajax_call_success(data)
{
	var is_success = false;
	if(typeof data === 'object' &&
	   data !== null && // not null
	   typeof data['is_success'] !== 'undefined' &&
	   data.is_success == 1
	  )
	{
		is_success = true;
	}

	return is_success;
}


//	show error for ajax call failure
//		use in the ajax.error() function
function vl_show_jquery_ajax_error(jqXHR, textStatus, error)
{
	var html_string = '';
	if('responseText' in jqXHR)
	{
		html_string = jqXHR.responseText;
	}
	else if('responseXML' in jqXHR)
	{
		html_string = jqXHR.responseXML;
	}
	else
	{
		html_string = "unhandled error type (#547894)";
	}

	var error_div = $("<div />").html(html_string);
	vl_append_error(error_div, "ajax jquery function failure");
}

//	show errors when ajax call succeeds but returned data signals an error
//		use in the ajax.success() function
function vl_show_ajax_response_data_errors(data, additional_error_string)
{
	var error_div = $("<div />");
	if(typeof additional_error_string === 'string')
	{
		error_div.append($("<div />").html(additional_error_string));
	}
	else if(typeof additional_error_string === 'undefined')
	{
		// additional_error_string is optional
	}
	else
	{
		error_div.append($("<div />").html("ajax error handler error:  unexpected additional error type: '"+ typeof additional_error_string +"' only strings are handled"));
	}

	if(typeof data === 'object' &&
	   data !== null
	  )
	{
		var is_success_type = typeof data.is_success;
		if(is_success_type === 'number' ||
		   is_success_type === 'boolean')
		{
			// this is the expected situation
		}
		else if(is_success_type === 'undefined')
		{
			error_div.append($("<div />").html("ajax error handler error:  \"is_success\" property undefined, expected 'number' or 'boolean'"));
		}
		else
		{
			error_div.append($("<div />").html("ajax error handler error:  \"is_success\" property unexpected type: '"+is_success_type+"' expected 'number' or 'boolean'"));
		}

		var errors_type = typeof data.errors;

		if(errors_type === 'undefined')
		{
			error_div.append($("<div />").html("ajax error handler error:  \"errors\" array undefined, expected an array of strings"));
		}
		else if(errors_type === 'string')
		{
			error_div.append($("<div />").html(data.errors));
		}
		else if(Array.isArray(data.errors))
		{
			data.errors.forEach(function (element, index, array) {
				if(typeof element === 'string')
				{
					error_div.append($("<div />").html(element));
				}
				else
				{
					var element_type = typeof element;
					error_div.append($("<div />").html("ajax error handler error:  unexpected \"errors\" array element ["+index+"] type: '"+element_type+"' must be string"));

				}
			});
		}
		else
		{
			error_div.append($("<div />").html("ajax error handler error:  unexpected errors type: '"+errors_type+"' expected an array of strings"));
		}
	}
	else
	{
		error_div.append($("<div />").html("ajax error handler error:  json response must be an object with at least an \"is_success\" property and an \"errors\" property"));
	}

	vl_append_error(error_div, 'ajax response data errors');
}

$(document).ready(function() {

	$('#mpq_geo_search_type, #display_assign_creative_dropdown, .io_period_dropdown').material_select();

	$('.collapsible').collapsible({
		accordion: true
	});

	if(!$.isEmptyObject(rfp_account_executive_data))
	{
		$('#account_executive_select').select2('data', rfp_account_executive_data);
	}

	var last_account_executive = rfp_account_executive_data;

	if(!$.isEmptyObject(rfp_industry_data))
	{
		$('#industry_select').select2('data', rfp_industry_data);
	}

	if(rfp_is_preload == 1)
	{
		if(!$.isEmptyObject(rfp_iab_category_data))
		{
			$('#iab_contextual_multiselect').select2('data', rfp_iab_category_data);
		}
	}

	$('.modal-trigger').leanModal({
		dismissible: false
	});
});

$.extend($.expr[":"], {"containsIN":function(elem, i, match, array) {
	return (elem.value || elem.textContent || elem.innerText || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
}});

function Locations(max_locations)
{
	this.is_first_time = true;
	this.max_locations = max_locations;
	this.current_location_tab = 0;
	this.locations = [];

	this.initialize_locations_header();
}

(function() {

	var new_locations_dropdown_id = 'geo_add_location_dropdown_button';
	var add_new_location_button_id = 'geo_add_single_location';
	var add_bulk_locations_button_id = 'geo_add_bulk_locations';
	var multi_geos_search_box_input_id = 'search_location_list';
	var rename_location_class_id = 'location_rename_input';

	var bulk_upload_modal_id = 'bulk_locations_upload_modal';
	var bulk_locations_textarea_id = 'bulk_upload_input_field';
	var bulk_locations_submit_id = 'upload_bulk_locations_button';
	var bulk_locations_close_id = 'close_bulk_upload_modal_button';

	var locations_select_container_id = 'geo_location_dropdown_container';
	var locations_search_container_id = 'search_container_div';
	var locations_select_id = 'geo_location_dropdown';

	var location_creation_in_progress = false;
	var number_of_locations_needed_for_searchbar = 5;
	var num_populated_locations = 0;

	this.initialize_locations_with_existing_data = function()
	{
		var self = this;
		var locations_object = JSON.parse(rfp_raw_preload_location_object);
		$.each(locations_object, function(key, value){
			var location_name = value.user_supplied_name;
			var location_id = key;
			var active_string = "";
			var is_radius_search = false;
			var has_custom_regions = false;
			var custom_region_data = false;
			var geo_dropdown_options = {};
			var zip_code_list = value.ids.zcta.join(', ');
			var geo_input_id = "zip_list";
			if(value.search_type == "custom_regions")
			{
				custom_region_data = [];
				geo_input_id = "custom_region_list";
				has_custom_regions = true;
				$.each(rfp_custom_regions_data, function(regions_key, regions_value){
					if(regions_value.location_id == location_id)
					{
						custom_region_data.push({"id":regions_value.id, "text": regions_value.geo_name});
					}
				});
			}
			else if(value.search_type == "radius")
			{
				geo_input_id = "radius_search";
				is_radius_search = true;
				geo_dropdown_options.radius = value.counter;
				geo_dropdown_options.address = value.address;
			}

			var is_first_preload_location = false;

			if(location_id === 0)
			{
				is_first_preload_location = true;
			}
			var new_location = $('#location_' + location_id);
			var preload_location_data = {
				location_name: location_name,
				has_been_renamed: !!value.user_renamed,
				has_custom_regions: has_custom_regions,
				custom_region_data: custom_region_data,
				is_radius_search: is_radius_search,
				regions: zip_code_list,
				geo_dropdown_selection: geo_input_id,
				geo_dropdown_options: geo_dropdown_options
			};
			self.create_new_location(preload_location_data, is_first_preload_location);
		});
	}

	this.create_new_location = function(default_data, change_location)
	{
		var self = this;

		if(location_creation_in_progress == false)
		{
			location_creation_in_progress = true;
			if(this.locations.length < this.max_locations)
			{
				if(this.locations.length > 0 && !default_data)
				{
					this.save_location_content(this.current_location_tab);
					this.clear_location_data();
				}
				else if(this.locations.length === 0)
				{
					$("#" + new_locations_dropdown_id).off("click");
					$("#" + new_locations_dropdown_id).removeClass("modal-trigger");
					$("#" + new_locations_dropdown_id).removeAttr("data-target href");
					$("#" + new_locations_dropdown_id).removeData("data-target");

					$("#" + new_locations_dropdown_id).on("click", open_dropdown);
					$("#" + new_locations_dropdown_id + " .number_of_locations_span").show();
				}

				var new_id = this.locations.length;
				var new_name = "";
				var use_title = false;

				if (typeof this.locations[new_id] == 'undefined')
				{
					this.locations[new_id] = {
						location_name: new_name,
						has_been_renamed: false,
						has_custom_regions: false,
						custom_region_data: false,
						is_radius_search: false,
						regions: '',
						geo_dropdown_selection: '',
						geo_dropdown_options: {},
						has_been_populated: false
					};

					var should_change_location = (change_location === undefined) ? true : change_location;

					if(default_data)
					{
						$.extend(true, this.locations[new_id], default_data);
						if(this.locations[new_id].location_name !== '')
						{
							new_name = this.locations[new_id].location_name;
							use_title = true;
						}
						should_change_location = false || should_change_location;
						this.locations[new_id].has_been_populated = true;
					}
					else
					{
						initialize_new_location_in_db(this, new_id, this.locations[new_id].location_name);
					}

					var geofencing_icon_in_location = '';
					if(has_geofencing)
					{
						geofencing_icon_in_location = '<span class="geofencing_icon_io" style="width:3em">' +
							'<img src="/assets/img/geofence_icon.svg" />' +
							'<span class="geofence_count" style="width: 1em;">0</span>' +
						'</span>';
					}
					$("#" + locations_select_id).append(
						'<li id="location_' + new_id + '" style="overflow:hidden" class="collection-item multi_location_collection_item">' +
							'<div>' +
								'<span class="rename_link"><i class="material-icons">&#xE150;</i></span>' +
								'<span class="truncate">' +
									new_name +
								'</span>' +
								'<a class="secondary-content"><i class="material-icons">&#xE14C;</i></a>' +
								geofencing_icon_in_location +
							'</div>' +
						'</li>'
					);

					if(has_geofencing)
					{
						// Add listener for modal
						$(".geofencing_icon_io").on('click', function(e){
							// get location id
							var parent = $(this).parents("ul#geo_location_dropdown li");
							var location_id = $("#geo_location_dropdown li").index(parent);

							var can_set_geofence = location_collection.locations[location_id] &&
								location_collection.locations[location_id].has_been_populated;

							if(can_set_geofence)
							{
								// open modal
								$('#add_geofencing_modal').openModal();
								if(set_up_geofencing_modal)
								{
									set_up_geofencing_modal(mpq_id, location_id);
								}
							}
							else
							{
								Materialize.toast('Please choose a region before selecting geofence locations.', 10000, 'toast_top');
							}
						});
					}

					if(this.locations.length == number_of_locations_needed_for_searchbar)
					{
						display_searchbar();
					}

					if(should_change_location)
					{
						this.current_location_tab = new_id;
						this.location_selected(this.current_location_tab);
						this.populate_location_data(this.current_location_tab);
						$("#" + locations_select_id).scrollTop($("#" + locations_select_id).scrollTop() + $("li#location_" + this.current_location_tab).position().top);
					}

					var new_location = $("li#location_" + new_id);
					if(use_title)
					{
						new_location.prop('title', new_name);
					}

					initialize_new_location_events(this, new_location);
					if(this.locations[new_id].has_been_populated)
					{
						this.handle_populated_location_counter_and_span("add location");
					}
				}
				if (is_rfp === false && $('#mpq_flight_info_section, #mpq_creative_info_section').hasClass('hidden'))
				{
					region_ids = location_collection.locations.map(function(location, i){
						return i;
					});
					for (var id in io_product_info){
						if (io_product_info.hasOwnProperty(id)){
							populate_flight_geo_content(id, region_ids);
							populate_creative_geo_content(id, region_ids);
						}
					}
					$('#mpq_flight_info_section, #mpq_creative_info_section').removeClass('hidden');
				}
			}
			else
			{
				Materialize.toast('Ooops! You already have ' + this.max_locations + ' locations.<br>Remove some to add more.', 20000, 'toast_top');
			}

			location_creation_in_progress = false;
			if(is_rfp === true)
			{
				recalculate_totals();
			}
		}
	};

	this.handle_populated_location_counter_and_span = function(location_action)
	{
		if(location_action == "add location")
		{
			$("#" + new_locations_dropdown_id + " .number_of_locations_span").text(++num_populated_locations);
		}
		else if(location_action == "remove location")
		{
			$("#" + new_locations_dropdown_id + " .number_of_locations_span").text(--num_populated_locations);
		}
	};

	this.initialize_locations_header = function()
	{
		var self = this;

		if(location_creation_in_progress == false)
		{
			$("#" + add_new_location_button_id).on("click", function(event){
				locations_add_location(self);
			});

			$("#" + bulk_locations_submit_id).on("click", function(event){
				begin_bulk_upload_process(self);
			});

			$("#" + bulk_locations_close_id).on("click", function(event){
				$("#" + bulk_upload_modal_id).closeModal();
			});

			$("#" + bulk_upload_modal_id + " input[name='submission_type']").on("change", function(event){
				var type = $(event.target).val();
				$("#" + bulk_upload_modal_id + " blockquote").hide();
				$("#" + bulk_upload_modal_id + " #" + type + "_blockquote").show();
			});
		}
		if(rfp_is_preload != 0)
		{
			this.initialize_locations_with_existing_data();
		}
	}

	function display_searchbar()
	{
		$("#" + locations_search_container_id).show();
	}

	function begin_bulk_upload_process(self)
	{
		$("#" + bulk_locations_submit_id).off("click");
		self.bulk_upload_locations();
	}

	function disable_ui_for_bulk_upload()
	{
		$("#" + bulk_upload_modal_id).find("a").addClass("disabled");
		$("#" + bulk_upload_modal_id).find("a").removeClass("waves-effect");
		$("#" + bulk_upload_modal_id).find(".modal-action").off();
		$("#" + bulk_locations_close_id).off("click");
		$(".modal-content .progress").show();
	}

	function reenable_ui_after_bulk_upload(self)
	{
		$("#" + bulk_locations_submit_id).on("click", function(event){
			begin_bulk_upload_process(self);
		});

		$("#" + bulk_locations_close_id).on("click", function(event){
			$("#" + bulk_upload_modal_id).closeModal();
		});

		$("#" + bulk_upload_modal_id).find("a").removeClass("disabled");
		$("#" + bulk_upload_modal_id).find("a").addClass("waves-effect");
		$(".modal-content .progress").hide();
	}

	var are_bulk_upload_errors_present = false;
	var bulk_upload_errors = [];
	this.bulk_upload_locations = function()
	{
		var locations_textarea_content = $("#" + bulk_locations_textarea_id).val();
		var locations_arrays = parse_textarea_for_locations(locations_textarea_content.trim());
		var location_upload_type = $("#" + bulk_upload_modal_id).find("input[name='submission_type']:checked").val();

		var location_textarea_input_error_sentences = locations_textarea_content.split("\n");
		var bulk_upload_error_string = "";
		are_bulk_upload_errors_present = false;

		var self = this;

		if((locations_arrays.length + this.locations.length) > this.max_locations)
		{
			Materialize.toast('Ooops! You\'re about to upload more than ' + this.max_locations + ' locations.<br>Remove some to add more.', 20000, 'toast_top');
			$("#" + bulk_locations_submit_id).on("click", function(event){
				begin_bulk_upload_process(self);
			});
		}
		else if(locations_arrays.length < 1)
		{
			Materialize.toast('Ooops! Please add some locations, and try again.', 20000, 'toast_top');
			$("#" + bulk_locations_submit_id).on("click", function(event){
				begin_bulk_upload_process(self);
			});
		}
		else
		{
			disable_ui_for_bulk_upload();
			bulk_upload_errors = [];
			for(var i = 0; i < locations_arrays.length; i++)
			{
				modify_region_ajax_queue.push({
					url: '/maps/ajax_add_bulk_locations_to_session',
					dataType: 'json',
					async: true,
					type: "POST",
					data: {
						starting_location_id : this.locations.length + i,
						locations : JSON.stringify([locations_arrays[i]]),
						submission_type : location_upload_type,
						line_number : i
					},
					success: function(data, textStatus, xhr)
					{
						if(!vl_is_ajax_call_success(data))
						{
							vl_show_ajax_response_data_errors(data, 'Error uploading locations');
						}

						if(data.errors.length > 0)
						{
							bulk_upload_errors.push(data.errors.join(" "));
							are_bulk_upload_errors_present = true;
						}
						else
						{
							location_textarea_input_error_sentences[data.line_number] = "";
							var locations_to_populate = data.successful_locations;
							for(var j = 0; j < locations_to_populate.length; j++)
							{
								var show_this_location = false;
								self.create_new_location(locations_to_populate[j], show_this_location);
								handle_add_location_button_status(self);
							}
						}

						// when the queue is finished
						if(modify_region_ajax_queue.getQueued().length == 0 && modify_region_ajax_queue.getPending().length == 1)
						{
							if(self.locations.length > 0)
							{
								var current_location_id = self.locations.length - 1;
								self.populate_location_data(current_location_id);
								self.location_selected(current_location_id);
							}

							if(are_bulk_upload_errors_present)
							{
								var sentences_with_errors = location_textarea_input_error_sentences.filter(Boolean).join("\n");
								$("#" + bulk_locations_textarea_id).val(sentences_with_errors);
								Materialize.toast(
									'Ooops! These lines seem to have some errors: <br>' +
										bulk_upload_errors.join("<br>") + "<br>" +
										'Check that the correct upload method is selected, fix the lines, and click upload to finish.',
									20000, 'toast_top');
							}
							else
							{
								$("#" + bulk_locations_textarea_id).val("");
								$("#" + bulk_upload_modal_id).closeModal();
								open_dropdown();
								$("#" + locations_select_id).scrollTop($("#" + locations_select_id).scrollTop() + $("li#location_" + current_location_id).position().top);
							}

							reenable_ui_after_bulk_upload(self);
						}
					},
					error: function(xhr, textStatus, error)
					{
						vl_show_jquery_ajax_error(xhr, textStatus, error);
						ajax_call_in_progress = false;
					}
				});
			}
		}
	};

	this.location_selected = function(location_id)
	{
		$("#" + locations_select_id + " li").removeClass("selected_geo_location");
		$("#" + locations_select_id + " li:eq(" + location_id + ")").addClass("selected_geo_location");

		if(this.current_location_tab == location_id) return;

		var prev_tab = this.current_location_tab;
		this.save_location_content(prev_tab);
		this.clear_location_data();

		this.current_location_tab = location_id;
		this.populate_location_data(location_id);
	};

	this.save_location_content = function(location_id)
	{
		this.locations[location_id].regions = $("textarea#set_zips").val();
		this.locations[location_id].custom_region_data = $("#s2id_custom_regions_multiselect").select2("data");
		this.locations[location_id].geo_dropdown_selection = $("#mpq_geo_search_type").get(0).value;

		this.locations[location_id].geo_dropdown_options.radius = $("#radius").val();
		this.locations[location_id].geo_dropdown_options.address = $("#address").val();
	};

	this.populate_location_data = function(location_id)
	{
		var data = this.locations[location_id].hasOwnProperty('custom_region_data') ? this.locations[location_id].custom_region_data : false;
		if(data !== false && data.length > 0)
		{
			$("#s2id_custom_regions_multiselect").select2("data", data);
		}
		$("textarea#set_zips").val(this.locations[location_id].regions);
		$("#set_zips_label").addClass("active");

		var selected_dropdown_value = this.locations[location_id].geo_dropdown_selection;
		if(selected_dropdown_value !== '')
		{
			this.handle_geo_input_section(selected_dropdown_value);

			for(var dom_id in this.locations[location_id].geo_dropdown_options)
			{
				if (this.locations[location_id].geo_dropdown_options.hasOwnProperty(dom_id))
				{
					var value = this.locations[location_id].geo_dropdown_options[dom_id];
					$("#" + dom_id).val(value);
				}
			}
		}
		show_map(location_id);
	};

	this.handle_geo_input_section = function(dropdown_value)
	{
		var dropdown_title = $("#mpq_geo_search_type [value='" + dropdown_value + "']").text();
		$("#mpq_geo_search_type option:contains('" + dropdown_title + "')").prop("selected", true);
		$("#mpq_geo_search_type").material_select();
		$(".search_box_pane").hide();
		$("#" + dropdown_value).show();
	};

	this.clear_location_data = function()
	{
		$("#s2id_custom_regions_multiselect").select2("data", "");
		$("textarea#set_zips").val("");
		$("#radius").val("");
		$("#address").val("");
	};

	var currently_renaming = false;
	this.rename_location_in_ui = function(location_id, new_location_name, renamed_by_user)
	{
		if(renamed_by_user || !this.locations[location_id].has_been_renamed)
		{
			this.locations[location_id].location_name = new_location_name;
			this.locations[location_id].has_been_renamed = !!(this.locations[location_id].has_been_renamed || renamed_by_user);

			var location_element = $("#location_" + location_id);

			if(renamed_by_user)
			{
				location_element.find("div input").remove();
				var self = this;
				location_element.find("span.rename_link").off("click");
				location_element.find("span.rename_link").on("click", function(event){
					rename_functionality(event, self, location_element);
				});
				location_element.find("div").removeClass("input-field");
				location_element.find("div span").show();
			}

			location_element.find("div span.truncate").text(new_location_name);
			location_element.prop("title", new_location_name);
		}
	};

	this.get_location_name = function(location_id)
	{
		return this.locations[location_id].location_name;
	};

	this.set_has_custom_regions = function(has_custom_regions, location_id)
	{
		var id = (location_id === undefined) ? this.current_location_tab : location_id;
		this.locations[id].has_custom_regions = has_custom_regions;
	};

	this.get_has_custom_regions = function(location_id)
	{
		var id = (location_id === undefined) ? this.current_location_tab : location_id;
		return this.locations[location_id].has_custom_regions;
	};

	this.get_current_location_id = function()
	{
		return this.current_location_tab;
	};

	this.set_is_radius_search = function(is_radius_search, location_id, radius, address)
	{
		var id = (location_id === undefined) ? this.current_location_tab : location_id;
		if(is_radius_search)
		{
			this.locations[id].geo_dropdown_options.radius = radius.toString();
			this.locations[id].geo_dropdown_options.address = address.toString();
		}
		else
		{
			this.locations[id].geo_dropdown_options.radius = '';
			this.locations[id].geo_dropdown_options.address = '';
		}
		this.locations[id].is_radius_search = !!is_radius_search;
	};

	function handle_add_location_button_status(self)
	{
		if(self.locations.length == self.max_locations)
		{
			$("#" + add_new_location_button_id).addClass("disabled");
			$("#" + add_new_location_button_id).off("click");
		}
		else
		{
			$("#" + add_new_location_button_id).removeClass("disabled");
			$("#" + add_new_location_button_id).off("click");
			$("#" + add_new_location_button_id).on("click", function(e){
				locations_add_location(self);
			});
		}
	}

	function initialize_new_location_events(self, new_location)
	{
		// Change everything to onchange stuff for select2, never mind
		new_location.on("click", function(event){
			var location_id = $("#geo_location_dropdown li").index(this);
			self.location_selected(location_id);
		});

		// Remove location
		new_location.find("a.secondary-content").on("click", function(event){
			event.stopImmediatePropagation();
			var parent = $(this).parents("ul#geo_location_dropdown li");
			var location_id = $("#geo_location_dropdown li").index(parent);
			locations_remove_location(self, location_id);
		});

		// Rename location
		new_location.find("span.rename_link").on("click", function(event){
			rename_functionality(event, self, new_location);
		});
	}

	this.call_remove_location_from_db = function(location_id)
	{
		remove_location_from_db(this, location_id);
	};

	function rename_functionality(event, self, new_location)
	{
		new_location.find("span.rename_link").off("click");
		var parent = $(event.target).parents("ul#geo_location_dropdown li");
		var location_id = parent.index();

		// Set up rename location stuff goes here
		var name_div = new_location.find("div");
		name_div.addClass("input-field");
		name_div.find("span").hide();

		name_div.prepend("<input type='text' value='" + self.locations[location_id].location_name + "'>");
		name_div.find("input").focus(function(){
			this.select();
		});
		name_div.find("input").focus();

		// Add rename on input blur (this now gets intialized in the other rename function as behavior on input)
		name_div.find("input").on("blur", function(event){

			var new_location_name = $(event.target).val();

			// Send to the rename function
			var manually_renamed = true;
			rename_and_save_location_name_to_db(self, location_id, new_location_name, manually_renamed);

		});

		name_div.find("input").on("keypress", function(event){
			if(event.which == 13)
			{
				name_div.find("input").blur();
			}
		});
	}

	function initialize_new_location_in_db(self, location_id, location_name)
	{
		modify_region_ajax_queue.push({
			url: '/mpq/initialize_new_location',
			dataType: 'json',
			async: true,
			type: "POST",
			data: {
				location_id : location_id,
				location_name : location_name,
				mpq_id: mpq_id
			},
			success: function(data, textStatus, xhr)
			{
				if(typeof data.session_expired !== "undefined" && data.session_expired !== false)
				{
					if(!is_rfp)
					{
						$('#io_redirect_to_all_ios_modal').openModal();
						io_redirect_timeout_id = window.setTimeout(function(){
							redirect_to_all_ios('io_lock');
						}, 8000);
					}
					else
					{
						Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
					}
				}
				else
				{
					if(!vl_is_ajax_call_success(data))
					{
						vl_show_ajax_response_data_errors(data, 'error initalizing location');
					}

					if(is_rfp == false)
					{
						io_reset_creatives_flights();
					}

					handle_add_location_button_status(self);
				}
			},
			error: function(xhr, textStatus, error)
			{
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			}
		});
	}

	function remove_location(self, location_id)
	{
		var old_length = self.locations.length;

		if(self.locations[location_id].has_been_populated)
		{
			self.handle_populated_location_counter_and_span("remove location");
		}

		// Remove from geo_locations_info_array
		self.locations.splice(location_id, 1);

		// Remove element
		$("#location_" + location_id).remove();

		// Change current tab if current tab was removed or if the current tab's id changed
		if(self.current_location_tab >= location_id)
		{
			var new_location_tab = (self.current_location_tab == 0) ? 0 : self.current_location_tab - 1;
			if(self.current_location_tab == location_id) self.populate_location_data(new_location_tab);

			self.current_location_tab = new_location_tab;
		}

		handle_add_location_button_status(self);

		if(is_rfp === true)
		{
			recalculate_totals();
		}
		else
		{
			io_reset_creatives_flights();
		}

		self.location_selected(self.current_location_tab);
		$("#" + new_locations_dropdown_id + " .number_of_locations_span").text(self.locations.length);

		if(location_id == (old_length - 1))
		{
			return;
		}

		// Shift ids from other <li>s, iterate through with counter and rename when needed
		var li_array = $("#" + locations_select_id + " li");
		for(var i = location_id; i < li_array.length; i++)
		{
			$("#" + locations_select_id + " li")[i].id = "location_" + i;
		}
	}

	function remove_location_from_db(self, location_id)
	{
		if(self.locations.length > 1)
		{
			modify_region_ajax_queue.push({
				url: '/mpq/remove_location',
				dataType: 'json',
				async: true,
				type: "POST",
				data: {
					location_id : location_id,
					mpq_id: mpq_id
				},
				success: function(data, textStatus, xhr)
				{
					if(typeof data.session_expired !== "undefined" && data.session_expired !== false)
					{
						if(!is_rfp)
						{
							$('#io_redirect_to_all_ios_modal').openModal();
							io_redirect_timeout_id = window.setTimeout(function(){
								redirect_to_all_ios('io_lock');
							}, 8000);
						}
						else
						{
							Materialize.toast('Notice: The form has expired.  Please refresh to continue creating your proposal.', 80000, 'toast_top');
						}
					}
					else if(vl_is_ajax_call_success(data))
					{
						remove_location(self, location_id);
						remove_geofences_of_removed_location_from_frontend_array(location_id);
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'error removing tab');
					}
					ajax_call_in_progress = false;
				},
				error: function(xhr, textStatus, error)
				{
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					ajax_call_in_progress = false;
				}
			});
		}
		else
		{
			Materialize.toast('Ooops! You need at least one location.', 20000, 'toast_top');
		}
	}

	function rename_and_save_location_name_to_db(self, location_id, new_location_name, manually_renamed)
	{
		if((new_location_name == self.locations[location_id].location_name))
		{
			self.rename_location_in_ui(location_id, new_location_name, true);
		}
		else if(new_location_name == "")
		{
			self.rename_location_in_ui(location_id, self.locations[location_id].location_name, true);
		}
		else
		{
			modify_region_ajax_queue.push({
				url: '/mpq/save_location_name',
				dataType: 'json',
				async: true,
				type: "POST",
				data: {
					location_id : location_id,
					location_name : new_location_name,
					manually_renamed: !!manually_renamed
				},
				success: function(data, textStatus, xhr)
				{
					if(vl_is_ajax_call_success(data))
					{
						self.locations[location_id].location_name = new_location_name;
						self.rename_location_in_ui(location_id, new_location_name, true);
					}
					else
					{
						vl_show_ajax_response_data_errors(data, 'saving location name failed');
					}
					currently_renaming = false;
					ajax_call_in_progress = false;
				},
				error: function(xhr, textStatus, error)
				{
					vl_show_jquery_ajax_error(xhr, textStatus, error);
					currently_renaming = false;
					ajax_call_in_progress = false;
				}
			});
		}
	}

	function parse_textarea_for_locations(locations)
	{
		var location_array = locations.split('\n');
		var filtered = location_array.filter(Boolean);

		for(var i = 0; i < filtered.length; i++)
		{
			filtered[i] = filtered[i].split(';');
		}
		return filtered;
	}

	function close_dropdown(event)
	{
		if(event)
		{
			event.stopPropagation();

			var container = $("#geo_location_menu_container")[0];
			var target = event.target;
			if(container === target || $.contains(container, target))
			{
				return true;
			}

			$(document).off("click", close_dropdown);
		}

		$("#" + new_locations_dropdown_id + " .number_of_locations_span").show();
		$("#geo_location_menu_container").animate({opacity:0,right:'-30px'}, function() { $(this).hide(); });
		$("#" + new_locations_dropdown_id).addClass("btn");
		$("#" + new_locations_dropdown_id).removeClass("btn-flat");

		$("#" + new_locations_dropdown_id).on("click", open_dropdown);
	}

	function open_dropdown(event)
	{
		if(event)
		{
			event.stopPropagation();
		}
		$("#" + new_locations_dropdown_id).off("click", open_dropdown);
		$(document).on("click", close_dropdown);

		$("#" + new_locations_dropdown_id + " .number_of_locations_span").hide();
		$("#geo_location_menu_container").css({opacity:0,right:'-30px'}).show().animate({opacity:1,right:'0px'});
		$("#" + new_locations_dropdown_id).addClass("btn-flat");
		$("#" + new_locations_dropdown_id).removeClass("btn");
	}

	$("#" + multi_geos_search_box_input_id).keyup(function(){
		$(".multi_location_collection_item").hide();
		var value = $(this).val();
		$(".multi_location_collection_item:containsIN('" + value + "')").show();
	});

	$("#" + bulk_locations_textarea_id).on('change input paste keyup', function(){
		$(this).val($(this).val().replace(/\t/gi, ';'));
	})

	$("#" + bulk_locations_textarea_id).keydown(function(e) {
		if(e.keyCode === 9)
		{
			// get caret position/selection
			var start = this.selectionStart;
			var end = this.selectionEnd;

			var $this = $(this);
			var value = $this.val();

			// set textarea value to: text before caret + tab + text after caret
			$this.val(value.substring(0, start) + "\t" + value.substring(end));

			// put caret at right position again (add one for the tab)
			this.selectionStart = this.selectionEnd = start + 1;

			// prevent the focus lose
			e.preventDefault();
		}
	});

}).call(Locations.prototype);

var modify_region_ajax_queue = new AjaxQueue();
var geo_max_locations = (typeof max_locations_for_rfp !== "undefined" && !isNaN(max_locations_for_rfp)) ? max_locations_for_rfp : 200;
var location_collection = new Locations(geo_max_locations);

$('#industry_select').select2({
	width: '100%' ,
	placeholder: "Advertiser Industry",
	minimumInputLength: 0,
	multiple: false,
	allowClear: true,
	ajax: {
		url: "/mpq_v2/get_industries/",
		type: 'POST',
		dataType: 'json',

		data: function (term, page) {
			term = (typeof term === "undefined" || term == "") ? "%" : term;
			strategy_id = $('input[name="strategy_id"]').val();
			return {
				q: term,
				page_limit: 50,
				page: page,
				strategy_id: strategy_id
			};
		},
		results: function (data) {
			return {results: data.results, more: data.more};
		}
	}
});

$('#account_executive_select').select2({
	width: '100%',
	placeholder: "Presented on Behalf of",
	minimumInputLength: 0,
	multiple: false,
	ajax: {
		url: "/mpq_v2/get_account_executives_for_rfp/",
		type: "POST",
		dataType: "json",
		data: function(term, page) {
			term = (typeof term === "undefined" || term == "") ? "%" : term;
			return {
				q: term,
				page_limit: 50,
				page: page
			};
		},
		results: function (data) {
			return {results: data.results, more: data.more};
		}
	},
	formatResult: function(data) {
		return '<small class="grey-text">'+data.email+'</small>'+
			'<br>'+data.text;
	}
});

function number_with_commas(number)
{
	var number = parseFloat(number.toString().replace(/,/g, '')).toString();
	var number_parts = number.split(".");
	number_parts[0] = number_parts[0].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	return number_parts.join(".");
}

function encode_demographic_data()
{
	var demographic_array = [
		$('#gender_male_channel').prop('checked'),
		$('#gender_female_channel').prop('checked'),
		$('#age_under_18_channel').prop('checked'),
		$('#age_18_to_24_channel').prop('checked'),
		$('#age_25_to_34_channel').prop('checked'),
		$('#age_35_to_44_channel').prop('checked'),
		$('#age_45_to_54_channel').prop('checked'),
		$('#age_55_to_64_channel').prop('checked'),
		$('#age_over_65_channel').prop('checked'),
		$('#income_under_50k_channel').prop('checked'),
		$('#income_50k_to_100k_channel').prop('checked'),
		$('#income_100k_to_150k_channel').prop('checked'),
		$('#income_over_150k_channel').prop('checked'),
		$('#education_no_college_channel').prop('checked'),
		$('#education_college_channel').prop('checked'),
		$('#education_grad_school_channel').prop('checked'),
		$('#parent_no_kids_channel').prop('checked'),
		$('#parent_has_kids_channel').prop('checked')
	];
	var demographic_string = "";
	for(var i = 0; i < demographic_array.length; i++)
	{
		if(typeof demographic_array[i] !== "undefined" && demographic_array[i] == true)
		{
			demographic_string += "1";
		}
		else
		{
			demographic_string += "0";
		}
		demographic_string += "_";
	};
	demographic_string += "1_1_1_1_1_75_All_unusedstring";
	return demographic_string;
}

function encode_political_data()
{
	var segment_data = [];
	if ($("#political_segments").is(":visible")){
		$("#political_segments input").each(function(i,val){
			segment_data.push({
				'name': $(this).attr('id'),
				'value': $(this).prop('checked')
			});
		});
	}
	return segment_data;
}

function encode_iab_category_data()
{
	var raw_channel_data = $('#iab_contextual_multiselect').select2('val');
	return JSON.stringify(raw_channel_data);
}

function validate_opportunity_details()
{
	var org_name = $('#mpq_org_name').val();
	var website  = $('#mpq_website_name').val();
	var industry = $('#industry_select').val();
	var presented_user = $('#account_executive_select').val();
	var is_success = true;

	if(!validate_text_name(org_name))
	{
		$('#mpq_org_name').addClass('invalid');
		is_success = false;
	}
	if(!validate_website(website))
	{
		$('#mpq_website_name').addClass('invalid');
		is_success = false;
	}
	if(!validate_select2_dropdown(industry))
	{
		is_success = false;
	}
	if(!validate_select2_dropdown(presented_user))
	{
		is_success = false;
	}
	return is_success;
}

function validate_geography(obj)
{
	if(location_collection.locations.length > 0)
	{
		$.ajax({
			type: "POST",
			url: '/mpq/check_rfp_session',
			async: false,
			data: {},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success)
				{
					obj.is_success = true;
				}
				else
				{
					obj.is_success = false;
				}
			},
			error: function(jqXHR, textStatus, error){
				obj.is_success = false;
			}
		});

	}
	else
	{
		obj.is_success = false;
	}
}

function validate_demographics()
{
	var num_demographics = $('#mpq_audience_info_section #demographic_inputs input[type=checkbox]').length;
	if(num_demographics == 18)
	{
		return true;
	}
	return false;
}

function validate_iab_categories()
{
	var iab_value = $('#iab_contextual_multiselect').select2('data');
	if((typeof iab_value !== "undefined" && iab_value.length > 2) || $('#mpq_audience_info_section').css('display') === 'none')
	{
		return true;
	}
	return false;
}

function validate_email_address(email)
{
	var email_regex = new RegExp(/^.+@.+[\.].+$/);
	if(email_regex.test(email))
	{
		return true;
	}
	return false;
}

function validate_select2_dropdown(value)
{
	if(value == "")
	{
		return false;
	}
	return true;
}

function validate_text_name(name)
{
	if(name == "" || name.length > 255)
	{
		return false;
	}
	return true;
}

function validate_website(website)
{
	var website_regex = new RegExp(/^.+[\.].+$/);
	if(!website_regex.test(website) || website.length > 255)
	{
		return false;
	}
	return true;
}

function revert_account_executive_dropdown()
{
	$('#account_executive_select').select2('data', last_account_executive);
}

$('#rfp_account_executive_cancel').click(function(e){
	e.preventDefault();
});

$('#rfp_account_executive_reload').click(function(e){
	e.preventDefault();
	$('#rfp_with_new_account_executive > input').val($('#account_executive_select').val());
	$('#rfp_with_new_account_executive').submit();
});

$('#mpq_geo_search_type').change(function() {
	$('.search_box_pane').hide();
	$('#'+this.value).show();
});

function locations_remove_location(self, location_id)
{
	if(is_rfp == false)
	{
		var toast_content =
			'Reminder: removing a location will reset the flights and creatives below - be sure to re-enter them after making your geo changes.'+
			'<button id="remove_location_toast_ok_'+location_id+'" style="background-image: none;margin:0px 20px; padding:0 1rem;" class="btn waves-effect waves-light green darken-3" type="button"><i class="material-icons">&#xE876;</i></button>' +
			'<button class="btn waves-effect waves-light red darken-4" style="background-image: none; padding:0 1rem;" type="button" onclick="document.getElementById(\'toast-container\').parentElement.removeChild(document.getElementById(\'toast-container\'));"><i class="material-icons">&#xE033;</i></button>';
		Materialize.toast(toast_content, 999999999, 'toast_top', '', 'Please confirm:');

		$('#remove_location_toast_ok_'+location_id).click(function(e){
			e.preventDefault();
			self.call_remove_location_from_db(location_id);
			$('#toast-container').remove();
		});
	}
	else
	{
		self.call_remove_location_from_db(location_id);
	}
}

function locations_add_location(self)
{
	if(is_rfp == false)
	{
		var toast_content =
			'Reminder: adding a location will reset the flights and creatives below - be sure to re-enter them after making your geo changes.'+
			'<button id="add_location_toast_ok" style="background-image:none;margin:0px 20px;padding:0 1rem;" class="btn waves-effect waves-light green darken-3" type="button"><i class="material-icons">&#xE876;</i></button>' +
			'<button class="btn waves-effect waves-light red darken-4" type="button" style="padding:0 1rem; background-image:none;" onclick="document.getElementById(\'toast-container\').parentElement.removeChild(document.getElementById(\'toast-container\'));"><i class="material-icons">&#xE033;</i></button>';
		Materialize.toast(toast_content, 150000, 'toast_top', '', 'Please confirm:');

		$('#add_location_toast_ok').click(function(e){
			e.preventDefault();
			self.create_new_location();

			$('#toast-container').remove();
		});
	}
	else
	{
		self.create_new_location();
	}
}

