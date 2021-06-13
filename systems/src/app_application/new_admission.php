<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";
    $a = new Admission;
    $a->patient_id = my_get('patient_id');
    
    if (!empty($a->patient_id)) {
        $libhtml->title = "New Admission";
        $a->set_post(my_post('admission'));
        $html .= $a->print_add_form();
        $libhtml->render_form($html);
    }

    