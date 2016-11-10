<?php
// -------------------------------------------
// -- Visits Per Day horizontal bar graph ----
// -------------------------------------------

include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 111;

$chartWidth = 700;
$chartHeight = 300;

$elementID = 'graph_'.$graphNumber.'_id';

$diff_secs = abs(strtotime($endDate) - strtotime($startDate));
$days = floor($diff_secs / (3600 * 24));
$actualDateRange = $days + 1;

$numDemographicColumnsToShow = 2;
$startingIndexOffset = 0;
$columnNames = array('Male','Female');

$mySqlGraphResponse = $graphResponse; 
$graphType = 'horizontalBarGraph'; 
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$graphColors = "['#7288a3']";

$impressions = $graphResponse->row()->imp;
//echo "IMPRESSIONS = " . $impressions . "<br>";
if($impressions == 0)
{
$demographics = array(
					0	=> .5,
					1	=> .5,
					);
}
else
{
$QueryResult = $mySqlGraphResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();

$totalimps = $totalGraphResponse->row()->totals;
//echo 'Total Imps = ' . $totalimps . '<br>';
$ptotalimps = $partialTotalGraphResponse->row()->ptotals;

//echo 'p total imps = ' . $ptotalimps . '<br>';
$maledecimal = ($QueryResult->row()->two/$impressions);
$femadecimal = ($QueryResult->row()->three/$impressions);

$lost_impressions = $totalimps - $impressions;//$ptotalimps;
/*
if(($maledecimal + $femadecimal) < 1)
{
	while(($maledecimal + $femadecimal) <= .999)
	{
		$maledecimal+= .0001;
		$femadecimal += .0001;
	}
}
*/


$demographics = array(
					0	=> $totalimps*$maledecimal,
					1	=> $totalimps*$femadecimal,
					);
}
/*
$demographics = array(
					0	=> $lost_impressions*$maledecimal,
					1	=> $lost_impressions*$femadecimal,
					);
*/

//$totalImpressionsColumn = 0;
//$totalImpressions = $QueryResult->row()->One;
?>

<html>
<head>
<link href='https://fonts.googleapis.com/css?family=Lato:400,700,900' rel='stylesheet' type='text/css' />
<link rel='stylesheet' type='text/css' href="<?php echo base_url('css/web_report_style.css');?>" />
<style type="text/css">
<?php
	echo '.graph_'.$graphNumber.'_wrapper{width:'.$chartWidth.'px;height:'.$chartHeight.'px;}';
	echo '.graph_'.$graphNumber.'_title{top:150px;left:6px}';

?>
</style>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

<script type="text/javascript">

<?php

	include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/printable_web_report_generic_demographics_graph.php';
?>
</script>
</head>
<body class="graph_body">

<div class="graph_<?php echo $graphNumber; ?>_wrapper">
 <!-- <div class="chart-title graph_<?php echo $graphNumber;?>_title">GENDER</div>-->
	<div class="chartBorder">
		<?php
		echo '
		<div id="'.$elementID.'"></div>
			';
		?>
	</div>
</div>
</body>
</html>
