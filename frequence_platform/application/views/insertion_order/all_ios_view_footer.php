<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.rowGrouping.js"></script>
<script type="text/javascript">
	var all_ios_table_data = <?php echo json_encode($all_ios); ?>;
	var submission_message = '<?php echo $post_submission_message; ?>';
	var user_edit = <?php echo $can_launch; ?>;
	var user_role = '<?php echo $user_role; ?>';
	var io_submit_allowed = '<?php echo $io_submit_allowed; ?>';
	var show_forecast_column = '<?php echo $show_forecast_column; ?>';
</script>
<script src="/bootstrap/assets/js/bootstrap-popover.js"></script>
<script src="/assets/js/mpq/all_ios_view.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script type="text/javascript">
	if (<?php echo $io_locked; ?>) all_ios_set_message_timeout_and_show("You've been redirected here because the insertion order you are trying to edit is currently being edited by another user.", 'alert alert-warning', 20000);
</script>
