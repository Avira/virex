<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This class extends the CButtonColumn class in order to have customized buttons with icons on grid views
 */
Yii::import('zii.widgets.grid.CButtonColumn');

class IconButtonColumn extends CButtonColumn
{

    public function init()
    {
        $this->deleteButtonImageUrl = '/images/icons/cross.png';
        $this->updateButtonImageUrl = '/images/icons/pencil.png';
        $this->viewButtonImageUrl = '/images/icons/magnifier.png';
        $this->htmlOptions = array('style' => 'text-align:center;');
        parent::init();
    }

}
