<?php
// minor JS ==> to ==> $_SESSION functions
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    if ($my_post["action"] == "collapse_form" || $my_post["action"] == "expand_form") {
        if (empty($_SESSION["config"]["hidden_filters"])) $_SESSION["config"]["hidden_filters"] = new StdClass;

        if ($my_post["action"] == "collapse_form") {
            $current_page = explode("/", $_SERVER["HTTP_REFERER"], -1);
            $page = str_replace($cfg["root"], "", implode("/", $current_page)) . ".php";
            $_SESSION["config"]["hidden_filters"]->$page = $page;

        } else {
            $current_page = explode("/", $_SERVER["HTTP_REFERER"], -1);
            $page = str_replace($cfg["root"], "", implode("/", $current_page)) . ".php";
            if (!empty($_SESSION["config"]["hidden_filters"]->$page)) unset($_SESSION["config"]["hidden_filters"]->$page);
        }

        // and update the user preferences in db
        $preferences = new StdClass;
        $select = $db->select_value("preferences", "system_users", array("WHERE id = ?", array($user1->id), array('integer')));
        if (!empty($select)) {
            $current = json_decode($select);
            if (!empty($current)) foreach($current as $key => $value) $preferences->$key = $value;
        }

        if (!empty($_SESSION["config"]["hidden_filters"])) $preferences->hidden_filters = $_SESSION["config"]["hidden_filters"];
        else $preferences->hidden_filters = new StdClass;

        $db->update("system_users", array('preferences' => json_encode($preferences)), array("WHERE id = ?", array("id" => $user1->id), array("integer")));

    }

}

$db->close();
?>
