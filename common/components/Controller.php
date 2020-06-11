<?php

namespace common\components;

class Controller extends \yii\web\Controller
{


    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        if(is_object($result) && $result instanceof Response) {
            $result = $result->asArray();
        }

        return $result;
    }

}