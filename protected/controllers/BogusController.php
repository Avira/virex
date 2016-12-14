<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the controller for the WebUI bogus files management
 */

class BogusController extends Controller
{

    //Displays the index page of the bogus files
    public function actionIndex()
    {
        $this->render('index');
    }

    //the pending samples management
    public function actionSamples_pending()
    {
        if (!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
        $action = $_GET['action'];
        if (isset($_POST['extendedSelect'])) {
            AdvancedSelectableGridView::mapUrlToGET();
            $dataProvider = SampleNew::searchDataProvider(true);
            $criteria = AdvancedSelectableGridView::getExtendedSelectCriteria('SampleNew', $dataProvider);
            if (!$criteria->condition) {
                $criteria->condition = "1=1";
            }
            if ($action == 'delete') {
                $nr = Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Delete' WHERE " . $criteria->condition . " LIMIT 50000")->execute($criteria->params);
                Yii::app()->user->setFlash('_success', $nr . ' samples will be deleted!');
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
            }
        } else {
            throw new CHttpException(400, 'Bad request.');
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

    //The archives management
    public function actionArchives()
    {
        $model = new BogusArchive('search');
        if (isset($_GET['action'])) {
            $id = (int) $_GET['id'];
            switch ($_GET['action']) {
                case 'download':
                    $file = $model->findByPk($id);
                    header("Content-type: application/octet-stream");
                    header("Content-Disposition: attachment; filename=\"" . $file->name_bga . "\"");
                    echo file_get_contents(PathFinder::get(VIREX_STORAGE_PATH, $file->detection_bga, 'bogus', true) . $file->id_bga);
                    die();
                    break;
                case 'delete':
                    Yii::app()->db->createCommand("UPDATE bogus_archives_bga SET pending_action_bga='Delete' WHERE id_bga={$id}")->execute();
                    Yii::app()->user->setFlash('_success', "Archive file will be deleted soon!");
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    break;
            }
        }
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['BogusArchive'])) {
            $model->attributes = $_GET['BogusArchive'];
        }
        $this->render('archives', array(
            'model' => $model
        ));
    }

    //The pending samples management
    public function actionPending()
    {
        $model = new SampleNew('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SampleNew'])) {
            $model->attributes = $_GET['SampleNew'];
        }
        $action = isset($_GET['action']) ? $_GET['action'] : false;
        if ($action == 'delete') {
            Yii::app()->db->createCommand("UPDATE samples_new_sne SET pending_action_sne='Delete' WHERE id_sne=" . (int) $_GET['idf'])->execute();
            Yii::app()->user->setFlash('_success', "Sample file will be deleted soon!");
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }

        $this->render('pending', array('model' => $model, 'status' => 'Pending'));
    }

    //URLs management
    public function actionUrls()
    {
        $model = new BogusArchive('search');
        if (isset($_GET['action'])) {
            $id = (int) $_GET['id'];
            switch ($_GET['action']) {
                case 'download':
                    $file = $model->findByPk($id);
                    header("Content-type: application/octet-stream");
                    header("Content-Disposition: attachment; filename=\"" . $file->name_bga . "\"");
                    echo file_get_contents(PathFinder::get(VIREX_STORAGE_PATH, $file->detection_bga, 'bogus', true) . $file->id_bga);
                    die();
                    break;
                case 'delete':
                    Yii::app()->db->createCommand("UPDATE bogus_archives_bga SET pending_action_bga='Delete' WHERE id_bga=$id")->execute();
                    Yii::app()->user->setFlash('_success', "Urls file will be deleted soon!");
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    break;
            }
        }
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['BogusArchive'])) {
            $model->attributes = $_GET['BogusArchive'];
        }
        $this->render('urls', array(
            'model' => $model
        ));
    }

    //Method used to perform some mass actions on the bogus files
    public function actionBogus_mass()
    {
        if (!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
        $action = $_GET['action'];
        $message = false;
        if (isset($_POST['extendedSelect'])) {
            AdvancedSelectableGridView::mapUrlToGET();
            $dataProvider = Url::searchDataProvider();
            $criteria = AdvancedSelectableGridView::getExtendedSelectCriteria('Url', $dataProvider);
            if (!$criteria->condition) {
                $criteria->condition = "1=1";
            }
            if ($action == 'delete') {
                Yii::app()->db->createCommand("UPDATE bogus_archives_bga SET pending_action_bga='Delete' WHERE {$criteria->condition}")->execute($criteria->params);
            }
        } elseif (isset($_POST['selected']) && is_array($_POST['selected']) && count($_POST['selected'])) {
            foreach ($_POST['selected'] as $k => $v) {
                // value must be integer
                $_POST['selected'][$k] = (int) $v;
            }
            if (count($_POST['selected']) == 1) {
                $condition = "id_bga=" . $_POST['selected'][0];
            } else {
                $condition = "id_bga='" . implode("' OR id_bga='", $_POST['selected']) . '\'';
            }
            $number = Yii::app()->db->createCommand("SELECT count(*) 'nr' FROM bogus_archives_bga WHERE $condition")->queryRow();
            $number = $number['nr'];
			if ($action == 'delete') {
                Yii::app()->db->createCommand("UPDATE bogus_archives_bga SET pending_action_bga='Delete' WHERE {$condition}")->execute();
                Yii::app()->user->setFlash('_success', $number . ' archives will be deleted!');
            }
        } else {
            throw new CHttpException(400, 'Bad request.');
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }

}
