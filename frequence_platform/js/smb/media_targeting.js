
function scaled_bullet_graph(realized_value, mu_value, sigma_value, id) 
{
	var normalized_error = (realized_value - mu_value)/sigma_value;
	normalized_error = Math.min(3,Math.max(normalized_error,-3));

	var range2 = 10*normalized_error+30;
	var range3 = 30;
	var error_color = '#45ADA8';

	if (normalized_error < 0){
		range2 = 30;
		range3 = 30+10*normalized_error;
		error_color = '#AAC2C1';
	}

	$(id).sparkline([0,0,60,range2,range3], {
	type: 'bullet',
	height: '8',
	targetWidth: 1,
	targetColor: '#ffffff',
	performanceColor: '#ffffff',
	rangeColors: ['white',error_color,'white']});
}

function save_iab_categories()
{
	var iab_categories_array = $("#iab_category_choices").val();
	$.ajax({
		type: "POST",
		url: "/smb/save_iab_categories",
		data: {iab_categories: iab_categories_array},
		success: function(data) {},
		error: function(jqXHR, testStatus, errorThrown)
		{
			alert("save_iab_categories() error: "+errorThrown);
		}
	});
}

function update_site_pack(category)
{    
		alert("The function 'update_site_pack' is deprecated use UpdateSitesList().");
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("GET", "/smb/get_media_targeting_site_pack/"+category, false);
		xmlhttp.send();
		document.getElementById("site_pack").innerHTML = xmlhttp.responseText;
}

function UpdateSitesList()
{
	var channelChoicesArray = $("#channel_choices").val();
	var channelChoices = "";
	if(channelChoicesArray)
	{
		channelChoices = channelChoicesArray.join("|");
	}

	var iab_categories_array = $("#iab_category_choices").val();
	var iab_categories = "";
	if(iab_categories_array)
	{
		iab_categories = iab_categories_array.join("|");
	}

	var encodedParameters = EncodeDemographicAndReachFrequencyParameters();
	//var category = $("#site_pack_dropdown").val();

	//category: category,
	$.ajax({
		type: "POST",
		url: "/smb/get_media_targeting_site_pack",
		data: {
			encodedData: encodedParameters,
			channels: channelChoices,
			iab_categories: iab_categories
		},
		success: function(data) {
			$("#site_pack").html(data);
		},
		error: function(jqXHR, testStatus, errorThrown)
		{
			alert("UpdateSitesList() error: "+errorThrown);
		}
	});
}

function channels_chosen_script(){
	//var display_string = '';
	var channel_query_string = '';
	var channel_query_array = [];
	var i;
	var j;
	var selected_count = 0;
	var url_to_call;
	
	for (i=0; i<document.getElementById("channel_choices").length; i++){
		if(document.getElementById("channel_choices")[i].selected == true){
			//display_string = display_string + document.getElementById("channel_choices")[i].value + '<br>'; 
			channel_query_array[selected_count] = document.getElementById("channel_choices")[i].value;
			selected_count = selected_count+1;
		} 
	}
	 
	for (j=0;j<selected_count;j++){
		//alert("cqs:" + channel_query_array[j]);
		channel_query_string = channel_query_string + channel_query_array[j];
		if(j < (selected_count)-1){
					 channel_query_string = channel_query_string+'|';
		}   
	} 
	if (channel_query_string!=''){
		var xmlhttp = new XMLHttpRequest();
		url_to_call = "/smb/get_channel_sites/"+channel_query_string;
		//alert(url_to_call);
		xmlhttp.open("GET", url_to_call, false);
		xmlhttp.send();
		document.getElementById("category_sites").innerHTML= xmlhttp.responseText;
		//alert(xmlhttp.responseText)
	}else{
		document.getElementById("category_sites").innerHTML= '';
	}
}



