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
 * Created to abstract boiler plate need to make cron scripts DB aware.
 *
 * All that is needed is to extend this class and create the function
 * 'proccess' in the extending class (stub methond at bottom of this class),
 * then in you cli script create an instance of your new cron class,
 * call set_directories and pass the directory names to be read from / written
 * to as an array e.g. incoming / export / etc then call the 'run' function.
 *
 */
Abstract Class Cron {
    protected $directories = array();
    private $use_resumable_lock_file = false; //USE WITH EXTREME CARE

    /*
     * Sets the use_resumable_lock_file value.
     * Use with care and make sure you've read the comment for the function
     * create_resumable_lock_file.
     *
     * @param boolean $value TRUE/FALSE.
     */
    public function set_use_resumable_lock_file($value) {
        $this->use_resumable_lock_file = $value;
    }

    /*
     * Creates the lock file preventing further execution until it is removed.
     *
     * @param string $file_name name of lockfile (without the extension).
     * @param string $additional additional text to concatente to file_name with '_' e.g. trustname.
     */
    public final function create_lock_file($file_name, $additional='') {
        if (empty($file_name)) throw new Exception('Lock file_name cannot be empty.');

        if (!empty($additional)) $file_name .= "_$additional";

        $lock_file = sys_get_temp_dir(). '/' . $file_name.'.lock';
        $lock_file_handle = fopen($lock_file, "x");
        if ($lock_file_handle === FALSE) {
            $lock_file_handle = fopen($lock_file, "r");
            $created_date = fread($lock_file_handle,filesize($lock_file));
            fclose($lock_file_handle);
            throw new Exception("Lock file '$lock_file' exists: created at $created_date (Current time - " .date('Y-m-d H:i:s'). ") ... aborting.");
        } else {
            fwrite($lock_file_handle, date('Y-m-d H:i:s'));
            fclose($lock_file_handle);
        }

    }

    /*
     * Creates the lock file preventing further execution until it is removed.
     *
     * However if the lock file creation date is > max_excution_time this resets
     * the creation time and continues normally (assuming Fataled due to exceeding
     * max_execution_time limit), unless max_excution_time returns zero.
     *
     * This creates a secondary lock file to ensure no race conditions are exposed
     * when dealing with the logic to descide if the creation date is > max_excution_time
     * and removes the secondary lock file when the method is complete
     *
     * USE WITH CAUTION.
     *
     * @param string $file_name name of lockfile (without the extension).
     * @param string $additional additional text to concatente to file_name with '_' e.g. trustname.
     */
    public final function create_resumable_lock_file($file_name, $additional='') {
        if (empty($file_name)) throw new Exception('Lock file_name cannot be empty.');

        if (!empty($additional)) $file_name .= "_$additional";

        $lock_file = sys_get_temp_dir(). '/' . $file_name.'.lock';
        $secondary_lock_file = sys_get_temp_dir(). '/' . $file_name.'.lock.tmp';

        $lock_file_handle = fopen($lock_file, "x");
        $secondary_lock_file_handle = fopen($secondary_lock_file, "x");

        // Check for presence of secondary lock file first
        if ($secondary_lock_file_handle === FALSE)
        {
            fclose($lock_file_handle);
            fclose($secondary_lock_file_handle);
            throw new Exception("Resumable Lock file '$lock_file' exists: created at '$created_date' (Current time - " .date('Y-m-d H:i:s'). ") ... aborting.");

        // Check for presence of main lock file
        } elseif ($lock_file_handle === FALSE) {
            $lock_file_handle = fopen($lock_file, "r");
            $created_date = fread($lock_file_handle,filesize($lock_file));
            $date1 = date_create($created_date);
            $now = date_create(date('Y-m-d H:i:s'));
            $diff = date_diff($date1, $now);
            $max_execution = (int) ini_get('max_execution');

            // if created time of main lock file > max_execution_time and max_execution_time != 0
            // Rewrite the created time and continue normally (assume Fataled due to exceeding max_execution_time limit)
            if ($max_execution != '0'
                && ($diff->format('%h') * 3600) + ($diff->format('%i') * 60) + $diff->format('%s') > $max_execution)
            {
                error_log("Lock file '$lock_file' creation date '$created_date' exceeded max execution time {$max_execution}s... recreating and continuing.");
                fclose($lock_file_handle);
                $lock_file_handle = fopen($lock_file, "w");
                fwrite($lock_file_handle, $now->format('Y-m-d H:i:s'));
                fclose($lock_file_handle);
                fclose($secondary_lock_file_handle);
                unlink($secondary_lock_file);
            } else {
                fclose($lock_file_handle);
                fclose($secondary_lock_file_handle);
                unlink($secondary_lock_file);
                throw new Exception("Lock file '$lock_file' exists: created at '$created_date' (Current time - " .date('Y-m-d H:i:s'). ") ... aborting.");
            }
        } else {
            fwrite($lock_file_handle, date('Y-m-d H:i:s'));
            fclose($lock_file_handle);
            fclose($secondary_lock_file_handle);
            unlink($secondary_lock_file);
        }

    }


    /*
     * Removes the lock file (when execution completes).
     *
     * @param string $file_name name of lockfile (without the extension).
     * @param string $additional additional text to concatente to file_name with '_' e.g. trustname.
     */
    public final function remove_lock_file($file_name, $additional='') {
        if (empty($file_name)) throw new Exception('Lock file_name cannot be empty.');

        if (!empty($additional)) $file_name .= "_$additional";

        unlink(sys_get_temp_dir(). '/' . $file_name.'.lock');
    }

    /*
     * Takes array of strings for subdirectories without slash.
     * Converts to an indexed array for easy use within 'main' function
     */
    public function set_directories(array $directories) {
        $keys = $directories;
        $this->directories = array_combine($keys, $directories);
    }

    /*
     * used by array_walk to prepend the full path to the requested directory
     */
    private function add_leading_path(&$directory, $key, $prefix) {
        $directory = "$prefix$directory/";
    }

    /*
     * DB switch / trust aware boiler plate function to be executed by cron script.
     * Passes correct DB connection and Directories to 'proccess' function
     *
     * DO NOT OVERRIDE THIS FUNCTION IN EXTENDING CLASS
     * INSTEAD PUT NEEDED FUNCTIONALITY IN proccess
     * METHOD IN EXTENDING CLASS AND CALL RUN FROM
     * cron file.
     *
     * requires: 'proccess' function (in extending class).
     *
     * @param string $trust optional name of trust directory to restrict to specific trust.
     *
     * If $trust is specified you MUST specify it for all identical cron jobs
     * or you will introduce race conditions/
     */
    public final function run($trust = '') {
        global $cfg, $db;

        // Create lock file using name of Extending class to
        // prevent any race conditions.
        if ($this->use_resumable_lock_file === TRUE) {
            $this->create_resumable_lock_file(get_class($this), $trust);
        } else {
            $this->create_lock_file(get_class($this), $trust);
        }

        try {
            // various folder checks
            if (empty($cfg["hl7_folder"])){
                throw new Exception("HL7 directory is missing from the application configuration file");
            }

            if (empty($this->directories)) {
                throw new Exception("No directory specified.  Have you forgotten to call set_directories(array $directories)?");
            }

            $hl7_base_dir = $cfg["hl7_folder"];
            $hl7_dirs = array_diff(scandir($hl7_base_dir), array('..', '.'));

            // Trust specific
            if (!empty($trust)) {

                $directories = $this->directories;
                array_walk($directories, array($this, 'add_leading_path'), $hl7_base_dir . $trust . '/');

                foreach ($directories as $key => $dir) {
                    // various folder checks
                    if (!is_dir($dir)){
                        error_log("$dir is not a directory...skipping '$hl7_base_dir$trust/'");
                        unset($directories[$key]);
                        continue; // skips this loop and continues from start of next iteration of outer loop.
                    }
                    if (!is_writable($dir)){
                        error_log("$dir is not writable...skipping '$hl7_base_dir$trust/'");
                        unset($directories[$key]);
                        continue; // skips this loop and continues from start of next iteration of outer loop.
                    }
                }

                try {
                    if (empty($directories)) throw new Exception("No directories to write to...aborting.");

                    // attempt to instantiate DB based on dir.
                    $db = DB2_Factory::factory($trust);
                    $this->process($db, $directories);

                } catch (Exception $e) {
                    error_log("[$trust] Caught Exception: ". $e->getMessage());
                }

            // Generic parse all trusts
            } else {

                foreach ($hl7_dirs as $hl7_dir) {
                    $directory_errors = false;
                    if (is_dir($hl7_base_dir .$hl7_dir)) {
                        $directories = $this->directories;
                        array_walk($directories, array($this, 'add_leading_path'), $hl7_base_dir . $hl7_dir . '/');

                        foreach ($directories as $dir) {
                            // various folder checks
                            if (!is_dir($dir)){
                                error_log("$dir is not a directory...skipping '$hl7_base_dir$hl7_dir/'");
                                continue 2; // skips this loop and continues from start of next iteration of outer loop.
                            }
                            if (!is_writable($dir)){
                                error_log("$dir is not writable...skipping '$hl7_base_dir$hl7_dir/'");
                                continue 2; // skips this loop and continues from start of next iteration of outer loop.
                            }
                        }

                        try {

                            // attempt to instantiate DB based on dir.
                            $db = DB2_Factory::factory($hl7_dir);
                            $this->process($db, $directories);

                        } catch (Exception $e) {
                            error_log("[$hl7_dir] Caught Exception: ". $e->getMessage());
                        }
                    }
                }
            }

        } catch (Exception $e) {
            error_log("Caught Exception: ". $e->getMessage());
        } finally {
            // Finished processing remove lock file.
            // finally was added in php 5.5
            $this->remove_lock_file(get_class($this), $trust);
        }
    }

    /*
     * Type-defined function stub to be overridden in custom class.
     *
     * If needing to use $db the correct connection is passed into
     * this function from run and can be used as $db calls or
     * object->db_operation calls inside this function.
     *
     * $directories is an associatice array of directories indexed by
     * the values passed to set_directories, i.e.
     * set_directories(array('export')) becomes
     * array('export' => '/correct/path/to/use/for/this/db/trust/export/')
     */
    function process($db, array $directories) {
        global $cfg, $db;
        // my custom stuff with $db / object / $directories
    }
}
