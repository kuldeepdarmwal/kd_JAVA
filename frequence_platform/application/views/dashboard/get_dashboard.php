<?php
/*
echo '<h2>Tool Dashboard</h2>
/application/views/dashboard/get_dashboard.php <br />';
 */
?>

<?php
//echo 'TicketType: '.$TicketType;

$rows_ticketStatus = $result_ticketStatus->num_rows();

for($i=0;$i<$rows_ticketStatus;$i++)
{	
	$row = $result_ticketStatus->row($i);		

	$Status = $row->Status;
	$RoleType = $row->Role;
	$aRow = $row->row;
	$aColumn = $row->column;

	if($RoleType == 'OPS'){$style = 'Ops';}
	elseif($RoleType == 'SALES'){$style = 'Sales';}
	elseif($RoleType == 'CREATIVE'){$style = 'Creative';}
	else {die("Unknown role: ".$RoleType);}

	if((($i+1)% $maxColumn) == ($aColumn% $maxColumn))
	{
		$result_statuses = $statusesArray[$i];
		$rows_statuses = $result_statuses->num_rows();

		echo '<div class="status-container '.$style.' soft'.$style.'" id="'.$style.'-'.$i.'">';
		echo '<table cellspacing="0" class="sofT'.$style.'">';
		//$needle_array = array('CAMPAIGN','NETWORKS','SUBMITTED','INTERNAL');
		//$haystack_array = array('CMPN','NTWK','SBMTD','INTRNL');
		//str_replace ($needle_array,$haystack_array,$Status)
		echo '<tr><td class="helpHed'.$style.'">'.$Status.'</td></tr>';
		echo '</table>';
		
		echo '<div style="overflow:auto;">';
		echo '<table cellspacing="0" class="sofT'.$style.'">';

		echo '
			<tr>
				<td class="helpSubHed'.$style.'">Ticket</td> 
				<td class="helpSubHed'.$style.'">Global</td>
				<td class="helpSubHed'.$style.'">State</td>
			</tr>';		

		for($j=0;$j<$rows_statuses;$j++)
		{
			$subRow = $result_statuses->row($j);
		
			$today = date('Y-m-d H:i:s');

			$TicketName = $subRow->TicketName;
			$TicketID = $subRow->TicketID;
			$Date_Created = $subRow->Date_Created;
			$Date_Modified = $subRow->Date_State_Start;

			$global_master_dif = (strtotime($today)-strtotime($Date_Created))/(60*60*24);
			$state_master_dif = (strtotime($today)-strtotime($Date_Modified))/(60*60*24);		

			
			//TICKET NAME
			//TICKET TIMER
			echo '
				<tr onclick="popitup(\'/dashboard/ajax_viewTicket/TicketID/'.$TicketID.'\'); return false;" alt="'.$TicketName.'" title="OPEN: '.$TicketName.'">
					<td class="helpBod'.$style.'">
						'.substr($TicketName,0,30).'
					</td>
					<td class="helpBod'.$style.'">'.round($global_master_dif,1).'d</td>
					<td class="helpBod'.$style.'">'.round($state_master_dif,1).'d</td>
				</tr>';
		}
		echo '</table>';
		echo '</div>';
		echo '</div>';
	}
	else
	{
		$AddThisManyPods = ($aColumn% $maxColumn)-(($i+1)% $maxColumn);
		for($j=0;$j<$AddThisManyPods;$j++)
		{
			echo '<div class="status-container"></div>';
		}
		echo '<div class="status-container '.$style.' soft'.$style.'" id="'.$style.'-'.$i.'">';

		$result_statuses = $statusesArray[$i];
		$rows_statuses = $result_statuses->num_rows();
						
		echo '<table cellspacing="0" class="sofT'.$style.'">';
		$needle_array = array('CAMPAIGN','NETWORKS','SUBMITTED','INTERNAL');
		$haystack_array = array('CMPN','NTWK','SBMTD','INTRNL');
		echo '<tr><td class="helpHed'.$style.'">'.str_replace ($needle_array,$haystack_array,$Status).'</td></tr>';
		echo '</table>';
		
		echo '<table cellspacing="0" class="sofT'.$style.'">';
		echo '
			<tr>
				<td class="helpSubHed'.$style.'">Ticket</td>
				<td class="helpSubHed'.$style.'">Global</td>
				<td class="helpSubHed'.$style.'">State</td>
			</tr>';
		for($j=0;$j<$rows_statuses;$j++)
		{
			$subRow = $result_statuses->row($j);

			$today = date('Y-m-d H:i:s');
		
			$TicketName = $subRow->TicketName;
			$TicketID = $subRow->TicketID;
			$Date_Created = $subRow->Date_Created;
			$Date_Modified = $subRow->Date_State_Start;
			
			$global_master_dif = (strtotime($today)-strtotime($Date_Created))/(60*60*24);
			$state_master_dif = (strtotime($today)-strtotime($Date_Modified))/(60*60*24);
		
			echo '
				<tr onclick="popitup(\'dashboard/ajax_viewTicket/TicketID/'.$TicketID.'\'); return false;">
					<td class="helpBod'.$style.'"><a href="" name="" onclick="popitup(\'/dashboard/ajax_viewTicket/TicketID/'.$TicketID.'\'); return false;" >'.$TicketName.'</a><br /></td>
					<td class="helpBod'.$style.'">'.round($global_master_dif,1).'d</td>
					<td class="helpBod'.$style.'">'.round($state_master_dif,1).'d</td>
				</tr>';
		}
		echo '</table>';
		echo '</div>';
	}
}

?>
