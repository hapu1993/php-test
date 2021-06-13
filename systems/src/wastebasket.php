<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $object = new Wastebasket;

    $libhtml->tab=my_get("tab","all");
    $libhtml->title = "Wastebasket";
    $libhtml->page_actions= array(
            $object->print_action_button('empty',array('class'=>'blue')),
    );

     $html .= $object->_list(array(
            'width'=>"100%",
            'edit'=>false,
    ));

    $libhtml->render($html);
