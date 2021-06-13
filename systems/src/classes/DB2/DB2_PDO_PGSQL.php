<?php

class DB2_PDO_PGSQL extends DB2_PDO
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
        $this->driver = 'pgsql';
        if (isset($options['database'])) {
            $this->database = $options['database'];
        }

        if (!in_array($this->driver, PDO::getAvailableDrivers())) {
            error_log("Error: selected PDO driver ($this->driver) is not avaialble.");
            throw new Exception("Selected PDO driver ($this->driver) is not avaialble.");
        } else {
            try {
                $this->dbh = new PDO("$this->driver:$dsn", $username, $password, $options);
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log('Caught exception: in DB __construct():'.  $e->getMessage());
                error_log(print_r($this, true));
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
        return "RTRIM(LTRIM(" . implode(' || ', $vars) . "))";
    }

    /**
     * Escapes column names for use in SQL query string, used in main Object class.
     *
     * Different database drivers use non-standard methods for escaping field/column names.
     * MySQL - uses backticks (`)
     * SQL Server - uses square brackets ([, ])
     * PostgreSQL - uses double quotes (")
     *
     * @param  string
     * @return string
     * @since  Method added since 2012-02-23.
     */
    public function column_escape($str="")
    {
        return '"' . $str . '"';
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * Different database drivers may not implement this in a consistent manner (if at all).
     *
     * SQL Server - use SCOPE_IDENTITY() instead.
     * PostgreSQL - can make use of returning statement.
     *
     * Note: NULL may be returned if no IDENTITY/AUTO_INCREMENT column exists.
     *
     * @param  string
     * @return integer
     */
    protected function _insert_id($table, array $data, $pk = 'id')
    {
        try {
            if (isset($data[$pk])) {
                return $data[$pk];
            } else {
                return $this->dbh->lastInsertId("{$table}_{$pk}_seq");
            }
        } catch (PDOException $e) {
            error_log('Caught exception: in DB _insert_id():'.  $e->getMessage());
            error_log($e->getTraceAsString());
            throw $e;
            //dump_var($e->xdebug_message);
        }
    }


    public function enum_select($table, $field)
    {
        $start = microtime(true);
        $m1 = memory_get_usage();

        $stmt = $this->_prepared_query("SELECT data_type, udt_name FROM INFORMATION_SCHEMA.COLUMNS", array('where' => array("WHERE table_catalog=? AND table_name=? AND column_name=?", array($this->database, $table, $field), array('varchar', 'varchar', 'varchar'))));
        //dump_var($stmt);
        $result=$stmt->execute();
        $obj = $stmt->fetch(PDO::FETCH_OBJ);
        //dump_var($obj);

        if ($obj === false) {
            throw new Exception('Selected field does not exist in schema');
        }

        $enums = array();

        $stmt = $this->_prepared_query("SELECT e.enumlabel FROM pg_enum e JOIN pg_type t ON t.oid=e.enumtypid", array('where' => array("WHERE t.typname=?", array($obj->udt_name), array('varchar'))));
        $result = $stmt->execute();
        $obj = $stmt->fetchAll(PDO::FETCH_OBJ);

        if ($obj === false) {
            throw new Exception('Selected field is not an ENUM data type');
        }

        foreach ($obj as $row) {
            $enums[] = $row->enumlabel;
        }

        $end = microtime(true);
        $time = $end - $start;
        $m2 = memory_get_usage();

        $this->_add_query_stat($stmt, $time, 1, ($m2 - $m1));
        $stmt = null;

        return $enums;
    }
}
