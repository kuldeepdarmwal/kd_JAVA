<?php 
/*
echo '
<h2>Tool Adgroup</h2>
/application/views/dashboard/tool_adgroup.php <br />
'.$tool; 
*/
?>

<?php
		echo '
	<h2>Link Adgroup</h2>
	<div class="VLForm" >
		<div class="selectionDropDown" >
			Business';
			echo '
			<select name="BusinessName" id="BusinessName" onchange="populateCampaignDropdown2(this.options[this.selectedIndex].value);return false;">
				<option value="">--Select a Business--</option>';
				$rows = $results->num_rows();
				for($i=0; $i<$rows; $i++) 
				{
					$cell = $results->row($i);
					echo '<option value="'.$cell->Name.'">'.$cell->Name.'</option>';
				}
			echo '	
				</select>
				</div>
				<div class="selectionDropDown" >
					<span id="campaignNameSpan" name="campaignNameSpan">
						Select A Business First
					</span>
				</div>
				<div class="selectionDropDown" >Network Unique ID
					<span id="ticketNameSpan" name="ticketNameSpan">
					</span>
				</div>
					<table border="0" style="width:100%">
		<tr>
			<td>
				<label name="label_IsRetargeting" >
					<input type="checkbox"  name="IsRetargeting" id="IsRetargeting" style="float:right;margin-right:-70px"/>
					RETARGETING? If Ticked, report will segregate retargting data.
				</label>
			</td>
		</tr>
		<tr>
			<td>
				<label name="label_IsDerivedSiteDateRequired" >
					<input type="checkbox"  name="IsDerivedSiteDateRequired" id="IsDerivedSiteDateRequired" style="float:right;margin-right:-70px"/>
					API: DERIVE SITE DATA? GDN autoplacement campaigns need this ticked
				</label>
			</td>
		</tr>
	</table>
</div>
<div id="submitButtonSpan">
	</div>
<div class="clearBoth" ></div>
<div class="content-options-wrapper">
	<div id="endOfAddBusinessForm">
	</div>
</div>
';

?>


