<?php
	echo "<span class='selectionName'>Campaign: </span>";
	
	if($iscmpn == 'false')
	{
		echo "<select id='Campaign' name='Campaign' onchange=populateInputTextBox2()>";
	}
	else
	{
		echo "<select id='Campaign' name='Campaign' onchange=populateInputTextBox()>";
	}
	
	echo '<option value="">--Select a Campaign--</option>';
	$rows = $results->num_rows();
	for($i=0; $i<$rows; $i += 1) {
		$cell = $results->row($i);
		echo '<option value="'.$cell->Name.'">'.$cell->Name.'</option>';
	}
	echo "</select>";
?>
