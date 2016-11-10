<h2>Add a Campaign</h2>
<div class="VLForm" >
	<div class="selectionDropDown" >
		Business Name
<?php

	if($defaultBusiness != false)
	{
		//die('"tool_campaign.php: Expected to never recieve $DataString paramaeter');

		echo '<select name="business" id="business">';
		echo '<option value="'.$defaultBusiness.'">'.stripslashes ($defaultBusiness).'</option>';
		echo "</select>";
	}
	else
	{
		echo '<select name="business" id="business">';
		echo "<option value=''>-- Select Business --</option>";

		$rows = $results->num_rows();
		for($i=0; $i<$rows; $i++) 
		{
			$responseCell = $results->row($i);//mysql_result($results,$i,0).' '.mysql_result($results,$i,1);
			$optionCell = $responseCell->Name;
			echo '<option value="'.$optionCell.'">'.$optionCell.'</option>';
		}

		echo '</select>';
	}
		?>
		
	</div>
	<div class="selectionDropDown" >
		Campaign Name: <input type="text" name="Campaign" id="Campaign"/>
	</div>
	<div class="selectionDropDown" >
		Landing Page: <input type="text" name="LandingPage" id="LandingPage"/>
	</div>
		<input type="submit" onClick="continueWithFlow('addCampaign',this.name);"value="&infin;AdGroup" name='adgroup'/>
		<input type='submit' onclick="addCampaignToDatabase()" value='Done!'/>
</div>
<div class="content-options-wrapper">
	<div id="endOfAddBusinessForm">
	</div>
</div>
