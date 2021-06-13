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

class View {

    public $table = "system_user_table_views";
    public $view = "";
    public $view_table = "";

    function __construct(){
        global $db;

        $selection = $db->get_table_column_metadata($this->table);

        foreach($selection as $item) {

            if (isset($item->COLUMN_NAME)) {

                $this->table_fields[] = $item->COLUMN_NAME;
                $this->table_types[$item->COLUMN_NAME] = $item;

            } elseif (isset($item->column_name)) {

                $this->table_fields[] = strtoupper($item->column_name);
                $this->table_types[strtoupper($item->column_name)] = $item;

            }
        }
    }

    function make_view(){
        global $cfg, $db, $user1;

        $db->insert(
                $this->table,
                array(
                        'user_id'=>$user1->id,
                        'view_table'=>$this->view_table,
                        'view'=>$this->view
                )
        );
    }

    function get_view(){
        global $cfg, $db, $user1;

        $selection = $db->select_value(
                "view",
                $this->table,
                array(
                        "WHERE user_id = ? AND view_table = ?",
                        array('user_id' => $user1->id, 'view_table' => $this->view_table),
                        array('integer', 'varchar')
                )
        );

        return (!empty($selection)) ? unserialize($selection) : '';
    }

    function remove_view(){
        global $cfg, $db, $user1;

        $db->delete(
                $this->table,
                array(
                        "WHERE user_id = ? AND view_table = ?",
                        array('user_id' => $user1->id, 'view_table' => $this->view_table),
                        array('integer', 'varchar')
                )
        );
    }

    function remove_column_view(){
        global $cfg, $db, $user1;

        $view = $this->get_view();

        if (empty($view["no_pagination"])) {

            $this->remove_view();

        } else {

            unset($view["columns"]);
            $db->update(
                    $this->table,
                    array('view'=>serialize($view)),
                    array(
                            "WHERE user_id = ? AND view_table = ?",
                            array('user_id' => $user1->id, 'view_table' => $this->view_table),
                            array('integer', 'varchar')
                    )
            );

        }
    }

    function toggle_pagination($state = "0"){
        global $cfg, $db, $user1;

        $view = $this->get_view();

        if (!empty($view)){

            if (empty($view["columns"]) && $state == 0) {

                $this->remove_view();

            } else if (!empty($view["no_pagination"]) && $state == 0) {

                unset($view["no_pagination"]);
                $db->update(
                        $this->table,
                        array('view'=>serialize($view)),
                        array(
                                "WHERE user_id = ? AND view_table = ?",
                                array('user_id' => $user1->id, 'view_table' => $this->view_table),
                                array('integer', 'varchar')
                        )
                );

            } else {

                $view["no_pagination"] = $state;

                $db->update(
                        $this->table,
                        array('view'=>serialize($view)),
                        array(
                                "WHERE user_id = ? AND view_table = ?",
                                array('user_id' => $user1->id, 'view_table' => $this->view_table),
                                array('integer', 'varchar')
                        )
                );

            }
        } else if (empty($view) && $state == 1){

            $view["no_pagination"] = 1;
            $db->insert(
                    $this->table,
                    array(
                            'view'=>serialize($view),
                            'user_id'=>$user1->id,
                            'view_table'=>$this->view_table
                    )
            );

        }
    }

}
