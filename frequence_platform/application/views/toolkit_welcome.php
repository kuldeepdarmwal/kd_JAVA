<html>
    <style type="text/css">
        
        div {
            font-family: 'Lato', sans-serif;

        }
        a:link {text-decoration: none; color:#555;}
        a:visited {text-decoration: none; color:#555;}
        a:active {text-decoration: none; color:#555;}
        a:hover {color: red; text-decoration: none;}
        
        
    </style>
    <head>
        <title></title>
    </head>
    <body>
        <div class="header">
  <div class="headerwrap">
  <div class="headlogobar">
  <div class="logobar_left" style="float:left;position:absolute;z-index:-1;"><img class="logo_img" src="<?php echo $logo_path; ?>" /></div>
  <div class="logobar_right" align="right">
  <div class="logobar_custinfo">Welcome<strong> <span class="logobar_name"><?php echo $username; ?></strong><br/> <?php echo $partner_name?><br />
              <a href="change_password/"style="font-size: 11px; font-weight: normal; " >Change password</a><br>
  </span></strong>
			 
  </span></strong><a href="auth/logout"><img src="/images/LOGOUT.gif" width="85" height="28" style="border:none"/></a>
  </div>
  </div>
  </div><!--END LOGO BAR -->
  <br/>
  <br/>
  <div align="left">
        <table>
         <?php foreach ($functions as $link):?>
         <tr>
            

                <td><a href="<?php echo $link['uri'];?>"><img src="<?php echo $link['icon_path'];?>" width ='33' height='33'/></a></td>
                <td><a href="<?php echo $link['uri'];?>"><p style ="font-size:30px;"><strong><?php echo $link['copy'];?></strong></p></a></td>

        </tr>
          <?php endforeach;?>
        </table>
  </div>
        
        
    </body>
</html>