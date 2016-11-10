<div class="container">
	<div id="title_div">
		<div class="alert alert-info">
			<h3>Loading Line Items <small id="line_item_counter_span"></small>
			</h3>
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
	var g_line_items = eval(<?php echo json_encode($data_array)?>);
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
				$('#line_item_counter_span').html(g_line_items.array_data.length+' line items loaded successfully');
			}
			else
			{
				$('#title_div').html('<div class="alert "><h3>Upload Partially Complete <small id="line_item_counter_span"></small></h3></div>');
				$('#line_item_counter_span').html(row_num+' line items attempted');
			}
			
					
		}
		else
		{
			///do ajax call to load line item here
			$.ajax({
				type: "POST",
				url: '/bulk_adset_refresh/load_line_item/',
				async: true,
				data: { line_item: g_line_items.array_data[row_num]},
				dataType: 'json',
				error: function(xhr, textStatus, error){
					console.log(xhr, error);
					var this_row_string = '<tr><td>File: Line '+(row_num+1) + '</td><td colspan="5"><span style="width:100%;" align="center" class="label label-important">FAILED - Check error console for more details</span></td>';
					this_row_string += '</tr>';
					g_all_insertions_ok = false;
					$('#results_table').append(this_row_string);
					consecutive_row_failures += 1;
					load_line_item(row_num+1);
				},
				success: function(data, textStatus, xhr){ 
					$('#the_loader_bar').css('width',100*(row_num+1)/g_line_items.array_data.length+'%');
					$('#line_item_counter_span').html((row_num+1)+' of '+g_line_items.array_data.length+' line items');
					if(data.is_success)
					{
						//do success stuff
						var this_row_string = '<tr><td>'+data.cmpn_name;
						
						if(data.is_removal)
						{
						    this_row_string += ' - REMOVAL';
						}
						
						this_row_string += '</td>';
						
						//vl landing page updated
						if(data.vl_landing_page_updated)
						{
						    this_row_string += '<td><span class="label label-success">VL landing page</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label">VL landing page</span></td>';
						}
						
						//vl_adsets_cloned
						if(data.vl_adsets_cloned)
						{
							this_row_string += '<td><span class="label label-success">VL adsets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label">VL adsets</span></td>';
							//g_all_insertions_ok = false;
						}
						//dfa_adsets_publish
						if(data.dfa_adsets_published == 1)
						{
							this_row_string += '<td><span class="label label-success">DFA adsets</span></td>';

						}
						else if(data.dfa_adsets_published == 0)
						{
							this_row_string += '<td><span class="label ">DFA adsets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label label-important">DFA adsets</span></td>';
							consecutive_row_failures += 1;
							g_all_insertions_ok = false;
						}
						//ttd_adsets_loaded
						if(data.ttd_adsets_loaded == 1)
						{
							this_row_string += '<td><span class="label label-success">TTD adsets</span></td>';

						}
						else if(data.ttd_adsets_loaded == 0)
						{
							this_row_string += '<td><span class="label ">TTD adsets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label label-important">TTD adsets</span></td>';
							consecutive_row_failures += 1;
							g_all_insertions_ok = false;
						}
						//frequence campaigns variables updated
						if(data.f_campaign_budgets_updated == 1)
						{
							this_row_string += '<td><span class="label label-success">Freq. Campaign Budgets</span></td>';
						}
						else if(data.f_campaign_budgets_updated == -1)
						{
							this_row_string += '<td><span class="label label-important">Freq.Campaign Budgets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label">Freq.Campaign Budgets</span></td>';
						}
						//ttd campaigns budgets updated
						if(data.ttd_campaign_budgets_updated == 1)
						{
							this_row_string += '<td><span class="label label-success">TTD Campaign Budgets</span></td>';
						}
						else if(data.ttd_campaign_budgets_updated == -1)
						{
							this_row_string += '<td><span class="label label-important">TTD Campaign Budgets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label">TTD Campaign Budgets</span></td>';
						}						
						//ttd budgets updated
						if(data.ttd_adgroup_budgets_updated)
						{
							this_row_string += '<td><span class="label label-success">TTD Adgroup Budgets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label ">TTD Adgroup Budgets</span></td>';
						}
						
						if(data.dfa_adsets_published != -1 && data.ttd_adsets_loaded != -1)
						{
						    consecutive_row_failures = 0;
						}
						
						this_row_string += '</tr>';
						$('#results_table').append(this_row_string);
						
						console.log(data);
						//on to the next row
						load_line_item(row_num+1);
					}
					else
					{
						alert('unexpected error: 03890y80');
					}

					
				}
			});
			
		}
	}
	$(document).ready( function () {
		load_line_item(0);
	});
</script>
</body>
</html>