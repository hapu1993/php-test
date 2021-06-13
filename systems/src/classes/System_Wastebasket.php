<?php
class System_Wastebasket {

    private $table = 'system_wastebasket';
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

}
