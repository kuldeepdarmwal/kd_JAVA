<!DOCTYPE html>
<html>
	<head>
		<style>
			@font-face { font-family:'Bebas Neue'; src: url('https://s3.amazonaws.com/brandcdn-assets/fonts/BebasNeue.otf') format("opentype"); }
		</style>

		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<title>Proposal Overview Snapshot</title>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<link rel="stylesheet" href="/css/whitelabel/report_v2/default/base.css?v=<?php echo CACHE_BUSTER_VERSION; ?>"></link>
		<?php
			if (!empty($partner_ui_css_path))
			{
				echo '<link rel="stylesheet" href="' . $s3_assets_path . $partner_ui_css_path . '?v=' . CACHE_BUSTER_VERSION . '"></link>';
			}
		?>
		<link href='https://api.mapbox.com/mapbox.js/v2.4.0/mapbox.css' rel='stylesheet' />
		<script type="text/javascript">
			L_PREFER_CANVAS = true; // Necessary for taking snapshots with Leaflet-Image
		</script>
		<?php echo $shared_js; ?>

		<script type="text/javascript">

			var minimum_zoom_level_for_the_map = 2;
			var map;

			var regions = <?php echo $regions_to_show; ?>;
			var map_tags = <?php echo !empty($map_tags) ? $map_tags : 'null'; ?>;
			var db_map_options = <?php echo !empty($snapshot_preferences) ? $snapshot_preferences : '{}'; ?>;
			var geofencing_points = <?php echo !empty($geofencing_points) ? $geofencing_points : 'null'; ?>;
			var is_overview_snapshot = <?php echo $is_overview_snapshot ? 'true' : 'false'; ?>;

			$(document).ready(function(){

				var active = $("<div class='report_map_active_polygon'></div>").hide().appendTo("body");
				var active_color = active.css("color");
				active.remove();

				var default_map_options = {
					map_style: 'mapbox.light',
					use_inverted_map: false,
					polygon_style: {
						fill: true,
						weight: 1,
						color: '#fff',
						fillColor: active_color,
						fillOpacity: 0.8
					}
				};
				try {
				db_map_options.polygon_style = JSON.parse(db_map_options.polygon_style);
				} catch (e) {
					db_map_options.polygon_style = {};
				}
				jQuery.extend(true, default_map_options, db_map_options);
				default_map_options.use_inverted_map = (default_map_options.use_inverted_map === true || default_map_options.use_inverted_map == '1');

				var custom_map_options = {
					map_options: {
						minZoom: minimum_zoom_level_for_the_map
					},
					polygon_options: default_map_options.polygon_style
				};

				map = new FrequenceMap("map_canvas", default_map_options.map_style, custom_map_options);
				map.useInvertedMap(default_map_options.use_inverted_map);
				map.is_geofencing = Boolean(geofencing_points);
				map.changeMarkerRadius(8);

				var num_regions = regions.zcta.length;
				map.loadRegions(regions.zcta, 'zcta', function(){
					if(map_tags)
					{
						for(var i = 0; i < map_tags.length; i++)
						{
							map.addMapTags(map_tags[i].zcta, map_tags[i].user_supplied_name);
						}
						map.displayMapTags();
					}

					if(geofencing_points)
					{
						for(var j = 0; j < geofencing_points.length; j++)
						{
							map.addGeofencingMarker(geofencing_points[j]);
						}
					}

					<?php if($mpq_id) { ?>
						window.setTimeout(function(){
							$("#snapshotBut").click();
							$("#snapshotBut").off("click");
						}, 3000);
					<?php } ?>
				});

				$("#snapshotBut").on("click", function(){
					$("#snapshotBut").prop("disabled", true);
					var title = $("input#snapshotTitle").val();

					map.takeSnapshot(function(err, canvas){
						if(!err)
						{
							var img_url = canvas.toDataURL();
							$.ajax({
								async: true,
								type: "POST",
								dataType: "json",
								data: {
									title: title,
									img: img_url
								},
								url: <?php echo ($is_mpq) ? "'/mpq_v2/save_mpq_image/{$mpq_id}/'" : "'/proposal_builder/save_lap_image/{$lap_id}/{$prop_id}'"; ?>,
								success: function(response_data, textStatus, jqXHR) {
									if(vl_is_ajax_call_success(response_data))
									{
										$('form#snapshot').html('Title:<br><input type="text" style="border:1px solid lightgrey" name="title" value="' + title + '" /><br /><input type="submit" name="submit" style="height:30px;width:120px;padding: 6px 3px;border:1px solid lightgrey; value="Submit" /><br />');
										$('form#snapshot').append("<img src='" + img_url + "'>");
										<?php if(!$is_auto_snapshot) { ?>
											alert(response_data.message);
										<?php } ?>
									}
									else
									{
										handle_ajax_controlled_error(response_data, "Failed to save screenshot ");
									}
								},
								error: function(jqXHR, textStatus, errorThrown) {
									if(textStatus != 'abort')
									{
										vl_show_jquery_ajax_error(jqXHR, textStatus, errorThrown);
									}
								},
								complete: function(){
									$("#snapshotBut").prop("disabled", false);
								}
							});
						}
					});
				});
			});

		</script>
	</head>

	<body>
		<div style="float:right;text-align:right;">
			<?php echo isset($response) ? $response : ''; ?>
			<br>
			<button id="snapshotBut" style="z-index:100;padding: 6px 3px;border:1px solid lightgrey;">Take Snapshot</button><br />
			<label for="snapshotTitle">Title: </label><input type="text" style="border:1px solid lightgrey" value="<?php echo isset($user_supplied_name) ? $user_supplied_name : ''; ?>" id="snapshotTitle" style="z-index:100;"></input>
			<br />WARNING: Taking a snapshot will overwrite the existing snapshot.
			<div id="msg_text"></div>
		</div>

		<?php if($is_auto_snapshot && !$is_mpq && !is_numeric($location_id)) { ?>
			<div id="map_canvas" class="proposal_snapshot_leaflet_map_ratio_3_2" style="border: 1px solid #e0e0e0;"></div>
		<?php } else { ?>
			<div id="map_canvas" class="proposal_snapshot_leaflet_map_ratio_2_1" style="border: 1px solid #e0e0e0;"></div>
		<?php } ?>

		<div class="row">
			<form action="" id="snapshot" method="post">
				<?php
					if(!isset($images))
					{
						//echo "<br>Generate some images to edit first<br>";
					}
					else
					{
						foreach($images as $v)
						{
							echo '<div class="col s12">';
							echo 'Title: <input type="text" name="title" style="border:1px solid lightgrey;" value="'.$v["snapshot_title"].'" />';
							echo '&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" style="height:30px;width:120px;padding: 6px 3px;border:1px solid lightgrey;" value="Submit" />';
							echo '<br /><br /><img src="'.$v["snapshot_data"].'">';
							echo '</div>';
						}
					}
				?>
			</form>
		</div>
	</body>
</html>
