<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

require_once('../../public/config.php');

$active_group = 'main';//'dev';
$active_record = TRUE;

$db['main']['hostname'] = DB_HOSTNAME;
$db['main']['username'] = DB_USERNAME;
$db['main']['password'] = DB_PASSWORD;
$db['main']['database'] = DB_DATABASE;
$db['main']['dbdriver'] = DB_DRIVER;
$db['main']['dbprefix'] = DB_PREFIX;
$db['main']['pconnect'] = DB_PCONNECT;
$db['main']['db_debug'] = DB_DEBUG;
$db['main']['cache_on'] = DB_CACHE_ON;
$db['main']['cachedir'] = DB_CACHEDIR;
$db['main']['char_set'] = DB_CHAR_SET;
$db['main']['dbcollat'] = DB_COLLAT;
$db['main']['swap_pre'] = DB_SWAP_PRE;
$db['main']['autoinit'] = DB_AUTOINIT;
$db['main']['stricton'] = DB_STRICTON;

$db['td_intermediate']['hostname'] = TD_DB_HOSTNAME;
$db['td_intermediate']['username'] = TD_DB_USERNAME;
$db['td_intermediate']['password'] = TD_DB_PASSWORD;
$db['td_intermediate']['database'] = TD_DB_DATABASE;
$db['td_intermediate']['dbdriver'] = TD_DB_DRIVER;
$db['td_intermediate']['dbprefix'] = TD_DB_PREFIX;
$db['td_intermediate']['pconnect'] = TD_DB_PCONNECT;
$db['td_intermediate']['db_debug'] = TD_DB_DEBUG;
$db['td_intermediate']['cache_on'] = TD_DB_CACHE_ON;
$db['td_intermediate']['cachedir'] = TD_DB_CACHEDIR;
$db['td_intermediate']['char_set'] = TD_DB_CHAR_SET;
$db['td_intermediate']['dbcollat'] = TD_DB_COLLAT;
$db['td_intermediate']['swap_pre'] = TD_DB_SWAP_PRE;
$db['td_intermediate']['autoinit'] = TD_DB_AUTOINIT;
$db['td_intermediate']['stricton'] = TD_DB_STRICTON;


/* End of file database.php */
/* Location: ./application/config/database.php */
