<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This file is the single access point to the WebUI
 */
define('VIREX_APP_PATH', realpath(dirname(__FILE__) . '/..'));
define('VIREX_CONFIG_PATH', VIREX_APP_PATH . '/protected/config/config.inc.php');

if (!is_file(VIREX_CONFIG_PATH)) {
    header('Location: /install.php');
    return;
}

require_once(VIREX_CONFIG_PATH);
require_once(VIREX_YII_PATH);

$config = VIREX_APP_PATH . '/protected/config/main.php';
Yii::createWebApplication($config)->run();
