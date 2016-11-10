<html>
<head>
</head>
<body>
Hello TTD <?php echo $aTest; ?> <br />

<?php
/*
echo 'file Urls...';
foreach($fileUrls as $fileUrl)
{
	echo $fileUrl.'<br />';
}
*/

echo 'buckets list... <br />';
foreach($bucketList as $bucket)
{
	echo $bucket.'<br />';
}

echo 'progress... <br />';
foreach($progressInfo as $infoItem)
{
	echo $infoItem.'<br />';
}

var_dump($objectResponse);

//phpinfo();
?>
</body>
</html>
