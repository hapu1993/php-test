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
class Wastebasket extends Object{

    public $table = "system_wastebasket";
    public $left_join = " LEFT JOIN system_users su on t.user_id = su.id";
    public $other_selects = ",su.fullname";
    public $view_array = array(
        'deletion_date'=>array("name"=>"Deletion Date", "column"=>"deletion_date"),
        'fullname'=>array("name"=>"User", "column"=>"fullname"),
        'object'=>array("name"=>"Object", "column"=>"object"),
        'object_key'=>array("name"=>"Primary Key","column"=>"object_key"),
        'information'=>array("name"=>"Object Info","toggle_all"=>true),
        'restore'=>array("name"=>"Restore", "show_name"=>false, "width"=>"16px"),
    );

    function restore() {
        global $cfg, $db, $user1, $libhtml;

        $this->select($this->id);

        $x= json_decode(urldecode($this->content));

        foreach($x->table_fields as $key) $restore_array[$key]=$x->$key;
        //What about id=0?

        if (!empty($restore_array) && (!empty($x->{$x->object_pk}) || $x->{$x->object_pk}==0)) {

            $x_schema = $db->get_table_column_metadata($x->table);
            $x_table_types = array();
            foreach ($x_schema as $schema) {
                if (isset($schema->COLUMN_NAME)) {
                    $x_table_types[$schema->COLUMN_NAME] = (array) $schema;
                } elseif (isset($schema->column_name)) {
                    $x_table_types[$schema->column_name] = (array) $schema;
                    $x_table_types[$schema->column_name]  = array_change_key_case($x_table_types[$schema->column_name], CASE_UPPER);
                }
            }

            $db->insert($x->table, $restore_array, $x_table_types);

            $db->delete($this->table, array("WHERE id = ?", array('id' => $this->id), array('integer')));

            $this->system_log->insert(array(
                'time' => date("Y-m-d H:i:s"),
                'user_id' => $user1->id,
                'object' => $this->human_name,
                'action' => "Restored ".$this->object,
                'object_id' => $x->{$x->object_pk},
                'comment' => $this->show(),
            ));

            // if restore, remove from session - shown in side panel
            if (isset($_SESSION["deleted_objects"][$this->id])) unset ($_SESSION["deleted_objects"][$this->id]);

        } else {

            $_SESSION['feedback'] .= g_feedback("error", "Content cannot be empty.");
            return false;

        }
    }

    function print_form() {
        global $cfg, $db, $libhtml;
        $html = $libhtml->form_start();
        $html .= $this->show();
        return $html;
    }

    function show() {
        global $cfg, $libhtml;

        $x = json_decode(urldecode($this->content));

        $html = open_table('100%');
        $html .= $libhtml->render_table_row("User", $this->fullname);
        $name = (!empty($x->human_name)) ? $x->human_name : ucfirst(str_replace("_"," ",$x->object_name));
        $html .= $libhtml->render_table_row("Object", $name);
        $html .= close_table();

        $new_class = new $x->class_name;
        foreach($x as $key=>$value) $new_class->$key = $value;
        $html .= $new_class->show();

        return $html;
    }

    function print_restore_form() {
        global $libhtml;

        $html = $this->print_form();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("restore_wastebasket", "Restore")
            ),
            array(
                "pause"=>false
            )
        );

        $html .= $libhtml->form_end();

        return $html;
    }

    function print_delete_form() {
        global $libhtml;

        $html = $this->print_form();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("delete_wastebasket", "Delete")
            ),
            array(
                "pause"=>false
            )
        );

        $html .= $libhtml->form_end();

        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $item->deletion_date = zero_date($item->deletion_date);

        $item->information = text_toggler($item->information, $item->id, 50, false);

        $item->restore=($user1->{"restore_wastebasket.php"}) ? href_link(array(
                "permission"=>true,
                "url"=>$cfg['root'] . "restore_wastebasket.php?wastebasket_id=$item->id",
                "text"=>'<span class="ico_restore"><i class="fa fa-rotate-right"></i></span>',
                "button"=>false
        )) : '</td>';
        return;
    }

    function _empty(){
        global $db, $cfg, $user1;

        $db->delete($this->table, array());

        unset($_SESSION["deleted_objects"]);

        return true;
    }
}
