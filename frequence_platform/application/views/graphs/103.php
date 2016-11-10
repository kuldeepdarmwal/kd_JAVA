<?php
assert_options(ASSERT_BAIL, true);
include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 103;

//$businessName = 'Almaden Valley Athletic Club';//$_GET['businessName'];
//$campaignName = 'AVAC Swim';//$_GET['campaignName'];
//$startDate = '2011-12-15';//$_GET['startDate'];
//$endDate = '2012-01-15';//$_GET['endDate'];
$chartWidth = 810;
$chartHeight = 268;

$elementID = 'graph_'.$graphNumber.'_id';

$mySqlGraphResponse = $graphRowsResponse;
$graphType = 'verticalBarGraph'; 
$titles = array(new TitleTextAndPosition('dates',16),new TitleTextAndPosition('ad views',204));
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$otherValue = 0;
$graphColors = "['#f17456', '#7288a3']";
$isDate = true;
$isLogScale = false;
$numDecimalPlaces = 2;
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
	echo '.graph_'.$graphNumber.'_title{}';
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
<div class="graph_<?php echo $graphNumber; ?>_wrapper">
	<!--<div class="chart-title graph_<?php echo $graphNumber;?>_title">VISITS / DAY </div>-->
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
