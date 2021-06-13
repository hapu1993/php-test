<?php
    //require_once $cfg['source_root'] . "lib/tcpdf/tcpdf.php";

    class PDF_Report_Template {

        function __construct($title='',$header_title='',$orientation='L',$font_size=9){
            global $cfg;

            $this->title = (empty($title)) ? $cfg["client"].' Report' : $title;
            $this->header_title = (empty($header_title)) ? $cfg["client"].' Report' : $header_title;
            $this->orientation=$orientation;
            $this->font_size=$font_size;
            $this->html = '';

        }

        public function set_pdf_template($options=array()){
            global $cfg, $pdf;

            ini_set('display_errors', TRUE);
            ini_set('display_startup_errors', TRUE);
            ini_set('memory_limit','256M');
            ini_set('max_execution_time', 300);

            // create new PDF document
            $this->pdf = new Custom_PDF($this->orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            if (!empty($options['user_password'])){
                $this->pdf->SetProtection(array('print', 'copy'), $options['user_password'],null, 0, null);
            }

            if (!empty($options['title'])) $this->title = $options['title'];
            if (!empty($options['header_title'])) $this->header_title = $options['header_title'];

            // set document information
            $this->pdf->SetCreator(PDF_CREATOR);
            $this->pdf->SetAuthor($cfg["client"]);
            $this->pdf->SetTitle($this->title);
            $this->pdf->SetSubject($this->title);
            $this->pdf->SetKeywords($cfg["client"].', '.$this->title);
            $this->pdf->custom_header_title = $this->header_title;


            // set default header data
            $this->pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

            // set header and footer fonts
            $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // set default monospaced font
            $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // set margins
            $this->pdf->SetMargins(10, 30, 10);
            $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // set auto page breaks
            $this->pdf->SetAutoPageBreak(TRUE,15);

            // set image scale factor
            $this->pdf->setImageScale(1.53);

            // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $this->pdf->setLanguageArray($l);
            }

            // set font
            $this->pdf->SetFont('Helvetica', 'N', $this->font_size);

        }

        function render_html($options = array()){

            $html = explode('<pagebreak/>',$this->html);

            foreach($html as $item){

                $this->pdf->AddPage();
                $this->pdf->writeHTML($item, true, false, true, false, 'L');

            }

            $this->html ='';

        }

        function render_pdf($options = array()){

            if (!empty($options['filepath'])){
                $this->pdf->Output($options['filepath'], 'F');
            } else {
                ob_end_clean();
                $this->pdf->Output(make_seo_title($this->title).'_'.date("His_dmY").'.pdf', 'I');
            }

        }

        public function export_pdf($options = array()){
            $this->set_pdf_template($options);
            $this->render_html($options);
            $this->render_pdf($options);
        }

    }

    class Custom_PDF extends TCPDF {

        // Page header
        public function Header() {
            global $cfg;

            $font_size = 20;

            if ($this->CurOrientation=='L'){

                $this->writeHTML('<span style="color:#666;font-size:'.$font_size.'pt;line-height:50pt;">'.$this->custom_header_title.'</span>', true, 0, true, 0);
                $this->Image($cfg["source_root"] . 'config/logo.png', 240, 8, 50, 0, 'PNG', '', 'T', true, 300, '', false, false, 0, false, false, false);
                $this->Line(10, 22, 287, 22, array('width' => 0.1, 'color' => array(190, 190, 190)));

            } elseif ($this->CurOrientation=='P'){


                if (strlen($this->custom_header_title>40)) $font_size=18;
                if (strlen($this->custom_header_title>60)) $font_size=16;

                $this->writeHTML('<span style="color:#666;font-size:'.$font_size.'pt;line-height:50pt;">'.$this->custom_header_title.'</span>', true, 0, true, 0);
                $this->Image($cfg["source_root"] . 'config/logo.png', 155, 8, 50, 0, 'PNG', '', 'T', true, 300, '', false, false, 0, false, false, false);
                $this->Line(10, 22, 200, 22, array('width' => 0.1, 'color' => array(190, 190, 190)));

            }

        }

        // Page footer
        public function Footer() {
            global $user1;

            if ($this->CurOrientation=='L'){

                $this->Line(10, 200, 287, 200, array('width' => 0.1, 'color' => array(190, 190, 190)));
                $this->writeHTMLCell(287, 0, 10, 202, '<span style="color:#999;">Created at '.date('H:i \o\n jS M Y').'</span>', 0, 0, false, false, "L", false);
                $this->writeHTMLCell(287, 0, 10, 202, '<span style="color:#999;">Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages() . '</span>', 0, 0, false, false, "R", false);

            } elseif ($this->CurOrientation=='P'){

                $this->Line(10, 285, 200, 285, array('width' => 0.1, 'color' => array(190, 190, 190)));
                $this->writeHTMLCell(202, 0, 10, 287, '<span style="color:#999;">Created at '.date('H:i \o\n jS M Y').'</span>', 0, 0, false, false, "L", false);
                $this->writeHTMLCell(202, 0, 10, 287, '<span style="color:#999;">Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages() . '</span>', 0, 0, false, false, "R", false);

            }
        }
    }

    //Simple HTML functions

    //Header table cell
    function hcell($title='',$width=''){
        $width_style = (!empty($width)) ? 'width:'.$width.';' : '';
        return '<th style="border: 0.5px solid #666; background-color:#efefef;color:#337ab7;'.$width_style.'">'.$title.'</th>';

    }

    //Normal table cell
    function cell($content='',$width=''){
        $width_style = (!empty($width)) ? 'width:'.$width.';' : '';
        return '<td style="border: 0.5px solid #666; background-color:#fff;'.$width_style.'">'.$content.'</td>';

    }

    //Subtotal table cell
    function cell2($content='',$width=''){
        $width_style = (!empty($width)) ? 'width:'.$width.';' : '';
        return '<td style="border: 0.5px solid #666; background-color:#efefef;font-weight:bold;'.$width_style.'">'.$content.'</td>';

    }

    function pdf_header($title='',$width=''){
        $width_style = (!empty($width)) ? 'width:'.$width.';' : '';
        return '<h1 style="color:#337ab7;font-size: 12px; font-weight:bold;'.$width_style.'">'.$title.'</h1>';
    }

    function pdf_subheader($title='',$width=''){
        $width_style = (!empty($width)) ? 'width:'.$width.';' : '';
        return '<h2 style="color:#22a0d6;font-size: 10px;'.$width_style.'">'.$title.'</h1>';
    }
