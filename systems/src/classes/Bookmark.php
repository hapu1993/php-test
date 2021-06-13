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
class Bookmark extends Object{

    public $table = "system_user_bookmarks";
    public $left_join = "
                            ";
    public $other_selects = "
                                ";
    public $orderby = "t.sort_order";
    public $dir = "ASC";
    public $view_array = array(
        'name'=>array("name"=>"Page"),
    );

    function insert() {
        global $db, $user1;
        $this->url = $_SERVER['HTTP_REFERER'];
        $name = end($_SESSION['history']);
        $this->name = $name['page'];
        if (!empty($this->user_added)) $this->name = '<span class="l4">'. $this->user_added . '</span>' . str_replace("l4", "l3", $this->name);
        $this->user_id = $user1->id;
        return parent::insert();
    }

    function print_form() {
        global $cfg, $db, $user1, $libhtml;

        $url = parse_url($_SERVER['HTTP_REFERER']);
        $url_str = $url['scheme']."://".$url['host'].$url['path'];
        if (strlen($url_str)>60) $url_str = substr($url_str,0,60) . "...";
        $name = end($_SESSION['history']);

        $html = $libhtml->form_start();

        $html .= open_table();
        $html .= $libhtml->render_table_row("Bookmark URL",$url_str);
        $html .= $libhtml->render_table_row("Bookmark Name",$name['page']);
        $html .= $libhtml->render_form_table_row("bookmark[user_added]", "", "User Info", "user_added");
        $html .= close_table();

        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1;
        $item->name = '<a href="'.$item->url.'" title="Go to page">'.$item->name.'</a>';
        return;
    }

}
