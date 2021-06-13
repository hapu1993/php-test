<?php

    class Facility extends Object{

        public $table = "facilities";
        public $left_join = "
            LEFT JOIN hospitals h ON t.hospital_id = h.id
        ";
        public $other_selects = "
            ,CONCAT_WS(' - ',h.hl7_id,h.name) as hospital_name
        ";
        public $orderby = "t.name";
        public $dir = "ASC";

        function __construct(){
            global $db, $cfg, $libhtml;

            parent::__construct();

			if (!isset($libhtml) || empty($libhtml)){
                $libhtml = new Libhtml();
            }

			$this->human_name= $libhtml->local_text['Facility'];

			$this->view_array = array(
	            'hl7_id' => array('name' => 'HL7 ID','column'=>'hl7_id'),
	            'name' => array('name' => 'Name','column'=>'name'),
	            'hospital_name' => array('name' => $libhtml->local_text['Hospital'],'column'=>'hospital_name','column'=>'hospital_name'),
	            'comment' => array('name' => 'Comment', 'toggle_all'=>true),
	            'active'=>array("name"=>"Active","width"=>"60px",'hide_filter'=>true),
	        );

        }

        function check_foreign_key_usage(){
            return false;
        }

        // You can delete a facility if there are no patients attached to the facility or any of the lower level fields such as wards, physicians, clinical service, etc.
        // Otherwise you should be able to archive the facility. This will also archive all lower level fields. Archiving implies that these options will not be available in drop downs. If the archived values comes on HL7, it is discarded.
        function delete(){
            global $db, $libhtml;

            // find out if facility is used
            $facility_wards_usage = $db->select_value('COUNT(id)', 'wards', array('WHERE facility_id = ?',array($this->id), array('integer')));
            
            // if any of the above is not empty, set the depending items to inactive
            if (!empty($facility_wards_usage) ) {
                $db->update('wards', array("active"=>0), array('WHERE facility_id = ?',array("facility_id"=>$this->id), array('integer')));
                $db->update('facilities', array("active"=>0), array('WHERE id = ?',array("id"=>$this->id), array('integer')));

                $_SESSION["feedback"] .= g_feedback("error", "This ".$libhtml->local_text['Facility'].", but also ".str_plural($libhtml->local_text['Ward']).", Physicians and Clinical Services related to the ".strtolower($libhtml->local_text['Facility'])." have been set to inactive");

            } else {
                $db->delete('wards', array('WHERE facility_id = ?',array($this->id), array('integer')));
                $db->delete('facilities', array('WHERE id = ?',array($this->id), array('integer')));

                $_SESSION["feedback"] .= g_feedback("error", "This ".$libhtml->local_text['Facility'].", but also ".str_plural($libhtml->local_text['Ward']).", Physicians and Clinical Services related to the ".strtolower($libhtml->local_text['Facility'])." have been deleted");

            }

            return true;
        }

        function _set_table_list_row_items($item) {
            global $db, $cfg, $user1, $libhtml;

            $item->comment = text_toggler(nl2br($item->comment));
            $item->active = ajax_toggle($item->id, $this->table, "active", $user1->{$libhtml->path . "edit_facility.php"}, $item->active);

			$item->offsets = array();
			if(!empty($item->q_review_offset)) $item->offsets[] = 'In, Q: '.$item->q_review_offset;
			if(!empty($item->nq_review_offset)) $item->offsets[] = 'In, NQ: '.$item->nq_review_offset;
			if(!empty($item->q_out_review_offset)) $item->offsets[] = 'Out, Q: '.$item->q_out_review_offset;
			if(!empty($item->nq_out_review_offset)) $item->offsets[] = 'Out, NQ: '.$item->nq_out_review_offset;

			$item->offsets = implode('<br/>',$item->offsets);

		}

        function print_form(){
            global $db, $user1, $libhtml, $cfg, $my_get;

            $html = $libhtml->form_start();
            $html .= open_table();

            $selection = $db->select('id, CONCAT_WS(", ", hl7_id, name) as name', 'hospitals', array(),array('order_by'=>'ORDER BY name ASC'));
            $html .= $libhtml->render_form_table_row_selection($this->object_name."[hospital_id]", $this->hospital_id, $libhtml->local_text['Hospital'], "hospital_id",$selection, 'id','name');

            $html .= $libhtml->render_form_table_row($this->object_name."[hl7_id]", $this->hl7_id, "HL7 ID", "hl7_id");
            $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name, "Name", "name");
            if(!empty($libhtml->system_settings['Show CQUIN Controls'])) $html .= $libhtml->render_form_table_row($this->object_name."[cquin_code]", $this->cquin_code, "CQUIN Code", "cquin_code");
            $html .= $libhtml->render_form_table_row_text($this->object_name."[comment]", $this->comment, "Comment", "commment");

            if (empty($this->id)) $this->active = 1;
            $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[active]", $this->active, "Active", "active");

            $html .= close_table();


            return $html;

        }

        function get_facilities_by_accessible_wards_and_locations($defaultFacilityId = -1){
            global $db, $user1, $libhtml;

            if (empty($defaultFacilityId))$defaultFacilityId = -1;

			$accessibleWardcount = $db->select_value(
				"count(id)",
				"user_ward_security_groups_links",
				array('WHERE user_id=?', array($user1->id),array('integer'))
			);

			$facilites = null;

			if ($accessibleWardcount > 0 ){

			    $facilites = $db->select_distinct(
					"f.id,CONCAT_WS(', ',CONCAT_WS(' - ', f.hl7_id, f.name),h.name) as name, COALESCE(f.name,f.hl7_id) as name_or_hl7_id",
                    "facilities f
                    LEFT join user_accessible_wards_and_locations a on f.id = a.facility_id
                    INNER JOIN hospitals h ON h.id=f.hospital_id ",
					array('WHERE a.user_id=? OR f.id =?',array($user1->id, $defaultFacilityId),array('integer', 'integer')),
                    array('order_by'=>'ORDER BY name ASC')
				);

            }else{

                $defaultWardSecurity =  $libhtml->system_settings['Default ward security'];

                if ($defaultWardSecurity == 'All'){

				    $facilites = $db->select(
						"t.id, CONCAT_WS(', ',CONCAT_WS(' - ', t.hl7_id, t.name),h.name) as name, COALESCE(t.name,t.hl7_id) as name_or_hl7_id",
						'facilities t LEFT JOIN hospitals h ON h.id=t.hospital_id',
						array("WHERE t.active = 1", array(), array()),
						array('order_by'=>'ORDER BY name ASC')
					);

				}else{

				    $facilites = array();

				}

			}

            return $facilites;

        }




    }
