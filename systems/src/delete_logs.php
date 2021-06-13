<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Delete System Logs";

    $html .= $libhtml->form_start();
    $count = $db->tcount(
            "system_log",
            array(
                    "WHERE time <= ?",
                    array('time' => date("Y-m-d H:i:s",strtotime("-6 months", time()))),
                    array('datetime')
            )
    );

    $html .= '<div class="error">Are you sure you want to delete all '.$count.' system log(s) older than 6 months?</div>';

    $html .= $libhtml->render_submit_button("delete_old_logs", "Yes",array(
        'post_functions'=>array(
            'delete_old_logs' => array("delete_old_logs","delete_old_logs","Log")
        )
    ));

    $html .= $libhtml->form_end();
    $libhtml->render_form($html);
