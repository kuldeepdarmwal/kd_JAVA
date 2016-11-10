<?php
	for($i=0;$i<$count;$i++)
	{
		$j = $i+1;
		echo '<span class="ticket-name"># '.$j.':</span> 
			<span class="ticket-object">
			<input name="uploadedfile'.$i.'" type="file" onchange="AJAX_UpdateIDInnerHTML(this.value.substring(12),\'upload_'.$i.'\');"/>
			</span>';
	}
?>
