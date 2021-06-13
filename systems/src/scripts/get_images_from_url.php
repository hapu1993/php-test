<?php

    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

     $cache = new Cache;
     $html = "Hello world";

     echo $html;
     $db->close();

?>
