<!DOCTYPE html>
<html>
<head>
<title>Map Builder</title>
<style>
.info_div {
	display:inline-block;
	width:300px;
	padding:7px;
	position:relative;
}
.info_div:hover{ 
	background-color:#DDDDDD;
}
td {
	padding:5px;
	
}
#geo_div{
   white-space: -moz-pre-wrap; /* Firefox */    
   white-space: -pre-wrap;     /* Opera <7 */   
   white-space: -o-pre-wrap;   /* Opera 7 */    
   word-wrap: break-word;      /* IE */
}
.demo_value{
	position:absolute;
	left:105px;
}
.demo_label{
	width:80px;
	text-align:right;
}
ul.sortable li {
	position: relative;
}

ul.boxy {
	list-style-type: none;
	padding: 4px 4px 0 4px;
	margin: 0px;
	width: 10em;
	font-size: 13px;
	font-family: Arial, sans-serif;
}
ul.boxy li {
	cursor:move;
	margin-bottom: 4px;
	padding: 2px 2px;
	border: 1px solid #ccc;
	background-color: #eee;
}

ul.boxier {
	list-style-type: none;
	padding: 4px 4px 0 4px;
	margin: 0px;
	width: 10em;
	font-size: 15px;
	font-family: "Courier New", Courier, monospace;
	font-variant: small-caps;
}
ul.boxier li {
	cursor:move;
	margin-bottom: 4px;
	padding: 2px 2px;
	border: 1px solid #c00;
	background-color: #eee;
}
</style>

<script language="JavaScript" type="text/javascript" src="../js/generator/coordinates.js"></script>
<script language="JavaScript" type="text/javascript" src="../js/generator/drag.js"></script>
<script language="JavaScript" type="text/javascript" src="../js/generator/dragdrop.js"></script>

<script language="JavaScript" type="text/javascript"><!--
	window.onload = function() {
		var list = document.getElementById("sites");
		DragDrop.makeListContainer( list );
		list.onDragOver = function() { this.style["background"] = "#EEF"; };
		list.onDragOut = function() {this.style["background"] = "none"; };
	};
	//-->
</script>

</script>
</head>

<body>
	<div id="geo_div" class="info_div" onMouseOver="$('#geoSpan').css('display','inline');" onMouseOut="$('#geoSpan').css('display','none');">
		<h3>Geo</h3>
		<?php echo $targeted_regions; ?>
	</div>

	<div id="impressions" class="info_div">
		<h3>Impressions</h3>
		<?php echo $impressions; ?>	per month <br><br>
		Retargeting: <?php echo $retargeting; ?>
	</div>

	<div id="demographic" class="info_div">
		<h3>Demographic</h3>
		<table>
			<tr><td style="text-align:right;font-weight:bold;">Gender:</td><td><?php echo $gender_value ?></td></tr>
			<tr><td style="text-align:right;font-weight:bold;">Age:</td><td><?php echo $age_value ?></td></tr>
			<tr><td style="text-align:right;font-weight:bold;">Income:</td><td><?php echo $income_value ?></td></tr>
			<tr><td style="text-align:right;font-weight:bold;">Ethnic:</td><td><?php echo $ethnic_value ?></td></tr>
			<tr><td style="text-align:right;font-weight:bold;">Parenting:</td><td><?php echo $parenting_value ?></td></tr>
			<tr><td style="text-align:right;font-weight:bold;">Education:</td><td><?php echo $education_value ?></td></tr>
		</table>

	</div>

	<script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="/js/jquery.sparkline.js"></script>

</body>
</html>
