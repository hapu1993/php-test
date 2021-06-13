<?php
class Application_User extends User {

    public $human_name = 'System User';
    public $object_name = 'user';

    function __construct(){
        parent::__construct();
        //$this->questionnaire = get_clean_object(7, 'Questionnaire');
    }

    function select($id="", $feedback=true) {
        global $db;

        parent::select($id, $feedback);
        //$this->questionnaire = get_clean_object(7, 'Questionnaire');

        if (!empty($this->questionnaire)){
            $this->questionnaire->object_id = $this->id;
            $this->questionnaire->get_answers();
            $this->questionnaire->set_post(my_post('questionnaire'));
        }

    }

    function update(array $additional = array()) {
        parent::update($additional);
        //$q = get_clean_object(7, 'Questionnaire');
        //$q->set_post(my_post('questionnaire'));
        $q->object_id = $this->id;
        $q->answer();
        return true;
    }

    public $view_array = array(
        'fullname'=>array("name"=>"Full Name","column"=>"fullname"),
        'username'=>array("name"=>"Username","column"=>"username"),
        'user_groups'=>array("name"=>"User Groups"),
        'last_login'=>array("name"=>"Last Login","column"=>"last_login"),
        'failed_login'=>array("name"=>"Failed"),
        'email'=>array("name"=>"Email", "show_name"=>false, "export"=>true),
        'active'=>array("name"=>"Active"),
        'auth_type'=>array("name"=>"Authentication"),
        'activation'=>array("name"=>"Activation status", "show_name"=>false,"width"=>"130px", "display" => false),
    );

    function print_form() {
        global $cfg, $db, $libhtml,$user1;
        $html = $libhtml->form_start();

        $is_edit = false;
        if ($libhtml->basename == 'edit_user.php') {
            $is_edit = true;
            $html .= '<div class="hint">To modify the active status use the Activate/Deactivate button on the list.</div>';
        }

        $html .= open_table("","User Details","action_form");
        $html .= $libhtml->render_form_table_row($this->object_name."[fullname]", $this->fullname, "Full Name", "fullname",array('required'=>true,'tooltip'=>'Name that will appear on top of the page when logged in'));
        $html .= $libhtml->render_form_table_row($this->object_name."[email]", $this->email, "Email", "email", array("unique"=>true,'tooltip'=>'Email must be valid and unique'));
        $html .= $libhtml->render_form_table_row($this->object_name."[username]", $this->username, "Username", "username",array('required'=>true, "unique"=>true,'tooltip'=>'Username must be unique and no more than 30 characters long; it must not contain any characters other than alphanumerics, @ and . (dot)'));

        if ($is_edit === true) {
            $html .= $libhtml->render_table_row("Active", tick_cross_image($this->active));
        } else {
            if (is_null($this->active)) {
                $this->active=1;
            }
            $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[active]", $this->active, "Active", "active");
        }

        //User groups
        $html .= table_separator("","User Groups");

        $selection = $this->get_permissible_groups();

        if (!isset($this->user_group_ids)) {
            if (isset($this->user_groups)) {
                foreach ($this->user_groups as $group) $tmp['group'][$group] = 1;
            } else {
                foreach ($selection['groups'] as $group) $tmp['group'][$group->id] = 0;
            }
        } else {
            foreach ($this->user_group_ids as $key => $value) $tmp['group'][$key] = $value;
        }

        foreach ($selection['groups'] as $group) {

            if (!isset($tmp['group'][$group->id])) $tmp['group'][$group->id] = 0;

            $html .= '
                <tr>
                     <th style="width: 200px; min-width: 200px;">
                          <label for="'.$group->id.'">'.$group->name.'</label>
                     </th>
                     <td>'.$libhtml->render_form_table_row_checkbox($this->object_name."[user_group_ids][$group->id]", $tmp['group'][$group->id], $group->name, $group->id,array('td_class'=>'column_multiselect','minimal'=>true)).'</td>
                     <td>'.$group->comment.'</td>
                </tr>';

        }

        $html .= close_table();

        
		//Ward Security
		$wardGroups = new Ward_Security_Group;
		$selection = $wardGroups->_get(array(
			'limit'=>array('offset' => 0, 'num_on_page' => 1e9)
		));

		if(!empty($selection)){

			if(!empty($this->user_ward_groups)){
				foreach($selection as $item){
					if(in_array($item->id,$this->user_ward_groups)){
						$wardGroups->user['ward_group_ids'][$item->id]=1;
					}
				}
			}

			$html .= '<p>'.$libhtml->local_text['Ward'].' Security Groups</p>';
			$wardGroups->view_array = array(
				'name'=>array("name"=>"Group"),
				'wards'=>array("name"=>str_plural($libhtml->local_text['Ward']),),
				'locations'=>array("name"=>str_plural($libhtml->local_text['Location']),),
				'pick'=>array("name"=>"Link","display" => true,"width"=>"60px")
			);

			$html .= $wardGroups->_list(array(
				'selection'=>$selection,
				'width'=>"100%",
				'table_wrapper'=>false,
				'edit'=>false,
				'delete'=>false,
				'pagination'=>false,
			));

		}

        return $html;
    }

    function print_select_user_group_form($full=true){
        global $db, $user1, $cfg, $libhtml;

        $html = $libhtml->form_start();

        $html .= '<table style="width:100% !important;"><tr><td style="width:50%; padding-right: 5px;vertical-align:top;">';

        $html .= open_table("100%");

        $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

        $selection = $this->get_permissible_groups();

        if (count($selection['groups'])>6){
            $html .= $libhtml->render_form_table_row_selection("user_group_id", my_request('user_group_id'), "User Group", "user_group_id", $selection['groups'], "id","name", array('class'=>"self_submit"));
        } else {
            $html .= $libhtml->render_form_table_radio_selection("user_group_id", my_request('user_group_id'), "User Group", "user_group_id", $selection['groups'], "id","name", array('class'=>"self_submit",'radio_break'=>2));
        }

        $html .= $libhtml->render_form_table_radio_selection("active_search", my_request('active_search'), "Active", "active_search", array('Active','Inactive','All'),'','',array('class'=>"self_submit"));

        $html .= close_table();

        $html .= '</td><td style="width:50%; padding-right: 5px;vertical-align:top;">';

        $custom_fields = null;

        if (!empty($custom_fields)) {

            $html .= open_table("100%");

            foreach($custom_fields as $field){

                $name = 'search_'.$field->id;

                if ($field->type=='Checkbox'){
                    $html .= $libhtml->render_form_table_row_checkbox($name, my_request($name), $field->question, $name, array('class'=>"self_submit"));
                } elseif ($field->type=='Radio buttons'){
                    $html .= $libhtml->render_form_table_radio_selection($name, my_request($name), $field->question, $name, array_map('trim', explode(',', $field->list_options)), '', '', array('class'=>"self_submit"));
                } elseif ($field->type=='Dropdown list'){
                    $html .= $libhtml->render_form_table_row_selection($name, my_request($name), $field->question, $name, array_map('trim', explode(',', $field->list_options)), '', '', array('class'=>"self_submit"));
                } elseif ($field->type=='Date'){
                    $html .= $libhtml->render_form_table_row_date_from_to($name.'_from', $name.'_to', my_request($name.'_from'),my_request($name.'_to'), $field->question, $name, array('class'=>"self_submit"));
                }
            }

            $html .= close_table();

        }

        $html .= '</td></tr></table>';

        $html .= $libhtml->form_end();

        $where = array(
            array('t.id IN (SELECT user_id FROM system_user_group_links WHERE group_id IN ('.$selection['ids'].'))'),
            array(),
            array()
        );

        if (my_request("user_group_id")!=''){
            $where[0][]= "t.id IN (SELECT user_id FROM system_user_group_links WHERE group_id=?)";
            $where[1][] = my_request("user_group_id");
            $where[2][] = 'integer';
        }

        if (my_request("active_search")=='Active'){
            $where[0][]= "t.active=1";
        } elseif (my_request("active_search")=='Inactive'){
            $where[0][]= "(t.active=0 OR t.active IS NULL)";
        }

        //Custom fields
        if (!empty($custom_fields)) {

            foreach($custom_fields as $field){

                $name = 'search_'.$field->id;

                if ($field->type=='Checkbox'){

                    if (my_request($name)==1) {
                        $where[0][]= "id IN (SELECT object_id FROM questionnaire_answers WHERE questionnaire_id=7 AND question_id=? AND answer=1)";
                        $where[1][] = $field->id;
                        $where[2][] = 'integer';
                    }


                } elseif ($field->type=='Radio buttons'){

                    if (my_request($name)!=''){
                        $where[0][]= "id IN (SELECT object_id FROM questionnaire_answers WHERE questionnaire_id=7 AND question_id=? AND answer=?)";
                        $where[1][] = $field->id;
                        $where[1][] = my_request($name);
                        $where[2][] = 'integer';
                        $where[2][] = 'varchar';
                    }

                } elseif ($field->type=='Dropdown list'){

                    if (my_request($name)!=''){
                        $where[0][]= "id IN (SELECT object_id FROM questionnaire_answers WHERE questionnaire_id=7 AND question_id=? AND answer=?)";
                        $where[1][] = $field->id;
                        $where[1][] = my_request($name);
                        $where[2][] = 'integer';
                        $where[2][] = 'varchar';
                    }

                } elseif ($field->type=='Date'){

                    if (my_request($name.'_from')!=''){
                        $where[0][]= "id IN (SELECT object_id FROM questionnaire_answers WHERE questionnaire_id=7 AND question_id=? AND answer>=?)";
                        $where[1][] = $field->id;
                        $where[1][] = my_request($name.'_from');
                        $where[2][] = 'integer';
                        $where[2][] = 'date';
                    }

                    if (my_request($name.'_to')!=''){
                        $where[0][]= "id IN (SELECT object_id FROM questionnaire_answers WHERE questionnaire_id=7 AND question_id=? AND answer<=?)";
                        $where[1][] = $field->id;
                        $where[1][] = my_request($name.'_to');
                        $where[2][] = 'integer';
                        $where[2][] = 'date';
                    }

                }
            }
        }

        $where[0]=(!empty($where[0])) ? "WHERE ".implode(" AND ",$where[0]): '';

        return array(
            'html'=>$html,
            'where'=>$where,
        );
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $last_login_html = '';
        if (empty($item->last_login)) $last_login_html .= '<span class="txtwrap txtwrap_red">Never</span>';
        else if (!empty($user_online) && rel_time(strtotime($item->last_login), time()) == "now") $last_login_html .= ' <span class="txtwrap txtwrap_green">'.rel_time(strtotime($item->last_login), time()).'</span>';
        else if (!empty($user_online)) $last_login_html .= ' <span class="txtwrap txtwrap_green">'.rel_time(strtotime($item->last_login), time()).' ago</span>';
        else $last_login_html .= ' <span class="txtwrap">'.rel_time(strtotime($item->last_login), time()).' ago</span>';
        $last_login_html .= zero_date($item->last_login, $user1->preferences->dateformat . " H:i");
        $item->last_login = $last_login_html;

        if ($item->allowed_ip) $item->allowed_ip = $item->allowed_ip.' ('.gethostbyaddr($item->allowed_ip).')';

        $item->fullname = href_link(array(
            "permission"=>($user1->{$libhtml->path."user_details.php"} && ($item->id!=0)),
            "url"=>$cfg['root'] . $libhtml->path."user_details.php?user_id=$item->id",
            "text"=>$item->fullname,
            "title"=>$item->fullname,
            "popup"=>false,
            "button"=>false,
            "clear"=>false,
            "tooltip"=>'User details',
            "expand_details"=>"User",
        ));

        $item->active_status = $item->active;
        $item->active = $this->getItemActive($item);
        $item->activation = $this->getItemActivation($item);

        $item->is_sa = tick_cross_image($item->is_sa);

        if ($item->email) $item->email = "<a class=\"tooltip\" title=\"$item->email\" href=\"mailto:$item->email\"><span class=\"ico_email\"><i class=\"fa fa-envelope-o\"></i></span></a>";

        if (!empty($item->group_ids)){

            $item->user_groups = implode(", ",explode(",",$item->group_names));

        } else {

            $item->user_groups = '';
        }

        if (!empty($item->wgroup_id)){
            $link = href_link(array(
                "permission"=>$user1->{$libhtml->path."edit_ward_security_group.php"},
                "url"=>$cfg["root"] . "includes/edit_ward_security_group_details.php?subtab=users&user_id=".$item->id."&group_id=".$item->wgroup_id.'&link_id='.$item->link_id.'&active_search='.my_request("active_search").'&path='.$libhtml->path,
                "text"=>"Remove",//tick_cross_image(!empty($item->link_id))
                "tooltip"=>'Unlink from '.$libhtml->local_text['Ward'].' Security Group',
                "popup"=>false,
                "button"=>true,
                "float" => "",
                "clear"=>false));

            $item->link_id = $link;
        }

        return;
    }

    function print_details() {
        global $db, $user1, $libhtml;

        $html = open_table();
        $html .= $libhtml->render_table_row("User Full Name",$this->fullname);
        $html .= $libhtml->render_table_row("Email",(IsEmail($this->email)) ? "<a href=\"mailto:$this->email\">$this->email</a>" : '');
        $html .= $libhtml->render_table_row("Username",$this->username);
        $html .= $libhtml->render_table_row("User Groups",$this->user_groups_string);
		$html .= $libhtml->render_table_row("User ".$libhtml->local_text['Ward']." Security Groups",(!empty($this->user_ward_groups_string) ? $this->user_ward_groups_string : $user1->system_settings['Default ward security']));
		if(!empty($this->user_ward_groups_string)) {
			if(!empty($this->user_access_wards_text)) $html .= $libhtml->render_table_row("User Accessible ".str_plural($libhtml->local_text['Ward']),$this->user_access_wards_text);
	        if(!empty($this->user_access_locations_text)) $html .= $libhtml->render_table_row("User Accessible ".$libhtml->local_text['Location'],$this->user_access_locations_text);
		}

        $html .= $libhtml->render_table_row("Last Login",zero_date($this->last_login,$user1->preferences->dateformat . " H:i"));

        if (!empty($this->access_time)) $html .= $libhtml->render_table_row("Last Page Access",date($user1->preferences->dateformat . " H:i",$this->access_time));

        $html .= $libhtml->render_table_row("Failed Logins",$this->failed_login);
        $html .= $libhtml->render_table_row("Active", tick_cross_image($this->active,false));

        if (isset($this->unique_login)) $html .= $libhtml->render_table_row("Unique Login", tick_cross_image($this->unique_login,true));
        $html .= $libhtml->render_table_row("Authentication Type", $this->auth_type);

        $html .= close_table();

        if (!empty($this->questionnaire)){

            $html .= $this->questionnaire->print_object_details(array(
                'time_stamp'=>false,
                'width'=>'600px',
                'th_width'=>'200px',
                'show_no_answers_message'=>false,
            ));

        }

        return $html;
    }

    function full_list_search(){
        global $cfg, $db, $crypt, $libhtml;

        if (my_post('filter_search')!='') {

            header("Location: " . encrypt_url($cfg["root"] . $libhtml->path."user_details.php?user_id=" . my_post('filter_search')));
            exit;

        } else {

            $html = $libhtml->form_start();
            $html .= open_table("", "", "full_list_search action_form");

            $selection = $this->get_permissible_groups();

            $html .= $libhtml->render_form_table_row_autocomplete("filter_search", "", "Find a user", "filter_search", $this->table ,"CONCAT(fullname,' / ',username,' / ',email)","id",array(
                'where'=>"WHERE CONCAT(fullname,' / ',username,' / ',email) LIKE ? AND id IN (SELECT user_id FROM system_user_group_links WHERE group_id IN (".$selection['ids']."))",
                "auto_select"=>true,
                'th_width'=>'auto',
            ));
            $html .= close_table();

            $html .= $libhtml->form_end();
        }

        return $html;
    }

    function get_permissible_groups(){
        global $user1, $db;

        $groups = $db->select_distinct(
            "g.id, g.name,g.comment",
            "system_user_groups g",
            array(
                'WHERE g.id!=0 AND (
                    g.id NOT IN
                        (SELECT DISTINCT group_id FROM system_user_group_permissions per LEFT JOIN system_pages p ON p.id=per.page_id WHERE p.page LIKE ? AND page_id NOT IN
                            (SELECT p.id FROM system_pages p LEFT JOIN system_user_group_permissions per ON per.page_id=p.id LEFT JOIN system_user_group_links l ON l.group_id=per.group_id WHERE l.user_id=? AND p.page LIKE ?)
                        )
                    OR
                        (g.id IN
                            (SELECT per.group_id FROM system_user_group_permissions per LEFT JOIN system_pages p ON p.id=per.page_id WHERE p.front_end_app=1 AND p.page=?)
                        AND g.id NOT IN
                            (SELECT per.group_id FROM system_user_group_permissions per LEFT JOIN system_pages p ON p.id=per.page_id WHERE p.page LIKE ?)
                        )
                )',
                array('%app_%',$user1->id,'%app_%','index.php','%app_%'),
                array('varchar','integer','varchar','varchar','varchar')
            ),
            array('order_by'=>"ORDER BY name ASC")
        );

        $ids = array();
        foreach($groups as $group) $ids[] =$group->id;

        return array(
            'groups'=>$groups,
            'ids'=>implode(',',$ids),
        );
    }


    function print_import_form() {
        global $cfg, $db, $libhtml, $my_post, $my_get, $user1;

        $html = $libhtml->form_start();

        $html .= open_table();

        $html .= $libhtml->render_form_table_row_file($this->object_name."[import]", "Upload the csv file", $this, "user_imports/", array("accepted_ft"=>"csv", "required"=>true));
        $html .= close_table();

        $html .= $libhtml->render_submit_button("import", "Import");
        $html .= $libhtml->form_end();

        return $html;
    }

    function import() {
        global $cfg, $db, $libhtml, $my_post, $my_get, $user1, $crypt;

        if (!empty($this->import)){

            $max_ram = ini_get('memory_limit');
            $max_execution_time = ini_get('max_execution_time');

            ini_set('memory_limit','256M');
            ini_set('max_execution_time', 1200);

            //Read the file
            $csv = array_map('str_getcsv', file($cfg['secure_dir'].$this->import));

            //Remove header row
            array_shift($csv);

            //dump_var($csv);die;

            // create a reporting array
            $this->import_report = array("success"=>array(), "already_exist"=>array(), "failed"=>array());

            // Loop through row by row
            foreach($csv as $row) {

                $first_name = $row[0];
                $last_name = $row[1];
                $email = $row[2];
                $user_group = $row[3];

                $group_id = $db->select_value('id','system_user_groups',array('WHERE name=?',array($user_group),array('integer')));

                // if the email is not empty, check if the user exists
                if (empty($first_name) || empty($last_name) || empty($email) || empty($user_group)) {
                    $this->import_report["failed"][] = array("row"=>$row, "reason"=>"Some of the required information (first name, last name, email or user group) are missing.");

                    error_log("[$db->database] Failed bulk upload entry: Some of the required information (first name, last name, email or user group) are missing.");
                    error_log(print_r($row,true));

                } else if (!IsEmail($email)){
                    $this->import_report["failed"][] = array("row"=>$row, "reason"=>"User could not be created, email field input is in invalid format.");

                    error_log("[$db->database] Failed bulk upload entry: User could not be created, email field input is in invalid format.");
                    error_log(print_r($row,true));

                } else if (empty($group_id)){

                    $this->import_report["failed"][] = array("row"=>$row, "reason"=>"User could not be created, User Group does not exist in the system.");

                    error_log("[$db->database] Failed bulk upload entry: User could not be created, User Group does not exist in the system.");
                    error_log(print_r($row,true));

                } else if (!empty($email) && IsEmail($email)){

                    $user_exists = $db->select("id", $this->table, array("WHERE email = ? OR username = ?", array($email, $email), array("varchar", "varchar", "varchar")));

                    if (!empty($user_exists)) {
                        $this->import_report["already_exist"][] = array("row"=>$row, "email"=>$email);

                        error_log("[$db->database] Failed bulk upload entry: Duplicate user.");
                        error_log(print_r($row,true));

                    } else {

                        $password = generate_password(8,7);

                        // insert user
                        $new_user_id = $db->insert(
                            $this->table,
                            array(
                                "fullname"=>$first_name.' '.$last_name,
                                "username"=>$email,
                                "email"=>$email,
                                "password"=>$crypt->bcrypt($password),
                                'active'=>1,
                            )
                        );

                        if (!empty($new_user_id)) {

                            //Insert group link
                            $db->insert(
                                'system_user_group_links',
                                array(
                                    'user_id'=>$new_user_id,
                                    'group_id'=>$group_id
                                )
                            );

                            // send an email
                            general_email(array(
                                "template"=>"user_mail",
                                "subject"=>"System - New User Details",
                                "content"=>"Dear ".$first_name. " " . $last_name . ",<br/><br/>
                                    Your user account has been created:<br/>
                                    URL: ".$cfg['website']."<br/>
                                    Username: ".$email."<br/>
                                    Password: ".$password."<br/><br/>
                                    Kind Regards,<br/>
                                    Admin",
                                "recipients"=>array(array("email"=>$email, "fullname"=>$first_name . ' ' . $last_name)),
                            ));

                        }

                        // add to report
                        $this->import_report["success"][] = array("row"=>$row, "email"=>$email);

                    }

                // something went wrong
                } else {
                    $this->import_report["failed"][] = array("row"=>$row, "reason"=>"There was an error inserting user to the database.");

                }

            } // end of for loop

            $_SESSION["import_report"] = $this->import_report;

            ini_set('memory_limit',$max_ram);
            ini_set('max_execution_time', $max_execution_time);

        }

    }

	function export_group_permissions_to_csv(){
		global $db, $cfg, $user1, $libhtml, $my_get;

		$data = array(array('Page','Front End'));

		$selection = $db->select(
			"p.page,p.front_end_app",
			"system_user_group_permissions per LEFT JOIN system_pages p ON p.id=per.page_id",
			array('WHERE per.group_id=?',array(my_get('user_group_id')),array('integer')),
			array('order_by'=>'ORDER BY p.front_end_app ASC, p.page ASC')
		);

		foreach($selection as $item) $data[] = array($item->page,(int) $item->front_end_app);

		$group_name = $db->select_value('name','system_user_groups',array('WHERE id=?',array(my_get('user_group_id')),array('integer')));

		export_to_csv($data,$group_name.'-permissions');

	}

	function print_import_permissions_form(){
		global $cfg, $db, $libhtml, $my_post, $my_get, $user1;

		$html = $libhtml->form_start();

		$html .=
		'<div class="hint">
			Import csv file must have columns with headings Page and Front End; all other columns will be ignored.
			<br/>On upload, all permissions for the selected user group will be deleted/unset.
			<br/>If there is a page matching value in column Page and the Front End flag in the csv file, the user group will be granted permission.
			<br/>All other lines will be ignored.
		</div>';

		$html .= open_table();

		$html .= $libhtml->render_form_table_row_file($this->object_name."[import]", "Upload the csv file", $this, "user_permission_imports/", array("accepted_ft"=>"csv", "required"=>true));
		$html .= close_table();

		$html .= $libhtml->render_submit_button("import_group_permissions", "Import");
		$html .= $libhtml->form_end();

		return $html;
	}

	function import_group_permissions(){
		global $cfg, $db, $libhtml, $my_post, $my_get, $user1, $crypt;

		if (!empty($this->import)){

			$csv = array_map('str_getcsv', file($cfg['secure_dir'].$this->import));

			if(!empty($csv[0])){

				$page_key = array_search('Page', $csv[0]);
				$front_end_app_key = array_search('Front End', $csv[0]);

				unset($csv[0]);

				if($page_key!==false && $front_end_app_key!==false){

					$inserted_count = 0;
					$db->delete('system_user_group_permissions',array('WHERE group_id=?',array(my_get('user_group_id')),array('integer')));

					foreach($csv as $row){

						if(!empty($row[$page_key]) && isset($row[$front_end_app_key])){

							if(empty($row[$front_end_app_key])){
								$page_ids=$db->select(
									'id','system_pages',
									array(
										'WHERE page=? AND (front_end_app=0 OR front_end_app IS NULL)',
										array($row[$page_key]),
										array('varchar')
									)
								);
							} else {
								$page_ids=$db->select(
									'id','system_pages',
									array(
										'WHERE page=? AND front_end_app=1',
										array($row[$page_key]),
										array('varchar')
									)
								);
							}

							if(!empty($page_ids)) {
								foreach($page_ids as $item){
									$db->insert(
										'system_user_group_permissions',
										array(
											'page_id'=>$item->id,
											'group_id'=>my_get('user_group_id')
										)
									);
								}
							}

						}

					}

				} else {

					$_SESSION['feedback'] .= g_feedback("error", "Page or Front End columns missing");

				}

			} else {

				$_SESSION['feedback'] .= g_feedback("error", "Empty csv file");

			}

		}

	}


}
