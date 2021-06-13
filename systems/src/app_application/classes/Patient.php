<?php

    class Patient extends Object {

        public $table = "patients";
        public $left_join = "";
        public $other_selects = "
            , CONCAT_WS(', ',t.surname, t.name) as fullname
            ";
        public $orderby = "t.name";
        public $dir = "ASC";
        public $view_array = array(
            'hl7_id' => array('name' => 'Patient ID',"column"=>"hl7_id",'hide_filter'=>true),
            'fullname' => array('name' => 'Patient', "column"=>"surname"),
            'dob' => array('name' => 'DoB', "column"=>"dob", "width"=>"120px",'hide_filter'=>true),
            'gender' => array('name' => 'Gender', "column"=>"gender", "width"=>"120px",'hide_filter'=>true),
            'address' => array('name' => 'Address', "column"=>"address", 'toggle_all'=>true),
        );

         function __construct(){
             global $db, $libhtml, $user1;
             parent::__construct();		     
         }

        function select($id="", $feedback=true) {
            parent::select($id, $feedback);
            //$this->get_questionnaire();
        }

        

        function insert(){
            if(!empty($this->postcode)) $this->normalised_postcode = strtolower(str_replace(' ','',$this->postcode));
            parent::insert();            
        }

        function update(array $additional = array()) {
            if(!empty($this->postcode)) $additional['normalised_postcode'] = strtolower(str_replace(' ','',$this->postcode));
            parent::update($additional);            
        }

        function delete(){
            global $db;

            // delete patient
            $db->delete('patients', array('WHERE id = ?',array($this->id),array('integer')));

        }

        

        function _set_table_list_row_items($item) {
            global $db, $cfg, $user1, $libhtml;

            $item->hl7_id = href_link(array(
                "permission"=>$user1->{$libhtml->path ."patient_details.php"},
                "url"=>$cfg["root"] . $libhtml->path ."patient_details.php?patient_id=$item->id",
                "text"=>$item->hl7_id,
                "tooltip"=>"Patient Details",
                "button"=>false,
                "popup"=>false,
            ));

            $item->fullname = href_link(array(
                "permission"=>$user1->{$libhtml->path ."patient_details.php"},
                "url"=>$cfg["root"] . $libhtml->path ."patient_details.php?patient_id=$item->id",
                "text"=>$item->fullname,
                "tooltip"=>"Patient Details",
                "button"=>false,
                "popup"=>false,
            ));

            $item->dob = zero_date($item->dob);

            

            // $item->dob = zero_date($item->dob);
            $item->address = text_toggler(nl2br($item->address));
        }

        function print_form(){
            global $db, $user1, $libhtml, $cfg, $my_get, $my_post;

            $html = $libhtml->form_start();
            $html .= open_table();

            $selection = array_map('trim', explode(',', $db->select_value('value','test_app_system_settings',array('WHERE name=?',array('Titles'),array('varchar')))));
            $html .= $libhtml->render_form_table_row_selection($this->object_name."[title]", $this->title, "Title", "title",$selection,'','');

            $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name, "Name", "name");
            $html .= $libhtml->render_form_table_row($this->object_name."[surname]", $this->surname, "Surname", "surname");
            $showNHS = $user1->system_settings['Show NHS Number'];
            
            
            $html .= $libhtml->render_form_table_row($this->object_name."[hl7_id]", $this->hl7_id, "Patient identifier", "hl7_id");

            $html .= table_separator("100%", "Additional details");
            $html .= $libhtml->render_form_table_row_date($this->object_name."[dob]", $this->dob, "DoB", "dob");
            $html .= $libhtml->render_form_table_radio_selection($this->object_name."[gender]", $this->gender, "Gender", "gender",array('Male', 'Female', 'Unspecified'),'','');
            $html .= $libhtml->render_form_table_row($this->object_name."[postcode]", $this->postcode, "Postcode", "postcode");

            $html .= $libhtml->render_form_table_row_text($this->object_name."[address]", $this->address, "Address", "address");
            $html .= $libhtml->render_form_table_row_date($this->object_name."[deceased_date]",$this->deceased_date,"Deceased date", "deceased_date");

            $html .= close_table();

           return $html;

        }

        function print_details(){
            global $user1, $db, $libhtml, $cfg;

            $html = '
            <table class="page_layout">
                <tr>
                    <td class="col first_col">';

            $html .=  section(array("title"=>"Patient details"));

            $html .= open_table("100%");
            $html .= $libhtml->render_table_row("Patient ID", $this->hl7_id);
            $html .= $libhtml->render_table_row("Full Name",$this->title . " " . $this->name . " " . $this->surname);
            if (!empty($this->dob)) $html .= $libhtml->render_table_row("Date of Birth",zero_date($this->dob));
            if (!empty($this->deceased_date)) $html .= $libhtml->render_table_row("Deceased date", nl2br($this->deceased_date));
            if (!empty($this->gender)) $html .= $libhtml->render_table_row("Gender",$this->gender);
            if (!empty($this->postcode)) $html .= $libhtml->render_table_row("Postcode",$this->postcode);
            if (!empty($this->address)) $html .= $libhtml->render_table_row("Address",nl2br($this->address));
            $html .= $libhtml->render_table_row("DB ID",$this->id);
            $html  .= close_table();

            $html .= '</td>
            <td class="col">';

            $html .= '</td>
        </tr>
    </table>';

            return $html;

        }

        function print_search_form(){
            global $db, $cfg, $user1, $libhtml, $my_post;

            $html = $libhtml->form_start();
            $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
            $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);

            $html .= '<table style="width:100% !important;"><tr><td style="width:50%; padding-right: 5px;vertical-align:top;">';

            $html .= open_table("100%");

            $html .= '
        <tr>
            <th style="width:200px;">
                <label for="hl7_id">Patient ID</label>
            </th>
            <td>' .
                    $libhtml->render_form_table_row_autocomplete("hl7_id", my_request('hl7_id'), "", "hl7_id",$this->table,"hl7_id","id", array(
                        'where'=>"WHERE hl7_id LIKE ?",
                        //"dropdown"=>true,
                        "no_of_chars"=>2,
                        "placeholder"=>"Type 2 letters to start searching",
                        "minimal"=>true,
                        "self_submit"=>true,
                        "label_value"=>( (my_request('hl7_id')!='') ? $db->select_value("hl7_id", $this->table, array("WHERE id = ?", array(my_request('hl7_id')), array("varchar"))) : '' )
                    )) . '
            </td>
        </tr>';

        $html .= '
    <tr>
        <th style="width:200px;">
            <label for="fullname">Patient name</label>
        </th>
        <td>' .
                $libhtml->render_form_table_row_autocomplete("fullname_id", my_request('fullname_id'), "", "fullname_id",$this->table,"CONCAT_WS(' ',name,surname)","id", array(
                    'where'=>"WHERE CONCAT_WS(' ',name,surname) LIKE ?",
                    //"dropdown"=>true,
                    "no_of_chars"=>2,
                    "placeholder"=>"Type 2 letters to start searching",
                    "minimal"=>true,
                    "self_submit"=>true,
                    "label_value"=>( (my_request('fullname_id')!='') ? $db->select_value("CONCAT_WS(' ',name,surname)", $this->table, array("WHERE id = ?", array(my_request('fullname_id')), array("varchar"))) : '' )
                )) . '
        </td>
    </tr>';
                

            $html .= close_table();

            $html .= '</td><td style="width:50%; padding-right: 5px;vertical-align:top;">';

            $html .= open_table("100%");

            $html .= $libhtml->render_form_table_radio_selection("gender", my_request('gender'), "Gender", "gender",$db->select_distinct('gender',$this->table,array()),'gender','gender',array('self_submit'=>true));
            $html .= $libhtml->render_form_table_row_date_from_to("from_date", "to_date", my_request("from_date"), my_request("to_date"), "DoB (from - to)", "from_date",array('self_submit'=>true));

            $html .= close_table();

            $html .= '</td></tr></table>';

            $html .= $libhtml->form_end();

            $where = array(array(),array(),array());

            if (my_request('hl7_id')!=''){
                $where[0][] = 't.id=?';
                $where[1][] = my_request('hl7_id');
                $where[2][] = 'varchar';
            }

            if (my_request('fullname_id')!=''){
                $where[0][] = 't.id=?';
                $where[1][] = my_request('fullname_id');
                $where[2][] = 'varchar';
            }

            
            if (my_request('gender')!=''){
                $where[0][] = 't.gender=?';
                $where[1][] = my_request('gender');
                $where[2][] = 'varchar';
            }

            if (my_request('to_date')!=''){
                $where[0][] = 't.dob<=?';
                $where[1][] = my_request('to_date');
                $where[2][] = 'date';
            }

            if (my_request('from_date')!=''){
                $where[0][] = 't.dob>=?';
                $where[1][] = my_request('from_date');
                $where[2][] = 'date';
            }

            if (my_request('gp')!=''){
                $where[0][] = 't.gp=?';
                $where[1][] = my_request('gp');
                $where[2][] = 'varchar';
            }

            $where[0] = (!empty($where[0])) ? 'WHERE '.implode(' AND ',$where[0]) : '';

            return array(
                'html'=>$html,
                'where'=>$where
            );
        }


        

        

		
    }
