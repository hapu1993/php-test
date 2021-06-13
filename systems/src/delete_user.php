<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Delete User";
    $id = my_get("user_id");

    if ($user1->id==$id){

        $html .= '<div class="hint">You cannot delete your own user object.</div>';
        $html .= $libhtml->render_submit_button("delete_user", "Delete",array('show_action'=>false));
        $libhtml->render_form($html);

    } else {

        $u = new User;
        $u->select($id);

        $html .= '<div class="hint">Deleting this user will delete all their logs. You may not be able to audit fully all database changes.</div>';
        $html .= $u->print_delete_form();
        $libhtml->render_form($html);

    }
