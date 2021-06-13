<?php

    class Hospital extends Object{

        public $table = "hospitals";
        public $left_join = "";
        public $other_selects = "";
        public $orderby = "t.name";
        public $dir = "ASC";
        public $view_array = array(
            'hl7_id' => array('name' => 'Hl7 ID','column'=>'hl7_id'),
            'name' => array('name' => 'Name','column'=>'name'),
            'comment' => array('name' => 'Comment', 'toggle_all'=>true),
            'active'=>array("name"=>"Active","width"=>"60px",'hide_filter'=>true),
        );

        function check_foreign_key_usage(){
            return false;
        }

        function __construct(){
            global $db, $cfg, $libhtml;
            parent::__construct();
            if (!isset($libhtml) || empty($libhtml)){
                $libhtml = new Libhtml();
            }

            $this->human_name= $libhtml->local_text['Hospital'];
        }

        // You can delete a hospital if there are no links to lower level fields such as facilities, wards, physicians, clinical service, etc.
        // Otherwise you should be able to archive the hospital. This will also archive all lower level fields. Archiving implies that these options will not be available in drop downs. If the archived values comes on HL7, it is discarded.
        function delete(){
            global $db, $libhtml;

            // find out if facility is used
            $hospital_facility_usage = $db->select_value('COUNT(id)', 'facilities', array('WHERE hospital_id = ?',array($this->id), array('integer')));
            $hospital_wards_usage = $db->select_value('COUNT(t.id)', 'wards t LEFT JOIN facilities f ON f.id = t.facility_id', array('WHERE f.hospital_id = ?',array($this->id), array('integer')));
            
            // if any of the above is not empty, set the depending items to inactive
            if (!empty($hospital_facility_usage) && !empty($hospital_wards_usage)) {
                $db->update('facilities', array("active"=>0), array('WHERE hospital_id = ?',array("hospital_id"=>$this->id), array('integer')));
                $db->update('wards', array("active"=>0), array('WHERE facility_id IN (SELECT id FROM facilities WHERE hospital_id = '.$this->id.')', array(), array()));
                $db->update('hospitals', array("active"=>0), array('WHERE id = ?',array("id"=>$this->id), array('integer')));

                $_SESSION["feedback"] .= g_feedback("error", "This ".$libhtml->local_text['Hospital'].", but also ".str_plural($libhtml->local_text['Facility']).", ".str_plural($libhtml->local_text['Ward']).", Physicians and Clinical Services related to the ".strtolower($libhtml->local_text['Hospital'])." have been set to inactive");

            } 

            return true;
        }

        function _set_table_list_row_items($item) {
            global $db, $cfg, $user1, $libhtml;

            $item->comment = text_toggler(nl2br($item->comment));

			$item->active = ajax_toggle($item->id, $this->table, "active", $user1->{$libhtml->path . "edit_hospital.php"}, $item->active);

		}

        function print_form(){
            global $db, $user1, $libhtml, $cfg, $my_get;

            $html = $libhtml->form_start();

			$html .= open_table();

			$html .= $libhtml->render_form_table_row($this->object_name."[hl7_id]", $this->hl7_id, "HL7 ID", "hl7_id");
            $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name, "Name", "name");
            $html .= $libhtml->render_form_table_row_text($this->object_name."[comment]", $this->comment, "Comment", "commment");

            if (empty($this->id)) $this->active = 1;
            $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[active]", $this->active, "Active", "active");

            $html .= close_table();

            return $html;

        }

        function  get_user_accessible_hospital($userId = -1){
            global $db, $user1,$libhtml;

            if ($userId == -1){
                $userId = $user1->id;
            }

            $count = $db->select_value("count(id)", "user_ward_security_groups_links", array('WHERE user_id=?', array($userId),array('integer')));

			$selection = null;

			if ($count > 0 ){

                $selection = $db->select_distinct(
					"h.id, CONCAT_WS(' - ', h.hl7_id, h.name) as name, COALESCE(h.name,h.hl7_id) as name_or_hl7_id",
                    'hospitals h inner join user_accessible_wards_and_locations a on h.id = a.hospital_id  '
                    ,array('WHERE h.active = 1 AND a.user_id=?',array($userId),array('integer')),
                    array('order_by'=>'ORDER BY name ASC')
				);

            }else{

				$defaultWardSecurity =  $libhtml->system_settings['Default ward security'];

				if ($defaultWardSecurity == 'All'){

				    $selection = $db->select(
						"h.id, CONCAT_WS(' - ', h.hl7_id, h.name) as name, COALESCE(h.name,h.hl7_id) as name_or_hl7_id",
                        'hospitals h',
						array('WHERE h.active = 1 ', array(), array()),
						array('order_by'=>'ORDER BY name ASC')
					);

                }else{

					$selection = array();

				}

            }

            return $selection;
        }

    }
