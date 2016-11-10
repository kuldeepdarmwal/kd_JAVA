<?php
$kill_id = $_POST['kill_id'];

//$kill_id = "1337";

shell_exec("touch /home/adverify/public/screenshots/".$kill_id.".kill");
sleep(45);
shell_exec("rm /home/adverify/public/screenshots/".$kill_id.".kill");

?>