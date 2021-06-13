<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Database wrapper class using PHP Data Objects (PDO).
 *
 * @author  Alan Campbell <alan.campbell@riskpoint.co.uk>
 * @version SVN: $Id: DB_PDO.php 2705 2012-12-18 12:02:09Z alan.campbell $
 */

    abstract class DB2_PDO implements IDatabase2, IDatabase_Specific, IValidator_Database {

        protected $driver;
        var $conn_id;
        var $database;
        var $result;
        var $rows;
        var $total_rows = 0;
        var $total_time = 0;
        var $total_memory = 0;

        private $fetch_style = PDO::FETCH_OBJ;
        protected $query_stats = array();
        protected $explain_stats = array();
        protected $stmt;
        protected $transaction_level = 0;
        protected $_in_transaction = false;
        protected $_transaction_error = false;
        protected $_transaction_errors = array();
        protected $meta_data_cache = array();

        protected $data_types = array(
            'tinyint'    => PDO::PARAM_INT,
            'smallint'   => PDO::PARAM_INT,
            'integer'    => PDO::PARAM_INT,
            'int'        => PDO::PARAM_INT,
            'bigint'     => PDO::PARAM_INT,
            'boolean'    => PDO::PARAM_BOOL,
            'bit'        => PDO::PARAM_BOOL,
            'blob'       => PDO::PARAM_LOB,
            'mediumblob' => PDO::PARAM_LOB,
            'longblob'   => PDO::PARAM_LOB,
            'binary'     => PDO::PARAM_LOB,
            'varbinary'  => PDO::PARAM_LOB,
            'bytea'      => PDO::PARAM_LOB,
            'char'       => PDO::PARAM_STR,
            'nchar'      => PDO::PARAM_STR,
            'varchar'    => PDO::PARAM_STR,
            'character varying' => PDO::PARAM_STR,
            'nvarchar'   => PDO::PARAM_STR,
            'text'       => PDO::PARAM_STR,
            'mediumtext' => PDO::PARAM_STR,
            'longtext'   => PDO::PARAM_STR,
            'date'       => PDO::PARAM_STR,
            'datetime'   => PDO::PARAM_STR,
            'time'  => PDO::PARAM_STR,
            'timestamp'  => PDO::PARAM_STR,
            'timestamp with time zone' => PDO::PARAM_STR,
            'timestamp without time zone' => PDO::PARAM_STR,
            'float'      => PDO::PARAM_STR,
            'double'     => PDO::PARAM_STR,
            'double precision' => PDO::PARAM_STR,
            'decimal'    => PDO::PARAM_STR,
            'numeric'    => PDO::PARAM_STR,

            'enum'       => PDO::PARAM_STR,
            'USER-DEFINED'     => PDO::PARAM_STR,
            'uniqueidentifier' => PDO::PARAM_STR
        );

        protected $is_options = array(
            'columns_fields' => "c.COLUMN_NAME, c.ORDINAL_POSITION, c.COLUMN_DEFAULT, c.IS_NULLABLE, c.DATA_TYPE, c.CHARACTER_MAXIMUM_LENGTH, tc.CONSTRAINT_TYPE",
            'columns_joins'  => "LEFT JOIN information_schema.key_column_usage kcu ON (c.table_name= kcu.table_name AND c.column_name = kcu.column_name) LEFT JOIN information_schema.table_constraints tc ON (tc.constraint_name = kcu.constraint_name)",
            'columns_where'  => array("WHERE c.TABLE_CATALOG=? AND c.TABLE_NAME=?", array(), array('varchar', 'varchar')),
            'column_usage_where' => array("WHERE TABLE_CATALOG=? AND COLUMN_NAME=?", array(), array('varchar', 'varchar')),
            'foreign_key_usage_fields' => "tc.constraint_name, tc.table_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name",
            'foreign_key_usage_from'   => "information_schema.table_constraints AS tc",
            'foreign_key_usage_joins'  => "JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name JOIN information_schema.referential_constraints rc ON (rc.constraint_name = tc.constraint_name AND (rc.delete_rule='RESTRICT' OR rc.delete_rule='NO ACTION'))",
            'foreign_key_usage_where'  => array("WHERE constraint_type = 'FOREIGN KEY' AND ccu.constraint_catalog=? AND kcu.table_catalog=? AND ccu.table_name=?", array(), array('varchar','varchar','varchar'))
        );

        public function getConnection()
        {
            return $this->dbh;
        }

        public function forceTransactionError($message)
        {
            if ($this->transaction_level>0) {
                $this->_transaction_error = true;
                $this->_transaction_errors[] = "Non DB related error: $message";
                $this->complete_transaction();
            }
        }

        public function getFetchStyle()
        {
            return $this->fetch_style;
        }

        public function setFetchStyle($fetch_style)
        {
            $this->fetch_style = $fetch_style;
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
        function concat($vars)
        {
            return "RTRIM(LTRIM(" . implode(' || ', $vars) . "))";
        }

        public function substring($str, $a, $b)
        {
            return "SUBSTR($str, $a, $b)";
        }

        /**
         * Quotes and escapes parameter for use in SQL query string.
         *
         * @param  mixed
         * @param  integer PDO constant
         * @return string
         * @since  Method added since 2012-02-23.
         */
        public function quote_and_escape($param, $parameter_type = PDO::PARAM_STR)
        {
            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                $param = stripslashes($param);
            }

            return $this->dbh->quote($param, $parameter_type);
        }

        /**
         * Quotes parameter for use in SQL query string.
         *
         * @param      mixed
         * @param      integer PDO constant
         * @return     string
         * @deprecated Method deprecated since 2012-02-23.
         */
        private function quote($param = "")
        {
            trigger_error('Deprecated method called', E_USER_DEPRECATED);
            $e = new Exception("Deprecated method called: ");
            error_log($e->getMessage() . "\n" . print_r($e->getTraceAsString(), true));

            return $this->escape($param);
        }

        /**
         * Escapes parameter for use in SQL query string.
         *
         * @param      mixed
         * @param      integer PDO constant
         * @return     string
         * @deprecated Method deprecated since 2012-02-23.
         */
        private function escape($param="")
        {
            trigger_error('Deprecated method called', E_USER_DEPRECATED);
            $e = new Exception("Deprecated method called: ");
            error_log($e->getMessage() . "\n" . print_r($e->getTraceAsString(), true));

            //error_log(print_r("unescaped sctring: $param", true));
            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                $param =  $this->dbh->quote(stripslashes($param));
            }
            $result = $this->dbh->quote($param);
            //error_log(print_r("escaped sctring: $result", true));
            return $result;
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
        abstract public function column_escape($str="");

        /**
         * Closes database connection.
         */
        public function close()
        {

            $this->dbh = null;

        }

        /**
         * Frees query result object.
         */
        private function free_result($result)
        {
            $result = null;
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
                    return $this->dbh->lastInsertId();
                }
            } catch (PDOException $e) {
                error_log('Caught exception: in DB _insert_id():'.  $e->getMessage());
                error_log(print_r($this->error_message($stmt), true));
                error_log($e->getTraceAsString());
                throw $e;
                //dump_var($e->xdebug_message);
            }
        }

        /**
         * Returns rows affected by previous insert, update or delete query.
         *
         * @return integer
         */
        protected function _affected_rows(PDOStatement $stmt)
        {
            return $stmt->rowCount();
        }

        /**
         * Frees query result object.
         *
         * @param  PDOStatement (result object).
         * @return integer
         */
        private function _num_rows(PDOStatement $stmt)
        {
            return $stmt->rowCount();
        }

        /**
         * Return PDOStatement for use as prepared statement.
         *
         * @param  string Query string to be used to build prepared statement
         * @param  array Parameters to be bound in prepared statement
         * @return PDOStatement
         */
        private function _fetch_all(PDOStatement $stmt)
        {
            return $stmt->fetchAll($this->fetch_style);
        }

        /**
         * Return PDOStatement for use as prepared statement.
         *
         * @param  string Query string to be used to build prepared statement
         * @param  array Parameters to be bound in prepared statement
         * @return PDOStatement
         */
        protected function _fetch(PDOStatement $stmt)
        {
            //var_dump($stmt);
            try {
                return $stmt->fetch($this->fetch_style);
            } catch (PDOException $e) {
                echo "Trace: " . $e->getTraceAsString();
                print_r($e);
                exit();
            }
        }

        /**
         * Use with CAUTION...
         */
        protected function _raw_query($sql, &$time=null)
        {
            if (is_string($sql)) {
                //$result = $this->dbh->query($sql);

                //error_log("SQL: " . print_r($sql, true));
                $stmt = $this->dbh->prepare($sql);
                //error_log("STMT: " . print_r($stmt, true));
                if ($this->driver == 'sqlsrv') {
                    $stmt->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
                }
                //error_log(print_r($stmt, true));
                $start = microtime(true);
                $result = $stmt->execute();
                //var_dump($stmt);
                //var_dump($result);

                $end = microtime(true);
                $time = $end - $start;

                if ($result === false) {
                    if ($stmt->errorCode() != '00000') {
                        error_log(print_r($this->error_message($stmt), true));
                        throw new Exception("Error code detected in PDOStatement");
                    }
                } elseif ($result === true) {
                    if ($stmt->errorCode() != '00000') {
                        error_log("Non-zero error code detected.");
                        error_log(print_r($this->error_message($stmt), true));
                        error_log(print_r($stmt, true));
                    }
                } else {
                    $this->result = $result;
                }

                return $stmt;
            }

        }

        /**
         * Return PDOStatement for use as prepared statement.
         *
         * @param  string Query string to be used to build prepared statement
         * @param  array Parameters to be bound in prepared statement
         * @return PDOStatement
         */
        protected function _query(PDOStatement $stmt, &$time=null)
        {
            //dump_var($sql); exit;

            try {
                $this->result = null;
                $start = microtime(true);
                $result = $stmt->execute();
                $end = microtime(true);
                $time = $end - $start;

                if ($result === false) {
                    if ($stmt->errorCode() != '00000') {
                        error_log(print_r($this->error_message($stmt), true));
                        throw new Exception("Error code detected in PDOStatement");
                    }
                } elseif ($result === true) {
                    if ($stmt->errorCode() != '00000') {
                        error_log("Non-zero error code detected.");
                        error_log(print_r($this->error_message($stmt), true));
                        error_log(print_r($stmt, true));
                    }
                } else {
                    $this->result = $result;
                }

                return $stmt;

            } catch (PDOException $e) {
                error_log('Caught PDO exception: in DB _query(): ' . $e->getMessage());
                error_log(print_r($this->error_message($stmt), true));
                error_log(print_r($stmt->queryString, true));
                error_log(print_r($e->getTraceAsString(), true));

                if ($this->_in_transaction) {
                    $this->_transaction_error = TRUE;
                    $this->_transaction_errors[] = $this->error_message($stmt);
                    //$this->_add_query_stat($sql, 0, -1);
                    $this->complete_transaction();
                } else {
                    throw new Exception($this->error_message($stmt));
                }
            } catch (Exception $e) {
                error_log('Caught exception: in DB _query(): ' . $e->getMessage());
                error_log(print_r($this->error_message($stmt), true));
                error_log(print_r($stmt->queryString, true));
                error_log(print_r($e->getTraceAsString(), true));

                if ($this->_in_transaction) {
                    $this->_transaction_error = TRUE;
                    $this->_transaction_errors[] = $this->error_message($stmt);
                    $this->_add_query_stat($sql, 0, -1);
                    $this->complete_transaction();
                } else {
                    if (!$result) {
                        throw new Exception($this->error_message($stmt));
                    } else {
                        return $stmt;
                    }
                }
                error_log($this->error_message($stmt));
                if (!$result) {
                    throw new Exception($this->error_message($stmt));
                } else {
                    return $stmt;
                }
            }
        }

        /**
         * Return PDOStatement for use as prepared statement.
         *
         * @param  string Query string to be used to build prepared statement
         * @param  array Parameters to be bound in prepared statement
         * @return PDOStatement
         */
        protected function _prepare_sql($sql, array $options = array())
        {
            (isset($options['where']) && !empty($options['where'])) ? $where = $options['where'] : $where = array("", array(), array());
            (isset($options['joins'])) ? $joins = $options['joins'] : $joins = "";
            (isset($options['group_by'])) ? $group_by = $options['group_by'] : $group_by = "";
            (isset($options['order_by'])) ? $order_by = $options['order_by'] : $order_by = "";
            (isset($options['limit'])) ? $limit = $options['limit'] : $limit = "";

            $local_limit = "";

            if (count($where) != 3) {
                error_log(print_r($where, true));
                throw new InvalidArgumentException("incorrect number of where parameters");
            }

            if (is_array($limit) && array_key_exists('offset', $limit) && array_key_exists('num_on_page', $limit)) {
                $offset = $limit['offset'];
                $num_on_page = $limit['num_on_page'];
                $local_limit = "LIMIT $num_on_page OFFSET $offset";
            }

            $sql = trim($sql) . " " . trim($joins);
            $sql = trim($sql) . " " . trim($where[0]);
            $sql = trim($sql) . " " . trim($group_by);
            $sql = trim($sql) . " " . trim($order_by);
            $sql = trim($sql) . " " . trim($local_limit);
            $sql = trim($sql);

            $stmt = $this->dbh->prepare($sql);

            return $stmt;
        }

        protected function type_check($value, $type, $field)
        {
            if ($type instanceOf stdClass) {
                $type = (array) $type;
            }

            if (is_array($type) && isset($type['DATA_TYPE'])) {
                $schema_data_type = $type['DATA_TYPE'];
            } elseif (is_array($type) && isset($type['data_type'])) {
                $schema_data_type = $type['data_type'];
            } else {
                error_log("TYPE: " . print_r($type, true));
                throw new Exception("Invalid datatype format");

                var_dump($type);
            }

            if (!array_key_exists($schema_data_type, $this->data_types)) {
                throw new Exception(__LINE__ . ' Invalid argument type (type,field => value) = (' . $schema_data_type . ', ' . $field . ' => ' . $value .')');
            }

            if (is_null($value) || (string) $value == 'NULL' || (empty($value) && (string) $value != '0')) {
                if ($type['IS_NULLABLE'] == 'YES') {
                    return PDO::PARAM_NULL;
                }
                if ($this->data_types[$schema_data_type] == PDO::PARAM_INT && $value === "") {
                    //var_dump(func_get_args());
                    throw new Exception("Invalid value supplied for integer datatype");
                }
            }

            return $this->data_types[$schema_data_type];

        }

        protected function where_type_check($value, $type)
        {
            if (!is_string($type)) {
                throw new Exception("Invalid datatype format");
                var_dump($type);
            }

            if ($this->data_types[$type] == PDO::PARAM_INT && $value === '') {
                //var_dump(func_get_args());
                throw new UnexpectedValueException("Invalid value supplied for integer datatype");
            }

            if (!array_key_exists($type, $this->data_types)) {
                throw new Exception(__LINE__ . ' Invalid argument type (type,value) = (' . $type . ', ' . $value .')');
            }

            return $this->data_types[$type];
        }

        protected function _prepared_query($sql, array $options = array(), &$time = null)
        {
            $where = (!empty($options['where'])) ? $options['where'] : array("", array(), array());
            $params = (isset($options['params'])) ? $options['params'] : array();
            $types = (isset($options['types'])) ? $options['types'] : array();

            if (!empty($where) && !is_array($where)) {
                trigger_error('WHERE string cannot be bound, you should use updated syntax', E_USER_WARNING);
                throw new InvalidArgumentException("where parameters must be an array");
            }

            $where_params = $where[1];
            if (!empty($where_params) && count($where) != 3) {
                throw new Exception("WHERE data types not supplied");
            }

            $where_types = $where[2];

            //var_dump(func_get_args());

            try {
                $stmt = $this->_prepare_sql($sql, $options);

                $key = 0;
                $pos = 0;

                //var_dump($stmt);
                //var_dump($params);
                //var_dump($where_params);
                //var_dump($where_types);

                if (!empty($params)) {
                    //print_r($params);
                    foreach ($params as $key => &$value) {
                        $type = $types[$pos];
                        $pos++;
                        $pdo_data_type = $this->type_check($value, $type, $key);
                        //error_log("binding parameter: " . ($pos) . " -> " . $value . ", data_type: " . $pdo_data_type);
                        if ($pdo_data_type == PDO::PARAM_NULL) $value = null;
                        $stmt->bindParam($pos, $value, $pdo_data_type);
                    }

                    $key = $pos;
                }
                //var_dump($pos);
                //var_dump($key);
                //var_dump($where_types);

                if (!empty($where_params)) {
                    //error_log(print_r($where_params, true));
                    //error_log(print_r($where_types, true));
                    foreach ($where_params as $key2 => &$value) {
                        $type = $where_types[$pos - $key];
                        $pos++;
                        $pdo_data_type = $this->where_type_check($value, $type);
                        if ($pdo_data_type == PDO::PARAM_NULL) $value = null;
                        if (($pdo_data_type == PDO::PARAM_INT) && (is_int($value) || ctype_digit($value))) $value = (int) $value;
                        $stmt->bindParam($pos, $value, $pdo_data_type);
                    }
                }

                //echo "<pre>";
                //dump_var($stmt->debugDumpParams());
                //echo "</pre>";

                //echo "<pre>";
                //var_dump($stmt);
                //echo "</pre>";

                //var_dump($stmt->debugDumpParams());

                return $stmt;
            } catch (PDOException $e) {
                error_log('Caught PDOException: in DB _prepare():'.  $e->getMessage());
                //error_log(print_r($this->error_message($stmt), true));

                //$code = $stmt->errorCode();
                //echo "<pre>";
                //print_r($stmt->errorInfo());
                //echo "Error: " . $e->getMessage();
                //echo "Code: " . $e->getCode();
                //echo "File: " . $e->getFile();
                //echo "Line: " . $e->getLine();
                //echo "Trace: " . $e->getTraceAsString();
                //echo "</pre>";
                throw $e;
            } catch (Exception $e) {
                error_log('Caught ' . get_class($e) . ': in DB _prepare():'.  $e->getMessage());
                //error_log($e->getTraceAsString());
                //error_log(print_r($stmt, true));
                throw $e;
            }
        }

        private function execute(PDOStatement $stmt) {
            try {
                $this->result = $stmt->execute();
                return $this->result;

            } catch (Exception $e) {
                if (is_null($e->getPrevious())) {
                    error_log(get_class($e) . " message: " . $e->getMessage());
                    error_log(get_class($e) . " stack trace: " . PHP_EOL . $e->getTraceAsString());
                    error_log(get_class($e) . " query: " . preg_replace('!\s+!', ' ', $stmt->queryString));
                }

                if ($this->_in_transaction) {
                    $this->_add_query_stat($stmt, 0, -1);
                    $this->complete_transaction();
                } else {
                    throw new DB2_Exception($stmt, $this->database);
                }
            }
        }

        /**
         * Calls stored procedure...Return PDOStatement for use as prepared statement.
         *
         * @param  string Query string to be used to build prepared statement
         * @param  array Parameters to be bound in prepared statement
         * @return PDOStatement
         */
        function sp_execute($proc, array $params = array())
        {
            try {
                $stmt = $this->dbh->prepare("EXEC $proc ?, ?");
                if ($this->driver == 'sqlsrv') {
                    $stmt->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
                }
                //$stmt = $this->dbh->prepare("CALL $proc ?, ?");

                if (!empty($params)) {
                    dump_var($params);
                    $param_keys = array_keys($params);
                    //if (is_int($param_keys[0])) {
                        $values = array_values($params);
                        foreach ($values as $key => &$param) {
                            if (is_array($param)) {
                                if (isset($param['maxlen'])) {
                                    $stmt->bindParam(($key+1), $param['var'], $param['type'], $param['is_output'], $param['is_null'], $param['maxlen']);
                                } else {
                                    $stmt->bindParam(($key+1), $param['var'], $param['type']);
                                }
                            } else {
                                $stmt->bindParam(($key+1), $param);
                            }
                        }
                    //} else {
                    //    foreach ($params as $key => &$param) {
                    //        if (is_array($param)) {
                    //            if (isset($param['maxlen'])) {
                    //                $stmt->bindParam($param['name'], $param['var'], $param['type'], $param['is_output'], $param['is_null'], $param['maxlen']);
                    //            } else {
                    //                $stmt->bindParam($param['name'], $param['var'], $param['type']);
                    //            }
                    //        } else {
                    //           $stmt->bindParam(($key+1), $param);
                    //        }
                    //        //$stmt->bindParam(':' . $key, $param);
                    //    }
                    //}
                }
                //dump_var($stmt->debugDumpParams());
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_OBJ);
                //dump_var($stmt);
            } catch (PDOException $e) {
                error_log('Caught exception: in DB sp_execute():'.  $e->getMessage());
                error_log(print_r($this->error_message($stmt), true));

                if ($this->_in_transaction) {
                    $this->_transaction_error = TRUE;
                    $this->_transaction_errors[] = $this->error_message($stmt);
                    $this->complete_transaction();
                } else {
                    throw new Exception($this->error_message($stmt));
                }
                echo "<pre>";
                print_r($e);
                echo "Trace: " . $e->getTraceAsString();
                echo "</pre>";
            }
        }


        public function start_transaction()
        {
            //error_log("Starting transaction");
            //error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
            //error_log(print_r($this, true));
            if (!isset($this->transaction_level) || $this->transaction_level == 0) {
                //error_log("Starting transaction");
                $this->_in_transaction = true;
                $this->_transaction_error = false;
                $this->_transaction_errors = array();
                $this->dbh->beginTransaction();
                //$this->_add_query_stat("BEGIN TRANSACTION", null, null);
                //$res = $this->dbh->query("BEGIN TRANSACTION");
                $this->transaction_level = 1;
            } else {
                //error_log("already in transaction");
                //error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
                $this->transaction_level++;
            }
            //error_log("  transaction_level: " . $this->transaction_level);
            //$this->dbh->beginTransaction();
        }

        public function complete_transaction()
        {
            //error_log("Completing transaction");
            //error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
            //error_log("  transaction_level: " . $this->transaction_level);
            try {
                if ($this->transaction_level>0) {

                    if (isset($this->_transaction_error) && $this->_transaction_error === TRUE) {
                        error_log("Error in transaction: rolling back.");
                        //dump_var($this->_transaction_errors);
                        //error_log("TRANSACTION Errors:");
                        //error_log(print_r($this->_transaction_errors, true));
                        $this->_rollback();
                        $this->_in_transaction = false;
                        //$this->_add_query_stat("ROLLBACK", null, null);
                        //error_log("throwing Exception...");
                        $this->transaction_level = 0;
                        throw new Exception($this->_transaction_errors[0]);
                    } else {
                        if (isset($this->transaction_level) && $this->transaction_level > 1) {
                            $this->transaction_level--;
                            //error_log("already in transaction - not committing");
                        } else {
                            //$res = $this->_commit();
                            if ($this->_commit()) {
                                //error_log("committing transaction");
                                $this->_in_transaction = false;
                                //$this->_add_query_stat("COMMIT", null, null);
                                $this->_in_transaction = false;
                                $this->_transaction_error = false;
                                $this->_transaction_errors = array();
                                $this->transaction_level = 0;
                            } else {
                                error_log("error committing transaction");
                                error_log("TRANSACTION Errors:");
                                error_log(print_r($this->_transaction_errors, true));
                                $this->_in_transaction = false;
                                unset($this->_transaction_error);
                                if (isset($this->_transaction_errors)) {
                                    unset($this->_transaction_errors);
                                }
                                $this->transaction_level = 0;
                            }

                        }
                    }
                } else {
                    error_log("Not in a tranascation...?");
                    error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
                    error_log(print_r(array($this->_in_transaction, $this->transaction_level, $this->_transaction_error, $this->_transaction_errors), true));
                    throw new Exception($this->_transaction_errors[0]);
                }
            } catch (PDOException $e) {
                error_log('PDO Exception caught in ' . __FILE__ . ' [' . __LINE__ . ']: ' . $e->getMessage());
                //error_log($e->getTraceAsString());
                throw new Exception('PDO Exception', 0, $e);
            } catch (Exception $e) {
                error_log('Exception caught in ' . __FILE__ . ' [' . __LINE__ . ']: ' . $e->getMessage());
                error_log($e->getTraceAsString());
                throw $e; //new Exception('DB Exception', 0, $e);
            }
        }

        /**
         * Commits transaction.
         *
         * @return boolean
         */
        private function _commit()
        {
            if (!$this->_in_transaction) {
                error_log('[DB] Attempted commit when not in transaction.');
                return false;
            }
            return $this->dbh->commit();
        }

        /**
         * Rollback transaction
         *
         * @return boolean
         */
        public function _rollback()
        {
            if (!$this->_in_transaction) {
                error_log('[DB] Attempted rollback when not in transaction.');
                return false;
            }
            return $this->dbh->rollBack();
        }

        private function _transaction()
        {
            try {
                $this->dbh->beginTransaction();

                $this->dbh->commit();
            } catch (PDOException $e) {
                $this->dbh->rollback();

                //print_r($e);
                echo "Trace: " . $e->getTraceAsString();
            }
        }
        /**
         * Returns textual representations of standard error messages.
         *
         * @param  PDOStatement
         * @return string
         */
        protected function error_message(PDOStatement $stmt)
        {
            $error_code = $stmt->errorCode();
            $error_info = $stmt->errorInfo();
            //error_log("Error code: " . print_r($error_code, true));
            //error_log("Error info: " . print_r($error_info, true));

            try {
                throw new DB2_Exception($stmt, $this->database);
            } catch (DB2_Exception $e) {
                return $e->getMessage();
            }
        }

        private function _add_query_explain($fields, $tables, $where=array(), $options=array())
        {
            global $cfg;
            if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') {

                $stmt = $this->_prepared_query("SELECT $fields FROM $tables", array_merge(array('where' => $where), $options));
                $stmt_explain= $this->_prepared_query("EXPLAIN SELECT $fields FROM $tables", array_merge(array('where' => $where), $options));

                $query_explain = array(
                    'sql'=>$stmt->queryString,
                    'explain'=>''
                );

                if ($stmt_explain->execute()) $query_explain['explain'] = $stmt_explain->fetchAll($this->fetch_style);

                $this->explain_stats[] = $query_explain;

            }
        }

        protected function _add_query_stat(PDOStatement $sql, $time, $rows, $memory = 0, $where = '')
        {
            global $cfg;

            $this->total_time += $time;
            $this->total_rows += $rows;
            $this->total_memory += $memory;

            if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') {
                $this->query_stats[] = array(
                    'sql' => $sql->queryString,
                    'time' => $time,
                    'rows' => $rows,
                    'memory' => $memory,
                    'where' => $where,
                );
            }
        }

        public function getQueryStats()
        {
            return array('total_queries' => count($this->query_stats), 'total_rows' => $this->total_rows, 'total_time' => $this->total_time, 'total_memory' => $this->total_memory);
        }

        public function getQueries()
        {
            return $this->query_stats;
        }

        /**
         * Returns meta-data for table columns.
         *
         * @param  string table_name
         * @param  array  meta-data fields
         * @return array
         */
        public function get_table_column_metadata($table, $database = "", $meta_fields = array(), array $extra_where = array())
        {

            //Get meta data out of cache if recorded
            if (!empty($this->meta_data_cache[$table])) return $this->meta_data_cache[$table];
            //if (!empty($_SESSION['meta_data'][$table])) return $_SESSION['meta_data'][$table];

            try {
                $start = microtime(true);
                $m1 = memory_get_usage();

                if (empty($database)) {
                    $database = $this->database;
                }

                $fields = $this->is_options['columns_fields'];
                if (!empty($meta_fields) && is_array($meta_fields)) {
                    $fields = implode(', ', $meta_fields);
                }

                $joins = $this->is_options['columns_joins'];
                $where = $this->is_options['columns_where'];
                $where[1] = array($database, $table);

                if (!empty($extra_where) && count($extra_where) == 3) {
                    $where[0] .= $extra_where[0];
                    $where[1] = array_merge($where[1], $extra_where[1]);
                    $where[2] = array_merge($where[2], $extra_where[2]);
                }

                $stmt = $this->_prepared_query("SELECT $fields FROM INFORMATION_SCHEMA.columns c $joins", array('where' => $where));
                $res = $stmt->execute();

                $rows = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row = array_change_key_case($row, CASE_UPPER);
                    $row = (object) $row;
                    $rows[$row->COLUMN_NAME] = $row;
                }

                $end = microtime(true);
                $time = $end - $start;
                $m2 = memory_get_usage();

                $this->_add_query_stat($stmt, $time, count($rows), ($m2-$m1), $where);

                $stmt = null;

                //Cache meta-data
                $this->meta_data_cache[$table]= $rows;
                //$_SESSION['meta_data'][$table] = $rows;

                return $rows;

            } catch (PDOException $e) {
                error_log('Caught exception: in DB get_table_column_metadata():'.  $e->getMessage());
                throw $e;

                /*
                error_log(print_r($this->error_message($stmt), true));

                $code = $stmt->errorCode();
                echo "<pre>";
                print_r($stmt->errorInfo());
                echo "Error: " . $e->getMessage();
                echo "Code: " . $e->getCode();
                echo "File: " . $e->getFile();
                echo "Line: " . $e->getLine();
                echo "Trace: " . $e->getTraceAsString();
                echo "</pre>";
                */
            }

        }

        public function get_real_foreign_key_usage($table)
        {
            try {

                //SELECT
                //    tc.constraint_name, tc.table_name, kcu.column_name,
                //    ccu.table_name AS foreign_table_name,
                //    ccu.column_name AS foreign_column_name
                //FROM
                //    information_schema.table_constraints AS tc
                //    JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
                //    JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
                //WHERE constraint_type = 'FOREIGN KEY'

                $fields = "tc.constraint_name, tc.table_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name";
                //$joins = "JOIN information_schema.key_column_usage kcu ON (rc.constraint_name = kcu.constraint_name AND (rc.delete_rule='RESTRICT' OR rc.DELETE_RULE='NO ACTION'))";
                $joins = "JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name ";
                $joins .= "JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name ";
                $joins .= "JOIN information_schema.referential_constraints rc ON (rc.constraint_name = tc.constraint_name AND (rc.delete_rule='RESTRICT' OR rc.delete_rule='NO ACTION'))";
                $where = array("WHERE constraint_type = 'FOREIGN KEY' AND ccu.constraint_catalog=? AND kcu.table_catalog=? AND ccu.table_name=?", array($this->database, $this->database, $table), array('varchar','varchar','varchar'));


                $fields = $this->is_options['foreign_key_usage_fields'];
                $from   = $this->is_options['foreign_key_usage_from'];
                $joins  = $this->is_options['foreign_key_usage_joins'];
                $where  = $this->is_options['foreign_key_usage_where'];
                $where[1] = array($this->database, $this->database, $table);

                $start = microtime(true);

                $stmt = $this->_prepared_query("SELECT $fields FROM $from", array('where' => $where, 'joins' => $joins));
                $res = $stmt->execute();

                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                $end = microtime(true);
                $time = $end - $start;

                $this->_add_query_stat($stmt, $time, count($rows), null, $where);

                $stmt = null;

                return $rows;

            } catch (Exception $e) {
                error_log('Caught exception: in DB_PDO2 get_foreign_key_usage():'.  $e->getMessage());
                throw $e;
            }
        }

        public function get_column_usage($column_name)
        {
            try {
                $start = microtime(true);

                $where = $this->is_options['column_usage_where'];
                $where[1] = array($this->database, $column_name);
                //array("WHERE TABLE_CATALOG=? AND COLUMN_NAME=?", array('catalog' => $this->database, 'column_name' => $column_name), array('varchar', 'varchar'));
                $stmt = $this->_prepared_query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.columns", array('where' => $where));
                $res = $stmt->execute();

                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                $end = microtime(true);
                $time = $end - $start;

                $this->_add_query_stat($stmt, $time, count($rows), null, $where);

                $stmt = null;

                return $rows;

            } catch (Exception $e) {
                error_log(print_r($stmt, true));
                error_log('Caught exception: in DB_PDO2 get_column_usage():'.  $e->getMessage());
                throw $e;
            }
        }

        public function delete($table, array $where, array $options = array())
        {
            try {
                $start = microtime(true);
                $sql = "DELETE FROM $table";
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

        public function insert($table, array $insert_fields, array $types = array())
        {
            $fields = array();
            $values = array();
            $params = array();
            $new_types = array();

            if (empty($types)) {
                $types = $this->get_table_column_metadata($table);
            }

            foreach ($insert_fields as $key => $value) {
                $fields[] = $this->column_escape($key);
                if (isset($types[$key]) && ((is_array($types[$key]) && $types[$key]['DATA_TYPE'] == 'uniqueidentifier') || ($types instanceOf stdClass))) {
                    $values[] = $value;
                } else {
                    //$values[] = ":$key";
                    $values[] = "?";
                    $params[$key] = $value;
                    $new_types[] = $types[$key];
                }
            }
            $fields = implode(", ",$fields);
            $values = implode(", ",$values);

            //var_dump($new_types);

            try {
                $start = microtime(true);

                $stmt = $this->_prepared_query("INSERT INTO $table ($fields) VALUES ($values)", array('params' => $params, 'types' => $new_types));
                //var_dump($stmt);
                //$stmt->debugDumpParams();

                $result = $stmt->execute();
                $end = microtime(true);
                $time = $end - $start;

                $this->rows = $stmt->rowCount();
                $this->_add_query_stat($stmt, $time, $stmt->rowCount());
                $stmt = null;

                return $this->_insert_id($table, $insert_fields);

            } catch (PDOException $e) {
                error_log('Caught exception: in DB insert:'.  $e->getMessage());
                //error_log(print_r($stmt->errorInfo(), true));
                error_log($stmt->queryString);
                error_log($e->getTraceAsString());

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
                    $this->_add_query_stat($stmt, 0, -1);
                    $this->complete_transaction();
                } else {
                    throw new DB2_Exception($stmt, $this->database);
                }
            } catch (Exception $e) {

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

                throw $e;
            }
        }

        public function insert_select($table, array $fields, $select, array $options = array())
        {
            $fields = implode(", ",$fields);

            try {
                $start = microtime(true);
                $stmt = $this->_prepared_query("INSERT INTO $table ($fields) ($select)", $options);
                //error_log(print_r($stmt, true));
                //error_log(print_r($stmt->debugDumpParams(), true));
                //error_log(print_r($options, true));
                $result = $stmt->execute();
                $end = microtime(true);
                $time = $end - $start;

                $this->rows = $stmt->rowCount();
                $this->_add_query_stat($stmt, $time, $this->rows);
                $stmt = null;

                return true;

            } catch (PDOException $e) {
                error_log('Caught exception: in DB insert_select:'.  $e->getMessage());
                error_log(print_r($this->error_message($stmt), true));
                error_log($stmt->queryString);
                error_log($e->getTraceAsString());
                if ($this->_in_transaction) {
                    $this->_transaction_error = TRUE;
                    $this->_transaction_errors[] = $this->error_message($stmt);
                    $this->_add_query_stat($stmt, 0, -1);
                    $this->complete_transaction();
                } else {
                    throw new DB2_Exception($stmt, $this->database);
                }
            }
        }

        // REPLACE (nneds to use prepared statements)
        function replace($table, $update_fields, $where)
        {
           $fields = array();
           $values = array();
           foreach ($update_fields as $key => $value) $values[] = $this->dbh->quote($value);
           $sql = "REPLACE INTO $table VALUES (" . implode(",",$values) . ")";
           $time = 0;
           $this->result = $this->_query($sql, $time);
           $rows = $this->_affected_rows();
           $this->rows = $rows;
           $this->_add_query_stat($sql, $time, $rows, null, $where);

           return $rows;
        }

        private function _multi_insert()
        {
        }

        // BLOCK_INSERT, needs to use prepared_statement
        function bulk_insert($table, array $fields, array $all_values)
        {
            $limit = 100;

            if (empty($types)) {
                $types = $this->get_table_column_metadata($table);
            }
            foreach ($fields as $field) {
                $new_types[] = $types[$field];
            }
            $types = array();

            foreach ($fields as $field) {
                $escaped_fields[] = $this->column_escape($field);
            }
            $escaped_fields = implode(",", $escaped_fields);

            try {
                $total_rows = 0;

                if (count($all_values) > $limit) {

                    $start = microtime(true);
                    $placeholder = implode(", ", array_fill(0, $limit, "(" . implode(',', array_fill(0, count($all_values[0]), '?')) . ")"));

                    $chunk = array_slice($all_values, 0, $limit);
                    $params = array();

                    foreach ($chunk as &$values) {
                        foreach ($values as $position=>&$value) {
                            $params[] = $value;
                            $types[] = $new_types[$position];
                        }
                    }
                    $stmt = $this->_prepared_query("INSERT INTO $table ($escaped_fields) VALUES $placeholder", array('params' => $params, 'types' => $types));

                    do {
                        $start = microtime(true);
                        $chunk = array_slice($all_values, 0, $limit);
                        $all_values = array_slice($all_values, $limit);

                        $pos = 1;
                        foreach ($chunk as &$values) {
                            foreach ($values as &$value) {
                                $stmt->bindParam($pos, $value);
                                $pos++;
                            }
                        }

                        $stmt->execute();
                        $rows = $stmt->rowCount();
                        $time = microtime(true) - $start;
                        $this->_add_query_stat($stmt, $time, $rows);

                        $total_rows += $rows;

                    } while (count($all_values) > $limit);

                    $stmt = null;
                }

                if (!empty($all_values)) {
                    $start = microtime(true);
                    $placeholder = implode(", ", array_fill(0, count($all_values), "(" . implode(',', array_fill(0, count($all_values[0]), '?')) . ")"));
                    $params = array();

                    foreach ($all_values as &$values) {
                        foreach ($values as $position=>&$value) {
                            $params[] = $value;
                            $types[] = $new_types[$position];
                        }
                    }

                    $stmt = $this->_prepared_query("INSERT INTO $table ($escaped_fields) VALUES $placeholder", array('params' => $params, 'types' => $types));
                    $stmt->execute();

                    $rows = $stmt->rowCount();
                    $time = microtime(true) - $start;
                    $this->_add_query_stat($stmt, $time, $rows);

                    $total_rows += $rows;
                    $stmt = null;
                }

                return $total_rows;

            } catch (Exception $e) {

                error_log(print_r($e->getmessage(), true));
                throw $e;
            }
        }

        public function select($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array(), 'cache' => true))
        {

            $key = md5($fields.$tables.json_encode($where).json_encode($options));
            if (!isset($options['cache']) || $options['cache']) {
               if (!empty($this->meta_data_cache[$key])) return $this->meta_data_cache[$key];
            }

            try {
                $start = microtime(true);
                $m1 = memory_get_usage();

                $stmt = $this->select_statement($fields, $tables, $where, $options);
                $selection = $stmt->fetchAll($this->fetch_style);
                $this->rows = count($selection);

                $end = microtime(true);
                $time = $end - $start;
                $m2 = memory_get_usage();

                $this->_add_query_stat($stmt, $time, $this->rows, ($m2-$m1), $where);
                $this->_add_query_explain($fields, $tables, $where, $options);

                $queries = $this->getQueries();

                //var_dump($queries[count($queries) - 1]);

                $stmt = null;

                $this->meta_data_cache[$key]= $selection;

                return $selection;

            } catch (PDOException $e) {
                //error_log('Caught PDOException: in DB2_PDO select()');
                if (is_null($e->getPrevious())) {
                    error_log(get_class($e) . " message: " . $e->getMessage());
                    error_log(get_class($e) . " stack trace: " . PHP_EOL . $e->getTraceAsString());
                    if (isset($stmt) && !is_null($stmt) && ($stmt instanceOf PDOStatement)) error_log(get_class($e) . " query: " . preg_replace('!\s+!', ' ', $stmt->queryString));
                }

                error_log(__METHOD__ . " arguments: " . PHP_EOL . preg_replace("/[\t]+/", " ", print_r(func_get_args(), true)));

                if (isset($stmt) && !is_null($stmt) && ($stmt instanceOf PDOStatement)) {
                    if ($this->_in_transaction) {
                        $this->_add_query_stat($stmt, 0, -1);
                        $this->complete_transaction();
                    } else {
                        //throw new Exception('Rethrow from select...', 0, $e);
                        throw new DB2_Exception($stmt, $this->database);
                    }
                } else {
                    throw $e;
                }
            } catch (Exception $e) {
                //error_log('Caught ' . get_class($e) . ': in DB select():'.  $e->getMessage());
                //error_log(print_r(func_get_args(), true));

                if (isset($stmt) && !is_null($stmt) && ($stmt instanceOf PDOStatement)) {
                    if ($this->_in_transaction) {
                        $this->_add_query_stat($stmt, 0, -1);
                        $this->complete_transaction();
                    } else {
                        //throw new Exception('Rethrow from select...', 0, $e);
                        throw new DB2_Exception($stmt, $this->database);
                    }
                } else {
                    throw $e;
                }
            }
        }

        /**
         * Returns the executed statement object which allow us to do streaming result sets
         *
         * When calling this must use soemthing along the lines of
         *
         * for streaming resultsets:
         *
         * while ($data = $statement->fetch(PDO::FETCH_OBJ)) {
         *     // do something
         * }
         *
         * or, for normal full dataset
         *
         * $data = $stmt->fetchAll($this->fetch_style);
         *
         * YOU MUST free the $stmt by doing $stmt = null; after
         *
         */
        public function select_statement($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array(), 'cache' => true))
        {

            try {
                $start = microtime(true);
                $stmt = $this->_prepared_query("SELECT $fields FROM $tables", array_merge(array('where' => $where), $options));
                //error_log(print_r($stmt,true));
                $result = $stmt->execute();
                //error_log(print_r($result,true));
                //var_dump($stmt->errorCode());
                //var_dump($stmt->errorInfo());
                $end = microtime(true);

                //$select = array();

                $time = $end - $start;
                $m1 = memory_get_usage();

                if ($result) {
                    return $stmt;
                } else {
                    var_dump($stmt);
                    //error_log("Error in query.");
                }

            } catch (PDOException $e) {
                //error_log('Caught PDOException: in DB2_PDO select()');
                if (is_null($e->getPrevious())) {
                    error_log(get_class($e) . " message: " . $e->getMessage());
                    error_log(get_class($e) . " stack trace: " . PHP_EOL . $e->getTraceAsString());
                    if (isset($stmt) && !is_null($stmt) && ($stmt instanceOf PDOStatement)) error_log(get_class($e) . " query: " . preg_replace('!\s+!', ' ', $stmt->queryString));
                }

                error_log(__METHOD__ . " arguments: " . PHP_EOL . preg_replace("/[\t]+/", " ", print_r(func_get_args(), true)));

                if (isset($stmt) && !is_null($stmt) && ($stmt instanceOf PDOStatement)) {
                    if ($this->_in_transaction) {
                        $this->_add_query_stat($stmt, 0, -1);
                        $this->complete_transaction();
                    } else {
                        //throw new Exception('Rethrow from select...', 0, $e);
                        throw new DB2_Exception($stmt, $this->database);
                    }
                } else {
                    throw $e;
                }
            } catch (Exception $e) {
                //error_log('Caught ' . get_class($e) . ': in DB select():'.  $e->getMessage());
                //error_log(print_r(func_get_args(), true));

                if (isset($stmt) && !is_null($stmt) && ($stmt instanceOf PDOStatement)) {
                    if ($this->_in_transaction) {
                        $this->_add_query_stat($stmt, 0, -1);
                        $this->complete_transaction();
                    } else {
                        //throw new Exception('Rethrow from select...', 0, $e);
                        throw new DB2_Exception($stmt, $this->database);
                    }
                } else {
                    throw $e;
                }
            }
        }

        function select_distinct($fields, $tables, array $where, $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array(), 'cache' => true))
        {

            //In case of query duplications, see if the query is already cached
            $key = md5($fields.$tables.json_encode($where).json_encode($options));
            if (!isset($options['cache']) || $options['cache']) {
                if (!empty($this->meta_data_cache[$key])) return $this->meta_data_cache[$key];
            }

            try {
                $start = microtime(true);
                $m1 = memory_get_usage();

                $stmt = $this->_prepared_query("SELECT DISTINCT $fields FROM $tables", array_merge($options, array('where' => $where)));
                $this->result = $this->_query($stmt, $time);
                $select = array();
                while ($row = $this->_fetch($this->result)) $select[] = $row;
                $this->rows = count($select);

                $end = microtime(true);
                $time = $end - $start;
                $m2 = memory_get_usage();

                $this->_add_query_stat($stmt, $time, $this->rows, ($m2-$m1), $where);
                $this->_add_query_explain($fields, $tables, $where, $options);

                $this->result = null;
                $stmt = null;

                $this->meta_data_cache[$key]= $select;

                return $select;

            } catch (Exception $e) {
                error_log('Caught exception: in DB select_distinct():'.  $e->getMessage());
                error_log($e->getTraceAsString());
                throw new DB2_Exception($stmt, $this->database);
            }

        }

        function select_value($field, $tables, array $where, array $options = array('order_by' => "", 'group_by' => "", 'limit' => array(), 'return_all' => false, 'cache' => true))
        {

            //In case of query duplications, see if the query is already cached
            $key = md5($field.$tables.json_encode($where).json_encode($options));
            if (!isset($options['cache']) || $options['cache']) {
                if (!empty($this->meta_data_cache[$key])) return $this->meta_data_cache[$key];
            }

            try {

                $stmt = $this->_prepared_query("SELECT $field FROM $tables", array_merge($options, array('where' => $where)));
                $time = 0;
                $this->result = $this->_query($stmt, $time);

                //dump_var($this->result);
                $rows = $this->_num_rows($this->result);
                $this->rows = $rows;

                $select = array();
                while ($row = $this->_fetch($this->result)) {
                    //dump_var($row);
                    $select[] = $row->$field;
                }

                $rows = count($select);
                $this->rows = $rows;

                $this->_add_query_stat($stmt, $time, $rows, null, $where);
                $this->_add_query_explain($field, $tables, $where, $options);

                $stmt = null;

                if (count($select)==0) {

                    $this->meta_data_cache[$key]= false;
                    return false;

                } elseif (count($select)==1) {

                    $this->meta_data_cache[$key]= $select[0];
                    return $select[0];

                } else {

                    $this->meta_data_cache[$key]= $select;
                    return $select;
                }

            } catch (Exception $e) {
                error_log('Caught exception: in DB select_value():'.  $e->getMessage());
                error_log($e->getTraceAsString());
                throw new DB2_Exception($stmt, $this->database);
            }
        }

        // TODO: Incomplete
        private function select_fixed_array($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array()))
        {
            if (!empty($where) && !is_array($where)) {
                trigger_error('WHERE string cannot be bound, you should use updated syntax', E_USER_WARNING);
                throw new InvalidArgumentException("where parameters must be an array");
            }

            $select = new SplFixedArray($rows);
            $i = 0;
            while ($row = $this->_fetch($this->result)) {
                $select[$i] = $row;
                $i++;
            }

        }

        // TODO: Incomplete
        public function select_union($selects, $orderby, $limit = array())
        {
            if (!empty($where) && !is_array($where)) {
                trigger_error('WHERE string cannot be bound, you should use updated syntax', E_USER_WARNING);
                throw new InvalidArgumentException("where parameters must be an array");
            }

        }

        // TODO: Incomplete
        public function select_latest($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array()))
        {
            // Allow prepared SQL statements...
            if (!empty($where) && !is_array($where)) {
                trigger_error('WHERE string cannot be bound, you should use updated syntax', E_USER_WARNING);
                throw new InvalidArgumentException("where parameters must be an array");
            }

        }

        // TODO: Incomplete
        function select_array($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array()))
        {
            // Allow prepared SQL statements...
            if (!empty($where) && !is_array($where)) {
                trigger_error('WHERE string cannot be bound, you should use updated syntax', E_USER_WARNING);
                throw new InvalidArgumentException("where parameters must be an array");
            } while ($row = $this->_fetch_array($this->result)) $select[] = $row;
        }

        /**
         * Update method.
         *
         * @return double
         */
        public function update($table, array $update_fields, array $where, array $types = array())
        {
            if (empty($types)) {
                $types = $this->get_table_column_metadata($table);
            }

            $fields = array();
            $params = array();
            $new_types = array();
            foreach ($update_fields as $key => $value) {
                $fields[] = $this->column_escape($key) . "=?";
                if (isset($types[$key]) && ((is_array($types[$key]) && $types[$key]['DATA_TYPE'] == 'uniqueidentifier') || (($types[$key] instanceOf stdClass) && $types[$key]->DATA_TYPE == 'uniqueidentifier'))) {
                } else {
                }
                $params[$key] = $value;
                if (!empty($types)) {
                    $new_types[] = $types[$key];
                    //$new_types[$key] = $types[$key];
                }
                //$fields[] = $this->column_escape($key) . "=:$key";
                //$params[$key] = $value;
            }

            foreach ($where[1] as $key => $value) {

//                 error_log(print_r($where,true));
//                 error_log('TYPES->'.print_r($types,true));

                if (!empty($types)) {
                    $new_types[] = $types[$key];
                    //$new_types[$key] = $types[$key];
                }
            }

            //if (!empty($new_types)) error_log(print_r($new_types, true));
            $fields = implode(", ",$fields);
            //error_log("params: " . print_r($params, true));

            try {
                $start = microtime(true);

                $stmt = new PDOStatement;


                //print_r($new_types);
                $stmt = $this->_prepared_query("UPDATE $table SET $fields", array('params' => $params, 'where' => $where, 'types' => $new_types));
                //error_log(print_r($stmt, true));
                //$stmt->debugDumpParams();
                $result = $stmt->execute();
                $end = microtime(true);
                $time = $end - $start;
                $rows = $stmt->rowCount();
                $this->rows = $rows;

                $this->_add_query_stat($stmt, $time, $rows, null, $where);
                $stmt = null;

                return $rows;

            } catch (PDOException $e) {
                error_log('PDO Caught exception: in DB update:'.  $e->getMessage());
                error_log($e->getTraceAsString());

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
            } catch (Exception $e) {
                error_log('Caught exception: in DB update:'.  $e->getMessage());

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
                    $this->_transaction_errors[] = $e->getMessage();
                    $this->complete_transaction();
                } else {
                    throw new DB2_Exception($stmt, $this->database);
                }
            }

        }

        /**
         * Returns nicely formatted query string, removed axtra whitespace and indents on keywords.
         *
         * @param  string sql
         * @return string output sql
         */
        public function pretty_print_sql($sql)
        {
            if ($sql instanceof PDOStatement) {
                $str = preg_replace("'\s+'", ' ', $sql->queryString);
            } else {
                $str = $sql;
            }

            $str = str_replace('FROM ', "\nFROM ", $str);
            $str = str_replace('LEFT JOIN ', "\n\tLEFT JOIN ", $str);
            $str = str_replace('WHERE ', "\nWHERE ", $str);
            $str = str_replace('GROUP BY ', "\nGROUP BY ", $str);
            $str = str_replace('ORDER BY ', "\nORDER BY ", $str);

            return $str;
        }

        public function tcount($table, array $where, array $options = array('joins' => '', 'limit' => array()))
        {
            try {
                $start = microtime(true);
                $m1 = memory_get_usage();

                $stmt = $this->_prepared_query("SELECT count(*) as total FROM $table", array_merge($options, array('where' => $where)));
                $result = $stmt->execute();

                if ($stmt->errorCode() != '00000') {
                    throw new Exception("Error code detected in PDOStatement");
                }

                $rows = $this->_num_rows($stmt);
                $row = $this->_fetch($stmt);
                $count = $row->total;

                $end = microtime(true);
                $time = $end - $start;
                $m2 = memory_get_usage();

                $this->_add_query_stat($stmt, $time, $rows, ($m2-$m1), $where);
                $stmt = null;

                return $count;

            } catch (Exception $e) {
                error_log("Caught exception: in DB count");
                error_log(__FILE__ . " [" . __LINE__ . "]: " .  $e->getMessage());
                error_log($stmt->queryString);
                error_log($this->error_message($stmt));
                throw new DB2_Exception($stmt, $this->database);
            }

        }

        public function tcount_distinct($field="id", $table, array $where, array $options = array('joins' => '', 'limit' => array()))
        {
            try {
                $stmt = $this->_prepared_query("SELECT count(DISTINCT $field) as total FROM $table", array_merge($options, array('where' => $where)));
                $result = $stmt->execute();

                if ($stmt->errorCode() != '00000') {
                    throw new Exception("Error code detected in PDOStatement");
                }

                $time = 0;
                $rows = $this->_num_rows($stmt);
                $row = $this->_fetch($stmt);
                $count = $row->total;
                $this->_add_query_stat($stmt, $time, $rows, null, $where);
                $stmt = null;

                return $count;

            } catch (Exception $e) {
                error_log("Caught exception: in DB count");
                error_log(__FILE__ . " [" . __LINE__ . "]: " .  $e->getMessage());
                error_log($stmt->queryString);
                error_log($this->error_message($stmt));
                throw new DB2_Exception($stmt, $this->database);
            }

        }

        public function get_table_columns($table)
        {
            return $this->get_table_column_metadata($table);
        }

        public function get_db_schema($where = "") {}

        public function table_exists($table_check) {}

        public function get_sqlstate()
        {
            return $this->dbh->errorCode();
        }

        //TODO: validator functions
        public function validate($table, $fields, $values, $operation="") { return true; }

        function validate_columns($object, $validator) { return true; }

        function is_data_valid($columns, $fields, $values, $operation="insert") { return true; }

        function is_individual_data_valid($column, $field_name, $value = null) { return true; }

        //database specific functions will be handled in another class if supported.
        function create_table($table_name, array $columns)
        {
            throw new Exception("Function '".__METHOD__."' is not available for the $this->driver driver.");
        }

        function truncate_table($table_name)
        {
            throw new Exception("Function '".__METHOD__."' is not available for the $this->driver driver.");
        }
        //function enum_select($table, $field) {
        //    throw new Exception("Function '".__METHOD__."' is not available for the $this->driver driver.");
        //}

        function enum_select($table, $field)
        {
            throw new Exception("ENUM support not available with this Databse driver ($this->driver)");
        }

        public function get_query_stats(){

            return array(
                'total_rows'=>$this->total_rows,
                'total_time'=>$this->total_time,
                'total_memory'=>$this->total_memory,
                'stats'=>$this->query_stats,
            );
        }

        public function get_explain_stats(){

            return $this->explain_stats;

        }

        public function getAttribute($attribute)
        {
            return $this->dbh->getAttribute($attribute);
        }

    }
