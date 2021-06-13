<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    $file_name = $cfg["secure_dir"].my_request("file_name");

    $phpThumb = new phpThumb();
    $phpThumb->setSourceFilename($file_name);
    $phpThumb->setParameter('config_allow_src_above_docroot', true);
    $phpThumb->setParameter('ra', "-90");
    $output_filename = $file_name;
    $phpThumb->RenderToFile($output_filename);

    if ($phpThumb->GenerateThumbnail()) $phpThumb->RenderToFile($output_filename);

}

$db->close();
?>
