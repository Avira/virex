<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The external user admin view
 */
$this->headlineText = 'External Users';

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('external-user-grid', {
		data: $(this).serialize()
	});
	return false;
});
");

$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'external-user-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'ajaxUpdate' => false,
    'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
    'rowCssClassExpression' => '($row%2?"even ":"odd "). ($data->userStatus!="Enabled"?($data->userStatus=="Pending"?"Important":"Rescan"):"")',
    'template' => '{summary}{pager}{items}',
    'columns' => array(
        array(
            'name' => 'id_usr',
            'headerHtmlOptions' => array('style' => 'width:75px;'),
            'cssClassExpression' => '"idcol"',
        ),
        'name_usr',
        'email_usr',
        'company_usr',
        array(
            'name' => 'last_login_date_usr',
            'value' => '$data->last_login_date_usr=="0000-00-00 00:00:00"?"n/a":$data->last_login_date_usr',
            'htmlOptions' => array('style' => 'width:120px;padding:0;text-align:center;')
        ),
        array('name' => 'userStatus',
            'filter' => CHtml::listData(ExternalUser::userStatusList(), 'name', 'name'),
            'htmlOptions' => array('style' => 'width:80px;padding:0;text-align:center;')
        ),
        array(
            'class' => 'CButtonColumn',
            'header' => 'Actions',
            'template' => '{enable} {disable} {update} {delete}',
            'buttons' => array(
                'enable' => array(
                    'label' => 'Enable account',
                    'url' => 'Yii::app()->createUrl("externaluser/enable", array("id"=>$data->id_usr))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/tick.png',
                    'visible' => '$data->status_usr != 2'
                ),
                'disable' => array(
                    'label' => 'Disable account',
                    'url' => 'Yii::app()->createUrl("externaluser/disable", array("id"=>$data->id_usr))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/minus-circle-frame.png',
                    'visible' => '$data->status_usr == 2'
                )
            )
        ),
    ),
));
