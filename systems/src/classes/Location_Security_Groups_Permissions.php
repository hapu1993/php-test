<?php
class Location_Security_Groups_Permissions extends Object
{
    public $table = "locations";
    public $basic_selection = "t.id, t.name as Location";
    
    public $left_join = "
    LEFT JOIN (SELECT g.id as group_id, w.id, p.id as link_id  FROM 
        location_security_groups_permissions p
        INNER JOIN ward_security_groups g ON g.id = p.group_id 
        LEFT JOIN locations w ON w.id = p.location_id 
        where group_id = ?) l on l.id = t.id  
    INNER JOIN facilities f ON f.id = t.facility_id and f.active = 1
    INNER JOIN hospitals h ON h.id = f.hospital_id and h.active = 1 
    
";
    
    public $other_selects = ",CONCAT_WS(' - ',f.hl7_id,f.name) as facility, CONCAT_WS(' - ',h.hl7_id,h.name) as hospital, l.link_id, l.group_id  ";
    public $orderby = " t.name ";
    public $dir = " ASC";
    public $view_array = array(
        'hl7_id' => array('name' => 'HL7 ID','column'=>'hl7_id'),
        'name'=>array("name"=>"Location",'column'=>'Location'),
        'facility'=>array("name"=>"Facility", 'column'=>'facility'),
        'hospital'=>array("name"=>"Hospital",'column'=>'hospital'),
        'link_id'=>array('name'=>"Active", "width"=>"100px"),
    );
    
    public $params = array();
    
    function __construct(){
        global $db, $cfg, $libhtml;
        parent::__construct();
        
        
        $this->view_array['facility'] = array("name"=>$libhtml->local_text["Facility"], 'column'=>'facility');
        $this->view_array['hospital'] = array("name"=>$libhtml->local_text['Hospital'],'column'=>'hospital');
        $this->view_array['name'] = array("name"=>$libhtml->local_text['Location'],'column'=>'Location');
    }
    
    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml, $wardSecurityGroups;
        
        $link = href_link(array(
            "permission"=>$user1->{$libhtml->path."edit_ward_security_group.php"},
            "url"=>$cfg["root"] . "includes/edit_ward_security_group_details.php?subtab=locations&location_id=".$item->id."&group_id=".$wardSecurityGroups->id.'&link_id='.$item->link_id.'&active_search='.my_request("active_search").'&path='.$libhtml->path,
            "text"=>tick_cross_image(!empty($item->link_id)),
            "tooltip"=>'Activate '.$libhtml->local_text['Location'].' Permission',
            "popup"=>false,
            "button"=>false,
            "float" => "",
            "clear"=>false));
        
        $item->link_id = $link;
        return;
    }
    
    function _count($options = array()) {
        global $db;
        
        $this->make_db_defaults($options);
        
        $types = array();
        $types[] = array('DATA_TYPE'=>'integer');
        
        return $db->tcount($this->table . " t", $this->where, array('joins' => $this->left_join,'params'=>$this->params,'types'=>$types));
    }
    
    function _get($options = array()){
        global $db, $libhtml;
        
        $this->make_db_defaults($options);
        $types = array();
        $types[] = array('DATA_TYPE'=>'integer');
        
        return $db->select(
            $this->field_selection . $this->other_selects,
            $this->table . " t",
            $this->where,
            array(
                'joins' => $this->left_join,
                'order_by' => $this->orderbystring,
                'group_by' => $this->groupby,
                'limit' => $this->limit,
                'params'=>$this->params,
                'types'=>$types
            )
            );
    }
    
    function print_select_location_group_filter_form($full=true){
        global $db, $user1, $cfg, $libhtml;
        
        $html = $libhtml->form_start();
        $html .= '<table style="width:100% !important;"><tr><td style="width:50%; padding-right: 5px;vertical-align:top;">';
        $html .= open_table("100%");
        $html .= $libhtml->render_form_table_row_hidden("tab", $libhtml->tab);
        $html .= $libhtml->render_form_table_row_hidden("move_to_get", true);
        $html .= $libhtml->render_form_table_radio_selection("active_search", my_request('active_search'), "Active", "active_search", array('Active','Inactive','All'),'','',array('class'=>"self_submit"));
        $html .= close_table();
        $html .= '</td><td style="width:50%; padding-right: 5px;vertical-align:top;">';
        $html .= '</td></tr></table>';
        $html .= $libhtml->form_end();
        $where = array(array(),array(),array());
        
        if (my_request("active_search")=='Active'){
            $where[0][]= "l.link_id IS NOT NULL";
        } elseif (my_request("active_search")=='Inactive'){
            $where[0][]= "(l.link_id IS NULL)";
        }
        
        $where[0]=(!empty($where[0])) ? "WHERE ".implode(" AND ",$where[0]): '';
        
        return array(
            'html'=>$html,
            'where'=>$where,
        );
    }
}

