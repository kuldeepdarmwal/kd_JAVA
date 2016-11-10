<?php
function zips_download_function($zips_array, $ctr, $display)
{
	$output="&nbsp;&nbsp;&nbsp;&nbsp;<a onclick='create_excel(\"zips_ctr_$ctr\", \"$display\")' class='btn btn-small' title='Zips'>Zips <i class='icon-download-alt icon-white'></i></a>";
	$output.="<div style='overflow: hidden;visibility:hidden;height:1px' id='zips_ctr_$ctr'><table><tr><td>Country Code</td><td>5 Digit Zip Code</td></tr>";
	foreach ($zips_array as $row)
	{
		$output.="<tr><td>US</td><td>$row</td></tr>";
	}
	$output.="</table></div>";
	return $output;
}
?>
<style>
    #flights_body_table, #flights_body_table th td{
	border:1px solid #cacaca !important;
    }
    #flights_body_table th tr:first{
	background-color:#696666;
    }
</style>
<table class="table table-bordered table-striped">
	<tr><td>
	<h4>IO Details <span style="font-weight:normal; font-size: 12px; color: #9e9e9e; margin-left:45px;">ID: <?php echo ($mpq['mpq_core']['unique_display_id'] !== null ? $mpq['mpq_core']['unique_display_id'] : "None Available"); ?></span></h4>
<?php
$on_behalf_of = '';
if($mpq['mpq_core']['creator_email'] != null && $mpq['mpq_core']['creator_email'] != $mpq['mpq_core']['submitter_email'])
{
	$on_behalf_of = '<strong>' . $mpq['mpq_core']['creator_name'] . '</strong> on behalf of ';
}
?>
<p>Submitted by <?php echo $on_behalf_of; ?><strong><?php echo ' '.$mpq['mpq_core']['owner_name'].' (' . $mpq['mpq_core']['submitter_email'] . ')'; ?></strong></p>
<ul>
	<?php if ($mpq['mpq_core']['advertiser_name']) : ?><li>Advertiser Name: <strong><?php echo $mpq['mpq_core']['advertiser_name']; ?></strong></li><?php endif; ?>
	<?php if ($mpq['mpq_core']['order_id']) : ?><li>Order ID: <strong><?php echo $mpq['mpq_core']['order_id']; ?></strong></li><?php endif; ?>
	<?php if ($mpq['mpq_core']['order_name']) : ?><li>Order Name: <strong><?php echo $mpq['mpq_core']['order_name']; ?></strong></li><?php endif; ?>
	<?php if ($mpq['mpq_core']['advertiser_website']) : ?><li>Advertiser Website: <strong><?php echo $mpq['mpq_core']['advertiser_website']; ?></strong></li><?php endif; ?>
	<?php if ($mpq['mpq_core']['industry_name']) : ?><li>Advertiser Industry: <strong><?php echo $mpq['mpq_core']['industry_name']; ?></strong></li><?php endif; ?>

</ul>
</td></tr></table>

<table class="table table-bordered table-striped"><tr><td>
<h4>Selected Channels</h4>
<?php
$categories=$mpq['iab_data'];
if(!empty($categories))
{
?>

<ul>
<?php
	foreach($categories as $category)
	{
		echo '<li>' . $category['name'] . '</li>';
		if (array_key_exists('subheaders', $category))
		{
			foreach($category['subheaders'] as $sub_category)
			{
				echo '- ' . $sub_category['name'] . '<br>';
			}
		}
	}
?>
</ul>
<?php
}
else
{
	echo "No Channels found";
}
?>
</td></tr></table>

<table class="table table-bordered table-striped"><tr><td>
<h4>Selected Demographics</h4>
<p>
<?php
foreach($mpq['demographic_data'] as $property => $values)
{
	echo '' . $property . ': ';
	echo ' <strong>' . $values . '</strong><br>';
}
?>
</p>
</td></tr></table>

<?php
	if ($mpq['mpq_core']['notes'] != null && $mpq['mpq_core']['notes'] != '')
	{
?>
<table class="table table-bordered table-striped"><tr><td>
<h4>Notes</h4>
<?php
	echo '<xmp>'.str_replace(array("</xmp>", "<br/>", "<br>", "<br />"), array("&lt;/xmp&gt;", "\r\n", "\r\n", "\r\n"), $mpq['mpq_core']['notes']).'</xmp>';
?>
</td></tr></table>
<?php
	}
?>

<h4>Targeted Locations</h4>

<?php
if(count($mpq['geos']) > 0)
{
	$ctr=0;
	foreach($mpq['geos'] as $region)
	{
?>
		<table class="table table-bordered table-striped"><tr><td><h4>Location: <?php echo ++$ctr . " : ".  $region['name']; ?></h4></td></tr>
		<tr><td>Zips: <?php if ($mode == '0' && $role == "admin") { echo zips_download_function($region['zips'], $ctr, $region['name']); } ?><blockquote><?php echo implode(', ', $region['zips']); ?></blockquote></td></tr>
<?php
	if(isset($region['geofence_points']) && !empty($region['geofence_points']))
	{
		echo '<tr><td>Geofence Locations: <blockquote>';
		foreach($region['geofence_points'] as $geofence_point_data)
		{
			echo "{$geofence_point_data['search_term']} - {$geofence_point_data['radius']} meter radius ({$geofence_point_data['type']})<br>";
		}
		echo '</blockquote></td></tr>';
	}
?>
<?php
		foreach($region['products'] as $product)
		{
?>
			<tr><td><h5>PRODUCT: <?php echo $product['friendly_name']; ?></h5>
			<h5>FLIGHTS: </h5>
			<span><?php echo $product['time_series']; ?></span>
			<h5>CREATIVES: </h5>
			<span><?php
			foreach ($product['mpq_creatives'] as $mpq_creatives)
			{

				if ($mode == '0')
				{
					 echo "<a href='/crtv/get_adset/".$mpq_creatives['base_64_id']. "' target='_blank'>".$mpq_creatives['text'] . "</a> ";
					 if ($role == "admin")
					 {
					 	 echo " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='/creative_uploader/".$mpq_creatives['cup_id']."' target='_blank'>View in Creative Uploader</a>";
					 }
				}
				else
				{
					echo $mpq_creatives['text'];
					echo "<br>";
					echo "<a href='".$base_url."crtv/get_adset/". $mpq_creatives['base_64_id'].'/'.$mpq_creatives['vanity_string']."' target='_blank'>".$base_url."crtv/get_adset/". $mpq_creatives['base_64_id'].'/'.$mpq_creatives['vanity_string']."</a> ";
				}
				echo "<br>";
			}
			?>
			</span>
			<?php
			if($product['o_o_enabled'] && $io_submit_allowed)
			{
				if(!empty($product['o_o_data']['dfp_advertiser_id']) || !empty($product['o_o_data']['dfp_orders']))
				{
			?>
				<h5>OWNED & OPERATED</h5>

				<span>
			<?php
					if(!empty($product['o_o_data']['dfp_advertiser_id']))
					{
						echo "DFP Advertiser ID: ".$product['o_o_data']['dfp_advertiser_id']."<br>";
					}
					if(!empty($product['o_o_data']['dfp_orders']))
					{
						echo "DFP Order: ";
						foreach($product['o_o_data']['dfp_orders'] as $order) 
						echo '<a target="_blank" href="'.$order['order_url'].'">'.$order['id'].'</a> ';
					}
				}
			}
			?></span></td></tr>
<?php
		}
?>
	</table>
<?php
	}
}
?>
<table class="table table-bordered table-striped"><tr><td>
    <h5>TRACKING</h5>
    <span>
    <?php
        echo 'Retargeting : ';
        if(isset($mpq['mpq_core']['include_retargeting']))
        echo $mpq['mpq_core']['include_retargeting'] ? 'Enabled' : 'Disabled';
    ?>
    </span>
</td></tr></table>

<script type="text/javascript" src="/js/csv_table_create.js"></script>
