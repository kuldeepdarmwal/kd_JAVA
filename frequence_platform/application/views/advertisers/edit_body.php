<div class="container">
	<div class="row">
		<div class="card">
			<form action="/advertisers/edit/<?php echo $advertiser['advertiser_id']; ?>" method="post" id="edit_advertiser" class="card-content">
				<h4 class="card-title">Advertiser Info</h4>
				<div class="row">
					<div class="input-field col s6">
						<input type="text" name="advertiser_name" class="validate" required value="<?php echo $advertiser['advertiser_name']; ?>" placeholder=""/>
						<label for="advertiser_name" data-error="Please fill out the Advertiser Name.">Advertiser Name</label>
					</div>
				</div>
				<div class="row">
					<div class="input-field col s6">
						<input type="text" name="external_id" value="<?php echo $advertiser['external_id']; ?>" placeholder=""/>
						<label for="external_id">External ID</label>
					</div>
				</div>
				<div class="row">
					<div class="input-field col s6" style="height:61px;">
						<input type="hidden" name="sales_person" class="validate" required id="sales_person" value="<?php echo $advertiser['sales_person']; ?>"/>
						<input type="hidden" id="sales_person_text" data-error="Please select a Sales User." value="<?php echo $advertiser['user_name']; ?>" placeholder=""/>
					</div>
				</div>
				<input type="hidden" name="advertiser_id" value="<?php echo $advertiser['advertiser_id']; ?>" />
				<div class="row">
					<div class="input-field col s6">
						<a href="#" id="advertiser_edit_submit" class="waves-effect waves-light btn">Submit</a>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>