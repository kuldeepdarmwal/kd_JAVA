<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.96.1/css/materialize.min.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<script src="/assets/js/materialize_freq.min.js"></script>
<script type='text/javascript' src="/js/maps/markerclusterer.js"></script>
<script type='text/javascript' src="/js/maps/infobubble.js"></script>
<script type='text/javascript' src="/js/ajax_queue.js"></script>
<script type="text/javascript">
	var us_center = new google.maps.LatLng(39.8282239, -98.579569);
	var default_us_zoom = 4;
	var minimum_zoom_level_for_edit_map_mode = 9;
	var minimum_zoom_level_for_the_map = 2;
	var modify_region_ajax_queue = new AjaxQueue();
	var region_edit_array = {}; // Array of markers that show up in edit mode
	var region_type = 'zcta';
	var latlngbounds = new google.maps.LatLngBounds();
	var is_edit_mode = false;
	var selected_regions = {}; // Currently highlighted regions
	var active_regions = {}; // Regions currently being modified
	var add_and_remove_region_queue; // It's important that the ajax calls for modify regions be executed in order
	var marker_array;
	var marker_cluster_infobubble_creation_object = {
		shadowStyle: 0,
		padding: 10,
		backgroundColor: '#fff',
		borderWidth: 1,
		borderRadius: 4,
		borderColor: '#ccc',
		arrowStyle: 0,
		minWidth: 200,
		maxWidth: 300,
		minHeight: 100,
		maxHeight: 210
	};
	var marker_cluster_loading_creation_object = {
		shadowStyle: 0,
		hideCloseButton: true,
		padding: 3,
		backgroundColor: '#fff',
		borderWidth: 1,
		borderRadius: 4,
		borderColor: '#ccc',
		maxWidth: 30
	};
	var marker_cluster;
	var marker_cluster_infobubble;
	var marker_cluster_info_ajax_call = false;
	var marker_click_event;
	var marker_mouseover_event;
	var marker_mouseout_event;
	var marker_cluster_styles = [
		{ // smallest (1 digit ints)
			url: '/images/map_clusters/grey_large_circle.png',
			height: 58,
			width: 58,
			textColor: '#fff',
			textSize: 12
		}, {
			url: '/images/map_clusters/grey_large_circle.png',
			height: 58,
			width: 58,
			textColor: '#fff',
			textSize: 12
		}, {
			url: '/images/map_clusters/grey_large_circle.png',
			height: 58,
			width: 58,
			textColor: '#fff',
			textSize: 12
		}, {
			url: '/images/map_clusters/grey_large_circle.png',
			height: 58,
			width: 58,
			textColor: '#fff',
			textSize: 12
		}
	];
	var marker_cluster_grid_sizes = [
		null, // 0 zoom level
		null, // 1
		30, // 2
		60, // 3
		90, // 4
		180, // 5
		200, // 6
		300, // 7
		300, // 8
		300, // 9
		300, // 10
		300, // 11
		300, // 12
		300, // 13
		300, // 14
		300, // 15
		300, // 16
		300, // 17
		300, // 18
		300, // 19
		300 // 20
	];

	var marker_cluster_options = {
		averageCenter: true,
		minimumClusterSize: 1,
		zoomOnClick: false,
		calculator: get_marker_cluster_text,
		styles: marker_cluster_styles
	};

	// Create our "tiny" marker icon
	var addIcon = {
		url: "/images/ring_UI_add_button.png",
		size: new google.maps.Size(26,26),
		origin: new google.maps.Point(0,0),
		anchor: new google.maps.Point(13,13)
	};
	var subIcon = {
		url: "/images/ring_UI_minus_button.png",
		size: new google.maps.Size(26,26),
		origin: new google.maps.Point(0,0),
		anchor: new google.maps.Point(13,13)
	};
	var tiny_icon = {
		url: "/images/map_clusters/blue_small_circle_frequence.png",
		size: new google.maps.Size(5, 5),
		origin: new google.maps.Point(0, 0),
		anchor: new google.maps.Point(2.5, 2.5),
		scaledSize: new google.maps.Size(5, 5)
	};
	var loading_icon = {
		url: "/images/report_v2_loader.gif",
		scaledSize: new google.maps.Size(24, 24),
		origin: new google.maps.Point(0,0),
		anchor: new google.maps.Point(12, 12)
	};
	var geofence_marker_icons = {
		conquesting: {
			url: '/images/geofence_conquesting.png',
			scaledSize: new google.maps.Size(50, 50),
			origin: new google.maps.Point(0,0),
			anchor: new google.maps.Point(25, 25)
		},
		proximity: {
			url: '/images/geofence_proximity.png',
			scaledSize: new google.maps.Size(50, 50),
			origin: new google.maps.Point(0,0),
			anchor: new google.maps.Point(25, 25)
		}
	};

	var styles = [{"featureType":"landscape","elementType":"all","stylers":[{"saturation":"-100"},{"lightness":"100"}]},{"featureType":"poi","elementType":"all","stylers":[{"saturation":"100"},{"lightness":"63"},{"gamma":1},{"hue":"#53ff00"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"weight":"0.10"},{"lightness":"1"},{"hue":"#53ff00"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"hue":"#00b7ff"},{"saturation":-31.19999999999996},{"lightness":2.1803921568627374},{"gamma":1},{"weight":"0.70"},{"visibility":"simplified"}]},{"featureType":"road.local","elementType":"all","stylers":[{"saturation":-33.333},{"lightness":27.294117647058826},{"gamma":1}]},{"featureType":"water","elementType":"all","stylers":[{"hue":"#00B7FF"},{"saturation":8.4},{"lightness":36.4},{"gamma":1}]}];
	var centerLatLng = new google.maps.LatLng(37.6670334, -95.4679285);
	var myOptions =
	{
		center: centerLatLng,
		disableDefaultUI: true,
		mapTypeId: google.maps.MapTypeId.TERRAIN,
		panControl: false,
		panControlOptions:
		{
			position: google.maps.ControlPosition.TOP_RIGHT
		},
		scaleControl: true,
		zoomControl: true,
		zoomControlOptions:
		{
			style: google.maps.ZoomControlStyle.MEDIUM,
			position: google.maps.ControlPosition.RIGHT_TOP
		},
		scrollwheel: false
	};
	var feature_style = {
		strokeColor: "#f0f0f0",
		strokeOpacity: 0.75,
		strokeWeight: 1,
		fillColor: "#F96A0B",
		fillOpacity: 0.50,
		zIndex:1
	}
	var world_polygon;
	var world_mask_coordinates = [
		new google.maps.LatLng(-87, 120),
		new google.maps.LatLng(-87, -87),
		new google.maps.LatLng(-87, 0)
	];
	var world_polygon_create_object = {
		paths: world_mask_coordinates,
		strokeColor: '#111111',
		strokeOpacity: 0.9,
		strokeWeight: 1,
		fillColor: '#000000',
		fillOpacity: 0.42
	};
	var stroke_weight_for_zoom_level_table = [
		null, // 0 zoom level
		null, // 1
		0.02, // 2
		0.02, // 3
		0.02, // 4
		0.12, // 5
		0.13, // 6
		0.13, // 7
		0.13, // 8
		0.13, // 9
		0.25, // 10
		0.25, // 11
		0.75, // 12
		1.25, // 13
		1.50, // 14
		1.75, // 15
		2.50, // 16
		4.50, // 17
		7.00, // 18
		23.0, // 19
		30.0 // 20
	];
	var paths_obj = {'world': world_mask_coordinates};
	function load_map_with_data(data, is_overview_image)
	{
		if(map.polygons)
		{
			map.polygons.forEach(function(polygon, index, polygons)
			{
				map.data.remove(polygon);
				delete map.polygons[index];
			});
		}
		if(!data.is_big_map)
		{
			world_polygon.setMap(map);
			load_geojson_string(data.geojson_blob, true);
		}
		else
		{
			var features = JSON.parse(data.geojson_blob).features;
			marker_array = [];
			for(var geo_object_number in features)
			{
				var latlng = new google.maps.LatLng(
					features[geo_object_number].geometry.coordinates[1],
					features[geo_object_number].geometry.coordinates[0]
				);
				var title = features[geo_object_number].properties.region_id;
				var marker = new google.maps.Marker({
					title: title.toString(),
					position: latlng,
					icon: tiny_icon,
					map: map
				});
				marker_array.push(marker);
				latlngbounds.extend(latlng);
			}
			map.setCenter(latlngbounds.getCenter());
			map.fitBounds(latlngbounds);
			marker_cluster_options.gridSize = (is_overview_image) ? $('#map_canvas').height() : marker_cluster_grid_sizes[zoom];
			marker_cluster = new MarkerClusterer(map, marker_array, marker_cluster_options);
			initialize_map_clusterer_and_events();
		}
		google.maps.event.addListenerOnce(map, 'idle', function(){
			window.parent.$("#map_loading_image").hide("fast");
			window.parent.$("#region-links").fadeTo(200, 1);
		});
		map.setCenter(new google.maps.LatLng(map.getCenter().lat(), map.getCenter().lng() + .000000001));
	}
	function zoom(map)
	{
		var bounds = new google.maps.LatLngBounds();
		map.data.forEach(function(feature)
		{
			process_points(feature.getGeometry(), bounds.extend, bounds);
		});
		map.fitBounds(bounds);
	}
	function tightFitBounds(map, bounds, try_count)
	{
		var map_bounds,
		map_dimensions,
		bounds_dimensions,
		retry_interval = 50, // how long to wait before trying again to get the map bounds
		zoom_factor = 2; // each zoom level is 2x the magnification of the previous

		var get_bounds_dimensions = function(bounds)
		{
			var dimensions = {
				ne: bounds.getNorthEast(),
				sw: bounds.getSouthWest()
			};

			dimensions.height = dimensions.ne.lat() - dimensions.sw.lat();
			dimensions.width = dimensions.ne.lng() - dimensions.sw.lng();

			return dimensions;
		};

		if (!try_count)
		{
			map.fitBounds(bounds);
			try_count = 1;
		}

		// If map bounds aren't available yet, retry in a little bit. No documented map event for this.
		map_bounds = map.getBounds();
		if(!map_bounds)
		{
			setTimeout(function(){
				tightFitBounds(map, bounds, try_count + 1);
			}, retry_interval);
			return;
		}

		map_dimensions = get_bounds_dimensions(map_bounds);
		bounds_dimensions = get_bounds_dimensions(bounds);

		if(
			map_dimensions.height > bounds_dimensions.height * zoom_factor
			&& map_dimensions.width > bounds_dimensions.width * zoom_factor
		)
		{
			map.setZoom(map.getZoom() + 1);
		}
	}
	/**
	* Process each point in a Geometry, regardless of how deep the points may lie.
	* @param {google.maps.Data.Geometry} geometry The structure to process
	* @param {function(google.maps.LatLng)} callback A function to call on each
	*	LatLng point encountered (e.g. Array.push)
	* @param {Object} thisArg The value of 'this' as provided to 'callback' (e.g.
	*	myArray) https://developers.google.com/maps/documentation/javascript/examples/layer-data-dragndrop
	*/
	function process_points(geometry, callback, thisArg) {
		if (geometry instanceof google.maps.LatLng) {
			callback.call(thisArg, geometry);
		} else if (geometry instanceof google.maps.Data.Point) {
			callback.call(thisArg, geometry.get());
		} else {
			geometry.getArray().forEach(function(g) {
				process_points(g, callback, thisArg);
			});
		}
	}
	function get_paths_array_from_paths_object()
	{
		var path_array = [];
		for(region_name in paths_obj)
		{
			if(region_name != 'world')
			{
				path_array = path_array.concat(paths_obj[region_name])
			}
			else
			{
				path_array.unshift(paths_obj[region_name]);
			}
		};
		return path_array;
	}
	/**
	 * A function that turns on and off the edit mode option while also cleaning any markers that may have
	 * displayed during edit mode. It preserves the list of selected_regions and polygons.
	 *
	 */
	function toggle_edit()
	{
		if(is_edit_mode)
		{
			for (var key in region_edit_array)
			{
				region_edit_array[key].setMap(null);
				delete(region_edit_array[key]);
			}
			is_edit_mode = false;
			$('#editBut').attr("data-tooltip", 'Edit regions');
			$('#editBut i').html("&#xE254;");
			//$('#editBut i').html("mode_edit");
		}
		else
		{
			is_edit_mode = true;
			$('#editBut').attr("data-tooltip", 'Finish editing');
			$('#editBut i').html("&#xE876;");
			//$('#editBut i').html("done");
			load_edit_mode();
		}
	}
	function generate_path_polys_from_coordinates(new_boundaries, coordinates, region_name)
	{
		for(var j = 0; j < coordinates.length; j++)
		{
			paths_obj[region_name].push(coordinates[j].map(function(arr){
				var latlng = new google.maps.LatLng(arr[1], arr[0]); // need check to make sure no undefined values get assigned in here
				new_boundaries = new_boundaries.extend(latlng);
				return latlng;
			}));
		}
	}
	function load_geojson_string(geojson_blob, should_refit_map)
	{
		var new_boundaries = new google.maps.LatLngBounds();
		var geojson = JSON.parse(geojson_blob);
		for(var i = 0; i < geojson.features.length; i++)
		{
			var region_name = geojson.features[i].properties.region_id;
			if(!(region_name in selected_regions))
			{
				selected_regions[region_name] = true;
			}
			if(!(region_name in paths_obj))
			{
				paths_obj[region_name] = [];
				if(geojson.features[i].geometry.type == 'MultiPolygon')
				{
					var polygons = geojson.features[i].geometry.coordinates;
					for(var k = 0; k < polygons.length; k++)
					{
						generate_path_polys_from_coordinates(new_boundaries, polygons[k], region_name);
					}
				}
				else
				{
					var coordinates = geojson.features[i].geometry.coordinates;
					generate_path_polys_from_coordinates(new_boundaries, coordinates, region_name);
				}
			}
		}
		world_polygon.setPaths(get_paths_array_from_paths_object());
		if(should_refit_map)
		{
			if(window.location.hash === '#snug')
			{
				tightFitBounds(map, new_boundaries);
			}
			else
			{
				map.fitBounds(new_boundaries);
			}
			world_polygon.setOptions({strokeWeight: stroke_weight_for_zoom_level_table[map.getZoom()]});
		}
	}
	/**
	 * Draws polygons for a region by calling vertex data from the lap_edit controller.
	 *
	 * @param region_id is the region ID for the region to draw
	 * @param region_type is the mode in which we are going to run the query for region. By default it is set to "Z" (Zip Code)
	 */
	function draw_region(region_id, region_type)
	{
		if(region_type === undefined) region_type = 'zcta';
		$.ajax(
		{
			url: '/maps/get_region_geojson_to_draw',
			type: 'POST',
			dataType: 'json',
			async: true,
			data:
			{
				region_id: [region_id],
				region_type: region_type
			},
			success : function(data, textStatus, jqXHR)
			{
				var success = (in_iframe()) ? parent.vl_is_ajax_call_success(data) : vl_is_ajax_call_success(data);
				if(success)
				{
					if(region_id in selected_regions || region_edit_array[region_id].getIcon() == addIcon) //***FLAG*** needs to be changed to proper icon detection
					{
						return; //this helps to protect against violent clicking
					}
					load_geojson_string(data.region_blob);
				}
				else
				{
					if(in_iframe())
						parent.vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
					else
						vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				if(in_iframe())
					parent.vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				else
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
			}
		});
	}
	function create_region_edit_handle(region_latlng, region_title)
	{
		if (!(region_title in region_edit_array) && is_edit_mode)
		{
			if (region_title in selected_regions)
			{
				var marker = new google.maps.Marker({
					title: region_title,
					is_selected: true,
					map: map,
					position: region_latlng,
					optimized: false,
					icon: subIcon
				});
			}
			else
			{
				var marker = new google.maps.Marker({
					title: region_title,
					is_selected: false,
					map: map,
					position: region_latlng,
					optimized: false,
					icon: addIcon
				});
			}
			google.maps.event.addListener(marker, 'click', function()
			{
				if(active_regions[marker.getTitle()] == null)
				{
					active_regions[marker.getTitle()] = true;
					var action = "";
					var zip = "";
					marker.setIcon(loading_icon);
					if(selected_regions[marker.getTitle()] != null)
					{
						// then turn off the zip code. #turnoff #switchoff
						action = "remove_zipcode";
						zip = "" + marker.getTitle();
						delete(selected_regions[marker.getTitle()]);
						delete(paths_obj[marker.getTitle()]);
						if(Object.size(selected_regions) == 0)
						{
							world_polygon.setMap(null);
						}
						world_polygon.setPaths(get_paths_array_from_paths_object());
					}
					else
					{
						// then turn on the zip code #turnon #switchoff
						action = "add_zipcode";
						marker.setIcon(loading_icon);
						draw_region(marker.getTitle(), 'zcta');
						zip = "" + marker.getTitle();
						if(!!world_polygon.getMap() === false)
						{
							world_polygon.setMap(map);
						}
					}
					if(page == 'mpq')
					{
						var controller = '/mpq';
						var post_data = {
							location_id: location_id,
							action: action,
							zip: zip
						}
					}
					else
					{
						var controller = '/lap_lite';
						var post_data = {
							action: action,
							zip: zip
						}
					}
					modify_region_ajax_queue.push(
					{
						url: controller + '/modify_zipcodes',
						type: 'POST',
						dataType: 'json',
						async: true,
						data: post_data,
						success : function(data, textStatus, jqXHR)
						{
							active_regions[marker.getTitle()] = null;
							if(data.is_success)
							{
								if(page == 'mpq')
								{
									demo_graphics(data);
									update_geo_stats(data.population, data.income, data.target_region);
								}
								else if(page == 'planner')
								{
									var demo_iframe = "<iframe id='iframe-demo' style='position:absolute; left:10px;' src='/lap_lite/demos' seamless='seamless' height='570' width='420'frameborder='0'></iframe>";
									parent.document.getElementById("sliderBodyContent_geo").innerHTML = demo_iframe;

									var current_zips_value = parent.$('textarea#manual_zips').val();
									var current_zips = current_zips_value.trim().replace(/\s/g, '');
									var current_zips_array = current_zips.split(',');
									if(action == 'add_zipcode')
									{
										if(!(current_zips_array.indexOf(zip) > -1))
										{
											current_zips_array.push(zip);
											current_zips_array = current_zips_array.filter(Boolean);
										}
									}
									else
									{
										var index = current_zips_array.indexOf(zip);
										if(index > -1)
										{
											current_zips_array.splice(index, 1);
											current_zips_array = current_zips_array.filter(Boolean);
										}
									}
									parent.$('#manual_zips').val(current_zips_array.join(', '));
								}
								if(action == 'add_zipcode')
								{
									marker.setIcon(subIcon);
									marker.is_selected = true;
								}
								else
								{
									marker.setIcon(addIcon);
									marker.is_selected = false;
								}
								if(typeof window.parent.update_zip_set !== "undefined")
								{
									window.parent.update_zip_set(action, zip);
								}
							}
							else
							{
								if(typeof(data.errors) !== "undefined")
								{
									var formatted_errors = '<div>';
									for(var ii = 0; ii < data.errors.length; ii++)
									{
										formatted_errors += '<div>'+data.errors[ii]+'</div>';
									}
									formatted_errors += '</div>';
									$("#map_view_errors").append(formatted_errors);
								}
								else
								{
									$("#map_view_errors").append('<div>Unknown update zips error (#987401)</div>');
								}
							}
						},
						error: function(jqXHR, textStatus, errorThrown)
						{
							active_regions[marker.getTitle()] = null;
							var formatted_errors = '<div>' + jqXHR.responseText + '</div>';
							$("#map_view_errors").append(formatted_errors);
							alert('modify_zips error');
						}
					});
				}
			});
			region_edit_array[marker.getTitle()] = marker;
		}
	}
	function load_edit_mode()
	{
		if(map.getZoom() < minimum_zoom_level_for_the_map)
			return;
		var bounds = map.getBounds();
		var ne_lat = bounds.getNorthEast().lat();
		var ne_lng = bounds.getNorthEast().lng();
		var sw_lat = bounds.getSouthWest().lat();
		var sw_lng = bounds.getSouthWest().lng();
		var region_type = 'zcta';
		$.ajax(
		{
			url: '/maps/get_all_region_centers_in_given_area',
			type: 'POST',
			dataType: 'json',
			async: true,
			data:
			{
				region_type: region_type,
				ne_lat: ne_lat,
				ne_lng: ne_lng,
				sw_lat: sw_lat,
				sw_lng: sw_lng
			},
			success : function(data, textStatus, jqXHR)
			{
				var success = (in_iframe()) ? parent.vl_is_ajax_call_success(data) : vl_is_ajax_call_success(data);
				if(success)
				{
					for(region_id in data.centers)
					{
						if(!(region_id in region_edit_array))
						{
							create_region_edit_handle(new google.maps.LatLng(data.centers[region_id].latitude, data.centers[region_id].longitude), region_id);
						}
					}
				}
				else
				{
					if(in_iframe())
						parent.vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
					else
						vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				if(in_iframe())
					parent.vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				else
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
			}
		});
	}
	function handle_grid_zoom_size(zoom)
	{
		marker_cluster.setGridSize(marker_cluster_grid_sizes[zoom]);
		marker_cluster.repaint();
	}
	function get_cluster_infowindow_text(cluster, regions)
	{
		if(marker_cluster_infobubble && marker_cluster_infobubble.isOpen)
		{
			marker_cluster_infobubble.close();
			marker_cluster_infobubble = null;
		}
		marker_cluster_infobubble = new InfoBubble(marker_cluster_loading_creation_object)
		marker_cluster_infobubble.setPosition(cluster.getCenter());
		marker_cluster_infobubble.setContent('<img src="/images/report_v2_loader.gif" height="30" width="30"/>');
		marker_cluster_infobubble.open(map);
		if(region_type === undefined) region_type = 'zcta';
		if(marker_cluster_info_ajax_call !== false) marker_cluster_info_ajax_call.abort();
		marker_cluster_info_ajax_call = $.ajax(
		{
			url: '/maps/get_marker_cluster_info',
			type: 'POST',
			dataType: 'json',
			async: true,
			data:
			{
				region_ids: regions.join(),
				region_type: region_type
			},
			success : function(data, textStatus, jqXHR)
			{
				marker_cluster_info_ajax_call = false;
				marker_cluster_infobubble.close();
				var success = (in_iframe()) ? parent.vl_is_ajax_call_success(data) : vl_is_ajax_call_success(data);
				if(success)
				{
					var html_content = "<div>";
					var zip_noun = (cluster.getSize() == 1) ? " zip" : " zips";
					html_content += "<p>" + cluster.getSize() + " " + zip_noun + "</p>";
					var county_noun = (data.lists.num_counties == 1) ? " county" : " counties";
					html_content += "<p>" + data.lists.num_counties + county_noun + ":</p>";
					html_content += "<p>" + data.lists.county_list + "</p>";
					var state_noun = (data.lists.num_states == 1) ? " state" : " states";
					html_content += "<p>" + data.lists.num_states + state_noun + ":</p>";
					html_content += "<p>" + data.lists.state_list + "</p></div>";
					marker_cluster_infobubble = new InfoBubble(marker_cluster_infobubble_creation_object);
					marker_cluster_infobubble.setPosition(cluster.getCenter());
					marker_cluster_infobubble.setContent(html_content);
					marker_cluster_infobubble.open(map);
				}
				else
				{
					if(in_iframe())
						parent.vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
					else
						vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				marker_cluster_info_ajax_call = false;
				if(textStatus == 'abort') return;
				if(in_iframe())
					parent.vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
				else
					vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
			}
		});
	}
	function get_marker_cluster_text(markers)
	{
		var num_markers = markers.length;
		var index = ((num_markers.toString().length)); // returns the index of the marker cluster style array for this cluster; based on number of digits in the int, NOT 0 INDEXED
		var text = num_markers.toString() + "<br/>zip";
		text += (num_markers == 1) ? "" : "s";
		var styles = "line-height:10px;position:relative;top:18px;"; // default styles for all divs
		return {text: '<div style="' + styles + '">' + text + '</div>', index: index};
	}
	function edit_button_zoom_actions(zoom)
	{
		var edit_button = $('#editBut');
		if(is_edit_mode && zoom < minimum_zoom_level_for_edit_map_mode)
		{
			// Turn edit mode off
			toggle_edit();
			edit_button.addClass("disabled");
			edit_button.off("click");
			edit_button.attr("data-tooltip", 'To edit, zoom in');
		}
		else if(is_edit_mode && zoom >= minimum_zoom_level_for_edit_map_mode)
		{
			load_edit_mode();
		}
		else if(!is_edit_mode && zoom < minimum_zoom_level_for_edit_map_mode)
		{
			edit_button.addClass("disabled");
			edit_button.off("click");
			edit_button.attr("data-tooltip", 'To edit, zoom in');
		}
		else if(!is_edit_mode && zoom >= minimum_zoom_level_for_edit_map_mode)
		{
			edit_button.removeClass("disabled");
			edit_button.off("click");
			edit_button.on("click", toggle_edit);
			edit_button.attr("data-tooltip", 'Edit regions');
		}
	}
	function initialize_shared_map_events(is_big_map)
	{
		if(is_big_map)
		{
			google.maps.event.addListener(marker_cluster, 'clusteringend', function (c){
				for(var i = 0; i < marker_array.length; i++)
				{
					marker_array[i].setMap(map);
				}
			});
		}
		google.maps.event.addDomListener(window, 'resize', function(){
			var center = map.getCenter();
			google.maps.event.trigger(map, "resize");
			map.setCenter(center);
		});
	}
	function initialize_map_clusterer_and_events()
	{
		if(!(marker_click_event && marker_mouseover_event && marker_mouseout_event))
		{
			marker_click_event = google.maps.event.addListener(marker_cluster, 'click', function(c) {
				var markers = c.getMarkers();
				var titles = [];
				markers.forEach(function(marker){
					titles.push(marker.title);
				});
				get_cluster_infowindow_text(c, titles);
			});
		}
	}

	function create_geofence_marker(geofence_object)
	{
		var latng_object = {lat: geofence_object.latlng[0], lng: geofence_object.latlng[1]};
		var latlng = new google.maps.LatLng(latng_object);
		var title = geofence_object.search_term + " - " + geofence_object.radius + " meters";
		var marker = new google.maps.Marker({
			title: title.toString(),
			position: latlng,
			icon: geofence_marker_icons[geofence_object.type],
			map: map,
			optimized: false
		});
	}

</script>

<script type="text/javascript">
	Object.size = function(obj)
	{
		var size = 0, key;
		for (key in obj)
		{
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	};
	function in_iframe()
	{
		try
		{
			return window.self !== window.top;
		}
		catch (e)
		{
			return true;
		}
	}
</script>

<?php $this->load->view('vl_platform_2/ui_core/js_error_handling'); write_vl_platform_error_handlers_js(); ?>
