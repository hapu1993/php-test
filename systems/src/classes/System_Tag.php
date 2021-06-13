<?php

    class System_Tag extends Object {

        public $table = "system_tags";
        public $object_name = "system_tag";
        public $left_join = "";
        public $other_selects = "";
        public $orderby = "t.tag";
        public $dir = "ASC";
        public $view_array = array(
            'tag'=>array("name"=>"Tag", "column"=>"tag", "hide_filter"=>true),
            'related_object_name'=>array("name"=>"Restricted to object", "column"=>"related_object_name", "hide_filter"=>true),
            'related_sub_object'=>array("name"=>"Restricted to sub-object", "column"=>"related_sub_object", "hide_filter"=>true),
        );

        // if specified, allows the deletion of the cross linked objects, if the foreign key's delete action is specified as Restrict
        public $allowed_delete_from_tables = array("system_objects_tags");

        function update(array $additional = array()) {
            global $cfg, $db, $libhtml;

            // does this tag already exist for this object
            $already_exist = $db->select("*", $this->table, array("WHERE id != ? AND tag = ? AND related_object_name = ?", array($this->id, $this->tag, $this->related_object_name), array("integer", "varchar", "varchar")));
            if (empty($already_exist)){
                parent::update();

            } else {
                $_SESSION["feedback"] = g_feedback("error", "Tag was not updated because it already exists");

            }

        }

        function print_form() {
            global $cfg, $db, $libhtml;
            $html = $libhtml->form_start();
            $html .= open_table();

            $html .= $libhtml->render_form_table_row($this->object_name."[tag]", $this->tag, "Tag", "tag");
            $html .= close_table();
            return $html;
        }

        function _set_table_list_row_items($item){
            global $db, $cfg, $user1, $libhtml, $crypt;
            $item->related_object_name = ucfirst(str_replace("_", " ", $item->related_object_name));
            return;
        }

    }
