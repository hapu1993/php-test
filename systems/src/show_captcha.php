<?php
    if ( !file_exists(dirname(__FILE__).'/config/global.php') ) {
        error_log("Please create a global.php file at location: " . dirname(__FILE__) . "/config/global.php");
        include_once(dirname(__FILE__) . "/missing_config_file.html");
        die;
    } else {
        require_once dirname(__FILE__).'/config/global.php';
    }
    require_once $cfg['source_root'] . "includes/common_plain_includes.php";

    $options = array();
    if (isset($cfg['captcha']) && is_array($cfg['captcha'])) {
        $options = $cfg['captcha'];
    }

    if (isset($options['type']) && in_array($options['type'], array('string', 'maths', 'words'))) {
        switch ($options['type']) {
            case 'string':
                $options['captcha_type'] = Securimage::SI_CAPTCHA_STRING;
                unset($options['type']);
                break;
            case 'maths':
                $options['captcha_type'] = Securimage::SI_CAPTCHA_MATHEMATIC;
                unset($options['type']);
                break;
            case 'words':
                $options['captcha_type'] = Securimage::SI_CAPTCHA_WORDS;
                unset($options['type']);
                break;
            default:
                $options['captcha_type'] = Securimage::SI_CAPTCHA_STRING;
                break;
        }
    }

    $securimage = new Securimage($options);
    $securimage->session_name = session_name();
    $securimage->show();
