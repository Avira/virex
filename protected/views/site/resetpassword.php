<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The reset password form view
 */
?>
<br /><br />
<?php if ($step == 0) { //ask for user email ?>
    <div class='login_form'>
        <h1>Reset password</h1>
        <div class="form wide">
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
                <?php echo $form->textField($model, 'username'); ?>
                <?php echo $form->error($model, 'username'); ?>
            </div>

            <div class="row buttons">
                <?php echo CHtml::submitButton('Reset password'); ?>
            </div>
            <a href="<?php echo Yii::app()->createUrl('site/register'); ?>">Create new account!</a><br />
            <a href="<?php echo Yii::app()->createUrl('site/login'); ?>">Login!</a>
            <?php $this->endWidget(); ?>
        </div><!-- form -->
    </div>
    <?php
    if ($success) {
        echo "<script>alert('Thank you for your request! If you have a valid account, an email has been sent to your address! Follow the link in order to reset your password.');</script>";
    }
    ?>
<?php } elseif ($step == 1) { // send the new password  ?>
    <?php if ($success) { ?>
        An email was sent to you with the new password!
    <?php } else { ?>
        Wrong code!
    <?php } ?>
<?php
}