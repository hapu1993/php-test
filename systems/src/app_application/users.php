<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $object = new Application_User;

    $libhtml->tab = my_get("tab","users");

    if ($libhtml->tab=="users") {

        $libhtml->title = "Application Users";

        $ldap_settings = new \Riskpoint\Auth\LDAP\Setting();
        if (!$ldap_settings->isEnabled()) {
            $libhtml->page_actions= array(
                $object->print_action_button('add', array('class'=>'blue','clear'=>false)),
            );
            unset($object->view_array['auth_type']);
        }
        
        $libhtml->page_actions[] = href_link(array(
            "permission"=>($user1->{$libhtml->path . "multiedit_user.php"} && $user1->id != 0),
            "url"=>$cfg["root"] . $libhtml->path . "multiedit_user.php",
            "text"=>"Edit All Selected",
            "clear"=>false,
            'class'=>'blue edit_selected',
            'extra'=>' data-uri="'.$cfg["root"] . $libhtml->path.'multiedit_user/" ',
        ));

        $data = $object->print_select_user_group_form(true);

        $html .= $data['html'];

        $html .= $object->_list(array(
            'where'=>$data['where'],
            
            'multiselect' => ($user1->{$libhtml->path . "multiedit_user.php"} && $user1->id != 0),
            'multicopy' => false,
            'multidelete' => false,
            'multiedit' => ($user1->{$libhtml->path . "multiedit_user.php"} && $user1->id != 0),
            
            'width'=>"100%",
            'hide_edit_when'=>array('id'=>0, 'auth_type' => 'AD/LDAP'),
            'hide_delete_when'=>array('id'=>0, 'auth_type' => 'AD/LDAP'),
        ));

    }elseif ($libhtml->tab == "ward_security_groups") {
        
        $object = new Ward_Security_Group;
        $libhtml->title = $libhtml->local_text['Ward']." Security Groups";
        
        $libhtml->page_actions= array(
            $object->print_action_button('add',array('class'=>'blue','clear'=>false)),
        );
        
        $object = new Ward_Security_Group;
        $html .= $object->_list(array(
            'width'=>"100%",
            'table_wrapper'=>false,            
        ));
        
    }elseif ($libhtml->tab=="system_log") {

        $libhtml->title = 'System Log';

        $object = new Log;

        $data = $object->print_search_form();

        $html .= $data['html'];

        $html .= $object->_list(array(
                'where'=>$data['where'],
                'width'=>"100%",
                'edit'=>false,
                'delete'=>false,
        ));

    }elseif ($libhtml->tab=="view_audit_log") {
        
        $libhtml->title = 'View Audit Log';
        
        $object = new ViewLog;
        
        $data = $object->print_search_form();
        
        $html .= $data['html'];
        
        $html .= $object->_list(array(
            'where'=>$data['where'],
            'width'=>"100%",
            'edit'=>false,
            'delete'=>false,
        ));
        
    } elseif ($libhtml->tab=="bulk_import") {

        $libhtml->title = 'Import Users';

        $libhtml->page_actions = array(
            href_link(array(
                "permission"=>$user1->{$libhtml->path."import_users.php"},
                "url"=>$cfg["root"] . $libhtml->path."import_users.php",
                "text"=>"Import users",
                "class"=>"blue",
                "clear"=>false,
            ))
        );

        if (empty($_SESSION['import_report'])){

            $html .='
                <div class="hint">
                    <p>Set up new users by importing csv file.</p>
                    <p>Template file available <a href="'.$cfg["root"].$libhtml->path.'includes/sample_import_template.csv" target="_blank">here</a> - please keep header row in the data file.</p>
                    <p>Successful new entries will be notified of their credentials by an automated email.</p>
                </div>';

        } else {

            $html .= section(array("title"=>"Successfully imported users (" .count($_SESSION["import_report"]["success"]) . ")", "collapsible"=>true, "state"=>"collapsed"));

            if (!empty($_SESSION["import_report"]["success"])) $html .= print_import_results($_SESSION["import_report"]["success"]);

            $html .= section(array("title"=>"Already existing users/emails (" .count($_SESSION["import_report"]["already_exist"]) . ")", "collapsible"=>true, "state"=>"collapsed"));

            if (!empty($_SESSION["import_report"]["already_exist"])) $html .= print_import_results($_SESSION["import_report"]["already_exist"]);

            $html .= section(array("title"=>"Failed entries (" .count($_SESSION["import_report"]["failed"]) . ")", "collapsible"=>true, "state"=>"collapsed"));

            if (!empty($_SESSION["import_report"]["failed"])) $html .= print_import_results($_SESSION["import_report"]["failed"]);

            $_SESSION['import_report'] = null;

        }

    }

    $libhtml->render($html);

    function print_import_results($data){

        $html = '
        <div class="table_wrap clearfix">
            <div class="table_parent">
                <table class="list_table summary">
                    <thead>
                        <tr class="header">
                            <th class="no_sort"><div class="inner"><span class="only_t">Name</span></div></th>
                            <th class="no_sort"><div class="inner"><span class="only_t">Surname</span></div></th>
                            <th class="no_sort"><div class="inner"><span class="only_t">Email</span></div></th>
                            <th class="no_sort"><div class="inner"><span class="only_t">User Group</span></div></th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach($data as $row){
            $html .= '
                    <tr>
                        <td>' . $row['row'][0] . '</td>
                        <td>' . $row['row'][1] . '</td>
                        <td>' . $row['row'][2] . '</td>
                        <td>' . $row['row'][3] . '</td>
                    </tr>';
        }

        $html .= '</tbody>
                </table>
            </div>
        </div>';

        return $html;

    }
