<?php
/**
 * Created by PhpStorm.
 * User: lihuang
 * Date: 2020/6/4
 * Time: 4:51 PM
 */

namespace common\models;


use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class WxUser extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return 'wx_user';
    }

    /**
     * 根据给到的ID查询身份。
     *
     * @param string|integer $id 被查询的ID
     * @return IdentityInterface|null 通过ID匹配到的身份对象
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * 根据 token 查询身份。
     *
     * @param string $token 被查询的 token
     * @return IdentityInterface|null 通过 token 得到的身份对象
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @return int|string 当前用户ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string 当前用户的（cookie）认证密钥
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public static function loginXcx($open_id, $session_key, $union_id = '')
    {
        try {
            $model = self::find()->where(['open_id' => $open_id])->one();
            if (!$model) {
                $model = new static();
                $model->open_id = $open_id;
            }

            $model->session_key = $session_key;
            if (isset($union_id) && $union_id) {
                $model->union_id = $union_id;
            }
            $model->productUserToken();
            if ($model->save()) {
                $token = $model->access_token;
            } else {
                /**
                 * @var $model static
                 */
                $model = self::find()->where(['open_id' => $open_id])->one();
                if ($model) {
                    $token = $model->access_token;
                } else {
                    $token = false;
                }

            }
            return $token;
        } catch (\Exception $e) {
            return '';
        }

    }

    public function productUserToken($save = false)
    {
        if (!$this->session_expired || strtotime($this->session_expired) < time()) {
            $this->access_token = md5($this->open_id . uniqid());
            $this->session_expired = date('Y-m-d H:i:s', strtotime('+30 days'));
            if ($save) {
                return $this->save();
            }
        }
        return true;
    }

}