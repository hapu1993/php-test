<?php

class DB2_PDO_ODBC extends DB2_PDO
{
    /**
     * Constructor for use with PDO driver, available drivers may be mysql, pgsql, sqlsrv, sqlite, sqlite2, firebird, dblib.
     *
     * @param  string PDO DSN connection string
     * @param  array additional PDO options
     * @return object
     * @since  Method added since 2012-02-23.
     */
    public function __construct($dsn, $username, $password, $options = array())
    {
        $this->driver = 'dblib';
        if (isset($options['database'])) {
            $this->database = $options['database'];
        }

        if (!in_array($this->driver, PDO::getAvailableDrivers())) {
            error_log("Error: selected PDO driver ($this->driver) is not avaialble.");
            throw new Exception("Selected PDO driver ($this->driver) is not avaialble.");
        } else {
            try {
                $this->dbh = new PDO("$this->driver:$dsn", $username, $password, $options); //, array(PDO::ATTR_TIMEOUT, 5));
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

                //$this->dbh->exec('SET ANSI_WARNINGS ON');
                //$this->dbh->exec('SET ANSI_NULLS ON');
                //$this->dbh->exec('SET ANSI_PADDING ON');
                //$this->dbh->exec('SET QUOTED_IDENTIFIER ON');
                //$this->dbh->exec('SET ANSI_NULL_DFLT_ON ON');

            } catch (PDOException $e) {
                error_log('Caught exception: in DB __construct():'.  $e->getMessage());
                error_log(print_r($this, true));
                //die();
                throw $e;
            }
        }
    }

    public function column_escape($str = "") {
        return $str;
    }
}
