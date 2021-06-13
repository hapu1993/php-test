<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    if ($user1->logged_in || my_get("public")) {

        // if the link does not require a user to be logged in
        if (!my_get("public")) {
            // server name must be in referer (i.e. it is a local access)
            $referer = getenv('HTTP_REFERER');
            $server_name = getenv('SERVER_NAME');
            if (!$referer || !$server_name || !preg_match(":$server_name:", $referer)) {
                errorMsg("You are not allowed to download this file");
                exit;
            }
        }

        if (my_get("file_id")!="" && my_get("table")!="" && my_get("field")!="") {

            $selection = $db->select_value(
                    my_get("field"),
                    my_get("table"),
                    array(
                            "WHERE id = ?",
                            array('id' => my_get("file_id")),
                            array('integer')
                    )
            );

            if (!empty($selection)) {
                $file_to_open = $selection;
            } else {
                errorMsg("Invalid file parameters");
                exit;
            }

        } else if (my_get("file_name") != "" ) {
            $file_to_open = my_get("file_name");
        } else {
            errorMsg("No file specified for download");
            exit;
        }

        // try for base64 names
        $file = basename($file_to_open);
        $filePath = $cfg['secure_dir'] . $file_to_open;

        //exclude special characters
        // if (preg_match(":(\.\.|^/|^\.):", $file)) {
            // errorMsg("Bad filename!");
            // exit;
        // }

        // allowed characters only (prevents use of meta characters)
        // allow alphanums, spaces, _, -, . and () brackets
        // no need to check this one at download stage!
        // if (! preg_match(":^[-A-Za-z0-9_\.\s+/\(\)\&]+$:", $file)) {
            // errorMsg("Bad filename!");
            // exit;
        // }

        // must be there:
        if (!file_exists($filePath)) {
            errorMsg("File does not exist");
            exit;
        }

        // and it should be readable:
        if (!is_readable($filePath)) {
            errorMsg("File is not readable");
            exit;
        }

        // switch off error reporting in browser in case this reveals our secretPath.
        // NB: on production system, error reporting should be turned off in php.ini file.
        $fd = fopen($filePath, "rb");

        // if it opened, then pass file through to browser:
        if ($fd) {

            // try for base64 names
            $filename = substr($file,14);
            if (base64_decode($filename,true) !== false) $filename = base64_decode($filename);

            $suffix = pathinfo($filename, PATHINFO_EXTENSION);

            if (function_exists('finfo_open')) {
                $finfo = finfo_open();
                $fileMime = finfo_file($finfo, $filePath, FILEINFO_MIME_TYPE);
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $fileMime = mime_content_type($filePath);
            } else {
                // Fallback, mimetypes not available using MAGIC file#
                // think IIS.
                $mime_types = array(
                    'csv' => 'text/csv',
                    'doc' => 'application/msword',
                    'odp' => 'application/vnd.oasis.opendocument.presentation',
                    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                    'odt' => 'application/vnd.oasis.opendocument.text',
                    'pdf' => 'application/pdf',
                    'pot' => 'application/vnd.ms-powerpoint',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    'xls' => 'application/vnd.ms-excel',
                );
                if (in_array($suffix,array_keys($mime_types))) $fileMime=$mime_types[$suffix];
            }
            $fileSize = filesize($filePath);

            $agent = getenv('HTTP_USER_AGENT');

            //Hack for recognising MS Office 2007 docs
            $ms_office_docs= array(
                    'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'dotx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                    'potm'=>'application/vnd.ms-powerpoint.template.macroEnabled.12',
                    'potx'=>'application/vnd.openxmlformats-officedocument.presentationml.template',
                    'ppam'=>'application/vnd.ms-powerpoint.addin.macroEnabled.12',
                    'ppsm'=>'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
                    'ppsx'=>'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                    'pptm'=>'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
                    'pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'xlam'=>'application/vnd.ms-excel.addin.macroEnabled.12',
                    'xlsb'=>'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
                    'xlsm'=>'application/vnd.ms-excel.sheet.macroEnabled.12',
                    'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'xltm'=>'application/vnd.ms-excel.template.macroEnabled.12',
                    'xltx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            );

            if (in_array($suffix,array_keys($ms_office_docs))) $fileMime=$ms_office_docs[$suffix];

            header("Cache-control: public");
            header("Pragma: public");
            header("Content-type: $fileMime");
            header("Content-length: $fileSize");
            header("Content-Disposition: attachment; filename=\"$filename\";");

            if (!fpassthru($fd)) {
                errorMsg("Problem in file transfer.");
                exit;
            }

            if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) error_log("User ID:".$user1->id."; File path: ".$filePath."; Document:".$filename."; MIME:".$fileMime);

            fclose($fd);

        } else {

            errorMsg("Problem opening file.");
            exit;

        }

    } else {

        if (!$user1->logged_in){
            header("Location: ".$cfg['root']."logout.php");
            exit;

        } else {
            errorMsg("You are not allowed to download this file.");
            exit;

        }

    }

    $db->close();

    function errorMsg($emsg) {
        echo $emsg;
        exit;
    }
