<?php

    require_once dirname(__FILE__).'/config/global.php';

    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $libhtml = new Libhtml(array(
        'public'=>true
    ));

    $html .= '<div class="login_prompt f_password">';
        require_once $cfg['source_root'] . "includes/session_handler.php";

        if (my_post("expired_password")) {

            $securimage = new Securimage;
            $securimage->session_name = session_name();
            $valid = $securimage->check(my_post("code"));

            if (my_post("code")==""
             || $my_post["existing_password"]==""
             || $my_post["login_password"]==""
             || $my_post["confirm_password"]=="") {

                $user1->error = '<p class="error">You must enter passwords, and the validation code.</p>';

            } elseif ($valid == false) {

                $user1->error = '<p class="error">The validation code you entered did not match the image.</p>';

            } else {
                $user1->update_expired_password();
            }
        }

        if (isset($user1->error) && !empty($user1->error)) {
            $html .= '<p class="error">'.$user1->error.'</p>';
            unset($user1->error);

        }

        if (!empty($_SESSION['login_message'])) {
            $html .= '<p class="info">' . $_SESSION['login_message'] . '</p>';
            unset($_SESSION['login_message']);
        }

        $html .= '<form method="post" action="'. $cfg["root"] .'expired_password.php">
            <table>
                <tr>
                    <td>
                        <label for="login_username">Username:</label>
                        <input type="text" name="login_username" id="login_username" value="';
                            if (isset($user1->username)) $html .= $user1->username;
                        $html .= '"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="existing_password">Current Password:</label>
                        <input type="password" name="existing_password" id="existing_password" value=""/>
                    </td>
                </tr>
                <tr class="first">
                    <td>
                        <label for="login_password">New Password:</label>
                        <input type="password" name="login_password" id="login_password" value=""/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" value=""/>
                    </td>
                </tr>
                <tr><td><label for="code">Enter code:</label><input type="text" name="code" id="code" class="required" value="" autocomplete="off"/></td></tr>
                <tr><td>
                    <a class="reload" href="' . encrypt_url($cfg["root"]."expired_password.php") . '" title="Refresh Image">Reload Image</a>
                    <div class="captcha">
                        <img id="siimage" style="border: 1px solid #E9E9E9;" src="' . $cfg["root"] .'show_captcha.php" />
                    </div>
                </td></tr>
                <tr class="actions">
                    <td>
                        <input name="expired_password" type="submit" value="Update" class="submit expired_password" />
                    </td>
                </tr>
            </table>
        </form>';

    $html .= '</div>';
    $libhtml->render($html);
