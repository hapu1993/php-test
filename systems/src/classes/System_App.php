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
class System_App extends Object {

    public $table = "system_apps";
    public $left_join = "";
    public $other_selects = "";
    public $view_array = array(
        'image'=>array("name"=>"","width"=>"32px"),
        'name'=>array("name"=>"App Name"),
        'path'=>array("name"=>"Path"),
        'tooltip'=>array("name"=>"Tooltip",'toggle_all'=>true),
        'active'=>array("name"=>"Active","width"=>"80px"),
    );
    public $orderby = "t.id";
    public $dir = "ASC";

    function insert() {
        global $user1,$db;
        $this->image = str_replace("fa-", "", $this->image);
        return parent::insert();
    }

    function update(array $additional = array()) {
        global $user1,$db;
        $additional = array("image"=>str_replace("fa-", "", $this->image));
        return parent::update($additional);
    }

    function print_form(){
        global $cfg, $db, $user1, $libhtml;

        $html = $libhtml->form_start();
        $html .= open_table();
        $html .= $libhtml->render_form_table_row("system_app[name]", $this->name, "App Name", "name");

        //Get all feasible app paths
        if ($handle = opendir($cfg['source_root'])) {

            $paths = array();

            while (false !== ($file = readdir($handle))) {
                if(
                        $file!="."
                        && $file!=".."
                        && is_dir($file)
                        && is_dir($file."/classes")
                        && is_dir($file."/includes")
                        && is_file($file."/index.php")
                ) $paths[] = $file."/";
            }
            closedir($handle);
        }

        $html .= $libhtml->render_form_table_row_selection("system_app[path]", $this->path, "Path", "path",$paths,'','');
        $html .= $libhtml->render_form_table_row("system_app[tooltip]", $this->tooltip, "Tooltip", "tooltip");
        $html .= $libhtml->render_form_table_row_checkbox("system_app[active]", $this->active, "Active", "active");
        $html .= $libhtml->render_form_table_row_checkbox("system_app[front_end_app]", $this->front_end_app, "Front End Application", "front_end_app", array("tooltip"=>"If ticked, this app should be deployed to 'website_source' path of the config variable.<br>It allows you a to specify permissions for each page of the application."));

        $html .= close_table();
        $html .= section(array("title"=>"Application icon"));
        $html .= '<div class="white_hint">To insert the Font Awesome icon, please pick the icon alias from <a href="https://fortawesome.github.io/Font-Awesome/icons/" target="_blank">here</a>.<br>
        To insert the image icon, please put the image into the <b>config</b> folder and paste the image file name, including the extension.</div>';
        $html .= open_table();

        if (!isset($this->type_image)) $this->type_image=null;
        $html .= $libhtml->render_form_table_radio_selection("system_app[type_image]", $this->type_image, "Icon entry method", "type_image", array(1=>'Type icon class or image file',2=>'Dropdown list'),'','',array('required'=>true,"self_submit"=>true));

        if ($this->type_image==1){

            $html .= $libhtml->render_form_table_row("system_app[image]", $this->image, "Application icon", "image", array("self_submit"=>true));

        } elseif ($this->type_image==2){

            //Insert a nice selection here
            $selection = array('bank', 'bar-chart', 'calendar-o', 'comment', 'database', 'dollar', 'euro', 'envelope', 'legal', 'list', 'folder-o', 'folder-open-o', 'flag', 'gbp', 'globe', 'graduation-cap', 'group', 'map', 'pie-chart', 'suitcase', 'server', 'shopping-cart', 'sitemap', 'sliders', 'th-list', 'user');
            $html .= $libhtml->render_form_table_row_selection("system_app[image]", $this->image, "Application icon", "image", $selection, '','', array("self_submit"=>true));

        }



        if (!empty($this->image) && preg_match('/png|jpg|jpeg|gif/', extension($this->image))) $html .= $libhtml->render_table_row("Image", '<img style="width:32px;" src="'.$cfg['root'] . 'config/' . $this->image.'"/>');
        else if (!empty($this->image)) $html .= $libhtml->render_table_row("Image", '<span class="ico_app"><i class="fa fa-'.$this->image.'"></i></span>');

        $html .= close_table();
        return $html;
    }

    function print_edit_form($options = array()){

        if (!empty($this->name) && $this->name=="Admin Panel" && empty($this->path)){

            $html = '<div class="hint">You cannot change or delete Admin Panel application.</div>';

        } else {

            $html = parent::print_edit_form($options);

        }
        return $html;
    }

    function print_delete_form(){

        if (!empty($this->name) && $this->name=="Admin Panel" && empty($this->path)){

            $html = '<div class="hint">You cannot change or delete Admin Panel application.</div>';

        } else {

            $html = parent::print_delete_form();

        }
        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        if (!empty($item->image) && preg_match('/png|jpg|jpeg|gif/', extension($item->image))) $item->image = '<img style="width:32px;" src="'.$cfg['root'] . 'config/' . $item->image.'"/>';
        else if (!empty($item->image)) $item->image = '<span class="ico_app"><i class="fa fa-'.$item->image.'"></i></span>';
        else $item->image = '<span class="ico_app"><i class="fa fa-times"></i></span>';

        $item->active = ajax_toggle(
            $item->id,
            $this->table,
            "active",
            (($item->name!="Admin Panel") && $user1->master_admin),
            $item->active
        );

        if (!empty($item->name) && $item->name=="Admin Panel" && empty($item->path)){
            $item->edit='';
            $item->delete='';
            $item->active=tick_cross_image(true);
        }

        $item->tooltip = text_toggler($item->tooltip);

        return;
    }
}
