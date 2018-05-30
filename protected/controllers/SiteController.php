<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Controller for the WebUI general actions
 */

class SiteController extends Controller
{

    /**
     * Declares class-based actions.
     */
    public function actions()
    {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class' => 'CCaptchaAction',
                'backColor' => 0xFFFFFF,
            ),
        );
    }

    //Method used for downloading the client
    public function actionDownload_client()
    {
        $afterDownload = false;
        if (isset($_POST['download'])) {
            $currentUser = Yii::app()->db->createCommand("SELECT * FROM external_users_usr WHERE id_usr=" . Yii::app()->user->userId)->queryRow();
            if ($currentUser['password_usr'] == ExternalUser::model()->passwordHash($_POST['password'])) {
                $afterDownload = true;
                $_SESSION['password'] = $_POST['password'];
            } else {
                Yii::app()->user->setFlash('_error', 'Wrong password!');
            }
        }
        if (isset($_GET['download'])) {
            $currentUser = Yii::app()->db->createCommand("SELECT * FROM external_users_usr WHERE id_usr=" . Yii::app()->user->userId)->queryRow();
            if ($currentUser['password_usr'] == ExternalUser::model()->passwordHash($_SESSION['password'])) {
                $password = $_SESSION['password'];
                $afterDownload = true;
                $tmpPath = VIREX_TEMP_PATH;
                $variables = array(
                    'START_DATE' => date('Y-m-d', strtotime('- 7 day')),
                    'END_DATE' => date('Y-m-d'),
                    'USERNAME' => $currentUser['name_usr'],
                    'PASSWORD' => $password,
                    'BASE_URL' => VIREX_URL
                );
                // step 1. Create username dir
                if (file_exists($tmpPath . '/' . $currentUser['name_usr'])) {
                    if (file_exists($tmpPath . '/' . $currentUser['name_usr'] . '/sampleshare.inc')) {
                        unlink($tmpPath . '/' . $currentUser['name_usr'] . '/sampleshare.inc');
                    }
                    if (file_exists($tmpPath . '/' . $currentUser['name_usr'] . '/sampleshare.php')) {
                        unlink($tmpPath . '/' . $currentUser['name_usr'] . '/sampleshare.php');
                    }
                } else {
                    mkdir($tmpPath . '/' . $currentUser['name_usr']);
                }
                $tmpPath = $tmpPath . '/' . $currentUser['name_usr'] . '/';
                // step 2. copy originaly files
                copy(VIREX_APP_PATH . '/protected/sampleshareclient/sampleshare.inc', $tmpPath . 'sampleshare.inc');
                copy(VIREX_APP_PATH . '/protected/sampleshareclient/sampleshare.php', $tmpPath . 'sampleshare.php');
                // step 3. replace variables
                $original = file_get_contents($tmpPath . 'sampleshare.php');
                foreach ($variables as $k => $v) {
                    $original = str_replace('<!--' . $k . '--!>', $v, $original);
                }
                file_put_contents($tmpPath . 'sampleshare.php', $original);
                // step 4. create zip archive
                $files = array($tmpPath . 'sampleshare.inc', $tmpPath . 'sampleshare.php');
                FileHelper::packFiles($files, $tmpPath, 'zip.zip');
                // step 5. download contents
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="norman-sampleshare-client-example.zip"');
                header('Content-Transfer-Encoding: binary');
                readfile($tmpPath . 'zip.zip');
                // step 6. clean folder
                unlink($tmpPath . 'sampleshare.inc');
                unlink($tmpPath . 'sampleshare.php');
                unlink($tmpPath . 'zip.zip');
                rmdir($tmpPath);
                unset($_SESSION['password']);
                die();
            } else {
                Yii::app()->user->setFlash('_error', 'Wrong password!');
            }
        }
        $this->render('download_client', array('afterDownload' => $afterDownload));
    }

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex()
    {
        // renders the view file 'protected/views/site/index.php'
        // using the default layout 'protected/views/layouts/main.php'
        $this->actionLogin();
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /**
     * Displays the login page
     */
    public function actionLogin()
    {
        if (!Yii::app()->user->isGuest) {
            if (Yii::app()->user->type == 'Internal') {
                $this->redirect(array('manage/index'));
            } else {
                $this->redirect(array('site/search_file'));
            }
        }
        $model = new LoginForm;

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && $model->login())
                $this->redirect(Yii::app()->user->returnUrl);
        }
        // display the login form
        $this->render('login', array('model' => $model));
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        ExternalUserHistory::addLog('Logout!');
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }

    //Method used to show the registration form
    public function actionRegister()
    {
        $model = new ExternalUser('register');

        // uncomment the following code to enable ajax-based validation
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'external-user-register-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        if (isset($_POST['ExternalUser'])) {
            $model->attributes = $_POST['ExternalUser'];
            if ($model->validate()) {
                $model->save(false);
                ExternalUserHistory::addLog('Account created!', $model->id_usr);
                Yii::app()->user->setFlash('_success', "Your account has been created! Check your email address to continue!");
                $this->redirect(array('site/login'));
            }
        }
        $this->render('register', array('model' => $model));
    }

    //Method used to check the email for the external users
    public function actionCheck_email()
    {
        $id = $_GET['c1'];
        $cod = $_GET['c2'];
        $exista = Yii::app()->db->createCommand()->select('id_usr, email_usr, name_usr, company_usr')->from('external_users_usr')->where('email_code_usr = :cod AND status_usr=0', array(':cod' => $cod))->queryRow();
        $ok = false;
        if ($exista && ($exista['id_usr'] == $id)) {
            $ok = true;
            ExternalUser::model()->validate_email($id);
            ExternalUser::send_ac_email($exista['email_usr'], $exista['id_usr'], $exista['name_usr'], $exista['company_usr']);
            ExternalUserHistory::addLog('Email validated!', $exista['id_usr']);
        }
        $this->render('check_email', array('ok' => $ok));
    }

    //Method used for searching for a file
    public function actionSearch_file()
    {
        $time = microtime();
        $time = explode(" ", $time);
        $time = $time[1] + $time[0];
        $time1 = $time;
        if (isset($_POST['search_hash'])) {
            $min_date = Yii::app()->user->limitation_date;
            $rd = Yii::app()->user->rights_daily;
            $rm = Yii::app()->user->rights_monthly;
            $rightsCondition = '';
            if ((!$rd) || (!$rm)) {
                if ($rd) {
                    $rightsCondition = ' AND type_sde = "daily"';
                } elseif ($rm) {
                    $rightsCondition = ' AND type_sde = "monthly"';
                } else {
                    $rightsCondition = ' AND type_sde = "None"';
                }
            }
            $file = Yii::app()->db->createCommand()->select('sha256_sde, hex(md5_sde) "hex"')->from('samples_detected_sde')->where('(md5_sde =:hash OR sha256_sde =:hash) AND added_when_sde>="' . $min_date . '" AND enabled_sde = 1 ' . $rightsCondition, array(':hash' => strtolower($_POST['search_hash'])))->queryRow();
            if ($file) {
                header("Content-type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"" . $file['sha256_sde'] . "\"");
                $file = PathFinder::get(VIREX_STORAGE_PATH, 'detected', '') . substr($file['hex'], 0, 3) . '/' . substr($file['hex'], 3, 3) . '/' . substr($file['hex'], 6, 3) . '/' . $file['hex'];
                readfile($file);
                die();
            } else {
                Yii::app()->user->setFlash('_error', "No sample found!");
                $this->render('search_file');
                return;
            }
        }
        $this->render('search_file');
    }

    //The profile page
    public function actionMyprofile()
    {
        $model = ExternalUser::model()->findByPk(Yii::app()->user->userId);
        $model->scenario = 'edit_profile';
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'external-user-myprofile-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        if (isset($_POST['ExternalUser'])) {
            if ($model->public_pgp_key_usr != $_POST['ExternalUser']['public_pgp_key_usr']) {
                ExternalUserHistory::addLog('PGP key changed!');
            }
            $model->attributes = $_POST['ExternalUser'];
            if ($model->validate()) {
                if ($model->new_password) {
                    ExternalUserHistory::addLog('Password changed!');
                    $model->password_usr = $model->passwordHash($model->new_password);
                }
                $model->save(false);
                Yii::app()->user->setFlash('_success', 'Your account has been successfully updated!');
                $this->redirect(array('site/search_file'));
            }
        }
        $this->render('myprofile', array('model' => $model));
    }

    //The reset password page
    public function actionResetpassword($code = false)
    {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		
        if (!Yii::app()->user->isGuest) {
            if (Yii::app()->user->type == 'Internal') {
                $this->redirect(array('manage/index'));
            } else {
                $this->redirect(array('site/search_file'));
            }
        }
        $success = false;
		$reset = 0;
        $step = 0; // ask for email
        if ($code) {
            $step = 1;
        } // send the new password
        $model = false;
        if ($step == 0) {
            $model = new ResetpasswordForm;
            // if it is ajax validation request
            if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
                echo CActiveForm::validate($model);
                Yii::app()->end();
            }

            // collect user input data
            if (isset($_POST['ResetpasswordForm'])) {
                $model->attributes = $_POST['ResetpasswordForm'];
				
                // validate user input and redirect to the previous page if valid
                if ($model->validate()) {
					
                    $success = true;
                    if($model->reset()){
						$reset = 1;
					}
                }
            }
			
            // display the login form
        } else {
            $c = explode(';', $code);
            $id = substr($c[0], 0, strlen($c[0]) - 1);
            $type = substr($c[0], -1);
            $code = $c[1];
            unset($c);
            $success = false;
            if ($type == 'e') {
                $external = true;
                $user = ExternalUser::model()->findByPk($id);
                if ($user) {
                    if ($code == $user->email_code_usr) {
						$rands = '';
						for ($i = 0; $i < 8; $i++){
							$rands .= chr(rand(97, 122));
						}
                        $success = true;
                        $name = $user->name_usr;
                        $password = substr(md5($rands), 0, 8);
                        $user->password_usr = $user->passwordHash($password);
                        ExternalUserHistory::addLog('Password reseted!', $user->id_usr);
                        $user->save();
                        $email = $user->email_usr;
                    }
                }
            } elseif ($type == 'i') {
                $external = false;
                $user = InternalUser::model()->findByPk($id);
                if ($user) {
                    if ($code == md5($user->fname_uin . $user->password_uin)) {
                        $rands = '';
						for ($i = 0; $i < 8; $i++){
							$rands .= chr(rand(97, 122));
						}
						$success = true;
                        $name = $user->fname_uin;
                        $password = substr(md5($rands), 0, 8);
                        $user->password_uin = $user->passwordHash($password);
                        $user->save();
                        $email = $user->email_uin;
                    }
                }
            }
            if ($success) {
                ResetpasswordForm::send_second_email($name, $password, $email, $external);
            }
        }
        $this->render('resetpassword', array('step' => $step, 'model' => $model, 'success' => $success, 'reset'=>$reset));
    }

    //Method used to re-send the activation confirmation link
    public function actionResendactivation()
    {
        if (isset($_POST['resend_activation'])) {
            $model = ExternalUser::model()->findAllByAttributes(array('email_usr' => $_POST['email']));
            if ($model) {
                $model = $model[0];
                if ($model->status_usr != 0) {
                    $model = false;
                    Yii::app()->user->setFlash('_error', "Your email address has been already validated!");
                }
            } else {
                Yii::app()->user->setFlash('_error', "Invalid email address!");
            }
            if ($model) {
                $model->sendActivationLink();
                ExternalUserHistory::addLog('Account created!', $model->id_usr);
                Yii::app()->user->setFlash('_success', "Your activation link has been sent! Check your email address to continue!");
                $this->redirect(array('site/login'));
                header("Location: " . Yii::app()->getBaseUrl());
                die();
            }
        }
        $this->render('resendactivation');
    }

}
