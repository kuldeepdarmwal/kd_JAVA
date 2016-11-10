<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>VantageLocal Option Engine</title>
	<style>
	.box {border:2px solid #0969a4;}
	.box h2 {background:#0969a4;color:white;padding:10px;margin-top:0px;}
	.box p {color:#333;padding:10px;}
	.box {
 		-moz-border-radius:5px;
 		-webkit-border-radius:5px;
 	 	border-radius:5px;
		background-color:#FFFFFF;
	}
	[id*="subtabs"] .tabs-panels {padding:20px;}
	body{
		padding:5px;
	}
	td {
		padding:3px;
	}
	</style>
</head>
<body>
	<div style="margin:10px">
		Option Name:  <input type="textfield" size="80" id="option_name_<?php echo $option_name;?>">

		<table style="width:700px;">
			<tr>
				<td><span id="option_pricing_base_period_type_<?php echo $option_name;?>">Monthly</span> Base: $<span id="monthly_raw_cost_<?php echo $option_name;?>">0</span></td>
				<td>Discount:&nbsp;&nbsp;&nbsp;<input type="text" size="3" name="discount_monthly_percent_<?php echo $option_name;?>" id="discount_monthly_percent_<?php echo $option_name;?>" onChange="handle_option_change('discount', <?php echo $option_name;?>);" /> %</td>
				<td><span id="option_pricing_total_period_type_<?php echo $option_name;?>">Monthly</span> Total $<span id="monthly_total_cost_<?php echo $option_name;?>">0</span></td>
			</tr>
			<tr colspan="3">
				<td><span id="option_pricing_base_period_type_<?php echo $option_name;?>">Monthly</span> Impressions: <span id="total_impressions_<?php echo $option_name;?>">0</span></td>
				<td><span id="option_pricing_total_period_type_<?php echo $option_name;?>">Monthly</span> CPM: $<span id="total_cpm_<?php echo $option_name;?>">0</span></td>
			</tr>
			<tr colspan="3">
				<td><label for="creative_design_<?php echo $option_name;?>"><input type="checkbox" checked="checked" name="creative_design_<?php echo $option_name;?>_1" id="creative_design_<?php echo $option_name;?>" /> Creative Design ($0)</label></td>
				<td><label for="campaign_or_month_<?php echo $option_name;?>"><input type="checkbox" name="campaign_or_month_<?php echo $option_name;?>" id="campaign_or_month_<?php echo $option_name;?>"> Campaign Cost</label></td>
			</tr>
		</table>
	</div>
	<div id="subtabs<?php echo $option_name;?>" style="margin:10px;">
	
	
	</div>
	    

</body>
</html>
