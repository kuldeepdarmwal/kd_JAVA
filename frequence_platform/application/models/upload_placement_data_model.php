<?php
class Upload_placement_data_model extends CI_Model
{
	public function __construct()
	{
		$this->dataFeedDb = $this->load->database('td_intermediate', TRUE);
		$this->destinationDb = $this->load->database('main', TRUE);
	}
	
	function __destruct()
	{
		$this->dataFeedDb->close();
		$this->destinationDb->close();
	}

	public function OptimizeIntermediateTables()
	{
		$this->dataFeedDb->reconnect();

		$queryOne = 'OPTIMIZE TABLE td_raw_clicks';
		$responseOne = $this->dataFeedDb->query($queryOne);

		$queryTwo = 'OPTIMIZE TABLE td_raw_impressions';
		$responseTwo = $this->dataFeedDb->query($queryTwo);
	}

	public function AggregateImpressionAndClickData($dataType, $startDateAndTime, $endDateAndTime=NULL)
	{
		$start = $startDateAndTime;

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
					ii.AdGroupId AS aid, 
					ii.Site AS ss,  
					COUNT(ii.ImpressionId) AS imp,
					COUNT(cc.ClickId) AS clk, 
					SUM(ii.WinningPriceCPMInDollars) AS tot
				FROM `td_raw_clicks` AS cc RIGHT JOIN td_raw_impressions AS ii 
					ON ii.VantageLocalId = cc.VantageLocalId
				WHERE
					ii.LogEntryTime >= ? AND 
					ii.LogEntryTime < ?
				GROUP BY
					aid, ss
				ORDER BY
					imp DESC, ss ASC
				';
		}
		elseif($dataType == 'cities')
		{
			$query = '
				SELECT
					ii.AdGroupId AS aid,
					ii.City AS cty,
					ii.Region AS reg,
					COUNT(cc.ClickId) AS Clicks,
					COUNT(ii.ImpressionId) AS Impressions, 
					SUM(ii.WinningPriceCPMInDollars) AS TotalCost 
				FROM td_raw_impressions AS ii LEFT JOIN td_raw_clicks AS cc
				ON ii.VantageLocalId = cc.VantageLocalId
				WHERE
					ii.LogEntryTime >= ? AND
					ii.LogEntryTime < ?
				GROUP BY
					aid, reg, cty
				ORDER BY
					Impressions DESC, cty ASC, reg ASC, aid DESC
				';	
		}
		else
		{
			die('Unknown dataType: '.$dataType);
		}

		$this->dataFeedDb->reconnect();
		$response = $this->dataFeedDb->query($query, $bindings);
		return $response;
	}

	public function CleanUpUrl($url)
	{
		$outUrl = preg_replace("/^www\./", "", $url, 1);
	
		return $outUrl;
	}

	public function UploadImpressionAndClickData($dataType, $aggregateResponse, $date)
	{

		$pureDate = date("Y-m-d", strtotime($date));

		if($aggregateResponse->num_rows() > 0)
		{
			if($dataType == 'sites')
			{
				$query = '
				REPLACE
					SiteRecords
				VALUE 
					(	?, ?, ?, ?, ?,
						?, ?)
				';

				foreach($aggregateResponse->result() as $aggregateRow)
				{
					$cost = $aggregateRow->tot / $aggregateRow->imp;
					$cleanUrl = $this->CleanUpUrl($aggregateRow->ss);
					$bindings = array(
						$aggregateRow->aid,
						$aggregateRow->ss,
						$pureDate,
						$aggregateRow->imp,
						$aggregateRow->clk,
						$cost,
						$cleanUrl
					);
					$this->destinationDb->reconnect();
					$response = $this->destinationDb->query($query, $bindings);
				}
			}
			elseif($dataType == 'cities')
			{
				$query = '
				REPLACE
					CityRecords
				VALUE 
					(	?, ?, ?, ?, ?,
						?, ?)
				';

				foreach($aggregateResponse->result() as $aggregateRow)
				{
					$cost = $aggregateRow->TotalCost / $aggregateRow->Impressions;
					$bindings = array(
						$aggregateRow->aid,
						$aggregateRow->cty,
						$aggregateRow->reg,
						$pureDate,
						$aggregateRow->Impressions,
						$aggregateRow->Clicks,
						$cost
					);
					$this->destinationDb->reconnect();
					$response = $this->destinationDb->query($query, $bindings);
				}
			}
		}
	}


	public function UploadImpressionRowToRawDataFeed($impressionRowArray)
	{
		$impressionIdIndex = 1;
		$temp = $impressionRowArray[$impressionIdIndex];
		$vlId = substr($temp, 0, 4).substr($temp, 32, 4);
		$vlIdInt = base_convert($vlId, 16, 10);
		$bindings = 
		array(
			$impressionRowArray[0],
			$impressionRowArray[1],
			$impressionRowArray[2],
			$impressionRowArray[3],
			$impressionRowArray[4],
			$impressionRowArray[5],
			$impressionRowArray[6],
			$impressionRowArray[7],
			$impressionRowArray[8],
			$impressionRowArray[9],
			$impressionRowArray[10],
			$impressionRowArray[11],
			$impressionRowArray[12],
			$impressionRowArray[13],
			$impressionRowArray[14],
			$impressionRowArray[15],
			$impressionRowArray[16],
			$impressionRowArray[17],
			$impressionRowArray[18],
			$impressionRowArray[19],
			$impressionRowArray[20],
			$vlIdInt
		);
		$query = "
			INSERT IGNORE INTO `td_raw_impressions` 
			VALUE (
				?, ?, ?, ?, ?,
				?, ?, ?, ?, ?,
				?, ?, ?, ?, ?,
				?, ?, ?, ?, ?,
				?, ?
			)
			";
		$this->dataFeedDb->reconnect();
		$response = $this->dataFeedDb->query($query, $bindings);
	}

	public function UploadClickRowToRawDataFeed($clickRowArray)
	{
		$displayImpressionIdIndex = 8;
		$temp = $clickRowArray[$displayImpressionIdIndex];
		$vlId = substr($temp, 0, 4).substr($temp, 32, 4);
		$vlIdInt = base_convert($vlId, 16, 10);
		$bindings = 
		array(
			$clickRowArray[0],
			$clickRowArray[1],
			$clickRowArray[2],
			$clickRowArray[3],
			$clickRowArray[4],
			$clickRowArray[5],
			$clickRowArray[6],
			$clickRowArray[7],
			$clickRowArray[8],
			$clickRowArray[9],
			$clickRowArray[10],
			$clickRowArray[11],
			$clickRowArray[12],
			$clickRowArray[13],
			$clickRowArray[14],
			$vlIdInt
		);
		$query = "
			INSERT IGNORE INTO `td_raw_clicks` 
			VALUE (
				?, ?, ?, ?, ?,
				?, ?, ?, ?, ?,
				?, ?, ?, ?, ?,
				?
			)
			";
		$this->dataFeedDb->reconnect();
		$response = $this->dataFeedDb->query($query, $bindings);
	}
}

?>
