<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Delete Intrusion Logs";

    $html .= $libhtml->form_start();

    $count = $db->tcount(
            "system_intrusions",
            array(
                    "WHERE created <= ?",
                    array('created' => date("Y-m-d H:i:s",strtotime("-6 months", time()))),
                    array('datetime')
            )
    );

    $html .= '<div class="error">Are you sure you want to delete all '.$count.' intrusion log(s) older than 6 months?</div>';
    $html .= $libhtml->render_submit_button("delete_old_intrusion_logs", "Yes",array(
        'post_functions'=>array(
            'delete_old_intrusion_logs' => array(
                    "delete_old_intrusion_logs",
                    "delete_old_intrusion_logs",
                    "Intrusion"
            ))
    ));

    $html .= $libhtml->form_end();
    $libhtml->render_form($html);
