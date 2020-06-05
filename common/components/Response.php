<?php

namespace common\components;


use yii\helpers\ArrayHelper;

class Response
{

    private $code;
    private $msg;
    private $data;

    /**
     * Response constructor.
     * @param $code
     * @param array $data
     * @param string $msg
     */
    public function __construct($code, $msg = '', $data = [])
    {
        if ($code instanceof \Exception) {
            $this->msg = $code->getMessage();
            $this->code = $code->getCode() ? $code->getCode() : 1000;
        } else {
            $this->code = $code;
            if ($this->code) {
                $this->msg = $msg;
            } else {
                $this->data = $data;
                $this->msg = $msg;
            }
        }

    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function isSuccess()
    {
        return $this->code == 0 ? true : false;
    }

    public function hasError()
    {
        return !$this->isSuccess();
    }

    public function asArray()
    {
        return [
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => is_array($this->data) ? ArrayHelper::toArray($this->data) : $this->data,
        ];
    }

    public function __toString()
    {
        return json_encode([
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => $this->data,
        ], JSON_UNESCAPED_UNICODE);
    }
}