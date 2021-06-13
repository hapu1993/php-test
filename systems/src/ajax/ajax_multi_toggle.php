<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $vars = unserialize($crypt->str_decrypt(my_post("data")));

    list(
            $object_name,
            $object_id,
            $target_field,
            $origin_table,
            $origin_value_field,
            $origin_name_field,
            ) = $vars;

    $obj = new $object_name;
    $obj->select($object_id);
    $value = $obj->$target_field;

    $selection = $db->select("$origin_value_field,$origin_name_field",$origin_table,array("WHERE id > ?", array('id' => 0), array('integer')));

    $i = 0;
    $n = count($selection);
    $new_name = $selection[0]->$origin_name_field;
    $new_value = $selection[0]->$origin_value_field;

    if ($selection[$n-1]->$origin_value_field!=$value){
        for($i=0;$i<$n-1;$i++){
            if ($selection[$i]->$origin_value_field==$value){
                $new_name = $selection[$i+1]->$origin_name_field;
                $new_value = $selection[$i+1]->$origin_value_field;
                break;
            }
        }
    }

    $obj->update(array($target_field=>$new_value));

    echo $new_name;

}

$db->close();
?>
