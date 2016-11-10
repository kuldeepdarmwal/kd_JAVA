var is_form_submitting_flag = false;
var io_side_nav_icons = {
	'done': {
		'io_class': 'io_icon_done',
		'io_icon': '&#xE8E8;',
	},
	'in_progress': {
		'io_class': 'io_icon_in_progress',
		'io_icon': '&#xE01B;',
	},
	'not_started': {
		'io_class': 'io_icon_not_started',
		'io_icon': '&#xE002;'
	},
	'on_hold': {
		'io_class': 'io_icon_on_hold',
		'io_icon': '&#xE924;'
	}
};

var io_side_nav_items = {
	'opportunity': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Opportunity'
	},
	'product': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Product'
	},
	'geo': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Geo'
	},
	'audience': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Audience'
	},
	'tracking': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Tracking'
	},
	'flights': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Flights'
	},
	'creative': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Creative'
	},
	'notes': {
		'status': 'not_started',
		'active': false,
		'friendly': 'Notes'
	}

};

var io_flights = {
	'selected_product': "",
	'products': {}
};

var io_creatives = {
	'selected_product': "",
	'products': {}
};

var io_redirect_timeout_id;

var io_dfp_advertiser_create_jqxhr = null;

if(rfp_is_preload == "1")
{
	var flight_builder_array = {};
	var creative_builder_array = {};
	var loc_keys = Object.keys(location_collection.locations);
	$.each(io_product_info, function(key, value){
		if(value.can_become_campaign == 1)
		{
			io_flights.products[key] = [false];
			io_creatives.products[key] = [false];
			$.each(loc_keys, function(key_2, value_2){
				var temp_flight_status = false;
				var temp_creative_status = 0;
				$.each(io_flights_data, function(key_3, value_3){
					if(value_3.product_id == key && value_3.region_id == key_2)
					{
						if(!flight_builder_array.hasOwnProperty(key))
						{
							flight_builder_array[key] = [];
						}
						flight_builder_array[key].push(key_2);
						temp_flight_status = true;
						return false;
					}
				});
				$.each(io_creatives_data, function(key_3, value_3){
					if(value_3.product_id == key && value_3.region_id == key_2)
					{
						if(!creative_builder_array.hasOwnProperty(key))
						{
							creative_builder_array[key] = [];
						}
						creative_builder_array[key].push(key_2);
						//If status
						temp_creative_status = value_3.creative_status;
						return false;
					}
				});

				io_flights.products[key][key_2] = temp_flight_status;
				io_creatives.products[key][key_2] = temp_creative_status;
			});
		}
	});
	$.each(flight_builder_array, function(key, value){
		if($.isArray(value) && value.length > 0)
		{
			var show_generic = true;
			$.each(time_series_data, function(ts_key, ts_value){
				if(ts_value.product_id == key)
				{
					show_generic = false;
					return false;
				}
			});
			$('#flights_product_'+key+' .io_flights_defined_text .io_flights_defined_header_content').hide();

			if(show_generic)
			{
				$('#flights_product_'+key+' .io_flights_defined_text .io_all_flights_defined').show();
			}
			else
			{
				$('#flights_product_'+key+' .io_flights_defined_text .io_all_flights_defined_with_data').show();
			}
			populate_flight_geo_content(key, value);
		}
	});

	$.each(io_flights_data, function(i, flight){
		update_flight_region_summary(flight.start_date, flight.end_date, flight.impressions, flight.product_id, flight.region_id);

		if(io_product_info[flight.product_id].o_o_enabled  == 1 && io_submit_allowed == 1 && io_product_info[flight.product_id].banner_intake_id == "Display")
		{
			if(flight.o_o_percent != null || flight.o_o_ids != null)
			{
				fill_flight_o_o_fields(flight.product_id, flight.region_id, flight.o_o_percent, flight.o_o_ids);
			}
			else
			{
				if(flight.o_o_percent == null)
				{
					save_o_o_ratio(flight.product_id, flight.region_id, io_product_info[flight.product_id].o_o_default_ratio)
				}
			}
		}
	});

	$.each(creative_builder_array, function(key, value){
		if($.isArray(value) && value.length > 0)
		{
			determine_product_status_for_product(key);
			populate_creative_geo_content(key, value);
		}
	});
}

var io_selected_submitted_product = false;
var io_selected_product_id = false;
var io_selected_product_geo_index = false;

$('#io_save_button').click(function(e){
	e.preventDefault();
	check_io_in_use().success(function(data){
		io_save_submit('save');
	});
});

$('#mpq_product_info_section img.targeted_image').click(function(e){
	var parent = $(this).closest('.io_product_container');
	var checkbox_state = !$(parent).find('input[type="checkbox"]').prop('checked');
	change_checkbox(parent, checkbox_state);
	confirm_product_change(parent.data('product-id'));
});

$('#mpq_product_info_section input[type=checkbox]').click(function(e){
	var parent = $(this).closest('.io_product_container');
	var checkbox_state = $(this).prop('checked');
	change_checkbox(parent, checkbox_state);
	confirm_product_change(parent.data('product-id'));
});

function change_checkbox(parent, checkbox_state)
{
	var img = $(parent).find('.product_header_img img');
	var checkbox = $(parent).find('input[type=checkbox]');

	$(checkbox).prop('checked', checkbox_state);
	$(img).attr('src', $(img).attr(checkbox_state ? 'data-enabled-img' : 'data-disabled-img'));
}

function confirm_product_change(product_id)
{
	$('#io_product_change input[name="product_id"]').val(product_id);
	$('#io_product_change input[name="product_status"]').val($('#product_'+product_id).prop('checked'));
	$('#io_product_change_confirm').openModal({
		dismissible: false,
		complete: function() {
			$('#io_product_change input[name="product_id"]').val('');
			$('#io_product_change input[name="product_status"]').val('');
			change_checkbox($('#product_container_'+product_id), !$('#product_'+product_id).prop('checked'));
		}
	});
}

$('#io_product_change_reload').on('click', function(e){
	e.preventDefault();
	if ($('#io_product_change input[name="product_id"]').val() !== '' && $('#io_product_change input[name="product_status"]').val() !== '')
	{
		$('#io_product_change').submit();
	}
});

function get_updated_io_creatives_data()
{
	$.ajax({
		type: "POST",
		url: '/mpq_v2/get_creatives_info',
		async: true,
		data:
		{
			mpq_id:mpq_id
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				io_creatives_data = data.creatives_data;
				var loc_keys = Object.keys(location_collection.locations);

				$.each(io_product_info, function(key, value){
					if(value.can_become_campaign == 1)
					{
						$.each(loc_keys, function(key_2, value_2){
							$.each(io_creatives_data, function(key_3, value_3){
								if(value_3.product_id == key && value_3.region_id == key_2)
								{
									if(!creative_builder_array.hasOwnProperty(key))
									{
										creative_builder_array[key] = [];
									}
									creative_builder_array[key].push(key_2);
									return false;
								}
							});
						});
					}
				});


				$.each(creative_builder_array, function(key, value){
					if($.isArray(value) && value.length > 0)
					{
						populate_creative_geo_content(key, value);
					}
				});
			}
			else
			{
				Materialize.toast('Ooops! There was a problem showing landing pages for the creatives : <br>- '+data.errors, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem showing landing pages for the creatives.', 46000, 'toast_top');
		}
	});
}

function io_validate()
{
	var text_response = "";
	if(!validate_opportunity_details_for_io())
	{
		text_response += "<br>- Please ensure the information about your advertiser is correct";
	}
	var geo_success = {'is_success': false};
	validate_geography(geo_success);
	if(!geo_success.is_success)
	{
		text_response += "<br>- Please complete the geography section";
	}
	if(!validate_demographics())
	{
		text_response += "<br>- Please make sure the demographics you provided are correct";
	}
	if(!validate_iab_categories())
	{
		text_response += "<br>- Please provide at least three contextual channels";
	}
	if(!validate_flights())
	{
		text_response += "<br>- Please complete the flights section";
	}
	if(!validate_creative())
	{
		text_response += "<br>- Please complete the creatives section";
	}


	return text_response;
}

var placecomplete_object = {
	placeholder: "Select the target location...",
	minimumInputLength: 3,
	maximumSelectionSize: 1,
	multiple: true,
	allowClear: true,
	width: "100%",
	requestParams: {
		types: ["geocode", "establishment"],
		componentRestrictions: {country : "us"}
	}
};

var geofencing_locations_array =
[
	// [ // location_id is array index
	//	{
	// 		address: x,
	// 		proximity_radius: x,
	// 		conquesting_radius: x,
	// 		latlng: [y, x],
	// 		type: x,
	// 		radius_options: ["proximity", "conquesting"]
	// 	}, // point_data
	// 	{more_points},
	// ],
	// {etc}
];

var geofencing_temp_locations_changes_array = [];

function set_up_geofencing_modal(mpq_id, location_id, etc)
{
	var location_name = location_collection.locations[location_id].location_name;
	var current_geofences = JSON.parse(JSON.stringify(geofencing_locations_array[location_id] || [])); // Make a copy of the array of objects
	geofencing_temp_locations_changes_array = current_geofences;

	$('#add_geofencing_modal span.geofence_modal_name').text(location_name);
	var geofencing_form_html = "";
	$('#add_geofencing_modal form').html(geofencing_form_html);
	if(current_geofences && current_geofences.length)
	{
		var map_bounds = document.getElementById('iframe-map').contentWindow.map.getBounds();
		var map_bounds_literal = {
			east: map_bounds.getNorthEast().lng(),
			west: map_bounds.getSouthWest().lng(),
			north: map_bounds.getNorthEast().lat(),
			south: map_bounds.getSouthWest().lat()
		};
		placecomplete_object.requestParams.bounds = map_bounds_literal;
		for(var i = 0; i < current_geofences.length; i++)
		{
			var cur_geo = current_geofences[i];
			var dropdown_options = cur_geo.dropdown_options;
			var dropdown_options_html = '';
			for(var dropdown_index in dropdown_options)
			{
				var radius = dropdown_options[dropdown_index];
				var selected = (cur_geo.type == radius.value) ? "selected" : "";
				dropdown_options_html += '<option ' + selected + ' value="' + radius.value + '">' + radius.text + '</option>';
			}

			var preload_row =
				'<div class="row">' +
					'<div class="input-field col s7">' +
						'<input type="text" value="' + cur_geo.address + '" class="geofence_place_complete geofence_address" />' +
					'</div>' +
					'<div class="input-field col s2">' +
						'<select id="geofence_radius_' + i + '" class="grey-text material_select" >' +
							dropdown_options_html +
						'</select>' +
						'<label for="geofence_radius_' + i + '">Radius</label>' +
					'</div>' +
					'<div class="col s2 grey-text">' +
						'<span class="radius_text">' + cur_geo.radius + 'm radius</span>' +
					'</div>' +
					'<div class="col s1">' +
						'<button type="button" data-location-id="' + location_id + '" data-geofence-index="' + i + '" class="btn remove-geofence">' +
							'<i data-time-icon="icon-remove" class="icon-remove icon-white"></i>' +
						'</button>' +
					'</div>' +
				'</div>';

			$('#add_geofencing_modal form').append(preload_row);

			$("#add_geofencing_modal form div.row:last .geofence_place_complete").placecomplete(placecomplete_object);
			$("#add_geofencing_modal form div.row:last .geofence_place_complete").on("change", function(e){
				if(e.added && e.added.description)
				{
					var address = e.added.description;
					get_radius_info_for_geofence_address(address, location_id, Number($("#add_geofencing_modal div.row").index($(this).parents(".row"))));
				}
			});
			$('#add_geofencing_modal .row select').last().on('change', handle_radius_change);
			$('#add_geofencing_modal .row select').last().material_select();
			$('#add_geofencing_modal .row select').last().change();
		}
	}

	var add_geofence_row =
		'<div class="row">' +
			'<div class="input-field col s4">' +
				'<button class="btn primary add_geofence" type="button">+ Add Geofence</button>' +
			'</div>' +
		'</div>';

	$('#add_geofencing_modal form').append(add_geofence_row);
	$('#add_geofencing_modal form .add_geofence').off('click').on('click', function(e){
		add_geofence_function(location_id);
	});
	$('#add_geofencing_modal .save_geofence_points').off('click').on('click', function(e){
		show_save_loading_button();
		handle_save_geofencing_points(location_id);
	});
	$('#add_geofencing_modal form .remove-geofence').off('click').on('click', function(e){
		var location_id = Number($(this).data('location-id'));
		var geofence_index = Number($(this).data("geofence-index"));
		remove_geofence_function(location_id, geofence_index);
	});
}

function remove_geofence_function(location_id, geofence_index)
{
	geofencing_temp_locations_changes_array[geofence_index] = false;
	$('#add_geofencing_modal form').find("[data-geofence-index='" + geofence_index + "']").parents(".row").remove();
}

function add_geofence_function(location_id)
{
	// Geofence object
	var new_geofence = {
		address: '',
		type: 'proximity',
		radius: geofencing_data.radius.SUBURBAN,
		latlng: [],
		zcta_type: 'SUBURBAN',
		radius_options: geofencing_data.radius,
		dropdown_options: geofencing_data.dropdown_options
	};

	var count_current_locations = geofencing_temp_locations_changes_array.length;
	var dropdown_options_html = "";
	for(var dropdown_index in new_geofence.dropdown_options)
	{
		var radius = new_geofence.dropdown_options[dropdown_index];
		var selected = (new_geofence.type == radius.value) ? "selected" : "";
		dropdown_options_html += '<option ' + selected + ' value="' + radius.value + '">' + radius.text + '</option>';
	}
	// Geofence gui
	var new_geofence_input =
		'<div class="row">' +
			'<div class="input-field col s7">' +
				'<input type="text" class="geofence_place_complete geofence_address" />' +
			'</div>' +
			'<div class="input-field col s2">' +
				'<select id="geofence_radius_' + count_current_locations + '" class="grey-text material_select" >' +
					dropdown_options_html +
				'</select>' +
				'<label for="geofence_radius_' + count_current_locations + '">Radius</label>' +
			'</div>' +
			'<div class="col s2 grey-text">' +
				'<span class="radius_text">' + new_geofence.radius + 'm radius</span>' +
			'</div>' +
			'<div class="col s1">' +
				'<button type="button" data-location-id="' + location_id + '" data-geofence-index="' + count_current_locations + '" class="btn remove-geofence">' +
					'<i data-time-icon="icon-remove" class="icon-remove icon-white"></i>' +
				'</button>' +
			'</div>' +
		'</div>';

	geofencing_temp_locations_changes_array.push(new_geofence);

	$(new_geofence_input).insertBefore($("#add_geofencing_modal form .row:last-child()"));
	$('#add_geofencing_modal form .remove-geofence').off('click').on('click', function(e){
		var location_id = Number($(this).data('location-id'));
		var geofence_index = Number($(this).data("geofence-index"));
		remove_geofence_function(location_id, geofence_index);
	});
	$('#add_geofencing_modal .row select').last().on('change', handle_radius_change);
	$('#add_geofencing_modal .row select').last().change();
	$('#geofence_radius_' + count_current_locations).material_select();
	var map_bounds = document.getElementById('iframe-map').contentWindow.map.getBounds();
	var map_bounds_literal = {
		east: map_bounds.getNorthEast().lng(),
		west: map_bounds.getSouthWest().lng(),
		north: map_bounds.getNorthEast().lat(),
		south: map_bounds.getSouthWest().lat()
	};
	placecomplete_object.requestParams.bounds = map_bounds_literal;
	$(function() {
		$('.geofence_place_complete:last').placecomplete(placecomplete_object);
		$(".geofence_place_complete:last").on("change", function(e){
			if(e.added && e.added.description)
			{
				disable_save_button();
				var address = e.added.description;
				get_radius_info_for_geofence_address(address, location_id, count_current_locations);
			}
		});
	});
}

function populate_geofence_location_data_from_preload()
{
	if(rfp_raw_preload_location_object && JSON.parse(rfp_raw_preload_location_object))
	{
		var io_preload_object_array = JSON.parse(rfp_raw_preload_location_object);
		for(var location_id = 0; location_id < io_preload_object_array.length; location_id++)
		{
			var io_preload_object = io_preload_object_array[location_id];
			if(io_preload_object.geofences && io_preload_object.geofences.length > 0)
			{
				geofencing_locations_array[location_id] = io_preload_object.geofences;
				$("#geo_location_dropdown li").eq(location_id).find("span.geofence_count").text(io_preload_object.geofences.length.toString());
			}
		}
	}
}
populate_geofence_location_data_from_preload();

function add_geofence_to_frontend_array(location_id, geofence_data)
{
	geofencing_locations_array[location_id] = [geofence_data];
}

function handle_radius_change(event)
{
	//radius_text
	var index = Number($(this).closest(".row").find("button").data("geofence-index"));
	var current_value = $(this).val();
	var current_object = geofencing_temp_locations_changes_array[index];
	var radius_value = current_object.radius_options[current_value.toUpperCase()][current_object.zcta_type.toUpperCase()] || current_object.radius_options[current_value.toUpperCase()];

	current_object.type = current_value;
	current_object.radius = radius_value;
	$(this).closest(".row").find(".radius_text").text(radius_value + "m radius");
}

function handle_save_geofencing_points(location_id)
{
	// get data together
	var points_to_send = construct_points_to_send_objects();
	$.ajax({
		url: "/rfp/handle_geofencing_ajax/",
		type: 'POST',
		dataType: 'json',
		data: {
			mpq_id: mpq_id,
			location_id: location_id,
			geofences: points_to_send
		},
		success: function(return_data)
		{
			if(return_data.is_success)
			{
				// Handle making temp object new point object
				geofencing_locations_array[location_id] = geofencing_temp_locations_changes_array.filter(Boolean);
				// Handle telling users if zips were added
				if(return_data.missing_geofence_regions.length)
				{
					var message = "The following zips were added based on the selected geofences:<br>" + return_data.missing_geofence_regions.join(', ');
					Materialize.toast(message, 5000, 'toast_top', null, "Heads up!");
				}
				// Refresh map
				var mapLinks ="<iframe id='iframe-map' src='/mpq/map/" + location_id + "#snug' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
				document.getElementById('region-links').innerHTML = '';
				document.getElementById('region-links').innerHTML = mapLinks;

				// Change number of geofences for location
				$("#geo_location_dropdown li").eq(location_id).find("span.geofence_count").text(geofencing_locations_array[location_id].length);

				$("#add_geofencing_modal").closeModal();
			}
		},
		complete: function()
		{
			reenable_save_button(location_id);
		}
	});
}

function construct_points_to_send_objects()
{
	var points_to_send = geofencing_temp_locations_changes_array;
	var formatted_points_to_send = [];
	for(var i = 0; i < points_to_send.length; i++)
	{
		if(points_to_send[i])
		{
			formatted_points_to_send.push({
				latlng: points_to_send[i].latlng,
				radius: points_to_send[i].radius,
				search: points_to_send[i].address,
				type: points_to_send[i].type
			});
		}
	}
	return formatted_points_to_send;
}

function get_radius_info_for_geofence_address(address, location_id, index_in_point_list)
{
	geocoder.geocode({'address': address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK)
		{
			var latitude = results[0].geometry.location.lat();
			var longitude = results[0].geometry.location.lng();

			$.ajax({
				url: "/rfp/get_center_geofence_type/",
				type: 'POST',
				dataType: 'json',
				data: {
					point_center: [latitude, longitude]
				},
				success: function(return_data)
				{
					if(return_data.is_success)
					{
						var zcta = return_data.point_info.zcta;
						var zcta_type = return_data.point_info.zcta_type;
						var affected_point = geofencing_temp_locations_changes_array[index_in_point_list];

						affected_point.type = $("#geofence_radius_" + index_in_point_list).val();
						affected_point.address = address;
						affected_point.latlng = [latitude, longitude];
						affected_point.zcta_type = zcta_type;

						$("#geofence_radius_" + index_in_point_list).change();
					}
				},
				complete: function()
				{
					reenable_save_button(location_id);
				}
			});
		}
		else
		{
			Materialize.toast("Oooops! There was a problem searching for your location.", 20000, 'toast_top');
		}
	});
}

function remove_geofences_of_removed_location_from_frontend_array(location_id)
{
	if(geofencing_locations_array[location_id])
	{
		geofencing_locations_array.splice(location_id, 1);
	}
}

function disable_save_button()
{
	$("#add_geofencing_modal .save_geofence_points").addClass("disabled").off("click");
}

function reenable_save_button(location_id)
{
	remove_saving_icon();
	$("#add_geofencing_modal .save_geofence_points").removeClass("disabled").off("click").on('click', function(e){
		show_save_loading_button();
		handle_save_geofencing_points(location_id);
	});
}

function show_save_loading_button()
{
	disable_save_button();
	add_saving_icon();
}

function add_saving_icon()
{
	$("#add_geofencing_modal .save_geofence_points").html("<img src='images/maploader.gif' />");
}

function remove_saving_icon()
{
	$("#add_geofencing_modal .save_geofence_points").text("Save");
}

function initialize_flights_modal(product_id, region_id, time_series_data, campaigns_id)
{
	$('#io_flights_modal input#io_flights_product_id').val(product_id);
	$('#io_flights_modal input#io_flights_region_id').val(region_id);

	$('#io_flights_modal #flights-collection li.collection-item').remove();
	$('#io_flights_modal .total-impressions').empty();

	var start_picker = $('#io_add_flight_start').pickadate('picker');
	var end_picker = $('#io_add_flight_end').pickadate('picker');

	var io_start_date = new Date();
	var io_term = io_product_info[product_id].term;
	var io_duration = parseInt(io_product_info[product_id].duration);
	var io_impressions = io_product_info[product_id].impressions == 0 ? 100000 : parseInt(io_product_info[product_id].impressions, 10);
	var io_end_date;

	if (io_term === null)
	{
		io_term = "monthly";
		io_duration = 6;
	}

	if(io_term === "monthly")
	{
		io_term = "MONTH_END";
		io_end_date = new Date(new Date(io_start_date).setMonth(io_start_date.getMonth()+io_duration));
	}
	else
	{
		io_impressions *= io_duration;

		if(io_term == "weekly")
		{
			io_duration = io_duration * 7;
		}

		io_term = 'FIXED';

		io_end_date = new Date(new Date(io_start_date).setDate(io_start_date.getDate()+io_duration));
	}

	$('#io_add_flight_impression').val(number_with_commas(io_impressions));
	$('#io_add_flight_term').val(io_term);
	$('#io_add_flight_term').material_select();

	var start_month = ("0"+(io_start_date.getMonth()+1)).slice(-2);
	var start_day = ("0"+io_start_date.getDate()).slice(-2);
	var start_year = io_start_date.getFullYear();

	var end_month = ("0"+(io_end_date.getMonth()+1)).slice(-2);
	var end_day = ("0"+io_end_date.getDate()).slice(-2);
	var end_year = io_end_date.getFullYear();

	start_picker.set('select', io_start_date);
	end_picker.set('select', io_end_date);

	if(start_picker.get('value'))
	{
		end_picker.set('min', start_picker.get('select'));
		start_picker.set('min', io_start_date);
	}
	if(end_picker.get('value'))
	{
		start_picker.set('max', end_picker.get('select'));
	}

	start_picker.on('set', function(event) {
		if (event.select)
		{
			end_picker.set('min', start_picker.get('select'));
		}
		else if (event.clear !== undefined)
		{
			end_picker.set('min', new Date());
		}
	});
	end_picker.on('set', function(event) {
		if (event.select)
		{
			start_picker.set('max', end_picker.get('select'));
		}
		else if (event.clear !== undefined)
		{
			var date = new Date();
			date.setMonth(date.getMonth() + 120);
			start_picker.set('max', date);
		}
	});

	$('#io_flights_modal span.per_geo').hide(0);

	if (time_series_data !== undefined)
	{
		$('#io_flights_modal').addClass('modal-fixed-footer');
		$('#io_define_flights_budget_allocation').attr('disabled', true);
		$('#io_define_flights_budget_allocation .input-group').hide();

		$('#io_define_flights_budget_allocation h4 .location_name').text(location_collection.locations[region_id].location_name);

		clear_date_picker();

		$('#io_flights_modal #io_flights_ok').removeClass('disabled');

		populate_time_series_collection(time_series_data);
	}
	else
	{
		if(location_collection.locations.length > 1)
		{
			$('#io_define_flights_budget_allocation').attr('disabled', false);
			$('#io_define_flights_budget_allocation .input-group').show();
			$('#io_flights_modal span.per_geo').show(0);
		}
		else
		{
			$('#io_define_flights_budget_allocation').attr('disabled', true);
			$('#io_define_flights_budget_allocation .input-group').hide();
		}
		$('#io_flights_modal').removeClass('modal-fixed-footer');
		$('#io_flights_modal #flights-collection').hide(0);

		$('#io_define_flights_budget_allocation h4 .location_name').text('(all locations)');

		$('#io_flights_modal #io_flights_ok').addClass('disabled');
	}

	io_flights.selected_product = product_id;
	$('#io_flights_modal').openModal({dismissible: false});
}

/*
 * The datepicker automatically sets its max/min date when a date
 * is changed. We have to set the start date to a far future date
 * and then set both dates to false to reset the max/min.
 */
function clear_date_picker(picker)
{
	var start_picker = $('#io_add_flight_start').pickadate('picker');
	var end_picker = $('#io_add_flight_end').pickadate('picker');

	var date = new Date();

	start_picker.set('select', date);
	date.setMonth(date.getMonth() + 120);
	end_picker.set('select', date);

	start_picker.set('select', false);
	end_picker.set('select', false);

	start_picker.clear();
	end_picker.clear();
}

function initialize_define_creatives_modal(product_id)
{
	var user_id = $('#account_executive_select').val();
	io_creatives.selected_product = product_id;
	$('#io_define_creatives_modal_body_content').html('<input type="hidden" style="width:100%;" id="io_creative_select_adsets">');
	var io_select2 = $('#io_creative_select_adsets').select2({
		placeholder: "Select adsets",
		minimumInputLength: 0,
		multiple: true,
		ajax: {
			url: "/mpq_v2/get_select2_adset_versions_for_user_io/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 30,
					page: page,
					user_id: user_id,
					product_id: product_id,
					show_all_versions: $('#define_creatives_modal_all_versions').prop('checked')
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		formatResult: format_creative_select2_results,
		allowClear: true,
		escapeMarkup: function(m) {return m;}
	});

	io_select2 = io_select2.data('select2');

	io_select2.onSelect = (function(fn) {
		return function(data, options) {
			var target;

			if (options != null)
			{
				target = $(options.target);
			}

			if (target && target.hasClass('io_creative_link_anchor'))
			{
				//success
			}
			else
			{
				return fn.apply(this, arguments);
			}
		};
	})(io_select2.onSelect);

	$('#io_define_creatives_modal').openModal();
}

function format_creative_select2_results(data)
{
	var thumb_html = '<img src="'+data.normal_thumb+'" style="width: 100px;">';
	if(data.normal_thumb == null)
	{
		thumb_html = '<div class="io_creative_no_preview_available">No Preview Available</div>';
	}

	var creative_html = "";
	if(data.show_for_io == 1)
	{
		creative_html += ''+
			'<table class="io_creative_results_table">'+
				'<tr>'+
					'<td rowspan="3" class="io_creative_thumb">'+thumb_html+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Adset:</span><br>'+data.text+' <small>v'+data.version+'.'+data.variation_name+'</small></td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Landing Page:</span><br>'+data.landing_page+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Last Saved:</span><br>'+data.time_created+' GMT</td>'+
				'</tr>'+
				'<tr>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser:</span><br>'+data.advertiser_name+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser Web Page:</span><br>'+data.website+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Request Type:</span><br>'+data.request_type+'</td>'+
				'</tr>'+
				'<tr>'+
					'<td colspan="3" class="io_creative_normal_table_data"><span class="io_creative_small">Preview Link:</span><br><a class="io_creative_link_anchor" href="'+data.gallery_link+'" target="_blank">'+data.gallery_link+'<i class="material-icons">&#xE89E;</i></a></td>'+
				'</tr>'+
			'</table>';
	}
	else
	{	//TODO: Add something to differentiate banner intakes from creatives.
		creative_html += ''+
			'<table class="io_creative_results_table banner_intake_result">'+
				'<tr>'+
					'<td rowspan="3" class="io_creative_thumb">'+thumb_html+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small banner_intake_header"><i class="material-icons io_icon_single_creative_status io_icon_on_hold" style="font-size:12px;top:2px;">&#xE924;</i>Creative Request:</span><br>'+data.text+' <small>v'+data.version+'.'+data.variation_name+'</small></td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Landing Page:</span><br>'+data.landing_page+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Last Saved:</span><br>'+data.time_created+' GMT</td>'+
				'</tr>'+
				'<tr>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser:</span><br>'+data.advertiser_name+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Advertiser Web Page:</span><br>'+data.website+'</td>'+
					'<td class="io_creative_normal_table_data"><span class="io_creative_small">Request Type:</span><br>'+data.request_type+'</td>'+
				'</tr>'+
			'</table>';
	}
	return creative_html;
}

function preload_new_adset_request(product_name)
{
	var result = true;
	$.ajax({
		type: "POST",
		url: '/mpq_v2/preload_io',
		async: false,
		data:
		{
			mpq_id: mpq_id,
			product: product_name
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			return true;

		},
		error: function(jqXHR, textStatus, error){
			result = false;
			Materialize.toast('Ooops! There was a problem updating your io status.', 46000, 'toast_top');
		}
	});
	return result;
}

$('.io_new_adset_request_button').click(function(e){
	e.stopPropagation();
});

$('.io_define_flights_button, .io_define_creatives_button').click(function(e){
	e.preventDefault();
	e.stopPropagation();
});

$(document).on('click', '#io_define_creatives_cancel, #io_single_creative_cancel, #io_flights_cancel, #io_confirm_flights_cancel, #io_confirm_flights_ok', function(e){
	e.preventDefault();
	e.stopPropagation();
});

$('#mpq_org_name, #unverified_advertiser, #mpq_website_name, #industry_select, #demographic_inputs input[type="checkbox"], #iab_contextual_multiselect, #mpq_notes_input').on('change', function(){
	if (($(this).val() !== 'unverified_advertiser_name'))
	{
		check_io_in_use().success(function(data){
			io_save_submit('save', true);
		});
	}
});

$('#dfp_advertiser_name').on('change', function(e){
	e.preventDefault();
	if (($(this).val() !== ''))
	{
		var new_dfp_advertiser_name = $(this).val();
		check_io_in_use().success(function(data){
			validate_and_create_dfp_advertiser(new_dfp_advertiser_name);
		});
	}
});

$('#io_flights_ok').on('click', function(e){
	e.preventDefault();

	var product_id = $(this).siblings('#io_flights_product_id').val();

	if ($('#io_flights_region_id').val() === "" && location_collection.locations.length > 1 && product_has_flights(product_id))
	{
		$('#io_flights_modal').fadeOut(200);
		$('#io_confirm_flights_modal').openModal({dismissible: false});
	} else {
		save_flights();
	}
});

$('#io_flights_cancel').on('click', function(e){
	$('#io_flights_modal').closeModal();
	$('#io_confirm_flights_modal').closeModal();
});

$('#io_confirm_flights_ok').on('click', function(e){
	save_flights(function(){
		$('#io_confirm_flights_modal').closeModal();
	});
});
$('#io_confirm_flights_cancel').on('click', function(e){
	e.preventDefault();
	$('#io_confirm_flights_modal').fadeOut(200);
	$('#io_flights_modal').fadeIn(200);
});

function save_flights(cb)
{
	var product_id = $('#io_flights_product_id').val();
	var region_id = $('#io_flights_region_id').val();
	$.ajax({
		type: "POST",
		url: '/mpq_v2/save_time_series_for_region_and_product',
		async: true,
		data:
		{
			product_id: product_id,
			region_id: region_id,
			time_series: get_timeseries_from_collection(),
			allocation_method: get_io_flight_budget_allocation_method()
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				$('#flights_product_'+product_id+' .io_flights_defined_text .io_flights_defined_header_content').hide();

				$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined').show();
				$('#io_flights_modal').closeModal();
				Materialize.toast('Flights saved successfully', 10000, 'toast_top', '', 'Success!');

				$('#io_flights_modal').closeModal();
				Materialize.toast('Flights defined successfully', 10000, 'toast_top', '', "Success!");

				$('#flights_product_'+product_id+' .io_flights_defined_text .io_flights_defined_header_content').hide();
				$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_with_data').show();

				set_io_flight_product_defined(product_id);
				if (region_id)
				{
					$.ajax({
						url: '/mpq_v2/get_time_series_summary_for_io',
						method: 'POST',
						dataType: 'json',
						data: {
							mpq_id: mpq_id,
							product_id: product_id
						},
						success: function(summary_data, x, y){
							$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_start_date').html(summary_data.min_date);
							$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_end_date').html(summary_data.max_date);
							$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_impressions').html(number_with_commas(Math.round(summary_data.sum_impressions/1000)));
						}
					});

					update_flight_region_summary(data.start_date, data.end_date, data.impressions, product_id, region_id);
				}
				else
				{
					$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_start_date').html(data.start_date);
					$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_end_date').html(data.end_date);
					$('#flights_product_'+product_id+' .io_flights_defined_text .io_all_flights_defined_impressions').html(number_with_commas(Math.round(data.impressions/1000)));
					populate_flight_geo_content(product_id, data.region_ids);
					$.each(data.region_data, function(i,flight){
						update_flight_region_summary(data.start_date, data.end_date, flight, product_id, i);
						if(io_product_info[product_id].o_o_enabled == 1 && io_submit_allowed == 1 &&  io_product_info[product_id].banner_intake_id == "Display")
						{
							retrieve_o_o_data(product_id, i);
						}
					});
				}
				if (cb !== undefined)
				{
					cb();
				}
			}
			else
			{
				Materialize.toast('Ooops! There was a problem editing flights for that location: <br>- '+data.errors, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem editing your flight data.', 46000, 'toast_top');
		}
	});
}

$('#io_add_flights_button').click(function(e){
	e.preventDefault();

	var validate_response = validate_flights_add_form();
	if(validate_response !== "")
	{
		Materialize.toast('Ooops! It looks like you haven\'t filled out the fight details completely:'+validate_response, 46000, 'toast_top');
		return;
	}
	else
	{
		var allocation_method = get_io_flight_budget_allocation_method();
		var product_id = $('#io_flights_product_id').val();
		$.ajax({
			type: "POST",
			url: '/mpq_v2/generate_flights_for_product',
			async: true,
			data:
			{
				product_id: product_id,
				start_date: $('#io_add_flight_start').val(),
				end_date: $('#io_add_flight_end').val(),
				impressions: $('#io_add_flight_impression').val().replace(/,/g, ""),
				term: $('#io_add_flight_term').val(),
				allocation_method: allocation_method,
				time_series: get_timeseries_from_collection()
			},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success === true && data.time_series.length > 0)
				{
					$('#io_flights_modal #flights-collection li.collection-item').remove();
					$('#io_flights_modal').addClass('modal-fixed-footer');
					populate_time_series_collection(data.time_series);
					clear_date_picker();
					$('#io_flights_modal #io_flights_ok').removeClass('disabled');
				}
				else
				{
					Materialize.toast(data.errors, 46000, 'toast_top');
					$('#io_flights_modal ul#flights-collection li.collection-item').removeClass('error');
					$('#io_flights_modal #flights-collection li.collection-item span.new.badge').remove();
					if (data.conflicts !== undefined)
					{
						$('#io_flights_modal ul#flights-collection li.collection-item:not(.hidden)').each(function(i,flight){
							var start_date = new Date($(this).find('.start_date').text());
							var end_date = new Date($(this).find('.end_date').text());
							var flight = this;
							$.each(data.conflicts, function(i,conflict){
								var conflict_start_date = new Date(conflict.start_date);
								var conflict_end_date = new Date(conflict.end_date);
								if (
									(conflict_start_date >= start_date && conflict_start_date <= end_date) ||
									(conflict_end_date >= start_date && conflict_end_date <= end_date) ||
									(start_date > conflict_start_date && end_date < conflict_end_date)
								){
									$(flight).addClass('error');
								}
							});
						});
					}
				}

			},
			error: function(jqXHR, textStatus, error){
				Materialize.toast('Ooops! There was a problem creating your flights.', 16000, 'toast_top');
			}
		});
	}
});

$('#io_define_creatives_ok').on('click', function(e){
	e.preventDefault();

	var validate_response = validate_define_creatives_modal();
	if(validate_response !== "")
	{
		Materialize.toast('Ooops! It looks like you haven\'t filled out the creative details completely:'+validate_response, 46000, 'toast_top');
		return;
	}
	else if(io_creatives.selected_product == "")
	{
		$('#io_define_creatives_modal').closeModal();
		Materialize.toast('Ooops! There was a problem generating your creatives.', 16000, 'toast_top');
	}
	else
	{
		var product_id = io_creatives.selected_product;
		if(io_creatives.products[product_id].length >= 1 && product_has_creatives(product_id))
		{
			$('#io_define_creatives_modal').fadeOut(200);
			$('#io_confirm_creatives_modal').openModal({dismissible: false});
		}
		else
		{
			save_creatives();
		}
	}
});

$('#io_confirm_creatives_ok').on('click', function(e){
	e.preventDefault();
	save_creatives(function(){
		$('#io_confirm_creatives_modal').closeModal();
	});
});
$('#io_confirm_creatives_cancel').on('click', function(e){
	e.preventDefault();
	$('#io_confirm_creatives_modal').fadeOut(200);
	$('#io_define_creatives_modal').fadeIn(200);
});

function save_creatives(callback)
{
	var product_id = io_creatives.selected_product;
	var creative_ids = $('#io_creative_select_adsets').select2('val');
	$.ajax({
		type: "POST",
		url: '/mpq_v2/io_define_creatives_for_product',
		async: true,
		data:
		{
			product_id: product_id,
			adset_id: creative_ids,
			mpq_id: mpq_id
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				$('#io_define_creatives_modal').closeModal();
				if(data.session_expired === false)
				{
					if(data.region_ids.length > 0)
					{
						Materialize.toast('Creatives assigned successfully', 10000, 'toast_top', '', "Success!");
						var creative_ids = $('#io_creative_select_adsets').select2('val');
						determine_product_status_for_product(product_id);
						set_io_creative_product_defined(product_id, creative_ids);
						populate_creative_geo_content(product_id, data.region_ids);
						get_updated_io_creatives_data();
					}
					else
					{
						Materialize.toast('Ooops! There was a problem generating your creatives.', 46000, 'toast_top');
					}
				}
				else
				{
					$('#io_redirect_to_all_ios_modal').openModal();
					io_redirect_timeout_id = window.setTimeout(function(){
						redirect_to_all_ios('io_lock');
					}, 8000);
				}
				if (callback !== undefined)
				{
					callback();
				}
			}
			else
			{
				Materialize.toast('Ooops! There was a problem generating your creatives: <br>- '+data.errors, 46000, 'toast_top');
			}


		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem generating your creatives.', 16000, 'toast_top');
		}
	});
}
function validate_flights_add_form()
{
	var return_string = "";
	if($.trim($('#io_add_flight_start').val()) == "")
	{
		return_string += "<br>- Please select a start date for your flights";
	}
	if($.trim($('#io_add_flight_end').val()) == "")
	{
		return_string += "<br>- Please select an end date for your flights";
	}
	if($.trim($('#io_add_flight_impression').val()) == "")
	{
		return_string += "<br>- Please select an impression amount for your flights";
	}
	return return_string;
}

function validate_define_creatives_modal()
{
	var return_string = "";
	var creative_ids = $('#io_creative_select_adsets').select2('val');
	if(creative_ids.length <= 0)
	{
		return_string += "<br>- Please select at least one adset";
	}
	return return_string;
}

function validate_flights()
{
	return are_all_flights_defined();
}

function validate_creative()
{
	return are_all_creatives_defined();
}

function validate_notes()
{
	//notes are optional (xmp is filtered)
	return true;
}

function get_io_flight_budget_allocation_method()
{
	if($('#io_allocation_fixed').prop('checked'))
	{
		return 'fixed';
	}
	else
	{
		return 'per_pop';
	}
}

function io_update_side_nav(update_in_db)
{

	if(location_collection.locations.length > 1)
	{
		$('#io_define_flights_budget_allocation .input-group').show();
	}
	else
	{
		$('#io_define_flights_budget_allocation .input-group').hide();
	}

	var can_be_submitted = true;
	if(rfp_is_preload == "1")
	{
		io_side_nav_items.product.status = 'done';
	}

	var validate_text_response = "";
	if(!validate_opportunity_details_for_io())
	{
		can_be_submitted = false;
		io_side_nav_items.opportunity.status = 'not_started';
	}
	else
	{
		io_side_nav_items.opportunity.status = 'done';
	}

	var geo_success = {'is_success': false};
	validate_geography(geo_success);
	if(!geo_success.is_success)
	{
		can_be_submitted = false;
		io_side_nav_items.geo.status = 'not_started';
	}
	else
	{
		io_side_nav_items.geo.status = 'done';
	}

	if(!validate_demographics())
	{
		can_be_submitted = false;
		io_side_nav_items.audience.status = 'not_started';
	}
	else
	{
		io_side_nav_items.audience.status = 'done';
	}

	if(!validate_iab_categories())
	{
		can_be_submitted = false;
		io_side_nav_items.audience.status = 'not_started';
	}
	else
	{
		io_side_nav_items.audience.status = 'done';
	}

        if(!validate_tracking_for_io())
	{
		can_be_submitted = false;
		io_side_nav_items.tracking.status = 'not_started';
	}
	else
	{
		io_side_nav_items.tracking.status = 'done';
	}

	if(!validate_flights())
	{
		can_be_submitted = false;
		io_side_nav_items.flights.status = 'not_started';
	}
	else
	{
		io_side_nav_items.flights.status = 'done';
	}

	var creative_validation_status = validate_creative();
	switch(creative_validation_status)
	{
		case 0:
			can_be_submitted = false;
			io_side_nav_items.creative.status = 'not_started';
			break;
		case 1:
			io_side_nav_items.creative.status = 'done';
			break;
		case 2:
			can_be_submitted = false;
			io_side_nav_items.creative.status = 'on_hold';
			break;
		default:
			can_be_submitted = false;
			io_side_nav_items.creative.status = 'not_started';
			break;
	}
	if(!validate_notes())
	{
		can_be_submitted = false;
		io_side_nav_items.notes.status = 'not_started';
	}
	else
	{
		io_side_nav_items.notes.status = 'done';
	}

	$.each(io_side_nav_items, function(key, value){
		var nav_item = $('#io_nav_'+key+' i.material-icons');
		if(value.status == 'done')
		{
			nav_item.addClass(io_side_nav_icons.done.io_class);
			nav_item.removeClass(io_side_nav_icons.in_progress.io_class);
			nav_item.removeClass(io_side_nav_icons.not_started.io_class);
			nav_item.removeClass(io_side_nav_icons.on_hold.io_class);
			nav_item.html(io_side_nav_icons.done.io_icon);
		}
		else if(value.status == 'in_progress')
		{
			nav_item.addClass(io_side_nav_icons.in_progress.io_class);
			nav_item.removeClass(io_side_nav_icons.done.io_class);
			nav_item.removeClass(io_side_nav_icons.not_started.io_class);
			nav_item.removeClass(io_side_nav_icons.on_hold.io_class);
			nav_item.html(io_side_nav_icons.in_progress.io_icon);
		}
		else if(value.status == "on_hold")
		{
			nav_item.addClass(io_side_nav_icons.on_hold.io_class);
			nav_item.removeClass(io_side_nav_icons.done.io_class);
			nav_item.removeClass(io_side_nav_icons.not_started.io_class);
			nav_item.removeClass(io_side_nav_icons.in_progress.io_class);
			nav_item.html(io_side_nav_icons.on_hold.io_icon);
		}
		else
		{
			nav_item.addClass(io_side_nav_icons.not_started.io_class);
			nav_item.removeClass(io_side_nav_icons.in_progress.io_class);
			nav_item.removeClass(io_side_nav_icons.done.io_class);
			nav_item.removeClass(io_side_nav_icons.on_hold.io_class);
			nav_item.html(io_side_nav_icons.not_started.io_icon);
		}
	});

        if (typeof update_in_db !== 'undefined' && update_in_db)
        {
                ajax_update_io_status(true);
        }

	if(can_be_submitted)
	{
		$('#io_submit_button, #io_submit_for_review_button').css("display", "block");
	}
	else
	{
		$('#io_submit_button, #io_submit_for_review_button').css("display", "none");
	}
}

function ajax_update_io_status(call_async)
{
	var result = true;
	$.ajax({
		type: "POST",
		url: '/proposal_builder/update_io_status',
		async: call_async,
		data:
		{
			io_status: io_side_nav_items,
			mpq_id: mpq_id
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
			}
			else
			{
				result = false;
				Materialize.toast('Ooops! There was a problem updating your io status: <br>- '+data.errors, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			result = false;
			Materialize.toast('Ooops! There was a problem updating your io status.', 46000, 'toast_top');
		}
	});
	return result;
}

function populate_flight_geo_content(product_id, region_ids)
{
	var geo_html_section = $('#flights_product_'+product_id+' .collapsible-body > .card-content');
	geo_html_section.html('');
	$.each(location_collection.locations, function(key, value){
		if(value.has_been_populated !== false)
		{
			var icon_html = '<i class="material-icons io_geo_body_icon '+io_side_nav_icons.done.io_class+'">'+io_side_nav_icons.done.io_icon+'</i>';
			if($.inArray(key, region_ids) === -1)
			{
				icon_html = '<i class="material-icons io_geo_body_icon '+io_side_nav_icons.not_started.io_class+'">'+io_side_nav_icons.not_started.io_icon+'</i>';
			}
			var flight_html_string = ''+
				'<div class="row" id="flight_summary_product_'+product_id+'_region_'+key+'">'+ icon_html+' '+value.location_name+'&nbsp;&nbsp;'+
					'<a href="#" class="io_single_flight_modal_link" data-product-id="'+product_id+'" data-region-id="'+key+'"><span class="flight-summary"></span> <span class="icon-pencil"></span></a>';
				'</div>';
			if(io_product_info[product_id].o_o_enabled == 1 && io_submit_allowed == 1 &&  io_product_info[product_id].banner_intake_id == "Display")
			{
				flight_html_string += '' +
				'<div class="row o_and_o_detail_row" id="flight_o_and_o_details_'+product_id+'_region_'+key+'">' +
					'<div class="input-field col s1"></div>' +
					'<div class="input-field col s3"> '+
						'<label for="flight_o_and_o_percent_'+product_id+'_region_'+key+'" style="top:30px;" class="active">O&O Percentage ('+io_product_info[product_id].o_o_min_ratio+' - '+io_product_info[product_id].o_o_max_ratio+'%)</label><br>' +
						'<input type="text" id="flight_o_and_o_percent_'+product_id+'_region_'+key+'" class="grey-text text-darken-1 o_and_o_percentage" value="'+io_product_info[product_id].o_o_default_ratio+'" data-product-id="'+product_id+'" data-region-id="'+key+'" data-ratio-min="'+io_product_info[product_id].o_o_min_ratio+'" data-ratio-default="'+io_product_info[product_id].o_o_default_ratio+'" data-ratio-max="'+io_product_info[product_id].o_o_max_ratio+'"> %' +
					'</div>' +
					'<div class="input-field col s5"> '+
						'<span class="o_and_o_mock_campaign_label" for="flight_o_and_o_ids_'+product_id+'_region_'+key+'" style="top:30px;">O&O Campaign IDs</span><br>' +
						'<input type="text" id="flight_o_and_o_ids_'+product_id+'_region_'+key+'" style="width:100%;" data-product-id="'+product_id+'" data-region-id="'+key+'">' +
					'</div>' +
				'</div>';
			}
			geo_html_section.append(flight_html_string);

			$('#flight_o_and_o_ids_'+product_id+'_region_'+key).select2({
				tags: [],
				dropdownCssClass: 'select2-hidden',
				tokenSeparators: [",", "\t"],
				 formatNoMatches: function() {return '';}
			});
			$('#s2id_flight_o_and_o_ids_'+product_id+'_region_'+key).on('paste', function(e) {
				var data = e.originalEvent.clipboardData.getData('Text');
				data = data.split(/\n/g);
				var s2_data = $(this).select2('data');
				$.each(data, function(key, value){
					s2_data.push({id:value, text:value});
				})
				$(this).select2('data', s2_data);
				return false;
			});

			$('#flight_o_and_o_percent_'+product_id+'_region_'+key).on('blur', function(e){
				$(this).removeClass('invalid');
				var o_o_percentage = $(this).val();
				if(o_o_percentage == "")
				{
					$("label[for='"+$(this).attr('id')+"']").addClass('active')
					this.value = "0";
					o_o_percentage = 0;
				}
				var min_ratio = $(this).attr('data-ratio-min');
				var max_ratio = $(this).attr('data-ratio-max');
				o_o_percentage = parseFloat(o_o_percentage);
				if(o_o_percentage > max_ratio || o_o_percentage < min_ratio)
				{
					$(this).addClass('invalid');
					Materialize.toast('Please enter a valid O&O Percentage (Between '+min_ratio+' and '+max_ratio+')', 46000, 'toast_top');
					this.value = $(this).attr('data-ratio-default');
					o_o_percentage = parseFloat(min_ratio);
				}

				save_o_o_ratio($(this).attr('data-product-id'), $(this).attr('data-region-id'), o_o_percentage)
			}).on('keyup', function(e)
			{
				if(this.value == " ")
				{
					this.value = "0";
				}
				this.value = this.value.replace(/[^0-9\.]/g,'');
			}).keypress(function(e)
			{
				if(e.which == 13)
				{
					$(this).blur();
				}
			});

			$('#flight_o_and_o_ids_'+product_id+'_region_'+key).on('select2-blur removed', function(e){
				$(this).removeClass('invalid');
				var o_o_ids = $(this).select2('data');
				if(o_o_ids.length == 0)
				{
					o_o_ids = 0;
				}
				$.ajax({
					type: "POST",
					url: '/mpq_v2/save_o_o_ids',
					async: true,
					data:
					{
						mpq_id: mpq_id,
						product_id: $(this).attr('data-product-id'),
						region_id:$(this).attr('data-region-id'),
						o_o_ids: o_o_ids

					},
					dataType: 'json',
					success: function(data, textStatus, jqXHR){
						if(data.is_success === true)
						{

						}
						else
						{
							Materialize.toast('Ooops! There was a problem saving O&O data: <br>- '+data.err, 46000, 'toast_top');
						}

					},
					error: function(jqXHR, textStatus, error){
						Materialize.toast('Ooops! There was a problem retrieving your flight data.', 46000, 'toast_top');
					}
				});
			});
		}
	});

	toggle_collapsible('#flights_product_'+product_id, geo_html_section.html() !== '');
}

function update_flight_region_summary(start_date, end_date, impressions, product_id, region_id)
{
	var impressions = impressions >= 10000 ? Math.round(impressions / 1000) : impressions;
	$('#flight_summary_product_'+product_id+'_region_'+region_id+ ' .flight-summary').text(number_with_commas(impressions) +'k total impressions from '+ start_date +' through '+ end_date);
}

function product_has_flights(product_id)
{
	return io_flights.products[product_id].reduce(function(has_flights, region){
		if (region) has_flights = true;
		return region;
	}, false);
}

function populate_creative_geo_content(product_id, region_ids)
{
	var geo_html_section = $('#creatives_product_'+product_id+' .collapsible-body > .card-content');
	geo_html_section.html('');
	$.each(location_collection.locations, function(key, value){
		if(value.has_been_populated !== false)
		{

			var show_hold = true;
			var landing_pages_html = '';
			$.each(io_creatives_data, function(key_landing_page, value_landing_page){
				if (value_landing_page.product_id == product_id && value_landing_page.region_id == key)
				{
					if (typeof value_landing_page.landing_page != 'undefined' && value_landing_page.landing_page != null && value_landing_page.landing_page != '')
					{
						if (landing_pages_html == '')
						{

							landing_pages_html = '<div class="creative_info_landing_pages_'+key+'"><div class="col s12"><ul>';
						}

						var landing_page_url = value_landing_page.landing_page;
						if (landing_page_url.indexOf("http:") <= -1 && landing_page_url.indexOf("https:") <= -1)
						{
							landing_page_url = 'http://'+landing_page_url;
						}

						var prefix_html = '';
						if(value_landing_page.creative_status == "2")
						{
							prefix_html = '<i class="material-icons io_icon_single_creative_status io_icon_on_hold tooltipped" data-position="top" data-tooltip="Creative Request Pending">&#xE924;</i>'
						}
						else
						{
							prefix_html = '<i class="material-icons io_icon_single_creative_status io_icon_done" >&#xE8E8;</i>';
							show_hold = false;
						}
						landing_pages_html = landing_pages_html + '<li>'+prefix_html+value_landing_page.creative_name+' : <a href="'+landing_page_url+'" target="_blank">'+value_landing_page.landing_page+'</a></li>';
					}
				}
			});

			var creative_html_string = ''+
				'<div class="row">'+
					' <a href="#" class="io_single_creative_modal_link" data-product-id="'+product_id+'" data-region-id="'+key+'">'+
						value.location_name+'&nbsp;&nbsp;<span class="icon-pencil"></span>'+
					'</a>';


			if (landing_pages_html != '')
			{
				landing_pages_html = landing_pages_html + '</ul></div></div></div>';

				$('#creatives_product_'+product_id+' .collapsible-body .creative_info_landing_pages_'+key).remove();
				creative_html_string = creative_html_string  + landing_pages_html;
			}

			creative_html_string += '</div>';
			geo_html_section.append(creative_html_string);
			determine_product_status_for_product(product_id);
		}
	});

	toggle_collapsible('#creatives_product_'+product_id, geo_html_section.html() !== '');
	$('.tooltipped').tooltip({delay:50});
}

$(document).on('click', '.io_single_flight_modal_link', function(e){
	e.preventDefault();
	var product_geo_data = $(this).data();
	if(typeof product_geo_data.productId === "undefined" || typeof product_geo_data.regionId === "undefined")
	{
		Materialize.toast('Ooops! There was a problem editing flights for that location.', 46000, 'toast_top');
		return;
	}
	io_selected_submitted_product = false;
	io_selected_product_id = false;
	$.ajax({
		type: "POST",
		url: '/mpq_v2/get_time_series_by_region_and_product',
		async: true,
		data:
		{
			product_id: product_geo_data.productId,
			region_id: product_geo_data.regionId
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				initialize_flights_modal(product_geo_data.productId, product_geo_data.regionId, data.time_series_data, data.time_series_data[0].campaigns_id);
			}
			else
			{
				Materialize.toast('Ooops! There was a problem editing flights for that location: <br>- '+data.errors, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem retrieving your flight data.', 46000, 'toast_top');
		}
	});
});

$(document).on('click', '.io_single_creative_modal_link', function(e){
	e.preventDefault();
	var product_geo_data = $(this).data();
	if(typeof product_geo_data.productId === "undefined" || typeof product_geo_data.regionId === "undefined")
	{
		Materialize.toast('Ooops! There was a problem editing creatives for that location.', 46000, 'toast_top');
		return;
	}
	io_selected_submitted_product = false;
	io_selected_product_id = false;
	io_selected_product_geo_index = false;
	var product_id = product_geo_data.productId;
	var product_geo_index = product_geo_data.regionId;
	$.ajax({
		type: "POST",
		url: '/mpq_v2/io_edit_adset_for_product_geo',
		async: true,
		data:
		{
			product_id: product_id,
			region_id: product_geo_data.regionId,
			mpq_id: mpq_id
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				if(data.session_expired === false)
				{
					var user_id = $('#account_executive_select').val();
					io_selected_submitted_product = data.data.cp_submitted_products_id;
					io_selected_product_id = product_id;
					io_selected_product_geo_index = product_geo_index;
					var io_existing_creatives_array = data.data.creatives_array;
					$('#io_single_creative_modal_body_content').html('<input type="hidden" style="width:100%;" id="io_single_creative_select_adsets">');
					var io_select2 = $('#io_single_creative_select_adsets').select2({
						placeholder: "Select adsets",
						minimumInputLength: 0,
						multiple: true,
						ajax: {
							url: "/mpq_v2/get_select2_adset_versions_for_user_io/",
							type: 'POST',
							dataType: 'json',
							data: function (term, page) {
								term = (typeof term === "undefined" || term == "") ? "%" : term;
								return {
									q: term,
									page_limit: 50,
									page: page,
									user_id: user_id,
									product_id: product_id,
									show_all_versions: $('#single_creative_modal_all_versions').prop('checked')
								};
							},
							results: function (data) {
								return {results: data.results, more: data.more};
							}
						},
						formatResult: format_creative_select2_results,
						allowClear: true,
						escapeMarkup: function(m) {return m;}
					});

					io_select2 = io_select2.data('select2');

					io_select2.onSelect = (function(fn) {
						return function(data, options) {
							var target;

							if (options != null)
							{
								target = $(options.target);
							}

							if (target && target.hasClass('io_creative_link_anchor'))
							{
								//success
							}
							else
							{
								return fn.apply(this, arguments);
							}
						}
					})(io_select2.onSelect);

					$('#io_single_creative_select_adsets').select2('data', io_existing_creatives_array);
					$('#io_single_creative_modal').openModal();
				}
				else
				{
					$('#io_redirect_to_all_ios_modal').openModal();
					io_redirect_timeout_id = window.setTimeout(function(){
						redirect_to_all_ios('io_lock');
					}, 8000);
				}
			}
			else
			{
				Materialize.toast('Ooops! There was a problem editing creatives for that location: <br>- '+data.errors, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem retrieving your creatives data.', 46000, 'toast_top');
		}
	});
});

$('#io_single_creative_save').click(function(e){
	e.preventDefault();
	var creative_ids = $('#io_single_creative_select_adsets').select2('val');
	if(io_selected_submitted_product !== false && creative_ids.length > 0)
	{
		$.ajax({
			type: "POST",
			url: '/mpq_v2/io_save_adset_for_product_geo',
			async: true,
			data:
			{
				cp_submitted_product_id: io_selected_submitted_product,
				adset_id: creative_ids
			},
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				if(data.is_success === true)
				{
					$('#io_single_creative_modal').closeModal();
					get_updated_io_creatives_data();
					io_creatives.products[io_selected_product_id][io_selected_product_geo_index] = determine_creative_readiness_flag(creative_ids);

					var nav_creative_status = are_all_creatives_defined()
					switch(nav_creative_status)
					{
						case 0:
							update_nav_item_status('creative', 'not_started');
							break;
						case 1:
							update_nav_item_status('creative', 'done', true);
							break;
						case 2:
							update_nav_item_status('creative', 'on_hold', true);
							break;
						default: //This shouldn't happen
							Materialize.toast('Ooops! There was an error processing your creative change', 46000, 'toast_top');
					}

					Materialize.toast('Creatives saved successfully', 10000, 'toast_top', '', 'Success!');
				}
				else
				{
					Materialize.toast('Ooops! There was a problem editing creatives for that location: <br>- '+data.errors, 46000, 'toast_top');
				}

			},
			error: function(jqXHR, textStatus, error){
				Materialize.toast('Ooops! There was a problem editing your adset data.', 46000, 'toast_top');
			}
		});
	}
	else
	{
		Materialize.toast('Ooops! There was a problem saving your adset data.', 46000, 'toast_top');
	}
});

function set_io_flight_product_defined(product_id)
{
	$.each(io_flights.products[product_id], function(key, value){
		io_flights.products[product_id][key] = true;
	});
	if(are_all_flights_defined())
	{
		update_nav_item_status('flights', 'done', true);
	}
	else
	{
		update_nav_item_status('flights', 'not_started');
	}
}

function set_io_creative_product_defined(product_id, creative_ids)
{
	//Determine if creatives are all enabled or one of them is a banner intake
	creative_status = determine_creative_readiness_flag(creative_ids);

	$.each(io_creatives.products[product_id], function(key, value){
		io_creatives.products[product_id][key] = creative_status;
	});
	var nav_creative_status = are_all_creatives_defined()
	switch(nav_creative_status)
	{
		case 0:
			update_nav_item_status('creative', 'not_started');
			break;
		case 1:
			update_nav_item_status('creative', 'done', true);
			break;
		case 2:
			update_nav_item_status('creative', 'on_hold', true);
			break;
		default: //This shouldn't happen
			Materialize.toast('Ooops! There was an error processing your creative change', 46000, 'toast_top');
	}
}

function are_all_flights_defined()
{
	var defined = true;
	if(io_flights.products.length <= 0)
	{
		defined = false;
	}
	else
	{
		$.each(io_flights.products, function(p_key, p_value){
			if(p_value.length <= 0)
			{
				defined = false;
			}
			$.each(p_value, function(g_key, g_value){
				if(g_value == false)
				{
					defined = false;
				}
			});
		});
	}
	return defined;
}

function are_all_creatives_defined()
{
	var defined = 1;
	var at_least_one_success = true;
	if(io_creatives.products.length <= 0)
	{
		defined = 0;
	}
	else
	{
		$.each(io_creatives.products, function(p_key, p_value){
			if(p_value.length <= 0)
			{
				defined = 0;
			}
			var single_product_at_least_one_success = false;
			$.each(p_value, function(g_key, g_value){
				if(g_value == 2 && defined > 0)
				{
					defined = 2;
				}
				else if(g_value == 0)
				{
					defined = 0;
				}
				else
				{
					single_product_at_least_one_success = true;
				}
			});
			at_least_one_success = single_product_at_least_one_success && at_least_one_success;
		});
	}
	if(at_least_one_success && defined == 2)
	{
		defined = 1;
	}
	return defined;
}

function update_nav_item_status(item_name, status, server_update)
{
	io_side_nav_items[item_name].status = status;
	io_update_side_nav(server_update);
}

function fill_flight_o_o_fields(product_id, region_id, o_o_percent, o_o_ids)
{
	if(o_o_percent != null)
	{
		$('#flight_o_and_o_percent_'+product_id+'_region_'+region_id).val(o_o_percent);
	}

	if(o_o_ids != null)
	{
		var o_o_saved_ids = o_o_ids.split(" ; ");
		var s2_data = [];
		$.each(o_o_saved_ids, function(key, value){
			s2_data.push({id:value, text:value});
		})
		$('#flight_o_and_o_ids_'+product_id+'_region_'+region_id).select2('data', s2_data);
	}
}

function retrieve_o_o_data(product_id, region_id)
{
	$.ajax({
		type: "POST",
		url: '/mpq_v2/retrieve_o_o_data',
		async: true,
		data:
		{
			mpq_id: mpq_id,
			product_id: product_id,
			region_id: region_id,
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success === true)
			{
				fill_flight_o_o_fields(product_id, region_id, data.o_o_percent, data.o_o_ids);
			}
			else
			{
				Materialize.toast('Ooops! There was a problem retrieving O&O data: <br>- '+data.err, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem retrieving your O&O data.', 46000, 'toast_top');
		}
	});
}

function save_o_o_ratio(product_id, region_id, o_o_percentage)
{
	$.ajax({
		type: "POST",
		url: '/mpq_v2/save_o_o_percentage',
		async: true,
		data:
		{
			mpq_id: mpq_id,
			product_id: product_id,
			region_id:region_id,
			o_o_percentage: o_o_percentage

		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			if(data.is_success !== true)
			{
				Materialize.toast('Ooops! There was a problem saving O&O data: <br>- '+data.err, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem retrieving your flight data.', 46000, 'toast_top');
		}
	});
}

$(document).ready(function(){

		check_io_in_use();

        $('#mpq_org_name').select2({
		width: '100%' ,
		placeholder: "Select Advertiser",
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
                                return data.text;
                        }
                        else
                        {
                                var verified_html = '';
                                if (data.status === 'verified')
                                {
                                        verified_html = '<br/><i class="material-icons io_icon_done" style="position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i><small class="grey-text">verified advertiser</small>';
                                }
                                var external_id_text = data.externalId ? ' <small class="grey-text">EXTID: '+data.externalId+'</small>' : '';
                                var third_party_ids = '';
				if(data.ul_id!=='' || data.eclipse_id!=='')
				{
					third_party_ids += '<small class="grey-text">';
					if(data.ul_id!=='')
					{
						third_party_ids += 'ulid : '+data.ul_id;
					}
					if(data.eclipse_id!=='')
					{
						if(data.ul_id!=='')
						{
							third_party_ids += ' | ';
						}
						third_party_ids += 'eclipseid : '+data.eclipse_id;
					}
					third_party_ids += '</span><br/>';
				}

                                return '<small class="grey-text">'+data.user_name+'&nbsp;&nbsp;['+data.email+']'+'</small><br/>'+third_party_ids+data.text+verified_html+external_id_text;
                        }
		},
                formatSelection: function(data) {
                        if (data.status === 'verified')
                        {
                        		var external_id_text = data.externalId ? ' <small class="grey-text">EXTID: '+data.externalId+'</small>' : '';
                                return data.text+'&nbsp;<i class="material-icons io_icon_done" style="position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i> '+ external_id_text;
                        }else
                        {
                                return data.text;
                        }
		}
        });

        $("#mpq_org_name").change(function(){
		if ($(this).val() === 'unverified_advertiser_name')
		{
                        $("#unverified_advertiser_container").show();
                        $("#unverified_advertiser_container input").focus();
		}
		else
		{
			$('#tracking_tag_advertiser_warning').hide(0);
			$('#tracking_tag').show(0);
			$("#unverified_advertiser_container").hide();
			$("#unverified_advertiser_container input").val("");
                        var org_information = get_organization_information();
                        reset_tracking_tag_file_dropdown(org_information.source_table,org_information.advertiser_id);
		}
                $('#s2id_mpq_org_name a.select2-choice').removeClass('invalid_select');
                $(".tracking_tag_file_appender_text").hide('slow');
                $("#mpq_tracking_tag_new_container").hide();
                $("#mpq_tracking_tag_new_container input").val("");
                can_be_submitted = false;
                io_update_side_nav();
	});
    build_dfp_advertiser_dropdown();

	populate_advertiser_in_dropdown();
        $('#mpq_tracking_tag_file').select2({
		width: '100%' ,
		placeholder: "Select Tracking Tag File",
		minimumInputLength: 0,
		multiple: false,
		ajax: {
			url: "/tag/get_select2_tracking_tag_file_names/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
                                var id_value = $("#mpq_org_name").val();
                                var advertiser_id = -1;
                                var source_table = "";

                                if (id_value !== '' && id_value !== 'unverified_advertiser_name')
                                {
                                        var advertiser_info = get_advertiser_info();
                                        advertiser_id = advertiser_info.advertiser_id;
                                        source_table = advertiser_info.source_table;
                                }

				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 100,
					page: page,
                                        source_table:source_table,
                                        advertiser_id:advertiser_id
				};
			},results: function (data) {
				return {results: data.results, more: data.more};
			},error: function(jqXHR, textStatus, error){
                                console.log(error);
                        }
		},
                formatResult: function(data) {
                        if (data.text.indexOf("/") != -1)
                        {
                                var directory_name = data.text.substring(0,data.text.indexOf("/")+1);
                                var file_name = data.text.substring(data.text.indexOf("/")+1,(data.text.length-3));

                                return  '<span style="font-size:12px;font-weight: 400;">/'+directory_name+'</span>'
                                        +'<span style="font-weight:700;">'+file_name+'</span>'
                                        +'<span style="font-size:12px;font-weight: 400;">.js</span>';
                        }
                        else
                        {
                                return data.text;
                        }
                },
                formatSelection: function(data) {
                        if (data.text.indexOf("/") != -1)
                        {
                                var directory_name = data.text.substring(0,data.text.indexOf("/")+1);
                                var file_name = data.text.substring(data.text.indexOf("/")+1,(data.text.length-3));

                                return  '<span style="font-size:12px;font-weight: 400;">/'+directory_name+'</span>'
                                        +'<span style="font-weight:700;">'+file_name+'</span>'
                                        +'<span style="font-size:12px;font-weight: 400;">.js</span>';
                        }
                        else
                        {
                                return data.text;
                        }
                }
        });

        $("#mpq_tracking_tag_file").change(function(){
		if ($(this).val() === 'new_tracking_tag_file')
		{
                        //Prepare directory name
                        var id_value = $("#mpq_org_name").val();
                        if (id_value !== '' && id_value !== 'unverified_advertiser_name')
                        {
                                var advertiser_info = get_advertiser_info();
                                var advertiser_id = advertiser_info.advertiser_id;
                                var directory_name = get_advertiser_directory_name();
                                $("#mpq_tracking_info_section #tracking_file_prepend_string").html(directory_name+"/");
                                $("#mpq_tracking_tag_new_container").show();
                                $(".tracking_tag_file_appender_text").show('slow');
                                $("#mpq_tracking_tag_new_container input").focus();
                        }
		}
		else
		{
			$(".tracking_tag_file_appender_text").hide('slow');
                        $("#mpq_tracking_tag_new_container").hide();
			$("#mpq_tracking_tag_new_container input").val("");
                        $("#mpq_tracking_info_section #tracking_file_prepend_string").html("");
                        io_save_submit('save', true);
		}
                io_update_side_nav();
	});

        populate_tracking_tag_file_in_dropdown();

        $('.scrollspy').scrollSpy();

	$('.datepicker').pickadate({
		selectMonths: true,
		selectYears: 15,
		container: 'body',
		format: 'mm/dd/yyyy',
		onSet: function(c){
			if(c.select){
				this.close();
			}
		}
	});

	$('#io_flights_modal').on('change', '#io_add_flight_impression, input.io-single-flight-impressions', function(e){

		$(this).val(number_with_commas($(this).val()));
	});

	io_update_side_nav();

	$('#io_loading_mask').fadeOut();
    $('#mpq_tracking_tag_new').change(function(){
			io_save_submit('save', true);
            io_update_side_nav();
    });

	if (JSON.parse(rfp_raw_preload_location_object).length === 0)
	{
		$('#mpq_flight_info_section, #mpq_creative_info_section').addClass('hidden');
	}
});

window.onbeforeunload = function(e) {
	$.ajax({
		async: false,
		type: "POST",
		url: "/mpq_v2/unlock_mpq_session",
		data: {
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
		},
		error: function(jqXHR, textStatus, error){
		}
	});
};

function redirect_to_all_ios(submission_method)
{
	$('#io_redirect_to_all_ios_method').val(submission_method);
	$('#io_redirect_to_all_ios').submit();
}

function check_io_in_use()
{
	return $.ajax({
		type: "POST",
		url: '/mpq_v2/is_io_in_use',
		async: true,
		data: {
			mpq_id: mpq_id
		},
		error: function(jqXHR, textStatus, error){
			if (jqXHR.status === 409)
			{
				io_locked_redirect();
			}
			else
			{
				Materialize.toast('Ooops! There was an error processing your IO.', 46000, 'toast_top');
			}
		}
	});
}

function io_locked_redirect()
{
	$('#io_redirect_to_all_ios_modal').openModal();
	io_redirect_timeout_id = window.setTimeout(function(){
		redirect_to_all_ios('io_lock');
	}, 8000);
}

$('#io_redirect_to_all_ios_ok').click(function(e){
	e.preventDefault();
	redirect_to_all_ios('io_lock');
});

$('#io_submit_button').click(function(e){
	check_io_in_use().success(function(data){
		if(is_o_o_enabled())
		{
			$("#io_dfp_advertiser_modal").openModal();
		}
		else
		{
			io_save_submit('submit');
		}
	});
});

$('#io_dfp_advertiser_modal_ok').click(function(e)
{
	$("#s2id_mpq_dfp_advertiser a.select2-choice").removeClass('invalid');
	var dfp_advertiser_id = $("#mpq_dfp_advertiser").val();

	if(typeof dfp_advertiser_id === 'undefined')
	{
		$("#s2id_mpq_dfp_advertiser a.select2-choice").addClass('invalid');
		return;
	}

	$.ajax({
		type: "POST",
		url: '/mpq_v2/save_dfp_advertiser_to_io',
		async: true,
		dataType: 'json',			
		data: {
			dfp_advertiser_id: dfp_advertiser_id,
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success !== true)
			{
				if(typeof data.err !== 'undefined' && data.err !== '')
				{
					Materialize.toast('Ooops! There was a problem linking DFP advertiser : <br>- '+data.err, 46000, 'toast_top');
				}
			}
			else
			{	
				$("#io_dfp_advertiser_modal").closeModal();
				io_save_submit('submit');
			}
		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem creating a DFP advertiser.', 46000, 'toast_top');
		}
	});
});

$('#io_dfp_advertiser_modal_cancel').on('click', function(e){
	$('#io_dfp_advertiser_modal_ok').closeModal();
});

$('#io_submit_for_review_button').click(function(e){
	check_io_in_use().success(function(data){
		io_save_submit('submit_for_review');
	});
});

function io_save_submit(submission_type, silent)
{
	if(is_form_submitting_flag || (submission_type !== 'save' && submission_type !== 'submit' && submission_type !== 'submit_for_review'))
	{
		return;
	}

    io_update_side_nav(true);

    var org_information = get_organization_information();
    if (!reset_tracking_tag_file_dropdown(org_information.source_table,org_information.advertiser_id)){
            Materialize.toast('Tag file dropdown is reset', 46000, 'toast_top');
    }

	if(submission_type == "submit" || submission_type == "submit_for_review")
	{
		var validate_string = io_validate();
		if(validate_string !== "")
		{
			Materialize.toast('Ooops! It looks like you haven\'t filled out the IO completely:'+validate_string, 46000, 'toast_top');
			return;
		}
	}

    var tracking_tag_file_id = get_tracking_tag_file_id();
    if (tracking_tag_file_id === null)
    {
        tracking_tag_file_id = -1;
    }
	else if(tracking_tag_file_id == -1)
    {
        return;
    }

    var include_retargeting = $('#include_retargeting').is(":checked");

	var update_status_success = ajax_update_io_status(false);

	if(!update_status_success)
	{
		return;
	}

	var advertiser_id = org_information.advertiser_id;
	var advertiser_name = org_information.advertiser_name;
	var source_table = org_information.source_table;
	var old_source_table = $("#old_source_table").val();
	var old_tracking_tag_file_id = $("#old_tracking_tag_file_id").val();
	var website_name = $('#mpq_website_name').val();
	var order_name = $('#mpq_order_name').val();
	var order_id = $('#mpq_order_id').val();
	var industry = $('#industry_select').val();
	var presented_by = $('#account_executive_select').val();
	var iab_categories = encode_iab_category_data();
	var demographics = encode_demographic_data();
	var notes = $('#mpq_notes_input').val().replace(/\n/g, '<br/>');

	is_form_submitting_flag = true;

	$.ajax({
		type: "POST",
		url: '/proposal_builder/save_io',
		async: true,
		data:
		{
			advertiser_id: advertiser_id,
			advertiser_name: advertiser_name,
			source_table: source_table,
			website_name: website_name,
			order_name:order_name,
			order_id:order_id,
			industry: industry,
			selected_user_id: presented_by,
			iab_categories: iab_categories,
			demographics: demographics,
			io_status: io_side_nav_items,
			notes: notes,
			submission_type: submission_type,
			tracking_tag_file_id: tracking_tag_file_id,
			include_retargeting: include_retargeting,
			old_source_table:old_source_table,
			old_tracking_tag_file_id:old_tracking_tag_file_id
		},
		dataType: 'json',
		success: function(data, textStatus, jqXHR){
			is_form_submitting_flag = false;
			if(data.is_success === true)
			{
				if (!silent) Materialize.toast('Insertion order saved successfully.', 10000, 'toast_top', '', "Success!");
                if(submission_type == 'submit' || submission_type == "submit_for_review")
                {
                    redirect_to_all_ios(submission_type);
                }
                else{
                    reset_globals_and_update_form(data);
                }
			}
			else
			{
				Materialize.toast('Ooops! There was a problem saving the IO: <br>- '+data.errors, 46000, 'toast_top');
			}

		},
		error: function(jqXHR, textStatus, error){
			is_form_submitting_flag = false;
			Materialize.toast('Ooops! There was a problem saving the IO.', 46000, 'toast_top');
		}
	});
}

function io_reset_creatives_flights()
{
	$.ajax({
		type: "POST",
		url: '/mpq_v2/io_delete_all_timeseries_and_creatives',
		async: true,
		dataType: 'json',
		data: {
			mpq_id: mpq_id
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success !== true)
			{
				Materialize.toast('Ooops! There was a problem resetting your flights and creatives: <br>- '+data.errors, 46000, 'toast_top');
			}
			else if(data.session_expired === true)
			{
				$('#io_redirect_to_all_ios_modal').openModal();
				io_redirect_timeout_id = window.setTimeout(function(){
					redirect_to_all_ios('io_lock');
				}, 8000);
			}
		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem resetting your flights and creatives.', 46000, 'toast_top');
		}
	});

	$.each(io_product_info, function(key, value){
		$('#creatives_product_'+key+' .collapsible-body > .card-content').empty();
		$('#flights_product_'+key+' .collapsible-body > .card-content').empty();
		$('#flights_product_'+key+' .io_flights_defined_text .io_flights_defined_header_content').hide();
		$('#flights_product_'+key+' .io_flights_defined_text .io_flights_not_defined_yet').show();
		$('#creatives_product_'+key+' .io_creatives_defined_text .io_all_creatives_defined').hide();
		$('#creatives_product_'+key+' .io_creatives_defined_text .io_all_creatives_hold').hide();
//		$('#creatives_product_'+key+' .io_creatives_defined_text .io_creatives_not_defined_yet').show();
		$.each(io_flights.products[key], function(f_key, f_val){
			io_flights.products[key][f_key] = false;
		});
		$.each(io_creatives.products[key], function(f_key, f_val){
			io_creatives.products[key][f_key] = 0;
		});

		toggle_collapsible('#flights_product_'+key, false);
		toggle_collapsible('#creatives_product_'+key, false);
	});

	io_update_side_nav();
}

function toggle_collapsible(selector, is_open)
{
	$(selector).toggleClass('active', is_open);
	$(selector).find('.collapsible-body').toggle(is_open);
}

function validate_opportunity_details_for_io()
{
	var org_name = $('#mpq_org_name').val();
        var unverified_advertiser = $("#unverified_advertiser").val();
	var website  = $('#mpq_website_name').val();
	var industry = $('#industry_select').val();
	var presented_user = $('#account_executive_select').val();
	var order_name = $('#mpq_order_name').val();
	var is_success = true;

	if(!validate_text_name(org_name))
	{
		$('#s2id_mpq_org_name a.select2-choice').addClass('invalid_select');
		is_success = false;
	}
        if(org_name === 'unverified_advertiser_name' && !validate_text_name(unverified_advertiser))
	{
		$('#unverified_advertiser').addClass('invalid');
		is_success = false;
	}
	if(!validate_website(website))
	{
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
	if(!validate_text_name(order_name))
	{
		is_success = false;
	}
	return is_success;
}

function validate_and_create_unverified_advertiser(unverified_advertiser)
{
    var unverified_advertiser_info = null;

    if (typeof unverified_advertiser === 'undefined' || unverified_advertiser === '')
    {
        Materialize.toast('Ooops! There was a problem creating advertiser : <br>- Please provide advertiser name', 46000, 'toast_top');
        $('#unverified_advertiser').addClass('invalid');
        return unverified_advertiser_info;
    }

    $.ajax({
		type: "POST",
		url: '/mpq_v2/validate_and_create_unverified_advertiser',
		async: false,
		dataType: 'json',
		data: {
            unverified_advertiser:unverified_advertiser
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success !== true)
			{
				if(typeof data.errors !== 'undefined' && data.errors !== '')
                {
                    Materialize.toast('Ooops! There was a problem creating advertiser : <br>- '+data.errors, 46000, 'toast_top');
                }
			}
            else
            {
                unverified_advertiser_info = data.result;
                $("#old_io_advertiser_id").val(io_adv_id);
                $("#old_source_table").val(io_adv_source_table);
                io_adv_id = data.result.advertiser_id;
                io_adv_name = data.result.advertiser_name;
                io_adv_source_table = data.result.source_table;
                populate_advertiser_in_dropdown();
            }
		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem creating advertiser.', 46000, 'toast_top');
		}
	});

    if (unverified_advertiser_info !== null)
    {
        $('#unverified_advertiser').removeClass('invalid');
    }
    else
    {
        $('#unverified_advertiser').addClass('invalid');
    }

    return unverified_advertiser_info;
}

function validate_tracking_for_io()
{
	var mpq_tracking_tag_file = $('#mpq_tracking_tag_file').val();
    var mpq_tracking_tag_new = $('#mpq_tracking_tag_new').val();

    if ( (!validate_text_name(mpq_tracking_tag_file)) || (mpq_tracking_tag_file === 'new_tracking_tag_file' && !validate_text_name(mpq_tracking_tag_new)) )
	{
		return false;
	}
    else
    {
        return true;
    }
}


function get_organization_information()
{
    if(!validate_opportunity_details_for_io())
	{
		io_side_nav_items.opportunity.status = 'not_started';
	}
	else
	{
		io_side_nav_items.opportunity.status = 'done';
	}

    var id_value = $('#mpq_org_name').val();
    var unverified_advertiser = $("#unverified_advertiser").val();

    if (id_value === 'unverified_advertiser_name')
    {
        return validate_and_create_unverified_advertiser(unverified_advertiser);
    }
	else
	{
        return get_advertiser_info();
    }
}

function get_advertiser_info()
{
	var advertiser_info = {};

	if ($('#mpq_org_name').select2('data') != null)
	{
		advertiser_info.advertiser_id = $('#mpq_org_name').select2('data').id;
		advertiser_info.source_table = $('#mpq_org_name').select2('data').source_table;
		advertiser_info.advertiser_name = $('#mpq_org_name').select2('data').adv_name;
	}

	return advertiser_info;
}

function get_tracking_tag_file_id()
{
    var mpq_tracking_tag_file = $('#mpq_tracking_tag_file').val();
    var mpq_tracking_tag_new = $('#mpq_tracking_tag_new').val();
    var tracking_file_prepend_string = $('#tracking_file_prepend_string').html();

    if ( (!validate_text_name(mpq_tracking_tag_file)) || (mpq_tracking_tag_file === 'new_tracking_tag_file' && !validate_text_name(mpq_tracking_tag_new)) )
	{
		io_side_nav_items.tracking.status = 'not_started';
        return  null;
	}

    if(!validate_tracking_for_io())
	{
		io_side_nav_items.tracking.status = 'not_started';
	}
	else
	{
		io_side_nav_items.tracking.status = 'done';
	}

    if (mpq_tracking_tag_file === 'new_tracking_tag_file')
    {
        mpq_tracking_tag_new = mpq_tracking_tag_new.replace(/ /g,"_");
        if (mpq_tracking_tag_new == '' || !(/\S/.test(mpq_tracking_tag_new)) || mpq_tracking_tag_new.match(/^[^a-zA-Z0-9]+$/))
		{
			Materialize.toast('Ooops! There was a problem creating new tracking tag file : <br>- Invalid tracking tag file name', 46000, 'toast_top');
            return -1;
		}

        var advertiser_info = get_advertiser_info();
        $.ajax({
            type: "POST",
            url: '/tag/create_new_tracking_tag_file',
            async: false,
            dataType: 'json',
            data: {
                io_advertiser_id:advertiser_info.advertiser_id,
                source_table:advertiser_info.source_table,
                tracking_tag_file_name:(tracking_file_prepend_string + mpq_tracking_tag_new)
            },
            success: function(data, textStatus, jqXHR){
                if(data.is_success !== true)
                {
                    Materialize.toast('Ooops! There was a problem creating new tracking tag file : <br>- '+data.errors, 46000, 'toast_top');
                    mpq_tracking_tag_file = -1;
                }
                else
                {
                    mpq_tracking_tag_file = data.id;
                    $("#old_tracking_tag_file_id").val(tracking_tag_file_id);
                    tracking_tag_file_id = data.id;
                    tracking_tag_file_name = data.name;
                    populate_tracking_tag_file_in_dropdown();
                }
            },
            error: function(jqXHR, textStatus, error){
                Materialize.toast('Ooops! There was a problem creating new tracking tag file.', 16000, 'toast_top');
            }
        });
    }

    return mpq_tracking_tag_file;
}

function get_advertiser_directory_name()
{
    var advertiser_info = get_advertiser_info();
    var data_url = "/tag/get_advertiser_directory_name/";
    var directory_name = "";
    $.ajax({
        type: "POST",
        url: data_url,
        async: false,
        data: {
            adv_id:advertiser_info.advertiser_id,
            source_table:advertiser_info.source_table
        },
        dataType: 'json',
        success: function(result){
            if(result.status === "success")
            {
                directory_name = result.directory_name;
            }else{
                Materialize.toast('Ooops! There was a problem getting directory name for advertiser.', 16000, 'toast_top');
            }
        },
        error: function(jqXHR, textStatus, error){
            Materialize.toast('Ooops! There was a problem getting directory name for advertiser.', 16000, 'toast_top');
        }
    });

    return directory_name;
}

function reset_tracking_tag_file_dropdown(source_table,io_advertiser_id)
{
    var old_source_table = $("#old_source_table").val();
    var old_tracking_tag_file_id = $("#old_tracking_tag_file_id").val();
    var old_io_advertiser_id = $("#old_io_advertiser_id").val();
    var tracking_tag_file_id = $('#mpq_tracking_tag_file').val();

    //Reset tracking tag file dropdown in below scenarios.
    //1. Old advertiser was verified one and the newly selected is unverified and selected tracking tag file is same as old one.
    //2. Old advertiser was verified one and the newly selected is verified and selected tracking tag file is same as old one.
    //3. Old advertiser was unverified one and the newly selected is unverified and selected tracking tag file is same as old one.
    if (
        (
            (old_source_table === 'Advertisers' && source_table === 'advertisers_unverified')
                || (old_source_table === 'Advertisers' && source_table === 'Advertisers' && io_advertiser_id != old_io_advertiser_id)
                || (old_source_table === 'advertisers_unverified' && source_table === 'advertisers_unverified' && io_advertiser_id != old_io_advertiser_id)
        )
            &&
            (
                old_tracking_tag_file_id == tracking_tag_file_id)
    )
    {
        $('#mpq_tracking_tag_file').select2("val", "");
        if (tracking_tag_file_id != null && tracking_tag_file_id != '' && tracking_tag_file_id != 'new_tracking_tag_file'){
            return false;
        }
    }

    return true;
}

function populate_tracking_tag_file_in_dropdown()
{
    if (typeof tracking_tag_file_id != 'undefined' &&  tracking_tag_file_id != null && tracking_tag_file_id != ''
        && typeof tracking_tag_file_name != 'undefined' && tracking_tag_file_name != null && tracking_tag_file_name != '')
	{
		var tracking_tag_file_select2_option = {'id':tracking_tag_file_id,'text':tracking_tag_file_name};
        $('#mpq_tracking_tag_file').select2('data', tracking_tag_file_select2_option);
        $(".tracking_tag_file_appender_text").hide();
        $("#mpq_tracking_tag_new_container").hide();
        $("#mpq_tracking_tag_new_container input").val("");
        $("#mpq_tracking_info_section #tracking_file_prepend_string").html("");
	}
}

function populate_advertiser_in_dropdown()
{
    if (typeof io_adv_id != 'undefined' && io_adv_id != null && io_adv_id != ''
        && typeof io_adv_name != 'undefined' && io_adv_name != null && io_adv_name != '')
	{
		$('#tracking_tag_advertiser_warning').hide(0);
		$('#tracking_tag').show(0);

		var display_text = io_adv_name;

        if (typeof io_adv_source_table !='undefined' && io_adv_source_table === 'Advertisers')
        {
            display_text =  display_text+'&nbsp;<i class="material-icons io_icon_done" style="position:relative;top:7px;left:10px;margin-right:10px;">&#xE8E8;</i>';
        }

        var adv_org_name_select2_option = {'id':io_adv_id,'text':display_text,'adv_name':io_adv_name,'source_table':io_adv_source_table};
        $('#mpq_org_name').select2('data', adv_org_name_select2_option);
        $("#unverified_advertiser_container").hide();
		$("#unverified_advertiser_container input").val("");
	}
}

function reset_globals_and_update_form(result)
{
	var io_advertiser = $('#mpq_org_name').select2('data');
    io_adv_id = io_advertiser === null ? null : io_advertiser.id;
	io_adv_name = io_advertiser === null ? null : io_advertiser.adv_name;
	io_adv_source_table = io_advertiser === null ? null : io_advertiser.source_table;
    $("#old_source_table").val(io_adv_source_table);
    $("#old_io_advertiser_id").val(io_adv_id);

    if ($('#mpq_tracking_tag_file').select2('data') != null)
    {
        tracking_tag_file_id = $('#mpq_tracking_tag_file').select2('data').id;
        $("#old_tracking_tag_file_id").val(tracking_tag_file_id);

        if (typeof result.file_name != 'undefined' && result.file_name != null && result.file_name != '')
        {
            tracking_tag_file_name = result.file_name;
            populate_tracking_tag_file_in_dropdown();
        }
		else
		{
            tracking_tag_file_name = $('#mpq_tracking_tag_file').select2('data').text;
        }
    }
}

function determine_creative_readiness_flag(creative_ids)
{
	var ready = 2;
	$.each(creative_ids, function(key, value)
	{
		var show_for_io = value.split(';')[0];
		if(show_for_io != "0")
		{
			ready = 1;
		}
	});
	return ready;
}

function determine_product_status_for_product(product_id)
{
	var product_status = 2;
	$.each(io_creatives.products[product_id], function(key, value){
		if(value == 1)
		{
			product_status = 1;
		}
		else if(value == 0)
		{
			product_status = 0;
		}
	});
	switch(product_status)
	{
		case 0:
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_defined').hide();
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_hold').hide();
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_creatives_not_defined_yet').show();
			break;
		case 1:
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_defined').show();
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_hold').hide();
			break;
		case 2:
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_defined').hide();
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_hold').show();
			break;
		default:
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_defined').hide();
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_all_creatives_hold').hide();
			$('#creatives_product_'+product_id+' .io_creatives_defined_text .io_creatives_not_defined_yet').show();
	}
}

function product_has_creatives(product_id)
{
	var has_creatives = false;
	$.each(io_creatives.products[product_id], function(key, value){
		if(value != 0)
		{
			has_creatives = true;
		}
	});
	return has_creatives;
}

function build_dfp_advertiser_dropdown()
{
	$('#mpq_dfp_advertiser').select2({
		width: '100%' ,
		placeholder: "Select DFP Advertiser",
		minimumInputLength: 0,
		multiple: false,
		ajax: {
			url: "/mpq_v2/get_dfp_advertisers/",
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
				return {results: data};
			}
		}
	});
	$('#mpq_dfp_advertiser').change(function(){
		if($(this).val() == 'new_dfp_advertiser')
		{
			$("#dfp_advertiser_container").show();
			$("#dfp_advertiser_container input").focus();
		}
		else
		{
			$("#dfp_advertiser_container").hide();
			$("#dfp_advertiser_container input").val("");
		}
	});
}

function validate_and_create_dfp_advertiser(dfp_advertiser)
{
	if (typeof dfp_advertiser === 'undefined' || dfp_advertiser === '')
	{
		Materialize.toast('Ooops! There was a problem creating DFP advertiser : <br>- Please provide a valid advertiser name', 46000, 'toast_top');
		$('#dfp_advertiser_name').addClass('invalid');
		return false;;
	}

	$('#new_dfp_advertiser_loading_image').show();
	$.ajax({
		type: "POST",
		url: '/mpq_v2/create_dfp_advertiser',
		async: false,
		dataType: 'json',		
		data: {
			new_dfp_advertiser:dfp_advertiser
		},
		success: function(data, textStatus, jqXHR){
			if(data.is_success !== true)
			{
				if(typeof data.err !== 'undefined' && data.err !== '')
				{
					Materialize.toast('Ooops! There was a problem creating DFP advertiser : <br>- '+data.err, 46000, 'toast_top');
					$('#new_dfp_advertiser_loading_image').hide();
				}
			}
			else
			{	
				var new_advertiser = {'id':data.new_advertiser_id,'text':dfp_advertiser+" ("+data.new_advertiser_id+")"};
				$("#mpq_dfp_advertiser").select2('data',new_advertiser).trigger('change');
				$('#new_dfp_advertiser_loading_image').hide();
			}
		},
		error: function(jqXHR, textStatus, error){
			Materialize.toast('Ooops! There was a problem creating a DFP advertiser.', 46000, 'toast_top');
			$('#new_dfp_advertiser_loading_image').hide();
		}
	});
}

function is_o_o_enabled()
{
	if(io_submit_allowed)
	{
		var o_o_enabled = false;
		$.each(io_product_info, function(key, value)
		{
			if(value.o_o_enabled == 1)
			{
				o_o_enabled = true;
			}
		});
	}
	return o_o_enabled;
}