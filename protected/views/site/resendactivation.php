<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The resend activation form view
 */
$this->pageTitle = Yii::app()->name . ' - Send activation email';
$this->headlineText = 'Send activation link';
$this->headlineSubText = 'Please fill out the following form with your email address.';
?>
<br /><br /><br />
<div class='login_form'>
    <div class="form wide">
        <form method="post">
            <div class="row"> 
                <label>Email address: </label>
                <input type="text" name="email" value="" />
            </div>
            <div class="row buttons">
                <input type="submit" name="resend_activation" value="Send activation link" />
            </div>
        </form>
    </div><!-- form -->
</div>
