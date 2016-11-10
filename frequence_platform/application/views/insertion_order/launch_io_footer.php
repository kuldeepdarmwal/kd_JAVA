<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript">
	var num_campaigns = <?php echo count($campaigns); ?>;
	var selected_campaign_id = false;
	var trash_campaign_row = null;
	var has_notes = <?php echo $has_notes; ?>;
</script>
<script src="/assets/js/mpq/launch_io_view.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>