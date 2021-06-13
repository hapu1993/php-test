<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
            "additional_tabs" => array('exception'=>"Exception"),
            "tab"=>"exception",
            "title" => "Code Error",
        ));

    $html = "<p>There seems to be a problem with the application code. This problem has been logged and reported.</p>";

    unset($_SESSION['error']);

    $libhtml->render($html);
