<?php
   /**
   * Coupon Template
   * 
   * Campaign variables :
   * $id,$coupon_id Camapign/Coupon id
   * $title campaign title
   * $description campaign description
   * $img_filename Campaign logo's relative path
   * $theme selected theme as per url argument
   *  
   */
   ?>
<!DOCTYPE html>
<html dir="ltr" lang="en-US">
  <head>
    <meta charset="UTF-8" />
    <title><?php echo $title; ?></title>
    <meta HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"/>
    <meta property="og:title" content="<?php echo $title; ?>"/>
    <meta property="og:description" content="<?php echo $description; ?>" />
    <meta property="og:url" content="<?php echo normal_url('coupon/').$id;  ?>"/>
    <meta property="og:site_name" content="VLCoupon"/>
    <meta property="og:image" content="<?php if(isset($img_filename) && $img_filename != '') { 
                                       echo normal_url('assets/img/uploads').'/'.$img_filename;
                                       }else{ echo normal_url('resource/img/coupon_icon.jpg'); } ?>" />

    <link href='http://fonts.googleapis.com/css?family=Amaranth:400,400italic,700,700italic' rel='stylesheet' type='text/css'>
    <?php $this->load->helper('url'); ?>
    <link href="<?php echo normal_url('assets/css/coupon_style.css'); ?>" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="<?php echo normal_url('assets/js/jquery-1.7.2.min.js'); ?>" ></script>
    <script type="text/javascript">
     $(document).ready(function() {
       //popup window
       $("div.white_content").fadeIn();
       $("div.black_overlay").fadeIn();
     });
    </script>
    <script type="text/javascript" src="<?php echo normal_url('assets/js/jquery.popUrl.js'); ?>" ></script>
    <script>
      // server side variables which we might require in JS .
      var CI = {
      'normal_url' : '<?php echo normal_url(); ?>',
      'campaign_id' : '<?php echo $coupon_id; ?>',
      'encoded_id' : '<?php echo $encoded_id; ?>'
      };
    </script>
    <script src="//connect.facebook.net/en_US/all.js"></script>
    <?php if(isset($sharepopup) && $sharepopup){ ?>
    <script type="text/javascript">
      window.open("http://www.facebook.com/sharer.php?u=<?php echo normal_url('coupon/'.$encoded_id); ?>",'share coupon',"status=1,width=450,height=300");
    </script>
    <?php unset($sharepopup); } ?>
    <script type="text/javascript">    
      var popupWindow=null;
      $(document).ready(function(){
      $('#email').val('Enter Your Email')
      .css('color','silver')
      .focus(function(){
      if( $.trim($(this).val().toLowerCase()) =='enter your email'){
      $(this).val('').css('color','black');
      } 
      })
      .blur(function(){
      if($.trim($(this).val()) == ''){
      $(this).val('Enter Your Email').css('color','silver');
      }
      });  
      if($('#like-the-coupon').length != 0){
      $('#like-the-coupon').click() ;
      }
      });
      
      function popup(){
      popupWindow = window.open('<?php echo normal_url('frontend/show_popup'); ?>/<?php echo $encoded_id; ?>','name','width=800px,height=600px');
      }
      function parent_disable() {
      if(popupWindow && !popupWindow.closed)
      popupWindow.focus();
      }
      
      
      function isEmail() {
      var email = document.coupon.email.value;
      var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
      
      if(email == ''){ alert('Please enter email address'); return false;}

      if(reg.test(email) == false) {
      alert('Email address is not valid');
      return false;
      }else{
      return true
      }
      }
      
    </script>

  </head>

    <body onFocus="parent_disable();" onclick="parent_disable();" <?php if($background == 'on') echo'bgcolor="#400B0C"';?>>

<!-- Start Open Web Analytics Tracker -->
<script type="text/javascript">
//<![CDATA[
var owa_baseUrl = 'http://www.vantagelocal.com/owa/';
var owa_cmds = owa_cmds || [];
owa_cmds.push(['setSiteId', '048935d9395e34e3a95e610f1254169e']);

owa_cmds.push(['trackPageView']);
owa_cmds.push(['trackClicks']);
owa_cmds.push(['trackDomStream']);

(function() {
    var _owa = document.createElement('script'); _owa.type = 'text/javascript'; _owa.async = true;
    owa_baseUrl = ('https:' == document.location.protocol ? window.owa_baseSecUrl || owa_baseUrl.replace(/http:/, 'https:') : owa_baseUrl );
    _owa.src = owa_baseUrl + 'modules/base/js/owa.tracker-combined-min.js';
    var _owa_s = document.getElementsByTagName('script')[0]; _owa_s.parentNode.insertBefore(_owa, _owa_s);
}());
//]]>
</script>
<!-- End Open Web Analytics Code -->

    <center><div id="light" class="white_content">
      <div id="fb-root"></div> <!-- required for FB like -->
      <div class="error"> <!-- error message wrapper -->
        <?php if($error->status){ ?>
        <?php echo $error->msg; ?> 
        <?php 
           unset($error) ;
           } ?>
      </div>
      <!-- consistent markup ( always visible ) -->
      <div class="coupon_wrap <?php  echo $theme;  ?>">
	<div class="coupon_top">
          <div class="coupon_icon">
            <?php if(isset($img_filename) && $img_filename != '') { ?>
            <img src="<?php echo normal_url('assets/img/uploads').'/'.$img_filename; ?>" width="146" height="146" />
            <?php }else{ ?>
            <img src="<?php echo normal_url('resource/img/coupon_icon.jpg'); ?>" width="146" height="146" />
            <?php } ?>    
          </div>
          <div class="coupon_title"><?php echo $title; ?></div>
	</div>
	<div class="coupon_description"><?php echo $description; ?></div>
	<div class="percent_off">Discount "<?php echo $discount; ?>"</div>
	
	<!-- after claim : second visit -->
	      <?php if(isset($show_coupon_use_code) && $show_coupon_use_code ){ ?>
	
        <div class="buttons_email">
          <div class="coupon_code">
            Coupon Code: <?php echo $coupon_usecode; ?><br>
            Expires: <?php echo $expire; ?><br>
          </div>
          
        </div>
	
	<?php 
           unset($show_coupon_use_code);  } 
           elseif($coupon_type == 1){ 
           // if it's a QPQ coupon type
	   ?>
	
        <div class="buttons_share">
          <div class="button_title"><?php echo $share_msg;  ?></div>
          <!-- Contact based share -->
	  <?php if(in_array('2',$actions)): ?>
	  <a href="" title="Share with Gmail/Yahoo Contacts!" class="claim-share" id="claim-share-mail" onclick="popup();">	 
	    <img src="<?php echo normal_url('assets/img/claim_mail_share.png');  ?>" /></a>
	  <?php   endif; ?>
          
          <!-- FB Share -->
	  <?php if(in_array('1',$actions)): ?>
	  <a href="<?php echo normal_url('fb/fb_connect/index'); ?>/<?php echo $encoded_id; ?>" title="Share with Facebook!" class="claim-share" id="claim-share-fb">
	    <img src="<?php echo normal_url('assets/img/claim_fb_share.png');  ?>" />
	  </a>
	  <?php   endif; ?>
          
          <!-- Self E-mail -->
	  <?php if(in_array('3',$actions)): ?>
	  <form onsubmit="return isEmail();" name="coupon" action="<?php echo normal_url('coupon/'.$encoded_id); if($testmodeactive){ echo '?mode=test';} ?>" method="post"><input id="email" name="email" type="text"><input name="submit" type="submit" value="SUBMIT" id="submit"></form>
	  <?php   endif; ?> 
          
          <!-- FB Like -->  
          <?php if(in_array('4',$actions)): ?>
          <div id="user-info"></div>
          <div id="user-like"></div>
          <p><button id="fb-auth">Login</button></p>
          <script src="<?php echo normal_url('assets/js/FBLike_actions.js'); ?>"></script> 
          <!--
              <div id="auth-status">
                <?php  if(isset($_REQUEST['auth'])):?>
                <div id="auth-loggedin" style="">
                  <!--<fb:like href="<?php echo normal_url('coupon/'.$encoded_id); ?>" send="false" layout="standard" width="450" show_faces="false"></fb:like>
                      <iframe src="//www.facebook.com/plugins/like.php?href=<?php echo normal_url('coupon/'.$encoded_id); ?>&amp;send=false&amp;layout=standard&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=tahoma&amp;height=35" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:35px;" allowTransparency="true"></iframe>
                </div>
                <?php else: ?>
                <div id="auth-loggedout">
                  <a id="auth-loginlink">Login</a>
                </div>
                <?php endif; ?>  
              </div>-->
                  
                  <?php endif; ?>
                  
		</div>
		
		<?php }else{ 
                      // FreePON Coupon Type
		      ?>
                <div class="buttons_email">
                  <div class="button_title">To claim coupon please provide your email</div>
                  <form onsubmit="return isEmail();" name="coupon" action="<?php echo normal_url('coupon/'.$encoded_id); if($testmodeactive){ echo '?mode=test';} ?>" method="post"><input id="email" name="email" type="text"><input name="submit" type="submit" value="SUBMIT" id="submit"></form>
                </div>
		<?php } ?>  
	      </div>
	</div></center>
	      
	   <script type="text/javascript" src="https://s3.amazonaws.com/brandcdn-assets/adservice/peacock/conversion.js">
	   </script>
	       <?php 
	       if ($background == 'on')
		 echo '<div id="fade" class="black_overlay"></div><center><iframe name="cwindow" style="border:0px double " width=100% height=900 scrolling="no" src="'.$landing_page.'"></iframe></center>';
	       ?>
  </body>
</html>