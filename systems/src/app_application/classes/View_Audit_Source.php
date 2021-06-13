<?php
class View_Audit_Source
{
    private  $table = 'view_audit_sources';
    
    public function getId($className, $code){
        global $db;
        return $db->select_value('id', $this->table, array ("where code = ? and class_name = ?", array($code, $className),array('integer', 'varchar')));
    }
}

