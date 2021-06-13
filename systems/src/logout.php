<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    if ($user1->logged_in) {

        $session_id = session_id();

        /**
         * Delete cookies - the time must be in the past,
         * so just negate what you added when creating the
         * cookie.
        */

        if (Request::my_cookie('riskpoint')!="") setcookie("riskpoint", "", time()-60*60*24*100, "/");

        $system_log->insert(array(
                'time' => date("Y-m-d H:i:s") ,
                'user_id' => $user1->id ,
                'object' => 'User',
                'action' => "Logout" ,
                'object_id' => $user1->id ,
                'comment' => "")
        );

        /* Kill session variables */
        unset($_SESSION['username']);
        unset($_SESSION['password']);
        unset($_SESSION['feedback']);
        $user1->logged_in = false;

        if(!empty($cfg['owncloud_url'])){
            $ssl = isSSL();
            setcookie('oc_token','',1,'/owncloud','',$ssl,true);
            setcookie('oc_remember_login','',1,'/owncloud','',$ssl,true);
            setcookie('oc_username','',1,'/owncloud','',$ssl,true);
            setcookie('RiskpointOwnCloud','',1,'/owncloud','',$ssl,true);
        }

    }

    $session->destroy();
    unset($_SESSION);
    session_regenerate_id();

    if (!empty($user1->id)) $db->delete("system_session", array("WHERE id = ? OR user_id IS NULL", array($session_id), array('varchar')));

    if (isset($_GET['expired_session'])) {
        header("Location: ".$cfg['root']."?expired_session");
        exit;
    } else {
        header("Location: ".$cfg['root']);
        exit;
    }
