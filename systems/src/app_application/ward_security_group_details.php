<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_includes.php";

$wardSecurityGroups = new Ward_Security_Group;
$wardSecurityGroups->select(my_get('ward_security_group_id'));
$locationLabel = $libhtml->local_text['Location'];
$wardLabel = str_plural($libhtml->local_text['Ward']);
$menuTitle = $libhtml->local_text['Ward']." Security Group Details - ";
$libhtml = new Libhtml(array(
    "subtab" => my_get ( "subtab", "wards"),
    "additional_tabs" => array (
        'wards' => $wardLabel,
        'locations' => $locationLabel,
        'users' => "Users",
    ),
    "add_to_url" => "ward_security_group_id=".my_get('ward_security_group_id').'&active_search=All',
    "title" => $menuTitle.$wardSecurityGroups->name,
    "show_back"=> true,
));
if ($libhtml->subtab == "wards") {
    $wardSecurityGroupsPermissions = new Ward_Security_Groups_Permissions;
    $data = $wardSecurityGroupsPermissions->print_select_ward_group_filter_form(true);
    $html .= $data['html'];
    $wardSecurityGroupsPermissions->params = array('group_id'=>$wardSecurityGroups->id);
    $html .= $wardSecurityGroupsPermissions->_list(array(   
        'params'=> array('group_id'=>$wardSecurityGroups->id),
        'where'=>$data['where'],
        'width'=>"100%",
        'edit'=>false,
        'delete'=>false,
        'pagination'=>false,
        'print'=>false,
    ));
}else if ($libhtml->subtab == "locations") {
    $locationSecurityGroupsPermissions = new Location_Security_Groups_Permissions;
    $data = $locationSecurityGroupsPermissions->print_select_location_group_filter_form(true);
    $html .= $data['html'];
    $locationSecurityGroupsPermissions->params = array('group_id'=>$wardSecurityGroups->id);
    $html .= $locationSecurityGroupsPermissions->_list(array(
        'params'=> array('group_id'=>$wardSecurityGroups->id),
        'where'=>$data['where'],
        'width'=>"100%",
        'edit'=>false,
        'delete'=>false,
        'pagination'=>false,
        'print'=>false,
    ));
}else if ($libhtml->subtab == "users") {
    
    $users = new User();
    $data = $users->print_select_location_group_filter_form(true);
    $html .= $data['html'];
    
     $users->left_join .= "left join user_ward_security_groups_links gl on gl.user_id = t.id
                          left join ward_security_groups g on gl.group_id = g.id";
    
    $users->other_selects .= ", gl.id as link_id, g.id as wgroup_id";
     
    $users->view_array = array(
        'fullname'=>array("name"=>"Full Name"),
        'username'=>array("name"=>"Username","column"=>"username"),
        'active'=>array("name"=>"Active"),
        'is_sa'=>array("name"=>"SA"),
        'auth_type'=>array("name"=>"Authentication"),
        'link_id'=>array('name'=>"", "width"=>"100px", "display" => true),
        'activation'=>array("name"=>"Activation status", "show_name"=>false,"width"=>"130px", "display" => false),
    );
    
    $where = array(
        "WHERE g.id = ?",
        array('id' => $wardSecurityGroups->id),
        array('integer')
    );
    
    $where[0]=(!empty($data['where'][0])) ? $where[0].' AND '.$data['where'][0]: $where[0];
    
    $html .= $users->_listWardGroupUsers(array(
        //'params'=> array('group_id'=>$wardSecurityGroups->id),
        'where'=>$where,
        'width'=>"100%",
        'edit'=>false,
        'delete'=>false,
        'pagination'=>false,
        'print'=>false,
    ));
}
$libhtml->render($html);


