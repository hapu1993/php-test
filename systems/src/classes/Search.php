<?php
/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2017 Riskpoint London Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
class Search {

    public $scope = array();
    public $exclude_apps = array("app_cms"); // app names to exclude from search
    public $exclude_objects = array("Client_Details"); // objects names to exclude from search

    function print_search_form(){
           global $cfg, $db, $libhtml;

        $html = $libhtml->form_start();
        $html .= open_table();
        $html .= $libhtml->render_form_table_row("keyword", my_request("keyword"), "Keyword", "keyword");
        $html  .= close_table();
        $html .= $libhtml->render_form_table_row_hidden("search", "Search");
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("search_button", "Search"),
            ),
            array(
                "show_prompt"=>false,
                "show_cancel"=>false,
                "pause"=>false,
            )
        );

        $html .= $libhtml->form_end();
        $html .= '<div class="clear"></div><br/>';
        return $html;
    }

    // get all objects' tables which are to be search
    function post_search(){
        global $cfg, $db, $libhtml;

        $html = '';
        $tables_already_searched = array();
        foreach($_SESSION["apps"] as $my_apps){
            if (!empty($my_apps->path) && (!in_array(str_replace("/", "", $my_apps->path), $this->exclude_apps))) {

                foreach(glob($cfg["source_root"] . $my_apps->path . "classes/*.php") as $object) {

                    $where = array("", array(), array()); // used for query
                    $where_in = array();
                    $object_name = str_replace(array($cfg["source_root"] . $my_apps->path . "classes/", ".php"), array("", ""), $object);

                    if (!in_array($object_name, $this->exclude_objects)) {
                        $object = new $object_name;

                         if (!empty($object->table) && !in_array($object->table, $tables_already_searched)) {
                            $tables_already_searched[] = $object->table;

                            // get all varchar & text fields in these searchable tables
                            $columns = $db->get_table_column_metadata($object->table);
                            foreach($columns as $column){
                                //error_log(print_r($column, true));
                                if ($column->DATA_TYPE == "text" || $column->DATA_TYPE == "varchar"){
                                    //$where_in[0] .= "t.".$column->COLUMN_NAME." LIKE '%".my_request("keyword")."%'";
                                    $where_in[] = " t.".$column->COLUMN_NAME." LIKE ?";
                                    $where[1][$column->COLUMN_NAME] = "%" . my_request("keyword") . "%";
                                    $where[2][] = 'varchar';
                                }
                            }
                            $where[0] = "WHERE " . implode(" OR ", $where_in);

                            // dump_var($object->table);
                            // dump_var(print_r($where_in, true));

                            // if there are varchar / text fields do ...
                            if (!empty($where_in)) {
                                // list object
                                $tmphtml = $object->_list(array(
                                    //'where'=>" AND ( " . implode(" OR ", $where_in) . " )"
                                    'where' => $where,
                                    'width'=>"100%",
                                    'quick_search'=>true,
                                    'app_path'=> $my_apps->path, // needed for _list edit action
                                    'view'=>true,
                                    'xml_export'=>false,
                                    'pdf_export'=>false,
                                    'csv_export'=>false,
                                    'email_alert'=>false,
                                    'dynamic_append'=>false,
                                    'pagination'=>false,
                                    'edit'=>false,
                                    'delete'=>false,
                                ));

                                // do not show empty messages
                                //if (strpos($tmphtml, "No items found") === false) {
                                if ($object->list_total>0) {

                                    $res = ($object->list_total>1) ? "results" : "result";
                                    $truncate = ($object->list_total>500) ? ", truncated to first 500" : '';

                                    $html .= section(array(
                                        "title"=>$my_apps->name . " - ". ucwords(str_replace("_", " ", $object_name)). " (".$object->list_total." ".$res.$truncate.")",
                                        "collapsible"=>true,
                                        "state"=>"collapsed",
                                        "to_top"=>true
                                    ));

                                    $html .= $tmphtml;
                                }
                            }

                        }
                    }
                }
            }
        }

        // do not show empty results
        if (empty($html)) return '<div class="success">Search returned no results.</div>';
        else return $html;

    }

}
