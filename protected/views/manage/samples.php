<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The samples management view
 */
$this->headlineText = ($status == 'pending' ? 'New' : ucwords($status)) . ' Samples';
?>
<a onclick="$('div.form').toggle('fast');$(this).toggle('fast');" style="cursor:pointer;">
    <?php
    if (isset($_SESSION['interval_start'])) {
        echo "Showing samples from " . date('d/m/Y', strtotime($_SESSION['interval_start'])) . ' to ' . date('d/m/Y', strtotime($_SESSION['interval_end'])) . '. Click here to select change this.';
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
                'dateFormat' => 'yy-mm-dd',
                'onSelect' => 'js:function(selectedDate) {
                                $("#interval_end").datepicker("option", "minDate", selectedDate);                  
                            }',
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
                'dateFormat' => 'yy-mm-dd',
                'onSelect' => 'js:function(selectedDate) {
                                $("#interval_start").datepicker("option", "maxDate", selectedDate);                  
                            }',
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
<?php if ($status == 'detected') { ?>
    <script>
        function after_au(id, data) {
            jQuery('#SampleDetected_added_when_sde').datepicker({'dateFormat': 'yy-mm-dd'});
            jQuery('.table_hint').each(function () {
                this.parentNode.hint = this.innerHTML;
                this.style.display = 'none';
            })
        }
    </script>
    <?php
    $this->widget('zii.widgets.grid.CGridView', array(
        'id' => 'external-user-grid',
        'dataProvider' => $model->search(),
        'filter' => $model,
        'selectableRows' => 2,
        'ajaxUpdate' => false,
        'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
        'rowCssClassExpression' => '($row%2?"even ":"odd "). ($data->pending_action_sde?$data->pending_action_sde:"")',
        'columns' => array(
            array(
                'name' => 'id_sde',
                'headerHtmlOptions' => array('style' => 'width:70px;'),
                'htmlOptions' => array('style' => 'text-align:right;'),
            ),
            array(
                'name' => 'md5_sde',
                'headerHtmlOptions' => array('style' => 'width:250px;'),
                'value' => '"<span title=\'Location: ".substr($data->hex,0,3)."/".substr($data->hex,3,3)."/".substr($data->hex,6,3)."/".$data->hex."\'>".$data->md5_sde."</span>"',
                'htmlOptions' => array('style' => 'font-family:"Courier New";'),
                'type' => 'raw'
            ),
            array(
                'name' => 'sha256_sde',
                'headerHtmlOptions' => array('style' => 'width:250px;'),
                'value' => '"<span title=\'Location: ".substr($data->hexSha256,0,3)."/".substr($data->hexSha256,3,3)."/".substr($data->hexSha256,6,3)."/".$data->hexSha256."\'>".$data->sha256_sde."</span>"',
                'htmlOptions' => array('style' => 'font-family:"Courier New";'),
                'type' => 'raw'
            ),
            array(
                'name' => 'file_size_sde',
                'value' => 'number_format($data->file_size_sde, 0, ",", ".");',
                'htmlOptions' => array('style' => 'text-align:right;'),
                'headerHtmlOptions' => array('style' => 'width:70px;'),
            ),
            array(
                'name' => 'added_when_sde',
                'value' => 'MiscHelper::smallDate($data->added_when_sde)',
                'headerHtmlOptions' => array('style' => 'width:70px;'),
                'htmlOptions' => array('style' => 'text-align:center;'),
                'filter' => Yii::app()->controller->widget('zii.widgets.jui.CJuiDatePicker', array(
                    'name' => 'SampleDetected[added_when_sde]',
                    'value' => isset($_GET['SampleDetected']) ? $_GET['SampleDetected']['added_when_sde'] : false,
                    'options' => array(
                        'dateFormat' => 'yy-mm-dd'
                    ),
                    'htmlOptions' => array('class' => 'date', 'autocomplete' => 'off'),
                        ), true
                )
            ),
            array(
                'name' => 'type_sde',
                'headerHtmlOptions' => array('style' => 'width:70px;'),
                'htmlOptions' => array('style' => 'text-align:center;'),
                'filter' => array('daily' => 'daily', 'monthly' => 'monthly'),
            ),
            array(
                'name' => 'enabled_sde',
                'headerHtmlOptions' => array('style' => 'width:80px;'),
                'htmlOptions' => array('style' => 'text-align:center;'),
                'value' => '$data->enabled_sde?"<span style=\'color:olivedrab;\'>Enabled</span>":"<span style=\'color:red;\'>Disabled</span>"',
                'type' => 'RAW',
                'filter' => array('1' => 'enabled', '0' => 'disabled'),
            ),
            array(
                'name' => 'pending_action_sde',
                'filter' => array('delete' => 'delete'),
            ),
            array(
                'class' => 'IconButtonColumn',
                'header' => 'Actions',
                'template' => '{enable}{disable} {delete}',
                'buttons' => array(
                    'enable' => array(
                        'label' => 'Enable sample',
                        'url' => 'Yii::app()->createUrl("/manage/samples",  array("idf"=>$data->id_sde, "status"=>"detected", "action"=>"enable"))',
                        'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/tick.png',
                        'visible' => '$data->enabled_sde == 0'
                    ),
                    'disable' => array(
                        'label' => 'Disable sample',
                        'url' => 'Yii::app()->createUrl("/manage/samples", array("idf"=>$data->id_sde, "status"=>"detected", "action"=>"disable"))',
                        'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/minus-circle-frame.png',
                        'visible' => '$data->enabled_sde'
                    ),
                    'download' => array(
                        'label' => 'Download file',
                        'url' => 'Yii::app()->createUrl("/manage/download", array("md5"=>$data->md5_sde, "type"=>"detected"))',
                        'imageUrl' => Yii::app()->request->baseUrl . '/images/download-icon.png',
                    ),
                    'delete' => array(
                        'url' => 'Yii::app()->createUrl("/manage/samples", array("idf"=>$data->id_sde, "status"=>"detected", "action"=>"delete"))'
                    )
                )
            )
        )
    ));
    ?>
<?php } elseif ($status == 'clean') { ?>
    <script>
        function after_au(id, data) {
            jQuery('#SampleClean_added_when_scl').datepicker({'dateFormat': 'yy-mm-dd'});
        }
    </script>
    <?php
    $this->widget('zii.widgets.grid.CGridView', array(
        'id' => 'external-user-grid',
        'dataProvider' => $model->search(),
        'filter' => $model,
        'ajaxUpdate' => false,
        'selectableRows' => 2,
        'rowCssClassExpression' => '($row%2?"even ":"odd "). ($data->pending_action_scl?$data->pending_action_scl:"")',
        'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
        'columns' => array(
            array(
                'name' => 'id_scl',
                'headerHtmlOptions' => array('style' => 'width:70px;'),
                'htmlOptions' => array('style' => 'text-align:right;'),
            ),
            array(
                'name' => 'md5_scl',
                'headerHtmlOptions' => array('style' => 'width:250px;'),
                'htmlOptions' => array('style' => 'font-family:"Courier New";'),
                'value' => '"<span title=\'Location: ".substr($data->hex,0,3)."/".substr($data->hex,3,3)."/".substr($data->hex,6,3)."/".$data->hex."\'>".$data->md5_scl."</span>"',
                'type' => 'raw'
            ),
            array(
                'name' => 'sha256_scl',
                'headerHtmlOptions' => array('style' => 'width:250px;'),
                'htmlOptions' => array('style' => 'font-family:"Courier New";'),
                'value' => '"<span title=\'Location: ".substr($data->hex,0,3)."/".substr($data->hexSha256,3,3)."/".substr($data->hexSha256,6,3)."/".$data->hexSha256."\'>".$data->sha256_scl."</span>"',
                'type' => 'raw'
            ),
            array(
                'name' => 'file_size_scl',
                'value' => 'number_format($data->file_size_scl, 0, ",", ".");',
                'htmlOptions' => array('style' => 'text-align:right;'),
                'headerHtmlOptions' => array('style' => 'width:70px;'),
            ),
            array(
                'name' => 'added_when_scl',
                'value' => 'MiscHelper::smallDate($data->added_when_scl)',
                'headerHtmlOptions' => array('style' => 'width:70px;'),
                'htmlOptions' => array('style' => 'text-align:center;'),
                'filter' => Yii::app()->controller->widget('zii.widgets.jui.CJuiDatePicker', array(
                    'name' => 'SampleClean[added_when_scl]',
                    'value' => isset($_GET['SampleClean']) ? $_GET['SampleClean']['added_when_scl'] : false,
                    'options' => array(
                        'dateFormat' => 'yy-mm-dd'
                    ),
                    'htmlOptions' => array('class' => 'date', 'autocomplete' => 'off'),
                        ), true
                )
            ),
            array(
                'name' => 'type_scl',
                'headerHtmlOptions' => array('style' => 'width:70px;'),
                'htmlOptions' => array('style' => 'text-align:center;'),
                'filter' => array('daily' => 'daily', 'monthly' => 'monthly'),
            ),
            array(
                'name' => 'enabled_scl',
                'headerHtmlOptions' => array('style' => 'width:80px;'),
                'htmlOptions' => array('style' => 'text-align:center;'),
                'value' => '$data->enabled_scl?"<span style=\'color:olivedrab;\'>Enabled</span>":"<span style=\'color:red;\'>Disabled</span>"',
                'type' => 'RAW',
                'filter' => array('1' => 'enabled', '0' => 'disabled'),
            ),
            array(
                'name' => 'pending_action_scl',
                'filter' => array('delete' => 'delete'),
            ),
            array(
                'class' => 'IconButtonColumn',
                'header' => 'Actions',
                'template' => '{enable}{disable} {delete}',
                'buttons' => array(
                    'enable' => array(
                        'label' => 'Enable sample',
                        'url' => 'Yii::app()->createUrl("/manage/samples",  array("idf"=>$data->id_scl, "status"=>"clean", "action"=>"enable"))',
                        'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/tick.png',
                        'visible' => '$data->enabled_scl == 0'
                    ),
                    'disable' => array(
                        'label' => 'Disable sample',
                        'url' => 'Yii::app()->createUrl("/manage/samples", array("idf"=>$data->id_scl, "status"=>"clean", "action"=>"disable"))',
                        'imageUrl' => Yii::app()->request->baseUrl . '/images/icons/minus-circle-frame.png',
                        'visible' => '$data->enabled_scl'
                    ),
                    'download' => array(
                        'label' => 'Download file',
                        'url' => 'Yii::app()->createUrl("/manage/download", array("md5"=>$data->md5_scl, "type"=>"clean"))',
                        'imageUrl' => Yii::app()->request->baseUrl . '/images/download-icon.png',
                    ),
                    'delete' => array(
                        'url' => 'Yii::app()->createUrl("/manage/samples", array("idf"=>$data->id_scl, "status"=>"clean", "action"=>"delete"))'
                    )
                )
            )
        ),
    ));
    ?>
<?php } ?>

<script>
    $(document).ready(function () {
        $('.table_hint').each(function () {
            this.parentNode.hint = this.innerHTML;
            this.style.display = 'none';
        })

    })

</script>