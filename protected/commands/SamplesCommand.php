<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The methods from the SamplesCommand are used to operate different jobs over the samples 
 * 
 */

class SamplesCommand extends ConsoleCommand
{

    //method used to remove the files pending for deletion
    public function actionPending()
    {
        /// DETECTED SAMPLES ACTIONS
        ALogger::log('detected files...', 'cyan');
        $m_detected = new SampleDetected('search');
        $m_detected->deleteFiles("pending_action_sde = 'delete'");
        unset($m_detected);

        /// CLEAN SAMPLES ACTIONS
        ALogger::log('clean files...', 'cyan');
        $m_undetected = new SampleClean('search');
        $m_undetected->deleteFiles("pending_action_scl = 'delete'");
        unset($m_undetected);
    }

    /**
     * Send a weekely email containing stats about what happenend last week
     * @param <type> $nomail - for debug only.. to show message and not send
     */
    public function actionWeeklystats($nomail = false)
    {
        // selecting clean files (count and size)
        $clean = Yii::app()->db->createCommand("SELECT count(*) 'nr', SUM(file_size_scl) 'size' FROM samples_clean_scl WHERE added_when_scl > SUBDATE(CURDATE(), INTERVAL 7 DAY)")->queryRow();
        // selecting detected files (count and size)
        $detected = Yii::app()->db->createCommand("SELECT count(*) 'nr', SUM(file_size_sde) 'size' FROM samples_detected_sde WHERE added_when_sde > SUBDATE(CURDATE(), INTERVAL 7 DAY)")->queryRow();
        // selecting users stats
        $download = Yii::app()->db->createCommand("SELECT sum(count_usf) 'nr', sum(file_size_usf*count_usf) 'size' FROM user_files_usf WHERE date_usf > SUBDATE(CURDATE(), INTERVAL 7 DAY)")->queryRow();
        $download['nr'] = (int) $download['nr'];
        $bogus = $this->getGeneralVariables(); // reading general variables( for error count )
        $bogus = $bogus['this_week_error_count'];
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $current_date = date('Y-m-d');
        $totalNr = $clean['nr'] + $detected['nr'];
        $totalSize = $clean['size'] + $detected['size'];

        $content = "VIREX statistics for: {$start_date} - {$current_date}\r\n\r\n";

        $content .= "Downloaded samples: " . $this->pad($download['nr']) . " / " . $this->size($download['size']) . "\r\n";
        $content .= "Uploaded samples: " . $this->pad($totalNr) . " /  " . $this->size($totalSize) . "\r\n\r\n";

        $content .= "Detected samples: " . $this->pad($detected['nr']) . " / " . $this->size($detected['size']) . "\r\n";
        $content .= "Clean samples: " . $this->pad($clean['nr']) . " / " . $this->size($clean['size']) . "\r\n\r\n";
        $content .= "Errors in uploads: " . $this->pad($bogus) . "\r\n";

        $content .= VIREX_MAIL_SIGNATURE;

        if ($this->debug) {
            echo $content;
        }
        if ($nomail) {
            return;
        }
        // getting admin emails to send messages ( all admins receive this email )
        $mails = Yii::app()->db->createCommand("SELECT email_uin, fname_uin FROM internal_users_uin")->queryAll();
        $subject = '[Virex] Weekly statistics ' . $start_date . ' - ' . $current_date;
        $addresses = array();
        foreach ($mails as $e) {
            $addresses[] = $e['email_uin'];
        }
        @mail(implode(',', $addresses), $subject, $content);
    }

    //method used to format a size
    private function size($size)
    { // shortcut to FileHelper static method formatSize()
        return $this->pad(FileHelper::formatSize($size));
    }

    //method used to dad a string to a certain length with another string
    private function pad($input)
    {
        return $input;
    }

}
