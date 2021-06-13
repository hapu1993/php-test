<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

	$libhtml->title = 'Import System Settings';

	$object = new Setting;
	$object->set_post(my_post($object->object_name));
	$html .= $object->print_import_form();

    $libhtml->render_form($html);
