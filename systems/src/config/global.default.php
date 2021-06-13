<?php

//Turn on all error reporting
error_reporting(E_ALL);

//Do not display on screen
ini_set('display_errors', 0);

$cfg = array(

//Client
'client' => 'Riskpoint Ltd.', // to be used on all places a client name is required, i.e. default window title

//Environment - comment for prod
'ENV'=>'dev',

//Database
'database' => 'MySQL',
'dbhost' => 'localhost',
'dbuser' => 'riskpoint_db',
'dbpass' => '',
'dbname' => 'riskpoint',

//Paths
'secure_dir_view' => 'https://admin.riskpoint.co.uk/files/',
'secure_dir' => '/var/www/vhosts/riskpoint.co.uk/subdomains/admin/files/',
'cache' => '/var/www/vhosts/riskpoint.co.uk/subdomains/admin/files/cache/',
'root' => 'https://admin.riskpoint.co.uk/',
'source_root' => '/var/www/vhosts/riskpoint.co.uk/subdomains/admin/httpsdocs/',
'website' => 'https://www.riskpoint.co.uk/',
'website_source' => '/var/www/vhosts/riskpoint.co.uk/httpdocs/',
'imagecache' => '/var/www/vhosts/riskpoint.co.uk/subdomains/admin/httpsdocs/images/imagecache/',
'imagecache_view' => 'https://admin.riskpoint.co.uk/images/imagecache/',

//OwnCloud
'owncloud_integration' => true,
'owncloud_url' => 'https://admin.riskpoint.co.uk/owncloud',
'owncloud_filesystem' => '/var/www/vhosts/riskpoint.co.uk/subdomains/owncloud/httpdocs/data/',
'owncloud_db' => 'owncloud',
'owncloud_group' => 'RiskpointSystems', // used for Solr searching files shared with group.

//Solr
'use_solr' =>true,
'solr_host' => 'localhost',
'solr_port' =>8080,
'solr_core' => 'riskpoint/',

//Login
'unique_login' => true,
'oauth_enable' => true,
'two_factor' => true,
'nice_URLs' => true,
'password_link_expiry' => '2 days', // textual representation to be used as offset in strtotime
'password' => '/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', //at least 8 chars, 1 digit, 1 lc, 1 uc
'password_message' => 'The password must be at least 8 characters long and contain at least one digit, one uppercase and one lowercase character.',
//'password' => '/^(?![A-Z]).*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*(\D)$/', // 8+chars, digit/Uc/Lc, no ucfirst, no digit last
//'password_message' => 'The password must be at least 8 characters long and contain at least one digit, one uppercase and one lowercase character but cannot start with uppercase nor end with digit.',
//'password' => '/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!()<>?\.,:;@£#%&_-]).*$/', // 8+chars, digit/Uc/Lc,special
//'password_message' => htmlspecialchars('The password must be at least 8 characters long and contain at least one digit, one uppercase, one lowercase character and one of the set !()<>?.,:;@£#%&_-'),
//'password' => '/^(?![A-Z]).*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!()<>?\.,:;@£#%&_-]).*(\D)$/', // 8+chars, digit/Uc/Lc,special, no ucfirst, no digit last
//'password_message' => htmlspecialchars('The password must be at least 8 characters long and contain at least one digit, one uppercase, one lowercase character and one of the set !()<>?.,:;@£#%&_- but cannot start with uppercase nor end with digit.'),


//Session
'session_path' => '', // '/systems/',
//Cookie name
'session_name' => 'RiskpointAdmin', 
'session_timeout' => 1440,

//Crypting
'md5_salt' => 'Riskmedia Systems Login',
'work_factor' => 14,
'openssl_crypt_algorithm' => 'AES-256-CBC',
'crypt_algorithm' => MCRYPT_BLOWFISH,
'crypt_mode' => MCRYPT_MODE_CBC,
'crypt_random_source' => MCRYPT_DEV_URANDOM,

//Email settings
//'From' email - displayed as return
'system_email' => 'systems@riskpoint.co.uk',
'email_footer_url'=>"http://www.riskpoint.co.uk", // shown in email templates footer 
'email_footer_email'=>"info@riskpoint.co.uk", // shown in email templates footer 

//System Administrator - default for 'to' in general_email
'email_to' => 'systems@riskpoint.co.uk',
'smtp' => '',
'smtp_user' => '',
'smtp_pass' => '',
'smtp_pass_is_encrypted' => false,
'smtp_from' => 'system', // ownCloud requires it in this format
'smtp_domain' => 'riskpoint.co.uk', // ownCloud requires it in this format

//Purifier
'IDS_filter_threshold' => 7,

'upload_whitelist' => array(
	array('ext' => 'ps','mime' => 'application/postscript'),
	array('ext' => 'eps',   'mime' => 'application/postscript'),
	array('ext' => 'epsi',  'mime' => 'application/postscript'),
	array('ext' => 'pdf',   'mime' => 'application/pdf'),
	array('ext' => 'doc',   'mime' => 'application/msword'),
	array('ext' => 'xls',   'mime' => 'application/vnd.ms-excel'),
	array('ext' => 'ppt',   'mime' => 'application/vnd.ms-powerpoint'),
	array('ext' => 'pps',   'mime' => 'application/vnd.ms-powerpoint'),
	array('ext' => 'doc',   'mime' => 'application/vnd.ms-office'),
	array('ext' => 'ppt',   'mime' => 'application/vnd.ms-office'),
	array('ext' => 'pps',   'mime' => 'application/vnd.ms-office'),
	array('ext' => 'xls',   'mime' => 'application/vnd.ms-office'),
	array('ext' => 'xlsx',  'mime' => 'application/zip'),
	array('ext' => 'xlsx',  'mime' => 'application/vnd.ms-excel'),
	array('ext' => 'docx',  'mime' => 'application/msword'),
	array('ext' => 'docx',  'mime' => 'application/zip'),
	array('ext' => 'pptx',  'mime' => 'application/zip'),
	array('ext' => 'zip',   'mime' => 'application/zip'),
	array('ext' => 'gz','mime' => 'application/x-gzip'),
	array('ext' => 'apk',   'mime' => 'application/zip'),
	array('ext' => 'bmp',   'mime' => 'image/bmp'),
	array('ext' => 'gif',   'mime' => 'image/gif'),
	array('ext' => 'jpg',   'mime' => 'image/jpeg'),
	array('ext' => 'jpeg',  'mime' => 'image/jpeg'),
	array('ext' => 'png',   'mime' => 'image/png'),
	array('ext' => 'tiff',  'mime' => 'image/tiff'),
	array('ext' => 'tif',   'mime' => 'image/tiff'),
	array('ext' => 'csv',   'mime' => 'text/csv'),
	array('ext' => 'csv',   'mime' => 'text/plain'),
	array('ext' => 'csv',   'mime' => 'application/vnd.ms-excel'),
	array('ext' => 'txt',   'mime' => 'text/plain'),
	array('ext' => 'txt',   'mime' => 'text/x-c'), //depending on comments txt files can be detected as this e.g. sphinx_installation.txt
	array('ext' => 'rtf',   'mime' => 'text/rtf'),
	array('ext' => 'flv',   'mime' => 'video/x-flv'),
	array('ext' => 'swf',   'mime' => 'application/x-shockwave-flash'),
	array('ext' => 'html',  'mime' => 'text/html'),
),

// include and override any to make captcha more difficult
'captcha' => array(
    'type'                         => 'string', // string, maths, or words
    // 'image_width'                  => 215,
    // 'image_height'                 => 80,d
    // 'font_ratio'                   => 0.4,
    // 'image_bg_color'               => '#ffffff',
    // 'text_color'                   => '#707070',
    // 'line_color'                   => '#707070',
    // 'noise_color'                  => '#707070',
    // 'text_transparency_percentage' => 20,
    // 'use_transparent_text'         => true,
    // 'code_length'                  => 6,
    'use_random_spaces'            => true, //false,
    'use_text_angles'              => true, //false,
    'use_random_baseline'          => true, //false,
    // 'use_random_boxes'             => false,
    // 'case_sensitive'               => false,
    // 'charset'                      => 'abcdefghijkmnopqrstuvwxzyABCDEFGHJKLMNPQRSTUVWXZY0123456789',
    // 'expiry_time'                  => 900,
    'perturbation'                 => 0.7, //0.85, // distortion
    'num_lines'                    => 3, //5,
    'noise_level'                  => 4, //2,
    // 'image_signature'              => '',
    // 'signature_color'              => '#707070',
),

//GA Access
'gmail_username' => 'riskpoint.limited',
'gmail_password' => 'riskpoint999',
'riskpoint.co.uk' => '51008554',
'riskpoint.mobi' => '61110396',

//Google APO
'google_api_key' => 'AIzaSyC_NvrP3A9bvBNVkFj3TRMBIjmLURkOnpc',

//Twitter name
'twitter'=>'riskpoint',

);

define('CONSUMER_KEY', 'HGcOXKiuoJCsYe0DOtVDOQ');
define('CONSUMER_SECRET', 'FONIZQGxTJ0sTZns1BkbsSrSuFhbutUhvya8UvfQ');
define('OAUTH_TOKEN', '260180131-J7av9PBsCnP5EpeZxTJaxNFk5ZVyqwRY1SqeQV28');
define('OAUTH_TOKEN_SECRET', 'SMP6RcN4nsQJc1EZ7M2yg3sz2kVb46ntYorYhBSf4');
