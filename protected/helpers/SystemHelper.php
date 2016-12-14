<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the helper class for local system operations
 */

class SystemHelper
{

    //Method used to get the load status of the current machine
    public static function getLoad()
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = sys_getloadavg();
            $procs = trim(file_get_contents('/proc/cpuinfo'));
            return floor(($uptime[0] / substr_count($procs, 'processor')) * 100);
        } else {
            // windows
            exec('typeperf -sc 1 "\Processor(_Total)\% Processor Time"', $output);
            if (isset($output[2]) && strstr($output[2], ',')) {
                list(, $proc) = explode(',', $output[2]);
                return floor(trim($proc, '"'));
            }
        }
        return 0;
    }

    //Method used to empty a folder
    public static function emptyDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                self::emptyDir($file);
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }

}
