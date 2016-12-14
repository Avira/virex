<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The statistics - chart view of the shared files
 */
Yiibase::import('application.extensions.chartjs2.Chartjs2');
$this->headlineText = "Shared files Statistics";
?>
<style type="text/css">
    div.row h4 {
        margin-bottom: 3px;
    }
    div.form div.inlinerow {
        float: left;
        margin-right: 15px;
    }
    .dateShortcut {
        margin-right: 6px;
    }
</style>
<?php
$frm = $this->beginWidget('CActiveForm', array(
    'id' => 'statistics',
    'method' => 'post',
        ));
?>
<div class="form" id="formDiv" style="display: inline-block;">
    <div>
        <div class="row inlinerow">
            <h4><?php echo CHtml::activeLabel($form, 'type'); ?></h4>
            <?php echo CHtml::activeDropDownList($form, 'type', array('uploadedfilesno' => 'Uploaded files no.', 'avgsamplesize' => 'Average sample size', 'avgarchivesize' => 'Average archive size', 'totalfilesize' => 'Total file size'), array('style' => 'width: 105px;')); ?>
        </div>
        <div class="row inlinerow">
            <h4><?php echo CHtml::activeLabel($form, 'group'); ?></h4>
            <?php echo CHtml::activeDropDownList($form, 'group', array('hour' => 'Hour', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month'), array('style' => 'width: 105px;')); ?>
        </div>
        <div class="row inlinerow" style="margin:0;">
            <div class="row inlinerow">
                <h4><?php echo CHtml::activeLabel($form, 'start'); ?></h4>
                <?php
                $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                    'name' => 'StatsForm[start]',
                    'value' => $form->start,
                    'options' => array(
                        'changeMonth' => true,
                        'changeYear' => true,
                        'maxDate' => '+0',
                        'minDate' => '2013-01-01',
                        'dateFormat' => 'yy-mm-dd',
                        'style' => 'margin-bottom: 0; width: 110px;',
                    ),
                    'htmlOptions' => array(
                        'style' => 'cursor:pointer; background:url(/images/icons/calendar.png) 98% center no-repeat #F0F0F0; width: 110px;'
                    )
                ));
                ?>
            </div>
            <div class="row inlinerow">
                <h4><?php echo CHtml::activeLabel($form, 'end'); ?></h4>
                <?php
                $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                    'name' => 'StatsForm[end]',
                    'value' => $form->end,
                    // additional javascript options for the date picker plugin
                    'options' => array(
                        'changeMonth' => true,
                        'changeYear' => true,
                        'minDate' => '2013-01-01',
                        'maxDate' => '+0',
                        'dateFormat' => 'yy-mm-dd',
                        'style' => 'margin-bottom: 0; width: 110px;',
                    ),
                    'htmlOptions' => array(
                        'style' => 'cursor:pointer; background:url(/images/icons/calendar.png) 98% center no-repeat #F0F0F0; width: 110px;'
                    )
                ));
                ?>
            </div>
            <br>
            <a href="#" class="dateShortcut" days="-1">yesterday</a>
            <a href="#" class="dateShortcut" days="-7">last 7 days</a>
            <a href="#" class="dateShortcut" days="-30">last 30 days</a>
        </div>
        <div class="row inlinerow">
            <?php echo CHtml::submitButton('Generate', array('id' => 'generateButton', 'style' => 'margin-top: 24px; min-width: 0;')); ?>
        </div>
    </div>
    <br clear="all"/>
</div><!-- form -->
<?php $this->endWidget(); ?>

<div style="text-align: center;display:inline-block; vertical-align:top; padding:10px;">
    <?php
    if (!empty($totals)) {
        $datasets = array();
        $colorsBold = array('rgba(123,117,161,0.8)', 'rgba(150,150,150,0.8)', 'rgba(34,117,156,0.8)');
        $colorsSoft = array('rgba(123,117,161,0.2)', 'rgba(150,150,150,0.2)', 'rgba(34,117,156,0.2)');
        $i = 0;
        foreach ($totals as $key => $total) {
            $datasets[] = array(
                'data' => array_values($total),
                'label' => $key,
                'pointRadius' => 2,
                'pointHoverRadius' => 2,
                'borderWidth' => 2,
                'borderColor' => $colorsBold[$i],
                'backgroundColor' => $colorsSoft[$i],
            );
            $i++;
        }
        $this->widget('application.extensions.chartjs2.Chartjs2', array(
            'id' => 'generated',
            'type' => $type,
            'width' => 900,
            'height' => 400,
            'chartOptions' => array(
                'maintainAspectRatio' => false,
                'animation' => false,
                'beginAtZero' => true,
                'legend' => array('display' => true),
                'scales' => array(
                    'yAxes' => array(
                        array(
                            'ticks' => array(
                                'beginAtZero' => true,
                                'min' => 0,
                                'stepSize' => $stepsize,
                            )
                        )
                    ),
                    'xAxes' => array(
                        array(
                            'gridLines' => array(
                                'display' => true
                            ),
                            'ticks' => array(
                                'autoSkip' => false
                            ),
                        )
                    )
                ),
            ),
            'chartData' => array('labels' => $labels,
                'datasets' => $datasets,
                'scaleStartValue' => '0',
                'scaleStepWidth' => '1',
            )
        ))->redraw();
    } else {
        ?><i>No information available.</i><?php
    }
    ?>
</div><br clear="all"/>

<script type="text/javascript">
    $('.dateShortcut').click(function () {
        $('#StatsForm_start').datepicker('setDate', $(this).attr('days'));
        $('#StatsForm_end').datepicker('setDate', '-1');
    });
</script>