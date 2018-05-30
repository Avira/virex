<?php

define('VIREX_APP_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'));
define('VIREX_CONFIG_PATH', VIREX_APP_PATH . '/protected/config/config.inc.php');
if (is_file(VIREX_CONFIG_PATH)) {
	die('Seems that the application is already installed! For a new installation, please remove the configuration file.');
}

if (!defined('VIREX_PASSWORD_SALT'))
    define('VIREX_PASSWORD_SALT', 'dh8ivpicmu5sosowrew4terekam9apefe');

function mainUrl() {
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;
}

if (!is_dir(VIREX_APP_PATH . '/htdocs/assets')) {
    @mkdir(VIREX_APP_PATH . '/htdocs/assets', 0777);
}
if (!is_dir(VIREX_APP_PATH . '/protected/runtime')) {
    @mkdir(VIREX_APP_PATH . '/protected/runtime', 0777);
}

exec('7z', $out);
$co = trim(implode(' ', $out));
$is7z = (substr($co, 0, 5) == '7-Zip');
unset($out);
exec('gpg --version', $out);
$co = trim(implode(' ', $out));
$isGpg = (substr($co, 0, 11) == 'gpg (GnuPG)');

$yiipath = defined('VIREX_YII_PATH') ? VIREX_YII_PATH : @$_POST['yiipath'];
$storage = defined('VIREX_STORAGE_PATH') ? VIREX_STORAGE_PATH : @$_POST['storage'];
$incoming = defined('VIREX_INCOMING_PATH') ? VIREX_INCOMING_PATH : @$_POST['incoming'];
$temppath = defined('VIREX_TEMP_PATH') ? VIREX_TEMP_PATH : @$_POST['temppath'];

$dbhost = defined('VIREX_DB_HOST') ? VIREX_DB_HOST : @$_POST['dbhost'];
$dbname = defined('VIREX_DB_NAME') ? VIREX_DB_NAME : @$_POST['dbname'];
$dbuser = defined('VIREX_DB_USER') ? VIREX_DB_USER : @$_POST['dbuser'];
$dbpass = defined('VIREX_DB_PASS') ? VIREX_DB_PASS : @$_POST['dbpass'];

$eadmin = @$_POST['eadmin'];
$epass = @$_POST['epass'];

$errors = array();

if (!$is7z) {
    $errors[] = '7-Zip is not installed';
}
if (!$isGpg) {
    $errors[] = 'GPG is not installed';
}
if (!is_file($yiipath)) {
    $errors[] = 'Yii script not found: ' . $yiipath;
} else {
    require_once($yiipath);
    if (!class_exists('Yii')) {
        $errors[] = 'Yii class not found in: ' . $yiipath;
    }
}
if (!is_dir($temppath) || !is_writable($temppath)) {
    $errors[] = 'The temp path does not exist or is not accessible: ' . $temppath;
}

if (!is_dir($incoming)) {
    $errors[] = 'The incoming path does not exist and could not be created: ' . $incoming;
}

if (!is_dir($storage)) {
    $errors[] = 'The storage path does not exist and could not be created: ' . $storage;
}

if (@mysql_connect($dbhost, $dbuser, $dbpass)) {
    if (@mysql_select_db($dbname)) {
        if(mysql_list_tables($dbname)){
			$errors[] = 'The database already contains tables: ' . $dbname;
		}
    } else {
        $errors[] = 'Invalid database: ' . $dbname;
    }
} else {
    $errors[] = 'Cannot connect to MySQL on: ' . $dbhost;
}

$status = 'new';

if (!isset($errors[0])) {
    if (isset($_POST['install'])) {
        // load virex sql
        $sqls = explode(';', file_get_contents(VIREX_APP_PATH . '/protected/data/virex.sql'));
        foreach ($sqls as $query) {
            $query = trim($query);
            if ($query)
                mysql_query($query);
        }
        if ($eadmin && $epass) {
            mysql_query("INSERT IGNORE INTO internal_users_uin (fname_uin, lname_uin, email_uin, enabled_uin, password_uin, register_date_uin) 
                    VALUES ('Virex', 'Admin', '{$eadmin}', 1, '" . md5(VIREX_PASSWORD_SALT . $epass) . "', NOW())");
        } else {
            $errors[] = 'Invalid admin account or password: ' . $eadmin;
        }
    }
}

if (!isset($errors[0])) {
    if (isset($_POST['install'])) {
        // write config.inc.php
        $confContent = "<?php
// Paths
define('VIREX_YII_PATH', '" . addslashes($yiipath) . "');
define('VIREX_STORAGE_PATH', '" . addslashes($storage) . "');
define('VIREX_INCOMING_PATH', '" . addslashes($incoming) . "');
define('VIREX_TEMP_PATH', '" . addslashes($temppath) . "');
// DB config
define('VIREX_DB_HOST', '" . addslashes($dbhost) . "');
define('VIREX_DB_NAME', '" . addslashes($dbname) . "');
define('VIREX_DB_USER', '" . addslashes($dbuser) . "');
define('VIREX_DB_PASS', '" . addslashes($dbpass) . "');
// Other
define('VIREX_URL', '" . mainUrl() . "');
define('VIREX_PASSWORD_SALT', '" . VIREX_PASSWORD_SALT . "');
";
        if (is_writable(dirname(VIREX_CONFIG_PATH))) {
            file_put_contents(VIREX_CONFIG_PATH, $confContent);
            $status = 'done';
        } else {
            $status = 'writeconfig';
        }
    } else {
        header('location: /index.php');
        die();
    }
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="language" content="en" />

        <!-- blueprint CSS framework -->
        <link rel="stylesheet" type="text/css" href="css/screen.css" media="screen, projection" />
        <link rel="stylesheet" type="text/css" href="css/print.css" media="print" />
        <!--[if lt IE 8]>
        <link rel="stylesheet" type="text/css" href="css/ie.css" media="screen, projection" />
        <![endif]-->
        <link rel="stylesheet" type="text/css" href="css/main.css" />
        <link rel="stylesheet" type="text/css" href="css/form.css" />
        <link rel="stylesheet" type="text/css" href="css/install.css" />
        <title>VIREX Install</title>
    </head>

    <body>
        <div id="header">
            <div class="wrapper">
                <div id="logo"><a href="."><img src="/images/virex.png" alt="Virus Exchange" /></a></div>
            </div>
        </div>
        <!-- header -->

        <div id='headline'>
            <div class='wrapper'>
                <h2>Installation</h2>
            </div>
        </div>
        <!-- headline -->

        <div class="container" id="page">
            <div class="content">
                <div class="installcontainer">
                    <?php
                    if (!version_compare(PHP_VERSION, "5.1.0", ">=") || !class_exists('Reflection', false) ||
                            !extension_loaded("pcre") || !extension_loaded("SPL") || !extension_loaded('pdo') || !extension_loaded('pdo_mysql') || !extension_loaded('gd') || !extension_loaded('fileinfo') ||
                            !is_writable(VIREX_APP_PATH . '/htdocs/assets') || !is_writable(VIREX_APP_PATH . '/protected/runtime')) {
                        ?>
                        <h4>Requirements</h4>
                        <div class="installrow"><b class="installreq">PHP version (<?php echo PHP_VERSION; ?>)</b> 
                            <div class="installres">
                                <?php if (version_compare(PHP_VERSION, "5.1.0", ">=")) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">PHP >= 5.1.0 is required by Yii Framework</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">Reflection</b>
                            <div class="installres">
                                <?php if (class_exists('Reflection', false)) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by Yii Framework</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">PCRE extension</b>
                            <div class="installres">
                                <?php if (extension_loaded("pcre")) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by Yii Framework</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">SPL extension</b>
                            <div class="installres">
                                <?php if (extension_loaded("SPL")) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by Yii Framework</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">PDO extension</b>
                            <div class="installres">
                                <?php if (extension_loaded('pdo')) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by VIREX</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">PDO MySQL extension</b>
                            <div class="installres">
                                <?php if (extension_loaded('pdo_mysql')) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by VIREX</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">GD extension</b>
                            <div class="installres">
                                <?php if (extension_loaded('gd')) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by VIREX</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">FileInfo extension</b>
                            <div class="installres">
                                <?php if (extension_loaded('fileinfo')) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by VIREX</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">Assets folder</b>
                            <div class="installres">
                                <?php if (is_writable(VIREX_APP_PATH . '/htdocs/assets')) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Please create and give write access for the web server to: <br /><b><?php echo VIREX_APP_PATH . '/htdocs/assets'; ?></b></div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">Runtime folder</b>
                            <div class="installres">
                                <?php if (is_writable(VIREX_APP_PATH . '/protected/runtime')) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Please create and give write access for the web server to: <br /><b><?php echo VIREX_APP_PATH . '/protected/runtime'; ?></b></div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">7-Zip</b>
                            <div class="installres">
                                <?php if ($is7z) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by VIREX</div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="installrow"><b class="installreq">GPG</b>
                            <div class="installres">
                                <?php if ($isGpg) { ?>
                                    <img src="images/icons/tick.png" alt="OK" />
                                <?php } else { ?>
                                    <div class="installerror">Required by VIREX</div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <h4>Configuration</h4>
                        <?php
                        if (isset($errors[0]) && isset($_POST['install'])) {
                            echo '<div class="installrow" style="color:red;"><b>Errors:</b><br />&middot; ' . implode('<br />&middot; ', $errors) . '</div>';
                        }
                        ?>
                        <div class="form">
                            <form action="" method="post">
                                <?php if ($status == 'new') { ?>
                                    <fieldset>
                                        <legend>&nbsp;Database&nbsp;</legend>
                                        <div class="row">
                                            <label for="dbhost" class="instalabel">MySQL host:</label>
                                            <input size="30" maxlength="64" name="dbhost" type="text" value="<?php echo $dbhost ? $dbhost : 'localhost'; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="dbuser" class="instalabel">MySQL username:</label>
                                            <input size="30" maxlength="64" name="dbuser" type="text" value="<?php echo $dbuser ? $dbuser : 'virex'; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="dbpass" class="instalabel">MySQL password:</label>
                                            <input size="30" maxlength="64" name="dbpass" type="password" value="<?php echo $dbpass ? $dbpass : ''; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="dbname" class="instalabel">Database name:</label>
                                            <input size="30" maxlength="64" name="dbname" type="text" value="<?php echo $dbname ? $dbname : 'virex'; ?>" />	
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <legend>&nbsp;Paths&nbsp;</legend>
                                        <div class="row">
                                            <label for="yiipath" class="instalabel">Yii script full path:</label>
                                            <input size="30" maxlength="64" name="yiipath" type="text" value="<?php echo $yiipath ? $yiipath : ''; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="storage" class="instalabel">Sample storage path:</label>
                                            <input size="30" maxlength="64" name="storage" type="text" value="<?php echo $storage ? $storage : VIREX_APP_PATH . DIRECTORY_SEPARATOR . 'storage'; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="incoming" class="instalabel">Incoming archives path:</label>
                                            <input size="30" maxlength="64" name="incoming" type="text" value="<?php echo $incoming ? $incoming : VIREX_APP_PATH . DIRECTORY_SEPARATOR . 'incoming'; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="temppath" class="instalabel">Temp path:</label>
                                            <input size="30" maxlength="64" name="temppath" type="text" value="<?php echo $temppath ? $temppath : sys_get_temp_dir(); ?>" />	
                                        </div>
                                    </fieldset>
                                    <fieldset>
                                        <legend>&nbsp;Administrator account&nbsp;</legend>
                                        <div class="row">
                                            <label for="eadmin" class="instalabel">Admin email (login):</label>
                                            <input size="30" maxlength="64" name="eadmin" type="text" value="<?php echo $eadmin ? $eadmin : 'admin@virex.org'; ?>" />	
                                        </div>
                                        <div class="row">
                                            <label for="epass" class="instalabel">Admin password:</label>
                                            <input size="30" maxlength="64" name="epass" type="password" value="<?php echo $epass ? $epass : 'virex'; ?>" />	
                                        </div>
                                    </fieldset>
                                    <div class="row buttons" style="text-align:center;">
                                        <input type="submit" name="install" value="Install" />		
                                    </div>
                                <?php } ?>
                                <?php if ($status == 'done') { ?>
                                    <div>The following configuration was written in: <b><?php echo VIREX_CONFIG_PATH; ?></b></div>
                                    <pre style="font-size:0.8em;"><?php highlight_string($confContent); ?></pre>
                                    <div class="row buttons" style="text-align:center;">
                                        <input type="button" name="done" value="Done" onclick="document.location='?r=<?php echo rand(10000, 99999); ?>';" />		
                                    </div>
                                <?php } ?>
                                <?php if ($status == 'writeconfig') { ?>
                                    <div>Please write the following lines in: <b><?php echo VIREX_CONFIG_PATH; ?></b> and click <b>Done</b></div>
                                    <pre style="font-size:0.8em;"><?php highlight_string($confContent); ?></pre>
                                    <div class="row buttons" style="text-align:center;">
                                        <input type="submit" name="done" value="Done" />		
                                    </div>
                                <?php } ?>
                            </form>	
                        </div>
                        <!-- form -->
                    <?php } ?>
                </div>
            </div>
            <!-- content -->

            <div id="footer">
                <a href="#"><b>VIREX</b></a> is based on the <i>Norman Sample Sharing</i> framework.
            </div>
            <!-- footer -->

        </div>
        <!-- page -->

    </body>
</html>