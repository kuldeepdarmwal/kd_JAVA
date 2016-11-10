<?php
/***AD SETS:
    alliance, allterra, andersonwindow, elithomas, landfour, realsaltlake, skinspirit, tastefull, vca, westernathletic
***/
include($_SERVER['DOCUMENT_ROOT'].'/ring_files/rtg_files/simplehtmldom/simple_html_dom.php');

class Rtg_demo extends CI_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->helper('url');
    $this->load->helper('form');
    $this->load->library('session');
    $this->load->library('tank_auth');
    $this->load->model('rtg_model');
  }
  public function index()
  {
    // if(!$this->tank_auth->is_logged_in())
    //  {
    //	redirect('login');
    //  }

    $data['sites_response'] = $this->rtg_model->get_sites();
    $data['ads_response'] = $this->rtg_model->get_adset();
    $this->load->view('rtg/rtg', $data);
  }
  public function sidebar()
  {
    /*
      $sites_response = $this->rtg_model->get_sites();
      $ads_response = $this->rtg_model->get_adset();
      $text = '<table>';
      $text .= '<div class="side_text" style="font-family:\'BebasNeue\', sans-serif;"><tr><td><strong>Step 1:</strong></td><td>Browse Advertising Pages</td></tr></div>';
      $text .= '<tr><td></td><td><select id="siteselect" name="siteselect" style="width:200px;">';
      foreach($sites_response as $v)
      {
      $text .='<option value="'.str_replace("http://www.","",$v["Site"]).'">'.str_replace("http://www.","",$v["Site"]).'</option>';
      }
      $text .= '</select><button type="button" onclick="site_open()">Go</button></td></tr>';
      $text .= '<div class="side_text" style="font-family:\'BebasNeue\', sans-serif;"><tr><td><strong>Step 2:</strong></td><td>Visit Site with Retargeting</td></tr></div>';
      $text .= '<tr><td></td><td><select id="adsiteselect" name="adsiteselect" style="width:200px;">';
      foreach($ads_response as $v)
      {
      $text .='<option value="'.$v["Name"]."|".$v["Site"].'">'.$v["friendly_name"].'</option>';
      }
      $text .= '</select><button type="button" onclick="ad_open()">Go</button></td></tr>';
      $text .= '<div class="side_text" style="font-family:\'BebasNeue\', sans-serif;"><tr><td><strong>Step 3:</strong></td><td>Refresh Page to See Retargeting Ad</td></tr></div>';
      $text .= '<tr><td></td><td><img src ="/ring_files/examicon/reload.png" height="50" width="50"/></td></tr>';
      $text .= '</table>';
      echo $text;
    */
  }
  public function dom($site, $adset)
  {
    if($site == 'nytimes.com')
      {
	$site .= '/pages/national/';
      }
    $list_adsets = $this->rtg_model->get_adset();
    $html = file_get_html("http://www.".$site."/");
    $tracer = FALSE;
    foreach($list_adsets as $v)
      {
	if($v['Name'] == $adset)
	  {
	    $tracer = TRUE;
	  }
      }
    if($tracer == TRUE)
      {
	if($site == 'ask.com')
	  {
	    foreach($html->find('div[id=ad_primaryAd]') as $e)
	      {
		$e->outertext='<iframe id="adi" src="'.site_url().'/ring_files/rtg_files/ads/'.$adset.'/flash_300x250.swf" width="300" height="250" frameborder="0" scrolling="no"></iframe>';
	      }
	    foreach($html->find('div[id=ad_promoAd]') as $e)
	      {
		$e->outertext='<iframe id="adi" src="http://www.ask.com/display.html?cl=ca-aj-cat&amp;ch=&amp;ty=image%2Cflash&amp;size=300x100&amp;kw=&amp;hints=&amp;target=/5480.iac.usa.ask.hp.x.x.dir/;sz=300x100;pos=mr2;log=0;s=as;hhi=159;test=0;ord=1343675123674?" width="300" height="100" frameborder="0" scrolling="no"></iframe>';
	      }
	  }
	else if($site == 'people.com')
	  {
	    foreach($html->find('div[class=adRight]') as $e)
	      {
		$e->outertext='<iframe src="'.site_url().'/ring_files/rtg_files/ads/'.$adset.'/flash_300x250.swf" width="300" height="250" frameborder="0" scrolling="no"></iframe>';
	      }
	  }
	else if($site == 'nytimes.com')
	  {
	    foreach($html->find('img[height=269]') as $e)
	      {
		$e->outertext='<iframe src="'.site_url().'/ring_files/rtg_files/ads/'.$adset.'/flash_300x250.swf" width="300" height="250" frameborder="0" srcolling="no"></iframe>';
	      }
	  }
	else if($site == 'hgtv.com')
	  {
	    foreach($html->find('div[id=bigbox]') as $e)
	      {
		$e->outertext='<iframe src="'.site_url().'/ring_files/rtg_files/ads/'.$adset.'/flash_300x250.swf" width="300" height="250" frameborder="0" srcolling="no"></iframe>';
	      }
	  }
	else if($site == 'ehow.com')
	  {
	    foreach($html->find('div[id=DartAd300x250]') as $e)
	      {
		$e->outertext='<iframe src="'.site_url().'/ring_files/rtg_files/ads/'.$adset.'/flash_300x250.swf" width="300" height="250" frameborder="0" srcolling="no"></iframe>';
	      }
	  }
	else if($site == 'businessweek.com')
	  {
	    foreach($html->find('div[class=module advertisement rectangle]') as $e)
	      {
		$e->outertext='<iframe src="'.site_url().'/ring_files/rtg_files/ads/'.$adset.'/flash_300x250.swf" width="300" height="250" frameborder="0" srcolling="no"></iframe>';
	      }
	  }
      }
    else
      {
	if($site == 'ask.com')
	  {
	    foreach($html->find('div[id=ad_primaryAd]') as $e)
	      {
		$e->outertext='<iframe id="adi" src="http://www.ask.com/display.html?cl=ca-aj-cat&ch=&ty=image%2Cflash&size=300x250&kw=&hints=&target=/5480.iac.usa.ask.hp.x.x.dir/;sz=300x250;log=0;s=as;hhi=159;test=0;ord=1343675123674?" width="300" height="250" frameborder="0" scrolling="no"></iframe>';
	      }
	    foreach($html->find('div[id=ad_promoAd]') as $e)
	      {
		$e->outertext='<iframe id="adi" src="http://www.ask.com/display.html?cl=ca-aj-cat&amp;ch=&amp;ty=image%2Cflash&amp;size=300x100&amp;kw=&amp;hints=&amp;target=/5480.iac.usa.ask.hp.x.x.dir/;sz=300x100;pos=mr2;log=0;s=as;hhi=159;test=0;ord=1343675123674?" width="300" height="100" frameborder="0" scrolling="no"></iframe>';
	      }
	  }
      }
    echo $html;
  }
  public function external()
  {
    $data['sites_response'] = $this->rtg_model->get_sites();
    $data['ads_response'] = $this->rtg_model->get_adset();
    $this->load->view('rtg/rtg', $data);
  }
}
?>
