<!DOCTYPE html>
<html>
	<body>
		<table style="border:1px solid black;">
			<thead>
				<tr>
<?php
foreach($campaigns[0] as $header => $cell)
{
	echo "<th style=\"border:1px solid black; text-align:left;\">".$header."</th>";
}
?>
				</tr>
			</thead>
			<tbody>
<?php
foreach($campaigns as $row)
{
	echo "<tr>";
	foreach($row as $cell)
	{
		echo "<td style=\"border:1px solid black;\">".$cell."</td>";
	}
	echo "</tr>";
}
?>
			</tbody>
		</table>

	</body>
	
</html>
