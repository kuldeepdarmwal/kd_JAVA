<script src="/libraries/external/select2/select2.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.rowGrouping.js"></script>

<script type="text/javascript" src="/js/datatable_csv_export.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script type="text/javascript">
var pv_table_data = <?php echo json_encode($rfps); ?>;
var user_role = '<?php echo $user_role; ?>';
var is_io_permitted = <?php echo json_encode($io_permitted); ?>;
</script>
<script src="/assets/js/mpq/proposals_view.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
