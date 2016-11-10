<!-- product section -->
<div id="mpq_product_info_section" class="mpq_section_card card">
	<h4 class="card-title grey-text text-darken-1">Product Selection</h4>
		<div id="product_section_content" class="card-content">
<?php

$business_categories = array();

foreach($products as $product)
{
	$temp_definition = json_decode($product['definition'], true);
	if(array_key_exists('category', $temp_definition) and $temp_definition['category'] !== false)
	{
		$temp_definition['id'] = $product['id'];

		$temp_definition['has_match'] = !array_key_exists('has_match', $product) ? true: $product['has_match'];
		$temp_definition['is_geo_dependent'] = $product['is_geo_dependent'];
		$temp_definition['is_audience_dependent'] = $product['is_audience_dependent'];
		$temp_definition['is_rooftops_dependent'] = $product['is_rooftops_dependent'];
		$temp_definition['is_zones_dependent'] = $product['is_zones_dependent'];
		$temp_definition['product_identifier'] = $product['product_identifier'];
		$temp_definition['is_political'] = $product['is_political'];
		$temp_definition['is_keywords_dependent'] = $product['is_keywords_dependent'];
		$business_categories[$temp_definition['category']][] = $temp_definition;
	}
}


foreach($business_categories as $category_name => $category)
{
	$product_class_size = count($category) > 5 ? "1" : "2";
?>

<div class="row">
	<div class="product_section_line_title col s2">
		<span class="text-bold grey-text" style="display:none;">
		 <?php echo $category_name; ?>
		</span>
	</div>

<?php

foreach($category as $checkbox_item)
{
	$container_class = "";
	if($checkbox_item['product_identifier'] === "display" || $checkbox_item['product_identifier'] === "preroll" || $checkbox_item['product_identifier'] === "leads" || $checkbox_item['product_identifier'] === "content" || $checkbox_item['product_identifier'] === "directories")
	{
		$container_class .= " rfp_product_container_";
	}
	$enabled_img = "";
	$disabled_img = "";
	if($checkbox_item['product_enabled_img'] !== false)
	{
		$enabled_img = $checkbox_item['product_enabled_img'];
		$disabled_img = $checkbox_item['product_enabled_img'];
	}
	if($checkbox_item['product_disabled_img'] !== false)
	{
		$disabled_img = $checkbox_item['product_disabled_img'];
	}
?>
	<div class="col s<?php echo $product_class_size.$container_class;?>" data-product-id="<?php echo $checkbox_item['id']; ?>">
		<div style="display:inline-block;">
			<div class="product_header_img">
				<img src="<?php echo ($checkbox_item['has_match'] ? $enabled_img : $disabled_img); ?>" class="targeted_image" data-disabled-img="<?php echo $disabled_img; ?>" data-enabled-img="<?php echo $enabled_img; ?>" />
			</div>
			<div class="text-align-center grey-text text-darken-1 product-display-text">
<?php
echo ($checkbox_item['first_name'] !== false) ? $checkbox_item['first_name']."<br>" : "";
echo $checkbox_item['last_name'];
$checkbox_classes = "filled-in";
if($checkbox_item['is_geo_dependent'] == 1)
{
	$checkbox_classes .= " enable_geo_section";
}
if($checkbox_item['is_audience_dependent'] == 1)
{
	$checkbox_classes .= " enable_audience_section";
}
if($checkbox_item['is_rooftops_dependent'] == 1)
{
	$checkbox_classes .= " enable_rooftops_section";
}
if($checkbox_item['is_zones_dependent'] == 1)
{
    $checkbox_classes .= " enable_tv_zones_section";
}
if($checkbox_item['is_keywords_dependent'] == 1)
{
	$checkbox_classes .= " enable_keywords_section";
}

$checkbox_checked = "";
if($checkbox_item['has_match'])
{
	$checkbox_checked = 'checked="checked" ';
}
?></div>
			<input type="checkbox" <?php echo $checkbox_checked; ?>data-budget-section="product_budget_<?php echo $checkbox_item['id']; ?>" class="<?php echo $checkbox_classes; ?>" id="product_<?php echo $checkbox_item['id']; ?>">
			<label for="product_<?php echo $checkbox_item['id']; ?>"></label>
		</div>
	</div>

<?php
}

?>

	</div>

<?php
}
?>
	</div>
</div>