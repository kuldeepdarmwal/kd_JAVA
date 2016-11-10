<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// For v3 of ttd API
function validate_rtb_optimization_attributes($column_hierarchy, $adgroup_data, $value, &$error_array)
{
	$is_success = true;
	$column = implode('::', $column_hierarchy);
	switch ($column_hierarchy[1])
	{
		case 'IsBaseBidAutoOptimizationEnabled':
			if(isset($adgroup_data['ttd_adgroup_object']->RTBAttributes->BudgetSettings->BudgetInImpressions))
			{ 
				$is_success = false;
				$error_array[] = "Base bid auto-optimization cannot be enabled for an ad group with an impression budget or in a campaign with spend prioritization enabled";				    
			}
			break;
		case 'IsAudienceAutoOptimizationEnabled':
			if(isset($adgroup_data['ttd_adgroup_object']->RTBAttributes->AudienceTargeting->AudienceId))
			{ 
				$is_success = is_audience_with_multiple_datagroups($adgroup_data['ttd_adgroup_object']->RTBAttributes->AudienceTargeting->AudienceId);
				if(!$is_success)
				{
					$error_array[] = "Optimization cannot enable for Audience associated with multiple data groups ";
				}
			}
			break;
		case 'IsSiteAutoOptimizationEnabled':
			break;
		case 'IsCreativeAutoOptimizationEnabled':
			break;
		case 'IsSupplyVendorAutoOptimizationEnabled':
			break;
		case 'IsUseClicksAsConversionsEnabled':
			break;
		case 'IsUseSecondaryConversionsEnabled':
			break;
		default:
			$error_array[] = "Not a valid column type: {$column}";
			$is_success = false;
			break;
	}
	return $is_success;
	
}

function is_audience_with_multiple_datagroups($audience_id)
{
	$CI = & get_instance();
	$CI->load->model('tradedesk_model');
	$audience_data = $CI->tradedesk_model->get_audience($audience_id);
	if(sizeof($audience_data['ttd_audience_data']->IncludedDataGroupIds) > 1)
	{
		return false;
	}
	return true;
}
