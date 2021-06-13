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
 * Uses ImageMagick to convert first page of PDF to image
 * is possible to use libreoffice --headless -convert-to pdf <path/source document> -outdir <output path>
 * to convert office docs to PDF
 */

class Secure extends Object{

    function upload($file_path, $generate_image=true) {
        $fp = null;
        $fp2 = null;

        //get document
        $fp = file_get_contents($file_path);

        // generate image
        if ($generate_image && pathinfo($file_path, PATHINFO_EXTENSION) == "pdf") {
            if (extension_loaded('Imagick')) {
                $im = new Imagick($file_path."[0]");
                $im->setImageColorspace(255);
                $im->setCompression(Imagick::COMPRESSION_JPEG);
                $im->setCompressionQuality(100);
                $im->setImageFormat('jpeg');

                $im->writeImage($file_path.'.jpg');
                $im->clear();
                $im->destroy();

                unlink($file_path);
                $fp2 = file_get_contents($file_path.".jpg");
                unlink($file_path.".jpg");
            } else {
                error_log("ImageMagick PHP Module not available. Cannot generate image.");
            }
        }

        $crypted = $this->crypt_contents($fp);
        file_put_contents($file_path.".crypt", $crypted);

        if (!is_null($fp2)) {
            $crypted = $this->crypt_contents($fp2);
            file_put_contents($file_path.".jpg.crypt", $crypted);
        }

        unlink($file_path);
    }

    function crypt_contents($contents, $pass="") {
        if (empty($pass)) $pass = substr($_SESSION["password"], 0, 24);  // there is a 24 char limit for salt

        $local_crypt = new Crypt("", "", $pass);
        //compress and encode document
        $compressed = gzcompress($contents);
        //file_put_contents($file_path.".gz", $compressed);
        $base64_encoded = base64_encode($compressed);
        $crypted = $local_crypt->str_encrypt($base64_encoded);
        return $crypted;
    }

    function extract_file($item, $pass="") {
        set_error_handler('secure_handleError'); // to suppress gzuncompress warnings when wrong user.
        $real_data = "";
        if (empty($pass)) $pass = substr($_SESSION["password"], 0, 24);  // there is a 24 char limit for salt

        $local_crypt = new Crypt("", "", $pass);
        $decrypted = $local_crypt->str_decrypt($item);
        $base64_decoded = base64_decode($decrypted);
        $real_data = @gzuncompress($base64_decoded);

        restore_error_handler();
        return $real_data;
    }

    function re_encode($person_id, $old, $new) {
        global $person;
        if (empty($person)) $person = get_clean_object($person_id, 'Person', false);
        if (!empty($person)){
            $items = $this->_get(array("where"=> array("WHERE link=?", array('link' => $this->get_link($person)), array('varchar'))));
            $this->_re_encode($this->table, $items, $old, $new);
        }
    }

    private function _re_encode($table, $items, $old, $new) {
        global $cfg, $db;

        foreach ($items as $item) {
            $contents = $this->extract_file($item->file_content, $old);
            $item->file_content = $this->crypt_contents($contents, $new);
            if (isset($item->file_image) && !empty($item->file_image)) {
                $contents = $this->extract_file($item->file_image, $old);
                $item->file_image = $this->crypt_contents($contents, $new);
                $db->update($table, array('file_content'=>$item->file_content, 'file_image'=>$item->file_image), array("WHERE id = ?", array('id' => $item->id), array('integer')), $this->table_types);
            } else {
                $db->update($table, array('file_content'=>$item->file_content), array("WHERE id = ?", array('id' => $item->id), array('integer')), $this->table_types);
            }
        }
    }

    function echo_image($item) {
        global $cfg;
        $error_level = ini_get('error_reporting');
        error_reporting(0);

        header('Content-type: image/jpeg');
        $file_data =  $this->extract_file($item);

        header('Content-type: image/jpeg');
        if (is_null($file_data) || empty($file_data)) {
            echo file_get_contents($cfg['root'] . "images/ico_app_blank.png");
        } else {
            echo $file_data;
        }
        error_reporting($error_level);
    }

    function update_links($class) {
        $this->_update_links($class, $this->table, $this->table_types);
    }

    private function _update_links($class, $table, $table_types) {
        global $db, $user1;
        //             $local_person = new Person;
        //             $local_person = $local_person->select($user1->person_id);
        $person = get_clean_object($class->id,'Person');

        $old_string = $person->name . $person->id . $person->surname;
        $new_string = $class->name . $class->id . $class->surname;

        $old_link = hash('sha1', "SF�$%^/.".$old_string."76t(Y*");
        $new_link = hash('sha1', "SF�$%^/.".$new_string."76t(Y*");
        $db->update($table, array('link' => $new_link), array("WHERE link = ?", array('link' => $old_link), array('varchar')), $table_types);
    }

    function get_link($local_person = "") {
        global $person;
        $string = "";
        if (!empty($local_person)) {
            $string = $local_person->name.$local_person->id.$local_person->surname;
        } elseif (!empty($person)) {
            $string = $person->name.$person->id.$person->surname;
        } else {
            error_log("ERROR: Person empty in Secure get link");
            error_log(print_r(debug_backtrace()));
        }
        return $this->_get_link($string);
    }

    private function _get_link($string) {
        $link = hash('sha1', "SF�$%^/.".$string."76t(Y*");
        return $link;
    }
}

function secure_handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
