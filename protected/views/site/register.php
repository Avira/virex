<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The user register form view
 */
$this->pageTitle = Yii::app()->name . ' - Login';
$this->headlineText = 'Register';
$this->headlineSubText = 'Fields with * are required.';
?>

<div class='login_form register_form'>
    <div class="form wide">

        <?php
        $form = $this->beginWidget('CActiveForm', array(
            'id' => 'external-user-register-form',
            'enableAjaxValidation' => false,
        ));
        ?>


        <?php echo $form->errorSummary($model); ?>

        <div class="row">
            <?php echo $form->labelEx($model, 'name_usr'); ?>
            <?php echo $form->textField($model, 'name_usr', array('size' => 75)); ?>
            <?php echo $form->error($model, 'name_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'company_usr'); ?>
            <?php echo $form->textField($model, 'company_usr', array('size' => 75)); ?>
            <?php echo $form->error($model, 'company_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'email_usr'); ?>
            <?php echo $form->textField($model, 'email_usr', array('size' => 75)); ?>
            <?php echo $form->error($model, 'email_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'password_usr'); ?>
            <?php echo $form->passwordField($model, 'password_usr', array('size' => 75)); ?>
            <?php echo $form->error($model, 'password_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'confirm_password'); ?>
            <?php echo $form->passwordField($model, 'confirm_password', array('size' => 75)); ?>
            <?php echo $form->error($model, 'confirm_password'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'public_pgp_key_usr'); ?>
            <?php echo $form->textArea($model, 'public_pgp_key_usr', array('cols' => 64, 'rows' => 15)); ?>
            <?php echo $form->error($model, 'public_pgp_key_usr'); ?>
        </div>

        <?php if (extension_loaded('gd')): ?>
            <div class="row">
                <?php echo CHtml::activeLabelEx($model, 'verifyCode') ?>
                <div>
                    <?php $this->widget('CCaptcha'); ?>
                    <?php echo CHtml::activeTextField($model, 'verifyCode', array('style' => 'margin-left:130px;')); ?>
                </div>
                <div class="hint">Please enter the letters as they are shown in the image above.
                    <br/>Letters are not case-sensitive.</div>
            </div>
        <?php endif; ?>

        <div class="row buttons">
            <?php echo CHtml::submitButton('Submit'); ?>
        </div>
        <div class="row">
            <a href="<?php echo Yii::app()->getBaseUrl(true); ?>">Already have an account?</a><br />
            <a href="<?php echo Yii::app()->createUrl('site/resetpassword'); ?>">Reset password!</a>
        </div>
        <?php $this->endWidget(); ?>

    </div><!-- form -->
</div>