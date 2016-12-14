<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The file search form
 */
$this->headlineText = 'Search samples';
?>
<div class="form">
    <table>
        <tr>
            <td style='width:50%;'>
                <h2 style='border-bottom:1px solid #8f8f8f;'>Search by hash</h2>
                <form method='post'>
                    <input type='text' style="width:280px;" name='search_hash' />
                    <input type='submit' value='Get File' />
                </form>
            </td>
        </tr>
    </table>
</div>