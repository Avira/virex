<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the helper class for the WebUI menu
 */

class MenuHelper
{

    public static $init = false;
    public static $administrationMenu = array(
        array('label' => 'External Users', 'url' => array('externaluser/admin')),
        array('label' => 'Internal Users', 'url' => array('internaluser/admin')),
        array('label' => 'Upload Statistics', 'url' => array('stats/sharedfiles')),
        array('label' => 'Download Statistics', 'url' => array('stats/traffic')),
    );
    public static $samplesMenu = array(
        array('label' => 'Detected', 'url' => array('/manage/samples?status=detected')),
        array('label' => 'Clean', 'url' => array('/manage/samples?status=clean')),
        array('label' => 'URLs', 'url' => array('/manage/urls')));
    public static $bogusMenu = array(
        array('label' => 'Archives', 'url' => array('/bogus/archives')),
        array('label' => 'URLs', 'url' => array('/bogus/urls'))
    );
    public static $subMenuMap = array(
        'manage' => array(),
        'internaluser' => array(
            'myprofile' => array(),
        ),
        'externaluser' => array(),
        'samples' => array(),
        'bogus' => array(),
        'stats' => array(),
    );

    //The intializing method
    public static function initMenu($controller)
    {
        self::$samplesMenu = array(
            array('label' => 'Detected', 'url' => array('/manage/samples?status=detected'), 'active' => (isset($_GET['status']) && ($_GET['status'] == 'detected'))),
            array('label' => 'Clean', 'url' => array('/manage/samples?status=clean'), 'active' => (isset($_GET['status']) && ($_GET['status'] == 'clean'))),
            array('label' => 'URLs', 'url' => array('/manage/urls'))
        );
        self::$subMenuMap['bogus'] = self::$bogusMenu;
        if (in_array(strtolower($controller->action->id), array('samples', 'urls', 'bogus'))) {
            self::$subMenuMap['manage'] = self::$samplesMenu;
        } else {
            self::$subMenuMap['manage'] = self::$administrationMenu;
        }
        self::$subMenuMap['stats'] = self::$administrationMenu;
        self::$subMenuMap['internaluser'] = array_merge(self::$administrationMenu, self::$subMenuMap['internaluser']);
        self::$subMenuMap['externaluser'] = self::$administrationMenu;
        self::$init = true;

        return true;
    }

    //Method used to get the submenu items
    public static function getSubMenuItems($controller)
    {
        if (!self::$init) {
            self::initMenu($controller);
        }
        if (!isset(self::$subMenuMap[$controller->id])) {
            return null;
        }
        if (isset(self::$subMenuMap[$controller->id][$controller->action->id])) {
            $items = self::$subMenuMap[$controller->id][$controller->action->id];
        } elseif (isset(self::$subMenuMap[$controller->id][0])) {
            $items = self::$subMenuMap[$controller->id];
        } else {
            return null;
        }


        $menuItems = array();
        for ($i = 0; $i < count($items); $i++) {
            if (!isset($items[$i]['label'])) {
                continue;
            }
            if (isset($items[$i]['visible'])) {
                if ($items[$i]['visible']) {
                    $menuItems[] = $items[$i];
                }
                continue;
            }

            if ($items[$i]['url'][0] != '#') {
                if (strpos($items[$i]['url'][0], '?')) {
                    $url = substr($items[$i]['url'][0], 0, strpos($items[$i]['url'][0], '?'));
                } else {
                    $url = $items[$i]['url'][0];
                }
                $route = explode('/', $url);
                if (!$route[0]) {
                    $route[0] = $route[1];
                    $route[1] = $route[2];
                }
                if (!self::getControllerAccess($route[0], isset($route[1]) ? $route[1] : '')) {
                    continue;
                }
            }

            $menuItems[] = $items[$i];
        }

        return $menuItems;
    }

    //Method used to get the user access permissions to an action
    public static function getControllerAccess($controller, $action)
    {
        $allowed = false;
        $amap = Controller::$accessMap;
        $controller = strtolower($controller);
        $action = strtolower($action);
        if (isset($amap[$controller]) && isset($amap[$controller][$action])) {
            $allowed = UserIdentity::check($amap[$controller][$action]);
        }

        return $allowed;
    }

}
