$(document).ready(function(){
    $('div.demographicsheader').click(function(){
	$("div.gridRight").css("display", "inline");
        $("div.gridRight").animate({ left: "0px" }); 
        $("div.demographicsheader").toggle();
    });
    $("div#collapse").click(function(){
    	$("div.gridRight").animate({ left: "-400px" }, {complete: function() {$("div.gridRight").css("display", "none");}});
    });
});

$(document).ready(function(){
	$('div#open').click(function(){
		$("div.gridRight").css("display", "inline");
		$("div.gridRight").animate({ left: "0px" }); 
		$("div.slide").toggle();
	});
	$("div#collapse").click(function(){
		$("div.gridRight").animate({ left: "-400px" }, {complete: function() {$("div.gridRight").css("display", "none");}});
		$("div.slide").toggle();
	});
});
