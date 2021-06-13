<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $vars = unserialize($crypt->str_decrypt(my_post("id")));
    list(
            $id,
            $table,
            $field,
    ) = $vars;

    if (strpos($_POST["type"], "_toggle_on")) {
        // TODO: add table types.
        $db->update($table, array($field=>0), array("WHERE id = ?", array('id' => $id), array('integer')));
        $action = "Toggle \"$field\" to 0";
        echo str_replace("_toggle_on", "_toggle_off", my_post("type"));
    } else {
        // TODO: add table types.
        $db->update($table, array($field=>1), array("WHERE id = ?", array('id' => $id), array('integer')));
        $action = "Toggle \"$field\" to 1";
        echo str_replace("_toggle_off", "_toggle_on", my_post("type"));
    }

    $system_log->insert(array(
        'time' => date("Y-m-d H:i:s"),
        'user_id' => $user1->id,
        'object' => $table,
        'action' => $action,
        'object_id' => $id,
        'comment' => "",
    ));

}

$db->close();
