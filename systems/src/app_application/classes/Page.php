<?php

    class Page extends Object {

        public $table = "cms_pages";
        public $left_join = "";
        public $other_selects = "
            , (SELECT menu_item_id FROM cms_submenu_items WHERE page_id = t.id ) as menu_item_id
            , (SELECT submenu_item_id FROM cms_sub_submenu_items WHERE page_id = t.id) as submenu_item_id
        ";
        public $orderby = "t.name";
        public $dir = "ASC";
        public $view_array = array(
            'type'=>array("name"=>"Type", "column"=>"type", "width"=>"90px", "filter"=>array("value"=>"t.type", "column"=>"t.type", "table"=>"cms_pages")),
            'name'=>array("name"=>"Name", "column"=>"name"),
            'active'=>array("name"=>"Public", "column"=>"active", "width"=>"60px"),
            'edit'=>array("name"=>"Edit", "show_name"=>false, "width"=>"16px"),
            'delete'=>array("name"=>"Delete", "show_name"=>false, "width"=>"16px"),
        );

        function insert(){
            global $cfg, $db, $my_post;

            if (empty($this->type)) $this->type = "Standard";

			$this->seo_title = make_seo_title($this->name);

			parent::insert();

			$this->update_link();

		}

        function update(array $additional = array()){
            global $cfg, $db, $my_post;

			if (empty($this->type)) $this->type = "Standard";

			$additional['seo_title'] = make_seo_title($this->name);

			parent::update($additional);

			$this->update_link();
        }

		function show($class = "action_form"){
			global $libhtml;

			$html = open_table("100%","",$class);
			$html .= $libhtml->render_table_row("Title", $this->title);
			$html  .= close_table();

			return $html;
		}

        function update_link(){
            global $cfg, $db, $my_post;

            // unlink page if it was ever linked
            if (empty($this->linked)) {

                $db->delete("cms_main_menu_items", array("WHERE page_id = ?", array("page_id" => $this->id), array("integer")));
                $db->delete("cms_submenu_items", array("WHERE page_id = ?", array("page_id" => $this->id), array("integer")));
                $db->delete("cms_sub_submenu_items", array("WHERE page_id = ?", array("page_id" => $this->id), array("integer")));

            // or insert / update / delete
            } else {

                // deletes
                if (!empty($my_post["menu_item_id"])) $db->delete("cms_main_menu_items", array("WHERE page_id = ?", array($this->id), array("integer")));
                if (!empty($my_post["submenu_item_id"])) $db->delete("cms_submenu_items", array("WHERE page_id = ?", array($this->id), array("integer")));
                if (!empty($my_post["original_menu_item_id"]) && empty($my_post["menu_item_id"])) $db->delete("cms_submenu_items", array("WHERE page_id = ?", array($this->id), array("integer")));
                if (!empty($my_post["original_submenu_item_id"]) && empty($my_post["submenu_item_id"])) $db->delete("cms_sub_submenu_items", array("WHERE page_id = ?", array($this->id), array("integer")));

                // updates
                if (
					!empty($my_post["original_submenu_item_id"])
					&& !empty($my_post["submenu_item_id"])
                    && $my_post["original_submenu_item_id"] != $my_post["submenu_item_id"]
				){

                    $db->delete("cms_sub_submenu_items", array("WHERE page_id = ?", array($this->id), array("integer")));
                    $db->update("cms_sub_submenu_items", array("submenu_item_id"=>$my_post["submenu_item_id"]), array("WHERE page_id = ?", array("page_id" => $this->id), array("integer")));

                } else if (
					!empty($my_post["original_menu_item_id"])
					&& !empty($my_post["menu_item_id"])
                    && $my_post["original_menu_item_id"] != $my_post["menu_item_id"]
				){

                    $db->delete("cms_submenu_items", array("WHERE page_id = ?", array($this->id), array("integer")));
                    $db->update("cms_submenu_items", array("menu_item_id"=>$my_post["menu_item_id"]), array("WHERE id = ?", array("id" => $my_post["original_menu_item_id"]), array("integer")));

				}

                // inserts
                if (!empty($my_post["submenu_item_id"]) && empty($my_post["original_submenu_item_id"])) {

					$db->insert("cms_sub_submenu_items", array("submenu_item_id"=>$my_post["submenu_item_id"], "page_id"=>$this->id));

                } else if (
					(empty($my_post["menu_item_id"]) && empty($my_post["original_menu_item_id"]))
                    || (!empty($my_post["original_menu_item_id"]) && empty($my_post["menu_item_id"]))
				) {

				    $is_already_inserted = $db->select_value("id", "cms_main_menu_items", array("WHERE page_id = ?", array("page_id"=>$this->id), array("integer")));

				    if (empty($is_already_inserted)) {
                        $db->insert("cms_main_menu_items", array("page_id"=>$this->id));
                    }

                } else if (!empty($my_post["menu_item_id"]) && empty($my_post["original_menu_item_id"])) {

					$db->insert("cms_submenu_items", array("menu_item_id"=>$my_post["menu_item_id"], "page_id"=>$this->id));

                }
            }

        }

        function print_form(){
            global $cfg, $db, $libhtml, $my_post;

            $html = $libhtml->form_start();
			$html .= open_table("100%", "Basic page details", "action_form");

            // Select parent menu item if page is in submenu
            if (!empty($this->submenu_item_id)) $this->menu_item_id = $db->select_value("menu_item_id", "cms_submenu_items", array("WHERE id = ?", array($this->submenu_item_id), array("integer")));

            // save default values, so if the page moves from menu to submenu or similar, we can update the links
            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[seo_title]", $this->seo_title);
            $html .= $libhtml->render_form_table_row_hidden("original_menu_item_id", (!empty($this->menu_item_id) ? $this->menu_item_id : ''));
            $html .= $libhtml->render_form_table_row_hidden("original_submenu_item_id", (!empty($this->submenu_item_id) ? $this->submenu_item_id : ''));

            $this->menu_item_id = (isset($my_post["menu_item_id"])) ? $my_post["menu_item_id"] : (!empty($this->menu_item_id) ? $this->menu_item_id : '');
            $this->submenu_item_id = (isset($this->submenu_item_id) ? $this->submenu_item_id : '');

            if (!empty($cfg["editable"])) $html .= $libhtml->render_form_table_radio_selection($this->object_name."[type]", $this->type, "Page type", "type", array("Standard", "Bespoke"), "", "", array("self_submit"=>true));

            if ($this->type != "Bespoke" || (!empty($cfg["editable"]))){

                $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name, "Name", "name", array('required'=>true));
            }

            $html .= $libhtml->render_form_table_row($this->object_name."[title]", $this->title, "Title", "title");
            if ($this->type == "Standard" || (empty($cfg["editable"]))) $html .= $libhtml->render_form_table_radio_selection($this->object_name."[template]", (!empty($this->template) ? $this->template : "One column"), "Template", "template", array("One column", "Two columns", "Three columns"));

			$html .= close_table();

            if ($this->linked) {

				$html .= open_table("100%", "Position for the page in the navigation", "action_form", true);

				$html .= $libhtml->render_form_table_row_checkbox($this->object_name."[linked]", $this->linked, "Link page from the menu", "linked", array("self_submit"=>true));

				$selection = $db->select(
					"t.*, p.name",
					"cms_main_menu_items t LEFT JOIN cms_pages p ON p.id = t.page_id",
					array(
						"WHERE t.id > 0 AND t.page_id != ?",
						array((!empty($this->id) ? $this->id : 0)),
						array('integer')
					),
					array('order_by' => "ORDER BY t.sort_order ASC")
				);
                $html .= $libhtml->render_form_table_row_selection("menu_item_id", $this->menu_item_id, "Main menu item", "menu_item_id", $selection, "id", "name", array('self_submit'=>true));

				$selection = $db->select(
					"t.*, p.name",
					"cms_submenu_items t LEFT JOIN cms_pages p ON p.id = t.page_id",
					array(
						"WHERE t.menu_item_id = ? AND t.page_id != ?",
						array((!empty($this->menu_item_id) ? $this->menu_item_id : 1), (!empty($this->id) ? $this->id : 0)),
						array('integer', 'integer')
					),
					array('order_by' => "ORDER BY t.sort_order ASC")
				);
                $html .= $libhtml->render_form_table_row_selection("submenu_item_id", $this->submenu_item_id, "Sub menu item", "submenu_item_id", $selection, "id", "name");

				$html .= close_table();

			} else {

			    $html .= open_table("100%", "Position for the page in the navigation", "action_form");
                $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[linked]", $this->linked, "Link page from the menu", "linked", array("self_submit"=>true));
				$html .= close_table();

			}

            // $html .= table_separator("100%", "SEO optimisation", "action_form", true);
            // $html .= table_help("If left blank, meta tags keywords and description will be taken from global website settings.");
            // $html .= $libhtml->render_form_table_row($this->object_name."[meta_keywords]", $this->meta_keywords, "Meta keywords", "meta_keywords", array("tooltip"=>"The keyword tags should contain between 4 and 10 keywords, separated by comma."));
            // $html .= $libhtml->render_form_table_row($this->object_name."[meta_description]", $this->meta_description, "Meta description", "meta_description", array("tooltip"=>"The description tag should be less than 200 characters long."));

            $html .= table_separator("100%", "Page options", "action_form");
            // $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[show_newsletter]", $this->show_newsletter, "Show newsletter box", "show_newsletter");
            // if (!empty($cfg["editable"])) $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[disable_delete]", $this->disable_delete, "Disable delete", "disable_delete");
            // if (!empty($cfg["editable"])) $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[hide_promos]", $this->hide_promos, "Hide promo boxes", "hide_promos");

            if (empty($this->id)) $this->active = 1;
            $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[active]", $this->active, "Publish page", "active");

            $html .= close_table();
            return $html;
        }

        function print_details() {
            global $cfg, $user1, $libhtml;

            $html = open_table("49%","","action_form details_form left");

			$html .= $libhtml->render_table_row("Name", $this->name);
            $html .= $libhtml->render_table_row("Published", tick_cross_image($this->active));

            $html .= table_separator("50%","","action_form details_form right");

			if (!empty($this->template)) $html .= $libhtml->render_table_row("Template", $this->template);

			$html .= $libhtml->render_table_row("URL","<a href=\""  . $cfg['website'] . "help/" . $this->id . "\" target=\"_blank\">" . $cfg['website'] . "help/" . $this->id. "</a>");
            if (!$this->active) $html .= $libhtml->render_table_row("Preview URL","<a href=\""  . $cfg['website'] . "help/" . $this->id . "-1109".md5($this->seo_title)."\" target=\"_blank\">" . $cfg['website'] . "help/" . $this->id. "-1109".md5($this->seo_title)."</a>");

            $html .= close_table();
            return $html;
        }

        function _set_table_list_row_items($item){
            global $db, $cfg, $user1, $libhtml;

            $item->active = ajax_toggle( $item->id, $this->table, "active", $user1->{$libhtml->path . "edit_page.php"}, $item->active);

            $item->name = href_link(array(
                "permission"=>$user1->{$libhtml->path . "page_details.php"},
                "url"=>$cfg['root'] . $libhtml->path . "page_details.php?page_id=$item->id&tab=summary",
                "text"=>$item->name,
                "tooltip"=>"View page details",
                "button"=>false,
                "popup"=>false,
                "click_trigger"=>true
            ));

            $item->edit = href_link(array( // delete
                "permission"=>$user1->{$libhtml->path . "edit_page.php"},
                "url"=>$cfg["root"] . $libhtml->path . "edit_page.php?page_id=$item->id",
                "text"=>"<span class=\"ico_edit tooltip\" title=\"Edit\">&nbsp;</span><span class=\"txt\">Edit</span>",
                "class"=>"action",
                "button"=>false,
                "popup"=>true,
                "clear"=>false,
            ));

            $item->delete = href_link(array( // delete
                "permission"=>( $user1->{$libhtml->path . "delete_page.php"} && ($item->type != "Bespoke" || (!empty($cfg["editable"])))),
                "url"=>$cfg["root"] . $libhtml->path . "delete_page.php?page_id=$item->id",
                "text"=>"<span class=\"ico_delete tooltip\" title=\"Delete\">&nbsp;</span><span class=\"txt\">Delete</span>",
                "class"=>"action",
                "button"=>false,
                "popup"=>true,
                "clear"=>false,
            ));

            if ($item->type=="Bespoke") {

                $item->type =  '<span class="ico_bpage tooltip" title="Bespoke pages have predefined set of elements which can be changed.">Bespoke</span>';

            } else {

                $item->type = '<span class="ico_spage tooltip" title="Standard pages are flexible and can be built using a layercake elements.">Standard</span>';

            }

            return;
        }

    }
