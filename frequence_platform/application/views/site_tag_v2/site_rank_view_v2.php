<!DOCTYPE html>
<html lang="en">
<head>
<style>
.toprow 
{
	background-color: #000;
	color: #fff;
	font-weight: bold;
}

.subrow 
{
	background-color: #EBF0EF;
	color: #000;
	font-weight: bold;
}

.tblc 
{
	background-color: #fff;
}

.highlight 
{
	background-color: #F5E942;
	font-weight: bold;
	font-size: 15px;
}

.thclass 
{
	background-color: #6CBD9B;
}

.small-size 
{
	font-size: 10px;
}
</style>
<title>Site Gen</title>
<script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script type="text/javascript">

function showoverlay() 
{
	
	if (document.getElementById('overlay') == undefined) 
	{
		$("body").append("<div id='overlay' style='background-color:#F2F0F2; opacity: 0.8;position:absolute;top:0;left:0;height:400%;width:100%;z-index:999'>Processing...</div>");
	}
}

function hideoverlay() {
	if (document.getElementById('overlay') != undefined) {
		$("#overlay").remove();
	}
}

	function get_mpq_details()
	{
		showoverlay();
		var mpq_id = document.getElementById("mpq_id").value;
		var industry_multi = document.getElementById("industry_multi").value;
		var industry_n_multi = document.getElementById("industry_n_multi").value;
		var iab_multi = document.getElementById("iab_multi").value;
		var iab_weight = document.getElementById("iab_weight").value;
		var iab_n_weight = document.getElementById("iab_n_weight").value;
		var reach_weight = document.getElementById("reach_weight").value;
		var stereo_weight = document.getElementById("stereo_weight").value;
		
		if (mpq_id == '')
			return;

		$.ajax({
			type: "POST",
			url: "/siterank_controller/get_site_rankings/",
			async: true,
			data: 
			{
				mpq_id: mpq_id,
				industry_multi: industry_multi,
				industry_n_multi: industry_n_multi,
				iab_multi: iab_multi,
				iab_weight: iab_weight,
				iab_n_weight: iab_n_weight,
				reach_weight: reach_weight,
				stereo_weight: stereo_weight
			},
			dataType: 'json',
			error: function(msg)
			{
			 	alert('error');
			 	hideoverlay();
			},
			success: function(data_all)
			{
				var context_array=data_all["context_array"];
				var complete_data_hdr=" <table border=1 id='sitedetailstbl' class='small-size' cellspacing='0' width='100%'> <thead> <tr valign='top' class='thclass'><th valign='top'>Page counter</th><th valign='top' width='170'>(Total Sites#-Sites in Header#) Url</th> <th valign='top'>Site Score</th> <th valign='top' width='200'>Industry-Score</th> <th valign='top' width='200'>IAB-Score</th> <th valign='top'  width='250'>IAB Neighbours-Score</th> <th valign='top'  width='200'>Stereotype-Score</th>  <th valign='top'>Reach-Score</th><th valign='top'>Male</th> <th valign='top'>Female</th> <th valign='top'>Age Under18</th> <th valign='top'>Age 18-24</th> <th valign='top'>Age 25-34</th> <th valign='top'>Age 35-44</th> <th valign='top'>Age 45-54</th> <th valign='top'>Age 55-64</th> <th valign='top'>Age 65</th> <th valign='top'>Kids</th> <th valign='top'>No Kids</th> <th valign='top'>Income 0-50</th> <th valign='top'>Income 50-100</th> <th valign='top'>Income 100-150</th> <th valign='top'>Income 150+</th><th valign='top' width='200'>Industrychild-Score</th><th valign='top' width='200'>IndustryParent-Score</th><th valign='top' width='200'>Industrytoplevel-Score</th> </tr> </thead> ";
				var complete_data_ftr="</table>";
				var complete_data=complete_data_hdr;
				var row_counter=1;
				for (var key in context_array) 
				{ 
					if (context_array[key][0].indexOf("break_tag") == -1) 
					{
						complete_data+="<tr><td>"+row_counter+"</td>";
					}
					
						var sitesarry = context_array[key];
						for (var key_sub in sitesarry) 
						{ 
							var data = sitesarry[key_sub];
							if (data.indexOf("(Score:") != -1) 
							{
								complete_data+="<td colspan=25 class='subrow'><b>"+data+"</b></td>";
							} 
							else if (data.indexOf("header_tag") != -1 || data.indexOf("(page break)") != -1 ) 
							{
								//ignore and skip
							} 
							else if (data.indexOf("break_tag") != -1) 
							{
								row_counter=0;
								complete_data+="</tr>"+complete_data_ftr+"<br>---<span class='highlight'> "+data+" --- </span><br><br>"+complete_data_hdr ;
							} 
							else
								complete_data+="<td>"+data+"</td>";
							
						}
						if (key.indexOf("break_tag") == -1) 
						{
							complete_data+="</tr>";
							row_counter++;
						}
						
				}
				complete_data+=complete_data_ftr;
				document.getElementById("codiv").innerHTML=complete_data;

				// header info
				$("#industry_id").html(" (Type: " + data_all["product_type_flag"] + ") " + data_all["industry_id"]);
				$("#context_id").html(data_all["context_id"]);
				$("#demo_id").html(data_all["demo_id"]);
				if (data_all["zip_id"] != undefined && data_all["zip_id"].length > 500)
				{
					document.getElementById("zip_id").title=data_all["zip_id"];
					$("#zip_id").html(data_all["zip_id"].substring(0, 500)+"...(Truncated)");
				}
				else
					$("#zip_id").html(data_all["zip_id"]);
				hideoverlay();
			}
		});
	}
</script>
</head>
<body>
	<div class='small-size'>
		<form class="form-inline">
			<div class="container-fluid" id="codiv1">
				<div class="row-fluid">

					<div class="span12 well well-large row"
						style="background-color: #C7FCFA; height: 450px;">

						<div class="span2">
							<span style="visibility: block">
								<table>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">MPQ ID</label>
										
										</td>
										<td><input type="text" id="mpq_id" class="input-small"
											value="17624"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">Industry Hdr Multi*</label>
										
										</td>
										<td><input type="text" id="industry_multi" class="input-small"
											value="500"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">Industry Nbr Hdr Multi*</label>
										
										</td>
										<td><input type="text" id="industry_n_multi"
											class="input-small" value="250"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">IAB Hdr Multi*</label>
										
										</td>
										<td><input type="text" id="iab_multi" class="input-small"
											value="25"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">IAB Weight</label>
										
										</td>
										<td><input type="text" id="iab_weight" class="input-small"
											value="50"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">IAB Neighbour Weight</label>
										
										</td>
										<td><input type="text" id="iab_n_weight" class="input-small"
											value="5"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">Reach Weight</label>
										
										</td>
										<td><input type="text" id="reach_weight" class="input-small"
											value="25"></input>
										</div></td>
									</tr>
									<tr>
										<td>
											<div class="form-group">
												<label class="control-label small-size"
													for="landing_page_input">Stereotype Weight</label>
										
										</td>
										<td><input type="text" id="stereo_weight" class="input-small"
											value="200"></input>
										</div>
											<br></td>
									</tr>
									<tr>
										<td><br><a id="insertion_order_submit_button"
											onclick='get_mpq_details()' role="button"
											class="btn btn-success" data-toggle="modal">Generate</a>
								
								</table>
							</span><br>
						</div>

						<div class="span2">
							<div id='industry_id'></div>
						</div>

						<div class="span4">
							<b>User selected Snag Tags:</b> <br>
							<div id='context_id'></div>
						</div>

						<div class="span2">
							<b>Demos:</b><br>
							<div id='demo_id'></div>
						</div>

						<div class="span2">
							<b>Coverage Zips:</b><br>
							<div id='zip_id'></div>
						</div>

					</div>
				</div>
			</div>
			<div class="row">
				<div id='btntdwait' class="span9"
					style='color: #FF8000; font-size: 16px'></div>
			</div>

			<div class="row-fluid" id="codiv"></div>
		</form>
	</div>
</body>
<script type="text/javascript">
<?php
try 
{
	if ($mpq_id != null && $mpq_id != "")
	{
		echo "document.getElementById('mpq_id').value=$mpq_id;";
		echo "get_mpq_details();";
	}
} 
catch (Exception $e) 
{
}
?>
</script>
</html>