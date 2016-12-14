<?php

/**
 * @copyright Copyright (c) 2016, Avira Operations GmbH & Co. KG ~ http://www.avira.com/
 * @author Avira <virex@avira.com>
 * 
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{

    /**
     * Authenticates a user.
     * The example implementation makes sure if the username and password
     * are both 'demo'.
     * In practical applications, this should be changed to authenticate
     * against some persistent user identity storage (e.g. database).
     * @return boolean whether authentication succeeds.
     */
    const ERROR_ENABLED_NEW = 114;   // email is not confirmed yet
    const ERROR_ENABLED_EMAIL = 115;   // email is confirmed but profile not activated by admin
    const ERROR_ENABLED_DISABLED = 117;  // profile is disabled

    //The main authentication method

    public function authenticate()
    {
        // check external
        $external = true;
        $user = ExternalUser::model()->findByAttributes(array('name_usr' => $this->username));
        if ($user === NULL) {
            // check internal
            $user = InternalUser::model()->findByAttributes(array('email_uin' => $this->username));
            if ($user === NULL) {
                // no user found
                $this->errorCode = self::ERROR_USERNAME_INVALID;
            }
            $external = false;
        }
        if ($user) {
            if ($external) {
                if ($user->password_usr == ExternalUser::passwordHash($this->password)) {
                    // password ok
                    if ($user->status_usr == ExternalUser::ENABLED_ENABLED) {
                        // account enabled
                        $this->errorCode = self::ERROR_NONE;
                        $this->setState('type', 'External');
                        $this->setState('userId', $user->id_usr);
                        $this->setState('name', $user->name_usr);
                        $this->setState('email', $user->email_usr);
                        $this->setState('limitation_date', $user->limitation_date_usr);
                        $this->setState('rights_daily', $user->rights_daily_usr);
                        $this->setState('rights_monthly', $user->rights_monthly_usr);
                        $this->setState('rights_clean', $user->rights_clean_usr);
                        $user->last_login_date_usr = date('Y-m-d H:i:s');
                        $user->ip_usr = $_SERVER['REMOTE_ADDR'];
                        $user->save(false);
                    } else {
                        $this->errorCode = 114 + $user->status_usr;
                        if ($user->status_usr == 0) {
                            $_POST['show_resend_activation'] = true;
                        }
                    }
                } else {
                    $this->errorCode = self::ERROR_PASSWORD_INVALID;
                }
            } else {
                if ($user->password_uin == InternalUser::passwordHash($this->password)) {
                    // password ok
                    if ($user->enabled_uin == InternalUser::ENABLED_ENABLED) {
                        // account enabled
                        $this->errorCode = self::ERROR_NONE;
                        $this->setState('type', 'Internal');
                        $this->setState('userId', $user->id_uin);
                        $this->setState('name', $user->fname_uin . ' ' . $user->lname_uin);
                        $this->setState('email', $user->email_uin);
                        $user->last_login_date_uin = date('Y-m-d H:i:s');
                        $user->save(false);
                    } else {
                        $this->errorCode = self::ERROR_ENABLED_DISABLED;
                    }
                } else {
                    $this->errorCode = self::ERROR_PASSWORD_INVALID;
                }
            }
        }
        return !$this->errorCode;
    }

    //Method used to check if the authenticated user has the specified rights
    public static function check($rights)
    {
        if ('*' == $rights) {
            return true;
        }
        if (Yii::app()->user->isGuest) {
            return false;
        }
        if ('@' == $rights) {
            return true;
        }

        $rights = explode(',', $rights);
        foreach ($rights as &$right) {
            $right = trim($right);
        }

        return in_array(Yii::app()->user->type, $rights);
    }

}
