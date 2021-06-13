<?php
    require_once $cfg['source_root'] . "includes/utils.php";
    require_once $cfg['source_root'] . "includes/html_functions.php";
    require_once $cfg['source_root'] . "includes/session_handler.php";

    $db = DB2_Factory::factory();
    $system_log = new System_Log;
    $system_wastebasket = new System_Wastebasket;
    $intrusion_log = new System_Intrusion;
    $crypt = new Crypt;
    $request = new Request;
    $user1 = new Auth_User;
    $libhtml = new Libhtml;

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === "XMLHttpRequest" && !$user1->logged_in) {
        if (!empty($_GET["term"])) echo json_encode(array("expired_session"));
        else echo "expired_session";
        exit;

    } else if (!$user1->logged_in) {
        echo "<script>window.top.location.href = '".$cfg['root']."?expired_session';</script>";
        exit;

    }

    $html = "";
