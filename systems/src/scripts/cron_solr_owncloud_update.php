<?php

require_once __DIR__.DIRECTORY_SEPARATOR."../config/global.php";

$solr_owncloud_cron=new Solr_Owncloud_Cron();
$solr_owncloud_cron->run();
