<?php

include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 108;

$chartWidth = 760;
$chartHeight = 338;

$elementID = 'graph_'.$graphNumber.'_id';


$limitValue0 = $partialTotalGraphResponse->row()->One;
$grandTotal0 = $totalGraphResponse->row()->One;
$otherValue0 = $grandTotal0 - $limitValue0;
$otherValue = array(0 => $otherValue0);

$mySqlGraphResponse = $graphResponse; 
$graphType = 'pieChart'; 
$titles = array(new TitleTextAndPosition('dates',16),new TitleTextAndPosition('ad views',204));
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$otherValue = $otherValue;
$graphColors = "['#7288a3', '#f17456', '#728a74', '#94ae95', '#b7d1b8', '#b74634', '#d95b44', '#f17456']";
$isDate = false;
$isLogScale = false;
$numDecimalPlaces = 0;
$numVisibleRows = $rankLimit;

$QueryResult = $mySqlGraphResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();

?>

<html>
<head>
<link href='https://fonts.googleapis.com/css?family=Lato:400,700,900' rel='stylesheet' type='text/css' />
<link rel='stylesheet' type='text/css' href="<?php echo base_url('css/web_report_style.css');?>" />
<style type="text/css">
<?php
	echo '.graph_'.$graphNumber.'_wrapper{width:'.$chartWidth.'px;height:'.$chartHeight.'px;}';
	echo '.graph_'.$graphNumber.'_title{top:20px;left:80px}';

?>
</style>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

<script type="text/javascript">

<?php
include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/printable_report_generic_retargeting_graph.php';
?>
</script>
</head>
<body class="graph_body">
<!--<div class="graph_<?php echo $graphNumber; ?>_wrapper">
  <div class="chart-title graph_<?php echo $graphNumber;?>_title">AD VIEWS % PER CITY</div> -->
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
