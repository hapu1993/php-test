<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "tab" => my_get("tab","patients"),
        "title" => "Patients and Admissions"
    ));

    if ($libhtml->tab=="patients") {

        $object = new Patient;
        $libhtml->title .= " - Patients";
        $libhtml->page_actions[] = $object->print_action_button('add', array('class'=>'blue'));

        $html .= $object->_list(array(
            'info'=>true
        ));

    } elseif ($libhtml->tab=="admissions") {

        $object = new Admission;
        $libhtml->title .= " - Admissions";
        
        $html .= $object->_list(array(
            'info'=>true
        ));

    } 

    $libhtml->render($html);
