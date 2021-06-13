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

//System Log is the minimal class to insert entries with DB layer just like Log which extends Object
//We do not want for System_Log to extend Object due to recursive logging

class System_Log {

    private $table = 'system_log';
    private $table_types = array();

    public function __construct() {
        global $db;

        $table_schema = $db->get_table_column_metadata($this->table);

        foreach ($table_schema as $schema) {
            if (isset($schema->COLUMN_NAME)) {
                $this->table_types[$schema->COLUMN_NAME] = (array) $schema;
            } elseif (isset($schema->column_name)) {
                $this->table_types[$schema->column_name] = (array) $schema;
                $this->table_types[$schema->column_name]  = array_change_key_case($this->table_types[$schema->column_name], CASE_UPPER);
            }
        }
    }

    public function insert(array $data) {
        global $db;

        return $db->insert($this->table, $data, $this->table_types);
    }

}
