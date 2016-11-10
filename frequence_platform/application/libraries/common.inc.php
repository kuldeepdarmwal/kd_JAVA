<?php

/**
 * Yahoo! PHP5 SDK
 *
 *  * Yahoo! Query Language
 *  * Yahoo! Social API
 *
 * Find documentation and support on Yahoo! Developer Network: http://developer.yahoo.com
 *
 * Hosted on GitHub: http://github.com/yahoo/yos-social-php5/tree/master
 *
 * @package    yos-social-php5
 * @subpackage yahoo
 *
 * @author     Dustin Whittle <dustin@yahoo-inc.com>
 * @copyright  Copyrights for code authored by Yahoo! Inc. is licensed under the following terms:
 * @license    BSD Open Source License
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy
 *   of this software and associated documentation files (the "Software"), to deal
 *   in the Software without restriction, including without limitation the rights
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *   copies of the Software, and to permit persons to whom the Software is
 *   furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *   THE SOFTWARE.
 **/

/*
 * Make sure you obtain oauth keys before continuing by visiting: http://developer.yahoo.com/dashboard
 */

# openid/oauth credentials
/* define('OAUTH_CONSUMER_KEY', 'dj0yJmk9ODBuZkVlYzNVYmtmJmQ9WVdrOU5VRlFkRWhDTkRnbWNHbzlNalkyTmpneU1UWXkmcz1jb25zdW1lcnNlY3JldCZ4PThm'); // bigo test key; please replace with your own.
define('OAUTH_CONSUMER_SECRET', 'bad2ede6e7adceae43d27116ea7aa109c2c05fc6');
define('OAUTH_DOMAIN', 'vl.mobicules.com');
define('OAUTH_APP_ID', '5APtHB48'); */

ini_set('session.save_handler', 'files');
session_save_path('./application/libraries/yahoo-sdk-tmp');
session_start();

require_once 'oauth/OAuth.php';
require_once 'yahoo-sdk/YahooOAuthApplication.class.php';
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

