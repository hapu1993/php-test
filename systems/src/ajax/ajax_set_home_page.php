<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $name = end($_SESSION['history']);
    $h = str_replace($cfg['root'],"",$_SERVER['HTTP_REFERER']);
    $preferences = new StdClass;
    $select = $db->select_value("preferences", "system_users", array("WHERE id = ?", array('id' => $user1->id), array('integer')));
    if (!empty($select)) {
        $current = json_decode($select);
        if (!empty($current)) foreach($current as $key => $value) $preferences->$key=$value;
    }
    $preferences->landpage = $h;
    $preferences->landpage_name = str_replace("<span class=\"l4\">","<span class=\"l4\">HOME - ", $name['page']);

    $user1->preferences->landpage = $h;
    $user1->preferences->landpage_name = str_replace("<span class=\"l4\">","<span class=\"l4\">HOME - ", $name['page']);

    // TODO: add table types.
    $db->update("system_users", array('preferences' => json_encode($preferences)),array("WHERE id = ?", array('id' => $user1->id), array('integer')));

    echo  '
            <a class="tooltip" href="' . $_SERVER['HTTP_REFERER'] . ' title="Go to your Home page">'.
                str_replace('<span class="l4">','<span class="l4">HOME - ', $name['page']).
            '</a>';

}

$db->close();
