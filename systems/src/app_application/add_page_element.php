<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";
    require_once "./classes/Page_Element.php";

    $libhtml->title = "Add New Page Element";

    $a = new Page_Element;
    $a->page_id = my_get("page_id", 0);

    $parent_page = get_clean_object($a->page_id, "Page");
    $a->page_type = $parent_page->type;
    $a->page_template = $parent_page->template;

    $a->set_post(my_post("page_element"));
    $html = $a->print_add_form();

    $libhtml->render_form($html);

?>
