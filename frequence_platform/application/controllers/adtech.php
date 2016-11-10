<?php
class adtech extends CI_Controller
{
	private $is_java_bridge_setup = false;
	private $customer_id = "406488";
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('al_model');
		$this->load->model('cup_model');
		$this->load->model('cdn_model');

		try {
			include_once('libraries/adtech/Java.inc');
			$this->is_java_bridge_setup = true;
			$this->customer_id = $this->customer_id;
		}
		catch(Exception $exception) {
			$this->is_java_bridge_setup = false;
		}
	}

	public function get_advertisers()
	{
		try
		{
			if(!$this->is_java_bridge_setup)
			{
				throw new Exception('Failed to load Java Bridge (Java.inc)');
			}

			$java_applet = new java("adtech_java_bridge.AdtechJavaBridge");
			$result_json = $java_applet->adtech->get_advertisers_for_customer($this->customer_id);
			$result = json_decode($result_json);
			if(empty($result->m_errors))
			{
				$string = $result->m_result;
				$advertisers = explode("#", $string );
				$output = "<option value='0'>Please Select</option><option value='new'>*New*</option>";
				if($string != "")
				{
				    foreach ($advertisers as $advertiser_string)
				    {
					    $advertiser_details = explode("|", $advertiser_string);
					    if(isset($advertiser_details[1])){
					    $output .= "<option value='".$advertiser_details[0]."'>".$advertiser_details[1]."</option>";
					    }
				    }
				}
				echo $output;
			}
			else
			{
				echo "";
			}
		}
		catch (Exception $exception)
		{
			echo "";
		}
	}

	public function get_campaigns_for_advertiser($advertiser_id)
	{
		try
		{
			if(!$this->is_java_bridge_setup)
			{
				throw new Exception('Failed to load Java Bridge (Java.inc)');
			}

			$java_applet = new java("adtech_java_bridge.AdtechJavaBridge");
			$result_json = $java_applet->adtech->get_campaigns_for_advertiser($advertiser_id);
			$result = json_decode($result_json);
			if(empty($result->m_errors))
			{
				$string = $result->m_result;
				$output = "<option value='0'>Please Select</option><option value='new'>*New*</option>";
				if($string != "")
				{
				    $campaigns = explode("#", $string );
				    foreach ($campaigns as $campaign_string)
				    {
					    $campaign_details = explode("|", $campaign_string);
					    if(isset($campaign_details[1])){
					    $output .= "<option value='".$campaign_details[0]."'>".$campaign_details[1]."</option>";
					    }
				    }
				}
				echo $output;
			}
			else
			{
				echo "";
			}
		}
		catch (Exception $exception)
		{
			echo "";
		}
	}
    
	public function insert_new_advertiser()
	{
		try
		{
			if(!$this->is_java_bridge_setup)
			{
				throw new Exception('Failed to load Java Bridge (Java.inc)');
			}

			$advertiser_name = $_POST['adv_name'];
			$java_applet = new java("adtech_java_bridge.AdtechJavaBridge");
			$result_json = $java_applet->adtech->create_new_advertiser_from_template_advertiser($advertiser_name, $this->customer_id);
			$result = json_decode($result_json);
			if(empty($result->m_errors))
			{
				$thing = $result->m_result;
				echo $thing;
			}
			else
			{
				echo "";
			}
		}
		catch (Exception $exception)
		{
			echo "";
		}
	}
    
	public function insert_new_campaign()
	{
		try
		{
			if(!$this->is_java_bridge_setup)
			{
				throw new Exception('Failed to load Java Bridge (Java.inc)');
			}

			$campaign_name = $_POST['campaign_name'];
			$landing_page = $_POST['landing_page'];
			$advertiser_id = $_POST['advertiser_id'];
			$java_applet = new java("adtech_java_bridge.AdtechJavaBridge");
			$result_json = $java_applet->adtech->create_new_campaign_from_template_campaign($campaign_name, $landing_page, $advertiser_id, $this->customer_id);
			$result = json_decode($result_json);
			if(empty($result->m_errors))
			{
				$thing = $result->m_result;

				echo $thing;
			}
			else
			{
				// TODO: handle error
				echo "";
			}
		}
		catch (Exception $exception)
		{
				// TODO: handle error
			echo "";
		}
	}
    
	public function publish_adtech_creative()
	{
		try
		{
			if(!$this->is_java_bridge_setup)
			{
				throw new Exception('Failed to load Java Bridge (Java.inc)');
			}

        $creative_size = $_POST["cr_size"];
	$version_id = $_POST['version'];
	$adset_id = $this->cup_model->get_adset_by_version_id($version_id);
        $adtech_advertiser_id = $_POST["adtech_a_id"];
        $adtech_campaign_id = $_POST["adtech_c_id"];
        $vl_campaign_id = $_POST["vl_c_id"];
        $assets = $this->cup_model->get_assets_by_adset($version_id, $creative_size);
        $builder_version = $this->cup_model->get_builder_version_by_version_id($version_id);
	//CHECK FOR ASSETS?
        if($this->cup_model->files_ok($assets, $creative_size, $builder_version, false, false, true))
        {
	    $campaign = $this->al_model->get_campaign_details_by_version_id($version_id);
	    $java_applet = new java("adtech_java_bridge.AdtechJavaBridge");
	    $landing_page_json = "".$java_applet->adtech->retrieve_landing_page_for_campaign($adtech_campaign_id);
	    $lp_result = json_decode($landing_page_json);
	    if(empty($lp_result->m_errors))
	    {
		$landing_page = $lp_result->m_result;
	    }
	    else 
	    {
		echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
		return;
	    }
	    //Get image link
	     $creative = $this->cup_model->prep_file_links($assets,$creative_size, $vl_campaign_id);
	     $rand_temp_folder = rand(1, 10000000);
	     shell_exec("mkdir /tmp/".$rand_temp_folder);
	     if($creative['backup_image'])
	     {
		$backup_image_url = $creative['backup_image'];
		$exploded_url = explode('/', $backup_image_url);
		$backup_image_filename = "/tmp/".$rand_temp_folder."/".$exploded_url[count($exploded_url)-1];
		//download image
		file_put_contents($backup_image_filename, fopen($backup_image_url, 'r'));
	     }
	     
	    //Build file html, save to file
	    $html = $this->cup_model->get_ad_html( $version_id, $creative_size,true);
	    
	    
	    if($html == false)
	    {
		shell_exec("rm -rf /tmp/".$rand_temp_folder);
		echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
		return;
	    }
	    
	    $html = str_replace("%c%u", "_ADCLICK_", $html); //Click Macro
	    $html = $this->no_adtech_url($html); //URL Fix, breaking srcs and hrefs apart
	    
	    //Replaced for clickthroughs to work properly
	    $html = str_replace('var advurl = "_ADCLICK_";', 'var advurl = "_ADCLICK_" + escape("'.$landing_page.'");', $html); 
	    $html = str_replace('var dcadvurl = escape("_ADCLICK_");', 'var dcadvurl = escape("_ADCLICK_") + escape("'.$landing_page.'");', $html);
	    
	    //Replace escaped urls for testing purposes
	    $html = $html = str_replace('+advurl', '+unescape(advurl)', $html);
	    $html = $html = str_replace('+ advurl', '+ unescape(advurl)', $html);
	    
	    file_put_contents("/tmp/".$rand_temp_folder."/index.html", $html);
	    
	    
	    //gzip those boys, get that filename
	    chdir("/tmp/".$rand_temp_folder);
	    shell_exec("zip -j /tmp/".$rand_temp_folder."/banner.zip /tmp/".$rand_temp_folder."/*");
	    //zipping /* is supposedly a horrible idea Ryan. You are what you code, you monster, you
	    
	    
	   //convert size to sizeid
	   //728x90 - 225
	    //300x250 - 170
	    //160x600 - 154
	    //336x280 - 171
	   //320x50 - 47
	   $size = 0;
	   switch($creative['creative_width'])
	   {
	       case 728:
		   $size = 225;
		   $size_name = "728x90";
		   $placement_id = "3103134";
		   break;
	       case 300:
		   $size = 170;
		   $size_name = "300x250";
		   $placement_id = "3103133";
		   break;
	       case 160:
		   $size = 154;
		   $size_name = "160x600";
		   $placement_id = "3103519";
		   break;
	       case 336:
		   $size = 171;
		   $size_name = "336x280";
		   $placement_id = "3103136";
		   break;
	       case 320:
		   $size = 3055;
		   $size_name = "320x50";
		   $placement_id = "3103135";
		   break;
	       default:
		   echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL: Invalid Creative Size</span>';
		   return;
	   }
	   
		   $creative_entry = $this->cup_model->get_adtech_ids_from_db((array(
							'size'=>$creative_size,
							'version_id'=>$version_id
						)));


				
				$result_json = $java_applet->adtech->get_advertisers_for_customer($this->customer_id);
				if($creative_entry[0]['adtech_campaign_id'] != $adtech_campaign_id || $creative_entry[0]['adtech_flight_id'] == NULL)
				{
				    $campaign_result_json = $java_applet->adtech->create_new_campaign_flight_from_template((string)$adtech_campaign_id, (string)$placement_id, $campaign['Name']." ".$size_name, $this->customer_id, $adtech_advertiser_id);
				    $campaign_result = json_decode($campaign_result_json);
				    if(empty($campaign_result->m_errors))
				    {
					    $flight_id = $campaign_result->m_result;
					    $flight_id = "".$flight_id;
					    if($flight_id == "")
					    {
						    echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAILED TO BUILD FLIGHT</span>';
						    return;
					    }
				    }
				    else
				    {
					    echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAILED TO BUILD FLIGHT</span>';
					    return;
				    }

				    $this->cup_model->update_creative_adtech_details((array(
					    'adtech_flight_id'=>$flight_id,
					    'adtech_campaign_id'=>$adtech_campaign_id,
					    'size'=>$creative_size,
					    'version_id'=>$version_id
				    )));

				    $banner_result_json = $java_applet->adtech->create_new_banner_for_campaign($flight_id, $campaign['Name']." ".$size_name,"/tmp/".$rand_temp_folder."/banner.zip", $size, $landing_page);
				    $image_banner_result_json = $java_applet->adtech->create_new_banner_for_campaign($flight_id, $campaign['Name']." ".$size_name."_IMG", $backup_image_filename, $size, $landing_page);
				} 
				else
				{
				    $flight_id = $creative_entry[0]['adtech_flight_id'];
				    $banner_result_json = $java_applet->adtech->update_banner_for_campaign($flight_id, $campaign['Name']." ".$size_name,"/tmp/".$rand_temp_folder."/banner.zip", $size, $landing_page);
				    $image_banner_result_json = $java_applet->adtech->update_banner_for_campaign($flight_id, $campaign['Name']." ".$size_name."_IMG",$backup_image_filename, $size, $landing_page);
				}
				$start_json = $java_applet->adtech->start_campaign_with_id($flight_id);
				$banner_result = json_decode($banner_result_json);
				$image_banner_result = json_decode($image_banner_result_json);
				$start_result = json_decode($start_json);
				
				if(empty($banner_result->m_errors) && empty($image_banner_result->m_errors) && empty($start_result->m_errors))
				{
					$tags_result_json = $java_applet->adtech->retrieve_tags_from_flight($flight_id, (string)$adtech_campaign_id);
					$tags_result = json_decode($tags_result_json);
					if(empty($tags_result->m_errors))
					{
						$tag = $tags_result->m_result;
						$tag = $this->secure_adtech_tag("".$tag);
						$tag = $this->fix_noscript_tag($tag);
						$tag = $this->tolower_timestamps($tag);
						$tag = $this->no_clickmacro($tag);
						$this->cup_model->update_creative_adtech_tag((array(
							'adtech_ad_tag'=>$tag,
							'size'=>$creative_size,
							'version_id'=>$version_id
						)));
						echo '<span class="label label-success"><i class="icon-thumbs-up icon-white"></i>SUCCESS</span>';
					}
					else
					{
						echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
						// TODO: handle error
					}
				}
				else
				{
					echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
					// TODO: handle error
				}

				//clean up
				shell_exec("rm -rf /tmp/".$rand_temp_folder);
				
				
			}
			else
			{
				echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
			} 
		}
		catch (Exception $exception)
		{
			echo '<span class="label-warning"><i class="icon-thumbs-down icon-white"></i>FAIL</span>';
			// TODO: handle error
		}
	}
	
    private function secure_adtech_tag($tag)
    {
	return str_replace("http://", "https://", $tag);
    }
    
    private function no_clickmacro($tag)
    {
	return str_replace("[CLICKMACRO]", "", $tag);
    }
    private function tolower_timestamps($tag)
    {
	return str_replace("[TIMESTAMP]", "[timestamp]", $tag);
    }
    private function no_adtech_url($html)
    {
      $search = array(
        " href",
        ".href",
        " src",
        ".src"
      );

      $replace = array(
        " hre' + 'f",
        "['hre' + 'f']",
        " sr' + 'c",
        "['sr' + 'c']"
      );
      $result = str_replace($search, $replace, $html);
      return $result;
    }
    private function fix_noscript_tag($tag)
    {
	$noscript = substr($tag, strpos($tag, "<noscript>"), (strpos($tag, "</noscript>") - strpos($tag, "<noscript>") + 11));
	$tag = str_replace($noscript, "", $tag);
	$tag = str_replace("</script>", "</scr'+'ipt>", $tag);
	$tag = str_replace("\n", "", $tag);
	$noscript = str_replace("<noscript>", "<noscript>\n", $noscript);
	$noscript = str_replace("</noscript>", "\n</noscript>", $noscript);
	$comments;
	preg_match_all("/<!--(.|\s)*?-->/" ,$tag, $comments);

	$split = preg_split("/<!--(.|\s)*?-->/" ,$tag);
	$split[1] = str_replace(">", ">'+\n'", $split[1]);
	
	$split[1] = str_replace("src", "'+\n'src", $split[1]); 
	$split[1] = str_replace("SRC", "'+\n'SRC", $split[1]);
	
	$newtag = $comments[0][0]." \n".$noscript."\n<script language=\"javascript\">document.write(\n'".$split[1]."'\n);</script>"." \n ".$comments[0][1];
	return $newtag;

	
	
    }
}
?>
