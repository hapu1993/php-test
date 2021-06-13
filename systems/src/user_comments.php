<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml->tab=my_get("tab","all");
    $libhtml->title = "User Comments";

    $object = new User_Comment;

    if ($libhtml->tab=="all") {

        $libhtml->page_actions= array(
                $object->print_action_button('export',array('class'=>'blue')),
                href_link(array(
                    "permission" => $user1->{"export_user_comments.php"},
                    "url" => $cfg ["root"] . "export_user_comments.php",
                    "text" => "Export user comments",
                    "popup"=>false,
                )),
        );

        $html .= $object->_list(array(
                'width'=>"100%",
                'quick_search'=>true,
                'view'=>true,
                'pagination'=>true,
                'edit'=>true,
                'delete'=>true,
                'csv_export'=>true,
        ));

    }

    $libhtml->render($html);
