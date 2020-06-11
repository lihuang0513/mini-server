<?php

namespace backend\modules\api\controllers;


use common\components\Controller;

/**
 * Default controller for the `api` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return 'default/index';
    }
}
