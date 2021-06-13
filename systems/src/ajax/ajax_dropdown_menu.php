<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $result = array();

    // just in case this was deployed in systems/ - replace this string if found
    $string = str_replace("systems/","", my_post('href'));
    $output = explode("/", $string);
    $class = my_post("class");

    foreach($output as $item) if(!empty($item)) $out2[] = $item;
    $count = count($out2);
    $get = $crypt->str_decrypt($out2[$count-1]);
    unset($out2[$count-1]);
    $page = $out2[$count-2];
    unset($out2[$count-2]);

    //This works only with fancy URLs!
    $url_get = array();
    $class_id=false;
    $url_get = explode("&",$get);
    foreach($url_get as $item){
        $x = explode("=",$item);
        if (strtolower($class) . "_id"==$x[0]) $class_id=$x[1];
    }

    $a = new $class;
    $i=0;

    //Print Details
    if (method_exists($a,'print_details') && $class_id){
        $result[$i]['name'] = "Details";
        $result[$i]['url'] = encrypt_url($cfg['root'] . "ajax/ajax_class_details.php?class=" . $cfg['source_root'] . implode("/",$out2) . "/classes/" . $class . ".php&class_name=" . $class . "&id=" . $class_id);
        $result[$i]['class'] = " details";
        $i++;
    }

    foreach($a->details_tabs as $key=>$value) {
        $result[$i]['name'] = $value;
        $result[$i]['url'] = encrypt_url($cfg['root'] . implode("/",$out2) . "/" . $page . ".php?" . $get . "&tab=$key");
        $result[$i]['class']="";
        $i++;
    }

    echo json_encode($result);

}

$db->close();
?>
