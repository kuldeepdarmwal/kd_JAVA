<?php  

if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function create_tracking_tag_file($tracking_tag_file_name, $tracking_tag_file_id, $write_tags=false, $tag_type=0)
{
	if (isset($tracking_tag_file_name) && $tracking_tag_file_name !== '' && $tracking_tag_file_name !== null && $tag_type != 1)
	{
		$autoscript_directory_path = $_SERVER['DOCUMENT_ROOT'] . '/autoscript/';
		$directory_name = "/";
		$forward_slash_position = strpos($tracking_tag_file_name,'/');

		//If filename contains forward slash, create directory.
		if ($forward_slash_position > 0)
		{
			$directory_name = substr($tracking_tag_file_name,0,$forward_slash_position).$directory_name;
			$tracking_tag_file_name = substr($tracking_tag_file_name,$forward_slash_position+1);
			create_tracking_tag_file_directory($directory_name);
		}

		$file_path = $autoscript_directory_path.$directory_name;

		if ($write_tags)
		{
			return publish_single_file($file_path,trim($tracking_tag_file_name),$tracking_tag_file_id);
		}
		elseif (!touch($file_path.trim($tracking_tag_file_name)))
		{
			return false;
		}
	}

	return true;
}

function create_tracking_tag_file_directory($directory_name)
{
	$autoscript_directory_path = $_SERVER['DOCUMENT_ROOT'] . '/autoscript/';
	
	//If directory not present, make new directory.
	if(!is_dir(trim($autoscript_directory_path.$directory_name)))
	{
		if (!mkdir(trim($autoscript_directory_path.$directory_name)))
		{
			return false;
		}
	}
	return true;
}

function move_tracking_tag_file_to_new_directory($old_path, $new_path)
{
	$autoscript_directory_path = $_SERVER['DOCUMENT_ROOT'] . '/autoscript/';
	return rename($autoscript_directory_path.$old_path,$autoscript_directory_path.$new_path);
}

function publish_single_file($file_path, $file_name, $tracking_tag_file_id)
{ 
	$CI =& get_instance();
	$CI->load->model('tag_model');
	
	// Reset the string
	$write_string = "";
	$extension = ".js";

	if(strpos($file_name,".js") > 0)
	{
		$extension="";
	}

	$JSPathName = trim($file_path).trim($file_name).$extension;

	// For this file name let's pull the script tags
	$tags = $CI->tag_model->get_tags_by_file_id($tracking_tag_file_id);

	if (isset($tags))
	{
		foreach($tags as $script_tag)
		{
			// Check if it's inactive or active
			if ($script_tag["isActive"] == 0)
			{
				$tagclean = "";
			}
			else
			{
				$tagclean = str_replace("'","\'",$script_tag["tag"]);
			}
			$write_string .= "document.write('".$tagclean."');"." \n";
		}
	}

	// Write to file
	$fp = fopen($JSPathName, "w");

	if ($fp)
	{
		fwrite($fp, $write_string);
		fclose($fp);
		return 'SUCCESS';
	}
	else
	{
		return 'FAILED';			
	}
}

function get_tag_file_directory_name($campaign_id, $adv_id, $source_table = null)
{
	$CI =& get_instance();
	$CI->load->model('tag_model');
	
	if ($adv_id != null)
	{
		$advertiser = $CI->tag_model->get_advertiser_by_advertiser_id($adv_id,$source_table);
	}
	elseif($campaign_id != null)
	{
		$advertiser = $CI->tag_model->get_advertiser_by_campaign_id($campaign_id);
	}


	if (!$advertiser)
	{
		$result['status'] = "fail";
	}
	else
	{
		$result['status'] = "success";
		$advertiser_name = get_friendly_advertiser_name($advertiser['Name']);
		$result['directory_name'] = $advertiser_name."_".strtolower(base64_encode(base64_encode(base64_encode($advertiser["id"]))));
	}

	return $result;
}

function get_friendly_advertiser_name($adv_name,$replace_space_with_underscore = false)
{
	if (isset($adv_name) && $adv_name != '')
	{
		if (!$replace_space_with_underscore)
		{
			$adv_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', strip_tags(html_entity_decode($adv_name))));
		}
		else
		{
			$adv_name = str_replace(' ', '_', $adv_name);
			$adv_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', strip_tags(html_entity_decode($adv_name))));
			$adv_name = trim($adv_name,'_');
		}		
		return $adv_name;
	}
}

function get_content_for_download_tags($c_id, $adv_id, $tag_file_id = -1, $tag_id = -1, $tag_type = -1, $source_table = null)
{
	$CI =& get_instance();
	$CI->load->model('tag_model');
	
	$result = array();
	$result['status'] = false;
	$all_str = "";
		
	if (empty($c_id) && empty($adv_id))
	{
		$result['err_msg'] = "Provided campaign_id or advertiser_id is invalid";
		return $result;
	}

	if (!empty($c_id))
	{
		$advertiser = $CI->tag_model->get_advertiser_by_campaign_id($c_id);
	}
	elseif (!empty($adv_id))
	{
		$advertiser = $CI->tag_model->get_advertiser_by_advertiser_id($adv_id,$source_table);
	}
		
	if (!isset($advertiser))
	{
		$result['err_msg'] = "Provided campaign_id or advertiser_id is invalid";
		return $result;
	}
	
	if (!empty($c_id))
	{
		$data['tags'] = $CI->tag_model->get_all_tag_files_for_campaign($c_id,$tag_file_id,$tag_id,$tag_type);
	}
	else
	{	
		$data['tags'] = $CI->tag_model->get_all_tag_files_for_advertiser($adv_id,$tag_file_id,$tag_id,$tag_type);
	}

	if ($data['tags'] == false)
	{
		$result['err_msg'] = "No tag files available for this advertiser";
		return $result;
	}
	
	if ($tag_file_id == -1 && $tag_id == -1 && $tag_type == -1 && empty($c_id))
	{
		$all_str = "_ALL";
	}
	
	$data['file_name'] = get_friendly_advertiser_name($advertiser['Name'],true).$all_str."_TAGS.txt";
	$result['status'] = true;
	$result['data'] = $data;
	
	return $result;
}