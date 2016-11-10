window.fbAsyncInit = function() {
  FB.init({ appId: '480001168682631',
	    status: true, 
	    cookie: true,
	    xfbml: true
	    });

  function updateButton(response) {
    var button = document.getElementById('fb-auth');
		
    if (response.authResponse) {
      //user is already logged in and connected
      var userInfo = document.getElementById('user-info');
      var userLike = document.getElementById('user-like');
      
                                           
      FB.api('/me', function(response) {
          
        // updates 
        userInfo.innerHTML = '<img src="https://graph.facebook.com/' 
	  + response.id + '/picture">' + response.name;
        var like_path = CI.base_url + 'coupon/' + CI.encoded_id ;
        var like_xfbml = '<fb:like href="'+like_path+'" layout="standard" show-faces="false" width="450" action="like" colorscheme="light" font="tahoma"></fb:like>';
       // var like_iframe = '<iframe src="//www.facebook.com/plugins/like.php?href='+like_path+'&amp;send=false&amp;layout=standard&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=tahoma&amp;height=35" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:35px;" allowTransparency="true"></iframe>';
        userLike.innerHTML = like_xfbml ;
        FB.XFBML.parse(userLike);
        button.innerHTML = 'Logout'; //
        button.style.display = 'none'; 
        userLike.style.display = 'block';
      });
      button.onclick = function() {
        FB.logout(function(response) {
          var userInfo = document.getElementById('user-info');
          userInfo.innerHTML="";
	});
      };
    } else {
      //user is not connected to your app or logged out
      button.innerHTML = 'Login';  //button.style.display = 'block';  userLike.style.display = 'none';
      button.onclick = function() {
        FB.login(function(response) {
	  if (response.authResponse) {
            FB.api('/me', function(response) {
	      var userInfo = document.getElementById('user-info');
	      userInfo.innerHTML = 
                '<img src="https://graph.facebook.com/' 
	        + response.id + '/picture" style="margin-right:5px"/>' 
	        + response.name;
	    });	   
          } else {
            //user cancelled login or did not grant authorization
          }
        }, {scope:'email'});  	
      }
    }
  }
  
   // FB Like event binding
   // if user is already logged in we may or may not find email in scope
   // we rely on email for claiming 
   // if there's no email like will have no claim action
   FB.Event.subscribe('edge.create',
                                                function(href,widget) {
                                                    FB.api('/me', function(me){
                                                        if (me.email) {
                                                            $.post(CI.base_url+'frontend/claim_by_mail',{'mail' : me.email , 'mode' : 4 , 'id' : CI.campaign_id },function(data){
                                                                if(data.status=='error'){
                                                                    $('.error').html(data.msg) ;
                                                                }else{
                                                                    // show coupon details
                                                                    var cpn_txt = '<div class="coupon_code">Coupon Code: '+ data.usecode +'<br>Expires: '+ data.expire +'</br></div>';
                                                                    $('.buttons_share').html(cpn_txt) ;
                                                                 }
                                                            },'json');
                                                            // make AJAX based coupon request for this e-mail
                                                            // with returned data
                                                            // show expiry / Error / Message
                                                     }else{
                                                         alert('we didn\'t find email in scope . authenticate via our app first ') ;
                                                     }
                                                    });
                                                    
                                                 }
  );      

  // run once with current status and whenever the status changes
  FB.getLoginStatus(updateButton);
  FB.Event.subscribe('auth.statusChange', updateButton);	
};
	
(function() {
  var e = document.createElement('script'); e.async = true;
  e.src = document.location.protocol 
    + '//connect.facebook.net/en_US/all.js';
  document.getElementById('fb-root').appendChild(e);
}());