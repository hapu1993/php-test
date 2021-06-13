<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $object = new Log;

    $libhtml->tab = my_get("tab","all");
    $libhtml->title = "System Logs - " . ucwords(str_replace("_"," ",$libhtml->tab));
    $libhtml->js = "";

    if ($libhtml->tab=="all") {

        $libhtml->page_actions= array(
                href_link(array(
                    "permission"=>$user1->{"delete_logs.php"},
                    "url"=>$cfg["root"] . "delete_logs.php",
                    "text"=>"Delete System Logs",
                )),
        );

           $html .= $object->_list(array(
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="logins") {

        $html .= $object->_list(array(
                'where'=>array("WHERE action LIKE ?", array('action' => "Login"), array('varchar')),
                'width'=>"100%",
                'view_reset'=>array('action'=>false,'object'=>false),
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="failed_logins") {

        $html .= $object->_list(array(
                'where'=>array("WHERE action LIKE ?", array('action' => "Failed Login"), array('varchar')),
                'width'=>"100%",
                'view_reset'=>array('action'=>false,'object'=>false),
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="updates") {

        $html .= $object->_list(array(
                'where'=>array("WHERE action LIKE ?", array('action' => "Update"), array('varchar')),
                'width'=>"100%",
                'view_reset'=>array('action'=>false),
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="deletes") {

        $html .= $object->_list(array(
                'where'=>array("WHERE action LIKE ?", array('action' => "Delete"), array('varchar')),
                'width'=>"100%",
                'view_reset'=>array('action'=>false),
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="inserts") {

        $html .= $object->_list(array(
                'where'=>array("WHERE action LIKE ?", array('action' => "Insert"), array('varchar')),
                'width'=>"100%",
                'view_reset'=>array('action'=>false),
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="exports") {

        $html .= $object->_list(array(
                'where'=>array("WHERE action LIKE ?", array('action' => "%export%"), array('varchar')),
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="search") {

        $data = $object->print_search_form();

        $html .= $data['html'];

        $html .= $object->_list(array(
                'where'=>$data['where'],
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
        ));

    } elseif ($libhtml->tab=="intrusions") {

        $libhtml->page_actions= array(
                href_link(array(
                        "permission"=>$user1->{"delete_intrusion_logs.php"},
                        "url"=>$cfg["root"] . "delete_intrusion_logs.php",
                        "text"=>"Delete Intrusion Logs",
                )),
        );

        $object = new Intrusion;

        $html .= $object->_list(array(
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
        ));

    }

    $libhtml->render($html);
