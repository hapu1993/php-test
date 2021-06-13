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
class User_Group extends Object{

    public $table = "system_user_groups";
    public $left_join = "";
    public $other_selects = "
            ,(SELECT COUNT(*) FROM system_user_group_links l LEFT JOIN system_users u ON u.id=l.user_id WHERE l.group_id=t.id) as total_users
            ,(SELECT COUNT(*) FROM system_user_group_links l LEFT JOIN system_users u ON u.id=l.user_id WHERE l.group_id=t.id AND u.active=1) as active_users
            ";
    public $orderby = "t.name";
    public $dir = "ASC";
    public $view_array = array(
        'name'=>array("name"=>"Group Name"),
        'active_users'=>array("name"=>"Active Users"),
        'total_users'=>array("name"=>"Total Users"),
        'comment'=>array("name"=>"Description",'toggle_all'=>true),
    );

    function delete() {
        global $cfg, $db;
        $db->delete(
                "system_user_group_links",
                array(
                        "WHERE group_id=?",
                        array('group_id' => $this->id),
                        array('integer')
                )
        );
        return parent::delete();
    }

    function print_form() {
        global $cfg, $db, $libhtml;
        $html = $libhtml->form_start();
        $html .= open_table();

        $html .= $libhtml->render_form_table_row("user_group[name]", $this->name,"Name","name", array('required'=>true));
        $html .= $libhtml->render_form_table_row_text("user_group[comment]", $this->comment,"Description","comment", array());

        if (empty($this->id)){

            if (!isset($this->set_permissions)) $this->set_permissions=null;

            $html .= $libhtml->render_form_table_row_checkbox("user_group[set_permissions]", $this->set_permissions,"Set basic user permissions?","set_permissions", array('self_submit'=>true));

            if (!empty($this->set_permissions)){

                $apps = $db->select("id,name","system_apps",array(),array('order_by'=>"ORDER BY name ASC"));

                foreach($apps as $app){

                    if (!isset($this->apps[$app->id]['pages'])) $this->apps[$app->id]['pages']=null;
                    if (!isset($this->apps[$app->id]['add'])) $this->apps[$app->id]['add']=null;
                    if (!isset($this->apps[$app->id]['edit'])) $this->apps[$app->id]['edit']=null;
                    if (!isset($this->apps[$app->id]['delete'])) $this->apps[$app->id]['delete']=null;
                    if (!isset($this->apps[$app->id]['other'])) $this->apps[$app->id]['other']=null;

                    $html .= table_separator('',$app->name);

                    $html .= $libhtml->render_form_table_row_checkbox("user_group[apps][$app->id][pages]", $this->apps[$app->id]['pages'],"Page access","apps[$app->id][pages]", array('self_submit'=>true));

                    if (!empty($this->apps[$app->id]['pages'])){

                        $html .= $libhtml->render_form_table_row_checkbox("user_group[apps][$app->id][add]", $this->apps[$app->id]['add'],"Add actions","apps[$app->id][add]");
                        $html .= $libhtml->render_form_table_row_checkbox("user_group[apps][$app->id][edit]", $this->apps[$app->id]['edit'],"Edit actions","apps[$app->id][edit]");
                        $html .= $libhtml->render_form_table_row_checkbox("user_group[apps][$app->id][delete]", $this->apps[$app->id]['delete'],"Delete actions","apps[$app->id][delete]");
                        $html .= $libhtml->render_form_table_row_checkbox("user_group[apps][$app->id][other]", $this->apps[$app->id]['other'],"Other actions","apps[$app->id][other]");

                    }
                }

            }

            $html .= close_table();

        }

        return $html;
    }

    function print_delete_form() {
        global $cfg, $db, $libhtml;

        $html = '<div class="error">Are you sure you want to delete this object?</div>';

        $html .= $libhtml->form_start();

        $html .= open_table("","","action_form details");
        $html .= $libhtml->render_table_row("Name",$this->name);
        $html .= $libhtml->render_table_row("Description",$this->comment);

        $html .= table_separator("","This will deactivate User Group for the following users and all permissions");

        $selection = $db->select("t.id,t.fullname","system_users t",array("WHERE l.group_id=?", array('group_id' => $this->id), array('integer')), array('joins' => "LEFT JOIN system_user_group_links l ON l.user_id=t.id"));
        foreach ($selection as $item) $html .= $libhtml->render_table_row("Name",$item->fullname);

        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("delete_user_group", "Delete")
            )
        );

        $html .= $libhtml->form_end();

        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $item->name = href_link(array(
                "permission"=>$user1->{"users.php"},
                "url"=>$cfg['root'] . "users.php?tab=pages&subtab=page_permissions&user_group_id=".$item->id,
                "text"=>$item->name,
                "tooltip"=>"Group permission details",
                "button"=>false,
                "popup"=>false,
        ));

        $item->active_users = href_link(array(
                "permission"=>$user1->{"users.php"} && !empty($item->id),
                "url"=>$cfg['root'] . "users.php?tab=all&active_search=Active&user_group_id=".$item->id,
                "text"=>$item->active_users,
                "tooltip"=>"Active group users",
                "button"=>false,
                "popup"=>false,
                "tooltip"=>'Active Users',
                // 'expand_method'=>'show_active_users',
                // "expand_details"=>"User_Group",
        ));

        $item->total_users = href_link(array(
                "permission"=>$user1->{"users.php"} && !empty($item->id),
                "url"=>$cfg['root'] . "users.php?tab=all&user_group_id=".$item->id,
                "text"=>$item->total_users,
                "tooltip"=>"All group users",
                "button"=>false,
                "popup"=>false,
                // 'expand_method'=>'show_all_users',
                // "expand_details"=>"User_Group",
        ));

        $item->comment = text_toggler($item->comment);
        return;
    }

    function show_users($where = array()){
        global $user1;
        return $user1->_list(array(
                'width'=>'100%',
                'pagination'=>false,
                'table_wrapper'=>false,
                'view_reset'=>array(
                        'status'=>false,
                ),
                'where'=>$where
        ));
    }

    function show_active_users(){
        return $this->show_users(array(
                    'WHERE t.active=1 AND t.id IN (SELECT user_id FROM system_user_group_links WHERE group_id=?)',
                    array($this->id),
                    array('integer'),
        ));

    }

    function show_inactive_users(){
        return $this->show_users(array(
                'WHERE (t.active=0 OR t.active IS NULL) AND t.id IN (SELECT user_id FROM system_user_group_links WHERE group_id=?)',
                array($this->id),
                array('integer'),
        ));

    }

    function show_all_users(){
        return $this->show_users(array(
                'WHERE t.id IN (SELECT user_id FROM system_user_group_links WHERE group_id=?)',
                array($this->id),
                array('integer'),
        ));

    }



}
