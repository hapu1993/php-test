<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $fo = (object) array();
    $real_data = "";

    if ($user1->logged_in || my_get("public")) {
        // if the link does not require a user to be logged in
        if (!my_get("public")) {
            // server name must be in referer (i.e. it is a local access)
            $referer = getenv('HTTP_REFERER');
            $server_name = getenv('SERVER_NAME');
            //dump_var("Ref:".$referer); dump_var("SERV:".$server_name);
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
        } else {
            errorMsg("No file specified for download");
        }

        if (my_get("file_id")!="" && my_get("table")!="" && my_get("field")!="" && my_get("filename_field")!="") {
            $file_parts = explode("/", $fo->filename);
            $filename = $file_parts[count($file_parts) -1];
            $filename = substr(str_replace(" ","_",$filename),14, strlen($filename)-20);

            $secure = new Secure();
            echo $secure->echo_image($fo->file);
        }
    } else {

        errorMsg("You are not allowed to download this file.");

    }

    $db->close();

    function errorMsg($emsg) {
        echo '<p style="
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
                    ">Error: '.$emsg.'</p>';
        exit;
    }
