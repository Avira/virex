<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * This is the model class for table "samples_clean_scl".
 *
 * The followings are the available columns in table 'samples_clean_scl':
 * @property string $id_scl
 * @property string $md5_scl
 * @property string $sha256_scl
 * @property string $add_date_scl
 * @property string $size_scl
 */
class SampleClean extends CActiveRecord
{

    /**
     * Returns the static model of the specified AR class.
     * @return SampleClean the static model class
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
        return 'samples_clean_scl';
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

    public function afterFind()
    {
        $this->hex = $this->ascii2hex($this->md5_scl);
		if(isset($this->sha256_scl)){
			$this->hexSha256 = $this->ascii2hex($this->sha256_scl);
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
            array('id_scl, md5_scl, sha256_scl, added_when_scl, file_size_scl, type_scl, enabled_scl, pending_action_scl', 'safe', 'on' => 'search'),
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
            'id_scl' => 'Id',
            'md5_scl' => 'Md5',
            'sha256_scl' => 'Sha256',
            'added_when_scl' => 'Date added',
            'file_size_scl' => 'Size',
            'type_scl' => 'Type',
            'enabled_scl' => 'Status',
            'pending_action_scl' => 'Pending action'
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

        $criteria->compare('id_scl', $this->id_scl);
        $criteria->compare('md5_scl', $this->md5_scl);
		$criteria->compare('sha256_scl', $this->sha256_scl);	
        $criteria->compare('added_when_scl', $this->added_when_scl);
        $criteria->compare('type_scl', $this->type_scl);
        $criteria->compare('enabled_scl', $this->enabled_scl);
        $criteria->compare('pending_action_scl', $this->pending_action_scl);
        if (isset($_SESSION['interval_start'])) {
            $criteria->addBetweenCondition('added_when_scl', $_SESSION['interval_start'], $_SESSION['interval_end']);
        }
        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => VIREX_PAGE_SIZE
            ),
            'sort' => array('defaultOrder' => 'id_scl DESC')
        ));
    }

    //Method used to delete files by special condition
    public function deleteFiles($where, $values = array())
    {
        $command = Yii::app()->db->createCommand("SELECT hex(md5_scl) 'hex', md5_scl, id_scl FROM samples_clean_scl WHERE " . $where . " LIMIT 0,5000");
        foreach ($values as $k => $v) {
            $command->bindValue($k, $v);
        }
        $nerrors = 0; // init stats
        $nok = 0;
        $files = $command->queryAll();
        ALogger::log('Found ' . count($files) . ' samples to delete.');
        $stillExists = Yii::app()->db->createCommand("SELECT count(*) 'n' FROM samples_clean_scl WHERE md5_scl=:md5");
        $deleteId = Yii::app()->db->createCommand("DELETE FROM samples_clean_scl WHERE id_scl=:id");
        foreach ($files as $f) {
            ALogger::start_action('deleting ' . $f['md5_scl'] . '..');
            $stillExists->bindValue(':md5', $f['md5_scl']);
            $deleteId->execute(array('id' => $f['id_scl']));
            $exists = $stillExists->queryRow();
            if (!$exists['n']) { // I delete it only if is not in db anymore( it can be deleted only from monthly and still be in daily for example..)
                $fName = PathFinder::get(VIREX_STORAGE_PATH, 'clean', '') . substr($f['hex'], 0, 3) . '/' . substr($f['hex'], 3, 3) . '/' . substr($f['hex'], 6, 3) . '/' . $f['hex'];
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
                    //	ALogger::error('file not found'); $nerrors++;
                    $nok++;
                }
            } else {
                $nok++;
            }
            ALogger::end_action();
        }
        if (($nerrors + $nok) > 0) {
            ALogger::log('Deleted: ' . $nok . ' samples');
            ALogger::log('Errors : ' . $nerrors . ' samples');
            ALogger::empty_line();
        }
    }

    public $errors_count = 0;

    //returns the searching data provider
    public static function searchDataProvider()
    {
        $model = new SampleClean('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SampleClean'])) {
            $model->attributes = $_GET['SampleClean'];
        }

        return $model->search();
    }

}
