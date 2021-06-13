<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml->tab = my_get("tab","all");

    $object = new User;

    if ($libhtml->tab=="all") {

        $libhtml->title = "System Users";
        $libhtml->page_filter_search = $object->full_list_search();

        $add_user_button = null;
        $ldap_settings = new \Riskpoint\Auth\LDAP\Setting();
        if (!$ldap_settings->isEnabled()) {
            $add_user_button = $object->print_action_button('add', array('class'=>'blue','clear'=>false));
        }
        $libhtml->page_actions= array(
            $add_user_button,
            href_link(array(
                "permission"=>$user1->{"send_user_email.php"},
                "url"=>$cfg["root"] . "send_user_email",
                "text"=>"Email System Users",
            )),
        );

        $libhtml->page_actions[] = href_link(array(
            "permission"=>($user1->{"multiedit_user.php"} && $user1->id != 0),
            "url"=>$cfg["root"] .  "multiedit_user.php",
            "text"=>"Edit All Selected",
            "clear"=>false,
            'easy_cancel'=>true,
            'class'=>'blue edit_selected',
            'extra'=>' data-uri="'.$cfg["root"] . 'app_application/multiedit_user/" ',
        ));

        $data = $object->print_select_user_group_form(true);

        $html .= $data['html'];
        if ($ldap_settings->isEnabled()) {
            $html .= '<div class="hint">Active Directory / LDAP users are managed via their respective service, and are only reflected here when the user attempts to login.</div>';
        } else {
            unset($object->view_array['auth_type']);
        }

        if ($object->count_SAs() == 0) {
            $html.= "<div class='error'>You have no active SysAdmins defined.  Login as MasterAdmin to set some otherwise noone will be notifed of password reset requests.</div>";
        }

        $html .= $object->_list(array(
            'where'=>$data['where'],
            'width'=>"100%",
            'multiselect' => ($user1->{"multiedit_user.php"} && $user1->id != 0),
            'multicopy' => false,
            'multidelete' => false,
            'multiedit' => ($user1->{"multiedit_user.php"} && $user1->id != 0),
            'hide_edit_when'=>array('id'=>0, 'auth_type' => 'AD/LDAP'),
            'hide_delete_when'=>array('id'=>0, 'auth_type' => 'AD/LDAP'),

        ));

        // Check logged in users
        $libhtml->js .= '
        <script type="text/javascript">
            function checkStatus(){
                var i = 0;
                $.getJSON(SYSTEM_ROOT+"ajax/ajax_check_status.php",
                    function(data) {
                        $("td.user_reset").html("<span class=\'ico_grey_circle tooltip\' title=\'User Offline\'><i class=\"fa fa-circle-o\"></i></span>");
                        while (data[i]) {
                            $("td.user"+data[i]).html("<span class=\'ico_green_circle tooltip\' title=\'User Online\'><i class=\"fa fa-circle\"></i></span>");
                            i++;
                        }
                    });
            }
            setInterval (checkStatus, 15000);
        </script>';

    } elseif ($libhtml->tab == "user_groups") {

        $object = new User_Group;
        $libhtml->title = "User Groups";

        $libhtml->page_actions= array(
            $object->print_action_button('add',array('class'=>'blue','clear'=>false)),
        );

        $object = new User_Group;
        $html .= $object->_list(array(
            'width'=>"100%",
            'table_wrapper'=>false,
            'hide_edit_when'=>array('id'=>0),
            'hide_delete_when'=>array('id'=>0),
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

    }elseif ($libhtml->tab == "sessions") {

        $object = new User_Session;
        $libhtml->title = "Live User Sessions";

        $html .= $object->_list(array(
            'where' => array ("WHERE user_id IS NOT NULL",array (),array ()),
            'width' => "100%",
            'table_wrapper' => false,
            'edit' => false,
            'delete' => true,
        ));

        $libhtml->js .= '
        <script type="text/javascript">
            function refreshSessionsPage(){
                $("div.table_parent").animate({"opacity": "0.5"}, 100);
                $("div.table_parent").load(window.location.href + " .table_parent", function(data){
                    if (typeof refreshJS == "function") refreshJS();
                    $(this).animate({"opacity": "1"});
                });
                var now = new Date();
                var h=now.getHours();
                var m=now.getMinutes();
                var s=now.getSeconds();
                if (m<10) m="0"+m;
                if (s<10) s="0"+s;
                $("h1").html("Live User Sessions - "+$.datepicker.formatDate("d M y", now)+" "+h+":"+m+":"+s);
            }
            setInterval (refreshSessionsPage, 15000);
        </script>';

    } elseif ($libhtml->tab=="apps") {

        $object = new System_App;
        $libhtml->title = "System Apps";

        $libhtml->page_actions= array(
            href_link(array(
                "permission" => $user1->master_admin,
                "url" => $cfg ["root"] . "add_system_app.php",
                "text" => "Add System App",
                'class'=>"blue",
            )),
        );

        $html .= $object->_list(array(
            'width'=>"100%",
            'table_wrapper'=>false,
            'pagination'=>false,
            'edit'=>($user1->master_admin),
            'delete'=>($user1->master_admin),
            'hide_edit_when'=>array('name'=>'Admin Panel'),
            'hide_delete_when'=>array('name'=>'Admin Panel'),
        ));

    } elseif ($libhtml->tab=="pages") {

        $can_any_group_edit_users = 0;

        $libhtml->title="Groups and Apps Permissions";

        $libhtml->additional_tabs = array(
            'groups'=>'Groups - Apps Matrix',
            'page_permissions'=>'Page Permissions'
        );

        $libhtml->subtab = my_get("subtab","page_permissions");

        //Active apps
        $apps = $db->select("*", "system_apps", array("WHERE active=1",array(),array()));

        //User Groups
        $groups = $db->select("*", "system_user_groups", array('WHERE id>0',array(),array()),array('order_by'=>"ORDER BY name ASC"));

        if ($libhtml->subtab=="groups") {

            $html.= open_table('800px','User Group access to Apps. Only Master Admin level user can change permission levels.');
            $html.= "<tr><th>Apps / User Groups</th>";

            $selection = $db->select("g.id,p.page","system_user_groups g LEFT JOIN system_user_group_permissions l ON l.group_id=g.id LEFT JOIN system_pages p ON p.id=l.page_id",array('WHERE p.page LIKE ?',array('%index.php'),array('varchar')));
            foreach($selection as $item) $permissions[$item->id][$item->page]=1;

            foreach($apps as $app) {
                $html .= '<td style="width: 32px;">';

                    if (!empty($app->image) && preg_match('/png|jpg|jpeg|gif/', extension($app->image))) $html .= '<img class="tooltip" style="width:32px;" alt="'.$app->name.'" title="'.$app->name.'" src="'.$cfg['root'] . 'config/' . $app->image.'" />';
                    else $html .= '<span class="ico_app"><i class="fa fa-'.$app->image.'"></i></span>';

                $html .= '</td>';
            }

            foreach($groups as $group){

                $row_html = array();

                foreach($apps as $app) {

                    //Only allow change of page permissions to Master Admin
                    if ($user1->master_admin) {
                        if (!empty($permissions[$group->id][$app->path.'index.php'])) {
                            $row_html[] = '
                            <a href="' . encrypt_url($cfg['root'] . 'includes/set_app_permissions.php?app='.$app->name.'&level='.$group->id.'&p=disable') . '" title="Close App to User Group - Disable all pages">
                                <span class="ico_circle_toggle_on">
                                    <i class="fa fa-times-circle"></i>
                                    <i class="fa fa-check-circle"></i>
                                </span>
                            </a>';
                        } else {
                            $row_html[] = '
                            <a href="' . encrypt_url($cfg['root'] . 'includes/set_app_permissions.php?app='.$app->name.'&level='.$group->id.'&p=enable') . '" title="Open App to User Group - Enable all pages">
                                <span class="ico_circle_toggle_off">
                                    <i class="fa fa-times-circle"></i>
                                    <i class="fa fa-check-circle"></i>
                                </span>
                            </a>';
                        }
                    } else {
                        $row_html[] = tick_cross_image((!empty($permissions[$group->id][$app->path.'index.php'])));
                    }

                }

                $html.= $libhtml->render_table_row(
					href_link(array(
                        "permission"=>true,
                        "url"=>$cfg['root'] . "users.php?tab=pages&subtab=page_permissions&user_group_id=".$group->id,
                        "text"=>$group->name,
                        "button"=>false,
                        "clear"=>false,
                        "popup"=>false,
                        "float"=>"",
                	)),implode('</td><td>',$row_html
				),array('tooltip'=>$group->comment));

            }

            $html.= "</tr>";
            $html.= close_table();

        } elseif ($libhtml->subtab=="page_permissions") {

            if ($object->can_update_user() == 0) {
                $html .= "<div class='error'>You have no user groups who can edit users.  No SysAdmins can be set and noone will receive password reset requests.</div>";
            }

            $data = $object->print_select_user_group_form(false);

            $html .= $data['html'];

                //user group priority is POST (GET is set after app permissions are set, on a redirect page)
                $selected_ug = '';
                if (!empty($my_get["user_group_id"])) $selected_ug = $my_get["user_group_id"];
                if (!empty($my_post["user_group_id"])) $selected_ug = $my_post["user_group_id"];

                if (!empty($selected_ug)) $user_group_name = $db->select_value("name","system_user_groups",array("WHERE id = ?", array('id' =>$selected_ug), array('integer')));
                $all_pages = $db->select("id, page, comment, core, front_end_app","system_pages", array());

                // Delete all non-existent pages from the table and their associated permissions
                foreach($all_pages as $key=>$row) {

                    //Does the page exist as a file?
                    $path = (empty($row->front_end_app)) ? $cfg['source_root'] . $row->page : $cfg['website_source'] . $row->page;

                    if(!file_exists($path)) {

                        //Delete from user group permissions links
                        $db->delete("system_user_group_permissions",array("WHERE page_id=(SELECT p.id FROM system_pages p WHERE p.id=?)", array('id' => $row->id), array('integer')));

                        //Delete from system pages
                        $db->delete("system_pages",array("WHERE id=?", array('id' => $row->id), array('integer')));

                        unset($all_pages[$key]);

                    } else {

                        //construct $permissions array - nicer format for later manipulation
                        if ($row->front_end_app) {

                            //collect all pages from the db for later discovery of any new pages
                            $all_fe_pages_register[$row->page] = $row->id;

                            $fe_permissions[$row->page] = array(
                                'name'=>ucwords(str_replace(array("_",".php")," ", basename($row->page))),
                                'comment'=>$row->comment,
                                'id'=>$row->id,
                                'core'=>$row->core,
                                'front_end_app'=>$row->front_end_app,
                                'access'=>0,
                            );

                        } else {

                            //collect all pages from the db for later discovery of any new pages
                            $all_pages_register[$row->page] = $row->id;

                            $permissions[$row->page] = array(
                                'name'=>ucwords(str_replace(array("_",".php")," ", basename($row->page))),
                                'comment'=>$row->comment,
                                'id'=>$row->id,
                                'core'=>$row->core,
                                'front_end_app'=>$row->front_end_app,
                                'access'=>0,
                            );
                        }
                    }
                }

                //Check for any pages-group links where the page does not exist any more and delete them
                $db->delete("system_user_group_permissions",array("WHERE page_id NOT IN (SELECT id FROM system_pages)", array(), array()));

                if (my_request("user_group_id")!=''){

					//Export
					if($user1->id==0 && my_get('export_group_permissions_to_csv')!=''){

						$object = new Application_User;
						$object->export_group_permissions_to_csv();
						exit;

					}

                    //Get permissions for selected user level and update $permissions array
                    $user_permissions = $db->select(
                        "p.page as page_name, p.front_end_app",
                        "system_user_group_permissions per LEFT JOIN system_pages p ON p.id=per.page_id",
                        array(
                            "WHERE per.id>0 AND per.group_id=?",
                            array(my_request("user_group_id")),
                            array('integer')
                        )
                    );

                    foreach($user_permissions as $row){
                        if ($row->front_end_app) $fe_permissions[$row->page_name]['access'] = 1;
                        else $permissions[$row->page_name]['access'] = 1;
                    }

                    $html .= "<br/><h2>Applications &amp; Pages Permissions".(!empty($user_group_name) ? " For User Group: <span style=\"color:#e60028;\">$user_group_name</span>" : '')."</h2> ";

					$original_links = $links;

                    // MAIN LOOP
                    // Loop through all apps
                       foreach($apps as $app) {

                           //if (isset($links)) unset($links);

						// different FE and BE apps
						if (empty($app->front_end_app)) {
							$path = $cfg['source_root'] . $app->path;
						} else {
							$path = $cfg['website_source'];
						}

                           // Page types
                           $types = array(
                            1=>array('title'=>$app->name.' - Main Menu Pages','show'=>0),
                            2=>array('title'=>$app->name.' - Other Pages','show'=>0),
                            3=>array('title'=>$app->name.' - Actions/Popups','show'=>0),
                           );

                           $table = '';

                           //Check that we have a valid path - must have index, includes & admin_menu.php
                           if (is_file($path . "index.php")) {

                               // include app admin_menu.php to get top-level pages in $links array
                            if (empty($app->front_end_app)) {
                                include $path . "includes/admin_menu.php";

                                //Loop through the top-level app navigation and record top-level pages;
                                $top_app_menu = array();
                                foreach($links as $link) $top_app_menu[] = $link[0];
                            }

                            //Get all files from the app
                            $directory_files = simple_get_files($path);

                            //Sort them
                            usort($directory_files, array('System_Page','sort_app_pages'));

                            //Loop through all app files
                            foreach($directory_files as $file) {

                                //If the file is not a directory file, index.php or type 1
                                if(
                                    file_exists($path.$file)
                                    && !is_dir($path.$file)
                                    && preg_match('/php/', extension($file))
                                ) {

                                    // changed for the BE and FE app permissions
                                    $page_path = $path . $file;
                                    $page_name = ($app->front_end_app) ? $file : $app->path . $file;

                                    //Criteria for distinguishing types 1, 2 and 3
                                    //Type 1 = index+top menu
                                    //Type 2 = all others apart from popups
                                    //Type 3 = all popups, selected by the presence of common_form_includes include file
                                    if (preg_match('/index.php/', $file) || in_array($file, $top_app_menu)) {
                                        $type = 1;
                                    } elseif (strpos(file_get_contents($page_path), "common_form_includes")===false) {
                                        $type = 2;
                                    } else {
                                        $type = 3;
                                    }

                                    $types[$type]['show'] = true;

                                    // If file is not in the permissions table, insert into $permissions array
                                    // Front End app
                                    if (!empty($app->front_end_app) && !isset($fe_permissions[$page_name])){

                                        // If file does not exist insert new table row
                                        if (!isset($all_fe_pages_register[$page_name])){
                                            $system_page = new System_Page();
                                            $page_id = $system_page->insert(array('page' => $page_name, 'front_end_app'=>"1"));
                                        } else {
                                            $page_id = $all_fe_pages_register[$page_name];
                                        }

                                        $tmp_fe_permissions[$page_name] = array(
                                            'type' => $type,
                                            'name' => ucwords(str_replace(array("_",".php")," ",$file)),
                                            'comment' => '',
                                            'access' => 0,
                                            'id' => $page_id,
                                            'core' => 0,
                                            'front_end_app' => 1,
                                            'mtime'=>filemtime($page_path),
                                        );

                                    // Back End app
                                    } else if (empty($app->front_end_app) && !isset($permissions[$page_name])){

                                        // If file does not exist insert new table row
                                        if (!isset($all_pages_register[$page_name])){
                                            $system_page = new System_Page();
                                            $page_id = $system_page->insert(array('page' => $page_name, 'front_end_app'=>"0"));
                                        } else {
                                            $page_id = $all_pages_register[$page_name];
                                        }

                                        $tmp_permissions[$page_name] = array(
                                            'type' => $type,
                                            'name' => ucwords(str_replace(array("_",".php")," ",$file)),
                                            'comment' => '',
                                            'access' => 0,
                                            'id' => $page_id,
                                            'core' => 0,
                                            'front_end_app' => 0,
                                            'mtime'=>filemtime($page_path),
                                        );

                                    // FE - page exists
                                    } else if (!empty($app->front_end_app)) {
                                        $tmp_fe_permissions[$page_name] = $fe_permissions[$page_name];
                                        $tmp_fe_permissions[$page_name]['front_end_app'] = 1;
                                        $tmp_fe_permissions[$page_name]['type'] = $type;
                                        $tmp_fe_permissions[$page_name]['mtime'] = filemtime($page_path);

                                    // BE - page exists
                                    } else {
                                        $tmp_permissions[$page_name] = $permissions[$page_name];
                                        $tmp_permissions[$page_name]['front_end_app'] = 0;
                                        $tmp_permissions[$page_name]['type'] = $type;
                                        $tmp_permissions[$page_name]['mtime'] = filemtime($page_path);

                                    }

                                }
                            }

                            //Special cmp sort function to account for sorting actions on objects
                            if (!empty($tmp_fe_permissions)) uksort($tmp_fe_permissions, array('System_Page', 'sort_app_pages'));
                            if (!empty($tmp_permissions)) uksort($tmp_permissions, array('System_Page','sort_app_pages'));

                            //Only allow change of page permissions to Master Admin
                            if ($user1->master_admin) {

                                // fe / be switch
                                if (!empty($app->front_end_app)) {

                                    if (!empty($fe_permissions["index.php"]['access'])){

                                        $table .= href_link(array(
                                            "permission"=>true,
                                            "url"=>$cfg["root"] . "includes/set_app_permissions.php?app=$app->name&level=".$selected_ug."&p=disable",
                                            "text"=>"Close Application to User Group",
                                            'clear'=>true,
                                            'popup'=>false,
                                            'class'=>"red_btn",
                                        ));

                                    } else {

                                        $table .= href_link(array(
                                            "permission"=>true,
                                            "url"=>$cfg["root"] . "includes/set_app_permissions.php?app=$app->name&level=".$selected_ug."&p=enable",
                                            "text"=>"Open Application to User Group",
                                            'clear'=>true,
                                            'popup'=>false,
                                            'class'=>"green_btn",
                                        ));

                                    }

                                // BE switch
                                } else {

                                    if (!empty($permissions[$app->path . "index.php"]['access'])){

                                        $table .= href_link(array(
                                            "permission"=>true,
                                            "url"=>$cfg["root"] . "includes/set_app_permissions.php?app=$app->name&level=".$selected_ug."&p=disable",
                                            "text"=>"Close Application to User Group",
                                            'clear'=>true,
                                            'popup'=>false,
                                            'class'=>"red_btn",
                                        ));

                                    } else {

                                        $table .= href_link(array(
                                            "permission"=>true,
                                            "url"=>$cfg["root"] . "includes/set_app_permissions.php?app=$app->name&level=".$selected_ug."&p=enable",
                                            "text"=>"Open Application to User Group",
                                            'clear'=>true,
                                            'popup'=>false,
                                            'class'=>"green_btn",
                                        ));

                                    }

                                }

                            }

                            $class = ($user1->master_admin) ? 'tick' : '';

                            // allow permissions toggling only for Master Admin user
                            $permissionsclass = ($user1->master_admin) ? "permissions" : "";

                            // For tracking object name so we don't repeat it in the table - type 3 only
                            $old_action_object = "";

                            $table .= '
                                <div class="table_wrap" style="margin:10px 0 0 0;">';

                            //Loop through page types 1,2,3
                            foreach(array(1,2,3) as $type) {

                                if(!empty($types[$type]['show'])) {

                                    $table .= '
                                    <h3 style="padding:10px 0px 10px 10px !important;">'.$types[$type]['title'].'</h3>
                                    <table class="summary '.$permissionsclass.'" style="width: 800px; margin-left:10px; margin-right:10px; margin-bottom:10px;">
                                        <tr>
                                            <th style="vertical-align:bottom;">
                                                <div class="inner">
                                                    <span class="only_t">Page</span>
                                                </div>
                                            </th>';

                                    //Function
                                    if ($type==3) $table .= '
                                            <th style="vertical-align:bottom;">
                                                <div class="inner">
                                                    <span class="only_t">Function</span>
                                                </div>
                                            </th>';

                                    //Access toggler
                                    $table .= '
                                            <th style="width: 50px;vertical-align: bottom;">
                                                <div class="inner">
                                                    <span class="only_t">Access</span>
                                                </div>
                                            </th>';

                                    //Core page
                                    if (($user1->master_admin)) $table .= '
                                            <th style="width: 125px;vertical-align: bottom;">
                                                <div class="inner">
                                                    <span class="only_t">System Core Page</span>
                                                </div>
                                            </th>';

                                    //Add-edit comment
                                    $table .= '
                                        </tr>';

                                    // FE
                                    if (!empty($app->front_end_app)) {
                                        foreach ($tmp_fe_permissions as $key=>$page) {

                                            //Check for type
                                            if ($page['type']==$type) {

                                                $file = basename($key);
                                                $table .= "<tr>";

                                                //Display human name - type 3 has function
                                                if ($type==3) {
                                                    //Find out action ("Add","Edit",...etc) and action object
                                                    $item_array = explode(" ",$page['name']);
                                                    if (count($item_array)>1){
                                                        $action = $item_array[0];
                                                        $action_object = str_replace($action,"",$page['name']);
                                                    } else {
                                                        $action = "";
                                                        $action_object = $item;
                                                    }

                                                    if ($old_action_object!=$action_object) {
                                                        $action_object_display = $action_object;
                                                        $old_action_object = $action_object;
                                                    } else {
                                                        $action_object_display="";
                                                    }

                                                    $table .= "<td>$action_object_display</td><td>$action</td>";
                                                } else {
                                                    $table .= "<td>" . $page['name'] . "</td>";
                                                }

                                                //If it is a core file everyone has access; otherwise allow permissioning switch
                                                if (!$page['core']) {
                                                    if ($page['access']) {
                                                        $table .= "<td><span id=\"" . $crypt->str_encrypt(serialize(array($page['id'],$selected_ug,$user1->id,1))) . "\" class=\"".$class." ico_circle_toggle_on\">
                                                            <i class='fa fa-check-circle'></i>
                                                            <i class='fa fa-times-circle'></i>
                                                        </span></td>";
                                                    } else {
                                                        $table .= "<td><span id=\"" . $crypt->str_encrypt(serialize(array($page['id'],$selected_ug,$user1->id,0))) . "\" class=\"".$class." ico_circle_toggle_off\">
                                                            <i class='fa fa-check-circle'></i>
                                                            <i class='fa fa-times-circle'></i>
                                                        </span></td>";
                                                    }
                                                } else {
                                                    $table .= "<td><span class=\"tooltip ico_circle_toggle_on\" title=\"System file accessible to all users\">
                                                        <i class='fa fa-check-circle'></i>
                                                        <i class='fa fa-times-circle'></i>
                                                    </span></td>";
                                                }

                                                if (($user1->master_admin)) $table .= "<td>" . ajax_toggle(
                                                        $page['id'],
                                                        "system_pages",
                                                        "core",
                                                        true,
                                                        $page['core']
                                                ) . "</td>";

                                                $table .= "</tr>\n";

                                            } //End of if page type=loop type

                                        } //End of tmp_permissions loop

                                    // BE
                                    } else {

                                        foreach ($tmp_permissions as $key=>$page) {

                                        //Check for type
                                        if ($page['type']==$type) {

                                            $file = basename($key);
                                            $table .= '
                                        <tr>';

                                            //Display human name - type 3 has function
                                            if ($type==3) {
                                                //Find out action ("Add","Edit",...etc) and action object
                                                $item_array = explode(" ",$page['name']);
                                                if (count($item_array)>1){
                                                    $action = $item_array[0];
                                                    $action_object = str_replace($action,"",$page['name']);
                                                } else {
                                                    $action = "";
                                                    $action_object = '';
                                                }

                                                if ($old_action_object!=$action_object) {
                                                    $action_object_display = $action_object;
                                                    $old_action_object = $action_object;
                                                } else {
                                                    $action_object_display="";
                                                }

                                                $table .= '
                                            <td>'.$action_object_display.'</td>
                                            <td>'.$action.'</td>';

                                            } else {

                                                $table .= '
                                            <td>'.$page['name'].'</td>';

                                            }

                                            //If it is a core file everyone has access; otherwise allow permissioning switch
                                            if (!$page['core']) {
                                                if ($page['access']) {
                                                    $table .= '
                                            <td>
                                                <span id="'.$crypt->str_encrypt(serialize(array($page['id'],my_request("user_group_id"),$user1->id,1))).'" class="'.$class.' ico_circle_toggle_on">
                                                    <i class="fa fa-times-circle"></i>
                                                    <i class="fa fa-check-circle"></i>
                                                </span>
                                            </td>';
                                                } else {
                                                    $table .= '
                                            <td>
                                                <span id="'.$crypt->str_encrypt(serialize(array($page['id'],my_request("user_group_id"),$user1->id,0))).'" class="'.$class.' ico_circle_toggle_off">
                                                    <i class="fa fa-check-circle"></i>
                                                    <i class="fa fa-times-circle"></i>
                                                </span>
                                            </td>';

                                                }
                                            } else {

                                                $table .= '
                                            <td>
                                                <span class="tooltip ico_circle_toggle_on" title="System file accessible to all users"><i class="fa fa-check-circle"></i></span>
                                            </td>';

                                            }

                                            if (($user1->master_admin)) $table .= "<td>" . ajax_toggle(
                                                    $page['id'],
                                                    "system_pages",
                                                    "core",
                                                    true,
                                                    $page['core']
                                            ) . "</td>
											</tr>";

                                        } //End of if page type=loop type

                                    } // End of be check
                                 } //End of tmp_permissions loop

                                    $table .= '
                                    </table>';

                                } //End of if show types

                            } //End of types loop

                            $table .= '
                                </div>';

                            //jquery_tabs inputs
                            $titles[] = $app->name;

                            if (!empty($app->image) && preg_match('/png|jpg|jpeg|gif/', extension($app->image))) $icons[] = $cfg['root'] . "config/" . $app->image;
                            else $icons[] = $app->image;

                            $fields[] = $table;

                            //Unset $tmp_permissions so we start the new app table building from scratch
                               unset($tmp_permissions);
                        }

                       } //End of apps loop

                       //dump_var($fields);die;

                       $html .= jquery_tabs($fields, $titles, array("icons" => $icons));

					   //Libhtml stuff goes at the end because its overwritten when we include admin_menus
					   $libhtml->title = 'Groups and Apps Permissions';

					   //Export to csv
					   $libhtml->more_actions[] = href_link(array(
						   "permission"=>($user1->id==0),
						   "url"=>$cfg["root"] . $libhtml->path . "users.php?export_group_permissions_to_csv=1&".http_build_query($my_request),
						   "text"=>"Export Group Permissions",
						   "clear"=>false,
						   "popup"=>false,
					   ));

					   //Import from csv
					   $libhtml->more_actions[] = href_link(array(
						   "permission"=>($user1->id==0),
						   "url"=>$cfg["root"] . $libhtml->path . "import_group_permissions.php",
						   "text"=>"Import Group Permissions",
						   "clear"=>false,
					   ));

                }

        } //End of subtab

		$libhtml->title = "User Group Permissions";
		$libhtml->links = $original_links;

    } //End of tab

    $libhtml->render($html);
