<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Add User";

    $u = new User;

    $u->fullname = my_get("fullname");
    $u->email = my_get("email");
    $u->person_id = my_get("person_id");

    $u->set_post(my_post('user'));
    $html .= $u->print_add_form();

    $libhtml->render_form($html);
