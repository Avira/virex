<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The view for the downloading client form
 */
$this->headlineText = 'Download client';
?>
<?php
if ($afterDownload) {
    ?> <br />
    You have downloaded Norman Sampleshare Client. If your download didn't start please <a href="#" onclick="location.reload(true);">click here</a> to try again.
    <br /><br />
    <span style="line-height:20px;">Please take the following into consideration:</span>
    <ul style="line-height:20px;">
        <li>your Virex password was included unencrypted in the file <i>sampleshare.php</i></li>
        <li>before using the client, edit the <i>sampleshare.php</i> file to fill in your gpg passphrase</li>
        <li>if you use a http proxy you can fill your address and port number in the <i>sampleshare.php</i> file</li>
    </ul>
    <script>
        setTimeout(function () {
            window.location = '<?php echo Yii::app()->request->baseUrl; ?>download_client?download';
        }, 400);
    </script>
    <?php
} else {
    ?>
    <div class="form wide">
        <?php echo CHtml::beginForm(); ?><br />
            <label style="width:300px;line-height:34px;">For security reasons please insert your password:</label>
            <input type="password" name="password" value="" autocomplete="off" />
            <input type="submit" name="download" value="Download" /><br />
        <?php echo CHtml::endForm(); ?>
    </div>
<?php
}