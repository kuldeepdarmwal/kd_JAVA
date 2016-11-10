$(function(){
		
		$(".adimage").preloadify({ imagedelay:100 });
	
	$("#restart").click(function(e){
		$(".adimage").preloadify({ imagedelay:50 }); e.preventDefault();
		});
	
	});
		var busy=false;
		function toggleSlide() {
			//if you want, you can add javascript to the end of this function or the
			//onClick call to 
			if(!busy){
				if($("div.gridRight").is(':visible')){
					//busy = true;
					slideIn(function(src){},"dummy");
					return;
				} else {
					//busy = true;
					slideOut();			
				}
			}
		}
		function slideOut() {
			busy = true;
		    $("div.gridRight").css("display", "inline");
		    $("div.gridRight").animate({ marginLeft: "0px" }); 
			busy = false;
		    //$("div.slide").toggle();
		}
		function slideIn(func,src) {
			busy = true;
			$("div.gridRight").animate({ marginLeft: "-600px" }, {complete: function() {$("div.gridRight").css("display", "none");func(src);}});
			busy = false;
		}

		function checkAndSlideOut()
		{
			if($("div.gridRight").css('marginLeft') != '0px' ||
				!$("div.gridRight").is(':visible')){
				slideOut();
			}
		}

function slideIn_geo(func,src) {
			busy = true;
			$("div#gridRight_geo").animate({ marginLeft: "-600px" }, {complete: function() {$("div#gridRight_geo").css("display", "none");func(src);}});
			busy = false;
		}
		function slideOut_geo() {
			busy = true;
		    $("div#gridRight_geo").css("display", "inline");
		    $("div#gridRight_geo").animate({ marginLeft: "0px" }); 
			busy = false;
		    //$("div.slide").toggle();
		}

		function slideGeo() {
                    slideIn_geo(slideOut_geo);
                }
		function cleanSlide(){
			if($("div.gridRight").is(':visible')){
				slideIn(function(){}, "dummy");
			}
			document.getElementById("sliderHeaderContent").innerHTML = '<span></span>';
			document.getElementById("sliderBodyContent").innerHTML = '';
			$('#slideOut').css('margin-top', 30);
		}
		function changeSlide(src){ 
			//alert($(window).height() + "");
			//alert($(window).scrollTop() + "");
			//alert($("div.gridRight").css('left'));
			if($("div.gridRight").css('marginLeft') == '0px'){
				//alert("sliding in");
				slideIn(changeSlide,src);
				return;
			}
			//alert($("div.gridRightMod").is(':visible') + "");
			//alert("got here!");
                        
                        document.getElementById("sliderBodyContent").innerHTML = '<div style="width:310px;height:260px;margin-right:auto;margin-left:auto;">'+
                                                                       '<iframe src="http://creative.vantagelocal.com/lap_demo_files/client/'+src+'/slide_embed.html" allowfullscreen="true" id="frame0" frameborder="0" border="0" height=100% width=100% scrolling=no></iframe>' + 
                                                                        '</div><br><br>'+
									'<div style="width:300px;margin-right:auto;margin-left:auto;"><a style="width:200px;margin-right:auto;margin-left:45px;" href="http://creative.vantagelocal.com/lap_demo_files/client/'+src+'/index.html" target="_blank"><button style="border:0px; -moz-box-shadow: inset 0 0 1px 1px #888;-webkit-box-shadow: inset 0 0 1px 1px #888; box-shadow: inset 0 0 1px 1px #888;background-color:#AF9631; font-family: Oxygen, sans-serif; text-transform: uppercase; color:#fbfbfb;width:180px;margin-right:auto;margin-left:auto; font-weight:bold; border-radius:2px; height:28px; font-size:12px;">View All Sizes</button></a><br><br>'+
									'<a style="width:200px;margin-right:auto;margin-left:45px;" href="http://creative.vantagelocal.com/lap_demo_files/client/'+src+'/popup.html" target="_blank"><button style="border:0px; -moz-box-shadow: inset 0 0 1px 1px #888;-webkit-box-shadow: inset 0 0 1px 1px #888; box-shadow:inset 0 0 1px 1px #888;background-color:#AF9631; font-family: Oxygen, sans-serif; text-transform: uppercase; font-size:12px; color:#fbfbfb; font-weight:bold; border-radius:2px; height:28px; width:180px;margin-right:auto;margin-left:auto;">Sample Placement</button></a></div>'+
                                                                        '';

                        /*
			document.getElementById("slideOut").innerHTML = '<div class="widget">'+
																'<div class="header" id="sliderHeaderContent"><span></span></div>' + 
																'<div class="content" id="sliderBodyContent">' +
																	'<div style="width:396px;height:333px;margin-right:auto;margin-left:auto;position:relative;">'+
																		'<div style="width:310px;height:260px;margin-right:auto;margin-left:auto;">'+
																		
																			'<iframe src="http://creative.vantagelocal.com/lap_demo_files/client/'+src+'/slide_embed.html" allowfullscreen="true" id="frame0" frameborder="0" border="0" height=100% width=100% scrolling=no></iframe>' + 
			
																		'</div>'+
																		'<img src="/images/creative_lib_ad_shadow.png" width="396" height="63" style="position:relative; top:-33px; margin-right:auto; margin-left:auto;" />'+
																		'<a style="width:265px; height:40px; margin-right:65px;margin-left:65px; position:relative;" href="http://creative.vantagelocal.com/lap_demo_files/client/'+src+'/popup.html" target="_blank">'+
																			'<img src="/images/creative_library_sample_button.png" width="265" height="40" alt="Sample Placement" style="margin:0px auto;"/>'+
																		'</a>'+
																		'<div id="cl_view_all_sizes">'+
																			'<a href="http://creative.vantagelocal.com/lap_demo_files/client/'+src+'/index.html" target="_blank">View All Sizes</a>'+
																		'</div>'+
																	'</div>'+
																'</div>'+
															'</div>'+
                                                                                                                        '<div class="header" id="notes_header_geo" style="position:absolute; top:0px; left:0px; visibility: hidden;"><span></span></div>'+
                                                                                                                        '<div class="content" id="notes_body_geo" style="position:absolute; top:0px; left:0px; visibility: hidden;">'+
                                                                                                                        '</div>';
*/


			//$('#slideOut').css('margin-top', $(window).scrollTop() + 30);
			//alert("now here");	
			slideOut();
			//alert("but not here");
			//alert($("div.gridRightMod").is(':visible') + "");



		}

