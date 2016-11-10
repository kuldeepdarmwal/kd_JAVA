<!-- flight section -->
<div id="mpq_flight_info_section" class="scrollspy mpq_section_card">
	<ul class="collapsible popout collapsible-accordion" id="flight_content_collection">
		<li id="flight_info_section_header">
			<div class="card-content">
				<h4 class="card-title grey-text text-darken-1 bold">Flights</h4>
			</div>
		</li>
<?php

	foreach($io_product_data as $product)
	{
		if($product['can_become_campaign'] == 1)
		{
			$min_date = "00/00/0000";
			$max_date = "00/00/0000";
			$impressions = 0;
			foreach($time_series_data as $time_series)
			{
				if($time_series['product_id'] == $product['id'])
				{
					$min_date = date('m/d/Y', strtotime($time_series['min_date']));
					$max_date = date('m/d/Y', strtotime($time_series['max_date']));
					$impressions += $time_series['sum_impressions'];
				}
			}
?>
		<li id="flights_product_<?php echo $product['id']; ?>" class="display_collection_item">
			<div class="io_flight_header">
				<div class="row">
	 				<div class="col s3 text-bold grey-text product-name">
						<img class="io_product_img" src="<?php echo $product['img']; ?>">
						<div class="io_product_title_section">
							<?php echo $product['name']; ?>
						</div>
					</div>
					<div class="col s6 text-bold grey-text io_flights_defined_text product-status">
						<span class="io_flights_not_defined_yet io_flights_defined_header_content">Flights Not Defined Yet</span>
						<span class="io_all_flights_defined io_flights_defined_header_content"><i class="material-icons io_icon_done io_geo_body_icon">&#xE8E8;</i>All Flights Defined</span>
						<div class="text-darken-1 io_all_flights_defined_with_data io_flights_defined_header_content">
							<span class="io_all_flights_defined_impressions"><?php echo number_format(((int)$impressions)/1000); ?></span>k total impressions from 
							<span class="io_all_flights_defined_start_date"><?php echo $min_date; ?></span> through <span class="io_all_flights_defined_end_date"><?php echo $max_date; ?></span>
						</div>
					</div>
					<div class="col s3">
						<a href="#" class="waves-effect waves-light btn btn-large grey darken-1 io_define_flights_button" onclick="initialize_flights_modal('<?php echo $product['id']; ?>');">Define Flights</a>
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

