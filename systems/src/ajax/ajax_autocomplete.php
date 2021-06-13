<?php
    require_once dirname(__FILE__).'/../config/global.php';
    require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

    if ($user1->logged_in) {

        $table = urldecode(my_get("table"));
        $display_field = urldecode(my_get("display_field"));
        $id_field = urldecode(my_get("id_field"));
        $where = urldecode(my_get("where"));
        $order_by = urldecode(my_get("order_by"));

        foreach(array($display_field,$id_field) as $item) {
            if (strpos($item,".")===false && strpos($item,")")===false && strpos($item,"(")===false) {
                $escaped_fields[]=$db->column_escape($item);
            } else {
                $escaped_fields[]=$item;
            }
        }

        $fields = $escaped_fields[0].' as label,'.$escaped_fields[1].' as value';

        $q = my_get("term");
        $q = $_GET['term'];

        $selection = $db->select_distinct(
            $fields,
            $table,
            array(
                $where,
                array("%".$q."%"),
                array('varchar')
            ),
            array(
                'order_by'=>$order_by,
                'limit' => array('num_on_page' => 10, 'offset' => 0)
            )
        );

        $result = array();

        foreach ($selection as $key => $item) {
            $result[$item->label]["label"] = $item->label;
            $result[$item->label]["value"] = $item->value;
        }

        if (urldecode(my_get("match_insert")) && empty($result)) { // if we are allowing insert ONLY when no matches are found
            $result[0]["label"] = $q;
            $result[0]["value"] = $q;

        } else if (urldecode(my_get("allow_insert")) && !empty($q) && empty($result[$q])) { // if we are allowing insert at the same time, useful for %LIKE% queries
            $result[$q]["label"] = $q;
            $result[$q]["value"] = $q;

        }

        echo json_encode($result);
    }

    $db->close();
