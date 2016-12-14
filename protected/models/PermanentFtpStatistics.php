<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the model class for table "permanent_statistics_ftp_psf".
 *
 * The followings are the available columns in table 'permanent_statistics_ftp_psf':
 * @property string $date_psf
 * @property string $hour_psf
 * @property string $files_number_psf
 * @property string $files_size_pst
 */
class PermanentFtpStatistics extends CActiveRecord
{

    public $start;
    public $end;
    public $type;
    public $group;

    /**
     * Returns the static model of the specified AR class.
     * @return PermanentFtpStatistics the static model class
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
        return 'permanent_statistics_ftp_psf';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('date_psf, hour_psf, files_number_psf, files_size_pst', 'required'),
            array('hour_psf', 'length', 'max' => 3),
            array('files_number_psf, files_size_pst', 'length', 'max' => 10),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('date_psf, hour_psf, files_number_psf, files_size_pst', 'safe', 'on' => 'search'),
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
            'date_psf' => 'Date Psf',
            'hour_psf' => 'Hour Psf',
            'files_number_psf' => 'Files Number Psf',
            'files_size_pst' => 'Files Size Pst',
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

        $criteria->compare('date_psf', $this->date_psf, true);
        $criteria->compare('hour_psf', $this->hour_psf, true);
        $criteria->compare('files_number_psf', $this->files_number_psf, true);
        $criteria->compare('files_size_pst', $this->files_size_pst, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

}
