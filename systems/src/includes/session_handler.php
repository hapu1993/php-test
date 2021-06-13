<?php 
//Error handler functions
function DevErrorHandler($errno, $errstr, $errfile, $errline){

    global $cfg, $libhtml;

    if ($errno == E_RECOVERABLE_ERROR) {
        return false;
    }

    error_log("$errno: $errstr in $errfile [$errline]");
    if (!isset($_SESSION['errors'])) $_SESSION['errors']=null;

    $html = "<p><b>PHP Error: $errstr<br /></b>\n";
    $html .= "User warning [$errno] on line $errline in file $errfile.";

    $debug = debug_backtrace();

    if (!empty($debug) && is_array($debug)){

        $html .= "<br/><br/>";

        foreach($debug as $k=>$v){
            if (empty($v['file'])) $v['file']='';
            if (empty($v['line'])) $v['line']='0';
            if(!empty($v['args']) && in_array($v['function'], array("include", "include_once", "require_once", "require"))){
                $html .= "#".$k." <b>".$v['function']."(".$v['args'][0].")</b> called at [".$v['file'].":<b>".$v['line']."]</b><br />";
            }else{
                $html .= "#".$k." <b>".$v['function']."()</b> called at [".$v['file'].":<b>".$v['line']."]</b><br />";
            }
             $html .= "------<br/>";
        }
    }

    $html .= "</p>\n";

    $_SESSION['errors'][md5($errno.$errstr.$errfile.$errline)]['html'] = $html;
    $_SESSION['errors'][md5($errno.$errstr.$errfile.$errline)]['errstr'] = $errstr;

    /* Don't execute PHP internal error handler */
    return true;
}

function ProdErrorHandler($errno, $errstr, $errfile, $errline){
    global $cfg, $libhtml, $db;

    if ($errno == E_RECOVERABLE_ERROR) {
        return false;
    }

    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        error_log("[" . $db->database . "] Unreported error [$errno]: $errstr in $errfile [$errline]");
        return;
    }

    error_log("[" . $db->database . "] $errno: $errstr in $errfile [$errline]");

    /* Don't execute PHP internal error handler */
    return true;
}

//Fatal error handler functions
function fatalErrorHandler() {
    global $cfg;
    $error = error_get_last();
    if(($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR)){
        header("HTTP/1.1 500 Server Error");
        readfile($cfg['source_root'] . "/500.html");
    //error_log(print_r($error, true));
    //error_log(print_r(debug_backtrace(), true));
    exit();
    }
/*
    if(($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR)){
        $_SESSION['error'] = $error;
        header("Location:".$cfg['root']."exception/");
        exit;
    }
*/
}

set_exception_handler(function (Exception $e) {
    global $db, $cfg;

    if (!is_null($db)) {
        $db->close();
    }

    // Does not log on Windows!!
    error_log("UNCAUGHT EXCEPTION " . $e->getMessage());
    error_log($e->getTraceAsString());

    header("HTTP/1.1 500 Server Error");
    readfile($cfg['source_root'] . "/500.html");
    die;
});

//Set the error handler
$my_error_handler = ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) ? set_error_handler("DevErrorHandler"): set_error_handler("ProdErrorHandler");

//Register exception handler
register_shutdown_function('fatalErrorHandler');

//Start page timing
$page_start_time = microtime(true);

//Start new session
$session = Session_DB::get_instance();
