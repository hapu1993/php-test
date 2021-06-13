<?php

require_once __DIR__.DIRECTORY_SEPARATOR."../config/global.php";

class Solr {
    private $host = "localhost";
    private $port = 8983;
    private $core = "collection1/";
    private $search_format_string = "&hl.highlightMultiTerm=true&fl=[docid],name,resourcename,score,content_type,author&indent=on&hl.fl=content&hl.usePhraseHighlighter=true&hl=true&wt=php&hl.simple.pre=<b>&hl.simple.post=</b>&sort=score+desc";

    public function __construct($host = "", $port = "", $core = "") {
        global $cfg;
        if (!empty($host)) {
            $this->host = $host;
        } elseif (!empty($cfg['solr_host'])) {
            $this->host = $cfg['solr_host'];
        } else {
            throw new Exception("Solr host not specified in globals file or constructor!");
        }
        if (!empty($port)) {
            $this->port = $port;
        } elseif (!empty($cfg['solr_port'])) {
            $this->port = $cfg['solr_port'];
        } else {
            throw new Exception("Solr port not specified in globals file or constructor!");
        }
        if (!empty($core)) {
            $this->core = $core;
        } elseif (!empty($cfg['solr_core'])) {
            $this->core = $cfg['solr_core'];
        } else {
            $this->core = "";
        }
    }

    /**
     * Only searches for files where the username parameter appears in the filepath/name.
     *
     * @param string $query query string to search on.
     * @param string $username to restrict search results to.
     * @param int $start pagination offset.
     * @param int $rows pagination size.
     *
     * @uses Solr::search()
     *
     * @return array of search results
     */
    function search_user_files($query, $username, $rows=10, $start=0) {
         $query .= " AND resourcename_text:/$username/";
         return $this->search($query, $rows, $start);
    }


    /**
     * Searches Solr index for the supplied text.
     *
     * @param string $query query string to search on.
     * @param int $start pagination offset.
     * @param int $rows pagination size.
     *
     * @uses Solr::_search()
     *
     * @return array of search results
     */
    function search($query, $rows=10, $start=0) {

        return $this->_search($query, $this->search_format_string, $rows, $start);
    }


    /**
     * Searches Solr index for the supplied sha1 of a file.
     * Useful for seeing if the file currently exists in another path.
     *
     * @param string $query query string to search on.
     * @param int $start pagination offset.
     * @param int $rows pagination size.
     *
     * @uses Solr::_search()
     *
     * @return array of search results
     */
    function search_sha1($query, $rows=10, $start=0) {

        return $this->_search("sha1:$query", "&fl=id,resourcename,sha1,sha256", $rows, $start);
    }


    /**
     * Searches Solr index for the supplied sha256 of a file.
     * Useful for seeing if the file currently exists in another path.
     *
     * @param string $query query string to search on.
     * @param int $start pagination offset.
     * @param int $rows pagination size.
     *
     * @uses Solr::_search()
     *
     * @return array of search results
     */
    function search_sha256($query, $rows=10, $start=0) {

        return $this->_search("sha256:$query", "&fl=id,resourcename,sha1,sha256", $rows, $start);
    }

    /**
     * Returns the number of files indexed.
     *
     * @uses Solr::_search()
     *
     * @return array of search results
     */
    function count($query='*') {

        $q_string = "&fl=resourcename";
        return $this->_search($query, $q_string, 1, 0);
    }

    /**
     * Searches Solr index for the supplied text.
     *
     * @param string $query query string to search on.
     * @param int $start pagination offset.
     * @param int $rows pagination size.
     *
     * @uses Solr::checkURL()
     *
     * @return array of search results
     *
     * @access private
     */
    private function _search($query, $formatting, $rows=10, $start=0) {
        $server_path = "http://".$this->host.":".$this->port."/solr/".$this->core;
        if (!$this->checkURL($server_path."select")) {
            error_log("Solr Host is inaccessible $server_path");
            $_SESSION['feedback'] .= g_feedback("error", "Solr Host is inaccessible!");
            return false;
        }
        // dump_var($query);
        // search
        $result = "";
        // print_r("Querying: ".PHP_EOL.$server_path."select?rows=$rows&start=$start&q=".str_replace(" ", "+", $query));
        $code = file_get_contents($server_path."select?rows=$rows&start=$start&wt=php&q=".urlencode($query).$formatting);
        // print_r($code);
        eval("\$result = " . $code . ";");
        // error_log(print_r($result, true));

        if ($result['response']['numFound'] > 0) {
            $result_arrays = array();
            $result_objects = array();
            $results = $result['response']['docs'];
            // print_r($result).PHP_EOL;
            $i = 0;
            if (array_key_exists('highlighting', $result)) {
                foreach ($result['highlighting'] as $k=>$v) {
                    if (array_key_exists("content", $v)) {
                        $temp_array = array();
                        $temp_array = $results[$i];
                        if (array_key_exists("author", $results[$i]) === false) {
                            $temp_array['author'] = "";
                        }
                        $temp_array['filename'] = "";
                        $temp_array['row_number'] = $i;
                        $temp_array['content'] = $v['content'][0];
                        $temp_array['content_type'] = $results[$i]['content_type'][0];
                        if (strpos($temp_array['resourcename'], "/") !== false) {
                            $file_array = explode("/", $temp_array['resourcename']);
                            $temp_array['filename'] = end($file_array);
                        }
                        if (array_key_exists('id', $result)) $temp_array['id'] = $results[$i]['id'];
                        if (array_key_exists('sha1', $result)) $temp_array['sha1'] = $results[$i]['sha1'];
                        if (array_key_exists('sha256', $result)) $temp_array['sha256'] = $results[$i]['sha256'];
                        $temp_object = new stdClass();
                        array2object(array($temp_array), $temp_object);
                        $result_arrays[$i] = $temp_array;
                        $result_objects[$i] = $temp_object;
                        // $results[$i]['row_number'] = $i;
                        // $results[$i]['content'] = $v['content'][0];
                        // $results[$i]['content_type'] = $results[$i]['content_type'][0];
                    } else {
                        $temp_array = array();
                        $temp_array[] = array('resourcename' => $k);
                        if (array_key_exists('id', $result)) $temp_array['id'] = $results[$i]['id'];
                        if (array_key_exists('sha1', $result)) $temp_array['sha1'] = $results[$i]['sha1'];
                        if (array_key_exists('sha256', $result)) $temp_array['sha256'] = $results[$i]['sha256'];
                        $temp_object = new stdClass();
                        array2object(array($temp_array), $temp_object);
                        $result_arrays[$i] = $temp_array;
                        $result_objects[$i] = $temp_object;
                    }
                    $i++;
                }
            } elseif (array_key_exists('resourcename', $results)) {
                foreach ($result['resourcename'] as $k=>$v) {
                    $temp_array = array();
                    $temp_array[] = array('resourcename' => $k);
                    if (array_key_exists('id', $result)) $temp_array['id'] = $results[$i]['id'];
                    if (array_key_exists('sha1', $result)) $temp_array['sha1'] = $results[$i]['sha1'];
                    if (array_key_exists('sha256', $result)) $temp_array['sha256'] = $results[$i]['sha256'];
                    $temp_object = new stdClass();
                    array2object(array($temp_array), $temp_object);
                    $result_arrays[$i] = $temp_array;
                    $result_objects[$i] = $temp_object;
                    $i++;
                }
            } else {
                foreach ($results as $r) {
                    if (array_key_exists('resourcename', $r)) {
                        $temp_array = array();
                        $temp_array = array('resourcename' => $r['resourcename']);
                        if (array_key_exists('id', $r)) $temp_array['id'] = $r['id'];
                        if (array_key_exists('sha1', $r)) $temp_array['sha1'] = $r['sha1'];
                        if (array_key_exists('sha256', $r)) $temp_array['sha256'] = $r['sha256'];
                        $temp_object = new stdClass();
                        array2object(array($temp_array), $temp_object);
                        $result_arrays[] = $temp_array;
                        $result_objects[] = $temp_object;
                    }
                }
            }
            if (empty($result_arrays)) {
                return array('total' => $result['response']['numFound']);
            }
            return array('total' => $result['response']['numFound'], 'result_arrays' => $result_arrays, 'result_objects' => $result_objects);
        } else {
            return false;
        }
    }

    /**
     * Returns an array of filenames for all indexed files.
     *
     * @return array of filenames
     *
     * @uses Solr::count(), Solr::_search()
     */
    function get_all_filenames() {
        $num=$this->count();
        if (!isset($num['total']) || is_null($num['total']) || $num['total']==0) {
            return false;
        }
        $q_string = "&fl=resourcename";
        // print_r($num['total']);
        $results=$this->_search("*", $q_string, $num['total'], 0);
        $return_array=array();
        foreach ($results['result_arrays'] as $r) {
            $return_array[] = $r['resourcename'];
        }
        return $return_array;
    }

    /**
     * Returns an array of ids matching the exact filepath (should only be one).
     *
     * @return array of ids
     *
     * @uses Solr::count(), Solr::_search()
     */
    function get_id_by_filename($filename_with_full_path) {
        $searchterm="{!term f=resourcename}$filename_with_full_path";
        $num=$this->count($searchterm);
        if (!isset($num['total']) || is_null($num['total']) || $num['total']==0) {
            return false;
        }
        $q_string = "&fl=id,resourcename";
        // print_r($num['total']);
        $results=$this->_search($searchterm, $q_string, $num['total'], 0);
        $return_array=array();
        foreach ($results['result_arrays'] as $r) {
            $return_array[] = $r['id'];
        }
        return $return_array;
    }

    /**
     * Returns an associative array of SHA1 hashes for all indexed files using the filename as the key.
     *
     * @return array of hashes
     *
     * @uses Solr::count(), Solr::_search()
     */
    function get_all_sha1() {
        $num=$this->count();
        if (!isset($num['total']) || is_null($num['total']) || $num['total']==0) {
            return false;
        }
        $q_string = "&fl=resourcename,sha1";
        // print_r($num['total']);
        $results=$this->_search("*", $q_string, $num['total'], 0);
        $return_array=array();
        foreach ($results['result_arrays'] as $r) {
            $return_array[$r['resourcename']] = $r['sha1'];
        }
        return $return_array;
    }

    /**
     * Returns an associative array of SHA256 hashes for all indexed files using the filename as the key.
     *
     * @return array of hashes
     *
     * @uses Solr::count(), Solr::_search()
     */
    function get_all_sha256() {
        $num=$this->count();
        if (!isset($num['total']) || is_null($num['total']) || $num['total']==0) {
            return false;
        }
        $q_string = "&fl=resourcename,sha256";
        // print_r($num['total']);
        $results=$this->_search("*", $q_string, $num['total'], 0);
        $return_array=array();
        foreach ($results['result_arrays'] as $r) {
            $return_array[$r['resourcename']] = $r['sha256'];
        }
        return $return_array;
    }

    /**
     * Deletes the Solr Index for the value passed.
     *
     * @param string $query query string for deletion.
     *
     * @uses Solr::checkURL()
     *
     * @return boolean
     *
     * @access private
     */
    private function _delete($query)  {
        $server_path = "http://".$this->host.":".$this->port."/solr/".$this->core;
        if (!$this->checkURL($server_path."select")) {
            error_log("Solr Host is inaccessible $server_path");
            $_SESSION['feedback'] .= g_feedback("error", "Solr Host is inaccessible!");
            return false;
        }

        $query_array = array();
        $query_array['stream.body'] = "<delete>$query</delete>";
        $query_array['commit'] = "true";
        //delete all
        //http://localhost:8080/solr/your_solr_admin/update?stream.body= <delete><query>*:*</query></delete>&commit=true
        //delete specific
        //http://localhost:8080/solr/your_solr_admin/update?stream.body= <delete><query>id:1</query></delete>&commit=true
        // curl http://46.231.77.98:7979/solr/update/?commit=true -H "Content-Type: text/xml" -d "<delete>(cartype:stationwagon)AND(color:blue)</delete>"

        $ch = curl_init($server_path."update?".http_build_query($query_array));
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $source = curl_exec($ch);
        if(curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        } else {
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $details = curl_getinfo($ch);
            curl_close($ch);
            if ($status != 200) {
                // error_log("Get Page source error: ".$this->getLocation());
                error_log("status : $status");
                error_log("Details: ".print_r($details, true));
                error_log("Body: ".print_r($source, true));
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Deletes all Solr Indexes.
     *
     * @uses Solr::_delete()
     *
     * @return boolean
     */
    function delete_all() {
        return $this->_delete("<query>*:*</query>");
    }

    /**
     * Deletes specific Solr Index based on id.
     *
     * @param string $id index to delete - typically filename with path.
     *
     * @uses Solr::_delete()
     *
     * @return boolean
     */
    function delete_id($id) {
//        echo "Removing index for id:$id".PHP_EOL;
        return $this->_delete("<id>$id</id>");
    }

    /**
     * Deletes specific Solr Index based on resourcename.
     *
     * @param string $id index to delete - typically filename with path.
     *
     * @uses Solr::_delete()
     *
     * @return boolean
     */
    function delete_resourcename($resourcename) {
//        echo "Removing index for resourcename:$resourcename".PHP_EOL;
        $id_array=$this->get_id_by_filename($resourcename);
        if (count($id_array) != 1) {
            echo "Resulting ID array has incorrect number of elements!".PHP_EOL;
            print_r($id_array);
            echo PHP_EOL;
            return null;
        }
        return $this->delete_id($id_array[0]);
    }


    /**
     * Deletes Collection of Solr Indexes based on ids.
     *
     * @param array/RecursiveIteratorIterator $ids indexes to delete - typically filenames with path.
     *
     * @uses Solr::_delete()
     *
     * @return boolean
     */
    function delete_ids($ids) {
        if (gettype($ids) == "array") {
            foreach ($ids as $file) {
                return $this->delete_id($file);
            }
        } elseif (gettype($ids) == "object") {
            foreach ($ids as $file => $metadata) {
                return $this->delete_id($file);
            }
        } elseif (gettype($ids) == "string") {
            return $this->delete_id($ids);
        } else {
            error_log(__CLASS__."::".__FUNCTION__." called with invalid type, path: ".print_r($ids, true)." type: ".gettype($ids));
        }
    }


    /**
     * Deletes Collection of Solr Indexes based on resourcenames.
     *
     * @param array/RecursiveIteratorIterator $ids indexes to delete - typically filenames with path.
     *
     * @uses Solr::_delete()
     *
     * @return boolean
     */
    function delete_resourcenames($resourcenames) {
        if (gettype($resourcenames) == "array") {
            foreach ($resourcenames as $file) {
                return $this->delete_resourcename($file);
            }
        } elseif (gettype($resourcenames) == "object") {
            foreach ($resourcenames as $file => $metadata) {
                return $this->delete_resourcename($file);
            }
        } elseif (gettype($resourcenames) == "string") {
            return $this->delete_resourcename($resourcenames);
        } else {
            error_log(__CLASS__."::".__FUNCTION__." called with invalid type, path: ".print_r($resourcenames, true)." type: ".gettype($resourcenames));
        }
    }

    /**
     * Updates the Solr Index from the value passed.
     * Can pass a file path, directory path, array of file, or RecursiveIteratorIterator (useful for cron)
     * example RecursiveIteratorIterator:
     * $directory_iterator=new RecursiveDirectoryIterator($local_path);
     * $objects=new RecursiveIteratorIterator($directory_iterator);
     * $solr->update($objects);
     *
     * @param string/array/RecursiveIteratorIterator $path the location to index.
     *
     * @uses Solr::checkURL(), Solr::_post_file(), Solr::_finalise_update()
     *
     * @return boolean
     *
     * @access public
     */
    function update($path) {
        $server_path = "http://".$this->host.":".$this->port."/solr/".$this->core;
        if (!$this->checkURL($server_path."select")) {
            error_log("Solr Host is inaccessible $server_path");
            $_SESSION['feedback'] .= g_feedback("error", "Solr Host is inaccessible!");
            return false;
        }

        if (gettype($path) == "array") {
            foreach ($path as $file) {
                $this->_post_file($server_path, $file);
            }
        } elseif (gettype($path) == "object") {
            foreach ($path as $file => $metadata) {
                $this->_post_file($server_path, $file);
            }
        } elseif (gettype($path) == "string") {
            $files = array();
            if (is_dir($path)) {
                if ($handle = opendir($path)) {
                    while (false !== ($entry = readdir($handle))) {
                        if ($entry != "." && $entry != "..") {
                            $files[] = $path.$entry;
                        }
                    }
                    closedir($handle);
                }
            } else {
                $files[] = $path;
            }
            foreach ($files as $file) {
                 $this->_post_file($server_path, $file);
            }
        } else {
            error_log(__CLASS__."::".__FUNCTION__." called with invalid type, supplied variable is none of array, object (RecursiveIteratorIterator), string - path: ".print_r($path, true)." type: ".gettype($path));
        }

        return $this->_finalise_update($server_path);
    }

    /**
     * Finalises/Commits the update.  More efficient to call at the end of a batch of updates.
     *
     * @param string $server_path endpoint url.
     *
     * @return boolean
     *
     * @access private
     */
    private function _finalise_update($server_path) {
        $ch = curl_init($server_path."update?commit=true");
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_NOBODY, 1);
        $source = curl_exec($ch);
        if(curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        } else {
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $details = curl_getinfo($ch);
            curl_close($ch);
            if ($status != 200) {
                // error_log("Get Page source error: ".$this->getLocation());
                error_log("status : $status");
                error_log("Details: ".print_r($details, true));
                error_log("Body: ".print_r($source, true));
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Calls the update endpoint to update Solr index for file.
     *
     * @param string $server_path endpoint url.
     * @param string $file name and full path of file to index.
     *
     * @return boolean
     *
     * @access private
     */
    private function _post_file($server_path, $file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        // echo finfo_file($finfo, $file)." - ".$file.PHP_EOL;
        $sha1=sha1_file($file);
        $sha256=hash_file('sha256', $file);
        $post_string = file_get_contents($file);
        // $escaped_file = str_replace(" ", "+", $file);
        $escaped_file = urlencode($file);
        $file_parts = explode("/", $file);
        $filename = end($file_parts);
//        echo "Updating index for $file".PHP_EOL;
        $success=false;
        $loops=0;
        while ($success === false && $loops <= 1) {
            $query_array = array();
            if ($loops == 1) {
                echo "failed using supplied path of file as id, unsetting and trying again.";
            } else {
                $query_array['literal.id'] = $file;
            }
            $query_array['literal.resourcename'] = $file;
            $query_array['literal.sha1'] = $sha1;
            $query_array['literal.sha256'] = $sha256;
            $query_array['stream.file'] = $file;
            $url = $server_path."update/extract?".http_build_query($query_array);
//            echo "URL: $url".PHP_EOL;
            $ch = curl_init($url); // needing - literal.id=[UNIQUE ID]&

    //            curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: ".mime_content_type($file)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
            // curl_setopt($ch, CURLOPT_NOBODY, 1);

            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                echo "Non 200 status: $status".PHP_EOL;
                echo "URL: $url".PHP_EOL;
                echo print_r($data, true).PHP_EOL;
                curl_close($ch);
                finfo_close($finfo);
                $success=false;
            } elseif (curl_errno($ch)) {
                echo 'Curl error: ' . curl_error($ch);
                echo "URL: $url".PHP_EOL;
                echo print_r($data, true).PHP_EOL;
                curl_close($ch);
                finfo_close($finfo);
                $success=false;
            } else {
                curl_close($ch);
//                print "curl exited okay\n";
//                echo "Data returned...\n";
//                echo "------------------------------------\n";
//                echo $data;
//                echo "------------------------------------\n";
                $success=true;
            }
            finfo_close($finfo);
            $loops += 1;
        }
        return $success;
    }

    /**
     * Checks to see if the supplied url is accessible without errors.
     *
     * @param string $url url to check.
     *
     * @return boolean
     *
     * @access public
     */
    function checkURL($url) {

        $url = @parse_url($url);
        if (!$url)
        {
            return false;
        }
        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
        $path = (isset($url['path'])) ? $url['path'] : '';
        if ($path == '')
        {
            $path = '/';
        }
        $path .= (isset($url['query'])) ?  "?$url[query] " : '';
        if (isset($url['host']) AND $url['host'] != gethostbyname($url['host']))
        {
            $headers = @get_headers( "$url[scheme]://$url[host]:$url[port]$path ");
            $headers = (is_array($headers)) ? implode( "\n ", $headers) : $headers;
            if(preg_match("#^HTTP/.*\s+[(200|301|302)]+\s#i", $headers)){
                return true;
            }
        }
        return false;
    }
}
