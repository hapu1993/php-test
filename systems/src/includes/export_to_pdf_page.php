<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $page_html = "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    $page_html .= "<head>\n";
    $page_html .= "<style>" . file_get_contents($cfg['root'] . "css/pdf.css") . "</style>";
    $page_html .= "</head>\n<body>\n";
    $page_html .= $_POST["html"];
    $page_html .= "</body>";
    $page_html .= "</html>";

    $page_html = preg_replace('/<img[^>]*?((alt="(.*?)".*?>)|>)/i', '$3', $page_html);
    $page_html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $page_html);

    $dompdf = new DOMPDF();
    $dompdf->load_html($page_html);
    $dompdf->set_paper("a4", "portrait");
    $dompdf->render();
    $dompdf->stream("page_" . date("_Y-m-d_H-i-s") . ".pdf");

    $db->insert(
        "system_log",
        array(
            'time' => date("Y-m-d H:i:s"),
            'user_id' => $user1->id,
            'object' => $a->object_name,
            'action' => "Export to PDF - Page",
            'comment' => gethostbyaddr($_SERVER['REMOTE_ADDR']) . " (" . $_SERVER['REMOTE_ADDR'] . ") " . "<br/>" . $_SERVER['HTTP_USER_AGENT']
        )
    );

    $db->close();
