<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the component class used to unpack the incoming archive files
 */

class AUnpacker
{
    #Errors:
    # 100 ######
    # 101 - output folder does not exist
    # 102 - output folder is not empty
    # 103 - mkdir failed
    # 200 ######
    # 201 - RAR STAT failed
    # 202 - 7z STAT failed
    # 203 - Can not determine archive type correctly!
    # 300 ######
    # 301 - too many files
    # 302 - size exceeded
    # 400 ######
    # 401 - not implemented

    const MAX_RECURSE_FILES = 200;

    public $archivePath;
    public $outputDir;
    public $max_size = 4096; // in megabytes
    public $max_files = 2;
    public $r_levels = 10; // number of recursive unpackings
    public $build_files_array = true; // number of recursive unpackings
    public $archive_password = null;
    public $delete_archive = false;
    public $check_empty_output_folder = true;
    public $handle_recursive_gpg = false;
    public $debug = false;
    private $subfolder = null;
    private $files = array();
    private $errorCode = null;
    private $errorOutput = null;

    //set the initial valus for attributes
    public function __construct($parent = null, $parent_size = 0, $parent_files = 0)
    {
        if ($parent && is_a($parent, 'AUnpacker')) {
            $this->outputDir = $parent->outputDir;
            $this->max_size = $parent->max_size - $parent_size;
            $this->max_files = $parent->max_files - $parent_files;
            $this->r_levels = $parent->r_levels - 1;
            $this->debug = $parent->debug;
            $this->handle_recursive_gpg = $parent->handle_recursive_gpg;
            $this->check_empty_output_folder = false;
            $this->delete_archive = true;
        }
    }

    //method for the reinitialization
    public function reInit()
    {
        $this->errorCode = false;
        $this->errorOutput = false;
        $this->files = array();
        $this->subfolder = null;
    }

    //returns the type of the archive
    public function getArchiveType($file, $pgpOk = true, $saveError = true)
    {
        return self::sGetArchiveType($file, $pgpOk, $saveError, $this);
    }

    //returns the type of the archive
    public static function sGetArchiveType($file, $pgpOk = true, $saveError = true, $uu = null)
    {
        if (!isset($uu) || !($uu instanceof AUnpacker)) {
            $saveError = false; //to prevent trying to access $this object;
            $debug = false;
        } else {
            $debug = $uu->debug;
        }

        $fileMime = FileHelper::fileMime($file);
        if (preg_match('/GPG/', $fileMime) && $pgpOk) {
            if ($debug)
                echo($file . " is pgp!\n");
            return 'pgp';
        }
        elseif (preg_match('/application\/zip/', $fileMime) || preg_match('/7-zip/', $fileMime)) {
            if ($debug)
                echo($file . " is zip!\n");
            return 'zip';
        }
        elseif (preg_match('/application\/octet-stream/', $fileMime) || preg_match('/7-zip/', $fileMime)) {
            if ($debug)
                echo($file . " is 7z!\n");
            return '7z';
        }
        elseif (preg_match('/RAR/', $fileMime)) {
            if ($debug)
                echo($file . " is rar!\n");
            return 'rar';
        }
        elseif (preg_match('/gzip/', $fileMime)) {
            if ($debug)
                echo($file . " is gzip!\n");
            return 'gzip';
        }
        elseif (preg_match('/tar/', $fileMime)) {
            if ($debug)
                echo($file . " is tar!\n");
            return 'tar';
        }
        elseif (preg_match('/: data$/', $fileMime) && $pgpOk) {
            if ($debug)
                echo($file . " is pgp!\n");
            return 'pgp';
        }
        else {
            if (preg_match('/\.pgp$/', $file) && $pgpOk) {
                if ($debug)
                    echo($file . " is pgp!\n");
                return 'pgp';
            } //last resort pgp detection
            if ($saveError) {
                $uu->errorCode = 'FILE: ' . $fileMime;
            }
            if ($debug)
                echo($file . " is not an archive!\n");
            return false;
        }
    }

    //the method for unpacking
    public function unpack($justReturnList = false)
    {
        if ($this->debug) {
            if (!$justReturnList) {
                echo("\n\nWill unpack archive {$this->archivePath}!\n");
            } else {
                echo("\n\nWill return listing for archive {$this->archivePath}!\n");
            }
        }
        if (!$justReturnList) {
            if (!$this->init()) {
                return false;
            }
            if (!$this->r_levels) {
                return true;
            }
        }

        if ($this->debug) {
            echo('Getting file type for: ' . $this->archivePath . "\n");
        }
        if (false == ($fileType = $this->getArchiveType($this->archivePath))) {
            return false;
        }

        $fullOutputPath = '';

        if (!$justReturnList) {
            $md5 = md5(uniqid());
            $this->subfolder = $md5;
            $fullOutputPath = $this->outputDir . '/' . $md5;
            if (!mkdir($fullOutputPath)) {
                $this->errorCode = '103';
                $this->errorOutput = "Can't mkdir " . $fullOutputPath . '!';
                return false;
            }
        }

        switch ($fileType) {
            case "7z":
            case "zip":
            case "gzip":
            case "tar":
                $statFunction = 'get7zStat';

                if (!empty($this->archive_password)) {
                    $password_command = '-p' . $this->archive_password . ' ';
                } else {
                    $password_command = '-p';
                }

                $unpackCommand = "7z e -o" . escapeshellarg($fullOutputPath) . " {$password_command} -aou " . self::escapeFilePath($this->archivePath);  //self::escapeFilePath();
                break;

            case "rar":
                $statFunction = 'getRarStat';

                if (!empty($this->archive_password)) {
                    $password_command = '-p' . $this->archive_password . ' ';
                } else {
                    $password_command = '-p-';
                }

                $unpackCommand = "unrar e -or {$password_command} " . escapeshellarg($this->archivePath) . " " . self::escapeFilePath($fullOutputPath);
                break;

            default:
                $this->errorCode = '203';
                if (isset($gpg_command)) {
                    $this->errorOutput = "Can not determine archive type correctly! Possible GPG fault! GPG output has size:" . filesize($this->archivePath);
                } else {
                    $this->errorOutput = "Can not determine archive type correctly!";
                }

                return false;
        }//end::switch($fileType)

        if ($justReturnList) {
            return call_user_func(array($this, $statFunction), $this->archivePath, $this->archive_password, true);
        }

        if (false !== ($stat = call_user_func(array($this, $statFunction), $this->archivePath, $this->archive_password))) {
            list($nr_files, $unpacked_size) = $stat;
        } else {
            return false;
        }

        if ($nr_files > $this->max_files) {
            $this->errorCode = '301';
            $this->errorOutput = "Too many files! ({$nr_files} vs {$this->max_files})";
            return false;
        }

        $unpacked_size = $unpacked_size / (1024 * 1024);
        if ($unpacked_size > $this->max_size) {
            $this->errorCode = '302';
            $this->errorOutput = "Size exceeded! ({$unpacked_size} vs {$this->max_size})";
            return false;
        }

        if ($this->debug) {
            echo("Found {$unpacked_size} smaller then {$this->max_size}\n");
        }

        if ($this->debug) {
            echo($unpackCommand . "\n");
        }
        exec($unpackCommand . ' 2>&1', $output, $errorCode);

        if ($errorCode) {
            $this->errorCode = "{$fileType}:{$errorCode}";
            $this->errorOutput = substr(implode(" ", $output), -200);
            if ($this->debug) {
                echo("Emptying " . $fullOutputPath . "\n");
            }
            SystemHelper::emptyDir($fullOutputPath);
            return false;
        }

        $recurse = ( ($nr_files < self::MAX_RECURSE_FILES) && ($this->r_levels > 1) );

        $d = dir($fullOutputPath);
        while (false !== ($entry = $d->read()))
            if ($entry != "." && $entry != "..") {
                $curFile = $fullOutputPath . '/' . $entry;
                if ($this->debug) {
                    echo('--->' . $curFile . "\n");
                }
                if (is_dir($curFile)) {
                    @rmdir($curFile);
                    continue;
                } //this is because 7z also creates the folders....
                $add_file = true;
                $is_archive = false;
                if ($this->getArchiveType($curFile, $this->handle_recursive_gpg, false)) {
                    $is_archive = true;
                    if ($recurse) {
                        $up = new AUnpacker($this, $unpacked_size - ( filesize($curFile) / (1024 * 1024) ), $nr_files - 1);
                        $up->build_files_array = $this->build_files_array;
                        $up->archivePath = $curFile;
                        $up->unpack();
                        if ($up->hadError()) {
                            list($errorCode, $errorOutput) = $up->getError();
                            if ($this->debug)
                                echo ("\nUnpacker for {$curFile} finished with error code {$errorCode} : {$errorOutput}\n");

                            $up_full_path = $up->outputDir . '/' . $up->getSubfolder();
                            unset($output);
                            if ($this->debug)
                                echo("Emptying " . $up_full_path . "\n");
                            SystemHelper::emptyDir($up_full_path);
                        }
                        else {
                            $this->files = array_merge($this->files, $up->getFiles());
                            $add_file = false;
                        }
                    }//if recurse
                }

                if ($add_file && $this->build_files_array) {
                    $nr_files = count($this->files);
                    $this->files[$nr_files]['path'] = $curFile;
                    $this->files[$nr_files]['mime'] = FileHelper::fileMime($curFile);
                    $this->files[$nr_files]['is_archive'] = $is_archive;
                }
            }
        $d->close();

        if (!$this->hadError() && $this->delete_archive) {
            if ($this->debug) {
                echo("Deleting {$this->archivePath}...\n");
            }
            if (!@unlink($this->archivePath)) {
                trigger_error("Unable to delete file {$this->archivePath} !");
            }
        }

        return true;
    }

    //returns the files list
    public function getFiles()
    {
        return $this->files;
    }

    //returns the subfolder
    public function getSubfolder()
    {
        return $this->subfolder;
    }

    //returns true if there is any error code
    public function hadError()
    {
        return (!empty($this->errorCode));
    }

    //returns the error
    public function getError()
    {
        return array($this->errorCode, $this->errorOutput);
    }

    //returns the status of a RAR archive
    private function getRarStat($archive, $password, $returnFullOutput = false)
    {
        if (!empty($password)) {
            $password_command = '-p' . $this->archive_password;
        } else {
            $password_command = '-p-';
        }
        $command = "unrar lt {$password_command} " . self::escapeFilePath($this->archivePath);
        if ($this->debug) {
            echo($command . "\n");
        }
        exec($command . ' 2>&1', $output, $errorCode);
        if ($errorCode) {
            $this->errorCode = "UNRAR:{$errorCode}";
            $this->errorOutput = substr(implode(" ", $output), -200);
            return false;
        }
        if ($returnFullOutput) {
            return implode("\n", $output);
        }

        $output = $output[(count($output) - 2)];
        $output = trim($output);

        if (!preg_match('/\d+\s+\d+\s+\d+\s+\d+%/', $output)) {
            $this->errorCode = '201';
            $this->errorOutput = "Can't recognise unrar output! {$output}";
            return false;
        }

        list($nr_files, $unpacked_size, $packed_size, $ratio) = preg_split("/\s+/", $output);

        return array($nr_files, $unpacked_size);
    }

    //returns the status of a 7z archive
    private function get7zStat($archive, $password, $returnFullOutput = false)
    {
        if (!empty($password)) {
            $password_command = '-p' . $this->archive_password;
        } else {
            $password_command = '';
        }
        $command = "7z l {$password_command} " . self::escapeFilePath($this->archivePath); // self::escapeFilePath();
        if ($this->debug) {
            echo($command . "\n");
        }
        exec($command . ' 2>&1', $output, $errorCode);
        if ($errorCode) {
            $this->errorCode = "7z:{$errorCode}";
            $this->errorOutput = substr(implode(" ", $output), -200);
            return false;
        }
        if ($returnFullOutput) {
            return implode("\n", $output);
        }

        $output = $output[(count($output) - 1)];

        if (!preg_match('/\s+\d+\s+\d+\s+\d+\sfiles,\s\d+\sfolders/', $output)) {
            $this->errorCode = '202';
            $this->errorOutput = "Can't recognise 7z output! {$output}";
            return false;
        }
        list($junk, $unpacked_size, $packed_size, $nr_files) = preg_split("/\s+/", $output);

        return array($nr_files, $unpacked_size);
    }

    //method used to initialize the process
    private function init()
    {
        if (empty($this->archivePath) || empty($this->outputDir)) {
            return false;
        }
        if ($this->check_empty_output_folder && !$this->isEmptyDir($this->outputDir)) {
            return false;
        }
        $this->outputDir = rtrim($this->outputDir, '/');

        $this->errorCode = null;
        $this->errorOutput = null;
        return true;
    }

    //checks if a folder is empty
    private function isEmptyDir($dir)
    {
        //IS DIR
        if (!is_dir($dir)) {
            $this->errorCode = '101';
            $this->errorOutput = 'Output folder ' . $this->outputDir . ' does not exist!';
            return false;
        }

        //IS EMPTY
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if ($entry != "." && $entry != "..") {
                $this->errorCode = '102';
                $this->errorOutput = 'Output folder ' . $this->outputDir . ' is not empty!';
                return false;
            }
        }
        $d->close();

        return true;
    }

    //method to escape the file path
    private static function escapeFilePath($filePath)
    {
        return '"' . str_replace('"', '\\"', $filePath) . '"';
    }

}
