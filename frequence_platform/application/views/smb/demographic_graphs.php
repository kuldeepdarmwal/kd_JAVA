<?php
// This file is used by both the Media Targeting and Geo Targeting pannels.

if(!isset($topOffset))
{
	die('Variable $topOffset must be defined for demographic_graphs.php');
	$topOffset = 0;
}

if(!isset($averageType))
{
	die('Variable $averageType must be defined for demographic_graphs.php');
	$averageType = 'average';
}


echo '<div style="text-align:right;position:absolute;left:0px;top:'.$topOffset.';border: 0px solid black;font-family: Oxygen, sans-serif;	font-size: 13.3px; color: #333333;">';
	echo '<div class="InternetAverageCenterLine">';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:0px;">';
		echo '<div class="DemographicGroupTitle" style="">Gender</div>';
		echo '<div id="sparkline_male" class="DemographicSparkline" style="top:28px;"></div>';
		echo '<div id="sparkline_female" class="DemographicSparkline" style="top:44px;"></div>';
		echo '<div class="DemographicRowName" style="top:24px;">Male:</div>';
		echo '<div class="DemographicRowName" style="top:40px;">Female:</div>';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:72px;">';
		echo '<div class="DemographicGroupTitle" style="">Age</div>';
		echo '<div id="sparkline_under18" class="DemographicSparkline" style="top:28px;"></div>';
		echo '<div id="sparkline_18to24" class="DemographicSparkline" style="top:44px;"></div>';
		echo '<div id="sparkline_25to34" class="DemographicSparkline" style="top:60px;"></div>';
		echo '<div id="sparkline_35to44" class="DemographicSparkline" style="top:76px;"></div>';
		echo '<div id="sparkline_45to54" class="DemographicSparkline" style="top:92px;"></div>';
		echo '<div id="sparkline_55to64" class="DemographicSparkline" style="top:108px;"></div>';
		echo '<div id="sparkline_65up" class="DemographicSparkline" style="top:124px;"></div>';
		echo '<div class="DemographicRowName" style="top:24px;">&lt 18:</div>';
		echo '<div class="DemographicRowName" style="top:40px;">18 - 24:</div>';
		echo '<div class="DemographicRowName" style="top:56px;">25 - 34:</div>';
		echo '<div class="DemographicRowName" style="top:72px;">35 - 44:</div>';
		echo '<div class="DemographicRowName" style="top:88px;">45 - 54:</div>';
		echo '<div class="DemographicRowName" style="top:104px;">55 - 64:</div>';
		echo '<div class="DemographicRowName" style="top:120px;">65 +:</div>';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:220px;">';
		echo '<div class="DemographicGroupTitle" style="">Household Income</div>';
		echo '<div id="sparkline_0to50" class="DemographicSparkline" style="top:28px;"></div>';
		echo '<div id="sparkline_50to100" class="DemographicSparkline" style="top:44px;"></div>';
		echo '<div id="sparkline_100to150" class="DemographicSparkline" style="top:60px;"></div>';
		echo '<div id="sparkline_150up" class="DemographicSparkline" style="top:76px;"></div>';
		echo '<div class="DemographicRowName" style="top:24px;">&lt $50k:</div>';
		echo '<div class="DemographicRowName" style="top:40px;">$50k-100k:</div>';
		echo '<div class="DemographicRowName" style="top:56px;">$100k-150k:</div>';
		echo '<div class="DemographicRowName" style="top:72px;">$150k +:</div>';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:318px;">';
		echo '<div class="InternetAverageSubtext" style="top:0px;">'.$averageType.'</div>';
	echo '</div>';
echo '</div>';

echo '<div style="width:230px; text-align:right; position:absolute; left:217px; border: 0px solid black; top:'.$topOffset.';font-size: 13.3px;font-family: Oxygen, sans-serif;color: #414142;">';
	echo '<div class="InternetAverageCenterLine">';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:0px;">';
		echo '<div class="DemographicGroupTitle" style="">Education Level</div>';
		echo '<div id="sparkline_NoCollege" class="DemographicSparkline" style="top:28px;"></div>';
		echo '<div id="sparkline_College" class="DemographicSparkline" style="top:44px;"></div>';
		echo '<div id="sparkline_GradSchool" class="DemographicSparkline" style="top:60px;"></div>';
		echo '<div class="DemographicRowName" style="top:24px;">No College:</div>';
		echo '<div class="DemographicRowName" style="top:40px;">College:</div>';
		echo '<div class="DemographicRowName" style="top:56px;">Grad School:</div>';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:110px;">';
		echo '<div class="DemographicGroupTitle" style="">Children In Household</div>';
		echo '<div id="sparkline_NoKids" class="DemographicSparkline" style="top:28px;"></div>';
		echo '<div id="sparkline_Kids" class="DemographicSparkline" style="top:44px;"></div>';
		echo '<div class="DemographicRowName" style="top:24px;">No Kids:</div>';
		echo '<div class="DemographicRowName" style="top:40px;">Has Kids:</div>';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:200px;">';
		echo '<div class="DemographicGroupTitle" style="">Ethnicity</div>';
		echo '<div id="sparkline_Cauc" class="DemographicSparkline" style="top:28px;"></div>';
		echo '<div id="sparkline_AfrAmer" class="DemographicSparkline" style="top:44px;"></div>';
		echo '<div id="sparkline_Asian" class="DemographicSparkline" style="top:60px;"></div>';
		echo '<div id="sparkline_Hisp" class="DemographicSparkline" style="top:76px;"></div>';
		echo '<div id="sparkline_Other" class="DemographicSparkline" style="top:92px;"></div>';
		echo '<div class="DemographicRowName" style="top:24px;">Cauc:  </div>';
		echo '<div class="DemographicRowName" style="top:40px;">Afr Amer: </div>';
		echo '<div class="DemographicRowName" style="top:56px;">Asian: </div>';
		echo '<div class="DemographicRowName" style="top:72px;">Hisp: </div>';
		echo '<div class="DemographicRowName" style="top:88px;">Other: </div>';
	echo '</div>';

	echo '<div class="DemographicGroup" style="left:0px; top:318px;">';
		echo '<div class="InternetAverageSubtext" style="top:0px;">'.$averageType.'</div>';
	echo '</div>';
echo '</div>';
?>
