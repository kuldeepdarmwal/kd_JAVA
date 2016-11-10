<script type="text/javascript" src="//code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>
<script src="/assets/js/materialize_freq.min.js?v=1"></script>
<script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>

<script type="text/javascript">
	var google_access_token_rfp_io = "<?php echo $google_access_token_rfp_io; ?>";
</script>

<!-- geo_component_functions.php -->
<script type="text/javascript" src="/libraries/external/json/json2.js"></script>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $google_access_token_rfp_io; ?>&libraries=places"></script>
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/3.4.1/select2.js"></script>
<script type="text/javascript" src="/js/ajax_queue.js"></script>
<script type="text/javascript" src="/js/timeseries/time_series.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mpq/jquery.placecomplete.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<script type="text/javascript">
	var rfp_is_preload = <?php echo ($is_preload == true ? "1" : "0"); ?>;
	var rfp_raw_preload_location_object = <?php echo json_encode($existing_locations); ?> ;
	var rfp_industry_data = <?php echo $industry_data; ?>;
	var rfp_iab_category_data = <?php echo json_encode($iab_category_data); ?>;
	var rfp_account_executive_data = <?php echo json_encode($account_executive_data); ?>;
	var rfp_custom_regions_data = <?php echo json_encode($custom_regions_data); ?>;
	var io_product_info = <?php echo json_encode($io_product_data); ?>;
	var io_flights_data = <?php echo json_encode($flights_data); ?>;
	var io_creatives_data = <?php echo json_encode($creatives_data); ?>;
	var is_rfp = false;
	var has_geofencing = <?php echo (isset($has_geofencing) && $has_geofencing) ? 'true' : 'false'; ?>;
	var geofencing_data = <?php echo (isset($geofencing_data) && $geofencing_data) ? json_encode($geofencing_data) : 'false'; ?>;
	var mpq_id = <?php echo $mpq_id; ?>;
	var time_series_data = <?php echo json_encode($time_series_data); ?>;
	var max_locations_for_rfp = <?php echo $max_locations_for_rfp; ?>;
	var io_adv_id = "<?php echo ( isset($io_advertiser_id) ? $io_advertiser_id : "" ); ?>";
	var io_adv_name = "<?php echo (isset($io_advertiser_name) ? $io_advertiser_name : "" ); ?>";
	var io_adv_source_table = "<?php echo (isset($source_table) ? $source_table : "" ); ?>";
	var tracking_tag_file_id = "<?php echo (isset($tracking_tag_file_id) ? $tracking_tag_file_id : "" ); ?>";
	var tracking_tag_file_name = "<?php echo (isset($tracking_tag_file_name) ? $tracking_tag_file_name : "" ); ?>";
	var io_submit_allowed = "<?php echo $io_submit_allowed; ?>";
	var user_role = "<?php echo $user_role; ?>";
</script>

<script src="/assets/js/mpq/rfp_common.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mpq/io.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

</body>
</html>
