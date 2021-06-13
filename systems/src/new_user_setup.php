<?php
/**
 * This file is part of the Riskpoint Framework Software.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Riskpoint/Core
 * @subpackage Core
 * @license http://opensource.org/licenses/MIT MIT
 */

 if ( !file_exists(dirname(__FILE__).'/config/global.php') ) {
     error_log("Please create a global.php file at location: " . dirname(__FILE__) . "/config/global.php");
     include_once(dirname(__FILE__) . "/missing_config_file.html");
     die;
 } else {
     require_once dirname(__FILE__).'/config/global.php';
 }

require_once $cfg['source_root'] . "includes/common_plain_includes.php";

$user_id = my_get('user_id', '');
$token_time = my_get('token_time', '');
$error = '';

$now = strtotime("now");
if (empty($token_time) || $token_time < $now) {
    $_SESSION['login_message'] = "The time to use this has expired.";

    header("Location: ".$cfg['root']);
    exit;
}

$user = new User();
$user->where = array('WHERE id = ?', array($user_id), array('integer'));

$libhtml->public = true;

if (empty($user->_get())) {
    $html .= '<p class="error">An error occurred with this link.  Please report it.</p>';
    $libhtml->render($html);
    exit;
}

if (my_post("setup_new_user")) {
    $securimage = new Securimage;

    $valid = $securimage->check(my_post("code"));

    if ((my_post("code")=="") || (my_post("new_password")=="")) {
        $error = '<p class="error">You must enter the new password, and the validation code.</p>';
    } elseif (my_post('new_password') != my_post('confirm_password')) {
        $error = '<p class="error">New Password and Confirm Password must be the same.';

    //Password must conform to $cfg preg_match pattern
    } elseif (!empty(my_post('new_password'))
        && isset($cfg['password'])
        && isset($cfg['password_message'])
        && !preg_match($cfg['password'], my_post('new_password'))
    ) {
        $error = '<p class="error">' . $cfg['password_message'] . '</p>';
    } elseif ($valid == false) {
        $error = '<p class="error">The validation code you entered did not match the image.</p>';
        
    }elseif (isset($_SESSION['password_changed_by_sa'])){
        if ($user1->same_as_previous_passwords(my_post('new_password')) === true) {
            $error = '<p class="error">Cannot use any of the last ' . $user1->system_settings['Password Expiry: Previous Passwords'] . ' passwords.</p>';            
    } else {
            $user1->reset_sa_changed_password($user_id, my_post('new_password'));
            exit;
        }
    } else {
        $user1->new_user_password($user_id, my_post('new_password'));

        header("Location: ".$cfg['root']);
        exit;
    }
}

$http_args = array('user_id' => $user_id, 'token_time' => $token_time);
$post_action = encrypt_url($cfg["root"]."new_user_setup.php?" . http_build_query($http_args));

$hint_message = $cfg['password_message'];
$password_regex = trim($cfg['password'], '/');

if (isset($_SESSION['password_changed_by_sa']) && $_SESSION['password_changed_by_sa'] == 1){
    $hint_message = 'Your password has been reset by the admin. Please update it.</br>'.$hint_message;
}


$hint = '<div class="hint">'.$hint_message.'</div>';
$html .= '
    <div class="login_prompt f_password">
        '.$hint . $error.'
        <form method="post" action="'. $post_action.'">
            <table>
                <tr class="first">
                    <td>
                        <label for="ident">New Password:</label>
                        <input type="password" name="new_password" id="ident" class="required" required pattern="' . $password_regex . '" title="Value format you have entered is not valid" value=""/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="ident2">Confirm Password:</label>
                        <input type="password" name="confirm_password" id="ident2" class="required" required pattern="' . $password_regex . '" title="Value format you have entered is not valid" value=""/>
                    </td>
                </tr>
                <tr><td><label for="code">Enter code:</label><input type="text" name="code" id="code" class="required" required value="" autocomplete="off"/></td></tr>
                <tr><td>
                    <a class="reload" href="' . $post_action . '" title="Refresh Image">Reload Image</a>
                    <div class="captcha">
                        <img id="siimage" style="border: 1px solid #E9E9E9;" src="' . $cfg["root"] .'show_captcha.php" />
                    </div>
                </td></tr>
                <tr class="actions"><td>
                <input name="setup_new_user" type="submit" value="Submit" class="login submit" /></td></tr>
                <tr class="border_less_actions"><td><a href="'. $cfg["root"] .'">Cancel</a>
                </td></tr>
            </table>
        </form>
    </div>';

$libhtml->render($html);
