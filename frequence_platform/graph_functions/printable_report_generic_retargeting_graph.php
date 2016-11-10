<?php
							// if there are more free row spaces
							// else // there are no more free row spaces
								// total up the rest of the values and add them to $other

							// if is Date based graph?
								// if current date is 1 greater than last date?
								// else // current date is more than one greater than last date
									// add date with no data
									// increment jsDataTableRowIndex


				// $sqlResultIndex, $sqlResultRows, $numDataRows, $jsDataTableRowIndex, $numVisibleRows
				//  12,  	12,  	40, 	9,		20
				//  20,  	20,  	40, 	20,		20
				//  30,  	40,  	40, 	20,		20
				//  30,  	30,  	40, 	18,		20
				//  40,  	40,  	40, 	20,		20


				// $numDataRows = $numVisibleRows * 2
				// $jsDataTableRowIndex = 0
				// $sqlResultIndex = 0
				// while $sqlResultIndex < $sqlResultRows && $sqlResultIndex < $numDataRows && $jsDataTableRowIndex < $numVisibleRows
					// print visible row for data set
					// $jsDataTableRowIndex += 1
					// if data set is 2 data rows
						// $sqlResultIndex += 2
					// else
						// $sqlResultIndex += 1


				// if $sqlResultRows == $sqlResultIndex // ran out of data
				// elseif $numDataRows == $sqlResultIndex // used all data
				// elseif $jsDataTableRowIndex == $numVisibleRows
					// accumulate remainder data in $other category

//echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
//echo '<script type="text/javascript">';

			$numColumns = 1;
			if($graphType == 'table' || 
				$graphType == 'pieChart' || 
				$isLogScale == true) {
				$numColumns = 1;
			}
			else {
				$numColumns = 2;
			}

			//if($numColumns == 1 && $isLogScale != true) {
			if($graphType == 'table') {
				echo 'google.load("visualization", "1", {packages:["table"]});';
			}
			else {
				echo 'google.load("visualization", "1", {packages:["corechart"]});';
			}
			//echo 'alert("1");';

echo '
		google.setOnLoadCallback(draw);
		function draw() {
			var data = new google.visualization.DataTable();
';
			//echo 'alert("2");';
				$jsNameColumnIndex = 0;
				$jsTotalColumnIndex = 1;
				$jsNormalColumnIndex = 2;
				$jsRetargetingColumnIndex = 1;

				$otherValues_TotalValueIndex = 0;
				$otherValues_NormalValueIndex = 1;
				$otherValues_RetargetingValueIndex = 0;

				echo 'data.addColumn( \'string\', \'\');';
				if($numColumns == 1) {
					echo 'data.addColumn( \'number\', \'\');';
				}
				else {
						echo 'data.addColumn( \'number\', \'Retargeting\');';
						echo 'data.addColumn( \'number\', \'Media Targeting\');';
				}
			//	echo 'alert("3");';

				if(isset($TotalRows) && $TotalRows > 0) 
				{
					$sqlResultRows = $TotalRows;
					$numJsRowsToRemove = 0;

					echo 'data.addRows('.$numVisibleRows.');';
					echo "\n";

					//$numDataRows = $numVisibleRows * 2;
					$jsDataTableRowIndex = 0;
					$sqlResultIndex = 0;
					for($sqlResultIndex=0; 
							$sqlResultIndex < $sqlResultRows &&
							//$sqlResultIndex < $numDataRows && // $sqlResultRows is at most $numDataRows.
							$jsDataTableRowIndex < $numVisibleRows; 
							$sqlResultIndex += 1) 
					{
						$tempResult = $QueryResult->row_array($sqlResultIndex);
						$rowName = $tempResult['One']; //mysql_result($QueryResult, $sqlResultIndex, 0);

						$isLookAheadSame = false;
						$currentIndex = $sqlResultIndex;
						$lookAheadIndex = $sqlResultIndex + 1;
						if($lookAheadIndex < $sqlResultRows) {
							$tempAhead = $QueryResult->row_array($lookAheadIndex);
							$lookAheadRowName = $tempAhead['One']; //mysql_result($QueryResult, ($lookAheadIndex), 0);
							if($lookAheadRowName == $rowName) {
								$isLookAheadSame = true;
								//$numJsRowsToRemove += 1;
								$sqlResultIndex += 1; // skip extra data value.
							}
						}
					//	echo 'alert("4");';
						if($numColumns == 1) 
						{
							$campaignTotalIndex = 'Two';
							if($rowName == 'All other cities' || $rowName == 'All other sites') {
								// Skip displaying this row, but add it to the 'Other' data row value.
								$temp = $QueryResult->row_array($sqlResultIndex); //mysql_result($QueryResult, $sqlResultIndex, $campaignTotalIndex);
								$rowValue = $temp[$campaignTotalIndex];
								$otherValue[$otherValues_TotalValueIndex] += $rowValue;
							}
							else {
								$temp = $QueryResult->row_array($sqlResultIndex); //mysql_result($QueryResult, $sqlResultIndex, $campaignTotalIndex);
								$rowTotal = $temp[$campaignTotalIndex];
								
								$formattedRowName = FormatPhpVariableForJavascript($rowName);
								$formattedRowTotal = FormatPhpVariableForJavascript($rowTotal);

								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsTotalColumnIndex .', '.$formattedRowTotal.');';
								echo "\n";

								$jsDataTableRowIndex += 1;
							}
						}
						else 
						{ // Some kind of bar graph.
							$retargetingSubAdGroupTotalIndex = 'Two';
							$retargetingFlagIndex = 'Three';

							if($rowName == 'All other cities' || $rowName == 'All other sites') {
								// Skip displaying this row, but add it to the 'Other' data row value.
								if( $isLookAheadSame == true) { 
									$tempCurrent = $QueryResult->row_array($currentIndex);
									$tempAhead = $QueryResult->row_array($lookAheadIndex);
									$otherValue[$otherValues_NormalValueIndex] += $tempCurrent[$retargetingSubAdGroupTotalIndex]; //mysql_result($QueryResult, $currentIndex, $retargetingSubAdGroupTotalIndex);
									$otherValue[$otherValues_RetargetingValueIndex] += $tempAhead[$retargetingSubAdGroupTotalIndex]; //mysql_result($QueryResult, $lookAheadIndex, $retargetingSubAdGroupTotalIndex);
								}
								else {
									$tempFlag = $QueryResult->row_array($currentIndex);
									$isRetargeting = $tempFlag[$retargetingFlagIndex]; //mysql_result($QueryResult, $currentIndex, $retargetingFlagIndex);
									if($isRetargeting == 1) {
										$tempCurrent = $QueryResult->row_array($currentIndex);
										$otherValue[$otherValues_RetargetingValueIndex] += $tempCurrent[$retargetingSubAdGroupTotalIndex];
									}
									else {
										$tempCurrent = $QueryResult->row_array($currentIndex);
										$otherValue[$otherValues_NormalValueIndex] += $tempCurrent[$retargetingSubAdGroupTotalIndex];
									}
								}
							}
							else { // Is not an $other row value.
								// Display this row.

								$formattedRowName = FormatPhpVariableForJavascript($rowName);
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								if( $isLookAheadSame == true) { 
									// we have the two values
									$tempCurrent = $QueryResult->row_array($currentIndex);
									$tempAhead = $QueryResult->row_array($lookAheadIndex);
									$normalRowTotal = $tempCurrent[$retargetingSubAdGroupTotalIndex];
									$retargetingRowTotal = $tempAhead[$retargetingSubAdGroupTotalIndex];

									$formattedNormalRowTotal = FormatPhpVariableForJavascript($normalRowTotal);
									$formattedRetargetingRowTotal = FormatPhpVariableForJavascript($retargetingRowTotal);

									echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNormalColumnIndex .', '.$formattedNormalRowTotal.');';
									echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsRetargetingColumnIndex .', '.$formattedRetargetingRowTotal.');';
								}
								else { // lookahead rowName is not same as current rowName 
									// we have only one value, need to populate second value with a zero data value.
									$tempCurrent = $QueryResult->row_array($currentIndex);
									$isRetargeting = $tempCurrent[$retargetingFlagIndex];
									if($isRetargeting == 1) {
										$tempCurrent = $QueryResult->row_array($currentIndex);
										$retargetingRowTotal = $tempCurrent[$retargetingSubAdGroupTotalIndex];
										$formattedRetargetingRowTotal = FormatPhpVariableForJavascript($retargetingRowTotal);
										echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNormalColumnIndex .', '. 0 .');';
										echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsRetargetingColumnIndex .', '.$formattedRetargetingRowTotal.');';
									}
									else {
										$tempCurrent = $QueryResult->row_array($currentIndex);
										$normalRowTotal = $tempCurrent[$retargetingSubAdGroupTotalIndex];
										$formattedNormalRowTotal = FormatPhpVariableForJavascript($normalRowTotal);
										echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNormalColumnIndex .', '.$formattedNormalRowTotal.');';
										echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsRetargetingColumnIndex .', '. 0 .');';
									}
								}
								echo "\n";
								// increment jsDataTableRowIndex
								$jsDataTableRowIndex += 1;
							}
						}
					}
					
					// Setup catch-all 'Other' row.
					if($jsDataTableRowIndex < $numVisibleRows) 
					{
						// Not enough data to fill all rows.
						// assert($sqlResultRows == $sqlResultIndex);
						if ($graphNumber == 106 || $graphNumber==107 || $graphNumber==108){
                                                    $formattedRowName = FormatPhpVariableForJavascript('Targeted Regions');
                                                }else{
                                                    $formattedRowName = FormatPhpVariableForJavascript('Other');
                                                }
                                                
						
                                                
                                                
                                                if($numColumns == 1) {
							if($otherValue[$otherValues_TotalValueIndex] != 0) {
								$formattedRowTotal = FormatPhpVariableForJavascript($otherValue[$otherValues_TotalValueIndex]);
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsTotalColumnIndex .', '.$formattedRowTotal.');';
								$jsDataTableRowIndex += 1;
							}
						}
						else { // A bar graph.
							if($otherValue[$otherValues_NormalValueIndex] != 0 || $otherValue[$otherValues_RetargetingValueIndex] != 0) {
								$formattedRowTotalNormal = FormatPhpVariableForJavascript($otherValue[$otherValues_NormalValueIndex]);
								$formattedRowTotalRetargeting = FormatPhpVariableForJavascript($otherValue[$otherValues_RetargetingValueIndex]);
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsNormalColumnIndex .', '.$formattedRowTotalNormal.');';
								echo 'data.setValue('.$jsDataTableRowIndex.', '. $jsRetargetingColumnIndex .', '.$formattedRowTotalRetargeting.');';
								$jsDataTableRowIndex += 1;
							}
						}

						$numJsRowsToRemove = $numVisibleRows - $jsDataTableRowIndex;
					}
					elseif($sqlResultIndex < $sqlResultRows) 
					{
						// More data than can fit in rows.
						//assert($jsDataTableRowIndex == $numVisibleRows);
						// Accumulate remainder data in $other category.

						$sqlResultIndex = getLookBackIndex($QueryResult, $sqlResultIndex);
						for(;$sqlResultIndex < $sqlResultRows; $sqlResultIndex += 1) {
							$shouldLookAhead = true;
							accumulateToOtherValuesAndIncrement($otherValue, $sqlResultIndex, $QueryResult, $sqlResultRows, $graphType, $isLogScale, $shouldLookAhead);
						}

						$overWriteIndex = $jsDataTableRowIndex - 1; // All visible rows are used, need to write over the last one.
						$formattedRowName = FormatPhpVariableForJavascript('Other');

						if($numColumns == 1) {
							if($otherValue[$otherValues_TotalValueIndex] != 0) {
								$formattedRowTotal = FormatPhpVariableForJavascript($otherValue[$otherValues_TotalValueIndex]);
								echo 'data.setValue('.$overWriteIndex .', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$overWriteIndex.', '. $jsTotalColumnIndex .', '.$formattedRowTotal.');';
							}
						}
						else { // A bar graph.
							if($otherValue[$otherValues_NormalValueIndex] != 0 || $otherValue[$otherValues_RetargetingValueIndex] != 0) {
								$formattedRowTotalNormal = FormatPhpVariableForJavascript($otherValue[$otherValues_NormalValueIndex]);
								$formattedRowTotalRetargeting = FormatPhpVariableForJavascript($otherValue[$otherValues_RetargetingValueIndex]);
								echo 'data.setValue('.$overWriteIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$overWriteIndex.', '. $jsNormalColumnIndex .', '.$formattedRowTotalNormal.');';
								echo 'data.setValue('.$overWriteIndex.', '. $jsRetargetingColumnIndex .', '.$formattedRowTotalRetargeting.');';
							}
						}

						$numJsRowsToRemove = $numVisibleRows - $jsDataTableRowIndex;
						//assert($numJsRowsToRemove == 0);
					}
					else 
					{
					  // assert($jsDataTableRowIndex == $numVisibleRows);
						// assert($sqlResultIndex == $sqlResultRows);
						$overWriteIndex = $jsDataTableRowIndex - 1;
						$formattedRowName = FormatPhpVariableForJavascript('Other');

						if($numColumns == 1) {
							if($otherValue[$otherValues_TotalValueIndex] != 0) {
								//die("\nUnexpected value remainging to be displayed.\n");
								$shouldLookAhead = false; // shouldLookBack == true;
								accumulateToOtherValuesAndIncrement($otherValue, $sqlResultIndex, $QueryResult, $sqlResultRows, $graphType, $isLogScale, $shouldLookAhead);
								$sqlResultIndex += 1;
								$formattedRowTotal = FormatPhpVariableForJavascript($otherValue[$otherValues_TotalValueIndex]);
								echo 'data.setValue('.$overWriteIndex .', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$overWriteIndex.', '. $jsTotalColumnIndex .', '.$formattedRowTotal.');';
							}
						}
						else {
							if($otherValue[$otherValues_NormalValueIndex] != 0 || $otherValue[$otherValues_RetargetingValueIndex] != 0) {
								//die("\nUnexpected value remainging to be displayed.\n");
								$shouldLookAhead = false; // shouldLookBack == true;
								accumulateToOtherValuesAndIncrement($otherValue, $sqlResultIndex, $QueryResult, $sqlResultRows, $graphType, $isLogScale, $shouldLookAhead);
								$sqlResultIndex += 1;
								$formattedRowTotalNormal = FormatPhpVariableForJavascript($otherValue[$otherValues_NormalValueIndex]);
								$formattedRowTotalRetargeting = FormatPhpVariableForJavascript($otherValue[$otherValues_RetargetingValueIndex]);
								echo 'data.setValue('.$overWriteIndex.', '. $jsNameColumnIndex .', '.$formattedRowName.');';
								echo 'data.setValue('.$overWriteIndex.', '. $jsNormalColumnIndex .', '.$formattedRowTotalNormal.');';
								echo 'data.setValue('.$overWriteIndex.', '. $jsRetargetingColumnIndex .', '.$formattedRowTotalRetargeting.');';
							}
						}
					}
					
					if($numJsRowsToRemove > 0) 
					{
						echo 'data.removeRows('. ($numVisibleRows - $numJsRowsToRemove) . ', ' . $numJsRowsToRemove . ');';
					}
				}
				else {
					if($numColumns == 1) {
						echo 'data.addRows(2);';
						echo 'data.setValue(0, 0, \'none\');';
						echo 'data.setValue(0, 1, 100);';
						echo 'data.setValue(1, 0, \'none\');';
						echo 'data.setValue(1, 1, 200);';
					}
					else { // Some type of bar garph.
						echo 'data.addRows(2);';
						echo 'data.setValue(0, 0, \'none\');';
						echo 'data.setValue(0, 1, 100);';
						echo 'data.setValue(0, 2, 100);';
						echo 'data.setValue(1, 0, \'none\');';
						echo 'data.setValue(1, 1, 200);';
						echo 'data.setValue(1, 2, 200);';
					}

				}

        echo 'var numberFormatter = new google.visualization.NumberFormat({groupingSymbol: \',\', fractionDigits: 0});';
				if($numColumns == 1) {
					echo 'numberFormatter.format(data, '. 1 .');';
				}
				else {
					for($j=1; $j < 3; $j += 1) {
						echo 'numberFormatter.format(data, '.$j.');';
					}
				}
				

				$vAxisFormatNumber = '#,###';
				$hAxisFormatNumber = '#,###';
				$legendType = '\'none\'';
				if($graphType == 'verticalBarGraph') {
					echo 'var chart = new google.visualization.ColumnChart(document.getElementById(\''.$elementID.'\'));';
					if($numDecimalPlaces > 0) {
						$vAxisFormatNumber = '#,##0.0';
						for($ii=1; $ii < $numDecimalPlaces; $ii += 1) {
							$vAxisFormatNumber = $vAxisFormatNumber . '#';
						}
					}
					$legendType = '\'right\'';
				}
				elseif($graphType == 'horizontalBarGraph') {
					echo 'var chart = new google.visualization.BarChart(document.getElementById(\''.$elementID.'\'));';
					if ($TotalRows < 0)
					{
					$tempMax = $QueryResult->row_array();
					$maxValue = $tempMax['Four'];//mysql_result($QueryResult, 0, 3);
					}
					else
					{
					$maxValue = 0;
					}
					if($isLogScale == false) {
						$legendType = '\'right\'';
					}
				}
				elseif($graphType == 'pieChart') {
					echo 'var chart = new google.visualization.PieChart(document.getElementById(\''.$elementID.'\'));';
					$legendType = '\'right\'';
				}
				elseif($graphType == 'table') {
					echo 'var chart = new google.visualization.Table(document.getElementById(\''.$elementID.'\'));';
				}
				else {
					die('graphType: '.$graphType.' unknown.');
				}

				if($graphType == 'table') {
					echo 'chart.draw(data, {
									width: '.$chart_width.',
									height: '.$chart_height.', 
									allowHtml: true,
									cssClassNames: {
										tableRow: \'tableRowClass\', 
										oddTableRow: \'tableOddRowClass\', 
										headerRow: \'tableHeaderClass\',
										hoverTableRow: \'tableRowClass\',
										selectedTableRow: \'tableRowClass\'
									}
								});';
				}
				else { // $graphType == some kind of bar graph.

					$hAxisString =  '';
					if($isLogScale) {
						$hAxisString = 'hAxis : {logScale: true, minValue: 10, format: \''.$hAxisFormatNumber.'\'}';
					}
					else {
						$hAxisString = 'hAxis : {logScale: false, minValue: 0, format: \''.$hAxisFormatNumber.'\'';
						/*
						if($graphType == 'horizontalBarGraph') {
							$hAxisString .= ',viewWindowMode: \'explicit\',';
							$hAxisString .= 'viewWindow: {min:0,max:10000}';
						}
						 */
						$hAxisString .= '}';
					}


					echo 'chart.draw(data, {
							width: '.$chart_width.',
							height: '.$chart_height.', 
							title: \'\',
							legend: '.$legendType.',
							smoothLine: false,';
							echo '
							isStacked: true,
							fontSize: 10,
							fontName: \'Lato,Verdana\',
							titleTextStyle : {fontSize: 18},'.$hAxisString.',
							vAxis: {minValue: 0, format: \''.$vAxisFormatNumber.'\', baseline: 0},
							pointSize : 4,
							colors : '.$graphColors.',
							sliceVisibilityThreshold : 0,
							chg : [10,10,4,0]});';
				}
	echo '
		}
		';
	//echo '</script>';
?>
