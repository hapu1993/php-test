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

/**
 *
 * All tests starting is_supplied_ validates values, is_ validates object variables
 * functions alpha, alphanumeric, alphadash, urlexists, creditcard, name are
 * adapted from the Gump class (https://github.com/Wixel/GUMP/blob/master/gump.class.php)
 *
 * if using validate or validate_array results are boolean with errors in $this->errors
 * otherwise results are boolean and errors in $this->error e.g.
 *

    $validator = new Validator($this);
    if (!$validator->validate()) dump_var($validator->errors);

 * or

    $validator = new Validator();
    $result = $validator->validate_array(array('test@test.co.ua', 1234,"abcd", 'http://www.riskpoint.co.uk',
                                    'http://test.adfbsinsffgb.com', '1234567898765432'),
                            array('email|length_less_than;length:5', 'not_null|tinyint|smallint|alphanumeric|value_between;min:100;max:2000',
                                    'alpha|urlexists', 'urlexists', 'urlexists', 'creditcard'));

    if (!$result) dump_var($validator->errors);

 * or

    $validator = new Validator();
    $variable = 'test';
    $result = $validator->is_supplied_length_greater_than($variable, 5);
    if (!$result) dump_var($validator->error);

 *
 */
class Validator {
    //set to true to log all validate/validate_array failures to the error_log
    protected $error_log = false;
    //32-bit lengths
    protected static $tinyint = 127;
    protected static $smallint = 32767;
    protected static $mediumint = 8388607;
    protected static $int = 2147483647;
    protected static $bigint = 9223372036854775807;

    protected static $text_length = 65535;
    protected static $mediumtext_length = 16777215;
    protected static $longtext_length = 4294967295;

    protected $int_type_array = array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint');
    protected $float_type_array = array('numeric', 'decimal', 'smallmoney', 'money', 'float', 'real', 'double', 'double precision');
    protected $text_type_array = array('char','varchar','nchar','nvarchar','text','blob','mediumtext','mediumblob','longtext','longblob');
    //
    public static $error = "";
    public $errors = array();

    function __construct($object = "") {
        if (!empty($object)) {
            $this->object_name = get_class($object);
            //cast to array so we can use array_key_exists for testing existance of key;
            $this->object = (array) $object;
            //$this->db = $db;

            if (!empty($this->object)) {
                foreach($this->object as $key=>$value) {
                    if (isset($this->object['validators']) && !empty($this->object['validators'])) {
                        if (array_key_exists($key, $this->object['validators'])) {
                            $this->object_keys[] = $key;
                            $this->object_values[] = $value;
                            $this->object_filters[] = $this->object['validators'][$key];
                        }
                    }
                }
            }
        }
    }

    function clear_errors() {
        $this->error = "";
        $this->errors = array();
    }

    function validate() {
        global $db;
        //error_log("Attempting to validate");
        if (!empty($this->object_values) || !empty($this->object_filters)) {
            $valid3 = $this->validate_array($this->object_values, $this->object_filters);
        }
        $valid = $db->validate_columns($this->object, $this);

        if ($valid === true) {
            if (isset($valid3) && $valid3 === true) {
                //error_log("1, 2 and 3 true");
                return true;
            } elseif (!isset($valid3)) {
                //error_log("1 and 2 true");
                return true;
            } else {
                //error_log("1 and 2 true, 3 false");
                return false;
            }
        } else {
            //error_log("1 or 2 false");
            return false;
        }
    }

    /**
     * Takes array of values and array of tests (pipe separated) for values
     * eg. array('test@test.co.uk.a', 1234), array('email', 'tinyint|length_less_than;length:5')
     *
     * Valid test names are:
     * postcode, email, tinyint, unsigned_tinyint, smallint, unsigned_smallint,
     * mediumint, unsigned_mediumint, int, unsigned_int, bigint, unsigned_bigint,
     * decimal, date, empty, boolean, bit, null, not_null, alpha, alphanumeric,
     * alphadash, urlexists, creditcard, name, length_greater_than, length_less_than,
     * length_between, value_greater_than, value_less_than, value_between
     *
     * @param  array $values
     * @param  array $filters
     * @return boolean
     */
    function validate_array($values, $filters) {
        if (count($values) == 0 || count($filters) == 0) {
            error_log("Values/Filters empty nothing to validate");
        }
        if (count($values) != count($filters)) {
            $this->errors = array("Array counts do not match");
            if ($this->error_log) {
                error_log("Errors validating array('".implode("','", $values)."')");
                error_log("against array('".implode("','", $filters)."')");
                error_log(print_r($this->errors, true));
            }
            return false;
        } else {
            $results=array();
            $i = 0;
            foreach ($values as $key => $value) {
                $rules = explode("|", $filters[$key]);
                foreach ($rules as $rule) {
                    $args = array();
                    if (strpos($rule, ";")) {
                        $vars = explode(";", $rule);
                        $rule = $vars[0];
                        foreach ($vars as $var) {
                            if (strpos($var, ":")) {
                                $arg = explode(":", $var);
                                $k = $arg[0];
                                $v = $arg[1];
                                $args[$k] = $v;
                            }
                        }
                    }
                    if (array_key_exists($this->object_keys[$i], $this->object['view_array'])) {
                        $this->field_human_name = $this->object['view_array'][$this->object_keys[$i]]['name'];
                    } else {
                        $this->field_human_name = $this->object_keys[$i];
                    }
                    //error_log(print_r($this->object['view_array'], true));
                    //error_log($this->object_keys[$i]);
                    //error_log("Value: $value");
                    $func = "is_supplied_".$rule;
                    if (count($args) == 0) {
                        $result = self::$func($value);
                    }  else {
                        $result = self::$func($value, $args);
                    }
                    //error_log(print_r($result));
                    if ($result !== true) $results[] = self::$error ." Field: '".$this->field_human_name."'";
                }
                $i++;
            }
            if (count($results)>0) {
                $this->errors = $results;
//                 if ($this->error_log) {
                    error_log("Number of results: ".count($results));
                    error_log("Errors validating array('".implode("','", $values)."')");
                    error_log("against array('".implode("','", $filters)."')");
                    error_log(print_r($this->errors, true));
//                 }
                return false;
            } else {
                return true;
            }
        }
    }

    //Postcode
    function is_postcode($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_postcode($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid Postcode supplied for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_postcode($value) {
        $postcode = strtoupper(str_replace(' ','',$value));
        $result = (preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/",$postcode) || preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/",$postcode) || preg_match("/^GIR0[A-Z]{2}$/",$postcode));
        self::$error = ($result) ? '' : "Invalid Postcode"." specified - ".self::get_value($value);
        return $result;
    }

    //Email
    function is_email($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_email($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid Email supplied for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_email($value) {
        $email_pattern = "/[A-z0-9\._-]+" . "@" . "[A-z0-9][A-z0-9-]*" . "(\.[A-z0-9_-]+)*" . "\.([A-z]{2,6})$/";
        $result = preg_match($email_pattern, $value);
        self::$error = ($result) ? '' : "Invalid Email"." specified - ".self::get_value($value);
        return $result;
    }

    //Tinyint
    function is_tinyint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_tinyint($this->object[$fieldname]);
            $this->error = ($result) ? '' : $this->error = "Invalid integer specified for '".$this->field_human_name."' must lie within the range ".(($this->tinyint*-1)-1)." and {$this->tinyint}";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_tinyint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range ".((self::$tinyint*-1)-1)." and ".self::$tinyint." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > (self::$tinyint*-1) && $value < self::$tinyint) {
            self::$error = "";
            $result =  true;
        }
        return $result;
    }

    //Unsigned tinyint
    function is_unsigned_tinyint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_unsigned_tinyint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range 0 and ".(($this->tinyint*2)+1);
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_unsigned_tinyint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range 0 and ".((self::$tinyint*2)+1)." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > 0 && $value < (self::$tinyint*2)+1) {
            self::$error = "";
            $result =  true;
        }
        return $result;
    }

    //Smallint
    function is_smallint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_smallint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range ".(($this->smallint*-1)-1)." and {$this->smallint}";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_smallint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range ".((self::$smallint*-1)-1)." and ".self::$smallint." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > (self::$smallint*-1) && $value < self::$smallint) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Unsigned smallint
    function is_unsigned_smallint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_unsigned_smallint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range 0 and ".$this->smallint*2 +1;
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_unsigned_smallint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range 0 and ".(self::$smallint*2 +1)." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > 0 && $value < (self::$smallint*2)+1) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Mediumint
    function is_mediumint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_mediumint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range ".(($this->mediumint*-1)-1)." and {$this->mediumint}";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_mediumint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range ".((self::$mediumint*-1)-1)." and ".self::$mediumint." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > (self::$mediumint*-1) && $value < self::$mediumint) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Unsigned mediumint
    function is_unsigned_mediumint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_unsigned_mediumint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range 0 and ".$this->mediumint*2 +1;
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_unsigned_mediumint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range 0 and ".(self::$mediumint*2 +1)." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > 0 && $value < (self::$mediumint*2)+1) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Int
    function is_int($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_int($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range ".(($this->int*-1)-1)." and ".$this->int;
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_int($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range ".((self::$int*-1)-1)." and ".self::$int." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > (self::$int*-1) && $value < self::$int) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Unsigned int
    function is_unsigned_int($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_unsigned_int($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range 0 and ".$this->int*2 +1;
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_unsigned_int($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range 0 and ".(self::$int*2 +1)." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > 0 && $value < (self::$int*2)+1) {
            self::$error = "";
            $result =  true;
        }
        return $result;
    }

    //Bigint
    function is_bigint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_bigint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range ".(($this->bigint*-1)-1)." and {$this->bigint}";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_bigint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range ".((self::$bigint*-1)-1)." and ".self::$bigint." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > (self::$bigint*-1) && $value < self::$bigint) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Unsigned bigint
    function is_unsigned_bigint($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_unsigned_bigint($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid integer specified for '".$this->field_human_name."' must lie within the range 0 and ".$this->bigint*2 +1;
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_unsigned_bigint($value) {
        $result = false;
        self::$error = "Invalid integer must lie within the range 0 and ".(self::$bigint*2 +1)." specified - ".self::get_value($value);
        if ((is_int($value) || strval(intval($value)) == strval($value)) && $value > 0 && $value < (self::$bigint*2)+1) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    //Decimal
    function is_decimal($fieldname, $column="") {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = true;
            $this->error = "";
            $data_type = (!empty($column) && isset($column->DATA_TYPE) && !empty($column->DATA_TYPE)) ? $column->DATA_TYPE : "decimal";
            if (is_numeric($this->object[$fieldname]) == false) {
                $this->error = "Invalid {$data_type} format specified for '".$this->field_human_name."'";
                $result = false;
            } elseif ($this->is_supplied_decimal($this->object[$fieldname], $column) == false) {
                $this->error = "Invalid precision for {$data_type} specified for '".$this->field_human_name."'";
                $result = false;
            }
            return $result;
        } else {
            return false;
        }
    }

    function is_supplied_decimal($value, $column="") {
        $result = true;
        $this->error = "";
        $data_type = (!empty($column) && isset($column->DATA_TYPE) && !empty($column->DATA_TYPE)) ? $column->DATA_TYPE : "decimal";
        if (is_numeric($value) === FALSE) {
            $this->error = "Invalid {$data_type} format specified for value"." specified - ".$this->get_value($value);
            $result = false;
        } elseif (isset($column->NUMERIC_PRECISION) && !empty($column->NUMERIC_PRECISION) &&
                isset($column->NUMERIC_SCALE) && !empty($column->NUMERIC_SCALE)) { // MSSQL (PDO)
            $int_part_size = $column->NUMERIC_PRECISION;
            $decimal_part_size = $column->NUMERIC_SCALE;
            $new_value = $value;
            if ($new_value < 0) $new_value = $new_value *-1;
            $value_array = explode(".", $new_value);
            //MySQL precision is max length of int and decimal parts, scale is max length of decimal part.
            //values inserted greater than this will be truncated to the max allowed value
            //e.g. inserting 1234.321 into a decimal(3,1) column will result in 99.9 being inserted
            if (strlen($value_array[0]) > ($int_part_size - $decimal_part_size) || (isset($value_array[1]) && strlen($value_array[1]) > $decimal_part_size)) {
                $this->error = "Invalid precision for ".$data_type."(".$int_part_size.",".$decimal_part_size.")"." specified with value $value";
                $result = false;
            }
        } elseif (!empty($column->COLUMN_TYPE)) { //MySQL
            $open_bracket = strpos($column->COLUMN_TYPE, "(");
            $comma = strpos($column->COLUMN_TYPE, ",");
            $close_bracket = strpos($column->COLUMN_TYPE, ")");
            if ($open_bracket !== FALSE && $comma !== FALSE && $close_bracket !== FALSE) {
                $int_part_size = substr($column->COLUMN_TYPE, $open_bracket+1, $comma - $open_bracket-1);
                $decimal_part_size = substr($column->COLUMN_TYPE, $comma+1, $close_bracket - $comma-1);
                $new_value = $value;
                if ($new_value < 0) $new_value = $new_value *-1;
                $value_array = explode(".", $new_value);
                //MySQL precision is max length of int and decimal parts, scale is max length of decimal part.
                //values inserted greater than this will be truncated to the max allowed value
                //e.g. inserting 1234.321 into a decimal(3,1) column will result in 99.9 being inserted
                if (strlen($value_array[0]) > ($int_part_size - $decimal_part_size) || (isset($value_array[1]) && strlen($value_array[1]) > $decimal_part_size)) {
                    $this->error = "Invalid precision for $column->COLUMN_TYPE specified with value"." specified - ".$this->get_value($value);
                    $result = false;
                }
                //                             dump_var("Int $int_part_size dec $decimal_part_size");
            }
        }
        return $result;
    }

    //Date
    function is_date($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_date($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid date format specified for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_date($value) {
        $is_valid = false;
        self::$error = "Invalid date format specified - ".self::get_value($value);
        $parsed = date_parse($value);
//         dump_var("Parsed: ");
//         var_dump($parsed);
        if ($parsed['error_count'] == 0) {
            // TODO: date parsed currently needs to be in Y-m-d format
            $date_time_part = explode(' ', $value);
            $date_parts = explode('-', $date_time_part[0]);
//             $is_valid = ($value=="0000-00-00 00:00:00" || $value=="0000-00-00" || checkdate($date_parts[1], $date_parts[2], $date_parts[0]));
            $is_valid = ($value=="0000-00-00 00:00:00" || $value=="0000-00-00" || checkdate($parsed["month"], $parsed["day"], $parsed["year"]));
//             dump_var("Is Valid? ");
//             var_dump($is_valid);
            if ($is_valid) self::$error = "";
            return $is_valid;
        } else {
            return $is_valid;
        }
    }

    //Empty
    function is_empty($fieldname) {
        //error_log("empty testing $fieldname, ".$this->object[$fieldname]);
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_empty($this->object[$fieldname]);
            $this->error = (!$result) ? '' : "Value not specified for '".$this->field_human_name."'";
            //($result) ? $result = false : $result = true;;
            return $result;
        } else {
            return false;
        }
    }

    static function is_supplied_empty($value) {
        $result = false;
        self::$error = "Value not specified";
        if (strlen($value) == 0) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    //Not empty
    /**
     * Tests for not Empty values (included for completeness and use within validate_array)
     * @param string $fieldname
     * @return boolean
     */
    function is_not_empty($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_not_empty($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Empty value specified for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Tests for not Empty values (included for completeness and use within validate_array)
     * @param mixed $value
     * @return boolean
     */
    static function is_supplied_not_empty($value) {
        $result = false;
        self::$error = "Empty value specified - ".self::get_value($value);
        //error_log($this->error);
        if (!empty($value)) {
            $result =  true;
            self::$error = "";
        }
        //error_log($this->error);
        return $result;
    }

    /**
     * Tests for true and false values
     * @param string $fieldname
     * @return boolean
     */
    function is_boolean($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_boolean($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid boolean specified for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Tests for true and false values
     * @param mixed $value
     * @return boolean
     */
    static function is_supplied_boolean($value) {
        $result = false;
        self::$error = "Invalid boolean specified - ".self::get_value($value);
        if ($value === TRUE || $value === FALSE) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    /**
     * Tests for 0 and 1 values
     * @param string $fieldname
     * @return boolean
     */
    function is_bit($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_bit($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Invalid bit specified for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Tests for 0 and 1 values
     * @param mixed $value
     * @return boolean
     */
    static function is_supplied_bit($value) {
        $result = false;
        self::$error = "Invalid bit specified - ".self::get_value($value);
        if ($value === 0 || $value === 1) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    /**
     * Tests for NULL values (included for completeness and use within validate_array)
     * @param string $fieldname
     * @return boolean
     */
    function is_null($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_null($this->object[$fieldname]);
            $this->error = ($result) ? '' : "Not NULL specified for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Tests for NULL values (included for completeness and use within validate_array)
     * @param mixed $value
     * @return boolean
     */
    static function is_supplied_null($value) {
        $result = false;
        self::$error = "Not NULL specified - ".self::get_value($value);
        if (is_null($value)) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    /**
     * Tests for not NULL values (included for completeness and use within validate_array)
     * @param string $fieldname
     * @return boolean
     */
    function is_not_null($fieldname) {
        if (self::verify_not_statically_called(__FUNCTION__) && $this->check_object_field($fieldname)){
            $result = $this->is_supplied_not_null($this->object[$fieldname]);
            $this->error = ($result) ? '' : "NULL specified for '".$this->field_human_name."'";
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Tests for not NULL values (included for completeness and use within validate_array)
     * @param mixed $value
     * @return boolean
     */
    static function is_supplied_not_null($value) {
        $result = false;
        self::$error = "NULL specified - ".self::get_value($value);
        if (!is_null($value)) {
            $result =  true;
            self::$error = "";
        }
        return $result;
    }

    static function is_supplied_length_greater_than($value, $args = array('length'=>0)) {
        $result = false;
        self::$error = "Value has length less than {$args['length']}"." specified - ".self::get_value($value);
        if (strlen($value) >= $args['length']) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    static function is_supplied_length_less_than($value, $args = array('length'=>0)) {
        $result = false;
        //dump_var($args);
        self::$error = "Value has length greater than {$args['length']}"." specified - ".self::get_value($value);
        if (strlen($value) <= $args['length']) {
            $result = true;
            self::$error = "";
        }
        //var_dump($result);
        //var_dump(self::$error);
        return $result;
    }

    static function is_supplied_length_between($value, $args = array('min'=>0, 'max'=>0)) {
        $result = false;
        self::$error = "Value has length outside the range {$args['min']} - {$args['max']}"." specified - ".self::get_value($value);
        if (strlen($value) >= $args['min'] && strlen($value) <= $args['max']) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    static function is_supplied_value_greater_than($value, $args = array('length'=>0)) {
        $result = false;
        self::$error = "Value is less than {$args['length']}"." specified - ".self::get_value($value);
        if (($value) > $args['length']) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    static function is_supplied_value_greater_than_or_equal($value, $args = array('length'=>0)) {
        $result = false;
        self::$error = "Value is less than {$args['length']}"." specified - ".self::get_value($value);
        if (($value) >= $args['length']) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    static function is_supplied_value_less_than($value, $args = array('length'=>0)) {
        $result = false;
        self::$error = "Value is greater than {$args['length']}"." specified - ".self::get_value($value);
        if (($value) <= $args['length']) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    static function is_supplied_value_between($value, $args = array('min'=>0, 'max'=>0)) {
        $result = false;
        self::$error = "Value lies outside the range {$args['min']} - {$args['max']}"." specified - ".self::get_value($value);
        if (($value) >= $args['min'] && ($value) <= $args['max']) {
            $result = true;
            self::$error = "";
        }
        return $result;
    }

    //TODO: alphanumeric testts etc check gump

    static function is_supplied_alpha($value) {
        $result = true;
        self::$error = "";
        if(!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i", $value) !== FALSE)
        {
            $result = false;
            self::$error = "Value contains non-alpha characters"." specified - ".self::get_value($value);
        }
        return $result;
    }

    static function is_supplied_alphanumeric($value) {
        $result = true;
        self::$error = "";
        if(!preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i", $value) !== FALSE)
        {
            $result = false;
            self::$error = "Value contains non-alphanumeric characters"." specified - ".self::get_value($value);
        }
        return $result;
    }

    static function is_supplied_alphadash($value) {
        $result = true;
        self::$error = "";
        if(!preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i", $value) !== FALSE)
        {
            $result = false;
            self::$error = "Value contains non-alphadash characters"." specified - ".self::get_value($value);
        }
        return $result;
    }

    static function is_supplied_urlexists($value) {
        $result = true;
        self::$error = "";

        $url = str_replace(array('http://', 'https://', 'ftp://'), '', strtolower($value));
//         var_dump(gethostbyname($url));

//         if(function_exists('checkdnsrr'))
//         {
//             var_dump(checkdnsrr($url));
//             if(!checkdnsrr($url)) {
//                 $result = false;
//                 $this->error = "URL ( $value ) does not exist";
//             }
//         } else {
//             var_dump(gethostbyname($url));
            if(gethostbyname($url) == $url) {
                $result = false;
                self::$error = "URL does not exist"." specified - ".self::get_value($value);
            }
//         }
        return $result;
    }

    static function is_supplied_creditcard($value) {
        $number = preg_replace('/\D/', '', $value);

        if(function_exists('mb_strlen')) {
            $number_length = mb_strlen($value);
        } else {
            $number_length = strlen($value);
        }
        if ($number_length <13 || $number_length >19) {
            self::$error = "Invalid credit card number"." specified - ".self::get_value($value);
            return false;
        }

        $parity = $number_length % 2;
        $total = 0;

        for($i = 0; $i < $number_length; $i++) {
            $digit = $number[$i];
            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $total += $digit;
        }

        if($total % 10 == 0) {
            $result = true;
            self::$error = "";
        } else {
            $result = false;
            self::$error = "Invalid credit card number"." specified - ".self::get_value($value);
        }
        return $result;
    }

    /**
     * Uses a regex to test for valid characters within a name.
     *
     * @param  string $value
     * @return boolean
     */
    static function is_supplied_name($value) {
        $result = true;
        self::$error = "";

        if(!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i", $value) !== FALSE) {
            $result = false;
            self::$error = "Not a real name"." specified - ".self::get_value($value);
        }
        return $result;
    }

    /**
     * Helper function to return the output of var_dump instead of echoing it.
     * @param  mixed $var
     * @param  boolean $htmlentities
     * @return string
     */
    static function get_value($var, $htmlentities = true) {
        ob_start();
        call_user_func('var_dump', $var);
        if ($htmlentities) {
            return trim(htmlentities(ob_get_clean()));
        } else {
            return trim(ob_get_clean());
        }
    }

    private function check_object_field($fieldname){
        if (empty($this->object)) {
            $this->error = "Object empty, consider using is_supplied_* function with the value or pass the object into the constructor.";
            return false;
        } elseif (array_key_exists($fieldname, $this->object) == false) {
            $this->error = "Variable {$fieldname} is not set in object";
            return false;
        } else {
            return true;
        }
    }

    function verify_not_statically_called($function) {
        if (!isset($this) || get_class($this) != __CLASS__) {
            throw new Exception ("Validator method $function called statically, you should use the is_supplied".str_replace("is", "", $function)." version of the method.");
        } else {
            return true;
        }
    }
}
