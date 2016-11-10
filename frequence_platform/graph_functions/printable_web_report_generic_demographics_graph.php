<?php

//$field_names = $QueryResult->list_fields();
if($graphNumber == 109)
{
$field_names = array(
		0 => 'two',
		1 => 'three',
		2 => 'four',
		3 => 'five',
		4 => 'six',
		5 => 'seven',
		6 => 'eight',
		);
}
else if($graphNumber == 110)
{
$field_names = array(
		0 => 'two',
		1 => 'three',
		2 => 'four',
		3 => 'five',

		);
}
else if($graphNumber == 111)
{
$field_names = array(
		0 => 'two',
		1 => 'three',
		);
}
else if($graphNumber == 112)
{
$field_names = array(
		0 => 'two',
		1 => 'three',
		2 => 'four',
		);
}

	echo '
		google.load("visualization", "1", {packages:["corechart"]});

		google.setOnLoadCallback(draw);
		function draw() {
			var data = new google.visualization.DataTable();
			';

	echo 'data.addColumn( \'string\', \'\');';
	echo 'data.addColumn( \'number\', \'\');';

	if(isset($TotalColumns) && $TotalColumns> 0) {
		echo 'data.addRows('.$numDemographicColumnsToShow.');';

		for($ii=0; $ii<$numDemographicColumnsToShow; $ii += 1) {
			$tempValue = $QueryResult->row_array();
			//$cellValue = $tempValue[$field_names[$ii+$startingIndexOffset]] + $demographics[$ii];//mysql_result($QueryResult, 0, $ii+$startingIndexOffset);
			$cellValue = $demographics[$ii];
			$weightedPercentile = $cellValue;// / $totalImpressions;
			$formattedVariable = FormatPhpVariableForJavascript($weightedPercentile, 1);
			echo 'data.setValue('.$ii.', 0, \''.$columnNames[$ii].'\');';
			echo 'data.setValue('.$ii.', 1, '.$formattedVariable.');';
		}
	}
	else {
		echo 'data.addRows(1);';
		echo 'data.setValue(0, 0, \'none\');';
		echo 'data.setValue(0, 1, 0.45);';
	}

	if($graphType == 'verticalBarGraph') {
			echo 'var chart = new google.visualization.ColumnChart(document.getElementById(\''.$elementID.'\'));';
	}
	elseif($graphType == 'horizontalBarGraph') {
			echo 'var chart = new google.visualization.BarChart(document.getElementById(\''.$elementID.'\'));';
	}
	elseif($graphType == 'pieChart') {
			echo 'var chart = new google.visualization.PieChart(document.getElementById(\''.$elementID.'\'));';
	}
	else {
			die('graphType: '.$graphType.' unknown.');
	}

	echo 'var numberFormatter = new google.visualization.NumberFormat({groupingSymbol: \',\', fractionDigits: 0});'; // 2});';
	echo 'numberFormatter.format(data, 1);';

	echo '
		chart.draw(data, {
				width: '.$chart_width.',
				height: '.$chart_height.',
				vAxis:{minValue: 0, format: \'#,##0\'},
				hAxis:{minValue: 0, format: \'#,##0\'},
				title: \'\',
				legend: \'none\',
				smoothLine: false,
				fontSize: 10,
				fontName: \'Lato,Verdana\',
				titleTextStyle : {fontSize: 18},
				pointSize : 4,
				colors : '.$graphColors.'
				});
		}';

?>
