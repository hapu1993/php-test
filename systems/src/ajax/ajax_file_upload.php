<?php
/*
 * jQuery File Upload Plugin PHP Class 6.6.3
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

require_once dirname(__FILE__).'/../config/global.php';
require_once $cfg['source_root'] . "includes/common_ajax_includes.php";

class UploadHandler {
    protected $options;
    protected $error_messages = array(
        1 => 'The uploaded file exceeds the maxiumum allowed file size',
        2 => 'The uploaded file exceeds the maxiumum allowed file size',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload',
        'long_filename' => 'A filename is too long. Please rename the file and try again',
        'post_max_size' => 'The uploaded file exceeds the maxiumum allowed file size',
        'max_file_size' => 'The uploaded file exceeds the maxiumum allowed file size',
        'min_file_size' => 'The uploaded file is smaller than minimum allowed file size',
        'accept_file_types' => 'The uploaded file type is not allowed',
        'max_number_of_files' => 'Maximum number of files exceeded',
    );

    function __construct($options = null, $initialize = true, $error_messages = null) {
        global $cfg, $_REQUEST;

        $this->options = array(
            'script_url' => $cfg["root"] . "ajax/ajax_file_upload.php",
            'full_upload_path' => $cfg["secure_dir"] . ((!empty($_REQUEST['folder'])) ? $_REQUEST['folder'] : ''),
            'upload_folder' => (!empty($_REQUEST['folder'])) ? $_REQUEST['folder'] : '',
            'mkdir_mode' => 0755,
            'param_name' => 'files',
            'access_control_allow_origin' => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods' => array(
                'GET',
                'POST',
            ),
            'access_control_allow_headers' => array(
                'Content-Type',
                'Content-Range',
                'Content-Disposition'
            ),

            // example: jpg|jpeg|bmp|png|tiff
            'accept_file_types' => (!empty($_REQUEST['accepted_ft']) && $_POST["accepted_ft"] != "*") ? "/(\.|\/)(".$_REQUEST['accepted_ft'].")$/i" : "/.+$/i",

            // The php.ini settings upload_max_filesize and post_max_size take precedence over
            'max_file_size' => (isset($_POST["max_fs"]) && $_POST["max_fs"] != "*") ? $_POST["max_fs"] : null,

            // minimum file size in kB
            'min_file_size' => 1,

            // The maximum number of files for the upload directory
            'max_number_of_files' => null,

            // multi file upload
            'multi_file' => (!empty($_REQUEST['multi_file'])) ? true : false,

            // keep file on disc even when the file is deleted
            'keep_file' => (!empty($_REQUEST['keep_file'])) ? true : false,

            // crypt the file after the upload
            'secure_file' => (!empty($_REQUEST['secure_file'])) ? true : false,

            // set to false to allow resumable uploads
            'discard_aborted_uploads' => true,

            // create a linked table
            'multi_table' => (!empty($_REQUEST['multi_table'])) ? $_REQUEST['multi_table'] : '',
            'unique_link' => (!empty($_REQUEST['unique_link'])) ? $_REQUEST['unique_link'] : '',
        );

        if ($options) $this->options = array_merge($this->options, $options);
        if ($error_messages) $this->error_messages = array_merge($this->error_messages, $error_messages);
        if ($initialize) $this->initialize();

    }

    protected function initialize() {
        global $cfg, $_REQUEST;

        if (!empty($_REQUEST["discard"]) && empty($_REQUEST["keep_file"])) {
            if (is_file($cfg["secure_dir"] . $_REQUEST["discard"])) unlink($cfg["secure_dir"] . $_REQUEST["discard"]);
            exit;
        }

        switch ($this->get_server_var('REQUEST_METHOD')) {
            case 'GET':
                $this->get();
                break;
            case 'POST':
                $this->post();
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    // start file upload
    public function post($print_response = true) {

        $upload = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : null;

        // Parse the Content-Disposition header, if available:
        $file_name = $this->get_server_var('HTTP_CONTENT_DISPOSITION') ? rawurldecode(preg_replace( '/(^[^"]+")|("$)/', '', $this->get_server_var('HTTP_CONTENT_DISPOSITION'))) : null;

        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = $this->get_server_var('HTTP_CONTENT_RANGE') ? preg_split('/[^0-9]+/', $this->get_server_var('HTTP_CONTENT_RANGE')) : null;
        $size =  $content_range ? $content_range[3] : null;

        $content_range = $this->get_server_var('CONTENT_LENGTH');
        $size = $content_range;

        $files = array();
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value){
                $files[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }
        } else {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $files[] = $this->handle_file_upload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ? $upload['name'] : null),
                $size ? $size : (isset($upload['size']) ? $upload['size'] : $this->get_server_var('CONTENT_LENGTH')),
                isset($upload['type']) ? $upload['type'] : $this->get_server_var('CONTENT_TYPE'),
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }
        return $this->generate_response(array($this->options['param_name'] => $files), $print_response);
    }

    // handle file upload
    private function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
        global $db, $cfg, $_POST;

        $file = new stdClass();
        $file->nice_name = $this->trim_file_name($name, $type, $index, $content_range);
        $file->type = strtolower(pathinfo($file->nice_name, PATHINFO_EXTENSION));
        $file->name = date("ymd_His")."_".base64_encode($file->nice_name) . '.' .$file->type;
        $file->size = $this->fix_integer_overflow(intval($size));

        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) mkdir($upload_dir, $this->options['mkdir_mode'], true);
            $file_path = $this->get_upload_path($file->name);
            $append_file = $content_range && is_file($file_path) && $file->size > $this->get_file_size($file_path);

            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents( $file_path, fopen($uploaded_file, 'r'), FILE_APPEND);

                } else {
                    move_uploaded_file($uploaded_file, $file_path);

                    // if this is secured, crypted file
                    if ($this->options["secure_file"]) {
                        $secure = new Secure();
                        $secure->upload($file_path, (isset($this->options['image']) && $this->options['image']) ? $this->options['image'] : false);
                        $file_path = $file_path.".crypt";
                        $file->name = $file->name . ".crypt";
                    }

                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
            }

            $file_size = $this->get_file_size($file_path, $append_file);
            if ($file_size !== $file->size && $this->options["secure_file"]) {
                $file->size = $file_size;
                if (!$content_range && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = 'Oops, something went wrong.';
                }
            }

            $file->filepath = $this->options["upload_folder"].$file->name;
            $this->set_additional_file_properties($file);

            // linked tables
            if ($this->options["multi_table"]) $db->insert($this->options["multi_table"], array("link_id"=>$this->options["unique_link"], "file"=>$this->options["upload_folder"].$file->name));

        }

        return $file;
    }

    // get file/s on edit
    public function get($print_response = true) {
        global $cfg;

        // if we are getting one file only
        $file_name = (!empty($_REQUEST["file"])) ? $_REQUEST["file"] : null;

        // if we are getting multi files
        $files_table = (!empty($_REQUEST["get_files_table"])) ? $_REQUEST["get_files_table"] : null;
        $files_link = (!empty($_REQUEST["get_files_link_id"])) ? $_REQUEST["get_files_link_id"] : null;

        // get multi files serialized value
        if ($this->options["multi_file"]) {

            $unserialized_files = array();
            $unserialized = json_decode($_GET["file"]);
            if (!empty($unserialized)) {
                foreach ($unserialized as $file_by_file) {
                    // fix for json decode converting + to space
                    $file_by_file = str_replace(" ", "+", $file_by_file);
                    $file_object = $this->get_file_object($file_by_file);
                    if ($file_object) $unserialized_files[] = $file_object;
                }
                $response = array("files" => $unserialized_files);
            }

        // get linked table
        } else if ($files_table && $files_link) {
            $response = $this->get_file_object("", $files_table, $files_link);

        // get single file
        } else if ($file_name) {
            $response = array("files" => array($this->get_file_object($file_name)));
        }

        if (!empty($response)) return $this->generate_response($response, $print_response);
    }

    // shared between add and edit forms
    protected function set_additional_file_properties($file) {
        global $cfg;

        // what is the file extension, can we create thumbnails
        if (strpos($file->nice_name, ".") !== 0) {
            $ext = extension($file->nice_name);
            if (!$this->options['secure_file'] && in_array($ext,array("jpg","jpeg","png","gif","bmp"))) {
                $file->thumb = phpThumb_URL(array(
                    "src"=>$cfg['secure_dir'] . $this->options["upload_folder"].$file->name,
                    "w"=>60,
                    "h"=>60,
                    "zc"=>1
                ));
            } else {
                $file->file_type = $ext;
            }
        }

        // download url
        $file->url = encrypt_url($cfg['root'] . "includes/downloader.php?file_name=".urlencode($this->options["upload_folder"] . $file->name));

        // keep or discard the file on delete
        if ($this->options["keep_file"]) $file->delete_url = $this->options['script_url'].'?discard='.$file->filepath.'&keep_file=true';
        else $file->delete_url = $this->options['script_url'].'?discard='.$file->filepath;
    }

    // function is called on edit, get all previously uploaded files
    protected function get_file_object($file_name, $files_table = "", $files_link = "") {
        global $db, $cfg;

        // multi file upload with linked tables
        if (!empty($files_table) && !empty($files_link)) {
            $all_files = $db->select("*", $files_table, array("WHERE link_id = ?", array('link_id' => $files_link), array('integer')));
            $result = array();
            foreach ($all_files as $file_by_file) {
                $result[] = $this->get_file_object($file_by_file->file);
            }
            return $result;

        // single file upload, get that file properties
        } else {

            if ($this->is_valid_file_object($file_name)){

				$file = new stdClass();
                $file->filepath = $file_name;
                $file->size = $this->get_file_size($this->get_upload_path($file_name));
                $file->name = $file_name;

				$path_parts = pathinfo($file_name);
				if (base64_decode(substr($path_parts['filename'],14),true) !== false){
		            $file->nice_name = base64_decode(substr($path_parts['filename'],14));
		        } else {
		            $file->nice_name = substr($path_parts['basename'],14);
		        }

				$file->type = strtolower($path_parts['extension']);

                $this->set_additional_file_properties($file);

                return $file;
            }
            return null;
        }
    }

    // internal function
    protected function get_upload_path($file_name = null) {
        return $this->options['full_upload_path'].$file_name;
    }

    protected function fix_integer_overflow($size) {
        if ($size < 0) $size += 2.0 * (PHP_INT_MAX + 1);
        return $size;
    }

    protected function get_file_size($file_path, $clear_stat_cache = false) {
        if ($clear_stat_cache) clearstatcache(true, $file_path);
        return $this->fix_integer_overflow(filesize($file_path));
    }

    protected function is_valid_file_object($file_name) {
        $file_path = $this->get_upload_path($file_name);
        if (is_file($file_path) && $file_name[0] !== '.') {
            return true;
        } else {
            return false;
        }
    }

    function get_config_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $this->fix_integer_overflow($val);
    }

    protected function get_error_message($error) {
        return array_key_exists($error, $this->error_messages) ?
            $this->error_messages[$error] : $error;
    }

    protected function validate($uploaded_file, $file, $error, $index) {
        if ($error) {
            $file->error = $this->get_error_message($error);
            return false;
        }
        $content_length = $this->fix_integer_overflow(intval(
            $this->get_server_var('CONTENT_LENGTH')
        ));
        if (strlen(base64_encode($file->nice_name)) > 200) {
            $file->error = $this->get_error_message('long_filename');
            return false;
        }
        $post_max_size = $this->get_config_bytes(ini_get('post_max_size'));
        if ($post_max_size && ($content_length > $post_max_size)) {
            $file->error = $this->get_error_message('post_max_size');
            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file->nice_name)) {
            $file->error = $this->get_error_message('accept_file_types');
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->get_file_size($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            $file->error = $this->get_error_message('max_file_size');
            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            $file->error = $this->get_error_message('min_file_size');
            return false;
        }
        if (is_int($this->options['max_number_of_files']) && (
                $this->count_file_objects() >= $this->options['max_number_of_files'])
            ) {
            $file->error = $this->get_error_message('max_number_of_files');
            return false;
        }
        return true;
    }

    // Remove path information and dots around the filename, to prevent uploading into different directories or replacing hidden system files.
    protected function trim_file_name($name, $type = null, $index = null, $content_range = null) {

        // remove control characters and spaces (\x00..\x20) around the filename
        $name = trim(basename(stripslashes($name)), ".\x00..\x20");

        // Use a timestamp for empty filenames
        if (!$name) $name = str_replace('.', '-', microtime(true));

        // Add missing file extension for known image types
        if (strpos($name, '.') === false && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $name .= '.'.$matches[1];
        }

        return $name;
    }

    protected function get_server_var($id) {
        return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
    }

    protected function generate_response($content, $print_response = true) {
        if ($print_response) {
            $json = json_encode($content);
            if ($this->get_server_var('HTTP_CONTENT_RANGE')) {
                $files = isset($content[$this->options['param_name']]) ? $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    header('Range: 0-'.($this->fix_integer_overflow(intval($files[0]->size)) - 1));
                }
            }
            echo $json;
        }
        return $content;
    }

}

// function rename_file($filename){
    //return str_replace(array("&", "#", ",", "  "), array("", "", "", " "), $filename);
    //return preg_replace("/[^a-zA-Z0-9?.-_:;()[]{}\s]/", "", $filename);
    // return base64_encode($filename);
// }

$upload_handler = new UploadHandler();
