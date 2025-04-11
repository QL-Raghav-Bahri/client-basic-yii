<?php

/**
 * API Response Component
 * File: modules/api/components/ApiResponse.php
 */

namespace app\modules\api\components;

use yii\base\Component;

class ApiResponse extends Component
{
    /**
     * Format success response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        \Yii::$app->response->statusCode = $statusCode;
        
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];
    }
    
    /**
     * Format error response
     * 
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return array
     */
    public static function error($message = 'Error', $errors = null, $statusCode = 400)
    {
        \Yii::$app->response->statusCode = $statusCode;
        
        return [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ];
    }
}