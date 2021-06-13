<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    if ($user1->logged_in && ($user1->user_groups==array("0"))){

            $result = $db->_query("SHOW VARIABLES");
            $select = array();
            while ($row = $db->_fetch($result)) $select[$row->Variable_name] = $row->Value;

            header('content-type: application/x-gzip');
            header("content-disposition: attachment; filename=\"" . $cfg['dbname'] . date("_Y-m-d_H-i-s") . ".sql.gz\"");
            header('Pragma: hack');

            (empty($cfg['dbpass'])) ? $pass="" : $pass = " -p " . $cfg['dbpass'] . " ";
            $command = $select['basedir'] . "bin/mysqldump --opt -h " . $cfg['dbhost'] . " -u " . $cfg['dbuser'] . $pass . " " . $cfg['dbname'] . " | gzip ";
            $output = null;

            passthru($command, $output);
            ob_start();
            passthru($command, $output);
            $content=ob_get_contents();
            ob_end_clean();
            echo $content;
    }

    $db->close();

?>
