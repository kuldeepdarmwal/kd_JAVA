<?php


$search_string = $_POST['search_string'];
if(strpos($_POST['cookie_string'], "\n") == false)
{
     $cookie_list = array($_POST['cookie_string']);
} 
else
{
    $cookie_list = explode("\n", $_POST['cookie_string']);
}
if(strpos($_POST['site_list_string'], "\n") == false)
{
    $site_list = array($_POST['site_list_string']);
}
else
{
    $site_list = explode("\n", $_POST['site_list_string']);
}
$dest_dir = $_POST['dest_dir'];
$max_loops = $_POST['max_loops'];
$max_depth = $_POST['max_depth'];
$kill_id = $_POST['kill_id'];



/*
$search_string = "STRING";
$cookie_list = array("http://www.thepeacocklounge.com/");
$dest_dir = "\/home\/adverify\/public\/adverify.vantagelocalstage.com\/screenshots\/";
$site_list = array("http://mlbtraderumors.com", "http://votefortheworst.com");
$max_loops = 10;
$max_depth = 20;
$kill_id = '1337';
*/

$file = fopen("/home/adverify/public/adverify.vantagelocalstage.com/screenshots/TEST.txt", 'w');


$data = array($search_string, $cookie_list, $site_list, $dest_dir, $max_loops, $max_depth, $kill_id);

$json = json_encode($data);
$json = str_replace("\/", "/", $json);
fwrite($file, $json);

$result = shell_exec("python /home/adverify/bin/ad_verify.py '".$json."'");
fwrite($file,$result);
$file = fopen("/home/adverify/public/adverify.vantagelocal.com/screenshots/TEST.txt", 'w');
fwrite($file, $json);
fwrite($file,$result);
fclose($file);

?>