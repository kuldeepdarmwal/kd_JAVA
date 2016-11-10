<?php
// -------------------------------------------
// -- Visits Per Day horizontal bar graph ----
// -------------------------------------------

include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 110;

$chartWidth = 700;
$chartHeight = 300;

$elementID = 'graph_'.$graphNumber.'_id';

$diff_secs = abs(strtotime($endDate) - strtotime($startDate));
$days = floor($diff_secs / (3600 * 24));
$actualDateRange = $days + 1;

$numDemographicColumnsToShow = 4;
$startingIndexOffset = 0;
$columnNames = array('0 - 50K','50K - 100K','100K - 150K','150K \+');

$mySqlGraphResponse = $graphResponse; 
$graphType = 'horizontalBarGraph'; 
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$graphColors = "['#7288a3']";

$QueryResult = $mySqlGraphResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();

$impressions = $graphResponse->row()->imp;

if ($impressions == 0)
{
$demographics = array(
					0	=>	.25,
					1	=> 	.25,
					2	=> 	.25,
					3	=> 	.25,
					);
}
else
{
$totalimps = $totalGraphResponse->row()->totals;
$ptotalimps = $partialTotalGraphResponse->row()->ptotals;

$onedec = ($QueryResult->row()->two/$impressions);
$twodec = ($QueryResult->row()->three/$impressions);
$tredec = ($QueryResult->row()->four/$impressions);
$fordec = ($QueryResult->row()->five/$impressions);

/*
if(($onedec + $twodec + $tredec + $fordec) < 1)
{
	while(($onedec + $twodec + $tredec + $fordec) <= .999)
	{
		$onedec += .0001;
		$twodec += .0001;
		$tredec += .0001;
		$fordec += .0001;
	}
}
*/
//$onedec . $twodec . $tredec . $fordec . "<br>";
$lost_impressions = $totalimps - $impressions;//$ptotalimps;
$demographics = array(
					0	=>	$totalimps*$onedec,
					1	=> 	$totalimps*$twodec,
					2	=> 	$totalimps*$tredec,
					3	=> 	$totalimps*$fordec,
					);
}
/*
$demographics = array(
					0	=>	$lost_impressions*$onedec,
					1	=> 	$lost_impressions*$twodec,
					2	=> 	$lost_impressions*$tredec,
					3	=> 	$lost_impressions*$fordec,
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
  <!--<div class="chart-title graph_<?php echo $graphNumber;?>_title">INCOME</div>-->
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
