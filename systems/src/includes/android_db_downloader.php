<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    if ($user1->logged_in || my_get("public")) {

        // if the link does not require a user to be logged in
        if (!my_get("public")) {
            // server name must be in referer (i.e. it is a local access)
            $referer = getenv('HTTP_REFERER');
            $server_name = getenv('SERVER_NAME');
            if (!$referer || !$server_name || !preg_match(":$server_name:", $referer)) errorMsg("You are not allowed to download this file");
        }

        $allowedChars = '-A-Za-z0-9_\.\s+/';

        if (my_get("file_id")!="" && my_get("table")!="" && my_get("field")!="") {
            $select = $db->select(my_get("field"), my_get("table"),array("WHERE id = ?", array('id' => my_get("file_id")), array('integer')));
            (count($select)==1) ? $fo = $select[0] : errorMsg("Invalid file parameters");
        } else if (my_get("file_name") != "" ) {
            $fo->file = my_get("file_name");
        } else {
            errorMsg("No file specified for download");
        }

        $file = basename($fo->file);
        $filePath = $cfg['secure_dir'] . $fo->file;

        //exclude special characters
        if (preg_match(":(\.\.|^/|^\.):", $file)) errorMsg("Bad filename!");

        // allowed characters only (prevents use of meta characters):
        if (! preg_match(":^[-A-Za-z0-9_\.\s+/]+$:", $file)) errorMsg("Bad filename!");

        // must be there:
        if (!file_exists($filePath)) errorMsg("File <b>" . $file . "</b> does not exist");

        // and it should be readable:
        if (!is_readable($filePath)) errorMsg("File <b>" . $file . "</b> is not readable");

        // switch off error reporting in browser in case this reveals our secretPath.
        // NB: on production system, error reporting should be turned off in php.ini file.
        $old_error = error_reporting(0);
        $fd = fopen($filePath, "rb");
        error_reporting($old_error);

        // if it opened, then pass file through to browser:
        if ($fd) {
            // lookup mime type; first extract filename suffix:
            preg_match(":\.([^\.]+)$:", $file, $matches);
            $suffix = strtolower($matches[1]);
            if (!isset($mimeTypes[$suffix])) $suffix = 'data'; // unknown file type, so send as default (binary)

            $filename = str_replace(" ","_",$file);
            $finfo = finfo_open();
            $fileMime = finfo_file($finfo, $filePath, FILEINFO_MIME_TYPE);
            $fileSize = filesize($filePath);

            $agent = getenv('HTTP_USER_AGENT');
            (preg_match(":MSIE:", $agent)) ? $ctype = "attachment;" : $ctype = "";

//             header("Cache-control: public");
//             header("Pragma: public");
//             header("Content-type: $fileMime");
//             header("Content-length: $fileSize");
//             header("Content-Disposition: $ctype filename=$filename;");
            header("Content-Type: application/octet-stream");
            header("Content-length: $fileSize");
            header("Content-Disposition: attachment; filename=\"$filename\"");

            if (!fpassthru($fd)) errorMsg("problem transferring file!");
            fclose($fd);

        } else {
            errorMsg("Problem opening file");
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
