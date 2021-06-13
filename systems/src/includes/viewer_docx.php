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


    error_reporting(E_ALL);

    $tmp_name = md5($file.filemtime($file)).'.pdf';

    if (!file_exists($cfg['imagecache'].$tmp_name)){

        $rendererName = PhpOffice\PhpWord\Settings::PDF_RENDERER_TCPDF;
        $rendererLibraryPath = realpath(__DIR__ . '/../../vendor/tecnickcom/tcpdf');
        if (\PhpOffice\PhpWord\Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {

            $phpWord = \PhpOffice\PhpWord\IOFactory::load($cfg['secure_dir'].$file);
            $docProps = $phpWord->getDocumentProperties();
            $docProps->setTitle(' ');
            $docProps->setCreator($cfg['client']);
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter( $phpWord, 'PDF' );

            $pdfWriter->save($cfg['imagecache'].$tmp_name);
        } else {
            error_log("Could not load renderer: $rendererName");
        }
    }

    $src = $cfg['root'].'js/pdf.js-1.1.469/web/viewer.html';
    $src .= '?file='.$cfg['imagecache_view'].$tmp_name;

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

    $libhtml->title = "Document Viewer for file ".$decoded_file_name;
    $libhtml->render_form($html);

    $db->close();
