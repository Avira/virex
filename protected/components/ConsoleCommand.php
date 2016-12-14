<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the component class used to work with the commands
 */

abstract class ConsoleCommand extends CConsoleCommand
{

    public $defaultAction = 'start';

    const PROCESS_TIME = 25000;
    const LOCK_EXPIRE_TIME = 25000;

    public $debug = 0;
    protected $nrGood = 0;
    protected $nrBad = 0;

    //Method used to check if the locking file exists
    public function checkLock($dir, $quiet = false, $return = false)
    {
        if (false !== ($lockedTime = @file_get_contents($dir . '_lockfile'))) {
            if ((time() - $lockedTime) < self::LOCK_EXPIRE_TIME) {
                if ($quiet) {
                    die();
                }
                if ($return) {
                    return true;
                }
                if (!$quiet) {
                    echo('Dir is locked! @ ' . date('Y-m-d H:i:s', $lockedTime));
                }
                exit();
            }
        }
    }

    //Method used to set the locking file
    public function setLock($dir)
    {
        set_time_limit(self::PROCESS_TIME);
        file_put_contents($dir . '_lockfile', time());
    }

    //Method used to remove the locking file
    public function clearLock($dir)
    {
        if (file_exists($dir . '_lockfile')) {
            unlink($dir . '_lockfile');
        }
    }

    //method used as a before action trigger
    protected function beforeAction($action, $params)
    {
        if ($this->debug) {
            ALogger::$debug = true;
        }
        return true;
    }

    //method used to get the variables values from the database
    public function getGeneralVariables()
    {
        $in = Yii::app()->db->createCommand("SELECT * FROM variables_vrb")->queryAll();
        $info = array();
        foreach ($in as $i) {
            $info[$i['name_vrb']] = $i['value_vrb'];
        }
        // init
        /// remembers error count for current week ( this value is later send in weekly newsletter )
        $info['this_week_error_count'] = (isset($info['this_week_error_count'])) ? $info['this_week_error_count'] : 0;
        // remembers the time when last email that announced an archive error was send(so that it will wait at least an hour before it sends the next one)
        $info['last_archive_error_email'] = isset($info['last_archive_error_email']) ? $info['last_archive_error_email'] : '2011-01-01 01:01:00';
        // remembers the time when last email that announced an pgp error was send(so that it will wait at least an hour before it sends the next one)
        $info['last_pgp_error_email'] = isset($info['last_pgp_error_email']) ? $info['last_pgp_error_email'] : '2011-01-01 01:01:00';
        // remembers last error id that was send in email( so that it knows when a new one appears and needs to send a new email)
        $info['last_archive_error_id'] = isset($info['last_archive_error_id']) ? $info['last_archive_error_id'] : 0;
        // remembers the time when "this_week_error_count" must reset.
        $info['next_week_reset_time'] = isset($info['next_week_reset_time']) ? $info['next_week_reset_time'] : '2011-01-01 01:01:00';
        // remembers the time when count of distinct downloaded files by user was executed and saved in permanent stats table
        $info['last_update_for_unique_files_stats'] = isset($info['last_update_for_unique_files_stats']) ? $info['last_update_for_unique_files_stats'] : '2011-01-01 01:01:00';
        // last clean samples error
        $info['last_clean_samples_email'] = isset($info['last_clean_samples_email']) ? $info['last_clean_samples_email'] : '2011-01-01 01:01:00';
        $info['last_error_samples_email'] = isset($info['last_error_samples_email']) ? $info['last_error_samples_email'] : '2011-01-01 01:01:00';
        $info['last_error_id_samples_email'] = isset($info['last_error_id_samples_email']) ? $info['last_error_id_samples_email'] : 1;
        return $info;
    }

    //method used to update the variables values from the database
    public function updateVariables($info)
    {
        $updateCommand = Yii::app()->db->createCommand("INSERT INTO variables_vrb (name_vrb, value_vrb) VALUES (:name,:value) ON DUPLICATE KEY UPDATE
																																								value_vrb=:value");
        foreach ($info as $k => $v) {
            $updateCommand->execute(array(':name' => $k, ':value' => $v));
        }
    }

}
