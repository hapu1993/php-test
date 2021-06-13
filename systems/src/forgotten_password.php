<?php
    if ( !file_exists(dirname(__FILE__).'/config/global.php') ) {
        error_log("Please create a global.php file at location: " . dirname(__FILE__) . "/config/global.php");
        include_once(dirname(__FILE__) . "/missing_config_file.html");
        die;
    } else {
        require_once dirname(__FILE__).'/config/global.php';
    }

    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    if ($user1->logged_in) {

        header("Location: ".$cfg['root']);
        exit;

    } else {
        if (isset($_SESSION['LDAP_Enabled']) && $_SESSION['LDAP_Enabled'] === true) {
            $libhtml = new Libhtml(array(
                'public'=>true
            ));
            $html = '
            <div id="article">
                <h1>Functionality unavailable</h1>
                <div>
                    <p>Forgotten password disabled as AD/LDAP authentication in use.</p>
                    <p>&mdash; Server Admin</p>
                </div>
            </div>';
            $libhtml->render($html);
            exit;
        }

        $user1->error = '';
        $user = null;
        if (my_post("request_new_login") || my_post("request_reset_login")) {

            $securimage = new Securimage;
            $securimage->session_name = session_name();
            $valid = $securimage->check(my_post("code"));

            if ((my_post("code")=="") || (my_post("ident")=="")) {

                $user1->error = '<p class="error">You must enter a username or email, and the validation code.</p>';

            } elseif ($valid == false) {

                $user1->error = '<p class="error">The validation code you entered did not match the image.</p>';

            } else {

                // username/email not empty and validation code ok
                $user = $db->select(
                    "id,username,fullname,email, auth_type",
                    "system_users",
                    array(
                        "WHERE (username = ? OR email = ?)",
                        array('username' => my_post("ident"), 'email' => my_post("ident")),
                        array('varchar', 'varchar')
                    )
                );


                //If unique user, send a check request email
                if (my_post("request_new_login")){
                    $_SESSION['login_message'] = "An email will be sent to you if your email or username exists in the system.";
                    if (count($user)==1 && !empty($user[0]->email)){
                        $_SESSION['login_message'] = "Reset Password Email Sent.</br>
                        An email has been sent with a link to reset your password.
                        if you do not receive an email within 10 minutes, please contact your system administrator.";
                        $user1->check_reset_request($user[0]);                        
                    }
                }elseif(my_post("request_reset_login")){
                    $_SESSION['login_message'] = "An email will be sent to system administrator if your email or username exists in the system.";
                    if (count($user)==1 && !empty($user[0]->email)){
                        $user1->send_reset_request_to_sa($user[0]);
                        $_SESSION['login_message'] = "Reset Password Email Sent.</br>An email has been sent to your system administrator requesting to reset your password. ".
                            "If you do not hear from your system administrator shortly, please contact him/her directly.";
                    }
                }

                header("Location: ".$cfg['root']);
                exit;

            }

        }

    }

    $libhtml = new Libhtml(array(
        'public'=>true
    ));

    $html .= '
    <div class="login_prompt f_password">
        '.$user1->error.'
        <div class="hint">Enter your username or email, and the validation code from the image below. Then press either the Option 1 or Option 2 button below to reset your password.</div>            
        <form method="post" action="'. encrypt_url($cfg["root"]."forgotten_password.php").'">
            <table>
                
                <tr><td><label for="ident">Username or email:</label><input type="text" name="ident" id="ident" class="required" value="'.my_post("ident").'"/></td></tr>
                <tr><td><label for="code">Enter code:</label><input type="text" name="code" id="code" class="required" value="" autocomplete="off"/></td></tr>
                <tr><td>
                    <a class="reload" href="' . encrypt_url($cfg["root"]."forgotten_password.php") . '" title="Refresh Image">Reload Image</a>
                    <div class="captcha">
                        <img id="siimage" style="border: 1px solid #E9E9E9;" src="' . $cfg["root"] .'show_captcha.php" />
                    </div>
                </td></tr>
                <tr class="actions"><td>
                <input name="request_new_login" type="submit" value="Option 1:Send password reset link to my email" class="login submit top_margin" />
                </td></tr>
                <tr class="border_less_actions"><td>
                <input name="request_reset_login" type="submit" value="Option 2:Send a password reset request to the system administrator" class="login submit top_margin" /></td></tr>
                <tr class="border_less_actions"><td>
                <a href="'. $cfg["root"] .'">Cancel</a>
                </td></tr>
            </table>
        </form>
    </div>';

    $libhtml->render($html);
