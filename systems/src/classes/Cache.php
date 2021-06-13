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
class Cache {

    private $has_buffer_started = false;
    private $buffer_content = "";

    function __construct($options=array()) {
        global $cfg, $user1;
        $defaults = array(
            'user_id'=>'',
            'subdirectory'=>''

        );
        if (!empty($options)) foreach($options as $key=>$value) $defaults[$key] = $value;

        $this->user_id = (!empty($defaults['user_id']) || $defaults['user_id']==0) ? $defaults['user_id'] : $user1->id;

        $this->caching_path = $cfg['cache'] . $defaults['subdirectory'];
        $this->directory_ok = $this->checkDirectory($this->caching_path);
    }

    function cache_content($name,$content,$duration = 0,$options = array()) {

        if ($this->directory_ok) {

            $filename = $this->caching_path . $this->_encrypt_name($name . $this->user_id);
            $content = array(
                'duration' => $duration,
                'creation' => time(),
                'content' => $content
            );
            $content = serialize($content);
            return file_put_contents($filename,$content);
        } else {
            return false;
        }
    }

    function retrieve_cache($name) {

        if ($this->directory_ok) {
            $filename = $this->caching_path . $this->_encrypt_name($name . $this->user_id);

            if(!file_exists($filename))
                return false;

            $content = file_get_contents($filename);
            $content = unserialize($content);

            if($content['duration'] == 0)
                return $content['content'];
               if(time() > $content['creation']+$content['duration'])
                return false;
            else
                return $content['content'];
        } else {
            return false;
        }
    }

    function start_buffer() {
        // we don't need to handle multiple buffers for simple content caching
        if(!$this->has_buffer_started){
            ob_start();
            $this->has_buffer_started = true;
        }
    }

    function stop_buffer() {
        if($this->has_buffer_started){
            $content = ob_get_clean();
            $this->buffer_content = $content;
            $this->has_buffer_started = false;
        }
    }

    function cache_buffer($name,$duration = 0) {
        // buffer has already been opened and closed
        if(!$this->has_buffer_started) {
            // simply use the method we already written earlier.
            return $this->cache_content($name,$this->buffer_content,$duration);
        } else {
            return false;
        }
    }

    function delete_cache($name) {
        if ($this->directory_ok) {
            $filename = $this->caching_path . $this->_encrypt_name($name . $this->user_id);
            if(!file_exists($filename))
                return false;
            else
                unlink($filename);
            return true;
        } else {
            return false;
        }
    }

    function _encrypt_name($name) {
        return md5($name);
    }

    protected function checkDirectory($dir){
        if(!is_dir($dir) && mkdir($dir, 0777, true)==false) {
            error_log("Unable to create directory '$dir'.");
            return false;
        }
        if(!is_writable($dir) && chmod($dir, 0777)==false) {
            error_log("Unable to chmod directory '$dir' to 0777.");
            return false;
        }
        return true;
    }

}
