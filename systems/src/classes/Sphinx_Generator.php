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
class Sphinx_Generator extends Object {

    public $object_pk = "";
    public $object_name = "sphinx_generator";
    private $win_config_path = "c:/sphinx/";
    private $linux_config_path = "/etc/sphinx/";

    function print_config_form() {
        global $cfg, $db, $libhtml, $my_get;
        $html = $libhtml->form_start();
        $html .= open_table("600px","Settings");

        $dirs = glob($cfg["source_root"] . "*", GLOB_ONLYDIR);
        (isset($my_get['file_action'])) ? $include = my_request("include_classes") : $include = 1;
        $html .= $libhtml->render_form_table_row_checkbox("include_classes", $include, "Include Core Classes", "include_classes");
        foreach ($dirs as $dir) {
            $dir_parts = explode("/", $dir);
            $dir_path = $dir_parts[count($dir_parts)-1];
            if (strpos($dir_path, "app_") !== FALSE) {
                (isset($my_get['file_action'])) ? $include = my_request("include_" . $dir_path) : $include = 1;
                $html .= $libhtml->render_form_table_row_checkbox("include_" . $dir_path, $include, "Include " . ucfirst(str_replace("_", " ", $dir_path)), "include_" . $dir_path);
            }
        }

        $action = array("linux"=>"Linux","win"=>"Windows");
        (isset($my_get['target_system'])) ? $target_system = my_request("target_system") : $target_system = "linux";
        $html .= $libhtml->render_form_table_radio_selection("target_system",$target_system,"Target System", "target_system", $action, "", "", array("break"=>true));

        $action = array("open"=>"Open file when done","save"=>"Save file");
        (isset($my_get['file_action'])) ? $file_action = my_request("file_action") : $file_action = "open";
        $html .= $libhtml->render_form_table_radio_selection("file_action",$file_action,"File Action", "file_action", $action, "", "", array("break"=>true));
        $html .= $libhtml->render_table_row("Info", "If save file has been selected will attempt to overwrite the path: <br />
            LINUX - $this->linux_config_path<br />
            WINDOWS - $this->win_config_path<br />
            Upon verification of config file move sphinx.generated.conf to sphinx.conf");
        $html .= $libhtml->render_table_row("N.B.", "All tables to be indexed need to have columns t.created_time, t.modified_time and t.sphinx_index_date");
        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("generate_config", "Generate"),
            ),
            array(
                "show_prompt"=>false,
                "show_cancel"=>false,
                "pause"=>false,
            )
        );

        $html .= $libhtml->form_end();
        return $html;
    }

    function generate_config() {
        global $cfg, $my_post;
        $html = "";
        $include_paths = array();
        foreach ($my_post as $key => $val) {
            if (!is_array($val)) {
                if (strpos($key, "include_") !== FALSE && $val == 1) {
                    $include_paths[] = substr($key, 8);
                }
            }
        }

        $sphinx_template = file_get_contents($cfg["source_root"] . "includes/generator_templates/sphinx.tpl");

        $sphinx_parts = explode("{{INDEX_START}}", $sphinx_template);
        $sphinx_header = $sphinx_parts[0];
        $sphinx_parts = explode("{{INDEX_END}}", $sphinx_parts[1]);
        $sphinx_index_template = $sphinx_parts[0];
        $sphinx_footer = $sphinx_parts[1];

        //configure header
        $arr_search = array("{{DATABASE}}","{{DBHOST}}","{{DBUSER}}","{{DBPASS}}","{{DBNAME}}"); // these are the placeholder variables in templates
        $arr_replace = array(strtolower($cfg['database']),$cfg['dbhost'], $cfg['dbuser'], $cfg['dbpass'], $cfg['dbname']); // which will be replaced with these values
        $html = str_replace($arr_search, $arr_replace, $sphinx_header);

        //configure sphinx indexes for each class
        //$core_classes = glob($cfg["source_root"] . "classes/*.php");
        //$app_classes = glob($cfg["source_root"] . "app_*/classes/*.php");
        $core_classes = array();
        $app_classes = array();
        foreach ($include_paths as $path) {
            if ($path == "classes") $core_classes = glob($cfg["source_root"] . "classes/*.php");
            foreach (glob($cfg["source_root"] . $path . "/classes/*.php") as $file) {
                array_push($app_classes, $file);
            }
        }
        error_log("app_classes " . print_r($app_classes, true));

        $html .= $this->parse_classes($core_classes, $sphinx_index_template);
        $html .= $this->parse_classes($app_classes, $sphinx_index_template);

        /*        $class->table = "projects_diary";
         $class->left_join = "
         LEFT JOIN projects_projects m
         ON m.id=t.project_id
         ";
         $class->other_selects = "
         ,m.name as project_name
         ";
         $arr_replace = array($class->table, preg_replace('/\s+/', ' ', $class->left_join), preg_replace('/\s+/', ' ', $class->other_selects), $data_path); // which will be replaced with these values
         $html .= str_replace($arr_search, $arr_replace, $sphinx_index_template);
         */        //end off loop

        //configure Footer
        $arr_search = array("{{LOG_PATH}}","{{PID_PATH}}"); // these are the placeholder variables in templates
        if (isset($my_post["target_system"]) && $my_post["target_system"] == "win") {
            $arr_replace = array("c:/sphinx/log/","c:/sphinx/log/"); // which will be replaced with these values
            $config_path = $this->win_config_path;
        } else {
            $arr_replace = array("/var/log/sphinx/","/var/run/sphinx/"); // which will be replaced with these values
            $config_path = $this->linux_config_path;
        }
        $html .= str_replace($arr_search, $arr_replace, $sphinx_footer);

        if (isset($my_post["file_action"]) && $my_post["file_action"] == "save") {
            $this->create_file($html, $config_path . "sphinx.generated.conf");
        } else {
            $this->open_file($html);
        }
    }

    function parse_classes($classes, $template) {
        global $my_post;

        $html = "";
        if (isset($my_post["target_system"]) && $my_post["target_system"] == "win") {
            $data_path = "c:/sphinx/data/";
        } else {
            $data_path = "/etc/sphinx/data/";
        }

        $arr_search = array("{{APP}}","{{CLASS_NAME}}","{{CLASS_TABLE}}","{{LEFT_JOIN}}","{{DEFAULT_SELECTS}}","{{OTHER_SELECTS}}","{{DATA_PATH}}"); // these are the placeholder variables in templates

        $counter = 0;
        foreach ($classes as $filename) {
            $counter++;
            $filename_parts = explode("/", $filename);
            $app = "";
            if (strpos($filename_parts[count($filename_parts)-3], "app_") !== FALSE) $app = $filename_parts[count($filename_parts)-3];
            $class_name_parts = explode(".", $filename_parts[count($filename_parts)-1]);
            $class_name = strtolower($class_name_parts[0]);
            //            $html .= "<br />app - $app<br />";
            //            $html .= "<br />filename - $filename<br />";
            //            error_log("third last part of file " . $filename_parts[count($filename_parts)-3]);
            $class_file = file_get_contents($filename);
            $class_parts = explode("function", $class_file);
            $class_header = $class_parts[0];
            $class_header_parts = explode(";", $class_header);
            $object_name = "";
            $table = "";
            $left_join = "";
            $other_selects = "";
            foreach ($class_header_parts as $header_part) {
                if (strpos($header_part, '$object_name') !== FALSE) {
                    $object_name = explode("= \"", $header_part);
                    $object_name = trim(str_replace("\"", "", $object_name[1]));
                }
                if (strpos($header_part, '$table') !== FALSE) {
                    $table = explode("= \"", $header_part);
                    $table = trim(str_replace("\"", "", $table[1]));
                }
                if (strpos($header_part, '$left_join') !== FALSE) {
                    $left_join = explode("= \"", $header_part);
                    //                    $html .= "header part - $header_part<br />";
                    //                    $html .= "left join - $left_join[1]<br />";
                    $left_join = trim(str_replace("\"", "", $left_join[1]));
                    $left_join = preg_replace('/\s+/', ' ', $left_join);
                }
                if (strpos($header_part, '$other_selects') !== FALSE) {
                    $other_selects = explode("= \"", $header_part);
                    $other_selects = trim(str_replace("\"", "", $other_selects[1]));
                    $other_selects = preg_replace('/\s+/', ' ', $other_selects);
                }
            }
            $columns_array = array();
            $columns_array = $this->get_table_column_metadata($table);
            $table_columns = "t." . implode(", t.", $columns_array);
            //            $html .= "table - $table<br />";
            //            error_log($filename . " $table ");
            if (!empty($app)) $app .= "_";
            if (empty($object_name)) $object_name = $class_name;
            if (!empty($table)) {
                $arr_replace = array($app, $object_name, $table, $left_join, $table_columns, $other_selects, $data_path); // which will be replaced with these values
                $html .= str_replace($arr_search, $arr_replace, $template);
            }
        }
        //error_log("$counter classes parsed.");

        return $html;
    }

    function get_table_columns($table_name) {
        global $cfg, $db;
        $columns = array();
        $dbname = $cfg['dbname'];
        $selection = $db->get_table_column_metadata($table_name);
        foreach ($selection as $info) {
            $columns[] = $info->COLUMN_NAME;
        }
        return $columns;
    }

    function create_file($content = "",  $filename = ""){
        global $cfg;
        $file = fopen($filename, "w");
        fwrite($file, $content);
        fclose($file);
    }

    function open_file($content = ""){
        global $cfg, $my_post;

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"sphinx.generated.conf\"");
        header("Pragma: hack");
        echo $content;
        exit();

    }

}
