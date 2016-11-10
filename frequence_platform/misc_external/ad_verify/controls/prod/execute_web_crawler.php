<?php

if($_POST['domain'] != ""){

    $domain = $_POST['domain'];

    $result = shell_exec("python /home/adverify/bin/web_crawl.py ".$domain);
}
?>