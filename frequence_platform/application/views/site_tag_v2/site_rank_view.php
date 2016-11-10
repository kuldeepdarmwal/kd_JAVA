<!DOCTYPE html>
<html lang="en">
<head>
<style>
.toprow {
	background-color: #000;
	color: #fff;
	font-weight: bold;
}

.subrow {
	background-color: #EBF0EF;
	color: #000;
	font-weight: bold;
}

.tblc {
	background-color: #fff;
}

.thclass {
	background-color: #F5CEA2;
}

#codiv1 {
	font-size: 10px;
}
</style>
<title>Site Gen</title>
<script type="text/javascript" language="javascript"
	src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet"
	href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css">
<script
	src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script type="text/javascript">

	var each_row = "";
	function get_mpq_details()
	{
		var mpq_id = document.getElementById("mpq_id").value;
		var cvg_factor = document.getElementById("cvg_factor").value;
		var cont_factor = document.getElementById("cont_factor").value;
		var cont_n_factor = document.getElementById("cont_n_factor").value;
		var gen_factor = document.getElementById("gen_factor").value;
		var age_factor = document.getElementById("age_factor").value;
		var income_factor = document.getElementById("income_factor").value;
		var parent_factor = document.getElementById("parent_factor").value;
		var edu_factor = document.getElementById("edu_factor").value;
		var detailedflag = false;
		$("#btntdwait").html('<marquee behavior="scroll" direction="right" scrollamount="20"><img src="images/processing_cat.gif" height="50" width="100" /></marquee>');		
		if (mpq_id == '')
			return;
		var data_url = "/siterank_controller/getmpqdata/";
		var dataiab = "";
		$("input:checked").each(function() 
		{
				dataiab += $(this).val()+",";
		});      
		$.ajax({
			type: "POST",
			url: data_url,
			async: true,
			data: 
			{
				mpq_id: mpq_id,
				cvg_factor: cvg_factor,
				cont_factor: cont_factor,
				cont_n_factor: cont_n_factor,
				gen_factor: gen_factor,
				age_factor: age_factor,
				income_factor: income_factor,
				parent_factor: parent_factor,
				edu_factor: edu_factor,
				detailedflag:detailedflag,
				dataiab : dataiab
			},
			dataType: 'json',
			error: function(msg)
			{
			 	alert('error');
			},
			success: function(msg)
			{ 
				$("#btntdwait").html("Now creating the view");		

				// otable
				oTable.fnClearTable();
				each_rowdata = msg['site_details_arr'];
				var ieach_row = 0;
				for (ieach_row=0 ; ieach_row < each_rowdata.length;ieach_row++) 
				{
					oTable.fnAddData([
					"<span style='color:green;font-size:13px'>"+each_rowdata[ieach_row][0]+"</span>",
					"<span style='font-size:13px'><b>"+each_rowdata[ieach_row][1]+"</b></span>",
					each_rowdata[ieach_row][2],
					each_rowdata[ieach_row][3],
					each_rowdata[ieach_row][4],
					each_rowdata[ieach_row][5],
					each_rowdata[ieach_row][6],
					each_rowdata[ieach_row][7],
					each_rowdata[ieach_row][8],
					each_rowdata[ieach_row][9],
					each_rowdata[ieach_row][10],
					each_rowdata[ieach_row][11],
					each_rowdata[ieach_row][12],
					each_rowdata[ieach_row][13],
					each_rowdata[ieach_row][14],
					each_rowdata[ieach_row][15],
					each_rowdata[ieach_row][16]
					]);
				} // End For
				// otable over
				
				// local rel table 
				localTable1.fnClearTable();
				each_row = msg['local_relevence'];
				var ji = 0;
				for (ji=0 ; ji < each_row.length; ji++) 
				{
					localTable1.fnAddData([
						"<span style='font-size:13px'><b>"+each_row[ji][0]+"</b></span>",
						each_row[ji][1],
						each_row[ji][2],
						each_row[ji][3]
						]);
				} // End For
				var context_array = msg['context_array'];
						
				var tablechdr = ' <table  class="tblc display table table-bordered" cellspacing="0" width="100%">'+
					'<thead> <tr class= thclass valign="top">'+
					'  <th valign="top" width="30%"></th>'+
					'  <th valign="top">Reach%</th>'+
					'  <th valign="top">Male%</th>'+
					' <th valign="top">Female%</th>'+
					'  <th valign="top">Under-18%</th>'+
					'  <th valign="top">18-24%</th>'+
					' <th valign="top">25-34%</th>'+
					'  <th valign="top">35-44%</th>'+
					'  <th valign="top">45-54%</th>'+
					' <th valign="top">55-64%</th>'+
					'  <th valign="top">65+%</th>'+
					'  <th valign="top">No Kids%</th>'+
					' <th valign="top">Has Kids%</th>'+
					'  <th valign="top">$0-50k%</th>'+
					'  <th valign="top">50-100k%</th>'+
					'  <th valign="top">100-150k%</th>'+
					' <th valign="top">150k%</th>'+
					'</tr> </thead>';
			    var tablec="";
				for (var key in context_array) 
				{//0 has score, 1 has data, 2 has children
						var sitesarry = context_array[key];
						 
					 	if (key == undefined)
					 		key = "";

					 	var score = sitesarry[0];
					 	var data1 = sitesarry[1];
					 	var kids = sitesarry[2];
					 	
						tablec+=tablechdr+"<tr class=toprow><td colspan=17>"+key.toUpperCase()+"</td></tr>";
						//console.log(data1);
						for (var key1 in data1) 
						{

							if (data1[key1] != undefined) {
								var var0=data1[key1][0]	;
	 						 	if (var0 == undefined)
	 						 		var0 = "";
 						 		var var1=data1[key1][1];
	 						 	if (var1 == undefined)
	 						 		var1 = "";
	 						 	var var2=data1[key1][2];
	 						 	if (var2 == undefined)
	 						 		var2 = "";
	 						 	var var3=data1[key1][3];
	 						 	if (var3 == undefined)
	 						 		var3 = "";
	 						 	var var4=data1[key1][4];
	 						 	if (var4 == undefined)
	 						 		var4 = "";

	 						 	var var5=data1[key1][5]	;
	 						 	if (var5 == undefined)
	 						 		var5 = "";
 						 		var var6=data1[key1][6];
	 						 	if (var6 == undefined)
	 						 		var6 = "";
	 						 	var var7=data1[key1][7];
	 						 	if (var7 == undefined)
	 						 		var7 = "";
	 						 	var var8=data1[key1][8];
	 						 	if (var8 == undefined)
	 						 		var8 = "";
	 						 	var var9=data1[key1][9];
	 						 	if (var9 == undefined)
	 						 		var9 = "";

	 						 	var var10=data1[key1][10]	;
	 						 	if (var10 == undefined)
	 						 		var10 = "";
 						 		var var11=data1[key1][11];
	 						 	if (var11 == undefined)
	 						 		var11 = "";
	 						 	var var12=data1[key1][12];
	 						 	if (var12 == undefined)
	 						 		var12 = "";
	 						 	var var13=data1[key1][13];
	 						 	if (var13 == undefined)
	 						 		var13 = "";
	 						 	var var14=data1[key1][14];
	 						 	if (var14 == undefined)
	 						 		var14 = "";

	 							var var15=data1[key1][15]	;
	 						 	if (var15 == undefined)
	 						 		var15 = "";
 						 		var var16=data1[key1][16];
	 						 	if (var16 == undefined)
	 						 		var16 = "";
	 						 	var var17=data1[key1][17];
	 						 	if (var17 == undefined)
	 						 		var17 = "";

	 						 	tablec+="<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+var1+"</td><td>"+var2+
	 						 	"</td><td>"+var3+"</td><td>"+var4+"</td><td>"+var5+"</td><td>"+var6+"</td><td>"+var7+"</td><td>"+var8+
	 						 	"</td><td>"+var9+"</td><td>"+var10+"</td><td>"+var11+"</td><td>"+var12+"</td><td>"+var13+"</td><td>"
	 						 	+var14+"</td><td>"+var15+"</td><td>"+var16+"</td><td>"+var17+"</td></tr>";
							}
						}
						for (var key1 in kids) 
						{
							var innerarr = kids[key1];
							var score1 = innerarr[0];
						 	var data11 = innerarr[1];
						 	var hdr1 = key1.toUpperCase();

							if (hdr1.indexOf(" > ") != -1) 
								hdr1=hdr1.substring(hdr1.indexOf(" > ")+3,hdr1.length );
						 	
							tablec+="<tr class=subrow><td colspan=17>&nbsp;&nbsp;&nbsp;"+hdr1+"</td></tr>";
							for (var key2 in data11) 
							{
								if (data11[key2] != undefined) 
								{
									var var0=data11[key2][0];
		 						 	if (var0 == undefined)
		 						 		var0 = "";
								 		var var1=data11[key2][1];
		 						 	if (var1 == undefined)
		 						 		var1 = "";
		 						 	var var2=data11[key2][2];
		 						 	if (var2 == undefined)
		 						 		var2 = "";
		 						 	var var3=data11[key2][3];
		 						 	if (var3 == undefined)
		 						 		var3 = "";
		 						 	var var4=data11[key2][4];
		 						 	if (var4 == undefined)
		 						 		var4 = "";		
		 							var var5=data11[key2][5]	;
		 						 	if (var5 == undefined)
		 						 		var5 = "";
	 						 		var var6=data11[key2][6];
		 						 	if (var6 == undefined)
		 						 		var6 = "";
		 						 	var var7=data11[key2][7];
		 						 	if (var7 == undefined)
		 						 		var7 = "";
		 						 	var var8=data11[key2][8];
		 						 	if (var8 == undefined)
		 						 		var8 = "";
		 						 	var var9=data11[key2][9];
		 						 	if (var9 == undefined)
		 						 		var9 = "";	
		 						 	var var10=data11[key2][10]	;
		 						 	if (var10 == undefined)
		 						 		var10 = "";
	 						 		var var11=data11[key2][11];
		 						 	if (var11 == undefined)
		 						 		var11 = "";
		 						 	var var12=data11[key2][12];
		 						 	if (var12 == undefined)
		 						 		var12 = "";
		 						 	var var13=data11[key2][13];
		 						 	if (var13 == undefined)
		 						 		var13 = "";
		 						 	var var14=data11[key2][14];
		 						 	if (var14 == undefined)
		 						 		var14 = "";	
		 							var var15=data11[key2][15]	;
		 						 	if (var15 == undefined)
		 						 		var15 = "";
	 						 		var var16=data11[key2][16];
		 						 	if (var16 == undefined)
		 						 		var16 = "";
		 						 	var var17=data11[key2][17];
		 						 	if (var17 == undefined)
		 						 		var17 = "";
	
		 						 	tablec+="<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+var1+"</td><td>"+var2+
		 						 	"</td><td>"+var3+"</td><td>"+var4+"</td><td>"+var5+"</td><td>"+var6+"</td><td>"+var7+"</td><td>"+var8+
		 						 	"</td><td>"+var9+"</td><td>"+var10+"</td><td>"+var11+"</td><td>"+var12+"</td><td>"+var13+"</td><td>"
		 						 	+var14+"</td><td>"+var15+"</td><td>"+var16+"</td><td>"+var17+"</td></tr>";
	 							}
							}
						}
						tablec +='</table><br>';
					}
					document.getElementById("codiv").innerHTML = "<br>"+tablec;


				//demo n tbl
				demotbl1.fnClearTable();
				var demo_array = msg['demo_array'];
			 
				for (var key in demo_array) 
				{
					var sitesarry = demo_array[key];

					if (key == undefined)
				 		key = "";
			 		if ($("#detailedflag").prop('checked')) 
				 	{
						demotbl1.fnAddData(["<span style='font-size:13px;font-weight: bold;background-color:#F5F7B0'><b>"+key+"</b></span>", "", "", ""]);
			 		}				 	 
					for(var key1 in sitesarry) 
					{
						var var1=sitesarry[key1][0];
					 	if (var1 == undefined)
					 		var1 = "";
					 	var var2=sitesarry[key1][1];
					 	if (var2 == undefined)
					 		var2 = "";
					 	var var3=sitesarry[key1][2];
					 	if (var3 == undefined)
					 		var3 = "";
					 	var var4=sitesarry[key1][3];
					 	if (var4 == undefined)
					 		var4 = "";
				 		if (sitesarry[key1][1] != undefined && sitesarry[key1][1] != '')
							demotbl1.fnAddData( [ "<span style='font-size:13px;'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+var1+"</b></span>", var2 , var3 ,var4 ]);
					} 
				}				
					 
				$("#context_id").html("<b>"+msg['iab_str']+"</b>");
				var forcheckboxes = msg['dataiab'];
				var forcheckboxesarr = forcheckboxes.split("<br>");
				for (var jj=0; jj < forcheckboxesarr.length; jj++) 
				{
					if (forcheckboxesarr[jj] == undefined || forcheckboxesarr[jj] =='')
						continue;
					document.getElementById(forcheckboxesarr[jj]).checked=true;
				}
				$("#demo_id").html("<b>"+msg['demo_str']+"</b>");
				$("#zip_id").html("<b>"+msg['ziparr_var']+"</b>");
				$("#btntdwait").html("");	
				$("#iab_sum").html(msg['iab_sum']);
				$("#iab_nei_sum").html(msg['iab_nei_sum']);
				$("#gender_sites_sum").html(msg['gender_sites_sum']);
				$("#age_sum").html(msg['age_sum']);
				$("#income_sum").html(msg['income_sum']);
				$("#parenting_sum").html(msg['parenting_sum']);
				$("#edu_sum").html(msg['edu_sum']);
			}
		});
	}
	var oTable = "";
	var localTable1 = "";
	var demotbl1 = "";
	function load_ready() 
	{
	  oTable = $('#sitedetailstbl').dataTable({
		  paging: false ,
		  "iDisplayLength": 100,
		  "aaSorting": [ 0, 'desc' ],
		  "aoColumns": [
                null,
                null,
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false },
                { "bSortable": false }
			]
		}
	);

	localTable1 = $('#localtbl').dataTable({
			  paging: false ,
			  "iDisplayLength": 50,
			  "aaSorting": [ 3, 'asc' ],
			  "autoWidth": false,
			  "columns": [
	              { "width": "50%" },
	              null,
	              null,
	              null 
	            ],
			  "aoColumns": [
                null,
                null,
                null, 
                null
              ]
		}
	);

	demotbl1 = $('#demotbl').dataTable({
		  "columns": [
              { "width": "50%" },
              null,
              null,
              null 
            ],
		  paging: false  ,"autoWidth": false,
		   "bSort" : false,
		  "iDisplayLength": 200				  
		}
	);

	$('#tabs').tab();
}
</script>
</head>
<body onload="load_ready()">
	<div class="container-fluid" id="codiv1">
		<div class="row-fluid">

			<div class="span10 well well-large row"
				style="background-color: #F7E9E1; height: 250px;">

				<div class="span2">
					<span style="visibility: block"> <label class="control-label"
						for="landing_page_input">MPQ ID</label><br> <input type="text"
						placeholder="MPQ ID" id="mpq_id" class="input-small" value="14444"></input><br>
						<a id="insertion_order_submit_button" onclick='get_mpq_details();'
						role="button" class="btn btn-info" data-toggle="modal">Rank 'em</a>
					</span><br>
				</div>

				<div class="span4">
					Contextual: (Select for Industry priority)<br>
					<div id='context_id'></div>
				</div>

				<div class="span2">
					Demos:<br>
					<div id='demo_id'></div>
				</div>

				<div class="span3">
					Coverage Zips:<i class="icon-ok-sign" rel="tooltip" title="" id="zip_id"></i><br>
					
				</div>

			</div>

			<div class="span2 well well-large row"
				style="background-color: #F7E9E1; height: 250px;">
				<label class="control-label" for="landing_page_input"><b>Matching
						tags count:</b></label>
				<table>

					<tr>
						<td>Contextual:</td>
						<td><div style="display: inline-block;" id='iab_sum'></div></td>
					</tr>
					<tr>
						<td>Contextual Neighbours:&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<td>
							<div style="display: inline-block;" id='iab_nei_sum'></div>
						</td>
					</tr>
					<tr>
						<td>Gender:</td>
						<td><div style="display: inline-block;" id='gender_sites_sum'></div>
						</td>
					</tr>
					<tr>
						<td>Age:</td>
						<td>
							<div style="display: inline-block;" id='age_sum'></div>
						</td>
					</tr>
					<tr>
						<td>Income:</td>
						<td><div style="display: inline-block;" id='income_sum'></div></td>
					</tr>
					<tr>
						<td>Parenting:</td>
						<td>
							<div style="display: inline-block;" id='parenting_sum'></div>
						</td>
					</tr>
					<tr>
						<td>Education:</td>
						<td>
							<div style="display: inline-block;" id='edu_sum'></div>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div id='btntdwait' class="span9"
			style='color: #FF8000; font-size: 16px'></div>
	</div>

	<div class="row-fluid">
		<div id="content span9">
			<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
				<li class="active"><a href="#red" data-toggle="tab"><span
						style="font-size: 16px">Contextual Coverage</span></a></li>
				<li><a href="#orange" data-toggle="tab"><span
						style="font-size: 16px">Demographic Coverage</span></a></li>
				<li><a href="#yellow" data-toggle="tab"><span
						style="font-size: 16px">Local Coverage</span></a></li>
				<li><a href="#green" data-toggle="tab"><span style="font-size: 16px">Detailed
							Site Scores</span></a></li>
			</ul>
			<div id="my-tab-content" class="tab-content">
				<div class="tab-pane active" id="red">
					<div class="container-fluid" id="codiv"></div>
				</div>

				<div class="tab-pane" id="orange">

					<div class="container-fluid">
						<table id="demotbl" class="tblc display table table-bordered"
							cellspacing="0" width="100%">
							<thead>
								<tr valign="top" class="thclass">
									<th valign="top" width="50%">Site</th>
									<th valign="top">Reach%</th>
									<th valign="top">Male%</th>
									<th valign="top">Female%</th>
								</tr>
							</thead>
						</table>
					</div>

				</div>

				<div class="tab-pane" id="yellow">

					<div class="container-fluid">
						<table id="localtbl" class="display table table-bordered tblc"
							cellspacing="0" width="100%">
							<thead>
								<tr valign="top" class="thclass">
									<th valign="top" width="50%">Site</th>
									<th valign="top">Area</th>
									<th valign="top">Reach%</th>
									<th valign="top">Distance in miles</th>
								</tr>
							</thead>
							<tbody class='tblc'></tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="green">
					<div class="container-fluid">
						<h5>Detailed Site Scores - 400 Sites sorted by Score</h5>
						<table id="sitedetailstbl" class="display table table-bordered"
							cellspacing="0" width="100%">
							<thead>
								<tr valign="top" class="thclass">
									<th valign="top">Score</th>
									<th valign="top">Site</th>
									<th valign="top"><input type="text" id="cvg_factor" value="0.3"
										class="input-mini"></input><br>Coverage</th>
									<th valign="top"><input type="text" id="cont_factor" value="10"
										class="input-mini"></input><br>Contextual</th>
									<th valign="top" width="300px">Contextual Details</th>
									<th valign="top"><input type="text" id="cont_n_factor"
										value="6" class="input-mini"></input><br>Contextual Neighbours</th>
									<th valign="top" width="300px">Contextual Neighbours Details</th>
									<th valign="top"><input type="text" id="gen_factor" value="5"
										class="input-mini"></input><br>Gender</th>
									<th valign="top">Gender Details</th>
									<th valign="top"><input type="text" id="age_factor" value="1"
										class="input-mini"></input><br>Age</th>
									<th valign="top" width="80px">Age Details</th>
									<th valign="top"><input type="text" id="income_factor"
										value="2" class="input-mini"></input><br>Income</th>
									<th valign="top" width="120px">Income Details</th>
									<th valign="top"><input type="text" id="parent_factor"
										value="5" class="input-mini"></input><br>Parenting</th>
									<th valign="top">Parenting Details</th>
									<th valign="top"><input type="text" id="edu_factor" value="2"
										class="input-mini"></input><br>Education</th>
									<th valign="top">Education Details</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

</html>