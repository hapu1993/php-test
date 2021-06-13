<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $file = urldecode(my_get("file_name"));

    $base_file_name = substr(basename($file),14);

    //Test for base64 encoding; use . as a test of a "good" filename
    if (base64_decode($base_file_name,true) !== false){
        $decoded_file_name = base64_decode($base_file_name);
    } elseif (strpos($base_file_name,'.')!==false){
        $decoded_file_name = $base_file_name;
    } else {
        $decoded_file_name = 'File name ERROR';
    }

    $libhtml->title = "PDF Viewer for file ".$decoded_file_name;

    $src = $cfg['root'].'js/pdf.js-1.1.469/web/viewer.html';
    $src .= '?file='.encrypt_url($cfg['root'] . "includes/downloader.php?file_name=".urlencode($file));

    $html = '<iframe src="'.$src.'" style="width:100%;max-height:800px !important;padding:0px;"></iframe>';

    $libhtml->js.='

            <script language="JavaScript" type="text/javascript">
            $(function(){
                $("div.jbox_content").css("padding","0px");
                $("div.jbox_content_inner").css("padding","0px");
                $("div.jbox_content_inner iframe").css("height",$("body").height()-150);
            });
        </script>
    ';

    $libhtml->render_form($html);

    $db->close();
