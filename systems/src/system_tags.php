<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $object = new System_Tag;
    $libhtml = new Libhtml(array(
        "title" => "System Tags"
    ));

    $html .= $object->_list(array(
        'width'=>"100%",
    ));

    $libhtml->render($html);
