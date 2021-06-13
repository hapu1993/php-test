<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $fo = (object) array();
    $real_data = "";
//     dump_var($my_get);
    if ($user1->logged_in || my_get("public")) {

        // if the link does not require a user to be logged in
        if (!my_get("public")) {
            // server name must be in referer (i.e. it is a local access)
            $referer = getenv('HTTP_REFERER');
            $server_name = getenv('SERVER_NAME');
            if (!$referer || !$server_name || !preg_match(":$server_name:", $referer)) errorMsg("You are not allowed to download this file");
        }

        $allowedChars = '-A-Za-z0-9_\.\s+/';

        if (my_get("file_id")!="" && my_get("table")!="" && my_get("field")!="" && my_get("filename_field")!="") {
            $select = $db->select(my_get("filename_field") . ", " . my_get("field"), my_get("table"),array("WHERE id = ?", array('id' =>my_get("file_id")), array('integer')));
            if (count($select)==1) {
                $extracted_file = $select[0];
                $fo->filename = $extracted_file->{my_get("filename_field")};
                $fo->file = $extracted_file->{my_get("field")};
            } else {
                errorMsg("Invalid file parameters");
            }
        } else if (my_get("file_name") != "" ) {
            $fo->file = my_get("file_name");
        } else {
            errorMsg("No file specified for download");
        }

        if (my_get("file_name") != "" ) {
            $file = basename($fo->file);
            $filePath = $cfg['secure_dir'] . $fo->file;

            //exclude special characters
            if (preg_match(":(\.\.|^/|^\.):", $file)) errorMsg("Bad filename!");

            // allowed characters only (prevents use of meta characters):
            if (! preg_match(":^[-A-Za-z0-9_\.\s+/]+$:", $file)) errorMsg("Bad filename!");

            // must be there:
            if (!file_exists($filePath)) errorMsg("File <b>" . substr($file,14, strlen($file)-20) . "</b> does not exist");

            // and it should be readable:
            if (!is_readable($filePath)) errorMsg("File <b>" . substr($file,14, strlen($file)-20) . "</b> is not readable");

            // switch off error reporting in browser in case this reveals our secretPath.
            // NB: on production system, error reporting should be turned off in php.ini file.
            $old_error = error_reporting(0);
            $fd = fopen($filePath, "rb");
            // if it opened, get the real contents:
            if ($fd) {
                //decrypt
                $secure = new Secure();
                $real_data = $secure->extract_file(file_get_contents($filePath));
            }
            error_reporting($old_error);

            // if it opened, then pass file through to browser:
            if ($fd) {
                // lookup mime type; first extract filename suffix:
                preg_match(":\.([^\.]+)$:", $file, $matches);
                $suffix = strtolower($matches[1]);
                if (!isset($mimeTypes[$suffix])) $suffix = 'data'; // unknown file type, so send as default (binary)

                //remove crypt extentsion
                $filename = substr(str_replace(" ","_",$file),14, strlen($file)-20);

                $finfo = finfo_open();
                $fileMime = finfo_file($finfo, $filePath, FILEINFO_MIME_TYPE);
                $fileSize = filesize($filePath);

                $agent = getenv('HTTP_USER_AGENT');
                $ctype = "attachment;";

                header("Cache-control: public");
                header("Pragma: public");
                header("Content-type: $fileMime");
                header("Content-length: $fileSize");
                 header("Content-Description: File Transfer");
                header("Content-Disposition: $ctype filename=$filename;");

                echo $real_data;

                if (!fpassthru($fd)) errorMsg("problem transferring file!");
                fclose($fd);

            } else {
                errorMsg("Problem opening file");
            }
        } elseif (my_get("file_id")!="" && my_get("table")!="" && my_get("field")!="" && my_get("filename_field")!="") {
            $file_parts = explode("/", $fo->filename);
            $filename = $file_parts[count($file_parts) -1];
            $filename = substr(str_replace(" ","_",$filename),14, strlen($filename)-20);

            $agent = getenv('HTTP_USER_AGENT');
            $ctype = "attachment;";
            $secure = new Secure();
            $file_data =  $secure->extract_file($fo->file);

            if (is_null($file_data) || empty($file_data)) {
                errorMsg("Unable to retrieve file, are you sure you have permission");
            } else {
                header("Cache-control: public");
                header("Pragma: public");
                //header("Content-type: $fileMime");
                //header("Content-length: $fileSize");
                header("Content-Disposition: $ctype filename=$filename;");
                //             echo strlen($fo->file);
                echo $file_data;
            }
        }
    } else {

        errorMsg("You are not allowed to download this file.");

    }

    $db->close();

    function errorMsg($emsg) {
        echo "<p style=\"
                        font-family: Verdana, Helvetica, Arial;
                        font-size: 13px;
                        background: #FDD5CE;
                        border: 1px solid #D8020E;
                        border-radius: 5px 5px;
                        clear: both;
                        color: #D8020E;
                        font-weight: bold;
                        margin: 10px 0px;
                        padding: 30px;
                    \">Error: $emsg</p>";
        exit;
    }
?>
