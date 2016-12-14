<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the model class for table "internal_users_uin".
 *
 * The followings are the available columns in table 'internal_users_uin':
 * @property string $id_uin
 * @property string $fname_uin
 * @property string $lname_uin
 * @property string $email_uin
 * @property string $enabled_uin
 * @property string $password_uin
 * @property string $register_date_uin
 * @property string $register_by_uin
 * @property string $last_login_date_uin
 * @property string $notification_pgp_error_uin
 * @property string $notification_undetected_samples_uin
 * @property string $notification_new_account_request_uin
 */
class InternalUser extends CActiveRecord
{

    /**
     * Returns the static model of the specified AR class.
     * @return InternalUser the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    const ENABLED_ENABLED = 1;
    const ENABLED_DISABLED = 0;

    public $new_password;
    public $old_password;
    public $confirm_new_password;
    public $YesNo = array('Yes', 'No');
    public $EnabledDisabled = array('Enabled', 'Disabled');
    public $requestedNewPassword = 0;
    public $oldPasswordHash;

    public static function passwordHash($password)
    {
        return md5(VIREX_PASSWORD_SALT . $password);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'internal_users_uin';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('fname_uin, lname_uin, email_uin', 'required'),
            array('fname_uin, lname_uin, email_uin', 'length', 'max' => 50),
            array('enabled_uin, notification_pgp_error_uin, notification_undetected_samples_uin, notification_new_account_request_uin', 'length', 'max' => 1),
            array('new_password', 'length', 'min' => 5, 'on' => 'update'),
            array('new_password, confirm_new_password, old_password', 'safe', 'on' => 'update'),
            array('confirm_new_password', 'compare', 'compareAttribute' => 'new_password', 'on' => 'update'),
            array('email_uin', 'email', 'on' => 'register'),
            array('email_uin', 'unique', 'on' => 'register'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id_uin, fname_uin, lname_uin, email_uin, enabled_uin, password_uin, register_date_uin, register_by_uin, last_login_date_uin, notification_pgp_error_uin, notification_undetected_samples_uin, notification_new_account_request_uin', 'safe', 'on' => 'search'),
        );
    }

    //Method used as trigger before saving the user
    public function beforeSave()
    {
        if ($this->isNewRecord) {
            $this->register_by_uin = Yii::app()->user->userId;
            $this->new_password = substr(md5(uniqid(rand(), true)), 0, 8);
            $this->password_uin = self::passwordHash($this->new_password);
            $this->register_date_uin = date('Y-m-d H:i:s');
            return true;
        }
        if ($this->requestedNewPassword == 1) {
            $e_flag = 0;
            if ($this->password_uin != self::passwordHash($this->old_password)) {
                $this->addError('old_password', 'Old password is wrong!');
                $e_flag = 1;
            }
            if ($this->new_password == '') {
                $this->addError('new_password', 'Enter the new password!');
                $e_flag = 1;
            }
            if ($this->confirm_new_password == '') {
                $this->addError('confirm_new_password', 'Enter the new password confirmation!');
                $e_flag = 1;
            }
            if ($e_flag)
                return false;
            $this->password_uin = self::passwordHash($this->new_password);
        }
        return true;
    }

    //Method used as trigger after saving an internal user
    public function afterSave()
    {
        if ($this->isNewRecord) {
            $url = Yii::app()->getBaseUrl(true);
            $body = "Dear {$this->fname_uin},

A new VIREX account has been created for you!
            
Please find bellow your login details:
Username: {$this->email_uin}
Password: {$this->new_password}

You can login here: {$url}

You can change this password by accesing 'My Profile' from the main menu.

--
Best Regards,
Virex Team";
            if (@mail($this->email_uin, '[Virex] New Account', $body)) {
                Yii::app()->user->setFlash('_success', "Account created! A generated password has been sent to " . $this->email_uin . " !");
            } else {
                Yii::app()->user->setFlash('_warning', 'Cannot send email to new user!');
            }
        }
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'admin' => array(self::BELONGS_TO, 'InternalUser', 'register_by_uin')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id_uin' => 'Id',
            'fname_uin' => 'First Name',
            'lname_uin' => 'Last Name',
            'email_uin' => 'Email',
            'enabled_uin' => 'Enabled',
            'register_date_uin' => 'Register Date',
            'password_uin' => 'Password',
            'password_confirm_uin' => 'Confirm Password',
            'register_by_uin' => 'Register By',
            'last_login_date_uin' => 'Last Login',
            'notification_pgp_error_uin' => 'Errors',
            'notification_undetected_samples_uin' => 'Undetected Samples',
            'notification_new_account_request_uin' => 'New Account Request',
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
        $criteria->compare('id_uin', $this->id_uin, true);
        $criteria->compare('fname_uin', $this->fname_uin, true);
        $criteria->compare('lname_uin', $this->lname_uin, true);
        $criteria->compare('email_uin', $this->email_uin, true);
        $criteria->compare('enabled_uin', $this->enabled_uin, true);
        $criteria->compare('password_uin', $this->password_uin, true);
        $criteria->compare('register_date_uin', $this->register_date_uin, true);
        $criteria->compare('register_by_uin', $this->register_by_uin, true);
        $criteria->compare('last_login_date_uin', $this->last_login_date_uin, true);
        $criteria->compare('notification_pgp_error_uin', $this->notification_pgp_error_uin, true);
        $criteria->compare('notification_undetected_samples_uin', $this->notification_undetected_samples_uin, true);
        $criteria->compare('notification_new_account_request_uin', $this->notification_new_account_request_uin, true);

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
        ));
    }

}
