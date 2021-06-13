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
class User_Comment extends Object{

    public $table = "system_user_comments";
    public $left_join = "
        LEFT JOIN system_users u ON u.id=t.user_id
    ";
    public $other_selects = "
        ,u.fullname as user_name
    ";
    public $view_array = array(
        'date'=>array("name"=>"Time","column"=>"date"),
        'user_name'=>array("name"=>"User","column"=>"user_name"),
        'action'=>array("name"=>"Page","column"=>"page"),
        'screenshot'=>array("name"=>"Screenshot","column"=>"screenshot","jbox_image"=>true),
        'comment'=>array("name"=>"Comment","toggle_all"=>true),
        'status'=>array("name"=>"Status"),
    );

    function insert() {
        global $cfg, $db, $user1;
        $this->date = date("Y-m-d H:i:s");

        if (parent::insert()) {

            general_email(array(
                "template"=>"user_comment",
                "subject"=>"New User Comment",
                "comment"=>nl2br($this->comment),
                "user_name"=>$user1->fullname,
                "user_page"=>$this->page,
            ));

            return true;
        } else {
            return false;
        }
    }

    // Options array is currently unused.  Only required for saving drafts in parent.
    function print_add_form($options = array()) {
        global $cfg, $user1, $libhtml;

        $html = $libhtml->form_start();
        $html .= $libhtml->render_form_table_row_hidden("user_comment[page]", $this->page);
        $html .= $libhtml->render_form_table_row_hidden("user_comment[screenshot]", $this->screenshot);
        $html .= $libhtml->render_form_table_row_hidden("user_comment[user_id]", $user1->id);
        $html .= $libhtml->render_form_table_row_hidden("user_comment[status]", "New");

        $html .= open_table();
        if (isset($this->screenshot) && pathinfo($this->screenshot, PATHINFO_EXTENSION)!="") {
            $html .= $libhtml->render_form_table_row_text("user_comment[comment]", "", "Comment", "comment", array('th_width'=>"150px",'rows'=>5));
            $html .= $libhtml->render_table_row("Screenshot","<img alt=\"Screenshot\" style=\"width:100%;\" " . phpThumb_URL(array("src"=>$cfg['secure_dir'] . $this->screenshot)) . "\"/>");
        } else {
            $html .= $libhtml->render_form_table_row_text("user_comment[comment]", "", "Comment", "comment", array('th_width'=>"150px",'rows'=>15));
        }
        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("insert_user_comment", "Submit")
            )
        );

        $html .= $libhtml->form_end();
        return $html;
    }

    // Options array is currently unused.  Only required for saving drafts in parent.
    function print_edit_form($options = array()) {
        global $cfg, $user1, $db, $libhtml;
        $html = $libhtml->form_start();
        $html .= $libhtml->render_form_table_row_hidden("user_comment[page]", $this->page);
        $html .= $libhtml->render_form_table_row_hidden("user_comment[screenshot]", $this->screenshot);
        $html .= $libhtml->render_form_table_row_hidden("user_comment[user_id]", $this->user_id);
        $html .= $libhtml->render_form_table_row_hidden("user_comment[date]", $this->date);
        $html .= open_table();
        if (isset($this->screenshot) && pathinfo($this->screenshot, PATHINFO_EXTENSION)!="") {
            $html .= $libhtml->render_form_table_row_text("user_comment[comment]", $this->comment, "Comment", "comment", array('th_width'=>"150px",'rows'=>5));
            $html .= $libhtml->render_table_row("Screenshot","<img alt=\"Screenshot\" style=\"width:100%;\" " . phpThumb_URL(array("src"=>$cfg['secure_dir'] . $this->screenshot)) . "\"/>");
        } else {
            $html .= $libhtml->render_form_table_row_text("user_comment[comment]", $this->comment, "Comment", "comment", array('th_width'=>"150px",'rows'=>15));
        }
        $selection = $db->enum_select("system_user_comments","status");
        $html .= $libhtml->render_form_table_radio_selection("user_comment[status]", $this->status, "Status", "status",$selection);
        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("update_user_comment", "Update")
            )
        );

        $html .= $libhtml->form_end();
        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $item->date = zero_date($item->date,$user1->preferences->dateformat . " H:i");

        $item->comment = text_toggler($item->comment);

        $item->action = href_link(array(
            "permission"=>(isset($user1->{$item->page}) && $user1->{$item->page}),
            "url"=>$cfg["root"] . $item->page,
            "text"=>$item->page,
            "title"=>"Go to page",
            "button"=>false,
            "popup"=>false,
            "clear"=>false,
        ));

        if ($item->status=="New"){
            $item->status = '<span class="ico_grey_circle tooltip" title="New">&nbsp;</span>';
        } elseif ($item->status=="Resolved"){
            $item->status = '<span class="ico_green_circle tooltip" title="Resolved">&nbsp;</span>';
        } elseif ($item->status=="Reviewed"){
            $item->status = '<span class="ico_yellow_circle tooltip" title="Reviewed">&nbsp;</span>';
        } else {
            $item->status ='';
        }
        return;
    }
}
