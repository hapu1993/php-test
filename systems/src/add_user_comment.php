<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $a = new User_Comment;
    $a->page = my_get('page');
    $a->screenshot = "screenshots/" . my_get('sc');

    $libhtml->title = "Leave a comment about this page";

    $html .= $a->print_add_form();
    $libhtml->render_form($html);
