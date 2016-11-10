<?php
require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\ObjectStore\Resource\DataObject;

$g_rss_url = "http://bestmedicinenews.org/feed/";
$g_rackspace_bucket_url = 'https://9e895baee06abaf60938-9895a1ef516f8e47ec03f0a11e0730bc.ssl.cf1.rackcdn.com';
$g_cdn_base_folder = 'renown_health';
$g_dictionary_file_name = 'renown_rss_ad_dictionary.json';
$g_dictionary_cdn_file_name = $g_cdn_base_folder.'/'.$g_dictionary_file_name;
$g_last_dictionary_info_file_path = '.';
$g_last_dictionary_info_file = 'renown_best_medicine_news_rss_last_dictionary_info.json';

$g_errors = array();
$g_warnings = array();
$g_info = array();

function get_data_from_rss_feed($url)
{
	global $g_errors;

	$rss_raw = file_get_contents($url);
	if($rss_raw === false)
	{
		$g_errors[] = "failed to get file";
		return null;
	}
	//echo "successfully retreived rss file";

	$rss_sxml = null;
	try {
		$rss_sxml = new SimpleXMLElement($rss_raw, LIBXML_NONET); // |LIBXML_NOCDATA
	}
	catch(Exception $ex)
	{
		$g_errors[] = "failed to parse rss";
		return null;
	}
	//echo "successfully parsed rss file";

	return $rss_sxml;
}

function get_rss_feed_last_build_date(SimpleXMLElement $rss_element)
{
	return (string)$rss_element->channel->lastBuildDate;
}

function create_dictionary_json(SimpleXMLElement $rss_element)
{
	return create_dictionary_json_v0($rss_element);
}

function get_date_time_microseconds_rand($mtime = null)
{
	if(is_null($mtime))
	{
		$mtime = microtime();
	}
	return date("Y_m_d_H_i_s_").substr((string)$mtime, 2, 6).'_'.sprintf('%05d', rand(0,99999));
}

function create_dictionary_json_v0(SimpleXMLElement $rss_element)
{
	global $g_info;
	global $g_errors;

	$dictionary = array();
	$all_rss_items = $rss_element->channel->item;
	$rss_items = array($all_rss_items[0]); // Renown client only wants first item in RSS feed
	$num_items = count($rss_items);
	$num_successfully_processed_images = 0;
	$num_successfull_rss_items = 0;

	srand();
	$directory_date_string = get_date_time_microseconds_rand();
	$intermediate_directory_name = 'renown_rss_ad_'.$directory_date_string;
	//echo 'intermediate_directory_name: '.$intermediate_directory_name."\n\n";
	mkdir($intermediate_directory_name);

	$dictionary['last_build_date'] = (string)$rss_element->channel->lastBuildDate;
	$dictionary['num_items'] = $num_items;
	$dictionary['save_file_version'] = '0';

	$dictionary_items = array();
	foreach($rss_items as $item)
	{
		$is_item_ok = true;

		$item_data = array();
		$title = (string)$item->title;
		$item_data['title'] = $title;
		$item_data['publish_date'] = (string)$item->pubDate;
		$item_data['landing_page'] = (string)$item->link;
		$item_data['text'] = (string)$item->description;

		$image_key = null;
		$media_thumbnails = $item->xpath('media:thumbnail');
		if($media_thumbnails && count($media_thumbnails) > 0)
		{
			$media_thumbnail = $media_thumbnails[0];

			$attributes = $media_thumbnail->attributes();
			$image_url = (string)$attributes['url'];
			$image_width = (string)$attributes['width'];
			$image_height = (string)$attributes['height'];
			$image_key = get_date_time_microseconds_rand();

			if(process_image_and_put_on_cdn(
				$image_url, 
				$image_width,
				$image_height,
				$image_key,
				$intermediate_directory_name
			))
			{
				$num_successfully_processed_images++;
			}
			else
			{
				$is_item_ok = false;
			}
		}
		else
		{
			$is_item_ok = false;
			$g_errors[] = "failed to get media thumbnail for item: $title";
		}

		$item_data['image_key'] = $image_key;

		if($is_item_ok)
		{
			$num_successfull_rss_items++;
			$dictionary_items[] = $item_data;
		}
	}

	$g_info[] = "Processed $num_successfull_rss_items/$num_items successfully, (".($num_items - $num_successfull_rss_items)." errors)";

	$dictionary['items'] = $dictionary_items;

	$dictionary_json = json_encode($dictionary);
	return $dictionary_json;
}

function process_image_and_put_on_cdn(
	$image_url,
	$width,
	$height,
	$image_key,
	$intermediate_directory
)
{
	global $g_cdn_base_folder;
	global $g_errors;
	global $g_warnings;

	$is_success = true;

	$file_type_suffix = strrchr($image_url, '.');

	$intermediate_string = '_original';
	$original_local_file_name = $intermediate_directory.'/'.$image_key.$intermediate_string.$file_type_suffix;
	copy($image_url, $original_local_file_name);
	$image_data = getimagesize($original_local_file_name);
	if($image_data !== false)
	{
		$new_images_dimensions = array(
		/* // Renown client now only wants 300x250 sized ad
			array(
				'ad_w' => 160,
				'ad_h' => 600,
				'img_w' => 392,
				'img_h' => 392 
			),
			array(
				'ad_w' => 728,
				'ad_h' => 90,
				'img_w' => 356,
				'img_h' => 356
			),
		*/
			array(
				'ad_w' => 300,
				'ad_h' => 250,
				'img_w' => 298,
				'img_h' => 298
			)
		);

		$k_image_type_index = 2;
		$image_type = $image_data[$k_image_type_index];
		$original_image_resource = null;
		switch($image_type)
		{
			case IMG_GIF:
				$original_image_resource = imagecreatefromgif($original_local_file_name);
				break;
			case IMG_JPG:
			case IMG_JPEG:
				$original_image_resource = imagecreatefromjpeg($original_local_file_name);
				break;
			case IMG_PNG:
				$original_image_resource = imagecreatefrompng($original_local_file_name);
				break;
			default:
				$original_image_resource = null;
				break;
		}

		if($original_image_resource !== null)
		{
			$original_width = $image_data[0];
			$original_height = $image_data[1];

			if($original_width != $original_height)
			{
				$g_warnings[] = "image is not square (width: $original_width, height: $original_height): from $image_url";
			}

			$resized_string = '_resized';
			foreach($new_images_dimensions as $new_image_dimensions)
			{
				$main_file_name = $image_key.'_'.$new_image_dimensions['ad_w'].'x'.$new_image_dimensions['ad_h'];
				$resized_local_file_name = $intermediate_directory.'/'.$main_file_name.$resized_string.".jpg";
				$cdn_file_name = $g_cdn_base_folder.'/images/'.$main_file_name.".jpg";

				$new_width = $new_image_dimensions['img_w'];
				$new_height = $new_image_dimensions['img_h'];
				$resized_image_resource = imagecreatetruecolor($new_width, $new_height);
				imagecopyresized($resized_image_resource, $original_image_resource, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
				imagejpeg($resized_image_resource, $resized_local_file_name, 95);
				$file_data = fopen($resized_local_file_name, 'r');
				$is_ok = upload_to_rackspace_cdn($cdn_file_name, $file_data);
				if($is_ok)
				{
					//echo "successfull upload to rackspace: $main_file_name\n";
				}
				else
				{
					$g_errors[] = "failed to upload image to rackspace: $main_file_name from $image_url";
					$is_success = false;
				}
			}
		}
		else
		{
			$g_errors[] = "unhandled image file type: $file_type_suffix from $image_url";
			$is_success = false;
		}
	}
	else
	{
		$g_errors[] = "failed to get image info: $image_url";
		$is_success = false;
	}

	return $is_success;
}


function extract_dictionary_from_json_v0($dictionary_json)
{
	return $dictionary_json;
}

function extract_dictionary_from_json($dictionary_json)
{
	global $g_errors;

	$dictionary = null;

	$extracted = json_decode($dictionary_json);
	if($extracted !== false)
	{
		switch($extracted->save_file_version)
		{
			case '0':
				$dictionary = extract_dictionary_from_json_v0($extracted);
				break;
			default:
				$g_errors[] = 'unhandled dictionary save file version: '.$extracted->save_file_version."";
		}
	}

	return $dictionary;
}

function create_last_dictionary_info_v0($base_info)
{
	$wrapped_info = array(
		'version' => '0',
		'content' => $base_info
	);

	return $wrapped_info;
}

function extract_last_dictionary_info($wrapped_info)
{
	global $g_errors;

	$extracted = null;

	switch($wrapped_info->version)
	{
		case '0':
			$extracted = extract_last_dictionary_info_v0($wrapped_info);
			break;
		default:
			$g_errors[] = 'failed to extract last dictionary info'."";
	}

	return $extracted;
}

function extract_last_dictionary_info_v0($wrapped_info)
{
	// check version
	$base_info = $wrapped_info->content;
	return $base_info;
}

function get_last_dictionary_info()
{
	global $g_errors;

	global $g_last_dictionary_info_file_path;
	global $g_last_dictionary_info_file;

	$dictionary_info_file_name = $g_last_dictionary_info_file_path . '/' . $g_last_dictionary_info_file;

	$last_dictionary_info = null;
	if(file_exists($dictionary_info_file_name))
	{
		$raw_file_data = file_get_contents($dictionary_info_file_name);
		if($raw_file_data !== false)
		{
			$wrapped_info = json_decode($raw_file_data);
			$last_dictionary_info = extract_last_dictionary_info($wrapped_info);
			$message = "extracted last dict info ";
		}
		else
		{
			$g_errors[] = "last dict info no contents";
		}
	}
	else
	{
		$message = "last dict info doesn't exist";
	}

	return $last_dictionary_info;
}

function save_last_dictionary_info($last_dictionary_info)
{
	global $g_errors;
	global $g_last_dictionary_info_file_path;
	global $g_last_dictionary_info_file;

	$is_success = false;
	$dictionary_info_file_name = $g_last_dictionary_info_file_path . '/' . $g_last_dictionary_info_file;
	$wrapped_info = create_last_dictionary_info_v0($last_dictionary_info);
	$json = json_encode($wrapped_info);
	if($json !== false)
	{
		$is_success = file_put_contents($dictionary_info_file_name, $json);
		if(!$is_success)
		{
			$g_errors[] = "Failed to save \"last dictionary info\" to local file system.";
		}
	}
	else
	{
		$g_errors[] = "Failed to json encode \"last dictionary info\".";
	}

	return $is_success;
}

function get_current_ad_dictionary_build_date()
{
}

function upload_to_rackspace_cdn($cdn_file_name, $cdn_file_data)
{
	global $g_errors;

	$is_uploaded = false;
	try {
		$client = new Rackspace(
			Rackspace::US_IDENTITY_ENDPOINT, 
			array(
				'username' => 'localbranding',
				'apiKey' => 'fadf29c3cfe25170ffabf4898d9de9e4'
			)
		);
		$object_store = $client->objectStoreService('cloudFiles', 'DFW', 'publicURL');
		$container = $object_store->getContainer('rss_ads');

		$headers = array(
			'Access-Control-Allow-Methods' => 'GET',
			'Access-Control-Allow-Origin' => '*'
		);
		$container->uploadObject($cdn_file_name, $cdn_file_data, $headers);
		//$container->getObject($remote_file_name);
		//$container->getPartialObject($remote_file_name);
	}
	catch(Exception $ex)
	{
		$is_uploaded = false;
		$g_errors[] = 'Failed to upload to rackspace cdn: '.$ex->getMessage()."";
		return false;
	}
	
	$message = 'successfully uploaded to rackspace cdn'."";
	return true;
}

function test_get_cdn_file()
{
	global $g_rackspace_bucket_url;
	global $g_dictionary_cdn_file_name;

	$json = file_get_contents($g_rackspace_bucket_url.'/'.$g_dictionary_cdn_file_name );
	$struct = json_decode($json);
	print_r($struct);
}

function mailgun($from, $to, $subject, $message)
{
	// taken from : https://sites.google.com/site/vantagecommons/tech-features/using-mailgun
	$is_mail_sent = false;

	$email_curl = curl_init();

	$post_data = array(
		'from' => $from,
		'to' => $to,
		'subject' => $subject,
		'text' => $message
	);

	$curl_options = array(
		CURLOPT_URL => 'https://api.mailgun.net/v2/mg.brandcdn.com/messages',
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

// main()
$rss_element = get_data_from_rss_feed($g_rss_url);
$rss_last_build_date = get_rss_feed_last_build_date($rss_element);
$was_dictionary_updated_on_cdn = false;

$old_dictionary_info = get_last_dictionary_info();

$previous_rss_build_date = empty($old_dictionary_info) ? 'empty' : $old_dictionary_info->last_build_date;
//echo "old: ".$old_string.", new: ".$rss_last_build_date."";

if(empty($old_dictionary_info) || $old_dictionary_info->last_build_date != $rss_last_build_date)
{
	// create new dictionary
	$dictionary_json = create_dictionary_json($rss_element);
	$dictionary_file_name = $g_last_dictionary_info_file_path . '/' . $g_dictionary_file_name;
	file_put_contents($dictionary_file_name, $dictionary_json);

	// upload file
	$is_success = upload_to_rackspace_cdn($g_dictionary_cdn_file_name, $dictionary_json);
	if($is_success === true)
	{
		$g_info[] = "RSS Ad dictionary updated on CDN with publication date: ".$rss_last_build_date;
		$was_dictionary_updated_on_cdn = true;
		$last_dictionary_info = array(
			'last_build_date' => $rss_last_build_date
		);
		$is_saved = save_last_dictionary_info($last_dictionary_info);
		if(!$is_saved)
		{
			$g_errors[] = 'failed to save last dictionary info'."";
		}
	}
	else
	{
		$g_errors[] = 'failed to upload updated RSS Ad Dictionary to CDN.'."";
	}
}
else
{
	$g_info[] = "Dictionary already up to date, no action taken.";
}

send_email($g_errors, $g_warnings, $g_info, $was_dictionary_updated_on_cdn, $previous_rss_build_date, $rss_last_build_date);

function send_email($errors, $warnings, $info, $was_dictionary_updated_on_cdn, $previous_rss_build_date, $new_rss_build_date)
{
	$email_from = 'Renown RSS Ad Cron <tech-logs@vantagelocal.com>';
	$email_to = 'tech-logs@vantagelocal.com';
	$subject = 'Renown RSS Ad Cron: ';
	$body = '';

	$has_errors = !empty($errors);
	$has_warnings = !empty($warnings);

	if($was_dictionary_updated_on_cdn || $has_errors || $has_warnings)
	{
		if($was_dictionary_updated_on_cdn)
		{
			$message = "Updated dictionary on CDN (".date("Y-m-d H:i:s O").")";
			$subject .= $message;
			$body .= $message."\n\n";
			$body .= "New RSS build date: ".$new_rss_build_date."\n";
			$body .= "Previous RSS build date: ".$previous_rss_build_date."\n";
		}
		else
		{
			$message = "Failed to update dictionary on CDN (".date("Y-m-d H:i:s O").")";
			$subject .= $message;
			$body .= $message."\n";
		}
		$body .= "\n\n";

		$body .= "Info items: ".count($info)."\n";
		foreach($info as $item)
		{
			$body .= " - ".$item."\n";
		}

		if($has_errors)
		{
			$body .= "\n\n";

			$num_errors = count($errors);
			if($was_dictionary_updated_on_cdn)
			{
				$subject .= " - ";
			}
			$subject .= $num_errors." errors";
			$body .= "Error items: ".$num_errors."\n";
			foreach($errors as $error)
			{
				$body .= " - ".$error."\n";
			}
		}

		if($has_warnings)
		{
			$body .= "\n\n";

			$num_warnings = count($warnings);
			if($was_dictionary_updated_on_cdn || $has_errors)
			{
				$subject .= " - ";
			}
			$subject .= $num_warnings." warnings";
			$body .= "Warning items: ".$num_warnings."\n";
			foreach($warnings as $warning)
			{
				$body .= " - ".$warning."\n";
			}
		}

		mailgun($email_from, $email_to, $subject, $body);
		//echo "sent email\n";
	}
	else
	{
		//echo "email not sent\n";
	}
}

//test_get_cdn_file();

//echo "eof\n";

?>
