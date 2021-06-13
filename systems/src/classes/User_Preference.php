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
class User_Preference extends Object {

    public $table = "system_users";
    public $left_join = "";
    public $other_selects = "";
    public $object_pk = "id";

    private function inner_password_form()
    {
        global $libhtml;
        $html = '';
        $html .= $libhtml->render_form_table_row_password("user_preference[old_password]", "Old Password", "old_password");
        $html .= $libhtml->render_form_table_row_password("user_preference[new_password]", "New Password", "new_password");
        $html .= $libhtml->render_form_table_row_password("user_preference[new_password2]", "Retype New Password", "new_password2");
        $html .= table_separator();
        return $html;
    }
    function print_update_form() {
        global $cfg, $db, $libhtml, $user1;
        $native_user = $user1->is_native_user();

        $ftabs = array("Email", "Display");
        $button_text = "Update Email";
        if ($native_user === true) {
            $ftabs[0] = "Password & Email";
            $button_text = "Update Password & Email";
        }

        $html = open_form_tabs(array("tabs"=>$ftabs));

        $html .= $libhtml->form_start();
        if (!empty($cfg['password_message']) && $native_user === true) $html .= '<div class="hint">'.$cfg['password_message'].'</div>';

        $html .= open_table();
        if ($native_user === true) {
            $html .= $this->inner_password_form();
        }
        $html .= $libhtml->render_form_table_row("user_preference[email]", $user1->email, "Email", "email", array("class"=>"email", "unique"=>true, "unique_id"=>$user1->id));
        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("update_password", $button_text, array("user_preference","update_password","User_Preference"))
            ),
            array(
                "pause"=>false
            )
        );

        $html .= form_tab();
        $html .= open_table();

        $page_opts = array(20, 50, 100);
        $date_opts = array("jS F Y","d F Y","d F y","D d F y","d M Y","d M y","D d M y","d m Y","d m y","D d m y","d.m.Y","d.m.y","Y-m-d","\W\e\e\k W Y","Y");
        $popup_size_opts = array("Dynamic", "Always maximised", "Always compact");

        $html .= $libhtml->render_form_table_row_selection("user_preference[pagination]", $user1->preferences->pagination, "Pagination", "pagination", $page_opts,"","",array('allowed_empty'=>false));
        foreach($date_opts as $d) $date_array[] = (object) array('format' => $d,'date' => date($d, time()));
        $html .= $libhtml->render_form_table_row_selection("user_preference[dateformat]", $user1->preferences->dateformat, "Date Format", "dateformat", $date_array, "format", "date",array('allowed_empty'=>false));
         if (class_exists('DateTimeZone')) {
            $timezone_identifiers = DateTimeZone::listIdentifiers();
            $timezones = array();
            foreach ($timezone_identifiers as $timezone) $timezones[] = (object) array('timezone' => $timezone);
            $html .= $libhtml->render_form_table_row_selection("user_preference[timezone]", $user1->preferences->timezone, "Time Zone", "timezone", $timezones, "timezone", "timezone");
        }

        $html .= table_separator();
        $html .= $libhtml->render_form_table_row_selection("user_preference[popup_size]", $user1->preferences->popup_size, "Popup size", "popup_size", $popup_size_opts,"","",array('allowed_empty'=>false));
        $html .= $libhtml->render_form_table_row_checkbox("user_preference[shortcuts_menu]", $user1->preferences->shortcuts_menu, "Show shortcuts menu", "shortcuts_menu", array("tooltip"=>"Quickly access all object's tabs by hovering over the link in the list"));
        $html .= $libhtml->render_form_table_row_checkbox("user_preference[disable_rc_menu]", $user1->preferences->disable_rc_menu, "Disable right click menu", "disable_rc_menu");
        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("update_display", "Update Display", array("user_preference","update_display","User_Preference"))
            ),
            array(
                "pause"=>false
            )
        );

        $html .= close_table();
        return $html;
    }

    function update_preferences($variables=array()){
        global $db, $user1;

        foreach ($variables as $variable){
            if (isset($this->$variable)){
                $user1->preferences->$variable = $this->$variable;
            }
        }

        $db->update(
                "system_users",
                array('preferences' => json_encode($user1->preferences)),
                array("WHERE id = ?", array('id' => $user1->id), array('integer'))
        );
        return $db->rows;
    }

    function update_display() {

        $this->update_preferences(array(
            "pagination",
            "dateformat",
            "timezone",
            "font",
            "fsize",
            "popup_size",
            "shortcuts_menu",
            "disable_rc_menu",
        ));

        $_SESSION['feedback'] .= g_feedback('fa-television', "User display preferences successfully updated.");
        return false;

    }

    function change_size() {

        return $this->update_preferences(array("width","height"));

    }

    function update_screen() {

        return $this->update_preferences(array("optimal_width","optimal_height"));

    }

    function update_password() {
        global $db, $user1, $cfg, $crypt;
        $success = false;

        if (isset($cfg['password'])) {
            $preg_match = $cfg['password'];
            $preg_message = $cfg['password_message'];
        } else {
            $preg_match = "/^.*/";
            $preg_message = "";
        }

        if (($crypt->bcrypt_verify($this->old_password, $_SESSION['password'])) && !empty($this->new_password) && ($this->new_password == $this->new_password2)) {

            if (!preg_match($preg_match, $this->new_password)) {

                $_SESSION['feedback'] .= g_feedback("error", $preg_message);

            } elseif ($user1->same_as_previous_passwords($this->new_password) === true) {
                $_SESSION['feedback'] .= g_feedback("error", 'Cannot use any of the last ' . $user1->system_settings['Password Expiry: Previous Passwords'] . ' passwords.');
                return;
            } else {

                $new_password = $crypt->bcrypt($this->new_password);
                $old_pass = $_SESSION['password'];

                $user1->password = $new_password;
                if ($user1->update_password()) {

                    $_SESSION['password'] = $new_password;

                    if (class_exists("Secure_Diary")){
                        $secure_diary = new Secure_Diary();
                        $secure_diary->re_encode($user1->person_id,$old_pass, $this->password);
                    }

                    $success = true;
                    $_SESSION['feedback'] .= g_feedback("success", "User password successfully updated.");
                } else {
                    $user1->password = $old_pass;
                    $_SESSION['feedback'] .= g_feedback("error", "Unexpected issue changing password.");
                }

            }

        } else {

            if (!empty($this->email)) {

                if (IsEmail($this->email)) {

                    if ($this->email!=$user1->email) {
                        $db->update("system_users", array('email'=>$this->email), array("WHERE id = ?", array('id' => $user1->id), array('integer')), $this->table_types);
                        $success = true;
                        $_SESSION['feedback'] .= g_feedback("success", "User email successfully updated.");
                    }

                } else {

                    $_SESSION['feedback'] .= g_feedback("error", "Incorrect email format, user data not updated.");

                }
            }

            if (empty($this->old_password) && empty($this->new_password)) {

                $_SESSION['feedback'] .= g_feedback("error", "Passwords not updated.");

            } else {

                $_SESSION['feedback'] .= g_feedback("error", "Problem with inputs, password not updated.");
            }
        }

        return $success;
    }

}
