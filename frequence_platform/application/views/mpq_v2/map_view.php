<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<title>MPQ Map</title>

		<style type="text/css">
			body {
				margin: 0px;
				padding: 0px;
			}
			#map_demos {
				position: relative;
				color: #333;
			}
			#map_demos .demos_left {
				margin:20px auto;
				font-family: sans-serif;
				font-size: 20px;
				font-weight: 100;
				width:120px;
			}
			#map_demos .demos_left div.demo {
				/*margin-bottom: 10px;*/
			}
			#map_demos .demos_left label {
				display:block;
				text-transform: uppercase;
				font-size: 11px;
				letter-spacing:-0.03em;
			}
			#map_demos .demo_column {
				width:190px;
				position: relative;
				box-sizing: border-box;
				-moz-box-sizing: border-box;
				margin: auto;
			}
			.demographic_group {
				margin: 20px 0;
				position:relative;
			}
			.demographic_group_title {
				color: #414142;
				font-family:BebasNeue, sans-serif;
				font-size:16px;
				text-align: left;
				border:0px solid black;
			}
			.demographic_group figure {
				position: relative;
				margin: 0 40px;
			}
			.demographic_row_name {
				color: #414142;
				font-family:Oxygen, sans-serif;
				font-size:11px;
				text-align: right;
				width:65px;
				position: absolute;
				left: -70px;
				top: 0px;
			}
			.demographic_sparkline {
				width:106px;
			}
			.extra_demographic_data_title {
				font-family:Oxygen, sans-serif;
				color:#414142;
				font-size:14px;
			}
			.extra_demographic_data_value {
				font-family:BebasNeue, sans-serif;
				color:#414142;
				font-size:24px;
			}
			.internet_average_subtext {
				color: #c1c2c3;
				font-family:Oxygen, sans-serif;
				font-size:11px;
				text-align: center;
			}
			.internet_average_center_line {
				position: absolute;
				height:85%;
				top:20px;
				left: 93px;
				width:1px;
				border-right:1px solid #eaeaea;
			}
			#jqstooltip, #jqstooltip:before, #jqstooltip:after {
				box-sizing: initial !important;
			}
		</style>

		<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $google_access_token_rfp_io; ?>&libraries=places"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script type="text/javascript" src="/js/lap/maplabel-compiled.js"></script>
		<script type="text/javascript" src="/js/jquery.sparkline.js"></script>
		<script type="text/javascript" src="/js/smb/demographic_common_code.js"></script>

		<?php echo $shared_js; ?>
		<script type="text/javascript">

			var map;
			var page = 'mpq';
			var location_id = <?php echo $location_id; ?>;
			var geofencing_points = <?php echo !empty($geofencing_points) ? $geofencing_points : 'null'; ?>;

			function initialize()
			{
				<?php
					echo 'var target_region = \''.addslashes($stats_data['target_region']).'\';'."\n"; //TODO: investigate further.  addslashes adds a backslash for quote, doublequote and backslash character
					echo 'var population = \''.addslashes($stats_data['population']).'\';'."\n";
					echo 'var income = \''.addslashes($stats_data['income']).'\';'."\n";
				?>

				update_geo_stats(population, income, target_region);
				var styled_map = new google.maps.StyledMapType(
					styles,
					{name: "Styled Map"}
				);
				var loading_image = document.getElementById("loading_image");
				loading_image.innerHTML = '<img src="/images/mpq_v2_loader.gif" />'
				map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
				map.mapTypes.set('map_style', styled_map);
				map.data.setStyle(feature_style);
				map.setMapTypeId('map_style');
				world_polygon = new google.maps.Polygon(world_polygon_create_object);
				var map_blobs = {is_big_map: <?php echo intval($big_map); ?>, geojson_blob: '<?php echo $map_objects; ?>'};
				if(map_blobs.geojson_blob === 'false')
				{
					map.setCenter(us_center);
					map.setZoom(default_us_zoom);
					$('#editBut').prop("disabled", true);
				}
				else
				{
					load_map_with_data(map_blobs);
					if(geofencing_points)
					{
						for(var i = 0; i < geofencing_points.length; i++)
						{
							var geofence_object = geofencing_points[i];
							create_geofence_marker(geofence_object);
						}
					}
				}
				initialize_shared_map_events(map_blobs.is_big_map);
				google.maps.event.addListener(map, 'bounds_changed', function(){
					if(is_edit_mode)
					{
						load_edit_mode();
					}
				});
				google.maps.event.addListenerOnce(map, 'idle', function() {
					window.parent.$("#map_loading_image").hide("fast");
					window.parent.$("#region-links").fadeTo(200, 1);
				});
				google.maps.event.addListener(map, 'zoom_changed', function(){
					var zoom = map.getZoom();
					if(zoom < minimum_zoom_level_for_the_map)
					{
						map.setZoom(minimum_zoom_level_for_the_map);
					}
					if(marker_cluster_infobubble)
					{
						marker_cluster_infobubble.close();
					}
					if(!map_blobs.is_big_map)
					{
						world_polygon.setOptions({strokeWeight: stroke_weight_for_zoom_level_table[zoom]});
						edit_button_zoom_actions(zoom);
					}
					else
					{
						handle_grid_zoom_size(zoom);
					}
				});
				var demos = <?php echo json_encode($stats_data); ?>;
				demo_graphics(demos);
			}
			function demo_graphics(demos)
			{
				if (!demos.population || parseInt(demos.population, 10) == 0)
				{
					$('#map_demos').hide(0);
					window.parent.$('#region-links').height(450);
					return false;
				}
				else
				{
					window.parent.$('#region-links').height(750);
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
					"age_65_and_over":			<?php echo $national_averages_array['age_65_and_over']; ?>,
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
					"college_grad":				<?php echo $national_averages_array['college_grad']; ?>
				}
				$('span.target_population').text(demos['population']);
				$('span.income').text(demos['income']);
				$('span.median_age').text(demos['demographics']['median_age']);
				$('span.persons_household').text(demos['demographics']['persons_household']);
				$('span.average_home_value').text(demos['demographics']['average_home_value']);
				$('span.num_establishments').text(demos['demographics']['num_establishments']);
				for (var key in demos['demographics'])
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
							(((demos['demographics'][key] / demos['demographics']['total_households']) * 100) / averages[key]),
							(Math.round((demos['demographics'][key] / demos['demographics']['total_households']) * 1000) / 10),
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
							(((demos['demographics'][key] / demos['demographics']['normalized_race_population']) * 100) / averages[key]),
							(Math.round((demos['demographics'][key] / demos['demographics']['normalized_race_population']) * 1000) / 10),
							"#sparkline_" + key
						);
					}
					else
					{
						sparkline(
							(((demos['demographics'][key] / demos['demographics']['region_population']) * 100) / averages[key]),
							(Math.round((demos['demographics'][key] / demos['demographics']['region_population']) * 1000) / 10),
							"#sparkline_" + key
						);
					}
				}
			}
			function update_geo_stats(population, income, target_region)
			{
				if (in_iframe() && (typeof parent.update_geo_stats != 'undefined'))
				{
					parent.update_geo_stats(population, income, target_region);
				}
			}
			function get_map_center_latitude()
			{
				if(typeof map !== "undefined")
				{
					var lat_lng = map.getCenter();
					return lat_lng.lat();
				}
				else
				{
					return 0;
				}
			}
			function get_map_center_longitude()
			{
				if(typeof map !== "undefined")
				{
					var lat_lng = map.getCenter();
					return lat_lng.lng();
				}
				else
				{
					return 0;
				}
			}
			google.maps.event.addDomListener(window, 'load', initialize);
		</script>
	</head>

	<body>

		<?php
			if($search_type == 'zcta' && !$big_map)
			{
				//echo '<a id="editBut" class="btn tooltipped waves-effect waves-light disabled" data-position="bottom" data-delay="30" data-tooltip="To edit, zoom in" style="position:absolute;padding:0 10px;top:12px;right:60px;z-index:10000000;"><i class="material-icons">mode_edit</i></a>';
				echo '<a id="editBut" class="btn tooltipped waves-effect waves-light disabled" data-position="bottom" data-delay="30" data-tooltip="To edit, zoom in" style="position:absolute;padding:0 10px;top:12px;right:60px;z-index:10000000;"><i class="material-icons">&#xE254;</i></a>';
			}
		?>

		<div style="absolute; left:50%; top:150px; margin-left: -48px; width:96px; height:96px; display:none" id="loading_image"></div>
		<div class="row">
			<div class="col s12" style="padding:0px;">
				<div style="position:relative;height:400px;width:100%; border-radius:3px" id="map_canvas"></div>
				<div id="map_view_errors" style="background-color:red;"></div>
			</div>
		</div>

			<div class="row" id="map_demos">
				<div class="col s12 m6 l3">
					<div class="demos_left">
						<div class="demo">
							<label>Population</label>
							<span class="target_population"></span>
						</div>
						<div class="demo">
							<label>Average Income</label>
							<span class="income"></span>
						</div>
						<div class="demo">
							<label>Median Age</label>
							<span class="median_age"></span>
						</div>
						<div class="demo">
							<label>People / Household</label>
							<span class="persons_household"></span>
						</div>
						<div class="demo">
							<label>Average Home Value</label>
							<span class="average_home_value"></span>
						</div>
						<div class="demo">
							<label>#of Businesses</label>
							<span class="num_establishments"></span>
						</div>
					</div>
				</div>

				<div class="col s12 m6 l3">
					<div class="demo_column">
						<div class="demographic_group">
							<div class="demographic_group_title" style="">Gender</div>
							<div class="internet_average_center_line"></div>
							<figure>
								<div id="sparkline_male_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Male:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_female_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Female:</figcaption>
							</figure>
						</div>

						<div class="demographic_group">
							<div class="demographic_group_title" style="">Age</div>
							<div class="internet_average_center_line"></div>
							<figure>
								<div id="sparkline_age_under_18" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">&lt; 18:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_age_18_24" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">18 - 24:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_age_25_34" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">25 - 34:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_age_35_44" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">35 - 44:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_age_45_54" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">45 - 54:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_age_55_64" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">55 - 64:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_age_65_and_over" css="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">65 +:</figcaption>
							</figure>
						</div>

						<div class="internet_average_subtext">US Average</div>
					</div>
				</div>

				<div class="col s12 m6 l3">
					<div class="demo_column">
						<div class="demographic_group">
							<div class="demographic_group_title" style="">Household Income</div>
							<div class="internet_average_center_line"></div>
							<figure>
								<div id="sparkline_income_0_50" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">&lt; $50k:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_income_50_100" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">$50k-100k:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_income_100_150" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">$100k-150k:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_income_150" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">$150k +:</figcaption>
							</figure>
						</div>

						<div class="demographic_group" style="left:0px; top:0px;">
							<div class="demographic_group_title" style="">Education Level</div>
							<div class="internet_average_center_line"></div>
							<figure>
								<div id="sparkline_college_no" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">No College:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_college_under" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">College:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_college_grad" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Grad School:</figcaption>
							</figure>
						</div>

						<div class="internet_average_subtext">US Average</div>
					</div>
				</div>

				<div class="col s12 m6 l3">
					<div class="demo_column">
						<div class="demographic_group">
							<div class="demographic_group_title" style="">Children In Household</div>
							<div class="internet_average_center_line"></div>
							<figure>
								<div id="sparkline_kids_no" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">No Kids:</figcaption>
							</figure>
							<figure>
								<div id="sparkline_kids_yes" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Has Kids:</figcaption>
							</figure>
						</div>

						<div class="demographic_group">
							<div class="demographic_group_title" style="">Ethnicity</div>
							<div class="internet_average_center_line"></div>
							<figure>
								<div id="sparkline_white_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Cauc:  </figcaption>
							</figure>
							<figure>
								<div id="sparkline_black_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Afr Amer: </figcaption>
							</figure>
							<figure>
								<div id="sparkline_asian_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Asian: </figcaption>
							</figure>
							<figure>
								<div id="sparkline_hispanic_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Hisp: </figcaption>
							</figure>
							<figure>
								<div id="sparkline_other_race_population" class="demographic_sparkline"></div>
								<figcaption class="demographic_row_name">Other: </figcaption>
							</figure>
						</div>

						<div class="internet_average_subtext">US Average</div>
					</div>
				</div>
			</div>
	</body>
</html>