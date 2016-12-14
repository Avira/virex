<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Controller for the WebUI external users management
 */

class ExternalUserController extends Controller
{

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public function actionEnable($id)
    {
        $m = $this->loadModel($id);
        $oldStat = $m->status_usr;
        $m->status_usr = 2;
        ExternalUserHistory::addLog('Account enabled by ' . Yii::app()->user->name . '!', $m->id_usr);
        $m->save(false);
        $email = '';
        if ($oldStat == 1) {
            ExternalUser::send_enable_account_email($m->email_usr, $m->name_usr, $id);
            $email = 'Also an email has been send to notify him.';
        }
        Yii::app()->user->setFlash('_success', "User account has been enabled! $email");
        $this->redirect(Yii::app()->createAbsoluteUrl("externaluser/update/$id"));
    }

    //Method used to disable a user
    public function actionDisable($id)
    {
        $m = $this->loadModel($id);
        $m->status_usr = 3;
        ExternalUserHistory::addLog('Account disabled by ' . Yii::app()->user->name . '!', $m->id_usr);
        $m->save(false);
        Yii::app()->user->setFlash('_success', "User account has been disabled!");
        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($model);

        if (isset($_POST['ExternalUser'])) {
            if ($_POST['ExternalUser']['new_password'] == $_POST['ExternalUser']['confirm_new_password']) {
                if (trim($_POST['ExternalUser']['new_password'])) {
                    ExternalUserHistory::addLog('Password changed by ' . Yii::app()->user->name . '!', $model->id_usr);
                }
            }
            $model->attributes = $_POST['ExternalUser'];
            $model->save();
            Yii::app()->user->setFlash('_success', "User account has been updated!");
        }

        $model->new_password = '';
        $model->confirm_new_password = '';

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        if (Yii::app()->request->isPostRequest) {
            // we only allow deletion via POST request
            $this->loadModel($id)->delete();
            ExternalUserHistory::addLog('Account deleted by ' . Yii::app()->user->name . '!', $id);
            Yii::app()->user->setFlash('_success', "User account has been deleted!");
            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if (!isset($_GET['ajax'])) {
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
            }
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }

    /**
     * Manages all models.
     */
    public function actionAdmin()
    {
        $model = new ExternalUser('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['ExternalUser'])) {
            $model->attributes = $_GET['ExternalUser'];
        }
        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id)
    {
        $model = ExternalUser::model()->findByPk((int) $id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param CModel the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'external-user-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    //Method used to display the statistics of a user
    public function actionStatistics($id)
    {
        Yii::app()->db->createCommand("SET sql_mode = ''")->execute();
        $model = ExternalUser::model()->findByPk((int) $id);
        $action = (isset($_GET['action'])) ? $_GET['action'] : false;
        $list = false;
        if ($action == 'details') {
            $list = (int) $_GET['list_id'];
            $list = Yii::app()->db->createCommand("SELECT * FROM user_lists_usl WHERE id_usl=$list")->queryRow();
        }
        $this->render('statistics', array('userModel' => $model, 'action' => $action, 'selected_list' => $list));
    }

    //Method used to display the history of a user
    public function actionHistory($id)
    {
        $dataProvider = new CActiveDataProvider('ExternalUserHistory', array(
            'criteria' => array(
                'condition' => 'idusr_euh=' . $id,
                'order' => 'time_euh DESC'
            ),
            'pagination' => array(
                'pageSize' => 15,
            )
        ));
        $this->render('history', array(
            'userId' => $id,
            'dataProvider' => $dataProvider
        ));
    }

    //Method used to reactivate a user
    public function actionReactivate($id)
    {
        if (ExternalUser::model()->findByPk($id)->sendActivationLink()) {
            Yii::app()->user->setFlash('_success', "Activation link has been sent to selected user!");
            $this->redirect($_SERVER['HTTP_REFERER']);
        } else {
            Yii::app()->user->setFlash('_error', "User not found!");
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
    }

}
