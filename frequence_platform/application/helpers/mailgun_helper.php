<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// mailgun() is a replacement for the mail SMTP function
// 
// returns true on success and an arrary of errors on failure so you have to use "===" or "!==" to test for success, using "==" doesn't work
//
// Example: adding CC and BCC call it like this:
//	$mailgun_extras = array('cc' => $my_cc, 'bcc' => $my_bcc);
//	$is_ok = mailgun($from, $to, $my_subject, $my_message, 'text', $mailgun_extras);
//	if($is_ok === true)
//
// See /application/controllers/mailgun_example.php for more detailed examples (must be logged in as admin to browse to page)
//
// further documentation
// - https://sites.google.com/site/vantagecommons/tech-features/using-mailgun
// - http://documentation.mailgun.com/api-sending.html
function mailgun(
	$from_email_address, // string, a single email address of the form "scott.huber@vantagelocal.com" or "Scott Huber <scott.huber@vantagelocal.com>"
	$to_email_addresses, // string, comma separated list of email addresses Example: "john.doe@gmail.com, Scott Super <scott.huber@vantagelocal.com>"
	$subject, // string, subject text
	$message, // string, message text (or html if $message_type == 'html')
	$message_type = 'text', // string, can alternately be 'html'
	$mailgun_post_overrides = array(), // array, mailgun post data - http://documentation.mailgun.com/api-sending.html
	$curl_option_overrides = array() // array, curl_setopt - http://www.php.net/manual/en/function.curl-setopt.php
)
{
	$is_mail_sent = false;
	$errors = array();

	if($message_type === 'text' || $message_type === 'html')
	{
		$email_curl = curl_init();

		$default_post_data = array(
			'from' => $from_email_address,
			'to' => $to_email_addresses,
			'subject' => $subject,
			$message_type => $message
		);

		$post_data = $default_post_data;
		if(is_array($mailgun_post_overrides))
		{
			$post_data =  $mailgun_post_overrides + $default_post_data;
		}
		else
		{
			$errors[] = 'unexpected $mailgun_post_overrides of type: '.gettype($mailgun_post_overrides);
		}

		$default_curl_options = array(
			CURLOPT_URL => 'https://api.mailgun.net/v2/mg.brandcdn.com/messages',
			CURLOPT_USERPWD => 'api:key-1bsoo8wav8mfihe11j30qj602snztfe4',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_RETURNTRANSFER => true
		);

		$curl_options = $default_curl_options;
		if(is_array($curl_option_overrides))
		{
			// Note: array_merge() doesn't work because CURLOPT_* are integers and array_merge() condenses indexes
			// Note: order of "$curl_option_overrides + $default_curl_options" matters, items from first array take precedence (opposite of array_merge()) tested on PHP version 5.3.10-1ubuntu3.8
			$curl_options = $curl_option_overrides + $default_curl_options;
		}
		else
		{
			$errors[] = 'unexpected $curl_option_overrides of type: '.gettype($curl_option_overrides);
		}

		if(empty($errors))
		{
			$is_ok = curl_setopt_array($email_curl, $curl_options);
			if($is_ok)
			{
				$result = curl_exec($email_curl);
				if($result != false)
				{
					$result_info = json_decode($result, true);
					if($result_info !== null && 
						array_key_exists('id', $result_info) &&
						array_key_exists('message', $result_info)
					)
					{
						$is_mail_sent = true;
					}
					else
					{
						if($result_info === null)
						{
							if(is_string($result))
							{
								$errors[] = 'mailgun denied send: '.$result;
							}
							else
							{
								$errors[] = 'mailgun denied send for unknown reason.';
							}
						}
						elseif(!array_key_exists('id', $result_info))
						{
							if(array_key_exists('message', $result_info))
							{
								$errors[] = 'mailgun denied send, no id, but with message: '.$result_info['message'];
							}
							else
							{
								$errors[] = 'mailgun denied send, no id, but with no message';
							}
						}
						elseif(array_key_exists('message', $result_info))
						{
							$errors[] = 'mailgun denied send with id('.$result_info['id'].') and message: '.$result_info['message'];
						}
						else
						{
							$errors[] = 'mailgun denied send with id('.$result_infl['id'].') but with no message';
						}
					}
				}
				else
				{
					$errors[] = 'curl_exec() failed';
				}
			}
			else
			{
				$errors[] = 'curl_setopt_array() failed';
			}
		}

		curl_close($email_curl);
		$email_curl = null;
	}
	else
	{
		$errors[] = "unhandled message type: ".$message_type.", expected 'text' or 'html'";
	}
	
	$response = $is_mail_sent;
	if($is_mail_sent === false || !empty($errors))
	{
		$response = implode(",", $errors);
	}

	return $response;
}

function process_attachments_for_email(
	$attachment_path_array //Array of file paths to be attached to email
)
{
    if(!is_array($attachment_path_array))
    {
		return false;
    }
    $return_attachment_array = array();
    
    $attachment_counter = 0;
    foreach($attachment_path_array as $attachment)
    {
		$return_attachment_array['attachment['.$attachment_counter.']'] = new CurlFile($attachment);
		$attachment_counter++;
    }
    return $return_attachment_array;
}

?>
