<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This class extends the CWidget class in order to have customized headline
 */

class Headline extends CWidget
{

    public $text = null;
    public $secondaryText = null;
    public $menuItems = null;
    public $menuId = 'secondarymenu';

    public function init()
    {
        $this->menuItems = MenuHelper::getSubMenuItems($this->controller);
    }

    public function run()
    {
        if (!($this->text || $this->menuItems)) {
            return true;
        }
        echo "<div id='headline'><div class='wrapper'>";
        if ($this->secondaryText) {
            echo "<h5 style='float: right; padding: 20px 120px 0 50px; margin: 0;'>{$this->secondaryText}</h5>";
        } elseif ($this->menuItems) {
            echo ("<div id='{$this->menuId}'>");
            $this->widget('zii.widgets.CMenu', array('items' => $this->menuItems));
            echo ("</div>");
        }
        echo "<h2>{$this->text}</h2>";
        echo "</div></div>";
    }

}
