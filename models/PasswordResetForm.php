<?php

/**
 * PasswordResetForm Model
 * File: models/PasswordResetForm.php
 */

namespace app\models;

use yii\base\Model;
use app\models\User;

class PasswordResetForm extends Model
{
    public $token;
    public $password;
    public $password_confirm;

    public function rules()
    {
        return [
            ['token', 'required'],
            ['token', 'validateToken'],
            
            ['password', 'required'],
            ['password', 'string', 'min' => 6],
            
            ['password_confirm', 'required'],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
        ];
    }
    
    public function validateToken($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = User::findByPasswordResetToken($this->token);
            
            if (!$user) {
                $this->addError($attribute, 'Invalid or expired password reset token.');
            }
        }
    }
}