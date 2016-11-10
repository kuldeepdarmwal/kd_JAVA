<?php 
/*
echo '
<h2>Tool Ticket</h2>
/application/views/dashboard/tool_ticket.php <br />
';
echo $tool; 
*/
?>

<h2>Add a Ticket</h2>
<div class="VLForm" >
<?php
	//<form action="addTicket.php" method="post" enctype="multipart/form-data" >
	//<form action="/dashboard/add_ticket" method="post" enctype="multipart/form-data" >
?>
	<?php echo form_open_multipart('dashboard/add_ticket'); ?>
		<?php //echo '<input type="hidden" name="MAX_FILE_SIZE" value="20000000" />'; ?>

		<div class="VLFormRow">
			TICKET TYPE
			<select name="VLFORM_select_ticketType" id="VLFORM_select_ticketType">
				<?php
					$TheNumberOfTicketTypes = $resultTicketTypes->num_rows();
					for($i=0;$i<$TheNumberOfTicketTypes;$i++)
					{
						$ticketType = $resultTicketTypes->row($i)->TicketType;
						echo "<option value='".$ticketType."'>".$ticketType."</option>";
					}
				?>
			</select>
		</div>
		<div class="VLFormRow">
			TICKET NAME
			<input type="text" name="VLFORM_ticketName" id="VLFORM_ticketName" value="" maxlength="30">
		</div>

		<input type="hidden" name="VLFORM_select_display" value="100k">
		<input type="hidden" name="isCustomDisplayOption" value="no">
		<input type="hidden" name="VLFORM_checkbox_retargeting" value="true">
		<input type="hidden" id="numAdditionalUploads" name="numAdditionalUploads" value="0">

		<div class="VLFormRow">
			COMMENTS
			<textarea name="VLFORM_ticket_comments" id="VLFORM_ticket_comments" onfocus="this.value=''; this.onfocus=null;">Please Enter A Comment</textarea>
			<div class="clearBoth"></div>
		</div>

		<div class="VLFormRow">
			UPLOAD BRIEF 
			
			<input name="VLFORM_upload_Brief" type="file" />
		</div>
		<div class="VLFormRow">
			UPLOAD CAMPAIGN SETTINGS 
			
			<input name="VLFORM_upload_CampaignSettings" type="file" />
		</div>
		<div class="VLFormRow">
			UPLOAD CONTRACT 
			
			<input name="VLFORM_upload_Contract" type="file" />
		</div>
		<div class="VLFormRow">
			OTHER 

			<input name="VLFORM_upload_Other" type="file" />
		</div>

		<div class="VLFormRow">
			<span>Add more files</span>
			<select onchange="addMoreUploadSpots(this.value, 'uploads_list')" name="numberOfFilesBeingUploaded">
				<option value="0">0</option>
				<option value="1">1</option>
				<option value="2">2</option>
				<option value="3">3</option>
				<option value="4">4</option>
				<option value="5">5</option>
				<option value="6">6</option>
				<option value="7">7</option>
				<option value="8">8</option>
				<option value="9">9</option>
				<option value="10">10</option>
				<option value="12">12</option>
				<option value="15">15</option>
				<option value="20">20</option>
			</select>
		</div>

		<div class="VLFormDiv" id="uploads_list">
		</div>

		<div class="VLFormRow">
			<input type="submit" value="DONE"/>
		</div>
	</form>
</div>
<div class="formResults">

	<div id="VLFORM_formResults">
		
	</div>
</div>

