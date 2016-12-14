<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Controller for the WebUI samples and URLs management
 */

class ManageController extends Controller
{

    //Method used to show the general system information
    public function actionIndex($ajax = null)
    {
        $load = SystemHelper::getLoad();
        $totalspace = disk_total_space(VIREX_STORAGE_PATH);
        $freespace = disk_free_space(VIREX_STORAGE_PATH);
        $slov = new ProgressBar(100, $load, 150);
        $slov->suff = '';
        $slov->doneBg = '#9BB947';
        $duov = new ProgressBar($totalspace, $totalspace - $freespace, 150);
        $duov->suff = '';
        $duov->doneBg = '#9BB947';
        $sysHealth = array(
            array('id' => 2, 'Property' => 'Operating System', 'Value' => PHP_OS, 'Overview' => ''),
            array('id' => 3, 'Property' => 'Web server', 'Value' => $_SERVER["SERVER_SOFTWARE"], 'Overview' => ''),
            array('id' => 4, 'Property' => 'PHP version', 'Value' => PHP_VERSION, 'Overview' => ''),
            array('id' => 5, 'Property' => 'MySQL version', 'Value' => Yii::app()->db->serverVersion, 'Overview' => ''),
            array('id' => 1, 'Property' => 'Storage space', 'Value' => FileHelper::formatSize($totalspace - $freespace) . ' / ' . FileHelper::formatSize($totalspace), 'Overview' => $duov->display()),
            array('id' => 6, 'Property' => 'System load / CPU usage', 'Value' => '', 'Overview' => $slov->display()),
        );
        $this->render('index', array(
            'systemHealth' => new CArrayDataProvider($sysHealth)
        ));
    }

    //Method used to set the items number for one page
    public function actionPer_page()
    {
        $_SESSION['files_per_page'] = $_POST['per_page'];
        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    //Method used to manage the detected samples
    public function actionSamples_detected()
    {
        if (!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
        $action = $_GET['action'];
        if (isset($_POST['extendedSelect'])) {
            AdvancedSelectableGridView::mapUrlToGET();
            $dataProvider = SampleDetected::searchDataProvider();
            $criteria = AdvancedSelectableGridView::getExtendedSelectCriteria('SampleDetected', $dataProvider);

            if (!$criteria->condition) {
                $criteria->condition = "1=1";
            }
            if ($action == 'rescan') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET pending_action_sde='Rescan' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be rescan!');
            } elseif ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET pending_action_sde='Delete' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
            } elseif ($action == 'enable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET enabled_sde=1 WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples have been enabled!');
            } elseif ($action == 'disable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET enabled_sde=0 WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples have been disabled!');
            }
        } elseif (isset($_POST['selected']) && is_array($_POST['selected']) && count($_POST['selected'])) {
            foreach ($_POST['selected'] as $k => $v) {
                // value must be integer
                $_POST['selected'][$k] = (int) $v;
            }
            if (count($_POST['selected']) == 1) {
                $condition = "id_sde=" . $_POST['selected'][0];
            } else {
                $condition = "id_sde='" . implode("' OR id_sde='", $_POST['selected']) . '\'';
            }
            if ($action == 'rescan') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET pending_action_sde='Rescan' WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples will be rescan!');
            } elseif ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET pending_action_sde='Delete' WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
            } elseif ($action == 'enable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET enabled_sde=1 WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples have been enabled!');
            } elseif ($action == 'disable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_detected_sde SET enabled_sde=0 WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples have been disabled!');
            }
        } else {
            throw new CHttpException(400, 'Bad request.');
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

    //Method used to manage the clean samples
    public function actionSamples_clean()
    {
        if (!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
        $action = $_GET['action'];
        if (isset($_POST['extendedSelect'])) {
            AdvancedSelectableGridView::mapUrlToGET();
            $dataProvider = SampleClean::searchDataProvider();
            $criteria = AdvancedSelectableGridView::getExtendedSelectCriteria('SampleClean', $dataProvider);
            if (!$criteria->condition) {
                $criteria->condition = "1=1";
            }
            if ($action == 'rescan') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET pending_action_scl='Rescan' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be rescan!');
            } elseif ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET pending_action_scl='Delete' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
            } elseif ($action == 'enable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET enabled_scl=1 WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples have been enabled!');
            } elseif ($action == 'disable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET enabled_scl=0 WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples have been disabled!');
            }
        } elseif (isset($_POST['selected']) && is_array($_POST['selected']) && count($_POST['selected'])) {
            foreach ($_POST['selected'] as $k => $v) {
                // value must be integer
                $_POST['selected'][$k] = (int) $v;
            }
            if (count($_POST['selected']) == 1) {
                $condition = "id_scl=" . $_POST['selected'][0];
            } else {
                $condition = "id_scl='" . implode("' OR id_scl='", $_POST['selected']) . '\'';
            }
            if ($action == 'rescan') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET pending_action_scl='Rescan' WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples will be rescan!');
            } elseif ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET pending_action_scl='Delete' WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
            } elseif ($action == 'enable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET enabled_scl=1 WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' have been enabled!');
            } elseif ($action == 'disable') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_clean_scl SET enabled_scl=0 WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' have been disabled!');
            }
        } else {
            throw new CHttpException(400, 'Bad request.');
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

    //Method used to manage the pending samples
    public function actionSamples_pending()
    {
        if (!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
        $action = $_GET['action'];
        if (isset($_POST['extendedSelect'])) {
            AdvancedSelectableGridView::mapUrlToGET();
            $dataProvider = SampleNew::searchDataProvider();
            $criteria = AdvancedSelectableGridView::getExtendedSelectCriteria('SampleNew', $dataProvider);
            if (!$criteria->condition) {
                $criteria->condition = "1=1";
            }
            if ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Delete' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
            } elseif ($action == 'rescan') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Rescan' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be rescan!');
            }
        } elseif (isset($_POST['selected']) && is_array($_POST['selected']) && count($_POST['selected'])) {
            foreach ($_POST['selected'] as $k => $v) {
                // value must be integer
                $_POST['selected'][$k] = (int) $v;
            }
            if (count($_POST['selected']) == 1) {
                $condition = "id_sne=" . $_POST['selected'][0];
            } else {
                $condition = "id_sne='" . implode("' OR id_sne='", $_POST['selected']) . '\'';
            }
            if ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Delete' WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
            } elseif ($action == 'rescan') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Rescan' WHERE " . $condition . " LIMIT 50000")->execute();
                Yii::app()->user->setFlash('_success', $nr . ' samples will be rescan!');
            }
        } else {
            throw new CHttpException(400, 'Bad request.');
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

    //Method used to manage the samples
    public function actionSamples()
    {
        if (isset($_POST['interval'])) {
            $_SESSION['interval_start'] = $_POST['interval_start'] . ' 00:00:00';
            $_SESSION['interval_end'] = $_POST['interval_end'] . ' 23:59:59';
        } elseif (isset($_POST['interval_reset'])) {
            unset($_SESSION['interval_start']);
            unset($_SESSION['interval_end']);
        }
        if (!isset($_GET['status'])) {
            $status = 'detected';
        } else {
            $status = $_GET['status'];
        }
        switch ($status) {
            case 'detected':
                $model = new SampleDetected('search');
                $model->unsetAttributes();  // clear any default values
                if (isset($_GET['SampleDetected']))
                    $model->attributes = $_GET['SampleDetected'];
                $action = isset($_GET['action']) ? $_GET['action'] : false;
                if ($action == 'rescan') {
                    Yii::app()->db->createCommand("UPDATE samples_detected_sde SET pending_action_sde='Rescan' WHERE id_sde=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                } elseif ($action == 'delete') {
                    Yii::app()->db->createCommand("UPDATE samples_detected_sde SET pending_action_sde='Delete' WHERE id_sde=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                } elseif ($action == 'enable') {
                    Yii::app()->db->createCommand("UPDATE samples_detected_sde SET enabled_sde=1 WHERE id_sde=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                } elseif ($action == 'disable') {
                    Yii::app()->db->createCommand("UPDATE samples_detected_sde SET enabled_sde=0 WHERE id_sde=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                }
                break;
            case 'clean':
                $model = new SampleClean('search');
                $model->unsetAttributes();  // clear any default values
                if (isset($_GET['SampleClean']))
                    $model->attributes = $_GET['SampleClean'];
                $action = isset($_GET['action']) ? $_GET['action'] : false;
                if ($action == 'rescan') {
                    Yii::app()->db->createCommand("UPDATE samples_clean_scl SET pending_action_scl='Rescan' WHERE id_scl=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                } elseif ($action == 'delete') {
                    Yii::app()->db->createCommand("UPDATE samples_clean_scl SET pending_action_scl='Delete' WHERE id_scl=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                } elseif ($action == 'enable') {
                    Yii::app()->db->createCommand("UPDATE samples_clean_scl SET enabled_scl=1 WHERE id_scl=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                } elseif ($action == 'disable') {
                    Yii::app()->db->createCommand("UPDATE samples_clean_scl SET enabled_scl=0 WHERE id_scl=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                }
                break;
            case 'pending':
                $model = new SampleNew('search');
                $model->unsetAttributes();  // clear any default values
                if (isset($_GET['SampleNew']))
                    $model->attributes = $_GET['SampleNew'];
                $action = isset($_GET['action']) ? $_GET['action'] : false;
                if ($action == 'delete') {
                    Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Delete' WHERE id_sne=" . (int) $_GET['idf'])->execute();
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    die();
                }
                break;
        }
        $this->render('samples', array(
            'status' => $status,
            'model' => $model,
            'per_page' => isset($_SESSION['files_per_page']) ? $_SESSION['files_per_page'] : 250
        ));
    }

    //Method used to download a sample
    public function actionDownload()
    {
        $type = $_GET['type'];
        $md5 = $_GET['md5'];
        if ($type == 'detected') {
            $file = Yii::app()->db->createCommand()->select('hex(md5_sde) "hex"')->from('samples_detected_sde')->where('md5_sde =:md5', array(':md5' => $md5))->queryRow();
            if ($file) {
                $fName = $file['hex'];
                $file = PathFinder::get(VIREX_STORAGE_PATH, $type, '') . substr($fName, 0, 3) . '/' . substr($fName, 3, 3) . '/' . substr($fName, 6, 3) . '/' . $fName;
                header("Content-type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"" . $md5 . "\"");
                echo file_get_contents($file);
                die();
            }
        } elseif ($type == 'clean') {
            $file = Yii::app()->db->createCommand()->select('hex(md5_scl) "hex"')->from('samples_clean_scl')->where('md5_scl =:md5', array(':md5' => $md5))
                    ->queryRow();
            if ($file) {
                $fName = $file['hex'];
                $file = PathFinder::get(VIREX_STORAGE_PATH, $type, '') . substr($fName, 0, 3) . '/' . substr($fName, 3, 3) . '/' . substr($fName, 6, 3) . '/' . $fName;
                header("Content-type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"" . $md5 . "\"");
                echo file_get_contents($file);
                die();
            }
        }
    }

    //Method used to manage the URLs
    public function actionUrls()
    {
        if (isset($_POST['interval'])) {
            $_SESSION['interval_start'] = $_POST['interval_start'] . ' 00:00:00';
            $_SESSION['interval_end'] = $_POST['interval_end'] . ' 23:59:59';
        } elseif (isset($_POST['interval_reset'])) {
            unset($_SESSION['interval_start']);
            unset($_SESSION['interval_end']);
        }
        if (isset($_POST['add'])) {
            $urlScanner = new AUrlArchive();
            $urlScanner->add_new();
        }
        $action = isset($_GET['action']) ? $_GET['action'] : false;
        if ($action == 'delete') {
            Url::model()->deleteByPk($_GET['idf']);
        } elseif ($action == 'enable') {
            Yii::app()->db->createCommand("UPDATE urls_url SET enabled_url=1 WHERE id_url=" . (int) $_GET['idf'])->execute();
        } elseif ($action == 'disable') {
            Yii::app()->db->createCommand("UPDATE urls_url SET enabled_url=0 WHERE id_url=" . (int) $_GET['idf'])->execute();
        }
        $model = new Url('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Url']))
            $model->attributes = $_GET['Url'];
        $this->render('urls', array(
            'model' => $model
        ));
    }

    //Method used to apply different action on more URLs once
    public function actionUrls_mass()
    {
        if (!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
        $action = $_GET['action'];
        if (isset($_POST['extendedSelect'])) {
            AdvancedSelectableGridView::mapUrlToGET();
            $dataProvider = Url::searchDataProvider();
            $criteria = AdvancedSelectableGridView::getExtendedSelectCriteria('Url', $dataProvider);
            if (!$criteria->condition) {
                $criteria->condition = "1=1";
            }
            if ($action == 'download') {
                Url::model()->downloadUrls($criteria->condition, $criteria->params);
            } elseif ($action == 'delete') {
                Url::model()->deleteUrls($criteria->condition, $criteria->params);
            } elseif ($action == 'enable') {
                Yii::app()->db->createCommand("UPDATE urls_url SET enabled_url=1 WHERE " . $criteria->condition)->execute($criteria->params);
            } elseif ($action == 'disable') {
                Yii::app()->db->createCommand("UPDATE urls_url SET enabled_url=0 WHERE " . $criteria->condition)->execute($criteria->params);
            }
        } elseif (isset($_POST['selected']) && is_array($_POST['selected']) && count($_POST['selected'])) {
            foreach ($_POST['selected'] as $k => $v) {
                // value must be integer
                $_POST['selected'][$k] = (int) $v;
            }
            if (count($_POST['selected']) == 1) {
                $condition = "id_url=" . $_POST['selected'][0];
            } else {
                $condition = "id_url='" . implode("' OR id_url='", $_POST['selected']) . '\'';
            }
            if ($action == 'download') {
                Url::model()->downloadUrls($condition);
            } elseif ($action == 'delete') {
                Url::model()->deleteUrls($condition);
            } elseif ($action == 'enable') {
                Yii::app()->db->createCommand("UPDATE urls_url SET enabled_url=1 WHERE " . $condition)->execute();
            } elseif ($action == 'disable') {
                Yii::app()->db->createCommand("UPDATE urls_url SET enabled_url=0 WHERE " . $condition)->execute();
            }
        } else {
            throw new CHttpException(400, 'Bad request.');
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

}
