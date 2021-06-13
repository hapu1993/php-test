<?php

class jMinifier {

    public static function minify($js_files){
        global $cfg;

        $unique_filename = "";
        foreach($js_files as $js_file) {
            $unique_filename .= md5_file($js_file);
        }
        $unique_filename = "js/min".substr(md5($unique_filename), 0, 10) . ".js";
        if (file_exists($cfg["source_root"].$unique_filename)) {
            return '<script type="text/javascript" src="' . $cfg["root"] . $unique_filename . '"></script>';

        } else {

            // remove all previous files
            jMinifier::remove();

            // localy, the folder is probably writeable, and write will be successful
            // live, if the folder is not writeable, check if file already exists; if it doesn't, return file by file
            if (is_writable($cfg["source_root"] . "js")) {
                // minify each, combine
                $js_as_string = "";
                foreach($js_files as $js_file) {
                    if (filemtime($js_file) !== false) {
                        if (strpos($js_file, "min") === false) $js_as_string .= jMinifier::build(file_get_contents($js_file));
                        else $js_as_string .= file_get_contents($js_file);
                    }
                }

                $success = file_put_contents($cfg["source_root"] . $unique_filename, $js_as_string);
                return '<script type="text/javascript" src="' . $cfg["root"] . $unique_filename . '"></script>';
            } else {
                error_log("jMinifier: js directory is not writable.  Cannot save $unique_filename.");
                if (!file_exists($cfg["source_root"] . $unique_filename)) {
                    $html = "";
                    foreach($js_files as $js_file) $html .= '<script type="text/javascript" src="' . str_replace($cfg["source_root"], $cfg["root"], $js_file) . '"></script>';
                    return $html;
                } else {
                    return '<script type="text/javascript" src="' . $cfg["root"] . $unique_filename . '"></script>';
                }
            }
        }
    }

    public static function remove($keep = ""){
        global $cfg;
        foreach (glob($cfg["source_root"] . "js/min*.js") as $filename) {
            if ($cfg["source_root"] . $keep != $filename) unlink($filename);
        }
    }

    public static function build($str){
        $res = '';
        $maybe_regex = true;
        $i=0;
        $current_char = '';
        while ($i+1<strlen($str)) {
            if ($maybe_regex && $str[$i]=='/' && $str[$i+1]!='/' && $str[$i+1]!='*' && @$str[$i-1]!='*') {//regex detected
                if (strlen($res) && $res[strlen($res)-1] === '/') $res .= ' ';
                do {
                    if ($str[$i] == '\\') {
                        $res .= $str[$i++];
                    } elseif ($str[$i] == '[') {
                        do {
                            if ($str[$i] == '\\') {
                                $res .= $str[$i++];
                            }
                            $res .= $str[$i++];
                        } while ($i<strlen($str) && $str[$i]!=']');
                    }
                    $res .= $str[$i++];
                } while ($i<strlen($str) && $str[$i]!='/');
                $res .= $str[$i++];
                $maybe_regex = false;
                continue;
            } elseif ($str[$i]=='"' || $str[$i]=="'") {//quoted string detected
                $quote = $str[$i];
                do {
                    if ($str[$i] == '\\') {
                        $res .= $str[$i++];
                    }
                    $res .= $str[$i++];
                } while ($i<strlen($str) && $str[$i]!=$quote);
                $res .= $str[$i++];
                continue;
            } elseif ($str[$i].$str[$i+1]=='/*' && @$str[$i+2]!='@') {//multi-line comment detected
                $i+=3;
                while ($i<strlen($str) && $str[$i-1].$str[$i]!='*/') $i++;
                if ($current_char == "\n") $str[$i] = "\n";
                else $str[$i] = ' ';
            } elseif ($str[$i].$str[$i+1]=='//') {//single-line comment detected
                $i+=2;
                while ($i<strlen($str) && $str[$i]!="\n" && $str[$i]!="\r") $i++;
            }

            $LF_needed = false;
            if (preg_match('/[\n\r\t ]/', $str[$i])) {
                if (strlen($res) && preg_match('/[\n ]/', $res[strlen($res)-1])) {
                    if ($res[strlen($res)-1] == "\n") $LF_needed = true;
                    $res = substr($res, 0, -1);
                }
                while ($i+1<strlen($str) && preg_match('/[\n\r\t ]/', $str[$i+1])) {
                    if (!$LF_needed && preg_match('/[\n\r]/', $str[$i])) $LF_needed = true;
                    $i++;
                }
            }

            if (strlen($str) <= $i+1) break;

            $current_char = $str[$i];

            if ($LF_needed) $current_char = "\n";
            elseif ($current_char == "\t") $current_char = " ";
            elseif ($current_char == "\r") $current_char = "\n";

            // detect unnecessary white spaces
            if ($current_char == " ") {
                if (strlen($res) &&
                    (
                    preg_match('/^[^(){}[\]=+\-*\/%&|!><?:~^,;"\']{2}$/', $res[strlen($res)-1].$str[$i+1]) ||
                    preg_match('/^(\+\+)|(--)$/', $res[strlen($res)-1].$str[$i+1]) // for example i+ ++j;
                    )) $res .= $current_char;
            } elseif ($current_char == "\n") {
                if (strlen($res) &&
                    (
                    preg_match('/^[^({[=+\-*%&|!><?:~^,;\/][^)}\]=+\-*%&|><?:,;\/]$/', $res[strlen($res)-1].$str[$i+1]) ||
                    (strlen($res)>1 && preg_match('/^(\+\+)|(--)$/', $res[strlen($res)-2].$res[strlen($res)-1])) ||
                    (strlen($str)>$i+2 && preg_match('/^(\+\+)|(--)$/', $str[$i+1].$str[$i+2])) ||
                    preg_match('/^(\+\+)|(--)$/', $res[strlen($res)-1].$str[$i+1])// || // for example i+ ++j;
                    )) $res .= $current_char;
            } else $res .= $current_char;

            // if the next charachter be a slash, detects if it is a divide operator or start of a regex
            if (preg_match('/[({[=+\-*\/%&|!><?:~^,;]/', $current_char)) $maybe_regex = true;
            elseif (!preg_match('/[\n ]/', $current_char)) $maybe_regex = false;

            $i++;
        }
        if ($i<strlen($str) && preg_match('/[^\n\r\t ]/', $str[$i])) $res .= $str[$i];
        return $res;
    }
}
?>
