<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Update Preferences";

    $a = new User_Preference;
    $html .= $a->print_update_form();
    $libhtml->render_form($html);
