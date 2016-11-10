<html>
<head>
    <?php
echo '<span id="distinctDomainsTitle">'.$rows.' In <i class="err">SiteRecords</i> AND NOT IN <i class="err">DemographicRecords_01_20_2012</i> </span>';

$counter = 0;
    ?>
</head>
<body>

<form method="post" action="">
  <textarea name="siteInputList" style="height:80%;width:70%" id="sitesList"><?php 
  foreach($sites as $v){
  if($v != end($sites))
    {echo $v.Chr(13);}
  else
    {echo $v;}
}
?></textarea>
  <div style="clear:both;">
  <div style="clear:both;">
  <input type="submit" name="processSites" value="Get Demographic Data">
  <a href="auth/logout"><img src="/images/LOGOUT.gif" width="85" height="28" style="position:relative;padding-top:10px;border:none"/></a>
  </div>
  <br />
  <div style="clear:both; width:450;">
  <label for="disp">Upload to Database</label> <input type="radio" name="disp" value="upload" /> <br />
  <label for="disp">Display Only</label><input type="radio" name="disp" value="display" checked/> <br />
  </div>
  </div>
  </form>
</body>
</html>