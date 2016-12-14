<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Controller for the WebUI actions
 */

class ServerController extends CController
{

    private static $errors = array(
        0 => 'Submission ok!',
        101 => 'Login needed!',
        102 => 'Bad login!',
        103 => 'You do not have API access!',
        104 => 'You do not have write access!',
        105 => 'You do not have read access!',
        201 => 'Invalid submission!',
        202 => 'This should be a POST request!',
        301 => 'id must be set!',
        302 => 'start_id must be set!',
        801 => 'Internal error!', //database error
        802 => 'Internal error!', //filesistem error
    );

    //Method used to show the error
    private function echoError($code, $desc = '', $format = '')
    {
        if (empty($format)) {
            echo($code . "\t" . self::$errors[$code] . "\t" . strip_tags($desc));
        } else {
            echo ("Can't display error!");
        }
        Yii::app()->end();
    }

    //Method used to authenticate the users
    private function checkHttpAuth()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="MUTE"');
            header('HTTP/1.0 401 Unauthorized');
            $this->echoError(101);
        } else {
            $userIdentity = new UserIdentity($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            if (!$userIdentity->authenticate()) {
                header('HTTP/1.0 403 Forbidden');
                $this->echoError(102);
            }//end:: authenticate failed
            Yii::app()->user->login($userIdentity);
        }
    }

    //The single access method to the Server controller
    public function actionIndex()
    {
        set_time_limit(120);
        // http auth
        $this->checkHttpAuth();
        // norman
        include dirname(__FILE__) . '/../extensions/norman_server/sampleshare.php';
    }

}
