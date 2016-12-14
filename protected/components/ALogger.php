<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the component class used to log the activity of the cronjobs
 */

class ALogger
{

    /// COLORS LIST:

    const CBLACK = 'black';
    const CDARK_GRAY = 'dark_gray';
    const CBLUE = 'blue';
    const CLIGHT_BLUE = 'light_blue';
    const CGREEN = 'green';
    const CLIGHT_GREEN = 'light_green';
    const CCYAN = 'cyan';
    const CLIGHT_CYAN = 'light_cyan';
    const CRED = 'red';
    const CLIGHT_RED = 'light_red';
    const CPURPLE = 'purple';
    const CLIGHT_PURPLE = 'light_purple';
    const CBROWN = 'brown';
    const CYELLOW = 'yellow';
    const CLIGHT_GRAY = 'light_gray';
    const CWHITE = 'white';
    /// BACKGROUNDS LIST:
    const BBLACK = 'black';
    const BRED = 'red';
    const BGREEN = 'green';
    const BYELLOW = 'yellow';
    const BBLUE = 'blue';
    const BMAGENTA = 'magenta';
    const BCYAN = 'cyan';
    const LIGHT_GRAY = 'light_gray';

    protected static $currentProgressBarDone = null;
    protected static $currentProgressBarTotal = null;
    protected static $currentAction = array();
    protected static $foregroundColors = array();
    protected static $backgroundColors = array();
    protected static $lastStep = '';
    protected static $startTime = null;
    protected static $totalOK = 0;
    protected static $totalErrors = 0;
    protected static $oldErrorHandler = null; //remembers old error handler in case that you replace it with ALogger
    private static $screen = null;
    //################################ USER SETTINGS ################################

    public static $useColors = 'auto';             // set to false if you never want to log in colors
    public static $debug = false;                       // set this to true in order to show messages
    // if is set to false then only errors will be visible and with no colors so that they can be read from file.
    // also for false critical errors will also generate a Yii error so that it will send an email to the developer
    public static $error_color = 'light_red';           // error
    public static $critical_error_color = 'light_red';  // critical error
    public static $step_color = 'light_blue';           // step start and end color
    public static $default_log_color = 'white';         // default log color
    public static $default_action_color = 'white';         // default action text color
    public static $default_action_continue_color = null;        // default action continue color
    public static $default_action_done_color = 'light_green';   // default action done color
    public static $date_color = null;
    public static $time_format = 'Y-m-d H:i:s';         // php time format
    public static $default_action_done_message = 'done';        // default action done message
    public static $time_separator_start = '';              // start of time
    public static $time_separator_end = ' ';             // separator between time and message
    public static $step_separator = '>>>>> ';
    public static $show_time_on_logs = true;            // show time on logs or just for errors and actions
    public static $align_action_result_to_right = true;

    //############################## END: USER SETTINGS ##############################
    //Method used to format a date from a time parameter
    public static function get_date($time = null)
    {
        $time = $time ? $time : time();
        return self::color(self::$date_color, self::$time_separator_start . date(self::$time_format, $time) . self::$time_separator_end);
    }

    /**
     * shows a message when is invoked
     * @param string $message
     * @param string $color
     * @param string $type  ('debug','log','mail')
     */
    public static function log($message, $color = 'default', $type = 'debug', $incrementProgressBar = true, $background = null)
    {
        if (!self::$debug && $type == 'debug') {
            return true;
        }
        if ($color == 'default') {
            $color = self::$default_log_color;
        }

        $showTime = self::$show_time_on_logs;
        if ($showTime) {
            echo self::get_date();
        }

        if ('mail' == $type) {
            Yii::log($message);
        }

        if (self::getScreen('columns')) {
            $message = str_pad($message, self::getScreen('columns') - ($showTime ? 20 : 0));
        }
        echo self::color($color, $message, $background) . "\n";

        if (!empty(self::$currentProgressBarTotal)) {
            if ($incrementProgressBar) {
                self::$totalOK++;
            }
            self::progress_bar(null, null, $incrementProgressBar);
        } //end::render the progressbar if a current one is set
    }

    //Method used to start the progress bar animation
    public static function start_progress_bar($total)
    {
        if ($total < 5) {
            return true;
        }
        if (!self::$debug) {
            return true;
        }
        self::$currentProgressBarTotal = $total;
        self::$currentProgressBarDone = 0;
        return true;
    }

    //Method used to stop the progress bar animation
    public static function end_progress_bar()
    {
        self::$currentProgressBarTotal = self::$currentProgressBarDone = null;
        self::empty_line();
        return true;
    }

    //Method used to display the progress bar
    public static function progress_bar($done = null, $total = null, $autoUpdate = true, $colorDone = 'light_green', $colorTotal = 'white', $colorCurrent = 'yellow', $width = 100)
    {
        if (!self::$debug) {
            return true;
        }

        $columns = self::getScreen('columns');
        if ($columns < 40) {
            return false;
        }
        if ($width + 25 > $columns) {
            $width = $columns - 25;
        }

        if ((null === $done) || (null === $total)) {
            if (empty(self::$currentProgressBarTotal)) {
                throw new Exception('Missing progress bar values!', 100);
            }
            self::$currentProgressBarDone += ($autoUpdate ? 1 : 0);
            $done = self::$currentProgressBarDone;
            $total = self::$currentProgressBarTotal;
        }

        $percent = round(($done / $total) * 100);
        $fill = round($percent / (100 / $width));
        echo (' ');
        echo self::color($colorTotal, '[');
        if ($fill && $fill < $width) {
            echo self::color($colorDone, str_repeat('=', $fill - 1));
            echo self::color($colorCurrent, str_repeat('=', 1));
        } else {
            echo self::color($colorDone, str_repeat('=', $fill));
        }
        echo self::color($colorTotal, str_repeat('-', $width - $fill));
        echo self::color($colorTotal, "] {$percent}% ({$done}/{$total}) \r");
    }

    /**
     * starts a new step
     * @param string $message
     */
    public static function step($message)
    {
        ALogger::end_action();
        if (self::$lastStep) {
            self::end_step();
        }
        self::$lastStep = $message;
        if (self::$debug) {
            echo self::color(self::$step_color, strtoupper(self::$step_separator . '[START] ' . $message . '...')) . "\n";
        }
    }

    /**
     * ends current open step is one is open
     */
    public static function end_step()
    {
        ALogger::end_action();
        if (self::$debug) {
            if (self::$lastStep) {
                echo self::color(self::$step_color, strtoupper(self::$step_separator . '[ END ] ' . self::$lastStep . ';')) . "\n\n";
                self::$lastStep = '';
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Starts a new action. You can change default text color
     * @param string $message
     * @param string $color
     */
    public static function start_action($message, $color = 'default')
    {
        if (self::$currentAction) {
            self::end_action();
        }
        if ('default' == $color) {
            $color = self::$default_action_color;
        }
        $date = self::get_date();
        self::$currentAction = array(
            'name' => $message,
            'time' => date('Y-m-d H:i:s'),
            'exact_start_time' => microtime(true),
            'color' => $color,
            'errors' => array(),
            'success_message' => array(
                'text' => '',
                'color' => null
            ),
            'length' => strlen($message) + strlen($date) - 5
        );
        if (self::$debug) {
            echo $date . self::color($color, self::$currentAction['name']);
        }
    }

    public static function continue_action($message, $color = 'default', $background = null)
    {
        if ('default' == $color) {
            $color = self::$default_action_continue_color;
        }
        if (self::$debug) {
            self::$currentAction['length'] += strlen(" [" . date('H:i:s') . " $message]");
            echo self::color($color, " [" . date('H:i:s') . " $message]", $background);
        }
    }

    /**
     * Shows a current action error.
     * @param string $message
     * @param boolean $critical
     */
    public static function error($message, $critical = false, $incrementProgressBar = true)
    {
        self::$totalErrors++;
        if ($critical) {
            $errorMessage = "CRITICAL ERROR";
        } else {
            $errorMessage = "ERROR";
        }
        if (self::$currentAction) {
            self::$currentAction['errors'][] = array('message' => $message, 'time' => date('Y-m-d H:i:s'), 'critical' => $critical);
            if ($critical) {
                self::show_action();
            }
        } else {
            $message = '[' . $errorMessage . '] ' . $message;
            if (self::$currentProgressBarTotal) {
                if (self::getScreen('clumns')) {
                    $message = str_pad($message, self::getScreen('columns') - 20);
                }
            }
            echo self::get_date() . self::color($critical ? self::$critical_error_color : self::$error_color, $message) . "\n";
        }

        if (!$critical && !empty(self::$currentProgressBarTotal)) {
            self::progress_bar(null, null, $incrementProgressBar);
        } //end::render the progressbar if a current one is set

        if ($critical && (!self::$debug)) {
            Yii::log('[' . $errorMessage . '] ' . $message, 'error');
        }
        if ($critical) {
            Yii::app()->end();
        }
    }

    /**
     * Ends current action . Also a succes message can be specified  and is visible if there are no errors
     * @param string $succes_message
     * @param string $color
     */
    public static function end_action($succes_message = null, $color = 'default')
    {
        if (!self::$currentAction) {
            return;
        }
        if (!$succes_message) {
            $succes_message = self::$default_action_done_message;
        }
        if ('default' == $color) {
            $color = self::$default_action_done_color;
        }
        self::$currentAction['success_message'] = array(
            'text' => $succes_message,
            'color' => $color
        );
        if (!count(self::$currentAction['errors'])) {
            self::$totalOK++;
        }
        if (count(self::$currentAction['errors']) || self::$debug) {
            self::show_action();
        }
        self::$currentAction = null;
    }

    //Method used to go to the next line
    public static function empty_line()
    {
        if (self::$debug) {
            echo "\n";
        }
    }

    //Method used to show the statistics
    public static function getStats()
    {
        return array(self::$totalOK, self::$totalErrors);
    }

    //Method used to log the statistics
    public static function logStats()
    {
        if (!self::$debug) {
            return true;
        }
        if (!ALogger::end_step()) {
            ALogger::empty_line();
        }
        $execTime = number_format(microtime(true) - self::$startTime, 2) . 's';
        $msg = "Time: " . self::color(self::CWHITE, $execTime) . "; Memory: " . self::color(self::CWHITE, FileHelper::formatSize(memory_get_peak_usage(true)));
        self::$show_time_on_logs = false;
        self::log($msg, null, 'debug', false);
        list($ok, $errors) = self::getStats();
        $errors = self::color(self::CLIGHT_RED, $errors);
        $ok = self::color(self::CLIGHT_GREEN, $ok);

        self::log('Errors: ' . $errors . '; Succesfull: ' . $ok, null, 'debug', false);
        self::empty_line();
    }

    //Method used to set the color
    public static function color($color, $message, $background = null)
    {
        if (!self::$useColors || (PHP_OS == 'WINNT')) {
            return $message;
        }
        $colored_string = "";
        // Check if given foreground color found
        if (isset(self::$foregroundColors[$color])) {
            $colored_string .= "\033[" . self::$foregroundColors[$color] . "m";
        }
        // Check if given background color found
        if (isset(self::$backgroundColors[$background])) {
            $colored_string .= "\033[" . self::$backgroundColors[$background] . "m";
        }

        // Add string and end coloring
        if ($color || $background) {
            $colored_string .= $message . "\033[0m";
        } else {
            $colored_string = $message;
        }
        return $colored_string;
    }

    //Method used to show the current action
    private static function show_action()
    {
        if (self::$currentAction) {
            if (!self::$debug) {
                echo self::get_date(strtotime(self::$currentAction['time'])) . self::color(self::$currentAction['color'], self::$currentAction['name']);
            }
            if (count(self::$currentAction['errors']) > 1) {
                echo "\n";
                foreach (self::$currentAction['errors'] as $error) {
                    if ($error['critical']) {
                        $errorMessage = "CRITICAL ERROR";
                    } else {
                        $errorMessage = "ERROR";
                    }
                    echo self::get_date(strtotime($error['time'])) . self::color($error['critical'] ? self::$critical_error_color : self::$error_color, '[' . $errorMessage . ']' . $error['message'] . "\n");
                }
                echo "\n";
            } elseif (count(self::$currentAction['errors']) > 0) {
                if (self::$currentAction['errors'][0]['critical']) {
                    $errorMessage = "CRITICAL ERROR";
                } else {
                    $errorMessage = "ERROR";
                }
                echo self::color(self::$currentAction['errors'][0]['critical'] ? self::$critical_error_color : self::$error_color, '[' . $errorMessage . ':' . self::$currentAction['errors'][0]['message'] . ']' . "\n");
            } else {
                $time = self::timeItTook(self::$currentAction['exact_start_time']);
                if (self::$align_action_result_to_right && self::getScreen('columns')) {
                    $length = self::$currentAction['length'] + self::$lastTimeLenght + strlen(' [' . self::$currentAction['success_message']['text'] . '] ');
                    $spaceLength = self::getScreen('columns') - $length - 2;
                    echo ' ';
                    echo str_repeat('.', $spaceLength);
                }
                echo self::color(self::$currentAction['success_message']['color'], ' [' . self::$currentAction['success_message']['text'] . '] ') .
                $time . "\n";
            }
        }
    }

    //Method used to show some information on the screen
    protected static function getScreen($what)
    {
        if (!empty(self::$screen)) {
            return self::$screen[$what];
        }

        self::$screen['rows'] = self::$screen['columns'] = 0;
        preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a |grep columns')), $output);
        if (sizeof($output) == 3) {
            if (count($output[0])) {
                self::$screen['rows'] = $output[1][0];
                self::$screen['columns'] = $output[2][0];
            } else {
                self::$screen['columns'] = 140;
                self::$screen['rows'] = 40;
            }
        }

        return self::$screen[$what];
    }

    private static $lastTimeLenght = 0;

    public static function timeItTook($startTime)
    {
        $time = microtime(true) - $startTime;
        $time = number_format($time, 4);
        $time = '[' . $time . 's]';
        self::$lastTimeLenght = strlen($time);
        if ($time < 60) {
            return self::color(self::$date_color, $time);
        }
        $minutes = (int) ($time / 60);
        $seconds = $time - ($minutes * 60);
        $time = '[' . $minutes . 'm' . $seconds . 's]';
        self::$lastTimeLenght = strlen($time);
        return self::color(self::$date_color, $time);
    }

    public static $htmlColors = array();

    public static function init()
    {
        self::$startTime = microtime(true);

        self::$foregroundColors['black'] = '0;30';
        self::$foregroundColors['dark_gray'] = '1;30';
        self::$foregroundColors['blue'] = '0;34';
        self::$foregroundColors['light_blue'] = '1;34';
        self::$foregroundColors['green'] = '0;32';
        self::$foregroundColors['light_green'] = '1;32';
        self::$foregroundColors['cyan'] = '0;36';
        self::$foregroundColors['light_cyan'] = '1;36';
        self::$foregroundColors['red'] = '0;31';
        self::$foregroundColors['light_red'] = '1;31';
        self::$foregroundColors['purple'] = '0;35';
        self::$foregroundColors['light_purple'] = '1;35';
        self::$foregroundColors['brown'] = '0;33';
        self::$foregroundColors['yellow'] = '1;33';
        self::$foregroundColors['light_gray'] = '0;37';
        self::$foregroundColors['white'] = '1;37';

        self::$htmlColors['black'] = '#000000';
        self::$htmlColors['dark_gray'] = '#505050';
        self::$htmlColors['blue'] = '#0000ff';
        self::$htmlColors['light_blue'] = '#5555ff';
        self::$htmlColors['green'] = '#00ff00';
        self::$htmlColors['light_green'] = '#55ff55';
        self::$htmlColors['cyan'] = '#00dddd';
        self::$htmlColors['light_cyan'] = '#44ffff';
        self::$htmlColors['red'] = '#ff0000';
        self::$htmlColors['light_red'] = '#ff5555';
        self::$htmlColors['purple'] = '#800080';
        self::$htmlColors['light_purple'] = '#FF0080';
        self::$htmlColors['yellow'] = '#FFFF00';
        self::$htmlColors['brown'] = '#A52A2A';
        self::$htmlColors['light_gray'] = '#808080';
        self::$htmlColors['white'] = '#ffffff';
        self::$htmlColors['magenta'] = '#FF00FF';

        self::$backgroundColors['black'] = '40';
        self::$backgroundColors['red'] = '41';
        self::$backgroundColors['green'] = '42';
        self::$backgroundColors['yellow'] = '43';
        self::$backgroundColors['blue'] = '44';
        self::$backgroundColors['magenta'] = '45';
        self::$backgroundColors['cyan'] = '46';
        self::$backgroundColors['light_gray'] = '47';
    }

    public static function init_handle_errors()
    {
        self::$oldErrorHandler = set_error_handler('ALogger::handle_error');
    }

    public static function handle_error($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (ini_get('error_reporting') == 0) {
            return;
        }
        if (self::$debug) {
            self::error('[' . $errfile . ':' . $errline . '] ' . $errno . ':' . $errstr, (!in_array($errno, array(E_WARNING, E_NOTICE, E_USER_NOTICE, E_USER_NOTICE))));
        } else {
            if (self::$oldErrorHandler) {
                call_user_func(self::$oldErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
            }
        }
    }

    /**
     * ALogger::logToHtml()
     * 
     * Parse a log created by ALogger and prepares it for html view(adds colors and stuff..)
     * @param string $logText  original log text
     * @param bool $userPre  if set to false then it will use <Br /> for new line , else inserts the code inside <pre></pre>
     * @return string html text
     */
    public static function logToHtml($logText, $userPre = true)
    {
        $patterns = array();
        $replacements = array();
        foreach (self::$foregroundColors as $k => $f) {
            foreach (self::$backgroundColors as $k1 => $b) {
                $patterns[] = '/\033\[' . $f . 'm\033\[' . $b . 'm/';
                $replacements[] = '<span style="color:' . self::$htmlColors[$k] . '; background:' . self::$htmlColors[$k1] . ';">';
            }
            $patterns[] = '/\033\[' . $f . 'm/';
            $replacements[] = '<span style="color:' . self::$htmlColors[$k] . '">';
        }
        $patterns[] = '/\033\[0m/';
        $replacements[] = '</span>';
        $content = preg_replace($patterns, $replacements, $logText);
        if ($userPre) {
            return "<pre style='background:#000;color:#cfcfcf;font-size:11px;padding:5px;min-width:800px;overflow:auto;'>" . $content . '</pre>';
        } else {
            return nl2br($content);
        }
    }

    public static function logToTextFile($logText)
    {
        return strip_tags(self::logToHtml($logText));
    }

}

ALogger::init();
