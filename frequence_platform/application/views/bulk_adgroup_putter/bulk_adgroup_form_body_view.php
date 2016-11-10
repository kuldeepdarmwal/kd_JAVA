<div class="container">
<h3>Bulk Adgroup PUTter <small>choose file -> upload -> done</small></h3>

<form action="/bulk_adgroup_putter" method="post" enctype="multipart/form-data">
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

<button type="submit" class="btn btn-info" name="upload">Upload</button>
<div>


</form>
<div id="err_div">
<?php echo validation_errors('<span class="label label-important">', '</span>'); ?>
</div>
</div>
<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
</body>
</html>