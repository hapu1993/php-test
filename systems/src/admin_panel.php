<?php
    require_once dirname(__FILE__).'/config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "title" => "Riskpoint Contact Details",
    ));

    $html = '
        <div class="sidebar-box clearfix">
            <h2>Riskpoint Limited</h2>
            <address>
                5th Floor East
                26 - 28 Hammersmith Grove<br/>
                London W6 7BA<br/>
                United Kingdom
            </address><br/>
                <!--Phone: +44 (0) 208 741 2294<br/>-->
                E-mail: <a class="contact-link" href="mailto:info@riskpoint.co.uk">info@riskpoint.co.uk</a><br/>
                Web: <a class="contact-link" href="http://riskpoint.co.uk" target="_blank">riskpoint.co.uk</a><br/><br/>
        </div>';

    $gm = new Google_Map(array(
            'x'=>51.494824,
            'y'=>-0.226255,
            'sx'=>51.4949,
            'sy'=>-0.226367,
            'streetViewHeading'=>45,
            'streetViewPitch'=>10,
            'streetViewZoom'=>0,
    ));

    $libhtml->js = $gm->return_js();
    $html .= $gm->return_html();
    $libhtml->render($html);
