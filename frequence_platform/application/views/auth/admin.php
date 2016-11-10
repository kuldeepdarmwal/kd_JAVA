<?php  
	echo '<br/>' . "<h1>ADMIN PAGE GOES HERE</h1>";
	echo "<br/> Username = " . $username . "<br/> Business Name = " . $business_name . "<br/> Role = " . $role . "<br/>";
	echo anchor('/auth/logout/', '<img src="/images/LOGOUT.gif" width="85" height="28" />');
	echo '<br />' . anchor('/register/', 'Register User');
	echo '<br />' . anchor('/auth/change_password/', 'Change Password');
	//echo "<a href= './auth/campaignh'> Campaign Health </a>";
	//echo "<a href= './campaignh.php'> Campaign Health </a>";
	?>