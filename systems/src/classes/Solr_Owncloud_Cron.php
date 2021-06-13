<?php

require_once __DIR__.DIRECTORY_SEPARATOR."../config/global.php";

Class Solr_Owncloud_Cron {

    private $LOCK_FILE = "/tmp/cron_solr_update.lock";
    private $FILE_BASEPATH = "";

    function __construct() {
        global $cfg;
        if (!isset($cfg['use_solr']) || $cfg['use_solr'] !== true) {
            echo "use_solr not set (or set to false) in Riskpoint config...aborting.".PHP_EOL;
            die;
        }
        if (!isset($cfg['owncloud_filesystem']) || empty($cfg['owncloud_filesystem'])) {
            echo "owncloud_filesystem not set in Riskpoint config...aborting.".PHP_EOL;
            die;
        }
        $this->FILE_BASEPATH = $cfg['owncloud_filesystem'];
    }

    /**
     * Creates the lock file or exits on error to prevent race conditions if another thread tries to start.
     *
     * @access private
     */
    private function write_lock_file() {
        if (file_exists($this->LOCK_FILE)) {
            echo "Found lock file ... aborting".PHP_EOL;
            readfile($this->LOCK_FILE);
            echo PHP_EOL;
            echo "Current time: ".date('Y-m-d H:i:s').PHP_EOL;
            die();
        }
        $fh = fopen($this->LOCK_FILE, 'w') or die("Can't create lock file ".$this->LOCK_FILE.PHP_EOL);
        $stringData = "Started at: ".date('Y-m-d H:i:s');
        fwrite($fh, $stringData);
        fclose($fh);
    }

    /**
     * Removed the lock file once processing has finished.
     *
     * @access private
     */
    private function remove_lock_file() {
        unlink($this->LOCK_FILE);
    }

    /**
     * Updates the Solr Indexes removing those in Wastbasket and adding/updating new/changed files.
     *
     * @uses Solr_Owncloud_Cron::write_lock_file(), Solr::get_all_sha256(), Solr::delete_ids(), Solr::update()Solr_Owncloud_Cron::remove_lock_file()
     */
    function run() {
        $this->write_lock_file();

        $update_path=$this->FILE_BASEPATH.'*/files';
        $update_paths=glob($update_path);

        $solr=new Solr();

        $all=array();
        $all=$solr->get_all_sha256();
        if ($all === false) {
            $all=array();
        }

        // Deleted files i.e. no longer on filesystem.
        // Could do the active to wastebasketed translation to MAKE SURE file no longer exists but if we call it
        // after the wastbasketed function there really is no need.
        foreach (array_keys($all) as $file) {
            if (!file_exists($file)) {
                $solr->delete_resourcenames($file);
            }
        }

        // New/Changed files
        foreach ($update_paths as $path) {
            $directory_iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS);
            $filter = new SO_NewChangeFilesFilterIterator($directory_iterator, $all);
            $objects = new \RecursiveIteratorIterator($filter);
            $solr->update($objects);
        }

        $this->remove_lock_file();
    }
}


/**
 * RecursiveFilterIterator to filter files from the RecursiveDirectoryIterator to only allow
 * files where the file is not in an exclusion list and the checksums do not match.
 *
 * Can handle:
 *     Flat array of filenames with full path
 *     Associative array of SHA1 hashes where the keys are the filenames with full path.
 *     Associative array of SHA256 hashes where the keys are the filenames with full path.
 */
class SO_NewChangeFilesFilterIterator extends \RecursiveFilterIterator {

    private $exclude_files=array();
    private $associative_array=false;
    private $excluded_file_extensions=array('jpg','jpeg','png','gz','tgz','bz','html','mp3','mov', 'xml');
    function __construct(RecursiveIterator $iterator, array $file_exclusion_list = array()) {
        parent::__construct($iterator);
        $this->exclude_files = $file_exclusion_list;
        if ($this->is_assoc($this->exclude_files)) $this->associative_array=true;
    }

    /**
     * Overrides RecursiveFilterIterator::accept() in order to query the filename against the
     * list of list of files supplied in the exclusion array.
     *
     * @return boolean returns true if filename in iterator is missing from exclusion list or
     * the hash does not match
     */
    public function accept() {
        $filename = $this->current()->getFilename();
        $fullpath = $this->current()->getPath().DIRECTORY_SEPARATOR.$filename;
        // Skip hidden files and directories.
        if ($filename[0] === '.') {
            return FALSE;
        }
        if (!$this->isDir()) {
            if (in_array(strtolower($this->current()->getExtension()), $this->excluded_file_extensions)) return false;
            if ($this->associative_array) {
                if (strlen(current($this->exclude_files)) == 40) { // assuming SHA1
                    $sha=sha1_file($fullpath);
                    $result = (!in_array($fullpath, array_keys($this->exclude_files)) || $sha != $this->exclude_files[$fullpath]);
                    if ($result === true) {
//                        echo "Comparing SHA1s for file $fullpath: current file: $sha";
                        if (in_array($fullpath, array_keys($this->exclude_files))) echo " indexed file: ".$this->exclude_files[$fullpath].PHP_EOL;
//                        echo "Returning: ".print_r($result, true).PHP_EOL;
                    }
                    return $result;
                } elseif (strlen(current($this->exclude_files)) == 64) { // assuming SHA256
                    $sha=hash_file('sha256', $fullpath);
                    $result = (!in_array($fullpath, array_keys($this->exclude_files)) || $sha != $this->exclude_files[$fullpath]);
                    if ($result === true) {
//                        echo "Comparing SHA256s for file $fullpath: current file: $sha";
                        if (in_array($fullpath, array_keys($this->exclude_files))) echo " indexed file: ".$this->exclude_files[$fullpath].PHP_EOL;
//                        echo "Returning: ".print_r($result, true).PHP_EOL;
                    }
                    return $result;
                } else { //unknown
//                    echo "Unknown algorithm for file $fullpath: strlen: ".strlen(current($this->exclude_files))." hash: ".$this->exclude_files[$fullpath].PHP_EOL;
                    return false;
                }
            }
            return !in_array($fullpath, $this->exclude_files);
        } else {
            return true;
        }
    }

    /**
     * Overrides RecursiveFilterIterator::getChildren() in order to pass valid paramters to
     * constructor when traversing directories.
     *
     * @return SO_NewChangeFilesFilterIterator
     */
    public function getChildren() {
        return new self($this->getInnerIterator()->getChildren(), $this->exclude_files);
    }

    /**
     * Returns if the supplied array is a normal array or associative array.
     *
     * Works by checking if all array keys are integer values (normal) or not
     * (associative).
     *
     * @param array $array
     *
     * @return boolean
     */
    function is_assoc($array) {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

}
