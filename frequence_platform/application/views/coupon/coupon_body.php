<div class="coupon_wrap">
  <div class="coupon_top">
    <div class="coupon_icon"><img src="<?php echo base_url('assets/img/uploads/coupon_icon.jpg');  ?>" width="146" height="146" /></div>
    <div class="coupon_title"><?php echo $title ;  ?></div>
  </div>
  <div class="coupon_description"><?php echo $description ;  ?><br />
</div>
  <div class="percent_off"><?php echo $discount ;  ?>% off</div>
  <div class="buttons_share">
    <div class="button_title">To claim coupon share with Facebook or your Friends</div>
    <a href="" title="Share with Gmail/Yahoo Contacts!" class="claim-share" id="claim-share-mail" onclick="MM_openBrWindow('http://www.google.com','','width=570,height=500')">
        <img src="<?php echo base_url('assets/img/claim_mail_share.png');  ?>" />
    </a>
    <a href="" title="Share with Facebook!" class="claim-share" id="claim-share-fb" onclick="MM_openBrWindow('http://www.google.com','','width=570,height=500')">
        <img src="<?php echo base_url('assets/img/claim_fb_share.png');  ?>" />
    </a>
  </div>
</div>





