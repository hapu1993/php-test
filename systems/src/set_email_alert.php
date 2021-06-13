<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    if (my_get('class')!='' && my_get('path')!='' && class_exists($class)) {

        $class = my_get('class');
        $path = my_get('path');

        $a = new $class;
        $e = new Email_Alert;

        $libhtml->title = "Set up email alert for object '$a->human_name'";
        $e->object = $class;
        $e->object_table = $a->table;
        $e->path = $path;

        if (
            $user1->{$path . "add_" . $a->object_name . ".php"}
            || $user1->{$path . "edit_" . $a->object_name . ".php"}
            || $user1->{$path . "delete_" . $a->object_name . ".php"}
            ) {
            $html .= $e->print_form();
        } else {
            $html .= '<div class="error">You have no database permissions for this object so you cannot set email alerts.</div>';
        }

    } else {
        error_log("Error: class ($class) does not exist.");
    }

    $libhtml->render_form($html);


?>
