<!-- footer -->
<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript">
	var advertisers_table_data = <?php echo json_encode($advertisers); ?>;
</script>
<script src="/assets/js/advertisers/all_advertisers_view.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
