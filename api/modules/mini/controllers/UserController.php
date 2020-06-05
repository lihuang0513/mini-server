<?php
/**
 * Created by PhpStorm.
 * User: lihuang
 * Date: 2020/6/3
 * Time: 5:04 PM
 */

namespace api\modules\mini\controllers;


use common\components\ApiController;
use common\components\Response;
use common\models\WxUser;
use yii\web\UnauthorizedHttpException;
use Yii;

class UserController extends ApiController
{

    /**
     * @property \EasyWeChat\MiniProgram\Application $mini_program 微信小程序实例
     * @return array|string
     */
    public function actionAuth()
    {
        try {
            $code = \Yii::$app->request->get('code');
            $mini_program = Yii::$app->wechat->miniProgram;
            if (!$mini_program) {
                throw new \Exception('未找到对应小程序实例', 119);
            }
            $result = $mini_program->auth->session($code);

            $session_key = $result['session_key'] ?? '';
            $open_id = $result['openid'] ?? '';
            $union_id = $result['unionid'] ?? '';
            if (!$open_id) {
                throw new \Exception('未找到用户信息', 119);
            }

            $token = WxUser::loginXcx($open_id, $session_key, $union_id);
            if (!$token) {
                throw new UnauthorizedHttpException('初始化用户信息失败', 401);
            }

            $u_token_key = "token.time." . $token;
            Yii::$app->cache->set($u_token_key, time(), 15*60);

            return [
                'token' => $token,
            ];
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function actionIndex()
    {
        return new Response(0,'授权成功，重新执行index');
    }


}