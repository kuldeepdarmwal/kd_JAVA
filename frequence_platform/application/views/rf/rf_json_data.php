<?php

$frequencyColor = "'#4795D1'";
$reachColor = "'#168C6B'";

$reach = $reach_frequency_result;

$numElementsInChart = 6; // corresponds to var of same name in rf.php & rf_header.php
for ($i=0;$i<$numElementsInChart ;$i++){
    $impressions_labels[$i] = $i + 1;
    $demo_pop_percentage = $demo_pop / $geo_pop;
    $demo_reach_uv[$i] = $reach[$i]['reach_percent']*$geo_pop*$demo_pop_percentage;
    $demo_size_time_series[$i] = round($demo_pop_percentage*$geo_pop,0);
}

$monthTitles = array();
for ($ii=0;$ii<$numElementsInChart ;$ii++){
	$monthTitles[$ii] = date("M", strtotime("+ ".($ii+1)." month", time()));
}

$rf_inputs = explode("|",urldecode($rf_parameters));
$nIMPRESSIONS = 0;
$nDEMO_COV = 1;
$nGAMMA = 2;
$nIP_ACCURACY = 3;
$nDEMO_ONLINE = 4;

$finalGeoPop = $geo_pop;
$finalDemoPop = $demo_pop;
if($finalDemoPop > $finalGeoPop)
{
	$finalDemoPop = $finalGeoPop;
}

$discountedPrice = (1-($discountPercent/100)) * $monthlyPriceEstimate;

?>

<?php
	class RfPageData {
		public $geoPopulation  = 0;
		public $demoPopulation  = 0;
		public $priceEstimate = 0;
		public $priceEstimateString = "";
		public $targeted_region_summary = "";
		public $displayTimesBetter = 0;
		public $mediaReachTitle = "";
		public $mediaFrequencyTitle = "";
		public $mediaCategories = array();
		public $monthTitles = array();
		public $impressions = array();
		public $reachPercent = array();
		public $reachValue = array();
		public $frequency = array();
		public $demoFrequency = array();
		public $demoReach = array();
		public $otherMediaReachPercent = array();
		public $otherMediaFrequency = array();
	}

	$data = new RfPageData();

	
	/*
	// Constants also found in rf_header.php
	$kPrintMediaIndex = 0;
	$kDirectMediaIndex = 1;
	$kRadioMediaIndex = 2;
	$kNumOtherMedia = 3;
	for($ii=0; $ii<$kNumOtherMedia; $ii++)
	{
		$data->otherMediaReachPercent[$ii] = array();
		$data->otherMediaFrequency[$ii] = array();
	}
	*/

	$data->geoPopulation =  number_format($finalGeoPop);
	$data->demoPopulation = number_format($finalDemoPop);
	$data->priceEstimate = $monthlyPriceEstimate;
	$data->priceEstimateString = '$'.number_format($monthlyPriceEstimate, 2).' / mo';
	$data->targeted_region_summary = $targeted_region_summary;
	
	$data->mediaReachTitle = $mediaType." Reach";
	$data->mediaFrequencyTitle = $mediaType." Average Frequency";

	$numCategories = $mediaComparisonCategories->num_rows();
	for($ii=0; $ii<$numCategories; $ii++)
	{
		$data->mediaCategories[$ii] = $mediaComparisonCategories->row($ii)->media_type;
	}
	$mediaComparisonCategories->row($ii)->media_type;

	for($ii=0; $ii<$numElementsInChart;$ii+=1) 
	{
		$data->monthTitles[$ii] = $monthTitles[$ii];
		$data->impressions[$ii] = number_format(($ii + 1) * $rf_inputs[$nIMPRESSIONS] / 1000) . 'k';
		$data->reachPercent[$ii] = round($reach[$ii]['reach_percent'] * 100, 1) . "%";
		$data->reachValue[$ii] = number_format($demo_reach_uv[$ii]);
		$data->frequency[$ii] = round($reach[$ii]['frequency'],2);
		
		$data->demoFrequency[$ii] = $reach[$ii]['frequency'];
		$data->demoReach[$ii] = $reach[$ii]['reach_percent'];

		if($mediaComparisonData->num_rows() > 0)
		{
			$geoPopulation = $finalGeoPop;
			$row = $mediaComparisonData->row(0);
			$cpm = $row->cpm+0; // Convert from string to number
			$coverage = $row->coverage+0;
			$geoAccuracy = $row->geo_accuracy+0;
			$numMonths = $ii+1;

			$impressions = $numMonths * 1000 * $discountedPrice / $cpm;
			$landed = $geoAccuracy * $impressions;
			$subMagicNumber = $coverage*$geoPopulation;
			$magicNumber = ($subMagicNumber-1)/$subMagicNumber;
			$geoReach = (1-pow($magicNumber, $landed)) / (1-$magicNumber);
			$mediaReachPercent = $geoReach / $geoPopulation;
			$data->otherMediaReachPercent[$ii] = $mediaReachPercent;
			$mediaFrequency = ($impressions*$geoAccuracy)/($mediaReachPercent*$geoPopulation);
			$data->otherMediaFrequency[$ii] = $mediaFrequency;
			$points = $mediaReachPercent*100*$mediaFrequency;
			$mediaCostPerPoint = ($discountedPrice * $numMonths)/($mediaReachPercent*$mediaFrequency*100);

			$displayReach = $reach[$ii]['reach_percent'];
			$displayFrequency = $reach[$ii]['frequency'];
			$displayCostPerPoint = ($discountedPrice * $numMonths)/($displayReach*$displayFrequency*100);
			$data->displayTimesBetter = number_format($mediaCostPerPoint/$displayCostPerPoint, 0).'X';
		}
		else
		{
			$data->otherMediaReachPercent[$ii] = 0.1;
			$data->otherMediaFrequency[$ii] = 0.5;
			$data->displayTimesBetter = '2X';
			//die("rf_json_data.php other media invalid: ".$mediaType);
		}
		/*
		for($jj=0; $jj<$kNumOtherMedia; $jj++)
		{
			$data->otherMediaReachPercent[$jj][$ii] = ($jj + $ii*3)/100.0;
			$data->otherMediaFrequency[$jj][$ii] = $jj + 3 + $ii*0.8;
		}
		*/
	}

	echo json_encode($data);
?>
