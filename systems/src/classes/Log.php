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
class Log extends Object {

    public $table = "system_log";
    public $left_join = "
        LEFT JOIN system_users u ON u.id=t.user_id
    ";
    public $other_selects = "
        ,u.fullname as user
    ";
    public $orderby = "t.time";
    public $dir = "DESC";
    public $view_array = array(
        'time'=>array("column"=>"time","name"=>"Time"),
        'user'=>array("name"=>"User","column"=>"user"),
        'action'=>array("name"=>"Action","column"=>"action"),
        'object'=>array("name"=>"Object / Table","column"=>"object"),
        'object_id'=>array("name"=>"Object ID / Info"),
        'comment'=>array("name"=>"Comment / Name / IP ", "toggle_all"=>true),
    );

    function _set_table_list_row_items($item){

        global $db, $cfg, $user1, $libhtml;

        if ($item->user_id>0) $item->user = href_link(array(
                "permission"=>$user1->{"user_details.php"},
                "url"=>$cfg['root'] . "user_details.php?user_id=$item->user_id",
                "text"=>$item->user,
                "tooltip"=>"User Details",
                "button"=>false,
                "popup"=>false,
                "expand_details"=>"User",
        ));

        $item->time = zero_date($item->time,$user1->preferences->dateformat . " H:i:s");

        if (preg_match('/<table/', $item->comment)) {

            $item->comment = href_link(array(
                "permission"=>true,
                "url"=>$cfg['root'] . "log.php?log_id=$item->id",
                "text"=>'Click to see full details',
                "tooltip"=>"Log Details",
                "button"=>false,
                "popup"=>false,
                "expand_details"=>"Log",
                'expand_url_details'=>true,
            ));

        } else {

            $item->comment = text_toggler($item->comment);
        }


        return;
    }

    function delete_old_logs() {
        global $db;

        return $db->delete(
                $this->table,
                array(
                    "WHERE time<=?",
                    array('time' => date("Y-m-d H:i:s", strtotime("-6 months", time()))),
                    array('datetime')
                )
        );
    }

    function print_details(){

        return $this->comment;

    }

    function print_search_form(){
        global $db, $cfg, $user1, $libhtml;

        $html = $libhtml->form_start();

        $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
        $html .= open_table("600px","","action_form details_form");

        $html .= $libhtml->render_form_table_row_date("from_date", my_request("from_date"), "Date - From", "from_date",array('self_submit'=>true));
        $html .= $libhtml->render_form_table_row_date("to_date", my_request("to_date"), "Date - Until", "to_date",array('self_submit'=>true));

        $selection=$db->select("id, fullname","system_users",array(), array('order_by' => "ORDER BY fullname ASC"));
        $html .= $libhtml->render_form_table_row_selection("user", my_request("user"), "User", "user",$selection,"id","fullname",array('self_submit'=>true));

        $selection=$db->select_distinct("action","system_log",array(), array('order_by' => "ORDER BY action ASC"));
        $html .= $libhtml->render_form_table_row_selection("action", my_request("action"), "Action", "action",$selection,"action","action",array('self_submit'=>true));

        $selection=$db->select_distinct("object","system_log",array("WHERE object<>''", array(), array()), array('order_by' => "ORDER BY object ASC"));
        $html .= $libhtml->render_form_table_row_selection("object", my_request("object"), "Object", "object",$selection,"object","object",array('self_submit'=>true));

        if (my_request("object")!=''){

            $selection=$db->select_distinct("object_id","system_log",array("WHERE object=?", array(my_request('object')), array('varchar')), array('order_by' => "ORDER BY object_id DESC"));
            $html .= $libhtml->render_form_table_row_selection("object_id", my_request("object_id"), "Object ID", "object_id",$selection,"object_id","object_id",array('self_submit'=>true));
        }

        $html .= close_table();
        $html .= $libhtml->render_form_table_row_hidden("search", "Search");
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

        $html .= $libhtml->form_end();

        $where = array(array(), array(), array());

        if (my_request("from_date")!="") {
            $where[0][]= "t.time>=?";
            $where[1][] = my_request("from_date") . " 00:00:00";
            $where[2][] = 'datetime';
        }

        if (my_request("to_date")!="") {
            $where[0][]= "t.time<=?";
            $where[1][] = my_request("to_date") . " 23:59:59";
            $where[2][] = 'datetime';
        }

        if (my_request("user")!="") {
            $where[0][]= "t.user_id=?";
            $where[1][] = my_request("user");
            $where[2][] = 'integer';
        }

        if (my_request("action")!="") {
            $where[0][]= "t.action=?";
            $where[1][] = my_request("action");
            $where[2][] = 'varchar';
        }

        if (my_request("object")!="") {
            $where[0][]= "t.object=?";
            $where[1][] = my_request("object");
            $where[2][] = 'varchar';
        }

        if (my_request("object_id")!="") {
            $where[0][]= "t.object_id=?";
            $where[1][] = my_request("object_id");
            $where[2][] = 'varchar';
        }

        $where[0] = (!empty($where[0])) ? ' WHERE '.implode(' AND ',$where[0]) :'';

        return array(
                'html'=>$libhtml->page_search_section($html),
                'where'=>$where,
        );


    }
}
