<?php

	$libhtml = new Libhtml;

    $links = array(

        'users' => array("users.php","Users & Permissions","",array (
            'all' => "Users",
            'user_groups' => "User Groups",
            'ward_security_groups' => $libhtml->local_text['Ward']." Security Groups",
            'apps' => "System Apps",
            'pages' => "Group Permissions",
            'sessions' => "User Sessions",
        )),

        'logs' => array("system_log.php","System Log","",array (
            'all' => "All",
            'search' => "Search",
            'logins' => "Logins",
            'intrusions' => "Intrusion Logs",
            'failed_logins' => "Failed Logins",
            'inserts' => "Inserts",
            'updates' => "Updates",
            'deletes' => "Deletes",
            'exports' => "Table Exports",
        )),

        'wastebasket' => array("wastebasket.php","Wastebasket",""),

    );

    if (!empty($cfg['oauth_enable'])) $links['openid'] = array("openid.php","Open ID Login",'');

    if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') {

        $links ['utilities'] = array ("utilities.php","Utilities",'',array(
            'encryption' => "Encryption",
            'urls' => "URLs",
            'psswd' => "Password Generator",
            'json' => "JSON",
            'functions' => "Functions",
            'preg_match' => "Preg Match",
            'time' => "Time",
            'ids' => "IDS Test",
            'filters' => "PHP Filters",
            'pdf' => "HTML to PDF",
        ));

    }

    $links ['license'] = array("license.php","License","");
