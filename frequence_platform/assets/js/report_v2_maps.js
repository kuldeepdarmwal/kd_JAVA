/**
 * The class for handling maps on the /report page
 * Handles the overview map and heat maps
 *
 * @param {string} map_container, the selector in which all map html will go
 * @param {string} type, the map type ('overview', 'heatmap')
 * @return {object} ReportMap, the object to handle map functionality
 */
function ReportMap(map_container, type)
{
	this.map_container = map_container;
	this.map_type = type;
	this.map = null;
	this.map_geojson = null;
	this.map_multilocation_polygons = null;
	this.map_multilocation_polygons_hash = {};
	this.map_multilocation_polygons_ids = [];
	this.map_multilocation_polygons_loaded = 0;
	this.map_multilocation_polygons_complexity = null;
	this.map_multilocation_map_tags = null;
	this.map_multilocation_map_tags_objects = {};
	this.geofencing_markers_for_main_map = null;
	this.count_complexity_levels = 3;
	this.map_div_id = null;
	this.multilocation_polygons_ajax = false;
	this.map_form_loaded = false;
	this.map_main_geography_type = "point";
	this.has_product_impression_totals = false;
	this.hovered_syscodes = [];
	this.selected_syscodes = [];
	this.syscode_data_objects = {};
	this.zoom_with_menu = false;

	var self = this;
	var polygon_default_creation_object = {
		fill: true,
		weight: 1,
		color: '#fff',
		fillOpacity: 0.8
	};

	var active = $("<div class='report_map_active_polygon'></div>").hide().appendTo("body");
	var inactive = $("<div class='report_map_inactive_polygon'></div>").hide().appendTo("body");
	this.active_color = active.css("color");
	this.inactive_color = inactive.css("color");
	this.active_hex_color = convert_to_hex(this.active_color);
	this.inactive_hex_color = convert_to_hex(this.inactive_color);
	active.remove();
	inactive.remove();

	var active_image_url = '/maps/get_marker_for_map/' + encodeURIComponent(this.active_hex_color);
	var inactive_image_url = '/maps/get_marker_for_map/' + encodeURIComponent(this.inactive_hex_color);

	var heatmap_marker_url = '/maps/get_marker_for_map/' + encodeURIComponent(this.active_hex_color) + '/6/';

	var preload_active_marker = document.createElement('img');
	preload_active_marker.src = active_image_url;
	var preload_inactive_marker = document.createElement('img');
	preload_inactive_marker = inactive_image_url;

	this.available_products = {};

	this.region_polygon_ajax_queue = new AjaxQueue({concurrency: 5});
	this.complexity_queue = {};

	this.geofencing_zoom_map = null;

	this.activated_map_marker_icon_object = {
		iconUrl: active_image_url,
		iconSize: [10, 10],
		iconAnchor: [5, 5],
		popupAnchor: [5, 5]
	};
	this.deactivated_map_marker_icon_object = {
		iconUrl: inactive_image_url,
		iconSize: [10, 10],
		iconAnchor: [5, 5],
		popupAnchor: [5, 5]
	};
	this.activated_map_tag_icon_object = {
		'marker-color': this.active_hex_color
	};
	this.deactivated_map_tag_icon_object =
	{
		'marker-color': this.inactive_hex_color
	};
	var heatmap_marker_icon_object = {
		iconUrl: heatmap_marker_url,
		iconSize: [10, 10],
		iconAnchor: [5, 5],
		popupAnchor: [5, 5]
	};

	/**
	 * These functions are used in the setStyle and style properties for Mapbox styling.
	 * Style functions are saved with map_type as the key.
	 *
	 * @param {object} feature, the feature object from Mapbox
	 * @return {object} style_object, the object with properties defined by the Path abstract class for Mapbox
	 */
	this.style_functions = {
		heatmap: function(feature)
		{
			var style_object = jQuery.extend({}, polygon_default_creation_object);

			style_object.fillColor = self.active_color;
			style_object.fillOpacity = feature.properties.percentage;

			return style_object;
		},
		overview: function(feature)
		{
			var style_object = jQuery.extend({}, polygon_default_creation_object);
			style_object.fillColor = self.inactive_color;

			if(self.is_feature_selected_from_form(feature.properties))
			{
				style_object.fillColor = self.active_color;
			}

			return style_object;
		},
		zone: function(feature)
		{
			var style_object = jQuery.extend({}, polygon_default_creation_object);
			style_object.fillColor = self.inactive_color;
			style_object.fillOpacity = 0.2;

			var combined_array = self.hovered_syscodes.concat(self.selected_syscodes);
			var unique = combined_array.filter(function(value, index, self){
				return self.indexOf(value) === index;
			});

			var num_common_elements = get_num_common_elements(feature.properties.syscodes, unique);

			if(num_common_elements)
			{
				style_object.fillColor = self.active_color;

				var new_opacity = style_object.fillOpacity = 0.2;
				if(combined_array.length > 1)
				{
					new_opacity = Math.min(style_object.fillOpacity * num_common_elements, 0.8);
				}

				style_object.fillOpacity = (new_opacity > 1) ? 1 : new_opacity;
			}
			if(self.hovered_syscodes.length > 0)
			{
				if(get_num_common_elements(feature.properties.syscodes, self.hovered_syscodes))
				{
					style_object.fillOpacity = 1;
					style_object.fillColor = self.active_color;
				}
			}
			return style_object;
		},
		lift: function(feature)
		{
			var style_object = jQuery.extend({}, polygon_default_creation_object);
			style_object.fillColor = "#FC3030";
			style_object.color = "#FC3030";
			style_object.fillOpacity = feature.properties.percentage_opacity;

			return style_object;
		}
	};

	/**
	 * These functions are used in pointToLayer for Mapbox feature handling.
	 * pointToLayer functions are saved with map_type as the key.
	 *
	 * @param {object} feature, the feature object from Mapbox
	 * @param {object} latlng, The latitude, longitude position of the marker
	 * @return {object}, L.Marker object with latitude, longitude, and a styled marker
	 */
	this.point_to_layer_functions = {
		heatmap: function(feature, latlng)
		{
			var individual_heatmap_marker_icon_object = jQuery.extend({}, heatmap_marker_icon_object);
			var marker_percentage = feature.properties.percentage;
			individual_heatmap_marker_icon_object.iconUrl += marker_percentage;
			var marker = L.marker(latlng,{
				icon: L.icon(individual_heatmap_marker_icon_object),
				title: feature.properties.region_id,
				riseOnHover: true
			});

			self.map_multilocation_polygons_ids.push(feature.properties.region_id);
			return marker;
		},
		overview: function(feature, latlng)
		{
			var marker = L.marker(latlng,{
				icon: L.icon(self.activated_map_marker_icon_object),
				title: feature.properties.region_id,
				riseOnHover: true
			});

			self.map_multilocation_polygons_ids.push(feature.properties.region_id);
			return marker;
		},
		zone: function(feature, latlng)
		{
			var marker = L.marker(latlng,{
				icon: L.icon(self.deactivated_map_marker_icon_object),
				title: feature.properties.region_id,
				riseOnHover: true
			});

			self.map_multilocation_polygons_ids.push(feature.properties.region_id);
			return marker;
		},
		lift: function(feature, latlng)
		{
			var marker = L.marker(latlng, {
				icon: L.icon(self.deactivated_map_marker_icon_object),
				title: feature.properties.region_id,
				riseOnHover: true
			});
			self.map_multilocation_polygons_ids.push(feature.properties.region_id);
			return marker;
		}
	};

	/**
	 * These functions are used in onEachFeature for Mapbox feature handling.
	 * onEachFeature functions are saved with map_type as the key.
	 *
	 * @param {object} feature, the feature object from Mapbox
	 * @param {object} layer, the layer object from Mapbox
	 * @return {void}
	 */
	this.on_each_feature_functions = {
		heatmap: function(feature, layer)
		{
			if(feature.geometry.type != 'Point')
			{
				self.map_multilocation_polygons_hash[feature.properties.region_id] = layer;
			}

			layer.on('mouseover', function() {
				if(layer.setStyle)
				{
					layer.setStyle({color: '#666666'});
					layer.bringToFront();
				}

				display_location_data_on_mouseover.apply(self, [layer]);
			});

			layer.on('mouseout', function(){
				if(layer.setStyle)
				{
					layer.setStyle(self.style_functions.heatmap(layer.feature));
				}
			});
		},
		overview: function(feature, layer)
		{
			if(feature.geometry.type != 'Point')
			{
				self.map_multilocation_polygons_hash[feature.properties.region_id] = layer;
			}

			// Check for availability of product on map by seeing if at least one region belongs to a given product
			for(var product in self.available_products)
			{
				if(self.available_products.hasOwnProperty(product))
				{
					// Only check permissions if permission has not been granted for given product
					if(!self.available_products[product])
					{
						// Turn permission on if the product is true for the given feature
						self.available_products[product] = (feature.properties[product]) ? true : false;
					}
				}
			}

			set_up_multi_location_markers(self, feature, layer.getLatLngs ? layer.getLatLngs() : layer.getLatLng());

			layer.on('mouseover', function() {
				if(layer.setStyle)
				{
					layer.setStyle({color: '#666666'});
					layer.bringToFront();
				}

				display_location_data_on_mouseover.apply(self, [layer]);
			});

			layer.on('mouseout', function(){
				if(layer.setStyle)
				{
					layer.setStyle(self.style_functions.overview(layer.feature));
				}
			});
		},
		zone: function(feature, layer)
		{
			if(feature.geometry.type != 'Point')
			{
				self.map_multilocation_polygons_hash[feature.properties.region_id] = layer;
			}
		},
		lift: function(feature, layer)
		{
			if(feature.geometry.type != 'Point')
			{
				self.map_multilocation_polygons_hash[feature.properties.region_id] = layer;
			}

			layer.on('mouseover', function()
			{
				$("#map_menu_for_" + self.map_div_id + ":hidden").show();
				if(layer.setStyle)
				{
					layer.setStyle({color: '#666666', weight: '3'});
					layer.bringToFront();
				}

				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").empty();
				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").append(jQuery('<span/>', { id: 'loc_span_1', html: "<b>Zip</b>: " + feature.properties.region_id}));
				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").append(jQuery('<span/>', { id: 'loc_span_2', html: "<b>City</b>: " + (feature.properties.city)}));
				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").append(jQuery('<span/>', { id: 'loc_span_3', html: "<b>Population</b>: " + (feature.properties.population_total || "0")}));

				$("#map_menu_for_" + self.map_div_id + " .lift_data").empty();


				var avg_lift = feature.properties.avg_lift;
				var avg_conversion_rate = feature.properties.avg_conversion_rate + "% Conversion Rate";
				var avg_baseline = "vs " + feature.properties.avg_baseline + "% UNEXPOSED";
				var is_lift_positive = true;

				if (!avg_lift || avg_lift <= 0)
				{
					avg_lift = "<span style='font-size:11px;font-style:italic;font-weight:normal;line-height:0.8rem'>Additional data required to calculate lift</span>";
					avg_conversion_rate = "--% Conversion Rate";
					avg_baseline = "vs --% UNEXPOSED";
				}else if(avg_lift > 99)
				{
					avg_lift = "> 99x LIFT";
				}else
				{
					avg_lift = avg_lift + "x LIFT";
				}



				$("#map_menu_for_" + self.map_div_id + " .lift_data").append(jQuery('<span/>', { 'id': 'dat_span_1', 'html': avg_lift}));
				//$("#map_menu_for_" + self.map_div_id + " .lift_data").append(jQuery('<span/>', { 'id': 'dat_span_2', 'html': avg_conversion_rate}));
				//$("#map_menu_for_" + self.map_div_id + " .lift_data").append(jQuery('<span/>', { 'id': 'dat_span_3', 'html': avg_baseline}));
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").empty();

				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_1', 'html': "<b>Avg Home Value</b>: $" + feature.properties.avg_home_value}));
				if (is_lift_positive)
				{
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
						'id': "dem_span_2",
						'class':'percentage',
						'html': "<small><b>" + "<i class='fa' aria-hidden='true'></i>" + feature.properties.percentage_avg_home_value + "% avg</b></small>"
					}));
				}

				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
					'id': 'dem_span_3',
					'html': "<b>Median Age</b>: " + "<i class='fa' aria-hidden='true'></i>&nbsp;" + feature.properties.median_age
				}));

				if (is_lift_positive)
				{
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
						'id': 'dem_span_4',
						'class':'percentage',
						'html': "<small><b>" + "<i class='fa' aria-hidden='true'></i>" + feature.properties.percentage_median_age + "% avg</b></small>"
					}));
				}

				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
					'id': 'dem_span_5',
					'html': "<b>Avg Income</b>: " + "<i class='fa' aria-hidden='true'></i>&nbsp;$" + feature.properties.avg_income
				}));

				if (is_lift_positive)
				{
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
						'id': 'dem_span_6',
						'class':'percentage',
						'html': "<small><b>" + "<i class='fa' aria-hidden='true'></i>" + feature.properties.percentage_avg_income + "% avg</b></small>"
					}));
				}

				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
					'id': 'dem_span_7',
					'html': "<b>Avg # Per Household</b>: " + "<i class='fa' aria-hidden='true'></i>&nbsp;" + feature.properties.avg_per_household
				}));

				if (is_lift_positive)
				{
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', {
						'id': 'dem_span_8',
						'class':'percentage',
						'html': "<small><b>" + "<i class='fa' aria-hidden='true'></i>" +  feature.properties.percentage_avg_per_household + "% avg</b></small>"
					}));
				}

				var pos = "#45A938";
				var neg = "#D21C2C";
				var neutral = "#FFFFFF";

				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info small").css("color", pos);
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info small:contains('-')").css("color", neg);
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info small i").addClass("fa-arrow-up");
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info small:contains('-')").find("i").removeClass("fa-arrow-up").addClass("fa-arrow-down");

				if (feature.properties.percentage_avg_home_value < 5 && feature.properties.percentage_avg_home_value > -5){
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info #dem_span_2 small").css("color", neutral);
				}
				if (feature.properties.percentage_median_age < 5 && feature.properties.percentage_median_age > -5){
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info #dem_span_4 small").css("color", neutral);
				}
				if (feature.properties.percentage_avg_income < 5 && feature.properties.percentage_avg_income > -5){
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info #dem_span_6 small").css("color", neutral);
				}
				if (feature.properties.percentage_avg_per_household < 5 && feature.properties.percentage_avg_per_household > -5){
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info #dem_span_8 small").css("color", neutral);
				}
			});

			layer.on('mouseout', function(){
				$("#map_menu_for_" + self.map_div_id + ":hidden").show();
				if(layer.setStyle)
				{
					layer.setStyle(self.style_functions.lift(layer.feature));

				}

				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").empty();
				$("#map_menu_for_" + self.map_div_id + " .lift_data").empty();
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").empty();

				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").append(jQuery('<span/>', { 'id': 'loc_span_1', 'html': "<b>Zip</b>: --"}));
				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").append(jQuery('<span/>', { 'id': 'loc_span_2', 'html': "<b>City</b>: --"}));
				$("#map_menu_for_" + self.map_div_id + " .lift_location_info").append(jQuery('<span/>', { 'id': 'loc_span_3', 'html': "<b>Population</b>: --" }));
				$("#map_menu_for_" + self.map_div_id + " .lift_data").append(jQuery('<span/>', { 'id': 'dat_span_1', 'html': '--'}));
				//$("#map_menu_for_" + self.map_div_id + " .lift_data").append(jQuery('<span/>', { 'id': 'dat_span_2', 'html': '--% Conversion Rate'}));
				//$("#map_menu_for_" + self.map_div_id + " .lift_data").append(jQuery('<span/>', { 'id': 'dat_span_3', 'html': 'vs --% UNEXPOSED'}));
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_1', 'html': "<b>Avg Home Value</b>: --"}));
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_2','class':'percentage', 'html': "&nbsp;"}));
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_3', 'html': "<b>Median Age</b>: --"}));
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_4', 'class':'percentage', 'html': "&nbsp;"}));
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_5', 'html': "<b>Avg Income</b>: --"}));
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_6', 'class':'percentage', 'html': "&nbsp;"}));
				$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_7', 'html': "<b>Avg # Per Household</b>: --"}));
					$("#map_menu_for_" + self.map_div_id + " .lift_demographic_info").append(jQuery('<span/>', { 'id': 'dem_span_8', 'class':'percentage', 'html': "&nbsp;"}));
			});
		}
	};

	this.map_fitbounds_defaults = {
		heatmap: function()
		{
			var fit_bounds_object = self.map_multilocation_polygons || self.map_geojson;
			return [fit_bounds_object.getBounds().pad(0.25), {
				maxZoom: 12
			}];
		},
		overview: function()
		{
			var fit_bounds_object = self.map_multilocation_polygons || self.map_geojson;
			return [fit_bounds_object.getBounds(), {
				paddingTopLeft: [$(".map_tools").outerWidth(true), 0],
				maxZoom: 12
			}];
		},
		zone: function()
		{
			var fit_bounds_object = self.map_multilocation_polygons || self.map_geojson;
			return [fit_bounds_object.getBounds(), {
				paddingTopLeft: [$(".map_tools").outerWidth(true), 0]
			}];
		},
		lift: function()
		{
			var fit_bounds_object = self.map_multilocation_polygons || self.map_geojson;
			return [fit_bounds_object.getBounds()];
		}
	};

	/**
	 * These functions are used in order to setup the html needed for each map.
	 * These functions are saved with map_type as the key.
	 *
	 * @param {string} container_selector, the selector of the div in which the map will be instantiated
	 * @return {void}
	 */
	this.map_container_html_functions = {
		heatmap: function(container_selector)
		{
			var marker_url = "/images/map_geofencing_marker.svg";
			if(typeof is_ie8 != 'undefined')
			{
				marker_url = "/images/map_geofencing_marker.png";
			}
			$(container_selector).html(
				'<div style="position: absolute; z-index:1000; left:50%; top:150px; margin-left: -48px; width:96px; height:96px" id="map_loading_image_for_' + this.map_div_id + '">' +
					'<img src="/images/loadingImage.gif" />' +
				'</div>' +
				'<div id="' + this.map_div_id + '" style="height:600px;border: 1px solid #e0e0e0;"></div>' +
				'<div id="zoomlens_for_' + this.map_div_id + '" class="report_zoommap_zoomlens report_zoommap_overlay">' +
					'<div id="info_zoommap_for_' + this.map_div_id + '" class="info_zoommap" style="background-color:' + this.inactive_color + '">' +
						'<div class="clickthrough_rate">.07%</div>' +
						'<div class="clickthrough_rate_label">Click Through Rate</div>' +
						'<div class="platform_breakdown">' +
							'<div class="platform_breakdown_android">35%</div>' +
							'<div class="platform_breakdown_ios">60%</div>' +
						'</div>' +
						'<div class="geofence_location_name"></div>' +
						'<div class="geofence_location_address"></div>' +
					'</div>' +
					'<div id="zoommap_for_' + this.map_div_id + '" class="report_zoommap report_zoommap_overlay" style="border-radius:50%;background-color:#e9e9e9"></div>' +
					'<div class="report_zoommap_border report_zoommap_overlay" style="border-color:' + this.inactive_color + '"></div>' +
				'</div>' +

				'<div class="map_tools" style="position:absolute;top:2%;left:2%;width:20%;font-weight:600;font-size: xx-small;">' +
					'<span style="font-size:8.5px; font-family:Arial, Helvetica, sans-serif; position:relative; left:5px; top:-2px;" class="beta badge badge-important">Beta</span>' +
					'<div id="map_tools_form_div_' + this.map_div_id + '" class="" style="background:#000;background:rgba(0,0,0,0.7);padding: 0 10px;">' +
						'<div id="location_info_' + this.map_div_id + '" style="color:white;text-transform: uppercase;">' +
						'</div>' +
					'</div>' +
					'<div id="map_heatmap_key_div_' + this.map_div_id + '" style="top:530px;position:absolute;width:100%;">' +
						'<div id="heatmap_key_' + this.map_div_id + '" style="' +
							'height:18px;' +
							'position:relative;' +
							'background-image: url(/images/reports/transparent_bg.png);' +
						'">' +
							'<div style="' +
								'width:100%;height:100%;' +
								'background-image: -moz-linear-gradient(left, rgba(255, 255, 255, 0) 0%, ' + this.active_hex_color + ' 100%);' +
								'background-image: -o-linear-gradient(left, rgba(255, 255, 255, 0) 0%, ' + this.active_hex_color + ' 100%);' +
								'background-image: -webkit-gradient(left, rgba(255, 255, 255, 0) 0%, ' + this.active_hex_color + ' 100%);' +
								'background-image: -webkit-gradient(linear, left, right, color-stop(0, rgba(255, 255, 255, 0)), color-stop(1, ' + this.active_hex_color + '));' +
								'background-image: -webkit-linear-gradient(left, rgba(255, 255, 255, 0) 0%, ' + this.active_hex_color + ' 100%);' +
								'background: -ms-linear-gradient(left, rgba(255, 255, 255, 0) 0%, ' + this.active_hex_color + ' 100%);' +
								'background: linear-gradient(left, rgba(255, 255, 255, 0) 0%, ' + this.active_hex_color + ' 100%);' +
								'filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#00ffffff\', endColorstr=\'#FF' + this.active_hex_color.replace('#', '') + '\',GradientType=1);' +
							'"></div>' +
						'</div>' +
						'<div style="position: relative;top:-18px;padding: 0 5px;">' +
							'<p class="heatmap_impression_key_text" style="float: left;">Fewer Impressions</p>' +
							'<p class="heatmap_impression_key_text" style="float: right;">More Impressions</p></div>' +
						'</div>' +
					'</div>' +
				'</div>' +

				'<div class="zoom_to_geofence_info" style="position: absolute;top: 510px;left: 33px;display:none;">' +
					'<img id="geofence_marker_info_for_' + this.map_div_id + '" src="' + marker_url + '" style="height:45px" alt="geofencing" />' +
					'<p style="display:inline-block;margin:0;vertical-align:middle;padding-left:5px;text-transform:uppercase;font-size:10px;line-height:1em">Zoom to<br>Geofence</p>' +
				'</div>'
			);
		},
		overview: function(container_selector)
		{
			$(container_selector).html(
				'<div style="position: absolute; z-index:1000; left:50%; top:150px; margin-left: -48px; width:96px; height:96px" id="map_loading_image_for_' + this.map_div_id + '">' +
					'<img src="/images/loadingImage.gif" />' +
				'</div>' +
				'<div id="' + this.map_div_id + '" style="height:450px;border: 1px solid #e0e0e0;"></div>' +
				'<div class="map_tools">' +
					'<div id="map_tools_form_div_' + this.map_div_id + '" style="margin-bottom:20px;background-color: #323232;background-color: rgba(50,50,50,0.7);color:white;overflow:hidden;">' +
						'<div id="change_view_form_' + this.map_div_id + '">' +
							'<form id="map_tools_form_' + this.map_div_id + '">' +
								'<h3>Product Footprint</h3> <h4 class="impression_total_value">Impressions</h4>' +
								'<ul class="map_tools_options clear">' +
								'</ul>' +
							'</form>' +
						'</div>' +
					'</div>' +
					'<div id="location_info_' + this.map_div_id + '" style="padding-left:45px;background:#48788f;background:rgba(72,120,143,0.7);clear:both;color:white;text-transform: uppercase;font-size: xx-small;"></div>' +
				'</div>'
			);
		},
		zone: function(container_selector)
		{
			$(container_selector).html(
				'<div style="position: absolute; z-index:1000; left:50%; top:150px; margin-left: -48px; width:96px; height:96px" id="map_loading_image_for_' + this.map_div_id + '">' +
					'<img src="/images/loadingImage.gif" />' +
				'</div>' +
				'<div id="' + this.map_div_id + '" style="height:450px;border: 1px solid #e0e0e0;"></div>' +
				'<div class="map_tools" style="width:30%">' +
					'<div id="map_tools_form_div_' + this.map_div_id + '" style="margin-bottom:20px;background-color: #323232;background-color: rgba(50,50,50,0.7);color:white;overflow:hidden;">' +
						'<div id="change_view_form_' + this.map_div_id + '">' +
							'<form id="map_tools_form_' + this.map_div_id + '">' +
								'<h3>Zones:</h3> <h4 class="data_shown"></h4>' +
								'<ul class="map_tools_options clear">' +
								'</ul>' +
							'</form>' +
						'</div>' +
					'</div>' +
				'</div>'
			);
		},
		lift: function(container_selector)
		{
			$(container_selector).html(
				'<div style="position: absolute; z-index:1000; left:50%; top:150px; margin-left: -48px; width:96px; height:96px" id="map_loading_image_for_' + this.map_div_id + '">' +
					'<img src="/images/loadingImage.gif" />' +
				'</div>' +
				'<div id="' + this.map_div_id + '" style="height:450px;border: 1px solid #e0e0e0;"></div>' +
				'<div id="map_menu_for_' + this.map_div_id + '" class="lift_map_form">' +
					'<div class="lift_location_info"></div>' +
					'<div class="lift_data"></div>' +
					'<div class="lift_demographic_info"></div>' +
				'</div>'
			);
		}
	};

	/**
	 * This function is called when map data is loaded from the overview card ajax call.
	 * Adds product <li> tags to map_tools ul and creates eventhandlers that alter the map.
	 *
	 * @return {void}
	 */
	this.add_product_html_to_map_form = function()
	{
		if(!this.map_form_loaded && new_products_instantiated && ($("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options").length > 0))
		{
			this.map_form_loaded = true;

			$("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options").html(
				get_html_elements_for_products.call(this)
			);

			if(typeof is_ie8 !== 'undefined' && is_ie8)
			{
				$(".map_tools form label").off('click');
				$(".map_tools form label").click(function(event) {
					var id = $(this).attr('for');
					$('#' + id).click();
				});
			}

			function update_map_options()
			{
				var
					option = $(this),
					label = option.closest('label');

				label.toggleClass('active', option.is(':checked'));
			}

			$('.map_tools form input[type="checkbox"]').off('change');
			$('.map_tools form input[type="checkbox"]').on('change', function(){
				update_map_options.call(this);
				self.change_overview_map_view();
			}).each(update_map_options);
		}
	};

	/**
	 * This function is called when there are multiple locations (campaigns) selected.
	 * Goes through the map_multilocation_map_tags_objects array and creates markers from each latlngbounds inside.
	 *
	 * @return {void}
	 */
	this.display_multilocation_map_tags = function()
	{
		if(this.map_multilocation_map_tags)
		{
			this.map_multilocation_map_tags.clearLayers();
		}

		var map_tags_array = [];
		var self = this;

		for(var prop in this.map_multilocation_map_tags_objects)
		{
			if(this.map_type == 'zone')
			{
				if(jQuery.isEmptyObject(this.syscode_data_objects)) return;
				// map_tags_array.push(handle_zone_map_popups(this, prop));
			}
			else
			{
				map_tags_array.push(handle_overview_map_tags(this, prop));
			}
		}

		if(map_tags_array.length > 1 || (this.map_type == 'zone' && map_tags_array.length))
		{
			this.map_multilocation_map_tags = L.featureGroup(map_tags_array);
			this.map_multilocation_map_tags.addTo(this.map);
		}
	};

	function handle_zone_map_popups(self, prop)
	{
		if(self.map_multilocation_map_tags_objects.hasOwnProperty(prop))
		{
			var marker_title = self.syscode_data_objects[prop.toString()].region;
			var marker_bounds = self.map_multilocation_map_tags_objects[prop];
			var marker = L.marker(marker_bounds.getCenter(), {
				icon: L.mapbox.marker.icon(self.activated_map_tag_icon_object),
				title: marker_title,
				riseOnHover: true
			});
			marker.on("click", function(){
				var padding = $(".map_tools").outerWidth(true);
				self.map.fitBounds(marker_bounds, {maxZoom: 8, paddingTopLeft: [padding, 0]});
			});

			var popup_content =
				'<div>' +
					'<p>' + self.syscode_data_objects[prop.toString()].region + '</p>' +
					'<p>' + self.syscode_data_objects[prop.toString()].total_impressions + ' Impressions</p>' +
					'<p>' + self.syscode_data_objects[prop.toString()].airings + ' Airings</p>' +
				'</div>';

			var popup_options = {
				closeButton: false,
				className: 'zone_mapbox_custom_popup'
			};

			marker.bindPopup(popup_content, popup_options);
			return marker;
		}
		return {};
	}

	function handle_overview_map_tags(self, prop) {
		if(self.map_multilocation_map_tags_objects.hasOwnProperty(prop))
		{
			var marker_title = $("#report_v2_campaign_options option[value*=';" + prop + "']").text(); // Gets campaign name from dropdown
			var marker_bounds = self.map_multilocation_map_tags_objects[prop];
			var marker = L.marker(marker_bounds.getCenter(), {
				icon: L.mapbox.marker.icon(self.activated_map_tag_icon_object),
				title: marker_title,
				riseOnHover: true
			});
			marker.on("click", function(){
				var padding = $(".map_tools").outerWidth(true);
				self.map.fitBounds(marker_bounds, {maxZoom: 8, paddingTopLeft: [padding, 0]});
			});
			return marker;
		}
		return {};
	}

	this.change_overview_map_view = function()
	{
		this.map_geojson.setStyle(this.style_functions.overview);
		if(this.map_multilocation_polygons)
		{
			this.map_multilocation_polygons.setStyle(this.style_functions.overview);
		}

		var self = this;
		this.map_geojson.eachLayer(function(marker) {
			if(marker.feature.geometry.type == 'Point')
			{
				marker.setIcon(
					(self.is_feature_selected_from_form(marker.feature.properties)) ?
						L.icon(self.activated_map_marker_icon_object) :
						L.icon(self.deactivated_map_marker_icon_object)
				);
			}
		});
	};

	this.initialize_new_map(type);

	/**
	 * Iterates through the selected inputs of the map form and checks if the feature.properties sent contain a property from the map form
	 *
	 * @param {object} properties, the properties object from the geojson, usually obtained from a feature object representing a particular feature on the map
	 * @return {boolean} is_feature_on, whether or not the feature should be shown as active based on map form
	 */
	this.is_feature_selected_from_form = function(properties)
	{
		var is_feature_on = false;

		$('input[name="overview_data_type"]:checked').each(function(){
			if(properties[$(this).val()])
			{
				is_feature_on = true;

				return false; // To get out of the each
			}
		});

		return is_feature_on;
	};

	/**
	 * Gets a string of li elements with product information formatted based on if products available for user
	 *
	 * @return {string} list_elements, list of <li> elements that go inside the product form on the map
	 */
	function get_html_elements_for_products()
	{
		var list_elements = "";
		var initialized_product_impressions = true;
		for(var i = 0; i < g_current_product_tab_set.length; i++)
		{
			var product = g_current_product_tab_set[i];
			if(product.html_element_id != 'overview_product')
			{
				var able_to_be_selected = "not_available";
				var checked = "disabled='disabled'";

				if(g_tabs_visibility[product.html_element_id])
				{
					able_to_be_selected = "available";
					if(product.overview_map_id)
					{
						able_to_be_selected += " map_choice";
						checked = "checked='checked'";
						var id = product.overview_map_id;

						var impressions = '';
						if(maps_product_sums && maps_product_sums.hasOwnProperty(id))
						{
							impressions = maps_product_sums[id];
						}
						else
						{
							initialized_product_impressions = false;
						}

						var temp_html =
						'<li>' +
							'<label for="' + id + '_for_' + this.map_div_id + '" class="radio ' + able_to_be_selected + '">' +
								'<input name="overview_data_type" value="' + id + '" ' + checked + ' type="checkbox" id="' + id + '_for_' + this.map_div_id + '" />' +
								product.product_brand + ' ' + product.product_type +
							'</label>' +
							'<span class="impression_total_value" id="' + id + '_impression_total_for_' + this.map_div_id + '">' + impressions + '</span>' +
						'</li>';

						list_elements += temp_html;
					}
				}
			}
		}
		this.has_product_impression_totals = initialized_product_impressions;
		return list_elements;
	}

	function get_num_common_elements(arr1, arr2)
	{
		var num_common_elements = 0;
		$.each(arr2, function(index, value){
			if($.inArray(value, arr1) != -1)
			{
				num_common_elements++;
			}
		});
		return num_common_elements;
	}

	// Converts rgb(105, 105, 105) to #696969
	function convert_to_hex(rgb_string)
	{
		if(rgb_string.indexOf("#") > -1)
		{
			return rgb_string;
		}
		var color_pieces = /(\d+), (\d+), (\d+)/.exec(rgb_string);
		if(color_pieces)
		{
			var r = parseInt(color_pieces[1], 10).toString(16);
			var g = parseInt(color_pieces[2], 10).toString(16);
			var b = parseInt(color_pieces[3], 10).toString(16);

			r = (r.length == 1) ? "0" + r : r;
			g = (g.length == 1) ? "0" + g : g;
			b = (b.length == 1) ? "0" + b : b;

			return "#" + r + g + b;
		}

		return null;
	}

	function set_up_multi_location_markers(self, feature, latlng)
	{
		if(feature.properties.campaign_ids)
		{
			var c_ids = feature.properties.campaign_ids;
			for(var id in c_ids)
			{
				var campaign_id = c_ids[id].toString();
				if(self.map_multilocation_map_tags_objects.hasOwnProperty(campaign_id))
				{
					self.map_multilocation_map_tags_objects[campaign_id].extend(latlng);
				}
				else
				{
					self.map_multilocation_map_tags_objects[campaign_id] = L.latLngBounds([latlng]);
				}
			}
		}
	}

	function set_up_syscode_popovers(self, feature, latlng)
	{
		if(feature.properties.syscodes)
		{
			var s_ids = feature.properties.syscodes;
			for(var id in s_ids)
			{
				var campaign_id = s_ids[id].toString();
				if(self.map_multilocation_map_tags_objects.hasOwnProperty(campaign_id))
				{
					self.map_multilocation_map_tags_objects[campaign_id].extend(latlng);
				}
				else
				{
					self.map_multilocation_map_tags_objects[campaign_id] = L.latLngBounds([latlng]);
				}
			}
		}
	}

	function generate_random_id_for_map_labels()
	{
		var d = new Date();
		var n = d.getTime();

		return 'temp_' + n;
	}

	function display_location_data_on_mouseover(layer)
	{
		var html_content =
			"Zip: " + layer.feature.properties.region_id +
			"<br>" +
			layer.feature.properties.city +
			"<br>" +
			layer.feature.properties.region +
			"<br>" +
			"Population: " + number_with_commas(layer.feature.properties.population);

		$("div#location_info_" + this.map_div_id).html(html_content);
	}

	function number_with_commas(x)
	{
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}

}

(function() {

	var minimum_zoom_level_for_the_map = 2;
	var us_center_array = [39.8282239, -98.579569];
	var default_us_zoom = 4;

	var current_width_plus_padding = $(".map_tools").outerWidth(true);
	var default_map_fitbounds_object = {
		padding: [0, 0]
	};
	var side_menu_map_fitbounds_object = {
		paddingTopLeft: [current_width_plus_padding, 0] // [x, y]
	};

	/**
	 * Initializes new map
	 *
	 * @param {string} type, optional map type if type change is desired
	 * @return {void}
	 */
	this.initialize_new_map = function(type)
	{
		if(!type && !this.map_type) type = 'heatmap';

		this.destroy_previous_data();
		this.map_div_id = generate_random_id_for_map();
		this.available_products = generate_available_product_object();

		if(type && type != this.map_type)
		{
			this.map_type = type;
		}
		this.map_container_html_functions[this.map_type].apply(this, [this.map_container]);

		this.initialize_mapbox_map();
	};

	/**
	 * Builds a new map geojson layer from geojson string passed in
	 *
	 * @param {string} geojson_blob, json string of geojson
	 * @param {string} type, optional map type
	 * @return {void}
	 */
	this.build_map_from_data = function(geojson_blob, callback, type)
	{
		if(!type) type = this.map_type;

		if(this.map_type == 'overview')
		{
			this.add_product_html_to_map_form();
			this.populate_impression_totals();
		}

		var geojson = JSON.parse(geojson_blob);
		this.map_geojson = L.geoJson(geojson, {
			style: this.style_functions[type],
			onEachFeature: this.on_each_feature_functions[type],
			pointToLayer: this.point_to_layer_functions[type]
		});

		if(this.map_type == 'overview')
		{
			hide_products_based_on_available_products_for_map_object(this.available_products, this.map_div_id);
		}

		var self = this;
		this.map_geojson.addTo(this.map);

		if(callback && typeof callback == 'function')
		{
			callback();
		}

		this.map.once('ready', function() {
			if(self.map_type != 'zone')
			{
				self.display_multilocation_map_tags();
			}
			self.map.fitBounds.apply(self.map, self.map_fitbounds_defaults[type]());
			for(var complexity_level = 1; complexity_level <= self.count_complexity_levels; complexity_level++)
			{
				self.embrace_complexity(complexity_level);
			}

			self.embrace_complexity();
			self.map.on('moveend zoomend', function(){
				zoom_handler_for_map(self);
			});
		});
	};

	this.load_initial_polygons = function(region_id)
	{
		var self = this;
		if(jQuery.inArray(region_id, this.map_multilocation_polygons_ids) < 0)
		{
			this.map_multilocation_polygons_ids.push(region_id);
			this.region_polygon_ajax_queue.push({
				async: true,
				type: "POST",
				dataType: "json",
				data: {
					region_id: [region_id],
					region_type: 'zcta'
				},
				url: "/maps/get_region_geojson_to_draw",
				success: function(response_data, textStatus, jqXHR) {
					if(vl_is_ajax_call_success(response_data))
					{
						if(response_data.region_blob)
						{
							var polygons = JSON.parse(response_data.region_blob);
							var returned_region_id = polygons.features[0].properties.region_id;
							self.get_properties_from_markers_for_containing_polygons(polygons, true);

							// load polys on separate layer that gets hidden/destroyed when no longer zoomed in on
							if(self.map_multilocation_polygons)
							{
								// Add polys
								self.map_multilocation_polygons.addData(polygons);
							}
							else
							{
								// Initialize
								self.map_multilocation_polygons = L.geoJson(polygons, {
									style: self.style_functions[self.map_type],
									onEachFeature: self.on_each_feature_functions[self.map_type]
								});
							}
						}

						// Check if map already has the layer and if not, re-add it
						if(self.map_main_geography_type == "polygon" && !self.map.hasLayer(self.map_multilocation_polygons) && self.map_multilocation_polygons)
						{
							self.map.addLayer(self.map_multilocation_polygons);
						}
					}
					else
					{
						handle_ajax_controlled_error(response_data, "Failed to retrieve polygon ");
					}
					self.multilocation_polygons_ajax = false;
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(textStatus != 'abort')
					{
						vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
					}
				},
				complete: function(){
					self.map_multilocation_polygons_loaded++;
					zoom_handler_for_map(self);
				}
			});
		}
	};

	this.embrace_complexity = function(complexity_level)
	{
		var self = this;
		if(complexity_level === undefined || complexity_level > this.count_complexity_levels)
		{
			complexity_level = 'max';
		}
		if(this.map_multilocation_polygons_ids.length < 5000) // TODO better memory handling -MC
		{
			var region_ids = this.map_multilocation_polygons_ids.join(',');
			var regions_to_send = region_ids;
			this.complexity_queue[complexity_level] =
				$.ajax({
					async: true,
					type: "POST",
					dataType: "json",
					data: {
						region_id: regions_to_send,
						region_type: 'zcta',
						complexity_level: complexity_level,
						memory_limit: '15M' // Don't return geos over this size, uses decimal prefixes from https://en.wikipedia.org/wiki/Binary_prefix
					},
					url: "/maps/get_region_geojson_to_draw",
					success: function(response_data, textStatus, jqXHR) {
						if(vl_is_ajax_call_success(response_data))
						{
							self.complexity_queue[response_data.complexity_level] = false;
							if(response_data.region_blob)
							{
								cancel_slower_queries(self.complexity_queue, response_data.complexity_level);

								var polygons = JSON.parse(response_data.region_blob);
								self.get_properties_from_markers_for_containing_polygons(polygons, true);

								if(self.map_multilocation_polygons === null)
								{
									self.map_multilocation_polygons_complexity = response_data.complexity_level;
									self.map_multilocation_polygons_loaded += (polygons.features.length - response_data.regions_without_geos);
									self.map_multilocation_polygons_hash = {};
									self.map_multilocation_polygons = L.geoJson(polygons, {
										style: self.style_functions[self.map_type],
										onEachFeature: self.on_each_feature_functions[self.map_type]
									});
									self.map.fitBounds.apply(self.map, self.map_fitbounds_defaults[self.map_type]());

									zoom_handler_for_map(self);
								}
								else
								{
									if(response_data.complexity_level == 'max' || (self.map_multilocation_polygons_complexity != 'max' && response_data.complexity_level > self.map_multilocation_polygons_complexity))
									{
										self.map_multilocation_polygons_complexity = response_data.complexity_level;
										var temp_geojson = L.geoJson(polygons, {
											onEachFeature: function(feature, layer){
												var region_id = feature.properties.region_id;
												var complex_latlngs = layer.getLatLngs();

												if(self.map_multilocation_polygons_hash[region_id])
												{
													self.map_multilocation_polygons_hash[region_id].setLatLngs(complex_latlngs);
												}
											}
										});
									}
								}
							}
							else
							{
								cancel_slower_queries(self.complexity_queue, response_data.complexity_level, true);
							}

							// Check if map already has the layer and if not, re-add it
							if(self.map_main_geography_type == "polygon" && !self.map.hasLayer(self.map_multilocation_polygons) && self.map_multilocation_polygons)
							{
								self.map.addLayer(self.map_multilocation_polygons);
							}
						}
						else
						{
							handle_ajax_controlled_error(response_data, "Failed to retrieve polygon ");
						}
						self.multilocation_polygons_ajax = false;
					},
					error: function(jqXHR, textStatus, errorThrown) {
						if(textStatus != 'abort')
						{
							vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
						}
					}
				});
		}
		else
		{
			// TODO handle targeted polygon loading -MC
		}
	};

	function cancel_slower_queries(complexity_queue, level_returned_first, cancel_higher_complexity_calls)
	{
		for(var complexity_level in complexity_queue)
		{
			if(
				complexity_queue.hasOwnProperty(complexity_level) &&
				(	(level_returned_first == 'max' && complexity_level != 'max' && complexity_queue[complexity_level]) ||
					(complexity_level < level_returned_first && complexity_queue[complexity_level]) ||
					(cancel_higher_complexity_calls && complexity_level > level_returned_first && complexity_queue[complexity_level]))
			)
			{
				if(typeof complexity_queue[complexity_level].abort === 'function') // Stops errors in ie8
				{
					complexity_queue[complexity_level].abort();
				}
			}
		}
	}

	this.load_geofencing_data = function(multipoint_feature_collection_string)
	{
		if(!!multipoint_feature_collection_string)
		{
			var multi_point_geojson = JSON.parse(multipoint_feature_collection_string);
			this.geofencing_zoom_map = L.mapbox.map('zoommap_for_' + this.map_div_id, 'mapbox.streets-satellite', {
				zoomControl: false,
				fadeAnimation: false,
				attributionControl: false
			});

			var tile_json = null;
			var main_map_satellite_layer = L.mapbox.tileLayer('mapbox.streets-satellite').on('ready', function() {
				// get TileJSON data from the loaded layer
				tile_json = main_map_satellite_layer.getTileJSON();
			});

			this.geofencing_zoom_map.boxZoom.disable();
			this.geofencing_zoom_map.dragging.disable();
			this.geofencing_zoom_map.doubleClickZoom.disable();
			this.geofencing_zoom_map.touchZoom.disable();
			this.geofencing_zoom_map.scrollWheelZoom.disable();
			this.geofencing_zoom_map.keyboard.disable();
			if(this.geofencing_zoom_map.tap)
			{
				this.geofencing_zoom_map.tap.disable();
			}

			$(".zoom_to_geofence_info").show();

			var mini_marker_radius = 7;
			var mini_marker_fill_color = this.active_hex_color; // "#C4121B";
			var mini_marker_fill_opacity = 1;
			var mini_marker_stroke_color = "#ffffff";
			var mini_marker_stroke_width = 0.5;
			var mini_marker_stroke_opacity = 0.5;

			var marker_url = (typeof is_ie8 != 'undefined' && is_ie8) ? "/images/map_geofencing_marker.png" : "/images/map_geofencing_marker.svg";

			var self = this;
			var geofencing_markers_for_main_map = L.featureGroup();
			this.geofencing_markers_for_zoomed_main_map = L.featureGroup();
			this.map.addLayer(this.geofencing_markers_for_zoomed_main_map);
			var geofencing_geojson = L.geoJson(multi_point_geojson, {
				onEachFeature: function(feature){
					var point_bounds = L.geoJson(feature).getBounds();
					var center_point = L.latLng(feature.properties.center_point_latitude, feature.properties.center_point_longitude);

					var main_marker = L.marker(center_point, {
						icon: L.icon({
							iconUrl: marker_url,
							iconSize: [36, 36],
							iconAnchor: [18, 18],
							popupAnchor: [18, 18]
						}),
						riseOnHover: true,
						title: feature.properties.name + '\n' + feature.properties.address
					});

					var main_map_center_point_radius = L.circle(center_point, feature.properties.radius_in_meters, {
						color: "#eee",
						weight: 1,
						fillColor: "#aaa",
						fillOpacity: 0.1
					});

					var mini_map_center_point_radius = L.circle(center_point, feature.properties.radius_in_meters, {
						color: "#eee",
						weight: 1,
						fillColor: "#aaa",
						fillOpacity: 0.1
					});

					main_marker.on('mouseover', function(e){
						handle_geofence_hover(self, e, mini_map_center_point_radius.getBounds(), feature);
					});
					main_marker.on('mouseout', function(){
						reset_geofence_hover(self);
					});
					main_marker.on('click', function(){
						self.map.fitBounds(main_map_center_point_radius.getBounds());
					});

					// Load initial image tiles while map is off screen
					self.geofencing_zoom_map.fitBounds(mini_map_center_point_radius.getBounds());

					self.map.on('zoomend moveend', function(e){
						if(self.map.getBounds().intersects(point_bounds))
						{
							if(self.map.getZoom() >= self.geofencing_zoom_map.getBoundsZoom(point_bounds) - 2)
							{
								if(!self.map.hasLayer(self.geofencing_markers_for_zoomed_main_map))
								{
									self.map.addLayer(self.geofencing_markers_for_zoomed_main_map);
								}
								if(self.map.hasLayer(self.map_multilocation_polygons))
								{
									self.map.removeLayer(self.map_multilocation_polygons);
								}
								if(!self.map.hasLayer(main_map_satellite_layer) && tile_json)
								{
									self.map.addLayer(main_map_satellite_layer);
								}
								$("#map_heatmap_key_div_" + self.map_div_id).hide();
								$("#map_tools_form_div_" + self.map_div_id).hide();
							}
							else
							{
								if(self.map.hasLayer(self.geofencing_markers_for_zoomed_main_map))
								{
									self.map.removeLayer(self.geofencing_markers_for_zoomed_main_map);
								}
								if(!self.map.hasLayer(self.map_multilocation_polygons) && self.map_multilocation_polygons)
								{
									self.map.addLayer(self.map_multilocation_polygons);
								}
								if(self.map.hasLayer(main_map_satellite_layer))
								{
									self.map.removeLayer(main_map_satellite_layer);
								}
								$("#map_heatmap_key_div_" + self.map_div_id).show();
								$("#map_tools_form_div_" + self.map_div_id).show();
							}
						}
					});
					geofencing_markers_for_main_map.addLayer(main_marker);
				},
				pointToLayer: function(feature, latlng) {
					var marker = L.circleMarker(latlng, {
						weight: mini_marker_stroke_width,
						color: mini_marker_stroke_color,
						opacity: mini_marker_stroke_opacity,
						fill: true,
						fillColor: mini_marker_fill_color,
						fillOpacity: mini_marker_fill_opacity
					}).setRadius(mini_marker_radius);

					var main_map_marker = L.circleMarker(latlng, {
						weight: mini_marker_stroke_width,
						color: mini_marker_stroke_color,
						opacity: mini_marker_stroke_opacity,
						fill: true,
						fillColor: mini_marker_fill_color,
						fillOpacity: mini_marker_fill_opacity
					}).setRadius(mini_marker_radius);

					self.geofencing_markers_for_zoomed_main_map.addLayer(main_map_marker);
					return marker;
				}
			});

			geofencing_markers_for_main_map.addTo(this.map);
			geofencing_geojson.addTo(this.geofencing_zoom_map);
		}
	};

	this.stop_and_clear_load_regions_queue = function()
	{
		if(this.region_polygon_ajax_queue)
		{
			this.region_polygon_ajax_queue.clear();
			this.region_polygon_ajax_queue.start();
		}

		if(this.complexity_queue)
		{
			for(var complexity_level in this.complexity_queue)
			{
				if(this.complexity_queue.hasOwnProperty(complexity_level) && this.complexity_queue[complexity_level] && this.complexity_queue[complexity_level].abort)
				{
					this.complexity_queue[complexity_level].abort();
				}
			}
		}
	};

	function zoom_handler_for_map(self)
	{
		if(!self.map.hasLayer(self.geofencing_markers_for_zoomed_main_map))
		{
			self.set_geography_type_variable(self.map_multilocation_polygons_loaded == self.map_multilocation_polygons_ids.length);
		}
	}

	/**
	 * This function is called when the map is zoomed in past a threshold while map tags are present.
	 * Gets the relavant polygons for the section of map being zoomed in on.
	 *
	 * @param {string} geojson_blob, json string of geojson
	 * @param {string} type, optional map type
	 * @return {void}
	 */
	this.set_geography_type_variable = function(handle_geographies)
	{
		// Get ids of markers of visible map area
		var ids_for_area_calculation = [];
		var bounds = this.map.getBounds();
		var bounds_rectangle = L.rectangle(bounds);

		var self = this;
		this.map_geojson.eachLayer(function(marker) {
			if(marker.feature.geometry.type == 'Point')
			{
				if(bounds.contains(marker.getLatLng()))
				{
					ids_for_area_calculation.push(marker.feature.properties.region_id);
				}
			}
		});

		if(ids_for_area_calculation.length)
		{
			if(this.multilocation_polygons_ajax)
			{
				this.multilocation_polygons_ajax.abort();
			}

			ids_for_area_calculation = (ids_for_area_calculation.length > 999) ? ids_for_area_calculation.join(',') : ids_for_area_calculation;

			// Ajax call with those zctas to return polys
			this.multilocation_polygons_ajax = $.ajax({
				async: true,
				type: "POST",
				dataType: "json",
				data: {
					area_ids: ids_for_area_calculation,
					map_area_km: (LGeo.area(bounds_rectangle) / 1000000) // convert to km2
				},
				url: "/maps/get_polygons_for_visible_area",
				success: function(response_data, textStatus, jqXHR) {
					if(vl_is_ajax_call_success(response_data))
					{
						if(!self.map.hasLayer(self.geofencing_markers_for_zoomed_main_map))
						{
							if(response_data.use_polygons)
							{
								self.map_main_geography_type = "polygon";
								if(handle_geographies)
								{
									// Check if map already has the layer and if not, re-add it
									if(!self.map.hasLayer(self.map_multilocation_polygons) && self.map_multilocation_polygons)
									{
										self.map.addLayer(self.map_multilocation_polygons);
									}

									// Make all visible markers invisible
									if(self.map.hasLayer(self.map_geojson))
									{
										self.map.removeLayer(self.map_geojson);
									}
								}
							}
							else
							{
								self.map_main_geography_type = "point";
								if(handle_geographies)
								{
									if(self.map.hasLayer(self.map_multilocation_polygons))
									{
										self.map.removeLayer(self.map_multilocation_polygons);
									}

									if(!self.map.hasLayer(self.map_geojson) && self.map_geojson)
									{
										self.map.addLayer(self.map_geojson);
									}
								}
							}
						}
					}
					else
					{
						handle_ajax_controlled_error(response_data, "Failed to retrieve geography status ");
					}
					self.multilocation_polygons_ajax = false;
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(textStatus != 'abort')
					{
						vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
					}
				}
			});
		}
	};

	/**
	 * This function is called when the map is zoomed in past a threshold while map tags are present.
	 * After getting the polygons that will replace the markers; iterate through markers and combine properties that should be shared.
	 *
	 * @param {object} polygons_geojson, geojson object of polygons
	 * @return {void}
	 */
	this.get_properties_from_markers_for_containing_polygons = function(polygons_geojson, initial_load)
	{
		var bounds = this.map.getBounds();
		this.map_geojson.eachLayer(function(marker) {
			if(marker.feature.geometry.type == 'Point')
			{
				if(bounds.contains(marker.getLatLng()) || initial_load)
				{
					for(var i = 0; i < polygons_geojson.features.length; i++)
					{
						if(polygons_geojson.features[i].properties.region_id == marker.feature.properties.region_id)
						{
							jQuery.extend(polygons_geojson.features[i].properties, marker.feature.properties);
							if(initial_load)
							{
								return;
							}
						}
					}
				}
			}
		});
	};

	var marker_update_queue_timeout = null;
	this.set_up_zone_form = function(zone_data)
	{
		if(this.map_type == 'zone')
		{
			this.syscode_data_objects = zone_data;
			this.display_multilocation_map_tags();
			$("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options").html('');
			var has_impressions = !!this.syscode_data_objects[[Object.keys(this.syscode_data_objects)[0]]].total_impressions;

			for(var zone in this.syscode_data_objects)
			{
				if(this.syscode_data_objects.hasOwnProperty(zone))
				{
					var id = zone;
					var zone_object = this.syscode_data_objects[zone];
					var total_span = (has_impressions) ?
						'<span class="impression_total_value" id="zone_' + id + '_total_for_' + this.map_div_id + '">' + zone_object.total_impressions + '</span>' :
						'<span class="airings_total_value" id="zone_' + id + '_total_for_' + this.map_div_id + '">' + zone_object.airings + '</span>';

					var temp_html =
					'<li class="zone_menu_options" title="' + zone_object.sysname + '">' +
						'<label for="' + id + '_for_' + this.map_div_id + '" class="zones">' +
							'<input name="tv_syscodes" value="' + id + '" ' + ' type="checkbox" id="' + id + '_for_' + this.map_div_id + '" checked="checked" />' +
							zone_object.sysname +
						'</label>' +
						total_span +
					'</li>';
					$("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options").append(temp_html);
				}
			}

			var self = this;

			var li = $("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options li").get();
			li.sort(function(a, b) {
				a = $('label', a).text();
				b = $('label', b).text();

				return (a < b) ? -1 : ((a > b) ? 1 : 0);
			});
			$("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options").append(li);

			function update_map_zone_options()
			{
				var
					option = $(this),
					label = option.closest('label.zones');

				label.toggleClass('active', option.is(':checked'));
			}

			$(".map_tools #map_tools_form_" + this.map_div_id + " .data_shown").text(has_impressions ? 'Impressions' : 'Airings'); //Impressions or Airings depending on permissions

			$('.map_tools #map_tools_form_' + this.map_div_id + ' input[type="checkbox"]').off('change');
			$('.map_tools #map_tools_form_' + this.map_div_id + ' input[type="checkbox"]').on('change', function(){
				var int_syscode = parseInt($(this).parents('li').find('label.zones input').val() , 10);
				var index_in_array = $.inArray(int_syscode, self.selected_syscodes);
				if (index_in_array == -1)
				{
					self.selected_syscodes.push(int_syscode);
				}
				else
				{
					self.selected_syscodes.splice(index_in_array, 1);
				}

				update_map_zone_options.call(this);
				self.handle_ui_hovers_for_zones();
			}).each(update_map_zone_options);

			var timeout_for_hover;

			$('.map_tools #map_tools_form_' + this.map_div_id + ' li').off('hover');
			$('.map_tools #map_tools_form_' + this.map_div_id + ' li').hover(
				function(){
					clearTimeout(marker_update_queue_timeout);
					var int_syscode = parseInt($(this).find('label.zones input').val() , 10);
					self.hovered_syscodes = [int_syscode];
					self.handle_ui_hovers_for_zones();
				},
				function(){
					clearTimeout(marker_update_queue_timeout);
					self.hovered_syscodes = [];
					self.handle_ui_hovers_for_zones();
				}
			);
			$('.map_tools #map_tools_form_' + this.map_div_id + ' input[type="checkbox"]').change();
		}
	};

	this.handle_ui_hovers_for_zones = function()
	{
		this.map_geojson.setStyle(this.style_functions.zone);
		if(this.map_multilocation_polygons)
		{
			this.map_multilocation_polygons.setStyle(this.style_functions.zone);
		}

		var self = this;
		var layers = this.map_geojson.getLayers();
		var layer_index = 0;
		function update_next_layer()
		{
			var marker = layers[layer_index];
			if(marker.feature.geometry.type == 'Point')
			{
				var marker_object = (has_common_element(marker.feature.properties.syscodes, self.hovered_syscodes) || has_common_element(marker.feature.properties.syscodes, self.selected_syscodes)) ?
						L.icon(self.activated_map_marker_icon_object) :
						L.icon(self.deactivated_map_marker_icon_object);
				marker.setIcon(
					marker_object
				);
			}
			layer_index++;
			if(layer_index % 20 === 0)
			{
				marker_update_queue_timeout = setTimeout(update_next_layer, 0);
			}
			else if(layer_index < layers.length - 1)
			{
				update_next_layer();
			}
			else
			{
				marker_update_queue_timeout = null;
			}
		}
		update_next_layer();
	};

	this.populate_impression_totals = function()
	{
		if(!this.has_product_impression_totals && !jQuery.isEmptyObject(maps_product_sums) && ($("#map_tools_form_div_" + this.map_div_id + " ul.map_tools_options").children('li').length > 0))
		{
			this.has_product_impression_totals = true;
			for(var product in maps_product_sums)
			{
				if(maps_product_sums.hasOwnProperty(product))
				{
					$("#" + product + "_impression_total_for_" + this.map_div_id).text(maps_product_sums[product]);
				}
			}
		}
	};

	function hide_products_based_on_available_products_for_map_object(available_products, map_id)
	{
		for(var product in available_products)
		{
			if(available_products.hasOwnProperty(product))
			{
				if(!available_products[product])
				{
					// Hide the product li
					$("input#" + product + "_for_" + map_id).parents("li").hide();
				}
			}
		}
	}

	/**
	 * Redraws the map if map created while container had no dimensions Ex. display:none
	 *
	 * @return {void}
	 */
	this.redraw_map = function()
	{
		this.map.invalidateSize();
		this.map.fitBounds.apply(this.map, this.map_fitbounds_defaults[this.map_type]());
	};

	this.destroy_previous_data = function()
	{
		if(this.map)
		{
			if(this.map_geojson)
			{
				this.map_geojson.removeLayer(this.map);
			}
			if(this.map_multilocation_polygons)
			{
				this.map_multilocation_polygons.removeLayer(this.map);
			}
			if(this.map._container.childNodes.length) // Hacky ie9 fix
			{
				this.map.remove();
			}
			var node = document.getElementById(this.map_div_id);
			if(node)
			{
				node.parentNode.removeChild(node);
			}

			this.map = null;
		}

		this.map_multilocation_polygons = null;
		this.map_multilocation_polygons_hash = {};
		this.map_multilocation_polygons_ids = [];
		this.map_multilocation_polygons_loaded = 0;
		this.map_multilocation_polygons_complexity = null;
		this.map_multilocation_map_tags = null;
		this.map_multilocation_map_tags_objects = {};
		this.has_product_impression_totals = false;
		this.map_form_loaded = false;
		this.selected_syscodes = [];
		this.complexity_queue = {};
		this.geofencing_markers_for_zoomed_main_map = null;
	};

	/**
	 * Creates a new Mapbox map with access token
	 *
	 * @return {void}
	 */
	this.initialize_mapbox_map = function()
	{
		L.mapbox.accessToken = 'pk.eyJ1IjoiZnJlcS1lLWRlZWsiLCJhIjoiY2lmcHpidnJ6aHlxeHI3bHhvbzFtZTI4ZCJ9.BqXwR2aAH83EuuX0-ozozA';
		this.map = L.mapbox.map(this.map_div_id, 'mapbox.light', {
			zoomControl: false,
			minZoom: minimum_zoom_level_for_the_map,
			trackResize: true
		}).setView(us_center_array, default_us_zoom);
		new L.Control.Zoom({ position: 'topright' }).addTo(this.map);

		this.map.scrollWheelZoom.disable();

		var self = this;
		this.map.once('ready', function() {
			var loading_image = $("#map_loading_image_for_" + self.map_div_id);
			loading_image.hide("fast");
		});
	};

	/**
	 * Generates an initially false-filled array of products allowed for use on the map, using products available on page objects
	 * Will be iterated through and products will be set to true when there are geos available for them.
	 *
	 * @return {object} product_permissions_for_map
	 */
	function generate_available_product_object()
	{
		var products_permissions_for_map = {};

		for(var index in g_current_product_tab_set)
		{
			var product = g_current_product_tab_set[index];
			if(g_tabs_visibility[product.html_element_id] && product.overview_map_id)
			{
				products_permissions_for_map[product.overview_map_id] = false; // Default permissions to false
			}
		}

		return products_permissions_for_map;
	}

	function handle_geofence_hover(self, e, bounds_to_fit, feature)
	{
		self.geofencing_zoom_map.invalidateSize();
		self.geofencing_zoom_map.fitBounds(bounds_to_fit);

		var zoommap = $('#zoomlens_for_' + self.map_div_id);
		var map = $('#' + self.map_div_id);

		var map_container_width = map.outerWidth();
		var map_container_height = map.outerHeight();
		var zoommap_container_width = zoommap.outerWidth() + $("#info_zoommap_for_" + self.map_div_id).outerWidth();
		var zoommap_container_height = zoommap.outerHeight();

		var marker_size = e.target.options.icon.options.iconSize;

		var marker_left = (~~self.map.latLngToContainerPoint(e.latlng).x);
		var marker_top = (~~self.map.latLngToContainerPoint(e.latlng).y);

		var left, right, top;
		if(marker_left > (map_container_width / 2))
		{
			$("#info_zoommap_for_" + self.map_div_id).removeClass('zoommap_info_right').addClass('zoommap_info_left');
			left = '17%';
			right = 'auto';
			top = '18%';
		}
		else
		{
			$("#info_zoommap_for_" + self.map_div_id).removeClass('zoommap_info_left').addClass('zoommap_info_right');
			left = 'auto';
			right = '17%';
			top = '2%';
		}

		var total_android_impressions = parseInt(feature.properties.android_impression_sum, 10);
		var total_ios_impressions = parseInt(feature.properties.ios_impression_sum, 10);
		var total_impressions = total_android_impressions + total_ios_impressions;

		var total_clicks = parseInt(feature.properties.android_click_sum, 10) + parseInt(feature.properties.ios_click_sum, 10);

		var clickthrough_rate = Highcharts.numberFormat(100 * (total_clicks / total_impressions), 2);
		var percent_android = Math.round(100 * (total_android_impressions / total_impressions));
		var percent_ios = Math.round(100 * (total_ios_impressions / total_impressions));

		var geofence_location_name = feature.properties.name;
		var geofence_location_address = feature.properties.address;

		zoommap.find(".clickthrough_rate").first().text(clickthrough_rate + "%");
		zoommap.find(".platform_breakdown_android").first().text(percent_android + "%");
		zoommap.find(".platform_breakdown_ios").first().text(percent_ios + "%");
		zoommap.find(".geofence_location_name").first().text(geofence_location_name);
		zoommap.find(".geofence_location_address").first().text(geofence_location_address);

		zoommap.css('left', left);
		zoommap.css('right', right);
		zoommap.css('top', top);
	}

	function reset_geofence_hover(self)
	{
		var zoommap = $('#zoomlens_for_' + self.map_div_id);
		zoommap.css('left', '-9999px');
		zoommap.css('top', '-9999px');
	}

	function make_gradient_color(color1, color2, percent) {
		var newColor = {};

		function make_channel(a, b) {
			return(a + Math.round((b-a)*(percent)));
		}

		function make_color_piece(num) {
			num = Math.min(num, 255);   // not more than 255
			num = Math.max(num, 0);     // not less than 0
			var str = num.toString(16);
			if (str.length < 2) {
				str = "0" + str;
			}
			return(str);
		}

		newColor.r = make_channel(color1.r, color2.r);
		newColor.g = make_channel(color1.g, color2.g);
		newColor.b = make_channel(color1.b, color2.b);
		newColor.cssColor = "#" +
			make_color_piece(newColor.r) +
			make_color_piece(newColor.g) +
			make_color_piece(newColor.b);
		return(newColor.cssColor);
	}

	function has_common_element(arr1, arr2)
	{
		var element_exists = false;
		$.each(arr2, function(index, value){
			if($.inArray(value, arr1) > -1)
			{
				element_exists = true;
				return false; // break out of each
			}
		});
		return element_exists;
	}

	function generate_random_id_for_map()
	{
		var d = new Date();
		var n = d.getTime();

		return 'map_' + n;
	}

}).call(ReportMap.prototype);

var new_products_instantiated = false;
function handle_map_products_ajax_call_race_condition(products_ready)
{
	new_products_instantiated = products_ready;
}
