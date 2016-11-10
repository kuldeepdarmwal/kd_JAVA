		<script>
			window.has_advertisers = <?php echo json_encode($has_advertisers); ?>;
			window.has_edit_permission = <?php echo json_encode($has_edit_permission); ?>;
			window.spec_ad_data = <?php echo $advertisers_json; ?>;
		</script>
		<script type="text/javascript" language="javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
		<script type="text/javascript" src="/js/datatable_csv_export.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/javascript" src="/assets/js/ad_machina/ad_machina_common.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
		<script type="text/javascript" src="/assets/js/ad_machina/ad_machina_index.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
