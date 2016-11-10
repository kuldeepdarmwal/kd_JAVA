<?php			$on_behalf_of = '';
			if($mpq['mpq_core']['creator_email'] != null && $mpq['mpq_core']['creator_email'] != $mpq['mpq_core']['submitter_email'])
			{
				$on_behalf_of = '<strong>' . $mpq['mpq_core']['creator_name'] . '</strong> on behalf of ';
			}
?>
<table class="table table-bordered table-striped">
	<tr><td><span style="font-weight:bold;"><?php echo $mpq['mpq_core']['advertiser_name']; ?> : <?php echo $mpq['mpq_core']['order_name']; ?></span> submitted for review by <?php echo $on_behalf_of; ?><strong><?php echo ' '.$mpq['mpq_core']['owner_name'].' (' . $mpq['mpq_core']['submitter_email'] . ')'; ?></strong></td></tr>	
	<tr>
		<td>
			<ul>
				<?php if ($mpq['mpq_core']['order_id']) : ?><li>Order ID: <strong><?php echo $mpq['mpq_core']['order_id']; ?></strong></li><?php endif; ?>
				<?php if ($mpq['mpq_core']['unique_display_id']) : ?><li>External ID: <strong><?php echo $mpq['mpq_core']['unique_display_id']; ?></strong></li><?php endif; ?>
			</ul>
		</td>
	</tr>
	<tr><td><a href="<?php echo site_url('/io/'.$mpq['mpq_core']['unique_display_id']) ?>">Approve IO</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo site_url('/insertion_orders') ?>">View All IOs</a></td></tr>
</table>