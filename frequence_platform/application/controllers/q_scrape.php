<?php
class Q_scrape extends CI_Controller
{
  function __construct()
  {
    parent::__construct();

    $this->load->helper(array('form', 'url'));
    $this->load->library('form_validation');
    $this->load->library('tank_auth');
    $this->load->model('q_scrape_model');
  }
  public function index()
  {
    if (!$this->tank_auth->is_logged_in()) {			//If not logged in, goto: login page
      $this->session->set_userdata('referer', 'planner');
      redirect('login');
    }    
    $username		= $this->tank_auth->get_username();
    $role	        = $this->tank_auth->get_role($username);
    if(!($role == 'ops' or $role == 'admin'))
      {
	redirect('director');
      }
    if(array_key_exists('processSites', $_POST))
      {
	$data['sites'] = $_POST['siteInputList'];
	$data['disp'] = $_POST['disp'];
	$this->load->view('vlocal-T1/q_scraper', $data);
      }
    else{
      /*$site_response = $this->q_scrape_model->get_missing_sites(); //gets different in sites between siterecords and demographic records
      $data['rows'] = $site_response->num_rows();  //gets number of returned rows
      $i = 0;
      foreach($site_response->result_array() as $row)  //put site names into sites array
        {
      $sites_array[$i] = $row['Base_Site'];
      $i++;
       }
       $data['sites'] = $sites_array;*/
      $data['rows'] = 1;
      $data['sites'] = array('Testing sites with no database call');  
      $this->load->view('vlocal-T1/q_tool', $data);
    }
  }
  public function get_row_stats()
  {
    $siteName = $_GET['siteName'];
    $display = $_GET['display'];
    $siteDemographics = array();
    $siteDemographics['site'] = $siteName;
    $url = 'https://www.quantcast.com/'.$siteName;
    $responseString = "";
    $flag = 0;
    //each demographic (and reach) are given an associated average value to be added to the average flag if we can't find it on the scraped page
    $internet_avg['Reach'] = 			8388608;
    $internet_avg['Gender_Male'] = 		4194304;
    $internet_avg['Gender_Female'] = 		2097152;
    $internet_avg['Age_Under18'] = 		1048576;
    $internet_avg['Age_18_24'] = 		524288;
    $internet_avg['Age_25_34'] = 		262144;
    $internet_avg['Age_35_44'] = 		131072;
    $internet_avg['Age_45_54'] = 		65536;
    $internet_avg['Age_55_64'] = 		32768;
    $internet_avg['Age_65'] = 			16384;
    $internet_avg['Race_Cauc'] = 		8192;
    $internet_avg['Race_Afr_Am'] = 		4096;
    $internet_avg['Race_Asian'] = 		2048;
    $internet_avg['Race_Hisp'] = 		1024;
    $internet_avg['Race_Other'] = 		512;
    $internet_avg['Kids_No'] = 			256;
    $internet_avg['Kids_Yes'] = 		128;
    $internet_avg['Income_0_50'] = 		64;
    $internet_avg['Income_50_100'] = 		32;
    $internet_avg['Income_100_150'] = 		16;
    $internet_avg['Income_150'] = 		8;
    $internet_avg['College_No'] = 		4;
    $internet_avg['College_Under'] = 		2;
    $internet_avg['College_Grad'] = 		1;
    $demo_strings = array( //Names of demographics on quantcast
			  'Gender_Male' => 'Male',
			  'Gender_Female' => 'Female',
			  'Age_Under18' => '< 18',
			  'Age_18_24' => '18-24',
			  'Age_25_34' => '25-34',
			  'Age_35_44' => '35-44',
			  'Age_45_54' => '45-54',
			  'Age_55_64' => '55-64',
			  'Age_65' => '65\+',
			  'Race_Cauc' => 'Caucasian',
			  'Race_Afr_Am' => 'African American',
			  'Race_Asian' => 'Asian',
			  'Race_Hisp' => 'Hispanic',
			  'Race_Other' => 'Other',
			  'Kids_No' => 'No Kids ',
			  'Kids_Yes' => 'Has Kids ',
			  'Income_0_50' => '\$0-50k',
			  'Income_50_100' => '\$50-100k',
			  'Income_100_150' => '\$100-150k',
			  'Income_150' => '\$150k\+',
			  'College_No' => 'No College',
			  'College_Under' => 'College',
			  'College_Grad' => 'Grad School'
			   );
    //index array to be used in for loops below
    $gender_index = array('Gender_Male', 'Gender_Female');
    $age_index = array('Age_Under18', 'Age_18_24', 'Age_25_34', 'Age_35_44', 'Age_45_54', 'Age_55_64', 'Age_65');
    $race_index = array('Race_Cauc', 'Race_Afr_Am', 'Race_Asian', 'Race_Hisp', 'Race_Other');
    $kids_index = array('Kids_No', 'Kids_Yes');
    $income_index = array('Income_0_50', 'Income_50_100', 'Income_100_150', 'Income_150');
    $college_index = array('College_No', 'College_Under', 'College_Grad');
    $totals = array( //total array for each set of demographics
		    'gender' => 0,
		    'age' => 0,
		    'race' => 0,
		    'kids' => 0,
		    'income' => 0,
		    'college' => 0
		     );
    $averages = array(//if demographic value not found at quantcast, use this average instead
	              'Reach' => 0,
		      'Gender_Male' => .49,
		      'Gender_Female' => .51,
		      'Age_Under18' => .18,
		      'Age_18_24' => .12,
		      'Age_25_34' => .17,
		      'Age_35_44' => .20,
		      'Age_45_54' => .17,
		      'Age_55_64' => .10,
		      'Age_65' => .06,
		      'Race_Cauc' => .76,
		      'Race_Afr_Am' => .09,
		      'Race_Asian' => .04,
		      'Race_Hisp' => .09,
		      'Race_Other' => .02,
		      'Kids_No' => .50,
		      'Kids_Yes' => .50,
		      'Income_0_50' => .51,
		      'Income_50_100' => .29,
		      'Income_100_150' => .12,
		      'Income_150' => .08,
		      'College_No' => .45,
		      'College_Under' => .41,
		      'College_Grad' => .14
		      );
    
    $ch = curl_init($url);//grab the page
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; InfoPath.2; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 1.1.4322)2011-10-16 20:22:33');	
    $curl_scraped_page = curl_exec($ch);
    curl_close($ch);
    $UV_string = $this->getUV($curl_scraped_page);//get reach
    if(!$UV_string)  //if reach not found, set it to average
      {
	$flag |= $internet_avg['Reach'];
	$UV_value = $averages['Reach'];
      }
    else{  //if reach found strip trailing M or K and apply associated multiplier
      if (substr($UV_string, -1) =='K'){
	$multiplier = 1/300000;
      }else if(substr($UV_string, -1) =='M'){
	$multiplier = 1/300;
      }else{
        $multiplier = 1/300000000; 
      }
      $UV_value = round(floatval($UV_string)*$multiplier,6);
    }
    $siteDemographics['Domain'] = $siteName;
    $siteDemographics['Reach'] = $UV_value;
    //grab results from get_index, if nothing found set it to average and add this demo's average number to the average flag
    //if index found multiply it by its associated average from the averages array and finally lump the result into totals for that demographic
    foreach($gender_index as $v) //get gender
      { 
	$siteDemographics[$v] = $this->get_index($curl_scraped_page, $demo_strings[$v]);
	if(!$siteDemographics[$v]){$siteDemographics[$v] = $averages[$v];$flag |= $internet_avg[$v];}
	else {$siteDemographics[$v] *= $averages[$v];}
	$totals['gender'] += $siteDemographics[$v];
      }
    foreach($age_index as $v)  //get age
      {
	$siteDemographics[$v] = $this->get_index($curl_scraped_page, $demo_strings[$v]);
	if(!$siteDemographics[$v]){$siteDemographics[$v] = $averages[$v];$flag |= $internet_avg[$v];}
	else {$siteDemographics[$v] *= $averages[$v];}
	$totals['age'] += $siteDemographics[$v];
      }
    foreach($race_index as $v) //get race
      {
	$siteDemographics[$v] = $this->get_index($curl_scraped_page, $demo_strings[$v]);
	if(!$siteDemographics[$v]){$siteDemographics[$v] = $averages[$v];$flag |= $internet_avg[$v];}
	else {$siteDemographics[$v] *= $averages[$v];}
	$totals['race'] += $siteDemographics[$v];
      }
    foreach($kids_index as $v) //get kids
      {
	$siteDemographics[$v] = $this->get_index($curl_scraped_page, $demo_strings[$v]);
	if(!$siteDemographics[$v]){$siteDemographics[$v] = $averages[$v];$flag |= $internet_avg[$v];}
	else {$siteDemographics[$v] *= $averages[$v];}
	$totals['kids'] += $siteDemographics[$v];
      }
    foreach($income_index as $v) //get income
      {
	$siteDemographics[$v] = $this->get_index($curl_scraped_page, $demo_strings[$v]);
	if(!$siteDemographics[$v]){$siteDemographics[$v] = $averages[$v];$flag |= $internet_avg[$v];}
	else {$siteDemographics[$v] *= $averages[$v];}
	$totals['income'] += $siteDemographics[$v];
      }
    foreach($college_index as $v) //get college
      {
	$siteDemographics[$v] = $this->get_index($curl_scraped_page, $demo_strings[$v]);
	if(!$siteDemographics[$v]){$siteDemographics[$v] = $averages[$v];$flag |= $internet_avg[$v];}
	else {$siteDemographics[$v] *= $averages[$v];}
	$totals['college'] += $siteDemographics[$v];
      }
    //(index*average/total) to get relative average.  Makes sure demographics will add to 100%;
    $siteDemographics['Gender_Male'] = round($siteDemographics['Gender_Male']/$totals['gender'], 2);
    $siteDemographics['Gender_Female'] = round($siteDemographics['Gender_Female']/$totals['gender'], 2);
    
    $siteDemographics['Age_Under18'] = round($siteDemographics['Age_Under18']/$totals['age'], 2);
    $siteDemographics['Age_18_24'] = round($siteDemographics['Age_18_24']/$totals['age'], 2);
    $siteDemographics['Age_25_34'] = round($siteDemographics['Age_25_34']/$totals['age'], 2);
    $siteDemographics['Age_35_44'] = round($siteDemographics['Age_35_44']/$totals['age'], 2);
    $siteDemographics['Age_45_54'] = round($siteDemographics['Age_45_54']/$totals['age'], 2);
    $siteDemographics['Age_55_64'] = round($siteDemographics['Age_55_64']/$totals['age'], 2);
    $siteDemographics['Age_65'] = round($siteDemographics['Age_65']/$totals['age'], 2);
    
    $siteDemographics['Race_Cauc'] = round($siteDemographics['Race_Cauc']/$totals['race'], 2);
    $siteDemographics['Race_Afr_Am'] = round($siteDemographics['Race_Afr_Am']/$totals['race'], 2);
    $siteDemographics['Race_Asian'] = round($siteDemographics['Race_Asian']/$totals['race'], 2);
    $siteDemographics['Race_Hisp'] = round($siteDemographics['Race_Hisp']/$totals['race'], 2);
    $siteDemographics['Race_Other'] = round($siteDemographics['Race_Other']/$totals['race'], 2);

		$siteDemographics['Kids_No'] = round($siteDemographics['Kids_No']/$totals['kids'], 2);
		$siteDemographics['Kids_Yes'] = round($siteDemographics['Kids_Yes']/$totals['kids'], 2);

		$siteDemographics['Income_0_50'] = round($siteDemographics['Income_0_50']/$totals['income'], 2);
		$siteDemographics['Income_50_100'] = round($siteDemographics['Income_50_100']/$totals['income'], 2);
		$siteDemographics['Income_100_150'] = round($siteDemographics['Income_100_150']/$totals['income'], 2);
		$siteDemographics['Income_150'] = round($siteDemographics['Income_150']/$totals['income'], 2);

		$siteDemographics['College_No'] = round($siteDemographics['College_No']/$totals['college'], 2);
		$siteDemographics['College_Under'] = round($siteDemographics['College_Under']/$totals['college'], 2);
		$siteDemographics['College_Grad'] = round($siteDemographics['College_Grad']/$totals['college'], 2);

		$totals['gender'] = $siteDemographics['Gender_Male'] + $siteDemographics['Gender_Female'];
		if($totals['gender'] > 1){ $siteDemographics['Gender_Female'] -= $totals['gender'] - 1;}
		else if($totals['gender'] < 1){ $siteDemographics['Gender_Female'] += 1 - $totals['gender'];}

		$totals['age'] = $siteDemographics['Age_Under18'] + $siteDemographics['Age_18_24'] + $siteDemographics['Age_25_34'] + $siteDemographics['Age_35_44'] + $siteDemographics['Age_45_54'] + $siteDemographics['Age_55_64'] + $siteDemographics['Age_65'];
		if($totals['age'] > 1){ $siteDemographics['Age_Under18'] -= $totals['age'] - 1;}
		else if($totals['age'] < 1){ $siteDemographics['Age_Under18'] += 1 - $totals['age'];}

		$totals['race'] = $siteDemographics['Race_Cauc'] + $siteDemographics['Race_Afr_Am'] + $siteDemographics['Race_Asian'] + $siteDemographics['Race_Hisp'] + $siteDemographics['Race_Other'];
		if($totals['race'] > 1){ $siteDemographics['Race_Cauc'] -= $totals['race'] - 1;}
		else if($totals['race'] < 1){ $siteDemographics['Race_Cauc'] += 1 - $totals['age'];}

		$totals['kids'] = $siteDemographics['Kids_No'] + $siteDemographics['Kids_Yes'];
		if($totals['kids'] > 1){ $siteDemographics['Kids_No'] -= $totals['kids'] - 1;}
		else if($totals['kids'] < 1){ $siteDemographics['Kids_No'] += 1 - $totals['kids'];}

		$totals['income'] = $siteDemographics['Income_0_50'] + $siteDemographics['Income_50_100'] + $siteDemographics['Income_100_150'] + $siteDemographics['Income_150'];
		if($totals['income'] > 1){$siteDemographics['Income_0_50'] -= $totals['income'] - 1;}
		else if($totals['income'] < 1){$siteDemographics['Income_0_50'] += 1 - $totals['income'];}

		$totals['college'] = $siteDemographics['College_No'] + $siteDemographics['College_Under'] + $siteDemographics['College_Grad'];
		if($totals['college'] > 1){$siteDemographics['College_No'] -= $totals['college'] - 1;}
		else if($totals['college'] < 1){$siteDemographics['College_No'] += 1 - $totals['college'];}

		$siteDemographics['Average_Flag'] = $flag;
		if($display == 'upload')
		{
			if(!$this->q_scrape_model->insert_demographic($siteDemographics))
			{
				echo "UPLOAD FAILED: ";
			}
		}
		if(true)
		{
			$responseString .= $siteDemographics['Domain'];
			$responseString .= ','.$siteDemographics['Reach'];
			$responseString .= ','.$siteDemographics['Gender_Male'];
			$responseString .= ','.$siteDemographics['Gender_Female'];
			$responseString .= ','.$siteDemographics['Age_Under18'];
			$responseString .= ','.$siteDemographics['Age_18_24'];
			$responseString .= ','.$siteDemographics['Age_25_34'];
			$responseString .= ','.$siteDemographics['Age_35_44'];
			$responseString .= ','.$siteDemographics['Age_45_54'];
			$responseString .= ','.$siteDemographics['Age_55_64'];
			$responseString .= ','.$siteDemographics['Age_65'];
			$responseString .= ','.$siteDemographics['Race_Cauc'];
			$responseString .= ','.$siteDemographics['Race_Afr_Am'];
			$responseString .= ','.$siteDemographics['Race_Asian'];
			$responseString .= ','.$siteDemographics['Race_Hisp'];
			$responseString .= ','.$siteDemographics['Race_Other'];
			$responseString .= ','.$siteDemographics['Kids_No'];
			$responseString .= ','.$siteDemographics['Kids_Yes'];
			$responseString .= ','.$siteDemographics['Income_0_50'];
			$responseString .= ','.$siteDemographics['Income_50_100'];
			$responseString .= ','.$siteDemographics['Income_100_150'];
			$responseString .= ','.$siteDemographics['Income_150'];
			$responseString .= ','.$siteDemographics['College_No'];
			$responseString .= ','.$siteDemographics['College_Under'];
			$responseString .= ','.$siteDemographics['College_Grad'];
			$responseString .= ','.$siteDemographics['Average_Flag'];
		}
		echo $responseString;  //print result inline
		return;
	}

  //parses scraped quantcast page (from curl) and tries to find the demographic index.  returns false on failure.
  private function get_index($scraped_page, $demo_string)
  {
    //OLD EXPRESSION: $expression = '/<td class="bucket-label" valign="top">'.$demo_string.'<\/td>'."\n".'<td class="below-average-line" valign="top">'."\n".'<div class="below-average(.+?)'."\n".'<\/div>'."\n".'<\/td>'."\n".'<td style="width:53px;"(.+?)'."\n".'<\/td>'."\n".'(.+?)valign="top">'."\n".'(.+?)<\/td>/s';
    //$expression = '/<td class="bucket-label">'.$demo_string.'<\/td>(.+?)<td class="below-average-line" valign="top">(.+?)<div class="below-average(.+?)&nbsp;(.+?)<\/div>(.+?)<\/td>(.+?)<td style="width:53px;"(.+?)<\/td>(.+?)<td class="index-digi(.+?)" valign="top">(.+?)<\/td>/s';
    //this expressions assumes line breaks removed
    $expression = '/<td class="bucket-label">'.$demo_string.'<\/td><td class="below-average-line" valign="top"><div class="below-average(.+?)&nbsp;<\/div><\/td><td style="width:53px;"(.+?)<\/td><td class="index-digi(.+?)" valign="top">(.+?)<\/td>/ms';
    

    preg_match($expression, $this->remove_breaks_from_page($scraped_page), $match);
    if(count($match)==0)
      {
	return FALSE;
      }
    return (floatval(trim($match[4])));
  }
  //DEPRECIATED
  private function get_better_demo($scrapedPage, $demoString){
    $get_demo_expr = '/'.$demoString.'<\/td>'."\n".'<td class="index-digit(.+?)">'."\n".'(.+?)%<\/td>/s';
    preg_match($get_demo_expr, $scrapedPage, $match);
    if(count($match)==0)
      {
	//echo "preg_match = ".$y."; NA ";
	return "NA";
      }
    return (floatval($match[2])/100);
  }
  //DEPRECIATED
  private function get_internet_average($scrapedPage, $demoString){
    $get_demo_expr = '/'."\n".$demoString.'<\/td>'."\n".'<td class="below-average-line" valign="top">'."\n".'<div class="below-average(.+?)><span class="below-average-percent-display(.+?)<\/span>&nbsp;'."\n".'<\/div>'."\n".'<div class="internet-average-bar (.+?)"><span class="prior-percent">(.+?)%<\/span> internet average/s';

    preg_match($get_demo_expr, $scrapedPage, $match);

		if(count($match)==0)
		{
			return "NA";
		}
		return (floatval($match[4])/100);
	}
	
	//DEPRECIATED
	private function getNewDemo($scrapedPage,$demoString)
	{

		//echo $scrapedPage;
		$regex = '/\<td class="bucket-label"\>'.$demoString.'\<\/td\>
		\<td class="below-average-line" valign="top"\>
		\<div class="below-average(.+?)
		\<\/div\>
		\<\/td\>\n
		\<td style="width:53px;"(.+?)
		\<\/td\>
		(.+?)valign="top"\>
		(.+?)\<\/td\>/s';

		//echo $regex."<br>";
		preg_match($regex,$scrapedPage,$match);
		if (count($match)<4) 
		{
			return "NA";
		}else
		{
		//return $match[4];
			return (floatval($match[4])/100);
		}
	}
	//DEPRECIATED
	private function getDemo($scrapedPage,$demoString)
	{
		$regex = '/\<td class="digit"\>(.+?)\<\/td\>
		\<td class="title border"\>'.$demoString.'\<\/td>/';

		preg_match($regex,$scrapedPage,$match);
		if (count($match)==0) 
		{
			return "NA";
		}
		else
		{
			return (floatval($match[1])/100);
		}
	}

	//GRABS REACH
	private function getUV($scrapedPage)
	{
		$regex = '/\<td class="reach" id="reach-wd:(.+?)"\>'."\n".'(.+?)\<span class="label"\>US\<\/span\>/s';

		preg_match($regex,$scrapedPage,$match);

		 //print '<pre>'; print_r($scrapedPage); print '</pre>';///m@

		if (count($match)==0) 
		{
		return NULL;
		}
		else
		{
			$tempen = trim($match[2]);
			if ($tempen == "N/A")
			{
				return NULL;
			}
			return $tempen;
		}
	}
	//NOT CURRENTLY IN USE: FORMAT A DECIMAL NUMBER FOR BINARY TO A CERTAIN NUMBER OF BITS (DEFAULT TO 8)
	private function fmt_binary($x, $numbits = 8) 
	{
		// Convert to binary
		$bin = decbin($x);
		$bin = substr(str_repeat(0,$numbits),0,$numbits - strlen($bin)) . $bin;
		// Split into x 4-bits long
		$rtnval = '';
		for ($x = 0; $x < $numbits/4; $x++) 
		{
			$rtnval .= substr($bin,$x*4,4);
		}
		// Get rid of first space.
		return ltrim($rtnval);
	}


  private function remove_breaks_from_page($curl_scraped_page){
     $output = str_replace(array("\r\n", "\r"), "\n", $curl_scraped_page);
     $lines = explode("\n", $output);
     $new_lines = array();
 
     foreach ($lines as $i => $line) {
         if(!empty($line))
             $new_lines[] = trim($line);
     }
     return implode($new_lines);
   }

}
