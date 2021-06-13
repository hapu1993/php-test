<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml->title = "Software Licenses";
    $libhtml->tab="license";
    $libhtml->main_tab="license";
    $libhtml->show_side_panel = false;

    $apps = $db->select("name, path","system_apps",array("WHERE path<>''",array(),array()),array('order_by'=>"ORDER BY name ASC"));

    $html = '
    <div class="clearfix rte">

    <h2>Definitions</h2>
    <p>
        <strong>Software</strong> means the <strong>Riskpoint Framework Software</strong>, the <strong>Third Party Software</strong>, and the <strong>Custom Software</strong>, and all subsequent amendments and updates to and new releases of such programs.&nbsp;
    </p>
    <br/>
    <h2>Software Licenses</h2>
    <ol>
        <li>
            <p>
                <strong>Custom Software </strong>means all software code developed by Riskpoint specifically for the Client application. The Custom Software currently resides in the following folders:
            </p>
            <ul>';

            foreach($apps as $app){

                $html .= '<li>/' . $app->path .' - '. $app->name . '</li>';

            }

    $html .= '
            </ul>
            <p>
                <strong>Custom Software IP resides fully with the Client or anyone they choose to offer it to.</strong>
            </p>
        </li>
        <li>
            <p>
                <strong>Third Party Software</strong> means the software programs proprietary to third parties.
                Third party&nbsp;code included in the application resides in the following folders:
            </p>
            <ul>';

    $html .= '
                <li>/js/ - various jQuery-based plugins</li>';

                if ($handle = opendir('../vendor')) {
                    while (false !== ($entry = readdir($handle))) {
                        if ($entry != "." && $entry != "..") $html .= '<li>../vendor/' . $entry . '</li>';
                    }
                    closedir($handle);
                }

    $html .= '
            </ul>
            <p>
                <strong>All Third Party software listed here has been released under various open-source licenses; see relevant URLs for more information.</strong>
            </p>
        </li>
        <li>
            <p>
                <strong>Riskpoint Framework Software</strong> means the software toolkit developed by Riskpoint.
                The Riskpoint framework includes the "Admin Panel" application controlling user logins,
                system permissions, user-preferences and various other shared functions used throughout the system.
                In the source code it includes all files and folders except:
            </p>
            <ul>
                <li>Custom software files and folders</li>
                <li>Third party software files and folders</li>
                <li>General user file and image storage and caching folders, including all subfolders and subfolder files</li>
            </ul>
            <p>
                <strong>Riskpoint Framework Software is released under the MIT free software license</strong>.
            </p>
        </li>
    </ol>
    <h2>Riskpoint Framework Software License - MIT License</h2>
    <p>Copyright (C) '.date("Y").' Riskpoint Limited (<a href="mailto:info@riskpoint.co.uk">info@riskpoint.co.uk</a>)</p><br/>
    <p>
        Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
        files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify,
        merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished
        to do so, subject to the following conditions:
    </p><br/>
    <p>
        The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
    </p><br/>
    <p>
        THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
        OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
        LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
        IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    </p>

    </div>
';


$libhtml->render($html);
