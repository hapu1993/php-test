<?php

    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml->page_start("Information");

    exec('ps -ef',$output);
    foreach($output as $item) $result[] = explode(" ",$item);
    $html .= dump_array($result);

    $libhtml->page_end($html);
