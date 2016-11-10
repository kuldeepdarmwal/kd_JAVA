 

$(document).ready(function(){
	$('div#left_menu').hover(function(){
		$('ul#sublist').css("display", "inline");
	});
	$('ul#nav').hover(function(){
		$('ul#sublist').css("display", "inline");
	});
        
    document.getElementById("notes_header").innerHTML = '<span></span>';
    document.getElementById('notes_body').innerHTML = '<div style="width:400px;margin-left:auto;margin-right:auto;"><h3>Advertiser</h3>'+
	'<input type="text" id="advertiser" name="advertiser" style="margin-left:10px;" />' +
	'<h3>Ad Plan Name</h3>' +
	'<input type="text" id="plan_name" name="plan_name" style="margin-left:10px;" />' + 
	'<h3>Notes</h3>' +
	'<textarea id="ad_notes" name="ad_notes" style="height:50px;width:80%;margin-left:10px;"></textarea><br><br>'+
        '<button style="visibility: hidden" id="submitButton" onClick="save_lap();" style="width:80px;height:30px;margin-left:40px;">Submit</button>'+
	'<button style="visibility: hidden" id="backButton" onClick="lap_back();" style="width:80px;height:30px;margin-left:40px;">Cancel</button>'+
	'<button style="visibility: hidden" id="saveasButton" onclick="saveas_lap();" style="width:80px;height:30px;margin-left:40px;">Save As New</button>'+	
	'</div><span style="color:#FF0000;" id="errorspan"></span>'+
        '<p id="notes_notif" style="color: #808080;"> </p>';
        
    if (document.getElementById('span').innerHTML == 'SMB')
    {
			$('[name=drop]').css("display", "block");
			$('span#span').text("SMB");
			$('[name=sub]').css("display", "none");
			$("div#smb").css("display", "inline");
			$('a#smb').css("display", "none");  
			$('ul#sublist').css("display", "none");

			//tracker();
    }
	$('a#smb').click(function(){
		ShowGeo(); flexigrid('true'); paint_geo(); 
		$('[name=drop]').css("display", "block");
		$('span#span').text("SMB");
		$('[name=sub]').css("display", "none");
		$("div#smb").css("display", "inline");
		$('a#smb').css("display", "none");  
		$('ul#sublist').css("display", "none");
	});
	//***FLAG*** scroll detect and floating sidebar binding
	$(window).bind('scroll', function(){
		//$('#slideOut').css('margin-top', $(window).scrollTop() + 30);
	
		if(50 - $(window).scrollTop() > 0){
			$('#ghost_title').css("margin-top", 50 - $(window).scrollTop() + "px");
		} else {
			$('#ghost_title').css("margin-top", "0px");
		}
	});
	$('a#campaigns').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Campaigns");
		$('[name=sub]').css("display", "none");
		$("div#campaigns").css("display", "inline");
		$('a#campaigns').css("display", "none");  
		$('ul#sublist').css("display", "none");
	});
	$('a#admin').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Admin");
		$('[name=sub]').css("display", "none");
		$("div#admin").css("display", "inline");
		$('a#admin').css("display", "none");  
		$('ul#sublist').css("display", "none");
	});
	$('a#adops').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Ad Ops");
		$('[name=sub]').css("display", "none");
		$("div#adops").css("display", "inline");
		$('a#adops').css("display", "none");  
		$('ul#sublist').css("display", "none");
	});
	
	$('a#techops').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Tech Ops");
		$('[name=sub]').css("display", "none");
		$("div#techops").css("display", "inline");
		$('a#techops').css("display", "none");  
		$('ul#sublist').css("display", "none");
	});
	
	$('a#marketing').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Marketing");
		$('[name=sub]').css("display", "none");
		$("div#marketing").css("display", "inline");
		$('a#marketing').css("display", "none");
		$('ul#sublist').css("display", "none");
	});
	$('a#tickets').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Tickets");
		$('[name=sub]').css("display", "none");
		$("div#tickets").css("display", "inline");
		$('a#tickets').css("display", "none");
		$('ul#sublist').css("display", "none");
	});
	$('a#lap').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Local Ad Plan");
		$('[name=sub]').css("display", "none");
		$("div#lap").css("display", "inline");
	 	$('a#lap').css("display", "none");
		$('ul#sublist').css("display", "none");
	});
	$('a#proposals').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Proposals");
		$('[name=sub]').css("display", "none");
 		$("div#proposals").css("display", "inline");
 		$('a#proposals').css("display", "none");
 		$('ul#sublist').css("display", "none");
	});
	$('a#partners').click(function(){
		$('[name=drop]').css("display", "block");
		$('span#span').text("Partners");
		$('[name=sub]').css("display", "none");
		$("div#partners").css("display", "inline");
		$('a#partners').css("display", "none");
		$('ul#sublist').css("display", "none");
	});
	
	



});
        var is_planner_showing=false;
        var is_planner_showing_geo=false;
        var advertiser_field = '';
        var ad_plan_name_field = '';
        var notes_field = '';
        var menu_index = 0;
        var impressions = 300000;
        
        /*
function deploy_creative_demo()
{
    slideIn(function(src){},'dummy');
    if(menu_index == 0)
    {
        alert("ASDASD");
        save_notes_geo();
        if(is_planner_showing_geo)
        {
           init_note_toggle_geo();
        }
    }
    else if(menu_index < 3)
    {
        save_notes();
        if(is_planner_showing)
        {
           init_note_toggle();
        }
    }
    menu_index = 3;
	   $('span#geospan').css('display', 'none');
   $('span#notgeo').css('display', 'inline');

			if($("div.gridRight").css('marginLeft') == '0px'){
				slideIn(function(){}, "dummy");
			}
			document.getElementById("sliderHeaderContent").innerHTML = '<span></span>';
			document.getElementById("sliderBodyContent").innerHTML = '';
	
	document.getElementById('body_content').innerHTML='<div class="selector" style="height:30px;position:relative;top:10px;padding-left:30px;margin-right:auto;margin-left:0px;"> <div style="font-family:\'Oxygen\';border-right:0px solid #97989a;position:relative;top:-25px;padding-top:1px;padding-bottom:5px;padding-right:5px;text-align:right;display:inline-block"> filter by </div>  <select name="test_box" id="test_box" style="font-size:12pt;font-family:Lato;position:relative;left:15px;top:-23px;"> <option id="all">all</option> <option id="business">business</option> <option id="fitness">fitness</option> <option id="health">health</option> <option id="home">home</option> <option id="parenting">parenting</option> <option id="personal">personal</option> <option id="politics">politics</option> <option id="realestate">realestate</option> <option id="restaurant">restaurant</option> <option id="sports">sports</option> <option id="video">video</option>  </select> </div> <ul class="ourHolder">  <li class="item" data-id="id-0" data-type="restaurant" onClick="changeSlide(\'agape\');"> <div class="adholder"> <div class="adtitle">Agape Grill</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/agape.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-1" data-type="personal" onClick="changeSlide(\'alliance\');"> <div class="adholder"> <div class="adtitle">Alliance Chiropractic</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/alliance.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-2" data-type="home" onClick="changeSlide(\'allterra\');"> <div class="adholder"> <div class="adtitle">Allterra Solar</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/allterra.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-3" data-type="home" onClick="changeSlide(\'andersonwindow\');"> <div class="adholder"> <div class="adtitle">Renewal by Anderson</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/andersonwindow.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-4" data-type="business" onClick="changeSlide(\'anvil\');"> <div class="adholder"> <div class="adtitle">Anvil Product Development</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/anvil.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-5" data-type="fitness" onClick="changeSlide(\'avac_membership\');"> <div class="adholder"> <div class="adtitle">AVAC Membership</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/avac_membership.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-6" data-type="parenting" onClick="changeSlide(\'avac_swimschool\');"> <div class="adholder"> <div class="adtitle">AVAC Swim</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/avac_swimschool.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-7" data-type="home" onClick="changeSlide(\'aztec\');"> <div class="adholder"> <div class="adtitle">Aztec Solar</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/aztec.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-8" data-type="home" onClick="changeSlide(\'aztec_v2\');"> <div class="adholder"> <div class="adtitle">Aztec Solar</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/aztec_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-9" data-type="sports" onClick="changeSlide(\'bouldercreek\');"> <div class="adholder"> <div class="adtitle">Boulder Creek Country Club</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/bouldercreek.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-10" data-type="realestate" onClick="changeSlide(\'brianbomber\');"> <div class="adholder"> <div class="adtitle">T.Bommer Bryan</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/brianbomber.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-11" data-type="home" onClick="changeSlide(\'calpaint\');"> <div class="adholder"> <div class="adtitle">California Paint Company</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/calpaint.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-12" data-type="health" onClick="changeSlide(\'childrensdental\');"> <div class="adholder"> <div class="adtitle">Children\'s Dental Group</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/childrensdental.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-13" data-type="health" onClick="changeSlide(\'childrensdental_v2\');"> <div class="adholder"> <div class="adtitle">Children\'s Dental Group</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/childrensdental_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-14" data-type="restaurant" onClick="changeSlide(\'cibo\');"> <div class="adholder"> <div class="adtitle">Cibo Restaurant & Bar</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/cibo.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-15" data-type="realestate" onClick="changeSlide(\'city_ventures.v3\');"> <div class="adholder"> <div class="adtitle">City Ventures</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/city_ventures.v3.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-16" data-type="restaurant" onClick="changeSlide(\'clarkes\');"> <div class="adholder"> <div class="adtitle">Clarke\'s Charcoal Broiler</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/clarkes.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-17" data-type="business" onClick="changeSlide(\'commonwealth\');"> <div class="adholder"> <div class="adtitle">Commonwealth Credit Union</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/commonwealth.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-18" data-type="realestate" onClick="changeSlide(\'cottercrubrolle\');"> <div class="adholder"> <div class="adtitle">Cotter Crumeyrolle Group</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/cottercrubrolle.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-19" data-type="realestate" onClick="changeSlide(\'deleon\');"> <div class="adholder"> <div class="adtitle">Deleon Realty</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/deleon.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-20" data-type="realestate" onClick="changeSlide(\'deleon.lindenave\');"> <div class="adholder"> <div class="adtitle">Deleon Realty</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/deleon.lindenave.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-21" data-type="realestate" onClick="changeSlide(\'deleon.miranda\');"> <div class="adholder"> <div class="adtitle">Deleon Realty</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/deleon.miranda.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-22" data-type="home" onClick="changeSlide(\'dover\');"> <div class="adholder"> <div class="adtitle">Dover</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/dover.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-23" data-type="politics" onClick="changeSlide(\'ed_lee\');"> <div class="adholder"> <div class="adtitle">Ed Lee for Mayor SF</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/ed_lee.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-24" data-type="personal" onClick="changeSlide(\'elithomas.v2\');"> <div class="adholder"> <div class="adtitle">Eli Thomas Menswear</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/elithomas.v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-25" data-type="personal" onClick="changeSlide(\'elithomasformen_v1\');"> <div class="adholder"> <div class="adtitle">Eli Thomas for Men</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/elithomasformen_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-26" data-type="personal" onClick="changeSlide(\'elithomasformen_v2\');"> <div class="adholder"> <div class="adtitle">Eli Thomas for Men</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/elithomasformen_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-27" data-type="personal" onClick="changeSlide(\'entrepreur\');"> <div class="adholder"> <div class="adtitle">Entrepreneur Extravaganza</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/entrepreur.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-28" data-type="realestate" onClick="changeSlide(\'eric_fischer-colbrie\');"> <div class="adholder"> <div class="adtitle">Eric Fischer-Colbrie</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/eric_fischer-colbrie.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-29" data-type="sports" onClick="changeSlide(\'fightnight_v1\');"> <div class="adholder"> <div class="adtitle">The Peacock Lounge Fight Night</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/fightnight_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-30" data-type="sports" onClick="changeSlide(\'fightnight_v2\');"> <div class="adholder"> <div class="adtitle">The Peacock Lounge Fight Night</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/fightnight_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-31" data-type="personal" onClick="changeSlide(\'fjwestern\');"> <div class="adholder"> <div class="adtitle">FJ Western & Boot Repair</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/fjwestern.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-32" data-type="politics" onClick="changeSlide(\'foolsandknaves\');"> <div class="adholder"> <div class="adtitle">Fools and Knaves Book</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/foolsandknaves.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-33" data-type="parenting" onClick="changeSlide(\'gms.v3\');"> <div class="adholder"> <div class="adtitle">Girls Middle School</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/gms.v3.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-34" data-type="restaurant" onClick="changeSlide(\'godfathers\');"> <div class="adholder"> <div class="adtitle">Godfathers Burger Lounge</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/godfathers.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-35" data-type="health" onClick="changeSlide(\'grantroad\');"> <div class="adholder"> <div class="adtitle">Grant Road Dental</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/grantroad.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-36" data-type="home" onClick="changeSlide(\'greatmaids_v1\');"> <div class="adholder"> <div class="adtitle">Great Maids</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/greatmaids_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-37" data-type="home" onClick="changeSlide(\'greatmaids_v2\');"> <div class="adholder"> <div class="adtitle">Great Maids</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/greatmaids_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-38" data-type="personal" onClick="changeSlide(\'halfmoonbay\');"> <div class="adholder"> <div class="adtitle">Half Moon Bay</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/halfmoonbay.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-39" data-type="video" onClick="changeSlide(\'halfmoonbay.v3\');"> <div class="adholder"> <div class="adtitle">Halfmoon Bay</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/halfmoonbay.v3.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-40" data-type="personal" onClick="changeSlide(\'halfmoonbay_v2\');"> <div class="adholder"> <div class="adtitle">Half Moon Bay</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/halfmoonbay_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-41" data-type="personal" onClick="changeSlide(\'handson\');"> <div class="adholder"> <div class="adtitle">Hands on Photography</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/handson.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-42" data-type="parenting" onClick="changeSlide(\'harkeracademy\');"> <div class="adholder"> <div class="adtitle">Harker Academy</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/harkeracademy.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-43" data-type="politics" onClick="changeSlide(\'hfh.2\');"> <div class="adholder"> <div class="adtitle">Habitat for Humanity</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/hfh.2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-44" data-type="parenting" onClick="changeSlide(\'huntington\');"> <div class="adholder"> <div class="adtitle">Huntington Tutoring Services</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/huntington.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-45" data-type="realestate" onClick="changeSlide(\'idahoguys\');"> <div class="adholder"> <div class="adtitle">Century 21 Idaho Guys</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/idahoguys.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-46" data-type="realestate" onClick="changeSlide(\'idahoguys2\');"> <div class="adholder"> <div class="adtitle">Century 21 Idaho Guys</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/idahoguys2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-47" data-type="personal" onClick="changeSlide(\'jackson.hole.v7\');"> <div class="adholder"> <div class="adtitle">Jackson Hole</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/jackson.hole.v7.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-48" data-type="sports" onClick="changeSlide(\'jacksonhole\');"> <div class="adholder"> <div class="adtitle">Jackson Hole</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/jacksonhole.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-49" data-type="personal" onClick="changeSlide(\'jacksonhole2\');"> <div class="adholder"> <div class="adtitle">Jackson Hole</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/jacksonhole2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-50" data-type="business" onClick="changeSlide(\'jassim\');"> <div class="adholder"> <div class="adtitle">Jassim Law APC</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/jassim.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-51" data-type="politics" onClick="changeSlide(\'jim_davis\');"> <div class="adholder"> <div class="adtitle">Jim Davis City Council</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/jim_davis.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-52" data-type="health" onClick="changeSlide(\'joelee\');"> <div class="adholder"> <div class="adtitle">Joe Lee DDS</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/joelee.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-53" data-type="politics" onClick="changeSlide(\'john_marchand\');"> <div class="adholder"> <div class="adtitle">John Marchand for Mayor</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/john_marchand.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-54" data-type="home" onClick="changeSlide(\'jts\');"> <div class="adholder"> <div class="adtitle">JTS Tree Service</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/jts.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-55" data-type="home" onClick="changeSlide(\'kellyhome\');"> <div class="adholder"> <div class="adtitle">Kelly Home Builders</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/kellyhome.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-56" data-type="parenting" onClick="changeSlide(\'kidspark\');"> <div class="adholder"> <div class="adtitle">Kids Park</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/kidspark.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-57" data-type="personal" onClick="changeSlide(\'knty_v1\');"> <div class="adholder"> <div class="adtitle">101.9 The Wolf</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/knty_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-58" data-type="personal" onClick="changeSlide(\'knty_v2\');"> <div class="adholder"> <div class="adtitle">101.9 The Wolf</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/knty_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-59" data-type="home" onClick="changeSlide(\'landfour\');"> <div class="adholder"> <div class="adtitle">Land Four</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/landfour.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-60" data-type="home" onClick="changeSlide(\'landfour2\');"> <div class="adholder"> <div class="adtitle">Land Four</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/landfour2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-61" data-type="realestate" onClick="changeSlide(\'lbs\');"> <div class="adholder"> <div class="adtitle">LBS Credit Union</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/lbs.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-62" data-type="personal" onClick="changeSlide(\'lespetitscadeaux\');"> <div class="adholder"> <div class="adtitle">Les Petits Cadeaux</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/lespetitscadeaux.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-63" data-type="fitness" onClick="changeSlide(\'losgatosfitness_v1\');"> <div class="adholder"> <div class="adtitle">Los Gatos Fitness</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/losgatosfitness_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-64" data-type="fitness" onClick="changeSlide(\'losgatosfitness_v2\');"> <div class="adholder"> <div class="adtitle">Los Gatos Fitness</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/losgatosfitness_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-65" data-type="fitness" onClick="changeSlide(\'losgatosfitness_v3\');"> <div class="adholder"> <div class="adtitle">Los Gatos Fitness</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/losgatosfitness_v3.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-66" data-type="personal" onClick="changeSlide(\'miramar\');"> <div class="adholder"> <div class="adtitle">Miramar Events</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/miramar.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-67" data-type="home" onClick="changeSlide(\'myclean\');"> <div class="adholder"> <div class="adtitle">MyClean.com</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/myclean.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-68" data-type="personal" onClick="changeSlide(\'mythbusters\');"> <div class="adholder"> <div class="adtitle">Myth Busters</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/mythbusters.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-69" data-type="personal" onClick="changeSlide(\'ndnu\');"> <div class="adholder"> <div class="adtitle">Notre Dame De Namur</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/ndnu.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-70" data-type="personal" onClick="changeSlide(\'nightlife_ttm\');"> <div class="adholder"> <div class="adtitle">Late Night Tech</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/nightlife_ttm.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-71" data-type="restaurant" onClick="changeSlide(\'peacock_v1\');"> <div class="adholder"> <div class="adtitle">The Peacock Lounge</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/peacock_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-72" data-type="restaurant" onClick="changeSlide(\'peacock_v2\');"> <div class="adholder"> <div class="adtitle">The Peacock Lounge</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/peacock_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-73" data-type="personal" onClick="changeSlide(\'peak\');"> <div class="adholder"> <div class="adtitle">Peak Travel</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/peak.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-74" data-type="personal" onClick="changeSlide(\'pjcc.2\');"> <div class="adholder"> <div class="adtitle">PJCC</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/pjcc.2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-75" data-type="parenting" onClick="changeSlide(\'premier\');"> <div class="adholder"> <div class="adtitle">Premier Driving School</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/premier.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-76" data-type="politics" onClick="changeSlide(\'prop_a\');"> <div class="adholder"> <div class="adtitle">SF Ballot Proposition A </div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/prop_a.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-77" data-type="politics" onClick="changeSlide(\'prop_c\');"> <div class="adholder"> <div class="adtitle">SF Ballot Proposition C </div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/prop_c.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-78" data-type="health" onClick="changeSlide(\'provincial\');"> <div class="adholder"> <div class="adtitle">Provincial Insurance</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/provincial.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-79" data-type="business" onClick="changeSlide(\'quinlin\');"> <div class="adholder"> <div class="adtitle">Matt Quinlin</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/quinlin.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-80" data-type="video" onClick="changeSlide(\'real-salt-lake\');"> <div class="adholder"> <div class="adtitle">Real Salt Lake MLS</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/real-salt-lake.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-81" data-type="politics" onClick="changeSlide(\'ross_mirkarimi\');"> <div class="adholder"> <div class="adtitle">Ross Mirkarimi for Sheriff, San Francisco 2011</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/ross_mirkarimi.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-82" data-type="sports" onClick="changeSlide(\'san_juanoaks\');"> <div class="adholder"> <div class="adtitle">San Jaun Oaks Golf Course</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/san_juanoaks.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-83" data-type="home" onClick="changeSlide(\'sc41_v1\');"> <div class="adholder"> <div class="adtitle">SC41 Furniture</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/sc41_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-84" data-type="home" onClick="changeSlide(\'sc41_v2\');"> <div class="adholder"> <div class="adtitle">SC41 Furniture</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/sc41_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-85" data-type="health" onClick="changeSlide(\'scdentist\');"> <div class="adholder"> <div class="adtitle">Dr. Matiasevich Jr., DDS</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/scdentist.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-86" data-type="restaurant" onClick="changeSlide(\'scotts_seafood\');"> <div class="adholder"> <div class="adtitle">Scott\'s Seafood</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/scotts_seafood.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-87" data-type="health" onClick="changeSlide(\'skinspirit\');"> <div class="adholder"> <div class="adtitle">Skin Spirit</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/skinspirit.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-88" data-type="home" onClick="changeSlide(\'solarworks\');"> <div class="adholder"> <div class="adtitle">Solar Works</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/solarworks.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-89" data-type="parenting" onClick="changeSlide(\'stanford\');"> <div class="adholder"> <div class="adtitle">Stanford Driving School</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/stanford.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-90" data-type="politics" onClick="changeSlide(\'steve_okamoto\');"> <div class="adholder"> <div class="adtitle">Steve Okamoto for City Council</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/steve_okamoto.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-91" data-type="restaurant" onClick="changeSlide(\'tastefull_v1\');"> <div class="adholder"> <div class="adtitle">Taste-Full Events</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/tastefull_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-92" data-type="restaurant" onClick="changeSlide(\'tastefull_v2\');"> <div class="adholder"> <div class="adtitle">Taste-Full Events</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/tastefull_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-93" data-type="restaurant" onClick="changeSlide(\'tastefull_v3\');"> <div class="adholder"> <div class="adtitle">Taste-Full Events</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/tastefull_v3.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-94" data-type="politics" onClick="changeSlide(\'terry_nagel\');"> <div class="adholder"> <div class="adtitle">Terry NagelCity Council</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/terry_nagel.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-95" data-type="parenting" onClick="changeSlide(\'thetech\');"> <div class="adholder"> <div class="adtitle">The Tech Museum</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/thetech.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-96" data-type="fitness" onClick="changeSlide(\'toadal_v1\');"> <div class="adholder"> <div class="adtitle">Toadal Fitness</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/toadal_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-97" data-type="fitness" onClick="changeSlide(\'toadal_v2\');"> <div class="adholder"> <div class="adtitle">Toadal Fitness</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/toadal_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-98" data-type="parenting" onClick="changeSlide(\'valleychristian\');"> <div class="adholder"> <div class="adtitle">Valley Christan Schools</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/valleychristian.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-99" data-type="home" onClick="changeSlide(\'vca_v1\');"> <div class="adholder"> <div class="adtitle">VCA Animal Clinics</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/vca_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-100" data-type="home" onClick="changeSlide(\'vca_v2\');"> <div class="adholder"> <div class="adtitle">VCA Animal Clinics</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/vca_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-101" data-type="home" onClick="changeSlide(\'vca_v3\');"> <div class="adholder"> <div class="adtitle">VCA Animal Clinics</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/vca_v3.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-102" data-type="video" onClick="changeSlide(\'video.deleon\');"> <div class="adholder"> <div class="adtitle">Deleon Realty</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/video.deleon.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-103" data-type="video" onClick="changeSlide(\'video.deleon.lindenave\');"> <div class="adholder"> <div class="adtitle">Deleon Realty</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/video.deleon.lindenave.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-104" data-type="video" onClick="changeSlide(\'video.deleon.miranda\');"> <div class="adholder"> <div class="adtitle">Deleon Realty</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/video.deleon.miranda.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-105" data-type="video" onClick="changeSlide(\'video.godfathers\');"> <div class="adholder"> <div class="adtitle">Godfathers Burger Lounge</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/video.godfathers.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-106" data-type="politics" onClick="changeSlide(\'warren_furutani\');"> <div class="adholder"> <div class="adtitle">Warren Furutani City Council</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/warren_furutani.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-107" data-type="fitness" onClick="changeSlide(\'westernathletic_v1\');"> <div class="adholder"> <div class="adtitle">Western Athletic Club</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/westernathletic_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-108" data-type="fitness" onClick="changeSlide(\'westernathletic_v2\');"> <div class="adholder"> <div class="adtitle">Western Athletic Club</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/westernathletic_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-109" data-type="restaurant" onClick="changeSlide(\'wholefoods_v1\');"> <div class="adholder"> <div class="adtitle">Whole Foods Redwood City</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/wholefoods_v1.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-110" data-type="restaurant" onClick="changeSlide(\'wholefoods_v2\');"> <div class="adholder"> <div class="adtitle">Whole Foods Redwood City</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/wholefoods_v2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-111" data-type="restaurant" onClick="changeSlide(\'wildhorse.2\');"> <div class="adholder"> <div class="adtitle">Wild Horse Cafe</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/wildhorse.2.jpg" width="200" height="100"></div> </div></li>  <li class="item" data-id="id-112" data-type="parenting" onClick="changeSlide(\'wushu.v4\');"> <div class="adholder"> <div class="adtitle">Wushu Martial Arts</div> <div class="adimage"><span class="rollover" ></span><img src="images/thumbs/wushu.v4.jpg" width="200" height="100"></div> </div></li> </ul> <div class="clear"></div>';

	document.getElementById('body_header').innerHTML = 'Ad Library <a id="notes_toggle_link" style="float:right;" href="javascript:toggle_notes()"><img src="/images/notes_PNG.png" /><div align="right" style="float:right;width:25px;"> +</div></a>';

  // get the action filter option item on page load
  var $filterType = $('#filterOptions li.active a').attr('class');
	
  // get and assign the ourHolder element to the
	// $holder varible for use later
  var $holder = $('ul.ourHolder');

  // clone all items within the pre-assigned $holder element
  var $data = $holder.clone();

	$('#test_box').change(function() {
		// reset the active class on all the buttons
		var id = $(this).find("option:selected").attr("id");
		// assign the class of the clicked filter option
		// element to our $filterType variable
		var $filterType = id;
		$filteredData = "";
		if ($filterType == 'all') {
			// assign all li items to the $filteredData var when
			// the 'All' filter option is clicked
			var $filteredData = $data.find('li');
		} 
		else {
			// find all li elements that have our required $filterType
			// values for the data-type element
			var $filteredData = $data.find('li[data-type=' + $filterType + ']');
		}
		// call quicksand and assign transition parameters
		$holder.quicksand($filteredData, {
			duration: 800,
			easing: 'easeInOutQuad'
		});
		return false;
	});
    //tracker();
}
*/
/*

Quicksand 1.2.2

Reorder and filter items with a nice shuffling animation.

Copyright (c) 2010 Jacek Galanciak (razorjack.net) and agilope.com
Big thanks for Piotr Petrus (riddle.pl) for deep code review and wonderful docs & demos.

Dual licensed under the MIT and GPL version 2 licenses.
http://github.com/jquery/jquery/blob/master/MIT-LICENSE.txt
http://github.com/jquery/jquery/blob/master/GPL-LICENSE.txt

Project site: http://razorjack.net/quicksand
Github site: http://github.com/razorjack/quicksand

*/

(function ($) {
    $.fn.quicksand = function (collection, customOptions) {     
        var options = {
            duration: 750,
            easing: 'swing',
            attribute: 'data-id', // attribute to recognize same items within source and dest
            adjustHeight: 'auto', // 'dynamic' animates height during shuffling (slow), 'auto' adjusts it before or after the animation, false leaves height constant
            useScaling: true, // disable it if you're not using scaling effect or want to improve performance
            enhancement: function(c) {}, // Visual enhacement (eg. font replacement) function for cloned elements
            selector: '> *',
            dx: 0,
            dy: 0
        };
        $.extend(options, customOptions);
        
        if ($.browser.msie || (typeof($.fn.scale) == 'undefined')) {
            // Got IE and want scaling effect? Kiss my ass.
            options.useScaling = false;
        }
        
        var callbackFunction;
        if (typeof(arguments[1]) == 'function') {
            var callbackFunction = arguments[1];
        } else if (typeof(arguments[2] == 'function')) {
            var callbackFunction = arguments[2];
        }
    
        
        return this.each(function (i) {
            var val;
            var animationQueue = []; // used to store all the animation params before starting the animation; solves initial animation slowdowns
            var $collection = $(collection).clone(); // destination (target) collection
            var $sourceParent = $(this); // source, the visible container of source collection
            var sourceHeight = $(this).css('height'); // used to keep height and document flow during the animation
            
            var destHeight;
            var adjustHeightOnCallback = false;
            
            var offset = $($sourceParent).offset(); // offset of visible container, used in animation calculations
            var offsets = []; // coordinates of every source collection item            
            
            var $source = $(this).find(options.selector); // source collection items
            
            // Replace the collection and quit if IE6
            if ($.browser.msie && $.browser.version.substr(0,1)<7) {
                $sourceParent.html('').append($collection);
                return;
            }

            // Gets called when any animation is finished
            var postCallbackPerformed = 0; // prevents the function from being called more than one time
            var postCallback = function () {
                
                if (!postCallbackPerformed) {
                    postCallbackPerformed = 1;
                    
                    // hack: 
                    // used to be: $sourceParent.html($dest.html()); // put target HTML into visible source container
                    // but new webkit builds cause flickering when replacing the collections
                    $toDelete = $sourceParent.find('> *');
                    $sourceParent.prepend($dest.find('> *'));
                    $toDelete.remove();
                         
                    if (adjustHeightOnCallback) {
                        $sourceParent.css('height', destHeight);
                    }
                    options.enhancement($sourceParent); // Perform custom visual enhancements on a newly replaced collection
                    if (typeof callbackFunction == 'function') {
                        callbackFunction.call(this);
                    }                    
                }
            };
            
            // Position: relative situations
            var $correctionParent = $sourceParent.offsetParent();
            var correctionOffset = $correctionParent.offset();
            if ($correctionParent.css('position') == 'relative') {
                if ($correctionParent.get(0).nodeName.toLowerCase() == 'body') {

                } else {
                    correctionOffset.top += (parseFloat($correctionParent.css('border-top-width')) || 0);
                    correctionOffset.left +=( parseFloat($correctionParent.css('border-left-width')) || 0);
                }
            } else {
                correctionOffset.top -= (parseFloat($correctionParent.css('border-top-width')) || 0);
                correctionOffset.left -= (parseFloat($correctionParent.css('border-left-width')) || 0);
                correctionOffset.top -= (parseFloat($correctionParent.css('margin-top')) || 0);
                correctionOffset.left -= (parseFloat($correctionParent.css('margin-left')) || 0);
            }
            
            // perform custom corrections from options (use when Quicksand fails to detect proper correction)
            if (isNaN(correctionOffset.left)) {
                correctionOffset.left = 0;
            }
            if (isNaN(correctionOffset.top)) {
                correctionOffset.top = 0;
            }
            
            correctionOffset.left -= options.dx;
            correctionOffset.top -= options.dy;

            // keeps nodes after source container, holding their position
            $sourceParent.css('height', $(this).height());
            
            // get positions of source collections
            $source.each(function (i) {
                offsets[i] = $(this).offset();
            });
            
            // stops previous animations on source container
            $(this).stop();
            var dx = 0; var dy = 0;
            $source.each(function (i) {
                $(this).stop(); // stop animation of collection items
                var rawObj = $(this).get(0);
                if (rawObj.style.position == 'absolute') {
                    dx = -options.dx;
                    dy = -options.dy;
                } else {
                    dx = options.dx;
                    dy = options.dy;                    
                }

                rawObj.style.position = 'absolute';
                rawObj.style.margin = '0';

                rawObj.style.top = (offsets[i].top - parseFloat(rawObj.style.marginTop) - correctionOffset.top + dy) + 'px';
                rawObj.style.left = (offsets[i].left - parseFloat(rawObj.style.marginLeft) - correctionOffset.left + dx) + 'px';
            });
                    
            // create temporary container with destination collection
            var $dest = $($sourceParent).clone();
            var rawDest = $dest.get(0);
            rawDest.innerHTML = '';
            rawDest.setAttribute('id', '');
            rawDest.style.height = 'auto';
            rawDest.style.width = $sourceParent.width() + 'px';
            $dest.append($collection);      
            // insert node into HTML
            // Note that the node is under visible source container in the exactly same position
            // The browser render all the items without showing them (opacity: 0.0)
            // No offset calculations are needed, the browser just extracts position from underlayered destination items
            // and sets animation to destination positions.
            $dest.insertBefore($sourceParent);
            $dest.css('opacity', 0.0);
            rawDest.style.zIndex = -1;
            
            rawDest.style.margin = '0';
            rawDest.style.position = 'absolute';
            rawDest.style.top = offset.top - correctionOffset.top + 'px';
            rawDest.style.left = offset.left - correctionOffset.left + 'px';
            
            
    
            

            if (options.adjustHeight === 'dynamic') {
                // If destination container has different height than source container
                // the height can be animated, adjusting it to destination height
                $sourceParent.animate({height: $dest.height()}, options.duration, options.easing);
            } else if (options.adjustHeight === 'auto') {
                destHeight = $dest.height();
                if (parseFloat(sourceHeight) < parseFloat(destHeight)) {
                    // Adjust the height now so that the items don't move out of the container
                    $sourceParent.css('height', destHeight);
                } else {
                    //  Adjust later, on callback
                    adjustHeightOnCallback = true;
                }
            }
                
            // Now it's time to do shuffling animation
            // First of all, we need to identify same elements within source and destination collections    
            $source.each(function (i) {
                var destElement = [];
                if (typeof(options.attribute) == 'function') {
                    
                    val = options.attribute($(this));
                    $collection.each(function() {
                        if (options.attribute(this) == val) {
                            destElement = $(this);
                            return false;
                        }
                    });
                } else {
                    destElement = $collection.filter('[' + options.attribute + '=' + $(this).attr(options.attribute) + ']');
                }
                if (destElement.length) {
                    // The item is both in source and destination collections
                    // It it's under different position, let's move it
                    if (!options.useScaling) {
                        animationQueue.push(
                                            {
                                                element: $(this), 
                                                animation: 
                                                    {top: destElement.offset().top - correctionOffset.top, 
                                                     left: destElement.offset().left - correctionOffset.left, 
                                                     opacity: 1.0
                                                    }
                                            });

                    } else {
                        animationQueue.push({
                                            element: $(this), 
                                            animation: {top: destElement.offset().top - correctionOffset.top, 
                                                        left: destElement.offset().left - correctionOffset.left, 
                                                        opacity: 1.0, 
                                                        scale: '1.0'
                                                       }
                                            });

                    }
                } else {
                    // The item from source collection is not present in destination collections
                    // Let's remove it
                    if (!options.useScaling) {
                        animationQueue.push({element: $(this), 
                                             animation: {opacity: '0.0'}});
                    } else {
                        animationQueue.push({element: $(this), animation: {opacity: '0.0', 
                                         scale: '0.0'}});
                    }
                }
            });
            
            $collection.each(function (i) {
                // Grab all items from target collection not present in visible source collection
                
                var sourceElement = [];
                var destElement = [];
                if (typeof(options.attribute) == 'function') {
                    val = options.attribute($(this));
                    $source.each(function() {
                        if (options.attribute(this) == val) {
                            sourceElement = $(this);
                            return false;
                        }
                    });                 

                    $collection.each(function() {
                        if (options.attribute(this) == val) {
                            destElement = $(this);
                            return false;
                        }
                    });
                } else {
                    sourceElement = $source.filter('[' + options.attribute + '=' + $(this).attr(options.attribute) + ']');
                    destElement = $collection.filter('[' + options.attribute + '=' + $(this).attr(options.attribute) + ']');
                }
                
                var animationOptions;
                if (sourceElement.length === 0) {
                    // No such element in source collection...
                    if (!options.useScaling) {
                        animationOptions = {
                            opacity: '1.0'
                        };
                    } else {
                        animationOptions = {
                            opacity: '1.0',
                            scale: '1.0'
                        };
                    }
                    // Let's create it
                    d = destElement.clone();
                    var rawDestElement = d.get(0);
                    rawDestElement.style.position = 'absolute';
                    rawDestElement.style.margin = '0';
                    rawDestElement.style.top = destElement.offset().top - correctionOffset.top + 'px';
                    rawDestElement.style.left = destElement.offset().left - correctionOffset.left + 'px';
                    d.css('opacity', 0.0); // IE
                    if (options.useScaling) {
                        d.css('transform', 'scale(0.0)');
                    }
                    d.appendTo($sourceParent);
                    
                    animationQueue.push({element: $(d), 
                                         animation: animationOptions});
                }
            });
            
            $dest.remove();
            options.enhancement($sourceParent); // Perform custom visual enhancements during the animation
            for (i = 0; i < animationQueue.length; i++) {
                animationQueue[i].element.animate(animationQueue[i].animation, options.duration, options.easing, postCallback);
            }
        });
    };
})(jQuery);

//paint_geo: Function to call and draw Geo Targeting information
function paint_geo(){
//ShowGeo();
		document.getElementById("ghost_title").innerHTML='Geographic Targeting';
              
//document.getElementById('ghost_title').innerHTML='Geographic Targeting';
                if(is_planner_showing)
                {
                    save_notes();

                        dismiss_notes();

                }
                if(is_planner_showing_geo)
                {
                    save_notes_geo();
                    
                }
                dismiss_notes_geo();
  menu_index = 0;  
  $('span#geospan').css('display', 'inline');
  $('span#notgeo').css('display', 'none');
	if($("div.gridRight_geo").css('marginLeft') != '0px') {
		slideOut_geo();
	}
        
        
  ////tracker();
}
        //init_note_toggle() and init_note_toggle_geo() quick-fix a view's notes state to false.
        function init_note_toggle()
        {
            is_planner_showing = false;
        }
        
        function init_note_toggle_geo()
        {
            is_planner_showing_geo = false;
        }
        
        //toggle_notes() toggles visibility for items and sets flag to display or not display notes information
        function toggle_notes()
        {
            if (document.getElementById("backButton").style.visibility != 'hidden')
            {
            lap_back();
            }
            if(!is_planner_showing)
            {
                document.getElementById("notes_notif").innerHTML = "";
                document.getElementById("advertiser").value = advertiser_field;
                document.getElementById("plan_name").value = ad_plan_name_field;
                document.getElementById("ad_notes").value = notes_field;
                document.getElementById("ad_notes").innerHTML = notes_field;
                slideIn(slideOut,"dummy");
                setTimeout(function() {
                    document.getElementById("sliderHeaderContent").style.visibility = 'hidden';
                    document.getElementById("sliderBodyContent").style.visibility = 'hidden';
                    document.getElementById("notes_header").style.visibility = 'visible';
                    document.getElementById("notes_body").style.visibility = 'visible';

                    
                },400);
                is_planner_showing = true;
               
                document.getElementById("notes_toggle_link").innerHTML = "<img src='/images/notes_PNG.png' /><div align='right' style='float:right;width:25px;'> -</div>";

            }
            else
            {
                slideIn(slideOut,"dummy");
                setTimeout(function() {
                    document.getElementById("sliderHeaderContent").style.visibility = 'visible';
                    document.getElementById("sliderBodyContent").style.visibility = 'visible';
                    document.getElementById("notes_header").style.visibility = 'hidden';                    
                    document.getElementById("notes_body").style.visibility = 'hidden';
                },400);
                save_notes();
                is_planner_showing = false;
                document.getElementById("notes_toggle_link").innerHTML = "<img src='/images/notes_PNG.png' /><div align='right' style='float:right;width:25px;'> +</div>";
            }
        }
     
     //dismiss_notes(_geo)() hides notes without saving
    function dismiss_notes()
    { 
        document.getElementById("sliderHeaderContent").style.visibility = 'visible';
        document.getElementById("sliderBodyContent").style.visibility = 'visible';
        document.getElementById("notes_header").style.visibility = 'hidden';                    
        document.getElementById("notes_body").style.visibility = 'hidden';
        document.getElementById("notes_toggle_link").innerHTML = "<img src='/images/notes_PNG.png' /><div align='right' style='float:right;width:25px;'> +</div>";
        is_planner_showing = false;

    }
        function dismiss_notes_geo()
    { 
        document.getElementById("sliderHeaderContent_geo").style.visibility = 'visible';
        document.getElementById("sliderBodyContent_geo").style.visibility = 'visible';
        document.getElementById("notes_header_geo").style.visibility = 'hidden';
        document.getElementById("notes_toggle_link_geo").innerHTML = "<img src='/images/notes_PNG.png' /><div align='right' style='float:right;width:25px;'> +</div>";
        document.getElementById("notes_body_geo").style.visibility = 'hidden';

        is_planner_showing_geo = false;

    }    
 //deploy_lap_summary
 //Probably the only function that doesn't have a geo variant
 //Deploys notes screen with LAP saving elements on it. 
function deploy_lap_summary(){
	notes_header_id = '';
        notes_body_id = '';
        
        if(menu_index === 0)
        {
            save_notes_geo();
            if(!is_planner_showing_geo)
            {
                toggle_notes_geo();
                setTimeout(function() {
                    document.getElementById("notes_header_geo").innerHTML = '<span>SUBMIT LAP</span>';
                    document.getElementById("submitButton_geo").style.visibility = 'visible';
                    document.getElementById("backButton_geo").style.visibility = 'visible';
		    document.getElementById("saveasButton_geo").style.visibility = 'visible';
                },400);
            } else {
            slideIn(slideOut,"DUMMY");
            setTimeout(function() {
                
                document.getElementById("notes_header_geo").innerHTML = '<span>SUBMIT LAP</span>';
                document.getElementById("submitButton_geo").style.visibility = 'visible';
		document.getElementById("backButton_geo").style.visibility = 'visible';
		document.getElementById("saveasButton_geo").style.visibility = 'visible';
                },400);
            } 
        }
        else 
        { 
            save_notes();
            if(!is_planner_showing)
            {
                toggle_notes();
                setTimeout(function() {
                    document.getElementById("notes_header").innerHTML = '<span>SUBMIT LAP</span>';
                    document.getElementById("submitButton").style.visibility = 'visible';
                    document.getElementById("backButton").style.visibility = 'visible';
		    document.getElementById("saveasButton").style.visibility = 'visible';
                },400);
            } 
						else 
						{
             slideIn(slideOut,"DUMMY");            
             setTimeout(function() {
                 document.getElementById("notes_header").innerHTML = '<span>SUBMIT LAP</span>';
                 document.getElementById("submitButton").style.visibility = 'visible';
                 document.getElementById("backButton").style.visibility = 'visible';
		 document.getElementById("saveasButton").style.visibility = 'visible';
                },400);
            }
        }
}

//lap_back hide 'Save LAP' elements and display a boring notes page
    function lap_back_geo()
    {
            slideIn(slideOut,"dummy");
            setTimeout(function() {
                document.getElementById("notes_header_geo").innerHTML = '<span></span>';
                document.getElementById("submitButton_geo").style.visibility = 'hidden';
                document.getElementById("backButton_geo").style.visibility = 'hidden';
		document.getElementById("saveasButton_geo").style.visibility = 'hidden';
            },400);
    }

    function lap_back()
    {
            slideIn(slideOut,"dummy");
            setTimeout(function() {
                document.getElementById("notes_header").innerHTML = '<span></span>';
                document.getElementById("submitButton").style.visibility = 'hidden';
                document.getElementById("backButton").style.visibility = 'hidden';
		document.getElementById("saveasButton").style.visibility = 'hidden';
            }, 400);
    }

// function tracker()
// {
// var owa_baseUrl = 'http://www.vantagelocal.com/owa/';
// var owa_cmds = owa_cmds || [];
// owa_cmds.push(['setSiteId', '08f08836beddd8ac2f7e1d178d28532f']);
// owa_cmds.push(['trackPageView']);
// owa_cmds.push(['trackClicks']);
// owa_cmds.push(['trackDomStream']);
// (function() {
// var _owa = document.createElement('script'); _owa.type = 'text/javascript'; _owa.async = true;
// owa_baseUrl = ('https:' == document.location.protocol ? window.owa_baseSecUrl || owa_baseUrl.replace(/http:/, 'https:') : owa_baseUrl );
// _owa.src = owa_baseUrl + 'modules/base/js/owa.tracker-combined-min.js';
// var _owa_s = document.getElementsByTagName('script')[0]; _owa_s.parentNode.insertBefore(_owa, _owa_s);
// }());
// }
/**
 * loads the information saved in the media_session table and uses it to prepopulate the wufoo form which will then be sent to Matthias
 */

// initialize_notes() sets the starting notes variable when loading from a lap
function initialize_js_notes(advertiser_data, ad_plan_name_data, notes_data)
{
     advertiser_field = advertiser_data;
     ad_plan_name_field = ad_plan_name_data;
     notes_field = notes_data;
}

function initialize_html_notes()
{
	var existenceCheck =  document.getElementById('advertiser');
	if (typeof(existenceCheck) != 'undefined' && existenceCheck != null)
	{
		document.getElementById("advertiser").value = advertiser_field;
		document.getElementById("plan_name").value = ad_plan_name_field;
		document.getElementById("ad_notes").value = notes_field;
	}
	else
	{
		alert("Attempting to call initialize_html_notes() before the DOM elements exist.");
	}
}

function initialize_html_geo_notes()
{
	var existenceCheck =  document.getElementById('advertiser_geo');
	if (typeof(existenceCheck) != 'undefined' && existenceCheck != null)
	{
		document.getElementById("advertiser_geo").value = advertiser_field;
		document.getElementById("plan_name_geo").value = ad_plan_name_field;
		document.getElementById("notes_geo").value = notes_field;
	}
	else
	{
		alert("Attempting to call initialize_html_geo_notes() before the DOM elements exist.");
	}
}
 
 //save_notes() - Save text stored in notes fields into holding variables for this session
 function save_notes()
 {
     advertiser_field = document.getElementById("advertiser").value;
     ad_plan_name_field = document.getElementById("plan_name").value;
     notes_field = document.getElementById("ad_notes").value;
     //document.getElementById("notes_notif").innerHTML = "Saved.";
 }
 function save_notes_geo()
 {
     advertiser_field = document.getElementById("advertiser_geo").value;
     ad_plan_name_field = document.getElementById("plan_name_geo").value;
     notes_field = document.getElementById("notes_geo").value;
     //document.getElementById("notes_notif_geo").innerHTML = "Saved.";
 }
function saveas_lap()
{
    $.ajax({
	type: "POST",
	url: '/lap_lite/switch_media_plan/'
    }).success(function(data){
	if(data == "win")
	{
	    save_lap();
	}
	else
	{
	    alert("Error 5520: Error retrieving media plan session");
	}
    }).error(function(jqXHR, textStatus, errorThrown){
	alert("Error 5519: Error calling save as function");
	console.log(jqXHR);
	console.log(textStatus);
	console.log(errorThrown);
    });
    
}
function saveas_lap_geo()
{
    $.ajax({
	type: "POST",
	url: '/lap_lite/switch_media_plan/'
    }).success(function(data){
	if(data == "win")
	{
	    save_lap_geo();
	}
	else
	{
	    alert("Error 5521: Error retrieving media plan session");
	}
    }).error(function(jqXHR, textStatus, errorThrown){
	alert("Error 5519: Error calling save as function");
	console.log(jqXHR);
	console.log(textStatus);
	console.log(errorThrown);
    });
}
function save_lap() {
	// check if all required fields are properly filled.
	var errorText = "";
	if(document.getElementById("advertiser").value == "") {
		errorText= errorText + "Advertiser Name Empty<br>";
	}
	if(document.getElementById("plan_name").value == "") {
		errorText = errorText + "Ad Plan Name Empty<br>";
	}
	if(document.getElementById("ad_notes").value == "") {
		errorText = errorText + "Notes Empty<br>";
	}
	if (errorText != "") {
		// then there are errors
		document.getElementById('errorspan').innerHTML = errorText;
		return;
	}
	//////////////////WRITE OUT DATA TO PROPGEN////////////////////////
	var url = '/lap_lite/load_advertiser_data'; //' + document.getElementById("advertiser").value + "/" + document.getElementById("plan_name").value + "/" + document.getElementById("notes").value;
	var advertiser = document.getElementById("advertiser").value;
	var planName = document.getElementById("plan_name").value;
	var notes = document.getElementById("ad_notes").value;
	$.ajax({
		type: "POST",
		url: url,
		dataType: 'json',
		data: {
			advertiser: advertiser,
			planName: planName,
			notes: notes
		},
	})
	.success(function(data, textStatus, jqXhr) {
		// asynchronous callback on successful loading.
		window.location.href = '/planner';
	})
	.error(function(jqXhr, testStatus, errorThrown) {
		alert("load_advertiser_data() failed."+errorThrown);
	})
	;

}


function save_lap_geo() {
	// check if all required fields are properly filled.
	var errorText = "";
	if(document.getElementById("advertiser_geo").value == "") {
		errorText= errorText + "Advertiser Name Empty<br>";
	}
	if(document.getElementById("plan_name_geo").value == "") {
		errorText = errorText + "Ad Plan Name Empty<br>";
	}
	if(document.getElementById("notes_geo").value == "") {
		errorText = errorText + "Notes Empty<br>";
	}
	if (errorText != "") {
		// then there are errors
		document.getElementById('errorspan_geo').innerHTML = errorText;
		return;
	}
	//////////////////WRITE OUT DATA TO PROPGEN////////////////////////
	var url = '/lap_lite/load_advertiser_data'; //' + document.getElementById("advertiser").value + "/" + document.getElementById("plan_name").value + "/" + document.getElementById("notes").value;
	var advertiser = document.getElementById("advertiser_geo").value;
	var planName = document.getElementById("plan_name_geo").value;
	var notes = document.getElementById("notes_geo").value;
	$.ajax({
		type: "POST",
		url: url,
		dataType: 'json',
		data: {
			advertiser: advertiser,
			planName: planName,
			notes: notes
		},
	})
	.success(function(data, textStatus, jqXhr) {
		// asynchronous callback on successful loading.
		window.location.href = '/planner';
	})
	.error(function(jqXhr, testStatus, errorThrown) {
		alert("load_advertiser_data() failed."+errorThrown);
	})
	;

}












