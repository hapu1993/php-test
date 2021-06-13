<?php

    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $pages = $db->select("*","system_pages");

    foreach($pages as $page) $unique[$page->page] = $page->id;

    foreach($pages as $page) {
        if ($page->id!=$unique[$page->page]) {
            $db->delete("system_pages","WHERE id='$page->id'");
            $db->delete("system_user_group_permissions","WHERE page_id=(SELECT p.id FROM system_pages p WHERE p.page='$page->id')");
        }
    }

    dump_var($pages);

    $db->close();

?>
