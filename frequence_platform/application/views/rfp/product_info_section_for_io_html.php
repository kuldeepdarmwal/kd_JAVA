<!-- product section -->
<div id="mpq_product_info_section" class="mpq_section_card card">
	<h4 class="card-title grey-text text-darken-1">Product Selection</h4>
		<div id="product_section_content" class="card-content">

<div class="row">
	<div class="product_section_line_title col s2">
		<span class="text-bold grey-text" style="display:none;"></span>
	</div>

<?php

foreach($default_products as $product)
{
	$container_class = "";

	$product['definition'] = json_decode($product['definition'], true);
	$product['has_match'] = array_key_exists($product['id'], $io_product_data);
	$enabled_img = "";
	$disabled_img = "";
	if($product['definition']['product_enabled_img'] !== false)
	{
		$enabled_img = $product['definition']['product_enabled_img'];
		$disabled_img = $product['definition']['product_enabled_img']; 
	}
	if($product['definition']['product_disabled_img'] !== false)
	{
		$disabled_img = $product['definition']['product_disabled_img'];
	}
?>
	<div class="col s2 rfp_product_container_<?php echo $product['product_identifier']; ?> io_product_container" id="product_container_<?php echo $product['id']; ?>" data-product-id="<?php echo $product['id']; ?>">
		<div style="display:inline-block;">
			<div class="product_header_img">
				<img src="<?php echo ($product['has_match'] ? $enabled_img : $disabled_img); ?>" class="targeted_image" data-disabled-img="<?php echo $disabled_img; ?>" data-enabled-img="<?php echo $enabled_img; ?>" />
			</div>
			<div class="text-align-center grey-text text-darken-1">
<?php
echo ($product['definition']['first_name'] !== false) ? $product['definition']['first_name']."<br>" : "";
echo $product['definition']['last_name'];
$checkbox_classes = "filled-in";
	
$checkbox_checked = "";		
if($product['has_match'])
{
	$checkbox_checked = 'checked="checked" ';
}
?>
			</div>
			<input type="checkbox" <?php echo $checkbox_checked; ?>data-budget-section="product_budget_<?php echo $product['id']; ?>" class="<?php echo $checkbox_classes; ?>" id="product_<?php echo $product['id']; ?>">
			<label for="product_<?php echo $product['id']; ?>"></label>
		</div>
	</div>

<?php
}

?>

	</div>
	</div>
</div>