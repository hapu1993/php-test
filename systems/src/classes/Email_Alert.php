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
class Email_Alert extends Object {

    public $table = "system_user_alerts";
    public $left_join = "
        LEFT JOIN system_users u ON u.id=t.user_id
    ";
    public $other_selects = "
        ,u.fullname as user_name
    ";
    public $view_array = array(
        'user_name'=>array("name"=>"User","column"=>"user_name"),
        // 'user_group'=>array("name"=>"Levels"),
        'object'=>array("name"=>"Object","column"=>"object"),
        'action'=>array("name"=>"Action","column"=>"action"),
    );
    public $object_pk = "id";
    public $orderby = "object ASC, action";
    public $dir = "ASC";
    public $actions = array("add","edit","delete");

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $item->object = str_replace("_"," ",ucfirst($item->object));
        $item->action = str_replace("_"," ",ucfirst($item->action));

        $item->user_name = href_link(array(
                "permission"=>$user1->{"user_details.php"},
                "url"=>$cfg['root'] . "user_details.php?user_id=$item->user_id",
                "text"=>$item->user_name,
                "tooltip"=>"User Details",
                "button"=>false,
                "popup"=>false
        ));

        // if (!empty($item->user_group)) {

            // $level = explode(",", $item->user_group);
            // $all_levels = $db->select("id,name","system_user_group", array());

            // $levels = array();
            // foreach ($all_levels as $value) $levels[$value->id] = $value->name;

            // $names = array();
            // foreach ($level as $value) {
                // $value = trim($value);
                // if(isset($levels[$value])) $names[$value] = $levels[$value];
            // }

            // $names = implode(", ",$names);

            // $item->user_group = $names;
        // }

        return;
    }

    function print_form(){
        global $libhtml, $db, $cfg, $user1;

        // existing object alerts
        $current_alerts = $db->select(
            "action",
            "system_user_alerts",
            array(
                "WHERE object_table = ? AND user_id = ?",
                array(
                        'object_table' => $this->object_table,
                        'user_id' => $user1->id
                ),
                array(
                        'varchar',
                        'integer'
                )
            )
        );

        $formatted_current_alerts = array();
        if (!empty($current_alerts)){
            foreach($current_alerts as $current_alert){
                $formatted_current_alerts[] = $current_alert->action;
            }
        }

        $html = $libhtml->form_start();
        $html .= $libhtml->render_form_table_row_hidden("email_alert[user_id]",$user1->id);
        $html .= $libhtml->render_form_table_row_hidden("email_alert[object]",$this->object);
        $html .= $libhtml->render_form_table_row_hidden("email_alert[object_table]",$this->object_table);
        $html .= open_table();

        foreach($this->actions as $action) {
            if ($user1->{$this->path . $action . "_" . strtolower($this->object) . ".php"}) {
                $html .= $libhtml->render_form_table_row_checkbox("email_alert[$action]",in_array($action, $formatted_current_alerts), ucfirst($action), $action);
            }
        }

        $html .= close_table();

        $html .= $libhtml->render_actions(array(
            $libhtml->render_button("set_alerts", "Set Alerts"),
        ));

        $html .= $libhtml->form_end();
        return $html;
    }

    function set_alerts(){
        global $db, $cfg, $user1;

        // existing object alerts
        $current_alerts = $db->select(
            "action",
            "system_user_alerts",
            array(
                "WHERE object_table = ? AND user_id = ?",
                array(
                    'object_table' => $this->object_table,
                    'user_id' => $user1->id
                ),
                array(
                    'varchar',
                    'integer'
                )
            )
        );

        $formatted_current_alerts = array();
        if (!empty($current_alerts)){
            foreach($current_alerts as $current_alert){
                $formatted_current_alerts[] = $current_alert->action;
            }
        }

        foreach($this->actions as $action) {
            $this->action = $action;

            if (isset($this->$action) && $this->$action){
                if (!in_array($action, $formatted_current_alerts)) {
                    $feedback = parent::insert();
                    // if (!empty($feedback)) return $feedback;
                    // $this->id = null;
                }
            } else {
                $db->delete(
                    "system_user_alerts",
                    array(
                        "WHERE user_id = ? AND object_table = ? AND action = ?",
                        array(
                            'user_id' => $user1->id,
                            'object_table' => $this->object_table,
                            'action' => $action
                        ),
                        array(
                            'integer',
                            'varchar',
                            'varchar'
                        )
                    )
                );
            }
        }

        $_SESSION["feedback"] = g_feedback("fa-paper-plane", "Set email alerts was successful");
        return false;

    }
}
