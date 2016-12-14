<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The external user single view
 */
?>
<div class="view">
    <b><?php echo MiscHelper::niceDate($data->time_euh) ?>:</b>
    <?php echo $data->action_euh; ?>
</div>