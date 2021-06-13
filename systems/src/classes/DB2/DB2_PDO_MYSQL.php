<?php

class DB2_PDO_MYSQL extends DB2_PDO
{

    protected $is_options = array(
        'columns_fields' => "c.COLUMN_NAME, c.ORDINAL_POSITION, c.COLUMN_DEFAULT, c.IS_NULLABLE, c.DATA_TYPE, c.CHARACTER_MAXIMUM_LENGTH, c.COLUMN_TYPE, c.EXTRA",
        'columns_joins'  => "",
        'columns_where'  => array("WHERE c.TABLE_SCHEMA=? AND c.TABLE_NAME=?", array(), array('varchar', 'varchar')),
        'column_usage_where' => array("WHERE TABLE_SCHEMA=? AND COLUMN_NAME=?", array(), array('varchar', 'varchar')),
        'foreign_key_usage_fields' => "rc.constraint_name, rc.table_name, kcu.column_name, kcu.referenced_table_name AS foreign_table_name, kcu.referenced_column_name AS foreign_column_name",
        'foreign_key_usage_from'   => "information_schema.referential_constraints rc",
        'foreign_key_usage_joins'  => "JOIN information_schema.key_column_usage kcu ON (rc.constraint_name = kcu.constraint_name AND (rc.delete_rule='RESTRICT' OR rc.DELETE_RULE='NO ACTION'))",
        'foreign_key_usage_where'  => array(
            "WHERE rc.constraint_schema=? AND kcu.table_schema=? AND kcu.referenced_table_name=?",
            array(),
            array('varchar','varchar','varchar')
        )
    );


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
        $this->driver = 'mysql';
        if (isset($options['database'])) {
            $this->database = $options['database'];
        }

        if (!in_array($this->driver, PDO::getAvailableDrivers())) {
            echo "Error: selected PDO driver ($this->driver) is not avaialble.";
            die;
        } else {
            try {

                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";

                $this->dbh = new PDO("$this->driver:$dsn;chartset=utf8", $username, $password, $options); //, array(PDO::ATTR_TIMEOUT, 5));
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                //$this->dbh->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, false);
                //$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                // $stmt = $this->dbh->prepare("SET @@SQL_MODE='STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY'");
                // $stmt->execute();
                $stmt = null;

                //$this->dbh->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
                //$this->dbh->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');

                //$this->dbh->exec("set names utf8");

                //error_log(print_r($this->dbh->getAttribute(PDO::ATTR_TIMEOUT), true));
                //error_log(print_r($this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION), true));
                //error_log(print_r($this->dbh->getAttribute(PDO::ATTR_CLIENT_VERSION), true));
                //error_log(print_r($this->dbh->getAttribute(PDO::ATTR_SERVER_INFO), true));
                //error_log(print_r($this->dbh->getAttribute(PDO::ATTR_CONNECTION_STATUS), true));
                //error_log(print_r($this->dbh->getAttribute(PDO::ATTR_DRIVER_NAME), true));
                //error_log(print_r($this->dbh->getAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY), true));
            } catch (PDOException $e) {
                error_log('Caught exception: in DB __construct():'.  $e->getMessage());
                error_log(print_r($this, true));
                //die();
                throw $e;

            }
        }
    }

    /**
     * Concatenates fields for use in SQL query string.
     *
     * Different database drivers use non-standard methods for concatenation.
     * MySQL - uses CONCAT() function
     * SQL Server - uses plus sign (+)
     * PostgreSQL - uses double pipe (||)
     *
     * @param  string
     * @return string
     * @since  Method added since 2012-02-23.
     */
    public function concat($vars)
    {
        return "TRIM(CONCAT(" . implode(',',$vars) . "))";
    }

    public function column_escape($str="")
    {
        return '`' . $str . '`';
    }

    private function table_has_foreign_key_support($table) {
        $is_supported = false;

        try {

            $where = array("WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", array($this->database, $table), array('varchar', 'varchar'));
            $stmt = $this->_prepared_query("SELECT ENGINE FROM information_schema.TABLES", array('where' => $where));
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            if ($result->ENGINE == 'InnoDB') {
                $is_supported = true;
            }
            $stmt = null;

            return $is_supported;

        } catch (Exception $e) {
            error_log('Caught exception: in DB_PDO2_MYSQL table_has_foreign_key_support():'.  $e->getMessage());
            throw $e;
        }
    }

    public function enum_select($table, $field)
    {
        $start = microtime(true);
        $m1 = memory_get_usage();
        $stmt = $this->_prepared_query("DESCRIBE $table $field");
        $result = $stmt->execute();
        $obj = $stmt->fetch(PDO::FETCH_OBJ);

        if ($obj === false) {
            throw new Exception('Selected field does not exist in schema');
        }

        $end = microtime(true);
        $time = $end - $start;
        $m2 = memory_get_usage();

        $this->_add_query_stat($stmt, $time, 1, ($m2 - $m1));
        $enum = $obj->Type;
        $this->result = null;
        $stmt = null;

        if (substr($enum, 0, 4) == "enum") {
            $enum = str_replace(array("enum('","')"), "", $enum);
            $enum = explode("','", $enum);

            return $enum;
        } else {
            throw new Exception('Selected field is not an ENUM data type');
        }
    }

    public function delete_join($table, $joins, array $where, array $options = array())
    {
        try {
            $start = microtime(true);
            $sql = "DELETE t FROM $table t $joins";
            $stmt = $this->_prepared_query($sql, array_merge($options, array('where' => $where)));
            $stmt->execute();
            $end = microtime(true);
            $time = $end - $start;
            $rows = $this->_affected_rows($stmt);
            $this->rows = $rows;

            $this->_add_query_stat($stmt, $time, $rows, null, $where);

            return $rows;

        } catch (PDOException $e) {
            error_log('Caught exception: in DB delete:'.  $e->getMessage());
            error_log(print_r($this->error_message($stmt), true));

            $error_message = $e->getMessage();
            if (strpos($error_message, 'Lock wait timeout exceeded') !== FALSE) {
                // Lock timeout!! show innodb status as part of exception
                $local_result = $this->result;

                $local_stmt = $this->dbh->prepare("SHOW ENGINE INNODB STATUS");
                $result = $local_stmt->execute();
                // error_log(print_r($this, true));
                error_log(print_r($local_stmt->fetchAll(), true));
                $this->result = $local_result;
                $local_stmt = null;
            }

            if ($this->_in_transaction) {
                $this->_transaction_error = TRUE;
                $this->_transaction_errors[] = $this->error_message($stmt);
                $this->complete_transaction();
            } else {
                throw new DB2_Exception($stmt, $this->database);
            }
        }
    }
}
