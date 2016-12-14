<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This component class is used to operate the archives
 */

class AFileArchive
{

    public $unpacker = false;
    public $error_count = 0;
    public $stats_ok = 0;
    public $stats_errors = 0;
    public $number_of_archives = 0;

    //Set the initial values for attributes
    public function __construct()
    {
        $this->unpacker = new AUnpacker();
        $this->unpacker->outputDir = PathFinder::ensure(VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . 'files');
        $this->unpacker->max_size = 80000;
        $this->unpacker->max_files = 50000;
        $this->unpacker->r_levels = 1;
    }

    //Method used to scan the folders for incoming files
    public function scan_folders($detection, $type)
    {
        if (!in_array($type, array('daily', 'monthly', 'bogus'))) { // check type
            ALogger::error('Unknown file type: ' . $type, true);
        }
        $archFolder = PathFinder::get(VIREX_INCOMING_PATH, $detection, $type); // setting archives folder

        $folderExceptions = array('.', '..', '_lockfile');
        $files = scandir($archFolder); // scanning files

        foreach ($files as $k => $f) {
            if (in_array($f, $folderExceptions)) {
                if (strpos($f, '.filepart') === false) {
                    unset($files[$k]);
                }
            }
        }
        return $files;
    }

    //Method used to process the available archives
    public function process_archives($detection, $type)
    {
        $archFolder = PathFinder::get(VIREX_INCOMING_PATH, $detection, $type); // setting archives folder
        $files = $this->scan_folders($detection, $type);
        foreach ($files as $fil) {
            $fi = $archFolder . DIRECTORY_SEPARATOR . $fil;
            ALogger::start_action('processing ' . $fi);
            try {
                if ($this->extract_file($fi, $detection, $type)) { // process file
                    try {
                        unlink($fi); // delet file
                    } catch (Exception $e) {
                        ALogger::error('delete error: ' . $e->getMessage(), true); // catch fatal error
                    }
                    $this->stats_ok++; // count ok files
                } else {
                    $this->stats_errors++; // count errors
                }
            } catch (Exception $e) {
                ALogger::error($e->getMessage(), true); // catch fata error
            }
            ALogger::end_action();
        }
        return $files;
    }

    //method used to extract the files from any archive
    public function extract_file($fname, $detection, $type, $nobogus = false, $bogus_id = null)
    {
        SystemHelper::emptyDir($this->unpacker->outputDir);
        $this->unpacker->reInit();
        $this->unpacker->archivePath = $fname;
        $this->unpacker->unpack();
        if (!$this->unpacker->hadError()) {
            $qstat = "INSERT INTO permanent_statistics_ftp_psf (date_psf, hour_psf, archives_number_psf)
                VALUES (CURDATE(), HOUR(NOW()), 1) ON DUPLICATE KEY UPDATE archives_number_psf = archives_number_psf + 1";
            Yii::app()->db->createCommand($qstat)->execute();

            foreach ($this->unpacker->getFiles() as $f) {
                $fName = $f['path'];
                if (!file_exists($fName)){
                    continue;
                }
                $md5 = hash_file('md5', $fName);
                $sha256 = hash_file('sha256', $fName);
                $file = $this->ascii2hex($md5);
                $dir = $this->makedir($file, VIREX_STORAGE_PATH . DIRECTORY_SEPARATOR . $detection . DIRECTORY_SEPARATOR);
                if (rename($fName, $dir . $file)) { // if error occurs is capture by main method ( scan_archives )
                    $this->save_to_db($detection, $type, $md5, $sha256, filesize($dir . $file));
                }
            }
        } else {
            $this->error_count++; // increase error count
            $e = $this->unpacker->getError();
            ALogger::error('Unpacker:' . $e[0] . ':' . $e[1]); // logging error
            if (!$nobogus) { // if I have to move file to bogus
                $this->move_file_to_bogus($detection, $type, $fname, $e[0] . ':' . $e[1]);
            } else {
                $this->move_file_to_bogus($detection, $type, $fname, $e[0] . ':' . $e[1], $bogus_id);
            }
            SystemHelper::emptyDir($this->unpacker->outputDir);
            return false;
        }
        SystemHelper::emptyDir($this->unpacker->outputDir);
        return true;
    }

    //method used to mark the files as bogus and to move them to the special folder
    private function move_file_to_bogus($detection, $type, $file, $error, $bogusId = null)
    {
        if ($bogusId) { // if file is already in bogus just change error message
            // execute update
            $q = "UPDATE bogus_archives_bga SET error_message_bga=:error WHERE id_bga=:id";
            Yii::app()->db->createCommand($q)->execute(array(
                ':error' => $error,
                ':id' => $bogusId
            ));
            return;
        }
        $bogusFolder = PathFinder::get(VIREX_INCOMING_PATH, $detection, 'bogus');
        $q = "INSERT INTO bogus_archives_bga (name_bga, detection_bga, type_bga, date_add_bga, error_message_bga)
            VALUES (:name, :detection, :type, CURDATE(), :error)";
        Yii::app()->db->createCommand($q)->execute(array(':name' => basename($file), ':detection' => $detection, ':type' => $type, ':error' => $error));
        $id = Yii::app()->db->lastInsertId;
        try {
            rename($file, $bogusFolder . DIRECTORY_SEPARATOR . $id);
        } catch (Exception $e) {
            ALogger::error($e->getMessage(), true); // critical error
        }
    }

    //method used to save the samples information into the database
    private function save_to_db($detection, $type, $md5, $sha256, $size)
    {
        switch ($detection) {
            case 'detected':
                $q = "INSERT INTO samples_detected_sde (added_when_sde, md5_sde, sha256_sde, file_size_sde, type_sde) VALUES (NOW(), :md5, :sha256, :size, :type) ON DUPLICATE KEY UPDATE added_when_sde = NOW()";
                break;
            case 'clean':
                $q = "INSERT INTO samples_clean_scl (added_when_scl, md5_scl, sha256_scl, file_size_scl, type_scl) VALUES (NOW(), :md5, :sha256, :size, :type) ON DUPLICATE KEY UPDATE added_when_scl = NOW()";
                break;
        }
        Yii::app()->db->createCommand($q)->execute(array(':md5' => $md5, ':sha256' => $sha256, ':size' => $size, ':type' => $type));

        $q = "INSERT INTO permanent_statistics_ftp_psf (date_psf, hour_psf, files_number_psf, files_size_psf)
            VALUES (CURDATE(), HOUR(NOW()), 1, :size) ON DUPLICATE KEY UPDATE files_number_psf = files_number_psf + 1, files_size_psf = files_size_psf + :size";
        Yii::app()->db->createCommand($q)->execute(array(':size' => $size));
    }

    //Method used to transform ascii to hex
    private function ascii2hex($ascii)
    { // for sampleshare framework
        $hex = '';
        for ($i = 0; $i < strlen($ascii); $i++) {
            $byte = strtoupper(dechex(ord($ascii{$i})));
            $byte = str_repeat('0', 2 - strlen($byte)) . $byte;
            $hex .= $byte . "";
        }
        return $hex;
    }

    //Method used to create a folder
    private function makedir($file, $baseUrl)
    { // for sampleshare framework
        $dir = $baseUrl . substr($file, 0, 3) . '/';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $dir .= substr($file, 3, 3) . '/';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $dir .= substr($file, 6, 3) . '/';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

}
