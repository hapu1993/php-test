<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    foreach ($links as $link) {
        $page = get_path() . $link[0];
        if ((isset($user1->$page) && ($user1->$page))) {
            header("Location: " . encrypt_url($cfg["root"].$page));
            exit;
        }
    }

    header("Location: ..");
?>
