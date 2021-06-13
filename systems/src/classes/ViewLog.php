<?php

class ViewLog extends Object {

    public $table = "view_audit";
    
    public $left_join = "
        INNER JOIN patients p on p.id = t.patient_id
        INNER JOIN system_users u ON u.id=t.user_id
        INNER JOIN view_audit_sources s ON s.id = t.source_id
    ";
    public $other_selects = "
        ,u.fullname as user, s.description as source, s.class_name as object, s.method_name, CONCAT_WS(' ',p.title,CONCAT(p.surname,', ',p.name)) as patient
    ";
    public $orderby = "t.date_time";
    public $dir = "DESC";
    public $view_array = array(
        'date_time'=>array("column"=>"date_time","name"=>"Time"),
        'user'=>array("name"=>"User","column"=>"user"),
        'source'=>array("name"=>"Source","column"=>"source"),
        'object'=>array("name"=>"Object","column"=>"object"),
        'patient'=>array("name"=>"patient","column"=>"patient"),        
    );

    function _set_table_list_row_items($item){

        global $db, $cfg, $user1, $libhtml;

        if ($item->user_id>0) $item->user = href_link(array(
                "permission"=>$user1->{"user_details.php"},
                "url"=>$cfg['root'] . "user_details.php?user_id=$item->user_id",
                "text"=>$item->user,
                "tooltip"=>"User Details",
                "button"=>false,
                "popup"=>false,
                "expand_details"=>"User",
        ));

        $item->date_time = zero_date($item->date_time,$user1->preferences->dateformat . " H:i:s");

        
        $item->patient = href_link(array(
                "permission"=>true,
            "url"=>$cfg['root'] . "view_log.php?id=".$item->id,
                "text"=>$item->patient,
                "tooltip"=>'Click to see full details',
                "button"=>false,
                "popup"=>false,
                "expand_method"=>'ajax_print_auditLog',
                "expand_details"=>'ViewLog',
                'expand_url_details'=>true,
            ));

        

        return;
    }

    function delete_old_logs() {
        global $db;

        return $db->delete(
                $this->table,
                array(
                    "WHERE time<=?",
                    array('time' => date("Y-m-d H:i:s", strtotime("-6 months", time()))),
                    array('datetime')
                )
        );
    }

    function print_details(){
        $object = get_clean_object($this->object_id, $this->object);
        return $object->print_details();

    }
    
    function ajax_print_auditLog(){
        $summary = ($this->method_name == 'ajax_print_auditLog_summary');
        $html = '';
        if (!empty($this->patient_id)){
            $html .= '<h3>Patient Context</h3>';
            $html .= $this->getPatientDetails($summary);
        }
        if (!empty($this->encounter_id)){
            $html .= '<h3>Encounter Context</h3>';
            $html .= $this->getEncounterDetails($summary);
        }
        if (!empty($this->review_id)){
            $html .= '<h3>Review Context</h3>';
            $html .= $this->getReviewDetails($summary);
        }
        
        return $html;         
    }
        
    
    function getPatientDetails($summary=false){
        $object = get_clean_object($this->patient_id, "Fe_Patient");
        return $object->ajax_print_auditLog($summary);        
    }
    
    function getEncounterDetails($summary=false){
        $object = get_clean_object($this->encounter_id, "Fe_Encounter");
        return $object->ajax_print_auditLog($summary);        
    }
    
    function getReviewDetails($summary=false){
        $object = get_clean_object($this->review_id, "Fe_Review");
        return $object->ajax_print_auditLog($summary);        
    }
    

    function print_search_form(){
        global $db, $cfg, $user1, $libhtml;

        $html = $libhtml->form_start();

        $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
        $html .= open_table("600px","","action_form details_form");

        $html .= $libhtml->render_form_table_row_date("from_date", my_request("from_date"), "Date - From", "from_date",array('self_submit'=>true));
        $html .= $libhtml->render_form_table_row_date("to_date", my_request("to_date"), "Date - Until", "to_date",array('self_submit'=>true));

        $selection=$db->select("id, fullname","system_users",array(), array('order_by' => "ORDER BY fullname ASC"));
        $html .= $libhtml->render_form_table_row_selection("user", my_request("user"), "User", "user",$selection,"id","fullname",array('self_submit'=>true));

        $selection=$db->select_distinct("id, CONCAT_WS(' - ', code, description) as source","view_audit_sources",array(), array('order_by' => "ORDER BY source ASC"));
        $html .= $libhtml->render_form_table_row_selection("source", my_request("source"), "Source", "source",$selection,"id","source",array('self_submit'=>true));
        
        $html .= '
        <tr>
        <th style="width:200px;">
        <label for="fullname">Patient name</label>
        </th>
        <td>' .
                $libhtml->render_form_table_row_autocomplete("fullname_id", my_request('fullname_id'), "", "fullname_id","patients","CONCAT_WS(' ',name,surname)","id", array(
                    'where'=>"WHERE CONCAT_WS(' ',name,surname) LIKE ?",
                    //"dropdown"=>true,
                    "no_of_chars"=>2,
                    "placeholder"=>"Type 2 letters to start searching",
                    "minimal"=>true,
                    "self_submit"=>true,
                    "label_value"=>( (my_request('fullname_id')!='') ? $db->select_value("CONCAT_WS(' ',name,surname)", "patients", array("WHERE id = ?", array(my_request('fullname_id')), array("varchar"))) : '' )
                )) . '
                        </td>
                        </tr>';
        /* $selection=$db->select_distinct("object","system_log",array("WHERE object<>''", array(), array()), array('order_by' => "ORDER BY object ASC"));
        $html .= $libhtml->render_form_table_row_selection("object", my_request("object"), "Object", "object",$selection,"object","object",array('self_submit'=>true));

        if (my_request("object")!=''){

            $selection=$db->select_distinct("object_id","system_log",array("WHERE object=?", array(my_request('object')), array('varchar')), array('order_by' => "ORDER BY object_id DESC"));
            $html .= $libhtml->render_form_table_row_selection("object_id", my_request("object_id"), "Object ID", "object_id",$selection,"object_id","object_id",array('self_submit'=>true));
        } */

        $html .= close_table();
        $html .= $libhtml->render_form_table_row_hidden("search", "Search");
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

        $html .= $libhtml->form_end();

        $where = array(array(), array(), array());

        if (my_request("from_date")!="") {
            $where[0][]= "t.date_time>=?";
            $where[1][] = my_request("from_date") . " 00:00:00";
            $where[2][] = 'datetime';
        }

        if (my_request("to_date")!="") {
            $where[0][]= "t.date_time<=?";
            $where[1][] = my_request("to_date") . " 23:59:59";
            $where[2][] = 'datetime';
        }

        if (my_request("user")!="") {
            $where[0][]= "t.user_id=?";
            $where[1][] = my_request("user");
            $where[2][] = 'integer';
        }

        if (my_request("source")!="") {
            $where[0][]= "t.source_id=?";
            $where[1][] = my_request("source");
            $where[2][] = 'integer';
        }

        if (my_request('fullname_id')!=''){
            $where[0][] = 't.patient_id=?';
            $where[1][] = my_request('fullname_id');
            $where[2][] = 'varchar';
        }
        

        $where[0] = (!empty($where[0])) ? ' WHERE '.implode(' AND ',$where[0]) :'';

        return array(
                'html'=>$libhtml->page_search_section($html),
                'where'=>$where,
        );


    }
}
