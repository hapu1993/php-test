<?php

    class Location extends Object {

        public $table = "locations";
        public $left_join = "
			LEFT JOIN facilities f ON f.id=t.facility_id
			LEFT JOIN hospitals h ON f.hospital_id = h.id
		";
        public $other_selects = "
			,CONCAT_WS(' - ',f.hl7_id,f.name) as facility_name
			,CONCAT_WS(' - ',h.hl7_id,h.name) as hospital_name
		";
        public $orderby = "t.name";
        public $dir = "ASC";

        function check_foreign_key_usage(){
            return false;
        }

        function __construct(){
            global $db, $cfg, $libhtml;

			parent::__construct();

			if (!isset($libhtml) || empty($libhtml)){
                $libhtml = new Libhtml();
            }

			$this->human_name= $libhtml->local_text['Location'];

			$this->view_array = array(
				'hl7_id' => array('name' => 'HL7 ID','column'=>'hl7_id'),
				'name' => array('name' => 'Name', "column"=>"name"),
				'facility_name' => array('name' => $libhtml->local_text['Facility'], "column"=>"facility_name"),
				'hospital_name' => array('name' => $libhtml->local_text['Hospital'], "column"=>"hospital_name"),
				'offsets' => array('name' => 'Next Review','width'=>'220px'),
				'active'=>array("name"=>"Active","width"=>"60px",'hide_filter'=>true),
			);

        }

        // You can delete a location if there are no encounters attached to it
        // Otherwise you should be able to archive the location
        function delete(){
            global $db, $libhtml;

            // find out if ward is used
            $db->delete('locations', array('WHERE id = ?',array($this->id), array('integer')));
                $_SESSION["feedback"] .= g_feedback("error", "This '.$libhtml->local_text['Location'].' has been deleted");

            return true;
        }

        function _set_table_list_row_items($item) {
            global $db, $cfg, $user1, $libhtml;
            $item->active = ajax_toggle($item->id, $this->table, "active", $user1->{$libhtml->path . "edit_location.php"}, $item->active);

			$item->offsets = array();
			if(!empty($item->q_out_review_offset)) $item->offsets[] = 'Qualified Date Offset: '.$item->q_out_review_offset;
			if(!empty($item->nq_out_review_offset)) $item->offsets[] = 'Non Qualified Date Offset: '.$item->nq_out_review_offset;
			if(!empty($item->allow_next_date_selection)) $item->offsets[] = 'Allow Date Selection: Yes';

			$item->offsets = implode('<br/>',$item->offsets);
        }

        function print_form(){
            global $db, $user1, $libhtml, $cfg, $my_get;
            $html = $libhtml->form_start();
            $html .= open_table();
            $html .= $libhtml->render_form_table_row($this->object_name."[hl7_id]", $this->hl7_id, "HL7 ID", "hl7_id");
            $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name, "Name", "name");

			$selection = $db->select("id,CONCAT_WS(' - ',hl7_id,name) as name",'facilities',array(),array('order_by'=>'ORDER BY name ASC'));
			$html .= $libhtml->render_form_table_row_selection($this->object_name."[facility_id]", $this->facility_id, $libhtml->local_text['Facility'], "facility_id",$selection,'id','name',array(
				'required'=>true,
			));

            if (empty($this->id)) $this->active = 1;
            $html .= $libhtml->render_form_table_row_checkbox($this->object_name."[active]", $this->active, "Active", "active");

            $html .= close_table();

			$html .= open_table('','Next Review');

			$html .= $libhtml->render_form_table_row_selection($this->object_name."[nq_out_review_offset]",  $this->nq_out_review_offset, "Date Offset - Non Qualified", "nq_out_review_offset", Default_Preferences::$offsets, '','',array(

			));

            $html .= $libhtml->render_form_table_row_selection($this->object_name."[q_out_review_offset]",  $this->q_out_review_offset, "Date Offset - Qualified", "q_out_review_offset", Default_Preferences::$offsets, '','',array(

			));

			$html .= $libhtml->render_form_table_row_checkbox($this->object_name."[allow_next_date_selection]",  $this->allow_next_date_selection, "Allow Date Selection", "allow_next_date_selection");

			$html .= close_table();

            return $html;

        }

        function  get_user_accessible_locations($userId = -1, $defaultLocationId = -1){
            global $db, $user1,$libhtml;

			if (empty($defaultLocationId)) $defaultLocationId = -1;

            if ($userId == -1){
                $userId = $user1->id;
            }

			$locationCount = $db->select_value("count(id)", "user_ward_security_groups_links", array('WHERE user_id=?', array($userId),array('integer')));

			$locations = null;

			if ($locationCount > 0 ){

                $locations = $db->select_distinct(
					"l.id, CONCAT_WS(' - ', l.hl7_id, l.name) as name, COALESCE(l.name,l.hl7_id) as name_or_hl7_id",
                    'locations l
					LEFT JOIN location_security_groups_permissions p ON p.location_id=l.id
					LEFT JOIN user_ward_security_groups_links u ON u.group_id=p.group_id'
					,array(
						'WHERE (l.active = 1 AND u.user_id=?) OR l.id = ?',
						array($userId, $defaultLocationId),
						array('integer', 'integer')
					),
					array('order_by'=>'ORDER BY name ASC')
				);

            }else{

                $defaultWardSecurity =  $libhtml->system_settings['Default ward security'];

                if ($defaultWardSecurity == 'All'){

					$locations = $db->select(
						"id, CONCAT_WS(' - ', hl7_id, name) as name, COALESCE(name,hl7_id) as name_or_hl7_id",
						'locations',
						array("WHERE active = 1", array(), array()),
						array('order_by'=>'ORDER BY name ASC')
					);

                }else{

                    $locations = array();

				}

            }

            return $locations;

        }

    }
