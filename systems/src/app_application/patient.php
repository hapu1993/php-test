<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $object = new Patient;

    $libhtml = new Libhtml(array(
        "tab" => my_get("tab","all"),
        "header_search"=>true,
        'header_search_label'=>'Find Patient/Encounter',
    ));

    if ($libhtml->tab=="all") {

        $libhtml->title = "Patients";

        if (isset($cfg['ENV']) && in_array(strtolower($cfg['ENV']), array('dev', 'local-dev'))) {

            $libhtml->page_actions[]=href_link(array(
                "permission"=>$user1->{$libhtml->path.'import_csv_data.php'},
                "url"=>$cfg["root"] . $libhtml->path.'import_csv_data.php',
                "text"=>"Import Review .csv File",
                "class"=>"blue",
                "clear"=>false,
            ));

            $libhtml->page_actions[]=href_link(array(
                "permission"=>$user1->{$libhtml->path.'delete_all_data.php'},
                "url"=>$cfg["root"] . $libhtml->path.'delete_all_data.php',
                "text"=>"Delete All Patient Data",
                "class"=>"blue",
                "clear"=>false,
            ));

        }

        $data = $object->print_search_form();

        $html .= $data['html'];

        $html .= $object->_list(array(
            'where'=>$data['where'],
            'width'=>'100%',
            'info'=>true,
            'edit'=>false,
        ));

    }

    $libhtml->render($html);
