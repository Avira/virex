<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Controller for the WebUI internal users management
 */

class InternalUserController extends Controller
{

    public $defaultAction = 'admin';

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new InternalUser('register');

        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($model);

        if (isset($_POST['InternalUser'])) {
            $model->attributes = $_POST['InternalUser'];
            if ($model->save()) {
                $this->redirect(array('internaluser/admin'));
            }
        }
        $this->headlineText = 'New user';
        $this->render('_form', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        $this->performAjaxValidation($model);

        if (isset($_POST['InternalUser'])) {
            $model->attributes = $_POST['InternalUser'];
            if (isset($_POST['password_change_request']) && $_POST['password_change_request'] == '1') {
                $model->requestedNewPassword = 1;
            }
            if ($model->save()) {
                Yii::app()->user->setFlash('_success', "The account has been successfully updated!");
            }
        }
        $this->headlineText = 'Update user';
        $this->render('_form', array(
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
            Yii::app()->user->setFlash('_success', "Internal user account has been deleted!");
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
        $model = new InternalUser('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['InternalUser'])) {
            $model->attributes = $_GET['InternalUser'];
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
        $model = InternalUser::model()->findByPk((int) $id)->with(array('admin'));
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
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'internal-user-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
