<div class="container-fluid">
	<ul id="banner_tabs" class="nav nav-pills" data-tabs="tabs">
		<li><a href="/proposal_builder/get_all_mpqs/proposals">Proposals</a></li>
		<li class="active"><a href="/proposal_builder/get_all_mpqs/io">Insertion Orders</a></li>
		<li><a href="/proposal_builder/get_all_mpqs/rfp">Instant Proposals</a></li>
	</ul>
	<h3>Submitted MPQ Insertion Orders</h3>
	<div id="load_more_form_div">
		<form action ="" method="get">
			<span>Show: </span>
			<input type="text" name="limit" id="load_more_limit_input" value="<?php echo $limit; ?>">
			<span> MPQ(s) starting from # </span>
			<input type="text" name="start" id="load_more_start_input" value="<?php echo $start; ?>">
			<button type="submit" class="btn btn-info" id="load_more_button">Show MPQs</button>
		</form>
	</div>
	<table id="mpq_list_table" name="site_pack_table" class="table table-hover table-striped">
		<thead>
			<tr>
				<th>
				    Created
				</th>
				<th>
				    Campaign
				</th>
				<th>
				    Media <i class="icon-list-alt"></i>
				</th>
				<th>
					Geo <i class="icon-globe"></i>
				</th>
				<th>
					Creative
				</th>
				<th>
					<span data-toggle="tooltip" title="The number of impressions divided by 1,000">kImprs</span>
				</th>
				<th>
					Term
				</th>
				<th>
					<span data-toggle="tooltip" title="The total population of selected regions divided by 1,000">kPop</span>
				</th>
				<th>
					Advertiser
				</th>
				<th>
					Landing Page
				</th>
				<th>
					Partner: Referrer | Submitter
				</th>
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
					echo "<tr>";

				    //Created
					echo '<td><a onclick="open_submit_notes_box(\''.$mpq["id"].'\')" href="#">'.date("Y-m-d", strtotime($mpq['creation_time'])).'</a></td>';

					if (!empty($mpq['campaign_ids']))
					{
						echo '<td><select class="campaign_select" data-io-id="'.$mpq["mio_id"].'"><option></option>';
						echo '<option value="create_new">Create New Campaign</option>';
						$campaign_ids = explode(',', $mpq['campaign_ids']);
						$campaign_names = explode(',', $mpq['campaign_names']);
						foreach ($campaign_ids as $i => $id)
						{
							echo '<option value="/campaign_setup/' . $id . '">' . $campaign_names[$i] . '</option>';
						}
						echo '</select></td>';
					}
					else
					{
						echo '<td><a class="btn btn-mini btn-info create-campaign" href="#" data-toggle="tooltip" data-io-id="'.$mpq["mio_id"].'" title="Create Campaign"><i class="icon-edit icon-white"></i></a></td>';
					}

				    //Media / Geo
					if($mpq['num_derived_proposals'] > 0)
					{
						if($mpq['has_sitelist'] == 1)
						{
							echo
								'<td>'.
								'<a class=" btn btn-mini"  target="_blank" href="/proposal_builder/edit_sitelist/'.$mpq["prop_id"].'/" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a> &nbsp;'.
								'<a class=" btn btn-mini" href="/proposal_builder/export_sites/'.$mpq["prop_id"].'" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i></a>'.
								'</td>'.
								'<td>'.
								'<a class=" btn btn-mini" target="_blank" href="/proposal_builder/force_session/'.$mpq['mpq_lap_id'].'" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a> &nbsp;'.
								'<a class=" btn btn-mini" href="/proposal_builder/export_geos/'.$mpq["prop_id"].'/false/'.$mpq["id"].'/" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i></a>'.
								'</td>';

						}
						else
						{
							echo 
								'<td>'.
								'<a class=" btn btn-mini btn-info"  target="_blank" href="/proposal_builder/edit_sitelist/'.$mpq["prop_id"].'/" data-toggle="tooltip" title="Create"><i class="icon-edit icon-white"></i></a> &nbsp;'.
								'<a class=" btn btn-mini disabled" onclick="event.preventDefault();" href="#"><i class="icon-download-alt"></i></a>'.
								'</td>'.
								'<td>'.
								'<a class=" btn btn-mini" target="_blank" href="/proposal_builder/force_session/'.$mpq['mpq_lap_id'].'" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a> &nbsp;'.
								'<a class=" btn btn-mini" href="/proposal_builder/export_geos/'.$mpq["prop_id"].'/false/'.$mpq["id"].'/" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i></a>'.
								'</td>';
						}
					}
					else
					{
						echo 
						'<td class="">'.
						'<a class=" btn btn-mini btn-info"  target="_blank" href="/proposal_builder/option_engine/create_from_mpq/'.$mpq["id"].'/" data-toggle="tooltip" title="Create"><i class="icon-edit icon-white"></i></i></a> &nbsp;'.
						'<a class=" btn btn-mini disabled" onclick="event.preventDefault();" href="#"><i class="icon-download-alt"></i></a>'.
						'</td>'.
						'<td>'.
						'<a class=" btn btn-mini"  target="_blank" href="/proposal_builder/option_engine/create_from_mpq_with_action/'.$mpq['id'].'/planner" data-toggle="tooltip" title="Edit"><i class="icon-edit"></i></a> &nbsp;'.
						'<a class=" btn btn-mini"  onclick="location.reload(true);" href="/proposal_builder/option_engine/create_from_mpq_with_action/'.$mpq["id"].'/download_geo" data-toggle="tooltip" title="Download"><i class="icon-download-alt"></i></a>'.
						'</td>';
					}


					//Creative
					echo $mpq['adset_request_id'] ? 
						'<td><a href="/banner_intake/review/'.$mpq['adset_request_id'].'" target="_blank">'.$mpq['adset_request_id'].'</a></td>' : //OR
						'<td></td>';

					//Impressions
					echo '<td>'.number_format(round($mpq['mio_impressions'] / 1000)).'</td>';

					//Term
					$ttp_term_array = array(
						"monthly" => array("mo", "month", "months"),
						"daily" => array("dy", "day", "days"),
						"weekly" => array("wk", "week", "weeks"),
						"BROADCAST_MONTHLY" => array("mo", "month", "months"),
						"MONTH_END" => array("mo", "month", "months")
					);
					$ttp_imprs = " ".$mpq['mio_impressions']." imprs";
					$ttp_title =  ($mpq['mio_term_type'] == "FIXED_TERM" || $mpq['mio_term_type'] == "in total") ? " total" : "/".$ttp_term_array[$mpq['mio_term_type']][0];
					$ttp_title .= " from ".$mpq['mio_start_date'];
					if($mpq['mio_end_date'] != NULL)
					{
						$ttp_title .= " to ".$mpq['mio_end_date'];
					}
					else if(is_numeric($mpq['mio_term_duration']))
					{
						$ttp_title .= " for ".$mpq['mio_term_duration']. " ";
						$ttp_title .= ((int)$mpq['mio_term_duration'] > 1) ? $ttp_term_array[$mpq['mio_term_type']][2] : $ttp_term_array[$mpq['mio_term_type']][1];
					}
					else if($mpq['mio_term_duration'] == "on going")
					{
						$ttp_title .= " Ongoing";
					}
					else
					{
						$ttp_title .= " Unknown";
					}
					echo '<td>'.$ttp_title.'</td>';

					//Population
					$population = 0;
					$region = json_decode($mpq['region_data']);
					if($region)
					{
						// TODO: update to account for arrays with multiple elements
						if(is_array($region))
						{
							$region = $region[0];
						}
						$this->map->convert_old_flexigrid_format_object($region);
						$population_string = $this->map->get_demographics_from_region_array(array('zcta' => $region->ids->zcta))['region_population'];
						$population = intval($population_string);
					}

					echo '<td>'.number_format(floor($population / 1000)).'</td>';

					//Advertiser
					echo '<td><span data-toggle="tooltip" title="'.$mpq['advertiser_name'].'">'.truncate($mpq['advertiser_name'], 30).'</span></td>';

					//Advertiser Website
					echo '<td><a href="'.$mpq['mio_landing_page'].'" target="_blank" data-toggle="tooltip" title="'.$mpq['mio_landing_page'].'">'.truncate($mpq['mio_landing_page'], 20).'</a></td>';

					//User
					$partner_name = $mpq['partner_name'] ? $mpq['partner_name'] . ' : ' : '';
					$user_name = $mpq['user_name'] ? $mpq['user_name'] : '';
					$submitter_name = $mpq['submitter_name'] ? ' | ' . $mpq['submitter_name'] : '';
					echo '<td><small>'. $partner_name . $user_name . $submitter_name .'</small></td>';

					echo "</tr>";
				
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

<div id="campaign_modal" class="modal hide fade" role="dialog" aria-labelledby="campaign_modal_label" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="campaign_modal_label">Create new Campaign from Insertion Order</h3>
	</div>
	<form id="campaign_submit">
		<div class="modal-body">
			<div>
				<label for="campaign_name">Campaign Name</label>
				<input type="text" name="campaign_name" length="63" placeholder="Campaign Name"/>
			</div>
			<div>
				<label for="business_id">Advertiser</label>
				<select id="advertiser_select" name="business_id" data-placeholder="select an advertiser">
					<option> <option>
					<option value="none">Select Advertiser</option>
					<option value="new">*New*</option>
				</select>
			</div>
		<input type="hidden" name="insertion_order_id"/>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
			<button id='campaign_submit_btn' class="btn btn-primary">Create Campaign</button>
		</div>
	</form>
</div>
