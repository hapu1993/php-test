<?php

abstract class DB2_PDO_SQLServer extends DB2_PDO
{
    /**
     * Concatenates fields for use in SQL query string.
     *
     * @param  string
     * @return string
     * @since  Method added since 2012-02-23.
     */
    public function concat($vars)
    {
        return "RTRIM(LTRIM(" . implode(' + ', $vars) . "))";
    }

    public function column_escape($str="")
    {
        return '[' . $str . ']';
    }

    public function substring($str, $a, $b)
    {
        return "SUBSTRING($str, $a, $b)";
    }

    protected function _insert_id($table, $pk = 'id')
    {
        try {

            // Note: cannot use PDO::lastInsertID with triggers.
            $stmt = $this->dbh->query("SELECT SCOPE_IDENTITY() AS last_insert_id");
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            if (is_null($result->last_insert_id)) {
                $stmt = $this->dbh->query("SELECT IDENT_CURRENT(" . $this->quote_and_escape($table) . ") AS last_insert_id");
                $result = $stmt->fetch(PDO::FETCH_OBJ);
                if (is_null($result->last_insert_id)) {
                    //throw new Exception("NULL insert ID returned.");
                }
            }
            $last_insert_id = $result->last_insert_id;
            $stmt = null;

            //error_log("insert ID: " . $last_insert_id);
            return $last_insert_id;

        } catch (PDOException $e) {
            error_log('Caught exception: in DB _insert_id():'.  $e->getMessage());
            error_log(print_r($this->error_message($stmt), true));
            error_log($e->getTraceAsString());
            throw $e;
        }
    }

    protected function _prepare_sql($sql, array $options = array())
    {
        (isset($options['where']) && !empty($options['where'])) ? $where = $options['where'] : $where = array("", array(), array());
        (isset($options['joins'])) ? $joins = $options['joins'] : $joins = "";
        (isset($options['group_by'])) ? $group_by = $options['group_by'] : $group_by = "";
        (isset($options['order_by'])) ? $order_by = $options['order_by'] : $order_by = "";
        (isset($options['limit'])) ? $limit = $options['limit'] : $limit = "";

        $local_limit = "";
        $where_params = $where[1];

        if (count($where) != 3) {
            error_log(print_r($where, true));
            throw new InvalidArgumentException("incorrect number of where parameters");
        }

        if (is_array($limit) && array_key_exists('offset', $limit) && array_key_exists('num_on_page', $limit)) {
            $offset = $limit['offset'];
            $num_on_page = $limit['num_on_page'];
            if ($offset == 0) {
                $local_limit = " TOP $num_on_page";
            } else {
                if (empty($order_by)) {
                    throw new Exception("ORDER BY parameter must be supplied when using WITH OrderedResult");
                }
            }
        }

        $query_type = strtoupper(substr($sql, 0, 6));
        if (!in_array($query_type, array('SELECT', 'INSERT', 'UPDATE', 'DELETE'))) {
            throw new Exception ("Invalid query type");
        }

        $query_type = "SELECT";
        if ($query_type == "SELECT") {
            if (!empty($limit)) {
                if ($limit['offset'] == 0) {
                    $sql = "SELECT TOP $num_on_page " . substr($sql, 6) . " $joins " . $where[0] . " $group_by $order_by";
                    //var_dump($sql);
                } else {
                    $original_sql = $sql;
                    $split_pos = strpos($sql, ' FROM');

                    $sql = "WITH OrderedResult AS (" . substr($original_sql, 0, $split_pos) . ", ROW_NUMBER() OVER ($group_by $order_by) AS RowNumber " . substr($original_sql, $split_pos) . " $joins $where[0])";
                    $sql .= " SELECT * FROM OrderedResult WHERE RowNumber BETWEEN " . ($offset+1) . " AND " . ($offset + $num_on_page) . " ORDER BY RowNumber ASC";
                    //var_dump($sql);
                }
            } else {
                $sql .= " $joins " . $where[0] . " $group_by $order_by";
            }
        } else {
            $sql .= " $joins " . $where[0] . " $group_by $order_by";
        }

        $stmt = $this->dbh->prepare(trim($sql));

        return $stmt;

    }
}
