<h2>Add a Business</h2>
<div class="VLForm" >
	<div class="selectionDropDown" >
		<span class="selectionName">Business Name</span><input type='text' name="business" id="business" />
	</div>
	<div class="selectionDropDown" >
		<span class="selectionName">Sales Person</span><select name="sales_person" id="sales_person" />
			<option value="">-- Select Sales Person --</option>
			<?php 
				$rows = $listOfSalesPeopleSqlResponse->num_rows();
				$columns = $listOfSalesPeopleSqlResponse->num_fields();
				for($i=0; $i<$rows; $i++) 
				{
					$row = $listOfSalesPeopleSqlResponse->row($i);
					$cell = $row->id;
					$cell_viewable = $row->firstname.' '.$row->lastname.' ('.$row->bgroup.')';
					echo '<option value="'.$cell.'">'.$cell_viewable.'</option>';
				}
			?>
		</select>
		<div class="clearBoth" ></div>
	</div><div class="clearBoth" ></div>
	<div class="selectionDropDown" >
	<input type="submit" value="+Campaign" name="addCampaign" onclick="continueWithFlow('addBiz',this.name); return false;" style="width:25%;height:40px;" />
		<input type='submit' onclick="addBusinessToDB()"value='Done!' style='width:25%;height:40px;' /></center>
	</div>
</div>
<div class="clearBoth" ></div>
<div class="content-options-wrapper">
	<div id="endOfAddBusinessForm">
	</div>
</div>
