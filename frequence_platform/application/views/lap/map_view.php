<!DOCTYPE html>
<html style="height:100%;">
	<head>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

		<title>Planner Map</title>

		<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script type="text/javascript" src="/js/lap/maplabel-compiled.js"></script>
		<?php echo $shared_js; ?>
		<script type="text/javascript">

			var map;
			var page = 'planner';

			function initialize()
			{
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
					map.fitBounds(default_us_zoom);
				}
				else
				{
					load_map_with_data(map_blobs);
				}

				initialize_shared_map_events(map_blobs.is_big_map);

				google.maps.event.addListener(map, 'bounds_changed', function(){ 
					if(is_edit_mode)
					{
						load_edit_mode();
					}
				});

				google.maps.event.addListenerOnce(map, 'idle', function() {
					$("#loading_image").hide("fast");
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
			}

			google.maps.event.addDomListener(window, 'load', initialize);

		</script>
	</head>

	<body style="height:100%;">

		<?php
			if($search_type == 'zcta' && !$big_map)
			{
				echo '<a id="editBut" class="btn tooltipped waves-effect waves-light" data-position="bottom" data-delay="30" data-tooltip="To edit, zoom in" style="position:absolute;padding:0 10px;top:8px;right:40px;z-index:10000000;"><i class="material-icons">mode_edit</i></a>';
			}
		?>

		<div style="position: absolute; z-index: 100; left:46%; top:25%;" id="loading_image"></div>
		<div style="position:relative;margin-bottom:10px;float:left;height:75%;width:100%; border:1px solid #547980; border-radius:3px" id="map_canvas"></div>

		<div style="position:relative;float:left;width: 100%; margin-top: 10px;font-family: Oxygen, sans-serif;font-size: 10px; color: #333333;">
			<select id="queryInput" onchange="doSearch(); sendDataToTable();" style="background-color:white;">
				<option value="none">Local Businesses</option>
				<option value="accounting">Accounting</option>
				<option value="airport">Airports</option>
				<option value="amusement_park">Amusement Parks</option>
				<option value="aquarium">Aquariums</option>
				<option value="art_gallery">Art Galleries</option>
				<option value="atm">ATMs</option>
				<option value="bakery">Bakeries</option>
				<option value="bank">Banks</option>
				<option value="bar">Bars</option>
				<option value="beauty_salon">Beauty Salons</option>
				<option value="bicycle_store">Bicycle Stores</option>
				<option value="bowling_alley">Bowling Alleys</option>
				<option value="bus_station">Bus Stations</option>
				<option value="cafe">Cafes</option>
				<option value="campground">Campgrounds</option>
				<option value="car_dealer">Car Dealers</option>
				<option value="car_rental">Car Rentals</option>
				<option value="car_repair">Car Repairs</option>
				<option value="car_wash">Car Washes</option>
				<option value="casino">Casinos</option>
				<option value="cemetery">Cemetaries</option>
				<option value="church">Churches</option>
				<option value="city_hall">City Halls</option>
				<option value=clothing_store>Clothing Stores</option>
				<option value="convenience_store">Covenience Stores</option>
				<option value="courthouse">Courthouses</option>
				<option value="dentist">Dentists</option>
				<option value="department_store">Department Stores</option>
				<option value="doctor">Doctors</option>
				<option value="electrician">Electrician</option>
				<option value="electronics_store">Electronic Stores</option>
				<option value="embassy">Embassy</option>
				<option value="establishment">Establishments</option>
				<option value="finance">Finances</option>
				<option value="fire_station">Fire Stations</option>
				<option value="florist">Florists</option>
				<option value="food">Food</option>
				<option value="funeral_home">Funeral Homes</option>
				<option value="furniture_store">Furniture Stores</option>
				<option value="gas_station">Gas Stations</option>
				<option value="general_contractor">General Contractors</option>
				<option value="grocery_or_supermarket">Groceries or Supermarkets</option>
				<option value="gym">Gyms</option>
				<option value="hair_care">Hair Care</option>
				<option value="hardware_store">Hardware Stores</option>
				<option value="health">Health</option>
				<option value="hindu_temple">Hindu Temples</option>
				<option value="home_goods_store">Home Goods Stores</option>
				<option value="hospital">Hospitals</option>
				<option value="insurance_agency">Insurance Agencies</option>
				<option value="jewelry_store">Jewelry Stores</option>
				<option value="laundry">Laundries</option>
				<option value="lawyer">Lawyers</option>
				<option value="library">Libraries</option>
				<option value="liquor_store">Liquor Stores</option>
				<option value="local_government_office">Local Government Offices</option>
				<option value="locksmith">Locksmiths</option>
				<option value="lodging">Lodging</option>
				<option value="meal_delivery">Meal Deliveries</option>
				<option value="meal_takeaway">Meal Takeaways</option>
				<option value="mosque">Mosques</option>
				<option value="movie_rental">Movie Rentals</option>
				<option value="movie_theater">Movie Theaters</option>
				<option value="moving_company">Moving Companies</option>
				<option value="museum">Museums</option>
				<option value="night_club">Night Clubs</option>
				<option value="painter">Painter</option>
				<option value="park">Park</option>
				<option value="parking">Parking</option>
				<option value="pet_store">Pet Stores</option>
				<option value="pharmacy">Pharmacies</option>
				<option value="physiotherapist">Physiotherapists</option>
				<option value="place_of_worship">Places of Worship</option>
				<option value="plumber">Plumbers</option>
				<option value="police">Police</option>
				<option value="post_office">Post Offices</option>
				<option value="real_estate_agency">Real Estate Agencies</option>
				<option value="restaurant">Restaurants</option>
				<option value="roofing_contractor">Roofing Contractors</option>
				<option value="rv_park">RV Parks</option>
				<option value="school">Schools</option>
				<option value="shoe_store">Shoe Stores</option>
				<option value="shopping_mall">Shopping Malls</option>
				<option value="spa">Spas</option>
				<option value="stadium">Stadiums</option>
				<option value="storage">Storage</option>
				<option value="store">Stores</option>
				<option value="subway_station">Subway Stations</option>
				<option value="synagogue">Synagogues</option>
				<option value="taxi_stand">Taxi Stands</option>
				<option value="train_station">Train Stations</option>
				<option value="travel_agency">Travel Agencies</option>
				<option value="university">Universities</option>
				<option value="veterinary_care">Veterinary Care</option>
				<option value="zoo">Zoos</option>
			</select>

			<div style="position: relative; float:left; margin-top:3px;">
				<div id="searchwell" style="width: 100%; height:relative; border:0px solid black"></div>
			</div>

		</div>

	</body>

</html>
