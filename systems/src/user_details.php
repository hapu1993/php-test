<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $user = new User;
    $user->select(my_get('user_id'));

    $libhtml = new Libhtml(array(
        "subtab" => my_get ( "subtab", "summary" ),
        "additional_tabs" => array (
            'summary' => "Summary",
            'logins' => "Logins",
            'all_logs' => "All User Logs",
            "user_comments" => "User Comments",
            "permissions" => "Permissions"
        ),
        "add_to_url" => "user_id=$user->id",
        "title" => "User Details - ".$user->fullname,
        "show_back"=> true,
        'page_filter_search' => $user->full_list_search(),
    ));


    if ($libhtml->subtab == "summary") {

        $libhtml->page_actions= array(
            $user->print_action_button('edit',array('class'=>'blue')),
        );

        $html .= $user->print_details();

    } elseif ($libhtml->subtab == "logins") {

        $object = new Log;

        $where = $user->sql_where;
        $where[0].= " AND action = ?";
        $where[1][]='Login';
        $where[2][]='varchar';

        $html .= $object->_list(array(
            'where'=>$where,
            'width'=>"100%",
            'edit'=>false,
            'delete'=>false,
            'view_reset'=>array(
                'user'=>false,
                'action'=>false,
                'object'=>false,
                'object_id'=>false
            )
        ));

    } elseif ($libhtml->subtab == "all_logs") {

        $object = new Log;
        $html .= $object->_list(array(
            'where'=>$user->sql_where,
            'width'=>"100%",
            'edit'=>false,
            'delete'=>false,
            'view_reset'=>array('user'=>false)
        ));

    } elseif ($libhtml->subtab == "user_comments") {

        $object = new User_Comment;

        $html .= $object->_list(array(
            'where'=>$user->sql_where,
            'width'=>"100%",
            'edit'=>false,
            'delete'=>false,
            'view_reset'=>array('user_name'=>false)
        ));

    } elseif ($libhtml->subtab == "permissions") {

        //Active apps
        $apps = $db->select("*", "system_apps", array("WHERE active=1",array(),array()));

        $pages = $db->select('*','system_pages',array());
        $user_permissions = $db->select(
            "p.page",
            "system_pages p LEFT JOIN system_user_group_permissions per ON p.id=per.page_id",
            array(
                'WHERE per.group_id IN (SELECT group_id FROM system_user_group_links WHERE user_id=?)',
                array($user->id),
                array('integer')
            )
        );

        foreach($pages as $row){
            //construct $permissions array - nicer format for later manipulation
            $user_page_permissions[$row->page] = array(
                'name'=>ucwords(str_replace(array("_",".php")," ",basename($row->page))),
                'comment'=>$row->comment,
                'id'=>$row->id,
                'core'=>$row->core,
                'access'=>0,
            );
        }

        foreach($user_permissions as $page) $user_page_permissions[$page->page]['access']=1;

        //MAIN LOOP
        //Loop through all apps
        foreach($apps as $app) {

            if (isset($links)) unset($links);
            $path = $cfg['source_root'] . $app->path;

            //Page types
            $types = array(
                1=>array('title'=>$app->name.' - Main Menu Pages','show'=>0),
                2=>array('title'=>$app->name.' - Other Pages','show'=>0),
                3=>array('title'=>$app->name.' - Actions/Popups','show'=>0),
            );

            $table = '';

            if (
                $dir = opendir($path)
                && is_file($path . "index.php")
                && is_file($path . "includes/admin_menu.php")
            ) {

                //include app admin_menu.php to get top-level pages in $links array
                include $path . "includes/admin_menu.php";

                //Loop through the top-level app navigation and record top-level pages;
                $top_app_menu = array();
                foreach($links as $link) $top_app_menu[] = $link[0];

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

                        $page = $app->path . $file;

                        //Criteria for distinguishing types 1, 2 and 3
                        //Type 1 = index+top menu
                        //Type 2 = all others apart from popups
                        //Type 3 = all popups, selected by the presence of common form includes include file
                        if (preg_match('/index.php/', $file) || in_array($file,$top_app_menu)) {
                            $type = 1;
                        } elseif (strpos(file_get_contents($page),"common_"."form_includes")===false) {
                            $type = 2;
                        } else {
                            $type = 3;
                        }

                        $types[$type]['show']=true;
                        $app_page_permissions[$page] = $user_page_permissions[$page];
                        $app_page_permissions[$page]['type']=$type;
                        $app_page_permissions[$page]['mtime']=filemtime($path.$file);

                    }
                }

                //dump_var($app_page_permissions);die;

                //Special cmp sort function to account for sorting actions on objects
                if (!empty($app_page_permissions)) uksort($app_page_permissions, array('System_Page','sort_app_pages'));



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
                        </th>

                        <th style="width: 150px;vertical-align: bottom;">
                            <div class="inner">
                                <span class="only_t">Last Modified</span>
                            </div>
                        </th>

                    </tr>';

                        //dump_var($app_page_permissions);die;

                        foreach ($app_page_permissions as $key=>$page) {

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

                                $table .= '
                                    <td>'.tick_cross_image($page['access']).'</td>
                                    <td>'.date("d M Y, H:i",$page['mtime']).'</td>
                                </tr>';

                            } //End of if page type=loop type

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

                //Unset $app_page_permissions so we start the new app table building from scratch
                unset($app_page_permissions);

            }


        } //End of apps loop

        //dump_var($fields);die;

        $html .= jquery_tabs($fields, $titles, array("icons" => $icons));


    }

    $libhtml->render($html);
