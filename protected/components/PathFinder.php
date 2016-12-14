<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This component class is used to find different paths and to ensure that a given path exists
 */

class PathFinder
{

    //method used to get the path of a file based on its detection type and collection
    public static function get($base, $detection = 'detected', $type = 'daily', $trlS = false)
    {
        $dir = $base . DIRECTORY_SEPARATOR . $detection . DIRECTORY_SEPARATOR . $type;
        self::ensure($dir);
        return $dir . ($trlS ? DIRECTORY_SEPARATOR : '');
    }

    //method used to create a folder if it doesn't exist
    public static function ensure($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

}
