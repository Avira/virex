<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the model class for table "permanent_statistics_user_psu".
 *
 * The followings are the available columns in table 'permanent_statistics_user_psu':
 * @property string $date_psu
 * @property string $hour_psu
 * @property string $idusr_psu
 * @property string $files_number_psu
 * @property string $files_size_psu
 */
class PermanentUserStatistics extends CActiveRecord
{

    public $start;
    public $end;
    public $type;
    public $group;

    /**
     * Returns the static model of the specified AR class.
     * @return PermanentUserStatistics the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'permanent_statistics_user_psu';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('date_psu, hour_psu, idusr_psu, files_number_psu, files_size_psu', 'required'),
            array('hour_psu', 'length', 'max' => 3),
            array('idusr_psu, files_number_psu, files_size_psu', 'length', 'max' => 10),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('date_psu, hour_psu, idusr_psu, files_number_psu, files_size_psu', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'date_psu' => 'Date',
            'hour_psu' => 'Hour',
            'idusr_psu' => 'User',
            'files_number_psu' => 'Files Number',
            'files_size_psu' => 'Files Size',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('date_psu', $this->date_psu, true);
        $criteria->compare('hour_psu', $this->hour_psu, true);
        $criteria->compare('idusr_psu', $this->idusr_psu, true);
        $criteria->compare('files_number_psu', $this->files_number_psu, true);
        $criteria->compare('files_size_psu', $this->files_size_psu, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

}
