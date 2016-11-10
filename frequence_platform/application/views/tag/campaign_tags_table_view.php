<?php

	if ($tags != NULL)
	{
		foreach($tags as $row)
		{		
			try
			{	
				echo ' <tr id="row_id_'.$row["id"].'"  onclick="">';
				echo ' 	<td style="font-size:13px">';
				echo 	$row["id"];
				echo ' 	</td>';
				echo ' 	<td class="tag_list_data">';
				echo '<a href="/tag/download_tag_file/'.$adv_name.'/'.$row["name"].'" >'.$row["name"].'</a>' ;
				echo ' 	</td>';
				echo ' </tr>';	
			}catch (Exception $e){
				echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		} 
	}	
	else 
	{
		echo ' <tr class="error" onclick="">';
		echo ' 	<td>';
		echo ' 	&nbsp';
		echo ' 	</td>';		
		echo ' 	<td >';
		echo ' 	No tag file present! ';
		echo ' 	</td>';
		echo ' </tr>';			

	}
?>