<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $a = get_clean_object(my_request("admission_id"), "Admission");

    $libhtml->title = "Edit Admission";

    if (!empty($a->hl7_id)) $libhtml->title.= ' <span class="hl7_id">'.$a->hl7_id.'</span>';

    $a->set_post(my_request('admission'));
    $html .= $a->print_edit_form();
    $libhtml->render_form($html);
