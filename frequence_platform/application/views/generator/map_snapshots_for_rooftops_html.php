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
		<link href='https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css' rel='stylesheet' />
		<script type="text/javascript">
			L_PREFER_CANVAS = true; // Necessary for taking snapshots with Leaflet-Image
		</script>
		<?php echo $shared_js; ?>

		<script type="text/javascript">

			var minimum_zoom_level_for_the_map = 2;
			var map;

			var rooftops = JSON.parse(<?php echo $rooftops_to_show; ?>);
			var db_map_options = <?php echo !empty($snapshot_preferences) ? $snapshot_preferences : '{}'; ?>;

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

				jQuery.extend(true, default_map_options, db_map_options);
				default_map_options.use_inverted_map = (default_map_options.use_inverted_map === true || default_map_options.use_inverted_map == '1');

				var custom_map_options = {
					map_options: {
						minZoom: minimum_zoom_level_for_the_map,
						maxZoom: 13
					},
					polygon_options: default_map_options.polygon_style
				}

				map = new FrequenceMap("map_canvas", default_map_options.map_style, custom_map_options);
				map.useInvertedMap(false);
				map.changeMarkerRadius(8);

				for(var i = 0; i < rooftops.length; i++)
				{
					map.addMapTags(rooftops[i], rooftops[i].description, true);
				}
				map.displayMapTags();
				map.map.fitBounds(map.map_multilocation_map_tags.getBounds().pad(0.5));

				window.setTimeout(function(){
					$("#snapshotBut").click();
				}, 3000);

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
								url: <?php echo ($is_mpq) ? "'/mpq_v2/save_mpq_image/{$mpq_id}/'" : "'/proposal_builder/save_lap_image/rooftops/{$prop_id}'"; ?>,
								success: function(response_data, textStatus, jqXHR) {
									if(vl_is_ajax_call_success(response_data))
									{
										$('form#snapshot').html('Title:<br><input type="text" style="border:1px solid lightgrey" name="title" value="' + title + '" /><br /><input type="submit" name="submit" style="height:30px;width:120px;padding: 6px 3px;border:1px solid lightgrey; value="Submit" /><br />');
										$('form#snapshot').append("<img src='" + img_url + "'>");
										alert(response_data.message);
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
									$('form#snapshot').html('Title:<br><input type="text" style="border:1px solid lightgrey" name="title" value="' + title + '" /><br /><input type="submit" name="submit" style="height:30px;width:120px;padding: 6px 3px;border:1px solid lightgrey; value="Submit" /><br />');
									$('form#snapshot').append("<img src='" + img_url + "'>");
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

		<div id="map_canvas" style="height:700px;width:700px;border: 1px solid #e0e0e0;"></div>

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
