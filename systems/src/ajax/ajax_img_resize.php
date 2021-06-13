<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    if (empty($_GET["file"])) {

        $file_name = $cfg["secure_dir"].my_post("file_name");

        $phpThumb = new phpThumb();
        $phpThumb->setSourceFilename($file_name);
        $phpThumb->setParameter('config_allow_src_above_docroot', true);
        if (isset($my_post["width"]) && is_numeric($my_post["width"])) $phpThumb->setParameter('w', my_post("width"));
        if (isset($my_post["height"]) && is_numeric($my_post["height"])) $phpThumb->setParameter('h', my_post("height"));
        $output_filename = $file_name;
        $phpThumb->RenderToFile($output_filename);
        if ($phpThumb->GenerateThumbnail()) $phpThumb->RenderToFile($output_filename);

    } else {

        $html .= "<div class=\"jbox_content\">\n";
            $html .= "<form class=\"image_resize\" action=\"#\">\n";
                $html .= "<label>Width:</label>\n";
                $html .= "<input class=\"width\" type=\"text\" name=\"width\" />\n";
                $html .= "<div class=\"clear\">\n";
                $html .= "<label><b>OR</b> Height:</label>\n";
                $html .= "<input class=\"height\" type=\"text\" name=\"height\" />\n";
                $html .= "<input class=\"file_name\" type=\"hidden\" name=\"file_name\" value=\"" . $_GET["file"] . "\" />\n";
                $html .= "<div class=\"clear\">\n";
                $html .= "<button data-file-resize-action=\"true\" class=\"btn blue\" value=\"Resize\">Resize</button>\n";
            $html .= "</form>\n";
        $html .= "</div>\n";

        echo $html;

    }

}

$db->close();
?>
