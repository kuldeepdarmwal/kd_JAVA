<? session_start();
// connect  server 
$hostname = "localhost";
$username = "username"; // your  localhost  username
$password = "pass"; // your  localhost  password
$dbname = "databasename"; // your   databasename
$link = mysql_pconnect($hostname,$username, $password);
mysql_query("SET NAMES 'utf8'");
mysql_select_db($dbname);

if($_POST["username"] and $_POST["password"]){
sleep(1);
$pass_login=$_POST["password"];
 // $pass_login=md5($_POST["password"]);  if use md5 encode 
$result=q("SELECT * FROM  field  where  username='".$_POST["username"]."' and password='".$pass_login."' ");

	  if(mysql_num_rows($result)==0){ 
			$return_arr["status"]=0;		
			echo json_encode($return_arr); // return value 
	  }else{
	  $row=mysql_fetch_assoc($result);
			if($_POST["remember"]){ //  if remeber checked
					$cookieTime=time()+3600*24*356;	 //  cookie  time
					setcookie("account_name",$row[username],$cookieTime); 
					// create cookie  ("your cookie name", parameter , cookie time )
			}else{ 
					$_SESSION["account_name"]=$row[username];	// create SESSION  
			}
			$return_arr["status"]=1;		 
			echo json_encode($return_arr); // return value 
}  //end else
exit();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
<title>ziceinclude&trade; admin  version 1.0 online</title>
        <!--[if lt IE 9]>
          <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
<link href="css/zice.style.css" rel="stylesheet" type="text/css" />
<link href="css/icon.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="components/tipsy/tipsy.css" media="all"/>
<style type="text/css">
html {
	background-image: none;
}
#versionBar {
	background-color:#212121;
	position:fixed;
	width:100%;
	height:35px;
	bottom:0;
	left:0;
	text-align:center;
	line-height:35px;
}
.copyright{
	text-align:center; font-size:10px; color:#CCC;
}
.copyright a{
	color:#A31F1A; text-decoration:none
}    
</style>
</head>
<body >
         
<div id="alertMessage" class="error"></div>
<div id="successLogin"></div>
<div class="text_success"><img src="images/loadder/loader_green.gif"  alt="ziceAdmin" /><span>Please wait</span></div>

<div id="login" >
  <div class="ribbon"></div>
  <div class="inner">
  <div  class="logo" ><img src="images/logo/logo_login.png" alt="ziceAdmin" /></div>
  <div class="userbox"></div>
  <div class="formLogin">
   <form name="formLogin"  id="formLogin" action="POST">
          <div class="tip">
          <input name="username" type="text"  id="username_id"  title="Username"   />
          </div>
          <div class="tip">
          <input name="password" type="password" id="password"   title="Password"  />
          </div>
          <div style="padding:20px 0px 0px 0px ;">
            <div style="float:left; padding:0px 0px 2px 0px ;">
           <input type="checkbox" id="on_off" name="remember" class="on_off_checkbox"  value="1"   />
              <span class="f_help">Remember me</span>
              </div>
          <div style="float:right;padding:2px 0px ;">
              <div> 
                <ul class="uibutton-group">
                   <li><a class="uibutton normal" href="#"  id="but_login" >Login</a></li>
				   <li><a class="uibutton  normal" href="#" id="forgetpass">forpassword</a></li>
               </ul>
              </div>
            </div>
</div>

    </form>
  </div>
</div>
  <div class="clear"></div>
  <div class="shadow"></div>
</div>

<!--Login div-->
<div class="clear"></div>
<div id="versionBar" >
  <div class="copyright" > &copy; Copyright 2012  All Rights Reserved <span class="tip"><a  href="#" title="Zice Admin" >Your company</a> </span> </div>
  <!-- // copyright-->
</div>
<!-- Link JScript-->
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="components/effect/jquery-jrumble.js"></script>
<script type="text/javascript" src="components/ui/jquery.ui.min.js"></script>     
<script type="text/javascript" src="components/tipsy/jquery.tipsy.js"></script>
<script type="text/javascript" src="components/checkboxes/iphone.check.js"></script>
<script type="text/javascript" src="js/logincheck.js"></script>
</body>
</html>