<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The methods from the ArchivesCommand are used to process the incoming archive files
 * 
 */

class ArchivesCommand extends ConsoleCommand
{

    /**
     * Just display the archives found in the incoming folders
     * 
     * @param string $detection
     * @param string $type
     * @param bool $help 
     */
    public function actionShow($detection = '', $type = '', $help = false)
    {
        if (!in_array($detection, array('', 'detected', 'clean'))) {
            $help = true;
        }
        if (!in_array($type, array('', 'daily', 'monthly', 'bogus', 'url'))) {
            $help = true;
        }

        if ($help) {
            echo "
USAGE EXAMPLE:
    archives show                                       // shows all files from all folders
    archives show --type=daily                          // shows all files from daily folders (both detected and clean)
    archives show --detection=clean --type=monthly      // shows all files from clean monthly folder

OPTIONS:
    detection = [clean|detected]
    type = [daily|monthly|bogus|url]
";
            die();
        }

        $this->pending_actions();

        if ($detection) {
            $dees = array($detection);
        } else {
            $dees = array('detected', 'clean');
        }
        if ($type) {
            $tees = array($type);
        } else {
            $tees = array('daily', 'monthly', 'url', 'bogus');
        }

        $archiver = new AFileArchive();
        $urchiver = new AUrlArchive();
        foreach ($tees as $tee) {
            foreach ($dees as $dee) {
                if ($tee == 'url') {
                    $files = $urchiver->return_all();
                } else {
                    $files = $archiver->scan_folders($dee, $tee);
                }
                $this->show_files($files, ucfirst($tee) . ' ' . $dee);
            }
        }
    }

    // display helper
    private function show_files($list, $title)
    {
        echo "\n{$title} files: \n";
        foreach ($list as $f) {
            echo " + {$f}\n";
        }
        echo " = " . count($list) . " found.\n";
    }

    // before any operation, try to run the pending operations
    private function pending_actions()
    {
        ALogger::step('PENDING ACTIONS');
        BogusArchive::deleteWhereCondition("pending_action_bga = 'delete'");
        ALogger::end_step();
    }

    /**
     * Process the archives found in the incoming folders
     * 
     * @param string $detection
     * @param string $type
     * @param bool $help
     * @param bool $clearlock
     * @return void 
     */
    public function actionProcess($detection = '', $type = '', $help = false, $clearlock = false)
    {
        if (!in_array($detection, array('', 'detected', 'clean'))) {
            $help = true;
        }
        if (!in_array($type, array('', 'daily', 'monthly', 'bogus', 'url'))) {
            $help = true;
        }
        if ($help) {
            echo "
USAGE EXAMPLE:
    archives process                                        // extracts all files from all archives
    archives process --type=daily                           // extracts all files from daily archives (both detected and clean)
    archives process --detection=clean --type=monthly       // extracts all files from clean monthly archives

OPTIONS:
    detection = [clean|detected]
    type = [daily|monthly|bogus|url]
";
            die();
        }

        if (!$this->handleFolderLock(VIREX_INCOMING_PATH, $clearlock)) {
            return;
        }

        $this->pending_actions();
        $errors_count = 0;

        if ($detection) {
            $dees = array($detection);
        } else {
            $dees = array('detected', 'clean');
        }
        if ($type) {
            $tees = array($type);
        } else {
            $tees = array('daily', 'monthly', 'url', 'bogus');
        }

        $archiver = new AFileArchive();
        $urchiver = new AUrlArchive();
        foreach ($tees as $tee) {
            foreach ($dees as $dee) {
                if ($tee == 'url') {
                    $urchiver->add_new();
                }
                if (in_array($tee, array('daily', 'monthly'))) {
                    $files = $archiver->process_archives($dee, $tee);
                    $this->show_files($files, ucfirst($tee) . ' ' . $dee);
                }
            }
        }

        $this->actionSendemails();
        ALogger::end_step();
        $this->clearLock(VIREX_INCOMING_PATH);
    }

    /*
     * Method for sending statistic emails
     */

    public function actionSendemails()
    {
        $vars = $this->getGeneralVariables();
        if ($vars['last_archive_error_email'] < date('Y-m-d H:i:s', mktime(date('H') - 1))) {
            $lastError = Yii::app()->db->createCommand("SELECT id_bga FROM bogus_archives_bga ORDER BY id_bga DESC LIMIT 0,1")->queryRow();
            $lastError = $lastError['id_bga'];
            if ($lastError > $vars['last_archive_error_id']) {
                $vars['last_archive_error_id'] = $lastError;
                $vars['last_archive_error_email'] = date('Y-m-d H:i:s');
                $errors = Yii::app()->db->createCommand("SELECT count(*) 'n' FROM bogus_archives_bga")->queryRow();
                $errors = $errors['n'];
                $baseUrl = VIREX_URL;
                $admins = Yii::app()->db->createCommand("SELECT fname_uin, email_uin FROM internal_users_uin WHERE notification_pgp_error_uin=1")->queryAll();
                $endMessage = VIREX_MAIL_SIGNATURE;
                foreach ($admins as $a) {
                    $message = <<<MESSAGE
Dear $a[fname_uin],

There are currently $errors errors waiting.

Click here to manage bogus archives:
$baseUrl/bogus/archives

$endMessage
MESSAGE;
                    $subject = '[Virex] Error Notification';
                    @mail($a['email_uin'], $subject, $message);
                }
            }
        }
        $this->updateVariables($vars);
    }

    // Method used for handling the Locking file
    private function handleFolderLock($folder, $clearlock = false)
    {
        if ($clearlock) {
            $this->clearLock($folder);
        }
        if ($this->checkLock($folder, false, true)) {
            ALogger::error("{$folder} locked!");
            return false;
        }
        $this->setLock($folder);
        return true;
    }

}
