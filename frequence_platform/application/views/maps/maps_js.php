<script type='text/javascript'>

	function append_map(object_to_be_appended_to, geo_json_uri_piece, id_append, iframe_format_object)
	{
		var map_frame = $("<iframe class='map_iframe' scrolling='no' seamless src='" + '/maps/map_formatted_for_iframe_with_unique_id/' + geo_json_uri_piece + "' id='map_" + id_append + "'/>");
		(iframe_format_object && iframe_format_object.frameborder) ? map_frame.attr('frameborder', iframe_format_object.frameborder) : map_frame.attr('frameborder', '0');
		(iframe_format_object && iframe_format_object.width) ? map_frame.css('width', iframe_format_object.width) : map_frame.css('width', '100%');
		(iframe_format_object && iframe_format_object.height) ? map_frame.css('height', iframe_format_object.height) : map_frame.css('height', '600px');
		object_to_be_appended_to.append(map_frame);
	}

	function append_map_demographics(object_to_be_appended_to, geo_json_uri_piece, id_append, iframe_format_object)
	{
		var demo_frame = $("<iframe scrolling='no' seamless src='" + '/maps/demos_formatted_for_iframe_with_unique_id/' + geo_json_uri_piece + "' id='demos_" + id_append + "'/>");
		(iframe_format_object && iframe_format_object.frameborder) ? demo_frame.attr('frameborder', iframe_format_object.frameborder) : demo_frame.attr('frameborder', '0');
		(iframe_format_object && iframe_format_object.width) ? demo_frame.css('width', iframe_format_object.width) : demo_frame.css('width', '100%');
		(iframe_format_object && iframe_format_object.height) ? demo_frame.css('height', iframe_format_object.height) : demo_frame.css('height', '330px');
		object_to_be_appended_to.append(demo_frame);
	}

	function get_uri_for_map_of_given_region_types_with_ajax(region_object, object_to_be_appended_to, id_append, iframe_format_object)
	{
		if (!region_object.blobs && !region_object.points && !region_object.center) return null;
		if (!id_append) id_append = 0;

		$.ajax({
			url: '/maps/get_geo_json_uri_for_maps_through_ajax',
			dataType: 'json',
			async: true,
			type: "POST", 
			data: {
				blobs : ((region_object.blobs) ? region_object.blobs : null),
				points : ((region_object.points) ? region_object.points : null),
				center : ((region_object.center) ? region_object.center : null),
				affected_uris : ((region_object.uris) ? region_object.uris : null),
				radius : null
			},
			success: function(data) 
			{
				if(vl_is_ajax_call_success(data)) 
				{
					var select_dropdown = $('#sel_id');
					select_dropdown.append($('<option value="' + data.geojson_uri + '">' + 'Aggregate Map Data' + '</option>'));
				}
				else vl_show_ajax_response_data_errors(data, 'Something went wrong: ');
			},
			error: function(xhr, textStatus, error) 
			{
				vl_show_jquery_ajax_error(xhr, textStatus, error);
			}
		})
	}

</script>

<script type="text/javascript" src="/js/jquery.sparkline.js"></script>
<script type="text/javascript" src="/js/smb/demographic_common_code.js"></script>

<script type="text/javascript">
	
	function calculate_and_post_demographics(demos)
	{
		if (!demos.region_population)
		{
			$('#map_demos').hide(0);
			return false;
		}
		else
		{
			$('#map_demos').show(0);
		}
		
		// newly calculated national averages from db data
		var averages = 
		{
			"male_population":			<?php echo $national_averages_array['male_population']; ?>,
			"female_population":		<?php echo $national_averages_array['female_population']; ?>,
			"age_under_18":				<?php echo $national_averages_array['age_under_18']; ?>,
			"age_18_24":				<?php echo $national_averages_array['age_18_24']; ?>,
			"age_25_34":				<?php echo $national_averages_array['age_25_34']; ?>,
			"age_35_44":				<?php echo $national_averages_array['age_35_44']; ?>,
			"age_45_54":				<?php echo $national_averages_array['age_45_54']; ?>,
			"age_55_64":				<?php echo $national_averages_array['age_55_64']; ?>,
			"age_65":					<?php echo $national_averages_array['age_65']; ?>,
			"white_population":			<?php echo $national_averages_array['white_population']; ?>,
			"black_population":			<?php echo $national_averages_array['black_population']; ?>,
			"asian_population":			<?php echo $national_averages_array['asian_population']; ?>,
			"hispanic_population":		<?php echo $national_averages_array['hispanic_population']; ?>,
			"other_race_population":	<?php echo $national_averages_array['other_race_population']; ?>,
			"kids_no":					<?php echo $national_averages_array['kids_no']; ?>,
			"kids_yes":					<?php echo $national_averages_array['kids_yes']; ?>,
			"income_0_50":				<?php echo $national_averages_array['income_0_50']; ?>,
			"income_50_100":			<?php echo $national_averages_array['income_50_100']; ?>,
			"income_100_150":			<?php echo $national_averages_array['income_100_150']; ?>,
			"income_150":				<?php echo $national_averages_array['income_150']; ?>,
			"college_no":				<?php echo $national_averages_array['college_no']; ?>,
			"college_under":			<?php echo $national_averages_array['college_under']; ?>,
			"college_grad":				<?php echo $national_averages_array['college_grad']; ?>,
		}

		$('span.target_population').text(demos['region_population_formatted']);
		$('span.income').text(demos['household_income']);
		$('span.median_age').text(demos['median_age']);
		$('span.income').text(demos['household_income']);
		$('span.persons_household').text(demos['persons_household']);
		$('span.average_home_value').text(demos['average_home_value']);
		$('span.num_establishments').text(demos['num_establishments']);

		for (var key in demos)
		{
			if (
				key == 'income_0_50' ||
				key == 'income_50_100' ||
				key == 'income_100_150' ||
				key == 'income_150' ||
				key == 'kids_no' ||
				key == 'kids_yes'
			)
			{
				sparkline(
					(((demos[key] / demos['total_households']) * 100) / averages[key]),
					(Math.round((demos[key] / demos['total_households']) * 1000) / 10),
					"#sparkline_" + key
				);
			}
			else if (
				key == 'white_population' ||
				key == 'black_population' ||
				key == 'asian_population' ||
				key == 'hispanic_population' ||
				key == 'other_race_population'
			)
			{
				sparkline(
					(((demos[key] / demos['normalized_race_population']) * 100) / averages[key]),
					(Math.round((demos[key] / demos['normalized_race_population']) * 1000) / 10),
					"#sparkline_" + key
				);
				
			}
			else
			{
				sparkline(
					(((demos[key] / demos['region_population']) * 100) / averages[key]),
					(Math.round((demos[key] / demos['region_population']) * 1000) / 10),
					"#sparkline_" + key
				);
			}
			
		}
	}

</script>

<script type="text/javascript">

	var map;
	var latlngbounds;
	var geojson_blob;
	var circle_bounds;

	var markerInfoWindow;
	var markersArray = placeTypes = globalResults = [];
	var zipsArray = activeZips = zipsChanging = {};

	// Create our "tiny" marker icon
	var vlIcon;
	var gSmallShadow;

	var my_options;
	
	function zoom(map) 
	{
		var bounds = new google.maps.LatLngBounds();
		map.data.forEach(function(feature) 
		{
			process_points(feature.getGeometry(), bounds.extend, bounds);
		});
		map.fitBounds(bounds);
	}

	/**
	* Process each point in a Geometry, regardless of how deep the points may lie.
	* @param {google.maps.Data.Geometry} geometry The structure to process
	* @param {function(google.maps.LatLng)} callback A function to call on each
	*     LatLng point encountered (e.g. Array.push)
	* @param {Object} thisArg The value of 'this' as provided to 'callback' (e.g.
	*     myArray) https://developers.google.com/maps/documentation/javascript/examples/layer-data-dragndrop
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

	function load_geojson_string(geojson_blob) 
	{
		var geojson = JSON.parse(geojson_blob);
		map.polygons = map.data.addGeoJson(geojson);
	}

	function load_map_with_data(data)
	{
		map.polygons.forEach(function(polygon, index, polygons)
		{
			map.data.remove(polygon);
			delete map.polygons[index];
		});

		if (circles)
		{
			for (var i = 0; i < circles.length; i++)
			{
				circles[i].setMap(null);
			}
		}

		if (center_markers)
		{
			for (var i = 0; i < center_markers.length; i++)
			{
				center_markers[i].setMap(null);
			}
		}

		if (data.centers_and_radii != null && data.centers_and_radii.length > 0)
		{
			circles = [];
			for (var cnt_rad_arr in data.centers_and_radii)
			{
				var center = JSON.parse(data.centers_and_radii[cnt_rad_arr].center);
				var marker = new google.maps.Marker({
					map: map,
					position: new google.maps.LatLng(center.latitude, center.longitude),
					title: 'Center',
					icon: vlIcon
				});

				center_markers.push(marker);

				var circle = new google.maps.Circle({
					map: map,
					radius: (data.centers_and_radii[cnt_rad_arr].radius / 0.00062137),
					center: new google.maps.LatLng(center.latitude, center.longitude),
					strokeColor: "#f0f0f0",
					strokeOpacity: 0.75,
					strokeWeight: 1,
					fillColor: "#F96A0B",
					fillOpacity: 0.50,
				});

				circle.bindTo('center', marker, 'position');
				circles.push(circle);
			}
			
			circle_bounds = null;
			circle_bounds = new google.maps.LatLngBounds();
			$.each(circles, function(index, circle){
				circle_bounds.union(circle.getBounds());
			});
			map.fitBounds(circle_bounds);
		}
		else
		{
			load_geojson_string(data.geojson_blob);
			zoom(map);
		}
		
		google.maps.event.addListenerOnce(map, 'idle', function(){
			map_loaded();
		});
		map.setCenter(new google.maps.LatLng(map.getCenter().lat(), map.getCenter().lng() + .000000001));
	}

	function map_loaded()
	{
		$("#map_loading_image").hide("slow");
		$('#map_canvas').fadeTo(200, 1);
	}

	function show_map_loading()
	{
		$('#map_canvas').fadeTo(200, 0.5);
		$("#map_loading_image").show();
	}

	function initialize_map()
	{
		show_map_loading();
		script = document.createElement('script');
		script.type = 'text/javascript';
		script.src = '/js/lap/maplabel-compiled.js';
		document.body.appendChild(script);
		
		// Set up map variables
		latlngbounds = new google.maps.LatLngBounds();
		
		vlIcon = new google.maps.MarkerImage(
			"/images/marker.png",
			new google.maps.Size(45, 51),
			new google.maps.Point(0, 0),
			new google.maps.Point(17.5, 50)
		);

		invisible_icon = new google.maps.MarkerImage(
			"/images/transparent.png",
			new google.maps.Size(0, 0),
			new google.maps.Point(0, 0),
			new google.maps.Point(0, 0)
		);

		gSmallShadow = new google.maps.MarkerImage(
			"/images/shadow.png",
			new google.maps.Size(39, 30),
			new google.maps.Point(0, 0),
			new google.maps.Point(10.5, 30)
		);

		my_options = {
			disableDefaultUI: true,
			mapTypeId: google.maps.MapTypeId.TERRAIN,
			panControl: false,
			center: {lat: 37.09024, lng: -95.712891},
			panControlOptions: {
				position: google.maps.ControlPosition.TOP_RIGHT
			},
			scaleControl: true,
			zoom: 4,
			zoomControl: true,
			zoomControlOptions: {
				style: google.maps.ZoomControlStyle.MEDIUM,
				position: google.maps.ControlPosition.RIGHT_TOP
			},
			scrollwheel: false
		};

		// Create new map
		map = new google.maps.Map(document.getElementById('map_canvas'), my_options);
		var styles = JSON.parse('[{"featureType":"water","stylers":[{"saturation":43},{"lightness":-11},{"hue":"#0088ff"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"hue":"#ff0000"},{"saturation":-100},{"lightness":99}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"color":"#808080"},{"lightness":54}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"color":"#ece2d9"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"color":"#ccdca1"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"color":"#767676"}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"landscape.natural","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#b8cb93"}]},{"featureType":"poi.park","stylers":[{"visibility":"on"}]},{"featureType":"poi.sports_complex","stylers":[{"visibility":"on"}]},{"featureType":"poi.medical","stylers":[{"visibility":"on"}]},{"featureType":"poi.business","stylers":[{"visibility":"simplified"}]}]');

		var styled_map = new google.maps.StyledMapType(
			styles,
			{name: "Styled Map"}
		);
		
		var feature_style = {
			strokeColor: "#f0f0f0",
			strokeOpacity: 0.75,
			strokeWeight: 1,
			fillColor: "#F96A0B",
			fillOpacity: 0.50,
			zIndex:1,
			icon: invisible_icon
		}

		map.mapTypes.set('map_style', styled_map);
		map.setMapTypeId('map_style');
		map.data.setStyle(feature_style);

		// set up polygons array
		map.polygons = [];
		circles = [];
		center_markers = [];
		min_zoom = 8;

		google.maps.event.addListenerOnce(map, 'idle', function(){
			map_loaded();
		});

		google.maps.event.addListener(map, 'zoom_changed', function(){
			if (center_markers)
			{
				if (map.getZoom() > min_zoom)
				{
					marker_visibility(center_markers, false);
				}
				else
				{
					marker_visibility(center_markers, true);
				}
			}
		});
	}

	window.onload = initialize_map;

	function marker_visibility(markers, is_visible)
	{
		for (var i = 0; i < markers.length; i++)
		{
			center_markers[i].setVisible(is_visible);
		}
	}

	function createMarker(place, request, service) 
	{
		var placeLoc = place.geometry.location;
		var service2 = service;
		var request2 = request;
		var marker = new google.maps.Marker({
			map: map,
			position: placeLoc,
			icon: vlIcon, 
			shadow: gSmallShadow
		});
		

		markersArray.push(marker);
		service2.getDetails(request2, function(details, status) {
			google.maps.event.addListener(marker, 'mouseover', function() {
				markerInfoWindow.setContent(place.name);
				markerInfoWindow.open(map, this);
			});
		});
	}
	
</script>

<script type='text/javascript' src='https://maps.googleapis.com/maps/api/js?key=AIzaSyAVqHsbdM1Thk9WK5JlfbQIfor-2CKlq2g'></script>
