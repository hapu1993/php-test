<?php
error_reporting(E_ALL ^ E_USER_NOTICE);

$cfg = array();
$cfg['base_dir'] = 'D:/xampp/htdocs/php_test/';
$cfg['default_url'] = 'php_test/';
$cfg['website_dir'] = $cfg['base_dir'];
$cfg['systems_dir'] = $cfg['base_dir'] . 'systems/src/';
$cfg['ENV'] = '';//local-dev
//$cfg['remember_me'] = true; //to enable the remember me checkbox @ login

$cfg['website_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/";


$cfg['systems_url'] = $cfg['website_url'] ;
$cfg['static'] = $cfg['website_url'];

$cfg['database'] = "MySQL";
$cfg['dbhost'] = "localhost";
$cfg['dbuser'] = "root";
$cfg['dbpass'] = "";
$cfg['dbname'] = "php_test";

$cfg['md5_salt'] = "PHP Test Systems Login";

$cfg['secure_dir'] = $cfg['base_dir'] . "files/";
$cfg['cache'] = $cfg['base_dir'] . "files/cache/";
$cfg['imagecache'] = $cfg['base_dir'] . "files/";
$cfg['imagecache_view'] = $cfg['base_dir'] . "images/imagecache/";

$cfg['root'] = $cfg['systems_url'];
$cfg['source_root'] = $cfg['systems_dir'];

$cfg['website'] = $cfg['website_url'];
$cfg['website_source'] = $cfg['website_dir'];

$cfg['unigue_login'] = true;
$cfg['oauth_enable'] = false;
$cfg['two_factor'] = false;
$cfg['nice_URLs'] = false;
$cfg['password'] = '/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/';
$cfg['password_message'] = 'The password must be at least 8 characters long and contain at least one digit, one uppercase and one lowercase character.';
$cfg['password_link_expiry'] = '2 minutes';

$cfg['session_name'] = 'SystemBE';
$cfg['session_timeout'] = 7200;

// Crypting
$cfg['md5_salt'] = 'py%eGa9A#AdEWAzUQyQy8Eba%EzU#A%a';
$cfg['work_factor'] = 14;
$cfg['openssl_crypt_algorithm'] = 'AES-256-CBC';
$cfg['crypt_algorithm'] = MCRYPT_BLOWFISH;
$cfg['crypt_mode'] = MCRYPT_MODE_CBC;
$cfg['crypt_random_source'] = MCRYPT_DEV_URANDOM;

$cfg['system_email'] = 'abc@abc.com';
$cfg['email_to'] = 'info@abc.com';
$cfg['smtp'] = "auth.abc.com";
$cfg['smtp_user'] = "systems@abc.com";
$cfg['smtp_pass'] = "abc@dm1n";

$cfg['IDS_filter_threshold'] = 7;

$cfg['client'] = "VitalHub";

$cfg['upload_whitelist'] = array(
		  array('ext' => 'ps', 'mime' => 'application/postscript'),
		  array('ext' => 'eps', 'mime' => 'application/postscript'),
		  array('ext' => 'epsi', 'mime' => 'application/postscript'),
		  array('ext' => 'pdf', 'mime' => 'application/pdf'),
		  array('ext' => 'doc', 'mime' => 'application/msword'),
		  array('ext' => 'xls', 'mime' => 'application/vnd.ms-excel'),
		  array('ext' => 'ppt', 'mime' => 'application/vnd.ms-powerpoint'),
		  array('ext' => 'pps', 'mime' => 'application/vnd.ms-powerpoint'),
		  array('ext' => 'zip', 'mime' => 'application/zip'),
		  array('ext' => 'gz', 'mime' => 'application/x-gzip'),
		  array('ext' => 'bmp', 'mime' => 'image/bmp'),
		  array('ext' => 'gif', 'mime' => 'image/gif'),
		  array('ext' => 'jpg', 'mime' => 'image/jpeg'),
		  array('ext' => 'jpeg', 'mime' => 'image/jpeg'),
		  array('ext' => 'png', 'mime' => 'image/png'),
		  array('ext' => 'tiff', 'mime' => 'image/tiff'),
		  array('ext' => 'tif', 'mime' => 'image/tiff'),
		  array('ext' => 'csv', 'mime' => 'text/csv'),
		  array('ext' => 'csv', 'mime' => 'text/plain'),
		  array('ext' => 'csv', 'mime' => 'application/vnd.ms-excel'),
		  array('ext' => 'txt', 'mime' => 'text/plain'),
		  array('ext' => 'txt', 'mime' => 'text/x-c'), //depending on comments txt files can be detected as this e.g. sphinx_installation.txt
		  array('ext' => 'rtf', 'mime' => 'text/rtf'),
		  array('ext' => 'flv', 'mime' => 'tvideo/x-flv'),
		  array('ext' => 'swf', 'mime' => 'application/x-shockwave-flash')
		);
$cfg['session.gc_maxlifetime'] = ini_get('session.gc_maxlifetime');
// ini_set('session.gc_maxlifetime', $cfg['session_timeout']);

require_once($cfg['source_root'] . "../vendor/autoload.php");
?>
