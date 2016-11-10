<?php

//if($_POST['domain'] != ""){
//$_POST['domain'] = "sfgate.com"; 
   
    $sites = array();
    $domain =  explode("\n", $_POST['domain']);
    foreach ($domain as $site){
	if($site != ""){
	    array_push($sites, $site);
	}
    }
    $json = json_encode($sites);
    $file = fopen("/home/adverify/public/adverify.vantagelocalstage.com/screenshots/logs/crawler/TEST.txt", 'w');
    fwrite($file, $json);
    
    $result = shell_exec("python /home/adverify/bin/web_crawl.py '".$json."' >> /home/adverify/public/screenshots/BUTT.txt");
    fwrite($file, $result);
//}
?>