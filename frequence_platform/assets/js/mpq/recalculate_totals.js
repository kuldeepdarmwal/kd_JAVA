var rfp_status = {
	num_products: 0,
	num_geo_products: 0,
	num_audience_products: 0,
	num_locations: 0,
	num_rooftops: 0,
	num_tv_zones: 0,
	has_geo_products: true,
	has_audience_products: true,
	has_rooftops_products: true,
	has_tv_zone_products: true,
	has_keywords_products: false,
	is_option_display: [true, true, true],
	is_max_options: true,
	is_multi_location: false,
	is_multi_product: true
};

function recalculate_totals()
{
	var old_status = {};
	for(var key in rfp_status)
	{
		old_status[key] = rfp_status[key];
	}

	rfp_status.num_products = $('#product_section_content input[type="checkbox"]:checked').length;
	rfp_status.num_geo_products = $('input.enable_geo_section:checked').length;
	rfp_status.num_audience_products = $('input.enable_audience_section:checked').length;
	rfp_status.num_rooftops_products = $('input.enable_rooftops_section:checked').length;
	rfp_status.num_tv_zone_products = $('input.enable_tv_zones_section:checked').length;
	rfp_status.num_keywords_products = $('input.enable_keywords_section:checked').length;
	rfp_status.num_locations = (location_collection ? location_collection.locations.length : 0);
	var rfp_temp_num_locations = (rfp_status.num_locations == 0) ? 1 : rfp_status.num_locations;
	rfp_status.num_rooftops = $('#rooftops_multiselect').select2('data').length;
	rfp_status.num_tv_zones = $('#tvzones_multiselect').select2('data').length;
	rfp_status.is_max_options = rfp_status.is_option_display.indexOf(false) === -1;

	rfp_status.has_geo_products = (rfp_status.num_geo_products > 0);
	rfp_status.has_audience_products = (rfp_status.num_audience_products > 0);
	rfp_status.has_rooftops_products = (rfp_status.num_rooftops_products > 0);
	rfp_status.has_tv_zone_products = (rfp_status.num_tv_zone_products > 0);
	rfp_status.has_keywords_products = (rfp_status.num_keywords_products > 0);

	rfp_status.is_multi_location = (rfp_status.has_geo_products && rfp_status.num_locations > 1);
	rfp_status.is_multi_product = (rfp_status.num_products > 1);

	// don't even check the DOM if the state is not changed
	if(rfp_status.has_geo_products != old_status.has_geo_products) 
	{
		$('#mpq_geography_info_section').toggle(rfp_status.has_geo_products);
	}
	if(rfp_status.has_audience_products != old_status.has_audience_products)
	{
		$('#mpq_audience_info_section').toggle(rfp_status.has_audience_products);
	}
	if(rfp_status.has_rooftops_products != old_status.has_rooftops_products) 
	{
		$('#mpq_rooftops_section').toggle(rfp_status.has_rooftops_products);
	}
    if(rfp_status.has_tv_zone_products != old_status.has_tv_zone_products)
    {
        $('#mpq_tvzones_section').toggle(rfp_status.has_tv_zone_products);
    }
	if(rfp_status.has_kewords_products != old_status.has_keywords_products)
	{
		$('#mpq_keywords_section').toggle(rfp_status.has_keywords_products);
	}
	

	if(rfp_status.is_multi_location === true)
	{
		$('.require_multi_location_placeholder').hide();
		$('.require_multi_location').show();
	}
	else
	{
		$('.require_multi_location_placeholder').show();
		$('.require_multi_location').hide();
	}

	if(rfp_status.is_multi_product != old_status.is_multi_product)
	{
		$('.require-multi-product').toggle(rfp_status.is_multi_product);
	}

	if(rfp_status.is_max_options != old_status.is_max_options)
	{
		// fadeToggle doesn't take a `display` boolean :(
		if(rfp_status.is_max_options)
		{
			$('#add_new_option_button').fadeOut();
		}
		else
		{
			$('#add_new_option_button').fadeIn();
		}
	}

	var option_1_duration = $("#option_0_duration").val();
	var option_2_duration = $("#option_1_duration").val();
	var option_3_duration = $("#option_2_duration").val();

	var option_1_discount = $("#discount_percent_option_0").val();
	var option_2_discount = $("#discount_percent_option_1").val();
	var option_3_discount = $("#discount_percent_option_2").val();

	var subtotal_1 = 0;
	var subtotal_2 = 0;
	var subtotal_3 = 0;

	var exclude_zero_num_locations = (rfp_status.num_locations == 0 ? 1 : rfp_status.num_locations);
	var exclude_zero_num_rooftops = (rfp_status.num_rooftops == 0 ? 1 : rfp_status.num_rooftops);

	$('.product_budget_calc').each(function(key, value){
		if($(value).hasClass('product_budget_geo_dependent'))
		{
			var location_summary_price = $(value).data('base-price') * exclude_zero_num_locations;
			$(value).html('$'+number_with_commas(location_summary_price));
		}
		if($(value).hasClass('product_budget_rooftops_dependent'))
		{
			var rooftops_summary_price = $(value).data('base-price') * exclude_zero_num_rooftops;
			$(value).html('$'+number_with_commas(rooftops_summary_price));
		}
		if($(value).is(':visible'))
		{
			var temp_option_id = $(value).attr('data-option-id');
			if(temp_option_id == 0)
			{
				subtotal_1 += +$(value).text().replace(/[^0-9.]/g, "");
			}
			else if(temp_option_id == 1)
			{
				subtotal_2 += +$(value).text().replace(/[^0-9.]/g, "");
			}
			else if(temp_option_id == 2)
			{
				subtotal_3 += +$(value).text().replace(/[^0-9.]/g, "");
			}
			else
			{
				//unknown option
			}
		}
	});

	var setup_fee_1 = 0;
	var setup_fee_2 = 0;
	var setup_fee_3 = 0;

	$('.product_budget_no_calc').each(function(key, value){
		var temp_option_id = $(value).attr('data-option-id');
		if(temp_option_id == 0)
		{
			setup_fee_1 += +$(value).text().replace(/[^0-9.]/g, "");
		}
		else if(temp_option_id == 1)
		{
			setup_fee_2 += +$(value).text().replace(/[^0-9.]/g, "");
		}
		else if(temp_option_id == 2)
		{
			setup_fee_3 += +$(value).text().replace(/[^0-9.]/g, "");
		}
		else
		{
			//unknown option
		}
	});

	var discount_1 = Math.round(subtotal_1 * option_1_discount/100);
	var discount_2 = Math.round(subtotal_2 * option_2_discount/100);
	var discount_3 = Math.round(subtotal_3 * option_3_discount/100);

	var total_per_location_1 = (subtotal_1 - discount_1);
	var total_per_location_2 = (subtotal_2 - discount_2);
	var total_per_location_3 = (subtotal_3 - discount_3);

	$("#subtotal_option_0").html('$'+number_with_commas(subtotal_1));
	$("#subtotal_option_1").html('$'+number_with_commas(subtotal_2));
	$("#subtotal_option_2").html('$'+number_with_commas(subtotal_3));

	$('#discount_option_0').html('-$'+number_with_commas(discount_1));
	$('#discount_option_1').html('-$'+number_with_commas(discount_2));
	$('#discount_option_2').html('-$'+number_with_commas(discount_3));

	if(rfp_status.is_multi_location)
	{
		$('#total_all_location_option_0').html('$'+number_with_commas(total_per_location_1));
		$('#total_all_location_option_1').html('$'+number_with_commas(total_per_location_2));
		$('#total_all_location_option_2').html('$'+number_with_commas(total_per_location_3));
	}
	$("#grand_total_option_0").html('$'+number_with_commas((total_per_location_1 * option_1_duration)+setup_fee_1));
	$("#grand_total_option_1").html('$'+number_with_commas((total_per_location_2 * option_2_duration)+setup_fee_2));
	$("#grand_total_option_2").html('$'+number_with_commas((total_per_location_3 * option_3_duration)+setup_fee_3));
}
