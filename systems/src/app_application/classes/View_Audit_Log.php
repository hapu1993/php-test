<?php
class View_Audit_Log
{
    private $table = 'view_audit';
    private $table_types = array();
    private $bulk_insert_vals = array();
    
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
    
    public function insert($code, $className, $patientId="", $encounterId="", $reviewId="") {
        global $db, $user1;
        $data = array(array($user1->id, $patientId, $encounterId, $reviewId, date("Y-m-d H:i:s")));
        return $this->bulk_insert($code, $className, $data);
    }
    
    public function bulk_insert($code, $className, array $data = array()) {
        global $db, $user1;
        $start = microtime(true);
        $m1 = memory_get_usage();
        
        $total_rows = 0;
        if (empty($data)){
            $data = $this->bulk_insert_vals;
        }
        if (!empty($user1->system_settings['Enable View Audit'])){
            $sourceId = (new View_Audit_Source())->getId($className, $code);
            $dataset = array();
            foreach($data as $dataRow){
                $dataset [$sourceId.'-'.$dataRow[0].'-'.$dataRow[1].'-'.$dataRow[2].'-'.$dataRow[3]]= array_merge((array)$sourceId,$dataRow );
            }
            
            $auditInterval = $user1->system_settings['View Audit Interval'];
            if (strtolower(trim($auditInterval)) != 'now'){
                $dataset = $this->removeDuplicateLogsDuringSetInterwal($sourceId, $dataset);
            }else{
                $dataset = array_values($dataset);
            }
            if (!empty($dataset)){
                $fields = array('source_id','user_id', 'patient_id', 'encounter_id', 'review_id', 'date_time');
                $total_rows = $db->bulk_insert($this->table, $fields, $dataset);                
            }
            $this->bulk_insert_vals = array();
        }
        
        $end = microtime(true);
        $time = $end - $start;
        $m2 = memory_get_usage();
        
        $micro = sprintf("%06d",($time - floor($time)) * 1000000);
        $d = new DateTime( date('Y-m-d H:i:s.'.$micro, $time) );
        //print 'Time (H:m:s.micro Seconds) = '.$d->format("H:i:s.u");
        //print 'memory usage bytes = '.($m2 - $m1);
        
        return $total_rows;
    }
        
    
    public function bulk_log_collect($patientId, $encounterId="", $reviewId=""){
        global $user1;
        $this->bulk_insert_vals[$patientId.'.'.$encounterId.'.'.$reviewId] = array($user1->id, $patientId, $encounterId, $reviewId, date("Y-m-d H:i:s"));
    }
    
    public function removeDuplicateLogsDuringSetInterwal($sourceId ,array $dataset = array()){
        global $db, $user1;
        $auditInterval = $user1->system_settings['View Audit Interval'];
        $from = date('Y-m-d H:i:s', strtotime("-".$auditInterval));
        $to = date('Y-m-d H:i:s',strtotime("now"));
        $patientIdIn = "";
        $encounterIdIn = "";
        $reviewIdIn = "";
        $where = "where date_time between ? and ? and user_id = ? and source_id = ? ";
        foreach ($dataset as $row){
            if (empty($patientIdIn) && !empty($row[2])){
                $patientIdIn .= $row[2];
            }elseif(!empty($row[2])){
                $patientIdIn .= ", ".$row[2];
            }            
        }
        if (!empty($patientIdIn)){
            $where .= ' and patient_id in ('.$patientIdIn.')';
        }
        
        
        $logs = $db->select('source_id, user_id, patient_id, encounter_id, review_id, date_time', 'view_audit',
            array($where,
            array($from,$to,$user1->id, $sourceId),
            array('datetime', 'datetime', 'integer', 'integer')
            ));
        
        foreach($logs as $log) {
            unset ($dataset[$log->source_id.'-'.$log->user_id.'-'.$log->patient_id.'-'.$log->encounter_id.'-'.$log->review_id]);            
        }
        
        
        return array_values($dataset);
    
    }
}

