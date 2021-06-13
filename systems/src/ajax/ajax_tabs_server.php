<?php
require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

if ($user1->logged_in) {

    // linux section
    if (my_get("tab") == "Linux_OS") {

        // OS
        $output = null;
        $html  .= "<div class=\"table_wrap clearfix left\" style=\"width:90%;margin: 10px;\"><table class=\"action_form details_form\" style=\"width:100%;\">";

        exec('hostname', $output);
        $html .= $libhtml->render_table_row("Hostname",$output[0]);
        $html .= $libhtml->render_table_row("Webserver Name",getenv('SERVER_NAME'));

         $output=null;
         exec('sysctl -n kernel.ostype', $output);
         if (!empty($output)) $html .= $libhtml->render_table_row("OS Type",$output[0]);

         $output=null;
         exec('sysctl -n kernel.osrelease', $output);
         if (!empty($output)) $html .= $libhtml->render_table_row("OS Release",$output[0]);

         $output=null;
         exec('lsb_release -a', $output);
         foreach($output as $item){
             $row = explode(":",$item);
             $html .= $libhtml->render_table_row(trim($row[0]),trim($row[1]));
         }

         $output=null;
         exec('uptime', $output);
         $html .= $libhtml->render_table_row("Uptime",$output[0]);
         $html .= $libhtml->render_table_row("Apache Version",apache_get_version());
         $html .= $libhtml->render_table_row("Apache Modules",implode(", ",apache_get_modules()));
         $html  .= "</table></div>\n";
         echo $html;

    } else if (my_get("tab") == "Linux_All_Processes") {

        $output = null;
         exec('ps auxf', $output);
        echo make_table($output);

    } else if (my_get("tab") == "Linux_Top_Memory") {

         $output = null;
         exec('ps -auxf | sort -nr -k 4 | head -10', $output);
         array_unshift($output,"USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND");
         echo make_table($output);

    } else if (my_get("tab") == "Linux_Top_CPU") {

        $output = null;
         exec('ps -auxf | sort -nr -k 3 | head -10', $output);
         array_unshift($output,"USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND");
         echo make_table($output);

    } else if (my_get("tab") == "Linux_Users") {

        $output = null;
         exec('w', $output);
         array_shift($output);
         echo make_table($output);


    } else if (my_get("tab") == "Linux_Memory") {

        $output = null;
         exec('free', $output);
         $output[0] = "* " . $output[0];
         $output[2] = str_replace("-/+ buffers/cache:","-/+buffers/cache: * ",$output[2]);
         $html = make_table($output);

         //More memory
         $output = null;
         $filename = "/proc/meminfo";
         if (file_exists($filename)) {
             if ($fd = fopen($filename, 'r')) {
                 while (!feof($fd)) $output[] = fgets($fd);
                 fclose($fd);
             }
         }
         array_unshift($output,"Item:Size");
         $html .= "<br/><br/>" . make_table($output,":");
         echo $html;

    } else if (my_get("tab") == "Linux_System_Info") {

        $output = null;
         exec('`which vmstat`', $output);
         array_shift($output);
         $html = make_table($output);

         $output = null;
         exec('`which sysctl` -a', $output);
         if (!empty($output)) {
            foreach($output as $item) $out2[] = str_replace("="," ",$item);
            array_unshift($out2,"Variable Value");
            $html .= "<br/>" . make_table($out2);
         }

         echo $html;

    } else if (my_get("tab") == "Linux_Network") {

        $output = null;
         exec('netstat -ni | tail -n +2', $output);
         $html = make_table($output);

         //More network
         $output = null;
         $filename = "/proc/net/dev";
         if (file_exists($filename)) {
             if ($fd = fopen($filename, 'r')) {
                 while (!feof($fd)) {
                     $output[] = fgets($fd);
                 }
                 fclose($fd);
             }
         }
         array_shift($output);
         $html .= "<br/><br/>" . make_table($output);

         echo $html;

    } else if (my_get("tab") == "Linux_Filesystem") {

        $output = null;
         exec('df -k', $output);
         echo make_table($output);

    } else if (my_get("tab") == "Linux_Hardware") {

         $output = null;
         exec('lspci', $output);
         //array_unshift($output,"");
         $html = make_simple_table($output) . "<br/><br/>";

         $output = null;
         exec('lspci -vvv', $output);
         $out2=array();

         foreach($output as $item){
             if (trim($item)==""){
                 $html .= make_simple_table($out2) . "<br/><br/>";
                 $out2 = array();
             } else {
                 $out2[] = $item;
             }
         }
         echo $html;

    } else if (my_get("tab") == "Linux_CPU") {

         $output = null;
         $filename = "/proc/cpuinfo";
         if (file_exists($filename)) {
             if ($fd = fopen($filename, 'r')) {
                 while (!feof($fd)) $output[] = fgets($fd);
                 fclose($fd);
             }
         }
         if (!empty($output)){
             $table = null;
             foreach($output as $item) {
                 if (trim($item)=="" && !empty($table)) {
                     //$html .= "<h3>$table[0]</h3><br/>\n";
                     $title = $table[0];
                     $table[0] = "Property:Value";
                     $html .= make_table($table,":","100%",$title) . "<br/><br/>";
                     $table = null;
                 } else {
                     $table[]=$item;
                 }
             }
         }
         echo $html;

    }

    // win section
    if (my_get("tab") == "Windows_OS") {
        $output = null;
        $html  .= "<table class=\"action_form details_form\" style=\"width:100%;\">";
        exec('hostname', $output);
        $html .= $libhtml->render_table_row("Hostname",$output[0]);
        $html .= $libhtml->render_table_row("Webserver Name",getenv('SERVER_NAME'));

         $output=null;
         if (!isset($_SESSION['windows_system_info'])) exec('Systeminfo', $_SESSION['windows_system_info']);
         $output = $_SESSION['windows_system_info'];

         $memory_keys = array_ereg_search('Memory', $output);
         $hotfix_key = array_ereg_search('otfix', $output);
         $network_key = array_ereg_search('etwork Card', $output);
//         dump_var($network_key);
//         dump_var($output);

        $html .= $libhtml->render_table_row("System Boot Time",substr($output[11], 27));
         $html .= $libhtml->render_table_row("System Manufacturer",substr($output[12], 27));
         $html .= $libhtml->render_table_row("System Model",substr($output[13], 27));
         $html .= $libhtml->render_table_row("System Type",substr($output[14], 27));
        if (isset($_SESSION['RISK_USER_OS_GENERIC']) && $_SESSION['RISK_USER_OS_GENERIC'] == "win") $html .= $libhtml->render_table_row("Client OS Type","Microsoft Windows");
        if (isset($_SESSION['RISK_USER_OS_GENERIC']) && $_SESSION['RISK_USER_OS_GENERIC'] == "win") $html .= $libhtml->render_table_row("Client OS Release",$_SESSION['RISK_USER_OS']);
         $html .= $libhtml->render_table_row("OS Full Name",substr($output[2], 27));
         $html .= $libhtml->render_table_row("OS Version",substr($output[3], 27));

         //windows date is dd/mm/yyyy, H:i:s
         $date_array = explode('/', substr($output[11], 27, 10));
         $time = substr($output[11], 39);
         $boottime = new datetime($date_array[2] . "-" . $date_array[1] . "-" . $date_array[0] . " " . $time);
         $now = new datetime(date("Y-m-d H:i:s"));
         $uptime = date_diff($boottime, new datetime());
         $uptime_text = "";
         if ($uptime->y != 0) $uptime_text .= $uptime->y . "years, ";
         if ($uptime->m != 0) $uptime_text .= $uptime->m . "months, ";
         if ($uptime->d != 0) $uptime_text .= $uptime->d . "days, ";
         if ($uptime->h != 0) $uptime_text .= $uptime->h . "hours, ";
         if ($uptime->i != 0) $uptime_text .= $uptime->i . "minutes, ";
         if ($uptime->s != 0) $uptime_text .= $uptime->s . "seconds";

         $html .= $libhtml->render_table_row("Uptime", $uptime_text);
         $html .= $libhtml->render_table_row("Apache Version",apache_get_version());
         $html .= $libhtml->render_table_row("Apache Modules",implode(", ",apache_get_modules()));

         $html  .= "</table>\n";
        echo $html;

    } else if (my_get("tab") == "Windows_Memory") {

        $output=null;
         if (!isset($_SESSION['windows_system_info'])) exec('Systeminfo', $_SESSION['windows_system_info']);
         $output = $_SESSION['windows_system_info'];

        $html  .= "<table class=\"action_form details_form\" style=\"width:100%;\">";
         foreach ($memory_keys as $key) $html .= $libhtml->render_table_row(substr($output[$key], 0, 27),substr($output[$key], 27));
         $html  .= "</table>\n";
         echo $html;

    } else if (my_get("tab") == "Windows_CPU") {

        $output=null;
         if (!isset($_SESSION['windows_system_info'])) exec('Systeminfo', $_SESSION['windows_system_info']);
         $output = $_SESSION['windows_system_info'];

        $html  .= "<table class=\"action_form details_form\" style=\"width:100%;\">";
         $html .= $libhtml->render_table_row("Number of Processors",substr($output[15], 27));
         $html .= $libhtml->render_table_row("Processor Details",substr($output[16], 27));
         $html  .= "</table>\n";
         echo $html;

    } else if (my_get("tab") == "Windows_Network") {

        $output=null;
         if (!isset($_SESSION['windows_system_info'])) exec('Systeminfo', $_SESSION['windows_system_info']);
         $output = $_SESSION['windows_system_info'];
        $html  .= "<table class=\"action_form details_form\" style=\"width:100%;\">";
        for ($i=$network_key[0] ; $i<count($output) ; $i++) $html .= $libhtml->render_table_row(substr($output[$i], 0, 27),substr($output[$i], 27));
           $html  .= "</table>\n";
           echo $html;

    } else if (my_get("tab") == "Windows_Hotfixes") {

        $output=null;
         if (!isset($_SESSION['windows_system_info'])) exec('Systeminfo', $_SESSION['windows_system_info']);
         $output = $_SESSION['windows_system_info'];

        $html  .= "<table class=\"action_form details_form\" style=\"width:100%;\">";
        $url = "http://support.microsoft.com/kb/";
        for ($i=$hotfix_key[0] ; $i<$network_key[0] ; $i++) {
            $hotfix_id = explode("KB", substr($output[$i], 27));
            if (array_key_exists("1", $hotfix_id)) {
                 $html .= $libhtml->render_table_row(substr($output[$i], 0, 27)," <a href=\"" . $url . $hotfix_id[1] . "\" target=\"_blank\">KB" . $hotfix_id[1] . "</a>");
            } else {
                 $html .= $libhtml->render_table_row(substr($output[$i], 0, 27),substr($output[$i], 27));
            }
         }
         $html  .= "</table>\n";
         echo $html;

    } else if (my_get("tab") == "Windows_Processes") {

         $output = null;
         exec('tasklist /v /FO CSV /nh | sort ', $output);
         //array_shift($output);
         //array_shift($output);
         //array_shift($output);
         //dump_var($output);
         array_unshift($output,"\"Image Name\",\"PID\",\"Session Name\",\"Session#\",\"Mem Usage\",\"Status\",\"Username\",\"CPU Time\",\"Window Title\"");
         echo make_windows_table($output, "\",\"");
    }

}

$db->close();

    function make_table($array, $explode=" ", $width="100%", $title=""){
        global $user1;
        $head = explode($explode,$array[0]);
        $html  = "<div class=\"padding_wrap clearfix\">\n";
        $html  .= "<div class=\"table_wrap clearfix left\" style=\"width:100%;  margin-bottom:20px !important;\">\n";
        $html  .= "<div class=\"table_options\">";
        if (!empty($title)) $html  .= "<h2 class=\"no_line\">$title</h2>";
        $html  .= "<div class=\"quick_wrap\"><input tabindex=\"1\" type=\"text\" class=\"quick_filter right\" value=\"Quick table filter\"/><span class=\"reset_quick\">&nbsp;</span></div>";
        $html  .= "</div><table class=\"action_form details_form tablesorter just_quick\" style=\"width:$width;\">";
        $html  .= "<thead><tr>";
        $count = 0;
        foreach ($head as $item) {
            if (trim($item)!="") {
                $html  .= "<th>" . $item . "</th>";
                $count++;
            }
        }
        $html  .= "</tr></thead>";
        if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') || $user1->user_groups==array("0")) array_shift($array);
        foreach ($array as $row){
            $html .= "<tr>";
            $new_row = explode($explode,$row);
            $new_count = 0;
            $last_item = "";
            foreach ($new_row as $item) {
                if (trim($item)!="") {
                    $new_count++;
                    if ($new_count<$count){
                        $html  .= "<td>" . $item . "</td>";
                    } else {
                        $last_item .= " " . $item;
                    }
                }
            }
            $html  .= "<td>" . $last_item . "</td>";
            $html  .= "</tr>";
        }
        $html  .= "</table>\n";
        $html  .= "</div>\n";
        $html  .= "</div>\n";
        return $html;
    }
    function make_windows_table($array,$explode=" ",$width="100%"){
        global $user1;
        $head = explode($explode,$array[0]);
        $table = "<div class=\"padding_wrap clearfix\">\n";
        $table .= "<div class=\"table_wrap clearfix left\" style=\"width:100%;  margin-bottom:20px !important;\">\n";
        $table .= "<div class=\"table_options\">
        <div class=\"quick_wrap\"><input tabindex=\"1\" type=\"text\" class=\"quick_filter right\" value=\"Quick table filter\"/><span class=\"reset_quick\">&nbsp;</span></div>";
        $table .= "</div><table class=\"action_form details_form tablesorter just_quick\" style=\"width:$width;\">";
        $table .= "<thead><tr><th></th>";
        $count = 0;
        foreach ($head as $item) {
            if (trim($item)!="") {
                $item = str_replace('"', '', $item);
                $table .= "<th>" . $item . "</th>";
                $count++;
            }
        }
        $table .= "</tr></thead>";
        if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') || $user1->user_groups==array("0")) array_shift($array);
        $i=0;
        foreach ($array as $row){
            $i++;
            $table .= "<tr>";
            $table .= "<td>$i</td>";
            $new_row = explode($explode,$row);
            $new_count = 0;
            $last_item = "";
            foreach ($new_row as $item) {
                if (trim($item)!="") {
                    $item = str_replace('"', '', $item);
                    $new_count++;
                    if ($new_count<$count){
                        $table .= "<td>" . $item . "</td>";
                    } else {
                        $last_item .= " " . $item;
                    }
                }
            }
            $table .= "<td>" . $last_item . "</td>";
            $table .= "</tr>";
        }
        $table .= "</table>\n";
        $table .= "</div>\n";
        $table .= "</div>\n";
        $table .= "
            <script type=\"text/javascript\">
            $(function() {";
                $table .= "
                $.tablesorter.addWidget({
                    // give the widget a id
                    id: 'indexFirstColumn',
                    // format is called when the on init and when a sorting has finished
                    format: function(table) {
                            // loop all tr elements and set the value for the first column
                            for(var i=0; i < table.tBodies[0].rows.length; i++) {
                                    $('tbody tr:eq(' + (i - 1) + ') td:first',table).html(i);
                            }
                    }
                });
                $('table.action_form').tablesorter({widgets: ['zebra','indexFirstColumn']});";
        $table .= "</script>";
        return $table;

    }
    function make_simple_table($array){
        $table = "<div class=\"padding_wrap clearfix\">\n";
        $table .= "<div class=\"table_wrap clearfix left\" style=\"width:100%; margin-bottom:20px !important;\">\n";
        $table .= "<div class=\"table_options\">
        <div class=\"quick_wrap\"><input tabindex=\"1\" type=\"text\" class=\"quick_filter right\" value=\"Quick table filter\"/><span class=\"reset_quick\">&nbsp;</span></div>";

        $table .= "</div><table class=\"action_form details_form tablesorter just_quick\" style=\"width:100%;\">";
        $table .= "<thead><tr><th>$array[0]</th></tr></thead>";
        array_shift($array);
        foreach ($array as $row) $table .= "<tr><td>$row</td></tr>";
        $table .= "</table>\n";
        $table .= "</div>\n";
        $table .= "</div>\n";
        return $table;
    }
    function array_ereg_search($val, $array) {
          $i = 0;
          $return = array();
          foreach($array as $v) {
               if(stristr($v, $val, true)) $return[] = $i;
               $i++;
          }
      return $return;
    }
    function find_program($program){
        $path = array_unique(explode(":",getenv("PATH").":/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin"));
        foreach($path as $dir) {
            if($x=@stat("$dir/$program")) {
                return "$dir/$program";
                break;
            }
        }
        return "";
    }
?>
