<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The external user update form view
 */
?>
<?php $this->headlineText = 'Update ' . $model->name_usr; ?>
<div class="actionBar">
    <?php
    echo CHtml::link('Show download statistics', array('/externaluser/statistics/' . $model->id_usr), array('class' => 'icon list'));
    if ($model->status_usr == 2) {
        echo CHtml::link('Disable account', array('/externaluser/disable/' . $model->id_usr), array('class' => 'icon cross'));
    } else {
        echo CHtml::link('Enable account', array('/externaluser/enable/' . $model->id_usr), array('class' => 'icon tick'));
    }
    echo CHtml::link('Show account history', array('/externaluser/history/' . $model->id_usr), array('class' => 'icon list'));

    if ($model->status_usr == 0) {
        echo CHtml::link('Resend activation link', array('/externaluser/reactivate/' . $model->id_usr), array('class' => 'icon tick'));
    }
    ?>
</div>

<?php
echo $this->renderPartial('_form', array('model' => $model));
