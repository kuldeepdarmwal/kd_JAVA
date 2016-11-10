<?php
	$proposal_opportunity_name = "";	
	if (!isset($io_advertiser_id) && !empty($advertiser_org_name))
	{
		$proposal_opportunity_name = " (Proposal Opportunity Name : <strong>$advertiser_org_name</strong>)";
	}
?>
<!-- advertiser section -->
<div id="mpq_advertiser_info_section" class="mpq_section_card card scrollspy">
	<h4 class="card-title grey-text text-darken-1">Opportunity Details<?php echo ($is_preload == true && !empty($submission_method) ? " (".$submission_method.")" : ""); ?></h4>
	<div class="card-content">
		<div class="row">
			<div class="input-field col s6" style="height:61px;">
				<input type="hidden" id="account_executive_select">
				<label id="rfp_account_executive_select_label" for="s2id_account_executive_select">Opportunity Owner</label>
			</div>
		</div>
		<div id="verified_advertiser_container" class="row">
			<div class="input-field col s6">
				<input type="hidden" id="mpq_org_name" class="grey-text text-darken-1" length="255"/>
				<label for="s2id_mpq_org_name" class="mpq_org_name_label">Advertiser Name <?php echo $proposal_opportunity_name; ?></label>
			</div>
		</div>
		<div id="unverified_advertiser_container" class="row">
			<div class="input-field col s6">
				<input placeholder="" id="unverified_advertiser" name="unverified_advertiser_name" type="text" length="255"/>
				<label for="unverified_advertiser">Unverified Advertiser Name</label>
			</div>
		</div>
		<div class="row">
			<div class="input-field col s6">
				<input type="text" id="mpq_website_name" class="grey-text text-darken-1" length="255" value="<?php echo empty($advertiser_website) ? "" : $advertiser_website; ?>">
				<label for="mpq_website_name" class="<?php echo empty($advertiser_org_name) ? "" : 'active';?>">Advertiser Website</label>
			</div>
		</div>
		<div class="row">
			<div class="input-field col s6">
				<input type="text" id="mpq_order_name" class="grey-text text-darken-1" length="255" value="<?php echo empty($order_name) ? "" : $order_name; ?>">
				<label for="mpq_order_name" class="<?php echo empty($advertiser_org_name) ? "" : 'active';?>">Order Name</label>
			</div>
		</div>	
		<div class="row">
			<div class="input-field col s6">
				<input type="text" id="mpq_order_id" class="grey-text text-darken-1" length="255" value="<?php echo empty($order_id) ? "" : $order_id; ?>">
				<label for="mpq_order_id" class="<?php echo empty($advertiser_org_name) ? "" : 'active';?>">Order ID</label>
			</div>
		</div>
		<div class="row">
			<div class="input-field col s6" style="height:61px;">
				<input type="hidden" id="industry_select">
			</div>
		</div>
		<div id="unique_display_id_header_text">ID: <?php echo ($unique_display_id !== NULL ? $unique_display_id : "None");?></div>
	</div>
</div>
