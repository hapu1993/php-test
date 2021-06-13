<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if !mso]><!-->
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!--<![endif]-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{CLIENT}}</title>
    <style type="text/css">
        body { margin: 0 !important; padding: 0; }
        table { border-spacing: 0; font-family: sans-serif; }
        td { padding: 0; }
        img { border: 0; }
        div[style*="margin: 16px 0"] { margin:0 !important; }
        .wrapper { width: 100%; table-layout: fixed; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #efefef; font-family: Arial; line-height: 20px; color:#6A6A6A; }
        .webkit { max-width: 960px; margin: 20px auto; background: #fff; }
        .header .container,
        .footer .container { max-width: 960px; margin: 0 auto; }

        .outer { Margin: 0 auto; width: 100%; max-width: 960px; }
        .header { border-bottom: 1px solid #d8d8d8; width: 100%; background: #fff; }
        .header td { padding: 15px 0px;  }
        .header td a { margin-left: 20px; text-align: center; max-width: 920px; }
        .header td a img { margin: 0px; width: auto; height: auto; }

        .footer { border-top: 1px solid #d8d8d8; width: 100%; background: #fff; }
        .footer td { padding: 15px 0px; text-align: center; font-size: 11px; }
        .footer td a { text-decoration: none; }

        p { Margin: 0; }
        a { text-decoration: none; }
        .h1 { font-size: 21px; font-weight: bold; Margin-bottom: 18px; }
        .h2 { font-size: 18px; font-weight: bold; Margin-bottom: 12px; }

        /* One column layout */
        .one-column .contents { text-align: left; }
        .one-column p { font-size: 14px; Margin-bottom: 10px; }

        .one-column .title { background: #1154a1; padding: 17px 20px; font-family: Arial; font-size: 17px; font-weight: bold; color:#ffffff; }
        .one-column .content { padding: 20px; font-size: 14px; }

        .one-column .system_login { background: #f8f8f8; padding: 20px; font-size: 11px; border-top: 1px dotted #eaeae6; }

        /* Two column layout */
        .two-column { text-align: center; font-size: 0; }
        .two-column .column { width: 100%; max-width: 260px; display: inline-block; vertical-align: top; }

        .contents { width: 100%; }

        .two-column .contents { font-size: 14px; text-align: left; }
        .two-column img { width: 100%; max-width: 240px; height: auto; }
        .two-column .text { padding-top: 10px; }

    </style>
    <!--[if (gte mso 9)|(IE)]>
    <style type="text/css">
        table {border-collapse: collapse;}
    </style>
    <![endif]-->
</head>
<body>
    <center class="wrapper">
        <table class="header">
            <tr>
                <td>
                    <div class="container">
                        <a target="_blank" href="{{ROOT}}" border="0"><img alt="" src="{{ROOT}}config/logo_small.png"></a>
                    </div>
                </td>
            </tr>
        </table>
        <div class="webkit">
            <!--[if (gte mso 9)|(IE)]>
            <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
            <td>
            <![endif]-->
            <table class="outer" align="center">
                <tr>
                    <td class="one-column">
                        <table width="100%" class="title">
                            <tr>
                                <td class="contents">
                                    {{SUBJECT}}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="one-column">
                        <table width="100%" class="content">
                            <tr>
                                <td class="contents">
                                    {{CONTENT}}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="one-column">
                        <table width="100%" class="system_login">
                            <tr>
                                <td>
                                    <b>System Login URL:</b> <a target="_blank" href="{{ROOT}}">{{ROOT}}<a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </div>
        <table class="footer">
            <tr>
                <td>
                    <div class="container">
                        <b>{{CLIENT}}</b><br/>
                    </div>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
