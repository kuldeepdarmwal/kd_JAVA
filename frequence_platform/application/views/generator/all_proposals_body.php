<!DOCTYPE html>
<html>
<head>
<title>Open Saved Proposal</title>
   <style>
   html
   {      
	   padding: 10px 20px;
       -webkit-box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.3);
       -moz-box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.3);
       box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.3);
       font: 12px Lato, Arial, Helvetica, sans-serif;
   }
   table, #load_more_section
   {       
       margin-left: auto;
       margin-right: auto;
   }
   table
   {
	   width:95%;
	   background-color: #0969a4;
   }
   table, td, th
   {
       border: 1px solid #414142;
   }
   table th
   {
       font-size: 16px;
       background-color:#f6f6f6;
   }
   table td
   {
       background-color:#f6f6f6;
       height:30px;
   }
   table tr:hover td
   {
       background-color:#c0c1c2;
   }
   #load_more_section
   {
	   width: 485px;
	   font-size: 14px;
   }
   #load_more_section input
   {
	   width:50px;
	   border-radius: 2px;
	   padding: 2px;
   }
   #load_more_button
   {
	   background-color:#49afcd;
	   color:#ffffff;
	   padding:4px 12px;
	   font-size: 14px;
	   -webkit-border-radius: 4px;
	   -moz-border-radius: 4px;
	   border-radius: 4px;
	   border: 1px solid #cccccc;
	   margin-left: 5px;
   }
   </style>
</head>
<body>
<div id="load_more_section">
  <form action="" method="get">
  <span>Show: </span>
  <input type="text" name="limit" id="load_more_limit_input" value="<?php echo $limit; ?>">
  <span> Proposal(s) starting from # </span>
  <input type="text" name="start" id="load_more_start_input" value="<?php echo $start; ?>">
  <button type="submit" id="load_more_button">Show Proposals</button>
  </form>
</div>
<hr>
<table>
<tr><th> # </th><th>Proposal ID</th><th>Proposal Name</th><th>Last Modified</th><th>Export Site List</th><th>Export Region Details</th></tr>
<?php
$option_index = 0;
foreach($proposals as $v)
{
	if(date('I'))
    {
		$datey = date("F j, Y, g:i a",strtotime($v['date_modified']."-7 hours"));
    }
	else
    {
		$datey = date("F j, Y, g:i a",strtotime($v['date_modified']."-8 hours"));
    }
	$name = (!is_null($v['source_mpq'])) ? $v['prop_name']." (".$v['advertiser'].")" : $v['prop_name'];
	echo "<tr class='row' ><td class='start_number_td'>". $start++ ."</td><td onclick=\"window.open('/proposal_builder/option_engine/".$v['prop_id']."');\">".$v['prop_id']."</td>".
	"<td onclick=\"window.open('/proposal_builder/option_engine/".$v['prop_id']."');\">".$name."</td>".
	"<td onclick=\"window.open('/proposal_builder/option_engine/".$v['prop_id']."');\">".$datey."</td>".
	"<td><a href='/proposal_builder/export_sites/".$v['prop_id']."'>Get Sites</a></td>".
	"<td>";
	$j = 1;
	
	for($option_index; $option_index < count($options); $option_index++)
	{
		if($options[$option_index]['prop_id'] == $v['prop_id'])
		{
			echo "<a href='/proposal_builder/export_geos/".$v['prop_id']."/".$options[$option_index]['option_id']."'>".($options[$option_index]['option_name'] == '' ? 'Display Package '.$j : $options[$option_index]['option_name']) ."</a><br>";
			$j++;
		}
		else
		{
			break;
		}
	}
	if($j == 1)
	{
		echo "No options found";
	}
	echo "</td>";
	echo "</tr>";
}
?>
</table>
</body>
</html>
