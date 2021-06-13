<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    //Only available in dev environment
    if (!isset($cfg['ENV']) || strtolower($cfg['ENV']) != 'local-dev') {
        header("Location: ..");
        exit;
    }

    $libhtml = new Libhtml(array(
            "tab"=>my_get("tab", "encryption"),
            "title"=>"Utilities",
    ));

    if ($libhtml->tab=="encryption") {

        $html = $libhtml->form_start();
        $html .= open_table("800px");
        $html .= $libhtml->render_form_table_row("plaintext",my_request("plaintext"),"Plaintext","plaintext");

        if (my_request("plaintext")!="") {
            $html .= $libhtml->render_table_row("md5 encrypted",md5(my_request("plaintext")));
            $html .= $libhtml->render_table_row("md5 salt encrypted",md5(my_request("plaintext") . $cfg['md5_salt']));
            $html .= $libhtml->render_table_row("sha1 encrypted",sha1(my_request("plaintext")));
            $html .= $libhtml->render_table_row("sha1 salt encrypted",sha1(my_request("plaintext") . $cfg['md5_salt']));
            $html .= $libhtml->render_table_row("System crypt",$crypt->str_encrypt(my_request("plaintext")));
            $html .= $libhtml->render_table_row("BCrypt",$crypt->bcrypt(my_request("plaintext")));
            $html .= $libhtml->render_table_row("Base 64 encode",base64_encode(my_request("plaintext")));
            $html .= $libhtml->render_table_row("URL encode",urlencode(my_request("plaintext")));
            $html .= $libhtml->render_table_row("UTF-8 encode",utf8_encode(my_request("plaintext")));
        }

        $html .= close_table();

        $html .= $libhtml->render_submit_button("encrypt", "Encrypt",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));

        $html .= $libhtml->form_end();

        $html .= "<br/>";

        $html .= $libhtml->form_start();
        $html .= open_table("800px");

        $html .= $libhtml->render_form_table_row("crypttext",my_request("crypttext"),"Crypted Text","crypttext");

        if (my_request("crypttext")!='') {
            $html .= $libhtml->render_table_row("System decrypt","<p>" . $crypt->str_decrypt(my_request("crypttext")) . "</p>");
        }

        $html .= close_table();

        $html .= $libhtml->render_submit_button("decrypt", "Decrypt",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));

        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="urls") {

        $html = $libhtml->form_start();
        $html .= open_table("800px");
        $html .= $libhtml->render_form_table_row("plain_url",my_request("plain_url"),"Plain URL","plain_url");

        if (my_request("plain_url")!="") {

            $url = my_request("plain_url");
            $html .= $libhtml->render_table_row("URL",$url);
            $html .= $libhtml->render_table_row("Parsed URL",dump_array(parse_url($url)));
            $html .= $libhtml->render_table_row("Crypted URL",encrypt_url($url));

        }

        $html .= close_table();

        $html .= $libhtml->render_submit_button("encrypt", "Encrypt",array('show_cancel'=>false,"pause"=>false,'post_functions'=>array()));

        $html .= $libhtml->form_end();

        $html .= "<br/>";

        $html .= $libhtml->form_start();
        $html .= open_table("800px");

        $html .= $libhtml->render_form_table_row("crypted_url",my_request("crypted_url"),"Crypted/System URL","crypted_url");

        if (my_request("crypted_url")!='') {

            $patterns = array(
                '^([-a-zA-Z0-9_]+)/([-a-zA-Z0-9_]+)/(.+)$^'=>0,
                '^([-a-zA-Z0-9_]+)/([-a-zA-Z0-9_!]+)$^'=>2,
            );

            foreach($patterns as $pattern=>$data){

                $url = str_replace($cfg['root'],'',my_request("crypted_url"));

                if (preg_match($pattern,$url,$matches)){

                    if ($data == 0)
                        $url = $matches[1].'/'.$matches[2].'.php?'.$crypt->str_decrypt($matches[3]);
                    if ($data == 2){
                        $url = $matches[1].'.php?'.$crypt->str_decrypt($matches[2]);
                    }

                    $html .= $libhtml->render_table_row("Pattern",$pattern);
                    $html .= $libhtml->render_table_row("Matches",$url);
                    break;

                }

            }

        }

        $html .= close_table();

        $html .= $libhtml->render_submit_button("decrypt", "Decrypt",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));

        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="preg_match") {

        //Use raw $_POST - dangerous!
        $utils_preg_subject="";

        if (isset($_SESSION['original_post']['utils_preg_subject'])) $utils_preg_subject = $_SESSION['original_post']['utils_preg_subject'];

        $html = $libhtml->form_start();
        $html .= open_table("800px");
        $html .= $libhtml->render_form_table_radio_selection("selection",my_request("selection"),"Pattern Source","selection",array("IDS XML","Common Patterns","Free Text"),"","",array('class'=>"self_submit"));

        if (my_request("selection")!="") {

            if (my_request("selection")=="IDS XML") {

                $rules = get_ids_and_description_from_XML();
                $html .= $libhtml->render_form_table_row_selection("utils_IDS_id",stripslashes(my_request("utils_IDS_id")),"Pattern Description","utils_IDS_id",$rules,"pattern","name",array('class'=>"self_submit"));
                if (my_request("utils_IDS_id")!="") {
                    $utils_preg_pattern = get_rule_from_id(my_request("utils_IDS_id"));
                    $html .= $libhtml->render_table_row("Preg Match Pattern",$utils_preg_pattern);
                }

            } elseif (my_request("selection")=="Free Text") {

                $html .= $libhtml->render_form_table_row("utils_preg_pattern",stripslashes(my_request("utils_preg_pattern")),"Pattern","utils_preg_pattern");

            } elseif (my_request("selection")=="Common Patterns") {

                $selection[] = (object) array('name' => "DateTime", 'pattern' => "/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/");
                $selection[] = (object) array('name' => "Date", 'pattern' => "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/");
                $html .= $libhtml->render_form_table_row_selection("utils_preg_pattern",stripslashes(my_request("utils_preg_pattern")),"Pattern","utils_preg_pattern",$selection,"pattern","name");
            }

            $html .= $libhtml->render_form_table_row_text("utils_preg_subject",stripslashes(my_request("utils_preg_subject")),"Subject","utils_preg_subject");

            if (my_request("selection")=="IDS XML" && my_request("utils_IDS_id")!="" && $utils_preg_pattern!="") {

                (preg_match('/' . $utils_preg_pattern . '/ms', stripslashes(my_request("utils_preg_subject")))) ? $result = "TRUE" : $result = "FALSE";
                $html .= $libhtml->render_table_row("Result",$result);

                if ($result == "TRUE") {
                    preg_match_all('/' . $utils_preg_pattern . '/ms', stripslashes(my_request("utils_preg_subject")), $output);
                    //dump_var($output);
                    $html .= $libhtml->render_table_row("Matched Pattern", "&quot;" . recursive_implode('&quot;<br />&quot;', $output) ."&quot;");
                }

            } elseif (my_request("utils_preg_pattern")!="" && my_request("utils_preg_subject")!="") {

                (preg_match(stripslashes(my_request("utils_preg_pattern")), nl2br(my_request("utils_preg_subject")))) ? $result = "TRUE" : $result = "FALSE";
                $html .= $libhtml->render_table_row("Result",$result);
            }
        }

        $html .= close_table();
        if (my_request("selection")!="") $html .= $libhtml->render_submit_button("preg_match", "Match", array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));
        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="json") {

        //Use raw $_POST - dangerous!
        $json="";

        if (isset($_SESSION['original_post']['json'])) $json = $_SESSION['original_post']['json'];

        $html .= $libhtml->form_start();
        $html .= open_table('100%');
        $html .= $libhtml->render_form_table_row_hidden("decode_json","decode_json");
        $html .= $libhtml->render_form_table_row_text("json",$json,"Encoded","json");
        $html .= $libhtml->render_form_table_radio_selection("selection",my_request("selection"),"Encoding Type","selection",array("JSON","Serialized"));

        if (my_request("decode_json")!="") {

            if (my_request("selection")=="JSON") {
                $object = json_decode($json);
                if ($object === FALSE) {
                    $_SESSION['feedback'].= g_feedback("error","JSON decode Error ...");
                } else {
                    $html .= $libhtml->render_table_row("Result",dump_array($object));
                }

            }

            if (my_request("selection")=="Serialized") {
                $object = unserialize($json);
                if ($object === FALSE) {
                    $_SESSION['feedback'].= g_feedback("error","Error unserializing...");
                } else {
                    $html .= $libhtml->render_table_row("Result",dump_array($object));
                }

            }

        }

        $html .= close_table();
        $html .= $libhtml->render_submit_button("decode", "Decode",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));
        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="time") {

        $html = $libhtml->form_start();
        $html .= open_table("600px","","action_form");
        $html .= $libhtml->render_form_table_row("unix_time",my_request("unix_time",time()),"Unix Time","unix_time");
        $html .= $libhtml->render_form_table_row("format",my_request("format",$user1->preferences->dateformat . " H:i:s"),"Date/Time Format","format");

        if (my_request("unix_time")!="") {
            $html .= $libhtml->render_table_row("Date/Time",date(my_request("format"),my_request("unix_time")));
        }

        $html .= close_table();
        $html .= $libhtml->render_submit_button("convert", "Convert",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));
        $html .= $libhtml->form_end();

        $html .= "<br/>";

        $html .= $libhtml->form_start();
        $html .= open_table("600px","","action_form");

        $html .= $libhtml->render_form_table_row("timestring",my_request("timestring"),"Time String","timestring");

        if (my_request("timestring")!="") {
            $time = strtotime(my_request("timestring"));
            $html .= $libhtml->render_table_row("Unix Time",$time);
            $html .= $libhtml->render_table_row("Date/Time",date("d M Y H:i:s",$time));
        }

        $html .= close_table();

        $html .= $libhtml->render_submit_button("convert", "Convert",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));

        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="ids") {

        $html = $libhtml->form_start();
        $html .= open_table('100%');

        $rules = get_ids_and_description_from_XML();
        (isset($_POST['utils_preg_subject'])) ? $utils_preg_subject = $_POST['utils_preg_subject'] : $utils_preg_subject="";
        $html .= $libhtml->render_form_table_row_text("utils_preg_subject",$utils_preg_subject,"Subject","utils_preg_subject");
        $preg_match = !empty($utils_preg_subject);
        $subject = $utils_preg_subject;

        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) $subject = stripslashes($subject);

        $filters = new \Expose\FilterCollection();
        $filters->load();

        //$logger = new \Expose\Log\Mongo();
        //$logger = new \Monolog\Logger('IDS');
        //$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(0, \Monolog\Logger::INFO));
        //$manager = new \Expose\Manager($filters, $logger);

        //$manager->run(array($subject));
        //var_dump($manager->export());
        //var_dump($manager->getImpact());
        //foreach ($manager->getReports() as $report) {
        //    var_dump($report);
        //    var_dump($report->toArray());
        //}

        // use the converter
        //$subject = IDS_Converter::runAll($subject);
        //$subject = IDS_Converter::runCentrifuge($subject);

        $ids_fired_check = false;

        if (!empty($preg_match)) {

            foreach ($rules as $rule) {

                $utils_preg_pattern = get_rule_from_id($rule->pattern);

                (preg_match('/' . $utils_preg_pattern . '/ms', strtolower($subject))) ? $result = "TRUE" : $result = "FALSE";
                if ($result == "TRUE") {
                    $html .= $libhtml->render_table_row("Rule $rule->pattern",$result);
                    $output = "";
                    preg_match_all('/' . $utils_preg_pattern . '/ms', strtolower($subject), $output);
                    //dump_var($output);
                    $html .= $libhtml->render_table_row("Description", $rule->description);
                    $html .= $libhtml->render_table_row("Impact", $rule->impact);
                    $html .= $libhtml->render_table_row("Matched Pattern(s)", "&quot;" . recursive_implode('&quot;<br /><br />&quot;', $output) ."&quot;");
//                    $libhtml->strlen(recursive_implode('&quot;<br />&quot;', $output));
                    $ids_fired_check = true;
                }
            }

            if ($ids_fired_check === false) {
                $html .= $libhtml->render_table_row("Matched Pattern(s)", "No Patterns Matched.");
            }

        }

        $html .= close_table();
        //Use raw html to deactivate execute_post_functions
        $html .= "<div class=\"actions\"><input type=\"submit\" name=\"preg_match\" id=\"preg_match\" value=\"Match\" class=\"submit\"></div>\n";
        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="psswd") {

        $password = '';

        $html .= $libhtml->form_start();
        $html .= open_table("800px");

        $html .= $libhtml->render_form_table_radio_selection("length",my_request("length",16),"Length","length",array(4,8,16,20,24,32,48,64), "", "");


        $strengths = array(
                1=>"Lower case characters and upper case consonants",
                2=>"Lower case characters and upper case vowels",
                3=>"Lower and upper case characters",
                4=>"Lower case characters and digits",
                5=>"Lower case characters, upper case consonants and digits",
                6=>"Lower case characters, upper case vowels and digits",
                7=>"Lower and upper case characters and digits",
                8=>"Lower case characters and special symbols",
                9=>"Lower case characters, special symbols and upper case consonants",
                10=>"Lower case characters, special symbols and upper case vowels",
                11=>"Lower and upper case characters and special symbols",
                12=>"Lower case characters, special symbols and digits",
                13=>"Lower case characters, upper case consonants, special symbols and digits",
                14=>"Lower case characters, upper case vowels, special symbols and digits",
                15=>"Lower and upper case characters, special symbols and digits",
        );
        $html .= $libhtml->render_form_table_row_selection("strength",my_request("strength",7),"Strength","strength",$strengths, "", "");

        $password = (my_post("generate_password")!="") ? generate_password(my_request("length"),my_request("strength")) : '';

        $html .= $libhtml->render_table_row("Generated Password:", $password);
        $html .= $libhtml->render_form_table_row_hidden("generate_password", "generate_password");

        $html .= close_table();
        //Use raw html to deactivate execute_post_functions
        $html .= "<div class=\"actions\"><input type=\"submit\" value=\"Generate Password\" class=\"submit\"></div>\n";

        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="filters") {

        $filter_input = (!empty($_POST['filter_input'])) ? $_POST['filter_input'] : '';

        foreach (filter_list() as $key => $value) $filters[filter_id($value)] = filter_id($value).": ".$value;

        $html .= $libhtml->form_start();
        $html .= open_table("800px");


        $html .= $libhtml->render_form_table_row("filter_input",$filter_input,"Input","filter_input");
        $html .= $libhtml->render_form_table_row_selection("filterx",my_request("filterx"),"Filter","filterx",$filters,"","");

        if (!empty($filter_input) && my_request("filterx")!=''){

            $result = filter_var($filter_input, my_request("filterx"));

            if ($result===FALSE) $result="FALSE";

            $html .= $libhtml->render_table_row("Result",$result);

        }

        $html .= close_table();
        //Use raw html to deactivate execute_post_functions
        $html .= "<div class=\"actions\"><input type=\"submit\" value=\"Run Filter\" class=\"submit\"></div>\n";

        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="android") {

        //$html = "<p>Generic Android app builder</p>";

    } elseif ($libhtml->tab=="functions") {

        $html .= $libhtml->form_start();
        $html .= open_table("800px");


        $html .= $libhtml->render_form_table_row_text("input",my_request('input'),"Input","input");
        $html .= $libhtml->render_form_table_radio_selection("function",my_request("function"),"Function","function",array(
                'strlen',
                'str_split',
                'utf8_encode',
                'utf8_decode',
                'base64_encode',
                'base64_decode',
                'parse_url',
                'parse_str',
                'htmlentities',
                'html_entity_decode',
                'htmlspecialchars',
                'htmlspecialchars_decode',

        ),"","",array('self_submit'=>true,'radio_break'=>2));

        if (my_request("function")!='' && function_exists(my_request("function"))){

            $function = my_request("function");

            $result = $function(my_request('input'));
            $text = (is_array($result)) ? dump_array($result) : $result;

            $html .= $libhtml->render_table_row("Result",$text);
        }


        $html .= $libhtml->form_end();

    } elseif ($libhtml->tab=="cron") {

        if(isset($my_get['delete'])){
            Crontab::removeLine($my_get['delete']);
        }

        exec('crontab -l',$output);
        $lines = array_filter($output, function($line) {
                           return '' != trim($line);
         });
        $crons = Array();
        $save  = '';
        foreach ($lines as $lineNumber => $line) {
                if (0 !== \strpos($line, '#', 0)) {
                       $line = Crontab::parse($line);
                }
                $crons['l'.$lineNumber] = $line;
        }
        foreach($crons as $command){
            if(is_a($command,'Crontab')){
                $save .= $command->getExpression().' '.$command->getCommand().PHP_EOL;
            }
        }

        if(isset($my_post['minute']) && isset($my_post['hour']) && isset($my_post['dayOfMonth']) && isset($my_post['dayOfWeek']) &&            isset($my_post['command']) ){
            $new = $my_post['minute'].' '.$my_post['hour'].' '.$my_post['dayOfMonth'].' '.$my_post['month'].' '.$my_post['dayOfWeek'].' '.html_entity_decode($my_post['command']).PHP_EOL;
            $crons['lnew'] =  Crontab::parse($new);
            $file = tempnam(sys_get_temp_dir(), 'cron');
                    file_put_contents($file, $save.$new);
                    exec('crontab '.$file);

        }

        $html = $libhtml->form_start();
        $html .= open_table("600px","","action_form");
        $html .= $libhtml->render_form_table_row("minute",'',"minute","minute");
        $html .= $libhtml->render_form_table_row("hour",'',"hour","hour");
        $html .= $libhtml->render_form_table_row("dayOfMonth",'',"dayOfMonth","dayOfMonth");
        $html .= $libhtml->render_form_table_row("month",'',"month","month");
        $html .= $libhtml->render_form_table_row("dayOfWeek",'',"dayOfWeek","dayOfWeek");
        $html .= $libhtml->render_form_table_row("command",'',"command","command");
        $html .= close_table();

        $html .= "<div class=\"actions\"><input type=\"submit\" value=\"Add cron job\" class=\"submit\"></div><div class=\"clear\"></div>";
        //dump_var($my_post);
        $html .= $libhtml->form_end();
        $html .= "<br/>";


        $html .= open_table("800px");
        foreach($crons as $key => $command){
            if(is_a($command,'Crontab')){
                $html .= $libhtml->render_table_row($command->getExpression(),$command->getCommand().'<div style="float:right;"><a href="'.encrypt_url('utilities.php?tab=cron&delete='.$key).'"><span class="ico_delete">&nbsp;</span></a></div>');
                $save .= $command->getExpression().' '.$command->getCommand().PHP_EOL;
            }
        }
        $html .= close_table();

    } elseif ($libhtml->tab=="pdf") {

        //Use raw $_POST - dangerous!

        $htmlx = (isset($_SESSION['original_post']['htmlx'])) ? $_SESSION['original_post']['htmlx'] : "";

        $html .= $libhtml->form_start();
        $html .= open_table('100%');
        $html .= $libhtml->render_form_table_row_hidden("html2pdf","html2pdf");
        $html .= $libhtml->render_form_table_row_text("htmlx",$htmlx,"HTML","htmlx",array('required'=>true));
        $html .= $libhtml->render_form_table_radio_selection("paper",my_request("paper"),"Orientation","paper",array("landscape","portrait"),"","",array('required'=>true));
        $html .= close_table();
        $html .= $libhtml->render_submit_button("convert", "Convert",array('show_cancel'=>false,"pause"=>false, 'post_functions'=>array()));
        $html .= $libhtml->form_end();

        if (my_request("html2pdf")!="" && my_request("paper")!="") {

            //dump_var(strlen($htmlx));

            //Output
            $dompdf = new DOMPDF();
            $dompdf->load_html($htmlx);
            $dompdf->set_paper("a4", my_request("paper"));
            $dompdf->render();
            $dompdf->stream("PDF_" . date("_Y-m-d_H-i-s") . ".pdf");

        }

    }

    $libhtml->render($html);

    //added by Alasdair to parse the XML and retrive the description
    function get_ids_and_description_from_XML() {
        global $cfg;
        $selection = array();

        $filters = new \Expose\FilterCollection();
        $filters->load();

        foreach ($filters as $filter) {
            $rules = (object) array(
                                    'name' => $filter->getID() . " - " . $filter->getDescription(),
                                    'pattern' => (int) $filter->getID(),
                                    'description' => (string) $filter->getDescription(),
                                    'impact' => (string) $filter->getImpact(),
                                    );
            $selection[]=$rules;

        }
        return $selection;
    }
    //added by Alasdair to parse the XML and retrive the rule as e.g. rule for filter id=5 would get truncated
    function get_rule_from_id($filter_id) {
        global $cfg;

        $filters = new \Expose\FilterCollection();
        $filters->load();

        foreach ($filters as $filter) {
            //$id = $nocache ? (string) $filter->id : $filter['id'];
            if ($filter->getID() == $filter_id) {
                return $filter->getRule();
                //return $nocache ? (string) $filter->rule : $filter['rule'];
                exit;
            }
        }
    }
