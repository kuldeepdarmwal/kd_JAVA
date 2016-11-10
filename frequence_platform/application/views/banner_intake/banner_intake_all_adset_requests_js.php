<script src="/libraries/external/select2/select2.js"></script>

<script src="/assets/js/banner_intake/all_adset_requests.js"></script>
<script type="text/javascript" src="/libraries/external/bootstrap-daterangepicker-1.3.16/moment.js"></script>
<script src="/libraries/external/momentjs/moment-timezone-with-data.min.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.rowGrouping.js"></script>

<script type="text/javascript">
var user_role = "<?php echo $user_role; ?>";
var invisible_columns = "<?php echo (isset($invisible_columns)) ? implode($invisible_columns,',') : ''; ?>";
var open_modal_id = <?php echo ($modal_open_id != null ? $modal_open_id: "null"); ?>;

if (typeof invisible_columns !== 'undefined' && invisible_columns !== '')
{
	invisible_columns = invisible_columns.split(",");
}
</script>
