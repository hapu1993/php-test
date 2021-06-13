<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $user = new Application_User;
    $user->select(my_get('user_id'));

    $libhtml = new Libhtml(array(
            "subtab" => my_get ( "subtab", "summary" ),
            "additional_tabs" => array (
                    'summary' => "Summary",
                    'logins' => "Logins",
                    'all_logs' => "All User Logs",
            ),
            "add_to_url" => "user_id=$user->id",
            "title" => "User Details - ".$user->fullname,
            "show_back"=> true,
            'page_filter_search' => $user->full_list_search(),
    ));


    if ($libhtml->subtab == "summary") {

        $libhtml->page_actions= array(
                $user->print_action_button('edit',array('class'=>'blue')),
        );

        $html .= $user->print_details();

    } elseif ($libhtml->subtab == "logins") {

        $object = new Log;

        $where = $user->sql_where;
        $where[0].= " AND action = ?";
        $where[1][]='Login';
        $where[2][]='varchar';

        $html .= $object->_list(array(
                'where'=>$where,
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
                'view_reset'=>array(
                        'user'=>false,
                        'action'=>false,
                        'object'=>false,
                        'object_id'=>false
                )
        ));

    } elseif ($libhtml->subtab == "all_logs") {

        $object = new Log;
        $html .= $object->_list(array(
                'where'=>$user->sql_where,
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
                'view_reset'=>array('user'=>false)
        ));

    }

    $libhtml->render($html);
