<?php
	echo '<label for="campaigns"></label>';
	echo '<select name="campaigns" id="select">';
	$count = 0;
	foreach($campaigns as $xprinter)
	{
	$count++;
		//echo $xprinter['Name'];
		echo '<option value="' .$xprinter['Name']. '">' .$xprinter['Name']. '</option>';
	}
	if ($count > 1)
	{
		echo '<option value=".*">All Campaigns</option>';
	}
?>