<?php

/* * ***************************************** */
/* Norman SampleShare Server Framework        */
/* Version 1.30                               */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* * ***************************************** */
include_once(dirname(__FILE__) . '/../../config/config.inc.php');

/* GLOBAL CONFIG VARS */
define('DIRTY_ROOT', VIREX_STORAGE_PATH . DIRECTORY_SEPARATOR . 'detected'); // Path to local sample storage
define('CLEAN_ROOT', VIREX_STORAGE_PATH . DIRECTORY_SEPARATOR . 'clean'); // Path to local CLEANFILES storage
define('CLEAN_TABLE', "samples_clean_scl");
define('DIRTY_TABLE', "samples_detected_sde");

define('DB_HOST', VIREX_DB_HOST);
define('DB_USER', VIREX_DB_USER);
define('DB_PASS', VIREX_DB_PASS);
define('DB_DATABASE', VIREX_DB_NAME);

/* Change the following defines to add support for more compression methods */
define('COMPRESSION_SUPPORTED_ZLIB', "true");
define('COMPRESSION_SUPPORTED_ZIP', "false");
define('COMPRESSION_SUPPORTED_RAR', "false");

/* Change the following defines to add/remove support for hashes */
define('HASH_SUPPORTED_MD5', "true");
define('HASH_SUPPORTED_SHA1', "false");
define('HASH_SUPPORTED_SHA256', "true");

class UserObject {

    public $ip_address;
    public $key_name;
    public $active;
    public $approved;
    public $user_id;
    public $vendor_id;
    public $limitation_date;
    public $rights_monthly;
    public $rights_daily;
    public $rights_clean;
    public $rights_urls;

    // [END] extra

    function __construct($sql, $uri_user) {
        $uri_user = $sql->real_escape_string($uri_user);
        $res = $sql->query("SELECT * FROM external_users_usr WHERE name_usr='$uri_user' LIMIT 1");
        if (!$res)
            die("ERROR!");
        $row = $res->fetch_object();
        if (!$row) {
            die("Error! => Account not found!");
        }
        $this->ip_address = $row->ip_usr;
        $this->user_id = $row->id_usr;
        $this->key_name = $row->pgp_key_name_usr;
        $this->limitation_date = $row->limitation_date_usr;
        if ($row->status_usr == 2) {
            $this->active = 1;
            $this->approved = 1;
        } else {
            $this->active = 0;
            $this->approved = 0;
        }
        $this->vendor_id = $row->company_usr;
        $res->free_result();
        $this->rights_monthly = $row->rights_monthly_usr;
        $this->rights_daily = $row->rights_daily_usr;
        $this->rights_clean = $row->rights_clean_usr;
        $this->rights_urls = $row->rights_url_usr;
        if ($this->ip_address == "")
            die("ERROR! => User-object empty");
        if ($this->active == 0)
            die("ERROR! => Account not activated");
        if ($this->approved == 0)
            die("ERROR! => Account not approved");

        /* Uncomment the line below to restrict users to a single IP */
        //if($_SERVER['REMOTE_ADDR']!=$this->ip_address) die("ERROR! => Your account is not valid for this IP! (".$_SERVER['REMOTE_ADDR'].")");		
    }

}

class ServerObject {

    public $host_ip;
    public $sample;
    public $filename;
    public $sql;
    public $uri_utc_from;
    public $uri_utc_to;
    public $uri_user;
    public $uri_action;
    public $uri_compression;
    public $uri_hash_type;
    public $cleanfile;
    public $vars_dirty_root;
    public $vars_cleanfiles_root;
    public $vars_table_cleanfiles;
    public $vars_table_samples;
    public $localtime_from;
    public $localtime_to;
    // currentUser - added for virex stats
    public $virex_currentUser;
    public $virex_DetectionPrefix;
    public $virex_DetectionSufix;
    public $virex_CurrentList = false;
    public $virex_ExtraConditions = array();
    public $table;

    /**
     * 	   EXTRA FUNCTIONS ADDED By Mirel Mitache (mirel.mitache@virex.com)
     */
    /*
     * register_file_download
     * insert to history info that a user downloaded this file
     */
    function virex_register_file_download($md5, $file_size) {
        $user = $this->virex_currentUser;
        $existingStats = $this->sql->query("SELECT * FROM user_files_usf WHERE idusr_usf=" . $user->user_id . " AND idusl_usf IS NOT NULL AND md5_usf='" . $md5 . "' ORDER BY idusl_usf DESC, date_usf DESC LIMIT 0,1");
        $existingStats = $existingStats->fetch_object();
        if ($existingStats) {
            if ($existingStats->date_usf == date('Y-m-d H:i:s')) {
                // count update
                $this->sql->query("UPDATE user_files_usf SET count_usf=count_usf+1 WHERE id_usf=" . $existingStats->id_usf);
            } else {
                // insert with list id
                if ($existingStats->idusl_usf) {
                    $this->sql->Query("INSERT INTO user_files_usf (idusl_usf, md5_usf, date_usf, count_usf, idusr_usf, file_size_usf) VALUES
					({$existingStats->idusl_usf}, '$md5', NOW(), 1, {$user->user_id}, '$file_size')");
                } else {
                    $this->sql->Query("INSERT INTO user_files_usf (md5_usf, date_usf, count_usf, idusr_usf, file_size_usf) VALUES ('$md5', NOW(), 1, {$user->user_id}, '$file_size')");
                }
            }
        } else {
            // insert with no list id
            $this->sql->Query("INSERT INTO user_files_usf (md5_usf, date_usf, count_usf, idusr_usf, file_size_usf) VALUES ('$md5', NOW(), 1, {$user->user_id}, '$file_size')");
        }
        $hour = (int) date('H');
        $file_size = (int) $file_size;
        $this->sql->query("INSERT INTO permanent_statistics_user_psu (date_psu, hour_psu, idusr_psu, files_number_psu, files_size_psu)
			VALUES (CURDATE(), $hour, {$user->user_id}, 1, $file_size) ON DUPLICATE KEY UPDATE files_number_psu = files_number_psu+1, files_size_psu=files_size_psu+$file_size");
    }

    /**
     * When a new list is downloaded this function registers details about the list (interval, detection_prefix, detection_sufix and files)
     * @param <int> $number
     */
    function virex_register_list_download($number, $type = 'Detected') {
        $number = (int) $number;
        if (!$number) {
            return true; // no result found so I don't record list
        }
        $start_interval = date('Y-m-d', strtotime($this->localtime_from));
        $end_interval = date('Y-m-d', strtotime($this->localtime_to));
        $this->sql->Query("INSERT INTO user_lists_usl (date_usl, idusr_usl, text_usl, number_of_files_usl, start_interval_usl, end_interval_usl, list_type_usl) VALUES
			(NOW(), {$this->virex_currentUser->user_id}, '{$this->virex_DetectionPrefix}/{$this->virex_DetectionSufix}',
			'$number', '$start_interval', '$end_interval', '$type')");
        $this->virex_CurrentList = $this->sql->insert_id;
        $this->sql->Query("INSERT INTO permanent_statistics_user_psu (date_psu, hour_psu, idusr_psu, files_number_psu, files_size_psu, files_in_list_count_psu) VALUES
				(CURDATE(), HOUR(NOW()), {$this->virex_currentUser->user_id}, 0, 0, $number) ON DUPLICATE KEY UPDATE files_in_list_count_psu=files_in_list_count_psu+$number");
    }

    /**
     * When a list is downloaded this function registers on file from that list in db
     * @param <string> $md5
     */
    function virex_add_file_to_list($md5, $size) {
        if ($this->virex_CurrentList) {
            $this->sql->Query("INSERT INTO user_files_usf (idusl_usf, md5_usf, date_usf, count_usf, idusr_usf, file_size_usf) VALUES
		    ({$this->virex_CurrentList}, '$md5', NOW(), 0, {$this->virex_currentUser->user_id}, '$size')");
        }
    }

    /**
     * sets limitation date, extra_condition(to respect ignored detections and user_rights), detection prefix  and sufix
     */
    function virex_init() {
        // step 1.1. setting time limit
        $limitDate = $this->virex_currentUser->limitation_date . ' 00:00:01';
        if ($limitDate > $this->localtime_from) {
            $this->localtime_from = $limitDate;
        }
        if ($limitDate > $this->localtime_to) {
            $this->localtime_to = $limitDate;
        }
        // step 1.2. time limit - insert condition in query
        $this->virex_ExtraConditions = array(
            'clean' => "samples_clean_scl.added_when_scl >= '$this->localtime_from' AND samples_clean_scl.added_when_scl <= '$this->localtime_to'",
            'detected' => "samples_detected_sde.added_when_sde >= '$this->localtime_from' AND samples_detected_sde.added_when_sde <= '$this->localtime_to'",
            'urls' => "urls_url.added_when_url >= '$this->localtime_from' AND urls_url.added_when_url <= '$this->localtime_to'"
        );
        // step 2. rights daily / monthly
        if ((!$this->virex_currentUser->rights_daily) || (!$this->virex_currentUser->rights_monthly)) {
            if ($this->virex_currentUser->rights_monthly) {
                $this->virex_ExtraConditions['clean'] .= ' AND type_scl = "monthly"';
                $this->virex_ExtraConditions['detected'] .= ' AND type_sde = "monthly"';
            } elseif ($this->virex_currentUser->rights_daily) {
                $this->virex_ExtraConditions['clean'] .= ' AND type_scl = "daily"';
                $this->virex_ExtraConditions['detected'] .= ' AND type_sde = "daily"';
            } else {
                $this->virex_ExtraConditions['clean'] .= ' AND type_scl = "None"';
                $this->virex_ExtraConditions['detected'] .= ' AND type_sde = "None"';
            }
        }


        // enabled conditions
        $this->virex_ExtraConditions['clean'] .= " AND enabled_scl=1 ";
        $this->virex_ExtraConditions['detected'] .= " AND enabled_sde =1 ";
        $this->virex_ExtraConditions['urls'] .= " AND enabled_url = 1 ";

        // step 3. rights clean
        if (!$this->virex_currentUser->rights_clean) {
            $this->virex_ExtraConditions['clean'] = "0 ";
        }

        // step 4. detection prefix and sufix
        if ($this->virex_DetectionPrefix) {
            $this->virex_ExtraConditions['detected'] .= ' AND name_pfx="' . $this->virex_DetectionPrefix . '"';
        }
        if ($this->virex_DetectionSufix) {
            $this->virex_ExtraConditions['detected'] .= ' AND detection_sufix_sde LIKE "' . $this->virex_DetectionSufix . '%"';
        }
    }

    function __construct() {
        $this->host_ip = $_SERVER['REMOTE_ADDR'];

        $this->sql = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
        if (mysqli_connect_errno()) {
            printf("ERROR! => Connect failed: %s\n", mysqli_connect_error());
            exit();
        }
        $this->uri_utc_from = isset($_REQUEST['from']) ? $this->secure($_REQUEST['from']) : '2011-10-01 00:00:01';
        $this->uri_utc_to = isset($_REQUEST['to']) ? $this->secure($_REQUEST["to"]) : date('Y-m-d H:i:s', mktime(1, 1, 1, date('m'), date('d') + 2, date('Y')));
        $this->uri_user = isset($_REQUEST['user']) ? $this->secure($_REQUEST["user"]) : '';
        $this->uri_action = isset($_REQUEST['action']) ? $this->secure($_REQUEST["action"]) : '';
        $this->uri_compression = isset($_REQUEST['compression']) ? $this->secure($_REQUEST["compression"]) : '';
        $this->uri_hash_type = isset($_REQUEST['hash_type']) ? $this->secure(strtolower($_REQUEST["hash_type"])) : '';

        // ADDED extra options for detection prefix and sufix;
        $this->virex_DetectionPrefix = isset($_REQUEST['detection_prefix']) ? $this->secure($_REQUEST['detection_prefix']) : '';
        $this->virex_DetectionSufix = isset($_REQUEST['detection_sufix']) ? $this->secure($_REQUEST['detection_sufix']) : '';

        $this->localtime_from = $this->utc_to_localtime($this->uri_utc_from);
        $this->localtime_to = $this->utc_to_localtime($this->uri_utc_to);
        if (isset($_GET['clean']) && ($_GET["clean"] == "true"))
            $this->cleanfile = true;
        else
            $this->cleanfile = false;

        $this->vars_dirty_root = DIRTY_ROOT;
        $this->vars_cleanfiles_root = CLEAN_ROOT;
        $this->vars_table_cleanfiles = CLEAN_TABLE;
        $this->vars_table_samples = DIRTY_TABLE;

        if (!is_dir($this->vars_dirty_root))
            die("ERROR! => Sampleshare root is invalid");
        if (!is_dir($this->vars_cleanfiles_root))
            die("ERROR! => Cleanfiles root is invalid");
        if ($this->uri_hash_type == "") {
            if (isset($_REQUEST['md5']) && ($_REQUEST["md5"] != ""))
                $this->uri_hash_type = "md5";
            else if (isset($_REQUEST['sha1']) && ($_REQUEST["sha1"] != ""))
                $this->uri_hash_type = "sha1";
            else if (isset($_REQUEST['sha256']) && ($_REQUEST["sha256"] != ""))
                $this->uri_hash_type = "sha256";
            else
                $this->uri_hash_type = "md5"; // Default to MD5
        }
        if ($this->uri_hash_type == "md5" && !constant(HASH_SUPPORTED_MD5))
            die("ERROR! => MD5 not supported");
        if ($this->uri_hash_type == "sha1" && !constant(HASH_SUPPORTED_SHA1))
            die("ERROR! => SHA1 not supported");
        if ($this->uri_hash_type == "sha256" && !constant(HASH_SUPPORTED_SHA256))
            die("ERROR! => SHA256 not supported");
    }

    public function verify_gpg(&$encrypted) {
        // Thanks to Dmitry Gryaznov
        for ($x = 0; $x < 10; $x++) {
            $pgphead[$x] = bin2hex($encrypted[$x]);
        }

        if (($pgphead[0] & ~3) != 84) {
            return 0;
        } else {
            switch ($pgphead[0] & 3) {
                case 0:
                    $verofs = 2;
                    break;

                case 1:
                    $verofs = 3;
                    break;

                case 2:
                    $verofs = 5;
                    break;

                case 3:
                    $verofs = 1;
                    break;

                default:
                    $verofs = 0;
                    break;
            }
            if ($pgphead[$verofs] != 3 && $pgphead[$verofs] != 2) {
                return 0;
            } else {
                return 1;
            }
        }
    }

    public function utc_to_localtime($utc_time) {
        $localTimezone = new DateTimeZone(date_default_timezone_get());
        $utcTimezone = new DateTimeZone('UTC');
        $myDateTime = new DateTime($utc_time, $utcTimezone);
        $myDateTime->setTimezone($localTimezone);
        return $myDateTime->format('Y-m-d H:i:s');
    }

    public function get_supported_compression() {
        if (constant(COMPRESSION_SUPPORTED_ZIP))
            echo "zip\r\n";
        if (constant(COMPRESSION_SUPPORTED_RAR))
            echo "rar\r\n";
        if (constant(COMPRESSION_SUPPORTED_ZLIB))
            echo "zlib\r\n";
    }

    public function get_supported_hashes() {
        if (constant(HASH_SUPPORTED_MD5))
            echo "MD5\r\n";
        if (constant(HASH_SUPPORTED_SHA1))
            echo "SHA1\r\n";
        if (constant(HASH_SUPPORTED_SHA256))
            echo "SHA256\r\n";
    }

    public function compress_file_zip($sample) {
        // Zip extension needs to be installed for this handler to work
        $basename = basename($sample);
        $compressed_sample = VIREX_TEMP_PATH . "/sample_" . $basename . ".zip";
        $zip = new ZipArchive;
        $res = $zip->open($compressed_sample, ZipArchive::CREATE);
        if ($res) {
            $zip->addFile($sample, $basename);
            $zip->close();
            if (!file_exists($compressed_sample) || filesize($compressed_sample) == 0)
                $this->send_error("Unable to compress $basename!");
            return $compressed_sample;
        } else
            $this->send_error("Unable to compress $basename!");
    }

    public function compress_file_zlib($sample) {
        $basename = basename($sample);
        $compressed_sample = VIREX_TEMP_PATH . "/sample_" . $basename . ".gz";
        $gzout = gzopen($compressed_sample, 'wb');
        if ($gzout) {
            $fin = fopen($sample, 'rb');
            if ($fin) {
                while (!feof($fin)) {
                    gzwrite($gzout, fread($fin, 1025 * 512));
                }
                fclose($fin);
            } else
                $this->send_error("Unable to compress $basename!");
            gzclose($gzout);
            if (!file_exists($compressed_sample) || filesize($compressed_sample) == 0)
                $this->send_error("Unable to compress $basename!");
            return $compressed_sample;
        } else
            $this->send_error("Unable to compress $basename!");
    }

    public function compress_file($sample) {
        // Add your custom compression-handlers here to support more formats

        if ($this->uri_compression == "zip" && !constant(COMPRESSION_SUPPORTED_ZIP))
            $this->send_error("$this->uri_compression not supported!");
        if ($this->uri_compression == "rar" && !constant(COMPRESSION_SUPPORTED_RAR))
            $this->send_error("$this->uri_compression not supported!");
        if ($this->uri_compression == "zlib" && !constant(COMPRESSION_SUPPORTED_ZLIB))
            $this->send_error("$this->uri_compression not supported!");

        $compress_function = "compress_file_" . $this->uri_compression;
        if (method_exists($this, $compress_function))
            return call_user_func(array($this, $compress_function), $sample);
        else
            $this->send_error("compression function for $this->uri_compression not found!");
    }

    public function encrypt_file($sample, $recp) {
        $gpghome = PathFinder::ensure(VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . 'gnupg');
        $plaintext = tempnam(VIREX_TEMP_PATH, "Sample");
        $encrypted = $plaintext . ".gpg";
        copy($sample, $plaintext);
        $command = 'gpg --batch --pgp2 --no-tty --homedir=' . $gpghome . ' --always-trust --no-secmem-warning -e -r "' . $recp . '" ' . $plaintext;
        $result = exec($command, $output, $errorcode);
        @unlink($plaintext);
        return $encrypted;
    }

    public function encrypt_buffer($buffer, $recp, $filename = 'buffer.txt') {
        $gpghome = PathFinder::ensure(VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . 'gnupg');
        $plaintext = VIREX_TEMP_PATH.'/'.$filename;
        $encrypted = $plaintext . ".gpg";
        $fout = fopen($plaintext, "w");
        fwrite($fout, $buffer);
        fclose($fout);
        $command = 'gpg --batch --pgp2 --no-tty --homedir=' . $gpghome . ' --always-trust --no-secmem-warning -e -r "' . $recp . '" ' . $plaintext;
        $result = exec($command, $output, $errorcode);

        if (!file_exists($encrypted))
            die("ERROR! => Error encrypting buffer!");
        $encrypted_buffer = file_get_contents($encrypted);

        @unlink($plaintext);
        @unlink($encrypted);

        return $encrypted_buffer;
    }

    public function secure($string) {
        if (!$this->sql)
            die("Error: sql == NULL!");
        $string = strip_tags($string);
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }
        $string = $this->sql->real_escape_string($string);
        return $string;
    }

    public function send_error($error, $hash = "") {
        if ($this->uri_action == "getfile_by_list") {
            $error = "ERROR! => " . $error;
            if ($hash == "") {
                if ($this->uri_hash_type == "md5")
                    $hash = sprintf("%032d", 0);
                if ($this->uri_hash_type == "sha1")
                    $hash = sprintf("%040d", 0);
                if ($this->uri_hash_type == "sha256")
                    $hash = sprintf("%064d", 0);
            }
            $len = sprintf("%010d", strlen($error));
            die($len . $hash . $error);
        } else {
            die("ERROR! => " . $error);
        }
    }

    public function get_sample($hash) {
        if ($hash == "")
            $this->send_error("Empty hash specified", $hash);
        if ($this->cleanfile) {
            $table = $this->vars_table_cleanfiles;
            $this->filename = "clean_$hash";
            $root_path = $this->vars_cleanfiles_root;
        } else {
            $table = $this->vars_table_samples;
            $this->filename = "dirty_$hash";
            $root_path = $this->vars_dirty_root;
        }
        if ($this->uri_hash_type == "")
            $hash_type = "md5";
        else
            $hash_type = $this->uri_hash_type;

        // Edit this to fetch samples from storage using your preferred hash
        if ($this->cleanfile) {
            $res = $this->sql->query("SELECT hex({$hash_type}_scl) as hash, hex(md5_scl) as 'md5', file_size_scl as 'fsize' FROM $table WHERE {$hash_type}_scl='$hash' AND " . $this->virex_ExtraConditions['clean']);
        } else {
            $res = $this->sql->query("SELECT hex({$hash_type}_sde) as hash, hex(md5_sde) as 'md5', file_size_sde as 'fsize' FROM $table WHERE {$hash_type}_sde='$hash' AND " . $this->virex_ExtraConditions['detected']);
        }
        if (!$res)
            $this->send_error($this->sql->error);
        if ($res->num_rows == 0)
            $this->send_error("Sample not found by hash ($hash)", $hash);
        $row = $res->fetch_object();
        // select list
        $this->virex_register_file_download($row->md5, $row->fsize);
        if ($row->md5 == "")
            return;
        $part1 = substr($row->md5, 0, 3);
        $part2 = substr($row->md5, 3, 3);
        $part3 = substr($row->md5, 6, 3);
        $this->sample = $root_path . "/$part1/$part2/$part3/$row->md5";

        if (!file_exists($this->sample))
            $this->send_error("File not found ($this->sample)", $hash);
        return $this->sample;
    }

    public function send_headers($filename, $filesize = "") {
        header("Pragma: public\n");
        header("Content-Type: application/octet-stream\n");
        header("Content-Disposition: attachment; filename=$filename\n");
        header("Content-transfer-encoding: binary\n");
        if ($filesize != "")
            header("Content-Length: $filesize\n");
    }

    function ascii2hex($ascii) {
        $hex = '';
        for ($i = 0; $i < strlen($ascii); $i++) {
            $byte = strtoupper(dechex(ord($ascii{$i})));
            $byte = str_repeat('0', 2 - strlen($byte)) . $byte;
            $hex.=$byte . "";
        }
        return $hex;
    }

    public function get_list() {
        global $user;
        if ($this->cleanfile) {
            $table = $this->vars_table_cleanfiles;
            $root_path = $this->vars_cleanfiles_root;
        } else {
            $table = $this->vars_table_samples;
            $root_path = $this->vars_dirty_root;
        }
        // Configure to collect files from local storage using the preferred hash
		
		$this->sql->query("SET sql_mode = ''");
        if ($this->cleanfile) {
            $type = 'Clean';
            $res = $this->sql->query("SELECT md5_scl as md5, sha256_scl as sha256, file_size_scl as 'size' FROM $table WHERE " . $this->virex_ExtraConditions['clean'] . ' GROUP BY md5_scl');
        } else {
            $type = 'Detected';
            $res = $this->sql->query("SELECT md5_sde as md5, sha256_sde as sha256, file_size_sde as 'size' FROM $table WHERE " . $this->virex_ExtraConditions['detected'] . ' GROUP BY md5_sde');
        }
        if (!$res)
            $this->send_error($this->sql->error);

        $plaintext = tempnam(VIREX_TEMP_PATH, "HashList");
        $this->virex_register_list_download($res->num_rows, $type);
        $fout = fopen($plaintext, "w");
        if (!$fout)
            send_error("Unable to create $plaintext");
        if ($res) {
            while ($row = $res->fetch_object()) {
                if ($row->size > 0) {
                    $hex = $this->ascii2hex($row->md5);
                    if ($row->md5 == "" )
                        continue;
                    $this->virex_add_file_to_list($row->md5, $row->size);
                    $part1 = substr($hex, 0, 3);
                    $part2 = substr($hex, 3, 3);
                    $part3 = substr($hex, 6, 3);
                    $file = $root_path . "/$part1/$part2/$part3/$hex";
                    if (!file_exists($file))
                        continue;
					$hash = $row->md5;
					if(isset($_REQUEST['hashalgo']) && $_REQUEST['hashalgo']=='sha256'){
						$hash = $row->sha256;
					}
				
                    fwrite($fout, "$hash:$row->size\r\n");
                }
            }
        }
        fclose($fout);
        return $plaintext;
    }

}