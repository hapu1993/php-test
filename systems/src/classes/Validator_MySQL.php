<?php

class Validator_MySQL extends Abstract_Validator implements Validator_Database {

    function validate_database_columns() {
        $used_table_columns = array();
        if (empty($this->object)) {
            $this->errors[] = "Object empty, consider using validate_array function or pass the object into the constructor.";
            return false;
        } else {
            foreach ($this->object as $key=>$value) {
                if (array_key_exists($key, $this->object['view_array'])) {
                    $this->field_human_name = $this->object['view_array'][$key]['name'];
                } else {
                    $this->field_human_name = $key;
                }
                $my_message = "testing $this->field_human_name";
                if (in_array($key, array_keys($this->object['table_types']))) {
                    $used_table_columns[] = $key;
                    $column = $this->object['table_types'][$key];

                    //text tests
                    $max_length = (!empty($column->CHARACTER_MAXIMUM_LENGTH)) ? $column->CHARACTER_MAXIMUM_LENGTH : -1;

                    if ($column->DATA_TYPE == "enum") {
                        preg_match("/\('(.*)\'\)/", $column->COLUMN_TYPE, $matches);
                        //error_log("Matches:".print_r($matches, true));
                        if (isset($matches[1]) && !empty($matches[1])) {
                            $possible_values = explode("','", $matches[1]);
                            //error_log("Value: $value, posibles: ".print_r($possible_values, true));
                            if (!in_array($value, $possible_values)) {
                                $this->errors[] = "Value '$value' is not allowed for '".$this->field_human_name."'! Possible values are: '".$matches[1]."'.";
                                return false;
                            }
                        }
                    } elseif (in_array($column->DATA_TYPE, $this->text_type_array)) {
                        if ($column->IS_NULLABLE == "NO" && $this->is_empty($key) == true && in_array($key, $this->object['null_check_exceptions'])) {
                            //In null check exclusion list so ignore.
                            $my_message .= ", Column not null, but in nullcheckexception";
                            //If unexpected value not specified, will come from this if, please check DB schema (is it really a required field)
                        } elseif ($column->IS_NULLABLE == "NO" && (strlen($column->COLUMN_DEFAULT) == 0) && $this->is_null($this->object[$key]) == true) {
                            $this->errors[] = $this->error;
                        } elseif (($column->DATA_TYPE == "char" || $column->DATA_TYPE == "varchar") && $max_length != -1 && strlen($value) > $max_length) {
                            $this->errors[] = "Value is too long for '".$this->field_human_name."' ".strlen($value)." it exceeds the max length ($max_length) specified for that column.";
                        } elseif (($column->DATA_TYPE == "nchar" || $column->DATA_TYPE == "nvarchar") && $max_length != -1 && mb_strlen($value, 'unicode') > $max_length) {
                            $this->errors[] = "Value is too long for '".$this->field_human_name."' ".mb_strlen($value)." it exceeds the max length ($max_length) specified for that column.";
                        } elseif ($column->DATA_TYPE == "text" || $column->DATA_TYPE == "blob") {
                            if (!$this->is_supplied_length_less_than($value, array('length'=>$this->text_length))) {
                                $this->errors[] = "Value is too long for '".$this->field_human_name."' ".strlen($value)." it exceeds the max length ($this->text_length) for a {$column->DATA_TYPE} column.";
                            }
                        } elseif ($column->DATA_TYPE == "mediumtext" || $column->DATA_TYPE == "mediumblob") {
                            if (!$this->is_supplied_length_less_than($value, array('length'=>$this->mediumtext_length))) {
                                $this->errors[] = "Value is too long for '".$this->field_human_name."' ".strlen($value)." it exceeds the max length ($this->mediumtext_length) for a {$column->DATA_TYPE} column.";
                            }
                        } elseif ($column->DATA_TYPE == "longtext" || $column->DATA_TYPE == "longblob") {
                            if (!$this->is_supplied_length_less_than($value, array('length'=>$this->longtext_length))) {
                                $this->errors[] = "Value is too long for '".$this->field_human_name."' ".strlen($value)." it exceeds the max length ($this->longtext_length) for a {$column->DATA_TYPE} column.";
                            }
                        }
                    }
                    //int tests
                    elseif (in_array($column->DATA_TYPE, $this->int_type_array)) {
                        if ($column->IS_NULLABLE == "NO" && (strlen($column->COLUMN_DEFAULT) > 0) && $this->is_empty($key) == true) {
                            //no error as is default will be used
                        } elseif ($column->IS_NULLABLE == "NO" && ($column->EXTRA == "auto_increment" || (isset($column->AUTO_INCREMENT) && $column->AUTO_INCREMENT == 1)) && $this->is_empty($key) == true) {
                            //no error as is autoincementable
                        } elseif ($column->IS_NULLABLE == "YES" && $this->is_empty($key) == true) {
                            //Nulls allowed
                        } elseif ($column->IS_NULLABLE == "NO" && $this->is_empty($key) == true && in_array($key, $this->object['null_check_exceptions'])) {
                            //In null check exclusion list so ignore.
                        } elseif ($column->IS_NULLABLE == "NO" && $this->is_empty($key) == true) {
                            $this->errors[] = $this->error;
                        } elseif ($column->DATA_TYPE == "tinyint") {
                            if (strpos('unsigned', $column->COLUMN_TYPE) !== FALSE) {
                                if ($this->is_unsigned_tinyint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            } else {
                                if ($this->is_tinyint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            }
                        } elseif ($column->DATA_TYPE == "smallint") {
                            if (strpos('unsigned', $column->COLUMN_TYPE) !== FALSE) {
                                if ($this->is_unsigned_smallint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            } else {
                                if ($this->is_smallint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            }
                        } elseif ($column->DATA_TYPE == "mediumint") {
                            if (strpos('unsigned', $column->COLUMN_TYPE) !== FALSE) {
                                if ($this->is_unsigned_mediumint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            } else {
                                if ($this->is_mediumint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            }
                        } elseif ($column->DATA_TYPE == "int" || $column->DATA_TYPE == 'integer') {
                            if (strpos('unsigned', $column->COLUMN_TYPE) !== FALSE) {
                                if ($this->is_unsigned_int($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            } else {
                                if ($this->is_int($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            }
                        } elseif ($column->DATA_TYPE == "bigint") {
                            if (strpos('unsigned', $column->COLUMN_TYPE) !== FALSE) {
                                if ($this->is_unsigned_bigint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            } else {
                                if ($this->is_bigint($key) == false) {
                                    $this->errors[] = $this->error;
                                }
                            }
                        }
                    }
                    //non ints
                    elseif ($column->IS_NULLABLE == "YES" && $this->is_empty($key) == true) {
                        //Nulls allowed
                    } elseif ($column->IS_NULLABLE == "NO" && $this->is_empty($key) == true && in_array($key, $this->object['null_check_exceptions'])) {
                        //In null check exclusion list so ignore.
                    } elseif ($column->IS_NULLABLE == "NO" && (strlen($column->COLUMN_DEFAULT) != 0) && $this->is_empty($key) == true) {
                        //will use default value
                    } elseif ($column->IS_NULLABLE == "NO" && (strlen($column->COLUMN_DEFAULT) == 0) && $this->is_empty($key) == true) {
                        $this->errors[] = $this->error;
                    } elseif (in_array($column->DATA_TYPE, $this->float_type_array) && $this->is_decimal($key, $column) == false) {
                        $this->errors[] = $this->error;
                    } elseif ($column->DATA_TYPE == "boolean" && $this->is_boolean($value) == false) {
                        $this->errors[] = $this->error;
                    } elseif ($column->DATA_TYPE == "bit" && $this->is_bit($value) == false) {
                        $this->errors[] = $this->error;
                    } elseif (($column->DATA_TYPE == 'date' || $column->DATA_TYPE == 'datetime') && $this->is_date($key) == false) {
                        $this->errors[] = $this->error;
                    }
                    if ($key == 'postcode' && !empty($value) && $this->is_postcode($key) == false) {
                        $this->errors[] = $this->error;
                    }
                    if ($key == 'email' && !empty($value) && $this->is_email($key) == false) {
                        $this->errors[] = $this->error;
                    }
                }
            }
            $unused_table_columns = array_diff(array_keys($this->object['table_types']), $used_table_columns);
            if (count($unused_table_columns) != 0) {
                foreach ($unused_table_columns as $key) {
                    if ($this->object['table_types'][$key]->IS_NULLABLE == "NO" && (strlen($this->object['table_types'][$key]->COLUMN_DEFAULT) == 0)) {
                        $this->errors[] = "Value not specified for '".$this->field_human_name."'";
                    }
                }
            }
            if (count($this->errors) > 0) {
                if ($this->error_log) {
                    error_log("Errors validating $this->object_name");
                    error_log(print_r($this->errors, true));
                }
                return false;
            } else {
                return true;
            }
        }
    }

    function is_object_data_valid() {
        foreach ($this->object['table_fields'] as $key => $fname) {

            if (array_key_exists($fname, $this->object['view_array'])) {
                $this->field_human_name = $this->object['view_array'][$fname]['name'];
            } else {
                $this->field_human_name = $fname;
            }
            if (is_null($this->object[$fname])) {
                $valid = $this->is_data_valid($fname);
            } else {
                $valid = $this->is_data_valid($fname, $this->object[$fname]);
            }
            if ($valid !== true) {
                // error_log("Value " . $this->$fname . " for field $fname failed data check. Check DB types, etc.");
                $this->errors[] = "Value " . $this->object[$fname] . " for field '".$this->field_human_name."' failed data check. Check DB types, etc.";
                return false;
                exit;
            }
        }
        return true;
    }

    function is_data_valid($field_name, $value = null) {

        if (array_key_exists($field_name, $this->object['view_array'])) {
            $this->field_human_name = $this->object['view_array'][$field_name]['name'];
        } else {
            $this->field_human_name = $field_name;
        }
        $error = "Data is not valid for '".$this->field_human_name."'";
        $is_valid = FALSE;

        $data_type = strtolower($this->object['table_types'][$field_name]->DATA_TYPE);
        $column_type = strtolower($this->object['table_types'][$field_name]->COLUMN_TYPE); // for unsigned test
        $charset = strtolower($this->object['table_types'][$field_name]->COLLATION_NAME);

        if (!empty($this->object['table_types'][$field_name]->CHARACTER_MAXIMUM_LENGTH)) $max_length = $this->object['table_types'][$field_name]->CHARACTER_MAXIMUM_LENGTH;

        if (isset($this->object['table_types'][$field_name])) {// && $this->table_types[$field_name]->IS_NULLABLE == "NO" && $this->table_types[$field_name]->COLUMN_DEFAULT == '') {

            if ($this->object['table_types'][$field_name]->DATA_TYPE == "enum") {
                preg_match("/\('(.*)\'\)/", $this->object['table_types'][$field_name]->COLUMN_TYPE, $matches);
                //error_log("Matches:".print_r($matches, true));
                if (isset($matches[1]) && !empty($matches[1])) {
                    $possible_values = explode("','", $matches[1]);
                    //error_log("Value: $value, posibles: ".print_r($possible_values, true));
                    if (!in_array($value, $possible_values)) {
                        $this->errors[] = "Value '$value' is not allowed for '".$this->field_human_name."'! Possible values are: '".$matches[1]."'.";
                        $is_valid = false;
                    } else {
                        $is_valid = TRUE;
                    }
                }
            } elseif ($this->object['table_types'][$field_name]->IS_NULLABLE == "NO" && $this->object['table_types'][$field_name]->COLUMN_DEFAULT != '') {

                $is_valid = TRUE;

            } elseif ($this->object['table_types'][$field_name]->IS_NULLABLE == "YES" && (is_null($value) === TRUE || empty($value))) {

                $is_valid = TRUE;

            } elseif (in_array($data_type, array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'))) {

                if (strpos($this->object['table_types'][$field_name]->EXTRA, 'auto_increment') !== FALSE) {

                    $is_valid = TRUE;

                } elseif (strpos($this->object['table_types'][$field_name]->EXTRA, 'auto_increment') === FALSE && (int)$value == $value) {

                    if (strpos('unsigned', $column_type) !== FALSE) {
                        if ($data_type == 'tinyint') $is_valid = $this->is_supplied_tinyint($value);
                        if ($data_type == 'smallint') $is_valid = $this->is_supplied_smallint($value);
                        if ($data_type == 'mediumint') $is_valid = $this->is_supplied_mediumint($value);
                        if ($data_type == 'int' || $data_type == 'integer') $is_valid = $this->is_supplied_int($value);
                        if ($data_type == 'bigint') $is_valid = $this->is_supplied_bigint($value);
                    } else {
                        if ($data_type == 'tinyint') $is_valid = $this->is_supplied_unsigned_tinyint($value);
                        if ($data_type == 'smallint') $is_valid = $this->is_supplied_unsigned_smallint($value);
                        if ($data_type == 'mediumint') $is_valid = $this->is_supplied_unsigned_mediumint($value);
                        if ($data_type == 'int' || $data_type == 'integer') $is_valid = $this->is_supplied_unsigned_int($value);
                        if ($data_type == 'bigint') $is_valid = $this->is_supplied_unsigned_bigint($value);
                    }
                }
            } elseif (in_array($data_type, array('numeric', 'decimal', 'smallmoney', 'money', 'float', 'real', 'double', 'double precision')) && is_numeric($value) === TRUE) {
                preg_match('/(?<=\()(.+)(?=\))/is', $column_type, $dimensions);
                if (isset($dimensions[0]) && !empty($dimensions[0])) {
                    $dim_array = explode(",", $dimensions[0]);
                    $precision = $dim_array[0];
                    $scale = $dim_array[1];
                    $max_num_length = $precision - $scale;
                    $largest_number = pow(10, $max_num_length) - pow(10, (0-$scale));
                    $is_valid = $this->is_supplied_value_less_than($value, array('length'=>$largest_number));
                    if (!$is_valid) {
                        $this->errors[] = $this->error;
                    }
                } else {
                    $is_valid = TRUE;
                }
            } elseif ($this->object['table_types'][$field_name]->IS_NULLABLE == "NO" && ($this->object['table_types'][$field_name]->COLUMN_DEFAULT == '' || is_null($this->object['table_types'][$field_name]->COLUMN_DEFAULT)) && ($this->is_supplied_null($value) === TRUE || $this->is_supplied_empty($value))) {

                $this->errors[] = "'".$this->field_human_name."' is a required field and cannot be empty!";
                $is_valid = false; //error_log("Setting false");

            } elseif (($data_type == 'char' || $data_type == 'varchar') && isset($max_length)) {
                $is_valid = $this->is_supplied_length_less_than($value, array('length'=>$max_length));
                // TODO: charset checking and use mb_strlen
                // can use COLLATION_NAME from INFORMATION_SCHEMA and test for utf-8 in string in MySQL
            } elseif (($data_type == 'nchar' || $data_type == 'nvarchar') && isset($max_length) && $max_length >= mb_strlen($value, 'unicode')) {
                $is_valid = TRUE;
            } elseif ($data_type == 'text' || $data_type == 'blob') {
                $is_valid = $this->is_supplied_length_less_than($value, array('length'=>$this->text_length));
            } elseif ($data_type == 'mediumtext' || $data_type == 'mediumblob') {
                $is_valid = $this->is_supplied_length_less_than($value, array('length'=>$this->mediumtext_length));
            } elseif ($data_type == 'longtext' || $data_type == 'longblob') {
                $is_valid = $this->is_supplied_length_less_than($value, array('length'=>$this->longtext_length));
            } elseif ($data_type == 'boolean') {
                $is_valid = $this->is_supplied_boolean($value);
            } elseif ($data_type == 'bit') {
                $is_valid = $this->is_supplied_bit($value);
            } elseif ($data_type == 'timestamp') {
                $is_valid = $this->is_supplied_int($value);
            } elseif ($data_type == 'date' || $data_type == 'datetime') {
                $is_valid = $this->is_supplied_date($value);
            } else {
                // unhandled data type
                //$is_valid = TRUE;//  error_log("Setting $field_name valid 24");
            }
        }
        if (!$is_valid) $this->errors[] = $error;
        return $is_valid;
    }
}

?>
