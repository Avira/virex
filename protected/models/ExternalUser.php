<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * This is the model class for table "external_users_usr".
 *
 * The followings are the available columns in table 'external_users_usr':
 * @property string $id_usr
 * @property string $name_usr
 * @property string $company_usr
 * @property string $email_usr
 * @property string $password_usr
 * @property string $public_pgp_key_usr
 * @property string $email_code_usr
 * @property string $status_usr
 * @property string $rights_daily_usr
 * @property string $rights_monthly_usr
 * @property string $rights_clean_usr
 * @property string $register_date_usr
 * @property string $last_login_date_usr
 */
class ExternalUser extends CActiveRecord
{

    /**
     * Returns the static model of the specified AR class.
     * @return ExternalUser the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    const ENABLED_NEW = 0;
    const ENABLED_EMAIL = 1;
    const ENABLED_ENABLED = 2;
    const ENABLED_DISABLED = 3;

    public $confirm_password;
    public $verifyCode;
    public $userStatus;
    public $new_password;
    public $confirm_new_password;
    public $old_password;

    // hashing password
    public static function passwordHash($password)
    {
        return (md5(VIREX_PASSWORD_SALT . $password));
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'external_users_usr';
    }

    public function findCompanies()
    {
        return Yii::app()->db->createCommand("SELECT DISTINCT company_usr FROM external_users_usr")->queryAll();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name_usr, company_usr, email_usr, password_usr, verifyCode, confirm_password, public_pgp_key_usr', 'required', 'on' => 'register'),
            array('name_usr', 'length', 'max' => 60),
            array('name_usr', 'ext.alpha', 'allowNumbers' => true, 'extra' => array('-', '_')),
            array('password_usr, new_password', 'length', 'min' => 5),
            array('userStatus', 'safe', 'on' => 'search'),
            array('verifyCode', 'captcha', 'allowEmpty' => !Yii::app()->user->isGuest, 'on' => 'register'),
            array('confirm_password', 'compare', 'compareAttribute' => 'password_usr', 'on' => 'register'),
            array('confirm_new_password', 'compare', 'compareAttribute' => 'new_password', 'on' => 'update'),
            array('confirm_new_password', 'compare', 'compareAttribute' => 'new_password', 'on' => 'edit_profile'),
            array('new_password,public_pgp_key_usr', 'safe', 'on' => 'update'),
            array('limitation_date_usr, rights_daily_usr, rights_monthly_usr, rights_clean_usr, rights_url_usr', 'safe', 'on' => 'update'),
            array('company_usr, email_usr', 'length', 'max' => 80),
            array('public_pgp_key_usr', 'check_pgp_key'),
            array('email_usr', 'email', 'on' => 'register'),
            array('name_usr', 'unique', 'on' => 'register'),
            array('email_usr', 'unique', 'on' => 'register'),
            array('old_password, new_password, confirm_new_password', 'safe', 'on' => 'edit_profile'),
            array('name_usr, company_usr, email_usr, public_pgp_key_usr', 'required', 'on' => 'edit_profile'),
            array('old_password', 'check_old_pass', 'on' => 'edit_profile')
        );
    }

    //Method used to check the old password matching
    public function check_old_pass()
    {
        if ($this->new_password == '') {
            return true;
        }
        if ($this->passwordHash($this->old_password) != $this->password_usr) {
            $this->addError('old_password', 'Old password is incorrect!');
        }
        return true;
    }

    //Method used as trigger after an external user is deleted
    public function afterDelete()
    {
        parent::afterDelete();
        $id = $this->id_usr;
        // delete history
        Yii::app()->db->createCommand("DELETE FROM user_lists_usl WHERE idusr_usl=$id")->execute();
        Yii::app()->db->createCommand("DELETE FROM user_files_usf WHERE idusr_usf=$id")->execute();
        Yii::app()->db->createCommand("DELETE FROM permanent_statistics_user_psu WHERE idusr_psu=$id")->execute();
        Yii::app()->db->createCommand("DELETE FROM external_users_history_euh WHERE idusr_euh=$id")->execute();
    }

    //Method used as trigger before saving an external user
    public function beforeSave()
    {
        if ($this->isNewRecord) {
            $this->password_usr = $this->passwordHash($this->password_usr);
            $this->status_usr = 0;
            $this->register_date_usr = date('Y-m-d H:i:s');
            $this->limitation_date_usr = date('Y-m-d H:i:s', strtotime('- 1 day'));
            $this->email_code_usr = md5($this->passwordHash(date('Y-m-d H:i:s')));
        } elseif ($this->new_password) {
            $this->password_usr = $this->passwordHash($this->new_password);
            Yii::app()->user->setFlash('_success', "Your password has been changed!");
        }
        return true;
    }

    //Method used to check a PGP Key
    public function check_pgp_key($attribute, $params)
    {
        $response = 1;
        $fisier = VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . md5(rand(5000, 9999) . date('His'));
        $f = fopen($fisier, 'w');
        fwrite($f, $this->public_pgp_key_usr);
        fclose($f);
        if (file_exists($fisier)) {
            $response = $this->exec_gpg('--import ' . $fisier);
        } else {
            $this->addError('public_pgp_key_usr', 'Error! Invalid pgp key [F]!');
            return false;
        }
        unlink($fisier);
        if (preg_match('/gpg:.*?([A-F0-9]{8}):.*?"(.*?)"/im', $response, $matches)) {
            $this->pgp_key_name_usr = $matches[2];
            return true;
        } else {
            $this->addError('public_pgp_key_usr', 'Invalid pgp key [E]! ' . $response);
            return false;
        }
    }

    //Method used to run a custom GPG command
    function exec_gpg($command)
    {
        $tempd = PathFinder::ensure(VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . 'gpg');
        $gpghome = PathFinder::ensure(VIREX_TEMP_PATH . DIRECTORY_SEPARATOR . 'gnupg');
        $file = uniqid('file_');
        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("file", "{$tempd}/{$file}.txt", "w") // stderr is a file to write to
        );
//        $process = proc_open('gpg --always-trust --no-secmem-warning ' . $command, $descriptorspec, $pipes, $tempd);
        $process = proc_open('gpg --always-trust --no-secmem-warning --no-tty --homedir=' . $gpghome . ' ' . $command, $descriptorspec, $pipes, $tempd);

        if (is_resource($process)) {
            proc_close($process);
        }
        $response = file_get_contents("{$tempd}/{$file}.txt");
        unlink("{$tempd}/{$file}.txt");
        return $response;
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
            'id_usr' => 'Id',
            'name_usr' => 'Username',
            'company_usr' => 'Company',
            'email_usr' => 'Email',
            'password_usr' => 'Password',
            'confirm_password' => 'Confirm password',
            'public_pgp_key_usr' => 'Public PGP Key',
            'status_usr' => 'Status',
            'rights_daily_usr' => 'Rights Daily',
            'rights_monthly_usr' => 'Rights Monthly',
            'rights_clean_usr' => 'Rights Clean',
            'rights_url_usr' => 'Rights URLs',
            'register_date_usr' => 'Register Date',
            'limitation_date_usr' => 'Limitation date',
            'last_login_date_usr' => 'Last Login',
            'verifyCode' => 'Verification Code'
        );
    }

    //Method used as trigger after saving an external user
    public function afterSave()
    {
        if ($this->isNewRecord) {
            // confirmation email
            $this->sendActivationLink();
        } else {
            // notification email(account enabled or disabled)
        }
    }

    //Method used to send the user's activation link by email
    public function sendActivationLink()
    {
        $baseUrl = Yii::app()->getBaseUrl(true);
        $emailCheckUrl = Yii::app()->createAbsoluteUrl('site/check_email/', array('c1' => $this->id_usr, 'c2' => $this->email_code_usr));
        $endMessage = VIREX_MAIL_SIGNATURE;
        $message = <<<MESSAGE
Dear {$this->name_usr},

You have requested a new account on Virex.

To verify your email address, please click the link below.
$emailCheckUrl

$endMessage
MESSAGE;
        $subject = '[Virex] Please confirm your email address';
        @mail($this->email_usr, $subject, $message);
        return $this;
    }

    //Method used as trigger after searching
    public function afterFind()
    {
        $stats = array(
            0 => 'New',
            1 => 'Pending',
            2 => 'Enabled',
            3 => 'Disabled'
        );
        $this->userStatus = $stats[$this->status_usr];
    }

    //returns the possible status list
    public static function userStatusList()
    {
        return array(
            array('name' => 'New'),
            array('name' => 'Pending'),
            array('name' => 'Enabled'),
            array('name' => 'Disabled')
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
        $revertStats = array(
            'New' => 0,
            'Pending' => 1,
            'Enabled' => 2,
            'Disabled' => 3
        );

        $criteria = new CDbCriteria;

        $criteria->compare('id_usr', $this->id_usr, true);
        $criteria->compare('name_usr', $this->name_usr, true);
        $criteria->compare('company_usr', $this->company_usr, true);
        if ($this->userStatus) {
            $criteria->compare('status_usr', $revertStats[$this->userStatus], true);
        }
        $criteria->compare('email_usr', $this->email_usr, true);
        $criteria->compare('password_usr', $this->password_usr, true);
        $criteria->compare('public_pgp_key_usr', $this->public_pgp_key_usr, true);
        $criteria->compare('email_code_usr', $this->email_code_usr, true);
        $criteria->compare('status_usr', $this->status_usr, true);
        $criteria->compare('rights_daily_usr', $this->rights_daily_usr, true);
        $criteria->compare('rights_monthly_usr', $this->rights_monthly_usr, true);
        $criteria->compare('rights_clean_usr', $this->rights_clean_usr, true);
        $criteria->compare('register_date_usr', $this->register_date_usr, true);
        $criteria->compare('last_login_date_usr', $this->last_login_date_usr, true);

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => 20,
            )
        ));
    }

    //Method used to set the status of an user as email validated
    public function validate_email($id)
    {
        Yii::app()->db->createCommand("UPDATE external_users_usr SET status_usr=1 WHERE id_usr=" . (int) $id)->execute();
    }

    //Method used to send an email confirmation
    public static function send_ac_email($email_address, $id, $name, $company)
    {
        $endMessage = VIREX_MAIL_SIGNATURE;
        $message = <<<MESSAGE
Dear {$name},

You submission for a new Virex account has been successfully
registered. It's now pending review for an administrator.

You will receive an email when your account is confirmed.

$endMessage
MESSAGE;
        $subject = '[Virex] Account pending confirmation';
        @mail($email_address, $subject, $message);
        ///*****************************
        // email to administrator
        $admins = Yii::app()->db->createCommand()->select('email_uin, fname_uin, lname_uin')->from('internal_users_uin')->where('notification_new_account_request_uin=1 AND enabled_uin=1')->queryAll();
        if ($admins) {
            $endMessage = VIREX_MAIL_SIGNATURE;
            foreach ($admins as $a) {
                $requestPage = Yii::app()->createAbsoluteUrl('externaluser/admin');
                $requestUrl = Yii::app()->createAbsoluteUrl('externaluser/update', array('id' => $id));
                $message = <<<MESSAGE
Dear {$a['fname_uin']},

$name requested a new Sampleshare Account for $company.

To review this request click here:
$requestUrl

For all pending requests click here:
$requestPage

$endMessage
MESSAGE;
                $subject = '[Virex] New account requested by ' . $name;
                @mail($a['email_uin'], $subject, $message);
            }
        }
    }

    //Method used to send the confirmation email for the enabled users
    public static function send_enable_account_email($email_address, $name, $id)
    {
        $endMessage = VIREX_MAIL_SIGNATURE;
        $baseUrl = Yii::app()->getBaseUrl(true);
        $message = <<<MESSAGE
Dear $name,

Welcome to Virex!

Your account on {$baseUrl} was just enabled by an administrator.

You are now on your way to download samples from our sever via the web interface or by using the Norman Sampleshare Framework Client.

Should you need any help please have a look at the documentation here:
https://sampleshare.norman.com/signup/framework.php

$endMessage
MESSAGE;
        $subject = '[Virex] Welcome to Virex!';
        @mail($email_address, $subject, $message);
    }

    //Method used to return the user lists
    public function get_file_lists($userId = false)
    {
        if (!$userId) {
            $userId = $this->id_usr;
        }
        return Yii::app()->db->createCommand("SELECT * FROM user_lists_usl WHERE idusr_usl = $userId ORDER BY date_usl DESC LIMIT 0,25")->queryAll();
    }

    //Method used to return the number of downloaded files by an external user
    public function get_downloaded_files($number, $userId = false)
    {
        if (!$userId) {
            $userId = $this->id_usr;
        }
        $number = (int) $number;
        return Yii::app()->db->createCommand("SELECT user_files_usf.*, list_type_usl, sum(count_usf) 'count_usf' FROM user_files_usf
		    LEFT JOIN user_lists_usl ON id_usl = idusl_usf
		    WHERE idusr_usf=$userId AND count_usf > 0 GROUP BY DATE(date_usf) ORDER BY date_usf DESC LIMIT 0, $number ")->queryAll();
    }

    //Method used to return the details of an user files list
    public function list_details($listId)
    {
        return Yii::app()->db->createCommand("SELECT *, sum(count_usf) 'count_usf' FROM user_files_usf WHERE idusl_usf=$listId  GROUP BY md5_usf, DATE(date_usf) ORDER BY count_usf DESC, date_usf DESC")->queryAll();
    }

}
