<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $review = get_clean_object(my_get('admission_id'), 'Admission');
    $libhtml->title = 'Are you sure you woud like to delete this admission?';
    $html .= $review->print_delete_details();

    $libhtml->render_form($html);
