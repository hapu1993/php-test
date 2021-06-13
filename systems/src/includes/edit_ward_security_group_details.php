<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_plain_includes.php";

if (!empty(my_get("ward_id")) && !empty(my_get("link_id"))){
    $wardGroupLink = new Ward_Security_Groups_Permissions_Link;
    $wardGroupLink->select(my_get("link_id"));
    $wardGroupLink->delete();    
}else if (!empty(my_get("location_id")) && !empty(my_get("link_id"))){
    $locationGroupPermission = new Location_Security_Groups_Permissions_Link;
    $locationGroupPermission->select(my_get("link_id"));
    $locationGroupPermission->delete();   
}else if (!empty(my_get("ward_id")) && !empty(my_get("group_id"))){
    $wardGroupLink = new Ward_Security_Groups_Permissions_Link;
    $wardGroupLink->set_post(array("ward_id"=>my_get("ward_id"),"group_id"=>my_get("group_id")));
    $wardGroupLink->insert();        
}else if (!empty(my_get("location_id")) && !empty(my_get("group_id"))){
    $locationGroupPermission = new Location_Security_Groups_Permissions_Link;
    $locationGroupPermission->set_post(array("location_id"=>my_get("location_id"),"group_id"=>my_get("group_id")));
    $locationGroupPermission->insert();    
}else if (!empty(my_get("user_id")) && !empty(my_get("link_id"))){
    $groupLink = new User_Ward_Security_Group_Link;
    $groupLink->select(my_get("link_id"));
    $groupLink->delete();       
}else if (!empty(my_get("user_id")) && !empty(my_get("group_id"))){
    
    $groupLink = new User_Ward_Security_Group_Link;
    $groupLink->set_post(array('user_id'=>my_get("user_id"), 'group_id'=>my_get("group_id")));
    $groupLink->insert();
}

$db->close();

header("Location: " . encrypt_url($cfg['root'] . my_get("path")."ward_security_group_details.php?ward_security_group_id=".my_get("group_id")."&subtab=".my_get("subtab").'&active_search='.my_request("active_search")));
exit;

