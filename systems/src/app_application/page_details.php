<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $page = get_clean_object(my_get("page_id", 0), "Page");

    $libhtml = new Libhtml(array(
        "main_tab" =>"pages",
        "add_to_url" => "page_id=$page->id",
        "title" => array("Page Details", $page->name),
        "show_back" => true,
        "page_actions" => array(
            href_link(array(
                "permission"=>$user1->{$libhtml->path . "edit_page.php"},
                "url"=>$cfg["root"] . $libhtml->path . "edit_page.php?page_id=$page->id&tab=$libhtml->tab",
                "text"=>"Edit",
                "class"=>"blue",
                "clear"=>false,
            )),
		),
        "more_actions" => array(
            href_link(array(
                "permission"=>($user1->{$libhtml->path . "delete_page.php"} && ($page->type != "Bespoke" || (!empty($cfg["editable"])))),
                "url"=>$cfg["root"] . $libhtml->path . "delete_page.php?page_id=$page->id&tab=$libhtml->tab",
                "text"=>"Delete",
                "clear"=>false,
            ))
        )
    ));

    $object = new Page_Element;
    $add_link = href_link(array(
        "permission"=>($user1->{$libhtml->path . "add_page_element.php"} && ($page->type != "Bespoke" || (!empty($cfg["editable"])))),
        "url"=>$cfg["root"] . $libhtml->path . "add_page_element.php?page_id=$page->id&tab=$libhtml->tab",
        "text"=>"Add page element",
        "clear"=>false,
    ));

    $libhtml->more_actions[] = $add_link;

    // disable list if bespoke page does not have any editable elements
    $page_elements = $object->_list(array(
        'where'=>array("WHERE t.page_id = ?", array('page_id' => $page->id), array('integer')),
        'table_wrapper'=>false,
        'width'=>"100%",
        'pagination'=>false,
        'ajax_sort'=>($page->type != "Bespoke" || (!empty($cfg["editable"]))),
        'delete'=>($page->type != "Bespoke" || (!empty($cfg["editable"]))),
        "view_reset"=>array(
            "public"=>($page->type != "Bespoke" || (!empty($cfg["editable"]))),
            "element_type"=>($page->type != "Bespoke"),
            "column"=>($page->type != "Bespoke"),
            "reference"=>!($page->type != "Bespoke"),
        )
    ));

    if ($object->_count() == 0 && ($page->type == "Bespoke" && (empty($cfg["editable"])))) {

        $html .= '<div class="hint">This page doesn\'t have any elements that can be changed.</div>';
        $html .= section(array("title"=>"Page details"));
        $html .= $page->print_details();

    } else {

        $html .= $page->print_details();
        $html .= section(array("title"=>"Page elements", "actions" => array($add_link)));
        $html .= $page_elements;

    }

    // galleries linked to bespoke pages
    if ($page->type == "Bespoke"){

        // any galleries for this page?
        $linked_galleries = $db->select("id, title", "cms_galleries", array("WHERE page_id = ?", array($page->id), array("integer")), array("order_by"=>"ORDER BY title ASC"));

        if (!empty($linked_galleries)) {

            foreach($linked_galleries as $linked_gallery){

                $object = new Gallery_Page;

				$html .= section(array("collapsible"=>true, "title"=>"Gallery: " . $linked_gallery->title, "actions" => array(href_link(array(
                    "permission"=>$user1->{$libhtml->path . "add_gallery_page.php"},
                    "url"=>$cfg["root"] . $libhtml->path . "add_gallery_page.php?gallery_id=$linked_gallery->id",
                    "text"=>"Add gallery image",
                    "clear"=>false,
                )))));

                $html .= $object->_list(array(
                    'where'=>array("WHERE gallery_id = ?", array($linked_gallery->id), array("integer")),
                    'width'=>"100%",
                    'table_wrapper'=>false,
                    'ajax_sort'=>true,
                    'pagination'=>false,
                ));

            }

        }

    } else {

        $libhtml->more_actions[] = href_link(array(
            "permission"=>$user1->{$libhtml->path . "add_promo_box_link.php"},
            "url"=>$cfg["root"] . $libhtml->path . "add_promo_box_link.php?page_id=$page->id",
            "text"=>"Add promo box",
            "clear"=>false,
            "show_fields"=>array("promo_box_id"),
        ));

    }

    // standard pages always have promo boxes, it depends if bespoke page can have promos
    if ($page->type == "Standard" || ($page->type == "Bespoke" && !$page->hide_promos)){

        $add_promo = href_link(array(
            "permission"=>$user1->{$libhtml->path . "add_promo_box_link.php"},
            "url"=>$cfg["root"] . $libhtml->path . "add_promo_box_link.php?page_id=$page->id",
            "text"=>"Add promo box",
            "clear"=>false,
            "show_fields"=>array("promo_box_id"),
        ));

    }

    $libhtml->render($html);
