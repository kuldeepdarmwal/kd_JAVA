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
				url: '/bulk_campaign_upload/load_line_item/',
				async: true,
				data: { line_item: g_line_items.array_data[row_num]},
				dataType: 'json',
				error: function(xhr, textStatus, error){
					console.log(xhr, error);
					var this_row_string = '<tr><td>File: Line '+(row_num+1) + '</td><td colspan="11"><span style="width:100%;" align="center" class="label label-important">FAILED - Check error console for more details</span></td>';
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
						var this_row_string = '<tr><td>'+data.cmpn_name+'</td>';
						//vl campaigns loaded
						if(data.vl_campaign_loaded)
						{
							this_row_string += '<td><span class="label label-success">VL campaign</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label label-important">VL campaign</span></td>';
							g_all_insertions_ok = false;
						}
						//ttd campaign created
						if(data.ttd_campaign_created)
						{
							this_row_string += '<td><span class="label label-success">TTD campaign</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label label-important">TTD campaign</span></td>';
							g_all_insertions_ok = false;
						}
						//ttd adgroups created
						if(data.ttd_adgroups_created)
						{
							this_row_string += '<td><span class="label label-success">TTD adgroups</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label ">TTD adgroups</span></td>';
							//g_all_insertions_ok = false;
						}
						//vl_ad_groups_loaded
						if(data.vl_ad_groups_loaded)
						{
							this_row_string += '<td><span class="label label-success">VL adgroups</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label ">VL adgroups</span></td>';
							//g_all_insertions_ok = false;
						}
						//ttd_rtg_created
						if(data.ttd_rtg_created)
						{
							this_row_string += '<td><span class="label label-success">TTD rtg</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label ">TTD rtg</span></td>';
							g_all_insertions_ok = false;
						}
						//vl_rtg_tags_loaded
						if(data.vl_rtg_tags_loaded)
						{
							this_row_string += '<td><span class="label label-success">VL rtg</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label ">VL rtg</span></td>';
							//g_all_insertions_ok = false;
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
						else if (data.ttd_adsets_loaded == 0)
						{
							this_row_string += '<td><span class="label ">TTD adsets</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label label-important">TTD adsets</span></td>';
							consecutive_row_failures += 1;
							g_all_insertions_ok = false;
						}
						//ttd_geo_loaded
						if(data.ttd_geo_loaded)
						{
							this_row_string += '<td><span class="label label-success">TTD geo</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label label">TTD geo</span></td>';
							//g_all_insertions_ok = false;
						}
						//ttd_sitelists_loaded
						if(data.ttd_sitelists_loaded)
						{
							this_row_string += '<td><span class="label label-success">TTD sites</span></td>';
						}
						else
						{
							this_row_string += '<td><span class="label">TTD sites</span></td>';
							//g_all_insertions_ok = false;
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