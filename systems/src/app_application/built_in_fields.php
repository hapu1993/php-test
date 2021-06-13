<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "tab" => my_get("tab","hospital"),
        "title" => "Built In Fields"
    ));

    if ($libhtml->tab=="hospital") {

        $object = new Hospital;
        $libhtml->title .= " - ".$libhtml->local_text['Hospital'];
        $libhtml->page_actions[] = $object->print_action_button('add', array('class'=>'blue'));

        $html .= $object->_list(array(
            'info'=>true
        ));

    } elseif ($libhtml->tab=="facility") {

        $object = new Facility;
        $libhtml->title .= " - ".$libhtml->local_text['Facility'];
        $libhtml->page_actions[] = $object->print_action_button('add',array('class'=>'blue'));

        $html .= $object->_list(array(
            'info'=>true
        ));

    } elseif ($libhtml->tab=="ward") {

        $object = new Ward;
        $libhtml->title .= " - ".$libhtml->local_text['Ward'];
        $libhtml->page_actions[] = $object->print_action_button('add',array('class'=>'blue'));

		$html .= $object->_list(array(
            'info'=>true
        ));

    } elseif ($libhtml->tab=="location") {

        $object = new Location;
        $libhtml->title .= " - ".$libhtml->local_text['Location'];
        $libhtml->page_actions[] = $object->print_action_button('add', array('class'=>'blue'));

        $html .= $object->_list(array(
            'info'=>true
        ));

    } 

    $libhtml->render($html);
