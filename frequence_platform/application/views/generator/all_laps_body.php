<?php
echo '<!DOCTYPE html>';
?>
<html>
<head>
	<title>All LAPs</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url('css/smb/media_targeting.css'); ?>"/>   
	
<script type="text/javascript">


</script>

</head>
<body>

<?php
echo '<button type="button" onClick="window.open(\'/proposal_builder/force_session/0\');">Create New Ad Plan</button>';
?>

<table id="site_pack_table" name="site_pack_table" class="mt_site_pack_table">
	<tr>
		<td class="mt_site_pack_header" colspan="6">
		AVAILABLE LOCAL AD PLANS
		</td>
	</tr>
	<tr class="mt_column_headers">
		<td class="mt_other_headers">
			ID	
		</td>
		<td class="mt_other_headers">
			PLAN NAME
		</td>
		<td class="mt_other_headers">
			ADVERTISER
		</td>
		<td class="mt_other_headers">
			DATE CREATED	
		</td>
		<td class="mt_other_headers">
			DATE LAST MODIFIED
		</td>
		<td class="mt_other_headers">
			SUGGESTED IMPRESSIONS
		</td>	
	</tr>
	<?php
		foreach($all_laps_data as $row) 
		{
			echo '<tr class="mt_site_pack_row" onClick="window.open(\'/proposal_builder/force_session/'.$row['lap_id'].'\');">
					<td>'.$row['lap_id'].'</td>
					<td>'.$row['plan_name'].'</td>
					<td>'.$row['advertiser'].'</td>
					<td>'.$row['date_created'].'</td>
					<td>'.$row['lap_save_time'].'</td>
					<td>'.$row['recommended_impressions'].'</td>
				</tr>';
		}
	?>
</table>


