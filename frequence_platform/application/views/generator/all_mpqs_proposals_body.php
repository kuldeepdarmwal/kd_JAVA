<div class="container-fluid">
	<ul id="banner_tabs" class="nav nav-pills" data-tabs="tabs">
		<li class="active"><a href="/proposal_builder/get_all_mpqs/proposals">Proposals</a></li>
		<li><a href="/proposal_builder/get_all_mpqs/io">Insertion Orders</a></li>
		<li><a href="/proposal_builder/get_all_mpqs/rfp">Instant Proposals</a></li>
	</ul>
	<h3>Submitted MPQ Proposals</h3>
	<div id="load_more_form_div">
		<form action ="" method="get">
			<span>Show: </span>
			<input type="text" name="limit" id="load_more_limit_input" value="<?php echo $limit; ?>">
			<span> starting from # </span>
			<input type="text" name="start" id="load_more_start_input" value="<?php echo $start; ?>">
			<button type="submit" class="btn btn-primary" id="load_more_button">Show MPQs</button>
		</form>
	</div>
	<table id="mpq_list_table" name="site_pack_table" class="table table-hover table-striped">
		<thead>
			<tr>
				<th>Created</th>
				<th>Media <i class="icon-list-alt"></i></th>
				<th>Geos <i class="icon-globe"></i></th>
				<th>Advertiser</th>
				<th>Advertiser Website</th>
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
					echo '<tr>'; 

					/*Proposal
					if($mpq['num_derived_proposals'] > 0)
					{
						echo '<td class=""><a target="_blank" href="/proposal_builder/option_engine/'.$mpq["prop_id"].'">Load</a></td>';
					}
					else
					{
						echo '<td><a class="btn btn-mini btn-warning" target="_blank" href="/proposal_builder/option_engine/create_from_mpq/'.$mpq['id'].'">Create</a></td>';
					}*/

					//Created
					echo '<td>'.'<a onclick="open_submit_notes_box(\''.$mpq["id"].'\')" href="#">'.$mpq['creation_time'].'</a> &nbsp;';

					$options_html = "";
					if (isset($mpq['options_array']))
					{
						$options_html .= '<div class="geos_download">';
						foreach ($mpq['options_array'] as $opt)
						{
							$options_html .= '<a href="/proposal_builder/export_geos/'.$mpq["prop_id"].'/'.$opt["option_id"].'/">'.$opt['option_name'].'</a><br />';
						}
						$options_html .= '</div>';
					}

				    //Media / Geo
					if($mpq['num_derived_proposals'] > 0)
					{
						if($mpq['has_sitelist'] == 1)
						{
							echo
								'<a class=" btn btn-mini" target="_blank" href="/proposal_builder/option_engine/'.$mpq['prop_id'].'" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a>'.
								'</td>'.
								'<td>'.
								'<a class=" btn btn-mini"  target="_blank" href="/proposal_builder/edit_sitelist/'.$mpq["prop_id"].'/" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a> &nbsp;'.
								'<a class=" btn btn-mini" href="/proposal_builder/export_sites/'.$mpq["prop_id"].'" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i></a>'.
								'</td>'.
								'<td>'.
								'<button class=" btn btn-mini geos_button" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i>'.$options_html.'</button>'.
								'</td>';

						}
						else
						{
							echo 
								'<a class=" btn btn-mini" target="_blank" href="/proposal_builder/option_engine/'.$mpq['prop_id'].'" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a>'.
								'</td>'.
								'<td>'.
								'<a class=" btn btn-mini btn-info"  target="_blank" href="/proposal_builder/edit_sitelist/'.$mpq["prop_id"].'/" data-toggle="tooltip" title="Create"><i class="icon-edit icon-white"></i></a> &nbsp;'.
								'<a class=" btn btn-mini disabled" onclick="event.preventDefault();" href="#"><i class="icon-download-alt"></i></a>'.
								'</td>'.
								'<td>'.
								'<button class=" btn btn-mini geos_button" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i>'.$options_html.'</button>'.
								'</td>';
						}
					}
					else
					{
						echo 
						'<a class=" btn btn-mini btn-info"  target="_blank" href="/proposal_builder/option_engine/create_from_mpq/'.$mpq['id'].'" data-toggle="tooltip" title="Create"><i class="icon-edit icon-white"></i></a>'.
						'</td>'.
						'<td class="">'.
						'<a class=" btn btn-mini disabled" onclick="event.preventDefault();" href="/proposal_builder/edit_sitelist/'.$mpq["prop_id"].'/"><i class="icon-edit"></i></a> &nbsp;'.
						'<a class=" btn btn-mini disabled" onclick="event.preventDefault();" href="#"><i class="icon-download-alt"></i></a>'.
						'</td>'.
						'<td>'.
						'<button class=" btn btn-mini geos_button disabled"><i class="icon-download-alt"></i>'.$options_html.'</button>'.
						'</td>';
					}

					//Advertiser Name
					echo '<td><span data-toggle="tooltip" title="'.$mpq['advertiser_name'].'">'.truncate($mpq['advertiser_name'], 40).'</span></td>';

					//Advertiser Website
					echo '<td><a href="'.$mpq['advertiser_website'].'" target="_blank" data-toggle="tooltip" title="'.$mpq['advertiser_website'].'">'.truncate($mpq['advertiser_website'], 30).'</a></td>';

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
	<h4>MPQ Summary</h4>
  </div>
  <div id="submittal_view_modal_body" class="modal-body">
	Body goes here
  </div>
</div>
