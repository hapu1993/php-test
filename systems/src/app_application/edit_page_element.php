<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";
    require_once "./classes/Page_Element.php";

    $libhtml->title = "Edit page element";
    $a = get_clean_object(my_get("page_element_id"), "Page_Element");

    $sub_page_elements = $db->select("id, sub_element_id, value, lib_value", "cms_page_sub_element_values", array("WHERE page_element_id = ? ", array($a->id), array("integer")), array("order_by"=>"ORDER BY id ASC"));
    $a->sub_page_elements = array();
    foreach($sub_page_elements as $sub_page_element) {
        $a->sub_page_elements[] = (array) $sub_page_element;
    }

    $html = $a->print_edit_form();
    $libhtml->render_form($html);

?>
