<?php
class Ward_Security_Group extends Object{

    public $table = "ward_security_groups";
    public $left_join = "";
    public $other_selects = "
        ,(SELECT COUNT(*) FROM user_ward_security_groups_links l LEFT JOIN system_users u ON u.id=l.user_id WHERE l.group_id=t.id) as total_users
        ,(SELECT COUNT(*) FROM user_ward_security_groups_links l LEFT JOIN system_users u ON u.id=l.user_id WHERE l.group_id=t.id AND u.active=1) as active_users
    ";
    public $orderby = "t.name";
    public $dir = "ASC";
    public $view_array = array(
        'name'=>array("name"=>"Ward Security Group Name"),
        'active_users'=>array("name"=>"Active Users"),
        'total_users'=>array("name"=>"Total Users"),
        'comment'=>array("name"=>"Description",'toggle_all'=>true),
        'wards'=>array("name"=>"List of Wards",'toggle_all'=>true,"display" => false),
        'locations'=>array("name"=>"List of Locations",'toggle_all'=>true,"display" => false),
        'pick'=>array("name"=>"Link","display" => false,"width"=>"60px")
    );

    function __construct(){
        global $db, $cfg, $libhtml;
        parent::__construct();

        $this->human_name = $libhtml->local_text['Ward']." Security Group";
        $this->view_array['name'] = array("name"=>$libhtml->local_text['Ward']." Security Group Name");
        $this->view_array['wards'] = array("name"=>str_plural($libhtml->local_text['Ward']),"display" => false);
        $this->view_array['locations'] = array("name"=>str_plural($libhtml->local_text['Location']),"display" => false);
    }

    function delete() {
        global $cfg, $db;
        $db->delete(
	        "user_ward_security_groups_links",
	        array(
	            "WHERE group_id=?",
	            array('group_id' => $this->id),
	            array('integer')
	        )
        );
        return parent::delete();
    }

    /* function add() {
        global $cfg, $db;
        parent::add();

        $db->insert("ward_security_groups_permissions", array('ward_id'=>ward_id,'group_id'=>$this->id));

        return true;
    } */

    function print_form() {
        global $cfg, $db, $libhtml;

		$html = $libhtml->form_start();

        $html .= open_table();
        $html .= $libhtml->render_form_table_row($this->object_name."[name]", $this->name,"Name","name", array('required'=>true));
        $html .= $libhtml->render_form_table_row_text($this->object_name."[comment]", $this->comment,"Description","comment", array());
        $html .= close_table();

        return $html;
    }


    function print_delete_form() {
        global $cfg, $db, $libhtml;

        $html = '<div class="error">Are you sure you want to delete this object?</div>';

        $html .= $libhtml->form_start();

        $html .= open_table("","","action_form details");
        $html .= $libhtml->render_table_row("Name",$this->name);
        $html .= $libhtml->render_table_row("Description",$this->comment);

        $html .= table_separator("","This will remove ".$libhtml->local_text['Ward']." Security Group for the following users.");

        $selection = $db->select("t.id,t.fullname","system_users t",array("WHERE l.group_id=?", array('group_id' => $this->id), array('integer')), array('joins' => "LEFT JOIN user_ward_security_groups_links l ON l.user_id=t.id"));
        foreach ($selection as $item) $html .= $libhtml->render_table_row("Name",$item->fullname);

        $html .= close_table();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("delete_ward_security_group", "Delete")
            )
        );

        $html .= $libhtml->form_end();

        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        $item->name = href_link(array(
			"permission"=>$user1->{$libhtml->path."ward_security_group_details.php"},
			"url"=>$cfg['root'] . $libhtml->path."ward_security_group_details.php?subtab=wards&ward_security_group_id=".$item->id.'&active_search=All',
			"text"=>$item->name,
			"tooltip"=>$libhtml->local_text['Ward']." security group permissions details",
			"button"=>false,
			"popup"=>false,
        ));

        $item->active_users = href_link(array(
            "permission"=>$user1->{$libhtml->path."ward_security_group_details.php"},
            "url"=>$cfg['root'] . $libhtml->path."ward_security_group_details.php?subtab=users&ward_security_group_id=".$item->id.'&active_search=Active',
            "text"=>$item->active_users,
            "tooltip"=>"Active ".$libhtml->local_text['Ward']." group users",
            "button"=>false,
            "popup"=>false,
            "tooltip"=>'Active Users',
             //'expand_method'=>'show_active_users',
             //"expand_details"=>"Ward_Security_Group",
        ));

        $item->total_users = href_link(array(
            "permission"=>$user1->{$libhtml->path."ward_security_group_details.php"},
            "url"=>$cfg['root'] .$libhtml->path. "ward_security_group_details.php?subtab=users&ward_security_group_id=".$item->id.'&active_search=All',
            "text"=>$item->total_users,
            "tooltip"=>"All ".$libhtml->local_text['Ward']." group users",
            "button"=>false,
            "popup"=>false,
            // 'expand_method'=>'show_all_users',
            // "expand_details"=>"Ward_Security_Group",
        ));

        $item->comment = text_toggler($item->comment);

         $wardsSelection = $db->select("CONCAT_WS(' - ', w.hl7_id, w.name) as name",'ward_security_groups_permissions p',
            array("WHERE g.id = ?", array('id' => $item->id), array('integer')),
            array(
                'joins' => " INNER JOIN ward_security_groups g ON g.id = p.group_id
                            LEFT JOIN wards w ON w.id = p.ward_id and w.active = 1 ",
                'order_by' => 'ORDER BY w.name ASC',
            ));

        $wards = array();

        foreach ($wardsSelection as $row) {
            $wards[] = $row->name;
        }

        $item->wards = "<div style='white-space: normal;'>".trim(implode(', ', $wards))."</div>";

        $locationsSelection = $db->select("CONCAT_WS(' - ', w.hl7_id, w.name) as name",'location_security_groups_permissions p',
            array("WHERE g.id = ?", array('id' => $item->id), array('integer')),
            array(
                'joins' => " INNER JOIN ward_security_groups g ON g.id = p.group_id
                             LEFT JOIN locations w ON w.id = p.location_id and w.active = 1 ",
                'order_by' => 'ORDER BY w.name ASC',
            ));
        $locations = array();
        foreach ($locationsSelection as $row) {
            $locations[] = $row->name;
        }
        $item->locations = "<div style='white-space: normal;'>".trim(implode(', ', $locations))."</div>";

        // $formname, $value="", $fieldname, $id, $options=[]
        $item->pick = $libhtml->render_form_table_row_checkbox('user[ward_group_ids]['.$item->id.']', !empty($this->user['ward_group_ids'][$item->id]), 'user[ward_group_ids]['.$item->id.']' , 'user[ward_group_ids]['.$item->id.']', array('minimal'=> true)) ;

        //"<input id='_' type='hidden' value='0' name='".$this->object_name."[ward_group_id]'><input type='checkbox' class='checkbox form_style' value='".$item->id."' name='".$this->object_name."[ward_group_id]' id='ward_group_id_".$item->id."' />";

        return;
    }

    function show_users($where = array()){
        global $user1;
        return $user1->_list(array(
            'width'=>'100%',
            'pagination'=>false,
            'table_wrapper'=>false,
            'view_reset'=>array(
                'status'=>false,
            ),
            'where'=>$where
        ));
    }

    function show_active_users(){
        return $this->show_users(array(
            'WHERE t.active=1 AND t.id IN (SELECT user_id FROM user_ward_security_groups_links WHERE group_id=?)',
            array($this->id),
            array('integer'),
        ));
    }

    function show_inactive_users(){
        return $this->show_users(array(
            'WHERE (t.active=0 OR t.active IS NULL) AND t.id IN (SELECT user_id FROM user_ward_security_groups_links WHERE group_id=?)',
            array($this->id),
            array('integer'),
        ));
    }

    function show_all_users(){
        return $this->show_users(array(
            'WHERE t.id IN (SELECT user_id FROM user_ward_security_groups_links WHERE group_id=?)',
            array($this->id),
            array('integer'),
        ));
    }

    function select($id="",$feedback=true){
        parent::select($id);
    }


}
