<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="/images/favicon.png">
</head>
<body>
<?php 
echo "<h1>Go To...</h1><br>";
echo "<a href='".site_url('lap')."'>Local Ad Planner</a><br>";
echo "<a href='".site_url('report')."'>Web Report</a><br>";
if($role == 'admin' or $role == 'ops')
{
	if($role == 'admin')
	{
		echo "<a href='".site_url('rollout')."'>Roll Out Latest GITHUB Master(special admin club only)</a><br>";
		echo "<a href='".site_url('uploadtradedeskdata')."'>Upload Trade Desk Data</a><br>";    
	}
	echo "<a href='".site_url('register')."'>Register a User</a><br>";
	echo "<a href='".site_url('couponadmin')."'>Coupon App</a><br>";    
}
echo "<a href='".site_url('dashboard')."'>Dashboard</a><br>";
echo "<a href='".site_url('ring')."'>Ring</a><br>";
echo "<a href='".site_url('auth/logout')."'><img src='/images/LOGOUT.gif' width='85' height='28' style='border:none' /></a><br>";

?>
</body>
</html>
