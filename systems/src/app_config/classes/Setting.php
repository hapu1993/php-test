<?php

class Setting extends Object{

    public $table = "test_app_system_settings";
    public $left_join = "";
    public $other_selects = "";
    public $orderby = "t.name";
    public $dir = "ASC";
    public $view_array = array(
        'name'=>array("name"=>"Setting","column"=>"name"),
        'comment'=>array("name"=>"Comment/instructions","toggle_all"=>true),
        'type'=>array("name"=>"Type","column"=>"type"),
        'value'=>array("name"=>"Value"),
    );

    public $types = array('String','Text','Rich Text','Number','Selection','Yes/No');

    function print_form() {
        global $cfg, $db, $libhtml;

        $html = $libhtml->form_start();
        $html .= open_table();

        if (empty($this->id)) {

            $html .= $libhtml->render_form_table_row("setting[name]", $this->name, "Setting", "name");
            $html .= $libhtml->render_form_table_row_selection("setting[type]", $this->type, "Type", "type",$this->types,'','',array('self_submit'=>true));
            $html .= $libhtml->render_form_table_row_text("setting[comment]", $this->comment, "Comment", "comment");

        } else {

            $html .= $libhtml->render_table_row('Setting',$this->name);
            $html .= $libhtml->render_table_row('Type',$this->type);
            if (!empty($this->comment)) $html .= $libhtml->render_table_row('Comment',nl2br($this->comment));
        }

        if (!empty($this->type)){

            if ($this->type=='Selection'){
                $selection = array_map('get_local_text', explode(',', $this->values));
                $values = implode(", ", $selection);
                $html .= $libhtml->render_table_row("Possible Values", $values); //$this->values
            }

            if ($this->type=='String'){
                $html .= $libhtml->render_form_table_row("setting[value]", $this->value, "Value", "value");
            } elseif ($this->type=='Text'){
                $html .= $libhtml->render_form_table_row_text("setting[value]", $this->value, "Value", "value");
            } elseif ($this->type=='Rich Text'){
                $html .= $libhtml->render_form_table_row_text("setting[value]", $this->value, "Value", "value",array('rte'=>true));
            } elseif ($this->type=='Number'){
                $html .= $libhtml->render_form_table_row("setting[value]", $this->value, "Value", "value",array('number'=>true));
            } elseif ($this->type=='Yes/No'){
                $html .= $libhtml->render_form_table_row_checkbox("setting[value]", $this->value, "Yes / No", "value");
            } elseif ($this->type=='Selection' && !empty($this->values)){

                $selection = array_map('get_local_text', explode(',', $this->values));
                if (count($selection)>4){
                    $html .= $libhtml->render_form_table_row_selection("setting[value]", $this->value, "Value", "value",$selection,'','');
                } else {
                    $html .= $libhtml->render_form_table_radio_selection("setting[value]", $this->value, "Value", "value",$selection,'','');
                }

            }



        }


        $html .= close_table();
        return $html;
    }

    function _set_table_list_row_items($item){
        global $db, $cfg, $user1, $libhtml;

        if ($item->type=='Selection') {
            $selection = array_map('get_local_text', explode(',', $item->values));
            $values = implode(", ", $selection);
            $item->type = $item->type .'<br/>' . $values; //$item->values
        } elseif ($item->type=='Text'){
            $item->value = text_toggler($item->value);
        } elseif ($item->type=='Text'){
            $item->value = text_toggler($item->value);
        } elseif ($item->type=='Yes/No'){
            $item->value = yes_no($item->value);
        } else {

        }

        $item->comment = text_toggler($item->comment);

        return;
    }

	function export_to_csv(){

		if(my_get('module')==''){
			$where = array('WHERE module IS NULL',array(),array());
		} else {
			$where = array('WHERE module=?',array(my_get('module')),array('varchar'));
		}

		$selection = $this->_get(array(
			'where'=>$where,
			'limit'=>array('offset' => 0, 'num_on_page' => 1e9)
		));

		$row = array();
		foreach($this->view_array as $key=>$column){
			$row[] = !empty($column['name']) ? $column['name'] : '';
		}
		$data[] = $row;

		foreach($selection as $item){
			$row = array();
			foreach($this->view_array as $key=>$column){
				$row[] = strip_tags($item->$key);
			}
			$data[] = $row;
		}

		export_to_csv($data,'settings');

	}

	function print_import_form(){
		global $cfg, $db, $libhtml, $my_post, $my_get, $user1;

		$html = $libhtml->form_start();

		$html .=
		'<div class="hint">
			Import csv file must have columns with headings Setting and Value; all other columns will be ignored.
			<br/>If there is a System Setting whose name matches Setting column value, it will be updated. Other values will be ignored.
			<br/>There is no type or value validation on input values.
		</div>';

		$html .= open_table();

		$html .= $libhtml->render_form_table_row_file($this->object_name."[import]", "Upload the csv file", $this, "setting_imports/", array("accepted_ft"=>"csv", "required"=>true));
		$html .= close_table();

		$html .= $libhtml->render_submit_button("import", "Import");
		$html .= $libhtml->form_end();

		return $html;
	}

	function import(){
		global $cfg, $db, $libhtml, $my_post, $my_get, $user1, $crypt;

		if (!empty($this->import)){

			$csv = array_map('str_getcsv', file($cfg['secure_dir'].$this->import));

			if(!empty($csv[0])){

				$setting_key = array_search('Setting', $csv[0]);
				$value_key = array_search('Value', $csv[0]);

				unset($csv[0]);

				if($setting_key!==false && $value_key!==false){

					$inserted_count = 0;
					foreach($csv as $row){

						if(!empty($row[$setting_key]) && isset($row[$value_key])){

							if($db->tcount($this->table,array('WHERE name=?',array($row[$setting_key]),array('varchar')))>0){

								$inserted_count+=$db->update(
									$this->table,
									array('value'=>$row[$value_key]),
									array('WHERE name=?',array('name'=>$row[$setting_key]),array('varchar'))
								);

							}

						}

					}

					$_SESSION['feedback'] .= g_feedback("success", "Total of $inserted_count settings updated");

				} else {

					$_SESSION['feedback'] .= g_feedback("error", "Setting or Value columns missing");

				}

			} else {

				$_SESSION['feedback'] .= g_feedback("error", "Empty csv file");

			}

		}

	}

}
