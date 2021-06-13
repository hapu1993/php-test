<?php

    class Admission extends Object {
        
        public $human_name = 'Admission';

        public $table = "admissions";
        public $left_join = "
            LEFT JOIN patients p ON p.id = t.patient_id
            LEFT JOIN facilities f ON f.id = t.facility_id
            LEFT JOIN wards w ON w.id = t.ward_id            
        ";
        public $other_selects = "
            , CONCAT_WS(', ',p.surname, p.name) as fullname
            , p.dob
            , p.deceased_date
            , CONCAT_WS(' - ', f.hl7_id, f.name) as facility_name
            , CONCAT_WS(' - ', w.hl7_id, w.name) as ward_name,
            t.physician
        ";

        public $orderby = "t.date";
        public $dir = "DESC";
        public $view_array = array(
            'date'=>array('name'=>'Admission','width'=>'130px', "column"=>"date",'hide_filter'=>true),
            'discharge_date'=>array('name'=>'Discharge','width'=>'130px', "column"=>"discharge_date",'hide_filter'=>true),
            'fullname' => array('name' => 'Patient', "column"=>"fullname"),
            'facility_name'=>array('name'=>'Facility', "column"=>"facility_name"),
            'ward_name'=>array('name'=>'Ward', "column"=>"ward_name"),
            'room'=>array('name'=>'Room', "column"=>"room",'hide_filter'=>true),
            'bed'=>array('name'=>'Bed', "column"=>"bed",'hide_filter'=>true),
            'physician'=>array('name'=>'Admitting Consultant', "column"=>"physician"),
            'comment' => array('name' => 'Comment', 'toggle_all'=>true),                        
        );

        public function __construct() {
            parent::__construct();
            $this->left_join .= "";
            $this->other_selects .= "";
        }

        function select($id="", $feedback=true) {
            parent::select($id, $feedback);
            
        }

        
        /**
         * This is called from execute post after DB transaction logic otherwise
         * transaction will not be COMMITed due to redirection.
         *
         * @param  [type] $function Name of function called from execute_post
         * @return nothing
         */
        function redirect_after($function) {
            global $cfg, $crypt;

            if ($function == 'delete') {
                if (isset($this->skip_redirect) && $this->skip_redirect === false) {
                    // redirect to the review screen
                    header("Location: " . $cfg["website"] . "patient_details/" . $crypt->str_encrypt("patient_id=".$this->patient_id . '&tab=admissions'));
                    exit();
                }
            } elseif (in_array($function, array('save', 'update'))) {
                // redirect to the review screen
                header("Location: " . $cfg["website"] . "patient_details/" . $crypt->str_encrypt("patient_id=".$this->patient_id . '&tab=admissions'));
                exit;
            }
        }

        function insert(){
            global $cfg, $db, $user1, $crypt;

            if ($this->date_checks()) {

                parent::insert();

               

                return true;
            }
        }

        
        //For system log
        function show(){
            //return parent::print_details();
        }

        function date_checks(){
            global $user1, $libhtml, $cfg, $db, $crypt, $my_post;

            $error = false;

            // date cannot be empty
            if (empty($this->date)){
                $error = true;
                $_SESSION["feedback"] .= g_feedback("error", "Admission Date cannot be empty.");
            }

            // get all discharged encounters that are happening at the moment
            if (!empty($this->id)){
                $active_encounters = $db->tcount($this->table, array("WHERE patient_id = ? AND  date < ? AND discharge_date > ? AND id != ?", array($this->patient_id,  $this->date, $this->date, $this->id), array("integer",  "date", "date", "integer")));
            } else {
                $active_encounters = $db->tcount($this->table, array("WHERE patient_id = ? AND date < ? AND discharge_date > ?", array($this->patient_id,  $this->date, $this->date), array("integer",  "date", "date")));
            }

            if (!empty($active_encounters)){
                $error = true;
                $_SESSION["feedback"] .= g_feedback("error", "You cannot add a new admission because another admission is in progress.");
            }


            // all ok, proceed
            return !$error;

        }

        
        function print_details(){
            global $user1, $libhtml, $cfg, $db,$view_log;

            // $html = '<h3>Admission data</h3>';
            $view_log->insert(7, 'Fe_Encounter', $this->patient_id, $this->id);

            $html = open_table("", "", "action_form inline_form col-xs-6");

            $html .= $libhtml->render_table_row("HL7 ID", $this->hl7_id,array('two_columns'=>true));

                
            $html .= $libhtml->render_table_row("Admission Date", zero_date($this->date) . ' (Age: ...)',array('two_columns'=>true));
            $html .= $libhtml->render_table_row($libhtml->local_text['Facility'], (!empty($this->facility_name) ? $this->facility_name : ''),array('two_columns'=>true));
        
            
            $html .= table_separator("", "", "action_form inline_form col-xs-6");
            
            

            $ward = !empty($this->ward_name) ? $this->ward_name : '';
            $label = 'Admitting Ward';

            
            $ward.=' / '.$this->room;
            $label.=' / Room';
            
            
            $ward.=' / '.$this->bed;
            $label.=' / Bed';
            
            $html .= $libhtml->render_table_row($label,$ward,array('two_columns'=>true));

        
            $html .= $libhtml->render_table_row("Admitting Consultant", (!empty($this->physician) ? $this->physician : ''),array('two_columns'=>true));
            
            $html .= close_table();

            

            if (!empty($this->comment)) {
                $html .= open_table("", "", "action_form inline_form col-xs-12");
                    $html .= $libhtml->render_table_row("Admission Notes", nl2br($this->comment), array('two_columns'=>true));
                $html .= close_table();
            }

            return $html;

        }

        

        function print_add_form($options = array()){
            global $cfg, $user1, $libhtml, $db, $my_post, $my_get;

            
            if (!empty($my_get['patient_id'])) $this->patient_id = $my_get['patient_id'];
            if (!empty($this->patient_id)) $this->set_post(my_post('admission'));

            $html = $libhtml->form_start();
            // get patient details
            $patient = get_clean_object($this->patient_id, "Patient");

            $html .= '<div class="section"><h2>'.$patient->fullname.'</h2></div>';

            $html .= open_table("", "", "action_form col-xs-12");
            $html .= $libhtml->render_table_row("Date Of Birth", zero_date($patient->dob, "d M Y"),array('two_columns'=>true));
            $html .= $libhtml->render_table_row("Patient Id", (!empty($patient->hl7_id) ? $patient->hl7_id : '' ),array('two_columns'=>true));
            $html .= close_table();

            $html .= '<h3>Admission Details</h3>';
            $html .= open_table("", "", "action_form encounter_details");
            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[patient_id]", $this->patient_id);
     
                
        
            $this->date = (empty($this->date) ? date("Y-m-d") : $this->date);
            $html .= $libhtml->render_form_table_row_date($this->object_name."[date]", $this->date, "Admission Date", "encounter_date", array("min_date"=>date("Y-m-d", strtotime($patient->dob))));
      
          

            if (empty($this->facility_id) && !empty($my_post[$this->object_name]["facility_id"])) $this->facility_id = $my_post[$this->object_name]["facility_id"];

            $faciltieObj = new Facility;
            $facilitiesBYAccessibleWards = $faciltieObj-> get_facilities_by_accessible_wards_and_locations();
            $html .= $libhtml->render_form_table_row_selection($this->object_name."[facility_id]", $this->facility_id, $libhtml->local_text['Facility'], "facility_id",$facilitiesBYAccessibleWards, 'id', 'name',array('self_submit'=>true));

            if (!empty($this->facility_id)){
                $wardObj = new Ward;
                $wards_selection = $wardObj->get_user_accessible_wards_by_facility($this->facility_id);                
            } else {
                $wards_selection = array();
            }

            //Reset Room & Bed if ward has changed
            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[previous_ward_id]", $this->ward_id);
            $ids = array_map(function($o) { return $o->id; }, $wards_selection);
            if (!empty($this->previous_ward_id) && ($this->previous_ward_id!=$this->ward_id || $this->ward_id==null || !in_array($this->ward_id,$ids))){
                $this->room = $this->bed = $my_post[$this->object_name]['room'] = $my_post[$this->object_name]['bed'] = null;
            }

            $html .= $libhtml->render_form_table_row_selection($this->object_name."[ward_id]", $this->ward_id, $libhtml->local_text['Ward'], "ward_id",$wards_selection, 'id','name',array('self_submit'=>true));

            
            $html .= $libhtml->render_form_table_row($this->object_name."[room]", $this->room, "Room", "room",array('class'=>'form_style text'));

            

            
            $html .= $libhtml->render_form_table_row($this->object_name."[bed]", $this->bed, "Bed", "bed",array('class'=>'form_style text'));


            $html .= $libhtml->render_form_table_row($this->object_name."[physician]", $this->physician, "Consultant", "physician",array('class'=>'form_style text'));

            $html .= close_table();


            $html .= open_table("100%", "Admission Notes");
            $html .= $libhtml->render_form_table_row_text($this->object_name."[comment]", $this->comment, "Admission Notes", "commment", array());
            $html .= close_table();

            $html .= '<div class="separator"><span class="fatline"></span></div>';

            
            $html .= close_table();
        
            $html .= '<div class="actions">';
                $html .= $libhtml->render_button("save_and_exit", "save and exit");
            $html .= '</div>';

            $html .= $libhtml->form_end();

            return $html;
        }

        function print_edit_form($options = array()){
            global $cfg, $user1, $libhtml, $db, $my_post, $my_get;

            if (empty($this->patient_id)) $this->patient_id = my_request('patient_id');

            $html = $libhtml->form_start();

            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[id]", $this->id);

            // get patient details
            $patient = get_clean_object($this->patient_id, "Patient");

            $html .= '
            <div class="section">
                <h2>'.(!empty($patient->title) ? $patient->title .' ' : '') . $patient->name.' ' . $patient->surname.'</h2>
            </div>';

            $html .= open_table("", "", "action_form col-xs-12");
            $html .= $libhtml->render_table_row("Date Of Birth", zero_date($patient->dob, "d M Y"),array('two_columns'=>true));
            $html .= $libhtml->render_table_row("Patient Id", (!empty($patient->hl7_id) ? $patient->hl7_id : '' ),array('two_columns'=>true));
            $html .= close_table();

            $html .= '<h3>Admission Details</h3>';

            $html .= open_table();

            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[patient_id]", $this->patient_id);

                        
            // disable changing of the dates
        
            if (empty($this->date)) $this->date = date("Y-m-d");
            $html .= $libhtml->render_form_table_row_date($this->object_name."[date]", $this->date, "Admission Date", "encounter_date", array("min_date"=>date("Y-m-d", strtotime($patient->dob))));
        

            $html .= table_separator();
            if (empty($this->previous_facility_id) && empty($my_post[$this->object_name]["facility_id"])){
                $this->previous_facility_id = $this->facility_id;
            }else{
                $this->previous_facility_id = $my_post[$this->object_name]["previous_facility_id"];
            }

        
            if (
                empty($this->facility_id)
                && !empty($my_post[$this->object_name]["facility_id"])
            ) $this->facility_id = $my_post[$this->object_name]["facility_id"];

            $facility_id = $this->facility_id;
            if (empty($facility_id)){
                $facility_id = -1;
            }

            //Facility edit
            
            $faciltieObj = new Facility;
            $facilitiesBYAccessibleWards = $faciltieObj-> get_facilities_by_accessible_wards_and_locations($facility_id);
            $html .= $libhtml->render_form_table_row_selection($this->object_name."[facility_id]", $this->facility_id, $libhtml->local_text['Facility'], "facility_id", $facilitiesBYAccessibleWards, 'id', 'name', array('self_submit'=>true));

        
            if (!empty($this->facility_id)){
                $ward_id = $this->ward_id;
                if ((!empty($this->previous_ward_id) && ($this->previous_ward_id!=$this->ward_id)) || empty($ward_id) || (!empty($this->previous_facility_id) && $this->previous_facility_id != $this->facility_id)){
                    $ward_id = -1;
                }
                $wardObj = new Ward;
                $wards_selection = $wardObj->get_user_accessible_wards_by_facility($this->facility_id, $ward_id);
                
            } else {

                $wards_selection = array();
                
            }

            //Reset Room & Bed if ward has changed
            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[previous_ward_id]", $this->ward_id);
            $html .= $libhtml->render_form_table_row_hidden($this->object_name."[previous_facility_id]", $this->facility_id);
            $ids = array_map(function($o) { return $o->id; }, $wards_selection);
            if (!empty($this->previous_ward_id) && ($this->previous_ward_id!=$this->ward_id || $this->ward_id==null || !in_array($this->ward_id,$ids))){
                $this->room = $this->bed = $my_post[$this->object_name]['room'] = $my_post[$this->object_name]['bed'] = null;
            }
            $allowed_empty = true;
            if (!empty($wards_selection) && count($wards_selection) == 1 && $wards_selection[0]->id == $this->ward_id){
                $allowed_empty = false;
            }

            
            $html .= $libhtml->render_form_table_row_selection($this->object_name."[ward_id]", $this->ward_id, $libhtml->local_text['Ward'], "ward_id",$wards_selection, 'id','name',array('self_submit'=>true, 'allowed_empty'=>$allowed_empty));

            $html .= $libhtml->render_form_table_row($this->object_name."[room]", $this->room, "Room", "room",array('class'=>'form_style text'));
            $html .= $libhtml->render_form_table_row($this->object_name."[bed]", $this->bed, "Bed", "bed",array('class'=>'form_style text'));

            $html .= $libhtml->render_form_table_row($this->object_name."[physician]", $this->physician, "Consultant", "physician",array('class'=>'form_style text'));

            $html .= close_table();

            $html .= open_table("100%", "Admission Notes");
            $html .= $libhtml->render_form_table_row_text($this->object_name."[comment]", $this->comment, "Admission Notes", "commment", array());
            $html .= close_table();

           

            $html .= '<div class="actions">';

                $html .= $libhtml->render_cancel_button("Close");

                $html .= href_link(array(
                    "permission"=>$user1->{"delete_admission.php"},
                    "url"=>$cfg["website"] . 'delete_admission.php?admission_id='.$this->id,
                    "text"=>"Delete Admission",
                    "popup"=>true,
                    "class"=>"pull-left",
                ));

                
                $html .= $libhtml->render_button("save_and_exit", "Save and exit");

            $html .= '</div>';

            $html .= $libhtml->form_end();

            return $html;
        }

        

        function save_and_exit(){
            global $cfg, $db, $user1, $crypt;

            $this->override_insert = true;

            $this->insert();

        }

        function update(array $additional = array()) {
            global $cfg, $db, $user1, $crypt;
            $this->update($additional);
        }

        function print_discharge_form($options = array()){
            global $db, $user1, $libhtml, $cfg, $my_get, $my_post;

            $html = $libhtml->form_start();
            
            $html .= '<div class="form-group">
    <label for="exampleInputEmail1">Discharge Date</label>
    <input type="date" class="form-control" id="exampleInputEmail1" name="discharge_date" aria-describedby="emailHelp" placeholder="Select Date">
    
  </div>';

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_button("discharge", 'Discharge')
                )
            );

            $html .= $libhtml->form_end();
            return $html;

        }

        function discharge($item){
            global $db, $user1, $libhtml, $cfg, $my_get, $my_post, $crypt;


            // check if the discharge date is valid
            $error = false;
            $discharge_date = $_POST['discharge_date'];
//            echo $this->date;
//            echo $discharge_date;
//
//            die();
            if ($discharge_date) {

                if($discharge_date >= $this->date ){

                    $same_day_discharges = $db->select_value("count(id)", "admissions", array('WHERE discharge_date=?', array($discharge_date),array('integer')));
                    if($same_day_discharges > 2){
                        $_SESSION["feedback"] .= g_feedback("error", "Already two discharges in same day");
                        $error = true;
                    }else{
                        $db->update('admissions', array("discharged"=>1,"discharge_date"=>$discharge_date), array('WHERE id = ?',array("id"=>$this->id), array('integer')));
                        parent::update(array(
                            "discharged"=>1,
                            "discharge_date"=>$discharge_date
                        ));

                    }

                }else{
                    $_SESSION["feedback"] .= g_feedback("error", "Discharge date should be greater than admitted date.");
                    $error = true;
                }



            } else {



                $_SESSION["feedback"] .= g_feedback("error", "Discharge Date is required");
                $error = true;
            }
            echo $discharge_date;


            // are there any other inpatient encounters in progress on the date you wish to discharge?


           

            if (!empty($error)){
                $_SESSION["show_popup"] = array(
                    "title"=>"Discharge Patient",
                    "original_action"=>$cfg["website"] . "discharge_patient/" . $crypt->str_encrypt("admission_id=" . $this->id),
                    "object"=>"Admission",
                    "function"=>"print_discharge_form",
                    "data"=>$my_post[$this->object_name]                    
                );

                return false;
            }


            $_SESSION["feedback"] = g_feedback("success", "Patient has been discharged.");

            
            

            return false;

        }

        function print_delete_details($options = array()){
            global $cfg, $user1, $libhtml, $db, $my_post, $my_get;

            $html = $libhtml->form_start();
                $html .= $libhtml->render_form_table_row_hidden($this->object_name."[id]", $this->id);

                // get patient details
                $patient = get_clean_object($this->patient_id, "Patient");

                $html .= '<div class="section">
                    <h2>'.(!empty($patient->title) ? $patient->title .' ' : '') . $patient->name.' ' . $patient->surname.'</h2>
                </div>';

                $html .= open_table("", "", "action_form col-xs-12");
                    $html .= $libhtml->render_table_row("Date Of Birth", zero_date($patient->dob, "d M Y"),array('two_columns'=>true));
                    $html .= $libhtml->render_table_row("Patient Id", (!empty($patient->hl7_id) ? $patient->hl7_id : '' ),array('two_columns'=>true));
                    $html .= '<tr><td colspan="2"><p class="mtp"><b style="color:red;">Important:</b> All Admission data will be deleted.</p></td></tr>';
                $html .= close_table();

                $html .= '<h3>Admission Details</h3>';
                $html .= open_table("", "", "action_form col-xs-6");

                $html .= $libhtml->render_table_row('Admission Date', zero_date($this->date) . ' (Age: ...)', array("two_columns"=>true));
                $html .= close_table();

                $html .= '<div class="actions">';

                    $html .= $libhtml->render_button("delete", "Yes");

                    $html .= href_link(array(
                        "permission"=>$user1->{"edit_admission.php"},
                        "url"=>$cfg["website"] . "edit_admission?admission_id=".$this->id,
                        "text"=>"No",
                        "class"=>"",
                    ));

                $html .= '</div>';

            $html .= $libhtml->form_end();
            return $html;
        }

        function delete(){
            global $cfg, $db, $crypt, $my_post;

            
            parent::delete();

            $_SESSION["feedback"] .= g_feedback("success", "Admission has been deleted");

        }


        function _set_table_list_row_items($item) {
            global $db, $cfg, $user1, $libhtml;

            if($item->discharged){
                $item->discharge_date = $item->discharge_date;
            }else{
                $item->discharge_date = href_link(array(
                    "permission"=>$user1->{$libhtml->path ."discharge_patient.php"},
                    "url"=>$cfg["root"] . $libhtml->path ."discharge_patient.php?id=$item->id",
                    "tooltip"=>"Discharge Patient",
                    "button"=>true,
                    "text"=>"Discharge",
                    "popup"=>true,
                ));
            }

            
                        
        }
        
    }
