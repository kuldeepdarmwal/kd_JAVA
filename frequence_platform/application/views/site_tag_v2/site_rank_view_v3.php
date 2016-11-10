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
<title>Site Gen for TTD</title>
<script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script type="text/javascript">

$(document).ready(function() 
{
	init_iab_category_data();
	init_industry_data();
});

function init_iab_category_data()
{
	 
	$('#iab_contextual_multiselect').select2({
		placeholder: "Select custom contextual channels",
		minimumInputLength: 0,
		multiple: true,
		ajax: {
			url: "/mpq_v2/get_contextual_iab_categories/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 10,
					page: page
				};
			},
			results: function (data) {
				return {results: data.result, more: data.more};
			}
		},
		allowClear: true
	});
}

function init_industry_data()
{
	$('#industry_select').select2({
		placeholder: "Select Advertiser Industry",
		minimumInputLength: 0,
		multiple: false,
		ajax: {
			url: "/mpq_v2/get_industries/",
			type: 'POST',
			dataType: 'json',
			data: function (term, page) {
				term = (typeof term === "undefined" || term == "") ? "%" : term;
				return {
					q: term,
					page_limit: 20,
					page: page
				};
			},
			results: function (data) {
				return {results: data.results, more: data.more};
			}
		},
		allowClear: true
	});
}

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

function get_mpq_details(type_of_sites)
{
	showoverlay();
	var industry_multi = document.getElementById("industry_multi").value;
	var industry_n_multi = document.getElementById("industry_n_multi").value;
	var iab_multi = document.getElementById("iab_multi").value;
	var iab_weight = document.getElementById("iab_weight").value;
	var iab_n_weight = document.getElementById("iab_n_weight").value;
	var reach_weight = document.getElementById("reach_weight").value;
	var stereo_weight = document.getElementById("stereo_weight").value;

	var industry_select = document.getElementById("industry_select").value;
	var iab_contextual_multiselect = document.getElementById("iab_contextual_multiselect").value;
	var zips = document.getElementById("zips").value;
	if (zips.indexOf("\n") != -1)
	{
		zips=zips.replace(new RegExp("\n", 'g'), ",");
	}
	var gender_male_demographic = document.getElementById("gender_male_demographic").checked;
	var gender_female_demographic = document.getElementById("gender_female_demographic").checked;

	var age_under_18_demographic = document.getElementById("age_under_18_demographic").checked;
	var age_18_to_24_demographic = document.getElementById("age_18_to_24_demographic").checked;
	var age_25_to_34_demographic = document.getElementById("age_25_to_34_demographic").checked;
	var age_35_to_44_demographic = document.getElementById("age_35_to_44_demographic").checked;
	var age_45_to_54_demographic = document.getElementById("age_45_to_54_demographic").checked;
	var age_55_to_64_demographic = document.getElementById("age_55_to_64_demographic").checked;
	var age_over_65_demographic = document.getElementById("age_over_65_demographic").checked;

	var income_under_50k_demographic = document.getElementById("income_under_50k_demographic").checked;
	var income_50k_to_100k_demographic = document.getElementById("income_50k_to_100k_demographic").checked;
	var income_100k_to_150k_demographic = document.getElementById("income_100k_to_150k_demographic").checked;
	var income_over_150k_demographic = document.getElementById("income_over_150k_demographic").checked;

	var parent_no_kids_demographic = document.getElementById("parent_no_kids_demographic").checked;
	var parent_has_kids_demographic = document.getElementById("parent_has_kids_demographic").checked;
	var education_no_college_demographic = document.getElementById("education_no_college_demographic").checked;
	var education_college_demographic = document.getElementById("education_college_demographic").checked;
	var education_grad_school_demographic = document.getElementById("education_grad_school_demographic").checked;
	
	$.ajax({
		type: "POST",
		url: "/siterank_controller/get_site_rankings_ttd/",
		async: true,
		data: 
		{
			type_of_sites: type_of_sites,
			industry_multi: industry_multi,
			industry_n_multi: industry_n_multi,
			iab_multi: iab_multi,
			iab_weight: iab_weight,
			iab_n_weight: iab_n_weight,
			reach_weight: reach_weight,
			stereo_weight: stereo_weight,

			industry_select: industry_select,
			iab_contextual_multiselect: iab_contextual_multiselect,
			zips: zips,

			gender_male_demographic: gender_male_demographic,
			gender_female_demographic: gender_female_demographic,

			age_under_18_demographic: age_under_18_demographic,
			age_18_to_24_demographic: age_18_to_24_demographic,
			age_25_to_34_demographic: age_25_to_34_demographic,
			age_35_to_44_demographic: age_35_to_44_demographic,
			age_45_to_54_demographic: age_45_to_54_demographic,
			age_55_to_64_demographic: age_55_to_64_demographic,
			age_over_65_demographic: age_over_65_demographic,
			
			income_under_50k_demographic: income_under_50k_demographic,
			income_50k_to_100k_demographic: income_50k_to_100k_demographic,
			income_100k_to_150k_demographic: income_100k_to_150k_demographic,
			income_over_150k_demographic: income_over_150k_demographic,

			parent_no_kids_demographic: parent_no_kids_demographic,
			parent_has_kids_demographic: parent_has_kids_demographic,

			education_no_college_demographic: education_no_college_demographic,
			education_college_demographic: education_college_demographic,
			education_grad_school_demographic: education_grad_school_demographic
			
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

			// table numbers
			var complete_data_hdr=" <table border=1 id='sitedetailstbl' style='display: inline-block;'  cellspacing='0' > <thead> <tr valign='top' class='thclass'><th valign='top' width='50'>Counter</th></tr> </thead> ";
			var complete_data_ftr="</table>";
			var complete_data=complete_data_hdr;
			var row_counter=1;
			for (var key in context_array) 
			{ 
				complete_data+="<tr><td>"+row_counter+"</td></tr>";
				row_counter++;
			}
			complete_data+=complete_data_ftr;
			// table data
			complete_data_hdr=" <table border=1 id='sitedetailstbl' style='display: inline-block;'   cellspacing='0'> <thead> <tr valign='top' class='thclass'><th valign='top' width='170'>Domain</th> <th valign='top'>Category ID</th> <th valign='top' width='200'>Adjustment</th></tr> </thead> ";
			complete_data_ftr="</table>";
			complete_data+=complete_data_hdr;
			row_counter=1;
			for (var key in context_array) 
			{ 
				complete_data+="<tr>";
				var sitesarry = context_array[key];
				var sub_counter=0
				for (var key_sub in sitesarry) 
				{ 
					var data = sitesarry[key_sub];
					if (key_sub == "url")
					{
						complete_data+="<td class='url_data_csv'>"+data+"</td><td></td><td>1</td>";
					}
						
					if (sub_counter>3)
						break;
					sub_counter++;
				}
				complete_data+="</tr>";
				row_counter++;
			}
			complete_data+=complete_data_ftr;
			document.getElementById("codiv").innerHTML=complete_data;

			// header info
			$("#industry_id").html(data_all["industry_id"]);
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

function create_excel()
{
        var tds = document.getElementsByTagName("td");
		 var ratingTdText="";
		 for (var i = 0; i < tds.length; i++) {
		     var cur = tds[i];
		     var the_class = cur.className;
		     if (the_class=="url_data_csv") {
		    	 ratingTdText = ratingTdText + cur.innerHTML + ",";
		     }
		 }
		 document.forms['main_form']['sites_list'].value=ratingTdText;
		 document.forms['main_form'].action = "/siterank_controller/export_sites/";
		 document.forms['main_form'].method='post';
		 document.forms['main_form'].submit();
} 
	
</script>
</head>
<body><iframe id="txtArea1" style="display:none"></iframe>
	<div class='small-size'>
		<form id="main_form" name='main_form' class="form-inline">
			<div class="container-fluid" id="codiv1">
			<h4>Site List Generator : For Bidder Upload</h4>
		<!-- 	this is for weights -->	 	
			<div class="row">
			<div class="span12">
			<table>
				<tr>
					<td>
					Industry Hdr Multi* <input type="text" id="industry_multi" class="input-mini" value="500"></input>
					Industry Nbr Hdr Multi* <input type="text" id="industry_n_multi" class="input-mini" value="250"></input>
					IAB Hdr Multi* <input type="text" id="iab_multi" class="input-mini" value="25"></input>
					IAB Weight <input type="text" id="iab_weight" class="input-mini" value="50"></input>
					IAB Neighbour Weight<input type="text" id="iab_n_weight" class="input-mini" value="5"></input>
					Reach Weight <input type="text" id="reach_weight" class="input-mini" value="25"></input>
					Stereotype Weight <input type="text" id="stereo_weight" class="input-mini" value="200"></input>
					
			</td></tr>
			</table>
			</div>
			</div>
			<br>
		<!-- 	this is for industry -->	 	
			<div class="row">
			<div class="span6">
			<input type="hidden" id="industry_select" class="my-select2-container span6 site-tag-multi-class js-example-basic-multiple"></div>
			<div class="span6">
			<input type="hidden" id="iab_contextual_multiselect" class="small-size my-select2-container span6 site-tag-multi-class js-example-basic-multiple">
			</div>
			</div>
			<br>
		
		<!-- 	this is for zips -->	 	
			<div class="row">
			<div class="span12 small-size">
			<textarea id="zips" placeholder="Paste comma seperated zips. eg: 94001,94002,94003... OR one zip per line" class='small-size' style="width:1160px; height:25px;"></textarea>
			</div>
			</div>
				
		<!-- 	this is for demo  -->
			<div class="row">
			<div class="span12">
			 
		
			<div class="span1 intro-chardin">
				<h4 class="muted">
					<!-- Gender -->
				</h4>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="gender_male_demographic" value="true">Male
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="gender_female_demographic" value="true">Female
			</label>
		
			</div>
		
			<div class="span3 intro-chardin">
				<h4 class="muted">
					<!-- Age -->
				</h4>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="age_under_18_demographic">Under 18
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="age_18_to_24_demographic">18 - 24
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="age_25_to_34_demographic">25 - 34
			</label>
		<br>
			<label class="small-size checkbox">
				<input type="checkbox" id="age_35_to_44_demographic" >35 - 44
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="age_45_to_54_demographic">45 - 54
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="age_55_to_64_demographic">55 - 64
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="age_over_65_demographic">Over 65
			</label>
		
			</div>
		
			<div class="span3 intro-chardin" id="hhi_checkboxes">
				<h4 class="muted">
					<!-- Household Annual Income -->
				</h4>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="income_under_50k_demographic">Under $50k
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="income_50k_to_100k_demographic">$50k-100k
			</label><br>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="income_100k_to_150k_demographic">$100k-150k
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="income_over_150k_demographic">Over $150k
			</label>
		
			</div>
		
			<div class="span1 intro-chardin">
				<h4 class="muted">
					<!--Parenting -->
				</h4>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="parent_no_kids_demographic">No Kids
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="parent_has_kids_demographic">Kids
			</label>
		
			</div>
		
			<div class="span2 intro-chardin" id="ed_checkboxes">
				<h4 class="muted">
					<!-- Education -->
				</h4>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="education_no_college_demographic">No College
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="education_college_demographic">College
			</label>
		
			<label class="small-size checkbox">
				<input type="checkbox" id="education_grad_school_demographic">Grad School
			</label>
			
			</div></div>

				<div><br><a id="insertion_order_submit_button" onclick='get_mpq_details("BIDDING")' role="button" class="btn btn-info btn-small" data-toggle="modal">Bidder Sites</a>
				&nbsp;
					  <a id="insertion_order_submit_button" onclick='get_mpq_details("PREMIUM")' role="button" class="btn btn-success btn-small" data-toggle="modal">Premium Sites</a>
					  <a id="download_button" onclick='create_excel()' role="button" class="btn btn-success btn-small" data-toggle="modal">Download Excel for TTD</a> 
			</div></div>
			
			<div class="row">
				<div id='btntdwait' class="span9" style='color: #FF8000; font-size: 16px'></div>
			</div>
<br>
			<div class="row-fluid" id="codiv"></div>
			<input type='hidden' id='sites_list' name='sites_list'/>
		</form>
	</div>
</body>
</html>