<?php

require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_form_includes.php";

$a = get_clean_object(my_request("id"), "Admission");

$libhtml->title =  'Discharge Patient';
$a->set_post(my_post('admission'));
$html .= $a->print_discharge_form();
$libhtml->render_form($html);





?>
