<?php
assert_options(ASSERT_BAIL, true);
include $_SERVER['DOCUMENT_ROOT'].'/graph_functions/common_php_functions.php';


$graphNumber = 101;

 //$businessName = 'Western Athletic Clubs'; // Passed as a parameter to this view.
 //$campaignName = '.*'; // Passed as a parameter to this view.
 //$startDate = date("Y-m-d", strtotime("-1 month", strtotime("-2 days"))); // Passed as a parameter to this view.
 //$endDate = date("Y-m-d", strtotime("-2 days"));// Passed as a parameter to this view.
$chartWidth = 200;
$chartHeight = 700;

$elementID = 'graph_'.$graphNumber.'_id';

$diff_secs = abs(strtotime($endDate) - strtotime($startDate));
$days = floor($diff_secs / (3600 * 24));
$actualDateRange = $days + 1;

// constants
$sqlNameColumnIndex = 0;
$sqlImpressionsColumnIndex = 1;
$sqlClicksColumnIndex = 2;

$jsNameColumnIndex = 0;
$jsImpressionsColumnIndex = 1;
$jsClicksColumnIndex = 2;
?>

<html>
<head>
<title>Graph <?php echo $graphNumber.''; ?></title>

<link href='https://fonts.googleapis.com/css?family=Lato:400,700,900' rel='stylesheet' type='text/css' />
<link rel='stylesheet' type='text/css' href="<?php echo base_url('css/web_report_style.css');?>" />
<style type="text/css">
<?php
	echo '.graph_'.$graphNumber.'_wrapper{width:'.$chartWidth.'px;height:'.$chartHeight.'px;}';
?>
</style>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

<script type="text/javascript">
<?php
$mySqlGraphResponse = $impressionsAndClicksColumnsResponse;
$TotalRows = $mySqlGraphResponse->num_rows();
$TotalColumns = $mySqlGraphResponse->num_fields();
$QueryResult = $mySqlGraphResponse;
foreach($QueryResult->result() as $row)
{
	//echo $row->TotalImpressions . ' ';
}
$graphType = 'table';
$graphId = 'page1_impressionsAndClicksTable';
$otherValues = array(0 => 0, 1 => 0);
$graphColors = "['#FEBE10', '#0C4977']";
$isDate = true;
$isLogScale = false;
$numDecimalPlaces = 0;
$numVisibleRows = $actualDateRange+ 2;

echo 'google.load("visualization", "1", {packages:["table"]});';
echo '
		google.setOnLoadCallback(draw);
		function draw() {
			var data = new google.visualization.DataTable();
';
echo 'data.addColumn( \'string\', \'\');';
echo 'data.addColumn( \'number\', \'\');';
echo 'data.addColumn( \'number\', \'\');';
echo "\n";
$sumimp = 0;
$sumclick = 0;
$jsDataTableRowIndex = 0;
if(isset($TotalRows) && $TotalRows > 0) 
{
	$sqlResultRows = $TotalRows;
	//echo '// $sqlResultRows: '.$sqlResultRows."\n";
	$numJsRowsToRemove = 0;

	echo 'data.addRows('.$numVisibleRows.');'."\n";

	//$numDataRows = $numVisibleRows * 2;
	$sqlResultIndex = 0;
	
	// fill in missing values
	if($sqlResultRows > 0) 
	{
		$startingDatabaseRowDateName = $QueryResult->row($sqlResultIndex)->daterange;
		//echo '// $startDatabaseRowDateName: '.$startingDatabaseRowDateName."\n";
		echoMissingIntermediateDates($startDate, $startingDatabaseRowDateName, $jsDataTableRowIndex);
	}
	for($sqlResultIndex=0; 
			$sqlResultIndex < $sqlResultRows;
			//$sqlResultIndex < $numDataRows; // $sqlResultRows is at most $numDataRows.
			$sqlResultIndex += 1) 
	{
		$currentRowDateName = $QueryResult->row($sqlResultIndex)->daterange;
		//echo '// $currentRowDateName: '.$currentRowDateName."\n";

		// Display this row.
		$formattedRowName = FormatPhpVariableForJavascript($currentRowDateName);
		echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
		$impressionsRowName = $QueryResult->row($sqlResultIndex)->TotalImpressions;
		$formattedImpressionsRowName = FormatPhpVariableForJavascript($impressionsRowName);
		echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsImpressionsColumnIndex .', '. $formattedImpressionsRowName .');';
		$clicksRowName = $QueryResult->row($sqlResultIndex)->TotalClicks;
		$formattedClicksRowName = FormatPhpVariableForJavascript($clicksRowName);
		echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsClicksColumnIndex .', '. $formattedClicksRowName .');';
		echo "\n";
		$sumimp += $impressionsRowName;
		$sumclick += $clicksRowName;
		$jsDataTableRowIndex += 1;
		
		// fill in missing values
		$lookAheadIndex = $sqlResultIndex + 1;
		if($lookAheadIndex < $sqlResultRows) {
			$lookAheadDateName = $QueryResult->row($lookAheadIndex)->daterange;
			$nextSequentialDateName = date("Y-m-d", strtotime("+1 day", strtotime($currentRowDateName)));
			echoMissingIntermediateDates($nextSequentialDateName, $lookAheadDateName, $jsDataTableRowIndex);
		}
	}

	assert('$sqlResultIndex == $sqlResultRows');
	$lastSqlResultDateName = $QueryResult->row($sqlResultRows - 1)->daterange;
	$afterLastSqlResultDateName = date("Y-m-d", strtotime("+1 day", strtotime($lastSqlResultDateName)));
	if(strtotime($afterLastSqlResultDateName) <= strtotime($endDate))
	{
		$endDatePlus1 = date("Y-m-d", strtotime("+1 day", strtotime($endDate)));
		echoMissingIntermediateDates($afterLastSqlResultDateName, $endDatePlus1, $jsDataTableRowIndex);
	}

	//assert('$jsDataTableRowIndex >= $numVisibleRows');
}
else
{
	echo 'data.addRows(1);';
	echo 'data.setValue('.'0'.', '. $jsNameColumnIndex 				.', \''.date("Y-m-d").'\');';
	echo 'data.setValue('.'0'.', '. $jsImpressionsColumnIndex .', '.'0'.');';
	echo 'data.setValue('.'0'.', '. $jsClicksColumnIndex 			.', '.'0'.');';
}

	$word = 'SUM';
	$formattedsumimp = FormatPhpVariableForJavascript($sumimp);
	$formattedsumclick = FormatPhpVariableForJavascript($sumclick);
	$formattedsumname = FormatPhpVariableForJavascript($word);
	echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '. $formattedsumname .');';
	echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsImpressionsColumnIndex .', '. $formattedsumimp .');';
	echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsClicksColumnIndex .', '. $formattedsumclick .');';
echo 'var numberFormatter = new google.visualization.NumberFormat({groupingSymbol: \',\', fractionDigits: 0});';
for($j=1; $j <= 2; $j += 1) {
	echo 'numberFormatter.format(data, '.$j.');';
}

echo 'var chart = new google.visualization.Table(document.getElementById(\''.$elementID.'\'));';

echo 'chart.draw(data, {
				width: '.$chartWidth.', height: '.$chartHeight.',
				allowHtml: true,
				cssClassNames: {
					tableRow: \'tableRowClass\',
					oddTableRow: \'tableOddRowClass\',
					headerRow: \'tableHeaderClass\',
					hoverTableRow: \'tableRowClass\',
					selectedTableRow: \'tableRowClass\'
				}
			});';
echo '}';
?>
</script>
</head>
<body>


<div class="graph_<?php echo $graphNumber; ?>_wrapper">
	<div class="table_<?php echo $graphNumber; ?>_date_title">DATE</div>
	<div class="table_<?php echo $graphNumber; ?>_ad_views_title">ADVIEWS</div>
	<div class="table_<?php echo $graphNumber; ?>_visits_title">VISITS</div>
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
