<?php

    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $db->delete("system_user_group_links","WHERE id>0");

    $users = $db->select("*","system_users","WHERE id>=0");

    foreach ($users as $user){
        if (!is_null($user->user_groups)) {
            $user_groups = explode(",",$user->user_groups);
            foreach($user_groups as $link){
                $db->insert("system_user_group_links",array('user_id'=>$user->id,'group_id'=>$link));
            }
        }
    }

    $links = $db->select("l.*, u.fullname, g.name","system_user_group_links l LEFT JOIN system_users u ON u.id=l.user_id LEFT JOIN system_user_groups g on g.id=l.group_id");
    dump_var($links);

    $db->close();

?>
