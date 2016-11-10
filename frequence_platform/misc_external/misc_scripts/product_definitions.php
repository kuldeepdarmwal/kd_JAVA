<?php
function product_definitions()
{
	$targeted_devices = 
		array(
			"category" => "Business Solutions",
			"geo_dependent" =>true,
			"audience_dependent" => true,
			"after_discount" => false,
			"term" => array('monthly', 'weekly', 'daily'),
			'first_name' => 'Targeted',
			'last_name' => 'Devices',
			'product_disabled_img' => '/images/mpq_otp/line/devices.png',
			'product_enabled_img' => '/images/mpq_otp/color/devices.png',
			'duplicate_by_geo' => true,
			'friendly_description' => 'Local Audience Banners on all devices',
			'options' => array(
				'0' => array(
					'unit' => array(
						'default' => '800',
						'label' => false
						),
					'type' => array(
						'dropdown_options' => array(
							array('value' => 'impressions', 'text' => 'impressions'),
							array('value' => 'dollars', 'text' => 'dollars'),
							),
						'default' => 'dollars',
						'label' => false
						),
					'cpm' => array(
						'default' => '10',
						'label' => 'CPM'
						)
					),
				'1' => array(
					'unit' => array(
						'default' => '1000',
						'label' => false
						),
					'type' => array(
						'dropdown_options' => array(
							array('value' => 'impressions', 'text' => 'impressions'),
							array('value' => 'dollars', 'text' => 'dollars'),
							),
						'default' => 'dollars',
						'label' => false
						),
					'cpm' => array(
						'default' => '10',
						'label' => 'CPM'
						)
					),
				'2' => array(
					'unit' => array(
						'default' => '2000',
						'label' => false
						),
					'type' => array(
						'dropdown_options' => array(
							array('value' => 'impressions', 'text' => 'impressions'),
							array('value' => 'dollars', 'text' => 'dollars'),
							),
						'default' => 'dollars',
						'label' => false
						),
					'cpm' => array(
						'default' => '10',
						'label' => 'CPM'
						)
					)
				)
			);	

	$targeted_preroll = 
		array(
			"category" => "Business Solutions",
			"geo_dependent" => true,
			"audience_dependent" => true,
			"after_discount" => false,
			"term" => array('monthly', 'weekly', 'daily'),
			'first_name' => 'Targeted',
			'last_name' => 'Preroll',
			'product_disabled_img' => '/images/mpq_otp/line/preroll.png',
			'product_enabled_img' => '/images/mpq_otp/color/preroll.png',
			'duplicate_by_geo' => true,
			'friendly_description' => 'Local Audience Banners on all devices',
			'options' => array(
				'0' => array(
					'unit' => array(
						'default' => '800',
						'label' => false
						),
					'type' => array(
						'dropdown_options' => array(
							array('value' => 'impressions', 'text' => 'impressions'),
							array('value' => 'dollars', 'text' => 'dollars'),
							),
						'default' => 'dollars',
						'label' => false
						),
					'cpm' => array(
						'default' => '25',
						'label' => 'CPM'
						)
					),
				'1' => array(
					'unit' => array(
						'default' => '1000',
						'label' => false
						),
					'type' => array(
						'dropdown_options' => array(
							array('value' => 'impressions', 'text' => 'impressions'),
							array('value' => 'dollars', 'text' => 'dollars'),
							),
						'default' => 'dollars',
						'label' => false
						),
					'cpm' => array(
						'default' => '25',
						'label' => 'CPM'
						)
					),
				'2' => array(
					'unit' => array(
						'default' => '2000',
						'label' => false
						),
					'type' => array(
						'dropdown_options' => array(
							array('value' => 'impressions', 'text' => 'impressions'),
							array('value' => 'dollars', 'text' => 'dollars'),
							),
						'default' => 'dollars',
						'label' => false
						),
					'cpm' => array(
						'default' => '25',
						'label' => 'CPM'
						)
					)
				)
			);
	$targeted_inventory = array(
		"category" => "Automotive Solutions",
		"geo_dependent" =>true,
		"audience_dependent" => true,
		"after_discount" => false,
		"term" => array('monthly', 'weekly', 'daily'),
		'first_name' => 'Targeted',
		'last_name' => 'Inventory',
		'product_disabled_img' => '/images/mpq_otp/line/leads.png',
		'product_enabled_img' => '/images/mpq_otp/color/leads.png',
		'duplicate_by_geo' => true,
		'friendly_description' => '',
		'inventory'  => array(
			'dropdown_options' => array(
				array('value' => '0', 'text' => '0'),
				array('value' => '1100', 'text' => '<30'),
				array('value' => '1300', 'text' => '30-75'),
				array('value' => '1400', 'text' => '75-125'),
				array('value' => '1500', 'text' => '125-200'),
				array('value' => '1600', 'text' => '201-299'),
				array('value' => '1700', 'text' => '300-399'),
				array('value' => '1800', 'text' => '400-499'),
				array('value' => '1900', 'text' => '500-599'),
				array('value' => '2000', 'text' => '600-699'),
				array('value' => '2100', 'text' => '700-799'),
				array('value' => '2200', 'text' => '800-899'),
				array('value' => '2300', 'text' => '900-999'),
				array('value' => '2400', 'text' => '1000-1099'),
				array('value' => '2500', 'text' => '1100+')
				),
			'default' => '2100',
			'label' => 'Cars'
			),
		'options' => array(
			'0' => array(
				'unit' => array(
					'dropdown_options' => array(
						array('value' => '0', 'text' => '0'),
						array('value' => '50000', 'text' => '50,000'),
						array('value' => '100000', 'text' => '100,000'),
						array('value' => '150000', 'text' => '150,000'),
						array('value' => '200000', 'text' => '200,000'),
						array('value' => '250000', 'text' => '250,000'),
						array('value' => '300000', 'text' => '300,000'),
						array('value' => '350000', 'text' => '350,000'),
						array('value' => '400000', 'text' => '400,000'),
						array('value' => '450000', 'text' => '450,000'),
						array('value' => '500000', 'text' => '500,000')
						),
					'default' => '50000',
					'label' => false
					),
				'cpm' => array(
					'default' => '10',
					'label' => 'CPM'
					)
				),
			'1' => array(
				'unit' => array(
					'dropdown_options' => array(
						array('value' => '0', 'text' => '0'),
						array('value' => '50000', 'text' => '50,000'),
						array('value' => '100000', 'text' => '100,000'),
						array('value' => '150000', 'text' => '150,000'),
						array('value' => '200000', 'text' => '200,000'),
						array('value' => '250000', 'text' => '250,000'),
						array('value' => '300000', 'text' => '300,000'),
						array('value' => '350000', 'text' => '350,000'),
						array('value' => '400000', 'text' => '400,000'),
						array('value' => '450000', 'text' => '450,000'),
						array('value' => '500000', 'text' => '500,000')
						),
					'default' => '50000',
					'label' => false
					),
				'cpm' => array(
					'default' => '10',
					'label' => 'CPM'
					)
				),
			'2' => array(
				'unit' => array(
					'dropdown_options' => array(
						array('value' => '0', 'text' => '0'),
						array('value' => '50000', 'text' => '50,000'),
						array('value' => '100000', 'text' => '100,000'),
						array('value' => '150000', 'text' => '150,000'),
						array('value' => '200000', 'text' => '200,000'),
						array('value' => '250000', 'text' => '250,000'),
						array('value' => '300000', 'text' => '300,000'),
						array('value' => '350000', 'text' => '350,000'),
						array('value' => '400000', 'text' => '400,000'),
						array('value' => '450000', 'text' => '450,000'),
						array('value' => '500000', 'text' => '500,000')
						),
					'default' => '50000',
					'label' => false
					),
				'cpm' => array(
					'default' => '10',
					'label' => 'CPM'
					)
				)
			)
		);
	$targeted_visits = array(
		"category" => "Business Solutions",
		"geo_dependent" =>true,
		"audience_dependent" => true,
		"after_discount" => false,
		"term" => array('monthly', 'weekly', 'daily'),
		'first_name' => 'Targeted',
		'last_name' => 'Clicks',
		'product_disabled_img' => '/images/mpq_otp/line/visits.png',
		'product_enabled_img' => '/images/mpq_otp/color/visits.png',
		'duplicate_by_geo' => true,
		'friendly_description' => '',
		'options' => array(
			'0' => array(
				'unit' => array(
					'dropdown_options' => array(
						array('value' => '0', 'text' => '0'),
						array('value' => '200', 'text' => '200'),
						array('value' => '300', 'text' => '300'),
						array('value' => '500', 'text' => '500'),
						array('value' => '1000', 'text' => '1000'),
						),
					'default' => '200',
					'label' => false
					),
				'cpc' => array(
					'default' => '10',
					'label' => 'CPC'
					)
				),
			'1' => array(
				'unit' => array(
					'dropdown_options' => array(
						array('value' => '0', 'text' => '0'),
						array('value' => '200', 'text' => '200'),
						array('value' => '300', 'text' => '300'),
						array('value' => '500', 'text' => '500'),
						array('value' => '1000', 'text' => '1000'),
						),
					'default' => '300',
					'label' => false
					),
				'cpc' => array(
					'default' => '10',
					'label' => 'CPC'
					)
				),
			'2' => array(
				'unit' => array(
					'dropdown_options' => array(
						array('value' => '0', 'text' => '0'),
						array('value' => '200', 'text' => '200'),
						array('value' => '300', 'text' => '300'),
						array('value' => '500', 'text' => '500'),
						array('value' => '1000', 'text' => '1000'),
						),
					'default' => '500',
					'label' => false
					),
				'cpc' => array(
					'default' => '10',
					'label' => 'CPC'
					)
				)
			)
		);
	$targeted_directories = array(
		"category" => "Business Solutions",
		"geo_dependent" =>true,
		"audience_dependent" => false,
		"after_discount" => false,
		"term" => array('monthly', 'weekly', 'daily'),
		'first_name' => 'Targeted',
		'last_name' => 'Directories',
		'product_disabled_img' => '/images/mpq_otp/line/directories.png',
		'product_enabled_img' => '/images/mpq_otp/color/directories.png',
		'duplicate_by_geo' => false,
		'friendly_description' => '',
		'options' => array(
			'0' => array(
				'unit' => array(
					'default' => '500',
					'label' => 'Price per location'
					)
				),
			'1' => array(
				'unit' => array(
					'default' => '500',
					'label' => 'Price per location'
					)
				),
			'2' => array(
				'unit' => array(
					'default' => '500',
					'label' => 'Price per location'
					)
				)
			)
		);

	$targeted_content = array(
		"category" => "Automotive Solutions",
		"geo_dependent" =>true,
		"audience_dependent" => false,
		"after_discount" => false,
		"term" => array('monthly', 'weekly', 'daily'),
		'first_name' => 'Targeted',
		'last_name' => 'Content',
		'product_disabled_img' => '/images/mpq_otp/line/content.png',
		'product_enabled_img' => '/images/mpq_otp/color/content.png',
		'duplicate_by_geo' => false,
		'friendly_description' => '',
		'content' => array(
			'default' => '4',
			'label' => 'Brands per dealer location'
			),
		'options' => array(
			'0' => array(
				'price' => array(
					'default' => '1500',
					'label' => 'Price per brand per location'
					)
				),
			'1' => array(
				'price' => array(
					'default' => '1500',
					'label' => 'Price per brand per location'
					)
				),
			'2' => array(
				'price' => array(
					'default' => '1500',
					'label' => 'Price per brand per location'
					)
				)
			)
		);
	$setup_fee = array(
		"category" => false,
		"geo_dependent" => false,
		"audience_dependent" => false,
		"after_discount" => true,
		"term" => array('monthly', 'weekly', 'daily'),
		'first_name' => false,
		'last_name' => 'Setup Fee',
		'name_editable' => true,
		'name_editable_label' => 'One time fee name',
		'product_disabled_img' => false,
		'product_enabled_img' => false,
		'duplicate_by_geo' => false,
		'friendly_description' => '',
		'options' => array(
			'0' => array(
				'unit' => array(
					'default' => '1500',
					'label' => false
					)
				),
			'1' => array(
				'unit' => array(
					'default' => '1500',
					'label' => false
					)
				),
			'2' => array(
				'unit' => array(
					'default' => '1500',
					'label' => false
					)
				)
			)
		);

	$creative_fee = array(
		"category" => false,
		"geo_dependent" => false,
		"audience_dependent" => false,
		"after_discount" => true,
		"term" => array('monthly', 'weekly', 'daily'),
		'first_name' => false,
		'last_name' => 'Creative Fee',
		'name_editable' => true,
		'name_editable_label' => 'Creative fee name',
		'product_disabled_img' => false,
		'product_enabled_img' => false,
		'duplicate_by_geo' => false,
		'friendly_description' => '',
		'options' => array(
			'0' => array(
				'unit' => array(
					'default' => '100',
					'label' => false
					)
				),
			'1' => array(
				'unit' => array(
					'default' => '100',
					'label' => false
					)
				),
			'2' => array(
				'unit' => array(
					'default' => '100',
					'label' => false
					)
				)
			)
		);


	$discount = array(
		"discount_name" => false,
		"discount_percent" => 10
		);
	echo json_encode($targeted_devices);
	echo "<br><br>";
	echo json_encode($targeted_preroll);
	echo "<br><br>";
	echo json_encode($targeted_inventory);
	echo "<br><br>";
	echo json_encode($targeted_visits);
	echo "<br><br>";
	echo json_encode($targeted_directories);
	echo "<br><br>";
	echo json_encode($targeted_content);
	echo "<br><br>";
	echo json_encode($setup_fee);
	echo "<br><br>";
	echo json_encode($discount);
	echo "<br><br>";
	echo json_encode($creative_fee);

}

product_definitions();

?>