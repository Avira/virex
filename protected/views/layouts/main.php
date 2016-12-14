<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The main WebUI layout of the project
 */
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php Yii::app()->getClientScript()->registerCoreScript('jquery'); ?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="language" content="en" />

        <!-- blueprint CSS framework -->
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/screen.css" media="screen, projection" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/print.css" media="print" />
        <!--[if lt IE 8]>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/ie.css" media="screen, projection" />
        <![endif]-->
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/main.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/form.css" />
        <title>Virex - Sample Sharing</title>
    </head>

    <body>

        <div id="header">
            <div class="wrapper">
                <div id="logo"><a href="/"><img src="/images/virex.png" alt="Virus Exchange" /></a></div>
                <div style="float: right;">
                    <div id="header_right">
                        <?php if (!(Yii::app()->user->isGuest)) { ?>
                            <?php if (Yii::app()->user->type == 'Internal') { ?>
                                <a href="/internaluser/update/<?php echo Yii::app()->user->userId; ?>" class="icon user" style="font-size:12px;"><?php echo Yii::app()->user->name; ?></a>
                                [<a href="/site/logout">logout</a>]
                            <?php } else { ?>
                                <a href="/site/myprofile" class="icon user" style="font-size:12px;"><?php echo Yii::app()->user->name; ?></a>
                            <?php } ?>
                        <?php } ?>
                    </div>

                    <?php
                    $active = null;
                    if ('myprofile' == strtolower($this->action->id))
                        $active = null;
                    elseif (in_array(strtolower($this->id), array('manage', 'internaluser', 'externaluser', 'stats')))
                        $active = true;
                    if (in_array(strtolower($this->action->id), array('samples', 'urls', 'bogus'))) {
                        $active = false;
                    }
                    ?>

                    <?php if ((!Yii::app()->user->isGuest) && Yii::app()->user->type == 'Internal') { ?>
                        <div id="mainmenu">
                            <?php
                            $this->widget('zii.widgets.CMenu', array(
                                'items' => array(
                                    array('label' => 'Samples', 'url' => array('/manage/samples?status=detected'), 'active' => (in_array(strtolower($this->action->id), array('samples', 'urls')) && ($this->id == 'manage') )),
                                    array('label' => 'Debug', 'url' => array('/bogus/archives'), 'active' => (in_array(strtolower($this->id), array('bogus')))),
                                    array('label' => 'Administration', 'url' => array('/manage/index'), 'active' => $active),
                                ),
                            ));
                            ?>
                        </div><!-- mainmenu -->
                    <?php } ?>
                    <?php if ((!Yii::app()->user->isGuest) && Yii::app()->user->type == 'External') { ?>
                        <div id="mainmenu">
                            <?php
                            $this->widget('zii.widgets.CMenu', array(
                                'items' => array(
                                    array('label' => 'Search files', 'url' => array('/site/search_file')),
                                    array('label' => 'Download Client', 'url' => array('/site/download_client')),
                                    array('label' => 'Logout', 'url' => array('site/logout'))
                                ),
                            ));
                            ?>
                        </div><!-- mainmenu -->
                    <?php } ?>		


                </div>
            </div>
        </div><!-- header -->

        <?php $this->widget('Headline', array('text' => $this->headlineText, 'secondaryText' => $this->headlineSubText)); ?>
        <!-- headline -->

        <div class="container" id="page">
            <?php
            if ($this->id == 'site' && $this->action->id == 'check_email')
                echo '<br />';
            ?>
            <?php $this->widget('FlashMessage', array('messageName' => '',)); ?>
            <!-- flash message -->

            <?php echo $content; ?>

            <div id="footer">
                <a href="#"><b>VIREX</b></a> is based on the <i>Norman Sample Sharing</i> framework.
                <span style="float:right;text-align:right;">
                    <?php echo Yii::powered(); ?> 
                    <span style="color:#aaa;">
                        ( t: <?php echo number_format(microtime(true) - $this->startTime, 2); ?> s.
                        q: <?php
                        $dbStats = Yii::app()->db->getStats();
                        echo $dbStats[0];
                        ?>)
                    </span>
                </span>

            </div><!-- footer -->

        </div><!-- page -->

    </body>
</html>