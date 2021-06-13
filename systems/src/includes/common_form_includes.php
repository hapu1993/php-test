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

    $user1->check_user_access("popup");
    $libhtml = new Libhtml;
    $html = "";

       if (
               isset($_SERVER['HTTP_REFERER'])
               && $_SERVER['HTTP_REFERER']!=$cfg['root']
               && $_SERVER['HTTP_REFERER']!=$cfg['root']."?expired_session"
           ) {

        $comment = $db->select_value(
			"comment",
			"system_pages",
			array(
				"WHERE page = ?",
				array('page' => str_replace($cfg['source_root'],"",$_SERVER['SCRIPT_FILENAME'])),
				array('varchar')
			)
		);

    } else {
        if (!isset($_SERVER['HTTP_REFERER'])) $_SESSION['feedback'] .= g_feedback("error","You cannot access a popup page directly.");
        $l = $user1->universal_redirect();
        //If there is a history
        if (isset($_SESSION['history'])){
            $e = end($_SESSION['history']);
            $l = $cfg['root'] . $e['url'];
        //else if there is a homepage
        } elseif (isset($user1->preferences->landpage)) {
            $l = $cfg['root'] . $user1->preferences->landpage;
        }
        header("Location: $l");
        exit;
    }

    // chained forms, put passed vars in the session, when going to the next page (jlink forward)
    // must be here, at the beggining of the page, can't be in form_page_start, since it comes after standard_form
    if (!empty($my_post["pass_vars"]) && !empty($my_post["prev_page"]) && !empty($my_post["jbox_id"])){
        //    try to get only object's variables, ignore other vars (token, self submit, etc)
        $tmp_array = array();
        parse_str($my_post["pass_vars"], $tmp_array); // unserialize
        foreach($tmp_array as $key => $value) { // and put them in a page related session $page => $vars
            if (is_array($value)) $_SESSION["popups"][$my_post["jbox_id"]][$my_post["prev_page"]]["vars"] = $value;
        }
    }

    // Execute any posted functions, chained forms
    if (!empty($my_post["through_page"])) {
        $request->execute_post_functions();
    }
