<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The login form view
 */
$this->pageTitle = Yii::app()->name . ' - Login';
$this->headlineText = 'Login';
$this->headlineSubText = 'Please fill out the following form with your login credentials.';
?>

<div class="form">
    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'login-form',
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
        ),
    ));
    ?>

    <div class="row">
        <?php echo $form->labelEx($model, 'username'); ?>
        <?php echo $form->textField($model, 'username', array('size' => 40, 'maxlength' => 64)); ?>
        <?php echo $form->error($model, 'username'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'password'); ?>
        <?php echo $form->passwordField($model, 'password', array('size' => 40, 'maxlength' => 64)); ?>
        <?php echo $form->error($model, 'password'); ?>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton('Login'); ?>
    </div>
    <?php if (isset($_POST['show_resend_activation'])) { ?>
        <a href="<?php echo Yii::app()->createUrl('site/resendactivation'); ?>">Resend activation email!</a><br />
    <?php } ?>
    <a href="<?php echo Yii::app()->createUrl('site/register'); ?>">Create new account!</a><br />
    <a href="<?php echo Yii::app()->createUrl('site/resetpassword'); ?>">Reset password!</a>
    <?php $this->endWidget(); ?>
</div><!-- form -->
