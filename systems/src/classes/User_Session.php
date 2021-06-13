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
class User_Session extends Object{

    public $table = "system_session";
    public $left_join = "
        LEFT JOIN system_users u ON u.id=t.user_id
    ";
    public $other_selects = "
        ,u.fullname as user_name
    ";
    public $view_array = array(
            'user_name'=>array("name"=>"User","column"=>"user_name"),
            'ip'=>array("name"=>"Access IP","column"=>"access"),
            'created_on'=>array("name"=>"Login / Session Start","column"=>"created_on"),
            'access'=>array("name"=>"Last Access","column"=>"access"),
            'size'=>array("name"=>"Size"),
            'user_agent'=>array("name"=>"Browser","toggle_all"=>true),
            'id'=>array("name"=>"Session ID",'width'=>'200px'),
    );
    public $object_pk = "id";
    public $orderby = "t.access";
    public $dir = "DESC";
    public $where = array("WHERE t.id<>''", array(), array());

    function delete() {
        global $db;
        $db->delete("system_session_key_value", array("WHERE id = ?", array('id' => $this->id), array('integer')));
        return parent::delete();

    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $item->access = date($user1->preferences->dateformat . " H:i",$item->access) . ' <span style="color:blue;">'.rel_time($item->access,time()).'</span>';

        $item->user_name = href_link(array(
                "permission"=>$user1->{"user_details.php"},
                "url"=>$cfg['root'] . "user_details.php?user_id=$item->user_id",
                "text"=>$item->user_name,
                "title"=>"User Details",
                "popup"=>false,
                "button"=>false,
                "clear"=>false,
                "tooltip"=>'User details',
                "expand_details"=>"User",
        ));

        $item->created_on = zero_date($item->created_on,"d M Y H:i") . ' <span style="color:blue;">'.rel_time(strtotime($item->created_on),time()).'</span>';

        $item->ip = $item->ip . " (".gethostbyaddr($item->ip).")";

        $item->user_agent = text_toggler(checkClient($item->user_agent,false,true));

        $item->size = print_filesize(null,strlen($item->data));

        return;
    }
}
