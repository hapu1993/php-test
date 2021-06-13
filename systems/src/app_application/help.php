<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "title" => "Menu",
        "tab" => my_get("tab", "pages"),
        "page_actions"=> array(href_link(array(
            "permission"=>$user1->{$libhtml->path . "add_page.php"},
            "url"=>$cfg["root"] . $libhtml->path . "add_page.php",
            "text"=>"Add New Page",
            "class"=>"blue",
            "clear"=>false,
        ))),
    ));

    $page_object = new Page;

    if ($libhtml->tab == "pages") {

        $main_menu = $db->select("t.*, p.name as page_name, p.id as page_id, p.seo_title, p.type as page_type, p.active", "cms_main_menu_items t LEFT JOIN cms_pages p ON p.id = t.page_id", array("", array(), array()), array("order_by"=>"ORDER BY t.sort_order ASC"));
        $sub_menu = $db->select("t.*, p.name as page_name, p.id as page_id, p.seo_title, p.type as page_type, p.active", "cms_submenu_items t LEFT JOIN cms_pages p ON p.id = t.page_id", array("", array(), array()), array("order_by"=>"ORDER BY t.menu_item_id ASC, t.sort_order ASC"));
        $subsub_menu = $db->select("t.*, p.name as page_name, p.id as page_id, p.seo_title, p.type as page_type, p.active", "cms_sub_submenu_items t LEFT JOIN cms_pages p ON p.id = t.page_id", array("", array(), array()), array("order_by"=>"ORDER BY t.submenu_item_id ASC, t.sort_order ASC"));

        if (!empty($main_menu)) {
            $html .= section(array("title"=>'Linked pages <span class="tooltip ico_binfo" title="If active, these pages will appear in menu structure"><i class="fa fa-info"></i></span>'));
            $html .= '<div class="table_wrap clearfix">
                <div class="table_parent">
                    <table class="list_table summary tablesorter float_header disable_cn mntbl">
                        <thead>
                            <tr class="header">
                                <th>
                                    <div class="inner">
                                        <span class="only_t cnme">Name</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>';

                foreach($main_menu as $main_menu_item) {
                    $bespoke = ($main_menu_item->page_type == "Bespoke") ? '<span class="ico_bpage tooltip" title="Bespoke pages have predefined set of elements which can be changed.">B</span>' : '<span class="ico_spage tooltip" title="Standard pages are flexible and can be built using a layercake elements.">S</span>';
                    $html .= '<tr><td>
                        <div class="trw">
                            <span class="rw">
                                <span class="tcll">' . href_link(array(
                                    "permission"=>$user1->{$libhtml->path . "page_details.php"},
                                    "url"=>$cfg['root'] . $libhtml->path . "page_details.php?page_id=$main_menu_item->page_id",
                                    "text"=>$main_menu_item->page_name . $bespoke,
                                    "button"=>false,
                                    "clear"=>false,
                                    "popup"=>false
                                )) . '
                                </span>
                                <span class="tcll right faction">'.href_link(array(
                                    "permission"=>$user1->{$libhtml->path . "delete_page.php"},
                                    "url"=>$cfg['root'] . $libhtml->path . "delete_page.php?page_id=" . $main_menu_item->page_id,
                                    "text"=>'
                                        <span class="ico_delete tooltip" title="Delete">
                                            <i class="fa fa-times"></i>
                                        </span>
                                        <span class="txt">Delete</span>',
                                    "class"=>"action",
                                    "button"=>false,
                                    "clear"=>false
                                )).'
                                </span>
                                <span class="tcll right">'.href_link(array(
                                    "permission"=>$user1->{$libhtml->path . "edit_page.php"},
                                    "url"=>$cfg['root'] . $libhtml->path . "edit_page.php?page_id=" . $main_menu_item->page_id,
                                    "text"=>'
                                        <span class="ico_edit tooltip" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </span>
                                        <span class="txt">Edit</span>',
                                    "class"=>"action",
                                    "button"=>false,
                                    "clear"=>false
                                )).'
                                </span>
                                <span class="tcll right">'. ajax_toggle($main_menu_item->page_id, "cms_pages", "active", $user1->{$libhtml->path . "edit_page.php"}, $main_menu_item->active) .'</span>
                                <span class="tcll right">
                                    <span id="'.$crypt->str_encrypt($main_menu_item->id . ", cms_main_menu_items").'" class="ico_move move_handle">
                                        <i class="fa fa-sort"></i>
                                    </span>
                                </span>
                            </span>
                        </div>';

                    // 2nd level
                    $open_table = false;
                    foreach($sub_menu as $sub_menu_item) {
                        if ($sub_menu_item->menu_item_id == $main_menu_item->id){
                            $bespoke = ($sub_menu_item->page_type == "Bespoke") ? '<span class="ico_bpage tooltip" title="Bespoke pages have predefined set of elements which can be changed.">B</span>' : '<span class="ico_spage tooltip" title="Standard pages are flexible and can be built using a layercake elements.">S</span>';
                            if (!$open_table) $html .= '<div class="trw srw"><table>';

                            $html .= '<tr><td>
                                <div class="trw">
                                    <span class="rw">
                                        <span class="tcll scnd">'.href_link(array(
                                            "permission"=>$user1->{$libhtml->path . "page_details.php"},
                                            "url"=>$cfg['root'] . $libhtml->path . "page_details.php?page_id=$sub_menu_item->page_id",
                                            "text"=>$sub_menu_item->page_name . $bespoke,
                                            "button"=>false,
                                            "clear"=>false,
                                            "popup"=>false
                                        )).'
                                        </span>
                                        <span class="tcll right faction">'.href_link(array(
                                            "permission"=>$user1->{$libhtml->path . "delete_page.php"},
                                            "url"=>$cfg['root'] . $libhtml->path . "delete_page.php?page_id=" . $sub_menu_item->page_id,
                                            "text"=>'
                                                <span class="ico_delete tooltip" title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </span>
                                                <span class="txt">Delete</span>',
                                            "class"=>"action",
                                            "button"=>false,
                                            "clear"=>false
                                        )).'
                                        </span>
                                        <span class="tcll right">'.href_link(array(
                                            "permission"=>$user1->{$libhtml->path . "edit_page.php"},
                                            "url"=>$cfg['root'] . $libhtml->path . "edit_page.php?page_id=" . $sub_menu_item->page_id,
                                            "text"=>'
                                                <span class="ico_edit tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </span>
                                                <span class="txt">Edit</span>',
                                                "class"=>"action",
                                                "button"=>false,
                                                "clear"=>false
                                            )).'
                                        </span>
                                        <span class="tcll right">'. ajax_toggle($sub_menu_item->page_id, "cms_pages", "active", $user1->{$libhtml->path . "edit_page.php"}, $sub_menu_item->active) .'</span>
                                        <span class="tcll right">
                                            <span id="'.$crypt->str_encrypt($sub_menu_item->id . ", cms_submenu_items").'" class="ico_move move_handle">
                                                <i class="fa fa-sort"></i>
                                            </span>
                                        </span>
                                    </span>
                                </div>';

                            // 3rd level
                            $open_third_table = false;
                            foreach($subsub_menu as $subsub_menu_item) {
                                if ($subsub_menu_item->submenu_item_id == $sub_menu_item->id){
                                    $bespoke = ($subsub_menu_item->page_type == "Bespoke") ? '<span class="ico_bpage tooltip" title="Bespoke pages have predefined set of elements which can be changed.">B</span>' : '<span class="ico_spage tooltip" title="Standard pages are flexible and can be built using a layercake elements.">S</span>';
                                    if (!$open_third_table) $html .= '<div class="trw lrw"><table>';

                                    $html .= '<tr><td>
                                        <div class="trw">
                                            <span class="rw">
                                                <span class="tcll trd">'.href_link(array(
                                                    "permission"=>$user1->{$libhtml->path . "page_details.php"},
                                                    "url"=>$cfg['root'] . $libhtml->path . "page_details.php?page_id=$subsub_menu_item->page_id",
                                                    "text"=>$subsub_menu_item->page_name . $bespoke,
                                                    "button"=>false,
                                                    "clear"=>false,
                                                    "popup"=>false)).'
                                                </span>
                                                <span class="tcll right faction">'.href_link(array(
                                                    "permission"=>$user1->{$libhtml->path . "delete_page.php"},
                                                    "url"=>$cfg['root'] . $libhtml->path . "delete_page.php?page_id=" . $subsub_menu_item->page_id,
                                                    "text"=>'
                                                        <span class="ico_delete tooltip" title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </span>
                                                        <span class="txt">Delete</span>',
                                                    "class"=>"action",
                                                    "button"=>false,
                                                    "clear"=>false
                                                )).'
                                                </span>
                                                <span class="tcll right">'.href_link(array(
                                                    "permission"=>$user1->{$libhtml->path . "edit_page.php"},
                                                    "url"=>$cfg['root'] . $libhtml->path . "edit_page.php?page_id=" . $subsub_menu_item->page_id,
                                                    "text"=>'
                                                        <span class="ico_edit tooltip" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </span>
                                                        <span class="txt">Edit</span>',
                                                        "class"=>"action",
                                                        "button"=>false,
                                                        "clear"=>false
                                                )).'
                                                </span>
                                                <span class="tcll right">'. ajax_toggle($subsub_menu_item->page_id, "cms_pages", "active", $user1->{$libhtml->path . "edit_page.php"}, $subsub_menu_item->active) .'</span>
                                                <span class="tcll right">
                                                    <span id="'.$crypt->str_encrypt($subsub_menu_item->id . ", cms_sub_submenu_items").'" class="ico_move move_handle">
                                                        <i class="fa fa-sort"></i>
                                                    </span>
                                                </span>
                                            </span>
                                        </div></td></tr>';

                                    $open_third_table = true;
                                }
                            }

                            if (!$open_third_table) $html .= '</td></tr>';
                            else $html .= '</table></div></td></tr>';

                            $open_table = true;
                        }
                    }

                    if ($open_table) $html .= '</table></div>';
                    $html .= '</td></tr>';

                }

                $html .= '</tbody>
                    </table>
                </div>
            </div>';

        } else {
            $html .= section(array("title"=>'Linked pages <span class="tooltip ico_binfo" title="If active, these pages will appear in menu structure"><i class="fa fa-info"></i></span>'));
            $html .= '<div class="no_data">No items found.</div>';

        }

        // unlinked pages
        $unlinked_pages = $db->select("t.*", "cms_pages t WHERE NOT EXISTS (SELECT page_id FROM cms_main_menu_items WHERE page_id = t.id) AND NOT EXISTS (SELECT page_id FROM cms_submenu_items WHERE page_id = t.id) AND NOT EXISTS (SELECT page_id FROM cms_sub_submenu_items WHERE page_id = t.id)", array("", array(), array()), array("order_by"=>"ORDER BY name ASC"));

        if (!empty($unlinked_pages)) {
            $html .= section(array("title"=>'Unlinked pages <span class="tooltip ico_binfo" title="These pages can still be accessed if they are published, but they will not appear in menu structure"><i class="fa fa-info"></i></span>'));
            $html .= '<div class="table_wrap clearfix">
                <div class="table_parent">
                    <table class="list_table summary tablesorter float_header disable_cn mntbl">
                        <thead>
                            <tr class="header">
                                <th><div class="inner"><span class="only_t cnme">Name</span></div></th>
                            </tr>
                        </thead>
                        <tbody>';

                foreach($unlinked_pages as $page) {
                    $bespoke = ($page->type == "Bespoke") ? '<span class="ico_bpage tooltip" title="Bespoke pages have predefined set of elements which can be changed.">B</span>' : '<span class="ico_spage tooltip" title="Standard pages are flexible and can be built using a layercake elements.">S</span>';

                    // quick check if this page had been properly unlinked
                    if ($page->linked == 1) $db->update("cms_pages", array("linked"=>'0'), array("WHERE id = ?", array("linked"=>$page->id), array("integer")));

                    $html .= '<tr><td>
                        <div class="trw">
                            <span class="rw">
                                <span class="tcll">' . $bespoke . href_link(array(
                                    "permission"=>$user1->{$libhtml->path . "page_details.php"},
                                    "url"=>$cfg['root'] . $libhtml->path . "page_details.php?page_id=$page->id",
                                    "text"=>$page->name,
                                    "button"=>false,
                                    "clear"=>false,
                                    "popup"=>false
                                )) . '
                                </span>
                                <span class="tcll right faction">'.href_link(array(
                                    "permission"=>$user1->{$libhtml->path . "delete_page.php"},
                                    "url"=>$cfg['root'] . $libhtml->path . "delete_page.php?page_id=" . $page->id,
                                    "text"=>'
                                        <span class="ico_delete tooltip" title="Delete">
                                            <i class="fa fa-times"></i>
                                        </span>
                                        <span class="txt">Delete</span>',
                                    "class"=>"action",
                                    "button"=>false,
                                    "clear"=>false
                                )).'
                                </span>
                                <span class="tcll right">'.href_link(array(
                                    "permission"=>$user1->{$libhtml->path . "edit_page.php"},
                                    "url"=>$cfg['root'] . $libhtml->path . "edit_page.php?page_id=" . $page->id,
                                    "text"=>'
                                        <span class="ico_edit tooltip" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </span>
                                        <span class="txt">Edit</span>',
                                        "class"=>"action",
                                        "button"=>false,
                                        "clear"=>false
                                )).'
                                </span>
                                <span class="tcll right">'. ajax_toggle($page->id, "cms_pages", "active", $user1->{$libhtml->path . "edit_page.php"}, $page->active) .'</span>
                            </span>
                        </div>
                    </td></tr>';

                }

                $html .= '</tbody>
                    </table>
                </div>
            </div>';

        } else {

            $html .= section(array("title"=>'Unlinked pages <span class="tooltip ico_binfo" title="These pages can still be accessed if they are published, but they will not appear in menu structure"><i class="fa fa-info"></i></span>'));
            $html .= '<div class="no_data">There are no unlinked pages.</div>';

        }

    }

    $libhtml->render($html);
