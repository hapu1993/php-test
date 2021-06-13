<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in && my_post('action') == "update") {
        $my_apps_ids = json_decode(my_post('excupdate'));
        $all_apps = $db->select("id, name, path, image, tooltip","system_apps", array("WHERE active=1",array(),array()));

        $error = false;
        $temp_arr = array();
        foreach ($all_apps as $app) $temp_arr[$app->id] = $app;
        foreach($my_apps_ids as $id) if ($id != 99 && (empty($temp_arr[$id]) || !($user1->{$temp_arr[$id]->path . "index.php"}))) $error = true;

        if (!$error) {

            $user1->preferences->apps = $my_apps_ids;

            // TODO: add table types.
            $db->update("system_users", array('preferences'=>json_encode($user1->preferences)), array("WHERE id = ?", array('id' => $user1->id), array('integer')));

            // and update my current session
            $_SESSION['apps'] = array();
            foreach ($my_apps_ids as $app_id) {
                if ($app_id == 99) $_SESSION['apps'][99] = "logout";
                else $_SESSION['apps'][$app_id] = $temp_arr[$app_id];
            }

            // build a full menu structure for each app (used in the apps dropdown)
            foreach ($_SESSION['apps'] as $key => $value){
                if ($key != 99) { // exclude logout "app"
                    $_SESSION['apps'][$key]->menu = array();

                    // check permissions for each link
                    include $cfg["source_root"] . $value->path . "includes/admin_menu.php";
                    foreach ($links as $page) {
                        if ( $user1->{$value->path . $page[0]}) $_SESSION['apps'][$key]->menu[$page[1]] = $value->path . $page[0];
                    }

                }
            }

        }
    }

    $db->close();
?>
