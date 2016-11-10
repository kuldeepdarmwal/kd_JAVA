<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">

	<link rel="shortcut icon" href="/images/favicon-1.ico">
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap.css">
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-responsive.css">
	<link rel="stylesheet" href="/libraries/external/font-awesome/css/font-awesome.css">

	<!-- mpq_component_functions.php -->
	<link rel="stylesheet" href="/bootstrap/assets/css/bootstrap-datetimepicker.min.css" />
	<link href="/libraries/external/fuelux/fuelux-responsive.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="/libraries/external/js/jquery-file-upload-9.5.7/css/jquery.fileupload.css"/>
	<link rel="stylesheet" href="/libraries/external/js/jquery-file-upload-9.5.7/css/jquery.fileupload-ui.css"/>
	<link href="/libraries/external/chardin/chardinjs.css" rel="stylesheet">
	<link rel="stylesheet" href="/assets/css/mpq/main.css" />

	<!-- geo_component_functions.php -->
	<link href="/libraries/external/select2/select2.css" rel="stylesheet"/>

	<style>
	  .left-welling{ margin-left:25px;}
	</style>

<body onload="">
	<?php
	if($submit_view === true)
	{
		echo '<div class="container container-body">'."\n";
		if(isset($mailgun_result) && is_array($mailgun_result))
		{
			echo '<div class="alert alert-error left-welling">';
			echo '<strong>Warning: We were unable to send an email to your provided email address.  Please contact us if you require a copy of your insertion order.</strong>';
			echo '</div>';
		}
	}
	?>

	<h2>Hello <?php echo $requester->name; ?>!</h2>
	<div class="well left-welling">
	  Your request for an insertion order has been received and will be processed shortly.<br />
	  Please ensure the following information regarding your insertion order is correct.<br />
	</div>
	<?php if($custom_regions != false) { ?>
	<div>
	  <h3>Targeted Geographies:</h3>
	  <ul class="well">
		<?php
		   foreach($custom_regions as $v)
		   {
			   echo "<li>";
			   echo $v['geo_name'];
			   echo "</li>". "\n";
		   }
		   ?>
	  </ul>
	</div>
	<?php } ?>
	<div>
	  <h3>Targeted Zip Codes:</h3>
	  <ul class="well">
		<li>
		  <?php
		  $num_zips = count($geo_mpq_data->rows);
		  $row_count = 0;
		  foreach($geo_mpq_data->rows as $v)
		  {
			  if($row_count == $num_zips - 1)
			  {
				  echo $v->id;
			  }
			  else
			  {
				  $row_count++;
				  echo $v->id. ', ';
			  }
		  }
		  ?>
		</li>
	  </ul>
		  <?php  
			if(isset($notes_view) AND $notes_view == true)
			{
				echo '<img src="'.$notes_snapshot->snapshot_data.'" />';
			}	 
			?>
	</div>
	<?php if($channels)
	{ ?>
	<div>
	  <h3>Selected Channels:</h3>
	  <ul class="well">
			
		<?php foreach($channels as $v)
			  {
				  echo "<li>".$v->tag_copy."</li>";
			  }
			  ?>
	  </ul>
	</div><?php } ?>
	<?php 
		$some_demos_chosen = FALSE;
		$demo_section_string = '<div>
								  <h3>Selected Demographics:</h3>
								  <ul class="well">';
		foreach($demographics as $k => $v)
		{
			if($v == 1)
			  {
				$some_demos_chosen = TRUE;
				$demo_section_string .= "<li>".$k."</li>";
			  }
		}
		$demo_section_string .= '</ul>
									</div>';
	if($some_demos_chosen) {
		echo $demo_section_string;
	 } ?>
	<div>
	  <h3>Insertion Order Details:</h3>
	</div>
	<ul class="well">
	  <?php 
				$temp_s = '';
				$duration = true;
				switch($insertion_order_data->term_type)
				{
				case "1":
					$term_type = "daily";
					break;
				case "7":
					$term_type = "weekly";
					break;
				case "30":
					$term_type = "monthly";
					break;
				case "-1":
					$term_type = "in total";
					break;
				default:
					$term_type = "in total";
					break;	
				}
				if($duration === true)
				{
					if($insertion_order_data->term_duration == '-1')
					{
						$duration = 'ongoing';
					}
					else if($insertion_order_data->term_duration == '0')
					{
						$duration = 'and ending on '.$insertion_order_data->end_date;
					}
					else
					{
						if($insertion_order_data->term_duration > 1)
						{
							$temp_s = 's';
						}
						$duration = 'for '.$insertion_order_data->term_duration.' '.$term_translate[$term_type].''.$temp_s;
							
					}
				}
				echo "<li><b>Landing Page</b>: ".$insertion_order_data->landing_page."</li>";
				echo "<li>".number_format($insertion_order_data->num_impressions)." impressions ".$term_type. " starting on ".$insertion_order_data->start_date." ".$duration."</li>";

				if($insertion_order_data->is_retargeting == '1')
				{
					echo "<li>Retargeting Included</li>";
				}

				if ($adset_request_id)
				{
					echo "<li>Banner Intake Request ".$adset_request_id;
				}
				?>
				</ul>
<div>
  <h3>Notes:</h3>
  <p class="well left-welling"><?php echo $requester->notes; ?></p>
</div>

<?php
if($submit_view === true)
{
	echo '</div>'."\n";
}
?>

<script type="text/javascript">
	window.parent.postMessage(<?php echo json_encode(array('frequence' => true, 'submission_id' => $insertion_order_id, 'success' => true)); ?>, "*");
</script>

</body>
</html>