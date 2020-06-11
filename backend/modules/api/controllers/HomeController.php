<?php
/**
 * Created by PhpStorm.
 * User: lihuang
 * Date: 2020/6/11
 * Time: 3:52 PM
 */

namespace backend\modules\api\controllers;


use common\components\Controller;

class HomeController extends Controller
{
    public function actionIndex()
    {
        return 'home/index';
    }
}