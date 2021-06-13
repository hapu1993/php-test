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

function my_get($str="void", $default=null){
    global $my_get;
    if (!isset($my_get[$str])){
        return $default;
    } else {
        return is_array($my_get[$str]) ? $my_get[$str] : trim($my_get[$str]);
    }
}

function my_post($str="void", $default=null){
    global $my_post;
    if (!isset($my_post[$str])){
        return $default;
    } else {
        return $my_post[$str];
    }
}

function my_request($str="void", $default=null){
    global $my_request;
    if (!isset($my_request[$str])){
        return $default;
    } else {
        return $my_request[$str];
    }
}

function my_file($str="void",$default=null){
    global $my_files;
    if (!isset($my_files[$str])){
        return $default;
    } else {
        return $my_files[$str];
    }
}

class Request  {

    function __construct(){
        global $cfg, $my_get, $my_post, $my_files, $my_request;

        //create purified global arrays
        $my_get = $this->make_my_get();
        $my_post = $this->make_my_post();
        $my_files = $this->make_my_files();
        $my_request = $this->make_my_request();

        //Set the new form token for page forms verification
        $_SESSION['form_token'] = md5(uniqid() . $cfg['md5_salt']);
    }

    function execute_post_functions(){
        global $db, $cfg, $my_post, $my_get, $user1, $crypt, $libhtml;

        $max_time = (!empty($cfg['form_expiry_time'])) ? $cfg['form_expiry_time'] : 5*60;

        try {
            if (my_post('posted_actions') !="") {

                $function = unserialize($crypt->str_decrypt(my_post('posted_actions')));
                // error_log("Posted actions:".print_r($function,true));

                if (!empty($function)){

                    if (
                        //my_post($key)
                        !empty($function)
                        && is_array($function)
                        && !empty($function[0])
                        && !empty($function[1])
                        && !empty($function[2])
                    ) {
                        $function_success = false;

                        if (!empty($my_post["paused_action"])) { // save data to the session

                            $app_name = '';
                            foreach($_SESSION['apps'] as $item){
                                if ($libhtml->path==$item->path){
                                    $app_name=$item->name;
                                    break;
                                }
                            }

                            // action => data
                            $_SESSION["paused_forms"][$my_post["paused_action"]] = array(
                                "form_data"=>$my_post[$function[0]],
                                "original_action"=>$my_post["paused_action"],
                                "title"=>$my_post["paused_title"],
                                "paused_date"=>date("Y-m-d H:i:s"),
                                "path"=>$libhtml->path,
                                "app_name"=>$app_name,
                                "new"=>true,
                            );

                            $_SESSION['feedback'] .= g_feedback("success", "Your draft has been saved.");
                            return;

                        } else if (!empty($my_post["discard_action"])) { // remove data from the session

                            unset($_SESSION["paused_forms"][$my_post["discard_action"]]);
                            $_SESSION['feedback'] .= g_feedback("success", "Your draft has been deleted.");
                            return;

                        } else if (!empty($_SESSION['form_token_time']) && (time() - $_SESSION['form_token_time']) > $max_time){

                            $_SESSION['feedback'] .= g_feedback("error","Your form has expired, maximum time is set to ".($max_time/60)." minutes. Please try again.");
                            return;

                        }

                        //post-array/object name
                        $post_array = $function[0];

                        //function name
                        $func_name = $function[1];

                        //For checking user permissions; accounting for update<>edit, and insert<>add misnaming in the system
                        if ($func_name=="update") {
                            $fn="edit";
                        } elseif ($func_name=="insert") {
                            $fn="add";
                        } else {
                            $fn = $func_name;
                        }

                        //Create new instance of the class;
                        $obj_name = $function[2];
                        $object = new $obj_name;

                        //Object ID - it will be empty for add etc.
                        $obj_id = (!empty($function[3])) ? $function[3] : '';

                        //Construct a user-permission path
                        $path = get_path() . $fn . "_" . $object->object_name . ".php";

                        //Construct feedback if everything goes ok
                        $success_feedback = g_feedback(str_replace("_"," ",$func_name), str_replace("_"," ",ucfirst($func_name)) . " (" . $object->human_name . ") was successful");

                        //Either the path is not there or if set must be 1
                        if (isset($user1->$path) && empty($user1->$path)) {

                            throw new \Exception("Action not allowed by the permission system!!!");

                        }

                        // Transfer POST array into new instance
                        if (method_exists($object, "set_post")) {
                            $object->set_post(my_post($post_array));
                        }

                        if (!empty($_SESSION['feedback']) && preg_match('/error/', $_SESSION['feedback'])) {
                            throw new \Exception("ACTION ABORTED.");

                        }

                        //Does the object have ID?
//                         if (!empty($object->{$object->object_pk})){

//                             if ($object->{$object->object_pk}!=$obj_id){

//                                 $_SESSION['feedback'] .= g_feedback("error","Object ID does not match the form. ACTION ABORTED.");
//                                 break;

//                             }

                        if (!empty($obj_id)){

                            $object->{$object->object_pk} = $obj_id;

                            //Select object from db - includes left joins for full log info; don't ask for feedback
                            $object->select($object->{$object->object_pk}, false);

                            //Data entry no longer exist in db! Show error popup
                            if (!empty($object->no_id_in_db)) {

                                $html = '<div class="error">This entry does not exist in the database.</div>';
                                $html .= $libhtml->last_log_entry($obj_name, $object->{$object->object_pk});
                                $html .= '<p>Please close this popup and refresh the page.</p>';
                                $html .= $libhtml->render_cancel_button();
                                $libhtml->show_popup(array("html"=>$html));

                            } else {

                                if (!empty($_SESSION['form_token_time'])) {
                                    //Check if the object has been updated in the meantime
                                    $selection = $db->select_value(
                                        "time",
                                        "system_log",
                                        array(
                                            "WHERE object_id = ? AND object = ? AND time >= ?",
                                            array('object_id' => $object->{$object->object_pk}, 'object' => strtolower($obj_name), 'time' => date('Y-m-d H:i:s', $_SESSION['form_token_time'])),
                                            array('integer', 'varchar', 'date')
                                        ),
                                        array('order_by' => "ORDER BY time DESC", "limit" => array('num_on_page' =>1))
                                    );
                                }
                                if (!empty($selection) && !empty($_SESSION['form_token_time']) && (strtotime($selection)>$_SESSION['form_token_time'])){

                                    $html = '<div class="error">This entry has been amended since the openning of this form.</div>';
                                    $html .= $libhtml->last_log_entry($obj_name, $object->{$object->object_pk});
                                    $html .= "<p>Please close this popup, refresh the page and try again.</p>";
                                    $html .= $libhtml->render_cancel_button();
                                    $libhtml->show_popup(array("html"=>$html));

                                } else {

                                    //Overwrite with posted data; yes it is a duplication, first one was just to set the ID
                                    $object->set_post(my_post($post_array));

                                }

                            }

                        }

                        //Execute function with no id required  - e.g. add
                        $db->start_transaction();

                        if (method_exists($object, $func_name)) $function_success = $object->$func_name();

                        $db->complete_transaction();

                        // remove saved data from the session
                        if (!empty($my_get["wasp"]) && !empty($my_post["wasp"]) && !empty($_SESSION["paused_forms"][$my_get["wasp"]])) {
                            unset($_SESSION["paused_forms"][$my_get["wasp"]]);
                        }

                        if ($function_success !== false && (empty($_SESSION['feedback']) || !preg_match('/error/', $_SESSION['feedback']))) { // all was ok
                            if (empty($_POST["through_page"])) $_SESSION['feedback'] .= $success_feedback;
                            else setrawcookie('gFeedback', rawurlencode($success_feedback), time() + 60*60, "/");

                            // if this was a chained form, inject the newly inserted value into the passed original form variables
                            if (!empty($my_post["prev_page"]) && (!empty($_SESSION["popups"][$my_post["jbox_id"]][$my_post["prev_page"]]))
                            && (!empty($_SESSION["popups"][$my_post["jbox_id"]][$my_post["prev_page"]]["prefill_field"]))) {
                                $fieldnametoprefill = $_SESSION["popups"][$my_post["jbox_id"]][$my_post["prev_page"]]["prefill_field"]; // coming from prev_page
                                $_SESSION["popups"][$my_post["jbox_id"]][$my_post["through_page"]]["vars"][$fieldnametoprefill] = $object->{$object->object_pk};
                            }

                        } else { // something went wrong
                            if ($fn == "add" || $fn == "edit") {
                                /* TO DO  */
                                //$_SESSION['show_popup'] = $object->{"print_".$fn."_form"}();
                            } else {
                                if (!empty($_POST["through_page"])) setrawcookie('gFeedback', rawurlencode($_SESSION['feedback']), time() + 60*60, "/"); // chained forms
                            }
                        }

                        // if we are showing the form again
                        if (!isset($_SESSION['show_popup'])) {
                            unset($my_post['posted_actions']); // Unset the action name submit only
                            unset($my_post[$function[0]]); // Unset the posted object array
                        }

                    }

                }

                // unset button array, keep it in the session for record
                $_SESSION['actions'] = unserialize($crypt->str_decrypt($_POST["posted_actions"]));
                if (!empty($object) && !empty($object->{$object->object_pk})) $_SESSION['actions']['id'] = $object->{$object->object_pk};
                unset($my_post['posted_actions']);
                unset($_POST["posted_actions"]);

                // unset form_token
                unset($my_post["form_token"]);
                unset($_POST["form_token"]);

                // Reload the page but without all unset posted data, only if this was not self submit (chained forms)
                if (!isset($_POST["through_page"])) {

                    $page = get_enc_page();
                    unset($_POST);

                    if (!isset($_SESSION['show_form'])) {
                        header("Location: $page");
                        exit;
                    }

                    // set some jbox variables back
                } else {
                    $_POST["page"] = $_POST["through_page"];
                    $_POST["type"] = "back";
                    $_POST["jbox_opened"] = true;
                }

            } elseif (!empty($_POST['move_to_get'])) {

                //Unset all system fields
                foreach(array('form_token','form_scroll','self_submit','move_to_get') as $field){
                    unset($my_post[$field]);
                    unset($_POST[$field]);
                }

                //Reload the page but without all unset posted data
                $page = get_enc_page();

                unset($_POST);

                header("Location: $page");
                exit;

            }

            //Set the new form token for page forms verification
            $_SESSION['form_token'] = md5(uniqid() . $cfg['md5_salt']);

            return;

        } catch (Exception $e) {
            error_log("REQUEST ERROR: " . $e->getMessage());
            $_SESSION['feedback'] .= g_feedback("error", $e->getMessage());

            $page = get_enc_page();
            unset($_POST);

            if (!isset($_SESSION['show_form'])) {
                header("Location: $page");
                exit;
            }
        }
    }

    function redirect_to_details_on_add_new($object){
        global $libhtml, $db, $cfg, $user1;
        if (!empty($object->object_name) && !empty($_SESSION['actions']['add_'.$object->object_name])){
            unset($_SESSION['actions']['add_'.$object->object_name]);
            $latest_id = $db->select_value($object->object_pk, $object->table, array(), array('order_by' => "ORDER BY $object->object_pk DESC", 'limit' => array('num_on_page' => 1, 'offset' => 0)));
            $url = '';
            if ($user1->{$libhtml->path.$object->object_name.'_details.php'}) $url = encrypt_url($cfg['root'] . $libhtml->path.$object->object_name.'_details.php?'.$object->object_name.'_id='.$latest_id);
            if (!empty($url) && !empty($latest_id)) {
                header("Location: $url");
                exit;
            }
        }
    }

    private function make_my_get(){
        global $crypt, $cfg;

        $my_get = array();

        // if there is "=" in the GET (which is taken from htaccess), than this is not crypted string, do nothing
        if(!empty($_GET['x']) && !strpos($_GET["x"], "=")){
            $decr = explode("&", $crypt->str_decrypt($_GET['x']));

            if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) {
                if (!isset($_SESSION['original_get'])) $_SESSION['original_get']=array();
                $_SESSION['original_get'] = $decr;
            }

            parse_str($crypt->str_decrypt($_GET['x']), $elements);

            foreach($elements as $key => $value){
                if (!is_array($value)) {
                    $my_get += self::purify_array(array($key=>$value), "GET");
                } else {
                    $my_get += self::purify_array(array($key=>$value), "GET");
                }
            }
        }

        return $my_get;
    }

    private function make_my_post(){
        global $cfg;

        $my_post=array();
        if(!empty($_POST)) {
            if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) {
                if (!isset($_SESSION['original_post'])) $_SESSION['original_post']=array();
                $_SESSION['original_post'] = copy_recursive_array($_POST);
            }
            $my_post = self::purify_array($_POST, "POST");
        }
        return $my_post;
    }

    private function make_my_files(){
        global $cfg;

        $my_files=array();
        if(!empty($_FILES)) {

            if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) {
                $_SESSION['original_files'] = $_FILES;
            }

            $my_files = self::purify_array($_FILES, "FILES");
        }
        return $my_files;
    }

    private function make_my_request(){
        global $my_get, $my_post;
        return copy_recursive_array($my_get+$my_post);
    }

    static function my_cookie($str="void",$default=null){
        global $crypt, $my_cookie;
        if (!isset($_COOKIE[$str])){
            return $default;
        } else {
            $cookie = unserialize(urldecode($crypt->str_decrypt($_COOKIE[$str])));
            return self::purify_array($cookie,"COOKIE");
        }
    }

    static function purify_array($input, $type=""){
        $result = self::purify_array_HTMLPurifier($input);
        //$result = self::purify_array_IDS($input, $type);
        return $result;
    }


    static function purify_array_IDS($input, $type) {
        global $cfg, $db;

        $request = array($type => $input,);

        try {
            $filters = new \Expose\FilterCollection();
            $filters->load();

            $logger = new \Monolog\Logger('IDS');
            $formatter = new Monolog\Formatter\LineFormatter("%channel%.%level_name%: %message%", null, true);
            $handler = new \Monolog\Handler\ErrorLogHandler(0, \Monolog\Logger::WARNING);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
            $manager = new \Expose\Manager($filters, $logger);

            $manager->run($request);
            //var_dump($manager->export());
            //var_dump($manager->getImpact());
            //var_dump($manager);
            //foreach ($manager->getReports() as $report) {
            //    var_dump($report);
            //    var_dump($report->toArray());
            //}

            $reports = $manager->getReports();
            if (!empty($reports) && $manager->getImpact() > $cfg['IDS_filter_threshold']) {

                $reports = $manager->getReports();
                $report = $reports[0];
                $logger->addWarning(sprintf("Variable: %s | Value: %s | Path: %s | Impact: %d", $report->getVarName(), $report->getVarValue(), json_encode($report->getVarPath()), $manager->getImpact()));

                $_SESSION['feedback'] .= g_feedback("error", "Injection attack detected and has been logged");
                foreach ($manager->getReports() as $event) {
                    foreach ($event as $filter) {
                        $_SESSION['feedback'] .= g_feedback("error", $filter->getDescription() . ' | Tags: ' . join(', ', $filter->getTags()) . ' | ID: ' . $filter->getId());
                    }
                }

                return array();

            } else {

                $result = $input;
                if (is_array($input)) {
                    foreach($input as $key=>$item) {
                        $result[$key] = (is_array($item)) ? self::purify_array($item) : $item;
                    }
                }
                return $result;
            }

        } catch (Exception $e) {
            printf('An error occured: %s',$e->getMessage());
        }
    }

    static function purify_array_HTMLPurifier($input=""){
        global $cfg, $db;

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^http://(www.youtube.com/embed/|player.vimeo.com/video/)%');
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        if (isset($_SERVER) && isset($_SERVER['OS']) && stripos($_SERVER['OS'], 'windows') !== false) {
            $config->set('Cache.SerializerPath', $cfg['secure_dir']);
        } else {
            $config->set('Cache.SerializerPath', sys_get_temp_dir());
        }
        $purifier = new HTMLPurifier($config);

        $result  = null;
        if (is_array($input)) {
            foreach($input as $key=>$item){
                if(is_array($item)){
                    //echo "purify1: $key \n\n";
                    $result[$key] = self::purify_array($item);
                } else {
                    $result[$key] = $purifier->purify($item);
                }
            }
        } else {
            $result = $purifier->purify($input);
        }
        return $result;
    }

}
