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
class Intrusion extends Object{

    public $table = "system_intrusions";
    public $left_join = "
                            LEFT JOIN system_users u ON u.id=t.user_id
                            ";
    public $other_selects = "
                                ,u.fullname as user
                                ";
    public $orderby = "t.created";
    public $dir = "DESC";
    public $view_array = array(
                                    'created'=>array("name"=>"Time","column"=>"created"),
                                    'user'=>array("name"=>"User","column"=>"user"),
                                    'impact'=>array("name"=>"Impact","column"=>"impact"),
                                    'name'=>array("name"=>"Field","column"=>"name"),
                                    'value'=>array("name"=>"Value", "toggle_all"=>true),
                                    'page'=>array("name"=>"Page","column"=>"page"),
                                    'tags'=>array("name"=>"Filter Tags","column"=>"tags"),
                                    'filter_id'=>array("name"=>"Filter ID","column"=>"filter_id"),
                                    'description'=>array("name"=>"Description","column"=>"description"),
                                    'ip'=>array("name"=>"Client IP","column"=>"ip"),
                                    'domain'=>array("name"=>"Domain","column"=>"domain"),
                                    'origin'=>array("name"=>"Server IP","column"=>"origin"),
                                    'page'=>array("name"=>"Page","column"=>"page"),
    );

    function delete_old_intrusion_logs() {
        global $db, $cfg, $user1;
        $db->delete($this->table, array("WHERE created<=?", array('created' => date("Y-m-d H:i:s",strtotime("-6 months", time()))), array('datetime')));
        $_SESSION['feedback'] .= g_feedback("success","Success: All intrusion logs older than 6 months have been deleted.");
        return true;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;
        $item->created = zero_date($item->created,$user1->preferences->dateformat . " H:i:s");

        $page = parse_url($item->page);
        $page['path']= str_replace($cfg['root'],"",$page['path']);
        if (substr($page['path'],0,1)=="/") $page['path'] = substr($page['path'],1);

        $item->page = href_link(array(
            "permission"=>(!empty($user1->{$page['path']})),
            "url"=>$item->page,
            "text"=>text_toggler($page['path']),
            "tooltip"=>"Page",
            "button"=>false,
            "popup"=>false,
        ));

        $item->value = text_toggler($item->value, 30);
        $item->description = text_toggler($item->description, 20);

        $item->page_td_class = "\" style=\"word-wrap: break-word;";
        $item->value_td_class = "\" style=\"word-wrap: break-word;";
        $item->description_td_class = "\" style=\"word-wrap: break-word;";

        return;
    }
}
?>
