<?php

class DB2_PDO_SQLITE extends DB2_PDO
{
    public function get_table_column_metadata($table, $database='', $meta_fields = array())
    {

        try {
            $start = microtime(true);
            $stmt = $this->_prepared_query("PRAGMA table_info('" . $table . "')");
            $res = $stmt->execute();

            if ($rows = $stmt->fetchAll(PDO::FETCH_OBJ)) {
                foreach ($rows as &$row) {
                    $row->COLUMN_NAME = $row->name;
                    $row->DATA_TYPE = $row->type;
                    $row->IS_NULLABLE = !$row->notnull;
                    $row->COLUMN_DEFAULT = $row->dflt_value;
                    $row->CHARACTER_MAXIMUM_LENGTH = 200;
                }
            }
            $end = microtime(true);
            $time = $end - $start;

            $this->_add_query_stat($stmt, $time, $stmt->rowCount());

            $stmt = null;

            return $rows;

        } catch (PDOException $e) {
            error_log('Caught exception: in DB get_table_column_metadata():'.  $e->getMessage());
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
        }

    }
}
