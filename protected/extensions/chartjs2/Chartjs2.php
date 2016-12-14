<?php

class Chartjs2 extends CWidget
{

    public $chartJsAssetsPath = '';
    public $chartJsFile = 'Chart.min.js';
    public $chartJsFilePath = '';
    public $width = 600;
    public $height = 300;
    public $type = 'line';
    public $valid_types = array('line', 'pie', 'bar');
    public $jsFile = '';
    public $timeSeriesFile = '';
    public $chartData = array();
    public $chartOptions = array();
    public $htmlOptions = array();

    public static function initStatic()
    {
        $c = new self();
        $c->normalizePaths();
        $c->registerChartJsFile();
    }

    public function init()
    {
        $this->normalizePaths();
        $this->handleWidgetId();

        $this->registerClientScripts();
        parent::init();
    }

    public function normalizePaths()
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = dirname(__FILE__);

        $this->chartJsAssetsPath = Yii::app()->getAssetManager()->publish($dir . $ds . 'assets');
        $this->chartJsFilePath = $this->chartJsAssetsPath . $ds . $this->chartJsFile;
    }

    public function handleWidgetId()
    {
        if (!$this->getId(false)) {
            $this->setId('chart');
        }
    }

    /**
     * Registers the external javascript files
     */
    public function registerClientScripts()
    {
        $this->registerChartJsFile();
        $this->registerJsCode();
    }

    public function registerChartJsFile()
    {
        $cs = Yii::app()->getClientScript();
        $cs->registerScriptFile($this->chartJsFilePath, CClientScript::POS_HEAD);
    }

    public function registerJsCode()
    {
        $cs = Yii::app()->getClientScript();
    }

    public function run()
    {
        $canvasOptions = array('id' => $this->id);
        if ($this->width) {
            $this->chartOptions['responsive'] = false;
            $canvasOptions['width'] = $this->width;
        } else {
            $this->chartOptions['maintainAspectRatio'] = false;
            $canvasOptions['style'] = 'width:100%;height:100%;';
        }
        if ($this->height) {
            $canvasOptions['height'] = $this->height;
        }
        echo CHtml::openTag('div', $this->htmlOptions);
        echo CHtml::openTag('canvas', $canvasOptions);
        echo CHtml::closeTag('canvas');
        echo CHtml::closeTag('div');
        parent::run();
    }

    protected function createJsCode()
    {

        $data = CJavaScript::encode($this->chartData);
        $options = CJavaScript::encode($this->chartOptions);

        $type = in_array($this->type, $this->valid_types) ? $this->type : reset($this->valid_types);

        $js = <<<CODE
var ctx = document.getElementById("{$this->getId()}");
new Chart(ctx, {type:'{$type}',data:{$data},options:{$options}});
CODE;

        return $js;
    }

    public function redraw()
    {
        echo '<script type="text/javascript">' . $this->createJsCode() . '</script>';
    }

    public static $colors = array('#4BACC6', '#92D050', '#00B0F0', '#7030A0', '#C00000', '#FFC000', '#808080', '#F79646', '#C0504D', '#4F81BD', '#9BBB59', '#8064A2', '#4BACC6',);
    public static $ncolors = array('red' => "#FF6384", 'green' => '#92D050', 'yellow' => "#FFCE56", 'blue' => "#36A2EB");

    public static function resetColors()
    {
        reset(self::$colors);
    }

    public static function nextColor()
    {
        if (!next(self::$colors)) {
            reset(self::$colors);
        }
        return self::lastColor();
    }

    public static function lastColor()
    {
        return self::hex2rgb(current(self::$colors));
    }

    public static function hex2rgb($hex)
    {
        return (hexdec(substr($hex, 1, 2)) . ',' . hexdec(substr($hex, 3, 2)) . ',' . hexdec(substr($hex, 5, 2)));
    }

    public static function green()
    {
        return self::hex2rgb(self::$ncolors['green']);
    }

    public static function blue()
    {
        return self::hex2rgb(self::$ncolors['blue']);
    }

    public static function red()
    {
        return self::hex2rgb(self::$ncolors['red']);
    }

    public static function yellow()
    {
        return self::hex2rgb(self::$ncolors['yellow']);
    }

}
