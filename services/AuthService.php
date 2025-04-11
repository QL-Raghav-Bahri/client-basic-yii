<?php
/**
 * The Authentication Service
 * File: services/AuthService.php
 */

namespace app\services;

use Yii;
use app\models\User;
use app\models\LoginForm;
use app\models\SignupForm;
use app\models\PasswordResetForm;
use app\components\JwtHelper;
use yii\base\Exception;

class AuthService
{
    private $jwtHelper;
    
    public function __construct()
    {
        $this->jwtHelper = new JwtHelper();
    }
    
    /**
     * Login user and return access token
     * 
     * @param LoginForm $form
     * @return array|null
     */
    public function login(LoginForm $form)
    {
        if (!$form->validate()) {
            return null;
        }
        
        $user = User::findByUsername($form->username);
        
        if (!$user) {
            return null;
        }
        
        return $this->generateTokens($user);
    }
    
    /**
     * Register new user
     * 
     * @param SignupForm $form
     * @return User|null
     */
    public function signup(SignupForm $form)
    {
        if (!$form->validate()) {
            return null;
        }
        
        $user = new User();
        $user->username = $form->username;
        $user->email = $form->email;
        $user->setPassword($form->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        $user->status = User::STATUS_INACTIVE;
        
        if (!$user->save()) {
            return null;
        }
        
        // Send verification email
        $this->sendVerificationEmail($user);
        
        return $user;
    }
    
    /**
     * Verify email
     * 
     * @param string $token
     * @return User|null
     */
    public function verifyEmail($token)
    {
        $user = User::findByVerificationToken($token);
        
        if (!$user) {
            return null;
        }
        
        $user->status = User::STATUS_ACTIVE;
        $user->verification_token = null;
        
        if (!$user->save(false)) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Sends password reset link
     * 
     * @param string $email
     * @return bool
     */
    public function sendPasswordResetLink($email)
    {
        $user = User::findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        $user->generatePasswordResetToken();
        
        if (!$user->save(false)) {
            return false;
        }
        
        // Send password reset email
        return $this->sendPasswordResetEmail($user);
    }
    
    /**
     * Reset password
     * 
     * @param PasswordResetForm $form
     * @return bool
     */
    public function resetPassword(PasswordResetForm $form)
    {
        if (!$form->validate()) {
            return false;
        }
        
        $user = User::findByPasswordResetToken($form->token);
        
        if (!$user) {
            return false;
        }
        
        $user->setPassword($form->password);
        $user->removePasswordResetToken();
        
        return $user->save(false);
    }
    
    /**
     * Generate access and refresh tokens for user
     * 
     * @param User $user
     * @return array
     */
    public function generateTokens(User $user)
    {
        $accessToken = $this->jwtHelper->generateToken([
            'user_id' => $user->id,
            'username' => $user->username,
            'type' => 'access',
        ], Yii::$app->params['jwt.accessTokenExpire']);
        
        $refreshToken = $this->jwtHelper->generateToken([
            'user_id' => $user->id,
            'type' => 'refresh',
        ], Yii::$app->params['jwt.refreshTokenExpire']);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => Yii::$app->params['jwt.accessTokenExpire'],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ];
    }
    
    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken
     * @return array|null
     */
    public function refreshToken($refreshToken)
    {
        $payload = $this->jwtHelper->validateToken($refreshToken);
        
        if (!$payload || $payload['type'] !== 'refresh') {
            return null;
        }
        
        $user = User::findIdentity($payload['user_id']);
        
        if (!$user) {
            return null;
        }
        
        return $this->generateTokens($user);
    }
    
    /**
     * Send verification email
     * 
     * @param User $user
     * @return bool
     */
    private function sendVerificationEmail(User $user)
    {
        return Yii::$app->mailer->compose('verifyEmail', ['user' => $user])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($user->email)
            ->setSubject('Account verification for ' . Yii::$app->name)
            ->send();
    }
    
    /**
     * Send password reset email
     * 
     * @param User $user
     * @return bool
     */
    private function sendPasswordResetEmail(User $user)
    {
        return Yii::$app->mailer->compose('passwordReset', ['user' => $user])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($user->email)
            ->setSubject('Password reset for ' . Yii::$app->name)
            ->send();
    }
}
