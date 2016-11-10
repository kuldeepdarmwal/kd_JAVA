<!-- rooftops section -->
<?php
        if ($is_rfp == true) {
            $tv_zones_display = false;
            foreach ($products as $product) {
                if ($product['is_zones_dependent'] == 1) {
                    $tv_zones_display = true;
                }
            }
        }
?>
<div id="mpq_tvzones_section" class="mpq_section_card card scrollspy" style="<?php echo $tv_zones_display ? '' : 'display: none'; ?>">
    <h4 class="card-title grey-text text-darken-1 bold">TV Zones
        <?php
        if ($is_rfp == true) {
            $tv_zones_product_text = "";
            foreach ($products as $product) {
                if ($product['is_zones_dependent'] == 1) {
                    $tv_zone_product_definition = json_decode($product['definition'], true);
                    if ($tv_zones_product_text !== "") {
                        $tv_zones_product_text .= ", ";
                    }
                    $tv_zones_product_text .= ($tv_zone_product_definition['first_name'] !== false) ? $tv_zone_product_definition['first_name'] . " " : "";
                    $tv_zones_product_text .= $tv_zone_product_definition['last_name'];
                }
            }
            echo '<div style="display:inline-block;font-weight:normal;font-size:1rem;margin-left:5%;">' . $tv_zones_product_text . "</div>";
        }
        ?>
    </h4>
    <div class="card-content">
        <input type="hidden" style="width:100%;" id="tvzones_multiselect">
    </div>
</div>