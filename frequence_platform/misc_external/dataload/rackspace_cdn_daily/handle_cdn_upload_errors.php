<?php

$path = '';
if($argc == 2)
{
	$path = $argv[1];
}
else
{
	die('wrong number of arguments to upload_cdn_data.php: '.$argc);
}

$to_email_address = 'Tech Logs <tech.logs@frequence.com>'; 
$from_email_address = 'Engagement Uploader <tech.logs@frequence.com>';
$subject_line = 'Upload Engagement Records: Failure ('.date('m/d/Y H:i:s').')';

$upload_log_file_name = 'upload_cdn_data_log.txt';
$file_data = file_get_contents($path.$upload_log_file_name);
if($file_data === false)
{
	$file_data = 'Failed to open log file: '.$upload_log_file_name."\n";
}

$first_line = substr($file_data, 0, strpos($file_data, "\n"));

// error_signal_string matches error_signal in upload_cdn_data.php, if you change this you must update that.
$error_signal_string = '0 no error signal';
if($first_line != $error_signal_string)
{
	$message = "Environment:\n";
	$message .= "- Host name: ".gethostname()."\n";
	$message .= "- Directory: ".getcwd()."\n";
	$message .= "\n";
	$message .= $subject_line."\n\n";
	$message .= 'upload log output...'."\n\n";
	$message .= $file_data;

	$is_mail_sent = mailgun(
		$from_email_address,
		$to_email_address,
		$subject_line,
		$message
	);  

	if(!$is_mail_sent)
	{   
		die('Failed to send email.'."\n");
	} 
}

function mailgun($from, $to, $subject, $message)
{
	$is_mail_sent = false;

	$email_curl = curl_init();

	$post_data = array(
		'from' => $from,
		'to' => $to,
		'subject' => $subject,
		'text' => $message
	);

	$curl_options = array(
		CURLOPT_URL => 'https://api.mailgun.net/v2/mailgun.vantagelocaldev.com/messages',
		CURLOPT_USERPWD => 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4',
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $post_data,
		CURLOPT_RETURNTRANSFER => true
	);

	$is_ok = curl_setopt_array($email_curl, $curl_options);
	if($is_ok)
	{
		$result = curl_exec($email_curl);
		if($result != false)
		{
			$is_mail_sent = true;
		}
	}

	curl_close($email_curl);
	$email_curl = null;

	return $is_mail_sent;
}

