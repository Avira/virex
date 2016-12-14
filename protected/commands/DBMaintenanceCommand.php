<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The methods are used to keep the database in optimal conditions
 * 
 */

class DBMaintenanceCommand extends ConsoleCommand
{

    //Method used to clear the 6 months history for any user
    public function actionClear_history()
    {
        $nr1 = Yii::app()->db->createCommand("DELETE FROM user_files_usf WHERE date_usf<=SUBDATE(NOW(), INTERVAL 6 MONTH)")->execute();
        ALogger::log($nr1 . ' files deleted from stats');
        $nr2 = Yii::app()->db->createCommand("DELETE FROM user_lists_usl WHERE date_usl<=SUBDATE(NOW(), INTERVAL 6 MONTH)")->execute();
        ALogger::log($nr2 . ' lists deleted from stats');
        ALogger::empty_line();
    }

    //Method used to update the statistics table
    public function actionUpdate_stats()
    {
        $vars = $this->getGeneralVariables();
        $last_update = $vars['last_update_for_unique_files_stats'];
        $stats = Yii::app()->db->createCommand("
            SELECT COUNT(DISTINCT md5_usf) ':number', HOUR(date_usf) ':hour', DATE(date_usf) ':date', idusr_usf ':idusr' FROM user_files_usf
            WHERE date_usf >= '$last_update' AND count_usf>0
            AND NOT EXISTS (SELECT 1 FROM user_files_usf as us WHERE us.date_usf < '$last_update' AND us.md5_usf = user_files_usf.md5_usf
            AND us.idusr_usf = user_files_usf.idusr_usf)
            GROUP BY idusr_usf, DATE(date_usf), HOUR(date_usf)")->queryAll();
        ALogger::log(count($stats) . " updates!");
        $updateStatsCommand = Yii::app()->db->createCommand("
            INSERT INTO permanent_statistics_user_psu (date_psu, hour_psu, idusr_psu, files_number_psu, files_size_psu, files_in_list_count_psu,
                files_unique_number_psu) VALUES (:date, :hour, :idusr, 0, 0, 0, :number) ON DUPLICATE KEY UPDATE 
                files_unique_number_psu=files_unique_number_psu + :number");
        foreach ($stats as $s) {
            $updateStatsCommand->execute($s);
        }
        $vars['last_update_for_unique_files_stats'] = date('Y-m-d H:i:s');
        $this->updateVariables($vars);
    }

    //Method used to remove the fake external users requests
    public function actionDelete_fake_emails()
    {
        $nr = Yii::app()->db->createCommand('DELETE FROM external_users_usr WHERE register_date_usr < SUBDATE(NOW(), INTERVAL 2 DAY) AND status_usr = 0')->execute();
        ALogger::log($nr . ' users deleted!');
        ALogger::empty_line();
    }

}
