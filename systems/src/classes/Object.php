<?php
/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2017 Riskpoint London Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

/*
 * This is the Class Object.
 */

class Object {
    protected $allow_pk_insert = false;
    /**
     * This is the constructor function
     *
     * @global Global variable $db is an object of the Database Class.
     *
     * Function will set arrays of table_fields and table_types for the object to use. Will also generate a view array.
     */

    function __construct(){
        global $db, $libhtml, $user1, $system_log, $system_wastebasket;

        $this->system_log = $system_log;
        $this->system_wastebasket = $system_wastebasket;
        $this->class_name = get_class($this);
        $this->table_fields = array();
        $this->table_types = array();

        if (!isset($this->database)) $this->database = null;

        if (isset($this->table)) { //if the table is not set, forget all the table / column vars

            //If the table is set as database.table
            if (strpos($this->table, ".")!==false){

                list($database, $table) = explode(".", $this->table);
                $selection = $db->get_table_column_metadata($table, $database);

            } else {

                $selection = $db->get_table_column_metadata($this->table, $this->database);

            }

            foreach($selection as $item) {
                $this->table_fields[] = $item->COLUMN_NAME;
                $this->table_types[$item->COLUMN_NAME] = $item;
            }

            $defaults = array(
                'object_pk'=>'id',
                'object_name'=>strtolower(get_class($this)),
                'field_selection'=>"t." . implode(",t.",$this->table_fields),
                'groupby'=>'',
                'orderby'=>'',
                'dir'=>'DESC',
                'page'=>1,
                'num_on_page'=>(!empty($user1->preferences->pagination)) ? $user1->preferences->pagination : 20,
                'null_check_exceptions'=>array(),
                'allowed_delete_from_tables'=>array(), // shows a button next to cross linked tables user is allowed to delete links from
            );

            foreach($defaults as $key=>$value) if (!isset($this->$key)) $this->$key=$value;

            if (!isset($this->human_name)) $this->human_name = str_replace("_"," ",ucfirst($this->object_name));
            if (!isset($this->num_on_page)) $this->num_on_page = (!empty($user1->preferences->pagination)) ? $user1->preferences->pagination : 20;
            if (!isset($this->where)) $this->where = array('', array(), array());
            if (!isset($this->offset)) $this->offset = max(0, ( $this->page - 1)) * $this->num_on_page;
            if (!isset($this->limit)) $this->limit = array('offset' => $this->offset, 'num_on_page' => $this->num_on_page);

            foreach($this->table_fields as $key => $fname) $this->$fname = null;
            //Removed: $fields used to be constructor argument
            //if (!empty($fields)) foreach($fields as $key => $value) $this->$key = $value;

            if (in_array("created_by", $this->table_fields)) {
                $this->left_join .= " LEFT JOIN system_users vucb ON vucb.id=t.created_by ";
                $this->other_selects .= " ,vucb.fullname as created_by_name";
            }

            if (in_array("modified_by",$this->table_fields)) {
                $this->left_join .= " LEFT JOIN system_users vumb ON vumb.id=t.modified_by ";
                $this->other_selects .= " ,vumb.fullname as modified_by_name";
            }

            if (!isset($this->view_array)) {
                foreach($this->table_fields as $item) {
                    if($item!=$this->object_pk) {
                        $this->view_array[$item] = array("name"=>ucfirst($item),"column"=>$item);
                    }
                }
            }
        }

        if (!isset($this->null_check_exceptions)) $this->null_check_exceptions = array();
        $this->null_check_exceptions+= array("created_time","created_by","modified_time","modified_by");
    }
    /**
     * (PHP 4, PHP 5)<br/>
     * Returns the name of the class
     *
     * @return string the name of the class.
     */
    function name(){ return get_class($this); }

    function set_post($post_event=array()) {

        if(!empty($post_event) && is_array($post_event)) {

            foreach($post_event as $key => $value) $this->$key = $value;

        }
        return;
    }

    function fields_check_handler(){
        global $libhtml, $my_post, $crypt;

        $message = $this->fields_check();

        if (!empty($message)){

            if (!empty($my_post) && !empty($my_post['posted_actions'])){

                $serialized_actions = $crypt->str_decrypt($my_post['posted_actions']);

                $actions = unserialize($serialized_actions);

                if (!empty($actions)){

                    if ($actions[1]=='insert') {
                        $form_action = 'add';
                    } elseif ($actions[1]=='update') {
                        $form_action = 'edit';
                    } else {
                        $form_action = $actions[1];
                    }

                    //Pass the ID into the reposted data; it is not posted raw!
                    $data = my_post($this->object_name);
                    if (isset($this->{$this->object_pk})) {
                        $data[$this->object_pk] = $this->{$this->object_pk};
                    }

                    if (method_exists($this,$actions[1])){

                        //We need object to instantiate it
                        //Form function name and data
                        //And original action - otherwise after 1st popup it all goes wrong!
                        $libhtml->show_popup(array(
                                "object"=>get_class($this),
                                "function"=>'print_'.$form_action.'_form',
                                "data"=>$data,
                                "original_action"=>my_post('original_action'),
                        ));

                    }

                }

            }

            $_SESSION['feedback'].= g_feedback("error",$message);
            error_log($message);
            return false;

        } else {

            //Passed all tests
            return true;
        }


    }

    /*
     * Overload this function for complex field validation
    */
    function fields_check(){
        return null;
    }

    /**
     * Placeholder function
     * Replace this in any extending class if wanting to do thing like
     * insert and redirect to details page.  Place redirect logic based
     * on supplide function name in the ovveriding function.  This is
     * called from execute post after DB transaction logic otherwise
     * transaction will not be COMMITed due to redirection
     *
     * @param  [type] $function Name of function called from execute_post
     * @return nothing
     */
    function redirect_after($function) {}

    function insert() {
        global $db, $user1, $cfg, $libhtml, $my_files, $my_post;

        $success = false;

        if ($this->fields_check_handler()) {

            try {
                $fields = $values = array();
                $my_object_post = my_post($this->object_name);

                if (in_array("created_time", $this->table_fields)) $this->created_time = date("Y-m-d H:i:s");
                if (in_array("created_by", $this->table_fields)) $this->created_by = $user1->id;
                if (in_array("sort_order", $this->table_fields) && empty($this->sort_order)) $this->sort_order = $db->tcount($this->table, array('', array(), array()))+1;

                foreach ($this->table_fields as $key => $fname){
                    if ($fname == 'file_content' || $fname == 'file_image'){
                        if ($fname == 'file_content') {
                            if (isset($this->file) && strlen($this->file) > 6 && (substr($this->file, -6, 6) == '.crypt')) {
                                $this->file_content = file_get_contents($cfg["secure_dir"].$this->file);
                            } elseif (isset($this->filename) && strlen($this->filename) > 6 && (substr($this->filename, -6, 6) == '.crypt')) {
                                $this->file_content = file_get_contents($cfg["secure_dir"].$this->filename);
                            }
                        } else {
                            if (isset($this->file) && strlen($this->file) > 6 && (substr($this->file, -6, 6) == '.crypt')) {
                                if (is_file($cfg["secure_dir"].substr($this->file, 0, strlen($this->file)-6).".jpg.crypt")) {
                                    $this->file_image = file_get_contents($cfg["secure_dir"].substr($this->file, 0, strlen($this->file)-6).".jpg.crypt");
                                }
                            } elseif (isset($this->filename) && strlen($this->filename) > 6 && (substr($this->filename, -6, 6) == '.crypt')) {
                                if (is_file($cfg["secure_dir"].substr($this->filename, 0, strlen($this->filename)-6).".jpg.crypt")) {
                                    $this->file_image = file_get_contents($cfg["secure_dir"].substr($this->filename, 0, strlen($this->filename)-6).".jpg.crypt");
                                }
                            }
                        }
                    }

                    // added to not supply PK for insert statement;
                    if (!($fname == $this->object_pk) && strlen($this->$fname)!=0) {
                        $fields[$fname]=$this->$fname;
                    }
                    if ($this->allow_pk_insert === true && strlen($this->$fname)!=0 && $fname == $this->object_pk) {
                        $fields[$fname]=$this->$fname;
                    }
                }

                // dump_var($this); exit;

                $validator = new Validator($this);
                if (!$validator->validate()) {
                    foreach ($validator->errors as $error) {
                        $_SESSION['feedback'] .= g_feedback("error", $error);
                    }
                    throw new Exception("Validation Errors: ".implode("\n", $validator->errors));
                }

                if (array_key_exists('file_content', $fields) === true) {
                    if (array_key_exists('file', $fields) === true) {
                        unlink($cfg["secure_dir"].$fields['file']);
                        $image_f = $cfg["secure_dir"].substr($fields['file'], 0, strlen($fields['file'])-6).".jpg.crypt";
                        if (file_exists($image_f)) unlink($image_f);
                    } elseif (array_key_exists('filename', $fields) === true) {
                        unlink($cfg["secure_dir"].$fields['filename']);
                        $image_f = $cfg["secure_dir"].substr($fields['filename'], 0, strlen($fields['filename'])-6).".jpg.crypt";
                        if (file_exists($image_f)) unlink($image_f);
                    }
                }

                $this->{$this->object_pk} = $db->insert($this->table, $fields, $this->table_types);
                if ($this->allow_pk_insert === true) {
                    $this->{$this->object_pk} = $fields[$this->object_pk];
                }

                if ($db->rows > 0) {

                    // if the insert was successful, and there are global system tags attached to the object, insert them too
                    $this->insert_system_tags();

                    $this->select($this->{$this->object_pk});
                    $this->system_log->insert(array(
                        'time' => date("Y-m-d H:i:s"),
                        'user_id' => $user1->id,
                        'object' => $this->human_name,
                        'action' => "Insert",
                        'object_id' => $this->{$this->object_pk},
                        'comment' => $this->show(),
                    ));

                    $success = true;

                } else {

                    $feedback = "Unspecified database problem with inserting $this->human_name";
                    $_SESSION['feedback'] .= g_feedback("error", $feedback);
                    throw new Exception($feedback);

                }

            } catch (Exception $e) {

                $feedback = "Unspecified database problem with inserting $this->human_name";
                $_SESSION['feedback'] .= g_feedback("error", $feedback);

                error_log("Insert Exception: ".$e->getMessage());
                error_log($e->getTraceAsString());

                throw $e;
            }

        }

        return $success;
    }

    function select($id="", $feedback=true) {
        global $db;

        if(!empty($id) || !empty($this->{$this->object_pk})) {

            if(empty($id)) $id=$this->{$this->object_pk};

            $selection = $db->select(
                $this->field_selection . $this->other_selects,
                $this->table . " t",
                array("WHERE t." . $this->object_pk . "=?", array($this->object_pk => $id), array('integer')),
                array('joins' => $this->left_join, 'group_by' => $this->groupby)
            );

            if (count($selection) == 1) {

                array2object($selection, $this);
                $this->sql_where = array("WHERE t.".$this->object_name."_id = ?", array($this->object_name.'_id' => $this->{$this->object_pk}), array('integer'));
                //$this->where = array('', array(), array());

            } elseif (!empty($_SESSION)) {

                if (count($selection) == 0) {

                    $this->no_id_in_db=true;
                    if ($feedback) $_SESSION['feedback'] .=  g_feedback("error","Problem in selecting object " . $this->name() . ". No database entries with Primary Key equal to '$id' found");
                    return false;

                } else {

                    if ($feedback) $_SESSION['feedback'] .=  g_feedback("error","Problem in selecting object " . $this->name() . ". More than one database entry with Primary Key equal to '$id' found");

                    if ((isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev')) {
                        //Dev mode
                        error_log("Object:".$this->object_name);
                        error_log("PK: ".$this->object_pk." = ".$id);
                        error_log(count($selection)." items returned");
                        error_log(print_r(debug_backtrace(false),true));
                        trigger_error("Database problem.", E_USER_ERROR);
                    }
                    return false;
                }

            }

        } else {

            error_log("Empty object ID used for Object::selection(), object_name=".$this->object_name);
            error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
            return false;

        }
    }

    function update(array $additional = array()) {
        global $db, $user1, $cfg, $my_files, $my_post;

        $success = false;

        if ($this->fields_check_handler()) {

            try {
                $fields = array();

                if (in_array("modified_time", $this->table_fields)) {
                    $this->modified_time = date("Y-m-d H:i:s");
                    $fields['modified_time'] = $this->modified_time;
                }

                if (in_array("modified_by", $this->table_fields)) {
                    $this->modified_by = $user1->id;
                    $fields['modified_by'] = $this->modified_by;
                }

                if (!empty($my_post[$this->object_name])){
                    foreach ($my_post[$this->object_name] as $key => $fname) {
                        if (!in_array($fname, array("created_time","created_by"))) {
                            if (in_array($key,$this->table_fields)) $fields[$key]=$fname;
                        }
                    }
                }

                foreach ($additional as $key => $fname) {
                    if (!in_array($key, array("created_time","created_by"))) $fields[$key]=$fname;
                }

                $validator = new Validator($this);
                if (!$validator->validate()) {

                    foreach ($validator->errors as $error) $_SESSION['feedback'] .= g_feedback("error", $error);

                    throw new Exception("Validation Errors: ".implode("\n", $validator->errors));

                }

                if (!empty($additional)) {
                    foreach ($additional as $key => $value) {
                        $this->{$key} = $value;
                    }
                }

                $x = new $this->class_name;
                $x->select($this->{$this->object_pk});
                $od = object_diff($x,$this);

                $db->update(
                    $this->table,
                    $fields,
                    array(
                        "WHERE " . $this->object_pk . " = ?",
                        array($this->object_pk => $this->{$this->object_pk}),
                        array('integer')
                    ),
                    $this->table_types
                );

                if ($db->rows>0 || $db->get_sqlstate() == "00000") {

                    // if the update was successful, and there are global system tags attached to the object, update them too
                    $this->update_system_tags();

                    $this->system_log->insert(array(
                        'time' => date("Y-m-d H:i:s"),
                        'user_id' => $user1->id,
                        'object' => $this->human_name,
                        'action' => "Update",
                        'object_id' => $this->{$this->object_pk},
                        'comment' => $this->show() . $od,
                    ));

                    $success = true;

                } else {
                    $feedback = "Unspecified database problem with updating $this->human_name";
                    $_SESSION['feedback'] = g_feedback("error",$feedback);
                    throw new Exception($feedback);

                }

            } catch (Exception $e) {

                error_log("Update Exception: ".$e->getMessage());
                error_log($e->getTraceAsString());

                throw $e;
            }

        }

        return $success;

    }

    function delete() {
        global $db, $cfg, $user1, $libhtml;

        $feedback = false;

        $this->select($this->{$this->object_pk});

        // Check for any files; if found delete them;
        foreach ($this->table_fields as $key => $fname) {

            $filepath = $cfg['secure_dir'] . $this->$fname;

            if (is_file($filepath)) {
                if (!unlink($cfg['secure_dir'] . $this->$fname)) {
                    $_SESSION['feedback'] .= g_feedback("error", "There was a problem in deletion of the file.");
                } else {
                    $_SESSION['feedback'] .= g_feedback("info","Attached file(s) sucessfully deleted from the hard drive.");
                }
            }
        }


        try {

            $db->start_transaction();

            $db->delete(
                $this->table,
                array(
                    "WHERE " . $this->object_pk . "=?",
                    array($this->object_pk => $this->{$this->object_pk}),
                    array('integer')
                )
            );

            if ($db->rows>0) {

                // delete any tags
                $this->delete_system_tags();

                $this->system_log->insert(array(
                        'time' => date("Y-m-d H:i:s"),
                        'user_id' => $user1->id,
                        'object' => $this->human_name,
                        'action' => "Delete",
                        'object_id' => $this->{$this->object_pk},
                        'comment' => $this->show(),
                ));

                if (!in_array($this->object_name,array(
                        "wastebasket",
                        "user_session")
                )){

                    $waste_id = $this->system_wastebasket->insert(array(
                        'user_id' => $user1->id,
                        'deletion_date' => date("Y-m-d H:i:s"),
                        'object' => $this->object_name,
                        'object_key' => $this->{$this->object_pk},
                        'information'=>$this->show(),
                        'content' => urlencode($this->_json_object()),
                    ));


                    // add to a session to be displayed in the side panel
                    if (!isset($_SESSION["deleted_objects"])) $_SESSION["deleted_objects"] = array();

                    $_SESSION["deleted_objects"][$waste_id] = array(
                        "new" => true,
                        "object" => $this->human_name,
                        "object_id" => $waste_id, // used for unseting NEW flag
                        'deletion_date' => date("Y-m-d H:i:s"),
                        'restore_url' =>encrypt_url($cfg['root'] . "restore_wastebasket.php?wastebasket_id=".$waste_id),
                    );

                }
            }

            $db->complete_transaction();

            if ($db->rows > 0) {

                return true;

            } else {
                $_SESSION['feedback'] .= g_feedback("error","Unspecified database problem with deleting $this->human_name");
                return false;
            }


        } catch (Exception $e) {
            error_log(print_r($e->getMessage(), true));
            $_SESSION['feedback'] .= g_feedback("error",$e->getMessage());
            //return false;

            throw $e;
        }
    }

    function multidelete() {
        global $db, $user1;

        $ids = explode("-", $this->ids);

        if (!empty($ids)) {
            try {
                foreach ($ids as $id) {

                    $this->select($id);

                    $this->system_log->insert(array(
                            'time' => date("Y-m-d H:i:s"),
                            'user_id' => $user1->id,
                            'object' => $this->human_name,
                            'action' => "Delete",
                            'object_id' => $id,
                            'comment' => $this->show(),
                    ));

                    if ($this->object_name!="wastebasket"){

                        $this->system_wastebasket->insert(array(
                            'user_id' => $user1->id,
                            'deletion_date' => date("Y-m-d H:i:s"),
                            'object' => $this->object_name,
                            'object_key' => $id,
                            'information'=>$this->show(),
                            'content' => urlencode($this->_json_object()),
                        ));

                    }

                    $db->delete($this->table, array("WHERE id=?", array('id' => $id), array('integer')));
                }

                if ($db->rows>0) {
                    return true;
                } else {
                    return false;
                }

            } catch (Exception $e) {
                error_log(print_r($e->getMessage(), true));
                $_SESSION['feedback'] .= g_feedback("error",$e->getMessage());

                throw $e;
            }
        }
    }

    function copy($updated_fields = array()){
        global $db;

        try {
            $object = new $this->class_name;

            foreach ($this->table_fields as $key => $fname) if (isset($this->$fname)) $object->$fname=$this->$fname;

            foreach ($updated_fields as $key => $value) $object->$key=$value;

            $object->{$this->object_pk}='';

            foreach(array("created_time","created_by","modified_time","modified_by") as $stamp) if (isset($object->$stamp)) $object->$stamp='';

            $object->insert();

            $this->{$this->object_pk} = $object->{$this->object_pk};

        } catch (Exception $e) {
            error_log(print_r($e->getMessage(), true));
            $_SESSION['feedback'] .= g_feedback("error",$e->getMessage());

            throw $e;
        }
    }

    // get system tags
    function get_tags($related_sub_object = ""){
        global $cfg, $db;

        return $db->select("t.*", "system_tags t LEFT JOIN system_objects_tags o ON o.tag_id = t.id", array("WHERE o.object_id = ? AND o.related_object_name = ? AND o.related_sub_object = ?", array($this->id, $this->object_name, $related_sub_object), array("integer", "varchar", "varchar")));

    }

    // show system tags
    function show_tags($related_sub_object = ""){
        global $cfg, $db;

        $tags = $this->get_tags($related_sub_object);

        $html = '<div class="inputwrap">';
            foreach ($tags as $one_item){
                $html .= '<span class="one_tag norem" id="'.$one_item->id.'">'.$one_item->tag.'</span>';
            }
        $html .= '</div>';

        return $html;

    }

    // insert system tags
    function insert_system_tags(){
        global $cfg, $db, $my_post;

        if (!empty($my_post["system_tags"])){

            // remove any previous tags for this object
            if ($db->get_table_column_metadata("system_objects_tags", $this->database)
            && $db->get_table_column_metadata("system_tags", $this->database)){

                foreach($my_post["system_tags"] as $related_sub_object => $posted_tags) {
                    if ($related_sub_object == '0') $related_sub_object = '';

                    $unique_tags = array_unique(explode(",", $posted_tags));
                    foreach ($unique_tags as $tag_id) {

                        if (!empty($tag_id)) {

                            // double check if tag exist in the database, because numbers can also be inserted as tags
                            if (is_numeric($tag_id)) {
                                $tag_exist = $db->select("id", "system_tags", array("WHERE id = ? AND related_object_name = ? AND related_sub_object = ?", array($tag_id, $this->object_name, $related_sub_object), array("integer", "varchar", "varchar")));

                                // link the tag to the object
                                if (!empty($tag_exist)) {
                                    $new_link = $db->insert("system_objects_tags", array("tag_id"=>$tag_id, "object_id"=>$this->id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                } else {
                                    $tag_id = $db->insert("system_tags", array("tag"=>$tag_id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                    // link the tag to the object
                                    $new_link = $db->insert("system_objects_tags", array("tag_id"=>$tag_id, "object_id"=>$this->id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                }

                            // if it is not numeric, this means the tag is newly created, add it to the database
                            } else if (!is_numeric($tag_id)) {
                                $tag_id = $db->insert("system_tags", array("tag"=>$tag_id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                // link the tag to the object
                                $new_link = $db->insert("system_objects_tags", array("tag_id"=>$tag_id, "object_id"=>$this->id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                            }

                        }
                    }
                }

            } else {
                error_log("Table 'system_objects_tags' or 'system_tags' does not exist");

            }
        }
    }

    // update system tags
    function update_system_tags(){
        global $cfg, $db, $my_post;

        if (isset($my_post["system_tags"])){

            // remove any previous tags for this object
            if ($db->get_table_column_metadata("system_objects_tags", $this->database)
            && $db->get_table_column_metadata("system_tags", $this->database)){

                foreach($my_post["system_tags"] as $related_sub_object => $posted_tags) {
                    if ($related_sub_object == '0') $related_sub_object = '';

                    $db->delete("system_objects_tags", array("WHERE object_id = ? AND related_object_name = ? AND related_sub_object = ?", array($this->id, $this->object_name, $related_sub_object), array("integer", "varchar", "varchar")));

                    // and reinsert them
                    $unique_tags = array_unique(explode(",", $posted_tags));
                    foreach ($unique_tags as $tag_id) {

                        if (!empty($tag_id)) {

                            // double check if tag exist in the database, because numbers can also be inserted as tags
                            if (is_numeric($tag_id)) {
                                $tag_exist = $db->select("id", "system_tags", array("WHERE id = ? AND related_object_name = ? AND related_sub_object = ?", array($tag_id, $this->object_name, $related_sub_object), array("integer", "varchar", "varchar")));

                                // link the tag to the object
                                if (!empty($tag_exist)) {
                                    $new_link = $db->insert("system_objects_tags", array("tag_id"=>$tag_id, "object_id"=>$this->id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                } else {
                                    $tag_id = $db->insert("system_tags", array("tag"=>$tag_id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                    // link the tag to the object
                                    $new_link = $db->insert("system_objects_tags", array("tag_id"=>$tag_id, "object_id"=>$this->id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                }

                            // if it is not numeric, this means the tag is newly created, add it to the database
                            } else if (!is_numeric($tag_id)) {
                                $tag_id = $db->insert("system_tags", array("tag"=>$tag_id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                                // link the tag to the object
                                $new_link = $db->insert("system_objects_tags", array("tag_id"=>$tag_id, "object_id"=>$this->id, "related_object_name"=>$this->object_name, "related_sub_object"=>$related_sub_object));

                            }
                        }
                    }
                }

            } else {
                error_log("Table 'system_objects_tags' or 'system_tags' does not exist");

            }

        }
    }

    // delete system tags
    function delete_system_tags(){
        global $cfg, $db, $my_post;

        // remove any previous tags for this object
        // including all related sub object tags
        // no need to check if tags exist, but do check if table exist
        if ($db->get_table_column_metadata("system_objects_tags", $this->database)){
            $db->delete("system_objects_tags", array("WHERE object_id = ? AND related_object_name = ?", array($this->{$this->object_pk}, $this->object_name), array("integer", "varchar")));
        } else {
            error_log("Table 'system_objects_tags' does not exist");
        }

    }

    function check_foreign_key_usage(){
        global $db;

        $usage = array();
        $relationships = array();
        $sum = 0;

        // DB keys
        $fks = $db->get_real_foreign_key_usage($this->table);

        if (!empty($fks)) {
            foreach ($fks as $fk) {
                $result = $db->select("count(*) as count", $fk->table_name, array("WHERE $fk->column_name=?", array($this->{$fk->foreign_column_name}), array('integer')));
                if ($result[0]->count > 0) {
                    $usage[$fk->table_name] = $result[0]->count;
                    $sum+=$result[0]->count;
                }

                // add to table => column array, used when manually deleting child objects
                $relationships[$fk->table_name] = $fk->column_name;

            }
        }

        // Manual
        if (!empty($this->foreign_keys)) {

            foreach ($this->foreign_keys as $item) {

                $fk_table = $item[0];
                $key= $item[1];

                if ($fk_table=="*") {

                    $schema = $db->get_column_usage($key);
                    if (!empty($schema)) {
                        foreach($schema as $item) {

                            $result = $db->select("count(*) as count", $item->TABLE_NAME, array("WHERE $key=?", array($key => $this->{$this->object_pk}), array('integer')));
                            if ($result[0]->count > 0) {
                                $usage[$item->TABLE_NAME][$key] = $result[0]->count;
                                $sum+=$result[0]->count;
                            }

                        }
                    }

                } else {

                    $result = $db->select("count(*) as count", $fk_table, array("WHERE $key=?", array($key => $this->{$this->object_pk}), array('integer')));
                    if ($result[0]->count > 0) {
                        $usage[$fk_table][$key] = $result[0]->count;
                        $sum+=$result[0]->count;
                    }

                }
            }
        }

        return array(
            'usage'=>$usage,
            'tables'=>count($usage),
            'sum'=>$sum,
            'relationships'=>$relationships
        );
    }

    function print_action_button($action, $options = array()){
        global $libhtml, $cfg, $user1;

        return href_link(array_merge(array(
            "permission"=>$user1->{$libhtml->path.$action.'_'.$this->object_name.'.php'},
            "url"=>$cfg["root"] . $libhtml->path.$action.'_'.$this->object_name.'.php?'.$this->object_name.'_id='.$this->{$this->object_pk},
            "text"=>ucwords($action).' '.ucwords($this->human_name),
        ),$options));
    }

    function print_form(){
        global $libhtml, $my_get, $my_post;

        $html = $libhtml->form_start();

        $html .= open_table();

        $n = count($this->table_fields);

        for ($i = 1; $i < $n; $i++) {

            $field_name = $this->table_fields[$i];
            $field_type = $this->table_types[$field_name]->DATA_TYPE;
            $field_text = ucwords(strtolower(str_replace("_"," ",$field_name)));

            if (preg_match("/text/",$field_type)) {
                $html .= $libhtml->render_form_table_row_text($this->object_name . "[$field_name]", $this->$field_name, $field_text, $field_name, array('rte'=>true));
            } elseif (preg_match("/date/",$field_type)) {
                $html .= $libhtml->render_form_table_row_date($this->object_name . "[$field_name]", $this->$field_name, $field_text, $field_name);
            } else {
                //$html .= $libhtml->render_form_table_row($this, $field_name);
                $html .= $libhtml->render_form_table_row($this->object_name . "[$field_name]", $this->$field_name, $field_text, $field_name);
            }
        }

        $html .= close_table();

        // paused forms, set post from session only if action is triggering the forms, not on self submit
        if (!empty($my_get["continue"]) && empty($my_post["self_submit"])) $this->set_post($_SESSION["paused_forms"][$my_get["continue"]]["form_data"]);

        // executed only on default print_form, usually it is overriden in object
        $this->setChainedPost();

        return $html;
    }

    function form_action()
    {
        global $libhtml;

        if (!empty($this->id)){

            return $libhtml->render_actions(
                array($libhtml->render_button("update_".$this->object_name, "Update", array($this->object_name,'update',$this->class_name,$this->id))),
                array("pause"=>false,"show_delete"=>true,'show_refresh'=>true,)
            );

        } else {

            return $libhtml->render_actions(
                array($libhtml->render_button("insert_".$this->object_name, "Add", array($this->object_name,'insert',$this->class_name,$this->id))),
                array("pause"=>false,"show_delete"=>true,'show_refresh'=>true)
            );

        }
    }

    function print_edit_form($options = array()){
        global $libhtml, $my_get, $my_post;

        $defaults = array('pause'=>false, 'show_delete'=>true); // for now, used only for pause
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        // paused forms, set post from session only if action is triggering the forms, not on self submit
        if (!empty($my_get["continue"]) && empty($my_post["self_submit"])) $this->set_post($_SESSION["paused_forms"][$my_get["continue"]]["form_data"]);

        $this->setChainedPost();

        $html = $this->print_form($options);

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("update_" . $this->object_name, "Update", array(
                    $this->object_name,
                    'update',
                    $this->class_name,
                    $this->{$this->object_pk}
                ))
            ),
            array(
                "pause"=>$defaults["pause"],
                "show_delete"=>$defaults["show_delete"],
                "object_name"=>$this->object_name,
                "object_id"=>$this->{$this->object_pk},
            )
        );

        //$html .= $libhtml->render_form_table_row_hidden($this->object_name . "[$this->object_pk]", $this->{$this->object_pk});
        $html .= $libhtml->form_end();

        return $html;
    }

    function print_copy_form($options = array()){
        global $libhtml, $my_get, $my_post;

        $defaults = array('pause'=>false); // for now, used only for pause
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        // paused forms, set post from session only if action is triggering the forms, not on self submit
        if (!empty($my_get["continue"]) && empty($my_post["self_submit"])) $this->set_post($_SESSION["paused_forms"][$my_get["continue"]]["form_data"]);

        $this->setChainedPost();
        $html = $this->print_form();

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("copy_" . $this->object_name, "Copy", array(
                    $this->object_name,
                    'copy',
                    $this->class_name,
                    $this->{$this->object_pk}
                ))
            ),
            array(
                "pause"=>$defaults["pause"],
            )
        );

        //$html .= $libhtml->render_form_table_row_hidden($this->object_name . "[$this->object_pk]", $this->{$this->object_pk});
        $html .= $libhtml->form_end();
        return $html;
    }

    function print_add_form($options = array()){
        global $libhtml, $my_get, $my_post;

        $defaults = array('pause'=>false); // for now, used only for pause
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        // paused forms, set post from session only if action is triggering the forms, not on self submit
        if (!empty($my_get["continue"]) && empty($my_post["self_submit"])) $this->set_post($_SESSION["paused_forms"][$my_get["continue"]]["form_data"]);

        $this->setChainedPost();
        $html = $this->print_form($options);

        $html .= $libhtml->render_actions(
            array(
                $libhtml->render_button("add_" . $this->object_name, "Add", array(
                    $this->object_name,
                    'insert',
                    $this->class_name,
                    (!empty($this->object_pk) ? $this->{$this->object_pk} : '')
                ))
            ),
            array(
                "pause"=>$defaults["pause"],
                "show_delete"=>false,
            )
        );

        $html .= $libhtml->form_end();
        return $html;
    }


    function print_delete_form(){
        global $libhtml, $db, $crypt, $user1, $cfg, $my_post, $my_get;

        $html = $libhtml->form_start();
        $usage = $this->check_foreign_key_usage();

        // a child object delete was triggered
        if (!empty($my_get["delete_from_cross_linked_table"])){
            $table_name = $my_get["delete_from_cross_linked_table"];
            $column_name = $usage["relationships"][$table_name];

            // try to delete from the database
            try {
                $delete_feedback = $db->delete($table_name, array("WHERE $column_name = ?", array($this->id), array("integer")));

                if (!empty($delete_feedback)) $html .= '<div class="success" style="white-space: normal;">A cross-linked object has been deleted.</div>';
                else $html .= '<div class="hint" style="white-space: normal;">A cross-linked object could not be deleted.</div>';

            } catch (Exception $e){
                $html .= '<div class="hint" style="white-space: normal;">A cross-linked object could not be deleted.</div>';

            }

            // check usage again
            $usage = $this->check_foreign_key_usage();
        }

        if ($usage['sum'] > 0) {

            $html .= '<div class="error" style="white-space: normal;">Sorry, this entry cannot be deleted as it is currently in use.</div>';
            $html .= $this->show();

            if ($user1->{"system_log.php"}) {

                $html .= section(array("title"=>'There are '.$usage['sum'].' instance(s) in '.$usage['tables'].' table(s) of cross-linking this object', "collapsible"=>true, "state"=>(empty($delete_feedback) ? "collapsed" : "")));
                $html .= '<div '. (empty($delete_feedback) ? 'style="display:none;"' : '') .'>';
                    $html .= open_table();

                    foreach($usage['usage'] as $object => $num){

                        // check if the $num is actually an array()
                        $num = (is_array($num) ? array_sum($num) : $num);

                        // check if user is allowed to delete the cross linked tables
                        $delete_link = (!in_array($object, $this->allowed_delete_from_tables)) ? '' : href_link(array(
                            "permission"=>true,
                            "url"=>$cfg["root"] . inject_crypt_vars(str_replace('/systems/', '', $my_post["page"]), array("delete_from_cross_linked_table"=>$object)),
                            "encrypt"=>false,
                            "text"=>" Delete",
                            "class"=>"red_btn",
                            "float"=>"right",
                            "clear"=>false,
                        ));

                        $html .= $libhtml->render_table_row(ucfirst(str_replace("_", " ", $object)), $num . $delete_link);

                    }
                    $html .= close_table();
                $html .= '</div>';

            }

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_cancel_button()
                ),
                array(
                    'pause'=>false,
                    'show_cancel'=>false,
            ));

        } else {

            $html .= '<div class="error">Are you sure you want to delete this object?</div>'
                //.$libhtml->render_form_table_row_hidden($this->object_name . "[$this->object_pk]", $this->{$this->object_pk})
                .$this->show();

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_button("delete_" . $this->object_name, "Delete", array(
                        $this->object_name,
                        'delete',
                        $this->class_name,
                        $this->{$this->object_pk}
                    ))
                ),
                array(
                    "pause"=>false,
                    "show_delete"=>false,
                )
            );
        }

        $html .= $libhtml->form_end();
        return $html;
    }

    function print_multidelete_form($options = array()){
        global $libhtml, $user1;

        $defaults = array('pause'=>false); // for now, used only for pause
        if (!empty($options)) foreach($options as $k=>$v) $defaults[$k] = $v;

        if (!empty($this->ids)) {
            $html  = $libhtml->form_start();
            $ids = explode("," , $this->ids);
            $deletable_ids = array();

            $html .= '<div class="error">Are you sure you want to delete all these objects?</div>';

            foreach($ids as $id) {
                $object = get_clean_object($id, get_class($this));

                // check usage for that object
                $usage = $object->check_foreign_key_usage();

                if ($usage['sum'] > 0) {

                    $html .= section(array("title"=>'<span style="color:#41ADE0;">This entry cannot be deleted as it is currently in use</span>', "collapsible"=>true));
                    $html .= $object->show();

                } else {

                    $html .= section(array("title"=>'<span style="color:#DC0000;">This entry will be deleted</span>', "collapsible"=>true));
                    $deletable_ids[] = $object->id;
                    $html .= $object->show();

                }
            }

            $html .= $libhtml->render_form_table_row_hidden($this->object_name . "[ids]", implode("-", $deletable_ids));

            $html .= $libhtml->render_actions(
                array(
                    $libhtml->render_button("multidelete_" . $this->object_name, "Delete All Selected", array(
                        $this->object_name,
                        'multidelete',
                        $this->class_name,
                        $this->{$this->object_pk}
                    ))
                ),
                array(
                    "pause"=>$defaults["pause"],
                    "show_delete"=>false,
                )
            );

            $html .= $libhtml->form_end();


        } else {
            $html = '<div class="hint">No items have been selected</div>';
            $html .= $libhtml->render_actions(array(),
                array(
                    "show_cancel"=>true,
                    "pause"=>false,
                )
            );

        }

        return $html;
    }

    function show(){
        global $libhtml;

        $className = get_class($this);
        $reflection = new ReflectionClass($className);

        //Is there a native print_details class; if yes use it
        if ($reflection->getMethod('print_details')->class == $className){

            $html = $this->print_details();

        //If no use view_array / list table layout
        } else {

            $clone = clone $this;
            $this->_set_table_list_row_items($clone);

            $html = open_table();

            foreach($this->view_array as $key => $item) {
                $value = strip_tags($clone->$key, '<br><p>');
                if ($value != '') $html .= $libhtml->render_table_row($item['name'], $value);
            }

            $html .= $this->render_stamp_rows();
            $html .= close_table();


        }

        return $html;
    }

    function print_details(){return $this->show();}

    // used for chained forms
    function setChainedPost(){
        global $my_post;

        // set chained jbox data
        if (!empty($my_post["jbox_id"])) {
            $jbox_id = $my_post["jbox_id"];
            $session_data = $_SESSION["popups"][$jbox_id];

            // going back button // forward data is always removed - even if not saved...
            if (!empty($my_post["page"]) && !empty($my_post["type"]) && $my_post["type"] == "back"){
                if (!empty($session_data[$my_post["page"]]["vars"])){
                    $this->set_post($session_data[$my_post["page"]]["vars"]);
                    if (!empty($session_data[$my_post["prev_page"]])) unset($session_data[$my_post["prev_page"]]); // unset forward page
                }

            // submitting form
            } else if (!empty($my_post["through_page"]) && !empty($my_post["self_submit"])){
                if (!empty($session_data[$my_post["through_page"]]["vars"])){
                    $this->set_post($session_data[$my_post["through_page"]]["vars"]);
                    unset($session_data[$my_post["prev_page"]]); // unset submitted page in this case
                }
            }
        }
    }

    // used when object's selection is build from the OUTSIDE of _list function, filter can't be built from the outside
    function make_db_defaults($options = array()){
        global $db;

        if (
                empty($this->defaults_are_set)
                && (
                        !isset($options['external'])
                        || (
                                isset($options['external'])
                                && !$options['external']
                                )
                        )
                ) {

            if (!empty($options["where"])) {

                if (count($options['where']) == 3) {

                    //If where is not empty, we are reseting the old one - if it existed and forcing a new one
                    /*
                    * Added not empty check here as if where is specified in class declaration
                    * it would get overridden here when called from _list as the default where
                    * in _list is array("", array(), array()) which is not empty, it's a 3 element
                    * array so the previous !empty check evaluates to true and where gets overridden
                    * on this line.
                    */
                    if (!empty($options["where"][0])) $this->where = $options["where"];

                } else  {

                    //error_log(print_r($options['where'],true));
                    throw new InvalidArgumentException("incorrect number of where option parameters");

                }

            }

            if (!empty($options["orderby"]) && !empty($options["dir"])) {
                $this->orderbystring = "ORDER BY ".$options["orderby"]." ".$options["dir"];
            } else if (!empty($options["orderby"])) {
                $this->orderbystring = "ORDER BY ".$options["orderby"]." ".$this->dir;
            } else if (!empty($this->orderby) && !empty($this->dir)) {
                $this->orderbystring = "ORDER BY ".$this->orderby." ".$this->dir;
            } else {
                $this->orderbystring = "ORDER BY t.".$this->object_pk ." ".$this->dir;
            }

            if (!empty($options["groupby"])) $this->groupby = $options["groupby"];

            if (!empty($options["page"])) $this->page = $options["page"];

            if (!empty($options["num_on_page"])) $this->num_on_page = $options["num_on_page"];

            if (!empty($options["offset"])) $this->offset = $options["offset"];

            if (!empty($options["limit"])) $this->limit = $options["limit"];

            $this->defaults_are_set = true; // set the flag, prevents these from being generated twice
        }

    }

    function _count($options = array()) {
        global $db;

        $this->make_db_defaults($options);

        return $db->tcount($this->table . " t", $this->where, array('joins' => $this->left_join));
    }

    function _simple_count($options = array()) {
        global $db;

        $this->make_db_defaults($options);

        return $db->tcount($this->table . " t", $this->where);
    }

    function _count_where($where = array('',array(),array())) {
        global $db;

        return $db->tcount($this->table . " t", $where);
    }

    // build $where depending on filtered values, can only come from inside of the _list function
    function filter_columns(){

        foreach($this->filter as $column => $value){

            if (!is_array($value) && (date('Y-m-d', strtotime($value)) == $value)){ // date picker - not in array cause it can be only one at the time

                (empty($this->where[0])) ? $this->where[0] = "WHERE " : $this->where[0] .= " AND ";
                $this->where[0] .= "t.".$column." = ?";
                $this->where[1][] = $value;
                $this->where[2][] = $this->table_types[$column]->DATA_TYPE;

                //$this->where .= " AND t." . $column . " = '".$value."'";

            } else if (is_array($value) && ( in_array("cah", $value) || in_array("cip", $value) || in_array("cqz", $value))) { // varchar filter, predefined array of values
                $set = array();
                if (in_array("cah", $value)) $set = array_merge($set, array("A", "B", "C", "D", "E", "F", "G", "H"));
                if (in_array("cip", $value)) $set = array_merge($set, array("I", "J", "K", "L", "M", "N", "O", "P"));
                if (in_array("cqz", $value)) $set = array_merge($set, array("Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"));

                //dump_var($this->where);die;

                (empty($this->where[0])) ? $this->where[0] .= "WHERE (" : $this->where[0] .= " AND (";

                $tmp_array = array();
                foreach ($set as $letter) {

                    $tmp_array[] = "t.".$column." LIKE ?";
                    //$this->where[0] .= " OR t.".$column." LIKE ?";
                    $this->where[1][] = $letter."%";
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;


                    //$tmp_array[] = " t." . $column . " LIKE '".$letter."%' ";
                }

                $this->where[0] .= implode(" OR ",$tmp_array).")";

                //dump_var($this->where);die;

                //$this->where .= " AND (" . implode(" OR ", $tmp_array) . ")";

            } else if (is_array($value) && ( in_array("cah", $value) || in_array("cip", $value) || in_array("cqz", $value))) { // it is a varchar filter, only possible when column is in the same table
                $set = array();
                if (in_array("cah", $value)) $set = array_merge($set, array("A", "B", "C", "D", "E", "F", "G", "H"));
                if (in_array("cip", $value)) $set = array_merge($set, array("I", "J", "K", "L", "M", "N", "O", "P"));
                if (in_array("cqz", $value)) $set = array_merge($set, array("Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"));

                (empty($this->where[0])) ? $this->where[0] .= "WHERE (" : $this->where[0] .= " AND (";

                $tmp_array = array();
                foreach ($set as $letter) {

                    $tmp_array[] = "t.".$column." LIKE ?";
                    $this->where[1][] = $letter."%";
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;

                }

                $this->where[0] .= implode(" OR ",$tmp_array).")";

            } else if (is_array($value) && ( in_array("cdw", $value) || in_array("cdm", $value) || in_array("cd3", $value))) { // date switch, week / month / 3 months
                if (in_array("cdw", $value)) {

                    (empty($this->where[0])) ? $this->where[0] = "WHERE " : $this->where[0] .= " AND ";
                    $this->where[0] .= " t.".$column." BETWEEN ? AND ?";

                    $this->where[1][] = date('Y-m-d', mktime(0,0,0, date("m"), date("d")-7, date("Y")));
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;

                    $this->where[1][] = date("Y-m-d");
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;
                }
                if (in_array("cdm", $value)) {

                    (empty($this->where[0])) ? $this->where[0] = "WHERE " : $this->where[0] .= " AND ";
                    $this->where[0] .= " t.".$column." BETWEEN ? AND ?";

                    $this->where[1][] = date('Y-m-d', mktime(0,0,0, date("m")-1, date("d"), date("Y")));
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;

                    $this->where[1][] = date("Y-m-d");
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;
                }
                if (in_array("cd3", $value)) {

                    (empty($this->where[0])) ? $this->where[0] = "WHERE " : $this->where[0] .= " AND ";
                    $this->where[0] .= " t.".$column." BETWEEN ? AND ?";

                    $this->where[1][] = date('Y-m-d', mktime(0,0,0, date("m")-3, date("d"), date("Y")));
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;

                    $this->where[1][] = date("Y-m-d");
                    $this->where[2][] = $this->table_types[$column]->DATA_TYPE;
                }

            } else if (is_array($value)) { // coming from a different table

                (empty($this->where[0])) ? $this->where[0] = "WHERE " : $this->where[0] .= " AND ";
                $tmp_array = array();
                foreach($value as $v) {
                    if ($v != "blank") {
                        $tmp_array[] = $column ." = ? ";
                        $this->where[1][] = $v;
                        $this->where[2][] = "varchar";
                    } else {
                        $tmp_array[] = $column ." IS NULL ";
                    }
                }
                if (count($tmp_array) > 1) {
                    $this->where[0] .= " ( ". implode(" OR ", $tmp_array) . ")"; // we have an array, its only possible with external joins
                } else {
                    $this->where[0] .= $tmp_array[0];
                }
            }
        }
    }

    function _get($options = array()){
        global $db, $libhtml;

        $this->make_db_defaults($options);

        return $db->select(
                $this->field_selection . $this->other_selects,
                $this->table . " t",
                $this->where,
                array(
                        'joins' => $this->left_join,
                        'order_by' => $this->orderbystring,
                        'group_by' => $this->groupby,
                        'limit' => $this->limit
                )
        );
    }

    function _basic_get($options = array()){
        global $db, $libhtml;

        if (!isset($this->basic_selection) || empty($this->basic_selection)) $this->basic_selection = "t.id, t.name";

        $this->make_db_defaults($options);

        return $db->select(
                $this->basic_selection,
                $this->table . " t",
                $this->where,
                array(
                        'joins' => $this->left_join,
                        'order_by' => $this->orderbystring,
                        'group_by' => $this->groupby,
                        'limit' => $this->limit
                )
        );
    }

    function _get_value($options = array()){
        global $db, $libhtml;

        $this->make_db_defaults($options);

        $result = $db->select(
                $this->field_selection . $this->other_selects,
                $this->table . " t",
                $this->where,
                array(
                        'joins' => $this->left_join,
                        'order_by' => $this->orderbystring,
                        'group_by' => $this->groupby,
                        'limit' => $this->limit
                )
        );

        return $result[0];
    }

    function _simple_get($options = array()){
        global $db, $libhtml;

        $this->make_db_defaults($options);

        return $db->select(
                $this->field_selection,
                $this->table . " t",
                $this->where,
                array(
                        'joins' => $this->left_join,
                        'order_by' => $this->orderbystring,
                        'group_by' => $this->groupby,
                        'limit' => $this->limit
                )
        );
    }

    function _list($options = array()){
        global $db, $libhtml, $user1, $my_get, $my_request;

        $defaults = array(
            'where'=>array("",array(),array()),
            'column_filter'=>array(),
            'header_sorting'=>true,
            'ajax_sort'=>false,
            'info'=>false,
            'app_path'=>get_path(),
            'edit'=>true,
            'delete'=>true,
            'copy'=>false,
            'multiselect'=>false,
            'multiedit'=>true,
            'multidelete'=>true,
            'table_wrapper'=>true,
            'pagination'=>true,
            'bottom_pagination'=>false,
            'multi_toggle'=>false,
            'to_top'=>false,
            'width'=>"100%",
            'table_width'=>"100%",
            'max_no_page_items'=>500,
            'view'=>false, // allows users to specify their own columns visibility
            'quick_search'=>true,
            'fix_toggler'=>false, // if set, text toggler's width will be calculated and attached to each div, when show_all_exp (expand all divs) is pressed
            'print'=>true,
            'xml_export'=>false,
            'csv_export'=>false,
            'pdf_export'=>false,
            'csv_import'=>false,
            'view_reset'=>array(),
            'no_items_message'=>true,
            'get_type'=>"",
            'js'=>true,
            'filter'=>true,
            'email_alert'=>false,
            'colourise'=>false, // any false (0) value will color the row red
            'ajax_list'=>true, // pagination buttons are ajaxed - only list is refreshed
            'dynamic_append'=>true, // ajax next page to the bottom
            'rc_enabled'=>true, // right click enabled
            'no_hover'=>false, // disabled the hover style per row (yellow rows)
            'external'=>false //If using Object->_list() with a query selection not associated with a standard class
        );

        // Prepare defaults
        foreach($options as $key=>$value) $defaults[$key] = $value;

        // generate unique table name here, to be able to compare my_get options for this table
        $defaults['table_name']=(!empty($this->table)) ? str_replace(".", "_", $this->table) : 'table';
        $table_name = $libhtml->name_table($defaults["table_name"], $defaults["where"]);
        $view_table_name = "table_".$defaults["table_name"]."_".$table_name;

        // override db defaults with table specific $my_get
        if (!empty($my_get[$view_table_name]["dir"])) {
            $this->dir = $defaults["dir"] = $my_get[$view_table_name]["dir"];

        }

        if (!empty($my_get[$view_table_name]["orderby"])) {

            $defaults["orderby"] = $my_get[$view_table_name]["orderby"];
            $this->orderbystring = " ORDER BY " . $defaults["orderby"] . " " . $defaults["dir"];

        } else {

            $defaults["orderby"] = '';

        }

        if (!empty($my_get[$view_table_name]["page"])) {
            $this->page = $defaults["page"] = $my_get[$view_table_name]["page"];
            $this->offset = max(0, ( $this->page - 1)) * $this->num_on_page;
            $this->limit = array('offset' => $this->offset, 'num_on_page' => $this->num_on_page);

        }

        //make_my_request(); // because my_request() function can't take multidimensional array as input element, global $my_request array is created

        $this->defaults_are_set = false;
        $this->make_db_defaults($defaults);

        //Add column filters to $defaults['where']
        if (!empty($my_request[$view_table_name]["filter"])){

            foreach($my_request[$view_table_name]["filter"] as $key => $value){ // group all filtering options by column name
                list($column, $col_value) = explode("--", $key); // break apart the column--value pair

                if ($value == 1){

                    if (!empty($defaults["column_filter"][$column]) && is_array($defaults["column_filter"][$column])) {

                        $defaults["column_filter"][$column][] = $col_value; // add value to array (more at the time)

                    } else if (isset($defaults["column_filter"][$column])) {

                        $defaults["column_filter"][$column] = $col_value; // add value to STRING (one at the time)

                    } else {

                        $defaults["column_filter"][$column] = array($col_value); // create value

                    }

                } else if (date('Y-m-d', strtotime($value)) == $value) { // date filters

                    $defaults["column_filter"][$column] = $value;

                }
            }
            $this->filter = $defaults["column_filter"];
            $this->filter_columns();

        }

        // pagination & show/hidden columns options
        $view = new View;
        $view->view_table = $view_table_name;

        if (isset($my_get[$view_table_name]["view_reload"])) {

            $view->remove_view();

        } else if (isset($my_get[$view_table_name]["show_all_cols"])) {

            $view->remove_column_view();

        } else if (isset($my_get[$view_table_name]["no_pagination"])) {

            $view->toggle_pagination($my_get[$view_table_name]["no_pagination"]);

        }

        // Miscellaneous
        if (!isset($options['multiedit'])) $defaults['multiedit'] = $defaults['edit'];
        if (!isset($options['multidelete'])) $defaults['multidelete'] = $defaults['delete'];

        // get view from database
        $defaults["view_options"] = $this->view_options = $view->get_view();

        // Get the totals - need them for pagination
        $defaults['total'] = (empty($defaults['selection'])) ? $this->{$defaults['get_type'] . "_count"}() : count($defaults['selection']);
        $defaults['row_count'] = 0;
        $defaults['num_of_pages'] = $this->num_of_pages = 1;

        // Adjust limits for SQL query - either pagination is on
        if ($defaults['pagination']){

            $defaults['num_of_pages'] = $this->num_of_pages = ceil($defaults['total'] / $this->num_on_page);

            // If it is on, but user chose differently
            if (!empty($defaults["view_options"]["no_pagination"]) && $defaults["view_options"]["no_pagination"] == 1){
                $defaults['limit'] = $this->limit = array('offset' => 0, 'num_on_page' => $defaults['max_no_page_items']);
                if ($defaults['total'] > $defaults['max_no_page_items']) {
                    $_SESSION['feedback'].= g_feedback("info","Table truncated at ".$defaults['max_no_page_items']." out of total of ".$defaults['total']." items");
                }
            } else {
                $defaults['row_count'] = (is_array($this->limit)) ? $this->limit['offset'] : substr($this->limit, 6) * 1;
                if ($this->page > $this->num_of_pages){
                    $defaults['page'] = $this->page = $this->num_of_pages;
                    $defaults['limit'] = $this->limit['offset'] = max(0, ($this->page - 1)) * $this->num_on_page;
                }
            }

        // Or pagination is off
        } else {
            $defaults['limit'] = $this->limit = array('offset' => 0, 'num_on_page' => $defaults['max_no_page_items']);
            if ($defaults['total'] > $defaults['max_no_page_items']){
                $_SESSION['feedback'].= g_feedback("info","Table truncated at ".$defaults['max_no_page_items']." out of total of ".$defaults['total']." items");
            }
        }

        //****************************************************************************************************
        // Get item selection
        if (empty($defaults['selection'])) $defaults['selection'] = $this->{$defaults['get_type'] . "_get"}();
        //****************************************************************************************************

        //Work through the items
        foreach($defaults['selection'] as $selection_item) $this->_set_table_list_row_items($selection_item);

        //dump_var($defaults["view_options"]);die;
        //Save list total
        $this->list_total = (empty($defaults['selection'])) ? $this->{$defaults['get_type'] . "_count"}() : count($defaults['selection']);

        // Prepare object view
        // enable od disable any columns that were disabled or enabled by default
        if (!empty($defaults['view_reset'])) {
            foreach($defaults['view_reset'] as $key => $value) {
                if ($value) $this->view_array[$key]["display"] = true;
                else $this->view_array[$key]["display"] = false;
            }
        }

        $position = 0;

        foreach($this->view_array as $key => $item) {

            if (!isset($item["display"]) || (isset($item["display"]) && $item["display"] != false)) {

                if (!empty($defaults["view_options"]["columns"])) {

                    $this->view_array[$key]["position"] = (isset($defaults["view_options"]["columns"][$key]["position"])) ? $defaults["view_options"]["columns"][$key]["position"] : $position;

                    // override the default width if its set
                    if (isset($defaults["view_options"]["columns"][$key]["width"])) $this->view_array[$key]["width"] = $defaults["view_options"]["columns"][$key]["width"];

                    // override the default no_display if the key is in the database
                    if (isset($defaults["view_options"]["columns"][$key]["display"]) && $defaults["view_options"]["columns"][$key]["display"] == false) $this->view_array[$key]["display"] = false;

                } else {

                    $this->view_array[$key]["position"] = $position;

                    // override the default display if the column in hidden by default
                    if (isset($item["view"]) && $item["view"] == false) $this->view_array[$key]["display"] = false;

                }

                $position++;

                // get the data type from the database
                //do not know if its used, should be in DB specific class if it is
                if (isset($this->table_types[$key])) $this->view_array[$key]["data_type"] = $this->table_types[$key]->DATA_TYPE;

            } else {
                unset($this->view_array[$key]);

            }

        }

        sortBySubkey($this->view_array, 'position', SORT_ASC);

        $defaults['all_columns'] = $this->view_array;

        // View - continued: add extra columns
        $this->view_array = array("row_number" => array("show_name"=>false,"width"=>"30px","class"=>"table_nudge")) + $this->view_array;

        if ($defaults['ajax_sort']) $this->view_array += array("sort_order"=>array("show_name"=>false,"width"=>"20px","display"=>($user1->{$defaults["app_path"] . "edit_$this->object_name.php"}),"class"=>"no_export"));
        if ($defaults['info']) $this->view_array += array("info"=>array("name"=>"Info", "show_name"=>false,"width"=>"18px"));
        if ($defaults['copy']) $this->view_array += array("copy"=>array("show_name"=>false,"width"=>"18px","display"=>($user1->{$defaults["app_path"] . "copy_$this->object_name.php"}), "class"=>"no_export"));
        if ($defaults['edit']) $this->view_array += array("edit"=>array("show_name"=>false,"width"=>"18px","display"=>($user1->{$defaults["app_path"] . "edit_$this->object_name.php"}),"class"=>"no_export"));
        if ($defaults['delete']) $this->view_array += array("delete"=>array("show_name"=>false,"width"=>"18px","display"=>($user1->{$defaults["app_path"] . "delete_$this->object_name.php"}),"class"=>"no_export"));
        if ($defaults['multiselect'] && $defaults['total'] >= 1) $this->view_array += array("multiselect"=>array("show_name"=>false,"width"=>"18px","display"=>true,"class"=>"no_export"));

        $defaults['view_array']= $this->view_array;
        $defaults['class_name'] = $this->name();

        // copy some object attributes to libhtml function
        $attributes = array(
                'table',
                'table_fields',
                'object_name',
                'object_pk',
                'page',
                'num_on_page',
                'offset',
                'limit',
                'dir',
        );

        foreach($attributes as $attribute){
            $defaults[$attribute] = (isset($this->$attribute)) ? $this->$attribute : null;
        }

        //dump_var($this);die;

        return $libhtml->list_object($defaults);
    }

    function _set_table_list_row_items($item){return;}

    function _move($move="", $where="") {
        if ($move=="up"){
            $this->sort_order -= 1.5;
        } elseif ($move=="down"){
            $this->sort_order += 1.5;
        } elseif ($move=="top"){
            $this->sort_order = -999999;
        } elseif ($move=="bottom"){
            $this->sort_order = 999999;
        }
        $this->update();
        $this->_order($where);
    }

    function _order($where_arg = array('',array(),array())){
        global $user1, $cfg, $db;

        $selection1 = $db->select(
                "t.$this->object_pk, t.`sort_order`",
                $this->table . " t",
                array(
                        "WHERE t.`sort_order` <> 0 ".$where_arg[0],
                        $where_arg[1],
                        $where_arg[2]
                ),
                array('order_by'=>"ORDER BY t.`sort_order` ASC")
        );

        $selection2 = $db->select(
                "t.$this->object_pk, t.`sort_order`",
                $this->table . " t",
                array(
                        "WHERE t.`sort_order` = 0 ".$where_arg[0],
                        $where_arg[1],
                        $where_arg[2]
                ),
                array('order_by'=>"ORDER BY t.`sort_order` ASC")
        );

        $selection = array_merge($selection1, $selection2);

        $i = 1;
        foreach($selection as $item){
            $db->update(
                    $this->table,
                    array('sort_order'=>$i),
                    array(
                            "WHERE " . $this->object_pk ." =?",
                            array($this->object_pk=>$item->{$this->object_pk}),
                            array('integer')
                    )
            );
            $i++;
        }
    }

    function _json_object() {
        global $db;

        $object = new StdClass;

        if (empty($this->{$this->object_pk})) $this->select($this->{$this->object_pk});

        foreach($this as $key => $item) $object->$key=$this->$key;

        $unsets=array("table_types","view_array","null_check_exceptions","left_join","other_selects","sort","dir","where","human_name");

        foreach($unsets as $item) unset($object->$item);

        $object->class_name = get_class($this);

        $object->class_app = get_path();

        return json_encode($object);
    }

    function dump_to_array($options = array()){

        $x = new StdClass;

        $name = $this->name();

        $pk = (isset($this->object_pk)) ? $this->object_pk : "";

        $pk_val = (isset($this->{$this->object_pk})) ? $this->{$this->object_pk} : "";

        $defaults = array(
                'unset_table_types'=>true,
                'unset_view_array'=>true,
                'unset_null_check_exceptions'=>true,
                'unset_object_variables'=>true,
                'unset_table_fields'=>true,
                'unset_array'=>array(),
                'toggle_all'=>false,
                'title'=>"Class:". $name . "; Primary Key:" . $pk . "; PK Value:" . $pk_val,
                'html_linebreaks'=>false,
        );

        foreach($options as $key=>$value) $defaults[$key] = $value;

        if ($defaults['html_linebreaks'] == true) {
            foreach($this as $key=>$value) $x->$key=str_replace("\n","\n<br />",$value);//clone, so as not to mess up $this!
        } else {
            foreach($this as $key=>$value) $x->$key=$value;//clone, so as not to mess up $this!
        }

        if ($defaults['unset_table_types']) unset($x->table_types);

        if ($defaults['unset_view_array']) unset($x->view_array);

        if ($defaults['unset_null_check_exceptions']) unset($x->null_check_exceptions);

        if ($defaults['unset_table_fields']) unset($x->table_fields);

        if ($defaults['unset_object_variables']){
            $object_variables = array("table","object_name","left_join","other_selects","sort","dir","object_pk","where","human_name");
            foreach($object_variables as $var) unset($x->$var);
        }

        $defaults['unset_array']+=array('field_selection','details_tabs');

        foreach($defaults['unset_array'] as $item) unset($x->$item);

        return (array) $x;
    }

    function dump($options = array()){
        return dump_array($this->dump_to_array(),$options);
    }

    function check_file_upload($options = array()){
        global $db, $cfg;
        $defaults = array(
            'table'=>"",
            'link_field_id'=>"",
        );
        if (!empty($options)) foreach($options as $key=>$value) $defaults[$key] = $value;

        if ($defaults["link_field_id"] && $defaults["table"]) { // compare the database content with the disk images - remove the fields from links table
            $files = $db->select("*", $defaults["table"], array("WHERE link_id=?", array('link_id' => $defaults["link_field_id"]), array('integer')));
            if ($files) {
                foreach($files as $file) {
                    if (!is_file($cfg["secure_dir"] . $file->file)) {
                        $db->delete(
                                $defaults["table"],
                                array(
                                        "WHERE file=?",
                                        array('file' => $file->file),
                                        array('varchar')
                                )
                        );
                    }
                }
            }
        }
    }

    function render_stamp_rows($options=array()){
        global $libhtml;

        $html ='';

        if (!empty($libhtml) && isset($this->created_by_name) && isset($this->created_time)) {

            $html .= $libhtml->render_table_row("Created By",$this->created_by_name . " on " . zero_date($this->created_time,"d M Y H:i"),$options);

            if (!empty($this->modified_by)) $html .= $libhtml->render_table_row("Last Modified By",$this->modified_by_name . " on " . zero_date($this->modified_time,"d M Y H:i"),$options);


        }

        return $html;
    }
    
    function getLocal($localKey){
        global $libhtml;
        return $libhtml->local_text[$localKey];
    }
}
