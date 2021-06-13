<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $patient = get_clean_object(my_get("patient_id", 0), "Patient");
    $encounter = new Admission;

    $libhtml = new Libhtml(array(
        "title" => array("Patient Details", $patient->fullname),
        "add_to_url" => "patient_id=$patient->id",
        "show_back"=> true,
        "header_search"=>true,
        'header_search_label'=>'Find Patient/Admissions',
        
    ));

    

    $api_key = null;

    $search_term = $patient->hl7_id;
    if (empty($search_term)) $search_term = $patient->fullname;

    if (!empty($search_term)){
        $libhtml->page_actions[] = href_link(array(
            "permission"=>$user1->{"patient_details.php"},
            "encrypt"=>false,
            "text"=>"Contextual Launch",
            "popup"=>false,
            "target"=>"_blank",
            "class"=>"blue",
            "clear"=>false,
        ));
    }

    
    $html .=  $patient->print_details();

    $html .= href_link(array(
        "permission"=>$user1->{"app_application/new_admission.php"},
        "url"=>$cfg["website"] . "app_application/new_admission.php?patient_id=" . $patient->id,
        "trwrap"=>false,
        "popup"=>true,
        "button"=>true,
        "text"=>"Add New Admission",
    ));

    $html .=  section(array("title"=>"Admissions"));
    $encounter = new Admission;

    $html .= $encounter->_list(array(
        'width'=>'100%',
        'where'=>$patient->sql_where,
        'edit'=>false,
        'view_reset'=>array('fullname'=>false,'nhs_number'=>false),
    ));

    
    $libhtml->render($html);
