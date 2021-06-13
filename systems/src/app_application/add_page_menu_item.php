<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";
    require_once "./classes/Page_Menu_Item.php";

    $libhtml->title = "Add page";

    $a = new Page_Menu_Item;
    $a->page_id = my_get("page_id", 0);
    $a->page_name = my_get("page_name", "");
    $a->set_post(my_post("page_menu_item"));
    $html = $a->print_add_form();

    $libhtml->render_form($html);

?>
