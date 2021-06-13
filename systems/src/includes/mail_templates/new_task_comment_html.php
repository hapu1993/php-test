<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>{{CLIENT}}</title>
<style type="text/css">
* { color:#6A6A6A; }
</style>
</head>
<body marginheight="0" topmargin="0" marginwidth="0" bgcolor="#fff" leftmargin="0" rightmargin="0" style="padding:0px; margin:0px;">
<!-- header -->
<table cellspacing="0" border="0" cellpadding="0" width="100%" style="border:none; padding:0px; margin:0px;">
    <tr>
        <td style="border:0; padding: 9px 0 15px 0; background:#ffffff; text-align:left; border-bottom:1px solid #D8D8D8; margin:0px;">
            <a target="_blank" href="{{ROOT}}" border="0" style="float:left; "><img style="margin:0; border:none;" alt="" src="{{ROOT}}config/logo_small.png"></a>
        </td>
    </tr>
</table>
<!-- / header -->

<table cellspacing="0" border="0" style="background-color: #EFEFEF" cellpadding="0" width="100%">
    <tr>
        <td valign="middle" style="padding:20px 0 20px 0;">

            <table cellspacing="0" border="0" align="center" style="-moz-border-radius:5px; -webkit-border-radius: 5px; border-radius: 5px; margin-top:40px; margin-bottom:40px; background: #fff; border: 5px solid #D6D6D6" cellpadding="0" width="700">
                <tr>
                    <td>
                        <!-- content -->
                        <table cellspacing="0" border="0" cellpadding="0" width="700">
                            <tr>
                                <td valign="top" style="background:#41ADE0; padding: 17px 20px; font-family: Arial; font-size: 17px; font-weight: bold; color:#ffffff; " width="700" colspan="2">
                                    {{SUBJECT}}
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" style="padding: 20px 20px 0px 20px; color: #000; font-size: 14px; font-family: Arial; line-height: 20px; color:#6A6A6A;">
                                {{DESCRIPTION}}
                                </td>
                            </tr>

                            <!-- file -->
                            <?php if (!empty($_GET['attachment'])) { ?><tr>
                                <td valign="top" style="padding: 0px 0px 20px 20px; color: #000; font-size: 14px; font-family: Arial; line-height: 20px; color:#6A6A6A;">
                                    <table style="font-weight:bold; font-size:11px; padding:0px 10px; background: #777; color: #ffffff; ">
                                        <tr>
                                            <td>
                                                <a style="color:#FFF; font-weight:bold; font-size:11px; text-decoration:none;" href="<?php echo $cfg['root']?>{{ATTACHMENT}}">Download the attachment</a>
                                            </td>
                                          </tr>
                                    </table>
                                </td>
                            </tr>
                            <?php } ?>
                            <!-- / file -->


                            <!-- original_task -->
                            <tr>
                                <td>
                                <table style="padding:20px;">
                                    <tr>
                                        <td valign="top" style="width:630px; padding: 10px; font-size: 12px; font-family: Arial; line-height: 20px; color:#6A6A6A; background:#EFEFEF;">
                                            {{ORIGINAL_TASK}}
                                        </td>
                                    </tr>
                                </table>
                                </td>
                            </tr>
                            <!-- / original_task -->

                            <!-- extra -->
                            <tr>
                                <td valign="top" style="padding:20px; color: #000; font-size: 11px; font-family: Arial; line-height: 20px; color:#6A6A6A; border-top:1px dotted #eaeae6;">
                                <b>System Login URL:</b> <a target="_blank" href="{{ROOT}}">{{ROOT}}</a><br/>
                                </td>
                            </tr>
                            <!--  / extra -->



                        </table>
                        <!--  / content -->
                    </td>
                </tr>

                <tr>
                    <td valign="top" width="700">
                        <!-- footer -->
                        <table cellspacing="0" border="0" cellpadding="0" width="700">
                            <tr>
                                <td height="70" align="center" valign="middle" style="background-color:#F8F8F8; border-top:1px solid #D4D4D4; padding: 0 20px; color: #6A6A6A; font-family: Arial; font-size: 11px; line-height: 20px;" width="700" colspan="2">
                                    <b style="color:#6A6A6A;">{{CLIENT}}</b><br/>
                                    <a style="color:#6A6A6A; text-decoration:none;" href="{{WEBSITE}}">{{WEBSITE}}</a>
                                </td>
                            </tr>
                        </table>
                        <!-- / end footer -->
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>
</body>
</html>
