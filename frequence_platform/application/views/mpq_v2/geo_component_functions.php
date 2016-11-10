<?php

	function write_geo_component_javascript($has_session_data, $mpq_id)
	{
		?>
			<script type="text/javascript" src="/libraries/external/json/json2.js"></script>

			<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>
			<script type="text/javascript" src="//www.google.com/jsapi"></script>

			<script src="/libraries/external/select2/select2.js"></script>


			<script type="text/javascript">
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
					data: {
						location_id: 0,
						mpq_id: <?php echo ($mpq_id ? $mpq_id : 'null') ?>
					},
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

			function update_zip_set(action, zip)
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

				if(zips.length <= 0)
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
				if (zips.length >= 2000 && zips.length <= 8000)
				{
					confirm = window.confirm('Warning: you have selected '+ zips.length +' zip codes. This may take a long time or even freeze your browser. Are you sure you want to continue?');
				}
				else if (zips.length > 8000)
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

								var zips_string = data.successful_zips.zcta.join(', ');
								$('textarea#set_zips').val(zips_string);

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

			function HandleGeoSearch(ev)
			{
				var key = ev.keyCode || ev.which;
				var enterKeyCode = 13;
				if(key==enterKeyCode)
				{
					flexigrid();
				}
			}

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
								if ($('#radius').val() >= 100 && $('#radius').val() <= 375)
								{
									confirm = window.confirm('Warning: you have selected a large radius. This may take a long time or even freeze your browser. Are you sure you want to continue?');
								}
								else if ($('#radius').val() > 375)
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

			$(document).ready(function() {
				show_map();
				// setup_initial_css();
			});

			</script>
		<?php
	}

	function write_geo_component_css()
	{
		?>
		<style>
			#map_search_input_form {
			}

			.map_search_sentence {
				font-family:BebasNeue;
				display:inline;
			}
			.map_search_input {
				display:inline-block;
			}
		</style>

		<style>
			.geo_stats_stat_type {
				font-size:16px;
			}

			.geo_stats_stat_value {
				font-size:18px;
			}

			.map_overlay_box {
				background-color:#00ff00;
			}

		</style>

		<style type="text/css">
			#map_loading_image {
				position:absolute;
				top:50%;
				left:50%;
				margin-top:-140px;
				margin-left:-60px;
				/*display:none;*/
			}
		</style>

		<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>

		<?php
	}

	function write_geo_component_map_html()
	{
		?>
			<div style="position:relative;">
				<div id="map_loading_image">
					<img src="/images/mpq_v2_loader.gif" />
				</div>
				<div id="region-links" style="width:100%;height:750px;">
				</div>
			</div>
		<?php
	}

	function write_geo_component_geo_stats_summary_html()
	{
		?>
			<div id="geo_stats_summary" class="map_overlay_box">
				<div id="geo_stats_population_summary" style="display:inline-block;">
					<span class="geo_stats_stat_type">Population: </span>
					<span class="geo_stats_stat_value" id="geo_stats_population_value">326,000</span>
				</div>
				<div id="geo_stats_income_summary" style="display:inline-block;">
					<span class="geo_stats_stat_type">Income: </span>
					<span class="geo_stats_stat_value" id="geo_stats_income_value">$1,212,000</span>
				</div>
			</div>
		<?php
	}

	function write_geo_component_geo_region_summary_html()
	{
		?>
		<div id="geo_region_summary" class="map_overlay_box" style="">
			Targeting 12 zip codes in Santa Clara, San Mateo counties
		</div>
		<?php
	}

	function write_geo_component_zips_html()
	{
	}

	function write_geo_component_search_html($geo_radius, $geo_center, $geographics_section_data,$custom_geos_enabled)
	{
		$zips_string = '';
		$zips = $geographics_section_data['zips'];
		$last_zip_index = count($zips) - 1;
		foreach($zips as $index=>$zip)
		{
			if($index != $last_zip_index)
			{
				$zips_string .= $zip.', ';
			}
			else
			{
				$zips_string .= $zip;
			}
		}
		?>
		<div style="display:none;" id="geo_error_div">
			<span class="span12 alert alert-error" id="geo_error_text"></span>
		</div>
		
		<div class="tabbable " >
			<ul class="nav nav-pills " id="geo_tabs">
			<?php if($custom_geos_enabled){ ?>
				<li id="custom_regions_pill" class=" active intro-chardin">
					<a href="#custom_region_list" data-toggle="tab" >Regions</a>
				</li>
			<?php } ?>
				<li id="radius_search_pill" class="<?php if(!$custom_geos_enabled){ echo 'active';  }?> intro-chardin" >
					<a href="#radius_search" data-toggle="tab" >Radius Search</a>
				</li>
				<li id="known_zips_pill" class="intro-chardin">
					<a href="#zip_list" data-toggle="tab" id="known_zips_tab_anchor">Known Zips</a>
				</li>

				<a href="#geo" onclick="chardin_geo_tabs();"><i class="icon-question-sign icon-large"></i></a>
			</ul>
		    
			<div class="tab-content">
			  <div class="tab-pane <?php if($custom_geos_enabled){ echo 'active';  }?>" id="custom_region_list">
				<div style="height:50px;" class="row-fluid">
				  <form class="form-inline">
					<div style="padding-right:4px;" class="span10"> <input class="span12" type="hidden" id="custom_regions_multiselect"></div>
					<a id="custom_regions_multiselect_load_button" class="btn btn-success"><i class="icon-map-marker icon-white"></i> Load Regions</a>
				  </form>
				</div>
			  </div>
			    
				<div class="tab-pane <?php if(!$custom_geos_enabled){ echo 'active';  }?>" id="radius_search">
					<div class="row-fluid">
						<div class="span12">

							<form class="form-inline">
								<input type="hidden" id="region_type" value="ZIP">
								Zips
								<!--select class="span1" id="region_type">
									<option value="ZIP" selected="selected">
										Zips
									</option>
									<option value="PLACE">
										Cities
									</option>
								</select-->
								&nbsp;within&nbsp;
								<div class="input-append">
									<div id="radius_span" class="intro-chardin"><input type="text" class="input-mini " placeholder="" id="radius" value="<?php echo $geo_radius; ?>" onclick="this.select();" onkeypress="HandleGeoSearch(event);" />
									<span class="add-on"> miles</span></div>
								</div>
								&nbsp;of&nbsp;
								<input type="text" class="span7 intro-chardin" placeholder="" value="<?php echo $geo_center; ?>" id="address" onClick="this.select();" onkeypress="HandleGeoSearch(event);" />
								<a class="btn btn-success" id="searchbut" onclick="flexigrid();"><i class="icon-map-marker icon-white"></i> Search</a> <a href="#geo_search" onclick="chardin_geo_search();"><i class="icon-question-sign icon-large"></i></a>
							</form>
						</div>
					</div>
				</div>
				<div class="tab-pane" id="zip_list">
					<form class="form-inline">
						<textarea id="set_zips" class="span10" rows="1" placeholder="type zips here (For Example: 94303, 94100, 93456...)" onClick="this.select();"><?php echo $zips_string; ?></textarea>
						<a class="btn btn-success" onclick="handle_set_zips(event);"><i class="icon-map-marker icon-white"></i> Load Zips</a>
					</form>
				</div>
			</div>
		    
		</div>

		<?php
	}
	
?>
