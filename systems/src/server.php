<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml->tab = my_get("tab", "summary");

    if ($libhtml->tab=="summary"){

        $libhtml->title = "System Summary";

        $html .= open_table('1000px');
        $html .= $libhtml->render_table_row("Operating System",php_uname());

        if (function_exists('apache_get_version')) {

            $html .= table_separator('1000px','Apache');
            $html .= $libhtml->render_table_row("Version",apache_get_version());
            $html .= $libhtml->render_table_row("Modules",implode(", ",apache_get_modules()));

        }

        $html .= table_separator('1000px','PHP');
        $html .= $libhtml->render_table_row("Version",phpversion());
        $html .= $libhtml->render_table_row("Interface",php_sapi_name());
        $html .= $libhtml->render_table_row("Extensions",implode(", ",get_loaded_extensions()));


        $html .= table_separator('1000px','MySQL');

        $html .= $libhtml->render_table_row("Client",$db->getAttribute(PDO::ATTR_CLIENT_VERSION));
        $html .= $libhtml->render_table_row("Server",$db->getAttribute(PDO::ATTR_SERVER_VERSION));

        $html .= table_separator('1000px','Miscellaneous');
        $html .= $libhtml->render_table_row("Current user",get_current_user());
        $html .= $libhtml->render_table_row("Document Root",apache_getenv("DOCUMENT_ROOT"));
        $html .= $libhtml->render_table_row("php.ini",php_ini_loaded_file());
        $html .= $libhtml->render_table_row("Temporary files",sys_get_temp_dir());
        $html .= $libhtml->render_table_row("Disk total space",number_format(disk_total_space(".")/(1024*1024)). " Mb");
        $html .= $libhtml->render_table_row("Disk free space",number_format(disk_free_space(".")/(1024*1024)). " Mb");

        $html .= close_table();


    } elseif ($libhtml->tab=="php"){

        //phpinfo();die;

        $libhtml->title = "PHP Version: ". phpversion();
        $libhtml->css = "
                <style type=\"text/css\">
                    #phpinfo pre {margin: 0; font-family: monospace;}
                    #phpinfo a:link {color: #009; text-decoration: none; background-color: #fff;}
                    #phpinfo a:hover {text-decoration: underline;}
                    #phpinfo table {border-collapse: collapse; border: 0; width: 960px; box-shadow: 1px 2px 3px #ccc;}
                    #phpinfo .center {text-align: center;}
                    #phpinfo .center table {margin: 1em auto; text-align: left;}
                    #phpinfo .center th {text-align: center !important;}
                    #phpinfo td, th {border: 1px solid #666; font-size: 100%; vertical-align: baseline; padding: 4px 5px;}
                    #phpinfo h1 {font-size: 200%;}
                    #phpinfo h2 {font-size: 150%;}
                    #phpinfo .p {text-align: left;}
                    #phpinfo .e {background-color: #ccf; width: 300px; font-weight: bold;}
                    #phpinfo .h {background-color: #99c; font-weight: bold;}
                    #phpinfo .v {background-color: #ddd; max-width: 300px; overflow-x: auto;}
                    #phpinfo .v i {color: #999;}
                    #phpinfo img {float: right; border: 0;}
                    #phpinfo hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
                </style>
            ";

        //PHP
          ob_start();
          phpinfo();
          $pinfo = ob_get_contents();
        if (ob_get_length()) ob_end_clean();
        $pinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);

        $html =  "<div id=\"phpinfo\">\n";
        $html .= $pinfo;

        //GET EXT INFO
        ob_start();
        $html .= '
            <div class="center">
                <table border="0" cellpadding="3" width="960">
                    <tr class="h">
                        <td>
                            <h1 class="p">PHP Extensions</h1>
                        </td>
                    </tr>
                </table><br />
                <h2>Overview</h2>
                <table border="0" cellpadding="3" width="960">
                    <tr>
                        <td class="e">Extensions</td>
                        <td class="v">';
        foreach (get_loaded_extensions() as $ext) $exts[] = $ext;
        $html .= implode(', ', $exts);
        $html .= '
                        </td>
                    </tr>
                </table><br />
                <h2>Details</h2>
                <table border="0" cellpadding="3" width="960">';
        foreach ($exts as $ext) {
            $html .= '
                    <tr>
                        <td class="e">'.$ext.'</td>
                        <td class="v">';
            $funcs = get_extension_funcs($ext);
            if (count($funcs)>1) {
                $html .= implode(', ', $funcs);
            } elseif(count($funcs)==1) {
                $html .= $funcs[0];
            }
            $html .= '
                        </td>
                    </tr>';
        }

        $html .= '</table><br />
                </div>
            </div>';


    } elseif ($libhtml->tab=="config" && (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')){

        $libhtml->title = "Server Information - System Configuration";
        $html .= dump_array($cfg);

    } elseif ($libhtml->tab=="session"){

        $libhtml->title = "Server Variables";

        $tables[] = dump_array($_SERVER);
        $titles[] = "Server";

        $tables[] = dump_array($_COOKIE);
        $titles[] = "Cookies";

        $tables[] = dump_array($_SESSION);
        $titles[] = "Session";

        $tables[] = dump_array(get_defined_constants(true));
        $titles[] = "Constants";

        $tables[] = dump_array(ini_get_all());
        $titles[] = "PHP ini";

        $html = jquery_tabs($tables, $titles);

    } else if ($libhtml->tab == "browser") {
        $libhtml->title = "Browser Information";
        $html .= '<script type="text/javascript" src="' . $cfg['root'] . 'js/jquery.client_info.js"></script>';

        $info_array = array(
                "Browser Timezone"=>"tz",
                "Online Status"=>"navigator.onLine",
                "Platform"=>"navigator.platform",
                "Operating System"=>"OSName",
                "Vendor"=>"navigator.vendor",
                "Vendor Version"=>"navigator.vendorSub",
                "Browser Application"=>"navigator.appCodeName",
                "Browser Name"=>"browserName",
                "Browser Major Version"=>"majorVersion",
                "Browser Full Version"=>"fullVersion",
                "Navigator Application Version"=>"navigator.appVersion",
                "Navigator Application Name"=>"navigator.appName",
                "Navigator User Agent"=>" navigator.userAgent",
                "Navigator Language"=>"navigator.language",
                "Java Enabled"=>"navigator.javaEnabled()",
                "Cookie Enabled"=>"navigator.cookieEnabled",
                "Screen Width and Height"=>"screenW+' x '+screenH",
                "Browser Width and Height"=>"winW+' x '+winH",
                "Browser Width and Height (with Scrollbars)"=>"winW2+' x '+winH2",
        );

        $browser_html = open_table("100%");

        foreach ($info_array as $key=>$info) {
            $browser_html .= $libhtml->render_table_row($key,"<script type=\"text/javascript\">if (typeof ($info) != \"undefined\") document.write($info);</script>");
        }

        $browser_html .= close_table();

        $fields[] = $browser_html;
        $titles[] = "Browser";

        $fields[] = "
            <script type=\"text/javascript\">
                var L = navigator.plugins.length;
                document.write('<table class=\"action_form details_form separator margin_top\">');
                for(var i=0; i<L; i++) {
                    document.write('<tr><th>'+navigator.plugins[i].name+'<\/th><td>');
                    document.write(navigator.plugins[i].description);
                    document.write('<\/td><\/tr>');
                }
                document.write('<\/table>');
            </script>
        ";

        $titles[] = "Browser Plugins";

        $server_html = open_table("100%");

        $info = checkClient(null,false,false);

        foreach($info as $key=>$value){
            if ($value!='') $server_html .= $libhtml->render_table_row($key,$value);
        }


        $d = new DateTime();
        $d->getTimezone();

        $server_html .= $libhtml->render_table_row('Encoding',(isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '');
        $server_html .= $libhtml->render_table_row('Language',(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '');
        $server_html .= $libhtml->render_table_row('Server Time',$d->format('D d M Y H:i:s P'));
        $server_html .= $libhtml->render_table_row('Server Timezone',timezone_name_get(date_timezone_get($d)));

        $server_html .= close_table();

        $fields[] = $server_html;
        $titles[] = "Server";

        $html .= jquery_tabs($fields, $titles);
    }


    $libhtml->render($html);

    function make_table($array, $explode=" ", $width="100%", $title=""){
        global $user1;
        $head = explode($explode,$array[0]);
        $table = '
            <div class="table_wrap clearfix left" style="width:100%;  margin-bottom:20px !important;">
                <div class=\table_options">';

        if (!empty($title)) $table .= '
                    <h2 class="no_line">'.$title.'</h2>';
        $table .= '
                    <div class="quick_wrap">
                        <input tabindex="1" type="text" class="quick_filter right" value="Quick table filter"/>
                        <span class="reset_quick">&nbsp;</span>
                    </div>
                </div>
                <table class="summary tablesorter just_quick" style="width:'.$width.';">
                    <thead>
                        <tr>';
        $count = 0;
        foreach ($head as $item) {
            if (trim($item)!="") {
                $table .= '
                            <th>
                                <div class="inner">
                                    <span class="only_t">' . $item . '</span>
                                </div>
                            </th>';
                $count++;
            }
        }
        $table .= '
                        </tr>
                    </thead>';

        if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') || (strpos($user1->user_group,"0")+1)>0)

        array_shift($array);

        foreach ($array as $row){
            $table .= '
                    <tr>';
            $new_row = explode($explode,$row);
            $new_count = 0;
            $last_item = "";
            foreach ($new_row as $item) {
                if (trim($item)!="") {
                    $new_count++;
                    if ($new_count<$count){
                        $table .= '
                        <td>' . $item . '</td>';
                    } else {
                        $last_item .= " " . $item;
                    }
                }
            }

            $table .= '
                        <td>'.$last_item.'</td>
                    </tr>';
        }

        $table .= '
                </table>
            </div>';

        return $table;

    }

    function make_windows_table($array,$explode=" ",$width="100%"){
        global $user1;
        $head = explode($explode,$array[0]);
        $table = "<div class=\"padding_wrap clearfix\">\n";
        $table .= "<div class=\"table_wrap clearfix left\" style=\"width:100%;  margin-bottom:20px !important;\">\n";
        $table .= "<div class=\"table_options\">
        <div class=\"quick_wrap\"><input tabindex=\"1\" type=\"text\" class=\"quick_filter right\" value=\"Quick table filter\"/><span class=\"reset_quick\">&nbsp;</span></div>";
        $table .= "</div><table class=\"summary tablesorter just_quick\" style=\"width:$width;\">";
        $table .= "<thead><tr><th></th>";
        $count = 0;
        foreach ($head as $item) {
            if (trim($item)!="") {
                $item = str_replace('"', '', $item);
                $table .= "<th><div class=\"inner\"><span class=\"only_t\">" . $item . "</span></div></th>";
                $count++;
            }
        }
        $table .= "</tr></thead>";
        if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') || (strpos($user1->user_group,"0")+1)>0) array_shift($array);
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
                $('table.summary').tablesorter({widgets: ['zebra','indexFirstColumn']});";
        $table .= "</script>";
        return $table;

    }

    function make_simple_table($array){

        $table = '
            <div class="padding_wrap clearfix">
                <div class="table_wrap clearfix left" style="width:100%; margin-bottom:20px !important;">
                    <div class="table_options">
                        <div class="quick_wrap">
                            <input tabindex="1" type="text" class="quick_filter right" value="Quick table filter"/>
                            <span class="reset_quick">&nbsp;</span>
                        </div>
                    </div>
                    <table class="summary tablesorter just_quick" style="width:100%;">
                        <thead>
                            <tr>
                                <th>
                                    <div class="inner">
                                        <span class="only_t">'.$array[0].'</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>';

        array_shift($array);

        foreach ($array as $row) $table .= '
                            <tr>
                                <td>'.$row.'</td>
                            </tr>';

        $table .= '
                    </table>
                </div>
            </div>';

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
            if(is_file("$dir/$program")) {
                return "$dir/$program";
                break;
            }
        }
        return "";
    }

    function mysql_version(){
        ob_start();
        phpinfo(INFO_MODULES);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'Client API version');
        preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
        $gd = $match[0];
        return $gd;
    }
