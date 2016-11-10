<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

//$route['sales_home'] = ''
//$route['privacy'] = base_url().'privacy/index';

$route['rfp/old'] = 'mpq_v2/rfp';
$route['rfp/old/(:num)'] = 'mpq_v2/rfp/$1';
$route['rfp/old/create'] = 'mpq_v2/rfp_gate';
$route['rfp/old/create/(:num)'] = 'mpq_v2/rfp_gate/$1';
$route['rfp/old/success'] = 'mpq_v2/rfp_success';

$route['rfp/gate/(:num)'] = 'rfp/index';
$route['rfp/gate'] = 'rfp/index';
$route['rfp/targeting/(:num)'] = 'rfp/index';
$route['rfp/budget/(:num)'] = 'rfp/index';
$route['rfp/builder/(:num)'] = 'rfp/index';
$route['rfp/builder'] = 'rfp/index';
$route['rfp'] = 'rfp/redirect';
$route['rfp/(:num)'] = 'rfp/redirect/$1';

$route['io'] = 'insertion_orders/io';
$route['io/(:num)'] = 'insertion_orders/io/$1';

$route['io/old'] = 'mpq_v2/io';
$route['io/old/(:num)'] = 'mpq_v2/io/$1';

$route['advertisers_main/(.+@.+[\.].+|:num|all)'] = 'advertisers_main/index/$1';

$route['linker'] = 'third_party_linker/index';

$route['banner_intake/review/(:num)'] = 'banner_intake/review_single/$1';
$route['banner_intake/review/page/(:num)'] = 'banner_intake/review/$1';

$route['share_gallery/(:any)'] = 'custom_gallery/view_custom_gallery/$1';

$route['mpq/ref/(:any)'] = 'mpq_v2/single_page/$1';

$route['creative_uploader/(:num)'] = 'creative_uploader/index/$1';

$route['tradedesk'] = 'tradedesk/index';

$route['campaigns_main/insert_new_campaigns'] = 'campaigns_main/insert_new_campaigns';
$route['campaigns_main'] = 'campaigns_main/health_v2';
$route['campaigns'] = 'campaigns_main/campaigns_sales_view'; 
$route['campaigns_main/tags/(:any)'] = 'campaigns_main/health_v2/$1';
$route['campaigns_main/old'] = 'campaigns_main/health';
$route['campaigns_main/old/(:num)'] = 'campaigns_main/health/$1';

$route['campaign_setup/create_new_campaign_from_insertion_order'] = 'campaign_setup/create_new_campaign_from_insertion_order';
$route['campaign_setup/ajax_get_assigned_advertiser_owners'] = 'campaign_setup/ajax_get_assigned_advertiser_owners';

$route['campaign_setup/add_new_note'] = 'campaign_setup/add_new_note';
$route['campaign_setup/update_note_bad_flag'] = 'campaign_setup/update_note_bad_flag';
$route['campaign_setup/update_imp_flag'] = 'campaign_setup/update_imp_flag';
$route['campaign_setup/generate_campaign_notes'] = 'campaign_setup/generate_campaign_notes';

$route['campaign_setup/get_notes_for_campaign'] = 'campaign_setup/get_notes_for_campaign';

$route['campaign_setup/select2_get_allowed_advertiser_owners'] = 'campaign_setup/select2_get_allowed_advertiser_owners';
$route['campaign_setup/get_initial_timeseries_start_date_array'] = 'campaign_setup/get_initial_timeseries_start_date_array';
$route['campaign_setup/ajax_add_advertiser_owner'] = 'campaign_setup/ajax_add_advertiser_owner';
$route['campaign_setup/ajax_remove_advertiser_owner'] = 'campaign_setup/ajax_remove_advertiser_owner';
$route['campaign_setup/edit_flights'] = 'campaign_setup/edit_flights';
$route['campaign_setup/edit_flights/(:any)'] = 'campaign_setup/edit_flights/$1';
$route['campaign_setup/(:any)'] = 'campaign_setup/index/$1';
$route['campaign_setup/campaign_tag_file'] = 'campaign_setup/campaign_tag_file';

$route['publisher'] = 'publisher/index';

// under development
$route['vab'] = 'ad_machina';
$route['vab/preview'] = 'ad_machina/demo'; // allow throwaway friendly URL section
$route['vab/preview/(:any)/(:any)'] = 'ad_machina/demo/$2'; // allow throwaway friendly URL section
$route['vab/(:any)'] = 'ad_machina/$1';
$route['sample_ads'] = 'ad_machina';
$route['sample_ads/demo/(:any)/(:any)'] = 'ad_machina/demo/$2'; // allow throwaway friendly URL section
$route['sample_ads/(:any)'] = 'ad_machina/$1';

$route['test_director_v2'] = 'vl_platform_v2/test_director_v2';

$route['sample_ad/(:any)'] = 'crtv/get_spec_ad/$1';

$route['creative_uploader/get_sample_ad_display_url'] = 'creative_uploader/ajax_get_sample_ad_display_url';

$route['mpq/get_existing_rfp_geo_data'] = 'mpq_v2/get_existing_rfp_geo_data';
$route['mpq/get_option_refresh'] = 'mpq_v2/ajax_get_option_refresh_for_mpq';
$route['mpq/save_zips'] = 'mpq_v2/ajax_save_zip_codes';
$route['mpq/submit_mpq'] = 'mpq_v2/ajax_submit_mpq';
$route['mpq/initialize_new_location'] = 'mpq_v2/ajax_initialize_new_location';
$route['mpq/save_location_name'] = 'mpq_v2/ajax_save_location_name';
$route['mpq/remove_location'] = 'mpq_v2/ajax_remove_location';
$route['mpq/recalculate_option_for_option_engine'] = 'mpq_v2/ajax_recalculate_option_for_option_engine';
$route['mpq/recalculate_reach_frequency_for_option_engine'] = 'mpq_v2/ajax_recalculate_reach_frequency_for_option_engine';
$route['mpq/get_initial_geo_data'] = 'mpq_v2/ajax_get_initial_geo_data';
$route['mpq/save_geo_search/(:any)'] = 'mpq_v2/ajax_save_geo_radius_search/$1';
$route['mpq/mpq_submitted'] = 'mpq_v2/mpq_submitted';
$route['mpq/map'] = 'mpq_v2/get_geo_map_and_stats';
$route['mpq/map/(:num)'] = 'mpq_v2/get_geo_map_and_stats/$1';
$route['mpq/map/(:num)/(:any)'] = 'mpq_v2/get_geo_map_and_stats/$1/$2';
$route['mpq/map/(:num)/(:num)'] = 'mpq_v2/get_geo_map_and_stats/$1/$2';
$route['mpq/modify_zipcodes'] = 'mpq_v2/ajax_modify_zipcodes';
$route['mpq/check_mpq_session'] = 'mpq_v2/ajax_check_mpq_session';
$route['mpq/check_rfp_session'] = 'mpq_v2/ajax_check_rfp_session';
$route['mpq'] = 'mpq_v2/single_page';

$route['embed/(:any)/summary'] = 'mpq_v2/embed_submitted/$1';
$route['embed/(:any)/(:any)'] = 'mpq_v2/embed_single_page/$1/$2';

$route['proposal_builder/get_all_mpqs/io'] = 'proposal_builder/get_all_mpqs_insertion_orders';
$route['proposal_builder/get_all_mpqs/proposals'] = 'proposal_builder/get_all_mpqs_proposals';
$route['proposal_builder/get_all_mpqs'] = '/proposal_builder/get_all_mpqs_proposals';
$route['proposal_builder/get_all_mpqs/rfp'] = 'proposal_builder/get_all_mpqs_rfps';

$route['review_screen_shots'] = 'screen_shot_approval/get_screen_shots_structure_and_data';
$route['screen_shot_approval/review'] = 'screen_shot_approval/get_screen_shots_structure_and_data';
$route['screen_shot_approval/get_screen_shots_data'] = 'screen_shot_approval/ajax_get_screen_shots_data';
$route['screen_shot_approval/set_screen_shot_approval'] = 'screen_shot_approval/set_screen_shot_status';

$route['report_v2/download_csv'] = 'report_v2/ajax_download_csv';
$route['report'] = 'report_v2/get_structure_and_data';

$route['reports/download_csv'] = 'report_legacy/ajax_download_csv';
$route['reports/get_report_data'] = 'report_legacy/ajax_get_report_data';
$route['reports'] = 'report_legacy/get_structure_and_data';

$route['campaign_health/get_overweight_detail/(:any)'] = 'campaign_health/get_overweight_detail/$1';
$route['campaign_health/impression_total/(:any)'] = 'campaign_health/impression_total/$1';
$route['campaign_health/lifetime_dates_impressions/(:any)'] = 'campaign_health/lifetime_dates_impressions/$1';
$route['campaign_health/ajax_index'] = 'campaign_health/ajax_index';
$route['campaign_health'] = 'campaign_health/index';
$route['campaign_health/depr'] = 'campaign_health/depr';
$route['campaign_health/remove_from_health_check/(:any)'] = 'campaign_health/remove_from_health_check/$1';
$route['campaign_health/add_to_health_check/(:any)'] = 'campaign_health/remove_from_health_check/$1';
$route['campaign_health/graveyard'] = 'campaign_health/graveyard';
$route['campaign_health/get_campaign_details/(:any)'] = 'get_campaign_details/$1';
$route['campaign_health/get_campaign_details_id/(:any)'] = 'get_campaign_details/$1';


$route['forgot_password'] = 'auth/forgot_password';
$route['reset_password/(:any)'] = 'auth/reset_password/$1';
$route['change_password'] = 'auth/change_password';
$route['client_invite/(:any)'] = 'auth/client_invite/$1';

$route['city_data'] = 'graphs/city_data';

$route['media_plan_category/form'] = 'media_plan_category/form';
$route['media_plan_category/form/(:any)'] = 'media_plan_category/form/$1';
$route['media_plan_channel_sites/(:any)'] = "media_plan_category/get_sites/$1";

$route['healthcheck'] = 'healthcheck';

$route['planner'] = 'director/planner';

$route['smb/get_channel_sites/(:any)'] = "smb/GetMediaPlanChannelSites/$1";
$route['smb/(:any)'] = "smb/$1";

$route['uploadtradedeskdata'] = 'upload_trade_desk_data';
$route['uploadtradedeskdata/(:any)'] = 'upload_trade_desk_data/$1';

$route['couponadmin'] = "admin";
$route['couponadmin/(:any)'] = "admin/$1";
$route['coupon/(:any)'] = "frontend/coupon/$1" ;
$route['admin/metrics/(:num)'] = "metric/view/$1" ;
$route['coupon'] = "frontend/coupon" ;

//Access Manager
$route['access_manager'] = "spectrum_account_data_loader/access_manager" ;
$route['access_manager/download_csv'] = "spectrum_account_data_loader/download_csv" ;
$route['partner_forms'] = 'partner_forms_dashboard';
$route['partner_forms/mpq'] = 'partner_forms_dashboard/mpq';
$route['partner_forms/new_campaign_ticket'] = 'partner_forms_dashboard/new_campaign_ticket';

$route['rollout'] = 'director/rollout';
$route['creative/(:any)'] = 'director/creative/$1';
$route['info/(:any)'] = 'director/info/$1';
$route['testimonials/(:any)'] = 'director/testimonials/$1';
$route['ad_demo/(:any)'] = 'director/ad_demo/$1';
$route['ad_demo'] = 'director/ad_demo';
$route['demo_files/(:any)'] = 'director/demo_files/$1';

$route['rf/GetPageData'] = 'rf/GetPageData';
$route['rf/get_settings_data'] = 'rf/GetSettingsData';
$route['rf/get_slider'] = 'rf/ReachFrequencySliderBody';
$route['rf/get_body/(:any)'] = 'rf/ReachFrequencyMainBody/$1';
$route['rf/get_body'] = 'rf/ReachFrequencyMainBody';
$route['rf/save_price'] = 'rf/SavePrice';
$route['rf/(:any)'] = 'rf/get_rf_performance/$1';
$route['rf/getDemo'] = 'rf/getDemo/$1';
$route['rf/getPopulation'] = 'rf/getPopulation/$1';
$route['rf/updateImpressions/(:any)'] = 'rf/updateImpressions/$1';


$route['graphs'] = 'graphs/index';
$route['graphs/graph_(:any)'] = 'graphs/graph_$1';

//lap_lite
$route['lap_lite/switch_media_plan'] = 'lap_lite/switch_media_plan';
$route['lap_lite/load_advertiser_data'] = 'lap_lite/load_advertiser_data';
$route['lap_lite/map'] = 'lap_lite/get_lap_lite_map';
$route['lap_lite/demos/(:any)'] = 'lap_lite/get_demographics/$1';
$route['lap_lite/demos'] = 'lap_lite/get_demographics';
$route['lap_lite/spark/(:any)'] = 'lap_lite/get_spark/$1';

$route['lap_lite/getZips/(:any)'] = 'lap_lite/getZips/$1';
$route['lap_lite/getInit/(:any)'] = 'lap_lite/getInit/$1';
$route['lap_lite/saveParameters/(:any)'] = 'lap_lite/saveParameters/$1';
$route['lap_lite/modify_zipcodes'] = 'lap_lite/modify_zipcodes';

$route['lap_lite/flexigrid/(:any)'] = 'lap_lite/flexigrid/$1';
$route['lap_lite/flexigrid/'] = 'lap_lite/flexigrid/$1';
$route['lap_lite/flexigrid/manual'] = 'lap_lite/flexigrid_manual';
$route['lap_lite/get_initial_data'] = 'lap_lite/get_initial_data';

$route['lap_lite/(:any)'] = 'lap_lite/region_table/$1';

//lap_lite

$route['siterank/(:any)'] = 'siterank/site_table/$1';
$route['siterank'] = 'siterank/select_your_audience';

$route['login'] = 'auth/login';
$route['login/(:any)'] = 'auth/login/$1';
$route['toolkit'] = 'auth/login';
$route['register'] = 'auth/register';

$route['auth/business'] = 'report';
$route['default_controller'] = 'auth/login';

$route['logout'] = 'auth/logout';

$route['proposals/(:num)/(:any)'] = '/proposals/$2/$1';
$route['proposals'] = '/proposals/submitted_rfps';

$route['launch_io/trash_campaign'] = 'launch_io/trash_campaign';
$route['launch_io/(:any)'] = 'launch_io/index/$1';

$route['partners/create'] = 'partners/add_edit_partner';
$route['partners/edit/(:any)'] = 'partners/add_edit_partner/$1';

/* API */
$route['api/v1/insertion_orders/(:num)'] = "api_v1/insertion_orders/index/$1"; // for retrieving single IO
$route['api/v1/insertion_orders/results'] = "api_v1/insertion_orders/results";
$route['api/v1/(:any)'] = "api_v1/$1/index/$2";

$route['creative_requests/new'] = '/banner_intake/new_adset_request';
$route['creative_requests'] = '/banner_intake/index';

/* End of file routes.php */
/* Location: ./application/config/routes.php */
