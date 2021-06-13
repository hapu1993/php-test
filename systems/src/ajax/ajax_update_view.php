<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in) {

        $view_object = new View;
        $view_object->view_table = my_post('view');
        $view = $view_object->get_view();

        if ($_POST['action'] == "update_visibility") {
            $view["columns"][$_POST['column']]["display"] = my_post('display');
            echo my_post('column');

        } elseif (my_post('action') == "update_position") {
            $all_columns = unserialize(my_post('all_columns'));
            foreach ($all_columns as $key => $value) {
                $name_position = explode("-", $value);
                $view["columns"][$name_position[0]]["position"] = $name_position[1];
            }

        } elseif (my_post('action') == "update_widths") {
            $all_columns = unserialize(my_post('all_columns'));
            foreach ($all_columns as $key => $value) {
                $name_width = explode("-", $value);
                $view["columns"][$name_width[0]]["width"] = $name_width[1] . "px";
            }
        }

        // Delete old view
        $view_object->remove_view();

        // Insert new view
        $view_object->view = serialize($view);
        $view_object->make_view();

    }

    $db->close();
?>
