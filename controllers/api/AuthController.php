<?php

/**
 * AuthController for API
 * File: controllers/api/AuthController.php
 */

 namespace app\controllers\api;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\SignupForm;
use app\models\PasswordResetForm;
use app\services\AuthService;
use app\behaviors\JwtAuthBehavior;
use app\modules\api\components\ApiResponse;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\Response;

class AuthController extends Controller
{
    private $authService;
    
    public function __construct($id, $module, AuthService $authService, $config = [])
    {
        $this->authService = $authService;
        parent::__construct($id, $module, $config);
    }
    
    public function behaviors()
    {
        return [
            'jwtAuth' => [
                'class' => JwtAuthBehavior::class,
                'except' => ['login', 'signup', 'request-password-reset', 'reset-password', 'verify-email', 'refresh-token'],
            ],
        ];
    }
    
    /**
     * Login action
     * 
     * @return array
     */
    public function actionLogin()
    {
        $model = new LoginForm();
    
        $data = Yii::$app->request->getBodyParams();
    
        Yii::info('Request data: ' . json_encode($data), 'login');
    
        if ($model->load($data, '') && $model->validate()) {
            // Proceed with login
            $result = $this->authService->login($model);
            
            if ($result) {
                Yii::$app->response->statusCode = 200;
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::success($result, 'Login successful');
            }
        } else {
            // Log validation errors
            Yii::error('Validation errors: ' . json_encode($model->errors), 'login');
    
            Yii::$app->response->statusCode = 400;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ApiResponse::error('Invalid login credentials', $model->errors);
        }
    }
    
    
    /**
     * Signup action
     * 
     * @return array
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        
        $data = Yii::$app->request->getBodyParams();
        
        if ($model->load($data, '') && $model->validate()) {
            // Proceed with signup logic
            $user = $this->authService->signup($model);
            
            if ($user) {
                Yii::$app->response->statusCode = 201;
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::success([
                    'username' => $user->username,
                    'email' => $user->email,
                ], 'Registration successful. Please check your email to verify your account.');
            }
        }
        
        Yii::$app->response->statusCode = 400;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::error('Registration failed', $model->errors);
    }
    
    /**
     * Verify email action
     * 
     * @param string $token
     * @return array
     */
    public function actionVerifyEmail($token)
    {
        $user = $this->authService->verifyEmail($token);
        
        if ($user) {
            Yii::$app->response->statusCode = 200;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ApiResponse::success(null, 'Email verification successful. You can now login.');
        }
        
        Yii::$app->response->statusCode = 400;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::error('Invalid or expired verification token', null);
    }
    
    /**
     * Request password reset action
     * 
     * @return array
     */
    public function actionRequestPasswordReset()
    {
        $email = Yii::$app->request->post('email');
        
        if (!$email) {
            Yii::$app->response->statusCode = 400;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ApiResponse::error('Email is required');
        }
        
        if ($this->authService->sendPasswordResetLink($email)) {
            Yii::$app->response->statusCode = 200;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ApiResponse::success(null, 'Password reset link has been sent to your email');
        }
        
        Yii::$app->response->statusCode = 400;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::error('Failed to send password reset link. Please check your email address.');
    }
    
    /**
     * Reset password action
     * 
     * @return array
     */
    public function actionResetPassword()
    {
        $model = new PasswordResetForm();
        
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            if ($this->authService->resetPassword($model)) {
                Yii::$app->response->statusCode = 200;
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::success(null, 'Password has been reset successfully');
            }
        }
        
        Yii::$app->response->statusCode = 400;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::error('Failed to reset password', $model->errors);
    }
    
    /**
     * Logout action
     * 
     * @return array
     */
    public function actionLogout()
    {
        // The client should discard the token
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::success(null, 'Logged out successfully');
    }
    
    /**
     * Refresh token action
     * 
     * @return array
     */
    public function actionRefreshToken()
    {
        $refreshToken = Yii::$app->request->post('refresh_token');
        
        if (!$refreshToken) {
            Yii::$app->response->statusCode = 400;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ApiResponse::error('Refresh token is required', null);
        }
        
        $result = $this->authService->refreshToken($refreshToken);
        
        if ($result) {
            Yii::$app->response->statusCode = 200;
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ApiResponse::success($result, 'Token refreshed successfully');
        }
        
        Yii::$app->response->statusCode = 401;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::error('Invalid or expired refresh token', null);
    }
    
    /**
     * Get current user info action
     * 
     * @return array
     */
    public function actionMe()
    {
        $user = Yii::$app->user->identity;
        
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ApiResponse::success([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ]);
    }
}