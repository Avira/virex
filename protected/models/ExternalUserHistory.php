<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * 
 * This is the model class for table "external_users_history_euh".
 *
 * The followings are the available columns in table 'external_users_history_euh':
 * @property string $id_euh
 * @property string $action_euh
 * @property integer $idusr_euh
 * @property string $time
 */
class ExternalUserHistory extends CActiveRecord
{

    /**
     * Returns the static model of the specified AR class.
     * @return ExternalUserHistory the static model class
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
        return 'external_users_history_euh';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('action_euh, idusr_euh', 'required'),
            array('id_euh, action_euh, idusr_euh, time_euh', 'safe', 'on' => 'search'),
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
            'id_euh' => 'Id Euh',
            'action_euh' => 'Action Euh',
            'idusr_euh' => 'Idusr Euh',
            'time_euh' => 'Time',
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

        $criteria->compare('id_euh', $this->id_euh, true);
        $criteria->compare('action_euh', $this->action_euh, true);
        $criteria->compare('idusr_euh', $this->idusr_euh);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    //Method used to add the user log
    public static function addLog($message, $user = null)
    {
        $user = $user ? $user : Yii::app()->user->userId;
        $model = new ExternalUserHistory();
        $model->action_euh = $message;
        $model->idusr_euh = $user;
        $model->save();
    }

    //Method used to get all the logs of an user
    public static function getUserLogs($userId, $limit = 40)
    {
        return self::model()->findAllByAttributes(array('idusr_euh' => $userId), array('limit' => $limit, 'order_by' => 'time_euh desc'));
    }

}
