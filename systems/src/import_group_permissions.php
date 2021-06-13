<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

	$libhtml->title = 'Import Group Permissions';

	$object = new Application_User;
	$object->set_post(my_post($object->object_name));
	$html .= $object->print_import_permissions_form();

    $libhtml->render_form($html);
