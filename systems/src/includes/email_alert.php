<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in){

    $path = explode("/", str_replace($cfg['root'], "", $_SERVER['HTTP_REFERER']));
    $class = $my_get["class"];
    $app_path = $path[0] . "/";

    if (!empty($class) && class_exists($class)) {

        $a = new $class;
        $e = new Email_Alert;

        $title = "Set up email alert for object '$a->human_name'";
        $e->object = $class;
        $e->object_table = $a->table;
        $e->path = $app_path;

        if (
            $user1->{$app_path . "add_" . $a->object_name . ".php"}
            || $user1->{$app_path . "edit_" . $a->object_name . ".php"}
            || $user1->{$app_path . "delete_" . $a->object_name . ".php"}
            ) {
            $html .= $e->print_form();
        } else {
            $html .= "<div class=\"error\">You have no database permissions for this object so you cannot set email alerts.</div>\n";
        }

        $libhtml->render_form($html);
    } else {
        error_log("Error: class ($class) does not exist.");
    }

}

$db->close();

?>
