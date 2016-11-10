function ReportHeatmap(map_container)
{
	this.map_container = map_container;
	this.initialize_new_map();
}

(function() {

	var heatmap;
	var heatmap_div_id;

	var initialized = false;
	var minimum_zoom_level_for_the_map = 4;
	var us_center = new google.maps.LatLng(39.8282239, -98.579569);
	var default_us_zoom = 4;
	var heatmap_ranges = {};

	var hover_infobubble = null;
	var heatmap_infobubble_creation_object = {
		disableAutoPan: true,
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

	this.initialize_new_map = function()
	{
		destroy_previous_data();
		heatmap_div_id = generate_random_id_for_heatmap();
		fill_container_with_map_html(this.map_container);
		initialize_google_map();
	}

	this.build_heatmap_from_data = function(geojson_blob)
	{
		destroy_previous_data();

		var geojson = JSON.parse(geojson_blob);
		heatmap.polygons = heatmap.data.addGeoJson(geojson);

		var heatmap_range_array = {
			impressions: [],
			impressions_per_capita: [],
			visits: [],
			visits_per_capita: []
		}

		heatmap.polygons.forEach(function(polygon){
			var polygon_object = {
				impressions: polygon.getProperty('impressions'),
				clicks: polygon.getProperty('clicks'),
				viewthroughs: polygon.getProperty('viewthroughs')
			};

			var population = parseInt(polygon.getProperty('population'), 10);

			var impressions = parseInt(polygon.getProperty('impressions'), 10);
			var visits = parseInt(polygon.getProperty('clicks'), 10); // Add viewthroughs when viewthroughs available for zcta reporting

			polygon.setProperty("visits", visits);

			var imps_per_capita = (impressions * 1000.0) / (population * 1000);
			var clks_per_capita = (visits * 1000.0) / (population * 1000);

			heatmap_range_array.impressions.push(impressions);
			heatmap_range_array.visits.push(visits);
			heatmap_range_array.impressions_per_capita.push(isNaN(imps_per_capita) || !isFinite(imps_per_capita) ? 0 : imps_per_capita);
			heatmap_range_array.visits_per_capita.push(isNaN(clks_per_capita) || !isFinite(clks_per_capita) ? 0 : clks_per_capita);
		});

		heatmap_ranges.impressions = [
			Math.min.apply(Math, heatmap_range_array.impressions),
			Math.max.apply(Math, heatmap_range_array.impressions)
		];
		heatmap_ranges.impressions_per_capita = [
			Math.min.apply(Math, heatmap_range_array.impressions_per_capita),
			Math.max.apply(Math, heatmap_range_array.impressions_per_capita)
		];
		heatmap_ranges.visits = [
			Math.min.apply(Math, heatmap_range_array.visits),
			Math.max.apply(Math, heatmap_range_array.visits)
		];
		heatmap_ranges.visits_per_capita = [
			Math.min.apply(Math, heatmap_range_array.visits_per_capita),
			Math.max.apply(Math, heatmap_range_array.visits_per_capita)
		];

		heatmap.data.setStyle(function(feature) {

			var color_info = get_polygon_colors_and_opacity_based_on_map_form(feature);
			return {
				fillColor: color_info.color,
				strokeWeight: 1,
				strokeColor: "#ffffff",
				strokeOpacity: 0.75,
				strokeWeight: 1,
				fillOpacity: color_info.opacity
			};
		});

		heatmap.data.addListener('mouseover', function(event) {
			heatmap.data.revertStyle();

			if(hover_infobubble && hover_infobubble.isOpen)
			{
				hover_infobubble.close();
				hover_infobubble = null;
			}

			var num_visits = parseInt(event.feature.getProperty('clicks'), 10);

			var region_string = (event.feature.getProperty("city").split(", ").length == 1) ? "Region: " : "Regions: "
			var state_string = (event.feature.getProperty("region").split(", ").length == 1) ? "State: " : "States: "
			var html_content = 
				"Zip: " + event.feature.getProperty("region_id") +
				"<br>" +
				event.feature.getProperty("city") +
				"<br>" +
				event.feature.getProperty("region") + 
				"<br>" +
				// "Impressions: " + event.feature.getProperty("impressions") + 
				// "<br>" +
				// "Visits: " + num_visits + 
				// "<br>" +
				"Population: " + number_with_commas(event.feature.getProperty("population"));

			$("#map_tools_form_div div").html(html_content);

			heatmap.data.overrideStyle(event.feature, {
				strokeColor: "#666666",
				zIndex:2
			});
		});

		heatmap.data.addListener('mouseout', function(event) {
			heatmap.data.revertStyle();
		});

		zoom(heatmap);
	}

	function destroy_previous_data()
	{
		if(heatmap && heatmap.polygons)
		{
			heatmap.polygons.forEach(function(polygon, index, polygons)
			{
				heatmap.data.remove(polygon);
				delete heatmap.polygons[index];
			});
		}
	}

	function get_polygon_colors_and_opacity_based_on_map_form(feature)
	{
		// var metric_dom_object = $('input[name="heatmap_metric"]:checked');
		// var units_dom_object = $('input[name="heatmap_units"]:checked');

		var id_to_show = 'impressions'; // metric_dom_object.val();
		var property_val = parseInt(feature.getProperty(id_to_show), 10);

		// if(units_dom_object.val() == 'per_capita')
		// {
		// 	id_to_show += '_per_capita';
		// 	property_val = (property_val * 1.0) / parseInt(feature.getProperty('population'), 10);
		// }

		var cool_color = {r:255, g:204, b:0} // #FFCC00
		var hot_color = {r:255, g:0, b:0} // #FF0000

		property_val = (isNaN(property_val) || !isFinite(property_val) || property_val == 0) ? 2 : property_val;

		if(heatmap_ranges[id_to_show][0] <= 1) heatmap_ranges[id_to_show][0] = 2;

		var percent = (Math.log(property_val) - Math.log(heatmap_ranges[id_to_show][0])) / Math.log(heatmap_ranges[id_to_show][1]);
		return {
			color: make_gradient_color(cool_color, hot_color, percent),
			opacity: (property_val == 0) ? 0.15 : 0.75
		};
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

	function initialize_google_map() 
	{
		var styled_map = new google.maps.StyledMapType(
			[{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"administrative.province","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"visibility":"on"}]},{"featureType":"administrative.locality","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"administrative.locality","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.locality","elementType":"labels.text","stylers":[{"lightness":"66"}]},{"featureType":"administrative.neighborhood","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"administrative.neighborhood","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#46bcec"},{"visibility":"on"}]}],
			{name: "Styled Map"}
		);

		var myOptions = 
		{
			center: us_center,
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
		};

		heatmap = new google.maps.Map(document.getElementById(heatmap_div_id), myOptions);
		heatmap.mapTypes.set('map_style', styled_map);
		heatmap.data.setStyle(feature_style);
		heatmap.setMapTypeId('map_style');
		heatmap.polygons = [];

		var map_blobs = {
			is_big_map: false,
			is_heatmap: true,
			geojson_blob: 'false'
		};
		if(map_blobs.geojson_blob === 'false')
		{
			heatmap.setCenter(us_center);
			heatmap.setZoom(default_us_zoom);
		}
		else
		{
			load_map_with_data(map_blobs);
		}

		var loading_image = $("#map_loading_image_for_" + heatmap_div_id);
		google.maps.event.addListenerOnce(heatmap, 'idle', function() {
			loading_image.hide("fast");
		});

		google.maps.event.addListener(heatmap, 'zoom_changed', function(){
			var zoom = heatmap.getZoom();
			if(zoom < minimum_zoom_level_for_the_map)
			{
				heatmap.setZoom(minimum_zoom_level_for_the_map);
			}
		});
	}

	function fill_container_with_map_html(container_selector)
	{
		$(container_selector).html(
			'<div style="position: absolute; z-index:1000; left:50%; top:150px; margin-left: -48px; width:96px; height:96px" id="map_loading_image_for_' + heatmap_div_id + '">' + 
				'<img src="/images/loadingImage.gif" />' + 
			'</div>' + 
			'<div id="' + heatmap_div_id + '" style="height:400px;border: 1px solid #e0e0e0;"></div>' + 
			'<div class="map_tools" style="position:absolute;top:1%;left:2%;width:20%;font-weight:600;font-size: xx-small;">' + 
				'<span style="font-size:8.5px; font-family:Arial, Helvetica, sans-serif; position:relative; left:5px; top:-2px;" class="beta badge badge-important">Beta</span>' + 
				'<div id="map_tools_form_div" class="" style="background:rgba(0,0,0,0.7);padding: 0 10px;">' + 
					'<div style="color:white;text-transform: uppercase;">' + 
					'</div>' + 
					// '<div style="color:white;">' + 
					// 	'<form id="map_tools_form">' + 
					// 		'<strong>Metric</strong>' + 
					// 		'<label for="metric_radio_impressions" class="radio">' + 
					// 			'<input name="heatmap_metric" checked="checked" type="radio" value="impressions" id="metric_radio_impressions" />' + 
					// 			'Impressions' + 
					// 		'</label>' + 
					// 		'<label for="metric_radio_visits" class="radio">' + 
					// 			'<input name="heatmap_metric" type="radio" value="visits" id="metric_radio_visits" />' + 
					// 			'Site Visits' + 
					// 		'</label>' + 
					// 		'<strong>Units</strong>' + 
					// 		'<label for="units_radio_per_capita" class="radio">' + 
					// 			'<input name="heatmap_units" value="per_capita" checked="checked" type="radio" id="units_radio_per_capita" />' + 
					// 			'Per Capita' + 
					// 		'</label>' + 
					// 		'<label for="units_radio_total" class="radio">' + 
					// 			'<input name="heatmap_units" type="radio" value="in_total" id="units_radio_total" />' + 
					// 			'Total' + 
					// 		'</label>' + 
					// 	'</form>' + 
					// '</div>' + 
				'</div>' + 
			'</div>'
		);

		// $('#map_tools_form').off("change");
		// $('#map_tools_form').on("change", function(event){
		// 	var values = {};
		// 	var metric_dom_object = $('input[name="heatmap_metric"]:checked');
		// 	var units_dom_object = $('input[name="heatmap_units"]:checked');

		// 	values.metric = metric_dom_object.val();
		// 	values.units = units_dom_object.val();

		// 	change_heatmap_view(values);
		// });
	}

	// function change_heatmap_view(values_object)
	// {
	// 	var id_to_show = values_object.metric;
	// 	if(values_object.units == 'per_capita')
	// 	{
	// 		id_to_show += '_per_capita';
	// 	}

	// 	heatmap.data.setStyle(function(feature) {

	// 		var color_info = get_polygon_colors_and_opacity_based_on_map_form(feature);
	// 		return {
	// 			fillColor: color_info.color,
	// 			strokeWeight: 1,
	// 			strokeColor: '#ffffff',
	// 			strokeOpacity: 0.75,
	// 			strokeWeight: 1,
	// 			fillOpacity: color_info.opacity
	// 		};
	// 	});
	// }

	function zoom(heatmap) 
	{
		var bounds = new google.maps.LatLngBounds();
		heatmap.data.forEach(function(feature) 
		{
			process_points(feature.getGeometry(), bounds.extend, bounds);
		});
		heatmap.fitBounds(bounds);
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

	function number_with_commas(x)
	{
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}

	function generate_random_id_for_heatmap()
	{
		var d = new Date();
		var n = d.getTime();

		return 'heatmap_' + n;
	}

}).call(ReportHeatmap.prototype);