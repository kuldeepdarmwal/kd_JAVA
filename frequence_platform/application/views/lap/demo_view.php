<?php 

	$maleAverage = $national_averages_array['male_population'];
	$femaleAverage = $national_averages_array['female_population'];
	$underEighteen = $national_averages_array['age_under_18'];
	$eighteenToTwentyFour = $national_averages_array['age_18_24'];
	$twentyFiveToThirtyFour = $national_averages_array['age_25_34'];
	$thirtyFiveToFortyFour = $national_averages_array['age_35_44'];
	$fortyFiveToFiftyFour = $national_averages_array['age_45_54'];
	$fiftyFiveToSixtyFour = $national_averages_array['age_55_64'];
	$sixtyFiveAndUp = $national_averages_array['age_65_and_over'];
	$Cauc = $national_averages_array['white_population'];
	$AfrAm = $national_averages_array['black_population'];
	$Asian = $national_averages_array['asian_population'];
	$Hisp = $national_averages_array['hispanic_population'];
	$Other = $national_averages_array['other_race_population'];
	$NoKids	= $national_averages_array['kids_no'];
	$HasKids = $national_averages_array['kids_yes'];
	$zeroToFiftyK = $national_averages_array['income_0_50'];
	$fiftyToOneHundredK = $national_averages_array['income_50_100'];
	$oneHundredToOneFiftyK = $national_averages_array['income_100_150'];
	$oneFiftyKAndUp = $national_averages_array['income_150'];
	$NoCollege = $national_averages_array['college_no'];
	$College = $national_averages_array['college_under'];
	$GradSch = $national_averages_array['college_grad'];

?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="/ring_files/css/ringfonts.css"/>
<link rel="stylesheet" type="text/css" href="/css/smb/demographic_graphs.css" />

<script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="/js/jquery.sparkline.js"></script>
<script type="text/javascript" src="/js/smb/demographic_common_code.js"></script>
<script type="text/javascript">

function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
		vars[key] = value;
	});
	return vars;
}

$(document).ready(function(){
	sparkline(<?php echo ((($demographics['male_population'] / $demographics['region_population']) * 100) / $maleAverage); ?>, <?php echo number_format((($demographics['male_population'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_male");
	sparkline(<?php echo ((($demographics['female_population'] / $demographics['region_population']) * 100) / $femaleAverage); ?>, <?php echo number_format((($demographics['female_population'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_female");
	sparkline(<?php echo ((($demographics['age_under_18'] / $demographics['region_population']) * 100) / $underEighteen); ?>, <?php echo number_format((($demographics['age_under_18'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_under18");
	sparkline(<?php echo ((($demographics['age_18_24'] / $demographics['region_population']) * 100) / $eighteenToTwentyFour); ?>, <?php echo number_format((($demographics['age_18_24'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_18to24");
	sparkline(<?php echo ((($demographics['age_25_34'] / $demographics['region_population']) * 100) / $twentyFiveToThirtyFour); ?>, <?php echo number_format((($demographics['age_25_34'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_25to34");
	sparkline(<?php echo ((($demographics['age_35_44'] / $demographics['region_population']) * 100) / $thirtyFiveToFortyFour); ?>, <?php echo number_format((($demographics['age_35_44'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_35to44");
	sparkline(<?php echo ((($demographics['age_45_54'] / $demographics['region_population']) * 100) / $fortyFiveToFiftyFour); ?>, <?php echo number_format((($demographics['age_45_54'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_45to54");
	sparkline(<?php echo ((($demographics['age_55_64'] / $demographics['region_population']) * 100) / $fiftyFiveToSixtyFour); ?>, <?php echo number_format((($demographics['age_55_64'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_55to64");
	sparkline(<?php echo ((($demographics['age_65_and_over'] / $demographics['region_population']) * 100) / $sixtyFiveAndUp); ?>, <?php echo number_format((($demographics['age_65_and_over'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_65up");

	sparkline(<?php echo ((($demographics['income_0_50'] / $demographics['total_households']) * 100) / $zeroToFiftyK); ?>, <?php echo number_format((($demographics['income_0_50'] / $demographics['total_households']) * 100), 1); ?>, "#sparkline_0to50");
	sparkline(<?php echo ((($demographics['income_50_100'] / $demographics['total_households']) * 100) / $fiftyToOneHundredK); ?>, <?php echo number_format((($demographics['income_50_100'] / $demographics['total_households']) * 100), 1); ?>, "#sparkline_50to100");
	sparkline(<?php echo ((($demographics['income_100_150'] / $demographics['total_households']) * 100) / $oneHundredToOneFiftyK); ?>, <?php echo number_format((($demographics['income_100_150'] / $demographics['total_households']) * 100), 1); ?>, "#sparkline_100to150");
	sparkline(<?php echo ((($demographics['income_150'] / $demographics['total_households']) * 100) / $oneFiftyKAndUp); ?>, <?php echo number_format((($demographics['income_150'] / $demographics['total_households']) * 100), 1); ?>, "#sparkline_150up");

	sparkline(<?php echo ((($demographics['college_no'] / $demographics['region_population']) * 100) / $NoCollege); ?>, <?php echo number_format((($demographics['college_no'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_NoCollege");
	sparkline(<?php echo ((($demographics['college_under'] / $demographics['region_population']) * 100) / $College); ?>, <?php echo number_format((($demographics['college_under'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_College");
	sparkline(<?php echo ((($demographics['college_grad'] / $demographics['region_population']) * 100) / $GradSch); ?>, <?php echo number_format((($demographics['college_grad'] / $demographics['region_population']) * 100), 1); ?>, "#sparkline_GradSchool");

	sparkline(<?php echo ((($demographics['kids_no'] / $demographics['total_households']) * 100) / $NoKids); ?>, <?php echo number_format((($demographics['kids_no'] / $demographics['total_households']) * 100), 1); ?>, "#sparkline_NoKids");
	sparkline(<?php echo ((($demographics['kids_yes'] / $demographics['total_households']) * 100) / $HasKids); ?>, <?php echo number_format((($demographics['kids_yes'] / $demographics['total_households']) * 100), 1); ?>, "#sparkline_Kids");

	sparkline(<?php echo ((($demographics['white_population'] / $demographics['normalized_race_population']) * 100) / $Cauc); ?>, <?php echo number_format((($demographics['white_population'] / $demographics['normalized_race_population']) * 100), 1); ?>, "#sparkline_Cauc");
	sparkline(<?php echo ((($demographics['black_population'] / $demographics['normalized_race_population']) * 100) / $AfrAm); ?>, <?php echo number_format((($demographics['black_population'] / $demographics['normalized_race_population']) * 100), 1); ?>, "#sparkline_AfrAmer");
	sparkline(<?php echo ((($demographics['asian_population'] / $demographics['normalized_race_population']) * 100) / $Asian); ?>, <?php echo number_format((($demographics['asian_population'] / $demographics['normalized_race_population']) * 100), 1); ?>, "#sparkline_Asian");
	sparkline(<?php echo ((($demographics['hispanic_population'] / $demographics['normalized_race_population']) * 100) / $Hisp); ?>, <?php echo number_format((($demographics['hispanic_population'] / $demographics['normalized_race_population']) * 100), 1); ?>, "#sparkline_Hisp");
	sparkline(<?php echo ((($demographics['other_race_population'] / $demographics['normalized_race_population']) * 100) / $Other); ?>, <?php echo number_format((($demographics['other_race_population'] / $demographics['normalized_race_population']) * 100), 1); ?>, "#sparkline_Other");
});





</script>
</head>
<body style="overflow:hidden; height:100%; width:100%; position:absolute;">
	<div style="position:absolute; left: 10px;font-family:'BebasNeue', sans-serif; color:#423b3b; font-size:18px; max-height:45px; overflow:auto; width:90%;"> 
		<?php echo $targeted_region_summary;?>
	</div>
	<div style="line-height:120%; position: absolute; float:left;top:65px; padding-bottom:20px;border-bottom: 0px solid #cecece; width:100%; font-family:BebasNeue;font-size: 12px; color:#547980;">
		<div class="ExtraDemographicDataValue" style="vertical-align:top;margin-right: 40px; position: absolute; left:0px; display:inline;border: 0px solid black">
			<?php echo number_format($demographics['region_population']); ?> <br>
			<span class="ExtraDemographicDataTitle" style=""> Total Population</span>
		</div>
		<div class="ExtraDemographicDataValue" style="margin-right: 40px; position: absolute; left: 150px; display:inline;border: 0px solid black">
			$<?php echo number_format($demographics['household_income']); ?> <br> 
			<span class="ExtraDemographicDataTitle" style=""> Household Income</span>
		</div>
		<div class="ExtraDemographicDataValue" style="margin-right: 40px; position: absolute; left:295px; display:inline;border: 0px solid black">
			$<?php echo number_format($demographics['average_home_value']); ?> <br>
			<span class="ExtraDemographicDataTitle" style=""> House Value</span>
		</div>
	</div>

	<div style="line-height:120%; position: absolute; float:left; top:115px; padding-bottom:20px;border-bottom: 0px solid #cecece; width:100%; font-family:BebasNeue;font-size: 12px; color:#547980;">
		<div class="ExtraDemographicDataValue" style="margin-right: 72px; position: absolute; left:0px; display:inline;border: 0px solid black"> 
			<?php echo number_format($demographics['num_establishments']); ?> <br> 
			<span class="ExtraDemographicDataTitle" style=""> Businesses</span>
		</div>
		<div class="ExtraDemographicDataValue" style="margin-right: 98px; position: absolute; left:150px; display:inline;border: 0px solid black"> 
			<?php echo number_format($demographics['total_households']); ?> <br> 
			<span class="ExtraDemographicDataTitle" style="">Households</span>
		</div>
		<div class="ExtraDemographicDataValue" style="margin-right: 40px; position: absolute; left:290px; display:inline;border: 0px solid black"> 
			<?php echo number_format($demographics['median_age'], 1); ?> <br> 
			<span class="ExtraDemographicDataTitle" style=""> Median Age</span>
		</div>
	</div>

	<div style="position: absolute; top:160px; width:90%; border-bottom: 1px solid #c0c1c2;"></div>

	<?php

		$topOffset = '180px';
		$averageType = 'US Average';
		require('application/views/smb/demographic_graphs.php');

	?>

</body>
</html>
