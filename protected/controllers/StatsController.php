<?php

/*
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the Controller for the WebUI statistics actions
 */

class StatsController extends Controller
{

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/main';

    //Displays the traffic chart
    public function actionTraffic()
    {
        Yii::app()->db->createCommand("SET sql_mode = ''")->execute();
        $form = new StatsForm;
        if (isset($_POST['StatsForm'])) {
            $form->attributes = Yii::app()->request->getPost('StatsForm');
        }


        $type = 'line';
        $join = '';
        $condition = '1';
        switch ($form->type) {
            case 'filesno':
                $select = 'SUM(`files_in_list_count_psu`) AS `Requested files`, SUM(`files_number_psu`) AS `Downloaded files`, SUM(`files_unique_number_psu`) AS `New files`';
                $columns = array('Requested files', 'Downloaded files', 'New files');
                break;
            case 'filesize':
                $select = 'SUM(`files_size_psu`)/1000000 AS `Data size(MB)`, SUM(`files_number_psu`) AS `Files Number`';
                $columns = array('Data size(MB)', 'Files Number');
                break;
            default:
                $select = 'SUM(`files_size_psu`)/1000000 AS `Data size(MB)`, SUM(`files_number_psu`) AS `Files Number`';
                $columns = array('Data size(MB)', 'Files Number');
                break;
        }
        switch ($form->group) {
            case 'hour':
                $group = 'hour_psu';
                $select .= ', hour_psu as `group`';
                break;
            case 'day':
                $group = 'date_psu';
                $select .= ', date_psu as `group`';
                if ($form->start == $form->end) {
                    $type = 'bar';
                }
                break;
            case 'week':
                $group = 'DATE_FORMAT(`date_psu`,"%v")';
                $select .= ', DATE_FORMAT(`date_psu`,"%x - week %v") as `group`';
                $type = 'bar';
                break;
            case 'month':
                $group = 'DATE_FORMAT(`date_psu`,"%m")';
                $select .= ', DATE_FORMAT(`date_psu`,"%Y - month %m") as `group`';
                $type = 'bar';
                break;
            case 'user':
                $group = 'name_usr';
                $select .= ', name_usr as `group`';
                $type = 'bar';
                $join = 'LEFT JOIN external_users_usr ON id_usr = idusr_psu';
                break;
            default:
                $group = 'date_psu';
                $select .= ', date_psu as `group`';
                break;
        }

        if ($form->group == 'user') {
            if ((int) $form->user) {
                $labels = $this->rangeUsers($form->user);
            } else {
                $labels = $this->rangeUsers();
            }
        } else {
            if ($form->group == 'hour') {
                $labels = range(0, 23);
            } else {
                $labels = $this->rangeDates($form->start . ' 00:00:00', $form->end . ' 23:59:59', $form->group);
            }
        }
		$varsArr = array();
		$varsArr[] = $form->start . ' 00:00:00';
		$varsArr[] = $form->end . ' 23:59:59';
        if ((int) $form->user) {
            $condition = 'id_usr = ?';
			$varsArr[] = $form->user;
            $join = 'LEFT JOIN external_users_usr ON id_usr = idusr_psu';
        }
        foreach ($columns as $col) {
            $totals[$col] = array_fill_keys($labels, 0);
        }
        $q = "SELECT 
					" . $select . "
				FROM permanent_statistics_user_psu
				" . $join . "
                WHERE 
					(date_psu BETWEEN ? AND ?) AND
					" . $condition . "
                GROUP BY " . $group;
        $ds = Yii::app()->db->createCommand($q)->queryAll(true, $varsArr);
        $stepsize = 1;
        foreach ($ds as $d) {
            foreach ($columns as $col) {
                if (!$d[$col]) {
                    $totals[$col][$d['group']] = 0;
                } else {
                    $totals[$col][$d['group']] = $d[$col];
                    if ((int) ($d[$col]) <= 10 && $stepsize != '') {
                        $stepsize = '1';
                    } else {
                        $stepsize = '';
                    }
                }
            }
        }
        $this->render('trafficchart', array(
            'form' => $form,
            'totals' => $totals,
            'type' => $type,
            'labels' => $labels,
            'stepsize' => $stepsize
        ));
    }

    //Displays the shard files chart
    public function actionSharedfiles()
    {
        Yii::app()->db->createCommand("SET sql_mode = ''")->execute();
        $form = new StatsForm;
        if (isset($_POST['StatsForm'])) {
            $form->attributes = Yii::app()->request->getPost('StatsForm');
        }
        $type = 'line';
        $join = '';
        $condition = '1';
        switch ($form->type) {
            case 'uploadedfilesno':
                $select = 'SUM(`files_number_psf`) AS `Samples`, SUM(`archives_number_psf`) AS `Archives`';
                $columns = array('Samples', 'Archives');
                break;
            case 'avgsamplesize':
                $select = '(SUM(`files_size_psf`) / SUM(`files_number_psf`) / 1024) AS `Average Sample Size`';
                $columns = array('Average Sample Size');
                break;
            case 'avgarchivesize':
                $select = '(SUM(`files_size_psf`) / SUM(`archives_number_psf`) / 1048576) AS `Average Archive Size`';
                $columns = array('Average Archive Size');
                break;
            case 'totalfilesize':
                $select = '(SUM(`files_size_psf`) / 1000000) AS `Samples Size(MB)`, SUM(`files_number_psf`) AS `Samples Number`';
                $columns = array('Samples Size(MB)', 'Samples Number');
                break;
            default:
                $select = 'SUM(`files_number_psf`) AS `Samples`, SUM(`archives_number_psf`) AS `Archives`';
                $columns = array('Samples', 'Archives');
                break;
        }
        switch ($form->group) {
            case 'hour':
                $group = 'hour_psf';
                $select .= ', hour_psf as `group`';
                break;
            case 'day':
                $group = 'date_psf';
                $select .= ', date_psf as `group`';
                if ($form->start == $form->end) {
                    $type = 'bar';
                }
                break;
            case 'week':
                $group = 'DATE_FORMAT(`date_psf`,"%v")';
                $select .= ', DATE_FORMAT(`date_psf`,"%x - week %v") as `group`';
                $type = 'bar';
                break;
            case 'month':
                $group = 'DATE_FORMAT(`date_psf`,"%m")';
                $select .= ', DATE_FORMAT(`date_psf`,"%Y - month %m") as `group`';
                $type = 'bar';
                break;
            default:
                $group = 'date_psf';
                $select .= ', date_psf as `group`';
                break;
        }

        if ($form->group == 'hour') {
            $labels = range(0, 23);
        } else {
            $labels = $this->rangeDates($form->start . ' 00:00:00', $form->end . ' 23:59:59', $form->group);
        }

        foreach ($columns as $col) {
            $totals[$col] = array_fill_keys($labels, 0);
        }
        $q = "SELECT 
					" . $select . "
				FROM permanent_statistics_ftp_psf
				" . $join . "
                WHERE 
					(date_psf BETWEEN ? AND ?) AND
					" . $condition . "
                GROUP BY " . $group;
        $ds = Yii::app()->db->createCommand($q)->queryAll(true, array($form->start . ' 00:00:00', $form->end . ' 23:59:59'));
        $stepsize = 1;
        foreach ($ds as $d) {
            foreach ($columns as $col) {
                if (!$d[$col]) {
                    $totals[$col][$d['group']] = 0;
                } else {
                    $totals[$col][$d['group']] = $d[$col];
                    if ((int) ($d[$col]) <= 10 && $stepsize != '') {
                        $stepsize = '1';
                    } else {
                        $stepsize = '';
                    }
                }
            }
        }
        $this->render('sharedfileschart', array(
            'form' => $form,
            'totals' => $totals,
            'type' => $type,
            'labels' => $labels,
            'stepsize' => $stepsize
        ));
    }

    //Method used to fullfill a date interval
    protected function rangeDates($begin, $end, $group)
    {

        $period = new DatePeriod(new DateTime($begin), new DateInterval('P1D'), new DateTime($end));
        $ret = array();
        foreach ($period as $date) {
            switch ($group) {
                case 'day':
                    $ret[] = $date->format('Y-m-d');
                    break;
                case 'week':
                    $ret[] = $date->format('Y') . ' - week ' . $date->format('W');
                    break;
                case 'month':
                    $ret[] = $date->format('Y') . ' - month ' . $date->format('m');
                    break;
            }
        }
        return array_values(array_unique($ret));
    }

    //Method used to fullfill the users list
    protected function rangeUsers($defaultUserId = null)
    {
        $ret = array();
        if ($defaultUserId) {
            $users = ExternalUser::model()->findAllByAttributes(array('id_usr' => $defaultUserId, 'status_usr' => '2'), array('order' => 'name_usr ASC'));
        } else {
            $users = ExternalUser::model()->findAllByAttributes(array('status_usr' => '2'), array('order' => 'name_usr ASC'));
        }
        foreach ($users as $user) {
            $ret[] = $user->name_usr;
        }
        return $ret;
    }

}
