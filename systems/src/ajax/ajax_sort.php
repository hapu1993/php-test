<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in) {

        if (my_post('action') == "update_row_order") {
            $i = 1;

            $all_rows = json_decode(my_post('excupdate'));
            foreach($all_rows as $row) {
                $id_and_table = explode(",", $crypt->str_decrypt($row));
                $db->update(trim($id_and_table[1]), array('sort_order'=>$i), array("WHERE id = ?", array('id' =>trim($id_and_table[0])), array('integer')));
                $i++;
            }
        }

    }

    $db->close();
?>
