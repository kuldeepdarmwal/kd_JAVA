<?php 
	$a_or_an = ($mpq_type == 'insertion order') ? 'an' : 'a';
?>

<?php
	if($submit_view === true)
	{
		echo '<div class="container container-body">'."\n";
		if(isset($mailgun_result) && is_array($mailgun_result))
		{
			echo '<div class="alert alert-error left-welling">';
			echo '<strong>Warning: We were unable to send an email to your provided email address.  Please contact us if you require a copy of your '.$mpq_type.'.</strong>';
			echo '</div>';
		}
	}
?>

	<h2>Hello <?php echo $requester->name; ?>!</h2>
	<div class="well left-welling">
		Your request for <?php echo $a_or_an." ".$mpq_type; ?> has been received and will be processed shortly.<br />
		Please ensure the following information regarding your <?php echo $mpq_type; ?> is correct.<br />
	</div>
	<div>
		<h3>Information About You:</h3>
		<ul class="well">
			<li><b>Name</b>: <?php echo $requester->name; ?></li>
			<li><b>Website</b>: <?php echo $requester->website; ?></li>
			<li><b>Email</b>: <?php echo $requester->email_address; ?></li>
			<li><b>Phone</b>: <?php echo $requester->phone_number; ?></li>
		</ul>
	</div>
	<div>
		<h3>Advertiser Information:</h3>
		<ul class="well">
			<li><b>Business Name</b>: <?php echo $advertiser->business_name; ?></li>
			<li><b>Website</b>: <?php echo $advertiser->website_url; ?></li>
		</ul>
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
				$num_zips = count($geo_mpq_data->regions);
				$row_count = 0;
				echo implode(', ', $geo_mpq_data->regions);
			?>
			</li>
		</ul>
		<?php
			if(!empty($notes_view))
			{
				echo "<img src='{$notes_snapshot->snapshot_data}' />";
			}
		?>
	</div>

	<?php if($channels){ ?>
		<div>
			<h3>Selected Channels:</h3>
			<ul class="well">
			<?php 
				foreach($channels as $v)
				{
					echo "<li>{$v->tag_copy}</li>";
				}
			?>
			</ul>
		</div>
	<?php } ?>
	<?php 
		$some_demos_chosen = false;
		$demo_section_string = 
		'<div>
			<h3>Selected Demographics:</h3>
			<ul class="well">
		';
		foreach($demographics as $k => $v)
		{
			if($v == 1)
			{
				$some_demos_chosen = true;
				$demo_section_string .= "<li>{$k}</li>";
			}
		}
		$demo_section_string .= 
		'	</ul>
		</div>
		';
		if($some_demos_chosen)
		{
			echo $demo_section_string;
		}
	?>

	<?php if($mpq_type == 'proposal'){ ?>
		<div>
			<h3>Proposal Options:</h3>
			<ul class="well">
			<?php
				foreach($option_data as $v)
				{
					$temp_s = '';
					if($v->duration > 1)
					{
						$temp_s = 's';
					}
					echo "<li>{$v->amount} {$v->type}s {$v->term} for {$v->duration} {$term_translate[$v->term]}{$temp_s}</li>";
				}
			?>
			</ul>
		</div>
	<?php }else{ ?>
		<div>
			<h3>Insertion Order Details:</h3>
		</div>
		<ul class="well">
		<?php 
			$temp_s = '';
			$duration = true;
			switch($insertion_order_data->term_type)
			{
				case "MONTH_END":
					$term_type = "monthly";
					break;
				case "BROADCAST_MONTHLY":
					$term_type = "monthly";
					break;
				case "FIXED_TERM":
					$term_type = "in total";
					break;
				default:
					$term_type = $insertion_order_data->term_type;
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
					$duration = "and ending on {$insertion_order_data->end_date}";
				}
				else
				{
					if($insertion_order_data->term_duration > 1)
					{
						$temp_s = 's';
					}
					$duration = "for {$insertion_order_data->term_duration} {$term_translate[$term_type]}{$temp_s}";
				}
			}
			echo "<li><b>Landing Page</b>: {$insertion_order_data->landing_page}</li>";
			echo '<li>' . number_format($insertion_order_data->num_impressions) . " impressions {$term_type} starting on {$insertion_order_data->start_date} {$duration}</li>";

			if($insertion_order_data->is_retargeting == '1')
			{
				echo '<li>Retargeting Included</li>';
			}

			if ($adset_request_id)
			{
				echo "<li class=\"adset_request_id\">Banner Intake Request {$adset_request_id}</li>";
			}
		?>
		</ul>
	<?php } ?>

	<div>
		<h3>Notes:</h3>
		<p clas="well left-welling"><?php echo $requester->notes; ?></p>
	</div>

	<div class="row">
		<div class="span12">
		<?php 

			if(empty($notes_view))
			{
				$ref_stub = "";
				if(!empty($cc_email))
				{
					$ref_stub = "/ref/{$cc_email}";
				}
				if(!empty($submit_view))
				{
					echo '<div class="well left-welling"><a href="' . base_url("mpq") . $ref_stub . '" class= "btn btn-info btn-block">Click here to fill out another media plan</a></div>';
				}
				else
				{
					echo '<a href="' . base_url("mpq") . $ref_stub. '">Click here to fill out another media plan</a><br><br><br>';
				}
			}
		?>
		</div>
	</div>

<?php
	if($submit_view === true)
	{
		echo "</div>\n";
	}
?>
