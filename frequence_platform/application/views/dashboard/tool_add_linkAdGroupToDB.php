<?php
/*
	//Start session
	session_start();
	
	foreach ($_REQUEST as $k => $v)
	{
		$$k = $v; // &k=v --> $k=v
	}
	
	//Include database connection details
	require_once('config.php');
	
	//Array to store validation errors
	
	//Validation error flag
	
	//Connect to mysql server
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$link) {
		die('Failed to connect to server: ' . mysql_error());
	}
	
	//Select database
	$db = mysql_select_db(DB_DATABASE);
	if(!$db) {
		die("Unable to select database");
	}
	
	function clean($str) {
		$str = @trim($str);
		if(get_magic_quotes_gpc()) {
			$str = stripslashes($str);
		}
		return mysql_real_escape_string($str);
	}
	
	$array_data = explode(",",$DataString );
	$count = count($array_data);
	
	for($i=0;$i<$count;$i++)
	{
		$array_data[$i] = clean($array_data[$i]);
	}

	$errflag = false;
	$errmsg_arr = array();
		
	$BusinessName				= $array_data[0]; 	//business name
	$CampaignName 				= $array_data[1]; 	//campaign name
	$ID							= $array_data[2]; 	//ID
	$IsRetargeting				= $array_data[3]; 	//IsRetargeting
	$IsDerivedSiteDateRequired	= $array_data[4]; 	//IsDerivedSiteDateRequired
	
	
	
	if($BusinessName == '') {
		$errmsg_arr[] = 'Business Name is Blank';
		$errflag = true;
	}
	if($CampaignName =='' || $CampaignName =='Select a Campaign') {
		$errmsg_arr[] = 'Campaign Name is Blank';
		$errflag = true;
	}
	if($ID == '') {
		$errmsg_arr[] = 'ID is Blank';
		$errflag = true;
	}
	if($IsRetargeting =='') {
		$errmsg_arr[] = 'Is Retargeting Is Blank';
		$errflag = true;
	}
	if($IsDerivedSiteDateRequired =='') {
		$errmsg_arr[] = 'Is Derived Site Date Required Is Blank';
		$errflag = true;
	}
	
	if($IsRetargeting == 'true') {
		$IsRetargeting = 1;
	}
	if($IsDerivedSiteDateRequired == 'true') {
		$IsDerivedSiteDateRequired = 1;
	}
	
	
	//Check for duplicate business ID
	if($ID != '') {
		$qry = "SELECT * FROM AdGroups WHERE ID ='".$ID."'";
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$errmsg_arr[] = 'Network Unique ID is already in use';
				$errflag = true;
			}
			@mysql_free_result($result);
		}
		else {
			die("Query failed");
		}
	}
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		include 'error-handler.php';
		exit();
	}
	
	$query_LinkAdGroup = "
		INSERT INTO  `552933_wp`.`AdGroups` (
		`ID` ,
		`BusinessName` ,
		`CampaignName`,
		`IsRetargeting`,
		`IsDerivedSiteDateRequired`
		)
		VALUES (
		'".$ID."',  '".$BusinessName."', '".$CampaignName."',  '".$IsRetargeting."',  '".$IsDerivedSiteDateRequired."'
		)
		";

	
	
	if(!mysql_query($query_LinkAdGroup))
	{
		echo "<h3 class='err'>Error Adding To DB</h3>";
	}
	else
	{
		echo "<h3>Success</h3>
		<table>
			<tr>
				<td style='padding:5px;'><strong>ID:</strong></td>
				<td>".stripslashes($ID)."</td>
				<td><button onclick=\"alert('This Does Not Work Yet')\">Modify</button></td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Business Name:</strong></td>
				<td>".stripslashes($Business)."</td>
				<td><button onclick=\"alert('This Does Not Work Yet')\">Modify</button></td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Campaign Name:</strong></td>
				<td>".stripslashes($Campaign)."</td>
				<td><button onclick=\"alert('This Does Not Work Yet')\">Modify</button></td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Is Retargeting:</strong></td>
				<td>".$IsRetargeting."</td>
				<td><button onclick=\"alert('This Does Not Work Yet')\">Modify</button></td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Is Derived Site Date Required:</strong></td>
				<td>".$IsDerivedSiteDateRequired."</td>
				<td><button onclick=\"alert('This Does Not Work Yet')\">Modify</button></td>
			</tr>
		</table>";
*/
		echo "<h3>Success</h3>
		<table>
			<tr>
				<td style='padding:5px;'><strong>ID:</strong></td>
				<td>".stripslashes($ID)."</td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Business Name:</strong></td>
				<td>".stripslashes($Business)."</td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Campaign Name:</strong></td>
				<td>".stripslashes($Campaign)."</td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Is Retargeting:</strong></td>
				<td>".$IsRetargeting."</td>
			</tr>
			<tr>
				<td style='padding:5px;'><strong>Is Derived Site Date Required:</strong></td>
				<td>".$IsDerivedSiteDateRequired."</td>
			</tr>
		</table>";
//	}
/*
echo '
<h2>Do Link Ad Group</h2>
dashboard/tool_add_linkAdGroupToDB.php
';
 */	
?>
