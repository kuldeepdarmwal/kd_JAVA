<!-- creative section -->
<div id="mpq_creative_info_section" class="scrollspy mpq_section_card">
	<ul class="collapsible popout collapsible-accordion" id="creative_content_collection">
		<li id="creative_info_section_header">
			<div class="card-content">
				<h4 class="card-title grey-text text-darken-1 bold">Creatives</h4>
			</div>
		</li>
<?php

	foreach($io_product_data as $product)
	{
		if($product['can_become_campaign'] == 1)
		{
?>
		<li id="creatives_product_<?php echo $product['id']; ?>" class="display_collection_item">
			<div class="io_creative_header">
				<div class="row">
	 				<div class="col s3 text-bold grey-text product-name">
						<img class="io_product_img" src="<?php echo $product['img']; ?>">
						<div class="io_product_title_section">
							<?php echo $product['name']; ?>
						</div>
					</div>
					<div class="col s5 text-bold grey-text io_creatives_defined_text product-status">
						<span class="io_all_creatives_defined">Creatives Assigned</span>
						<span class="io_all_creatives_hold">Creative Requests Pending</span>
						<a href="/creative_requests/new" class="io_creatives_not_defined_yet" onclick="preload_new_adset_request('<?php echo $product['banner_intake_id']; ?>');">New Creative Request&nbsp;&nbsp;<i class="icon-picture icon-white"></i></a>
					</div>
					<div class="col s4">
						<a href="#" class="waves-effect waves-light btn btn-large grey darken-1 io_new_adset_request_button" onclick="initialize_define_creatives_modal('<?php echo $product['id']; ?>'); return false;">Assign Creatives</a>
					</div>
	 			</div>
			</div>
			<div class="collapsible-body">
				<div class="card-content">
				</div>
			</div>
	   	</li>
<?php
		}
	}

?>	 
	</ul>
</div>

