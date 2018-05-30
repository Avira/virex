<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The URLs management view
 */



$this->headlineText = 'URLs'; ?>

<a onclick="$('div.form').toggle('fast');$(this).toggle('fast');" style="cursor:pointer;">
    <?php
    if (isset($_SESSION['interval_start'])) {
        echo "Showing urls from " . date('d/m/Y', strtotime($_SESSION['interval_start'])) . ' to ' . date('d/m/Y', strtotime($_SESSION['interval_end'])) . '. Click here to change this.';
    } else {
        echo "Click here to restrict results to a specific time frame";
    }
    ?><br />
</a>

<div class="form" style="display:none;">
    <?php echo CHtml::beginForm(); ?>
        Time frame:
        <?php
        echo Yii::app()->controller->widget('zii.widgets.jui.CJuiDatePicker', array(
            'name' => 'interval_start',
            'value' => isset($_SESSION['interval_start']) ? $_SESSION['interval_start'] : date('Y-m-d', strtotime('- 7 day')),
            'options' => array(
                'dateFormat' => 'yy-mm-dd'
            ),
            'htmlOptions' => array('size' => 30, 'class' => 'date', 'style' => 'width:70px;margin:auto;float:none;', 'autocomplete' => 'off'),
                ), true
        );
        ?> -
        <?php
        echo Yii::app()->controller->widget('zii.widgets.jui.CJuiDatePicker', array(
            'name' => 'interval_end',
            'value' => isset($_SESSION['interval_end']) ? $_SESSION['interval_end'] : date('Y-m-d'),
            'options' => array(
                'dateFormat' => 'yy-mm-dd'
            ),
            'htmlOptions' => array('size' => 30, 'class' => 'date', 'style' => 'width:70px;margin:auto;float:none;', 'autocomplete' => 'off'),
                ), true
        );
        ?>
        <input type="submit" name="interval" value="Apply" />
        <input type="submit" name="interval_reset" value="Reset" />
    <?php echo CHtml::endForm(); ?>
</div>
<br />
<script>
    function after_au(id, data) {
        jQuery('#Url_added_when_url').datepicker({'dateFormat': 'yy-mm-dd'});
    }
</script>
<?php
$this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'selectableRows' => 2,
    'ajaxUpdate' => false,
    'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
    'columns' => array(
        array(
            'name' => 'md5_url',
            'headerHtmlOptions' => array('style' => 'width:240px;'),
            'htmlOptions' => array('style' => 'font-family:"Courier New";width:240px;')
        ),
        array(
            'name' => 'sha256_url',
            'headerHtmlOptions' => array('style' => 'width:240px;'),
            'htmlOptions' => array('style' => 'font-family:"Courier New";width:240px;')
        ),
        array(
            'name' => 'url_url',
            'htmlOptions' => array('class' => 'urls')
        ),
        array(
            'name' => 'added_when_url',
            'value' => 'date("d/m/Y", strtotime($data->added_when_url))',
            'headerHtmlOptions' => array('style' => 'width:80px;padding:0;'),
            'htmlOptions' => array('style' => 'width:80px;text-align:center;padding:0;'),
            'filter' => Yii::app()->controller->widget('zii.widgets.jui.CJuiDatePicker', array(
                'name' => 'Url[added_when_url]',
                'value' => isset($_GET['Url']) ? $_GET['Url']['added_when_url'] : false,
                'options' => array(
                    'dateFormat' => 'yy-mm-dd'
                ),
                'htmlOptions' => array('size' => 30, 'class' => 'date', 'autocomplete' => 'off'),
                    ), true
            )
        ),
        array(
            'name' => 'enabled_url',
            'value' => '$data->enabled_url?"<span style=\'color:lime;\'>Enabled</span>":"<span style=\'color:red;\'>Disabled</span>"',
            'type' => 'RAW',
            'htmlOptions' => array('style' => 'width:80px; text-align:center;padding:0;'),
            'filter' => CHtml::listData(array(array('type' => '1', 'text' => 'Enabled'), array('type' => '0', 'text' => 'Disabled')), 'type', 'text'),
        ),
        array(
            'class' => 'IconButtonColumn',
            'template' => '{enable} {disable} {delete}',
            'buttons' => array(
                'delete' => array(
                    'url' => 'Yii::app()->createUrl("/manage/urls", array("idf"=>$data->id_url, "action"=>"delete"))'
                ),
                'enable' => array(
                    'label' => 'Enable sample',
                    'url' => 'Yii::app()->createUrl("/manage/urls",  array("idf"=>$data->id_url, "action"=>"enable"))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/tick.png',
                    'visible' => '$data->enabled_url == 0'
                ),
                'disable' => array(
                    'label' => 'Disable sample',
                    'url' => 'Yii::app()->createUrl("/manage/urls", array("idf"=>$data->id_url, "action"=>"disable"))',
                    'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/minus-circle-frame.png',
                    'visible' => '$data->enabled_url'
                ),
            )
        )
    ),
));
?>
<br />

<style>
    .urls{
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
		max-width: 200px;
    }
</style>