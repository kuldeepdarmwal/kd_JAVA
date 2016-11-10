<html>
<head>
</head>
<body>
Future home of Ad Verify
<?php
$file_handle = fopen('/home/adverify/public/screenshots/test.txt', 'a');

fwrite($file_handle, "Testing 123\n");
fclose($file_handle);
?>
</body>
</html>
