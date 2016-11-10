<script type="text/javascript">

		google.load('visualization', '1', {packages: ['corechart']});
		google.load('visualization', '1', {packages: ['table']});
		google.load("visualization", "1", {packages: ["map"]});

		var geocoder = new google.maps.Geocoder();
		/*Sites Flexigrid functions Added by Mobicules*/

		function checkDataTypes(desiredDataType,theValue,theID){
			if(desiredDataType == "numeric"){
				if(isNaN(theValue)==true){
					alert(theValue+" for "+theID+" is not "+desiredDataType);
					return false;
				}
				else{
					return true;
				}
			}
		}

		function saveParameters() {
			var parameters = document.getElementById("region_type").value+"_"+document.getElementById("radius").value+"_"+document.getElementById("address").value;
			var xmlhttp = new XMLHttpRequest(); 
			xmlhttp.open('GET', '/lap_lite/saveParameters/'+parameters, false); 
			xmlhttp.send();
		}

		function resize_map()
		{
			$('#region-links').toggleClass('fullscreen');
			$('#resize_map span').toggleClass('icon-resize-full');
			$('#resize_map span').toggleClass('icon-resize-small');
			$('#left_menu').toggleClass('hidden');
		}

		function make_readable(str)
		{
			str = str.replace(/(\|)+/g, ', ');
			return str.replace(/([A-Z])+/g, '');
		}

		function flexigrid(first)
		{
			slideOut();
			if(checkDataTypes("numeric",document.getElementById("radius").value,"Radius Input") == true)
			{
				var address = document.getElementById("address").value;
				var passed;
			
				geocoder.geocode( { 'address': address}, function(results, status) 
				{
					if (status == google.maps.GeocoderStatus.OK) 
					{
						passed = document.getElementById("region_type").value +
							"_" + document.getElementById("radius").value +
							"_" + results[0].geometry.location.lat() +
							"_" + results[0].geometry.location.lng();

						var savedZips = "";

						if(first !== undefined)
						{
							var xmlhttp = new XMLHttpRequest(); 
							xmlhttp.open('GET', '/lap_lite/get_initial_data', false);
							xmlhttp.send();
							var initial_data = jQuery.parseJSON(xmlhttp.responseText);
							savedZips = jQuery.trim(initial_data.zips);

							$('#manual_zips').val(make_readable(savedZips));

							var advertiser = initial_data.advertiserElem;
							var ad_plan_name = initial_data.plan;
							var notes = initial_data.notes;

							initialize_js_notes(advertiser, ad_plan_name, notes);
							initialize_html_geo_notes();
							initialize_html_notes();
						}

						if (first !== undefined && savedZips !== "") {
							var mapsrc = savedZips;
							var mapLinks = '<button id="resize_map" onclick="resize_map();"><span class="icon-resize-full"></span></button>';
							mapLinks += "<iframe id='iframe-map' src='/lap_lite/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
							// ***FLAG*** shoot back load call to parent div ***DEVCHANGE***
							var demoLinks="<iframe id='iframe-demo' style='position:absolute; left:10px;' src='/lap_lite/demos' seamless='seamless' height='570' width='420'frameborder='0'></iframe>";
							document.getElementById('region-links').innerHTML = '';

							document.getElementById('region-links').innerHTML = mapLinks;
							document.getElementById("sliderBodyContent_geo").innerHTML='';
							document.getElementById("sliderBodyContent_geo").innerHTML=demoLinks;

							document.getElementById("radius").value = '';
							document.getElementById("address").value = '';
						} 
						else 
						{
							var jsonData = $.ajax({
								url: "lap_lite/flexigrid/" + passed,
								dataType: "json",
								async: true,
								success: function(data)
								{
									if(data.is_success) 
									{
										var mapsrc = document.getElementById('targetedRegions').innerHTML;
										if(data.regions)
										{
											$('#manual_zips').val(data.regions.join(', '));
										}
										else
										{
											$('#manual_zips').val(make_readable(savedZips));
										}

										saveParameters();

										var mapLinks = '<button id="resize_map" onclick="resize_map();"><span class="icon-resize-full"></span></button>';
										mapLinks += "<iframe id='iframe-map' src='/lap_lite/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
										// ***FLAG*** shoot back load call to parent div ***DEVCHANGE***
										var demoLinks="<iframe id='iframe-demo' style='position:absolute; left:10px;' src='/lap_lite/demos' seamless='seamless' height='570' width='420'frameborder='0'></iframe>";
										document.getElementById('region-links').innerHTML = '';

										document.getElementById('region-links').innerHTML = mapLinks;
										document.getElementById("sliderBodyContent_geo").innerHTML = '';
										document.getElementById("sliderBodyContent_geo").innerHTML = demoLinks;
									}
								}
							}).responseText;
						}
					} 
					else 
					{
						alert("Geocode was not successful for the following reason: " + status);
					}
				});
			}
		}

		function flexigrid_manual()
		{
			var zips = $('#manual_zips').val();
			zips = zips.replace(/^\s+|\s+$/g,'');

			if (zips !== '')
			{
				var usable_zips = zips.replace(/([, ])+/g, '|');

				var jsonData = $.ajax({
					url: "lap_lite/flexigrid/manual",
					dataType: "json",
					async: true,
					type: "POST",
					data: {
						'zips': usable_zips
					},
					success: function(data)
					{
						if(data.is_success) 
						{
							var mapsrc = document.getElementById('targetedRegions').innerHTML;
							var mapLinks = '<button id="resize_map" onclick="resize_map();"><span class="icon-resize-full"></span></button>';
							mapLinks += "<iframe id='iframe-map' src='/lap_lite/map' seamless='seamless' height='100%' width='100%;' overflow:'hidden' frameborder='0' scrolling='no' allowtransparency='true'></iframe>" ;
							var demoLinks = "<iframe id='iframe-demo' style='position:absolute; left:10px;' src='/lap_lite/demos' seamless='seamless' height='570' width='420'frameborder='0'></iframe>";

							document.getElementById('region-links').innerHTML = mapLinks;
							document.getElementById("sliderBodyContent_geo").innerHTML = demoLinks;
						}
					}
				}).responseText;
			}
			else
			{
				alert('No zip codes were requested!');
			}
		}

	function GetBody()
	{
		var xmlhttp = new XMLHttpRequest(); 
		xmlhttp.open('GET', '/lap_lite/getInit/zoo', false); 
		xmlhttp.send();
		var body = xmlhttp.responseText; 
		document.getElementById("mapBody").innerHTML = body;
	}

	function ShowGeo()
	{
		document.getElementById("body_header_geo").innerHTML='Geographic Targeting <a id="notes_toggle_link_geo" href="javascript:toggle_notes_geo()" style="float:right;"><img src="/images/notes_PNG.png"  /><div align="right" style="float:right;width:25px;"> +</div></a>';
		document.getElementById("body_content_geo").innerHTML='';
		document.getElementById("sliderBodyContent_geo").innerHTML='';

		// document.getElementById('sliderBodyContent').innerHTML = '' + demoLinks;
		document.getElementById("sliderHeaderContent_geo").innerHTML='Demographics';
		document.getElementById("sliderBodyContent_geo").innerHTML='';

		document.getElementById("body_content_geo").innerHTML=''+
			'<div id="warning" style="position:absolute; top:110px; z-index: 100000; left:50px;font-size:10px; font-family: Oxygen, sans-serif; color:#590000; "></div>'+
			'<div id="mapBody" style="height:auto;" >'+
			'</div>'+
			'<div>'+
				'<button style="display: none;" onclick="document.getElementById(\'selected-regions\').innerHTML=\'\';document.getElementById(\'targetedRegions\').innerHTML = \'\';">QA: Clear targets</button>'+
				'<div id="targetedRegions" style="display:none;"></div>'+
			'</div>';
		GetBody();
	}

	function toggle_notes_geo()
	{
		if(document.getElementById("backButton_geo").style.visibility != 'hidden')
		{
			lap_back_geo();
		}
		if(!is_planner_showing_geo)
		{
			document.getElementById("notes_notif_geo").innerHTML = "";
			document.getElementById("advertiser_geo").value = advertiser_field;
			document.getElementById("plan_name_geo").value = ad_plan_name_field;
			document.getElementById("notes_geo").value = notes_field;
			document.getElementById("notes_geo").innerHTML = notes_field;
			slideIn(slideOut,"dummy");
			setTimeout(function() {
				document.getElementById("sliderHeaderContent_geo").style.visibility = 'hidden';    
				document.getElementById("sliderBodyContent_geo").style.visibility = 'hidden';
				document.getElementById("notes_header_geo").style.visibility = 'visible';
				document.getElementById("notes_body_geo").style.visibility = 'visible';
			},400);

			is_planner_showing_geo = true;
			document.getElementById("notes_toggle_link_geo").innerHTML = "<img src='/images/notes_PNG.png'  /><div align='right' style='float:right;width:25px;'> -</div>";
		}
		else
		{
			slideIn(slideOut,"dummy");
			setTimeout(function() {
				document.getElementById("sliderHeaderContent_geo").style.visibility = 'visible';
				document.getElementById("sliderBodyContent_geo").style.visibility = 'visible';
				document.getElementById("notes_header_geo").style.visibility = 'hidden';
				document.getElementById("notes_body_geo").style.visibility = 'hidden';
				save_notes_geo();
			},400);

			is_planner_showing_geo = false;
			document.getElementById("notes_toggle_link_geo").innerHTML = "<img src='/images/notes_PNG.png' /><div align='right' style='float:right;width:25px;'> +</div>";
		}
	}

	function HandleGeoSearch(ev)
	{
		var key = ev.keyCode || ev.which;
		var enterKeyCode = 13;
		if(key == enterKeyCode)
		{
			flexigrid();
		}
	}

	jQuery(document).ready(function(){
		jQuery('#filter_menu a').live('click', function(e)
		{
			e.preventDefault();

			if ($(this).hasClass('active')) 
			{
				return false;
			}

			jQuery('#filter_menu a.active').removeClass('active');
			jQuery(this).addClass('active');

			jQuery('#search_by_radius').toggle();
			jQuery('#search_by_zip').toggle();

			return false;
		});
	});

</script>

<style>

	#region-links {
		position: relative;
	}

	#region-links.fullscreen {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 35px;
		left: 0;
		z-index: 99999;
		background: white;
	}

	#resize_map {
		position: absolute;
		top: 21px;
		left: 15px;
		font-size: 0.75em;
		border-radius: 5px;
		background: #eee;
		border: 1px solid #666;
	}

	#left_menu.hidden {
		display: none;
	}

</style>
