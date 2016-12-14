<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the helper class for file operations
 */

class FileHelper
{

    //Method used to create a folder
    static function makedir($file, $baseUrl)
    {
        $dir = $baseUrl;
        $dir .= substr($file, 0, 3) . '/';
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $dir .= substr($file, 3, 3) . '/';
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $dir .= substr($file, 6, 3) . '/';
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        return $dir;
    }

    //Method used to return a human readable formatted a size
    static function formatSize($size)
    {
        $mod = 1024;
        $units = explode(' ', 'B KB MB GB TB PB');
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    //Method used to send a download file to the browser
    public static function outputDownloadFile($path, $requestedBy = '', $filename = null)
    {
        if ('false' !== ($fd = fopen($path, "r"))) {
            $fsize = filesize($path);

            $mime_type = self::fileMime($path);

            if (null === $filename)
                $filename = basename($path);

            //Email needs .eml extension for ThunderBird
            if ($requestedBy == 'EmailFile') {
                $filename .= '.eml';
                $mime_type = 'text/x-mail';
            }

            header("Content-Type: {$mime_type}");
            header("Content-Disposition: attachment; filename=\"{$filename}\""); // use 'attachment' to force a download
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header("Content-Length: {$fsize}");

            while (!feof($fd))
                echo fread($fd, 2048);
        }
        fclose($fd);
    }

    //Method used to get the mimetype of a file
    public static function fileMime($file)
    {
        $finfo = finfo_open(FILEINFO_MIME);
        @$mime_type = finfo_file($finfo, $file);
        finfo_close($finfo);

        return $mime_type;
    }

    //Method used to create an archive of files
    public static function packFiles($files, $archive_path = null, $archive_name = null)
    {
        if (null == $archive_name) {
            $archive_name = Yii::app()->user->userId . ".zip";
        }

        @unlink("{$archive_path}/{$archive_name}");

        $files = implode(" ", $files);
        $command = "7z a {$archive_path}/{$archive_name} {$files}";
        exec($command, $output, $errorCode);
        if (!$errorCode) {
            return $archive_name;
        } else {
            Yii::app()->session['pack_error'] = print_r($output, true);
            return false;
        }
    }

    //Method used to create the structure of a folder
    public static function buildPath($path, $root, $dirname = true)
    {
        if ($dirname) {
            $path = dirname($path);
        }
        $path = str_replace($root, '', $path);
        $dirs = explode('/', $path);

        $path = $root;
        foreach ($dirs as $dir)
            if (!empty($dir)) {
                $path .= '/' . $dir;
                if (!is_dir($path)) {
                    if (!@mkdir($path)) {
                        return false;
                    }
                    chmod($path, 0775);
                }
            }
        return true;
    }

    //Method used to decode UU encoded section files
    function &uudecode($input)
    {

        // Find all uuencoded sections
        preg_match_all("/begin ([0-7]{3}) (.+?)\r?\n(.+)\r?\nend/s", $input, $matches);

        for ($j = 0; $j < count($matches[3]); $j++) {

            $str = $matches[3][$j];
            $filename = $matches[2][$j];
            $fileperm = $matches[1][$j];

            $file = '';
            $str = preg_split("/\r?\n/", trim($str));
            $strlen = count($str);

            for ($i = 0; $i < $strlen; $i++) {
                $pos = 1;
                $d = 0;
                $len = (int) (((ord(substr($str[$i], 0, 1)) - 32) - ' ') & 077);

                while (($d + 3 <= $len) AND ( $pos + 4 <= strlen($str[$i]))) {
                    $c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);
                    $c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
                    $c2 = (ord(substr($str[$i], $pos + 2, 1)) ^ 0x20);
                    $c3 = (ord(substr($str[$i], $pos + 3, 1)) ^ 0x20);
                    $file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
                    $file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));
                    $file .= chr(((($c2 - ' ') & 077) << 6) | (($c3 - ' ') & 077));
                    $pos += 4;
                    $d += 3;
                }

                if (($d + 2 <= $len) && ($pos + 3 <= strlen($str[$i]))) {
                    $c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);
                    $c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
                    $c2 = (ord(substr($str[$i], $pos + 2, 1)) ^ 0x20);
                    $file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
                    $file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));
                    $pos += 3;
                    $d += 2;
                }

                if (($d + 1 <= $len) && ($pos + 2 <= strlen($str[$i]))) {
                    $c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);
                    $c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
                    $file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
                }
            }

            $files[] = array('filename' => $filename, 'fileperm' => $fileperm, 'filedata' => $file);
        }

        return $files;
    }

}
