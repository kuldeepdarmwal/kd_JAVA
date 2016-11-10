<script type="text/javascript" src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.js"></script>
<script src="/assets/js/materialize_freq.min.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
<script type="text/javascript">
var pv_table_data = <?php echo json_encode($partners); ?>;
var domain_without_cname = <?php echo json_encode($domain); ?>;
var user_role = '<?php echo $user_role; ?>';
var partner_status = '<?php echo $partner_status; ?>';
</script>
<script src="/assets/js/partners/partners_view.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>