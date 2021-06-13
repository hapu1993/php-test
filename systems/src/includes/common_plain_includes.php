<?php
    require_once $cfg['source_root'] . "includes/utils.php";
    require_once $cfg['source_root'] . "includes/html_functions.php";
    require_once $cfg['source_root'] . "includes/session_handler.php";

    $_SESSION['feedback'] = '';

    $db = DB2_Factory::factory();
    $system_log = new System_Log;
    $system_wastebasket = new System_Wastebasket;
    $intrusion_log = new System_Intrusion;
    $crypt = new Crypt;
    $request = new Request;
    $libhtml = new Libhtml;
    $user1 = new Auth_User;
    $html = '';

    $ldap_settings = new \Riskpoint\Auth\LDAP\Setting();
    if ($ldap_settings->isEnabled()) {
        $_SESSION['LDAP_Enabled'] = true;
    }
    unset($ldap_settings);
