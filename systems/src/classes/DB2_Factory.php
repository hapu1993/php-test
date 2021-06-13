<?php
/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2017 Riskpoint London Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */


/*
 * TESTED to work with mysql, mysqli, sqlsrv, pdo_mysql
 * needs further testing for SQL specific commands like concat
 */
class DB2_Factory {

    public static function factory($subdomain="", array $options = array('host' => "", 'user' => "", 'password' => "", 'database' => "", 'multiple' => FALSE)) {
        global $cfg;

        if (empty($subdomain)) {
            $database = self::getDatabaseFromDomain();
        } else {
            $database = self::getDatabaseFromSubdomain($subdomain);
        }

        if (empty($options['host'])) $host = $cfg['dbhost'];
        if (empty($options['user'])) $user = $cfg['dbuser'];
        if (empty($options['password'])) $password = $cfg['dbpass'];

        if ($database['database'] == 'php_db57') {
            $host     = $cfg['db']['mysql-5.7']['dbhost'];
            $user     = $cfg['db']['mysql-5.7']['dbuser'];
            $password = $cfg['db']['mysql-5.7']['dbpass'];
        }

        $db = self::get_db($host, $user, $password, $database['database'], $options['multiple']);
        $db->subdomain = $database['subdomain'];

        return $db;
    }

    protected static function get_db($host = "", $user = "", $password = "", $database = "", $multiple=FALSE) {
        global $cfg;

        $database_type = $cfg['database'];
        $message = "No suitable DB layer found for type $database_type";

        if ($database_type == 'MySQL') {
            if (extension_loaded("pdo_mysql") && class_exists('DB2_PDO_MYSQL')) {
                return new DB2_PDO_MYSQL("host=$host;dbname=$database", $user, $password, array('database' => $database));
            } else {
                echo $message; error_log($message); die;
            }

        }

        if ($database_type == 'MSSQL') {
            if (extension_loaded("sqlsrv") && extension_loaded("pdo_sqlsrv") && class_exists('DB2_PDO_SQLSRV')) {
                return new DB2_PDO_SQLSRV($host, $user, $password, $database, $multiple);
            } elseif (extension_loaded("pdo_dblib") && extension_loaded("pdo_dblib") && class_exists('DB2_PDO_DBLIB')) {
                return new DB2_PDO_DBLIB($host, $user, $password, $database, $multiple);
            } else {
                //                  echo phpinfo();
                //error_log("PDO_MSSQL: ".extension_loaded("pdo_mssql"));
                //error_log("DB_PDO: ".file_exists($cfg['source_root'] . "classes/DB_PDO.php"));
                //error_log("DB_PDO2: ".file_exists($cfg['source_root'] . "classes/DB_PDO2.php"));
                //error_log("SQLSRV: ".extension_loaded("sqlsrv"));
                //error_log("DB_sqlsrv: ".file_exists($cfg['source_root'] . "classes/DB_sqlsrv.php"));
                echo $message; error_log($message); die;
            }
        }

        if ($database_type == 'PGSQL') {
            if (extension_loaded("pdo_pgsql") && class_exists('DB2_PDO_PGSQL')) {
                (isset($cfg['dbport'])) ? $port = "port=" . $cfg['dbport'] . ";" : $port = "";
                return new DB2_PDO_PGSQL("host=$host;{$port}dbname=$database", $user, $password, array('database' => $database));
            } else {
                echo $message; error_log($message); die;
            }
        }

        throw new Exception("Unsupported database driver: $database_type");

    }

    static function getDatabaseFromSubdomain($subdomain) {
        global $cfg;
        //strip unwanted characters, only allowing alphanum and _
        if (preg_match("/[^a-zA-Z0-9_-]+/", $subdomain) == 1) {
            throw new Exception("Non-allowed characters detected in subdomain '$subdomain'.");
        }

        if ($subdomain == 'www' || $subdomain == "System") {
            return array('database' => $cfg['dbname'], 'subdomain' => 'www');
        } else {
            return array('database' => str_replace('-', '_', $subdomain), 'subdomain' => $subdomain);
        }
    }

    static function getDatabaseFromDomain () {
        global $cfg;
        if (PHP_SAPI == 'cli' || $cfg['default_url'] == $_SERVER['HTTP_HOST']) {
            return self::getDatabaseFromSubdomain('www');
        } else {
            $fqdn_parts = explode('.', $_SERVER['HTTP_HOST']);
            return self::getDatabaseFromSubdomain($fqdn_parts[0]);
        }
    }
}
