<?php

class DB2_PDO_DBLIB extends DB2_PDO_SQLServer
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
                $this->dbh = new PDO("$this->driver:$dsn", $username, $password, $options);
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $this->dbh->exec('SET ANSI_WARNINGS ON');
                $this->dbh->exec('SET ANSI_NULLS ON');
                $this->dbh->exec('SET ANSI_PADDING ON');
                $this->dbh->exec('SET QUOTED_IDENTIFIER ON');
                $this->dbh->exec('SET ANSI_NULL_DFLT_ON ON');

            } catch (PDOException $e) {
                error_log('Caught exception: in DB __construct():'.  $e->getMessage());
                //error_log(print_r($this, true));
                throw $e;
            }
        }

    }

    protected function _prepared_query($sql, array $options = array(), &$time = null)
    {
        (isset($options['where']) && !empty($options['where'])) ? $where = $options['where'] : $where = array("", array(), array());
        (isset($options['params'])) ? $params = $options['params'] : $params = array();
        (isset($options['types'])) ? $types = $options['types'] : $types = array();
        $where_params = $where[1];

        try {
            $stmt = $this->_prepare_sql($sql, $options);
            print_r($stmt);
            //var_dump($stmt);
            //error_log(print_r($stmt, true));

            if (!empty($where_params)) {

                $key = 0;
                if (!empty($params)) {
//error_log(print_r(array_values($params), true));
                    foreach ($params as $key1=>&$value1) {
                        $value = $value1;
                        $key = $key1+1;

                        if (isset($types[$key1])) {
                            $pdo_data_type = $this->type_check($value1, $types[$key1], $key1);
                            //error_log("binding parameter: " . ($key1+1) . " -> " . $value1 . ", data_type: " . $pdo_data_type);
                            $stmt->bindParam($key, $value1, $pdo_data_type);
                        } else {

                            $stmt->bindParam($key, $value1);
                        }
                    }

                    $key = $key++;
                }

                $counter = 0;
                foreach ($where_params as $key2=>&$value) {
                    $counter++;

                    if (!empty($types)) {
                        $pdo_data_type = $this->type_check($value, $types[$key2], $key2);

                        if ($pdo_data_type == PDO::PARAM_INT) {
                            if (is_int($value) || ctype_digit($value)) {
                                $value2 = (int) $value;

                                $tmp_params[$key+$counter] = array('value' => $value2, 'type' => PDO::PARAM_INT);
                                //$stmt->bindParam(($key+$counter), $value2, PDO::PARAM_INT);
                            } else {
                                throw new Exception(__LINE__ . ' Invalid argument type...');
                            }
                        } else {
                            $value2 = $value;
                            $tmp_params[$key+$counter] = array('value' => $value2, 'type' => $pdo_data_type);
                            //$stmt->bindParam(($key+$counter), $value, $pdo_data_type);
                        }
                    } else {
                        //error_log("Binding " . ($key+$counter) . ": -> $value");
                        $value2 = $value;
                        $tmp_params[$key+$counter] = array('value' => $value2);
                        //$stmt->bindParam(($key+$counter), $value);
                    }

                    //error_log(print_r($tmp_params, true));
                    foreach ($tmp_params as $pos => &$tmp) {
                        if (isset($tmp['type'])) {
                            $stmt->bindParam($pos, $tmp['value'], $tmp['type']);
                        } else {
                            $stmt->bindParam($pos, $tmp['value']);
                        }
                    }
                }

            } else {
// error_log($sql); error_log(print_r($params, true));
                $key = 0;
                if (!empty($params)) {
                    //error_log(print_r($params, true));
                    //dump_var($params);
                    //error_log(print_r($params, true));
                    $param_keys = array_keys($params);
                    //$invalid_chars = strpos($param_keys[0], ' ');
                    if (is_int($param_keys[0])) {
                        foreach ($params as $key => &$value) {
                            //error_log("Binding: $value");
                            if (isset($types[$key])) {
                                $pdo_data_type = $this->type_check($value, $types[$key], $key);
                                //error_log("binding parameter: " . ($key1+1) . " -> " . $value . ", data_type: " . $pdo_data_type);
                                $stmt->bindParam(($key+1), $value, $pdo_data_type);
                            } else {
                                $stmt->bindParam(($key+1), $value);
                            }
                        }
                    } else {
                        foreach ($params as $key1 => &$value) {
                            $key++;
                            //error_log("Binding: $key1=>$value");
                            //dump_var($params);
                            //$stmt->bindParam(($key), $value);
                            if (isset($types[$key1])) {
                                $pdo_data_type = $this->type_check($value, $types[$key1], $key1);
                                //error_log("binding parameter: " . ($key1) . " -> " . $value . ", data_type: " . $pdo_data_type);
                                $stmt->bindParam(':' . $key1, $value, $pdo_data_type);
                            } else {
                                $stmt->bindParam(':' . $key1, $value);
                            }
                        }
                    }
                }

            }

            return $stmt;
        } catch (PDOException $e) {
            error_log('Caught exception: in DB _prepare():'.  $e->getMessage());
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
            throw $e;
        } catch (Exception $e) {
            error_log('Caught exception: in DB _prepare():'.  $e->getMessage());
            error_log($e->getTraceAsString());

            if (isset($stmt) && !is_null($stmt)) {
                error_log(print_r($stmt, true));
            }
            throw $e;
        }
    }
}
