<?php


    if ( !file_exists(dirname(__FILE__).'/config/global.php') ) {
        error_log("Please create a global.php file at location: " . dirname(__FILE__) . "/config/global.php");
        include_once(dirname(__FILE__) . "/missing_config_file.html");
        die;
    } else {
        require_once dirname(__FILE__).'/config/global.php';
    }

    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $_SESSION['feedback'] = "";

    //Password reset functions

    if (my_get("reset_user_id")!='') $user1->reset_user_id();
    if (my_get("confirm_user_id") != '') {
        $user1->confirm_user_id();
    }

    if ($user1->logged_in) {

        if (my_post('login')) {

            //If there is a remembered page
            if (isset($_SESSION['redirect_page'])) {
                header("Location: " . $user1->universal_redirect($_SESSION['redirect_page']));
                exit;

                // Or first place permitted
            }elseif (!empty($user1->preferences->landpage)) {

                //error_log("Redirecting to users landpage");
                header("Location: " . $user1->universal_redirect($user1->preferences->landpage));
                exit;

            //Or first place permitted
            } else {

                //error_log("Redirecting to first permitted page which is not in Admin app");
                header("Location: " . $user1->universal_redirect());
                exit;
            }

           } else {

               // This is just a click on admin panel app or a first call
            // check if admin app is disabled and redirect to first other app which is not
            if (!empty($_SESSION["apps"][1]->menu) && $user1->{"index.php"}) {

                //error_log("This is first permitted admin panel page");
                foreach ($_SESSION["apps"][1]->menu as $link) {
                    if ((isset($user1->$link) && ($user1->$link))) {
                        header("Location: " . encrypt_url($cfg["root"].$link));
                        exit;
                    }
                }

            } else {

                //error_log("Redirecting to first permitted page");
                header("Location: " . $user1->universal_redirect());
                exit;

            }
           }

    } else {

        // If not logged in and not referred from forgotten password page, regenerate session id to ensure clean start
        if (
            !isset($_SESSION['oauth_error'])
            && !isset($_GET["expired_session"])
            && (isset($_SERVER['HTTP_REFERER'])
            && $_SERVER['HTTP_REFERER']!=$cfg['root'].'forgotten_password/')
        ) {

            //session_regenerate_id(true);
            //error_log('Regenerate Session ID:'.session_id());

        }

    }

    $show_forgotten_password_link = true;
    if (isset($_SESSION['LDAP_Enabled']) && $_SESSION['LDAP_Enabled'] === true) {
        $show_forgotten_password_link = false;
    }

    $libhtml = new Libhtml(array(
        'public'=>true
    ));

    $html .= '<div class="login_prompt">';

        require_once $cfg['source_root'] . "includes/session_handler.php";

        if (isset($user1->error) && !empty($user1->error)) {
            $html .= '<p class="error">'.$user1->error.'</p>';
            unset($user1->error);

        } else if (isset($_GET["expired_session"])) {
            $html .= '<p class="error">Your session may have expired. Please log in.</p>';

        } else if (isset($_SESSION['oauth_error'])) {
            $html .= '<p class="error">'.$_SESSION['oauth_error'].'</p>';
            unset($_SESSION['oauth_error']);

        }

        if (!empty($_SESSION['login_message'])) {
            $html .= '<p class="info">' . $_SESSION['login_message'] . '</p>';
            unset($_SESSION['login_message']);
        }

        $html .= '<form method="post" action="'. $cfg["root"] .'">
            <table>
                <tr>
                    <td>
                        <label for="login_username">Username:</label>
                        <input type="text" name="login[login_username]" id="login_username" value=""/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="login_password">Password:</label>
                        <input type="password" name="login[login_password]" id="login_password" value=""/>
                    </td>
                </tr>';

                if (!empty($cfg['remember_me'])) {
                    $html .= '<tr>
                        <td>
                            <label for="remember">Remember login:</label>
                            <input class="checkbox" type="checkbox" name="login[remember]" id="remember"/>
                        </td>
                    </tr>';
                }

                $forgotten_password_link = '';
                if ($show_forgotten_password_link === true) {
                    $forgotten_password_link = '<a href="'. $cfg["root"] .'forgotten_password/">Forgot your username or password?</a>';
                }

                $html .= '<tr class="actions">
                    <td>' . $forgotten_password_link . '
                        <input name="page_login" type="submit" value="Login" class="submit login" />
                    </td>
                </tr>
            </table>
        </form>';

        if (!empty($cfg['login_cookie_expiry'])) {
            $hours = ($cfg['login_cookie_expiry'] == 3600) ? "1 hour" : ($cfg['login_cookie_expiry'] / 3600) . " hours";
        } else {
            $hours = "24 hours";
        }

         // $html .= '<p class="info cookie">After a successful login this page will save a cookie valid for '.$hours.'. It will keep you logged in unless you log out deliberately.</p>';

        if (!empty($cfg['oauth_enable'])) {
            $html .= '<div class="oid">
                <p class="first">Login using</p>
                <a href="' . encrypt_url($cfg['root'].'openid.php?type=Google&action=login') .'" class="g"><i class="fa fa-google"></i></a>
                <p>or</p>
                <a href="' . encrypt_url($cfg['root'].'openid.php?type=Twitter') .'" class="t"><i class="fa fa-twitter"></i></a>
                <p>OpenID instead?</p>
            </div>';
        }

    $html .= '</div>';
    $libhtml->render($html);
