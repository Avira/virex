<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The internal user form view
 */
?>

<div class="form wide">
    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'internal-user-form',
        'enableAjaxValidation' => true,
    ));
    ?>
    <fieldset>
        <legend>Account details</legend>

        <?php echo $form->errorSummary($model); ?>

        <div class="row">
            <?php echo $form->labelEx($model, 'fname_uin'); ?>
            <?php echo $form->textField($model, 'fname_uin', array('size' => 50, 'maxlength' => 50)); ?>
            <?php echo $form->error($model, 'fname_uin'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'lname_uin'); ?>
            <?php echo $form->textField($model, 'lname_uin', array('size' => 50, 'maxlength' => 50)); ?>
            <?php echo $form->error($model, 'lname_uin'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'email_uin'); ?>
            <?php echo $form->textField($model, 'email_uin', array('size' => 50, 'maxlength' => 50)); ?>
            <?php echo $form->error($model, 'email_uin'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'enabled_uin'); ?>
            <?php echo $form->checkBox($model, 'enabled_uin', array('size' => 1, 'maxlength' => 1)); ?>
            <?php echo $form->error($model, 'enabled_uin'); ?>
        </div>
    </fieldset>
    <?php if ((!$model->isNewRecord) && ($model->id_uin == Yii::app()->user->userId)) { ?>
        <?php echo CHtml::hiddenField('password_change_request', '0'); ?>

        <fieldset style="text-align:center;"><legend onclick="$('#change_old_password').toggle('fast');$('#link_change_password').toggle('fast');if ($('#password_change_request').val() == 1) {
                        $('#password_change_request').val(0)
                    } else {
                        $('#password_change_request').val(1);
                    }" style="cursor:pointer;">Change password</legend>
            <a id="link_change_password" onclick="$('#change_old_password').toggle('fast');
                        $(this).toggle('fast');if ($('#password_change_request').val() == 1) {
                            $('#password_change_request').val(0)
                        } else {
                            $('#password_change_request').val(1);
                        }" style="cursor:pointer;">Change password</a>
            <div id="change_old_password" style="display:none;min-width:550px;text-align:left;">
                <div class="row">
                    <?php echo $form->labelEx($model, 'old_password'); ?>
                    <?php echo $form->passwordField($model, 'old_password', array('style' => 'width:350px;', 'autocomplete' => 'off')); ?>
                    <?php echo $form->error($model, 'old_password'); ?>
                </div>
                <div class="row">
                    <?php echo $form->labelEx($model, 'new_password'); ?>
                    <?php echo $form->passwordField($model, 'new_password', array('style' => 'width:350px;', 'value' => '', 'autocomplete' => 'off')); ?>
                    <?php echo $form->error($model, 'new_password'); ?>
                </div>
                <div class="row">
                    <?php echo $form->labelEx($model, 'confirm_new_password'); ?>
                    <?php echo $form->passwordField($model, 'confirm_new_password', array('style' => 'width:350px;', 'value' => '', 'autocomplete' => 'off')); ?>
                    <?php echo $form->error($model, 'confirm_new_password'); ?>
                </div>
            </div>
        </fieldset>
        <?php
        if ($model->hasErrors('old_password') || $model->hasErrors('new_password') || $model->hasErrors('confirm_new_password')) {
            echo '
            <script type="text/javascript">
                $("#link_change_password").hide();
                $("#change_old_password").show();
                $("#password_change_request").val(1);
            </script>';
        }
        ?>
    <?php } ?>
    <fieldset><legend>Notifications</legend>
        <div class="row">
            <?php echo $form->labelEx($model, 'notification_pgp_error_uin'); ?>
            <?php echo $form->checkBox($model, 'notification_pgp_error_uin', array('size' => 1, 'maxlength' => 1)); ?>
            <?php echo $form->error($model, 'notification_pgp_error_uin'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'notification_new_account_request_uin'); ?>
            <?php echo $form->checkBox($model, 'notification_new_account_request_uin', array('size' => 1, 'maxlength' => 1)); ?>
            <?php echo $form->error($model, 'notification_new_account_request_uin'); ?>
        </div>
    </fieldset>

    <div class="row buttons">
        <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
    </div>
    <?php $this->endWidget(); ?>
</div>
<!-- form -->