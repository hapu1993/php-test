<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Import Users";

    $object = new Application_User;

    $object->set_post(my_post('user'));
    $html = $object->print_import_form();

    $libhtml->render_form($html);
