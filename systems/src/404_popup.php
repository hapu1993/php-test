<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_form_includes.php";

    $cache = new Cache(array('user_id'=>$user1->id));
    $inner_html = $cache->retrieve_cache('sitemap_tree');

    $html .=
        '<div class="hint">
                <b>The page you are looking for is not available on this server.</b> You can access the following pages to which you have permissions:
        </div>
        <ul class="treeview" id="popup_sitemap">
            <li>' . $inner_html . '</li>
        </ul>
        <div class="actions" style="display:block;">
            <input data-cancel="true" type="button" value="Close" class="btn right" />
        </div>';

    $libhtml->render_form($html);
