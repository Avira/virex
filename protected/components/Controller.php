<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */

class Controller extends CController
{

    /**
     * @var string the default layout for the controller view. Defaults to '//layouts/column1',
     * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
     */
    public $layout = '//layouts/column1';

    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */
    public $menu = array();
    public $headlineText = null;
    public $headlineSubText = null;
    public static $accessMap = array(
        'site' => array(
            'login' => '*',
            'logout' => '*',
            'register' => '*',
            'check_email' => '*',
            'forgotpassword' => '*',
            'resetpassword' => '*',
            'resendactivation' => '*',
            'error' => '*',
            'captcha' => '*',
            'index' => '@',
            'download_client' => '@',
            'search_file' => 'External',
            'myprofile' => 'External',
        ),
        'server' => array(
            'index' => '*'
        ),
        'internaluser' => array(
            'admin' => 'Internal',
            'view' => 'Internal',
            'create' => 'Internal',
            'delete' => 'Internal',
            'enable' => 'Internal',
            'index' => 'Internal',
            'update' => 'Internal'
        ),
        'externaluser' => array(
            'admin' => 'Internal',
            'view' => 'Internal',
            'create' => 'Internal',
            'enable' => 'Internal',
            'delete' => 'Internal',
            'disable' => 'Internal',
            'update' => 'Internal',
            'history' => 'Internal',
            'statistics' => 'Internal',
            'reactivate' => 'Internal'
        ),
        'manage' => array(
            'index' => 'Internal',
            'urls' => 'Internal',
            'samples' => 'Internal',
            'cronjobs' => 'Internal',
            'download' => 'Internal',
            'exclusion_list' => 'Internal',
            'per_page' => 'Internal',
            'bogus' => 'Internal',
            'bogus_mass' => 'Internal',
            'urls_mass' => 'Internal',
            'samples_detected' => 'Internal',
            'samples_clean' => 'Internal',
            'samples_pending' => 'Internal',
            'statistics' => 'Internal'
        ),
        'statistics' => array(
            'sharedfiles' => 'Internal',
            'trafic' => 'Internal'
        ),
        'stats' => array(
            'traffic' => 'Internal',
            'sharedfiles' => 'Internal',
        ),
        'bogus' => array(
            'index' => 'Internal',
            'archives' => 'Internal',
            'pending' => 'Internal',
            'urls' => 'Internal',
            'bogus_mass' => 'Internal',
            'samples_pending' => 'Internal'
        ),
        'utils/log' => array(
            'index' => '@',
            ''
        ),
    );
    public $startTime = 0;

    //the initialization method
    public function init()
    {
        $this->startTime = microtime(true);
        return parent::init();
    }

    //method used to get the access rules of an action
    public function accesRulesByAction($action)
    {
        $allowed = false;
        $amap = self::$accessMap;
        $controller = strtolower($this->getUniqueId());
        $action = strtolower($action->getId());
        if (isset($amap[$controller]) && isset($amap[$controller][$action])) {
            $allowed = UserIdentity::check($amap[$controller][$action]);
        }

        if ($allowed) {
            return array(array('allow', 'users' => array('*')));
        } else {
            return array(array('deny', 'users' => array('*')));
        }
    }

    //method used to set the rules for the actions access
    public function filterAccessControl($filterChain)
    {
        $filter = new CAccessControlFilter;
        $rules = $this->accesRulesByAction($filterChain->action);
        $filter->setRules($rules);
        $filter->filter($filterChain);
    }

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    //method used to get the general variables values from the database
    public function getGeneralVariables()
    {
        $in = Yii::app()->db->createCommand("SELECT * FROM variables_vrb")->queryAll();
        $info = array();
        foreach ($in as $i) {
            $info[$i['name_vrb']] = $i['value_vrb'];
        }
        // init
        $info['this_week_error_count'] = (isset($info['this_week_error_count'])) ? $info['this_week_error_count'] : 0;
        $info['last_archive_error_email'] = isset($info['last_archive_error_email']) ? $info['last_archive_error_email'] : '2011-07-18 15:34:00';
        $info['last_pgp_error_email'] = isset($info['last_pgp_error_email']) ? $info['last_pgp_error_email'] : '2011-07-18 15:34:00';
        $info['last_archive_error_id'] = isset($info['last_archive_error_id']) ? $info['last_archive_error_id'] : '0';
        $info['next_week_reset_time'] = isset($info['next_week_reset_time']) ? $info['next_week_reset_time'] : '2011-07-18 15:34:00';
        return $info;
    }

    //method used to update the vatiables values from the database
    public function updateVariables($info)
    {
        $updateCommand = Yii::app()->db->createCommand("INSERT INTO variables_vrb (name_vrb, value_vrb) VALUES (:name,:value) ON DUPLICATE KEY UPDATE
																																								value_vrb=:value");
        foreach ($info as $k => $v) {
            $updateCommand->execute(array(':name' => $k, ':value' => $v));
        }
    }

}
