<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The first page view
 */
$this->headlineText = "System Status";
?>

<div id="system_health_container">
    <?php
    $this->widget('zii.widgets.grid.CGridView', array(
        'id' => 'sh-grid',
        'cssFile' => Yii::app()->request->baseUrl . '/css/gridview/styles.css',
        'summaryText' => '',
        'dataProvider' => $systemHealth,
        'columns' => array(
            'Property',
            array('name' => 'Value', 'htmlOptions' => array('style' => 'text-align:center;')),
            array('name' => 'Overview', 'type' => 'raw', 'htmlOptions' => array('style' => 'text-align:right;width:200px;'))
        ),
    ));
    ?>
</div>