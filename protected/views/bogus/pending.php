<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The bogus pending view
 */
$this->headlineText = 'Bogus Samples';
?>    
<script>
    function after_au(id, data) {
        jQuery('#SampleNew_added_when_sne').datepicker({'dateFormat': 'yy-mm-dd'});
    }
</script>
<?php

$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'external-user-grid',
    'dataProvider' => $model->search('error'),
    'filter' => $model,
    'selectableRows' => 2,
    'ajaxUpdate' => false,
    'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
    'rowCssClassExpression' => '($row%2?"even ":"odd "). ($data->pending_action_sne?$data->pending_action_sne:"")',
    'columns' => array(
        array(
            'name' => 'md5_sne',
            'value' => '"<span title=\'Location: ".substr($data->hex,0,3)."/".substr($data->hex,3,3)."/".substr($data->hex,6,3)."/".$data->hex."\'>".$data->md5_sne."</span>"',
            'type' => 'raw',
            'htmlOptions' => array('style' => 'font-family:"Courier New";width:225px;')
        ),
        array(
            'name' => 'date_added_sne',
            'value' => 'date("d/m/Y H:i", strtotime($data->date_added_sne))',
            'htmlOptions' => array('style' => 'width:90px;text-align:center;padding:0;'),
            'filter' => Yii::app()->controller->widget('zii.widgets.jui.CJuiDatePicker', array(
                'name' => 'SampleNew[added_when_sne]',
                'value' => isset($_GET['SampleNew']) ? $_GET['SampleNew']['added_when_sne'] : false,
                'options' => array(
                    'dateFormat' => 'yy-mm-dd'
                ),
                'htmlOptions' => array('size' => 30, 'class' => 'date', 'style' => 'width:100px;margin:auto;float:none;', 'autocomplete' => 'off'),
                    ), true
            )
        ),
        array(
            'name' => 'error_message_sne',
            'filter' => false
        ),
        array(
            'name' => 'last_scan_date_sne',
            'value' => 'MiscHelper::niceDate($data->last_scan_date_sne, false, false) . " (".$data->scan_count_sne." scans)"',
            'htmlOptions' => array('style' => 'width:140px;text-align:center;padding:0;'),
            'filter' => false
        ),
        array(
            'name' => 'pending_action_sne',
            'filter' => false,
            'htmlOptions' => array('style' => 'text-align:center;width:90px;padding:0;')
        ),
        array(
            'class' => 'IconButtonColumn',
            'template' => '{delete}',
            'headerHtmlOptions' => array('style' => 'width:45px;'),
            'htmlOptions' => array('style' => 'padding:0;'),
            'buttons' => array(
                'download' => array(
                    'label' => 'Download file',
                    'url' => 'Yii::app()->createUrl("/manage/download", array("md5"=>$data->md5_sne, "type"=>"pending"))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/download-icon.png',
                'delete' => array(
                    'url' => 'Yii::app()->createUrl("/bogus/pending", array("idf"=>$data->id_sne, "status"=>"pending", "action"=>"delete"))'
                )
            )
        )
    ),
));
