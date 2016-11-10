$(document).ready(function(){

    // Get url location
    
    var _loc = window.location ;
    if(_loc.pathname.indexOf('coupon') != -1  && _loc.search.indexOf('mode=test') != -1  ){
	// var url = _loc.protocol + '//' + _loc.host + _loc.port  + _loc.pathname ; // overridden as was taking unnecessary path values
	//var url = _loc.protocol + '//' + _loc.host + '/coupon/' + _loc.pathname.split('/')[_loc.pathname.split('/').length - 1] ;
	var url = 'http://' + _loc.host + '/coupon/' + _loc.pathname.split('/')[_loc.pathname.split('/').length - 1] ;
	
	
	var _close = $('<span class="close-pop">x</span>').css({
	    color : 'red',
            cursor : 'pointer',
	    width : 40
	}).click(function(){
	    $(this).parent().slideUp() ;
	}) ;
	var _pop = $('<div id="this-url"></div>').text(url).css({
	    width : 500 ,
	    display : 'block' ,
	    background : '#effdcb',
	    fontSize : 22,
	    textAlign : 'center',
	    margin : 'auto',
	    marginBottom: 10
	}).append('&nbsp;&nbsp;').append(_close);
	$('.white_content').prepend(_pop) ;
        
	
    }

});
