<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The external user form view
 */
?>
<div class="form wide">

    <?php Yii::app()->clientScript->registerCoreScript('jquery'); ?>
    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'external-user-form',
        'enableAjaxValidation' => true,
    ));
    ?>
    <p class="note">Fields with <span class="required">*</span> are required.</p>
    <?php echo $form->errorSummary($model); ?>
    <div class="row">
        <?php echo $form->labelEx($model, 'name_usr'); ?>
        <span><?php echo $model->name_usr; ?></span>
    </div>
    <div class="row">
        <label>Status</label>
        <span><b><i><?php echo $model->userStatus; ?></i></b></span>
    </div>
    <div class="row">
        <?php echo $form->labelEx($model, 'company_usr'); ?>
        <span><?php echo $model->company_usr; ?></span>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'email_usr'); ?>
        <span><?php echo $model->email_usr; ?></span>
    </div>

    <div class="row" style='width:100%;float:left;'>
        <?php echo $form->labelEx($model, 'public_pgp_key_usr', arraY('id' => 'pgp_key_label')); ?>
        <?php echo $form->textArea($model, 'public_pgp_key_usr', array('style' => 'display:none;width:540px;height:195px;')) ?>
        <?php echo $form->error($model, 'public_pgp_key_usr'); ?>
        <a id="php_button_show" style="cursor:pointer;" >Change PGP key</a>
        <Script>
            $('#pgp_key_label').click(function () {
                $('#ExternalUser_public_pgp_key_usr').toggle('slow');
                $('#php_button_show').toggle();
            });
            $('#php_button_show').click(function () {
                $('#ExternalUser_public_pgp_key_usr').toggle('slow');
                $('#php_button_show').toggle();
            });
        </Script>
    </div><br />

    <fieldset style='width:42%;float:left;'><legend>Change password</legend>
        <a style="cursor:pointer;display:block;text-align:center;" onclick="$(this).toggle('slow');$('#change_password_fields').toggle('fast');">Change password</a>
        <span id="change_password_fields" style="display:none;">
            <div class="row">
                <?php echo $form->labelEx($model, 'new_password'); ?>
                <?php echo $form->passwordField($model, 'new_password', array('style' => 'width:160px', 'maxlength' => 80, 'autocomplete' => 'off')); ?>
                <?php echo $form->error($model, 'new_password'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'confirm_new_password'); ?>
                <?php echo $form->passwordField($model, 'confirm_new_password', array('style' => 'width:160px', 'maxlength' => 80, 'autocomplete' => 'off')); ?>
                <?php echo $form->error($model, 'confirm_new_password'); ?>
            </div>
        </span>
    </fieldset>

    <fieldset style='width:42%;float:left;margin-left:10px;'><legend>User rights</legend>
        <div class="row">
            <?php echo $form->labelEx($model, 'limitation_date_usr'); ?>
            <?php
            $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                'name' => 'ExternalUser[limitation_date_usr]',
                'value' => $model->limitation_date_usr,
                'options' => array(
                    'dateFormat' => 'yy-mm-dd'
                ),
                'htmlOptions' => array('size' => 30, 'class' => 'date', 'style' => 'width:160px;', 'autocomplete' => 'off'),
                    )
            );
            ?>
            <?php echo $form->error($model, 'limitation_date_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'rights_daily_usr'); ?>
            <?php echo $form->checkBox($model, 'rights_daily_usr', array('style' => 'width:auto;')); ?>
            <?php echo $form->error($model, 'rights_daily_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'rights_monthly_usr'); ?>
            <?php echo $form->checkBox($model, 'rights_monthly_usr', array('style' => 'width:auto;')); ?>
            <?php echo $form->error($model, 'rights_monthly_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'rights_clean_usr'); ?>
            <?php echo $form->checkBox($model, 'rights_clean_usr', array('style' => 'width:auto;')); ?>
            <?php echo $form->error($model, 'rights_clean_usr'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'rights_url_usr'); ?>
            <?php echo $form->checkBox($model, 'rights_url_usr', array('style' => 'width:auto;')); ?>
            <?php echo $form->error($model, 'rights_url_usr'); ?>
        </div>

    </fieldset>

    <div class="row buttons">
        <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save changes'); ?>
    </div>

    <?php $this->endWidget(); ?>
</div><!-- form -->