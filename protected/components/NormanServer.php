<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This class extends the NormanShare server extension
 * It is used for the Server side operations
 */

class NormanServer
{

    /**
     * @var Account
     */
    public static $account = null;

    /**
     * @var Collection
     */
    public static $collection = null;

    //the files server action - used to download files by a list of hashes
    private static function _files($list = array())
    {
        self::_streamHeader('block.gpg');
        foreach ($list as $hash) {
            $attr = false;
            if (preg_match('/^[0-9a-f]{32}$/i', $hash)) {
                $attr = 'md5_sam';
            }
            if (preg_match('/^[0-9a-f]{64}$/i', $hash)) {
                $attr = 'sha256_sam';
            }
            if (!$attr) {
                return;
            }
            $sam = Sample::model()->findByAttributes(array($attr => $hash));
            if (!$sam || !is_file($sam->getFilePath())) {
                return;
            }
            $tempfile = self::_tempEncrypt($sam->getFilePath());
            if (!is_file($tempfile)) {
                continue;
            }
            echo str_pad(filesize($tempfile), 10, '0', STR_PAD_LEFT) . $hash;
            $f = fopen($tempfile, 'r');
            while (!feof($f)) {
                echo fread($f, 8192);
                flush();
            }
            fclose($f);
            @unlink($tempfile);
            StatDownload::increase(self::$account->id_acc, $sam->size_sam);
            History::download(self::$account->id_acc, $sam->id_sam);
        }
    }

    //the file server action - used to get a file by its hash
    private static function _file($hash, $single = true, $source = 'api')
    {
        $attr = false;
		$hash = strtolower($hash)
        if (preg_match('/^[0-9a-f]{32}$/i', $hash)) {
            $attr = 'md5_sam';
        }
        if (preg_match('/^[0-9a-f]{64}$/i', $hash)) {
            $attr = 'sha256_sam';
        }
        if (!$attr) {
            return false;
        }
        $sam = Sample::model()->findByAttributes(array($attr => $hash));
        if (!$sam || !is_file($sam->getFilePath())) {
            return false;
        }

        //check Collection rights
        self::$collection = Collection::model()->findAllByAttributes(array('type_col' => 'file', 'enabled_col' => 1), array('condition' => 'acronym_col != "clean"'));
        if (!self::$collection) {
            return false;
        }
        $rights = self::$account->colRights();

        if (is_array(self::$collection)) {
            foreach (self::$collection as $k => $coll) {
                if (!isset($rights[$coll->id_col])) {
                    unset(self::$collection[$k]);
                }
            }
        }
        if (!count(self::$collection)) {
            return false;
        }
        $found = 0;
        foreach (self::$collection as $collection) {
            $sample_in_collection = Yii::app()->db->createCommand("SELECT added_s2c FROM sam2cols_s2c WHERE idcol_s2c='" . $collection->id_col . "' AND idsam_s2c='" . $sam->id_sam . "' AND added_s2c>=" . $rights[$collection->id_col])->queryScalar();
            if ($sample_in_collection) {
                $found = 1;
                break;
            }
        }
        if (!$found) {
            return false;
        }
        if (!$single) {
            echo str_pad($sam->size_sam, 10, '0', STR_PAD_LEFT) . $hash;
        }
        self::_streamEncrypt($sam->getFilePath(), $single ? $sam->$attr . '.gpg' : false);
        StatDownload::increase(self::$account ? self::$account->id_acc : Yii::app()->user->userId, $sam->size_sam, $source);
        History::download(self::$account ? self::$account->id_acc : Yii::app()->user->userId, $sam->id_sam);
        return true;
    }

    //method used to get a list of files by a collection name
    private static function _list($collAcr)
    {

        if (null === $collAcr) {
            self::$collection = Collection::model()->findAllByAttributes(array('type_col' => 'file', 'enabled_col' => 1), array('condition' => 'acronym_col != "clean"'));
        } else {
            self::$collection = Collection::model()->findByAttributes(array('acronym_col' => $collAcr, 'enabled_col' => 1));
        }

        if (!self::$collection) {
            self::_error('Invalid collection!');
            return;
        }

        $rights = self::$account->colRights();
        if (is_array(self::$collection)) {
            foreach (self::$collection as $k => $coll) {
                if (!isset($rights[$coll->id_col])) {
                    unset(self::$collection[$k]);
                }
            }
            if (!count(self::$collection)) {
                return;
            } elseif (1 === count(self::$collection)) { // if it's just one collection remaining use it as a single collection instead of an array;
                foreach (self::$collection as $coll) {
                    self::$collection = $coll;
                    break;
                }
            }
        } elseif (!isset($rights[self::$collection->id_col])) {
            self::_error('Permission denied to this collection!');
            return;
        }

        $dumpf = Config::value('DUMP_DIR') . '/' . uniqid('list') . ".txt";
        @unlink($dumpf);
        $dfrom = self::_utcTime(@$_REQUEST['from']);
        $dto = self::_utcTime(@$_REQUEST['to']);
        if (!$dto) {
            $dto = time();
        }
        if (!$dfrom) {
            self::_error('Invalid start time!');
            return;
        }

        $collectionCondition = '';
        if (!is_array(self::$collection)) {
            if ($dfrom < $rights[self::$collection->id_col]) {
                self::_error('Start time should be greater than (UTC): ' . gmdate('Y-m-d H:i:s', $rights[self::$collection->id_col]));
                return;
            }
            $collectionCondition = "idcol_s2c = " . self::$collection->id_col . " AND added_s2c BETWEEN {$dfrom} AND {$dto}";
        } else {
            $collectionCondition = array();
            foreach (self::$collection as $collection) {
                if ($dfrom < $rights[$collection->id_col]) {
                    $tfrom = $rights[$collection->id_col];
                } else {
                    $tfrom = $dfrom; // temporary date for this collection in case that the current account doesn't have rights for older samples;
                }
                if ($tfrom > $dto) {
                    continue; // the current account doesn't have any rights for this time interval;
                }
                $collectionCondition[] = " (idcol_s2c = {$collection->id_col} AND added_s2c BETWEEN $tfrom AND $dto) ";
            }
            if (!count($collectionCondition)) {
                self::_error('Start time should be greater than (UTC): ' . gmdate('Y-m-d H:i:s', $rights[$collection->id_col]));
                return;
            }
            $collectionCondition = implode(' OR ', $collectionCondition);
        }

        switch (is_array(self::$collection) ? $collection->type_col : self::$collection->type_col) {
            case 'url':
                $q = "SELECT url_url FROM urls_url
                    INNER JOIN url2cols_u2c ON idurl_u2c = id_url
                    WHERE idcol_u2c = " . self::$collection->id_col . " AND added_u2c BETWEEN {$dfrom} AND {$dto}
                    INTO OUTFILE '{$dumpf}' FIELDS TERMINATED BY ':' ENCLOSED BY '' ESCAPED BY '' LINES TERMINATED BY '\r\n'";
                Yii::app()->db->createCommand($q)->execute();
                self::_streamEncrypt($dumpf, 'urls.gpg');
                break;
            case 'file':
                AccountHistoryList::createFromAPI(self::$account->id_acc, @$_REQUEST['from'], @$_REQUEST['to'], self::$collection);
                $colHash = 'md5_sam';
                if (isset($_REQUEST['hashalgo']) && ($_REQUEST['hashalgo'] == 'sha256')) {
                    $colHash = 'sha256_sam';
                }
                $q = "SELECT $colHash, size_sam FROM samples_sam 
                    INNER JOIN sam2cols_s2c ON idsam_s2c = id_sam
                    WHERE $collectionCondition
                    INTO OUTFILE '{$dumpf}' FIELDS TERMINATED BY ':' ENCLOSED BY '' ESCAPED BY '' LINES TERMINATED BY '\r\n'";
                Yii::app()->db->createCommand($q)->execute();
                if (!is_file($dumpf)) {
                    file_put_contents($dumpf, '');
                }
                self::_streamEncrypt($dumpf, 'hashlist.gpg');
                break;
        }
        @unlink($dumpf);
    }

    //method used to return the URC time of a date
    public static function _utcTime($date)
    {
        if (!$date) {
            return false;
        }
        return strtotime($date . ' UTC');
    }

    //method used to show the file streamming header 
    private static function _streamHeader($filename)
    {
        header("Pragma: public\n");
        header("Content-Type: application/octet-stream\n");
        header("Content-Disposition: attachment; filename=$filename\n");
        header("Content-transfer-encoding: binary\n");
    }

    //method used to encrypt a file
    private static function _streamEncrypt($file, $filename = false)
    {
        if (!file_exists($file)) {
            touch($file);
        }
        if ($filename) {
            self::_streamHeader($filename);
        }

        $recs = '';
        foreach (self::$account->getKeyNames() as $kn) {
            $recs .= '-r "' . $kn . '" ';
        }
        $cmd = 'gpg --batch --pgp2 --always-trust --no-secmem-warning -e ' . $recs . '-o - ' . $file;
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        flush();
        $process = proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            while ($s = stream_get_contents($pipes[1])) {
                echo $s;
                flush();
            }
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
    }

    //method used to encrypt a file temporary
    private static function _tempEncrypt($file)
    {
        $outfile = Config::value('DUMP_DIR') . '/' . uniqid('encrypted');
        $recs = '';
        foreach (self::$account->getKeyNames() as $kn) {
            $recs .= '-r "' . $kn . '" ';
        }
        $cmd = 'gpg --batch --pgp2 --always-trust --no-secmem-warning -e ' . $recs . '-o ' . $outfile . ' ' . $file;

        exec($cmd);
        return $outfile;
    }

    //method used to download a specific file based on its hash
    public static function getFile($hash)
    {
        self::$account = Account::model()->findByPk(Yii::app()->user->userId);
        return self::_file($hash, true, 'web');
    }

    //the initial method
    public static function run($coll = 'malware')
    {
        if (!self::_checkHttpAuth()) {
            return;
        }
        if (@$_REQUEST["clean"] == 'true') {    // some backwards compatibility
            $coll = 'clean';
        }
        switch (@$_REQUEST["action"]) {
            case 'getfile_by_list':
                self::_files(explode(':', @$_REQUEST['md5list'] . @$_REQUEST['hashlist']));
                break;
            case 'getfile':
                self::_file(@$_REQUEST['md5'] . @$_REQUEST['sha256']);
                break;
            case 'getlist':
                self::_list($coll);
                break;
            case 'geturls':
                self::_list('urls');            // some backwards compatibility
                break;
            case 'get_supported_compression':
                echo "zip\r\n";
                break;
            case 'get_supported_hashes':
                echo "MD5\r\nSHA256\r\n";
                break;
            case 'getmetadata':
                self::_metadata();
                die();
                break;
            default:
                self::_error('Invalid action!');
                break;
        }
    }

    //method used to return a specific metadata
    private static function _metadata()
    {
        file_put_contents(Config::value('TMP_DIR') . '/norman_metadata', 'No data!');
        self::_streamEncrypt(Config::value('TMP_DIR') . '/norman_metadata');
    }

    //method used to show the error
    private static function _error($message)
    {
        echo 'ERROR! => ' . $message;
    }

    //method used for user authentication
    private static function _checkHttpAuth()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="VIREX"');
            header('HTTP/1.0 401 Unauthorized');
            self::_error('Login needed!');
            return false;
        }
        self::$account = ExternalUser::model()->findByAttributes(array('status_usr' => 2, 'name_usr' => $_SERVER['PHP_AUTH_USER'], 'password_usr' => ExternalUser::passwordHash($_SERVER['PHP_AUTH_PW'])));
        if (!self::$account) {
            header('HTTP/1.0 403 Forbidden');
            self::_error('Bad login!');
            return false;
        }
        self::$account->last_login_date_usr = date("Y-m-d H:i:s");
        self::$account->save();
        return true;
    }

}
