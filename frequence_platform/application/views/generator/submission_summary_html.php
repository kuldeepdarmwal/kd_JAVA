<?php
function format_option_budget_line($value, $modifiers = NULL, $strong = true)
{
	if($strong)
	{
		$output = '<strong>$' . number_format($value) . '</strong>';		
	}
	else
	{
		$output = '$' . number_format($value);
	}

	if(!empty($modifiers))
	{
		$output .= ' <small>';
		if(is_array($modifiers))
		{
			$output .= $modifiers['term'];
		}
		else
		{
			$output .= $modifiers;
		}
		$output .= '</small>';
	}

	return $output;
}

$show_audience_info = false;
$show_geo_info = false;
$show_zone_info = false;
foreach($proposal['products'] as $submitted_product)
{
	if($submitted_product['is_audience_dependent'] == 1)
	{
		$show_audience_info = true;
	}
	if($submitted_product['is_geo_dependent'] == 1)
	{
		$show_geo_info = true;
	}
    if($submitted_product['is_zones_dependent'] == 1)
    {
        $show_zone_info = true;
    }
}

?>

<h4>Opportunity Details <span style="font-weight:normal; font-size: 12px; color: #9e9e9e; margin-left:45px;">ID: <?php echo ($mpq['unique_display_id'] !== null ? $mpq['unique_display_id'] : "None Available"); ?></span></h4>
<?php
$on_behalf_of = '';
if($creator->email != $mpq['submitter_email'])
{
	$on_behalf_of = '<strong>' . $creator->firstname . ' ' . $creator->lastname . ' (' . $creator->email . ')</strong> on behalf of ';
}
?>
<p>Submitted by <?php echo $on_behalf_of; ?><strong><?php echo $mpq['submitter_name'] . ' (' . $mpq['submitter_email'] . ')'; ?></strong></p>
<ul>
	<li><label>Advertiser Name:</label> <strong><?php echo $proposal['advertiser']; ?></strong></li>
	<li><label>Advertiser Website:</label> <strong><?php echo $mpq['advertiser_website']; ?></strong></li>
	<li><label>Proposal Name:</label> <strong><?php echo (empty($mpq['proposal_name']) ? "--" : $mpq['proposal_name']); ?></strong></li>
	<li><label>Advertiser Industry:</label> <strong><?php echo $industry['name']; ?></strong></li>
</ul>


<?php
if($show_zone_info === true)
{
    $tv_zone_text = "";
    foreach($tv_zones as $i => $tv_zone)
    {
        $tv_zone_text .= $tv_zone['text'];
        if ($i !== count($tv_zones)-1)
        {
            $tv_zone_text .= ', ';
        }
    }
    ?>
    <h4>Targeted Zones</h4>
    <blockquote><?php echo $tv_zone_text; ?></blockquote>
    <?php

}


if (count($proposal['sem_keywords']) > 0)
{
	echo '<h4>SEM Keywords</h4>';
	echo '<blockquote>';
	for ($i = 0; $i < count($proposal['sem_keywords']); $i++)
	{
		if ($i > 0)
		{
			echo ", ";
		}
		echo $proposal['sem_keywords'][$i];
	}
	echo '</blockquote>';
}


if($show_geo_info === true)
{
	?>
	<h4>Targeted Geographies</h4>
	<?php
	if(count($proposal['geos']) > 1)
	{
		foreach($proposal['geos'] as $region_id => $region)
		{
	?>
			<h5><?php echo $region['name']; ?></h5>
			<blockquote><?php echo implode(', ', $region['zips']); ?></blockquote>
			<?php if(array_key_exists('has_geofences', $proposal) && $proposal['has_geofences'] == 1 && array_key_exists($region_id, $proposal['geofences']['locations'])) { ?>
				<h5 style="font-weight:normal; margin-top:-12px; margin-left:10px;">Geofence Locations:</h5>
				<?php
				if(array_key_exists('proximity', $proposal['geofences']['locations'][$region_id]))
				{
					foreach($proposal['geofences']['locations'][$region_id]['proximity']['rows'] as $row)
					{
						echo '<blockquote>'.$row['search_term'].' - '.$row['radius'].' meter radius (proximity)'.'</blockquote>';
					}
				}
				if(array_key_exists('conquesting', $proposal['geofences']['locations'][$region_id]))
				{
					foreach($proposal['geofences']['locations'][$region_id]['conquesting']['rows'] as $row)
					{
						echo '<blockquote>'.$row['search_term'].' - '.$row['radius'].' meter radius (conquesting)'.'</blockquote>';
					}
				}
			}
		}
	}
	else
	{
	?>
		<blockquote><?php echo implode(', ', $proposal['geos'][0]['zips']); ?></blockquote>
		<?php if(array_key_exists('has_geofences', $proposal) && $proposal['has_geofences'] == 1 && array_key_exists(0, $proposal['geofences']['locations'])) { ?>
			<h5 style="font-weight:normal; margin-top:-12px; margin-left:10px;">Geofence Locations:</h5>
			<?php
			if(array_key_exists('proximity', $proposal['geofences']['locations'][0]))
			{
				foreach($proposal['geofences']['locations'][0]['proximity']['rows'] as $row)
				{
					echo '<blockquote>'.$row['search_term'].' - '.$row['radius'].' meter radius (proximity)'.'</blockquote>';
				}
			}
			if(array_key_exists('conquesting', $proposal['geofences']['locations'][0]))
			{
				foreach($proposal['geofences']['locations'][0]['conquesting']['rows'] as $row)
				{
					echo '<blockquote>'.$row['search_term'].' - '.$row['radius'].' meter radius (conquesting)'.'</blockquote>';
				}
			}
		}
	}
}

if($show_audience_info === true)
{
	if (!empty($political['parties']))
	{
		echo '
		<h4>Political Segments</h4>
		<ul>
			<li>Targeting ';
		foreach ($political['parties'] as $i => $party)
		{
			if ($i !== 0)
			{
				echo ', ';
			}
			echo $party;
		}
		echo ' Voters</li>';

		if (!empty($political['segments']))
		{
			foreach ($political['segments'] as $segment)
			{
				echo '<li>'. $segment .'</li>';
			}
		}

		echo '</ul>';
	}
?>
	<h4>Selected Demographics</h4>
	<p>
	<?php
	foreach($proposal['demographic_data'] as $property => $values)
	{
		echo '<label>' . $property . ':</label>';
		echo ' <strong>' . $values . '</strong><br>';
	}
	?>
	</p>

	<?php
	if(!empty($categories))
	{
	?>
	<h4>Selected Interests</h4>
	<ul>
	<?php
	foreach($categories as $category)
	{
		echo '<li>' . $category['tag_copy'] . '</li>';
	}
	?>
	</ul>
	<?php
	}
}

if($proposal['is_rooftops_dependent'] === true)
{
?>
	<h4>Selected Rooftops</h4>
	<ol>
	<?php
		foreach($rooftops as $i => $rooftop)
		{
			echo '<li>'.$rooftop['description'].'</li>';
		}
}

?>



<h4>Budget</h4>
<table cellpadding="0" cellspacing="20" border="0" width="100%">
	<thead>
		<tr>
			<td></td>
<?php
foreach($proposal['options'] as $option)
{
?>
			<td><?php echo $option['name']; ?></td>
<?php
}
?>
		</tr>
	</thead>
	<tbody>
<?php
// before discount
$num_products_before_discount = 0;
$num_products_after_discount = 0;
foreach($proposal['pricing'] as $product_index => $product)
{
	$num_products_before_discount++;
	if($product['after_discount'])
	{
		$num_products_after_discount++;
	} else {
		echo '<tr>';
		echo '<td>' . ($product['custom_name'] !== false ? $product['custom_name'] : $product['name']) . '</td>';
		foreach($product['options'] as $option_index => $pricing_product_options)
		{
			echo '<td>' . format_option_budget_line($pricing_product_options['total_budget'], $proposal['options'][$option_index]) . '</td>';
		}
		echo '</tr>' . "\n";
		if($product['options'][0]['has_geofence'] == 1)
		{
			echo '<tr>';
			echo '<td style="padding-left:10px;"><small>Geofence</small></td>';
			foreach($product['options'] as $option_index => $pricing_product_options)
			{
				echo '<td>';
				if(array_key_exists('geofencing_budget', $pricing_product_options))
				{
					echo format_option_budget_line($pricing_product_options['geofencing_budget'], $proposal['options'][$option_index], false);
				}
				echo '</td>';
			}
			echo '</tr>' . "\n";
			echo '<tr>';
			echo '<td style="padding-left:10px;"><small>Geotarget</small></td>';
			foreach($product['options'] as $option_index => $pricing_product_options)
			{
				echo '<td>';
				if(array_key_exists('ip_budget', $pricing_product_options))
				{
					echo format_option_budget_line($pricing_product_options['ip_budget'], $proposal['options'][$option_index], false);
				}
				echo '</td>';
			}
			echo '</tr>' . "\n";
			
		}
	}
}
if (!$proposal['no_discount'])
{
	if($num_products_before_discount > 1)
	{
		echo '<tr><td>Subtotal</td>';
		foreach($proposal['options'] as $option)
		{
			echo '<td>' . format_option_budget_line($option['pre_discount_cost'], $option) . '</td>';
		}
		echo '</tr>' . "\n";
	}

	echo '<tr style="color:green;"><td>'.$proposal['options'][0]['discount_name'].'</td>';
	foreach($proposal['options'] as $option)
	{
		echo '<td>-' . format_option_budget_line($option['total_monthly_absolute_discount'], $option) . '</td>';
	}
	echo '</tr>' . "\n";
}

echo '<tr><td>Subtotal</td>';
foreach($proposal['options'] as $option_index => $option)
{
	echo '<td>' . format_option_budget_line($option['total_cost'], $option['term']) . '</td>';
}
echo '</tr>' . "\n";

// after discount; assuming one-time, no term
foreach($proposal['pricing'] as $product_index => $product)
{
	if(!$product['after_discount']) continue;
	echo '<tr>';
	echo '<td>' . ($product['custom_name'] !== false ? $product['custom_name'] : $product['name']) .'</td>';
	foreach($product['options'] as $option_index => $pricing_product_options)
	{
		echo '<td>' . format_option_budget_line($pricing_product_options['budget'], 'one time') . '</td>';
	}
	echo '</tr>' . "\n";
}

echo '<tr><td>Grand Total</td>';
	foreach($proposal['options'] as $option)
	{
		echo '<td>' . format_option_budget_line($option['grand_total']) . '</td>';
	}
echo '</tr>' . "\n";
?>
	</tbody>
</table>
