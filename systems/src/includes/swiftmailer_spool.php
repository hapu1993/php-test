<?php
    require_once dirname(__FILE__).'/../config/global.php';

    try {

        $spool = new Swift_FileSpool($cfg['secure_dir'] . "swiftmailer_spool");
        $transport = Swift_SpoolTransport::newInstance($spool);
        $realTransport = Swift_SmtpTransport::newInstance($cfg['smtp']);
        $realTransport->setUsername($cfg['smtp_user']);
        $realTransport->setpassword($cfg['smtp_pass']);
        if (isset($cfg['smtp_port'])) $realTransport->setPort($cfg['smtp_port']);
        if (isset($cfg['smtp_encryption'])) $realTransport->setEncryption($cfg['smtp_encryption']);

        $logger = new Swift_Plugins_Loggers_ArrayLogger();
        $realTransport->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
        $failedRecipients = null;

        $spool = $transport->getSpool();
        $spool->setMessageLimit(10);
        $spool->setTimeLimit(200);
        $sent = $spool->flushQueue($realTransport);

        $log = $logger->dump();
    } catch (Exception $e) {
        $log = $logger->dump();
        error_log("Swift error: ".print_r($e->getMessage(), true));
        error_log($e->getTraceAsString());
        error_log(print_r($log, true));
    }
