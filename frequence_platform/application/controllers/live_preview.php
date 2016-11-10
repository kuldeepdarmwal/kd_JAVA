<?php 
class Live_preview extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->helper('form');
		$this->load->helper('html_dom');
		$this->load->library('session');
		$this->load->library('tank_auth');
	}
	public function index()
	{
		show_404();
		return;
	}

	public function nrg($file_index = false)
	{
		if(!$file_index)
		{
			show_404();
			return;
		}
		
		$file_link = "http://creative.vantagelocal.com/livepreview/".$file_index.".html";
		$size_and_site = array(
			'300x250' => array('http://www.ehow.com/', 'http://www.foodnetwork.com/', 'http://weather.weatherbug.com/'), 
			'160x600' => array('http://sports-ak.espn.go.com/nfl/schedule', 'http://www.webmd.com/a-to-z-guides/common-topics/default.htm', 'http://weather.weatherbug.com/'), 
			'728x90'  => array('http://www.webmd.com/', 'http://sports-ak.espn.go.com/nfl/schedule', 'http://espn.go.com/blog/nflnation', 'http://dictionary.reference.com/')
			); //10/15/13 : for now we're only using the first site listed, the others are possibilities for each size.
		$selected_size = false;
		
		foreach($size_and_site as $k => $v)  //get the selected size from the file name
		{
			if(strpos($file_index, $k))
			{
				$selected_size = $k;
			}
		}

		if(!$selected_size)
		{
			echo "You have selected an invalid ad or size: ".$file_index;
			return;
		}
		$height_and_width = explode('x', $selected_size);
		$ad_width =  $height_and_width[0];
		$ad_height = $height_and_width[1];

		$ad_tags = '<div class="vlprevimprt" style="width:'.$ad_width.'px;height:'.$ad_height.'px;">'.file_get_contents($file_link).'</div>';
		$url = $size_and_site[$selected_size][0];		
		$html = new simple_html_dom();
		$string = file_get_contents($url);
	  
		$html->load($string, true, false); //load the scraped web page

		$ad_shown = false;  //so we only replace one ad, ad_show boolean
		if($selected_size == '300x250')
		{
			foreach($html->find('div[id=DartAd300x250]') as $v) //find each div with id equal to 'DartAd300x250' (site specific)
			{
				if(!$ad_shown)
				{
					$v->outertext = $ad_tags;
					$ad_shown = true;
				}
			}
		}
		else if($selected_size == '160x600')
		{
			foreach($html->find('div[class=col-ad-160]') as $v)
			{
				if(!$ad_shown)
				{
					$v->innertext = $ad_tags;
					$ad_shown = true;
				}
			}
		}
		else if ($selected_size == '728x90')
		{
			foreach($html->find('div[id=bannerAd_fmt]') as $v)
			{
				if(!$ad_shown)
				{
					$v->style = 'width:728px;margin: 0px auto 2px;padding-left: 12px;'; //apply a custom style because we are changing the id of the element
					$v->id = 'vlbannerAd_fmt';  //this site in particular was using the id to replace the ad, changed the id.
					$v->innertext = $ad_tags;
					$ad_shown = true;
				}
			}
		}
		else
		{
			echo "Invalid ad size selected: ".$selected_size;
			return;
		}
		foreach($html->find('head') as $v)
		{
			$front = '<base href="'.$url.'" />'."\n"; //so relative links go to the correct website.

			$v->innertext = $front."".$v->innertext."";
		}
		echo $html;
	}

	public function get_page($file_index = false)
	{
		if(!$file_index)
		{
			show_404();
			return;
		}
		$file_link = "http://creative.vantagelocal.com/livepreview/".$file_index.".html";
		$ad_tags = '<div class="vlprevimprt" style="width:300px;height:250px;">'.file_get_contents($file_link).'</div>';
	  
		$url = $this->input->get('p');
	  
		$html = new simple_html_dom();
		$string = file_get_contents($url);
	  
		$html->load($string, true, false);

		$ad_shown = false;
		foreach($html->find('div[id=main_top]') as $v)
		{
			if(!$ad_shown)
			{
				$v->outertext = $ad_tags;
				$ad_shown = true;
			}
		}
		foreach($html->find('div[class=ad iab300x250] div') as $v)
		{
			if(!$ad_shown)
			{
				$v->innertext = $ad_tags;
				$ad_shown = true;
			}
		}
		foreach($html->find('div[class=ad]') as $v)
		{
			if(!$ad_shown)
			{
				$v->innertext = $ad_tags;
				$ad_shown = true;
			}
		}
		foreach($html->find('a') as $v)
		{
			$temp = $v->href;
			if(strpos($temp, 'http://xfinity') !== false)
			{
				$v->href = base_url('live_preview/get_page/'.$file_index.'/?p='.$temp);
			}
		}
		foreach($html->find('head') as $v)
		{
			$front = '<base href="'.$url.'" />'."\n";
			//$front .= '<script type="text/javascript">console.log(jQuery.fn.jquery);</script>';
			$front .= '<link href="'.base_url("css/live_preview/customcss.css").'" rel="stylesheet">'."\n";
			$front .= '<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>';
			$front .= '<script tyle="text/javascript">var vljQuery = {};vljQuery = jQuery.noConflict(true);var vlpreviewadtags='.json_encode($ad_tags).'; var ad_shown="'.$ad_shown.'";</script>';
			$front .= '<script src="'.base_url("js/customMiniNotification.js").'"></script>';

			$v->innertext = $front."".$v->innertext."";
		}
		foreach($html->find("body") as $v)
		{
			$explanation_text = "This is a preview of the advertisement.<br>The above page is a simulation of a live webpage environment.";
			$back = '<div class="vlfltbar" id="vldemonotification">'.$explanation_text.'</div>';
			$v->innertext = '<div id="vl_container_preview1">'.$v->innertext.'</div>'.$back;
		}
		echo $html;
	}
}
?>