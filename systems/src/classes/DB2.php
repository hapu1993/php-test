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
class DB2 implements IDatabase2, IDatabase_Specific {
    private $db;

    public function __construct($host = "", $user = "", $password = "", $database = "", $multiple=FALSE) {
        global $cfg;

        if (empty($host)) $host = $cfg['dbhost'];
        if (empty($user)) $user = $cfg['dbuser'];
        if (empty($password)) $password = $cfg['dbpass'];
        if (empty($database)) $database = $cfg['dbname'];

        $db = $this->get_db($host, $user, $password, $database, $multiple);

        return $db;
    }

    private function get_db($host = "", $user = "", $password = "", $database = "", $multiple=FALSE) {
        global $cfg;

        $database_type = $cfg['database'];
        $message = "No suitable DB layer found for type $database_type";

        if ($database_type == 'MySQL') {
            if (extension_loaded("pdo_mysql") && class_exists('DB2_PDO_MYSQL')) {
                $this->db = new DB2_PDO_MYSQL("host=$host;dbname=$database", $user, $password, array('database' => $database));
                return $this->db;
            } else {
                echo $message; error_log($message); die;
            }

        }

        if ($database_type == 'MSSQL') {
            if (extension_loaded("sqlsrv") && extension_loaded("pdo_sqlsrv") && class_exists('DB2_PDO_SQLSRV')) {
                $this->db = new DB2_PDO_SQLSRV($host, $user, $password, $database, $multiple);
                return $this->db;
            } elseif (extension_loaded("pdo_dblib") && extension_loaded("pdo_dblib") && class_exists('DB2_PDO_DBLIB')) {
                $this->db = new DB2_PDO_DBLIB($host, $user, $password, $database, $multiple);
                return $this->db;
            } else {
                //                     echo phpinfo();
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
                $this->db = new DB2_PDO_PGSQL("host=$host;{$port}dbname=$database", $user, $password, array('database' => $database));
                return $this->db;
            } else {
                echo $message; error_log($message); die;
            }
        }

        throw new Exception("Unsupported database driver: $database_type");

    }

    // Provides access to public DB functions
    public function concat($vars) { return $this->db->concat($vars); }

    public function escape($str="") { return $this->db->escape($str); }

    public function quote($str="") { return $this->db->quote($str); }

    public function column_escape($str="") { return $this->db->column_escape($str); }

    public function close() { return $this->db->close(); }

    public function get_table_columns($table) { $return_val = $this->db->get_table_column_metadata($table); $this->rows = $this->db->rows; return $return_val; }

    public function get_db_schema($where="") { $return_val = $this->db->get_db_schema($where); $this->rows = $this->db->rows; return $return_val; }

    public function delete($table, array $where, array $types = array()) {
        $return_val = $this->db->delete($table, $where, $types);
        $this->rows = $this->db->rows;
        return $return_val;
    }

    public function get_table_column_metadata($table, $database = "", $meta_fields = array(), array $extra_where = array()) {
        return $this->db->get_table_column_metadata($table, $database, $meta_fields, $extra_where);
    }

    public function get_column_usage($column_name) {
        return $this->db->get_column_usage($column_name);
    }

    public function insert($table, array $insert_fields, array $types = array()) { $return_val = $this->db->insert($table, $insert_fields, $types); $this->rows = $this->db->rows; return $return_val; }

    public function insert_select($table, array $fields, $select, array $options = array()) {
        $return_val = $this->db->insert_select($table, $fields, $select, $options); $this->rows = $this->db->rows; return $return_val;
    }

    public function replace($table, $update_fields, $where="") { $return_val = $this->db->replace($table, $update_fields, $where); $this->rows = $this->db->rows; return $return_val; }

    public function bulk_insert($table, array $fields, array $all_values) { $return_val = $this->db->bulk_insert($table, $fields, $all_values); $this->rows = $this->db->rows; return $return_val; }

    public function select($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array())) {
        $return_val = $this->db->select($fields, $tables, $where, $options);
        $this->rows = $this->db->rows;
        return $return_val;
    }
    //public function select($fields, $tables, $where = "", $orderby = "", $groupby = "", $limit = "") { $return_val = $this->db->select($fields, $tables, $where, $orderby, $groupby, $limit); $this->rows = $this->db->rows; return $return_val; }

    public function select_distinct($fields, $tables, array $where, $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array())) {
        $return_val = $this->db->select_distinct($fields, $tables, $where, $options);
        $this->rows = $this->db->rows;
        return $return_val;
    }
    //public function select_distinct($fields, $tables, $where = "", $orderby = "", $groupby = "", $limit = "") { $return_val = $this->db->select_distinct($fields, $tables, $where, $orderby, $groupby, $limit); $this->rows = $this->db->rows; return $return_val; }

    public function select_value($field, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array(), 'return_all' => false)) {
        $return_val = $this->db->select_value($field, $tables, $where, $options);
        $this->rows = $this->db->rows;
        return $return_val;
    }
    //public function select_value($field, $tables, $where = "", $orderby = "", $groupby = "", $limit = "", $return_all=false) { $return_val = $this->db->select_value($field, $tables, $where, $orderby, $groupby, $limit); $this->rows = $this->db->rows; return $return_val; }

    public function update($table, array $update_fields, array $where, array $types = array()) {
        $return_val = $this->db->update($table, $update_fields, $where, $types);
        $this->rows = $this->db->rows;
        return $return_val;
    }
    //public function update($table, $update_fields, $where) { $return_val = $this->db->update($table, $update_fields, $where); $this->rows = $this->db->rows; return $return_val; }

    public function explain($sql) { return $this->db->explain($sql); }

    public function getQueries() { return $this->db->getQueries(); }

    public function tcount($table, array $where, array $options = array('joins' => '', 'limit' => array())) {
        return $this->db->tcount($table, $where, $options);
    }
    //public function tcount($table, $where="", $limit="") { return $this->db->tcount($table, $where, $limit); }

    public function tcount_distinct($field="id", $table, array $where, array $options = array('joins' => '', 'limit' => array())) {
        return $this->db->tcount_distinct($field, $table, $where, $options);
    }
    //public function tcount_distinct($field="id", $table, $where="", $limit="") { return $this->db->tcount_distinct($field, $table, $where, $limit); }

    public function table_exists($table_check) { return $this->db->table_exists($table_check); }

    public function start_transaction() { return $this->db->start_transaction(); }

    public function complete_transaction() { return $this->db->complete_transaction(); }

    public function get_sqlstate() { return $this->db->get_sqlstate(); }

    // only added for systems -> database.php
    // These functions should never be public especially _fetch!!!!!
    public function _query($sql, &$time=null) { return $this->db->_query($sql, $time); }

    public function _num_rows($result) { return $this->db->_num_rows($result); }

    public function _fetch($result) { return $this->db->_fetch($result); }

    //for validator
    function validate_columns($object, $validator) { return $this->db->validate_columns($object, $validator); }

    //specific functions
    function create_table($table_name, array $columns) { return $this->db->create_table($table_name, $columns); }

    function truncate_table($table_name) { return $this->db->truncate_table($table_name); }

    function enum_select($table, $field) { return $this->db->enum_select($table, $field); }

    function get_real_foreign_key_usage($table) { return $this->db->get_real_foreign_key_usage($table); }

    public function getAttribute($attribute) { return $this->db->getAttribute($attribute); }

    function get_query_stats(){return $this->db->get_query_stats();}

    function get_explain_stats(){return $this->db->get_explain_stats();}

    public function getConnection()
    {
        return $this->db->dbh;
    }
}
