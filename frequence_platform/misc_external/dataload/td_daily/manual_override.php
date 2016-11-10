<?php
     $num_clicks = 0;
     $num_impressions = 0;
$num_siterecords = 0;
$num_cityrecords = 0;
$new_imp_table = "td_raw_impressions_".date("Y_m_d",strtotime('26 November 2013'))."_REDO";
$new_clk_table = "td_raw_clicks_".date("Y_m_d", strtotime('26 November 2013'))."_REDO";
$missing_imps = array();
$missing_clks = array();
//$new_imp_table = "td_raw_impressions";
//$new_clk_table = "td_raw_clicks";


//Loads data from Trade desk into an amazon s3 object to be utilized in transfer to raw tables
function LoadData($start_time, $end_time) //$date)
  {
    $progressInfo = array();

    $startDateTimePost = $start_time;
    $endDateTimePost = $end_time;
    
    echo "LoadData: ".$startDateTimePost." - ".$endDateTimePost."\n";
    //Create tables
    global $new_imp_table, $new_clk_table;
    //    $table_gen_impressions = "CREATE TABLE ".$new_imp_table." AS (SELECT * FROM td_raw_impressions WHERE 1=2)";
    //    $table_gen_clicks = "CREATE TABLE ".$new_clk_table." AS (SELECT * FROM td_raw_clicks WHERE 1=2)";
   
    $table_gen_impressions = "
 CREATE  TABLE  `".$new_imp_table."` (  `LogEntryTime` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `ImpressionId` varchar( 36  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `WinningPriceCPMInDollars` decimal( 4, 4  )  NOT  NULL ,
 `SupplyVendor` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `AdvertiserId` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `CampaignId` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `AdGroupId` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `CreativeId` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `AdWidthInPixels` int( 11  )  NOT  NULL ,
 `AdHeightInPixels` int( 11  )  NOT  NULL ,
 `Frequency` int( 11  )  NOT  NULL ,
 `Site` varchar( 512  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `TDID` varchar( 36  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `ReferrerCategoriesList` int( 11  )  NOT  NULL ,
 `FoldPosition` int( 11  )  NOT  NULL ,
 `UserHourOfWeek` int( 11  )  NOT  NULL ,
 `CountryLog` text COLLATE utf8_unicode_ci NOT  NULL ,
 `Region` text COLLATE utf8_unicode_ci NOT  NULL ,
 `Metro` text COLLATE utf8_unicode_ci NOT  NULL ,
 `City` text COLLATE utf8_unicode_ci NOT  NULL ,
 `IPAddress` varchar( 16  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `VantageLocalId` bigint( 10  )  NOT  NULL ,
 KEY  `indx_time_vlid` (  `LogEntryTime` ,  `VantageLocalId`  ) ,
 KEY  `indx_vlid` (  `VantageLocalId`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci";   

$table_gen_clicks = "
 CREATE  TABLE  `".$new_clk_table."` (  `LogEntryTime` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `ClickId` varchar( 36  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `IPAddress` varchar( 16  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `ReferrerUrl` text COLLATE utf8_unicode_ci NOT  NULL ,
 `RedirectUrl` text COLLATE utf8_unicode_ci NOT  NULL ,
 `CampaignId` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `ChannelId` text COLLATE utf8_unicode_ci NOT  NULL ,
 `AdvertiserId` varchar( 32  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `DisplayImpressionId` varchar( 36  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `Keyword` text COLLATE utf8_unicode_ci NOT  NULL ,
 `KeywordId` text COLLATE utf8_unicode_ci NOT  NULL ,
 `MatchType` text COLLATE utf8_unicode_ci NOT  NULL ,
 `DistributionNetwork` text COLLATE utf8_unicode_ci NOT  NULL ,
 `TDID` varchar( 36  )  COLLATE utf8_unicode_ci NOT  NULL ,
 `RawUrl` text COLLATE utf8_unicode_ci NOT  NULL ,
 `VantageLocalId` bigint( 10  )  NOT  NULL ,
 KEY  `indx_time_vlid` (  `LogEntryTime` ,  `VantageLocalId`  ) ,
 KEY  `indx_vlid` (  `VantageLocalId`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci";
    
    $table_gen_Db = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
     mysql_select_db('vantagelocal_prod_raw');
        $result = mysql_query($table_gen_impressions, $table_gen_Db);
     $result = mysql_query($table_gen_clicks, $table_gen_Db);
   
    if($startDateTimePost && $endDateTimePost)
      {
	$progressInfo[] = 'Post: '.$startDateTimePost.' : '.$endDateTimePost;

	//$startDateTime = new DataTime($startDateTimePost);
	$startDateTime = new DateTime($startDateTimePost); //('2012-06-04 00:00:00');
	$startDateHour = new DateTime($startDateTime->format("Y-m-d H:00:00"));
	$startDate = new DateTime($startDateTime->format("Y-m-d"));
	$currentDateHour = clone $startDateHour;
	$timeInterval = new DateInterval('PT1H0M0S');
	$endDateTime = new DateTime($endDateTimePost);
       
	$s3 = new AmazonS3();

	//$bucketByDate = "2012/06/04/";
	//$bucketByDateAndHour = "2012/06/04/01/";

	while($currentDateHour->getTimestamp() < $endDateTime->getTimestamp())
	  {
	    global $missing_imps, $missing_clks;	
	    echo "\nLoop: ".$currentDateHour->format('d-m-Y H:i:s');
	    $progressInfo[] = $currentDateHour->format('Y-m-d H:i:s')."\n";

	    $bucketByDateAndHour = $currentDateHour->format('Y/m/d/H/');
	    echo $bucketByDateAndHour."\n";
	                $impressionBucketsByHour = $s3->get_object_list("thetradedesk-uswest-partners-vantagelocal",
                                                            array(
                                                                  "prefix" => $bucketByDateAndHour,
                                                                  "pcre" => "/impressions/"
                                                                  ));
            if(!LoadRawDataFeed($s3, "impressions", $impressionBucketsByHour, $progressInfo))
	    {
              array_push($missing_imps, $currentDateHour->format("d/m-H"));
            }
          
            $clickBucketsByHour = $s3->get_object_list("thetradedesk-uswest-partners-vantagelocal",
                                                       array(
                                                             "prefix" => $bucketByDateAndHour,
                                                             "pcre" => "/clicks/"
                                                             ));
            if(!LoadRawDataFeed($s3, "clicks", $clickBucketsByHour, $progressInfo))
            {
              array_push($missing_clks, $currentDateHour->format("d/m-H"));
            }
            $currentDateHour->add($timeInterval);

	  }
      }
    else
      {
	$progressInfo[] = 'no post data: '.$startDateTimePost.' : '.$endDateTimePost;
      }
    
    $imp_optimize = "OPTIMIZE TABLE ".$new_imp_table;
    $clk_optimize = "OPTIMIZE TABLE ".$new_clk_table;
    $table_opt_db = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
    mysql_select_db('vantagelocal_prod_raw');
    mysql_query($imp_optimize, $table_opt_db);
    mysql_query($clk_optimize, $table_opt_db);
    //        $imp_prune = "DELETE FROM ".$new_imp_table."WHERE LogEntryTime >= '".date("Y-m-d 00:00:00", strtotime('-0 day'))."'";
    //    $clk_prune = "DELETE FROM ".$new_clk_table."WHERE LogEntryTime >= '".date("Y-m-d 00:00:00", strtotime('-0 day'))."'";
    //  echo $imp_prune."\n".$clk_prune."\n";
// $i_result = mysql_query($imp_prune, $table_opt_db);
    // $c_result = mysql_query($clk_prune, $table_opt_db);
   
    
  
    //$viewData = array();
    $viewData['progressInfo'] = $progressInfo;
    //$viewData['bucketsByHour'] = $clickBucketsByHour;


  }

//Function takes in an s3 object full of trade desk data
//And which table we're going into and dumps the raw data into that table
  function LoadRawDataFeed($s3, $destinationTableType, $sourceFilesList, &$progressInfo)
  {
    foreach($sourceFilesList as $hourBucket)
      {
	$progressInfo[] = $hourBucket;

	$tempFileName = '/home/dataload/td_upload/ttdFileToDecompress.log.gz';
	$objectResponse = $s3->get_object('thetradedesk-uswest-partners-vantagelocal', 
					  $hourBucket, 
					  array('fileDownload' => $tempFileName));

	if($objectResponse->isOK())
	  {
	    $fileData = gzfile($tempFileName);

	    if($fileData)
	      {
		if($destinationTableType == "impressions")
		  {
		 
                    foreach($fileData as $fileLine)
		      {
		      	$cells = str_getcsv($fileLine, "\t");
				
			UploadImpressionRowToRawDataFeed($cells);
		      }
 		    echo "Imps: OK"."\n";
    		    return TRUE;               
		  }
		elseif($destinationTableType == "clicks")
		  {
		    foreach($fileData as $fileLine)
		      {
			$cells = str_getcsv($fileLine, "\t");

			UploadClickRowToRawDataFeed($cells);
		      }
		    echo "Clks: OK"."\n";
		    return TRUE;
		  }
		else
		  {
		    die("Unknown destinationTableType: ".$destinationTableType);
		  }
	      }
	    else
	      {
		die("Failed to open or gunzip for bucket: ".$hourBucket);
	      }
	  }
	else
	  {
	    die("Failed to get response from Amazon s3 object.");
	  }
      }
    return FALSE;
  }
  
//Inserts a row into td_raw_impressions  
  function UploadImpressionRowToRawDataFeed($impressionRowArray)
	{
		$impressionIdIndex = 1;
		$temp = $impressionRowArray[$impressionIdIndex];
		$vlId = substr($temp, 0, 4).substr($temp, 32, 4);
		$vlIdInt = base_convert($vlId, 16, 10);
		global $num_impressions, $new_imp_table, $process_date;
                if(substr($impressionRowArray[0], 0, 10) == $process_date){
				
                $query = "
			INSERT IGNORE INTO ".$new_imp_table."
			VALUE ("
                            ."'".$impressionRowArray[0]."',"
                            ."'".$impressionRowArray[1]."',"
                            ."'".$impressionRowArray[2]."',"
                            ."'".$impressionRowArray[3]."',"
                            ."'".$impressionRowArray[4]."',"
                            ."'".$impressionRowArray[5]."',"
                            ."'".$impressionRowArray[6]."',"
                            ."'".$impressionRowArray[7]."',"
                            ."'".$impressionRowArray[8]."',"
                            ."'".$impressionRowArray[9]."',"
                            ."'".$impressionRowArray[10]."',"
		  ."'".clean_url($impressionRowArray[11])."',"
		  //."'".$impressionRowArray[11]."',"
		            ."'".$impressionRowArray[12]."',"
                            ."'".$impressionRowArray[13]."',"
                            ."'".$impressionRowArray[14]."',"
                            ."'".$impressionRowArray[15]."',"
                            ."'".$impressionRowArray[16]."',"
                            ."'".$impressionRowArray[17]."',"
                            ."'".$impressionRowArray[18]."',"
                            ."'".$impressionRowArray[19]."',"
                            ."'".$impressionRowArray[20]."',"
                            ."'".$vlIdInt."'"
			.")
			";
                $dataFeedDb = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
                mysql_select_db('vantagelocal_prod_raw');
                $result = mysql_query($query, $dataFeedDb);
		if($result == TRUE)
		{
		  $num_impressions++;
		}
		}
	}
//Inserts a row into td_raw_clicks        
        function UploadClickRowToRawDataFeed($clickRowArray)
	{
		$displayImpressionIdIndex = 8;
		$temp = $clickRowArray[$displayImpressionIdIndex];
		$vlId = substr($temp, 0, 4).substr($temp, 32, 4);
		$vlIdInt = base_convert($vlId, 16, 10);
		global $num_clicks, $new_clk_table, $process_date;
		$num_clicks++;
                if(substr($clickRowArray[0], 0, 10) == $process_date){

                $query = "
                        INSERT IGNORE INTO ".$new_clk_table."
                        VALUE ("
                            ."'".$clickRowArray[0]."',"
                            ."'".$clickRowArray[1]."',"
                            ."'".$clickRowArray[2]."',"
                  ."'".mysql_real_escape_string($clickRowArray[3])."',"
                  ."'".mysql_real_escape_string($clickRowArray[4])."',"
                            ."'".$clickRowArray[5]."',"
                            ."'".$clickRowArray[6]."',"
                            ."'".$clickRowArray[7]."',"
                            ."'".$clickRowArray[8]."',"
                            ."'".$clickRowArray[9]."',"
                            ."'".$clickRowArray[10]."',"
                            ."'".$clickRowArray[11]."',"
                            ."'".$clickRowArray[12]."',"
                            ."'".$clickRowArray[13]."',"
                  ."'".mysql_real_escape_string($clickRowArray[14])."',"
                            ."'".$vlIdInt."'"
                        .")";


                $dataFeedDb = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
                mysql_select_db('vantagelocal_prod_raw');
                $result = mysql_query($query, $dataFeedDb);
		}
	}
//Cleans urls to match criteria for a Base_Site
//Moved all regex to this giant if-chain because I clearly have no idea what I'm doing
function clean_url($test_string)
{
	if(preg_match("*google.site-not-provided*", $test_string))
	{
		return "All other sites";
	} 
	if(preg_match("*casale.site-not-provided*", $test_string))
	{
		return "All other sites";
	} 
	if(preg_match("*rubicon.site-not-provided*", $test_string))
	{
		return "All other sites";
	}
	if(preg_match("*appnexus.site-not-provided*", $test_string))
	{
		return "All other sites";
	}
	if(preg_match("*bmp.gunbroker.com*", $test_string))
	{
		return "All other sites";
	}
	if(preg_match("*nym1.ib.adnxs.com*", $test_string))
	{
		return "All other sites";
	}
	if(preg_match("*dakinemedia.net*", $test_string))
	{
		return "All other sites";
	}
        if(preg_match("*mail.yahoo.com*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*ad.doubleclick.net*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*site9264.com*", $test_string))
        {
                return "All other sites";
        }
	if(preg_match("*yahoonetplus.com*", $test_string))
	{
		return "All other sites";
	}
	if(preg_match("*optimized-by.rubiconproject.com*", $test_string))
	{
		return "All other sites";
	}
	if(preg_match("*i.vemba.com*", $test_string))
	{
		return "All other sites";
	}
        if(preg_match("*funnie.st*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*m.datehookup.com*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*meetme.com*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*animefreak.tv*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*coed.com*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*failblog.org*", $test_string))
        {
                return "All other sites";
        }       
        if(preg_match("*rantgirls.com*", $test_string))
        {
                return "All other sites";
        }
        if(preg_match("*cdn.lovedgames.com*", $test_string))
        {
                return "All other sites";
        }
	if($test_string == "{techno.page_url}")
	{
		return "All other sites";
	}
	if(!preg_match('*.[A-Za-z0-9\-]{1,}[\.][A-Za-z\-]{2,3}*', $test_string))
	{
		return "All other sites";
	} 
	if(preg_match("/^(:)/", $test_string))
	{
		return "All other sites";
	} 
	
	$out = $test_string;
	if(preg_match("/^(www.*)/", $test_string))
	{
		$out = substr($out, strrpos($out, "www.")+4);
	}
	if(preg_match("*\?*", $out))
	{	
		$out = substr($out, 0, strrpos($out, '?'));
	}
	if(preg_match("*\@*", $out))
	{
		$out = substr($out,strrpos($out, '@')+1);
	}
	if(preg_match("(\{.*?\})", $out))
	{
		$out = substr($out, strrpos($out, '{')+1, strrpos($out, '}')-1);
	}
	if(preg_match("*\/*", $out))
	{
		$out = substr($out, 0, strrpos($out, '/'));
	} 
	if(!preg_match("*\.*", $out))
	{
		return "All other sites";
	}
	if($out == "")
	{
		return "All other sites";
	} 
	return $out;	
}

//Grabs and aggregates all clicks and impressions
//for a given date, and aggregates and passes rows
//generated to be put into CityRecords/SiteRecords
function ProcessAndTransferData($date_to_process) //$date)
  {
    $viewData = array();
    $progressInfo = array();

    $dateToProcess = $date_to_process;
    

    if($dateToProcess)
      {
	same_day_clean($dateToProcess);
	$progressInfo[] = 'process and transfer '.$dateToProcess;
	$sitesAggregateResponse = AggregateImpressionAndClickData("sites", $dateToProcess);
	echo "Sites: ".mysql_num_rows($sitesAggregateResponse);
	UploadImpressionAndClickData("sites", $sitesAggregateResponse, $dateToProcess);

	$citiesAggregateResponse = AggregateImpressionAndClickData("cities", $dateToProcess);
	echo "Cities: ".mysql_num_rows($citiesAggregateResponse);
	UploadImpressionAndClickData("cities", $citiesAggregateResponse, $dateToProcess);
      }
    else
      {
	$progressInfo[] = 'no post data';
      }

  }

function same_day_clean($today)
{
global $new_clk_table, $new_imp_table;
    
  $id_get_query = "SELECT DISTINCT AdGroupId FROM ".$new_imp_table; 
  
  
  $recordsDb = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
  mysql_select_db('vantagelocal_prod_raw');
  $result = mysql_query($id_get_query, $recordsDb);
  mysql_select_db('vantagelocal_prod');
  
  while ($row = mysql_fetch_array($result, MYSQL_NUM))
  {
    $delete_query = "DELETE FROM SiteRecords WHERE AdGroupId = '".$row[0]."' AND Site = 'All other sites' AND Date = '".$today."'";
    mysql_query($delete_query);
  }


}

//Aggregation function that generates the data which will
//wind up in the Site/CityRecords tables
function AggregateImpressionAndClickData($dataType, $startDateAndTime, $endDateAndTime=NULL)
	{
		$start = $startDateAndTime;
		global $new_clk_table, $new_imp_table;
		if(isset($endDateAndTime))
		{
			$end = $endDateAndTime;
		}
		else
		{
			$roundedToDate = date("Y-m-d", strtotime($startDateAndTime));
			$start = date("Y-m-d H:i:s", strtotime($roundedToDate));
			$end = date("Y-m-d H:i:s", strtotime("+ 1 day", strtotime($roundedToDate)));
		}
		
		$bindings = array($start, $end);
		if($dataType == 'sites')
		{
			$query = '
			SELECT 
    ci.AdGroupId as aid,
    ci.Site as ss,
    ci.Date as date,
    ci.Impressions as imp,
    COALESCE(cc.Clicks, 0) as clk,
    ci.Cost as tot
FROM
    (SELECT a.AdGroupId as AdGroupID,
        a.Site as Site,
    DATE(a.LogEntryTime) as Date,
        count(a.ImpressionId) as Impressions,
        SUM(0) as Clicks,
        SUM(a.WinningPriceCPMInDollars)/1000 as Cost
    FROM '.$new_imp_table.' a 
    GROUP BY a.AdGroupId,
        a.Site,
        DATE(a.LogEntryTime)) as ci 
LEFT JOIN 
    (SELECT a.AdGroupId as AdGroupID,
        a.Site as Site,
        DATE(a.LogEntryTime) as Date,
        SUM(0) as Impressions,
        count(DISTINCT b.ClickId) as Clicks,
        sum(a.WinningPriceCPMInDollars)/1000 as Cost
    FROM '.$new_clk_table.' b
    JOIN '.$new_imp_table.' a
    ON a.VantageLocalId = b.VantageLocalId
    GROUP BY a.AdGroupId,
        a.Site,
        DATE(a.LogEntryTime)) as cc
ON
    ci.AdGroupId = cc.AdgroupId AND
    ci.Site = cc.Site AND
    ci.Date = cc.Date 
WHERE 1
    ORDER BY
    ci.Impressions DESC, ci.Site ASC,  ci.AdGroupId DESC 
				';
		
		}
		elseif($dataType == 'cities')
		{
			$query = '
SELECT 
    ci.AdGroupId as aid,
    ci.City as cty,
    ci.Region as reg,
    ci.Date as date,
    ci.Impressions as Impressions,
    COALESCE(cc.Clicks, 0) as Clicks,
    ci.Cost as Cost
FROM
    (SELECT a.AdGroupId as AdGroupID,
        a.City as City,
        a.Region as Region,
    DATE(a.LogEntryTime) as Date,
        count(a.ImpressionId) as Impressions,
        SUM(0) as Clicks,
        SUM(a.WinningPriceCPMInDollars)/1000 as Cost
    FROM '.$new_imp_table.' a 
    GROUP BY a.AdGroupId,
        a.City,
        a.Region,
    DATE(a.LogEntryTime)) as ci 
LEFT JOIN 
    (SELECT a.AdGroupId as AdGroupID,
        a.City as City,
        a.Region as Region,
        DATE(a.LogEntryTime) as Date,
        SUM(0) as Impressions,
        count(DISTINCT b.ClickId) as Clicks,
        sum(a.WinningPriceCPMInDollars)/1000 as Cost
    FROM '.$new_clk_table.' b
    JOIN '.$new_imp_table.' a
    ON a.VantageLocalId = b.VantageLocalId
    GROUP BY a.AdGroupId,
        a.City,
        a.Region,
        DATE(a.LogEntryTime)) as cc
ON
    ci.AdGroupId = cc.AdgroupId AND
    ci.City = cc.City AND
    ci.Region = cc.Region AND
    ci.Date = cc.Date 
WHERE 1
    ORDER BY
    ci.Impressions DESC, ci.City ASC, ci.Region ASC, ci.AdGroupId DESC
				';	
		       
		}
		else
		{
			die('Unknown dataType: '.$dataType);
		}
		echo $query."\n";
		$db_raw = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
                mysql_select_db('vantagelocal_prod_raw');
                $response = mysql_query($query, $db_raw);
		return $response;
	}

//Inserts aggregate rows into CityRecords/SiteRecords.
function UploadImpressionAndClickData($dataType, $aggregateResponse, $date)
	{

		$pureDate = date("Y-m-d", strtotime($date));
		if(sizeOf($aggregateResponse) > 0)
		{
			if($dataType == 'sites')
			{
			  

			  while($aggregateRow = mysql_fetch_array($aggregateResponse))
				{
				  global $num_siterecords;
				 
					$cost = $aggregateRow['tot'] / $aggregateRow['imp'];
					//$cleanUrl = clean_url($aggregateRow['ss']);
					//$query = 'DELETE FROM SiteRecords WHERE Date = "'.$aggregateRow['date'].'" AND Site = "'.$aggregateRow['ss'].'" AND AdGroupID = "'.$aggregateRow['aid'].'"';

					$query = '
					REPLACE INTO
						SiteRecords
					VALUE 
					('.'"'.$aggregateRow['aid'].'",'.
					   '"'.$aggregateRow['ss'].'",'.
					   '"'.$aggregateRow['date'].'",'.
					   '"'.$aggregateRow['imp'].'",'.
					   '"'.$aggregateRow['clk'].'",'.
					   '"'.$cost.'",'.
					   '"'.$aggregateRow['ss'].'"'.
					   ')';
					//	echo $query."\n";
					$db_raw = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
               				 mysql_select_db('vantagelocal_prod');
                			 $response = mysql_query($query, $db_raw);
					 if($response)
					   {
					     $num_siterecords++;
					   }
				}
			}
			elseif($dataType == 'cities')
			{
			  while($aggregateRow = mysql_fetch_array($aggregateResponse))
				{
				  global $num_cityrecords;
				 
				  $cost = $aggregateRow['Cost'] / $aggregateRow['Impressions'];
					
					$query = '
					REPLACE
						CityRecords
					VALUE 
					('.'"'.$aggregateRow['aid'].'",'.
					   '"'.$aggregateRow['cty'].'",'.
					   '"'.$aggregateRow['reg'].'",'.
					   '"'.$aggregateRow['date'].'",'.
					   '"'.$aggregateRow['Impressions'].'",'.
					   '"'.$aggregateRow['Clicks'].'",'.
					   '"'.$cost.'"'.
					   ')';
					//	echo $query."\n";
					$db_raw = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
               				 mysql_select_db('vantagelocal_prod');
                			 $response = mysql_query($query, $db_raw);
					 if($response)
					   {
					     $num_cityrecords++;
					   }
				}
			}
		}
	}
//Scoops up tail aggregate rows in the SiteRecords table
//Bunches up rows with Less than 10 impressions and no clicks
//and merges them with the "other" row for that AdGroupID/Date
function collate_loose_impressions($date) 
{

 $db_collate = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
 mysql_select_db('vantagelocal_prod');
 
 $scoop_query = 
"INSERT INTO SiteRecords (
SELECT AdGroupID,  'OTHER SITES', DATE, SUM( Impressions ) , SUM( Clicks ) , Cost,  'All other sites' AS Imp
FROM SiteRecords
WHERE Date = '".$date."' AND ((
Impressions <10
AND Clicks =0
)
OR Site =  'All other sites')
GROUP BY AdGroupID, DATE )";

$clear_query = 
  "DELETE FROM SiteRecords WHERE Date = '".$date."' AND ((Impressions < 10 AND Clicks = 0 AND Site != 'OTHER SITES') OR Site = 'All other sites')";



$replace_query = 
  "UPDATE SiteRecords SET Site='All other sites' WHERE Date = '".$date."' AND Base_Site='All other sites'";


mysql_query($scoop_query, $db_collate);
mysql_query($clear_query, $db_collate);
mysql_query($replace_query, $db_collate);

}

?>
<?php
$start_timestamp = new DateTime("now");
$message = "<u>TRADEDESK DATA UPLOAD</u> ".date("M d,Y", strtotime('-30 day'))."<br/>"."PROCESS STARTED: ".date("F j, Y, H:i:s"). " GMT";

require_once('aws-sdk-1.5.6.2/sdk.class.php');
//HEY RYAN EDIT HERE
$early_time = date("m/d/Y 23:00:00", strtotime('25 November 2013'));
$start_time = date("m/d/Y 00:00:00", strtotime('26 November 2013'));
$end_time   = date("m/d/Y 00:00:00", strtotime('27 November 2013'));

//  $start_time = "11/02/2012 00:00:00";                                                                                           
//  $end_time = "11/02/2012 02:00:00";                                                                                             

$process_date = date("Y-m-d", strtotime($start_time));

//$process_date = "2012-10-21";                                                                                                    

echo $start_time. "\n".$end_time."\n".$process_date;

LoadData($early_time, $start_time);                                                  
LoadData($start_time, $end_time);                                               

$message .= "<br/><br/><u>S3 BUCKETS</u><br/>Impressions: ";
$message .= (25-count($missing_imps))."/25 found";
if(count($missing_imps) > 0)
{
  $message .= "(MISSING: ";
  foreach ($missing_imps as $miss_bucket)
  {
    $message .= $miss_bucket."  ";
  }
  $message .= ")";
}
$message .= "<br/>Clicks:". (25-count($missing_clks))."/25 found";
if(count($missing_clks) > 0)
  {
    $message .= "(MISSING: ";
    foreach ($missing_clks as $miss_bucket)
      {
        $message .= $miss_bucket."   ";
      }
    $message .= ")";
  }



$raw_timestamp = new DateTime("now");
$message .= "<br/> <br/><u>RAW DATA LOADED</u> (".date_diff($start_timestamp, $raw_timestamp)->format("%imin, %ss").")<br/>";
$message .= "Raw Clicks: ".number_format($num_clicks)."<br/>".
  "Raw Impressions: ".number_format($num_impressions)."<br/>";

    ProcessAndTransferData($process_date);                                               
$agg_timestamp = new DateTime("now");




  $message .= "<br/><u>AGGREGATE DATA LOADED</u> (".date_diff($raw_timestamp, $agg_timestamp)->format("%imin, %ss").")<br>";


$site_imps = "SELECT SUM(Impressions) FROM SiteRecords WHERE Date = '".$process_date."'";

$site_clicks = "SELECT SUM(Clicks) FROM SiteRecords WHERE Date = '".$process_date."'";

$city_imps = "SELECT SUM(Impressions) FROM CityRecords WHERE Date = '".$process_date."'";

$city_clicks = "SELECT SUM(Clicks) FROM CityRecords WHERE Date = '".$process_date."'";

$loose_imps = "SELECT SUM(Impressions) FROM SiteRecords WHERE Date = '".$process_date."' AND Impressions < 10 AND Clicks = 0 AND Site != 'All other sites'";

$loose_rows = "SELECT COUNT(*) FROM SiteRecords WHERE Date = '".$process_date."' AND Impressions < 10 AND Clicks = 0 AND Site != 'All other sites'";

$num_site_rows = "SELECT COUNT(*) FROM SiteRecords WHERE Date = '".$process_date."'";

$num_city_rows = "SELECT COUNT(*) FROM CityRecords WHERE Date = '".$process_date."'";

$total_s_rows = "SELECT COUNT(*) FROM SiteRecords";

$db_raw = mysql_connect('localhost', 'vlproduser', 'L0cal1s1n!');
mysql_select_db('vantagelocal_prod');

$response = mysql_query($site_imps, $db_raw);
$s_impressions = mysql_result($response, 0);

$response = mysql_query($site_clicks, $db_raw);
$s_clicks = mysql_result($response, 0);

$response = mysql_query($city_imps, $db_raw);
$c_impressions = mysql_result($response, 0);

$response = mysql_query($city_clicks, $db_raw);
$c_clicks = mysql_result($response, 0);

$message .= "New Site Records: ".number_format($num_siterecords)."<br/>".
  "New City Records: ".number_format($num_cityrecords)."<br/>".
  "City/Site Impressions: ";
if($s_impressions == $c_impressions){
  $message .= "OK ";
} else {
  $message .= "NOT OK ";
}
$message .= "(".number_format($c_impressions)."/".number_format($s_impressions).")"."<br/>City/Site Clicks: ";

if($s_clicks == $c_clicks){
  $message .= "OK ";
} else {
  $message .= "NOT OK ";
}
$message .= "(".number_format($c_clicks)."/".number_format($s_clicks).")<br/><br/>";

$response = mysql_query($loose_imps, $db_raw);
$l_imps   = mysql_result($response, 0);

$response = mysql_query($loose_rows, $db_raw);
$l_rows   = mysql_result($response, 0);

collate_loose_impressions($process_date);


$tail_timestamp = new DateTime("now");
$message .= "<br/><br/><u>TAIL SITES AGGREGATION</u> (".date_diff($agg_timestamp, $tail_timestamp)->format("%imin, %ss").")<br/>";
$message .= "Aggregate Tail Site Records:       ".number_format($l_rows)." (".number_format(intval(100*($l_rows/$num_siterecords)))."%)<br/>".
  "Aggregate Tail Site Impressions:    ".number_format($l_imps)." (".number_format(intval(100*($l_imps/$s_impressions)))."%)
<br/><br/><br/>";


$response = mysql_query($num_site_rows, $db_raw);
$s_rows   = mysql_result($response, 0);

$response = mysql_query($num_city_rows, $db_raw);
$c_rows = mysql_result($response, 0);

$response = mysql_query($site_imps, $db_raw);
$num_s_imps = mysql_result($response, 0);

$response = mysql_query($site_clicks, $db_raw);
$num_s_clks = mysql_result($response, 0);

$final_timestamp = new DateTime("now");
$message .= "<u>SUMMARY</u><br> ".
  "Total Site Records: ".number_format($s_rows)."<br/>".
  "Total City Records: ".number_format($c_rows)."<br/>".
  "Total New Impressions: ".number_format($num_s_imps)."<br/>".
  "Total New Clicks: ".number_format($num_s_clks)."<br/>".
  "Total Process Time: ".date_diff($start_timestamp, $final_timestamp)->format("%imin, %ss");

$headers = 'MIME-Version: 1.0'."\r\n";
$headers .= "From: noreply@vantagelocal.com"."\r\n".
'Reply-To: noreply@vantagelocal.com'."\r\n".
  'X-Mailer: PHP/' . phpversion() . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n";
$subject = "Nightly TD Upload (".date("m/d", strtotime('-1 day')).")";
$success = mail("tech@vantagelocal.com", $subject, $message, $headers);


$optimize_sites = "OPTIMIZE TABLE SiteRecords";
$optimize_cities = "OPTIMIZE TABLE CityRecords";
mysql_query($optimize_sites, $db_raw);
mysql_query($optimize_cities, $db_raw);

$av_clean_cities = "DELETE FROM CityRecords WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV')";
$av_clean_sites = "DELETE FROM SiteRecords WHERE AdGroupID IN (SELECT ID FROM AdGroups WHERE Source = 'TDAV')";
mysql_query($av_clean_cities, $db_raw);
mysql_query($av_clean_sites, $db_raw);



?>
