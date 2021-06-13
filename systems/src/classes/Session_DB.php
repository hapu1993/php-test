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
 *
 * if using session key value storage then we can use set_var($key, $val),
 * get_var($key), unset_var($key) however this should then be used EVERYWHERE
 * and the $_SESSION superglobal should not be used.
 *
 * When setting the element of an array the entire array must be passed to
 * set_var to update the contents.
 *
 * Also if doing this write_key_vals function should do nothing as this will
 * be called at the end of page execution writing the contents of the $_SESSION
 * superglobal and wipe out the data set using the set_var methods and the
 * set_var values will be lost..
 */
class Session_DB {

    private $session_db;
    private $crypt;
    private $session_id;
    private $use_key_values;
    private $crypt_keys;
    private $crypt_values;
    private $session_table;
    private $session_kv_table;
    //for singleton
    private static $instance;

    private $table_types = array();
    private $kv_table_types = array();

    // needed for inherits
//     private $index;
//     private $curElement;

    /**
     * Enforce Singleton
     *
     * @access public
     * @return Session_DB
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Unserializing is not allowed.', E_USER_ERROR);
    }

    private function __construct() {
        global $cfg;

        $this->session_db = DB2_Factory::factory();
        $this->crypt = new Crypt;
        $this->session_table = "system_session";

        $table_schema = $this->session_db->get_table_column_metadata($this->session_table);

        foreach ($table_schema as $schema) {
            if (isset($schema->COLUMN_NAME)) {
                $this->table_types[$schema->COLUMN_NAME] = (array) $schema;
            } elseif (isset($schema->column_name)) {
                $this->table_types[$schema->column_name] = (array) $schema;
                $this->table_types[$schema->column_name]  = array_change_key_case($this->table_types[$schema->column_name], CASE_UPPER);
            }
        }

        $this->session_kv_table = "system_session_key_value";

        $kv_table_schema = $this->session_db->get_table_column_metadata($this->session_kv_table);

        foreach ($kv_table_schema as $schema) {
            if (isset($schema->COLUMN_NAME)) {
                $this->kv_table_types[$schema->COLUMN_NAME] = (array) $schema;
            } elseif (isset($schema->column_name)) {
                $this->kv_table_types[$schema->column_name] = (array) $schema;
                $this->kv_table_types[$schema->column_name]  = array_change_key_case($this->kv_table_types[$schema->column_name], CASE_UPPER);
            }
        }

        $this->use_key_values = (isset($cfg['use_session_key_value']) && $cfg['use_session_key_value'] === TRUE);

        if ($this->use_key_values) {

            $this->crypt_keys = (isset ( $cfg ['crypt_session_keys'] )) ? $cfg ['crypt_session_keys'] : true;
            $this->crypt_values = (isset ( $cfg ['crypt_session_values'] )) ? $cfg ['crypt_session_values'] : true;

            session_set_save_handler ( array (
                    $this,
                    "open"
            ), array (
                    $this,
                    "close"),
                                    array($this, "read_key_vals"),
                                    array($this, "write_key_vals"),
                                    array($this, "destroy"),
                                    array($this, "gc")
            );

        } else {

            session_set_save_handler(
                                    array($this, "open"),
                                    array($this, "close"),
                                    array($this, "read"),
                                    array($this, "write"),
                                    array($this, "destroy"),
                                    array($this, "gc")
            );
        }

        // It is possible normal PHP Garbage Collection does not occur thus we shall call our own when opening a new session.

        // **PREVENTING SESSION FIXATION**
        // This causes the session identifier to be persisted between requests using cookies.
        // It should either not be set at all, or explicitly set to 1, its default value.
        ini_set('session.use_cookies',1);

        // This prevents the session identifier from being persisted or overridden by other methods of introducing data into the request, such as query string and POST parameters.
        // It should be explicitly set to 1.
        ini_set('session.use_only_cookies', 1);

        // This causes PHP to automatically modify its output to persist the session identifier in links and forms.
        // It should be explicitly set to 0.
        ini_set('session.use_trans_sid', 0);

        // When session.use_trans_id is enabled, it dictates what HTML tags have their values rewritten to include the session identifier.
        // It should be explicitly set to the empty string to prevent session.use_trans_id from having an effect if accidentally enabled.
        ini_set('url_rewriter.tags','');

        // **PREVENTING SESSION HIJACKING**
        // Prevents javascript XSS attacks aimed to steal the session ID
        // allow non http_only cookies in dev mode so selenium tests using curl can authenticate.
        ini_set('session.cookie_httponly', (!isset($cfg['ENV']) || !in_array($cfg['ENV'], array('dev', 'local-dev'))));

        // Adds entropy into the randomization of the session ID, as PHP's random number
        // generator has some known flaws
        ini_set('session.entropy_file', '/dev/urandom');
        ini_set('session.entropy_length', '32');
        ini_set('session.hash_bits_per_character', 6);

        //If SSL, use only secure cookies
        if (isSSL()) ini_set('session.cookie_secure', 1);

        header('Cache-control: no-store');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: deny');
        header_remove('X-Powered-By');

        //Start the session! Assumes presence of config file!!!
        if (!empty($cfg['session_name'])) session_name(str_replace(" ", "_", $cfg['session_name']));

        session_start();

        $this->session_id = session_id();
        //can use this to regenerate session id on each request.

    }

    function __destruct() {
        session_write_close();
    }

    public function open(){
        global $cfg;
        $time_out = (!empty($cfg['session_timeout'])) ? $cfg['session_timeout'] : 1440;
        $this->gc($time_out);
        return true;
    }

    public function close(){
        if (!is_null($this->session_db)) $this->session_db->close();
        $this->session_db = null;
        return true;
    }

    public function read_key_vals($id){
        $selection = $this->session_db->select("session_key, session_value", $this->session_kv_table, "WHERE " . $this->session_db->column_escape("id") . " = '" . $this->session_db->escape($id) . "'");
        $data = "";
        foreach ($selection as $sel) {
            $key = ($this->crypt_keys) ? $this->crypt->str_decrypt($sel->session_key) : $sel->session_key;
            $val = ($this->crypt_values) ? $this->crypt->str_decrypt($sel->session_value) : $sel->session_value;
            $data .= $key . "|" . $val;
        }
        return $data;
    }

    public function read($id){
        $selection = $this->session_db->select_value("data", $this->session_table, array("WHERE id=?", array('id' => $id), array('varchar')));
        $data = $this->crypt->str_decrypt($selection);
        $data = (get_magic_quotes_gpc ()) ? stripslashes ( $data ) : $data;
        return $data;
    }

    public function write_key_vals($id, $data) {
        $id = $this->session_db->escape ( $id );
        //Write and Close handlers are called after destructing objects since PHP 5.0.5.
        //Thus destructors can use sessions but session handler can't use objects.
        //hence the if statement as this will be called after destroy

        if (!empty($data)) {
            $this->_update($id, "");

            $data_key_vals = $this->unserialize_php($data);
            $this->session_db->delete($this->session_kv_table, array("WHERE ".$this->session_db->column_escape("id") . " = ?", array('id' => $id), array('varchar')));
            foreach ($data_key_vals as $key=>$val) {
                $serialized_val = serialize($val);
                $new_key = ($this->crypt_keys) ? $this->crypt->str_encrypt($key) : $key;
                $new_value = ($this->crypt_values) ? $this->crypt->str_encrypt($serialized_val) : $serialized_val;
                $block_insert_vals[] = "(".$this->session_db->quote($id).", ".$this->session_db->quote($new_key).", ".$this->session_db->quote($this->session_db->escape($new_value)).", ".$this->session_db->quote(substr($serialized_val, 0, 1)).", ".strlen($serialized_val).")";
            }
            $this->session_db->block_insert($this->session_kv_table,array('id', 'session_key', 'session_value','type','length'), $block_insert_vals);
        } else {
            $this->session_db->delete($this->session_kv_table, array('WHERE id = ?', array('id' => $id), array('varchar')));
        }
        //         session_write_close();
        return true;
    }

    public function write($id, $data){
        //Write and Close handlers are called after destructing objects since PHP 5.0.5.
        //Thus destructors can use sessions but session handler can't use objects.
        //hence the if statement as this will be called after destroy

        if (!empty($data)) {
            $this->_update($id, $data);
        } else {
            $this->session_db->delete($this->session_table, array("WHERE id = ?", array('id' => $id), array('varchar')));
        }
        //         session_write_close();
        return true;
    }

    /*
     * Updates DB with access time for expiry.
     * if not using session key value methods also sets session data.
     */
    private function _update($id, $data) {

        //get user id from the user_id field of the session data
        //We CAN use $_SESSION here - calls on db_session_read
        $user_id = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : NULL;

        $crypted_data = (!empty($data)) ? $this->crypt->str_encrypt($data) : "";

        $where = array("WHERE id = ?", array('id' => $this->session_id), array('varchar'));
        $insert_array = array(
                'access' => time(),
                'user_id' => $user_id,
                'data' => $crypted_data,
                'ip' => (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
                'created_on' => date("Y-m-d H:i:s"),
                'user_agent'=>(isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '',
        );
        if (isset($_SESSION['user_id'])) $insert_array['user_id']=$user_id;

        $this->session_db->start_transaction();
        $created = $this->session_db->select_value("created_on",$this->session_table, $where);

        if (!empty($created)) {
            //update
            $insert_array['created_on']=$created;

            $this->session_db->update($this->session_table, $insert_array, $where, $this->table_types);
        } else {
            // insert
            $insert_array['id']=$id;
            $this->session_db->insert($this->session_table, $insert_array, $this->table_types);
        }

        $this->session_db->complete_transaction();

    }

    /**
     * Session set_var method
     * will be more efficient than using the global $_SESSION as it
     * will delete and rewrite the entire session data whereas this
     * function only affects a single key=>value pair.
     * ONLY MORE EFFICIENT IF NOT USING SUPER GLOBAL $_SESSION
     * Only usable with config variable use_session_key_value.
     *
     * TODO: make more efficient if using super global this is where the inherits version comes in.
     *
     * @param  Session key
     * @param  Session value
     * @return nothing
     * @since  Method added since 2012-05-02.
     */
    public function set_var($key, $val) {
        if ($this->use_key_values) {
            $new_key = ($this->crypt_keys) ? $this->crypt->str_encrypt($key) : $key;
            $serialized_val = serialize($val);
            $new_value = ($this->crypt_values) ? $this->crypt->str_encrypt($serialized_val) : $serialized_val;
            if ($this->crypt_keys) {
                $selection = $this->session_db->select("session_key, session_value", $this->session_kv_table, array("WHERE id = ?", array('id' => $this->session_id)));
                $replaced = false;
                foreach ($selection as $sel) {
                    $var = ($this->crypt_keys) ? $this->crypt->str_decrypt($sel->session_key) : $sel->session_key;
                    if ($key == $var) {
                        $this->session_db->update($this->session_kv_table, array("session_value"=>$new_value,'type'=>substr($serialized_val, 0, 1),'length'=>strlen($serialized_val)),
                                                  array("WHERE id = ? AND session_key = ?", array('id' => $this->session_id, 'session_key' => $sel->session_key)), $this->kv_table_types);
                        $replaced = true;
                    }
                }
                if ($replaced !== true) {
                       $this->session_db->insert($this->session_kv_table, array("id"=>$this->session_id, "session_key"=>$new_key, "session_value"=>$new_value,'type'=>substr($serialized_val, 0, 1),'length'=>strlen($serialized_val)), $this->kv_table_types);
                }
            } else {
//                 cannot use replace as there is no unique key in the table
//                 $this->session_db->replace($this->session_kv_table, array("session_id"=>$this->session_id, "session_key"=>$new_key, "session_value"=>$new_value,'type'=>substr($serialized_val, 0, 1),'length'=>strlen($serialized_val)), " WHERE ".$this->session_db->column_escape("id") . "= ".$this->session_db->quote($this->session_id)." AND ".$this->session_db->column_escape("session_key")." = ".$this->session_db->quote($new_key));
                $this->session_db->delete($this->session_kv_table, " WHERE ".$this->session_db->column_escape("id") . "= ".$this->session_db->quote($this->session_id)." AND ".$this->session_db->column_escape("session_key")." = ".$this->session_db->quote($new_key));
                $this->session_db->insert($this->session_kv_table, array("id"=>$this->session_id, "session_key"=>$new_key, "session_value"=>$new_value,'type'=>substr($serialized_val, 0, 1),'length'=>strlen($serialized_val)), $this->kv_table_types);
            }
        } else {
            trigger_error('Unavailable method called', E_USER_DEPRECATED);
            $e = new Exception("Unavailable method called: ");
        }
    }


    /**
     * Session unset_var method called unset_var as unset is reserved
     * will be more efficient than using the global $_SESSION as it
     * will delete and rewrite the entire session data whereas this
     * function only affects a single key=>value pair.
     * ONLY MORE EFFICIENT IF NOT USING SUPER GLOBAL $_SESSION
     * Only usable with config variable use_session_key_value
     *
     * TODO: make more efficient if using super global this is where the inherits version comes in.
     *
     * @param  Session key
     * @return nothing
     * @since  Method added since 2012-05-02.
     */
    public function unset_var($key) {
        if ($this->use_key_values) {
            $selection = $this->session_db->select("session_key, session_value", $this->session_kv_table, "WHERE " . $this->session_db->column_escape("id") . " = " . $this->session_db->quote($this->session_id));
               foreach ($selection as $sel) {
                $var = ($this->crypt_keys) ? $this->crypt->str_decrypt($sel->session_key) : $sel->session_key;
                   if ($key == $var) {
                    $this->session_db->delete($this->session_kv_table, " WHERE ".$this->session_db->column_escape("id") . "= ".$this->session_db->quote($this->session_id)." AND ".$this->session_db->column_escape("session_key")." = ".$this->session_db->quote($sel->session_key));;
                }
               }
        } else {
            trigger_error('Unavailable method called', E_USER_DEPRECATED);
            $e = new Exception("Unavailable method called: ");
        }
    }

    public function get_var($key) {
        if ($this->use_key_values) {
            $selection = $this->session_db->select("session_key, session_value", $this->session_kv_table, "WHERE " . $this->session_db->column_escape("id") . " = " . $this->session_db->quote($this->session_id));
            foreach ($selection as $sel) {
                $var = ($this->crypt_keys) ? $this->crypt->str_decrypt($sel->session_key) : $sel->session_key;
                if ($key == $var) {
                    $data = ($this->crypt_values) ? $this->crypt->str_decrypt($sel->session_value) : $sel->session_value;
                    return unserialize($data);
                }
            }
        } else {
            trigger_error('Unavailable method called', E_USER_DEPRECATED);
            $e = new Exception("Unavailable method called: ");
        }
    }

    public function destroy(){
        //session destroy does not unset any of the global variables associated with the session, or unset the session cookie
        unset($_SESSION);
        session_unset();
        $selection = $this->session_db->delete($this->session_table, array("WHERE id = ?", array('id' => $this->session_id), array('varchar')));
        if ($this->use_key_values) $this->_tidy_key_values();
        return ($selection > 0);
    }

    public function gc($maxlifetime){
        $old = time() - $maxlifetime;
        $selection = $this->session_db->delete($this->session_table, array("WHERE access < ?", array('access' => $old), array('integer')));
        if ($this->use_key_values) $this->_tidy_key_values();
        return ($selection > 0);
    }

    private function _tidy_key_values() {
        $this->session_db->delete($this->session_kv_table, " WHERE ".$this->session_db->column_escape("id") . "= '" . $this->session_id . "'");
        $orphaned = $this->session_db->select_distinct("ss.id", "{$this->session_table} ss LEFT JOIN {$this->session_kv_table} sskv ON ss.id=sskv.id");
        foreach ($orphaned as $sel) {
            $ids[] = $sel->id;
        }
        if (isset($ids) && !empty($ids)) $this->session_db->delete($this->session_kv_table, "WHERE " . $this->session_db->column_escape("id") . " NOT IN ('" . implode("', '", $ids) . "')");
    }

    public function update_id() {
        $old_sess_id = session_id();
        session_regenerate_id(false);
        $new_sess_id = session_id();

        $this->session_db->update($this->session_table, array('id' => $new_sess_id), array("WHERE id = ?", array('id' => $old_sess_id)), $this->table_types);
        $this->session_db->update($this->session_kv_table, array('id' => $new_sess_id), array("WHERE id = ?", array('id' => $old_sess_id)), $this->kv_table_types);
        $this->session_id = $new_sess_id;
    }


    /*
     * used to unserialse session as session_decode writes data into $_SESSION
     * Found at http://www.php.net/manual/en/function.session-decode.php#108037
     */
    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserialize_phpbinary($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

}
