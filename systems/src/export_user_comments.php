<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $object = new User_Comment;
    $selection = $object->_get();

    header('Content-Type: text/x-comma-separated-values');
    header("Content-Disposition: attachment; filename=\"user_comments_" . date("_Y-m-d_H-i-s") . ".csv\"");
    header('Pragma: hack');

    $header = ",User,Date,Page,Comment,Status\n";

    echo $header;

    $i=0;
    foreach ($selection as $row) {
        $i++;
        $line = $i . ",";
        $line .= trim($row->user_name) . ",";
        $line .= zero_date($row->date,"d/m/Y H:i") . ",";
        $line .= $row->page . ",";
        $line .= "\"" . trim($row->comment) . "\",";
        $line .= $row->status . ",";
        $line .= "\n";
        echo $line;
    }
