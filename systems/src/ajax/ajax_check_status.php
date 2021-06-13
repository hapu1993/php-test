<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in) {

        $result = array();
        $selection = $db->select("s.user_id,s.access","system_session s JOIN (SELECT user_id, MAX(access) as latest FROM system_session GROUP BY user_id)  s2 ON s.user_id=s2.user_id",array("WHERE s.user_id>0 AND s.access=s2.latest", array(), array()));
        foreach ($selection as $item) {
            if ((time() - $item->access) < ini_get('session.gc_maxlifetime')) {
                $result[]= $item->user_id;
            }
        }
        echo json_encode($result);

    }

    $db->close();
