<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_includes.php";

    $libhtml = new Libhtml(array(
        "tab" => my_get("tab","settings"),
        "title" => "System Settings",
    ));

    if ($libhtml->tab=='settings') {

		$object = new Setting;

		//Export
		if(my_get('export_to_csv')!=''){

			$object->export_to_csv();
			exit;

		}

		//Export to csv
		$libhtml->more_actions[] = href_link(array(
			"permission"=>true,
			"url"=>$cfg["root"] . $libhtml->path . "system_settings.php?export_to_csv=1",
			"text"=>"Export to csv",
			"clear"=>false,
			"popup"=>false,
		));

		//Import from csv
		$libhtml->more_actions[] = href_link(array(
			"permission"=>$user1->{$libhtml->path . "import_settings.php"},
			"url"=>$cfg["root"] . $libhtml->path . "import_settings.php",
			"text"=>"Import from csv",
			"clear"=>false,
		));

        $html .= $object->_list(array(
            'where'=>array('WHERE module IS NULL',array(),array()),
            'info' => true,
            'pagination'=>false,
            'table_wrapper'=>false,
        ));

    }

    $libhtml->render($html);
