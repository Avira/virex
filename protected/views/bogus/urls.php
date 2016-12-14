
<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The bogus urls view
 */
$this->headlineText = 'Bogus URLs';

$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'grid',
    'dataProvider' => $model->search('urls'),
    'filter' => $model,
    'selectableRows' => 2,
    'ajaxUpdate' => false,
    'rowCssClassExpression' => '($row%2?"even ":"odd "). ($data->pending_action_bga?$data->pending_action_bga:"")',
    'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
    'columns' => array(
        array(
            'name' => 'name_bga',
            'htmlOptions' => array('style' => 'font-family:"Courier New";')
        ),
        array(
            'name' => 'date_add_bga',
            'value' => 'date("d/m/Y", strtotime($data->date_add_bga))',
            'filter' => false
        ),
        array(
            'name' => 'error_message_bga',
            'filter' => false
        ),
        array(
            'name' => 'pending_action_bga',
            'filter' => false,
            'value' => '$data->pending_action_bga'
        ),
        array(
            'class' => 'IconButtonColumn',
            'template' => '{unpack} {delete}',
            'buttons' => array(
                'download' => array(
                    'label' => 'Download file',
                    'url' => 'Yii::app()->createUrl("/bogus/urls", array("id"=>$data->id_bga, "action"=>"download"))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/download-icon.png',
                ),
                'unpack' => array(
                    'label' => 'Retry processing file',
                    'url' => 'Yii::app()->createUrl("/bogus/urls", array("id"=>$data->id_bga, "action"=>"unpack"))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/page_refresh.png',
                ),
                'delete' => array(
                    'url' => 'Yii::app()->createUrl("/bogus/urls", array("id"=>$data->id_bga, "action"=>"delete"))'
                )
            )
        )
    )
));
