<div class="container-fluid banner_intake_review_body">
	<div class="tabbable">
		<ul id="banner_tabs" class="nav nav-pills" data-tabs="tabs">
			<li><a href="/banner_intake">New Adset Request</a></li>
			<li class="active"><a href="/banner_intake/review">Existing Adset Requests</a></li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="existing_adsets">
					<table class="table table-hover table-condensed">
						<thead>
							<tr>
								<th>Request #</th>
								<th>Partner</th>
								<th>Owner</th>
								<th>Advertiser</th>
								<th>Landing Page</th>
								<th>Date Requested</th>
							</tr>
						</thead>
						<tbody>
							<?php
								if (empty($adset_requests))
								{
									echo '<h4 class="warning">Sorry, there are no more requests to parse! <a href="/banner_intake/review">Go back to the beginning?</a></h4>';
								}
								else
								{
									foreach ($adset_requests as $request)
									{
										$date = new DateTime($request['updated']);

										echo "<tr>
										<td><a href=\"/banner_intake/review/". $request['id'] ."\">". $request['id'] ."</a></td>
										<td>". $request['user']['partner_name'] ."</td>
										<td>". $request['user']['firstname'] ." ". $request['user']['lastname'] ."</td>
										<td>". $request['advertiser_name'] ."</td>
										<td>". $request['landing_page'] ."</td>
										<td>". $date->format('Y-m-d') ."</td>
										</tr>";
									}
								}
							?>
						</tbody>
					</table>
					<?php echo $page_links; ?>
			</div>
		</div>
	</div>


</div>
