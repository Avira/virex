<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This component class is used to display progress bars 
 */

class ProgressBar
{

    var $border = 'solid 1px #000';
    var $mainBg = '#FFFFFF';
    var $doneBg = '#B8D1D4'; //#47B99B';
    var $newBg = '#FFFFFF';
    var $innerSpanCss = 'padding:3px;line-height:18px;';
    var $legendCss = 'padding:4px;';
    var $width, $total, $done, $percent;
    var $suff = ' done';

    //The initial method - used to initialize the attributes
    function ProgressBar($total = 1, $done = 0, $width = 100, $options = array())
    {
        foreach ($options as $opt => $value) {
            $this->$opt = $value;
        }
        $this->width = $width;
        $this->total = $total;
        $this->done = $done;
    }

    //Method used to show the bar
    public function display()
    {
        if ($this->total) {
            $this->percent = number_format($this->done * 100 / $this->total, 0);
        }
        $return = '';

        $return .= '<div style="width:' . ($this->width) . 'px;background:' . $this->mainBg . ';border:' . $this->border . ';text-align:center;margin:0 auto;">';
        if ($this->done) {
            $return .= '<div style="overflow:hidden;white-space:nowrap;width:' . floor($this->done * $this->width / $this->total) . 'px;float:left;background:' . $this->doneBg . ';' . '">';
            $return .= '<span style="' . $this->innerSpanCss . ';margin-right:-' . floor(($this->total - $this->done) * $this->width / $this->total) . 'px;">' . $this->percent . '%' . $this->suff . '</span>';
            $return .= '</div>';
        }
        if ($this->done < $this->total) {
            $return .= '<div style="overflow:hidden;white-space:nowrap;width:' . floor(($this->total - $this->done) * $this->width / $this->total) . 'px;float:left;">';
            $return .= '<span style="' . $this->innerSpanCss . ';margin-left:-' . floor($this->done * $this->width / $this->total) . 'px;">' . $this->percent . '%' . $this->suff . '</span>';
            $return .= '</div>';
        }
        $return .= '<br clear="all" /></div>';
        return $return;
    }

    //Method used to show the legend
    public function displayLegend()
    {
        echo '<div style="' . $this->legendCss . '">';

        echo '<div style="float:left;margin-right:20px;line-height:10px;">';
        echo '<div style="width: 10px; height: 10px; display: inline-block; border: ' . $this->border . ';background:' . $this->doneBg . ';margin-right:5px;">&nbsp;</div>' . $this->done . ' done';
        echo '</div>';

        echo '<div style="float:left;line-height:10px;">';
        echo '<div style="width: 10px; height: 10px; display: inline-block; border: ' . $this->border . ';background:' . $this->newBg . ';margin-right:5px;">&nbsp;</div>' . ($this->total - $this->done) . ' new';
        echo '</div>';

        echo '<br clear="all" /></div>';
    }

}
