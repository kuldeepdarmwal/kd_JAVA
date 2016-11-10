<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Facebook PHP SDK on CI Reactor TEST</title>
</head>
<body>
<div>
    
  <img src="https://graph.facebook.com/<?php echo $fb_data['uid']; ?>/picture" alt="" class="pic" />
  <?php print "<pre>";
  print_r($fb_data);print "</pre>"; ?>
  <p>Hi <?php echo $fb_data['me']['name']; ?>,<br />
    <a href="<?php echo site_url('fb/fb_connect/topsecret'); ?>">You can access the top secret page</a> or <a href="<?php echo $fb_data['logoutUrl']; ?>">logout</a> </p>
</div>
</body>
</html>