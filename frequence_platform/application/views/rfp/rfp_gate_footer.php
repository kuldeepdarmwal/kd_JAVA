<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
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
<script type="text/javascript" src="/assets/js/mustache/mustache.min.js"></script>

<script type="text/javascript">
	var rfp_is_preload = false;
	var rfp_industry_data = <?php echo $industry_data; ?>;
	var rfp_account_executive_data = <?php echo json_encode($account_executive_data); ?>;
	var rfp_custom_regions_data = [];
</script>

<script src="/assets/js/mpq/recalculate_totals.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mpq/rfp_common.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script src="/assets/js/mpq/rfp-gate.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

</body>
</html>
