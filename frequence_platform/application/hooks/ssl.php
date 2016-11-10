<?php

//SSL CORE ADDITION



function check_ssl()
{
    $CI =& get_instance();
    $class = $CI->router->fetch_class();
 
    $ssl = array('adgroups','auth','director','webr', 'graphs', 'dashboard', 'admin','coupon', 'front', 'metric', 'vlcoupon', 'report_v2', 'user_editor', 'report_legacy'); //Controllers that we want to use SSL with
    $partial =  array('creative_uploader','campaign_health', 'frontend', 'smb', 'lap_lite', 'lap_edit', 'rf', 'ring', 'tradedesk', 'campaigns_main', 'tmpi_data_loader', 'td_uploader', 'dbm_uploader', 'dfp_uploader', 'Comscore_integration', "carmercial", 'maps', 'crtv', 'spectrum_data_loader', 'spectrum_account_data_loader', 'ad_machina');//'proposal_builder','login','registration');			//Controllers that can be both SSL and not SSL
	 

    if(in_array($class,$ssl))
    {
        force_ssl();
    }
    else if(in_array($class,$partial))
    {
        return;
    }
    else
    {
        unforce_ssl();
    }
}
 
function force_ssl()
{
    $CI =& get_instance();
    $CI->config->config['base_url'] = str_replace('http://', 'https://', $CI->config->config['base_url']);
    if ($_SERVER['SERVER_PORT'] != 443) redirect($CI->uri->uri_string());
}
 
function unforce_ssl()
{
    $CI =& get_instance();
    $CI->config->config['base_url'] = str_replace('https://', 'http://', $CI->config->config['base_url']);
    if ($_SERVER['SERVER_PORT'] == 443) redirect($CI->uri->uri_string());
}


?>
