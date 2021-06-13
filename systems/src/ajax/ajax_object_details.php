<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in) {

        $result = '';
        // just in case this was deployed in systems/ - replace this string if found
        $href = str_replace("systems/","",my_post('href'));

        if ($cfg['nice_URLs']) {

            $crypted_get = explode('/',$href);

        } else {

            $parsed_href = parse_url($href);
            $crypted_get = explode('x=',$parsed_href['query']);

        }

        $class = my_post("class");
        $method = my_post("method","ajax_details");

        // Include the class
        $a = new $class;

        $class_id=false;
        parse_str($crypt->str_decrypt(end($crypted_get)),$get);

        foreach($get as $var => $value){
            if ((strtolower($class) . "_id"==$var) || ($a->object_pk==$var)) {
                $class_id=$value;
                break;
            }
        }

        // Print Details ( try ajax details table first )
        if (!empty($class_id)) {
            if (method_exists($a,$method)){

                $a->select($class_id);
                $result=$a->$method(false);

            } elseif (method_exists($a,'print_details')){

                $a->select($class_id);
                $result=$a->print_details(false);

            }
        }

        //error_log(print_r($result, true));

        echo $result;

    }

    $db->close();
