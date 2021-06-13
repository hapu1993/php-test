<?php

    class Page_Element extends Object {

        public $table = "cms_page_elements";
        public $left_join = "
            LEFT JOIN cms_pages p on p.id = t.page_id
            LEFT JOIN cms_elements e on e.id = t.element_id
        ";
        public $other_selects = "
            , e.name as element_type
            , p.name as page_name
            , p.type as page_type
            , p.template as page_template
        ";
        public $orderby = "page_name, t.sort_order";
        public $dir = "ASC";
        public $view_array = array(
            'reference'=>array("name"=>"Reference", "width"=>"230px"),
            'column'=>array("name"=>"Column", "width"=>"100px"),
            'element_type'=>array("name"=>"Element type", "width"=>"100px"),
            'value'=>array("name"=>"Value", "toggle_all"=>true),
            'img'=>array("name"=>"Image / File"),
            'public'=>array("name"=>"Public", "column"=>"public", "width"=>"60px"),
        );

        function insert(){
            global $cfg, $db, $user1;

            if (parent::insert()) {
                // insert sub elements one by one
                $common = array("created_by"=>$user1->id, "created_time"=>date("Y-m-d H:i:s"));
                foreach($this->sub_page_elements as $sub_page_element){
                    $db->insert("cms_page_sub_element_values", array_merge(array(
                        "page_id"=>$this->page_id,
                        "page_element_id"=>$this->id,
                        "sub_element_id"=>$sub_page_element["sub_element_id"],
                        "value"=>(!empty($sub_page_element["value"]) ? $sub_page_element["value"] : ''),
                        "lib_value"=>(!empty($sub_page_element["lib_value"]) ? $sub_page_element["lib_value"] : ''),
                    ), $common));
                }
            }
        }

        function update(array $additional = array()){
            global $cfg, $db, $user1;

            if (parent::update($additional)) {
                $common = array("modified_by"=>$user1->id, "modified_time"=>date("Y-m-d H:i:s"));

                foreach($this->sub_page_elements as $sub_page_element){
                    if (!empty($sub_page_element["id"])) {
                        $db->update("cms_page_sub_element_values", array_merge(array(
                            "page_id"=>$this->page_id,
                            "page_element_id"=>$this->id,
                            "sub_element_id"=>$sub_page_element["sub_element_id"],
                            "value"=>(!empty($sub_page_element["value"]) ? $sub_page_element["value"] : ''),
                            "lib_value"=>(!empty($sub_page_element["lib_value"]) ? $sub_page_element["lib_value"] : ''),
                        ), $common), array("WHERE id = ?", array("id" => $sub_page_element["id"]), array("integer")));
                    } else {
                        $db->insert("cms_page_sub_element_values", array_merge(array(
                            "page_id"=>$this->page_id,
                            "page_element_id"=>$this->id,
                            "sub_element_id"=>$sub_page_element["sub_element_id"],
                            "value"=>(!empty($sub_page_element["value"]) ? $sub_page_element["value"] : ''),
                            "lib_value"=>(!empty($sub_page_element["lib_value"]) ? $sub_page_element["lib_value"] : ''),
                        ), $common));
                    }
                }
            }
        }

        function delete(){
            global $cfg, $db;
            $db->delete($this->table, array("WHERE id = ?", array($this->id), array("integer")));
            $db->delete("cms_page_sub_element_values", array("WHERE page_element_id = ?", array($this->id), array("integer")));
        }

        function print_form() {
            global $cfg, $db, $libhtml, $my_post, $my_get;

            $html = $libhtml->form_start();
            $html .= open_table();

            // copy self submitted values
            if (!empty($my_post["sub_page_elements"])) $this->sub_page_elements = $my_post["sub_page_elements"];

            $html .= $libhtml->render_form_table_row_hidden("page_element[sort_order]", $this->sort_order);
            $html .= $libhtml->render_form_table_row_hidden("page_element[page_id]", $this->page_id);

            if (empty($this->element_id)) {

                // show all types of elements for Bespoke pages
                if (!empty($cfg["editable"]) && $this->page_type == "Bespoke") $element_types = $db->select("id, CONCAT(type, ' - ' , name) as name","cms_elements", array("WHERE public = 1", array(), array()), array('order_by'=>"ORDER BY name ASC"));
                else $element_types = $db->select("id, name","cms_elements", array("WHERE type = ? AND public = ?", array("Standard", 1), array("varchar", "integer")), array('order_by'=>"ORDER BY name ASC"));

                $html .= $libhtml->render_form_table_row_selection("page_element[element_id]", $this->element_id, "Element Type", "element_id", $element_types,"id", "name", array('required'=>true, 'self_submit'=>true));

            } else {
                $element_type = $db->select_value("name","cms_elements", array("WHERE id = ?", array($this->element_id), array("integer")));
                $html .= $libhtml->render_form_table_row_hidden("page_element[element_id]", $this->element_id);
                // $html .= $libhtml->render_table_row("Element", '<b>' . $element_type . '</b>');
                if ($this->page_type == "Bespoke" && !empty($cfg["editable"])) $html .= $libhtml->render_form_table_row("page_element[reference]", $this->reference, "Reference", "reference");

                // number of columns need to match the number in Page object
                if ($this->page_type == "Standard" || (empty($cfg["editable"]))) {
                    if ($this->page_template == "One column") $html .= $libhtml->render_form_table_row_hidden("page_element[column]", "First column");
                    else if ($this->page_template == "Two columns") $html .= $libhtml->render_form_table_radio_selection("page_element[column]", $this->column, "Column", "column", array("First column", "Second column"), "", "", array("required"=>true));
                    else if ($this->page_template == "Three columns") $html .= $libhtml->render_form_table_radio_selection("page_element[column]", $this->column, "Column", "column", array("First column", "Second column", "Third column"), "", "", array("required"=>true));
                }

                $html .= table_separator();

                $sub_element_types = $db->select("*","cms_sub_elements", array("WHERE element_id = ?", array($this->element_id), array("integer")), array('order_by'=>"ORDER BY sort_order ASC"));

                if (!empty($sub_element_types)){
                    $id = 0;
                    foreach($sub_element_types as $element){

                        // set common options
                        $options = array("tooltip"=>$element->description, "required"=>$element->required);
                        $value = (!empty($this->sub_page_elements[$id]["value"])) ? $this->sub_page_elements[$id]["value"] : '';
                        $lib_value = (!empty($this->sub_page_elements[$id]["lib_value"])) ? $this->sub_page_elements[$id]["lib_value"] : '';
                        if (!empty($this->sub_page_elements[$id]["id"])) $html .= $libhtml->render_form_table_row_hidden("page_element[sub_page_elements][".$id."][id]", $this->sub_page_elements[$id]["id"]);
                        $html .= $libhtml->render_form_table_row_hidden("page_element[sub_page_elements][".$id."][sub_element_id]", $element->id);

                        switch ($element->type) {
                            case "String":
                                $html .= $libhtml->render_form_table_row("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $options);
                                break;
                            case "String - URL":
                                $html .= $libhtml->render_form_table_row("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array_merge(array("class"=>"url"), $options));
                                break;
                            case "Digits":
                                $html .= $libhtml->render_form_table_row("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array_merge(array("class"=>"digits"), $options));
                                break;
                            case "Text":
                                $html .= $libhtml->render_form_table_row_text("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array_merge(array("rte"=>false), $options));
                                break;
                            case "Rich Text":
                                $html .= $libhtml->render_form_table_row_text("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array_merge(array("rte"=>true), $options));
                                break;
                            case "Textarea":
                                $rows = ($this->element_id == 7) ? 22 : 5;
                                $html .= $libhtml->render_form_table_row_text("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array_merge(array("rte"=>false, "rows"=>$rows), $options));
                                break;
                            case "Image file upload - with library option":
                                $html .= $libhtml->render_form_table_row_file("page_element[sub_page_elements][".$id."][value]", $element->title, $element, "page_elements/", array_merge(array("file"=>$value, "lib_file"=>$lib_value, "from_library"=>true, "accepted_ft"=>"jpg|jpeg|bmp|gif|png|tiff", "override_field"=>"lib_value", "override_formname"=>"page_element[sub_page_elements][".$id."][lib_value]"), $options));
                                break;
                            case "Image file upload - without library option":
                                $html .= $libhtml->render_form_table_row_file("page_element[sub_page_elements][".$id."][value]", $element->title, $element, "page_elements/", array_merge(array("file"=>$value, "accepted_ft"=>"jpg|jpeg|bmp|gif|png|tiff"), $options));
                                break;
                            case "File upload":
                                $html .= $libhtml->render_form_table_row_file("page_element[sub_page_elements][".$id."][value]", $element->title, $element, "page_elements/", array_merge(array("file"=>$value), $options));
                                break;
                            case "Target":
                                $target = array('_parent'=>"Same window", '_blank'=>"New window");
                                $html .= $libhtml->render_form_table_radio_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $target, "", "", $options);
                                break;
                            case "Separator":
                                $html .= table_separator();
                                break;
                            case "Hint":
                                $html .= '<div class="hint">'.$element->description.'</div>';
                                break;
                            case "Gallery":
                                $selection = $db->select("id, title", "cms_galleries", array("", array(), array()), array("order_by"=>"ORDER BY title ASC"));
                                $html .= $libhtml->render_form_table_row_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $selection, "id", "title", $options);
                                break;
                            case "Checkbox":
                                $html .= $libhtml->render_form_table_row_checkbox("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $options);
                                break;
                            case "Columns width":
                                $columns_width = array(''=>"Equal width", '30'=>"30 : 70", '70'=>"70 : 30");
                                $html .= $libhtml->render_form_table_radio_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $columns_width, "", "", $options);
                                break;
                            case "Background color":
                                $background_color = array(''=>"White", "blue"=>"Blue", 'green'=>"Green", 'pink'=>"Pink", "purple"=>"Purple", "orange"=>"Orange", "red"=>"Red");
                                $html .= $libhtml->render_form_table_radio_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $background_color, "", "", $options);
                                break;
                            case "Promo Box":
                                $selection = $db->select("id, title", "cms_promo_boxes", array("", array(), array()), array("order_by"=>"ORDER BY title ASC"));
                                $html .= $libhtml->render_form_table_row_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $selection, "id", "title", $options);
                                break;
                            case "Linked page":
                                // Must come before "Linked page - URL" in sort order
                                $selection = $db->select("id, name", "cms_pages", array("", array(), array()), array("order_by"=>"ORDER BY name ASC"));
                                $html .= '<tr><th class="minw"><label for="project_id">Link to</label></th><td>
                                    <span class="tdhint">Select a page from the list</span>' .
                                    $libhtml->render_form_table_row_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $selection, "id", "name", array_merge(array("minimal"=>true), $options)) .
                                '<span class="add ctxt">or enter a page URL</span>';
                                break;
                            case "Linked page - URL":
                                // Must come after "Linked page" in sort order
                                $html .= $libhtml->render_form_table_row("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array_merge(array("minimal"=>true, "class"=>"url"), $options))
                                .'</td></tr>';
                                break;
                            case "Library document":
                                $selection = $db->select("id, name", "cms_documents", array("", array(), array()), array("order_by"=>"ORDER BY name ASC"));
                                $html .= $libhtml->render_form_table_row_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, $selection, "id", "name", $options);
                                break;
                            case "File display":
                                $html .= $libhtml->render_form_table_radio_selection("page_element[sub_page_elements][".$id."][value]", $value, $element->title, "id" . $id, array('Button','Link'), "", "", $options);
                                break;
                        }

                        $id++;
                    }

                    $html .= table_separator();
                    $html .= $libhtml->render_form_table_row_checkbox("page_element[public]", (strpos($libhtml->basename, 'add_') !== false) ? 1 : $this->public, "Public", "public");
                }

            }

            $html .= close_table();
            return $html;
        }

        function _set_table_list_row_items($item){
            global $db, $cfg, $user1, $libhtml;

            // show all values for this element in one column
            $element_values = $db->select("e.*, t.title", "cms_page_sub_element_values e LEFT JOIN cms_sub_elements t ON t.id = e.sub_element_id", array("WHERE e.page_element_id = ? AND e.page_id = ?", array($item->id, $item->page_id), array("integer", "integer")));
            $item->value = '<div class="rte">';
            $item->img = '';

            foreach ($element_values as $element){

                if (($element->title == "Title" || $element->title == "Tab title" || $element->title == "Main title") && !empty($element->value)) $item->value .= '<h4>'.$element->value.'</h4>';

                if (($element->title == "Text" || $element->title == "Rich Text" || $element->title == "Tab content" || $element->title == "Main text") && !empty($element->value)) $item->value .= $element->value;

                if ($element->title == "YouTube video URL" && !empty($element->value)) $item->value .= '<iframe width="560" height="315" src="//www.youtube-nocookie.com/embed/'.getYouTubeId($element->value).'" frameborder="0" allowfullscreen></iframe>';
                else if ($element->title == "Vimeo video URL" && !empty($element->value)) $item->value .= '<iframe src="//player.vimeo.com/video/'.getVimeoId($element->value).'?title=0&amp;byline=0&amp;portrait=0&amp;color=879DC5" width="560" height="315" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

                if (($element->title == "Image" || $element->title == "Main image")){
                    $image = (!empty($element->lib_value)) ? $db->select_value("image", "cms_lib_images", array("WHERE id = ?", array("id"=>$element->lib_value), array("integer"))) : $element->value;
                    if(is_file($cfg['secure_dir'] . $image)) {
						$item->img = "<a class=\"jbox_img\" href=\"". phpThumb_URL(array("src"=>$cfg['secure_dir'] . $image)) . "\"><img src=\"". phpThumb_URL(array("src"=>$cfg['secure_dir'] . $image,"w"=>50,"h"=>50,"zc"=>1)) . "\" alt=\"".str_replace(substr(basename($image), 0, 14), "", basename($image))."\"/></a>";
					}
                }

                if ($element->title == "Gallery"){
                    if ($element->value) {
                        $gallery = $db->select_value("title", "cms_galleries", array("WHERE id = ?", array("id"=>$element->value), array("integer")));
                        if (!empty($gallery)) {
                            $item->value = "Gallery: " . $gallery;
                        } else {
                            $db->delete("cms_page_sub_element_values", array("WHERE id = ?", array($element->id), array("integer")));
                            $item->value = "<i>None</i>";
                        }
                    } else {
                        $item->value = "<i>None</i>";
                    }
                }

                if ($element->title == "Promo Box"){
                    if ($element->value) {
                        $gallery = $db->select_value("title", "cms_promo_boxes", array("WHERE id = ?", array("id"=>$element->value), array("integer")));
                        if (!empty($gallery)) {
                            $item->value = "Promo box: " . $gallery;
                        } else {
                            $db->delete("cms_page_sub_element_values", array("WHERE id = ?", array($element->id), array("integer")));
                            $item->value = "<i>None</i>";
                        }
                    } else {
                        $item->value = "<i>None</i>";
                    }
                }


                if ($element->title == "File"){

                    $item->img = $libhtml->print_file_cell($element->value);

                }
            }
            $item->value .= '</div>';
            $item->value = text_toggler($item->value);
            $item->public = ajax_toggle($item->id, $this->table, "public", $user1->{$libhtml->path . "edit_page_element.php"}, $item->public);

            return;
        }

    }
