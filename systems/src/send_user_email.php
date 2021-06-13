<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    if ($user1->logged_in) {
        $libhtml->title = "Email system users";
        $html = $user1->print_send_email_form();
        $libhtml->render_form($html);
    }
