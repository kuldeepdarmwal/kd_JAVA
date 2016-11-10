<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   </head>
   <body>
   <?php 
   // $source160x600 = 'flash_160x600.swf';
   //$source336x280 = 'flash_336x280.swf';
   //$source300x250 = 'flash_300x250.swf';
   ?>
<div id="rtg_text" style="font-family:'BebasNeue', sans-serif;"><?php 
     if($adset == 'not_retargeted'){ echo " You are not being retargeted. ";}
     else{ echo " You are currently being retargeted by:<strong> ".$adset_array[$adset]. "</strong> ";}
?>
</div>
<span id="flash_container">
<div id="flash_300x250" style="position:relative;" bgcolor="#a29393"><embed width=300 height=250 src="<?php echo site_url('ring_files/rtg_files/ads/'.$adset.'/'.$source300x250); ?>" /></div>
<div id="flash_336x280" style="position:relative;top:70px;" bgcolor="#a29393"><embed width=336 height=280 src="<?php echo site_url('ring_files/rtg_files/ads/'.$adset.'/'.$source336x280); ?>" /></div>
<div id="flash_160x600" style="position:relative;left:366px;top:-532px;" bgcolor="#a29393"><embed width=160 height=600 src="<?php echo site_url('ring_files/rtg_files/ads/'.$adset.'/'.$source160x600); ?>" /></div>
</span>
<!--   
     <iframe scrolling=no width=160 height=600 frameborder="0" allowtransparecy="true" src="<?php echo site_url('ring_files/rtg_files/ads/'.$adset.'/'.$source160x600); ?>"></iframe>
     <iframe scrolling=no width=336 height=280 frameborder="0" allowtransparecy="true" src="<?php echo site_url('ring_files/rtg_files/ads/'.$adset.'/'.$source336x280); ?>"></iframe>  
     <iframe scrolling=no width=300 height=250 frameborder="0" allowtransparecy="true" src="<?php echo site_url('ring_files/rtg_files/ads/'.$adset.'/'.$source300x250); ?>"></iframe>
-->
   </body>
</html>