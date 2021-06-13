<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if (
            $user1->logged_in
            && isset($my_get["table_pos"])
            && !empty($my_get["extype"])
            ) {

        // get the page again, send session cookie
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $_SERVER["HTTP_REFERER"]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_COOKIE, $cfg["session_name"] . '=' . $_COOKIE[$cfg["session_name"]] . '; path=/');
        $data = curl_exec($curl);
        curl_close($curl);

        // parse the document to get one table
        $html = SimpleHtmlDom\str_get_html($data);
        $filename = $html->find('#' . $my_get["table_pos"], 0)->id;

        // depending on export type, build html
        if ($my_get["extype"] == "csv") {
            $output = "";
            // header
            foreach($html->find('#' . $my_get["table_pos"], 0)->find('thead th') as $header) {
                if (trim($header->plaintext) || strstr($header->class, "column_row_number") || strstr($header->class, "column_status")) {
                    (!empty($header->find(".cnme", 0)->plaintext)) ? $column_name = $header->find(".cnme", 0)->plaintext : $column_name = "";
                    $output .= safecsv($column_name) . ",";
                }
            }

            $output .= "\n";

            // rows
            foreach($html->find('#' . $my_get["table_pos"], 0)->find('tbody tr') as $row){
                if (strstr($row->class, "add_more") == false && strstr($row->class, "multiselect") == false && strstr($row->class, "header") == false) { // exclude ajax view more & multiselect row
                    foreach($row->find("td") as $cell) {
                        if (!empty($cell)) {
                            if (strstr($cell->class, "no_export") == false && strstr($cell->parent()->parent()->class, "details") == false) { // exclude edit & delete & multiselect & action buttons columns - whatever has a no_export class + not a table inside a table
                                if (!empty($cell->find("img", 0)->alt)) $output .= safecsv($cell->find("img", 0)->alt) . ","; // images
                                else if (!empty($cell->find("a", 0)->plaintext)) $output .= safecsv($cell->find("a", 0)->plaintext) . ","; // a href links
                                else if (!empty($cell->find("div.more_text", 0)->plaintext)) $output .= safecsv($cell->find("div.more_text", 0)->plaintext) . ","; // expandable div
                                else if (!empty($cell->find("span[class*=ajax_toggle]", 0)->plaintext)) {
                                    if (!empty($cell->find("span[class*=ico_circle_toggle_on]", 0)->plaintext)) $output .= "Yes,"; // ajax toggle, circle icon is ON
                                    else if (!empty($cell->find("span[class*=ico_circle_toggle_off]", 0)->plaintext)) $output .= "No,"; // ajax toggle, circle icon is OFF
                                }
                                else if (!empty($cell->find("span[class*=tooltip]", 0)->plaintext)) $output .= safecsv($cell->find("span[class*=tooltip]", 0)->title) . ","; // span with tooltip (not actions buttons, those are excluded above)
                                else $output .= safecsv($cell->plaintext) . ","; // normal
                            }
                        }
                    }
                    if (strstr($row->parent()->class, "details") == false) $output .= "\n";
                }
            }

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Type: text/x-comma-separated-values');
            header("Content-Disposition: attachment; filename=\"" . $filename . "_" . date("_Y-m-d_H-i-s") . ".csv\"");
            echo $output;

        } else if ($my_get["extype"] == "xml") {
            $output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n<root>\n";

            // header
            $arrhead = array();
            foreach($html->find('#' . $my_get["table_pos"], 0)->find('thead th') as $header) {
                if (strstr($header->class, "column_row_number")) {
                    $arrhead[] = "num";
                } else if (trim($header->plaintext) != '' || strstr($header->class, "column_status")) {
                    $column_name = (!empty($header->find(".cnme", 0)->plaintext)) ? $header->find(".cnme", 0)->plaintext : '';
                    $arrhead[] = safexml($column_name, true);
                }
            }

            // rows
            foreach($html->find('#' . $my_get["table_pos"], 0)->find('tbody tr') as $row) {
                if (strstr($row->class, "add_more") == false && strstr($row->class, "multiselect") == false && strstr($row->class, "header") == false) { // exclude ajax view more & multiselect row
                    if (strstr($row->parent()->class, "details") == false) {
                        $output .= "\t<item>\n";
                        $i = 0;
                    }
                    foreach($row->find("td") as $cell) {
                        if (strstr($cell->class, "no_export") == false && strstr($cell->parent()->parent()->class, "details") == false) { // exclude edit & delete & multiselect & action buttons columns - whatever has a no_export class + not a table inside a table
                            if (!empty($cell)) {
                                if (!empty($cell->find("img", 0)->alt)) $output .= "\t\t<". $arrhead[$i] .">". safexml($cell->find("img", 0)->alt) . "</". $arrhead[$i] .">\n"; // images
                                else if (!empty($cell->find("a", 0)->plaintext)) $output .= "\t\t<". $arrhead[$i] .">" . safexml($cell->find("a", 0)->plaintext) . "</". $arrhead[$i] .">\n"; // a href links
                                else if (!empty($cell->find("div.more_text", 0)->plaintext)) $output .= "\t\t<". $arrhead[$i] .">" . safexml($cell->find("div.more_text", 0)->plaintext) . "</". $arrhead[$i] .">\n"; // expandable div
                                else if (!empty($cell->find("span[class*=ajax_toggle]", 0)->plaintext)) {
                                    if (!empty($cell->find("span[class*=ico_circle_toggle_on]", 0)->plaintext)) $output .= "\t\t<". $arrhead[$i] .">Yes</". $arrhead[$i] .">\n"; // ajax toggle, circle icon is ON
                                    else if (!empty($cell->find("span[class*=ico_circle_toggle_off]", 0)->plaintext)) $output .= "\t\t<". $arrhead[$i] .">No</". $arrhead[$i] .">\n"; // ajax toggle, circle icon is OFF
                                }
                                else if (!empty($cell->find("span[class*=tooltip]", 0)->plaintext)) $output .= "\t\t<". $arrhead[$i] .">" . safexml($cell->find("span[class*=tooltip]", 0)->title) . "</". $arrhead[$i] .">\n"; // span with tooltip (not actions buttons, those are excluded above)
                                else $output .= "\t\t<". $arrhead[$i] .">" . safexml($cell->plaintext) . "</". $arrhead[$i] .">\n"; // normal
                            }
                        }
                        $i++;
                    }
                    if (strstr($row->parent()->class, "details") == false) $output .= "\t</item>\n";
                }
            }
            $output .= "</root>";

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-type: text/xml");
            header("Content-Disposition: attachment; filename=\"" . $filename . "_" . date("_Y-m-d_H-i-s") . ".xml\"");
            echo $output;

        } else if ($my_get["extype"] == "pdf") {

            // create new PDF document
            $pdf = new TCPDF("landscape", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($user1->fullname);
            $pdf->SetTitle($filename);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('helvetica', '', 9, '', true);
            $pdf->AddPage();

            $pdf->SetFont('helvetica', '', 9, '', true);
            $output = '<table border="0" cellspacing="0" cellpadding="4" width="100%">';
            $i = 0;
            foreach($html->find('#' . $my_get["table_pos"], 0)->find('tbody tr') as $row) {
                if (strstr($row->class, "add_more") == false && strstr($row->class, "multiselect") == false) { // exclude ajax view more & multiselect row

                    if ($i == 0) {
                        $output .= '<thead>';
                            $output .= '<tr nobr="true">';
                                $numcol = 0;
                                foreach($html->find('#' . $my_get["table_pos"], 0)->find('thead th') as $header) {
                                    if (trim($header->plaintext) || strstr($header->class, "column_row_number") || strstr($header->class, "column_status")) {
                                        (!empty($header->find(".cnme", 0)->plaintext)) ? $column_name = $header->find(".cnme", 0)->plaintext : $column_name = "";
                                        if ($numcol == 0) $output .= '<th style="width:30px; padding:5px; background-color:#DADFF3; border:1px solid #ccc;"><b>'.$column_name.'</b></th>';
                                        else $output .= '<th style="background-color:#DADFF3; padding:5px; border:1px solid #ccc;"><b>'.$column_name.'</b></th>';
                                        $numcol++;
                                    }
                                }
                            $output .= '</tr>';
                        $output .= '</thead>';

                    } else {
                        $output .= '<tr nobr="true">';
                        if ($i % 2) $style = ' background-color: #f5f5f5; border:1px solid #ccc; padding:5px;';
                        else $style = ' border:1px solid #ccc; background-color: #f9f9f9; padding:5px;';
                        $numcol = 0;
                        foreach($row->find("td") as $cell) {
                            if (strstr($cell->class, "no_export") == false && strstr($cell->parent()->parent()->class, "details") == false) { // exclude edit & delete & multiselect & action buttons columns - whatever has a no_export class + not a table inside a table
                                if (!empty($cell)) {
                                    if (!empty($cell->find("img", 0)->alt)) $output .= '<td style="'.$style.'">'.$cell->find("img", 0)->alt.'</td>'; // images
                                    else if (!empty($cell->find("a", 0)->plaintext)) $output .= '<td style="'.$style.'">'.$cell->find("a", 0)->plaintext.'</td>'; // a href links
                                    else if (!empty($cell->find("div.more_text", 0)->plaintext)) $output .= '<td style="'.$style.'">'.$cell->find("div.more_text", 0)->plaintext.'</td>'; // expandable div
                                    else if (!empty($cell->find("span[class*=ajax_toggle]", 0)->plaintext)){
                                        if (!empty($cell->find("span[class*=ico_circle_toggle_on]", 0)->plaintext)) $output .= '<td style="'.$style.'">Yes</td>'; // ajax toggle, circle icon is ON
                                        else if (!empty($cell->find("span[class*=ico_circle_toggle_off]", 0)->plaintext)) $output .= '<td style="'.$style.'">No</td>'; // ajax toggle, circle icon is OFF
                                    }
                                    else if (!empty($cell->find("span[class*=tooltip]", 0)->plaintext)) $output .= '<td style="'.$style.'">'.$cell->find("span[class*=tooltip]", 0)->title.'</td>'; // span with tooltip (not actions buttons, those are excluded above)
                                    else if ($numcol == 0) $output .= '<td style=" width:30px; '.$style.'">'.$cell->plaintext.'</td>'; // normal, number column
                                    else $output .= '<td style="'.$style.'">'.$cell->plaintext.'</td>'; // normal
                                }
                            }
                            $numcol++;
                        }
                        if (strstr($row->parent()->class, "details") == false) $output .= "</tr>";
                    }

                }
                $i++;
            }
            $output .= '</table>';

            $pdf->writeHTMLCell($w="305", $h=0, $x=10, $y="", $output, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding = false);

            // stream the document
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"" . $filename . "_" . date("_Y-m-d_H-i-s") . ".pdf\"");
            $pdf->Output($filename . "_" . date("_Y-m-d_H-i-s") . ".pdf", 'I');

        }

        // fix memory leak
        $html->clear();

         // System Log
        $db->insert("system_log", array(
                                'time' => date("Y-m-d H:i:s")
                                ,'user_id' => $user1->id
                                ,'object' => "table_pos"
                                ,'action' => "Export to CSV"
                                ,'comment' => "Page ".$_SERVER["HTTP_REFERER"].", requested from ".gethostbyaddr($_SERVER['REMOTE_ADDR']) . " (" . $_SERVER['REMOTE_ADDR'] . ") " . "<br/>" . $_SERVER['HTTP_USER_AGENT']
        ));

    }

    // format values for csv / xml
    function safecsv($string){
        return '"' . str_replace(array('"', '&nbsp;', '&amp;'), array('""', '', '&'), trim($string)) . '"';
    }

    function safexml($string, $header = false){
        if ($header) return str_replace(array("/ ", "/", " ", ), array("", "", "_"), strtolower(strip_tags(trim(str_replace("&nbsp;","",$string)))));
        else return str_replace("&amp;nbsp;","", htmlspecialchars(strip_tags(trim(str_replace("&nbsp;","",$string)))));
    }

?>
