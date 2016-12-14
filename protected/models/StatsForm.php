<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the StatsForm model used for the Statistics controller
 */
class StatsForm extends CFormModel
{

    public $type;
    public $user;
    public $start;
    public $end;
    public $group;

    //The initialization method
    public function __construct()
    {
        $this->type = 'filesno';
        $this->user = '';
        $this->start = date('Y-m-d', strtotime('-7 days'));
        $this->end = date('Y-m-d');
        $this->group = 'day';
    }

    //The rules method
    public function rules()
    {
        return array(
            array('type, user, start, end, group', 'safe'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'type' => 'Type',
            'user' => 'User',
            'start' => 'Start Date',
            'end' => 'End Date',
            'group' => 'Group By',
        );
    }

}
