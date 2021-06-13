<?php
	require_once dirname(__FILE__).'/../config/global.php';
	require_once $cfg['source_root'] . "includes/common_form_includes.php";

	$libhtml->title = "Multiedit Users ".$libhtml->local_text['Ward']." Security Groups";

	$a = new Application_User;

	$get = Request::purify_array($_GET, "GET");

	$a->ids = array();

	if (!empty($get['x'])){

		$pieces = explode('=',$get['x']);

		if (!empty($pieces[1])) {
			$a->ids = explode(',',$pieces[1]);
		}

	}

	$a->set_post(my_post('user'));
	$html .= $a->print_multiedit_form();

	$libhtml->render_form($html);
