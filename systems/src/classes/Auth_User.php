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
class Auth_User {

    public $system_settings = array();

    function __construct(){
        global $system_log;

        $this->table = 'system_users';
        $this->logged_in = false;
        $this->system_log = $system_log;
        $this->init_settings();

        if (my_post("login")!='') {
            $this->_check_post();
        } else {
            $this->logged_in = $this->_check_login();
        }
    }

    public function is_native_user()
    {
        if ($this->auth_type == 'Native') {
            return true;
        }
        return false;
    }

    function init_settings() {
        global $db;
        $system_settings = $db->select("name, value", "test_app_system_settings", array());
        if (!empty($system_settings)){
            foreach($system_settings as $setting){
                $this->system_settings[$setting->name] = $setting->value;
            }
        }
    }

    function redirect_to_index_page() {
        global $cfg;
        header("Location: " . $cfg['root']);
        exit;
    }

    function same_as_previous_passwords($unencrypted_new_password) {
        global $db, $crypt;

        $result = $db->select(
            'password',
            'system_user_historical_passwords',
            array('WHERE user_id = ?', array($this->id), array('integer')),
            array('order_by' => 'ORDER BY created_time desc')
        );

        $i = 0;
        foreach ($result as $password) {
            // loop through all entries until reach Previous Passwords count
            if (isset($this->system_settings['Password Expiry: Previous Passwords']) && ($i < $this->system_settings['Password Expiry: Previous Passwords'] || $this->system_settings['Password Expiry: Previous Passwords'] < 0)) {
                $result = $crypt->bcrypt_verify($unencrypted_new_password, $password->password);
                if ($result == 1) {
                    return true;
                }
            }
            $i++;
        }
        return false;
    }

    function has_password_expired() {
        if (isset($this->system_settings['Password Expiry: Enable']) && $this->system_settings['Password Expiry: Enable'] == 1) {
            // do not process where timestamp is null.
            if (!is_null($this->password_changed) && !empty($this->password_changed)) {
                if (strtotime($this->system_settings['Password Expiry: Validity'], strtotime($this->password_changed)) - time() < 0) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }

    function update_password($user_id = null, $crypted_password = null) {
        global $db;

        if (is_null($user_id)) $user_id = $this->id;
        if (is_null($crypted_password)) $crypted_password = $this->password;

        try {
            if (!ctype_digit(strval($user_id))) throw new Exception("Invalid user id: $user_id (" . gettype($user_id) . ").");

            $db->start_transaction();
            $previous_password_count = $db->tcount('system_user_historical_passwords', array('WHERE user_id = ?', array($user_id), array('integer')));
            $db->update(
                    $this->table,
                    array('password'=>$crypted_password, 'password_changed'=>date('Y-m-d H:i:s', time()),'password_changed_by_sa'=>null),
                    array("WHERE id = ?", array('id' => $user_id), array('integer'))
            );

            if (isset($this->system_settings['Password Expiry: Enable'])
                && $this->system_settings['Password Expiry: Enable'] == 1
                && $this->system_settings['Password Expiry: Previous Passwords'] > 0
            ) {
                $db->insert(
                    'system_user_historical_passwords',
                    array('user_id'=>$user_id, 'password'=>$crypted_password)
                );
            }

            if (isset($this->system_settings['Password Expiry: Previous Passwords'])
                && $previous_password_count >= $this->system_settings['Password Expiry: Previous Passwords']
            ) {
                //delete existing
                //($previous_password_count - $this->system_settings['Password Expiry: Previous Passwords']) +1
                //rows
                $number_to_delete = ($previous_password_count - $this->system_settings['Password Expiry: Previous Passwords']) +1;

                $db->delete(
                    'system_user_historical_passwords',
                    array('WHERE user_id = ? ORDER BY created_time ASC LIMIT ?',
                        array(
                            $user_id,
                            $number_to_delete
                        ),
                        array('integer', 'integer')
                    )
                );
            }
            $db->complete_transaction();
            return true;
        } catch (Exception $e) {
            error_log('Exception caught in '. __FUNCTION__);
            error_log($e->getMessage());
            error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
            return false;
        }
    }

    function update_expired_password() {
        global $cfg, $crypt, $my_post;

        if (empty($my_post['existing_password']) || empty($my_post['login_password']) || empty($my_post['confirm_password'])) {
            $_SESSION['login_message'] = 'You did not fill in a required field.';
            return;
        } elseif ($my_post['login_password'] != $my_post['confirm_password']) {
            $_SESSION['login_message'] = 'Passwords do not match.';
            return;
        } elseif ($my_post['existing_password'] == $my_post['login_password']) {
            $_SESSION['login_message'] = 'Cannot reuse old password.';
            return;
        } elseif (isset($cfg['password'])
            && isset($cfg['password_message'])
            && !preg_match($cfg['password'], $my_post['login_password'])
        ) {
            $_SESSION['login_message'] = $cfg['password_message'];
            return;
        }

        $this->_confirm_User($my_post['login_username'], $my_post['existing_password']);
        if (!empty($this->id)) {
            // check passwords
            if ($crypt->bcrypt_verify($my_post['existing_password'], $this->password)) {
                //password matches
                if ($this->same_as_previous_passwords($my_post['login_password']) === true) {
                    $_SESSION['login_message'] = 'Cannot use any of the last ' . $this->system_settings['Password Expiry: Previous Passwords'] . ' passwords.';
                    return;
                }

                $_SESSION['password'] = $this->password = $crypt->bcrypt($my_post['login_password']);
                $this->password_changed_by_sa = 0;
                if ($this->update_password()) {
                    unset($_SESSION['password_changed_by_sa']);
                    // set these so can be used in check post
                    $my_post['login']['login_username'] = $my_post['login_username'];
                    $my_post['login']['login_password'] = $my_post['login_password'];

                    $this->_check_post();
                    // redirect
                    $this->redirect_to_index_page();
                } else {
                    $_SESSION['login_message'] = 'An unexpected error occurred please report this with the date '. date('Y-m-d H:i:s') . '.';
                    return;
                }
            } else {
                $_SESSION['login_message'] = 'Current password not recognised.';
                return;
            }
        } else {
            // shouldnt hit this normally
            $_SESSION['login_message'] = 'Unknown user, please try again.';
            $this->redirect_to_index_page();
        }
    }

    private function attemptLDAPLogin($username, $password) {
        $result = true;
        $ldap_settings = new \Riskpoint\Auth\LDAP\Setting;
        if ($ldap_settings->isEnabled()) {
            $ldap = new \Riskpoint\Auth\LDAP();
            $result = $ldap->authenticate($username, $password);
        }
        return $result;
    }

    protected function LDAPLogin($username, $password, $raw_password="") {
        global $db;

        $isRiskpointUser = $db->tcount(
            $this->table,
            array(
                "WHERE username = ? AND auth_type = ?",
                array(
                    $username,
                    'Native'
                ),
                array(
                    'varchar',
                    'varchar'
                )
            ),
            array('cache' => false)
        );
        if ($isRiskpointUser > 0) {
            return -1; //Riskpoint user fall back to Riskpoint Authentication
        }

        // Only bind to AD when logging in, if session username not set then part of login process
        if (!isset($_SESSION['username'])) {
            $ldap = new \Riskpoint\Auth\LDAP();
            $ldap_user = $ldap->getUserArray($username);
            if (empty($ldap_user)) {
                return 1; // no such user
            }

            if (!empty($raw_password)) {
                $ldap_user['password'] = $raw_password;
                $ldap->upsertSystemUser($ldap_user);
            }
            $_SESSION['username'] = $ldap_user['msds-principalname'];
        }
        $username = $_SESSION['username'];
        $selection = $db->select(
            "t.*,(SELECT COUNT(*) FROM system_user_group_links l WHERE l.user_id=t.id) as group_count",
            $this->table.' t',
            array(
                "WHERE t.username = ?",
                array($username),
                array('varchar')
            ),
            array('cache' => false)
        );
        // error_log("dumping user");
        // error_log(print_r($selection, true));
        array2object($selection, $this);
        $this->username = $username;

        $this->set_preferences();

        if ($password == $this->password) {
            $this->logged_in = true;
            return 0;
        }
        if (!empty($raw_password)) {
            // $ldap->upsertSystemUser($ldap_user);
            $ldap_result = $this->attemptLDAPLogin($username, $raw_password);
            if ($ldap_result === true) {

                if (empty($this->group_count)) {
                    unset($_SESSION['username']);
                    error_log("No groups set for AD/LDAP user $username");
                    return 4; // no groups set
                }

                //Select user from database
                $this->logged_in = true;
                $_SESSION['password'] = $this->password;
                return 0; //Success! Username and password confirmed;

            } else {
                unset($_SESSION['username']);
                unset($_SESSION['password']);
                if (!$ldap_user['active']) {
                    error_log("Inactive AD/LDAP user $username");
                    return 4; // user inactive (or no groups set)
                } elseif ($ldap_user['expired']) {
                    error_log("Expired AD/LDAP password for user $username");
                    return 5; // password expired
                }
                $this->log_failed_login();
                $this->logged_in = false;
                error_log("Bad AD/LDAP password for user $username");
                return 2; //Indicates password failure
            }
        }
    }

    protected function get_custom_login_failure_message()
    {
        global $db;
        $message = $db->select_value("value", "test_app_system_settings", array("WHERE name=?", array("Login failure message"), array('varchar')));
        return $message;
    }

    function _check_post() {
        global $db, $cfg,$my_post;

        $username = $password = '';

        if (isset($my_post['login']['login_username'])){
            $username = trim($my_post['login']['login_username']);
        }

        if (isset($my_post['login']['login_password'])){
            $password = trim($my_post['login']['login_password']);
        }

        $md5pass = md5($password . $cfg['md5_salt']);

        if (empty($username) || empty($password)) {
            $this->error = "You did not fill in a required field.";
            return;

        }

        if (strlen($username) > 100) {

            $this->error = "Sorry, the username is longer than 100 characters, please shorten it.";
            return;
        }

        // Check user login
        $result = $this->_confirm_User($username, $md5pass, $password);

        $login_failure_message = "Incorrect username and/or password.<br/>You only have 3 incorrect attempts before an account is locked.<br />If you suspect your account has been locked please contact your Administrator.";
        $custom_message = $this->get_custom_login_failure_message();
        if (!empty($custom_message)) {
            $login_failure_message = nl2br($custom_message);
        }

        if ($result !== 0) {
            /* not logged in for any reason */
            /* kill session variables */
            unset($_SESSION['current_page']);
            unset($_SESSION['redirect_page']);
            unset($_SESSION['history']);
            $this->error = $login_failure_message;
            return;
        }

        // If difference between current time and last active login time stored in database is greater than 7 seconds allow login
        if (!empty($this->unique_login)) {

            $selection = $db->select("access","system_session",array("WHERE user_id=?", array('user_id' => $this->id), array('integer')), array('order_by'=> 'ORDER BY access DESC')); //take the last accessed session

            $last_session_access = (!empty($selection)) ? $selection[0]->access : 0;

            $session_maxlifetime = (ini_get('session.gc_maxlifetime')!=false) ? ini_get('session.gc_maxlifetime') : 1440;

            if ((time() - $last_session_access) < $session_maxlifetime) { // if older than max session life, accept as user not logged in
                $this->error = $login_failure_message;
                $this->logged_in = 0;
                return;
            }
        }

        //If yes record in session
        if ($this->logged_in){

            if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
                $_SESSION['username'] = $username;
            }

            $db->update(
                    $this->table,
                    array(
                            'failed_login'=>0,
                            'active'=>1,
                            'last_login'=> date("Y-m-d H:i:s"),
                    ),
                    array("WHERE id = ?", array('id' => $this->id), array('integer'))
            );

            $this->_post_login_setup();

            $this->system_log->insert(array(
                'time' => date("Y-m-d H:i:s"),
                'user_id' => $this->id,
                'object' => 'User',
                'action' => "Login",
                'object_id' => $this->id,
                'comment' => $_SERVER['REMOTE_ADDR'] . " (".gethostbyaddr($_SERVER['REMOTE_ADDR']).")"
                    . "<br/>" . href_link(array(
                        "permission"=>true,
                        "url"=>$cfg['root'] . "includes/ua_info.php?ua=" . urlencode($_SERVER['HTTP_USER_AGENT']),
                        "text"=>$_SERVER['HTTP_USER_AGENT'],
                        "button"=>false,
                        "clear"=>false,
                        "float"=>"",
                        "class"=>"no_close",
                ))
            ));

            //If the user clicked remember me for longer login or there is no option
            if (!empty($post['remember']) || empty($cfg['remember_me'])) $this->_update_cookie();

            // build the session array for this user
        }
        return;
    }

    function _check_login() {
        global $cfg;

        $result = null;
        if (isset($_SESSION['username']) && isset($_SESSION['password'])) {

            $result = $this->_confirm_User($_SESSION['username'],$_SESSION['password']);

        } elseif (!empty($_COOKIE['riskpoint'])) {

            $cookie = Request::my_cookie('riskpoint');
            $_SESSION['username'] = $cookie['riskpoint_user'];
            $_SESSION['password'] = $cookie['riskpoint_pass'];

            $result = $this->_confirm_User($_SESSION['username'],$_SESSION['password']);

            if ($this->logged_in) $this->_post_login_setup();
        }

        if (!$this->logged_in){
            if ($result == 5
                && strpos($_SERVER['SCRIPT_NAME'], "expired_password.php") !== false
                && strpos($_SERVER['SCRIPT_NAME'], "show_captcha.php") !== false
             ) {
                // Expired password
                $_SESSION['login_message'] = 'Your password has expired please update it.';
                header("Location: " . encrypt_url($cfg['root'] . "expired_password.php?id=" . $this->id));
                exit;
            }

            checkClient();
            unset($_SESSION['username']);
            unset($_SESSION['password']);
            return false;

        }else if($this->password_changed_by_sa == '1'){
            //$_SESSION['redirect_page'] = encrypt_url($cfg['root'] . "expired_password.php?id=" . $this->id);
            $token_time = strtotime("+ 1 day" );
            $http_args = array('user_id' => $this->id, 'token_time' => $token_time);
            $password_link = encrypt_url($cfg['root'] ."new_user_setup.php?" . http_build_query($http_args));
            $_SESSION['redirect_page'] = $password_link;
            unset($_SESSION['username']);
            unset($_SESSION['password']);
            return false;
        } else {

            $this->user_groups = $_SESSION['user_groups'];
            //Master Admin
            $this->master_admin = in_array(0, $this->user_groups);
            $this->_set_user_permissions();
            return true;

        }
    }

    function universal_redirect($try_url=''){
        global $cfg, $db;

        //Universal redirect
        $redirect_page = $cfg['root'];

        if (!empty($try_url)){
            //If there is a passed URL - try and use it

            $redirect_page = $try_url;


        }else if($this->password_changed_by_sa == '1'){
            $_SESSION['redirect_page'] = encrypt_url($cfg['root'] . "expired_password.php?id=" . $this->id);
            unset($_SESSION['username']);
            unset($_SESSION['password']);
            return false;
        } else {

            // Try first allowed page
            $found = false;
            $apps = $db->select("path,front_end_app","system_apps", array("WHERE active=1",array(),array()), array('order_by' => "ORDER BY front_end_app ASC"));
            foreach($apps as $app){
                if (!$found) {
                    $user1 = $this;
                    if (empty($app->front_end_app)) {
                        // switch from require_once to ensure is pulled in for checking
                        require $cfg['source_root'] . $app->path . "includes/admin_menu.php";
                        if (!empty($links)) {
                            foreach($links as $link) {
                                if (!empty($this->{$app->path."index.php"}) && !empty($this->{$app->path.$link[0]})) {
                                    $redirect_page = $cfg['root'].encrypt_url($app->path.$link[0]);
                                    $found=true;
                                    break;
                                }
                            }
                            unset($links);
                        }
                    } else {
                        // has no backend access due to 'ORDER BY' term on select, redirect to correct base path
                        // thus preventing FE users attempting to login to backend being either presented with
                        // 'File not found' page or possibly worse a backend page of the same name.
                        if (!empty($this->{$app->path."index.php"})) {
                            if ($app->front_end_app == 1) {
                                $redirect_page = $cfg['website'];
                                $found=true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        //error_log("Universal redirect->".$l);
        return $redirect_page;
    }

    function check_user_access($page_type="page") {
        global $cfg;
        $script_filename = str_replace("\\", "/",$_SERVER['SCRIPT_FILENAME']);
        $path = str_replace($cfg['source_root'],"",$script_filename);

        //Universal redirect
        $l = $this->universal_redirect();

        //TODO: check if http/https etc and redirect using appropriate protocol
        //If there is a history
        if (isset($_SESSION['history'])){
            $e = end($_SESSION['history']);
            $l = $e['url'];
        }

        //else if there is a homepage
        if (isset($this->preferences->landpage)) {
            if (strpos($this->preferences->landpage, "http") === false) {
                $l = $cfg['root'] . $this->preferences->landpage;
            } else {
                $l = $this->preferences->landpage;
            }
            // if not take universal redirect
        } else {
            if (empty($this->preferences)) $this->preferences = new StdClass;
            $this->preferences->landpage = $l;
        }

        //Are you not logged in?
        if (!$this->logged_in){

            //Popup page
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === "XMLHttpRequest" && $page_type=="popup") {



            //Normal page
            } else if ($page_type=="page"){

                header("Location: " . $cfg['root'] . "?expired_session");
                exit;

            //Redirect to 404
            }

            //If you are logged in
        } else {
            //Does the page exist and do you have permission to access?
            if (!empty($this->$path)){
                return true;
            } else {
                $_SESSION['feedback'] .= g_feedback("error","The page you requested does not exist or you may not have the access permission.");
                header("Location: $l");
                exit;
            }
        }
    }

    protected function set_preferences() {
        global $db;
        //JSON decode the preferences
        if (!is_null(json_decode($this->preferences))) {
            $this->preferences = json_decode($this->preferences);
        } else {
            $this->preferences = new StdClass;
        }

        // write global config into session
        $selection = $db->select("*","system_config", array());
        foreach($selection as $item) {
            if (!isset($this->preferences->{$item->name})) {
                $this->preferences->{$item->name} = ($item->constant) ? constant($item->value) : $item->value;
            }
        }
    }

    function _confirm_User($username, $password, $raw_password="") {
        global $cfg, $db, $crypt;

        $ldap_settings = new \Riskpoint\Auth\LDAP\Setting();
        if ($ldap_settings->isEnabled()) {
            $_SESSION['LDAP_Enabled'] = true;
            $result = $this->LDAPLogin($username, $password, $raw_password);
            if ($result != -1) {
                return $result;
            }
        }
        unset($ldap_settings);
        // error_log($password);
        //Select user from database
        $selection = $db->select("t.*,(SELECT COUNT(*) FROM system_user_group_links l WHERE l.user_id=t.id) as group_count",$this->table.' t',array("WHERE t.username = ?", array($username), array('varchar')), array('cache' => false));

        if (empty($selection)) {
            //No such user
            return 1;
        } else {
            //There is a user; transfer all database fields
            array2object($selection,$this);
            if ($this->has_password_expired() === true) {
                return 5;
            }
            $this->set_preferences();
        }

        //User inactive or no user groups set up
        if (empty($this->active) || empty($this->group_count)) {
            return 4;
        }

        //User's IP not allowed
        if (!empty($this->allowed_ip) && (!in_array($_SERVER['REMOTE_ADDR'],explode(',',$this->allowed_ip)))) {
            return 3;
        }

        //Verify password
        if ($password == $this->password) {

            $this->logged_in = true;

            if (strlen($password) == 32) { //md5 pwd; generate securer password with bcrypt

                $_SESSION['password'] = $this->password = $crypt->bcrypt($raw_password);

                $db->update(
                        $this->table,
                        array('password'=>$this->password),
                        array("WHERE id = ?", array('id' => $this->id), array('integer'))
                );

            }

            return 0; //Success! Username and password confirmed;

        }

        if (!empty($raw_password) && $crypt->bcrypt_verify($raw_password, $this->password)) {

            $_SESSION['password'] = $this->password;
            $this->logged_in = true;

            //check work factor and update password if different
            $chunks = explode("$", $this->password);

            if (isset($cfg['work_factor']) && !empty($cfg['work_factor']) && $cfg['work_factor'] != $chunks[2]) {

                $_SESSION['password'] = $this->password = $crypt->bcrypt($raw_password);

                //update passwd to bcrypt
                $password_update = $db->update(
                        $this->table,
                        array('password'=>$this->password),
                        array("WHERE id = ?", array('id' => $this->id), array('integer'))
                );

                if (!$password_update) $_SESSION['feedback'] .= g_feedback("error", "Error updating password to securer form");

            }

            return 0; //Success! Username and password confirmed;

        } else {

            $this->log_failed_login();
            $this->logged_in = false;
            return 2; //Indicates password failure

        }
    }

    function log_failed_login() {
        global $db,$user1, $cfg;

        $failed_logins = $this->failed_login + 1;

        $user_id = (isset($user1->id)) ? $user1->id : "";

        $this->system_log->insert(array(
            'time' => date("Y-m-d H:i:s"),
            'user_id' => $this->id,
            'object' => 'User',
            'action' => "Failed Login",
            'object_id' => $this->id,
            'comment' => "User " . $this->username . " from " . $_SERVER['REMOTE_ADDR']
        ));

        $active = ($failed_logins >= 3) ? 0 : 1;

        $db->update(
                $this->table,
                array(
                        'failed_login'=>$failed_logins,
                        'active'=>$active
                ),
                array(
                        "WHERE id = ?",
                        array('id' => $this->id),
                        array('integer')
                )
        );
    }

    function _update_cookie() {
        global $cfg, $crypt;

        $time = (!empty($cfg['login_cookie_expiry'])) ? $cfg['login_cookie_expiry'] : 60*60*24;
        $cookie = $crypt->str_encrypt(urlencode(serialize(array(
            "riskpoint_user"=> $_SESSION['username'],
            "riskpoint_pass"=> $_SESSION['password']
        ))));
        setcookie("riskpoint", $cookie, time() + $time, "/",'',isSSL(),true);
    }

    function _post_login_setup() {
        global $db, $cfg, $links;

        // Find out about the client, write into session
        checkClient();

        //Save user id for session identification
        $_SESSION['user_id'] = $this->id;

        // prepare the arrays
        $_SESSION['apps'] = null;
        $temp_arr = array();
        $selection = $db->select("id, name, path, image, tooltip, front_end_app","system_apps",array("WHERE active = 1",array(),array()));
        $selection[99] = (object) array("id"=>99, "name"=>"Logout", "path"=>"logout/", "image"=>"power-off");
        foreach ($selection as $db_app) $temp_arr[$db_app->id] = $db_app;

        // sort all apps + add new if they are added after user sorting
        // but check first if user has apps order preference in his settings
        if (!empty($this->preferences->apps) && is_array($this->preferences->apps)) {

            foreach ($this->preferences->apps as $app_id) {
                if ($app_id == 99) {
                    $_SESSION['apps'][99] = "logout";
                } else if (!empty($temp_arr[$app_id])) {
                    $_SESSION['apps'][$app_id] = $temp_arr[$app_id];
                }
            }

            foreach ($temp_arr as $app) if (empty($_SESSION['apps'][$app->id])) $_SESSION['apps'][$app->id] = $app;

        // there are no apps in user settings
        } else {
            foreach ($selection as $db_app) {
                if (isset($_SESSION['apps']) && count($_SESSION['apps']) == 2) {
                    $_SESSION['apps'][99] = "logout"; // + add a logout app
                    $_SESSION['apps'][$db_app->id] = $db_app;
                } else {
                    $_SESSION['apps'][$db_app->id] = $db_app;
                }
            }
            ksort($_SESSION['apps']);
        }

        // set permissions
        $user = $db->select("*",$this->table, array("WHERE id = ?", array('id' => $this->id), array('varchar')));

        $_SESSION['fullname'] = $user[0]->fullname;

        //Get user groups
        $_SESSION['user_groups']=array();

        $selection = $db->select("g.id","system_user_groups g",array("WHERE t.user_id= ?", array('user_id' => $this->id), array('integer')), array('joins' => "LEFT JOIN system_user_group_links t ON g.id=t.group_id"));

        foreach($selection as $item) $_SESSION['user_groups'][] = $item->id;

        $this->user_groups = $_SESSION['user_groups'];

        $this->master_admin = in_array(0,$this->user_groups);

        $this->_set_user_permissions();

        // check permissions for apps
        foreach($_SESSION['apps'] as $key => $app) {
            if ($key != 99 && !$this->{$app->path . "index.php"} && (!empty($this->master_admin) && $app->path != 'app_owncloud/')) unset($_SESSION['apps'][$key]);
        }

        $this->_create_permissions_array();

        // build a full menu structure for each app (used in the apps dropdown)
        foreach ($_SESSION['apps'] as $key => $value){
            if ($key != 99 && empty($value->front_end_app)) { // exclude logout "app"

                $_SESSION['apps'][$key]->menu = array();

                // check permissions for each link
                $user1 = $this;
                require $cfg["source_root"] . $value->path . "includes/admin_menu.php";
                foreach ($links as $page) {
                    if ( $this->{$value->path . $page[0]} || (!empty($this->master_admin) && $app->path != 'app_owncloud/')) $_SESSION['apps'][$key]->menu[$page[1]] = $value->path . $page[0];
                }

            }
        }
        
        if ($user[0]->password_changed_by_sa == '1'){
            $_SESSION['password_changed_by_sa'] = '1';
            //$_SESSION['redirect_page'] = encrypt_url($cfg['root'] . "expired_password.php?id=" . $this->id);
            $token_time = strtotime("+ 1 day" );
            $http_args = array('user_id' => $this->id, 'token_time' => $token_time);
            $password_link = encrypt_url($cfg['root'] ."new_user_setup.php?" . http_build_query($http_args));
            $_SESSION['redirect_page'] = $password_link;
        }
        return;
    }

    function _create_permissions_array(){
        global $db, $cfg;

        //Create site map on login for side panel and 404 pages
        $xml = '
                                                            <ul class="apps">';

        // first, build the apps array
        $apps = $db->select("id, name, path, image, front_end_app", "system_apps", array("WHERE active = 1",array(),array()));

        foreach ($apps as $app_path) {

            if (!empty($this->{$app_path->path . "index.php"}) && empty($app_path->front_end_app)) {

                include ($cfg['source_root'] . $app_path->path . "includes/admin_menu.php");

                $subxml = '';

                foreach ($links as $key => $value) {

                    // if the user has permissions for that file in SESSION
                    if (!empty($this->{$app_path->path . $value[0]})) {

                        $has_pointer = (!isset($value[3])) ? '<span class="pointer">&nbsp;</span>' : '';

                        $subxml .= '
                                                                    <ul class="pages">
                                                                        <li>
                                                                            <a href="'.encrypt_url($cfg["root"].$app_path->path.$value[0]).'">'.$has_pointer.' '.$value[1].'</a>';

                        // build all the links with the different tabs from admin_menu.php - user has permissions for all the tabs
                        if (isset($value[3])) {
                            $subxml .= '
                                                                            <ul class="tabs">';
                            foreach ($value[3] as $tab => $tab_name) $subxml .= '
                                                                                <li>
                                                                                    <a href="'.encrypt_url($cfg["root"].$app_path->path.$value[0].'?tab='.$tab).'">
                                                                                        <span class="pointer">&nbsp;</span>
                                                                                        '.$tab_name.'
                                                                                    </a>
                                                                                </li>';
                            $subxml .= '
                                                                            </ul>';
                        }

                        $subxml .= '
                                                                        </li>
                                                                    </ul>';
                    }
                }

                if (!empty($subxml)) {
                    $xml .= '
                                                                <li>
                                                                    <a href="'.encrypt_url($cfg["root"].$app_path->path."index.php") .'">'.$app_path->name.'</a>
                                                                    '.$subxml.'
                                                                </li>';
                }
            }
        }

        $xml .= '
                                                            </ul>';
        $cache = new Cache(array('user_id'=>$this->id));
        $cache->delete_cache('sitemap_tree');
        $cache->cache_content('sitemap_tree', $xml, 24*60*60);
    }

    private function _set_user_permissions(){
        global $db;
        if (!$this->master_admin) {
            //If not Master Admin get all group permissions and write them in

            $permissions = $db->select_distinct(
                "p.page",
                "system_pages p LEFT JOIN system_user_group_permissions per ON p.id=per.page_id LEFT JOIN system_user_group_links l ON l.group_id=per.group_id",
                array(
                    "WHERE l.user_id=? OR p.core=1",
                    array($this->id),
                    array('integer')
                )
            );

            foreach ($permissions as $p) $this->{$p->page} = 1;

        } else {
            // Give Master Admin full control of all User Admin app files + ownCloud browser
            $all_pages = $db->select("page, core","system_pages", array());
            foreach ($all_pages as $p) {
                // Admin app files: basename = name
                if (basename($p->page) == $p->page
                    || $p->page == "app_owncloud/index.php"
                    || $p->page == "app_owncloud/browser.php" )
                    $this->{$p->page} = 1;
            }
        }
    }

    // Magic method to handle all non-permitted pages, rather than have a bunch of 0's in session
    function __get($name){
        if (isset($this->$name)){
            return $this->$name;
        } else {
            // error_log("Setting $name to null");
            if(!empty($name)){
                $this->$name = null;
                return null;
            }

        }
    }

    function print_send_email_form() {
        global $user1, $db, $cfg, $libhtml;

        $where = array("", array(), array());
        $where[0] = "WHERE u.email != ''";
        $left_join = "";
        $active = 0;

        if (my_post("active")){
            $active = 1;
            $where[0] .= " AND u.active = ?";
            $where[1][] = 1;
            $where[2][] = 'integer';
        }

        if (my_post("user_group")) {
            $left_join .= " LEFT JOIN system_user_group_links l ON l.user_id=u.id";
            $where[0] .= " AND l.group_id = ?";
            $where[1][] = my_post("user_group");
            $where[2][] = 'integer';
        }

        $html = $libhtml->form_start();
        $html .= open_table('100%');

        $selection = $db->select("id, name", "system_user_groups", array('WHERE id>0',array(),array()));
        $html .= $libhtml->render_form_table_row_selection("user_group", my_post("user_group"), "User Group", "user_group", $selection,"id","name",array('class'=>"self_submit"));
        $html .= $libhtml->render_form_table_row_checkbox("active", $active, "Only Active", "active", array('class'=>"self_submit"));
        $html .= $libhtml->render_form_table_row_checkbox("show_users", my_post("show_users"), "Show Users", "show_users", array('class'=>"self_submit"));
        $html .= close_table();

        $selection = $db->select_distinct("u.id, u.fullname, u.email ","system_users u", $where, array('joins' => $left_join, 'order_by' => "ORDER BY u.fullname ASC"));

        if (count($selection)>0) {

            $html .= '<div class="hint"><b>Total:</b> '.count($selection).' users.</div>';

            if (my_post("show_users")) {
                $html .= open_table();
                foreach ($selection as $item) $html .= $libhtml->render_form_table_row_checkbox("user[$item->id]", "1", $item->fullname, "user_id_$item->id");
                $html .= close_table();
            }

        } else {
            $html .= '<div class="hint"><b>No users found.</b></div>';
        }


        $html .= open_table();
        $html .= $libhtml->render_form_table_row("subject", my_request("subject"), "Subject", "subject");
        $html .= $libhtml->render_form_table_row_text("content", my_request("content"), "Content", "content", array('rows'=>15));
        $html .= close_table();

        if (count($selection)) {

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_button("send_user_email", "Send", array(
                        "send_user_email",
                        "send_user_email",
                        "Auth_User"
                    )),
                )
            );

        }

        $html .= $libhtml->form_end();
        return $html;
    }

    function send_user_email() {
        global $user1, $db, $cfg;

        // create recipient list
        $where = array("", array(), array());

        $where[0] = "WHERE u.email != ''";

        if (my_post("active")) {
            $where[0] .= " AND u.active = ?";
            $where[1][] = 1;
            $where[2][] = 'integer';
        }
        if (my_post("user_group")) {
            $where[0] .= " AND u.id IN (SELECT user_id FROM system_user_group_links WHERE group_id = ?)";
            $where[1][] = my_post("user_group");
            $where[2][] = 'varchar';
        }

        // these are unticked
        $dont_send_to = array();
        foreach(my_post("user") as $key => $value) {
            if ($value == '0') $dont_send_to[] = $key;
        }

        $all_recipients = $db->select_distinct("u.id, u.fullname, u.email ", "system_users u", $where);

        // remove the unticked from the recipients list
        $recipients = array();
        foreach($all_recipients as $item){
            if (!in_array($item->id, $dont_send_to)) {
                $recipient_details = array();
                $recipient_details['email'] = $item->email;
                $recipient_details['fullname'] = $item->fullname;
                $recipients[] = $recipient_details;
            }
        }

        $subject = (my_post("subject")) ? " - " . my_post("subject") : '';

        if (!empty($recipients)) {
            general_email(array(
                "template"=>"user_mail",
                "subject"=>$cfg['client'].' System Email' . $subject,
                "content"=>nl2br(my_post("content")),
                "user_name"=>$user1->fullname,
                "recipients"=>$recipients,
            ));

            $_SESSION["feedback"] = g_feedback("fa-envelope", "Email has been sent successfully");
            return false;
        }
    }

    function print_send_single_email_form() {
        global $user1, $db, $cfg, $libhtml;

        $html = $libhtml->form_start();
        $html .= open_table();

        $html .= $libhtml->render_form_table_row_hidden("url", $_SERVER['HTTP_REFERER']);
        $html .= $libhtml->render_form_table_row_hidden("sender", $user1->fullname);
        $html .= $libhtml->render_table_row("URL", text_toggler($_SERVER['HTTP_REFERER']));

        //Select only users that have access to my_get('page'), are active and have non empty email
        $selection = $db->select_distinct(
                "u.id, u.fullname, u.email ",
                "system_users u
                LEFT JOIN system_user_group_links l ON l.user_id=u.id
                LEFT JOIN system_user_group_permissions gp ON gp.group_id=l.group_id
                LEFT JOIN system_pages p ON p.id=gp.page_id",
                array(
                        "WHERE u.active=1 AND (u.email<>'' AND u.email IS NOT NULL) AND p.page=?",
                        array(my_get('page')),
                        array('varchar')
                ),
                array(
                        'order_by' => "ORDER BY u.fullname ASC"
                )
        );
        $html .= $libhtml->render_form_table_row_selection("user_email", my_request("user_email"), "User", "user_email", $selection,"email","fullname",array('class'=>"self_submit",'required'=>true));

        if (my_request("user_email")!='') {

            $html .= $libhtml->render_table_row("User Email", my_request("user_email"));
            $html .= $libhtml->render_form_table_row_text("content", my_request("content"), "Email message", "content", array('rows'=>5,'required'=>true));

        }

        $html .= close_table();
        if (count($selection)){

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_button("send_single_user_email", "Send", array(
                        "send_single_user_email",
                        "send_single_user_email",
                        "Auth_User"
                    )),
                )
            );

        }

        $html .= $libhtml->form_end();
        return $html;
    }

    function send_single_user_email() {
        global $user1, $db, $cfg;

        $raw_url = my_post("url");
        $short_url = (strlen($raw_url)>60) ? substr($raw_url,0,60)." ..." : $raw_url;
        $url = "<a href=\"$raw_url\">$short_url</a>";

        general_email(array(
                "template"=>"user_mail",
                "subject"=>$cfg['client'].' System Email from ' . my_post("sender"),
                "content"=>"URL: ".$url."<br/><br/>".nl2br(my_post("content")),
                "user_name"=>$user1->fullname,
                "recipients"=>array(array(
                    'email'=>my_post("user_email"),
                    'fullname'=>my_post("user_email")
                ))
        ));

        $_SESSION["feedback"] = g_feedback("fa-envelope", "Email has been sent successfully");
        return false;
    }

    function oauth_login($oauth_record,$user_profile = Array()){
        global $db,$cfg;

        if(!empty($oauth_record)){

            $selection = $db->select("*",$this->table,array("WHERE id = ?", array('id' => $oauth_record[0]->user_id), array('int')));

            if (empty($selection)) {return 1;} else {array2object($selection,$this);}

            $_SESSION['username'] = $selection[0]->username;
            $_SESSION['password'] = $selection[0]->password;
            $_SESSION['user_id'] = $selection[0]->id;

            $this->_post_login_setup();
            $this->logged_in = true;

            header('Location: '.$cfg['root']);
            die;

        } else {

            $_SESSION['oauth_error'] = 'This external account is not linked with any local account.';

            header('Location: '.$cfg['root']);
            die;
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

    function check_reset_request($userArr){
        global $db, $cfg;

        $user = new User;
        $user->select($userArr->id);
        
        $deactivated = $this->is_deactivated($user);
        
        if ($user->auth_type === 'Native'){
        //Check link
        $url = $cfg['root'] . encrypt_url("index.php?reset_user_id=".$user->id);

        //Send a check email
            /* general_email(array(
            "template"=>"login_reset",
                "subject"=>"Forgotten Password",
                "content"=>'<p>Dear '.$user->fullname.',
                    <p>We received your request for a new password for your System account.  To reset your password, please <a href="'.$url.'"><strong>click on this link</strong></a>.</p>
                    <p>If you did not request to have your password reset you can safely ignore this email.  Rest assured your System account is safe.</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>Your System Support Team</p>',
            "user_name"=>$user->username,
            "recipients"=>array(array(
                'email'=>$user->email,
                'fullname'=>$user->fullname
            ))
            )); */
            
            
            
            
            if (!empty($user->id)){
                try {
                    // Confirmation URL
                    $url = $cfg['root'] . encrypt_url("index.php?confirm_user_id=".$user->id);
                    
                    //Universal message
                    $_SESSION['login_message'] = "Reset Password Email Sent.</br>
                        An email has been sent with a link to reset your password.
                        if you do not receive an email within 10 minutes, please contact your system administrator.";
                    
                    if ($deactivated === false) {
                        $user->send_forgotten_password_email_user_activated($url);
                        
                        // Sysadmin email
                        $admin_content = '<p>User <strong>'.$user->username.'</strong>, email <strong>'.$user->email.'</strong> has requested a password reset to his email.
                An email has been sent to the user with instructions to reset their password. You do not have to do anything</p>';
                    } else {
                        // User email
                        $user->send_forgotten_password_email();
                        
                        // Sysadmin email
                        $admin_content = '<p>User <strong>'.$user->username.'</strong>, email <strong>'.$user->email.'</strong> has requested a password reset to his email.</p>';
                        if ($deactivated === true) {
                            $admin_content .= '
                        <p>The user was manually deactivated in the system on ' . $user->deactivation_time . ' for "' . $user->deactivation_reason . '".  You will need to log in to manually reactivate them or ignore it.</p>';
                        } else {
                            $admin_content .= '
                        <p>To activate this user\'s new login details please click on this <a href="'.$url.'"><strong>link</strong></a>.</p>';
                        }
                    }
                    
                    //Sysadmin email
                    general_email(array(
                        "template"=>"user_mail",
                        "subject"=>"Login reset request",
                        "content"=>$admin_content,
                        "user_name"=>"System Administrator",
                        "recipients"=>self::get_sys_admin_recipients()
        ));
                } catch (Exception $e) {
                    $this->error = 'An error has occured, please report this to the System Administrator.';
                    error_log($e->getMessage());
                    error_log($e->getTraceAsString());
                }            
            }
        }else{
            $_SESSION['login_message'] = "<strong>Cannot Reset Password</strong></br>Your user account is tied to your network login and therefore your password cannot be reset through this process.".
                "Please contact your system administrator for further assistance.";
            /*general_email(array(
                "template"=>"login_reset",
                "subject"=>"Login reset request - Please contact your IT administrator",
                "content"=>'<p>Hi,</p>
                            <p>This email is to notify that the System system received a password reset request for this account.
                               This account is an Active Directory account and System does not store nor has the ability to change password on such accounts.
                               Please contact your IT administrator to reset your Active Directory Password.</p>',
                "user_name"=>$user->username,
                "recipients"=>array(array(
                    'email'=>$user->email,
                    'fullname'=>$user->fullname
                ))
            ));*/
        } 
        
        
        

    }
    
    function send_reset_request_to_sa($user){
        global $db, $cfg;
        
        $saUser = new User;
        $SAs = $saUser->get_SA_contact_details();
        $to_addresses = array();
        if (empty($SAs)) {
            throw new Exception("No Sys Admins set cannot set email 'From' Address", 1);
        }
        foreach ($SAs as $SA) {
            $to_addresses[] = array(
                'email'=>$SA->email,
                'fullname'=>$SA->fullname
            );
        }

        if ($user->auth_type === 'Native'){
            //Send a check email
            general_email(array(
                "template"=>"login_reset",
                "subject"=>"Forgotten Password",
                "content"=>'<p>User <strong>'.$user->fullname.'</strong> with User ID <strong>'.$user->username.'</strong> has requested to reset his/her System password. '.
                'Please reset his/her password and let the user know when done.</p>',
                "user_name"=>$user->username,
                "recipients"=>$to_addresses
            ));
        }else{
            general_email(array(
                "template"=>"login_reset",
                "subject"=>"Forgotten Password",
                "content"=>'<p>Hi,</p>'.
                '<p>User <strong>'.$user->fullname.'</strong> with User ID <strong>'.$user->username.'</strong> has requested to reset his/her System password.</p>'.
                '<p><strong>BUT...</strong> This user&apos;s System account is tied to his/her network login and therefore you cannot reset the password using the standard'.
                ' System Password Reset process.  Instead, the user&apos;s network login password must be reset if indeed he/she forgot the password. '.
                'Please provide instructions for the user to reset his/her network password using your standard network password change process.</p><p>Regards,</br>Your System Support Team</p>',
                "user_name"=>$user->username,
                "recipients"=>$to_addresses
            ));            
    }
    }

    function is_deactivated($user)
    {
        if (!empty($user->deactivation_time)) {
            return true;
        }
        return false;
    }

    function isPasswordCreated($user)
    {
        if (!empty($user->password)) {
            return true;
        }
        return false;
    }

    function reset_user_id(){
        global $db, $cfg, $crypt;

        $user = new User;
        $user->select(my_get("reset_user_id"));

        $deactivated = $this->is_deactivated($user);

        if (!empty($user->id)){
            try {
                // deactivate user account and wipe out the password
                $db->update(
                        "system_users",
                        array(
                                'active'=>'0',
                                'failed_login'=>'0',
                                'password'=>null
                        ),
                        array(
                                "WHERE id = ?",
                                array('id'=>$user->id),
                                array('integer')
                        )
                );

                // Confirmation URL
                $url = $cfg['root'] . encrypt_url("index.php?confirm_user_id=".$user->id);

                //Universal message
                $_SESSION['login_message'] = "Password reset is now confirmed: please check your email for further instructions.";

                if ($deactivated === false) {
                    $user->send_forgotten_password_email_user_activated($url);

                    // Sysadmin email
                    $admin_content = '<p>User <strong>'.$user->username.'</strong>, email <strong>'.$user->email.'</strong> has requested a password reset.</p>';
                } else {
                    // User email
                    $user->send_forgotten_password_email();

                    // Sysadmin email
                    $admin_content = '
                    <p>User <strong>'.$user->username.'</strong>, email <strong>'.$user->email.'</strong> has requested a password reset.</p>';
                    if ($deactivated === true) {
                        $admin_content .= '
                        <p>The user was manually deactivated in the system on ' . $user->deactivation_time . ' for "' . $user->deactivation_reason . '".  You will need to log in to manually reactivate them or ignore it.</p>';
                    } else {
                        $admin_content .= '
                        <p>To activate this user\'s new login details please click on this <a href="'.$url.'"><strong>link</strong></a>.</p>';
                    }
                }

                //Sysadmin email
                general_email(array(
                    "template"=>"user_mail",
                    "subject"=>"Login reset request",
                    "content"=>$admin_content,
                    "user_name"=>"System Administrator",
                    "recipients"=>self::get_sys_admin_recipients()
                ));
            } catch (Exception $e) {
                $this->error = 'An error has occured, please report this to the System Administrator.';
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }

        }

    }

    function confirm_user_id(){
        global $db, $cfg;

        $where = array("WHERE id = ?", array('id'=>my_get("confirm_user_id")), array('integer'));

        if ($db->update("system_users", array('active'=>1,'failed_login'=>0), $where)) {

            $selection = $db->select('email,username,fullname,is_sa','system_users',$where);
            $_SESSION['login_message'] = "User access has now been activated.";

            //Send email only if user is not SA
            if (empty($selection[0]->is_sa)) {
                //User email
                general_email(array(
                    "template"=>"user_mail",
                    "subject"=>"Login reset request - request confirmed",
                    "content"=>'Your request has been confirmed by the System Administrator. Please follow the instructions in the email regarding setting your password if you have not already or login using the details you set.',
                    "recipients"=>array(array(
                        'email'=>$selection[0]->email,
                        'fullname'=>$selection[0]->fullname
                    ))
                ));
            }


        }

    }

    public function new_user_password($user_id, $raw_password)
    {
        global $db, $crypt, $cfg;
        try {
            $user = new User();
            $user->select($user_id);
            $this->id = $user->id;
            $crypted_password = $crypt->bcrypt($raw_password);

            $deactivated = $this->is_deactivated($user);
            $passwordCreated = $this->isPasswordCreated($user);
            $updatedTxt = 'updated';
            $failMessage = "Password change failed.";
            if ($passwordCreated === false){
                $updatedTxt = 'created';
                $failMessage = "Password create failed.";
            }


            $update_array = array(
                'password_changed_by_sa'=>null
            );
            if ($deactivated === false) {
                $user->active = 1;
                $update_array['active'] = 1;
            }

            if ($this->same_as_previous_passwords($raw_password) === true) {
                $_SESSION['login_message'] = 'Cannot use any of the last ' . $this->system_settings['Password Expiry: Previous Passwords'] . ' passwords.';
                return;
            }else{
                if ($this->update_password($user_id, $crypted_password)){
                    $db->update("system_users", $update_array, array("WHERE id = ?", array('id' => $user_id), array('integer')));
                }else{
                    $_SESSION['login_message'] = $failMessage;
                    return;
                }
            }
            
                
            unset($_SESSION['password_changed_by_sa']);
            $subject = "User password " . $updatedTxt;
            if ($user->active == 1) {
                $_SESSION['login_message'] = "You can now login with your new password.";
                $admin_content = '<p>User <strong>' . $user->username . '</strong>, email <strong>' . $user->email . '</strong> has ' . $updatedTxt . ' their password.</p>';
                
            } else {
                $_SESSION['login_message'] = "Please contact an Admin to activate your account.";
                if ($deactivated === true) {
                    $subject = "Deactivated user attempting to reset password";
                    $admin_content = '<p>User <strong>' . $user->username . '</strong>, email <strong>' . $user->email . '</strong> has ' . $updatedTxt . ' their password.  They were manually deactivated for "' . $user->deactivation_reason . '". You must manually activate the user in order for them to login but please pay attention to the reason they were deactivated.</p>';
                } else {
                    $admin_content = '<p>User <strong>' . $user->username . '</strong>, email <strong>' . $user->email . '</strong> has ' . $updatedTxt . ' their password.  You must manually activate the user.</p>';
                }
            }

            //Sysadmin email
            general_email(
                array(
                    "template" => "user_mail",
                    "subject" => $subject,
                    "content" => $admin_content,
                    "user_name" => "System Administrator",
                    "recipients" => self::get_sys_admin_recipients()
                )
            );
        } catch (Exception $e) {
            $this->error = 'An error has occured, please report this to the System Administrator.';
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
    }
    
    
    public function reset_sa_changed_password($user_id, $raw_password)
    {
        global $db, $crypt, $cfg, $my_post;
        try {
            $user = new User;
            $user->select($user_id);
            $crypted_password = $crypt->bcrypt($raw_password);
            
            $deactivated = $this->is_deactivated($user);
            $update_array = array(
                'active' => $user->active,
                'password_changed_by_sa'=>null
            );
            
            if ($deactivated === false) {
                $user->active = 1;
                $update_array['active'] = 1;
            }
            
            $db->update(
                "system_users",
                $update_array,
                array(
                    "WHERE id = ? AND password IS NULL",
                    array('id' => $user_id),
                    array('integer')
                )
                );
            
            $this->id = $user_id;
            $this->password = $crypted_password;
            $isUpdated = $this->update_password();
            unset($_SESSION['password_changed_by_sa']);
            
            if ($isUpdated){
            $subject = "User password creation";
            if ($user->active == 1) {
                $admin_content = '<p>User <strong>' . $user->username . '</strong>, email <strong>' . $user->email . '</strong> has created their password.</p>';
                    // set these so can be used in check post
                    $my_post['login']['login_username'] = $user->username;
                    $my_post['login']['login_password'] = $raw_password;
                    
                    $this->_check_post();
                    // redirect
                    //Sysadmin email
                    general_email(
                        array(
                            "template" => "user_mail",
                            "subject" => $subject,
                            "content" => $admin_content,
                            "user_name" => "System Administrator",
                            "recipients" => self::get_sys_admin_recipients()
                        )
                        );
                    $this->redirect_to_index_page();
            } else {
                    $_SESSION['login_message'] = "Your user account is deactivated. Please contact an Admin to activate your account.";
                if ($deactivated === true) {
                    $subject = "Deactivated user attempting to reset password";
                    $admin_content = '<p>User <strong>' . $user->username . '</strong>, email <strong>' . $user->email . '</strong> has created their password.  They were manually deactivated for "' . $user->deactivation_reason . '". You must manually activate the user in order for them to login but please pay attention to the reason they were deactivated.</p>';
                } else {
                    $admin_content = '<p>User <strong>' . $user->username . '</strong>, email <strong>' . $user->email . '</strong> has created their password.  You must manually activate the user.</p>';
                }
            //Sysadmin email
            general_email(
                array(
                    "template" => "user_mail",
                    "subject" => $subject,
                    "content" => $admin_content,
                    "user_name" => "System Administrator",
                    "recipients" => self::get_sys_admin_recipients()
                )
            );
                }
                
                
            }else{
                $_SESSION['login_message'] = 'An unexpected error occurred please report this with the date '. date('Y-m-d H:i:s') . '.';
                $this->redirect_to_index_page();
                return;
            }
        } catch (Exception $e) {
            $this->error = 'An unexpected error occurred please report this with the date '. date('Y-m-d H:i:s') . '.';
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
    }

    public static function get_sys_admin_recipients()
    {
        global $db;

        // Get Sys Admins from DB.
        $sys_admins = $db->select('email,fullname', 'system_users', array('WHERE is_sa = ? AND active = ?', array(1, 1), array('integer', 'integer')));

        $recipients = array();

        foreach ($sys_admins as $sys_admin) {
            $recipients[] = array(
                    'email'=>$sys_admin->email,
                    'fullname'=>$sys_admin->fullname,
            );
        }

        return $recipients;
    }
}
