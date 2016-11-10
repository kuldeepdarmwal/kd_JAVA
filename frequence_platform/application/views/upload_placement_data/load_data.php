<html>
<head>
</head>
<body>
<?php
echo 'load_data.php:<br />'."\n";

/*
foreach($bucketsByHour as $bucketString)
{
	echo $bucketString.'<br />';
}
*/


foreach($progressInfo as $progressItem)
{
	echo $progressItem.'<br />'."\n";
}

echo "<br />\n";

//phpinfo();
?>
</body>
</html>
