<script src='https://api.mapbox.com/mapbox.js/v2.2.4/mapbox.js'></script>
<script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-geodesy/v0.1.0/leaflet-geodesy.js'></script>
<!-- <script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-image/v0.0.4/leaflet-image.js'></script> -->
<script type="text/javascript" src="/js/ajax_queue.js"></script>
<script type="text/javascript" src="/js/maps/leaflet_image_0.2.1.js"></script>
<script type="text/javascript">

(function(){

	var access_token = '<?php echo $mapbox_access_token; ?>';

	var us_center_array = [38.95940879245423, -98.173828125];
	var default_us_zoom = 4;

	/**
	 * The class for handling maps for most of the platform
	 * Instantiates a map and has commonly-used methods built in for handling regions
	 *
	 * @param {string} container_id, the id of the div in which all map html will go
	 * @param {string} map_style, the Mapbox (or custom style) the tiles of the map will use
	 * @param {object} options, the object to handle map functionality; has properties polygon_options and map_options
	 */
	function FrequenceMap(container_id, map_style, options)
	{
		L.mapbox.accessToken = access_token;

		this.map_options = {
			zoomControl: false,
			center: us_center_array,
			zoom: default_us_zoom
		};

		this.polygon_options = {};
		handle_user_passed_options.call(this, options);
		if(this.polygon_options.fillColor)
		{
			this.polygon_options.fillColor = convert_to_hex(this.polygon_options.fillColor);
		}

		this.map = L.mapbox.map(container_id, map_style, this.map_options);

		if(!this.map_options.zoomControl)
		{
			this.zoom_controls = new L.Control.Zoom({ position: 'topright' }).addTo(this.map);
			this.map.scrollWheelZoom.disable();
		}

		var self = this;
		this.map.on('zoomend moveend', function(){
			smart_region_handler(self);
		});

		this.use_inverted_map = false;
		this.inverted_map_mask = L.rectangle([[79.23718500609336, 112.1484375], [-22.268764039073968, -337.1484375]]);
		this.inverted_map_polygon;
		this.map.setMaxBounds(this.inverted_map_mask.getBounds());

		this.map_main_geography_type = "polygon";
		this.force_points = false;

		this.current_active_regions = [];
		this.current_polygon_object = {};
		this.current_marker_object = {};
		this.current_polygon_feature_group = L.featureGroup().setStyle(function(layer){
			return self.polygon_options;
		}).addTo(this.map);
		this.current_marker_feature_group = L.featureGroup();
		this.map_multilocation_map_tags = null;
		this.map_multilocation_map_tags_objects = {};
		this.load_regions_ajax_queue = new AjaxQueue({concurrency: 6});

		this.number_of_simultaneous_regions = 20;

		this.__inverse_polygon_outer_rings = [this.inverted_map_mask.getLatLngs()];
		this.__inverse_polygon_inner_rings = [];

		this.polygon_geojson;
		this.point_geojson;

		this.__marker_image_url = '/maps/get_marker_for_map/' + encodeURIComponent(this.polygon_options.fillColor ? this.polygon_options.fillColor : '#ff0000');
		this.map_marker_icon_object = {
			iconUrl: this.__marker_image_url,
			iconSize: [10, 10],
			iconAnchor: [5, 5],
			popupAnchor: [5, 5]
		};

		this.__geofencing_markers = {
			conquesting: {
				iconUrl: "/images/geofence_conquesting.svg",
				iconSize: [50, 50],
				iconAnchor: [25, 25],
				popupAnchor: [25, 25]
			},
			proximity: {
				iconUrl: "/images/geofence_proximity.svg",
				iconSize: [50, 50],
				iconAnchor: [25, 25],
				popupAnchor: [25, 25]
			}
		};
		this.is_geofencing = false;

		/**
		 * The function that handles showing polygons/points on the map based on zoom level
		 *
		 * @param {object} self, instantiated object of the map class
		 */
		function smart_region_handler(self)
		{
			window.setTimeout(function(){
				var map_bounds = self.map.getBounds();
				var bounds_rectangle = L.rectangle(map_bounds);
				var visible_area = (LGeo.area(bounds_rectangle) / 1000000);
				var used_area = 0;

				self.current_polygon_feature_group.eachLayer(function(layer){
					var main_layer = layer;
					if(map_bounds.intersects(main_layer.getBounds()))
					{
						used_area += (LGeo.area(main_layer) / 1000000);
					}
				});

				var ratio = used_area / visible_area;
				var ratio_cutoff = <?php echo $polygon_dots_ratio_cutoff; ?>;
				(ratio < ratio_cutoff && self.map.getZoom() < 8) ? change_map_regions_view.call(self, "point") : change_map_regions_view.call(self, "polygon");

			}, 1);
		}

		/**
		 * The function that changes the geography type of the map
		 * Changes from polygon to point or vice versa
		 *
		 * @param {string} geography_type, either string or polygon; which geography type should be used for map
		 */
		function change_map_regions_view(geography_type)
		{
			if(geography_type == "polygon")
			{
				if(this.map_main_geography_type != "polygon")
				{
					this.map_main_geography_type = "polygon";

					if(this.use_inverted_map)
					{
						this.useInvertedMap(true);
					}
					else
					{
						if(!this.map.hasLayer(this.current_polygon_feature_group))
						{
							this.map.addLayer(this.current_polygon_feature_group);
						}
					}

					if(this.map.hasLayer(this.current_marker_feature_group))
					{
						this.map.removeLayer(this.current_marker_feature_group);
					}
				}
			}
			else
			{
				if(this.map_main_geography_type != "point")
				{
					this.map_main_geography_type = "point";

					if(this.use_inverted_map)
					{
						var self = this;
						this.useInvertedMap(false, true);
						this.use_inverted_map = true;
					}

					if(this.map.hasLayer(this.current_polygon_feature_group))
					{
						this.map.removeLayer(this.current_polygon_feature_group);
					}

					if(!this.map.hasLayer(this.current_marker_feature_group))
					{
						this.map.addLayer(this.current_marker_feature_group);
					}
				}
			}
		}

		/**
		 * Extends default map options with user supplied ones
		 *
		 * @param {object} options, user supplied options
		 */
		function handle_user_passed_options(options)
		{
			for(var specific_option in options)
			{
				if(specific_option.toLowerCase().indexOf('color') > -1)
				{
					options[specific_option] = convert_to_hex(options[specific_option]);
				}
				jQuery.extend(this[specific_option], this[specific_option], options[specific_option]);
			}
		}
	}

	FrequenceMap.prototype = {
		// methods
		takeSnapshot: function(callback)
		{
			leafletImage(this.map, callback);
		},
		/**
		 * The method that loads regions of specified type and ids on the map
		 * When finished, it calls a user_supplied callback
		 *
		 * @param {Array} regions, an array of ids which map to regions in the database
		 * @param {string} region_type, the type of region that the ids reference, default zcta
		 * @return {function} callback, the function a user wants called when the regions/map finish loading
		 */
		loadRegions: function(regions, region_type, callback)
		{
			if(!region_type)
			{
				region_type = 'zcta';
			}

			var number_of_regions_to_load = regions.length;
			var number_of_regions_loaded = 0;
			var polygons;
			var points;
			var self = this;

			function checkIfFinished()
			{
				if(number_of_regions_loaded == number_of_regions_to_load)
				{
					self.polygon_geojson = null;

					if(self.use_inverted_map)
					{
						var new_latlng_array = self.__createInvertedPolygonArray();
						self.inverted_map_polygon.setLatLngs(new_latlng_array);
					}

					if(callback && typeof(callback) === "function")
					{
						self.map.once('zoomend moveend', function() {
							callback();
						});
					}

					var map_size = self.map.getSize();
					var map_width = map_size.x;
					var fit_bounds_object = (self.is_geofencing) ? {paddingTopLeft: [map_width * 0.3, 0]} : {};
					if(self.force_points)
					{
						self.useInvertedMap(false);
						self.map.fitBounds(self.current_marker_feature_group.getBounds(), fit_bounds_object);
					}
					else
					{
						self.map.fitBounds(self.current_polygon_feature_group.getBounds(), fit_bounds_object);
					}
				}
			}

			this.force_points = (regions.length >= 2000);
			this.number_of_simultaneous_regions = (this.force_points) ? 2000 : 20;
			while(regions.length)
			{
				this.load_regions_ajax_queue.push({
					async: true,
					type: "POST",
					dataType: "json",
					data: {
						region_id: regions.splice(0, self.number_of_simultaneous_regions).join(","),
						region_type: region_type,
						use_points: self.force_points
					},
					url: "/maps/get_region_geojson_to_draw",
					success: function(response_data, textStatus, jqXHR) {
						if(vl_is_ajax_call_success(response_data))
						{
							if(response_data.region_blob)
							{
								if(self.force_points)
								{
									points = JSON.parse(response_data.region_blob);
									self.point_geojson = L.geoJson(points, {
										pointToLayer: function(feature, latlng){
											var marker = L.marker(latlng, {
												icon: L.icon(self.map_marker_icon_object),
												title: feature.properties.region_id,
												riseOnHover: true
											});
											number_of_regions_loaded++;
											self.current_marker_feature_group.addLayer(marker);
											self.current_marker_object[feature.properties.region_id.toString()] = marker;
											checkIfFinished();

											return marker;
										}
									});
									number_of_regions_to_load -= response_data.regions_without_geos; // Account for the regions that weren't real
								}
								else
								{
									polygons = JSON.parse(response_data.region_blob);
									self.polygon_geojson = L.geoJson(polygons, {style: function(){return self.polygon_options;}});
									number_of_regions_to_load -= response_data.regions_without_geos; // Account for the regions that weren't real
									self.polygon_geojson.eachLayer(function(layer){
										window.setTimeout(function(){
											var returned_region_id = layer.feature.properties.region_id.toString();
											self.current_active_regions.push(returned_region_id);
											self.__addRegion(returned_region_id, layer);
											number_of_regions_loaded++;
											checkIfFinished();
										}, 1);
									});
								}
							}
						}
						else
						{
							handle_ajax_controlled_error(response_data, "Failed to retrieve polygon ");
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						if(textStatus != 'abort')
						{
							vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
						}
					},
					complete: checkIfFinished
				});
			}
		},
		removeRegions: function(regions)
		{
			var number_of_regions_removed = 0;
			for(var i = 0; i < regions.length; i++)
			{
				if(this.current_polygon_object.hasOwnProperty(regions[i].toString()))
				{
					var region_id = regions[i].toString();

					// Remove from map
					this.map.removeLayer(this.current_polygon_object[region_id]);

					// Remove from featureGroup
					this.current_polygon_feature_group.removeLayer(this.current_polygon_object[region_id]);

					// Remove property from feature group object
					delete this.current_polygon_object[region_id];

					// Remove region from array of region ids
					delete this.current_active_regions[jQuery.inArray(region_id, this.current_active_regions)];

					number_of_regions_removed++;
				}
			}
			if(this.use_inverted_map && number_of_regions_removed)
			{
				this.inverted_map_polygon.setLatLngs(this.__createInvertedPolygonArray(true));
				this.__handleMapInversion();
			}
		},
		useInvertedMap: function(should_use_inverted_map, just_remove_overlay)
		{
			if(this.force_points)
			{
				should_use_inverted_map = false;
			}
			this.use_inverted_map = should_use_inverted_map;
			this.__handleMapInversion(just_remove_overlay);
		},
		/**
		 * The method that takes region ids of regions already on the map, gets the centers, and adds map tags
		 * Instantiates a map and has commonly-used methods built in for handling regions
		 *
		 * @param {Array} array_of_ids, the ids the map tag will be describing
		 * @param {string} title, the desired title of the map tag
		 */
		addMapTags: function(array_of_ids, title, is_rooftops)
		{
			var centers = [];

			if (is_rooftops)
			{
				var coords = array_of_ids.coordinates;
				centers.push(L.latLng(coords.lat, coords.lng));
			} else {
				for(var i = 0; i < array_of_ids.length; i++)
				{
					if(this.force_points)
					{
						centers.push(this.current_marker_object[array_of_ids[i].toString()].getLatLng());
					}
					else
					{
						centers.push(this.current_polygon_object[array_of_ids[i].toString()].getBounds());
					}
				}
			}
			this.map_multilocation_map_tags_objects[title] = L.latLngBounds(centers);
		},
		displayMapTags: function()
		{
			if(this.map_multilocation_map_tags)
			{
				this.map_multilocation_map_tags.clearLayers();
			}

			var map_tags_array = [];
			var self = this;

			for(var prop in this.map_multilocation_map_tags_objects)
			{
				(function(prop) {
					if(self.map_multilocation_map_tags_objects.hasOwnProperty(prop))
					{
						var marker_title = prop;
						var marker_bounds = self.map_multilocation_map_tags_objects[prop];
						var marker = L.marker(marker_bounds.getCenter(), {
							icon: L.mapbox.marker.icon({
								'marker-color': self.polygon_options.fillColor,
								'marker-size': 'large'
							}),
							title: marker_title,
							riseOnHover: true
						});
						marker.on("click", function(){
							self.map.fitBounds(marker_bounds);
						});
						map_tags_array.push(marker);
					}
				})(prop);
			}

			if(map_tags_array.length)
			{
				this.map_multilocation_map_tags = L.featureGroup(map_tags_array);
				this.map_multilocation_map_tags.addTo(this.map);
			}
		},
		showZoomControls: function(show_zoom_controls)
		{
			(show_zoom_controls) ? this.zoom_controls.addTo(this.map) : this.zoom_controls.removeFrom(this.map);
		},
		changeMarkerRadius: function(radius)
		{
			var cast_radius = Number(radius);
			this.map_marker_icon_object.iconUrl = this.__marker_image_url + "/" + cast_radius.toString();
			this.map_marker_icon_object.iconSize = [(cast_radius * 2), (cast_radius * 2)];
			this.map_marker_icon_object.iconAnchor = [cast_radius, cast_radius];
			this.map_marker_icon_object.popupAnchor = [cast_radius, cast_radius];
		},
		addGeofencingMarker: function(geofence_object)
		{
			var geofence_latlng = geofence_object.latlng;
			var geofence_title = geofence_object.search_term + " - " + geofence_object.radius + " meters";
			var icon_to_use = geofence_object.type;
			L.marker(geofence_latlng, {
				icon: L.icon(this.__geofencing_markers[icon_to_use]),
				title: geofence_title,
				riseOnHover: true
			}).addTo(this.map);
		},
		/**
		 * The method for handling a single region being added to the object
		 * Handles adding polygon to non-inverted and inverted objects, and handles displaying polygon holes
		 *
		 * @param {string} region_id, the region id of the polygon to be handled
		 * @param {object} polygon, the Mapbox polygon object from L.geojson.getLayers()[0]
		 */
		__addRegion: function(region_id, polygon)
		{
			var self = this;
			this.current_polygon_object[region_id] = polygon;
			var bounds = this.current_polygon_object[region_id].getBounds();

			self.current_polygon_feature_group.addLayer(polygon);
			self.current_marker_feature_group.addLayer(
				L.marker(bounds.getCenter(), {
					icon: L.icon(self.map_marker_icon_object),
					title: region_id,
					riseOnHover: true
				})
			);

			var inner_layer = this.current_polygon_object[region_id];
			var inner_layer_geometry = inner_layer.feature.geometry;

			var outer_ring_latlngs = inner_layer.getLatLngs();

			var holes = [];
			if(inner_layer_geometry.type == "MultiPolygon")
			{
				self.__inverse_polygon_outer_rings = self.__inverse_polygon_outer_rings.concat(outer_ring_latlngs);
				for(var i = 0; i < inner_layer_geometry.coordinates.length; i++)
				{
					holes = holes.concat(handle_polygons_with_holes(inner_layer_geometry.coordinates[i]));
				}
			}
			else
			{
				self.__inverse_polygon_outer_rings.push(outer_ring_latlngs);
				holes = holes.concat(handle_polygons_with_holes(inner_layer_geometry.coordinates));
			}

			if(holes.length > 0)
			{
				self.__inverse_polygon_inner_rings = self.__inverse_polygon_inner_rings.concat(holes);
			}
		},
		/**
		 * The class for handling whether or not the map should be inverted
		 * Checks variable use_inverted_map and toggles map inversion if needed
		 *
		 * @param {boolean} just_remove_overlay, optional boolean which is used when the map is inverted but map tags are needed
		 */
		__handleMapInversion: function(just_remove_overlay)
		{
			var self = this;
			if(this.use_inverted_map)
			{
				// Show the polygon and hide everything else
				this.current_polygon_feature_group.eachLayer(function(layer){
					self.map.removeLayer(layer);
				});
				this.map.removeLayer(this.current_polygon_feature_group);

				if(this.inverted_map_polygon)
				{
					this.inverted_map_polygon.setLatLngs(this.__createInvertedPolygonArray());
					if(!this.map.hasLayer(this.inverted_map_polygon))
					{
						this.inverted_map_polygon.addTo(this.map);
					}
				}
				else
				{
					this.inverted_map_polygon = this.__createInvertedPolygon(true).addTo(this.map);
				}
			}
			else
			{
				if(this.map.hasLayer(this.inverted_map_polygon))
				{
					this.map.removeLayer(this.inverted_map_polygon);
				}

				if(just_remove_overlay !== true)
				{
					this.current_polygon_feature_group.eachLayer(function(layer){
						self.map.addLayer(layer);
					});
				}
			}
		},
		/**
		 * The method for creating the inner rings of the inversed multipolygon for the world map
		 * Mapbox doesn't supply the holes easily, so gotten from geojson and new polygons are created from them
		 */
		__createInvertedPolygonRings: function()
		{
			this.__inverse_polygon_outer_rings = [this.inverted_map_mask.getLatLngs()];
			this.__inverse_polygon_inner_rings = [];

			var self = this;
			this.current_polygon_feature_group.eachLayer(function(layer){
				var inner_layer = layer;
				var inner_layer_geometry = inner_layer.feature.geometry;

				var outer_ring_latlngs = inner_layer.getLatLngs();
				var holes = [];
				if(inner_layer_geometry.type == "MultiPolygon")
				{
					self.__inverse_polygon_outer_rings = self.__inverse_polygon_outer_rings.concat(outer_ring_latlngs);
					for(var i = 0; i < inner_layer_geometry.coordinates.length; i++)
					{
						holes = holes.concat(handle_polygons_with_holes(inner_layer_geometry.coordinates[i]));
					}
				}
				else
				{
					self.__inverse_polygon_outer_rings.push(outer_ring_latlngs);
					holes = holes.concat(handle_polygons_with_holes(inner_layer_geometry.coordinates));
				}

				if(holes.length > 0)
				{
					self.__inverse_polygon_inner_rings = self.__inverse_polygon_inner_rings.concat(holes);
				}
			});
		},
		__createInvertedPolygonArray: function(recreate_inverted_polygon)
		{
			if(recreate_inverted_polygon)
			{
				this.__createInvertedPolygonRings();
			}

			var inverted_polygon_array = [
				this.__inverse_polygon_outer_rings.concat(this.__inverse_polygon_inner_rings)
			];

			return inverted_polygon_array;
		},
		__createInvertedPolygon: function(recreate_inverted_polygon)
		{
			var multi_polygon_array = this.__createInvertedPolygonArray(recreate_inverted_polygon);
			return L.multiPolygon(multi_polygon_array, {
				color: "#000000",
				weight: 1,
				opacity: 0.2,
				fillColor: '#000000',
				fillOpacity: 0.5
			});
		}
	};

	// Mapbox doesn't give the latlngs for the holes in polygons
	function handle_polygons_with_holes(polygon)
	{
		var holes = [];
		if(polygon.length > 1)
		{
			var outer = {type: "Polygon", coordinates: polygon};
			var temp_outer = L.geoJson(outer).getLayers()[0];

			for(var i = 1; i < polygon.length; i++)
			{
				var fake_poly_geojson = {type: "Polygon", coordinates: [polygon[i]]};
				var temp_poly = L.geoJson(fake_poly_geojson).getLayers()[0];

				holes.push(temp_poly.getLatLngs());
			}
		}
		return holes;
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

	window.FrequenceMap = FrequenceMap;

}());

</script>

<?php $this->load->view('vl_platform_2/ui_core/js_error_handling'); write_vl_platform_error_handlers_js(); ?>
