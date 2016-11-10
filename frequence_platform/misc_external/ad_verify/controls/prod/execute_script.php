<?php

$campaign_id = $_POST['campaign_id'];

echo "python /home/adverify/bin/ad_verify.py ".$campaign_id;
$result = shell_exec("python /home/adverify/bin/ad_verify_prod.py ".$campaign_id);


?>