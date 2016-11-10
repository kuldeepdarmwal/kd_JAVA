<html>
<head>
<script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="/js/jquery.sparkline.js"></script>
<script type="text/javascript" src="/js/smb/demographic_common_code.js"></script>

<link rel="stylesheet" type="text/css" href="/ring_files/css/ringfonts.css"/>
<link rel="stylesheet" type="text/css" href="/css/smb/demographic_graphs.css" />

<script type="text/javascript">

</script>

<?php
	$totalReach = $realizedValueResponse->row()->totalReach;
	$realizedgm = $realizedValueResponse->row()->gm / $totalReach;
	$realizedgf = $realizedValueResponse->row()->gf / $totalReach;
	$realizedau18= $realizedValueResponse->row()->au18/ $totalReach;
	$realizeda1824= $realizedValueResponse->row()->a1824/ $totalReach;
	$realizeda2534= $realizedValueResponse->row()->a2534/ $totalReach;
	$realizeda3544= $realizedValueResponse->row()->a3544/ $totalReach;
	$realizeda4554= $realizedValueResponse->row()->a4554/ $totalReach;
	$realizeda5564= $realizedValueResponse->row()->a5564/ $totalReach;
	$realizeda65= $realizedValueResponse->row()->a65/ $totalReach;
	$realizedrc= $realizedValueResponse->row()->rc/ $totalReach;
	$realizedraa= $realizedValueResponse->row()->raa/ $totalReach;
	$realizedra= $realizedValueResponse->row()->ra/ $totalReach;
	$realizedrh= $realizedValueResponse->row()->rh/ $totalReach;
	$realizedro= $realizedValueResponse->row()->ro/ $totalReach;
	$realizedkn= $realizedValueResponse->row()->kn/ $totalReach;
	$realizedky= $realizedValueResponse->row()->ky/ $totalReach;
	$realizedi050= $realizedValueResponse->row()->i050/ $totalReach;
	$realizedi50100= $realizedValueResponse->row()->i50100/ $totalReach;
	$realizedi100150= $realizedValueResponse->row()->i100150/ $totalReach;
	$realizedi150= $realizedValueResponse->row()->i150/ $totalReach;
	$realizedcn= $realizedValueResponse->row()->cn/ $totalReach;
	$realizedcu= $realizedValueResponse->row()->cu/ $totalReach;
	$realizedcg = $realizedValueResponse->row()->cg/ $totalReach;

	$sdgm = $internetStandardDeviation->row()->gm;
	$sdgf= $internetStandardDeviation->row()->gf;
	$sdau18= $internetStandardDeviation->row()->au18;
	$sda1824= $internetStandardDeviation->row()->a1824;
	$sda2534= $internetStandardDeviation->row()->a2534;
	$sda3544= $internetStandardDeviation->row()->a3544;
	$sda4554= $internetStandardDeviation->row()->a4554;
	$sda5564= $internetStandardDeviation->row()->a5564;
	$sda65= $internetStandardDeviation->row()->a65;
	$sdrc= $internetStandardDeviation->row()->rc;
	$sdraa= $internetStandardDeviation->row()->raa;
	$sdra= $internetStandardDeviation->row()->ra;
	$sdrh= $internetStandardDeviation->row()->rh;
	$sdro= $internetStandardDeviation->row()->ro;
	$sdkn= $internetStandardDeviation->row()->kn;
	$sdky= $internetStandardDeviation->row()->ky;
	$sdi050= $internetStandardDeviation->row()->i050;
	$sdi50100= $internetStandardDeviation->row()->i50100;
	$sdi100150= $internetStandardDeviation->row()->i100150;
	$sdi150= $internetStandardDeviation->row()->i150;
	$sdcn= $internetStandardDeviation->row()->cn;
	$sdcu= $internetStandardDeviation->row()->cu;
	$sdcg= $internetStandardDeviation->row()->cg;

	$meanTotalReach = 1.0;//$getInternetMean->row()->totalReach;
	$meangm = 0.49;//$getInternetMean->row()->gm / $meanTotalReach;
	$meangf = 0.51;//$getInternetMean->row()->gf / $meanTotalReach;
	$meanau18= 0.18;//$getInternetMean->row()->au18/ $meanTotalReach;
	$meana1824= 0.12;//$getInternetMean->row()->a1824/ $meanTotalReach;
	$meana2534= 0.17;//$getInternetMean->row()->a2534/ $meanTotalReach;
	$meana3544= 0.20;//$getInternetMean->row()->a3544/ $meanTotalReach;
	$meana4554= 0.17;//$getInternetMean->row()->a4554/ $meanTotalReach;
	$meana5564= 0.10;//$getInternetMean->row()->a5564/ $meanTotalReach;
	$meana65= 0.06;//$getInternetMean->row()->a65/ $meanTotalReach;
	$meanrc= 0.76;//$getInternetMean->row()->rc/ $meanTotalReach;
	$meanraa= 0.09;//$getInternetMean->row()->raa/ $meanTotalReach;
	$meanra= 0.04;//$getInternetMean->row()->ra/ $meanTotalReach;
	$meanrh= 0.09;//$getInternetMean->row()->rh/ $meanTotalReach;
	$meanro= 0.02;//$getInternetMean->row()->ro/ $meanTotalReach;
	$meankn= 0.50;//$getInternetMean->row()->kn/ $meanTotalReach;
	$meanky= 0.50;//$getInternetMean->row()->ky/ $meanTotalReach;
	$meani050= 0.51;//$getInternetMean->row()->i050/ $meanTotalReach;
	$meani50100= 0.29;//$getInternetMean->row()->i50100/ $meanTotalReach;
	$meani100150= 0.12;//$getInternetMean->row()->i100150/ $meanTotalReach;
	$meani150= 0.08;//$getInternetMean->row()->i150/ $meanTotalReach;
	$meancn= 0.45;//$getInternetMean->row()->cn/ $meanTotalReach;
	$meancu= 0.41;//$getInternetMean->row()->cu/ $meanTotalReach;
	$meancg= 0.14;//$getInternetMean->row()->cg / $meanTotalReach;
?>

<script type="text/javascript">

customColor='#fcbb7f';

$(document).ready(function(){

	sparkline(<?php echo $realizedgm*100/$meangm.','. '"#sparkline_male"'; ?>);
	sparkline(<?php echo $realizedgf*100/$meangf.','. '"#sparkline_female"'; ?>);
	sparkline(<?php echo $realizedau18*100/$meanau18.','. '"#sparkline_under18"'; ?>);
	sparkline(<?php echo $realizeda1824*100/$meana1824.','. '"#sparkline_18to24"'; ?>);
	sparkline(<?php echo $realizeda2534*100/$meana2534.','. '"#sparkline_25to34"'; ?>);
	sparkline(<?php echo $realizeda3544*100/$meana3544.','. '"#sparkline_35to44"'; ?>);
	sparkline(<?php echo $realizeda4554*100/$meana4554.','. '"#sparkline_45to54"'; ?>);
	sparkline(<?php echo $realizeda5564*100/$meana5564.','. '"#sparkline_55to64"'; ?>);
	sparkline(<?php echo $realizeda65*100/$meana65.','. '"#sparkline_65up"'; ?>);
	
	sparkline(<?php echo $realizedrc*100/$meanrc.', "#sparkline_Cauc"'; ?>);
	sparkline(<?php echo $realizedraa*100/$meanraa.', "#sparkline_AfrAmer"'; ?>);
	sparkline(<?php echo $realizedra*100/$meanra.', "#sparkline_Asian"'; ?>);
	sparkline(<?php echo $realizedrh*100/$meanrh.', "#sparkline_Hisp"'; ?>);
	sparkline(<?php echo $realizedro*100/$meanro.', "#sparkline_Other"'; ?>);
	sparkline(<?php echo $realizedkn*100/$meankn.', "#sparkline_NoKids"'; ?>);
	sparkline(<?php echo $realizedky*100/$meanky.', "#sparkline_Kids"'; ?>);

	sparkline(<?php echo $realizedi050*100/$meani050.', "#sparkline_0to50"'; ?>);
	sparkline(<?php echo $realizedi50100*100/$meani50100.', "#sparkline_50to100"'; ?>);
	sparkline(<?php echo $realizedi100150*100/$meani100150.', "#sparkline_100to150"'; ?>);
	sparkline(<?php echo $realizedi150*100/$meani150.', "#sparkline_150up"'; ?>);

	sparkline(<?php echo $realizedcn*100/$meancn.', "#sparkline_NoCollege"'; ?>);
	sparkline(<?php echo $realizedcu*100/$meancu.', "#sparkline_College"'; ?>);
	sparkline(<?php echo $realizedcg*100/$meancg.', "#sparkline_GradSchool"'; ?>);
});

</script>

</head>
<body>
<?php
$topOffset = 0;
$averageType = 'internet average';
require('application/views/smb/demographic_graphs.php');
?>

</body>
</html>
