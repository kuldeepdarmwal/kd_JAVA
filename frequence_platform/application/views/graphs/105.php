<?php
// -------------------------------------------
// -- Visits Per Day horizontal bar graph ----
// -------------------------------------------


include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';

$graphNumber = 105;

$chartWidth = 760;
$chartHeight = 604;

$elementID = 'graph_'.$graphNumber.'_id';


$limitValue0 = $partialTotalGraphResponse->row()->One;//mysql_result($partialTotalGraphResponse, 0, 0); 
$grandTotal0 = $totalGraphResponse->row()->One;//mysql_result($totalGraphResponse, 0, 0); 
$otherValue0 = $grandTotal0 - $limitValue0;
$otherValue = array(0 => $otherValue0);

$mySqlGraphResponse = $graphResponse; 
$graphType = 'horizontalBarGraph'; 
$titles = array(new TitleTextAndPosition('dates',16),new TitleTextAndPosition('ad views',204));
$chart_width = $chartWidth; 
$chart_height = $chartHeight; 
$otherValue = $otherValue;
$graphColors = "['#7288a3']";
$isDate = false;
$isLogScale = true;
$numDecimalPlaces = 2;
$numVisibleRows = $rankLimit;

$QueryResult = $mySqlGraphResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();;

?>

<html>
<head>
<link href='https://fonts.googleapis.com/css?family=Lato:400,700,900' rel='stylesheet' type='text/css' />
<link rel='stylesheet' type='text/css' href="<?php echo base_url('css/web_report_style.css');?>" />
<style type="text/css">
<?php
	echo '.graph_'.$graphNumber.'_wrapper{width:'.$chartWidth.'px;height:'.$chartHeight.'px;}';
	echo '.graph_'.$graphNumber.'_title{top:60px;left:140px}';

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
  <!--<div class="chart-title graph_<?php echo $graphNumber;?>_title">AD VIEWS PER AD PLACEMENT</div>-->
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
