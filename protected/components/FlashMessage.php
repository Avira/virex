<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This class extends the CWidget class in order to have customized flash messages
 */

class FlashMessage extends CWidget
{

    public $messageName;
    public $messageTypes = array('error', 'notice', 'success');

    public function run()
    {
        if (!is_array($this->messageTypes)) {
            return false;
        }
        foreach ($this->messageTypes as $messageType) {
            if (Yii::app()->user->hasFlash($this->messageName . '_' . $messageType)) {
                echo("<div class='flash-{$messageType}' style='opacity:0.1;'>" . Yii::app()->user->getFlash($this->messageName . '_' . $messageType) . "</div>");
                echo("<script>$(function() {");
                echo("$('div.flash-{$messageType}').animate({opacity: 1}, 1000);");
                echo("$('div.flash-{$messageType}').click(function() { $(this).animate({opacity: 0.1}, 1000).hide('fast'); });");
                echo("});</script>");
            }
        }
    }

}
