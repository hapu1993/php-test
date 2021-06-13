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
class Two_Factor_Authentication extends Object {

    public $table = "lists_two_factor_auth_types";
    public $left_join = "";
    public $other_selects = "";
    public $orderby = "t.name";
    public $dir = "ASC";
    public $view_array = array();

    private $auths = array('authy' => array('live' => array('url' => 'http://api.authy.com', 'api_key' => ''),
                                            'sandbox' => array('url' => 'http://sandbox-api.authy.com', 'api_key' => ''),

                                            )
                           );
    public $use_sandbox = false;

    //Create authy user
    function create_authy_user($two_factor_auth_type, $email, $phone, $country_code) {
        global $cfg, $db, $user1;
        $authy_api = null;
        $sandbox_or_live = 'sandbox';
        if ($use_sandbox === false) $sandbox_or_live = 'live';

        if (!isset($auths['authy'][$sandbox_or_live]['api_key']) || empty($auths['authy'][$sandbox_or_live]['api_key'])) {
            throw new Exception("Told to create authy login but authy_api_key is empty or does not exist.");
        }

        $authy_api = new Authy_Api($auths['authy'][$sandbox_or_live]['api_key'], $auths['authy'][$sandbox_or_live]['url']);
        $authy_user = $authy_api->registerUser($email, $phone, $country_code); //email, cellphone, area_code
        if ($authy_user->ok()) {
            $db->update($user1->table, array('two_factor_auth_token' => $authy_user->id()), array("WHERE id = ?", array('id' => $user1->id), array("integer")));
        } else {
            $error = "";
            foreach($authy_user->errors() as $field => $message) {
                $error = "$field = $message\n";
            }
            throw new Exception($error);
        }
    }

    function verify_authy_token($token) {
        global $cfg, $db, $user1;
        $authy_api = null;
        $sandbox_or_live = 'sandbox';
        if ($use_sandbox === false) $sandbox_or_live = 'live';

        if (!isset($auths['authy'][$sandbox_or_live]['api_key']) || empty($auths['authy'][$sandbox_or_live]['api_key'])) {
            throw new Exception("Told to varify authy login but authy_api_key is empty or does not exist.");
        }

        $authy_api = new Authy_Api($auths['authy'][$sandbox_or_live]['api_key'], $auths['authy'][$sandbox_or_live]['url']);
        $verification = $authy_api->verifyToken($user1->two_factor_auth_token, $token, array("force" => "true"));
        if ($verification->ok()) {
            return 0;
        } else {
            $error = "Error verifying authy token '$token'\n";
            foreach($verification->errors() as $field => $message) {
                $error .= "$field = $message\n";
            }
            error_log($error);
            $user1->log_failed_login();
            $user1->logged_in = false;
            return 2;
        }
    }

    function generate_secret(){
        global $user1;
        return substr(strtoupper($this->base32_encode(substr(md5($user1->username),0,18))),0,-3);
    }

    function verify_ga_offline_token($token) {
        global $cfg, $db, $user1;
        $ga = new GoogleAuth();
        $secretkey = $this->generate_secret();

        if (isset($_POST['token']) && $ga->checkCode($secretkey,$token)) {
            return 0;
        } else {
            $error = "Error verifying GA token '$token'";
            error_log($error);
            $user1->log_failed_login();
            $user1->logged_in = false;
            return 2;
        }
    }

    function get_ga_offline_QR_code() {
        global $cfg, $user1;
        $html ='
                <!-- QR CODE -->
                <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.js" type="text/javascript"></script>

                <script type="text/javascript">

                    $(document).ready(function(){

                        $(".slidingDiv").hide();
                        $(".show_hide").show();

                        $(".show_hide").click(function(){
                            $(".slidingDiv").slideToggle(600);
                        });

                    });

                </script>
                <tr><td style="padding-left: 10px"><a href="#" class="show_hide">Scan QR code</a></td></tr>
                <tr class="slidingDiv"">
                    <td style="padding-left: 10px;">
                    '.$this->generate_ga_qrcode_iframe().'
                    </td>
                </tr>
                <!-- END QR CODE -->
                ';
        return $html;
    }

    function generate_ga_qrcode_iframe(){
        global $user1,$cfg;
        $secretkey = $this->generate_secret();
        return '<iframe style="padding-left: 75px;height: 230px;" src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/'.$cfg['root'].'%3Fsecret%3D'.$secretkey.'"></iframe>';
    }
    //utility functions

    // used for GA
    function base32_encode($input) {
        // Get a binary representation of $input
        $binary = unpack('C*', $input);
        $binary = vsprintf(str_repeat('%08b', count($binary)), $binary);

        $binaryLength = strlen($binary);
        $base32_characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $currentPosition = 0;
        $output = '';

        while($currentPosition < $binaryLength) {
            $bits = substr($binary, $currentPosition, 5);

            if(strlen($bits) < 5)
                $bits = str_pad($bits, 5, "0");

            // Convert the 5 bits into a decimal number
            // and append the matching character to $output
            $output .= $base32_characters[bindec($bits)];
            $currentPosition += 5;
        }

        // Pad the output length to a multiple of 8 with '=' characters
        $desiredOutputLength = strlen($output);
        if($desiredOutputLength % 8 != 0) {
            $desiredOutputLength += (8 - ($desiredOutputLength % 8));
            $output = str_pad($output, $desiredOutputLength, "=");
        }

        return $output;
    }
}
?>
