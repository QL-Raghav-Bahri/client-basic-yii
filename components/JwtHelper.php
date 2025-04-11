<?php
/**
 * The JWT Helper Component
 * File: components/JwtHelper.php
 */

namespace app\components;

use Yii;
use yii\base\Component;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JwtHelper extends Component
{
    /**
     * Generate JWT token
     * 
     * @param array $payload
     * @param int $expiresIn
     * @return string
     */
    public function generateToken($payload, $expiresIn)
    {
        $now = time();
        
        $jwtPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'iss' => Yii::$app->params['jwt.issuer'],
        ]);
        
        return JWT::encode($jwtPayload, Yii::$app->params['jwt.secret'], 'HS256');
    }
    
    /**
     * Validate JWT token
     * 
     * @param string $token
     * @return array|null
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(Yii::$app->params['jwt.secret'], 'HS256'));
            
            // Convert object to array
            return json_decode(json_encode($decoded), true);
        } catch (\Exception $e) {
            return null;
        }
    }
}