<div class="container-fluid">
	<ul id="banner_tabs" class="nav nav-pills" data-tabs="tabs">
		<li><a href="/proposal_builder/get_all_mpqs/proposals">Proposals</a></li>
		<li><a href="/proposal_builder/get_all_mpqs/io">Insertion Orders</a></li>
		<li class="active"><a href="/proposal_builder/get_all_mpqs/rfp">Instant Proposals</a></li>
	</ul>
	<h3>Instant Proposals</h3>
	<div id="load_more_form_div">
		<form action ="" method="get">
			<span>Show: </span>
			<input type="text" name="limit" id="load_more_limit_input" value="<?php echo $limit; ?>">
			<span> starting from # </span>
			<input type="text" name="start" id="load_more_start_input" value="<?php echo $start; ?>">
			<button type="submit" class="btn btn-primary" id="load_more_button">Show Instant Proposals</button>
		</form>
	</div>
	<table id="mpq_list_table" name="site_pack_table" class="table table-hover table-striped">
		<thead>
			<tr>
				<th>Created</th>
				<th>Media <i class="icon-list-alt"></i></th>
				<th>Geos <i class="icon-globe"></i></th>
				<th>Industry</th>
				<th>Context</th>
				<th>Advertiser Name</th>
				<th>Advertiser Site</th>
				<th>Partner: Referrer | Submitter</th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Trim a string over a certain length and add ellipses
				function truncate($str, $length = 0)
				{
					if (strlen($str) > $length)
					{
						$str = substr($str, 0, $length);
						$str = rtrim($str, '\.');
						$str .= '...';
					}

					return $str;
				}

				foreach($mpqs_data as $mpq) 
				{
					$regions_download_html = "";
					$region_count=0;
					if (!empty($mpq['regions_list']))
					{
						$regions_download_html .= '<div class="geos_rfp_download">';
						foreach ($mpq['regions_list'] as $region)
						{
							$regions_download_html .= '<a href="/proposal_builder/export_geos_by_region_id/'.$mpq["id"].'/'.$region["id"].'/">'.truncate($region['name'], 10).'</a><br />';
							$region_count++;
						}
						$regions_download_html .= '</div>';
					}

					echo '<tr>'; 

					//Created
		 			echo '<td><small><a onclick=\'open_rfp_notes_box('.json_encode($mpq["original_submission_summary_html"]).')\' href="#">'.$mpq['creation_time'].'</a>&nbsp;&nbsp;';
		 			echo '<a class="btn btn-mini" target="_blank" href="/proposals/'.$mpq["prop_id"].'/build" data-toggle="tooltip" title="Edit Proposal"><i class="icon-edit"></i></a>&nbsp;&nbsp;';
					if ($mpq["pdf_location"] != null)
					{
						echo '<a class="btn btn-mini" target="_blank" href="/proposals/'.$mpq["prop_id"].'/download/'.$mpq["pdf_title"].'" data-toggle="tooltip" title="'.$mpq["process_status"].'/'.$mpq["date_modified"].'/MID:'.$mpq["id"].'"><i class="icon-download"></i></a>';
					}
					else
					{
						echo '<a class="btn btn-mini btn-warning" data-toggle="tooltip" title="'.$mpq["process_status"].'/'.$mpq["date_modified"].'/MID: '.$mpq["id"].'"><i class="icon-warning-sign"></i></a>';
					}

					echo
						'</small></td>'.
						'<td><small>'.
						'&nbsp;<a class=" btn btn-mini" href="/proposal_builder/export_sites/'.$mpq["prop_id"].'" data-toggle="tooltip" title="Download Sites"><i class="icon-download-alt"></i></a>'.
						'</td>'.
						'<td><small>'.$region_count.
						'&nbsp;<button class="btn btn-mini geos_rfp_button" data-toggle="tooltip" title="Download Ziplist per Geo"><i class="icon-download-alt"></i>'.$regions_download_html.'</button>'.
						'</small></td>';
					
					//Industry Name
					echo '<td><small><span data-toggle="tooltip" title="'.truncate($mpq['industry_name'], 40).'">'.truncate($mpq['industry_name'], 40).'</span></small></td>';
					echo '<td><small><span data-toggle="tooltip" title="'.truncate($mpq['contextual_string'], 200).'">'.$mpq['contextual_count'].'</span></small></td>';

					//Advertiser Name
					echo '<td><small><span data-toggle="tooltip" title="'.truncate($mpq['advertiser_name'], 40).'">'.truncate($mpq['advertiser_name'], 40).'</span></small></td>';

					//Advertiser Website
				 
					echo '<td><small><a href="http:\\\\' . $mpq['advertiser_website'].'" target="_blank" data-toggle="tooltip" title="'.truncate($mpq['advertiser_website'], 30).'">'.truncate($mpq['advertiser_website'], 30).'</a></small></td>';

					//User
					$partner_name = $mpq['partner_name'] ? $mpq['partner_name'] . ' : ' : '';
					$user_name = $mpq['user_name'] ? $mpq['user_name'] : '';
					$submitter_name = $mpq['submitter_name'] ? ' | ' . $mpq['submitter_name'] : '';
					echo '<td><small>'. $partner_name . $user_name . $submitter_name .'</small></td>';

					echo '</tr>';

				}
			?>
		</tbody>
	</table>
</div>

<div id="submittal_view_modal" class="modal hide fade">
  <div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	<h4>Proposal Summary</h4>
  </div>
  <div id="submittal_view_modal_body" class="modal-body">
	Body goes here
  </div>
</div>

<script type="text/javascript">
	
	function open_rfp_notes_box(data)
	{
		if (data == undefined || data == "")
			data = "No summary found";
		$('#submittal_view_modal_body').html(data);
		$('#submittal_view_modal').modal('show');
	}
</script>
