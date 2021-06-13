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
interface IDatabase2 {

    function __construct($dsn, $username, $password, $options = array());

    function concat($vars);

    function close();

    function get_table_columns($table);

    function get_column_usage($column_name);

    function get_db_schema($where = "");

    function replace($table, $update_fields, $where);

    function insert($table, array $insert_fields, array $types = array());

    function bulk_insert($table, array $fields, array $all_values);

    function delete($table, array $where, array $types = array());

    function select($fields, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array()));

    function select_value($field, $tables, array $where, array $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array(), 'return_all' => false));

    function select_distinct($fields, $tables, array $where, $options = array('joins' => "", 'order_by' => "", 'group_by' => "", 'limit' => array()));

    function tcount($table, array $where, array $options = array('joins' => '', 'limit' => array()));

    function tcount_distinct($field="id", $table, array $where, array $options = array('joins' => '', 'limit' => array()));

    function update($table, array $update_fields, array $where, array $types = array());

    function table_exists($table_check);

    function start_transaction();

    function complete_transaction();

    function get_sqlstate();
    function getAttribute($attribute);
    function get_query_stats();

    function get_explain_stats();

}
