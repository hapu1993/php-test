# PHP Test App

## Developer Notes

All pages in `systems/src` MUST use `dirname(__FILE__)` with relative path name for `global.php` NOT `$_SERVER['DOCUMENT_ROOT']`

##Setup and configurations
Setup xampp v3.2.3 which has `php 5.6.40`.
Make sure to enable following php extentions in the `php.ini`.
`Core`, `bcmath`, `calendar`, `ctype`, `date`, `ereg`, `filter`, `ftp`, `hash`, `iconv`, `json`, `mcrypt`, `SPL`, `odbc`, `pcre`, `Reflection`, `session`, `standard`, `mysqlnd`, `tokenizer`, `zip`, `zlib`, `libxml`, `dom`, `PDO`, `bz2`, `SimpleXML`, `wddx`, `xml`, `xmlreader`, `xmlwriter`, `apache2handler`, `openssl`, `curl`, `fileinfo`, `gd`, `gettext`, `ldap`, `mbstring`, `exif`, `mysql`, `mysqli`, `pdo_mysql`, `pdo_sqlite`, `Phar`, `mhash`, `xdebug`

Also make sure you have following apache modules enabled.
`core`, `mod_win32`, `mpm_winnt`, `http_core`, `mod_so`, `mod_access_compat`, `mod_actions`, `mod_alias`, `mod_allowmethods`, `mod_asis`, `mod_auth_basic`, `mod_authn_core`, `mod_authn_file`, `mod_authz_core`, `mod_authz_groupfile`, `mod_authz_host`, `mod_authz_user`, `mod_autoindex`, `mod_cgi`, `mod_dav_lock`, `mod_dir`, `mod_env`, `mod_headers`, `mod_include`, `mod_info`, `mod_isapi`, `mod_log_config`, `mod_cache_disk`, `mod_mime`, `mod_negotiation`, `mod_proxy`, `mod_proxy_ajp`, `mod_rewrite`, `mod_setenvif`, `mod_socache_shmcb`, `mod_ssl`, `mod_status`, `mod_version`, `mod_vhost_alias`, `mod_php5`

Download the code base to `C:\PHP_Test`
Create a virtual host in apache `xampp\apache\conf\extra\httpd-vhosts.conf`

eg:
```
<VirtualHost php_test:9090>
    ServerAdmin webmaster@localhost
    DocumentRoot "C:/PHP_test/systems/src"
    ServerName localhost
	ServerAlias php_test
	
	<Directory "C:/PHP_test/systems/src">
		Options Indexes FollowSymLinks Includes ExecCGI
		AllowOverride All
		Require all granted
	</Directory>	
</VirtualHost>
```

make sure to add `127.0.0.1 php_test`  to the host file

Create a mysql database named php_test and restore the db backup found under `sql` DIR.

You need to configure the database parameters in the following php file. 
`C:\PHP_Test\systems\src\config\global.php`
It contains an array of configuration parameters including the database name, user and password. This file is included in all most all the php files. If you happen to setup your app in a differant directory, you need to correct all the path related config array elements in this file.

Download and install Composer. (Make sure to set the php installed path to the xampp php. Add the composer path to the system path so you can access composer utility from command line.)
https://getcomposer.org/download/

Navigate to the `C:\PHP_Test\systems\` DIR from the command line and run **composer install** command to download all the dependant libraries.

Start the xampp apache server and you are all set.

Open a browser and point to http://php_test:9090 and you'll be prompted with an login screen.

##Application Users
Following two system users were created in the application and both of them has the same password.

**User 1**
```
Username : Admin
Password : Password@123
Comments : Admin user is the only Master Admin who has privileges to grant Group permissions.
```
**User 2**
```
Username : test_user
Password : Password@123
Comments : Use has some Admin privileges but can not grant Group Permissions.
```  

## Additional Info

This Application is developed using the open source php framework named `Riskpoint Framework Software`
This framework provides all the neccesary classes and APIs to do rapid application development.
all the framework related files are located in following directories.

```
C:\PHP_Test\systems\src\config
C:\PHP_Test\systems\src\classes
C:\PHP_Test\systems\src\*.php
C:\PHP_Test\systems\src\includes
C:\PHP_Test\systems\src\ajax
C:\PHP_Test\systems\src\js
C:\PHP_Test\systems\src\scripts
C:\PHP_Test\systems\src\css
```
Following directories contains files related to this PHP test Application.
```
C:\PHP_Test\systems\src\app_application
```

All Application data classes must extend Object class.
when ever you introduce a new class you need to run composer install to include that class in the autoload.php.

