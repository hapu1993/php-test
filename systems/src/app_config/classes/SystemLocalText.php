<?php
class SystemLocalText extends Object{
    
    public $table = "system_local_text";
    public $left_join = "";
    public $other_selects = "";
    public $orderby = "t.local_group";
    public $dir = "ASC";
    public $object_name ="system_local_text";
    public $view_array = array(
        'local_group'=>array("name"=>"Label Group","column"=>"local_group"),
        'local_key'=>array("name"=>"Label Key","column"=>"local_key"),
        'value'=>array("name"=>"Label Value","column"=>"value"),
    );
    
    function print_form() {
        global $cfg, $db, $libhtml;
        
        $html = $libhtml->form_start();
        $html .= open_table();
        
        $html .= $libhtml->render_table_row('Label Group',$this->local_group);
        $html .= $libhtml->render_table_row('Label Key',$this->local_key);
        
        if (!empty($this->id)) {
            $html .= $libhtml->render_form_table_row("system_local_text[value]", $this->value, "Label Value", "value");            
        } else {            
            if (!empty($this->value)) $html .= $libhtml->render_table_row('Label Value',nl2br($this->value));
        }
        
        $html .= close_table();
        return $html;
    }
    
    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;
        
        $item->value = text_toggler($item->value);
        
        return;
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
                <label for="key">Label Value</label>
            </th>
            <td>' .
            $libhtml->render_form_table_row_autocomplete("value", my_request('value'), "", "value",$this->table,"value","id", array(
                'where'=>"WHERE value LIKE ?",
                //"dropdown"=>true,
                "no_of_chars"=>2,
                "placeholder"=>"Type 2 letters to start searching",
                "minimal"=>true,
                "self_submit"=>true,
                "label_value"=>( (my_request('value')!='') ? $db->select_value("value", $this->table, array("WHERE id = ?", array(my_request('value')), array("varchar"))) : '' )
            )) . '
            </td>
        </tr>';
        
            $html .= close_table();
            
            $html .= '</td></tr></table>';
            
            $html .= $libhtml->form_end();
            
            $where = array(array(),array(),array());
            
            if (my_request('value')!=''){
                $where[0][] = 't.id=?';
                $where[1][] = my_request('value');
                $where[2][] = 'varchar';
            }
            
            $where[0] = (!empty($where[0])) ? 'WHERE '.implode(' AND ',$where[0]) : '';
            return array(
                'html'=>$html, 
                'where'=>$where
            );
    }
    
}

