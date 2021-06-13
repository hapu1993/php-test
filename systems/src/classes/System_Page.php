<?php
class System_Page {

    private $table = 'system_pages';
    private $table_types = array();

    public function __construct() {
        global $db;

        $table_schema = $db->get_table_column_metadata($this->table);

        foreach ($table_schema as $schema) {
            if (isset($schema->COLUMN_NAME)) {
                $this->table_types[$schema->COLUMN_NAME] = (array) $schema;
            } elseif (isset($schema->column_name)) {
                $this->table_types[$schema->column_name] = (array) $schema;
                $this->table_types[$schema->column_name]  = array_change_key_case($this->table_types[$schema->column_name], CASE_UPPER);
            }
        }
    }

    public function insert(array $data) {
        global $db;

        return $db->insert($this->table, $data, $this->table_types);
    }

    static function sort_app_pages($a,$b) {

            if (strpos($a,"/")) $a = substr($a,strpos($a,"/")+1);
            if (strpos($b,"/")) $b = substr($b,strpos($b,"/")+1);
            $aa = explode("_", $a);
            $bb = explode("_", $b);

            if (count($aa) == 2) {
                $aa1 = $aa[0];
                $aa2 = $aa[1];
            } else if (count($aa) > 2) {
                $aa1 = $aa[0];
                $aa2 = str_replace($aa1 . "_" , "", implode("_", $aa));
            } else if (count($aa) == 1) {
                $aa1 = $aa[0];
                $aa2 = "";
            } else {
                g_feedback("error","Error comparing $a, $b");
                return -1;
                //exit();
            }

            if (count($bb) == 2) {
                $bb1 = $bb[0];
                $bb2 = $bb[1];
            } else if (count($bb) > 2) {
                $bb1 = $bb[0];
                $bb2 = str_replace($bb1 . "_" , "", implode("_", $bb));
            } else if (count($bb) == 1) {
                $bb1 = $bb[0];
                $bb2 = "";
            } else {
                g_feedback("error","Error comparing $a, $b");
                return 1;
            }

            if ($aa2 > $bb2) {
                return 1;
            } else if ($aa2 < $bb2) {
                return -1;
            } else {
                if ($aa1 > $bb1) {
                    return 1;
                } else if ($aa1 < $bb1) {
                    return -1;
                } else {
                    return 0;
                }
            }

    }

}
