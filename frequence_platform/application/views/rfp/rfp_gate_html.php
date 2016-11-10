<!doctype html>
<html>
<body>
	<div class="rfp-gate-container"> 
		<div class="row">
			<div class="col s4 rfp-gate-form">
				<h4>Proposal Builder</h4>
				<p>Enter opportunity details below. Your proposal template information is on the right.</p>
				<form method="POST" action="/rfp/create/<?php echo $unique_display_id; ?>">
					<div id="mpq_advertiser_info_section">
						<div class="card-content">
							<div class="row">
								<div class="input-field col s12" style="height:61px;">
									<input type="hidden" id="account_executive_select" name="owner_id" value="<?php echo $owner_user_id ?: ''; ?>">
									<label id="rfp_account_executive_select_label" for="s2id_account_executive_select">Opportunity Owner</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input type="text" id="mpq_org_name" name="advertiser_name" class="grey-text text-darken-1" length="255" required value="<?php echo $advertiser_name ?: ''; ?>">
									<label for="mpq_org_name" data-error="Please fill in the advertiser's name." class="<?php echo empty($advertiser_org_name) ? "" : 'active';?>">Advertiser Name (appears on proposal)</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input type="text" id="mpq_proposal_name" name="proposal_name" class="grey-text text-darken-1" length="255" value="<?php echo $proposal_name ?: ''; ?>">
									<label for="mpq_proposal_name" class="<?php echo empty($proposal_name) ? "" : 'active';?>">Proposal Name</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input type="text" id="mpq_website_name" name="advertiser_website" class="grey-text text-darken-1" length="255" required value="<?php echo $advertiser_website ?: ''; ?>">
									<label for="mpq_website_name" data-error="Please fill in the advertiser's website." class="<?php echo empty($advertiser_website) ? "" : 'active';?>">Advertiser Website</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12" style="height:61px;">
									<input type="hidden" id="industry_select" name="industry_id" required value="<?php echo $industry_id ?: ''; ?>">
								</div>
							</div>
							<input type="hidden" name="strategy_id" value="<?php echo $strategy_id ?: ''; ?>">
							<button type="submit" id="rfp_gate_submit" class="btn btn-primary disabled">Continue</button>
						</div>
					</div>
				</form>
			</div>
			<div class="col s7 offset-s1">
				<span class="select-notice">Select your proposal template.</span>
				<div  id="strategies"></div>
			</div>
		</div>
	</div>
</body>
</html>