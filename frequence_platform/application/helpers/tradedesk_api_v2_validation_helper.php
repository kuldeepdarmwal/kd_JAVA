<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// For v2 of ttd API
function validate_rtb_attributes($column_hierarchy, $value, &$error_array)
{
	$is_success = true;
	$column = implode('::', $column_hierarchy);
	switch ($column_hierarchy[0])
	{
		case 'SiteListIds':
		case 'CreativeIds':
			$ids = json_decode($value, true);
			if (!is_array($ids))
			{
				$is_success = false;
				$error_array[] = "{$column} must be a list of strings";
			}
			break;
		case 'FrequencyPricingSlope':
		case 'AboveFoldAdjustment':
		case 'SiteListFallThroughAdjustment':
		case 'BelowFoldAdjustment':
		case 'UnknownFoldAdjustment':
		case 'BudgetInUSDollars':
		case 'DailyBudgetInUSDollars':
		case 'BaseBidCPMInUSDollars':
		case 'MaxBidCPMInUSDollars':
		case 'UserHourOfWeekUnknownAdjustment':
			if (!is_numeric($value) || !((float)$value == $value))
			{
				$is_success = false;
				$error_array[] = "{$column} must be a decimal and not null";
			}
			break;
		case 'BudgetInImpressions':
		case 'DailyBudgetInImpressions':
			if (!($value === null))
			{
				if (!is_numeric($value) || !((float)$value == $value))
				{
					$is_success = false;
					$error_array[] = "{$column} must be a decimal and not null";
				}
			}
			break;
		case 'PacingEnabled':
		case 'UserHourOfWeekAdjustmentsEnabled':
			if (($value === null) || !(strtolower($value) == 'true' || strtolower($value) == 'false'))
			{
				$is_success = false;
				$error_array[] = "{$column} must be either true or false and not null";
			}
			break;
		case 'AudienceId':
			if (!is_string($value))
			{
				$is_success = false;
				$error_array[] = "{$column} must be a string";
			}
			break;
		case 'FrequencyCap':
		case 'FrequencyPeriodInMinutes':
			if ($value !== null || $value !== '')
			{
				if (!is_numeric($value) || !((int)$value == $value))
				{
					$is_success = false;
					$error_array[] = "{$column} must be a null or an integer";
				}
			}
			break;
		case 'SupplyVendorAdjustments':
		case 'BrowserAdjustments':
		case 'OSAdjustments':
		case 'OSFamilyAdjustments':
		case 'DeviceTypeAdjustments':
			$decoded = json_decode($value, true);
			$is_success = is_bid_adjustments_type($decoded);
			if (!$is_success)
			{
				$error_array[] = 'Check formatting of bid adjustments type';
			}
			break;
		case 'AudienceTargeting':
			$is_success = is_audience_targeting_type($value, array_slice($column_hierarchy, 1), $error_array);
			break;
		case 'ROITarget':
			$decoded = json_decode($value, true);
			$is_success = is_valid_roi_target_type($decoded, $error_array);
			break;
		case 'AdFormatAdjustments':
		case 'GeoSegmentAdjustments':
		case 'UserHourOfWeekAdjustments':
			$decoded = json_decode($value, true);
			$is_success = is_bid_adjustment_type($decoded);
			if (!$is_success)
			{
				$error_array[] = 'Check formatting of bid adjustment type';
			} 
			break;
		default:
			$error_array[] = "Not a valid column type: {$column}";
			$is_success = false;
			break;
	}

	return $is_success;
}

// For v2 of ttd API
function is_audience_targeting_type($var, $column_hierarchy, &$error_array)
{
	$is_success = true;
	if ($column_hierarchy[0] == 'RecencyAdjustments')
	{
		$decoded = json_decode($var, true);
		if (is_array($decoded))
		{
			foreach ($decoded as $ra)
			{
				$is_ra_type = is_recency_adjustment_type($ra);
				if (!$is_ra_type)
				{
					$is_success = false;
					$error_array[] = 'Not valid RecencyAdjustment type';
				}
			}
		}
		else
		{
			$is_success = false;
			$error_array[] = 'Not valid RecencyAdjustments type';
		}
	}
	else if ($column_hierarchy[0] != 'AudienceId')
	{
		$error_array[] = 'Unknown subcolumn found under AudienceTargeting';
		$is_success = false;
	}
	return $is_success;
}

// For v2 of ttd API
function is_valid_roi_target_type($var, &$error_array)
{
	$is_success = true;
	if (is_array($var))
	{
		if (array_key_exists('ROITargetType', $var) && array_key_exists('ROITargetValue', $var))
		{
			if (!(is_numeric($var['ROITargetValue']) && (float)$var['ROITargetValue'] == $var['ROITargetValue']))
			{
				$is_success = false;
				$error_array[] = 'ROITargetValue needs to be a non-null decimal';
			}
		}
		else
		{
			$is_success = false;
			$error_array[] = 'ROITarget needs both an ROITargetType and an ROITargetValue';
		}
	}
	else
	{
		$is_success = false;
		$error_array[] = 'Not valid ROITarget type';
	}
	return $is_success;
}

// For v2 and v3 of ttd API
function is_recency_adjustment_type($var)
{
	if (array_key_exists('RecencyWindowStartInMinutes', $var) && array_key_exists('Adjustment', $var))
	{
		if ($var['RecencyWindowStartInMinutes'] !== null && $var['Adjustment'] !== null)
		{
			if (is_numeric($var['RecencyWindowStartInMinutes']) && is_numeric($var['Adjustment']))
			{
				$is_int = ((int)$var['RecencyWindowStartInMinutes'] == $var['RecencyWindowStartInMinutes']);
				$is_dec = ((float)$var['Adjustment'] == $var['Adjustment']);
				return $is_int && $is_dec;
			}
		}
	}
	return false;
}

// For v2 of ttd API
function is_bid_adjustments_type($var)
{
	if (is_array($var))
	{
		if (array_key_exists('DefaultAdjustment', $var) && $var['DefaultAdjustment'] === null)
		{
			return false;
		}
		if (array_key_exists('Adjustments', $var))
		{
			return (is_array($var['Adjustments']) && is_bid_adjustment_type($var['Adjustments']));
		}
	}
	return false;
}

// For v2 of ttd API
function is_bid_adjustment_type($var)
{
	if (is_array($var))
	{
		return (!empty($var['Id']) && isset($var['Adjustment']));
	}
	return false;
}