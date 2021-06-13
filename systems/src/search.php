<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "tab"=>"results",
        "title" => "Search Results",
    ));

    if ($libhtml->tab == "results") {

        $object = new Search();

        $html .= $libhtml->page_search_section($object->print_search_form());

        if (my_request("keyword")) {

            $html .= $object->post_search();

        } else if (!empty($my_get["search"])) {

            $html .= '<div class="error">Please type a keyword...</div>';

        }

    }

    $libhtml->render($html);
