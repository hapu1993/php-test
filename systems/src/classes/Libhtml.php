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
class Libhtml {

    function __construct($options=array()){
        global $cfg, $user1, $db, $where, $cache, $links, $my_get;

        $defaults = array(
            'public'=>false, // used for the login, and other public pages
            'title'=>$cfg['client'],
            'main_tab'=>'',
            'tab'=>'', // needs to be empty, so the tab saved into the session shows the previous page
            'subtab'=>my_get("subtab", "all"),
            'subtabs_tabs'=>array(), // 2nd submenu, it will override whatever is in admin_menu.php
            'additional_tabs'=>array(), // 3rd level menu
            'basename'=>'',
            'path'=>get_path(),
            'sys_path'=>'',
            'add_to_url'=>'',
            'styles'=>'',
            'css'=>'',
            'body_class'=>'',
            'meta'=>'',
            'js'=>'',
            'js_body_on_load'=>'',
            'js_body_on_unload'=>'',
            'doc_start'=>'',
            'page_actions'=>'',
            'html'=>'',
            'links'=>$links,
            'fixed_menu'=>true, // should main menu float on page scroll
            'fixed_actions'=>true, // should main page actions float on page scroll
            'num_of_tab_items'=>10, // after this number, the items will be grouped in dropdown menu
            'num_of_submenu_items'=>10, // after this number, the items will be grouped in dropdown menu
            'put_to_history'=>false, // if true, all tabs on the page will be put in a Recently viewed history list
            'show_back'=>false, // show back button next to the page title, history back
            'back_link'=>'', // used to override back link (used on 2nd level of details pages)
            'page_action'=>array(), // list all additional actions next to the page title
            'more_actions'=>array(), // list all additional actions next to the page title in a dropdown menu
            'more_actions_caption'=>"More",
            'page_filter_search' =>'', // show an ajax input list
            'show_apps'=>true, // Show apps + app switcher
            'show_side_panel'=>true, //Show side bar
            'show_sitemap'=>true, //Show sitemap in the side menu
            'show_search'=>true, //Show search field in the side bar
            'show_menu'=>true, //Show main menu + submenu
            'show_header'=>true, //Show entire header
            'show_title_bar'=>true, //Show title
            'show_bar_icons'=>true, //Show main menu + submenu
            'cloud_env'=>false, // should be true for cloud app, fixes styling issues
            'header_search'=>false, // header search
            'header_search_label'=>'', //header search label
            'local_text'=>array(),
            'system_settings'=>array(),

        );

        foreach($defaults as $key => $value) {
            $this->{$key} = (isset($options[$key])) ? $options[$key] : $defaults[$key];
        }

        $this->basename = (!empty($cfg["nice_URLs"])) ? basename($_SERVER['PHP_SELF']) : basename($_SERVER['SCRIPT_NAME']);
        $this->sys_path = $this->path . $this->basename;

        // change user pagination on the fly
        if (!empty($my_get["update_pagination"])) $user1->preferences->pagination = $my_get["update_pagination"];

		// add system settings to libhtml object
        if (isset($user1->system_settings)) {
            $this->system_settings = $user1->system_settings;
        } else {
            $system_settings = $db->select("name,value", "test_app_system_settings", array("", array(), array()));
            if (!empty($system_settings)){
                foreach($system_settings as $setting){
                    $this->system_settings[$setting->name] = $setting->value;
                }
            }
        }

        $this->populateLocalText();

    }

    protected function populateLocalText(){
        global $db;
        if (!isset($_SESSION["local_text"]) || empty($_SESSION["local_text"])){
            $_SESSION["local_text"] = $db->select("local_key,value", "system_local_text", array(
                "WHERE (local_group = 'Global' OR local_group = ?)",
                array('local_group' => $this->basename),
                array('varchar')
            ));
        }

        if(isset($_SESSION["local_text"]) && !empty($_SESSION["local_text"])){
            foreach($_SESSION["local_text"] as $local_txt){
                $this->local_text[$local_txt->local_key] = $local_txt->value;
            }
        }
    }

    function render($html=""){
        global $hide_feedback;

        $this->page_start();
        $this->html .= $html;

        // activate popup
        if (!empty($_SESSION["show_popup"])){

            // create object
            if (!empty($_SESSION["show_popup"]["object"])){
                $tmp_object = new $_SESSION["show_popup"]["object"];
                if (!empty($_SESSION["show_popup"]["data"])) $tmp_object->set_post($_SESSION["show_popup"]["data"]);
            }

            $popup_html = '';
            if (!empty($_SESSION['feedback'])) $popup_html .= $_SESSION['feedback'];

            if (!empty($_SESSION["show_popup"]["function"])) {
                $popup_html .= $tmp_object->{$_SESSION["show_popup"]["function"]}();
            } else if (!empty($_SESSION["show_popup"]["html"])) {
                $popup_html .= $_SESSION["show_popup"]["html"];
            }

            $hide_feedback = true;
            $this->html .= $this->jbox_popup($_SESSION["show_popup"]["title"], $popup_html);
            $_SESSION['feedback'] = null;
            $_SESSION['show_popup'] = null;
        }

        $this->page_end();
        echo $this->html;
    }

    function render_form($html){
        $this->form_page_start();
        $this->html .= $html;
        $this->form_page_end();
        echo $this->html;
    }

    function page_start() {
        global $cfg, $user1, $db, $where, $cache, $history_item, $crypt, $app_actions, $app_count, $comment;

        // Header + Apps
        $image = $active_app = $extra = null;
        $app_count = 0;

        if (!empty($_SESSION['apps'])){
            foreach($_SESSION['apps'] as $app_id => $app) {
                if ($app_id != 99) {
                    if (!empty($app->menu)) $app_count++;
                    if ($app->path == $this->path) {
                        $active_app = $app;
                        $active[$app->name] = true;
                        $image = $app->image;
                        $extra = (!empty($app->tooltip)) ? $app->tooltip : $app->name;
                    }
                }
            }
        }

        // Page Title
        if ($this->title != $cfg['client']){
            if (is_array($this->title)){
                $this->plain_title = $this->title[1];
                $_SESSION['current_page'] = $this->title[0] . " - <span>" . $this->title[1] . "</span>";
                if (!empty($this->subtab)) $history_item['page'] = "<span class=\"l4\">" . $this->title[1]." - </span><span class=\"l1\"> ".ucfirst(str_replace("_"," ", $this->subtab)) . "</span>";
                else $history_item['page'] = "<span class=\"l4\">" . $this->title[1]." - </span><span class=\"l1\">".ucfirst(str_replace("_"," ", $this->tab)) . "</span>";
                $history_item["basename"] = $this->basename;
            } else {
                $this->plain_title = $this->title;
                $_SESSION['current_page'] = $this->title;
                $tab = (!empty($this->tab)) ? " - </span><span class=\"l1\">".ucfirst(str_replace("_"," ",$this->tab))."</span>" : "</span>" ;
                $history_item['page'] = "<span class=\"l4\">" . $this->title . $tab;
                $history_item["basename"] = $this->basename;
            }
        } else {
            $this->plain_title = $cfg['client'];
            $_SESSION['current_page'] = $this->plain_title;
            //$history_item['page'] = "<span class=\"l1\">$active_app->name</span><span>$this->plain_title</span><span>" . ucfirst(str_replace("_"," ",$this->tab)) . "</span>";
            $tab = (!empty($this->tab)) ? " - </span><span class=\"l1\">".ucfirst(str_replace("_"," ",$this->tab))."</span>" : "</span>" ;
            $history_item['page'] = "<span class=\"l4\">".$this->plain_title. $tab;
            $history_item["basename"] = $this->basename;
        }

        // History
        // Previous page
        $history_count = (isset($_SESSION['history'])) ? count($_SESSION['history']) : 0;
        $previous_page = (isset($_SESSION['history'][$history_count-1]['page'])) ? $_SESSION['history'][$history_count-1]['page'] : '';

        // last visited page, remember it for logout
        $_SESSION["redirect_page"] = get_enc_page();

        // Visits history
        if ((($history_item['page'] != $previous_page) && ($history_item['page'] != "Not Found")) || $this->put_to_history){
            $history_item['url'] = get_enc_page();
            if (empty($_SESSION['history'])) $_SESSION['history'][] = $history_item;
            else if (!empty($_SESSION['history']) && end($_SESSION['history']) != $history_item) $_SESSION['history'][] = $history_item;
        }

        if ($history_count > 5) array_shift($_SESSION['history']);

        // START OF PAGE HTML
        $this->html .= '
        <!DOCTYPE html>
            <html class="'.strtolower($_SESSION['RISK_USER_BROWSER_AGENT']).' '.strtolower($_SESSION['RISK_USER_BROWSER_AGENT']).'-'.intval($_SESSION['RISK_USER_BROWSER_VER']).'" lang="en">
                <head>
                    <meta charset="utf-8">
                    <meta name="robots" content="noindex, nofollow">
                    '.$this->meta.'
                    <title>'.$this->plain_title.'</title>
                    '.$this->css.'
                    <link href="' . $cfg['root'] . 'config/favicon.png" rel="shortcut icon" type="image/png">
                    <link href="' . $cfg['root'] . 'css/default464.css" rel="stylesheet" type="text/css">
                    <link href="' . $cfg['root'] . 'config/branding457.css" rel="stylesheet" type="text/css">
                    <link href="' . $cfg['root'] . 'css/font-awesome.min.css" rel="stylesheet" type="text/css">
                    <link href="' . $cfg['root'] . 'css/print.css" rel="stylesheet" type="text/css" media="print">';
                    if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') $this->html .= '<link href="' . $cfg['root'] . 'css/debug.css" rel="stylesheet" type="text/css">';

                    if (isset($_SESSION['RISK_USER_OS_GENERIC']) && $_SESSION['RISK_USER_OS_GENERIC'] == "iPad") {
                        $this->html .= '<link href="' . $cfg['root'] . 'css/ipad.css" rel="stylesheet" type="text/css">
                        <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
                        <link rel="apple-touch-icon" href="'.$cfg["root"].'config/icon-57.png" >
                        <link rel="apple-touch-icon" sizes="72x72" href="'.$cfg["root"].'config/icon-72.png" >
                        <link rel="apple-touch-icon" sizes="114x114" href="'.$cfg["root"].'config/icon-114.png" >';
                    }

                $this->html .= '</head>
                <body class="';
                    if (!empty($this->body_class)) $this->html .= $this->body_class . ' ';
                    if (!empty($user1->preferences->disable_rc_menu)) $this->html .= 'disable_rc ';
                    if (!empty($this->public)) $this->html .= 'publicpg ';
                    $this->html .= '"'; // close body class

                    if (!empty($this->js_body_on_load)) $this->html .= ' onload="' . $this->js_body_on_load . ';"';
                    if (!empty($this->js_body_on_unload)) $this->html .= ' onunload="' . $this->js_body_on_unload . ';"';

                $this->html .= '>';

                $this->html .= '
                    <div class="wrapper">
                        <div class="page_cover">
                            <div class="page_feedback perm">
                                <div class="g_feedback">
                                    <span class="wait">
                                        <i class="fa fa-spinner fa-pulse"></i>
                                    </span>
                                    <p>Please wait...</p>
                                </div>
                            </div>
                        </div>';

                        // show debug bar

                        if (isset($cfg['ENV'])) {
                            if (in_array(strtolower($cfg['ENV']), array('dev', 'local-dev'))) {
                                $this->html .= '
                                        <div class="envid">This is the development environment.</div>';
                            } elseif (strtolower($cfg['ENV']) == 'staging') {
                                $this->html .= '
                                        <div class="envid">This is the staging environment.</div>';
                            }
                        }

						// Show custom banner
						if(!empty($this->system_settings['Banner - Text'])){

							$styles[] = 'font-weight: bold';
							if(!empty($this->system_settings['Banner - Background Colour'])) $styles[] = 'background-color: '.$this->system_settings['Banner - Background Colour'];
							if(!empty($this->system_settings['Banner - Font Colour'])) $styles[] = 'color: '.$this->system_settings['Banner - Font Colour'];

							$this->html .= '
									<div class="envid" style="'.implode(';',$styles).';">'.$this->system_settings['Banner - Text'].'</div>';

						}

                        // ie7 warning
                        if ($_SESSION['RISK_USER_BROWSER_AGENT'] == "Internet Explorer" && intval($_SESSION['RISK_USER_BROWSER_VER']) <= 8) {
                            $this->html .= '
                            <div class="iewarning">
                                <p>
                                    <b>Your browser is out of date.</b><br/>
                                    It has known security flaws and most of the application features will not work properly. <a target="_blank" href="http://chrome.google.com/">Please update your browser.</a>
                                </p>
                            </div>';
                        }

                        // Does the top menu band div show?
                        $top_band_style = '';
                        if (empty($this->public)){
                            if (!isset($_COOKIE['showTopCookie']) || $_COOKIE['showTopCookie'] == "on") {
                                $up_down = 'on';
                                $top_band_style = 'style="display:block;"';
                            } else {
                                $up_down = 'off';
                                $top_band_style = 'style="display:none;"';
                            }
                        }

                        if (!empty($this->show_header)) {

                        // Start of top band div
                        $this->html .= '
						<div class="head_wrap" '.$top_band_style.'>
                            <div class="logo">
                                <a class="tooltip" title="Visit ' . $cfg['website'] . '" href="' . $cfg['website'] . '" target="_blank">
                                    <img src="' . $cfg['root'] . 'config/logo.png" alt="Logo" style="height: 44px;"/>
                                </a>
                            </div>';

                            if (empty($this->public)) {
                                $this->html .= '
                                <div class="user_info">
                                    <span class="settings">
                                        <span class="left">Logged in as</span>
                                        '.href_link(array(
                                            "permission"=>$user1->{"edit_preferences.php"},
                                            "url"=>$cfg["root"] . "edit_preferences.php",
                                            "popup"=>true,
                                            "button"=>false,
                                            "clear"=>false,
                                            "text"=>'<i class="fa fa-cog"></i><b> ' . $_SESSION['fullname'] . '</b>',
                                            "float"=>"left",
                                        )).'
                                    </span>
                                    <span class="logout">
                                        <a href="' . encrypt_url($cfg['root'] . 'logout.php'). '">
                                            <i class="fa fa-power-off"></i>Logout
                                        </a>
                                    </span>
                                </div>';

                                $comment = $db->select_value("comment","system_pages",array("WHERE page=?", array('page' => $this->sys_path), array('varchar')));
								if(is_array($comment)) $comment = $comment[0];

                                if (!empty($comment)) {
                                    $this->html .= '
                                    <div class="msgpnl">
                                        <a class="btn jbox jbox_modal" title="Help" href="#page_comment">View help</a>
                                        <div id="page_comment" class="hide">'.$comment.'</div>
                                    </div>';
                                }

                                if ($this->header_search){
                                    $this->html .=
                                    '<div class="header_search">
                                        <label for="hsterms">'.$this->header_search_label.'</label>
                                        <input id="hsterms" name="hsterms" value="Search..." />
                                    </div>';
                                }

                                if ($this->show_apps) $this->html .= $this->apps_menu($app_count,$active_app,$active);

                            } else {
                                $codeVersion = $this->getCodeVersion();
                                $this->html .= '
                                <div class="copy">
                                    '.$codeVersion.' &copy; <a href="'.$cfg['website'].'" target="_blank">'.$cfg['client'].'</a> '. date("Y",time()) .'
                                </div>';

                            }

                        $this->html .= '</div>';
                        // End of top end div

                        }

                        $this->html .= '
                        <div class="body_section">
                            <table class="body_table">
                                <tr>';

                                    // side panel
                                    // is side menu visible at all
                                    if ($this->show_side_panel && empty($this->public)) {

                                        $side_cell_style = (!isset($_COOKIE['showSideCookie']) || $_COOKIE['showSideCookie'] == "off") ? ' style="display:none;"' : '';
                                        $this->html .= '
                                        <td class="side_cell" '.$side_cell_style.'>
                                            <div id="side_panel" class="side_panel" '.$side_cell_style.'>';

                                                $this->html .= $this->navigation_tabs();

                                                // app specific actions
                                                if (!empty($app_actions)) {
                                                    $this->html .= '
                                                    <div class="section shortcuts opened top_border">
                                                        <h2>Shortcuts</h2>
                                                        <div class="s_content">';

                                                            foreach($app_actions as $action) $this->html .= $action;

                                                        $this->html .= '
                                                        </div>
                                                    </div>';
                                                }

                                                // search box
                                                if ($this->show_search && !empty($user1->preferences->show_side_search) && $user1->{"search.php"}) {
                                                    $this->html .= '
                                                    <div class="search section opened top_border">
                                                        <h2>Search</h2>
                                                        <div class="s_content">
                                                            <form method="post" action="'.$cfg["root"].'search/">
                                                                <input type="hidden" name="move_to_get" value="1" />
                                                                <input type="text" class="keyword" name="keyword" placeholder="Keyword..." value="'.my_request("keyword").'"/>
                                                                <span class="submit">
                                                                    <i class="fa fa-search"></i>
                                                                </span>
                                                            </form>
                                                        </div>
                                                    </div>';
                                                }

                                                // paused forms (saved drafts)
                                                if (!empty($_SESSION["paused_forms"])){

                                                    $this->html .= '
                                                    <div class="paused section opened">
                                                        <h2>Saved drafts</h2>
                                                        <div class="s_content">';

                                                            $temp_array = array_reverse($_SESSION["paused_forms"]);
                                                            foreach($temp_array as $item => $data){
                                                                if (isset($data["new"])){
                                                                    $new = '<span class="red">*</span>';
                                                                    unset($_SESSION["paused_forms"][$item]["new"]);
                                                                } else {
                                                                    $new = '';
                                                                }

                                                                $time_diff = (rel_time(strtotime($data["paused_date"])) == "now") ? "Just saved" : "Saved ".rel_time(strtotime($data["paused_date"])) . " ago";

                                                                // truncate long title
                                                                if (strlen($data["title"]) > 26) $data["title"] = substr($data["title"], 0, 25) . '...';

                                                                if ($data["path"] == $this->path) {
                                                                    $this->html .= '
                                                                    <a class="jbox tooltip" title="'.$time_diff.'" href="'.inject_crypt_vars($item, array("continue"=>$item)).'">
                                                                        <span>'.$data["title"].$new.'</span>
                                                                        <span class="ico"><i class="fa fa-pencil"></i></span>
                                                                    </a>';

                                                                } else {
                                                                    $this->html .= '
                                                                    <span class="mlink tooltip" title="'.$time_diff.'<br/>Draft is only available from <b>'.$data["app_name"].'</b>">
                                                                        <span>'.$data["title"].$new.'</span>
                                                                        <span class="ico"><i class="fa fa-pencil"></i></span>
                                                                    </span>';
                                                                }
                                                            }

                                                        $this->html .= '</div>
                                                    </div>';
                                                }

                                                if (!empty($_SESSION["deleted_objects"])){
                                                    $this->html .= '
                                                    <div class="paused section opened">
                                                        <h2>Wastebasket</h2>
                                                        <div class="s_content">';

                                                            $temp_array = array_reverse($_SESSION["deleted_objects"]);

                                                            foreach($temp_array as $d_object) {
                                                                if (isset($d_object["new"])) {
                                                                    $new = '<span class="red">*</span>';
                                                                    unset($_SESSION["deleted_objects"][$d_object["object_id"]]["new"]);
                                                                } else {
                                                                    $new = '';
                                                                }

                                                                $time_diff = (rel_time(strtotime($d_object["deletion_date"])) == "now") ? "Just deleted" : "Deleted ".rel_time(strtotime($d_object["deletion_date"]) . " ago");

                                                                // truncate long title
                                                                if (strlen($d_object["object"]) > 26) $d_object["object"] = substr($d_object["object"], 0, 25) . '...';

                                                                $this->html .= '
                                                                <a class="jbox tooltip" title="'.$time_diff.'" href="'.$d_object["restore_url"].'">
                                                                    <span>'.$d_object["object"].'</span>
                                                                    <span class="ico"><i class="fa fa-repeat"></i></span>
                                                                </a>';
                                                            }

                                                        $this->html .= '
                                                        </div>
                                                    </div>';
                                                }

                                                // Does the side menu show?
                                                if (!isset($_COOKIE['showSideCookie']) || $_COOKIE['showSideCookie'] == "off") {
                                                    $left_right = "off";
                                                    $style = ' style="width:100%;"';
                                                } else {
                                                    $left_right = "on";
                                                    $style = "";
                                                }

                                            $this->html .= '
                                            </div>
                                        </td>';

                                    }

                                    $this->html .= '
                                    <td class="content_cell">
                                        <div class="content_section clearfix">';

                                            if ($this->show_menu && empty($this->public)){

                                                // Top level menu
                                                $this->html .= '
                                                <div class="menu_wrap clearfix '.(!empty($this->fixed_menu) ? 'fixed_menu' : '' ).'">
                                                    <div class="admin_menu">
                                                        <ul class="main_menu">
                                                            <li class="shift">
                                                                <span class="header_toggle header_'.$up_down.'" data-header-toggle="'.$up_down.'">
                                                                    <i class="fa fa-caret-up">&nbsp;</i>
                                                                    <i class="fa fa-caret-down">&nbsp;</i>
                                                                </span>';

                                                                if ($this->show_side_panel) {
                                                                    $this->html .= '
                                                                    <span class="side_toggle side_'.$left_right.'" data-toggle-side-menu="'.$left_right.'">
                                                                        <i class="fa fa-caret-left">&nbsp;</i>
                                                                        <i class="fa fa-caret-right">&nbsp;</i>
                                                                    </span>';
                                                                }

                                                            $this->html .= '
                                                            </li>';

                                                            $subtabs = array();

                                                            // Main app navigation
                                                            foreach ($this->links as $link_name => $link) {
                                                                $page2 = $this->path . $link[0];
                                                                if (isset($user1->$page2) && $user1->$page2) {

                                                                    if ($link[0]==$this->basename) {
                                                                        $this->html .= '<li class="selected">';
                                                                        if (isset($link[3]) && $link[0]!=$this->tab) $subtabs = $link[3];

                                                                    } elseif ($link_name==$this->main_tab) {
                                                                        $this->html .= '<li class="selected">';
                                                                        if (isset($link[3])) $subtabs = $link[3];

                                                                    } else {
                                                                        $this->html .= '<li>';

                                                                    }

                                                                        $this->html .= '<a href="' . encrypt_url($cfg['root'] . $this->path . $link[0]) . '">' . $link[1] . '</a>';

                                                                        if (isset($link[3])) {
                                                                            $this->html .= '<ul>';

                                                                                foreach ($link[3] as $key=>$val) {
                                                                                    $this->html .= '<li>
                                                                                        <a href="' . encrypt_url($cfg['root'] . $this->path . $link[0] . "?tab=$key") .'">'.$val.'</a>
                                                                                    </li>';
                                                                                }

                                                                            $this->html .= '</ul>';
                                                                        }

                                                                    $this->html .= '</li>';
                                                                }

                                                            }

                                                        $this->html .= '
                                                        </ul>
                                                    </div>';

                                                    // Subtabs
                                                    if (!empty($this->subtabs_tabs)) $subtabs = $this->subtabs_tabs;

                                                    // put the current (previous in case of the details page) tab into the session
                                                    if (!empty($this->tab)) $_SESSION["subtab"] = $this->tab;

                                                    // build class for the user menu
                                                    $notbs = (empty($subtabs)) ? "notbs " : "";

                                                    $this->html .= '<ul class="tabmenu '.$notbs.'clearfix">';
                                                        if (!empty($subtabs)) {

                                                            $i = 0;
                                                            $submenu_html = '';
                                                            foreach($subtabs as $key => $val){

                                                                $active = (!empty($_SESSION["subtab"]) && $_SESSION["subtab"] == $key) ? 'class="active"' : '';
                                                                if (!empty($this->main_tab)) $url = $cfg["root"] . $this->path . $this->links[$this->main_tab][0] . "?tab=" . $key; // tab url when on details page
                                                                else $url = $_SERVER['PHP_SELF'] . "?tab=" . $key; // tab url when on main / tab page

                                                                if ($i < $this->num_of_tab_items - 1) {
                                                                    $this->html .= '<li>
                                                                        <a '.$active .' href="' . encrypt_url($url) . '">'.$val.'</a>
                                                                    </li>';

                                                                } else {

                                                                    if (!empty($_SESSION["subtab"]) && $_SESSION["subtab"] == $key) {
                                                                        $hidden_selected = $val;
                                                                        $hidden_url = $url;
                                                                    }
                                                                    $submenu_html .= '<li>
                                                                        <a '.$active .' href="' . encrypt_url($url) . '">'.$val.'</a>
                                                                    </li>';

                                                                }

                                                                $i++;

                                                            }
                                                        }

                                                        if (!empty($submenu_html)) {

                                                            if (!empty($hidden_selected)) {
                                                                $this->html .= '
                                                                <li>
                                                                    <a href="' . encrypt_url($hidden_url) . '" class="more clearfix">
                                                                        ' . $hidden_selected . ' - More <i class="fa fa-caret-down"></i>
                                                                    </a>
                                                                    <ul>
                                                                        ' . $submenu_html .  '
                                                                    </ul>
                                                                </li>';

                                                            } else {
                                                                $this->html .= '
                                                                <li>
                                                                    <a href="#" class="more clearfix">
                                                                        More <i class="fa fa-caret-down"></i>
                                                                    </a>
                                                                    <ul>
                                                                        ' . $submenu_html .  '
                                                                    </ul>
                                                                </li>';

                                                            }
                                                        }

                                                    $this->html .= '
                                                    </ul>
                                                </div>';

                                            }

                                            if ($this->show_title_bar && empty($this->public)) {

                                                $this->html .= '<div class="main_title clearfix '.($this->fixed_actions ? 'fixed_actions' : '').'">';

                                                    if (!empty($this->back_link)) {
                                                        $this->html .= '<a href="'.encrypt_url($cfg["root"] . $this->back_link).'">Back</a>'; // show back link

                                                    } else if ($this->show_back) {
                                                        $history = array_reverse($_SESSION['history']);
                                                        foreach ($history as $item){
                                                            if ($item["basename"] != $this->basename) {
                                                                $this->html .= '<a class="btn" href="'.$item["url"].'">Back</a>'; // show back button
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    $this->html .= '<h1>' . $_SESSION['current_page'] . '</h1>'; // title

                                                    if (!empty($this->page_actions)) {
                                                        $this->html .= '<div class="page_actions">';

                                                            foreach($this->page_actions as $action){ // list all primary actions
                                                                $this->html .= $action;
                                                            }

                                                        $this->html .= '</div>';
                                                    }

                                                    if (!empty($this->more_actions)) {

                                                        $actions = '';
                                                        foreach($this->more_actions as $action){ // list all primary actions
                                                            $actions .= $action;
                                                        }

                                                        if (!empty($actions)) {
                                                            $this->html .= '
                                                            <div class="ddmenu mactions">
                                                                    <span class="top">'.$this->more_actions_caption.'</span>
                                                                    <span class="handle">
                                                                        <i class="fa fa-caret-down"></i>
                                                                    </span>
                                                                <span class="wrap">
                                                                    '.$actions.'
                                                                </span>
                                                            </div>';
                                                        }
                                                    }

                                                    if ($this->show_bar_icons) {
                                                        $this->html .= '
                                                        <div class="ddmenu pops">
                                                            <span class="handle">
                                                                <i class="fa fa-caret-down"></i>
                                                            </span>
                                                            <span class="wrap">';

                                                                if ($user1->{"add_user_comment.php"}) {
                                                                    //need to pass _self as request_uri is record.php
                                                                    //$this->html .= "<li class=\"submenu_right no_line far_right\"><a class=\"tooltip\" title=\"Record a video and write a comment. Java applet may ask you for permission to run, please confrim.&lt;br/&gt;The video recording has been limited to a maximum of 20 seconds.\" href=\"#\" onclick=\"window.open('".$cfg['root']."includes/record.php?x=".$crypt->str_encrypt("REQUEST_URI=" . $_SERVER['REQUEST_URI'])."','RiskpointRecorder','menubar=no,location=no,scrollbars=no,'+ popup_params(320, 455));\"><span class=\"ico_record\">&nbsp;</span><span class=\"txt\">Record a video</span></a></li>\n";
                                                                    //$this->html .= "<li class=\"submenu_right no_line\"><a class=\"tooltip\" title=\"Take a screenshot and write a comment. Java applet may ask you for permission to run, please confrim.\" onclick = \"getScreenshot();\"><span class=\"ico_camera\">&nbsp;</span><span class=\"txt\">Take a screenshot</span></a><span id=\"screenshot_holder\"></span></li>\n";
                                                                    $this->html .= '<a class="jbox popup tooltip" title="Report a bug, propose a change, write a comment" href="' .encrypt_url($cfg['root'] . 'add_user_comment.php?page='.$this->sys_path). '">Report a bug</a>';
                                                                }

                                                                $this->html .= '<a data-page-print="true" class="tooltip" href="#" title="Print this page">Print this page</a>';

                                                                if ($user1->{"send_single_user_email.php"}) {
                                                                    $this->html .= '<a class="jbox popup tooltip" href="' .encrypt_url($cfg['root'] . 'send_single_user_email.php?page='.$this->sys_path). '" title="Email another system user">Email another system user</a>';
                                                                }

                                                                // $this->html .= "<li class=\"submenu_right no_line\"><a class=\"tooltip export_pdf_page\" style=\"display:none;\" href=\"#\" title=\"Export page to PDF\"><span class=\"ico_page_pdf\">&nbsp;</span><span class=\"txt\">Export page to pdf</span></a></li>\n";

                                                            $this->html .= '
                                                            </span>
                                                        </div>';
                                                    }

                                                    // rhs filter
                                                    $this->html .= $this->page_filter_search;

                                                    // up and down, visible only on page scroll
                                                    $this->html .= '
                                                    <div class="bottom_side">
                                                            <a class="btn blue" href="#" data-scroll="to_top">
                                                                <i class="fa fa-caret-up"></i>Back to top
                                                            </a>
                                                            <a class="btn blue" href="#" data-scroll="down">
                                                                <i class="fa fa-caret-down"></i>Scroll down
                                                            </a>';
                                                            if (!empty($comment)) {
                                                                $this->html .= '<a class="btn jbox jbox_modal hlptrg" title="Help" href="#page_comment">View help</a>';
                                                            }
                                                    $this->html .= '
                                                    </div>
                                                </div>';
                                            }

                                            // 3rd level menu
                                            if (!empty($this->additional_tabs)) {

                                                $this->html .= '<ul class="submenu clearfix">';
                                                    $i = 0;
                                                    $submenu_html = '';

                                                    foreach($this->additional_tabs as $key => $val){
                                                        $url = $_SERVER['PHP_SELF'] . '?subtab=' .$key.'&tab='.$this->tab;
                                                        if (!empty($this->add_to_url)) $url .= "&" . $this->add_to_url;

                                                        $active = '';
                                                        if ($this->subtab == $key) {
                                                            $active = 'class="active"';
                                                        }

                                                        if ($i < $this->num_of_submenu_items - 1) {
                                                            // strip_tags allows images to be prepended or appended
                                                            if (!preg_match('/<a .*>.*<\/a>/', $val)) {
                                                                $this->html .= '<li>
                                                                    <a '.$active.' href="' . encrypt_url($url) . '">'.$val .'</a>
                                                                </li>';
                                                            } else {
                                                                $this->html .= '<li>
                                                                    '.$val.'
                                                                </li>';
                                                            }

                                                        } else {

                                                            if ($this->subtab == $key) {
                                                                $hidden_selected = $val;
                                                                $hidden_url = $url;
                                                            }

                                                            $submenu_html .= '<li>
                                                                <a href="' . encrypt_url($url) . '">'.$val .'</a>
                                                            </li>';

                                                        }

                                                        $i++;
                                                    }

                                                    if (!empty($submenu_html)) {
                                                        if (!empty($hidden_selected)) {
                                                            $this->html .= '
                                                            <li>
                                                                <a href="' . encrypt_url($hidden_url) . '" class="more clearfix">
                                                                    ' . $hidden_selected . ' - More <i class="fa fa-caret-down"></i>
                                                                </a>
                                                                <ul>
                                                                    ' . $submenu_html .  '
                                                                </ul>
                                                            </li>';

                                                        } else {
                                                            $this->html .= '
                                                            <li>
                                                                <a href="#" class="more clearfix">
                                                                    More<i class="fa fa-caret-down"></i>
                                                                </a>
                                                                <ul>
                                                                    ' . $submenu_html .  '
                                                                </ul>
                                                            </li>';
                                                        }
                                                    }

                                                $this->html .= '</ul>';

                                            } else if (empty($this->cloud_env)) {
                                                $this->html .= '<div class="sbtm clearfix"></div>';

                                            }

                                            $this->html .= '<div class="application-tabs '.(!empty($this->cloud_env) ? "cldenv" : "").' clearfix">
                                            <!-- START OF PAGE CONTENT -->';

    }

    function apps_menu($app_count=0, $active_app, $active){
        global $cfg, $user1;

        if ($app_count > 1) {
            $this->html .= '
            <div data-applications-menu="true" class="appmenu">
                <div class="more" title="More">
                    <span class="txt">More</span>
                    <i class="fa fa-caret-down">&nbsp;</i>
                    <span class="dmwrap">
                        <a data-show-app-switcher="true" data-swap-app-title="Manage my apps" class="moreapps" href="#">Manage my apps</a>
                    </span>
                </div>
                <ul class="apps hasmore">';

                    // different counters if there are 4 apps in total, less or more
                    $app_limit = ($app_count <= 4) ? 4 : 3;

                    if ($app_count > 0) {
                        $i = 0;
                        $active_shown = false;
                        foreach($_SESSION['apps'] as $app_id => $app) {

                            if (!empty($app->menu)) {

                                if (empty($app->image)) $app->image = '<span class="ico_app"><i class="fa fa-times"></i></span>';
                                $extra = (!empty($app->tooltip)) ? $app->tooltip : $app->name;

                                if ($user1->{$app->path . "index.php"}){
                                    $class = ($i >= $app_limit) ? "hide" : "qacc";
                                    if (!empty($active[$app->name])) {
                                        $active_class = "app-active";
                                        if ($i < $app_limit) $active_shown = true;
                                    } else {
                                        $active_class = '';
                                    }

                                    $this->html .= '
                                    <li class="'.$class.'">
                                        <a data-app-id="'.$app->id.'" data-swap-app-title="'.$extra.'" class="'.$active_class.'" href="' . encrypt_url($cfg['root'] . $app->path . 'index.php') . '">
                                            <span class="txt">'.$app->name.'</span>
                                            <span class="ico_app">';

                                            if (preg_match('/png|jpg|jpeg|gif/', extension($app->image))) $this->html .= '<img src="'.$cfg["root"].'config/'.$app->image.'"/>';
                                            else $this->html .= '<i class="fa fa-'.$app->image.'"></i>';

                                            $this->html .= '</span>
                                        </a>';

                                        // dropdown menu
                                        if (!empty($app->menu)){
                                            $this->html .= '
                                            <div class="dmwrap">';

                                            foreach($app->menu as $menu_item => $menu_path) {
                                                $this->html .= '<a href="' . encrypt_url($cfg['root'] . $menu_path) . '" title="'.$menu_item.'">'.$menu_item .'</a>';
                                            }

                                            $this->html .= '
                                            </div>';
                                        }

                                    $this->html .= '
                                    </li>';
                                }
                                $i++;
                            }
                        }
                    }

                    if (!$active_shown) {
                        $this->html .= '
                        <li class="othersel">
                            <a data-swap-app-title="'.$active_app->name.'" class="app-active" href="' . encrypt_url($cfg['root'] . $active_app->path . "index.php") . '">
                                <span class="ico_app"><i class="fa fa-'.$active_app->image.'"></i></span>
                            </a>
                        </li>';
                    }

                $this->html .= '
                </ul>
                <span class="info">
                    <span data-current-app-name="true">'.$active_app->name.'</span>
                </span>
            </div>';

        }
    }

    function page_search_section($ps_html = ""){
        global $cfg, $user1;
        $style = (!empty($user1->preferences->hidden_filters->{$this->sys_path})) ? 'style="display:none;"' : '';
        $html = '
            <div class="page_search clearfix">
                <div class="psin clearfix">
                    <div class="search_wrap" '.$style.'>'.$ps_html.'</div>
                    <div class="hide_handle">
                        <span class="grippy"><i class="fa fa-ellipsis-h"></i></span>
                    </div>
                </div>
            </div>';
        return $html;
    }

    function form_page_start() {
        global $cfg, $comment, $my_post, $my_get;

        // load jbox for the first time
        if (!isset($_POST["jbox_opened"])) {
            // clear previous popup history, vars, etc
            unset($_SESSION["popups"]);

            // create new popup session
            $jbox_id = date("His");
            $_SESSION["popups"][$jbox_id][$_POST["page"]]["title"] = $this->title;
            $_SESSION["popups"][$jbox_id][$_POST["page"]]["url"] = $_POST["page"];

            $this->html .= '<div data-jbox="header" class="jbox_header '. ( !empty($this->easy_cancel) ? "ecbox" : "" ) .' clearfix" rel="'.$jbox_id.'">
                <div data-jbox="title" class="title">'.$this->title.'</div>
                <div data-jbox="close" class="close">
                    <i class="fa fa-times"></i>
                </div>
                <div data-jbox="restore" class="restore">
                    <i class="fa fa-square-o"></i>
                    <i class="fa fa-minus"></i>
                </div>
            </div>
            <div data-jbox="content" class="jbox_content">
                <div class="jbox_content_inner">';

        } else {

            $this->html .= '<div class="jbox_content_inner">'; // open inner box (nothing to do with breadcrumbs)

            if (!empty($_POST["page"]) || !empty($_POST["through_page"])) { // do the following only for non self-submited forms
                $_POST["page"] = str_replace(array("http://", "https://", $_SERVER['HTTP_HOST']), "", $_POST["page"]);

                // remove from session, all keys after the one posted
                if (!empty($_POST["page"]) && !empty($_POST["type"]) && $_POST["type"] == "back"){
                    $found = false;
                    foreach($_SESSION["popups"][$_POST["jbox_id"]] as $value) {
                        if ($found) unset($_SESSION["popups"][$_POST["jbox_id"]][$value["url"]]);
                        if ($_POST["page"] == $value["url"]) $found = true; // but do not remove the current key / page
                    }

                } else if (!empty($_POST["page"]) && !empty($_POST["type"]) && $_POST["type"] == "forward"){ // add to forward session
                    $_SESSION["popups"][$_POST["jbox_id"]][$_POST["page"]] = array(
                        "title"=>$this->title,
                        "url" => $_POST["page"],
                        "prefill_field" => $my_get["prefill_field"],
                    );

                }

                // generate breadcrumbs as string
                $bcrumbs = "";
                if (!empty($_POST["page"]) && !empty($_SESSION["popups"][$_POST["jbox_id"]])){

                    foreach($_SESSION["popups"][$_POST["jbox_id"]] as $value) {

                        if ($value["url"] != $_POST["page"]) {
                            $bcrumbs .= '<a data-jbox-jlink="back" href="'. $value["url"].'" class="crumb">
                                <i class="fa fa-angle-left"></i>'.$value["title"].'
                            </a>';

                        } else {
                            $bcrumbs_last = '<span class="crumb">
                                <i class="fa fa-angle-left"></i>'.$value["title"].'
                            </span>';
                        }
                    }
                }

                //    display breadcrumbs (can be empty, although session is not empty, because the current page is also stored in the session)
                if (!empty($bcrumbs)) {
                    $this->html .= '<div class="jbox_inner_controls clearfix">' . $bcrumbs;
                    if (!empty($bcrumbs_last)) $this->html .= $bcrumbs_last;
                    $this->html .= '</div>';
                }

            }
        }

        // add comment
		if(is_array($comment)) $comment = $comment[0];
        if (!empty($comment)) {
			$this->html .= $comment . "<br/>";
		}
    }

    function page_end() {
        global $db, $cfg, $page_start_time, $user1, $my_get, $my_post, $my_files, $hide_feedback, $crypt;

        $url = (empty($this->path)) ? 'http://www.riskpoint.co.uk' : $cfg['website'];
        $copyright = (empty($this->path)) ? 'Riskpoint Ltd' : $cfg['client'];
        $codeVersion = $this->getCodeVersion();

                                            $this->html .= '<!-- END OF PAGE CONTENT -->
                                            </div>';

                                            if (empty($this->public)) {
                                                $this->html .= '<div class="clear"></div>
                                                <div class="footer clearfix">
                                                    <span class="right">
                                                        '.$codeVersion.' &copy; <a href="'.$url.'" target="_blank">'.$copyright.'</a> ' . date("Y") . '
                                                    </span>
                                                </div>';
                                            }
                                        $this->html .= '</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>';

        // don't display feedback messages if we are redisplaying a popup form
        if (empty($hide_feedback)) {

            if (!empty($_SESSION['errors'])) {
                foreach($_SESSION['errors'] as $error) {
                    $_SESSION['feedback'].=g_feedback("error",$error['errstr']);
                }
            }

            if (!empty($_SESSION['feedback'])) $this->html .= '<div class="page_feedback">'.$_SESSION['feedback'].'</div>';

            $_SESSION['feedback'] = null;
        }

        if (isset($db)) $db->close();

        if (empty($this->public) && (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') && ($_SESSION["RISK_USER_BROWSER_AGENT"] != "Internet Explorer")) { // If we want debug info + not for IE, please...

            // get the position and visibility from cookies
            $dbg_display = (isset($_COOKIE["dbg_toolbar"])) ? $_COOKIE["dbg_toolbar"] : "block";
            $dbg_trigger_display = (isset($_COOKIE["dbg_toolbar"]) && $_COOKIE["dbg_toolbar"] == "block") ? "-30px" : "0px";
            $dbg_type = (isset($_COOKIE["dbg_toolbar_type"])) ? $_COOKIE["dbg_toolbar_type"] : "dbg_vertical";
            $dbg_left = (isset($_COOKIE["dbg_toolbar_left"])) ? $_COOKIE["dbg_toolbar_left"] : "30px";
            $dbg_top = (isset($_COOKIE["dbg_toolbar_top"])) ? $_COOKIE["dbg_toolbar_top"] : "90%";

            $toolbar = '
                <div id="dbg_toolbar_trigger" style="left:'.$dbg_trigger_display.';"><i class="fa fa-bug"></i></div>
                    <div id="dbg_toolbar" class="'.$dbg_type.'" style="display:'.$dbg_display.'; left:'.$dbg_left.'; top:'.$dbg_top.'">
                        <div class="dbg_head">
                            <span class="dbg_type tooltip" title="Change toolbar orientation">
                                <i class="fa fa-chevron-down"></i>
                                <i class="fa fa-chevron-up"></i>
                            </span>
                            <span class="dbg_close tooltip" title="Close">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>';

            // PHP Tidy report on the configured HTML
            if (extension_loaded('tidy') || function_exists('tidy_parse_string') && mb_strlen($tidy->errorBuffer) > 0) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="Tidy" href="#dbg_tidy">Tidy</a>';

                $tidy = tidy_parse_string($this->html . "</body></html>");

                $this->html .= '
                        <div id="dbg_tidy">
                            <div class="dbg_info">
                                '.open_table('100%').
                                $this->render_table_row("Accessibility warnings",tidy_access_count($tidy)).
                                $this->render_table_row("Configuration errors",tidy_config_count($tidy)).
                                $this->render_table_row("Errors",tidy_error_count($tidy)).
                                $this->render_table_row("Warnings",tidy_warning_count($tidy)).
                                $this->render_table_row("Error Buffer","<pre>" . htmlspecialchars($tidy->errorBuffer) . "</pre>").
                                close_table().'
                            </div>
                        <div>';

            }

            //PHP Errors
            if (!empty($_SESSION['errors'])) {

                $toolbar .= '<a class="dbg_code jbox jbox_modal" data-close-overlay="true" title="Errors" href="#dbg_errors">Errors</a>';

                $this->html .= '
                        <div id="dbg_errors">
                            <div class="dbg_info">
                                '.open_table('100%');

                foreach($_SESSION['errors'] as $error) $this->html .= $this->render_table_row("Error",$error['html']);

                $this->html .= close_table().'
                            </div>
                        </div>';

                unset($_SESSION['errors']);

            } else {

                $toolbar .= '<a href="#">Errors (0)</a>';

            }

            //Source code
            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="PHP-code" href="#dbg_code">PHP-code</a>';
            $this->html .= '
                        <div id="dbg_code">
                            <div class="dbg_info">
                                '.renderFile($_SERVER['SCRIPT_FILENAME']) . '
                            </div>
                        </div>';

            //User log
            if (!empty($_SESSION['user_log'])) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="User Log" href="#dbg_log">User Log</a>';

                $this->html .= '
                        <div id="dbg_log">
                            <div class="dbg_info">
                                '.dump_array($_SESSION['user_log'],array('title'=>"User Log",'dstate'=>'hidden')).'
                            </div>
                        </div>';

                unset($_SESSION['user_log']);
            }

            //$_GET
            if (!empty($_SESSION['original_get'])) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="$_GET" href="#dbg_get">$_GET</a>';

                $this->html .= '
                        <div id="dbg_get">
                            <div class="dbg_info">
                                '.dump_array($_SESSION['original_get'], array('title'=>"\$_GET",'dstate'=>'hidden')).
                                dump_array($my_get,array('title'=>"\$my_get",'dstate'=>'hidden')).'
                            </div>
                        </div>';

                unset($_SESSION['original_get']);
            }

            //$_POST
            if (!empty($_SESSION['original_post'])) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="$_POST" href="#dbg_post">$_POST</a>';

                $this->html .= '
                        <div id="dbg_post">
                            <div class="dbg_info">
                                '.dump_array($_SESSION['original_post'],array('title'=>"\$_POST",'dstate'=>'hidden','textarea'=>true));

                if (!empty($_SESSION['actions'])) $this->html .= dump_array($_SESSION['actions'],array('title'=>"Actions",'dstate'=>'hidden','textarea'=>true));
                if (!empty($my_post)) $this->html .= dump_array($my_post,array('title'=>"\$my_post",'dstate'=>'hidden'));

                    $this->html .= '
                            </div>
                        </div>';

                unset($_SESSION['original_post']);
            }

            //$_SERVER

            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="$_SERVER" href="#dbg_server">$_SERVER</a>';

            $this->html .= '
                    <div id="dbg_server">
                        <div class="dbg_info">
                            '.dump_array($_SERVER).'
                        </div>
                    </div>';

            //$_SESSION

            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="$_SESSION" href="#dbg_session">$_SESSION</a>';

            $this->html .= '
                    <div id="dbg_session">
                        <div class="dbg_info">
                            '.dump_array($_SESSION).'
                        </div>
                    </div>';

            //$_FILES
            if (!empty($_SESSION['original_files'])) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="$_FILES" href="#dbg_files">$_FILES</a>';

                $this->html .= '
                    <div id="dbg_files">
                        <div class="dbg_info">
                            '.dump_array($_SESSION['original_files'],array('title'=>"\$_FILES",'dstate'=>'hidden')).'
                        </div>
                    </div>';

                unset($_SESSION['original_files']);
            }

            //$_COOKIE

            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="$_COOKIE" href="#dbg_cookie">$_COOKIE</a>';

            $this->html .= '
                    <div id="dbg_cookie">
                        <div class="dbg_info">
                            '.dump_array($_COOKIE).'
                        </div>
                    </div>';

            //$user1
            if (!empty($user1)) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="User" href="#dbg_user">User</a>';

                $this->html .= '
                    <div id="dbg_user">
                        <div class="dbg_info">
                            '.dump_array($user1).'
                        </div>
                    </div>';
            }

            //$libhtml

            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="Libhtml" href="#dbg_libhtml">Libhtml</a>';

            $this->html .= '
                    <div id="dbg_libhtml">
                        <div class="dbg_info">
                            '.$this->dump().'
                        </div>
                    </div>';

            //DB Report

            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="DB-stats" href="#dbg_dbstats">DB-stats</a>';

            $this->html .= '
                    <div id="dbg_dbstats">
                        <div class="dbg_info">
                            '.$this->db_print_query_stats().'
                        </div>
                    </div>';

            $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="DB-explain" href="#dbg_dbexplain">DB-explain</a>';

            $this->html .= '
                <div id="dbg_dbexplain">
                    <div class="dbg_info">
                        '.$this->db_print_explain_stats().'
                    </div>
                </div>';

            //Resource usage
            if (function_exists("getrusage")) {

                $usage = getrusage();

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="Res. usage" href="#dbg_stats">Res. usage</a>';

                $this->html .= '
                    <div id="dbg_stats">
                        <div class="dbg_info">
                            '.open_table('100%').
                            $this->render_table_row("Page render time",microtime(true) - $page_start_time).
                            $this->render_table_row("User time",$usage['ru_utime.tv_sec']+$usage['ru_utime.tv_usec']/1000000).
                            $this->render_table_row("System time",$usage['ru_stime.tv_sec']+$usage['ru_stime.tv_usec']/1000000).
                            $this->render_table_row("Current memory usage",print_filesize("",memory_get_usage(),4)).
                            $this->render_table_row("Peak memory usage",print_filesize("",memory_get_peak_usage(),4)).
                            $this->render_table_row("No of page faults",$usage['ru_majflt']).
                            close_table().'
                        </div>
                    </div>';
            }

            // Classes
            $all_classes = get_declared_classes();

            $user_defined_classes=array();

            $leave_out=array("Crypt","DB","Object","User","Libhtml","Cache","Intrusion","View","IDS_Init","Swift_Log","Swift_Log_DefaultLog","");

            foreach($all_classes as $class){
                if (!in_array($class,$leave_out)) {
                    $p = new ReflectionClass($class);
                    if ($p->isUserDefined() && in_array("dump",get_class_methods($class))) $user_defined_classes[]=$class;
                }
            }

            if (!empty($user_defined_classes)) {

                $toolbar .= '<a class="jbox jbox_modal" data-close-overlay="true" title="Classes" href="#dbg_classes">Classes</a>';

                $this->html .= '
                        <div id="dbg_classes">
                            <div class="dbg_info">
                                '.dump_array($user_defined_classes,array('title'=>"User-defined classes",'dstate'=>'hidden'));

                foreach($GLOBALS as $key=>$value){

                    if (is_object($value)){
                        $class_name = get_class($value);
                        if (in_array($class_name,$user_defined_classes)) {
                            if (in_array("dump",get_class_methods($class_name))) {
                                $this->html .= $value->dump(array('title'=>"Class '$class_name'; instance name '$key'"));
                            } else {
                                $this->html .= dump_array($value,array('title'=>"Class '$class_name'; instance name '$key'",'dstate'=>'hidden'));
                            }
                        }
                    }
                }

                $this->html .= '
                            </div>
                        </div>';

            }


            $toolbar .= '
                    </div>';

            $this->html .= $toolbar;

        } else {
            unset($_SESSION['errors']);
        }

        if (empty($this->public)) {
            $this->html .= '<script type="text/javascript">
                window.name="'.make_seo_title($this->plain_title).'";
                var SYSTEM_ROOT = "'. $cfg['root'].'";
                var USER_DATE ="'.phpdtojsd($user1->preferences->dateformat).'";
                var POPUP_SIZE = "'.($user1->preferences->popup_size).'";
                var USERNAME = "'.$_SESSION['username'].'";
                var ENC_PAGE = "'.get_enc_page().'";
                var set = '.($cfg['session_timeout']*1000-120000).';';
                if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') $this->html .= 'var limce = ",loremipsum";';
                else $this->html .= 'var limce = "";';
            $this->html .= '</script>';

            // these files are about to be minified
            $minify_js = array(
                $cfg["source_root"] . "js/jquery.min.js",
                $cfg["source_root"] . "js/jquery-ui.min.js",
                $cfg["source_root"] . "js/jquery.functions463.js",
                $cfg["source_root"] . "js/jquery.jbox.js",
                $cfg["source_root"] . "js/jquery.jfull.js",
                $cfg["source_root"] . "js/jquery.jeditable.mini.js",
                $cfg["source_root"] . "js/jquery.tablesorter.js",
                $cfg["source_root"] . "js/jquery.tmpl.min.js",
                $cfg["source_root"] . "js/jquery.shiftselect.js",
                $cfg["source_root"] . "js/jquery.validate.js",
                $cfg["source_root"] . "js/jquery.timepicker.js",
                $cfg["source_root"] . "js/jquery.cookies.2.2.0.min.js",
                $cfg["source_root"] . "js/jquery.cookie.js",
                $cfg["source_root"] . "js/jquery.treeview.js",
                $cfg["source_root"] . "js/jquery.timeout.js",
                $cfg["source_root"] . "js/jquery.rotate-min.js",
                $cfg["source_root"] . "js/jquery.iframe-transport.js",
                $cfg["source_root"] . "js/jquery.fileupload.js",
                $cfg["source_root"] . "js/jquery.fileupload-process.js",
                $cfg["source_root"] . "js/jquery.fileupload-ui.js",
                $cfg["source_root"] . "js/jquery.shiftselect.js",
                $cfg["source_root"] . "js/jquery.tinymce.min.js",
                $cfg["source_root"] . "js/jquery.rangy.min.js",
                $cfg["source_root"] . "js/init246.js"
            );

            // load iPad specific js
            if (isset($_SESSION['RISK_USER_OS_GENERIC']) && $_SESSION['RISK_USER_OS_GENERIC'] == "iPad") $minify_js[] = $cfg["source_root"] . "js/jquery.ui.touch-punch.min.js";

            // load debug js only for development mode
            if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') $minify_js[] = $cfg["source_root"] . "js/debug.js";

            // don't minify if this is a dev env, much faster for debug
            if (isset($cfg['ENV']) && in_array(strtolower($cfg['ENV']), array('dev', 'local-dev'))) {
                foreach ($minify_js as $one_file) {
                    $this->html .= '<script type="text/javascript" src="' . str_replace($cfg['source_root'], $cfg['root'], $one_file). '"></script>';
                }
            } else {
                $this->html .= jMinifier::minify($minify_js);
            }
        }

        // Unset record of posted actions
        unset($_SESSION['actions']);

        $this->html .= $this->js;

        $this->html .= '</body>
        </html>';
        // END OF PAGE HTML

    }

    public function db_print_query_stats(){
        global $db;

        $query_stats = $db->get_query_stats();

        $query_stats['stats'] = sortByProp($query_stats['stats'],'time',true,false);

        $table = '

                <table summary="DB statistics" class="summary db_debug action_form details_form" style="width: 100%;">
                    <tr>
                        <th style="text-align:left;"></th>
                        <th style="text-align:left;">Rows</th>
                        <th style="text-align:left;">Time</th>
                        <th style="text-align:left;">Memory</th>
                        <th style="text-align:left;">Queries</th>
                        <th style="text-align:left;">Arguments</th>
                    </tr>
                    <tr>
                        <th style="text-align:left;"></th>
                        <th style="text-align:left;">'.$query_stats['total_rows'].'</th>
                        <th style="text-align:left;">'.sprintf("%.6f", $query_stats['total_time']).'</th>
                        <th style="text-align:left;">'.number_format($query_stats['total_memory']).'</th>
                        <th style="text-align:left;">'.count($query_stats['stats']).'</th>
                        <th style="text-align:left;"></th>
                    </tr>';

        $i=0;

        foreach ($query_stats['stats'] as $stat) {

            if (is_string($stat['sql'])) {
                $query_str = $stat['sql'];
            } elseif (is_object($stat['sql']) && ($stat['sql'] instanceof PDOStatement)) {
                $query_str = $stat['sql']->queryString;
            }

            $i++;

            $where_args = array();
            $where_string = '';
            if (!empty($stat['where']) && !empty($stat['where'][1])){

                $where_string .= '
                    <table class="action_form details_form" width="100%">';

                foreach(array_values($stat['where'][1]) as $key=>$value) {
                    $where_string .= '
                        <tr><th>'.$stat['where'][2][$key].'</th><td>'.$value.'</td></tr>';
                }

                $where_string .= '
                    </table>';
            }

            $table .= '
                    <tr valign="top">
                        <td>'.$i.'</td>
                        <td>'.$stat['rows'].'</td>
                        <td>'.sprintf("%.6f", $stat['time']).'</td>
                        <td>'.number_format($stat['memory']).'</td>
                        <td>
                            <pre class="prettyprint lang-sql sql">'.wordwrap($query_str, 105,"<br/>",true) .'</pre>
                        </td>
                        <td>'.$where_string.'</td>
                    </tr>';
        }

        $table .= '
                </table>';

        return $table;
    }

    function db_print_explain_stats(){
        global $db;

        $explain_stats = $db->get_explain_stats();

        $html = '';

        if (!empty($explain_stats)) {

            foreach ($explain_stats as $stat) {

                $html  .= '
                    <table class="action_form details_form">
                        <tr>
                            <th style="width: 200px;">Query</th>
                            <th style="text-align:left;" colspan=9>Explain</th>
                        </tr>
                        <tr valign="top">
                            <th rowspan="' . (count($stat['explain'])+1) . '" align="left">' . wordwrap($stat['sql'], 40, "<br/>", true) . '</th>
                            <th style="text-align:left;">Sel. Type</th>
                            <th style="text-align:left;">Table</th>
                            <th style="text-align:left;">Type</th>
                            <th style="text-align:left;">Poss. Keys</th>
                            <th style="text-align:left;">Key</th>
                            <th style="text-align:left;">Key Len.</th>
                            <th style="text-align:left;">Ref</th>
                            <th style="text-align:left;">Rows</th>
                            <th style="text-align:left;">Extra</th>
                        </tr>';

                foreach ($stat['explain'] as $explain) {

                    $html .= '
                        <tr valign="top">
                            <td>' . $explain->select_type . '</td>
                            <td>' . $explain->table . '</td>
                            <td>' . $explain->type . '</td>
                            <td>' . $explain->possible_keys . '</td>
                            <td>' . $explain->key . '</td>
                            <td>' . $explain->key_len . '</td>
                            <td>' . $explain->ref . '</td>
                            <td>' . $explain->rows . '</td>
                            <td>' . $explain->Extra . '</td>
                        </tr>';

                }

                $html .= '
                    </table><br/>';

            }

        }

        return $html;
    }

    function form_page_end() {
        if (!isset($_POST["jbox_opened"])) $this->html .= "</div>";
        $this->html .= "</div>" . $this->js;
    }

    function navigation_tabs(){
        global $cfg, $db, $user1, $title, $active_app, $tab, $cache, $history_item, $app_count;
        $cache = new Cache(array('user_id'=>$user1->id));

        $this->html .= '
                                            <div class="side_options jquery_tabs">
                                                <ul class="side_tab_menu">';
                                                    if ($this->show_sitemap) {
                                                        $this->html .= '<li class="sitemap">
                                                            <a class="tooltip" href="#tab_sitemap" title="Quick navigation">
                                                                <i class="fa fa-bars"></i>
                                                            </a>
                                                        </li>';
                                                    }
                                                    $this->html .= '<li class="history">
                                                        <a class="tooltip" href="#tab_history" title="Latest visited pages">
                                                            <i class="fa fa-reply"></i>
                                                        </a>
                                                    </li>
                                                    <li class="bookmarks">
                                                        <a class="tooltip" href="#tab_bookmarks" title="Favourites">
                                                            <i class="fa fa-star"></i>
                                                        </a>
                                                    </li>
                                                </ul>';

                                                // sitemap
                                                if ($this->show_sitemap) {
                                                    $this->html .= '<div class="ui-tabs-hide" id="tab_sitemap">
                                                        <ul class="treeview" id="sitemap">
                                                            <li>
                                                                '. $cache->retrieve_cache('sitemap_tree') . '
                                                            </li>
                                                        </ul>
                                                    </div>';
                                                }

                                                // history
                                                $history = array_reverse($_SESSION['history']);
                                                $this->html .= '<div class="ui-tabs-hide" id="tab_history">
                                                    <ul class="history">';

                                                        $i = 0;
                                                        foreach ($history as $item){
                                                            if ($i <= 7) {
                                                                $this->html .= '<li>
                                                                    <a href="' . $item['url'] . '">' . $item['page'] . '</a>
                                                                </li>';
                                                            }
                                                            $i++;
                                                        }

                                                    $this->html .= '</ul>
                                                </div>';

                                                // bookmarks
                                                $home_page_name = (isset($user1->preferences->landpage_name)) ? $user1->preferences->landpage_name : '<span class="l4">Home</span>';
                                                $this->html .= '<div class="ui-tabs-hide" id="tab_bookmarks">
                                                    <ul class="favsnhome">
                                                        <li class="fav home_page_link">
                                                            <a class="tooltip" href="'.$cfg['root'].$user1->preferences->landpage.'" title="Go to your Home page">'.$home_page_name.'</a>
                                                        </li>';

                                                        $bookmarks = $db->select("*","system_user_bookmarks",
                                                        array("WHERE user_id=?", array('user_id' => $user1->id), array('integer')),
                                                        array('order_by' => "ORDER BY sort_order ASC"));

                                                        foreach($bookmarks as $item){
                                                            // remove bookmark in a loop, because it can be repeated in get_enc_page
                                                            if (my_get('remove_bookmark')== $item->id){
                                                                $db->delete("system_user_bookmarks", array("WHERE id = ?", array($item->id), array('integer')));
                                                                $_SESSION['feedback'] .= g_feedback("success", "Bookmark has been removed");

                                                            } else {
                                                                // (strlen($item->name)>100) ? $name = substr($item->name, 0,100) . "..." : $name = $item->name;
                                                                $this->html .= '<li class="fav">
                                                                    <a href="' . $item->url . '">' . $item->name . '</a>
                                                                    <a class="rlnk mns tooltip" title="Remove bookmark" href="' . get_enc_page(array("remove_bookmark"=>$item->id)) . '"><i class="fa fa-times"></i></a>
                                                                </li>';
                                                            }

                                                        }

                                                    $this->html .= '</ul>
                                                    <div class="bookmarks section opened">
                                                        <h2>Bookmarks</h2>
                                                        <ul class="favsnhome">
                                                            <li>
                                                                <a class="home_add tooltip set_home_page" href="#" title="Set this page as Home">
                                                                    <i class="fa fa-home"></i>Set this page as Home
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="jbox jmini tooltip" href="' . encrypt_url($cfg['root'] . 'add_bookmark.php?page=' . $history_item['page']) . '" title="Add this page to bookmarks">
                                                                    <i class="fa fa-bookmark"></i>Add this page to bookmarks
                                                                </a>
                                                            </li>';

                                                            if ($user1->{"preferences.php"}) {
                                                                $this->html .= '<li>
                                                                    <a class="home_add tooltip" href="' . encrypt_url($cfg['root'] . 'preferences.php?tab=bookmarks') . '" title="Manage bookmarks">
                                                                        <span>Manage your bookmarks</span>
                                                                    </a>
                                                                </li>';
                                                            }

                                                        $this->html .= '</ul>
                                                    </div>
                                                </div>
                                            </div>';
    }

    function form_start($action = "", $target="_self", $method="post", $name="main", $enctype="", $onsubmit="", $monitor_ch = false){
        global $cfg, $my_get;

        $action = $_SERVER['REQUEST_URI'];

        //This is the override in case show_popup is not empty
        if (!empty($_SESSION["show_popup"]) && !empty($_SESSION["show_popup"]["original_action"])){
            $action = $_SESSION["show_popup"]["original_action"];
        }

        $_SESSION['form_token'] = md5(uniqid() . $cfg['md5_salt']);
        $_SESSION['form_token_time'] = time();

        $html = '<form name="'.$name.'" method="'.$method.'" action="'.$action.'" target="'.$target.'"';
        if (!empty($onsubmit)) $html .= ' onsubmit="'.$onsubmit.'"';
        if (!empty($enctype)) $html .= ' enctype="'.$enctype.'"';
        $html .= ' style="text-align: left; width: 100%;">';

        $html .= $this->render_form_table_row_hidden("form_token", $_SESSION['form_token']);
        $html .= $this->render_form_table_row_hidden("form_scroll", my_request('form_scroll'));
        $html .= $this->render_form_table_row_hidden("jbox_id", my_request('jbox_id'));
        $html .= $this->render_form_table_row_hidden("self_submit", null);

        if ($monitor_ch) $html .= $this->render_form_table_row_hidden("haschng", my_request("haschng"));

        if (!empty($my_get["continue"])) $html .= $this->render_form_table_row_hidden("wasp", $my_get["continue"]);

        if (!empty($_SESSION["show_popup"]) && !empty($_SESSION["show_popup"]["original_action"])){
            $html .= $this->render_form_table_row_hidden("original_action", $_SESSION["show_popup"]["original_action"]);
        }
        $html .= '<div class="form_content jtab_visible '.(!empty($monitor_ch) ? 'monitor_ch' : '').'">';

        return $html;
    }

    function form_end($clear = true){
        $html = '</div>
                </form>';
        if ($clear) $html .= '<div class="clear"></div>';

        return $html;
    }

    function filter_form_start($action = "", $target="", $method="post", $name="main", $enctype="", $onsubmit="") {
        global $cfg, $filter_form;
        $filter_form = true; // to use in render table rows
        $html = '<form name="'.$name.'" id="'.$name.'" method="'.$method.'" action="'.$_SERVER["REQUEST_URI"].'" target="'.$target.'">';

        //If we have ID, md5 it for form token, otherwise generate a random one.
        $_SESSION['form_token'] = md5(uniqid() . $cfg['md5_salt']);
        $_SESSION['form_token_time'] = time();

        $html .= $this->render_form_table_row_hidden("form_token",$_SESSION['form_token']);
        $html .= '<div class="form_content jtab_visible">';
        return $html;
    }

    function render_table_row($title, $value="", $options = array()) {
        global $cfg;

        $defaults = array(
            'th_width'=>"200px",
            'th_align'=>'right',
            'tooltip'=>"",
            'class'=>"",
            'display'=>true,
            'add'=>array(),
        );
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        if ($defaults['display']){
            $html = '<tr>
                <th style="min-width: '.$defaults['th_width'].'; width: '.$defaults['th_width'].'; text-align: '.$defaults['th_align'].';">' .
                    $title;
                    if ($defaults['tooltip']) $html .= '<span class="tooltip ico_binfo" title="'.$defaults['tooltip'].'"><i class="fa fa-info"></i></span>';
                $html .= '</th>

                <td class="'.$defaults['class'].'">' .
                    $value;

                    if (!empty($defaults["add"]["text"]) && !empty($defaults["add"]["url"]) && !empty($defaults["add"]["permission"]) && $defaults["add"]["permission"]) {
                        $html .= '<span class="add'. (!empty($defaults["add"]["celltext"]) ? ' ctxt' : '') .'">'.(!empty($defaults["add"]["celltext"]) ? $defaults["add"]["celltext"] : '').'
                            <a data-jbox-jlink="forward" href="'.encrypt_url($defaults["add"]["url"]).'">
                                '.$defaults["add"]["text"].'
                            </a>
                        </span>';
                    }

                $html .= '</td>
              </tr>';

            return $html;
        }
    }

    function render_table_row_file($title, $files="", $options = array()) {

        $defaults = array(
            'display_empty' => true,
            'tooltip'=>"",
            'th_width'=>"200px",
            'display'=>true,
            'class'=>"",
            'display_size' => true,
            'display_filename' => true,
            'display_icon' => true
        );

        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;
        if (empty($files) && $defaults['display_empty'] == false) return '';

        $html = '';

        if ($defaults['display'] && ((empty($files) && $defaults['display_empty']) || !empty($files))) {

            $tooltip = (!empty($defaults['tooltip'])) ? '<span class="tooltip ico_binfo" title="' . $defaults['tooltip'] . '"><i class="fa fa-info"></i></span>' : '';

            $html = '
            <tr>
                <th style="min-width: '.$defaults['th_width'].'; width: '.$defaults['th_width'].';">'.$title.$tooltip.'</th>
                <td class="' . $defaults['class'] . '">'.$this->print_file_cell($files,$defaults).'</td>
            </tr>';

            return $html;
        }

    }

    function print_file_cell($files='',$options=array()){

		$defaults = array(
			'display_empty' => true,
			'display_size' => true,
			'display_filename' => true,
			'display_icon' => true,
        );

        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;
        if (empty($files) && $defaults['display_empty'] == false) return '';

        $html = '';

        if(!empty($files)){

            if (is_null(json_decode($files)) && !is_serial($files)){
                //Single file!
                $html = $this->print_single_file($files,$defaults);

            } elseif (is_serial($files)){
                //Old format
                $html = $this->print_file_array(@unserialize($files),$defaults);

            } elseif (!is_null(json_decode($files))) {
                //Latest format, multi file
                $html = $this->print_file_array(json_decode($files),$defaults);

            }
            return $html;
        }

    }

    function print_single_file($file, $defaults) {
        global $cfg;

		$html = '';

		if(is_file($cfg['secure_dir'] . $file)){

			//Strip the timestamp
	        $base_file_name = substr(basename($file),14);
			$path_parts = pathinfo($file);

	        //Test for base64 encoding; use . as a test of a "good" filename
	        if (base64_decode(substr($path_parts['filename'],14),true) !== false){
	            $decoded_file_name = base64_decode(substr($path_parts['filename'],14));
	        } elseif (strpos($base_file_name,'.')!==false){
	            $decoded_file_name = $base_file_name;
	        } else {
	            return 'File name error!';
	        }

	        $filename = ($defaults['display_filename']) ? $decoded_file_name : '';
	        $tooltip = ($defaults['display_filename']) ? '' : 'title="'.$decoded_file_name.'"';
	        $file_size = ($defaults['display_size'])? ' ('.print_filesize($cfg['secure_dir'] . $file).')' : '';

	        $ext = strtolower($path_parts['extension']);
	        $html = '
			<div class="file_wrap tooltip" '.$tooltip.'>
	            <a href="'.encrypt_url($cfg['root'] . "includes/downloader.php?file_name=".urlencode($file)).'">
					'.$filename.$file_size.'
	            </a>
	        </div>';

		}

        return $html;

    }

	// public function print_single_file($file, $defaults)
	// {
	// 	global $cfg;
	//
	// 	//Strip the timestamp
	// 	$base_file_name = !empty($defaults['strip_timestamp']) ? substr(basename($file), 14) : basename($file);
	//
	// 	//Test for base64 encoding; use . as a test of a "good" filename
	// 	if (base64_decode($base_file_name, true) !== false) {
	// 		$decoded_file_name = base64_decode($base_file_name);
	// 	} elseif (strpos($base_file_name, '.')!==false) {
	// 		$decoded_file_name = $base_file_name;
	// 	} else {
	// 		return 'File name error!';
	// 	}
	//
	// 	$ext = strtolower(extension($decoded_file_name));
	//
	// 	$filename = ($defaults['display_filename']) ? substr(basename($file),0,14).$decoded_file_name : '';
	// 	if (!empty($defaults['display_name'])) $filename = $defaults['display_name'];
	// 	$file_size = ($defaults['display_size'])? '('.print_filesize($cfg['secure_dir'] . $file).')' : '';
	// 	$timestamp = ($defaults['show_timestamp'])? '('.date('d M Y, H:i:s',filemtime($cfg['secure_dir'] . $file)).')' : '';
	// 	$icon = '';
	//
	// 	if (is_file($cfg['secure_dir'] . $file)){
	// 		//Copy file
	// 		if (!is_file($this->export_folder.dirname($file).'/'.$filename)){
	// 			if (!copy($cfg['secure_dir'] . $file, $this->export_folder.dirname($file).'/'.$filename)) {
	// 				die ('Failed to copy '.dirname($file).'/'.$filename.' file.');
	// 			}
	// 		}
	//
	// 		if (!empty($defaults['display_icon'])){
	// 			if ($ext=='pdf') {
	// 				$icon = '<span class="file_icon fa fa-file-pdf-o"></span>';
	// 			} elseif ($ext=='doc' || $ext=='docx') {
	// 				$icon = '<span class="file_icon fa fa-file-word-o"></span>';
	// 			} elseif ($ext=='ppt' || $ext=='pptx') {
	// 				$icon = '<span class="file_icon fa fa-file-powerpoint-o"></span>';
	// 			} elseif ($ext=='xls' || $ext=='xlsx') {
	// 				$icon = '<span class="file_icon fa fa-file-excel-o"></span>';
	// 			} elseif ($ext=='jpg' || $ext=='jpeg' || $ext=='png' || $ext='gif') {
	// 				$icon = '<span class="file_icon fa fa-file-image-o"></span>';
	// 			} else {
	// 				$icon = '<span class="file_icon fa fa-file-o"></span>';
	// 			}
	// 		}
	//
	// 		return '
	// 		<div class="file_wrap">
	// 			<a href="'.dirname($file).'/'.$filename.'" target="_blank">
	// 				<span class="'.(!empty($defaults['use_file_colours']) ? 'txtwrap txtwrap_'.$ext : '').'">
	// 					'.$icon.' '.$filename.'
	// 				</span>
	// 				 '.$file_size.'&nbsp;'.$timestamp.'
	// 			</a>
	// 		</div>';
	// 	} else {
	// 		return '';
	// 	}
	//
	// }

    function print_file_array($files, $defaults) {
        $html = '';
        foreach($files as $file) $html .= $this->print_single_file($file, $defaults) . '<div class="clear"></div>';
        return $html;
    }

    function render_table_row_progress($value="0", $id=null, $th_width="200px", $tooltip="") {
        global $cfg;

        $heat = "green";
        if ($value < 20) $heat = "red";
        elseif ($value < 40) $heat = "orange";
        elseif ($value < 60) $heat = "yellow";
        elseif ($value < 80) $heat = "lime";

        $html = '
		<tr>
            <th style="min-width: '.$th_width.';">'.(!empty($tooltip) ? '<span class="ico_binfo"><i class="fa fa-info"></i></span>' : '').'</th>
            <td class="tooltip '.$heat.'" title="'.$tooltip.'">
                <div class="inputwrap">
                    <div class="td_ttime">
                        <div id="'.$id.'" class="ui-slider"></div>
                        <span class="helper">'.$value.'%</span>
                    </div>
                </div>
            </td>
        </tr>
		script>
            $(function() {
                $("#'.$id.'").progressbar({ value: '.$value.' });
            });
        </script>';

        return $html;
    }

    // this is an old version of the form submit buttons and action wrap
    // should be gradually removed and replaced with the few functions below
    function render_submit_button($name, $form_value="Submit", $options = array()){
        global $crypt, $my_post, $my_get, $cfg, $user1;

        error_log('render_submit_button() is deprecated, please update to actions() method');

        return $this->render_actions(
            array(
                $this->render_button($name, $form_value, (!empty($options["post_functions"][$name]) ? $options["post_functions"][$name] : array()) )
            ),
            array(
                "show_cancel"=>(isset($options["show_cancel"]) ? $options["show_cancel"] : true),
                "show_delete"=>(isset($options["show_delete"]) ? $options["show_delete"] : true),
                "pause"=>(isset($options["pause"]) ? $options["pause"] : false),
            )
        );

    }

    // used to render actions panel at the bottom of the form
    // $list_of_actions is not required, only first argument
    function render_actions($list_of_actions = array(), $options = array()){
        global $crypt, $my_post, $my_get, $cfg, $user1;

        $defaults = array(
            'show_wrap'=>true, // '<div class="actions"> wrap html
            'show_prompt'=>true, // 'Are you sure...' prompt html
            'show_cancel'=>true, // 'Cancel' button
            'pause'=>false, // 'Save draft' button
            'block_page'=>true, // if a Please wait feedback should be shown on submit
            'show_delete'=>true, // 'Delete' link
            'object_name'=>'', // used for Delete link
            'object_id'=>'', // used for Delete link
        );

        if (!empty($options)) foreach($options as $key=>$value) $defaults[$key] = $value;

        $html = '';
        if ($defaults["show_wrap"]) $html .= '<div class="actions'.(!empty($defaults["block_page"]) ? ' block_page' : '').'">';

            // append all submit buttons
            // all buttons are already formatted as the html at this point
            if (!empty($list_of_actions)){
                foreach($list_of_actions as $action){
                    $html .= $action;
                }
            }

            // cancel and draft buttons
            if ($defaults['show_cancel']) $html .= $this->render_cancel_button();
            if ($defaults['pause'] && empty($this->dont_show_pause)) $html .= $this->render_save_draft_button();
            if ($defaults['pause'] && !empty($my_get["continue"])) $html .= $this->render_discard_draft_button();

            // Build and show 'Delete link'
            // Don't show link for drafts, edit or delete forms
            if (
                $defaults['show_delete'] // from $defaults
                && !empty($defaults['object_name'])
                && !empty($defaults['object_id']) // from standard_form
                && (strpos($this->sys_path, "/edit_") != 0) // show only for edit forms
                && empty($my_get["continue"]))
            {

                $delete_path = str_replace("/edit_", "/delete_", $this->sys_path);

                $html .= href_link(array(
                    "permission"=>$user1->$delete_path,
                    "url"=>$cfg['root'] . $delete_path . "?" . $defaults['object_name'] . "_id=" . $defaults['object_id'],
                    "popup"=>true,
                    "button"=>false,
                    "text"=>($user1->$delete_path) ? "Delete" : '',
                    "clear"=>false,
                    "float"=>"left",
                    "class"=>"ancdel"
                ));
            }

            // are you sure prompt
            if ($defaults['show_prompt']) $html .= $this->render_discard_prompt();

        if ($defaults["show_wrap"]) $html .= '</div>';

        return $html;
    }

    // only used to render submit buttons, without any additional html
    function render_button($name, $form_value = "Submit", $post_functions = array()){
        global $cfg, $crypt;

        // if post_functions is empty, rebuld it from the object backtrace
        if (empty($post_functions)){

            //Find a caller class if any
            foreach(debug_backtrace() as $item) {

                if (isset($item['class']) && isset($item['object']->object_pk)) {

                    $object_name = $item['object']->object_name;
                    $action_class = $item['object']->class_name;

                    // work out action name; convention is to have $action_$object_name
                    // for any exceptions - fill in the function options!
                    $action_name = str_replace("_" . $object_name,"",$name);

                    if (!empty($action_name) && empty($action)){
                        $action = ($action_name == "add") ? "insert" : $action_name;
                    }

                    // Prepare post functions array
                    $post_functions = array(
                        $object_name,
                        (!empty($action)) ? $action : null,
                        $action_class,
                        (!empty($item['object']->{$item['object']->object_pk})) ? $item['object']->{$item['object']->object_pk} : null
                    );

                    break;

                }
            }
        }

        $html = '<input type="submit" name="'.$name.'" value="'.$form_value.'" class="btn blue right"/>';

        if (!empty($post_functions)) {

            $html .= '<input type="hidden" name="posted_actions"  id="posted_'.$name.'"  value="'.( $crypt->str_encrypt(serialize($post_functions)) ) .'"/>';

        } else {

            $html .= '<input type="hidden" name="posted_actions"  id="posted_'.$name.'"  value="'.$name.'"/>';

        }

        return $html;
    }

    // only renders 'Cancel' button, without any additional html
    function render_cancel_button($name = "Cancel"){
        return '<input data-cancel="true" type="button" value="'.$name.'" class="cancel btn right"/>';
    }

    // only renders 'Save draft' button, without any additional html
    function render_save_draft_button($name = "Save draft"){
        return '<input data-save-draft="true" type="button" value="'.$name.'" class="btn"/>';
    }

    // only renders 'Discard draft' button, without any additional html
    function render_discard_draft_button($name = "Discard draft"){
        return '<input data-discard-draft="true" type="button" value="'.$name.'" class="btn"/>';
    }

    // render 'Are you sure...' prompt
    function render_discard_prompt(){
        return '<div class="prompt">
            <span class="note"><b>You have unsaved changes.</b> Are you sure you would like to discard your changes?</span>
            <input data-prompt-cancel="true" type="button" value="Yes" class="btn blue right">
            <input data-prompt-back="true" type="button" value="No" class="btn right">
        </div>';
    }

    function render_form_table_row_hidden($name, $value) {
        return '<input type="hidden" name="'.$name.'" id="'.$name.'" value="'.$value.'" />';
    }

    function render_form_table_row_base($formname, $value="", $fieldname, $id="", $options = array(), $input_type='default'){
        global $cfg, $filter_form, $my_get, $my_post, $crypt, $libhtml, $defaults;

        // don't show this form element if there is a form fields filter present
        if (!empty($my_get["show_fields"]) && !in_array($id, $my_get["show_fields"])){
            return false;
        }

        $defaults = array(
            'input_type' => $input_type,
            'th_width'=> (isset($filter_form) && ($filter_form)) ? "100px" : "200px",
            'td_width'=> (isset($filter_form) && ($filter_form)) ? "300px" : "auto",
            'td_style'=>'',
            'th_align'=>'right',
            'tooltip'=>'',
            'required'=>false,
            'unique'=>false, // prevents form from being submitted if an item with that name already exists
            'soft_unique'=>false, // just shows a warning that the item with that name already exists
            'unique_id'=>'', // override for user preferences unique email / username check (not present in object like it should be)
            'extra'=>'',
            'multi'=>'single',
            'class'=> (in_array($input_type,array('date','time','datetime','slider'))) ? '' : 'form_style',
            'rows'=>5,
            'cols'=>0,
            'rte'=>false,
            'limit'=>false,
            'onchange'=>'',
            'selection'=>'Select ...',
            'type'=>'',
            'width'=>'',
            'allowed_empty'=>true,
            'break'=>false,
            'radio_break'=>0,
            'min_value'=>0,
            'max_value'=>100,
            'steps'=>0,
            'where'=>'',
            'no_of_chars'=>4,
            'auto_select'=>false,
            'self_submit'=>false,
            'js'=>'',
            'minimal'=> false,
            'add'=> array(),
            'url_preview'=>false, // appends an url preview text for page SEO title
            'regex'=>'', // used for a custom validation
            'regex_message'=>'', // related to the above, custom validation error message
        );
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        // Special defaults
        // Limits
        if (
                in_array($defaults['self_submit'], array('password', 'text'))
                && !isset($defaults['limit'])
        ) $defaults['limit'] = true;

        // Date, time, td widths
        if (in_array($defaults['input_type'],array('date','time','datetime','slider'))) {
            $defaults['td_width'] = "100px";
        }

        // Self submit
        if ($defaults['self_submit']) $defaults["class"] .= " self_submit";

        // RTE
        if ($defaults['rte']) $defaults['class'] .= " rte";

        // url preview
        if ($defaults['url_preview']) {
            $defaults['class'] .= " url_preview";
            $defaults['extra'] .= " data-website='".$cfg["website"]."'";
        }

        // Find a caller class if any
        foreach(debug_backtrace() as $item) {

            if(isset($item['class']) && isset($item['object']->object_pk)) {

                //auto load class and check url(images/shadow.png) 9 9 repeatif table_field in $formname = object_name[table_field] is nullable;
                $form_and_key = explode("[", $formname);

                if (isset($form_and_key[1])) {

                    $db_required_field = FALSE;
                    $key = rtrim($form_and_key[1],"]");
                    $data_class = new $item['object']->class_name;

                    // table_field exists as a database table field
                    if (isset($data_class->table_types[$key])) {

                        $defaults['limit'] = true;
                        $field_properties = $data_class->table_types[$key];
                        $data_type = strtolower($field_properties->DATA_TYPE);

                        // required from database, if it is not in null_check_exceptions
                        if (in_array($key, $data_class->null_check_exceptions) === FALSE) {

                            if (
                                $field_properties->IS_NULLABLE == "NO"
                                && (
                                    $field_properties->COLUMN_DEFAULT == ''
                                    || is_null($field_properties->COLUMN_DEFAULT)
                                )
                            ) {

                                $db_required_field = TRUE;

                                if (in_array($data_type, array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'))) {

                                    if ($input_type != "autocomplete") $defaults['class'] .= " digits";
                                    if (isset($field_properties->EXTRA) && (strpos($field_properties->EXTRA, 'auto_increment') !== FALSE) || (strpos($field_properties->COLUMN_DEFAULT, 'nextval') !== FALSE)) {
                                        $db_required_field = FALSE;
                                    }

                                } elseif (in_array($data_type, array('numeric', 'decimal', 'smallmoney', 'money', 'float', 'real', 'double', 'double precision'))) {
                                    $defaults['class'] .= " number";

                                } elseif (in_array($data_type, array('char','varchar','tinytext','mediumtext','text'))) {

                                    if ($defaults['limit']) {
                                        if (!is_null($field_properties->CHARACTER_MAXIMUM_LENGTH)) {
                                            $dblimit = $field_properties->CHARACTER_MAXIMUM_LENGTH;
                                        } else {
                                            $dblimit = 65535;
                                        }
                                        $data_limit = 'data-limit="'.$dblimit.'"'; // take the limit from database
                                    }

                                }
                            }
                        }

                        // limit from database
                        if ($defaults['limit']) {
                            if (!is_null($field_properties->CHARACTER_MAXIMUM_LENGTH)) {
                                $dblimit = $field_properties->CHARACTER_MAXIMUM_LENGTH;
                            } else {
                                $dblimit = 65535;
                            }

                            if (empty($dblimit) && isset($field_properties->COLUMN_TYPE)) {
                                preg_match('/(?<=\()(.+)(?=\))/is', $field_properties->COLUMN_TYPE, $dimensions);
                                if (isset($dimensions[0]) && !empty($dimensions[0])) {
                                    $dim_array = explode(",", $dimensions[0]);
                                    $precision = $dim_array[0];
                                    $scale = (isset($dim_array[1]))? $scale = $dim_array[1] : 0;
                                    $dblimit = $precision - $scale;
                                }
                                if (empty($dblimit) && $field_properties->COLUMN_TYPE == "double") {
                                    $dblimit = 53;
                                }
                            }

                            $data_limit = 'data-limit="'.$dblimit.'"'; // take the limit from database
                        }

                        // unique ajax
                        if ($defaults['unique'] || $defaults['soft_unique']){

                            $defaults['class'] .= ' unique valid_unique';
                            $defaults['class'] .= (!empty($defaults['soft_unique']) ? " soft_unique" : "");

                            if (!empty($defaults['unique_id'])) {
                                $id = $defaults['unique_id'];
                            } else {
                                $id = (!empty($item['object']->{$item["object"]->{"object_pk"}})) ? $item['object']->{$item["object"]->{"object_pk"}} : '';
                            }

                            $defaults['extra'] .= ' data-unique="'.$crypt->str_encrypt('table='.$item['object']->table.'&field='.$key.(!empty($id) ? '&id=' . $id : '' )).'"';

                        }

                    }
                }

                break;

            } else { // just extract key (used for "add" new option > prefill field)
                $form_and_key = explode("[", $formname);
                if (isset($form_and_key[1])) $key = rtrim($form_and_key[1],"]");

            }

        }

        // Regex
        if (!empty($defaults['regex'])) {
            $defaults['extra'] .= ' regex="'.trim($defaults['regex'],'/').'"';
            if (!empty($defaults['regex_message'])) {
                $defaults['extra'] .= ' data-regex-message="'.$defaults['regex_message'].'"';
            } else {
                $defaults['extra'] .= ' data-regex-message="Value format you have entered is not valid"';
            }
        }

        //Start form HTML
        $form = "";
        if (!$defaults['minimal']) { // if we want only input element to be returned

            if ($defaults['multi'] == "single" || $defaults['multi'] == "first") {

                $th_align = (!empty($defaults['th_align'])) ? 'text-align: '.$defaults['th_align'].';' : '';

                if (!isset($filter_form) && (!$filter_form)) {
                    $form .= '
                <tr>
                    <th style="width: '.$defaults['th_width'].'; min-width: '.$defaults['th_width'].';'.$th_align.'">';
                }

                $tooltip =  (!empty($defaults['tooltip'])) ? '
                        <span class="tooltip ico_binfo" title="'.$defaults['tooltip'].'"><i class="fa fa-info"></i></span>' : '';

                // required asterisk
                $required = ($defaults['required'] || !empty($db_required_field)) ? '
                        <span class="req tooltip" title="Required"><i class="fa fa-asterisk"></i></span>' : '';

                $form .= '
                        <label for="'.$id.'">'.$tooltip.$fieldname.$required.'</label>';

                $current_num = (!empty($value)) ? strlen($value) : '0';

                if (in_array($defaults['input_type'],array("hidden","text","autocomplete","default"))) {

                    $form .= ($defaults['limit'] && !empty($dblimit)) ? '
                        <span class="limit">
                            <span class="current">'.$current_num.'</span> / '.$dblimit : '';

                }

                $form .= '
                    </th>';
            }
            if ($defaults['onchange']) $defaults['extra'] .=' onchange="'.$defaults['onchange'].'"';

            $td_class = ($defaults['break']) ? 'class = "break"' : '';

            $form .= '
                    <td '.$td_class.$defaults['td_style'].'>';
        }

        if ($defaults['required'] || !empty($db_required_field)) $defaults['class'] .= " required";

        switch ($defaults['input_type']) {
            case "password":
                $form .= '
                        <div class="inputwrap">
                            <input class="'. $defaults['class'] . '" type="password" id="'.$id.'" name="'.$formname.'" '.$defaults['extra']. '/>
                        </div>';
                break;

            case "text":
                if (!isset($data_limit)) $data_limit = "";
                $value = (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) ? stripslashes(my_nl2br($value)) : my_nl2br($value);
                $form .= '
                        <div class="inputwrap">
                            <textarea '.$data_limit.' class="'.$defaults['class'].'" id="'.$id.'" name="'.$formname.'" rows="'.$defaults['rows'].'" cols="'.$defaults['cols'].'" '.$defaults['extra'].'>' . $value . '</textarea>
                        </div>';
                break;

            case "checkbox":
                $form .= '
                        <div class="inputwrap">
                            <input id="'.$id.'_" type="hidden" value="0" name="'.$formname.'"/>
                            <input class="checkbox '.$defaults['class'].'" type="checkbox" id="'.$id.'" value="1" name="'.$formname.'" '.$defaults['extra'];
                if ($value == 1) $form .= ' checked="checked"';
                $form .= '/>';
                $form .= '
                        </div>';
                break;

            case "radio":
                $form .= '<div class="inputwrap">';
                $select_value = $defaults['select_value'];
                $selection = $defaults['selection_array'];
                $id_field = $defaults['id_field'];
                $value_field = $defaults['value_field'];
                $option="";
                reset($selection);
                if (count($selection)>0) {
                    $i=1;
                    $style = (empty($defaults['radio_break'])) ? '' : 'width:'.floor(100/min($defaults['radio_break'],count($selection))).'%;float:left;';
                    if ((is_assoc_array($selection) && empty($defaults['type'])) || $defaults['type']=="associative") {
                        //hash array; ignore id_field and value_field
                        foreach($selection as $key=>$value) {
                            if ($style) $option .= '<div style="'.$style.'">';
                            $option .= "<input type=\"radio\" name=\"$formname\" style=\"vertical-align:bottom;\" value=\"$key\" id=\"id_".$id."_".$key."\" ";
                            if ($select_value==$key) $option .= " checked=\"checked\"";
                            $option .= " class=\"radio " . $defaults['class'] . "\" " . $defaults['extra'] . "><label class=\"radio\" for=\"id_".$id."_".$key."\">".htmlspecialchars($value)."</label>";
                            if ($style) {
                                $option .= '</div>';
                                if($i  %  $defaults['radio_break']  ==  0)  '<br/>';
                            }
                            if ($defaults['break']) $option .= '<div class="clear">&nbsp;</div>';
                            $i++;
                        }
                    } elseif ((is_object(current($selection)) && empty($defaults['type'])) || $defaults['type']=="object_array") {
                        //array of classes - most likely db->select; we need exact id_field and value_field
                        foreach($selection as $item) {
                            if ($style) $option .= '<div style="'.$style.'">';
                            $option .= "<input type=\"radio\" name=\"$formname\" style=\"vertical-align:bottom;\" value=\"" . $item->$id_field . "\" id=\"id_" .$id."_". $item->$id_field . "\" ";
                            if ($select_value==$item->$id_field) $option .= " checked=\"checked\"";
                            $option .= " class=\"radio " . $defaults['class'] . "\" " . $defaults['extra'] . "/><label class=\"radio\" for=\"id_".$id."_".$item->$id_field."\">" . htmlspecialchars($item->$value_field) . "</label>";
                            if ($style) {
                                $option .= '</div>';
                                if($i  %  $defaults['radio_break']  ==  0)  '<br/>';
                            }
                            if ($defaults['break']) $option .= '<div class="clear">&nbsp;</div>';
                            $i++;
                        }
                    } elseif ((is_sequential_array($selection) && empty($defaults['type'])) || $defaults['type']=="simple") {
                        //simple array
                        foreach($selection as $item) {
                            $item_id="id_".$id."_".$item;
                            if ($style) $option .= '<div style="'.$style.'">';
                            $option .= "<input type=\"radio\" name=\"$formname\" style=\"vertical-align:bottom;\" value=\"$item\" id=\"".$id."_".$item_id."\" ";
                            if ($select_value==$item) $option .= " checked=\"checked\"";
                            $option .= " class=\"radio " . $defaults['class'] . "\" " . $defaults['extra'] . "/><label class=\"radio\" for=\"".$id."_".$item_id."\">".htmlspecialchars($item)."</label>";
                            if ($style) {
                                $option .= '</div>';
                                if($i  %  $defaults['radio_break']  ==  0)  '<br/>';
                            }
                            if ($defaults['break']) $option .= '<div class="clear">&nbsp;</div>';
                            $i++;
                        }
                    }
                }
                $form .= $option;
                $form .= '</div>';
                break;

            case "selection" :

                $form .= '<div class="inputwrap">';
                    $empty_value = "";

                    if (isset($key)) {
                        if (isset($data_class->table_types[$key]->COLUMN_DEFAULT) && !is_null($data_class->table_types[$key]->COLUMN_DEFAULT)) {
                            $empty_value = $data_class->table_types[$key]->COLUMN_DEFAULT;
                        }
                    }
                    $select_value = $defaults['select_value'];
                    $selection = $defaults['selection_array'];
                    $id_field = $defaults['id_field'];
                    $value_field = $defaults['value_field'];
                    // dump_var($defaults['selection_array']);
                    // dump_var($defaults['value_field']);
                    $form .= "<select class=\"" . $defaults['class'] . "\" id=\"$id\" name=\"$formname\" " . $defaults['extra'] . ">";
                    $option = "";
                    if ($defaults['allowed_empty']) $option = "<option value=\"$empty_value\">". $defaults['selection'] ."</option>";
                    if (count($selection)>0) {
                        if ((is_assoc_array($selection) && empty($defaults['type'])) || $defaults['type']=="associative") {
                            //hash array; ignore id_field and value_field
                            foreach($selection as $key=>$value) {
                                $option .= '<option value="'.$key.'"';
                                if ($select_value == $key) $option .=' selected="selected"';
                                $option .= ">".htmlspecialchars($value)."</option>";
                            }
                        } elseif ((is_object(current($selection)) && empty($defaults['type'])) || $defaults['type']=="object_array") {
                            //array of classes - most likely db->select; we need exact id_field and value_field
                            foreach($selection as $item) {
                                $option .= '<option value="'.$item->$id_field.'"';
                                if ($select_value == $item->$id_field) $option .=' selected="selected"';
                                $option .= ">" . htmlspecialchars($item->$value_field) . "</option>";
                            }
                        } elseif ((is_sequential_array($selection) && empty($defaults['type'])) || $defaults['type']=="simple") {
                            //simple array
                            foreach($selection as $item) {
                                $option .= '<option value="'.$item.'"';
                                if ($select_value == $item) $option .=' selected="selected"';
                                $option .= ">".htmlspecialchars($item)."</option>";
                            }
                        }
                    }
                    $form .= $option . "</select>";
                $form .= '</div>';
                break;

            case "date":

                $hasdate = (!empty($value))  ?  "hasdate"  :  "";
                $min_date = (!empty($defaults["min_date"]))  ?  '  data-min_date="'.$defaults["min_date"].'"'  :  '';
                $max_date = (!empty($defaults["max_date"]))  ?  '  data-max_date="'.$defaults["max_date"].'"'  :  '';

                $form .= '
                <div class="inputwrap '.$hasdate.'">
                    <a href="#" class="cleardate"><i class="fa fa-times"></i></a>
                    <input id="'.$id.'" readonly class="dropdate" type="text" value="'.zero_date($value,"d M Y").'" '.$defaults['extra'].' '.$min_date.$max_date.'/>
                    <input class="dropdate_trigger '.$defaults["class"].'" name="'.$formname.'" type="hidden" value="'.zero_date($value,"Y-m-d").'"/>
                </div>';

            break;

            case "date_from_to":
                $form .= '<div class="inputwrap">';
                    $value1 = $defaults['value1'];
                    $value2 = $defaults['value2'];
                    $form .= "<input type=\"text\" value=\"$value1\" style=\"float:none; width: 80px;\" class=\"dropdate_from ".$defaults['class']."\" name=\"$formname1\" id=\"" . $id . "1\"/>";
                    $form .= "&nbsp;-&nbsp;";
                    $form .= "<input type=\"text\" value=\"$value2\" style=\"float:none; width: 80px;\" class=\"dropdate_to ".$defaults['class']."\" name=\"$formname2\" id=\"" . $id . "2\"/>";
                $form .= '</div>';
                break;

            case "time":
                if (zero_date($value, "H:i:s")) {
                    $h_value = date("H", strtotime($value));
                    $m_value = date("i", strtotime($value));
                    $hasdate = "hasdate";
                } else {
                    $h_value = "";
                    $m_value = "";
                    $hasdate = "";
                }

                $form .= '
                        <div class="inputwrap '.$hasdate.'">
                            <input class="v_date_time '.$defaults['class'].'" type="text" name="'.$formname.'" style="width: '.$defaults['td_width'].';" value="'.zero_date($value, "H:i:s").'" '.$defaults['extra']. '/>
                            <div class="time_wrap">
                                <span class="h_hour" data-time-trigger="true">'.$h_value.'</span>
                                <span class="colon" data-time-trigger="true">:</span>
                                <span class="h_minute" data-time-trigger="true">'.$m_value.'</span>

                                <a href="#" class="cleardate">
                                    <i class="fa fa-times"></i>
                                </a>

                                <span class="time_trigger" data-time-trigger="true">
                                    <i class="fa fa-clock-o"></i>
                                </span>
                            </div>
                        </div>';
                break;

            case "slider":
                $form .= '
                        <div class="inputwrap">
                            <div class="td_'.$id.'">
                                <div id="'.$id.'"></div>
                                <span class="helper">'.$value.'</span>
                                <input type="hidden" name="'.$formname.'" value="'.$value.'" />
                            </div>
                            '.$defaults['js'].'
                        </div>';
                break;

            case "autocomplete":
                $form .= '<div class="inputwrap">';

                if ($defaults["tags"]) {
                    if (!empty($defaults["dropdown"])) {
                        $form .= '<span class="dd_icon">
                            <i class="fa fa-caret-down"></i>
                            <i class="fa fa-refresh fa-spin"></i>
                        </span>';
                    }
                    $form .= '<input data-autocomplete="true" data-tags="true" class="form_style auto_display '.str_replace("required", "", $defaults["class"]).'" id="'.$id.'" type="text" '.$defaults["extra"].'/>';
                    if (!empty($defaults["selected_items"])) {
                        foreach ($defaults["selected_items"] as $one_item) {
                            $form .= '<span class="one_tag" id="'.$one_item->id.'">'.$one_item->tag.'<a data-remove-tag="true" class="remove tooltip" href="#" title="Remove '. $one_item->tag.'"><i class="fa fa-times"></i></a></span>';
                            $value .= $one_item->id . ","; // add comma at the end - because js for removing and adding works with it
                        }
                    }
                    $required = ($defaults['required'] || !empty($db_required_field)) ? "required" : "";
                    $form .= "<input type=\"hidden\" class=\"autocomplete-value ".$required."\" name=\"".$formname."\" value=\"".$value."\" />\n";

                } else {
                    if (!empty($my_post[$defaults["label"]])) $defaults["label_value"] = $my_post[$defaults["label"]];

                    $form_display = '';
                    if (!empty($value)){
                        $form .= '<span class="one_tag acoption">' . $defaults["label_value"] . '<a data-remove-selected="true" class="remove tooltip" href="#" title="Remove"><i class="fa fa-times"></i></a></span>';
                        $form_display = ' style="display:none"';
                    }

                    if (!empty($defaults["dropdown"])) $form .= '<span class="dd_icon"><i class="fa fa-caret-down"></i></span>';
                    $form .= "<input data-autocomplete=\"true\" ".$form_display." type=\"text\" id=\"" . $id . "\" name=\"" . $defaults["label"] . "\" value=\"".$defaults["label_value"]."\" class=\"" . str_replace("required", "", $defaults['class']) . "\" " . $defaults['extra'] . "/>";
                    $required = ($defaults['required'] || !empty($db_required_field)) ? "required" : "";
                    $form .= "<input class=\"autocomplete-value ".$required."\" type=\"hidden\" name=\"".$formname."\" value=\"".$value."\" />\n";
                }
                $form .= '</div>';
                break;

            case "multicheck":
                $form .= '<div class="inputwrap multicheck">';

                $select_value = !empty($defaults['select_value']) ? (array) json_decode($defaults['select_value']) : array();

                $multicheck_id = str_replace(array('[', ']'), '', $id);

                if(!empty($defaults['object'])) $defaults['object']->multicheck[$id] = null;

                if (!empty($my_post['multicheck'][$multicheck_id])) {
                    $select_value = array();
                    foreach ($my_post['multicheck'][$multicheck_id] as $selected_val) $select_value[] = $selected_val;
                }

                if(!empty($defaults['object'])) $defaults['object']->multicheck[$id] = $select_value;

                $required_check = ($defaults['required'] || !empty($db_required_field)) ? "required" : "";

                $form .= '<input type="hidden" class="multicheck '.$required_check.'" name="'.$formname.'" value="'.htmlspecialchars(json_encode($select_value)).'"/>';

                $selection = $defaults['selection_array'];
                $id_field = $defaults['id_field'];
                $value_field = $defaults['value_field'];
                $option="";
                reset($selection);
                if (count($selection)>0) {
                    $i=1;
                    $style = (empty($defaults['radio_break'])) ? '' : 'width:'.floor(100/min($defaults['radio_break'], count($selection))).'%;float:left;';
                    if ((is_assoc_array($selection) && empty($defaults['type'])) || $defaults['type']=="associative") {
                        //hash array; ignore id_field and value_field
                        foreach ($selection as $key => $value) {
                            if ($style) {
                                $option .= '<div style="'.$style.'">';
                            }
                            $option .= "<input id=\"id_".$item_id."\" type=\"hidden\" value=\"0\" name=\"multicheck[".$multicheck_id."][$i]\"/>";
                            $option .= "<input type=\"checkbox\" name=\"multicheck[".$multicheck_id."][$i]\" style=\"vertical-align:bottom;\" value=\"$key\" id=\"id_".$key."\" ";
                            if (in_array($key, $select_value)) {
                                $option .= " checked=\"checked\"";
                            }
                            $option .= " class=\"checkbox " . $defaults['class'] . "\" " . $defaults['extra'] . "><label class=\"radio\" for=\"id_".$key."\">".($value)."</label>";
                            if ($style) {
                                $option .= '</div>';
                                if ($i  %  $defaults['radio_break']  ==  0) {
                                    '<br/>';
                                }
                            }
                            if ($defaults['break']) {
                                $option .= '<div class="clear">&nbsp;</div>';
                            }
                            $i++;
                        }
                    } elseif ((is_object(current($selection)) && empty($defaults['type'])) || $defaults['type']=="object_array") {
                        //array of classes - most likely db->select; we need exact id_field and value_field
                        foreach ($selection as $item) {
                            if ($style) {
                                $option .= '<div style="'.$style.'">';
                            }
                            $option .= "<input id=\"id_".$item_id."\" type=\"hidden\" value=\"0\" name=\"multicheck[".$multicheck_id."][$i]\"/>";
                            $option .= "<input type=\"checkbox\" name=\"multicheck[".$multicheck_id."][$i]\" style=\"vertical-align:bottom;\" value=\"" . $item->$id_field . "\" id=\"id_".$item->$id_field."\" ";
                            if (in_array($item->$id_field, $select_value)) {
                                $option .= " checked=\"checked\"";
                            }
                            $option .= " class=\"checkbox " . $defaults['class'] . "\" " . $defaults['extra'] . "/><label class=\"radio\" for=\"id_".$item->$id_field."\">" . ($item->$value_field) . "</label>";
                            if ($style) {
                                $option .= '</div>';
                                if ($i  %  $defaults['radio_break']  ==  0) {
                                    '<br/>';
                                }
                            }
                            if ($defaults['break']) {
                                $option .= '<div class="clear">&nbsp;</div>';
                            }
                            $i++;
                        }
                    } elseif ((is_sequential_array($selection) && empty($defaults['type'])) || $defaults['type']=="simple") {
                        //simple array
                        foreach ($selection as $item) {
                            $item_id="id_".$id."_".$item;
                            if ($style) {
                                $option .= '<div style="'.$style.'">';
                            }
                            $option .= "<input id=\"id_".$item_id."\" type=\"hidden\" value=\"0\" name=\"multicheck[".$multicheck_id."][$i]\"/>";
                            $option .= "<input type=\"checkbox\" name=\"multicheck[".$multicheck_id."][$i]\" style=\"vertical-align:bottom;\" value=\"$item\" id=\"id_".$item_id."\" ";
                            if (in_array($item, $select_value)) {
                                $option .= " checked=\"checked\"";
                            }
                            $option .= " class=\"checkbox " . $defaults['class'] . "\" " . $defaults['extra'] . "/><label class=\"radio\" for=\"id_".$item_id."\">".($item)."</label>";
                            if ($style) {
                                $option .= '</div>';
                                if ($i  %  $defaults['radio_break']  ==  0) {
                                    '<br/>';
                                }
                            }
                            if ($defaults['break']) {
                                $option .= '<div class="clear">&nbsp;</div>';
                            }
                            $i++;
                        }
                    }
                }
                $form .= $option;

                if (!empty($defaults['toggle_buttons'])) {
                    $form .= '<div class="toggle_on"><span class="btn">Check All</span></div>
                    <div class="toggle_off"><span class="btn">Uncheck All</span></div>';
                }

                $form .= '</div>
                <script>

                    function update_checkboxes(elem){
                        var values = new Array();
                        elem.find("input:checked").each(function(e){
                            values.push($(this).val());
                        });
                        elem.find("input[type=\"hidden\"]").val(JSON.stringify(values));
                    }

                    $(".multicheck input[type=\"checkbox\"]").change(function (e) {
                        var elem = $(this).parents(".multicheck");
                        update_checkboxes(elem);
                    });

                    $(".multicheck .toggle_on span").on("click", function(){
                        var elem = $(this).parents(".multicheck");
                        elem.find("input[type=\"checkbox\"]").prop("checked", "checked");
                        update_checkboxes(elem);
                    });

                    $(".multicheck .toggle_off span").on("click", function(){
                        var elem = $(this).parents(".multicheck");
                        elem.find("input[type=\"checkbox\"]").prop("checked", false);
                        update_checkboxes(elem);
                    });
                </script>';
                break;

            default:
                $form .= '<div class="inputwrap">';
                    if (!isset($data_limit)) $data_limit = "";
                    $form .= "<input $data_limit class=\"" . $defaults['class'] . "\" type=\"text\" id=\"$id\" name=\"$formname\" value='" . htmlspecialchars($value) . "' " . $defaults['extra'] ." autocomplete=\"off\"/>";
                    if (!empty($defaults["url_preview"]) && !empty($value)) $form .= '<span class="add url_preview">Page will be published as <b>'.$cfg["website"] . make_seo_title($value).'</b>';
                $form .= '</div>';
                break;
        }

        // build quick add link options
        if (!empty($defaults["add"]["text"]) && !empty($defaults["add"]["url"]) && !empty($defaults["add"]["permission"]) && $defaults["add"]["permission"]) {

            // link can be already built in with a query string, so append field name
            $defaults["add"]["url"] = $defaults["add"]["url"] . ((strpos($defaults["add"]["url"], "?") !== false) ? "&" : "?") . http_build_query(array("prefill_field"=>$key));
            $form .= '<span class="add'. (!empty($defaults["add"]["celltext"]) ? ' ctxt' : '') .'">'.(!empty($defaults["add"]["celltext"]) ? $defaults["add"]["celltext"] : '').'<a data-jbox-jlink="forward" href="'.encrypt_url($defaults["add"]["url"]).'">'.$defaults["add"]["text"].'</a></span>';
        }

        if (!$defaults['minimal']){ // if we want only input element to be returned
            ($defaults['multi'] == "middle" || $defaults['multi'] == "first" || $filter_form) ? $form .= "</td>\n" : $form .= "</td></tr>\n";
        }
        return $form;
    }

    function render_form_table_row_password($formname, $fieldname, $id, $options = array()){
        return $this->render_form_table_row_base($formname, "", $fieldname, $id, $options,'password');
    }

    function render_form_table_row($formname, $value="", $fieldname, $id="", $options = array()) {
        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $options,'default');
    }

    function render_form_table_row_text($formname, $value="", $fieldname, $id, $options = array()) {
        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $options,'text');
    }

    function render_form_table_row_date($formname, $value="", $fieldname, $id, $options=array()) {
        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $options,'date');
    }

    function render_form_table_row_datetime($formname, $value="", $fieldname, $id, $options=array()) {
        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $options,'datetime');
    }

    function render_form_table_row_time($formname, $value="", $fieldname, $id, $options=array()) {
        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $options,'time');
    }

    function render_form_table_row_checkbox($formname, $value="", $fieldname, $id, $options=array()) {
        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $options,'checkbox');
    }

    function render_form_table_row_file($formname, $fieldname, $object = null, $folder, $options=array()) {
        global $cfg, $db, $my_post, $user1;

        $defaults = array(
            'th_width'=>"200px",
            'tooltip'=>"",
            'required'=>false,
            'class'=>"",
            'section'=>true,
            'secure_file'=>false, // crypt file and save entire file to the database (requires filename_CONTENT column)
            'auto_upload'=>true,
            'accepted_ft'=>"*", // gif|jpe|jpeg|png
            'max_fs'=>"100000000", // 10MB usually
            'num_of_files'=>1,
            'multi_file'=>false,
            'file'=>"",
            'image'=>"",
            'field'=>"",
            'keep_file'=>false,// disable file delete from the disk
            'hide_button'=>true, // remove Browse / Choose File button once file is uploaded (if a new file is uploaded, previous file is overwritten)
            'from_library'=>false, // show a library with thumbs, allowing you to link a file from library instead of uploading it (requires LIB_fieldname column)
            'database_prefix'=>"cms", // you can specify different database tables
            'lib_file'=>'',
            'override_field'=>'', // used when database field is not in standard "lib_" + field format
            'override_formname'=>'', // used when form field name is not in standard "object[field]" format
            'library_selected'=>false, // if set to true + library is enabled, it will be a swithced on by default
        );

        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        if ($defaults['required']) $defaults['class'] = " required";

        $keep_file = ($defaults["keep_file"]) ? '&keep_file=true' : '';

        // Is the formname field in our standard format object_name[field_name]?
        preg_match('#\[(.*)\]#', $formname, $field);

        // If yes take the inside of [] for fieldname, otherwise take the whole string
        // + quick fix for object_name[counter][image] type of file names
        $f = (!empty($field[1])) ? str_replace("][", "_", $field[1]) : $formname;

        // value is by default $defaults['file'] i.e. empty unless overwritten
        $value = $defaults['file'];

        // If object is given
        if (!empty($object)) {

            // If we have posted (self submited) the right field - overwrite value
            if (!empty($object->object_name) && !empty($my_post[$object->object_name][$f])) {
                $value = $my_post[$object->object_name][$f];

            // otherwise if it comes from the DB take that
            } elseif (isset($object->id) && isset($object->$f)) {

                $value = $object->$f;

			}

		}

		//Delete $value if the file does not exist
		if(empty($defaults["multi_file"]) && !is_file($cfg['secure_dir'].$value)) $value = null;

		// Do the same for library source files, if object is given
		if (!empty($object) && $defaults['from_library']) {

			// Is the formname field in our standard format object_name[field_name]?
			if (!empty($defaults["override_field"]) && !empty($defaults["override_formname"])) {

	            $lib_f = $defaults["override_field"];
	            $lib_formname = $defaults["override_formname"];

			} else if (!empty($field[1])) {

				$lib_f = "lib_" . str_replace("][", "_", $field[1]); // DB field name
				$lib_formname = (strpos($formname, "][") !== false) ? str_replace("][", "][lib_", $formname) : str_replace("[", "[lib_", $formname); // form input element name

			} else {

				$lib_f = "lib_" . $formname; // DB filed name
				$lib_formname = "lib_" . $formname; // form input element name

			}

			// error_log(print_r($lib_f, true));

			// possible file value override, only if the file upload value is empty
		  	if (empty($value)){

				$lib_value = $defaults["lib_file"];

				// But find out the real file value, if we have posted (self submited) the right field - overwrite value
				if (!empty($object->object_name) && !empty($my_post[$object->object_name][$lib_f])) {

					$lib_value = $my_post[$object->object_name][$lib_f];

				// otherwise if it comes from the DB take that
				} elseif (isset($object->$lib_f)) {

					$lib_value = $object->$lib_f;

				}

			} else {

			  $lib_value = "";

          	}

		}

        // windows safari bug for multiple files
        $multi_file = "";
        if (
			$defaults['multi_file']
			&& !empty($_SERVER["HTTP_USER_AGENT"])
			&& (strpos($_SERVER["HTTP_USER_AGENT"], "Safari")
			&& strpos($_SERVER["HTTP_USER_AGENT"], "Windows")
			&& strpos($_SERVER["HTTP_USER_AGENT"], "Chrome") === false
        )) {

            $multi_file = "";
            $defaults["hide_button"] = false;

        } else if ($defaults['multi_file']) {

            $multi_file = "multiple";
            $defaults["hide_button"] = false;
        }

        $html = '
        <tr>
            <th class="top" style="width: '.$defaults['th_width'].' !important;">';

        if ($defaults['tooltip']) {
            $html .= '
            <span class="tooltip ico_binfo" title="'.$defaults['tooltip'].'"><i class="fa fa-info"></i></span>';

        } elseif ($defaults['required'] || !empty($db_required_field)) {

            $html .= '
            <span class="tooltip ico_binfo" title="Required"><i class="fa fa-info"></i></span>';

        }

          $html .= '
		  <label>'.$fieldname.'</label>';

        // different upload sources switcher
        if ($defaults['from_library']) {

            // which source trigger is selected
            $fuactive = (!empty($lib_value)) ? '' : "active";
            $libactive = (!empty($lib_value)) ? "active" : '';

            $html .= '
            <span class="source">Upload from: <a class="fulocal '.$fuactive.'" href="#">Computer</a>';

            if ($defaults['from_library']) $html .= '
            <a class="fulib '.$libactive.'" href="#" data-source="'.$defaults["database_prefix"].'">Library</a>';

            $html .= '
            </span>';
        }

        $hide = (empty($lib_value)) ? "" : "hide";
        $html .= '
        </th>
        <td class="fup_td">
            <div
				class="row_fileupload '.$hide.'"
				data-num="'.$defaults["num_of_files"].'"
                data-folder="'.$folder.'"
                data-form_name="'.$formname.'"
                data-accepted_ft="'.$defaults["accepted_ft"].'"
                data-max_fs="'.$defaults["max_fs"].'"
                data-auto_upload="'.$defaults["auto_upload"].'"
                data-multi_file="'.$defaults["multi_file"].'"
                data-hide_button="'.$defaults["hide_button"].'"
                data-keep_file="'.$defaults["keep_file"].'"
                data-secure_file="'.$defaults["secure_file"].'"
                data-image="'.$defaults["image"].'
			">

            <table class="inner_table">
                <tr>
                    <td class="file_upload">
                        <input type="file" class="file" name="files[]" '.$multi_file .'/>
                        <input class="uploaded_file '.$defaults['class'] .'" value="'.htmlentities($value).'" type="hidden" name="'.$formname.'"/>
                        <div class="fileupload-content">
                            <table class="files"></table>
                          </div>
                     </td>
                 </tr>
             </table>
         </div>';

        // library upload source
        if ($defaults['from_library']){

            // show on load, if there is a value saved
            $hide = (empty($lib_value)) ? "hide" : "";
            $html .= '
                    <div class="lib_source clearfix '.$hide.'">
                        <input class="lib_file" value="'.$lib_value.'" type="hidden" name="'. $lib_formname . '"/>';

            $html .= $this->render_form_table_row_tags("", "",
                array('object_name'=>"lib_image", "field_id"=>"tags_lookup", "tags"=>true, "allow_insert"=>false, "dropdown"=>true, "minimal"=>true, "placeholder"=>"To filter the images, please enter the image tags", "extra"=>"data-image-tags='true'"
            ));

            // if there was an image selected, build the query here, and filter by the image tags
            $html .= '
                        <div class="img_holder">';

            // just get all images and highlight the selected (easiest, but can be improved)
            if (!empty($lib_value)) {
                $selection = $db->select("i.*, (SELECT GROUP_CONCAT(t.tag SEPARATOR ', ') FROM system_objects_tags it INNER JOIN system_tags t ON t.id = it.tag_id WHERE it.object_id = i.id AND it.related_object_name = 'lib_image') as tags",
                "cms_lib_images i",
                array("WHERE i.access_public", array(), array()));

                foreach($selection as $image){

                    $aclass = ($lib_value == $image->id) ? 'selected' : '';
                    $imgdesc = (!empty($image->description)) ? $image->description . '<br/>' : '';
                    $imgat = (!empty($image->tags)) ? '<b>Tags:</b> '.$image->tags.'</i>' : '';
                    $imgtitle = $imgdesc . $imgat;

                    $html .= '
                        <a class="img tooltip '.$aclass.'" href="#" title="'.$imgtitle.'" data-lid="'.$image->id.'">
                            <span class="rm"><i class="fa fa-times"></i></span>
                            <img src="'. phpThumb_URL(array( "src"=>$cfg['secure_dir'] . $image->image, "w"=>60, "h"=>60, "zc"=>1)) . '" alt="'.str_replace(substr(basename($image->image), 0, 14), '', basename($image->image)). '"/>
                        </a>';

                }
            }

            $html .= '
                        </div>';

            if (!empty($user1->{"app_cms/add_lib_image.php"})){
                $html .= '<span class="add ctxt">If you cannot find the image you are looking for, then '.href_link(
                    array(
                        "permission"=>true,
                        "url"=>$cfg['root'] . "app_cms/add_lib_image.php?prefill_field=lib_image",
                        "popup"=>false,
                        "button"=>false,
                        "text"=>'Add an image to the library',
                        "clear"=>false,
                        "float"=>"none",
                        "extra"=>"data-jbox-jlink='forward'"
                    )
                ) . '</span>';
            }

                $html .= '</div>';
        }

         $html .= '
                 </td>
             </tr>';

        $this->js .= file_get_contents($cfg["source_root"].'js/jquery.fileupload-templates.js');
        return $html;
    }

    function render_form_table_row_selection($formname, $select_value, $fieldname, $id, $selection, $id_field="", $value_field="", $options=array()) {
        return $this->render_form_table_row_base(
            $formname,
            $select_value,
            $fieldname,
            $id,
            array_merge($options,array(
                'select_value'=>$select_value,
                'selection_array' => $selection,
                'id_field' => $id_field,
                'value_field'=>$value_field,
            )),
            'selection'
        );
    }

    public function render_form_table_row_multicheck($formname, $select_value, $fieldname, $id, $selection, $id_field = "", $value_field = "", $options = array())
    {
        return $this->render_form_table_row_base(
            $formname,
            $select_value,
            $fieldname,
            $id,
            array_merge(
                $options,
                array(
                    'select_value'=>$select_value,
                    'selection_array' => $selection,
                    'id_field' => $id_field,
                    'value_field'=>$value_field,
                )
            ),
            'multicheck'
        );
    }

    function render_form_table_radio_selection($formname, $select_value, $fieldname, $id, $selection, $id_field="", $value_field="", $options=array()) {
        return $this->render_form_table_row_base($formname, $select_value, $fieldname, $id,
                array_merge($options,array(
                    'select_value'=>$select_value,
                    'selection_array' => $selection,
                    'id_field' => $id_field,
                    'value_field'=>$value_field,
                )),
                'radio');
    }

    function render_form_table_row_date_from_to($formname1, $formname2, $value1="", $value2="", $fieldname, $id, $options=array()){
        global $cfg;

        $defaults = array(
            'th_width'=>"200px",
            'tooltip'=>"",
            'required'=>false,
            'extra'=>"",
            'class'=>"",
            'from_class'=>"",
            'to_class'=>"",
			'self_submit'=>false,
			'minimal'=> false,
			'delete_date_trigger'=>true, //show delete date button
			'inputwrap_extra'=>'', //Additional style/attributes for inputwrap
        );

        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        if ($defaults['required'] == "from"){

            $defaults['from_class'] .= " required";

        } elseif($defaults['required'] == "to"){

            $defaults['to_class'] .= " required";

        } elseif($defaults['required']){

            $defaults['from_class'] .= " required";
            $defaults['to_class'] .= " required";

        }

        if ($defaults["self_submit"]) {

            $defaults["from_class"] .= ' self_submit';
            $defaults["to_class"] .= ' self_submit';

        }

        $hasdate1 = (!empty($value1)) ? "hasdate" : "";
        $hasdate2 = (!empty($value2)) ? "hasdate" : "";

        $tooltip = (!empty($defaults['tooltip'])) ? '<span class="tooltip ico_binfo" title="' . $defaults['tooltip'] . '" style="float:right;"><i class="fa fa-info"></i></span>' : '';

		$delete_button = (!empty($defaults['delete_date_trigger'])) ?  '<a href="#" class="cleardate"><i class="fa fa-times"></i></a>' : '';

		$html = '';

		if (!$defaults['minimal']) $html .= '
                <tr>
                    <th style="width: '.$defaults['th_width'].';">'.$tooltip.'
						<label for="'.$id.'">'.$fieldname.'</label>
					</th>
					<td>';

		$html .= '
						<div class="inputwrap datefrom '.$hasdate1.'" '.$defaults['inputwrap_extra'].'>
							'.$delete_button.'
							<input type="text" value="'.zero_date($value1, "d M Y") . '" readonly class="dob dropdate fromto '.$defaults['from_class'].'" id="'.$id.'"/>
                            <input class="dropdate_trigger '.$defaults["class"].'" name="'.$formname1.'" type="hidden" value="'.zero_date($value1,"Y-m-d").'"/>
                        </div>
						<div class="inputwrap dateto '.$hasdate2.'" '.$defaults['inputwrap_extra'].'>
							'.$delete_button.'
                            <span class="dash">&nbsp;-&nbsp;</span>
                            <input type="text" value="'.zero_date($value2, "d M Y") . '" readonly class="dob dropdate fromto '.$defaults['to_class'].'"/>
                            <input class="dropdate_trigger '.$defaults["class"].'" name="'.$formname2.'" type="hidden" value="'. zero_date($value2,"Y-m-d") . '"/>
						</div>';

		if (!$defaults['minimal']) $html .= '
                    </td>
                </tr>';

        return $html;
    }

    function render_form_table_row_slider($formname, $value, $fieldname, $id, $options=array()) {
        global $cfg;
        $defaults = array(
                'multi'=>"single",
                'range'=>'min',
                'min_value'=>0,
                'max_value'=>100,
                'steps'=>0,
        );
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        if ($value == "") $value=0;

              $this->js .= '
                  <script>
                      $(function() { $( "#'.$id.'").slider({
                        value: '.$value.',
                        range: "min",
                        min: '.$defaults['min_value'].',
                        max: '.$defaults['max_value'].',
                        step: '.$defaults['steps'].',
                        slide: function( event, ui ) {
                            $(".td_'.$id.' span").text ( ui.value );
                            $(".td_'.$id.' input").attr ("value", ui.value );
                        },
                        stop: function( event, ui ) {
                            $(".td_'.$id.' span").text ( $(this).slider("value") );
                            $(".td_'.$id.' input").attr ("value", $(this).slider("value") );
                        }
                      });});
                  </script>';

            return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $defaults,'slider');
    }

    function render_form_table_row_autocomplete($formname, $value = "", $fieldname, $id, $table, $display_field, $id_field, $options=array()) {
        global $db, $cfg, $crypt;
        $defaults = array(
            'th_width'=>"200px",
            'selection'=>array(),
            "spellcheck"=>false,
            'tooltip'=>"",
            'required'=>false,
            'extra'=>"",
            'class'=>"",
            'multi'=>"single",
            'where'=>"",
            'order_by'=>'',
            'label'=>"", // for editing forms, this is the value that is displayed if the value is not empty
            'label_value'=>"",
            'no_of_chars'=>3,
            'dropdown'=>false, // autocomplete opens and behaves like a select element
            'auto_select'=>false,
            'self_submit'=>false,
            'tags'=>false,
            'placeholder'=>"",
            'allow_insert'=>false, // tags - adds a searched term to results array, allowing you to insert it later
            'selected_items'=>'', // tags - used on edit forms - we need to populate this value with (id, name) values
        );
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        $defaults["extra"] .= ' data-url="'.$crypt->str_encrypt(
            "table=" . urlencode($table)
            ."&display_field=". urlencode($display_field)
            ."&id_field=". urlencode($id_field)
            ."&where=" . urlencode($defaults['where'])
            ."&order_by=" . urlencode($defaults['order_by'])
            ."&allow_insert=".$defaults["allow_insert"])
        .'"';

        if ($defaults["self_submit"]) { // override self submit with fake trigger class
            $defaults["class"] .= ' fksubmit';
            $defaults["self_submit"] = false;
        }

        if (!$defaults["spellcheck"]) $defaults["extra"] .= ' spellcheck="false"';
        if ($defaults["allow_insert"]) $defaults["extra"] .= ' data-insert="true"';
        if ($defaults["auto_select"]) $defaults["extra"] .= ' data-auto-select="true"';
        if ($defaults["placeholder"]) $defaults["extra"] .= ' placeholder="'.$defaults["placeholder"].'"';
        if ($defaults["tags"]) $defaults["extra"] .= ' data-tags="true"';
        if ($defaults["dropdown"]) {
            $defaults["extra"] .= ' data-dropdown="true"';
            $defaults["class"] .= ' dropdown';
            $defaults["no_of_chars"] = 0;
        }
        $defaults["extra"] .= ' data-min="' . $defaults["no_of_chars"].'"';

        return $this->render_form_table_row_base($formname, $value, $fieldname, $id, $defaults, 'autocomplete');
    }


    function render_form_table_row_tags($fieldname, $object, $options=array()) {
        global $db, $cfg, $crypt, $my_post, $my_get;

        $defaults = array(
            'th_width'=>"200px",
              'tooltip'=>"",
              'required'=>false,
              'self_submit'=>false,
              'placeholder'=>"Click to select existing tags, or type new tags to create them",
              'dropdown'=>true,  // autocomplete opens and behaves like a select element
              'allow_insert'=>true, // adds a searched term to results array, allowing you to insert it later
              'object_name'=>'', // if $object is not set, used to get object name - used for images library switch
              'field_id'=>'system_tags', // can be changed if needed (i.e. search forms) but should be kept as default for out of box functionality with object's insert and update
              'related_sub_object'=>'', // if you need the tags input for more than once in the same object / popup, specify a sub object
        );

        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        // find out object_name
        $object_name = (!empty($object)) ? $object->object_name : $defaults["object_name"];

        // check if the object has any tags linked
        $existing_tags = array();

        // get or post, post is more important
        if (!empty($my_post[$defaults["field_id"]]) || !empty($my_get[$defaults["field_id"]])) {
            if (!empty($my_get[$defaults["field_id"]])) $format_tags = explode(",", $my_get[$defaults["field_id"]]);
            if (!empty($my_post[$defaults["field_id"]])) $format_tags = explode(",", $my_post[$defaults["field_id"]]);

            foreach ($format_tags as $tag){
                if (!empty($tag)) {

                    // existing tags or a new ones
                    if (is_numeric($tag)){
                        $posted_tag = $db->select("t.*", "system_tags t", array("WHERE t.id = ? AND t.related_object_name = ? AND t.related_sub_object = ?", array($tag, $object_name, $defaults["related_sub_object"]), array("integer", "varchar", "varchar")));
                        if (!empty($posted_tag)) $posted_tag = $posted_tag[0];

                    } else {
                        $posted_tag = new stdClass;
                        $posted_tag->id = $tag;
                        $posted_tag->id = $tag;
                        $posted_tag->tag = $tag;

                    }

                    $existing_tags = array_merge($existing_tags, array($posted_tag));
                }
            }

        } else if (!empty($object->id)) {
            $existing_tags = $db->select("t.*", "system_tags t LEFT JOIN system_objects_tags o ON o.tag_id = t.id", array("WHERE o.object_id = ? AND o.related_object_name = ? AND t.related_sub_object = ?", array($object->id, $object_name, $defaults["related_sub_object"]), array("integer", "varchar", "varchar")));

        }

        // if there is related sub object specified, format the input name as the array
        $input_name = $defaults["field_id"] . (!empty($defaults["related_sub_object"]) ? '[' . $defaults["related_sub_object"] . ']' : '[]');
        $html = $this->render_form_table_row_autocomplete($input_name, "", $fieldname, $defaults["field_id"] . uniqid(), "system_tags", "tag", "id", array_merge(array('where'=>"WHERE tag LIKE ? AND related_object_name = '".$object_name."' AND related_sub_object = '". $defaults["related_sub_object"] . "'", "tags"=>true, "selected_items"=>$existing_tags), $defaults));
        return $html;

    }

    function list_object($options=array()){
        global $db, $user1, $cfg, $crypt, $my_get, $my_post;

        foreach($options as $key=>$value) $defaults[$key] = $value;

        $this->tableid = $this->name_table($defaults["table_name"], $defaults["where"]);
        $this->div_id = "table_".$defaults["table_name"]."_".$this->tableid;
        $table = '';
        $total = $options["total"];
        $num_of_pages = $options["num_of_pages"];
        $page = $options["page"];

        if ( $defaults['total'] > 0 || !empty($options["column_filter"])) {
            if (!empty($defaults["ajax_list"])) $table .= '
                <div class="table_ajax_wrap clearfix" style="width:'.$defaults['width'].'">'; // add the invisible wrap which is updated on list page change
            $table .= '
                    <div id="'.$this->div_id.'" class="table_wrap clearfix">';

            if ($defaults['table_wrapper']) {

                $table .= '
                        <div class="table_options">';

                if ($defaults['pagination'] && $defaults['total'] > 0) $table .= $this->generatePagination($defaults);

                if ($defaults['quick_search'] && (!empty($defaults["view_options"]["no_pagination"]) || $num_of_pages == 1) && $defaults['total'] > 0) {

                    $table .= '
                            <div class="quick_wrap">
                                <input tabindex="1" type="text" class="quick_filter right tooltip" title="Narrow down current table results" value="Quick table filter" data-quick-table-filter="true"/>
                                <span class="reset_quick" data-quick-table-filter-reset="true"><i class="fa fa-times"></i></span>
                            </div>';

                } else if ($defaults['quick_search'] && (!empty($defaults["view_options"]["no_pagination"]) || $num_of_pages == 1)) {

                    $table .= '
                            <div class="quick_wrap">
                                <input disabled="disabled" tabindex="1" type="text" class="quick_filter right" value="Quick table filter"  data-quick-table-filter="true"/>
                                <span class="reset_quick" data-quick-table-filter-reset="true"><i class="fa fa-times"></i></span>
                            </div>';

                }

                if ($defaults['view']) {

                    $dvhtml = '
                            <ul class="dropdown_columns">';

                    $has_unchecked = false;

                    foreach($defaults['all_columns'] as $key => $column) {

                        if (!empty($column["name"])) {
                            if ((isset($column["display"]) && $column["display"] == false)){

                                $has_unchecked = true;
                                $dvhtml .= '
                                    <li>
                                        <input type="checkbox" name="'.$key.'" id="col_'.$this->tableid.'_'.$key.'" />
                                        <label for="col_'.$this->tableid.'_'.$key.'">'.$column["name"].'</label>
                                    </li>';

                            } else {

                                $dvhtml .= '
                                    <li>
                                        <input type="checkbox" name="'.$key.'" id="col_'.$this->tableid.'_'.$key.'" checked="checked" />
                                        <label for="col_'.$this->tableid.'_'.$key.'">'.$column["name"].'</label>
                                    </li>';

                            }
                        }
                    }

                    $dvhtml .= '
                            </ul>';

                    if ($has_unchecked) {

                        $dvhtml = '
                            <div class="change_view dropdown_view in_use">
                                <i class="fa fa-caret-down"></i>
                                <div class="dropdown_wrap">' . $dvhtml . '
                                    <a class="link" href="'.get_enc_page(array($this->div_id => array("show_all_cols"=>"1", "view_reload"=>""))).'">Reset View</a>';

                    } else {

                        $dvhtml = '
                            <div class="change_view dropdown_view">
                                <i class="fa fa-caret-down"></i>
                                <div class="dropdown_wrap">' . $dvhtml . '
                                    <a class="hide link" href="'.get_enc_page(array($this->div_id => array("show_all_cols"=>"1", "view_reload"=>''))).'">Reset View</a>';
                    }

                    $dvhtml .= '
                                </div>
                            </div>';

                    // append html that was built above
                    $table .= $dvhtml;

                }

                // start table options dropdown
                $table_options = array();
				if (isset($defaults['table_options']) && !empty($defaults['table_options']) && is_array($defaults['table_options'])) {
					$table_options = array_merge($table_options, $defaults['table_options']);
				}
                if ($defaults['xml_export']) $table_options[] = href_link(array(
                        "permission"=>true,
                        "url"=>$cfg["root"] . "includes/table_export.php?extype=xml&table_pos=".$this->div_id,
                        "popup"=>false,
                        "button"=>false,
                        "text"=>"Export table to <b>.xml</b>",
                        "clear"=>false,
                        "float"=>"right",
                        "class"=>"change_view"
                ));

                if ($defaults['csv_export']) $table_options[] = href_link(array(
                        "permission"=>true,
                        "url"=>$cfg["root"] . "includes/table_export.php?extype=csv&table_pos=".$this->div_id,
                        "popup"=>false,
                        "button"=>false,
                        "text"=>"Export table to <b>.csv</b>",
                        "clear"=>false,
                        "float"=>"right",
                        "class"=>"change_view"
                ));

                if ($defaults['pdf_export']) $table_options[] = href_link(array(
                        "permission"=>true,
                        "url"=>$cfg['root'] . "includes/table_export.php?extype=pdf&table_pos=".$this->div_id,
                        "popup"=>false,
                        "button"=>false,
                        "text"=>"Export table to <b>.pdf</b>",
                        "clear"=>false,
                        "float"=>"right",
                        "class"=>"change_view"
                ));

                if ($defaults['email_alert']) $table_options[] = href_link(array(
                        "permission"=>true,
                        "url"=>$cfg['root'] . "set_email_alert.php?class=" . $defaults['class_name'] . "&path=".$this->path,
                        "popup"=>true,
                        "button"=>false,
                        "text"=>"Set up an email alert",
                        "clear"=>false,
                        "float"=>"right",
                        "class"=>"change_view"
                ));

                if ($defaults['print']) $table_options[] = href_link(array(
                        "permission"=>true,
                        "url"=>"#",
                        "encrypt"=>false,
                        "popup"=>false,
                        "button"=>false,
                        "text"=>"Print the table",
                        "clear"=>false,
                        "float"=>"right",
                        "class"=>"change_view prnt"
                ));

                if (!empty($table_options)) {
                    $table .= '
                            <div class="ddmenu tblops">
                                <span class="top">Options</span>
                                <span class="handle">
                                    <i class="fa fa-caret-down"></i>
                                </span>
                                <span class="wrap">' . implode($table_options, "") . '</span>
                            </div>';
                }

                $table .= '
                        </div>';
            }

            $table .= '
                    <div class="table_parent">';
            $table .= $this->create_table_header($defaults);
            $table .= '
                    <tbody>';

            $row_count = $defaults['row_count'];

            foreach($defaults['selection'] as $selection_item) {

                $row_count++;
                $id = (!empty($selection_item->{$defaults['object_pk']})) ? $selection_item->{$defaults['object_pk']} : null;
                $row = ($row_count % 2 == 0) ? "even" : "odd";

                // colourize row if any class "_toggle_off" is found
                if (!empty($defaults["colourise"]) && strpos(serialize($selection_item), "_toggle_off")) $row .= " c_inactive";

                $table .= '
                        <tr class="'.$row.'">';

                $selection_item->row_number = $row_count;

                // if ajax_sort is set, it overrides the default sort if there is no pagination
                if (isset($selection_item->sort_order) && $defaults["ajax_sort"]){
                    $selection_item->sort_order = '<span id="'.$crypt->str_encrypt($id . "," . $defaults['table']).'" class="ico_move move_handle"><i class="fa fa-sort"></i></span>';
                }

                // Created and modified information
                if ($defaults['info']){
                    $created=$modified=$selection_item->info="";
                    if (
                            !empty($selection_item->created_time)
                            && zero_date($selection_item->created_time)!=""
                    ) $created .= "Entry created on " . zero_date($selection_item->created_time, $user1->preferences->dateformat . " H:i");
                    if (
                            !empty($selection_item->modified_time)
                            && zero_date($selection_item->modified_time)!=""
                    ) $modified .= "Entry modifed on " . zero_date($selection_item->modified_time, $user1->preferences->dateformat . " H:i");
                    if (
                            !empty($created)
                            && !empty($selection_item->created_by_name)
                    ) $created .= " by " . $selection_item->created_by_name . ".";
                    if (
                            !empty($modified)
                            && !empty($selection_item->modified_by_name)
                    ) $modified .= " by " . $selection_item->modified_by_name . ".";

                    if ($created || $modified) $selection_item->info = '
                            <span class="tooltip ico_binfo" title="'.$created.' '.$modified.'"><i class="fa fa-info"></i></span>';
                }

                // Copy, edit, delete
                if(empty($selection_item->delete)) $selection_item->delete = '';
                if(empty($selection_item->copy)) $selection_item->copy = '';
                if(empty($selection_item->edit)) $selection_item->edit = '';

                if (
                        $defaults['copy']
                        && $user1->{$defaults['app_path'] . "copy_" . $defaults['object_name'] . ".php"}

                ) $selection_item->copy .= href_link(array(
                        "permission"=>true,
                        "url"=>$cfg['root'] . $defaults['app_path'] . "copy_" . $defaults['object_name'] . ".php?" . $defaults['object_name'] . "_id=" . $id,
                        "text"=>'
                            <span class="ico_copy tooltip" title="Copy"><i class="fa fa-copy"></i></span>
                            <span class="txt">Copy</span>',
                        "class"=>"action",
                        "button"=>false,
                        "clear"=>false
                ));

                if (
                        $defaults['edit']
                        && $user1->{$defaults['app_path'] . "edit_" . $defaults['object_name'] . ".php"}
                ) {
                    if (isset($defaults['hide_edit_when'])) {
                        $hide_edit = false;
                        foreach ($defaults['hide_edit_when'] as $hide_key=>$hide_value) {
                            if ($selection_item->{$hide_key} == $hide_value) {
                                $hide_edit = true;
                                $selection_item->edit = "";
                                break;
                            }
                        }

                        if (!$hide_edit) $selection_item->edit .= href_link(array(
                                "permission"=>true,
                                "url"=>$cfg['root'] . $defaults['app_path'] . "edit_" . $defaults['object_name'] . ".php?" . $defaults['object_name'] . "_id=" . $id,
                                "text"=>'
                                    <span class="ico_edit tooltip" title="Edit"><i class="fa fa-pencil"></i></span>
                                    <span class="txt">Edit</span>',
                                "class"=>"action dblclick_action",
                                "button"=>false,
                                "clear"=>false
                        ));
                    } else {
                        $selection_item->edit .= href_link(array(
                                "permission"=>true,
                                "url"=>$cfg['root'] . $defaults['app_path'] . "edit_" . $defaults['object_name'] . ".php?" . $defaults['object_name'] . "_id=" . $id,
                                "text"=>'
                                <span class="ico_edit tooltip" title="Edit"><i class="fa fa-pencil"></i></span>
                                <span class="txt">Edit</span>',
                                "class"=>"action dblclick_action",
                                "button"=>false,
                                "clear"=>false
                        ));
                    }
                }

                if (
                        $defaults['delete']
                        && $user1->{$defaults['app_path'] . "delete_" . $defaults['object_name'] . ".php"}
                ) {
                    if (isset($defaults['hide_delete_when'])) {
                        $hide_delete = false;
                        foreach ($defaults['hide_delete_when'] as $hide_key=>$hide_value) {
                            if ($selection_item->{$hide_key} == $hide_value) {
                                $hide_delete = true;
                                $selection_item->delete = "";
                                break;
                            }
                        }
                        if (!$hide_delete) $selection_item->delete .= href_link(array(
                                "permission"=>true,
                                "url"=>$cfg['root'] . $defaults['app_path'] . "delete_" . $defaults['object_name'] . ".php?" . $defaults['object_name'] . "_id=" . $id,
                                "text"=>'
                                    <span class="ico_delete tooltip" title="Delete"><i class="fa fa-times"></i></span>
                                    <span class="txt">Delete</span>',
                                "class"=>"action jmini",
                                "button"=>false,
                                "clear"=>false
                        ));
                    } else {
                        $selection_item->delete .= href_link(array(
                                "permission"=>true,
                                "url"=>$cfg['root'] . $defaults['app_path'] . "delete_" . $defaults['object_name'] . ".php?" . $defaults['object_name'] . "_id=" . $id,
                                "text"=>'
                                    <span class="ico_delete tooltip" title="Delete"><i class="fa fa-times"></i></span>
                                    <span class="txt">Delete</span>',
                                "class"=>"action jmini",
                                "button"=>false,
                                "clear"=>false
                        ));
                    }
                }

                // simplified multiselect, just comes from the _list settings
                if (!empty($defaults['multiselect'])) {
                    $selection_item->multiselect = '<input type="checkbox" id="'.$id.'" name="multiselect['.$id.']" class="checkbox" />';
                }

                // row highlighting
                $highlight = "";
                if (
                        isset($defaults['highlight_column'])
                        && !empty($defaults['highlight_column'])
                ) {
                    if (in_array($selection_item->{$defaults['highlight_column']}, $defaults['highlight_conditions'])) {
                        $highlight_key = array_search($selection_item->{$defaults['highlight_column']}, $defaults['highlight_conditions']);
                        if ($highlight_key !== false) {
                            if (
                                    !isset($defaults['highlight_colours'])
                                    || empty($defaults['highlight_colours'])
                                    || !isset($defaults['highlight_colours'][$highlight_key])
                            ) {
                                $highlight = 'style="background-color: #F44;"';
                            } else {
                                $highlight = 'style="background-color: '.$defaults['highlight_colours'][$highlight_key]. ';"';
                            }
                        }
                    }
                }

                // Loop through columns in view array
                foreach($defaults['view_array'] as $key => $item){

                    $class = (isset($item["class"])) ? $item["class"] . " column_" . $key . " " : "column_" . $key . " ";
                    $class .= (isset($defaults['view_array'][$key]["multi_toggle"])) ? "multi_toggle" : "";
                    $class .= (isset($defaults['view_array'][$key]["inline_edit"])) ? "inline_edit" : "";

                    // Either the key exists and is 1 or it does not exist at all
                    if (
                            !array_key_exists("display", $item)
                            || !empty($item["display"])
                    ) {
                        if (
                                isset($defaults['view_array'][$key]["jbox_image"])
                                && $item
                        ) {
                            $table .= "<td>";
                            if (
                                    !empty($selection_item->$key)
                                    && $selection_item->$key != "screenshots/"
                            ) {
                                $caption = (isset($item["jbox_caption"])) ? " title = \"".$selection_item->$item["jbox_caption"]."\"" : "";
                                $table .= "<a class=\"jbox_img\" $caption ";

                                if (extension($selection_item->$key) != ".avi") {
                                    $table .= "href=\"". phpThumb_URL(array(
                                            "src"=>$cfg['secure_dir'] . $selection_item->$key
                                    )) . "\">";
                                    $table .= "<img src=\"". phpThumb_URL(array(
                                            "src"=>$cfg['secure_dir'] . $selection_item->$key,
                                            "w"=>50,
                                            "h"=>50,
                                            "zc"=>1
                                    )) . "\" alt=\"".str_replace(substr(basename($selection_item->$key), 0, 14), "", basename($selection_item->$key))."\"/>";

                                    $table .= "</a>";
                                }

                            // image is coming from the library
                            } else if (!empty($selection_item->{'lib_' . $key})) {

                                $image_id = $selection_item->{'lib_' . $key};

                                // which table
                                $table_prefix = (!empty($defaults['view_array'][$key]["database_prefix"]) ? $defaults['view_array'][$key]["database_prefix"] : 'cms');
                                $selection = $db->select("image", $table_prefix . "_lib_images", array("WHERE id = ?", array("id"=>$image_id), array("integer")));

                                if (!empty($selection[0]->image)) {
                                    $image = $selection[0]->image;

                                    $caption = (isset($item["jbox_caption"])) ? " title = \"".$selection_item->$item["jbox_caption"]."\"" : "";
                                    $table .= "<a class=\"jbox_img\" $caption ";

                                    $table .= "href=\"". phpThumb_URL(array(
                                            "src"=>$cfg['secure_dir'] . $image
                                            )) . "\">";
                                    $table .= "<img src=\"". phpThumb_URL(array(
                                            "src"=>$cfg['secure_dir'] . $image,
                                            "w"=>50,
                                            "h"=>50,
                                            "zc"=>1
                                            )) . "\" alt=\"".str_replace(substr(basename($image), 0, 14), "", basename($image))."\"/>";

                                    $table .= "</a>";
                                }

                            }

                            // save the total number of visible columns, used for table footer multiselect
                            $table .= "</td>";

                        } else {
                            if (isset($selection_item->{$key . "_td_class"})) {
                                $table .= "<td class=\"" . $class . $selection_item->{$key . "_td_class"} . "\" $highlight>";
                            } else {
                                $table .= "<td class=\"".$class."\" $highlight>";
                            }
//                             if ($key=="row_number"){
//                                 $table .= "<a name=\"".$defaults['object_name']."_".$selection_item->{$key}."\"></a>";
//                             }
                            $table .= $selection_item->$key . "</td>";
                        }
                    }
                }
                $table .= "</tr>\n";
            }

            if (
                    !empty($defaults['dynamic_append'])
                    && !empty($defaults['table_wrapper'])
                    && (isset($num_of_pages))
                    && $num_of_pages > 1
                    && $page != $num_of_pages && (
                            empty($defaults["view_options"])
                            || empty($defaults["view_options"]["no_pagination"]
                            )
                    )
            ) {
                $table .= '
                        <tr class="add_more">
                            <td class="plus">
                                <i class="fa fa-plus"></i>
                            </td>
                            <td class="add_link" colspan="100%">View more</td>
                        </tr>';
            }
            $table .= '
                    </tbody>';

            // multiselect
            if ($defaults['multiselect'] && $defaults['total'] >= 1) {

                // calculate offset
                $table .= '<tfoot>
                    <tr class="multiselect">
                        <td class="multiactions" colspan="'. ($this->columns_number - 1).'">';

                            if ($defaults['multidelete'] && $defaults['delete'] && $user1->{$defaults['app_path'] . "multidelete_" . $defaults['object_name'] . ".php"}) {
                                $table .= href_link(array(
                                    "permission"=>true,
                                    "url"=>$cfg['root'] . $defaults['app_path'] . "multidelete_" . $defaults['object_name'] . ".php",
                                    "text"=>'<i class="fa fa-times"></i>Delete selected',
                                    "button"=>true,
                                    "clear"=>false,
                                    "float"=>"none",
                                    "class"=>"delete_selected",
                                    "extra"=>"data-uri=\"".$cfg['root'] . $defaults['app_path'] . "multidelete_" . $defaults['object_name'] . "/\""
                                ));

                            }

                            if ($defaults['multiedit'] && $defaults['edit'] &&$user1->{$defaults['app_path'] . "multiedit_" . $defaults['object_name'] . ".php"}) {
                                $table .= href_link(array(
                                    "permission"=>true,
                                    "url"=>$cfg['root'] . $defaults['app_path'] . "multiedit_" . $defaults['object_name'] . ".php",
                                    "text"=>'<i class="fa fa-pencil"></i>Edit selected',
                                    "button"=>true,
                                    "clear"=>false,
                                    "float"=>"none",
                                    "class"=>"edit_selected",
                                    "extra"=>"data-uri=\"".$cfg['root'] . $defaults['app_path'] . "multiedit_" . $defaults['object_name'] . "/\""
                                ));
                            }

                            if (!empty($defaults['multicopy']) && $user1->{$defaults['app_path'] . "multicopy_" . $defaults['object_name'] . ".php"}) {
                                $table .= href_link(array(
                                    "permission"=>true,
                                    "url"=>$cfg['root'] . $defaults['app_path'] . "multicopy_" . $defaults['object_name'] . ".php",
                                    "text"=>"<i class=\"fa fa-files-o\"></i> Copy selected",
                                    "button"=>true,
                                    "clear"=>false,
                                    "float"=>"none",
                                    "class"=>"copy_selected",
                                    "extra"=>"data-uri=\"".$cfg['root'] . $defaults['app_path'] . "multicopy_" . $defaults['object_name'] . "/\""
                                ));
                            }

                        $table .= '</td>
                        <td style="width:18px">
                            <input type="checkbox" class="check_all"/>
                        </td>
                    </tr>
                </tfoot>';
            }

            $table .= '
                    </table>';

            // column filters message
            if ($defaults['total'] == 0) $table .= '
                    <p class="no_rows_grey">No rows to display.</p>';

            if (!empty($defaults['bottom_pagination']) || !empty($defaults['to_top'])) $table .= '
                    <div class="table_options_bottom">';

            if (!empty($defaults['bottom_pagination'])) $table .= $this->generatePagination($defaults);

            if (!empty($defaults['to_top'])) $table .= '
                    <a class="to_top" href="#top">back to top</a>';

            if (!empty($defaults['bottom_pagination']) || !empty($defaults['to_top'])) $table .= '
                    </div>';

            $table .= '
                        </div>
                    </div>';

            if (!empty($defaults["ajax_list"])) $table .= '
                </div>';

            if ($defaults['multiselect']) {
                $table .= '
                <form class="multiselect" name="multiselect" action="" method="get">
                    <input type="text" class="ids" name="multiselect_ids" />
                    <input type="submit" />
                </form>';
            }

        } else {
            if ($defaults['no_items_message']) $table .= '
                <div class="no_data">No items found.</div>';
        }

        $table .= '
                <div class="dropdown_parent">
                    <div class="dropdown_menu"></div>
                </div>';

        return $table;
    }

    function standard_form($options = array()){
        global $db, $my_post, $my_get;

        $defaults = array(
            "class_name"=>"",
            "type"=>"",
            "title"=>"",
            'pause'=>false,
            'show_delete'=>true,
            'easy_cancel'=>false,
        );
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        $this->title = $defaults["title"];
        $this->easy_cancel = $defaults["easy_cancel"];
        $a = new $defaults["class_name"];
        $id = my_get($a->object_name . '_id', 0);
        $show = true;

        if ($defaults["type"] != "add" && $defaults["type"] != "multiedit" && $defaults["type"] != "multidelete" && $defaults["type"] != 'multicopy'){
            $a = get_clean_object($id, $defaults["class_name"], false);
            $show = (!empty($a));
        }

        if ($defaults["type"] == "multiedit" || $defaults["type"] == "multidelete" || $defaults["type"] == 'multicopy'){
            $a = new $defaults["class_name"];
            $ids = explode("=", $_GET['x']);
            if (!empty($ids[1])) $a->ids = $ids[1];
        }

        if ($show) {
            if (!empty($my_get["continue"])) {
                $data = $_SESSION["paused_forms"][$my_get["continue"]]["form_data"];

            } else if (!empty($my_post[$a->object_name])) {
                $data = $my_post[$a->object_name];

            } else {
                $data = array();
            }

            $a->set_post($data);
            $this->render_form($a->{"print_" . $defaults["type"] . "_form"}(array("pause"=>$defaults["pause"], "show_delete"=>$defaults["show_delete"], "easy_cancel"=>$defaults["easy_cancel"])));

        } else {
            $html = '<div class="error">This entry does not exist in the database. Please close this popup and refresh the page.</div>';
            $html .= $this->last_log_entry($defaults["class_name"], $id);
            $html .= $this->render_actions(array(), array("pause"=>false));
            $this->render_form($html);

        }

    }

    function last_log_entry($class_name, $id){
        global $db;
        //Select info from user log

        $html ='';
        $selection = $db->select("u.fullname, l.time, l.action, l.comment","system_log l",array("WHERE object_id=? and object=?", array('user_id' => $id, 'object_id' => strtolower($class_name)), array('integer', 'varchar')), array('joins' => "LEFT JOIN system_users u ON u.id=l.user_id", 'order_by' => "ORDER BY time DESC", "limit" => array('num_on_page' =>1)));

        if (!empty($selection)){
            $html .= '<div class="hint">Last System Log entry</div>';
            $html .= open_table();
            $html .= $this->render_table_row("User",$selection[0]->fullname);
            $html .= $this->render_table_row("Action",$selection[0]->action);
            $html .= $this->render_table_row("Time",zero_date($selection[0]->time,"jS F Y H:i"));
            $html .= close_table();
            $html .= '<div class="hint">Entry details</div>';
            $html .= $selection[0]->comment;
        }

        return $html;
    }

    function jbox_popup($title = 'Your action could not be completed', $new_html = ''){
        $html = '
        <div class="jbox_overlay">
            <div data-jbox="load" id="jbox_main" class="jbox_main">
                <div data-jbox="header" class="jbox_header clearfix" rel="'.date("His").'">
                    <div data-jbox="title" class="title">'.$title.'</div>
                    <div data-jbox="close" class="close">
                        <i class="fa fa-times"></i>
                    </div>
                </div>
                <div data-jbox="content" class="jbox_content">
                    <div class="jbox_content_inner">'.$new_html.'</div>
                </div>
            </div>
        </div>';

        return $html;
    }

    function create_table_header($defaults) {
        global $cfg, $db, $user1;

        if ($defaults["dir"] == "ASC"){
            $defaults["dir"]="DESC";
            $title="Sort Descending";
        } else {
            $defaults["dir"]="ASC";
            $title="Sort Ascending";
        }

        if (
                !empty($defaults["view_options"]["no_pagination"])
                || ( empty($defaults['pagination']))
                || ($defaults['total'] <= $defaults["num_on_page"])
                || ($defaults['external'])
        ) {
            $class = "list_table summary tablesorter float_header";
        } else {
            $class = "list_table summary float_header";
        }

        if (!empty($defaults['rc_enabled']) && empty($user1->preferences->disable_rc_menu)) $class .= " rc_enabled";
        if (!empty($defaults['fix_toggler'])) $class .= " fix_toggler";
        if (!empty($defaults['no_hover'])) $class .= " no_hover";

        $this->columns_number = 0;

        $html = '<table class="'.$class.'">
            <thead>
                <tr class="header">';

        // MAIN LOOP
        foreach ($defaults['view_array'] as $key => $heading) {

            $filter = ''; // reset filter
            $parent_class = ""; // parent filter class - adds padding

            $style = '';
            // build widths
            if (!empty($heading["width"]) || !empty($heading["min-width"])) {
                $style = 'style = "';
                if (!empty($heading["width"])) $style .= 'width:'.$heading["width"].';';
                if (!empty($heading["min-width"])) $style .= 'min-width:'.$heading["min-width"].';';
                $style .= '"';
            }

            // add classes, and build filter trigger only for some columns
            $class = "column_" . $key . " ";
            if ($key == "item_number" && empty($heading->show_name)) {
                $class .= "table_bottom";

            } else if ($key=="edit" || $key=="delete" || $key=="multiselect") {
                $class .= "no_export ";

            } else if (empty($heading["hide_filter"]) && !empty($heading["column"])) { // filters start

                // case when this is linking table, get a list of possible values from the other table
                if (
                        !empty($heading["filter"]) &&
                        !empty($heading["filter"]["table"]) &&
                        !empty($heading["filter"]["value"])
                ){

                    $input_type = (!empty($heading["filter"]["type"]) && $heading["filter"]["type"] == "radio") ? "radio" : "checkbox"; // radio vs. checkbox
                    $value = explode(".", $heading["filter"]["value"]);
                    $column = explode(".", $heading["filter"]["column"]);
                    (isset($heading["filter"]["order"])) ? $filter_order = $heading["filter"]["order"] : $filter_order = "ORDER BY ".$column[1] ." ASC";
                    $where = array("",array(),array());

                    //If 'target' is set, choose values only present in current table, not all values in join table
                    if (!empty($heading["filter"]["target"])) $where= array("WHERE ".$value[1]." IN (SELECT DISTINCT ".$heading["filter"]["target"]." FROM ".$defaults['table'].")",array(),array());

                    // Choose our selection (Note (PostgreSQL, SQL Server): ORDER BY expressions must apprear in select list when using SELECT DISTINCT).
                    if (empty($where[0]) || !isset($heading["filter"]["order"])) {
                        $list = $db->select_distinct($value[1] . ", " . $column[1], $heading["filter"]["table"], $where, array('order_by' => $filter_order));
                    } else {
                        $list = $db->select($value[1] . ", " . $column[1], $heading["filter"]["table"], $where, array('order_by' => $filter_order));
                    }

                    if (!empty($list)){
                        $inner_filter = '';
                        $reset_link = array("page"=>"1", "filter"=>array());
                        $has_filter = false;

                        // show blank selection control
                        if (!empty($heading["filter"]["show_blank"])) {

                            //if this is a radio selection, build empty (reset) values for "blank" options
                            $unique_switch = array();
                            if (!empty($heading["filter"]["type"]) && $heading["filter"]["type"] == "radio"){
                                foreach($list as $resetoption){
                                    $unique_switch[$heading["filter"]["value"]."--blank"] = "";
                                }
                            }

                            $inner_filter .= '<li>';
                            if (!empty($defaults["column_filter"][$heading["filter"]["value"]]) && in_array("blank", $defaults["column_filter"][$heading["filter"]["value"]])){
                                $inner_filter .= '<input type="'.$input_type.'" checked="checked" />
                                <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["filter"]["value"]."--blank"=>"")))).'"><i>(Blanks)</i></a>';
                                $reset_link["filter"][$heading["filter"]["value"]."--blank"] = ""; // build reset link
                                $has_filter = true; // flag this column as filtered

                            } else {
                                $inner_filter .= '<input type="'.$input_type.'"/>';
                                if (!empty($unique_switch)) {
                                    $unique_switch[$heading["filter"]["value"]."--blank"] = "1";
                                    $inner_filter .= '<a href="'. get_enc_page(array($this->div_id => array("page"=>1, "filter"=>$unique_switch))).'"><i>(Blanks)</i></a>';
                                } else {
                                    $inner_filter .= '<a href="'. get_enc_page(array($this->div_id => array("page"=>1, "filter"=>array($heading["filter"]["value"]."--blank"=>"1")))).'"><i>(Blanks)</i></a>';
                                }
                            }
                            $inner_filter .= '</li>';
                        }

                        foreach($list as $option){

                            // if this is a radio selection, build empty (reset) values for each option
                            $unique_switch = array();
                            if (!empty($heading["filter"]["type"]) && $heading["filter"]["type"] == "radio"){
                                foreach($list as $resetoption){
                                    $unique_switch[$heading["filter"]["value"]."--".$resetoption->$value[1]] = "";
                                }
                            }

                            $inner_filter .= '<li>';
                            if (!empty($defaults["column_filter"][$heading["filter"]["value"]]) && in_array($option->$value[1], $defaults["column_filter"][$heading["filter"]["value"]])){
                                $inner_filter .= '<input type="'.$input_type.'" checked="checked" />
                                <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["filter"]["value"]."--".$option->$value[1]=>"")))).'">'.$option->$column[1].'</a>';
                                $reset_link["filter"][$heading["filter"]["value"]."--".$option->$value[1]] = ""; // build reset link
                                $has_filter = true; // flag this column as filtered

                            } else {
                                $inner_filter .= '<input type="'.$input_type.'"/>';
                                if (!empty($unique_switch)) {
                                    $unique_switch[$heading["filter"]["value"]."--".$option->$value[1]] = "1";
                                    $inner_filter .= '<a href="'. get_enc_page(array($this->div_id => array("page"=>1, "filter"=>$unique_switch))).'">'.$option->$column[1].'</a>';
                                } else {
                                    $inner_filter .= '<a href="'. get_enc_page(array($this->div_id => array("page"=>1, "filter"=>array($heading["filter"]["value"]."--".$option->$value[1]=>"1")))).'">'.$option->$column[1].'</a>';
                                }
                            }
                            $inner_filter .= '</li>';
                        }

                        $filter = ($has_filter) ? '<div class="cfilter hasFilter">' : '<div class="cfilter">';
                            $filter .= '<span class="ico"><i class="fa fa-caret-down"></i></span>
                            <div class="filter_wrap">
                                <ul class="filter_option">
                                ' . $inner_filter . '
                                </ul>';
                                if (!empty($has_filter)) $filter .= '<a class="link" href="'.get_enc_page(array($this->div_id=>$reset_link)).'"><span></span>Reset</a>';
                            $filter .= '</div>
                        </div>';
                        $parent_class = "fltr_parent ";
                    }

                } else if (!empty($heading["data_type"]) && $heading["data_type"] == "date") {

                    $has_filter = false;
                    $has_text_filter = false;
                    $reset_link = array("page"=>"1", "filter"=>array());
                    $inner_filter = '';
                    $input_value = "";
                    $col_head_filter = (!empty($defaults["column_filter"][$heading["column"]])) ? $defaults["column_filter"][$heading["column"]] : null;

                    //Date filter
                    if (
                            !empty($col_head_filter)
                            && !is_array($col_head_filter)
                            && date('Y-m-d', strtotime($col_head_filter)) == $col_head_filter
                    ){
                        $has_filter = true;
                        $has_text_filter = false;
                        $reset_link["filter"][$heading["column"]."--date_filter"] = "";
                        $reset_link["filter"][$heading["column"]."--cdw"] = "";
                        $reset_link["filter"][$heading["column"]."--cdm"] = "";
                        $reset_link["filter"][$heading["column"]."--cd3"] = "";
                        $input_value = $col_head_filter;
                    }

                    if (
                            !empty($col_head_filter)
                            && is_array($col_head_filter)
                            && in_array("cdw", $col_head_filter)
                    ){
                        $inner_filter .= '
                        <li>
                            <input type="radio" checked="checked"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cdw"=>"")))).'">Past week</a>
                        </li>';
                        $has_filter = true;
                        $has_text_filter = true;
                        $reset_link["filter"][$heading["column"]."--cdw"] = "";
                    } else {
                        $inner_filter .= '
                        <li>
                            <input type="radio"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cdw"=>"1", $heading["column"]."--cdm"=>"", $heading["column"]."--cd3"=>"", $heading["column"]."--date_filter"=>"")))).'">Past week</a>
                        </li>';
                    }

                    if (
                            !empty($col_head_filter)
                            && is_array($col_head_filter)
                            && in_array("cdm", $col_head_filter)
                    ){
                        $inner_filter .= '
                        <li>
                            <input type="radio" checked="checked"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cdm"=>"")))).'">Past month</a>
                        </li>';
                        $has_filter = true;
                        $has_text_filter = true;
                        $reset_link["filter"][$heading["column"]."--cdm"] = "";
                    } else {
                        $inner_filter .= '
                        <li>
                            <input type="radio"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cdm"=>"1", $heading["column"]."--cdw"=>"", $heading["column"]."--cd3"=>"", $heading["column"]."--date_filter"=>"")))).'">Past month</a>
                        </li>';
                    }

                    if (
                            !empty($col_head_filter)
                            && is_array($col_head_filter)
                            && in_array("cd3", $col_head_filter)
                    ){
                        $inner_filter .= '
                        <li>
                            <input type="radio" checked="checked"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cd3"=>"")))).'">Past 3 months</a>
                        </li>';
                        $has_filter = true;
                        $has_text_filter = true;
                        $reset_link["filter"][$heading["column"]."--cd3"] = "";
                    } else {
                        $inner_filter .= '
                        <li>
                            <input type="radio"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cd3"=>"1", $heading["column"]."--cdw"=>"", $heading["column"]."--cdm"=>"", $heading["column"]."--date_filter"=>"")))).'">Past 3 months</a>
                        </li>';
                    }

                    $filter = ($has_filter) ? '<div class="cfilter hasFilter">' : '<div class="cfilter">';
                        $filter .= '<span class="ico"><i class="fa fa-caret-down"></i></span>
                        <div class="filter_wrap">
                            <form class="inline_picker_form" method="post">
                                <input class="datef" name="table_'.$defaults["table_name"].'_'.$this->tableid.'[filter]['.$heading["column"].'--date_filter]" value="'.$input_value.'" />
                            </form>';

                            if ($has_text_filter){
                                $filter .= '<div class="filter_picker hide"></div>
                                <span class="dswitch">Show calendar</span>
                                <ul class="filter_option doptions">';
                            } else {
                                $filter .= '<div class="filter_picker"></div>
                                <span class="dswitch">Show options</span>
                                <ul class="filter_option doptions hide">';
                            }

                                $filter .= $inner_filter . '
                            </ul>';
                            if (!empty($has_filter)) $filter .= '<a class="link" href="'.get_enc_page(array($this->div_id=>$reset_link)).'"><span></span>Reset</a>';
                        $filter .= '</div>
                    </div>';
                    $parent_class = "fltr_parent ";

                } else if (!empty($heading["data_type"]) && $heading["data_type"] == "tinyint") {

                    $has_filter = false;
                    $inner_filter = '<li>';
                    $col_head_filter = (!empty($defaults["column_filter"][$heading["column"]])) ? $defaults["column_filter"][$heading["column"]] : null;

                    if (
                            !empty($col_head_filter)
                            && in_array("1", $col_head_filter)
                    ){
                        $inner_filter .= '
                        <input type="radio" checked="checked"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--1"=>"")))).'">Yes</a>';
                        $has_filter = true; // flag this column as filtered
                    } else {
                        $inner_filter .= '
                        <input type="radio"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--0"=>"0", $heading["column"]."--1"=>"1")))).'">Yes</a>';
                    }
                    $inner_filter .= '
                        </li>
                    <li>';
                    if (
                            !empty($col_head_filter)
                            && in_array("0", $col_head_filter)
                    ){
                        $inner_filter .= '<input type="radio" checked="checked"/>
                        <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--0"=>"")))).'">No</a>';
                        $has_filter = true; // flag this column as filtered
                    } else {
                        $inner_filter .= '<input type="radio"/>
                        <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--0"=>"1", $heading["column"]."--1"=>"0")))).'">No</a>';
                    }
                    $inner_filter .= '</li>';

                    $filter = ($has_filter) ? '<div class="cfilter hasFilter">' : '<div class="cfilter">';
                        $filter .= '<span class="ico"><i class="fa fa-caret-down"></i></span>
                        <div class="filter_wrap">
                            <ul class="filter_option">
                            ' . $inner_filter . '
                            </ul>';
                            if (!empty($has_filter)) $filter .= '<a class="link" href="'.get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--0"=>"", $heading["column"]."--1"=>"")))).'"><span></span>Reset</a>';
                        $filter .= '</div>
                    </div>';
                    $parent_class = "fltr_parent ";

                } else if (!empty($heading["data_type"]) && !empty($heading["filter"]["set"])){

                    $inner_filter = "";
                    $has_filter = false;
                    $reset_link = array("page"=>"1", "filter"=>array());
                    $col_head_filter = (!empty($defaults["column_filter"][$heading["column"]])) ? $defaults["column_filter"][$heading["column"]] : null;

                    foreach($heading["filter"]["set"] as $option){
                        $inner_filter .= '<li>';
                        if (
                                !empty($col_head_filter)
                                && in_array($option, $col_head_filter)
                        ){
                            $inner_filter .= '<input type="checkbox" checked="checked" />
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--".$option=>"")))).'">'.$option.'</a>';
                            $reset_link["filter"][$heading["column"]."--".$option] = ""; // build reset link
                            $has_filter = true; // flag this column as filtered
                        } else {
                            $inner_filter .= '<input type="checkbox"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--".$option=>"1")))).'">'.$option.'</a>';
                        }
                        $inner_filter .= '</li>';
                    }

                    $filter = ($has_filter) ? '<div class="cfilter hasFilter">' : '<div class="cfilter">';
                        $filter .= '<span class="ico"><i class="fa fa-caret-down"></i></span>
                        <div class="filter_wrap">
                            <ul class="filter_option">
                                ' . $inner_filter . '
                            </ul>';
                            if (!empty($has_filter)) $filter .= '<a class="link" href="'.get_enc_page(array($this->div_id=>$reset_link)).'"><span></span>Reset</a>';
                        $filter .= '</div>
                    </div>';
                    $parent_class = "fltr_parent ";

                // show 3 groups of letters
                } else if (!empty($heading["data_type"]) && (in_array($heading["data_type"], array("varchar", "character varying")))){

                    $inner_filter = "";
                    $has_filter = false;
                    $reset_link = array("page"=>"1", "filter"=>array());
                    $col_head_filter = (!empty($defaults["column_filter"][$heading["column"]])) ? $defaults["column_filter"][$heading["column"]] : null;

                    if (
                            !empty($col_head_filter)
                            && in_array("cah", $col_head_filter)
                    ){
                        $inner_filter .= '<li>
                        <input type="checkbox" checked="checked"/>
                        <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cah"=>"")))).'">A &ndash; H</a>
                        </li>';
                        $has_filter = true;
                        $reset_link["filter"][$heading["column"]."--cah"] = "";
                    } else {
                        $inner_filter .= '<li>
                        <input type="checkbox"/>
                        <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cah"=>"1")))).'">A &ndash; H</a>
                        </li>';
                    }

                    if (
                            !empty($col_head_filter)
                            && in_array("cip", $col_head_filter)
                    ){
                        $inner_filter .= '
                        <li>
                            <input type="checkbox" checked="checked"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cip"=>"")))).'">I &ndash; P</a>
                        </li>';
                        $has_filter = true;
                        $reset_link["filter"][$heading["column"]."--cip"] = "";
                    } else {
                        $inner_filter .= '
                        <li>
                            <input type="checkbox"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cip"=>"1")))).'">I &ndash; P</a>
                        </li>';
                    }

                    if (
                            !empty($col_head_filter)
                            && in_array("cqz", $col_head_filter)
                    ){
                        $inner_filter .= '
                        <li>
                            <input type="checkbox" checked="checked"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cqz"=>"")))).'">Q &ndash; Z</a>
                        </li>';
                        $has_filter = true;
                        $reset_link["filter"][$heading["column"]."--cqz"] = "";
                    } else {
                        $inner_filter .= '
                        <li>
                            <input type="checkbox"/>
                            <a href="'. get_enc_page(array($this->div_id=>array("page"=>1, "filter"=>array($heading["column"]."--cqz"=>"1")))).'">Q &ndash; Z</a>
                        </li>';
                    }

                    $filter = ($has_filter) ? '<div class="cfilter hasFilter">' : '<div class="cfilter">';
                        $filter .= '<span class="ico"><i class="fa fa-caret-down"></i></span>
                        <div class="filter_wrap">
                            <ul class="filter_option">
                                '.$inner_filter.'
                            </ul>';
                            if (!empty($has_filter)) $filter .= '<a class="link" href="'.get_enc_page(array($this->div_id=>$reset_link)).'"><span></span>Reset</a>';
                        $filter .= '</div>
                    </div>';
                    $parent_class = "fltr_parent ";
                }
            }

            // extra and toggle buttons
            $extra = (!empty($heading["extra"])) ? $heading["extra"] : '';

            if (!empty($heading["toggle_all"])) {
                $append = '<span class="show_all_exp"  data-text-toggler-all="show">
                    <i class="fa fa-plus"></i>
                    <i class="fa fa-minus"></i>
                </span>';
                $filter = ""; // override filter

            } else {

                $append = '';
            }

            if (!array_key_exists("display", $heading) || !empty($heading["display"])) {

                // If it is sortable
                if(!empty($heading["column"]) && $defaults['header_sorting']){

                    // if the current table is already sorted
                    if ($heading["column"] == $defaults["orderby"]) {
                        if ($defaults["dir"]=="ASC") {
                            $class .= "sort headerSortUp ";
                        } else if($defaults["dir"]=="DESC") {
                            $class .= "sort headerSortDown ";
                        }
                    } else {
                        $class .= "sort ";
                    }

                    if (isset($heading["show_name"]) && $heading["show_name"] != "1") {
                        if (!empty($heading["name"])) {
                            $html .= '
                                <th '.$style.' class="'.$class.'">
                                    <div class="inner '.$parent_class.'clearfix">
                                        '.$extra.' '.$filter.' '.$append.'
                                        <span class="hide cnme">
                                            <i class="fa fa-sort"></i>
                                            <i class="fa fa-sort-asc"></i>
                                            <i class="fa fa-sort-desc"></i>
                                            '.$heading["name"].'
                                        </span>
                                    </div>
                                </th>';

                                $this->columns_number++;

                        } else {
                            $html .= '
                                <th '.$style.' class="'.$class.'">
                                    <div class="inner '.$parent_class.'clearfix">'.$extra.' '.$filter.' '.$append.'</div>
                                </th>';

                                $this->columns_number++;
                        }
                    } else {
                        $html .= '
                                <th '.$style.' class="'.$class.'">
                                    <div class="inner '.$parent_class.'clearfix">
                                        <a class="tooltip sort" href="'.get_enc_page(array($this->div_id=>array('orderby'=>$heading["column"], 'dir'=>$defaults["dir"]))).'" title="'.$title.'">
                                            <span class="cnme">
                                                <i class="fa fa-sort"></i>
                                                <i class="fa fa-sort-asc"></i>
                                                <i class="fa fa-sort-desc"></i>
                                                '.$heading["name"].' '.$extra.'
                                            </span>
                                        </a>'.$filter.' '.$append.'
                                    </div>
                                </th>';

                                $this->columns_number++;
                    }

                // If the column is not sortable
                } else {
                    if (isset($heading["show_name"]) && $heading["show_name"] != "1") {

                        if (!empty($heading["name"])) {
                            $html .= "<th class=\"$class no_sort\" $style><div class=\"inner ".$parent_class."\">$extra $append<span class=\"hide cnme\">".$heading["name"]. "</span></div></th>\n";
                            $this->columns_number++;
                        } else {
                            $html .= "<th class=\"$class no_sort\" $style><div class=\"inner ".$parent_class."\">$extra $filter $append</div></th>\n";
                            $this->columns_number++;
                        }

                    } else {
                        $html .= "<th class=\"$class no_sort\" $style><div class=\"inner ".$parent_class."\">$extra<span class=\"only_t cnme\">".$heading["name"]. "</span>$filter $append</div></th>\n";
                        $this->columns_number++;
                    }
                }
            }
        }
        $html .= '
            </tr>
        </thead>';

        return $html;
    }

    public function generatePagination($defaults = array())
	{
		global $cfg, $crypt, $my_get, $user1;

		$total = $defaults["total"];
		$num_of_pages = $defaults["num_of_pages"];

		$page = (!empty($my_get[$this->div_id]["page"])) ? $my_get[$this->div_id]["page"] : 1;

		if ($page > $num_of_pages) {
			$page = 1;
			$my_get[$this->div_id]["page"] = 1;
		}

		$paginator_move = true;
		$pages_around = 5;

		$prev_page = ($page == 1) ? 1 : $page - 1;
		$next_page = ($page == $num_of_pages) ? $num_of_pages : $page + 1;

		$html = '<div class="paginator clearfix">';

			// there are any view options (pagination off or hidden columns)
		if (!empty($defaults["view_options"]["no_pagination"]) && !empty($defaults["view_options"]["columns"])) {
			$html .= '
				<a class="tooltip ico_pagination_reload" href="'.get_enc_page(array($this->div_id => array("view_reload"=>"1", "show_all_cols"=>"", "page"=>1))).'" title="Reset">
					<span>&nbsp;</span>
				</a>';
		}

		if ($num_of_pages > 1) {
			// pagination is hidden, show only reset button
			if (!empty($defaults["view_options"]) && !empty($defaults["view_options"]["no_pagination"]) && $defaults["view_options"]["no_pagination"] == 1) {
				$html .= '
					<ul class="inner">
						<li>
							<a class="tooltip ico_pagination_on" href="'.get_enc_page(array($this->div_id => array("no_pagination"=>"0", "view_reload"=>"", "show_all_cols"=>""))).'" title="Turn on pagination">
								<i class="fa fa-bars"></i>
							</a>
						</li>
					</ul>
					<div class="ntotal">'.$total.' items</div>';
			} else {
				$html .= '<ul class="inner">';

				// allow user to disable pagination, only if there are less than 500 rows
				if ($total < 500) {
					$html .= '
						<li>
							<a class="ico_pagination_off tooltip" href="'.get_enc_page(array($this->div_id => array("no_pagination"=>"1", "view_reload"=>"", "show_all_cols"=>""))).'" title="Turn off pagination">
								<i class="fa fa-times"></i>
							</a>
						</li>';
				}

				$html .= '
						<li class="first">
							<a href="'.get_enc_page(array($this->div_id => array("page"=>"1"))).'" class="first tooltip" title="First page">
								<span class="txt">First</span>
								<i class="fa fa-caret-left"></i>
								<i class="fa fa-caret-left"></i>
							</a>
						</li>
						<li class="prev">
							<a href="'.get_enc_page(array($this->div_id => array("page"=>$prev_page))).'" class="prev tooltip" title="Previous page">
								<i class="fa fa-caret-left"></i>
								<span class="txt">Previous</span>
							</a>
						</li>
						<li class="next">
							<a href="'.get_enc_page(array($this->div_id => array("page"=>$next_page))).'" class="next tooltip" title="Next page">
								<span class="txt">Next</span>
								<i class="fa fa-caret-right"></i>
							</a>
						</li>
						<li class="last">
							<a href="'.get_enc_page(array($this->div_id => array("page"=>$num_of_pages))).'" class="last tooltip" title="Last page">
								<span class="txt">Last</span>
								<i class="fa fa-caret-right"></i>
								<i class="fa fa-caret-right"></i>
							</a>
						</li>
					</ul>';

				$lpn = ($page == $num_of_pages) ? $total : $page * $user1->preferences->pagination;
				$html .= '<div class="ptotal">Showing '.((($page - 1) * $user1->preferences->pagination) + 1).' - '.($lpn).' of '.$total.'</div>
					<div class="pdd pages first">
						<span class="label">Jump to page</span>
						<div class="ddmenu">
							<span class="top">
								<b>'.$page.'</b> ('.((($page - 1) * $user1->preferences->pagination) + 1 ).' - '.($page * $user1->preferences->pagination).')
							</span>
							<span class="handle">
								<i class="fa fa-caret-down"></i>
							</span>
							<span class="wrap">';

				for ($i = 1; $i <= $num_of_pages; $i++) {

					$lpn = ($i == $num_of_pages) ? $total : $i * $user1->preferences->pagination;
					$current_page = ($i == $page) ? 'class="current"' : '';

					$html .= '
						<a '.$current_page.' href="' . get_enc_page(array($this->div_id => array("page"=>$i))) . '">
							<b>'.$i.'</b> ('.((($i - 1) * $user1->preferences->pagination) + 1 ).' - '.($lpn).')
						</a>';
				}

				$html .= '</span>
						</div>
					</div>';

				$this->js .= '
					<script type="text/javascript">
						var pag_current_'.$defaults["table_name"].'_'.$this->tableid.' = '.ceil($page/5).';
						var pag_total_'.$defaults["table_name"].'_'.$this->tableid.' = '.ceil($num_of_pages/5).';
					</script>';

				// change pagination
				$html .= '
					<div class="pdd">
						<span class="label">Show</span>
						<div class="ddmenu pagm">
							<span class="top"><b>'.$user1->preferences->pagination.'</b></span>
							<span class="handle">
								<i class="fa fa-caret-down"></i>
							</span>
							<span class="wrap">
								<a href="' . get_enc_page(array("update_pagination" => "20")) . '"><b>20</b></a>
								<a href="' . get_enc_page(array("update_pagination" => "50")) . '"><b>50</b></a>
								<a href="' . get_enc_page(array("update_pagination" => "100")) . '"><b>100</b></a>
							</span>
						</div>
						<span class="label">items per page</span>
					</div>';
			}
		} elseif ($num_of_pages == 1 && $total > 20) {
			$html .= '
				<div class="ptotal">'.$total.' items</div>
				<div class="pdd">
					<span class="label">Show</span>
					<div class="ddmenu pagm">
						<span class="top"><b>'.$user1->preferences->pagination.'</b></span>
						<span class="handle">
							<i class="fa fa-caret-down"></i>
						</span>
						<span class="wrap">
							<a href="' . get_enc_page(array("update_pagination" => "20")) . '"><b>20</b></a>
							<a href="' . get_enc_page(array("update_pagination" => "50")) . '"><b>50</b></a>
							<a href="' . get_enc_page(array("update_pagination" => "100")) . '"><b>100</b></a>
						</span>
					</div>
					<span class="label">items per page</span>
				</div>';
		}

		$html .= '</div>';

		return $html;
	}

    function inline_calendar($selected_date = "", $field_name = "date"){
        if (empty($selected_date)) $selected_date = date("Y-m-d");
        $html = '
            <form class="inline_picker_form hide" method="post" href="'.get_enc_page().'">
                <input class="datef" name="'.$field_name.'" value="'.$selected_date.'" />
            </form>
            <div class="filter_picker_wrap">
                <div class="filter_picker"></div>
            </div>';
        return $html;
    }

    function dump($options = array()){
        $x = new StdClass;
        foreach($this as $key=>$value) $x->$key=$value;//clone, so as not to mess up $this!
        $defaults = array(
                'unset_html'=>true,
                'unset_array'=>array(),
                'toggle_all'=>false,
                'title'=>"Libhtml",
                'textarea'=>true
        );

        foreach($options as $key=>$value) $defaults[$key] = $value;
        if ($defaults['unset_html']) unset($x->html);
        foreach($defaults['unset_array'] as $item) unset($x->$item);
        return dump_array($x,$defaults);
    }

    function name_table($table_name = "", $where = ""){
        if (is_array($where)) $where = serialize($where);
        return substr(md5($this->sys_path . $table_name . $where), 0, 5);
    }

    function show_popup($options = array()){
        global $cfg, $db, $user1;

        // we might add other features later
        $_SESSION["show_popup"] = $options;
    }

    /**
     * Returns the contents of the file systems/src/VERSION.txt which holds
     * the git version number and is automatically created on push.
     * FILE MUST BE IN IGNORE LIST!!
     *
     * @return [string] The git version string for display.
     */
    function getCodeVersion() {
        $version = 'unknown';
        if (file_exists(dirname(__FILE__) . '/../VERSION.txt')) {
            $version = file_get_contents(dirname(__FILE__) . '/../VERSION.txt');
        }
        return $version;
    }

    // used to render actions panel at the bottom of the form
    // $list_of_actions is not required, only first argument
    function render_actions_new($list_of_actions = array(), $options = array()){
        global $crypt, $my_post, $my_get, $cfg, $user1;

        $defaults = array(
            'show_wrap'=>true, // '<div class="actions"> wrap html
            'show_cancel'=>true, // 'Cancel' button
            'block_page'=>true, // if a Please wait feedback should be shown on submit
        );

        if (!empty($options)) foreach($options as $key=>$value) $defaults[$key] = $value;

        $html = '';
        if ($defaults["show_wrap"]) $html .= '<div class="actions'.(!empty($defaults["block_page"]) ? ' block_page' : '').'">';

            // append all submit buttons
            // all buttons are already formatted as the html at this point
            if (!empty($list_of_actions)){
                foreach($list_of_actions as $action){
                    $html .= $action;
                }
            }

            // cancel
            if ($defaults['show_cancel']) $html .= $this->render_cancel_button();

        if ($defaults["show_wrap"]) $html .= '</div>';

        return $html;
    }

}
//**************************************************************************


// class ends here
