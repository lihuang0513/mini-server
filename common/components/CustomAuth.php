<?php
/**
 * Created by PhpStorm.
 * User: lihuang
 * Date: 2020/6/5
 * Time: 9:39 AM
 */

namespace common\components;


use yii\filters\auth\AuthMethod;
use Yii;
use yii\base\Action;
use yii\helpers\StringHelper;
use yii\web\Request;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use yii\web\User;

class CustomAuth extends AuthMethod
{
    /**
     * @var User the user object representing the user authentication status. If not set, the `user` application component will be used.
     */
    public $user;
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;
    /**
     * @var array list of action IDs that this filter will be applied to, but auth failure will not lead to error.
     * It may be used for actions, that are allowed for public, but return some additional data for authenticated users.
     * Defaults to empty, meaning authentication is not optional for any action.
     * Since version 2.0.10 action IDs can be specified as wildcards, e.g. `site/*`.
     * @see isOptional()
     * @since 2.0.7
     */
    public $optional = ['auth'];

    public $tokenParam = 'access_token';


    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        $response = $this->response ?: Yii::$app->getResponse();

        try {
            $identity = $this->authenticate(
                $this->user ?: Yii::$app->getUser(),
                $this->request ?: Yii::$app->getRequest(),
                $response
            );
        } catch (UnauthorizedHttpException $e) {
            if ($this->isOptional($action)) {
                return true;
            }

            throw $e;
        }

        if ($identity !== null || $this->isOptional($action)) {
            return true;
        }

        $this->challenge($response);
        $this->handleFailure($response);

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function handleFailure($response)
    {
        throw new UnauthorizedHttpException('Your request was made with invalid credentials.');
    }

    /**
     * Checks, whether authentication is optional for the given action.
     *
     * @param Action $action action to be checked.
     * @return bool whether authentication is optional or not.
     * @see optional
     * @since 2.0.7
     */
    protected function isOptional($action)
    {
        $id = $this->getActionId($action);
        foreach ($this->optional as $pattern) {
            if (StringHelper::matchWildcard($pattern, $id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate($user, $request, $response)
    {
        if(!$this->checkSign($request)) {
            $this->handleFailure($response);
        }

        if($request->isPost) {
            $accessToken = $request->post($this->tokenParam);
        } else {
            $accessToken = $request->get($this->tokenParam);
        }
        if (is_string($accessToken)) {
            $identity = $user->loginByAccessToken($accessToken, get_class($this));
            if ($identity !== null) {
                return $identity;
            }
        }

        if ($accessToken !== null) {
            $this->handleFailure($response);
        }

        return null;

    }

    public function checkSign($request)
    {

        try {
            if ($request->isGet) {
                $_get = \Yii::$app->request->get();
            } else {
                $_get = \Yii::$app->request->post();
            }

//            if (YII_ENV_DEV) {
//                return true;
//            }

            $time = $_get['_time'];
            $sign = $_get['_sign'];
            if (abs($time - time()) > 15 * 60) {
                throw new \Exception("Time no fixed:($time)vs " . time());
            }

            unset($_get['_sign']);
            ksort($_get);
            $before_sign = '';
            foreach ($_get as $_key => $_val) {
                if (is_array($_val) || is_object($_val)) {
                    continue;
                }
                $before_sign .= ($_key . '=' . $_val);
            }

            $before_sign .= Yii::$app->params['api_sign'];
            $sys_sign = md5(base64_encode($before_sign));

            if (strtolower($sys_sign) != strtolower($sign)) {

                throw new \Exception('签名失败');
            }

            $this->initParams();

            return true;
        }catch (\Exception $e){

            return false;
        }
    }

    private function initParams()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', "Bearer realm=\"{$this->tokenParam}\"");
    }
}