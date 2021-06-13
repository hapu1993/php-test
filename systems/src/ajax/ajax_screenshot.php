<?php
    require_once dirname(__FILE__).'/../config/global.php';

//Screenshot applet
//As is ajaxed server_root is not defined unless alter to post this JS var
        $html = '
            <SCRIPT LANGUAGE="JavaScript">
            function firepopup( val ){
                if ( val == "" ) return;
                var bug = $("#bug");
                var link = bug.attr(\'href\');
                bug.attr(\'href\', link + \'&sc=\'+val);
                bug.trigger(\'click\');
                bug.attr(\'href\', link);
            }
            </SCRIPT>
            <APPLET ID="RiskpointScreenshotApplet" NAME="RiskpointScreenshotApplet" code="RiskpointScreenshot.class" codebase="'.$cfg['root'].'jar/" archive="RiskpointScreenshot.jar" align="baseline" width="0" height="0">
            <PARAM NAME="root" VALUE="'.$cfg['root'].'">
            <PARAM NAME="java_archive" VALUE="RiskpointScreenshot.jar">
            <PARAM NAME="java_code" VALUE="RiskpointScreenshot.class">
            <PARAM NAME="java_codebase" VALUE="'.$cfg['root'].'jar/">
            <PARAM NAME="java_type" VALUE="application/x-java-applet;jpi-version=1.4">
            <PARAM NAME="scriptable" VALUE="true">
            </APPLET>
        ';
        //End applet code
        //<script LANGUAGE="JavaScript">document.write(\'<PARAM NAME="root" VALUE="\'+SYSTEM_ROOT+\'">\');</script>

    echo $html;
?>
