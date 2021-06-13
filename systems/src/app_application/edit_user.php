<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Edit User";

    $id = my_get("user_id");
    $object = new Application_User;

    if ($user1->id==$id){

        $html .= '<div class="no_data">You cannot edit your own user object. Edit your preferences or ask anther admin-level user to change your details.</div>';

    } else {

        $object->select($id);
        $object->set_post(my_post('user'));
        $html .= $object->print_edit_form();

    }

    $libhtml->render_form($html);
