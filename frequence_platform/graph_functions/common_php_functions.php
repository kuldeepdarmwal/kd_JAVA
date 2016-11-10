<?php
	//echo 'Inlcuding: common_php_functions.php <br />';

	class TitleTextAndPosition {
		public $text;
		public $positionX;
		public $positionY;

		function __construct($paramText,$paramPositionX,$paramPositionY=0) {
			$this->text = $paramText;
			$this->positionX = $paramPositionX;
			$this->positionY = $paramPositionY;
		}
	}

	function GetDayAfter($date) {
		$time = strtotime($date);
		$nextDay = mktime(0, 0, 0, date("m", $time), date("d", $time)+1, date("Y", $time));
		return date("Y-m-d", $nextDay);
	}

	function FormatPhpVariableForJavascript($pVariable, $rowIndex = 0, $divisor = 0, $isDate = false) 
	{
		$modifiedVar = $pVariable;
		if($rowIndex > 0 && is_numeric($pVariable) && $divisor != 0) {
			$modifiedVar = ($pVariable/$divisor);
		}

		if($rowIndex == 0 && $isDate == true) {
			$trimmed = trim($pVariable);
			$time = strtotime($trimmed);
			$modifiedVar= date('M-j',$time);
		}

		if(is_numeric($pVariable)) {
			return $modifiedVar;
		}
		elseif(is_string($pVariable)) {
			return '"'.$modifiedVar.'"';
		}
		else {
			return 0;
		}
	}

	function getLookBackIndex($QueryResult, $currentRowIndex) {
		$previousRowIndex = $currentRowIndex - 1;
		$previousRowName = $QueryResult->row($previousRowIndex)->One; //mysql_result($QueryResult, $previousRowIndex, 0);

		$isLookBackSame = false;
		$lookBackIndex = $previousRowIndex - 1;
		if($lookBackIndex >= 0) {
			$lookBackRowName = $QueryResult->row($lookBackIndex)->One; //mysql_result($QueryResult, $lookBackIndex, 0);
			if($lookBackRowName == $previousRowName) {
				$isLookBackSame = true;
				return $lookBackIndex;
			}
		}
		return $previousRowIndex;
	}

	function accumulateToOtherValuesAndIncrement(
		&$otherValue, 
		&$sqlResultIndex, 
		$QueryResult, 
		$sqlResultRows, 
		$graphType, 
		$isLogScale, 
		$shouldLookAhead) {

		$otherValues_TotalValueIndex = 0;
		$otherValues_NormalValueIndex = 1;
		$otherValues_RetargetingValueIndex = 0;

		if($shouldLookAhead == false) {
			$sqlResultIndex = getLookBackIndex($QueryResult, $sqlResultIndex);
		}

		$rowName = $QueryResult->row($sqlResultIndex)->One; //mysql_result($QueryResult, $sqlResultIndex, 0);

		$isLookAheadSame = false;
		$currentIndex = $sqlResultIndex;
		$lookAheadIndex = $sqlResultIndex + 1;
		if($lookAheadIndex < $sqlResultRows) {
			$lookAheadRowName = $QueryResult->row($lookAheadIndex)->One; //mysql_result($QueryResult, ($lookAheadIndex), 0);
			if($lookAheadRowName == $rowName) {
				$isLookAheadSame = true;
				//$numJsRowsToRemove += 1;
				$sqlResultIndex += 1; // skip extra data value.
			}
		}

		$numColumns = 1;
		if($graphType == 'table' ||  
			$graphType == 'pieChart' ||  
			$isLogScale == true) {
			$numColumns = 1;
		}   
		else {
			$numColumns = 2;
		} 

		if($numColumns == 1) {
			$campaignTotalIndex = 'Two';
			
			$tempValue = $QueryResult->row_array($sqlResultIndex);
			$rowValue = $tempValue[$campaignTotalIndex]; //mysql_result($QueryResult, $sqlResultIndex, $campaignTotalIndex);
			$otherValue[$otherValues_TotalValueIndex] += $rowValue;
		}
		else {
			$retargetingSubAdGroupTotalIndex = 'Two';
			$retargetingFlagIndex = 'Three';
			$tempCurrent = $QueryResult->row_array($currentIndex);
			$tempAhead = $QueryResult->row_array($lookAheadIndex);

			if( $isLookAheadSame == true) { 
				$otherValue[$otherValues_NormalValueIndex] += $tempCurrent[$retargetingSubAdGroupTotalIndex];//mysql_result($QueryResult, $currentIndex, $retargetingSubAdGroupTotalIndex);
				$otherValue[$otherValues_RetargetingValueIndex ] += $tempAhead[$retargetingSubAdGroupTotalIndex];//mysql_result($QueryResult, $lookAheadIndex, $retargetingSubAdGroupTotalIndex);
			}
			else {
				$isRetargeting = $tempCurrent[$retargetingFlagIndex];//mysql_result($QueryResult, $currentIndex, $retargetingFlagIndex);
				if($isRetargeting == 1) {
					$otherValue[$otherValues_RetargetingValueIndex ] += $tempCurrent[$retargetingSubAdGroupTotalIndex]; //mysql_result($QueryResult, $currentIndex, $retargetingSubAdGroupTotalIndex);
				}
				else {
					$otherValue[$otherValues_NormalValueIndex ] += $tempCurrent[$retargetingSubAdGroupTotalIndex]; //mysql_result($QueryResult, $currentIndex, $retargetingSubAdGroupTotalIndex);
				}
			}
		}
	}

	function getMySqlGraphResponse(
			$businessSelection, 
			$campaignSelection, 
			$startDate, 
			$endDate, 
			$databaseTable, 
			$dataColumn,
			$groupBy, 
			$orderBy, 
			$rankLimit,
			$isTotals,
			$graphType,
			$isLogScale
			) {
			/*
			echo 'Scott - DEBUG<br />';
			echo '$businessSelection:'.$businessSelection.'<br />'; 
			echo '$campaignSelection:'.$campaignSelection.'<br />'; 
			echo '$startDate:'.$startDate.'<br />'; 
			echo '$endDate:'.$endDate.'<br />'; 
			echo '$databaseTable:'.$databaseTable.'<br />'; 
			echo '$dataColumn:'.$dataColumn.'<br />';
			echo '$groupBy:'.$groupBy.'<br />'; 
			echo '$orderBy:'.$orderBy.'<br />'; 
			echo '$rankLimit:'.$rankLimit.'<br />';
			echo '$isTotals:'.$isTotals.'<br />';
			echo '$graphType:'.$graphType.'<br />';
			echo '$isLogScale:'.$isLogScale.'<br />';
			*/

						$sqlGroupBy = "";
						$sqlOrderBy = "";
						$sqlLimitString = "";

						switch($groupBy) {
							case "City":
								$sqlGroupByTable1 = "table1.City";
								$sqlGroupByTable2 = "table2.City";
								break;
							case "Date":
								$sqlGroupByTable1 = "table1.Date";
								$sqlGroupByTable2 = "table2.Date";
								break;
							case "Site":
								$sqlGroupByTable1 = "SUBSTRING_INDEX(table1.Site, ':', 1)";
								$sqlGroupByTable2 = "SUBSTRING_INDEX(table2.Site, ':', 1)";
								break;
							default:
								die("Unknown group for sql query: ".$groupBy);
								break;
						}

						switch($orderBy) {
							case "Date":
								$sqlOrderBy = "ORDER BY 1 ASC, 3 ASC";
								break;
							case "Sum":
								$sqlOrderBy = "ORDER BY 4 DESC, 1 DESC, 3 ASC";
								break;
							default:
								die("Unknown ordering for sql query: ".$orderBy);
								break;
						}

						if(isset($rankLimit) && $rankLimit > 0) {
            	$sqlLimitString = "LIMIT ".($rankLimit * 2);
						}

						$campaignFilter = "";
						if($campaignSelection == 'All Campaigns' ||
							$campaignSelection == 'All'
						) {
							$campaignFilter = "";
						}
						else {
							$campaignFilter = "AND CampaignName = '".$campaignSelection."'";
						}

				//echo 'DEBUG Post parameter setup <br />';

           $graphQueryString = "SELECT 
		            totalsByAdGroup.rowName name,
                SUM(totalsByAdGroup.adGroupTotal) retargetingTotal,
                filteredAdGroups1.IsRetargeting isRetargeting,
                totalsByRowName.total campaignTotal
            FROM 
						   
							(SELECT ID, IsRetargeting
                FROM AdGroups
								WHERE BusinessName = '".$businessSelection."' 
									".$campaignFilter."
                ) filteredAdGroups1,
              
							(SELECT ".$sqlGroupByTable1." rowName, SUM(".$dataColumn.") adGroupTotal, table1.AdGroupID adGroupId
                FROM ".$databaseTable." table1
                WHERE 1=1
									AND table1.Date >= '".$startDate."'
									AND table1.Date < '".GetDayAfter($endDate)."' 
                GROUP BY rowName, adGroupId) totalsByAdGroup,

              (SELECT ".$sqlGroupByTable2." rowName, SUM(".$dataColumn.") total
                FROM ".$databaseTable." table2,
									(SELECT ID, IsRetargeting
                		FROM AdGroups
										WHERE BusinessName = '".$businessSelection."' 
											".$campaignFilter."
                	) filteredAdGroups2
                WHERE 1=1
									AND table2.Date >= '".$startDate."'
									AND table2.Date < '".GetDayAfter($endDate)."' 
									AND table2.AdGroupID = filteredAdGroups2.ID
                GROUP BY rowName) totalsByRowName 

            WHERE totalsByAdGroup.adGroupId = filteredAdGroups1.ID
                AND totalsByAdGroup.rowName = totalsByRowName.rowName
            GROUP BY totalsByAdGroup.rowName, filteredAdGroups1.IsRetargeting
						".$sqlOrderBy." 
						".$sqlLimitString;

				if($isTotals == true) {
					$numColumns = 1;
					if($graphType == 'table' ||  
						$graphType == 'pieChart' ||  
						$isLogScale == true) {
						$numColumns = 1;
					}   
					else {
						$numColumns = 2;
					} 

					if($numColumns == 1) {
						$graphQueryString = '
							SELECT 
								SUM(sumTable.retargetingTotal) tableTotal 
							FROM ('.$graphQueryString.') sumTable';
					}
					else {
						$graphQueryString = '
							SELECT 
								SUM(sumTable.retargetingTotal) tableTotal, sumTable.isRetargeting isRetargeting
							FROM ('.$graphQueryString.') sumTable 
							GROUP BY sumTable.isRetargeting
							ORDER BY 2 ASC';
					}
				}

				//echo 'DEBUG Post string concatenations <br />';
						$graphResponse = mysql_query($graphQueryString) or die(mysql_error());

						return $graphResponse;
	}

	function setupGraphVisualsFromMySqlResponse(
			$mySqlGraphResponse, 
			$graphType, 
			$titles, 
			$elementID, 
			$chart_width, 
			$chart_height, 
			$otherValue,
			$graphColors,
			$isDate,
			$isLogScale,
			$numDecimalPlaces,
			$numVisibleRows,
			$numTableColumns = NULL
	) {
		/*
		switch($graphPurpose) {
			case '':
				$isDate = true;
				$isLogScale = false;
				$numDecimalPlaces = 2;
				break;
			default:
				die("Unknown graph purpose: ".$graphPurpose);
				break;
		}
		*/

		echo '
		<div class="chartHeader">';
			foreach($titles as $title) {
				echo '<div class="chartHeaderText" style="left: '.$title->positionX.'px">'.$title->text.'</div>';
			}
		echo '</div>
		<div class="chartBorder">
			<div id="'.$elementID.'" style="width:'.$chart_width.'px; height:'.$chart_height.'px;"></div>';

				$QueryResult = $mySqlGraphResponse;
				$TotalRows = $QueryResult->num_rows();//mysql_num_rows($mySqlGraphResponse);
				$TotalColumns = $QueryResult->num_fields();
				//$row = mysql_fetch_row($mySqlGraphResponse);
				//$TotalColumns = count($row);
				
				echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
				echo '<script type="text/javascript">';
				include $_SERVER['DOCUMENT_ROOT'].'printable_report_generic_retargeting_graph.php';
				echo '</script>';
		
		echo '</div>';
	}

	// includes $startDate, excludes $endDate
	function echoMissingIntermediateDates($startDate, $endDate, &$jsDataTableRowIndex)
	{
		//echo 'echoMissingIntermediateDates: $startDate: '.$startDate.', $endDate: '.$endDate."\n";
		//global $jsNameColumnIndex, $jsImpressionsColumnIndex, $jsClicksColumnIndex;
		$jsNameColumnIndex = 0;
		$jsImpressionsColumnIndex = 1;
		$jsClicksColumnIndex = 2;

		//echo '$startDate:'.$startDate.', $endDate:'.$endDate;
		assert('strtotime(\''.$startDate.'\') <= strtotime(\''.$endDate.'\')');
		$nextDate = $startDate;
		//'echo '// begin. startDate: '.$startDate.', '.$endDate."\n";
		while($nextDate != $endDate)
		//while(strtotime($nextDate) < strtotime($endDate));
		{
			assert('strtotime(\''.$nextDate.'\') < strtotime(\''.$endDate.'\')');
			$formattedRowName = FormatPhpVariableForJavascript($nextDate);
			echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
			$formattedZero = "0";
			echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsImpressionsColumnIndex.', '.$formattedZero.');';
			echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsClicksColumnIndex .', '.$formattedZero.');';
			echo "\n";
			$jsDataTableRowIndex += 1;

			$nextDate = date("Y-m-d", strtotime("+1 day", strtotime($nextDate)));
		}
		//echo '// end.'."\n";
	}

?>
