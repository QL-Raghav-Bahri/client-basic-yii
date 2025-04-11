<?php
/**
 * The JWT Auth Behavior
 * File: behaviors/JwtAuthBehavior.php
 */

namespace app\behaviors;

use yii\base\Behavior;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use app\components\JwtHelper;
use app\models\User;

class JwtAuthBehavior extends Behavior
{
    public $except = [];
    private $jwtHelper;
    
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->jwtHelper = new JwtHelper();
    }
    
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }
    
    public function beforeAction($event)
    {
        $action = $event->action->id;
        
        if (in_array($action, $this->except)) {
            return true;
        }
        
        $headers = \Yii::$app->request->headers;
        
        if (!$headers->has('Authorization')) {
            throw new UnauthorizedHttpException('Authorization header not found');
        }
        
        $authHeader = $headers->get('Authorization');
        
        if (!preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            throw new UnauthorizedHttpException('Invalid Authorization header format');
        }
        
        $token = $matches[1];
        $payload = $this->jwtHelper->validateToken($token);
        
        if (!$payload) {
            throw new UnauthorizedHttpException('Invalid or expired token');
        }
        
        if (!isset($payload['user_id']) || $payload['type'] !== 'access') {
            throw new UnauthorizedHttpException('Invalid token type');
        }
        
        $user = User::findIdentity($payload['user_id']);
        
        if (!$user) {
            throw new UnauthorizedHttpException('User not found');
        }
        
        \Yii::$app->user->login($user, 0);
        
        return true;
    }
}