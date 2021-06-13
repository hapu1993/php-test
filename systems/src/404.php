<?php
    if ( !file_exists(dirname(__FILE__).'/config/global.php') ) {
        error_log("Please create a global.php file at location: " . dirname(__FILE__) . "/config/global.php");
        include_once(dirname(__FILE__) . "/missing_config_file.html");
        die;
    } else {
        require_once dirname(__FILE__).'/config/global.php';
    }

    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "tab"=>"404",
        "title" => "Page not found",
        'show_side_panel'=>false,
        'show_apps'=>false,
        'show_back'=>true,
        'show_menu'=>false,
        'show_bar_icons'=>false
    ));

    $cache = new Cache(array('user_id'=>$user1->id));

    $html .= '
            <div class="hint">
                <b>The page you are looking for is not available on this server.</b> You can access the following pages to which you have permissions:
            </div>
            <div class="clearfix rte">
            <div class="side_panel page404" id="side_panel">
                <div class="side_options jquery_tabs ui-tabs ui-widget ui-widget-content ui-corner-all">
                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="tab_sitemap" aria-labelledby="ui-id-1" role="tabpanel" aria-expanded="true" aria-hidden="false" style="display: block;border:none;">
                        <ul class="treeview" id="sitemap">
                            <li>' . $cache->retrieve_cache('sitemap_tree') . '</li>
                        </ul>
                    </div>
                </div>
            </div>';

    $libhtml->render($html);
