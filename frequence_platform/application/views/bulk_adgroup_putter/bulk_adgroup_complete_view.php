<div class="container">
	<div id="title_div">
		<div class="alert alert-info">
			<h3>Loading Line Items <small id="line_item_counter_span"></small></h3>
		</div>
	</div>
	<div class="row-fluid">
		<div id="loader_row" class="span12">
			<div id="progress_bar" class="progress progress-striped active loader_bar">
				<div id="the_loader_bar" class="bar loader_bar" style="width: 5%;"></div>
			</div>
		</div>
		<table class="table table-hover table-condensed " id="results_table">
		</table>
	</div>
</div>
<?php 

?>
<script>
	var g_line_items = $.parseJSON('<?php echo addslashes(json_encode($data_array)); ?>');
	console.log(g_line_items);
	var g_all_insertions_ok = true;
	var consecutive_row_failures = 0;
	var max_errors = 3;
	function load_line_item(row_num)
	{
		if(row_num == g_line_items.array_data.length || consecutive_row_failures == max_errors)
		{
			$('#loader_row').html('');
			if(g_all_insertions_ok)
			{
				$('#title_div').html('<div class="alert alert-success"><h3>Upload Complete <small id="line_item_counter_span"></small></h3></div>');
				$('#line_item_counter_span').html(g_line_items.array_data.length + ' line items loaded successfully');
			}
			else
			{
				$('#title_div').html('<div class="alert "><h3>Upload Partially Complete <small id="line_item_counter_span"></small></h3></div>');
				$('#line_item_counter_span').html(row_num + ' line items attempted');
			}	
		}
		else
		{
			///do ajax call to load line item here
			var this_row_string = '<tr><td>File: Line ' + (row_num + 1) + '</td><td colspan="5"><span id="span_' + (row_num + 1) + '" style="width:100%;" align="center" class="label label-info">UPDATING</span></td>';
			$('#results_table').append(this_row_string);

			$.ajax({
				type: "POST",
				url: '/bulk_adgroup_putter/load_line_item',
				async: true,
				data: {line_item: g_line_items.array_data[row_num]},
				dataType: 'json',
				error: function(xhr, textStatus, error){
					console.log(xhr, error);
					$('#span_' + (row_num + 1)).toggleClass('label-info');
					$('#span_' + (row_num + 1)).toggleClass('label-important');
					$('#span_' + (row_num + 1)).html('Unexpected error: Check console log for more information.');
					g_all_insertions_ok = false;

					consecutive_row_failures += 1;
					load_line_item(row_num + 1);
				},
				success: function(data, textStatus, xhr){ 
					$('#the_loader_bar').css('width', 100 * (row_num + 1) / g_line_items.array_data.length + '%');
					$('#line_item_counter_span').html((row_num + 1) + ' of ' + g_line_items.array_data.length + ' line items');
					if(data.is_success)
					{
						//do success stuff
						$('#span_' + (row_num + 1)).toggleClass('label-info');
						$('#span_' + (row_num + 1)).toggleClass('label-success');
						$('#span_' + (row_num + 1)).html('AdGroup \'' + data.adgroup_id + '\' updated successfully');
					}
					else
					{
						$('#span_' + (row_num + 1)).toggleClass('label-info');
						$('#span_' + (row_num + 1)).toggleClass('label-important');
						$('#span_' + (row_num + 1)).html('Error: 03890y80: AdGroup failed to update. ' + data.errors);
						consecutive_row_failures += 1;
						g_all_insertions_ok = false;
					}
					console.log(data);
					
					//on to the next row
					load_line_item(row_num + 1);
				}
			});
		}
	}
	$(document).ready(function() {
		load_line_item(0);
	});
</script>
</body>
</html>