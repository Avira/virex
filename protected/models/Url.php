<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the model class for table "urls_url".
 *
 * The followings are the available columns in table 'urls_url':
 * @property string $id_url
 * @property string $md5_url
 * @property string $sha256_url
 * @property string $url_url
 * @property string $added_when_url
 */
class Url extends CActiveRecord
{

    /**
     * Returns the static model of the specified AR class.
     * @return Url the static model class
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
        return 'urls_url';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('md5_url, url_url, added_when_url', 'required'),
            array('md5_url', 'length', 'max' => 32),
            array('sha256_url', 'length', 'max' => 64),
            array('url_url', 'length', 'max' => 210),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id_url, md5_url, sha256_url, url_url, added_when_url, enabled_url', 'safe', 'on' => 'search'),
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
            'id_url' => 'Id',
            'md5_url' => 'Md5',
            'sha256_url' => 'Sha256',
            'enabled_url' => 'Status',
            'url_url' => 'Url',
            'added_when_url' => 'Date added',
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

        $criteria->compare('id_url', $this->id_url);
        $criteria->compare('md5_url', $this->md5_url);
        $criteria->compare('sha256_url', $this->sha256_url);
        $criteria->compare('url_url', $this->url_url, true);
        $criteria->compare('enabled_url', $this->enabled_url);
        $criteria->compare('added_when_url', $this->added_when_url, true);
        if (isset($_SESSION['interval_start'])) {
            $criteria->addBetweenCondition('added_when_url', $_SESSION['interval_start'], $_SESSION['interval_end']);
        }

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
        ));
    }

    //Method used to return DataProvider for Searching
    public static function searchDataProvider()
    {
        $model = new Url('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Url'])) {
            $model->attributes = $_GET['Url'];
        }

        return $model->search();
    }

    //Method used to download URLs by special condition
    public function downloadUrls($condition, $params = array())
    {
        $comm = Yii::app()->db->createCommand("SELECT url_url FROM urls_url WHERE $condition LIMIT 0,5000");
        foreach ($params as $k => $p) {
            $comm->bindValue($k, $p);
        }
        $rows = $comm->queryAll();
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"url_list.txt\"");
        foreach ($rows as $r) {
            echo "$r[url_url]\n";
        }
        die();
    }

    //Method used to delete urls by special condition
    public function deleteUrls($condition, $params = array())
    {
        $nr = Yii::app()->db->createCommand("DELETE FROM urls_url WHERE $condition LIMIT 5000")->execute($params);
        Yii::app()->user->setFlash('_success', $nr . ' urls have been deleted!');
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

}
