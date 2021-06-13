<?php
/**
 * Used for for all encryption and decryption within the system.
 *
 * PHP Version 5
 *
 * @package    Riskpoint/Core
 * @author     Alan Campbell <alan.campbell@riskpoint.co.uk>
 * @author     Alasdair Campbell <alasdair.campbell@riskpoint.co.uk>
 * @copyright  Copyright (c) 2008-2013 Riskpoint Limited
 * @license    http://opensource.org/licenses/MIT MIT Open-Source license
 * @version    SVN: $Id:$
 * @link       http://www.riskpoint.co.uk
 */

/**
 * Handles all encryption and decryption within the system.
 *
 * @package    Riskpoint/Core
 * @author     Alan Campbell <alan.campbell@riskpoint.co.uk>
 * @author     Alasdair Campbell <alasdair.campbell@riskpoint.co.uk>
 * @copyright  Copyright (c) 2008-2013 Riskpoint Limited
 * @license    http://opensource.org/licenses/MIT MIT Open-Source license
 * @version    Release: @package_version@
 * @link       http://www.riskpoint.co.uk
 */
class Crypt {

    /**
     * The encryption algorithm to use.
     * @var string
     */
    private $algorithm;
    /**
     * The encryption mode to use
     *
     * Potential values are 'cbc', 'cfb', 'ctr', 'ecb', 'ncfb', 'nofb', 'ofb' and 'stream'.
     * @var string
     */
    private $mode;
    /**
     * The source to use for randomness.
     *
     * Potential values are 'MCRYPT_RAND', 'MCRYPT_DEV_RANDOM' and 'MCRYPT_DEV_URANDOM'.
     * @var string
     */
    private $random_source;
    /**
     * The text to pad out the string to make sure is of acceptable length.
     *
     * Amongst other things combats Rainbow Table attacks on the crypted/hashed data.
     * @var string
     */
    private $salt;
    /**
     * Flag to check if string has been mangled by a third party.
     * @var boolean
     */
    private $detect_mangle;
    /**
     * Flag to state if encrypted strings should be compressed.
     * @var boolean
     * @deprecated set in contructor but longer has any effect
     */
    private $compression;
    /**
     * Array to store the available algorithms set from within the constructor.
     * @var boolean
     */
    private $hash_algorithms;
    /**
     * The raw text to encrypt.
     * @var string
     */
    public $cleartext;
    /**
     * The encrypted text to decrypt.
     * @var string
     */
    public $ciphertext;
    /**
     * Creates an initialization vector (IV) from a random source.
     * @var string
     */
    public $iv;

    private $intrusion_log;

    /**
     * Initialises class variables if passed in or uses config settings.
     *
     * @param string $algorithm One of the OPENSSL_ciphername constants
     * @param string $mode an MCRYPT_MODE_modename constant - NO LONGER NEEDED only used for MCrypt (FUTURE CLEANUP)
     * @param string $salt string for padding
     * @param string $random source of randomness - NO LONGER NEEDED only used for MCrypt (FUTURE CLEANUP)
     * @param boolean $detect_mangle prepend HMAC to all crypted strings
     * @param string $compression compression mode to use
     */
    public function __construct($algorithm = "", $mode = "", $salt="", $random="", $detect_mangle=true, $compression="none"){
        global $cfg, $intrusion_log;

        $this->intrusion_log = $intrusion_log;
        $this->compression = (empty($compression)) ? "none" : $this->compression=$compression;
        $this->algorithm = (empty($algorithm)) ? $cfg['openssl_crypt_algorithm'] : $this->algorithm=$algorithm;

        $supported_algorithms = openssl_get_cipher_methods(true);
        if (!in_array($this->algorithm, $supported_algorithms)) {
            $e_message = "Crypt algorithm supplied '".$this->algorithm."' is not supported.".PHP_EOL;
            $e_message .= "Supported algorithms are available through the method openssl_get_cipher_methods(true)";
            throw new Exception($e_message);
        }

        $this->salt = (empty($salt)) ? $cfg['md5_salt'] : $this->salt = $salt;
        $this->detect_mangle = $detect_mangle;
        $this->iv_size = openssl_cipher_iv_length($this->algorithm);
        $this->iv = openssl_random_pseudo_bytes($this->iv_size);

        $this->hash_algorithms = array(
            'md4'=>32,
            'md5'=>32,
            'sha1'=>40,
            'sha256'=>64,
            'sha384'=>96,
            'sha512'=>128,
            'ripemd128'=>32,
            'ripemd160'=>40,
            'whirlpool'=>128,
            'tiger128,3'=>32,
            'tiger160,3'=>40,
            'tiger192,3'=>48,
            'tiger128,4'=>32,
            'tiger160,4'=>40,
            'tiger192,4'=>48,
            'snefru'=>64,
            'gost'=>64,
            'adler32'=>8,
            'crc32'=>8,
            'crc32b'=>8,
            'haval128,3'=>32,
            'haval160,3'=>40,
            'haval192,3'=>48,
            'haval224,3'=>56,
            'haval256,3'=>64,
            'haval128,4'=>32,
            'haval160,4'=>40,
            'haval192,4'=>48,
            'haval224,4'=>56,
            'haval256,4'=>64,
            'haval128,5'=>32,
            'haval160,5'=>40,
            'haval192,5'=>48,
            'haval224,5'=>56,
            'haval256,5'=>64,
        );
    }

    /**
     * Encrypts the supplied string.
     *
     * @param string $str the string to encrypt.
     *
     * @return string
     */
    public function str_encrypt($str=""){
        $this->cleartext = $str;
        $this->ciphertext = openssl_encrypt($this->cleartext, $this->algorithm, $this->salt, null, $this->iv);
        $msg = $this->iv . $this->ciphertext;
        if ($this->detect_mangle) $msg = $this->hash_crypt($msg) . $msg;
        $msg = strtr(base64_encode($msg), array("+"=>"-","/"=>"_","="=>""));
        return $msg;
    }

    /**
     * Decrypts the supplied string.
     *
     * @param string $str the string to decrypt.
     *
     * @return string
     */
    public function str_decrypt($str=""){
        $base64_encoded=TRUE;
        if (!empty($str)){
            $untouched_str = $str;
            if ($this->detect_mangle) {
                //HASH
                $passed_check = $this->hash_check($str, TRUE);
                if ($passed_check !== TRUE) {
                    return $passed_check;
                }
                $base64_encoded = FALSE;
            } else {
                if ($base64_encoded === TRUE) $str = base64_decode(strtr($str, '-_', '+/'));
            }
            $this->ciphertext = substr($str,$this->iv_size);
            $iv = substr($str,0,$this->iv_size);
            $this->cleartext = openssl_decrypt($this->ciphertext, $this->algorithm, $this->salt, null, $iv);

            if($this->compression == "bzip") {
                $this->cleartext = bzdecompress($this->cleartext);
            } elseif($this->compression == "gzip") {
                $this->cleartext = gzuncompress($this->cleartext);
            }
            return trim($this->cleartext);
        } else {
            return $str;
        }
    }

    /**
     * Returns the supplied string hashed with a salt.
     *
     * @param string $ciphertext the string to hash.
     *
     * @return string
     */
    function hash_crypt($ciphertext) {
        global $cfg;
        //dump_var(strlen(hash($cfg['crypt_hmac_algo'], $ciphertext . $cfg['md5_salt'])));
        if (isset($cfg['crypt_hmac_algo'])) {
            return hash($cfg['crypt_hmac_algo'], $ciphertext . $cfg['md5_salt']);
        } else {
            return hash('md5', $ciphertext . $cfg['md5_salt']);
        }
    }

    /**
     * Returns the array of hash value and encrypted text (without hash.
     *
     * @param string $ciphertext the string to hash.
     *
     * @return array
     */
    function hash_decrypt($ciphertext) {
        global $cfg;

        if (isset($cfg['crypt_hmac_algo'])) {
            $dec[0] = substr($ciphertext,0,$this->hash_algorithms[$cfg['crypt_hmac_algo']]);
            $dec[1] = substr($ciphertext,$this->hash_algorithms[$cfg['crypt_hmac_algo']]);
        } else {
            $dec[0] = substr($ciphertext,0,$this->hash_algorithms['md5']);
            $dec[1] = substr($ciphertext,$this->hash_algorithms['md5']);
        }
        return $dec;
    }

    /**
     * Check the hash on the string to make sure no mangling has occurred.
     *
     * @param string $str the string check.
     * @param boolean $base64_encoded
     *
     * @return boolean
     */
    function hash_check(&$str = "", $base64_encoded = TRUE) {
        global $cfg, $user1;
        $user_id = (isset($user1->id)) ? $user1->id : 0;

        $untouched_str = $str;
        if ($base64_encoded === TRUE) $str = base64_decode(strtr($str, '-_!', '+/='));

        $dec = $this->hash_decrypt($str);
        $hash = $dec[0];
        $str = $dec[1];
        $computed_hash = $this->hash_crypt($str);
        if ($hash == $computed_hash) {
            return true;
        } else {
            error_log(print_r(array($untouched_str, $hash, $this->hash_crypt($str)), true));
            error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));

            $page = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            $this->intrusion_log->insert(array(
				'user_id'=>$user_id,
				'name'=>'GET',
				'value'=>$untouched_str,
				'domain'=>$_SERVER['SERVER_NAME'],
				'page'=>$page,
				'tags'=>'mangle',
				'filter_id'=>0,
				'description'=>'Detects mangling of encrypted string',
				'ip'=>$ip,
				'impact'=>999,
				'origin'=>$_SERVER['SERVER_ADDR'],
				'created'=>date('Y-m-d H:i:s')
            ));
            if (empty($_SESSION['feedback'])) $_SESSION['feedback']='';
            $_SESSION['feedback'] .= g_feedback("error","Mangling attempt detected and has been logged.");

            // Mangling log in UI in dev mode.
            if (isset($cfg['ENV']) && strtolower($cfg['ENV']) == 'local-dev') {

                $mhtml = "<p><b>Mangling attempt detected and has been logged.<br /></b>\n";
                $mhtml .= "Value: $untouched_str<br/>";
                $mhtml .= "Page: $page<br/>";

                $debug = debug_backtrace();

                if (!empty($debug) && is_array($debug)){

                    $mhtml .= "<br/><br/>";

                    foreach($debug as $k=>$v){
                        if (empty($v['file'])) $v['file']='';
                        if (empty($v['line'])) $v['line']='0';
                        if(!empty($v['args']) && in_array($v['function'], array("include", "include_once", "require_once", "require"))){
                            $mhtml .= "#".$k." <b>".$v['function']."(".$v['args'][0].")</b> called at [".$v['file'].":<b>".$v['line']."]</b><br />";
                        }else{
                            $mhtml .= "#".$k." <b>".$v['function']."()</b> called at [".$v['file'].":<b>".$v['line']."]</b><br />";
                        }
                        $mhtml .= "------<br/>";
                    }
                }

                $mhtml .= "</p>\n";

                $_SESSION['errors'][md5($page.$untouched_str.$debug)]['html'] = $mhtml;
                $_SESSION['errors'][md5($page.$untouched_str.$debug)]['errstr'] = $untouched_str;

            }


            return false;
        }
    }

    /**
     * bcrypt implementation for securer hashing
     * salt generated using openssl
     *
     * @param string $str the string to hash
     * @param string $cost the work factor for bcrypt
     *
     * @return string
     */
    public function bcrypt($str, $cost="") {
        global $cfg;
        if (empty($cost)) {
            if (isset($cfg['work_factor']) && !empty($cfg['work_factor'])) {
                $cost=$cfg['work_factor'];
            } else {
                echo "Work factor is missing cannot continue.";
                die();
            }
        }
        $strong=true;
        $bytes = openssl_random_pseudo_bytes(16, $strong);
        $salt = str_replace('+', '.',base64_encode($bytes));
        $start = microtime(true);
        $hash = crypt($str,'$2a$'.$cost.'$'.$salt);
        //error_log("time to create password: ".(microtime(true)-$start));
        return $hash;
    }

    /**
     * uses bcrypt to verify the raw string matches the hash
     *
     * @param string $str the raw string for comparison
     * @param string $hash the hash for comparison
     *
     * @return boolean
     */
    public function bcrypt_verify($str, $hash) {
        $start = microtime(true);
        $hash2 = crypt($str, $hash);
        //error_log("time to varify password: ".(microtime(true)-$start));
        return ($hash==$hash2);
    }

    /**
     * PBKDF2 Implementation (as described in RFC 2898);
     *
     * @param string p password
     * @param string s salt
     * @param int c iteration count (use 1000 or higher)
     * @param int kl derived key length
     * @param string a hash algorithm
     *
     * @return string derived key
     */
    public function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {
        $hl = strlen(hash($a, null, true)); # Hash length
        $kb = ceil($kl / $hl);              # Key blocks to compute
        $dk = '';                           # Derived key

        # Create key
        for ( $block = 1; $block <= $kb; $block ++ ) {
            # Initial hash for this block
            $ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);
            # Perform block iterations
            for ($i = 1;$i<$c;$i++)
            # XOR each iterate
            $ib ^= ($b = hash_hmac($a, $b, $p, true));
            $dk .= $ib; # Append iterated block
        }
        # Return derived key of correct length
        return substr($dk, 0, $kl);
    }
}
