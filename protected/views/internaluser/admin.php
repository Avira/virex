<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The external user admin view
 */
$this->headlineText = "Manage Internal Users";
?>

<div class="actionBar">
    <?php
    echo CHtml::link('Add a new user', array('/internaluser/create'), array('class' => 'icon add'));
    ?>
</div>

<?php
$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'internal-user-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'rowCssClassExpression' => '($row % 2 ? "even " : "odd "). ($data->enabled_uin ? "" : "Rescan")',
    'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
    'template' => '{summary}{pager}{items}',
    'columns' => array(
        array(
            'name' => 'id_uin',
            'headerHtmlOptions' => array('style' => 'width:75px;'),
            'cssClassExpression' => '"idcol"',
        ),
        'fname_uin',
        'lname_uin',
        'email_uin',
        array(
            'name' => 'register_date_uin',
            'htmlOptions' => array('style' => 'width:140px;padding:0;text-align:center;'),
            'value' => '$data->register_date_uin=="0000-00-00 00:00:00"?"n/a":$data->register_date_uin'
        ),
        array(
            'name' => 'last_login_date_uin',
            'value' => '!$data->last_login_date_uin?"n/a":$data->last_login_date_uin',
            'htmlOptions' => array('style' => 'width:140px;padding:0;text-align:center;')
        ),
        array(
            'class' => 'CButtonColumn',
            'template' => '{update} {delete}',
            'header' => 'Actions'
        )
    ),
));
