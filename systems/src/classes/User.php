<?php

/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2017 Riskpoint London Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
class User extends Object {

    public $table = "system_users";
    public $left_join = "
        LEFT JOIN system_user_groups_view v ON v.user_id = t.id
    ";

    public $other_selects = "
        ,(SELECT MAX(access) FROM system_session s WHERE s.user_id=t.id) as access
        ,v.group_ids
        ,v.group_names
    ";
    public $orderby = "t.fullname";
    public $dir = "ASC";
    public $basic_selection = "t.id, t.fullname as name";

    public $view_array = array(
        'status'=>array("name"=>"User Online", "width"=>"14px", "show_name"=>false, "export"=>true),
        'fullname'=>array("name"=>"Full Name"),
        'username'=>array("name"=>"Username","column"=>"username"),
        'user_groups'=>array("name"=>"User Groups"),
        'allowed_ip'=>array("name"=>"Allowed IP"),
        'last_login'=>array("name"=>"Last Login","column"=>"last_login"),
        'failed_login'=>array("name"=>"Failed"),
        'email'=>array("name"=>"Email", "show_name"=>false, "export"=>true),
        'active'=>array("name"=>"Active"),
        'unique_login'=>array("name"=>"Unique"),
        'is_sa'=>array("name"=>"SA"),
        'auth_type'=>array("name"=>"Authentication"),
        'activation'=>array("name"=>"Activation status", "show_name"=>false,"width"=>"130px", "display" => false),
    );
    protected $default_password_link_expiry = "2 days";

    function __construct(){
        global $cfg;
        parent::__construct();

    }

    function select($id="",$feedback=true){
        parent::select($id);
        $this->get_user_groups();
        $this->get_user_ward_groups();
    }

    function insert() {
        global $cfg, $crypt;

        $success = false;

        $SAs = array();
        if (!empty($this->notify_admin)){
            $SAs = $this->get_SA_contact_details();
            if (empty($SAs)) throw new Exception('No SAs configured in DB for emailling.');
        }

        if (parent::insert()) {

            if ($this->id) {

                $this->set_user_groups();
                $this->get_user_groups();
                $this->get_user_ward_groups();

				$this->update_user_ward_groups();

                $success = true;

                if (!empty($this->notify_admin)){

                    $content = array(
                        'A new user has been set up:',
                        'Username: '.$this->username,
                        'Email: '.$this->email,
                        'User Groups: '.$this->user_groups_text,
                    );
                    $recipients = array();

                    $sa_index = 0;
                    foreach ($SAs as $sa) {
                        if ($sa_index == 0 ) {
                            $contact = $sa->email;
                        }
                        $temp_array = array();
                        $temp_array['email'] = $sa->email;
                        $temp_array['fullname'] = "System Administrator";
                        $recipients[] = $temp_array;
                        $sa_index++;
                    }

                    general_email(array(
                        "template"=>"user_mail",
                        "subject"=>$cfg['client'].' System: New User',
                        'content'=>'<p>'.implode('<br/>',$content),
                        'feedback'=>false,
                        'recipients'=>$recipients,
                    ));

                }

                $this->send_new_password_email();

            } else {
                $_SESSION['feedback'] .= g_feedback("error","Error: problem with user creation. Please use the bug-reporting system");
            }
        }


        return $success;
    }

    public function send_new_password_email()
    {
        global $cfg;
        try {
            $this->send_password_email("new_user", 'New user setup');
        } catch (Exception $e) {
            $_SESSION['feedback'] .= g_feedback("error", "Error: problem with user creation. Please use the bug-reporting system");
            $message = $e->getMessage();
            error_log("Caught exception: in " . __CLASS__ . " " . __METHOD__ . ": $message");
        }
    }

    public function send_forgotten_password_email_user_activated($url)
    {
        try {
            $this->send_password_email('forgotten_password_user', 'Login reset request', $url);
        } catch (Exception $e) {
            $_SESSION['feedback'] .= g_feedback("error", "Error: problem with forgotten password. Please use the bug-reporting system");
            $message = $e->getMessage();
            error_log("Caught exception: in " . __CLASS__ . " " . __METHOD__ . ": $message");
        }
    }

    public function send_forgotten_password_email()
    {
        try {
            $this->send_password_email('forgotten_password', 'Login reset request');
        } catch (Exception $e) {
            $_SESSION['feedback'] .= g_feedback("error", "Error: problem with forgotten password. Please use the bug-reporting system");
            $message = $e->getMessage();
            error_log("Caught exception: in " . __CLASS__ . " " . __METHOD__ . ": $message");
        }
    }

    /**
     * Sends email to user with link to create their own password.
     *
     * @since  Method added since 2018-05-16.
     */
    public function send_password_email($template, $subject, $activation_link = '')
    {
        global $cfg, $db;

        $expires = $this->default_password_link_expiry;
        if (!empty($cfg['password_link_expiry'])) {
            $expires = $cfg['password_link_expiry'];
        }
        $password_link = $this->get_user_password_link($expires);

        general_email(
            array(
                "template"=>$template,
                "subject"=>$cfg['client'].' System: ' . $subject,
                'username' => $this->username,
                'password_link' => $password_link,
                'activation_link' => $activation_link,
                'expiry' => $expires,
                'feedback'=>false,
                'recipients'=>array(array('email'=>$this->email,'fullname'=>$this->fullname)),
            )
        );

        $db->update(
            $this->table,
            array(
                'password_link_time' => date("Y-m-d H:i:s")
            ),
            array(
                "WHERE id = ?",
                array('id'=>$this->id),
                array('integer')
            )
        );
        // $this->update(array('password_link_time' => date("Y-m-d H:i:s")));
    }

    /**
     * Creates a time dependent URL to new_user_setup
     * may be able to be used for forgotten password as well.
     *
     * @param  string Text based time interval (e.g. 2 days)
     * @param  array key value array of any additional URL request arguments
     * @return string Enrypted absolute URL
     * @since  Method added since 2018-05-14.
     */
    public function get_user_password_link($expires, array $additional_args = array())
    {
        $token_time = strtotime("+" . $expires);
        $http_args = array('user_id' => $this->id, 'token_time' => $token_time);
        $http_args = array_merge($http_args, $additional_args);
        $password_link = encrypt_url("new_user_setup.php?" . http_build_query($http_args));
        return $password_link;
    }

    // $addional parameter is currently unused, added to comply with Object definition.
    function update(array $additional = array()) {
        global $cfg, $crypt, $user1;

        $success = false;

        //If restoring active, set failed logins to 0
        if ($this->active) {
            $this->failed_login=0;
            $additional['failed_login'] = 0;
        }

        //If updating password add to empty
        if (!empty($this->new_password)){
            $this->password = $crypt->bcrypt($this->new_password);
            if (!empty($this->update_password)){
                $additional['password_changed_by_sa'] = 1;

            }
            $success = $user1->update_password($this->id, $this->password);
            if (!$success) {
                return $success;
            }
        }

        $success = false;

        if (parent::update($additional)) {

            //Set user groups for new User
            $this->set_user_groups();

			$this->update_user_ward_groups();

            $success = true;

            $_SESSION['feedback'] .= g_feedback("success","Success: updated details for user $this->username");
        } else {
            $_SESSION['feedback'] .= g_feedback("error","Error: problem with user update. Please use the bug-reporting system");
        }

        return $success;
    }

	function update_user_ward_groups(){
		global $db;

		if(!empty($this->ward_group_ids)){

			foreach($this->ward_group_ids as $ward_group_id=>$value){

				$where = array(
					"WHERE user_id=? AND group_id=?",
					array('user_id' => $this->id, 'group_id' => $ward_group_id),
					array('integer', 'integer')
				);

				if(empty($value) && $db->tcount("user_ward_security_groups_links",$where)>0){

					$db->delete("user_ward_security_groups_links",$where);

				} elseif (!empty($value) && $db->tcount("user_ward_security_groups_links",$where)==0){

					$User_Ward_Security_Group_Link = new User_Ward_Security_Group_Link;
					$User_Ward_Security_Group_Link->user_id = $this->id;
					$User_Ward_Security_Group_Link->group_id = $ward_group_id;
                    $User_Ward_Security_Group_Link->insert();

				}

			}

		}
	}

    function fields_check(){
        global $db, $cfg, $my_post, $libhtml;

        $message = '';
        $db_username = $db->select_value("username", "system_users", array("WHERE username=?", array('username' => $this->username), array('varchar')));
        $db_email = $db->select_value("email", "system_users", array("WHERE email=?", array('email' => $this->email), array('varchar')));

        if (!preg_match('/^[a-zA-Z0-9-_\@\.]+$/', $this->username)) {

            $message = "Error: username '$this->username' contains invalid characters.";

        //Username must not be more than 100 characters
        } elseif (strlen($this->username) > 100) {

            $message = "Error: username '$this->username' is longer than characters.";

        //Username must be unique
        } elseif (!empty($db_username) && ((is_array($db_username) && count($db_username)>1 && !empty($this->id)) || empty($this->id))) {

            $message = "Error: username '$this->username' already exists in the database";

        //Email must be valid
        } elseif (!IsEmail($this->email)) {

            $message = "Error: provided email '$this->email' is not a valid email";

        //Email must be unique
        } elseif (!empty($db_email) && ((is_array($db_email) && count($db_email)>1 && !empty($this->id)) || empty($this->id))) {

            $message = "Error: provided email '$this->email' is not unique";

        //Are there any user groups?
        } elseif (empty($this->id) && (empty($this->user_group_ids) || (1*implode('',$this->user_group_ids)=='0'))) {

            $message = "You must select at least one user group for this user.";

        }

        return $message;

    }

    function get_user_groups(){
        global $cfg, $db, $user1, $libhtml;

        $this->user_groups = $this->user_groups_string = $this->user_group_names = array();

        $selection = $db->select(
            "g.id, g.name","system_user_groups g",
            array("WHERE t.user_id=?", array('user_id' => $this->id), array('integer')),
            array(
                'order_by'=>"ORDER BY g.name ASC",
                'joins' => "LEFT JOIN system_user_group_links t ON g.id=t.group_id"
            )
        );

        foreach($selection as $item){

            $this->user_groups[] = $item->id;
            $this->user_group_names[] = $item->name;
            $this->user_groups_string[] = href_link(array(
                "permission"=>$user1->{"users.php"},
                "url"=>$cfg['root'] . "users.php?tab=pages&user_group_id=$item->id",
                "text"=>$item->name,
                "title"=>"User Group Details",
                "popup"=>false,
                "button"=>false,
                "clear"=>false
            ));

        }

        $this->user_groups_string = implode("<br/>",$this->user_groups_string);
        $this->user_groups_text = implode(', ',$this->user_group_names);
    }


    function get_user_ward_groups(){
        global $cfg, $db, $user1, $libhtml;

        $this->user_ward_groups = $this->user_ward_groups_string = $this->user_ward_group_names = $this->user_access_wards = $this->user_access_wards_string = array();
        $this->user_access_locations = $this->user_access_locations_string = array();

        //         select * from ward_security_groups t
        //         inner join user_ward_security_groups_links u on u.group_id = t.id
        $selection = $db->select(
            "g.id, g.name","ward_security_groups g",
            array("WHERE t.user_id=?", array('user_id' => $this->id), array('integer')),
            array(
                'order_by'=>"ORDER BY g.name ASC",
                'joins' => "LEFT JOIN user_ward_security_groups_links t ON g.id=t.group_id"
            )
        );

        foreach($selection as $item){

            $this->user_ward_groups[] = $item->id;
            $this->user_ward_group_names[] = $item->name;
            $this->user_ward_groups_string[] = href_link(array(
                "permission"=>$user1->{"ward_security_group_details.php"},
                "url"=>$cfg['root'] . "ward_security_group_details.php?tab=wards&ward_security_group_id=$item->id".'&active_search=All',
                "text"=>$item->name,
                "title"=>$libhtml->local_text['Ward']." Security Group Details",
                "popup"=>false,
                "button"=>false,
                "clear"=>false
            ));

        }

        $ward = new Ward();
        $wardsSelection = $ward->get_user_accessible_wards($this->id);
        foreach ($wardsSelection as $row) {
            if (!in_array($row->id, $this->user_access_wards)){
                $this->user_access_wards[] = $row->id;
                $this->user_access_wards_string[] = $row->name;
            }
        }

        $location = new Location();
        $locationsSelection = $location->get_user_accessible_locations($this->id);

        foreach ($locationsSelection as $row) {
            $this->user_access_locations[] = $row->id;
            $this->user_access_locations_string[] = $row->name;
        }

        $this->user_access_locations_text = implode(', ', $this->user_access_locations_string);
        $this->user_access_wards_text = implode(', ', $this->user_access_wards_string);
        $this->user_ward_groups_string = implode("<br/>",$this->user_ward_groups_string);
        $this->user_ward_groups_text = implode(', ',$this->user_ward_group_names);
    }

    function set_user_groups(){
        global $cfg, $db;
        //Set user groups

        $user_group_link = new User_Group_Link;

        if (!empty($this->user_group_ids)) {

            foreach ($this->user_group_ids as $key => $value) {

                $db->delete(
                        "system_user_group_links",
                        array(
                                "WHERE user_id=? AND group_id=?",
                                array('user_id' => $this->id, 'group_id' => $key),
                                array('integer', 'integer')
                        )
                );

                if($value == 1) {
                    $db->insert(
                        "system_user_group_links",
                        array(
                                'user_id'=>$this->id,
                                'group_id'=>$key
                        ),
                        $user_group_link->table_types
                    );
                }
            }
        }
    }

    function print_details() {
        global $db, $user1, $libhtml;

        $html = open_table("", "Basic details", "action_form details_form");
        $html .= $libhtml->render_table_row("User Full Name",$this->fullname);
        $html .= $libhtml->render_table_row("Email",(IsEmail($this->email)) ? "<a href=\"mailto:$this->email\">$this->email</a>" : '');

        $html .= table_separator('',"User access","action_form details_form");
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

        $html .= $libhtml->render_table_row("SysAdmin (Receives forgotten password for approval)", tick_cross_image($this->is_sa,false));
        $html .= $libhtml->render_table_row("Authentication Type", $this->auth_type);






        $html .= close_table();

        return $html;
    }

    function print_form() {
        global $cfg, $db, $libhtml, $user1;
        $html = $libhtml->form_start();

        $is_edit = false;
        if ($libhtml->basename == 'edit_user.php') {
            $is_edit = true;
            $html .= '<div class="hint">To modify the active status use the Activate/Deactivate button on the list.</div>';
        }

        $html .= open_table("","User Details","action_form");
        $html .= $libhtml->render_form_table_row("user[fullname]", $this->fullname, "Full Name", "fullname",array('required'=>true,'tooltip'=>'Name that will appear on top of the page when logged in'));
        $html .= $libhtml->render_form_table_row("user[email]", $this->email, "Email", "email", array("unique"=>true,'tooltip'=>'Email must be valid and unique'));
        $html .= $libhtml->render_form_table_row("user[username]", $this->username, "Username", "username",array('required'=>true, "unique"=>true,'tooltip'=>'Username must be unique and no more than 30 characters long; it must not contain any characters other than alphanumerics, @ and . (dot)'));

        if ($is_edit === true) {
            $html .= $libhtml->render_table_row("Active", tick_cross_image($this->active));
        } else {
            if (is_null($this->active)) {
                $this->active=1;
            }
            $html .= $libhtml->render_form_table_row_checkbox("user[active]", $this->active, "Active", "active");
        }

		$html .= close_table();

            //Update User
        if (!empty($this->id) && $this->auth_type === "Native"){
            //Password update
            $html .= open_table("","Password","action_form");

            if (!isset($this->update_password)) $this->update_password=null;
            $html .= $libhtml->render_form_table_row_checkbox("user[update_password]", $this->update_password, "Update User Password", "update_password",array('self_submit'=>true));

            if (!empty($this->update_password)){

                $html .= $libhtml->render_form_table_row_password("user[new_password]", "New Password", "new_password",array(
                    'required'=>empty($this->id),
                    'regex'=>$cfg['password'],
                    'regex_message'=>$cfg['password_message'],
                ));

                $html .= $libhtml->render_form_table_row_password("user[confirm_password]", "Confirm Password", "confirm_password", array(
                    'extra'=>" equalTo=\"#new_password\"",
                    'class'=>'no_strength form_style',
                    'required'=>empty($this->id)
                ));

            } else {

                $html .= $libhtml->render_form_table_row_hidden("user[new_password]",null);
                $html .= $libhtml->render_form_table_row_hidden("user[confirm_password]",null);
            }

			$html .= close_table();
        }


        //User groups
        $html .= open_table("","User Groups");

        $groups = $db->select(
            "id, name,comment",
            "system_user_groups",
            array("WHERE id > ?", array('id' => 0), array('integer')),
            array('order_by'=>"ORDER BY name ASC")
        );

        if(count($groups)>0) {

            if (!isset($this->user_group_ids)) {
                if (isset($this->user_groups)) {
                    foreach ($this->user_groups as $group) $tmp['group'][$group] = 1;
                } else {
                    foreach ($groups as $group) $tmp['group'][$group->id] = 0;
                }
            } else {
                foreach ($this->user_group_ids as $key => $value) $tmp['group'][$key] = $value;
            }
            foreach ($groups as $group) {
                if (!isset($tmp['group'][$group->id])) $tmp['group'][$group->id] = 0;

                $html .= '
                    <tr>
                         <th style="width: 200px; min-width: 200px;">
                              <label for="'.$group->id.'">'.$group->name.'</label>
                         </th>
                         <td>'.$libhtml->render_form_table_row_checkbox("user[user_group_ids][$group->id]", $tmp['group'][$group->id], $group->name, $group->id,array('td_class'=>'column_multiselect','minimal'=>true)).'</td>
                         <td>'.$group->comment.'</td>
                    </tr>';

            }
        } else {
            $html .= '<tr><td colspan="100%"><div class="no_data">There are no User Groups set on the system</div></td></tr>';
        }

		$html .= close_table();

        if (empty($this->id)) {

            $html .= open_table("","Notifications");

            if (!isset($this->notify_admin)) $this->notify_admin=1;
            $html .= $libhtml->render_form_table_row_checkbox("user[notify_admin]", $this->notify_admin, "Notify System Administrator", "notify_admin");
            $html .= '<tr><td colspan="2"><div class="hint">The user is always notified for security reasons to allow them to create their own password.</div></td></tr>';

			$html .= close_table();

        }

        $html .= open_table("","Restrictions");

        $html .= $libhtml->render_form_table_row("user[allowed_ip]", $this->allowed_ip, "Allowed IP", "allowed_ip");

        if (isset($this->table_types['unique_login'])) $html .= $libhtml->render_form_table_row_checkbox("user[unique_login]", $this->unique_login, "Unique Login", "unique_login");

        // if select group has update_user permission.
        // get group ids
        $group_ids = array();
        if (isset($this->group_ids)) $group_ids = explode(',', $this->group_ids);

        if ($user1->id == 0 && $this->can_update_user($group_ids) >0) {
            $html .= $libhtml->render_form_table_row_checkbox("user[is_sa]", $this->is_sa, "SysAdmin (Receives forgotten password for approval)", "is_sa");
        } else {
            $html .= $libhtml->render_form_table_row_hidden("user[is_sa]",$this->is_sa);
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

    function is_deactivated()
    {
        if (!empty($this->deactivation_time)) {
            return true;
        }
        return false;
    }

    public function activate()
    {
        global $cfg, $crypt, $user1;

        $success = false;
        $this->active = 1;
        $this->failed_login = 0;
        $this->deactivation_time = null;
        $this->deactivation_reason = null;
        $additional['active'] = $this->active;
        $additional['failed_login'] = $this->failed_login;
        $additional['deactivation_time'] = $this->deactivation_time;
        $additional['deactivation_reason'] = $this->deactivation_reason;

        if (parent::update($additional)) {
            $success = true;
        } else {
            $mssage = "Error: problem with activating user. Please use the bug-reporting system";
            error_log($message);
        }

        return $success;
    }
    public function print_activate_form()
    {
        global $cfg, $db, $libhtml, $user1;

        $html = $libhtml->form_start();
        $html .= open_table("", "User Details", "action_form");
        $html .= '<tr><td colspan="2"><div class="hint">Are you sure you wish to activate the following user?</div></td></tr>';
        $html .= $libhtml->render_table_row("Fullname", $this->fullname);
        $html .= $libhtml->render_table_row("Email", $this->email);
        $html .= $libhtml->render_table_row("Username", $this->username);
        $status = 'Locked out due to password attempts / not created active';
        if ($this->is_deactivated() === true) {
            $status = 'Explicitly disabled on ' .$this->deactivation_time ;
        } elseif ($this->active == 1) {
            $status = 'Active';
        }
        $html .= $libhtml->render_table_row("Current Status", $status);
        if (!empty($this->deactivation_reason)) {
            $html .= $libhtml->render_table_row("Deactivation Reason", $this->deactivation_reason);
        }

        $html .= close_table();
        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button(
                    'activate',
                    'Activate',
                    array(
                        $this->object_name,
                        'activate',
                        get_class($this),
                        $this->id
                    )
                ),
            ),
            array(
                "pause" => false,
                "object_name" => $this->object_name,
                "object_id" => $this->id
            )
        );
        return $html;
    }


    public function deactivate()
    {
        global $cfg, $crypt, $user1;

        $success = false;

        //If restoring active, set failed logins to 0
        if ($this->active) {
            $this->active = 0;
            $this->deactivation_time = date("Y-m-d H:i:s");
            $additional['deactivation_time'] = $this->deactivation_time;
            $additional['active'] = $this->active;
        }

        if (parent::update($additional)) {
            $success = true;
        } else {
            $message = "Error: problem with deactivating user. Please use the bug-reporting system";
            error_log($message);
        }

        return $success;
    }
    public function print_deactivate_form()
    {
        global $cfg, $db, $libhtml, $user1;

        $html = $libhtml->form_start();
        $html .= open_table("", "User Details", "action_form");
        $html .= '<tr><td colspan="2"><div class="hint">Are you sure you wish to deactivate the following user?</div></td></tr>';
        $html .= $libhtml->render_table_row("Fullname", $this->fullname);
        $html .= $libhtml->render_table_row("Email", $this->email);
        $html .= $libhtml->render_table_row("Username", $this->username);
        if ($this->active == 1) {
            $status = 'Active';
        } else {
            $status = 'Locked out due to password attempts';
        }
        $html .= $libhtml->render_table_row("Current Status", $status);
        $html .= $libhtml->render_form_table_row_text("user[deactivation_reason]", $this->deactivation_reason, "Deactivation Reason", "deactivation_reason", array('tooltip'=>'Reason for deactivation'));

        $html .= close_table();
        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button(
                    'deactivate',
                    'Deactivate',
                    array(
                        $this->object_name,
                        'deactivate',
                        get_class($this),
                        $this->id
                    )
                ),
            ),
            array(
                "pause" => false,
                "object_name" => $this->object_name,
                "object_id" => $this->id
            )
        );
        return $html;
    }

    public function print_resend_password_link_form()
    {
        global $libhtml;
        $html = $libhtml->form_start();
        $html .= '<div class="hint">';
        $html .= "The password link for the user has expired.";
        $html .= '</div>';
        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button(
                    'send_new_password_email_user',
                    'Resend email',
                    array(
                        $this->object_name,
                        'send_new_password_email',
                        get_class($this),
                        $this->id
                    )
                ),
            ),
            array(
                "pause" => false,
                "object_name" => $this->object_name,
                "object_id" => $this->id
            )
        );
        return $html;
    }

    protected function getItemActive($item)
    {
        global $cfg, $user1;
        if (is_null($item->password) || empty($item->password)) {
            $password_link_time = strtotime($item->password_link_time);
            $nice_password_link_time = date('Y-m-d H:i:s', $password_link_time);
            $active = '<span class="tooltip ico_exclamation_circle_good" title="Pending (Active) email sent on ' . $nice_password_link_time . '"><i class="fa fa-exclamation-circle"></i></span>';
            if ($item->active_status == 0) {
                $active = '<span class="tooltip ico_exclamation_circle_bad" title="Pending (Disabled) email sent on ' . $nice_password_link_time . '"><i class="fa fa-exclamation-circle"></i></span>';
            }

            $expires = $this->default_password_link_expiry;
            if (!empty($cfg['password_link_expiry'])) {
                $expires = $cfg['password_link_expiry'];
            }

            if ($password_link_time < strtotime("-" . $expires)) {
                $active = '<span class="tooltip ico_exclamation_triangle_neutral" title="Link Expired email sent on ' . $nice_password_link_time . '"><i class="fa fa-exclamation-triangle"></i></span>';
                $active .= href_link(
                    array(
                        "permission"=>$user1->{"resend-password-link_user.php"},
                        "url"=>$cfg["root"] . "resend-password-link_user.php?user_id=" . $item->id,
                        "text"=>"Resend Email",
                        "extra" => 'style = "float: unset !important; "',
                        "clear" => false,
                    )
                );
            }
            return $active;
        } else {
            return tick_cross_image($item->active);
        }
    }

    protected function getItemActivation($item)
    {
        global $cfg, $user1;
        if ($item->auth_type != 'Native') {
            return;
        }
        $activate_tooltip = 'Activate';
        // deactivation_reason
        if (!empty($item->deactivation_time)) {
            $activate_tooltip = 'Activate (Disabled on ' . $item->deactivation_time . ' wth reason: ' . $item->deactivation_reason . ')';
        }
        if ($item->active_status == 1) {
            return href_link(
                array(
                    "permission"=>($user1->{"deactivate_user.php"} && ($item->id!=0)),
                    "url"=>$cfg['root'] . "deactivate_user.php?user_id=" . $item->id,
                    "text"=>'Deactivate',
                    "title"=>'Deactivate',
                    "button"=>true,
                    "clear"=>false,
                    "tooltip"=>'Deactivate',
                    "extra"=>"style='width:80px'"
                )
            );
        } else {
            return href_link(
                array(
                    "permission"=>($user1->{"activate_user.php"} && ($item->id!=0)),
                    "url"=>$cfg['root'] . "activate_user.php?user_id=" . $item->id,
                    "text"=>'Activate',
                    "title"=>'Activate',
                    "button"=>true,
                    "clear"=>false,
                    "tooltip"=>$activate_tooltip,
                    "extra"=>"style='width:80px'"
                )
            );
        }
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        if (!empty($this->view_array['status']['display']) || !isset($this->view_array['status']['display'])) {

            if (empty($item->status)) $item->status = '';

            if ((round(abs(strtotime(date("Y-m-d H:i:s")) - $item->access) / 1,2)) < ini_get('session.gc_maxlifetime')) {
                $user_online = true;
                $item->status .= '<span class="ico_green_circle tooltip" title="User Online"><i class="fa fa-circle"></i></span>';
            } else {
                $item->status .= '<span class="ico_grey_circle tooltip" title="User Offline"><i class="fa fa-circle-o"></i></span>';

            }
        }

        $item->status_td_class = "user_reset user" . $item->id;

        $last_login_html = '';
        if (empty($item->last_login)) $last_login_html .= '<span class="txtwrap txtwrap_red">Never</span>';
        else if (!empty($user_online) && rel_time(strtotime($item->last_login), time()) == "now") $last_login_html .= ' <span class="txtwrap txtwrap_green">'.rel_time(strtotime($item->last_login), time()).'</span>';
        else if (!empty($user_online)) $last_login_html .= ' <span class="txtwrap txtwrap_green">'.rel_time(strtotime($item->last_login), time()).' ago</span>';
        else $last_login_html .= ' <span class="txtwrap">'.rel_time(strtotime($item->last_login), time()).' ago</span>';
        $last_login_html .= zero_date($item->last_login, $user1->preferences->dateformat . " H:i");
        $item->last_login = $last_login_html;

        if ($item->allowed_ip) $item->allowed_ip = $item->allowed_ip.' ('.gethostbyaddr($item->allowed_ip).')';

        if (!empty($cfg['unique_login'])) $item->unique_login = ajax_toggle(
                $item->id,
                $this->table,
                "unique_login",
                $user1->{"edit_user.php"},
                $item->unique_login
        );

        $item->fullname = href_link(array(
                "permission"=>($user1->{"user_details.php"} && ($item->id!=0)),
                "url"=>$cfg['root'] . "user_details.php?user_id=$item->id",
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

            $ids = explode(",",$item->group_ids);

            $plain_names = explode(",",$item->group_names);

            for($c = 0; $c < count($ids); $c++ ){

                $names[] = href_link(array(
                            "permission"=>$user1->{"users.php"},
                            "url"=>$cfg['root'] . "users.php?tab=pages&subtab=page_permissions&user_group_id=".$ids[$c],
                            "text"=>$plain_names[$c],
                             "tooltip"=>'Group permission details',
                            "popup"=>false,
                            "button"=>false,
                            "float" => "",
                            "clear"=>false
                ));
            }

            $item->user_groups = implode(", ",$names);

        } else {

            $item->user_groups = '';
        }

        if (!empty($item->wgroup_id)){
            $link = href_link(array(
                "permission"=>$user1->{$libhtml->path."edit_ward_security_group.php"},
                "url"=>$cfg["root"] . "includes/edit_ward_security_group_details.php?subtab=users&user_id=".$item->id."&group_id=".$item->wgroup_id.'&link_id='.$item->link_id.'&active_search='.my_request("active_search").'&path='.$libhtml->path,
                "text"=>"Remove",//tick_cross_image(!empty($item->link_id)),
                "tooltip"=>'Unlink from '.$libhtml->local_text['Ward'].' Security Group',
                "popup"=>false,
                "button"=>true,
                "float" => "",
                "clear"=>false));

            $item->link_id = $link;
        }

        return;
    }

    function delete() {
        global $cfg, $db;
        $db->delete("system_log", array("WHERE user_id=?", array('user_id' => $this->id), array('integer')));
        $db->delete("system_user_group_links", array("WHERE user_id=?", array('user_id' => $this->id), array('integer')));
        return parent::delete();
    }

    function _list($options=array()) {
        global $db, $user1, $cfg, $limit;
        $this->view_array['unique_login']['display'] = (isset($this->unique_login));

        if ($user1->{"deactivate_user.php"} || $user1->{"activate_user.php"}) {
            $this->view_array['activation']['display'] = true;
        }
        return parent::_list($options);
    }

    function _listWardGroupUsers($options=array()) {
        global $db, $user1, $cfg, $limit;

        return parent::_list($options);
    }

    function _multiEditList($options=array()) {
        global $db, $user1, $cfg, $limit;
        return parent::_list($options);
    }

    // Magic method to handle all non-permitted pages, rather than have a bunch of 0's in session
    function __get($name){
        if (isset($this->$name)){
            return $this->$name;
        } else {
            //error_log("Setting $name to null");
            $this->$name = null;
            return null;
        }
    }

    function fetch_open_id(){
        global $db, $cfg;
        //$selection = $db->select("provider, identity", "system_openid", array("WHERE user_id = ?", array('user_id' => $this->id), array('integer')));
        //if (!empty($selection)){
        //    return $selection[0];
        //} else {
            return false;
        //}
    }

    function print_select_user_group_form($full=false){
        global $db, $user1, $cfg, $libhtml;

        $this->all_user_groups = $db->select(
            "id, name",
            "system_user_groups",
            array('WHERE id>0',array(),array()),
            array('order_by'=>"ORDER BY name ASC")
        );

        $html = $libhtml->form_start();

        $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

        $html .= open_table("800px");

        if (count($this->all_user_groups)>6){
            $html .= $libhtml->render_form_table_row_selection("user_group_id", my_request('user_group_id'), "User Group", "user_group_id", $this->all_user_groups, "id","name", array('class'=>"self_submit"));
        } else {
            $html .= $libhtml->render_form_table_radio_selection("user_group_id", my_request('user_group_id'), "User Group", "user_group_id", $this->all_user_groups, "id","name", array('class'=>"self_submit",'radio_break'=>2));
        }

        if ($full){
            $html .= $libhtml->render_form_table_radio_selection("active_search", my_request('active_search'), "Active", "active_search", array('Active','Inactive','All'),'','',array('class'=>"self_submit"));
            $html .= $libhtml->render_form_table_radio_selection("logged_search", my_request('logged_search'), "Logged in", "logged_search", array('Online','Offline','All'),'','',array('class'=>"self_submit"));
            $html .= $libhtml->render_form_table_radio_selection("auth_search", my_request('auth_search'), "Authentication Type", "auth_search", array('Native','AD/LDAP','All'),'','',array('class'=>"self_submit"));
        }

        $html .= close_table();

        $html .= $libhtml->form_end();

        $where = array(array(),array(),array());
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

        if (my_request("logged_search")=='Online'){
            $where[0][]= "(SELECT MAX(access) FROM system_session s WHERE s.user_id=t.id) IS NOT NULL";
        } elseif (my_request("logged_search")=='Offline'){
            $where[0][]= "(SELECT MAX(access) FROM system_session s WHERE s.user_id=t.id) IS NULL";
        }

        if (my_request("auth_search")!='' && my_request("auth_search")!='All'){
            $where[0][]= "t.auth_type = ?";
            $where[1][] = my_request("auth_search");
            $where[2][] = 'varchar';
        }

        $where[0]=(!empty($where[0])) ? "WHERE ".implode(" AND ",$where[0]): '';

        return array(
                'html'=>$html,
                'where'=>$where,

        );
    }

    function full_list_search(){
        global $cfg, $db, $crypt, $libhtml;

        if (my_post('filter_search')!='') {

            header("Location: " . encrypt_url($cfg["root"] . "user_details.php?user_id=" . my_post('filter_search')));
            exit;

        } else {

            $html = $libhtml->form_start();
            $html .= open_table("", "", "full_list_search action_form");

            $html .= $libhtml->render_form_table_row_autocomplete("filter_search", "", "Find a user", "filter_search", $this->table ,"CONCAT(fullname,' / ',username,' / ',email)","id",array(
                    'where'=>"WHERE CONCAT(fullname,' / ',username,' / ',email) LIKE ?",
                    "auto_select"=>true,
                    'th_width'=>'auto',
            ));
            $html .= close_table();

            $html .= $libhtml->form_end();
        }

        return $html;
    }

    function count_SAs() {
        return $this->_count_where(array('WHERE is_sa = 1 AND active=1', array(), array()));
    }

    function can_update_user(array $group_ids = array()) {
        global $db;

        $where = array("WHERE p.page = ?", array('edit_user.php'), array('varchar'));
        if (count($group_ids) > 0 && !empty($group_ids[0])) {
            // do not arbitrarily trust array, enforce integers
            $where[0] .= " AND g.id in (";
            $where_string = array();
            foreach ($group_ids as $group_id) {
                $where_string[] = '?';
                $where[1][] = $group_id;
                $where[2][] = 'integer';
            }
            $where[0] .= implode(',', $where_string).')';
        }

        return $db->tcount(
            'system_user_group_permissions t
            INNER JOIN system_pages p on t.page_id = p.id
            INNER JOIN system_user_groups g on t.group_id = g.id',
            $where
        );
    }

    function get_SA_contact_details() {
        global $db;

        return $db->select('email,fullname', $this->table, array('WHERE is_sa = 1 AND active=1', array(), array()));
    }

    function print_multiedit_form(){
        global $libhtml,$my_get,$my_post,$db,$cfg;

        $html = $libhtml->form_start();

        if (!empty($this->ids)){

            foreach($this->ids as $key=>$id){

                $html .= $libhtml->render_form_table_row_hidden($this->object_name."[ids][$key]",$this->ids[$key]);

            }

            $this->view_array = array(
                'fullname'=>array("name"=>"Full Name"),
                'username'=>array("name"=>"Username","column"=>"username"),
                'is_sa'=>array("name"=>"SA"),
                'auth_type'=>array("name"=>"Authentication"),
                'active'=>array("name"=>"Active"),
            );

            //List reason
            $html .= $this->_multiEditList(array(
                'width'=>'100%',
                'table_wrapper'=>false,
                'edit'=>false,
                'delete'=>false,
                'pagination'=>false,
                'where'=>array('WHERE t.id IN ('.implode(',',$this->ids).')',array(),array()),
            ));

            $wardGroups = new Ward_Security_Group;
            $wardGroups->view_array = array(
                'name'=>array("name"=>$libhtml->local_text['Ward']." Security Group Name"),
                'wards'=>array("name"=>"List of ".str_plural($libhtml->local_text['Ward']),"display" => true, 'toggle_all'=>true ),
                'locations'=>array("name"=>"List of ".str_plural($libhtml->local_text['Location']),"display" => true, 'toggle_all'=>true),
                'pick'=>array("name"=>"Link","display" => true,"width"=>"60px")
            );

            $html .= $wardGroups->_list(array(
                'width'=>"100%",
                'table_wrapper'=>false,
                'edit'=>false,
                'delete'=>false,
                'pagination'=>false,
                '_toggle'=>true,
                'fix_toggler'=>true,
            ));

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_button("edit", "Add Selected ".$libhtml->local_text['Ward']." Security Group(s)")
                ), array(
                    'pause'=>false,
                    'show_prompt'=>true,
                    'show_cancel'=>true,
                )
            );

        } else {

            $html .= '<div class="hint">No users selected.</div>';

        }

        $html .= $libhtml->form_end();

        return $html;

    }

    function multiedit(){
        global $db;

        $User_Ward_Security_Group_Link = new User_Ward_Security_Group_Link;

        if (!empty($this->ids) && !empty($this->ward_group_ids)){
            foreach($this->ids as $key=>$userId){
                foreach($this->ward_group_ids as $groupId=>$value){
                    if($value == 1) {
                        $db->delete(
                            "user_ward_security_groups_links",
                            array(
                                "WHERE user_id=? AND group_id=?",
                                array('user_id' => $userId, 'group_id' => $groupId),
                                array('integer', 'integer')
                            )
                        );

                        $User_Ward_Security_Group_Link->set_post(array('user_id'=>$userId,'group_id'=>$groupId));
                        $User_Ward_Security_Group_Link->insert();
                        /* $db->insert(
                            	"user_ward_security_groups_links",
	                            array(
	                                'user_id'=>$userId,
	                                'group_id'=>$groupId
	                            ),
	                            $User_Ward_Security_Group_Link->table_types
                            ); */

                    }

                }

            }

        }

    }

    function get_user_assigned_ward_groups(){
        global $user1, $db;

        $groups = $db->select_distinct(
            "g.id, g.name,g.comment",
            "ward_security_groups g
             INNER JOIN user_ward_security_groups_links l on l.group_id = g.id",
            array(
                'WHERE  l.user_id = ?',
                array($this->id),
                array('integer')
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

    function print_select_location_group_filter_form($full=true){
        global $db, $user1, $cfg, $libhtml;

        $html = $libhtml->form_start();
        $html .= '<table style="width:100% !important;"><tr><td style="width:50%; padding-right: 5px;vertical-align:top;">';
        $html .= open_table("100%");
        $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);
        $html .= $libhtml->render_form_table_radio_selection("active_search", my_request('active_search'), "Active", "active_search", array('Active','Inactive','All'),'','',array('class'=>"self_submit"));
        $html .= close_table();
        $html .= '</td><td style="width:50%; padding-right: 5px;vertical-align:top;">';
        $html .= '</td></tr></table>';
        $html .= $libhtml->form_end();
        $where = array(array(),array(),array());

        if (my_request("active_search")=='Active'){
            $where[0][]= "t.active = 1";
        } elseif (my_request("active_search")=='Inactive'){
            $where[0][]= "(t.active = 0)";
        }

        $where[0]=(!empty($where[0])) ? implode(" AND ",$where[0]): '';

        return array(
            'html'=>$html,
            'where'=>$where,
        );

    }

}
