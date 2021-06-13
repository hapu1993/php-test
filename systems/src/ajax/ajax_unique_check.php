<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in && !empty($my_post["data"]) && !empty($my_post["value"])) {

        parse_str($crypt->str_decrypt($my_post["data"]));

        // one query is for add (id is not present)
        if (empty($id)) $selection = $db->tcount($table, array("WHERE " . $field ." = ?", array($my_post["value"]), array('varchar')));
        else $selection = $db->tcount($table, array("WHERE " . $field ." = ? AND id != ? ", array($my_post["value"], $id), array('varchar', 'integer')));

        if ( $selection > 0 ) {
            if (empty($my_post["soft_unique"])) echo 'error';
            else echo $selection;
        } else echo "ok";

    }

    $db->close();
