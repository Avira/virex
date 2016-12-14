<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The methods from the SystemCommand are used to operate different jobs over the Virex Project
 * 
 */

class SystemCommand extends ConsoleCommand
{

    public $defaultAction = 'update';

    //method used to update the Virex system
    public function actionUpdate()
    {
		//Clear the cache
		ALogger::log('Clear cache', 'cyan');
        echo "Clear cache\r\n";
		$this->emptyDir('../runtime');
		
        // Update the tables structure to support the sha256
		$sql = "ALTER TABLE `permanent_statistics_ftp_psf`
			CHANGE `files_number_psf` `files_number_psf` int(10) unsigned NULL AFTER `hour_psf`,
			CHANGE `files_size_psf` `files_size_psf` int(10) unsigned NULL AFTER `files_number_psf`,
			CHANGE `archives_number_psf` `archives_number_psf` int(10) unsigned NULL AFTER `files_size_psf`";
		Yii::app()->db->createCommand($sql)->query();
		
		$sql = "ALTER TABLE `permanent_statistics_user_psu`
			CHANGE `files_number_psu` `files_number_psu` int(10) unsigned NULL AFTER `idusr_psu`,
			CHANGE `files_size_psu` `files_size_psu` int(10) unsigned NULL AFTER `files_number_psu`,
			CHANGE `files_in_list_count_psu` `files_in_list_count_psu` int(10) unsigned NULL AFTER `files_size_psu`,
			CHANGE `files_unique_number_psu` `files_unique_number_psu` int(10) unsigned NULL AFTER `files_in_list_count_psu`";
		Yii::app()->db->createCommand($sql)->query();
		
		$sql = "ALTER TABLE `samples_detected_sde`
			CHANGE `detection_sde` `detection_sde` varchar(40) COLLATE 'utf8_general_ci' NULL AFTER `md5_sde`;";
		Yii::app()->db->createCommand($sql)->query();
		
		$sql = "ALTER TABLE `external_users_usr`
			CHANGE `second_public_gpg_key_text_usr` `second_public_gpg_key_text_usr` text COLLATE 'utf8_general_ci' NULL AFTER `rights_url_usr`,
			CHANGE `second_public_gpg_key_name_usr` `second_public_gpg_key_name_usr` varchar(120) COLLATE 'utf8_general_ci' NULL AFTER `second_public_gpg_key_text_usr`;";
		Yii::app()->db->createCommand($sql)->query();
		
        ALogger::log('Updating table samples_clean_scl', 'cyan');
        echo "Updating table samples_clean_scl\r\n";
        $sql = "ALTER TABLE `samples_clean_scl`"
                . "ADD `sha256_scl` char(64) COLLATE 'utf8_general_ci' NULL AFTER `md5_scl`;";
        Yii::app()->db->createCommand($sql)->query();

        ALogger::log('Updating table samples_detected_sde', 'cyan');
        echo "Updating table samples_detected_sde\r\n";
        $sql = "ALTER TABLE `samples_detected_sde`"
                . "ADD `sha256_sde` char(64) COLLATE 'utf8_general_ci' NULL AFTER `md5_sde`;";
        Yii::app()->db->createCommand($sql)->query();

        ALogger::log('Updating table urls_url', 'cyan');
        echo "Updating table urls_url\r\n";
        $sql = "ALTER TABLE `urls_url`"
                . "ADD `sha256_url` char(64) COLLATE 'utf8_general_ci' NULL AFTER `md5_url`;";
        Yii::app()->db->createCommand($sql)->query();
		


        ALogger::log('Updating table user_files_usf', 'cyan');
        echo "Updating table user_files_usf\r\n";
        $sql = "ALTER TABLE `user_files_usf`"
                . "ADD `sha256_usf` char(64) COLLATE 'utf8_general_ci' NULL AFTER `md5_usf`;";
        Yii::app()->db->createCommand($sql)->query();

        //Update the clean samples sha256
        ALogger::log('Updating clean samples sha256', 'cyan');
        echo "Updating clean samples sha256\r\n";
        $samplesClean = SampleClean::model()->findAll();
        foreach ($samplesClean as $sample) {
            $fName = PathFinder::get(VIREX_STORAGE_PATH, 'clean', '') . substr($sample->hex, 0, 3) . '/' . substr($sample->hex, 3, 3) . '/' . substr($sample->hex, 6, 3) . '/' . $sample->hex;
            if (file_exists($fName)) {
                $sample->sha256_scl = hash_file('sha256', $fName);
                $sample->save();
            } else {
                $sample->delete();
            }
        }

        //Update the detected samples sha256
        ALogger::log('Updating detected samples sha256', 'cyan');
        echo "Updating detected samples sha256\r\n";
        $samplesDetected = SampleDetected::model()->findAll();
        foreach ($samplesDetected as $sample) {
            $fName = PathFinder::get(VIREX_STORAGE_PATH, 'detected', '') . substr($sample->hex, 0, 3) . '/' . substr($sample->hex, 3, 3) . '/' . substr($sample->hex, 6, 3) . '/' . $sample->hex;
            if (file_exists($fName)) {
                $sample->sha256_sde = hash_file('sha256', $fName);
                $sample->save();
            } else {
                $sample->delete();
            }
        }
        //Update the URLs sha256
        ALogger::log('Updating URLs sha256', 'cyan');
        $urls = Url::model()->findAll();
        echo "Updating URLs sha256\r\n";
        foreach ($urls as $url) {
            $url->sha256_url = hash('sha256', $url->url_url);
            $url->save();
        }
		
		//Clear the cache
		ALogger::log('Clear cache', 'cyan');
        echo "Clear cache\r\n";
		$this->emptyDir('../runtime');
		
        ALogger::log('Done', 'cyan');
        echo "DONE\r\n";
    }
	
	//Method used to empty a folder recursively
	public function emptyDir($dir) {
		if (is_dir($dir)) {
			$scn = scandir($dir);
			foreach ($scn as $files) {
				if ($files !== '.') {
					if ($files !== '..') {
						if (!is_dir($dir . '/' . $files)) {
							unlink($dir . '/' . $files);
						} else {
							$this->emptyDir($dir . '/' . $files);
							rmdir($dir . '/' . $files);
						}
					}
				}
			}
		}
	}

}
