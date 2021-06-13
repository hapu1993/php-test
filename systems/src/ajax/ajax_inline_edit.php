<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $vars = unserialize($crypt->str_decrypt(my_post("id")));
    list(
            $id,
            $table,
            $value_field,
            $id_field,
            $type_field
    ) = $vars;

    $value = my_post("value");

    $id= $vars[0];
    $table = $vars[1];
    $value_field = $vars[2];
    $id_field = $vars[3];
    $type_field = $vars[4];

    // TODO: missing where and table types.
    $db->update(
            $table,
            array($value_field=>$value),
            array(
                    "WHERE $id_field = ?",
                    array($id_field => $id),
                    array($type_field)
            )
    );

    $system_log->insert(array(
        'time' => date("Y-m-d H:i:s"),
        'user_id' => $user1->id,
        'object' => $table,
        'action' => "Update",
        'object_id' => $id,
        'comment' => "field $value_field to $value",
    ));

    echo $value;

}

$db->close();
?>
