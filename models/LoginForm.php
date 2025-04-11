<?php
namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;
    
    private $_user = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Custom password validation
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
    
            if (!$user) {
                Yii::error('User not found: ' . $this->username, 'login'); // Log when user is not found
            }
    
            if ($user && !$user->validatePassword($this->password)) {
                Yii::error('Password mismatch for username: ' . $this->username, 'login'); // Log when password doesn't match
            }
    
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }
    

    /**
     * Retrieves user by username
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}