<?php
/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * The external user statistics view
 */
$this->headlineText = 'History for ' . $userModel->name_usr;
?>
<div class="actionBar">
    <a class="icon back" href="<?php echo Yii::app()->createUrl('externaluser/update', array('id' => $userModel->id_usr)); ?>" >Go back to user details</a>
    <?php if ($action == 'details') { ?>
        <a class="icon list" href="<?php echo Yii::app()->createUrl('externaluser/statistics', array('id' => $userModel->id_usr)); ?>" >Go back general history</a>
    <?php } ?>
</div>
<?php if ($action == 'details') { ?>
    <table>
        <tr>
            <td>
                <h2 style='border-bottom:1px solid #8f8f8f;'>General</h2>
                <ul>
                    <li>Search query: <b><?php echo $selected_list['text_usl']; ?></b></li>
                    <li>Search date: <b><?php echo $selected_list['date_usl']; ?></b></li>
                    <li>List: <b><?php echo $selected_list['list_type_usl']; ?></b></li>
                    <li>Start date: <b><?php echo $selected_list['start_interval_usl']; ?></b></li>
                    <li>End date: <b><?php echo $selected_list['end_interval_usl']; ?></b></li>
                    <li>Number of results: <b><?php echo $selected_list['number_of_files_usl'] ?></b></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td style=" vertical-align:top;">
                <h2 style='border-bottom:1px solid #8f8f8f;'>Files</h2>
                <ul class="file_list_md5">
                    <?php
                    if (!($list = $userModel->list_details($selected_list['id_usl']))) {
                        echo "<li><i>No files downloaded yet!</i></li>";
                    } else {
                        foreach ($list as $l) {
                            echo "<li style='font-family:\"Courier New\";'><i>($l[date_usf])</i> <b>$l[md5_usf]</b> - (downloaded $l[count_usf] times)</li>";
                        }
                    }
                    ?>
                </ul>
            </td>
        </tr>
    </table>
    <style>
        .file_list_md5 a{
            color:#555555;
            text-decoration: none;
        }
        .file_list_md5 a:hover{
            color:#0099FF;
            text-decoration:underline;
        }

    </style>
<?php } else { ?>
    <fieldset><legend>General</legend>
        <table>
            <tr>
                <td style='width:40%; vertical-align:top;'>
                    <h2 style='border-bottom:1px solid #8f8f8f;'>Last downloaded Lists</h2>
                    <ul>
                        <?php
                        if (!($list = $userModel->get_file_lists())) {
                            echo "<li><i>No list found!</i></li>";
                        } else {
                            foreach ($list as $l) {
                                echo "
				    <li>
					<a href='" . Yii::app()->createUrl('externaluser/statistics', array(
                                    'id' => $userModel->id_usr,
                                    'action' => 'details',
                                    'list_id' => $l['id_usl']
                                )) . "'> " . date('d/m/Y H:i', strtotime($l['date_usl'])) . ".txt ( $l[number_of_files_usl] files ) </a>
					</a>
				    </li>
				";
                            }
                        }
                        ?>
                    </ul>
                </td>
                <td style=" vertical-align:top;">
                    <h2 style='border-bottom:1px solid #8f8f8f;'>Last downloaded Files</h2>
                    <ul class="file_list_md5">
                        <?php
                        if (!($list = $userModel->get_downloaded_files(30))) {
                            echo "<li><i>No files downloaded yet!</i></li>";
                        } else {
                            foreach ($list as $l) {
                                echo "
					<li style='font-family:\"Courier New\";'>
					    <i>(" . date('d/m/Y H:i', strtotime($l['date_usf'])) . ")</i> <b>$l[md5_usf]</b> - ($l[count_usf] times)
					</li>
				    ";
                            }
                        }
                        ?>
                    </ul>
                </td>
            </tr>
        </table>
    </fieldset>
    <style>
        .file_list_md5 a{
            color:#555555;
            text-decoration: none;
        }
        .file_list_md5 a:hover{
            color:#0099FF;
            text-decoration:underline;
        }

    </style>
<?php } ?>
<br />