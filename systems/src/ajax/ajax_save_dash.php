<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in && my_post("layout")!='' && my_post('id')!='') {

    $where = array(
        'WHERE dashboard_id = ? AND user_id = ?',
        array('dashboard_id'=>my_post('id'), 'user_id'=>$user1->id),
        array('varchar', 'integer')
    );

    $id = $db->select_value('id','system_users_dashboards',$where);
    if (empty($id)){
        $db->insert(
            'system_users_dashboards',
            array(
                    "user_id"=>$user1->id,
                    "dashboard_id"=>my_post('id'),
                    "dashboard_layout"=>my_post('layout')
            )
        );
    } else {
        $db->update(
            'system_users_dashboards',
            array(
                "dashboard_layout"=>my_post('layout')
            ),
            $where
        );
    }

}
