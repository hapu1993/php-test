<?php
    require_once $cfg['source_root'] . "includes/utils.php";
    require_once $cfg['source_root'] . "includes/html_functions.php";
    require_once $cfg['source_root'] . "includes/session_handler.php";

    $db = DB2_Factory::factory();
    $system_log = new System_Log;
    $view_log = new View_Audit_Log;
    $system_wastebasket = new System_Wastebasket;
    $intrusion_log = new System_Intrusion;
    $crypt = new Crypt;
    $request = new Request;
    $user1 = new Auth_User;

    // Include menu structure after user (permissions)
    include_once "./includes/admin_menu.php";

    $user1->check_user_access("page");

    $libhtml = new Libhtml;
    $cache = new Cache;
    $html = '';

    // Execute any posted functions
    $request->execute_post_functions();
