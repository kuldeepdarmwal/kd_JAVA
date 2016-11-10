<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <link rel="shortcut icon" href="<?php echo $favicon_path; ?>">
   <title><?php echo $partner_name; ?> | Reset Password</title>
   <link href="<?php echo $css_path; ?>" rel="stylesheet" type="text/css" />
   <link href='https://fonts.googleapis.com/css?family=Lato:100,100italic,300,300italic,400,400italic,700,700italic,900italic,900' rel='stylesheet' type='text/css'>
  
   <script type="text/javascript">
   function MM_preloadImages() { //v3.0
   var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
     var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
							   if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
 }

   function MM_swapImgRestore() { //v3.0
     var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
   }

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
								    if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
</script>


<!-- LOAD jQuery --><script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
<style>
	#new_password, #confirm_new_password{
		display: block;
		width: 200px;
		border: 1px solid #000;
		padding: 10px;
		webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		border-radius: 5px;
	}
</style>
  </head>
<body>
  <div class="header960">
  <img class="logo_img" src="<?php echo $logo_path; ?>" alt="" />
  </div>

<?php
$new_password = array(
	'name'	=> 'new_password',
	'id'	=> 'new_password',
	'maxlength'	=> $this->config->item('password_max_length', 'tank_auth'),
	'size'	=> 30,
);
$confirm_new_password = array(
	'name'	=> 'confirm_new_password',
	'id'	=> 'confirm_new_password',
	'maxlength'	=> $this->config->item('password_max_length', 'tank_auth'),
	'size' 	=> 30,
);
?>
  <div class="content">
  <div class="content_left">
<p><small>Please enter and confirm a new password.</small></p>
<?php echo form_open($this->uri->uri_string()); ?>
  <table>
  <p class="logintitle"><strong><label for="new_password"><?php echo form_label('New Password', 'new_password'); ?></label></strong></p>
  <?php echo form_password($new_password); ?>  <p class = "logintitle" style="color: red;"><?php echo isset($errors['new_password'])?$errors['new_password']:''; ?></p>
  <p class="logintitle"><strong><label for="password"><?php echo form_label('Confirm New Password', 'confirm_new_password'); ?></label></strong></p>
  <?php echo form_password($confirm_new_password); ?> <p class = "logintitle" style="color: red;"><?php echo isset($errors['confirm_new_password'])?$errors['confirm_new_password']:''; ?></p>
  </table>
<?php echo form_submit('change', 'Change Password'); ?>
<?php echo form_close(); ?>
</div>
</div>