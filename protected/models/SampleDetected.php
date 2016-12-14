<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * This is the model class for table "samples_detected_sde".
 *
 * The followings are the available columns in table 'samples_detected_sde':
 * @property string $id_sde
 * @property string $md5_sde
 * @property string $sha256_sde
 * @property string $detection_sde
 * @property string $file_size_sde
 */
class SampleDetected extends CActiveRecord
{

    public $visible_prefix = false;

    /**
     * Returns the static model of the specified AR class.
     * @return SampleDetected the static model class
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
        return 'samples_detected_sde';
    }

    public $hex;
    public $hexSha256 = '';

    function ascii2hex($ascii)
    {
        $hex = '';
        for ($i = 0; $i < strlen($ascii); $i++) {
            $byte = strtoupper(dechex(ord($ascii{$i})));
            $byte = str_repeat('0', 2 - strlen($byte)) . $byte;
            $hex .= $byte . "";
        }
        return $hex;
    }

    //The after find trigger method
    public function afterFind()
    {
        $this->hex = $this->ascii2hex($this->md5_sde);
		if(isset($this->sha256_sde)){
			$this->hexSha256 = $this->ascii2hex($this->sha256_sde);
		}
        
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('md5_sde, file_size_sde', 'required'),
            array('md5_sde', 'length', 'max' => 32),
            array('sha256_sde', 'length', 'max' => 64),
            array('file_size_sde', 'length', 'max' => 10),
            array('detection_sde', 'length', 'max' => 20),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id_sde, md5_sde, sha256_sde, type_sde, detection_sde, file_size_sde, added_when_sde, enabled_sde, pending_action_sde', 'safe', 'on' => 'search'),
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
            'id_sde' => 'Id',
            'md5_sde' => 'MD5',
            'sha256_sde' => 'SHA256',
            'sha256hash' => 'SHA256',
            'detection_sde' => 'Detection',
            'file_size_sde' => 'Size',
            'enabled_sde' => 'Status',
            'added_when_sde' => 'Date added',
            'pending_action_sde' => 'Pending action',
            'type_sde' => 'Type'
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

        $criteria->compare('id_sde', $this->id_sde);
        $criteria->compare('md5_sde', $this->md5_sde);
        $criteria->compare('sha256_sde', $this->sha256_sde);
        $criteria->compare('detection_sde', $this->detection_sde, true);
        $criteria->compare('added_when_sde', $this->added_when_sde, true);
        $criteria->compare('type_sde', $this->type_sde);
        $criteria->compare('pending_action_sde', $this->pending_action_sde);
        $criteria->compare('enabled_sde', $this->enabled_sde);
        if (isset($_SESSION['interval_start'])) {
            $criteria->addBetweenCondition('added_when_sde', $_SESSION['interval_start'], $_SESSION['interval_end']);
        }
        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => VIREX_PAGE_SIZE
            ),
            'sort' => array('defaultOrder' => 'id_sde DESC')
        ));
    }

    public function deleteFiles($where, $values = array())
    {
        $nerrors = 0;
        $nok = 0;

        $stillExists = Yii::app()->db->createCommand("SELECT count(*) 'n' FROM samples_detected_sde WHERE md5_sde=:md5");
        $deleteId = Yii::app()->db->createCommand("DELETE FROM samples_detected_sde WHERE id_sde=:id");
        $start = 0;

        while ($files = Yii::app()->db->createCommand("SELECT hex(md5_sde) 'hex', md5_sde, id_sde FROM samples_detected_sde WHERE " . $where)->queryAll(true, $values)) {
            ALogger::log('Found ' . count($files) . ' samples to delete.');
            $start += 5000;
            foreach ($files as $f) {
                ALogger::start_action('deleting ' . $f['md5_sde'] . '..');
                $deleteId->execute(array('id' => $f['id_sde']));
                $exists = $stillExists->bindValue(':md5', $f['md5_sde'])->queryRow();
                if (!$exists['n']) { // I delete it only if is not in db anymore( it can be deleted only from monthly and still be in daily for example..)
                    $fName = PathFinder::get(VIREX_STORAGE_PATH, 'detected', '') . substr($f['hex'], 0, 3) . '/' . substr($f['hex'], 3, 3) . '/' . substr($f['hex'], 6, 3) . '/' . $f['hex'];
                    if (file_exists($fName)) {
                        try {
                            if (unlink($fName)) {
                                $nok++;
                            }
                        } catch (Exception $e) {
                            ALogger::error($e->get_message());
                            $nerrors++;
                        }
                    } else {
                        //	ALogger::error('file not found');				$nerrors++;
                        $nok++;
                    }
                } else {
                    $nok++;
                }
                ALogger::end_action();
            }
        }

        if (($nerrors + $nok) > 0) {
            ALogger::log('Deleted: ' . $nok . ' samples');
            ALogger::log('Errors : ' . $nerrors . ' samples');
            ALogger::empty_line();
        }
    }

    //returns the searching data provider
    public static function searchDataProvider()
    {
        $model = new SampleDetected('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SampleDetected'])) {
            $model->attributes = $_GET['SampleDetected'];
        }

        return $model->search();
    }

}
