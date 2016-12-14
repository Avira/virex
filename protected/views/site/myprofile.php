<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The profile view
 */
$this->headlineText = 'My profile';
$this->headlineSubText = 'Fields with * are required.';
?>
<div class="form wide">

    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'external-user-myprofile-form',
        'enableAjaxValidation' => true,
    ));
    ?>

    <?php echo $form->errorSummary($model); ?>
    <fieldset><legend>Basic info</legend>
        <div class="row">
            <?php echo $form->labelEx($model, 'name_usr'); ?>
            <?php echo $form->textField($model, 'name_usr', array('style' => 'width:600px;')); ?>
            <?php echo $form->error($model, 'name_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'company_usr'); ?>
            <?php echo $form->textField($model, 'company_usr', array('style' => 'width:600px;')); ?>
            <?php echo $form->error($model, 'company_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'email_usr'); ?>
            <?php echo $form->textField($model, 'email_usr', array('style' => 'width:600px;')); ?>
            <?php echo $form->error($model, 'email_usr'); ?>
        </div>
    </fieldset>
    <fieldset><legend>Change password</legend>
        <div class="row">
            <?php echo $form->labelEx($model, 'old_password'); ?>
            <?php echo $form->passwordField($model, 'old_password', array('style' => 'width:600px;', 'value' => '', 'autocomplete' => 'off')); ?>
            <?php echo $form->error($model, 'old_password'); ?>
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'new_password'); ?>
            <?php echo $form->passwordField($model, 'new_password', array('style' => 'width:600px;', 'value' => '')); ?>
            <?php echo $form->error($model, 'new_password'); ?>
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'confirm_new_password'); ?>
            <?php echo $form->passwordField($model, 'confirm_new_password', array('style' => 'width:600px;', 'value' => '')); ?>
            <?php echo $form->error($model, 'confirm_new_password'); ?>
        </div>
    </fieldset>
    <fieldset><legend>Public PGP Key</legend>
        <div class="row">
            <?php echo $form->labelEx($model, 'public_pgp_key_usr'); ?>
            <?php echo $form->textArea($model, 'public_pgp_key_usr', array('style' => 'width:600px;height:200px;')); ?>
            <?php echo $form->error($model, 'public_pgp_key_usr'); ?>
        </div>
    </fieldset>


    <div class="row buttons" style="padding:0;">
        <?php echo CHtml::submitButton('Submit', array('style' => 'width:155px;display:block;margin:auto;')); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->