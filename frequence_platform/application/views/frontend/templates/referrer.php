<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href='http://fonts.googleapis.com/css?family=Amaranth:400,400italic,700,700italic' rel='stylesheet' type='text/css'>
<title></title>
<?php $this->load->helper('url'); ?>
<link href="<?php echo base_url('resource/css/layout.css'); ?>" rel="stylesheet" type="text/css" />
<style type="text/css">
    body{margin:0 auto; font-family:arial; font-size:13px}
    .con{width:800px; margin: 0 auto;}
    .email_con{
        height: 150px;
        width: 350px;
        overflow: scroll;
        border: 1px solid #ccc
    }
    .bottom{
        with:800px;
        height: 400px;
        clear: both;
        display: block;
        overflow: hidden
    }
    .bottom>div, .top>div{float:left; width:359px; padding-top: 10px; padding-bottom: 10px}
    .top>div{width:399px}
    .default_msg textarea{width:300px; height: 200px; resize: none}
    .sharebr{float:left; width:200px}
    .top{overflow: hidden; height: 120px}
    #emails{width: 250px; border: 1px solid #ccc; height: 25px}
    .msg{font-size: 12px; color:#555; margin-bottom: 15px}
    .label{  font-size: 13px}
    .border-right,.form_con{border-right: 1px solid #ccc}
    .bottom>div{margin-top:20px; padding:0 20px 20px 20px}
    .social{width:230px; height: 60px; border: 1px solid #d8d8d8; background: #f8f8f8}
    .social *{float:left;}
    .social img{margin-right: 10px}
    .social input{margin-top: 20px}
    .title{font-size: 30px ; font-family:arail; margin: 0 0 10px 0}
    .discount, .discription{ font-family:arail; margin: 0}
    h3{margin: 0; font-weight: normal; text-transform: uppercase; margin-bottom: 20px; font-size: 13px}
    .top .coupon_tamplate{padding-left: 20px; width:350px}
    .con,.email_con{padding-left: 10px}
    .coupon_tamplate div{flaot:left}
 </style>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" ></script>
 <script type="text/javascript">
     <?php if( isset($success) ){ ?>
        window.onload = function(){
        if (opener && !opener.closed){
            <?php if(isset($testmodeactive) && $testmodeactive == true){ ?>
                    opener.location.href = "<?php echo normal_url('frontend/coupon/'.$encoded_id).'?mode=test'; ?>";
            <?php }else{ ?>
                    opener.location.href = "<?php echo normal_url('frontend/coupon/'.$encoded_id); ?>";
            <?php } ?>
            //opener.parent.location.href = "index.html"; //if opener is in a frame and you want to change the url of the parent window
        }
        window.close();
        }

<?php } ?>
    <?php if(isset($step)): ?>    
       //document.getElementsByTagName("html")[0].style.width="1000px";
    <?php endif; ?>
        function isEmail(email) {               
                var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

                if(reg.test(email) == false) {
                    alert('Email address is not valid');
                    return false;
                }else{
                    return true
                }
        }
        function validate() {
            var status='false';
            var emails = '';
            var emailArray = new Array();
            jQuery("input:checkbox").each(function(i){
                    if(jQuery(this).attr("checked")){
                       status = 'true';
                    }
                });
            emails = jQuery("#emails").val();

            if(emails != ''){
                emailArray = emails.split(',');
                for(i=0; i<emailArray.length; i++){
                    returndata = isEmail($.trim(emailArray[i]));
                    if(!returndata){
                        status = 'notevalild';
                        break;                        
                    }else{
                        status = 'true';
                    }
                }
            }
            if(status == 'true'){
                    return true;
            }else{
                if(status == 'notevalild'){
                        return false;
                }else{
                    alert('please select email');
                    return false;
                }
            }
          }
</script><?php if( isset($success) ){ print '</head><body></body></html>'; exit ; } ?>
</head>
<body>
    <div class="con">
        <div class="top">
            <div class="border-right">
                <h3>Send from your address book</h3>
                <div class="social">
                    <div class="radio"><input <?php if(isset($step) && $step == 1): ?> checked="checked" <?php elseif(isset($step) && $step == 2): ?> disabled="disabled" <?php endif; ?> type="radio" name="gy" value="google" onclick="window.location ='https://accounts.google.com/o/oauth2/auth?client_id=<?php echo $this->config->item('gmailapp_clientID');   ?>&redirect_uri=<?php echo normal_url('frontend/google_auth'); ?>&scope=https://www.google.com/m8/feeds/+https://www.googleapis.com/auth/userinfo.email&response_type=code'" /></div>                    
                    <img src="<?php echo base_url('resource/img/gmail.png'); ?>" />
                    <div class="radio"><input <?php if( isset($step) && $step == 2): ?> checked="checked" <?php elseif(isset($step) && $step == 1): ?> disabled="disabled" <?php endif; ?> type="radio" name="gy" value="yahoo" onclick="window.location ='<?php echo normal_url('/connect/yahoo'); ?>'" /></div>
                    <img src="<?php echo base_url('resource/img/yahoo.png'); ?>" />
                </div>
            </div>
            <div class="coupon_tamplate" style="width:350px;padding:0;margin:2px;">
                <div style="width:100px;float:left;">
                     <?php if(isset($campaign->img_filename) && $campaign->img_filename != '') { ?>
                        <img src="<?php echo base_url('assets/img/uploads').'/'.$campaign->img_filename; ?>" width="100" height="100" />
                    <?php }else{ ?>
                        <img src="<?php echo base_url('resource/img/coupon_icon.jpg'); ?>" width="100" height="100" />
                     <?php } ?>    
                </div>
                <div style="width:240px;float:left;padding:5px;">
                        <h4 style="font-size:18px;margin-bottom:4px;"><?php echo $campaign->title; ?></h4>
                        <p class="discription"><?php echo $campaign->description; ?></p>
                        <p class="discount"><?php echo $campaign->discount; ?></p>
                </div>
                <div style="clear:both;"></div>
            </div>
        </div>
  <?php if(isset($step)): ?>
        <form id="email_form" name="email_form" method='post' action="<?php echo normal_url('frontend/show_popup/'.$encoded_id); ?>" onsubmit="return validate();">
        <span class="sharebr">SHARE THIS COUPON</span> <hr style="margin-top:10px" />

        <div class="bottom">
            <div class="form_con"> <!-- from container -->                
                
                    <div class="label">SEND TO EMAIL(S)</div>
                    <input id="emails" type="test" value='' name="emails"/>
                    <div class="msg">Separate multiple addresses with comma</div>
                    <div class="email_con">
                    <?php
			if(isset($result)):
					foreach ($result as $title) { ?>
                          <div>
                                <input name="referrer[]" type="checkbox" value="<?php echo $title->attributes()->address; ?>"/><?php echo $title->attributes()->address; ?><br />
                          </div>
                    <?php }
					else:
					foreach ($user_contacts as $contact) { ?>
                          <div>
                                <input name="referrer[]" type="checkbox" value="<?php echo $contact->email; ?>"/><?php echo $contact->email; ?><br />
                          </div>
                        <?php } endif;?>                                                
                   </div>                    
                   <input type="checkbox" id="selectall" value="selectall" onclick="jQuery('input:checkbox').attr('checked',jQuery('#selectall').is(':checked'))" />Select All
                   <br /><br />
                   <input id="submit" type="submit" value="Share Coupon" />
                
            </div> <!-- //form container -->
            <div class="default_msg">
                <span>Message</span><br />                
                <textarea name="msg"><?php echo $campaign->share_message; ?></textarea>
            </div>
        </div>
        </form>
  <?php endif; ?>
    </div>
</body>
</html>