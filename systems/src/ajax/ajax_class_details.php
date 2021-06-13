<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $href = my_post('href');
    $get = explode("/",$href);

    $get = $crypt->str_decrypt($get[count($get)-1]);
    $get = explode("&",$get);
    $args = array();
    foreach($get as $item) {
        $x = explode("=",$item);
        if (count($x)==2 && !empty($x[0]) && !empty($x[1])){
            $args[$x[0]]=$x[1];
        }
    }

    if (!empty($args['class']) && !empty($args['class_name']) && !empty($args['id'])) {

        $libhtml = new Libhtml;
        $a = new $args['class_name'];
        $a->select($args['id']);
        echo $a->print_details();

    }

}

$db->close();
?>
