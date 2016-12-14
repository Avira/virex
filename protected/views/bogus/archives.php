<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The bogus archives view
 */
$this->headlineText = 'Archives errors';

$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'grid',
    'dataProvider' => $model->search('samples'),
    'filter' => $model,
    'selectableRows' => 2,
    'ajaxUpdate' => false,
    'rowCssClassExpression' => '($row%2?"even ":"odd "). ($data->pending_action_bga?$data->pending_action_bga:"")',
    'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
    'columns' => array(
        array(
            'name' => 'id_bga',
            'headerHtmlOptions' => array('style' => 'width:70px;'),
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
        array(
            'name' => 'name_bga',
        ),
        array(
            'name' => 'date_add_bga',
        ),
        array(
            'name' => 'error_message_bga',
        ),
        array(
            'name' => 'pending_action_bga',
            'value' => '$data->pending_action_bga',
            'headerHtmlOptions' => array('style' => 'width:100px;'),
            'htmlOptions' => array('style' => 'text-align:center;'),
        ),
        array(
            'class' => 'IconButtonColumn',
            'template' => '{delete}',
            'buttons' => array(
                'delete' => array(
                    'url' => 'Yii::app()->createUrl("/bogus/archives", array("id"=>$data->id_bga, "action"=>"delete"))'
                )
            )
        )
    )
));
