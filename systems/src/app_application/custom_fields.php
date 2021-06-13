<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "tab" => my_get("tab","patient"),
        "title" => "Custom Fields",
    ));

    if ($libhtml->tab=='patient') {

        $libhtml->title .= " - Patient";
        $questionnaire = get_clean_object(1, 'Questionnaire');

    } elseif ($libhtml->tab == 'system_user') {

        $libhtml->title .= " - System User";
        $questionnaire = get_clean_object(7, 'Questionnaire');

    }

    $libhtml->page_actions=array(
        href_link(array(
            "permission"=>$user1->{$libhtml->path . "add_questionnaire_question.php"},
            "url"=>$cfg["root"] . $libhtml->path . "add_questionnaire_question.php?questionnaire_id=$questionnaire->id",
            "text"=>"Add Question",
            "clear"=>false,
            'class'=>'blue',
        )),
    );

    $libhtml->page_actions[] = href_link(array(
        "permission"=>$user1->{$libhtml->path . "test_questionnaire.php"},
        "url"=>$cfg["root"] . $libhtml->path . "test_questionnaire.php?questionnaire_id=$questionnaire->id",
        "text"=>"Test Fields",
        "clear"=>false,
    ));

    $object = new Questionnaire_Question;

	$html .= '<h3>('.href_link(array(
		"permission"=>true,
		"url"=>get_enc_page(array('show_all_questions'=>!my_request('show_all_questions'))),
		'encrypt'=>false,
		"text"=>(my_request('show_all_questions') ? 'Hide ' : 'Show ').'inactive questions',
		'clear'=>false,
		'popup'=>false,
		'button'=>false,
		'float'=>'none',
	)).')</h3>';

	if(my_request('show_all_questions')){
		$where = array('WHERE t.questionnaire_id=?',array($questionnaire->id),array('integer'));
	} else {
		$where = array('WHERE t.questionnaire_id=? AND t.active=1',array($questionnaire->id),array('integer'));
	}

    $html .= $object->_list(array(
        'where'=>$where,
        'ajax_sort'=>true,
        'hide_delete_when'=>array('answers_exist'=>1)
    ));

    $libhtml->render($html);
