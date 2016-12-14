<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The ExternalUser search form view
 */
?>
<div class="wide form">

    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'action' => Yii::app()->createUrl($this->route),
        'method' => 'get',
    ));
    ?>

    <div class="row">
        <?php echo $form->label($model, 'id_usr'); ?>
        <?php echo $form->textField($model, 'id_usr', array('size' => 10, 'maxlength' => 10)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model, 'name_usr'); ?>
        <?php echo $form->textField($model, 'name_usr', array('size' => 60, 'maxlength' => 60)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model, 'company_usr'); ?>
        <?php echo $form->textField($model, 'company_usr', array('size' => 60, 'maxlength' => 80)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model, 'email_usr'); ?>
        <?php echo $form->textField($model, 'email_usr', array('size' => 60, 'maxlength' => 80)); ?>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton('Search'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- search-form -->