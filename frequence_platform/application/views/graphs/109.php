<?php
// -------------------------------------------
// -- Visits Per Day horizontal bar graph ----
// -------------------------------------------

include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 109;

$chartWidth = 700;
$chartHeight = 300;

$elementID = 'graph_'.$graphNumber.'_id';

$diff_secs = abs(strtotime($endDate) - strtotime($startDate));
$days = floor($diff_secs / (3600 * 24));
$actualDateRange = $days + 1;

$numDemographicColumnsToShow = 7;
$startingIndexOffset = 0;
$columnNames = array('Under 18', '18 - 24', '25 - 34', '35 - 44', '45 - 54', '55 - 64', '65 \+');

$mySqlGraphResponse = $graphResponse; 
$graphType = 'horizontalBarGraph'; 
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$graphColors = "['#7288a3']";

$QueryResult = $mySqlGraphResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();
//echo $TotalRows . " " . $TotalColumns . "<br>";
//$totalImpressions = mysql_result($QueryResult, 0, $totalImpressionsColumn);

$impressions = $graphResponse->row()->imp;

if($impressions == 0)
{
$demographics = array(
					0	=>	.166,
					1	=> 	.166,
					2	=> 	.166,
					3	=> 	.166,
					4	=> 	.166,
					5	=> 	.166,
					6	=> 	.166,
					);
}
else
{
$totalimps = $totalGraphResponse->row()->totals;
$ptotalimps = $partialTotalGraphResponse->row()->ptotals;

$lost_impressions = $totalimps - $impressions;//$ptotalimps;

$onedec = ($QueryResult->row()->two/$impressions);
$twodec = ($QueryResult->row()->three/$impressions);
$tredec = ($QueryResult->row()->four/$impressions);
$fordec = ($QueryResult->row()->five/$impressions);
$fvedec = ($QueryResult->row()->six/$impressions);
$sixdec = ($QueryResult->row()->seven/$impressions);
$svndec = ($QueryResult->row()->eight/$impressions);
//	$total = $onedec + $twodec + $tredec + $fordec + $fvedec + $sixdec + $svndec;
//		echo " '" . $total . "' <br>";
/*
if(($onedec + $twodec + $tredec + $fordec + $fvedec + $sixdec + $svndec) < 1)
{
	while(($onedec + $twodec + $tredec + $fordec + $fvedec + $sixdec + $svndec) <= .999)
	{
	$total = $onedec + $twodec + $tredec + $fordec + $fvedec + $sixdec + $svndec;
		echo " '" . $total . "' ";
		$onedec += .0001;
		$twodec += .0001;
		$tredec += .0001;
		$fordec += .0001;
		$fvedec += .0001;
		$sixdec += .0001;
		$svndec += .0001;
	}
}
*/
/*
$demographics = array(
					0	=>	$lost_impressions*$onedec,
					1	=> 	$lost_impressions*$twodec,
					2	=> 	$lost_impressions*$tredec,
					3	=> 	$lost_impressions*$fordec,
					4	=> 	$lost_impressions*$fvedec,
					5	=> 	$lost_impressions*$sixdec,
					6	=> 	$lost_impressions*$svndec,
					);
					*/
					
$demographics = array(
					0	=>	$totalimps*$onedec,
					1	=> 	$totalimps*$twodec,
					2	=> 	$totalimps*$tredec,
					3	=> 	$totalimps*$fordec,
					4	=> 	$totalimps*$fvedec,
					5	=> 	$totalimps*$sixdec,
					6	=> 	$totalimps*$svndec,
					);
}
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
  <!--<div class="chart-title graph_<?php echo $graphNumber;?>_title">AGE</div>-->
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
