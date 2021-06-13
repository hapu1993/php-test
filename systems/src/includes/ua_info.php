<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $libhtml->title="User Agent Infomation";

    $info = checkClient(my_get("ua"),false,false);

    $html = open_table("100%");

    foreach($info as $key=>$value){
        if ($value!='') $html .= $libhtml->render_table_row($key,$value);
    }

    $html .= close_table();

    $libhtml->render_form($html);
