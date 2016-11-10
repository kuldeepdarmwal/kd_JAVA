<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="/bootstrap/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/materialize_freq.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
<script src="/assets/js/mpq/placecomplete.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<!-- geo_component_functions.php -->
<script type="text/javascript" src="/libraries/external/json/json2.js"></script>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places"></script>
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script src="/assets/js/range_slider/rangeslider.js"></script>
<script type="text/javascript" src="/js/ajax_queue.js"></script>


<script type="text/javascript">
	var rfp_is_preload = <?php echo ($is_preload == true ? "1" : "0"); ?>;
	var rfp_raw_preload_location_object = <?php echo json_encode($existing_locations); ?> ;
	var rfp_industry_data = <?php echo $industry_data; ?>;
	var rfp_iab_category_data = <?php echo json_encode($iab_category_data); ?>;
	var rfp_rooftops_data = <?php echo $rooftops_data; ?>;
	var rfp_tv_zones_data = <?php echo $rfp_tv_zones_data; ?>;
	var rfp_keywords_data = <?php echo $rfp_keywords_data; ?>;
 	var rfp_keywords_clicks = '<?php echo $rfp_keywords_clicks; ?>';
	var rfp_account_executive_data = <?php echo json_encode($account_executive_data); ?>;
	var rfp_custom_regions_data = <?php echo json_encode($custom_regions_data); ?>;
	var rfp_options_data = <?php echo json_encode($rfp_options_data); ?>;
	var is_rfp = true;
	var mpq_id = <?php echo $mpq_id; ?>;
	var max_locations_for_rfp = <?php echo $max_locations_for_rfp; ?>;
</script>

<script src="/assets/js/mpq/recalculate_totals.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mpq/rfp_common.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mpq/rfp.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

</body>
</html>
