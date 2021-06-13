<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $page = my_get("page");
    $a = new Comment();
    $selection = $db->select_value("id","system_pages", array("WHERE page = ?", array('page' => $page), array('varchar')));

    if (!empty($selection)) {

        $a->select($selection);

        $path_info = pathinfo($page,PATHINFO_DIRNAME);
        $filename = pathinfo($page,PATHINFO_FILENAME);

        $path = ($path_info=='.') ? '' : $path_info.'/';

        $app_name = $db->select_value('name','system_apps',array('WHERE path=?',array($path),array('varchar')));

        $libhtml->title = "Add/Edit comment for page '".$filename."' in '".$app_name."' application";

        $html .= $a->print_edit_form();
        $libhtml->render_form($html);

    }
