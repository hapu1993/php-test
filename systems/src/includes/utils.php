<?php

// *** Strings ***

function my_nl2br($string){return str_replace(array('\r','\n'), array(chr(13),chr(10)), $string);}

// Method to make a render element function name from a given String
function make_func_name($string="") {
    $string = trim($string);

    if (ctype_digit($string)){
        return $string;

    } else {
        // replace accented chars
        $accents = '/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/';
        $string_encoded = htmlentities($string,ENT_NOQUOTES,'UTF-8');

        $string = preg_replace($accents,'$1',$string_encoded);

        // clean out the rest
        $replace = array('([\40])','([^a-zA-Z0-9-])','(-{2,})');
        $with = array('_','_','_');
        $string = preg_replace($replace,$with,$string);
        $string = trim($string,"-");

    }
    return strtolower($string);
}

function make_seo_title($string="") {
    $string = trim($string);

    if ($string == "homepage" || $string == "Homepage") return '';

    if (ctype_digit($string)){
        return $string;

    } else {
        // replace accented chars
        $accents = '/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/';
        $string_encoded = htmlentities($string, ENT_NOQUOTES,'UTF-8');

        $string = preg_replace($accents,'$1',$string_encoded);

        // clean out the rest
        $replace = array('([\40])','([^a-zA-Z0-9-])','(-{2,})');
        $with = array('-','-','-');
        $string = preg_replace($replace,$with,$string);
        $string = trim($string,"-");

    }
    return strtolower($string);

}

function undo_seo_title($string="") {
    $string = basename($string);
    $string = str_replace(array("-","/"),array("_",""),$string) . ".php";
    return strtolower($string);
}

function initials($string){
	if(!is_null($string) && $string!=''){
		$initials = '';
		$words = array_map('trim', explode(' ', $string));
		foreach($words as $word){
			if(!empty($word)) $initials.= $word[0];
		}
		return $initials;
	} else {
		return '';
	}
}

function yes_no($arg = false){return ($arg) ? "Yes" : "No";}

function IsPostcode($postcode) {
    $postcode = strtoupper(str_replace(' ','',$postcode));
    return (preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/",$postcode) || preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/",$postcode) || preg_match("/^GIR0[A-Z]{2}$/",$postcode));
}

function generate_password($length=32, $strength=15){

    //1 Lower case characters and upper case consonants
    //2 Lower case characters and upper case vowels
    //3 Lower and upper case characters
    //4 Lower case characters and digits
    //5 Lower case characters, upper case consonants and digits
    //6 Lower case characters, upper case vowels and digits
    //7 Lower and upper case characters and digits
    //8 Lower case characters and special symbols
    //9 Lower case characters, special symbols and upper case consonants
    //10 Lower case characters, special symbols and upper case vowels
    //11 Lower and upper case characters and special symbols
    //12 Lower case characters, special symbols and digits
    //13 Lower case characters, upper case consonants, special symbols and digits
    //14 Lower case characters, upper case vowels, special symbols and digits
    //15 Lower and upper case characters, special symbols and digits

    $password = '';
    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';

    if ( $strength & 1) $consonants .= 'BDGHJLMNPQRSTVWXZ';
    if ( $strength & 2) $vowels .= "AEUY";
    if ( $strength & 4) $consonants .= '23456789';
    if ( $strength & 8) $consonants .= '@#$%';

    $alt = time() % 2;
    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }

    return $password;

}

// *** URLs and Paths ***

function get_enc_page($new_args=array(), $encrypt=true, $vars_override = array(), $page_override = ""){
    global $my_get, $my_post;

    // if override is set, my_post and my_get will not be used
    if (!empty($vars_override)) $my_post = $my_get = $vars_override;

    // used for ajax pages navigation
    if (!empty($page_override)) $php_self = $page_override;
    else $php_self = $_SERVER['PHP_SELF'];

    // Unset submit buttons
    unset($my_post['button']);
    unset($my_get['button']);

    // cycle throught all get, post and new arguments
    $vars = array($my_get, $my_post, $new_args);
    $args = array();

    foreach($vars as $set) {
        if (!empty($set)) {
            foreach($set as $k1 => $v1){
                if (!empty($v1) && is_array($v1)){
                    foreach($v1 as $k2 => $v2){
                        if (!empty($v2) && is_array($v2)){
                            foreach($v2 as $k3 => $v3){
                                if (isset($v3) && $v3 != '') $args[$k1][$k2][$k3] = $v3;
                                else unset($args[$k1][$k2][$k3]);
                            }
                        } else if (isset($v2) && $v2 != '' && strlen($v2)<255) {
                            $args[$k1][$k2] = $v2;
                        } else {
                            unset($args[$k1][$k2]);
                        }
                    }
                } else if (isset($v1) && $v1 != '' && strlen($v1)<255) {
                    $args[$k1] = $v1;
                } else {
                    unset($args[$k1]);
                }
            }
        }
    }

    if ($encrypt) return encrypt_url($php_self . "?" . http_build_query($args));
    else return $php_self . "?" . http_build_query($args);

}

function inject_crypt_vars($page = "", $new_vars = array()){
    global $crypt;

    $page = explode("/", $page); // page example /app_cms/page/ABCrypted_string - slash, app, slash, page, slash (with, or without vars, needs to end with slash)

    //Take off the last element; could be empty or a crypted string
    $last_element = array_pop($page);

    //Decrypt and put into old vars
    if (!empty($last_element)) parse_str($crypt->str_decrypt($last_element), $old_vars);

    $args = array();
    if (!empty($old_vars)) {
        foreach($old_vars as $key => $value){
            $args[$key] = (!empty($new_vars[$key])) ? $new_vars[$key] : $value;
            unset($new_vars[$key]);
        }
        $args = array_merge($args, $new_vars);
    } else if (!empty($new_vars)) {
        $args = $new_vars;
    }

    $url=implode("/",$page);
    return encrypt_url($url . "?" . http_build_query($args));

}

function get_path() {
    global $cfg;
    $script_filename = str_replace("\\", "/",$_SERVER['SCRIPT_FILENAME']);
    $path =  str_replace($cfg['source_root'],"",$script_filename);
    return str_replace(basename($path),"",$path);
}

function encrypt_url($url="", $override_urls = false){
    global $crypt, $cfg;
    $x = parse_url($url);
    if (!isset($cfg['nice_URLs']) || (!$cfg['nice_URLs']) || $override_urls) { // override url - used for crypting, but not striping .php from the url
        // Version with no .htaccess
        $encrypted_url = $x['path'];
        if (!empty($x['query'])) $encrypted_url .= "?x=" . $crypt->str_encrypt($x['query']);
    } else {
        //Version with .htaccess
        $str = explode(".php", $x['path']); // replace everything after .php (.php/CRYPTEDSTRING)
        $encrypted_url = $str[0] . "/";
        if (!empty($x['query'])) $encrypted_url .= $crypt->str_encrypt($x['query']);
    }

    return $encrypted_url;

}

function checkClient($HTTP_USER_AGENT = '',$put_into_session=true, $string=false) {
    global $cfg;

    $result=array(
        'RISK_USER_OS'=>'',
        'RISK_USER_OS_GENERIC'=>'',
        'RISK_USER_BROWSER_VER'=>'',
        'RISK_USER_BROWSER_AGENT'=>''
    );

    if (empty($HTTP_USER_AGENT)) $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

    $classifier = new \Woothee\Classifier;
    $r = $classifier->parse($HTTP_USER_AGENT);
    $result['RISK_USER_OS'] = $r['os'] . ' (' . $r['os_version'] . ')';
    $result['RISK_USER_OS_GENERIC'] = $r['os'];
    $result['RISK_USER_BROWSER_VER'] = $r['version'];
    $result['RISK_USER_BROWSER_AGENT'] = $r['name'];

    // create cache folder if it doesn't exist
    if(!is_dir($cfg['cache'])) mkdir($cfg['cache'], 0777);

    //$bc = new Browscap($cfg['cache']);
    //$bc->doAutoUpdate = false;
    //$current_browser = $bc->getBrowser($HTTP_USER_AGENT);
    //$result['RISK_USER_OS']=$current_browser->Platform_Version;
    //$result['RISK_USER_OS_GENERIC'] = $current_browser->Platform;
    //$result['RISK_USER_BROWSER_VER']=$current_browser->Version;
    //if ($current_browser->Device_Name == "iPad") $result['RISK_USER_BROWSER_AGENT'] = "iPad";
    //else $result['RISK_USER_BROWSER_AGENT'] = strtoupper($current_browser->Browser);

    if ($put_into_session) {
        foreach($result as $key=>$value) $_SESSION[$key]=$value;
    } else {
        if ($string) {
            if (isset($current_browser->Parent)) {
                return $current_browser->Parent;
            } else if (isset($current_browser->Browser)) {
                return $current_browser->Browser;
            }
        } else if (!empty($current_browser)) {
            return $current_browser;
        }
    }

}

function isSSL(){
    if (isset($_SERVER['HTTPS'])) {
        if ($_SERVER['HTTPS'] == 1) {
            return true;
        } elseif ($_SERVER['HTTPS'] == 'on') {
            return true;
        }
    } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
        return true;
    }

    return false;
}


// *** Emails ***

function IsEmail($email){
    $email_pattern = "/[A-z0-9\._-]+" . "@" . "[A-z0-9][A-z0-9-]*" . "(\.[A-z0-9_-]+)*" . "\.([A-z]{2,6})$/";
    return preg_match($email_pattern, $email);
}

function general_email($options = array()){
    global $db, $cfg, $user1, $crypt;

    //          create defaults
    $defaults = array(
        'template'=>"user_mail",
        'recipients'=>array(array('email'=>$cfg['email_to'],'fullname'=>$cfg['client'])),
        'cc_recipients'=>array(),
        'bcc_recipients'=>array(),
        'feedback'=>false,
        'attachment'=>false,
        'files'=>array(),
        'client'=>$cfg['client'],
        'root'=>$cfg['root'],
        'website'=>$cfg['website']
    );
    // To use the ArrayLogger
    $logger = new Swift_Plugins_Loggers_ArrayLogger();

    //  SWIFT part - nothing to change

    if (isset($cfg['smtp']) && isset($cfg['smtp_user']) && isset($cfg['smtp_pass']) ) {

        try {
            $user = new User;
            $SAs = $user->get_SA_contact_details();
            $from_addresses = array();
            if (empty($SAs)) {
                throw new Exception("No Sys Admins set cannot set email 'From' Address", 1);
            }
            foreach ($SAs as $SA) {
                $from_addresses[$SA->email] = $SA->fullname;
            }

            $password = $cfg['smtp_pass'];
            if (isset($cfg['smtp_pass_is_encrypted']) && $cfg['smtp_pass_is_encrypted'] === true) {
                $password = $crypt->str_decrypt($cfg['smtp_pass']);
            }

            if (!isset($cfg['file_spool'])){
                $transport = Swift_SmtpTransport::newInstance($cfg['smtp']);
                $transport->setUsername($cfg['smtp_user']);
                $transport->setpassword($passwword);
                if (isset($cfg['smtp_port'])) $transport->setPort($cfg['smtp_port']);
                if (isset($cfg['smtp_encryption'])) $transport->setEncryption($cfg['smtp_encryption']);
            }else{
                $spool = new Swift_FileSpool($cfg['secure_dir'] . "swiftmailer_spool");
                $transport = Swift_SpoolTransport::newInstance($spool);
                $spool_mailer = Swift_Mailer::newInstance($transport);
            }

            foreach($options as $key=>$value) $defaults[$key] = $value;

            // $html_content = file_get_contents($cfg["source_root"] . "includes/mail_templates/" . $defaults['template'] . "_html.php" . $append);
            // $text_content = file_get_contents($cfg["source_root"] . "includes/mail_templates/" . $defaults['template'] . "_text.php" . $append);

            $html_content = file_get_contents($cfg["source_root"] . "includes/mail_templates/" . $defaults['template'] . "_html.tpl");
            $text_content = file_get_contents($cfg["source_root"] . "includes/mail_templates/" . $defaults['template'] . "_text.tpl");

            //find all instances of replacement string
            preg_match_all('/\{\{[A-Z_]+\}\}/',$html_content,$replacements);
            foreach($replacements[0] as $item){
                $str = strtolower(str_replace(array("{{","}}"),"",$item));
                if (!isset($defaults[$str])) $defaults[$str]="";

                //HTML
                $html_content = str_replace($item,str_replace(array("\\r\\n","\r\n","\\n","\n"),"",$defaults[$str]),$html_content);

                //TEXT
                $piece = str_replace(array("</td>","</th>","</tr>"),array("\t\t","\t","\r\n"),$defaults[$str]);
                $text_content = str_replace($item,strip_tags($piece),$text_content);
            }

            // Create the message
            $message = Swift_Message::newInstance($defaults['subject']);
            $message->setFrom($from_addresses);
            $message->setReplyTo($from_addresses);
            if (count($from_addresses) > 1) {
                $message->setSender(current(array_keys($from_addresses)));
            }
            $message->setBody($text_content);
            $message->addPart($html_content, "text/html");
            $message->setCc($defaults['cc_recipients']);
            $message->setBcc($defaults['bcc_recipients']);

            if (!empty($defaults["files"])){
                foreach($defaults["files"] as $file){
                    if (!empty($file['name'])){
                        $message->attach(Swift_Attachment::fromPath($cfg['secure_dir'].$file['file'])->setFilename($file['name']));
                    } else {
                        $message->attach(Swift_Attachment::fromPath($cfg['secure_dir'].$file['file']));
                    }
                }
            }

            // Create the Mailer using your created Transport
            $mailer = Swift_Mailer::newInstance($transport);
            $mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));

            //the recipients are set in the default, can be passed in the options array
            foreach($defaults["recipients"] as $recipient) {
                $failures = array();
                if (IsEmail($recipient['email'])) {
                    $email = $recipient['email'];
                    $fullname =    $recipient['fullname'];
                    $message->setTo(array($email => $fullname));
                    if(isset($cfg['email_delivery_address'])){
                        $plugin = new Swift_Plugins_RedirectingPlugin($cfg['email_delivery_address']);
                        $mailer->registerPlugin($plugin);
                    }
                    try {
                        if (!isset($cfg['file_spool'])){
                            if ($mailer->send($message, $failures)) {
                                if ($defaults["feedback"]) $_SESSION['feedback'] .= g_feedback("success", "Mail to $fullname ($email) sent successfully.");
                            } else {
                                if ($defaults["feedback"]) $_SESSION['feedback'] .= g_feedback("error", "Could not send mail to $fullname.");
                                error_log('[' . $db->database . '] ' . "Email failure sending to: ".print_r($failures, true));
                                error_log('[' . $db->database . '] ' . "Options: ".print_r($defaults, true));
                                error_log('[' . $db->database . '] ' . "Body: $text_content");
                                error_log(print_r($logger->dump(), true));
                            }
                        }else{
                            $spool_mailer->send($message, $failures);
                        }

                    } catch (Exception $e) {
                        if ($defaults["feedback"]) $_SESSION['feedback'] .= g_feedback("error", "SWIFT ERROR: " . $e->getMessage());
                        error_log('[' . $db->database . '] ' . "Swift error: ".print_r($e->getMessage(), true));
                        error_log('[' . $db->database . '] ' . "Options: ".print_r($defaults, true));
                        error_log('[' . $db->database . '] ' . "Body: $text_content");
                        error_log(print_r($logger->dump(), true));
                    }
                }
            }

            /*  if ($ics_file) { $message->attach(new Swift_Message_Attachment($ics_file['data'], $ics_file['filename'], "text/calendar")); } */
        } catch (Exception $e) {
            if ($defaults["feedback"]) $_SESSION['feedback'] .= g_feedback("error", "SWIFT ERROR: " . $e->getMessage());
            error_log('[' . $db->database . '] ' . "Swift error: ".print_r($e->getMessage(), true));
            error_log('[' . $db->database . '] ' . "Options: ".print_r($defaults, true));
            if (isset($text_content) && !empty($text_content)) {
                error_log('[' . $db->database . '] ' . "Body: $text_content");
            }
            error_log(print_r($logger->dump(), true));
        }
    }
}



// *** Files ***

function print_filesize($file,$filesize=0,$decimals=1){
    if (file_exists($file)) $filesize = filesize($file);
    $decr = 1024; $step = 0;
    $prefix = array('Byte','kb','Mb','Gb');
    while(($filesize / $decr) > 0.9){
        $filesize = $filesize / $decr;
        $step++;
    }
    return round($filesize,$decimals).' '.$prefix[$step];
}

function extension($filepath){return strtolower(pathinfo($filepath, PATHINFO_EXTENSION));}

// kept for backwards compatibility as font awesome does not offer good quality file type icons
// only colour coded file types used
function file_type_icon($filename){

    error_log('file_type_icon() is deprecated, please gradually stop using it');

    $ext = extension($filename);
    return strtoupper($ext);

    // return (preg_match('/jpg|gif|png|ai|doc|docx|xls|xlsx|ppt|pptx|pdf|bmp|psd|zip|rar|txt/', $ext)) ? '<span class="ico_file_'.$ext.'">&nbsp;</span>' : '<span class="ico_file_magnifier">&nbsp;</span>';
}

function get_files($dir,$recursive = FALSE) {
    if (is_dir($dir)) {
        for ($list = array(),$handle = opendir($dir); (FALSE !== ($file = readdir($handle)));) {
            if (($file != '.' && $file != '..') && (file_exists($path = $dir.'/'.$file))) {
                if (is_dir($path) && ($recursive)) {
                    $list = array_merge($list, get_files($path, TRUE));
                } else {

                    $owner = posix_getgrgid(fileowner($path));
                    $list[] = (object) array(
                            'filename'=>$file,
                            'dirpath'=>$dir,
                            'modtime'=>date ("d M Y H:i:s.", filemtime($path)),
                            'size'=>print_filesize(filesize($path)),
                            'type'=>filetype($path),
                            'owner'=>$owner['name'],
                    );
                }
            }
        }
        closedir($handle);
        return $list;
    } else return FALSE;
}

function simple_get_files($path, $sort=true){
    $dir_array = array();
    if ($dir = opendir($path)) {
        while (false !== ($file = readdir($dir))) if($file!="." && $file!="..") $dir_array[] = $file;
        closedir ($dir);
        if ($sort) asort($dir_array);
    }
    return $dir_array;
}

function curl_get_file_contents($url){
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $url);
    $contents = curl_exec($c);
    curl_close($c);
    if ($contents){
        return $contents;
    } else {
        return false;
    }
}

function is_upload_file_allowed($temp_filename_with_path, $actual_filename, $mime_types = array()) {
    global $cfg, $my_files;

    if ($mime_types == array()) $mime_types = $cfg['upload_whitelist'];

    if (function_exists("finfo_open") ==- true) {
        $allowed = false;
        $mime = "N/A";
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = strtolower(finfo_file($finfo, $temp_filename_with_path));
        foreach ($mime_types as $type) {
            $type = (object) $type;
            if ($mime == $type->mime) {
                //error_log("Mime Type $mime allowed");
                $allowed = true;
                break;
            }
        }

        if ($allowed == true) {

            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if (!in_array($ext, array_keys($mime_types))) {
                //extension not in allowed list
                //error_log("Extension $ext not allowed");
                $feedback = "File Extension $ext not allowed.";
                $allowed = false;
            } else {
                $allowed = false;
                foreach ($mime_types as $type) {
                    $type = (object) $type;
                    if ($mime == $type->mime && $ext == $type->ext) {
                        $allowed = true;
                        break;
                    }
                }
                if ($allowed == false) {
                    //extension does not match mimetype
                    //error_log("Extension $ext Mimetype $mime mismatch.");
                    $feedback = "Extension $ext Filetype $mime mismatch.";
                    $allowed = false;
                }
            }
        } else {
            $feedback = "File Type $mime not allowed.";
        }
    } else {
        error_log("ERROR: unable to check filetype is allowed finfo_open does not exist.");
        $allowed = true;
    }
    if (isset($feedback)) $_SESSION['feedback'] = $feedback;

    return $allowed;
}

function renderFile($filename) {
    if(file_exists($filename) && is_file($filename)) {
        $code = highlight_file($filename, true);
        $counter = 1;
        $arr = explode('<br />', $code);
        $html = '<table class="code_table">';
        foreach($arr as $line) {
            $html.= '
                    <tr>
                        <td style="color: #666;width: 65px;">' . $counter . ':</td>
                        <td style="width: 100%;">' . $line . '</td>
                    </tr>';
            $counter++;
        }
        $html.= '</table>';
    } else {
        $html.= "<p>The file <i>$filename</i> could not be opened.</p>\r\n";
    }
    return $html;
}

function feed_grab($url,$local_file,$min_chars,$time_limit){
    if (!is_file($local_file) || (is_file($local_file) && (strtotime("now") - filemtime($local_file) > $time_limit))) {
        $text = file_get_contents($url);
        if (strlen($text) > $min_chars) {
            file_put_contents($local_file, $text);
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

function  export_to_csv($data=array(),$report_root_name='report'){

  header('Content-Type:  text/x-comma-separated-values');
  header('Content-Disposition:  attachment;  filename="'.$report_root_name.'_'.date("Y-m-d_H-i-s",  time())  .  '.csv"');
  header('Pragma:  hack');

  $fp  =  fopen('php://output',  'w');

  foreach($data  as  $row)  fputcsv($fp,$row);

  fclose($fp);

}


// *** Images ***

function phpThumb_URL($options){
    global $cfg;

    if (empty($options['src']) || !is_file($options['src'])){
        return false;
    }

    // Mash together all source parameters and last write time for the file
    $md5 = md5(serialize($options) . filemtime($options['src']));
    $ext = extension($options['src']);
    $file = $md5[0].'/'.$md5[1].'/'.$md5[2].'/'.$md5[3].'/cache_'.$md5.'.'.$ext;

    if (!is_file($cfg['imagecache'].$file)) {

        //error_log("Making thumb--->" . $cfg['imagecache'].$file);

        $phpThumb = new phpThumb();
        $phpThumb->setParameter('config_document_root', $cfg['secure_dir']);
        $phpThumb->setParameter('config_cache_directory', $cfg['imagecache']);
        $phpThumb->setParameter('config_cache_directory_depth', 4);
        $phpThumb->setParameter('config_cache_prefix', 'cache');
        $phpThumb->setParameter('cache_filename', $cfg['imagecache'].$file);
        $phpThumb->setParameter('config_error_die_on_error', ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) ? true : false);
        $phpThumb->setParameter('config_error_silent_die_on_error', ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) ? false : true);
        $phpThumb->setParameter('config_error_die_on_source_failure', ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) ? true : false);
        $phpThumb->setParameter('config_high_security_enabled', false);
        $phpThumb->setParameter('config_high_security_password', 'Riskpoint.2015!');
        $phpThumb->setParameter('config_output_format', $ext);
        $phpThumb->setParameter('config_disable_debug', false);
        $phpThumb->setParameter('config_error_die_on_error', true);
        $phpThumb->setParameter('config_error_silent_die_on_error', false);

        foreach($options as $key=>$value) $phpThumb->$key=$value;

        if (!$phpThumb->GenerateThumbnail()) {
            error_log('cannot generate thumbnail');
            //dump_var($phpThumb);
            //die();
        }
        if (!phpThumb_EnsureDirectoryExists(dirname($phpThumb->cache_filename))) {
            error_log('cannot make a directory');
            //dump_var($phpThumb);
            //die();
        }
        if (!$phpThumb->RenderToFile($phpThumb->cache_filename)) {
            error_log('cannot save thumbnail');
            //dump_var($phpThumb);
            //die();
        }
    }

    return $cfg['imagecache_view'].$file;
}

function phpThumb_EnsureDirectoryExists($dirname) {
    $directory_elements = explode("/", $dirname);
    $startoffset = (!$directory_elements[0] ? 2 : 1);  // unix with leading "/" then start with 2nd element; Windows with leading "c:\" then start with 1st element
    $open_basedirs = preg_split('/[;:]/', ini_get('open_basedir'));
    foreach ($open_basedirs as $key => $open_basedir) {
        if (preg_match('#^'.preg_quote($open_basedir).'#', $dirname) && (strlen($dirname) > strlen($open_basedir))) {
            $startoffset = count(explode("/", $open_basedir));
            break;
        }
    }
    $i = $startoffset;
    $endoffset = count($directory_elements);
    for ($i = $startoffset; $i <= $endoffset; $i++) {
        $test_directory = implode("/", array_slice($directory_elements, 0, $i));
        if (!$test_directory) {
            continue;
        }
        if (!@is_dir($test_directory)) {
            if (@file_exists($test_directory)) {
                // directory name already exists as a file
                return false;
            }
            //error_log($test_directory);
            mkdir($test_directory, 0755);
            //error_log("Making directory--->" . $test_directory);
            chmod($test_directory, 0755);
            if (!@is_dir($test_directory) || !@is_writeable($test_directory)) {
                return false;
            }
        }
    }
    return true;
}

function captcha_image(){
    global $cfg;
    //Mash together all source parameters and last write time for the file
    $md5 = md5(time().microtime(true).$cfg['md5_salt']);
    $ext = "png";
    $file = $md5[0].'/'.$md5[1].'/'.$md5[2].'/'.$md5[3].'/cache_'.$md5.'.'.$ext;
    $filname = $cfg['imagecache'].$file;

    //Assumes Riskpoint session handler present and tmp directory open.
    $securimage = new Securimage();
    $securimage->session_name = session_name();

    if (!phpThumb_EnsureDirectoryExists(dirname($filname))) {
        error_log('cannot make a directory');
        //dump_var($phpThumb);
        //die();
    }
    if ($securimage->create($filname)){
        return $cfg['imagecache_view'].$file;
    } else {
        error_log('Cannot save image');
        return false;
    }

}



// *** Date and Time ***

function zero_date($d, $dateformat="") {
    global $user1;
    if (strtotime($d)===false || is_zero_date($d)) {
        return "";
    } else {
        if (empty($dateformat)) {
            $dateformat="d M Y";
            if (!empty($user1) && !empty($user1->preferences->dateformat)) $dateformat = $user1->preferences->dateformat;
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/',$d)) $dateformat .= " H:i";
        }
        if (class_exists('DateTime')) {
            $user_timezone = 'Europe/London';
            if (!empty($user1) && !empty($user1->preferences->timezone)) $user_timezone = $user1->preferences->timezone;
            $timezone = new DateTimeZone($user_timezone);
            $date = new DateTime($d,$timezone);
            return $date->format($dateformat);
        } else {
            return date($dateformat,strtotime($d));
        }
    }
}

function is_zero_date($d) {
    $zero_dates = array(
            "0000-00-00",
            "0000-00-00 00:00:00",
            // "1900-01-01 00:00:00",
            // "1900-01-01 00:00:00.000",
            // "Jan 1 1900 12:00AM",
            // "Jan 1 1900 12:00AM",
            // "Jan 1 1900 12:00:000AM",
            // "1900-01-01",
    );
    return (empty($d) || (is_null($d)) || in_array($d,$zero_dates));
}

function phpdtojsd($date){ // converts php date format to js date format (datepicker)
    return str_replace(array("\Wee\k W Y", "F", "Y", "D d"), array("d M yy", "M", "yy", "d"), $date);
}

function rel_time($datefrom, $dateto=-1, $zero='N/A'){
    // Defaults and assume if 0 is passed in that
    // its an error rather than the epoch

    if(empty($datefrom)) { return $zero; }
    if($dateto==-1) { $dateto = time(); }

    // Calculate the difference in seconds betweeen
    // the two timestamps

    $difference = $dateto - $datefrom;

    // Based on the interval, determine the
    // number of units between the two dates
    // From this point on, you would be hard
    // pushed telling the difference between
    // this function and DateDiff. If the $datediff
    // returned is 1, be sure to return the singular
    // of the unit, e.g. 'day' rather 'days'

    switch(true){

        case(strtotime('-1 min', $dateto) < $datefrom):
            $res = 'now';
            break;
            // If difference is less than 60 seconds,
            // seconds is a good interval of choice
        case(strtotime('-1 min', $dateto) < $datefrom):
            $datediff = $difference;
            // $res = ($datediff==1) ? $datediff.' second ago' : $datediff.' seconds ago';
            $res = ($datediff==1) ? $datediff.' second' : ($datediff==0) ? '0' : $datediff.' seconds'; // return 0 - so we can customize the meesage like Just deleted (for side wastebasket)
            break;
            // If difference is between 60 seconds and
            // 60 minutes, minutes is a good interval
        case(strtotime('-1 hour', $dateto) < $datefrom):
            $datediff = floor($difference / 60);
            $res = ($datediff==1) ? $datediff.' minute' : $datediff.' minutes';
            break;
            // If difference is between 1 hour and 24 hours
            // hours is a good interval
        case(strtotime('-1 day', $dateto) < $datefrom):
            $datediff = floor($difference / 60 / 60);
            $res = ($datediff==1) ? $datediff.' hour' : $datediff.' hours';
            break;
            // If difference is between 1 day and 7 days
            // days is a good interval
        case(strtotime('-1 week', $dateto) < $datefrom):
            $day_difference = 1;
            while (strtotime('-'.$day_difference.' day', $dateto) >= $datefrom)
            {
                $day_difference++;
            }

            $datediff = $day_difference;
            $res = ($datediff==1) ? 'yesterday' : $datediff.' days';
            break;
            // If difference is between 1 week and 30 days
            // weeks is a good interval
        case(strtotime('-1 month', $dateto) < $datefrom):
            $week_difference = 1;
            while (strtotime('-'.$week_difference.' week', $dateto) >= $datefrom)
            {
                $week_difference++;
            }

            $datediff = $week_difference;
            $res = ($datediff==1) ? 'last week' : $datediff.' weeks';
            break;
            // If difference is between 30 days and 365 days
            // months is a good interval, again, the same thing
            // applies, if the 29th February happens to exist
            // between your 2 dates, the function will return
            // the 'incorrect' value for a day
        case(strtotime('-1 year', $dateto) < $datefrom):
            $months_difference = 1;
            while (strtotime('-'.$months_difference.' month', $dateto) >= $datefrom)
            {
                $months_difference++;
            }

            $datediff = $months_difference;
            $res = ($datediff==1) ? $datediff.' month' : $datediff.' months';

            break;
            // If difference is greater than or equal to 365
            // days, return year. This will be incorrect if
            // for example, you call the function on the 28th April
            // 2008 passing in 29th April 2007. It will return
            // 1 year ago when in actual fact (yawn!) not quite
            // a year has gone by
        case(strtotime('-1 year', $dateto) >= $datefrom):
            $year_difference = 1;
            while (strtotime('-'.$year_difference.' year', $dateto) >= $datefrom)
            {
                $year_difference++;
            }

            $datediff = $year_difference;
            $res = ($datediff==1) ? $datediff.' year' : $datediff.' years';
            break;

    }
    return $res;
}

// *** Arrays and Objects ***

function array2object($array,&$object){
    if (count($array) == 1) foreach($array[0] as $key => $value) (is_null($value)) ? $object->$key="" : $object->$key = $value;
}

function copy_recursive_array($input){
    $limit = 20000;
    if (is_array($input)){
        $output = array();
        foreach($input as $key => $value) $output[$key] = copy_recursive_array($value);
    } else {
        $output = (strlen($input) < $limit) ? $input : $output="[DATA TOO BIG TO RETRIEVE]";
    }
    return $output;
}

function get_clean_object($id, $obj_class,$redirect = true){
    global $cfg, $libhtml, $user1;

    $object = new $obj_class;

    $object->select($id, false);

    if (empty($object->{$object->object_pk})) {

        if ($redirect) {

            if (!empty($_SESSION['history'])) {
                trigger_error("$obj_class {$object->object_pk} $id doesnt exist, redirecting back to previous page.", E_USER_ERROR);
                trigger_error(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true), E_USER_ERROR);
                $e = end($_SESSION['history']);
                $l = $e['url'];

            } else {

                $l = $user1->universal_redirect();

            }

            //Check for scenario where an object is deleted from its own detail page
            if (!empty($_SESSION['actions']) && !empty($_SESSION['actions']['delete_'.$object->object_name])){
                $del = $_SESSION['actions']['delete_'.$object->object_name];
                if (
                (!empty($del[0]) && $del[0]==$object->object_name)
                && (!empty($del[1]) && $del[1]=='delete')
                && (!empty($del[2]) && $del[2]==$obj_class)
                && (!empty($_SESSION['actions']['id']) && $_SESSION['actions']['id']==$id)
                ){

                    if (!empty($_SESSION['history']) && count($_SESSION['history'])>1){

                        //Go back one step
                        $e = prev($_SESSION['history']);
                        $l = $e['url'];

                    } else {

                        $l = $user1->universal_redirect();

                    }

                }
            }

            header("Location: " . $l);
            exit;

        } else {
            return false;
        }
    } else {
        return $object;
    }
}

function sortBySubkey(&$array, $subkey, $sortType = SORT_ASC) {
    foreach ($array as $subarray) $keys[] = $subarray[$subkey];
    array_multisort($keys, $sortType, $array);
}

function is_sequential_array($var){return(array_merge($var)===$var && is_numeric(implode(array_keys($var))));}

function is_assoc_array($var){return (array_merge($var)!==$var || !is_numeric(implode(array_keys($var))));}

function object_diff($o1,$o2,$differences_only=true,$labels = array("Old","New"),$ss1=false,$ss2=true){

    if (isset($o1->table_fields) && isset($o2->table_fields) && ($o1->table_fields==$o2->table_fields)){

        $html = "";

        foreach($o1->table_fields as $field) {

            $f1 = ($ss1) ? my_nl2br($o1->$field) : $o1->$field;
            $f2 = ($ss2) ? my_nl2br($o2->$field) : $o2->$field;

            $f1 = (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) ? stripslashes($f1) : $f1;
            $f2 = (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) ? stripslashes($f2) : $f2;

            if (!($differences_only && ($f1==$f2))) {

                if (is_serial($f1) && is_serial($f2)){

                    $u1 = unserialize($f1);
                    $u2 = unserialize($f2);

                    $html .= '
                        <tr>
                            <th style="width:200px;">'.$field.'</th>
                            <td>'.dump_array(arrayRecursiveDiff($u1,$u2)).'</td>
                            <td>'.dump_array(arrayRecursiveDiff($u2,$u1)).'</td>
                        </tr>';
                } else {

                    $html .= '
                            <tr>
                                <th style="width:200px;">'.$field.'</th>
                                <td>'.$f1.'</td>
                                <td>'.$f2.'</td>
                            </tr>';
                }
            }
        }

        if (!empty($html)) {
            $html = '
                    <br/>
                    <table class="action_form details_form" style="max-width:100%;">
                        <tr>
                            <th style="width:200px;"></th>
                            <th style="min-width:200px;">'.$labels[0].'</th>
                            <th style="min-width:200px;">'.$labels[1].'</th>
                        </tr>
                        '.$html.'
                    </table>';
        }
        return $html;
    }
}

function is_serial($value) {

    if (!is_string($value) || empty($value)){
        return false;
    }

    // Serialized false, return true. unserialize() returns false on an
    // invalid string or it could return false if the string is serialized
    // false, eliminate that possibility.
    if ($value === 'b:0;'){
        $result = false;
        return true;
    }

    $length    = strlen($value);
    $end    = '';

    switch ($value[0]){
        case 's':
            if ($value[$length - 2] !== '"'){
                return false;
            }
        case 'b':
        case 'i':
        case 'd':
            // This looks odd but it is quicker than isset()ing
            $end .= ';';
        case 'a':
        case 'O':
            $end .= '}';

            if ($value[1] !== ':'){
                return false;
            }

            switch ($value[2]){
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                    break;

                default:
                    return false;
            }
        case 'N':
            $end .= ';';

            if ($value[$length - 1] !== $end[0]){
                return false;
            }
            break;

        default:
            return false;
    }

    if (($result = @unserialize($value)) === false){
        $result = null;
        return false;
    }
    return true;
}

function arrayRecursiveDiff($aArray1, $aArray2) {
    $aReturn = array();

    foreach ($aArray1 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
            } else {
                if ($mValue != $aArray2[$mKey]) {
                    $aReturn[$mKey] = $mValue;
                }
            }
        } else {
            $aReturn[$mKey] = $mValue;
        }
    }
    return $aReturn;
}

function recursive_implode( $glue, $pieces ) {
    foreach( $pieces as $r_pieces ) {
        $retVal[] = (is_array( $r_pieces )) ? recursive_implode( $glue, $r_pieces ) : $r_pieces;
    }
    return implode( $glue, $retVal );
}

function array2xml($array, $inner = false){
    ($inner === false) ? $xml = "<root>" : $xml='';
    foreach($array as $k=>$v) {
        $tag = preg_replace('/[0-9]{1,}/','data',$k); // replace numeric key in array to 'data'
        if(is_array($v) || is_object($v)) {
            $xml .= "<$tag>" . array2xml($v,true) . "</$tag>";
        } else {
            $xml .= "<$tag>" . htmlspecialchars($v) . "</$tag>";
        }
    }
    if($inner === false) $xml .= "</root>";
    return $xml;
}

function xml2array($contents, $get_attributes=1, $priority = 'tag') {
    if(!$contents) return array();

    if(!function_exists('xml_parser_create')) {
        //print "'xml_parser_create()' function not found!";
        return array();
    }

    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);

    if(!$xml_values) return;//Hmm...

    //Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();

    $current = &$xml_array; //Refference

    //Go through the tags.
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
        unset($attributes,$value);//Remove existing values, or there will be trouble

        //This command will extract these variables into the foreach scope
        // tag(string), type(string), level(int), attributes(array).
        extract($data);//We could use the array by itself, but this cooler.

        $result = array();
        $attributes_data = array();

        if(isset($value)) {
            if($priority == 'tag') $result = $value;
            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }

        //Set the attributes too.
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag') $attributes_data[$attr] = $val;
                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }

        //See tag status and do the needed.
        if($type == "open") {//The starting of the tag '<tag>'
            $parent[$level-1] = &$current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                $repeated_tag_index[$tag.'_'.$level] = 1;

                $current = &$current[$tag];

            } else { //There was another element with the same tag name

                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else {//This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag.'_'.$level] = 2;

                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                        unset($current[$tag.'_attr']);
                    }

                }
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                $current = &$current[$tag][$last_item_index];
            }

        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
            //See if the key is already taken.
            if(!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

            } else { //If taken, put all things inside a list(array)
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                    if($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level]++;

                } else { //If it is not an array...
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $get_attributes) {
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }

                        if($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                }
            }

        } elseif($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level-1];
        }
    }

    return($xml_array);
}

function domnode_to_array($node) {

    $output = array();
    switch ($node->nodeType) {
        case XML_CDATA_SECTION_NODE:
        case XML_TEXT_NODE:
            $output = trim($node->textContent);
            break;
        case XML_ELEMENT_NODE:
            for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                $child = $node->childNodes->item($i);
                $v = domnode_to_array($child);
                if(isset($child->tagName)) {
                    $t = (string) $child->tagName;
                    if(!isset($output[$t])) $output[$t] = array();

                    if (is_array($output[$t])) $output[$t][] = $v;
                }
                elseif($v) {
                    $output = (string) $v;
                }
            }
            if(is_array($output)) {
                if($node->attributes->length) {
                    $a = array();
                    foreach($node->attributes as $attrName => $attrNode) {
                        $a[$attrName] = (string) $attrNode->value;
                    }
                    $output['@attributes'] = $a;
                }
                foreach ($output as $t => $v) {
                    if(is_array($v) && count($v)==1 && $t!='@attributes') {
                        $output[$t] = $v[0];
                    }
                }
            }
            break;
    }
    return $output;

}

//Sort array of objects or arrays - eg. results of db->select - by a property
function sortByProp($array, $propName, $reverse = false, $objects = true){
    $sorted = array();

    if ($objects) {
        foreach ($array as $item) $sorted[$item->$propName][] = $item;
    } else {
        foreach ($array as $item) $sorted[$item[$propName]][] = $item;
    }

    if ($reverse) krsort($sorted); else ksort($sorted);

    $result = array();

    foreach ($sorted as $subArray) foreach ($subArray as $item) $result[] = $item;

    return $result;
}


// *** Logging ***

function dump_var($var, $to_string = false) {
    if (isset($var)) {
        $str = "<pre>\n" . htmlspecialchars(print_r($var, true)) . "</pre>\n";
        if ($to_string) {
            return $str;
        } else {
            echo $str;
        }
    }
}

function dump_array($arr, $options = array()) {

    $defaults = array (
        'width'=>'100%',
        'dstate'=>'hidden', //hidden || visible
        'title'=>'',
        'toggle_all'=>false,
        'textarea'=>false,
        'details_form'=>true,
    );

    if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

    $dump_array = " dump_array";
    if (!$defaults['toggle_all']) {
        $defaults['dstate']="";
        $dump_array="";
    }

    // add class
    $details_form = (!empty($defaults['details_form'])) ? 'details_form' : '';

    $table = '
        <table class="action_form '.$details_form . $dump_array.'" width="'.$defaults['width'].'">';
    if (!empty($defaults['title'])) {
        $table .= '
            <tr class="table_title">
                <td colspan="100%">
                    <h3>' . $defaults['title'] . '</h3>
                </td>
            </tr>';
    }

    if (is_array($arr) || is_object($arr)) {

        foreach($arr as $key => $val) {
            $table.= '
            <tr class="' . $defaults['dstate'] . '">
                    <th style="width:200px;">'.$key.'</th>
                        <td>
                            <div>';

            if (!is_a($val, 'mysqli_result')){
                if (is_object($val)) {
                    $val2 = array();
                    foreach($val as $key2 => $item2) $val2[$key2]=$item2;
                    $table .= dump_array($val2);
                } elseif (is_array($val)){
                    $table .= dump_array($val);
                } else {
                    if ($defaults['textarea']) {
                        $table .= "<textarea>$val</textarea>";
                    } else {
                        $table .= $val;
                    }
                }
            }

            $show = ($defaults['toggle_all']) ? '<div class="strigg">Show</div>' : '';
            $table .= "</div>$show</td></tr>\n";
        }

    } else {

        $table.= '
            <tr class="' . $defaults['dstate'] . '">
                <th style="width:200px;"></th>
                    <td>'.$arr.'</td>
                </tr>';
    }

    $table .= '</table><br/>';

    return $table;
}

function user_log($var,$options=array()){
    if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) {//Only in local-dev
        if (!isset($_SESSION['user_log'])) $_SESSION['user_log']="";
        $x=debug_backtrace();
        $file = $x[0]['file'];
        $line = $x[0]['line'];//$file." - line ". $line
        $defaults = array(
                'title'=>basename($file)." - line ". $line
        );
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;
        $_SESSION['user_log'] .= dump_array($var,$defaults);
    }
}



// *** 3rd Party APIs and Services ***

function google_postcode_location_lookup($postcode) {
    global $cfg;

    $coordinates = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($postcode . ',United Kingdom'));
    $coordinates = json_decode($coordinates);
    //error_log(print_r($coordinates,true));
    if (!empty($coordinates)) {
        $location = new StdClass;
        $location->lat = $coordinates->results[0]->geometry->location->lat;
        $location->lng = $coordinates->results[0]->geometry->location->lng;
        return $location;
    }
}

function get_google_coordinates_from_os($os1="", $os2="", $grid="", $system="osng") {
    global $cfg, $user1;
    $s = "&system=$system";
    if ($os1!="") $s .="&x_1=$os1";
    if ($os2!="") $s .="&y_1=$os2";
    if ($grid!="") $s .="&grid_1=$grid";
    $url = "http://developer.multimap.com/API/convert/1.2/" . $user1->preferences->os_key . "?output=json".$s;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $cfg['website']);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response);
    $result = $data->result_set[0];
    if (count($result)==1){
        return $result->point;
    } else {
        return "";
    }
}

function getYouTubeId($url) {
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        return $match[1];
    }
}


function create_lock_file($file_name) {
    if (empty($file_name)) throw new Exception('Lock file_name cannot be empty.');

    $lock_file = sys_get_temp_dir(). '/' . $file_name;
    $lock_file_handle = fopen($lock_file, "x");
    if ($lock_file_handle === FALSE) {
        $lock_file_handle = fopen($lock_file, "r");
        $created_date = fread($lock_file_handle,filesize($lock_file));
        fclose($lock_file_handle);
        throw new Exception("Lock file '$lock_file' exists: created at $created_date (Current time - " .date('Y-m-d H:i:s'). ") ... aborting.");
    } else {
        fwrite($lock_file_handle, date('Y-m-d H:i:s'));
        fclose($lock_file_handle);
    }

}

function remove_lock_file($file_name) {
    if (empty($file_name)) throw new Exception('Lock file_name cannot be empty.');

    unlink(sys_get_temp_dir(). '/' . $file_name);
}

function get_local_text($str){
    global $libhtml;
    $key = trim($str);
    $value = $key;
    if (isset($libhtml->local_text[$key])){
        $value = $libhtml->local_text[$key];
    }
    return $value;
}

function populateLocalText(){
    global $db;
    if (!isset($_SESSION["local_text"]) || empty($_SESSION["local_text"])){
        $_SESSION["local_text"] = $db->select("local_key,value", "system_local_text", array());
    }
    
    if(isset($_SESSION["local_text"]) && !empty($_SESSION["local_text"])){
        foreach($_SESSION["local_text"] as $local_txt){
            $_SESSION["local_txt"][$local_txt->local_key] = $local_txt->value;
        }
    }
}
