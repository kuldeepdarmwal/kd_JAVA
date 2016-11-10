<script type="text/javascript" src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script src="/assets/js/moment/moment.js"></script>
<link rel="stylesheet" href="https://npmcdn.com/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/bootstrap/dist/css/bootstrap-theme.min.css">
<script type="text/javascript" src="/assets/materialize/js/materialize.min.js"></script>
<script src="/libraries/external/DataTables-1.10.2/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Intl.~locale.en"></script>
<?php
if (ENVIRONMENT == "local" || ENVIRONMENT == "localhost-development") :
?>
        <script src="/node_modules/zone.js/dist/zone.js"></script>
        <script src="/node_modules/es6-shim/es6-shim.min.js"></script>
        <script src="/node_modules/reflect-metadata/Reflect.js"></script>
        <script src="../node_modules/systemjs/dist/system.js"></script>
        <script src="/angular/src/systemjs.conf.js"></script>
        <script>
          System.import('/angular/src/tmp/app/campaign/campaign.js')
            .catch(function(err) { console.error(err); });
        </script>

<?php else : ?>

        <script type="text/javascript" src="/angular/build/assets/lib.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
        <script type="text/javascript" src="/angular/build/assets/campaign.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<?php endif; ?>
<link rel="stylesheet" href="http://materializecss.com/css/ghpages-materialize.css">
<link rel="stylesheet" href="/assets/css/campaigns/campaigns_style.css" />
<style type="text/css">
    .navbar .collapse{
        display:block !important;
    }
    
    .navbar .navbar-text{
        margin:0px !important;
    }    
</style>
<script>
$( document ).ready(function() {
    $('#campaigns').on("click",'.campaigns-list #campaigns-list .pagination li a, .campaigns-list #campaigns-list tr th a', function(){
	var chk = $("#campaigns .campaigns-list .campaigns-list-mode input").is(":checked");
	if(chk){
	    $('#campaigns .campaigns-list table .all_time').css("display", 'none');
	    $('#campaigns .campaigns-list table .this_flight').css("display", 'table-cell');
	} else {
	    $('#campaigns .campaigns-list table .all_time').css("display", 'table-cell');
	    $('#campaigns .campaigns-list table .this_flight').css("display", 'none');
	}
    });
    
    $('#campaigns').on('change', '.campaigns-list .sub-header .search-box input', function(){
	$("#campaigns .campaigns-list .pagination:first-child li:first-child").click();
    },this);
});
</script>