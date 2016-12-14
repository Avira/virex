<?php

/**
 * 
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * ResetpasswordForm class.
 * ResetpasswordForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'SiteController'.
 */
class ResetpasswordForm extends CFormModel
{

    public $username;

    const SALT = '2chrfdrfxs8se23';

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return array(
            // username and password are required
            array('username', 'required'),
            array('username', 'exists')
        );
    }

    public function exists($attribute, $params)
    {
        $external = true;
        $user = ExternalUser::model()->findByAttributes(array('name_usr' => $this->username));
        if ($user === NULL) {
            // check internal
            $user = InternalUser::model()->findByAttributes(array('email_uin' => $this->username));
            if ($user === NULL) {
                $this->addError('username', 'Login invalid!');
                // no user found
                return false;
            }
            $external = false;
        }
        if (!$user) {
            $this->addError('username', 'Login invalid!');
        }
        return true;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array(
            'username' => 'Login',
        );
    }

    /**
     * Authenticates the password.
     * This is the 'authenticate' validator as declared in rules().
     */
    public function reset()
    {
        $external = true;
        $user = ExternalUser::model()->findByAttributes(array('name_usr' => $this->username));
        if ($user === NULL) {
            // check internal
            $user = InternalUser::model()->findByAttributes(array('email_uin' => $this->username));
            if ($user === NULL) {
                // no user found
            }
            $external = false;
        }
        if ($external) {
            $user->email_code_usr = md5(date('Y-m-d H:i:s') . self::SALT);
            $user->save();
            ExternalUserHistory::addLog('Requested password reset!', $user->id_usr);
            $md5 = $user->id_usr . 'e;' . $user->email_code_usr;
            $name = $user->name_usr;
            $email = $user->email_usr;
        } else {
            $md5 = $user->id_uin . 'i;' . md5($user->fname_uin . $user->password_uin);
            $name = $user->fname_uin;
            $email = $user->email_uin;
        }
        ResetpasswordForm::send_first_email($md5, $name, $email, $external);
    }

    public static function send_first_email($md5, $name, $email, $external = false)
    {
        $endMessage = VIREX_MAIL_SIGNATURE;
        $emailCheckUrl = Yii::app()->createAbsoluteUrl('site/resetpassword/', array('code' => $md5));
        $message = <<<MESSAGE
Dear {$name},

We have received a request to reset your password.
Please confirm this request by using the link below:
$emailCheckUrl

$endMessage
MESSAGE;
        $subject = '[Virex] Confirm password reset';
        @mail($email, $subject, $message);
    }

    public static function send_second_email($name, $password, $email, $external = false)
    {
        $endMessage = VIREX_MAIL_SIGNATURE;
        $baseUrl = Yii::app()->getBaseUrl(true);
        $message = <<<MESSAGE
Dear {$name},

We have received a request to reset your password.
Your new password is: $password
You can login here: $baseUrl

$endMessage
MESSAGE;
        $subject = '[Virex] Your new password';
        @mail($email, $subject, $message);
    }

}
