<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Yii configuration file for the WebUI
 */
require_once 'config.inc.php';

define('VIREX_MAIL_SIGNATURE', '
--
Best Regards,
Virex Team');
define('VIREX_PAGE_SIZE', 20);

$config = array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Virex',
    // preloading 'log' component
    'preload' => array('log'),
    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.helpers.*',
        'application.extensions.*',
        'application.extensions.fusionchart.*',
        'zii.widgets.grid.*',
        'zii.widgets.*',
        'zii.widgets.jui.*',
    ),
    'controllerMap' => array(
        'internaluser' => 'application.controllers.InternalUserController',
        'externaluser' => 'application.controllers.ExternalUserController',
    ),
    // application components
    'components' => array(
        'cache' => array('class' => 'CFileCache'),
        'user' => array(
            // enable cookie-based authentication
            'allowAutoLogin' => false,
        ),
		'request' => array(
            'enableCsrfValidation' => true,
            'enableCookieValidation' => true,
            'class' => 'HttpRequest',
            'noCsrfValidationRoutes' => array('server/'),
            'csrfCookie' => array(
                'httpOnly' => true,
            ),
        ),
        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => array(
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),
        'db' => array(
            'connectionString' => 'mysql:host=' . VIREX_DB_HOST . ';dbname=' . VIREX_DB_NAME,
            'emulatePrepare' => true,
            'username' => VIREX_DB_USER,
            'password' => VIREX_DB_PASS,
            'charset' => 'utf8',
            'enableProfiling' => true,
            'schemaCachingDuration' => 3600
        ),
        'errorHandler' => array(
            // use 'site/error' action to display errors
            'errorAction' => 'site/error',
        ),
    ),
);

return $config;
