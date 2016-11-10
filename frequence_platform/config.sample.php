<?php
/*
 * The sample configuration file.
 * Save this file as config.php and
 * fill in the values for your environment.
 *
 * The config.php file is ignored in .gitignore.
 *
 */

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
	if (array_key_exists('HTTP_HOST', $_SERVER))
	{
		define('BASE_URL', $_SERVER['HTTP_HOST']);
	}
	else
	{
		define('BASE_URL', 'secure.brandcdn.com');
	}
	define('ENVIRONMENT', 'localhost-development');
	define('g_second_level_domain', 'localhost');

	define('ENABLE_HOOKS', FALSE);

	error_reporting(E_ALL);

	define('MAINTENANCE_MODE', FALSE);

	define('CACHE_BUSTER_VERSION', '1.0');

/*
 * DATABASE STUFF
 *
 */
	define('DB_HOSTNAME', 'db.host.com');
	define('DB_USERNAME', 'db_user');
	define('DB_PASSWORD', 'Db_Passw0rd');
	define('DB_DATABASE', 'db_name');
	define('DB_DRIVER', 'mysql');
	define('DB_PREFIX', '');
	define('DB_PCONNECT', TRUE);
	define('DB_DEBUG', TRUE);
	define('DB_CACHE_ON', FALSE);
	define('DB_CACHEDIR', '');
	define('DB_CHAR_SET', 'utf8');
	define('DB_COLLAT', 'utf8_general_ci');
	define('DB_SWAP_PRE', '');
	define('DB_AUTOINIT', TRUE);
	define('DB_STRICTON', FALSE);

/*
 * TD_Intermediate stuff
 *
 */
	define('TD_DB_HOSTNAME', 'db.host.com');
	define('TD_DB_USERNAME', 'db_user');
	define('TD_DB_PASSWORD', 'Db_Passw0rd');
	define('TD_DB_DATABASE', 'db_name');
	define('TD_DB_DRIVER', 'mysql');
	define('TD_DB_PREFIX', '');
	define('TD_DB_PCONNECT', TRUE);
	define('TD_DB_DEBUG', TRUE);
	define('TD_DB_CACHE_ON', FALSE);
	define('TD_DB_CACHEDIR', '');
	define('TD_DB_CHAR_SET', 'utf8');
	define('TD_DB_COLLAT', 'utf8_general_ci');
	define('TD_DB_SWAP_PRE', '');
	define('TD_DB_AUTOINIT', TRUE);
	define('TD_DB_STRICTON', FALSE);
	define('TRADEDESK_WEB_APP_URL','https://desk.thetradedesk.com/');

/*
 * TTD STUFF
 *
 */
	define("TTD_LOGIN", "ttd_login");
	define("TTD_PASSWORD", "TtD_Passw0rd");
	define("TTD_PARTNER_ID", "ttd_partner_id");
	define("TTD_BASE_URL", "https://apisb.thetradedesk.com/v2/");
	define("TTD_V3_BASE_URL", "https://apisb.thetradedesk.com/v3/");
	define("TTD_FEED_VER", "4");
	define("TD_UPLOADER_WARNING_THRESHOLD", 12);
	define("TTD_INDUSTRY_CATEGORY_ID", 54);
	define('TTD_CREATE_LOGO_URL', 'http://dummyimage.com/250x200/000/ffffff.png&text=');

/*
 * DFA STUFF
 *
 */
	define("DFA_USERNAME", "dfa_username");
	define("DFA_PASSWORD", "DfA_Passw0rd");
	define("DFA_APPLICATION_NAME", "dfa_app_name");
	define("DFA_SITE", "dfa_site");
	define("DFA_BASE_WSDL_URL", "https://advertisersapitest.doubleclick.com/v1.19/api/");
	define("DFA_CLIENT_USER_EMAIL", "");
	define("DFA_PROFILE_ID", "");
	define("DFA_CLIENT_EMAIL", "");
	define("DFA_API_KEY", "");


/*
 * AWS STUFF
 *
 */
	define("AWS_KEY", "aws_key");
	define("AWS_SECRET", "aws_secret");
	define("S3_BANNER_INTAKE_BUCKET", "test-banner-intake-files");
	define("S3_PROPOSAL_TEMPLATE_BUCKET", "brandcdn-proposal-templates");
	define("S3_ASSETS_PATH", "https://s3.amazonaws.com/brandcdn-assets");
	define("S3_PARTNERS_BUCKET", "brandcdn-assets-dev");
	define("S3_FREQUENCE_ADSERVER_BUCKET", "frequence-adserver-resources");
	define("S3_FREQUENCE_CLOUDFRONT_URL", "https://ads.brandcdn.com");
	define('S3_RICH_MEDIA_SCRIPT_BUCKET', 'brandcdn-ad-resources-dev');

/*
 * Rackspace API
 *
 */
	define('RACKSPACE_USER', 'replace_username');
	define('RACKSPACE_API_KEY', 'replace_api_key');
	define('DISPLAY_AD_RACKSPACE_USER', 'replace_username'); // this is your rackspace user name
	define('DISPLAY_AD_RACKSPACE_API_KEY', 'replace_api_key'); // get it from my account tab
	define('DISPLAY_AD_RACKSPACE_CONTAINER', 'test_html_ad_support');

/*
 * TMPi ftp
 *
 */
	define('TMPI_FTP_HOSTNAME', 'replace_hostname');
	define('TMPI_FTP_USERNAME', 'replace_username');
	define('TMPI_FTP_PASSWORD', 'replace_password');

/*
 * Proposal FTP
 */
	define('PROPOSAL_SNAPSHOTS_FTP_DOMAIN', 'replace_proposal_snapshots_ftp_domain');
	define('PROPOSAL_SNAPSHOTS_FTP_HOSTNAME', 'replace_proposal_snapshots_ftp_hostname');
	define('PROPOSAL_SNAPSHOTS_FTP_USERNAME', 'replace_proposal_snapshots_ftp_username');
	define('PROPOSAL_SNAPSHOTS_FTP_PASSWORD', 'replace_proposal_snapshots_ftp_password');
	define('PROPOSAL_SNAPSHOTS_FTP_DEBUG_MODE', 'replace_proposal_snapshots_ftp_debug_mode');

/*
 * CodeIgniter Session
 *
 */
	// define('CODEIGNITER_SESSION_EXPIRATION', 300); // optional defaults to 32400 seconds // 9 hours
	// define('CODEIGNITER_SESSION_TIME_TO_UPDATE', 30); // optional defaults to 32400 seconds // 9 hours

/*
 * Uncomment this if you're on a linux distro that
 * doesn't use PHP's regular JSON library, like Ubuntu.
 * See: https://github.com/firebase/php-jwt/issues/8
 */
#	define("JSON_C_VERSION", true);

/*
*  CharterAuto Carmercial API stuff
*/
define('CARMERCIAL_USERNAME', 'replace_username');
define('CARMERCIAL_PASS', 'replace_password');

/*
 * video & media processing stuff
 *
 */
define('TMPDIR', '/tmp');
define('VIDEO_RECEIVING_BUCKET', 'spectrumreach-data');
define('VIDEO_RECEIVING_PATH', 'spots');
define('VIDEO_OUTPUT_BUCKET', 'reports-tv-data');
define('VIDEO_OUTPUT_PATH', '');
define('VIDEO_CONVERSION_TOOL', 'avconv'); // set to ffmpeg if avconv is not available
define('MP4_VIDEO_CODEC', 'libx264');
define('WEBM_VIDEO_CODEC', 'libvpx');
define('MP4_AUDIO_CODEC', 'libvo_aacenc');
define('WEBM_AUDIO_CODEC', 'libvorbis');
define('VIDEO_THUMBNAIL_OFFSET', '00:00:05');
define('VIDEO_OUTPUT_SIZE', '512x288');
define('VIDEO_OUTPUT_V_BITRATE', '418k');
define('VIDEO_OUTPUT_A_BITRATE', '128k');
define('VIDEO_CONVERSION_TIMEOUT', 60);
define('CPU_CORES', 1); // set to the number of cores available for parallel processes

/*
 * video ad stuff
 */
define('SPEC_AD_RACKSPACE_CONTAINER', 'richmedia');
define('SPEC_AD_RACKSPACE_PATH', 'test/automated_video_ads'); // do not include a trailing slash

/*
 * Spectrum email addresses to receive emails about their data processor results
 * (optional comma separated list. Defaults to empty string.)
 */
//define('SPECTRUM_DATA_PROCESSOR_EMAILS', '');

/*
 * Mixpanel Token
 */
	define('MIXPANEL_TOKEN', 'replace_token');

/*
 * Map-related variables
 */
	define('MAX_LOCATIONS_PER_RFP', 'replace_max_locations');
	define('GOOGLE_ACCESS_TOKEN_RFP_IO', 'replace_google_access_key');
	define('MAPBOX_ACCESS_TOKEN', 'replace_mapbox_access_token');
	define('PHANTOMJS_BIN_LOCATION', __DIR__.'/vendor/bin/phantomjs');
	define('ALLOWED_MONTHLY_IMPRESSIONS_TO_POPULATION_RATIO', 99999.0);
/*
 * Frequence pixel URL
 */
define('PIXEL_PING_DOMAIN','https://adservices.brandcdnstage.com');

/*
* Demo Data Config
*/
define('DEMO_PARTNER_ID', 415);
define('DEMO_ADVERTISER_BIG', 24300);
define('DEMO_ADVERTISER_MEDIUM', 24301);
define('DEMO_ADVERTISER_SMALL', 24302);
define('DEMO_ADVERTISER_PREROLL', 24449);

define('TANK_AUTH_PASSWORD_RESET', 60*60*24*30);

define('GEOFENCING_YIELD', 0.01);

/*
 * DFP User
 */
define('DFP_CLIENT_ID', '');
define('DFP_CLIENT_SECRET', "");
define('DFP_REFRESH_TOKEN', "");
define('DFP_NETWORK_CODE', '<ADD_CODE>');
//define('NETWORK_CODE', ); Sandbox Network Code
define('DFP_APPLICATION_NAME', '');
