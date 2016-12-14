<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the helper class for different miscellaneous operations
 */

class MiscHelper
{

    //Method used to display the info bar
    public static function outputInfoBar($id, $content)
    {
        echo("<div style='padding: 3px 3px 3px 23px; border: 1px solid #CCC; background-color: #FFA; margin: 5px;");
        echo("background-position:3px 50%; background-repeat: no-repeat; background-image: url(\"/images/icons/exclamation-white.png\");' ");
        echo(" id={$id}>{$content}</div>");
    }

    //Method used to format a date
    public static function smallDate($date)
    {
        return date('Y-m-d', strtotime($date));
    }

    //method used to format a date
    public static function niceDate($date, $noInterval = false, $showDayOfWeek = true)
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }
        if (!$date) {
            return 'never';
        }
        $days = array('', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $months = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'Octomber', 'November', 'December');
        $day = $days[(int) date('N', $date)];
        $month = $months[(int) date('m', $date)];
        if ($showDayOfWeek) {
            $niceDate = $day . ', ' . date('d', $date) . ' ' . $month . ' ' . date('Y', $date) . ' (' . date('H:i)', $date);
        } else {
            $niceDate = date('d', $date) . ' ' . $month . ' ' . date('Y', $date) . ' (' . date('H:i)', $date);
        }
        if ((date('Y-m-d') == date('Y-m-d', $date)) && (!$noInterval)) {
            if ((date('H') != date('H', $date)) && ((date('H') != (date('H', $date) + 1)) || (date('i') > date('i', $date)) )) {
                $niceDate = date('H') - date('H', $date);
                if ($niceDate != 1) {
                    $niceDate = $niceDate . ' hours ago';
                } else {
                    $niceDate = 'one hour ago';
                }
            } elseif (date('i') == date('i', $date)) {
                $niceDate = date('s') - date('s', $date);
                if ($niceDate < 2) {
                    $niceDate = 2;
                }
                $niceDate = $niceDate . ' seconds ago';
            } else {
                if (date('H') == date('H', $date)) {
                    $niceDate = date('i') - date('i', $date);
                } else {
                    $niceDate = date('i') + 60 - date('i', $date);
                }
                if ($niceDate != 1) {
                    $niceDate = $niceDate . ' minutes ago';
                } else {
                    $niceDate = 'one minute ago';
                }
            }
        }
        return $niceDate;
    }

}
