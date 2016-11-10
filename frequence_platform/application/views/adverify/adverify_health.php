<div class="container">
<h3>AdVerify Health CSV <small>pick some dates, get healthy.</small></h3>

<div style="position:relative;">
	<form action="" method="POST">
		<table>
			<tr>
				<td style="width:150px;">
				<label class="control-label" for="end_date_box">Start Date</label>
					<div class="controls" >
						<input type="date" id="start_date_box">
					</div>
				</td>
				<td>
					<label class="control-label" for="end_date_box">End Date</label>
					<div class="controls">
						<input type="date" id="end_date_box">
					</div>
				</td>
			</tr>
		</table>
	</form>
		<span class='label label-success' id="upload-file-info"></span>
		
	</div>
	<hr>
<div class="row-fluid">
<span id="err_span"></span>
<br><br>
<button id="add_queue_button" class="btn btn-info" onclick="download_health_csv();"><i class="icon-download icon-white"></i> Download CSV</button>
<div>


</div>
<script src="/bootstrap/assets/js/bootstrap-dropdown.js"></script>
<script>

function download_health_csv()
{
		clear_errors();
		var start_date = $('#start_date_box').val();
		var end_date = $('#end_date_box').val();
		if(start_date == "" || end_date == "")
		{
			set_error_status("Error: Please specify a date range");
			return;
		}
		
		window.open("/adverify/adverify_health_download/"+start_date+"/"+end_date,"_blank");
}

function clear_errors()
{
	$('#err_span').attr("class", "");
	$('#err_span').html("");
}

function set_error_status(msg, bs_color)
{
	$('#err_span').attr("class", "label label-"+bs_color);
    $('#err_span').html(msg);
}


</script>
</body>
</html>