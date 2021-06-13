<?php

    require_once dirname(__FILE__) . '/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $selection = $db->select("*","projects_credentials",array());

    $old = new Crypt_old;
    $new = new Crypt;

    foreach($selection as $item){

        $url = $new->str_encrypt($old->str_decrypt($item->url));
        $credentials = $new->str_encrypt($old->str_decrypt($item->credentials));
        $db->update("projects_credentials",array('url'=>$url,'credentials'=>$credentials),array("WHERE id=?",array($item->id),array('integer')));

    }

//     $old = new Crypt('AES-128-CBC');
//     $new = new Crypt('AES-256-CBC');

//     foreach($selection as $item){

//         $url = $new->str_encrypt($old->str_decrypt($item->url));
//         $credentials = $new->str_encrypt($old->str_decrypt($item->credentials));
//         $db->update("projects_credentials",array('url'=>$url,'credentials'=>$credentials),array("WHERE id=?",array($item->id),array('integer')));

//     }

    $db->close();
