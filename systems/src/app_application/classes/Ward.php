<?php

    class Ward extends Object{

        public $table = "wards";
        public $left_join = "
            LEFT JOIN facilities f ON t.facility_id = f.id
        ";
        public $other_selects = "
            ,CONCAT_WS(' - ',f.hl7_id,f.name) as facility_name
        ";
        public $orderby = "t.name";
        public $dir = "ASC";

        public $cquin_types = array(
            'Medical Acute',
            'Surgical Acute',
            'Other',
        );

        function __construct(){
            global $db, $cfg, $libhtml;

            parent::__construct();

            if (!isset($libhtml) || empty($libhtml)){
                $libhtml = new Libhtml();
            }

			$this->human_name= $libhtml->local_text['Ward'];

			$this->view_array = array(
				'hl7_id' => array('name' => 'HL7 ID','column'=>'hl7_id'),
				'name' => array('name' => 'Name','column'=>'name'),
				'facility_name' => array('name' => $libhtml->local_text['Facility'],'column'=>'facility_name'),
				'cquin_type' => array('name' => 'CQUIN Type','column'=>'cquin_type','display'=>!empty($libhtml->system_settings['Show CQUIN Controls'])),
				'nhsi_report' => array('name' => 'Include in NHSE/I reports', "column"=>"nhsi_report"),
				'offsets' => array('name' => 'Next Review','width'=>'220px'),
				'comment' => array('name' => 'Comment','toggle_all'=>true),
				'active'=>array("name"=>"Active","width"=>"60px",'hide_filter'=>true),
			);

        }

        function check_foreign_key_usage(){
            return false;
        }

        // You can delete a ward if there are no encounters and reviews attached to it
        // Otherwise you should be able to archive the ward
        function delete(){
            global $db, $libhtml;

            
            $db->delete('wards', array('WHERE id = ?',array($this->id), array('integer')));
            $_SESSION["feedback"] .= g_feedback("error", "This ".$libhtml->local_text['Ward']." has been deleted");

            

            return true;
        }

        function _set_table_list_row_items($item) {
            global $db, $cfg, $user1, $libhtml;

            $item->comment = text_toggler(nl2br($item->comment));
            $item->active = ajax_toggle($item->id, $this->table, "active", $user1->{$libhtml->path . "edit_ward.php"}, $item->active);
			$item->nhsi_report = tick_cross_image($item->nhsi_report);

			$item->offsets = array();
			if(!empty($item->q_review_offset)) $item->offsets[] = 'Qualified Date Offset: '.$item->q_review_offset;
			if(!empty($item->nq_review_offset)) $item->offsets[] = 'Non Qualified Date Offset: '.$item->nq_review_offset;
			if(!empty($item->allow_next_date_selection)) $item->offsets[] = 'Allow Date Selection: Yes';

			$item->offsets = implode('<br/>',$item->offsets);

        }

        function print_form(){
            global $db, $user1, $libhtml, $cfg, $my_get;

            $html = $libhtml->form_start();
            $html .= open_table();

            $selection = $db->select("t.id, CONCAT(CONCAT_WS(', ', h.hl7_id, h.name), ' - ', CONCAT_WS(', ', t.hl7_id, t.name)) as fullname",'facilities t LEFT JOIN hospitals h ON h.id=t.hospital_id',array(),array('order_by'=>'ORDER BY fullname ASC'));
            $html .= $libhtml->render_form_table_row_selection($this->object_name."[facility_id]", $this->facility_id, $libhtml->local_text['Facility'], "facility_id",$selection, 'id','fullname');

            $html .= $libhtml->render_form_table_row($this->object_name."[hl7_id]", $this->hl7_id, "HL7 ID", "hl7_id");

            $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name, "Name", "name");

            if(!empty($libhtml->system_settings['Show CQUIN Controls'])) $html .= $libhtml->render_form_table_row_selection($this->object_name."[cquin_type]", $this->cquin_type, "CQUIN Type", "cquin_type",$this->cquin_types,'','');

			

            $html .= $libhtml->render_form_table_row_text($this->object_name."[comment]", $this->comment, "Comment", "commment");

            if (empty($this->id)) $this->active = 1;
            $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[active]", $this->active, "Active", "active");

            $html .= close_table();

			
			$html .= close_table();

            return $html;

        }

        function  get_user_accessible_wards($userId = -1){
            global $db, $user1,$libhtml;

            if ($userId == -1){
                $userId = $user1->id;
            }

            $count = $db->select_value("count(id)", "user_ward_security_groups_links", array('WHERE user_id=?', array($userId),array('integer')));

			$wards = null;

			if ($count > 0 ){

			    $wards = $db->select_distinct(
					"w.id, CONCAT_WS(' - ', w.hl7_id, w.name) as name, COALESCE(w.name,w.hl7_id) as name_or_hl7_id",
					'wards w
					INNER JOIN ward_security_groups_permissions p ON p.ward_id=w.id
					INNER JOIN user_ward_security_groups_links u ON u.group_id=p.group_id',
					array('WHERE w.active = 1 AND u.user_id=?',array($userId),array('integer')),
					array('order_by'=>'ORDER BY name ASC')
				);

            }else{

				$defaultWardSecurity =  $libhtml->system_settings['Default ward security'];

				if ($defaultWardSecurity == 'All'){

				    $wards = $db->select(
						"w.id, CONCAT_WS(' - ', w.hl7_id, w.name) as name, COALESCE(w.name,w.hl7_id) as name_or_hl7_id",
                        'wards w',
						array('WHERE w.active = 1 ', array(), array()),
						array('order_by'=>'ORDER BY name ASC')
					);

                }else{

					$wards = array();

				}

			}

			return $wards;
		}

        function  get_user_accessible_wards_by_facility($facilityId = -1, $defaultWard = -1){
            global $db, $user1,$libhtml;

            if (!isset($defaultWard) || empty($defaultWard))$defaultWard = -1;

			if (!isset($facilityId) || empty($facilityId)) $facilityId = -1;

            $where =array(
				'WHERE (facility_id = ? AND active = 1 AND u.user_id=?) OR w.id = ?',
                array($facilityId,$user1->id,$defaultWard),
                array('integer','integer','integer')
			);

            $count = $db->select_value("count(id)", "user_ward_security_groups_links", array('WHERE user_id=?', array($user1->id),array('integer')));

			$wards = null;

			if ($count > 0 ){

				$wards = $db->select_distinct("w.id, CONCAT_WS(' - ', w.hl7_id, w.name) as name, COALESCE(w.name,w.hl7_id) as name_or_hl7_id",
					'wards w
					LEFT JOIN ward_security_groups_permissions p ON p.ward_id=w.id
					LEFT JOIN user_ward_security_groups_links u ON u.group_id=p.group_id',
					$where,
					array('order_by'=>'ORDER BY name ASC')
				);

            }else{

                $defaultWardSecurity =  $libhtml->system_settings['Default ward security'];

				if ($defaultWardSecurity == 'All'){

					$wards = $db->select(
						"w.id, CONCAT_WS(' - ', w.hl7_id, w.name) as name, COALESCE(w.name,w.hl7_id) as name_or_hl7_id",
						'wards w',
	                    array(
							'WHERE  (w.facility_id = ? OR w.facility_id = 0 OR w.facility_id IS NULL) AND w.active = 1 ',
	                        array($facilityId),
	                        array('integer')
						),
	                    array('order_by'=>'ORDER BY name ASC')
					);

				}else{

                    $wards = array();

				}

			}

            return $wards;

		}

    }
