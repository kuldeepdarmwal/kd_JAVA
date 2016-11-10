<div class="container">
<h3>Bulk Adset Refresher <small>choose file -> upload -> done</small></h3>

<form action="/bulk_adset_refresh" method="post" enctype="multipart/form-data">
	<div style="position:relative;">
		<a class='btn btn-mini' href='javascript:;'>
			Choose File...
			<input type="file" style='position:absolute;z-index:2;top:0;left:0;filter: alpha(opacity=0);-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";opacity:0;background-color:transparent;color:transparent;' name="userfile" size="40"  onchange='var pieces=$(this).val().split("\\");$("#upload-file-info").html(pieces[pieces.length-1]);$("#err_div").html("");'>
		</a>
		&nbsp;
		<span class='label label-success' id="upload-file-info"></span>
	</div>
	<hr>
<div class="row-fluid">

<button type="submit" class="btn btn-info" name="upload">Upload Adset Refresher</button>
<div>
</form>
<div id="err_div">
<?php echo validation_errors('<span class="label label-important">', '</span>'); ?>
</div>
</div>


<hr><hr>


<div class="container">
<h3>Bulk Time series Refresher <small>choose file -> upload -> done</small></h3>

<form action="/bulk_adset_refresh/bulk_load_timeseries" method="post" enctype="multipart/form-data">
	<div style="position:relative;">
		<a class='btn btn-mini' href='javascript:;'>
			Choose File...
			<input type="file" style='position:absolute;z-index:2;top:0;left:0;filter: alpha(opacity=0);-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";opacity:0;background-color:transparent;color:transparent;' name="userfile" size="40"  onchange='var pieces=$(this).val().split("\\");$("#upload-file-info_ts").html(pieces[pieces.length-1]);$("#err_div_ts").html("");'>
		</a>
		&nbsp;
		<span class='label label-success' id="upload-file-info_ts"></span>
	</div>
	<hr>
<div class="row-fluid">

<button type="submit" class="btn btn-info" name="upload">Upload Time series</button>
<div>
</form>
<div id="err_div_ts">
<?php echo validation_errors('<span class="label label-important">', '</span>'); ?>
</div>
</div>


<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
</body>
</html>