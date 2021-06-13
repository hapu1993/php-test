<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Edit User";

    $id = my_get("user_id");
    $u = new User;

    if ($user1->id==$id){

        $html .= '<div class="no_data">You cannot edit your own user object. Go to '.
          href_link(array(
                  "permission"=>$user1->{"preferences.php"},
                  "url"=>$cfg['root'] . "preferences.php?tab=password",
                  "text"=>"&nbsp;User Preferences page&nbsp;",
                  "button"=>false,
                  'popup'=> false,
                  "clear"=>false,
                  'float'=>"",
          )).' or ask anther admin-level user to change your details.</div>';

        $html .= $libhtml->render_submit_button("update_user", "Update",array('show_action'=>false));

    } else {

        $u->select($id);
        $u->set_post(my_post('user'));
        $html .= $u->print_edit_form();

    }

    $libhtml->render_form($html);
