<?php

include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 112;

$chartWidth = 700;
$chartHeight = 300;

$elementID = 'graph_'.$graphNumber.'_id';

$diff_secs = abs(strtotime($endDate) - strtotime($startDate));
$days = floor($diff_secs / (3600 * 24));
$actualDateRange = $days + 1;

$numDemographicColumnsToShow = 3;
$startingIndexOffset = 0;
$columnNames = array('No College','College','Graduate');

$mySqlGraphResponse = $graphResponse; 
$graphType = 'horizontalBarGraph'; 
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$graphColors = "['#7288a3']";

$QueryResult = $mySqlGraphResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();



$impressions = $graphResponse->row()->imp;
if($impressions == 0)
{
$demographics = array(
					0	=>	.33,
					1	=> 	.33,
					2	=> 	.33,
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
/*
if(($onedec + $twodec + $tredec) < 1)
{
	while(($onedec + $twodec + $tredec) <= .999)
	{
		$onedec += .0001;
		$twodec += .0001;
		$tredec += .0001;
	}
}*/
$demographics = array(
					0	=>	$totalimps*$onedec,
					1	=> 	$totalimps*$twodec,
					2	=> 	$totalimps*$tredec,
					);
}
/*
$demographics = array(
					0	=>	$lost_impressions*$onedec,
					1	=> 	$lost_impressions*$twodec,
					2	=> 	$lost_impressions*$tredec,
					);
*/
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
