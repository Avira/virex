<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The checking email view
 */
if ($ok) {
    ?>
    Your email address has been validated and an administrator has been notified. You will receive a new message when your account is activated. <br />
<?php } else { ?>
    Wrong code!
    <?php
}