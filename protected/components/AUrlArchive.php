<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the component class used to work with the incoming URL archive files
 */

class AUrlArchive
{

    public $saveToBogusCommand;
    public $error_count = 0;
    public $stats_errors = 0;
    public $stats_ok = 0;
    public $unpacker = false;
    public $baseUrl;

    const MAX_NUMBER_OF_INVALID_URLS = 30;

    //Method used to set the initial attributes
    public function __construct($detection = 'detected')
    {
        $this->unpacker = new AUnpacker();
        $this->unpacker->max_files = 10;
        $this->unpacker->r_levels = 1;
        $this->unpacker->outputDir = PathFinder::ensure(VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . 'urls');
        $this->baseUrl = PathFinder::get(VIREX_INCOMING_PATH, $detection, 'urls') . '/';
    }

    //Method used to validate a URL
    public function valid_url($url)
    {
        $regexp = "^(([a-zA-Z]{2,10})\://)?([a-zA-Z0-9\.\-_]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-_]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|asia|arpa|info|jobs|mobi|name|pro|aero|coop|museum|travel|[a-zA-Z]{2,3}))(\:[0-9]+)*([\?/].*)?$";
        return preg_match('`' . $regexp . '`', strtolower($url));
    }

    //Method used to return all the found files
    public function return_all()
    {
        $files = array();
        foreach (scandir($this->baseUrl) as $file) {
            if (($file != '.') && ($file != '..') && ($file != '_lockfile') && (strpos($file, '.filepart') === false)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    public $last_error = '';

    //Method used to search for the new incoming URLs files
    public function add_new()
    {
        ALogger::log('AUrlArchive::search archives in urls folder');
        $this->stats_errors = 0; // reinit stats
        $this->stats_ok = 0;
        $files = scandir($this->baseUrl);
        ALogger::log('Total files found: ' . (count($files) - 2) . ' archives');
        if (count($files) > 2) {
            foreach ($files as $file) {
                if (($file != '.') && ($file != '..') && ($file != '_lockfile') && (strpos($file, '.filepart') === false)) {
                    ALogger::start_action('processing ' . $file . '..');
                    try {
                        $this->last_error = '';
                        if ($this->process_file($this->baseUrl . $file)) {
                            if (file_exists($this->baseUrl . $file)) {
                                unlink($this->baseUrl . $file);
                            }
                            $this->stats_ok++;
                        } else {
                            $this->move_to_bogus($this->baseUrl . $file);
                            $this->stats_errors++;
                        }
                    } catch (Exception $e) {
                        ALogger::error($e->getMessage(), true); // log critical error
                    }
                    ALogger::end_action(); // logging done message
                }
            }
        }
        // showing final stats
        if (($this->stats_errors + $this->stats_ok) > 0) {
            ALogger::log('Success: ' . $this->stats_ok . ' archives');
            ALogger::log('Errors : ' . $this->stats_errors . ' archives');
        }
    }

    //Method used to process the incoming URLs file
    public function process_file($file)
    {
        if ($this->is_archive($file)) {
            $this->unpacker->max_size = 6000;
            $this->unpacker->max_files = 15;
            $m = $this->read_archive($file); // read archive
            $this->unpacker->reInit();
            $this->unpacker->max_files = 15;
            return $m;
        } else {
            return $this->read_file($file, 'text');
        }
    }

    //Method used to check the incoming URLs file
    public function check_file($file, $mime, $just_mime = false)
    {
        $mime = trim($mime);
        if (substr(strtolower($mime), 0, strlen('text')) === 'text') {
            if ($just_mime) {
                return true;
            }
            $f = fopen($file, 'r');
            $numberInvalid = 0;
            while ($line = fgets($f)) {
                if (trim($line)) {
                    if (!$this->valid_url(trim($line))) {
                        $numberInvalid++;
                        if (self::MAX_NUMBER_OF_INVALID_URLS < $numberInvalid) {
                            $error_message = "URL \"" . trim($line) . "\" is not valid!";
                            $this->last_error = $error_message;
                            ALogger::error($error_message);
                            $this->error_count++;
                            return false;
                        }
                    }
                }
            }
            return true;
        } else {
            $this->error_count++;
            $error_message = "A file inside the archive has mime {$mime}, and does not seem to be a valid text file!";
            $this->last_error = $error_message;
            ALogger::error($error_message);
            return false;
        }
    }

    //Method used to move an incoming file to bogus
    private function move_to_bogus($file)
    {
        $error_message = $this->last_error;
        $bogusFolder = VIREX_INCOMING_PATH . '/bogus';
        if (!$this->saveToBogusCommand) {
            $this->saveToBogusCommand = Yii::app()->db->createCommand("INSERT INTO bogus_archives_bga (name_bga, type_bga,	date_add_bga, error_message_bga) VALUES (:name, :type, CURDATE(), :error)");
        }
        $this->saveToBogusCommand->execute(array(':name' => basename($file), ':type' => 'U', ':error' => $error_message));
        rename($file, $bogusFolder . Yii::app()->db->lastInsertId);
    }

    //method used to read an incoming file
    public function read_file($file, $mime)
    {
        if (!$this->check_file($file, $mime)) {
            return false;
        }
        $f = fopen($file, 'r');
        $insert = Yii::app()->db->createCommand("INSERT INTO urls_url (md5_url, sha256_url, url_url, added_when_url) VALUES	(md5(:url), :sha256, :url, CURDATE()) ON DUPLICATE KEY UPDATE added_when_url = CURDATE()");
        while ($line = fgets($f)) {
            if (trim($line) && $this->valid_url(trim($line))) {
                $insert->execute(array(':url' => trim($line), ':sha256' => hash('sha256', trim($line))));
            }
        }
        unset($insert);
        fclose($f);
        unlink($file);
        return true;
    }

    //Method used to check if an incoming file is an archive
    public function is_archive($file)
    {
        exec('file "' . $file . '"', $output, $errorCode);
        if ($errorCode) {
            ALogger::error('Check if is archive error: ' . substr(implode(" ", $output), -200));
        }
        if (preg_match('/text/', $output[0])) {
            return false;
        } else {
            return true;
        }
    }

    //Method used to unpack an incoming archive file
    public function read_archive($file)
    {
        $this->unpacker->archivePath = $file;
        $error = false;
        $this->unpacker->unpack();
        if ($this->unpacker->hadError()) {
            $e = $this->unpacker->getError();
            ALogger::error('Unpacker: ' . $e[0] . ':' . $e[1]);
            $this->last_error = 'Unpacker: ' . $e[0] . ':' . $e[1];
            if (!SystemHelper::emptyDir($this->unpacker->outputDir)) {
                ALogger::error('Error cleaning extract folder ' . $this->unpacker->outputDir, true);
            }
            return false;
        }
        $ok = true;
        foreach ($this->unpacker->getFiles() as $f) {
            if (!$this->check_file($f['path'], $f['mime'])) {
                $ok = false;
                break;
            }
        }
        if (!$ok) {
            foreach ($this->unpacker->getFiles() as $f) {
                unlink($f['path']);
            }
            // clean folder
            if (!SystemHelper::emptyDir($this->unpacker->outputDir)) {
                ALogger::error('Error cleaning extract folder ' . $this->unpacker->outputDir, true);
            }
        } else {
            foreach ($this->unpacker->getFiles() as $f) {
                $this->read_file($f['path'], $f['mime']);
            }
            if (!SystemHelper::emptyDir($this->unpacker->outputDir)) {
                ALogger::error('Error cleaning extract folder ' . $this->unpacker->outputDir, true);
            }
        }
        //perform cleanup:
        SystemHelper::emptyDir($this->unpacker->outputDir);
        if (count(scandir($this->unpacker->outputDir)) > 2) {
            ALogger::error('Error cleaning extract folder' . $this->unpacker->outputDir, true);
        }
        return $ok;
    }

}
