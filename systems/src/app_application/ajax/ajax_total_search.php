<?php
    require_once dirname(__FILE__).'/../../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in && !empty($my_post["term"])) {
        $fullname = "CONCAT_WS(' - ',hl7_id,CONCAT_WS(' ',title,name,surname),nhs_number)";
        $showNHS = $user1->system_settings['Show NHS Number'];
        if (empty($showNHS)){
            $fullname = "CONCAT_WS(' - ',hl7_id,CONCAT_WS(' ',title,name,surname))";
        }
         $people = $db->select(
             "id, ".$fullname." as fullname",
            "patients",
            array(
                "WHERE ".$fullname." LIKE ?",
                array("%".$my_post["term"]."%"),
                array('varchar')
            ),
            array("order_by"=>"ORDER BY fullname ASC")
        );

        $encounters = null;

        if (!empty($encounters) || !empty($people)) {

            $html = '<div class="dacwrap">
                        <div class="inwrap">
                            <div class="col frcol">
                                <h3>Encounters</h3>';

                        if (!empty($encounters)) {

                            foreach ($encounters as $encounter) {

                                $html .= href_link(array(
                                    "permission"=>$user1->{"app_application/admission_details.php"},
                                    "url"=>$cfg["root"] . "app_application/admission_details.php?admission_id=$encounter->id",
                                    "text"=>str_ireplace($my_post["term"], '<em class="lite">'.$my_post["term"].'</em>', $encounter->encounter_name),
                                    "button"=>false,
                                    "popup"=>false,
                                    "clear"=>false,
                                ));
                            }

                        } else {

                            $html .= '<p class="empty">There are no encounters that match your search term.</p>';

                        }

                        $html .= '
                            </div>
                            <div class="col">
                                <h3>People</h3>';

                        if (!empty($people)) {

                            foreach ($people as $person) {

                                $html .= href_link(array(
                                    "permission"=>$user1->{"app_application/patient_details.php"},
                                    "url"=>$cfg["root"] . "app_application/patient_details.php?patient_id=$person->id",
                                    "text"=>str_ireplace($my_post["term"], '<em class="lite">'.$my_post["term"].'</em>', $person->fullname ),
                                    "button"=>false,
                                    "popup"=>false,
                                    "clear"=>false,
                                ));
                            }

                        } else {

                            $html .= '<p class="empty">There are no people that match your search term.</p>';

                        }
                    $html .= '
                            </div>
                        </div>
                    </div>';

            echo $html;
        }
    }

    //error_log($my_post["term"]);
    //error_log(print_r($people,true));

    $db->close();
