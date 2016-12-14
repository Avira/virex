<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Yii configuration file for the console commands
 */
$extraConsoleConfig = array(
    'components' => array('log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                    'maxFileSize' => 16384, // 16 MB
                ),
            ),
        ),
    )
);

$config = include('main.php');

foreach ($extraConsoleConfig as $k => $v) {
    if (isset($config[$k])) {
        $config[$k] = array_merge($config[$k], $v);
    } else {
        $config[$k] = $v;
    }
}

unset($config['controllerMap']);
return $config;
