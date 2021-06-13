<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $path = explode("/", str_replace($cfg["root"],"",$_SERVER['HTTP_REFERER']));

    if (my_request("upload")) {
        $step = "display";
    } elseif (my_request("batch_upload")) {
        $step = "batch";
    } else {
        $step = "start";
    }

    if ($user1->logged_in){

        $class = my_request("class");

        if (!empty($class)) {
            if (!class_exists($class)) {
                error_log("Error: class $class does not exist.");
                exit;
            }

            $a = new $class;
            foreach($a->table_fields as $item) if ($item!=$a->object_pk) $fields[]=$item;
            $n = count($fields);
        }

        if ($step=="start") {

            //$form = "<h3>Upload Values</h3>\n";
            $form = "<p class=\"blue\">The field/s you requre is / are <i>" . implode(",",$fields) . "</i></p>\n";
            if (my_request("method")=="Text box") {
                $form .= "<p class=\"blue\">Copy and paste a list of comma-separated values, one row for each entry.</p>\n";
            } elseif (my_request("method")==".csv file upload") {
                $form .= "<p class=\"blue\">Upload a .csv file</p>\n";
            }

            $form .= $libhtml->form_start();
            $form .= open_table("","","action_form");
            $form .= $libhtml->render_form_table_row_hidden("class",my_request("class"));
            $form .= $libhtml->render_form_table_radio_selection("method",my_request("method"),"Method","method",array("Text box",".csv file upload"),"","",array('class'=>"self_submit"));
            if (my_request("method")=="Text box") {
                $form .= $libhtml->render_form_table_row_text("values",my_request("values"),"Values","values");
                $form .= "</table>\n<br/>\n";
                $form .= $libhtml->render_submit_button("upload", "Send", array("self_submit"=>true));
            } elseif (my_request("method")==".csv file upload") {
                $form .= $libhtml->render_form_table_row_file("file[file]", ".csv file", "", "temp/");
                //$libhtml->render_form_table_row_file("file", ".csv file", $this);
                $form .= close_table();
                $form .= $libhtml->render_submit_button("upload", "Upload file", array("self_submit"=>true));
            } else {
                $form .= close_table();
            }
            $form .= $libhtml->form_end();

        } elseif ($step=="display") {

            $form = $libhtml->form_start();
            $form .= open_table("","","details");
            $form .= "<tr><th style=\"width:25px\"/>";
            foreach($fields as $f) $form .= "<th>$f</th>";
            $form .= "</tr>";

            if (my_request("method")=="Text box") {

                $values = my_request("values");
                $values = explode('\n',str_replace(array('\r'),'',$values));
                foreach($values as $key=>$item) $values[$key] = explode(",",$item);

            } elseif (my_request("method")==".csv file upload") {
                if (($handle = fopen($cfg["secure_dir"] . $my_post["file"]["file"], "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) if (count($data)==$n) $values[] = $data;
                    fclose($handle);
                }
            }

            $row_count = 0;
            foreach($values as $item){
                if (count($item)==$n) {
                    $row_count++;
                    $row = ($row_count % 2 == 0) ? "even" : "odd";
                    $form .= "<tr class=\"$row\"><td class=\"row_id\">$row_count</td>";
                    foreach($fields as $key => $f) $form .= "<td>" . trim($item[$key]) . "</td>\n";
                    $form .= $libhtml->render_form_table_row_hidden(my_request("class") . "[$row_count][$f]",trim($item[$key]));
                    $form .= "\n</tr>\n";
                }
            }

            $form .= "</table>\n";
            $form .= $libhtml->render_form_table_row_hidden("class",my_request("class"));
            $form .= $libhtml->render_submit_button("batch_upload", "Upload All", array("self_submit"=>true));
            $form .= $libhtml->form_end();

        } elseif ($step=="batch") {
            $form = "";
            $data = my_request(my_request("class"));
            if (my_post("form_token")==$_SESSION['form_token']) {                                    //Check form token to prevent multiple submits
                if ((time() - $_SESSION['form_token_time'])<5*50) {                                  //Check the token was generated withing reasonable time
                    $count=0;
                    foreach($data as $item){
                        $function_feedback = "";
                        $object = new $class;                                                       //Create new instance of the class
                        $object->set_post($item);                                                     //Transfer POST array into new instance
                        if (!preg_match('/error/', $_SESSION['feedback'])) {
                            $function_feedback .= $object->insert();
                        } else {
                            $_SESSION['feedback'] .= g_feedback("error","ACTION ABORTED");
                        }
                        if ($db->rows > 0 && empty($function_feedback) && !preg_match('/error/',$_SESSION['feedback'])) {
                            $count++;
                        } else {
                            $_SESSION['feedback'] .= g_feedback("success",$function_feedback);
                        }
                    }
                    $_SESSION['feedback'] .= g_feedback("success","$count entr(y/ies) successfully inserted.");
                } else {
                    $_SESSION['feedback'] .= g_feedback("error","Your form has expired, maximum time is set to 5 minutes. Please try again");
                }
            } else {
                $_SESSION['feedback'] .= g_feedback("error","Multiple submits not allowed");
            }

        echo "<script type=\"text/javascript\">$(function(){ window.parent.location.reload(false); })</script>";
        }

         $db->close();
         $libhtml->render_form($form);
    }

?>
