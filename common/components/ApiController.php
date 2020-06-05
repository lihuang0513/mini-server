<?php
namespace common\components;

use yii\helpers\ArrayHelper;
use yii\rest\Controller;

use yii\base\InvalidRouteException;
use yii\web\HttpException;
use yii\web\Response;

class ApiController extends Controller
{

    public $actionType = 'json';

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CustomAuth::class,
                'tokenParam' => 'access_token'
            ],
        ]);

    }

    public function runAction($id, $params = [])
    {
        try {

            $result = parent::runAction($id, $params);
            if($this->actionType == 'json'){
                if(is_object($result) && $result instanceof \common\components\Response) {
                    return $this->asJson($result->asArray());
                }
                return $this->asJson($result);
            }else{
                return $result;
            }


        } catch (\Exception $e) {
            return $this->asJson($this->errorInfo($e));
        }
    }

    private function errorInfo(\Exception $e)
    {
        if ($e instanceof HttpException) {
            $result['code'] = $e->statusCode;
            if ($e->statusCode == 401) {
                \Yii::$app->response->statusCode = 401;
            }
        } elseif ($e instanceof InvalidRouteException) {
            \Yii::$app->response->statusCode = 404;
        } else {
            $result['code'] = $e->getCode();
        }
        if (!$result['code']) {
            $result['code'] = 10000;
        }
        $result['msg'] = $e->getMessage() ? $e->getMessage() : 'error occur';
        if (YII_DEBUG) {
            $result['file'] = $e->getFile();
            $result['line'] = $e->getLine();
            $result['trace'] = $e->getTraceAsString();
        }
        return $result;
    }
}