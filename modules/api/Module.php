<?php
/**
 * API Module
 * File: modules/api/Module.php
 */

 namespace app\modules\api;

 use yii\base\Module as BaseModule;
 
 class Module extends BaseModule
 {
     public $controllerNamespace = 'app\modules\api\controllers';
 
     public function init()
     {
         parent::init();
         
         // Configure module settings
         \Yii::$app->user->enableSession = false;
         \Yii::$app->user->loginUrl = null;
         \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
     }
 }