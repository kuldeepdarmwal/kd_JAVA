$(function(){
	
		$(".adimage").preloadify({ imagedelay:100 });
	
	$("#restart").click(function(e){
		$(".adimage").preloadify({ imagedelay:50 }); e.preventDefault();
		});
	
	});
	
function slideOut() {
    $("div.gridRight").css("display", "inline");
    $("div.gridRight").animate({ left: "0px" }); 
    //$("div.slide").toggle();
}
function slideIn() {
	$("div.gridRight").animate({ left: "-400px" }, {complete: function() {$("div.gridRight").css("display", "none");}});
}
function changeSlide(src, name){ 
	alert("hello world");

	if($("div.gridRight").is(':visible')){
		slideIn();
	}

	document.getElementById("slideOut").innerHTML = '<div class="widget">'+
														'<div class="header"><span> '+name+' | Creative </span></div>' + 
														'<div class="content">' +
															'<p>Application data for the slider to be displayed here</p>'+
															'<embed src="client/'+src+'/flash_300x250.swf" wmode="opaque" width="300" height="250" name="yourmovie" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />'+
															'<a href="client/'+src+'/index.html" target="_blank"><button>View All Sizes</button></a>'+
															'<a href="client/'+src+'/popup.html" target="_blank"><button>Sample Placement</button></a>'+
														'</div>'+
													'</div>';	
	slideOut();

}

