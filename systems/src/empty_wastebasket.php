<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $libhtml->title = "Empty Wastebasket";

    $html .= $libhtml->form_start();

    $html .= '<div class="error">Are you sure you want to empty the entire Wastebasket?</div>';

    $html .= $libhtml->render_submit_button("empty_wastebasket", "Yes",array(
            'post_functions'=>array(
                    'empty_wastebasket'=>array("wastebasket","_empty","Wastebasket")
            )
    ));

    $html .= $libhtml->form_end();

    $libhtml->render_form($html);
