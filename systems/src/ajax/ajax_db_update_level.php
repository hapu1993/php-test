<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $vars = unserialize($crypt->str_decrypt(my_post("id")));
    list(
            $page_id,
            $group_id,
            $user_id,
            $value,
    ) = $vars;

    $page = $db->select_value("page","system_pages",array("WHERE id = ?", array('id' => $page_id), array('integer')));
    $group = $db->select_value("name","system_user_groups",array("WHERE id = ?", array('id' => $group_id), array('integer')));
    $change_text=$new_id=$new_image='';

    if ($value=="0") {

        $new_image = "tick ico_circle_toggle_on";
        $new_id = $crypt->str_encrypt(serialize(array($page_id,$group_id,$user1->id,1)));
        $change_text = "Allow access";
        //Check if the link exists, just in case, to prevent multiple entries
        $x = $db->select("id", "system_user_group_permissions",array("WHERE page_id = ? AND group_id = ?", array('page_id' =>$page_id, 'group_id' => $group_id), array('integer', 'integer')));
        if (count($x)==0) $db->insert("system_user_group_permissions", array('page_id'=>$page_id, 'group_id'=>$group_id));
        //error_log("Permission granted for page id $page_id, group id $group_id");

    } elseif ($value=="1") {

        $new_image = "tick ico_circle_toggle_off";
        $new_id = $crypt->str_encrypt(serialize(array($page_id,$group_id,$user1->id,0)));
        $change_text = "Deny access";
        $db->delete("system_user_group_permissions",array("WHERE page_id = ? AND group_id = ?", array('page_id' => $page_id, 'group_id' => $group_id), array('integer', 'integer')));
        //error_log("Permission denied for page id $page_id, group id $group_id");

    }

    $system_log->insert(array(
        'time' => date("Y-m-d H:i:s"),
        'user_id' => $user1->id,
        'object' => "User Group - Page Permission",
        'action' => $change_text ,
        'comment' => "Page: $page; Group: $group",
    ));

    echo json_encode(array('styleclass'=>$new_image, 'new_id'=>$new_id));

}

$db->close();
?>
