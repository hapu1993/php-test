<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in && my_post('id')!='') {

    $db->delete(
        'system_users_dashboards',
        array(
            'WHERE dashboard_id = ? AND user_id = ?',
            array(my_post('id'), $user1->id),
            array('varchar', 'integer')
        )
    );

}
