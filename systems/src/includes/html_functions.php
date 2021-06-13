<?php
function href_link($options = array()){
    global $cfg, $user1;

    $defaults = array(
        'permission'=> false,
        'button'=> true,
        'popup'=> true,
        'encrypt'=>true,
        'text'=> "",
        'url'=>"",
        'query_string'=>"",
        'icon'=>"",
        'float'=>"left",
        'clear' => true,
        'show_fields' => array(), // array of fields we want do show in a popup edit forms
        'easy_cancel' => false, // should "are you sure..." message appear when you try to close a jbox
        'tooltip'=>"",
        'title'=>"",
        'class'=>"",
        'extra'=>"",
        'load_submenu'=>false,
        'expand_details'=>"",
        'expand_method'=>'ajax_details',
        'expand_url_details'=>false, // do not follow the link on click but just expand more details (for objects without details pages)
        'target'=>"",
        'id'=>"",
        'ico_ext'=>"",
        'click_trigger'=>false,
        'pblock'=>false, // if we want to show Please wait.. page cover / blocker when this link is clicked
        'jbox-width'=>false, // set the fixed jbox width
    );

    if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

    $html = '';

    if ($defaults["permission"]) {

        // build all classes first
        $class = 'class="';

        if ($defaults["button"]) $class .= "btn ";
        if ($defaults["icon"]) $class .= $defaults["icon"] . " wicon ";
        if ($defaults["popup"]) $class .= "jbox ";
        if ($defaults["easy_cancel"]) $class .= "ecbox ";
        if ($defaults["float"]) $class .= $defaults["float"] . " ";
        if ($defaults["tooltip"]) $class .= "tooltip ";
        if ($defaults["pblock"]) $defaults["extra"] .= 'data-page-block="true" ';
        if ($defaults["jbox-width"]) $defaults["extra"] .= 'data-jbox-width="'.str_replace("px", "", $defaults["jbox-width"]).'" ';
        if ($defaults["class"]) $class .= $defaults["class"] . " ";
        if ($defaults["click_trigger"]) $class .= "click_me ";
        if ($defaults["expand_url_details"]) $class .= "expand_url_details ";
        if (!empty($defaults["load_submenu"]) && $user1->preferences->shortcuts_menu){
            $defaults["extra"] .= 'data-load-submenu="'.$defaults["load_submenu"].'" ';
        }
        $class .= '"';

        // append query string (only show some popup form fields)
        $defaults["query_string"] .= ((strpos($defaults["url"], "?") !== false) ? "&" : "?") . http_build_query(array("show_fields"=>$defaults["show_fields"]));

        $defaults["url"] .= $defaults["query_string"];
        if ($defaults['encrypt']) $defaults['url'] = encrypt_url($defaults["url"] . $defaults["query_string"]);

        if (!empty($defaults["expand_details"])) $html .= '
            <div class="expand_details tooltip" title="Expand details" rel="'.$defaults["expand_details"].'" data-method="'.$defaults["expand_method"].'">
                <i class="fa fa-caret-down"></i>
                <i class="fa fa-caret-up"></i>
            </div>';

        $html .= '<a '.$defaults["extra"].' href="'.$defaults["url"].'" ';
            if ($defaults["tooltip"]) $defaults["title"] = $defaults["tooltip"];
            if ($defaults["title"]) $html .= ' title="'.$defaults["title"].'" ';
            if ($defaults["id"]) $html .= ' id="'.$defaults["id"].'" ';
            if ($defaults["target"]) $html .= ' target="'.$defaults["target"].'" ';
        $html .= $class . '>';

            if (!empty($defaults["icon"])) $html .= '<span class="ico"></span>';
            $html .= $defaults["text"];
            if ($defaults["ico_ext"]) $html .= '<i class="fa fa-external-link"></i>';
        $html .= '</a>';

        if ($defaults["clear"]) $html .= '<div class="clear"></div>';

    } else {
        if (!$defaults["button"]) $html = $defaults["text"];

    }

    return $html;
}

function section($options = array()){

    $html = '<div class="section_title';
        if (!empty($options["collapsible"])) $html .= " sh_section";
        if (!empty($options["state"]) && $options["state"] == "collapsed") $html .= " coll";
        if (!empty($options["close_others"]) && $options["close_others"]) $html .= " cothers";
        if (!empty($options["disabled"]) && $options["disabled"]) $html .= " disabled";
    $html .= '">';

    $html .= '<span class="s_title">';
        if (!empty($options["collapsible"]) && $options["collapsible"]) $html .= "<span class=\"ico\"><i class=\"fa fa-caret-down\"></i><i class=\"fa fa-caret-right\"></i></span>";
    $html .= $options["title"]."</span>";

    if (!empty($options["actions"])) {
        $html .= '<div class="links">';
        foreach($options["actions"] as $action) $html .= $action;
        $html .= "</div>";
    }

    $html .= "</div>";
    return $html;
}

function open_table($width = "", $h3 = "", $table_class = "", $separator = false){
    global $ft_width, $ft_h3, $ft_table_class, $my_get;

    if (empty($width)) $width = "600px";
    if (empty($table_class)) $table_class = "action_form";
    if ($table_class) $table_class .= " separator";

    $table = '
            <table class="'.$table_class.'" style="width: '.$width.';">';

    if (!empty($h3)) $table .= '
                <tr class="table_title">
                    <td colspan="100%">
                        <h3>'.$h3.'</h3>
                    </td>
                </tr>';

    // assign to a global - used if form is tabbed
    $ft_width = $width;
    $ft_h3 = $h3;
    $ft_table_class = $table_class;

    return $table;
}

function open_form_tabs($options = array()){

    $defaults = array(
        'tabs'=>array(),
        'show_controls'=>true,
    );

    foreach($options as $key=>$value) $defaults[$key] = $value;

    $html = '<div class="jbox_inner_controls jbox_tabs clearfix">';
    $i = 0;

    foreach($defaults["tabs"] as $tab) {
        $active = ($i == 0) ? ' active' : '';
        $html .= '
                <a class="jtab'.$active.'" href="#">
                    <span class="txt">'.$tab.'</span>
                </a>';
        $i++;
    }

    // left & right controls
    if ($defaults["show_controls"]) {
        $html .= '
            <a data-jbox="previous_tab" class="jtab_control jtab_prev" href="#">
                <span class="ico"><i class="fa fa-angle-left"></i></span>
            </a>
            <a data-jbox="next_tab" class="jtab_control jtab_next" href="#">
                <span class="ico"><i class="fa fa-angle-right"></i></span>
            </a>';
    }

    $html .= '</div>';

    return $html;
}

function form_tab(){
    global $ft_width, $ft_h3, $ft_table_class;

    $html = "";
    if (!empty($ft_width)) { // if it is empty, this is a first tab on the form
        $html .= '</div>
        <div class="form_content">';
    }

    return $html;
}

function close_table() {
    return '</table>';
}

function table_separator($width = "", $h3="", $table_class="action_form", $has_help = false, $sort_order = false, $remove = false) {
    global $my_get;

    // don't separate if there is a form fields filter present
    if (!empty($my_get["show_fields"])) return false;
    if (empty($width)) $width = "600px";

    $table = '</table>
    <table class="'.$table_class.'" style="width: '.$width.'">';

        if (!empty($h3)) {
            $table .= '<tr class="table_title">
                <td colspan="100%">
                    <h3>'.$h3.'</h3>';
        }

        if ($has_help) $table .= '<span class="ico_binfo tooltip" title="View help" data-view-table-help="true"><i class="fa fa-info"></i></span>';
        if ($remove) $table .= '<span data-jbox="remove_section" class="ico_remove tooltip" title="Delete"><i class="fa fa-times"></i></span>';
        if ($sort_order) $table .= '<span data-jbox="sort_section" class="ico_sort tooltip" title="Drag to reorder"><i class="fa fa-sort"></i></span>';

        if (!empty($h3)) {
            $table .= '</td>
            </tr>';
        }

    return $table;
}

function table_help($help_string, $closed = true, $white_bg = true){
    $hide = $closed ? " hide" : '';
    $class = $white_bg ? ' white_msg' : '';

    return '
	<tr class="section_msg '.$hide.'" data-table-help="true">
        <td colspan="100%">
            <div class="msg '.$class.'">'.$help_string.'</div>
        </td>
    </tr>';

}

function text_toggler($input_text="", $n=60) {

    if (!empty($input_text)) {
        return '<div class="text_toggler rte">
            <span title="Show more" class="tooltip toggle" data-text-toggler="show">
                <i class="fa fa-plus"></i>
                <i class="fa fa-minus"></i>
            </span>
            ' . nl2br($input_text) . '
        </div>';
    }
}

function jquery_tabs($fields, $titles, $options = array()){
    global $cfg, $libhtml;

    $defaults = array(
        'ajax'=>false,
        'consecutive'=>false,
        'icons'=>array(),
        'div_clear'=>true,
        'div_class'=>""
    );
    foreach($options as $key=>$value) $defaults[$key] = $value;
    $html = "";

    // consecutive script
    if ($defaults["consecutive"]) {
        $libhtml->js .= '
            <script type="text/javascript">
                var tabs = new Array();
                var values = new Array();
                var i = 0;
            ';

        $i = 0;
        foreach($titles as $t) {
            $libhtml->js .= '
                values.push("'.make_seo_title($fields[$i]).'");
                tabs.push("'.make_seo_title($titles[$i]).'");';
            $i++;
        }

        $libhtml->js .= '
                $(document).ready(function() {
                    function Load(tab, value) {
                        $("#"+tab).load(value, function(){ i++; Load(tabs[i], values[i]); });
                    }
                Load(tabs[0], values[0]);
                });
            </script>';
    }

    $content = "";
    ($defaults["ajax"] && !($defaults["consecutive"])) ? $ajax_class = "ajax_tabs" : $ajax_class = "";
    ($defaults["icons"]) ? $ajax_class = "with_icons" : $ajax_class = "";
    $tabs = '
            <div class="clearfix jquery_tabs '.$ajax_class.$defaults["div_class"].'">
                <ul class="clearfix nav">';
    $i = 0;
    $id = rand( 0, 1000 ); // get the random number - this is a counter for panel ID's
    foreach($titles as $t) {

        // jquery-fy names
        $clean_title = make_seo_title($titles[$i]);
        $clean_field = make_seo_title($fields[$i]);

        if ($defaults["ajax"] && !($defaults["consecutive"])) {
            $tabs .= '
                    <li class="tab-'.$i.'">
                        <a href="'.$clean_field.'" title="'.$titles[$i].'">';
        } else {
            $tabs .= '
                    <li class="tab-'.$i.'">
                        <a href="#'.$clean_title.'">';
        }

        if (isset($defaults["icons"][$i])) {
            if (preg_match('/png|jpg|jpeg|gif/', extension($defaults["icons"][$i]))) $tabs .= '<img src="'.$defaults["icons"][$i].'" alt="'.$titles[$i].'" class="tooltip" title="'.$titles[$i].'"/>';
            else $tabs .= '<span class="ico_app tooltip" title="'.$titles[$i].'"><i class="fa fa-'.$defaults["icons"][$i].'"></i></span>';
            $extra_height = "icons";

        } else {
            $tabs .= $titles[$i];
            $extra_height = " ";
        }

        $tabs.=    '
                        </a>
                    </li>';

        if ($i!=0) {

            $content .= '
                    <div class="tab_title hide">';

            if (isset($defaults["icons"]) && !empty($defaults["icons"])) {
                if (preg_match('/png|jpg|jpeg|gif/', extension($defaults["icons"][$i]))) $content .= '<img src="'.$defaults["icons"][$i].'" alt="'.$titles[$i].'" class="tooltip" title="'.$titles[$i].'"/>';
                else $content .= '<span class="ico_app tooltip" title="'.$titles[$i].'"><i class="fa fa-'.$defaults["icons"][$i].'"></i></span>';

            } else {
                $content .= '<span>'.$titles[$i].'</span>';

            }

            $content .= '
                    </div>';
        }

        if ($defaults["div_clear"]) $content .= '
                    <div class="clear"></div>';

        if (!$defaults["ajax"] && !($defaults["consecutive"])) $content .= '
                    <div class="ui-tabs-hide" id="'.$clean_title.'">
                        <div class="padding_wrap clearfix">'.$fields[$i].'</div>
                    </div>';

        if ($defaults["consecutive"]) $content .= '
                    <div class="ui-tabs-hide" id="'.$clean_title.'">
                        <span class="loading">&nbsp;</span>
                    </div>';
        $i++;
        $id++;
    }

    if (!$defaults["ajax"] && count($fields) > 1) {
        $tabs.= '<li data-show-all-tabs="true" class="show_all '.$extra_height.'">
            Show All
        </li>';
    }

    $tabs .= '</ul>';

    $html .= $tabs . $content;

    if ($defaults["ajax"]) $html .= '
                <div class="ajax_tabs_loader">&nbsp;</div>';

    return $html . '
            </div>';

}

function ajax_toggle($id, $table, $field, $edit = false, $value=null, $icon = "ico_circle"){
    global $cfg, $user1, $db, $crypt;

    if (is_null($value)) $value=$db->select_value($field, $table, array("WHERE id=?", array('id' => $id), array('integer')));
    $toggle_class = ($value) ? $icon."_toggle_on" : $icon."_toggle_off";

    if ($edit) {
        $id = $crypt->str_encrypt(serialize(array($id,$table,$field)));
        return '<span id="'.$id.'" class="'.$toggle_class.' cur_pointer tooltip" title="Toggle" data-ajax-toggle="true">
            <i class="fa fa-check-circle"></i>
            <i class="fa fa-times-circle"></i>
        </span>';
    } else {
        return '<span class="'.$toggle_class.'">
            <i class="fa fa-check-circle"></i>
            <i class="fa fa-times-circle"></i>
        </span>';
    }
}

function multi_toggle($value,$class_name,$object_id,$target_field,$origin_table,$origin_value_field,$origin_name_field){
    global $cfg, $user1, $db, $crypt;

    if (empty($value)) $value="&nbsp;&nbsp;";

    $id = $crypt->str_encrypt(serialize(array(
            $class_name,
            $object_id,
            $target_field,
            $origin_table,
            $origin_value_field,
            $origin_name_field,
    )));

    return '<div id="'.$id.'" class="multi_toggle tooltip" title="Click to toggle" data-cell-toggle="true">
        <span class="text">'.$value.'</span>
        <span class="ico">
            <i class="fa fa-refresh"></i>
            <i class="fa fa-spin fa-refresh"></i>
        </span>
    </div>';
}

function inline_edit($value,$id, $table,$value_field,$id_field,$table_type){
    global $cfg, $user1, $db, $crypt;

    if (empty($value)) $value=null;

    $id = $crypt->str_encrypt(serialize(array(
        $id,
        $table,
        $value_field,
        $id_field,
        $table_type
    )));

    return '<div class="tooltip inline_edit" title="Click to edit" data-inline-edit="true">
        <span id="'.$id.'" class="text">'.$value.'</span>
        <span class="ico">
            <i class="fa fa-pencil"></i>
            <i class="fa fa-spin fa-refresh"></i>
        </span>
    </div>';
}

function tick_cross_image($bool=true, $show_false = true){
    if ($bool) {
        return '<span class="tooltip ico_circle_toggle_on" title="Yes"><i class="fa fa-check-circle"></i></span>';
    } else {
        return ($show_false) ? '<span class="tooltip ico_circle_toggle_off" title="No"><i class="fa fa-times-circle"></i></span>' : '';
    }
}

function breadcrumb($array){
    global $cfg, $db, $user1;

    $html = "<div class=\"breadcrumb\">";
        foreach($array as $link) $html .= $link;
    $html .= "</div>";
    return $html;
}

function universal_table($selection,$options=array()){
    global $cfg;
    $html ='';

    $defaults = array(
        'title'=>"",
        'class'=>"summary float_header",
        'table_wrapper'=>false,
        'width'=>"100%",
        'table_width'=>"100%",
        'quick_search'=>false,
        'print'=>false,
        'header'=>array(),
        'no_items_message'=>true,
    );

    if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

    if (!empty($selection) && count($selection)>0) {

        if (!empty($defaults['title'])) $html .= '
            <h3>'.$defaults['title'].'</h3>';

        $html .= '
            <div class="table_wrap clearfix left" style="width:'.$defaults['width'] .'">';

        if ($defaults['table_wrapper']) {
            $html .= '
                <div class="table_options">';

            if ($defaults['quick_search']) {
                $html .= '
                    <div class="quick_wrap">
                        <input tabindex="1" type="text" class="quick_filter right" value="Quick table filter"/>
                        <span class="reset_quick">&nbsp;</span>
                    </div>';
            }
            $html .= '
                </div>';
        }
        $html .= '
                <div class="table_parent">
                    <table class="' . $defaults['class'] . '" style="width:'.$defaults['table_width'] . ';">
                        <thead>
                            <tr>
                                <th style="width: 25px;"></th>';

        if (!empty($defaults['header'])){

            foreach ($defaults['header'] as $key=>$name) {

                if (is_array($name)){
                    $html .=  '
                                <th>
                                    <div class="inner">&nbsp;&nbsp;'.$name[0].'</div>
                                </th>';
                } else {
                    $html .=  '
                                <th>
                                    <div class="inner">&nbsp;&nbsp;'.$name.'</div>
                                </th>';
                }
            }
        }

        $html .= '
                            </tr>
                        </thead>';
        $row_count = 0;
        if (!empty($defaults['header'])){
            //Test $selection
            if (
                    !is_object($selection[0])
                    && is_array($selection[0])
                    && is_assoc_array($selection[0])
            ) {
                foreach($selection as $item) $result[]=(object) $item;
                $selection = $result;
            }
            foreach($selection as $item) {
                $row_count++;
                $row = ($row_count % 2 == 0) ? "even" : "odd";
                $html .= '
                            <tr class="'.$row.'">
                                <td>'.$row_count.'</td>';

                foreach ($defaults['header'] as $key=>$name) {

                    if (!isset($item->$key)){
                        $html .= '
                                <td>
                                ++</td>'; //if key not set return empty cell
                    } elseif (is_array($name)) {

                        $html .= '
                                <td>'.str_replace("#",$item->$key,$name[1]).'</td>';
                    } else {

                        $html .= '
                                <td>'.$item->$key.'</td>';
                    }
                }

                $html .= '
                            </tr>';
            }
        }

        $html .= '
                    </table>
                </div>
            </div>';

    } else {
        if ($defaults['no_items_message']) $html = "<div class=\"no_data\">No items found.</div>\n";
    }

    return $html;
}

function mail_table($table){
    $html = $table;
    $html = str_replace('<table','<table style="text-align:left; width:100%; font-size:12px; color:#000000; border-collapse:collapse; border:1px solid #DADDEC;"', $html);
    $html = str_replace('<th','<th style="padding:5px; background-color:#F0F1F7; border:1px solid #DADDEC; width:200px; color:#000; font-weight:normal;"', $html);
    $html = str_replace('<td','<td style="padding:5px; border:1px solid #E9E9E9; color:#000; font-weight:normal;"', $html);
    return $html;
}

// $type can either be a predefined (success, info, error) or a custom font-awesome icon can be specified (i.e. 'fa-send')
function g_feedback($type = "info", $msg = "Please wait..."){

    // red or black feedback style
    $class = ($type == "error") ? "g_error" : "g_info";

    // predefined types list
    $defined_types = array(
        "error"=>"fa-times",
        "success"=>"fa-check",
        "activate"=>"fa-check",
        "deactivate"=>"fa-check",
        "update"=>"fa-pencil",
        "info"=>"fa-info",
        "restore"=>"fa-rotate-right",
        "insert"=>"fa-plus",
        "delete"=>"fa-trash",
        "wait"=>"fa-spinner fa-pulse",
    );

    // format type / icon (will be skipped if icon is not predefined)
    if (!empty($defined_types[$type])) $type = $defined_types[$type];

    return '<div class="g_feedback '.$class.'">
        <span><i class="fa '.$type.'"></i></span>
        <p>'.$msg.'</p>
    </div>';

}
